<?php
/**
 * PHP Version 5.4
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         CakePHP(tm) v 3.0.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
namespace Cake\Test\TestCase\Database;

use Cake\Core\Configure;
use Cake\Database\Connection;
use Cake\Datasource\ConnectionManager;
use Cake\TestSuite\TestCase;

/**
 * Tests Connection class
 */
class ConnectionTest extends TestCase {

	public function setUp() {
		parent::setUp();
		$this->connection = ConnectionManager::get('test');
	}

	public function tearDown() {
		parent::tearDown();
		$this->connection->execute('DROP TABLE IF EXISTS things');
		$this->connection->useSavePoints(false);
		unset($this->connection);
	}

/**
 * Auxiliary method to build a mock for a driver so it can be injected into
 * the connection object
 *
 * @return \Cake\Database\Driver
 */
	public function getMockFormDriver() {
		$driver = $this->getMock('Cake\Database\Driver');
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
	public function testConnect() {
		$this->assertTrue($this->connection->connect());
		$this->assertTrue($this->connection->isConnected());
	}

/**
 * Tests creating a connection using an invalid driver throws an exception
 *
 * @expectedException \Cake\Database\Exception\MissingDriverException
 * @expectedExceptionMessage Database driver \Foo\InvalidDriver could not be found.
 * @return void
 */
	public function testMissingDriver() {
		$connection = new Connection(['driver' => '\Foo\InvalidDriver']);
	}

/**
 * Tests trying to use a disabled driver throws an exception
 *
 * @expectedException \Cake\Database\Exception\MissingExtensionException
 * @expectedExceptionMessage Database driver DriverMock cannot be used due to a missing PHP extension or unmet dependency
 * @return void
 */
	public function testDisabledDriver() {
		$mock = $this->getMock('\Cake\Database\Connection\Driver', ['enabled'], [], 'DriverMock');
		$connection = new Connection(['driver' => $mock]);
	}

/**
 * Tests that connecting with invalid credentials or database name throws an exception
 *
 * @expectedException \Cake\Database\Exception\MissingConnectionException
 * @return void
 **/
	public function testWrongCredentials() {
		$config = ConnectionManager::config('test');
		$this->skipIf(isset($config['dsn']), 'Datasource has dsn, skipping.');
		$connection = new Connection(['database' => '_probably_not_there_'] + ConnectionManager::config('test'));
		$connection->connect();
	}

/**
 * Tests disconnecting from database
 *
 * @return void
 **/
	public function testDisconnect() {
		$this->assertTrue($this->connection->connect());
		$this->assertTrue($this->connection->isConnected());
		$this->connection->disconnect();
		$this->assertFalse($this->connection->isConnected());
	}

/**
 * Tests creation of prepared statements
 *
 * @return void
 **/
	public function testPrepare() {
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
 **/
	public function testExecuteWithArguments() {
		$sql = 'SELECT 1 + ?';
		$statement = $this->connection->execute($sql, [1], array('integer'));
		$this->assertCount(1, $statement);
		$result = $statement->fetch();
		$this->assertEquals([2], $result);

		$sql = 'SELECT 1 + ? + ? AS total';
		$statement = $this->connection->execute($sql, [2, 3], array('integer', 'integer'));
		$this->assertCount(1, $statement);
		$result = $statement->fetch('assoc');
		$this->assertEquals(['total' => 6], $result);

		$sql = 'SELECT 1 + :one + :two AS total';
		$statement = $this->connection->execute($sql, ['one' => 2, 'two' => 3], array('one' => 'integer', 'two' => 'integer'));
		$this->assertCount(1, $statement);
		$result = $statement->fetch('assoc');
		$this->assertEquals(['total' => 6], $result);
	}

/**
 * Tests executing a query with params and associated types
 *
 * @return void
 **/
	public function testExecuteWithArgumentsAndTypes() {
		$sql = "SELECT ? = '2012-01-01'";
		$statement = $this->connection->execute($sql, [new \DateTime('2012-01-01')], ['date']);
		$result = $statement->fetch();
		$this->assertTrue((bool)$result[0]);

		$sql = "SELECT ? = '2012-01-01', ? = '2000-01-01 10:10:10', ? = 2";
		$params = [new \DateTime('2012-01-01 10:10:10'), '2000-01-01 10:10:10', 2.1];
		$statement = $this->connection->execute($sql, $params, ['date', 'string', 'integer']);
		$result = $statement->fetch();
		$this->assertEquals($result, array_filter($result));
	}

/**
 * Tests that passing a unknown value to a query throws an exception
 *
 * @expectedException \InvalidArgumentException
 * @return void
 **/
	public function testExecuteWithMissingType() {
		$sql = 'SELECT ?';
		$statement = $this->connection->execute($sql, [new \DateTime('2012-01-01')], ['bar']);
	}

/**
 * Tests executing a query with no params also works
 *
 * @return void
 **/
	public function testExecuteWithNoParams() {
		$sql = 'SELECT 1';
		$statement = $this->connection->execute($sql);
		$result = $statement->fetch();
		$this->assertCount(1, $result);
		$this->assertEquals([1], $result);
	}

/**
 * Tests it is possible to insert data into a table using matching types by key name
 *
 * @return void
 **/
	public function testInsertWithMatchingTypes() {
		$table = 'CREATE TEMPORARY TABLE things(id int, title varchar(20), body varchar(50))';
		$this->connection->execute($table);
		$data = ['id' => '1', 'title' => 'a title', 'body' => 'a body'];
		$result = $this->connection->insert(
			'things',
			$data,
			['id' => 'integer', 'title' => 'string', 'body' => 'string']
		);
		$this->assertInstanceOf('Cake\Database\StatementInterface', $result);
		$result = $this->connection->execute('SELECT * from things');
		$this->assertCount(1, $result);
		$row = $result->fetch('assoc');
		$this->assertEquals($data, $row);
	}

/**
 * Tests it is possible to insert data into a table using matching types by array position
 *
 * @return void
 **/
	public function testInsertWithPositionalTypes() {
		$table = 'CREATE TEMPORARY TABLE things(id int, title varchar(20), body varchar(50))';
		$this->connection->execute($table);
		$data = ['id' => '1', 'title' => 'a title', 'body' => 'a body'];
		$result = $this->connection->insert(
			'things',
			$data,
			['integer', 'string', 'string']
		);
		$this->assertInstanceOf('Cake\Database\StatementInterface', $result);
		$result = $this->connection->execute('SELECT * from things');
		$this->assertCount(1, $result);
		$row = $result->fetch('assoc');
		$this->assertEquals($data, $row);
	}

/**
 * Auxiliary function to insert a couple rows in a newly created table
 *
 * @return void
 **/
	protected function _insertTwoRecords() {
		$table = 'CREATE TEMPORARY TABLE things(id int, title varchar(20), body varchar(50))';
		$this->connection->execute($table);
		$data = ['id' => '1', 'title' => 'a title', 'body' => 'a body'];
		$result = $this->connection->insert(
			'things',
			$data,
			['id' => 'integer', 'title' => 'string', 'body' => 'string']
		);

		$result->bindValue(1, '2', 'integer');
		$result->bindValue(2, 'another title');
		$result->bindValue(3, 'another body');
		$result->execute();
	}

/**
 * Tests an statement class can be reused for multiple executions
 *
 * @return void
 **/
	public function testStatementReusing() {
		$this->_insertTwoRecords();

		$total = $this->connection->execute('SELECT COUNT(*) AS total FROM things');
		$total = $total->fetch('assoc');
		$this->assertEquals(2, $total['total']);

		$result = $this->connection->execute('SELECT title, body  FROM things');
		$row = $result->fetch('assoc');
		$this->assertEquals('a title', $row['title']);
		$this->assertEquals('a body', $row['body']);

		$row = $result->fetch('assoc');
		$this->assertEquals('another title', $row['title']);
		$this->assertEquals('another body', $row['body']);
	}

/**
 * Tests rows can be updated without specifying any conditions nor types
 *
 * @return void
 **/
	public function testUpdateWithoutConditionsNorTypes() {
		$this->_insertTwoRecords();
		$title = 'changed the title!';
		$body = 'changed the body!';
		$this->connection->update('things', ['title' => $title, 'body' => $body]);
		$result = $this->connection->execute('SELECT * FROM things WHERE title = ? AND body = ?', [$title, $body]);
		$this->assertCount(2, $result);
	}

/**
 * Tests it is possible to use key => value conditions for update
 *
 * @return void
 **/
	public function testUpdateWithConditionsNoTypes() {
		$this->_insertTwoRecords();
		$title = 'changed the title!';
		$body = 'changed the body!';
		$this->connection->update('things', ['title' => $title, 'body' => $body], ['id' => 2]);
		$result = $this->connection->execute('SELECT * FROM things WHERE title = ? AND body = ?', [$title, $body]);
		$this->assertCount(1, $result);
	}

/**
 * Tests it is possible to use key => value and string conditions for update
 *
 * @return void
 **/
	public function testUpdateWithConditionsCombinedNoTypes() {
		$this->_insertTwoRecords();
		$title = 'changed the title!';
		$body = 'changed the body!';
		$this->connection->update('things', ['title' => $title, 'body' => $body], ['id' => 2, 'body is not null']);
		$result = $this->connection->execute('SELECT * FROM things WHERE title = ? AND body = ?', [$title, $body]);
		$this->assertCount(1, $result);
	}

/**
 * Tests you can bind types to update values
 *
 * @return void
 **/
	public function testUpdateWithTypes() {
		$this->_insertTwoRecords();
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
	}

/**
 * Tests you can bind types to update values
 *
 * @return void
 **/
	public function testUpdateWithConditionsAndTypes() {
		$this->_insertTwoRecords();
		$title = 'changed the title!';
		$body = new \DateTime('2012-01-01');
		$values = compact('title', 'body');
		$this->connection->update('things', $values, ['id' => '1-string-parsed-as-int'], ['body' => 'date', 'id' => 'integer']);
		$result = $this->connection->execute('SELECT * FROM things WHERE title = :title AND body = :body', $values, ['body' => 'date']);
		$this->assertCount(1, $result);
		$row = $result->fetch('assoc');
		$this->assertEquals('2012-01-01', $row['body']);
	}

/**
 * Tests delete from table with no conditions
 *
 * @return void
 **/
	public function testDeleteNoConditions() {
		$this->_insertTwoRecords();
		$this->connection->delete('things');
		$result = $this->connection->execute('SELECT * FROM things');
		$this->assertCount(0, $result);
	}

/**
 * Tests delete from table with conditions
 * @return void
 **/
	public function testDeleteWithConditions() {
		$this->_insertTwoRecords();
		$this->connection->delete('things', ['id' => '1-rest-is-ommited'], ['id' => 'integer']);
		$result = $this->connection->execute('SELECT * FROM things');
		$this->assertCount(1, $result);

		$this->connection->delete('things', ['id' => '1-rest-is-ommited'], ['id' => 'integer']);
		$result = $this->connection->execute('SELECT * FROM things');
		$this->assertCount(1, $result);

		$this->connection->delete('things', ['id' => '2-rest-is-ommited'], ['id' => 'integer']);
		$result = $this->connection->execute('SELECT * FROM things');
		$this->assertCount(0, $result);
	}

/**
 * Tests that it is possible to use simple database transactions
 *
 * @return void
 **/
	public function testSimpleTransactions() {
		$this->_insertTwoRecords();
		$this->connection->begin();
		$this->connection->delete('things', ['id' => 1]);
		$this->connection->rollback();
		$result = $this->connection->execute('SELECT * FROM things');
		$this->assertCount(2, $result);

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
 **/
	public function testVirtualNestedTrasanction() {
		$this->_insertTwoRecords();

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
 **/
	public function testVirtualNestedTrasanction2() {
		$this->_insertTwoRecords();

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
 **/

	public function testVirtualNestedTrasanction3() {
		$this->_insertTwoRecords();

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
 **/
	public function testSavePoints() {
		$this->skipIf(!$this->connection->useSavePoints(true));
		$this->_insertTwoRecords();

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
 **/

	public function testSavePoints2() {
		$this->skipIf(!$this->connection->useSavePoints(true));
		$this->_insertTwoRecords();

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
 * Tests connection can quote values to be safely used in query strings
 *
 * @return void
 **/
	public function testQuote() {
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
	public function testQuoteIdentifier() {
		$driver = $this->getMock('Cake\Database\Driver\Sqlite', ['enabled']);
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
	public function testLoggerDefault() {
		$logger = $this->connection->logger();
		$this->assertInstanceOf('\Cake\Database\Log\QueryLogger', $logger);
		$this->assertSame($logger, $this->connection->logger());
	}

/**
 * Tests that a custom logger object can be set
 *
 * @return void
 */
	public function testSetLogger() {
		$logger = new \Cake\Database\Log\QueryLogger;
		$this->connection->logger($logger);
		$this->assertSame($logger, $this->connection->logger());
	}

/**
 * Tests that statements are decorated with a logger when logQueries is set to true
 *
 * @return void
 */
	public function testLoggerDecorator() {
		$logger = new \Cake\Database\Log\QueryLogger;
		$this->connection->logQueries(true);
		$this->connection->logger($logger);
		$st = $this->connection->prepare('SELECT 1');
		$this->assertInstanceOf('\Cake\Database\Log\LoggingStatement', $st);
		$this->assertSame($logger, $st->logger());

		$this->connection->logQueries(false);
		$st = $this->connection->prepare('SELECT 1');
		$this->assertNotInstanceOf('\Cake\Database\Log\LoggingStatement', $st);
	}

/**
 * Tests that log() function logs to the configured query logger
 *
 * @return void
 */
	public function testLogFunction() {
		$logger = $this->getMock('\Cake\Database\Log\QueryLogger');
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
	public function testLogBeginRollbackTransaction() {
		$connection = $this->getMock(
			'\Cake\Database\Connection',
			['connect'],
			[['log' => true]]
		);
		$driver = $this->getMockFormDriver();
		$connection->driver($driver);

		$logger = $this->getMock('\Cake\Database\Log\QueryLogger');
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
	public function testLogCommitTransaction() {
		$driver = $this->getMockFormDriver();
		$connection = $this->getMock(
			'\Cake\Database\Connection',
			['connect'],
			[['driver' => $driver]]
		);

		$logger = $this->getMock('\Cake\Database\Log\QueryLogger');
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
	public function testTransactionalSuccess() {
		$driver = $this->getMockFormDriver();
		$connection = $this->getMock(
			'\Cake\Database\Connection',
			['connect', 'commit', 'begin'],
			[['driver' => $driver]]
		);
		$connection->expects($this->at(0))->method('begin');
		$connection->expects($this->at(1))->method('commit');
		$result = $connection->transactional(function($conn) use ($connection) {
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
	public function testTransactionalFail() {
		$driver = $this->getMockFormDriver();
		$connection = $this->getMock(
			'\Cake\Database\Connection',
			['connect', 'commit', 'begin', 'rollback'],
			[['driver' => $driver]]
		);
		$connection->expects($this->at(0))->method('begin');
		$connection->expects($this->at(1))->method('rollback');
		$connection->expects($this->never())->method('commit');
		$result = $connection->transactional(function($conn) use ($connection) {
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
	public function testTransactionalWithException() {
		$driver = $this->getMockFormDriver();
		$connection = $this->getMock(
			'\Cake\Database\Connection',
			['connect', 'commit', 'begin', 'rollback'],
			[['driver' => $driver]]
		);
		$connection->expects($this->at(0))->method('begin');
		$connection->expects($this->at(1))->method('rollback');
		$connection->expects($this->never())->method('commit');
		$connection->transactional(function($conn) use ($connection) {
			$this->assertSame($connection, $conn);
			throw new \InvalidArgumentException;
		});
	}

}
