<?php
/**
 * HttpSocketTest file
 *
 * PHP 5
 *
 * CakePHP(tm) Tests <http://book.cakephp.org/view/1196/Testing>
 * Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/view/1196/Testing CakePHP(tm) Tests
 * @package       cake
 * @subpackage    cake.tests.cases.libs
 * @since         CakePHP(tm) v 1.2.0.4206
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
App::import('Core', 'HttpResponse');

/**
 * HttpResponseTest class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs
 */
class HttpResponseTest extends CakeTestCase {
/**
 * This function sets up a HttpResponse
 *
 * @return void
 */
	public function setUp() {
		$this->HttpResponse = new HttpResponse();
	}

/**
 * testBody
 *
 * @return void
 */
	public function testBody() {
		$this->HttpResponse->body = 'testing';
		$this->assertEqual($this->HttpResponse->body(), 'testing');

		$this->HttpResponse->body = null;
		$this->assertIdentical($this->HttpResponse->body(), '');
	}

/**
 * testToString
 *
 * @return void
 */
	public function testToString() {
		$this->HttpResponse->body = 'other test';
		$this->assertEqual($this->HttpResponse->body(), 'other test');
		$this->assertEqual((string)$this->HttpResponse, 'other test');
		$this->assertTrue(strpos($this->HttpResponse, 'test') > 0);

		$this->HttpResponse->body = null;
		$this->assertEqual((string)$this->HttpResponse, '');
	}

/**
 * testGetHeadr
 *
 * @return void
 */
	public function testGetHeader() {
		$this->HttpResponse->headers = array(
			'foo' => 'Bar',
			'Some' => 'ok',
			'HeAdEr' => 'value',
			'content-Type' => 'text/plain'
		);

		$this->assertEqual($this->HttpResponse->getHeader('foo'), 'Bar');
		$this->assertEqual($this->HttpResponse->getHeader('Foo'), 'Bar');
		$this->assertEqual($this->HttpResponse->getHeader('FOO'), 'Bar');
		$this->assertEqual($this->HttpResponse->getHeader('header'), 'value');
		$this->assertEqual($this->HttpResponse->getHeader('Content-Type'), 'text/plain');
		$this->assertIdentical($this->HttpResponse->getHeader(0), null);
	}

/**
 * testIsOk
 *
 * @return void
 */
	public function testIsOk() {
		$this->HttpResponse->code = 0;
		$this->assertFalse($this->HttpResponse->isOk());
		$this->HttpResponse->code = -1;
		$this->assertFalse($this->HttpResponse->isOk());
		$this->HttpResponse->code = 201;
		$this->assertFalse($this->HttpResponse->isOk());
		$this->HttpResponse->code = 'what?';
		$this->assertFalse($this->HttpResponse->isOk());
		$this->HttpResponse->code = 200;
		$this->assertTrue($this->HttpResponse->isOk());
	}

/**
 * testArrayAccess
 *
 * @return void
 */
	public function testArrayAccess() {
		$this->HttpResponse->httpVersion = 'HTTP/1.1';
		$this->HttpResponse->code = 200;
		$this->HttpResponse->reasonPhrase = 'OK';
		$this->HttpResponse->headers = array(
			'Server' => 'CakePHP',
			'ContEnt-Type' => 'text/plain'
		);
		$this->HttpResponse->cookies = array(
			'foo' => array('value' => 'bar'),
			'bar' => array('value' => 'foo')
		);
		$this->HttpResponse->body = 'This is a test!';
		$this->HttpResponse->raw = "HTTP/1.1 200 OK\r\nServer: CakePHP\r\nContEnt-Type: text/plain\r\n\r\nThis is a test!";

		$expected1 = 'HTTP/1.1 200 OK';
		$this->assertEqual($this->HttpResponse['raw']['status-line'], $expected1);
		$expected2 = "Server: CakePHP\r\nContEnt-Type: text/plain";
		$this->assertEqual($this->HttpResponse['raw']['header'], $expected2);
		$expected3 = 'This is a test!';
		$this->assertEqual($this->HttpResponse['raw']['body'], $expected3);
		$expected = $expected1 . "\r\n" . $expected2 . "\r\n\r\n" . $expected3;
		$this->assertEqual($this->HttpResponse['raw']['response'], $expected);

		$expected = 'HTTP/1.1';
		$this->assertEqual($this->HttpResponse['status']['http-version'], $expected);
		$expected = 200;
		$this->assertEqual($this->HttpResponse['status']['code'], $expected);
		$expected = 'OK';
		$this->assertEqual($this->HttpResponse['status']['reason-phrase'], $expected);

		$expected = array(
			'Server' => 'CakePHP',
			'ContEnt-Type' => 'text/plain'
		);
		$this->assertEqual($this->HttpResponse['header'], $expected);

		$expected = 'This is a test!';
		$this->assertEqual($this->HttpResponse['body'], $expected);

		$expected = array(
			'foo' => array('value' => 'bar'),
			'bar' => array('value' => 'foo')
		);
		$this->assertEqual($this->HttpResponse['cookies'], $expected);
	}

}