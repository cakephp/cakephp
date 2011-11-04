<?php
/**
 * DispatcherTest file
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
 * @package       Cake.Test.Case.Routing
 * @since         CakePHP(tm) v 1.2.0.4206
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
App::uses('Dispatcher', 'Routing');

if (!class_exists('AppController', false)) {
	require_once CAKE . 'Controller' . DS . 'AppController.php';
} elseif (!defined('APP_CONTROLLER_EXISTS')){
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
 * invoke method
 *
 * @param mixed $controller
 * @param mixed $request
 * @return void
 */
	protected function _invoke(Controller $controller, CakeRequest $request, CakeResponse $response) {
		$result = parent::_invoke($controller, $request, $response);
		return $controller;
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
 * @param mixed $page
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
 * @param mixed $page
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
 * Mock out the reponse object so it doesn't send headers.
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
	}

/**
 * testParseParamsWithoutZerosAndEmptyPost method
 *
 * @return void
 */
	public function testParseParamsWithoutZerosAndEmptyPost() {
		$Dispatcher = new Dispatcher();

		$test = $Dispatcher->parseParams(new CakeRequest("/testcontroller/testaction/params1/params2/params3"));
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
 */
	public function testParseParamsReturnsPostedData() {
		$_POST['testdata'] = "My Posted Content";
		$Dispatcher = new Dispatcher();

		$test = $Dispatcher->parseParams(new CakeRequest("/"));
		$this->assertEquals($test['data']['testdata'], "My Posted Content");
	}

/**
 * testParseParamsWithSingleZero method
 *
 * @return void
 */
	public function testParseParamsWithSingleZero() {
		$Dispatcher = new Dispatcher();
		$test = $Dispatcher->parseParams(new CakeRequest("/testcontroller/testaction/1/0/23"));
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
 */
	public function testParseParamsWithManySingleZeros() {
		$Dispatcher = new Dispatcher();
		$test = $Dispatcher->parseParams(new CakeRequest("/testcontroller/testaction/0/0/0/0/0/0"));
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
 */
	public function testParseParamsWithManyZerosInEachSectionOfUrl() {
		$Dispatcher = new Dispatcher();
		$request = new CakeRequest("/testcontroller/testaction/000/0000/00000/000000/000000/0000000");
		$test = $Dispatcher->parseParams($request);
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
 */
	public function testParseParamsWithMixedOneToManyZerosInEachSectionOfUrl() {
		$Dispatcher = new Dispatcher();

		$request = new CakeRequest("/testcontroller/testaction/01/0403/04010/000002/000030/0000400");
		$test = $Dispatcher->parseParams($request);
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
 */
	public function testQueryStringOnRoot() {
		Router::reload();
		Router::connect('/', array('controller' => 'pages', 'action' => 'display', 'home'));
		Router::connect('/pages/*', array('controller' => 'pages', 'action' => 'display'));
		Router::connect('/:controller/:action/*');

		$_GET = array('coffee' => 'life', 'sleep' => 'sissies');
		$Dispatcher = new Dispatcher();
		$uri = new CakeRequest('posts/home/?coffee=life&sleep=sissies');
		$result = $Dispatcher->parseParams($uri);
		$this->assertPattern('/posts/', $result['controller']);
		$this->assertPattern('/home/', $result['action']);
		$this->assertTrue(isset($result['url']['sleep']));
		$this->assertTrue(isset($result['url']['coffee']));

		$Dispatcher = new Dispatcher();
		$uri = new CakeRequest('/?coffee=life&sleep=sissy');

		$result = $Dispatcher->parseParams($uri);
		$this->assertPattern('/pages/', $result['controller']);
		$this->assertPattern('/display/', $result['action']);
		$this->assertTrue(isset($result['url']['sleep']));
		$this->assertTrue(isset($result['url']['coffee']));
		$this->assertEqual($result['url']['coffee'], 'life');
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

		$controller = $Dispatcher->dispatch($url, $response, array('return' => 1));
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

		$controller = $Dispatcher->dispatch($url, $response, array('return' => 1));
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

		$controller = $Dispatcher->dispatch($url, $response, array('return' => 1));
	}
/**
 * testDispatch method
 *
 * @return void
 */
	public function testDispatchBasic() {
		App::build(array(
			'View' => array(CAKE . 'Test' . DS . 'test_app' . DS . 'View'. DS)
		));
		$Dispatcher = new TestDispatcher();
		Configure::write('App.baseUrl', '/index.php');
		$url = new CakeRequest('pages/home/param:value/param2:value2');
		$response = $this->getMock('CakeResponse');

		$controller = $Dispatcher->dispatch($url, $response, array('return' => 1));
		$expected = 'Pages';
		$this->assertEqual($expected, $controller->name);

		$expected = array('0' => 'home', 'param' => 'value', 'param2' => 'value2');
		$this->assertIdentical($expected, $controller->passedArgs);

		Configure::write('App.baseUrl','/pages/index.php');

		$url = new CakeRequest('pages/home');
		$controller = $Dispatcher->dispatch($url, $response, array('return' => 1));

		$expected = 'Pages';
		$this->assertEqual($expected, $controller->name);

		$url = new CakeRequest('pages/home/');
		$controller = $Dispatcher->dispatch($url, $response, array('return' => 1));
		$this->assertNull($controller->plugin);

		$expected = 'Pages';
		$this->assertEqual($expected, $controller->name);

		unset($Dispatcher);

		require CAKE . 'Config' . DS . 'routes.php';
		$Dispatcher = new TestDispatcher();
		Configure::write('App.baseUrl', '/timesheets/index.php');

		$url = new CakeRequest('timesheets');
		$controller = $Dispatcher->dispatch($url, $response, array('return' => 1));

		$expected = 'Timesheets';
		$this->assertEqual($expected, $controller->name);

		$url = new CakeRequest('timesheets/');
		$controller = $Dispatcher->dispatch($url, $response, array('return' => 1));

		$this->assertEqual('Timesheets', $controller->name);
		$this->assertEqual('/timesheets/index.php', $url->base);

		$url = new CakeRequest('test_dispatch_pages/camelCased');
		$controller = $Dispatcher->dispatch($url, $response, array('return' => 1));
		$this->assertEqual('TestDispatchPages', $controller->name);

		$url = new CakeRequest('test_dispatch_pages/camelCased/something. .');
		$controller = $Dispatcher->dispatch($url, $response, array('return' => 1));
		$this->assertEqual($controller->params['pass'][0], 'something. .', 'Period was chopped off. %s');
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
		$controller = $Dispatcher->dispatch($url, $response, array('return' => 1));

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
		$controller = $Dispatcher->dispatch($url, $response, array('return' => 1));

		$result = $Dispatcher->parseParams($url);
		$expected = array(
			'pass' => array('home'),
			'named' => array('param'=> 'value', 'param2'=> 'value2'), 'plugin'=> 'my_plugin',
			'controller'=> 'some_pages', 'action'=> 'display'
		);
		foreach ($expected as $key => $value) {
			$this->assertEqual($result[$key], $value, 'Value mismatch ' . $key . ' %');
		}

		$this->assertIdentical($controller->plugin, 'MyPlugin');
		$this->assertIdentical($controller->name, 'SomePages');
		$this->assertIdentical($controller->params['controller'], 'some_pages');
		$this->assertIdentical($controller->passedArgs, array('0' => 'home', 'param'=>'value', 'param2'=>'value2'));
	}

/**
 * testAutomaticPluginDispatch method
 *
 * @return void
 */
	public function testAutomaticPluginDispatch() {
		$_POST = array();
		$_SERVER['SCRIPT_NAME'] = '/cake/repo/branches/1.2.x.x/index.php';

		Router::reload();
		$Dispatcher = new TestDispatcher();
		Router::connect(
			'/my_plugin/:controller/:action/*',
			array('plugin' => 'my_plugin', 'controller' => 'pages', 'action' => 'display')
		);

		$Dispatcher->base = false;

		$url = new CakeRequest('my_plugin/other_pages/index/param:value/param2:value2');
		$response = $this->getMock('CakeResponse');
		$controller = $Dispatcher->dispatch($url, $response, array('return' => 1));

		$this->assertIdentical($controller->plugin, 'MyPlugin');
		$this->assertIdentical($controller->name, 'OtherPages');
		$this->assertIdentical($controller->action, 'index');
		$this->assertIdentical($controller->passedArgs, array('param' => 'value', 'param2' => 'value2'));

		$expected = '/cake/repo/branches/1.2.x.x/my_plugin/other_pages/index/param:value/param2:value2';
		$this->assertIdentical($expected, $url->here);

		$expected = '/cake/repo/branches/1.2.x.x';
		$this->assertIdentical($expected, $url->base);
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

		$controller = $Dispatcher->dispatch($url, $response, array('return' => 1));

		$this->assertIdentical($controller->plugin, 'MyPlugin');
		$this->assertIdentical($controller->name, 'MyPlugin');
		$this->assertIdentical($controller->action, 'add');
		$this->assertEqual($controller->params['named'], array('param' => 'value', 'param2' => 'value2'));


		Router::reload();
		require CAKE . 'Config' . DS . 'routes.php';
		$Dispatcher = new TestDispatcher();
		$Dispatcher->base = false;

		// Simulates the Route for a real plugin, installed in APP/plugins
		Router::connect('/my_plugin/:controller/:action/*', array('plugin' => 'my_plugin'));

		$plugin = 'MyPlugin';
		$pluginUrl = Inflector::underscore($plugin);

		$url = new CakeRequest($pluginUrl);
		$controller = $Dispatcher->dispatch($url, $response, array('return' => 1));
		$this->assertIdentical($controller->plugin, 'MyPlugin');
		$this->assertIdentical($controller->name, 'MyPlugin');
		$this->assertIdentical($controller->action, 'index');

		$expected = $pluginUrl;
		$this->assertEqual($controller->params['controller'], $expected);


		Configure::write('Routing.prefixes', array('admin'));

		Router::reload();
		require CAKE . 'Config' . DS . 'routes.php';
		$Dispatcher = new TestDispatcher();

		$url = new CakeRequest('admin/my_plugin/my_plugin/add/5/param:value/param2:value2');
		$response = $this->getMock('CakeResponse');

		$controller = $Dispatcher->dispatch($url, $response, array('return' => 1));

		$this->assertEqual($controller->params['plugin'], 'my_plugin');
		$this->assertEqual($controller->params['controller'], 'my_plugin');
		$this->assertEqual($controller->params['action'], 'admin_add');
		$this->assertEqual($controller->params['pass'], array(5));
		$this->assertEqual($controller->params['named'], array('param' => 'value', 'param2' => 'value2'));
		$this->assertIdentical($controller->plugin, 'MyPlugin');
		$this->assertIdentical($controller->name, 'MyPlugin');
		$this->assertIdentical($controller->action, 'admin_add');

		$expected = array(0 => 5, 'param'=>'value', 'param2'=>'value2');
		$this->assertEqual($controller->passedArgs, $expected);

		Configure::write('Routing.prefixes', array('admin'));
		CakePlugin::load('ArticlesTest', array('path' => '/fake/path'));
		Router::reload();
		require CAKE . 'Config' . DS . 'routes.php';

		$Dispatcher = new TestDispatcher();

		$controller = $Dispatcher->dispatch(new CakeRequest('admin/articles_test'), $response, array('return' => 1));
		$this->assertIdentical($controller->plugin, 'ArticlesTest');
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
			'return' => 1
		);
		foreach ($expected as $key => $value) {
			$this->assertEqual($controller->request[$key], $expected[$key], 'Value mismatch ' . $key);
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

		$controller = $Dispatcher->dispatch($url, $response, array('return' => 1));
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
	public function testPluginShortCutUrlsWithControllerThatNeedsToBeLoaded() {
		$loaded = class_exists('TestPluginController', false);
		$this->skipIf($loaded, 'TestPluginController already loaded.');

		Router::reload();
		App::build(array(
			'plugins' => array(CAKE . 'Test' . DS . 'test_app' . DS . 'Plugin' . DS)
		), true);
		CakePlugin::loadAll();

		$Dispatcher = new TestDispatcher();
		$Dispatcher->base = false;

		$url = new CakeRequest('test_plugin/');
		$response = $this->getMock('CakeResponse');

		$controller = $Dispatcher->dispatch($url, $response, array('return' => 1));
		$this->assertEqual($controller->params['controller'], 'test_plugin');
		$this->assertEqual($controller->params['plugin'], 'test_plugin');
		$this->assertEqual($controller->params['action'], 'index');
		$this->assertFalse(isset($controller->params['pass'][0]));

		$url = new CakeRequest('/test_plugin/tests/index');
		$controller = $Dispatcher->dispatch($url, $response, array('return' => 1));
		$this->assertEqual($controller->params['controller'], 'tests');
		$this->assertEqual($controller->params['plugin'], 'test_plugin');
		$this->assertEqual($controller->params['action'], 'index');
		$this->assertFalse(isset($controller->params['pass'][0]));

		$url = new CakeRequest('/test_plugin/tests/index/some_param');
		$controller = $Dispatcher->dispatch($url, $response, array('return' => 1));
		$this->assertEqual($controller->params['controller'], 'tests');
		$this->assertEqual($controller->params['plugin'], 'test_plugin');
		$this->assertEqual($controller->params['action'], 'index');
		$this->assertEqual($controller->params['pass'][0], 'some_param');

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

		$controller = $Dispatcher->dispatch($url, $response, array('return'=> 1));
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

		$controller = $Dispatcher->dispatch($url, $response, array('return'=> 1));
	}

/**
 * Test dispatching into the TestPlugin in the test_app
 *
 * @return void
 */
	public function testTestPluginDispatch() {
		$Dispatcher = new TestDispatcher();
		App::build(array(
			'plugins' => array(CAKE . 'Test' . DS . 'test_app' . DS . 'Plugin' . DS)
		), APP::RESET);
		CakePlugin::loadAll();
		Router::reload();
		Router::parse('/');

		$url = new CakeRequest('/test_plugin/tests/index');
		$response = $this->getMock('CakeResponse');
		$result = $Dispatcher->dispatch($url, $response, array('return' => 1));
		$this->assertTrue(class_exists('TestsController'));
		$this->assertTrue(class_exists('TestPluginAppController'));
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
 */
	public function testChangingParamsFromBeforeFilter() {
		$Dispatcher = new TestDispatcher();
		$response = $this->getMock('CakeResponse');
		$url = new CakeRequest('some_posts/index/param:value/param2:value2');

		try {
			$controller = $Dispatcher->dispatch($url, $response, array('return'=> 1));
			$this->fail('No exception.');
		} catch (MissingActionException $e) {
			$this->assertEquals('Action SomePostsController::view() could not be found.', $e->getMessage());
		}

		$url = new CakeRequest('some_posts/something_else/param:value/param2:value2');
		$controller = $Dispatcher->dispatch($url, $response, array('return' => 1));

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
 */
	public function testAssets() {
		Router::reload();

		App::build(array(
			'plugins' => array(CAKE . 'Test' . DS . 'test_app' . DS . 'Plugin' . DS),
			'vendors' => array(CAKE . 'Test' . DS . 'test_app' . DS . 'Vendor'. DS),
			'View' => array(CAKE . 'Test' . DS . 'test_app' . DS . 'View'. DS)
		));
		CakePlugin::loadAll();

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

		ob_start();
		$Dispatcher->dispatch(new CakeRequest('theme/test_theme/flash/theme_test.swf'), $response);
		$result = ob_get_clean();

		$file = file_get_contents(CAKE . 'Test' . DS . 'test_app' . DS . 'View' . DS . 'Themed' . DS . 'TestTheme' . DS . 'webroot' . DS . 'flash' . DS . 'theme_test.swf');
		$this->assertEqual($file, $result);
		$this->assertEqual('this is just a test to load swf file from the theme.', $result);

		ob_start();
		$Dispatcher->dispatch(new CakeRequest('theme/test_theme/pdfs/theme_test.pdf'), $response);
		$result = ob_get_clean();
		$file = file_get_contents(CAKE . 'Test' . DS . 'test_app' . DS . 'View' . DS . 'Themed' . DS . 'TestTheme' . DS . 'webroot' . DS . 'pdfs' . DS . 'theme_test.pdf');
		$this->assertEqual($file, $result);
		$this->assertEqual('this is just a test to load pdf file from the theme.', $result);

		ob_start();
		$Dispatcher->dispatch(new CakeRequest('theme/test_theme/img/test.jpg'), $response);
		$result = ob_get_clean();
		$file = file_get_contents(CAKE . 'Test' . DS . 'test_app' . DS . 'View' . DS . 'Themed' . DS . 'TestTheme' . DS . 'webroot' . DS . 'img' . DS . 'test.jpg');
		$this->assertEqual($file, $result);

		ob_start();
		$Dispatcher->asset('theme/test_theme/css/test_asset.css', $response);
		$result = ob_get_clean();
		$this->assertEqual('this is the test asset css file', $result);

		ob_start();
		$Dispatcher->asset('theme/test_theme/js/theme.js', $response);
		$result = ob_get_clean();
		$this->assertEqual('root theme js file', $result);

		ob_start();
		$Dispatcher->asset('theme/test_theme/js/one/theme_one.js', $response);
		$result = ob_get_clean();
		$this->assertEqual('nested theme js file', $result);

		ob_start();
		$Dispatcher->asset('test_plugin/root.js', $response);
		$result = ob_get_clean();
		$expected = file_get_contents(CAKE . 'Test' . DS . 'test_app' . DS . 'Plugin' . DS . 'TestPlugin' . DS . 'webroot' . DS . 'root.js');
		$this->assertEqual($expected, $result);

		ob_start();
		$Dispatcher->dispatch(new CakeRequest('test_plugin/flash/plugin_test.swf'), $response);
		$result = ob_get_clean();
		$file = file_get_contents(CAKE . 'Test' . DS . 'test_app' . DS . 'Plugin' . DS . 'TestPlugin' . DS . 'webroot' . DS . 'flash' . DS . 'plugin_test.swf');
		$this->assertEqual($file, $result);
		$this->assertEqual('this is just a test to load swf file from the plugin.', $result);

		ob_start();
		$Dispatcher->dispatch(new CakeRequest('test_plugin/pdfs/plugin_test.pdf'), $response);
		$result = ob_get_clean();
		$file = file_get_contents(CAKE . 'Test' . DS . 'test_app' . DS . 'Plugin' . DS . 'TestPlugin' . DS . 'webroot' . DS . 'pdfs' . DS . 'plugin_test.pdf');
		$this->assertEqual($file, $result);
		 $this->assertEqual('this is just a test to load pdf file from the plugin.', $result);

		ob_start();
		$Dispatcher->asset('test_plugin/js/test_plugin/test.js', $response);
		$result = ob_get_clean();
		$this->assertEqual('alert("Test App");', $result);

		ob_start();
		$Dispatcher->asset('test_plugin/js/test_plugin/test.js', $response);
		$result = ob_get_clean();
		$this->assertEqual('alert("Test App");', $result);

		ob_start();
		$Dispatcher->asset('test_plugin/css/test_plugin_asset.css', $response);
		$result = ob_get_clean();
		$this->assertEqual('this is the test plugin asset css file', $result);

		ob_start();
		$Dispatcher->asset('test_plugin/img/cake.icon.gif', $response);
		$result = ob_get_clean();
		$file = file_get_contents(CAKE . 'Test' . DS . 'test_app' . DS . 'Plugin' . DS . 'TestPlugin' .DS . 'webroot' . DS . 'img' . DS . 'cake.icon.gif');
		$this->assertEqual($file, $result);

		ob_start();
		$Dispatcher->asset('plugin_js/js/plugin_js.js', $response);
		$result = ob_get_clean();
		$expected = "alert('win sauce');";
		$this->assertEqual($expected, $result);

		ob_start();
		$Dispatcher->asset('plugin_js/js/one/plugin_one.js', $response);
		$result = ob_get_clean();
		$expected = "alert('plugin one nested js file');";
		$this->assertEqual($expected, $result);

		ob_start();
		$Dispatcher->asset('test_plugin/css/unknown.extension', $response);
		$result = ob_get_clean();
		$this->assertEqual('Testing a file with unknown extension to mime mapping.', $result);

		ob_start();
		$Dispatcher->asset('test_plugin/css/theme_one.htc', $response);
		$result = ob_get_clean();
		$this->assertEqual('htc file', $result);

		$response = $this->getMock('CakeResponse', array('_sendHeader'));
		ob_start();
		$Dispatcher->asset('test_plugin/css/unknown.extension', $response);
		ob_end_clean();
		$expected = filesize(CakePlugin::path('TestPlugin') . 'webroot' . DS . 'css' . DS . 'unknown.extension');
		$headers = $response->header();
		$this->assertEqual($expected, $headers['Content-Length']);

		if (php_sapi_name() == 'cli') {
			while (ob_get_level()) {
				ob_get_clean();
			}
		}
	}

/**
 * test that missing asset processors trigger a 404 with no response body.
 *
 * @return void
 */
	public function testMissingAssetProcessor404() {
		$response = $this->getMock('CakeResponse', array('_sendHeader'));
		$Dispatcher = new TestDispatcher();
		Configure::write('Asset.filter', array(
			'js' => '',
			'css' => null
		));

		ob_start();
		$this->assertTrue($Dispatcher->asset('ccss/cake.generic.css', $response));
		$result = ob_get_clean();
	}

/**
 * test that asset filters work for theme and plugin assets
 *
 * @return void
 */
	public function testAssetFilterForThemeAndPlugins() {
		$Dispatcher = new TestDispatcher();
		$response = $this->getMock('CakeResponse', array('_sendHeader'));
		Configure::write('Asset.filter', array(
			'js' => '',
			'css' => ''
		));
		$this->assertTrue($Dispatcher->asset('theme/test_theme/ccss/cake.generic.css', $response));

		$this->assertTrue($Dispatcher->asset('theme/test_theme/cjs/debug_kit.js', $response));

		$this->assertTrue($Dispatcher->asset('test_plugin/ccss/cake.generic.css', $response));

		$this->assertTrue($Dispatcher->asset('test_plugin/cjs/debug_kit.js', $response));

		$this->assertFalse($Dispatcher->asset('css/ccss/debug_kit.css', $response));

		$this->assertFalse($Dispatcher->asset('js/cjs/debug_kit.js', $response));
	}
/**
 * testFullPageCachingDispatch method
 *
 * @return void
 */
	public function testFullPageCachingDispatch() {
		Configure::write('Cache.disable', false);
		Configure::write('Cache.check', true);
		Configure::write('debug', 2);


		Router::reload();
		Router::connect('/', array('controller' => 'test_cached_pages', 'action' => 'index'));
		Router::connect('/:controller/:action/*');

		App::build(array(
			'View' => array(CAKE . 'Test' . DS . 'test_app' . DS . 'View' . DS),
		), true);

		$dispatcher = new TestDispatcher();
		$request = new CakeRequest('/');
		$response = new CakeResponse();

		ob_start();
		$dispatcher->dispatch($request, $response);
		$out = ob_get_clean();

		ob_start();
		$dispatcher->cached($request->here);
		$cached = ob_get_clean();

		$result = str_replace(array("\t", "\r\n", "\n"), "", $out);
		$cached = preg_replace('/<!--+[^<>]+-->/', '', $cached);
		$expected =  str_replace(array("\t", "\r\n", "\n"), "", $cached);

		$this->assertEqual($expected, $result);

		$filename = $this->__cachePath($request->here);
		unlink($filename);

		$request = new CakeRequest('test_cached_pages/index');
		$_POST = array(
			'slasher' => "Up in your's grill \ '"
		);

		ob_start();
		$dispatcher->dispatch($request, $response);
		$out = ob_get_clean();

		ob_start();
		$dispatcher->cached($request->here);
		$cached = ob_get_clean();

		$result = str_replace(array("\t", "\r\n", "\n"), "", $out);
		$cached = preg_replace('/<!--+[^<>]+-->/', '', $cached);
		$expected =  str_replace(array("\t", "\r\n", "\n"), "", $cached);

		$this->assertEqual($expected, $result);
		$filename = $this->__cachePath($request->here);
		unlink($filename);

		$request = new CakeRequest('TestCachedPages/index');

		ob_start();
		$dispatcher->dispatch($request, $response);
		$out = ob_get_clean();

		ob_start();
		$dispatcher->cached($request->here);
		$cached = ob_get_clean();

		$result = str_replace(array("\t", "\r\n", "\n"), "", $out);
		$cached = preg_replace('/<!--+[^<>]+-->/', '', $cached);
		$expected =  str_replace(array("\t", "\r\n", "\n"), "", $cached);

		$this->assertEqual($expected, $result);
		$filename = $this->__cachePath($request->here);
		unlink($filename);

		$request = new CakeRequest('TestCachedPages/test_nocache_tags');

		ob_start();
		$dispatcher->dispatch($request, $response);
		$out = ob_get_clean();

		ob_start();
		$dispatcher->cached($request->here);
		$cached = ob_get_clean();

		$result = str_replace(array("\t", "\r\n", "\n"), "", $out);
		$cached = preg_replace('/<!--+[^<>]+-->/', '', $cached);
		$expected =  str_replace(array("\t", "\r\n", "\n"), "", $cached);

		$this->assertEqual($expected, $result);
		$filename = $this->__cachePath($request->here);
		unlink($filename);

		$request = new CakeRequest('test_cached_pages/view/param/param');

		ob_start();
		$dispatcher->dispatch($request, $response);
		$out = ob_get_clean();

		ob_start();
		$dispatcher->cached($request->here);
		$cached = ob_get_clean();

		$result = str_replace(array("\t", "\r\n", "\n"), "", $out);
		$cached = preg_replace('/<!--+[^<>]+-->/', '', $cached);
		$expected =  str_replace(array("\t", "\r\n", "\n"), "", $cached);

		$this->assertEqual($expected, $result);
		$filename = $this->__cachePath($request->here);
		unlink($filename);

		$request = new CakeRequest('test_cached_pages/view/foo:bar/value:goo');

		ob_start();
		$dispatcher->dispatch($request, $response);
		$out = ob_get_clean();

		ob_start();
		$dispatcher->cached($request->here);
		$cached = ob_get_clean();

		$result = str_replace(array("\t", "\r\n", "\n"), "", $out);
		$cached = preg_replace('/<!--+[^<>]+-->/', '', $cached);
		$expected =  str_replace(array("\t", "\r\n", "\n"), "", $cached);

		$this->assertEqual($expected, $result);
		$filename = $this->__cachePath($request->here);
		$this->assertTrue(file_exists($filename));

		unlink($filename);
	}

/**
 * Test full page caching with themes.
 *
 * @return void
 */
	public function testFullPageCachingWithThemes() {
		Configure::write('Cache.disable', false);
		Configure::write('Cache.check', true);
		Configure::write('debug', 2);

		Router::reload();
		Router::connect('/:controller/:action/*');

		App::build(array(
			'View' => array(CAKE . 'Test' . DS . 'test_app' . DS . 'View' . DS),
		), true);

		$dispatcher = new TestDispatcher();
		$request = new CakeRequest('/test_cached_pages/themed');
		$response = new CakeResponse();

		ob_start();
		$dispatcher->dispatch($request, $response);
		$out = ob_get_clean();

		ob_start();
		$dispatcher->cached($request->here);
		$cached = ob_get_clean();

		$result = str_replace(array("\t", "\r\n", "\n"), "", $out);
		$cached = preg_replace('/<!--+[^<>]+-->/', '', $cached);
		$expected =  str_replace(array("\t", "\r\n", "\n"), "", $cached);

		$this->assertEqual($expected, $result);

		$filename = $this->__cachePath($request->here);
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

		$result = $dispatcher->parseParams(new CakeRequest('/posts'));
		$expected = array('pass' => array(), 'named' => array(), 'plugin' => null, 'controller' => 'posts', 'action' => 'add', '[method]' => 'POST');
		foreach ($expected as $key => $value) {
			$this->assertEqual($result[$key], $value, 'Value mismatch for ' . $key . ' %s');
		}

		$_SERVER['REQUEST_METHOD'] = 'GET';
		$_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE'] = 'PUT';

		$result = $dispatcher->parseParams(new CakeRequest('/posts/5'));
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
			$this->assertEqual($result[$key], $value, 'Value mismatch for ' . $key . ' %s');
		}

		unset($_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE']);
		$_SERVER['REQUEST_METHOD'] = 'GET';

		$result = $dispatcher->parseParams(new CakeRequest('/posts/5'));
		$expected = array('pass' => array('5'), 'named' => array(), 'id' => '5', 'plugin' => null, 'controller' => 'posts', 'action' => 'view', '[method]' => 'GET');
		foreach ($expected as $key => $value) {
			$this->assertEqual($result[$key], $value, 'Value mismatch for ' . $key . ' %s');
		}

		$_POST['_method'] = 'PUT';

		$result = $dispatcher->parseParams(new CakeRequest('/posts/5'));
		$expected = array('pass' => array('5'), 'named' => array(), 'id' => '5', 'plugin' => null, 'controller' => 'posts', 'action' => 'edit', '[method]' => 'PUT');
		foreach ($expected as $key => $value) {
			$this->assertEqual($result[$key], $value, 'Value mismatch for ' . $key . ' %s');
		}

		$_POST['_method'] = 'POST';
		$_POST['data'] = array('Post' => array('title' => 'New Post'));
		$_POST['extra'] = 'data';
		$_SERVER = array();

		$result = $dispatcher->parseParams(new CakeRequest('/posts'));
		$expected = array(
			'pass' => array(), 'named' => array(), 'plugin' => null, 'controller' => 'posts', 'action' => 'add',
			'[method]' => 'POST', 'data' => array('extra' => 'data', 'Post' => array('title' => 'New Post')),
		);
		foreach ($expected as $key => $value) {
			$this->assertEqual($result[$key], $value, 'Value mismatch for ' . $key . ' %s');
		}

		unset($_POST['_method']);
	}

/**
 * backupEnvironment method
 *
 * @return void
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
