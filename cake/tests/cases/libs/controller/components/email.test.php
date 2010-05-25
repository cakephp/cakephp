<?php
/**
 * EmailComponentTest file
 *
 * Series of tests for email component.
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) Tests <http://book.cakephp.org/view/1196/Testing>
 * Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 *  Licensed under The Open Group Test Suite License
 *  Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/view/1196/Testing CakePHP(tm) Tests
 * @package       cake
 * @subpackage    cake.cake.tests.cases.libs.controller.components
 * @since         CakePHP(tm) v 1.2.0.5347
 * @license       http://www.opensource.org/licenses/opengroup.php The Open Group Test Suite License
 */
App::import('Component', 'Email');

/**
 * EmailTestComponent class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs.controller.components
 */
class EmailTestComponent extends EmailComponent {

/**
 * smtpSend method override for testing
 *
 * @access public
 * @return mixed
 */
	function smtpSend($data, $code = '250') {
		return parent::_smtpSend($data, $code);
	}

/**
 * Convenience setter method for testing.
 *
 * @access public
 * @return void
 */
	function setConnectionSocket(&$socket) {
		$this->__smtpConnection = $socket;
	}

/**
 * Convenience getter method for testing.
 *
 * @access public
 * @return mixed
 */
	function getConnectionSocket() {
		return $this->__smtpConnection;
	}

/**
 * Convenience setter for testing.
 *
 * @access public
 * @return void
 */
	function setHeaders($headers) {
		$this->__header += $headers;
	}

/**
 * Convenience getter for testing.
 *
 * @access public
 * @return array
 */
	function getHeaders() {
		return $this->__header;
	}

/**
 * Convenience setter for testing.
 *
 * @access public
 * @return void
 */
	function setBoundary() {
		$this->__createBoundary();
	}

/**
 * Convenience getter for testing.
 *
 * @access public
 * @return string
 */
	function getBoundary() {
		return $this->__boundary;
	}

/**
 * Convenience getter for testing.
 *
 * @access public
 * @return string
 */
	function getMessage() {
		return $this->__message;
	}

/**
 * Convenience method for testing.
 *
 * @access public
 * @return string
 */
	function strip($content, $message = false) {
		return parent::_strip($content, $message);
	}
}

/**
 * EmailTestController class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs.controller.components
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
	var $components = array('Session', 'EmailTest');

/**
 * pageTitle property
 *
 * @var string
 * @access public
 */
	var $pageTitle = 'EmailTest';
}

/**
 * EmailTest class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs.controller.components
 */
class EmailComponentTest extends CakeTestCase {

/**
 * Controller property
 *
 * @var EmailTestController
 * @access public
 */
	var $Controller;

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
		$this->_appEncoding = Configure::read('App.encoding');
		Configure::write('App.encoding', 'UTF-8');

		$this->Controller =& new EmailTestController();

		restore_error_handler();
		@$this->Controller->Component->init($this->Controller);
		set_error_handler('simpleTestErrorHandler');

		$this->Controller->EmailTest->initialize($this->Controller, array());
		ClassRegistry::addObject('view', new View($this->Controller));

		App::build(array(
			'views' => array(TEST_CAKE_CORE_INCLUDE_PATH . 'tests' . DS . 'test_app' . DS . 'views'. DS)
		));
	}

/**
 * tearDown method
 *
 * @access public
 * @return void
 */
	function tearDown() {
		Configure::write('App.encoding', $this->_appEncoding);
		App::build();
		$this->Controller->Session->delete('Message');
		restore_error_handler();
		ClassRegistry::flush();
	}

/**
 * osFix method
 *
 * @param string $string
 * @access private
 * @return string
 */
	function __osFix($string) {
		return str_replace(array("\r\n", "\r"), "\n", $string);
	}

/**
 * testBadSmtpSend method
 *
 * @access public
 * @return void
 */
	function testBadSmtpSend() {
		$this->Controller->EmailTest->smtpOptions['host'] = 'blah';
		$this->Controller->EmailTest->delivery = 'smtp';
		$this->assertFalse($this->Controller->EmailTest->send('Should not work'));
	}

