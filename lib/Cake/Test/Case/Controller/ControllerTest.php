<?php
/**
 * ControllerTest file
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
 * @link          http://cakephp.org CakePHP Project
 * @package       Cake.Test.Case.Controller
 * @since         CakePHP(tm) v 1.2.0.5436
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
App::uses('Controller', 'Controller');
App::uses('Router', 'Routing');
App::uses('CakeRequest', 'Network');
App::uses('CakeResponse', 'Network');
App::uses('SecurityComponent', 'Controller/Component');
App::uses('CookieComponent', 'Controller/Component');

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
	public $uses = array('ControllerPost');
/**
 * components property
 *
 * @var array
 */
	public $components = array('Cookie');
}


/**
 * ControllerPost class
 *
 * @package       Cake.Test.Case.Controller
 */
class ControllerPost extends CakeTestModel {

/**
 * name property
 *
 * @var string 'ControllerPost'
 */
	public $name = 'ControllerPost';

/**
 * useTable property
 *
 * @var string 'posts'
 */
	public $useTable = 'posts';

/**
 * invalidFields property
 *
 * @var array
 */
	public $invalidFields = array('name' => 'error_msg');

/**
 * lastQuery property
 *
 * @var mixed null
 */
	public $lastQuery = null;

/**
 * beforeFind method
 *
 * @param mixed $query
 * @return void
 */
	public function beforeFind($query) {
		$this->lastQuery = $query;
	}

/**
 * find method
 *
 * @param mixed $type
 * @param array $options
 * @return void
 */
	public function find($type = 'first', $options = array()) {
		if ($type == 'popular') {
			$conditions = array($this->name . '.' . $this->primaryKey .' > ' => '1');
			$options = Set::merge($options, compact('conditions'));
			return parent::find('all', $options);
		}
		return parent::find($type, $options);
	}
}

/**
 * ControllerPostsController class
 *
 * @package       Cake.Test.Case.Controller
 */
class ControllerCommentsController extends ControllerTestAppController {

/**
 * name property
 *
 * @var string 'ControllerPost'
 */
	public $name = 'ControllerComments';

	protected $_mergeParent = 'ControllerTestAppController';
}

/**
 * ControllerComment class
 *
 * @package       Cake.Test.Case.Controller
 */
class ControllerComment extends CakeTestModel {

/**
 * name property
 *
 * @var string 'ControllerComment'
 */
	public $name = 'Comment';

/**
 * useTable property
 *
 * @var string 'comments'
 */
	public $useTable = 'comments';

/**
 * data property
 *
 * @var array
 */
	public $data = array('name' => 'Some Name');

/**
 * alias property
 *
 * @var string 'ControllerComment'
 */
	public $alias = 'ControllerComment';
}

/**
 * ControllerAlias class
 *
 * @package       Cake.Test.Case.Controller
 */
class ControllerAlias extends CakeTestModel {

/**
 * name property
 *
 * @var string 'ControllerAlias'
 */
	public $name = 'ControllerAlias';

/**
 * alias property
 *
 * @var string 'ControllerSomeAlias'
 */
	public $alias = 'ControllerSomeAlias';

/**
 * useTable property
 *
 * @var string 'posts'
 */
	public $useTable = 'posts';
}

/**
 * NameTest class
 *
 * @package       Cake.Test.Case.Controller
 */
class NameTest extends CakeTestModel {

/**
 * name property
 * @var string 'Name'
 */
	public $name = 'Name';

/**
 * useTable property
 * @var string 'names'
 */
	public $useTable = 'comments';

/**
 * alias property
 *
 * @var string 'ControllerComment'
 */
	public $alias = 'Name';
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
	public $uses = array('ControllerComment', 'ControllerAlias');

	protected $_mergeParent = 'ControllerTestAppController';

/**
 * index method
 *
 * @param mixed $testId
 * @param mixed $test2Id
 * @return void
 */
	public function index($testId, $test2Id) {
		$this->data = array(
			'testId' => $testId,
			'test2Id' => $test2Id
		);
	}

