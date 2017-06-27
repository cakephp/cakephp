<?php
/**
 * HttpResponseTest file
 *
 * CakePHP(tm) Tests <https://book.cakephp.org/2.0/en/development/testing.html>
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://book.cakephp.org/2.0/en/development/testing.html CakePHP(tm) Tests
 * @package       Cake.Test.Case.Network.Http
 * @since         CakePHP(tm) v 1.2.0.4206
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
App::uses('HttpResponse', 'Network/Http');

/**
 * TestHttpResponse class
 *
 * @package       Cake.Test.Case.Network.Http
 */
class TestHttpResponse extends HttpResponse {

/**
 * Convenience method for testing protected method
 *
 * @param array $header Header as an indexed array (field => value)
 * @return array Parsed header
 */
	public function parseHeader($header) {
		return parent::_parseHeader($header);
	}

/**
 * Convenience method for testing protected method
 *
 * @param string $body A string containing the body to decode
 * @param bool|string $encoding Can be false in case no encoding is being used, or a string representing the encoding
 * @return mixed Array or false
 */
	public function decodeBody($body, $encoding = 'chunked') {
		return parent::_decodeBody($body, $encoding);
	}

/**
 * Convenience method for testing protected method
 *
 * @param string $body A string containing the chunked body to decode
 * @return mixed Array or false
 */
	public function decodeChunkedBody($body) {
		return parent::_decodeChunkedBody($body);
	}

/**
 * Convenience method for testing protected method
 *
 * @param string $token Token to unescape
 * @return string Unescaped token
 */
	public function unescapeToken($token, $chars = null) {
		return parent::_unescapeToken($token, $chars);
	}

/**
 * Convenience method for testing protected method
 *
 * @param bool $hex true to get them as HEX values, false otherwise
 * @return array Escape chars
 */
	public function tokenEscapeChars($hex = true, $chars = null) {
		return parent::_tokenEscapeChars($hex, $chars);
	}

}

/**
 * HttpResponseTest class
 *
 * @package       Cake.Test.Case.Network.Http
 */
