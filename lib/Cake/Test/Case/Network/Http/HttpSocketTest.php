<?php
/**
 * HttpSocketTest file
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
 * @package       Cake.Test.Case.Network.Http
 * @since         CakePHP(tm) v 1.2.0.4206
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

App::uses('HttpSocket', 'Network/Http');
App::uses('HttpResponse', 'Network/Http');

/**
 * TestAuthentication class
 *
 * @package       Cake.Test.Case.Network.Http
 * @package       Cake.Test.Case.Network.Http
 */
class TestAuthentication {

/**
 * authentication method
 *
 * @param HttpSocket $http
 * @param array $authInfo
 * @return void
 */
	public static function authentication(HttpSocket $http, &$authInfo) {
		$http->request['header']['Authorization'] = 'Test ' . $authInfo['user'] . '.' . $authInfo['pass'];
	}

/**
 * proxyAuthentication method
 *
 * @param HttpSocket $http
 * @param array $proxyInfo
 * @return void
 */
	public static function proxyAuthentication(HttpSocket $http, &$proxyInfo) {
		$http->request['header']['Proxy-Authorization'] = 'Test ' . $proxyInfo['user'] . '.' . $proxyInfo['pass'];
	}

}

/**
 * CustomResponse
 *
 */
class CustomResponse {

/**
 * First 10 chars
 *
 * @var string
 */
	public $first10;

/**
 * Constructor
 *
 */
	public function __construct($message) {
		$this->first10 = substr($message, 0, 10);
	}

}

/**
 * TestHttpSocket
 *
 */
