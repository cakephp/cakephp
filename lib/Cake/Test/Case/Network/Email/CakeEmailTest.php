<?php
/**
 * CakeEmailTest file
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

/**
 * Help to test CakeEmail
 *
 */
class TestCakeEmail extends CakeEmail {

/**
 * Config classname.
 *
 * Use a the testing config class in this file.
 *
 * @var string
 */
	protected $_configClass = 'TestEmailConfig';

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
	public function wrap($text, $length = CakeEmail::LINE_LENGTH_MUST) {
		return parent::_wrap($text, $length);
	}

/**
 * Get the boundary attribute
 *
 * @return string
 */
	public function getBoundary() {
		return $this->_boundary;
	}

/**
 * Encode to protected method
 *
 */
	public function encode($text) {
		return $this->_encode($text);
	}

/**
 * Render to protected method
 *
 */
	public function render($content) {
		return $this->_render($content);
	}

}

/*
 * EmailConfig class
 *
 */
class TestEmailConfig {

/**
 * test config
 *
 * @var string
 */
	public $test = array(
		'from' => array('some@example.com' => 'My website'),
		'to' => array('test@example.com' => 'Testname'),
		'subject' => 'Test mail subject',
		'transport' => 'Debug',
		'theme' => 'TestTheme',
		'helpers' => array('Html', 'Form'),
	);

/**
 * test config 2
 *
 * @var string
 */
	public $test2 = array(
		'from' => array('some@example.com' => 'My website'),
		'to' => array('test@example.com' => 'Testname'),
		'subject' => 'Test mail subject',
		'transport' => 'Smtp',
		'host' => 'cakephp.org',
		'timeout' => 60
	);

}

/*
 * ExtendTransport class
 * test class to ensure the class has send() method
 *
 */
class ExtendTransport {

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
			'View' => array(CAKE . 'Test' . DS . 'test_app' . DS . 'View' . DS)
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
		$this->assertSame($this->CakeEmail->from(), array());

		$this->CakeEmail->from('cake@cakephp.org');
		$expected = array('cake@cakephp.org' => 'cake@cakephp.org');
		$this->assertSame($this->CakeEmail->from(), $expected);

		$this->CakeEmail->from(array('cake@cakephp.org'));
		$this->assertSame($this->CakeEmail->from(), $expected);

		$this->CakeEmail->from('cake@cakephp.org', 'CakePHP');
		$expected = array('cake@cakephp.org' => 'CakePHP');
		$this->assertSame($this->CakeEmail->from(), $expected);

		$result = $this->CakeEmail->from(array('cake@cakephp.org' => 'CakePHP'));
		$this->assertSame($this->CakeEmail->from(), $expected);
		$this->assertSame($this->CakeEmail, $result);

		$this->setExpectedException('SocketException');
		$result = $this->CakeEmail->from(array('cake@cakephp.org' => 'CakePHP', 'fail@cakephp.org' => 'From can only be one address'));
	}

/**
 * Test that from addresses using colons work.
 *
 * @return void
 */
	public function testFromWithColonsAndQuotes() {
		$address = array(
			'info@example.com' => '70:20:00 " Forum'
		);
		$this->CakeEmail->from($address);
		$this->assertEquals($address, $this->CakeEmail->from());
		$this->CakeEmail->to('info@example.com')
			->subject('Test email')
			->transport('Debug');

		$result = $this->CakeEmail->send();
		$this->assertContains('From: "70:20:00 \" Forum" <info@example.com>', $result['headers']);
	}

/**
 * testSender method
 *
 * @return void
 */
	public function testSender() {
		$this->CakeEmail->reset();
		$this->assertSame($this->CakeEmail->sender(), array());

		$this->CakeEmail->sender('cake@cakephp.org', 'Name');
		$expected = array('cake@cakephp.org' => 'Name');
		$this->assertSame($this->CakeEmail->sender(), $expected);

		$headers = $this->CakeEmail->getHeaders(array('from' => true, 'sender' => true));
		$this->assertFalse($headers['From']);
		$this->assertSame($headers['Sender'], 'Name <cake@cakephp.org>');

		$this->CakeEmail->from('cake@cakephp.org', 'CakePHP');
		$headers = $this->CakeEmail->getHeaders(array('from' => true, 'sender' => true));
		$this->assertSame($headers['From'], 'CakePHP <cake@cakephp.org>');
		$this->assertSame($headers['Sender'], '');
	}

