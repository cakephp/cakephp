<?php
/**
 * ControllerTestCaseTest file
 *
 * Test Case for ControllerTestCase class
 *
 * CakePHP : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP Project
 * @package       Cake.Test.Case.TestSuite
 * @since         CakePHP v 2.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

App::uses('Controller', 'Controller');
App::uses('Model', 'Model');
App::uses('AppModel', 'Model');
App::uses('CakeHtmlReporter', 'TestSuite/Reporter');

require_once dirname(dirname(__FILE__)) . DS . 'Model' . DS . 'models.php';

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
} elseif (!defined('APP_CONTROLLER_EXISTS')) {
	define('APP_CONTROLLER_EXISTS', true);
}

/**
 * PostsController class
 */
if (!class_exists('PostsController')) {

/**
 * PostsController
 *
 * @package       Cake.Test.Case.TestSuite
 */
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
 *
 * @package       Cake.Test.Case.TestSuite
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
			'Plugin' => array(CAKE . 'Test' . DS . 'test_app' . DS . 'Plugin' . DS),
			'Controller' => array(CAKE . 'Test' . DS . 'test_app' . DS . 'Controller' . DS),
			'Model' => array(CAKE . 'Test' . DS . 'test_app' . DS . 'Model' . DS),
			'View' => array(CAKE . 'Test' . DS . 'test_app' . DS . 'View' . DS)
		), App::RESET);
		CakePlugin::load(array('TestPlugin', 'TestPluginTwo'));
		$this->Case = $this->getMockForAbstractClass('ControllerTestCase');
		Router::reload();
	}

/**
 * tearDown
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
 *
 * @return void
 */
	public function testGenerate() {
		if (defined('APP_CONTROLLER_EXISTS')) {
			$this->markTestSkipped('AppController exists, cannot run.');
		}
		$Posts = $this->Case->generate('Posts');
		$this->assertEquals('Posts', $Posts->name);
		$this->assertEquals('Post', $Posts->modelClass);
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
		$this->assertEquals('posts', $Posts->Post->useTable);
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
		$this->assertEquals('written!', $Posts->Auth->Session->write('something'));
	}

/**
 * testGenerateWithComponentConfig
 *
 * @return void
 */
	public function testGenerateWithComponentConfig() {
		$Tests = $this->Case->generate('TestConfigs', array(
		));

		$expected = array('some' => 'config');
		$settings = array_intersect_key($Tests->RequestHandler->settings, array('some' => 'foo'));
		$this->assertSame($expected, $settings, 'A mocked component should have the same config as an unmocked component');

		$Tests = $this->Case->generate('TestConfigs', array(
			'components' => array(
				'RequestHandler' => array('isPut')
			)
		));

		$expected = array('some' => 'config');
		$settings = array_intersect_key($Tests->RequestHandler->settings, array('some' => 'foo'));
		$this->assertSame($expected, $settings, 'A mocked component should have the same config as an unmocked component');
	}

