<?php
/**
 * ControllerTest file
 *
 * PHP 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP Project
 * @package       cake.tests.cases.libs.controller
 * @since         CakePHP(tm) v 1.2.0.5436
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
App::import('Controller', 'Controller', false);
App::import('Core', array('CakeRequest', 'CakeResponse'));
App::import('Component', 'Security');
App::import('Component', 'Cookie');


/**
 * AppController class
 *
 * @package       cake.tests.cases.libs.controller
 */
class ControllerTestAppController extends Controller {
/**
 * helpers property
 *
 * @var array
 * @access public
 */
	public $helpers = array('Html');
/**
 * uses property
 *
 * @var array
 * @access public
 */
	public $uses = array('ControllerPost');
/**
 * components property
 *
 * @var array
 * @access public
 */
	public $components = array('Cookie');
}


/**
 * ControllerPost class
 *
 * @package       cake.tests.cases.libs.controller
 */
class ControllerPost extends CakeTestModel {

/**
 * name property
 *
 * @var string 'ControllerPost'
 * @access public
 */
	public $name = 'ControllerPost';

/**
 * useTable property
 *
 * @var string 'posts'
 * @access public
 */
	public $useTable = 'posts';

/**
 * invalidFields property
 *
 * @var array
 * @access public
 */
	public $invalidFields = array('name' => 'error_msg');

/**
 * lastQuery property
 *
 * @var mixed null
 * @access public
 */
	public $lastQuery = null;

/**
 * beforeFind method
 *
 * @param mixed $query
 * @access public
 * @return void
 */
	function beforeFind($query) {
		$this->lastQuery = $query;
	}

/**
 * find method
 *
 * @param mixed $type
 * @param array $options
 * @access public
 * @return void
 */
	function find($conditions = null, $fields = array(), $order = null, $recursive = null) {
		if ($conditions == 'popular') {
			$conditions = array($this->name . '.' . $this->primaryKey .' > ' => '1');
			$options = Set::merge($fields, compact('conditions'));
			return parent::find('all', $fields);
		}
		return parent::find($conditions, $fields);
	}
}

/**
 * ControllerPostsController class
 *
 * @package       cake.tests.cases.libs.controller
 */
class ControllerCommentsController extends ControllerTestAppController {

/**
 * name property
 *
 * @var string 'ControllerPost'
 * @access public
 */
	public $name = 'ControllerComments';
	
	protected $_mergeParent = 'ControllerTestAppController';
}

/**
 * ControllerComment class
 *
 * @package       cake.tests.cases.libs.controller
 */
class ControllerComment extends CakeTestModel {

/**
 * name property
 *
 * @var string 'ControllerComment'
 * @access public
 */
	public $name = 'Comment';

/**
 * useTable property
 *
 * @var string 'comments'
 * @access public
 */
	public $useTable = 'comments';

/**
 * data property
 *
 * @var array
 * @access public
 */
	public $data = array('name' => 'Some Name');

/**
 * alias property
 *
 * @var string 'ControllerComment'
 * @access public
 */
	public $alias = 'ControllerComment';
}

/**
 * ControllerAlias class
 *
 * @package       cake.tests.cases.libs.controller
 */
class ControllerAlias extends CakeTestModel {

/**
 * name property
 *
 * @var string 'ControllerAlias'
 * @access public
 */
	public $name = 'ControllerAlias';

/**
 * alias property
 *
 * @var string 'ControllerSomeAlias'
 * @access public
 */
	public $alias = 'ControllerSomeAlias';

/**
 * useTable property
 *
 * @var string 'posts'
 * @access public
 */
	public $useTable = 'posts';
}

/**
 * NameTest class
 *
 * @package       cake.tests.cases.libs.controller
 */
class NameTest extends CakeTestModel {

/**
 * name property
 * @var string 'Name'
 * @access public
 */
	public $name = 'Name';

/**
 * useTable property
 * @var string 'names'
 * @access public
 */
	public $useTable = 'comments';

/**
 * alias property
 *
 * @var string 'ControllerComment'
 * @access public
 */
	public $alias = 'Name';
}

/**
 * TestController class
 *
 * @package       cake.tests.cases.libs.controller
 */
class TestController extends ControllerTestAppController {

/**
 * name property
 * @var string 'Name'
 * @access public
 */
	public $name = 'TestController';

/**
 * helpers property
 *
 * @var array
 * @access public
 */
	public $helpers = array('Session', 'Xml');

/**
 * components property
 *
 * @var array
 * @access public
 */
	public $components = array('Security');

/**
 * uses property
 *
 * @var array
 * @access public
 */
	public $uses = array('ControllerComment', 'ControllerAlias');
	
