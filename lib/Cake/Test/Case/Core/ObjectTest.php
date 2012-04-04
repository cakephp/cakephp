<?php
/**
 * ObjectTest file
 *
 * PHP 5
 *
 * CakePHP(tm) Tests <http://book.cakephp.org/view/1196/Testing>
 * Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
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
		return $this->request->data;
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
 * @param mixed $paramTwo
 * @return void
 */
	public function twoParamMethod($param, $paramTwo) {
		$this->methodCalls[] = array('twoParamMethod' => array($param, $paramTwo));
	}

/**
 * threeParamMethod method
 *
 * @param mixed $param
 * @param mixed $paramTwo
 * @param mixed $paramThree
 * @return void
 */
	public function threeParamMethod($param, $paramTwo, $paramThree) {
		$this->methodCalls[] = array('threeParamMethod' => array($param, $paramTwo, $paramThree));
	}

/**
 * fourParamMethod method
 *
 * @param mixed $param
 * @param mixed $paramTwo
 * @param mixed $paramThree
 * @param mixed $paramFour
 * @return void
 */
	public function fourParamMethod($param, $paramTwo, $paramThree, $paramFour) {
		$this->methodCalls[] = array('fourParamMethod' => array($param, $paramTwo, $paramThree, $paramFour));
	}

/**
 * fiveParamMethod method
 *
 * @param mixed $param
 * @param mixed $paramTwo
 * @param mixed $paramThree
 * @param mixed $paramFour
 * @param mixed $paramFive
 * @return void
 */
	public function fiveParamMethod($param, $paramTwo, $paramThree, $paramFour, $paramFive) {
		$this->methodCalls[] = array('fiveParamMethod' => array($param, $paramTwo, $paramThree, $paramFour, $paramFive));
	}

/**
 * crazyMethod method
 *
 * @param mixed $param
 * @param mixed $paramTwo
 * @param mixed $paramThree
 * @param mixed $paramFour
 * @param mixed $paramFive
 * @param mixed $paramSix
 * @param mixed $paramSeven
 * @return void
 */
	public function crazyMethod($param, $paramTwo, $paramThree, $paramFour, $paramFive, $paramSix, $paramSeven = null) {
		$this->methodCalls[] = array('crazyMethod' => array($param, $paramTwo, $paramThree, $paramFour, $paramFive, $paramSix, $paramSeven));
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
		$this->assertRegExp('/^2[0-9]{3}-[0-9]+-[0-9]+ [0-9]+:[0-9]+:[0-9]+ Error: Test warning 1$/', $result[0]);
		$this->assertRegExp('/^2[0-9]{3}-[0-9]+-[0-9]+ [0-9]+:[0-9]+:[0-9]+ Error: Array$/', $result[1]);
		$this->assertRegExp('/^\($/', $result[2]);
		$this->assertRegExp('/\[Test\] => warning 2$/', $result[3]);
		$this->assertRegExp('/^\)$/', $result[4]);
		unlink(LOGS . 'error.log');

		$this->assertTrue($this->object->log('Test warning 1', LOG_WARNING));
		$this->assertTrue($this->object->log(array('Test' => 'warning 2'), LOG_WARNING));
		$result = file(LOGS . 'error.log');
		$this->assertRegExp('/^2[0-9]{3}-[0-9]+-[0-9]+ [0-9]+:[0-9]+:[0-9]+ Warning: Test warning 1$/', $result[0]);
		$this->assertRegExp('/^2[0-9]{3}-[0-9]+-[0-9]+ [0-9]+:[0-9]+:[0-9]+ Warning: Array$/', $result[1]);
		$this->assertRegExp('/^\($/', $result[2]);
		$this->assertRegExp('/\[Test\] => warning 2$/', $result[3]);
		$this->assertRegExp('/^\)$/', $result[4]);
		unlink(LOGS . 'error.log');
	}

/**
 * testSet method
 *
 * @return void
 */
	public function testSet() {
		$this->object->set('a string');
		$this->assertEquals('Joel', $this->object->firstName);

		$this->object->set(array('firstName'));
		$this->assertEquals('Joel', $this->object->firstName);

		$this->object->set(array('firstName' => 'Ashley'));
		$this->assertEquals('Ashley', $this->object->firstName);

		$this->object->set(array('firstName' => 'Joel', 'lastName' => 'Moose'));
		$this->assertEquals('Joel', $this->object->firstName);
		$this->assertEquals('Moose', $this->object->lastName);
	}

/**
 * testToString method
 *
 * @return void
 */
	public function testToString() {
		$result = strtolower($this->object->toString());
		$this->assertEquals('testobject', $result);
	}

