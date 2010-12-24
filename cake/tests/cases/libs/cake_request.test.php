<?php
/**
 * CakeRequest Test case file.
 *
 * PHP 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       cake.tests.cases.libs
 * @since         CakePHP(tm) v 2.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
App::import('Core', 'Dispatcher');
App::import('Core', 'CakeRequest');

class CakeRequestTestCase extends CakeTestCase {
/**
 * setup callback
 *
 * @return void
 */
	function setUp() {
		parent::setUp();
		$this->_server = $_SERVER;
		$this->_get = $_GET;
		$this->_post = $_POST;
		$this->_files = $_FILES;
	}

/**
 * tearDown-
 *
 * @return void
 */
	function tearDown() {
		parent::tearDown();
		$_SERVER = $this->_server;
		$_GET = $this->_get;
		$_POST = $this->_post;
		$_FILES = $this->_files;
	}

/**
 * test that the autoparse = false constructor works.
 *
 * @return void
 */
	function testNoAutoParseConstruction() {
		$_GET = array(
			'one' => 'param'
		);
		$request = new CakeRequest(null, false);
		$this->assertFalse(isset($request->query['one']));
	}

/**
 * test construction
 *
 * @return void
 */
	function testConstructionGetParsing() {
		$_GET = array(
			'one' => 'param',
			'two' => 'banana'
		);
		$request = new CakeRequest('some/path');
		$this->assertEqual($request->query, $_GET + array('url' => 'some/path'));
		
		$_GET = array(
			'one' => 'param',
			'two' => 'banana',
			'url' => 'some/path'
		);
		$request = new CakeRequest('some/path');
		$this->assertEqual($request->query, $_GET);
		$this->assertEqual($request->url, 'some/path');
	}

/**
 * Test that querystring args provided in the url string are parsed.
 *
 * @return void
 */
	function testQueryStringParsingFromInputUrl() {
		$_GET = array();
		$request = new CakeRequest('some/path?one=something&two=else');
		$expected = array('one' => 'something', 'two' => 'else', 'url' => 'some/path?one=something&two=else');
		$this->assertEqual($request->query, $expected);
	}

/**
 * test addParams() method
 *
 * @return void
 */
	function testAddParams() {
		$request = new CakeRequest('some/path');
		$request->params = array('controller' => 'posts', 'action' => 'view');
		$result = $request->addParams(array('plugin' => null, 'action' => 'index'));

		$this->assertIdentical($result, $request, 'Method did not return itself. %s');

		$this->assertEqual($request->controller, 'posts');
		$this->assertEqual($request->action, 'index');
		$this->assertEqual($request->plugin, null);
	}

/**
 * test splicing in paths.
 *
 * @return void
 */
	function testAddPaths() {
		$request = new CakeRequest('some/path');
		$request->webroot = '/some/path/going/here/';
		$result = $request->addPaths(array(
			'random' => '/something', 'webroot' => '/', 'here' => '/', 'base' => '/base_dir'
		));

		$this->assertIdentical($result, $request, 'Method did not return itself. %s');

		$this->assertEqual($request->webroot, '/');
		$this->assertEqual($request->base, '/base_dir');
		$this->assertEqual($request->here, '/');
		$this->assertFalse(isset($request->random));
	}


/**
 * test parsing POST data into the object.
 *
 * @return void
 */
	function testPostParsing() {
		$_POST = array('data' => array(
			'Article' => array('title')
		));
		$request = new CakeRequest('some/path');
		$this->assertEqual($request->data, $_POST['data']);

		$_POST = array('one' => 1, 'two' => 'three');
		$request = new CakeRequest('some/path');
		$this->assertEqual($request->params['form'], $_POST);
	}