	protected $_mergeParent = 'ControllerTestAppController';

/**
 * index method
 *
 * @param mixed $testId
 * @param mixed $test2Id
 * @access public
 * @return void
 */
	function index($testId, $test2Id) {
		$this->data = array(
			'testId' => $testId,
			'test2Id' => $test2Id
		);
	}
}

/**
 * TestComponent class
 *
 * @package       cake.tests.cases.libs.controller
 */
class TestComponent extends Object {

/**
 * beforeRedirect method
 *
 * @access public
 * @return void
 */
	function beforeRedirect() {
	}
/**
 * initialize method
 *
 * @access public
 * @return void
 */
	function initialize($controller) {
	}

/**
 * startup method
 *
 * @access public
 * @return void
 */
	function startup($controller) {
	}
/**
 * shutdown method
 *
 * @access public
 * @return void
 */
	function shutdown($controller) {
	}
/**
 * beforeRender callback
 *
 * @return void
 */
	function beforeRender($controller) {
		if ($this->viewclass) {
			$controller->view = $this->viewclass;
		}
	}
}

/**
 * AnotherTestController class
 *
 * @package       cake.tests.cases.libs.controller
 */
class AnotherTestController extends ControllerTestAppController {

/**
 * name property
 * @var string 'Name'
 * @access public
 */
	public $name = 'AnotherTest';
/**
 * uses property
 *
 * @var array
 * @access public
 */
	public $uses = null;
	
	protected $_mergeParent = 'ControllerTestAppController';
}

/**
 * ControllerTest class
 *
 * @package       cake.tests.cases.libs.controller
 */
