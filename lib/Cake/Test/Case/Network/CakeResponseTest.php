<?php
/**
 * CakeResponse Test case file.
 *
 * PHP 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       Cake.Test.Case.Network
 * @since         CakePHP(tm) v 2.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
App::uses('CakeResponse', 'Network');
App::uses('CakeRequest', 'Network');

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
		$this->assertEquals('UTF-8', $response->charset());
		$this->assertEquals('text/html', $response->type());
		$this->assertEquals(200, $response->statusCode());

		$options = array(
			'body' => 'This is the body',
			'charset' => 'my-custom-charset',
			'type' => 'mp3',
			'status' => '203'
		);
		$response = new CakeResponse($options);
		$this->assertEquals('This is the body', $response->body());
		$this->assertEquals('my-custom-charset', $response->charset());
		$this->assertEquals('audio/mpeg', $response->type());
		$this->assertEquals(203, $response->statusCode());
	}

/**
 * Tests the body method
 *
 */
	public function testBody() {
		$response = new CakeResponse();
		$this->assertNull($response->body());
		$response->body('Response body');
		$this->assertEquals('Response body', $response->body());
		$this->assertEquals('Changed Body', $response->body('Changed Body'));
	}

/**
 * Tests the charset method
 *
 */
	public function testCharset() {
		$response = new CakeResponse();
		$this->assertEquals('UTF-8', $response->charset());
		$response->charset('iso-8859-1');
		$this->assertEquals('iso-8859-1', $response->charset());
		$this->assertEquals('UTF-16', $response->charset('UTF-16'));
	}

/**
 * Tests the statusCode method
 *
 * @expectedException CakeException
 */
	public function testStatusCode() {
		$response = new CakeResponse();
		$this->assertEquals(200, $response->statusCode());
		$response->statusCode(404);
		$this->assertEquals(404, $response->statusCode());
		$this->assertEquals(500, $response->statusCode(500));

		//Throws exception
		$response->statusCode(1001);
	}

/**
 * Tests the type method
 *
 */
	public function testType() {
		$response = new CakeResponse();
		$this->assertEquals('text/html', $response->type());
		$response->type('pdf');
		$this->assertEquals('application/pdf', $response->type());
		$this->assertEquals('application/crazy-mime', $response->type('application/crazy-mime'));
		$this->assertEquals('application/json', $response->type('json'));
		$this->assertEquals('text/vnd.wap.wml', $response->type('wap'));
		$this->assertEquals('application/vnd.wap.xhtml+xml', $response->type('xhtml-mobile'));
		$this->assertEquals('text/csv', $response->type('csv'));

		$response->type(array('keynote' => 'application/keynote', 'bat' => 'application/bat'));
		$this->assertEquals('application/keynote', $response->type('keynote'));
		$this->assertEquals('application/bat', $response->type('bat'));

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
		$response = $this->getMock('CakeResponse', array('_sendHeader', '_sendContent', '_setCookies'));
		$response->header(array(
			'Content-Language' => 'es',
			'WWW-Authenticate' => 'Negotiate'
		));
		$response->body('the response body');
		$response->expects($this->once())->method('_sendContent')->with('the response body');
		$response->expects($this->at(0))->method('_setCookies');
		$response->expects($this->at(1))
			->method('_sendHeader')->with('HTTP/1.1 200 OK');
		$response->expects($this->at(2))
			->method('_sendHeader')->with('Content-Language', 'es');
		$response->expects($this->at(3))
			->method('_sendHeader')->with('WWW-Authenticate', 'Negotiate');
		$response->expects($this->at(4))
			->method('_sendHeader')->with('Content-Length', 17);
		$response->expects($this->at(5))
			->method('_sendHeader')->with('Content-Type', 'text/html; charset=UTF-8');
		$response->send();
	}

/**
 * Tests the send method and changing the content type
 *
 */
	public function testSendChangingContentType() {
		$response = $this->getMock('CakeResponse', array('_sendHeader', '_sendContent', '_setCookies'));
		$response->type('mp3');
		$response->body('the response body');
		$response->expects($this->once())->method('_sendContent')->with('the response body');
		$response->expects($this->at(0))->method('_setCookies');
		$response->expects($this->at(1))
			->method('_sendHeader')->with('HTTP/1.1 200 OK');
		$response->expects($this->at(2))
			->method('_sendHeader')->with('Content-Length', 17);
		$response->expects($this->at(3))
			->method('_sendHeader')->with('Content-Type', 'audio/mpeg');
		$response->send();
	}

