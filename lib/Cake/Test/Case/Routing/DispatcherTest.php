<?php
/**
 * DispatcherTest file
 *
 * PHP 5
 *
 * CakePHP(tm) Tests <http://book.cakephp.org/2.0/en/development/testing.html>
 * Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/2.0/en/development/testing.html CakePHP(tm) Tests
 * @package       Cake.Test.Case.Routing
 * @since         CakePHP(tm) v 1.2.0.4206
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
App::uses('Dispatcher', 'Routing');

if (!class_exists('AppController', false)) {
	require_once CAKE . 'Test' . DS . 'test_app' . DS . 'Controller' . DS . 'AppController.php';
} elseif (!defined('APP_CONTROLLER_EXISTS')) {
	define('APP_CONTROLLER_EXISTS', true);
}

/**
 * A testing stub that doesn't send headers.
 *
 * @package       Cake.Test.Case.Routing
 */
class DispatcherMockCakeResponse extends CakeResponse {

	protected function _sendHeader($name, $value = null) {
		return $name . ' ' . $value;
	}

}

/**
 * TestDispatcher class
 *
 * @package       Cake.Test.Case.Routing
 */
class TestDispatcher extends Dispatcher {

/**
 * Controller instance, made publicly available for testing
 *
 * @var Controller
 */
	public $controller;

/**
 * invoke method
 *
 * @param Controller $controller
 * @param CakeRequest $request
 * @param CakeResponse $response
 * @return void
 */
	protected function _invoke(Controller $controller, CakeRequest $request, CakeResponse $response) {
		$this->controller = $controller;
		return parent::_invoke($controller, $request, $response);
	}

/**
 * Helper function to test single method attaching for dispatcher filters
 *
 * @param CakeEvent $event
 * @return void
 */
	public function filterTest($event) {
		$event->data['request']->params['eventName'] = $event->name();
	}

/**
 * Helper function to test single method attaching for dispatcher filters
 *
 * @param CakeEvent
 * @return void
 */
	public function filterTest2($event) {
		$event->stopPropagation();
		return $event->data['response'];
	}

}

/**
 * MyPluginAppController class
 *
 * @package       Cake.Test.Case.Routing
 */
class MyPluginAppController extends AppController {
}

abstract class DispatcherTestAbstractController extends Controller {

	abstract public function index();

}

interface DispatcherTestInterfaceController {

	public function index();

}

/**
 * MyPluginController class
 *
 * @package       Cake.Test.Case.Routing
 */
class MyPluginController extends MyPluginAppController {

/**
 * name property
 *
 * @var string 'MyPlugin'
 */
	public $name = 'MyPlugin';

/**
 * uses property
 *
 * @var array
 */
	public $uses = array();

/**
 * index method
 *
 * @return void
 */
	public function index() {
		return true;
	}

/**
 * add method
 *
 * @return void
 */
	public function add() {
		return true;
	}

/**
 * admin_add method
 *
 * @param mixed $id
 * @return void
 */
	public function admin_add($id = null) {
		return $id;
	}

}

/**
 * SomePagesController class
 *
 * @package       Cake.Test.Case.Routing
 */
class SomePagesController extends AppController {

/**
 * name property
 *
 * @var string 'SomePages'
 */
	public $name = 'SomePages';

/**
 * uses property
 *
 * @var array
 */
	public $uses = array();

/**
 * display method
 *
 * @param string $page
 * @return void
 */
	public function display($page = null) {
		return $page;
	}

/**
 * index method
 *
 * @return void
 */
	public function index() {
		return true;
	}

/**
 * Test method for returning responses.
 *
 * @return CakeResponse
 */
	public function responseGenerator() {
		return new CakeResponse(array('body' => 'new response'));
	}

}

/**
 * OtherPagesController class
 *
 * @package       Cake.Test.Case.Routing
 */
class OtherPagesController extends MyPluginAppController {

/**
 * name property
 *
 * @var string 'OtherPages'
 */
	public $name = 'OtherPages';

/**
 * uses property
 *
 * @var array
 */
	public $uses = array();

/**
 * display method
 *
 * @param string $page
 * @return void
 */
	public function display($page = null) {
		return $page;
	}

/**
 * index method
 *
 * @return void
 */
	public function index() {
		return true;
	}

}

/**
 * TestDispatchPagesController class
 *
 * @package       Cake.Test.Case.Routing
 */
class TestDispatchPagesController extends AppController {

/**
 * name property
 *
 * @var string 'TestDispatchPages'
 */
	public $name = 'TestDispatchPages';

/**
 * uses property
 *
 * @var array
 */
	public $uses = array();

/**
 * admin_index method
 *
 * @return void
 */
	public function admin_index() {
		return true;
	}

/**
 * camelCased method
 *
 * @return void
 */
	public function camelCased() {
		return true;
	}

}

/**
 * ArticlesTestAppController class
 *
 * @package       Cake.Test.Case.Routing
 */
class ArticlesTestAppController extends AppController {
}

/**
 * ArticlesTestController class
 *
 * @package       Cake.Test.Case.Routing
 */
class ArticlesTestController extends ArticlesTestAppController {

/**
 * name property
 *
 * @var string 'ArticlesTest'
 */
	public $name = 'ArticlesTest';

/**
 * uses property
 *
 * @var array
 */
	public $uses = array();

/**
 * admin_index method
 *
 * @return void
 */
	public function admin_index() {
		return true;
	}

/**
 * fake index method.
 *
 * @return void
 */
	public function index() {
		return true;
	}

}

/**
 * SomePostsController class
 *
 * @package       Cake.Test.Case.Routing
 */