/**
 * testSmtpSend method
 *
 * @access public
 * @return void
 */
	function testSmtpSend() {
		if (!$this->skipIf(!@fsockopen('localhost', 25), '%s No SMTP server running on localhost')) {
			return;
		}

		$this->Controller->EmailTest->to = 'postmaster@localhost';
		$this->Controller->EmailTest->from = 'noreply@example.com';
		$this->Controller->EmailTest->subject = 'Cake SMTP test';
		$this->Controller->EmailTest->replyTo = 'noreply@example.com';
		$this->Controller->EmailTest->template = null;

		$this->Controller->EmailTest->delivery = 'smtp';
		$this->assertTrue($this->Controller->EmailTest->send('This is the body of the message'));

		$this->Controller->EmailTest->_debug = true;
		$this->Controller->EmailTest->sendAs = 'text';
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
		$this->assertTrue($this->Controller->EmailTest->send('This is the body of the message'));
		$this->assertEqual($this->Controller->Session->read('Message.email.message'), $this->__osFix($expect));
	}

/**
 * testSmtpEhlo method
 *
 * @access public
 * @return void
 */
	function testSmtpEhlo() {
		if (!$this->skipIf(!@fsockopen('localhost', 25), '%s No SMTP server running on localhost')) {
			return;
		}

		$connection =& new CakeSocket(array('protocol'=>'smtp', 'host' => 'localhost', 'port' => 25));
		$this->Controller->EmailTest->setConnectionSocket($connection);
		$this->assertTrue($connection->connect());
		$this->assertTrue($this->Controller->EmailTest->smtpSend(null, '220') !== false);
		$this->skipIf($this->Controller->EmailTest->smtpSend('EHLO locahost', '250') === false, '%s do not support EHLO.');
		$connection->disconnect();

		$this->Controller->EmailTest->to = 'postmaster@localhost';
		$this->Controller->EmailTest->from = 'noreply@example.com';
		$this->Controller->EmailTest->subject = 'Cake SMTP test';
		$this->Controller->EmailTest->replyTo = 'noreply@example.com';
		$this->Controller->EmailTest->template = null;

		$this->Controller->EmailTest->delivery = 'smtp';
		$this->assertTrue($this->Controller->EmailTest->send('This is the body of the message'));

		$this->Controller->EmailTest->_debug = true;
		$this->Controller->EmailTest->sendAs = 'text';
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
		$this->assertTrue($this->Controller->EmailTest->send('This is the body of the message'));
		$this->assertEqual($this->Controller->Session->read('Message.email.message'), $this->__osFix($expect));
	}

/**
 * testSmtpSendMultipleTo method
 *
 * @access public
 * @return void
 */
	function testSmtpSendMultipleTo() {
		if (!$this->skipIf(!@fsockopen('localhost', 25), '%s No SMTP server running on localhost')) {
			return;
		}
		$this->Controller->EmailTest->reset();
		$this->Controller->EmailTest->to = array('postmaster@localhost', 'root@localhost');
		$this->Controller->EmailTest->from = 'noreply@example.com';
		$this->Controller->EmailTest->subject = 'Cake SMTP multiple To test';
		$this->Controller->EmailTest->replyTo = 'noreply@example.com';
		$this->Controller->EmailTest->template = null;

		$this->Controller->EmailTest->delivery = 'smtp';
		$this->assertTrue($this->Controller->EmailTest->send('This is the body of the message'));

		$this->Controller->EmailTest->_debug = true;
		$this->Controller->EmailTest->sendAs = 'text';
		$expect = <<<TEMPDOC
<pre>Host: localhost
Port: 25
Timeout: 30
To: postmaster@localhost, root@localhost
From: noreply@example.com
Subject: Cake SMTP multiple To test
Header:

To: postmaster@localhost, root@localhost
From: noreply@example.com
Reply-To: noreply@example.com
Subject: Cake SMTP multiple To test
X-Mailer: CakePHP Email Component
Content-Type: text/plain; charset=UTF-8
Content-Transfer-Encoding: 7bitParameters:

Message:

This is the body of the message

</pre>
TEMPDOC;
		$this->assertTrue($this->Controller->EmailTest->send('This is the body of the message'));
		$this->assertEqual($this->Controller->Session->read('Message.email.message'), $this->__osFix($expect));
	}