/**
 * Tests the send method and changing the content type to JSON
 *
 */
	public function testSendChangingContentTypeJSON() {
		$response = $this->getMock('CakeResponse', array('_sendHeader', '_sendContent', '_setCookies'));
		$response->type('json');
		$response->body('the response body');
		$response->expects($this->once())->method('_sendContent')->with('the response body');
		$response->expects($this->at(0))->method('_setCookies');
		$response->expects($this->at(1))
			->method('_sendHeader')->with('HTTP/1.1 200 OK');
		$response->expects($this->at(2))
			->method('_sendHeader')->with('Content-Length', 17);
		$response->expects($this->at(3))
			->method('_sendHeader')->with('Content-Type', 'application/json; charset=UTF-8');
		$response->send();
	}

/**
 * Tests the send method and changing the content type
 *
 */
	public function testSendWithLocation() {
		$response = $this->getMock('CakeResponse', array('_sendHeader', '_sendContent', '_setCookies'));
		$response->header('Location', 'http://www.example.com');
		$response->expects($this->at(0))->method('_setCookies');
		$response->expects($this->at(1))
			->method('_sendHeader')->with('HTTP/1.1 302 Found');
		$response->expects($this->at(2))
			->method('_sendHeader')->with('Location', 'http://www.example.com');
		$response->expects($this->at(3))
			->method('_sendHeader')->with('Content-Type', 'text/html; charset=UTF-8');
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
			'Cache-Control' => 'no-store, no-cache, must-revalidate, post-check=0, pre-check=0'
		);
		$response->disableCache();
		$this->assertEquals($expected, $response->header());
	}

/**
 * Tests the cache method
 *
 */
	public function testCache() {
		$response = new CakeResponse();
		$since = time();
		$time = new DateTime('+1 day', new DateTimeZone('UTC'));
		$response->expires('+1 day');
		$expected = array(
			'Date' => gmdate("D, j M Y G:i:s ", $since) . 'GMT',
			'Last-Modified' => gmdate("D, j M Y H:i:s ", $since) . 'GMT',
			'Expires' => $time->format('D, j M Y H:i:s') . ' GMT',
			'Cache-Control' => 'public, max-age=' . ($time->format('U') - time())
		);
		$response->cache($since);
		$this->assertEquals($expected, $response->header());

		$response = new CakeResponse();
		$since = time();
		$time = '+5 day';
		$expected = array(
			'Date' => gmdate("D, j M Y G:i:s ", $since) . 'GMT',
			'Last-Modified' => gmdate("D, j M Y H:i:s ", $since) . 'GMT',
			'Expires' => gmdate("D, j M Y H:i:s", strtotime($time)) . " GMT",
			'Cache-Control' => 'public, max-age=' . (strtotime($time) - time())
		);
		$response->cache($since, $time);
		$this->assertEquals($expected, $response->header());

		$response = new CakeResponse();
		$since = time();
		$time = time();
		$expected = array(
			'Date' => gmdate("D, j M Y G:i:s ", $since) . 'GMT',
			'Last-Modified' => gmdate("D, j M Y H:i:s ", $since) . 'GMT',
			'Expires' => gmdate("D, j M Y H:i:s", $time) . " GMT",
			'Cache-Control' => 'public, max-age=0'
		);
		$response->cache($since, $time);
		$this->assertEquals($expected, $response->header());
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
		$this->assertEquals(39, count($result));

		$result = $response->httpCodes(100);
		$expected = array(100 => 'Continue');
		$this->assertEquals($expected, $result);

		$codes = array(
			1337 => 'Undefined Unicorn',
			1729 => 'Hardy-Ramanujan Located'
		);

		$result = $response->httpCodes($codes);
		$this->assertTrue($result);
		$this->assertEquals(41, count($response->httpCodes()));

		$result = $response->httpCodes(1337);
		$expected = array(1337 => 'Undefined Unicorn');
		$this->assertEquals($expected, $result);

		$codes = array(404 => 'Sorry Bro');
		$result = $response->httpCodes($codes);
		$this->assertTrue($result);
		$this->assertEquals(41, count($response->httpCodes()));

		$result = $response->httpCodes(404);
		$expected = array(404 => 'Sorry Bro');
		$this->assertEquals($expected, $result);
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
		$this->assertEquals($expected, $response->header());
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
		$response->expects($this->at(2))
			->method('_sendHeader')->with('Content-Type', 'text/html; charset=UTF-8');
		$response->expects($this->at(1))
			->method('_sendHeader')->with('Content-Length', strlen('the response body'));
		$response->send();

		$response = $this->getMock('CakeResponse', array('_sendHeader', '_sendContent'));
		$body = '長い長い長いSubjectの場合はfoldingするのが正しいんだけどいったいどうなるんだろう？';
		$response->body($body);
		$response->expects($this->once())->method('_sendContent')->with($body);
		$response->expects($this->at(0))
			->method('_sendHeader')->with('HTTP/1.1 200 OK');
		$response->expects($this->at(2))
			->method('_sendHeader')->with('Content-Type', 'text/html; charset=UTF-8');
		$response->expects($this->at(1))
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
		$response->expects($this->at(1))
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
			->method('_sendHeader')->with('Content-Length', strlen($goofyOutput) + 116);
		$response->expects($this->at(2))
			->method('_sendHeader')->with('Content-Type', 'text/html; charset=UTF-8');
		$response->send();
		ob_end_clean();
	}

