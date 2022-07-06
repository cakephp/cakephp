<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         3.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Database;

use Cake\Cache\Engine\NullEngine;
use Cake\Collection\Collection;
use Cake\Core\App;
use Cake\Database\Connection;
use Cake\Database\Driver;
use Cake\Database\Driver\Mysql;
use Cake\Database\Driver\Sqlite;
use Cake\Database\Driver\Sqlserver;
use Cake\Database\Exception\MissingConnectionException;
use Cake\Database\Exception\MissingDriverException;
use Cake\Database\Exception\MissingExtensionException;
use Cake\Database\Exception\NestedTransactionRollbackException;
use Cake\Database\Log\LoggingStatement;
use Cake\Database\Log\QueryLogger;
use Cake\Database\Schema\CachedCollection;
use Cake\Database\StatementInterface;
use Cake\Datasource\ConnectionManager;
use Cake\Log\Log;
use Cake\TestSuite\TestCase;
use DateTime;
use Error;
use Exception;
use InvalidArgumentException;
use PDO;
use ReflectionMethod;
use ReflectionProperty;

/**
 * Tests Connection class
 */
class ConnectionTest extends TestCase
{
    /**
     * @var array<string>
     */
    protected $fixtures = ['core.Things'];

    /**
     * Where the NestedTransactionRollbackException was created.
     *
     * @var int
     */
    protected $rollbackSourceLine = -1;

    /**
     * Internal states of nested transaction.
     *
     * @var array
     */
    protected $nestedTransactionStates = [];

    /**
     * @var bool
     */
    protected $logState;

    /**
     * @var \Cake\Datasource\ConnectionInterface
     */
    protected $connection;

    /**
     * @var \Cake\Database\Log\QueryLogger
     */
    protected $defaultLogger;

    public function setUp(): void
    {
        parent::setUp();
        $this->connection = ConnectionManager::get('test');
        $this->defaultLogger = $this->connection->getLogger();

        $this->logState = $this->connection->isQueryLoggingEnabled();
        $this->connection->disableQueryLogging();

        static::setAppNamespace();
    }

    public function tearDown(): void
    {
        $this->connection->disableSavePoints();
        $this->connection->setLogger($this->defaultLogger);
        $this->connection->enableQueryLogging($this->logState);

        Log::reset();
        unset($this->connection);
        parent::tearDown();
    }

    /**
     * Auxiliary method to build a mock for a driver so it can be injected into
     * the connection object
     *
     * @return \Cake\Database\Driver|\PHPUnit\Framework\MockObject\MockObject
     */
    public function getMockFormDriver()
    {
        $driver = $this->getMockBuilder(Driver::class)->getMock();
        $driver->expects($this->once())
            ->method('enabled')
            ->will($this->returnValue(true));

        return $driver;
    }

    /**
     * Tests connecting to database
     */
    public function testConnect(): void
    {
        $this->assertTrue($this->connection->connect());
        $this->assertTrue($this->connection->isConnected());
    }

    /**
     * Tests creating a connection using no driver throws an exception
     */
    public function testNoDriver(): void
    {
        $this->expectException(MissingDriverException::class);
        $this->expectExceptionMessage('Could not find driver `` for connection ``.');
        $connection = new Connection([]);
    }

    /**
     * Tests creating a connection using an invalid driver throws an exception
     */
    public function testEmptyDriver(): void
    {
        $this->expectException(Error::class);
        $connection = new Connection(['driver' => false]);
    }

    /**
     * Tests creating a connection using an invalid driver throws an exception
     */
    public function testMissingDriver(): void
    {
        $this->expectException(MissingDriverException::class);
        $this->expectExceptionMessage('Could not find driver `\Foo\InvalidDriver` for connection ``.');
        $connection = new Connection(['driver' => '\Foo\InvalidDriver']);
    }

    /**
     * Tests trying to use a disabled driver throws an exception
     */
    public function testDisabledDriver(): void
    {
        $this->expectException(MissingExtensionException::class);
        $this->expectExceptionMessage(
            'Database driver DriverMock cannot be used due to a missing PHP extension or unmet dependency. ' .
            'Requested by connection "custom_connection_name"'
        );
        $mock = $this->getMockBuilder(Mysql::class)
            ->onlyMethods(['enabled'])
            ->setMockClassName('DriverMock')
            ->getMock();
        $connection = new Connection(['driver' => $mock, 'name' => 'custom_connection_name']);
    }

    /**
     * Tests that the `driver` option supports the short classname/plugin syntax.
     */
    public function testDriverOptionClassNameSupport(): void
    {
        $connection = new Connection(['driver' => 'TestDriver']);
        $this->assertInstanceOf('TestApp\Database\Driver\TestDriver', $connection->getDriver());

        $connection = new Connection(['driver' => 'TestPlugin.TestDriver']);
        $this->assertInstanceOf('TestPlugin\Database\Driver\TestDriver', $connection->getDriver());

        [, $name] = namespaceSplit(get_class($this->connection->getDriver()));
        $connection = new Connection(['driver' => $name]);
        $this->assertInstanceOf(get_class($this->connection->getDriver()), $connection->getDriver());
    }