	public function returner() {
		return 'I am from the controller.';
	}

	protected function protected_m() {

	}

	private function private_m() {

	}

	public function _hidden() {

	}

	public function admin_add() {

	}
}

/**
 * TestComponent class
 *
 * @package       Cake.Test.Case.Controller
 */
class TestComponent extends Object {
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
 * @return void
 */
	public function initialize(&$controller) {
	}

/**
 * startup method
 *
 * @return void
 */
	public function startup(&$controller) {
	}
/**
 * shutdown method
 *
 * @return void
 */
	public function shutdown(&$controller) {
	}
/**
 * beforeRender callback
 *
 * @return void
 */
	public function beforeRender(&$controller) {
		if ($this->viewclass) {
			$controller->viewClass = $this->viewclass;
		}
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
/**
 * uses property
 *
 * @var array
 */
	public $uses = null;

	protected $_mergeParent = 'ControllerTestAppController';
}

/**
 * ControllerTest class
 *
 * @package       Cake.Test.Case.Controller
 */
class ControllerTest extends CakeTestCase {

/**
 * fixtures property
 *
 * @var array
 */
	public $fixtures = array('core.post', 'core.comment', 'core.name');

/**
 * reset environment.
 *
 * @return void
 */
	public function setUp() {
		App::objects('plugin', null, false);
		App::build();
		Router::reload();
	}

/**
 * teardown
 *
 * @return void
 */
	public function teardown() {
		CakePlugin::unload();
		App::build();
	}

/**
 * testLoadModel method
 *
 * @return void
 */
	public function testLoadModel() {
		$request = new CakeRequest('controller_posts/index');
		$response = $this->getMock('CakeResponse');
		$Controller = new Controller($request, $response);

		$this->assertFalse(isset($Controller->ControllerPost));

		$result = $Controller->loadModel('ControllerPost');
		$this->assertTrue($result);
		$this->assertTrue(is_a($Controller->ControllerPost, 'ControllerPost'));
		$this->assertTrue(in_array('ControllerPost', $Controller->uses));

		ClassRegistry::flush();
		unset($Controller);
	}

/**
 * testLoadModel method from a plugin controller
 *
 * @return void
 */
	public function testLoadModelInPlugins() {
		App::build(array(
			'plugins' => array(CAKE . 'Test' . DS . 'test_app' . DS . 'Plugin' . DS),
			'Controller' => array(CAKE . 'Test' . DS . 'test_app' . DS . 'Controller' . DS),
			'Model' => array(CAKE . 'Test' . DS . 'test_app' . DS . 'Model' . DS)
		));
		CakePlugin::load('TestPlugin');
		App::uses('TestPluginAppController', 'TestPlugin.Controller');
		App::uses('TestPluginController', 'TestPlugin.Controller');

		$Controller = new TestPluginController();
		$Controller->plugin = 'TestPlugin';
		$Controller->uses = false;

		$this->assertFalse(isset($Controller->Comment));

		$result = $Controller->loadModel('Comment');
		$this->assertTrue($result);
		$this->assertInstanceOf('Comment', $Controller->Comment);
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
		$request = new CakeRequest('controller_posts/index');
		$Controller = new Controller($request);

		$Controller = new Controller($request);
		$Controller->uses = array('ControllerPost', 'ControllerComment');
		$Controller->constructClasses();
		$this->assertTrue(is_a($Controller->ControllerPost, 'ControllerPost'));
		$this->assertTrue(is_a($Controller->ControllerComment, 'ControllerComment'));

		$this->assertEqual($Controller->ControllerComment->name, 'Comment');

		unset($Controller);

		App::build(array('plugins' => array(CAKE . 'Test' . DS . 'test_app' . DS . 'Plugin' . DS)));
		CakePlugin::load('TestPlugin');

		$Controller = new Controller($request);
		$Controller->uses = array('TestPlugin.TestPluginPost');
		$Controller->constructClasses();

		$this->assertTrue(isset($Controller->TestPluginPost));
		$this->assertTrue(is_a($Controller->TestPluginPost, 'TestPluginPost'));

		unset($Controller);
	}

/**
 * testAliasName method
 *
 * @return void
 */
	public function testAliasName() {
		$request = new CakeRequest('controller_posts/index');
		$Controller = new Controller($request);
		$Controller->uses = array('NameTest');
		$Controller->constructClasses();

		$this->assertEqual($Controller->NameTest->name, 'Name');
		$this->assertEqual($Controller->NameTest->alias, 'Name');

		unset($Controller);
	}

/**
 * testFlash method
 *
 * @return void
 */
	public function testFlash() {
		$request = new CakeRequest('controller_posts/index');
		$request->webroot = '/';
		$request->base = '/';

		$Controller = new Controller($request, $this->getMock('CakeResponse', array('_sendHeader')));
		$Controller->flash('this should work', '/flash');
		$result = $Controller->response->body();

		$expected = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
		<html xmlns="http://www.w3.org/1999/xhtml">
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
		$expected =  str_replace(array("\t", "\r\n", "\n"), "", $expected);
		$this->assertEqual($expected, $result);

		App::build(array(
			'View' => array(CAKE . 'Test' . DS . 'test_app' . DS . 'View'. DS)
		));
		$Controller = new Controller($request);
		$Controller->response = $this->getMock('CakeResponse', array('_sendHeader'));
		$Controller->flash('this should work', '/flash', 1, 'ajax2');
		$result = $Controller->response->body();
		$this->assertPattern('/Ajax!/', $result);
		App::build();
	}

/**
 * testControllerSet method
 *
 * @return void
 */
	public function testControllerSet() {
		$request = new CakeRequest('controller_posts/index');
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
		$this->assertIdentical($Controller->viewVars['title'], 'someTitle');
		$this->assertTrue(empty($Controller->pageTitle));

		$Controller->viewVars = array();
		$expected = array('ModelName' => 'name', 'ModelName2' => 'name2');
		$Controller->set(array('ModelName', 'ModelName2'), array('name', 'name2'));
		$this->assertIdentical($Controller->viewVars, $expected);

		$Controller->viewVars = array();
		$Controller->set(array(3 => 'three', 4 => 'four'));
		$Controller->set(array(1 => 'one', 2 => 'two'));
		$expected = array(3 => 'three', 4 => 'four', 1 => 'one', 2 => 'two');
		$this->assertEqual($Controller->viewVars, $expected);

	}

/**
 * testRender method
 *
 * @return void
 */
	public function testRender() {
		App::build(array(
			'View' => array(CAKE . 'Test' . DS . 'test_app' . DS . 'View'. DS)
		), true);
		ClassRegistry::flush();
		$request = new CakeRequest('controller_posts/index');
		$request->params['action'] = 'index';

		$Controller = new Controller($request, new CakeResponse());
		$Controller->viewPath = 'Posts';

		$result = $Controller->render('index');
		$this->assertPattern('/posts index/', (string)$result);

		$Controller->view = 'index';
		$result = $Controller->render();
		$this->assertPattern('/posts index/', (string)$result);

		$result = $Controller->render('/Elements/test_element');
		$this->assertPattern('/this is the test element/', (string)$result);
		$Controller->view = null;

		$Controller = new TestController($request, new CakeResponse());
		$Controller->uses = array('ControllerAlias', 'TestPlugin.ControllerComment', 'ControllerPost');
		$Controller->helpers = array('Html');
		$Controller->constructClasses();
		$Controller->ControllerComment->validationErrors = array('title' => 'tooShort');
		$expected = $Controller->ControllerComment->validationErrors;

		$Controller->viewPath = 'Posts';
		$result = $Controller->render('index');
		$View = $Controller->View;
		$this->assertTrue(isset($View->validationErrors['ControllerComment']));
		$this->assertEqual($expected, $View->validationErrors['ControllerComment']);

		$expectedModels = array(
			'ControllerAlias' => array('plugin' => null, 'className' => 'ControllerAlias'),
			'ControllerComment' => array('plugin' => 'TestPlugin', 'className' => 'ControllerComment'),
			'ControllerPost' => array('plugin' => null, 'className' => 'ControllerPost')
		);
		$this->assertEqual($expectedModels, $Controller->request->params['models']);

		ClassRegistry::flush();
		App::build();
	}

/**
 * test that a component beforeRender can change the controller view class.
 *
 * @return void
 */
	public function testComponentBeforeRenderChangingViewClass() {
		App::build(array(
			'View' => array(
				CAKE . 'Test' . DS . 'test_app' . DS . 'View'. DS
			)
		), true);
		$Controller = new Controller($this->getMock('CakeRequest'), new CakeResponse());
		$Controller->uses = array();
		$Controller->components = array('Test');
		$Controller->constructClasses();
		$Controller->Test->viewclass = 'Theme';
		$Controller->viewPath = 'Posts';
		$Controller->theme = 'TestTheme';
		$result = $Controller->render('index');
		$this->assertPattern('/default test_theme layout/', (string)$result);
		App::build();
	}

/**
 * testToBeInheritedGuardmethods method
 *
 * @return void
 */
	public function testToBeInheritedGuardmethods() {
		$request = new CakeRequest('controller_posts/index');

		$Controller = new Controller($request, $this->getMock('CakeResponse'));
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
			array(307, "Temporary Redirect")
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
		$Controller->response = $this->getMock('CakeResponse', array('header', 'statusCode'));

		$Controller->Components = $this->getMock('ComponentCollection');

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
		$Controller->response = $this->getMock('CakeResponse', array('header', 'statusCode'));

		$Controller->Components = $this->getMock('ComponentCollection');

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
		$Controller->response = $this->getMock('CakeResponse', array('header', 'statusCode'));
		$Controller->Components = $this->getMock('ComponentCollection');

		$Controller->Components->expects($this->once())->method('trigger')
			->will($this->returnValue(null));

		$Controller->response->expects($this->once())->method('statusCode')
			->with(301);

		$Controller->response->expects($this->once())->method('header')
			->with('Location', 'http://cakephp.org');

		$Controller->redirect('http://cakephp.org', 301, false);
	}

/**
 * test that beforeRedirect callback returnning null doesn't affect things.
 *
 * @return void
 */
	public function testRedirectBeforeRedirectModifyingParams() {
		$Controller = new Controller(null);
		$Controller->response = $this->getMock('CakeResponse', array('header', 'statusCode'));
		$Controller->Components = $this->getMock('ComponentCollection');

		$Controller->Components->expects($this->once())->method('trigger')
			->will($this->returnValue(array('http://book.cakephp.org')));

		$Controller->response->expects($this->once())->method('statusCode')
			->with(301);

		$Controller->response->expects($this->once())->method('header')
			->with('Location', 'http://book.cakephp.org');

		$Controller->redirect('http://cakephp.org', 301, false);
	}

/**
 * test that beforeRedirect callback returnning null doesn't affect things.
 *
 * @return void
 */
	public function testRedirectBeforeRedirectModifyingParamsArrayReturn() {
		$Controller = $this->getMock('Controller', array('header', '_stop'));
		$Controller->response = $this->getMock('CakeResponse');
		$Controller->Components = $this->getMock('ComponentCollection');

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

		$Controller->response->expects($this->at(0))->method('header')
			->with('Location', 'http://example.com/test/2');

		$Controller->response->expects($this->at(1))->method('statusCode')
			->with(302);

		$Controller->expects($this->never())->method('_stop');
		$Controller->redirect('http://cakephp.org', 301);
	}

/**
 * test that beforeRedirect callback returnning false in controller
 *
 * @return void
 */
	public function testRedirectBeforeRedirectInController() {
		$Controller = $this->getMock('Controller', array('_stop', 'beforeRedirect'));
		$Controller->response = $this->getMock('CakeResponse', array('header'));
		$Controller->Components = $this->getMock('ComponentCollection');

		$Controller->expects($this->once())->method('beforeRedirect')
			->will($this->returnValue(false));
		$Controller->response->expects($this->never())->method('header');
		$Controller->expects($this->never())->method('_stop');
		$Controller->redirect('http://cakephp.org');
	}

/**
 * testMergeVars method
 *
 * @return void
 */
	public function testMergeVars() {
		$request = new CakeRequest('controller_posts/index');

		$TestController = new TestController($request);
		$TestController->constructClasses();

		$testVars = get_class_vars('TestController');
		$appVars = get_class_vars('ControllerTestAppController');

		$components = is_array($appVars['components'])
						? array_merge($appVars['components'], $testVars['components'])
						: $testVars['components'];
		if (!in_array('Session', $components)) {
			$components[] = 'Session';
		}
		$helpers = is_array($appVars['helpers'])
					? array_merge($appVars['helpers'], $testVars['helpers'])
					: $testVars['helpers'];
		$uses = is_array($appVars['uses'])
					? array_merge($appVars['uses'], $testVars['uses'])
					: $testVars['uses'];

		$this->assertEqual(count(array_diff_key($TestController->helpers, array_flip($helpers))), 0);
		$this->assertEqual(count(array_diff($TestController->uses, $uses)), 0);
		$this->assertEqual(count(array_diff_assoc(Set::normalize($TestController->components), Set::normalize($components))), 0);

		$expected = array('ControllerComment', 'ControllerAlias', 'ControllerPost');
		$this->assertEquals($expected, $TestController->uses, '$uses was merged incorrectly, ControllerTestAppController models should be last.');

		$TestController = new AnotherTestController($request);
		$TestController->constructClasses();

		$appVars = get_class_vars('ControllerTestAppController');
		$testVars = get_class_vars('AnotherTestController');


		$this->assertTrue(in_array('ControllerPost', $appVars['uses']));
		$this->assertNull($testVars['uses']);

		$this->assertFalse(property_exists($TestController, 'ControllerPost'));


		$TestController = new ControllerCommentsController($request);
		$TestController->constructClasses();

		$appVars = get_class_vars('ControllerTestAppController');
		$testVars = get_class_vars('ControllerCommentsController');


		$this->assertTrue(in_array('ControllerPost', $appVars['uses']));
		$this->assertEqual(array('ControllerPost'), $testVars['uses']);

		$this->assertTrue(isset($TestController->ControllerPost));
		$this->assertTrue(isset($TestController->ControllerComment));
	}

/**
 * test that options from child classes replace those in the parent classes.
 *
 * @return void
 */
	public function testChildComponentOptionsSupercedeParents() {
		$request = new CakeRequest('controller_posts/index');

		$TestController = new TestController($request);

		$expected = array('foo');
		$TestController->components = array('Cookie' => $expected);
		$TestController->constructClasses();
		$this->assertEqual($TestController->components['Cookie'], $expected);
	}

/**
 * Ensure that _mergeControllerVars is not being greedy and merging with
 * ControllerTestAppController when you make an instance of Controller
 *
 * @return void
 */
	public function testMergeVarsNotGreedy() {
		$request = new CakeRequest('controller_posts/index');

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
		$request = $this->getMock('CakeRequest');

		$request->expects($this->any())->method('referer')
			->with(true)
			->will($this->returnValue('/posts/index'));

		$Controller = new Controller($request);
		$result = $Controller->referer(null, true);
		$this->assertEqual($result, '/posts/index');

		$Controller = new Controller($request);
		$request->setReturnValue('referer', '/', array(true));
		$result = $Controller->referer(array('controller' => 'posts', 'action' => 'index'), true);
		$this->assertEqual($result, '/posts/index');

		$request = $this->getMock('CakeRequest');

		$request->expects($this->any())->method('referer')
			->with(false)
			->will($this->returnValue('http://localhost/posts/index'));

		$Controller = new Controller($request);
		$result = $Controller->referer();
		$this->assertEqual($result, 'http://localhost/posts/index');

		$Controller = new Controller(null);
		$result = $Controller->referer();
		$this->assertEqual($result, '/');
	}

/**
 * testSetAction method
 *
 * @return void
 */
	public function testSetAction() {
		$request = new CakeRequest('controller_posts/index');

		$TestController = new TestController($request);
		$TestController->setAction('index', 1, 2);
		$expected = array('testId' => 1, 'test2Id' => 2);
		$this->assertSame($expected, $TestController->request->data);
		$this->assertSame('index', $TestController->view);
	}

/**
 * testValidateErrors method
 *
 * @return void
 */
	public function testValidateErrors() {
		ClassRegistry::flush();
		$request = new CakeRequest('controller_posts/index');

		$TestController = new TestController($request);
		$TestController->constructClasses();
		$this->assertFalse($TestController->validateErrors());
		$this->assertEqual($TestController->validate(), 0);

		$TestController->ControllerComment->invalidate('some_field', 'error_message');
		$TestController->ControllerComment->invalidate('some_field2', 'error_message2');

		$comment = new ControllerComment($request);
		$comment->set('someVar', 'data');
		$result = $TestController->validateErrors($comment);
		$expected = array('some_field' => array('error_message'), 'some_field2' => array('error_message2'));
		$this->assertIdentical($expected, $result);
		$this->assertEqual($TestController->validate($comment), 2);
	}

/**
 * test that validateErrors works with any old model.
 *
 * @return void
 */
	public function testValidateErrorsOnArbitraryModels() {
		$TestController = new TestController();

		$Post = new ControllerPost();
		$Post->validate = array('title' => 'notEmpty');
		$Post->set('title', '');
		$result = $TestController->validateErrors($Post);

		$expected = array('title' => array('This field cannot be left blank'));
		$this->assertEqual($expected, $result);
	}

/**
 * testPostConditions method
 *
 * @return void
 */
	public function testPostConditions() {
		$request = new CakeRequest('controller_posts/index');

		$Controller = new Controller($request);

		$data = array(
			'Model1' => array('field1' => '23'),
			'Model2' => array('field2' => 'string'),
			'Model3' => array('field3' => '23'),
		);
		$expected = array(
			'Model1.field1' => '23',
			'Model2.field2' => 'string',
			'Model3.field3' => '23',
		);
		$result = $Controller->postConditions($data);
		$this->assertIdentical($expected, $result);


		$data = array();
		$Controller->data = array(
			'Model1' => array('field1' => '23'),
			'Model2' => array('field2' => 'string'),
			'Model3' => array('field3' => '23'),
		);
		$expected = array(
			'Model1.field1' => '23',
			'Model2.field2' => 'string',
			'Model3.field3' => '23',
		);
		$result = $Controller->postConditions($data);
		$this->assertIdentical($expected, $result);


		$data = array();
		$Controller->data = array();
		$result = $Controller->postConditions($data);
		$this->assertNull($result);


		$data = array();
		$Controller->data = array(
			'Model1' => array('field1' => '23'),
			'Model2' => array('field2' => 'string'),
			'Model3' => array('field3' => '23'),
		);
		$ops = array(
			'Model1.field1' => '>',
			'Model2.field2' => 'LIKE',
			'Model3.field3' => '<=',
		);
		$expected = array(
			'Model1.field1 >' => '23',
			'Model2.field2 LIKE' => "%string%",
			'Model3.field3 <=' => '23',
		);
		$result = $Controller->postConditions($data, $ops);
		$this->assertIdentical($expected, $result);
	}

/**
 * testControllerHttpCodes method
 *
 * @return void
 */
	public function testControllerHttpCodes() {
		$response = $this->getMock('CakeResponse', array('httpCodes'));
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
		$Controller = $this->getMock('Controller', array('beforeFilter', 'afterFilter'));

		$Controller->components = array('MockStartup');
		$Controller->Components = $this->getMock('ComponentCollection');

		$Controller->expects($this->once())->method('beforeFilter');
		$Controller->Components->expects($this->at(0))->method('trigger')
			->with('initialize', array(&$Controller));

		$Controller->Components->expects($this->at(1))->method('trigger')
			->with('startup', array(&$Controller));

		$Controller->startupProcess();
	}
/**
 * Tests that the shutdown process calls the correct functions
 *
 * @return void
 */
	public function testShutdownProcess() {
		$Controller = $this->getMock('Controller', array('beforeFilter', 'afterFilter'));

		$Controller->components = array('MockShutdown');
		$Controller->Components = $this->getMock('ComponentCollection');

		$Controller->expects($this->once())->method('afterFilter');
		$Controller->Components->expects($this->once())->method('trigger')
			->with('shutdown', array(&$Controller));

		$Controller->shutdownProcess();
	}

/**
 * test that BC works for attributes on the request object.
 *
 * @return void
 */
	public function testPropertyBackwardsCompatibility() {
		$request = new CakeRequest('posts/index', null);
		$request->addParams(array('controller' => 'posts', 'action' => 'index'));
		$request->data = array('Post' => array('id' => 1));
		$request->here = '/posts/index';
		$request->webroot = '/';

		$Controller = new TestController($request);
		$this->assertEquals($request->data, $Controller->data);
		$this->assertEquals($request->webroot, $Controller->webroot);
		$this->assertEquals($request->here, $Controller->here);
		$this->assertEquals($request->action, $Controller->action);

		$this->assertFalse(empty($Controller->data));
		$this->assertTrue(isset($Controller->data));
		$this->assertTrue(empty($Controller->something));
		$this->assertFalse(isset($Controller->something));

		$this->assertEquals($request, $Controller->params);
		$this->assertEquals($request->params['controller'], $Controller->params['controller']);
	}

/**
 * test that the BC wrapper doesn't interfere with models and components.
 *
 * @return void
 */
	public function testPropertyCompatibilityAndModelsComponents() {
		$request = new CakeRequest('controller_posts/index');

		$Controller = new TestController($request);
		$Controller->constructClasses();
		$this->assertInstanceOf('SecurityComponent', $Controller->Security);
		$this->assertInstanceOf('ControllerComment', $Controller->ControllerComment);
	}

/**
 * test that using Controller::paginate() falls back to PaginatorComponent
 *
 * @return void
 */
	public function testPaginateBackwardsCompatibility() {
		$request = new CakeRequest('controller_posts/index');
		$request->params['pass'] = $request->params['named'] = array();
		$response = $this->getMock('CakeResponse', array('httpCodes'));

		$Controller = new Controller($request, $response);
		$Controller->uses = array('ControllerPost', 'ControllerComment');
		$Controller->passedArgs[] = '1';
		$Controller->params['url'] = array();
		$Controller->constructClasses();
		$expected = array('page' => 1, 'limit' => 20, 'maxLimit' => 100, 'paramType' => 'named');
		$this->assertEqual($Controller->paginate, $expected);

		$results = Set::extract($Controller->paginate('ControllerPost'), '{n}.ControllerPost.id');
		$this->assertEqual($results, array(1, 2, 3));

		$Controller->passedArgs = array();
		$Controller->paginate = array('limit' => '-1');
		$this->assertEqual($Controller->paginate, array('limit' => '-1'));
		$Controller->paginate('ControllerPost');
		$this->assertIdentical($Controller->params['paging']['ControllerPost']['page'], 1);
		$this->assertIdentical($Controller->params['paging']['ControllerPost']['pageCount'], 3);
		$this->assertIdentical($Controller->params['paging']['ControllerPost']['prevPage'], false);
		$this->assertIdentical($Controller->params['paging']['ControllerPost']['nextPage'], true);
	}

/**
 * testMissingAction method
 *
 * @expectedException MissingActionException
 * @expectedExceptionMessage Action TestController::missing() could not be found.
 * @return void
 */
	public function testInvokeActionMissingAction() {
		$url = new CakeRequest('test/missing');
		$url->addParams(array('controller' => 'test_controller', 'action' => 'missing'));
		$response = $this->getMock('CakeResponse');

		$Controller = new TestController($url, $response);
		$Controller->invokeAction($url);
	}

/**
 * test invoking private methods.
 *
 * @expectedException PrivateActionException
 * @expectedExceptionMessage Private Action TestController::private_m() is not directly accessible.
 * @return void
 */
	public function testInvokeActionPrivate() {
		$url = new CakeRequest('test/private_m/');
		$url->addParams(array('controller' => 'test_controller', 'action' => 'private_m'));
		$response = $this->getMock('CakeResponse');

		$Controller = new TestController($url, $response);
		$Controller->invokeAction($url);
	}

/**
 * test invoking protected methods.
 *
 * @expectedException PrivateActionException
 * @expectedExceptionMessage Private Action TestController::protected_m() is not directly accessible.
 * @return void
 */
	public function testInvokeActionProtected() {
		$url = new CakeRequest('test/protected_m/');
		$url->addParams(array('controller' => 'test_controller', 'action' => 'protected_m'));
		$response = $this->getMock('CakeResponse');

		$Controller = new TestController($url, $response);
		$Controller->invokeAction($url);
	}

/**
 * test invoking hidden methods.
 *
 * @expectedException PrivateActionException
 * @expectedExceptionMessage Private Action TestController::_hidden() is not directly accessible.
 * @return void
 */
	public function testInvokeActionHidden() {
		$url = new CakeRequest('test/_hidden/');
		$url->addParams(array('controller' => 'test_controller', 'action' => '_hidden'));
		$response = $this->getMock('CakeResponse');

		$Controller = new TestController($url, $response);
		$Controller->invokeAction($url);
	}

/**
 * test invoking controller methods.
 *
 * @expectedException PrivateActionException
 * @expectedExceptionMessage Private Action TestController::redirect() is not directly accessible.
 * @return void
 */
	public function testInvokeActionBaseMethods() {
		$url = new CakeRequest('test/redirect/');
		$url->addParams(array('controller' => 'test_controller', 'action' => 'redirect'));
		$response = $this->getMock('CakeResponse');

		$Controller = new TestController($url, $response);
		$Controller->invokeAction($url);
	}

/**
 * test invoking controller methods.
 *
 * @expectedException PrivateActionException
 * @expectedExceptionMessage Private Action TestController::admin_add() is not directly accessible.
 * @return void
 */
	public function testInvokeActionPrefixProtection() {
		Router::reload();
		Router::connect('/admin/:controller/:action/*', array('prefix'=>'admin'));

		$url = new CakeRequest('test/admin_add/');
		$url->addParams(array('controller' => 'test_controller', 'action' => 'admin_add'));
		$response = $this->getMock('CakeResponse');

		$Controller = new TestController($url, $response);
		$Controller->invokeAction($url);
	}

/**
 * test invoking controller methods.
 *
 * @return void
 */
	public function testInvokeActionReturnValue() {
		$url = new CakeRequest('test/returner/');
		$url->addParams(array(
			'controller' => 'test_controller',
			'action' => 'returner',
			'pass' => array()
		));
		$response = $this->getMock('CakeResponse');

		$Controller = new TestController($url, $response);
		$result = $Controller->invokeAction($url);
		$this->assertEquals('I am from the controller.', $result);
	}


}