/**
 * Tests getting/setting the protocol
 *
 * @return void
 */
	public function testProtocol() {
		$response = $this->getMock('CakeResponse', array('_sendHeader', '_sendContent'));
		$response->protocol('HTTP/1.0');
		$this->assertEquals('HTTP/1.0', $response->protocol());
		$response->expects($this->at(0))
			->method('_sendHeader')->with('HTTP/1.0 200 OK');
		$response->send();
	}

/**
 * Tests getting/setting the Content-Length
 *
 * @return void
 */
	public function testLength() {
		$response = $this->getMock('CakeResponse', array('_sendHeader', '_sendContent'));
		$response->length(100);
		$this->assertEquals(100, $response->length());
		$response->expects($this->at(1))
			->method('_sendHeader')->with('Content-Length', 100);
		$response->send();

		$response = $this->getMock('CakeResponse', array('_sendHeader', '_sendContent'));
		$response->length(false);
		$this->assertFalse($response->length());
		$response->expects($this->exactly(2))
			->method('_sendHeader');
		$response->send();
	}

/**
 * Tests that the response body is unset if the status code is 304 or 204
 *
 * @return void
 */
	public function testUnmodifiedContent() {
		$response = $this->getMock('CakeResponse', array('_sendHeader', '_sendContent'));
		$response->body('This is a body');
		$response->statusCode(204);
		$response->expects($this->once())
			->method('_sendContent')->with('');
		$response->send();
		$this->assertFalse(array_key_exists('Content-Type', $response->header()));

		$response = $this->getMock('CakeResponse', array('_sendHeader', '_sendContent'));
		$response->body('This is a body');
		$response->statusCode(304);
		$response->expects($this->once())
			->method('_sendContent')->with('');
		$response->send();

		$response = $this->getMock('CakeResponse', array('_sendHeader', '_sendContent'));
		$response->body('This is a body');
		$response->statusCode(200);
		$response->expects($this->once())
			->method('_sendContent')->with('This is a body');
		$response->send();
	}

/**
 * Tests setting the expiration date
 *
 * @return void
 */
	public function testExpires() {
		$response = $this->getMock('CakeResponse', array('_sendHeader', '_sendContent'));
		$now = new DateTime('now', new DateTimeZone('America/Los_Angeles'));
		$response->expires($now);
		$now->setTimeZone(new DateTimeZone('UTC'));
		$this->assertEquals($now->format('D, j M Y H:i:s') . ' GMT', $response->expires());
		$response->expects($this->at(1))
			->method('_sendHeader')->with('Expires', $now->format('D, j M Y H:i:s') . ' GMT');
		$response->send();

		$response = $this->getMock('CakeResponse', array('_sendHeader', '_sendContent'));
		$now = time();
		$response->expires($now);
		$this->assertEquals(gmdate('D, j M Y H:i:s', $now) . ' GMT', $response->expires());
		$response->expects($this->at(1))
			->method('_sendHeader')->with('Expires', gmdate('D, j M Y H:i:s', $now) . ' GMT');
		$response->send();

		$response = $this->getMock('CakeResponse', array('_sendHeader', '_sendContent'));
		$time = new DateTime('+1 day', new DateTimeZone('UTC'));
		$response->expires('+1 day');
		$this->assertEquals($time->format('D, j M Y H:i:s') . ' GMT', $response->expires());
		$response->expects($this->at(1))
			->method('_sendHeader')->with('Expires', $time->format('D, j M Y H:i:s') . ' GMT');
		$response->send();
	}

