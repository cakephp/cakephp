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

}
