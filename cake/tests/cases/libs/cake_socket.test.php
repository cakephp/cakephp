<?php
/**
 * SocketTest file
 *
 * PHP 5
 *
 * CakePHP(tm) Tests <http://book.cakephp.org/view/1196/Testing>
 * Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/view/1196/Testing CakePHP(tm) Tests
 * @package       cake.tests.cases.libs
 * @since         CakePHP(tm) v 1.2.0.4206
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
App::import('Core', 'CakeSocket');

/**
 * SocketTest class
 *
 * @package       cake.tests.cases.libs
 */
class CakeSocketTest extends CakeTestCase {

/**
 * setUp method
 *
 * @access public
 * @return void
 */
	function setUp() {
		parent::setUp();
		$this->Socket = new CakeSocket(array('timeout' => 1));
	}

/**
 * tearDown method
 *
 * @access public
 * @return void
 */
	function tearDown() {
		parent::tearDown();
		unset($this->Socket);
	}

/**
 * testConstruct method
 *
 * @access public
 * @return void
 */
	function testConstruct() {
		$this->Socket = new CakeSocket();
		$config = $this->Socket->config;
		$this->assertIdentical($config, array(
			'persistent'	=> false,
			'host'			=> 'localhost',
			'protocol'		=> getprotobyname('tcp'),
			'port'			=> 80,
			'timeout'		=> 30
		));

		$this->Socket->reset();
		$this->Socket->__construct(array('host' => 'foo-bar'));
		$config['host'] = 'foo-bar';
		$this->assertIdentical($this->Socket->config, $config);

		$this->Socket = new CakeSocket(array('host' => 'www.cakephp.org', 'port' => 23, 'protocol' => 'udp'));
		$config = $this->Socket->config;

		$config['host'] = 'www.cakephp.org';
		$config['port'] = 23;
		$config['protocol'] = 17;

		$this->assertIdentical($this->Socket->config, $config);
	}

/**
 * testSocketConnection method
 *
 * @access public
 * @return void
 */
	function testSocketConnection() {
		$this->assertFalse($this->Socket->connected);
		$this->Socket->disconnect();
		$this->assertFalse($this->Socket->connected);
		$this->Socket->connect();
		$this->assertTrue($this->Socket->connected);
		$this->Socket->connect();
		$this->assertTrue($this->Socket->connected);

		$this->Socket->disconnect();
		$config = array('persistent' => true);
		$this->Socket = new CakeSocket($config);
		$this->Socket->connect();
		$this->assertTrue($this->Socket->connected);
	}

/**
 * data provider function for testInvalidConnection
 *
 * @return array
 */
	public static function invalidConnections() {
		return array(
			array(array('host' => 'invalid.host', 'timeout' => 1)),
			array(array('host' => '127.0.0.1', 'port' => '70000', 'timeout' => 1))
		);
	}

/**
 * testInvalidConnection method
 *
 * @dataProvider invalidConnections
 * @expectedException SocketException
 * return void
 */
	public function testInvalidConnection($data) {
		$this->Socket->config = array_merge($this->Socket->config, $data);
		$this->Socket->connect();
	}

/**
 * testSocketHost method
 *
 * @access public
 * @return void
 */
	function testSocketHost() {
		$this->Socket = new CakeSocket();
		$this->Socket->connect();
		$this->assertEqual($this->Socket->address(), '127.0.0.1');
		$this->assertEqual(gethostbyaddr('127.0.0.1'), $this->Socket->host());
		$this->assertEqual($this->Socket->lastError(), null);
		$this->assertTrue(in_array('127.0.0.1', $this->Socket->addresses()));

		$this->Socket = new CakeSocket(array('host' => '127.0.0.1'));
		$this->Socket->connect();
		$this->assertEqual($this->Socket->address(), '127.0.0.1');
		$this->assertEqual(gethostbyaddr('127.0.0.1'), $this->Socket->host());
		$this->assertEqual($this->Socket->lastError(), null);
		$this->assertTrue(in_array('127.0.0.1', $this->Socket->addresses()));
	}

/**
 * testSocketWriting method
 *
 * @access public
 * @return void
 */
	function testSocketWriting() {
		$request = "GET / HTTP/1.1\r\nConnection: close\r\n\r\n";
		$this->assertTrue((bool)$this->Socket->write($request));
	}

/**
 * testSocketReading method
 *
 * @access public
 * @return void
 */
	function testSocketReading() {
		$this->Socket = new CakeSocket(array('timeout' => 5));
		$this->Socket->connect();
		$this->assertEqual($this->Socket->read(26), null);

		$config = array('host' => '127.0.0.1', 'timeout' => 0.5);
		$this->Socket = new CakeSocket($config);
		$this->assertTrue($this->Socket->connect());
		$this->assertFalse($this->Socket->read(1024 * 1024));
		$this->assertEqual($this->Socket->lastError(), '2: ' . __('Connection timed out'));

		$config = array('host' => 'cakephp.org', 'port' => 80, 'timeout' => 20);
		$this->Socket = new CakeSocket($config);
		$this->assertTrue($this->Socket->connect());
		$this->assertEqual($this->Socket->read(26), null);
		$this->assertEqual($this->Socket->lastError(), null);
	}

/**
 * testLastError method
 *
 * @access public
 * @return void
 */
	function testLastError() {
		$this->Socket = new CakeSocket();
		$this->Socket->setLastError(4, 'some error here');
		$this->assertEqual($this->Socket->lastError(), '4: some error here');
	}

/**
 * testReset method
 *
 * @access public
 * @return void
 */
	function testReset() {
		$config = array(
			'persistent'	=> true,
			'host'			=> '127.0.0.1',
			'protocol'		=> 'udp',
			'port'			=> 80,
			'timeout'		=> 20
		);
		$anotherSocket = new CakeSocket($config);
		$anotherSocket->reset();
		$this->assertEqual(array(), $anotherSocket->config);
	}
}