/**
 * Tests setting the modification date
 *
 * @return void
 */
	public function testModified() {
		$response = $this->getMock('CakeResponse', array('_sendHeader', '_sendContent'));
		$now = new DateTime('now', new DateTimeZone('America/Los_Angeles'));
		$response->modified($now);
		$now->setTimeZone(new DateTimeZone('UTC'));
		$this->assertEquals($now->format('D, j M Y H:i:s') . ' GMT', $response->modified());
		$response->expects($this->at(1))
			->method('_sendHeader')->with('Last-Modified', $now->format('D, j M Y H:i:s') . ' GMT');
		$response->send();

		$response = $this->getMock('CakeResponse', array('_sendHeader', '_sendContent'));
		$now = time();
		$response->modified($now);
		$this->assertEquals(gmdate('D, j M Y H:i:s', $now) . ' GMT', $response->modified());
		$response->expects($this->at(1))
			->method('_sendHeader')->with('Last-Modified', gmdate('D, j M Y H:i:s', $now) . ' GMT');
		$response->send();

		$response = $this->getMock('CakeResponse', array('_sendHeader', '_sendContent'));
		$time = new DateTime('+1 day', new DateTimeZone('UTC'));
		$response->modified('+1 day');
		$this->assertEquals($time->format('D, j M Y H:i:s') . ' GMT', $response->modified());
		$response->expects($this->at(1))
			->method('_sendHeader')->with('Last-Modified', $time->format('D, j M Y H:i:s') . ' GMT');
		$response->send();
	}

/**
 * Tests setting of public/private Cache-Control directives
 *
 * @return void
 */
	public function testSharable() {
		$response = $this->getMock('CakeResponse', array('_sendHeader', '_sendContent'));
		$this->assertNull($response->sharable());
		$response->sharable(true);
		$headers = $response->header();
		$this->assertEquals('public', $headers['Cache-Control']);
		$response->expects($this->at(1))
			->method('_sendHeader')->with('Cache-Control', 'public');
		$response->send();

		$response = $this->getMock('CakeResponse', array('_sendHeader', '_sendContent'));
		$response->sharable(false);
		$headers = $response->header();
		$this->assertEquals('private', $headers['Cache-Control']);
		$response->expects($this->at(1))
			->method('_sendHeader')->with('Cache-Control', 'private');
		$response->send();

		$response = $this->getMock('CakeResponse', array('_sendHeader', '_sendContent'));
		$response->sharable(true);
		$headers = $response->header();
		$this->assertEquals('public', $headers['Cache-Control']);
		$response->sharable(false);
		$headers = $response->header();
		$this->assertEquals('private', $headers['Cache-Control']);
		$response->expects($this->at(1))
			->method('_sendHeader')->with('Cache-Control', 'private');
		$response->send();
		$this->assertFalse($response->sharable());
		$response->sharable(true);
		$this->assertTrue($response->sharable());

		$response = new CakeResponse;
		$response->sharable(true, 3600);
		$headers = $response->header();
		$this->assertEquals('public, s-maxage=3600', $headers['Cache-Control']);

		$response = new CakeResponse;
		$response->sharable(false, 3600);
		$headers = $response->header();
		$this->assertEquals('private, max-age=3600', $headers['Cache-Control']);
		$response->send();
	}

/**
 * Tests setting of max-age Cache-Control directive
 *
 * @return void
 */
	public function testMaxAge() {
		$response = $this->getMock('CakeResponse', array('_sendHeader', '_sendContent'));
		$this->assertNull($response->maxAge());
		$response->maxAge(3600);
		$this->assertEquals(3600, $response->maxAge());
		$headers = $response->header();
		$this->assertEquals('max-age=3600', $headers['Cache-Control']);
		$response->expects($this->at(1))
			->method('_sendHeader')->with('Cache-Control', 'max-age=3600');
		$response->send();

		$response = $this->getMock('CakeResponse', array('_sendHeader', '_sendContent'));
		$response->maxAge(3600);
		$response->sharable(false);
		$headers = $response->header();
		$this->assertEquals('max-age=3600, private', $headers['Cache-Control']);
		$response->expects($this->at(1))
			->method('_sendHeader')->with('Cache-Control', 'max-age=3600, private');
		$response->send();
	}

/**
 * Tests setting of s-maxage Cache-Control directive
 *
 * @return void
 */
	public function testSharedMaxAge() {
		$response = $this->getMock('CakeResponse', array('_sendHeader', '_sendContent'));
		$this->assertNull($response->maxAge());
		$response->sharedMaxAge(3600);
		$this->assertEquals(3600, $response->sharedMaxAge());
		$headers = $response->header();
		$this->assertEquals('s-maxage=3600', $headers['Cache-Control']);
		$response->expects($this->at(1))
			->method('_sendHeader')->with('Cache-Control', 's-maxage=3600');
		$response->send();

		$response = $this->getMock('CakeResponse', array('_sendHeader', '_sendContent'));
		$response->sharedMaxAge(3600);
		$response->sharable(true);
		$headers = $response->header();
		$this->assertEquals('s-maxage=3600, public', $headers['Cache-Control']);
		$response->expects($this->at(1))
			->method('_sendHeader')->with('Cache-Control', 's-maxage=3600, public');
		$response->send();
	}

