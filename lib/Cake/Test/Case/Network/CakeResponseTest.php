<?php
/**
 * CakeResponse Test case file.
 *
 * PHP 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2011, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2011, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       Cake.Test.Case.Network
 * @since         CakePHP(tm) v 2.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
App::uses('CakeResponse', 'Network');

class CakeResponseTest extends CakeTestCase {

/**
 * Setup for tests
 *
 * @return void
 */
	public function setUp() {
		ob_start();
	}

/**
 * Cleanup after tests
 *
 * @return void
 */
	public function tearDown() {
		ob_end_clean();
	}

/**
* Tests the request object constructor
*
*/
	public function testConstruct() {
		$response = new CakeResponse();
		$this->assertNull($response->body());
		$this->assertEquals($response->charset(), 'UTF-8');
		$this->assertEquals($response->type(), 'text/html');
		$this->assertEquals($response->statusCode(), 200);

		$options = array(
			'body' => 'This is the body',
			'charset' => 'my-custom-charset',
			'type' => 'mp3',
			'status' => '203'
		);
		$response = new CakeResponse($options);
		$this->assertEquals($response->body(), 'This is the body');
		$this->assertEquals($response->charset(), 'my-custom-charset');
		$this->assertEquals($response->type(), 'audio/mpeg');
		$this->assertEquals($response->statusCode(), 203);
	}

/**
* Tests the body method
*
*/
	public function testBody() {
		$response = new CakeResponse();
		$this->assertNull($response->body());
		$response->body('Response body');
		$this->assertEquals($response->body(), 'Response body');
		$this->assertEquals($response->body('Changed Body'), 'Changed Body');
	}

/**
* Tests the charset method
*
*/
	public function testCharset() {
		$response = new CakeResponse();
		$this->assertEquals($response->charset(), 'UTF-8');
		$response->charset('iso-8859-1');
		$this->assertEquals($response->charset(), 'iso-8859-1');
		$this->assertEquals($response->charset('UTF-16'), 'UTF-16');
	}

/**
* Tests the statusCode method
*
* @expectedException CakeException
*/
	public function testStatusCode() {
		$response = new CakeResponse();
		$this->assertEquals($response->statusCode(), 200);
		$response->statusCode(404);
		$this->assertEquals($response->statusCode(), 404);
		$this->assertEquals($response->statusCode(500), 500);

		//Throws exception
		$response->statusCode(1001);
	}

/**
* Tests the type method
*
*/
	public function testType() {
		$response = new CakeResponse();
		$this->assertEquals($response->type(), 'text/html');
		$response->type('pdf');
		$this->assertEquals($response->type(), 'application/pdf');
		$this->assertEquals($response->type('application/crazy-mime'), 'application/crazy-mime');
		$this->assertEquals($response->type('json'), 'application/json');
		$this->assertEquals($response->type('wap'), 'text/vnd.wap.wml');
		$this->assertEquals($response->type('xhtml-mobile'), 'application/vnd.wap.xhtml+xml');
		$this->assertEquals($response->type('csv'), 'text/csv');

		$response->type(array('keynote' => 'application/keynote'));
		$this->assertEquals($response->type('keynote'), 'application/keynote');

		$this->assertFalse($response->type('wackytype'));
	}

/**
* Tests the header method
*
*/
	public function testHeader() {
		$response = new CakeResponse();
		$headers = array();
		$this->assertEquals($response->header(), $headers);

		$response->header('Location', 'http://example.com');
		$headers += array('Location' => 'http://example.com');
		$this->assertEquals($response->header(), $headers);

		//Headers with the same name are overwritten
		$response->header('Location', 'http://example2.com');
		$headers = array('Location' => 'http://example2.com');
		$this->assertEquals($response->header(), $headers);

		$response->header(array('WWW-Authenticate' => 'Negotiate'));
		$headers += array('WWW-Authenticate' => 'Negotiate');
		$this->assertEquals($response->header(), $headers);

		$response->header(array('WWW-Authenticate' => 'Not-Negotiate'));
		$headers['WWW-Authenticate'] = 'Not-Negotiate';
		$this->assertEquals($response->header(), $headers);

		$response->header(array('Age' => 12, 'Allow' => 'GET, HEAD'));
		$headers += array('Age' => 12, 'Allow' => 'GET, HEAD');
		$this->assertEquals($response->header(), $headers);

		// String headers are allowed
		$response->header('Content-Language: da');
		$headers += array('Content-Language' => 'da');
		$this->assertEquals($response->header(), $headers);

		$response->header('Content-Language: da');
		$headers += array('Content-Language' => 'da');
		$this->assertEquals($response->header(), $headers);

		$response->header(array('Content-Encoding: gzip', 'Vary: *', 'Pragma' => 'no-cache'));
		$headers += array('Content-Encoding' => 'gzip', 'Vary' => '*', 'Pragma' => 'no-cache');
		$this->assertEquals($response->header(), $headers);
	}