/**
 * testTo method
 *
 * @return void
 */
	public function testTo() {
		$this->assertSame($this->CakeEmail->to(), array());

		$result = $this->CakeEmail->to('cake@cakephp.org');
		$expected = array('cake@cakephp.org' => 'cake@cakephp.org');
		$this->assertSame($this->CakeEmail->to(), $expected);
		$this->assertSame($this->CakeEmail, $result);

		$this->CakeEmail->to('cake@cakephp.org', 'CakePHP');
		$expected = array('cake@cakephp.org' => 'CakePHP');
		$this->assertSame($this->CakeEmail->to(), $expected);

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
		$this->assertSame($this->CakeEmail->to(), $expected);

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
		$this->assertSame($this->CakeEmail->to(), $expected);
		$this->assertSame($this->CakeEmail, $result);
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
			array('some@one-whereis'),
			array('wrong@key' => 'Name'),
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
 * testBuildInvalidData
 *
 * @dataProvider invalidEmails
 * @expectedException SocketException
 * @return void
 */
	public function testInvalidEmailAdd($value) {
		$this->CakeEmail->addTo($value);
	}

/**
 * test emailPattern method
 *
 * @return void
 */
	public function testEmailPattern() {
		$regex = '/.+@.+\..+/i';
		$this->assertNull($this->CakeEmail->emailPattern());
		$this->assertSame($regex, $this->CakeEmail->emailPattern($regex)->emailPattern());
	}

/**
 * Tests that it is possible to set email regex configuration to a CakeEmail object
 *
 * @return void
 */
	public function testConfigEmailPattern() {
		$regex = '/.+@.+\..+/i';
		$email = new CakeEmail(array('emailPattern' => $regex));
		$this->assertSame($regex, $email->emailPattern());
	}

/**
 * Tests that it is possible set custom email validation
 */
	public function testCustomEmailValidation() {
		$regex = '/^[\.a-z0-9!#$%&\'*+\/=?^_`{|}~-]+@[-a-z0-9]+(\.[-a-z0-9]+)*\.[a-z]{2,6}$/i';

		$this->CakeEmail->emailPattern($regex)->to('pass.@example.com');
		$this->assertSame(array(
			'pass.@example.com' => 'pass.@example.com',
		), $this->CakeEmail->to());

		$this->CakeEmail->addTo('pass..old.docomo@example.com');
		$this->assertSame(array(
			'pass.@example.com' => 'pass.@example.com',
			'pass..old.docomo@example.com' => 'pass..old.docomo@example.com',
		), $this->CakeEmail->to());

		$this->CakeEmail->reset();
		$emails = array(
			'pass.@example.com',
			'pass..old.docomo@example.com'
		);
		$additionalEmails = array(
			'.extend.@example.com',
			'.docomo@example.com'
		);
		$this->CakeEmail->emailPattern($regex)->to($emails);
		$this->assertSame(array(
			'pass.@example.com' => 'pass.@example.com',
			'pass..old.docomo@example.com' => 'pass..old.docomo@example.com',
		), $this->CakeEmail->to());

		$this->CakeEmail->addTo($additionalEmails);
		$this->assertSame(array(
			'pass.@example.com' => 'pass.@example.com',
			'pass..old.docomo@example.com' => 'pass..old.docomo@example.com',
			'.extend.@example.com' => '.extend.@example.com',
			'.docomo@example.com' => '.docomo@example.com',
		), $this->CakeEmail->to());
	}

/**
 * testFormatAddress method
 *
 * @return void
 */
	public function testFormatAddress() {
		$result = $this->CakeEmail->formatAddress(array('cake@cakephp.org' => 'cake@cakephp.org'));
		$expected = array('cake@cakephp.org');
		$this->assertSame($expected, $result);

		$result = $this->CakeEmail->formatAddress(array('cake@cakephp.org' => 'cake@cakephp.org', 'php@cakephp.org' => 'php@cakephp.org'));
		$expected = array('cake@cakephp.org', 'php@cakephp.org');
		$this->assertSame($expected, $result);

		$result = $this->CakeEmail->formatAddress(array('cake@cakephp.org' => 'CakePHP', 'php@cakephp.org' => 'Cake'));
		$expected = array('CakePHP <cake@cakephp.org>', 'Cake <php@cakephp.org>');
		$this->assertSame($expected, $result);

		$result = $this->CakeEmail->formatAddress(array('me@example.com' => 'Last, First'));
		$expected = array('"Last, First" <me@example.com>');
		$this->assertSame($expected, $result);

		$result = $this->CakeEmail->formatAddress(array('me@example.com' => '"Last" First'));
		$expected = array('"\"Last\" First" <me@example.com>');
		$this->assertSame($expected, $result);

		$result = $this->CakeEmail->formatAddress(array('me@example.com' => 'Last First'));
		$expected = array('Last First <me@example.com>');
		$this->assertSame($expected, $result);

		$result = $this->CakeEmail->formatAddress(array('cake@cakephp.org' => 'ÄÖÜTest'));
		$expected = array('=?UTF-8?B?w4TDlsOcVGVzdA==?= <cake@cakephp.org>');
		$this->assertSame($expected, $result);

		$result = $this->CakeEmail->formatAddress(array('cake@cakephp.org' => '日本語Test'));
		$expected = array('=?UTF-8?B?5pel5pys6KqeVGVzdA==?= <cake@cakephp.org>');
		$this->assertSame($expected, $result);
	}

/**
 * testFormatAddressJapanese
 *
 * @return void
 */
	public function testFormatAddressJapanese() {
		$this->skipIf(!function_exists('mb_convert_encoding'));

		$this->CakeEmail->headerCharset = 'ISO-2022-JP';
		$result = $this->CakeEmail->formatAddress(array('cake@cakephp.org' => '日本語Test'));
		$expected = array('=?ISO-2022-JP?B?GyRCRnxLXDhsGyhCVGVzdA==?= <cake@cakephp.org>');
		$this->assertSame($expected, $result);

		$result = $this->CakeEmail->formatAddress(array('cake@cakephp.org' => '寿限無寿限無五劫の擦り切れ海砂利水魚の水行末雲来末風来末食う寝る処に住む処やぶら小路の藪柑子パイポパイポパイポのシューリンガンシューリンガンのグーリンダイグーリンダイのポンポコピーのポンポコナーの長久命の長助'));
		$expected = array("=?ISO-2022-JP?B?GyRCPHc4Qkw1PHc4Qkw1OF45ZSROOyQkakBaJGwzJDo9TXg/ZTV7GyhC?=\r\n" .
			" =?ISO-2022-JP?B?GyRCJE4/ZTlUS3YxQE1oS3ZJd01oS3Y/KSQmPzIkaz1oJEs9OyRgGyhC?=\r\n" .
			" =?ISO-2022-JP?B?GyRCPWgkZCRWJGk+Lk8pJE5pLjQ7O1IlUSUkJV0lUSUkJV0lUSUkGyhC?=\r\n" .
			" =?ISO-2022-JP?B?GyRCJV0kTiU3JWUhPCVqJXMlLCVzJTclZSE8JWolcyUsJXMkTiUwGyhC?=\r\n" .
			" =?ISO-2022-JP?B?GyRCITwlaiVzJUAlJCUwITwlaiVzJUAlJCROJV0lcyVdJTMlVCE8GyhC?=\r\n" .
			" =?ISO-2022-JP?B?GyRCJE4lXSVzJV0lMyVKITwkTkQ5NVdMPyRORDk9dRsoQg==?= <cake@cakephp.org>");
		$this->assertSame($expected, $result);
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

		$this->assertSame($this->CakeEmail->from(), array('cake@cakephp.org' => 'CakePHP'));
		$this->assertSame($this->CakeEmail->replyTo(), array('replyto@cakephp.org' => 'ReplyTo CakePHP'));
		$this->assertSame($this->CakeEmail->readReceipt(), array('readreceipt@cakephp.org' => 'ReadReceipt CakePHP'));
		$this->assertSame($this->CakeEmail->returnPath(), array('returnpath@cakephp.org' => 'ReturnPath CakePHP'));
		$this->assertSame($this->CakeEmail->to(), array('to@cakephp.org' => 'To CakePHP', 'to2@cakephp.org' => 'To2 CakePHP'));
		$this->assertSame($this->CakeEmail->cc(), array('cc@cakephp.org' => 'Cc CakePHP', 'cc2@cakephp.org' => 'Cc2 CakePHP'));
		$this->assertSame($this->CakeEmail->bcc(), array('bcc@cakephp.org' => 'Bcc CakePHP', 'bcc2@cakephp.org' => 'Bcc2 CakePHP'));

		$headers = $this->CakeEmail->getHeaders(array_fill_keys(array('from', 'replyTo', 'readReceipt', 'returnPath', 'to', 'cc', 'bcc'), true));
		$this->assertSame($headers['From'], 'CakePHP <cake@cakephp.org>');
		$this->assertSame($headers['Reply-To'], 'ReplyTo CakePHP <replyto@cakephp.org>');
		$this->assertSame($headers['Disposition-Notification-To'], 'ReadReceipt CakePHP <readreceipt@cakephp.org>');
		$this->assertSame($headers['Return-Path'], 'ReturnPath CakePHP <returnpath@cakephp.org>');
		$this->assertSame($headers['To'], 'To CakePHP <to@cakephp.org>, To2 CakePHP <to2@cakephp.org>');
		$this->assertSame($headers['Cc'], 'Cc CakePHP <cc@cakephp.org>, Cc2 CakePHP <cc2@cakephp.org>');
		$this->assertSame($headers['Bcc'], 'Bcc CakePHP <bcc@cakephp.org>, Bcc2 CakePHP <bcc2@cakephp.org>');
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
		$this->assertSame($this->CakeEmail, $result);
		$result = $this->CakeEmail->getHeaders();
		$this->assertSame($result['Message-ID'], '<my-email@localhost>');

		$result = $this->CakeEmail->messageId();
		$this->assertSame($result, '<my-email@localhost>');
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
 * testDomain method
 *
 * @return void
 */
	public function testDomain() {
		$result = $this->CakeEmail->domain();
		$expected = env('HTTP_HOST') ? env('HTTP_HOST') : php_uname('n');
		$this->assertSame($expected, $result);

		$this->CakeEmail->domain('example.org');
		$result = $this->CakeEmail->domain();
		$expected = 'example.org';
		$this->assertSame($expected, $result);
	}

/**
 * testMessageIdWithDomain method
 *
 * @return void
 */
	public function testMessageIdWithDomain() {
		$this->CakeEmail->domain('example.org');
		$result = $this->CakeEmail->getHeaders();
		$expected = '@example.org>';
		$this->assertTextContains($expected, $result['Message-ID']);

		$_SERVER['HTTP_HOST'] = 'example.org';
		$result = $this->CakeEmail->getHeaders();
		$this->assertTextContains('example.org', $result['Message-ID']);

		$_SERVER['HTTP_HOST'] = 'example.org:81';
		$result = $this->CakeEmail->getHeaders();
		$this->assertTextNotContains(':81', $result['Message-ID']);
	}

/**
 * testSubject method
 *
 * @return void
 */
	public function testSubject() {
		$this->CakeEmail->subject('You have a new message.');
		$this->assertSame($this->CakeEmail->subject(), 'You have a new message.');

		$this->CakeEmail->subject('You have a new message, I think.');
		$this->assertSame($this->CakeEmail->subject(), 'You have a new message, I think.');
		$this->CakeEmail->subject(1);
		$this->assertSame($this->CakeEmail->subject(), '1');

		$this->CakeEmail->subject('هذه رسالة بعنوان طويل مرسل للمستلم');
		$expected = '=?UTF-8?B?2YfYsNmHINix2LPYp9mE2Kkg2KjYudmG2YjYp9mGINi32YjZitmEINmF2LE=?=' . "\r\n" . ' =?UTF-8?B?2LPZhCDZhNmE2YXYs9iq2YTZhQ==?=';
		$this->assertSame($this->CakeEmail->subject(), $expected);
	}

/**
 * testSubjectJapanese
 *
 * @return void
 */
	public function testSubjectJapanese() {
		$this->skipIf(!function_exists('mb_convert_encoding'));
		mb_internal_encoding('UTF-8');

		$this->CakeEmail->headerCharset = 'ISO-2022-JP';
		$this->CakeEmail->subject('日本語のSubjectにも対応するよ');
		$expected = '=?ISO-2022-JP?B?GyRCRnxLXDhsJE4bKEJTdWJqZWN0GyRCJEskYkJQMX4kOSRrJGgbKEI=?=';
		$this->assertSame($this->CakeEmail->subject(), $expected);

		$this->CakeEmail->subject('長い長い長いSubjectの場合はfoldingするのが正しいんだけどいったいどうなるんだろう？');
		$expected = "=?ISO-2022-JP?B?GyRCRDkkJEQ5JCREOSQkGyhCU3ViamVjdBskQiROPmw5ZyRPGyhCZm9s?=\r\n" .
			" =?ISO-2022-JP?B?ZGluZxskQiQ5JGskTiQsQDUkNyQkJHMkQCQxJEkkJCRDJD8kJCRJGyhC?=\r\n" .
			" =?ISO-2022-JP?B?GyRCJCYkSiRrJHMkQCRtJCYhKRsoQg==?=";
		$this->assertSame($this->CakeEmail->subject(), $expected);
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
			'Content-Transfer-Encoding' => '8bit'
		);
		$this->assertSame($this->CakeEmail->getHeaders(), $expected);

		$this->CakeEmail->addHeaders(array('X-Something' => 'very nice', 'X-Other' => 'cool'));
		$expected = array(
			'X-Something' => 'very nice',
			'X-Other' => 'cool',
			'X-Mailer' => 'CakePHP Email',
			'Date' => date(DATE_RFC2822),
			'MIME-Version' => '1.0',
			'Content-Type' => 'text/plain; charset=UTF-8',
			'Content-Transfer-Encoding' => '8bit'
		);
		$this->assertSame($this->CakeEmail->getHeaders(), $expected);

		$this->CakeEmail->from('cake@cakephp.org');
		$this->assertSame($this->CakeEmail->getHeaders(), $expected);

		$expected = array(
			'From' => 'cake@cakephp.org',
			'X-Something' => 'very nice',
			'X-Other' => 'cool',
			'X-Mailer' => 'CakePHP Email',
			'Date' => date(DATE_RFC2822),
			'MIME-Version' => '1.0',
			'Content-Type' => 'text/plain; charset=UTF-8',
			'Content-Transfer-Encoding' => '8bit'
		);
		$this->assertSame($this->CakeEmail->getHeaders(array('from' => true)), $expected);

		$this->CakeEmail->from('cake@cakephp.org', 'CakePHP');
		$expected['From'] = 'CakePHP <cake@cakephp.org>';
		$this->assertSame($this->CakeEmail->getHeaders(array('from' => true)), $expected);

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
			'Content-Transfer-Encoding' => '8bit'
		);
		$this->assertSame($this->CakeEmail->getHeaders(array('from' => true, 'to' => true)), $expected);

		$this->CakeEmail->charset = 'ISO-2022-JP';
		$expected = array(
			'From' => 'CakePHP <cake@cakephp.org>',
			'To' => 'cake@cakephp.org, CakePHP <php@cakephp.org>',
			'X-Something' => 'very nice',
			'X-Other' => 'cool',
			'X-Mailer' => 'CakePHP Email',
			'Date' => date(DATE_RFC2822),
			'MIME-Version' => '1.0',
			'Content-Type' => 'text/plain; charset=ISO-2022-JP',
			'Content-Transfer-Encoding' => '7bit'
		);
		$this->assertSame($this->CakeEmail->getHeaders(array('from' => true, 'to' => true)), $expected);

		$result = $this->CakeEmail->setHeaders(array());
		$this->assertInstanceOf('CakeEmail', $result);
	}

/**
 * Data provider function for testInvalidHeaders
 *
 * @return array
 */
	public static function invalidHeaders() {
		return array(
			array(10),
			array(''),
			array('string'),
			array(false),
			array(null)
		);
	}