/**
 * Tests setting of must-revalidate Cache-Control directive
 *
 * @return void
 */
	public function testMustRevalidate() {
		$response = $this->getMock('CakeResponse', array('_sendHeader', '_sendContent'));
		$this->assertFalse($response->mustRevalidate());
		$response->mustRevalidate(true);
		$this->assertTrue($response->mustRevalidate());
		$headers = $response->header();
		$this->assertEquals('must-revalidate', $headers['Cache-Control']);
		$response->expects($this->at(1))
			->method('_sendHeader')->with('Cache-Control', 'must-revalidate');
		$response->send();
		$response->mustRevalidate(false);
		$this->assertFalse($response->mustRevalidate());

		$response = $this->getMock('CakeResponse', array('_sendHeader', '_sendContent'));
		$response->sharedMaxAge(3600);
		$response->mustRevalidate(true);
		$headers = $response->header();
		$this->assertEquals('s-maxage=3600, must-revalidate', $headers['Cache-Control']);
		$response->expects($this->at(1))
			->method('_sendHeader')->with('Cache-Control', 's-maxage=3600, must-revalidate');
		$response->send();
	}

/**
 * Tests getting/setting the Vary header
 *
 * @return void
 */
	public function testVary() {
		$response = $this->getMock('CakeResponse', array('_sendHeader', '_sendContent'));
		$response->vary('Accept-encoding');
		$this->assertEquals(array('Accept-encoding'), $response->vary());
		$response->expects($this->at(1))
			->method('_sendHeader')->with('Vary', 'Accept-encoding');
		$response->send();

		$response = $this->getMock('CakeResponse', array('_sendHeader', '_sendContent'));
		$response->vary(array('Accept-language', 'Accept-encoding'));
		$response->expects($this->at(1))
			->method('_sendHeader')->with('Vary', 'Accept-language, Accept-encoding');
		$response->send();
		$this->assertEquals(array('Accept-language', 'Accept-encoding'), $response->vary());
	}

/**
 * Tests getting/setting the Etag header
 *
 * @return void
 */
	public function testEtag() {
		$response = $this->getMock('CakeResponse', array('_sendHeader', '_sendContent'));
		$response->etag('something');
		$this->assertEquals('"something"', $response->etag());
		$response->expects($this->at(1))
			->method('_sendHeader')->with('Etag', '"something"');
		$response->send();

		$response = $this->getMock('CakeResponse', array('_sendHeader', '_sendContent'));
		$response->etag('something', true);
		$this->assertEquals('W/"something"', $response->etag());
		$response->expects($this->at(1))
			->method('_sendHeader')->with('Etag', 'W/"something"');
		$response->send();
	}

/**
 * Tests that the response is able to be marked as not modified
 *
 * @return void
 */
	public function testNotModified() {
		$response = $this->getMock('CakeResponse', array('_sendHeader', '_sendContent'));
		$response->body('something');
		$response->statusCode(200);
		$response->length(100);
		$response->modified('now');
		$response->notModified();

		$this->assertEmpty($response->header());
		$this->assertEmpty($response->body());
		$this->assertEquals(304, $response->statusCode());
	}

/**
 * Test checkNotModified method
 *
 * @return void
 **/
	public function testCheckNotModifiedByEtagStar() {
		$_SERVER['HTTP_IF_NONE_MATCH'] = '*';
		$response = $this->getMock('CakeResponse', array('notModified'));
		$response->etag('something');
		$response->expects($this->once())->method('notModified');
		$response->checkNotModified(new CakeRequest);
	}

/**
 * Test checkNotModified method
 *
 * @return void
 **/
	public function testCheckNotModifiedByEtagExact() {
		$_SERVER['HTTP_IF_NONE_MATCH'] = 'W/"something", "other"';
		$response = $this->getMock('CakeResponse', array('notModified'));
		$response->etag('something', true);
		$response->expects($this->once())->method('notModified');
		$this->assertTrue($response->checkNotModified(new CakeRequest));
	}

/**
 * Test checkNotModified method
 *
 * @return void
 **/
	public function testCheckNotModifiedByEtagAndTime() {
		$_SERVER['HTTP_IF_NONE_MATCH'] = 'W/"something", "other"';
		$_SERVER['HTTP_IF_MODIFIED_SINCE'] = '2012-01-01 00:00:00';
		$response = $this->getMock('CakeResponse', array('notModified'));
		$response->etag('something', true);
		$response->modified('2012-01-01 00:00:00');
		$response->expects($this->once())->method('notModified');
		$this->assertTrue($response->checkNotModified(new CakeRequest));
	}