/**
 * Tests ControllerTestCase::generate() using classes from plugins
 *
 * @return void
 */
	public function testGenerateWithPlugin() {
		$Tests = $this->Case->generate('TestPlugin.Tests', array(
			'models' => array(
				'TestPlugin.TestPluginComment'
			),
			'components' => array(
				'TestPlugin.Plugins'
			)
		));
		$this->assertEquals('Tests', $Tests->name);
		$this->assertInstanceOf('PluginsComponent', $Tests->Plugins);

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
 *
 * @return void
 */
	public function testTestAction() {
		$this->Case->generate('TestsApps');
		$this->Case->testAction('/tests_apps/index');
		$this->assertInternalType('array', $this->Case->controller->viewVars);

		$this->Case->testAction('/tests_apps/set_action');
		$results = $this->Case->controller->viewVars;
		$expected = array(
			'var' => 'string'
		);
		$this->assertEquals($expected, $results);

		$result = $this->Case->controller->response->body();
		$this->assertRegExp('/This is the TestsAppsController index view/', $result);

		$Controller = $this->Case->generate('TestsApps');
		$this->Case->testAction('/tests_apps/redirect_to');
		$results = $this->Case->headers;
		$expected = array(
			'Location' => 'https://cakephp.org'
		);
		$this->assertEquals($expected, $results);
		$this->assertSame(302, $Controller->response->statusCode());
	}

/**
 * Test array URLs with testAction()
 *
 * @return void
 */
	public function testTestActionArrayUrls() {
		$this->Case->generate('TestsApps');
		$this->Case->testAction(array('controller' => 'tests_apps', 'action' => 'index'));
		$this->assertInternalType('array', $this->Case->controller->viewVars);
	}

/**
 * Test that file responses don't trigger errors.
 *
 * @return void
 */
	public function testActionWithFile() {
		$Controller = $this->Case->generate('TestsApps');
		$this->Case->testAction('/tests_apps/file');
		$this->assertArrayHasKey('Content-Disposition', $Controller->response->header());
		$this->assertArrayHasKey('Content-Length', $Controller->response->header());
	}

/**
 * Make sure testAction() can hit plugin controllers.
 *
 * @return void
 */
	public function testTestActionWithPlugin() {
		$this->Case->generate('TestPlugin.Tests');
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
		$result = $this->Case->testAction('/tests_apps/index.json', array('return' => 'contents'));
		$result = json_decode($result, true);
		$expected = array('cakephp' => 'cool');
		$this->assertEquals($expected, $result);

		include CAKE . 'Test' . DS . 'test_app' . DS . 'Config' . DS . 'routes.php';
		$result = $this->Case->testAction('/some_alias');
		$this->assertEquals(5, $result);
	}

/**
 * Tests not using loaded routes during tests
 *
 * @expectedException MissingActionException
 * @return void
 */
	public function testSkipRoutes() {
		Router::connect('/:controller/:action/*');
		include CAKE . 'Test' . DS . 'test_app' . DS . 'Config' . DS . 'routes.php';

		$this->Case->loadRoutes = false;
		$this->Case->testAction('/tests_apps/missing_action.json', array('return' => 'view'));
	}

/**
 * Tests backwards compatibility with setting the return type
 *
 * @return void
 */
	public function testBCSetReturn() {
		$this->Case->autoMock = true;

		$result = $this->Case->testAction('/tests_apps/some_method');
		$this->assertEquals(5, $result);

		$data = array('var' => 'set');
		$result = $this->Case->testAction('/tests_apps_posts/post_var', array(
			'data' => $data,
			'return' => 'vars'
		));
		$this->assertEquals($data, $result['data']);

		$result = $this->Case->testAction('/tests_apps/set_action', array(
			'return' => 'view'
		));
		$this->assertEquals('This is the TestsAppsController index view string', $result);

		$result = $this->Case->testAction('/tests_apps/set_action', array(
			'return' => 'contents'
		));
		$this->assertRegExp('/<html/', $result);
		$this->assertRegExp('/This is the TestsAppsController index view/', $result);
		$this->assertRegExp('/<\/html>/', $result);
	}

/**
 * Tests sending POST data to testAction
 *
 * @return void
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
		$this->assertEquals($expected, $this->Case->controller->request->named);
		$this->assertEquals($this->Case->controller->data, $data);

		$result = $this->Case->testAction('/tests_apps_posts/post_var', array(
			'return' => 'vars',
			'method' => 'post',
			'data' => array(
				'name' => 'is jonas',
				'pork' => 'and beans',
			)
		));
		$this->assertEquals(array('name', 'pork'), array_keys($result['data']));

		$result = $this->Case->testAction('/tests_apps_posts/add', array('return' => 'vars'));
		$this->assertTrue(array_key_exists('posts', $result));
		$this->assertEquals(4, count($result['posts']));
		$this->assertTrue($this->Case->controller->request->is('post'));
	}

/**
 * Tests sending GET data to testAction
 *
 * @return void
 */
	public function testTestActionGetData() {
		$this->Case->autoMock = true;

		$this->Case->testAction('/tests_apps_posts/url_var', array(
			'method' => 'get',
			'data' => array(
				'some' => 'var',
				'lackof' => 'creativity'
			)
		));
		$this->assertEquals('var', $this->Case->controller->request->query['some']);
		$this->assertEquals('creativity', $this->Case->controller->request->query['lackof']);

		$result = $this->Case->testAction('/tests_apps_posts/url_var/var1:value1/var2:val2', array(
			'return' => 'vars',
			'method' => 'get',
		));
		$this->assertEquals(array('var1', 'var2'), array_keys($result['params']['named']));

		$result = $this->Case->testAction('/tests_apps_posts/url_var/gogo/val2', array(
			'return' => 'vars',
			'method' => 'get',
		));
		$this->assertEquals(array('gogo', 'val2'), $result['params']['pass']);

		$this->Case->testAction('/tests_apps_posts/url_var', array(
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
 *
 * @return void
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
 *
 * @return void
 */
	public function testNoMocking() {
		$result = $this->Case->testAction('/tests_apps/some_method');
		$this->Case->assertEquals(5, $result);

		$data = array('var' => 'set');
		$result = $this->Case->testAction('/tests_apps_posts/post_var', array(
			'data' => $data,
			'return' => 'vars'
		));
		$this->assertEquals($data, $result['data']);

		$result = $this->Case->testAction('/tests_apps/set_action', array(
			'return' => 'view'
		));
		$this->assertEquals('This is the TestsAppsController index view string', $result);

		$result = $this->Case->testAction('/tests_apps/set_action', array(
			'return' => 'contents'
		));
		$this->assertRegExp('/<html/', $result);
		$this->assertRegExp('/This is the TestsAppsController index view/', $result);
		$this->assertRegExp('/<\/html>/', $result);
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
		$this->Case->generate('TestsApps');

		$options = array('method' => 'get');
		$this->Case->testAction('/tests_apps/redirect_to', $options);
		$this->Case->testAction('/tests_apps/redirect_to', $options);
	}

/**
 * Tests that Components storing response or request objects internally during construct
 * will always have a fresh reference to those object available
 *
 * @return void
 */
	public function testComponentsSameRequestAndResponse() {
		$this->Case->generate('TestsApps');
		$options = array('method' => 'get');
		$this->Case->testAction('/tests_apps/index', $options);
		$this->assertSame($this->Case->controller->response, $this->Case->controller->RequestHandler->response);
		$this->assertSame($this->Case->controller->request, $this->Case->controller->RequestHandler->request);
	}

/**
 * Test that testAction() doesn't destroy data in GET & POST
 *
 * @return void
 */
	public function testRestoreGetPost() {
		$restored = array('new' => 'value');

		$_GET = $restored;
		$_POST = $restored;

		$this->Case->generate('TestsApps');
		$options = array('method' => 'get');
		$this->Case->testAction('/tests_apps/index', $options);

		$this->assertEquals($restored, $_GET);
		$this->assertEquals($restored, $_POST);
	}

/**
 * Tests that the `App.base` path is properly stripped from the URL generated from the
 * given URL array, and that consequently the correct controller/action is being matched.
 *
 * @return void
 */
	public function testAppBaseConfigCompatibilityWithArrayUrls() {
		Configure::write('App.base', '/cakephp');

		$this->Case->generate('TestsApps');
		$this->Case->testAction(array('controller' => 'tests_apps', 'action' => 'index'));

		$this->assertEquals('/cakephp', $this->Case->controller->request->base);
		$this->assertEquals('/cakephp/', $this->Case->controller->request->webroot);
		$this->assertEquals('/cakephp/tests_apps', $this->Case->controller->request->here);
		$this->assertEquals('tests_apps', $this->Case->controller->request->url);

		$expected = array(
			'plugin' => null,
			'controller' => 'tests_apps',
			'action' => 'index',
			'named' => array(),
			'pass' => array(),
		);
		$this->assertEquals($expected, array_intersect_key($this->Case->controller->request->params, $expected));
	}

/**
 * Tests that query string data from URL arrays properly makes it into the request object
 * on GET requests.
 *
 * @return void
 */
	public function testTestActionWithArrayUrlQueryStringDataViaGetRequest() {
		$query = array('foo' => 'bar');

		$this->Case->generate('TestsApps');
		$this->Case->testAction(
			array(
				'controller' => 'tests_apps',
				'action' => 'index',
				'?' => $query
			),
			array(
				'method' => 'get'
			)
		);

		$this->assertEquals('tests_apps', $this->Case->controller->request->url);
		$this->assertEquals($query, $this->Case->controller->request->query);
	}

/**
 * Tests that query string data from URL arrays properly makes it into the request object
 * on POST requests.
 *
 * @return void
 */
	public function testTestActionWithArrayUrlQueryStringDataViaPostRequest() {
		$query = array('foo' => 'bar');

		$this->Case->generate('TestsApps');
		$this->Case->testAction(
			array(
				'controller' => 'tests_apps',
				'action' => 'index',
				'?' => $query
			),
			array(
				'method' => 'post'
			)
		);

		$this->assertEquals('tests_apps', $this->Case->controller->request->url);
		$this->assertEquals($query, $this->Case->controller->request->query);
	}

/**
 * Tests that query string data from both, URL arrays as well as the `data` option,
 * properly makes it into the request object.
 *
 * @return void
 */
	public function testTestActionWithArrayUrlQueryStringDataAndDataOptionViaGetRequest() {
		$query = array('foo' => 'bar');
		$data = array('bar' => 'foo');

		$this->Case->generate('TestsApps');
		$this->Case->testAction(
			array(
				'controller' => 'tests_apps',
				'action' => 'index',
				'?' => $query
			),
			array(
				'method' => 'get',
				'data' => $data
			)
		);

		$this->assertEquals('tests_apps', $this->Case->controller->request->url);
		$this->assertEquals($data + $query, $this->Case->controller->request->query);
	}
}
