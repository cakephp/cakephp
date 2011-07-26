<?php
/**
 * CakeEmailTest file
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
 * @package       Cake.Test.Case.Network.Email
 * @since         CakePHP(tm) v 2.0.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
App::uses('CakeEmail', 'Network/Email');

/**
 * Help to test CakeEmail
 *
 */
class TestCakeEmail extends CakeEmail {

/**
 * Config
 *
 */
	protected $_config = array();

/**
 * Wrap to protected method
 *
 */
	public function formatAddress($address) {
		return parent::_formatAddress($address);
	}

/**
 * Wrap to protected method
 *
 */
	public function wrap($text) {
		return parent::_wrap($text);
	}

/**
 * Get the boundary attribute
 *
 * @return string
 */
	public function getBoundary() {
		return $this->_boundary;
	}

}

/**
 * Debug transport email
 *
 */
class DebugTransport extends AbstractTransport {

/**
 * Last email body
 *
 * @var string
 */
	public static $lastEmail = '';

/**
 * Last email header
 *
 * @var string
 */
	public static $lastHeader = '';

/**
 * Include addresses in header
 *
 * @var boolean
 */
	public static $includeAddresses = false;

/**
 * Config
 *
 * @var array
 */
	public static $config = array();

/**
 * Config
 *
 * @param mixed $config
 * @return mixed
 */
	public function config($config) {
		self::$config = $config;
	}

/**
 * Send
 *
 * @param object $email CakeEmail
 * @return boolean
 */
	public function send(CakeEmail $email) {
		self::$lastEmail = implode("\r\n", $email->message());
		$options = array();
		if (self::$includeAddresses) {
			$options = array_fill_keys(array('from', 'replyTo', 'readReceipt', 'returnPath', 'to', 'cc', 'bcc'), true);
		}
		self::$lastHeader = $this->_headersToString($email->getHeaders($options));
		return true;
	}

}

/**
 * CakeEmailTest class
 *
 * @package       Cake.Test.Case.Network.Email
 */