/**
 * Test checkNotModified method
 *
 * @return void
 **/
	public function testCheckNotModifiedByEtagAndTimeMismatch() {
		$_SERVER['HTTP_IF_NONE_MATCH'] = 'W/"something", "other"';
		$_SERVER['HTTP_IF_MODIFIED_SINCE'] = '2012-01-01 00:00:00';
		$response = $this->getMock('CakeResponse', array('notModified'));
		$response->etag('something', true);
		$response->modified('2012-01-01 00:00:01');
		$response->expects($this->never())->method('notModified');
		$this->assertFalse($response->checkNotModified(new CakeRequest));
	}

/**
 * Test checkNotModified method
 *
 * @return void
 **/
	public function testCheckNotModifiedByEtagMismatch() {
		$_SERVER['HTTP_IF_NONE_MATCH'] = 'W/"something-else", "other"';
		$_SERVER['HTTP_IF_MODIFIED_SINCE'] = '2012-01-01 00:00:00';
		$response = $this->getMock('CakeResponse', array('notModified'));
		$response->etag('something', true);
		$response->modified('2012-01-01 00:00:00');
		$response->expects($this->never())->method('notModified');
		$this->assertFalse($response->checkNotModified(new CakeRequest));
	}

/**
 * Test checkNotModified method
 *
 * @return void
 **/
	public function testCheckNotModifiedByTime() {
		$_SERVER['HTTP_IF_MODIFIED_SINCE'] = '2012-01-01 00:00:00';
		$response = $this->getMock('CakeResponse', array('notModified'));
		$response->modified('2012-01-01 00:00:00');
		$response->expects($this->once())->method('notModified');
		$this->assertTrue($response->checkNotModified(new CakeRequest));
	}

/**
 * Test checkNotModified method
 *
 * @return void
 **/
	public function testCheckNotModifiedNoHints() {
		$_SERVER['HTTP_IF_NONE_MATCH'] = 'W/"something", "other"';
		$_SERVER['HTTP_IF_MODIFIED_SINCE'] = '2012-01-01 00:00:00';
		$response = $this->getMock('CakeResponse', array('notModified'));
		$response->expects($this->never())->method('notModified');
		$this->assertFalse($response->checkNotModified(new CakeRequest));
	}

/**
 * Test cookie setting
 *
 * @return void
 */
	public function testCookieSettings() {
		$response = new CakeResponse();
		$cookie = array(
			'name' => 'CakeTestCookie[Testing]'
		);
		$response->cookie($cookie);
		$expected = array(
			'name' => 'CakeTestCookie[Testing]',
			'value' => '',
			'expire' => 0,
			'path' => '/',
			'domain' => '',
			'secure' => false,
			'httpOnly' => false);
		$result = $response->cookie('CakeTestCookie[Testing]');
		$this->assertEquals($expected, $result);

		$cookie = array(
			'name' => 'CakeTestCookie[Testing2]',
			'value' => '[a,b,c]',
			'expire' => 1000,
			'path' => '/test',
			'secure' => true
		);
		$response->cookie($cookie);
		$expected = array(
			'CakeTestCookie[Testing]' => array(
				'name' => 'CakeTestCookie[Testing]',
				'value' => '',
				'expire' => 0,
				'path' => '/',
				'domain' => '',
				'secure' => false,
				'httpOnly' => false
			),
			'CakeTestCookie[Testing2]' => array(
				'name' => 'CakeTestCookie[Testing2]',
				'value' => '[a,b,c]',
				'expire' => 1000,
				'path' => '/test',
				'domain' => '',
				'secure' => true,
				'httpOnly' => false
			)
		);

		$result = $response->cookie();
		$this->assertEquals($expected, $result);

		$cookie = $expected['CakeTestCookie[Testing]'];
		$cookie['value'] = 'test';
		$response->cookie($cookie);
		$expected = array(
			'CakeTestCookie[Testing]' => array(
				'name' => 'CakeTestCookie[Testing]',
				'value' => 'test',
				'expire' => 0,
				'path' => '/',
				'domain' => '',
				'secure' => false,
				'httpOnly' => false
			),
			'CakeTestCookie[Testing2]' => array(
				'name' => 'CakeTestCookie[Testing2]',
				'value' => '[a,b,c]',
				'expire' => 1000,
				'path' => '/test',
				'domain' => '',
				'secure' => true,
				'httpOnly' => false
			)
		);

		$result = $response->cookie();
		$this->assertEquals($expected, $result);
	}

/**
 * testFileNotFound
 *
 * @expectedException NotFoundException
 * @return void
 */
	public function testFileNotFound() {
		$response = new CakeResponse();
		$response->file('/some/missing/folder/file.jpg');
	}

