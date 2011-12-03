<?php
/**
 * DispatcherTest file
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) Tests <http://book.cakephp.org/view/1196/Testing>
 * Copyright 2005-2011, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 *  Licensed under The Open Group Test Suite License
 *  Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2011, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/view/1196/Testing CakePHP(tm) Tests
 * @package       cake
 * @subpackage    cake.tests.cases
 * @since         CakePHP(tm) v 1.2.0.4206
 * @license       http://www.opensource.org/licenses/opengroup.php The Open Group Test Suite License
 */
require_once CAKE . 'dispatcher.php';

if (!class_exists('AppController')) {
	require_once LIBS . 'controller' . DS . 'app_controller.php';
} elseif (!defined('APP_CONTROLLER_EXISTS')){
	define('APP_CONTROLLER_EXISTS', true);
}

/**
 * TestDispatcher class
 *
 * @package       cake
 * @subpackage    cake.tests.cases
 */
class TestDispatcher extends Dispatcher {

/**
 * invoke method
 *
 * @param mixed $controller
 * @param mixed $params
 * @param mixed $missingAction
 * @return void
 * @access protected
 */
	function _invoke(&$controller, $params) {
		restore_error_handler();
		if ($result = parent::_invoke($controller, $params)) {
			if ($result[0] === 'missingAction') {
				return $result;
			}
		}
		set_error_handler('simpleTestErrorHandler');

		return $controller;
	}

/**
 * cakeError method
 *
 * @param mixed $filename
 * @return void
 * @access public
 */
	function cakeError($filename, $params) {
		return array($filename, $params);
	}

/**
 * _stop method
 *
 * @return void
 * @access protected
 */
	function _stop() {
		$this->stopped = true;
		return true;
	}
}

/**
 * MyPluginAppController class
 *
 * @package       cake
 * @subpackage    cake.tests.cases
 */
class MyPluginAppController extends AppController {
}

/**
 * MyPluginController class
 *
 * @package       cake
 * @subpackage    cake.tests.cases
 */
class MyPluginController extends MyPluginAppController {

/**
 * name property
 *
 * @var string 'MyPlugin'
 * @access public
 */
	var $name = 'MyPlugin';

/**
 * uses property
 *
 * @var array
 * @access public
 */
	var $uses = array();

/**
 * index method
 *
 * @return void
 * @access public
 */
	function index() {
		return true;
	}

/**
 * add method
 *
 * @return void
 * @access public
 */
	function add() {
		return true;
	}

/**
 * admin_add method
 *
 * @param mixed $id
 * @return void
 * @access public
 */
	function admin_add($id = null) {
		return $id;
	}
}

/**
 * SomePagesController class
 *
 * @package       cake
 * @subpackage    cake.tests.cases
 */
class SomePagesController extends AppController {

/**
 * name property
 *
 * @var string 'SomePages'
 * @access public
 */
	var $name = 'SomePages';

/**
 * uses property
 *
 * @var array
 * @access public
 */
	var $uses = array();

/**
 * display method
 *
 * @param mixed $page
 * @return void
 * @access public
 */
	function display($page = null) {
		return $page;
	}

/**
 * index method
 *
 * @return void
 * @access public
 */
	function index() {
		return true;
	}

/**
 * protected method
 *
 * @return void
 * @access protected
 */
	function _protected() {
		return true;
	}

/**
 * redirect method overriding
 *
 * @return void
 * @access public
 */
	function redirect() {
		echo 'this should not be accessible';
	}
}

/**
 * OtherPagesController class
 *
 * @package       cake
 * @subpackage    cake.tests.cases
 */
class OtherPagesController extends MyPluginAppController {

/**
 * name property
 *
 * @var string 'OtherPages'
 * @access public
 */
	var $name = 'OtherPages';

/**
 * uses property
 *
 * @var array
 * @access public
 */
	var $uses = array();

/**
 * display method
 *
 * @param mixed $page
 * @return void
 * @access public
 */
	function display($page = null) {
		return $page;
	}

/**
 * index method
 *
 * @return void
 * @access public
 */
	function index() {
		return true;
	}
}

/**
 * TestDispatchPagesController class
 *
 * @package       cake
 * @subpackage    cake.tests.cases
 */
class TestDispatchPagesController extends AppController {

/**
 * name property
 *
 * @var string 'TestDispatchPages'
 * @access public
 */
	var $name = 'TestDispatchPages';

/**
 * uses property
 *
 * @var array
 * @access public
 */
	var $uses = array();

/**
 * admin_index method
 *
 * @return void
 * @access public
 */
	function admin_index() {
		return true;
	}

/**
 * camelCased method
 *
 * @return void
 * @access public
 */
	function camelCased() {
		return true;
	}
}

/**
 * ArticlesTestAppController class
 *
 * @package       cake
 * @subpackage    cake.tests.cases
 */
class ArticlesTestAppController extends AppController {
}

/**
 * ArticlesTestController class
 *
 * @package       cake
 * @subpackage    cake.tests.cases
 */
class ArticlesTestController extends ArticlesTestAppController {

/**
 * name property
 *
 * @var string 'ArticlesTest'
 * @access public
 */
	var $name = 'ArticlesTest';

/**
 * uses property
 *
 * @var array
 * @access public
 */
	var $uses = array();

/**
 * admin_index method
 *
 * @return void
 * @access public
 */
	function admin_index() {
		return true;
	}
/**
 * fake index method.
 *
 * @return void
 */
	function index() {
		return true;
	}
}

/**
 * SomePostsController class
 *
 * @package       cake
 * @subpackage    cake.tests.cases
 */
class SomePostsController extends AppController {

/**
 * name property
 *
 * @var string 'SomePosts'
 * @access public
 */
	var $name = 'SomePosts';

/**
 * uses property
 *
 * @var array
 * @access public
 */
	var $uses = array();

/**
 * autoRender property
 *
 * @var bool false
 * @access public
 */
	var $autoRender = false;

/**
 * beforeFilter method
 *
 * @return void
 * @access public
 */
	function beforeFilter() {
		if ($this->params['action'] == 'index') {
			$this->params['action'] = 'view';
		} else {
			$this->params['action'] = 'change';
		}
		$this->params['pass'] = array('changed');
	}

/**
 * index method
 *
 * @return void
 * @access public
 */
	function index() {
		return true;
	}

/**
 * change method
 *
 * @return void
 * @access public
 */
	function change() {
		return true;
	}
}

/**
 * TestCachedPagesController class
 *
 * @package       cake
 * @subpackage    cake.tests.cases
 */
class TestCachedPagesController extends AppController {

/**
 * name property
 *
 * @var string 'TestCachedPages'
 * @access public
 */
	var $name = 'TestCachedPages';

/**
 * uses property
 *
 * @var array
 * @access public
 */
	var $uses = array();

/**
 * helpers property
 *
 * @var array
 * @access public
 */
	var $helpers = array('Cache');

/**
 * cacheAction property
 *
 * @var array
 * @access public
 */
	var $cacheAction = array(
		'index' => '+2 sec',
		'test_nocache_tags' => '+2 sec',
		'view' => '+2 sec'
	);

/**
 * viewPath property
 *
 * @var string 'posts'
 * @access public
 */
	var $viewPath = 'posts';

/**
 * index method
 *
 * @return void
 * @access public
 */
	function index() {
		$this->render();
	}

/**
 * test_nocache_tags method
 *
 * @return void
 * @access public
 */
	function test_nocache_tags() {
		$this->render();
	}

/**
 * view method
 *
 * @return void
 * @access public
 */
	function view($id = null) {
		$this->render('index');
	}
/**
 * test cached forms / tests view object being registered
 *
 * @return void
 */
	function cache_form() {
		$this->cacheAction = 10;
		$this->helpers[] = 'Form';
	}
}

/**
 * TimesheetsController class
 *
 * @package       cake
 * @subpackage    cake.tests.cases
 */
class TimesheetsController extends AppController {

/**
 * name property
 *
 * @var string 'Timesheets'
 * @access public
 */
	var $name = 'Timesheets';

/**
 * uses property
 *
 * @var array
 * @access public
 */
	var $uses = array();

/**
 * index method
 *
 * @return void
 * @access public
 */
	function index() {
		return true;
	}
}

/**
 * DispatcherTest class
 *
 * @package       cake
 * @subpackage    cake.tests.cases
 */
