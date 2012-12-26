<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         CakePHP(tm) v 3.0.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
namespace Cake\Test\TestCase\Network\Http;

use Cake\Network\Http\Response;
use Cake\TestSuite\TestCase;

/**
 * HTTP response test.
 */
class ResponseTest extends TestCase {

	public function testHeaderParsing() {
		$headers = [
			'HTTP/1.1 200 OK',
			'Content-Type : text/html;charset="UTF-8"',
			'date: Tue, 25 Dec 2012 04:43:47 GMT',
		];
		$response = new Response($headers, 'ok');

		$this->assertEquals(200, $response->statusCode());
		$this->assertEquals(
			'text/html;charset="UTF-8"',
			$response->header('content-type')
		);
		$this->assertEquals(
			'Tue, 25 Dec 2012 04:43:47 GMT',
			$response->header('Date')
		);
	}

	public function testContent() {
		$this->markTestIncomplete();
	}

/**
 * Test isOk()
 *
 * @return void
 */
	public function testIsOk() {
		$headers = [
			'HTTP/1.1 200 OK',
			'Content-Type: text/html'
		];
		$response = new Response($headers, 'ok');
		$this->assertTrue($response->isOk());

		$headers = [
			'HTTP/1.1 301 Moved Permanently',
			'Content-Type: text/html'
		];
		$response = new Response($headers, '');
		$this->assertFalse($response->isOk());

		$headers = [
			'HTTP/1.0 404 Not Found',
			'Content-Type: text/html'
		];
		$response = new Response($headers, '');
		$this->assertFalse($response->isOk());
	}

/**
 * Test isRedirect()
 *
 * @return void
 */
	public function testIsRedirect() {
		$headers = [
			'HTTP/1.1 200 OK',
			'Content-Type: text/html'
		];
		$response = new Response($headers, 'ok');
		$this->assertFalse($response->isRedirect());

		$headers = [
			'HTTP/1.1 301 Moved Permanently',
			'Location: /',
			'Content-Type: text/html'
		];
		$response = new Response($headers, '');
		$this->assertTrue($response->isRedirect());

		$headers = [
			'HTTP/1.0 404 Not Found',
			'Content-Type: text/html'
		];
		$response = new Response($headers, '');
		$this->assertFalse($response->isRedirect());
	}

	public function testCookie() {
		$this->markTestIncomplete();
	}

	public function testStatusCode() {
		$this->markTestIncomplete();
	}

	public function testEncoding() {
		$this->markTestIncomplete();
	}

}
