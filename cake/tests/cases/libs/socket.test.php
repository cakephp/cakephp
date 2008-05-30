<?php
/* SVN FILE: $Id$ */
/**
 * Short description for file.
 *
 * Long description for file
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) Tests <https://trac.cakephp.org/wiki/Developement/TestSuite>
 * Copyright 2005-2008, Cake Software Foundation, Inc.
 *								1785 E. Sahara Avenue, Suite 490-204
 *								Las Vegas, Nevada 89104
 *
 *  Licensed under The Open Group Test Suite License
 *  Redistributions of files must retain the above copyright notice.
 *
 * @filesource
 * @copyright		Copyright 2005-2008, Cake Software Foundation, Inc.
 * @link				https://trac.cakephp.org/wiki/Developement/TestSuite CakePHP(tm) Tests
 * @package			cake.tests
 * @subpackage		cake.tests.cases.libs
 * @since			CakePHP(tm) v 1.2.0.4206
 * @version			$Revision$
 * @modifiedby		$LastChangedBy$
 * @lastmodified	$Date$
 * @license			http://www.opensource.org/licenses/opengroup.php The Open Group Test Suite License
 */
App::import('Core', 'Socket');
/**
 * Short description for class.
 *
 * @package		cake.tests
 * @subpackage	cake.tests.cases.libs
 */
class SocketTest extends UnitTestCase {

	function setUp() {
		$this->Socket = new CakeSocket();
	}

	function testConstruct() {
		$this->Socket->__construct();
		$baseConfig = $this->Socket->_baseConfig;
		$this->assertIdentical($baseConfig, array(
			'persistent'	=> false,
			'host'			=> 'localhost',
			'protocol'		=> 'tcp',
			'port'			=> 80,
			'timeout'		=> 30
		));

		$this->Socket->reset();
		$this->Socket->__construct(array('host' => 'foo-bar'));
		$baseConfig['host'] = 'foo-bar';
		$baseConfig['protocol'] = getprotobyname($baseConfig['protocol']);
		$this->assertIdentical($this->Socket->config, $baseConfig);

		$this->Socket = new CakeSocket(array('host' => 'www.cakephp.org', 'port' => 23, 'protocol' => 'udp'));
		$baseConfig = $this->Socket->_baseConfig;

		$baseConfig['host'] = 'www.cakephp.org';
		$baseConfig['port'] = 23;
		$baseConfig['protocol'] = 17;

		$this->assertIdentical($this->Socket->config, $baseConfig);
	}

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

	function testSocketWriting() {
		$request = "GET / HTTP/1.1\r\nConnection: close\r\n\r\n";
		$this->assertTrue($this->Socket->write($request));
	}

	function testSocketReading() {
		$this->Socket = new CakeSocket(array('timeout' => 5));
		$this->Socket->connect();
		$this->assertEqual($this->Socket->read(26), null);
	}

	function testLastError() {
		$this->Socket = new CakeSocket();
		$this->Socket->setLastError(4, 'some error here');
		$this->assertEqual($this->Socket->lastError(), '4: some error here');
	}

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

	function tearDown() {
		unset($this->Socket);
	}
}
?>