<?php
/**
 * DebugTransportTest file
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
App::uses('CakeEmail', 'Network/Email');
App::uses('AbstractTransport', 'Network/Email');
App::uses('DebugTransport', 'Network/Email');

/**
 * Test case
 *
 */
class DebugTransportTest extends CakeTestCase {

/**
 * Setup
 *
 * @return void
 */
	public function setUp() {
		$this->DebugTransport = new DebugTransport();
	}

/**
 * testSend method
 *
 * @return void
 */
	public function testSend() {
		$this->getMock('CakeEmail', array('message'), array(), 'DebugCakeEmail');
		$email = new DebugCakeEmail();
		$email->from('noreply@cakephp.org', 'CakePHP Test');
		$email->to('cake@cakephp.org', 'CakePHP');
		$email->cc(array('mark@cakephp.org' => 'Mark Story', 'juan@cakephp.org' => 'Juan Basso'));
		$email->bcc('phpnut@cakephp.org');
		$email->messageID('<4d9946cf-0a44-4907-88fe-1d0ccbdd56cb@localhost>');
		$email->subject('Testing Message');
		$email->expects($this->any())->method('message')->will($this->returnValue(array('First Line', 'Second Line', '')));

		$data = "From: CakePHP Test <noreply@cakephp.org>\r\n";
		$data .= "To: CakePHP <cake@cakephp.org>\r\n";
		$data .= "Cc: Mark Story <mark@cakephp.org>, Juan Basso <juan@cakephp.org>\r\n";
		$data .= "Bcc: phpnut@cakephp.org\r\n";
		$data .= "X-Mailer: CakePHP Email\r\n";
		$data .= "Date: " . date(DATE_RFC2822) . "\r\n";
		$data .= "Message-ID: <4d9946cf-0a44-4907-88fe-1d0ccbdd56cb@localhost>\r\n";
		$data .= "Subject: Testing Message\r\n";
		$data .= "MIME-Version: 1.0\r\n";
		$data .= "Content-Type: text/plain; charset=UTF-8\r\n";
		$data .= "Content-Transfer-Encoding: 7bit";
		$data .= "\n\n";
		$data .= "First Line\n";
		$data .= "Second Line\n";

		$result = $this->DebugTransport->send($email);
		$this->assertEquals($data, $result);
	}

}