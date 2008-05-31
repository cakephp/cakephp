<?php
/* SVN FILE: $Id$ */
/**
 * Series of tests for email component.
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
 * @package			cake
 * @subpackage		cake.cake.tests.cases.libs.controller.components
 * @since			CakePHP(tm) v 1.2.0.5347
 * @version			$Revision$
 * @modifiedby		$LastChangedBy$
 * @lastmodified	$Date$
 * @license			http://www.opensource.org/licenses/opengroup.php The Open Group Test Suite License
 */
uses('controller' . DS . 'components' . DS .'email');

class EmailTestController extends Controller {
	var $name = 'EmailTest';
	var $uses = null;
	var $components = array('Email');
}

class EmailTest extends CakeTestCase {
	var $name = 'Email';

	function setUp() {
		$this->Controller =& new EmailTestController();

		restore_error_handler();
		@$this->Controller->Component->init($this->Controller);
		set_error_handler('simpleTestErrorHandler');

		$this->Controller->Email->startup($this->Controller);
		ClassRegistry::addObject('view', new View($this->Controller));
	}

	function testBadSmtpSend() {
		$this->Controller->Email->smtpOptions['host'] = 'blah';
		$this->Controller->Email->delivery = 'smtp';
		$this->assertFalse($this->Controller->Email->send('Should not work'));
	}

	function testSmtpSend() {
		if (@fsockopen('localhost', 25)) {
			$this->assertTrue(@fsockopen('localhost', 25), 'Local mail server is running');
			$this->Controller->Email->reset();
			$this->Controller->Email->to = 'postmaster@localhost';
			$this->Controller->Email->from = 'noreply@example.com';
			$this->Controller->Email->subject = 'Cake SMTP test';
			$this->Controller->Email->replyTo = 'noreply@example.com';
			$this->Controller->Email->template = null;

			$this->Controller->Email->delivery = 'smtp';
			$this->assertTrue($this->Controller->Email->send('This is the body of the message'));

			$this->Controller->Email->_debug = true;
			if (stristr(PHP_OS, 'win') === false) {
				$this->Controller->Email->_newLine = "\n";
			}
			$this->Controller->Email->sendAs = 'text';
			$expect = <<<TEMPDOC
<pre>Host: localhost
Port: 25
Timeout: 30
To: postmaster@localhost
From: noreply@example.com
Subject: Cake SMTP test
Header:

To: postmaster@localhost
From: noreply@example.com
Reply-To: noreply@example.com
Subject: =?UTF-8?B?Q2FrZSBTTVRQIHRlc3Q=?=
X-Mailer: CakePHP Email Component
Content-Type: text/plain; charset=UTF-8
Content-Transfer-Encoding: 7bitParameters:

Message:

This is the body of the message

</pre>
TEMPDOC;

			$this->assertTrue($this->Controller->Email->send('This is the body of the message'));
			$this->assertEqual($this->Controller->Session->read('Message.email.message'), $expect);
		}
	}
	function testAuthenticatedSmtpSend() {
		if (@fsockopen('localhost', 25)) {
			$this->assertTrue(@fsockopen('localhost', 25), 'Local mail server is running');
			$this->Controller->Email->reset();
			$this->Controller->Email->to = 'postmaster@localhost';
			$this->Controller->Email->from = 'noreply@example.com';
			$this->Controller->Email->subject = 'Cake SMTP test';
			$this->Controller->Email->replyTo = 'noreply@example.com';
			$this->Controller->Email->template = null;
			$this->Controller->Email->smtpOptions['username'] = 'test';
			$this->Controller->Email->smtpOptions['password'] = 'testing';

			$this->Controller->Email->delivery = 'smtp';
			$result = $this->Controller->Email->send('This is the body of the message');
			if (!$result) {
				$code = substr($this->Controller->Email->smtpError, 0, 3);
				$this->skipIf($code == '503', 'Authentication not enabled on server');
				if ($code == '503') {
					$this->skip();
				} elseif ($code == '535') {
					$this->pass('Authentication attempted succesfully and failed as expected.');
				} else {
					$this->fail($this->Controller->Email->smtpError);
				}
			} else {
				$this->exception('Authentication passed unexpectedly');
			}
		}
	}

	function testSendFormats() {
		if (@fsockopen('localhost', 25)) {
			$this->assertTrue(@fsockopen('localhost', 25), 'Local mail server is running');
			$this->Controller->Email->reset();
			$this->Controller->Email->to = 'postmaster@localhost';
			$this->Controller->Email->from = 'noreply@example.com';
			$this->Controller->Email->subject = 'Cake SMTP test';
			$this->Controller->Email->replyTo = 'noreply@example.com';
			$this->Controller->Email->template = null;
			$this->Controller->Email->delivery = 'debug';
			if (stristr(PHP_OS, 'win') === false) {
				$this->Controller->Email->_newLine = "\n";
			}

			$this->Controller->Email->sendAs = 'text';
			$expect = <<<TEMPDOC
<pre>To: postmaster@localhost
From: noreply@example.com
Subject: Cake SMTP test
Header:

From: noreply@example.com
Reply-To: noreply@example.com
X-Mailer: CakePHP Email Component
Content-Type: text/plain; charset=UTF-8
Content-Transfer-Encoding: 7bitParameters:

Message:

This is the body of the message

</pre>
TEMPDOC;
			$this->assertTrue($this->Controller->Email->send('This is the body of the message'));
			$this->assertEqual($this->Controller->Session->read('Message.email.message'), $expect);

			$this->Controller->Email->sendAs = 'html';
			$expect = str_replace('Content-Type: text/plain; charset=UTF-8', 'Content-Type: text/html; charset=UTF-8', $expect);
			$this->assertTrue($this->Controller->Email->send('This is the body of the message'));
			$this->assertEqual($this->Controller->Session->read('Message.email.message'), $expect);

			// TODO: better test for format of message sent?
			$this->Controller->Email->sendAs = 'both';
			$expect = str_replace('Content-Type: text/html; charset=UTF-8', 'Content-Type: multipart/alternative; boundary="alt-"' . "\n", $expect);
			$this->assertTrue($this->Controller->Email->send('This is the body of the message'));
			$this->assertEqual($this->Controller->Session->read('Message.email.message'), $expect);
		}
	}

	function testSendDebug() {
		if (@fsockopen('localhost', 25)) {
			$this->assertTrue(@fsockopen('localhost', 25), 'Local mail server is running');
			$this->Controller->Email->reset();
			$this->Controller->Email->to = 'postmaster@localhost';
			$this->Controller->Email->from = 'noreply@example.com';
			$this->Controller->Email->subject = 'Cake SMTP test';
			$this->Controller->Email->replyTo = 'noreply@example.com';
			$this->Controller->Email->template = null;

			$this->Controller->Email->delivery = 'debug';
			$this->assertTrue($this->Controller->Email->send('This is the body of the message'));
		}
	}

	function testContentStripping() {
		$content = "Previous content\n--alt-\nContent-TypeContent-Type:: text/html; charsetcharset==utf-8\nContent-Transfer-Encoding: 7bit";
		$content .= "\n\n<p>My own html content</p>";

		$result = $this->Controller->Email->__strip($content, true);
		$expected = "Previous content\n--alt-\n text/html; utf-8\n 7bit\n\n<p>My own html content</p>";
		$this->assertEqual($result, $expected);
	}
}

?>