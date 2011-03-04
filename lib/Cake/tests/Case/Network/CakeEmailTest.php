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
 * @package       cake.tests.cases.libs
 * @since         CakePHP(tm) v 2.0.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
App::import('Core', 'CakeEmail');

/**
 * Help to test CakeEmail
 *
 */
class TestCakeEmail extends CakeEmail {

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
 * Send
 *
 * @param object $email CakeEmail
 * @return boolean
 */
	public function send(CakeEmail $email) {
		self::$lastEmail = implode("\r\n", $email->getMessage());
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
 * @package       cake.tests.cases.libs
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
			'views' => array(TEST_CAKE_CORE_INCLUDE_PATH . 'tests' . DS . 'test_app' . DS . 'views'. DS)
		));
	}

/**
 * tearDown method
 *
 * @return void
 */
	function tearDown() {
		parent::tearDown();
		App::build();
	}

/**
 * testFrom method
 *
 * @return void
 */
	public function testFrom() {
		$this->assertIdentical($this->CakeEmail->getFrom(), array());

		$this->CakeEmail->setFrom('cake@cakephp.org');
		$expected = array('cake@cakephp.org' => 'cake@cakephp.org');
		$this->assertIdentical($this->CakeEmail->getFrom(), $expected);

		$this->CakeEmail->setFrom(array('cake@cakephp.org'));
		$this->assertIdentical($this->CakeEmail->getFrom(), $expected);

		$this->CakeEmail->setFrom('cake@cakephp.org', 'CakePHP');
		$expected = array('cake@cakephp.org' => 'CakePHP');
		$this->assertIdentical($this->CakeEmail->getFrom(), $expected);

		$this->CakeEmail->setFrom(array('cake@cakephp.org' => 'CakePHP'));
		$this->assertIdentical($this->CakeEmail->getFrom(), $expected);
	}

/**
 * testTo method
 *
 * @return void
 */
	public function testTo() {
		$this->assertIdentical($this->CakeEmail->getTo(), array());

		$this->CakeEmail->setTo('cake@cakephp.org');
		$expected = array('cake@cakephp.org' => 'cake@cakephp.org');
		$this->assertIdentical($this->CakeEmail->getTo(), $expected);

		$this->CakeEmail->setTo('cake@cakephp.org', 'CakePHP');
		$expected = array('cake@cakephp.org' => 'CakePHP');
		$this->assertIdentical($this->CakeEmail->getTo(), $expected);

		$list = array(
			'cake@cakephp.org' => 'Cake PHP',
			'cake-php@googlegroups.com' => 'Cake Groups',
			'root@cakephp.org'
		);
		$this->CakeEmail->setTo($list);
		$expected = array(
			'cake@cakephp.org' => 'Cake PHP',
			'cake-php@googlegroups.com' => 'Cake Groups',
			'root@cakephp.org' => 'root@cakephp.org'
		);
		$this->assertIdentical($this->CakeEmail->getTo(), $expected);

		$this->CakeEmail->addTo('jrbasso@cakephp.org');
		$this->CakeEmail->addTo('mark_story@cakephp.org', 'Mark Story');
		$this->CakeEmail->addTo(array('phpnut@cakephp.org' => 'PhpNut', 'jose_zap@cakephp.org'));
		$expected = array(
			'cake@cakephp.org' => 'Cake PHP',
			'cake-php@googlegroups.com' => 'Cake Groups',
			'root@cakephp.org' => 'root@cakephp.org',
			'jrbasso@cakephp.org' => 'jrbasso@cakephp.org',
			'mark_story@cakephp.org' => 'Mark Story',
			'phpnut@cakephp.org' => 'PhpNut',
			'jose_zap@cakephp.org' => 'jose_zap@cakephp.org'
		);
		$this->assertIdentical($this->CakeEmail->getTo(), $expected);
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
		$this->CakeEmail->setTo($value);
	}

