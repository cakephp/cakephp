<?php
/**
 * ControllerTestCaseTest file
 *
 * Test Case for ControllerTestCase class
 *
 * PHP version 5
 *
 * CakePHP : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2011, Cake Software Foundation, Inc.
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2011, Cake Software Foundation, Inc.
 * @link          http://cakephp.org CakePHP Project
 * @package       Cake.Test.Case.TestSuite
 * @since         CakePHP v 2.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

App::uses('Controller', 'Controller');
App::uses('Model', 'Model');
App::uses('AppModel', 'Model');
App::uses('CakeHtmlReporter', 'TestSuite/Reporter');

require_once dirname(dirname(__FILE__)) . DS . 'Model' . DS . 'models.php';


/**
 * AppController class
 *
 * @package       Cake.Test.Case.TestSuite
 */
if (!class_exists('AppController', false)) {
	/**
	 * AppController class
	 *
		 * @package       Cake.Test.Case.TestSuite
	 */
	class AppController extends Controller {
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
} elseif (!defined('APP_CONTROLLER_EXISTS')) {
	define('APP_CONTROLLER_EXISTS', true);
}

/**
 * PostsController class
 */
if (!class_exists('PostsController')) {
	class PostsController extends AppController {

	/**
	 * Components array
	 *
	 * @var array
	 */
		public $components = array(
			'RequestHandler',
			'Email',
			'Auth'
		);
	}
}

/**
 * ControllerTestCaseTest controller
 */
class ControllerTestCaseTestController extends AppController {

/**
 * Uses array
 *
 * @param array
 */
	public $uses = array('TestPlugin.TestPluginComment');

}

/**
 * ControllerTestCaseTest
 *
 * @package       Cake.Test.Case.TestSuite
 */
class ControllerTestCaseTest extends CakeTestCase {

/**
 * fixtures property
 *
 * @var array
 */
	public $fixtures = array('core.post', 'core.author', 'core.test_plugin_comment');

/**
 * reset environment.
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();
		App::build(array(
			'plugins' => array(CAKE . 'Test' . DS . 'test_app' . DS . 'Plugin' . DS),
			'Controller' => array(CAKE . 'Test' . DS . 'test_app' . DS . 'Controller' . DS),
			'Model' => array(CAKE . 'Test' . DS . 'test_app' . DS . 'Model' . DS),
			'View' => array(CAKE . 'Test' . DS . 'test_app' . DS . 'View' . DS)
		), App::RESET);
		CakePlugin::loadAll();
		$this->Case = $this->getMockForAbstractClass('ControllerTestCase');
		Router::reload();
	}

/**
 * teardown
 *
 * @return void
 */
	public function tearDown() {
		parent::tearDown();
		CakePlugin::unload();
		$this->Case->controller = null;
	}

/**
 * Test that ControllerTestCase::generate() creates mock objects correctly
 */
	public function testGenerate() {
		if (defined('APP_CONTROLLER_EXISTS')) {
			$this->markTestSkipped('AppController exists, cannot run.');
		}
		$Posts = $this->Case->generate('Posts');
		$this->assertEquals($Posts->name, 'Posts');
		$this->assertEquals($Posts->modelClass, 'Post');
		$this->assertNull($Posts->response->send());

		$Posts = $this->Case->generate('Posts', array(
			'methods' => array(
				'render'
			)
		));
		$this->assertNull($Posts->render('index'));

		$Posts = $this->Case->generate('Posts', array(
			'models' => array('Post'),
			'components' => array('RequestHandler')
		));

		$this->assertInstanceOf('Post', $Posts->Post);
		$this->assertNull($Posts->Post->save(array()));
		$this->assertNull($Posts->Post->find('all'));
		$this->assertEquals($Posts->Post->useTable, 'posts');
		$this->assertNull($Posts->RequestHandler->isAjax());

		$Posts = $this->Case->generate('Posts', array(
			'models' => array(
				'Post' => true
			)
		));
		$this->assertNull($Posts->Post->save(array()));
		$this->assertNull($Posts->Post->find('all'));

		$Posts = $this->Case->generate('Posts', array(
			'models' => array(
				'Post' => array('save'),
			)
		));
		$this->assertNull($Posts->Post->save(array()));
		$this->assertInternalType('array', $Posts->Post->find('all'));

		$Posts = $this->Case->generate('Posts', array(
			'models' => array('Post'),
			'components' => array(
				'RequestHandler' => array('isPut'),
				'Email' => array('send'),
				'Session'
			)
		));
		$Posts->RequestHandler->expects($this->once())
			->method('isPut')
			->will($this->returnValue(true));
		$this->assertTrue($Posts->RequestHandler->isPut());

		$Posts->Auth->Session->expects($this->any())
			->method('write')
			->will($this->returnValue('written!'));
		$this->assertEquals($Posts->Auth->Session->write('something'), 'written!');
	}

/**
 * Tests ControllerTestCase::generate() using classes from plugins
 */
	public function testGenerateWithPlugin() {
		$Tests = $this->Case->generate('TestPlugin.Tests', array(
			'models' => array(
				'TestPlugin.TestPluginComment'
			),
			'components' => array(
				'TestPlugin.PluginsComponent'
			)
		));
		$this->assertEquals($Tests->name, 'Tests');
		$this->assertInstanceOf('PluginsComponentComponent', $Tests->PluginsComponent);

		$result = ClassRegistry::init('TestPlugin.TestPluginComment');
		$this->assertInstanceOf('TestPluginComment', $result);

		$Tests = $this->Case->generate('ControllerTestCaseTest', array(
			'models' => array(
				'TestPlugin.TestPluginComment' => array('save')
			)
		));
		$this->assertInstanceOf('TestPluginComment', $Tests->TestPluginComment);
		$Tests->TestPluginComment->expects($this->at(0))
			->method('save')
			->will($this->returnValue(true));
		$Tests->TestPluginComment->expects($this->at(1))
			->method('save')
			->will($this->returnValue(false));
		$this->assertTrue($Tests->TestPluginComment->save(array()));
		$this->assertFalse($Tests->TestPluginComment->save(array()));

	}

/**
 * Tests testAction
 */
	public function testTestAction() {
		$Controller = $this->Case->generate('TestsApps');
		$this->Case->testAction('/tests_apps/index');
		$this->assertInternalType('array', $this->Case->controller->viewVars);

		$this->Case->testAction('/tests_apps/set_action');
		$results = $this->Case->controller->viewVars;
		$expected = array(
			'var' => 'string'
		);
		$this->assertEquals($expected, $results);

		$result = $this->Case->controller->response->body();
		$this->assertPattern('/This is the TestsAppsController index view/', $result);

		$Controller = $this->Case->generate('TestsApps');
		$this->Case->testAction('/tests_apps/redirect_to');
		$results = $this->Case->headers;
		$expected = array(
			'Location' => 'http://cakephp.org'
		);
		$this->assertEquals($expected, $results);
	}

/**
 * Make sure testAction() can hit plugin controllers.
 *
 * @return void
 */
	public function testTestActionWithPlugin() {
		$Controller = $this->Case->generate('TestPlugin.Tests');
		$this->Case->testAction('/test_plugin/tests/index');
		$this->assertEquals('It is a variable', $this->Case->controller->viewVars['test_value']);
	}

/**
 * Tests using loaded routes during tests
 *
 * @return void
 */
	public function testUseRoutes() {
		Router::connect('/:controller/:action/*');
		include CAKE . 'Test' . DS . 'test_app' . DS . 'Config' . DS . 'routes.php';

		$controller = $this->Case->generate('TestsApps');
		$controller->Components->load('RequestHandler');
		$result = $this->Case->testAction('/tests_apps/index.json', array('return' => 'view'));
		$result = json_decode($result, true);
		$expected = array('cakephp' => 'cool');
		$this->assertEquals($expected, $result);

		include CAKE . 'Test' . DS . 'test_app' . DS . 'Config' . DS . 'routes.php';
		$result = $this->Case->testAction('/some_alias');
		$this->assertEquals($result, 5);
	}

/**
 * Tests not using loaded routes during tests
 *
 * @expectedException MissingActionException
 */
	public function testSkipRoutes() {
		Router::connect('/:controller/:action/*');
		include CAKE . 'Test' . DS . 'test_app' . DS . 'Config' . DS . 'routes.php';

		$this->Case->loadRoutes = false;
		$result = $this->Case->testAction('/tests_apps/missing_action.json', array('return' => 'view'));
	}

/**
 * Tests backwards compatibility with setting the return type
 */
	public function testBCSetReturn() {
		$this->Case->autoMock = true;

		$result = $this->Case->testAction('/tests_apps/some_method');
		$this->assertEquals($result, 5);

		$data = array('var' => 'set');
		$result = $this->Case->testAction('/tests_apps_posts/post_var', array(
			'data' => $data,
			'return' => 'vars'
		));
		$this->assertEquals($result['data'], $data);

		$result = $this->Case->testAction('/tests_apps/set_action', array(
			'return' => 'view'
		));
		$this->assertEquals($result, 'This is the TestsAppsController index view string');

		$result = $this->Case->testAction('/tests_apps/set_action', array(
			'return' => 'contents'
		));
		$this->assertPattern('/<html/', $result);
		$this->assertPattern('/This is the TestsAppsController index view/', $result);
		$this->assertPattern('/<\/html>/', $result);
	}

/**
 * Tests sending POST data to testAction
 */
	public function testTestActionPostData() {
		$this->Case->autoMock = true;

		$data = array(
			'Post' => array(
				'name' => 'Some Post'
			)
		);
		$this->Case->testAction('/tests_apps_posts/post_var', array(
			'data' => $data
		));
		$this->assertEquals($this->Case->controller->viewVars['data'], $data);
		$this->assertEquals($this->Case->controller->data, $data);

		$this->Case->testAction('/tests_apps_posts/post_var/named:param', array(
			'data' => $data
		));
		$expected = array(
			'named' => 'param'
		);
		$this->assertEqual($this->Case->controller->request->named, $expected);
		$this->assertEquals($this->Case->controller->data, $data);

		$result = $this->Case->testAction('/tests_apps_posts/post_var', array(
			'return' => 'vars',
			'method' => 'post',
			'data' => array(
				'name' => 'is jonas',
				'pork' => 'and beans',
			)
		));
		$this->assertEqual(array_keys($result['data']), array('name', 'pork'));

		$result = $this->Case->testAction('/tests_apps_posts/add', array('return' => 'vars'));
		$this->assertTrue(array_key_exists('posts', $result));
		$this->assertEqual(count($result['posts']), 4);
		$this->assertTrue($this->Case->controller->request->is('post'));
	}

/**
 * Tests sending GET data to testAction
 */
	public function testTestActionGetData() {
		$this->Case->autoMock = true;

		$result = $this->Case->testAction('/tests_apps_posts/url_var', array(
			'method' => 'get',
			'data' => array(
				'some' => 'var',
				'lackof' => 'creativity'
			)
		));
		$this->assertEquals($this->Case->controller->request->query['some'], 'var');
		$this->assertEquals($this->Case->controller->request->query['lackof'], 'creativity');

		$result = $this->Case->testAction('/tests_apps_posts/url_var/var1:value1/var2:val2', array(
			'return' => 'vars',
			'method' => 'get',
		));
		$this->assertEqual(array_keys($result['params']['named']), array('var1', 'var2'));

		$result = $this->Case->testAction('/tests_apps_posts/url_var/gogo/val2', array(
			'return' => 'vars',
			'method' => 'get',
		));
		$this->assertEqual($result['params']['pass'], array('gogo', 'val2'));

		$result = $this->Case->testAction('/tests_apps_posts/url_var', array(
			'return' => 'vars',
			'method' => 'get',
			'data' => array(
				'red' => 'health',
				'blue' => 'mana'
			)
		));
		$query = $this->Case->controller->request->query;
		$this->assertTrue(isset($query['red']));
		$this->assertTrue(isset($query['blue']));
	}

/**
 * Test that REST actions with XML/JSON input work.
 *
 * @return void
 */
	public function testTestActionJsonData() {
		$result = $this->Case->testAction('/tests_apps_posts/input_data', array(
			'return' => 'vars',
			'method' => 'post',
			'data' => '{"key":"value","json":true}'
		));
		$this->assertEquals('value', $result['data']['key']);
		$this->assertTrue($result['data']['json']);
	}

/**
 * Tests autoMock ability
 */
	public function testAutoMock() {
		$this->Case->autoMock = true;
		$this->Case->testAction('/tests_apps/set_action');
		$results = $this->Case->controller->viewVars;
		$expected = array(
			'var' => 'string'
		);
		$this->assertEquals($expected, $results);
	}

/**
 * Test using testAction and not mocking
 */
	public function testNoMocking() {
		$result = $this->Case->testAction('/tests_apps/some_method');
		$this->Case->assertEquals($result, 5);

		$data = array('var' => 'set');
		$result = $this->Case->testAction('/tests_apps_posts/post_var', array(
			'data' => $data,
			'return' => 'vars'
		));
		$this->assertEquals($result['data'], $data);

		$result = $this->Case->testAction('/tests_apps/set_action', array(
			'return' => 'view'
		));
		$this->assertEquals($result, 'This is the TestsAppsController index view string');

		$result = $this->Case->testAction('/tests_apps/set_action', array(
			'return' => 'contents'
		));
		$this->assertPattern('/<html/', $result);
		$this->assertPattern('/This is the TestsAppsController index view/', $result);
		$this->assertPattern('/<\/html>/', $result);
	}

/**
 * Test that controllers don't get reused.
 *
 * @return void
 */
	public function testNoControllerReuse() {
		$this->Case->autoMock = true;
		$result = $this->Case->testAction('/tests_apps/index', array(
			'data' => array('var' => 'first call'),
			'method' => 'get',
			'return' => 'contents',
		));
		$this->assertContains('<html', $result);
		$this->assertContains('This is the TestsAppsController index view', $result);
		$this->assertContains('first call', $result);
		$this->assertContains('</html>', $result);
	
		$result = $this->Case->testAction('/tests_apps/index', array(
			'data' => array('var' => 'second call'),
			'method' => 'get',
			'return' => 'contents'
		));
		$this->assertContains('second call', $result);

		$result = $this->Case->testAction('/tests_apps/index', array(
			'data' => array('var' => 'third call'),
			'method' => 'get',
			'return' => 'contents'
		));
		$this->assertContains('third call', $result);
	}

/**
 * Test that multiple calls to redirect in the same test method don't cause issues.
 *
 * @return void
 */
	public function testTestActionWithMultipleRedirect() {
		$Controller = $this->Case->generate('TestsApps');

		$options = array('method' => 'get');
		$this->Case->testAction('/tests_apps/redirect_to', $options);
		$this->Case->testAction('/tests_apps/redirect_to', $options);
	}

}