/**
 * testFile method
 *
 * @return void
 */
	public function testFile() {
		$response = $this->getMock('CakeResponse', array(
			'header',
			'type',
			'_sendHeader',
			'_setContentType',
			'_isActive',
			'_clearBuffer',
			'_flushBuffer'
		));

		$response->expects($this->exactly(1))
			->method('type')
			->with('css')
			->will($this->returnArgument(0));

		$response->expects($this->at(1))
			->method('header')
			->with('Content-Length', 38);

		$response->expects($this->once())->method('_clearBuffer');
		$response->expects($this->once())->method('_flushBuffer');

		$response->expects($this->exactly(1))
			->method('_isActive')
			->will($this->returnValue(true));

		$response->file(CAKE . 'Test' . DS . 'test_app' . DS . 'Vendor' . DS . 'css' . DS . 'test_asset.css');

		ob_start();
		$result = $response->send();
		$output = ob_get_clean();
		$this->assertEquals("/* this is the test asset css file */\n", $output);
		$this->assertTrue($result !== false);
	}

/**
 * testFileWithUnknownFileTypeGeneric method
 *
 * @return void
 */
	public function testFileWithUnknownFileTypeGeneric() {
		$currentUserAgent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : null;
		$_SERVER['HTTP_USER_AGENT'] = 'Some generic browser';

		$response = $this->getMock('CakeResponse', array(
			'header',
			'type',
			'download',
			'_sendHeader',
			'_setContentType',
			'_isActive',
			'_clearBuffer',
			'_flushBuffer'
		));

		$response->expects($this->exactly(1))
			->method('type')
			->with('ini')
			->will($this->returnValue(false));

		$response->expects($this->once())
			->method('download')
			->with('no_section.ini');

		$response->expects($this->at(2))
			->method('header')
			->with('Accept-Ranges', 'bytes');

		$response->expects($this->at(3))
			->method('header')
			->with('Content-Length', 35);

		$response->expects($this->once())->method('_clearBuffer');
		$response->expects($this->once())->method('_flushBuffer');

		$response->expects($this->exactly(1))
			->method('_isActive')
			->will($this->returnValue(true));

		$response->file(CAKE . 'Test' . DS . 'test_app' . DS . 'Config' . DS . 'no_section.ini');

		ob_start();
		$result = $response->send();
		$output = ob_get_clean();
		$this->assertEquals("some_key = some_value\nbool_key = 1\n", $output);
		$this->assertTrue($result !== false);
		if ($currentUserAgent !== null) {
			$_SERVER['HTTP_USER_AGENT'] = $currentUserAgent;
		}
	}

/**
 * testFileWithUnknownFileTypeOpera method
 *
 * @return void
 */
	public function testFileWithUnknownFileTypeOpera() {
		$currentUserAgent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : null;
		$_SERVER['HTTP_USER_AGENT'] = 'Opera/9.80 (Windows NT 6.0; U; en) Presto/2.8.99 Version/11.10';

		$response = $this->getMock('CakeResponse', array(
			'header',
			'type',
			'download',
			'_sendHeader',
			'_setContentType',
			'_isActive',
			'_clearBuffer',
			'_flushBuffer'
		));

		$response->expects($this->at(0))
			->method('type')
			->with('ini')
			->will($this->returnValue(false));

		$response->expects($this->at(1))
			->method('type')
			->with('application/octetstream')
			->will($this->returnValue(false));

		$response->expects($this->once())
			->method('download')
			->with('no_section.ini');

		$response->expects($this->at(3))
			->method('header')
			->with('Accept-Ranges', 'bytes');

		$response->expects($this->at(4))
			->method('header')
			->with('Content-Length', 35);

		$response->expects($this->once())->method('_clearBuffer');
		$response->expects($this->once())->method('_flushBuffer');
		$response->expects($this->exactly(1))
			->method('_isActive')
			->will($this->returnValue(true));

		$response->file(CAKE . 'Test' . DS . 'test_app' . DS . 'Config' . DS . 'no_section.ini');

		ob_start();
		$result = $response->send();
		$output = ob_get_clean();
		$this->assertEquals("some_key = some_value\nbool_key = 1\n", $output);
		$this->assertTrue($result !== false);
		if ($currentUserAgent !== null) {
			$_SERVER['HTTP_USER_AGENT'] = $currentUserAgent;
		}
	}