/**
 * testAuthenticatedSmtpSend method
 *
 * @access public
 * @return void
 */
	function testAuthenticatedSmtpSend() {
		$this->skipIf(!@fsockopen('localhost', 25), '%s No SMTP server running on localhost');

		$this->Controller->EmailTest->to = 'postmaster@localhost';
		$this->Controller->EmailTest->from = 'noreply@example.com';
		$this->Controller->EmailTest->subject = 'Cake SMTP test';
		$this->Controller->EmailTest->replyTo = 'noreply@example.com';
		$this->Controller->EmailTest->template = null;
		$this->Controller->EmailTest->smtpOptions['username'] = 'test';
		$this->Controller->EmailTest->smtpOptions['password'] = 'testing';

		$this->Controller->EmailTest->delivery = 'smtp';
		$result = $this->Controller->EmailTest->send('This is the body of the message');
		$code = substr($this->Controller->EmailTest->smtpError, 0, 3);
		$this->skipIf(!$code, '%s Authentication not enabled on server');

		$this->assertFalse($result);
		$this->assertEqual($code, '535');
	}

/**
 * testSendFormats method
 *
 * @access public
 * @return void
 */
	function testSendFormats() {
		$this->Controller->EmailTest->to = 'postmaster@localhost';
		$this->Controller->EmailTest->from = 'noreply@example.com';
		$this->Controller->EmailTest->subject = 'Cake SMTP test';
		$this->Controller->EmailTest->replyTo = 'noreply@example.com';
		$this->Controller->EmailTest->template = null;
		$this->Controller->EmailTest->delivery = 'debug';
		$this->Controller->EmailTest->messageId = false;

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
		$this->Controller->EmailTest->sendAs = 'text';
		$expect = str_replace('{CONTENTTYPE}', 'text/plain; charset=UTF-8', $message);
		$this->assertTrue($this->Controller->EmailTest->send('This is the body of the message'));
		$this->assertEqual($this->Controller->Session->read('Message.email.message'), $this->__osFix($expect));

		$this->Controller->EmailTest->sendAs = 'html';
		$expect = str_replace('{CONTENTTYPE}', 'text/html; charset=UTF-8', $message);
		$this->assertTrue($this->Controller->EmailTest->send('This is the body of the message'));
		$this->assertEqual($this->Controller->Session->read('Message.email.message'), $this->__osFix($expect));

		// TODO: better test for format of message sent?
		$this->Controller->EmailTest->sendAs = 'both';
		$expect = str_replace('{CONTENTTYPE}', 'multipart/alternative; boundary="alt-"', $message);

		$this->assertTrue($this->Controller->EmailTest->send('This is the body of the message'));
		$this->assertEqual($this->Controller->Session->read('Message.email.message'), $this->__osFix($expect));
	}

/**
 * testTemplates method
 *
 * @access public
 * @return void
 */
	function testTemplates() {
		$this->Controller->EmailTest->to = 'postmaster@localhost';
		$this->Controller->EmailTest->from = 'noreply@example.com';
		$this->Controller->EmailTest->subject = 'Cake SMTP test';
		$this->Controller->EmailTest->replyTo = 'noreply@example.com';

		$this->Controller->EmailTest->delivery = 'debug';
		$this->Controller->EmailTest->messageId = false;

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

		$this->Controller->EmailTest->layout = 'default';
		$this->Controller->EmailTest->template = 'default';

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
		$this->assertEqual($this->Controller->Session->read('Message.email.message'), $this->__osFix($expect));

		$this->Controller->EmailTest->sendAs = 'html';
		$expect = '<pre>' . str_replace('{CONTENTTYPE}', 'text/html; charset=UTF-8', $header) . $html . "\n" . '</pre>';
		$this->assertTrue($this->Controller->EmailTest->send('This is the body of the message'));
		$this->assertEqual($this->Controller->Session->read('Message.email.message'), $this->__osFix($expect));

		$this->Controller->EmailTest->sendAs = 'both';
		$expect = str_replace('{CONTENTTYPE}', 'multipart/alternative; boundary="alt-"', $header);
		$expect .= '--alt-' . "\n" . 'Content-Type: text/plain; charset=UTF-8' . "\n" . 'Content-Transfer-Encoding: 7bit' . "\n\n" . $text . "\n\n";
		$expect .= '--alt-' . "\n" . 'Content-Type: text/html; charset=UTF-8' . "\n" . 'Content-Transfer-Encoding: 7bit' . "\n\n" . $html . "\n\n";
		$expect = '<pre>' . $expect . '--alt---' . "\n\n" . '</pre>';

		$this->assertTrue($this->Controller->EmailTest->send('This is the body of the message'));
		$this->assertEqual($this->Controller->Session->read('Message.email.message'), $this->__osFix($expect));

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
		$this->assertEqual($this->Controller->Session->read('Message.email.message'), $this->__osFix($expect));

		return;

		$text = <<<TEXTBLOC

This element has some text that is just too wide to comply with email
standards.
This is the body of the message

This email was sent using the CakePHP Framework, http://cakephp.org.
TEXTBLOC;

		$this->Controller->EmailTest->sendAs = 'text';
		$expect = '<pre>' . str_replace('{CONTENTTYPE}', 'text/plain; charset=UTF-8', $header) . $text . "\n" . '</pre>';
		$this->assertTrue($this->Controller->EmailTest->send('This is the body of the message', 'wide', 'default'));
		$this->assertEqual($this->Controller->Session->read('Message.email.message'), $this->__osFix($expect));
	}

