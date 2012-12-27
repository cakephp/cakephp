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

/**
 * Test body()
 *
 * @return void
 */
	public function testBody() {
		$data = [
			'property' => 'value'
		];
		$encoded = json_encode($data);

		$response = new Response([], $encoded);
		$result = $response->body('json_decode');
		$this->assertEquals($data['property'], $result->property);
		$this->assertEquals($encoded, $response->body());
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
			'HTTP/1.1 201 Created',
			'Content-Type: text/html'
		];
		$response = new Response($headers, 'ok');
		$this->assertTrue($response->isOk());

		$headers = [
			'HTTP/1.1 202 Accepted',
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

/**
 * Test parsing / getting cookies.
 *
 * @return void
 */
	public function testCookie() {
		$headers = [
			'HTTP/1.0 200 Ok',
			'Set-Cookie: test=value',
			'Set-Cookie: session=123abc',
			'Set-Cookie: expiring=soon; Expires=Wed, 09-Jun-2021 10:18:14 GMT; Path=/; HttpOnly; Secure',
		];
		$response = new Response($headers, '');
		$this->assertEquals('value', $response->cookie('test'));
		$this->assertEquals('123abc', $response->cookie('session'));
		$this->assertEquals('soon', $response->cookie('expiring'));

		$result = $response->cookie('expiring', true);
		$this->assertTrue($result['httponly']);
		$this->assertTrue($result['secure']);
		$this->assertEquals(
			'Wed, 09-Jun-2021 10:18:14 GMT',
			$result['expires']
		);
		$this->assertEquals('/', $result['path']);

		$result = $response->header('set-cookie');
		$this->assertCount(3, $result, 'Should be an array.');
	}

/**
 * Test statusCode()
 *
 * @return void
 */
	public function testStatusCode() {
		$headers = [
			'HTTP/1.0 404 Not Found',
			'Content-Type: text/html'
		];
		$response = new Response($headers, '');
		$this->assertEquals(404, $response->statusCode());
	}

/**
 * Test reading the encoding out.
 *
 * @return void
 */
	public function testEncoding() {
		$headers = [
			'HTTP/1.0 200 Ok',
		];
		$response = new Response($headers, '');
		$this->assertNull($response->encoding());

		$headers = [
			'HTTP/1.0 200 Ok',
			'Content-Type: text/html'
		];
		$response = new Response($headers, '');
		$this->assertNull($response->encoding());

		$headers = [
			'HTTP/1.0 200 Ok',
			'Content-Type: text/html; charset="UTF-8"'
		];
		$response = new Response($headers, '');
		$this->assertEquals('UTF-8', $response->encoding());

		$headers = [
			'HTTP/1.0 200 Ok',
			"Content-Type: text/html; charset='ISO-8859-1'"
		];
		$response = new Response($headers, '');
		$this->assertEquals('ISO-8859-1', $response->encoding());
	}

}
