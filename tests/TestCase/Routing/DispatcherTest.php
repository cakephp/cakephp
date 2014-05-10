<?php
/**
 * CakePHP(tm) Tests <http://book.cakephp.org/2.0/en/development/testing.html>
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/2.0/en/development/testing.html CakePHP(tm) Tests
 * @since         1.2.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Routing;

use Cake\Controller\Controller;
use Cake\Controller\Error\MissingActionException;
use Cake\Controller\Error\MissingControllerException;
use Cake\Core\App;
use Cake\Core\Configure;
use Cake\Core\Plugin;
use Cake\Error;
use Cake\Event\Event;
use Cake\Network\Request;
use Cake\Network\Response;
use Cake\Routing\Dispatcher;
use Cake\Routing\Filter\AssetDispatcher;
use Cake\Routing\Error\MissingDispatcherFilterException;
use Cake\Routing\Router;
use Cake\TestSuite\TestCase;
use Cake\Utility\Inflector;

/**
 * A testing stub that doesn't send headers.
 */
class DispatcherMockResponse extends Response {

	protected function _sendHeader($name, $value = null) {
		return $name . ' ' . $value;
	}

}

/**
 * TestDispatcher class
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
 * @param \Cake\Controller\Controller $controller
 * @return \Cake\Network\Response $response
 */
	protected function _invoke(Controller $controller) {
		$this->controller = $controller;
		return parent::_invoke($controller);
	}

/**
 * Helper function to test single method attaching for dispatcher filters
 *
 * @param \Cake\Event\Event $event
 * @return void
 */
	public function filterTest($event) {
		$event->data['request']->params['eventName'] = $event->name();
	}

/**
 * Helper function to test single method attaching for dispatcher filters
 *
 * @param \Cake\Event\Event
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
 */
class MyPluginAppController extends Controller {
}

interface DispatcherTestInterfaceController {

	public function index();

}

/**
 * MyPluginController class
 *
 */
