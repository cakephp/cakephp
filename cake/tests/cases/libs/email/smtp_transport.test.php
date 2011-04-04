<?php
/**
 * SmtpTransportTest file
 *
 * PHP 5
 *
 * CakePHP(tm) Tests <http://book.cakephp.org/view/1196/Testing>
 * Copyright 2005-2011, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright 2005-2011, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/view/1196/Testing CakePHP(tm) Tests
 * @package       cake.tests.cases.libs.email
 * @since         CakePHP(tm) v 2.0.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
App::import('Lib', array('email/AbstractTransport', 'email/SmtpTransport'));

/**
 * Help to test SmtpTransport
 *
 */
class SmtpTestTransport extends SmtpTransport {

/**
 * Config the timeout
 *
 * @var array
 */
	protected $_config = array(
		'timeout' => 30
	);

/**
 * Helper to change the socket
 *
 * @param object $socket
 * @return void
 */
	public function setSocket(CakeSocket $socket) {
		$this->_socket = $socket;
	}

/**
 * Disabled the socket change
 *
 * @return void
 */
	protected function _generateSocket() {
		return;
	}

/**
 * Magic function to call protected methods
 *
 * @param string $method
 * @param string $args
 * @return mixed
 */ 
	public function __call($method, $args) {
		$method = '_' . $method;
		return $this->$method();
	}

}

/**
 * Test case
 *
 */
class StmpProtocolTest extends CakeTestCase {

/**
 * Setup
 *
 * @return void
 */
	public function setUp() {
		if (!class_exists('MockSocket')) {
			$this->getMock('CakeSocket', array('read', 'write', 'connect'), array(), 'MockSocket');
		}
		$this->socket = new MockSocket();

		$this->SmtpTransport = new SmtpTestTransport();
		$this->SmtpTransport->setSocket($this->socket);
	}

/**
 * testConnectEhlo method
 *
 * @return void
 */
	public function testConnectEhlo() {
		$this->socket->expects($this->any())->method('connect')->will($this->returnValue(true));
		$this->socket->expects($this->at(0))->method('read')->will($this->returnValue(false));
		$this->socket->expects($this->at(1))->method('read')->will($this->returnValue("220 Welcome message\r\n"));
		$this->socket->expects($this->at(2))->method('write')->with("EHLO localhost\r\n");
		$this->socket->expects($this->at(3))->method('read')->will($this->returnValue(false));
		$this->socket->expects($this->at(4))->method('read')->will($this->returnValue("250 Accepted\r\n"));
		$this->SmtpTransport->connect();
	}

/**
 * testConnectHelo method
 *
 * @return void
 */
	public function testConnectHelo() {
		$this->socket->expects($this->any())->method('connect')->will($this->returnValue(true));
		$this->socket->expects($this->at(0))->method('read')->will($this->returnValue(false));
		$this->socket->expects($this->at(1))->method('read')->will($this->returnValue("220 Welcome message\r\n"));
		$this->socket->expects($this->at(2))->method('write')->with("EHLO localhost\r\n");
		$this->socket->expects($this->at(3))->method('read')->will($this->returnValue(false));
		$this->socket->expects($this->at(4))->method('read')->will($this->returnValue("200 Not Accepted\r\n"));
		$this->socket->expects($this->at(5))->method('write')->with("HELO localhost\r\n");
		$this->socket->expects($this->at(6))->method('read')->will($this->returnValue(false));
		$this->socket->expects($this->at(7))->method('read')->will($this->returnValue("250 Accepted\r\n"));
		$this->SmtpTransport->connect();
	}

/**
 * testConnectFail method
 *
 * @expectedException Exception
 * @return void
 */
	public function testConnetFail() {
		$this->socket->expects($this->any())->method('connect')->will($this->returnValue(true));
		$this->socket->expects($this->at(0))->method('read')->will($this->returnValue(false));
		$this->socket->expects($this->at(1))->method('read')->will($this->returnValue("220 Welcome message\r\n"));
		$this->socket->expects($this->at(2))->method('write')->with("EHLO localhost\r\n");
		$this->socket->expects($this->at(3))->method('read')->will($this->returnValue(false));
		$this->socket->expects($this->at(4))->method('read')->will($this->returnValue("200 Not Accepted\r\n"));
		$this->socket->expects($this->at(5))->method('write')->with("HELO localhost\r\n");
		$this->socket->expects($this->at(6))->method('read')->will($this->returnValue(false));
		$this->socket->expects($this->at(7))->method('read')->will($this->returnValue("200 Not Accepted\r\n"));
		$this->SmtpTransport->connect();
	}

}