/**
 * testInvalidHeaders
 *
 * @dataProvider invalidHeaders
 * @expectedException SocketException
 * @return void
 */
	public function testInvalidHeaders($value) {
		$this->CakeEmail->setHeaders($value);
	}

/**
 * testInvalidAddHeaders
 *
 * @dataProvider invalidHeaders
 * @expectedException SocketException
 * @return void
 */
	public function testInvalidAddHeaders($value) {
		$this->CakeEmail->addHeaders($value);
	}

/**
 * testTemplate method
 *
 * @return void
 */
	public function testTemplate() {
		$this->CakeEmail->template('template', 'layout');
		$expected = array('template' => 'template', 'layout' => 'layout');
		$this->assertSame($this->CakeEmail->template(), $expected);

		$this->CakeEmail->template('new_template');
		$expected = array('template' => 'new_template', 'layout' => 'layout');
		$this->assertSame($this->CakeEmail->template(), $expected);

		$this->CakeEmail->template('template', null);
		$expected = array('template' => 'template', 'layout' => null);
		$this->assertSame($this->CakeEmail->template(), $expected);

		$this->CakeEmail->template(null, null);
		$expected = array('template' => null, 'layout' => null);
		$this->assertSame($this->CakeEmail->template(), $expected);
	}

/**
 * testTheme method
 *
 * @return void
 */
	public function testTheme() {
		$this->assertSame(null, $this->CakeEmail->theme());

		$this->CakeEmail->theme('default');
		$expected = 'default';
		$this->assertSame($expected, $this->CakeEmail->theme());
	}

/**
 * testViewVars method
 *
 * @return void
 */
	public function testViewVars() {
		$this->assertSame($this->CakeEmail->viewVars(), array());

		$this->CakeEmail->viewVars(array('value' => 12345));
		$this->assertSame($this->CakeEmail->viewVars(), array('value' => 12345));

		$this->CakeEmail->viewVars(array('name' => 'CakePHP'));
		$this->assertSame($this->CakeEmail->viewVars(), array('value' => 12345, 'name' => 'CakePHP'));

		$this->CakeEmail->viewVars(array('value' => 4567));
		$this->assertSame($this->CakeEmail->viewVars(), array('value' => 4567, 'name' => 'CakePHP'));
	}

/**
 * testAttachments method
 *
 * @return void
 */
	public function testAttachments() {
		$this->CakeEmail->attachments(CAKE . 'basics.php');
		$expected = array(
			'basics.php' => array(
				'file' => CAKE . 'basics.php',
				'mimetype' => 'application/octet-stream'
			)
		);
		$this->assertSame($this->CakeEmail->attachments(), $expected);

		$this->CakeEmail->attachments(array());
		$this->assertSame($this->CakeEmail->attachments(), array());

		$this->CakeEmail->attachments(array(
			array('file' => CAKE . 'basics.php', 'mimetype' => 'text/plain')
		));
		$this->CakeEmail->addAttachments(CAKE . 'bootstrap.php');
		$this->CakeEmail->addAttachments(array(CAKE . 'bootstrap.php'));
		$this->CakeEmail->addAttachments(array('other.txt' => CAKE . 'bootstrap.php', 'license' => CAKE . 'LICENSE.txt'));
		$expected = array(
			'basics.php' => array('file' => CAKE . 'basics.php', 'mimetype' => 'text/plain'),
			'bootstrap.php' => array('file' => CAKE . 'bootstrap.php', 'mimetype' => 'application/octet-stream'),
			'other.txt' => array('file' => CAKE . 'bootstrap.php', 'mimetype' => 'application/octet-stream'),
			'license' => array('file' => CAKE . 'LICENSE.txt', 'mimetype' => 'application/octet-stream')
		);
		$this->assertSame($this->CakeEmail->attachments(), $expected);

		$this->setExpectedException('SocketException');
		$this->CakeEmail->attachments(array(array('nofile' => CAKE . 'basics.php', 'mimetype' => 'text/plain')));
	}

/**
 * testTransport method
 *
 * @return void
 */
	public function testTransport() {
		$result = $this->CakeEmail->transport('Debug');
		$this->assertSame($this->CakeEmail, $result);
		$this->assertSame($this->CakeEmail->transport(), 'Debug');

		$result = $this->CakeEmail->transportClass();
		$this->assertInstanceOf('DebugTransport', $result);

		$this->setExpectedException('SocketException');
		$this->CakeEmail->transport('Invalid');
		$result = $this->CakeEmail->transportClass();
	}

/**
 * testExtendTransport method
 *
 * @return void
 */
	public function testExtendTransport() {
		$this->setExpectedException('SocketException');
		$this->CakeEmail->transport('Extend');
		$this->CakeEmail->transportClass();
	}

/**
 * testConfig method
 *
 * @return void
 */
	public function testConfig() {
		$transportClass = $this->CakeEmail->transport('debug')->transportClass();

		$config = array('test' => 'ok', 'test2' => true);
		$this->CakeEmail->config($config);
		$this->assertSame($transportClass->config(), $config);
		$this->assertSame($this->CakeEmail->config(), $config);

		$this->CakeEmail->config(array());
		$this->assertSame($transportClass->config(), $config);

		$config = array('test' => 'test@example.com');
		$this->CakeEmail->config($config);
		$expected = array('test' => 'test@example.com', 'test2' => true);
		$this->assertSame($expected, $this->CakeEmail->config());
		$this->assertSame($expected, $transportClass->config());
	}

/**
 * testConfigString method
 *
 * @return void
 */
	public function testConfigString() {
		$configs = new TestEmailConfig();
		$this->CakeEmail->config('test');

		$result = $this->CakeEmail->to();
		$this->assertEquals($configs->test['to'], $result);

		$result = $this->CakeEmail->from();
		$this->assertEquals($configs->test['from'], $result);

		$result = $this->CakeEmail->subject();
		$this->assertEquals($configs->test['subject'], $result);

		$result = $this->CakeEmail->theme();
		$this->assertEquals($configs->test['theme'], $result);

		$result = $this->CakeEmail->transport();
		$this->assertEquals($configs->test['transport'], $result);

		$result = $this->CakeEmail->transportClass();
		$this->assertInstanceOf('DebugTransport', $result);

		$result = $this->CakeEmail->helpers();
		$this->assertEquals($configs->test['helpers'], $result);
	}

/**
 * Test updating config doesn't reset transport's config.
 *
 * @return void
 */
	public function testConfigMerge() {
		$this->CakeEmail->config('test2');

		$expected = array(
			'host' => 'cakephp.org',
			'port' => 25,
			'timeout' => 60,
			'username' => null,
			'password' => null,
			'client' => null,
			'tls' => false
		);
		$this->assertEquals($expected, $this->CakeEmail->transportClass()->config());

		$this->CakeEmail->config(array('log' => true));
		$result = $this->CakeEmail->transportClass()->config();
		$expected += array('log' => true);
		$this->assertEquals($expected, $this->CakeEmail->transportClass()->config());

		$this->CakeEmail->config(array('timeout' => 45));
		$result = $this->CakeEmail->transportClass()->config();
		$this->assertEquals(45, $result['timeout']);
	}

/**
 * Calling send() with no parameters should not overwrite the view variables.
 *
 * @return void
 */
	public function testSendWithNoContentDoesNotOverwriteViewVar() {
		$this->CakeEmail->reset();
		$this->CakeEmail->transport('Debug');
		$this->CakeEmail->from('cake@cakephp.org');
		$this->CakeEmail->to('you@cakephp.org');
		$this->CakeEmail->subject('My title');
		$this->CakeEmail->emailFormat('text');
		$this->CakeEmail->template('default');
		$this->CakeEmail->viewVars(array(
			'content' => 'A message to you',
		));

		$result = $this->CakeEmail->send();
		$this->assertContains('A message to you', $result['message']);
	}

/**
 * testSendWithContent method
 *
 * @return void
 */
	public function testSendWithContent() {
		$this->CakeEmail->reset();
		$this->CakeEmail->transport('Debug');
		$this->CakeEmail->from('cake@cakephp.org');
		$this->CakeEmail->to(array('you@cakephp.org' => 'You'));
		$this->CakeEmail->subject('My title');
		$this->CakeEmail->config(array('empty'));

		$result = $this->CakeEmail->send("Here is my body, with multi lines.\nThis is the second line.\r\n\r\nAnd the last.");
		$expected = array('headers', 'message');
		$this->assertEquals($expected, array_keys($result));
		$expected = "Here is my body, with multi lines.\r\nThis is the second line.\r\n\r\nAnd the last.\r\n\r\n";

		$this->assertEquals($expected, $result['message']);
		$this->assertTrue((bool)strpos($result['headers'], 'Date: '));
		$this->assertTrue((bool)strpos($result['headers'], 'Message-ID: '));
		$this->assertTrue((bool)strpos($result['headers'], 'To: '));

		$result = $this->CakeEmail->send("Other body");
		$expected = "Other body\r\n\r\n";
		$this->assertSame($result['message'], $expected);
		$this->assertTrue((bool)strpos($result['headers'], 'Message-ID: '));
		$this->assertTrue((bool)strpos($result['headers'], 'To: '));

		$this->CakeEmail->reset();
		$this->CakeEmail->transport('Debug');
		$this->CakeEmail->from('cake@cakephp.org');
		$this->CakeEmail->to(array('you@cakephp.org' => 'You'));
		$this->CakeEmail->subject('My title');
		$this->CakeEmail->config(array('empty'));
		$result = $this->CakeEmail->send(array('Sending content', 'As array'));
		$expected = "Sending content\r\nAs array\r\n\r\n\r\n";
		$this->assertSame($result['message'], $expected);
	}

/**
 * testSendWithoutFrom method
 *
 * @return void
 */
	public function testSendWithoutFrom() {
		$this->CakeEmail->transport('Debug');
		$this->CakeEmail->to('cake@cakephp.org');
		$this->CakeEmail->subject('My title');
		$this->CakeEmail->config(array('empty'));
		$this->setExpectedException('SocketException');
		$this->CakeEmail->send("Forgot to set From");
	}