/**
 * testSmtpSendSocket method
 *
 * @access public
 * @return void
 */
	function testSmtpSendSocket() {
		$this->skipIf(!@fsockopen('localhost', 25), '%s No SMTP server running on localhost');

		$socket =& new CakeSocket(array_merge(array('protocol'=>'smtp'), $this->Controller->EmailTest->smtpOptions));
		$this->Controller->EmailTest->setConnectionSocket($socket);

		$this->assertTrue($this->Controller->EmailTest->getConnectionSocket());

		$response = $this->Controller->EmailTest->smtpSend('HELO', '250');
		$this->assertPattern('/501 Syntax: HELO hostname/', $this->Controller->EmailTest->smtpError);

		$this->Controller->EmailTest->reset();
		$response = $this->Controller->EmailTest->smtpSend('HELO somehostname', '250');
		$this->assertNoPattern('/501 Syntax: HELO hostname/', $this->Controller->EmailTest->smtpError);
	}

/**
 * testSendDebug method
 *
 * @access public
 * @return void
 */
	function testSendDebug() {
		$this->Controller->EmailTest->to = 'postmaster@localhost';
		$this->Controller->EmailTest->from = 'noreply@example.com';
		$this->Controller->EmailTest->subject = 'Cake Debug Test';
		$this->Controller->EmailTest->replyTo = 'noreply@example.com';
		$this->Controller->EmailTest->template = null;

		$this->Controller->EmailTest->delivery = 'debug';
		$this->assertTrue($this->Controller->EmailTest->send('This is the body of the message'));
		$result = $this->Controller->Session->read('Message.email.message');

		$this->assertPattern('/To: postmaster@localhost\n/', $result);
		$this->assertPattern('/Subject: Cake Debug Test\n/', $result);
		$this->assertPattern('/Reply-To: noreply@example.com\n/', $result);
		$this->assertPattern('/From: noreply@example.com\n/', $result);
		$this->assertPattern('/X-Mailer: CakePHP Email Component\n/', $result);
		$this->assertPattern('/Content-Type: text\/plain; charset=UTF-8\n/', $result);
		$this->assertPattern('/Content-Transfer-Encoding: 7bitParameters:\n/', $result);
		$this->assertPattern('/This is the body of the message/', $result);
	}

/**
 * test send with delivery = debug and not using sessions.
 *
 * @return void
 */
	function testSendDebugWithNoSessions() {
		$session =& $this->Controller->Session;
		unset($this->Controller->Session);
		$this->Controller->EmailTest->to = 'postmaster@localhost';
		$this->Controller->EmailTest->from = 'noreply@example.com';
		$this->Controller->EmailTest->subject = 'Cake Debug Test';
		$this->Controller->EmailTest->replyTo = 'noreply@example.com';
		$this->Controller->EmailTest->template = null;

		$this->Controller->EmailTest->delivery = 'debug';
		$result = $this->Controller->EmailTest->send('This is the body of the message');

		$this->assertPattern('/To: postmaster@localhost\n/', $result);
		$this->assertPattern('/Subject: Cake Debug Test\n/', $result);
		$this->assertPattern('/Reply-To: noreply@example.com\n/', $result);
		$this->assertPattern('/From: noreply@example.com\n/', $result);
		$this->assertPattern('/X-Mailer: CakePHP Email Component\n/', $result);
		$this->assertPattern('/Content-Type: text\/plain; charset=UTF-8\n/', $result);
		$this->assertPattern('/Content-Transfer-Encoding: 7bitParameters:\n/', $result);
		$this->assertPattern('/This is the body of the message/', $result);
		$this->Controller->Session = $session;
	}

