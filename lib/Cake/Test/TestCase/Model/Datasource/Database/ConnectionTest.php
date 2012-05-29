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
	}

}
