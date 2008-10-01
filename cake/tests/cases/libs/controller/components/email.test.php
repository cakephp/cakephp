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
Configure::write('App.encoding', 'UTF-8');
App::import('Component', 'Email');
/**
 * EmailTestController class
 *
 * @package              cake
 * @subpackage           cake.tests.cases.libs.controller.components
 */
class EmailTestController extends Controller {
/**
 * name property
 *
 * @var string 'EmailTest'
 * @access public
 */
	var $name = 'EmailTest';
/**
 * uses property
 *
 * @var mixed null
 * @access public
 */
	var $uses = null;
/**
 * components property
 *
 * @var array
 * @access public
 */
	var $components = array('Email');

	var $pageTitle = 'EmailTest';
}
/**
 * EmailTest class
 *
 * @package              cake
 * @subpackage           cake.tests.cases.libs.controller.components
 */
class EmailTest extends CakeTestCase {
/**
 * name property
 *
 * @var string 'Email'
 * @access public
 */
	var $name = 'Email';
/**
 * setUp method
 *
 * @access public
 * @return void
 */
	function setUp() {
		$this->Controller =& new EmailTestController();

		restore_error_handler();
		@$this->Controller->Component->init($this->Controller);
		set_error_handler('simpleTestErrorHandler');

		$this->Controller->Email->startup($this->Controller);
		ClassRegistry::addObject('view', new View($this->Controller));
		Configure::write('viewPaths', array(TEST_CAKE_CORE_INCLUDE_PATH . 'tests' . DS . 'test_app' . DS . 'views'. DS));

	}
/**
 * testBadSmtpSend method
 *
 * @access public
 * @return void
 */
	function testBadSmtpSend() {
		$this->Controller->Email->smtpOptions['host'] = 'blah';
		$this->Controller->Email->delivery = 'smtp';
		$this->assertFalse($this->Controller->Email->send('Should not work'));
	}
/**
 * testSmtpSend method
 *
 * @access public
 * @return void
 */
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
Subject: Cake SMTP test
X-Mailer: CakePHP Email Component
Content-Type: text/plain; charset=UTF-8
Content-Transfer-Encoding: 7bitParameters:

Message:

This is the body of the message

</pre>
TEMPDOC;
			$this->assertTrue($this->Controller->Email->send('This is the body of the message'));
			$this->assertEqual($this->Controller->Session->read('Message.email.message'), $this->__osFix($expect));
		}
	}
/**
 * testAuthenticatedSmtpSend method
 *
 * @access public
 * @return void
 */
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
/**
 * testSendFormats method
 *
 * @access public
 * @return void
 */
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

			$message = <<<MSGBLOC
<pre>To: postmaster@localhost
From: noreply@example.com
Subject: Cake SMTP test
Header:

From: noreply@example.com
Reply-To: noreply@example.com
X-Mailer: CakePHP Email Component
Content-Type: {CONTENTTYPE}
Content-Transfer-Encoding: 7bitParameters:

Message:

This is the body of the message

</pre>
MSGBLOC;
			$this->Controller->Email->sendAs = 'text';
			$expect = str_replace('{CONTENTTYPE}', 'text/plain; charset=UTF-8', $message);
			$this->assertTrue($this->Controller->Email->send('This is the body of the message'));
			$this->assertEqual($this->Controller->Session->read('Message.email.message'), $this->__osFix($expect));

			$this->Controller->Email->sendAs = 'html';
			$expect = str_replace('{CONTENTTYPE}', 'text/html; charset=UTF-8', $message);
			$this->assertTrue($this->Controller->Email->send('This is the body of the message'));
			$this->assertEqual($this->Controller->Session->read('Message.email.message'), $this->__osFix($expect));

			// TODO: better test for format of message sent?
			$this->Controller->Email->sendAs = 'both';
			$expect = str_replace('{CONTENTTYPE}', 'multipart/alternative; boundary="alt-"' . "\n", $message);
			$this->assertTrue($this->Controller->Email->send('This is the body of the message'));
			$this->assertEqual($this->Controller->Session->read('Message.email.message'), $this->__osFix($expect));
		}
	}