    /**
     * Tests that connecting with invalid credentials or database name throws an exception
     */
    public function testWrongCredentials(): void
    {
        $config = ConnectionManager::getConfig('test');
        $this->skipIf(isset($config['url']), 'Datasource has dsn, skipping.');
        $connection = new Connection(['database' => '/dev/nonexistent'] + ConnectionManager::getConfig('test'));

        $e = null;
        try {
            $connection->connect();
        } catch (MissingConnectionException $e) {
        }

        $this->assertNotNull($e);
        $this->assertStringStartsWith(
            sprintf(
                'Connection to %s could not be established:',
                App::shortName(get_class($connection->getDriver()), 'Database/Driver')
            ),
            $e->getMessage()
        );
        $this->assertInstanceOf('PDOException', $e->getPrevious());
    }

    public function testConnectRetry(): void
    {
        $this->skipIf(!ConnectionManager::get('test')->getDriver() instanceof Sqlserver);

        $connection = new Connection(['driver' => 'RetryDriver']);
        $this->assertInstanceOf('TestApp\Database\Driver\RetryDriver', $connection->getDriver());

        try {
            $connection->connect();
        } catch (MissingConnectionException $e) {
        }

        $this->assertSame(4, $connection->getDriver()->getConnectRetries());
    }

    /**
     * Tests creation of prepared statements
     */
    public function testPrepare(): void
    {
        $sql = 'SELECT 1 + 1';
        $result = $this->connection->prepare($sql);
        $this->assertInstanceOf('Cake\Database\StatementInterface', $result);
        $this->assertEquals($sql, $result->queryString);

        $query = $this->connection->newQuery()->select('1 + 1');
        $result = $this->connection->prepare($query);
        $this->assertInstanceOf('Cake\Database\StatementInterface', $result);
        $sql = '#SELECT [`"\[]?1 \+ 1[`"\]]?#';
        $this->assertMatchesRegularExpression($sql, $result->queryString);
    }

    /**
     * Tests executing a simple query using bound values
     */
    public function testExecuteWithArguments(): void
    {
        $sql = 'SELECT 1 + ?';
        $statement = $this->connection->execute($sql, [1], ['integer']);
        $this->assertCount(1, $statement);
        $result = $statement->fetch();
        $this->assertEquals([2], $result);
        $statement->closeCursor();

        $sql = 'SELECT 1 + ? + ? AS total';
        $statement = $this->connection->execute($sql, [2, 3], ['integer', 'integer']);
        $this->assertCount(1, $statement);
        $result = $statement->fetch('assoc');
        $this->assertEquals(['total' => 6], $result);
        $statement->closeCursor();

        $sql = 'SELECT 1 + :one + :two AS total';
        $statement = $this->connection->execute($sql, ['one' => 2, 'two' => 3], ['one' => 'integer', 'two' => 'integer']);
        $this->assertCount(1, $statement);
        $result = $statement->fetch('assoc');
        $statement->closeCursor();
        $this->assertEquals(['total' => 6], $result);
    }

    /**
     * Tests executing a query with params and associated types
     */
    public function testExecuteWithArgumentsAndTypes(): void
    {
        $sql = "SELECT '2012-01-01' = ?";
        $statement = $this->connection->execute($sql, [new DateTime('2012-01-01')], ['date']);
        $result = $statement->fetch();
        $statement->closeCursor();
        $this->assertTrue((bool)$result[0]);
    }

    /**
     * test executing a buffered query interacts with Collection well.
     */
    public function testBufferedStatementCollectionWrappingStatement(): void
    {
        $this->skipIf(
            !($this->connection->getDriver() instanceof Sqlite),
            'Only required for SQLite driver which does not support buffered results natively'
        );

        $statement = $this->connection->query('SELECT * FROM things LIMIT 3');

        $collection = new Collection($statement);
        $result = $collection->extract('id')->toArray();
        $this->assertEquals(['1', '2'], $result);

        // Check iteration after extraction
        $result = [];
        foreach ($collection as $v) {
            $result[] = $v['id'];
        }
        $this->assertEquals(['1', '2'], $result);
    }

    /**
     * Tests that passing a unknown value to a query throws an exception
     */
    public function testExecuteWithMissingType(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $sql = 'SELECT ?';
        $statement = $this->connection->execute($sql, [new DateTime('2012-01-01')], ['bar']);
    }

    /**
     * Tests executing a query with no params also works
     */
    public function testExecuteWithNoParams(): void
    {
        $sql = 'SELECT 1';
        $statement = $this->connection->execute($sql);
        $result = $statement->fetch();
        $this->assertCount(1, $result);
        $this->assertEquals([1], $result);
        $statement->closeCursor();
    }

    /**
     * Tests it is possible to insert data into a table using matching types by key name
     */
    public function testInsertWithMatchingTypes(): void
    {
        $data = ['id' => '3', 'title' => 'a title', 'body' => 'a body'];
        $result = $this->connection->insert(
            'things',
            $data,
            ['id' => 'integer', 'title' => 'string', 'body' => 'string']
        );
        $this->assertInstanceOf('Cake\Database\StatementInterface', $result);
        $result->closeCursor();
        $result = $this->connection->execute('SELECT * from things where id = 3');
        $this->assertCount(1, $result);
        $row = $result->fetch('assoc');
        $result->closeCursor();
        $this->assertEquals($data, $row);
    }