class MyPluginController extends MyPluginAppController {

/**
 * name property
 *
 * @var string
 */
	public $name = 'MyPlugin';

/**
 * uses property
 *
 * @var array
 */
	public $uses = [];

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
 * OtherPagesController class
 *
 */
class OtherPagesController extends MyPluginAppController {

/**
 * name property
 *
 * @var string
 */
	public $name = 'OtherPages';

/**
 * uses property
 *
 * @var array
 */
	public $uses = [];

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
 * ArticlesTestAppController class
 *
 */
class ArticlesTestAppController extends Controller {
}

/**
 * ArticlesTestController class
 *
 */
class ArticlesTestController extends ArticlesTestAppController {

/**
 * name property
 *
 * @var string
 */
	public $name = 'ArticlesTest';

/**
 * uses property
 *
 * @var array
 */
	public $uses = [];

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
 * DispatcherTest class
 *
 */
class DispatcherTest extends TestCase {

/**
 * setUp method
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();
		$_GET = [];

		Configure::write('App.base', false);
		Configure::write('App.baseUrl', false);
		Configure::write('App.dir', 'app');
		Configure::write('App.webroot', 'webroot');
		Configure::write('App.namespace', 'TestApp');

		App::objects('Plugin', null, false);
	}

/**
 * tearDown method
 *
 * @return void
 */
	public function tearDown() {
		parent::tearDown();
		Plugin::unload();
	}

/**
 * testMissingController method
 *
 * @expectedException \Cake\Controller\Error\MissingControllerException
 * @expectedExceptionMessage Controller class SomeController could not be found.
 * @return void
 */
	public function testMissingController() {
		Router::connect('/:controller/:action/*');

		$Dispatcher = new TestDispatcher();
		Configure::write('App.baseUrl', '/index.php');
		$request = new Request([
			'url' => 'some_controller/home',
			'params' => [
				'controller' => 'some_controller',
				'action' => 'home',
			]
		]);
		$response = $this->getMock('Cake\Network\Response');

		$Dispatcher->dispatch($request, $response, array('return' => 1));
	}

/**
 * testMissingControllerInterface method
 *
 * @expectedException \Cake\Controller\Error\MissingControllerException
 * @expectedExceptionMessage Controller class DispatcherTestInterface could not be found.
 * @return void
 */
	public function testMissingControllerInterface() {
		Router::connect('/:controller/:action/*');

		$Dispatcher = new TestDispatcher();
		Configure::write('App.baseUrl', '/index.php');
		$request = new Request([
			'url' => 'dispatcher_test_interface/index',
			'params' => [
				'controller' => 'dispatcher_test_interface',
				'action' => 'index',
			]
		]);
		$url = new Request('dispatcher_test_interface/index');
		$response = $this->getMock('Cake\Network\Response');
		$Dispatcher->dispatch($request, $response, array('return' => 1));
	}

/**
 * testMissingControllerInterface method
 *
 * @expectedException \Cake\Controller\Error\MissingControllerException
 * @expectedExceptionMessage Controller class Abstract could not be found.
 * @return void
 */
	public function testMissingControllerAbstract() {
		Router::connect('/:controller/:action/*');

		$Dispatcher = new TestDispatcher();
		$request = new Request([
			'url' => 'abstract/index',
			'params' => [
				'controller' => 'abstract',
				'action' => 'index',
			]
		]);
		$response = $this->getMock('Cake\Network\Response');
		$Dispatcher->dispatch($request, $response, array('return' => 1));
	}

/**
 * testDispatch method
 *
 * @return void
 */
	public function testDispatchBasic() {
		$this->markTestIncomplete();
		Router::connect('/pages/*', array('controller' => 'Pages', 'action' => 'display'));
		Router::connect('/:controller/:action/*');

		$Dispatcher = new TestDispatcher();
		$url = new Request('pages/home');
		$response = $this->getMock('Cake\Network\Response');

		$Dispatcher->dispatch($url, $response, array('return' => 1));
		$expected = 'Pages';
		$this->assertEquals($expected, $Dispatcher->controller->name);

		$expected = array('0' => 'home');
		$this->assertSame($expected, $Dispatcher->controller->request->params['pass']);

		Configure::write('App.baseUrl', '/pages/index.php');

		$url = new Request('pages/home');
		$Dispatcher->dispatch($url, $response, array('return' => 1));

		$expected = 'Pages';
		$this->assertEquals($expected, $Dispatcher->controller->name);

		$url = new Request('pages/home/');
		$Dispatcher->dispatch($url, $response, array('return' => 1));
		$this->assertNull($Dispatcher->controller->plugin);

		$expected = 'Pages';
		$this->assertEquals($expected, $Dispatcher->controller->name);

		unset($Dispatcher);

		require CAKE . 'Config/routes.php';
		$Dispatcher = new TestDispatcher();

		$url = new Request('test_dispatch_pages/camelCased');
		$Dispatcher->dispatch($url, $response, array('return' => 1));
		$this->assertEquals('TestDispatchPages', $Dispatcher->controller->name);

		$url = new Request('test_dispatch_pages/camelCased/something. .');
		$Dispatcher->dispatch($url, $response, array('return' => 1));
		$this->assertEquals(
			'something. .',
			$url->params['pass'][0],
			'Period was chopped off. %s'
		);
	}

/**
 * Test that Dispatcher handles actions that return response objects.
 *
 * @return void
 */
	public function testDispatchActionReturnsResponse() {
		$this->markTestIncomplete();
		Router::connect('/:controller/:action');
		$Dispatcher = new Dispatcher();
		$request = new Request('some_pages/responseGenerator');
		$response = $this->getMock('Cake\Network\Response', array('_sendHeader'));

		ob_start();
		$Dispatcher->dispatch($request, $response);
		$result = ob_get_clean();

		$this->assertEquals('new response', $result);
	}

/**
 * testPrefixDispatch method
 *
 * @return void
 */
	public function testPrefixDispatch() {
		$this->markTestIncomplete();
		$Dispatcher = new TestDispatcher();
		Configure::write('Routing.prefixes', array('admin'));
		$request = new Request('admin/posts/index');
		$response = $this->getMock('Cake\Network\Response');

		Router::reload();
		require CAKE . 'Config/routes.php';

		$Dispatcher->dispatch($request, $response, array('return' => 1));

		$this->assertInstanceOf(
			'TestApp\Controller\Admin\PostsController',
			$Dispatcher->controller
		);
		$this->assertEquals('admin', $request->params['prefix']);
		$this->assertEquals('posts', $request->params['controller']);
		$this->assertEquals('index', $request->params['action']);

		$expected = '/admin/posts/index';
		$this->assertSame($expected, $request->here);
	}

/**
 * test prefix dispatching in a plugin.
 *
 * @return void
 */
	public function testPrefixDispatchPlugin() {
		$this->markTestIncomplete();
		Configure::write('Routing.prefixes', array('admin'));
		Plugin::load('TestPlugin');

		$request = new Request('admin/posts/index');
		$response = $this->getMock('Cake\Network\Response');

		Router::reload();
		require CAKE . 'Config/routes.php';

		$Dispatcher = new TestDispatcher();
		$Dispatcher->dispatch($request, $response, array('return' => 1));

		$this->assertInstanceOf(
			'TestApp\Controller\Admin\PostsController',
			$Dispatcher->controller
		);
		$this->assertEquals('admin', $request->params['prefix']);
		$this->assertEquals('posts', $request->params['controller']);
		$this->assertEquals('index', $request->params['action']);

		$expected = '/admin/posts/index';
		$this->assertSame($expected, $request->here);
	}

/**
 * test plugin shortcut urls with controllers that need to be loaded,
 * the above test uses a controller that has already been included.
 *
 * @return void
 */
	public function testPluginShortCutUrlsWithControllerThatNeedsToBeLoaded() {
		$this->markTestIncomplete();
		Router::reload();
		Plugin::load(['TestPlugin', 'TestPluginTwo']);

		$Dispatcher = new TestDispatcher();
		$Dispatcher->base = false;

		$url = new Request('test_plugin/');
		$response = $this->getMock('Cake\Network\Response');

		$Dispatcher->dispatch($url, $response, array('return' => 1));
		$this->assertEquals('test_plugin', $url->params['controller']);
		$this->assertEquals('test_plugin', $url->params['plugin']);
		$this->assertEquals('index', $url->params['action']);
		$this->assertFalse(isset($url->params['pass'][0]));

		$url = new Request('/test_plugin/tests/index');
		$Dispatcher->dispatch($url, $response, array('return' => 1));
		$this->assertEquals('tests', $url->params['controller']);
		$this->assertEquals('test_plugin', $url->params['plugin']);
		$this->assertEquals('index', $url->params['action']);
		$this->assertFalse(isset($url->params['pass'][0]));

		$url = new Request('/test_plugin/tests/index/some_param');
		$Dispatcher->dispatch($url, $response, array('return' => 1));
		$this->assertEquals('tests', $url->params['controller']);
		$this->assertEquals('test_plugin', $url->params['plugin']);
		$this->assertEquals('index', $url->params['action']);
		$this->assertEquals('some_param', $url->params['pass'][0]);
	}

/**
 * Test dispatching into the TestPlugin in the TestApp
 *
 * @return void
 */
	public function testTestPluginDispatch() {
		$this->markTestIncomplete();
		$Dispatcher = new TestDispatcher();
		Plugin::load(array('TestPlugin', 'TestPluginTwo'));
		Router::reload();
		Router::parse('/');

		$url = new Request('/test_plugin/tests/index');
		$response = $this->getMock('Cake\Network\Response');
		$Dispatcher->dispatch($url, $response, array('return' => 1));
		$this->assertTrue(class_exists('TestPlugin\Controller\TestsController'));
		$this->assertTrue(class_exists('TestPlugin\Controller\TestPluginAppController'));
		$this->assertTrue(class_exists('TestPlugin\Controller\Component\PluginsComponent'));

		$this->assertEquals('tests', $url->params['controller']);
		$this->assertEquals('test_plugin', $url->params['plugin']);
		$this->assertEquals('index', $url->params['action']);
	}

/**
 * Test dispatcher filters being called.
 *
 * @return void
 */
	public function testDispatcherFilter() {
		$dispatcher = new TestDispatcher();
		$filter = $this->getMock(
			'Cake\Routing\DispatcherFilter',
			['beforeDispatch', 'afterDispatch']
		);

		$filter->expects($this->at(0))
			->method('beforeDispatch');
		$filter->expects($this->at(1))
			->method('afterDispatch');
		$dispatcher->add($filter);

		$request = new Request([
			'url' => '/',
			'params' => [
				'controller' => 'pages',
				'action' => 'display',
				'home',
				'pass' => []
			]
		]);
		$response = $this->getMock('Cake\Network\Response', ['send']);
		$dispatcher->dispatch($request, $response);
	}

/**
 * Test dispatcher filters being called and changing the response.
 *
 * @return void
 */
	public function testBeforeDispatchAbortDispatch() {
		$this->markTestIncomplete();
	}

/**
 * Test dispatcher filters being called and changing the response.
 *
 * @return void
 */
	public function testAfterDispatchAbortDispatch() {
		$this->markTestIncomplete();
	}

/**
 * testChangingParamsFromBeforeFilter method
 *
 * @return void
 */
	public function testChangingParamsFromBeforeFilter() {
		$this->markTestIncomplete();
		Router::connect('/:controller/:action/*');

		$Dispatcher = new TestDispatcher();
		$response = $this->getMock('Cake\Network\Response');
		$url = new Request('some_posts/index/param:value/param2:value2');

		try {
			$Dispatcher->dispatch($url, $response, array('return' => 1));
			$this->fail('No exception.');
		} catch (MissingActionException $e) {
			$this->assertEquals('Action SomePostsController::view() could not be found.', $e->getMessage());
		}

		$url = new Request('some_posts/something_else/param:value/param2:value2');
		$Dispatcher->dispatch($url, $response, array('return' => 1));

		$expected = 'SomePosts';
		$this->assertEquals($expected, $Dispatcher->controller->name);

		$expected = 'change';
		$this->assertEquals($expected, $url->action);

		$expected = array('changed');
		$this->assertSame($expected, $url->params['pass']);
	}
}
