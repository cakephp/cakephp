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
 * @package       Cake.Test.Case.Controller
 * @since         CakePHP(tm) v 1.2.0.5436
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
namespace Cake\Test\TestCase\Controller;

use Cake\Controller\Controller;
use Cake\Core\App;
use Cake\Core\Configure;
use Cake\Core\Object;
use Cake\Core\Plugin;
use Cake\Network\Request;
use Cake\Network\Response;
use Cake\Routing\Router;
use Cake\TestSuite\Fixture\TestModel;
use Cake\TestSuite\TestCase;
use Cake\Utility\ClassRegistry;
use Cake\Utility\Hash;
use TestPlugin\Controller\TestPluginController;

/**
 * AppController class
 *
 * @package       Cake.Test.Case.Controller
 */
class ControllerTestAppController extends Controller {

/**
 * helpers property
 *
 * @var array
 */
	public $helpers = array('Html');

/**
 * uses property
 *
 * @var array
 */
	public $uses = ['Post'];

/**
 * components property
 *
 * @var array
 */
	public $components = array('Cookie');
}


/**
 * TestController class
 *
 * @package       Cake.Test.Case.Controller
 */
class TestController extends ControllerTestAppController {

/**
 * name property
 * @var string 'Name'
 */
	public $name = 'Test';

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
 * uses property
 *
 * @var array
 */
	public $uses = array('Comment');

/**
 * index method
 *
 * @param mixed $testId
 * @param mixed $test2Id
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
 * @param mixed $test2Id
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
 * AnotherTestController class
 *
 * @package       Cake.Test.Case.Controller
 */
class AnotherTestController extends ControllerTestAppController {

/**
 * name property
 * @var string 'Name'
 */
	public $name = 'AnotherTest';

}

/**
 * ControllerTest class
 *
 * @package       Cake.Test.Case.Controller
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
		App::build();
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
 * testLoadModel method
 *
 * @return void
 */
	public function testLoadModel() {
		Configure::write('App.namespace', 'TestApp');
		$request = new Request('controller_posts/index');
		$response = $this->getMock('Cake\Network\Response');
		$Controller = new Controller($request, $response);

		$this->assertFalse(isset($Controller->Post));

		$result = $Controller->loadModel('Post');
		$this->assertTrue($result);
		$this->assertInstanceOf('TestApp\Model\Post', $Controller->Post);
		$this->assertTrue(in_array('Post', $Controller->uses));

		ClassRegistry::flush();
		unset($Controller);
	}

/**
 * testLoadModel method from a plugin controller
 *
 * @return void
 */
	public function testLoadModelInPlugins() {
		Configure::write('App.namespace', 'TestApp');
		App::build([
			'Plugin' => [CAKE . 'Test/TestApp/Plugin/'],
			'Controller' => [CAKE . 'Test/TestApp/Controller/'],
			'Model' => [CAKE . 'Test/TestApp/Model/']
		]);
		Plugin::load('TestPlugin');

		$Controller = new TestPluginController();
		$Controller->plugin = 'TestPlugin';
		$Controller->uses = false;

		$this->assertFalse(isset($Controller->Comment));

		$result = $Controller->loadModel('Comment');
		$this->assertTrue($result);
		$this->assertInstanceOf('TestApp\Model\Comment', $Controller->Comment);
		$this->assertTrue(in_array('Comment', $Controller->uses));

		ClassRegistry::flush();
		unset($Controller);
	}

/**
 * testConstructClasses method
 *
 * @return void
 */
	public function testConstructClasses() {
		Configure::write('App.namespace', 'TestApp');
		$request = new Request('controller_posts/index');

		$Controller = new Controller($request);
		$Controller->uses = ['Post', 'Comment'];
		$Controller->constructClasses();
		$this->assertInstanceOf('TestApp\Model\Post', $Controller->Post);
		$this->assertInstanceOf('TestApp\Model\Comment', $Controller->Comment);

		$this->assertEquals('Comment', $Controller->Comment->name);

		unset($Controller);

		App::build(['Plugin' => [CAKE . 'Test/TestApp/Plugin/']]);
		Plugin::load('TestPlugin');

		$Controller = new Controller($request);
		$Controller->uses = array('TestPlugin.TestPluginPost');
		$Controller->constructClasses();

		$this->assertTrue(isset($Controller->TestPluginPost));
		$this->assertInstanceOf('TestPlugin\Model\TestPluginPost', $Controller->TestPluginPost);
	}

/**
 * testFlash method
 *
 * @return void
 */
	public function testFlash() {
		$request = new Request('controller_posts/index');
		$request->webroot = '/';
		$request->base = '/';

		$Controller = new Controller($request, $this->getMock('Cake\Network\Response', array('_sendHeader')));
		$Controller->flash('this should work', '/flash');
		$result = $Controller->response->body();

		$expected = '<!DOCTYPE html>
		<html>
		<head>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		<title>this should work</title>
		<style><!--
		P { text-align:center; font:bold 1.1em sans-serif }
		A { color:#444; text-decoration:none }
		A:HOVER { text-decoration: underline; color:#44E }
		--></style>
		</head>
		<body>
		<p><a href="/flash">this should work</a></p>
		</body>
		</html>';
		$result = str_replace(array("\t", "\r\n", "\n"), "", $result);
		$expected = str_replace(array("\t", "\r\n", "\n"), "", $expected);
		$this->assertEquals($expected, $result);

		App::build(array(
			'View' => array(CAKE . 'Test/TestApp/View/')
		));
		$Controller = new Controller($request);
		$Controller->response = $this->getMock('Cake\Network\Response', array('_sendHeader'));
		$Controller->flash('this should work', '/flash', 1, 'ajax2');
		$result = $Controller->response->body();
		$this->assertRegExp('/Ajax!/', $result);
		App::build();
	}

/**
 * testControllerSet method
 *
 * @return void
 */
	public function testControllerSet() {
		$request = new Request('controller_posts/index');
		$Controller = new Controller($request);

		$Controller->set('variable_with_underscores', null);
		$this->assertTrue(array_key_exists('variable_with_underscores', $Controller->viewVars));

		$Controller->viewVars = array();
		$viewVars = array('ModelName' => array('id' => 1, 'name' => 'value'));
		$Controller->set($viewVars);
		$this->assertTrue(array_key_exists('ModelName', $Controller->viewVars));

		$Controller->viewVars = array();
		$Controller->set('variable_with_underscores', 'value');
		$this->assertTrue(array_key_exists('variable_with_underscores', $Controller->viewVars));

		$Controller->viewVars = array();
		$viewVars = array('ModelName' => 'name');
		$Controller->set($viewVars);
		$this->assertTrue(array_key_exists('ModelName', $Controller->viewVars));

		$Controller->set('title', 'someTitle');
		$this->assertSame($Controller->viewVars['title'], 'someTitle');
		$this->assertTrue(empty($Controller->pageTitle));

		$Controller->viewVars = array();
		$expected = array('ModelName' => 'name', 'ModelName2' => 'name2');
		$Controller->set(array('ModelName', 'ModelName2'), array('name', 'name2'));
		$this->assertSame($expected, $Controller->viewVars);

		$Controller->viewVars = array();
		$Controller->set(array(3 => 'three', 4 => 'four'));
		$Controller->set(array(1 => 'one', 2 => 'two'));
		$expected = array(3 => 'three', 4 => 'four', 1 => 'one', 2 => 'two');
		$this->assertEquals($expected, $Controller->viewVars);
	}

/**
 * testRender method
 *
 * @return void
 */
	public function testRender() {
		Configure::write('App.namespace', 'TestApp');
		App::build([
			'Plugin' => [CAKE . 'Test/TestApp/Plugin/'],
			'View' => [CAKE . 'Test/TestApp/View/']
		], App::RESET);
		ClassRegistry::flush();
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

		$result = $Controller->render('/Elements/test_element');
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
	public function testComponentBeforeRenderChangingViewClass() {
		Configure::write('App.namespace', 'TestApp');
		App::build([
			'View' => [
				CAKE . 'Test/TestApp/View/'
			]
		], true);
		$Controller = new Controller($this->getMock('Cake\Network\Request'), new Response());
		$Controller->constructClasses();
		$Controller->uses = $Controller->components = [];

		$mock = $this->getMock('Cake\Controller\Component', ['beforeRender'], [$Controller->Components]);
		$mock->expects($this->once())
			->method('beforeRender')
			->will($this->returnCallback(function ($controller) {
				$controller->viewClass = 'Json';
			}));
		$Controller->Components->set('Test', $mock);
		$Controller->Components->enable('Test');

		$Controller->set([
			'test' => 'value',
			'_serialize' => ['test']
		]);
		$result = $Controller->render('index');
		$this->assertEquals('{"test":"value"}', $result);
	}

/**
 * test that a component beforeRender can change the controller view class.
 *
 * @return void
 */
	public function testComponentCancelRender() {
		$Controller = new Controller($this->getMock('Cake\Network\Request'), new Response());
		$Controller->constructClasses();
		$mock = $this->getMock('Cake\Controller\Component', ['beforeRender'], [$Controller->Components]);
		$mock->expects($this->once())
			->method('beforeRender')
			->will($this->returnValue(false));
		$Controller->Components->set('Test', $mock);
		$Controller->Components->enable('Test');

		$result = $Controller->render('index');
		$this->assertInstanceOf('Cake\Network\Response', $result);
	}

/**
 * testToBeInheritedGuardmethods method
 *
 * @return void
 */
	public function testToBeInheritedGuardmethods() {
		$request = new Request('controller_posts/index');

		$Controller = new Controller($request, $this->getMock('Cake\Network\Response'));
		$this->assertTrue($Controller->beforeScaffold(''));
		$this->assertTrue($Controller->afterScaffoldSave(''));
		$this->assertTrue($Controller->afterScaffoldSaveError(''));
		$this->assertFalse($Controller->scaffoldError(''));
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
		$Controller->response = $this->getMock('Cake\Network\Response', array('header', 'statusCode'));

		$Controller->Components = $this->getMock('Cake\Controller\ComponentCollection', array('trigger'));

		$Controller->response->expects($this->once())->method('statusCode')
			->with($code);
		$Controller->response->expects($this->once())->method('header')
			->with('Location', 'http://cakephp.org');

		$Controller->redirect('http://cakephp.org', (int)$code, false);
		$this->assertFalse($Controller->autoRender);
	}

/**
 * test redirecting by message
 *
 * @dataProvider statusCodeProvider
 * @return void
 */
	public function testRedirectByMessage($code, $msg) {
		$Controller = new Controller(null);
		$Controller->response = $this->getMock('Cake\Network\Response', array('header', 'statusCode'));

		$Controller->Components = $this->getMock('Cake\Controller\ComponentCollection', array('trigger'));

		$Controller->response->expects($this->once())->method('statusCode')
			->with($code);

		$Controller->response->expects($this->once())->method('header')
			->with('Location', 'http://cakephp.org');

		$Controller->redirect('http://cakephp.org', $msg, false);
		$this->assertFalse($Controller->autoRender);
	}

/**
 * test that redirect triggers methods on the components.
 *
 * @return void
 */
	public function testRedirectTriggeringComponentsReturnNull() {
		$Controller = new Controller(null);
		$Controller->response = $this->getMock('Cake\Network\Response', array('header', 'statusCode'));
		$Controller->Components = $this->getMock('Cake\Controller\ComponentCollection', array('trigger'));

		$Controller->Components->expects($this->once())->method('trigger')
			->will($this->returnValue(null));

		$Controller->response->expects($this->once())->method('statusCode')
			->with(301);

		$Controller->response->expects($this->once())->method('header')
			->with('Location', 'http://cakephp.org');

		$Controller->redirect('http://cakephp.org', 301, false);
	}

/**
 * test that beforeRedirect callback returning null doesn't affect things.
 *
 * @return void
 */
	public function testRedirectBeforeRedirectModifyingParams() {
		$Controller = new Controller(null);
		$Controller->response = $this->getMock('Cake\Network\Response', array('header', 'statusCode'));
		$Controller->Components = $this->getMock('Cake\Controller\ComponentCollection', array('trigger'));

		$Controller->Components->expects($this->once())->method('trigger')
			->will($this->returnValue(array('http://book.cakephp.org')));

		$Controller->response->expects($this->once())->method('statusCode')
			->with(301);

		$Controller->response->expects($this->once())->method('header')
			->with('Location', 'http://book.cakephp.org');

		$Controller->redirect('http://cakephp.org', 301, false);
	}

/**
 * test that beforeRedirect callback returning null doesn't affect things.
 *
 * @return void
 */
	public function testRedirectBeforeRedirectModifyingParamsArrayReturn() {
		$Controller = $this->getMock('Cake\Controller\Controller', array('header', '_stop'));
		$Controller->response = $this->getMock('Cake\Network\Response');
		$Controller->Components = $this->getMock('Cake\Controller\ComponentCollection', array('trigger'));

		$return = array(
			array(
				'url' => 'http://example.com/test/1',
				'exit' => false,
				'status' => 302
			),
			array(
				'url' => 'http://example.com/test/2',
			),
		);
		$Controller->Components->expects($this->once())->method('trigger')
			->will($this->returnValue($return));

		$Controller->response->expects($this->once())->method('header')
			->with('Location', 'http://example.com/test/2');

		$Controller->response->expects($this->at(1))->method('statusCode')
			->with(302);

		$Controller->expects($this->never())->method('_stop');
		$Controller->redirect('http://cakephp.org', 301);
	}

/**
 * test that beforeRedirect callback returning false in controller
 *
 * @return void
 */
	public function testRedirectBeforeRedirectInController() {
		$Controller = $this->getMock('Cake\Controller\Controller', array('_stop', 'beforeRedirect'));
		$Controller->response = $this->getMock('Cake\Network\Response', array('header'));
		$Controller->Components = $this->getMock('Cake\Controller\ComponentCollection', array('trigger'));

		$Controller->expects($this->once())->method('beforeRedirect')
			->with('http://cakephp.org')
			->will($this->returnValue(false));
		$Controller->response->expects($this->never())->method('header');
		$Controller->expects($this->never())->method('_stop');
		$Controller->redirect('http://cakephp.org');
	}

/**
 * Test that beforeRedirect works with returning an array from the controller method.
 *
 * @return void
 */
	public function testRedirectBeforeRedirectInControllerWithArray() {
		$Controller = $this->getMock('Cake\Controller\Controller', array('_stop', 'beforeRedirect'));
		$Controller->response = $this->getMock('Cake\Network\Response', array('header'));
		$Controller->Components = $this->getMock('Cake\Controller\ComponentCollection', array('trigger'));

		$Controller->expects($this->once())
			->method('beforeRedirect')
			->with('http://cakephp.org', null, true)
			->will($this->returnValue(array(
				'url' => 'http://example.org',
				'status' => 302,
				'exit' => true
			)));

		$Controller->response->expects($this->at(0))
			->method('header')
			->with('Location', 'http://example.org');

		$Controller->expects($this->once())->method('_stop');
		$Controller->redirect('http://cakephp.org');
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

		$expected = array('Comment', 'Post');
		$this->assertEquals(
			$expected,
			$TestController->uses,
			'$uses was merged incorrectly, ControllerTestAppController models should be last.'
		);

		$TestController = new AnotherTestController($request);
		$TestController->constructClasses();

		$this->assertEquals('AnotherTest', $TestController->modelClass);
		$this->assertEquals(
			['AnotherTest', 'Post'],
			$TestController->uses,
			'Incorrect uses when controller does not define $uses.'
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
		$Controller->components = array();
		$Controller->uses = array();
		$Controller->constructClasses();

		$this->assertFalse(isset($Controller->Session));
	}

/**
 * testReferer method
 *
 * @return void
 */
	public function testReferer() {
		$request = $this->getMock('Cake\Network\Request');

		$request->expects($this->any())->method('referer')
			->with(true)
			->will($this->returnValue('/posts/index'));

		$Controller = new Controller($request);
		$result = $Controller->referer(null, true);
		$this->assertEquals('/posts/index', $result);

		$Controller = new Controller($request);
		$request->setReturnValue('referer', '/', array(true));
		$result = $Controller->referer(array('controller' => 'posts', 'action' => 'index'), true);
		$this->assertEquals('/posts/index', $result);

		$request = $this->getMock('Cake\Network\Request');

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
 * testValidateErrors method
 *
 * @return void
 */
	public function testValidateErrors() {
		ClassRegistry::flush();
		$request = new Request('controller_posts/index');

		$TestController = new TestController($request);
		$TestController->constructClasses();
		$this->assertFalse($TestController->validateErrors());
		$this->assertEquals(0, $TestController->validate());

		$TestController->Comment->invalidate('some_field', 'error_message');
		$TestController->Comment->invalidate('some_field2', 'error_message2');

		$comment = new \TestApp\Model\Comment($request);
		$comment->set('someVar', 'data');
		$result = $TestController->validateErrors($comment);
		$expected = array('some_field' => array('error_message'), 'some_field2' => array('error_message2'));
		$this->assertSame($expected, $result);
		$this->assertEquals(2, $TestController->validate($comment));
	}

/**
 * test that validateErrors works with any old model.
 *
 * @return void
 */
	public function testValidateErrorsOnArbitraryModels() {
		$TestController = new TestController();

		$Post = new \TestApp\Model\Post();
		$Post->validate = array('title' => 'notEmpty');
		$Post->set('title', '');
		$result = $TestController->validateErrors($Post);

		$expected = array('title' => array('This field cannot be left blank'));
		$this->assertEquals($expected, $result);
	}

/**
 * testControllerHttpCodes method
 *
 * @return void
 */
	public function testControllerHttpCodes() {
		$response = $this->getMock('Cake\Network\Response', array('httpCodes'));
		$Controller = new Controller(null, $response);
		$Controller->response->expects($this->at(0))->method('httpCodes')->with(null);
		$Controller->response->expects($this->at(1))->method('httpCodes')->with(100);
		$Controller->httpCodes();
		$Controller->httpCodes(100);
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
			);
		$eventManager->expects($this->at(1))->method('dispatch')
			->with(
				$this->logicalAnd(
					$this->isInstanceOf('Cake\Event\Event'),
					$this->attributeEqualTo('_name', 'Controller.startup'),
					$this->attributeEqualTo('_subject', $Controller)
				)
			);
		$Controller->expects($this->exactly(2))->method('getEventManager')
			->will($this->returnValue($eventManager));
		$Controller->startupProcess();
	}

/**
 * Tests that the shutdown process calls the correct functions
 *
 * @return void
 */
	public function testStartupProcessIndirect() {
		$Controller = $this->getMock('Cake\Controller\Controller', array('beforeFilter'));

		$Controller->components = array('MockShutdown');
		$Controller->Components = $this->getMock('Cake\Controller\ComponentCollection', array('trigger'));

		$Controller->expects($this->once())->method('beforeFilter');
		$Controller->Components->expects($this->exactly(2))->method('trigger')->with($this->isInstanceOf('Cake\Event\Event'));

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
			);
		$Controller->expects($this->once())->method('getEventManager')
			->will($this->returnValue($eventManager));
		$Controller->shutdownProcess();
	}

/**
 * Tests that the shutdown process calls the correct functions
 *
 * @return void
 */
	public function testShutdownProcessIndirect() {
		$Controller = $this->getMock('Cake\Controller\Controller', array('afterFilter'));

		$Controller->components = array('MockShutdown');
		$Controller->Components = $this->getMock('Cake\Controller\ComponentCollection', array('trigger'));

		$Controller->expects($this->once())->method('afterFilter');
		$Controller->Components->expects($this->exactly(1))->method('trigger')->with($this->isInstanceOf('Cake\Event\Event'));

		$Controller->shutdownProcess();
	}

/**
 * test that using Controller::paginate() falls back to PaginatorComponent
 *
 * @return void
 */
	public function testPaginateBackwardsCompatibility() {
		$request = new Request('controller_posts/index');
		$request->params['pass'] = array();
		$response = $this->getMock('Cake\Network\Response', ['httpCodes']);

		$Controller = new Controller($request, $response);
		$Controller->uses = ['Post', 'Comment'];
		$Controller->passedArgs[] = '1';
		$Controller->request->query['url'] = [];
		$Controller->constructClasses();
		$expected = ['page' => 1, 'limit' => 20, 'maxLimit' => 100];
		$this->assertEquals($expected, $Controller->paginate);

		$results = Hash::extract($Controller->paginate('Post'), '{n}.Post.id');
		$this->assertEquals([1, 2, 3], $results);

		$Controller->paginate = array('limit' => '1');
		$this->assertEquals(array('limit' => '1'), $Controller->paginate);
		$Controller->paginate('Post');
		$this->assertSame($Controller->request->params['paging']['Post']['page'], 1);
		$this->assertSame($Controller->request->params['paging']['Post']['pageCount'], 3);
		$this->assertSame($Controller->request->params['paging']['Post']['prevPage'], false);
		$this->assertSame($Controller->request->params['paging']['Post']['nextPage'], true);
	}

/**
 * testMissingAction method
 *
 * @expectedException Cake\Error\MissingActionException
 * @expectedExceptionMessage Action TestController::missing() could not be found.
 * @return void
 */
	public function testInvokeActionMissingAction() {
		$url = new Request('test/missing');
		$url->addParams(array('controller' => 'test_controller', 'action' => 'missing'));
		$response = $this->getMock('Cake\Network\Response');

		$Controller = new TestController($url, $response);
		$Controller->invokeAction($url);
	}

/**
 * test invoking private methods.
 *
 * @expectedException Cake\Error\PrivateActionException
 * @expectedExceptionMessage Private Action TestController::private_m() is not directly accessible.
 * @return void
 */
	public function testInvokeActionPrivate() {
		$url = new Request('test/private_m/');
		$url->addParams(array('controller' => 'test_controller', 'action' => 'private_m'));
		$response = $this->getMock('Cake\Network\Response');

		$Controller = new TestController($url, $response);
		$Controller->invokeAction($url);
	}

/**
 * test invoking protected methods.
 *
 * @expectedException Cake\Error\PrivateActionException
 * @expectedExceptionMessage Private Action TestController::protected_m() is not directly accessible.
 * @return void
 */
	public function testInvokeActionProtected() {
		$url = new Request('test/protected_m/');
		$url->addParams(array('controller' => 'test_controller', 'action' => 'protected_m'));
		$response = $this->getMock('Cake\Network\Response');

		$Controller = new TestController($url, $response);
		$Controller->invokeAction($url);
	}

/**
 * test invoking hidden methods.
 *
 * @expectedException Cake\Error\PrivateActionException
 * @expectedExceptionMessage Private Action TestController::_hidden() is not directly accessible.
 * @return void
 */
	public function testInvokeActionHidden() {
		$url = new Request('test/_hidden/');
		$url->addParams(array('controller' => 'test_controller', 'action' => '_hidden'));
		$response = $this->getMock('Cake\Network\Response');

		$Controller = new TestController($url, $response);
		$Controller->invokeAction($url);
	}

/**
 * test invoking controller methods.
 *
 * @expectedException Cake\Error\PrivateActionException
 * @expectedExceptionMessage Private Action TestController::redirect() is not directly accessible.
 * @return void
 */
	public function testInvokeActionBaseMethods() {
		$url = new Request('test/redirect/');
		$url->addParams(array('controller' => 'test_controller', 'action' => 'redirect'));
		$response = $this->getMock('Cake\Network\Response');

		$Controller = new TestController($url, $response);
		$Controller->invokeAction($url);
	}

/**
 * test invoking controller methods.
 *
 * @expectedException Cake\Error\PrivateActionException
 * @expectedExceptionMessage Private Action TestController::admin_add() is not directly accessible.
 * @return void
 */
	public function testInvokeActionPrefixProtection() {
		Router::reload();
		Router::connect('/admin/:controller/:action/*', array('prefix' => 'admin'));

		$url = new Request('test/admin_add/');
		$url->addParams(array('controller' => 'test_controller', 'action' => 'admin_add'));
		$response = $this->getMock('Cake\Network\Response');

		$Controller = new TestController($url, $response);
		$Controller->invokeAction($url);
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
		$result = $Controller->invokeAction($url);
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

}