    /**
     * Tests it is possible to insert data into a table using matching types by array position
     */
    public function testInsertWithPositionalTypes(): void
    {
        $data = ['id' => '3', 'title' => 'a title', 'body' => 'a body'];
        $result = $this->connection->insert(
            'things',
            $data,
            ['integer', 'string', 'string']
        );
        $result->closeCursor();
        $this->assertInstanceOf('Cake\Database\StatementInterface', $result);
        $result = $this->connection->execute('SELECT * from things where id  = 3');
        $this->assertCount(1, $result);
        $row = $result->fetch('assoc');
        $result->closeCursor();
        $this->assertEquals($data, $row);
    }

    /**
     * Tests an statement class can be reused for multiple executions
     */
    public function testStatementReusing(): void
    {
        $total = $this->connection->execute('SELECT COUNT(*) AS total FROM things');
        $result = $total->fetch('assoc');
        $this->assertEquals(2, $result['total']);
        $total->closeCursor();

        $total->execute();
        $result = $total->fetch('assoc');
        $this->assertEquals(2, $result['total']);
        $total->closeCursor();

        $result = $this->connection->execute('SELECT title, body  FROM things');
        $row = $result->fetch('assoc');
        $this->assertSame('a title', $row['title']);
        $this->assertSame('a body', $row['body']);

        $row = $result->fetch('assoc');
        $result->closeCursor();
        $this->assertSame('another title', $row['title']);
        $this->assertSame('another body', $row['body']);

        $result->execute();
        $row = $result->fetch('assoc');
        $result->closeCursor();
        $this->assertSame('a title', $row['title']);
    }

    /**
     * Tests that it is possible to pass PDO constants to the underlying statement
     * object for using alternate fetch types
     */
    public function testStatementFetchObject(): void
    {
        $statement = $this->connection->execute('SELECT title, body  FROM things');
        $row = $statement->fetch(PDO::FETCH_OBJ);
        $this->assertSame('a title', $row->title);
        $this->assertSame('a body', $row->body);
        $statement->closeCursor();
    }

    /**
     * Tests rows can be updated without specifying any conditions nor types
     */
    public function testUpdateWithoutConditionsNorTypes(): void
    {
        $title = 'changed the title!';
        $body = 'changed the body!';
        $this->connection->update('things', ['title' => $title, 'body' => $body]);
        $result = $this->connection->execute('SELECT * FROM things WHERE title = ? AND body = ?', [$title, $body]);
        $this->assertCount(2, $result);
        $result->closeCursor();
    }

    /**
     * Tests it is possible to use key => value conditions for update
     */
    public function testUpdateWithConditionsNoTypes(): void
    {
        $title = 'changed the title!';
        $body = 'changed the body!';
        $this->connection->update('things', ['title' => $title, 'body' => $body], ['id' => 2]);
        $result = $this->connection->execute('SELECT * FROM things WHERE title = ? AND body = ?', [$title, $body]);
        $this->assertCount(1, $result);
        $result->closeCursor();
    }

    /**
     * Tests it is possible to use key => value and string conditions for update
     */
    public function testUpdateWithConditionsCombinedNoTypes(): void
    {
        $title = 'changed the title!';
        $body = 'changed the body!';
        $this->connection->update('things', ['title' => $title, 'body' => $body], ['id' => 2, 'body is not null']);
        $result = $this->connection->execute('SELECT * FROM things WHERE title = ? AND body = ?', [$title, $body]);
        $this->assertCount(1, $result);
        $result->closeCursor();
    }

    /**
     * Tests you can bind types to update values
     */
    public function testUpdateWithTypes(): void
    {
        $title = 'changed the title!';
        $body = new DateTime('2012-01-01');
        $values = compact('title', 'body');
        $this->connection->update('things', $values, [], ['body' => 'date']);
        $result = $this->connection->execute('SELECT * FROM things WHERE title = :title AND body = :body', $values, ['body' => 'date']);
        $this->assertCount(2, $result);
        $row = $result->fetch('assoc');
        $this->assertSame('2012-01-01', $row['body']);
        $row = $result->fetch('assoc');
        $this->assertSame('2012-01-01', $row['body']);
        $result->closeCursor();
    }

    /**
     * Tests you can bind types to update values
     */
    public function testUpdateWithConditionsAndTypes(): void
    {
        $title = 'changed the title!';
        $body = new DateTime('2012-01-01');
        $values = compact('title', 'body');
        $this->connection->update('things', $values, ['id' => '1'], ['body' => 'date', 'id' => 'integer']);
        $result = $this->connection->execute('SELECT * FROM things WHERE title = :title AND body = :body', $values, ['body' => 'date']);
        $this->assertCount(1, $result);
        $row = $result->fetch('assoc');
        $this->assertSame('2012-01-01', $row['body']);
        $result->closeCursor();
    }

    /**
     * Tests delete from table with no conditions
     */
    public function testDeleteNoConditions(): void
    {
        $this->connection->delete('things');
        $result = $this->connection->execute('SELECT * FROM things');
        $this->assertCount(0, $result);
        $result->closeCursor();
    }

