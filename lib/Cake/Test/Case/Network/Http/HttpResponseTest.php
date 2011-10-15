<?php
/**
 * HttpResponseTest file
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
 * @package       Cake.Test.Case.Network.Http
 * @since         CakePHP(tm) v 1.2.0.4206
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
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
 * @param string $body A string continaing the body to decode
 * @param mixed $encoding Can be false in case no encoding is being used, or a string representing the encoding
 * @return mixed Array or false
 */
	public function decodeBody($body, $encoding = 'chunked') {
		return parent::_decodeBody($body, $encoding);
	}

/**
 * Convenience method for testing protected method
 *
 * @param string $body A string continaing the chunked body to decode
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
 * @param boolean $hex true to get them as HEX values, false otherwise
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
		$this->HttpResponse = new TestHttpResponse();
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

		$this->assertEqual($this->HttpResponse->getHeader('foo', false), 'Bar');
		$this->assertEqual($this->HttpResponse->getHeader('foo', array('foo' => 'not from class')), 'not from class');
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
 * Test that HttpSocket::parseHeader can take apart a given (and valid) $header string and turn it into an array.
 *
 * @return void
 */
	public function testParseHeader() {
		$r = $this->HttpResponse->parseHeader(array('foo' => 'Bar', 'fOO-bAr' => 'quux'));
		$this->assertEquals($r, array('foo' => 'Bar', 'fOO-bAr' => 'quux'));

		$r = $this->HttpResponse->parseHeader(true);
		$this->assertEquals($r, false);

		$header = "Host: cakephp.org\t\r\n";
		$r = $this->HttpResponse->parseHeader($header);
		$expected = array(
			'Host' => 'cakephp.org'
		);
		$this->assertEquals($r, $expected);

		$header = "Date:Sat, 07 Apr 2007 10:10:25 GMT\r\nX-Powered-By: PHP/5.1.2\r\n";
		$r = $this->HttpResponse->parseHeader($header);
		$expected = array(
			'Date' => 'Sat, 07 Apr 2007 10:10:25 GMT',
			'X-Powered-By' => 'PHP/5.1.2'
		);
		$this->assertEquals($r, $expected);

		$header = "people: Jim,John\r\nfoo-LAND: Bar\r\ncAKe-PHP: rocks\r\n";
		$r = $this->HttpResponse->parseHeader($header);
		$expected = array(
			'people' => 'Jim,John',
			'foo-LAND' => 'Bar',
			'cAKe-PHP' => 'rocks'
		);
		$this->assertEquals($r, $expected);

		$header = "People: Jim,John,Tim\r\nPeople: Lisa,Tina,Chelsea\r\n";
		$r = $this->HttpResponse->parseHeader($header);
		$expected = array(
			'People' => array('Jim,John,Tim', 'Lisa,Tina,Chelsea')
		);
		$this->assertEquals($r, $expected);

		$header = "Multi-Line: I am a \r\nmulti line\t\r\nfield value.\r\nSingle-Line: I am not\r\n";
		$r = $this->HttpResponse->parseHeader($header);
		$expected = array(
			'Multi-Line' => "I am a\r\nmulti line\r\nfield value.",
			'Single-Line' => 'I am not'
		);
		$this->assertEquals($r, $expected);

		$header = "Esc\"@\"ped: value\r\n";
		$r = $this->HttpResponse->parseHeader($header);
		$expected = array(
			'Esc@ped' => 'value'
		);
		$this->assertEquals($r, $expected);
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
				$this->assertEquals($this->HttpResponse->{$property}, $expectedVal, 'Test "' . $name . '": response.' . $property . ' - %s');
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
 * return void
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
		$this->assertEquals($r, false);

		$r = $this->HttpResponse->decodeBody('Foobar', false);
		$this->assertEquals($r, array('body' => 'Foobar', 'header' => false));

		$encoding = 'chunked';
		$sample = array(
			'encoded' => "19\r\nThis is a chunked message\r\n0\r\n",
			'decoded' => array('body' => "This is a chunked message", 'header' => false)
		);

		$r = $this->HttpResponse->decodeBody($sample['encoded'], $encoding);
		$this->assertEquals($r, $sample['decoded']);
	}