class DispatcherTest extends CakeTestCase {

/**
 * setUp method
 *
 * @return void
 * @access public
 */
	function startTest() {
		$this->_get = $_GET;
		$_GET = array();
		$this->_post = $_POST;
		$this->_files = $_FILES;
		$this->_server = $_SERVER;

		$this->_app = Configure::read('App');
		Configure::write('App.base', false);
		Configure::write('App.baseUrl', false);
		Configure::write('App.dir', 'app');
		Configure::write('App.webroot', 'webroot');

		$this->_cache = Configure::read('Cache');
		Configure::write('Cache.disable', true);

		$this->_debug = Configure::read('debug');

		App::build(App::core());
	}

/**
 * tearDown method
 *
 * @return void
 * @access public
 */
	function endTest() {
		$_GET = $this->_get;
		$_POST = $this->_post;
		$_FILES = $this->_files;
		$_SERVER = $this->_server;
		App::build();
		Configure::write('App', $this->_app);
		Configure::write('Cache', $this->_cache);
		Configure::write('debug', $this->_debug);
	}

/**
 * testParseParamsWithoutZerosAndEmptyPost method
 *
 * @return void
 * @access public
 */
	function testParseParamsWithoutZerosAndEmptyPost() {
		$Dispatcher =& new Dispatcher();
		$test = $Dispatcher->parseParams("/testcontroller/testaction/params1/params2/params3");
		$this->assertIdentical($test['controller'], 'testcontroller');
		$this->assertIdentical($test['action'], 'testaction');
		$this->assertIdentical($test['pass'][0], 'params1');
		$this->assertIdentical($test['pass'][1], 'params2');
		$this->assertIdentical($test['pass'][2], 'params3');
		$this->assertFalse(!empty($test['form']));
	}

/**
 * testParseParamsReturnsPostedData method
 *
 * @return void
 * @access public
 */
	function testParseParamsReturnsPostedData() {
		$_POST['testdata'] = "My Posted Content";
		$Dispatcher =& new Dispatcher();
		$test = $Dispatcher->parseParams("/");
		$this->assertTrue($test['form'], "Parsed URL not returning post data");
		$this->assertIdentical($test['form']['testdata'], "My Posted Content");
	}

/**
 * testParseParamsWithSingleZero method
 *
 * @return void
 * @access public
 */
	function testParseParamsWithSingleZero() {
		$Dispatcher =& new Dispatcher();
		$test = $Dispatcher->parseParams("/testcontroller/testaction/1/0/23");
		$this->assertIdentical($test['controller'], 'testcontroller');
		$this->assertIdentical($test['action'], 'testaction');
		$this->assertIdentical($test['pass'][0], '1');
		$this->assertPattern('/\\A(?:0)\\z/', $test['pass'][1]);
		$this->assertIdentical($test['pass'][2], '23');
	}

/**
 * testParseParamsWithManySingleZeros method
 *
 * @return void
 * @access public
 */
	function testParseParamsWithManySingleZeros() {
		$Dispatcher =& new Dispatcher();
		$test = $Dispatcher->parseParams("/testcontroller/testaction/0/0/0/0/0/0");
		$this->assertPattern('/\\A(?:0)\\z/', $test['pass'][0]);
		$this->assertPattern('/\\A(?:0)\\z/', $test['pass'][1]);
		$this->assertPattern('/\\A(?:0)\\z/', $test['pass'][2]);
		$this->assertPattern('/\\A(?:0)\\z/', $test['pass'][3]);
		$this->assertPattern('/\\A(?:0)\\z/', $test['pass'][4]);
		$this->assertPattern('/\\A(?:0)\\z/', $test['pass'][5]);
	}

/**
 * testParseParamsWithManyZerosInEachSectionOfUrl method
 *
 * @return void
 * @access public
 */
	function testParseParamsWithManyZerosInEachSectionOfUrl() {
		$Dispatcher =& new Dispatcher();
		$test = $Dispatcher->parseParams("/testcontroller/testaction/000/0000/00000/000000/000000/0000000");
		$this->assertPattern('/\\A(?:000)\\z/', $test['pass'][0]);
		$this->assertPattern('/\\A(?:0000)\\z/', $test['pass'][1]);
		$this->assertPattern('/\\A(?:00000)\\z/', $test['pass'][2]);
		$this->assertPattern('/\\A(?:000000)\\z/', $test['pass'][3]);
		$this->assertPattern('/\\A(?:000000)\\z/', $test['pass'][4]);
		$this->assertPattern('/\\A(?:0000000)\\z/', $test['pass'][5]);
	}

/**
 * testParseParamsWithMixedOneToManyZerosInEachSectionOfUrl method
 *
 * @return void
 * @access public
 */
	function testParseParamsWithMixedOneToManyZerosInEachSectionOfUrl() {
		$Dispatcher =& new Dispatcher();
		$test = $Dispatcher->parseParams("/testcontroller/testaction/01/0403/04010/000002/000030/0000400");
		$this->assertPattern('/\\A(?:01)\\z/', $test['pass'][0]);
		$this->assertPattern('/\\A(?:0403)\\z/', $test['pass'][1]);
		$this->assertPattern('/\\A(?:04010)\\z/', $test['pass'][2]);
		$this->assertPattern('/\\A(?:000002)\\z/', $test['pass'][3]);
		$this->assertPattern('/\\A(?:000030)\\z/', $test['pass'][4]);
		$this->assertPattern('/\\A(?:0000400)\\z/', $test['pass'][5]);
	}

/**
 * testQueryStringOnRoot method
 *
 * @return void
 * @access public
 */
	function testQueryStringOnRoot() {
		Router::reload();
		Router::connect('/', array('controller' => 'pages', 'action' => 'display', 'home'));
		Router::connect('/pages/*', array('controller' => 'pages', 'action' => 'display'));

		$_GET = array('coffee' => 'life', 'sleep' => 'sissies');
		$Dispatcher =& new Dispatcher();
		$uri = 'posts/home/?coffee=life&sleep=sissies';
		$result = $Dispatcher->parseParams($uri);
		$this->assertPattern('/posts/', $result['controller']);
		$this->assertPattern('/home/', $result['action']);
		$this->assertTrue(isset($result['url']['sleep']));
		$this->assertTrue(isset($result['url']['coffee']));

		$Dispatcher =& new Dispatcher();
		$uri = '/?coffee=life&sleep=sissy';
		$result = $Dispatcher->parseParams($uri);
		$this->assertPattern('/pages/', $result['controller']);
		$this->assertPattern('/display/', $result['action']);
		$this->assertTrue(isset($result['url']['sleep']));
		$this->assertTrue(isset($result['url']['coffee']));
		$this->assertEqual($result['url']['coffee'], 'life');
	}

/**
 * testFileUploadArrayStructure method
 *
 * @return void
 * @access public
 */
	function testFileUploadArrayStructure() {
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

		$Dispatcher =& new Dispatcher();
		$result = $Dispatcher->parseParams('/');

		$expected = array(
			'File' => array(
				array('data' => array(
					'name' => 'cake_mssql_patch.patch',
					'type' => '',
					'tmp_name' => '/private/var/tmp/phpy05Ywj',
					'error' => 0,
					'size' => 6271,
				),
			),
			array('data' => array(
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
		)));
		$this->assertEqual($result['data'], $expected);

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
		$Dispatcher =& new Dispatcher();
		$result = $Dispatcher->parseParams('/');
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
		$this->assertEqual($result['data'], $expected);


		$_FILES = array(
			'data' => array(
				'name' => array('birth_cert' => 'born on.txt'),
				'type' => array('birth_cert' => 'application/octet-stream'),
				'tmp_name' => array('birth_cert' => '/private/var/tmp/phpbsUWfH'),
				'error' => array('birth_cert' => 0),
				'size' => array('birth_cert' => 123)
			)
		);

		$Dispatcher =& new Dispatcher();
		$result = $Dispatcher->parseParams('/');

		$expected = array(
			'birth_cert' => array(
				'name' => 'born on.txt',
				'type' => 'application/octet-stream',
				'tmp_name' => '/private/var/tmp/phpbsUWfH',
				'error' => 0,
				'size' => 123
			)
		);

		$this->assertEqual($result['data'], $expected);
	}

/**
 * testGetUrl method
 *
 * @return void
 * @access public
 */
	function testGetUrl() {
		$Dispatcher =& new Dispatcher();
		$Dispatcher->base = '/app/webroot/index.php';
		$uri = '/app/webroot/index.php/posts/add';
		$result = $Dispatcher->getUrl($uri);
		$expected = 'posts/add';
		$this->assertEqual($expected, $result);

		Configure::write('App.baseUrl', '/app/webroot/index.php');

		$uri = '/posts/add';
		$result = $Dispatcher->getUrl($uri);
		$expected = 'posts/add';
		$this->assertEqual($expected, $result);

		$_GET['url'] = array();
		Configure::write('App.base', '/control');
		$Dispatcher =& new Dispatcher();
		$Dispatcher->baseUrl();
		$uri = '/control/students/browse';
		$result = $Dispatcher->getUrl($uri);
		$expected = 'students/browse';
		$this->assertEqual($expected, $result);

		$_GET['url'] = array();
		$Dispatcher =& new Dispatcher();
		$Dispatcher->base = '';
		$uri = '/?/home';
		$result = $Dispatcher->getUrl($uri);
		$expected = '?/home';
		$this->assertEqual($expected, $result);

		$_GET['url'] = array();
		$Dispatcher =& new Dispatcher();
		$Dispatcher->base = '/shop';
		$uri = '/shop/fr/pages/shop';
		$result = $Dispatcher->getUrl($uri);
		$expected = 'fr/pages/shop';
		$this->assertEqual($expected, $result);
	}

/**
 * testBaseUrlAndWebrootWithModRewrite method
 *
 * @return void
 * @access public
 */
	function testBaseUrlAndWebrootWithModRewrite() {
		$Dispatcher =& new Dispatcher();

		$Dispatcher->base = false;
		$_SERVER['DOCUMENT_ROOT'] = '/cake/repo/branches';
		$_SERVER['SCRIPT_FILENAME'] = '/cake/repo/branches/1.2.x.x/app/webroot/index.php';
		$_SERVER['PHP_SELF'] = '/1.2.x.x/app/webroot/index.php';
		$result = $Dispatcher->baseUrl();
		$expected = '/1.2.x.x';
		$this->assertEqual($expected, $result);
		$expectedWebroot = '/1.2.x.x/';
		$this->assertEqual($expectedWebroot, $Dispatcher->webroot);

		$Dispatcher->base = false;
		$_SERVER['DOCUMENT_ROOT'] = '/cake/repo/branches/1.2.x.x/app/webroot';
		$_SERVER['SCRIPT_FILENAME'] = '/cake/repo/branches/1.2.x.x/app/webroot/index.php';
		$_SERVER['PHP_SELF'] = '/index.php';
		$result = $Dispatcher->baseUrl();
		$expected = '';
		$this->assertEqual($expected, $result);
		$expectedWebroot = '/';
		$this->assertEqual($expectedWebroot, $Dispatcher->webroot);

		$Dispatcher->base = false;
		$_SERVER['DOCUMENT_ROOT'] = '/cake/repo/branches/1.2.x.x/test/';
		$_SERVER['SCRIPT_FILENAME'] = '/cake/repo/branches/1.2.x.x/test/webroot/index.php';
		$_SERVER['PHP_SELF'] = '/webroot/index.php';
		$result = $Dispatcher->baseUrl();
		$expected = '';
		$this->assertEqual($expected, $result);
		$expectedWebroot = '/';
		$this->assertEqual($expectedWebroot, $Dispatcher->webroot);

		$Dispatcher->base = false;
		$_SERVER['DOCUMENT_ROOT'] = '/some/apps/where';
		$_SERVER['SCRIPT_FILENAME'] = '/some/apps/where/app/webroot/index.php';
		$_SERVER['PHP_SELF'] = '/some/apps/where/app/webroot/index.php';
		$result = $Dispatcher->baseUrl();
		$expected = '/some/apps/where';
		$this->assertEqual($expected, $result);
		$expectedWebroot = '/some/apps/where/';
		$this->assertEqual($expectedWebroot, $Dispatcher->webroot);


		Configure::write('App.dir', 'auth');

		$Dispatcher->base = false;
		$_SERVER['DOCUMENT_ROOT'] = '/cake/repo/branches';
		$_SERVER['SCRIPT_FILENAME'] = '/cake/repo/branches/demos/auth/webroot/index.php';
		$_SERVER['PHP_SELF'] = '/demos/auth/webroot/index.php';

		$result = $Dispatcher->baseUrl();
		$expected = '/demos/auth';
		$this->assertEqual($expected, $result);
		$expectedWebroot = '/demos/auth/';
		$this->assertEqual($expectedWebroot, $Dispatcher->webroot);

		Configure::write('App.dir', 'code');

		$Dispatcher->base = false;
		$_SERVER['DOCUMENT_ROOT'] = '/Library/WebServer/Documents';
		$_SERVER['SCRIPT_FILENAME'] = '/Library/WebServer/Documents/clients/PewterReport/code/webroot/index.php';
		$_SERVER['PHP_SELF'] = '/clients/PewterReport/code/webroot/index.php';
		$result = $Dispatcher->baseUrl();
		$expected = '/clients/PewterReport/code';
		$this->assertEqual($expected, $result);
		$expectedWebroot = '/clients/PewterReport/code/';
		$this->assertEqual($expectedWebroot, $Dispatcher->webroot);
	}

/**
 * testBaseUrlwithModRewriteAlias method
 *
 * @return void
 * @access public
 */
	function testBaseUrlwithModRewriteAlias() {
		$_SERVER['DOCUMENT_ROOT'] = '/home/aplusnur/public_html';
		$_SERVER['SCRIPT_FILENAME'] = '/home/aplusnur/cake2/app/webroot/index.php';
		$_SERVER['PHP_SELF'] = '/control/index.php';

		Configure::write('App.base', '/control');

		$Dispatcher =& new Dispatcher();
		$result = $Dispatcher->baseUrl();
		$expected = '/control';
		$this->assertEqual($expected, $result);
		$expectedWebroot = '/control/';
		$this->assertEqual($expectedWebroot, $Dispatcher->webroot);

		Configure::write('App.base', false);
		Configure::write('App.dir', 'affiliate');
		Configure::write('App.webroot', 'newaffiliate');

		$_SERVER['DOCUMENT_ROOT'] = '/var/www/abtravaff/html';
		$_SERVER['SCRIPT_FILENAME'] = '/var/www/abtravaff/html/newaffiliate/index.php';
		$_SERVER['PHP_SELF'] = '/newaffiliate/index.php';
		$Dispatcher =& new Dispatcher();
		$result = $Dispatcher->baseUrl();
		$expected = '/newaffiliate';
		$this->assertEqual($expected, $result);
		$expectedWebroot = '/newaffiliate/';
		$this->assertEqual($expectedWebroot, $Dispatcher->webroot);
	}

/**
 * testBaseUrlAndWebrootWithBaseUrl method
 *
 * @return void
 * @access public
 */
	function testBaseUrlAndWebrootWithBaseUrl() {
		$Dispatcher =& new Dispatcher();

		Configure::write('App.dir', 'app');

		Configure::write('App.baseUrl', '/app/webroot/index.php');
		$result = $Dispatcher->baseUrl();
		$expected = '/app/webroot/index.php';
		$this->assertEqual($expected, $result);
		$expectedWebroot = '/app/webroot/';
		$this->assertEqual($expectedWebroot, $Dispatcher->webroot);

		Configure::write('App.baseUrl', '/app/webroot/test.php');
		$result = $Dispatcher->baseUrl();
		$expected = '/app/webroot/test.php';
		$this->assertEqual($expected, $result);
		$expectedWebroot = '/app/webroot/';
		$this->assertEqual($expectedWebroot, $Dispatcher->webroot);

		Configure::write('App.baseUrl', '/app/index.php');
		$result = $Dispatcher->baseUrl();
		$expected = '/app/index.php';
		$this->assertEqual($expected, $result);
		$expectedWebroot = '/app/webroot/';
		$this->assertEqual($expectedWebroot, $Dispatcher->webroot);

		Configure::write('App.baseUrl', '/index.php');
		$result = $Dispatcher->baseUrl();
		$expected = '/index.php';
		$this->assertEqual($expected, $result);
		$expectedWebroot = '/app/webroot/';
		$this->assertEqual($expectedWebroot, $Dispatcher->webroot);

		Configure::write('App.baseUrl', '/CakeBB/app/webroot/index.php');
		$result = $Dispatcher->baseUrl();
		$expected = '/CakeBB/app/webroot/index.php';
		$this->assertEqual($expected, $result);
		$expectedWebroot = '/CakeBB/app/webroot/';
		$this->assertEqual($expectedWebroot, $Dispatcher->webroot);

		Configure::write('App.baseUrl', '/CakeBB/app/index.php');
		$result = $Dispatcher->baseUrl();
		$expected = '/CakeBB/app/index.php';
		$this->assertEqual($expected, $result);
		$expectedWebroot = '/CakeBB/app/webroot/';
		$this->assertEqual($expectedWebroot, $Dispatcher->webroot);

		Configure::write('App.baseUrl', '/CakeBB/index.php');
		$result = $Dispatcher->baseUrl();
		$expected = '/CakeBB/index.php';
		$this->assertEqual($expected, $result);
		$expectedWebroot = '/CakeBB/app/webroot/';
		$this->assertEqual($expectedWebroot, $Dispatcher->webroot);

		Configure::write('App.baseUrl', '/dbhauser/index.php');
		$_SERVER['DOCUMENT_ROOT'] = '/kunden/homepages/4/d181710652/htdocs/joomla';
		$_SERVER['SCRIPT_FILENAME'] = '/kunden/homepages/4/d181710652/htdocs/joomla/dbhauser/index.php';
		$result = $Dispatcher->baseUrl();
		$expected = '/dbhauser/index.php';
		$this->assertEqual($expected, $result);
		$expectedWebroot = '/dbhauser/app/webroot/';
		$this->assertEqual($expectedWebroot, $Dispatcher->webroot);
	}

/**
 * Check that a sub-directory containing app|webroot doesn't get mishandled when re-writing is off.
 *
 * @return void
 */
	function testBaseUrlWithAppAndWebrootInDirname() {
		Configure::write('App.baseUrl', '/approval/index.php');
		$_SERVER['DOCUMENT_ROOT'] = '/Users/markstory/Sites/';
		$_SERVER['SCRIPT_FILENAME'] = '/Users/markstory/Sites/approval/index.php';
		$Dispatcher =& new Dispatcher();
		$result = $Dispatcher->baseUrl();

		$this->assertEqual('/approval/index.php', $result);
		$this->assertEqual('/approval/app/webroot/', $Dispatcher->webroot);

		Configure::write('App.baseUrl', '/webrootable/index.php');
		$_SERVER['DOCUMENT_ROOT'] = '/Users/markstory/Sites/';
		$_SERVER['SCRIPT_FILENAME'] = '/Users/markstory/Sites/webrootable/index.php';
		$Dispatcher =& new Dispatcher();
		$result = $Dispatcher->baseUrl();

		$this->assertEqual('/webrootable/index.php', $result);
		$this->assertEqual('/webrootable/app/webroot/', $Dispatcher->webroot);
	}

/**
 * test baseUrl with no rewrite and using the top level index.php.
 *
 * @return void
 */
	function testBaseUrlNoRewriteTopLevelIndex() {
		$Dispatcher =& new Dispatcher();

		Configure::write('App.baseUrl', '/index.php');
		$_SERVER['DOCUMENT_ROOT'] = '/Users/markstory/Sites/cake_dev';
		$_SERVER['SCRIPT_FILENAME'] = '/Users/markstory/Sites/cake_dev/index.php';

		$result = $Dispatcher->baseUrl();
		$this->assertEqual('/index.php', $result);
		$this->assertEqual('/app/webroot/', $Dispatcher->webroot);
		$this->assertEqual('', $Dispatcher->base);
	}

/**
 * test baseUrl with no rewrite, and using the app/webroot/index.php file as is normal with virtual hosts.
 *
 * @return void
 */
	function testBaseUrlNoRewriteWebrootIndex() {
		$Dispatcher =& new Dispatcher();

		Configure::write('App.baseUrl', '/index.php');
		$_SERVER['DOCUMENT_ROOT'] = '/Users/markstory/Sites/cake_dev/app/webroot';
		$_SERVER['SCRIPT_FILENAME'] = '/Users/markstory/Sites/cake_dev/app/webroot/index.php';

		$result = $Dispatcher->baseUrl();
		$this->assertEqual('/index.php', $result);
		$this->assertEqual('/', $Dispatcher->webroot);
		$this->assertEqual('', $Dispatcher->base);
	}

/**
 * testBaseUrlAndWebrootWithBase method
 *
 * @return void
 * @access public
 */
	function testBaseUrlAndWebrootWithBase() {
		$Dispatcher =& new Dispatcher();
		$Dispatcher->base = '/app';
		$result = $Dispatcher->baseUrl();
		$expected = '/app';
		$this->assertEqual($expected, $result);
		$expectedWebroot = '/app/';
		$this->assertEqual($expectedWebroot, $Dispatcher->webroot);

		$Dispatcher->base = '';
		$result = $Dispatcher->baseUrl();
		$expected = '';
		$this->assertEqual($expected, $result);
		$expectedWebroot = '/';
		$this->assertEqual($expectedWebroot, $Dispatcher->webroot);

		Configure::write('App.dir', 'testbed');
		$Dispatcher->base = '/cake/testbed/webroot';
		$result = $Dispatcher->baseUrl();
		$expected = '/cake/testbed/webroot';
		$this->assertEqual($expected, $result);
		$expectedWebroot = '/cake/testbed/webroot/';
		$this->assertEqual($expectedWebroot, $Dispatcher->webroot);
	}

/**
 * testMissingController method
 *
 * @return void
 * @access public
 */
	function testMissingController() {
		$Dispatcher =& new TestDispatcher();
		Configure::write('App.baseUrl', '/index.php');
		$url = 'some_controller/home/param:value/param2:value2';
		$controller = $Dispatcher->dispatch($url, array('return' => 1));
		$expected = array('missingController', array(array(
			'className' => 'SomeControllerController',
			'webroot' => '/app/webroot/',
			'url' => 'some_controller/home/param:value/param2:value2',
			'base' => '/index.php'
		)));
		$this->assertEqual($expected, $controller);
	}

/**
 * testPrivate method
 *
 * @return void
 * @access public
 */
	function testPrivate() {
		$Dispatcher =& new TestDispatcher();
		Configure::write('App.baseUrl','/index.php');
		$url = 'some_pages/_protected/param:value/param2:value2';

		$controller = $Dispatcher->dispatch($url, array('return' => 1));

		$expected = array('privateAction', array(array(
			'className' => 'SomePagesController',
			'action' => '_protected',
			'webroot' => '/app/webroot/',
			'url' => 'some_pages/_protected/param:value/param2:value2',
			'base' => '/index.php'
		)));
		$this->assertEqual($controller, $expected);
	}

/**
 * testMissingAction method
 *
 * @return void
 * @access public
 */
	function testMissingAction() {
		$Dispatcher =& new TestDispatcher();
		Configure::write('App.baseUrl', '/index.php');
		$url = 'some_pages/home/param:value/param2:value2';

		$controller = $Dispatcher->dispatch($url, array('return'=> 1));

		$expected = array('missingAction', array(array(
			'className' => 'SomePagesController',
			'action' => 'home',
			'webroot' => '/app/webroot/',
			'url' => '/index.php/some_pages/home/param:value/param2:value2',
			'base' => '/index.php'
		)));
		$this->assertEqual($expected, $controller);

		$Dispatcher =& new TestDispatcher();
		Configure::write('App.baseUrl','/index.php');
		$url = 'some_pages/redirect/param:value/param2:value2';

		$controller = $Dispatcher->dispatch($url, array('return'=> 1));

		$expected = array('missingAction', array(array(
			'className' => 'SomePagesController',
			'action' => 'redirect',
			'webroot' => '/app/webroot/',
			'url' => '/index.php/some_pages/redirect/param:value/param2:value2',
			'base' => '/index.php'
		)));
		$this->assertEqual($expected, $controller);
	}

/**
 * testDispatch method
 *
 * @return void
 * @access public
 */
	function testDispatch() {
		$Dispatcher =& new TestDispatcher();
		Configure::write('App.baseUrl','/index.php');
		$url = 'pages/home/param:value/param2:value2';

		$controller = $Dispatcher->dispatch($url, array('return' => 1));
		$expected = 'Pages';
		$this->assertEqual($expected, $controller->name);

		$expected = array('0' => 'home', 'param' => 'value', 'param2' => 'value2');
		$this->assertIdentical($expected, $controller->passedArgs);

		Configure::write('App.baseUrl','/pages/index.php');

		$url = 'pages/home';
		$controller = $Dispatcher->dispatch($url, array('return' => 1));

		$expected = 'Pages';
		$this->assertEqual($expected, $controller->name);

		$url = 'pages/home/';
		$controller = $Dispatcher->dispatch($url, array('return' => 1));
		$this->assertNull($controller->plugin);
		$this->assertNull($Dispatcher->params['plugin']);

		$expected = 'Pages';
		$this->assertEqual($expected, $controller->name);

		unset($Dispatcher);

		$Dispatcher =& new TestDispatcher();
		Configure::write('App.baseUrl','/timesheets/index.php');

		$url = 'timesheets';
		$controller = $Dispatcher->dispatch($url, array('return' => 1));

		$expected = 'Timesheets';
		$this->assertEqual($expected, $controller->name);

		$url = 'timesheets/';
		$controller = $Dispatcher->dispatch($url, array('return' => 1));

		$this->assertEqual('Timesheets', $controller->name);
		$this->assertEqual('/timesheets/index.php', $Dispatcher->base);


		$url = 'test_dispatch_pages/camelCased';
		$controller = $Dispatcher->dispatch($url, array('return' => 1));
		$this->assertEqual('TestDispatchPages', $controller->name);

		$url = 'test_dispatch_pages/camelCased/something. .';
		$controller = $Dispatcher->dispatch($url, array('return' => 1));
		$this->assertEqual($controller->params['pass'][0], 'something. .', 'Period was chopped off. %s');

	}

/**
 * testDispatchWithArray method
 *
 * @return void
 * @access public
 */
	function testDispatchWithArray() {
		$Dispatcher =& new TestDispatcher();
		$url = 'pages/home/param:value/param2:value2';

		$url = array('controller' => 'pages', 'action' => 'display');
		$controller = $Dispatcher->dispatch($url, array(
			'pass' => array('home'),
			'named' => array('param' => 'value', 'param2' => 'value2'),
			'return' => 1
		));
		$expected = 'Pages';
		$this->assertEqual($expected, $controller->name);

		$expected = array('0' => 'home', 'param' => 'value', 'param2' => 'value2');
		$this->assertIdentical($expected, $controller->passedArgs);

		$this->assertEqual($Dispatcher->base . '/pages/display/home/param:value/param2:value2', $Dispatcher->here);
	}

/**
 * test that a garbage url doesn't cause errors.
 *
 * @return void
 */
	function testDispatchWithGarbageUrl() {
		Configure::write('App.baseUrl', '/index.php');

		$Dispatcher =& new TestDispatcher();
		$url = 'http://google.com';
		$result = $Dispatcher->dispatch($url);
		$expected = array('missingController', array(array(
			'className' => 'Controller',
			'webroot' => '/app/webroot/',
			'url' => 'http://google.com',
			'base' => '/index.php'
		)));
		$this->assertEqual($expected, $result);
	}

/**
 * testAdminDispatch method
 *
 * @return void
 * @access public
 */
	function testAdminDispatch() {
		$_POST = array();
		$Dispatcher =& new TestDispatcher();
		Configure::write('Routing.prefixes', array('admin'));
		Configure::write('App.baseUrl','/cake/repo/branches/1.2.x.x/index.php');
		$url = 'admin/test_dispatch_pages/index/param:value/param2:value2';

		Router::reload();
		$Router =& Router::getInstance();
		$controller = $Dispatcher->dispatch($url, array('return' => 1));

		$this->assertEqual($controller->name, 'TestDispatchPages');

		$this->assertIdentical($controller->passedArgs, array('param' => 'value', 'param2' => 'value2'));
		$this->assertTrue($controller->params['admin']);

		$expected = '/cake/repo/branches/1.2.x.x/index.php/admin/test_dispatch_pages/index/param:value/param2:value2';
		$this->assertIdentical($expected, $controller->here);

		$expected = '/cake/repo/branches/1.2.x.x/index.php';
		$this->assertIdentical($expected, $controller->base);
	}

/**
 * testPluginDispatch method
 *
 * @return void
 * @access public
 */
	function testPluginDispatch() {
		$_POST = array();
		$_SERVER['PHP_SELF'] = '/cake/repo/branches/1.2.x.x/index.php';

		Router::reload();
		$Dispatcher =& new TestDispatcher();
		Router::connect(
			'/my_plugin/:controller/*',
			array('plugin' => 'my_plugin', 'controller' => 'pages', 'action' => 'display')
		);

		$Dispatcher->base = false;
		$url = 'my_plugin/some_pages/home/param:value/param2:value2';
		$controller = $Dispatcher->dispatch($url, array('return' => 1));

		$result = $Dispatcher->parseParams($url);
		$expected = array(
			'pass' => array('home'),
			'named' => array('param'=> 'value', 'param2'=> 'value2'), 'plugin'=> 'my_plugin',
			'controller'=> 'some_pages', 'action'=> 'display', 'form'=> null,
			'url'=> array('url'=> 'my_plugin/some_pages/home/param:value/param2:value2'),
		);
		ksort($expected);
		ksort($result);

		$this->assertEqual($expected, $result);

		$this->assertIdentical($controller->plugin, 'my_plugin');
		$this->assertIdentical($controller->name, 'SomePages');
		$this->assertIdentical($controller->params['controller'], 'some_pages');
		$this->assertIdentical($controller->passedArgs, array('0' => 'home', 'param'=>'value', 'param2'=>'value2'));

		$expected = '/cake/repo/branches/1.2.x.x/my_plugin/some_pages/home/param:value/param2:value2';
		$this->assertIdentical($expected, $controller->here);

		$expected = '/cake/repo/branches/1.2.x.x';
		$this->assertIdentical($expected, $controller->base);
	}

/**
 * testAutomaticPluginDispatch method
 *
 * @return void
 * @access public
 */
	function testAutomaticPluginDispatch() {
		$_POST = array();
		$_SERVER['PHP_SELF'] = '/cake/repo/branches/1.2.x.x/index.php';

		Router::reload();
		$Dispatcher =& new TestDispatcher();
		Router::connect(
			'/my_plugin/:controller/:action/*',
			array('plugin' => 'my_plugin', 'controller' => 'pages', 'action' => 'display')
		);

		$Dispatcher->base = false;

		$url = 'my_plugin/other_pages/index/param:value/param2:value2';
		$controller = $Dispatcher->dispatch($url, array('return' => 1));

		$this->assertIdentical($controller->plugin, 'my_plugin');
		$this->assertIdentical($controller->name, 'OtherPages');
		$this->assertIdentical($controller->action, 'index');
		$this->assertIdentical($controller->passedArgs, array('param' => 'value', 'param2' => 'value2'));

		$expected = '/cake/repo/branches/1.2.x.x/my_plugin/other_pages/index/param:value/param2:value2';
		$this->assertIdentical($expected, $controller->here);

		$expected = '/cake/repo/branches/1.2.x.x';
		$this->assertIdentical($expected, $controller->base);
	}

/**
 * testAutomaticPluginControllerDispatch method
 *
 * @return void
 * @access public
 */
	function testAutomaticPluginControllerDispatch() {
		$_POST = array();
		$_SERVER['PHP_SELF'] = '/cake/repo/branches/1.2.x.x/index.php';

		$plugins = App::objects('plugin');
		$plugins[] = 'MyPlugin';
		$plugins[] = 'ArticlesTest';

		$app = App::getInstance();
		$app->__objects['plugin'] = $plugins;

		Router::reload();
		$Dispatcher =& new TestDispatcher();
		$Dispatcher->base = false;

		$url = 'my_plugin/my_plugin/add/param:value/param2:value2';

		$controller = $Dispatcher->dispatch($url, array('return' => 1));

		$this->assertIdentical($controller->plugin, 'my_plugin');
		$this->assertIdentical($controller->name, 'MyPlugin');
		$this->assertIdentical($controller->action, 'add');
		$this->assertEqual($controller->params['named'], array('param' => 'value', 'param2' => 'value2'));


		Router::reload();
		$Dispatcher =& new TestDispatcher();
		$Dispatcher->base = false;

		// Simulates the Route for a real plugin, installed in APP/plugins
		Router::connect('/my_plugin/:controller/:action/*', array('plugin' => 'my_plugin'));

		$plugin = 'MyPlugin';
		$pluginUrl = Inflector::underscore($plugin);

		$url = $pluginUrl;
		$controller = $Dispatcher->dispatch($url, array('return' => 1));
		$this->assertIdentical($controller->plugin, 'my_plugin');
		$this->assertIdentical($controller->name, 'MyPlugin');
		$this->assertIdentical($controller->action, 'index');

		$expected = $pluginUrl;
		$this->assertEqual($controller->params['controller'], $expected);


		Configure::write('Routing.prefixes', array('admin'));

		Router::reload();
		$Dispatcher =& new TestDispatcher();
		$Dispatcher->base = false;

		$url = 'admin/my_plugin/my_plugin/add/5/param:value/param2:value2';
		$controller = $Dispatcher->dispatch($url, array('return' => 1));

		$this->assertEqual($controller->params['plugin'], 'my_plugin');
		$this->assertEqual($controller->params['controller'], 'my_plugin');
		$this->assertEqual($controller->params['action'], 'admin_add');
		$this->assertEqual($controller->params['pass'], array(5));
		$this->assertEqual($controller->params['named'], array('param' => 'value', 'param2' => 'value2'));
		$this->assertIdentical($controller->plugin, 'my_plugin');
		$this->assertIdentical($controller->name, 'MyPlugin');
		$this->assertIdentical($controller->action, 'admin_add');

		$expected = array(0 => 5, 'param'=>'value', 'param2'=>'value2');
		$this->assertEqual($controller->passedArgs, $expected);

		Configure::write('Routing.prefixes', array('admin'));
		Router::reload();

		$Dispatcher =& new TestDispatcher();
		$Dispatcher->base = false;

		$controller = $Dispatcher->dispatch('admin/articles_test', array('return' => 1));
		$this->assertIdentical($controller->plugin, 'articles_test');
		$this->assertIdentical($controller->name, 'ArticlesTest');
		$this->assertIdentical($controller->action, 'admin_index');

		$expected = array(
			'pass'=> array(),
			'named' => array(),
			'controller' => 'articles_test',
			'plugin' => 'articles_test',
			'action' => 'admin_index',
			'prefix' => 'admin',
			'admin' =>  true,
			'form' => array(),
			'url' => array('url' => 'admin/articles_test'),
			'return' => 1
		);
		$this->assertEqual($controller->params, $expected);
	}

/**
 * test Plugin dispatching without controller name and using
 * plugin short form instead.
 *
 * @return void
 * @access public
 */
	function testAutomaticPluginDispatchWithShortAccess() {
		$_POST = array();
		$_SERVER['PHP_SELF'] = '/cake/repo/branches/1.2.x.x/index.php';
		$plugins = App::objects('plugin');
		$plugins[] = 'MyPlugin';

		$app = App::getInstance();
		$app->__objects['plugin'] = $plugins;

		Router::reload();

		$Dispatcher =& new TestDispatcher();
		$Dispatcher->base = false;

		$url = 'my_plugin/';
		$controller = $Dispatcher->dispatch($url, array('return' => 1));
		$this->assertEqual($controller->params['controller'], 'my_plugin');
		$this->assertEqual($controller->params['plugin'], 'my_plugin');
		$this->assertEqual($controller->params['action'], 'index');
		$this->assertFalse(isset($controller->params['pass'][0]));
	}

/**
 * test plugin shortcut urls with controllers that need to be loaded,
 * the above test uses a controller that has already been included.
 *
 * @return void
 */
	function testPluginShortCutUrlsWithControllerThatNeedsToBeLoaded() {
		$loaded = class_exists('TestPluginController', false);
		if ($this->skipIf($loaded, 'TestPluginController already loaded, this test will always pass, skipping %s')) {
			return true;
		}
		Router::reload();
		App::build(array(
			'plugins' => array(TEST_CAKE_CORE_INCLUDE_PATH . 'tests' . DS . 'test_app' . DS . 'plugins' . DS)
		), true);
		App::objects('plugin', null, false);

		$Dispatcher =& new TestDispatcher();
		$Dispatcher->base = false;

		$url = 'test_plugin/';
		$controller = $Dispatcher->dispatch($url, array('return' => 1));
		$this->assertEqual($controller->params['controller'], 'test_plugin');
		$this->assertEqual($controller->params['plugin'], 'test_plugin');
		$this->assertEqual($controller->params['action'], 'index');
		$this->assertFalse(isset($controller->params['pass'][0]));

		$url = '/test_plugin/tests/index';
		$controller = $Dispatcher->dispatch($url, array('return' => 1));
		$this->assertEqual($controller->params['controller'], 'tests');
		$this->assertEqual($controller->params['plugin'], 'test_plugin');
		$this->assertEqual($controller->params['action'], 'index');
		$this->assertFalse(isset($controller->params['pass'][0]));

		$url = '/test_plugin/tests/index/some_param';
		$controller = $Dispatcher->dispatch($url, array('return' => 1));
		$this->assertEqual($controller->params['controller'], 'tests');
		$this->assertEqual($controller->params['plugin'], 'test_plugin');
		$this->assertEqual($controller->params['action'], 'index');
		$this->assertEqual($controller->params['pass'][0], 'some_param');

		App::build();
	}

/**
 * testAutomaticPluginControllerMissingActionDispatch method
 *
 * @return void
 * @access public
 */
	function testAutomaticPluginControllerMissingActionDispatch() {
		$_POST = array();
		$_SERVER['PHP_SELF'] = '/cake/repo/branches/1.2.x.x/index.php';

		Router::reload();
		$Dispatcher =& new TestDispatcher();
		$Dispatcher->base = false;

		$url = 'my_plugin/not_here/param:value/param2:value2';
		$controller = $Dispatcher->dispatch($url, array('return'=> 1));

		$expected = array('missingAction', array(array(
			'className' => 'MyPluginController',
			'action' => 'not_here',
			'webroot' => '/cake/repo/branches/1.2.x.x/',
			'url' => '/cake/repo/branches/1.2.x.x/my_plugin/not_here/param:value/param2:value2',
			'base' => '/cake/repo/branches/1.2.x.x'
		)));
		$this->assertIdentical($expected, $controller);

		Router::reload();
		$Dispatcher =& new TestDispatcher();
		$Dispatcher->base = false;

		$url = 'my_plugin/param:value/param2:value2';
		$controller = $Dispatcher->dispatch($url, array('return'=> 1));

		$expected = array('missingAction', array(array(
			'className' => 'MyPluginController',
			'action' => 'param:value',
			'webroot' => '/cake/repo/branches/1.2.x.x/',
			'url' => '/cake/repo/branches/1.2.x.x/my_plugin/param:value/param2:value2',
			'base' => '/cake/repo/branches/1.2.x.x'
		)));
		$this->assertIdentical($expected, $controller);
	}

/**
 * testPrefixProtection method
 *
 * @return void
 * @access public
 */
	function testPrefixProtection() {
		$_POST = array();
		$_SERVER['PHP_SELF'] = '/cake/repo/branches/1.2.x.x/index.php';

		Router::reload();
		Router::connect('/admin/:controller/:action/*', array('prefix'=>'admin'), array('controller', 'action'));

		$Dispatcher =& new TestDispatcher();
		$Dispatcher->base = false;

		$url = 'test_dispatch_pages/admin_index/param:value/param2:value2';
		$controller = $Dispatcher->dispatch($url, array('return' => 1));

		$expected = array('privateAction', array(array(
			'className' => 'TestDispatchPagesController',
			'action' => 'admin_index',
			'webroot' => '/cake/repo/branches/1.2.x.x/',
			'url' => 'test_dispatch_pages/admin_index/param:value/param2:value2',
			'base' => '/cake/repo/branches/1.2.x.x'
		)));
		$this->assertIdentical($expected, $controller);
	}

/**
 * Test dispatching into the TestPlugin in the test_app
 *
 * @return void
 * @access public
 */
	function testTestPluginDispatch() {
		$Dispatcher =& new TestDispatcher();
		App::build(array(
			'plugins' => array(TEST_CAKE_CORE_INCLUDE_PATH . 'tests' . DS . 'test_app' . DS . 'plugins' . DS)
		));
		App::objects('plugin', null, false);
		Router::reload();
		Router::parse('/');

		$url = '/test_plugin/tests/index';
		$result = $Dispatcher->dispatch($url, array('return' => 1));
		$this->assertTrue(class_exists('TestsController'));
		$this->assertTrue(class_exists('TestPluginAppController'));
		$this->assertTrue(class_exists('OtherComponentComponent'));
		$this->assertTrue(class_exists('PluginsComponentComponent'));

		$this->assertEqual($result->params['controller'], 'tests');
		$this->assertEqual($result->params['plugin'], 'test_plugin');
		$this->assertEqual($result->params['action'], 'index');

		App::build();
	}

/**
 * testChangingParamsFromBeforeFilter method
 *
 * @return void
 * @access public
 */
	function testChangingParamsFromBeforeFilter() {
		$_SERVER['PHP_SELF'] = '/cake/repo/branches/1.2.x.x/index.php';
		$Dispatcher =& new TestDispatcher();
		$url = 'some_posts/index/param:value/param2:value2';
		$controller = $Dispatcher->dispatch($url, array('return' => 1));

		$expected = array('missingAction', array(array(
			'className' => 'SomePostsController',
			'action' => 'view',
			'webroot' => '/cake/repo/branches/1.2.x.x/',
			'url' => '/cake/repo/branches/1.2.x.x/some_posts/index/param:value/param2:value2',
			'base' => '/cake/repo/branches/1.2.x.x'
		)));
		$this->assertEqual($expected, $controller);

		$url = 'some_posts/something_else/param:value/param2:value2';
		$controller = $Dispatcher->dispatch($url, array('return' => 1));

		$expected = 'SomePosts';
		$this->assertEqual($expected, $controller->name);

		$expected = 'change';
		$this->assertEqual($expected, $controller->action);

		$expected = array('changed');
		$this->assertIdentical($expected, $controller->params['pass']);
	}

/**
 * testStaticAssets method
 *
 * @return void
 * @access public
 */
	function testAssets() {
		Router::reload();
		$Configure =& Configure::getInstance();
		$Configure->__objects = null;

		App::build(array(
			'plugins' => array(TEST_CAKE_CORE_INCLUDE_PATH . 'tests' . DS . 'test_app' . DS . 'plugins' . DS),
			'vendors' => array(TEST_CAKE_CORE_INCLUDE_PATH . 'tests' . DS . 'test_app' . DS . 'vendors'. DS),
			'views' => array(TEST_CAKE_CORE_INCLUDE_PATH . 'tests' . DS . 'test_app' . DS . 'views'. DS)
		));

		$Dispatcher =& new TestDispatcher();
		$debug = Configure::read('debug');
		Configure::write('debug', 0);

		ob_start();
		$Dispatcher->dispatch('theme/test_theme/../webroot/css/test_asset.css');
		$result = ob_get_clean();
		$this->assertFalse($result);

		ob_start();
		$Dispatcher->dispatch('theme/test_theme/pdfs');
		$result = ob_get_clean();
		$this->assertFalse($result);

		ob_start();
		$Dispatcher->dispatch('theme/test_theme/flash/theme_test.swf');
		$result = ob_get_clean();
		$file = file_get_contents(TEST_CAKE_CORE_INCLUDE_PATH . 'tests' . DS . 'test_app' . DS . 'views' . DS . 'themed' . DS . 'test_theme' . DS . 'webroot' . DS . 'flash' . DS . 'theme_test.swf');
		$this->assertEqual($file, $result);
		$this->assertEqual('this is just a test to load swf file from the theme.', $result);

		ob_start();
		$Dispatcher->dispatch('theme/test_theme/pdfs/theme_test.pdf');
		$result = ob_get_clean();
		$file = file_get_contents(TEST_CAKE_CORE_INCLUDE_PATH . 'tests' . DS . 'test_app' . DS . 'views' . DS . 'themed' . DS . 'test_theme' . DS . 'webroot' . DS . 'pdfs' . DS . 'theme_test.pdf');
		$this->assertEqual($file, $result);
		$this->assertEqual('this is just a test to load pdf file from the theme.', $result);

		ob_start();
		$Dispatcher->dispatch('theme/test_theme/img/test.jpg');
		$result = ob_get_clean();
		$file = file_get_contents(TEST_CAKE_CORE_INCLUDE_PATH . 'tests' . DS . 'test_app' . DS . 'views' . DS . 'themed' . DS . 'test_theme' . DS . 'webroot' . DS . 'img' . DS . 'test.jpg');
		$this->assertEqual($file, $result);

		$Dispatcher->params = $Dispatcher->parseParams('theme/test_theme/css/test_asset.css');
		ob_start();
		$Dispatcher->asset('theme/test_theme/css/test_asset.css');
		$result = ob_get_clean();
		$this->assertEqual('this is the test asset css file', $result);

		$Dispatcher->params = $Dispatcher->parseParams('theme/test_theme/js/theme.js');
		ob_start();
		$Dispatcher->asset('theme/test_theme/js/theme.js');
		$result = ob_get_clean();
		$this->assertEqual('root theme js file', $result);

		$Dispatcher->params = $Dispatcher->parseParams('theme/test_theme/js/one/theme_one.js');
		ob_start();
		$Dispatcher->asset('theme/test_theme/js/one/theme_one.js');
		$result = ob_get_clean();
		$this->assertEqual('nested theme js file', $result);

		ob_start();
		$Dispatcher->asset('test_plugin/root.js');
		$result = ob_get_clean();
		$expected = file_get_contents(TEST_CAKE_CORE_INCLUDE_PATH . 'tests' . DS . 'test_app' . DS . 'plugins' . DS . 'test_plugin' . DS . 'webroot' . DS . 'root.js');
		$this->assertEqual($result, $expected);

		ob_start();
		$Dispatcher->dispatch('test_plugin/flash/plugin_test.swf');
		$result = ob_get_clean();
		$file = file_get_contents(TEST_CAKE_CORE_INCLUDE_PATH . 'tests' . DS . 'test_app' . DS . 'plugins' . DS . 'test_plugin' . DS . 'webroot' . DS . 'flash' . DS . 'plugin_test.swf');
		$this->assertEqual($file, $result);
		$this->assertEqual('this is just a test to load swf file from the plugin.', $result);

		ob_start();
		$Dispatcher->dispatch('test_plugin/pdfs/plugin_test.pdf');
		$result = ob_get_clean();
		$file = file_get_contents(TEST_CAKE_CORE_INCLUDE_PATH . 'tests' . DS . 'test_app' . DS . 'plugins' . DS . 'test_plugin' . DS . 'webroot' . DS . 'pdfs' . DS . 'plugin_test.pdf');
		$this->assertEqual($file, $result);
		 $this->assertEqual('this is just a test to load pdf file from the plugin.', $result);

		ob_start();
		$Dispatcher->asset('test_plugin/js/test_plugin/test.js');
		$result = ob_get_clean();
		$this->assertEqual('alert("Test App");', $result);

		$Dispatcher->params = $Dispatcher->parseParams('test_plugin/js/test_plugin/test.js');
		ob_start();
		$Dispatcher->asset('test_plugin/js/test_plugin/test.js');
		$result = ob_get_clean();
		$this->assertEqual('alert("Test App");', $result);

		$Dispatcher->params = $Dispatcher->parseParams('test_plugin/css/test_plugin_asset.css');
		ob_start();
		$Dispatcher->asset('test_plugin/css/test_plugin_asset.css');
		$result = ob_get_clean();
		$this->assertEqual('this is the test plugin asset css file', $result);

		$Dispatcher->params = $Dispatcher->parseParams('test_plugin/img/cake.icon.gif');
		ob_start();
		$Dispatcher->asset('test_plugin/img/cake.icon.gif');
		$result = ob_get_clean();
		$file = file_get_contents(TEST_CAKE_CORE_INCLUDE_PATH . 'tests' . DS . 'test_app' . DS . 'plugins' . DS . 'test_plugin' .DS . 'webroot' . DS . 'img' . DS . 'cake.icon.gif');
		$this->assertEqual($file, $result);

		$Dispatcher->params = $Dispatcher->parseParams('plugin_js/js/plugin_js.js');
		ob_start();
		$Dispatcher->asset('plugin_js/js/plugin_js.js');
		$result = ob_get_clean();
		$expected = "alert('win sauce');";
		$this->assertEqual($result, $expected);

		$Dispatcher->params = $Dispatcher->parseParams('plugin_js/js/one/plugin_one.js');
		ob_start();
		$Dispatcher->asset('plugin_js/js/one/plugin_one.js');
		$result = ob_get_clean();
		$expected = "alert('plugin one nested js file');";
		$this->assertEqual($result, $expected);
		Configure::write('debug', $debug);
		//reset the header content-type without page can render as plain text.
		header('Content-type: text/html');

		$Dispatcher->params = $Dispatcher->parseParams('test_plugin/css/theme_one.htc');
		ob_start();
		$Dispatcher->asset('test_plugin/css/unknown.extension');
		$result = ob_get_clean();
		$this->assertEqual('Testing a file with unknown extension to mime mapping.', $result);
		header('Content-type: text/html');

		$Dispatcher->params = $Dispatcher->parseParams('test_plugin/css/theme_one.htc');
		ob_start();
		$Dispatcher->asset('test_plugin/css/theme_one.htc');
		$result = ob_get_clean();
		$this->assertEqual('htc file', $result);
		header('Content-type: text/html');
	}

/**
 * test that missing asset processors trigger a 404 with no response body.
 *
 * @return void
 */
	function testMissingAssetProcessor404() {
		$Dispatcher =& new TestDispatcher();
		Configure::write('Asset.filter', array(
			'js' => '',
			'css' => null
		));
		$this->assertNoErrors();

		ob_start();
		$Dispatcher->asset('ccss/cake.generic.css');
		$result = ob_get_clean();
		$this->assertTrue($Dispatcher->stopped);

		header('HTTP/1.1 200 Ok');
	}

/**
 * test that asset filters work for theme and plugin assets
 *
 * @return void
 */
	function testAssetFilterForThemeAndPlugins() {
		$Dispatcher =& new TestDispatcher();
		Configure::write('Asset.filter', array(
			'js' => '',
			'css' => ''
		));
		$Dispatcher->asset('theme/test_theme/ccss/cake.generic.css');
		$this->assertTrue($Dispatcher->stopped);

		$Dispatcher->stopped = false;
		$Dispatcher->asset('theme/test_theme/cjs/debug_kit.js');
		$this->assertTrue($Dispatcher->stopped);

		$Dispatcher->stopped = false;
		$Dispatcher->asset('test_plugin/ccss/cake.generic.css');
		$this->assertTrue($Dispatcher->stopped);

		$Dispatcher->stopped = false;
		$Dispatcher->asset('test_plugin/cjs/debug_kit.js');
		$this->assertTrue($Dispatcher->stopped);

		$Dispatcher->stopped = false;
		$Dispatcher->asset('css/ccss/debug_kit.css');
		$this->assertFalse($Dispatcher->stopped);

		$Dispatcher->stopped = false;
		$Dispatcher->asset('js/cjs/debug_kit.js');
		$this->assertFalse($Dispatcher->stopped);
	}
/**
 * testFullPageCachingDispatch method
 *
 * @return void
 * @access public
 */
	function testFullPageCachingDispatch() {
		Configure::write('Cache.disable', false);
		Configure::write('Cache.check', true);
		Configure::write('debug', 2);

		$_POST = array();
		$_SERVER['PHP_SELF'] = '/';

		Router::reload();
		Router::connect('/', array('controller' => 'test_cached_pages', 'action' => 'index'));

		App::build(array(
			'views' => array(TEST_CAKE_CORE_INCLUDE_PATH . 'tests' . DS . 'test_app' . DS . 'views' . DS),
		), true);

		$dispatcher =& new TestDispatcher();
		$dispatcher->base = false;

		$url = '/';

		ob_start();
		$dispatcher->dispatch($url);
		$out = ob_get_clean();

		ob_start();
		$dispatcher->cached($url);
		$cached = ob_get_clean();

		$result = str_replace(array("\t", "\r\n", "\n"), "", $out);
		$cached = preg_replace('/<!--+[^<>]+-->/', '', $cached);
		$expected =  str_replace(array("\t", "\r\n", "\n"), "", $cached);

		$this->assertEqual($result, $expected);

		$filename = $this->__cachePath($dispatcher->here);
		unlink($filename);

		$dispatcher->base = false;
		$url = 'test_cached_pages/index';

		ob_start();
		$dispatcher->dispatch($url);
		$out = ob_get_clean();

		ob_start();
		$dispatcher->cached($url);
		$cached = ob_get_clean();

		$result = str_replace(array("\t", "\r\n", "\n"), "", $out);
		$cached = preg_replace('/<!--+[^<>]+-->/', '', $cached);
		$expected =  str_replace(array("\t", "\r\n", "\n"), "", $cached);

		$this->assertEqual($result, $expected);
		$filename = $this->__cachePath($dispatcher->here);
		unlink($filename);

		$url = 'TestCachedPages/index';

		ob_start();
		$dispatcher->dispatch($url);
		$out = ob_get_clean();

		ob_start();
		$dispatcher->cached($url);
		$cached = ob_get_clean();

		$result = str_replace(array("\t", "\r\n", "\n"), "", $out);
		$cached = preg_replace('/<!--+[^<>]+-->/', '', $cached);
		$expected =  str_replace(array("\t", "\r\n", "\n"), "", $cached);

		$this->assertEqual($result, $expected);
		$filename = $this->__cachePath($dispatcher->here);
		unlink($filename);

		$url = 'TestCachedPages/test_nocache_tags';

		ob_start();
		$dispatcher->dispatch($url);
		$out = ob_get_clean();

		ob_start();
		$dispatcher->cached($url);
		$cached = ob_get_clean();

		$result = str_replace(array("\t", "\r\n", "\n"), "", $out);
		$cached = preg_replace('/<!--+[^<>]+-->/', '', $cached);
		$expected =  str_replace(array("\t", "\r\n", "\n"), "", $cached);

		$this->assertEqual($result, $expected);
		$filename = $this->__cachePath($dispatcher->here);
		unlink($filename);

		$url = 'test_cached_pages/view/param/param';

		ob_start();
		$dispatcher->dispatch($url);
		$out = ob_get_clean();

		ob_start();
		$dispatcher->cached($url);
		$cached = ob_get_clean();

		$result = str_replace(array("\t", "\r\n", "\n"), "", $out);
		$cached = preg_replace('/<!--+[^<>]+-->/', '', $cached);
		$expected =  str_replace(array("\t", "\r\n", "\n"), "", $cached);

		$this->assertEqual($result, $expected);
		$filename = $this->__cachePath($dispatcher->here);
		unlink($filename);

		$url = 'test_cached_pages/view/foo:bar/value:goo';

		ob_start();
		$dispatcher->dispatch($url);
		$out = ob_get_clean();

		ob_start();
		$dispatcher->cached($url);
		$cached = ob_get_clean();

		$result = str_replace(array("\t", "\r\n", "\n"), "", $out);
		$cached = preg_replace('/<!--+[^<>]+-->/', '', $cached);
		$expected =  str_replace(array("\t", "\r\n", "\n"), "", $cached);

		$this->assertEqual($result, $expected);
		$filename = $this->__cachePath($dispatcher->here);
		$this->assertTrue(file_exists($filename));
		unlink($filename);
	}

/**
 * test that cached() registers a view and un-registers it.  Tests
 * that helpers using ClassRegistry::getObject('view'); don't fail
 *
 * @return void
 */
	function testCachedRegisteringViewObject() {
		Configure::write('Cache.disable', false);
		Configure::write('Cache.check', true);
		Configure::write('debug', 2);

		$_POST = array();
		$_SERVER['PHP_SELF'] = '/';

		Router::reload();
		App::build(array(
			'views' => array(TEST_CAKE_CORE_INCLUDE_PATH . 'tests' . DS . 'test_app' . DS . 'views'. DS)
		));

		$dispatcher =& new TestDispatcher();
		$dispatcher->base = false;

		$url = 'test_cached_pages/cache_form';
		ob_start();
		$dispatcher->dispatch($url);
		$out = ob_get_clean();

		ClassRegistry::flush();

		ob_start();
		$dispatcher->cached($url);
		$cached = ob_get_clean();

		$result = str_replace(array("\t", "\r\n", "\n"), "", $out);
		$cached = preg_replace('/<!--+[^<>]+-->/', '', $cached);
		$expected =  str_replace(array("\t", "\r\n", "\n"), "", $cached);

		$this->assertEqual($result, $expected);
		$filename = $this->__cachePath($dispatcher->here);
		@unlink($filename);
		ClassRegistry::flush();
	}

/**
 * testHttpMethodOverrides method
 *
 * @return void
 * @access public
 */
	function testHttpMethodOverrides() {
		Router::reload();
		Router::mapResources('Posts');

		$_SERVER['REQUEST_METHOD'] = 'POST';
		$dispatcher =& new Dispatcher();
		$dispatcher->base = false;

		$result = $dispatcher->parseParams('/posts');
		$expected = array('pass' => array(), 'named' => array(), 'plugin' => null, 'controller' => 'posts', 'action' => 'add', '[method]' => 'POST', 'form' => array(), 'url' => array());
		$this->assertEqual($result, $expected);

		$_SERVER['REQUEST_METHOD'] = 'GET';
		$_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE'] = 'PUT';

		$result = $dispatcher->parseParams('/posts/5');
		$expected = array('pass' => array('5'), 'named' => array(), 'id' => '5', 'plugin' => null, 'controller' => 'posts', 'action' => 'edit', '[method]' => 'PUT', 'form' => array(), 'url' => array());
		$this->assertEqual($result, $expected);

		unset($_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE']);
		$_SERVER['REQUEST_METHOD'] = 'GET';

		$result = $dispatcher->parseParams('/posts/5');
		$expected = array('pass' => array('5'), 'named' => array(), 'id' => '5', 'plugin' => null, 'controller' => 'posts', 'action' => 'view', '[method]' => 'GET', 'form' => array(), 'url' => array());
		$this->assertEqual($result, $expected);

		$_POST['_method'] = 'PUT';

		$result = $dispatcher->parseParams('/posts/5');
		$expected = array('pass' => array('5'), 'named' => array(), 'id' => '5', 'plugin' => null, 'controller' => 'posts', 'action' => 'edit', '[method]' => 'PUT', 'form' => array(), 'url' => array());
		$this->assertEqual($result, $expected);

		$_POST['_method'] = 'POST';
		$_POST['data'] = array('Post' => array('title' => 'New Post'));
		$_POST['extra'] = 'data';
		$_SERVER = array();

		$result = $dispatcher->parseParams('/posts');
		$expected = array(
			'pass' => array(), 'named' => array(), 'plugin' => null, 'controller' => 'posts', 'action' => 'add',
			'[method]' => 'POST', 'form' => array('extra' => 'data'), 'data' => array('Post' => array('title' => 'New Post')),
			'url' => array()
		);
		$this->assertEqual($result, $expected);

		unset($_POST['_method']);
	}

/**
 * Tests that invalid characters cannot be injected into the application base path.
 *
 * @return void
 * @access public
 */
	function testBasePathInjection() {
		$self = $_SERVER['PHP_SELF'];
		$_SERVER['PHP_SELF'] = urldecode(
			"/index.php/%22%3E%3Ch1%20onclick=%22alert('xss');%22%3Eheya%3C/h1%3E"
		);

		$dispatcher =& new Dispatcher();
		$result = $dispatcher->baseUrl();
		$expected = '/index.php/h1 onclick=alert(xss);heya';
		$this->assertEqual($result, $expected);
	}

/**
 * testEnvironmentDetection method
 *
 * @return void
 * @access public
 */
	function testEnvironmentDetection() {
		$dispatcher =& new Dispatcher();

		$environments = array(
			'IIS' => array(
				'No rewrite base path' => array(
					'App' => array('base' => false, 'baseUrl' => '/index.php?', 'server' => 'IIS'),
					'SERVER' => array('HTTPS' => 'off', 'SCRIPT_NAME' => '/index.php', 'PATH_TRANSLATED' => 'C:\\Inetpub\\wwwroot', 'QUERY_STRING' => '', 'REMOTE_ADDR' => '127.0.0.1', 'REMOTE_HOST' => '127.0.0.1', 'REQUEST_METHOD' => 'GET', 'SERVER_NAME' => 'localhost', 'SERVER_PORT' => '80', 'SERVER_PROTOCOL' => 'HTTP/1.1', 'APPL_PHYSICAL_PATH' => 'C:\\Inetpub\\wwwroot\\', 'REQUEST_URI' => '/index.php', 'URL' => '/index.php', 'SCRIPT_FILENAME' => 'C:\\Inetpub\\wwwroot\\index.php', 'ORIG_PATH_INFO' => '/index.php', 'PATH_INFO' => '', 'ORIG_PATH_TRANSLATED' => 'C:\\Inetpub\\wwwroot\\index.php', 'DOCUMENT_ROOT' => 'C:\\Inetpub\\wwwroot', 'PHP_SELF' => '/index.php', 'HTTP_HOST' => 'localhost', 'argv' => array(), 'argc' => 0),
					'reload' => true,
					'path' => ''
				),
				'No rewrite with path' => array(
					'SERVER' => array('QUERY_STRING' => '/posts/add', 'REQUEST_URI' => '/index.php?/posts/add', 'URL' => '/index.php?/posts/add', 'argv' => array('/posts/add'), 'argc' => 1),
					'reload' => false,
					'path' => '/posts/add'
				),
				'No rewrite sub dir 1' => array(
					'GET' => array(),
					'SERVER' => array('QUERY_STRING' => '',  'REQUEST_URI' => '/index.php', 'URL' => '/index.php', 'SCRIPT_FILENAME' => 'C:\\Inetpub\\wwwroot\\index.php', 'ORIG_PATH_INFO' => '/index.php', 'PATH_INFO' => '', 'ORIG_PATH_TRANSLATED' => 'C:\\Inetpub\\wwwroot\\index.php', 'DOCUMENT_ROOT' => 'C:\\Inetpub\\wwwroot', 'PHP_SELF' => '/index.php', 'argv' => array(), 'argc' => 0),
					'reload' => false,
					'path' => ''
				),
				'No rewrite sub dir 1 with path' => array(
					'GET' => array('/posts/add' => ''),
					'SERVER' => array('QUERY_STRING' => '/posts/add', 'REQUEST_URI' => '/index.php?/posts/add', 'URL' => '/index.php?/posts/add', 'SCRIPT_FILENAME' => 'C:\\Inetpub\\wwwroot\\index.php', 'argv' => array('/posts/add'), 'argc' => 1),
					'reload' => false,
					'path' => '/posts/add'
				),
				'No rewrite sub dir 2' => array(
					'App' => array('base' => false, 'baseUrl' => '/site/index.php?', 'dir' => 'app', 'webroot' => 'webroot', 'server' => 'IIS'),
					'GET' => array(),
					'POST' => array(),
					'SERVER' => array('SCRIPT_NAME' => '/site/index.php', 'PATH_TRANSLATED' => 'C:\\Inetpub\\wwwroot', 'QUERY_STRING' => '', 'REQUEST_URI' => '/site/index.php', 'URL' => '/site/index.php', 'SCRIPT_FILENAME' => 'C:\\Inetpub\\wwwroot\\site\\index.php', 'DOCUMENT_ROOT' => 'C:\\Inetpub\\wwwroot', 'PHP_SELF' => '/site/index.php', 'argv' => array(), 'argc' => 0),
					'reload' => false,
					'path' => ''
				),
				'No rewrite sub dir 2 with path' => array(
					'GET' => array('/posts/add' => ''),
					'SERVER' => array('SCRIPT_NAME' => '/site/index.php', 'PATH_TRANSLATED' => 'C:\\Inetpub\\wwwroot', 'QUERY_STRING' => '/posts/add', 'REQUEST_URI' => '/site/index.php?/posts/add', 'URL' => '/site/index.php?/posts/add', 'ORIG_PATH_TRANSLATED' => 'C:\\Inetpub\\wwwroot\\site\\index.php', 'DOCUMENT_ROOT' => 'C:\\Inetpub\\wwwroot', 'PHP_SELF' => '/site/index.php', 'argv' => array('/posts/add'), 'argc' => 1),
					'reload' => false,
					'path' => '/posts/add'
				)
			),
			'Apache' => array(
				'No rewrite base path' => array(
					'App' => array('base' => false, 'baseUrl' => '/index.php', 'dir' => 'app', 'webroot' => 'webroot'),
					'SERVER' => array(
						'SERVER_NAME' => 'localhost',
						'SERVER_ADDR' => '::1',
						'SERVER_PORT' => '80',
						'REMOTE_ADDR' => '::1',
						'DOCUMENT_ROOT' => '/Library/WebServer/Documents/officespace/app/webroot',
						'SCRIPT_FILENAME' => '/Library/WebServer/Documents/site/app/webroot/index.php',
						'QUERY_STRING' => '',
						'REQUEST_URI' => '/',
						'SCRIPT_NAME' => '/index.php',
						'PHP_SELF' => '/index.php',
						'argv' => array(),
						'argc' => 0
					),
					'reload' => true,
					'path' => ''
				),
				'No rewrite with path' => array(
					'SERVER' => array(
						'HTTP_HOST' => 'localhost', 
						'DOCUMENT_ROOT' => '/Library/WebServer/Documents/officespace/app/webroot',
						'SCRIPT_FILENAME' => '/Library/WebServer/Documents/officespace/app/webroot/index.php',
						'QUERY_STRING' => '',
						'REQUEST_URI' => '/index.php/posts/add',
						'SCRIPT_NAME' => '/index.php',
						'PATH_INFO' => '/posts/add',
						'PHP_SELF' => '/index.php/posts/add', 
						'argv' => array(),
						'argc' => 0),
					'reload' => false,
					'path' => '/posts/add'
				),
				'GET Request at base domain' => array(
					'App' => array('base' => false, 'baseUrl' => null, 'dir' => 'app', 'webroot' => 'webroot'),
					'SERVER'	=> array(
						'HTTP_HOST' => 'cake.1.2',
						'SERVER_NAME' => 'cake.1.2',
						'SERVER_ADDR' => '127.0.0.1',
						'SERVER_PORT' => '80',
						'REMOTE_ADDR' => '127.0.0.1',
						'DOCUMENT_ROOT' => '/Volumes/Home/htdocs/cake/repo/branches/1.2.x.x/app/webroot',
						'SCRIPT_FILENAME' => '/Volumes/Home/htdocs/cake/repo/branches/1.2.x.x/app/webroot/index.php',
						'REMOTE_PORT' => '53550',
						'QUERY_STRING' => 'a=b',
						'REQUEST_URI' => '/?a=b',
						'SCRIPT_NAME' => '/index.php',
						'PHP_SELF' => '/index.php'
					),
					'GET' => array('a' => 'b'),
					'POST' => array(),
					'reload' => true,
					'path' => '',
					'urlParams' => array('a' => 'b'),
					'environment' => array('CGI_MODE' => false)
				),
				'New CGI no mod_rewrite' => array(
					'App' => array('base' => false, 'baseUrl' => '/limesurvey20/index.php', 'dir' => 'app', 'webroot' => 'webroot'),
					'SERVER' => array(
						'DOCUMENT_ROOT' => '/home/.sites/110/site313/web',
						'PATH_INFO' => '/installations',
						'PATH_TRANSLATED' => '/home/.sites/110/site313/web/limesurvey20/index.php',
						'PHPRC' => '/home/.sites/110/site313',
						'QUERY_STRING' => '',
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
					'path' => '/installations',
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
				$this->assertEqual($dispatcher->uri(), $settings['path'], "%s on environment: {$name}, on setting: {$descrip}");

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
 * Tests that the Dispatcher does not return an empty action
 *
 * @return void
 * @access public
 */
	function testTrailingSlash() {
		$_POST = array();
		$_SERVER['PHP_SELF'] = '/cake/repo/branches/1.2.x.x/index.php';

		Router::reload();
		$Dispatcher =& new TestDispatcher();
		Router::connect('/myalias/:action/*', array('controller' => 'my_controller', 'action' => null));

		$Dispatcher->base = false;
		$url = 'myalias/'; //Fails
		$controller = $Dispatcher->dispatch($url, array('return' => 1));
		$result = $Dispatcher->parseParams($url);
		$this->assertEqual('index', $result['action']);

		$url = 'myalias'; //Passes
		$controller = $Dispatcher->dispatch($url, array('return' => 1));
		$result = $Dispatcher->parseParams($url);
		$this->assertEqual('index', $result['action']);
	}

/**
 * backupEnvironment method
 *
 * @return void
 * @access private
 */
	function __backupEnvironment() {
		return array(
			'App'	=> Configure::read('App'),
			'GET'	=> $_GET,
			'POST'	=> $_POST,
			'SERVER'=> $_SERVER
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

/**
 * cachePath method
 *
 * @param mixed $her
 * @return string
 * @access private
 */
	function __cachePath($here) {
		$path = $here;
		if ($here == '/') {
			$path = 'home';
		}
		$path = strtolower(Inflector::slug($path));

		$filename = CACHE . 'views' . DS . $path . '.php';

		if (!file_exists($filename)) {
			$filename = CACHE . 'views' . DS . $path . '_index.php';
		}
		return $filename;
	}
}