    /**
     * Tests delete from table with conditions
     */
    public function testDeleteWithConditions(): void
    {
        $this->connection->delete('things', ['id' => '1'], ['id' => 'integer']);
        $result = $this->connection->execute('SELECT * FROM things');
        $this->assertCount(1, $result);
        $result->closeCursor();

        $this->connection->delete('things', ['id' => '1'], ['id' => 'integer']);
        $result = $this->connection->execute('SELECT * FROM things');
        $this->assertCount(1, $result);
        $result->closeCursor();

        $this->connection->delete('things', ['id' => '2'], ['id' => 'integer']);
        $result = $this->connection->execute('SELECT * FROM things');
        $this->assertCount(0, $result);
        $result->closeCursor();
    }

    /**
     * Tests that it is possible to use simple database transactions
     */
    public function testSimpleTransactions(): void
    {
        $this->connection->begin();
        $this->assertTrue($this->connection->getDriver()->inTransaction());
        $this->connection->delete('things', ['id' => 1]);
        $this->connection->rollback();
        $this->assertFalse($this->connection->getDriver()->inTransaction());
        $result = $this->connection->execute('SELECT * FROM things');
        $this->assertCount(2, $result);
        $result->closeCursor();

        $this->connection->begin();
        $this->assertTrue($this->connection->getDriver()->inTransaction());
        $this->connection->delete('things', ['id' => 1]);
        $this->connection->commit();
        $this->assertFalse($this->connection->getDriver()->inTransaction());
        $result = $this->connection->execute('SELECT * FROM things');
        $this->assertCount(1, $result);
    }

    /**
     * Tests that the destructor of Connection generates a warning log
     * when transaction is not closed
     */
    public function testDestructorWithUncommittedTransaction(): void
    {
        $driver = $this->getMockFormDriver();
        $connection = new Connection(['driver' => $driver]);
        $connection->begin();
        $this->assertTrue($connection->inTransaction());

        $logger = $this->createMock('Psr\Log\AbstractLogger');
        $logger->expects($this->once())
            ->method('log')
            ->with('warning', $this->stringContains('The connection is going to be closed'));

        Log::setConfig('error', $logger);

        // Destroy the connection
        unset($connection);
    }

    /**
     * Tests that it is possible to use virtualized nested transaction
     * with early rollback algorithm
     */
    public function testVirtualNestedTransaction(): void
    {
        //starting 3 virtual transaction
        $this->connection->begin();
        $this->connection->begin();
        $this->connection->begin();
        $this->assertTrue($this->connection->getDriver()->inTransaction());

        $this->connection->delete('things', ['id' => 1]);
        $result = $this->connection->execute('SELECT * FROM things');
        $this->assertCount(1, $result);

        $this->connection->commit();
        $this->assertTrue($this->connection->getDriver()->inTransaction());
        $this->connection->rollback();
        $this->assertFalse($this->connection->getDriver()->inTransaction());

        $result = $this->connection->execute('SELECT * FROM things');
        $this->assertCount(2, $result);
    }

    /**
     * Tests that it is possible to use virtualized nested transaction
     * with early rollback algorithm
     */
    public function testVirtualNestedTransaction2(): void
    {
        //starting 3 virtual transaction
        $this->connection->begin();
        $this->connection->begin();
        $this->connection->begin();

        $this->connection->delete('things', ['id' => 1]);
        $result = $this->connection->execute('SELECT * FROM things');
        $this->assertCount(1, $result);
        $this->connection->rollback();

        $result = $this->connection->execute('SELECT * FROM things');
        $this->assertCount(2, $result);
    }

    /**
     * Tests that it is possible to use virtualized nested transaction
     * with early rollback algorithm
     */

    public function testVirtualNestedTransaction3(): void
    {
        //starting 3 virtual transaction
        $this->connection->begin();
        $this->connection->begin();
        $this->connection->begin();

        $this->connection->delete('things', ['id' => 1]);
        $result = $this->connection->execute('SELECT * FROM things');
        $this->assertCount(1, $result);
        $this->connection->commit();
        $this->connection->commit();
        $this->connection->commit();

        $result = $this->connection->execute('SELECT * FROM things');
        $this->assertCount(1, $result);
    }

    /**
     * Tests that it is possible to real use  nested transactions
     */
    public function testSavePoints(): void
    {
        $this->skipIf(!$this->connection->enableSavePoints(true));

        $this->connection->begin();
        $this->connection->delete('things', ['id' => 1]);

        $result = $this->connection->execute('SELECT * FROM things');
        $this->assertCount(1, $result);

        $this->connection->begin();
        $this->connection->delete('things', ['id' => 2]);
        $result = $this->connection->execute('SELECT * FROM things');
        $this->assertCount(0, $result);

        $this->connection->rollback();
        $result = $this->connection->execute('SELECT * FROM things');
        $this->assertCount(1, $result);

        $this->connection->rollback();
        $result = $this->connection->execute('SELECT * FROM things');
        $this->assertCount(2, $result);
    }

    /**
     * Tests that it is possible to real use  nested transactions
     */

