<?php
/**
 * 
 * PHP Version 5.x
 *
 * CakePHP(tm) Tests <http://book.cakephp.org/2.0/en/development/testing.html>
 * Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The Open Group Test Suite License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/2.0/en/development/testing.html CakePHP(tm) Tests
 * @package       Cake.Test.Case.Model.Datasource.Database
 * @since         CakePHP(tm) v 3.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
namespace Cake\Test\TestCase\Model\Datasource\Database;

use Cake\Model\Datasource\Database\Connection,
	Cake\Core\Configure;

/**
 * Tests Connection class
 *
 **/
class ConnectionTest extends \Cake\TestSuite\TestCase {


	public function setUp() {
		$this->connection = new Connection(Configure::read('Connections.test'));
	}

	public function tearDown() {
		$this->connection->execute('DROP TABLE IF EXISTS things');
	}

/**
 * Tests connecting to database
 *
 * @return void
 **/
	public function testConnect() {
		$this->assertFalse($this->connection->isConnected());
		$this->assertTrue($this->connection->connect());
		$this->assertTrue($this->connection->isConnected());
	}

/**
 * Tests creating a connection using an invalid driver throws an exception
 *
 * @expectedException Cake\Model\Datasource\Database\Exception\MissingDriverException
 * @expectedExceptionMessage Database driver \Foo\InvalidDriver could not be found. 
 * @return void
 **/
	public function testMissingDriver() {
		$connection = new Connection(array('datasource' =>  '\Foo\InvalidDriver'));
	}

/**
 * Tests trying to use a disabled driver throws an exception
 * @expectedException Cake\Model\Datasource\Database\Exception\MissingExtensionException
 * @expectedExceptionMessage Database driver DriverMock cannot be used due to a missing PHP extension or unmet dependency
 * @return void
 **/
	public function testDisabledDriver() {
		$mock = $this->getMock('\Cake\Model\Datasource\Database\Connection\Driver', array('enabled'), array(), 'DriverMock');
		$connection = new Connection(array('datasource' => get_class($mock)));
	}

/**
 * Tests that connecting with invalid credentials or database name throws an exception
 *
 * @expectedException \Cake\Model\Datasource\Database\Exception\MissingConnectionException
 * @return void
 **/
	public function testWrongCredentials() {
		$connection = new Connection(array('database' => 'foobar') + Configure::read('Connections.test'));
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
		$this->assertInstanceOf('Cake\Model\Datasource\Database\Statement', $result);
		$this->assertEquals($sql, $result->queryString);
	}

/**
 * Tests executing a simple query using bound values
 *
 * @return void
 **/
	public function testExecuteWithArguments() {
		$sql = 'SELECT 1 + ?';
		$statement = $this->connection->execute($sql, array(1));
		$this->assertCount(1, $statement);
		$result = $statement->fetch();
		$this->assertEquals(array(2), $result);

		$sql = 'SELECT 1 + ? + ? AS total';
		$statement = $this->connection->execute($sql, array(2, 3));
		$this->assertCount(1, $statement);
		$result = $statement->fetch('assoc');
		$this->assertEquals(array('total' => 6), $result);

		$sql = 'SELECT 1 + :one + :two AS total';
		$statement = $this->connection->execute($sql, array('one' => 2, 'two' => 3));
		$this->assertCount(1, $statement);
		$result = $statement->fetch('assoc');
		$this->assertEquals(array('total' => 6), $result);
	}

/**
 * Tests executing a query with params and associated types
 *
 * @return void
 **/
	public function testExecuteWithArgumentsAndTypes() {
		$sql = 'SELECT ?';
		$statement = $this->connection->execute($sql, array(new \DateTime('2012-01-01')), array('date'));
		$result = $statement->fetch();
		$this->assertEquals('2012-01-01', $result[0]);

		$sql = 'SELECT ?, ?, ?';
		$params = array(new \DateTime('2012-01-01 10:10:10'), '2000-01-01 10:10:10', 1.1);
		$statement = $this->connection->execute($sql, $params, array('date', 'string', 'float'));
		$result = $statement->fetch();
		$this->assertEquals(array('2012-01-01', '2000-01-01 10:10:10', 1.1), $result);
	}

/**
 * Tests that passing a unknown value to a query throws an exception
 *
 * @expectedException \InvalidArgumentException
 * @return void
 **/
	public function testExecuteWithMissingType() {
		$sql = 'SELECT ?';
		$statement = $this->connection->execute($sql, array(new \DateTime('2012-01-01')), array('bar'));
	}

/**
 * Tests executing a qury with no params also works
 *
 * @return void
 **/
	public function testExecuteWithNoParams() {
		$sql = 'SELECT 1';
		$statement = $this->connection->execute($sql);
		$result = $statement->fetch();
		$this->assertCount(1, $result);
		$this->assertEquals(array(1), $result);
	}

/**
 * Tests it is possible to insert data into a table using matching types by key name
 *
 * @return void
 **/
	public function testInsertWithMatchingTypes() {
		$table = 'CREATE TEMPORARY TABLE things(id int, title varchar(20), body varchar(50))';
		$this->connection->execute($table);
		$data = array('id' => '1', 'title' =>  'a title', 'body' =>  'a body');
		$result = $this->connection->insert(
			'things',
			$data,
			array('id' => 'integer', 'title' => 'string', 'body' => 'string')
		);
		$this->assertInstanceOf('Cake\Model\Datasource\Database\Statement', $result);
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
		$data = array('id' => '1', 'title' =>  'a title', 'body' =>  'a body');
		$result = $this->connection->insert(
			'things',
			$data,
			array('integer', 'string', 'string')
		);
		$this->assertInstanceOf('Cake\Model\Datasource\Database\Statement', $result);
		$result = $this->connection->execute('SELECT * from things');
		$this->assertCount(1, $result);
		$row = $result->fetch('assoc');
		$this->assertEquals($data, $row);
	}

/**
 * Auxiliary function to insert a couple rows in a newly creted table
 *
 * @return void
 **/
	protected function _insertTwoRecords() {
		$table = 'CREATE TEMPORARY TABLE things(id int, title varchar(20), body varchar(50))';
		$this->connection->execute($table);
		$data = array('id' => '1', 'title' =>  'a title', 'body' =>  'a body');
		$result = $this->connection->insert(
			'things',
			$data,
			array('id' => 'integer', 'title' => 'string', 'body' => 'string')
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

		$result =  $this->connection->execute('SELECT title, body  FROM things');
		$row = $result->fetch('assoc');
		$this->assertEquals('a title', $row['title']);
		$this->assertEquals('a body', $row['body']);

		$row = $result->fetch('assoc');
		$this->assertEquals('another title', $row['title']);
		$this->assertEquals('another body', $row['body']);
	}

/**
 * Tests rows can be updated without specifying any coditions nor types
 *
 * @return void
 **/
	public function testUpdateWithoutConditionsNorTypes() {
		$this->_insertTwoRecords();
		$title = 'changed the title!';
		$body = 'changed the body!';
		$this->connection->update('things', array('title' => $title, 'body' => $body));
		$result = $this->connection->execute('SELECT * FROM things WHERE title = ? AND body = ?', array($title, $body));
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
		$this->connection->update('things', array('title' => $title, 'body' => $body), array('id' => 2));
		$result = $this->connection->execute('SELECT * FROM things WHERE title = ? AND body = ?', array($title, $body));
		$this->assertCount(1, $result);
	}

/**
 * Tests it is possible to use key => value and stirng conditions for update
 *
 * @return void
 **/
	public function testUpdateWithConditionsCombinedNoTypes() {
		$this->_insertTwoRecords();
		$title = 'changed the title!';
		$body = 'changed the body!';
		$this->connection->update('things', array('title' => $title, 'body' => $body), array('id' => 2, 'body is not null'));
		$result = $this->connection->execute('SELECT * FROM things WHERE title = ? AND body = ?', array($title, $body));
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
		$this->connection->update('things', $values, array(), array('body' =>  'date'));
		$result = $this->connection->execute('SELECT * FROM things WHERE title = :title AND body = :body', $values, array('body' => 'date'));
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
		$this->connection->update('things', $values, array('id' => '1-string-parsed-as-int'), array('body' =>  'date', 'id' => 'integer'));
		$result = $this->connection->execute('SELECT * FROM things WHERE title = :title AND body = :body', $values, array('body' => 'date'));
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
		$this->connection->delete('things', array('id' => '1-rest-is-ommited'), array('id' => 'integer'));
		$result = $this->connection->execute('SELECT * FROM things');
		$this->assertCount(1, $result);

		$this->connection->delete('things', array('id' => '1-rest-is-ommited'), array('id' => 'integer'));
		$result = $this->connection->execute('SELECT * FROM things');
		$this->assertCount(1, $result);

		$this->connection->delete('things', array('id' => '2-rest-is-ommited'), array('id' => 'integer'));
		$result = $this->connection->execute('SELECT * FROM things');
		$this->assertCount(0, $result);
	}

}
