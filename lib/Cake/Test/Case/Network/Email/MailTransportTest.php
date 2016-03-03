<?php
/**
 * MailTransportTest file
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
App::uses('MailTransport', 'Network/Email');

/**
 * Test case
 */
class MailTransportTest extends CakeTestCase {

/**
 * Setup
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();
		$this->MailTransport = $this->getMock('MailTransport', array('_mail'));
		$this->MailTransport->config(array('additionalParameters' => '-f'));
	}

/**
 * testSend method
 *
 * @return void
 */
	public function testSendData() {
		$email = $this->getMock('CakeEmail', array('message'), array());
		$email->from('noreply@cakephp.org', 'CakePHP Test');
		$email->returnPath('pleasereply@cakephp.org', 'CakePHP Return');
		$email->to('cake@cakephp.org', 'CakePHP');
		$email->cc(array('mark@cakephp.org' => 'Mark Story', 'juan@cakephp.org' => 'Juan Basso'));
		$email->bcc('phpnut@cakephp.org');
		$email->messageID('<4d9946cf-0a44-4907-88fe-1d0ccbdd56cb@localhost>');
		$longNonAscii = 'Foø Bår Béz Foø Bår Béz Foø Bår Béz Foø Bår Béz';
		$email->subject($longNonAscii);
		$date = date(DATE_RFC2822);
		$email->setHeaders(array(
			'X-Mailer' => 'CakePHP Email',
			'Date' => $date,
			'X-add' => mb_encode_mimeheader($longNonAscii, 'utf8', 'B'),
		));
		$email->expects($this->any())->method('message')
			->will($this->returnValue(array('First Line', 'Second Line', '.Third Line', '')));

		$encoded = '=?UTF-8?B?Rm/DuCBCw6VyIELDqXogRm/DuCBCw6VyIELDqXogRm/DuCBCw6VyIELDqXog?=';
		$encoded .= ' =?UTF-8?B?Rm/DuCBCw6VyIELDqXo=?=';

		$data = "From: CakePHP Test <noreply@cakephp.org>" . PHP_EOL;
		$data .= "Return-Path: CakePHP Return <pleasereply@cakephp.org>" . PHP_EOL;
		$data .= "Cc: Mark Story <mark@cakephp.org>, Juan Basso <juan@cakephp.org>" . PHP_EOL;
		$data .= "Bcc: phpnut@cakephp.org" . PHP_EOL;
		$data .= "X-Mailer: CakePHP Email" . PHP_EOL;
		$data .= "Date: " . $date . PHP_EOL;
		$data .= "X-add: " . $encoded . PHP_EOL;
		$data .= "Message-ID: <4d9946cf-0a44-4907-88fe-1d0ccbdd56cb@localhost>" . PHP_EOL;
		$data .= "MIME-Version: 1.0" . PHP_EOL;
		$data .= "Content-Type: text/plain; charset=UTF-8" . PHP_EOL;
		$data .= "Content-Transfer-Encoding: 8bit";

		$this->MailTransport->expects($this->once())->method('_mail')
			->with(
				'CakePHP <cake@cakephp.org>',
				$encoded,
				implode(PHP_EOL, array('First Line', 'Second Line', '.Third Line', '')),
				$data,
				'-f'
			);

		$result = $this->MailTransport->send($email);

		$this->assertContains('Subject: ', $result['headers']);
		$this->assertContains('To: ', $result['headers']);
	}

}