/**
 * testSendWithoutTo method
 *
 * @return void
 */
	public function testSendWithoutTo() {
		$this->CakeEmail->transport('Debug');
		$this->CakeEmail->from('cake@cakephp.org');
		$this->CakeEmail->subject('My title');
		$this->CakeEmail->config(array('empty'));
		$this->setExpectedException('SocketException');
		$this->CakeEmail->send("Forgot to set To");
	}

/**
 * Test send() with no template.
 *
 * @return void
 */
	public function testSendNoTemplateWithAttachments() {
		$this->CakeEmail->transport('debug');
		$this->CakeEmail->from('cake@cakephp.org');
		$this->CakeEmail->to('cake@cakephp.org');
		$this->CakeEmail->subject('My title');
		$this->CakeEmail->emailFormat('text');
		$this->CakeEmail->attachments(array(CAKE . 'basics.php'));
		$result = $this->CakeEmail->send('Hello');

		$boundary = $this->CakeEmail->getBoundary();
		$this->assertContains('Content-Type: multipart/mixed; boundary="' . $boundary . '"', $result['headers']);
		$expected = "--$boundary\r\n" .
			"Content-Type: text/plain; charset=UTF-8\r\n" .
			"Content-Transfer-Encoding: 8bit\r\n" .
			"\r\n" .
			"Hello" .
			"\r\n" .
			"\r\n" .
			"\r\n" .
			"--$boundary\r\n" .
			"Content-Type: application/octet-stream\r\n" .
			"Content-Transfer-Encoding: base64\r\n" .
			"Content-Disposition: attachment; filename=\"basics.php\"\r\n\r\n";
		$this->assertContains($expected, $result['message']);
	}

/**
 * Test send() with no template and data string attachment
 *
 * @return void
 */

	public function testSendNoTemplateWithDataStringAttachment() {
		$this->CakeEmail->transport('debug');
		$this->CakeEmail->from('cake@cakephp.org');
		$this->CakeEmail->to('cake@cakephp.org');
		$this->CakeEmail->subject('My title');
		$this->CakeEmail->emailFormat('text');
		$data = file_get_contents(CAKE . 'Console/Templates/skel/webroot/img/cake.icon.png');
		$this->CakeEmail->attachments(array('cake.icon.png' => array(
				'data' => $data,
				'mimetype' => 'image/png'
		)));
		$result = $this->CakeEmail->send('Hello');

		$boundary = $this->CakeEmail->getBoundary();
		$this->assertContains('Content-Type: multipart/mixed; boundary="' . $boundary . '"', $result['headers']);
		$expected = "--$boundary\r\n" .
				"Content-Type: text/plain; charset=UTF-8\r\n" .
				"Content-Transfer-Encoding: 8bit\r\n" .
				"\r\n" .
				"Hello" .
				"\r\n" .
				"\r\n" .
				"\r\n" .
				"--$boundary\r\n" .
				"Content-Type: image/png\r\n" .
				"Content-Transfer-Encoding: base64\r\n" .
				"Content-Disposition: attachment; filename=\"cake.icon.png\"\r\n\r\n";
		$expected .= chunk_split(base64_encode($data), 76, "\r\n");
		$this->assertContains($expected, $result['message']);
	}

/**
 * Test send() with no template as both
 *
 * @return void
 */
	public function testSendNoTemplateWithAttachmentsAsBoth() {
		$this->CakeEmail->transport('debug');
		$this->CakeEmail->from('cake@cakephp.org');
		$this->CakeEmail->to('cake@cakephp.org');
		$this->CakeEmail->subject('My title');
		$this->CakeEmail->emailFormat('both');
		$this->CakeEmail->attachments(array(CAKE . 'VERSION.txt'));
		$result = $this->CakeEmail->send('Hello');

		$boundary = $this->CakeEmail->getBoundary();
		$this->assertContains('Content-Type: multipart/mixed; boundary="' . $boundary . '"', $result['headers']);
		$expected = "--$boundary\r\n" .
			"Content-Type: multipart/alternative; boundary=\"alt-$boundary\"\r\n" .
			"\r\n" .
			"--alt-$boundary\r\n" .
			"Content-Type: text/plain; charset=UTF-8\r\n" .
			"Content-Transfer-Encoding: 8bit\r\n" .
			"\r\n" .
			"Hello" .
			"\r\n" .
			"\r\n" .
			"\r\n" .
			"--alt-$boundary\r\n" .
			"Content-Type: text/html; charset=UTF-8\r\n" .
			"Content-Transfer-Encoding: 8bit\r\n" .
			"\r\n" .
			"Hello" .
			"\r\n" .
			"\r\n" .
			"\r\n" .
			"--alt-{$boundary}--\r\n" .
			"\r\n" .
			"--$boundary\r\n" .
			"Content-Type: application/octet-stream\r\n" .
			"Content-Transfer-Encoding: base64\r\n" .
			"Content-Disposition: attachment; filename=\"VERSION.txt\"\r\n\r\n";
		$this->assertContains($expected, $result['message']);
	}

/**
 * Test setting inline attachments and messages.
 *
 * @return void
 */
	public function testSendWithInlineAttachments() {
		$this->CakeEmail->transport('debug');
		$this->CakeEmail->from('cake@cakephp.org');
		$this->CakeEmail->to('cake@cakephp.org');
		$this->CakeEmail->subject('My title');
		$this->CakeEmail->emailFormat('both');
		$this->CakeEmail->attachments(array(
			'cake.png' => array(
				'file' => CAKE . 'VERSION.txt',
				'contentId' => 'abc123'
			)
		));
		$result = $this->CakeEmail->send('Hello');

		$boundary = $this->CakeEmail->getBoundary();
		$this->assertContains('Content-Type: multipart/mixed; boundary="' . $boundary . '"', $result['headers']);
		$expected = "--$boundary\r\n" .
			"Content-Type: multipart/related; boundary=\"rel-$boundary\"\r\n" .
			"\r\n" .
			"--rel-$boundary\r\n" .
			"Content-Type: multipart/alternative; boundary=\"alt-$boundary\"\r\n" .
			"\r\n" .
			"--alt-$boundary\r\n" .
			"Content-Type: text/plain; charset=UTF-8\r\n" .
			"Content-Transfer-Encoding: 8bit\r\n" .
			"\r\n" .
			"Hello" .
			"\r\n" .
			"\r\n" .
			"\r\n" .
			"--alt-$boundary\r\n" .
			"Content-Type: text/html; charset=UTF-8\r\n" .
			"Content-Transfer-Encoding: 8bit\r\n" .
			"\r\n" .
			"Hello" .
			"\r\n" .
			"\r\n" .
			"\r\n" .
			"--alt-{$boundary}--\r\n" .
			"\r\n" .
			"--rel-$boundary\r\n" .
			"Content-Type: application/octet-stream\r\n" .
			"Content-Transfer-Encoding: base64\r\n" .
			"Content-ID: <abc123>\r\n" .
			"Content-Disposition: inline; filename=\"cake.png\"\r\n\r\n";
		$this->assertContains($expected, $result['message']);
		$this->assertContains('--rel-' . $boundary . '--', $result['message']);
		$this->assertContains('--' . $boundary . '--', $result['message']);
	}

/**
 * Test disabling content-disposition.
 *
 * @return void
 */
	public function testSendWithNoContentDispositionAttachments() {
		$this->CakeEmail->transport('debug');
		$this->CakeEmail->from('cake@cakephp.org');
		$this->CakeEmail->to('cake@cakephp.org');
		$this->CakeEmail->subject('My title');
		$this->CakeEmail->emailFormat('text');
		$this->CakeEmail->attachments(array(
			'cake.png' => array(
				'file' => CAKE . 'VERSION.txt',
				'contentDisposition' => false
			)
		));
		$result = $this->CakeEmail->send('Hello');

		$boundary = $this->CakeEmail->getBoundary();
		$this->assertContains('Content-Type: multipart/mixed; boundary="' . $boundary . '"', $result['headers']);
		$expected = "--$boundary\r\n" .
			"Content-Type: text/plain; charset=UTF-8\r\n" .
			"Content-Transfer-Encoding: 8bit\r\n" .
			"\r\n" .
			"Hello" .
			"\r\n" .
			"\r\n" .
			"\r\n" .
			"--{$boundary}\r\n" .
			"Content-Type: application/octet-stream\r\n" .
			"Content-Transfer-Encoding: base64\r\n" .
			"\r\n";

		$this->assertContains($expected, $result['message']);
		$this->assertContains('--' . $boundary . '--', $result['message']);
	}
/**
 * testSendWithLog method
 *
 * @return void
 */
	public function testSendWithLog() {
		CakeLog::config('email', array(
			'engine' => 'File',
			'path' => TMP
		));
		CakeLog::drop('default');
		$this->CakeEmail->transport('Debug');
		$this->CakeEmail->to('me@cakephp.org');
		$this->CakeEmail->from('cake@cakephp.org');
		$this->CakeEmail->subject('My title');
		$this->CakeEmail->config(array('log' => 'cake_test_emails'));
		$result = $this->CakeEmail->send("Logging This");

		App::uses('File', 'Utility');
		$File = new File(TMP . 'cake_test_emails.log');
		$log = $File->read();
		$this->assertTrue(strpos($log, $result['headers']) !== false);
		$this->assertTrue(strpos($log, $result['message']) !== false);
		$File->delete();
		CakeLog::drop('email');
	}

/**
 * testSendWithLogAndScope method
 *
 * @return void
 */
	public function testSendWithLogAndScope() {
		CakeLog::config('email', array(
			'engine' => 'File',
			'path' => TMP,
			'types' => array('cake_test_emails'),
			'scopes' => array('email')
		));
		CakeLog::drop('default');
		$this->CakeEmail->transport('Debug');
		$this->CakeEmail->to('me@cakephp.org');
		$this->CakeEmail->from('cake@cakephp.org');
		$this->CakeEmail->subject('My title');
		$this->CakeEmail->config(array('log' => array('level' => 'cake_test_emails', 'scope' => 'email')));
		$result = $this->CakeEmail->send("Logging This");

		App::uses('File', 'Utility');
		$File = new File(TMP . 'cake_test_emails.log');
		$log = $File->read();
		$this->assertTrue(strpos($log, $result['headers']) !== false);
		$this->assertTrue(strpos($log, $result['message']) !== false);
		$File->delete();
		CakeLog::drop('email');
	}

