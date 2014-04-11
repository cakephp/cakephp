<?php
/**
 * CakePHP : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP Project
 * @since         2.0.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\TestSuite;

use Cake\Controller\Controller;
use Cake\Core\App;
use Cake\Core\Configure;
use Cake\Core\Plugin;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\Routing\Router;
use Cake\TestSuite\Reporter\HtmlReporter;
use Cake\TestSuite\TestCase;

/**
 * AppController class
 *
 */
class AppController extends Controller {

/**
 * helpers property
 *
 * @var array
 */
	public $helpers = array('Html');

/**
 * components property
 *
 * @var array
 */
	public $components = array('Cookie');
}


/**
 * ControllerTestCaseTest controller
 *
 */
class ControllerTestCaseTestController extends AppController {

}

/**
 * ControllerTestCaseTest
 *
 */
class ControllerTestCaseTest extends TestCase {

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
		Configure::write('App.namespace', 'TestApp');
		Plugin::load(array('TestPlugin', 'TestPluginTwo'));

		$this->Case = $this->getMockForAbstractClass('Cake\TestSuite\ControllerTestCase');
		Router::reload();
		TableRegistry::clear();
	}

/**
 * tearDown
 *
 * @return void
 */
	public function tearDown() {
		parent::tearDown();
		Plugin::unload();
		$this->Case->controller = null;
	}

/**
 * Test that ControllerTestCase::generate() creates mock objects correctly
 *
 * @return void
 */
	public function testGenerate() {
		$Posts = $this->Case->generate('TestApp\Controller\PostsController');
		$this->assertEquals('Posts', $Posts->name);
		$this->assertEquals('Posts', $Posts->modelClass);
		$this->assertEquals('Posts', $Posts->viewPath);
		$this->assertNull($Posts->response->send());

		$Posts = $this->Case->generate('TestApp\Controller\PostsController', array(
			'methods' => array(
				'render'
			)
		));
		$this->assertNull($Posts->render('index'));

		$Posts = $this->Case->generate('TestApp\Controller\PostsController', array(
			'models' => array('Posts'),
			'components' => array('RequestHandler')
		));

		$this->assertInstanceOf('TestApp\Model\Table\PostsTable', $Posts->Posts);
		$this->assertNull($Posts->Posts->deleteAll(array()));
		$this->assertNull($Posts->Posts->find('all'));
		$this->assertNull($Posts->RequestHandler->isXml());

		$Posts = $this->Case->generate('TestApp\Controller\PostsController', array(
			'models' => array(
				'Posts' => true
			)
		));
		$this->assertNull($Posts->Posts->deleteAll([]));
		$this->assertNull($Posts->Posts->find('all'));

		$Posts = $this->Case->generate('TestApp\Controller\PostsController', array(
			'models' => array(
				'Posts' => array('deleteAll'),
			)
		));
		$this->assertNull($Posts->Posts->deleteAll([]));
		$this->assertEquals('posts', $Posts->Posts->table());

		$Posts = $this->Case->generate('TestApp\Controller\PostsController', array(
			'models' => array('Posts'),
			'components' => array(
				'RequestHandler' => array('isPut'),
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
		$settings = array_intersect_key($Tests->RequestHandler->config(), array('some' => 'foo'));
		$this->assertSame($expected, $settings, 'A mocked component should have the same config as an unmocked component');

		$Tests = $this->Case->generate('TestConfigs', array(
			'components' => array(
				'RequestHandler' => array('isPut')
			)
		));

		$expected = array('some' => 'config');
		$settings = array_intersect_key($Tests->RequestHandler->config(), array('some' => 'foo'));
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
				'TestPlugin.TestPluginComments'
			),
			'components' => array(
				'TestPlugin.Plugins'
			)
		));
		$this->assertEquals('Tests', $Tests->name);
		$this->assertInstanceOf('TestPlugin\Controller\Component\PluginsComponent', $Tests->Plugins);

		$result = TableRegistry::get('TestPlugin.TestPluginComments');
		$this->assertInstanceOf('TestPlugin\Model\Table\TestPluginCommentsTable', $result);
	}

/**
 * Tests testAction
 *
 * @return void
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
		$this->assertRegExp('/This is the TestsAppsController index view/', $result);

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
		include APP . '/Config/routes.php';

		$controller = $this->Case->generate('TestsApps');
		$controller->components()->load('RequestHandler');
		$result = $this->Case->testAction('/tests_apps/index.json', array('return' => 'contents'));
		$result = json_decode($result, true);
		$expected = array('cakephp' => 'cool');
		$this->assertEquals($expected, $result);

		include APP . '/Config/routes.php';
		$result = $this->Case->testAction('/some_alias');
		$this->assertEquals(5, $result);
	}

/**
 * Tests not using loaded routes during tests
 *
 * @expectedException \Cake\Controller\Error\MissingActionException
 * @return void
 */
	public function testSkipRoutes() {
		Router::connect('/:controller/:action/*');
		include APP . 'Config/routes.php';

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
			'method' => 'post',
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
			'name' => 'Some Post'
		);
		$this->Case->testAction('/tests_apps_posts/post_var', array(
			'method' => 'post',
			'data' => $data
		));
		$this->assertEquals($this->Case->controller->viewVars['data'], $data);
		$this->assertEquals($this->Case->controller->request->data, $data);

		$result = $this->Case->testAction('/tests_apps_posts/post_var', array(
			'return' => 'vars',
			'method' => 'post',
			'data' => array(
				'name' => 'is jonas',
				'pork' => 'and beans',
			)
		));
		$this->assertEquals(array('name', 'pork'), array_keys($result['data']));

		$result = $this->Case->testAction('/tests_apps_posts/add', array(
			'method' => 'post',
			'return' => 'vars'
		));
		$this->assertTrue(array_key_exists('posts', $result));
		$this->assertInstanceOf('Cake\ORM\Query', $result['posts']);
		$this->assertTrue($this->Case->controller->request->is('post'));
	}

/**
 * Tests sending GET data to testAction
 *
 * @return void
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
		$this->assertEquals('var', $this->Case->controller->request->query['some']);
		$this->assertEquals('creativity', $this->Case->controller->request->query['lackof']);

		$result = $this->Case->testAction('/tests_apps_posts/url_var/gogo/val2', array(
			'return' => 'vars',
			'method' => 'get',
		));
		$this->assertEquals(array('gogo', 'val2'), $result['params']['pass']);

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
			'method' => 'post',
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
 * @see https://cakephp.lighthouseapp.com/projects/42648-cakephp/tickets/2705-requesthandler-weird-behavior
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

}
