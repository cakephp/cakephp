<?php
/**
 * SmtpTransportTest file
 *
 * PHP 5
 *
 * CakePHP(tm) Tests <http://book.cakephp.org/2.0/en/development/testing.html>
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/2.0/en/development/testing.html CakePHP(tm) Tests
 * @package       Cake.Test.Case.Network.Email
 * @since         CakePHP(tm) v 2.0.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */

App::uses('CakeEmail', 'Network/Email');
App::uses('AbstractTransport', 'Network/Email');
App::uses('SmtpTransport', 'Network/Email');

/**
 * Help to test SmtpTransport
 *
 */
class SmtpTestTransport extends SmtpTransport {

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
class SmtpTransportTest extends CakeTestCase {

/**
 * Setup
 *
 * @return void
 */
	public function setUp() {
		if (!class_exists('MockSocket')) {
			$this->getMock('CakeSocket', array('read', 'write', 'connect', 'enableCrypto'), array(), 'MockSocket');
		}
		$this->socket = new MockSocket();

		$this->SmtpTransport = new SmtpTestTransport();
		$this->SmtpTransport->setSocket($this->socket);
		$this->SmtpTransport->config(array('client' => 'localhost'));
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
 * testConnectEhloTls method
 *
 * @return void
 */
	public function testConnectEhloTls() {
		$this->SmtpTransport->config(array('tls' => true));
		$this->socket->expects($this->any())->method('connect')->will($this->returnValue(true));
		$this->socket->expects($this->at(0))->method('read')->will($this->returnValue(false));
		$this->socket->expects($this->at(1))->method('read')->will($this->returnValue("220 Welcome message\r\n"));
		$this->socket->expects($this->at(2))->method('write')->with("EHLO localhost\r\n");
		$this->socket->expects($this->at(3))->method('read')->will($this->returnValue(false));
		$this->socket->expects($this->at(4))->method('read')->will($this->returnValue("250 Accepted\r\n"));
		$this->socket->expects($this->at(5))->method('write')->with("STARTTLS\r\n");
		$this->socket->expects($this->at(6))->method('read')->will($this->returnValue(false));
		$this->socket->expects($this->at(7))->method('read')->will($this->returnValue("220 Server ready\r\n"));
		$this->socket->expects($this->at(8))->method('other')->with('tls')->will($this->returnValue(true));
		$this->socket->expects($this->at(9))->method('write')->with("EHLO localhost\r\n");
		$this->socket->expects($this->at(10))->method('read')->will($this->returnValue(false));
		$this->socket->expects($this->at(11))->method('read')->will($this->returnValue("250 Accepted\r\n"));
		$this->SmtpTransport->connect();
	}

/**
 * testConnectEhloTlsOnNonTlsServer method
 *
 * @expectedException SocketException
 * @return void
 */
	public function testConnectEhloTlsOnNonTlsServer() {
		$this->SmtpTransport->config(array('tls' => true));
		$this->socket->expects($this->any())->method('connect')->will($this->returnValue(true));
		$this->socket->expects($this->at(0))->method('read')->will($this->returnValue(false));
		$this->socket->expects($this->at(1))->method('read')->will($this->returnValue("220 Welcome message\r\n"));
		$this->socket->expects($this->at(2))->method('write')->with("EHLO localhost\r\n");
		$this->socket->expects($this->at(3))->method('read')->will($this->returnValue(false));
		$this->socket->expects($this->at(4))->method('read')->will($this->returnValue("250 Accepted\r\n"));
		$this->socket->expects($this->at(5))->method('write')->with("STARTTLS\r\n");
		$this->socket->expects($this->at(6))->method('read')->will($this->returnValue(false));
		$this->socket->expects($this->at(7))->method('read')->will($this->returnValue("500 5.3.3 Unrecognized command\r\n"));
		$this->SmtpTransport->connect();
	}

/**
 * testConnectEhloNoTlsOnRequiredTlsServer method
 *
 * @expectedException SocketException
 * @return void
 */
	public function testConnectEhloNoTlsOnRequiredTlsServer() {
		$this->SmtpTransport->config(array('tls' => false, 'username' => 'user', 'password' => 'pass'));
		$this->socket->expects($this->any())->method('connect')->will($this->returnValue(true));
		$this->socket->expects($this->at(0))->method('read')->will($this->returnValue(false));
		$this->socket->expects($this->at(1))->method('read')->will($this->returnValue("220 Welcome message\r\n"));
		$this->socket->expects($this->at(2))->method('write')->with("EHLO localhost\r\n");
		$this->socket->expects($this->at(3))->method('read')->will($this->returnValue(false));
		$this->socket->expects($this->at(4))->method('read')->will($this->returnValue("250 Accepted\r\n"));
		$this->socket->expects($this->at(5))->method('read')->with("AUTH LOGIN\r\n");
		$this->socket->expects($this->at(6))->method('read')->will($this->returnValue(false));
		$this->socket->expects($this->at(7))->method('read')->will($this->returnValue("504 5.7.4 Unrecognized authentication type\r\n"));
		$this->SmtpTransport->connect();
		$this->SmtpTransport->auth();
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
 * @expectedException SocketException
 * @return void
 */
	public function testConnectFail() {
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
		$this->SmtpTransport->config(array('username' => 'mark', 'password' => 'story'));
		$this->SmtpTransport->auth();
	}

/**
 * testAuthNoAuth method
 *
 * @return void
 */
	public function testAuthNoAuth() {
		$this->socket->expects($this->never())->method('write')->with("AUTH LOGIN\r\n");
		$this->SmtpTransport->config(array('username' => null, 'password' => null));
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

/**
 * testSendData method
 *
 * @return void
 */
	public function testSendData() {
		$this->getMock('CakeEmail', array('message'), array(), 'SmtpCakeEmail');
		$email = new SmtpCakeEmail();
		$email->from('noreply@cakephp.org', 'CakePHP Test');
		$email->returnPath('pleasereply@cakephp.org', 'CakePHP Return');
		$email->to('cake@cakephp.org', 'CakePHP');
		$email->cc(array('mark@cakephp.org' => 'Mark Story', 'juan@cakephp.org' => 'Juan Basso'));
		$email->bcc('phpnut@cakephp.org');
		$email->messageID('<4d9946cf-0a44-4907-88fe-1d0ccbdd56cb@localhost>');
		$email->subject('Testing SMTP');
		$date = date(DATE_RFC2822);
		$email->setHeaders(array('X-Mailer' => SmtpCakeEmail::EMAIL_CLIENT, 'Date' => $date));
		$email->expects($this->any())->method('message')->will($this->returnValue(array('First Line', 'Second Line', '.Third Line', '')));

		$data = "From: CakePHP Test <noreply@cakephp.org>\r\n";
		$data .= "Return-Path: CakePHP Return <pleasereply@cakephp.org>\r\n";
		$data .= "To: CakePHP <cake@cakephp.org>\r\n";
		$data .= "Cc: Mark Story <mark@cakephp.org>, Juan Basso <juan@cakephp.org>\r\n";
		$data .= "X-Mailer: CakePHP Email\r\n";
		$data .= "Date: " . $date . "\r\n";
		$data .= "Message-ID: <4d9946cf-0a44-4907-88fe-1d0ccbdd56cb@localhost>\r\n";
		$data .= "Subject: Testing SMTP\r\n";
		$data .= "MIME-Version: 1.0\r\n";
		$data .= "Content-Type: text/plain; charset=UTF-8\r\n";
		$data .= "Content-Transfer-Encoding: 8bit\r\n";
		$data .= "\r\n";
		$data .= "First Line\r\n";
		$data .= "Second Line\r\n";
		$data .= "..Third Line\r\n"; // RFC5321 4.5.2.Transparency
		$data .= "\r\n";
		$data .= "\r\n\r\n.\r\n";

		$this->socket->expects($this->at(0))->method('write')->with("DATA\r\n");
		$this->socket->expects($this->at(1))->method('read')->will($this->returnValue(false));
		$this->socket->expects($this->at(2))->method('read')->will($this->returnValue("354 OK\r\n"));
		$this->socket->expects($this->at(3))->method('write')->with($data);
		$this->socket->expects($this->at(4))->method('read')->will($this->returnValue(false));
		$this->socket->expects($this->at(5))->method('read')->will($this->returnValue("250 OK\r\n"));

		$this->SmtpTransport->setCakeEmail($email);
		$this->SmtpTransport->sendData();
	}

/**
 * testQuit method
 *
 * @return void
 */
	public function testQuit() {
		$this->socket->expects($this->at(0))->method('write')->with("QUIT\r\n");
		$this->SmtpTransport->disconnect();
	}

/**
 * testEmptyConfigArray method
 *
 * @return void
 */
	public function testEmptyConfigArray() {
		$expected = $this->SmtpTransport->config(array(
			'client' => 'myhost.com',
			'port' => 666
		));

		$this->assertEquals(666, $expected['port']);

		$result = $this->SmtpTransport->config(array());
		$this->assertEquals($expected, $result);
	}

}