/**
* Tests the send method
*
*/
	public function testSend() {
		$response = $this->getMock('CakeResponse', array('_sendHeader', '_sendContent'));
		$response->header(array(
			'Content-Language' => 'es',
			'WWW-Authenticate' => 'Negotiate'
		));
		$response->body('the response body');
		$response->expects($this->once())->method('_sendContent')->with('the response body');
		$response->expects($this->at(0))
			->method('_sendHeader')->with('HTTP/1.1 200 OK');
		$response->expects($this->at(1))
			->method('_sendHeader')->with('Content-Type', 'text/html; charset=UTF-8');
		$response->expects($this->at(2))
			->method('_sendHeader')->with('Content-Language', 'es');
		$response->expects($this->at(3))
			->method('_sendHeader')->with('WWW-Authenticate', 'Negotiate');
		$response->send();
	}

/**
* Tests the send method and changing the content type
*
*/
	public function testSendChangingContentYype() {
		$response = $this->getMock('CakeResponse', array('_sendHeader', '_sendContent'));
		$response->type('mp3');
		$response->body('the response body');
		$response->expects($this->once())->method('_sendContent')->with('the response body');
		$response->expects($this->at(0))
			->method('_sendHeader')->with('HTTP/1.1 200 OK');
		$response->expects($this->at(1))
			->method('_sendHeader')->with('Content-Type', 'audio/mpeg; charset=UTF-8');
		$response->send();
	}

/**
* Tests the send method and changing the content type
*
*/
	public function testSendChangingContentType() {
		$response = $this->getMock('CakeResponse', array('_sendHeader', '_sendContent'));
		$response->type('mp3');
		$response->body('the response body');
		$response->expects($this->once())->method('_sendContent')->with('the response body');
		$response->expects($this->at(0))
			->method('_sendHeader')->with('HTTP/1.1 200 OK');
		$response->expects($this->at(1))
			->method('_sendHeader')->with('Content-Type', 'audio/mpeg; charset=UTF-8');
		$response->send();
	}

/**
* Tests the send method and changing the content type
*
*/
	public function testSendWithLocation() {
		$response = $this->getMock('CakeResponse', array('_sendHeader', '_sendContent'));
		$response->header('Location', 'http://www.example.com');
		$response->expects($this->at(0))
			->method('_sendHeader')->with('HTTP/1.1 302 Found');
		$response->expects($this->at(1))
			->method('_sendHeader')->with('Content-Type', 'text/html; charset=UTF-8');
		$response->expects($this->at(2))
			->method('_sendHeader')->with('Location', 'http://www.example.com');
		$response->send();
	}

/**
* Tests the disableCache method
*
*/
	public function testDisableCache() {
		$response = new CakeResponse();
		$expected = array(
			'Expires' => 'Mon, 26 Jul 1997 05:00:00 GMT',
			'Last-Modified' => gmdate("D, d M Y H:i:s") . " GMT",
			'Cache-Control' => 'no-store, no-cache, must-revalidate, post-check=0, pre-check=0',
			'Pragma' => 'no-cache'
		);
		$response->disableCache();
		$this->assertEquals($response->header(), $expected);
	}

