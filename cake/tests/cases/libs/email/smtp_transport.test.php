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
App::import('Lib', array('CakeEmail', 'email/AbstractTransport', 'email/SmtpTransport'));

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
 * Helper to change the config attribute
 *
 * @param array $config
 * @return void
 */
	public function setConfig($config) {
		$this->_config = array_merge($this->_config, $config);
	}

/**
 * Helper to change the CakeEmail
 *
 * @param object $cakeEmail
 * @return void
 */
	public function setCakeEmail($cakeEmail) {
		$this->_cakeEmail = $cakeEmail;
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

/**
 * testAuth method
 *
 * @return void
 */
	public function testAuth() {
		$this->socket->expects($this->at(0))->method('write')->with("AUTH LOGIN\r\n");
		$this->socket->expects($this->at(1))->method('read')->will($this->returnValue(false));
		$this->socket->expects($this->at(2))->method('read')->will($this->returnValue("334 Login\r\n"));
		$this->socket->expects($this->at(3))->method('write')->with("bWFyaw==\r\n");
		$this->socket->expects($this->at(4))->method('read')->will($this->returnValue(false));
		$this->socket->expects($this->at(5))->method('read')->will($this->returnValue("334 Pass\r\n"));
		$this->socket->expects($this->at(6))->method('write')->with("c3Rvcnk=\r\n");
		$this->socket->expects($this->at(7))->method('read')->will($this->returnValue(false));
		$this->socket->expects($this->at(8))->method('read')->will($this->returnValue("235 OK\r\n"));
		$this->SmtpTransport->setConfig(array('username' => 'mark', 'password' => 'story'));
		$this->SmtpTransport->auth();
	}

/**
 * testAuthNoAuth method
 *
 * @return void
 */
	public function testAuthNoAuth() {
		$this->socket->expects($this->never())->method('write')->with("AUTH LOGIN\r\n");
		$this->SmtpTransport->setConfig(array('username' => null, 'password' => null));
		$this->SmtpTransport->auth();
	}

/**
 * testRcpt method
 *
 * @return void
 */
	public function testRcpt() {
		$email = new CakeEmail();
		$email->from('noreply@cakephp.org', 'CakePHP Test');
		$email->to('cake@cakephp.org', 'CakePHP');
		$email->bcc('phpnut@cakephp.org');
		$email->cc(array('mark@cakephp.org' => 'Mark Story', 'juan@cakephp.org' => 'Juan Basso'));

		$this->socket->expects($this->at(0))->method('write')->with("MAIL FROM:<noreply@cakephp.org>\r\n");
		$this->socket->expects($this->at(1))->method('read')->will($this->returnValue(false));
		$this->socket->expects($this->at(2))->method('read')->will($this->returnValue("250 OK\r\n"));
		$this->socket->expects($this->at(3))->method('write')->with("RCPT TO:<cake@cakephp.org>\r\n");
		$this->socket->expects($this->at(4))->method('read')->will($this->returnValue(false));
		$this->socket->expects($this->at(5))->method('read')->will($this->returnValue("250 OK\r\n"));
		$this->socket->expects($this->at(6))->method('write')->with("RCPT TO:<mark@cakephp.org>\r\n");
		$this->socket->expects($this->at(7))->method('read')->will($this->returnValue(false));
		$this->socket->expects($this->at(8))->method('read')->will($this->returnValue("250 OK\r\n"));
		$this->socket->expects($this->at(9))->method('write')->with("RCPT TO:<juan@cakephp.org>\r\n");
		$this->socket->expects($this->at(10))->method('read')->will($this->returnValue(false));
		$this->socket->expects($this->at(11))->method('read')->will($this->returnValue("250 OK\r\n"));
		$this->socket->expects($this->at(12))->method('write')->with("RCPT TO:<phpnut@cakephp.org>\r\n");
		$this->socket->expects($this->at(13))->method('read')->will($this->returnValue(false));
		$this->socket->expects($this->at(14))->method('read')->will($this->returnValue("250 OK\r\n"));

		$this->SmtpTransport->setCakeEmail($email);
		$this->SmtpTransport->sendRcpt();
	}

}