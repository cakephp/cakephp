<?php
/**
 * DebugTransportTest file
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
 * @since         2.0.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Network\Email;

use Cake\Network\Email\DebugTransport;
use Cake\Network\Email\Email;
use Cake\TestSuite\TestCase;

/**
 * Test case
 *
 */
class DebugTransportTest extends TestCase {

/**
 * Setup
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();
		$this->DebugTransport = new DebugTransport();
	}

/**
 * testSend method
 *
 * @return void
 */
	public function testSend() {
		$email = $this->getMock('Cake\Network\Email\Email', array('message'));
		$email->from('noreply@cakephp.org', 'CakePHP Test');
		$email->to('cake@cakephp.org', 'CakePHP');
		$email->cc(array('mark@cakephp.org' => 'Mark Story', 'juan@cakephp.org' => 'Juan Basso'));
		$email->bcc('phpnut@cakephp.org');
		$email->messageID('<4d9946cf-0a44-4907-88fe-1d0ccbdd56cb@localhost>');
		$email->subject('Testing Message');
		$date = date(DATE_RFC2822);
		$email->setHeaders(array('X-Mailer' => Email::EMAIL_CLIENT, 'Date' => $date));
		$email->expects($this->once())->method('message')->will($this->returnValue(array('First Line', 'Second Line', '.Third Line', '')));

		$headers = "From: CakePHP Test <noreply@cakephp.org>\r\n";
		$headers .= "To: CakePHP <cake@cakephp.org>\r\n";
		$headers .= "Cc: Mark Story <mark@cakephp.org>, Juan Basso <juan@cakephp.org>\r\n";
		$headers .= "X-Mailer: CakePHP Email\r\n";
		$headers .= "Date: " . $date . "\r\n";
		$headers .= "Message-ID: <4d9946cf-0a44-4907-88fe-1d0ccbdd56cb@localhost>\r\n";
		$headers .= "Subject: Testing Message\r\n";
		$headers .= "MIME-Version: 1.0\r\n";
		$headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
		$headers .= "Content-Transfer-Encoding: 8bit";

		$data = "First Line\r\n";
		$data .= "Second Line\r\n";
		$data .= ".Third Line\r\n"; // Not use 'RFC5321 4.5.2.Transparency' in DebugTransport.

		$result = $this->DebugTransport->send($email);

		$this->assertEquals($headers, $result['headers']);
		$this->assertEquals($data, $result['message']);
	}

}