/**
 * testMessageRetrievalWithoutTemplate method
 *
 * @access public
 * @return void
 */
	function testMessageRetrievalWithoutTemplate() {
		App::build(array(
			'views' => array(TEST_CAKE_CORE_INCLUDE_PATH . 'tests' . DS . 'test_app' . DS . 'views'. DS)
		));

		$this->Controller->EmailTest->to = 'postmaster@localhost';
		$this->Controller->EmailTest->from = 'noreply@example.com';
		$this->Controller->EmailTest->subject = 'Cake Debug Test';
		$this->Controller->EmailTest->replyTo = 'noreply@example.com';
		$this->Controller->EmailTest->layout = 'default';
		$this->Controller->EmailTest->template = null;

		$this->Controller->EmailTest->delivery = 'debug';

		$text = $html = 'This is the body of the message';

		$this->Controller->EmailTest->sendAs = 'both';
		$this->assertTrue($this->Controller->EmailTest->send('This is the body of the message'));
		$this->assertEqual($this->Controller->EmailTest->textMessage, $this->__osFix($text));
		$this->assertEqual($this->Controller->EmailTest->htmlMessage, $this->__osFix($html));

		$this->Controller->EmailTest->sendAs = 'text';
		$this->assertTrue($this->Controller->EmailTest->send('This is the body of the message'));
		$this->assertEqual($this->Controller->EmailTest->textMessage, $this->__osFix($text));
		$this->assertNull($this->Controller->EmailTest->htmlMessage);

		$this->Controller->EmailTest->sendAs = 'html';
		$this->assertTrue($this->Controller->EmailTest->send('This is the body of the message'));
		$this->assertNull($this->Controller->EmailTest->textMessage);
		$this->assertEqual($this->Controller->EmailTest->htmlMessage, $this->__osFix($html));
	}

/**
 * testMessageRetrievalWithTemplate method
 *
 * @access public
 * @return void
 */
	function testMessageRetrievalWithTemplate() {
		App::build(array(
			'views' => array(TEST_CAKE_CORE_INCLUDE_PATH . 'tests' . DS . 'test_app' . DS . 'views'. DS)
		));

		$this->Controller->set('value', 22091985);
		$this->Controller->set('title_for_layout', 'EmailTest');

		$this->Controller->EmailTest->to = 'postmaster@localhost';
		$this->Controller->EmailTest->from = 'noreply@example.com';
		$this->Controller->EmailTest->subject = 'Cake Debug Test';
		$this->Controller->EmailTest->replyTo = 'noreply@example.com';
		$this->Controller->EmailTest->layout = 'default';
		$this->Controller->EmailTest->template = 'custom';

		$this->Controller->EmailTest->delivery = 'debug';

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
		$this->assertEqual($this->Controller->EmailTest->textMessage, $this->__osFix($text));
		$this->assertEqual($this->Controller->EmailTest->htmlMessage, $this->__osFix($html));

		$this->Controller->EmailTest->sendAs = 'text';
		$this->assertTrue($this->Controller->EmailTest->send());
		$this->assertEqual($this->Controller->EmailTest->textMessage, $this->__osFix($text));
		$this->assertNull($this->Controller->EmailTest->htmlMessage);

		$this->Controller->EmailTest->sendAs = 'html';
		$this->assertTrue($this->Controller->EmailTest->send());
		$this->assertNull($this->Controller->EmailTest->textMessage);
		$this->assertEqual($this->Controller->EmailTest->htmlMessage, $this->__osFix($html));
	}

