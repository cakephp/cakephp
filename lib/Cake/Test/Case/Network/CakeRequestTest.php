<?php
/**
 * CakeRequest Test case file.
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

App::uses('Dispatcher', 'Routing');
App::uses('Xml', 'Utility');
App::uses('CakeRequest', 'Network');

class CakeRequestTest extends CakeTestCase {
/**
 * setup callback
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();
		$this->_server = $_SERVER;
		$this->_get = $_GET;
		$this->_post = $_POST;
		$this->_files = $_FILES;
		$this->_app = Configure::read('App');
		$this->_case = null;
		if (isset($_GET['case'])) {
			$this->_case = $_GET['case'];
			unset($_GET['case']);
		}

		Configure::write('App.baseUrl', false);
	}

/**
 * tearDown-
 *
 * @return void
 */
	public function tearDown() {
		parent::tearDown();
		$_SERVER = $this->_server;
		$_GET = $this->_get;
		$_POST = $this->_post;
		$_FILES = $this->_files;
		if (!empty($this->_case)) {
			$_GET['case'] = $this->_case;
		}
		Configure::write('App', $this->_app);
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
		$request = new CakeRequest(null, false);
		$this->assertFalse(isset($request->query['one']));
	}

/**
 * test construction
 *
 * @return void
 */
	public function testConstructionGetParsing() {
		$_GET = array(
			'one' => 'param',
			'two' => 'banana'
		);
		$request = new CakeRequest('some/path');
		$this->assertEqual($request->query, $_GET);

		$_GET = array(
			'one' => 'param',
			'two' => 'banana',
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
	public function testQueryStringParsingFromInputUrl() {
		$_GET = array();
		$request = new CakeRequest('some/path?one=something&two=else');
		$expected = array('one' => 'something', 'two' => 'else');
		$this->assertEqual($request->query, $expected);
		$this->assertEquals('some/path?one=something&two=else', $request->url);

	}

/**
 * test addParams() method
 *
 * @return void
 */
	public function testAddParams() {
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
	public function testAddPaths() {
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
	public function testPostParsing() {
		$_POST = array('data' => array(
			'Article' => array('title')
		));
		$request = new CakeRequest('some/path');
		$this->assertEqual($request->data, $_POST['data']);

		$_POST = array('one' => 1, 'two' => 'three');
		$request = new CakeRequest('some/path');
		$this->assertEquals($_POST, $request->data);
	}

/**
 * test parsing of FILES array
 *
 * @return void
 */
	public function testFILESParsing() {
		$_FILES = array('data' => array('name' => array(
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
		));

		$request = new CakeRequest('some/path');
		$expected = array(
			'File' => array(
				array('data' => array(
					'name' => 'cake_sqlserver_patch.patch',
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
	public function testMethodOverrides() {
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
	public function testclientIp() {
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
	public function testReferer() {
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
	public function testIsHttpMethods() {
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
	public function testMethod() {
		$_SERVER['REQUEST_METHOD'] = 'delete';
		$request = new CakeRequest('some/path');

		$this->assertEquals('delete', $request->method());
	}

/**
 * test host retrieval.
 *
 * @return void
 */
	public function testHost() {
		$_SERVER['HTTP_HOST'] = 'localhost';
		$request = new CakeRequest('some/path');

		$this->assertEquals('localhost', $request->host());
	}

/**
 * test domain retrieval.
 *
 * @return void
 */
	public function testDomain() {
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
	public function testSubdomain() {
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
	public function testisAjaxFlashAndFriends() {
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
	public function test__callExceptionOnUnknownMethod() {
		$request = new CakeRequest('some/path');
		$request->IamABanana();
	}

/**
 * test is(ssl)
 *
 * @return void
 */
	public function testIsSsl() {
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
	public function test__get() {
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
	public function testArrayAccess() {
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
	public function testAddDetector() {
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
	public function testHeader() {
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
	public function testAccepts() {
		$_SERVER['HTTP_ACCEPT'] = 'text/xml,application/xml;q=0.9,application/xhtml+xml,text/html,text/plain,image/png';
		$request = new CakeRequest('/', false);

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
		$request = new CakeRequest('/', false);
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
		$request = new CakeRequest('/', false);
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
		$request = new CakeRequest('/', false);
		$result = $request->parseAccept();
		$expected = array(
			'1.0' => array('application/xml', 'image/png'),
			'0.8' => array('text/html'),
			'0.7' => array('application/json'),
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
		$_SERVER['SCRIPT_NAME'] = '/1.2.x.x/app/webroot/index.php';
		$_SERVER['PATH_INFO'] = '/posts/view/1';

		$request = new CakeRequest();
		$this->assertEqual($request->base, '/1.2.x.x');
		$this->assertEqual($request->webroot, '/1.2.x.x/');
		$this->assertEqual($request->url, 'posts/view/1');


		$_SERVER['DOCUMENT_ROOT'] = '/cake/repo/branches/1.2.x.x/app/webroot';
		$_SERVER['SCRIPT_NAME'] = '/index.php';
		$_SERVER['PATH_INFO'] = '/posts/add';
		$request = new CakeRequest();

		$this->assertEqual($request->base, '');
		$this->assertEqual($request->webroot, '/');
		$this->assertEqual($request->url, 'posts/add');

		$_SERVER['DOCUMENT_ROOT'] = '/cake/repo/branches/1.2.x.x/test/';
		$_SERVER['SCRIPT_NAME'] = '/webroot/index.php';
		$request = new CakeRequest();

		$this->assertEqual('', $request->base);
		$this->assertEqual('/', $request->webroot);


		$_SERVER['DOCUMENT_ROOT'] = '/some/apps/where';
		$_SERVER['SCRIPT_NAME'] = '/app/webroot/index.php';
		$request = new CakeRequest();

		$this->assertEqual($request->base, '');
		$this->assertEqual($request->webroot, '/');

		Configure::write('App.dir', 'auth');

		$_SERVER['DOCUMENT_ROOT'] = '/cake/repo/branches';
		$_SERVER['SCRIPT_NAME'] = '/demos/auth/webroot/index.php';

		$request = new CakeRequest();

		$this->assertEqual($request->base, '/demos/auth');
		$this->assertEqual($request->webroot, '/demos/auth/');

		Configure::write('App.dir', 'code');

		$_SERVER['DOCUMENT_ROOT'] = '/Library/WebServer/Documents';
		$_SERVER['SCRIPT_NAME'] = '/clients/PewterReport/code/webroot/index.php';
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
		$_SERVER['SCRIPT_NAME'] = '/control/index.php';

		Configure::write('App.base', '/control');

		$request = new CakeRequest();

		$this->assertEqual($request->base, '/control');
		$this->assertEqual($request->webroot, '/control/');

		Configure::write('App.base', false);
		Configure::write('App.dir', 'affiliate');
		Configure::write('App.webroot', 'newaffiliate');

		$_SERVER['DOCUMENT_ROOT'] = '/var/www/abtravaff/html';
		$_SERVER['SCRIPT_NAME'] = '/newaffiliate/index.php';
		$request = new CakeRequest();

		$this->assertEqual($request->base, '/newaffiliate');
		$this->assertEqual($request->webroot, '/newaffiliate/');
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
 * test baseUrl with no rewrite and using the top level index.php.
 *
 * @return void
 */
	public function testBaseUrlNoRewriteTopLevelIndex() {
		Configure::write('App.baseUrl', '/index.php');
		$_SERVER['DOCUMENT_ROOT'] = '/Users/markstory/Sites/cake_dev';
		$_SERVER['SCRIPT_FILENAME'] = '/Users/markstory/Sites/cake_dev/index.php';

		$request = new CakeRequest();
		$this->assertEqual('/index.php', $request->base);
		$this->assertEqual('/app/webroot/', $request->webroot);
	}

/**
 * test baseUrl with no rewrite, and using the app/webroot/index.php file as is normal with virtual hosts.
 *
 * @return void
 */
	public function testBaseUrlNoRewriteWebrootIndex() {
		Configure::write('App.baseUrl', '/index.php');
		$_SERVER['DOCUMENT_ROOT'] = '/Users/markstory/Sites/cake_dev/app/webroot';
		$_SERVER['SCRIPT_FILENAME'] = '/Users/markstory/Sites/cake_dev/app/webroot/index.php';

		$request = new CakeRequest();
		$this->assertEqual('/index.php', $request->base);
		$this->assertEqual('/', $request->webroot);
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
						'dir' => 'app',
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
					'webroot' => '/app/webroot/',
					'url' => ''
				),
			),
			array(
				'IIS - No rewrite with path, no PHP_SELF',
				array(
					'App' => array(
						'base' => false,
						'baseUrl' => '/index.php?',
						'dir' => 'app',
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
					'webroot' => '/app/webroot/'
				)
			),
			array(
				'IIS - No rewrite sub dir 2',
				array(
					'App' => array(
						'base' => false,
						'baseUrl' => '/site/index.php',
						'dir' => 'app',
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
					'webroot' => '/site/app/webroot/'
				),
			),
			array(
				'IIS - No rewrite sub dir 2 with path',
				array(
					'App' => array(
						'base' => false,
						'baseUrl' => '/site/index.php',
						'dir' => 'app',
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
					'webroot' => '/site/app/webroot/'
				)
			),
			array(
				'Apache - No rewrite, document root set to webroot, requesting path',
				array(
					'App' => array(
						'base' => false,
						'baseUrl' => '/index.php',
						'dir' => 'app',
						'webroot' => 'webroot'
					),
					'SERVER' => array(
						'DOCUMENT_ROOT' => '/Library/WebServer/Documents/site/app/webroot',
						'SCRIPT_FILENAME' => '/Library/WebServer/Documents/site/app/webroot/index.php',
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
						'dir' => 'app',
						'webroot' => 'webroot'
					),
					'SERVER' => array(
						'DOCUMENT_ROOT' => '/Library/WebServer/Documents/site/app/webroot',
						'SCRIPT_FILENAME' => '/Library/WebServer/Documents/site/app/webroot/index.php',
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
						'dir' => 'app',
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
					'webroot' => '/site/app/webroot/',
				),
			),
			array(
				'Apache - No rewrite, document root set above top level cake dir, request root, no PATH_INFO',
				array(
					'App' => array(
						'base' => false,
						'baseUrl' => '/site/index.php',
						'dir' => 'app',
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
					'webroot' => '/site/app/webroot/',
				),
			),
			array(
				'Apache - No rewrite, document root set above top level cake dir, request path, with GET',
				array(
					'App' => array(
						'base' => false,
						'baseUrl' => '/site/index.php',
						'dir' => 'app',
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
					'webroot' => '/site/app/webroot/',
				),
			),
			array(
				'Apache - w/rewrite, document root set above top level cake dir, request root, no PATH_INFO',
				array(
					'App' => array(
						'base' => false,
						'baseUrl' => false,
						'dir' => 'app',
						'webroot' => 'webroot'
					),
					'SERVER' => array(
						'SERVER_NAME' => 'localhost',
						'DOCUMENT_ROOT' => '/Library/WebServer/Documents',
						'SCRIPT_FILENAME' => '/Library/WebServer/Documents/site/index.php',
						'REQUEST_URI' => '/site/',
						'SCRIPT_NAME' => '/site/app/webroot/index.php',
						'PHP_SELF' => '/site/app/webroot/index.php',
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
						'dir' => 'app',
						'webroot' => 'webroot'
					),
					'SERVER' => array(
						'SERVER_NAME' => 'localhost',
						'DOCUMENT_ROOT' => '/Library/WebServer/Documents',
						'SCRIPT_FILENAME' => '/Library/WebServer/Documents/site/index.php',
						'SCRIPT_NAME' => '/site/app/webroot/index.php',
						'PHP_SELF' => '/site/app/webroot/index.php',
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
						'dir' => 'app',
						'webroot' => 'webroot'
					),
					'SERVER' => array(
						'SERVER_NAME' => 'localhost',
						'DOCUMENT_ROOT' => '/Library/WebServer/Documents/site/app/webroot',
						'SCRIPT_FILENAME' => '/Library/WebServer/Documents/site/app/webroot/index.php',
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
				'Nginx - w/rewrite, document root set to webroot, request root, no PATH_INFO',
				array(
					'App' => array(
						'base' => false,
						'baseUrl' => false,
						'dir' => 'app',
						'webroot' => 'webroot'
					),
					'GET' => array('/posts/add' => ''),
					'SERVER' => array(
						'SERVER_NAME' => 'localhost',
						'DOCUMENT_ROOT' => '/Library/WebServer/Documents/site/app/webroot',
						'SCRIPT_FILENAME' => '/Library/WebServer/Documents/site/app/webroot/index.php',
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
		$this->__loadEnvironment($env);

		$request = new CakeRequest();
		$this->assertEquals($expected['url'], $request->url, "url error");
		$this->assertEquals($expected['base'], $request->base, "base error");
		$this->assertEquals($expected['webroot'], $request->webroot, "webroot error");
		if (isset($expected['urlParams'])) {
			$this->assertEqual($request->query, $expected['urlParams'], "GET param mismatch");
		}
	}

/**
 * test the data() method reading
 *
 * @return void
 */
	public function testDataReading() {
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
	public function testDataWriting() {
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
	public function testDataWritingFalsey() {
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
	public function testAcceptLanguage() {
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
 * test the here() method
 *
 * @return void
 */
	public function testHere() {
		Configure::write('App.base', '/base_path');
		$_GET = array('test' => 'value');
		$request = new CakeRequest('/posts/add/1/name:value');

		$result = $request->here();
		$this->assertEquals('/base_path/posts/add/1/name:value?test=value', $result);

		$result = $request->here(false);
		$this->assertEquals('/posts/add/1/name:value?test=value', $result);

		$request = new CakeRequest('/posts/base_path/1/name:value');
		$result = $request->here();
		$this->assertEquals('/base_path/posts/base_path/1/name:value?test=value', $result);

		$result = $request->here(false);
		$this->assertEquals('/posts/base_path/1/name:value?test=value', $result);
	}

/**
 * Test the input() method.
 *
 * @return void
 */
	public function testInput() {
		$request = $this->getMock('CakeRequest', array('_readInput'));
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
		$request = $this->getMock('CakeRequest', array('_readInput'));
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

		$request = $this->getMock('CakeRequest', array('_readInput'));
		$request->expects($this->once())->method('_readInput')
			->will($this->returnValue($xml));

		$result = $request->input('Xml::build', array('return' => 'domdocument'));
		$this->assertInstanceOf('DOMDocument', $result);
		$this->assertEquals(
			'Test',
			$result->getElementsByTagName('title')->item(0)->childNodes->item(0)->wholeText
		);
	}

/**
 * loadEnvironment method
 *
 * @param mixed $env
 * @return void
 */
	function __loadEnvironment($env) {
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