/**
* Tests the cache method
*
*/
	public function testCache() {
		$response = new CakeResponse();
		$since = time();
		$time = '+1 day';
		$expected = array(
			'Date' => gmdate("D, j M Y G:i:s ", $since) . 'GMT',
			'Last-Modified' => gmdate("D, j M Y G:i:s ", $since) . 'GMT',
			'Expires' => gmdate("D, j M Y H:i:s", strtotime($time)) . " GMT",
			'Cache-Control' => 'public, max-age=' . (strtotime($time) - time()),
			'Pragma' => 'cache'
		);
		$response->cache($since);
		$this->assertEquals($response->header(), $expected);

		$response = new CakeResponse();
		$since = time();
		$time = '+5 day';
		$expected = array(
			'Date' => gmdate("D, j M Y G:i:s ", $since) . 'GMT',
			'Last-Modified' => gmdate("D, j M Y G:i:s ", $since) . 'GMT',
			'Expires' => gmdate("D, j M Y H:i:s", strtotime($time)) . " GMT",
			'Cache-Control' => 'public, max-age=' . (strtotime($time) - time()),
			'Pragma' => 'cache'
		);
		$response->cache($since, $time);
		$this->assertEquals($response->header(), $expected);

		$response = new CakeResponse();
		$since = time();
		$time = time();
		$expected = array(
			'Date' => gmdate("D, j M Y G:i:s ", $since) . 'GMT',
			'Last-Modified' => gmdate("D, j M Y G:i:s ", $since) . 'GMT',
			'Expires' => gmdate("D, j M Y H:i:s", $time) . " GMT",
			'Cache-Control' => 'public, max-age=0',
			'Pragma' => 'cache'
		);
		$response->cache($since, $time);
		$this->assertEquals($response->header(), $expected);
	}

/**
 * Tests the compress method
 *
 * @return void
 */
	public function testCompress() {
		if (php_sapi_name() !== 'cli') {
			$this->markTestSkipped('The response compression can only be tested in cli.');
		}

		$response = new CakeResponse();
		if (ini_get("zlib.output_compression") === '1' || !extension_loaded("zlib")) {
			$this->assertFalse($response->compress());
			$this->markTestSkipped('Is not possible to test output compression');
		}

		$_SERVER['HTTP_ACCEPT_ENCODING'] = '';
		$result = $response->compress();
		$this->assertFalse($result);

		$_SERVER['HTTP_ACCEPT_ENCODING'] = 'gzip';
		$result = $response->compress();
		$this->assertTrue($result);
		$this->assertTrue(in_array('ob_gzhandler', ob_list_handlers()));

		ob_get_clean();
	}

/**
* Tests the httpCodes method
*
*/
	public function testHttpCodes() {
		$response = new CakeResponse();
		$result = $response->httpCodes();
		$this->assertEqual(count($result), 39);

		$result =  $response->httpCodes(100);
		$expected = array(100 => 'Continue');
		$this->assertEqual($expected, $result);

		$codes = array(
			1337 => 'Undefined Unicorn',
			1729 => 'Hardy-Ramanujan Located'
		);

		$result =  $response->httpCodes($codes);
		$this->assertTrue($result);
		$this->assertEqual(count($response->httpCodes()), 41);

		$result = $response->httpCodes(1337);
		$expected = array(1337 => 'Undefined Unicorn');
		$this->assertEqual($expected, $result);

		$codes = array(404 => 'Sorry Bro');
		$result = $response->httpCodes($codes);
		$this->assertTrue($result);
		$this->assertEqual(count($response->httpCodes()), 41);

		$result = $response->httpCodes(404);
		$expected = array(404 => 'Sorry Bro');
		$this->assertEqual($expected, $result);
	}

/**
* Tests the download method
*
*/
	public function testDownload() {
		$response = new CakeResponse();
		$expected = array(
			'Content-Disposition' => 'attachment; filename="myfile.mp3"'
		);
		$response->download('myfile.mp3');
		$this->assertEquals($response->header(), $expected);
	}

/**
* Tests the mapType method
*
*/
	public function testMapType() {
		$response = new CakeResponse();
		$this->assertEquals('wav', $response->mapType('audio/x-wav'));
		$this->assertEquals('pdf', $response->mapType('application/pdf'));
		$this->assertEquals('xml', $response->mapType('text/xml'));
		$this->assertEquals('html', $response->mapType('*/*'));
		$this->assertEquals('csv', $response->mapType('application/vnd.ms-excel'));
		$expected = array('json', 'xhtml', 'css');
		$result = $response->mapType(array('application/json', 'application/xhtml+xml', 'text/css'));
		$this->assertEquals($expected, $result);
	}