    public function testSavePoints2(): void
    {
        $this->skipIf(!$this->connection->enableSavePoints(true));
        $this->connection->begin();
        $this->connection->delete('things', ['id' => 1]);

        $result = $this->connection->execute('SELECT * FROM things');
        $this->assertCount(1, $result);

        $this->connection->begin();
        $this->connection->delete('things', ['id' => 2]);
        $result = $this->connection->execute('SELECT * FROM things');
        $this->assertCount(0, $result);

        $this->connection->rollback();
        $result = $this->connection->execute('SELECT * FROM things');
        $this->assertCount(1, $result);

        $this->connection->commit();
        $result = $this->connection->execute('SELECT * FROM things');
        $this->assertCount(1, $result);
    }

    /**
     * Tests inTransaction()
     */
    public function testInTransaction(): void
    {
        $this->connection->begin();
        $this->assertTrue($this->connection->inTransaction());

        $this->connection->begin();
        $this->assertTrue($this->connection->inTransaction());

        $this->connection->commit();
        $this->assertTrue($this->connection->inTransaction());

        $this->connection->commit();
        $this->assertFalse($this->connection->inTransaction());

        $this->connection->begin();
        $this->assertTrue($this->connection->inTransaction());

        $this->connection->begin();
        $this->connection->rollback();
        $this->assertFalse($this->connection->inTransaction());
    }

    /**
     * Tests inTransaction() with save points
     */
    public function testInTransactionWithSavePoints(): void
    {
        $this->skipIf(
            $this->connection->getDriver() instanceof Sqlserver,
            'SQLServer fails when this test is included.'
        );
        $this->skipIf(!$this->connection->enableSavePoints(true));
        $this->connection->begin();
        $this->assertTrue($this->connection->inTransaction());

        $this->connection->begin();
        $this->assertTrue($this->connection->inTransaction());

        $this->connection->commit();
        $this->assertTrue($this->connection->inTransaction());

        $this->connection->commit();
        $this->assertFalse($this->connection->inTransaction());

        $this->connection->begin();
        $this->assertTrue($this->connection->inTransaction());

        $this->connection->begin();
        $this->connection->rollback();
        $this->assertTrue($this->connection->inTransaction());

        $this->connection->rollback();
        $this->assertFalse($this->connection->inTransaction());
    }

    /**
     * Tests connection can quote values to be safely used in query strings
     */
    public function testQuote(): void
    {
        $this->skipIf(!$this->connection->supportsQuoting());
        $expected = "'2012-01-01'";
        $result = $this->connection->quote(new DateTime('2012-01-01'), 'date');
        $this->assertEquals($expected, $result);

        $expected = "'1'";
        $result = $this->connection->quote(1, 'string');
        $this->assertEquals($expected, $result);

        $expected = "'hello'";
        $result = $this->connection->quote('hello', 'string');
        $this->assertEquals($expected, $result);
    }

    /**
     * Tests identifier quoting
     */
    public function testQuoteIdentifier(): void
    {
        $driver = $this->getMockBuilder('Cake\Database\Driver\Sqlite')
            ->onlyMethods(['enabled'])
            ->getMock();
        $driver->expects($this->once())
            ->method('enabled')
            ->will($this->returnValue(true));
        $connection = new Connection(['driver' => $driver]);

        $result = $connection->quoteIdentifier('name');
        $expected = '"name"';
        $this->assertEquals($expected, $result);

        $result = $connection->quoteIdentifier('Model.*');
        $expected = '"Model".*';
        $this->assertEquals($expected, $result);

        $result = $connection->quoteIdentifier('Items.No_ 2');
        $expected = '"Items"."No_ 2"';
        $this->assertEquals($expected, $result);

        $result = $connection->quoteIdentifier('Items.No_ 2 thing');
        $expected = '"Items"."No_ 2 thing"';
        $this->assertEquals($expected, $result);

        $result = $connection->quoteIdentifier('Items.No_ 2 thing AS thing');
        $expected = '"Items"."No_ 2 thing" AS "thing"';
        $this->assertEquals($expected, $result);

        $result = $connection->quoteIdentifier('Items.Item Category Code = :c1');
        $expected = '"Items"."Item Category Code" = :c1';
        $this->assertEquals($expected, $result);

        $result = $connection->quoteIdentifier('MTD()');
        $expected = 'MTD()';
        $this->assertEquals($expected, $result);

        $result = $connection->quoteIdentifier('(sm)');
        $expected = '(sm)';
        $this->assertEquals($expected, $result);

        $result = $connection->quoteIdentifier('name AS x');
        $expected = '"name" AS "x"';
        $this->assertEquals($expected, $result);

        $result = $connection->quoteIdentifier('Model.name AS x');
        $expected = '"Model"."name" AS "x"';
        $this->assertEquals($expected, $result);

        $result = $connection->quoteIdentifier('Function(Something.foo)');
        $expected = 'Function("Something"."foo")';
        $this->assertEquals($expected, $result);

        $result = $connection->quoteIdentifier('Function(SubFunction(Something.foo))');
        $expected = 'Function(SubFunction("Something"."foo"))';
        $this->assertEquals($expected, $result);

        $result = $connection->quoteIdentifier('Function(Something.foo) AS x');
        $expected = 'Function("Something"."foo") AS "x"';
        $this->assertEquals($expected, $result);

        $result = $connection->quoteIdentifier('name-with-minus');
        $expected = '"name-with-minus"';
        $this->assertEquals($expected, $result);

        $result = $connection->quoteIdentifier('my-name');
        $expected = '"my-name"';
        $this->assertEquals($expected, $result);

        $result = $connection->quoteIdentifier('Foo-Model.*');
        $expected = '"Foo-Model".*';
        $this->assertEquals($expected, $result);

        $result = $connection->quoteIdentifier('Team.P%');
        $expected = '"Team"."P%"';
        $this->assertEquals($expected, $result);

        $result = $connection->quoteIdentifier('Team.G/G');
        $expected = '"Team"."G/G"';
        $this->assertEquals($expected, $result);

        $result = $connection->quoteIdentifier('Model.name as y');
        $expected = '"Model"."name" AS "y"';
        $this->assertEquals($expected, $result);

        $result = $connection->quoteIdentifier('nämé');
        $expected = '"nämé"';
        $this->assertEquals($expected, $result);

        $result = $connection->quoteIdentifier('aßa.nämé');
        $expected = '"aßa"."nämé"';
        $this->assertEquals($expected, $result);

        $result = $connection->quoteIdentifier('aßa.*');
        $expected = '"aßa".*';
        $this->assertEquals($expected, $result);

        $result = $connection->quoteIdentifier('Modeß.nämé as y');
        $expected = '"Modeß"."nämé" AS "y"';
        $this->assertEquals($expected, $result);

        $result = $connection->quoteIdentifier('Model.näme Datum as y');
        $expected = '"Model"."näme Datum" AS "y"';
        $this->assertEquals($expected, $result);
    }