/**
 * testSendRender method
 *
 * @return void
 */
	public function testSendRender() {
		$this->CakeEmail->reset();
		$this->CakeEmail->transport('debug');

		$this->CakeEmail->from('cake@cakephp.org');
		$this->CakeEmail->to(array('you@cakephp.org' => 'You'));
		$this->CakeEmail->subject('My title');
		$this->CakeEmail->config(array('empty'));
		$this->CakeEmail->template('default', 'default');
		$result = $this->CakeEmail->send();

		$this->assertContains('This email was sent using the CakePHP Framework', $result['message']);
		$this->assertContains('Message-ID: ', $result['headers']);
		$this->assertContains('To: ', $result['headers']);
	}

/**
 * test sending and rendering with no layout
 *
 * @return void
 */
	public function testSendRenderNoLayout() {
		$this->CakeEmail->reset();
		$this->CakeEmail->transport('debug');

		$this->CakeEmail->from('cake@cakephp.org');
		$this->CakeEmail->to(array('you@cakephp.org' => 'You'));
		$this->CakeEmail->subject('My title');
		$this->CakeEmail->config(array('empty'));
		$this->CakeEmail->template('default', null);
		$result = $this->CakeEmail->send('message body.');

		$this->assertContains('message body.', $result['message']);
		$this->assertNotContains('This email was sent using the CakePHP Framework', $result['message']);
	}

/**
 * testSendRender method for ISO-2022-JP
 *
 * @return void
 */
	public function testSendRenderJapanese() {
		$this->skipIf(!function_exists('mb_convert_encoding'));

		$this->CakeEmail->reset();
		$this->CakeEmail->transport('debug');

		$this->CakeEmail->from('cake@cakephp.org');
		$this->CakeEmail->to(array('you@cakephp.org' => 'You'));
		$this->CakeEmail->subject('My title');
		$this->CakeEmail->config(array('empty'));
		$this->CakeEmail->template('default', 'japanese');
		$this->CakeEmail->charset = 'ISO-2022-JP';
		$result = $this->CakeEmail->send();

		$expected = mb_convert_encoding('CakePHP Framework を使って送信したメールです。 http://cakephp.org.', 'ISO-2022-JP');
		$this->assertContains($expected, $result['message']);
		$this->assertContains('Message-ID: ', $result['headers']);
		$this->assertContains('To: ', $result['headers']);
	}

/**
 * testSendRenderThemed method
 *
 * @return void
 */
	public function testSendRenderThemed() {
		$this->CakeEmail->reset();
		$this->CakeEmail->transport('debug');

		$this->CakeEmail->from('cake@cakephp.org');
		$this->CakeEmail->to(array('you@cakephp.org' => 'You'));
		$this->CakeEmail->subject('My title');
		$this->CakeEmail->config(array('empty'));
		$this->CakeEmail->theme('TestTheme');
		$this->CakeEmail->template('themed', 'default');
		$result = $this->CakeEmail->send();

		$this->assertContains('In TestTheme', $result['message']);
		$this->assertContains('Message-ID: ', $result['headers']);
		$this->assertContains('To: ', $result['headers']);
	}

/**
 * testSendRenderWithHTML method and assert line length is kept below the required limit
 *
 * @return void
 */
	public function testSendRenderWithHTML() {
		$this->CakeEmail->reset();
		$this->CakeEmail->transport('debug');

		$this->CakeEmail->from('cake@cakephp.org');
		$this->CakeEmail->to(array('you@cakephp.org' => 'You'));
		$this->CakeEmail->subject('My title');
		$this->CakeEmail->config(array('empty'));
		$this->CakeEmail->emailFormat('html');
		$this->CakeEmail->template('html', 'default');
		$result = $this->CakeEmail->send();

		$this->assertTextContains('<h1>HTML Ipsum Presents</h1>', $result['message']);
		$this->assertLineLengths($result['message']);
	}

/**
 * testSendRenderWithVars method
 *
 * @return void
 */
	public function testSendRenderWithVars() {
		$this->CakeEmail->reset();
		$this->CakeEmail->transport('debug');

		$this->CakeEmail->from('cake@cakephp.org');
		$this->CakeEmail->to(array('you@cakephp.org' => 'You'));
		$this->CakeEmail->subject('My title');
		$this->CakeEmail->config(array('empty'));
		$this->CakeEmail->template('custom', 'default');
		$this->CakeEmail->viewVars(array('value' => 12345));
		$result = $this->CakeEmail->send();

		$this->assertContains('Here is your value: 12345', $result['message']);
	}

/**
 * testSendRenderWithVars method for ISO-2022-JP
 *
 * @return void
 */
	public function testSendRenderWithVarsJapanese() {
		$this->skipIf(!function_exists('mb_convert_encoding'));
		$this->CakeEmail->reset();
		$this->CakeEmail->transport('debug');

		$this->CakeEmail->from('cake@cakephp.org');
		$this->CakeEmail->to(array('you@cakephp.org' => 'You'));
		$this->CakeEmail->subject('My title');
		$this->CakeEmail->config(array('empty'));
		$this->CakeEmail->template('japanese', 'default');
		$this->CakeEmail->viewVars(array('value' => '日本語の差し込み123'));
		$this->CakeEmail->charset = 'ISO-2022-JP';
		$result = $this->CakeEmail->send();

		$expected = mb_convert_encoding('ここにあなたの設定した値が入ります: 日本語の差し込み123', 'ISO-2022-JP');
		$this->assertTrue((bool)strpos($result['message'], $expected));
	}

/**
 * testSendRenderWithHelpers method
 *
 * @return void
 */
	public function testSendRenderWithHelpers() {
		$this->CakeEmail->reset();
		$this->CakeEmail->transport('debug');

		$timestamp = time();
		$this->CakeEmail->from('cake@cakephp.org');
		$this->CakeEmail->to(array('you@cakephp.org' => 'You'));
		$this->CakeEmail->subject('My title');
		$this->CakeEmail->config(array('empty'));
		$this->CakeEmail->template('custom_helper', 'default');
		$this->CakeEmail->viewVars(array('time' => $timestamp));

		$result = $this->CakeEmail->helpers(array('Time'));
		$this->assertInstanceOf('CakeEmail', $result);

		$result = $this->CakeEmail->send();
		$this->assertTrue((bool)strpos($result['message'], 'Right now: ' . date('Y-m-d\TH:i:s\Z', $timestamp)));

		$result = $this->CakeEmail->helpers();
		$this->assertEquals(array('Time'), $result);
	}

/**
 * testSendRenderWithImage method
 *
 * @return void
 */
	public function testSendRenderWithImage() {
		$this->CakeEmail->reset();
		$this->CakeEmail->transport('Debug');

		$this->CakeEmail->from('cake@cakephp.org');
		$this->CakeEmail->to(array('you@cakephp.org' => 'You'));
		$this->CakeEmail->subject('My title');
		$this->CakeEmail->config(array('empty'));
		$this->CakeEmail->template('image');
		$this->CakeEmail->emailFormat('html');
		$server = env('SERVER_NAME') ? env('SERVER_NAME') : 'localhost';

		if (env('SERVER_PORT') && env('SERVER_PORT') != 80) {
			$server .= ':' . env('SERVER_PORT');
		}

		$expected = '<img src="http://' . $server . '/img/image.gif" alt="cool image" width="100" height="100" />';
		$result = $this->CakeEmail->send();
		$this->assertContains($expected, $result['message']);
	}

/**
 * testSendRenderPlugin method
 *
 * @return void
 */
	public function testSendRenderPlugin() {
		App::build(array(
			'Plugin' => array(CAKE . 'Test' . DS . 'test_app' . DS . 'Plugin' . DS)
		));
		CakePlugin::load('TestPlugin');

		$this->CakeEmail->reset();
		$this->CakeEmail->transport('debug');
		$this->CakeEmail->from('cake@cakephp.org');
		$this->CakeEmail->to(array('you@cakephp.org' => 'You'));
		$this->CakeEmail->subject('My title');
		$this->CakeEmail->config(array('empty'));

		$result = $this->CakeEmail->template('TestPlugin.test_plugin_tpl', 'default')->send();
		$this->assertContains('Into TestPlugin.', $result['message']);
		$this->assertContains('This email was sent using the CakePHP Framework', $result['message']);

		$result = $this->CakeEmail->template('TestPlugin.test_plugin_tpl', 'TestPlugin.plug_default')->send();
		$this->assertContains('Into TestPlugin.', $result['message']);
		$this->assertContains('This email was sent using the TestPlugin.', $result['message']);

		$result = $this->CakeEmail->template('TestPlugin.test_plugin_tpl', 'plug_default')->send();
		$this->assertContains('Into TestPlugin.', $result['message']);
		$this->assertContains('This email was sent using the TestPlugin.', $result['message']);

		// test plugin template overridden by theme
		$this->CakeEmail->theme('TestTheme');
		$result = $this->CakeEmail->send();

		$this->assertContains('Into TestPlugin. (themed)', $result['message']);

		$this->CakeEmail->viewVars(array('value' => 12345));
		$result = $this->CakeEmail->template('custom', 'TestPlugin.plug_default')->send();
		$this->assertContains('Here is your value: 12345', $result['message']);
		$this->assertContains('This email was sent using the TestPlugin.', $result['message']);

		$this->setExpectedException('MissingViewException');
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

		$this->CakeEmail->from('cake@cakephp.org');
		$this->CakeEmail->to(array('you@cakephp.org' => 'You'));
		$this->CakeEmail->subject('My title');
		$this->CakeEmail->template('custom', 'default');
		$this->CakeEmail->config(array());
		$this->CakeEmail->viewVars(array('value' => 12345));
		$this->CakeEmail->emailFormat('both');
		$this->CakeEmail->send();

		$message = $this->CakeEmail->message();
		$boundary = $this->CakeEmail->getBoundary();
		$this->assertFalse(empty($boundary));
		$this->assertContains('--' . $boundary, $message);
		$this->assertContains('--' . $boundary . '--', $message);
		$this->assertContains('--alt-' . $boundary, $message);
		$this->assertContains('--alt-' . $boundary . '--', $message);

		$this->CakeEmail->attachments(array('fake.php' => __FILE__));
		$this->CakeEmail->send();

		$message = $this->CakeEmail->message();
		$boundary = $this->CakeEmail->getBoundary();
		$this->assertFalse(empty($boundary));
		$this->assertContains('--' . $boundary, $message);
		$this->assertContains('--' . $boundary . '--', $message);
		$this->assertContains('--alt-' . $boundary, $message);
		$this->assertContains('--alt-' . $boundary . '--', $message);
	}

