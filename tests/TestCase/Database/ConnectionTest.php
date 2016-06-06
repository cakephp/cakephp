<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         3.0.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Database;

use Cake\Core\Configure;
use Cake\Database\Connection;
use Cake\Datasource\ConnectionManager;
use Cake\TestSuite\TestCase;

/**
 * Tests Connection class
 */
class ConnectionTest extends TestCase
{

    public $fixtures = ['core.things'];

    public function setUp()
    {
        parent::setUp();
        $this->connection = ConnectionManager::get('test');
        Configure::write('App.namespace', 'TestApp');
    }

    public function tearDown()
    {
        $this->connection->useSavePoints(false);
        unset($this->connection);
        parent::tearDown();
    }

    /**
     * Auxiliary method to build a mock for a driver so it can be injected into
     * the connection object
     *
     * @return \Cake\Database\Driver
     */
    public function getMockFormDriver()
    {
        $driver = $this->getMockBuilder('Cake\Database\Driver')->getMock();
        $driver->expects($this->once())
            ->method('enabled')
            ->will($this->returnValue(true));
        return $driver;
    }

    /**
     * Tests connecting to database
     *
     * @return void
     */
    public function testConnect()
    {
        $this->assertTrue($this->connection->connect());
        $this->assertTrue($this->connection->isConnected());
    }

    /**
     * Tests creating a connection using no driver throws an exception
     *
     * @expectedException \Cake\Database\Exception\MissingDriverException
     * @expectedExceptionMessage Database driver  could not be found.
     * @return void
     */
    public function testNoDriver()
    {
        $connection = new Connection([]);
    }

    /**
     * Tests creating a connection using an invalid driver throws an exception
     *
     * @expectedException \Cake\Database\Exception\MissingDriverException
     * @expectedExceptionMessage Database driver  could not be found.
     * @return void
     */
    public function testEmptyDriver()
    {
        $connection = new Connection(['driver' => false]);
    }

    /**
     * Tests creating a connection using an invalid driver throws an exception
     *
     * @expectedException \Cake\Database\Exception\MissingDriverException
     * @expectedExceptionMessage Database driver \Foo\InvalidDriver could not be found.
     * @return void
     */
    public function testMissingDriver()
    {
        $connection = new Connection(['driver' => '\Foo\InvalidDriver']);
    }

    /**
     * Tests trying to use a disabled driver throws an exception
     *
     * @expectedException \Cake\Database\Exception\MissingExtensionException
     * @expectedExceptionMessage Database driver DriverMock cannot be used due to a missing PHP extension or unmet dependency
     * @return void
     */
    public function testDisabledDriver()
    {
        $mock = $this->getMockBuilder('\Cake\Database\Connection\Driver')
            ->setMethods(['enabled'])
            ->setMockClassName('DriverMock')
            ->getMock();
        $connection = new Connection(['driver' => $mock]);
    }

    /**
     * Tests that the `driver` option supports the short classname/plugin syntax.
     *
     * @return void
     */
    public function testDriverOptionClassNameSupport()
    {
        $connection = new Connection(['driver' => 'TestDriver']);
        $this->assertInstanceOf('\TestApp\Database\Driver\TestDriver', $connection->driver());

        $connection = new Connection(['driver' => 'TestPlugin.TestDriver']);
        $this->assertInstanceOf('\TestPlugin\Database\Driver\TestDriver', $connection->driver());

        list(, $name) = namespaceSplit(get_class($this->connection->driver()));
        $connection = new Connection(['driver' => $name]);
        $this->assertInstanceOf(get_class($this->connection->driver()), $connection->driver());
    }

    /**
     * Tests that connecting with invalid credentials or database name throws an exception
     *
     * @expectedException \Cake\Database\Exception\MissingConnectionException
     * @return void
     */
    public function testWrongCredentials()
    {
        $config = ConnectionManager::config('test');
        $this->skipIf(isset($config['url']), 'Datasource has dsn, skipping.');
        $connection = new Connection(['database' => '/dev/nonexistent'] + ConnectionManager::config('test'));
        $connection->connect();
    }

    /**
     * Tests creation of prepared statements
     *
     * @return void
     */
    public function testPrepare()
    {
        $sql = 'SELECT 1 + 1';
        $result = $this->connection->prepare($sql);
        $this->assertInstanceOf('Cake\Database\StatementInterface', $result);
        $this->assertEquals($sql, $result->queryString);

        $query = $this->connection->newQuery()->select('1 + 1');
        $result = $this->connection->prepare($query);
        $this->assertInstanceOf('Cake\Database\StatementInterface', $result);
        $sql = '#SELECT [`"\[]?1 \+ 1[`"\]]?#';
        $this->assertRegExp($sql, $result->queryString);
    }

