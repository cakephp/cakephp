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
		$Dispatcher = new TestDispatcher();
		$url = new Request([
			'url' => 'pages/home',
			'params' => [
				'controller' => 'pages',
				'action' => 'display',
				'pass' => ['extract'],
				'return' => 1
			]
		]);
		$response = $this->getMock('Cake\Network\Response');

		$Dispatcher->dispatch($url, $response);
		$expected = 'Pages';
		$this->assertEquals($expected, $Dispatcher->controller->name);
	}

/**
 * Test that Dispatcher handles actions that return response objects.
 *
 * @return void
 */
	public function testDispatchActionReturnsResponse() {
		Router::connect('/:controller/:action');
		$Dispatcher = new Dispatcher();
		$request = new Request([
			'url' => 'some_pages/responseGenerator',
			'params' => [
				'controller' => 'some_pages',
				'action' => 'responseGenerator',
				'pass' => []
			]
		]);
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
		$Dispatcher = new TestDispatcher();
		$request = new Request([
			'url' => 'admin/posts/index',
			'params' => [
				'prefix' => 'admin',
				'controller' => 'posts',
				'action' => 'index',
				'pass' => [],
				'return' => 1
			]
		]);
		$response = $this->getMock('Cake\Network\Response');

		$Dispatcher->dispatch($request, $response);

		$this->assertInstanceOf(
			'TestApp\Controller\Admin\PostsController',
			$Dispatcher->controller
		);
		$expected = '/admin/posts/index';
		$this->assertSame($expected, $request->here);
	}

/**
 * test prefix dispatching in a plugin.
 *
 * @return void
 */
	public function testPrefixDispatchPlugin() {
		Plugin::load('TestPlugin');

		$request = new Request([
			'url' => 'admin/test_plugin/comments/index',
			'params' => [
				'plugin' => 'test_plugin',
				'prefix' => 'admin',
				'controller' => 'comments',
				'action' => 'index',
				'pass' => [],
				'return' => 1
			]
		]);
		$response = $this->getMock('Cake\Network\Response');

		$Dispatcher = new TestDispatcher();
		$Dispatcher->dispatch($request, $response);

		$this->assertInstanceOf(
			'TestPlugin\Controller\Admin\CommentsController',
			$Dispatcher->controller
		);
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
		$dispatcher->addFilter($filter);

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
		$response = $this->getMock('Cake\Network\Response', ['send']);
		$response->expects($this->once())
			->method('send');

		$filter = $this->getMock(
			'Cake\Routing\DispatcherFilter',
			['beforeDispatch', 'afterDispatch']);
		$filter->expects($this->once())
			->method('beforeDispatch')
			->will($this->returnValue($response));

		$filter->expects($this->never())
			->method('afterDispatch');

		$request = new Request();
		$res = new Response();
		$dispatcher = new Dispatcher();
		$dispatcher->addFilter($filter);
		$dispatcher->dispatch($request, $res);
	}

/**
 * Test dispatcher filters being called and changing the response.
 *
 * @return void
 */
	public function testAfterDispatchReplaceResponse() {
		$response = $this->getMock('Cake\Network\Response', ['send']);
		$response->expects($this->once())
			->method('send');

		$filter = $this->getMock(
			'Cake\Routing\DispatcherFilter',
			['beforeDispatch', 'afterDispatch']);

		$filter->expects($this->once())
			->method('afterDispatch')
			->will($this->returnValue($response));

		$request = new Request([
			'url' => '/posts',
			'params' => [
				'plugin' => null,
				'controller' => 'posts',
				'action' => 'index',
				'pass' => [],
			]
		]);
		$dispatcher = new Dispatcher();
		$dispatcher->addFilter($filter);
		$dispatcher->dispatch($request, $response);
	}

}