/**
 * testSendAttachment method
 *
 * @return void
 */
	public function testSendAttachment() {
		$this->CakeEmail->reset();
		$this->CakeEmail->transport('debug');
		$this->CakeEmail->from('cake@cakephp.org');
		$this->CakeEmail->to(array('you@cakephp.org' => 'You'));
		$this->CakeEmail->subject('My title');
		$this->CakeEmail->config(array());
		$this->CakeEmail->attachments(array(CAKE . 'basics.php'));
		$result = $this->CakeEmail->send('body');
		$this->assertContains("Content-Type: application/octet-stream\r\nContent-Transfer-Encoding: base64\r\nContent-Disposition: attachment; filename=\"basics.php\"", $result['message']);

		$this->CakeEmail->attachments(array('my.file.txt' => CAKE . 'basics.php'));
		$result = $this->CakeEmail->send('body');
		$this->assertContains("Content-Type: application/octet-stream\r\nContent-Transfer-Encoding: base64\r\nContent-Disposition: attachment; filename=\"my.file.txt\"", $result['message']);

		$this->CakeEmail->attachments(array('file.txt' => array('file' => CAKE . 'basics.php', 'mimetype' => 'text/plain')));
		$result = $this->CakeEmail->send('body');
		$this->assertContains("Content-Type: text/plain\r\nContent-Transfer-Encoding: base64\r\nContent-Disposition: attachment; filename=\"file.txt\"", $result['message']);

		$this->CakeEmail->attachments(array('file2.txt' => array('file' => CAKE . 'basics.php', 'mimetype' => 'text/plain', 'contentId' => 'a1b1c1')));
		$result = $this->CakeEmail->send('body');
		$this->assertContains("Content-Type: text/plain\r\nContent-Transfer-Encoding: base64\r\nContent-ID: <a1b1c1>\r\nContent-Disposition: inline; filename=\"file2.txt\"", $result['message']);
	}

/**
 * testDeliver method
 *
 * @return void
 */
	public function testDeliver() {
		$instance = CakeEmail::deliver('all@cakephp.org', 'About', 'Everything ok', array('from' => 'root@cakephp.org'), false);
		$this->assertInstanceOf('CakeEmail', $instance);
		$this->assertSame($instance->to(), array('all@cakephp.org' => 'all@cakephp.org'));
		$this->assertSame($instance->subject(), 'About');
		$this->assertSame($instance->from(), array('root@cakephp.org' => 'root@cakephp.org'));

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
		$this->assertSame($instance->from(), array('cake@cakephp.org' => 'cake@cakephp.org'));
		$this->assertSame($instance->to(), array('debug@cakephp.org' => 'debug@cakephp.org'));
		$this->assertSame($instance->subject(), 'Update ok');
		$this->assertSame($instance->template(), array('template' => 'custom', 'layout' => 'custom_layout'));
		$this->assertSame($instance->viewVars(), array('value' => 123, 'name' => 'CakePHP'));
		$this->assertSame($instance->cc(), array('cake@cakephp.org' => 'Myself'));

		$configs = array('from' => 'root@cakephp.org', 'message' => 'Message from configs', 'transport' => 'Debug');
		$instance = CakeEmail::deliver('all@cakephp.org', 'About', null, $configs, true);
		$message = $instance->message();
		$this->assertEquals($configs['message'], $message[0]);
	}

/**
 * testMessage method
 *
 * @return void
 */
	public function testMessage() {
		$this->CakeEmail->reset();
		$this->CakeEmail->transport('debug');
		$this->CakeEmail->from('cake@cakephp.org');
		$this->CakeEmail->to(array('you@cakephp.org' => 'You'));
		$this->CakeEmail->subject('My title');
		$this->CakeEmail->config(array('empty'));
		$this->CakeEmail->template('default', 'default');
		$this->CakeEmail->emailFormat('both');
		$this->CakeEmail->send();

		$expected = '<p>This email was sent using the <a href="http://cakephp.org">CakePHP Framework</a></p>';
		$this->assertContains($expected, $this->CakeEmail->message(CakeEmail::MESSAGE_HTML));

		$expected = 'This email was sent using the CakePHP Framework, http://cakephp.org.';
		$this->assertContains($expected, $this->CakeEmail->message(CakeEmail::MESSAGE_TEXT));

		$message = $this->CakeEmail->message();
		$this->assertContains('Content-Type: text/plain; charset=UTF-8', $message);
		$this->assertContains('Content-Type: text/html; charset=UTF-8', $message);

		// UTF-8 is 8bit
		$this->assertTrue($this->_checkContentTransferEncoding($message, '8bit'));

		$this->CakeEmail->charset = 'ISO-2022-JP';
		$this->CakeEmail->send();
		$message = $this->CakeEmail->message();
		$this->assertContains('Content-Type: text/plain; charset=ISO-2022-JP', $message);
		$this->assertContains('Content-Type: text/html; charset=ISO-2022-JP', $message);

		// ISO-2022-JP is 7bit
		$this->assertTrue($this->_checkContentTransferEncoding($message, '7bit'));
	}

/**
 * testReset method
 *
 * @return void
 */
	public function testReset() {
		$this->CakeEmail->to('cake@cakephp.org');
		$this->CakeEmail->theme('TestTheme');
		$this->CakeEmail->emailPattern('/.+@.+\..+/i');
		$this->assertSame($this->CakeEmail->to(), array('cake@cakephp.org' => 'cake@cakephp.org'));

		$this->CakeEmail->reset();
		$this->assertSame($this->CakeEmail->to(), array());
		$this->assertSame(null, $this->CakeEmail->theme());
		$this->assertSame(null, $this->CakeEmail->emailPattern());
	}

/**
 * testReset with charset
 *
 * @return void
 */
	public function testResetWithCharset() {
		$this->CakeEmail->charset = 'ISO-2022-JP';
		$this->CakeEmail->reset();

		$this->assertSame($this->CakeEmail->charset, 'utf-8', $this->CakeEmail->charset);
		$this->assertSame($this->CakeEmail->headerCharset, null, $this->CakeEmail->headerCharset);
	}

/**
 * testWrap method
 *
 * @return void
 */
	public function testWrap() {
		$text = 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Donec ac turpis orci, non commodo odio. Morbi nibh nisi, vehicula pellentesque accumsan amet.';
		$result = $this->CakeEmail->wrap($text, CakeEmail::LINE_LENGTH_SHOULD);
		$expected = array(
			'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Donec ac turpis orci,',
			'non commodo odio. Morbi nibh nisi, vehicula pellentesque accumsan amet.',
			''
		);
		$this->assertSame($expected, $result);

		$text = 'Lorem ipsum dolor sit amet, consectetur < adipiscing elit. Donec ac turpis orci, non commodo odio. Morbi nibh nisi, vehicula > pellentesque accumsan amet.';
		$result = $this->CakeEmail->wrap($text, CakeEmail::LINE_LENGTH_SHOULD);
		$expected = array(
			'Lorem ipsum dolor sit amet, consectetur < adipiscing elit. Donec ac turpis',
			'orci, non commodo odio. Morbi nibh nisi, vehicula > pellentesque accumsan',
			'amet.',
			''
		);
		$this->assertSame($expected, $result);

		$text = '<p>Lorem ipsum dolor sit amet,<br> consectetur adipiscing elit.<br> Donec ac turpis orci, non <b>commodo</b> odio. <br /> Morbi nibh nisi, vehicula pellentesque accumsan amet.<hr></p>';
		$result = $this->CakeEmail->wrap($text, CakeEmail::LINE_LENGTH_SHOULD);
		$expected = array(
			'<p>Lorem ipsum dolor sit amet,<br> consectetur adipiscing elit.<br> Donec ac',
			'turpis orci, non <b>commodo</b> odio. <br /> Morbi nibh nisi, vehicula',
			'pellentesque accumsan amet.<hr></p>',
			''
		);
		$this->assertSame($expected, $result);

		$text = 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Donec ac <a href="http://cakephp.org">turpis</a> orci, non commodo odio. Morbi nibh nisi, vehicula pellentesque accumsan amet.';
		$result = $this->CakeEmail->wrap($text, CakeEmail::LINE_LENGTH_SHOULD);
		$expected = array(
			'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Donec ac',
			'<a href="http://cakephp.org">turpis</a> orci, non commodo odio. Morbi nibh',
			'nisi, vehicula pellentesque accumsan amet.',
			''
		);
		$this->assertSame($expected, $result);

		$text = 'Lorem ipsum <a href="http://www.cakephp.org/controller/action/param1/param2" class="nice cool fine amazing awesome">ok</a>';
		$result = $this->CakeEmail->wrap($text, CakeEmail::LINE_LENGTH_SHOULD);
		$expected = array(
			'Lorem ipsum',
			'<a href="http://www.cakephp.org/controller/action/param1/param2" class="nice cool fine amazing awesome">',
			'ok</a>',
			''
		);
		$this->assertSame($expected, $result);

		$text = 'Lorem ipsum withonewordverybigMorethanthelineshouldsizeofrfcspecificationbyieeeavailableonieeesite ok.';
		$result = $this->CakeEmail->wrap($text, CakeEmail::LINE_LENGTH_SHOULD);
		$expected = array(
			'Lorem ipsum',
			'withonewordverybigMorethanthelineshouldsizeofrfcspecificationbyieeeavailableonieeesite',
			'ok.',
			''
		);
		$this->assertSame($expected, $result);
	}

/**
 * testRender method
 *
 * @return void
 */
	public function testRenderWithLayoutAndAttachment() {
		$this->CakeEmail->emailFormat('html');
		$this->CakeEmail->template('html', 'default');
		$this->CakeEmail->attachments(array(CAKE . 'basics.php'));
		$result = $this->CakeEmail->render(array());
		$this->assertNotEmpty($result);

		$result = $this->CakeEmail->getBoundary();
		$this->assertNotEmpty($result);
	}

