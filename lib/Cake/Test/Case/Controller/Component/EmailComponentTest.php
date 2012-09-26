<?php
/**
 * EmailComponentTest file
 *
 * Series of tests for email component.
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
 * @package       Cake.Test.Case.Controller.Component
 * @since         CakePHP(tm) v 1.2.0.5347
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
App::uses('Controller', 'Controller');
App::uses('EmailComponent', 'Controller/Component');
App::uses('AbstractTransport', 'Network/Email');

/**
 * EmailTestComponent class
 *
 * @package       Cake.Test.Case.Controller.Component
 */
class EmailTestComponent extends EmailComponent {

/**
 * Convenience method for testing.
 *
 * @return string
 */
	public function strip($content, $message = false) {
		return parent::_strip($content, $message);
	}

}

/**
 * DebugCompTransport class
 *
 * @package       Cake.Test.Case.Controller.Component
 */
class DebugCompTransport extends AbstractTransport {

/**
 * Last email
 *
 * @var string
 */
	public static $lastEmail = null;

/**
 * Send mail
 *
 * @params object $email CakeEmail
 * @return boolean
 */
	public function send(CakeEmail $email) {
		$email->addHeaders(array('Date' => EmailComponentTest::$sentDate));
		$headers = $email->getHeaders(array_fill_keys(array('from', 'replyTo', 'readReceipt', 'returnPath', 'to', 'cc', 'bcc', 'subject'), true));
		$to = $headers['To'];
		$subject = $headers['Subject'];
		unset($headers['To'], $headers['Subject']);

		$message = implode("\n", $email->message());

		$last = '<pre>';
		$last .= sprintf("%s %s\n", 'To:', $to);
		$last .= sprintf("%s %s\n", 'From:', $headers['From']);
		$last .= sprintf("%s %s\n", 'Subject:', $subject);
		$last .= sprintf("%s\n\n%s", 'Header:', $this->_headersToString($headers, "\n"));
		$last .= sprintf("%s\n\n%s", 'Message:', $message);
		$last .= '</pre>';

		self::$lastEmail = $last;

		return true;
	}

}

/**
 * EmailTestController class
 *
 * @package       Cake.Test.Case.Controller.Component
 */
class EmailTestController extends Controller {

/**
 * name property
 *
 * @var string 'EmailTest'
 */
	public $name = 'EmailTest';

/**
 * uses property
 *
 * @var mixed null
 */
	public $uses = null;

/**
 * components property
 *
 * @var array
 */
	public $components = array('Session', 'EmailTest');

}

/**
 * EmailTest class
 *
 * @package       Cake.Test.Case.Controller.Component
 */
