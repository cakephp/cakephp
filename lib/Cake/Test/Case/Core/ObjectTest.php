<?php
/**
 * ObjectTest file
 *
 * PHP 5
 *
 * CakePHP(tm) Tests <http://book.cakephp.org/view/1196/Testing>
 * Copyright 2005-2011, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright 2005-2011, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/view/1196/Testing CakePHP(tm) Tests
 * @package       Cake.Test.Case.Core
 * @since         CakePHP(tm) v 1.2.0.5432
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

App::uses('Object', 'Core');
App::uses('Router', 'Routing');
App::uses('Controller', 'Controller');
App::uses('Model', 'Model');

/**
 * RequestActionPost class
 *
 * @package       Cake.Test.Case.Core
 */
class RequestActionPost extends CakeTestModel {

/**
 * name property
 *
 * @var string 'ControllerPost'
 */
	public $name = 'RequestActionPost';

/**
 * useTable property
 *
 * @var string 'posts'
 */
	public $useTable = 'posts';
}

/**
 * RequestActionController class
 *
 * @package       Cake.Test.Case.Core
 */
class RequestActionController extends Controller {

/**
* uses property
*
* @var array
* @access public
*/
	public $uses = array('RequestActionPost');

/**
* test_request_action method
*
* @access public
* @return void
*/
	public function test_request_action() {
		return 'This is a test';
	}

/**
* another_ra_test method
*
* @param mixed $id
* @param mixed $other
* @access public
* @return void
*/
	public function another_ra_test($id, $other) {
		return $id + $other;
	}

/**
 * normal_request_action method
 *
 * @return void
 */
	public function normal_request_action() {
		return 'Hello World';
	}

/**
 * returns $this->here
 *
 * @return void
 */
	public function return_here() {
		return $this->here;
	}

/**
 * paginate_request_action method
 *
 * @return void
 */
	public function paginate_request_action() {
		$data = $this->paginate();
		return true;
	}

/**
 * post pass, testing post passing
 *
 * @return array
 */
	public function post_pass() {
		return $this->data;
	}

/**
 * test param passing and parsing.
 *
 * @return array
 */
	public function params_pass() {
		return $this->request;
	}

	public function param_check() {
		$this->autoRender = false;
		$content = '';
		if (isset($this->request->params[0])) {
			$content = 'return found';
		}
		$this->response->body($content);
	}
}


/**
 * TestObject class
 *
 * @package       Cake.Test.Case.Core
 */
class TestObject extends Object {

/**
 * firstName property
 *
 * @var string 'Joel'
 */
	public $firstName = 'Joel';

/**
 * lastName property
 *
 * @var string 'Moss'
 */
	public $lastName = 'Moss';

/**
 * methodCalls property
 *
 * @var array
 */
	public $methodCalls = array();

/**
 * emptyMethod method
 *
 * @return void
 */
	public function emptyMethod() {
		$this->methodCalls[] = 'emptyMethod';
	}

/**
 * oneParamMethod method
 *
 * @param mixed $param
 * @return void
 */
	public function oneParamMethod($param) {
		$this->methodCalls[] = array('oneParamMethod' => array($param));
	}

/**
 * twoParamMethod method
 *
 * @param mixed $param
 * @param mixed $param2
 * @return void
 */
	public function twoParamMethod($param, $param2) {
		$this->methodCalls[] = array('twoParamMethod' => array($param, $param2));
	}

/**
 * threeParamMethod method
 *
 * @param mixed $param
 * @param mixed $param2
 * @param mixed $param3
 * @return void
 */
	public function threeParamMethod($param, $param2, $param3) {
		$this->methodCalls[] = array('threeParamMethod' => array($param, $param2, $param3));
	}
	/**
 * fourParamMethod method
 *
 * @param mixed $param
 * @param mixed $param2
 * @param mixed $param3
 * @param mixed $param4
 * @return void
 */
	public function fourParamMethod($param, $param2, $param3, $param4) {
		$this->methodCalls[] = array('fourParamMethod' => array($param, $param2, $param3, $param4));
	}
	/**
 * fiveParamMethod method
 *
 * @param mixed $param
 * @param mixed $param2
 * @param mixed $param3
 * @param mixed $param4
 * @param mixed $param5
 * @return void
 */
	public function fiveParamMethod($param, $param2, $param3, $param4, $param5) {
		$this->methodCalls[] = array('fiveParamMethod' => array($param, $param2, $param3, $param4, $param5));
	}

/**
 * crazyMethod method
 *
 * @param mixed $param
 * @param mixed $param2
 * @param mixed $param3
 * @param mixed $param4
 * @param mixed $param5
 * @param mixed $param6
 * @param mixed $param7
 * @return void
 */
	public function crazyMethod($param, $param2, $param3, $param4, $param5, $param6, $param7 = null) {
		$this->methodCalls[] = array('crazyMethod' => array($param, $param2, $param3, $param4, $param5, $param6, $param7));
	}

/**
 * methodWithOptionalParam method
 *
 * @param mixed $param
 * @return void
 */
	public function methodWithOptionalParam($param = null) {
		$this->methodCalls[] = array('methodWithOptionalParam' => array($param));
	}

/**
 * undocumented function
 *
 * @return void
 */
	public function set($properties = array()) {
		return parent::_set($properties);
	}
}