class HttpResponseTest extends CakeTestCase {

/**
 * This function sets up a HttpResponse
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();
		$this->HttpResponse = new TestHttpResponse();
	}

/**
 * testBody
 *
 * @return void
 */
	public function testBody() {
		$this->HttpResponse->body = 'testing';
		$this->assertEquals('testing', $this->HttpResponse->body());

		$this->HttpResponse->body = null;
		$this->assertSame($this->HttpResponse->body(), '');
	}

/**
 * testToString
 *
 * @return void
 */
	public function testToString() {
		$this->HttpResponse->body = 'other test';
		$this->assertEquals('other test', $this->HttpResponse->body());
		$this->assertEquals('other test', (string)$this->HttpResponse);
		$this->assertTrue(strpos($this->HttpResponse, 'test') > 0);

		$this->HttpResponse->body = null;
		$this->assertEquals('', (string)$this->HttpResponse);
	}

/**
 * testGetHeader
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

		$this->assertEquals('Bar', $this->HttpResponse->getHeader('foo'));
		$this->assertEquals('Bar', $this->HttpResponse->getHeader('Foo'));
		$this->assertEquals('Bar', $this->HttpResponse->getHeader('FOO'));
		$this->assertEquals('value', $this->HttpResponse->getHeader('header'));
		$this->assertEquals('text/plain', $this->HttpResponse->getHeader('Content-Type'));
		$this->assertNull($this->HttpResponse->getHeader(0));

		$this->assertEquals('Bar', $this->HttpResponse->getHeader('foo', false));
		$this->assertEquals('not from class', $this->HttpResponse->getHeader('foo', array('foo' => 'not from class')));
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
		$this->HttpResponse->code = 'what?';
		$this->assertFalse($this->HttpResponse->isOk());
		$this->HttpResponse->code = 200;
		$this->assertTrue($this->HttpResponse->isOk());
		$this->HttpResponse->code = 201;
		$this->assertTrue($this->HttpResponse->isOk());
		$this->HttpResponse->code = 202;
		$this->assertTrue($this->HttpResponse->isOk());
		$this->HttpResponse->code = 203;
		$this->assertTrue($this->HttpResponse->isOk());
		$this->HttpResponse->code = 204;
		$this->assertTrue($this->HttpResponse->isOk());
		$this->HttpResponse->code = 205;
		$this->assertTrue($this->HttpResponse->isOk());
		$this->HttpResponse->code = 206;
		$this->assertTrue($this->HttpResponse->isOk());
		$this->HttpResponse->code = 207;
		$this->assertFalse($this->HttpResponse->isOk());
		$this->HttpResponse->code = 208;
		$this->assertFalse($this->HttpResponse->isOk());
		$this->HttpResponse->code = 209;
		$this->assertFalse($this->HttpResponse->isOk());
		$this->HttpResponse->code = 210;
		$this->assertFalse($this->HttpResponse->isOk());
		$this->HttpResponse->code = 226;
		$this->assertFalse($this->HttpResponse->isOk());
		$this->HttpResponse->code = 288;
		$this->assertFalse($this->HttpResponse->isOk());
		$this->HttpResponse->code = 301;
		$this->assertFalse($this->HttpResponse->isOk());
	}

/**
 * testIsRedirect
 *
 * @return void
 */
	public function testIsRedirect() {
		$this->HttpResponse->code = 0;
		$this->assertFalse($this->HttpResponse->isRedirect());
		$this->HttpResponse->code = -1;
		$this->assertFalse($this->HttpResponse->isRedirect());
		$this->HttpResponse->code = 201;
		$this->assertFalse($this->HttpResponse->isRedirect());
		$this->HttpResponse->code = 'what?';
		$this->assertFalse($this->HttpResponse->isRedirect());
		$this->HttpResponse->code = 301;
		$this->assertFalse($this->HttpResponse->isRedirect());
		$this->HttpResponse->code = 302;
		$this->assertFalse($this->HttpResponse->isRedirect());
		$this->HttpResponse->code = 303;
		$this->assertFalse($this->HttpResponse->isRedirect());
		$this->HttpResponse->code = 307;
		$this->assertFalse($this->HttpResponse->isRedirect());
		$this->HttpResponse->code = 301;
		$this->HttpResponse->headers['Location'] = 'http://somewhere/';
		$this->assertTrue($this->HttpResponse->isRedirect());
		$this->HttpResponse->code = 302;
		$this->HttpResponse->headers['Location'] = 'http://somewhere/';
		$this->assertTrue($this->HttpResponse->isRedirect());
		$this->HttpResponse->code = 303;
		$this->HttpResponse->headers['Location'] = 'http://somewhere/';
		$this->assertTrue($this->HttpResponse->isRedirect());
		$this->HttpResponse->code = 307;
		$this->HttpResponse->headers['Location'] = 'http://somewhere/';
		$this->assertTrue($this->HttpResponse->isRedirect());
	}

/**
 * Test that HttpSocket::parseHeader can take apart a given (and valid) $header string and turn it into an array.
 *
 * @return void
 */
	public function testParseHeader() {
		$r = $this->HttpResponse->parseHeader(array('foo' => 'Bar', 'fOO-bAr' => 'quux'));
		$this->assertEquals(array('foo' => 'Bar', 'fOO-bAr' => 'quux'), $r);

		$r = $this->HttpResponse->parseHeader(true);
		$this->assertEquals(false, $r);

		$header = "Host: cakephp.org\t\r\n";
		$r = $this->HttpResponse->parseHeader($header);
		$expected = array(
			'Host' => 'cakephp.org'
		);
		$this->assertEquals($expected, $r);

		$header = "Date:Sat, 07 Apr 2007 10:10:25 GMT\r\nX-Powered-By: PHP/5.1.2\r\n";
		$r = $this->HttpResponse->parseHeader($header);
		$expected = array(
			'Date' => 'Sat, 07 Apr 2007 10:10:25 GMT',
			'X-Powered-By' => 'PHP/5.1.2'
		);
		$this->assertEquals($expected, $r);

		$header = "people: Jim,John\r\nfoo-LAND: Bar\r\ncAKe-PHP: rocks\r\n";
		$r = $this->HttpResponse->parseHeader($header);
		$expected = array(
			'people' => 'Jim,John',
			'foo-LAND' => 'Bar',
			'cAKe-PHP' => 'rocks'
		);
		$this->assertEquals($expected, $r);

		$header = "People: Jim,John,Tim\r\nPeople: Lisa,Tina,Chelsea\r\n";
		$r = $this->HttpResponse->parseHeader($header);
		$expected = array(
			'People' => array('Jim,John,Tim', 'Lisa,Tina,Chelsea')
		);
		$this->assertEquals($expected, $r);

		$header = "Date:Sat, 07 Apr 2007 10:10:25 GMT\r\nLink: \r\nX-Total-Count: 19\r\n";
		$r = $this->HttpResponse->parseHeader($header);
		$expected = array(
			'Date' => 'Sat, 07 Apr 2007 10:10:25 GMT',
			'Link' => '',
			'X-Total-Count' => '19',
		);
		$this->assertEquals($expected, $r);

		$header = "Multi-Line: I am a\r\n multi line \r\n\tfield value.\r\nSingle-Line: I am not\r\n";
		$r = $this->HttpResponse->parseHeader($header);
		$expected = array(
			'Multi-Line' => "I am a multi line field value.",
			'Single-Line' => 'I am not'
		);
		$this->assertEquals($expected, $r);

		$header = "Esc\"@\"ped: value\r\n";
		$r = $this->HttpResponse->parseHeader($header);
		$expected = array(
			'Esc@ped' => 'value'
		);
		$this->assertEquals($expected, $r);
	}

/**
 * testParseResponse method
 *
 * @return void
 */
	public function testParseResponse() {
		$tests = array(
			'simple-request' => array(
				'response' => array(
					'status-line' => "HTTP/1.x 200 OK\r\n",
					'header' => "Date: Mon, 16 Apr 2007 04:14:16 GMT\r\nServer: CakeHttp Server\r\n",
					'body' => "<h1>Hello World</h1>\r\n<p>It's good to be html</p>"
				),
				'expectations' => array(
					'httpVersion' => 'HTTP/1.x',
					'code' => 200,
					'reasonPhrase' => 'OK',
					'headers' => array('Date' => 'Mon, 16 Apr 2007 04:14:16 GMT', 'Server' => 'CakeHttp Server'),
					'body' => "<h1>Hello World</h1>\r\n<p>It's good to be html</p>"
				)
			),
			'no-header' => array(
				'response' => array(
					'status-line' => "HTTP/1.x 404 OK\r\n",
					'header' => null
				),
				'expectations' => array(
					'code' => 404,
					'headers' => array()
				)
			)
		);

		$testResponse = array();
		$expectations = array();

		foreach ($tests as $name => $test) {
			$testResponse = array_merge($testResponse, $test['response']);
			$testResponse['response'] = $testResponse['status-line'] . $testResponse['header'] . "\r\n" . $testResponse['body'];
			$this->HttpResponse->parseResponse($testResponse['response']);
			$expectations = array_merge($expectations, $test['expectations']);

			foreach ($expectations as $property => $expectedVal) {
				$this->assertEquals($expectedVal, $this->HttpResponse->{$property}, 'Test "' . $name . '": response.' . $property . ' - %s');
			}

			foreach (array('status-line', 'header', 'body', 'response') as $field) {
				$this->assertEquals($this->HttpResponse['raw'][$field], $testResponse[$field], 'Test response.raw.' . $field . ': %s');
			}
		}
	}

/**
 * data provider function for testInvalidParseResponseData
 *
 * @return array
 */
	public static function invalidParseResponseDataProvider() {
		return array(
			array(array('foo' => 'bar')),
			array(true),
			array("HTTP Foo\r\nBar: La"),
			array('HTTP/1.1 TEST ERROR')
		);
	}

/**
 * testInvalidParseResponseData
 *
 * @dataProvider invalidParseResponseDataProvider
 * @expectedException SocketException
 * @return void
 */
	public function testInvalidParseResponseData($value) {
		$this->HttpResponse->parseResponse($value);
	}

/**
 * testDecodeBody method
 *
 * @return void
 */
	public function testDecodeBody() {
		$r = $this->HttpResponse->decodeBody(true);
		$this->assertEquals(false, $r);

		$r = $this->HttpResponse->decodeBody('Foobar', false);
		$this->assertEquals(array('body' => 'Foobar', 'header' => false), $r);

		$encoding = 'chunked';
		$sample = array(
			'encoded' => "19\r\nThis is a chunked message\r\n0\r\n",
			'decoded' => array('body' => "This is a chunked message", 'header' => false)
		);

		$r = $this->HttpResponse->decodeBody($sample['encoded'], $encoding);
		$this->assertEquals($r, $sample['decoded']);

		$encoding = 'chunked';
		$sample = array(
			'encoded' => "19\nThis is a chunked message\r\n0\n",
			'decoded' => array('body' => "This is a chunked message", 'header' => false)
		);

		$r = $this->HttpResponse->decodeBody($sample['encoded'], $encoding);
		$this->assertEquals($r, $sample['decoded'], 'Inconsistent line terminators should be tolerated.');
	}

/**
 * testDecodeFooCoded
 *
 * @return void
 */
	public function testDecodeFooCoded() {
		$r = $this->HttpResponse->decodeBody(true);
		$this->assertEquals(false, $r);

		$r = $this->HttpResponse->decodeBody('Foobar', false);
		$this->assertEquals(array('body' => 'Foobar', 'header' => false), $r);

		$encoding = 'foo-bar';
		$sample = array(
			'encoded' => '!Foobar!',
			'decoded' => array('body' => '!Foobar!', 'header' => false),
		);

		$r = $this->HttpResponse->decodeBody($sample['encoded'], $encoding);
		$this->assertEquals($r, $sample['decoded']);
	}

/**
 * testDecodeChunkedBody method
 *
 * @return void
 */
	public function testDecodeChunkedBody() {
		$r = $this->HttpResponse->decodeChunkedBody(true);
		$this->assertEquals(false, $r);

		$encoded = "19\r\nThis is a chunked message\r\n0\r\n";
		$decoded = "This is a chunked message";
		$r = $this->HttpResponse->decodeChunkedBody($encoded);
		$this->assertEquals($r['body'], $decoded);
		$this->assertEquals(false, $r['header']);

		$encoded = "19 \r\nThis is a chunked message\r\n0\r\n";
		$r = $this->HttpResponse->decodeChunkedBody($encoded);
		$this->assertEquals($r['body'], $decoded);

		$encoded = "19\r\nThis is a chunked message\r\nE\r\n\nThat is cool\n\r\n0\r\n";
		$decoded = "This is a chunked message\nThat is cool\n";
		$r = $this->HttpResponse->decodeChunkedBody($encoded);
		$this->assertEquals($r['body'], $decoded);
		$this->assertEquals(false, $r['header']);

		$encoded = "19\r\nThis is a chunked message\r\nE;foo-chunk=5\r\n\nThat is cool\n\r\n0\r\n";
		$r = $this->HttpResponse->decodeChunkedBody($encoded);
		$this->assertEquals($r['body'], $decoded);
		$this->assertEquals(false, $r['header']);

		$encoded = "19\r\nThis is a chunked message\r\nE\r\n\nThat is cool\n\r\n0\r\nfoo-header: bar\r\ncake: PHP\r\n\r\n";
		$r = $this->HttpResponse->decodeChunkedBody($encoded);
		$this->assertEquals($r['body'], $decoded);
		$this->assertEquals(array('foo-header' => 'bar', 'cake' => 'PHP'), $r['header']);
	}

/**
 * testDecodeChunkedBodyError method
 *
 * @return void
 */
	public function testDecodeChunkedBodyError() {
		$encoded = "19\r\nThis is a chunked message\r\nE\r\n\nThat is cool\n\r\n";
		$result = $this->HttpResponse->decodeChunkedBody($encoded);
		$expected = "This is a chunked message\nThat is cool\n";
		$this->assertEquals($expected, $result['body']);
	}

/**
 * testParseCookies method
 *
 * @return void
 */
	public function testParseCookies() {
		$header = array(
			'Set-Cookie' => array(
				'foo=bar',
				'people=jim,jack,johnny";";Path=/accounts',
				'google=not=nice',
				'1271; domain=.example.com; expires=Fri, 04-Nov-2016 12:50:26 GMT; path=/',
				'cakephp=great; Secure'
			),
			'Transfer-Encoding' => 'chunked',
			'Date' => 'Sun, 18 Nov 2007 18:57:42 GMT',
		);
		$cookies = $this->HttpResponse->parseCookies($header);
		$expected = array(
			'foo' => array(
				'value' => 'bar'
			),
			'people' => array(
				'value' => 'jim,jack,johnny";"',
				'path' => '/accounts',
			),
			'google' => array(
				'value' => 'not=nice',
			),
			'' => array(
				'value' => '1271',
				'domain' => '.example.com',
				'expires' => 'Fri, 04-Nov-2016 12:50:26 GMT',
				'path' => '/'
			),
			'cakephp' => array(
				'value' => 'great',
				'secure' => true,
			)
		);
		$this->assertEquals($expected, $cookies);

		$header['Set-Cookie'] = 'foo=bar';
		$expected = array(
			'foo' => array('value' => 'bar')
		);
		$cookies = $this->HttpResponse->parseCookies($header);
		$this->assertEquals($expected, $cookies);
	}

/**
 * Test that escaped token strings are properly unescaped by HttpSocket::unescapeToken
 *
 * @return void
 */
	public function testUnescapeToken() {
		$this->assertEquals('Foo', $this->HttpResponse->unescapeToken('Foo'));

		$escape = $this->HttpResponse->tokenEscapeChars(false);
		foreach ($escape as $char) {
			$token = 'My-special-"' . $char . '"-Token';
			$unescapedToken = $this->HttpResponse->unescapeToken($token);
			$expectedToken = 'My-special-' . $char . '-Token';

			$this->assertEquals($expectedToken, $unescapedToken, 'Test token unescaping for ASCII ' . ord($char));
		}

		$token = 'Extreme-":"Token-"	"-""""@"-test';
		$escapedToken = $this->HttpResponse->unescapeToken($token);
		$expectedToken = 'Extreme-:Token-	-"@-test';
		$this->assertEquals($expectedToken, $escapedToken);
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
		$expectedOne = "HTTP/1.1 200 OK\r\n";
		$this->assertEquals($expectedOne, $this->HttpResponse['raw']['status-line']);
		$expectedTwo = "Server: CakePHP\r\nContEnt-Type: text/plain\r\n";
		$this->assertEquals($expectedTwo, $this->HttpResponse['raw']['header']);
		$expectedThree = 'This is a test!';
		$this->assertEquals($expectedThree, $this->HttpResponse['raw']['body']);
		$expected = $expectedOne . $expectedTwo . "\r\n" . $expectedThree;
		$this->assertEquals($expected, $this->HttpResponse['raw']['response']);

		$expected = 'HTTP/1.1';
		$this->assertEquals($expected, $this->HttpResponse['status']['http-version']);
		$expected = 200;
		$this->assertEquals($expected, $this->HttpResponse['status']['code']);
		$expected = 'OK';
		$this->assertEquals($expected, $this->HttpResponse['status']['reason-phrase']);

		$expected = array(
			'Server' => 'CakePHP',
			'ContEnt-Type' => 'text/plain'
		);
		$this->assertEquals($expected, $this->HttpResponse['header']);

		$expected = 'This is a test!';
		$this->assertEquals($expected, $this->HttpResponse['body']);

		$expected = array(
			'foo' => array('value' => 'bar'),
			'bar' => array('value' => 'foo')
		);
		$this->assertEquals($expected, $this->HttpResponse['cookies']);

		$this->HttpResponse->raw = "HTTP/1.1 200 OK\r\n\r\nThis is a test!";
		$this->assertNull($this->HttpResponse['raw']['header']);
	}

}