class EmailComponentTest extends CakeTestCase {

/**
 * Controller property
 *
 * @var EmailTestController
 */
	public $Controller;

/**
 * name property
 *
 * @var string 'Email'
 */
	public $name = 'Email';

/**
 * sentDate
 *
 * @var string
 */
	public static $sentDate = null;

/**
 * setUp method
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();

		Configure::write('App.encoding', 'UTF-8');

		$this->Controller = new EmailTestController();
		$this->Controller->Components->init($this->Controller);
		$this->Controller->EmailTest->initialize($this->Controller, array());

		self::$sentDate = date(DATE_RFC2822);

		App::build(array(
			'View' => array(CAKE . 'Test' . DS . 'test_app' . DS . 'View' . DS)
		));
	}

/**
 * testSendFormats method
 *
 * @return void
 */
	public function testSendFormats() {
		$this->Controller->EmailTest->to = 'postmaster@example.com';
		$this->Controller->EmailTest->from = 'noreply@example.com';
		$this->Controller->EmailTest->subject = 'Cake SMTP test';
		$this->Controller->EmailTest->replyTo = 'noreply@example.com';
		$this->Controller->EmailTest->template = null;
		$this->Controller->EmailTest->delivery = 'DebugComp';
		$this->Controller->EmailTest->messageId = false;

		$date = self::$sentDate;
		$message = <<<MSGBLOC
<pre>To: postmaster@example.com
From: noreply@example.com
Subject: Cake SMTP test
Header:

From: noreply@example.com
Reply-To: noreply@example.com
X-Mailer: CakePHP Email Component
Date: $date
MIME-Version: 1.0
Content-Type: {CONTENTTYPE}
Content-Transfer-Encoding: 8bitMessage:

This is the body of the message

</pre>
MSGBLOC;

		$this->Controller->EmailTest->sendAs = 'text';
		$expect = str_replace('{CONTENTTYPE}', 'text/plain; charset=UTF-8', $message);
		$this->assertTrue($this->Controller->EmailTest->send('This is the body of the message'));
		$this->assertTextEquals(DebugCompTransport::$lastEmail, $expect);

		$this->Controller->EmailTest->sendAs = 'html';
		$expect = str_replace('{CONTENTTYPE}', 'text/html; charset=UTF-8', $message);
		$this->assertTrue($this->Controller->EmailTest->send('This is the body of the message'));
		$this->assertTextEquals(DebugCompTransport::$lastEmail, $expect);
	}

/**
 * testTemplates method
 *
 * @return void
 */
	public function testTemplates() {
		ClassRegistry::flush();

		$this->Controller->EmailTest->to = 'postmaster@example.com';
		$this->Controller->EmailTest->from = 'noreply@example.com';
		$this->Controller->EmailTest->subject = 'Cake SMTP test';
		$this->Controller->EmailTest->replyTo = 'noreply@example.com';

		$this->Controller->EmailTest->delivery = 'DebugComp';
		$this->Controller->EmailTest->messageId = false;

		$date = self::$sentDate;
		$header = <<<HEADBLOC
To: postmaster@example.com
From: noreply@example.com
Subject: Cake SMTP test
Header:

From: noreply@example.com
Reply-To: noreply@example.com
X-Mailer: CakePHP Email Component
Date: $date
MIME-Version: 1.0
Content-Type: {CONTENTTYPE}
Content-Transfer-Encoding: 8bitMessage:


HEADBLOC;

		$this->Controller->EmailTest->layout = 'default';
		$this->Controller->EmailTest->template = 'default';
		$this->Controller->set('title_for_layout', 'Email Test');

		$text = <<<TEXTBLOC

This is the body of the message

This email was sent using the CakePHP Framework, http://cakephp.org.
TEXTBLOC;

		$html = <<<HTMLBLOC
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01//EN">

<html>
<head>
	<title>Email Test</title>
</head>

<body>
	<p> This is the body of the message</p><p> </p>
	<p>This email was sent using the <a href="http://cakephp.org">CakePHP Framework</a></p>
</body>
</html>
HTMLBLOC;

		$this->Controller->EmailTest->sendAs = 'text';
		$expect = '<pre>' . str_replace('{CONTENTTYPE}', 'text/plain; charset=UTF-8', $header) . $text . "\n" . '</pre>';
		$this->assertTrue($this->Controller->EmailTest->send('This is the body of the message'));
		$this->assertTextEquals(DebugCompTransport::$lastEmail, $expect);

		$this->Controller->EmailTest->sendAs = 'html';
		$expect = '<pre>' . str_replace('{CONTENTTYPE}', 'text/html; charset=UTF-8', $header) . $html . "\n" . '</pre>';
		$this->assertTrue($this->Controller->EmailTest->send('This is the body of the message'));
		$this->assertTextEquals(DebugCompTransport::$lastEmail, $expect);

		$this->Controller->EmailTest->sendAs = 'both';
		$expect = str_replace('{CONTENTTYPE}', 'multipart/mixed; boundary="{boundary}"', $header);
		$expect .= "--{boundary}\n" .
			'Content-Type: multipart/alternative; boundary="alt-{boundary}"' . "\n\n" .
			'--alt-{boundary}' . "\n" .
			'Content-Type: text/plain; charset=UTF-8' . "\n" .
			'Content-Transfer-Encoding: 8bit' . "\n\n" .
			$text .
			"\n\n" .
			'--alt-{boundary}' . "\n" .
			'Content-Type: text/html; charset=UTF-8' . "\n" .
			'Content-Transfer-Encoding: 8bit' . "\n\n" .
			$html .
			"\n\n" .
			'--alt-{boundary}--' . "\n\n\n" .
			'--{boundary}--' . "\n";

		$expect = '<pre>' . $expect . '</pre>';

		$this->assertTrue($this->Controller->EmailTest->send('This is the body of the message'));
		$this->assertTextEquals(
			$expect,
			preg_replace('/[a-z0-9]{32}/i', '{boundary}', DebugCompTransport::$lastEmail)
		);

		$html = <<<HTMLBLOC
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01//EN">

<html>
<head>
	<title>Email Test</title>
</head>

<body>
	<p> This is the body of the message</p><p> </p>
	<p>This email was sent using the CakePHP Framework</p>
</body>
</html>

HTMLBLOC;

		$this->Controller->EmailTest->sendAs = 'html';
		$expect = '<pre>' . str_replace('{CONTENTTYPE}', 'text/html; charset=UTF-8', $header) . $html . '</pre>';
		$this->assertTrue($this->Controller->EmailTest->send('This is the body of the message', 'default', 'thin'));
		$this->assertTextEquals(DebugCompTransport::$lastEmail, $expect);
	}

/**
 * test that elements used in email templates get helpers.
 *
 * @return void
 */
	public function testTemplateNestedElements() {
		$this->Controller->EmailTest->to = 'postmaster@example.com';
		$this->Controller->EmailTest->from = 'noreply@example.com';
		$this->Controller->EmailTest->subject = 'Cake SMTP test';
		$this->Controller->EmailTest->replyTo = 'noreply@example.com';

		$this->Controller->EmailTest->delivery = 'DebugComp';
		$this->Controller->EmailTest->messageId = false;
		$this->Controller->EmailTest->layout = 'default';
		$this->Controller->EmailTest->template = 'nested_element';
		$this->Controller->EmailTest->sendAs = 'html';
		$this->Controller->helpers = array('Html');

		$this->Controller->EmailTest->send();
		$result = DebugCompTransport::$lastEmail;
		$this->assertRegExp('/Test/', $result);
		$this->assertRegExp('/http\:\/\/example\.com/', $result);
	}

/**
 * testSendDebug method
 *
 * @return void
 */
	public function testSendDebug() {
		$this->Controller->EmailTest->to = 'postmaster@example.com';
		$this->Controller->EmailTest->from = 'noreply@example.com';
		$this->Controller->EmailTest->cc = 'cc@example.com';
		$this->Controller->EmailTest->bcc = 'bcc@example.com';
		$this->Controller->EmailTest->subject = 'Cake Debug Test';
		$this->Controller->EmailTest->replyTo = 'noreply@example.com';
		$this->Controller->EmailTest->template = null;

		$this->Controller->EmailTest->delivery = 'DebugComp';
		$this->assertTrue($this->Controller->EmailTest->send('This is the body of the message'));
		$result = DebugCompTransport::$lastEmail;

		$this->assertRegExp('/To: postmaster@example.com\n/', $result);
		$this->assertRegExp('/Subject: Cake Debug Test\n/', $result);
		$this->assertRegExp('/Reply-To: noreply@example.com\n/', $result);
		$this->assertRegExp('/From: noreply@example.com\n/', $result);
		$this->assertRegExp('/Cc: cc@example.com\n/', $result);
		$this->assertRegExp('/Bcc: bcc@example.com\n/', $result);
		$this->assertRegExp('/Date: ' . preg_quote(self::$sentDate) . '\n/', $result);
		$this->assertRegExp('/X-Mailer: CakePHP Email Component\n/', $result);
		$this->assertRegExp('/Content-Type: text\/plain; charset=UTF-8\n/', $result);
		$this->assertRegExp('/Content-Transfer-Encoding: 8bitMessage:\n/', $result);
		$this->assertRegExp('/This is the body of the message/', $result);
	}

/**
 * test send with delivery = debug and not using sessions.
 *
 * @return void
 */
	public function testSendDebugWithNoSessions() {
		$session = $this->Controller->Session;
		unset($this->Controller->Session);
		$this->Controller->EmailTest->to = 'postmaster@example.com';
		$this->Controller->EmailTest->from = 'noreply@example.com';
		$this->Controller->EmailTest->subject = 'Cake Debug Test';
		$this->Controller->EmailTest->replyTo = 'noreply@example.com';
		$this->Controller->EmailTest->template = null;

		$this->Controller->EmailTest->delivery = 'DebugComp';
		$this->Controller->EmailTest->send('This is the body of the message');
		$result = DebugCompTransport::$lastEmail;

		$this->assertRegExp('/To: postmaster@example.com\n/', $result);
		$this->assertRegExp('/Subject: Cake Debug Test\n/', $result);
		$this->assertRegExp('/Reply-To: noreply@example.com\n/', $result);
		$this->assertRegExp('/From: noreply@example.com\n/', $result);
		$this->assertRegExp('/Date: ' . preg_quote(self::$sentDate) . '\n/', $result);
		$this->assertRegExp('/X-Mailer: CakePHP Email Component\n/', $result);
		$this->assertRegExp('/Content-Type: text\/plain; charset=UTF-8\n/', $result);
		$this->assertRegExp('/Content-Transfer-Encoding: 8bitMessage:\n/', $result);
		$this->assertRegExp('/This is the body of the message/', $result);
		$this->Controller->Session = $session;
	}

/**
 * testMessageRetrievalWithoutTemplate method
 *
 * @return void
 */
	public function testMessageRetrievalWithoutTemplate() {
		App::build(array(
			'View' => array(CAKE . 'Test' . DS . 'test_app' . DS . 'View' . DS)
		));

		$this->Controller->EmailTest->to = 'postmaster@example.com';
		$this->Controller->EmailTest->from = 'noreply@example.com';
		$this->Controller->EmailTest->subject = 'Cake Debug Test';
		$this->Controller->EmailTest->replyTo = 'noreply@example.com';
		$this->Controller->EmailTest->layout = 'default';
		$this->Controller->EmailTest->template = null;

		$this->Controller->EmailTest->delivery = 'DebugComp';

		$text = $html = "This is the body of the message\n";

		$this->Controller->EmailTest->sendAs = 'both';
		$this->assertTrue($this->Controller->EmailTest->send('This is the body of the message'));
		$this->assertTextEquals($this->Controller->EmailTest->textMessage, $text);
		$this->assertTextEquals($this->Controller->EmailTest->htmlMessage, $html);

		$this->Controller->EmailTest->sendAs = 'text';
		$this->assertTrue($this->Controller->EmailTest->send('This is the body of the message'));
		$this->assertTextEquals($this->Controller->EmailTest->textMessage, $text);
		$this->assertNull($this->Controller->EmailTest->htmlMessage);

		$this->Controller->EmailTest->sendAs = 'html';
		$this->assertTrue($this->Controller->EmailTest->send('This is the body of the message'));
		$this->assertNull($this->Controller->EmailTest->textMessage);
		$this->assertTextEquals($this->Controller->EmailTest->htmlMessage, $html);
	}

/**
 * testMessageRetrievalWithTemplate method
 *
 * @return void
 */
	public function testMessageRetrievalWithTemplate() {
		App::build(array(
			'View' => array(CAKE . 'Test' . DS . 'test_app' . DS . 'View' . DS)
		));

		$this->Controller->set('value', 22091985);
		$this->Controller->set('title_for_layout', 'EmailTest');

		$this->Controller->EmailTest->to = 'postmaster@example.com';
		$this->Controller->EmailTest->from = 'noreply@example.com';
		$this->Controller->EmailTest->subject = 'Cake Debug Test';
		$this->Controller->EmailTest->replyTo = 'noreply@example.com';
		$this->Controller->EmailTest->layout = 'default';
		$this->Controller->EmailTest->template = 'custom';

		$this->Controller->EmailTest->delivery = 'DebugComp';

		$text = <<<TEXTBLOC

Here is your value: 22091985
This email was sent using the CakePHP Framework, http://cakephp.org.
TEXTBLOC;

		$html = <<<HTMLBLOC
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01//EN">

<html>
<head>
	<title>EmailTest</title>
</head>

<body>
	<p>Here is your value: <b>22091985</b></p>

	<p>This email was sent using the <a href="http://cakephp.org">CakePHP Framework</a></p>
</body>
</html>
HTMLBLOC;

		$this->Controller->EmailTest->sendAs = 'both';
		$this->assertTrue($this->Controller->EmailTest->send());
		$this->assertTextEquals($this->Controller->EmailTest->textMessage, $text);
		$this->assertTextEquals($this->Controller->EmailTest->htmlMessage, $html);

		$this->Controller->EmailTest->sendAs = 'text';
		$this->assertTrue($this->Controller->EmailTest->send());
		$this->assertTextEquals($this->Controller->EmailTest->textMessage, $text);
		$this->assertNull($this->Controller->EmailTest->htmlMessage);

		$this->Controller->EmailTest->sendAs = 'html';
		$this->assertTrue($this->Controller->EmailTest->send());
		$this->assertNull($this->Controller->EmailTest->textMessage);
		$this->assertTextEquals($this->Controller->EmailTest->htmlMessage, $html);
	}

/**
 * testMessageRetrievalWithHelper method
 *
 * @return void
 */
	public function testMessageRetrievalWithHelper() {
		App::build(array(
			'View' => array(CAKE . 'Test' . DS . 'test_app' . DS . 'View' . DS)
		));

		$timestamp = time();
		$this->Controller->set('time', $timestamp);
		$this->Controller->set('title_for_layout', 'EmailTest');
		$this->Controller->helpers = array('Time');

		$this->Controller->EmailTest->to = 'postmaster@example.com';
		$this->Controller->EmailTest->from = 'noreply@example.com';
		$this->Controller->EmailTest->subject = 'Cake Debug Test';
		$this->Controller->EmailTest->replyTo = 'noreply@example.com';
		$this->Controller->EmailTest->layout = 'default';
		$this->Controller->EmailTest->template = 'custom_helper';
		$this->Controller->EmailTest->sendAs = 'text';
		$this->Controller->EmailTest->delivery = 'DebugComp';

		$this->assertTrue($this->Controller->EmailTest->send());
		$this->assertTrue((bool)strpos($this->Controller->EmailTest->textMessage, 'Right now: ' . date('Y-m-d\TH:i:s\Z', $timestamp)));
	}

/**
 * testContentArray method
 *
 * @return void
 */
	public function testSendContentArray() {
		$this->Controller->EmailTest->to = 'postmaster@example.com';
		$this->Controller->EmailTest->from = 'noreply@example.com';
		$this->Controller->EmailTest->subject = 'Cake Debug Test';
		$this->Controller->EmailTest->replyTo = 'noreply@example.com';
		$this->Controller->EmailTest->template = null;
		$this->Controller->EmailTest->delivery = 'DebugComp';

		$content = array('First line', 'Second line', 'Third line');
		$this->assertTrue($this->Controller->EmailTest->send($content));
		$result = DebugCompTransport::$lastEmail;

		$this->assertRegExp('/To: postmaster@example.com\n/', $result);
		$this->assertRegExp('/Subject: Cake Debug Test\n/', $result);
		$this->assertRegExp('/Reply-To: noreply@example.com\n/', $result);
		$this->assertRegExp('/From: noreply@example.com\n/', $result);
		$this->assertRegExp('/X-Mailer: CakePHP Email Component\n/', $result);
		$this->assertRegExp('/Content-Type: text\/plain; charset=UTF-8\n/', $result);
		$this->assertRegExp('/Content-Transfer-Encoding: 8bitMessage:\n/', $result);
		$this->assertRegExp('/First line\n/', $result);
		$this->assertRegExp('/Second line\n/', $result);
		$this->assertRegExp('/Third line\n/', $result);
	}

/**
 * test setting a custom date.
 *
 * @return void
 */
	public function testDateProperty() {
		$this->Controller->EmailTest->to = 'postmaster@example.com';
		$this->Controller->EmailTest->from = 'noreply@example.com';
		$this->Controller->EmailTest->subject = 'Cake Debug Test';
		$this->Controller->EmailTest->date = self::$sentDate = 'Today!';
		$this->Controller->EmailTest->template = null;
		$this->Controller->EmailTest->delivery = 'DebugComp';

		$this->assertTrue($this->Controller->EmailTest->send('test message'));
		$result = DebugCompTransport::$lastEmail;
		$this->assertRegExp('/Date: Today!\n/', $result);
	}

/**
 * testContentStripping method
 *
 * @return void
 */
	public function testContentStripping() {
		$content = "Previous content\n--alt-\nContent-TypeContent-Type:: text/html; charsetcharset==utf-8\nContent-Transfer-Encoding: 8bit";
		$content .= "\n\n<p>My own html content</p>";

		$result = $this->Controller->EmailTest->strip($content, true);
		$expected = "Previous content\n--alt-\n text/html; utf-8\n 8bit\n\n<p>My own html content</p>";
		$this->assertEquals($expected, $result);

		$content = '<p>Some HTML content with an <a href="mailto:test@example.com">email link</a>';
		$result  = $this->Controller->EmailTest->strip($content, true);
		$expected = $content;
		$this->assertEquals($expected, $result);

		$content  = '<p>Some HTML content with an ';
		$content .= '<a href="mailto:test@example.com,test2@example.com">email link</a>';
		$result  = $this->Controller->EmailTest->strip($content, true);
		$expected = $content;
		$this->assertEquals($expected, $result);
	}

/**
 * test that the _encode() will set mb_internal_encoding.
 *
 * @return void
 */
	public function testEncodeSettingInternalCharset() {
		$this->skipIf(!function_exists('mb_internal_encoding'), 'Missing mb_* functions, cannot run test.');

		$restore = mb_internal_encoding();
		mb_internal_encoding('ISO-8859-1');

		$this->Controller->charset = 'UTF-8';
		$this->Controller->EmailTest->to = 'postmaster@example.com';
		$this->Controller->EmailTest->from = 'noreply@example.com';
		$this->Controller->EmailTest->subject = 'هذه رسالة بعنوان طويل مرسل للمستلم';
		$this->Controller->EmailTest->replyTo = 'noreply@example.com';
		$this->Controller->EmailTest->template = null;
		$this->Controller->EmailTest->delivery = 'DebugComp';

		$this->Controller->EmailTest->sendAs = 'text';
		$this->assertTrue($this->Controller->EmailTest->send('This is the body of the message'));

		$subject = '=?UTF-8?B?2YfYsNmHINix2LPYp9mE2Kkg2KjYudmG2YjYp9mGINi32YjZitmEINmF2LE=?=' . "\r\n" . ' =?UTF-8?B?2LPZhCDZhNmE2YXYs9iq2YTZhQ==?=';

		preg_match('/Subject: (.*)Header:/s', DebugCompTransport::$lastEmail, $matches);
		$this->assertEquals(trim($matches[1]), $subject);

		$result = mb_internal_encoding();
		$this->assertEquals('ISO-8859-1', $result);

		mb_internal_encoding($restore);
	}

/**
 * testMultibyte method
 *
 * @return void
 */
	public function testMultibyte() {
		$this->Controller->charset = 'UTF-8';
		$this->Controller->EmailTest->to = 'postmaster@example.com';
		$this->Controller->EmailTest->from = 'noreply@example.com';
		$this->Controller->EmailTest->subject = 'هذه رسالة بعنوان طويل مرسل للمستلم';
		$this->Controller->EmailTest->replyTo = 'noreply@example.com';
		$this->Controller->EmailTest->template = null;
		$this->Controller->EmailTest->delivery = 'DebugComp';

		$subject = '=?UTF-8?B?2YfYsNmHINix2LPYp9mE2Kkg2KjYudmG2YjYp9mGINi32YjZitmEINmF2LE=?=' . "\r\n" . ' =?UTF-8?B?2LPZhCDZhNmE2YXYs9iq2YTZhQ==?=';

		$this->Controller->EmailTest->sendAs = 'text';
		$this->assertTrue($this->Controller->EmailTest->send('This is the body of the message'));
		preg_match('/Subject: (.*)Header:/s', DebugCompTransport::$lastEmail, $matches);
		$this->assertEquals(trim($matches[1]), $subject);

		$this->Controller->EmailTest->sendAs = 'html';
		$this->assertTrue($this->Controller->EmailTest->send('This is the body of the message'));
		preg_match('/Subject: (.*)Header:/s', DebugCompTransport::$lastEmail, $matches);
		$this->assertEquals(trim($matches[1]), $subject);

		$this->Controller->EmailTest->sendAs = 'both';
		$this->assertTrue($this->Controller->EmailTest->send('This is the body of the message'));
		preg_match('/Subject: (.*)Header:/s', DebugCompTransport::$lastEmail, $matches);
		$this->assertEquals(trim($matches[1]), $subject);
	}

/**
 * undocumented function
 *
 * @return void
 */
	public function testSendWithAttachments() {
		$this->Controller->EmailTest->to = 'postmaster@example.com';
		$this->Controller->EmailTest->from = 'noreply@example.com';
		$this->Controller->EmailTest->subject = 'Attachment Test';
		$this->Controller->EmailTest->replyTo = 'noreply@example.com';
		$this->Controller->EmailTest->template = null;
		$this->Controller->EmailTest->delivery = 'DebugComp';
		$this->Controller->EmailTest->attachments = array(
			__FILE__,
			'some-name.php' => __FILE__
		);
		$body = '<p>This is the body of the message</p>';

		$this->Controller->EmailTest->sendAs = 'text';
		$this->assertTrue($this->Controller->EmailTest->send($body));
		$msg = DebugCompTransport::$lastEmail;
		$this->assertRegExp('/' . preg_quote('Content-Disposition: attachment; filename="EmailComponentTest.php"') . '/', $msg);
		$this->assertRegExp('/' . preg_quote('Content-Disposition: attachment; filename="some-name.php"') . '/', $msg);
	}

/**
 * testSendAsIsNotIgnoredIfAttachmentsPresent method
 *
 * @return void
 */
	public function testSendAsIsNotIgnoredIfAttachmentsPresent() {
		$this->Controller->EmailTest->to = 'postmaster@example.com';
		$this->Controller->EmailTest->from = 'noreply@example.com';
		$this->Controller->EmailTest->subject = 'Attachment Test';
		$this->Controller->EmailTest->replyTo = 'noreply@example.com';
		$this->Controller->EmailTest->template = null;
		$this->Controller->EmailTest->delivery = 'DebugComp';
		$this->Controller->EmailTest->attachments = array(__FILE__);
		$body = '<p>This is the body of the message</p>';

		$this->Controller->EmailTest->sendAs = 'html';
		$this->assertTrue($this->Controller->EmailTest->send($body));
		$msg = DebugCompTransport::$lastEmail;
		$this->assertNotRegExp('/text\/plain/', $msg);
		$this->assertRegExp('/text\/html/', $msg);

		$this->Controller->EmailTest->sendAs = 'text';
		$this->assertTrue($this->Controller->EmailTest->send($body));
		$msg = DebugCompTransport::$lastEmail;
		$this->assertRegExp('/text\/plain/', $msg);
		$this->assertNotRegExp('/text\/html/', $msg);

		$this->Controller->EmailTest->sendAs = 'both';
		$this->assertTrue($this->Controller->EmailTest->send($body));
		$msg = DebugCompTransport::$lastEmail;

		$this->assertRegExp('/text\/plain/', $msg);
		$this->assertRegExp('/text\/html/', $msg);
		$this->assertRegExp('/multipart\/alternative/', $msg);
	}

/**
 * testNoDoubleNewlinesInHeaders function
 *
 * @return void
 */
	public function testNoDoubleNewlinesInHeaders() {
		$this->Controller->EmailTest->to = 'postmaster@example.com';
		$this->Controller->EmailTest->from = 'noreply@example.com';
		$this->Controller->EmailTest->subject = 'Attachment Test';
		$this->Controller->EmailTest->replyTo = 'noreply@example.com';
		$this->Controller->EmailTest->template = null;
		$this->Controller->EmailTest->delivery = 'DebugComp';
		$body = '<p>This is the body of the message</p>';

		$this->Controller->EmailTest->sendAs = 'both';
		$this->assertTrue($this->Controller->EmailTest->send($body));
		$msg = DebugCompTransport::$lastEmail;

		$this->assertNotRegExp('/\n\nContent-Transfer-Encoding/', $msg);
		$this->assertRegExp('/\nContent-Transfer-Encoding/', $msg);
	}

/**
 * testReset method
 *
 * @return void
 */
	public function testReset() {
		$this->Controller->EmailTest->template = 'default';
		$this->Controller->EmailTest->to = 'test.recipient@example.com';
		$this->Controller->EmailTest->from = 'test.sender@example.com';
		$this->Controller->EmailTest->replyTo = 'test.replyto@example.com';
		$this->Controller->EmailTest->return = 'test.return@example.com';
		$this->Controller->EmailTest->cc = array('cc1@example.com', 'cc2@example.com');
		$this->Controller->EmailTest->bcc = array('bcc1@example.com', 'bcc2@example.com');
		$this->Controller->EmailTest->date = 'Today!';
		$this->Controller->EmailTest->subject = 'Test subject';
		$this->Controller->EmailTest->additionalParams = 'X-additional-header';
		$this->Controller->EmailTest->delivery = 'smtp';
		$this->Controller->EmailTest->smtpOptions['host'] = 'blah';
		$this->Controller->EmailTest->smtpOptions['timeout'] = 0.2;
		$this->Controller->EmailTest->attachments = array('attachment1', 'attachment2');
		$this->Controller->EmailTest->textMessage = 'This is the body of the message';
		$this->Controller->EmailTest->htmlMessage = 'This is the body of the message';
		$this->Controller->EmailTest->messageId = false;

		try {
			$this->Controller->EmailTest->send('Should not work');
			$this->fail('No exception');
		} catch (SocketException $e) {
			$this->assertTrue(true, 'SocketException raised');
		}

		$this->Controller->EmailTest->reset();

		$this->assertNull($this->Controller->EmailTest->template);
		$this->assertSame($this->Controller->EmailTest->to, array());
		$this->assertNull($this->Controller->EmailTest->from);
		$this->assertNull($this->Controller->EmailTest->replyTo);
		$this->assertNull($this->Controller->EmailTest->return);
		$this->assertSame($this->Controller->EmailTest->cc, array());
		$this->assertSame($this->Controller->EmailTest->bcc, array());
		$this->assertNull($this->Controller->EmailTest->date);
		$this->assertNull($this->Controller->EmailTest->subject);
		$this->assertNull($this->Controller->EmailTest->additionalParams);
		$this->assertNull($this->Controller->EmailTest->smtpError);
		$this->assertSame($this->Controller->EmailTest->attachments, array());
		$this->assertNull($this->Controller->EmailTest->textMessage);
		$this->assertTrue($this->Controller->EmailTest->messageId);
		$this->assertEquals('mail', $this->Controller->EmailTest->delivery);
	}