/**
 * testContentArray method
 *
 * @access public
 * @return void
 */
	function testSendContentArray() {
		$this->Controller->EmailTest->to = 'postmaster@localhost';
		$this->Controller->EmailTest->from = 'noreply@example.com';
		$this->Controller->EmailTest->subject = 'Cake Debug Test';
		$this->Controller->EmailTest->replyTo = 'noreply@example.com';
		$this->Controller->EmailTest->template = null;
		$this->Controller->EmailTest->delivery = 'debug';

		$content = array('First line', 'Second line', 'Third line');
		$this->assertTrue($this->Controller->EmailTest->send($content));
		$result = $this->Controller->Session->read('Message.email.message');

		$this->assertPattern('/To: postmaster@localhost\n/', $result);
		$this->assertPattern('/Subject: Cake Debug Test\n/', $result);
		$this->assertPattern('/Reply-To: noreply@example.com\n/', $result);
		$this->assertPattern('/From: noreply@example.com\n/', $result);
		$this->assertPattern('/X-Mailer: CakePHP Email Component\n/', $result);
		$this->assertPattern('/Content-Type: text\/plain; charset=UTF-8\n/', $result);
		$this->assertPattern('/Content-Transfer-Encoding: 7bitParameters:\n/', $result);
		$this->assertPattern('/First line\n/', $result);
		$this->assertPattern('/Second line\n/', $result);
		$this->assertPattern('/Third line\n/', $result);

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

		$result = $this->Controller->EmailTest->strip($content, true);
		$expected = "Previous content\n--alt-\n text/html; utf-8\n 7bit\n\n<p>My own html content</p>";
		$this->assertEqual($result, $expected);

		$content = '<p>Some HTML content with an <a href="mailto:test@example.com">email link</a>';
		$result  = $this->Controller->EmailTest->strip($content, true);
		$expected = $content;
		$this->assertEqual($result, $expected);

		$content  = '<p>Some HTML content with an ';
		$content .= '<a href="mailto:test@example.com,test2@example.com">email link</a>';
		$result  = $this->Controller->EmailTest->strip($content, true);
		$expected = $content;
		$this->assertEqual($result, $expected);

	}

/**
 * testMultibyte method
 *
 * @access public
 * @return void
 */
	function testMultibyte() {
		$this->Controller->EmailTest->to = 'postmaster@localhost';
		$this->Controller->EmailTest->from = 'noreply@example.com';
		$this->Controller->EmailTest->subject = 'هذه رسالة بعنوان طويل مرسل للمستلم';
		$this->Controller->EmailTest->replyTo = 'noreply@example.com';
		$this->Controller->EmailTest->template = null;
		$this->Controller->EmailTest->delivery = 'debug';

		$subject = '=?UTF-8?B?2YfYsNmHINix2LPYp9mE2Kkg2KjYudmG2YjYp9mGINi32YjZitmEINmF2LE=?=' . "\r\n" . ' =?UTF-8?B?2LPZhCDZhNmE2YXYs9iq2YTZhQ==?=';

		$this->Controller->EmailTest->sendAs = 'text';
		$this->assertTrue($this->Controller->EmailTest->send('This is the body of the message'));
		preg_match('/Subject: (.*)Header:/s', $this->Controller->Session->read('Message.email.message'), $matches);
		$this->assertEqual(trim($matches[1]), $subject);

		$this->Controller->EmailTest->sendAs = 'html';
		$this->assertTrue($this->Controller->EmailTest->send('This is the body of the message'));
		preg_match('/Subject: (.*)Header:/s', $this->Controller->Session->read('Message.email.message'), $matches);
		$this->assertEqual(trim($matches[1]), $subject);

		$this->Controller->EmailTest->sendAs = 'both';
		$this->assertTrue($this->Controller->EmailTest->send('This is the body of the message'));
		preg_match('/Subject: (.*)Header:/s', $this->Controller->Session->read('Message.email.message'), $matches);
		$this->assertEqual(trim($matches[1]), $subject);
	}

