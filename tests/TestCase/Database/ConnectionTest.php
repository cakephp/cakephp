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
use Cake\Core\App;
use Cake\Database\Connection;
use Cake\Database\Driver;
use Cake\Database\Driver\Mysql;
use Cake\Database\Driver\Sqlserver;
use Cake\Database\Exception\MissingConnectionException;
use Cake\Database\Exception\MissingDriverException;
use Cake\Database\Exception\MissingExtensionException;
use Cake\Database\Exception\NestedTransactionRollbackException;
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
use TestApp\Database\Driver\DisabledDriver;
use TestApp\Database\Driver\RetryDriver;
use function Cake\Core\namespaceSplit;

/**
 * Tests Connection class
 */
class ConnectionTest extends TestCase
{
    /**
     * @var array<string>
     */
    protected array $fixtures = ['core.Things'];

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
     * @var \Cake\Database\Connection
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

        static::setAppNamespace();
    }

    public function tearDown(): void
    {
        parent::tearDown();
        $this->connection->disableSavePoints();

        ConnectionManager::drop('test:read');
        ConnectionManager::dropAlias('test:read');
        Log::reset();
        unset($this->connection);
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
            ->willReturn(true);

        return $driver;
    }

    /**
     * Tests creating a connection using no driver throws an exception
     */
    public function testNoDriver(): void
    {
        $this->expectException(MissingDriverException::class);
        $this->expectExceptionMessage('Could not find driver `` for connection ``.');
        new Connection([]);
    }

    /**
     * Tests creating a connection using an invalid driver throws an exception
     */
    public function testEmptyDriver(): void
    {
        $this->expectException(Error::class);
        new Connection(['driver' => false]);
    }

    /**
     * Tests creating a connection using an invalid driver throws an exception
     */
    public function testMissingDriver(): void
    {
        $this->expectException(MissingDriverException::class);
        $this->expectExceptionMessage('Could not find driver `\Foo\InvalidDriver` for connection ``.');
        new Connection(['driver' => '\Foo\InvalidDriver']);
    }

    /**
     * Tests trying to use a disabled driver throws an exception
     */
    public function testDisabledDriver(): void
    {
        $this->expectException(MissingExtensionException::class);
        $this->expectExceptionMessage(
            'Database driver `DriverMock` cannot be used due to a missing PHP extension or unmet dependency. ' .
            'Requested by connection `custom_connection_name`'
        );
        $mock = $this->getMockBuilder(Mysql::class)
            ->onlyMethods(['enabled'])
            ->setMockClassName('DriverMock')
            ->getMock();
        new Connection(['driver' => $mock, 'name' => 'custom_connection_name']);
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
     * Test providing a unique read config only creates separate drivers.
     */
    public function testDifferentReadDriver(): void
    {
        $this->skipIf(!extension_loaded('pdo_sqlite'), 'Skipping as SQLite extension is missing');
        $config = ConnectionManager::getConfig('test') + ['read' => ['database' => 'read_test.db']];
        $connection = new Connection($config);
        $this->assertNotSame($connection->getDriver(Connection::ROLE_READ), $connection->getDriver(Connection::ROLE_WRITE));
        $this->assertSame(Connection::ROLE_READ, $connection->getDriver(Connection::ROLE_READ)->getRole());
        $this->assertSame(Connection::ROLE_WRITE, $connection->getDriver(Connection::ROLE_WRITE)->getRole());
    }

    /**
     * Test providing a unique write config only creates separate drivers.
     */
    public function testDifferentWriteDriver(): void
    {
        $this->skipIf(!extension_loaded('pdo_sqlite'), 'Skipping as SQLite extension is missing');
        $config = ConnectionManager::getConfig('test') + ['write' => ['database' => 'read_test.db']];
        $connection = new Connection($config);
        $this->assertNotSame($connection->getDriver(Connection::ROLE_READ), $connection->getDriver(Connection::ROLE_WRITE));
        $this->assertSame(Connection::ROLE_READ, $connection->getDriver(Connection::ROLE_READ)->getRole());
        $this->assertSame(Connection::ROLE_WRITE, $connection->getDriver(Connection::ROLE_WRITE)->getRole());
    }

    /**
     * Test providing the same read and write config uses a shared driver.
     */
    public function testSameReadWriteDriver(): void
    {
        $this->skipIf(!extension_loaded('pdo_sqlite'), 'Skipping as SQLite extension is missing');
        $config = ConnectionManager::getConfig('test') + ['read' => ['database' => 'read_test.db'], 'write' => ['database' => 'read_test.db']];
        $connection = new Connection($config);
        $this->assertSame($connection->getDriver(Connection::ROLE_READ), $connection->getDriver(Connection::ROLE_WRITE));
        $this->assertSame(Connection::ROLE_WRITE, $connection->getDriver(Connection::ROLE_READ)->getRole());
        $this->assertSame(Connection::ROLE_WRITE, $connection->getDriver(Connection::ROLE_WRITE)->getRole());
    }

    public function testDisabledReadWriteDriver(): void
    {
        $this->skipIf(!extension_loaded('pdo_sqlite'), 'Skipping as SQLite extension is missing');
        $config = ['driver' => DisabledDriver::class] + ConnectionManager::getConfig('test');

        $this->expectException(MissingExtensionException::class);
        new Connection($config);
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
            $connection->getDriver()->connect();
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
        $this->assertInstanceOf(RetryDriver::class, $connection->getDriver());

        try {
            $connection->execute('SELECT 1');
        } catch (MissingConnectionException $e) {
            $this->assertSame(4, $connection->getDriver()->getConnectRetries());
        }
    }

    /**
     * Tests executing a simple query using bound values
     */
    public function testExecuteWithArguments(): void
    {
        $sql = 'SELECT 1 + ?';
        $statement = $this->connection->execute($sql, [1], ['integer']);
        $result = $statement->fetchAll();
        $this->assertCount(1, $result);
        $this->assertEquals([2], $result[0]);
        $statement->closeCursor();

        $sql = 'SELECT 1 + ? + ? AS total';
        $statement = $this->connection->execute($sql, [2, 3], ['integer', 'integer']);
        $result = $statement->fetchAll('assoc');
        $statement->closeCursor();
        $this->assertCount(1, $result);
        $this->assertEquals(['total' => 6], $result[0]);

        $sql = 'SELECT 1 + :one + :two AS total';
        $statement = $this->connection->execute($sql, ['one' => 2, 'two' => 3], ['one' => 'integer', 'two' => 'integer']);
        $result = $statement->fetchAll('assoc');
        $statement->closeCursor();
        $this->assertCount(1, $result);
        $this->assertEquals(['total' => 6], $result[0]);
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
     * Tests that passing a unknown value to a query throws an exception
     */
    public function testExecuteWithMissingType(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $sql = 'SELECT ?';
        $this->connection->execute($sql, [new DateTime('2012-01-01')], ['bar']);
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
        $this->assertInstanceOf(StatementInterface::class, $result);
        $result->closeCursor();
        $result = $this->connection->execute('SELECT * from things where id = 3');
        $rows = $result->fetchAll('assoc');
        $this->assertCount(1, $rows);
        $result->closeCursor();
        $this->assertEquals($data, $rows[0]);
    }

    /**
     * Tests insertQuery
     */
    public function testInsertQuery(): void
    {
        $data = ['id' => '3', 'title' => 'a title', 'body' => 'a body'];
        $query = $this->connection->insertQuery(
            'things',
            $data,
            ['id' => 'integer', 'title' => 'string', 'body' => 'string']
        );
        $result = $query->execute();
        $this->assertInstanceOf('Cake\Database\StatementInterface', $result);
        $result->closeCursor();

        $result = $this->connection->execute('SELECT * from things where id = 3');
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
        $this->assertInstanceOf(StatementInterface::class, $result);
        $result = $this->connection->execute('SELECT * from things where id  = 3');
        $rows = $result->fetchAll('assoc');
        $result->closeCursor();
        $this->assertCount(1, $rows);
        $this->assertEquals($data, $rows[0]);
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
        $this->assertCount(2, $result->fetchAll());
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
        $this->assertCount(1, $result->fetchAll());
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
        $this->assertCount(1, $result->fetchAll());
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
        $rows = $result->fetchAll('assoc');
        $this->assertCount(2, $rows);
        $this->assertSame('2012-01-01', $rows[0]['body']);
        $this->assertSame('2012-01-01', $rows[1]['body']);
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
        $rows = $result->fetchAll('assoc');
        $this->assertCount(1, $rows);
        $this->assertSame('2012-01-01', $rows[0]['body']);
        $result->closeCursor();
    }

    /**
     * Tests you can bind types to update values
     */
    public function testUpdateQueryWithConditionsAndTypes(): void
    {
        $title = 'changed the title!';
        $body = new DateTime('2012-01-01');
        $values = compact('title', 'body');
        $query = $this->connection->updateQuery('things', $values, ['id' => '1'], ['body' => 'date', 'id' => 'integer']);
        $query->execute()->closeCursor();

        $result = $this->connection->execute('SELECT * FROM things WHERE title = :title AND body = :body', $values, ['body' => 'date']);
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
        $this->assertCount(0, $result->fetchAll());
        $result->closeCursor();
    }

    /**
     * Tests delete from table with conditions
     */
    public function testDeleteWithConditions(): void
    {
        $this->connection->delete('things', ['id' => '1'], ['id' => 'integer']);
        $result = $this->connection->execute('SELECT * FROM things');
        $this->assertCount(1, $result->fetchAll());
        $result->closeCursor();

        $this->connection->delete('things', ['id' => '2'], ['id' => 'integer']);
        $result = $this->connection->execute('SELECT * FROM things');
        $this->assertCount(0, $result->fetchAll());
        $result->closeCursor();
    }

    /**
     * Tests delete from table with conditions
     */
    public function testDeleteQuery(): void
    {
        $query = $this->connection->deleteQuery('things', ['id' => '1'], ['id' => 'integer']);
        $query->execute()->closeCursor();
        $result = $this->connection->execute('SELECT * FROM things');
        $result->closeCursor();

        $query = $this->connection->deleteQuery('things')->where(['id' => 2], ['id' => 'integer']);
        $query->execute()->closeCursor();
        $result = $this->connection->execute('SELECT * FROM things');
        $this->assertCount(0, $result->fetchAll());
        $result->closeCursor();
    }

    /**
     * Test basic selectQuery behavior
     */
    public function testSelectQuery(): void
    {
        $query = $this->connection->selectQuery(['*'], 'things');
        $statement = $query->execute();
        $row = $statement->fetchAssoc();
        $statement->closeCursor();

        $this->assertArrayHasKey('title', $row);
        $this->assertArrayHasKey('body', $row);
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
        $this->assertCount(2, $result->fetchAll());
        $result->closeCursor();

        $this->connection->begin();
        $this->assertTrue($this->connection->getDriver()->inTransaction());
        $this->connection->delete('things', ['id' => 1]);
        $this->connection->commit();
        $this->assertFalse($this->connection->getDriver()->inTransaction());
        $result = $this->connection->execute('SELECT * FROM things');
        $this->assertCount(1, $result->fetchAll());
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
        $this->assertCount(1, $result->fetchAll());

        $this->connection->commit();
        $this->assertTrue($this->connection->getDriver()->inTransaction());
        $this->connection->rollback();
        $this->assertFalse($this->connection->getDriver()->inTransaction());

        $result = $this->connection->execute('SELECT * FROM things');
        $this->assertCount(2, $result->fetchAll());
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
        $this->assertCount(1, $result->fetchAll());
        $this->connection->rollback();

        $result = $this->connection->execute('SELECT * FROM things');
        $this->assertCount(2, $result->fetchAll());
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
        $this->assertCount(1, $result->fetchAll());
        $this->connection->commit();
        $this->connection->commit();
        $this->connection->commit();

        $result = $this->connection->execute('SELECT * FROM things');
        $this->assertCount(1, $result->fetchAll());
    }

    /**
     * Tests that it is possible to real use  nested transactions
     */
    public function testSavePoints(): void
    {
        $this->connection->enableSavePoints(true);
        $this->skipIf(!$this->connection->isSavePointsEnabled(), 'Database driver doesn\'t support save points');

        $this->connection->begin();
        $this->connection->delete('things', ['id' => 1]);

        $result = $this->connection->execute('SELECT * FROM things');
        $this->assertCount(1, $result->fetchAll());

        $this->connection->begin();
        $this->connection->delete('things', ['id' => 2]);
        $result = $this->connection->execute('SELECT * FROM things');
        $this->assertCount(0, $result->fetchAll());

        $this->connection->rollback();
        $result = $this->connection->execute('SELECT * FROM things');
        $this->assertCount(1, $result->fetchAll());

        $this->connection->rollback();
        $result = $this->connection->execute('SELECT * FROM things');
        $this->assertCount(2, $result->fetchAll());
    }

    /**
     * Tests that it is possible to real use  nested transactions
     */

    public function testSavePoints2(): void
    {
        $this->connection->enableSavePoints(true);
        $this->skipIf(!$this->connection->isSavePointsEnabled(), 'Database driver doesn\'t support save points');

        $this->connection->begin();
        $this->connection->delete('things', ['id' => 1]);

        $result = $this->connection->execute('SELECT * FROM things');
        $this->assertCount(1, $result->fetchAll());

        $this->connection->begin();
        $this->connection->delete('things', ['id' => 2]);
        $result = $this->connection->execute('SELECT * FROM things');
        $this->assertCount(0, $result->fetchAll());

        $this->connection->rollback();
        $result = $this->connection->execute('SELECT * FROM things');
        $this->assertCount(1, $result->fetchAll());

        $this->connection->commit();
        $result = $this->connection->execute('SELECT * FROM things');
        $this->assertCount(1, $result->fetchAll());
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

        $this->connection->enableSavePoints(true);
        $this->skipIf(!$this->connection->isSavePointsEnabled(), 'Database driver doesn\'t support save points');

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
            ->onlyMethods(['commit', 'begin'])
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
            ->onlyMethods(['commit', 'begin', 'rollback'])
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
            ->onlyMethods(['commit', 'begin', 'rollback'])
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
        $connection = new Connection(['driver' => $driver]);

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

        $connection = new Connection([
            'driver' => $driver,
            'name' => 'default',
            'cacheMetadata' => true,
        ]);

        $schema = $connection->getSchemaCollection();
        $this->assertInstanceOf(CachedCollection::class, $schema);
        $this->assertSame('default_key', $schema->cacheKey('key'));

        $driver = $this->getMockFormDriver();
        $connection = new Connection([
            'driver' => $driver,
            'name' => 'default',
            'cacheMetadata' => true,
            'cacheKeyPrefix' => 'foo',
        ]);

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
        $this->nestedTransactionStates[] = $method->invoke($this->connection);
    }

    /**
     * Tests that the connection is restablished whenever it is interrupted
     * after having used the connection at least once.
     */
    public function testAutomaticReconnect2(): void
    {
        $conn = clone $this->connection;
        $statement = $conn->execute('SELECT 1');
        $statement->execute();
        $statement->closeCursor();

            $newDriver = $this->getMockBuilder(Driver::class)->getMock();
            $prop = new ReflectionProperty($conn, 'readDriver');
            $prop->setAccessible(true);
            $prop->setValue($conn, $newDriver);
            $prop = new ReflectionProperty($conn, 'writeDriver');
            $prop->setAccessible(true);
            $prop->setValue($conn, $newDriver);

        $newDriver->expects($this->exactly(2))
            ->method('execute')
            ->willReturnOnConsecutiveCalls(
                $this->throwException(new Exception('server gone away')),
                $statement
            );

        $res = $conn->execute('SELECT 1');
        $this->assertInstanceOf(StatementInterface::class, $res);
    }

    /**
     * Tests that the connection is not restablished whenever it is interrupted
     * inside a transaction.
     */
    public function testNoAutomaticReconnect(): void
    {
        $conn = clone $this->connection;
        $statement = $conn->execute('SELECT 1');
        $statement->execute();
        $statement->closeCursor();

            $conn->begin();

            $newDriver = $this->getMockBuilder(Driver::class)->getMock();
            $prop = new ReflectionProperty($conn, 'readDriver');
            $prop->setAccessible(true);
            $prop->setValue($conn, $newDriver);
            $prop = new ReflectionProperty($conn, 'writeDriver');
            $prop->setAccessible(true);
            $oldDriver = $prop->getValue($conn);
            $prop->setValue($conn, $newDriver);

        $newDriver->expects($this->once())
            ->method('execute')
            ->will($this->throwException(new Exception('server gone away')));

        try {
            $conn->execute('SELECT 1');
        } catch (Exception $e) {
            $this->assertInstanceOf(Exception::class, $e);
            $prop->setValue($conn, $oldDriver);
            $conn->rollback();
        }
    }
}