/**
* Tests the outputCompressed method
*
*/
	public function testOutputCompressed() {
		$response = new CakeResponse();

		$_SERVER['HTTP_ACCEPT_ENCODING'] = 'gzip';
		$result = $response->outputCompressed();
		$this->assertFalse($result);

		$_SERVER['HTTP_ACCEPT_ENCODING'] = '';
		$result = $response->outputCompressed();
		$this->assertFalse($result);

		if (!extension_loaded("zlib")) {
			$this->markTestSkipped('Skipping further tests for outputCompressed as zlib extension is not loaded');
		}
		if (php_sapi_name() !== 'cli') {
			$this->markTestSkipped('Testing outputCompressed method with compression enabled done only in cli');
		}

		if (ini_get("zlib.output_compression") !== '1') {
			ob_start('ob_gzhandler');
		}
		$_SERVER['HTTP_ACCEPT_ENCODING'] = 'gzip';
		$result = $response->outputCompressed();
		$this->assertTrue($result);

		$_SERVER['HTTP_ACCEPT_ENCODING'] = '';
		$result = $response->outputCompressed();
		$this->assertFalse($result);
		if (ini_get("zlib.output_compression") !== '1') {
			ob_get_clean();
		}
	}

/**
* Tests the send and setting of Content-Length
*
*/
	public function testSendContentLength() {
		$response = $this->getMock('CakeResponse', array('_sendHeader', '_sendContent'));
		$response->body('the response body');
		$response->expects($this->once())->method('_sendContent')->with('the response body');
		$response->expects($this->at(0))
			->method('_sendHeader')->with('HTTP/1.1 200 OK');
		$response->expects($this->at(1))
			->method('_sendHeader')->with('Content-Type', 'text/html; charset=UTF-8');
		$response->expects($this->at(2))
			->method('_sendHeader')->with('Content-Length', strlen('the response body'));
		$response->send();

		$response = $this->getMock('CakeResponse', array('_sendHeader', '_sendContent'));
		$body = '長い長い長いSubjectの場合はfoldingするのが正しいんだけどいったいどうなるんだろう？';
		$response->body($body);
		$response->expects($this->once())->method('_sendContent')->with($body);
		$response->expects($this->at(0))
			->method('_sendHeader')->with('HTTP/1.1 200 OK');
		$response->expects($this->at(1))
			->method('_sendHeader')->with('Content-Type', 'text/html; charset=UTF-8');
		$response->expects($this->at(2))
			->method('_sendHeader')->with('Content-Length', 116);
		$response->send();

		$response = $this->getMock('CakeResponse', array('_sendHeader', '_sendContent', 'outputCompressed'));
		$body = '長い長い長いSubjectの場合はfoldingするのが正しいんだけどいったいどうなるんだろう？';
		$response->body($body);
		$response->expects($this->once())->method('outputCompressed')->will($this->returnValue(true));
		$response->expects($this->once())->method('_sendContent')->with($body);
		$response->expects($this->exactly(2))->method('_sendHeader');
		$response->send();

		$response = $this->getMock('CakeResponse', array('_sendHeader', '_sendContent', 'outputCompressed'));
		$body = 'hwy';
		$response->body($body);
		$response->header('Content-Length', 1);
		$response->expects($this->never())->method('outputCompressed');
		$response->expects($this->once())->method('_sendContent')->with($body);
			$response->expects($this->at(2))
				->method('_sendHeader')->with('Content-Length', 1);
		$response->send();

		$response = $this->getMock('CakeResponse', array('_sendHeader', '_sendContent'));
		$body = 'content';
		$response->statusCode(301);
		$response->body($body);
		$response->expects($this->once())->method('_sendContent')->with($body);
		$response->expects($this->exactly(2))->method('_sendHeader');
		$response->send();

		ob_start();
		$response = $this->getMock('CakeResponse', array('_sendHeader', '_sendContent'));
		$goofyOutput = 'I am goofily sending output in the controller';
		echo $goofyOutput;
		$response = $this->getMock('CakeResponse', array('_sendHeader', '_sendContent'));
		$body = '長い長い長いSubjectの場合はfoldingするのが正しいんだけどいったいどうなるんだろう？';
		$response->body($body);
		$response->expects($this->once())->method('_sendContent')->with($body);
		$response->expects($this->at(0))
			->method('_sendHeader')->with('HTTP/1.1 200 OK');
		$response->expects($this->at(1))
			->method('_sendHeader')->with('Content-Type', 'text/html; charset=UTF-8');
		$response->expects($this->at(2))
			->method('_sendHeader')->with('Content-Length', strlen($goofyOutput) + 116);
		$response->send();
		ob_end_clean();
	}
}