/**
 * testConstructWithConfigArray method
 *
 * @return void
 */
	public function testConstructWithConfigArray() {
		$configs = array(
			'from' => array('some@example.com' => 'My website'),
			'to' => 'test@example.com',
			'subject' => 'Test mail subject',
			'transport' => 'Debug',
		);
		$this->CakeEmail = new CakeEmail($configs);

		$result = $this->CakeEmail->to();
		$this->assertEquals(array($configs['to'] => $configs['to']), $result);

		$result = $this->CakeEmail->from();
		$this->assertEquals($configs['from'], $result);

		$result = $this->CakeEmail->subject();
		$this->assertEquals($configs['subject'], $result);

		$result = $this->CakeEmail->transport();
		$this->assertEquals($configs['transport'], $result);

		$result = $this->CakeEmail->transportClass();
		$this->assertTrue($result instanceof DebugTransport);

		$result = $this->CakeEmail->send('This is the message');

		$this->assertTrue((bool)strpos($result['headers'], 'Message-ID: '));
		$this->assertTrue((bool)strpos($result['headers'], 'To: '));
	}

/**
 * testConstructWithConfigString method
 *
 * @return void
 */
	public function testConstructWithConfigString() {
		$configs = new TestEmailConfig();
		$this->CakeEmail = new TestCakeEmail('test');

		$result = $this->CakeEmail->to();
		$this->assertEquals($configs->test['to'], $result);

		$result = $this->CakeEmail->from();
		$this->assertEquals($configs->test['from'], $result);

		$result = $this->CakeEmail->subject();
		$this->assertEquals($configs->test['subject'], $result);

		$result = $this->CakeEmail->transport();
		$this->assertEquals($configs->test['transport'], $result);

		$result = $this->CakeEmail->transportClass();
		$this->assertTrue($result instanceof DebugTransport);

		$result = $this->CakeEmail->send('This is the message');

		$this->assertTrue((bool)strpos($result['headers'], 'Message-ID: '));
		$this->assertTrue((bool)strpos($result['headers'], 'To: '));
	}

/**
 * testViewRender method
 *
 * @return void
 */
	public function testViewRender() {
		$result = $this->CakeEmail->viewRender();
		$this->assertEquals('View', $result);

		$result = $this->CakeEmail->viewRender('Theme');
		$this->assertInstanceOf('CakeEmail', $result);

		$result = $this->CakeEmail->viewRender();
		$this->assertEquals('Theme', $result);
	}

/**
 * testEmailFormat method
 *
 * @return void
 */
	public function testEmailFormat() {
		$result = $this->CakeEmail->emailFormat();
		$this->assertEquals('text', $result);

		$result = $this->CakeEmail->emailFormat('html');
		$this->assertInstanceOf('CakeEmail', $result);

		$result = $this->CakeEmail->emailFormat();
		$this->assertEquals('html', $result);

		$this->setExpectedException('SocketException');
		$result = $this->CakeEmail->emailFormat('invalid');
	}

/**
 * Tests that it is possible to add charset configuration to a CakeEmail object
 *
 * @return void
 */
	public function testConfigCharset() {
		$email = new CakeEmail();
		$this->assertEquals(Configure::read('App.encoding'), $email->charset);
		$this->assertEquals(Configure::read('App.encoding'), $email->headerCharset);

		$email = new CakeEmail(array('charset' => 'iso-2022-jp', 'headerCharset' => 'iso-2022-jp-ms'));
		$this->assertEquals('iso-2022-jp', $email->charset);
		$this->assertEquals('iso-2022-jp-ms', $email->headerCharset);

		$email = new CakeEmail(array('charset' => 'iso-2022-jp'));
		$this->assertEquals('iso-2022-jp', $email->charset);
		$this->assertEquals('iso-2022-jp', $email->headerCharset);

		$email = new CakeEmail(array('headerCharset' => 'iso-2022-jp-ms'));
		$this->assertEquals(Configure::read('App.encoding'), $email->charset);
		$this->assertEquals('iso-2022-jp-ms', $email->headerCharset);
	}

/**
 * Tests that the header is encoded using the configured headerCharset
 *
 * @return void
 */
	public function testHeaderEncoding() {
		$this->skipIf(!function_exists('mb_convert_encoding'));
		$email = new CakeEmail(array('headerCharset' => 'iso-2022-jp-ms', 'transport' => 'Debug'));
		$email->subject('あれ？もしかしての前と');
		$headers = $email->getHeaders(array('subject'));
		$expected = "?ISO-2022-JP?B?GyRCJCIkbCEpJGIkNyQrJDckRiROQTAkSBsoQg==?=";
		$this->assertContains($expected, $headers['Subject']);

		$email->to('someone@example.com')->from('someone@example.com');
		$result = $email->send('ってテーブルを作ってやってたらう');
		$this->assertContains('ってテーブルを作ってやってたらう', $result['message']);
	}

/**
 * Tests that the body is encoded using the configured charset
 *
 * @return void
 */
	public function testBodyEncoding() {
		$this->skipIf(!function_exists('mb_convert_encoding'));
		$email = new CakeEmail(array(
			'charset' => 'iso-2022-jp',
			'headerCharset' => 'iso-2022-jp-ms',
			'transport' => 'Debug'
		));
		$email->subject('あれ？もしかしての前と');
		$headers = $email->getHeaders(array('subject'));
		$expected = "?ISO-2022-JP?B?GyRCJCIkbCEpJGIkNyQrJDckRiROQTAkSBsoQg==?=";
		$this->assertContains($expected, $headers['Subject']);

		$email->to('someone@example.com')->from('someone@example.com');
		$result = $email->send('ってテーブルを作ってやってたらう');
		$this->assertContains('Content-Type: text/plain; charset=ISO-2022-JP', $result['headers']);
		$this->assertContains(mb_convert_encoding('ってテーブルを作ってやってたらう', 'ISO-2022-JP'), $result['message']);
	}

/**
 * Tests that the body is encoded using the configured charset (Japanese standard encoding)
 *
 * @return void
 */
	public function testBodyEncodingIso2022Jp() {
		$this->skipIf(!function_exists('mb_convert_encoding'));
		$email = new CakeEmail(array(
			'charset' => 'iso-2022-jp',
			'headerCharset' => 'iso-2022-jp',
			'transport' => 'Debug'
		));
		$email->subject('あれ？もしかしての前と');
		$headers = $email->getHeaders(array('subject'));
		$expected = "?ISO-2022-JP?B?GyRCJCIkbCEpJGIkNyQrJDckRiROQTAkSBsoQg==?=";
		$this->assertContains($expected, $headers['Subject']);

		$email->to('someone@example.com')->from('someone@example.com');
		$result = $email->send('①㈱');
		$this->assertTextContains("Content-Type: text/plain; charset=ISO-2022-JP", $result['headers']);
		$this->assertTextNotContains("Content-Type: text/plain; charset=ISO-2022-JP-MS", $result['headers']); // not charset=iso-2022-jp-ms
		$this->assertTextNotContains(mb_convert_encoding('①㈱', 'ISO-2022-JP-MS'), $result['message']);
	}

/**
 * Tests that the body is encoded using the configured charset (Japanese irregular encoding, but sometime use this)
 *
 * @return void
 */
	public function testBodyEncodingIso2022JpMs() {
		$this->skipIf(!function_exists('mb_convert_encoding'));
		$email = new CakeEmail(array(
			'charset' => 'iso-2022-jp-ms',
			'headerCharset' => 'iso-2022-jp-ms',
			'transport' => 'Debug'
		));
		$email->subject('あれ？もしかしての前と');
		$headers = $email->getHeaders(array('subject'));
		$expected = "?ISO-2022-JP?B?GyRCJCIkbCEpJGIkNyQrJDckRiROQTAkSBsoQg==?=";
		$this->assertContains($expected, $headers['Subject']);

		$email->to('someone@example.com')->from('someone@example.com');
		$result = $email->send('①㈱');
		$this->assertTextContains("Content-Type: text/plain; charset=ISO-2022-JP", $result['headers']);
		$this->assertTextNotContains("Content-Type: text/plain; charset=iso-2022-jp-ms", $result['headers']); // not charset=iso-2022-jp-ms
		$this->assertContains(mb_convert_encoding('①㈱', 'ISO-2022-JP-MS'), $result['message']);
	}

	protected function _checkContentTransferEncoding($message, $charset) {
		$boundary = '--alt-' . $this->CakeEmail->getBoundary();
		$result['text'] = false;
		$result['html'] = false;
		$length = count($message);
		for ($i = 0; $i < $length; ++$i) {
			if ($message[$i] == $boundary) {
				$flag = false;
				$type = '';
				while (!preg_match('/^$/', $message[$i])) {
					if (preg_match('/^Content-Type: text\/plain/', $message[$i])) {
						$type = 'text';
					}
					if (preg_match('/^Content-Type: text\/html/', $message[$i])) {
						$type = 'html';
					}
					if ($message[$i] === 'Content-Transfer-Encoding: ' . $charset) {
						$flag = true;
					}
					++$i;
				}
				$result[$type] = $flag;
			}
		}
		return $result['text'] && $result['html'];
	}

/**
 * Test CakeEmail::_encode function
 *
 * @return void
 */
	public function testEncode() {
		$this->skipIf(!function_exists('mb_convert_encoding'));

		$this->CakeEmail->headerCharset = 'ISO-2022-JP';
		$result = $this->CakeEmail->encode('日本語');
		$expected = '=?ISO-2022-JP?B?GyRCRnxLXDhsGyhC?=';
		$this->assertSame($expected, $result);

		$this->CakeEmail->headerCharset = 'ISO-2022-JP';
		$result = $this->CakeEmail->encode('長い長い長いSubjectの場合はfoldingするのが正しいんだけどいったいどうなるんだろう？');
		$expected = "=?ISO-2022-JP?B?GyRCRDkkJEQ5JCREOSQkGyhCU3ViamVjdBskQiROPmw5ZyRPGyhCZm9s?=\r\n" .
			" =?ISO-2022-JP?B?ZGluZxskQiQ5JGskTiQsQDUkNyQkJHMkQCQxJEkkJCRDJD8kJCRJGyhC?=\r\n" .
			" =?ISO-2022-JP?B?GyRCJCYkSiRrJHMkQCRtJCYhKRsoQg==?=";
		$this->assertSame($expected, $result);
	}