class SomePostsController extends AppController {

/**
 * name property
 *
 * @var string 'SomePosts'
 */
	public $name = 'SomePosts';

/**
 * uses property
 *
 * @var array
 */
	public $uses = array();

/**
 * autoRender property
 *
 * @var bool false
 */
	public $autoRender = false;

/**
 * beforeFilter method
 *
 * @return void
 */
	public function beforeFilter() {
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
 */
	public function index() {
		return true;
	}

/**
 * change method
 *
 * @return void
 */
	public function change() {
		return true;
	}

}

/**
 * TestCachedPagesController class
 *
 * @package       Cake.Test.Case.Routing
 */
class TestCachedPagesController extends Controller {

/**
 * name property
 *
 * @var string 'TestCachedPages'
 */
	public $name = 'TestCachedPages';

/**
 * uses property
 *
 * @var array
 */
	public $uses = array();

/**
 * helpers property
 *
 * @var array
 */
	public $helpers = array('Cache', 'Html');

/**
 * cacheAction property
 *
 * @var array
 */
	public $cacheAction = array(
		'index' => '+2 sec',
		'test_nocache_tags' => '+2 sec',
		'view' => '+2 sec'
	);

/**
 * Mock out the response object so it doesn't send headers.
 *
 * @var string
 */
	protected $_responseClass = 'DispatcherMockCakeResponse';

/**
 * viewPath property
 *
 * @var string 'posts'
 */
	public $viewPath = 'Posts';

/**
 * index method
 *
 * @return void
 */
	public function index() {
		$this->render();
	}

/**
 * test_nocache_tags method
 *
 * @return void
 */
	public function test_nocache_tags() {
		$this->render();
	}

/**
 * view method
 *
 * @return void
 */
	public function view($id = null) {
		$this->render('index');
	}

/**
 * test cached forms / tests view object being registered
 *
 * @return void
 */
	public function cache_form() {
		$this->cacheAction = 10;
		$this->helpers[] = 'Form';
	}

/**
 * Test cached views with themes.
 */
	public function themed() {
		$this->cacheAction = 10;
		$this->viewClass = 'Theme';
		$this->theme = 'TestTheme';
	}

}

/**
 * TimesheetsController class
 *
 * @package       Cake.Test.Case.Routing
 */
class TimesheetsController extends Controller {

/**
 * name property
 *
 * @var string 'Timesheets'
 */
	public $name = 'Timesheets';

/**
 * uses property
 *
 * @var array
 */
	public $uses = array();

/**
 * index method
 *
 * @return void
 */
	public function index() {
		return true;
	}

}

/**
 * DispatcherTest class
 *
 * @package       Cake.Test.Case.Routing
 */
class DispatcherTest extends CakeTestCase {

/**
 * setUp method
 *
 * @return void
 */
	public function setUp() {
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

		App::build();
		App::objects('plugin', null, false);
	}

/**
 * tearDown method
 *
 * @return void
 */
	public function tearDown() {
		$_GET = $this->_get;
		$_POST = $this->_post;
		$_FILES = $this->_files;
		$_SERVER = $this->_server;
		App::build();
		CakePlugin::unload();
		Configure::write('App', $this->_app);
		Configure::write('Cache', $this->_cache);
		Configure::write('debug', $this->_debug);
		Configure::write('Dispatcher.filters', array());
	}

/**
 * testParseParamsWithoutZerosAndEmptyPost method
 *
 * @return void
 */
	public function testParseParamsWithoutZerosAndEmptyPost() {
		$Dispatcher = new Dispatcher();
		$request = new CakeRequest("/testcontroller/testaction/params1/params2/params3");
		$event = new CakeEvent('DispatcherTest', $Dispatcher, array('request' => $request));
		$Dispatcher->parseParams($event);
		$this->assertSame($request['controller'], 'testcontroller');
		$this->assertSame($request['action'], 'testaction');
		$this->assertSame($request['pass'][0], 'params1');
		$this->assertSame($request['pass'][1], 'params2');
		$this->assertSame($request['pass'][2], 'params3');
		$this->assertFalse(!empty($request['form']));
	}

/**
 * testParseParamsReturnsPostedData method
 *
 * @return void
 */
	public function testParseParamsReturnsPostedData() {
		$_POST['testdata'] = "My Posted Content";
		$Dispatcher = new Dispatcher();
		$request = new CakeRequest("/");
		$event = new CakeEvent('DispatcherTest', $Dispatcher, array('request' => $request));
		$Dispatcher->parseParams($event);
		$test = $Dispatcher->parseParams($event);
		$this->assertEquals("My Posted Content", $request['data']['testdata']);
	}

/**
 * testParseParamsWithSingleZero method
 *
 * @return void
 */
	public function testParseParamsWithSingleZero() {
		$Dispatcher = new Dispatcher();
		$test = new CakeRequest("/testcontroller/testaction/1/0/23");
		$event = new CakeEvent('DispatcherTest', $Dispatcher, array('request' => $test));
		$Dispatcher->parseParams($event);

		$this->assertSame($test['controller'], 'testcontroller');
		$this->assertSame($test['action'], 'testaction');
		$this->assertSame($test['pass'][0], '1');
		$this->assertRegExp('/\\A(?:0)\\z/', $test['pass'][1]);
		$this->assertSame($test['pass'][2], '23');
	}

/**
 * testParseParamsWithManySingleZeros method
 *
 * @return void
 */
	public function testParseParamsWithManySingleZeros() {
		$Dispatcher = new Dispatcher();
		$test = new CakeRequest("/testcontroller/testaction/0/0/0/0/0/0");
		$event = new CakeEvent('DispatcherTest', $Dispatcher, array('request' => $test));
		$Dispatcher->parseParams($event);

		$this->assertRegExp('/\\A(?:0)\\z/', $test['pass'][0]);
		$this->assertRegExp('/\\A(?:0)\\z/', $test['pass'][1]);
		$this->assertRegExp('/\\A(?:0)\\z/', $test['pass'][2]);
		$this->assertRegExp('/\\A(?:0)\\z/', $test['pass'][3]);
		$this->assertRegExp('/\\A(?:0)\\z/', $test['pass'][4]);
		$this->assertRegExp('/\\A(?:0)\\z/', $test['pass'][5]);
	}

/**
 * testParseParamsWithManyZerosInEachSectionOfUrl method
 *
 * @return void
 */
	public function testParseParamsWithManyZerosInEachSectionOfUrl() {
		$Dispatcher = new Dispatcher();
		$test = new CakeRequest("/testcontroller/testaction/000/0000/00000/000000/000000/0000000");
		$event = new CakeEvent('DispatcherTest', $Dispatcher, array('request' => $test));
		$Dispatcher->parseParams($event);

		$this->assertRegExp('/\\A(?:000)\\z/', $test['pass'][0]);
		$this->assertRegExp('/\\A(?:0000)\\z/', $test['pass'][1]);
		$this->assertRegExp('/\\A(?:00000)\\z/', $test['pass'][2]);
		$this->assertRegExp('/\\A(?:000000)\\z/', $test['pass'][3]);
		$this->assertRegExp('/\\A(?:000000)\\z/', $test['pass'][4]);
		$this->assertRegExp('/\\A(?:0000000)\\z/', $test['pass'][5]);
	}

/**
 * testParseParamsWithMixedOneToManyZerosInEachSectionOfUrl method
 *
 * @return void
 */
	public function testParseParamsWithMixedOneToManyZerosInEachSectionOfUrl() {
		$Dispatcher = new Dispatcher();
		$test = new CakeRequest("/testcontroller/testaction/01/0403/04010/000002/000030/0000400");
		$event = new CakeEvent('DispatcherTest', $Dispatcher, array('request' => $test));
		$Dispatcher->parseParams($event);

		$this->assertRegExp('/\\A(?:01)\\z/', $test['pass'][0]);
		$this->assertRegExp('/\\A(?:0403)\\z/', $test['pass'][1]);
		$this->assertRegExp('/\\A(?:04010)\\z/', $test['pass'][2]);
		$this->assertRegExp('/\\A(?:000002)\\z/', $test['pass'][3]);
		$this->assertRegExp('/\\A(?:000030)\\z/', $test['pass'][4]);
		$this->assertRegExp('/\\A(?:0000400)\\z/', $test['pass'][5]);
	}

/**
 * testQueryStringOnRoot method
 *
 * @return void
 */
	public function testQueryStringOnRoot() {
		Router::reload();
		Router::connect('/', array('controller' => 'pages', 'action' => 'display', 'home'));
		Router::connect('/pages/*', array('controller' => 'pages', 'action' => 'display'));
		Router::connect('/:controller/:action/*');

		$_GET = array('coffee' => 'life', 'sleep' => 'sissies');
		$Dispatcher = new Dispatcher();
		$request = new CakeRequest('posts/home/?coffee=life&sleep=sissies');
		$event = new CakeEvent('DispatcherTest', $Dispatcher, array('request' => $request));
		$Dispatcher->parseParams($event);

		$this->assertRegExp('/posts/', $request['controller']);
		$this->assertRegExp('/home/', $request['action']);
		$this->assertTrue(isset($request['url']['sleep']));
		$this->assertTrue(isset($request['url']['coffee']));

		$Dispatcher = new Dispatcher();
		$request = new CakeRequest('/?coffee=life&sleep=sissy');

		$event = new CakeEvent('DispatcherTest', $Dispatcher, array('request' => $request));
		$Dispatcher->parseParams($event);
		$this->assertRegExp('/pages/', $request['controller']);
		$this->assertRegExp('/display/', $request['action']);
		$this->assertTrue(isset($request['url']['sleep']));
		$this->assertTrue(isset($request['url']['coffee']));
		$this->assertEquals('life', $request['url']['coffee']);
	}

/**
 * testMissingController method
 *
 * @expectedException MissingControllerException
 * @expectedExceptionMessage Controller class SomeControllerController could not be found.
 * @return void
 */
	public function testMissingController() {
		Router::connect('/:controller/:action/*');

		$Dispatcher = new TestDispatcher();
		Configure::write('App.baseUrl', '/index.php');
		$url = new CakeRequest('some_controller/home/param:value/param2:value2');
		$response = $this->getMock('CakeResponse');

		$Dispatcher->dispatch($url, $response, array('return' => 1));
	}

/**
 * testMissingControllerInterface method
 *
 * @expectedException MissingControllerException
 * @expectedExceptionMessage Controller class DispatcherTestInterfaceController could not be found.
 * @return void
 */
	public function testMissingControllerInterface() {
		Router::connect('/:controller/:action/*');

		$Dispatcher = new TestDispatcher();
		Configure::write('App.baseUrl', '/index.php');
		$url = new CakeRequest('dispatcher_test_interface/index');
		$response = $this->getMock('CakeResponse');

		$Dispatcher->dispatch($url, $response, array('return' => 1));
	}

/**
 * testMissingControllerInterface method
 *
 * @expectedException MissingControllerException
 * @expectedExceptionMessage Controller class DispatcherTestAbstractController could not be found.
 * @return void
 */
	public function testMissingControllerAbstract() {
		Router::connect('/:controller/:action/*');

		$Dispatcher = new TestDispatcher();
		Configure::write('App.baseUrl', '/index.php');
		$url = new CakeRequest('dispatcher_test_abstract/index');
		$response = $this->getMock('CakeResponse');

		$Dispatcher->dispatch($url, $response, array('return' => 1));
	}

/**
 * testDispatch method
 *
 * @return void
 */
	public function testDispatchBasic() {
		App::build(array(
			'View' => array(CAKE . 'Test' . DS . 'test_app' . DS . 'View' . DS)
		));
		$Dispatcher = new TestDispatcher();
		Configure::write('App.baseUrl', '/index.php');
		$url = new CakeRequest('pages/home/param:value/param2:value2');
		$response = $this->getMock('CakeResponse');

		$Dispatcher->dispatch($url, $response, array('return' => 1));
		$expected = 'Pages';
		$this->assertEquals($expected, $Dispatcher->controller->name);

		$expected = array('0' => 'home', 'param' => 'value', 'param2' => 'value2');
		$this->assertSame($expected, $Dispatcher->controller->passedArgs);

		Configure::write('App.baseUrl', '/pages/index.php');

		$url = new CakeRequest('pages/home');
		$Dispatcher->dispatch($url, $response, array('return' => 1));

		$expected = 'Pages';
		$this->assertEquals($expected, $Dispatcher->controller->name);

		$url = new CakeRequest('pages/home/');
		$Dispatcher->dispatch($url, $response, array('return' => 1));
		$this->assertNull($Dispatcher->controller->plugin);

		$expected = 'Pages';
		$this->assertEquals($expected, $Dispatcher->controller->name);

		unset($Dispatcher);

		require CAKE . 'Config' . DS . 'routes.php';
		$Dispatcher = new TestDispatcher();
		Configure::write('App.baseUrl', '/timesheets/index.php');

		$url = new CakeRequest('timesheets');
		$Dispatcher->dispatch($url, $response, array('return' => 1));

		$expected = 'Timesheets';
		$this->assertEquals($expected, $Dispatcher->controller->name);

		$url = new CakeRequest('timesheets/');
		$Dispatcher->dispatch($url, $response, array('return' => 1));

		$this->assertEquals('Timesheets', $Dispatcher->controller->name);
		$this->assertEquals('/timesheets/index.php', $url->base);

		$url = new CakeRequest('test_dispatch_pages/camelCased');
		$Dispatcher->dispatch($url, $response, array('return' => 1));
		$this->assertEquals('TestDispatchPages', $Dispatcher->controller->name);

		$url = new CakeRequest('test_dispatch_pages/camelCased/something. .');
		$Dispatcher->dispatch($url, $response, array('return' => 1));
		$this->assertEquals('something. .', $Dispatcher->controller->params['pass'][0], 'Period was chopped off. %s');
	}

/**
 * Test that Dispatcher handles actions that return response objects.
 *
 * @return void
 */
	public function testDispatchActionReturnsResponse() {
		Router::connect('/:controller/:action');
		$Dispatcher = new Dispatcher();
		$request = new CakeRequest('some_pages/responseGenerator');
		$response = $this->getMock('CakeResponse', array('_sendHeader'));

		ob_start();
		$Dispatcher->dispatch($request, $response);
		$result = ob_get_clean();

		$this->assertEquals('new response', $result);
	}

/**
 * testAdminDispatch method
 *
 * @return void
 */
	public function testAdminDispatch() {
		$_POST = array();
		$Dispatcher = new TestDispatcher();
		Configure::write('Routing.prefixes', array('admin'));
		Configure::write('App.baseUrl','/cake/repo/branches/1.2.x.x/index.php');
		$url = new CakeRequest('admin/test_dispatch_pages/index/param:value/param2:value2');
		$response = $this->getMock('CakeResponse');

		Router::reload();
		$Dispatcher->dispatch($url, $response, array('return' => 1));

		$this->assertEquals('TestDispatchPages', $Dispatcher->controller->name);

		$this->assertSame($Dispatcher->controller->passedArgs, array('param' => 'value', 'param2' => 'value2'));
		$this->assertTrue($Dispatcher->controller->params['admin']);

		$expected = '/cake/repo/branches/1.2.x.x/index.php/admin/test_dispatch_pages/index/param:value/param2:value2';
		$this->assertSame($expected, $Dispatcher->controller->here);

		$expected = '/cake/repo/branches/1.2.x.x/index.php';
		$this->assertSame($expected, $Dispatcher->controller->base);
	}

/**
 * testPluginDispatch method
 *
 * @return void
 */
	public function testPluginDispatch() {
		$_POST = array();

		Router::reload();
		$Dispatcher = new TestDispatcher();
		Router::connect(
			'/my_plugin/:controller/*',
			array('plugin' => 'my_plugin', 'controller' => 'pages', 'action' => 'display')
		);

		$url = new CakeRequest('my_plugin/some_pages/home/param:value/param2:value2');
		$response = $this->getMock('CakeResponse');
		$Dispatcher->dispatch($url, $response, array('return' => 1));

		$event = new CakeEvent('DispatcherTest', $Dispatcher, array('request' => $url));
		$Dispatcher->parseParams($event);
		$expected = array(
			'pass' => array('home'),
			'named' => array('param' => 'value', 'param2' => 'value2'), 'plugin' => 'my_plugin',
			'controller' => 'some_pages', 'action' => 'display'
		);
		foreach ($expected as $key => $value) {
			$this->assertEquals($value, $url[$key], 'Value mismatch ' . $key . ' %');
		}

		$this->assertSame($Dispatcher->controller->plugin, 'MyPlugin');
		$this->assertSame($Dispatcher->controller->name, 'SomePages');
		$this->assertSame($Dispatcher->controller->params['controller'], 'some_pages');
		$this->assertSame($Dispatcher->controller->passedArgs, array('0' => 'home', 'param' => 'value', 'param2' => 'value2'));
	}

/**
 * testAutomaticPluginDispatch method
 *
 * @return void
 */
	public function testAutomaticPluginDispatch() {
		$_POST = array();
		$_SERVER['PHP_SELF'] = '/cake/repo/branches/1.2.x.x/index.php';

		Router::reload();
		$Dispatcher = new TestDispatcher();
		Router::connect(
			'/my_plugin/:controller/:action/*',
			array('plugin' => 'my_plugin', 'controller' => 'pages', 'action' => 'display')
		);

		$Dispatcher->base = false;

		$url = new CakeRequest('my_plugin/other_pages/index/param:value/param2:value2');
		$response = $this->getMock('CakeResponse');
		$Dispatcher->dispatch($url, $response, array('return' => 1));

		$this->assertSame($Dispatcher->controller->plugin, 'MyPlugin');
		$this->assertSame($Dispatcher->controller->name, 'OtherPages');
		$this->assertSame($Dispatcher->controller->action, 'index');
		$this->assertSame($Dispatcher->controller->passedArgs, array('param' => 'value', 'param2' => 'value2'));

		$expected = '/cake/repo/branches/1.2.x.x/my_plugin/other_pages/index/param:value/param2:value2';
		$this->assertSame($expected, $url->here);

		$expected = '/cake/repo/branches/1.2.x.x';
		$this->assertSame($expected, $url->base);
	}

/**
 * testAutomaticPluginControllerDispatch method
 *
 * @return void
 */
	public function testAutomaticPluginControllerDispatch() {
		$plugins = App::objects('plugin');
		$plugins[] = 'MyPlugin';
		$plugins[] = 'ArticlesTest';

		CakePlugin::load('MyPlugin', array('path' => '/fake/path'));

		Router::reload();
		$Dispatcher = new TestDispatcher();
		$Dispatcher->base = false;

		$url = new CakeRequest('my_plugin/my_plugin/add/param:value/param2:value2');
		$response = $this->getMock('CakeResponse');

		$Dispatcher->dispatch($url, $response, array('return' => 1));

		$this->assertSame($Dispatcher->controller->plugin, 'MyPlugin');
		$this->assertSame($Dispatcher->controller->name, 'MyPlugin');
		$this->assertSame($Dispatcher->controller->action, 'add');
		$this->assertEquals(array('param' => 'value', 'param2' => 'value2'), $Dispatcher->controller->params['named']);

		Router::reload();
		require CAKE . 'Config' . DS . 'routes.php';
		$Dispatcher = new TestDispatcher();
		$Dispatcher->base = false;

		// Simulates the Route for a real plugin, installed in APP/plugins
		Router::connect('/my_plugin/:controller/:action/*', array('plugin' => 'my_plugin'));

		$plugin = 'MyPlugin';
		$pluginUrl = Inflector::underscore($plugin);

		$url = new CakeRequest($pluginUrl);
		$Dispatcher->dispatch($url, $response, array('return' => 1));
		$this->assertSame($Dispatcher->controller->plugin, 'MyPlugin');
		$this->assertSame($Dispatcher->controller->name, 'MyPlugin');
		$this->assertSame($Dispatcher->controller->action, 'index');

		$expected = $pluginUrl;
		$this->assertEquals($expected, $Dispatcher->controller->params['controller']);

		Configure::write('Routing.prefixes', array('admin'));

		Router::reload();
		require CAKE . 'Config' . DS . 'routes.php';
		$Dispatcher = new TestDispatcher();

		$url = new CakeRequest('admin/my_plugin/my_plugin/add/5/param:value/param2:value2');
		$response = $this->getMock('CakeResponse');

		$Dispatcher->dispatch($url, $response, array('return' => 1));

		$this->assertEquals('my_plugin', $Dispatcher->controller->params['plugin']);
		$this->assertEquals('my_plugin', $Dispatcher->controller->params['controller']);
		$this->assertEquals('admin_add', $Dispatcher->controller->params['action']);
		$this->assertEquals(array(5), $Dispatcher->controller->params['pass']);
		$this->assertEquals(array('param' => 'value', 'param2' => 'value2'), $Dispatcher->controller->params['named']);
		$this->assertSame($Dispatcher->controller->plugin, 'MyPlugin');
		$this->assertSame($Dispatcher->controller->name, 'MyPlugin');
		$this->assertSame($Dispatcher->controller->action, 'admin_add');

		$expected = array(0 => 5, 'param' => 'value', 'param2' => 'value2');
		$this->assertEquals($expected, $Dispatcher->controller->passedArgs);

		Configure::write('Routing.prefixes', array('admin'));
		CakePlugin::load('ArticlesTest', array('path' => '/fake/path'));
		Router::reload();
		require CAKE . 'Config' . DS . 'routes.php';

		$Dispatcher = new TestDispatcher();

		$Dispatcher->dispatch(new CakeRequest('admin/articles_test'), $response, array('return' => 1));
		$this->assertSame($Dispatcher->controller->plugin, 'ArticlesTest');
		$this->assertSame($Dispatcher->controller->name, 'ArticlesTest');
		$this->assertSame($Dispatcher->controller->action, 'admin_index');

		$expected = array(
			'pass' => array(),
			'named' => array(),
			'controller' => 'articles_test',
			'plugin' => 'articles_test',
			'action' => 'admin_index',
			'prefix' => 'admin',
			'admin' => true,
			'return' => 1
		);
		foreach ($expected as $key => $value) {
			$this->assertEquals($expected[$key], $Dispatcher->controller->request[$key], 'Value mismatch ' . $key);
		}
	}

/**
 * test Plugin dispatching without controller name and using
 * plugin short form instead.
 *
 * @return void
 */
	public function testAutomaticPluginDispatchWithShortAccess() {
		CakePlugin::load('MyPlugin', array('path' => '/fake/path'));
		Router::reload();

		$Dispatcher = new TestDispatcher();
		$Dispatcher->base = false;

		$url = new CakeRequest('my_plugin/');
		$response = $this->getMock('CakeResponse');

		$Dispatcher->dispatch($url, $response, array('return' => 1));
		$this->assertEquals('my_plugin', $Dispatcher->controller->params['controller']);
		$this->assertEquals('my_plugin', $Dispatcher->controller->params['plugin']);
		$this->assertEquals('index', $Dispatcher->controller->params['action']);
		$this->assertFalse(isset($Dispatcher->controller->params['pass'][0]));
	}

/**
 * test plugin shortcut urls with controllers that need to be loaded,
 * the above test uses a controller that has already been included.
 *
 * @return void
 */
	public function testPluginShortCutUrlsWithControllerThatNeedsToBeLoaded() {
		$loaded = class_exists('TestPluginController', false);
		$this->skipIf($loaded, 'TestPluginController already loaded.');

		Router::reload();
		App::build(array(
			'Plugin' => array(CAKE . 'Test' . DS . 'test_app' . DS . 'Plugin' . DS)
		), App::RESET);
		CakePlugin::load(array('TestPlugin', 'TestPluginTwo'));

		$Dispatcher = new TestDispatcher();
		$Dispatcher->base = false;

		$url = new CakeRequest('test_plugin/');
		$response = $this->getMock('CakeResponse');

		$Dispatcher->dispatch($url, $response, array('return' => 1));
		$this->assertEquals('test_plugin', $Dispatcher->controller->params['controller']);
		$this->assertEquals('test_plugin', $Dispatcher->controller->params['plugin']);
		$this->assertEquals('index', $Dispatcher->controller->params['action']);
		$this->assertFalse(isset($Dispatcher->controller->params['pass'][0]));

		$url = new CakeRequest('/test_plugin/tests/index');
		$Dispatcher->dispatch($url, $response, array('return' => 1));
		$this->assertEquals('tests', $Dispatcher->controller->params['controller']);
		$this->assertEquals('test_plugin', $Dispatcher->controller->params['plugin']);
		$this->assertEquals('index', $Dispatcher->controller->params['action']);
		$this->assertFalse(isset($Dispatcher->controller->params['pass'][0]));

		$url = new CakeRequest('/test_plugin/tests/index/some_param');
		$Dispatcher->dispatch($url, $response, array('return' => 1));
		$this->assertEquals('tests', $Dispatcher->controller->params['controller']);
		$this->assertEquals('test_plugin', $Dispatcher->controller->params['plugin']);
		$this->assertEquals('index', $Dispatcher->controller->params['action']);
		$this->assertEquals('some_param', $Dispatcher->controller->params['pass'][0]);

		App::build();
	}

/**
 * testAutomaticPluginControllerMissingActionDispatch method
 *
 * @expectedException MissingActionException
 * @expectedExceptionMessage Action MyPluginController::not_here() could not be found.
 * @return void
 */
	public function testAutomaticPluginControllerMissingActionDispatch() {
		Router::reload();
		$Dispatcher = new TestDispatcher();

		$url = new CakeRequest('my_plugin/not_here/param:value/param2:value2');
		$response = $this->getMock('CakeResponse');

		$Dispatcher->dispatch($url, $response, array('return' => 1));
	}

/**
 * testAutomaticPluginControllerMissingActionDispatch method
 *
 * @expectedException MissingActionException
 * @expectedExceptionMessage Action MyPluginController::param:value() could not be found.
 * @return void
 */