/**
 * testTemplates method
 *
 * @access public
 * @return void
 */
	function testTemplates() {
		if (@fsockopen('localhost', 25)) {
			$this->assertTrue(@fsockopen('localhost', 25), 'Local mail server is running');
			$this->Controller->Email->reset();
			$this->Controller->Email->to = 'postmaster@localhost';
			$this->Controller->Email->from = 'noreply@example.com';
			$this->Controller->Email->subject = 'Cake SMTP test';
			$this->Controller->Email->replyTo = 'noreply@example.com';

			$this->Controller->Email->delivery = 'debug';

			$header = <<<HEADBLOC
To: postmaster@localhost
From: noreply@example.com
Subject: Cake SMTP test
Header:

From: noreply@example.com
Reply-To: noreply@example.com
X-Mailer: CakePHP Email Component
Content-Type: {CONTENTTYPE}
Content-Transfer-Encoding: 7bitParameters:

Message:


HEADBLOC;

			$this->Controller->Email->layout = 'default';
			$this->Controller->Email->template = 'default';

			$text = <<<TEXTBLOC

This is the body of the message

This email was sent using the CakePHP Framework, http://cakephp.org.


TEXTBLOC;

			$html = <<<HTMLBLOC
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01//EN">

<html>
<head>
	<title>EmailTest</title>
</head>

<body>
	<p> This is the body of the message</p><p> </p>
	<p>This email was sent using the <a href="http://cakephp.org">CakePHP Framework</a></p>
</body>
</html>

HTMLBLOC;

			$this->Controller->Email->sendAs = 'text';
			$expect = '<pre>' . str_replace('{CONTENTTYPE}', 'text/plain; charset=UTF-8', $header) . $text . "\n" . '</pre>';
			$this->assertTrue($this->Controller->Email->send('This is the body of the message'));
			$this->assertEqual($this->Controller->Session->read('Message.email.message'), $this->__osFix($expect));

			$this->Controller->Email->sendAs = 'html';
			$expect = '<pre>' . str_replace('{CONTENTTYPE}', 'text/html; charset=UTF-8', $header) . $html . "\n" . '</pre>';
			$this->assertTrue($this->Controller->Email->send('This is the body of the message'));
			$this->assertEqual($this->Controller->Session->read('Message.email.message'), $this->__osFix($expect));

			$this->Controller->Email->sendAs = 'both';
			$expect = str_replace('{CONTENTTYPE}', 'multipart/alternative; boundary="alt-"' . "\n", $header);
			$expect .= '--alt-' . "\n" . 'Content-Type: text/plain; charset=UTF-8' . "\n" . 'Content-Transfer-Encoding: 7bit' . "\n\n" . $text . "\n\n";
			$expect .= '--alt-' . "\n" . 'Content-Type: text/html; charset=UTF-8' . "\n" . 'Content-Transfer-Encoding: 7bit' . "\n\n" . $html . "\n\n";
			$expect = '<pre>' . $expect . '--alt---' . "\n\n" . '</pre>';
			$this->assertTrue($this->Controller->Email->send('This is the body of the message'));
			$this->assertEqual($this->Controller->Session->read('Message.email.message'), $this->__osFix($expect));

			$html = <<<HTMLBLOC
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01//EN">

<html>
<head>
	<title>EmailTest</title>
</head>

<body>
	<p> This is the body of the message</p><p> </p>
	<p>This email was sent using the CakePHP Framework</p>
</body>
</html>

HTMLBLOC;

			$this->Controller->Email->sendAs = 'html';
			$expect = '<pre>' . str_replace('{CONTENTTYPE}', 'text/html; charset=UTF-8', $header) . $html . "\n" . '</pre>';
			$this->assertTrue($this->Controller->Email->send('This is the body of the message', 'default', 'thin'));
			$this->assertEqual($this->Controller->Session->read('Message.email.message'), $this->__osFix($expect));

			return;

			$text = <<<TEXTBLOC

This element has some text that is just too wide to comply with email
standards.
This is the body of the message

This email was sent using the CakePHP Framework, http://cakephp.org.


TEXTBLOC;

			$this->Controller->Email->sendAs = 'text';
			$expect = '<pre>' . str_replace('{CONTENTTYPE}', 'text/plain; charset=UTF-8', $header) . $text . "\n" . '</pre>';
			$this->assertTrue($this->Controller->Email->send('This is the body of the message', 'wide', 'default'));
			$this->assertEqual($this->Controller->Session->read('Message.email.message'), $this->__osFix($expect));

		}

	}
/**
 * testSendDebug method
 *
 * @access public
 * @return void
 */
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
/**
 * testContentStripping method
 *
 * @access public
 * @return void
 */
	function testContentStripping() {
		$content = "Previous content\n--alt-\nContent-TypeContent-Type:: text/html; charsetcharset==utf-8\nContent-Transfer-Encoding: 7bit";
		$content .= "\n\n<p>My own html content</p>";

		$result = $this->Controller->Email->__strip($content, true);
		$expected = "Previous content\n--alt-\n text/html; utf-8\n 7bit\n\n<p>My own html content</p>";
		$this->assertEqual($result, $expected);
	}

	function testMultibyte() {
		if (@fsockopen('localhost', 25)) {
			$this->assertTrue(@fsockopen('localhost', 25), 'Local mail server is running');
			$this->Controller->Email->reset();
			$this->Controller->Email->to = 'postmaster@localhost';
			$this->Controller->Email->from = 'noreply@example.com';
			$this->Controller->Email->subject = 'هذه رسالة بعنوان طويل مرسل للمستلم';
			$this->Controller->Email->replyTo = 'noreply@example.com';
			$this->Controller->Email->template = null;
			$this->Controller->Email->delivery = 'debug';

			$subject = '=?UTF-8?B?2YfYsNmHINix2LPYp9mE2Kkg2KjYudmG2YjYp9mGINi32YjZitmEINmF2LE=?=' . "\r\n" . ' =?UTF-8?B?2LPZhCDZhNmE2YXYs9iq2YTZhQ==?=';

			$this->Controller->Email->sendAs = 'text';
			$this->assertTrue($this->Controller->Email->send('This is the body of the message'));
			preg_match('/Subject: (.*)Header:/s', $this->Controller->Session->read('Message.email.message'), $matches);
			$this->assertEqual(trim($matches[1]), $subject);

			$this->Controller->Email->sendAs = 'html';
			$this->assertTrue($this->Controller->Email->send('This is the body of the message'));
			preg_match('/Subject: (.*)Header:/s', $this->Controller->Session->read('Message.email.message'), $matches);
			$this->assertEqual(trim($matches[1]), $subject);

			$this->Controller->Email->sendAs = 'both';
			$this->assertTrue($this->Controller->Email->send('This is the body of the message'));
			preg_match('/Subject: (.*)Header:/s', $this->Controller->Session->read('Message.email.message'), $matches);
			$this->assertEqual(trim($matches[1]), $subject);
		}
	}

	function __osFix($string) {
		return str_replace(array("\r\n", "\r"), "\n", $string);
	}
}

?>