/**
 * Tests charset setter/getter
 *
 * @return void
 */
	public function testCharset() {
		$this->CakeEmail->charset('UTF-8');
		$this->assertSame($this->CakeEmail->charset(), 'UTF-8');

		$this->CakeEmail->charset('ISO-2022-JP');
		$this->assertSame($this->CakeEmail->charset(), 'ISO-2022-JP');

		$charset = $this->CakeEmail->charset('Shift_JIS');
		$this->assertSame($charset, 'Shift_JIS');
	}

/**
 * Tests headerCharset setter/getter
 *
 * @return void
 */
	public function testHeaderCharset() {
		$this->CakeEmail->headerCharset('UTF-8');
		$this->assertSame($this->CakeEmail->headerCharset(), 'UTF-8');

		$this->CakeEmail->headerCharset('ISO-2022-JP');
		$this->assertSame($this->CakeEmail->headerCharset(), 'ISO-2022-JP');

		$charset = $this->CakeEmail->headerCharset('Shift_JIS');
		$this->assertSame($charset, 'Shift_JIS');
	}

/**
 * Tests for compatible check.
 *          charset property and       charset() method.
 *    headerCharset property and headerCharset() method.
 */
	public function testCharsetsCompatible() {
		$this->skipIf(!function_exists('mb_convert_encoding'));

		$checkHeaders = array(
			'from' => true,
			'to' => true,
			'cc' => true,
			'subject' => true,
		);

		// Header Charset : null (used by default UTF-8)
		//   Body Charset : ISO-2022-JP
		$oldStyleEmail = $this->_getEmailByOldStyleCharset('iso-2022-jp', null);
		$oldStyleHeaders = $oldStyleEmail->getHeaders($checkHeaders);

		$newStyleEmail = $this->_getEmailByNewStyleCharset('iso-2022-jp', null);
		$newStyleHeaders = $newStyleEmail->getHeaders($checkHeaders);

		$this->assertSame($oldStyleHeaders['From'], $newStyleHeaders['From']);
		$this->assertSame($oldStyleHeaders['To'], $newStyleHeaders['To']);
		$this->assertSame($oldStyleHeaders['Cc'], $newStyleHeaders['Cc']);
		$this->assertSame($oldStyleHeaders['Subject'], $newStyleHeaders['Subject']);

		// Header Charset : UTF-8
		//   Boby Charset : ISO-2022-JP
		$oldStyleEmail = $this->_getEmailByOldStyleCharset('iso-2022-jp', 'utf-8');
		$oldStyleHeaders = $oldStyleEmail->getHeaders($checkHeaders);

		$newStyleEmail = $this->_getEmailByNewStyleCharset('iso-2022-jp', 'utf-8');
		$newStyleHeaders = $newStyleEmail->getHeaders($checkHeaders);

		$this->assertSame($oldStyleHeaders['From'], $newStyleHeaders['From']);
		$this->assertSame($oldStyleHeaders['To'], $newStyleHeaders['To']);
		$this->assertSame($oldStyleHeaders['Cc'], $newStyleHeaders['Cc']);
		$this->assertSame($oldStyleHeaders['Subject'], $newStyleHeaders['Subject']);

		// Header Charset : ISO-2022-JP
		//   Boby Charset : UTF-8
		$oldStyleEmail = $this->_getEmailByOldStyleCharset('utf-8', 'iso-2022-jp');
		$oldStyleHeaders = $oldStyleEmail->getHeaders($checkHeaders);

		$newStyleEmail = $this->_getEmailByNewStyleCharset('utf-8', 'iso-2022-jp');
		$newStyleHeaders = $newStyleEmail->getHeaders($checkHeaders);

		$this->assertSame($oldStyleHeaders['From'], $newStyleHeaders['From']);
		$this->assertSame($oldStyleHeaders['To'], $newStyleHeaders['To']);
		$this->assertSame($oldStyleHeaders['Cc'], $newStyleHeaders['Cc']);
		$this->assertSame($oldStyleHeaders['Subject'], $newStyleHeaders['Subject']);
	}

	protected function _getEmailByOldStyleCharset($charset, $headerCharset) {
		$email = new CakeEmail(array('transport' => 'Debug'));

		if (! empty($charset)) {
			$email->charset = $charset;
		}
		if (! empty($headerCharset)) {
			$email->headerCharset = $headerCharset;
		}

		$email->from('someone@example.com', 'どこかの誰か');
		$email->to('someperson@example.jp', 'どこかのどなたか');
		$email->cc('miku@example.net', 'ミク');
		$email->subject('テストメール');
		$email->send('テストメールの本文');

		return $email;
	}

	protected function _getEmailByNewStyleCharset($charset, $headerCharset) {
		$email = new CakeEmail(array('transport' => 'Debug'));

		if (! empty($charset)) {
			$email->charset($charset);
		}
		if (! empty($headerCharset)) {
			$email->headerCharset($headerCharset);
		}

		$email->from('someone@example.com', 'どこかの誰か');
		$email->to('someperson@example.jp', 'どこかのどなたか');
		$email->cc('miku@example.net', 'ミク');
		$email->subject('テストメール');
		$email->send('テストメールの本文');

		return $email;
	}

	public function testWrapLongLine() {
		$message = '<a href="http://cakephp.org">' . str_repeat('x', CakeEmail::LINE_LENGTH_MUST) . "</a>";

		$this->CakeEmail->reset();
		$this->CakeEmail->transport('Debug');
		$this->CakeEmail->from('cake@cakephp.org');
		$this->CakeEmail->to('cake@cakephp.org');
		$this->CakeEmail->subject('Wordwrap Test');
		$this->CakeEmail->config(array('empty'));
		$result = $this->CakeEmail->send($message);
		$expected = "<a\r\n" . 'href="http://cakephp.org">' . str_repeat('x', CakeEmail::LINE_LENGTH_MUST - 26) . "\r\n" .
			str_repeat('x', 26) . "\r\n</a>\r\n\r\n";
		$this->assertEquals($expected, $result['message']);
		$this->assertLineLengths($result['message']);

		$str1 = "a ";
		$str2 = " b";
		$length = strlen($str1) + strlen($str2);
		$message = $str1 . str_repeat('x', CakeEmail::LINE_LENGTH_MUST - $length - 1) . $str2;

		$result = $this->CakeEmail->send($message);
		$expected = "{$message}\r\n\r\n";
		$this->assertEquals($expected, $result['message']);
		$this->assertLineLengths($result['message']);

		$message = $str1 . str_repeat('x', CakeEmail::LINE_LENGTH_MUST - $length) . $str2;

		$result = $this->CakeEmail->send($message);
		$expected = "{$message}\r\n\r\n";
		$this->assertEquals($expected, $result['message']);
		$this->assertLineLengths($result['message']);

		$message = $str1 . str_repeat('x', CakeEmail::LINE_LENGTH_MUST - $length + 1) . $str2;

		$result = $this->CakeEmail->send($message);
		$expected = $str1 . str_repeat('x', CakeEmail::LINE_LENGTH_MUST - $length + 1) . sprintf("\r\n%s\r\n\r\n", trim($str2));
		$this->assertEquals($expected, $result['message']);
		$this->assertLineLengths($result['message']);
	}

	public function testWrapWithTagsAcrossLines() {
		$str = <<<HTML
<table>
<th align="right" valign="top"
        style="font-weight: bold">The tag is across multiple lines</th>
</table>
HTML;
		$length = strlen($str);
		$message = $str . str_repeat('x', CakeEmail::LINE_LENGTH_MUST + 1);

		$this->CakeEmail->reset();
		$this->CakeEmail->transport('Debug');
		$this->CakeEmail->from('cake@cakephp.org');
		$this->CakeEmail->to('cake@cakephp.org');
		$this->CakeEmail->subject('Wordwrap Test');
		$this->CakeEmail->config(array('empty'));
		$result = $this->CakeEmail->send($message);
		$message = str_replace("\r\n", "\n", substr($message, 0, -9));
		$message = str_replace("\n", "\r\n", $message);
		$expected = "{$message}\r\nxxxxxxxxx\r\n\r\n";
		$this->assertEquals($expected, $result['message']);
		$this->assertLineLengths($result['message']);
	}

	public function testWrapIncludeLessThanSign() {
		$str = 'foo<bar';
		$length = strlen($str);
		$message = $str . str_repeat('x', CakeEmail::LINE_LENGTH_MUST - $length + 1);

		$this->CakeEmail->reset();
		$this->CakeEmail->transport('Debug');
		$this->CakeEmail->from('cake@cakephp.org');
		$this->CakeEmail->to('cake@cakephp.org');
		$this->CakeEmail->subject('Wordwrap Test');
		$this->CakeEmail->config(array('empty'));
		$result = $this->CakeEmail->send($message);
		$message = substr($message, 0, -1);
		$expected = "{$message}\r\nx\r\n\r\n";
		$this->assertEquals($expected, $result['message']);
		$this->assertLineLengths($result['message']);
	}

	public function testWrapForJapaneseEncoding() {
		$this->skipIf(!function_exists('mb_convert_encoding'));

		$message = mb_convert_encoding('受け付けました', 'iso-2022-jp', 'UTF-8');

		$this->CakeEmail->reset();
		$this->CakeEmail->transport('Debug');
		$this->CakeEmail->from('cake@cakephp.org');
		$this->CakeEmail->to('cake@cakephp.org');
		$this->CakeEmail->subject('Wordwrap Test');
		$this->CakeEmail->config(array('empty'));
		$this->CakeEmail->charset('iso-2022-jp');
		$this->CakeEmail->headerCharset('iso-2022-jp');
		$result = $this->CakeEmail->send($message);
		$expected = "{$message}\r\n\r\n";
		$this->assertEquals($expected, $result['message']);
	}

/**
 * CakeEmailTest::assertLineLengths()
 *
 * @param string $message
 * @return void
 */
	public function assertLineLengths($message) {
		$lines = explode("\r\n", $message);
		foreach ($lines as $line) {
			$this->assertTrue(strlen($line) <= CakeEmail::LINE_LENGTH_MUST,
				'Line length exceeds the max. limit of CakeEmail::LINE_LENGTH_MUST');
		}
	}

}