	public function testAutomaticPluginControllerIndexMissingAction() {
		Router::reload();
		$Dispatcher = new TestDispatcher();

		$url = new CakeRequest('my_plugin/param:value/param2:value2');
		$response = $this->getMock('CakeResponse');

		$Dispatcher->dispatch($url, $response, array('return' => 1));
	}

/**
 * Test dispatching into the TestPlugin in the test_app
 *
 * @return void
 */
	public function testTestPluginDispatch() {
		$Dispatcher = new TestDispatcher();
		App::build(array(
			'Plugin' => array(CAKE . 'Test' . DS . 'test_app' . DS . 'Plugin' . DS)
		), App::RESET);
		CakePlugin::load(array('TestPlugin', 'TestPluginTwo'));
		Router::reload();
		Router::parse('/');

		$url = new CakeRequest('/test_plugin/tests/index');
		$response = $this->getMock('CakeResponse');
		$Dispatcher->dispatch($url, $response, array('return' => 1));
		$this->assertTrue(class_exists('TestsController'));
		$this->assertTrue(class_exists('TestPluginAppController'));
		$this->assertTrue(class_exists('PluginsComponent'));

		$this->assertEquals('tests', $Dispatcher->controller->params['controller']);
		$this->assertEquals('test_plugin', $Dispatcher->controller->params['plugin']);
		$this->assertEquals('index', $Dispatcher->controller->params['action']);

		App::build();
	}

/**
 * Tests that it is possible to attach filter classes to the dispatch cycle
 *
 * @return void
 */
	public function testDispatcherFilterSubscriber() {
		App::build(array(
			'View' => array(CAKE . 'Test' . DS . 'test_app' . DS . 'View' . DS),
			'Plugin' => array(CAKE . 'Test' . DS . 'test_app' . DS . 'Plugin' . DS)
		), App::RESET);

		CakePlugin::load('TestPlugin');
		Configure::write('Dispatcher.filters', array(
			array('callable' => 'TestPlugin.TestDispatcherFilter')
		));
		$dispatcher = new TestDispatcher();
		$request = new CakeRequest('/');
		$request->params['altered'] = false;
		$response = $this->getMock('CakeResponse', array('send'));

		$dispatcher->dispatch($request, $response);
		$this->assertTrue($request->params['altered']);
		$this->assertEquals(304, $response->statusCode());

		Configure::write('Dispatcher.filters', array(
			'TestPlugin.Test2DispatcherFilter',
			'TestPlugin.TestDispatcherFilter'
		));
		$dispatcher = new TestDispatcher();
		$request = new CakeRequest('/');
		$request->params['altered'] = false;
		$response = $this->getMock('CakeResponse', array('send'));

		$dispatcher->dispatch($request, $response);
		$this->assertFalse($request->params['altered']);
		$this->assertEquals(500, $response->statusCode());
		$this->assertNull($dispatcher->controller);
	}

/**
 * Tests that attaching an inexistent class as filter will throw an exception
 *
 * @expectedException MissingDispatcherFilterException
 * @return void
 */
	public function testDispatcherFilterSuscriberMissing() {
		App::build(array(
			'Plugin' => array(CAKE . 'Test' . DS . 'test_app' . DS . 'Plugin' . DS)
		), App::RESET);

		CakePlugin::load('TestPlugin');
		Configure::write('Dispatcher.filters', array(
			array('callable' => 'TestPlugin.NotAFilter')
		));
		$dispatcher = new TestDispatcher();
		$request = new CakeRequest('/');
		$response = $this->getMock('CakeResponse', array('send'));
		$dispatcher->dispatch($request, $response);
	}

/**
 * Tests it is possible to attach single callables as filters
 *
 * @return void
 */
	public function testDispatcherFilterCallable() {
		App::build(array(
			'View' => array(CAKE . 'Test' . DS . 'test_app' . DS . 'View' . DS)
		), App::RESET);

		$dispatcher = new TestDispatcher();
		Configure::write('Dispatcher.filters', array(
			array('callable' => array($dispatcher, 'filterTest'), 'on' => 'before')
		));

		$request = new CakeRequest('/');
		$response = $this->getMock('CakeResponse', array('send'));
		$dispatcher->dispatch($request, $response);
		$this->assertEquals('Dispatcher.beforeDispatch', $request->params['eventName']);

		$dispatcher = new TestDispatcher();
		Configure::write('Dispatcher.filters', array(
			array('callable' => array($dispatcher, 'filterTest'), 'on' => 'after')
		));

		$request = new CakeRequest('/');
		$response = $this->getMock('CakeResponse', array('send'));
		$dispatcher->dispatch($request, $response);
		$this->assertEquals('Dispatcher.afterDispatch', $request->params['eventName']);

		// Test that it is possible to skip the route connection process
		$dispatcher = new TestDispatcher();
		Configure::write('Dispatcher.filters', array(
			array('callable' => array($dispatcher, 'filterTest2'), 'on' => 'before', 'priority' => 1)
		));

		$request = new CakeRequest('/');
		$response = $this->getMock('CakeResponse', array('send'));
		$dispatcher->dispatch($request, $response);
		$this->assertEmpty($dispatcher->controller);
		$expected = array('controller' => null, 'action' => null, 'plugin' => null, 'named' => array(), 'pass' => array());
		$this->assertEquals($expected, $request->params);

		$dispatcher = new TestDispatcher();
		Configure::write('Dispatcher.filters', array(
			array('callable' => array($dispatcher, 'filterTest2'), 'on' => 'before', 'priority' => 1)
		));

		$request = new CakeRequest('/');
		$request->params['return'] = true;
		$response = $this->getMock('CakeResponse', array('send'));
		$response->body('this is a body');
		$result = $dispatcher->dispatch($request, $response);
		$this->assertEquals('this is a body', $result);

		$request = new CakeRequest('/');
		$response = $this->getMock('CakeResponse', array('send'));
		$response->expects($this->once())->method('send');
		$response->body('this is a body');
		$result = $dispatcher->dispatch($request, $response);
		$this->assertNull($result);
	}

/**
 * testChangingParamsFromBeforeFilter method
 *
 * @return void
 */
	public function testChangingParamsFromBeforeFilter() {
		$Dispatcher = new TestDispatcher();
		$response = $this->getMock('CakeResponse');
		$url = new CakeRequest('some_posts/index/param:value/param2:value2');

		try {
			$Dispatcher->dispatch($url, $response, array('return' => 1));
			$this->fail('No exception.');
		} catch (MissingActionException $e) {
			$this->assertEquals('Action SomePostsController::view() could not be found.', $e->getMessage());
		}

		$url = new CakeRequest('some_posts/something_else/param:value/param2:value2');
		$Dispatcher->dispatch($url, $response, array('return' => 1));

		$expected = 'SomePosts';
		$this->assertEquals($expected, $Dispatcher->controller->name);

		$expected = 'change';
		$this->assertEquals($expected, $Dispatcher->controller->action);

		$expected = array('changed');
		$this->assertSame($expected, $Dispatcher->controller->params['pass']);
	}

/**
 * testStaticAssets method
 *
 * @return void
 */
	public function testAssets() {
		Router::reload();

		App::build(array(
			'Plugin' => array(CAKE . 'Test' . DS . 'test_app' . DS . 'Plugin' . DS),
			'Vendor' => array(CAKE . 'Test' . DS . 'test_app' . DS . 'Vendor' . DS),
			'View' => array(CAKE . 'Test' . DS . 'test_app' . DS . 'View' . DS)
		));
		CakePlugin::load(array('TestPlugin', 'TestPluginTwo'));
		Configure::write('Dispatcher.filters', array('AssetDispatcher'));

		$Dispatcher = new TestDispatcher();
		$response = $this->getMock('CakeResponse', array('_sendHeader'));

		try {
			$Dispatcher->dispatch(new CakeRequest('theme/test_theme/../webroot/css/test_asset.css'), $response);
			$this->fail('No exception');
		} catch (MissingControllerException $e) {
			$this->assertEquals('Controller class ThemeController could not be found.', $e->getMessage());
		}

		try {
			$Dispatcher->dispatch(new CakeRequest('theme/test_theme/pdfs'), $response);
			$this->fail('No exception');
		} catch (MissingControllerException $e) {
			$this->assertEquals('Controller class ThemeController could not be found.', $e->getMessage());
		}
	}

/**
 * Data provider for asset filter
 *
 * - theme assets.
 * - plugin assets.
 * - plugin assets in sub directories.
 * - unknown plugin assets.
 *
 * @return array
 */
	public static function assetProvider() {
		return array(
			array(
				'theme/test_theme/flash/theme_test.swf',
				'View/Themed/TestTheme/webroot/flash/theme_test.swf'
			),
			array(
				'theme/test_theme/pdfs/theme_test.pdf',
				'View/Themed/TestTheme/webroot/pdfs/theme_test.pdf'
			),
			array(
				'theme/test_theme/img/test.jpg',
				'View/Themed/TestTheme/webroot/img/test.jpg'
			),
			array(
				'theme/test_theme/css/test_asset.css',
				'View/Themed/TestTheme/webroot/css/test_asset.css'
			),
			array(
				'theme/test_theme/js/theme.js',
				'View/Themed/TestTheme/webroot/js/theme.js'
			),
			array(
				'theme/test_theme/js/one/theme_one.js',
				'View/Themed/TestTheme/webroot/js/one/theme_one.js'
			),
			array(
				'theme/test_theme/space%20image.text',
				'View/Themed/TestTheme/webroot/space image.text'
			),
			array(
				'test_plugin/root.js',
				'Plugin/TestPlugin/webroot/root.js'
			),
			array(
				'test_plugin/flash/plugin_test.swf',
				'Plugin/TestPlugin/webroot/flash/plugin_test.swf'
			),
			array(
				'test_plugin/pdfs/plugin_test.pdf',
				'Plugin/TestPlugin/webroot/pdfs/plugin_test.pdf'
			),
			array(
				'test_plugin/js/test_plugin/test.js',
				'Plugin/TestPlugin/webroot/js/test_plugin/test.js'
			),
			array(
				'test_plugin/css/test_plugin_asset.css',
				'Plugin/TestPlugin/webroot/css/test_plugin_asset.css'
			),
			array(
				'test_plugin/img/cake.icon.gif',
				'Plugin/TestPlugin/webroot/img/cake.icon.gif'
			),
			array(
				'plugin_js/js/plugin_js.js',
				'Plugin/PluginJs/webroot/js/plugin_js.js'
			),
			array(
				'plugin_js/js/one/plugin_one.js',
				'Plugin/PluginJs/webroot/js/one/plugin_one.js'
			),
			array(
				'test_plugin/css/unknown.extension',
				'Plugin/TestPlugin/webroot/css/unknown.extension'
			),
			array(
				'test_plugin/css/theme_one.htc',
				'Plugin/TestPlugin/webroot/css/theme_one.htc'
			),
		);
	}

/**
 * Test assets
 *
 * @dataProvider assetProvider
 * @outputBuffering enabled
 * @return void
 */
	public function testAsset($url, $file) {
		Router::reload();

		App::build(array(
			'Plugin' => array(CAKE . 'Test' . DS . 'test_app' . DS . 'Plugin' . DS),
			'Vendor' => array(CAKE . 'Test' . DS . 'test_app' . DS . 'Vendor' . DS),
			'View' => array(CAKE . 'Test' . DS . 'test_app' . DS . 'View' . DS)
		));
		CakePlugin::load(array('TestPlugin', 'PluginJs'));
		Configure::write('Dispatcher.filters', array('AssetDispatcher'));

		$Dispatcher = new TestDispatcher();
		$response = $this->getMock('CakeResponse', array('_sendHeader'));

		$Dispatcher->dispatch(new CakeRequest($url), $response);
		$result = ob_get_clean();

		$path = CAKE . 'Test' . DS . 'test_app' . DS . str_replace('/', DS, $file);
		$file = file_get_contents($path);
		$this->assertEquals($file, $result);

		$expected = filesize($path);
		$headers = $response->header();
		$this->assertEquals($expected, $headers['Content-Length']);
	}

/**
 * test that missing asset processors trigger a 404 with no response body.
 *
 * @return void
 */
	public function testMissingAssetProcessor404() {
		$response = $this->getMock('CakeResponse', array('send'));
		$Dispatcher = new TestDispatcher();
		Configure::write('Asset.filter', array(
			'js' => '',
			'css' => null
		));
		Configure::write('Dispatcher.filters', array('AssetDispatcher'));

		$request = new CakeRequest('ccss/cake.generic.css');
		$Dispatcher->dispatch($request, $response);
		$this->assertEquals('404', $response->statusCode());
	}

/**
 * Data provider for cached actions.
 *
 * - Test simple views
 * - Test views with nocache tags
 * - Test requests with named + passed params.
 * - Test requests with query string params
 * - Test themed views.
 *
 * @return array
 */
	public static function cacheActionProvider() {
		return array(
			array('/'),
			array('test_cached_pages/index'),
			array('TestCachedPages/index'),
			array('test_cached_pages/test_nocache_tags'),
			array('TestCachedPages/test_nocache_tags'),
			array('test_cached_pages/view/param/param'),
			array('test_cached_pages/view/foo:bar/value:goo'),
			array('test_cached_pages/view?q=cakephp'),
			array('test_cached_pages/themed'),
		);
	}

/**
 * testFullPageCachingDispatch method
 *
 * @dataProvider cacheActionProvider
 * @return void
 */
	public function testFullPageCachingDispatch($url) {
		Configure::write('Cache.disable', false);
		Configure::write('Cache.check', true);
		Configure::write('debug', 2);

		Router::reload();
		Router::connect('/', array('controller' => 'test_cached_pages', 'action' => 'index'));
		Router::connect('/:controller/:action/*');

		App::build(array(
			'View' => array(CAKE . 'Test' . DS . 'test_app' . DS . 'View' . DS),
		), App::RESET);

		$dispatcher = new TestDispatcher();
		$request = new CakeRequest($url);
		$response = $this->getMock('CakeResponse', array('send'));

		$dispatcher->dispatch($request, $response);
		$out = $response->body();

		Configure::write('Dispatcher.filters', array('CacheDispatcher'));
		$request = new CakeRequest($url);
		$response = $this->getMock('CakeResponse', array('send'));
		$dispatcher = new TestDispatcher();
		$dispatcher->dispatch($request, $response);
		$cached = $response->body();

		$cached = preg_replace('/<!--+[^<>]+-->/', '', $cached);

		$this->assertTextEquals($out, $cached);

		$filename = $this->_cachePath($request->here());
		unlink($filename);
	}

/**
 * testHttpMethodOverrides method
 *
 * @return void
 */
	public function testHttpMethodOverrides() {
		Router::reload();
		Router::mapResources('Posts');

		$_SERVER['REQUEST_METHOD'] = 'POST';
		$dispatcher = new Dispatcher();

		$request = new CakeRequest('/posts');
		$event = new CakeEvent('DispatcherTest', $dispatcher, array('request' => $request));
		$dispatcher->parseParams($event);
		$expected = array('pass' => array(), 'named' => array(), 'plugin' => null, 'controller' => 'posts', 'action' => 'add', '[method]' => 'POST');
		foreach ($expected as $key => $value) {
			$this->assertEquals($value, $request[$key], 'Value mismatch for ' . $key . ' %s');
		}

		$_SERVER['REQUEST_METHOD'] = 'GET';
		$_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE'] = 'PUT';

		$request = new CakeRequest('/posts/5');
		$event = new CakeEvent('DispatcherTest', $dispatcher, array('request' => $request));
		$dispatcher->parseParams($event);
		$expected = array(
			'pass' => array('5'),
			'named' => array(),
			'id' => '5',
			'plugin' => null,
			'controller' => 'posts',
			'action' => 'edit',
			'[method]' => 'PUT'
		);
		foreach ($expected as $key => $value) {
			$this->assertEquals($value, $request[$key], 'Value mismatch for ' . $key . ' %s');
		}

		unset($_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE']);
		$_SERVER['REQUEST_METHOD'] = 'GET';

		$request = new CakeRequest('/posts/5');
		$event = new CakeEvent('DispatcherTest', $dispatcher, array('request' => $request));
		$dispatcher->parseParams($event);
		$expected = array('pass' => array('5'), 'named' => array(), 'id' => '5', 'plugin' => null, 'controller' => 'posts', 'action' => 'view', '[method]' => 'GET');
		foreach ($expected as $key => $value) {
			$this->assertEquals($value, $request[$key], 'Value mismatch for ' . $key . ' %s');
		}

		$_POST['_method'] = 'PUT';

		$request = new CakeRequest('/posts/5');
		$event = new CakeEvent('DispatcherTest', $dispatcher, array('request' => $request));
		$dispatcher->parseParams($event);
		$expected = array('pass' => array('5'), 'named' => array(), 'id' => '5', 'plugin' => null, 'controller' => 'posts', 'action' => 'edit', '[method]' => 'PUT');
		foreach ($expected as $key => $value) {
			$this->assertEquals($value, $request[$key], 'Value mismatch for ' . $key . ' %s');
		}

		$_POST['_method'] = 'POST';
		$_POST['data'] = array('Post' => array('title' => 'New Post'));
		$_POST['extra'] = 'data';
		$_SERVER = array();

		$request = new CakeRequest('/posts');
		$event = new CakeEvent('DispatcherTest', $dispatcher, array('request' => $request));
		$dispatcher->parseParams($event);
		$expected = array(
			'pass' => array(), 'named' => array(), 'plugin' => null, 'controller' => 'posts', 'action' => 'add',
			'[method]' => 'POST', 'data' => array('extra' => 'data', 'Post' => array('title' => 'New Post')),
		);
		foreach ($expected as $key => $value) {
			$this->assertEquals($value, $request[$key], 'Value mismatch for ' . $key . ' %s');
		}

		unset($_POST['_method']);
	}

/**
 * cachePath method
 *
 * @param string $here
 * @return string
 */
	protected function _cachePath($here) {
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