    /**
     * Tests executing a simple query using bound values
     *
     * @return void
     */
    public function testExecuteWithArguments()
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
     *
     * @return void
     */
    public function testExecuteWithArgumentsAndTypes()
    {
        $sql = "SELECT '2012-01-01' = ?";
        $statement = $this->connection->execute($sql, [new \DateTime('2012-01-01')], ['date']);
        $result = $statement->fetch();
        $statement->closeCursor();
        $this->assertTrue((bool)$result[0]);
    }

    /**
     * Tests that passing a unknown value to a query throws an exception
     *
     * @expectedException \InvalidArgumentException
     * @return void
     */
    public function testExecuteWithMissingType()
    {
        $sql = 'SELECT ?';
        $statement = $this->connection->execute($sql, [new \DateTime('2012-01-01')], ['bar']);
    }

    /**
     * Tests executing a query with no params also works
     *
     * @return void
     */
    public function testExecuteWithNoParams()
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
     *
     * @return void
     */
    public function testInsertWithMatchingTypes()
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
     *
     * @return void
     */
    public function testInsertWithPositionalTypes()
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
     *
     * @return void
     */
    public function testStatementReusing()
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
        $this->assertEquals('a title', $row['title']);
        $this->assertEquals('a body', $row['body']);

        $row = $result->fetch('assoc');
        $result->closeCursor();
        $this->assertEquals('another title', $row['title']);
        $this->assertEquals('another body', $row['body']);