    /**
     * Tests default return vale for logger() function
     */
    public function testGetLoggerDefault(): void
    {
        $logger = $this->connection->getLogger();
        $this->assertInstanceOf('Cake\Database\Log\QueryLogger', $logger);
        $this->assertSame($logger, $this->connection->getLogger());
    }

    /**
     * Tests setting and getting the logger object
     */
    public function testGetAndSetLogger(): void
    {
        $logger = new QueryLogger();
        $this->connection->setLogger($logger);
        $this->assertSame($logger, $this->connection->getLogger());
    }

    /**
     * Tests that statements are decorated with a logger when logQueries is set to true
     */
    public function testLoggerDecorator(): void
    {
        $logger = new QueryLogger();
        $this->connection->enableQueryLogging(true);
        $this->connection->setLogger($logger);
        $st = $this->connection->prepare('SELECT 1');
        $this->assertInstanceOf(LoggingStatement::class, $st);
        $this->assertSame($logger, $st->getLogger());

        $this->connection->enableQueryLogging(false);
        $st = $this->connection->prepare('SELECT 1');
        $this->assertNotInstanceOf('Cake\Database\Log\LoggingStatement', $st);
    }

    /**
     * test enableQueryLogging method
     */
    public function testEnableQueryLogging(): void
    {
        $this->connection->enableQueryLogging(true);
        $this->assertTrue($this->connection->isQueryLoggingEnabled());

        $this->connection->disableQueryLogging();
        $this->assertFalse($this->connection->isQueryLoggingEnabled());
    }

    /**
     * Tests that log() function logs to the configured query logger
     */
    public function testLogFunction(): void
    {
        Log::setConfig('queries', ['className' => 'Array']);
        $this->connection->enableQueryLogging();
        $this->connection->log('SELECT 1');

        $messages = Log::engine('queries')->read();
        $this->assertCount(1, $messages);
        $this->assertSame('debug: connection=test duration=0 rows=0 SELECT 1', $messages[0]);
    }

    /**
     * @see https://github.com/cakephp/cakephp/issues/14676
     */
    public function testLoggerDecoratorDoesNotPrematurelyFetchRecords(): void
    {
        Log::setConfig('queries', ['className' => 'Array']);
        $logger = new QueryLogger();
        $this->connection->enableQueryLogging(true);
        $this->connection->setLogger($logger);
        $st = $this->connection->execute('SELECT * FROM things');
        $this->assertInstanceOf(LoggingStatement::class, $st);

        $messages = Log::engine('queries')->read();
        $this->assertCount(0, $messages);

        $expected = [
            [1, 'a title', 'a body'],
            [2, 'another title', 'another body'],
        ];
        $results = $st->fetchAll();
        $this->assertEquals($expected, $results);

        $messages = Log::engine('queries')->read();
        $this->assertCount(1, $messages);

        $st = $this->connection->execute('SELECT * FROM things WHERE id = 0');
        $this->assertSame(0, $st->rowCount());

        $messages = Log::engine('queries')->read();
        $this->assertCount(2, $messages, 'Select queries without any matching rows should also be logged.');
    }

    /**
     * Tests that begin and rollback are also logged
     */
    public function testLogBeginRollbackTransaction(): void
    {
        Log::setConfig('queries', ['className' => 'Array']);

        $connection = $this
            ->getMockBuilder(Connection::class)
            ->onlyMethods(['connect'])
            ->disableOriginalConstructor()
            ->getMock();
        $connection->enableQueryLogging(true);

        $this->deprecated(function () use ($connection) {
            $driver = $this->getMockFormDriver();
            $connection->setDriver($driver);
        });

        $connection->begin();
        $connection->begin(); //This one will not be logged
        $connection->rollback();

        $messages = Log::engine('queries')->read();
        $this->assertCount(2, $messages);
        $this->assertSame('debug: connection= duration=0 rows=0 BEGIN', $messages[0]);
        $this->assertSame('debug: connection= duration=0 rows=0 ROLLBACK', $messages[1]);
    }

