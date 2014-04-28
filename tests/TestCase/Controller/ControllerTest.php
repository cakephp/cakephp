<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP Project
 * @since         1.2.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Controller;

use Cake\Controller\Component;
use Cake\Controller\Controller;
use Cake\Core\App;
use Cake\Core\Configure;
use Cake\Core\Plugin;
use Cake\Event\Event;
use Cake\Network\Request;
use Cake\Network\Response;
use Cake\ORM\TableRegistry;
use Cake\Routing\Router;
use Cake\TestSuite\Fixture\TestModel;
use Cake\TestSuite\TestCase;
use Cake\Utility\ClassRegistry;
use Cake\Utility\Hash;
use TestPlugin\Controller\TestPluginController;

/**
 * AppController class
 *
 */
class ControllerTestAppController extends Controller {

/**
 * helpers property
 *
 * @var array
 */
	public $helpers = array('Html');

/**
 * modelClass property
 *
 * @var string
 */
	public $modelClass = 'Posts';

/**
 * components property
 *
 * @var array
 */
	public $components = array('Cookie');
}

/**
 * TestController class
 */
class TestController extends ControllerTestAppController {

/**
 * helpers property
 *
 * @var array
 */
	public $helpers = array('Session');

/**
 * components property
 *
 * @var array
 */
	public $components = array('Security');

/**
 * modelClass property
 *
 * @var string
 */
	public $modelClass = 'Comments';

/**
 * index method
 *
 * @param mixed $testId
 * @param mixed $testTwoId
 * @return void
 */
	public function index($testId, $testTwoId) {
		$this->request->data = array(
			'testId' => $testId,
			'test2Id' => $testTwoId
		);
	}

/**
 * view method
 *
 * @param mixed $testId
 * @param mixed $testTwoId
 * @return void
 */
	public function view($testId, $testTwoId) {
		$this->request->data = array(
			'testId' => $testId,
			'test2Id' => $testTwoId
		);
	}

	public function returner() {
		return 'I am from the controller.';
	}

	//@codingStandardsIgnoreStart
	protected function protected_m() {
	}

	private function private_m() {
	}

	public function _hidden() {
	}
	//@codingStandardsIgnoreEnd

	public function admin_add() {
	}

}

/**
 * TestComponent class
 */
class TestComponent extends Component {

/**
 * beforeRedirect method
 *
 * @return void
 */
	public function beforeRedirect() {
	}

/**
 * initialize method
 *
 * @param Event $event
 * @return void
 */
	public function initialize(Event $event) {
	}

/**
 * startup method
 *
 * @param Event $event
 * @return void
 */
	public function startup(Event $event) {
	}

/**
 * shutdown method
 *
 * @param Event $event
 * @return void
 */
	public function shutdown(Event $event) {
	}

/**
 * beforeRender callback
 *
 * @param Event $event
 * @return void
 */
	public function beforeRender(Event $event) {
		$controller = $event->subject();
		if ($this->viewclass) {
			$controller->viewClass = $this->viewclass;
		}
	}

}

/**
 * AnotherTestController class
 *
 */
class AnotherTestController extends ControllerTestAppController {
}

/**
 * ControllerTest class
 *
 */
