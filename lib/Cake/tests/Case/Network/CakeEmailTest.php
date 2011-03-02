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
		return $this->_formatAddress($address);
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
			'Date' => date(DATE_RFC2822)
		);
		$this->assertIdentical($this->CakeEmail->getHeaders(), $expected);

		$this->CakeEmail->addHeaders(array('X-Something' => 'very nice', 'X-Other' => 'cool'));
		$expected = array(
			'X-Something' => 'very nice',
			'X-Other' => 'cool',
			'X-Mailer' => 'CakePHP Email Component',
			'Date' => date(DATE_RFC2822)
		);
		$this->assertIdentical($this->CakeEmail->getHeaders(), $expected);

		$this->CakeEmail->setFrom('cake@cakephp.org');
		$this->assertIdentical($this->CakeEmail->getHeaders(), $expected);

		$expected = array(
			'From' => 'cake@cakephp.org',
			'X-Something' => 'very nice',
			'X-Other' => 'cool',
			'X-Mailer' => 'CakePHP Email Component',
			'Date' => date(DATE_RFC2822)
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
			'Date' => date(DATE_RFC2822)
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
		$expected = array(WWW_ROOT . 'index.php');
		$this->assertIdentical($this->CakeEmail->getAttachments(), $expected);

		$this->CakeEmail->setAttachments(array());
		$this->assertIdentical($this->CakeEmail->getAttachments(), array());

		$this->CakeEmail->setAttachments(WWW_ROOT . 'index.php');
		$this->CakeEmail->addAttachments(WWW_ROOT . 'test.php');
		$this->CakeEmail->addAttachments(array(WWW_ROOT . 'test.php', WWW_ROOT . '.htaccess'));
		$expected = array(WWW_ROOT . 'index.php', WWW_ROOT . 'test.php', WWW_ROOT . '.htaccess');
		$this->assertIdentical(array_values($this->CakeEmail->getAttachments()), $expected);
	}

/**
 * testSend method
 *
 * @return void
 */
	public function testSend() {
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

}