class TestHttpSocket extends HttpSocket {

/**
 * Convenience method for testing protected method
 *
 * @param string|array $uri URI (see {@link _parseUri()})
 * @return array Current configuration settings
 */
	public function configUri($uri = null) {
		return parent::_configUri($uri);
	}

/**
 * Convenience method for testing protected method
 *
 * @param string|array $uri URI to parse
 * @param boolean|array $base If true use default URI config, otherwise indexed array to set 'scheme', 'host', 'port', etc.
 * @return array Parsed URI
 */
	public function parseUri($uri = null, $base = array()) {
		return parent::_parseUri($uri, $base);
	}

/**
 * Convenience method for testing protected method
 *
 * @param array $uri A $uri array, or uses $this->config if left empty
 * @param string $uriTemplate The Uri template/format to use
 * @return string A fully qualified URL formatted according to $uriTemplate
 */
	public function buildUri($uri = array(), $uriTemplate = '%scheme://%user:%pass@%host:%port/%path?%query#%fragment') {
		return parent::_buildUri($uri, $uriTemplate);
	}

/**
 * Convenience method for testing protected method
 *
 * @param array $header Header to build
 * @return string Header built from array
 */
	public function buildHeader($header, $mode = 'standard') {
		return parent::_buildHeader($header, $mode);
	}

/**
 * Convenience method for testing protected method
 *
 * @param string|array $query A query string to parse into an array or an array to return directly "as is"
 * @return array The $query parsed into a possibly multi-level array. If an empty $query is given, an empty array is returned.
 */
	public function parseQuery($query) {
		return parent::_parseQuery($query);
	}

/**
 * Convenience method for testing protected method
 *
 * @param array $request Needs to contain a 'uri' key. Should also contain a 'method' key, otherwise defaults to GET.
 * @param string $versionToken The version token to use, defaults to HTTP/1.1
 * @return string Request line
 */
	public function buildRequestLine($request = array(), $versionToken = 'HTTP/1.1') {
		return parent::_buildRequestLine($request, $versionToken);
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

/**
 * Convenience method for testing protected method
 *
 * @param string $token Token to escape
 * @return string Escaped token
 */
	public function escapeToken($token, $chars = null) {
		return parent::_escapeToken($token, $chars);
	}

}

/**
 * HttpSocketTest class
 *
 * @package       Cake.Test.Case.Network.Http
 */
class HttpSocketTest extends CakeTestCase {

/**
 * Socket property
 *
 * @var mixed null
 */
	public $Socket = null;

/**
 * RequestSocket property
 *
 * @var mixed null
 */
	public $RequestSocket = null;

/**
 * This function sets up a TestHttpSocket instance we are going to use for testing
 *
 * @return void
 */
	public function setUp() {
		if (!class_exists('MockHttpSocket')) {
			$this->getMock('TestHttpSocket', array('read', 'write', 'connect'), array(), 'MockHttpSocket');
			$this->getMock('TestHttpSocket', array('read', 'write', 'connect', 'request'), array(), 'MockHttpSocketRequests');
		}

		$this->Socket = new MockHttpSocket();
		$this->RequestSocket = new MockHttpSocketRequests();
	}

/**
 * We use this function to clean up after the test case was executed
 *
 * @return void
 */
	public function tearDown() {
		unset($this->Socket, $this->RequestSocket);
	}

/**
 * Test that HttpSocket::__construct does what one would expect it to do
 *
 * @return void
 */
	public function testConstruct() {
		$this->Socket->reset();
		$baseConfig = $this->Socket->config;
		$this->Socket->expects($this->never())->method('connect');
		$this->Socket->__construct(array('host' => 'foo-bar'));
		$baseConfig['host'] = 'foo-bar';
		$baseConfig['protocol'] = getprotobyname($baseConfig['protocol']);
		$this->assertEquals($this->Socket->config, $baseConfig);

		$this->Socket->reset();
		$baseConfig = $this->Socket->config;
		$this->Socket->__construct('http://www.cakephp.org:23/');
		$baseConfig['host'] = $baseConfig['request']['uri']['host'] = 'www.cakephp.org';
		$baseConfig['port'] = $baseConfig['request']['uri']['port'] = 23;
		$baseConfig['request']['uri']['scheme'] = 'http';
		$baseConfig['protocol'] = getprotobyname($baseConfig['protocol']);
		$this->assertEquals($this->Socket->config, $baseConfig);

		$this->Socket->reset();
		$this->Socket->__construct(array('request' => array('uri' => 'http://www.cakephp.org:23/')));
		$this->assertEquals($this->Socket->config, $baseConfig);
	}

/**
 * Test that HttpSocket::configUri works properly with different types of arguments
 *
 * @return void
 */
	public function testConfigUri() {
		$this->Socket->reset();
		$r = $this->Socket->configUri('https://bob:secret@www.cakephp.org:23/?query=foo');
		$expected = array(
			'persistent' => false,
			'host' => 'www.cakephp.org',
			'protocol' => 'tcp',
			'port' => 23,
			'timeout' => 30,
			'ssl_verify_peer' => true,
			'ssl_verify_depth' => 5,
			'ssl_verify_host' => true,
			'request' => array(
				'uri' => array(
					'scheme' => 'https',
					'host' => 'www.cakephp.org',
					'port' => 23
				),
				'redirect' => false,
				'cookies' => array(),
			)
		);
		$this->assertEquals($expected, $this->Socket->config);
		$this->assertTrue($r);
		$r = $this->Socket->configUri(array('host' => 'www.foo-bar.org'));
		$expected['host'] = 'www.foo-bar.org';
		$expected['request']['uri']['host'] = 'www.foo-bar.org';
		$this->assertEquals($expected, $this->Socket->config);
		$this->assertTrue($r);

		$r = $this->Socket->configUri('http://www.foo.com');
		$expected = array(
			'persistent' => false,
			'host' => 'www.foo.com',
			'protocol' => 'tcp',
			'port' => 80,
			'timeout' => 30,
			'ssl_verify_peer' => true,
			'ssl_verify_depth' => 5,
			'ssl_verify_host' => true,
			'request' => array(
				'uri' => array(
					'scheme' => 'http',
					'host' => 'www.foo.com',
					'port' => 80
				),
				'redirect' => false,
				'cookies' => array(),
			)
		);
		$this->assertEquals($expected, $this->Socket->config);
		$this->assertTrue($r);

		$r = $this->Socket->configUri('/this-is-broken');
		$this->assertEquals($expected, $this->Socket->config);
		$this->assertFalse($r);

		$r = $this->Socket->configUri(false);
		$this->assertEquals($expected, $this->Socket->config);
		$this->assertFalse($r);
	}

/**
 * Tests that HttpSocket::request (the heart of the HttpSocket) is working properly.
 *
 * @return void
 */
	public function testRequest() {
		$this->Socket->reset();

		$response = $this->Socket->request(true);
		$this->assertFalse($response);

		$context = array(
			'ssl' => array(
				'verify_peer' => true,
				'verify_depth' => 5,
				'CN_match' => 'www.cakephp.org',
				'cafile' => CAKE . 'Config' . DS . 'cacert.pem'
			)
		);

		$tests = array(
			array(
				'request' => 'http://www.cakephp.org/?foo=bar',
				'expectation' => array(
					'config' => array(
						'persistent' => false,
						'host' => 'www.cakephp.org',
						'protocol' => 'tcp',
						'port' => 80,
						'timeout' => 30,
						'context' => $context,
						'request' => array(
							'uri' => array(
								'scheme' => 'http',
								'host' => 'www.cakephp.org',
								'port' => 80
							),
							'redirect' => false,
							'cookies' => array()
						)
					),
					'request' => array(
						'method' => 'GET',
						'uri' => array(
							'scheme' => 'http',
							'host' => 'www.cakephp.org',
							'port' => 80,
							'user' => null,
							'pass' => null,
							'path' => '/',
							'query' => array('foo' => 'bar'),
							'fragment' => null
						),
						'version' => '1.1',
						'body' => '',
						'line' => "GET /?foo=bar HTTP/1.1\r\n",
						'header' => "Host: www.cakephp.org\r\nConnection: close\r\nUser-Agent: CakePHP\r\n",
						'raw' => "",
						'redirect' => false,
						'cookies' => array(),
						'proxy' => array(),
						'auth' => array()
					)
				)
			),
			array(
				'request' => array(
					'uri' => array(
						'host' => 'www.cakephp.org',
						'query' => '?foo=bar'
					)
				)
			),
			array(
				'request' => 'www.cakephp.org/?foo=bar'
			),
			array(
				'request' => array(
					'host' => '192.168.0.1',
					'uri' => 'http://www.cakephp.org/?foo=bar'
				),
				'expectation' => array(
					'request' => array(
						'uri' => array('host' => 'www.cakephp.org')
					),
					'config' => array(
						'request' => array(
							'uri' => array('host' => 'www.cakephp.org')
						),
						'host' => '192.168.0.1'
					)
				)
			),
			'reset4' => array(
				'request.uri.query' => array()
			),
			array(
				'request' => array(
					'header' => array('Foo@woo' => 'bar-value')
				),
				'expectation' => array(
					'request' => array(
						'header' => "Host: www.cakephp.org\r\nConnection: close\r\nUser-Agent: CakePHP\r\nFoo\"@\"woo: bar-value\r\n",
						'line' => "GET / HTTP/1.1\r\n"
					)
				)
			),
			array(
				'request' => array('header' => array('Foo@woo' => 'bar-value', 'host' => 'foo.com'), 'uri' => 'http://www.cakephp.org/'),
				'expectation' => array(
					'request' => array(
						'header' => "Host: foo.com\r\nConnection: close\r\nUser-Agent: CakePHP\r\nFoo\"@\"woo: bar-value\r\n"
					),
					'config' => array(
						'host' => 'www.cakephp.org'
					)
				)
			),
			array(
				'request' => array('header' => "Foo: bar\r\n"),
				'expectation' => array(
					'request' => array(
						'header' => "Foo: bar\r\n"
					)
				)
			),
			array(
				'request' => array('header' => "Foo: bar\r\n", 'uri' => 'http://www.cakephp.org/search?q=http_socket#ignore-me'),
				'expectation' => array(
					'request' => array(
						'uri' => array(
							'path' => '/search',
							'query' => array('q' => 'http_socket'),
							'fragment' => 'ignore-me'
						),
						'line' => "GET /search?q=http_socket HTTP/1.1\r\n"
					)
				)
			),
			'reset8' => array(
				'request.uri.query' => array()
			),
			array(
				'request' => array(
					'method' => 'POST',
					'uri' => 'http://www.cakephp.org/posts/add',
					'body' => array(
						'name' => 'HttpSocket-is-released',
						'date' => 'today'
					)
				),
				'expectation' => array(
					'request' => array(
						'method' => 'POST',
						'uri' => array(
							'path' => '/posts/add',
							'fragment' => null
						),
						'body' => "name=HttpSocket-is-released&date=today",
						'line' => "POST /posts/add HTTP/1.1\r\n",
						'header' => "Host: www.cakephp.org\r\nConnection: close\r\nUser-Agent: CakePHP\r\nContent-Type: application/x-www-form-urlencoded\r\nContent-Length: 38\r\n",
						'raw' => "name=HttpSocket-is-released&date=today"
					)
				)
			),
			array(
				'request' => array(
					'method' => 'POST',
					'uri' => 'http://www.cakephp.org:8080/posts/add',
					'body' => array(
						'name' => 'HttpSocket-is-released',
						'date' => 'today'
					)
				),
				'expectation' => array(
					'config' => array(
						'port' => 8080,
						'request' => array(
							'uri' => array(
								'port' => 8080
							)
						)
					),
					'request' => array(
						'uri' => array(
							'port' => 8080
						),
						'header' => "Host: www.cakephp.org:8080\r\nConnection: close\r\nUser-Agent: CakePHP\r\nContent-Type: application/x-www-form-urlencoded\r\nContent-Length: 38\r\n"
					)
				)
			),
			array(
				'request' => array(
					'method' => 'POST',
					'uri' => 'https://www.cakephp.org/posts/add',
					'body' => array(
						'name' => 'HttpSocket-is-released',
						'date' => 'today'
					)
				),
				'expectation' => array(
					'config' => array(
						'port' => 443,
						'request' => array(
							'uri' => array(
								'scheme' => 'https',
								'port' => 443
							)
						)
					),
					'request' => array(
						'uri' => array(
							'scheme' => 'https',
							'port' => 443
						),
						'header' => "Host: www.cakephp.org\r\nConnection: close\r\nUser-Agent: CakePHP\r\nContent-Type: application/x-www-form-urlencoded\r\nContent-Length: 38\r\n"
					)
				)
			),
			array(
				'request' => array(
					'method' => 'POST',
					'uri' => 'https://www.cakephp.org/posts/add',
					'body' => array('name' => 'HttpSocket-is-released', 'date' => 'today'),
					'cookies' => array('foo' => array('value' => 'bar'))
				),
				'expectation' => array(
					'request' => array(
						'header' => "Host: www.cakephp.org\r\nConnection: close\r\nUser-Agent: CakePHP\r\nContent-Type: application/x-www-form-urlencoded\r\nContent-Length: 38\r\nCookie: foo=bar\r\n",
						'cookies' => array(
							'foo' => array('value' => 'bar'),
						)
					)
				)
			)
		);

		$expectation = array();
		foreach ($tests as $i => $test) {
			if (strpos($i, 'reset') === 0) {
				foreach ($test as $path => $val) {
					$expectation = Hash::insert($expectation, $path, $val);
				}
				continue;
			}

			if (isset($test['expectation'])) {
				$expectation = Hash::merge($expectation, $test['expectation']);
			}
			$this->Socket->request($test['request']);

			$raw = $expectation['request']['raw'];
			$expectation['request']['raw'] = $expectation['request']['line'] . $expectation['request']['header'] . "\r\n" . $raw;

			$r = array('config' => $this->Socket->config, 'request' => $this->Socket->request);
			$this->assertEquals($r, $expectation, 'Failed test #' . $i . ' ');
			$expectation['request']['raw'] = $raw;
		}

		$this->Socket->reset();
		$request = array('method' => 'POST', 'uri' => 'http://www.cakephp.org/posts/add', 'body' => array('name' => 'HttpSocket-is-released', 'date' => 'today'));
		$response = $this->Socket->request($request);
		$this->assertEquals("name=HttpSocket-is-released&date=today", $this->Socket->request['body']);
	}

/**
 * Test the scheme + port keys
 *
 * @return void
 */
	public function testGetWithSchemeAndPort() {
		$this->Socket->reset();
		$request = array(
			'uri' => array(
				'scheme' => 'http',
				'host' => 'cakephp.org',
				'port' => 8080,
				'path' => '/',
			),
			'method' => 'GET'
		);
		$this->Socket->request($request);
		$this->assertContains('Host: cakephp.org:8080', $this->Socket->request['header']);
	}

/**
 * Test URLs like http://cakephp.org/index.php?somestring without key/value pair for query
 *
 * @return void
 */
	public function testRequestWithStringQuery() {
		$this->Socket->reset();
		$request = array(
			'uri' => array(
				'scheme' => 'http',
				'host' => 'cakephp.org',
				'path' => 'index.php',
				'query' => 'somestring'
			),
			'method' => 'GET'
		);
		$this->Socket->request($request);
		$this->assertContains("GET /index.php?somestring HTTP/1.1", $this->Socket->request['line']);
	}

/**
 * The "*" asterisk character is only allowed for the following methods: OPTIONS.
 *
 * @expectedException SocketException
 * @return void
 */
	public function testRequestNotAllowedUri() {
		$this->Socket->reset();
		$request = array('uri' => '*', 'method' => 'GET');
		$this->Socket->request($request);
	}

/**
 * testRequest2 method
 *
 * @return void
 */
	public function testRequest2() {
		$this->Socket->reset();
		$request = array('uri' => 'htpp://www.cakephp.org/');
		$number = mt_rand(0, 9999999);
		$this->Socket->expects($this->once())->method('connect')->will($this->returnValue(true));
		$serverResponse = "HTTP/1.x 200 OK\r\nDate: Mon, 16 Apr 2007 04:14:16 GMT\r\nServer: CakeHttp Server\r\nContent-Type: text/html\r\n\r\n<h1>Hello, your lucky number is " . $number . "</h1>";
		$this->Socket->expects($this->at(0))->method('read')->will($this->returnValue(false));
		$this->Socket->expects($this->at(1))->method('read')->will($this->returnValue($serverResponse));
		$this->Socket->expects($this->once())->method('write')
			->with("GET / HTTP/1.1\r\nHost: www.cakephp.org\r\nConnection: close\r\nUser-Agent: CakePHP\r\n\r\n");
		$response = (string)$this->Socket->request($request);
		$this->assertEquals($response, "<h1>Hello, your lucky number is " . $number . "</h1>");
	}

/**
 * testRequest3 method
 *
 * @return void
 */
	public function testRequest3() {
		$request = array('uri' => 'htpp://www.cakephp.org/');
		$serverResponse = "HTTP/1.x 200 OK\r\nSet-Cookie: foo=bar\r\nDate: Mon, 16 Apr 2007 04:14:16 GMT\r\nServer: CakeHttp Server\r\nContent-Type: text/html\r\n\r\n<h1>This is a cookie test!</h1>";
		$this->Socket->expects($this->at(1))->method('read')->will($this->returnValue($serverResponse));
		$this->Socket->connected = true;
		$this->Socket->request($request);
		$result = $this->Socket->response['cookies'];
		$expect = array(
			'foo' => array(
				'value' => 'bar'
			)
		);
		$this->assertEquals($expect, $result);
		$this->assertEquals($this->Socket->config['request']['cookies']['www.cakephp.org'], $expect);
		$this->assertFalse($this->Socket->connected);
	}

/**
 * testRequestWithConstructor method
 *
 * @return void
 */
	public function testRequestWithConstructor() {
		$request = array(
			'request' => array(
				'uri' => array(
					'scheme' => 'http',
					'host' => 'localhost',
					'port' => '5984',
					'user' => null,
					'pass' => null
				)
			)
		);
		$http = new MockHttpSocketRequests($request);

		$expected = array('method' => 'GET', 'uri' => '/_test');
		$http->expects($this->at(0))->method('request')->with($expected);
		$http->get('/_test');

		$expected = array('method' => 'GET', 'uri' => 'http://localhost:5984/_test?count=4');
		$http->expects($this->at(0))->method('request')->with($expected);
		$http->get('/_test', array('count' => 4));
	}

/**
 * testRequestWithResource
 *
 * @return void
 */
	public function testRequestWithResource() {
		$serverResponse = "HTTP/1.x 200 OK\r\nDate: Mon, 16 Apr 2007 04:14:16 GMT\r\nServer: CakeHttp Server\r\nContent-Type: text/html\r\n\r\n<h1>This is a test!</h1>";
		$this->Socket->expects($this->at(1))->method('read')->will($this->returnValue($serverResponse));
		$this->Socket->expects($this->at(2))->method('read')->will($this->returnValue(false));
		$this->Socket->expects($this->at(4))->method('read')->will($this->returnValue($serverResponse));
		$this->Socket->connected = true;

		$f = fopen(TMP . 'download.txt', 'w');
		if (!$f) {
			$this->markTestSkipped('Can not write in TMP directory.');
		}

		$this->Socket->setContentResource($f);
		$result = (string)$this->Socket->request('http://www.cakephp.org/');
		$this->assertEquals('', $result);
		$this->assertEquals('CakeHttp Server', $this->Socket->response['header']['Server']);
		fclose($f);
		$this->assertEquals(file_get_contents(TMP . 'download.txt'), '<h1>This is a test!</h1>');
		unlink(TMP . 'download.txt');

		$this->Socket->setContentResource(false);
		$result = (string)$this->Socket->request('http://www.cakephp.org/');
		$this->assertEquals('<h1>This is a test!</h1>', $result);
	}

/**
 * testRequestWithCrossCookie
 *
 * @return void
 */
	public function testRequestWithCrossCookie() {
		$this->Socket->connected = true;
		$this->Socket->config['request']['cookies'] = array();

		$serverResponse = "HTTP/1.x 200 OK\r\nSet-Cookie: foo=bar\r\nDate: Mon, 16 Apr 2007 04:14:16 GMT\r\nServer: CakeHttp Server\r\nContent-Type: text/html\r\n\r\n<h1>This is a test!</h1>";
		$this->Socket->expects($this->at(1))->method('read')->will($this->returnValue($serverResponse));
		$this->Socket->expects($this->at(2))->method('read')->will($this->returnValue(false));
		$expected = array('www.cakephp.org' => array('foo' => array('value' => 'bar')));
		$this->Socket->request('http://www.cakephp.org/');
		$this->assertEquals($expected, $this->Socket->config['request']['cookies']);

		$serverResponse = "HTTP/1.x 200 OK\r\nSet-Cookie: bar=foo\r\nDate: Mon, 16 Apr 2007 04:14:16 GMT\r\nServer: CakeHttp Server\r\nContent-Type: text/html\r\n\r\n<h1>This is a test!</h1>";
		$this->Socket->expects($this->at(1))->method('read')->will($this->returnValue($serverResponse));
		$this->Socket->expects($this->at(2))->method('read')->will($this->returnValue(false));
		$this->Socket->request('http://www.cakephp.org/other');
		$this->assertEquals(array('foo' => array('value' => 'bar')), $this->Socket->request['cookies']);
		$expected['www.cakephp.org'] += array('bar' => array('value' => 'foo'));
		$this->assertEquals($expected, $this->Socket->config['request']['cookies']);

		$serverResponse = "HTTP/1.x 200 OK\r\nDate: Mon, 16 Apr 2007 04:14:16 GMT\r\nServer: CakeHttp Server\r\nContent-Type: text/html\r\n\r\n<h1>This is a test!</h1>";
		$this->Socket->expects($this->at(1))->method('read')->will($this->returnValue($serverResponse));
		$this->Socket->expects($this->at(2))->method('read')->will($this->returnValue(false));
		$this->Socket->request('/other2');
		$this->assertEquals($expected, $this->Socket->config['request']['cookies']);

		$serverResponse = "HTTP/1.x 200 OK\r\nSet-Cookie: foobar=ok\r\nDate: Mon, 16 Apr 2007 04:14:16 GMT\r\nServer: CakeHttp Server\r\nContent-Type: text/html\r\n\r\n<h1>This is a test!</h1>";
		$this->Socket->expects($this->at(1))->method('read')->will($this->returnValue($serverResponse));
		$this->Socket->expects($this->at(2))->method('read')->will($this->returnValue(false));
		$this->Socket->request('http://www.cake.com');
		$this->assertTrue(empty($this->Socket->request['cookies']));
		$expected['www.cake.com'] = array('foobar' => array('value' => 'ok'));
		$this->assertEquals($expected, $this->Socket->config['request']['cookies']);
	}

/**
 * testRequestCustomResponse
 *
 * @return void
 */
	public function testRequestCustomResponse() {
		$this->Socket->connected = true;
		$serverResponse = "HTTP/1.x 200 OK\r\nDate: Mon, 16 Apr 2007 04:14:16 GMT\r\nServer: CakeHttp Server\r\nContent-Type: text/html\r\n\r\n<h1>This is a test!</h1>";
		$this->Socket->expects($this->at(1))->method('read')->will($this->returnValue($serverResponse));
		$this->Socket->expects($this->at(2))->method('read')->will($this->returnValue(false));

		$this->Socket->responseClass = 'CustomResponse';
		$response = $this->Socket->request('http://www.cakephp.org/');
		$this->assertInstanceOf('CustomResponse', $response);
		$this->assertEquals('HTTP/1.x 2', $response->first10);
	}

/**
 * Test that redirect URLs are urldecoded
 *
 * @return void
 */
	public function testRequestWithRedirectUrlEncoded() {
		$request = array(
			'uri' => 'http://localhost/oneuri',
			'redirect' => 1
		);
		$serverResponse1 = "HTTP/1.x 302 Found\r\nDate: Mon, 16 Apr 2007 04:14:16 GMT\r\nServer: CakeHttp Server\r\nContent-Type: text/html\r\nLocation: http://i.cmpnet.com%2Ftechonline%2Fpdf%2Fa.pdf=\r\n\r\n";
		$serverResponse2 = "HTTP/1.x 200 OK\r\nDate: Mon, 16 Apr 2007 04:14:16 GMT\r\nServer: CakeHttp Server\r\nContent-Type: text/html\r\n\r\n<h1>You have been redirected</h1>";

		$this->Socket->expects($this->at(1))
			->method('read')
			->will($this->returnValue($serverResponse1));

		$this->Socket->expects($this->at(3))
			->method('write')
			->with($this->logicalAnd(
				$this->stringContains('Host: i.cmpnet.com'),
				$this->stringContains('GET /techonline/pdf/a.pdf')
			));

		$this->Socket->expects($this->at(4))
			->method('read')
			->will($this->returnValue($serverResponse2));

		$response = $this->Socket->request($request);
		$this->assertEquals('<h1>You have been redirected</h1>', $response->body());
	}

/**
 * testRequestWithRedirect method
 *
 * @return void
 */
	public function testRequestWithRedirectAsTrue() {
		$request = array(
			'uri' => 'http://localhost/oneuri',
			'redirect' => true
		);
		$serverResponse1 = "HTTP/1.x 302 Found\r\nDate: Mon, 16 Apr 2007 04:14:16 GMT\r\nServer: CakeHttp Server\r\nContent-Type: text/html\r\nLocation: http://localhost/anotheruri\r\n\r\n";
		$serverResponse2 = "HTTP/1.x 200 OK\r\nDate: Mon, 16 Apr 2007 04:14:16 GMT\r\nServer: CakeHttp Server\r\nContent-Type: text/html\r\n\r\n<h1>You have been redirected</h1>";
		$this->Socket->expects($this->at(1))->method('read')->will($this->returnValue($serverResponse1));
		$this->Socket->expects($this->at(4))->method('read')->will($this->returnValue($serverResponse2));

		$response = $this->Socket->request($request);
		$this->assertEquals('<h1>You have been redirected</h1>', $response->body());
	}

/**
 * Test that redirects with a count limit are decremented.
 *
 * @return void
 */
	public function testRequestWithRedirectAsInt() {
		$request = array(
			'uri' => 'http://localhost/oneuri',
			'redirect' => 2
		);
		$serverResponse1 = "HTTP/1.x 302 Found\r\nDate: Mon, 16 Apr 2007 04:14:16 GMT\r\nServer: CakeHttp Server\r\nContent-Type: text/html\r\nLocation: http://localhost/anotheruri\r\n\r\n";
		$serverResponse2 = "HTTP/1.x 200 OK\r\nDate: Mon, 16 Apr 2007 04:14:16 GMT\r\nServer: CakeHttp Server\r\nContent-Type: text/html\r\n\r\n<h1>You have been redirected</h1>";
		$this->Socket->expects($this->at(1))->method('read')->will($this->returnValue($serverResponse1));
		$this->Socket->expects($this->at(4))->method('read')->will($this->returnValue($serverResponse2));

		$this->Socket->request($request);
		$this->assertEquals(1, $this->Socket->request['redirect']);
	}

/**
 * Test that redirects after the redirect count reaches 9 are not followed.
 *
 * @return void
 */
	public function testRequestWithRedirectAsIntReachingZero() {
		$request = array(
			'uri' => 'http://localhost/oneuri',
			'redirect' => 1
		);
		$serverResponse1 = "HTTP/1.x 302 Found\r\nDate: Mon, 16 Apr 2007 04:14:16 GMT\r\nServer: CakeHttp Server\r\nContent-Type: text/html\r\nLocation: http://localhost/oneruri\r\n\r\n";
		$serverResponse2 = "HTTP/1.x 302 Found\r\nDate: Mon, 16 Apr 2007 04:14:16 GMT\r\nServer: CakeHttp Server\r\nContent-Type: text/html\r\nLocation: http://localhost/anotheruri\r\n\r\n";
		$this->Socket->expects($this->at(1))->method('read')->will($this->returnValue($serverResponse1));
		$this->Socket->expects($this->at(4))->method('read')->will($this->returnValue($serverResponse2));

		$response = $this->Socket->request($request);
		$this->assertEquals(0, $this->Socket->request['redirect']);
		$this->assertEquals(302, $response->code);
		$this->assertEquals('http://localhost/anotheruri', $response->getHeader('Location'));
	}

/**
 * testProxy method
 *
 * @return void
 */
	public function testProxy() {
		$this->Socket->reset();
		$this->Socket->expects($this->any())->method('connect')->will($this->returnValue(true));
		$this->Socket->expects($this->any())->method('read')->will($this->returnValue(false));

		$this->Socket->configProxy('proxy.server', 123);
		$expected = "GET http://www.cakephp.org/ HTTP/1.1\r\nHost: www.cakephp.org\r\nConnection: close\r\nUser-Agent: CakePHP\r\n\r\n";
		$this->Socket->request('http://www.cakephp.org/');
		$this->assertEquals($expected, $this->Socket->request['raw']);
		$this->assertEquals('proxy.server', $this->Socket->config['host']);
		$this->assertEquals(123, $this->Socket->config['port']);
		$expected = array(
			'host' => 'proxy.server',
			'port' => 123,
			'method' => null,
			'user' => null,
			'pass' => null
		);
		$this->assertEquals($expected, $this->Socket->request['proxy']);

		$expected = "GET http://www.cakephp.org/bakery HTTP/1.1\r\nHost: www.cakephp.org\r\nConnection: close\r\nUser-Agent: CakePHP\r\n\r\n";
		$this->Socket->request('/bakery');
		$this->assertEquals($expected, $this->Socket->request['raw']);
		$this->assertEquals('proxy.server', $this->Socket->config['host']);
		$this->assertEquals(123, $this->Socket->config['port']);
		$expected = array(
			'host' => 'proxy.server',
			'port' => 123,
			'method' => null,
			'user' => null,
			'pass' => null
		);
		$this->assertEquals($expected, $this->Socket->request['proxy']);

		$expected = "GET http://www.cakephp.org/ HTTP/1.1\r\nHost: www.cakephp.org\r\nConnection: close\r\nUser-Agent: CakePHP\r\nProxy-Authorization: Test mark.secret\r\n\r\n";
		$this->Socket->configProxy('proxy.server', 123, 'Test', 'mark', 'secret');
		$this->Socket->request('http://www.cakephp.org/');
		$this->assertEquals($expected, $this->Socket->request['raw']);
		$this->assertEquals('proxy.server', $this->Socket->config['host']);
		$this->assertEquals(123, $this->Socket->config['port']);
		$expected = array(
			'host' => 'proxy.server',
			'port' => 123,
			'method' => 'Test',
			'user' => 'mark',
			'pass' => 'secret'
		);
		$this->assertEquals($expected, $this->Socket->request['proxy']);

		$this->Socket->configAuth('Test', 'login', 'passwd');
		$expected = "GET http://www.cakephp.org/ HTTP/1.1\r\nHost: www.cakephp.org\r\nConnection: close\r\nUser-Agent: CakePHP\r\nProxy-Authorization: Test mark.secret\r\nAuthorization: Test login.passwd\r\n\r\n";
		$this->Socket->request('http://www.cakephp.org/');
		$this->assertEquals($expected, $this->Socket->request['raw']);
		$expected = array(
			'host' => 'proxy.server',
			'port' => 123,
			'method' => 'Test',
			'user' => 'mark',
			'pass' => 'secret'
		);
		$this->assertEquals($expected, $this->Socket->request['proxy']);
		$expected = array(
			'Test' => array(
				'user' => 'login',
				'pass' => 'passwd'
			)
		);
		$this->assertEquals($expected, $this->Socket->request['auth']);
	}

/**
 * testUrl method
 *
 * @return void
 */
	public function testUrl() {
		$this->Socket->reset(true);

		$this->assertEquals(false, $this->Socket->url(true));

		$url = $this->Socket->url('www.cakephp.org');
		$this->assertEquals('http://www.cakephp.org/', $url);

		$url = $this->Socket->url('https://www.cakephp.org/posts/add');
		$this->assertEquals('https://www.cakephp.org/posts/add', $url);
		$url = $this->Socket->url('http://www.cakephp/search?q=socket', '/%path?%query');
		$this->assertEquals('/search?q=socket', $url);

		$this->Socket->config['request']['uri']['host'] = 'bakery.cakephp.org';
		$url = $this->Socket->url();
		$this->assertEquals('http://bakery.cakephp.org/', $url);

		$this->Socket->configUri('http://www.cakephp.org');
		$url = $this->Socket->url('/search?q=bar');
		$this->assertEquals('http://www.cakephp.org/search?q=bar', $url);

		$url = $this->Socket->url(array('host' => 'www.foobar.org', 'query' => array('q' => 'bar')));
		$this->assertEquals('http://www.foobar.org/?q=bar', $url);

		$url = $this->Socket->url(array('path' => '/supersearch', 'query' => array('q' => 'bar')));
		$this->assertEquals('http://www.cakephp.org/supersearch?q=bar', $url);

		$this->Socket->configUri('http://www.google.com');
		$url = $this->Socket->url('/search?q=socket');
		$this->assertEquals('http://www.google.com/search?q=socket', $url);

		$url = $this->Socket->url();
		$this->assertEquals('http://www.google.com/', $url);

		$this->Socket->configUri('https://www.google.com');
		$url = $this->Socket->url('/search?q=socket');
		$this->assertEquals('https://www.google.com/search?q=socket', $url);

		$this->Socket->reset();
		$this->Socket->configUri('www.google.com:443');
		$url = $this->Socket->url('/search?q=socket');
		$this->assertEquals('https://www.google.com/search?q=socket', $url);

		$this->Socket->reset();
		$this->Socket->configUri('www.google.com:8080');
		$url = $this->Socket->url('/search?q=socket');
		$this->assertEquals('http://www.google.com:8080/search?q=socket', $url);
	}

/**
 * testGet method
 *
 * @return void
 */
	public function testGet() {
		$this->RequestSocket->reset();

		$this->RequestSocket->expects($this->at(0))
			->method('request')
			->with(array('method' => 'GET', 'uri' => 'http://www.google.com/'));

		$this->RequestSocket->expects($this->at(1))
			->method('request')
			->with(array('method' => 'GET', 'uri' => 'http://www.google.com/?foo=bar'));

		$this->RequestSocket->expects($this->at(2))
			->method('request')
			->with(array('method' => 'GET', 'uri' => 'http://www.google.com/?foo=bar'));

		$this->RequestSocket->expects($this->at(3))
			->method('request')
			->with(array('method' => 'GET', 'uri' => 'http://www.google.com/?foo=23&foobar=42'));

		$this->RequestSocket->expects($this->at(4))
			->method('request')
			->with(array('method' => 'GET', 'uri' => 'http://www.google.com/', 'version' => '1.0'));

		$this->RequestSocket->expects($this->at(5))
			->method('request')
			->with(array('method' => 'GET', 'uri' => 'https://secure.example.com/test.php?one=two'));

		$this->RequestSocket->expects($this->at(6))
			->method('request')
			->with(array('method' => 'GET', 'uri' => 'https://example.com/oauth/access?clientid=123&redirect_uri=http%3A%2F%2Fexample.com&code=456'));

		$this->RequestSocket->get('http://www.google.com/');
		$this->RequestSocket->get('http://www.google.com/', array('foo' => 'bar'));
		$this->RequestSocket->get('http://www.google.com/', 'foo=bar');
		$this->RequestSocket->get('http://www.google.com/?foo=bar', array('foobar' => '42', 'foo' => '23'));
		$this->RequestSocket->get('http://www.google.com/', null, array('version' => '1.0'));
		$this->RequestSocket->get('https://secure.example.com/test.php', array('one' => 'two'));
		$this->RequestSocket->get('https://example.com/oauth/access', array(
			'clientid' => '123',
			'redirect_uri' => 'http://example.com',
			'code' => 456
		));
	}

/**
 * Test authentication
 *
 * @return void
 */
	public function testAuth() {
		$socket = new MockHttpSocket();
		$socket->get('http://mark:secret@example.com/test');
		$this->assertTrue(strpos($socket->request['header'], 'Authorization: Basic bWFyazpzZWNyZXQ=') !== false);

		$socket->configAuth(false);
		$socket->get('http://example.com/test');
		$this->assertFalse(strpos($socket->request['header'], 'Authorization:'));

		$socket->configAuth('Test', 'mark', 'passwd');
		$socket->get('http://example.com/test');
		$this->assertTrue(strpos($socket->request['header'], 'Authorization: Test mark.passwd') !== false);

		$socket->configAuth(false);
		$socket->request(array(
			'method' => 'GET',
			'uri' => 'http://example.com/test',
			'auth' => array(
				'method' => 'Basic',
				'user' => 'joel',
				'pass' => 'hunter2'
			)
		));
		$this->assertEquals($socket->request['auth'],array('Basic' => array('user' => 'joel', 'pass' => 'hunter2')));
		$this->assertTrue(strpos($socket->request['header'], 'Authorization: Basic am9lbDpodW50ZXIy') !== false);
	}

/**
 * test that two consecutive get() calls reset the authentication credentials.
 *
 * @return void
 */
	public function testConsecutiveGetResetsAuthCredentials() {
		$socket = new MockHttpSocket();
		$socket->get('http://mark:secret@example.com/test');
		$this->assertEquals('mark', $socket->request['uri']['user']);
		$this->assertEquals('secret', $socket->request['uri']['pass']);
		$this->assertTrue(strpos($socket->request['header'], 'Authorization: Basic bWFyazpzZWNyZXQ=') !== false);

		$socket->get('/test2');
		$this->assertTrue(strpos($socket->request['header'], 'Authorization: Basic bWFyazpzZWNyZXQ=') !== false);

		$socket->get('/test3');
		$this->assertTrue(strpos($socket->request['header'], 'Authorization: Basic bWFyazpzZWNyZXQ=') !== false);
	}

/**
 * testPostPutDelete method
 *
 * @return void
 */
	public function testPost() {
		$this->RequestSocket->reset();
		$this->RequestSocket->expects($this->at(0))
			->method('request')
			->with(array('method' => 'POST', 'uri' => 'http://www.google.com/', 'body' => array()));

		$this->RequestSocket->expects($this->at(1))
			->method('request')
			->with(array('method' => 'POST', 'uri' => 'http://www.google.com/', 'body' => array('Foo' => 'bar')));

		$this->RequestSocket->expects($this->at(2))
			->method('request')
			->with(array('method' => 'POST', 'uri' => 'http://www.google.com/', 'body' => null, 'line' => 'Hey Server'));

		$this->RequestSocket->post('http://www.google.com/');
		$this->RequestSocket->post('http://www.google.com/', array('Foo' => 'bar'));
		$this->RequestSocket->post('http://www.google.com/', null, array('line' => 'Hey Server'));
	}

/**
 * testPut
 *
 * @return void
 */
	public function testPut() {
		$this->RequestSocket->reset();
		$this->RequestSocket->expects($this->at(0))
			->method('request')
			->with(array('method' => 'PUT', 'uri' => 'http://www.google.com/', 'body' => array()));

		$this->RequestSocket->expects($this->at(1))
			->method('request')
			->with(array('method' => 'PUT', 'uri' => 'http://www.google.com/', 'body' => array('Foo' => 'bar')));

		$this->RequestSocket->expects($this->at(2))
			->method('request')
			->with(array('method' => 'PUT', 'uri' => 'http://www.google.com/', 'body' => null, 'line' => 'Hey Server'));

		$this->RequestSocket->put('http://www.google.com/');
		$this->RequestSocket->put('http://www.google.com/', array('Foo' => 'bar'));
		$this->RequestSocket->put('http://www.google.com/', null, array('line' => 'Hey Server'));
	}

/**
 * testDelete
 *
 * @return void
 */
	public function testDelete() {
		$this->RequestSocket->reset();
		$this->RequestSocket->expects($this->at(0))
			->method('request')
			->with(array('method' => 'DELETE', 'uri' => 'http://www.google.com/', 'body' => array()));

		$this->RequestSocket->expects($this->at(1))
			->method('request')
			->with(array('method' => 'DELETE', 'uri' => 'http://www.google.com/', 'body' => array('Foo' => 'bar')));

		$this->RequestSocket->expects($this->at(2))
			->method('request')
			->with(array('method' => 'DELETE', 'uri' => 'http://www.google.com/', 'body' => null, 'line' => 'Hey Server'));

		$this->RequestSocket->delete('http://www.google.com/');
		$this->RequestSocket->delete('http://www.google.com/', array('Foo' => 'bar'));
		$this->RequestSocket->delete('http://www.google.com/', null, array('line' => 'Hey Server'));
	}

/**
 * testBuildRequestLine method
 *
 * @return void
 */
	public function testBuildRequestLine() {
		$this->Socket->reset();

		$this->Socket->quirksMode = true;
		$r = $this->Socket->buildRequestLine('Foo');
		$this->assertEquals('Foo', $r);
		$this->Socket->quirksMode = false;

		$r = $this->Socket->buildRequestLine(true);
		$this->assertEquals(false, $r);

		$r = $this->Socket->buildRequestLine(array('foo' => 'bar', 'method' => 'foo'));
		$this->assertEquals(false, $r);

		$r = $this->Socket->buildRequestLine(array('method' => 'GET', 'uri' => 'http://www.cakephp.org/search?q=socket'));
		$this->assertEquals("GET /search?q=socket HTTP/1.1\r\n", $r);

		$request = array(
			'method' => 'GET',
			'uri' => array(
				'path' => '/search',
				'query' => array('q' => 'socket')
			)
		);
		$r = $this->Socket->buildRequestLine($request);
		$this->assertEquals("GET /search?q=socket HTTP/1.1\r\n", $r);

		unset($request['method']);
		$r = $this->Socket->buildRequestLine($request);
		$this->assertEquals("GET /search?q=socket HTTP/1.1\r\n", $r);

		$r = $this->Socket->buildRequestLine($request, 'CAKE-HTTP/0.1');
		$this->assertEquals("GET /search?q=socket CAKE-HTTP/0.1\r\n", $r);

		$request = array('method' => 'OPTIONS', 'uri' => '*');
		$r = $this->Socket->buildRequestLine($request);
		$this->assertEquals("OPTIONS * HTTP/1.1\r\n", $r);

		$request['method'] = 'GET';
		$this->Socket->quirksMode = true;
		$r = $this->Socket->buildRequestLine($request);
		$this->assertEquals("GET * HTTP/1.1\r\n", $r);

		$r = $this->Socket->buildRequestLine("GET * HTTP/1.1\r\n");
		$this->assertEquals("GET * HTTP/1.1\r\n", $r);
	}

/**
 * testBadBuildRequestLine method
 *
 * @expectedException SocketException
 * @return void
 */
	public function testBadBuildRequestLine() {
		$this->Socket->buildRequestLine('Foo');
	}

/**
 * testBadBuildRequestLine2 method
 *
 * @expectedException SocketException
 * @return void
 */
	public function testBadBuildRequestLine2() {
		$this->Socket->buildRequestLine("GET * HTTP/1.1\r\n");
	}

/**
 * Asserts that HttpSocket::parseUri is working properly
 *
 * @return void
 */
	public function testParseUri() {
		$this->Socket->reset();

		$uri = $this->Socket->parseUri(array('invalid' => 'uri-string'));
		$this->assertEquals(false, $uri);

		$uri = $this->Socket->parseUri(array('invalid' => 'uri-string'), array('host' => 'somehost'));
		$this->assertEquals(array('host' => 'somehost', 'invalid' => 'uri-string'), $uri);

		$uri = $this->Socket->parseUri(false);
		$this->assertEquals(false, $uri);

		$uri = $this->Socket->parseUri('/my-cool-path');
		$this->assertEquals(array('path' => '/my-cool-path'), $uri);

		$uri = $this->Socket->parseUri('http://bob:foo123@www.cakephp.org:40/search?q=dessert#results');
		$this->assertEquals($uri, array(
			'scheme' => 'http',
			'host' => 'www.cakephp.org',
			'port' => 40,
			'user' => 'bob',
			'pass' => 'foo123',
			'path' => '/search',
			'query' => array('q' => 'dessert'),
			'fragment' => 'results'
		));

		$uri = $this->Socket->parseUri('http://www.cakephp.org/');
		$this->assertEquals($uri, array(
			'scheme' => 'http',
			'host' => 'www.cakephp.org',
			'path' => '/'
		));

		$uri = $this->Socket->parseUri('http://www.cakephp.org', true);
		$this->assertEquals($uri, array(
			'scheme' => 'http',
			'host' => 'www.cakephp.org',
			'port' => 80,
			'user' => null,
			'pass' => null,
			'path' => '/',
			'query' => array(),
			'fragment' => null
		));

		$uri = $this->Socket->parseUri('https://www.cakephp.org', true);
		$this->assertEquals($uri, array(
			'scheme' => 'https',
			'host' => 'www.cakephp.org',
			'port' => 443,
			'user' => null,
			'pass' => null,
			'path' => '/',
			'query' => array(),
			'fragment' => null
		));

		$uri = $this->Socket->parseUri('www.cakephp.org:443/query?foo', true);
		$this->assertEquals($uri, array(
			'scheme' => 'https',
			'host' => 'www.cakephp.org',
			'port' => 443,
			'user' => null,
			'pass' => null,
			'path' => '/query',
			'query' => array('foo' => ""),
			'fragment' => null
		));

		$uri = $this->Socket->parseUri('http://www.cakephp.org', array('host' => 'piephp.org', 'user' => 'bob', 'fragment' => 'results'));
		$this->assertEquals($uri, array(
			'host' => 'www.cakephp.org',
			'user' => 'bob',
			'fragment' => 'results',
			'scheme' => 'http'
		));

		$uri = $this->Socket->parseUri('https://www.cakephp.org', array('scheme' => 'http', 'port' => 23));
		$this->assertEquals($uri, array(
			'scheme' => 'https',
			'port' => 23,
			'host' => 'www.cakephp.org'
		));

		$uri = $this->Socket->parseUri('www.cakephp.org:59', array('scheme' => array('http', 'https'), 'port' => 80));
		$this->assertEquals($uri, array(
			'scheme' => 'http',
			'port' => 59,
			'host' => 'www.cakephp.org'
		));

		$uri = $this->Socket->parseUri(array('scheme' => 'http', 'host' => 'www.google.com', 'port' => 8080), array('scheme' => array('http', 'https'), 'host' => 'www.google.com', 'port' => array(80, 443)));
		$this->assertEquals($uri, array(
			'scheme' => 'http',
			'host' => 'www.google.com',
			'port' => 8080
		));

		$uri = $this->Socket->parseUri('http://www.cakephp.org/?param1=value1&param2=value2%3Dvalue3');
		$this->assertEquals($uri, array(
			'scheme' => 'http',
			'host' => 'www.cakephp.org',
			'path' => '/',
			'query' => array(
				'param1' => 'value1',
				'param2' => 'value2=value3'
			)
		));

		$uri = $this->Socket->parseUri('http://www.cakephp.org/?param1=value1&param2=value2=value3');
		$this->assertEquals($uri, array(
			'scheme' => 'http',
			'host' => 'www.cakephp.org',
			'path' => '/',
			'query' => array(
				'param1' => 'value1',
				'param2' => 'value2=value3'
			)
		));
	}

/**
 * Tests that HttpSocket::buildUri can turn all kinds of uri arrays (and strings) into fully or partially qualified URI's
 *
 * @return void
 */
	public function testBuildUri() {
		$this->Socket->reset();

		$r = $this->Socket->buildUri(true);
		$this->assertEquals(false, $r);

		$r = $this->Socket->buildUri('foo.com');
		$this->assertEquals('http://foo.com/', $r);

		$r = $this->Socket->buildUri(array('host' => 'www.cakephp.org'));
		$this->assertEquals('http://www.cakephp.org/', $r);

		$r = $this->Socket->buildUri(array('host' => 'www.cakephp.org', 'scheme' => 'https'));
		$this->assertEquals('https://www.cakephp.org/', $r);

		$r = $this->Socket->buildUri(array('host' => 'www.cakephp.org', 'port' => 23));
		$this->assertEquals('http://www.cakephp.org:23/', $r);

		$r = $this->Socket->buildUri(array('path' => 'www.google.com/search', 'query' => 'q=cakephp'));
		$this->assertEquals('http://www.google.com/search?q=cakephp', $r);

		$r = $this->Socket->buildUri(array('host' => 'www.cakephp.org', 'scheme' => 'https', 'port' => 79));
		$this->assertEquals('https://www.cakephp.org:79/', $r);

		$r = $this->Socket->buildUri(array('host' => 'www.cakephp.org', 'path' => 'foo'));
		$this->assertEquals('http://www.cakephp.org/foo', $r);

		$r = $this->Socket->buildUri(array('host' => 'www.cakephp.org', 'path' => '/foo'));
		$this->assertEquals('http://www.cakephp.org/foo', $r);

		$r = $this->Socket->buildUri(array('host' => 'www.cakephp.org', 'path' => '/search', 'query' => array('q' => 'HttpSocket')));
		$this->assertEquals('http://www.cakephp.org/search?q=HttpSocket', $r);

		$r = $this->Socket->buildUri(array('host' => 'www.cakephp.org', 'fragment' => 'bar'));
		$this->assertEquals('http://www.cakephp.org/#bar', $r);

		$r = $this->Socket->buildUri(array(
			'scheme' => 'https',
			'host' => 'www.cakephp.org',
			'port' => 25,
			'user' => 'bob',
			'pass' => 'secret',
			'path' => '/cool',
			'query' => array('foo' => 'bar'),
			'fragment' => 'comment'
		));
		$this->assertEquals('https://bob:secret@www.cakephp.org:25/cool?foo=bar#comment', $r);

		$r = $this->Socket->buildUri(array('host' => 'www.cakephp.org', 'fragment' => 'bar'), '%fragment?%host');
		$this->assertEquals('bar?www.cakephp.org', $r);

		$r = $this->Socket->buildUri(array('host' => 'www.cakephp.org'), '%fragment???%host');
		$this->assertEquals('???www.cakephp.org', $r);

		$r = $this->Socket->buildUri(array('path' => '*'), '/%path?%query');
		$this->assertEquals('*', $r);

		$r = $this->Socket->buildUri(array('scheme' => 'foo', 'host' => 'www.cakephp.org'));
		$this->assertEquals('foo://www.cakephp.org:80/', $r);
	}

/**
 * Asserts that HttpSocket::parseQuery is working properly
 *
 * @return void
 */
	public function testParseQuery() {
		$this->Socket->reset();

		$query = $this->Socket->parseQuery(array('framework' => 'cakephp'));
		$this->assertEquals(array('framework' => 'cakephp'), $query);

		$query = $this->Socket->parseQuery('');
		$this->assertEquals(array(), $query);

		$query = $this->Socket->parseQuery('framework=cakephp');
		$this->assertEquals(array('framework' => 'cakephp'), $query);

		$query = $this->Socket->parseQuery('?framework=cakephp');
		$this->assertEquals(array('framework' => 'cakephp'), $query);

		$query = $this->Socket->parseQuery('a&b&c');
		$this->assertEquals(array('a' => '', 'b' => '', 'c' => ''), $query);

		$query = $this->Socket->parseQuery('value=12345');
		$this->assertEquals(array('value' => '12345'), $query);

		$query = $this->Socket->parseQuery('a[0]=foo&a[1]=bar&a[2]=cake');
		$this->assertEquals(array('a' => array(0 => 'foo', 1 => 'bar', 2 => 'cake')), $query);

		$query = $this->Socket->parseQuery('a[]=foo&a[]=bar&a[]=cake');
		$this->assertEquals(array('a' => array(0 => 'foo', 1 => 'bar', 2 => 'cake')), $query);

		$query = $this->Socket->parseQuery('a[][]=foo&a[][]=bar&a[][]=cake');
		$expectedQuery = array(
			'a' => array(
				0 => array(
					0 => 'foo'
				),
				1 => array(
					0 => 'bar'
				),
				array(
					0 => 'cake'
				)
			)
		);
		$this->assertEquals($expectedQuery, $query);

		$query = $this->Socket->parseQuery('a[][]=foo&a[bar]=php&a[][]=bar&a[][]=cake');
		$expectedQuery = array(
			'a' => array(
				array('foo'),
				'bar' => 'php',
				array('bar'),
				array('cake')
			)
		);
		$this->assertEquals($expectedQuery, $query);

		$query = $this->Socket->parseQuery('user[]=jim&user[3]=tom&user[]=bob');
		$expectedQuery = array(
			'user' => array(
				0 => 'jim',
				3 => 'tom',
				4 => 'bob'
			)
		);
		$this->assertEquals($expectedQuery, $query);

		$queryStr = 'user[0]=foo&user[0][items][]=foo&user[0][items][]=bar&user[][name]=jim&user[1][items][personal][]=book&user[1][items][personal][]=pen&user[1][items][]=ball&user[count]=2&empty';
		$query = $this->Socket->parseQuery($queryStr);
		$expectedQuery = array(
			'user' => array(
				0 => array(
					'items' => array(
						'foo',
						'bar'
					)
				),
				1 => array(
					'name' => 'jim',
					'items' => array(
						'personal' => array(
							'book'
							, 'pen'
						),
						'ball'
					)
				),
				'count' => '2'
			),
			'empty' => ''
		);
		$this->assertEquals($expectedQuery, $query);

		$query = 'openid.ns=example.com&foo=bar&foo=baz';
		$result = $this->Socket->parseQuery($query);
		$expected = array(
			'openid.ns' => 'example.com',
			'foo' => array('bar', 'baz')
		);
		$this->assertEquals($expected, $result);
	}

/**
 * Tests that HttpSocket::buildHeader can turn a given $header array into a proper header string according to
 * HTTP 1.1 specs.
 *
 * @return void
 */
	public function testBuildHeader() {
		$this->Socket->reset();

		$r = $this->Socket->buildHeader(true);
		$this->assertEquals(false, $r);

		$r = $this->Socket->buildHeader('My raw header');
		$this->assertEquals('My raw header', $r);

		$r = $this->Socket->buildHeader(array('Host' => 'www.cakephp.org'));
		$this->assertEquals("Host: www.cakephp.org\r\n", $r);

		$r = $this->Socket->buildHeader(array('Host' => 'www.cakephp.org', 'Connection' => 'Close'));
		$this->assertEquals("Host: www.cakephp.org\r\nConnection: Close\r\n", $r);

		$r = $this->Socket->buildHeader(array('People' => array('Bob', 'Jim', 'John')));
		$this->assertEquals("People: Bob,Jim,John\r\n", $r);

		$r = $this->Socket->buildHeader(array('Multi-Line-Field' => "This is my\r\nMulti Line field"));
		$this->assertEquals("Multi-Line-Field: This is my\r\n Multi Line field\r\n", $r);

		$r = $this->Socket->buildHeader(array('Multi-Line-Field' => "This is my\r\n Multi Line field"));
		$this->assertEquals("Multi-Line-Field: This is my\r\n Multi Line field\r\n", $r);

		$r = $this->Socket->buildHeader(array('Multi-Line-Field' => "This is my\r\n\tMulti Line field"));
		$this->assertEquals("Multi-Line-Field: This is my\r\n\tMulti Line field\r\n", $r);

		$r = $this->Socket->buildHeader(array('Test@Field' => "My value"));
		$this->assertEquals("Test\"@\"Field: My value\r\n", $r);
	}

/**
 * testBuildCookies method
 *
 * @return void
 */
	public function testBuildCookies() {
		$cookies = array(
			'foo' => array(
				'value' => 'bar'
			),
			'people' => array(
				'value' => 'jim,jack,johnny;',
				'path' => '/accounts'
			),
			'key' => 'value'
		);
		$expect = "Cookie: foo=bar; people=jim,jack,johnny\";\"; key=value\r\n";
		$result = $this->Socket->buildCookies($cookies);
		$this->assertEquals($expect, $result);
	}

/**
 * Tests that HttpSocket::_tokenEscapeChars() returns the right characters.
 *
 * @return void
 */
	public function testTokenEscapeChars() {
		$this->Socket->reset();

		$expected = array(
			'\x22','\x28','\x29','\x3c','\x3e','\x40','\x2c','\x3b','\x3a','\x5c','\x2f','\x5b','\x5d','\x3f','\x3d','\x7b',
			'\x7d','\x20','\x00','\x01','\x02','\x03','\x04','\x05','\x06','\x07','\x08','\x09','\x0a','\x0b','\x0c','\x0d',
			'\x0e','\x0f','\x10','\x11','\x12','\x13','\x14','\x15','\x16','\x17','\x18','\x19','\x1a','\x1b','\x1c','\x1d',
			'\x1e','\x1f','\x7f'
		);
		$r = $this->Socket->tokenEscapeChars();
		$this->assertEquals($expected, $r);

		foreach ($expected as $key => $char) {
			$expected[$key] = chr(hexdec(substr($char, 2)));
		}

		$r = $this->Socket->tokenEscapeChars(false);
		$this->assertEquals($expected, $r);
	}

/**
 * Test that HttpSocket::escapeToken is escaping all characters as described in RFC 2616 (HTTP 1.1 specs)
 *
 * @return void
 */
	public function testEscapeToken() {
		$this->Socket->reset();

		$this->assertEquals('Foo', $this->Socket->escapeToken('Foo'));

		$escape = $this->Socket->tokenEscapeChars(false);
		foreach ($escape as $char) {
			$token = 'My-special-' . $char . '-Token';
			$escapedToken = $this->Socket->escapeToken($token);
			$expectedToken = 'My-special-"' . $char . '"-Token';

			$this->assertEquals($expectedToken, $escapedToken, 'Test token escaping for ASCII ' . ord($char));
		}

		$token = 'Extreme-:Token-	-"@-test';
		$escapedToken = $this->Socket->escapeToken($token);
		$expectedToken = 'Extreme-":"Token-"	"-""""@"-test';
		$this->assertEquals($expectedToken, $escapedToken);
	}

/**
 * This tests asserts HttpSocket::reset() resets a HttpSocket instance to it's initial state (before Object::__construct
 * got executed)
 *
 * @return void
 */
	public function testReset() {
		$this->Socket->reset();

		$initialState = get_class_vars('HttpSocket');
		foreach ($initialState as $property => $value) {
			$this->Socket->{$property} = 'Overwritten';
		}

		$return = $this->Socket->reset();

		foreach ($initialState as $property => $value) {
			$this->assertEquals($this->Socket->{$property}, $value);
		}

		$this->assertEquals(true, $return);
	}

/**
 * This tests asserts HttpSocket::reset(false) resets certain HttpSocket properties to their initial state (before
 * Object::__construct got executed).
 *
 * @return void
 */
	public function testPartialReset() {
		$this->Socket->reset();

		$partialResetProperties = array('request', 'response');
		$initialState = get_class_vars('HttpSocket');

		foreach ($initialState as $property => $value) {
			$this->Socket->{$property} = 'Overwritten';
		}

		$return = $this->Socket->reset(false);

		foreach ($initialState as $property => $originalValue) {
			if (in_array($property, $partialResetProperties)) {
				$this->assertEquals($this->Socket->{$property}, $originalValue);
			} else {
				$this->assertEquals('Overwritten', $this->Socket->{$property});
			}
		}
		$this->assertEquals(true, $return);
	}

/**
 * test configuring the context from the flat keys.
 *
 * @return void
 */
	public function testConfigContext() {
		$this->Socket->reset();
		$this->Socket->request('http://example.com');
		$this->assertTrue($this->Socket->config['context']['ssl']['verify_peer']);
		$this->assertEquals(5, $this->Socket->config['context']['ssl']['verify_depth']);
		$this->assertEquals('example.com', $this->Socket->config['context']['ssl']['CN_match']);
		$this->assertArrayNotHasKey('ssl_verify_peer', $this->Socket->config);
		$this->assertArrayNotHasKey('ssl_verify_host', $this->Socket->config);
		$this->assertArrayNotHasKey('ssl_verify_depth', $this->Socket->config);
	}

/**
 * Test that requests fail when peer verification fails.
 *
 * @return void
 */
	public function testVerifyPeer() {
		$this->skipIf(!extension_loaded('openssl'), 'OpenSSL is not enabled cannot test SSL.');
		$socket = new HttpSocket();
		try {
			$socket->get('https://typography.com');
			$this->markTestSkipped('Found valid certificate, was expecting invalid certificate.');
		} catch (SocketException $e) {
			$message = $e->getMessage();
			$this->skipIf(strpos($message, 'Invalid HTTP') !== false, 'Invalid HTTP Response received, skipping.');
			$this->assertContains('Peer certificate CN', $message);
			$this->assertContains('Failed to enable crypto', $message);
		}
	}
}