/**
 * testFormatAddress method
 *
 * @return void
 */
	public function testFormatAddress() {
		$result = $this->CakeEmail->formatAddress(array('cake@cakephp.org' => 'cake@cakephp.org'));
		$expected = array('cake@cakephp.org');
		$this->assertIdentical($result, $expected);

		$result = $this->CakeEmail->formatAddress(array('cake@cakephp.org' => 'cake@cakephp.org', 'php@cakephp.org' => 'php@cakephp.org'));
		$expected = array('cake@cakephp.org', 'php@cakephp.org');
		$this->assertIdentical($result, $expected);

		$result = $this->CakeEmail->formatAddress(array('cake@cakephp.org' => 'CakePHP', 'php@cakephp.org' => 'Cake'));
		$expected = array('CakePHP <cake@cakephp.org>', 'Cake <php@cakephp.org>');
		$this->assertIdentical($result, $expected);

		$result = $this->CakeEmail->formatAddress(array('cake@cakephp.org' => 'ÄÖÜTest'));
		$expected = array('=?UTF-8?B?w4TDlsOcVGVzdA==?= <cake@cakephp.org>');
		$this->assertIdentical($result, $expected);
	}

/**
 * testAddresses method
 *
 * @return void
 */
	public function testAddresses() {
		$this->CakeEmail->reset();
		$this->CakeEmail->setFrom('cake@cakephp.org', 'CakePHP');
		$this->CakeEmail->setReplyTo('replyto@cakephp.org', 'ReplyTo CakePHP');
		$this->CakeEmail->setReadReceipt('readreceipt@cakephp.org', 'ReadReceipt CakePHP');
		$this->CakeEmail->setReturnPath('returnpath@cakephp.org', 'ReturnPath CakePHP');
		$this->CakeEmail->setTo('to@cakephp.org', 'To CakePHP');
		$this->CakeEmail->setCc('cc@cakephp.org', 'Cc CakePHP');
		$this->CakeEmail->setBcc('bcc@cakephp.org', 'Bcc CakePHP');
		$this->CakeEmail->addTo('to2@cakephp.org', 'To2 CakePHP');
		$this->CakeEmail->addCc('cc2@cakephp.org', 'Cc2 CakePHP');
		$this->CakeEmail->addBcc('bcc2@cakephp.org', 'Bcc2 CakePHP');

		$this->assertIdentical($this->CakeEmail->getFrom(), array('cake@cakephp.org' => 'CakePHP'));
		$this->assertIdentical($this->CakeEmail->getReplyTo(), array('replyto@cakephp.org' => 'ReplyTo CakePHP'));
		$this->assertIdentical($this->CakeEmail->getReadReceipt(), array('readreceipt@cakephp.org' => 'ReadReceipt CakePHP'));
		$this->assertIdentical($this->CakeEmail->getReturnPath(), array('returnpath@cakephp.org' => 'ReturnPath CakePHP'));
		$this->assertIdentical($this->CakeEmail->getTo(), array('to@cakephp.org' => 'To CakePHP', 'to2@cakephp.org' => 'To2 CakePHP'));
		$this->assertIdentical($this->CakeEmail->getCc(), array('cc@cakephp.org' => 'Cc CakePHP', 'cc2@cakephp.org' => 'Cc2 CakePHP'));
		$this->assertIdentical($this->CakeEmail->getBcc(), array('bcc@cakephp.org' => 'Bcc CakePHP', 'bcc2@cakephp.org' => 'Bcc2 CakePHP'));

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
		$this->CakeEmail->setMessageId(true);
		$result = $this->CakeEmail->getHeaders();
		$this->assertTrue(isset($result['Message-ID']));

		$this->CakeEmail->setMessageId(false);
		$result = $this->CakeEmail->getHeaders();
		$this->assertFalse(isset($result['Message-ID']));

		$this->CakeEmail->setMessageId('<my-email@localhost>');
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
		$this->CakeEmail->setMessageId('my-email@localhost');
	}

