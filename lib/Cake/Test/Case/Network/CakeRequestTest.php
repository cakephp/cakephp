<?php
/**
 * CakeRequest Test case file.
 *
 * PHP 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       Cake.Test.Case.Network
 * @since         CakePHP(tm) v 2.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */

App::uses('Dispatcher', 'Routing');
App::uses('Xml', 'Utility');
App::uses('CakeRequest', 'Network');

/**
 * Class TestCakeRequest
 *
 * @package       Cake.Test.Case.Network
 */
class TestCakeRequest extends CakeRequest {

/**
 * reConstruct method
 *
 * @param string $url
 * @param boolean $parseEnvironment
 * @return void
 */
	public function reConstruct($url = 'some/path', $parseEnvironment = true) {
		$this->_base();
		if (empty($url)) {
			$url = $this->_url();
		}
		if ($url[0] === '/') {
			$url = substr($url, 1);
		}
		$this->url = $url;

		if ($parseEnvironment) {
			$this->_processPost();
			$this->_processGet();
			$this->_processFiles();
		}
		$this->here = $this->base . '/' . $this->url;
	}

}

/**
 * Class CakeRequestTest
 */
class CakeRequestTest extends CakeTestCase {

/**
 * Setup callback
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();
		$this->_app = Configure::read('App');
		$this->_case = null;
		if (isset($_GET['case'])) {
			$this->_case = $_GET['case'];
			unset($_GET['case']);
		}

		Configure::write('App.baseUrl', false);
	}

/**
 * TearDown
 *
 * @return void
 */
	public function tearDown() {
		parent::tearDown();
		if (!empty($this->_case)) {
			$_GET['case'] = $this->_case;
		}
		Configure::write('App', $this->_app);
	}

/**
 * Test that the autoparse = false constructor works.
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
 * Test construction
 *
 * @return void
 */
	public function testConstructionGetParsing() {
		$_GET = array(
			'one' => 'param',
			'two' => 'banana'
		);
		$request = new CakeRequest('some/path');
		$this->assertEquals($request->query, $_GET);

		$_GET = array(
			'one' => 'param',
			'two' => 'banana',
		);
		$request = new CakeRequest('some/path');
		$this->assertEquals($request->query, $_GET);
		$this->assertEquals('some/path', $request->url);
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
		$this->assertEquals($expected, $request->query);
		$this->assertEquals('some/path?one=something&two=else', $request->url);
	}

/**
 * Test that named arguments + querystrings are handled correctly.
 *
 * @return void
 */
	public function testQueryStringAndNamedParams() {
		$_SERVER['REQUEST_URI'] = '/tasks/index/page:1?ts=123456';
		$request = new CakeRequest();
		$this->assertEquals('tasks/index/page:1', $request->url);

		$_SERVER['REQUEST_URI'] = '/tasks/index/page:1/?ts=123456';
		$request = new CakeRequest();
		$this->assertEquals('tasks/index/page:1/', $request->url);

		$_SERVER['REQUEST_URI'] = '/some/path?url=http://cakephp.org';
		$request = new CakeRequest();
		$this->assertEquals('some/path', $request->url);

		$_SERVER['REQUEST_URI'] = Configure::read('App.fullBaseUrl') . '/other/path?url=http://cakephp.org';
		$request = new CakeRequest();
		$this->assertEquals('other/path', $request->url);
	}

/**
 * Test addParams() method
 *
 * @return void
 */
	public function testAddParams() {
		$request = new CakeRequest('some/path');
		$request->params = array('controller' => 'posts', 'action' => 'view');
		$result = $request->addParams(array('plugin' => null, 'action' => 'index'));

		$this->assertSame($result, $request, 'Method did not return itself. %s');

		$this->assertEquals('posts', $request->controller);
		$this->assertEquals('index', $request->action);
		$this->assertEquals(null, $request->plugin);
	}

/**
 * Test splicing in paths.
 *
 * @return void
 */
	public function testAddPaths() {
		$request = new CakeRequest('some/path');
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
 * Test parsing POST data into the object.
 *
 * @return void
 */
	public function testPostParsing() {
		$_POST = array('data' => array(
			'Article' => array('title')
		));
		$request = new CakeRequest('some/path');
		$this->assertEquals($_POST['data'], $request->data);

		$_POST = array('one' => 1, 'two' => 'three');
		$request = new CakeRequest('some/path');
		$this->assertEquals($_POST, $request->data);

		$_POST = array(
			'data' => array(
				'Article' => array('title' => 'Testing'),
			),
			'action' => 'update'
		);
		$request = new CakeRequest('some/path');
		$expected = array(
			'Article' => array('title' => 'Testing'),
			'action' => 'update'
		);
		$this->assertEquals($expected, $request->data);

		$_POST = array('data' => array(
			'Article' => array('title'),
			'Tag' => array('Tag' => array(1, 2))
		));
		$request = new CakeRequest('some/path');
		$this->assertEquals($_POST['data'], $request->data);

		$_POST = array('data' => array(
			'Article' => array('title' => 'some title'),
			'Tag' => array('Tag' => array(1, 2))
		));
		$request = new CakeRequest('some/path');
		$this->assertEquals($_POST['data'], $request->data);

		$_POST = array(
			'a' => array(1, 2),
			'b' => array(1, 2)
		);
		$request = new CakeRequest('some/path');
		$this->assertEquals($_POST, $request->data);
	}

/**
 * Test parsing PUT data into the object.
 *
 * @return void
 */
	public function testPutParsing() {
		$_SERVER['REQUEST_METHOD'] = 'PUT';
		$_SERVER['CONTENT_TYPE'] = 'application/x-www-form-urlencoded; charset=UTF-8';

		$data = array('data' => array(
			'Article' => array('title')
		));

		$request = $this->getMock('TestCakeRequest', array('_readInput'));
		$request->expects($this->at(0))->method('_readInput')
			->will($this->returnValue('data[Article][]=title'));
		$request->reConstruct();
		$this->assertEquals($data['data'], $request->data);

		$data = array('one' => 1, 'two' => 'three');
		$request = $this->getMock('TestCakeRequest', array('_readInput'));
		$request->expects($this->at(0))->method('_readInput')
			->will($this->returnValue('one=1&two=three'));
		$request->reConstruct();
		$this->assertEquals($data, $request->data);

		$data = array(
			'data' => array(
				'Article' => array('title' => 'Testing'),
			),
			'action' => 'update'
		);
		$request = $this->getMock('TestCakeRequest', array('_readInput'));
		$request->expects($this->at(0))->method('_readInput')
			->will($this->returnValue('data[Article][title]=Testing&action=update'));
		$request->reConstruct();
		$expected = array(
			'Article' => array('title' => 'Testing'),
			'action' => 'update'
		);
		$this->assertEquals($expected, $request->data);

		$_SERVER['REQUEST_METHOD'] = 'DELETE';
		$data = array('data' => array(
			'Article' => array('title'),
			'Tag' => array('Tag' => array(1, 2))
		));
		$request = $this->getMock('TestCakeRequest', array('_readInput'));
		$request->expects($this->at(0))->method('_readInput')
			->will($this->returnValue('data[Article][]=title&Tag[Tag][]=1&Tag[Tag][]=2'));
		$request->reConstruct();
		$this->assertEquals($data['data'], $request->data);

		$data = array('data' => array(
			'Article' => array('title' => 'some title'),
			'Tag' => array('Tag' => array(1, 2))
		));
		$request = $this->getMock('TestCakeRequest', array('_readInput'));
		$request->expects($this->at(0))->method('_readInput')
			->will($this->returnValue('data[Article][title]=some%20title&Tag[Tag][]=1&Tag[Tag][]=2'));
		$request->reConstruct();
		$this->assertEquals($data['data'], $request->data);

		$data = array(
			'a' => array(1, 2),
			'b' => array(1, 2)
		);
		$request = $this->getMock('TestCakeRequest', array('_readInput'));
		$request->expects($this->at(0))->method('_readInput')
			->will($this->returnValue('a[]=1&a[]=2&b[]=1&b[]=2'));
		$request->reConstruct();
		$this->assertEquals($data, $request->data);
	}

/**
 * Test parsing json PUT data into the object.
 *
 * @return void
 */
	public function testPutParsingJSON() {
		$_SERVER['REQUEST_METHOD'] = 'PUT';
		$_SERVER['CONTENT_TYPE'] = 'application/json';

		$request = $this->getMock('TestCakeRequest', array('_readInput'));
		$request->expects($this->at(0))->method('_readInput')
			->will($this->returnValue('{"Article":["title"]}'));
		$request->reConstruct();
		$result = $request->input('json_decode', true);
		$this->assertEquals(array('title'), $result['Article']);
	}

/**
 * Test parsing of FILES array
 *
 * @return void
 */
	public function testFilesParsing() {
		$_FILES = array(
			'data' => array(
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
			)
		);

		$request = new CakeRequest('some/path');
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
		$this->assertEquals($expected, $request->data);

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
		$this->assertEquals($expected, $request->data);

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
		$this->assertEquals($request->params['form'], $_FILES);
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

		$request = new CakeRequest('some/path');
		$this->assertEquals($_FILES, $request->params['form']);
	}

/**
 * Test method overrides coming in from POST data.
 *
 * @return void
 */
	public function testMethodOverrides() {
		$_POST = array('_method' => 'POST');
		$request = new CakeRequest('some/path');
		$this->assertEquals(env('REQUEST_METHOD'), 'POST');

		$_POST = array('_method' => 'DELETE');
		$request = new CakeRequest('some/path');
		$this->assertEquals(env('REQUEST_METHOD'), 'DELETE');

		$_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE'] = 'PUT';
		$request = new CakeRequest('some/path');
		$this->assertEquals(env('REQUEST_METHOD'), 'PUT');
	}

/**
 * Test the clientIp method.
 *
 * @return void
 */
	public function testclientIp() {
		$_SERVER['HTTP_X_FORWARDED_FOR'] = '192.168.1.5, 10.0.1.1, proxy.com';
		$_SERVER['HTTP_CLIENT_IP'] = '192.168.1.2';
		$_SERVER['REMOTE_ADDR'] = '192.168.1.3';
		$request = new CakeRequest('some/path');
		$this->assertEquals('192.168.1.5', $request->clientIp(false));
		$this->assertEquals('192.168.1.2', $request->clientIp());

		unset($_SERVER['HTTP_X_FORWARDED_FOR']);
		$this->assertEquals('192.168.1.2', $request->clientIp());

		unset($_SERVER['HTTP_CLIENT_IP']);
		$this->assertEquals('192.168.1.3', $request->clientIp());

		$_SERVER['HTTP_CLIENTADDRESS'] = '10.0.1.2, 10.0.1.1';
		$this->assertEquals('10.0.1.2', $request->clientIp());
	}

/**
 * Test the referrer function.
 *
 * @return void
 */
	public function testReferer() {
		$request = new CakeRequest('some/path');
		$request->webroot = '/';

		$_SERVER['HTTP_REFERER'] = 'http://cakephp.org';
		$result = $request->referer();
		$this->assertSame($result, 'http://cakephp.org');

		$_SERVER['HTTP_REFERER'] = '';
		$result = $request->referer();
		$this->assertSame($result, '/');

		$_SERVER['HTTP_REFERER'] = Configure::read('App.fullBaseUrl') . '/some/path';
		$result = $request->referer(true);
		$this->assertSame($result, '/some/path');

		$_SERVER['HTTP_REFERER'] = Configure::read('App.fullBaseUrl') . '/some/path';
		$result = $request->referer(false);
		$this->assertSame($result, Configure::read('App.fullBaseUrl') . '/some/path');

		$_SERVER['HTTP_REFERER'] = Configure::read('App.fullBaseUrl') . '/some/path';
		$result = $request->referer(true);
		$this->assertSame($result, '/some/path');

		$_SERVER['HTTP_REFERER'] = Configure::read('App.fullBaseUrl') . '/recipes/add';
		$result = $request->referer(true);
		$this->assertSame($result, '/recipes/add');

		$_SERVER['HTTP_X_FORWARDED_HOST'] = 'cakephp.org';
		$result = $request->referer();
		$this->assertSame($result, 'cakephp.org');
	}

/**
 * Test referer() with a base path that duplicates the
 * first segment.
 *
 * @return void
 */
	public function testRefererBasePath() {
		$request = new CakeRequest('some/path');
		$request->url = 'users/login';
		$request->webroot = '/waves/';
		$request->base = '/waves';
		$request->here = '/waves/users/login';

		$_SERVER['HTTP_REFERER'] = FULL_BASE_URL . '/waves/waves/add';

		$result = $request->referer(true);
		$this->assertSame($result, '/waves/add');
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
 * Test is() with multiple types.
 *
 * @return void
 */
	public function testIsMultiple() {
		$request = new CakeRequest('some/path');

		$_SERVER['REQUEST_METHOD'] = 'GET';
		$this->assertTrue($request->is(array('get', 'post')));

		$_SERVER['REQUEST_METHOD'] = 'POST';
		$this->assertTrue($request->is(array('get', 'post')));

		$_SERVER['REQUEST_METHOD'] = 'PUT';
		$this->assertFalse($request->is(array('get', 'post')));
	}

/**
 * Test isAll()
 *
 * @return void
 */
	public function testIsAll() {
		$request = new CakeRequest('some/path');

		$_SERVER['HTTP_X_REQUESTED_WITH'] = 'XMLHttpRequest';
		$_SERVER['REQUEST_METHOD'] = 'GET';

		$this->assertTrue($request->isAll(array('ajax', 'get')));
		$this->assertFalse($request->isAll(array('post', 'get')));
		$this->assertFalse($request->isAll(array('ajax', 'post')));
	}

/**
 * Test the method() method.
 *
 * @return void
 */
	public function testMethod() {
		$_SERVER['REQUEST_METHOD'] = 'delete';
		$request = new CakeRequest('some/path');

		$this->assertEquals('delete', $request->method());
	}

/**
 * Test host retrieval.
 *
 * @return void
 */
	public function testHost() {
		$_SERVER['HTTP_HOST'] = 'localhost';
		$request = new CakeRequest('some/path');

		$this->assertEquals('localhost', $request->host());
	}

/**
 * Test domain retrieval.
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
 * Test getting subdomains for a host.
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
 * Test ajax, flash and friends
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

		$_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (compatible; MSIE 9.0; Windows Phone OS 7.5; Trident/5.0; IEMobile/9.0; SAMSUNG; OMNIA7)';
		$this->assertTrue($request->is('mobile'));
		$this->assertTrue($request->isMobile());
	}

/**
 * Test __call exceptions
 *
 * @expectedException CakeException
 * @return void
 */
	public function testMagicCallExceptionOnUnknownMethod() {
		$request = new CakeRequest('some/path');
		$request->IamABanana();
	}

/**
 * Test is(ssl)
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
 * Test getting request params with object properties.
 *
 * @return void
 */
	public function testMagicget() {
		$request = new CakeRequest('some/path');
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
		$request = new CakeRequest('some/path');
		$request->params = array(
			'controller' => 'posts',
			'action' => 'view',
			'plugin' => 'blogs',
			'named' => array()
		);

		$this->assertTrue(isset($request->controller));
		$this->assertFalse(isset($request->notthere));
		$this->assertFalse(empty($request->controller));
		$this->assertTrue(empty($request->named));
	}

/**
 * Test the array access implementation
 *
 * @return void
 */
	public function testArrayAccess() {
		$request = new CakeRequest('some/path');
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

		$request = new CakeRequest('some/path?one=something&two=else');
		$this->assertTrue(isset($request['url']['one']));

		$request->data = array('Post' => array('title' => 'something'));
		$this->assertEquals('something', $request['data']['Post']['title']);
	}

/**
 * Test adding detectors and having them work.
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
 * Helper function for testing callbacks.
 *
 * @param $request
 * @return bool
 */
	public function detectCallback($request) {
		return (bool)$request->return;
	}

/**
 * Test getting headers
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
 * Test accepts() with and without parameters
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
 * Test parsing accept with a confusing accept value.
 *
 * @return void
 */
	public function testParseAcceptNoQValues() {
		$_SERVER['HTTP_ACCEPT'] = 'application/json, text/plain, */*';

		$request = new CakeRequest('/', false);
		$result = $request->parseAccept();
		$expected = array(
			'1.0' => array('application/json', 'text/plain', '*/*'),
		);
		$this->assertEquals($expected, $result);
	}

/**
 * Test baseUrl and webroot with ModRewrite
 *
 * @return void
 */
	public function testBaseUrlAndWebrootWithModRewrite() {
		Configure::write('App.baseUrl', false);

		$_SERVER['DOCUMENT_ROOT'] = '/cake/repo/branches';
		$_SERVER['PHP_SELF'] = '/urlencode me/app/webroot/index.php';
		$_SERVER['PATH_INFO'] = '/posts/view/1';

		$request = new CakeRequest();
		$this->assertEquals('/urlencode%20me', $request->base);
		$this->assertEquals('/urlencode%20me/', $request->webroot);
		$this->assertEquals('posts/view/1', $request->url);

		$_SERVER['DOCUMENT_ROOT'] = '/cake/repo/branches';
		$_SERVER['PHP_SELF'] = '/1.2.x.x/app/webroot/index.php';
		$_SERVER['PATH_INFO'] = '/posts/view/1';

		$request = new CakeRequest();
		$this->assertEquals('/1.2.x.x', $request->base);
		$this->assertEquals('/1.2.x.x/', $request->webroot);
		$this->assertEquals('posts/view/1', $request->url);

		$_SERVER['DOCUMENT_ROOT'] = '/cake/repo/branches/1.2.x.x/app/webroot';
		$_SERVER['PHP_SELF'] = '/index.php';
		$_SERVER['PATH_INFO'] = '/posts/add';
		$request = new CakeRequest();

		$this->assertEquals('', $request->base);
		$this->assertEquals('/', $request->webroot);
		$this->assertEquals('posts/add', $request->url);

		$_SERVER['DOCUMENT_ROOT'] = '/cake/repo/branches/1.2.x.x/test/';
		$_SERVER['PHP_SELF'] = '/webroot/index.php';
		$request = new CakeRequest();

		$this->assertEquals('', $request->base);
		$this->assertEquals('/', $request->webroot);

		$_SERVER['DOCUMENT_ROOT'] = '/some/apps/where';
		$_SERVER['PHP_SELF'] = '/app/webroot/index.php';
		$request = new CakeRequest();

		$this->assertEquals('', $request->base);
		$this->assertEquals('/', $request->webroot);

		Configure::write('App.dir', 'auth');

		$_SERVER['DOCUMENT_ROOT'] = '/cake/repo/branches';
		$_SERVER['PHP_SELF'] = '/demos/auth/webroot/index.php';

		$request = new CakeRequest();

		$this->assertEquals('/demos/auth', $request->base);
		$this->assertEquals('/demos/auth/', $request->webroot);

		Configure::write('App.dir', 'code');

		$_SERVER['DOCUMENT_ROOT'] = '/Library/WebServer/Documents';
		$_SERVER['PHP_SELF'] = '/clients/PewterReport/code/webroot/index.php';
		$request = new CakeRequest();

		$this->assertEquals('/clients/PewterReport/code', $request->base);
		$this->assertEquals('/clients/PewterReport/code/', $request->webroot);
	}

/**
 * Test baseUrl with ModRewrite alias
 *
 * @return void
 */
	public function testBaseUrlwithModRewriteAlias() {
		$_SERVER['DOCUMENT_ROOT'] = '/home/aplusnur/public_html';
		$_SERVER['PHP_SELF'] = '/control/index.php';

		Configure::write('App.base', '/control');

		$request = new CakeRequest();

		$this->assertEquals('/control', $request->base);
		$this->assertEquals('/control/', $request->webroot);

		Configure::write('App.base', false);
		Configure::write('App.dir', 'affiliate');
		Configure::write('App.webroot', 'newaffiliate');

		$_SERVER['DOCUMENT_ROOT'] = '/var/www/abtravaff/html';
		$_SERVER['PHP_SELF'] = '/newaffiliate/index.php';
		$request = new CakeRequest();

		$this->assertEquals('/newaffiliate', $request->base);
		$this->assertEquals('/newaffiliate/', $request->webroot);
	}

/**
 * Test base, webroot, url and here parsing when there is url rewriting but
 * CakePHP gets called with index.php in url nonetheless.
 *
 * Tests uri with
 * - index.php/
 * - index.php/
 * - index.php/apples/
 * - index.php/bananas/eat/tasty_banana
 *
 * @link https://cakephp.lighthouseapp.com/projects/42648-cakephp/tickets/3318
 */
	public function testBaseUrlWithModRewriteAndIndexPhp() {
		$_SERVER['REQUEST_URI'] = '/cakephp/app/webroot/index.php';
		$_SERVER['PHP_SELF'] = '/cakephp/app/webroot/index.php';
		unset($_SERVER['PATH_INFO']);
		$request = new CakeRequest();

		$this->assertEquals('/cakephp', $request->base);
		$this->assertEquals('/cakephp/', $request->webroot);
		$this->assertEquals('', $request->url);
		$this->assertEquals('/cakephp/', $request->here);

		$_SERVER['REQUEST_URI'] = '/cakephp/app/webroot/index.php/';
		$_SERVER['PHP_SELF'] = '/cakephp/app/webroot/index.php/';
		$_SERVER['PATH_INFO'] = '/';
		$request = new CakeRequest();

		$this->assertEquals('/cakephp', $request->base);
		$this->assertEquals('/cakephp/', $request->webroot);
		$this->assertEquals('', $request->url);
		$this->assertEquals('/cakephp/', $request->here);

		$_SERVER['REQUEST_URI'] = '/cakephp/app/webroot/index.php/apples';
		$_SERVER['PHP_SELF'] = '/cakephp/app/webroot/index.php/apples';
		$_SERVER['PATH_INFO'] = '/apples';
		$request = new CakeRequest();

		$this->assertEquals('/cakephp', $request->base);
		$this->assertEquals('/cakephp/', $request->webroot);
		$this->assertEquals('apples', $request->url);
		$this->assertEquals('/cakephp/apples', $request->here);

		$_SERVER['REQUEST_URI'] = '/cakephp/app/webroot/index.php/melons/share/';
		$_SERVER['PHP_SELF'] = '/cakephp/app/webroot/index.php/melons/share/';
		$_SERVER['PATH_INFO'] = '/melons/share/';
		$request = new CakeRequest();

		$this->assertEquals('/cakephp', $request->base);
		$this->assertEquals('/cakephp/', $request->webroot);
		$this->assertEquals('melons/share/', $request->url);
		$this->assertEquals('/cakephp/melons/share/', $request->here);

		$_SERVER['REQUEST_URI'] = '/cakephp/app/webroot/index.php/bananas/eat/tasty_banana';
		$_SERVER['PHP_SELF'] = '/cakephp/app/webroot/index.php/bananas/eat/tasty_banana';
		$_SERVER['PATH_INFO'] = '/bananas/eat/tasty_banana';
		$request = new CakeRequest();

		$this->assertEquals('/cakephp', $request->base);
		$this->assertEquals('/cakephp/', $request->webroot);
		$this->assertEquals('bananas/eat/tasty_banana', $request->url);
		$this->assertEquals('/cakephp/bananas/eat/tasty_banana', $request->here);
	}

/**
 * Test base, webroot, and url parsing when there is no url rewriting
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
		$this->assertEquals('/cake/index.php', $request->base);
		$this->assertEquals('/cake/app/webroot/', $request->webroot);
		$this->assertEquals('posts/index', $request->url);
	}

/**
 * Test baseUrl and webroot with baseUrl
 *
 * @return void
 */
	public function testBaseUrlAndWebrootWithBaseUrl() {
		Configure::write('App.dir', 'app');
		Configure::write('App.baseUrl', '/app/webroot/index.php');

		$request = new CakeRequest();
		$this->assertEquals('/app/webroot/index.php', $request->base);
		$this->assertEquals('/app/webroot/', $request->webroot);

		Configure::write('App.baseUrl', '/app/webroot/test.php');
		$request = new CakeRequest();
		$this->assertEquals('/app/webroot/test.php', $request->base);
		$this->assertEquals('/app/webroot/', $request->webroot);

		Configure::write('App.baseUrl', '/app/index.php');
		$request = new CakeRequest();
		$this->assertEquals('/app/index.php', $request->base);
		$this->assertEquals('/app/webroot/', $request->webroot);

		Configure::write('App.baseUrl', '/CakeBB/app/webroot/index.php');
		$request = new CakeRequest();
		$this->assertEquals('/CakeBB/app/webroot/index.php', $request->base);
		$this->assertEquals('/CakeBB/app/webroot/', $request->webroot);

		Configure::write('App.baseUrl', '/CakeBB/app/index.php');
		$request = new CakeRequest();

		$this->assertEquals('/CakeBB/app/index.php', $request->base);
		$this->assertEquals('/CakeBB/app/webroot/', $request->webroot);

		Configure::write('App.baseUrl', '/CakeBB/index.php');
		$request = new CakeRequest();

		$this->assertEquals('/CakeBB/index.php', $request->base);
		$this->assertEquals('/CakeBB/app/webroot/', $request->webroot);

		Configure::write('App.baseUrl', '/dbhauser/index.php');
		$_SERVER['DOCUMENT_ROOT'] = '/kunden/homepages/4/d181710652/htdocs/joomla';
		$_SERVER['SCRIPT_FILENAME'] = '/kunden/homepages/4/d181710652/htdocs/joomla/dbhauser/index.php';
		$request = new CakeRequest();

		$this->assertEquals('/dbhauser/index.php', $request->base);
		$this->assertEquals('/dbhauser/app/webroot/', $request->webroot);
	}

/**
 * Test baseUrl with no rewrite and using the top level index.php.
 *
 * @return void
 */
	public function testBaseUrlNoRewriteTopLevelIndex() {
		Configure::write('App.baseUrl', '/index.php');
		$_SERVER['DOCUMENT_ROOT'] = '/Users/markstory/Sites/cake_dev';
		$_SERVER['SCRIPT_FILENAME'] = '/Users/markstory/Sites/cake_dev/index.php';

		$request = new CakeRequest();
		$this->assertEquals('/index.php', $request->base);
		$this->assertEquals('/app/webroot/', $request->webroot);
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

		$request = new CakeRequest();
		$this->assertEquals('/approval/index.php', $request->base);
		$this->assertEquals('/approval/app/webroot/', $request->webroot);

		Configure::write('App.baseUrl', '/webrootable/index.php');
		$_SERVER['DOCUMENT_ROOT'] = '/Users/markstory/Sites/';
		$_SERVER['SCRIPT_FILENAME'] = '/Users/markstory/Sites/webrootable/index.php';

		$request = new CakeRequest();
		$this->assertEquals('/webrootable/index.php', $request->base);
		$this->assertEquals('/webrootable/app/webroot/', $request->webroot);
	}

/**
 * Test baseUrl with no rewrite, and using the app/webroot/index.php file as is normal with virtual hosts.
 *
 * @return void
 */
	public function testBaseUrlNoRewriteWebrootIndex() {
		Configure::write('App.baseUrl', '/index.php');
		$_SERVER['DOCUMENT_ROOT'] = '/Users/markstory/Sites/cake_dev/app/webroot';
		$_SERVER['SCRIPT_FILENAME'] = '/Users/markstory/Sites/cake_dev/app/webroot/index.php';

		$request = new CakeRequest();
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
		$_SERVER['PHP_SELF'] = '/cake_dev/app/webroot/index.php';
		$_SERVER['REQUEST_URI'] = '/cake_dev/posts/index/add.add';

		$request = new CakeRequest();
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
		$_SERVER['PHP_SELF'] = '/cake_dev/app/webroot/index.php';
		$_SERVER['REQUEST_URI'] = '/cake_dev/posts/add/%E2%88%82%E2%88%82';

		$request = new CakeRequest();
		$this->assertEquals(array(), $request->query);
	}

/**
 * Generator for environment configurations
 *
 * @return array Environment array
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
				'Apache - w/rewrite, document root set above top level cake dir, request root, absolute REQUEST_URI',
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
						'REQUEST_URI' => '/site/posts/index',
						'SCRIPT_NAME' => '/site/app/webroot/index.php',
						'PHP_SELF' => '/site/app/webroot/index.php',
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
 * Test environment detection
 *
 * @dataProvider environmentGenerator
 * @param $name
 * @param $env
 * @param $expected
 * @return void
 */
	public function testEnvironmentDetection($name, $env, $expected) {
		$_GET = array();
		$this->_loadEnvironment($env);

		$request = new CakeRequest();
		$this->assertEquals($expected['url'], $request->url, "url error");
		$this->assertEquals($expected['base'], $request->base, "base error");
		$this->assertEquals($expected['webroot'], $request->webroot, "webroot error");
		if (isset($expected['urlParams'])) {
			$this->assertEquals($expected['urlParams'], $request->query, "GET param mismatch");
		}
	}

/**
 * Test the query() method
 *
 * @return void
 */
	public function testQuery() {
		$_GET = array();
		$_GET['foo'] = 'bar';

		$request = new CakeRequest();

		$result = $request->query('foo');
		$this->assertEquals('bar', $result);

		$result = $request->query('imaginary');
		$this->assertNull($result);
	}

/**
 * Test the query() method with arrays passed via $_GET
 *
 * @return void
 */
	public function testQueryWithArray() {
		$_GET = array();
		$_GET['test'] = array('foo', 'bar');

		$request = new CakeRequest();

		$result = $request->query('test');
		$this->assertEquals(array('foo', 'bar'), $result);

		$result = $request->query('test.1');
		$this->assertEquals('bar', $result);

		$result = $request->query('test.2');
		$this->assertNull($result);
	}

/**
 * Test using param()
 *
 * @return void
 */
	public function testReadingParams() {
		$request = new CakeRequest();
		$request->addParams(array(
			'controller' => 'posts',
			'admin' => true,
			'truthy' => 1,
			'zero' => '0',
		));
		$this->assertFalse($request->param('not_set'));
		$this->assertTrue($request->param('admin'));
		$this->assertEquals(1, $request->param('truthy'));
		$this->assertEquals('posts', $request->param('controller'));
		$this->assertEquals('0', $request->param('zero'));
	}

/**
 * Test the data() method reading
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
 * Test writing with data()
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

		$this->assertEquals('new value', $request->data['Model']['new_value']);

		$request->data('Post.title', 'New post')->data('Comment.1.author', 'Mark');
		$this->assertEquals('New post', $request->data['Post']['title']);
		$this->assertEquals('Mark', $request->data['Comment']['1']['author']);
	}

/**
 * Test writing falsey values.
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
 * Test accept language
 *
 * @return void
 */
	public function testAcceptLanguage() {
		// Weird language
		$_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'inexistent,en-ca';
		$result = CakeRequest::acceptLanguage();
		$this->assertEquals(array('inexistent', 'en-ca'), $result, 'Languages do not match');

		// No qualifier
		$_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'es_mx,en_ca';
		$result = CakeRequest::acceptLanguage();
		$this->assertEquals(array('es-mx', 'en-ca'), $result, 'Languages do not match');

		// With qualifier
		$_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'en-US,en;q=0.8,pt-BR;q=0.6,pt;q=0.4';
		$result = CakeRequest::acceptLanguage();
		$this->assertEquals(array('en-us', 'en', 'pt-br', 'pt'), $result, 'Languages do not match');

		// With spaces
		$_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'da, en-gb;q=0.8, en;q=0.7';
		$result = CakeRequest::acceptLanguage();
		$this->assertEquals(array('da', 'en-gb', 'en'), $result, 'Languages do not match');

		// Checking if requested
		$_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'es_mx,en_ca';
		$result = CakeRequest::acceptLanguage();

		$result = CakeRequest::acceptLanguage('en-ca');
		$this->assertTrue($result);

		$result = CakeRequest::acceptLanguage('en-CA');
		$this->assertTrue($result);

		$result = CakeRequest::acceptLanguage('en-us');
		$this->assertFalse($result);

		$result = CakeRequest::acceptLanguage('en-US');
		$this->assertFalse($result);
	}

/**
 * Test the here() method
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
 * Test is('requested') and isRequested()
 *
 * @return void
 */
	public function testIsRequested() {
		$request = new CakeRequest('/posts/index');
		$request->addParams(array(
			'controller' => 'posts',
			'action' => 'index',
			'plugin' => null,
			'requested' => 1
		));
		$this->assertTrue($request->is('requested'));
		$this->assertTrue($request->isRequested());

		$request = new CakeRequest('/posts/index');
		$request->addParams(array(
			'controller' => 'posts',
			'action' => 'index',
			'plugin' => null,
		));
		$this->assertFalse($request->is('requested'));
		$this->assertFalse($request->isRequested());
	}

/**
 * Test onlyAllow method
 *
 * @return void
 */
	public function testOnlyAllow() {
		$_SERVER['REQUEST_METHOD'] = 'PUT';
		$request = new CakeRequest('/posts/edit/1');

		$this->assertTrue($request->onlyAllow(array('put')));

		$_SERVER['REQUEST_METHOD'] = 'DELETE';
		$this->assertTrue($request->onlyAllow('post', 'delete'));
	}

/**
 * Test onlyAllow throwing exception
 *
 * @return void
 */
	public function testOnlyAllowException() {
		$_SERVER['REQUEST_METHOD'] = 'PUT';
		$request = new CakeRequest('/posts/edit/1');

		try {
			$request->onlyAllow('POST', 'DELETE');
			$this->fail('An expected exception has not been raised.');
		} catch (MethodNotAllowedException $e) {
			$this->assertEquals(array('Allow' => 'POST, DELETE'), $e->responseHeader());
		}

		$this->setExpectedException('MethodNotAllowedException');
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