    /**
     * Tests that commits are logged
     */
    public function testLogCommitTransaction(): void
    {
        $driver = $this->getMockFormDriver();
        $connection = $this->getMockBuilder(Connection::class)
            ->onlyMethods(['connect'])
            ->setConstructorArgs([['driver' => $driver]])
            ->getMock();

        Log::setConfig('queries', ['className' => 'Array']);
        $connection->enableQueryLogging(true);
        $connection->begin();
        $connection->commit();

        $messages = Log::engine('queries')->read();
        $this->assertCount(2, $messages);
        $this->assertSame('debug: connection= duration=0 rows=0 BEGIN', $messages[0]);
        $this->assertSame('debug: connection= duration=0 rows=0 COMMIT', $messages[1]);
    }

    /**
     * Tests setting and getting the cacher object
     */
    public function testGetAndSetCacher(): void
    {
        $cacher = new NullEngine();
        $this->connection->setCacher($cacher);
        $this->assertSame($cacher, $this->connection->getCacher());
    }

    /**
     * Tests that the transactional method will start and commit a transaction
     * around some arbitrary function passed as argument
     */
    public function testTransactionalSuccess(): void
    {
        $driver = $this->getMockFormDriver();
        $connection = $this->getMockBuilder(Connection::class)
            ->onlyMethods(['connect', 'commit', 'begin'])
            ->setConstructorArgs([['driver' => $driver]])
            ->getMock();
        $connection->expects($this->once())->method('begin');
        $connection->expects($this->once())->method('commit');
        $result = $connection->transactional(function ($conn) use ($connection) {
            $this->assertSame($connection, $conn);

            return 'thing';
        });
        $this->assertSame('thing', $result);
    }

    /**
     * Tests that the transactional method will rollback the transaction if false
     * is returned from the callback
     */
    public function testTransactionalFail(): void
    {
        $driver = $this->getMockFormDriver();
        $connection = $this->getMockBuilder(Connection::class)
            ->onlyMethods(['connect', 'commit', 'begin', 'rollback'])
            ->setConstructorArgs([['driver' => $driver]])
            ->getMock();
        $connection->expects($this->once())->method('begin');
        $connection->expects($this->once())->method('rollback');
        $connection->expects($this->never())->method('commit');
        $result = $connection->transactional(function ($conn) use ($connection) {
            $this->assertSame($connection, $conn);

            return false;
        });
        $this->assertFalse($result);
    }

