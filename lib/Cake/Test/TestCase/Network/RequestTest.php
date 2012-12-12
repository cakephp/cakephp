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
 * @package       Cake.Test.Case.Network
 * @since         CakePHP(tm) v 2.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
namespace Cake\Test\TestCase\Network;

use Cake\Core\Configure;
use Cake\Error;
use Cake\Network\Request;
use Cake\Routing\Dispatcher;
use Cake\TestSuite\TestCase;
use Cake\Utility\Xml;

class RequestTest extends TestCase {

/**
 * setup callback
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();
		$this->_case = null;
		if (isset($_GET['case'])) {
			$this->_case = $_GET['case'];
			unset($_GET['case']);
		}

		Configure::write('App.baseUrl', false);
	}

/**
 * tearDown
 *
 * @return void
 */
	public function tearDown() {
		parent::tearDown();
		if (!empty($this->_case)) {
			$_GET['case'] = $this->_case;
		}
	}

/**
 * test that the autoparse = false constructor works.
 *
 * @return void
 */
	public function testNoAutoParseConstruction() {
		$_GET = array(
			'one' => 'param'
		);
		$request = new Request();
		$this->assertFalse(isset($request->query['one']));
	}

/**
 * test construction
 *
 * @return void
 */
	public function testConstructionQueryData() {
		$data = array(
			'query' => array(
				'one' => 'param',
				'two' => 'banana'
			),
			'url' => 'some/path'
		);
		$request = new Request($data);
		$this->assertEquals($request->query, $data['query']);
		$this->assertEquals('some/path', $request->url);
	}

/**
 * Test that querystring args provided in the url string are parsed.
 *
 * @return void
 */
	public function testQueryStringParsingFromInputUrl() {
		$_GET = array();
		$request = new Request(array('url' => 'some/path?one=something&two=else'));
		$expected = array('one' => 'something', 'two' => 'else');
		$this->assertEquals($expected, $request->query);
		$this->assertEquals('some/path?one=something&two=else', $request->url);
	}

/**
 * Test that named arguments + querystrings are handled correctly.
 *
 * @return void
 */
	public function testQueryStringAndNamedParams() {
		$_SERVER['REQUEST_URI'] = '/tasks/index?ts=123456';
		$request = Request::createFromGlobals();
		$this->assertEquals('tasks/index', $request->url);

		$_SERVER['REQUEST_URI'] = '/tasks/index/?ts=123456';
		$request = Request::createFromGlobals();
		$this->assertEquals('tasks/index/', $request->url);
	}

/**
 * test addParams() method
 *
 * @return void
 */
	public function testAddParams() {
		$request = new Request();
		$request->params = array('controller' => 'posts', 'action' => 'view');
		$result = $request->addParams(array('plugin' => null, 'action' => 'index'));

		$this->assertSame($result, $request, 'Method did not return itself. %s');

		$this->assertEquals('posts', $request->controller);
		$this->assertEquals('index', $request->action);
		$this->assertEquals(null, $request->plugin);
	}

/**
 * test splicing in paths.
 *
 * @return void
 */
	public function testAddPaths() {
		$request = new Request();
		$request->webroot = '/some/path/going/here/';
		$result = $request->addPaths(array(
			'random' => '/something', 'webroot' => '/', 'here' => '/', 'base' => '/base_dir'
		));

		$this->assertSame($result, $request, 'Method did not return itself. %s');

		$this->assertEquals('/', $request->webroot);
		$this->assertEquals('/base_dir', $request->base);
		$this->assertEquals('/', $request->here);
		$this->assertFalse(isset($request->random));
	}

/**
 * test parsing POST data into the object.
 *
 * @return void
 */
	public function testPostParsing() {
		$post = array(
			'Article' => array('title')
		);
		$request = new Request(compact('post'));
		$this->assertEquals($post, $request->data);

		$post = array('one' => 1, 'two' => 'three');
		$request = new Request(compact('post'));
		$this->assertEquals($post, $request->data);

		$post = array(
			'Article' => array('title' => 'Testing'),
			'action' => 'update'
		);
		$request = new Request(compact('post'));
		$this->assertEquals($post, $request->data);
	}

/**
 * test parsing PUT data into the object.
 *
 * @return void
 */
	public function testPutParsing() {
		$_SERVER['REQUEST_METHOD'] = 'PUT';
		$_SERVER['CONTENT_TYPE'] = 'application/x-www-form-urlencoded; charset=UTF-8';

		$data = array(
			'Article' => array('title')
		);
		$request = new Request(array(
			'input' => 'Article[]=title'
		));
		$this->assertEquals($data, $request->data);

		$data = array('one' => 1, 'two' => 'three');
		$request = new Request(array(
			'input' => 'one=1&two=three'
		));
		$this->assertEquals($data, $request->data);

		$_SERVER['REQUEST_METHOD'] = 'DELETE';
		$request = new Request(array(
			'input' => 'Article[title]=Testing&action=update'
		));
		$expected = array(
			'Article' => array('title' => 'Testing'),
			'action' => 'update'
		);
		$this->assertEquals($expected, $request->data);

		$_SERVER['REQUEST_METHOD'] = 'PATCH';
		$data = array(
			'Article' => array('title'),
			'Tag' => array('Tag' => array(1, 2))
		);
		$request = new Request(array(
			'input' => 'Article[]=title&Tag[Tag][]=1&Tag[Tag][]=2'
		));
		$this->assertEquals($data, $request->data);
	}

/**
 * test parsing json PUT data into the object.
 *
 * @return void
 */
	public function testPutParsingJSON() {
		$_SERVER['REQUEST_METHOD'] = 'PUT';
		$_SERVER['CONTENT_TYPE'] = 'application/json';

		$data = '{Article":["title"]}';
		$request = new Request([
			'input' => $data
		]);
		$this->assertEquals([], $request->data);
		$result = $request->input('json_decode', true);
		$this->assertEquals(['title'], $result['Article']);
	}

/**
 * test parsing of FILES array
 *
 * @return void
 */
	public function testFilesParsing() {
		$files = array(
			'name' => array(
				'File' => array(
						array('data' => 'cake_sqlserver_patch.patch'),
						array('data' => 'controller.diff'),
						array('data' => ''),
						array('data' => ''),
					),
					'Post' => array('attachment' => 'jquery-1.2.1.js'),
				),
			'type' => array(
				'File' => array(
					array('data' => ''),
					array('data' => ''),
					array('data' => ''),
					array('data' => ''),
				),
				'Post' => array('attachment' => 'application/x-javascript'),
			),
			'tmp_name' => array(
				'File' => array(
					array('data' => '/private/var/tmp/phpy05Ywj'),
					array('data' => '/private/var/tmp/php7MBztY'),
					array('data' => ''),
					array('data' => ''),
				),
				'Post' => array('attachment' => '/private/var/tmp/phpEwlrIo'),
			),
			'error' => array(
				'File' => array(
					array('data' => 0),
					array('data' => 0),
					array('data' => 4),
					array('data' => 4)
				),
				'Post' => array('attachment' => 0)
			),
			'size' => array(
				'File' => array(
					array('data' => 6271),
					array('data' => 350),
					array('data' => 0),
					array('data' => 0),
				),
				'Post' => array('attachment' => 80469)
			),
		);

		$request = new Request(compact('files'));
		$expected = array(
			'File' => array(
				array(
					'data' => array(
						'name' => 'cake_sqlserver_patch.patch',
						'type' => '',
						'tmp_name' => '/private/var/tmp/phpy05Ywj',
						'error' => 0,
						'size' => 6271,
					)
				),
				array(
					'data' => array(
						'name' => 'controller.diff',
						'type' => '',
						'tmp_name' => '/private/var/tmp/php7MBztY',
						'error' => 0,
						'size' => 350,
					)
				),
				array(
					'data' => array(
						'name' => '',
						'type' => '',
						'tmp_name' => '',
						'error' => 4,
						'size' => 0,
					)
				),
				array(
					'data' => array(
						'name' => '',
						'type' => '',
						'tmp_name' => '',
						'error' => 4,
						'size' => 0,
					)
				),
			),
			'Post' => array(
				'attachment' => array(
					'name' => 'jquery-1.2.1.js',
					'type' => 'application/x-javascript',
					'tmp_name' => '/private/var/tmp/phpEwlrIo',
					'error' => 0,
					'size' => 80469,
				)
			)
		);
		$this->assertEquals($expected, $request->data);

		$files = array(
			'name' => array(
				'Document' => array(
					1 => array(
						'birth_cert' => 'born on.txt',
						'passport' => 'passport.txt',
						'drivers_license' => 'ugly pic.jpg'
					),
					2 => array(
						'birth_cert' => 'aunt betty.txt',
						'passport' => 'betty-passport.txt',
						'drivers_license' => 'betty-photo.jpg'
					),
				),
			),
			'type' => array(
				'Document' => array(
					1 => array(
						'birth_cert' => 'application/octet-stream',
						'passport' => 'application/octet-stream',
						'drivers_license' => 'application/octet-stream',
					),
					2 => array(
						'birth_cert' => 'application/octet-stream',
						'passport' => 'application/octet-stream',
						'drivers_license' => 'application/octet-stream',
					)
				)
			),
			'tmp_name' => array(
				'Document' => array(
					1 => array(
						'birth_cert' => '/private/var/tmp/phpbsUWfH',
						'passport' => '/private/var/tmp/php7f5zLt',
						'drivers_license' => '/private/var/tmp/phpMXpZgT',
					),
					2 => array(
						'birth_cert' => '/private/var/tmp/php5kHZt0',
						'passport' => '/private/var/tmp/phpnYkOuM',
						'drivers_license' => '/private/var/tmp/php9Rq0P3',
					)
				)
			),
			'error' => array(
				'Document' => array(
					1 => array(
						'birth_cert' => 0,
						'passport' => 0,
						'drivers_license' => 0,
					),
					2 => array(
						'birth_cert' => 0,
						'passport' => 0,
						'drivers_license' => 0,
					)
				)
			),
			'size' => array(
				'Document' => array(
					1 => array(
						'birth_cert' => 123,
						'passport' => 458,
						'drivers_license' => 875,
					),
					2 => array(
						'birth_cert' => 876,
						'passport' => 976,
						'drivers_license' => 9783,
					)
				)
			)
		);

		$request = new Request(compact('files'));
		$expected = array(
			'Document' => array(
				1 => array(
					'birth_cert' => array(
						'name' => 'born on.txt',
						'tmp_name' => '/private/var/tmp/phpbsUWfH',
						'error' => 0,
						'size' => 123,
						'type' => 'application/octet-stream',
					),
					'passport' => array(
						'name' => 'passport.txt',
						'tmp_name' => '/private/var/tmp/php7f5zLt',
						'error' => 0,
						'size' => 458,
						'type' => 'application/octet-stream',
					),
					'drivers_license' => array(
						'name' => 'ugly pic.jpg',
						'tmp_name' => '/private/var/tmp/phpMXpZgT',
						'error' => 0,
						'size' => 875,
						'type' => 'application/octet-stream',
					),
				),
				2 => array(
					'birth_cert' => array(
						'name' => 'aunt betty.txt',
						'tmp_name' => '/private/var/tmp/php5kHZt0',
						'error' => 0,
						'size' => 876,
						'type' => 'application/octet-stream',
					),
					'passport' => array(
						'name' => 'betty-passport.txt',
						'tmp_name' => '/private/var/tmp/phpnYkOuM',
						'error' => 0,
						'size' => 976,
						'type' => 'application/octet-stream',
					),
					'drivers_license' => array(
						'name' => 'betty-photo.jpg',
						'tmp_name' => '/private/var/tmp/php9Rq0P3',
						'error' => 0,
						'size' => 9783,
						'type' => 'application/octet-stream',
					),
				),
			)
		);
		$this->assertEquals($expected, $request->data);

		$files = array(
			'name' => array('birth_cert' => 'born on.txt'),
			'type' => array('birth_cert' => 'application/octet-stream'),
			'tmp_name' => array('birth_cert' => '/private/var/tmp/phpbsUWfH'),
			'error' => array('birth_cert' => 0),
			'size' => array('birth_cert' => 123)
		);

		$request = new Request(compact('files'));
		$expected = array(
			'birth_cert' => array(
				'name' => 'born on.txt',
				'type' => 'application/octet-stream',
				'tmp_name' => '/private/var/tmp/phpbsUWfH',
				'error' => 0,
				'size' => 123
			)
		);
		$this->assertEquals($expected, $request->data);
	}

/**
 * Test that files in the 0th index work.
 */
	public function testFilesZeroithIndex() {
		$_FILES = array(
			0 => array(
				'name' => 'cake_sqlserver_patch.patch',
				'type' => 'text/plain',
				'tmp_name' => '/private/var/tmp/phpy05Ywj',
				'error' => 0,
				'size' => 6271,
			),
		);

		$request = new Request('some/path');
		$this->assertEquals($_FILES, $request->params['form']);
	}

/**
 * test method overrides coming in from POST data.
 *
 * @return void
 */
	public function testMethodOverrides() {
		$post = array('_method' => 'POST');
		$request = new Request(compact('post'));
		$this->assertEquals(env('REQUEST_METHOD'), 'POST');

		$post = array('_method' => 'DELETE');
		$request = new Request(compact('post'));
		$this->assertEquals(env('REQUEST_METHOD'), 'DELETE');

		$_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE'] = 'PUT';
		$request = new Request();
		$this->assertEquals(env('REQUEST_METHOD'), 'PUT');
	}

/**
 * test the clientIp method.
 *
 * @return void
 */
	public function testclientIp() {
		$_SERVER['HTTP_X_FORWARDED_FOR'] = '192.168.1.5, 10.0.1.1, proxy.com';
		$_SERVER['HTTP_CLIENT_IP'] = '192.168.1.2';
		$_SERVER['REMOTE_ADDR'] = '192.168.1.3';
		$request = new Request();

		$request->trustProxy = true;
		$this->assertEquals('192.168.1.5', $request->clientIp());

		$request->trustProxy = false;
		$this->assertEquals('192.168.1.2', $request->clientIp());

		unset($_SERVER['HTTP_X_FORWARDED_FOR']);
		$this->assertEquals('192.168.1.2', $request->clientIp());

		unset($_SERVER['HTTP_CLIENT_IP']);
		$this->assertEquals('192.168.1.3', $request->clientIp());

		$_SERVER['HTTP_CLIENTADDRESS'] = '10.0.1.2, 10.0.1.1';
		$this->assertEquals('10.0.1.2', $request->clientIp());
	}

/**
 * test the referer function.
 *
 * @return void
 */
	public function testReferer() {
		$request = new Request();
		$request->webroot = '/';

		$_SERVER['HTTP_REFERER'] = 'http://cakephp.org';
		$result = $request->referer();
		$this->assertSame($result, 'http://cakephp.org');

		$_SERVER['HTTP_REFERER'] = '';
		$result = $request->referer();
		$this->assertSame($result, '/');

		$_SERVER['HTTP_REFERER'] = FULL_BASE_URL . '/some/path';
		$result = $request->referer(true);
		$this->assertSame($result, '/some/path');

		$_SERVER['HTTP_REFERER'] = FULL_BASE_URL . '/some/path';
		$result = $request->referer(false);
		$this->assertSame($result, FULL_BASE_URL . '/some/path');

		$_SERVER['HTTP_REFERER'] = FULL_BASE_URL . '/some/path';
		$result = $request->referer(true);
		$this->assertSame($result, '/some/path');

		$_SERVER['HTTP_REFERER'] = FULL_BASE_URL . '/recipes/add';
		$result = $request->referer(true);
		$this->assertSame($result, '/recipes/add');

		$_SERVER['HTTP_X_FORWARDED_HOST'] = 'cakephp.org';
		$result = $request->referer();
		$this->assertSame(FULL_BASE_URL . '/recipes/add', $result);

		$request->trustProxy = true;
		$result = $request->referer();
		$this->assertSame('cakephp.org', $result);
	}

/**
 * test the simple uses of is()
 *
 * @return void
 */
	public function testIsHttpMethods() {
		$request = new Request();

		$this->assertFalse($request->is('undefined-behavior'));

		$_SERVER['REQUEST_METHOD'] = 'GET';
		$this->assertTrue($request->is('get'));

		$_SERVER['REQUEST_METHOD'] = 'POST';
		$this->assertTrue($request->is('POST'));

		$_SERVER['REQUEST_METHOD'] = 'PUT';
		$this->assertTrue($request->is('put'));
		$this->assertFalse($request->is('get'));

		$_SERVER['REQUEST_METHOD'] = 'DELETE';
		$this->assertTrue($request->is('delete'));
		$this->assertTrue($request->isDelete());

		$_SERVER['REQUEST_METHOD'] = 'delete';
		$this->assertFalse($request->is('delete'));
	}

/**
 * test the method() method.
 *
 * @return void
 */
	public function testMethod() {
		$_SERVER['REQUEST_METHOD'] = 'delete';
		$request = new Request();

		$this->assertEquals('delete', $request->method());
	}

/**
 * test host retrieval.
 *
 * @return void
 */
	public function testHost() {
		$_SERVER['HTTP_HOST'] = 'localhost';
		$request = new Request();

		$this->assertEquals('localhost', $request->host());
	}

/**
 * test port retrieval.
 *
 * @return void
 */
	public function testPort() {
		$_SERVER['SERVER_PORT'] = '80';
		$request = new Request();

		$this->assertEquals('80', $request->port());

		$_SERVER['SERVER_PORT'] = '443';
		$_SERVER['HTTP_X_FORWARDED_PORT'] = 80;
		$this->assertEquals('443', $request->port());

		$request->trustProxy = true;
		$this->assertEquals('80', $request->port());
	}

/**
 * test domain retrieval.
 *
 * @return void
 */
	public function testDomain() {
		$_SERVER['HTTP_HOST'] = 'something.example.com';
		$request = new Request();

		$this->assertEquals('example.com', $request->domain());

		$_SERVER['HTTP_HOST'] = 'something.example.co.uk';
		$this->assertEquals('example.co.uk', $request->domain(2));
	}

/**
 * Test scheme() method.
 *
 * @return void
 */
	public function testScheme() {
		$_SERVER['HTTPS'] = 'on';
		$request = new Request();
		$this->assertEquals('https', $request->scheme());

		unset($_SERVER['HTTPS']);
		$this->assertEquals('http', $request->scheme());

		$_SERVER['HTTP_X_FORWARDED_PROTO'] = 'https';
		$request->trustProxy = true;
		$this->assertEquals('https', $request->scheme());
	}

/**
 * test getting subdomains for a host.
 *
 * @return void
 */
	public function testSubdomain() {
		$_SERVER['HTTP_HOST'] = 'something.example.com';
		$request = new Request();

		$this->assertEquals(array('something'), $request->subdomains());

		$_SERVER['HTTP_HOST'] = 'www.something.example.com';
		$this->assertEquals(array('www', 'something'), $request->subdomains());

		$_SERVER['HTTP_HOST'] = 'www.something.example.co.uk';
		$this->assertEquals(array('www', 'something'), $request->subdomains(2));

		$_SERVER['HTTP_HOST'] = 'example.co.uk';
		$this->assertEquals(array(), $request->subdomains(2));
	}

/**
 * test ajax, flash and friends
 *
 * @return void
 */
	public function testisAjaxFlashAndFriends() {
		$request = new Request();

		$_SERVER['HTTP_USER_AGENT'] = 'Shockwave Flash';
		$this->assertTrue($request->is('flash'));

		$_SERVER['HTTP_USER_AGENT'] = 'Adobe Flash';
		$this->assertTrue($request->is('flash'));

		$_SERVER['HTTP_X_REQUESTED_WITH'] = 'XMLHttpRequest';
		$this->assertTrue($request->is('ajax'));

		$_SERVER['HTTP_X_REQUESTED_WITH'] = 'XMLHTTPREQUEST';
		$this->assertFalse($request->is('ajax'));
		$this->assertFalse($request->isAjax());

		$_SERVER['HTTP_USER_AGENT'] = 'Android 2.0';
		$this->assertTrue($request->is('mobile'));
		$this->assertTrue($request->isMobile());

		$_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (Windows NT 5.1; rv:2.0b6pre) Gecko/20100902 Firefox/4.0b6pre Fennec/2.0b1pre';
		$this->assertTrue($request->is('mobile'));
		$this->assertTrue($request->isMobile());

		$_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (compatible; MSIE 9.0; Windows Phone OS 7.5; Trident/5.0; IEMobile/9.0; SAMSUNG; OMNIA7)';
		$this->assertTrue($request->is('mobile'));
		$this->assertTrue($request->isMobile());
	}

/**
 * test __call expcetions
 *
 * @expectedException Cake\Error\Exception
 * @return void
 */
	public function testMagicCallExceptionOnUnknownMethod() {
		$request = new Request();
		$request->IamABanana();
	}

/**
 * test is(ssl)
 *
 * @return void
 */
	public function testIsSsl() {
		$request = new Request();

		$_SERVER['HTTPS'] = 1;
		$this->assertTrue($request->is('ssl'));

		$_SERVER['HTTPS'] = 'on';
		$this->assertTrue($request->is('ssl'));

		$_SERVER['HTTPS'] = '1';
		$this->assertTrue($request->is('ssl'));

		$_SERVER['HTTPS'] = 'I am not empty';
		$this->assertTrue($request->is('ssl'));

		$_SERVER['HTTPS'] = 1;
		$this->assertTrue($request->is('ssl'));

		$_SERVER['HTTPS'] = 'off';
		$this->assertFalse($request->is('ssl'));

		$_SERVER['HTTPS'] = false;
		$this->assertFalse($request->is('ssl'));

		$_SERVER['HTTPS'] = '';
		$this->assertFalse($request->is('ssl'));
	}

/**
 * test getting request params with object properties.
 *
 * @return void
 */
	public function testMagicget() {
		$request = new Request();
		$request->params = array('controller' => 'posts', 'action' => 'view', 'plugin' => 'blogs');

		$this->assertEquals('posts', $request->controller);
		$this->assertEquals('view', $request->action);
		$this->assertEquals('blogs', $request->plugin);
		$this->assertSame($request->banana, null);
	}

/**
 * Test isset()/empty() with overloaded properties.
 *
 * @return void
 */
	public function testMagicisset() {
		$request = new Request();
		$request->params = array(
			'controller' => 'posts',
			'action' => 'view',
			'plugin' => 'blogs',
		);

		$this->assertTrue(isset($request->controller));
		$this->assertFalse(isset($request->notthere));
		$this->assertFalse(empty($request->controller));
	}

/**
 * test the array access implementation
 *
 * @return void
 */
	public function testArrayAccess() {
		$request = new Request();
		$request->params = array('controller' => 'posts', 'action' => 'view', 'plugin' => 'blogs');

		$this->assertEquals('posts', $request['controller']);

		$request['slug'] = 'speedy-slug';
		$this->assertEquals('speedy-slug', $request->slug);
		$this->assertEquals('speedy-slug', $request['slug']);

		$this->assertTrue(isset($request['action']));
		$this->assertFalse(isset($request['wrong-param']));

		$this->assertTrue(isset($request['plugin']));
		unset($request['plugin']);
		$this->assertFalse(isset($request['plugin']));
		$this->assertNull($request['plugin']);
		$this->assertNull($request->plugin);

		$request = new Request(array('url' => 'some/path?one=something&two=else'));
		$this->assertTrue(isset($request['url']['one']));

		$request->data = array('Post' => array('title' => 'something'));
		$this->assertEquals('something', $request['data']['Post']['title']);
	}

/**
 * test adding detectors and having them work.
 *
 * @return void
 */
	public function testAddDetector() {
		$request = new Request();
		$request->addDetector('compare', array('env' => 'TEST_VAR', 'value' => 'something'));

		$_SERVER['TEST_VAR'] = 'something';
		$this->assertTrue($request->is('compare'), 'Value match failed.');

		$_SERVER['TEST_VAR'] = 'wrong';
		$this->assertFalse($request->is('compare'), 'Value mis-match failed.');

		$request->addDetector('compareCamelCase', array('env' => 'TEST_VAR', 'value' => 'foo'));

		$_SERVER['TEST_VAR'] = 'foo';
		$this->assertTrue($request->is('compareCamelCase'), 'Value match failed.');
		$this->assertTrue($request->is('comparecamelcase'), 'detectors should be case insensitive');
		$this->assertTrue($request->is('COMPARECAMELCASE'), 'detectors should be case insensitive');

		$_SERVER['TEST_VAR'] = 'not foo';
		$this->assertFalse($request->is('compareCamelCase'), 'Value match failed.');
		$this->assertFalse($request->is('comparecamelcase'), 'detectors should be case insensitive');
		$this->assertFalse($request->is('COMPARECAMELCASE'), 'detectors should be case insensitive');

		$request->addDetector('banana', array('env' => 'TEST_VAR', 'pattern' => '/^ban.*$/'));
		$_SERVER['TEST_VAR'] = 'banana';
		$this->assertTrue($request->isBanana());

		$_SERVER['TEST_VAR'] = 'wrong value';
		$this->assertFalse($request->isBanana());

		$request->addDetector('mobile', array('options' => array('Imagination')));
		$_SERVER['HTTP_USER_AGENT'] = 'Imagination land';
		$this->assertTrue($request->isMobile());

		$_SERVER['HTTP_USER_AGENT'] = 'iPhone 3.0';
		$this->assertTrue($request->isMobile());

		$request->addDetector('callme', array('env' => 'TEST_VAR', 'callback' => array($this, 'detectCallback')));

		$request->addDetector('index', array('param' => 'action', 'value' => 'index'));
		$request->params['action'] = 'index';
		$this->assertTrue($request->isIndex());

		$request->params['action'] = 'add';
		$this->assertFalse($request->isIndex());

		$request->return = true;
		$this->assertTrue($request->isCallMe());

		$request->return = false;
		$this->assertFalse($request->isCallMe());
	}

/**
 * helper function for testing callbacks.
 *
 * @return void
 */
	public function detectCallback($request) {
		return (bool)$request->return;
	}

/**
 * test getting headers
 *
 * @return void
 */
	public function testHeader() {
		$_SERVER['HTTP_HOST'] = 'localhost';
		$_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (Macintosh; U; Intel Mac OS X 10_6_4; en-ca) AppleWebKit/534.8+ (KHTML, like Gecko) Version/5.0 Safari/533.16';
		$request = new Request();

		$this->assertEquals($_SERVER['HTTP_HOST'], $request->header('host'));
		$this->assertEquals($_SERVER['HTTP_USER_AGENT'], $request->header('User-Agent'));
	}

/**
 * test accepts() with and without parameters
 *
 * @return void
 */
	public function testAccepts() {
		$_SERVER['HTTP_ACCEPT'] = 'text/xml,application/xml;q=0.9,application/xhtml+xml,text/html,text/plain,image/png';
		$request = new Request();

		$result = $request->accepts();
		$expected = array(
			'text/xml', 'application/xhtml+xml', 'text/html', 'text/plain', 'image/png', 'application/xml'
		);
		$this->assertEquals($expected, $result, 'Content types differ.');

		$result = $request->accepts('text/html');
		$this->assertTrue($result);

		$result = $request->accepts('image/gif');
		$this->assertFalse($result);
	}

/**
 * Test that accept header types are trimmed for comparisons.
 *
 * @return void
 */
	public function testAcceptWithWhitespace() {
		$_SERVER['HTTP_ACCEPT'] = 'text/xml  ,  text/html ,  text/plain,image/png';
		$request = new Request();
		$result = $request->accepts();
		$expected = array(
			'text/xml', 'text/html', 'text/plain', 'image/png'
		);
		$this->assertEquals($expected, $result, 'Content types differ.');

		$this->assertTrue($request->accepts('text/html'));
	}

/**
 * Content types from accepts() should respect the client's q preference values.
 *
 * @return void
 */
	public function testAcceptWithQvalueSorting() {
		$_SERVER['HTTP_ACCEPT'] = 'text/html;q=0.8,application/json;q=0.7,application/xml;q=1.0';
		$request = new Request();
		$result = $request->accepts();
		$expected = array('application/xml', 'text/html', 'application/json');
		$this->assertEquals($expected, $result);
	}

/**
 * Test the raw parsing of accept headers into the q value formatting.
 *
 * @return void
 */
	public function testParseAcceptWithQValue() {
		$_SERVER['HTTP_ACCEPT'] = 'text/html;q=0.8,application/json;q=0.7,application/xml;q=1.0,image/png';
		$request = new Request();
		$result = $request->parseAccept();
		$expected = array(
			'1.0' => array('application/xml', 'image/png'),
			'0.8' => array('text/html'),
			'0.7' => array('application/json'),
		);
		$this->assertEquals($expected, $result);
	}

/**
 * Test parsing accept with a confusing accept value.
 *
 * @return void
 */
	public function testParseAcceptNoQValues() {
		$_SERVER['HTTP_ACCEPT'] = 'application/json, text/plain, */*';

		$request = new Request();
		$result = $request->parseAccept();
		$expected = array(
			'1.0' => array('application/json', 'text/plain', '*/*'),
		);
		$this->assertEquals($expected, $result);
	}

/**
 * testBaseUrlAndWebrootWithModRewrite method
 *
 * @return void
 */
	public function testBaseUrlAndWebrootWithModRewrite() {
		Configure::write('App.baseUrl', false);

		$_SERVER['DOCUMENT_ROOT'] = '/cake/repo/branches';
		$_SERVER['PHP_SELF'] = '/1.2.x.x/App/webroot/index.php';
		$_SERVER['PATH_INFO'] = '/posts/view/1';

		$request = Request::createFromGlobals();
		$this->assertEquals('/1.2.x.x', $request->base);
		$this->assertEquals('/1.2.x.x/', $request->webroot);
		$this->assertEquals('posts/view/1', $request->url);

		$_SERVER['DOCUMENT_ROOT'] = '/cake/repo/branches/1.2.x.x/App/webroot';
		$_SERVER['PHP_SELF'] = '/index.php';
		$_SERVER['PATH_INFO'] = '/posts/add';
		$request = Request::createFromGlobals();

		$this->assertEquals('', $request->base);
		$this->assertEquals('/', $request->webroot);
		$this->assertEquals('posts/add', $request->url);

		$_SERVER['DOCUMENT_ROOT'] = '/cake/repo/branches/1.2.x.x/test/';
		$_SERVER['PHP_SELF'] = '/webroot/index.php';
		$request = Request::createFromGlobals();

		$this->assertEquals('', $request->base);
		$this->assertEquals('/', $request->webroot);

		$_SERVER['DOCUMENT_ROOT'] = '/some/apps/where';
		$_SERVER['PHP_SELF'] = '/App/webroot/index.php';
		$request = Request::createFromGlobals();

		$this->assertEquals('', $request->base);
		$this->assertEquals('/', $request->webroot);

		Configure::write('App.dir', 'auth');

		$_SERVER['DOCUMENT_ROOT'] = '/cake/repo/branches';
		$_SERVER['PHP_SELF'] = '/demos/auth/webroot/index.php';

		$request = Request::createFromGlobals();

		$this->assertEquals('/demos/auth', $request->base);
		$this->assertEquals('/demos/auth/', $request->webroot);

		Configure::write('App.dir', 'code');

		$_SERVER['DOCUMENT_ROOT'] = '/Library/WebServer/Documents';
		$_SERVER['PHP_SELF'] = '/clients/PewterReport/code/webroot/index.php';
		$request = Request::createFromGlobals();

		$this->assertEquals('/clients/PewterReport/code', $request->base);
		$this->assertEquals('/clients/PewterReport/code/', $request->webroot);
	}

/**
 * testBaseUrlwithModRewriteAlias method
 *
 * @return void
 */
	public function testBaseUrlwithModRewriteAlias() {
		$_SERVER['DOCUMENT_ROOT'] = '/home/aplusnur/public_html';
		$_SERVER['PHP_SELF'] = '/control/index.php';

		Configure::write('App.base', '/control');

		$request = Request::createFromGlobals();

		$this->assertEquals('/control', $request->base);
		$this->assertEquals('/control/', $request->webroot);

		Configure::write('App.base', false);
		Configure::write('App.dir', 'affiliate');
		Configure::write('App.webroot', 'newaffiliate');

		$_SERVER['DOCUMENT_ROOT'] = '/var/www/abtravaff/html';
		$_SERVER['PHP_SELF'] = '/newaffiliate/index.php';
		$request = Request::createFromGlobals();

		$this->assertEquals('/newaffiliate', $request->base);
		$this->assertEquals('/newaffiliate/', $request->webroot);
	}

/**
 * test base, webroot, and url parsing when there is no url rewriting
 *
 * @return void
 */
	public function testBaseUrlWithNoModRewrite() {
		$_SERVER['DOCUMENT_ROOT'] = '/Users/markstory/Sites';
		$_SERVER['SCRIPT_FILENAME'] = '/Users/markstory/Sites/cake/index.php';
		$_SERVER['PHP_SELF'] = '/cake/index.php/posts/index';
		$_SERVER['REQUEST_URI'] = '/cake/index.php/posts/index';

		Configure::write('App', array(
			'dir' => APP_DIR,
			'webroot' => WEBROOT_DIR,
			'base' => false,
			'baseUrl' => '/cake/index.php'
		));

		$request = Request::createFromGlobals();
		$this->assertEquals('/cake/index.php', $request->base);
		$this->assertEquals('/cake/App/webroot/', $request->webroot);
		$this->assertEquals('posts/index', $request->url);
	}

/**
 * testBaseUrlAndWebrootWithBaseUrl method
 *
 * @return void
 */
	public function testBaseUrlAndWebrootWithBaseUrl() {
		Configure::write('App.dir', 'App');
		Configure::write('App.baseUrl', '/App/webroot/index.php');

		$request = Request::createFromGlobals();
		$this->assertEquals('/App/webroot/index.php', $request->base);
		$this->assertEquals('/App/webroot/', $request->webroot);

		Configure::write('App.baseUrl', '/App/webroot/test.php');
		$request = Request::createFromGlobals();
		$this->assertEquals('/App/webroot/test.php', $request->base);
		$this->assertEquals('/App/webroot/', $request->webroot);

		Configure::write('App.baseUrl', '/App/index.php');
		$request = Request::createFromGlobals();
		$this->assertEquals('/App/index.php', $request->base);
		$this->assertEquals('/App/webroot/', $request->webroot);

		Configure::write('App.baseUrl', '/CakeBB/App/webroot/index.php');
		$request = Request::createFromGlobals();
		$this->assertEquals('/CakeBB/App/webroot/index.php', $request->base);
		$this->assertEquals('/CakeBB/App/webroot/', $request->webroot);

		Configure::write('App.baseUrl', '/CakeBB/App/index.php');
		$request = Request::createFromGlobals();

		$this->assertEquals('/CakeBB/App/index.php', $request->base);
		$this->assertEquals('/CakeBB/App/webroot/', $request->webroot);

		Configure::write('App.baseUrl', '/CakeBB/index.php');
		$request = Request::createFromGlobals();

		$this->assertEquals('/CakeBB/index.php', $request->base);
		$this->assertEquals('/CakeBB/App/webroot/', $request->webroot);

		Configure::write('App.baseUrl', '/dbhauser/index.php');
		$_SERVER['DOCUMENT_ROOT'] = '/kunden/homepages/4/d181710652/htdocs/joomla';
		$_SERVER['SCRIPT_FILENAME'] = '/kunden/homepages/4/d181710652/htdocs/joomla/dbhauser/index.php';
		$request = Request::createFromGlobals();

		$this->assertEquals('/dbhauser/index.php', $request->base);
		$this->assertEquals('/dbhauser/App/webroot/', $request->webroot);
	}

/**
 * test baseUrl with no rewrite and using the top level index.php.
 *
 * @return void
 */
	public function testBaseUrlNoRewriteTopLevelIndex() {
		Configure::write('App.baseUrl', '/index.php');
		$_SERVER['DOCUMENT_ROOT'] = '/Users/markstory/Sites/cake_dev';
		$_SERVER['SCRIPT_FILENAME'] = '/Users/markstory/Sites/cake_dev/index.php';

		$request = Request::createFromGlobals();
		$this->assertEquals('/index.php', $request->base);
		$this->assertEquals('/App/webroot/', $request->webroot);
	}

/**
 * Check that a sub-directory containing app|webroot doesn't get mishandled when re-writing is off.
 *
 * @return void
 */
	public function testBaseUrlWithAppAndWebrootInDirname() {
		Configure::write('App.baseUrl', '/approval/index.php');
		$_SERVER['DOCUMENT_ROOT'] = '/Users/markstory/Sites/';
		$_SERVER['SCRIPT_FILENAME'] = '/Users/markstory/Sites/approval/index.php';

		$request = Request::createFromGlobals();
		$this->assertEquals('/approval/index.php', $request->base);
		$this->assertEquals('/approval/App/webroot/', $request->webroot);

		Configure::write('App.baseUrl', '/webrootable/index.php');
		$_SERVER['DOCUMENT_ROOT'] = '/Users/markstory/Sites/';
		$_SERVER['SCRIPT_FILENAME'] = '/Users/markstory/Sites/webrootable/index.php';

		$request = Request::createFromGlobals();
		$this->assertEquals('/webrootable/index.php', $request->base);
		$this->assertEquals('/webrootable/App/webroot/', $request->webroot);
	}

/**
 * test baseUrl with no rewrite, and using the app/webroot/index.php file as is normal with virtual hosts.
 *
 * @return void
 */
	public function testBaseUrlNoRewriteWebrootIndex() {
		Configure::write('App.baseUrl', '/index.php');
		$_SERVER['DOCUMENT_ROOT'] = '/Users/markstory/Sites/cake_dev/App/webroot';
		$_SERVER['SCRIPT_FILENAME'] = '/Users/markstory/Sites/cake_dev/App/webroot/index.php';

		$request = Request::createFromGlobals();
		$this->assertEquals('/index.php', $request->base);
		$this->assertEquals('/', $request->webroot);
	}

/**
 * Test that a request with a . in the main GET parameter is filtered out.
 * PHP changes GET parameter keys containing dots to _.
 *
 * @return void
 */
	public function testGetParamsWithDot() {
		$_GET = array();
		$_GET['/posts/index/add_add'] = '';
		$_SERVER['PHP_SELF'] = '/cake_dev/App/webroot/index.php';
		$_SERVER['REQUEST_URI'] = '/cake_dev/posts/index/add.add';

		$request = Request::createFromGlobals();
		$this->assertEquals(array(), $request->query);
	}

/**
 * Test that a request with urlencoded bits in the main GET parameter are filtered out.
 *
 * @return void
 */
	public function testGetParamWithUrlencodedElement() {
		$_GET = array();
		$_GET['/posts/add/∂∂'] = '';
		$_SERVER['PHP_SELF'] = '/cake_dev/App/webroot/index.php';
		$_SERVER['REQUEST_URI'] = '/cake_dev/posts/add/%E2%88%82%E2%88%82';

		$request = new Request();
		$this->assertEquals(array(), $request->query);
	}

/**
 * generator for environment configurations
 *
 * @return void
 */
	public static function environmentGenerator() {
		return array(
			array(
				'IIS - No rewrite base path',
				array(
					'App' => array(
						'base' => false,
						'baseUrl' => '/index.php',
						'dir' => 'App',
						'webroot' => 'webroot'
					),
					'SERVER' => array(
						'SCRIPT_NAME' => '/index.php',
						'PATH_TRANSLATED' => 'C:\\Inetpub\\wwwroot',
						'QUERY_STRING' => '',
						'REQUEST_URI' => '/index.php',
						'URL' => '/index.php',
						'SCRIPT_FILENAME' => 'C:\\Inetpub\\wwwroot\\index.php',
						'ORIG_PATH_INFO' => '/index.php',
						'PATH_INFO' => '',
						'ORIG_PATH_TRANSLATED' => 'C:\\Inetpub\\wwwroot\\index.php',
						'DOCUMENT_ROOT' => 'C:\\Inetpub\\wwwroot',
						'PHP_SELF' => '/index.php',
					),
				),
				array(
					'base' => '/index.php',
					'webroot' => '/App/webroot/',
					'url' => ''
				),
			),
			array(
				'IIS - No rewrite with path, no PHP_SELF',
				array(
					'App' => array(
						'base' => false,
						'baseUrl' => '/index.php?',
						'dir' => 'App',
						'webroot' => 'webroot'
					),
					'SERVER' => array(
						'QUERY_STRING' => '/posts/add',
						'REQUEST_URI' => '/index.php?/posts/add',
						'PHP_SELF' => '',
						'URL' => '/index.php?/posts/add',
						'DOCUMENT_ROOT' => 'C:\\Inetpub\\wwwroot',
						'argv' => array('/posts/add'),
						'argc' => 1
					),
				),
				array(
					'url' => 'posts/add',
					'base' => '/index.php?',
					'webroot' => '/App/webroot/'
				)
			),
			array(
				'IIS - No rewrite sub dir 2',
				array(
					'App' => array(
						'base' => false,
						'baseUrl' => '/site/index.php',
						'dir' => 'App',
						'webroot' => 'webroot',
					),
					'SERVER' => array(
						'SCRIPT_NAME' => '/site/index.php',
						'PATH_TRANSLATED' => 'C:\\Inetpub\\wwwroot',
						'QUERY_STRING' => '',
						'REQUEST_URI' => '/site/index.php',
						'URL' => '/site/index.php',
						'SCRIPT_FILENAME' => 'C:\\Inetpub\\wwwroot\\site\\index.php',
						'DOCUMENT_ROOT' => 'C:\\Inetpub\\wwwroot',
						'PHP_SELF' => '/site/index.php',
						'argv' => array(),
						'argc' => 0
					),
				),
				array(
					'url' => '',
					'base' => '/site/index.php',
					'webroot' => '/site/App/webroot/'
				),
			),
			array(
				'IIS - No rewrite sub dir 2 with path',
				array(
					'App' => array(
						'base' => false,
						'baseUrl' => '/site/index.php',
						'dir' => 'App',
						'webroot' => 'webroot'
					),
					'GET' => array('/posts/add' => ''),
					'SERVER' => array(
						'SCRIPT_NAME' => '/site/index.php',
						'PATH_TRANSLATED' => 'C:\\Inetpub\\wwwroot',
						'QUERY_STRING' => '/posts/add',
						'REQUEST_URI' => '/site/index.php/posts/add',
						'URL' => '/site/index.php/posts/add',
						'ORIG_PATH_TRANSLATED' => 'C:\\Inetpub\\wwwroot\\site\\index.php',
						'DOCUMENT_ROOT' => 'C:\\Inetpub\\wwwroot',
						'PHP_SELF' => '/site/index.php/posts/add',
						'argv' => array('/posts/add'),
						'argc' => 1
					),
				),
				array(
					'url' => 'posts/add',
					'base' => '/site/index.php',
					'webroot' => '/site/App/webroot/'
				)
			),
			array(
				'Apache - No rewrite, document root set to webroot, requesting path',
				array(
					'App' => array(
						'base' => false,
						'baseUrl' => '/index.php',
						'dir' => 'App',
						'webroot' => 'webroot'
					),
					'SERVER' => array(
						'DOCUMENT_ROOT' => '/Library/WebServer/Documents/site/App/webroot',
						'SCRIPT_FILENAME' => '/Library/WebServer/Documents/site/App/webroot/index.php',
						'QUERY_STRING' => '',
						'REQUEST_URI' => '/index.php/posts/index',
						'SCRIPT_NAME' => '/index.php',
						'PATH_INFO' => '/posts/index',
						'PHP_SELF' => '/index.php/posts/index',
					),
				),
				array(
					'url' => 'posts/index',
					'base' => '/index.php',
					'webroot' => '/'
				),
			),
			array(
				'Apache - No rewrite, document root set to webroot, requesting root',
				array(
					'App' => array(
						'base' => false,
						'baseUrl' => '/index.php',
						'dir' => 'App',
						'webroot' => 'webroot'
					),
					'SERVER' => array(
						'DOCUMENT_ROOT' => '/Library/WebServer/Documents/site/App/webroot',
						'SCRIPT_FILENAME' => '/Library/WebServer/Documents/site/App/webroot/index.php',
						'QUERY_STRING' => '',
						'REQUEST_URI' => '/index.php',
						'SCRIPT_NAME' => '/index.php',
						'PATH_INFO' => '',
						'PHP_SELF' => '/index.php',
					),
				),
				array(
					'url' => '',
					'base' => '/index.php',
					'webroot' => '/'
				),
			),
			array(
				'Apache - No rewrite, document root set above top level cake dir, requesting path',
				array(
					'App' => array(
						'base' => false,
						'baseUrl' => '/site/index.php',
						'dir' => 'App',
						'webroot' => 'webroot'
					),
					'SERVER' => array(
						'SERVER_NAME' => 'localhost',
						'DOCUMENT_ROOT' => '/Library/WebServer/Documents',
						'SCRIPT_FILENAME' => '/Library/WebServer/Documents/site/index.php',
						'REQUEST_URI' => '/site/index.php/posts/index',
						'SCRIPT_NAME' => '/site/index.php',
						'PATH_INFO' => '/posts/index',
						'PHP_SELF' => '/site/index.php/posts/index',
					),
				),
				array(
					'url' => 'posts/index',
					'base' => '/site/index.php',
					'webroot' => '/site/App/webroot/',
				),
			),
			array(
				'Apache - No rewrite, document root set above top level cake dir, request root, no PATH_INFO',
				array(
					'App' => array(
						'base' => false,
						'baseUrl' => '/site/index.php',
						'dir' => 'App',
						'webroot' => 'webroot'
					),
					'SERVER' => array(
						'SERVER_NAME' => 'localhost',
						'DOCUMENT_ROOT' => '/Library/WebServer/Documents',
						'SCRIPT_FILENAME' => '/Library/WebServer/Documents/site/index.php',
						'REQUEST_URI' => '/site/index.php/',
						'SCRIPT_NAME' => '/site/index.php',
						'PHP_SELF' => '/site/index.php/',
					),
				),
				array(
					'url' => '',
					'base' => '/site/index.php',
					'webroot' => '/site/App/webroot/',
				),
			),
			array(
				'Apache - No rewrite, document root set above top level cake dir, request path, with GET',
				array(
					'App' => array(
						'base' => false,
						'baseUrl' => '/site/index.php',
						'dir' => 'App',
						'webroot' => 'webroot'
					),
					'GET' => array('a' => 'b', 'c' => 'd'),
					'SERVER' => array(
						'SERVER_NAME' => 'localhost',
						'DOCUMENT_ROOT' => '/Library/WebServer/Documents',
						'SCRIPT_FILENAME' => '/Library/WebServer/Documents/site/index.php',
						'REQUEST_URI' => '/site/index.php/posts/index?a=b&c=d',
						'SCRIPT_NAME' => '/site/index.php',
						'PATH_INFO' => '/posts/index',
						'PHP_SELF' => '/site/index.php/posts/index',
						'QUERY_STRING' => 'a=b&c=d'
					),
				),
				array(
					'urlParams' => array('a' => 'b', 'c' => 'd'),
					'url' => 'posts/index',
					'base' => '/site/index.php',
					'webroot' => '/site/App/webroot/',
				),
			),
			array(
				'Apache - w/rewrite, document root set above top level cake dir, request root, no PATH_INFO',
				array(
					'App' => array(
						'base' => false,
						'baseUrl' => false,
						'dir' => 'App',
						'webroot' => 'webroot'
					),
					'SERVER' => array(
						'SERVER_NAME' => 'localhost',
						'DOCUMENT_ROOT' => '/Library/WebServer/Documents',
						'SCRIPT_FILENAME' => '/Library/WebServer/Documents/site/index.php',
						'REQUEST_URI' => '/site/',
						'SCRIPT_NAME' => '/site/App/webroot/index.php',
						'PHP_SELF' => '/site/App/webroot/index.php',
					),
				),
				array(
					'url' => '',
					'base' => '/site',
					'webroot' => '/site/',
				),
			),
			array(
				'Apache - w/rewrite, document root above top level cake dir, request root, no PATH_INFO/REQUEST_URI',
				array(
					'App' => array(
						'base' => false,
						'baseUrl' => false,
						'dir' => 'App',
						'webroot' => 'webroot'
					),
					'SERVER' => array(
						'SERVER_NAME' => 'localhost',
						'DOCUMENT_ROOT' => '/Library/WebServer/Documents',
						'SCRIPT_FILENAME' => '/Library/WebServer/Documents/site/index.php',
						'SCRIPT_NAME' => '/site/App/webroot/index.php',
						'PHP_SELF' => '/site/App/webroot/index.php',
						'PATH_INFO' => null,
						'REQUEST_URI' => null,
					),
				),
				array(
					'url' => '',
					'base' => '/site',
					'webroot' => '/site/',
				),
			),
			array(
				'Apache - w/rewrite, document root set to webroot, request root, no PATH_INFO/REQUEST_URI',
				array(
					'App' => array(
						'base' => false,
						'baseUrl' => false,
						'dir' => 'App',
						'webroot' => 'webroot'
					),
					'SERVER' => array(
						'SERVER_NAME' => 'localhost',
						'DOCUMENT_ROOT' => '/Library/WebServer/Documents/site/App/webroot',
						'SCRIPT_FILENAME' => '/Library/WebServer/Documents/site/App/webroot/index.php',
						'SCRIPT_NAME' => '/index.php',
						'PHP_SELF' => '/index.php',
						'PATH_INFO' => null,
						'REQUEST_URI' => null,
					),
				),
				array(
					'url' => '',
					'base' => '',
					'webroot' => '/',
				),
			),
			array(
				'Apache - w/rewrite, document root set above top level cake dir, request root, absolute REQUEST_URI',
				array(
					'App' => array(
						'base' => false,
						'baseUrl' => false,
						'dir' => 'App',
						'webroot' => 'webroot'
					),
					'SERVER' => array(
						'SERVER_NAME' => 'localhost',
						'DOCUMENT_ROOT' => '/Library/WebServer/Documents',
						'SCRIPT_FILENAME' => '/Library/WebServer/Documents/site/index.php',
						'REQUEST_URI' => FULL_BASE_URL . '/site/posts/index',
						'SCRIPT_NAME' => '/site/App/webroot/index.php',
						'PHP_SELF' => '/site/App/webroot/index.php',
					),
				),
				array(
					'url' => 'posts/index',
					'base' => '/site',
					'webroot' => '/site/',
				),
			),
			array(
				'Nginx - w/rewrite, document root set to webroot, request root, no PATH_INFO',
				array(
					'App' => array(
						'base' => false,
						'baseUrl' => false,
						'dir' => 'App',
						'webroot' => 'webroot'
					),
					'GET' => array('/posts/add' => ''),
					'SERVER' => array(
						'SERVER_NAME' => 'localhost',
						'DOCUMENT_ROOT' => '/Library/WebServer/Documents/site/App/webroot',
						'SCRIPT_FILENAME' => '/Library/WebServer/Documents/site/App/webroot/index.php',
						'SCRIPT_NAME' => '/index.php',
						'QUERY_STRING' => '/posts/add&',
						'PHP_SELF' => '/index.php',
						'PATH_INFO' => null,
						'REQUEST_URI' => '/posts/add',
					),
				),
				array(
					'url' => 'posts/add',
					'base' => '',
					'webroot' => '/',
					'urlParams' => array()
				),
			),
		);
	}

/**
 * testEnvironmentDetection method
 *
 * @dataProvider environmentGenerator
 * @return void
 */
	public function testEnvironmentDetection($name, $env, $expected) {
		$_GET = array();
		$this->_loadEnvironment($env);

		$request = Request::createFromGlobals();
		$this->assertEquals($expected['url'], $request->url, "url error");
		$this->assertEquals($expected['base'], $request->base, "base error");
		$this->assertEquals($expected['webroot'], $request->webroot, "webroot error");
		if (isset($expected['urlParams'])) {
			$this->assertEquals($expected['urlParams'], $request->query, "GET param mismatch");
		}
	}

/**
 * test the query() method
 *
 * @return void
 */
	public function testQuery() {
		$_GET = array();
		$_GET['foo'] = 'bar';

		$request = new Request();

		$result = $request->query('foo');
		$this->assertEquals('bar', $result);

		$result = $request->query('imaginary');
		$this->assertNull($result);
	}

/**
 * test the query() method with arrays passed via $_GET
 *
 * @return void
 */
	public function testQueryWithArray() {
		$_GET = array();
		$_GET['test'] = array('foo', 'bar');

		$request = new Request();

		$result = $request->query('test');
		$this->assertEquals(array('foo', 'bar'), $result);

		$result = $request->query('test.1');
		$this->assertEquals('bar', $result);

		$result = $request->query('test.2');
		$this->assertNull($result);
	}

/**
 * test the data() method reading
 *
 * @return void
 */
	public function testDataReading() {
		$post = array(
			'Model' => array(
				'field' => 'value'
			)
		);
		$request = new Request(compact('post'));
		$result = $request->data('Model');
		$this->assertEquals($post['Model'], $result);

		$result = $request->data('Model.imaginary');
		$this->assertNull($result);
	}

/**
 * test writing with data()
 *
 * @return void
 */
	public function testDataWriting() {
		$_POST['data'] = array(
			'Model' => array(
				'field' => 'value'
			)
		);
		$request = new Request();
		$result = $request->data('Model.new_value', 'new value');
		$this->assertSame($result, $request, 'Return was not $this');

		$this->assertEquals('new value', $request->data['Model']['new_value']);

		$request->data('Post.title', 'New post')->data('Comment.1.author', 'Mark');
		$this->assertEquals('New post', $request->data['Post']['title']);
		$this->assertEquals('Mark', $request->data['Comment']['1']['author']);
	}

/**
 * test writing falsey values.
 *
 * @return void
 */
	public function testDataWritingFalsey() {
		$request = new Request();

		$request->data('Post.null', null);
		$this->assertNull($request->data['Post']['null']);

		$request->data('Post.false', false);
		$this->assertFalse($request->data['Post']['false']);

		$request->data('Post.zero', 0);
		$this->assertSame(0, $request->data['Post']['zero']);

		$request->data('Post.empty', '');
		$this->assertSame('', $request->data['Post']['empty']);
	}

/**
 * test accept language
 *
 * @return void
 */
	public function testAcceptLanguage() {
		// Weird language
		$_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'inexistent,en-ca';
		$result = Request::acceptLanguage();
		$this->assertEquals(array('inexistent', 'en-ca'), $result, 'Languages do not match');

		// No qualifier
		$_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'es_mx,en_ca';
		$result = Request::acceptLanguage();
		$this->assertEquals(array('es-mx', 'en-ca'), $result, 'Languages do not match');

		// With qualifier
		$_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'en-US,en;q=0.8,pt-BR;q=0.6,pt;q=0.4';
		$result = Request::acceptLanguage();
		$this->assertEquals(array('en-us', 'en', 'pt-br', 'pt'), $result, 'Languages do not match');

		// With spaces
		$_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'da, en-gb;q=0.8, en;q=0.7';
		$result = Request::acceptLanguage();
		$this->assertEquals(array('da', 'en-gb', 'en'), $result, 'Languages do not match');

		// Checking if requested
		$_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'es_mx,en_ca';
		$result = Request::acceptLanguage();

		$result = Request::acceptLanguage('en-ca');
		$this->assertTrue($result);

		$result = Request::acceptLanguage('en-CA');
		$this->assertTrue($result);

		$result = Request::acceptLanguage('en-us');
		$this->assertFalse($result);

		$result = Request::acceptLanguage('en-US');
		$this->assertFalse($result);
	}

/**
 * test the here() method
 *
 * @return void
 */
	public function testHere() {
		Configure::write('App.base', '/base_path');
		$q = array('test' => 'value');
		$request = new Request(array(
			'query' => $q,
			'url' => '/posts/add/1/value',
			'base' => '/base_path'
		));

		$result = $request->here();
		$this->assertEquals('/base_path/posts/add/1/value?test=value', $result);

		$result = $request->here(false);
		$this->assertEquals('/posts/add/1/value?test=value', $result);

		$request = new Request(array(
			'url' => '/posts/base_path/1/value',
			'query' => array('test' => 'value'),
			'base' => '/base_path'
		));
		$result = $request->here();
		$this->assertEquals('/base_path/posts/base_path/1/value?test=value', $result);

		$result = $request->here(false);
		$this->assertEquals('/posts/base_path/1/value?test=value', $result);
	}

/**
 * Test the input() method.
 *
 * @return void
 */
	public function testInput() {
		$request = $this->getMock('Cake\Network\Request', array('_readInput'));
		$request->expects($this->once())->method('_readInput')
			->will($this->returnValue('I came from stdin'));

		$result = $request->input();
		$this->assertEquals('I came from stdin', $result);
	}

/**
 * Test input() decoding.
 *
 * @return void
 */
	public function testInputDecode() {
		$request = $this->getMock('Cake\Network\Request', array('_readInput'));
		$request->expects($this->once())->method('_readInput')
			->will($this->returnValue('{"name":"value"}'));

		$result = $request->input('json_decode');
		$this->assertEquals(array('name' => 'value'), (array)$result);
	}

/**
 * Test input() decoding with additional arguments.
 *
 * @return void
 */
	public function testInputDecodeExtraParams() {
		$xml = <<<XML
<?xml version="1.0" encoding="utf-8"?>
<post>
	<title id="title">Test</title>
</post>
XML;

		$request = $this->getMock('Cake\Network\Request', array('_readInput'));
		$request->expects($this->once())->method('_readInput')
			->will($this->returnValue($xml));

		$result = $request->input('Cake\Utility\Xml::build', array('return' => 'domdocument'));
		$this->assertInstanceOf('DOMDocument', $result);
		$this->assertEquals(
			'Test',
			$result->getElementsByTagName('title')->item(0)->childNodes->item(0)->wholeText
		);
	}

/**
 * Test is('requested') and isRequested()
 *
 * @return void
 */
	public function testIsRequested() {
		$request = new Request();
		$request->addParams(array(
			'controller' => 'posts',
			'action' => 'index',
			'plugin' => null,
			'requested' => 1
		));
		$this->assertTrue($request->is('requested'));
		$this->assertTrue($request->isRequested());

		$request = new Request();
		$request->addParams(array(
			'controller' => 'posts',
			'action' => 'index',
			'plugin' => null,
		));
		$this->assertFalse($request->is('requested'));
		$this->assertFalse($request->isRequested());
	}

/**
 * Test the cookie() method.
 *
 * @return void
 */
	public function testReadCookie() {
		$request = new Request(array(
			'cookies' => array(
				'testing' => 'A value in the cookie'
			)
		));
		$result = $request->cookie('testing');
		$this->assertEquals('A value in the cookie', $result);

		$result = $request->cookie('not there');
		$this->assertNull($result);
	}

/**
 * TestOnlyAllow
 *
 * @return void
 */
	public function testOnlyAllow() {
		$_SERVER['REQUEST_METHOD'] = 'PUT';
		$request = new Request('/posts/edit/1');

		$this->assertTrue($request->onlyAllow(array('put')));

		$_SERVER['REQUEST_METHOD'] = 'DELETE';
		$this->assertTrue($request->onlyAllow('post', 'delete'));
	}

/**
 * TestOnlyAllow throwing exception
 *
 * @return void
 */
	public function testOnlyAllowException() {
		$_SERVER['REQUEST_METHOD'] = 'PUT';
		$request = new Request('/posts/edit/1');

		try {
			$request->onlyAllow('POST', 'DELETE');
			$this->fail('An expected exception has not been raised.');
		} catch (Error\MethodNotAllowedException $e) {
			$this->assertEquals(array('Allow' => 'POST, DELETE'), $e->responseHeader());
		}

		$this->setExpectedException('Cake\Error\MethodNotAllowedException');
		$request->onlyAllow('POST');
	}

/**
 * loadEnvironment method
 *
 * @param array $env
 * @return void
 */
	protected function _loadEnvironment($env) {
		if (isset($env['App'])) {
			Configure::write('App', $env['App']);
		}

		if (isset($env['GET'])) {
			foreach ($env['GET'] as $key => $val) {
				$_GET[$key] = $val;
			}
		}

		if (isset($env['POST'])) {
			foreach ($env['POST'] as $key => $val) {
				$_POST[$key] = $val;
			}
		}

		if (isset($env['SERVER'])) {
			foreach ($env['SERVER'] as $key => $val) {
				$_SERVER[$key] = $val;
			}
		}
	}

}
