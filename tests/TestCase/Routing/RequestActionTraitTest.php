<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         3.0.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Routing;

use Cake\Core\App;
use Cake\Core\Configure;
use Cake\Core\Plugin;
use Cake\Routing\RequestActionTrait;
use Cake\Routing\Router;
use Cake\TestSuite\TestCase;

/**
 */
class RequestActionTraitTest extends TestCase {

/**
 * fixtures
 *
 * @var string
 */
	public $fixtures = array('core.post', 'core.test_plugin_comment', 'core.comment');

/**
 * Setup
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();
		Configure::write('App.namespace', 'TestApp');
		Configure::write('Security.salt', 'not-the-default');
		$this->object = $this->getObjectForTrait('Cake\Routing\RequestActionTrait');
	}

/**
 * testRequestAction method
 *
 * @return void
 */
	public function testRequestAction() {
		$this->assertNull(Router::getRequest(), 'request stack should be empty.');

		$result = $this->object->requestAction('');
		$this->assertFalse($result);

		$result = $this->object->requestAction('/request_action/test_request_action');
		$expected = 'This is a test';
		$this->assertEquals($expected, $result);

		$result = $this->object->requestAction(Configure::read('App.fullBaseUrl') . '/request_action/test_request_action');
		$expected = 'This is a test';
		$this->assertEquals($expected, $result);

		$result = $this->object->requestAction('/request_action/another_ra_test/2/5');
		$expected = 7;
		$this->assertEquals($expected, $result);

		$result = $this->object->requestAction('/tests_apps/index', array('return'));
		$expected = 'This is the TestsAppsController index view ';
		$this->assertEquals($expected, $result);

		$result = $this->object->requestAction('/tests_apps/some_method');
		$expected = 5;
		$this->assertEquals($expected, $result);

		$result = $this->object->requestAction('/request_action/paginate_request_action');
		$this->assertNull($result);

		$result = $this->object->requestAction('/request_action/normal_request_action');
		$expected = 'Hello World';
		$this->assertEquals($expected, $result);

		$this->assertNull(Router::getRequest(), 'requests were not popped off the stack, this will break url generation');
	}

/**
 * test requestAction() and plugins.
 *
 * @return void
 */
	public function testRequestActionPlugins() {
		Plugin::load('TestPlugin');
		Router::reload();

		$result = $this->object->requestAction('/test_plugin/tests/index', array('return'));
		$expected = 'test plugin index';
		$this->assertEquals($expected, $result);

		$result = $this->object->requestAction('/test_plugin/tests/index/some_param', array('return'));
		$expected = 'test plugin index';
		$this->assertEquals($expected, $result);

		$result = $this->object->requestAction(
			array('controller' => 'tests', 'action' => 'index', 'plugin' => 'test_plugin'), array('return')
		);
		$expected = 'test plugin index';
		$this->assertEquals($expected, $result);

		$result = $this->object->requestAction('/test_plugin/tests/some_method');
		$expected = 25;
		$this->assertEquals($expected, $result);

		$result = $this->object->requestAction(
			array('controller' => 'tests', 'action' => 'some_method', 'plugin' => 'test_plugin')
		);
		$expected = 25;
		$this->assertEquals($expected, $result);
	}

/**
 * test requestAction() with arrays.
 *
 * @return void
 */
	public function testRequestActionArray() {
		Plugin::load(array('TestPlugin'));

		$result = $this->object->requestAction(
			array('controller' => 'request_action', 'action' => 'test_request_action')
		);
		$expected = 'This is a test';
		$this->assertEquals($expected, $result);

		$result = $this->object->requestAction(
			array('controller' => 'request_action', 'action' => 'another_ra_test'),
			array('pass' => array('5', '7'))
		);
		$expected = 12;
		$this->assertEquals($expected, $result);

		$result = $this->object->requestAction(
			array('controller' => 'tests_apps', 'action' => 'index'), array('return')
		);
		$expected = 'This is the TestsAppsController index view ';
		$this->assertEquals($expected, $result);

		$result = $this->object->requestAction(array('controller' => 'tests_apps', 'action' => 'some_method'));
		$expected = 5;
		$this->assertEquals($expected, $result);

		$result = $this->object->requestAction(
			array('controller' => 'request_action', 'action' => 'normal_request_action')
		);
		$expected = 'Hello World';
		$this->assertEquals($expected, $result);

		$result = $this->object->requestAction(
			array('controller' => 'request_action', 'action' => 'paginate_request_action')
		);
		$this->assertNull($result);

		$result = $this->object->requestAction(
			array('controller' => 'request_action', 'action' => 'paginate_request_action'),
			array('pass' => array(5))
		);
		$this->assertNull($result);
	}

/**
 * Test that requestAction() does not forward the 0 => return value.
 *
 * @return void
 */
	public function testRequestActionRemoveReturnParam() {
		$result = $this->object->requestAction(
			'/request_action/param_check', array('return')
		);
		$this->assertEquals('', $result, 'Return key was found');
	}

/**
 * Test that requestAction() is populating $this->params properly
 *
 * @return void
 */
	public function testRequestActionParamParseAndPass() {
		$result = $this->object->requestAction('/request_action/params_pass');
		$result = json_decode($result, true);
		$this->assertEquals('request_action/params_pass', $result['url']);
		$this->assertEquals('request_action', $result['params']['controller']);
		$this->assertEquals('params_pass', $result['params']['action']);
		$this->assertNull($result['params']['plugin']);
	}

/**
 * test that requestAction does not fish data out of the POST
 * superglobal.
 *
 * @return void
 */
	public function testRequestActionNoPostPassing() {
		$_POST = array(
			'item' => 'value'
		);
		$result = $this->object->requestAction(array('controller' => 'request_action', 'action' => 'post_pass'));
		$result = json_decode($result, true);
		$this->assertEmpty($result);

		$result = $this->object->requestAction(
			array('controller' => 'request_action', 'action' => 'post_pass'),
			array('post' => $_POST)
		);
		$result = json_decode($result, true);
		$expected = $_POST;
		$this->assertEquals($expected, $result);
	}

/**
 * test that requestAction() can get query data from the query string and
 * query option.
 *
 * @return void
 */
	public function testRequestActionWithQueryString() {
		Router::reload();
		require CAKE . 'Config/routes.php';
		$query = ['page' => 1, 'sort' => 'title'];
		$result = $this->object->requestAction(
			['controller' => 'request_action', 'action' => 'query_pass'],
			['query' => $query]
		);
		$result = json_decode($result, true);
		$this->assertEquals($query, $result);

		$result = $this->object->requestAction([
			'controller' => 'request_action',
			'action' => 'query_pass',
			'?' => $query
		]);
		$result = json_decode($result, true);
		$this->assertEquals($query, $result);

		$result = $this->object->requestAction(
			'/request_action/query_pass?page=3&sort=body'
		);
		$result = json_decode($result, true);
		$expected = ['page' => 3, 'sort' => 'body'];
		$this->assertEquals($expected, $result);
	}

/**
 * Test requestAction with post data.
 *
 * @return void
 */
	public function testRequestActionPostWithData() {
		$data = array(
			'Post' => array('id' => 2)
		);
		$result = $this->object->requestAction(
			array('controller' => 'request_action', 'action' => 'post_pass'),
			array('post' => $data)
		);
		$result = json_decode($result, true);
		$this->assertEquals($data, $result);

		$result = $this->object->requestAction(
			'/request_action/post_pass',
			array('post' => $data)
		);
		$result = json_decode($result, true);
		$this->assertEquals($data, $result);
	}

/**
 * Test that requestAction handles get parameters correctly.
 *
 * @return void
 */
	public function testRequestActionGetParameters() {
		$result = $this->object->requestAction(
			'/request_action/params_pass?get=value&limit=5'
		);
		$result = json_decode($result, true);
		$this->assertEquals('value', $result['query']['get']);

		$result = $this->object->requestAction(
			array('controller' => 'request_action', 'action' => 'params_pass'),
			array('query' => array('get' => 'value', 'limit' => 5))
		);
		$result = json_decode($result, true);
		$this->assertEquals('value', $result['query']['get']);
	}

}