/**
 * testFileWithUnknownFileTypeIE method
 *
 * @return void
 */
	public function testFileWithUnknownFileTypeIE() {
		$currentUserAgent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : null;
		$_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (compatible; MSIE 8.0; Windows NT 5.2; Trident/4.0; Media Center PC 4.0; SLCC1; .NET CLR 3.0.04320)';

		$response = $this->getMock('CakeResponse', array(
			'header',
			'type',
			'download',
			'_sendHeader',
			'_setContentType',
			'_isActive',
			'_clearBuffer',
			'_flushBuffer'
		));

		$response->expects($this->at(0))
			->method('type')
			->with('ini')
			->will($this->returnValue(false));

		$response->expects($this->at(1))
			->method('type')
			->with('application/force-download')
			->will($this->returnValue(false));

		$response->expects($this->once())
			->method('download')
			->with('config.ini');

		$response->expects($this->at(3))
			->method('header')
			->with('Accept-Ranges', 'bytes');

		$response->expects($this->at(4))
			->method('header')
			->with('Content-Length', 35);

		$response->expects($this->once())->method('_clearBuffer');
		$response->expects($this->once())->method('_flushBuffer');
		$response->expects($this->exactly(1))
			->method('_isActive')
			->will($this->returnValue(true));

		$response->file(CAKE . 'Test' . DS . 'test_app' . DS . 'Config' . DS . 'no_section.ini', array(
			'name' => 'config.ini'
		));

		ob_start();
		$result = $response->send();
		$output = ob_get_clean();
		$this->assertEquals("some_key = some_value\nbool_key = 1\n", $output);
		$this->assertTrue($result !== false);
		if ($currentUserAgent !== null) {
			$_SERVER['HTTP_USER_AGENT'] = $currentUserAgent;
		}
	}
/**
 * testFileWithUnknownFileNoDownload method
 *
 * @return void
 */
	public function testFileWithUnknownFileNoDownload() {
		$currentUserAgent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : null;
		$_SERVER['HTTP_USER_AGENT'] = 'Some generic browser';

		$response = $this->getMock('CakeResponse', array(
			'header',
			'type',
			'download',
			'_sendHeader',
			'_setContentType',
			'_isActive',
			'_clearBuffer',
			'_flushBuffer'
		));

		$response->expects($this->exactly(1))
			->method('type')
			->with('ini')
			->will($this->returnValue(false));

		$response->expects($this->never())
			->method('download');

		$response->file(CAKE . 'Test' . DS . 'test_app' . DS . 'Config' . DS . 'no_section.ini', array(
			'download' => false
		));

		if ($currentUserAgent !== null) {
			$_SERVER['HTTP_USER_AGENT'] = $currentUserAgent;
		}
	}

/**
 * testConnectionAbortedOnBuffering method
 *
 * @return void
 */
	public function testConnectionAbortedOnBuffering() {
		$response = $this->getMock('CakeResponse', array(
			'header',
			'type',
			'download',
			'_sendHeader',
			'_setContentType',
			'_isActive',
			'_clearBuffer',
			'_flushBuffer'
		));

		$response->expects($this->any())
			->method('type')
			->with('css')
			->will($this->returnArgument(0));

		$response->expects($this->at(0))
			->method('_isActive')
			->will($this->returnValue(false));

		$response->expects($this->once())->method('_clearBuffer');
		$response->expects($this->never())->method('_flushBuffer');

		$response->file(CAKE . 'Test' . DS . 'test_app' . DS . 'Vendor' . DS . 'css' . DS . 'test_asset.css');

		$result = $response->send();
		$this->assertNull($result);
	}

/**
 * Test downloading files with UPPERCASE extensions.
 *
 * @return void
 */
	public function testFileUpperExtension() {
		$response = $this->getMock('CakeResponse', array(
			'header',
			'type',
			'download',
			'_sendHeader',
			'_setContentType',
			'_isActive',
			'_clearBuffer',
			'_flushBuffer'
		));

		$response->expects($this->any())
			->method('type')
			->with('jpg')
			->will($this->returnArgument(0));

		$response->expects($this->at(0))
			->method('_isActive')
			->will($this->returnValue(true));

		$response->file(CAKE . 'Test' . DS . 'test_app' . DS . 'Vendor' . DS . 'img' . DS . 'test_2.JPG');
	}

/**
 * Test downloading files with extension not explicitly set.
 *
 * @return void
 */
	public function testFileExtensionNotSet() {
		$response = $this->getMock('CakeResponse', array(
			'header',
			'type',
			'download',
			'_sendHeader',
			'_setContentType',
			'_isActive',
			'_clearBuffer',
			'_flushBuffer'
		));

		$response->expects($this->any())
			->method('type')
			->with('jpg')
			->will($this->returnArgument(0));

		$response->expects($this->at(0))
			->method('_isActive')
			->will($this->returnValue(true));

		$response->file(CAKE . 'Test' . DS . 'test_app' . DS . 'Vendor' . DS . 'img' . DS . 'test_2.JPG');
	}

}
