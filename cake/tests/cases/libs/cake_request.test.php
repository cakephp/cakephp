<?php

App::import('Core', 'CakeRequest');

class CakeRequestTestCase extends CakeTestCase {
/**
 * setup callback
 *
 * @return void
 */
	function startTest() {
		$this->_server = $_SERVER;
		$this->_get = $_GET;
		$this->_post = $_POST;
		$this->_files = $_FILES;
		$this->_app = Configure::read('App');
	}

/**
 * end test
 *
 * @return void
 */
	function endTest() {
		$_SERVER = $this->_server;
		$_GET = $this->_get;
		$_POST = $this->_post;
		$_FILES = $this->_files;
		Configure::write('App', $this->_app);
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
		$this->assertEqual($request->query, $_GET);
		
		$_GET = array(
			'one' => 'param',
			'two' => 'banana',
			'url' => '/some/path/here'
		);
		$request = new CakeRequest('some/path');
		$this->assertEqual($request->query, $_GET);
		$this->assertEqual($request->url, 'some/path');
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
 * test the getClientIp method.
 *
 * @return void
 */
	function testGetClientIp() {
		$_SERVER['HTTP_X_FORWARDED_FOR'] = '192.168.1.5, 10.0.1.1, proxy.com';
		$_SERVER['HTTP_CLIENT_IP'] = '192.168.1.2';
		$_SERVER['REMOTE_ADDR'] = '192.168.1.3';
		$request = new CakeRequest('some/path');
		$this->assertEqual($request->getClientIP(false), '192.168.1.5');
		$this->assertEqual($request->getClientIP(), '192.168.1.2');

		unset($_SERVER['HTTP_X_FORWARDED_FOR']);
		$this->assertEqual($request->getClientIP(), '192.168.1.2');

		unset($_SERVER['HTTP_CLIENT_IP']);
		$this->assertEqual($request->getClientIP(), '192.168.1.3');

		$_SERVER['HTTP_CLIENTADDRESS'] = '10.0.1.2, 10.0.1.1';
		$this->assertEqual($request->getClientIP(), '10.0.1.2');
	}

/**
 * test the referer function.
 *
 * @return void
 */
	function testReferer() {
		$request = new CakeRequest('some/path');

		$_SERVER['HTTP_REFERER'] = 'http://cakephp.org';
		$result = $request->referer();
		$this->assertIdentical($result, 'http://cakephp.org');

		$_SERVER['HTTP_REFERER'] = '';
		$result = $request->referer();
		$this->assertIdentical($result, '/');

		$_SERVER['HTTP_REFERER'] = FULL_BASE_URL . 'some/path';
		$result = $request->referer(true);
		$this->assertIdentical($result, '/some/path');

		$_SERVER['HTTP_REFERER'] = FULL_BASE_URL . 'some/path';
		$result = $request->referer();
		$this->assertIdentical($result, FULL_BASE_URL . 'some/path');

		$_SERVER['HTTP_REFERER'] = FULL_BASE_URL . 'some/path';
		$result = $request->referer(true);
		$this->assertIdentical($result, '/some/path');

		$_SERVER['HTTP_REFERER'] = FULL_BASE_URL . 'recipes/add';
		$result = $request->referer(true);
		$this->assertIdentical($result, '/recipes/add');
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
		$this->assertTrue($request->is('compare'), 'Value match failed %s.');

		$_SERVER['TEST_VAR'] = 'wrong';
		$this->assertFalse($request->is('compare'), 'Value mis-match failed %s.');

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
 * testGetUrl method
 *
 * @return void
 */
	public function XXtestGetUrl() {
		$request = new CakeRequest();
		$request->base = '/app/webroot/index.php';
		$uri = '/app/webroot/index.php/posts/add';
		unset($_GET['url']);

		$result = $request->getUrl($uri);
		$expected = 'posts/add';
		$this->assertEqual($expected, $result);

		Configure::write('App.baseUrl', '/app/webroot/index.php');

		$uri = '/posts/add';
		$result = $request->getUrl($uri);
		$expected = 'posts/add';
		$this->assertEqual($expected, $result);

		$_GET['url'] = array();
		Configure::write('App.base', '/control');
		$request = new CakeRequest();
		unset($_GET['url']);

		$request->baseUrl();
		$uri = '/control/students/browse';
		$result = $request->getUrl($uri);
		$expected = 'students/browse';
		$this->assertEqual($expected, $result);

		$request = new CakeRequest();
		$_GET['url'] = array();

		$request->base = '';
		$uri = '/?/home';
		$result = $request->getUrl($uri);
		$expected = '?/home';
		$this->assertEqual($expected, $result);
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

}