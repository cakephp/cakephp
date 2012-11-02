<?php
/**
 * SocketTest file
 *
 * PHP 5
 *
 * CakePHP(tm) Tests <http://book.cakephp.org/2.0/en/development/testing.html>
 * Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/2.0/en/development/testing.html CakePHP(tm) Tests
 * @package       Cake.Test.Case.Network
 * @since         CakePHP(tm) v 1.2.0.4206
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

App::uses('CakeSocket', 'Network');

/**
 * SocketTest class
 *
 * @package       Cake.Test.Case.Network
 */
class CakeSocketTest extends CakeTestCase {

/**
 * setUp method
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();
		$this->Socket = new CakeSocket(array('timeout' => 1));
	}

/**
 * tearDown method
 *
 * @return void
 */
	public function tearDown() {
		parent::tearDown();
		unset($this->Socket);
	}

/**
 * testConstruct method
 *
 * @return void
 */
	public function testConstruct() {
		$this->Socket = new CakeSocket();
		$config = $this->Socket->config;
		$this->assertSame($config, array(
			'persistent'	=> false,
			'host'			=> 'localhost',
			'protocol'		=> getprotobyname('tcp'),
			'port'			=> 80,
			'timeout'		=> 30
		));

		$this->Socket->reset();
		$this->Socket->__construct(array('host' => 'foo-bar'));
		$config['host'] = 'foo-bar';
		$this->assertSame($this->Socket->config, $config);

		$this->Socket = new CakeSocket(array('host' => 'www.cakephp.org', 'port' => 23, 'protocol' => 'udp'));
		$config = $this->Socket->config;

		$config['host'] = 'www.cakephp.org';
		$config['port'] = 23;
		$config['protocol'] = 17;

		$this->assertSame($this->Socket->config, $config);
	}

/**
 * testSocketConnection method
 *
 * @return void
 */
	public function testSocketConnection() {
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
			array(array('host' => 'invalid.host', 'port' => 9999, 'timeout' => 1)),
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
 * @return void
 */
	public function testSocketHost() {
		$this->Socket = new CakeSocket();
		$this->Socket->connect();
		$this->assertEquals('127.0.0.1', $this->Socket->address());
		$this->assertEquals(gethostbyaddr('127.0.0.1'), $this->Socket->host());
		$this->assertEquals(null, $this->Socket->lastError());
		$this->assertTrue(in_array('127.0.0.1', $this->Socket->addresses()));

		$this->Socket = new CakeSocket(array('host' => '127.0.0.1'));
		$this->Socket->connect();
		$this->assertEquals('127.0.0.1', $this->Socket->address());
		$this->assertEquals(gethostbyaddr('127.0.0.1'), $this->Socket->host());
		$this->assertEquals(null, $this->Socket->lastError());
		$this->assertTrue(in_array('127.0.0.1', $this->Socket->addresses()));
	}

/**
 * testSocketWriting method
 *
 * @return void
 */
	public function testSocketWriting() {
		$request = "GET / HTTP/1.1\r\nConnection: close\r\n\r\n";
		$this->assertTrue((bool)$this->Socket->write($request));
	}

/**
 * testSocketReading method
 *
 * @return void
 */
	public function testSocketReading() {
		$this->Socket = new CakeSocket(array('timeout' => 5));
		$this->Socket->connect();
		$this->assertEquals(null, $this->Socket->read(26));

		$config = array('host' => 'google.com', 'port' => 80, 'timeout' => 1);
		$this->Socket = new CakeSocket($config);
		$this->assertTrue($this->Socket->connect());
		$this->assertEquals(null, $this->Socket->read(26));
		$this->assertEquals('2: ' . __d('cake_dev', 'Connection timed out'), $this->Socket->lastError());
	}

/**
 * testTimeOutConnection method
 *
 * @return void
 */
	public function testTimeOutConnection() {
		$config = array('host' => '127.0.0.1', 'timeout' => 0.5);
		$this->Socket = new CakeSocket($config);
		$this->assertTrue($this->Socket->connect());

		$config = array('host' => '127.0.0.1', 'timeout' => 0.00001);
		$this->Socket = new CakeSocket($config);
		$this->assertFalse($this->Socket->read(1024 * 1024));
		$this->assertEquals('2: ' . __d('cake_dev', 'Connection timed out'), $this->Socket->lastError());
	}

/**
 * testLastError method
 *
 * @return void
 */
	public function testLastError() {
		$this->Socket = new CakeSocket();
		$this->Socket->setLastError(4, 'some error here');
		$this->assertEquals('4: some error here', $this->Socket->lastError());
	}

/**
 * testReset method
 *
 * @return void
 */
	public function testReset() {
		$config = array(
			'persistent' => true,
			'host' => '127.0.0.1',
			'protocol' => 'udp',
			'port' => 80,
			'timeout' => 20
		);
		$anotherSocket = new CakeSocket($config);
		$anotherSocket->reset();
		$this->assertEquals(array(), $anotherSocket->config);
	}

/**
 * testEncrypt
 *
 * @expectedException SocketException
 * @return void
 */
	public function testEnableCryptoSocketExceptionNoSsl() {
		$this->skipIf(!extension_loaded('openssl'), 'OpenSSL is not enabled cannot test SSL.');
		$configNoSslOrTls = array('host' => 'localhost', 'port' => 80, 'timeout' => 0.1);

		// testing exception on no ssl socket server for ssl and tls methods
		$this->Socket = new CakeSocket($configNoSslOrTls);
		$this->Socket->connect();
		$this->Socket->enableCrypto('sslv3', 'client');
	}

/**
 * testEnableCryptoSocketExceptionNoTls
 *
 * @expectedException SocketException
 * @return void
 */
	public function testEnableCryptoSocketExceptionNoTls() {
		$configNoSslOrTls = array('host' => 'localhost', 'port' => 80, 'timeout' => 0.1);

		// testing exception on no ssl socket server for ssl and tls methods
		$this->Socket = new CakeSocket($configNoSslOrTls);
		$this->Socket->connect();
		$this->Socket->enableCrypto('tls', 'client');
	}

/**
 * _connectSocketToSslTls
 *
 * @return void
 */
	protected function _connectSocketToSslTls() {
		$this->skipIf(!extension_loaded('openssl'), 'OpenSSL is not enabled cannot test SSL.');
		$configSslTls = array('host' => 'smtp.gmail.com', 'port' => 465, 'timeout' => 5);
		$this->Socket = new CakeSocket($configSslTls);
		$this->Socket->connect();
	}

/**
 * testEnableCryptoBadMode
 *
 * @expectedException InvalidArgumentException
 * @return void
 */
	public function testEnableCryptoBadMode() {
		// testing wrong encryption mode
		$this->_connectSocketToSslTls();
		$this->Socket->enableCrypto('doesntExistMode', 'server');
		$this->Socket->disconnect();
	}

/**
 * testEnableCrypto
 *
 * @return void
 */
	public function testEnableCrypto() {
		// testing on ssl server
		$this->_connectSocketToSslTls();
		$this->assertTrue($this->Socket->enableCrypto('sslv3', 'client'));
		$this->Socket->disconnect();

		// testing on tls server
		$this->_connectSocketToSslTls();
		$this->assertTrue($this->Socket->enableCrypto('tls', 'client'));
		$this->Socket->disconnect();
	}

/**
 * testEnableCryptoExceptionEnableTwice
 *
 * @expectedException SocketException
 * @return void
 */
	public function testEnableCryptoExceptionEnableTwice() {
		// testing on tls server
		$this->_connectSocketToSslTls();
		$this->Socket->enableCrypto('tls', 'client');
		$this->Socket->enableCrypto('tls', 'client');
	}

/**
 * testEnableCryptoExceptionDisableTwice
 *
 * @expectedException SocketException
 * @return void
 */
	public function testEnableCryptoExceptionDisableTwice() {
		// testing on tls server
		$this->_connectSocketToSslTls();
		$this->Socket->enableCrypto('tls', 'client', false);
	}

/**
 * testEnableCryptoEnableStatus
 *
 * @return void
 */
	public function testEnableCryptoEnableStatus() {
		// testing on tls server
		$this->_connectSocketToSslTls();
		$this->assertFalse($this->Socket->encrypted);
		$this->Socket->enableCrypto('tls', 'client', true);
		$this->assertTrue($this->Socket->encrypted);
	}

}