    /**
     * Tests that the transactional method will rollback the transaction
     * and throw the same exception if the callback raises one
     *
     * @throws \InvalidArgumentException
     */
    public function testTransactionalWithException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $driver = $this->getMockFormDriver();
        $connection = $this->getMockBuilder(Connection::class)
            ->onlyMethods(['connect', 'commit', 'begin', 'rollback'])
            ->setConstructorArgs([['driver' => $driver]])
            ->getMock();
        $connection->expects($this->once())->method('begin');
        $connection->expects($this->once())->method('rollback');
        $connection->expects($this->never())->method('commit');
        $connection->transactional(function ($conn) use ($connection): void {
            $this->assertSame($connection, $conn);
            throw new InvalidArgumentException();
        });
    }

    /**
     * Tests it is possible to set a schema collection object
     */
    public function testSetSchemaCollection(): void
    {
        $driver = $this->getMockFormDriver();
        $connection = $this->getMockBuilder(Connection::class)
            ->onlyMethods(['connect'])
            ->setConstructorArgs([['driver' => $driver]])
            ->getMock();

        $schema = $connection->getSchemaCollection();
        $this->assertInstanceOf('Cake\Database\Schema\Collection', $schema);

        $schema = $this->getMockBuilder('Cake\Database\Schema\Collection')
            ->setConstructorArgs([$connection])
            ->getMock();
        $connection->setSchemaCollection($schema);
        $this->assertSame($schema, $connection->getSchemaCollection());
    }

    /**
     * Test CachedCollection creation with default and custom cache key prefix.
     */
    public function testGetCachedCollection(): void
    {
        $driver = $this->getMockFormDriver();
        $connection = $this->getMockBuilder(Connection::class)
            ->onlyMethods(['connect'])
            ->setConstructorArgs([[
                'driver' => $driver,
                'name' => 'default',
                'cacheMetadata' => true,
            ]])
            ->getMock();

        $schema = $connection->getSchemaCollection();
        $this->assertInstanceOf(CachedCollection::class, $schema);
        $this->assertSame('default_key', $schema->cacheKey('key'));

        $driver = $this->getMockFormDriver();
        $connection = $this->getMockBuilder(Connection::class)
            ->onlyMethods(['connect'])
            ->setConstructorArgs([[
                'driver' => $driver,
                'name' => 'default',
                'cacheMetadata' => true,
                'cacheKeyPrefix' => 'foo',
            ]])
            ->getMock();

        $schema = $connection->getSchemaCollection();
        $this->assertInstanceOf(CachedCollection::class, $schema);
        $this->assertSame('foo_key', $schema->cacheKey('key'));
    }

    /**
     * Tests that allowed nesting of commit/rollback operations doesn't
     * throw any exceptions.
     */
    public function testNestedTransactionRollbackExceptionNotThrown(): void
    {
        $this->connection->transactional(function () {
            $this->connection->transactional(function () {
                return true;
            });

            return true;
        });
        $this->assertFalse($this->connection->inTransaction());

        $this->connection->transactional(function () {
            $this->connection->transactional(function () {
                return true;
            });

            return false;
        });
        $this->assertFalse($this->connection->inTransaction());

        $this->connection->transactional(function () {
            $this->connection->transactional(function () {
                return false;
            });

            return false;
        });
        $this->assertFalse($this->connection->inTransaction());
    }

    /**
     * Tests that not allowed nesting of commit/rollback operations throws
     * a NestedTransactionRollbackException.
     */
    public function testNestedTransactionRollbackExceptionThrown(): void
    {
        $this->rollbackSourceLine = -1;

        $e = null;
        try {
            $this->connection->transactional(function () {
                $this->connection->transactional(function () {
                    return false;
                });
                $this->rollbackSourceLine = __LINE__ - 1;
                if (PHP_VERSION_ID >= 80200) {
                    $this->rollbackSourceLine -= 2;
                }

                return true;
            });

            $this->fail('NestedTransactionRollbackException should be thrown');
        } catch (NestedTransactionRollbackException $e) {
        }

        $trace = $e->getTrace();
        $this->assertEquals(__FILE__, $trace[1]['file']);
        $this->assertEquals($this->rollbackSourceLine, $trace[1]['line']);
    }

    /**
     * Tests more detail about that not allowed nesting of rollback/commit
     * operations throws a NestedTransactionRollbackException.
     */
    public function testNestedTransactionStates(): void
    {
        $this->rollbackSourceLine = -1;
        $this->nestedTransactionStates = [];

        $e = null;
        try {
            $this->connection->transactional(function () {
                $this->pushNestedTransactionState();

                $this->connection->transactional(function () {
                    return true;
                });

                $this->connection->transactional(function () {
                    $this->pushNestedTransactionState();

                    $this->connection->transactional(function () {
                        return false;
                    });
                    $this->rollbackSourceLine = __LINE__ - 1;
                    if (PHP_VERSION_ID >= 80200) {
                        $this->rollbackSourceLine -= 2;
                    }

                    $this->pushNestedTransactionState();

                    return true;
                });

                $this->connection->transactional(function () {
                    return false;
                });

                $this->pushNestedTransactionState();

                return true;
            });

            $this->fail('NestedTransactionRollbackException should be thrown');
        } catch (NestedTransactionRollbackException $e) {
        }

        $this->pushNestedTransactionState();

        $this->assertSame([false, false, true, true, false], $this->nestedTransactionStates);
        $this->assertFalse($this->connection->inTransaction());

        $trace = $e->getTrace();
        $this->assertEquals(__FILE__, $trace[1]['file']);
        $this->assertEquals($this->rollbackSourceLine, $trace[1]['line']);
    }

    /**
     * Helper method to trace nested transaction states.
     */
    public function pushNestedTransactionState(): void
    {
        $method = new ReflectionMethod($this->connection, 'wasNestedTransactionRolledback');
        $method->setAccessible(true);
        $this->nestedTransactionStates[] = $method->invoke($this->connection);
    }

    /**
     * Tests that the connection is restablished whenever it is interrupted
     * after having used the connection at least once.
     */
    public function testAutomaticReconnect(): void
    {
        $conn = clone $this->connection;
        $statement = $conn->query('SELECT 1');
        $statement->execute();
        $statement->closeCursor();

        $prop = new ReflectionProperty($conn, '_driver');
        $prop->setAccessible(true);
        $oldDriver = $prop->getValue($conn);
        $newDriver = $this->getMockBuilder(Driver::class)->getMock();
        $prop->setValue($conn, $newDriver);

        $newDriver->expects($this->exactly(2))
            ->method('prepare')
            ->will($this->onConsecutiveCalls(
                $this->throwException(new Exception('server gone away')),
                $this->returnValue($statement)
            ));

        $res = $conn->query('SELECT 1');
        $this->assertInstanceOf(StatementInterface::class, $res);
    }

    /**
     * Tests that the connection is not restablished whenever it is interrupted
     * inside a transaction.
     */
    public function testNoAutomaticReconnect(): void
    {
        $conn = clone $this->connection;
        $statement = $conn->query('SELECT 1');
        $statement->execute();
        $statement->closeCursor();

        $conn->begin();

        $prop = new ReflectionProperty($conn, '_driver');
        $prop->setAccessible(true);
        $oldDriver = $prop->getValue($conn);
        $newDriver = $this->getMockBuilder(Driver::class)->getMock();
        $prop->setValue($conn, $newDriver);

        $newDriver->expects($this->once())
            ->method('prepare')
            ->will($this->throwException(new Exception('server gone away')));

        try {
            $conn->query('SELECT 1');
        } catch (Exception $e) {
        }
        $this->assertInstanceOf(Exception::class, $e ?? null);

        $prop->setValue($conn, $oldDriver);
        $conn->rollback();
    }
}