/**
 * testSubject method
 *
 * @return void
 */
	public function testSubject() {
		$this->CakeEmail->setSubject('You have a new message.');
		$this->assertIdentical($this->CakeEmail->getSubject(), 'You have a new message.');

		$this->CakeEmail->setSubject(1);
		$this->assertIdentical($this->CakeEmail->getSubject(), '1');

		$this->CakeEmail->setSubject(array('something'));
		$this->assertIdentical($this->CakeEmail->getSubject(), 'Array');
	}

/**
 * testHeaders method
 *
 * @return void
 */
	public function testHeaders() {
		$this->CakeEmail->setMessageId(false);
		$this->CakeEmail->setHeaders(array('X-Something' => 'nice'));
		$expected = array(
			'X-Something' => 'nice',
			'X-Mailer' => 'CakePHP Email Component',
			'Date' => date(DATE_RFC2822),
			'Content-Type' => 'text/plain; charset=UTF-8',
			'Content-Transfer-Encoding' => '7bit'
		);
		$this->assertIdentical($this->CakeEmail->getHeaders(), $expected);

		$this->CakeEmail->addHeaders(array('X-Something' => 'very nice', 'X-Other' => 'cool'));
		$expected = array(
			'X-Something' => 'very nice',
			'X-Other' => 'cool',
			'X-Mailer' => 'CakePHP Email Component',
			'Date' => date(DATE_RFC2822),
			'Content-Type' => 'text/plain; charset=UTF-8',
			'Content-Transfer-Encoding' => '7bit'
		);
		$this->assertIdentical($this->CakeEmail->getHeaders(), $expected);

		$this->CakeEmail->setFrom('cake@cakephp.org');
		$this->assertIdentical($this->CakeEmail->getHeaders(), $expected);

		$expected = array(
			'From' => 'cake@cakephp.org',
			'X-Something' => 'very nice',
			'X-Other' => 'cool',
			'X-Mailer' => 'CakePHP Email Component',
			'Date' => date(DATE_RFC2822),
			'Content-Type' => 'text/plain; charset=UTF-8',
			'Content-Transfer-Encoding' => '7bit'
		);
		$this->assertIdentical($this->CakeEmail->getHeaders(array('from' => true)), $expected);

		$this->CakeEmail->setFrom('cake@cakephp.org', 'CakePHP');
		$expected['From'] = 'CakePHP <cake@cakephp.org>';
		$this->assertIdentical($this->CakeEmail->getHeaders(array('from' => true)), $expected);

		$this->CakeEmail->setTo(array('cake@cakephp.org', 'php@cakephp.org' => 'CakePHP'));
		$expected = array(
			'From' => 'CakePHP <cake@cakephp.org>',
			'To' => 'cake@cakephp.org, CakePHP <php@cakephp.org>',
			'X-Something' => 'very nice',
			'X-Other' => 'cool',
			'X-Mailer' => 'CakePHP Email Component',
			'Date' => date(DATE_RFC2822),
			'Content-Type' => 'text/plain; charset=UTF-8',
			'Content-Transfer-Encoding' => '7bit'
		);
		$this->assertIdentical($this->CakeEmail->getHeaders(array('from' => true, 'to' => true)), $expected);
	}

/**
 * testAttachments
 *
 * @return void
 */
	public function testAttachments() {
		$this->CakeEmail->setAttachments(WWW_ROOT . 'index.php');
		$expected = array('index.php' => WWW_ROOT . 'index.php');
		$this->assertIdentical($this->CakeEmail->getAttachments(), $expected);

		$this->CakeEmail->setAttachments(array());
		$this->assertIdentical($this->CakeEmail->getAttachments(), array());

		$this->CakeEmail->setAttachments(WWW_ROOT . 'index.php');
		$this->CakeEmail->addAttachments(WWW_ROOT . 'test.php');
		$this->CakeEmail->addAttachments(array(WWW_ROOT . 'test.php'));
		$this->CakeEmail->addAttachments(array('other.txt' => WWW_ROOT . 'test.php', 'ht' => WWW_ROOT . '.htaccess'));
		$expected = array(
			'index.php' => WWW_ROOT . 'index.php',
			'test.php' => WWW_ROOT . 'test.php',
			'other.txt' => WWW_ROOT . 'test.php',
			'ht' => WWW_ROOT . '.htaccess'
		);
		$this->assertIdentical($this->CakeEmail->getAttachments(), $expected);
	}