/**
 * ObjectTestModel class
 *
 * @package       Cake.Test.Case.Core
 */
class ObjectTestModel extends CakeTestModel {
	public $useTable = false;
	public $name = 'ObjectTestModel';
}

/**
 * Object Test class
 *
 * @package       Cake.Test.Case.Core
 */
class ObjectTest extends CakeTestCase {

/**
 * fixtures
 *
 * @var string
 */
	public $fixtures = array('core.post', 'core.test_plugin_comment', 'core.comment');

/**
 * setUp method
 *
 * @return void
 */
	public function setUp() {
		$this->object = new TestObject();
	}

/**
 * tearDown method
 *
 * @return void
 */
	public function tearDown() {
		App::build();
		CakePlugin::unload();
		unset($this->object);
	}

/**
 * testLog method
 *
 * @return void
 */
	public function testLog() {
		if (file_exists(LOGS . 'error.log')) {
			unlink(LOGS . 'error.log');
		}
		$this->assertTrue($this->object->log('Test warning 1'));
		$this->assertTrue($this->object->log(array('Test' => 'warning 2')));
		$result = file(LOGS . 'error.log');
		$this->assertPattern('/^2[0-9]{3}-[0-9]+-[0-9]+ [0-9]+:[0-9]+:[0-9]+ Error: Test warning 1$/', $result[0]);
		$this->assertPattern('/^2[0-9]{3}-[0-9]+-[0-9]+ [0-9]+:[0-9]+:[0-9]+ Error: Array$/', $result[1]);
		$this->assertPattern('/^\($/', $result[2]);
		$this->assertPattern('/\[Test\] => warning 2$/', $result[3]);
		$this->assertPattern('/^\)$/', $result[4]);
		unlink(LOGS . 'error.log');

		$this->assertTrue($this->object->log('Test warning 1', LOG_WARNING));
		$this->assertTrue($this->object->log(array('Test' => 'warning 2'), LOG_WARNING));
		$result = file(LOGS . 'error.log');
		$this->assertPattern('/^2[0-9]{3}-[0-9]+-[0-9]+ [0-9]+:[0-9]+:[0-9]+ Warning: Test warning 1$/', $result[0]);
		$this->assertPattern('/^2[0-9]{3}-[0-9]+-[0-9]+ [0-9]+:[0-9]+:[0-9]+ Warning: Array$/', $result[1]);
		$this->assertPattern('/^\($/', $result[2]);
		$this->assertPattern('/\[Test\] => warning 2$/', $result[3]);
		$this->assertPattern('/^\)$/', $result[4]);
		unlink(LOGS . 'error.log');
	}

/**
 * testSet method
 *
 * @return void
 */
	public function testSet() {
		$this->object->set('a string');
		$this->assertEqual($this->object->firstName, 'Joel');

		$this->object->set(array('firstName'));
		$this->assertEqual($this->object->firstName, 'Joel');

		$this->object->set(array('firstName' => 'Ashley'));
		$this->assertEqual($this->object->firstName, 'Ashley');

		$this->object->set(array('firstName' => 'Joel', 'lastName' => 'Moose'));
		$this->assertEqual($this->object->firstName, 'Joel');
		$this->assertEqual($this->object->lastName, 'Moose');
	}

/**
 * testToString method
 *
 * @return void
 */
	public function testToString() {
		$result = strtolower($this->object->toString());
		$this->assertEqual($result, 'testobject');
	}

/**
 * testMethodDispatching method
 *
 * @return void
 */
	public function testMethodDispatching() {
		$this->object->emptyMethod();
		$expected = array('emptyMethod');
		$this->assertIdentical($this->object->methodCalls, $expected);

		$this->object->oneParamMethod('Hello');
		$expected[] = array('oneParamMethod' => array('Hello'));
		$this->assertIdentical($this->object->methodCalls, $expected);

		$this->object->twoParamMethod(true, false);
		$expected[] = array('twoParamMethod' => array(true, false));
		$this->assertIdentical($this->object->methodCalls, $expected);

		$this->object->threeParamMethod(true, false, null);
		$expected[] = array('threeParamMethod' => array(true, false, null));
		$this->assertIdentical($this->object->methodCalls, $expected);

		$this->object->crazyMethod(1, 2, 3, 4, 5, 6, 7);
		$expected[] = array('crazyMethod' => array(1, 2, 3, 4, 5, 6, 7));
		$this->assertIdentical($this->object->methodCalls, $expected);

		$this->object = new TestObject();
		$this->assertIdentical($this->object->methodCalls, array());

		$this->object->dispatchMethod('emptyMethod');
		$expected = array('emptyMethod');
		$this->assertIdentical($this->object->methodCalls, $expected);

		$this->object->dispatchMethod('oneParamMethod', array('Hello'));
		$expected[] = array('oneParamMethod' => array('Hello'));
		$this->assertIdentical($this->object->methodCalls, $expected);

		$this->object->dispatchMethod('twoParamMethod', array(true, false));
		$expected[] = array('twoParamMethod' => array(true, false));
		$this->assertIdentical($this->object->methodCalls, $expected);

		$this->object->dispatchMethod('threeParamMethod', array(true, false, null));
		$expected[] = array('threeParamMethod' => array(true, false, null));
		$this->assertIdentical($this->object->methodCalls, $expected);

		$this->object->dispatchMethod('fourParamMethod', array(1, 2, 3, 4));
		$expected[] = array('fourParamMethod' => array(1, 2, 3, 4));
		$this->assertIdentical($this->object->methodCalls, $expected);

		$this->object->dispatchMethod('fiveParamMethod', array(1, 2, 3, 4, 5));
		$expected[] = array('fiveParamMethod' => array(1, 2, 3, 4, 5));
		$this->assertIdentical($this->object->methodCalls, $expected);

		$this->object->dispatchMethod('crazyMethod', array(1, 2, 3, 4, 5, 6, 7));
		$expected[] = array('crazyMethod' => array(1, 2, 3, 4, 5, 6, 7));
		$this->assertIdentical($this->object->methodCalls, $expected);

		$this->object->dispatchMethod('methodWithOptionalParam', array('Hello'));
		$expected[] = array('methodWithOptionalParam' => array("Hello"));
		$this->assertIdentical($this->object->methodCalls, $expected);

		$this->object->dispatchMethod('methodWithOptionalParam');
		$expected[] = array('methodWithOptionalParam' => array(null));
		$this->assertIdentical($this->object->methodCalls, $expected);
	}

/**
 * testRequestAction method
 *
 * @return void
 */
	public function testRequestAction() {
		App::build(array(
			'models' => array(CAKE . 'Test' . DS . 'test_app' . DS . 'Model' . DS),
			'views' => array(CAKE . 'Test' . DS . 'test_app' . DS . 'View' . DS),
			'controllers' => array(CAKE . 'Test' . DS . 'test_app' . DS . 'Controller' . DS)
		), true);
		$this->assertNull(Router::getRequest(), 'request stack should be empty.');

		$result = $this->object->requestAction('');
		$this->assertFalse($result);

		$result = $this->object->requestAction('/request_action/test_request_action');
		$expected = 'This is a test';
		$this->assertEqual($expected, $result);

		$result = $this->object->requestAction('/request_action/another_ra_test/2/5');
		$expected = 7;
		$this->assertEqual($expected, $result);

		$result = $this->object->requestAction('/tests_apps/index', array('return'));
		$expected = 'This is the TestsAppsController index view ';
		$this->assertEqual($expected, $result);

		$result = $this->object->requestAction('/tests_apps/some_method');
		$expected = 5;
		$this->assertEqual($expected, $result);

		$result = $this->object->requestAction('/request_action/paginate_request_action');
		$this->assertTrue($result);

		$result = $this->object->requestAction('/request_action/normal_request_action');
		$expected = 'Hello World';
		$this->assertEqual($expected, $result);

		$this->assertNull(Router::getRequest(), 'requests were not popped off the stack, this will break url generation');
	}

/**
 * test requestAction() and plugins.
 *
 * @return void
 */
	public function testRequestActionPlugins() {
		App::build(array(
			'plugins' => array(CAKE . 'Test' . DS . 'test_app' . DS . 'Plugin' . DS),
		), true);
		CakePlugin::loadAll();
		Router::reload();

		$result = $this->object->requestAction('/test_plugin/tests/index', array('return'));
		$expected = 'test plugin index';
		$this->assertEqual($expected, $result);

		$result = $this->object->requestAction('/test_plugin/tests/index/some_param', array('return'));
		$expected = 'test plugin index';
		$this->assertEqual($expected, $result);

		$result = $this->object->requestAction(
			array('controller' => 'tests', 'action' => 'index', 'plugin' => 'test_plugin'), array('return')
		);
		$expected = 'test plugin index';
		$this->assertEqual($expected, $result);

		$result = $this->object->requestAction('/test_plugin/tests/some_method');
		$expected = 25;
		$this->assertEqual($expected, $result);

		$result = $this->object->requestAction(
			array('controller' => 'tests', 'action' => 'some_method', 'plugin' => 'test_plugin')
		);
		$expected = 25;
		$this->assertEqual($expected, $result);
	}

/**
 * test requestAction() with arrays.
 *
 * @return void
 */
	public function testRequestActionArray() {
		App::build(array(
			'models' => array(CAKE . 'Test' . DS . 'test_app' . DS . 'Model' . DS),
			'views' => array(CAKE . 'Test' . DS . 'test_app' . DS . 'View' . DS),
			'controllers' => array(CAKE . 'Test' . DS . 'test_app' . DS . 'Controller' . DS),
			'plugins' => array(CAKE . 'Test' . DS . 'test_app' . DS . 'Plugin'. DS)
		), true);
		CakePlugin::loadAll();

		$result = $this->object->requestAction(
			array('controller' => 'request_action', 'action' => 'test_request_action')
		);
		$expected = 'This is a test';
		$this->assertEqual($expected, $result);

		$result = $this->object->requestAction(
			array('controller' => 'request_action', 'action' => 'another_ra_test'),
			array('pass' => array('5', '7'))
		);
		$expected = 12;
		$this->assertEqual($expected, $result);

		$result = $this->object->requestAction(
			array('controller' => 'tests_apps', 'action' => 'index'), array('return')
		);
		$expected = 'This is the TestsAppsController index view ';
		$this->assertEqual($expected, $result);

		$result = $this->object->requestAction(array('controller' => 'tests_apps', 'action' => 'some_method'));
		$expected = 5;
		$this->assertEqual($expected, $result);

		$result = $this->object->requestAction(
			array('controller' => 'request_action', 'action' => 'normal_request_action')
		);
		$expected = 'Hello World';
		$this->assertEqual($expected, $result);

		$result = $this->object->requestAction(
			array('controller' => 'request_action', 'action' => 'paginate_request_action')
		);
		$this->assertTrue($result);

		$result = $this->object->requestAction(
			array('controller' => 'request_action', 'action' => 'paginate_request_action'),
			array('pass' => array(5), 'named' => array('param' => 'value'))
		);
		$this->assertTrue($result);
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
		$this->assertEqual($result->url, 'request_action/params_pass');
		$this->assertEqual($result['controller'], 'request_action');
		$this->assertEqual($result['action'], 'params_pass');
		$this->assertEqual($result['plugin'], null);

		$result = $this->object->requestAction('/request_action/params_pass/sort:desc/limit:5');
		$expected = array('sort' => 'desc', 'limit' => 5,);
		$this->assertEqual($result['named'], $expected);

		$result = $this->object->requestAction(
			array('controller' => 'request_action', 'action' => 'params_pass'),
			array('named' => array('sort' => 'desc', 'limit' => 5))
		);
		$this->assertEqual($result['named'], $expected);
	}

/**
 * test requestAction and POST parameter passing, and not passing when url is an array.
 *
 * @return void
 */
	public function testRequestActionPostPassing() {
		$_tmp = $_POST;

		$_POST = array('data' => array(
			'item' => 'value'
		));
		$result = $this->object->requestAction(array('controller' => 'request_action', 'action' => 'post_pass'));
		$expected = null;
		$this->assertEmpty($result);

		$result = $this->object->requestAction(
			array('controller' => 'request_action', 'action' => 'post_pass'),
			array('data' => $_POST['data'])
		);
		$expected = $_POST['data'];
		$this->assertEqual($expected, $result);

		$result = $this->object->requestAction('/request_action/post_pass');
		$expected = $_POST['data'];
		$this->assertEqual($expected, $result);

		$_POST = $_tmp;
	}
}