/**
 * test parsing of FILES array
 *
 * @return void
 */
	function testFILESParsing() {
		$_FILES = array('data' => array('name' => array(
			'File' => array(
					array('data' => 'cake_mssql_patch.patch'),
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
		));

		$request = new CakeRequest('some/path');
		$expected = array(
			'File' => array(
				array('data' => array(
					'name' => 'cake_mssql_patch.patch',
					'type' => '',
					'tmp_name' => '/private/var/tmp/phpy05Ywj',
					'error' => 0,
					'size' => 6271,
				)),
				array(
					'data' => array(
					'name' => 'controller.diff',
					'type' => '',
					'tmp_name' => '/private/var/tmp/php7MBztY',
					'error' => 0,
					'size' => 350,
				)),
				array('data' => array(
					'name' => '',
					'type' => '',
					'tmp_name' => '',
					'error' => 4,
					'size' => 0,
				)),
				array('data' => array(
					'name' => '',
					'type' => '',
					'tmp_name' => '',
					'error' => 4,
					'size' => 0,
				)),
			),
			'Post' => array('attachment' => array(
				'name' => 'jquery-1.2.1.js',
				'type' => 'application/x-javascript',
				'tmp_name' => '/private/var/tmp/phpEwlrIo',
				'error' => 0,
				'size' => 80469,
			))
		);
		$this->assertEqual($request->data, $expected);

		$_FILES = array(
			'data' => array(
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
			)
		);

		$request = new CakeRequest('some/path');
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
		$this->assertEqual($request->data, $expected);


		$_FILES = array(
			'data' => array(
				'name' => array('birth_cert' => 'born on.txt'),
				'type' => array('birth_cert' => 'application/octet-stream'),
				'tmp_name' => array('birth_cert' => '/private/var/tmp/phpbsUWfH'),
				'error' => array('birth_cert' => 0),
				'size' => array('birth_cert' => 123)
			)
		);

		$request = new CakeRequest('some/path');
		$expected = array(
			'birth_cert' => array(
				'name' => 'born on.txt',
				'type' => 'application/octet-stream',
				'tmp_name' => '/private/var/tmp/phpbsUWfH',
				'error' => 0,
				'size' => 123
			)
		);
		$this->assertEqual($request->data, $expected);

		$_FILES = array(
			'something' => array(
				'name' => 'something.txt',
				'type' => 'text/plain',
				'tmp_name' => '/some/file',
				'error' => 0,
				'size' => 123
			)
		);
		$request = new CakeRequest('some/path');
		$this->assertEqual($request->params['form'], $_FILES);

	}

/**
 * test method overrides coming in from POST data.
 *
 * @return void
 */
	function testMethodOverrides() {
		$_POST = array('_method' => 'POST');
		$request = new CakeRequest('some/path');
		$this->assertEqual(env('REQUEST_METHOD'), 'POST');

		$_POST = array('_method' => 'DELETE');
		$request = new CakeRequest('some/path');
		$this->assertEqual(env('REQUEST_METHOD'), 'DELETE');

		$_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE'] = 'PUT';
		$request = new CakeRequest('some/path');
		$this->assertEqual(env('REQUEST_METHOD'), 'PUT');
	}

/**
 * test the clientIp method.
 *
 * @return void
 */
	function testclientIp() {
		$_SERVER['HTTP_X_FORWARDED_FOR'] = '192.168.1.5, 10.0.1.1, proxy.com';
		$_SERVER['HTTP_CLIENT_IP'] = '192.168.1.2';
		$_SERVER['REMOTE_ADDR'] = '192.168.1.3';
		$request = new CakeRequest('some/path');
		$this->assertEqual($request->clientIp(false), '192.168.1.5');
		$this->assertEqual($request->clientIp(), '192.168.1.2');

		unset($_SERVER['HTTP_X_FORWARDED_FOR']);
		$this->assertEqual($request->clientIp(), '192.168.1.2');

		unset($_SERVER['HTTP_CLIENT_IP']);
		$this->assertEqual($request->clientIp(), '192.168.1.3');

		$_SERVER['HTTP_CLIENTADDRESS'] = '10.0.1.2, 10.0.1.1';
		$this->assertEqual($request->clientIp(), '10.0.1.2');
	}

/**
 * test the referer function.
 *
 * @return void
 */
	function testReferer() {
		$request = new CakeRequest('some/path');
		$request->webroot = '/';

		$_SERVER['HTTP_REFERER'] = 'http://cakephp.org';
		$result = $request->referer();
		$this->assertIdentical($result, 'http://cakephp.org');

		$_SERVER['HTTP_REFERER'] = '';
		$result = $request->referer();
		$this->assertIdentical($result, '/');

		$_SERVER['HTTP_REFERER'] = FULL_BASE_URL . '/some/path';
		$result = $request->referer(true);
		$this->assertIdentical($result, '/some/path');

		$_SERVER['HTTP_REFERER'] = FULL_BASE_URL . '/some/path';
		$result = $request->referer(false);
		$this->assertIdentical($result, FULL_BASE_URL . '/some/path');

		$_SERVER['HTTP_REFERER'] = FULL_BASE_URL . '/some/path';
		$result = $request->referer(true);
		$this->assertIdentical($result, '/some/path');

		$_SERVER['HTTP_REFERER'] = FULL_BASE_URL . '/recipes/add';
		$result = $request->referer(true);
		$this->assertIdentical($result, '/recipes/add');

		$_SERVER['HTTP_X_FORWARDED_HOST'] = 'cakephp.org';
		$result = $request->referer();
		$this->assertIdentical($result, 'cakephp.org');
	}

/**
 * test the simple uses of is()
 *
 * @return void
 */
	function testIsHttpMethods() {
		$request = new CakeRequest('some/path');

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
	function testMethod() {
		$_SERVER['REQUEST_METHOD'] = 'delete';
		$request = new CakeRequest('some/path');

		$this->assertEquals('delete', $request->method());
	}

/**
 * test host retrieval.
 *
 * @return void
 */
	function testHost() {
		$_SERVER['HTTP_HOST'] = 'localhost';
		$request = new CakeRequest('some/path');
		
		$this->assertEquals('localhost', $request->host());
	}

/**
 * test domain retrieval.
 *
 * @return void
 */
	function testDomain() {
		$_SERVER['HTTP_HOST'] = 'something.example.com';
		$request = new CakeRequest('some/path');

		$this->assertEquals('example.com', $request->domain());

		$_SERVER['HTTP_HOST'] = 'something.example.co.uk';
		$this->assertEquals('example.co.uk', $request->domain(2));
	}

/**
 * test getting subdomains for a host.
 *
 * @return void
 */
	function testSubdomain() {
		$_SERVER['HTTP_HOST'] = 'something.example.com';
		$request = new CakeRequest('some/path');

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
	function testisAjaxFlashAndFriends() {
		$request = new CakeRequest('some/path');

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
	}

/**
 * test __call expcetions
 *
 * @expectedException CakeException
 * @return void
 */
	function test__callExceptionOnUnknownMethod() {
		$request = new CakeRequest('some/path');
		$request->IamABanana();
	}

/**
 * test is(ssl)
 *
 * @return void
 */
	function testIsSsl() {
		$request = new CakeRequest('some/path');

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
	function test__get() {
		$request = new CakeRequest('some/path');
		$request->params = array('controller' => 'posts', 'action' => 'view', 'plugin' => 'blogs');

		$this->assertEqual($request->controller, 'posts');
		$this->assertEqual($request->action, 'view');
		$this->assertEqual($request->plugin, 'blogs');
		$this->assertIdentical($request->banana, null);
	}

/**
 * test the array access implementation
 *
 * @return void
 */
	function testArrayAccess() {
		$request = new CakeRequest('some/path');
		$request->params = array('controller' => 'posts', 'action' => 'view', 'plugin' => 'blogs');

		$this->assertEqual($request['controller'], 'posts');

		$request['slug'] = 'speedy-slug';
		$this->assertEqual($request->slug, 'speedy-slug');
		$this->assertEqual($request['slug'], 'speedy-slug');

		$this->assertTrue(isset($request['action']));
		$this->assertFalse(isset($request['wrong-param']));

		$this->assertTrue(isset($request['plugin']));
		unset($request['plugin']);
		$this->assertFalse(isset($request['plugin']));
		$this->assertNull($request['plugin']);
		$this->assertNull($request->plugin);

		$request = new CakeRequest('some/path?one=something&two=else');
		$this->assertTrue(isset($request['url']['one']));

		$request->data = array('Post' => array('title' => 'something'));
		$this->assertEqual($request['data']['Post']['title'], 'something');
	}

/**
 * test adding detectors and having them work.
 *
 * @return void
 */
	function testAddDetector() {
		$request = new CakeRequest('some/path');
		$request->addDetector('compare', array('env' => 'TEST_VAR', 'value' => 'something'));

		$_SERVER['TEST_VAR'] = 'something';
		$this->assertTrue($request->is('compare'), 'Value match failed.');

		$_SERVER['TEST_VAR'] = 'wrong';
		$this->assertFalse($request->is('compare'), 'Value mis-match failed.');

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

		$request->addDetector('callme', array('env' => 'TEST_VAR', 'callback' => array($this, '_detectCallback')));

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
	function _detectCallback($request) {
		return $request->return == true;
	}

/**
 * test getting headers
 *
 * @return void
 */
	function testHeader() {
		$_SERVER['HTTP_HOST'] = 'localhost';
		$_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (Macintosh; U; Intel Mac OS X 10_6_4; en-ca) AppleWebKit/534.8+ (KHTML, like Gecko) Version/5.0 Safari/533.16';
		$request = new CakeRequest('/', false);

		$this->assertEquals($_SERVER['HTTP_HOST'], $request->header('host'));
		$this->assertEquals($_SERVER['HTTP_USER_AGENT'], $request->header('User-Agent'));
	}

/**
 * test accepts() with and without parameters
 *
 * @return void
 */
	function testAccepts() {
		$_SERVER['HTTP_ACCEPT'] = 'text/xml,application/xml;q=0.9,application/xhtml+xml,text/html,text/plain,image/png';
		$request = new CakeRequest('/', false);
		
		$result = $request->accepts();
		$expected = array(
			'text/xml', 'application/xml', 'application/xhtml+xml', 'text/html', 'text/plain', 'image/png'
		);
		$this->assertEquals($expected, $result, 'Content types differ.');
		
		$result = $request->accepts('text/html');
		$this->assertTrue($result);
		
		$result = $request->accepts('image/gif');
		$this->assertFalse($result);
	}

/**
 * testBaseUrlAndWebrootWithModRewrite method
 *
 * @return void
 */
	public function testBaseUrlAndWebrootWithModRewrite() {
		Configure::write('App.baseUrl', false);

		$_SERVER['DOCUMENT_ROOT'] = '/cake/repo/branches';
		$_SERVER['SCRIPT_FILENAME'] = '/cake/repo/branches/1.2.x.x/app/webroot/index.php';
		$_SERVER['PHP_SELF'] = '/1.2.x.x/app/webroot/index.php';
		$_GET['url'] = 'posts/view/1';

		$request = new CakeRequest();
		$this->assertEqual($request->base, '/1.2.x.x');
		$this->assertEqual($request->webroot, '/1.2.x.x/');
		$this->assertEqual($request->url, 'posts/view/1');


		$_SERVER['DOCUMENT_ROOT'] = '/cake/repo/branches/1.2.x.x/app/webroot';
		$_SERVER['SCRIPT_FILENAME'] = '/cake/repo/branches/1.2.x.x/app/webroot/index.php';
		$_SERVER['PHP_SELF'] = '/index.php';
		$_GET['url'] = 'posts/add';
		$request = new CakeRequest();

		$this->assertEqual($request->base, '');
		$this->assertEqual($request->webroot, '/');
		$this->assertEqual($request->url, 'posts/add');

		$_SERVER['DOCUMENT_ROOT'] = '/cake/repo/branches/1.2.x.x/test/';
		$_SERVER['SCRIPT_FILENAME'] = '/cake/repo/branches/1.2.x.x/test/webroot/index.php';
		$_SERVER['PHP_SELF'] = '/webroot/index.php';
		$request = new CakeRequest();

		$this->assertEqual('', $request->base);
		$this->assertEqual('/', $request->webroot);


		$_SERVER['DOCUMENT_ROOT'] = '/some/apps/where';
		$_SERVER['SCRIPT_FILENAME'] = '/some/apps/where/app/webroot/index.php';
		$_SERVER['PHP_SELF'] = '/some/apps/where/app/webroot/index.php';
		$request = new CakeRequest();

		$this->assertEqual($request->base, '/some/apps/where');
		$this->assertEqual($request->webroot, '/some/apps/where/');

		Configure::write('App.dir', 'auth');

		$_SERVER['DOCUMENT_ROOT'] = '/cake/repo/branches';
		$_SERVER['SCRIPT_FILENAME'] = '/cake/repo/branches/demos/auth/webroot/index.php';
		$_SERVER['PHP_SELF'] = '/demos/auth/webroot/index.php';

		$request = new CakeRequest();

		$this->assertEqual($request->base, '/demos/auth');
		$this->assertEqual($request->webroot, '/demos/auth/');

		Configure::write('App.dir', 'code');

		$_SERVER['DOCUMENT_ROOT'] = '/Library/WebServer/Documents';
		$_SERVER['SCRIPT_FILENAME'] = '/Library/WebServer/Documents/clients/PewterReport/code/webroot/index.php';
		$_SERVER['PHP_SELF'] = '/clients/PewterReport/code/webroot/index.php';
		$request = new CakeRequest();

		$this->assertEqual($request->base, '/clients/PewterReport/code');
		$this->assertEqual($request->webroot, '/clients/PewterReport/code/');
	}

/**
 * testBaseUrlwithModRewriteAlias method
 *
 * @return void
 */
	public function testBaseUrlwithModRewriteAlias() {
		$_SERVER['DOCUMENT_ROOT'] = '/home/aplusnur/public_html';
		$_SERVER['SCRIPT_FILENAME'] = '/home/aplusnur/cake2/app/webroot/index.php';
		$_SERVER['PHP_SELF'] = '/control/index.php';

		Configure::write('App.base', '/control');

		$request = new CakeRequest();

		$this->assertEqual($request->base, '/control');
		$this->assertEqual($request->webroot, '/control/');

		Configure::write('App.base', false);
		Configure::write('App.dir', 'affiliate');
		Configure::write('App.webroot', 'newaffiliate');

		$_SERVER['DOCUMENT_ROOT'] = '/var/www/abtravaff/html';
		$_SERVER['SCRIPT_FILENAME'] = '/var/www/abtravaff/html/newaffiliate/index.php';
		$_SERVER['PHP_SELF'] = '/newaffiliate/index.php';
		$request = new CakeRequest();

		$this->assertEqual($request->base, '/newaffiliate');
		$this->assertEqual($request->webroot, '/newaffiliate/');
	}

/**
 * test base, webroot, and url parsing when there is no url rewriting
 *
 * @return void
 */
	function testBaseUrlWithNoModRewrite() {
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

		$request = new CakeRequest();
		$this->assertEqual($request->base, '/cake/index.php');
		$this->assertEqual($request->webroot, '/cake/app/webroot/');
		$this->assertEqual($request->url, 'posts/index');
	}

/**
 * testBaseUrlAndWebrootWithBaseUrl method
 *
 * @return void
 */
	public function testBaseUrlAndWebrootWithBaseUrl() {
		Configure::write('App.dir', 'app');
		Configure::write('App.baseUrl', '/app/webroot/index.php');

		$request = new CakeRequest();
		$this->assertEqual($request->base, '/app/webroot/index.php');
		$this->assertEqual($request->webroot, '/app/webroot/');

		Configure::write('App.baseUrl', '/app/webroot/test.php');
		$request = new CakeRequest();
		$this->assertEqual($request->base, '/app/webroot/test.php');
		$this->assertEqual($request->webroot, '/app/webroot/');

		Configure::write('App.baseUrl', '/app/index.php');
		$request = new CakeRequest();
		$this->assertEqual($request->base, '/app/index.php');
		$this->assertEqual($request->webroot, '/app/webroot/');

		Configure::write('App.baseUrl', '/index.php');
		$request = new CakeRequest();

		$this->assertEqual($request->base, '/index.php');
		$this->assertEqual($request->webroot, '/');

		Configure::write('App.baseUrl', '/CakeBB/app/webroot/index.php');
		$request = new CakeRequest();
		$this->assertEqual($request->base, '/CakeBB/app/webroot/index.php');
		$this->assertEqual($request->webroot, '/CakeBB/app/webroot/');

		Configure::write('App.baseUrl', '/CakeBB/app/index.php');
		$request = new CakeRequest();

		$this->assertEqual($request->base, '/CakeBB/app/index.php');
		$this->assertEqual($request->webroot, '/CakeBB/app/webroot/');

		Configure::write('App.baseUrl', '/CakeBB/index.php');
		$request = new CakeRequest();

		$this->assertEqual($request->base, '/CakeBB/index.php');
		$this->assertEqual($request->webroot, '/CakeBB/app/webroot/');

		Configure::write('App.baseUrl', '/dbhauser/index.php');
		$_SERVER['DOCUMENT_ROOT'] = '/kunden/homepages/4/d181710652/htdocs/joomla';
		$_SERVER['SCRIPT_FILENAME'] = '/kunden/homepages/4/d181710652/htdocs/joomla/dbhauser/index.php';
		$request = new CakeRequest();

		$this->assertEqual($request->base, '/dbhauser/index.php');
		$this->assertEqual($request->webroot, '/dbhauser/app/webroot/');
	}

/**
 * testEnvironmentDetection method
 *
 * @return void
 */
	public function testEnvironmentDetection() {
		$dispatcher = new Dispatcher();

		$environments = array(
			'IIS' => array(
				'No rewrite base path' => array(
					'App' => array(
						'base' => false, 
						'baseUrl' => '/index.php?',
						'server' => 'IIS'
					),
					'SERVER' => array(
						'HTTPS' => 'off',
						'SCRIPT_NAME' => '/index.php',
						'PATH_TRANSLATED' => 'C:\\Inetpub\\wwwroot',
						'QUERY_STRING' => '',
						'REMOTE_ADDR' => '127.0.0.1',
						'REMOTE_HOST' => '127.0.0.1',
						'REQUEST_METHOD' => 'GET',
						'SERVER_NAME' => 'localhost',
						'SERVER_PORT' => '80',
						'SERVER_PROTOCOL' => 'HTTP/1.1', 
						'SERVER_SOFTWARE' => 'Microsoft-IIS/5.1', 
						'APPL_PHYSICAL_PATH' => 'C:\\Inetpub\\wwwroot\\', 
						'REQUEST_URI' => '/index.php', 
						'URL' => '/index.php', 
						'SCRIPT_FILENAME' => 'C:\\Inetpub\\wwwroot\\index.php', 
						'ORIG_PATH_INFO' => '/index.php', 
						'PATH_INFO' => '', 
						'ORIG_PATH_TRANSLATED' => 'C:\\Inetpub\\wwwroot\\index.php', 
						'DOCUMENT_ROOT' => 'C:\\Inetpub\\wwwroot', 
						'PHP_SELF' => '/index.php', 
						'HTTP_ACCEPT' => '*/*', 
						'HTTP_ACCEPT_LANGUAGE' => 'en-us', 
						'HTTP_CONNECTION' => 'Keep-Alive', 
						'HTTP_HOST' => 'localhost', 
						'HTTP_USER_AGENT' => 'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1; .NET CLR 2.0.50727)', 
						'HTTP_ACCEPT_ENCODING' => 'gzip, deflate', 
						'argv' => array(), 
						'argc' => 0
					),
					'reload' => true,
					'base' => '/index.php?',
					'webroot' => '/',
					'url' => ''
				),
				'No rewrite with path' => array(
					'SERVER' => array(
						'QUERY_STRING' => '/posts/add',
						'REQUEST_URI' => '/index.php?/posts/add',
						'URL' => '/index.php?/posts/add',
						'argv' => array('/posts/add'),
						'argc' => 1
					),
					'reload' => false,
					'url' => 'posts/add',
					'base' => '/index.php?',
					'webroot' => '/'
				),
				'No rewrite sub dir 1' => array(
					'GET' => array(),
					'SERVER' => array(
						'QUERY_STRING' => '',  
						'REQUEST_URI' => '/index.php', 
						'URL' => '/index.php', 
						'SCRIPT_FILENAME' => 'C:\\Inetpub\\wwwroot\\index.php', 
						'ORIG_PATH_INFO' => '/index.php', 
						'PATH_INFO' => '', 
						'ORIG_PATH_TRANSLATED' => 'C:\\Inetpub\\wwwroot\\index.php', 
						'DOCUMENT_ROOT' => 'C:\\Inetpub\\wwwroot', 
						'PHP_SELF' => '/index.php', 
						'argv' => array(), 
						'argc' => 0
					),
					'reload' => false,
					'url' => '',
					'base' => '/index.php?',
					'webroot' => '/'
				),
				'No rewrite sub dir 1 with path' => array(
					'GET' => array('/posts/add' => ''),
					'SERVER' => array(
						'QUERY_STRING' => '/posts/add', 
						'REQUEST_URI' => '/index.php?/posts/add', 
						'URL' => '/index.php?/posts/add', 
						'SCRIPT_FILENAME' => 'C:\\Inetpub\\wwwroot\\index.php', 
						'argv' => array('/posts/add'), 
						'argc' => 1
					),
					'reload' => false,
					'url' => 'posts/add',
					'base' => '/index.php?',
					'webroot' => '/'
				),
				'No rewrite sub dir 2' => array(
					'App' => array(
						'base' => false, 
						'baseUrl' => '/site/index.php?', 
						'dir' => 'app', 
						'webroot' => 'webroot', 
						'server' => 'IIS'
					),
					'GET' => array(),
					'POST' => array(),
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
					'reload' => false,
					'url' => '',
					'base' => '/site/index.php?',
					'webroot' => '/site/app/webroot/'
				),
				'No rewrite sub dir 2 with path' => array(
					'GET' => array('/posts/add' => ''),
					'SERVER' => array(
						'SCRIPT_NAME' => '/site/index.php', 
						'PATH_TRANSLATED' => 'C:\\Inetpub\\wwwroot', 
						'QUERY_STRING' => '/posts/add', 
						'REQUEST_URI' => '/site/index.php?/posts/add', 
						'URL' => '/site/index.php?/posts/add', 
						'ORIG_PATH_TRANSLATED' => 'C:\\Inetpub\\wwwroot\\site\\index.php', 
						'DOCUMENT_ROOT' => 'C:\\Inetpub\\wwwroot', 
						'PHP_SELF' => '/site/index.php', 
						'argv' => array('/posts/add'), 
						'argc' => 1
					),
					'reload' => false,
					'url' => 'posts/add',
					'base' => '/site/index.php?',
					'webroot' => '/site/app/webroot/'
				)
			),
			'Apache' => array(
				'No rewrite base path' => array(
					'App' => array(
						'base' => false, 
						'baseUrl' => '/index.php', 
						'dir' => 'app', 
						'webroot' => 'webroot'
					),
					'SERVER' => array(
						'SERVER_NAME' => 'localhost', 
						'SERVER_ADDR' => '::1', 
						'SERVER_PORT' => '80', 
						'REMOTE_ADDR' => '::1', 
						'DOCUMENT_ROOT' => '/Library/WebServer/Documents/officespace/app/webroot', 
						'SCRIPT_FILENAME' => '/Library/WebServer/Documents/site/app/webroot/index.php', 
						'REQUEST_METHOD' => 'GET', 
						'QUERY_STRING' => '', 
						'REQUEST_URI' => '/', 
						'SCRIPT_NAME' => '/index.php', 
						'PHP_SELF' => '/index.php', 
						'argv' => array(), 
						'argc' => 0
					),
					'reload' => true,
					'url' => '',
					'base' => '/index.php',
					'webroot' => '/'
				),
				'No rewrite with path' => array(
					'SERVER' => array(
						'UNIQUE_ID' => 'VardGqn@17IAAAu7LY8AAAAK', 
						'HTTP_USER_AGENT' => 'Mozilla/5.0 (Macintosh; U; Intel Mac OS X; en-us) AppleWebKit/523.10.5 (KHTML, like Gecko) Version/3.0.4 Safari/523.10.6', 
						'HTTP_ACCEPT' => 'text/xml,application/xml,application/xhtml+xml,text/html;q=0.9,text/plain;q=0.8,image/png,*/*;q=0.5', 
						'HTTP_ACCEPT_LANGUAGE' => 'en-us', 
						'HTTP_ACCEPT_ENCODING' => 'gzip, deflate', 
						'HTTP_CONNECTION' => 'keep-alive', 
						'HTTP_HOST' => 'localhost', 
						'DOCUMENT_ROOT' => '/Library/WebServer/Documents/officespace/app/webroot', 
						'SCRIPT_FILENAME' => '/Library/WebServer/Documents/officespace/app/webroot/index.php', 
						'QUERY_STRING' => '', 
						'REQUEST_URI' => '/index.php/posts/add', 
						'SCRIPT_NAME' => '/index.php', 
						'PATH_INFO' => '/posts/add', 
						'PHP_SELF' => '/index.php/posts/add', 
						'argv' => array(), 
						'argc' => 0
					),
					'reload' => false,
					'url' => 'posts/add',
					'base' => '/index.php',
					'webroot' => '/'
				),
				'GET Request at base domain' => array(
					'App' => array(
						'base' => false, 
						'baseUrl' => null, 
						'dir' => 'app', 
						'webroot' => 'webroot'
					),
					'SERVER' => array(
						'UNIQUE_ID' => '2A-v8sCoAQ8AAAc-2xUAAAAB', 
						'HTTP_ACCEPT_LANGUAGE' => 'en-us', 
						'HTTP_ACCEPT_ENCODING' => 'gzip, deflate', 
						'HTTP_COOKIE' => 'CAKEPHP=jcbv51apn84kd9ucv5aj2ln3t3', 
						'HTTP_CONNECTION' => 'keep-alive', 
						'HTTP_HOST' => 'cake.1.2', 
						'SERVER_NAME' => 'cake.1.2', 
						'SERVER_ADDR' => '127.0.0.1', 
						'SERVER_PORT' => '80', 
						'REMOTE_ADDR' => '127.0.0.1', 
						'DOCUMENT_ROOT' => '/Volumes/Home/htdocs/cake/repo/branches/1.2.x.x/app/webroot', 
						'SERVER_ADMIN' => 'you@example.com', 
						'SCRIPT_FILENAME' => '/Volumes/Home/htdocs/cake/repo/branches/1.2.x.x/app/webroot/index.php', 
						'REMOTE_PORT' => '53550', 
						'GATEWAY_INTERFACE' => 'CGI/1.1', 
						'SERVER_PROTOCOL' => 'HTTP/1.1', 
						'REQUEST_METHOD' => 'GET', 
						'QUERY_STRING' => 'a=b', 
						'REQUEST_URI' => '/?a=b', 
						'SCRIPT_NAME' => '/index.php', 
						'PHP_SELF' => '/index.php'
					),
					'GET' => array('a' => 'b'),
					'POST' => array(),
					'reload' => true,
					'url' => '',
					'base' => '',
					'webroot' => '/',
					'urlParams' => array('a' => 'b'),
					'environment' => array('CGI_MODE' => false)
				),
				'New CGI no mod_rewrite' => array(
					'App' => array(
						'base' => false, 
						'baseUrl' => '/limesurvey20/index.php', 
						'dir' => 'app', 
						'webroot' => 'webroot'
					),
					'SERVER' => array(
						'DOCUMENT_ROOT' => '/home/.sites/110/site313/web', 
						'PATH_INFO' => '/installations', 
						'PATH_TRANSLATED' => '/home/.sites/110/site313/web/limesurvey20/index.php', 
						'PHPRC' => '/home/.sites/110/site313', 
						'QUERY_STRING' => '', 
						'REQUEST_METHOD' => 'GET', 
						'REQUEST_URI' => '/limesurvey20/index.php/installations', 
						'SCRIPT_FILENAME' => '/home/.sites/110/site313/web/limesurvey20/index.php', 
						'SCRIPT_NAME' => '/limesurvey20/index.php', 
						'SCRIPT_URI' => 'http://www.gisdat-umfragen.at/limesurvey20/index.php/installations', 
						'PHP_SELF' => '/limesurvey20/index.php/installations', 
						'CGI_MODE' => true
					),
					'GET' => array(),
					'POST' => array(),
					'reload' => true,
					'webroot' => '/limesurvey20/app/webroot/',
					'base' => '/limesurvey20/index.php',
					'url' => 'installations',
					'urlParams' => array(),
					'environment' => array('CGI_MODE' => true)
				)
			)
		);
		$backup = $this->__backupEnvironment();

		foreach ($environments as $name => $env) {
			foreach ($env as $descrip => $settings) {
				if ($settings['reload']) {
					$this->__reloadEnvironment();
				}
				$this->__loadEnvironment($settings);
		
				$request = new CakeRequest();
				$this->assertEqual($request->url, $settings['url'], "%s url on env: {$name} on setting {$descrip}");
				$this->assertEqual($request->base, $settings['base'], "%s base on env: {$name} on setting {$descrip}");
				$this->assertEqual($request->webroot, $settings['webroot'], "%s webroot on env: {$name} on setting {$descrip}");
				
				
				if (isset($settings['urlParams'])) {
					$this->assertEqual($_GET, $settings['urlParams'], "%s on environment: {$name}, on setting: {$descrip}");
				}
				

				if (isset($settings['environment'])) {
					foreach ($settings['environment'] as $key => $val) {
						$this->assertEqual(env($key), $val, "%s on key {$key} on environment: {$name}, on setting: {$descrip}");
					}
				}
			}
		}
		$this->__loadEnvironment(array_merge(array('reload' => true), $backup));
	}

/**
 * test that XSS can't be performed against the base path.
 *
 * @return void
 */
	function testBasePathInjection() {
		$self = $_SERVER['PHP_SELF'];
		$_SERVER['PHP_SELF'] = urldecode(
			"/index.php/%22%3E%3Ch1%20onclick=%22alert('xss');%22%3Eheya%3C/h1%3E"
		);

		$request = new CakeRequest();
		$expected = '/index.php/h1 onclick=alert(xss);heya';
		$this->assertEqual($request->base, $expected);
	}

/**
 * test the data() method reading
 *
 * @return void
 */
	function testDataReading() {
		$_POST['data'] = array(
			'Model' => array(
				'field' => 'value'
			)
		);
		$request = new CakeRequest('posts/index');
		$result = $request->data('Model');
		$this->assertEquals($_POST['data']['Model'], $result);

		$result = $request->data('Model.imaginary');
		$this->assertNull($result);
	}

/**
 * test writing with data()
 *
 * @return void
 */
	function testDataWriting() {
		$_POST['data'] = array(
			'Model' => array(
				'field' => 'value'
			)
		);
		$request = new CakeRequest('posts/index');
		$result = $request->data('Model.new_value', 'new value');
		$this->assertSame($result, $request, 'Return was not $this');
		
		$this->assertEquals($request->data['Model']['new_value'], 'new value');

		$request->data('Post.title', 'New post')->data('Comment.1.author', 'Mark');
		$this->assertEquals($request->data['Post']['title'], 'New post');
		$this->assertEquals($request->data['Comment']['1']['author'], 'Mark');
	}

/**
 * test writing falsey values.
 *
 * @return void
 */
	function testDataWritingFalsey() {
		$request = new CakeRequest('posts/index');

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
	function testAcceptLanguage() {
		$_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'inexistent,en-ca';
		$result = CakeRequest::acceptLanguage();
		$this->assertEquals(array('inexistent', 'en-ca'), $result, 'Languages do not match');

		$_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'es_mx;en_ca';
		$result = CakeRequest::acceptLanguage();
		$this->assertEquals(array('es-mx', 'en-ca'), $result, 'Languages do not match');
		
		$result = CakeRequest::acceptLanguage('en-ca');
		$this->assertTrue($result);

		$result = CakeRequest::acceptLanguage('en-us');
		$this->assertFalse($result);
	}

/**
 * backupEnvironment method
 *
 * @return void
 * @access private
 */
	function __backupEnvironment() {
		return array(
			'App' => Configure::read('App'),
			'GET' => $_GET,
			'POST' => $_POST,
			'SERVER' => $_SERVER
		);
	}

/**
 * reloadEnvironment method
 *
 * @return void
 * @access private
 */
	function __reloadEnvironment() {
		foreach ($_GET as $key => $val) {
			unset($_GET[$key]);
		}
		foreach ($_POST as $key => $val) {
			unset($_POST[$key]);
		}
		foreach ($_SERVER as $key => $val) {
			unset($_SERVER[$key]);
		}
		Configure::write('App', array());
	}

/**
 * loadEnvironment method
 *
 * @param mixed $env
 * @return void
 * @access private
 */
	function __loadEnvironment($env) {
		if ($env['reload']) {
			$this->__reloadEnvironment();
		}

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