/**
 * undocumented function
 *
 * @return void
 * @access public
 */
	function testSendWithAttachments() {
		$this->Controller->EmailTest->to = 'postmaster@localhost';
		$this->Controller->EmailTest->from = 'noreply@example.com';
		$this->Controller->EmailTest->subject = 'Attachment Test';
		$this->Controller->EmailTest->replyTo = 'noreply@example.com';
		$this->Controller->EmailTest->template = null;
		$this->Controller->EmailTest->delivery = 'debug';
		$this->Controller->EmailTest->attachments = array(
			__FILE__,
			'some-name.php' => __FILE__
		);
		$body = '<p>This is the body of the message</p>';

		$this->Controller->EmailTest->sendAs = 'text';
		$this->assertTrue($this->Controller->EmailTest->send($body));
		$msg = $this->Controller->Session->read('Message.email.message');
		$this->assertPattern('/' . preg_quote('Content-Disposition: attachment; filename="email.test.php"') . '/', $msg);
		$this->assertPattern('/' . preg_quote('Content-Disposition: attachment; filename="some-name.php"') . '/', $msg);
	}

/**
 * testSendAsIsNotIgnoredIfAttachmentsPresent method
 *
 * @return void
 * @access public
 */
	function testSendAsIsNotIgnoredIfAttachmentsPresent() {
		$this->Controller->EmailTest->to = 'postmaster@localhost';
		$this->Controller->EmailTest->from = 'noreply@example.com';
		$this->Controller->EmailTest->subject = 'Attachment Test';
		$this->Controller->EmailTest->replyTo = 'noreply@example.com';
		$this->Controller->EmailTest->template = null;
		$this->Controller->EmailTest->delivery = 'debug';
		$this->Controller->EmailTest->attachments = array(__FILE__);
		$body = '<p>This is the body of the message</p>';

		$this->Controller->EmailTest->sendAs = 'html';
		$this->assertTrue($this->Controller->EmailTest->send($body));
		$msg = $this->Controller->Session->read('Message.email.message');
		$this->assertNoPattern('/text\/plain/', $msg);
		$this->assertPattern('/text\/html/', $msg);

		$this->Controller->EmailTest->sendAs = 'text';
		$this->assertTrue($this->Controller->EmailTest->send($body));
		$msg = $this->Controller->Session->read('Message.email.message');
		$this->assertPattern('/text\/plain/', $msg);
		$this->assertNoPattern('/text\/html/', $msg);

		$this->Controller->EmailTest->sendAs = 'both';
		$this->assertTrue($this->Controller->EmailTest->send($body));
		$msg = $this->Controller->Session->read('Message.email.message');

		$this->assertNoPattern('/text\/plain/', $msg);
		$this->assertNoPattern('/text\/html/', $msg);
		$this->assertPattern('/multipart\/alternative/', $msg);
	}

/**
 * testNoDoubleNewlinesInHeaders function
 *
 * @return void
 * @access public
 */
	function testNoDoubleNewlinesInHeaders() {
		$this->Controller->EmailTest->to = 'postmaster@localhost';
		$this->Controller->EmailTest->from = 'noreply@example.com';
		$this->Controller->EmailTest->subject = 'Attachment Test';
		$this->Controller->EmailTest->replyTo = 'noreply@example.com';
		$this->Controller->EmailTest->template = null;
		$this->Controller->EmailTest->delivery = 'debug';
		$body = '<p>This is the body of the message</p>';

		$this->Controller->EmailTest->sendAs = 'both';
		$this->assertTrue($this->Controller->EmailTest->send($body));
		$msg = $this->Controller->Session->read('Message.email.message');

		$this->assertNoPattern('/\n\nContent-Transfer-Encoding/', $msg);
		$this->assertPattern('/\nContent-Transfer-Encoding/', $msg);
	}