/**
 * testDecodeFooCoded
 *
 * @return void
 */
	public function testDecodeFooCoded() {
		$r = $this->HttpResponse->decodeBody(true);
		$this->assertEquals($r, false);

		$r = $this->HttpResponse->decodeBody('Foobar', false);
		$this->assertEquals($r, array('body' => 'Foobar', 'header' => false));

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
		$this->assertEquals($r, false);

		$encoded = "19\r\nThis is a chunked message\r\n0\r\n";
		$decoded = "This is a chunked message";
		$r = $this->HttpResponse->decodeChunkedBody($encoded);
		$this->assertEquals($r['body'], $decoded);
		$this->assertEquals($r['header'], false);

		$encoded = "19 \r\nThis is a chunked message\r\n0\r\n";
		$r = $this->HttpResponse->decodeChunkedBody($encoded);
		$this->assertEquals($r['body'], $decoded);

		$encoded = "19\r\nThis is a chunked message\r\nE\r\n\nThat is cool\n\r\n0\r\n";
		$decoded = "This is a chunked message\nThat is cool\n";
		$r = $this->HttpResponse->decodeChunkedBody($encoded);
		$this->assertEquals($r['body'], $decoded);
		$this->assertEquals($r['header'], false);

		$encoded = "19\r\nThis is a chunked message\r\nE;foo-chunk=5\r\n\nThat is cool\n\r\n0\r\n";
		$r = $this->HttpResponse->decodeChunkedBody($encoded);
		$this->assertEquals($r['body'], $decoded);
		$this->assertEquals($r['header'], false);

		$encoded = "19\r\nThis is a chunked message\r\nE\r\n\nThat is cool\n\r\n0\r\nfoo-header: bar\r\ncake: PHP\r\n\r\n";
		$r = $this->HttpResponse->decodeChunkedBody($encoded);
		$this->assertEquals($r['body'], $decoded);
		$this->assertEquals($r['header'], array('foo-header' => 'bar', 'cake' => 'PHP'));
	}

/**
 * testDecodeChunkedBodyError method
 *
 * @expectedException SocketException
 * @return void
 */
	public function testDecodeChunkedBodyError() {
		$encoded = "19\r\nThis is a chunked message\r\nE\r\n\nThat is cool\n\r\n";
		$r = $this->HttpResponse->decodeChunkedBody($encoded);
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
				'google=not=nice'
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
			)
		);
		$this->assertEqual($cookies, $expected);

		$header['Set-Cookie'][] = 'cakephp=great; Secure';
		$expected['cakephp'] = array('value' => 'great', 'secure' => true);
		$cookies = $this->HttpResponse->parseCookies($header);
		$this->assertEqual($cookies, $expected);

		$header['Set-Cookie'] = 'foo=bar';
		unset($expected['people'], $expected['cakephp'], $expected['google']);
		$cookies = $this->HttpResponse->parseCookies($header);
		$this->assertEqual($cookies, $expected);
	}

/**
 * Test that escaped token strings are properly unescaped by HttpSocket::unescapeToken
 *
 * @return void
 */
	public function testUnescapeToken() {
		$this->assertEquals($this->HttpResponse->unescapeToken('Foo'), 'Foo');

		$escape = $this->HttpResponse->tokenEscapeChars(false);
		foreach ($escape as $char) {
			$token = 'My-special-"' . $char . '"-Token';
			$unescapedToken = $this->HttpResponse->unescapeToken($token);
			$expectedToken = 'My-special-' . $char . '-Token';

			$this->assertEquals($unescapedToken, $expectedToken, 'Test token unescaping for ASCII '.ord($char));
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

		$expected1 = "HTTP/1.1 200 OK\r\n";
		$this->assertEqual($this->HttpResponse['raw']['status-line'], $expected1);
		$expected2 = "Server: CakePHP\r\nContEnt-Type: text/plain\r\n";
		$this->assertEqual($this->HttpResponse['raw']['header'], $expected2);
		$expected3 = 'This is a test!';
		$this->assertEqual($this->HttpResponse['raw']['body'], $expected3);
		$expected = $expected1 . $expected2 . "\r\n" . $expected3;
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

		$this->HttpResponse->raw = "HTTP/1.1 200 OK\r\n\r\nThis is a test!";
		$this->assertIdentical($this->HttpResponse['raw']['header'], null);
	}

}