/**
 * testMethodDispatching method
 *
 * @return void
 */
	public function testMethodDispatching() {
		$this->object->emptyMethod();
		$expected = array('emptyMethod');
		$this->assertSame($this->object->methodCalls, $expected);

		$this->object->oneParamMethod('Hello');
		$expected[] = array('oneParamMethod' => array('Hello'));
		$this->assertSame($this->object->methodCalls, $expected);

		$this->object->twoParamMethod(true, false);
		$expected[] = array('twoParamMethod' => array(true, false));
		$this->assertSame($this->object->methodCalls, $expected);

		$this->object->threeParamMethod(true, false, null);
		$expected[] = array('threeParamMethod' => array(true, false, null));
		$this->assertSame($this->object->methodCalls, $expected);

		$this->object->crazyMethod(1, 2, 3, 4, 5, 6, 7);
		$expected[] = array('crazyMethod' => array(1, 2, 3, 4, 5, 6, 7));
		$this->assertSame($this->object->methodCalls, $expected);

		$this->object = new TestObject();
		$this->assertSame($this->object->methodCalls, array());

		$this->object->dispatchMethod('emptyMethod');
		$expected = array('emptyMethod');
		$this->assertSame($this->object->methodCalls, $expected);

		$this->object->dispatchMethod('oneParamMethod', array('Hello'));
		$expected[] = array('oneParamMethod' => array('Hello'));
		$this->assertSame($this->object->methodCalls, $expected);

		$this->object->dispatchMethod('twoParamMethod', array(true, false));
		$expected[] = array('twoParamMethod' => array(true, false));
		$this->assertSame($this->object->methodCalls, $expected);

		$this->object->dispatchMethod('threeParamMethod', array(true, false, null));
		$expected[] = array('threeParamMethod' => array(true, false, null));
		$this->assertSame($this->object->methodCalls, $expected);

		$this->object->dispatchMethod('fourParamMethod', array(1, 2, 3, 4));
		$expected[] = array('fourParamMethod' => array(1, 2, 3, 4));
		$this->assertSame($this->object->methodCalls, $expected);

		$this->object->dispatchMethod('fiveParamMethod', array(1, 2, 3, 4, 5));
		$expected[] = array('fiveParamMethod' => array(1, 2, 3, 4, 5));
		$this->assertSame($this->object->methodCalls, $expected);

		$this->object->dispatchMethod('crazyMethod', array(1, 2, 3, 4, 5, 6, 7));
		$expected[] = array('crazyMethod' => array(1, 2, 3, 4, 5, 6, 7));
		$this->assertSame($this->object->methodCalls, $expected);

		$this->object->dispatchMethod('methodWithOptionalParam', array('Hello'));
		$expected[] = array('methodWithOptionalParam' => array("Hello"));
		$this->assertSame($this->object->methodCalls, $expected);

		$this->object->dispatchMethod('methodWithOptionalParam');
		$expected[] = array('methodWithOptionalParam' => array(null));
		$this->assertSame($this->object->methodCalls, $expected);
	}

/**
 * testRequestAction method
 *
 * @return void
 */
	public function testRequestAction() {
		App::build(array(
			'Model' => array(CAKE . 'Test' . DS . 'test_app' . DS . 'Model' . DS),
			'View' => array(CAKE . 'Test' . DS . 'test_app' . DS . 'View' . DS),
			'Controller' => array(CAKE . 'Test' . DS . 'test_app' . DS . 'Controller' . DS)
		), App::RESET);
		$this->assertNull(Router::getRequest(), 'request stack should be empty.');

		$result = $this->object->requestAction('');
		$this->assertFalse($result);

		$result = $this->object->requestAction('/request_action/test_request_action');
		$expected = 'This is a test';
		$this->assertEquals($expected, $result);

		$result = $this->object->requestAction(FULL_BASE_URL . '/request_action/test_request_action');
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
		$this->assertTrue($result);

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
		App::build(array(
			'Plugin' => array(CAKE . 'Test' . DS . 'test_app' . DS . 'Plugin' . DS),
		), App::RESET);
		CakePlugin::load('TestPlugin');
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
		App::build(array(
			'Model' => array(CAKE . 'Test' . DS . 'test_app' . DS . 'Model' . DS),
			'View' => array(CAKE . 'Test' . DS . 'test_app' . DS . 'View' . DS),
			'Controller' => array(CAKE . 'Test' . DS . 'test_app' . DS . 'Controller' . DS),
			'Plugin' => array(CAKE . 'Test' . DS . 'test_app' . DS . 'Plugin' . DS)
		), App::RESET);
		CakePlugin::load(array('TestPlugin'));

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
		$this->assertEquals('request_action/params_pass', $result->url);
		$this->assertEquals('request_action', $result['controller']);
		$this->assertEquals('params_pass', $result['action']);
		$this->assertEquals(null, $result['plugin']);

		$result = $this->object->requestAction('/request_action/params_pass/sort:desc/limit:5');
		$expected = array('sort' => 'desc', 'limit' => 5,);
		$this->assertEquals($expected, $result['named']);

		$result = $this->object->requestAction(
			array('controller' => 'request_action', 'action' => 'params_pass'),
			array('named' => array('sort' => 'desc', 'limit' => 5))
		);
		$this->assertEquals($expected, $result['named']);
	}

/**
 * test that requestAction does not fish data out of the POST
 * superglobal.
 *
 * @return void
 */
	public function testRequestActionNoPostPassing() {
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
		$this->assertEquals($expected, $result);

		$result = $this->object->requestAction('/request_action/post_pass');
		$expected = $_POST['data'];
		$this->assertEquals($expected, $result);

		$_POST = $_tmp;
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
			array('data' => $data)
		);
		$this->assertEquals($data, $result);

		$result = $this->object->requestAction(
			'/request_action/post_pass',
			array('data' => $data)
		);
		$this->assertEquals($data, $result);
	}
}