	public function testPluginCustomViewClass() {
		App::build(array(
			'Plugin' => array(CAKE . 'Test' . DS . 'test_app' . DS . 'Plugin' . DS),
			'View' => array(CAKE . 'Test' . DS . 'test_app' . DS . 'View' . DS)
		));

		$this->Controller->view = 'TestPlugin.Email';

		$this->Controller->EmailTest->to = 'postmaster@example.com';
		$this->Controller->EmailTest->from = 'noreply@example.com';
		$this->Controller->EmailTest->subject = 'CustomViewClass test';
		$this->Controller->EmailTest->delivery = 'DebugComp';
		$body = 'Body of message';

		$this->assertTrue($this->Controller->EmailTest->send($body));
		$result = DebugCompTransport::$lastEmail;

		$this->assertRegExp('/Body of message/', $result);
	}

/**
 * testStartup method
 *
 * @return void
 */
	public function testStartup() {
		$this->assertNull($this->Controller->EmailTest->startup($this->Controller));
	}

/**
 * testMessageId method
 *
 * @return void
 */
	public function testMessageId() {
		$this->Controller->EmailTest->to = 'postmaster@example.com';
		$this->Controller->EmailTest->from = 'noreply@example.com';
		$this->Controller->EmailTest->subject = 'Cake Debug Test';
		$this->Controller->EmailTest->replyTo = 'noreply@example.com';
		$this->Controller->EmailTest->template = null;

		$this->Controller->EmailTest->delivery = 'DebugComp';
		$this->assertTrue($this->Controller->EmailTest->send('This is the body of the message'));
		$result = DebugCompTransport::$lastEmail;

		$host = env('HTTP_HOST') ? env('HTTP_HOST') : php_uname('n');
		$this->assertRegExp('/Message-ID: \<[a-f0-9]{8}[a-f0-9]{4}[a-f0-9]{4}[a-f0-9]{4}[a-f0-9]{12}@' . $host . '\>\n/', $result);

		$this->Controller->EmailTest->messageId = '<22091985.998877@example.com>';

		$this->assertTrue($this->Controller->EmailTest->send('This is the body of the message'));
		$result = DebugCompTransport::$lastEmail;

		$this->assertRegExp('/Message-ID: <22091985.998877@example.com>\n/', $result);

		$this->Controller->EmailTest->messageId = false;

		$this->assertTrue($this->Controller->EmailTest->send('This is the body of the message'));
		$result = DebugCompTransport::$lastEmail;

		$this->assertNotRegExp('/Message-ID:/', $result);
	}

/**
 * Make sure from/to are not double encoded when UTF-8 is present
 */
	public function testEncodingFrom() {
		$this->Controller->EmailTest->to = 'Teßt <test@example.com>';
		$this->Controller->EmailTest->from = 'Teßt <test@example.com>';
		$this->Controller->EmailTest->subject = 'Cake Debug Test';
		$this->Controller->EmailTest->replyTo = 'noreply@example.com';
		$this->Controller->EmailTest->template = null;

		$this->Controller->EmailTest->delivery = 'DebugComp';
		$this->assertTrue($this->Controller->EmailTest->send('This is the body of the message'));
		$result = DebugCompTransport::$lastEmail;

		$this->assertContains('From: =?UTF-8?B?VGXDn3Qg?= <test@example.com>', $result);
		$this->assertContains('To: =?UTF-8?B?VGXDn3Qg?= <test@example.com>', $result);
	}

}