class CakeEmailTest extends CakeTestCase {

/**
 * setUp
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();
		$this->CakeEmail = new TestCakeEmail();

		App::build(array(
			'views' => array(CAKE . 'Test' . DS . 'test_app' . DS . 'View'. DS)
		));
	}

/**
 * tearDown method
 *
 * @return void
 */
	public function tearDown() {
		parent::tearDown();
		App::build();
	}

/**
 * testFrom method
 *
 * @return void
 */
	public function testFrom() {
		$this->assertIdentical($this->CakeEmail->from(), array());

		$this->CakeEmail->from('cake@cakephp.org');
		$expected = array('cake@cakephp.org' => 'cake@cakephp.org');
		$this->assertIdentical($this->CakeEmail->from(), $expected);

		$this->CakeEmail->from(array('cake@cakephp.org'));
		$this->assertIdentical($this->CakeEmail->from(), $expected);

		$this->CakeEmail->from('cake@cakephp.org', 'CakePHP');
		$expected = array('cake@cakephp.org' => 'CakePHP');
		$this->assertIdentical($this->CakeEmail->from(), $expected);

		$result = $this->CakeEmail->from(array('cake@cakephp.org' => 'CakePHP'));
		$this->assertIdentical($this->CakeEmail->from(), $expected);
		$this->assertIdentical($this->CakeEmail, $result);
	}

/**
 * testSender method
 *
 * @return void
 */
	public function testSender() {
		$this->CakeEmail->reset();
		$this->assertIdentical($this->CakeEmail->sender(), array());

		$this->CakeEmail->sender('cake@cakephp.org', 'Name');
		$expected = array('cake@cakephp.org' => 'Name');
		$this->assertIdentical($this->CakeEmail->sender(), $expected);

		$headers = $this->CakeEmail->getHeaders(array('from' => true, 'sender' => true));
		$this->assertIdentical($headers['From'], false);
		$this->assertIdentical($headers['Sender'], 'Name <cake@cakephp.org>');

		$this->CakeEmail->from('cake@cakephp.org', 'CakePHP');
		$headers = $this->CakeEmail->getHeaders(array('from' => true, 'sender' => true));
		$this->assertIdentical($headers['From'], 'CakePHP <cake@cakephp.org>');
		$this->assertIdentical($headers['Sender'], '');
	}

/**
 * testTo method
 *
 * @return void
 */
	public function testTo() {
		$this->assertIdentical($this->CakeEmail->to(), array());

		$result = $this->CakeEmail->to('cake@cakephp.org');
		$expected = array('cake@cakephp.org' => 'cake@cakephp.org');
		$this->assertIdentical($this->CakeEmail->to(), $expected);
		$this->assertIdentical($this->CakeEmail, $result);

		$this->CakeEmail->to('cake@cakephp.org', 'CakePHP');
		$expected = array('cake@cakephp.org' => 'CakePHP');
		$this->assertIdentical($this->CakeEmail->to(), $expected);

		$list = array(
			'cake@cakephp.org' => 'Cake PHP',
			'cake-php@googlegroups.com' => 'Cake Groups',
			'root@cakephp.org'
		);
		$this->CakeEmail->to($list);
		$expected = array(
			'cake@cakephp.org' => 'Cake PHP',
			'cake-php@googlegroups.com' => 'Cake Groups',
			'root@cakephp.org' => 'root@cakephp.org'
		);
		$this->assertIdentical($this->CakeEmail->to(), $expected);

		$this->CakeEmail->addTo('jrbasso@cakephp.org');
		$this->CakeEmail->addTo('mark_story@cakephp.org', 'Mark Story');
		$result = $this->CakeEmail->addTo(array('phpnut@cakephp.org' => 'PhpNut', 'jose_zap@cakephp.org'));
		$expected = array(
			'cake@cakephp.org' => 'Cake PHP',
			'cake-php@googlegroups.com' => 'Cake Groups',
			'root@cakephp.org' => 'root@cakephp.org',
			'jrbasso@cakephp.org' => 'jrbasso@cakephp.org',
			'mark_story@cakephp.org' => 'Mark Story',
			'phpnut@cakephp.org' => 'PhpNut',
			'jose_zap@cakephp.org' => 'jose_zap@cakephp.org'
		);
		$this->assertIdentical($this->CakeEmail->to(), $expected);
		$this->assertIdentical($this->CakeEmail, $result);
	}

/**
 * Data provider function for testBuildInvalidData
 *
 * @return array
 */
	public static function invalidEmails() {
		return array(
			array(1.0),
			array(''),
			array('string'),
			array('<tag>'),
			array('some@one.whereis'),
			array(array('ok@cakephp.org', 1.0, '', 'string'))
		);
	}

/**
 * testBuildInvalidData
 *
 * @dataProvider invalidEmails
 * @expectedException SocketException
 * @return void
 */
	public function testInvalidEmail($value) {
		$this->CakeEmail->to($value);
	}

/**
 * testFormatAddress method
 *
 * @return void
 */
	public function testFormatAddress() {
		$result = $this->CakeEmail->formatAddress(array('cake@cakephp.org' => 'cake@cakephp.org'));
		$expected = array('cake@cakephp.org');
		$this->assertIdentical($expected, $result);

		$result = $this->CakeEmail->formatAddress(array('cake@cakephp.org' => 'cake@cakephp.org', 'php@cakephp.org' => 'php@cakephp.org'));
		$expected = array('cake@cakephp.org', 'php@cakephp.org');
		$this->assertIdentical($expected, $result);

		$result = $this->CakeEmail->formatAddress(array('cake@cakephp.org' => 'CakePHP', 'php@cakephp.org' => 'Cake'));
		$expected = array('CakePHP <cake@cakephp.org>', 'Cake <php@cakephp.org>');
		$this->assertIdentical($expected, $result);

		$result = $this->CakeEmail->formatAddress(array('cake@cakephp.org' => 'ÄÖÜTest'));
		$expected = array('=?UTF-8?B?w4TDlsOcVGVzdA==?= <cake@cakephp.org>');
		$this->assertIdentical($expected, $result);
	}

/**
 * testAddresses method
 *
 * @return void
 */
	public function testAddresses() {
		$this->CakeEmail->reset();
		$this->CakeEmail->from('cake@cakephp.org', 'CakePHP');
		$this->CakeEmail->replyTo('replyto@cakephp.org', 'ReplyTo CakePHP');
		$this->CakeEmail->readReceipt('readreceipt@cakephp.org', 'ReadReceipt CakePHP');
		$this->CakeEmail->returnPath('returnpath@cakephp.org', 'ReturnPath CakePHP');
		$this->CakeEmail->to('to@cakephp.org', 'To CakePHP');
		$this->CakeEmail->cc('cc@cakephp.org', 'Cc CakePHP');
		$this->CakeEmail->bcc('bcc@cakephp.org', 'Bcc CakePHP');
		$this->CakeEmail->addTo('to2@cakephp.org', 'To2 CakePHP');
		$this->CakeEmail->addCc('cc2@cakephp.org', 'Cc2 CakePHP');
		$this->CakeEmail->addBcc('bcc2@cakephp.org', 'Bcc2 CakePHP');

		$this->assertIdentical($this->CakeEmail->from(), array('cake@cakephp.org' => 'CakePHP'));
		$this->assertIdentical($this->CakeEmail->replyTo(), array('replyto@cakephp.org' => 'ReplyTo CakePHP'));
		$this->assertIdentical($this->CakeEmail->readReceipt(), array('readreceipt@cakephp.org' => 'ReadReceipt CakePHP'));
		$this->assertIdentical($this->CakeEmail->returnPath(), array('returnpath@cakephp.org' => 'ReturnPath CakePHP'));
		$this->assertIdentical($this->CakeEmail->to(), array('to@cakephp.org' => 'To CakePHP', 'to2@cakephp.org' => 'To2 CakePHP'));
		$this->assertIdentical($this->CakeEmail->cc(), array('cc@cakephp.org' => 'Cc CakePHP', 'cc2@cakephp.org' => 'Cc2 CakePHP'));
		$this->assertIdentical($this->CakeEmail->bcc(), array('bcc@cakephp.org' => 'Bcc CakePHP', 'bcc2@cakephp.org' => 'Bcc2 CakePHP'));

		$headers = $this->CakeEmail->getHeaders(array_fill_keys(array('from', 'replyTo', 'readReceipt', 'returnPath', 'to', 'cc', 'bcc'), true));
		$this->assertIdentical($headers['From'], 'CakePHP <cake@cakephp.org>');
		$this->assertIdentical($headers['Reply-To'], 'ReplyTo CakePHP <replyto@cakephp.org>');
		$this->assertIdentical($headers['Disposition-Notification-To'], 'ReadReceipt CakePHP <readreceipt@cakephp.org>');
		$this->assertIdentical($headers['Return-Path'], 'ReturnPath CakePHP <returnpath@cakephp.org>');
		$this->assertIdentical($headers['To'], 'To CakePHP <to@cakephp.org>, To2 CakePHP <to2@cakephp.org>');
		$this->assertIdentical($headers['Cc'], 'Cc CakePHP <cc@cakephp.org>, Cc2 CakePHP <cc2@cakephp.org>');
		$this->assertIdentical($headers['Bcc'], 'Bcc CakePHP <bcc@cakephp.org>, Bcc2 CakePHP <bcc2@cakephp.org>');
	}

/**
 * testMessageId method
 *
 * @return void
 */
	public function testMessageId() {
		$this->CakeEmail->messageId(true);
		$result = $this->CakeEmail->getHeaders();
		$this->assertTrue(isset($result['Message-ID']));

		$this->CakeEmail->messageId(false);
		$result = $this->CakeEmail->getHeaders();
		$this->assertFalse(isset($result['Message-ID']));

		$result = $this->CakeEmail->messageId('<my-email@localhost>');
		$this->assertIdentical($this->CakeEmail, $result);
		$result = $this->CakeEmail->getHeaders();
		$this->assertIdentical($result['Message-ID'], '<my-email@localhost>');
	}

/**
 * testMessageIdInvalid method
 *
 * @return void
 * @expectedException SocketException
 */
	public function testMessageIdInvalid() {
		$this->CakeEmail->messageId('my-email@localhost');
	}

/**
 * testSubject method
 *
 * @return void
 */
	public function testSubject() {
		$this->CakeEmail->subject('You have a new message.');
		$this->assertIdentical($this->CakeEmail->subject(), 'You have a new message.');

		$this->CakeEmail->subject(1);
		$this->assertIdentical($this->CakeEmail->subject(), '1');

		$result = $this->CakeEmail->subject(array('something'));
		$this->assertIdentical($this->CakeEmail->subject(), 'Array');
		$this->assertIdentical($this->CakeEmail, $result);

		$this->CakeEmail->subject('هذه رسالة بعنوان طويل مرسل للمستلم');
		$expected = '=?UTF-8?B?2YfYsNmHINix2LPYp9mE2Kkg2KjYudmG2YjYp9mGINi32YjZitmEINmF2LE=?=' . "\r\n" . ' =?UTF-8?B?2LPZhCDZhNmE2YXYs9iq2YTZhQ==?=';
		$this->assertIdentical($this->CakeEmail->subject(), $expected);
	}

/**
 * testHeaders method
 *
 * @return void
 */
	public function testHeaders() {
		$this->CakeEmail->messageId(false);
		$this->CakeEmail->setHeaders(array('X-Something' => 'nice'));
		$expected = array(
			'X-Something' => 'nice',
			'X-Mailer' => 'CakePHP Email',
			'Date' => date(DATE_RFC2822),
			'MIME-Version' => '1.0',
			'Content-Type' => 'text/plain; charset=UTF-8',
			'Content-Transfer-Encoding' => '7bit'
		);
		$this->assertIdentical($this->CakeEmail->getHeaders(), $expected);

		$this->CakeEmail->addHeaders(array('X-Something' => 'very nice', 'X-Other' => 'cool'));
		$expected = array(
			'X-Something' => 'very nice',
			'X-Other' => 'cool',
			'X-Mailer' => 'CakePHP Email',
			'Date' => date(DATE_RFC2822),
			'MIME-Version' => '1.0',
			'Content-Type' => 'text/plain; charset=UTF-8',
			'Content-Transfer-Encoding' => '7bit'
		);
		$this->assertIdentical($this->CakeEmail->getHeaders(), $expected);

		$this->CakeEmail->from('cake@cakephp.org');
		$this->assertIdentical($this->CakeEmail->getHeaders(), $expected);

		$expected = array(
			'From' => 'cake@cakephp.org',
			'X-Something' => 'very nice',
			'X-Other' => 'cool',
			'X-Mailer' => 'CakePHP Email',
			'Date' => date(DATE_RFC2822),
			'MIME-Version' => '1.0',
			'Content-Type' => 'text/plain; charset=UTF-8',
			'Content-Transfer-Encoding' => '7bit'
		);
		$this->assertIdentical($this->CakeEmail->getHeaders(array('from' => true)), $expected);

		$this->CakeEmail->from('cake@cakephp.org', 'CakePHP');
		$expected['From'] = 'CakePHP <cake@cakephp.org>';
		$this->assertIdentical($this->CakeEmail->getHeaders(array('from' => true)), $expected);

		$this->CakeEmail->to(array('cake@cakephp.org', 'php@cakephp.org' => 'CakePHP'));
		$expected = array(
			'From' => 'CakePHP <cake@cakephp.org>',
			'To' => 'cake@cakephp.org, CakePHP <php@cakephp.org>',
			'X-Something' => 'very nice',
			'X-Other' => 'cool',
			'X-Mailer' => 'CakePHP Email',
			'Date' => date(DATE_RFC2822),
			'MIME-Version' => '1.0',
			'Content-Type' => 'text/plain; charset=UTF-8',
			'Content-Transfer-Encoding' => '7bit'
		);
		$this->assertIdentical($this->CakeEmail->getHeaders(array('from' => true, 'to' => true)), $expected);
	}

/**
 * testTemplate method
 *
 * @return void
 */
	public function testTemplate() {
		$this->CakeEmail->template('template', 'layout');
		$expected = array('template' => 'template', 'layout' => 'layout');
		$this->assertIdentical($this->CakeEmail->template(), $expected);

		$this->CakeEmail->template('new_template');
		$expected = array('template' => 'new_template', 'layout' => 'layout');
		$this->assertIdentical($this->CakeEmail->template(), $expected);

		$this->CakeEmail->template('template', null);
		$expected = array('template' => 'template', 'layout' => null);
		$this->assertIdentical($this->CakeEmail->template(), $expected);

		$this->CakeEmail->template(null, null);
		$expected = array('template' => null, 'layout' => null);
		$this->assertIdentical($this->CakeEmail->template(), $expected);
	}

/**
 * testViewVars method
 *
 * @return void
 */
	public function testViewVars() {
		$this->assertIdentical($this->CakeEmail->viewVars(), array());

		$this->CakeEmail->viewVars(array('value' => 12345));
		$this->assertIdentical($this->CakeEmail->viewVars(), array('value' => 12345));

		$this->CakeEmail->viewVars(array('name' => 'CakePHP'));
		$this->assertIdentical($this->CakeEmail->viewVars(), array('value' => 12345, 'name' => 'CakePHP'));

		$this->CakeEmail->viewVars(array('value' => 4567));
		$this->assertIdentical($this->CakeEmail->viewVars(), array('value' => 4567, 'name' => 'CakePHP'));
	}

/**
 * testAttachments method
 *
 * @return void
 */
	public function testAttachments() {
		$this->CakeEmail->attachments(CAKE . 'basics.php');
		$expected = array('basics.php' => array('file' => CAKE . 'basics.php', 'mimetype' => 'application/octet-stream'));
		$this->assertIdentical($this->CakeEmail->attachments(), $expected);

		$this->CakeEmail->attachments(array());
		$this->assertIdentical($this->CakeEmail->attachments(), array());

		$this->CakeEmail->attachments(array(array('file' => CAKE . 'basics.php', 'mimetype' => 'text/plain')));
		$this->CakeEmail->addAttachments(CAKE . 'bootstrap.php');
		$this->CakeEmail->addAttachments(array(CAKE . 'bootstrap.php'));
		$this->CakeEmail->addAttachments(array('other.txt' => CAKE . 'bootstrap.php', 'license' => CAKE . 'LICENSE.txt'));
		$expected = array(
			'basics.php' => array('file' => CAKE . 'basics.php', 'mimetype' => 'text/plain'),
			'bootstrap.php' => array('file' => CAKE . 'bootstrap.php', 'mimetype' => 'application/octet-stream'),
			'other.txt' => array('file' => CAKE . 'bootstrap.php', 'mimetype' => 'application/octet-stream'),
			'license' => array('file' => CAKE . 'LICENSE.txt', 'mimetype' => 'application/octet-stream')
		);
		$this->assertIdentical($this->CakeEmail->attachments(), $expected);
	}

/**
 * testTransport method
 *
 * @return void
 */
	public function testTransport() {
		$result = $this->CakeEmail->transport('debug');
		$this->assertIdentical($this->CakeEmail, $result);
		$this->assertIdentical($this->CakeEmail->transport(), 'debug');

		$result = $this->CakeEmail->transportClass();
		$this->assertIsA($result, 'DebugTransport');
	}

/**
 * testConfig method
 *
 * @return void
 */
	public function testConfig() {
		$this->CakeEmail->transport('debug')->transportClass();
		DebugTransport::$config = array();

		$config = array('test' => 'ok', 'test2' => true);
		$this->CakeEmail->config($config);
		$this->assertIdentical(DebugTransport::$config, $config);
		$this->assertIdentical($this->CakeEmail->config(), $config);

		$this->CakeEmail->config(array());
		$this->assertIdentical(DebugTransport::$config, array());
	}

/**
 * testSendWithContent method
 *
 * @return void
 */
	public function testSendWithContent() {
		$this->CakeEmail->reset();
		$this->CakeEmail->transport('debug');
		DebugTransport::$includeAddresses = false;

		$this->CakeEmail->from('cake@cakephp.org');
		$this->CakeEmail->to(array('you@cakephp.org' => 'You'));
		$this->CakeEmail->subject('My title');
		$this->CakeEmail->config(array('empty'));
		$result = $this->CakeEmail->send("Here is my body, with multi lines.\nThis is the second line.\r\n\r\nAnd the last.");

		$this->assertTrue($result);
		$expected = "Here is my body, with multi lines.\r\nThis is the second line.\r\n\r\nAnd the last.\r\n\r\n";
		$this->assertIdentical(DebugTransport::$lastEmail, $expected);
		$this->assertTrue((bool)strpos(DebugTransport::$lastHeader, 'Date: '));
		$this->assertTrue((bool)strpos(DebugTransport::$lastHeader, 'Message-ID: '));
		$this->assertFalse(strpos(DebugTransport::$lastHeader, 'To: '));

		DebugTransport::$includeAddresses = true;
		$this->CakeEmail->send("Other body");
		$this->assertIdentical(DebugTransport::$lastEmail, "Other body\r\n\r\n");
		$this->assertTrue((bool)strpos(DebugTransport::$lastHeader, 'Message-ID: '));
		$this->assertTrue((bool)strpos(DebugTransport::$lastHeader, 'To: '));
	}

/**
 * testSendRender method
 *
 * @return void
 */
	public function testSendRender() {
		$this->CakeEmail->reset();
		$this->CakeEmail->transport('debug');
		DebugTransport::$includeAddresses = true;

		$this->CakeEmail->from('cake@cakephp.org');
		$this->CakeEmail->to(array('you@cakephp.org' => 'You'));
		$this->CakeEmail->subject('My title');
		$this->CakeEmail->config(array('empty'));
		$this->CakeEmail->template('default', 'default');
		$result = $this->CakeEmail->send();

		$this->assertTrue((bool)strpos(DebugTransport::$lastEmail, 'This email was sent using the CakePHP Framework'));
		$this->assertTrue((bool)strpos(DebugTransport::$lastHeader, 'Message-ID: '));
		$this->assertTrue((bool)strpos(DebugTransport::$lastHeader, 'To: '));
	}

/**
 * testSendRenderWithVars method
 *
 * @return void
 */
	public function testSendRenderWithVars() {
		$this->CakeEmail->reset();
		$this->CakeEmail->transport('debug');
		DebugTransport::$includeAddresses = true;

		$this->CakeEmail->from('cake@cakephp.org');
		$this->CakeEmail->to(array('you@cakephp.org' => 'You'));
		$this->CakeEmail->subject('My title');
		$this->CakeEmail->config(array('empty'));
		$this->CakeEmail->template('custom', 'default');
		$this->CakeEmail->viewVars(array('value' => 12345));
		$result = $this->CakeEmail->send();

		$this->assertTrue((bool)strpos(DebugTransport::$lastEmail, 'Here is your value: 12345'));
	}

/**
 * testSendRenderWithHelpers method
 *
 * @return void
 */
	public function testSendRenderWithHelpers() {
		$this->CakeEmail->reset();
		$this->CakeEmail->transport('debug');
		DebugTransport::$includeAddresses = true;

		$timestamp = time();
		$this->CakeEmail->from('cake@cakephp.org');
		$this->CakeEmail->to(array('you@cakephp.org' => 'You'));
		$this->CakeEmail->subject('My title');
		$this->CakeEmail->config(array('empty'));
		$this->CakeEmail->template('custom_helper', 'default');
		$this->CakeEmail->viewVars(array('time' => $timestamp));
		$this->CakeEmail->helpers(array('Time'));
		$result = $this->CakeEmail->send();

		$this->assertTrue((bool)strpos(DebugTransport::$lastEmail, 'Right now: ' . date('Y-m-d\TH:i:s\Z', $timestamp)));
	}

/**
 * testSendRenderPlugin method
 *
 * @return void
 */
	public function testSendRenderPlugin() {
		App::build(array(
			'plugins' => array(CAKE . 'Test' . DS . 'test_app' . DS . 'Plugin' . DS)
		));
		CakePlugin::load('TestPlugin');

		$this->CakeEmail->reset();
		$this->CakeEmail->transport('debug');
		DebugTransport::$includeAddresses = true;
		$this->CakeEmail->from('cake@cakephp.org');
		$this->CakeEmail->to(array('you@cakephp.org' => 'You'));
		$this->CakeEmail->subject('My title');
		$this->CakeEmail->config(array('empty'));

		$this->CakeEmail->template('TestPlugin.test_plugin_tpl', 'default')->send();
		$this->assertTrue((bool)strpos(DebugTransport::$lastEmail, 'Into TestPlugin.'));
		$this->assertTrue((bool)strpos(DebugTransport::$lastEmail, 'This email was sent using the CakePHP Framework'));

		$this->CakeEmail->template('TestPlugin.test_plugin_tpl', 'TestPlugin.plug_default')->send();
		$this->assertTrue((bool)strpos(DebugTransport::$lastEmail, 'Into TestPlugin.'));
		$this->assertTrue((bool)strpos(DebugTransport::$lastEmail, 'This email was sent using the TestPlugin.'));

		DebugTransport::$lastEmail = '';
		$this->CakeEmail->template('TestPlugin.test_plugin_tpl', 'plug_default')->send();
		$this->assertTrue((bool)strpos(DebugTransport::$lastEmail, 'Into TestPlugin.'));
		$this->assertTrue((bool)strpos(DebugTransport::$lastEmail, 'This email was sent using the TestPlugin.'));

		$this->CakeEmail->viewVars(array('value' => 12345));
		$this->CakeEmail->template('custom', 'TestPlugin.plug_default')->send();
		$this->assertTrue((bool)strpos(DebugTransport::$lastEmail, 'Here is your value: 12345'));
		$this->assertTrue((bool)strpos(DebugTransport::$lastEmail, 'This email was sent using the TestPlugin.'));

		$this->expectException();
		$this->CakeEmail->template('test_plugin_tpl', 'plug_default')->send();
	}

/**
 * testSendMultipleMIME method
 *
 * @return void
 */
	public function testSendMultipleMIME() {
		$this->CakeEmail->reset();
		$this->CakeEmail->transport('debug');
		DebugTransport::$includeAddresses = true;

		$this->CakeEmail->from('cake@cakephp.org');
		$this->CakeEmail->to(array('you@cakephp.org' => 'You'));
		$this->CakeEmail->subject('My title');
		$this->CakeEmail->template('custom', 'default');
		$this->CakeEmail->config(array());
		$this->CakeEmail->viewVars(array('value' => 12345));
		$this->CakeEmail->emailFormat('both');
		$result = $this->CakeEmail->send();

		$message = $this->CakeEmail->message();
		$boundary = $this->CakeEmail->getBoundary();
		$this->assertFalse(empty($boundary));
		$this->assertFalse(in_array('--' . $boundary, $message));
		$this->assertFalse(in_array('--' . $boundary . '--', $message));
		$this->assertTrue(in_array('--alt-' . $boundary, $message));
		$this->assertTrue(in_array('--alt-' . $boundary . '--', $message));

		$this->CakeEmail->attachments(array('fake.php' => __FILE__));
		$this->CakeEmail->send();

		$message = $this->CakeEmail->message();
		$boundary = $this->CakeEmail->getBoundary();
		$this->assertFalse(empty($boundary));
		$this->assertTrue(in_array('--' . $boundary, $message));
		$this->assertTrue(in_array('--' . $boundary . '--', $message));
		$this->assertTrue(in_array('--alt-' . $boundary, $message));
		$this->assertTrue(in_array('--alt-' . $boundary . '--', $message));
	}

/**
 * testSendAttachment method
 *
 * @return void
 */
	public function testSendAttachment() {
		$this->CakeEmail->reset();
		$this->CakeEmail->transport('debug');
		DebugTransport::$includeAddresses = false;

		$this->CakeEmail->from('cake@cakephp.org');
		$this->CakeEmail->to(array('you@cakephp.org' => 'You'));
		$this->CakeEmail->subject('My title');
		$this->CakeEmail->config(array());
		$this->CakeEmail->attachments(array(CAKE . 'basics.php'));
		$this->CakeEmail->send('body');
		$this->assertTrue((bool)strpos(DebugTransport::$lastEmail, "Content-Type: application/octet-stream\r\nContent-Transfer-Encoding: base64\r\nContent-Disposition: attachment; filename=\"basics.php\""));

		$this->CakeEmail->attachments(array('my.file.txt' => CAKE . 'basics.php'));
		$this->CakeEmail->send('body');
		$this->assertTrue((bool)strpos(DebugTransport::$lastEmail, "Content-Type: application/octet-stream\r\nContent-Transfer-Encoding: base64\r\nContent-Disposition: attachment; filename=\"my.file.txt\""));

		$this->CakeEmail->attachments(array('file.txt' => array('file' => CAKE . 'basics.php', 'mimetype' => 'text/plain')));
		$this->CakeEmail->send('body');
		$this->assertTrue((bool)strpos(DebugTransport::$lastEmail, "Content-Type: text/plain\r\nContent-Transfer-Encoding: base64\r\nContent-Disposition: attachment; filename=\"file.txt\""));

		$this->CakeEmail->attachments(array('file.txt' => array('file' => CAKE . 'basics.php', 'mimetype' => 'text/plain', 'contentId' => 'a1b1c1')));
		$this->CakeEmail->send('body');
		$this->assertTrue((bool)strpos(DebugTransport::$lastEmail, "Content-Type: text/plain\r\nContent-Transfer-Encoding: base64\r\nContent-ID: <a1b1c1>\r\nContent-Disposition: inline; filename=\"file.txt\""));
	}

/**
 * testDeliver method
 *
 * @return void
 */
	public function testDeliver() {
		$instance = CakeEmail::deliver('all@cakephp.org', 'About', 'Everything ok', array('from' => 'root@cakephp.org'), false);
		$this->assertIsA($instance, 'CakeEmail');
		$this->assertIdentical($instance->to(), array('all@cakephp.org' => 'all@cakephp.org'));
		$this->assertIdentical($instance->subject(), 'About');
		$this->assertIdentical($instance->from(), array('root@cakephp.org' => 'root@cakephp.org'));

		$config = array(
			'from' => 'cake@cakephp.org',
			'to' => 'debug@cakephp.org',
			'subject' => 'Update ok',
			'template' => 'custom',
			'layout' => 'custom_layout',
			'viewVars' => array('value' => 123),
			'cc' => array('cake@cakephp.org' => 'Myself')
		);
		$instance = CakeEmail::deliver(null, null, array('name' => 'CakePHP'), $config, false);
		$this->assertIdentical($instance->from(), array('cake@cakephp.org' => 'cake@cakephp.org'));
		$this->assertIdentical($instance->to(), array('debug@cakephp.org' => 'debug@cakephp.org'));
		$this->assertIdentical($instance->subject(), 'Update ok');
		$this->assertIdentical($instance->template(), array('template' => 'custom', 'layout' => 'custom_layout'));
		$this->assertIdentical($instance->viewVars(), array('value' => 123, 'name' => 'CakePHP'));
		$this->assertIdentical($instance->cc(), array('cake@cakephp.org' => 'Myself'));
	}

/**
 * testMessage method
 *
 * @return void
 */
	public function testMessage() {
		$this->CakeEmail->reset();
		$this->CakeEmail->transport('debug');
		DebugTransport::$includeAddresses = true;

		$this->CakeEmail->from('cake@cakephp.org');
		$this->CakeEmail->to(array('you@cakephp.org' => 'You'));
		$this->CakeEmail->subject('My title');
		$this->CakeEmail->config(array('empty'));
		$this->CakeEmail->template('default', 'default');
		$this->CakeEmail->emailFormat('both');
		$result = $this->CakeEmail->send();

		$expected = '<p>This email was sent using the <a href="http://cakephp.org">CakePHP Framework</a></p>';
		$this->assertTrue((bool)strpos($this->CakeEmail->message(CakeEmail::MESSAGE_HTML), $expected));

		$expected = 'This email was sent using the CakePHP Framework, http://cakephp.org.';
		$this->assertTrue((bool)strpos($this->CakeEmail->message(CakeEmail::MESSAGE_TEXT), $expected));

		$message = $this->CakeEmail->message();
		$this->assertTrue(in_array('Content-Type: text/plain; charset=UTF-8', $message));
		$this->assertTrue(in_array('Content-Type: text/html; charset=UTF-8', $message));
	}

/**
 * testReset method
 *
 * @return void
 */
	public function testReset() {
		$this->CakeEmail->to('cake@cakephp.org');
		$this->assertIdentical($this->CakeEmail->to(), array('cake@cakephp.org' => 'cake@cakephp.org'));

		$this->CakeEmail->reset();
		$this->assertIdentical($this->CakeEmail->to(), array());
	}

/**
 * testWrap method
 *
 * @return void
 */
	public function testWrap() {
		$text = 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Donec ac turpis orci, non commodo odio. Morbi nibh nisi, vehicula pellentesque accumsan amet.';
		$result = $this->CakeEmail->wrap($text);
		$expected = array(
			'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Donec ac turpis orci,',
			'non commodo odio. Morbi nibh nisi, vehicula pellentesque accumsan amet.',
			''
		);
		$this->assertIdentical($expected, $result);

		$text = 'Lorem ipsum dolor sit amet, consectetur < adipiscing elit. Donec ac turpis orci, non commodo odio. Morbi nibh nisi, vehicula > pellentesque accumsan amet.';
		$result = $this->CakeEmail->wrap($text);
		$expected = array(
			'Lorem ipsum dolor sit amet, consectetur < adipiscing elit. Donec ac turpis',
			'orci, non commodo odio. Morbi nibh nisi, vehicula > pellentesque accumsan',
			'amet.',
			''
		);
		$this->assertIdentical($expected, $result);

		$text = '<p>Lorem ipsum dolor sit amet,<br> consectetur adipiscing elit.<br> Donec ac turpis orci, non <b>commodo</b> odio. <br /> Morbi nibh nisi, vehicula pellentesque accumsan amet.<hr></p>';
		$result = $this->CakeEmail->wrap($text);
		$expected = array(
			'<p>Lorem ipsum dolor sit amet,<br> consectetur adipiscing elit.<br> Donec ac',
			'turpis orci, non <b>commodo</b> odio. <br /> Morbi nibh nisi, vehicula',
			'pellentesque accumsan amet.<hr></p>',
			''
		);
		$this->assertIdentical($expected, $result);

		$text = 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Donec ac <a href="http://cakephp.org">turpis</a> orci, non commodo odio. Morbi nibh nisi, vehicula pellentesque accumsan amet.';
		$result = $this->CakeEmail->wrap($text);
		$expected = array(
			'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Donec ac',
			'<a href="http://cakephp.org">turpis</a> orci, non commodo odio. Morbi nibh',
			'nisi, vehicula pellentesque accumsan amet.',
			''
		);
		$this->assertIdentical($expected, $result);

		$text = 'Lorem ipsum <a href="http://www.cakephp.org/controller/action/param1/param2" class="nice cool fine amazing awesome">ok</a>';
		$result = $this->CakeEmail->wrap($text);
		$expected = array(
			'Lorem ipsum',
			'<a href="http://www.cakephp.org/controller/action/param1/param2" class="nice cool fine amazing awesome">',
			'ok</a>',
			''
		);
		$this->assertIdentical($expected, $result);

		$text = 'Lorem ipsum withonewordverybigMorethanthelineshouldsizeofrfcspecificationbyieeeavailableonieeesite ok.';
		$result = $this->CakeEmail->wrap($text);
		$expected = array(
			'Lorem ipsum',
			'withonewordverybigMorethanthelineshouldsizeofrfcspecificationbyieeeavailableonieeesite',
			'ok.',
			''
		);
		$this->assertIdentical($expected, $result);
	}

}