class ControllerTest extends CakeTestCase {

/**
 * fixtures property
 *
 * @var array
 * @access public
 */
	public $fixtures = array('core.post', 'core.comment', 'core.name');

/**
 * reset environment.
 *
 * @return void
 */
	function setUp() {
		App::objects('plugin', null, false);
		App::build();
		Router::reload();
	}

/**
 * teardown
 *
 * @access public
 * @return void
 */
	function teardown() {
		App::build();
	}

/**
 * testLoadModel method
 *
 * @access public
 * @return void
 */
	function testLoadModel() {
		$request = new CakeRequest('controller_posts/index');
		$Controller = new Controller($request);

		$this->assertFalse(isset($Controller->ControllerPost));

		$result = $Controller->loadModel('ControllerPost');
		$this->assertTrue($result);
		$this->assertTrue(is_a($Controller->ControllerPost, 'ControllerPost'));
		$this->assertTrue(in_array('ControllerPost', $Controller->modelNames));

		ClassRegistry::flush();
		unset($Controller);
	}

/**
 * testLoadModel method from a plugin controller
 *
 * @access public
 * @return void
 */
	function testLoadModelInPlugins() {
		App::build(array(
			'plugins' => array(TEST_CAKE_CORE_INCLUDE_PATH . 'tests' . DS . 'test_app' . DS . 'plugins' . DS),
			'controllers' => array(TEST_CAKE_CORE_INCLUDE_PATH . 'tests' . DS . 'test_app' . DS . 'controllers' . DS),
			'models' => array(TEST_CAKE_CORE_INCLUDE_PATH . 'tests' . DS . 'test_app' . DS . 'models' . DS)
		));
		App::import('Controller', 'TestPlugin.TestPlugin');

		$Controller = new TestPluginController();
		$Controller->plugin = 'TestPlugin';
		$Controller->uses = false;

		$this->assertFalse(isset($Controller->Comment));

		$result = $Controller->loadModel('Comment');
		$this->assertTrue($result);
		$this->assertInstanceOf('Comment', $Controller->Comment);
		$this->assertTrue(in_array('Comment', $Controller->modelNames));

		ClassRegistry::flush();
		unset($Controller);
	}

/**
 * testConstructClasses method
 *
 * @access public
 * @return void
 */
	function testConstructClasses() {
		$request = new CakeRequest('controller_posts/index');
		$Controller = new Controller($request);

		$Controller->modelClass = 'ControllerPost';
		$Controller->passedArgs[] = '1';
		$Controller->constructClasses();
		$this->assertEqual($Controller->ControllerPost->id, 1);

		unset($Controller);

		$Controller = new Controller($request);
		$Controller->uses = array('ControllerPost', 'ControllerComment');
		$Controller->passedArgs[] = '1';
		$Controller->constructClasses();
		$this->assertTrue(is_a($Controller->ControllerPost, 'ControllerPost'));
		$this->assertTrue(is_a($Controller->ControllerComment, 'ControllerComment'));

		$this->assertEqual($Controller->ControllerComment->name, 'Comment');

		unset($Controller);

		App::build(array('plugins' => array(TEST_CAKE_CORE_INCLUDE_PATH . 'tests' . DS . 'test_app' . DS . 'plugins' . DS)));

		$Controller = new Controller($request);
		$Controller->uses = array('TestPlugin.TestPluginPost');
		$Controller->constructClasses();

		$this->assertEqual($Controller->modelClass, 'TestPluginPost');
		$this->assertTrue(isset($Controller->TestPluginPost));
		$this->assertTrue(is_a($Controller->TestPluginPost, 'TestPluginPost'));

		unset($Controller);
	}

/**
 * testAliasName method
 *
 * @access public
 * @return void
 */
	function testAliasName() {
		$request = new CakeRequest('controller_posts/index');
		$Controller = new Controller($request);
		$Controller->uses = array('NameTest');
		$Controller->constructClasses();

		$this->assertEqual($Controller->NameTest->name, 'Name');
		$this->assertEqual($Controller->NameTest->alias, 'Name');

		unset($Controller);
	}

/**
 * testPersistent method
 *
 * @access public
 * @return void
 */
	function testPersistent() {
		$this->markTestIncomplete('persistModel is totally broken right now.');

		Configure::write('Cache.disable', false);
		$Controller = new Controller();
		$Controller->modelClass = 'ControllerPost';
		$Controller->persistModel = true;
		$Controller->constructClasses();
		$this->assertTrue(file_exists(CACHE . 'persistent' . DS .'controllerpost.php'));
		$this->assertTrue(is_a($Controller->ControllerPost, 'ControllerPost'));
		@unlink(CACHE . 'persistent' . DS . 'controllerpost.php');
		@unlink(CACHE . 'persistent' . DS . 'controllerpostregistry.php');

		unset($Controller);
		Configure::write('Cache.disable', true);
	}

/**
 * testFlash method
 *
 * @access public
 * @return void
 */
	function testFlash() {
		$request = new CakeRequest('controller_posts/index');
		$request->webroot = '/';
		$request->base = '/';

		$Controller = new Controller($request);
		$Controller->response = $this->getMock('CakeResponse', array('_sendHeader'));
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
		$this->assertEqual($result, $expected);

		App::build(array(
			'views' => array(TEST_CAKE_CORE_INCLUDE_PATH . 'tests' . DS . 'test_app' . DS . 'views'. DS)
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
 * @access public
 * @return void
 */
	function testControllerSet() {
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
 * @access public
 * @return void
 */
	function testRender() {
		App::build(array(
			'views' => array(TEST_CAKE_CORE_INCLUDE_PATH . 'tests' . DS . 'test_app' . DS . 'views'. DS)
		), true);
		$request = new CakeRequest('controller_posts/index');


		$Controller = new Controller($request, $this->getMock('CakeResponse'));
		$Controller->viewPath = 'posts';

		$result = $Controller->render('index');
		$this->assertPattern('/posts index/', $result);

		$result = $Controller->render('/elements/test_element');
		$this->assertPattern('/this is the test element/', $result);

		$Controller = new TestController($request);
		$Controller->helpers = array('Html');
		$Controller->constructClasses();
		$Controller->ControllerComment->validationErrors = array('title' => 'tooShort');
		$expected = $Controller->ControllerComment->validationErrors;

		ClassRegistry::flush();
		$Controller->viewPath = 'posts';
		$result = $Controller->render('index');
		$View = $Controller->View;
		$this->assertTrue(isset($View->validationErrors['ControllerComment']));
		$this->assertEqual($expected, $View->validationErrors['ControllerComment']);

		$Controller->ControllerComment->validationErrors = array();
		ClassRegistry::flush();

		App::build();
	}

/**
 * test that a component beforeRender can change the controller view class.
 *
 * @return void
 */
	function testComponentBeforeRenderChangingViewClass() {
		$core = App::core('views');
		App::build(array(
			'views' => array(
				TEST_CAKE_CORE_INCLUDE_PATH . 'tests' . DS . 'test_app' . DS . 'views'. DS,
				$core[0]
			)
		), true);
		$Controller = new Controller($this->getMock('CakeRequest'));
		$Controller->uses = array();
		$Controller->components = array('Test');
		$Controller->constructClasses();
		$Controller->Test->viewclass = 'Theme';
		$Controller->viewPath = 'posts';
		$Controller->theme = 'test_theme';
		$result = $Controller->render('index');
		$this->assertPattern('/default test_theme layout/', $result);
		App::build();
	}

/**
 * testToBeInheritedGuardmethods method
 *
 * @access public
 * @return void
 */
	function testToBeInheritedGuardmethods() {
		$request = new CakeRequest('controller_posts/index');

		$Controller = new Controller($request);
		$this->assertTrue($Controller->_beforeScaffold(''));
		$this->assertTrue($Controller->_afterScaffoldSave(''));
		$this->assertTrue($Controller->_afterScaffoldSaveError(''));
		$this->assertFalse($Controller->_scaffoldError(''));
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
 * @access public
 * @return void
 */
	function testRedirectByCode($code, $msg) {
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
	function testRedirectByMessage($code, $msg) {
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
	function testRedirectTriggeringComponentsReturnNull() {
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
	function testRedirectBeforeRedirectModifyingParams() {
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
	function testRedirectBeforeRedirectModifyingParamsArrayReturn() {
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
 * testMergeVars method
 *
 * @access public
 * @return void
 */
	function testMergeVars() {
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

		$this->assertFalse(isset($TestController->ControllerPost));


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
 * @access public
 * @return void
 */
	function testChildComponentOptionsSupercedeParents() {
		$request = new CakeRequest('controller_posts/index');

		$TestController = new TestController($request);

		$expected = array('foo');
		$TestController->components = array('Cookie' => $expected);
		$TestController->constructClasses();
		$this->assertEqual($TestController->components['Cookie'], $expected);
	}

/**
 * Ensure that __mergeVars is not being greedy and merging with
 * ControllerTestAppController when you make an instance of Controller
 *
 * @return void
 */
	function testMergeVarsNotGreedy() {
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
 * @access public
 * @return void
 */
	function testReferer() {
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
 * @access public
 * @return void
 */
	function testSetAction() {
		$request = new CakeRequest('controller_posts/index');

		$TestController = new TestController($request);
		$TestController->setAction('index', 1, 2);
		$expected = array('testId' => 1, 'test2Id' => 2);
		$this->assertidentical($TestController->data, $expected);
	}

/**
 * testUnimplementedIsAuthorized method
 *
 * @expectedException PHPUnit_Framework_Error
 * @access public
 * @return void
 */
	function testUnimplementedIsAuthorized() {
		$request = new CakeRequest('controller_posts/index');

		$TestController = new TestController($request);
		$TestController->isAuthorized();
	}

/**
 * testValidateErrors method
 *
 * @access public
 * @return void
 */
	function testValidateErrors() {
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
		$expected = array('some_field' => 'error_message', 'some_field2' => 'error_message2');
		$this->assertIdentical($result, $expected);
		$this->assertEqual($TestController->validate($comment), 2);
	}

/**
 * test that validateErrors works with any old model.
 *
 * @return void
 */
	function testValidateErrorsOnArbitraryModels() {
		$TestController = new TestController();

		$Post = new ControllerPost();
		$Post->validate = array('title' => 'notEmpty');
		$Post->set('title', '');
		$result = $TestController->validateErrors($Post);

		$expected = array('title' => 'This field cannot be left blank');
		$this->assertEqual($result, $expected);
	}

/**
 * testPostConditions method
 *
 * @access public
 * @return void
 */
	function testPostConditions() {
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
		$this->assertIdentical($result, $expected);


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
		$this->assertIdentical($result, $expected);


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
		$this->assertIdentical($result, $expected);
	}

/**
 * testRequestHandlerPrefers method
 *
 * @access public
 * @return void
 */
	function testRequestHandlerPrefers(){
		Configure::write('debug', 2);

		$request = new CakeRequest('controller_posts/index');

		$Controller = new Controller($request);

		$Controller->components = array("RequestHandler");
		$Controller->modelClass='ControllerPost';
		$Controller->params['url'] = array('ext' => 'rss');
		$Controller->constructClasses();
		$Controller->Components->trigger('initialize', array(&$Controller));
		$Controller->beforeFilter();
		$Controller->Components->trigger('startup', array(&$Controller));

		$this->assertEqual($Controller->RequestHandler->prefers(), 'rss');
		unset($Controller);
	}

/**
 * testControllerHttpCodes method
 *
 * @access public
 * @return void
 */
	function testControllerHttpCodes() {
		$Controller = new Controller(null);
		$Controller->response = $this->getMock('CakeResponse', array('httpCodes'));
		$Controller->response->expects($this->at(0))->method('httpCodes')->with(null);
		$Controller->response->expects($this->at(1))->method('httpCodes')->with(100);
		$Controller->httpCodes();
		$Controller->httpCodes(100);
	}

/**
 * Tests that the startup process calls the correct functions
 *
 * @access public
 * @return void
 */
	function testStartupProcess() {
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
 * @access public
 * @return void
 */
	function testShutdownProcess() {
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
	function testPropertyBackwardsCompatibility() {
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
	function testPropertyCompatibilityAndModelsComponents() {
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
	function testPaginateBackwardsCompatibility() {
		$request = new CakeRequest('controller_posts/index');
		$request->params['pass'] = $request->params['named'] = array();

		$Controller = new Controller($request);
		$Controller->uses = array('ControllerPost', 'ControllerComment');
		$Controller->passedArgs[] = '1';
		$Controller->params['url'] = array();
		$Controller->constructClasses();
		$this->assertEqual($Controller->paginate, array('page' => 1, 'limit' => 20));

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
}