class ControllerTest extends TestCase {

/**
 * fixtures property
 *
 * @var array
 */
	public $fixtures = array(
		'core.post',
		'core.comment'
	);

/**
 * reset environment.
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();

		App::objects('Plugin', null, false);
		Router::reload();
	}

/**
 * tearDown
 *
 * @return void
 */
	public function tearDown() {
		parent::tearDown();
		Plugin::unload();
	}

/**
 * test autoload modelClass
 *
 * @return void
 */
	public function testTableAutoload() {
		Configure::write('App.namespace', 'TestApp');
		$request = new Request('controller_posts/index');
		$response = $this->getMock('Cake\Network\Response');
		$Controller = new Controller($request, $response);
		$Controller->modelClass = 'Articles';

		$this->assertInstanceOf(
			'TestApp\Model\Table\ArticlesTable',
			$Controller->Articles
		);
	}

/**
 * testLoadModel method
 *
 * @return void
 */
	public function testLoadModel() {
		Configure::write('App.namespace', 'TestApp');
		$request = new Request('controller_posts/index');
		$response = $this->getMock('Cake\Network\Response');
		$Controller = new Controller($request, $response);

		$this->assertFalse(isset($Controller->Articles));

		$result = $Controller->loadModel('Articles');
		$this->assertTrue($result);
		$this->assertInstanceOf(
			'TestApp\Model\Table\ArticlesTable',
			$Controller->Articles
		);
	}

/**
 * testLoadModel method from a plugin controller
 *
 * @return void
 */
	public function testLoadModelInPlugins() {
		Configure::write('App.namespace', 'TestApp');
		Plugin::load('TestPlugin');

		$Controller = new TestPluginController();
		$Controller->plugin = 'TestPlugin';

		$this->assertFalse(isset($Controller->TestPluginComments));

		$result = $Controller->loadModel('TestPlugin.TestPluginComments');
		$this->assertTrue($result);
		$this->assertInstanceOf(
			'TestPlugin\Model\Table\TestPluginCommentsTable',
			$Controller->TestPluginComments
		);
	}

/**
 * testConstructClassesWithComponents method
 *
 * @return void
 */
	public function testConstructClassesWithComponents() {
		Configure::write('App.namespace', 'TestApp');
		Plugin::load('TestPlugin');

		$Controller = new TestPluginController(new Request(), new Response());
		$Controller->components[] = 'TestPlugin.Other';

		$Controller->constructClasses();
		$this->assertInstanceOf('TestPlugin\Controller\Component\OtherComponent', $Controller->Other);
	}

/**
 * testRender method
 *
 * @return void
 */
	public function testRender() {
		$this->markTestIncomplete('Need to sort out a few more things with the ORM first.');
		Configure::write('App.namespace', 'TestApp');
		Plugin::load('TestPlugin');

		$request = new Request('controller_posts/index');
		$request->params['action'] = 'index';

		$Controller = new Controller($request, new Response());
		$Controller->viewPath = 'Posts';

		$result = $Controller->render('index');
		$this->assertRegExp('/posts index/', (string)$result);

		$Controller->view = 'index';
		$result = $Controller->render();
		$this->assertRegExp('/posts index/', (string)$result);

		$result = $Controller->render('/Element/test_element');
		$this->assertRegExp('/this is the test element/', (string)$result);
		$Controller->view = null;

		$Controller = new TestController($request, new Response());
		$Controller->uses = ['TestPlugin.TestPluginComment'];
		$Controller->helpers = array('Html');
		$Controller->constructClasses();
		$expected = ['title' => 'tooShort'];
		$Controller->TestPluginComment->validationErrors = $expected;

		$Controller->viewPath = 'Posts';
		$result = $Controller->render('index');
		$View = $Controller->View;
		$this->assertTrue(isset($View->validationErrors['TestPluginComment']));
		$this->assertEquals($expected, $View->validationErrors['TestPluginComment']);

		$expectedModels = [
			'TestPluginComment' => [
				'className' => 'TestPlugin\Model\TestPluginComment'
			],
			'Post' => [
				'className' => 'TestApp\Model\Post'
			]
		];
		$this->assertEquals($expectedModels, $Controller->request->params['models']);
	}

/**
 * test that a component beforeRender can change the controller view class.
 *
 * @return void
 */
	public function testBeforeRenderCallbackChangingViewClass() {
		Configure::write('App.namespace', 'TestApp');
		$Controller = new Controller(new Request, new Response());

		$Controller->getEventManager()->attach(function ($event) {
			$controller = $event->subject();
			$controller->viewClass = 'Json';
		}, 'Controller.beforeRender');

		$Controller->set([
			'test' => 'value',
			'_serialize' => ['test']
		]);
		$debug = Configure::read('debug');
		Configure::write('debug', false);
		$result = $Controller->render('index');
		$this->assertEquals('{"test":"value"}', $result->body());
		Configure::write('debug', $debug);
	}

/**
 * test that a component beforeRender can change the controller view class.
 *
 * @return void
 */
	public function testBeforeRenderEventCancelsRender() {
		$Controller = new Controller(new Request, new Response());

		$Controller->getEventManager()->attach(function ($event) {
			return false;
		}, 'Controller.beforeRender');

		$result = $Controller->render('index');
		$this->assertInstanceOf('Cake\Network\Response', $result);
	}

/**
 * Generates status codes for redirect test.
 *
 * @return void
 */
	public static function statusCodeProvider() {
		return array(
			array(300, "Multiple Choices"),
			array(301, "Moved Permanently"),
			array(302, "Found"),
			array(303, "See Other"),
			array(304, "Not Modified"),
			array(305, "Use Proxy"),
			array(307, "Temporary Redirect"),
			array(403, "Forbidden"),
		);
	}

/**
 * testRedirect method
 *
 * @dataProvider statusCodeProvider
 * @return void
 */
	public function testRedirectByCode($code, $msg) {
		$Controller = new Controller(null);
		$Controller->response = new Response();

		$response = $Controller->redirect('http://cakephp.org', (int)$code, false);
		$this->assertEquals($code, $response->statusCode());
		$this->assertEquals('http://cakephp.org', $response->header()['Location']);
		$this->assertFalse($Controller->autoRender);
	}

/**
 * test that beforeRedirect callbacks can set the URL that is being redirected to.
 *
 * @return void
 */
	public function testRedirectBeforeRedirectModifyingUrl() {
		$Controller = new Controller(null);
		$Controller->response = new Response();

		$Controller->getEventManager()->attach(function ($event, $response, $url) {
			$response->location('http://book.cakephp.org');
		}, 'Controller.beforeRedirect');

		$response = $Controller->redirect('http://cakephp.org', 301, false);
		$this->assertEquals('http://book.cakephp.org', $response->header()['Location']);
		$this->assertEquals(301, $response->statusCode());
	}

/**
 * test that beforeRedirect callback returning null doesn't affect things.
 *
 * @return void
 */
	public function testRedirectBeforeRedirectModifyingStatusCode() {
		$Response = $this->getMock('Cake\Network\Response', array('stop'));
		$Controller = new Controller(null, $Response);

		$Controller->getEventManager()->attach(function ($event, $response, $url) {
			$response->statusCode(302);
		}, 'Controller.beforeRedirect');

		$response = $Controller->redirect('http://cakephp.org', 301, false);

		$this->assertEquals('http://cakephp.org', $response->header()['Location']);
		$this->assertEquals(302, $response->statusCode());
	}

/**
 * test that beforeRedirect callback returning false in controller
 *
 * @return void
 */
	public function testRedirectBeforeRedirectListenerReturnFalse() {
		$Response = $this->getMock('Cake\Network\Response', array('stop', 'header'));
		$Controller = new Controller(null, $Response);

		$Controller->getEventManager()->attach(function ($event, $response, $url, $status) {
			return false;
		}, 'Controller.beforeRedirect');

		$Controller->response->expects($this->never())
			->method('stop');
		$Controller->response->expects($this->never())
			->method('header');
		$Controller->response->expects($this->never())
			->method('statusCode');

		$result = $Controller->redirect('http://cakephp.org');
		$this->assertNull($result);
	}

/**
 * testMergeVars method
 *
 * @return void
 */
	public function testMergeVars() {
		$request = new Request();

		$TestController = new TestController($request);
		$TestController->constructClasses();

		$expected = [
			'Html' => null,
			'Session' => null
		];
		$this->assertEquals($expected, $TestController->helpers);

		$expected = [
			'Session' => null,
			'Security' => null,
			'Cookie' => null,
		];
		$this->assertEquals($expected, $TestController->components);

		$TestController = new AnotherTestController($request);
		$TestController->constructClasses();

		$this->assertEquals(
			'Posts',
			$TestController->modelClass,
			'modelClass should not be overwritten when defined.'
		);
	}

/**
 * test that options from child classes replace those in the parent classes.
 *
 * @return void
 */
	public function testChildComponentOptionsSupercedeParents() {
		$request = new Request('controller_posts/index');

		$TestController = new TestController($request);

		$expected = array('foo');
		$TestController->components = array('Cookie' => $expected);
		$TestController->constructClasses();
		$this->assertEquals($expected, $TestController->components['Cookie']);
	}

/**
 * Ensure that _mergeControllerVars is not being greedy and merging with
 * ControllerTestAppController when you make an instance of Controller
 *
 * @return void
 */
	public function testMergeVarsNotGreedy() {
		$request = new Request('controller_posts/index');

		$Controller = new Controller($request);
		$Controller->components = [];
		$Controller->constructClasses();

		$this->assertFalse(isset($Controller->Session));
	}

/**
 * testReferer method
 *
 * @return void
 */
	public function testReferer() {
		$request = $this->getMock('Cake\Network\Request', ['referer']);
		$request->expects($this->any())->method('referer')
			->with(true)
			->will($this->returnValue('/posts/index'));

		$Controller = new Controller($request);
		$result = $Controller->referer(null, true);
		$this->assertEquals('/posts/index', $result);

		$request = $this->getMock('Cake\Network\Request', ['referer']);
		$request->expects($this->any())->method('referer')
			->with(true)
			->will($this->returnValue('/posts/index'));
		$Controller = new Controller($request);
		$result = $Controller->referer(array('controller' => 'posts', 'action' => 'index'), true);
		$this->assertEquals('/posts/index', $result);

		$request = $this->getMock('Cake\Network\Request', ['referer']);

		$request->expects($this->any())->method('referer')
			->with(false)
			->will($this->returnValue('http://localhost/posts/index'));

		$Controller = new Controller($request);
		$result = $Controller->referer();
		$this->assertEquals('http://localhost/posts/index', $result);

		$Controller = new Controller(null);
		$result = $Controller->referer();
		$this->assertEquals('/', $result);
	}

/**
 * testSetAction method
 *
 * @return void
 */
	public function testSetAction() {
		$request = new Request('controller_posts/index');

		$TestController = new TestController($request);
		$TestController->setAction('view', 1, 2);
		$expected = array('testId' => 1, 'test2Id' => 2);
		$this->assertSame($expected, $TestController->request->data);
		$this->assertSame('view', $TestController->request->params['action']);
		$this->assertSame('view', $TestController->view);
	}

/**
 * Tests that the startup process calls the correct functions
 *
 * @return void
 */
	public function testStartupProcess() {
		$Controller = $this->getMock('Cake\Controller\Controller', array('getEventManager'));

		$eventManager = $this->getMock('Cake\Event\EventManager');
		$eventManager->expects($this->at(0))->method('dispatch')
			->with(
				$this->logicalAnd(
					$this->isInstanceOf('Cake\Event\Event'),
					$this->attributeEqualTo('_name', 'Controller.initialize'),
					$this->attributeEqualTo('_subject', $Controller)
				)
			)
			->will($this->returnValue($this->getMock('Cake\Event\Event', null, [], '', false)));

		$eventManager->expects($this->at(1))->method('dispatch')
			->with(
				$this->logicalAnd(
					$this->isInstanceOf('Cake\Event\Event'),
					$this->attributeEqualTo('_name', 'Controller.startup'),
					$this->attributeEqualTo('_subject', $Controller)
				)
			)
			->will($this->returnValue($this->getMock('Cake\Event\Event', null, [], '', false)));

		$Controller->expects($this->exactly(2))->method('getEventManager')
			->will($this->returnValue($eventManager));

		$Controller->startupProcess();
	}

/**
 * Tests that the shutdown process calls the correct functions
 *
 * @return void
 */
	public function testShutdownProcess() {
		$Controller = $this->getMock('Cake\Controller\Controller', array('getEventManager'));

		$eventManager = $this->getMock('Cake\Event\EventManager');
		$eventManager->expects($this->once())->method('dispatch')
			->with(
				$this->logicalAnd(
					$this->isInstanceOf('Cake\Event\Event'),
					$this->attributeEqualTo('_name', 'Controller.shutdown'),
					$this->attributeEqualTo('_subject', $Controller)
				)
			)
			->will($this->returnValue($this->getMock('Cake\Event\Event', null, [], '', false)));

		$Controller->expects($this->once())->method('getEventManager')
			->will($this->returnValue($eventManager));

		$Controller->shutdownProcess();
	}

/**
 * test using Controller::paginate()
 *
 * @return void
 */
	public function testPaginate() {
		$request = new Request('controller_posts/index');
		$request->params['pass'] = array();
		$response = $this->getMock('Cake\Network\Response', ['httpCodes']);

		$Controller = new Controller($request, $response);
		$Controller->request->query['url'] = [];
		$Controller->constructClasses();
		$this->assertEquals([], $Controller->paginate);

		$this->assertNotContains('Paginator', $Controller->helpers);
		$this->assertArrayNotHasKey('Paginator', $Controller->helpers);

		$results = $Controller->paginate('Posts');
		$this->assertInstanceOf('Cake\ORM\ResultSet', $results);
		$this->assertContains('Paginator', $Controller->helpers, 'Paginator should be added.');

		$results = $Controller->paginate(TableRegistry::get('Posts'));
		$this->assertInstanceOf('Cake\ORM\ResultSet', $results);

		$this->assertSame($Controller->request->params['paging']['Posts']['page'], 1);
		$this->assertSame($Controller->request->params['paging']['Posts']['pageCount'], 1);
		$this->assertSame($Controller->request->params['paging']['Posts']['prevPage'], false);
		$this->assertSame($Controller->request->params['paging']['Posts']['nextPage'], false);
	}

/**
 * test that paginate uses modelClass property.
 *
 * @return void
 */
	public function testPaginateUsesModelClass() {
		$request = new Request('controller_posts/index');
		$request->params['pass'] = array();
		$response = $this->getMock('Cake\Network\Response', ['httpCodes']);

		$Controller = new Controller($request, $response);
		$Controller->request->query['url'] = [];
		$Controller->constructClasses();
		$Controller->modelClass = 'Posts';
		$results = $Controller->paginate();

		$this->assertInstanceOf('Cake\ORM\ResultSet', $results);
	}

/**
 * testMissingAction method
 *
 * @expectedException \Cake\Controller\Error\MissingActionException
 * @expectedExceptionMessage Action TestController::missing() could not be found.
 * @return void
 */
	public function testInvokeActionMissingAction() {
		$url = new Request('test/missing');
		$url->addParams(array('controller' => 'test_controller', 'action' => 'missing'));
		$response = $this->getMock('Cake\Network\Response');

		$Controller = new TestController($url, $response);
		$Controller->invokeAction();
	}

/**
 * test invoking private methods.
 *
 * @expectedException \Cake\Controller\Error\PrivateActionException
 * @expectedExceptionMessage Private Action TestController::private_m() is not directly accessible.
 * @return void
 */
	public function testInvokeActionPrivate() {
		$url = new Request('test/private_m/');
		$url->addParams(array('controller' => 'test_controller', 'action' => 'private_m'));
		$response = $this->getMock('Cake\Network\Response');

		$Controller = new TestController($url, $response);
		$Controller->invokeAction();
	}

/**
 * test invoking protected methods.
 *
 * @expectedException \Cake\Controller\Error\PrivateActionException
 * @expectedExceptionMessage Private Action TestController::protected_m() is not directly accessible.
 * @return void
 */
	public function testInvokeActionProtected() {
		$url = new Request('test/protected_m/');
		$url->addParams(array('controller' => 'test_controller', 'action' => 'protected_m'));
		$response = $this->getMock('Cake\Network\Response');

		$Controller = new TestController($url, $response);
		$Controller->invokeAction();
	}

/**
 * test invoking hidden methods.
 *
 * @expectedException \Cake\Controller\Error\PrivateActionException
 * @expectedExceptionMessage Private Action TestController::_hidden() is not directly accessible.
 * @return void
 */
	public function testInvokeActionHidden() {
		$url = new Request('test/_hidden/');
		$url->addParams(array('controller' => 'test_controller', 'action' => '_hidden'));
		$response = $this->getMock('Cake\Network\Response');

		$Controller = new TestController($url, $response);
		$Controller->invokeAction();
	}

/**
 * test invoking controller methods.
 *
 * @expectedException \Cake\Controller\Error\PrivateActionException
 * @expectedExceptionMessage Private Action TestController::redirect() is not directly accessible.
 * @return void
 */
	public function testInvokeActionBaseMethods() {
		$url = new Request('test/redirect/');
		$url->addParams(array('controller' => 'test_controller', 'action' => 'redirect'));
		$response = $this->getMock('Cake\Network\Response');

		$Controller = new TestController($url, $response);
		$Controller->invokeAction();
	}

/**
 * test invoking controller methods.
 *
 * @expectedException \Cake\Controller\Error\PrivateActionException
 * @expectedExceptionMessage Private Action TestController::admin_add() is not directly accessible.
 * @return void
 */
	public function testInvokeActionPrefixProtection() {
		Configure::write('Routing.prefixes', array('admin'));
		Router::reload();
		Router::connect('/admin/:controller/:action/*', array('prefix' => 'admin'));

		$url = new Request('test/admin_add/');
		$url->addParams(array('controller' => 'test_controller', 'action' => 'admin_add'));
		$response = $this->getMock('Cake\Network\Response');

		$Controller = new TestController($url, $response);
		$Controller->invokeAction();
	}

/**
 * test invoking controller methods.
 *
 * @return void
 */
	public function testInvokeActionReturnValue() {
		$url = new Request('test/returner/');
		$url->addParams(array(
			'controller' => 'test_controller',
			'action' => 'returner',
			'pass' => array()
		));
		$response = $this->getMock('Cake\Network\Response');

		$Controller = new TestController($url, $response);
		$result = $Controller->invokeAction();
		$this->assertEquals('I am from the controller.', $result);
	}

/**
 * test that a classes namespace is used in the viewPath.
 *
 * @return void
 */
	public function testViewPathConventions() {
		$request = new Request('admin/posts');
		$request->addParams(array(
			'prefix' => 'admin'
		));
		$response = $this->getMock('Cake\Network\Response');
		$Controller = new \TestApp\Controller\Admin\PostsController($request, $response);
		$this->assertEquals('Admin/Posts', $Controller->viewPath);

		$request = new Request('pages/home');
		$Controller = new \TestApp\Controller\PagesController($request, $response);
		$this->assertEquals('Pages', $Controller->viewPath);
	}

/**
 * Test the components() method.
 *
 * @return void
 */
	public function testComponents() {
		$request = new Request('/');
		$response = $this->getMock('Cake\Network\Response');

		$controller = new TestController($request, $response);
		$this->assertInstanceOf('Cake\Controller\ComponentRegistry', $controller->components());

		$result = $controller->components();
		$this->assertSame($result, $controller->components());
	}

/**
 * Test adding a component
 *
 * @return void
 */
	public function testAddComponent() {
		$request = new Request('/');
		$response = $this->getMock('Cake\Network\Response');

		$controller = new TestController($request, $response);
		$result = $controller->addComponent('Paginator');
		$this->assertInstanceOf('Cake\Controller\Component\PaginatorComponent', $result);
		$this->assertSame($result, $controller->Paginator);

		$registry = $controller->components();
		$this->assertTrue(isset($registry->Paginator));
	}

}