        $result->execute();
        $row = $result->fetch('assoc');
        $result->closeCursor();
        $this->assertEquals('a title', $row['title']);
    }

    /**
     * Tests that it is possible to pass PDO constants to the underlying statement
     * object for using alternate fetch types
     *
     * @return void
     */
    public function testStatementFetchObject()
    {
        $result = $this->connection->execute('SELECT title, body  FROM things');
        $row = $result->fetch(\PDO::FETCH_OBJ);
        $this->assertEquals('a title', $row->title);
        $this->assertEquals('a body', $row->body);
    }

    /**
     * Tests rows can be updated without specifying any conditions nor types
     *
     * @return void
     */
    public function testUpdateWithoutConditionsNorTypes()
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
     *
     * @return void
     */
    public function testUpdateWithConditionsNoTypes()
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
     *
     * @return void
     */
    public function testUpdateWithConditionsCombinedNoTypes()
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
     *
     * @return void
     */
    public function testUpdateWithTypes()
    {
        $title = 'changed the title!';
        $body = new \DateTime('2012-01-01');
        $values = compact('title', 'body');
        $this->connection->update('things', $values, [], ['body' => 'date']);
        $result = $this->connection->execute('SELECT * FROM things WHERE title = :title AND body = :body', $values, ['body' => 'date']);
        $this->assertCount(2, $result);
        $row = $result->fetch('assoc');
        $this->assertEquals('2012-01-01', $row['body']);
        $row = $result->fetch('assoc');
        $this->assertEquals('2012-01-01', $row['body']);
        $result->closeCursor();
    }

    /**
     * Tests you can bind types to update values
     *
     * @return void
     */
    public function testUpdateWithConditionsAndTypes()
    {
        $title = 'changed the title!';
        $body = new \DateTime('2012-01-01');
        $values = compact('title', 'body');
        $this->connection->update('things', $values, ['id' => '1-string-parsed-as-int'], ['body' => 'date', 'id' => 'integer']);
        $result = $this->connection->execute('SELECT * FROM things WHERE title = :title AND body = :body', $values, ['body' => 'date']);
        $this->assertCount(1, $result);
        $row = $result->fetch('assoc');
        $this->assertEquals('2012-01-01', $row['body']);
        $result->closeCursor();
    }

    /**
     * Tests delete from table with no conditions
     *
     * @return void
     */
    public function testDeleteNoConditions()
    {
        $this->connection->delete('things');
        $result = $this->connection->execute('SELECT * FROM things');
        $this->assertCount(0, $result);
        $result->closeCursor();
    }

    /**
     * Tests delete from table with conditions
     * @return void
     */
    public function testDeleteWithConditions()
    {
        $this->connection->delete('things', ['id' => '1-rest-is-ommited'], ['id' => 'integer']);
        $result = $this->connection->execute('SELECT * FROM things');
        $this->assertCount(1, $result);
        $result->closeCursor();

        $this->connection->delete('things', ['id' => '1-rest-is-ommited'], ['id' => 'integer']);
        $result = $this->connection->execute('SELECT * FROM things');
        $this->assertCount(1, $result);
        $result->closeCursor();

        $this->connection->delete('things', ['id' => '2-rest-is-ommited'], ['id' => 'integer']);
        $result = $this->connection->execute('SELECT * FROM things');
        $this->assertCount(0, $result);
        $result->closeCursor();
    }

    /**
     * Tests that it is possible to use simple database transactions
     *
     * @return void
     */
    public function testSimpleTransactions()
    {
        $this->connection->begin();
        $this->connection->delete('things', ['id' => 1]);
        $this->connection->rollback();
        $result = $this->connection->execute('SELECT * FROM things');
        $this->assertCount(2, $result);
        $result->closeCursor();

        $this->connection->begin();
        $this->connection->delete('things', ['id' => 1]);
        $this->connection->commit();
        $result = $this->connection->execute('SELECT * FROM things');
        $this->assertCount(1, $result);
    }

    /**
     * Tests that it is possible to use virtualized nested transaction
     * with early rollback algorithm
     *
     * @return void
     */
    public function testVirtualNestedTrasanction()
    {
        //starting 3 virtual transaction
        $this->connection->begin();
        $this->connection->begin();
        $this->connection->begin();

        $this->connection->delete('things', ['id' => 1]);
        $result = $this->connection->execute('SELECT * FROM things');
        $this->assertCount(1, $result);

        $this->connection->commit();
        $this->connection->rollback();

        $result = $this->connection->execute('SELECT * FROM things');
        $this->assertCount(2, $result);
    }

    /**
     * Tests that it is possible to use virtualized nested transaction
     * with early rollback algorithm
     *
     * @return void
     */
    public function testVirtualNestedTrasanction2()
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
     *
     * @return void
     */

    public function testVirtualNestedTrasanction3()
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
     *
     * @return void
     */
    public function testSavePoints()
    {
        $this->skipIf(!$this->connection->useSavePoints(true));

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
     *
     * @return void
     */

    public function testSavePoints2()
    {
        $this->skipIf(!$this->connection->useSavePoints(true));
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
     *
     * @return void
     */
    public function testInTransaction()
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
     *
     * @return void
     */
    public function testInTransactionWithSavePoints()
    {
        $this->skipIf(
            $this->connection->driver() instanceof \Cake\Database\Driver\Sqlserver,
            'SQLServer fails when this test is included.'
        );
        $this->skipIf(!$this->connection->useSavePoints(true));
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
     *
     * @return void
     */
    public function testQuote()
    {
        $this->skipIf(!$this->connection->supportsQuoting());
        $expected = "'2012-01-01'";
        $result = $this->connection->quote(new \DateTime('2012-01-01'), 'date');
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
     *
     * @return void
     */
    public function testQuoteIdentifier()
    {
        $driver = $this->getMockBuilder('Cake\Database\Driver\Sqlite')
            ->setMethods(['enabled'])
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

        $result = $connection->quoteIdentifier('Model.name as y');
        $expected = '"Model"."name" AS "y"';
        $this->assertEquals($expected, $result);
    }

    /**
     * Tests default return vale for logger() function
     *
     * @return void
     */
    public function testLoggerDefault()
    {
        $logger = $this->connection->logger();
        $this->assertInstanceOf('Cake\Database\Log\QueryLogger', $logger);
        $this->assertSame($logger, $this->connection->logger());
    }

    /**
     * Tests that a custom logger object can be set
     *
     * @return void
     */
    public function testSetLogger()
    {
        $logger = new \Cake\Database\Log\QueryLogger;
        $this->connection->logger($logger);
        $this->assertSame($logger, $this->connection->logger());
    }

    /**
     * Tests that statements are decorated with a logger when logQueries is set to true
     *
     * @return void
     */
    public function testLoggerDecorator()
    {
        $logger = new \Cake\Database\Log\QueryLogger;
        $this->connection->logQueries(true);
        $this->connection->logger($logger);
        $st = $this->connection->prepare('SELECT 1');
        $this->assertInstanceOf('Cake\Database\Log\LoggingStatement', $st);
        $this->assertSame($logger, $st->logger());

        $this->connection->logQueries(false);
        $st = $this->connection->prepare('SELECT 1');
        $this->assertNotInstanceOf('\Cake\Database\Log\LoggingStatement', $st);
    }

    /**
     * test logQueries method
     *
     * @return void
     */
    public function testLogQueries()
    {
        $this->connection->logQueries(true);
        $this->assertTrue($this->connection->logQueries());

        $this->connection->logQueries(false);
        $this->assertFalse($this->connection->logQueries());
    }

    /**
     * Tests that log() function logs to the configured query logger
     *
     * @return void
     */
    public function testLogFunction()
    {
        $logger = $this->getMockBuilder('\Cake\Database\Log\QueryLogger')->getMock();
        $this->connection->logger($logger);
        $logger->expects($this->once())->method('log')
            ->with($this->logicalAnd(
                $this->isInstanceOf('\Cake\Database\Log\LoggedQuery'),
                $this->attributeEqualTo('query', 'SELECT 1')
            ));
        $this->connection->log('SELECT 1');
    }

    /**
     * Tests that begin and rollback are also logged
     *
     * @return void
     */
    public function testLogBeginRollbackTransaction()
    {
        $connection = $this
            ->getMockBuilder('\Cake\Database\Connection')
            ->setMethods(['connect'])
            ->disableOriginalConstructor()
            ->getMock();
        $connection->logQueries(true);

        $driver = $this->getMockFormDriver();
        $connection->driver($driver);

        $logger = $this->getMockBuilder('\Cake\Database\Log\QueryLogger')->getMock();
        $connection->logger($logger);
        $logger->expects($this->at(0))->method('log')
            ->with($this->logicalAnd(
                $this->isInstanceOf('\Cake\Database\Log\LoggedQuery'),
                $this->attributeEqualTo('query', 'BEGIN')
            ));
        $logger->expects($this->at(1))->method('log')
            ->with($this->logicalAnd(
                $this->isInstanceOf('\Cake\Database\Log\LoggedQuery'),
                $this->attributeEqualTo('query', 'ROLLBACK')
            ));

        $connection->begin();
        $connection->begin(); //This one will not be logged
        $connection->rollback();
    }

    /**
     * Tests that commits are logged
     *
     * @return void
     */
    public function testLogCommitTransaction()
    {
        $driver = $this->getMockFormDriver();
        $connection = $this->getMockBuilder('\Cake\Database\Connection')
            ->setMethods(['connect'])
            ->setConstructorArgs([['driver' => $driver]])
            ->getMock();

        $logger = $this->getMockBuilder('\Cake\Database\Log\QueryLogger')->getMock();
        $connection->logger($logger);

        $logger->expects($this->at(1))->method('log')
            ->with($this->logicalAnd(
                $this->isInstanceOf('\Cake\Database\Log\LoggedQuery'),
                $this->attributeEqualTo('query', 'COMMIT')
            ));
        $connection->logQueries(true);
        $connection->begin();
        $connection->commit();
    }

    /**
     * Tests that the transactional method will start and commit a transaction
     * around some arbitrary function passed as argument
     *
     * @return void
     */
    public function testTransactionalSuccess()
    {
        $driver = $this->getMockFormDriver();
        $connection = $this->getMockBuilder('\Cake\Database\Connection')
            ->setMethods(['connect', 'commit', 'begin'])
            ->setConstructorArgs([['driver' => $driver]])
            ->getMock();
        $connection->expects($this->at(0))->method('begin');
        $connection->expects($this->at(1))->method('commit');
        $result = $connection->transactional(function ($conn) use ($connection) {
            $this->assertSame($connection, $conn);
            return 'thing';
        });
        $this->assertEquals('thing', $result);
    }

    /**
     * Tests that the transactional method will rollback the transaction if false
     * is returned from the callback
     *
     * @return void
     */
    public function testTransactionalFail()
    {
        $driver = $this->getMockFormDriver();
        $connection = $this->getMockBuilder('\Cake\Database\Connection')
            ->setMethods(['connect', 'commit', 'begin', 'rollback'])
            ->setConstructorArgs([['driver' => $driver]])
            ->getMock();
        $connection->expects($this->at(0))->method('begin');
        $connection->expects($this->at(1))->method('rollback');
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
     * @expectedException \InvalidArgumentException
     * @return void
     * @throws \InvalidArgumentException
     */
    public function testTransactionalWithException()
    {
        $driver = $this->getMockFormDriver();
        $connection = $this->getMockBuilder('\Cake\Database\Connection')
            ->setMethods(['connect', 'commit', 'begin', 'rollback'])
            ->setConstructorArgs([['driver' => $driver]])
            ->getMock();
        $connection->expects($this->at(0))->method('begin');
        $connection->expects($this->at(1))->method('rollback');
        $connection->expects($this->never())->method('commit');
        $connection->transactional(function ($conn) use ($connection) {
            $this->assertSame($connection, $conn);
            throw new \InvalidArgumentException;
        });
    }

    /**
     * Tests it is possible to set a schema collection object
     *
     * @return void
     */
    public function testSchemaCollection()
    {
        $driver = $this->getMockFormDriver();
        $connection = $this->getMockBuilder('\Cake\Database\Connection')
            ->setMethods(['connect'])
            ->setConstructorArgs([['driver' => $driver]])
            ->getMock();

        $schema = $connection->schemaCollection();
        $this->assertInstanceOf('Cake\Database\Schema\Collection', $schema);

        $schema = $this->getMockBuilder('Cake\Database\Schema\Collection')
            ->setConstructorArgs([$connection])
            ->getMock();
        $connection->schemaCollection($schema);
        $this->assertSame($schema, $connection->schemaCollection());
    }
}
