<?php
/**
 * ObjectTest file
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) Tests <http://book.cakephp.org/view/1196/Testing>
 * Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 *  Licensed under The Open Group Test Suite License
 *  Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/view/1196/Testing CakePHP(tm) Tests
 * @package       cake
 * @subpackage    cake.tests.cases.libs
 * @since         CakePHP(tm) v 1.2.0.5432
 * @license       http://www.opensource.org/licenses/opengroup.php The Open Group Test Suite License
 */
App::import('Core', array('Object', 'Controller', 'Model'));

/**
 * RequestActionPost class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs.object
 */
class RequestActionPost extends CakeTestModel {

/**
 * name property
 *
 * @var string 'ControllerPost'
 * @access public
 */
	var $name = 'RequestActionPost';

/**
 * useTable property
 *
 * @var string 'posts'
 * @access public
 */
	var $useTable = 'posts';
}

/**
 * RequestActionController class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs
 */
class RequestActionController extends Controller {

/**
* uses property
*
* @var array
* @access public
*/
	var $uses = array('RequestActionPost');

/**
* test_request_action method
*
* @access public
* @return void
*/
	function test_request_action() {
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
	function another_ra_test($id, $other) {
		return $id + $other;
	}

/**
 * normal_request_action method
 *
 * @access public
 * @return void
 */
	function normal_request_action() {
		return 'Hello World';
	}

/**
 * returns $this->here
 *
 * @return void
 */
	function return_here() {
		return $this->here;
	}

/**
 * paginate_request_action method
 *
 * @access public
 * @return void
 */
	function paginate_request_action() {
		$data = $this->paginate();
		return true;
	}

/**
 * post pass, testing post passing
 *
 * @return array
 */
	function post_pass() {
		return $this->data;
	}

/**
 * test param passing and parsing.
 *
 * @return array
 */
	function params_pass() {
		return $this->params;
	}
}

/**
 * RequestActionPersistentController class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs
 */
class RequestActionPersistentController extends Controller {

/**
* uses property
*
* @var array
* @access public
*/
	var $uses = array('PersisterOne');

/**
* persistModel property
*
* @var array
* @access public
*/
	var $persistModel = true;

/**
 * post pass, testing post passing
 *
 * @return array
 */
	function index() {
		return 'This is a test';
	}
}

/**
 * TestObject class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs
 */
class TestObject extends Object {

/**
 * firstName property
 *
 * @var string 'Joel'
 * @access public
 */
	var $firstName = 'Joel';

/**
 * lastName property
 *
 * @var string 'Moss'
 * @access public
 */
	var $lastName = 'Moss';

/**
 * methodCalls property
 *
 * @var array
 * @access public
 */
	var $methodCalls = array();

/**
 * emptyMethod method
 *
 * @access public
 * @return void
 */
	function emptyMethod() {
		$this->methodCalls[] = 'emptyMethod';
	}

/**
 * oneParamMethod method
 *
 * @param mixed $param
 * @access public
 * @return void
 */
	function oneParamMethod($param) {
		$this->methodCalls[] = array('oneParamMethod' => array($param));
	}

/**
 * twoParamMethod method
 *
 * @param mixed $param
 * @param mixed $param2
 * @access public
 * @return void
 */
	function twoParamMethod($param, $param2) {
		$this->methodCalls[] = array('twoParamMethod' => array($param, $param2));
	}

/**
 * threeParamMethod method
 *
 * @param mixed $param
 * @param mixed $param2
 * @param mixed $param3
 * @access public
 * @return void
 */
	function threeParamMethod($param, $param2, $param3) {
		$this->methodCalls[] = array('threeParamMethod' => array($param, $param2, $param3));
	}
	/**
 * fourParamMethod method
 *
 * @param mixed $param
 * @param mixed $param2
 * @param mixed $param3
 * @param mixed $param4
 * @access public
 * @return void
 */
	function fourParamMethod($param, $param2, $param3, $param4) {
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
 * @access public
 * @return void
 */
	function fiveParamMethod($param, $param2, $param3, $param4, $param5) {
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
 * @access public
 * @return void
 */
	function crazyMethod($param, $param2, $param3, $param4, $param5, $param6, $param7 = null) {
		$this->methodCalls[] = array('crazyMethod' => array($param, $param2, $param3, $param4, $param5, $param6, $param7));
	}

/**
 * methodWithOptionalParam method
 *
 * @param mixed $param
 * @access public
 * @return void
 */
	function methodWithOptionalParam($param = null) {
		$this->methodCalls[] = array('methodWithOptionalParam' => array($param));
	}

/**
 * testPersist
 *
 * @return void
 */
	function testPersist($name, $return = null, &$object, $type = null) {
		return $this->_persist($name, $return, $object, $type);
	}
}

/**
 * ObjectTestModel class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs
 */
class ObjectTestModel extends CakeTestModel {
	var $useTable = false;
	var $name = 'ObjectTestModel';
}

/**
 * Object Test class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs
 */
class ObjectTest extends CakeTestCase {

/**
 * fixtures
 *
 * @var string
 */
	var $fixtures = array('core.post', 'core.test_plugin_comment', 'core.comment');

/**
 * setUp method
 *
 * @access public
 * @return void
 */
	function setUp() {
		$this->object = new TestObject();
	}

/**
 * tearDown method
 *
 * @access public
 * @return void
 */
	function tearDown() {
		unset($this->object);
	}

/**
 * endTest
 *
 * @access public
 * @return void
 */
	function endTest() {
		App::build();
	}

/**
 * testLog method
 *
 * @access public
 * @return void
 */
	function testLog() {
		@unlink(LOGS . 'error.log');
		$this->assertTrue($this->object->log('Test warning 1'));
		$this->assertTrue($this->object->log(array('Test' => 'warning 2')));
		$result = file(LOGS . 'error.log');
		$this->assertPattern('/^2[0-9]{3}-[0-9]+-[0-9]+ [0-9]+:[0-9]+:[0-9]+ Error: Test warning 1$/', $result[0]);
		$this->assertPattern('/^2[0-9]{3}-[0-9]+-[0-9]+ [0-9]+:[0-9]+:[0-9]+ Error: Array$/', $result[1]);
		$this->assertPattern('/^\($/', $result[2]);
		$this->assertPattern('/\[Test\] => warning 2$/', $result[3]);
		$this->assertPattern('/^\)$/', $result[4]);
		unlink(LOGS . 'error.log');

		@unlink(LOGS . 'error.log');
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
 * @access public
 * @return void
 */
	function testSet() {
		$this->object->_set('a string');
		$this->assertEqual($this->object->firstName, 'Joel');

		$this->object->_set(array('firstName'));
		$this->assertEqual($this->object->firstName, 'Joel');

		$this->object->_set(array('firstName' => 'Ashley'));
		$this->assertEqual($this->object->firstName, 'Ashley');

		$this->object->_set(array('firstName' => 'Joel', 'lastName' => 'Moose'));
		$this->assertEqual($this->object->firstName, 'Joel');
		$this->assertEqual($this->object->lastName, 'Moose');
	}

/**
 * testPersist method
 *
 * @access public
 * @return void
 */
	function testPersist() {
		ClassRegistry::flush();

		$cacheDisable = Configure::read('Cache.disable');
		Configure::write('Cache.disable', false);
		@unlink(CACHE . 'persistent' . DS . 'testmodel.php');
		$test = new stdClass;
		$this->assertFalse($this->object->testPersist('TestModel', null, $test));
		$this->assertFalse($this->object->testPersist('TestModel', true, $test));
		$this->assertTrue($this->object->testPersist('TestModel', null, $test));
		$this->assertTrue(file_exists(CACHE . 'persistent' . DS . 'testmodel.php'));
		$this->assertTrue($this->object->testPersist('TestModel', true, $test));
		$this->assertEqual($this->object->TestModel, $test);

		@unlink(CACHE . 'persistent' . DS . 'testmodel.php');

		$model =& new ObjectTestModel();
		$expected = ClassRegistry::keys();

		ClassRegistry::flush();
		$data = array('object_test_model' => $model);
		$this->assertFalse($this->object->testPersist('ObjectTestModel', true, $data));
		$this->assertTrue(file_exists(CACHE . 'persistent' . DS . 'objecttestmodel.php'));

		$this->object->testPersist('ObjectTestModel', true, $model, 'registry');

		$result = ClassRegistry::keys();
		$this->assertEqual($result, $expected);

		$newModel = ClassRegistry::getObject('object_test_model');
		$this->assertEqual('ObjectTestModel', $newModel->name);

		@unlink(CACHE . 'persistent' . DS . 'objecttestmodel.php');

		Configure::write('Cache.disable', $cacheDisable);
	}

/**
 * testPersistWithRequestAction method
 *
 * @access public
 * @return void
 */
	function testPersistWithBehavior() {
		ClassRegistry::flush();

		$cacheDisable = Configure::read('Cache.disable');
		Configure::write('Cache.disable', false);

		App::build(array(
			'models' => array(TEST_CAKE_CORE_INCLUDE_PATH . 'tests' . DS . 'test_app' . DS . 'models' . DS),
			'plugins' => array(TEST_CAKE_CORE_INCLUDE_PATH . 'tests' . DS . 'test_app' . DS . 'plugins'. DS),
			'behaviors' => array(TEST_CAKE_CORE_INCLUDE_PATH . 'tests' . DS . 'test_app' . DS . 'models'. DS . 'behaviors' . DS),
		), true);

		$this->assertFalse(class_exists('PersisterOneBehaviorBehavior'));
		$this->assertFalse(class_exists('PersisterTwoBehaviorBehavior'));
		$this->assertFalse(class_exists('TestPluginPersisterBehavior'));
		$this->assertFalse(class_exists('TestPluginAuthors'));

		$Controller = new RequestActionPersistentController();
		$Controller->persistModel = true;
		$Controller->constructClasses();

		$this->assertTrue(file_exists(CACHE . 'persistent' . DS . 'persisterone.php'));
		$this->assertTrue(file_exists(CACHE . 'persistent' . DS . 'persisteroneregistry.php'));

		$contents = file_get_contents(CACHE . 'persistent' . DS . 'persisteroneregistry.php');
		$contents = str_replace('"PersisterOne"', '"PersisterTwo"', $contents);
		$contents = str_replace('persister_one', 'persister_two', $contents);
		$contents = str_replace('test_plugin_comment', 'test_plugin_authors', $contents);
		$result = file_put_contents(CACHE . 'persistent' . DS . 'persisteroneregistry.php', $contents);

		$this->assertTrue(class_exists('PersisterOneBehaviorBehavior'));
		$this->assertTrue(class_exists('TestPluginPersisterOneBehavior'));
		$this->assertTrue(class_exists('TestPluginComment'));
		$this->assertFalse(class_exists('PersisterTwoBehaviorBehavior'));
		$this->assertFalse(class_exists('TestPluginPersisterTwoBehavior'));
		$this->assertFalse(class_exists('TestPluginAuthors'));

		$Controller = new RequestActionPersistentController();
		$Controller->persistModel = true;
		$Controller->constructClasses();

		$this->assertTrue(class_exists('PersisterOneBehaviorBehavior'));
		$this->assertTrue(class_exists('PersisterTwoBehaviorBehavior'));
		$this->assertTrue(class_exists('TestPluginPersisterTwoBehavior'));
		$this->assertTrue(class_exists('TestPluginAuthors'));

		@unlink(CACHE . 'persistent' . DS . 'persisterone.php');
		@unlink(CACHE . 'persistent' . DS . 'persisteroneregistry.php');
	}

/**
 * testPersistWithBehaviorAndRequestAction method
 *
 * @see testPersistWithBehavior
 * @access public
 * @return void
 */
	function testPersistWithBehaviorAndRequestAction() {
		ClassRegistry::flush();

		$cacheDisable = Configure::read('Cache.disable');
		Configure::write('Cache.disable', false);

		$this->assertFalse(class_exists('ContainableBehavior'));

		App::build(array(
			'models' => array(TEST_CAKE_CORE_INCLUDE_PATH . 'tests' . DS . 'test_app' . DS . 'models' . DS),
			'behaviors' => array(TEST_CAKE_CORE_INCLUDE_PATH . 'tests' . DS . 'test_app' . DS . 'models'. DS . 'behaviors' . DS),
		), true);

		$this->assertFalse(class_exists('PersistOneBehaviorBehavior'));
		$this->assertFalse(class_exists('PersistTwoBehaviorBehavior'));

		$Controller = new RequestActionPersistentController();
		$Controller->persistModel = true;
		$Controller->constructClasses();

		$this->assertTrue(file_exists(CACHE . 'persistent' . DS . 'persisterone.php'));
		$this->assertTrue(file_exists(CACHE . 'persistent' . DS . 'persisteroneregistry.php'));

		$keys = ClassRegistry::keys();
		$this->assertEqual($keys, array(
			'persister_one',
			'comment',
			'test_plugin_comment',
			'test_plugin.test_plugin_comment',
			'persister_one_behavior_behavior',
			'test_plugin_persister_one_behavior',
			'test_plugin.test_plugin_persister_one_behavior'
		));

		ob_start();
		$Controller->set('content_for_layout', 'cool');
		$Controller->render('index', 'ajax', '/layouts/ajax');
		$result = ob_get_clean();

		$keys = ClassRegistry::keys();
		$this->assertEqual($keys, array(
			'persister_one',
			'comment',
			'test_plugin_comment',
			'test_plugin.test_plugin_comment',
			'persister_one_behavior_behavior',
			'test_plugin_persister_one_behavior',
			'test_plugin.test_plugin_persister_one_behavior',
			'view'
		));
		$result = $this->object->requestAction('/request_action_persistent/index');
		$expected = 'This is a test';
		$this->assertEqual($result, $expected);

		@unlink(CACHE . 'persistent' . DS . 'persisterone.php');
		@unlink(CACHE . 'persistent' . DS . 'persisteroneregistry.php');

		$Controller = new RequestActionPersistentController();
		$Controller->persistModel = true;
		$Controller->constructClasses();

		@unlink(CACHE . 'persistent' . DS . 'persisterone.php');
		@unlink(CACHE . 'persistent' . DS . 'persisteroneregistry.php');

		Configure::write('Cache.disable', $cacheDisable);
	}

/**
 * testToString method
 *
 * @access public
 * @return void
 */
	function testToString() {
		$result = strtolower($this->object->toString());
		$this->assertEqual($result, 'testobject');
	}

/**
 * testMethodDispatching method
 *
 * @access public
 * @return void
 */
	function testMethodDispatching() {
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
 * @access public
 * @return void
 */
	function testRequestAction() {
		App::build(array(
			'models' => array(TEST_CAKE_CORE_INCLUDE_PATH . 'tests' . DS . 'test_app' . DS . 'models' . DS),
			'views' => array(TEST_CAKE_CORE_INCLUDE_PATH . 'tests' . DS . 'test_app' . DS . 'views' . DS),
			'controllers' => array(TEST_CAKE_CORE_INCLUDE_PATH . 'tests' . DS . 'test_app' . DS . 'controllers' . DS)
		));
		$result = $this->object->requestAction('');
		$this->assertFalse($result);

		$result = $this->object->requestAction('/request_action/test_request_action');
		$expected = 'This is a test';
		$this->assertEqual($result, $expected);;

		$result = $this->object->requestAction('/request_action/another_ra_test/2/5');
		$expected = 7;
		$this->assertEqual($result, $expected);

		$result = $this->object->requestAction('/tests_apps/index', array('return'));
		$expected = 'This is the TestsAppsController index view';
		$this->assertEqual($result, $expected);

		$result = $this->object->requestAction('/tests_apps/some_method');
		$expected = 5;
		$this->assertEqual($result, $expected);

		$result = $this->object->requestAction('/request_action/paginate_request_action');
		$this->assertTrue($result);

		$result = $this->object->requestAction('/request_action/normal_request_action');
		$expected = 'Hello World';
		$this->assertEqual($result, $expected);
		
		App::build();
	}

/**
 * test requestAction() and plugins.
 *
 * @return void
 */
	function testRequestActionPlugins() {
		App::build(array(
			'plugins' => array(TEST_CAKE_CORE_INCLUDE_PATH . 'tests' . DS . 'test_app' . DS . 'plugins' . DS),
		));
		App::objects('plugin', null, false);
		Router::reload();
		
		$result = $this->object->requestAction('/test_plugin/tests/index', array('return'));
		$expected = 'test plugin index';
		$this->assertEqual($result, $expected);

		$result = $this->object->requestAction('/test_plugin/tests/index/some_param', array('return'));
		$expected = 'test plugin index';
		$this->assertEqual($result, $expected);

		$result = $this->object->requestAction(
			array('controller' => 'tests', 'action' => 'index', 'plugin' => 'test_plugin'), array('return')
		);
		$expected = 'test plugin index';
		$this->assertEqual($result, $expected);

		$result = $this->object->requestAction('/test_plugin/tests/some_method');
		$expected = 25;
		$this->assertEqual($result, $expected);

		$result = $this->object->requestAction(
			array('controller' => 'tests', 'action' => 'some_method', 'plugin' => 'test_plugin')
		);
		$expected = 25;
		$this->assertEqual($result, $expected);
		
		App::build();
		App::objects('plugin', null, false);
	}

/**
 * test requestAction() with arrays.
 *
 * @return void
 */
	function testRequestActionArray() {
		App::build(array(
			'models' => array(TEST_CAKE_CORE_INCLUDE_PATH . 'tests' . DS . 'test_app' . DS . 'models' . DS),
			'views' => array(TEST_CAKE_CORE_INCLUDE_PATH . 'tests' . DS . 'test_app' . DS . 'views' . DS),
			'controllers' => array(TEST_CAKE_CORE_INCLUDE_PATH . 'tests' . DS . 'test_app' . DS . 'controllers' . DS)
		));
	
		$result = $this->object->requestAction(
			array('controller' => 'request_action', 'action' => 'test_request_action')
		);
		$expected = 'This is a test';
		$this->assertEqual($result, $expected);

		$result = $this->object->requestAction(
			array('controller' => 'request_action', 'action' => 'another_ra_test'), 
			array('pass' => array('5', '7'))
		);
		$expected = 12;
		$this->assertEqual($result, $expected);

		$result = $this->object->requestAction(
			array('controller' => 'tests_apps', 'action' => 'index'), array('return')
		);
		$expected = 'This is the TestsAppsController index view';
		$this->assertEqual($result, $expected);

		$result = $this->object->requestAction(array('controller' => 'tests_apps', 'action' => 'some_method'));
		$expected = 5;
		$this->assertEqual($result, $expected);

		$result = $this->object->requestAction(
			array('controller' => 'request_action', 'action' => 'normal_request_action')
		);
		$expected = 'Hello World';
		$this->assertEqual($result, $expected);

		$result = $this->object->requestAction(
			array('controller' => 'request_action', 'action' => 'paginate_request_action')
		);
		$this->assertTrue($result);

		$result = $this->object->requestAction(
			array('controller' => 'request_action', 'action' => 'paginate_request_action'),
			array('pass' => array(5), 'named' => array('param' => 'value'))
		);
		$this->assertTrue($result);

		App::build();
	}

/**
 * Test that requestAction() is populating $this->params properly
 *
 * @access public
 * @return void
 */
	function testRequestActionParamParseAndPass() {
		$result = $this->object->requestAction('/request_action/params_pass');
		$this->assertTrue(isset($result['url']['url']));
		$this->assertEqual($result['url']['url'], '/request_action/params_pass');
		$this->assertEqual($result['controller'], 'request_action');
		$this->assertEqual($result['action'], 'params_pass');
		$this->assertEqual($result['form'], array());
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
 * @access public
 * @return void
 */
	function testRequestActionPostPassing() {
		$_tmp = $_POST;

		$_POST = array('data' => array(
			'item' => 'value'
		));
		$result = $this->object->requestAction(array('controller' => 'request_action', 'action' => 'post_pass'));
		$expected = array();
		$this->assertEqual($expected, $result);

		$result = $this->object->requestAction(array('controller' => 'request_action', 'action' => 'post_pass'), array('data' => $_POST['data']));
		$expected = $_POST['data'];
		$this->assertEqual($expected, $result);

		$result = $this->object->requestAction('/request_action/post_pass');
		$expected = $_POST['data'];
		$this->assertEqual($expected, $result);

		$_POST = $_tmp;
	}

/**
 * testCakeError
 *
 * @return void
 */
	function testCakeError() {

	}
}