/**
 * testSendWithContent method
 *
 * @return void
 */
	public function testSendWithContent() {
		$this->CakeEmail->reset();
		$this->CakeEmail->setTransport('debug');
		DebugTransport::$includeAddresses = false;

		$this->CakeEmail->setFrom('cake@cakephp.org');
		$this->CakeEmail->setTo(array('you@cakephp.org' => 'You'));
		$this->CakeEmail->setSubject('My title');
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
		$this->CakeEmail->setTransport('debug');
		DebugTransport::$includeAddresses = true;

		$this->CakeEmail->setFrom('cake@cakephp.org');
		$this->CakeEmail->setTo(array('you@cakephp.org' => 'You'));
		$this->CakeEmail->setSubject('My title');
		$this->CakeEmail->setLayout('default', 'default');
		$result = $this->CakeEmail->send();

		$this->assertTrue((bool)strpos(DebugTransport::$lastEmail, 'This email was sent using the CakePHP Framework'));
		$this->assertTrue((bool)strpos(DebugTransport::$lastHeader, 'Message-ID: '));
		$this->assertTrue((bool)strpos(DebugTransport::$lastHeader, 'To: '));
	}

/**
 * testReset method
 *
 * @return void
 */
	public function testReset() {
		$this->CakeEmail->setTo('cake@cakephp.org');
		$this->assertIdentical($this->CakeEmail->getTo(), array('cake@cakephp.org' => 'cake@cakephp.org'));

		$this->CakeEmail->reset();
		$this->assertIdentical($this->CakeEmail->getTo(), array());
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
		$this->assertIdentical($result, $expected);

		$text = 'Lorem ipsum dolor sit amet, consectetur < adipiscing elit. Donec ac turpis orci, non commodo odio. Morbi nibh nisi, vehicula > pellentesque accumsan amet.';
		$result = $this->CakeEmail->wrap($text);
		$expected = array(
			'Lorem ipsum dolor sit amet, consectetur < adipiscing elit. Donec ac turpis',
			'orci, non commodo odio. Morbi nibh nisi, vehicula > pellentesque accumsan',
			'amet.',
			''
		);
		$this->assertIdentical($result, $expected);

		$text = '<p>Lorem ipsum dolor sit amet,<br> consectetur adipiscing elit.<br> Donec ac turpis orci, non <b>commodo</b> odio. <br /> Morbi nibh nisi, vehicula pellentesque accumsan amet.<hr></p>';
		$result = $this->CakeEmail->wrap($text);
		$expected = array(
			'<p>Lorem ipsum dolor sit amet,<br> consectetur adipiscing elit.<br> Donec ac',
			'turpis orci, non <b>commodo</b> odio. <br /> Morbi nibh nisi, vehicula',
			'pellentesque accumsan amet.<hr></p>',
			''
		);
		$this->assertIdentical($result, $expected);

		$text = 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Donec ac <a href="http://cakephp.org">turpis</a> orci, non commodo odio. Morbi nibh nisi, vehicula pellentesque accumsan amet.';
		$result = $this->CakeEmail->wrap($text);
		$expected = array(
			'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Donec ac',
			'<a href="http://cakephp.org">turpis</a> orci, non commodo odio. Morbi nibh',
			'nisi, vehicula pellentesque accumsan amet.',
			''
		);
		$this->assertIdentical($result, $expected);
	}

}