/**
 * testReset method
 *
 * @access public
 * @return void
 */
	function testReset() {
		$this->Controller->EmailTest->template = 'test_template';
		$this->Controller->EmailTest->to = 'test.recipient@example.com';
		$this->Controller->EmailTest->from = 'test.sender@example.com';
		$this->Controller->EmailTest->replyTo = 'test.replyto@example.com';
		$this->Controller->EmailTest->return = 'test.return@example.com';
		$this->Controller->EmailTest->cc = array('cc1@example.com', 'cc2@example.com');
		$this->Controller->EmailTest->bcc = array('bcc1@example.com', 'bcc2@example.com');
		$this->Controller->EmailTest->subject = 'Test subject';
		$this->Controller->EmailTest->additionalParams = 'X-additional-header';
		$this->Controller->EmailTest->delivery = 'smtp';
		$this->Controller->EmailTest->smtpOptions['host'] = 'blah';
		$this->Controller->EmailTest->attachments = array('attachment1', 'attachment2');
		$this->Controller->EmailTest->textMessage = 'This is the body of the message';
		$this->Controller->EmailTest->htmlMessage = 'This is the body of the message';
		$this->Controller->EmailTest->messageId = false;

		$this->assertFalse($this->Controller->EmailTest->send('Should not work'));

		$this->Controller->EmailTest->reset();

		$this->assertNull($this->Controller->EmailTest->template);
		$this->assertIdentical($this->Controller->EmailTest->to, array());
		$this->assertNull($this->Controller->EmailTest->from);
		$this->assertNull($this->Controller->EmailTest->replyTo);
		$this->assertNull($this->Controller->EmailTest->return);
		$this->assertIdentical($this->Controller->EmailTest->cc, array());
		$this->assertIdentical($this->Controller->EmailTest->bcc, array());
		$this->assertNull($this->Controller->EmailTest->subject);
		$this->assertNull($this->Controller->EmailTest->additionalParams);
		$this->assertIdentical($this->Controller->EmailTest->getHeaders(), array());
		$this->assertNull($this->Controller->EmailTest->getBoundary());
		$this->assertIdentical($this->Controller->EmailTest->getMessage(), array());
		$this->assertNull($this->Controller->EmailTest->smtpError);
		$this->assertIdentical($this->Controller->EmailTest->attachments, array());
		$this->assertNull($this->Controller->EmailTest->textMessage);
		$this->assertTrue($this->Controller->EmailTest->messageId);
	}

	function testPluginCustomViewClass() {
		App::build(array(
			'plugins' => array(TEST_CAKE_CORE_INCLUDE_PATH . 'tests' . DS . 'test_app' . DS . 'plugins' . DS),
			'views' => array(TEST_CAKE_CORE_INCLUDE_PATH . 'tests' . DS . 'test_app' . DS . 'views'. DS)
		));

		$this->Controller->view = 'TestPlugin.Email';

		$this->Controller->EmailTest->to = 'postmaster@localhost';
		$this->Controller->EmailTest->from = 'noreply@example.com';
		$this->Controller->EmailTest->subject = 'CustomViewClass test';
		$this->Controller->EmailTest->delivery = 'debug';
		$body = 'Body of message';

		$this->assertTrue($this->Controller->EmailTest->send($body));
		$result = $this->Controller->Session->read('Message.email.message');

		$this->assertPattern('/Body of message/', $result);

	}

/**
 * testStartup method
 *
 * @access public
 * @return void
 */
	function testStartup() {
		$this->assertNull($this->Controller->EmailTest->startup($this->Controller));
	}

/**
 * testMessageId method
 *
 * @access public
 * @return void
 */
	function testMessageId() {
		$this->Controller->EmailTest->to = 'postmaster@localhost';
		$this->Controller->EmailTest->from = 'noreply@example.com';
		$this->Controller->EmailTest->subject = 'Cake Debug Test';
		$this->Controller->EmailTest->replyTo = 'noreply@example.com';
		$this->Controller->EmailTest->template = null;

		$this->Controller->EmailTest->delivery = 'debug';
		$this->assertTrue($this->Controller->EmailTest->send('This is the body of the message'));
		$result = $this->Controller->Session->read('Message.email.message');

		$this->assertPattern('/Message-ID: \<[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}@' . env('HTTP_HOST') . '\>\n/', $result);

		$this->Controller->EmailTest->messageId = '<22091985.998877@localhost>';

		$this->assertTrue($this->Controller->EmailTest->send('This is the body of the message'));
		$result = $this->Controller->Session->read('Message.email.message');

		$this->assertPattern('/Message-ID: <22091985.998877@localhost>\n/', $result);

		$this->Controller->EmailTest->messageId = false;

		$this->assertTrue($this->Controller->EmailTest->send('This is the body of the message'));
		$result = $this->Controller->Session->read('Message.email.message');

		$this->assertNoPattern('/Message-ID:/', $result);
	}

}
