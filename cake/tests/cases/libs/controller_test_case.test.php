<?php
/**
 * ControllerTestCaseTest file
 *
 * Test Case for ControllerTestCase class
 *
 * PHP version 5
 *
 * CakePHP : Rapid Development Framework (http://cakephp.org)
 * Copyright 2006-2010, Cake Software Foundation, Inc.
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2006-2010, Cake Software Foundation, Inc.
 * @link          http://cakephp.org CakePHP Project
 * @package       cake.libs.
 * @since         CakePHP v 2.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
App::import('Controller', 'Controller', false);
App::import('Core', array('AppModel', 'Model'));
require_once TEST_CAKE_CORE_INCLUDE_PATH  . 'tests' . DS . 'lib' . DS . 'reporter' . DS . 'cake_html_reporter.php';
require_once dirname(__FILE__) . DS . 'model' . DS . 'models.php';

/**
 * AppController class
 *
 * @package       cake.tests.cases.libs.controller
 */
if (!class_exists('AppController')) {
	/**
	 * AppController class
	 *
		 * @package       cake.tests.cases.libs.controller
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
 * ControllerTestCaseTest
 *
 * @package       cake.tests.cases.libs
 */
class ControllerTestCaseTest extends CakeTestCase {

/**
 * fixtures property
 *
 * @var array
 * @access public
 */
	public $fixtures = array('core.post', 'core.author');

/**
 * reset environment.
 *
 * @return void
 */
	function setUp() {
		parent::setUp();
		App::build(array(
			'plugins' => array(TEST_CAKE_CORE_INCLUDE_PATH . 'tests' . DS . 'test_app' . DS . 'plugins' . DS),
			'controllers' => array(TEST_CAKE_CORE_INCLUDE_PATH . 'tests' . DS . 'test_app' . DS . 'controllers' . DS),
			'models' => array(TEST_CAKE_CORE_INCLUDE_PATH . 'tests' . DS . 'test_app' . DS . 'models' . DS),
			'views' => array(TEST_CAKE_CORE_INCLUDE_PATH . 'tests' . DS . 'test_app' . DS . 'views' . DS)
		));
		$this->Case = new ControllerTestCase();
		Router::reload();
	}

/**
 * teardown
 *
 * @return void
 */
	function tearDown() {
		parent::tearDown();
		$this->Case->controller = null;
	}

/**
 * Test that ControllerTestCase::generate() creates mock objects correctly
 */
	function testGenerate() {
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
 * Tests testAction
 */
	function testTestAction() {
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

		$this->Case->testAction('/tests_apps/redirect_to');
		$results = $this->Case->headers;
		$expected = array(
			'Location' => 'http://cakephp.org'
		);
		$this->assertEquals($expected, $results);
	}

/**
 * Tests using loaded routes during tests
 */
	function testUseRoutes() {
		include TEST_CAKE_CORE_INCLUDE_PATH . 'tests' . DS . 'test_app' . DS . 'config' . DS . 'routes.php';
		$controller = $this->Case->generate('TestsApps');
		$controller->Components->load('RequestHandler');
		$result = $this->Case->testAction('/tests_apps/index.json', array('return' => 'view'));
		$result = json_decode($result, true);
		$expected = array('cakephp' => 'cool');
		$this->assertEquals($result, $expected);

		include TEST_CAKE_CORE_INCLUDE_PATH . 'tests' . DS . 'test_app' . DS . 'config' . DS . 'routes.php';
		$result = $this->Case->testAction('/some_alias');
		$this->assertEquals($result, 5);

		include TEST_CAKE_CORE_INCLUDE_PATH . 'tests' . DS . 'test_app' . DS . 'config' . DS . 'routes.php';
		$this->Case->testAction('/redirect_me_now');
		$result = $this->Case->headers['Location'];
		$this->assertEquals($result, 'http://cakephp.org');

		include TEST_CAKE_CORE_INCLUDE_PATH . 'tests' . DS . 'test_app' . DS . 'config' . DS . 'routes.php';
		$this->Case->testAction('/redirect_me');
		$result = $this->Case->headers['Location'];
		$this->assertEquals($result, Router::url(array('controller' => 'tests_apps', 'action' => 'some_method'), true));		
	}

/**
 * Tests not using loaded routes during tests
 *
 * @expectedException MissingActionException
 */
	function testSkipRoutes() {
		include TEST_CAKE_CORE_INCLUDE_PATH . 'tests' . DS . 'test_app' . DS . 'config' . DS . 'routes.php';

		$this->Case->loadRoutes = false;
		$result = $this->Case->testAction('/tests_apps/missing_action.json', array('return' => 'view'));
	}

/**
 * Tests backwards compatibility with setting the return type
 */
	function testBCSetReturn() {
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
		$this->assertEquals($result, 'This is the TestsAppsController index view');

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
	function testTestActionPostData() {
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
	}

/**
 * Tests sending GET data to testAction
 */
	function testTestActionGetData() {
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
		$this->assertTrue(isset($result['params']['url']['url']));
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
		$this->assertTrue(isset($result['params']['url']['red']));
		$this->assertTrue(isset($result['params']['url']['blue']));
		$this->assertTrue(isset($result['params']['url']['url']));
	}

/**
 * Tests autoMock ability
 */
	function testAutoMock() {
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
	function testNoMocking() {
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
		$this->assertEquals($result, 'This is the TestsAppsController index view');

		$result = $this->Case->testAction('/tests_apps/set_action', array(
			'return' => 'contents'
		));
		$this->assertPattern('/<html/', $result);
		$this->assertPattern('/This is the TestsAppsController index view/', $result);
		$this->assertPattern('/<\/html>/', $result);
	}

}