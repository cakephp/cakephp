<?php
/* SVN FILE: $Id$ */
/**
 * Short description for file.
 *
 * Long description for file
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) Tests <https://trac.cakephp.org/wiki/Developement/TestSuite>
 * Copyright 2005-2008, Cake Software Foundation, Inc.
 *								1785 E. Sahara Avenue, Suite 490-204
 *								Las Vegas, Nevada 89104
 *
 *  Licensed under The Open Group Test Suite License
 *  Redistributions of files must retain the above copyright notice.
 *
 * @filesource
 * @copyright		Copyright 2005-2008, Cake Software Foundation, Inc.
 * @link				https://trac.cakephp.org/wiki/Developement/TestSuite CakePHP(tm) Tests
 * @package			cake.tests
 * @subpackage		cake.tests.cases.libs
 * @since			CakePHP(tm) v 1.2.0.5432
 * @version			$Revision$
 * @modifiedby		$LastChangedBy$
 * @lastmodified	$Date$
 * @license			http://www.opensource.org/licenses/opengroup.php The Open Group Test Suite License
 */
App::import('Core', array('Object', 'Controller'));

if (!class_exists('RequestActionController')) {
	class RequestActionController extends Controller {
		var $uses = array();
		function test_request_action() {
			return 'This is a test';
		}

		function another_ra_test($id, $other) {
			return $id + $other;
		}
	}
}
class TestObject extends Object {

	var $methodCalls = array();

	function emptyMethod() {
		$this->methodCalls[] = 'emptyMethod';
	}

	function oneParamMethod($param) {
		$this->methodCalls[] = array('oneParamMethod' => array($param));
	}

	function twoParamMethod($param, $param2) {
		$this->methodCalls[] = array('twoParamMethod' => array($param, $param2));
	}

	function threeParamMethod($param, $param2, $param3) {
		$this->methodCalls[] = array('threeParamMethod' => array($param, $param2, $param3));
	}

	function crazyMethod($param, $param2, $param3, $param4, $param5, $param6, $param7 = null) {
		$this->methodCalls[] = array('crazyMethod' => array($param, $param2, $param3, $param4, $param5, $param6, $param7));
	}

	function methodWithOptionalParam($param = null) {
		$this->methodCalls[] = array('methodWithOptionalParam' => array($param));
	}
}

/**
 * Short description for class.
 *
 * @package    cake.tests
 * @subpackage cake.tests.cases.libs
 */
class ObjectTest extends UnitTestCase {

	function setUp() {
		$this->object = new TestObject();
	}

	function testToString() {
		$result = strtolower($this->object->toString());
		$this->assertEqual($result, 'testobject');
	}

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

		$this->object->dispatchMethod('threeParamMethod', array(true, false, null));
		$expected[] = array('threeParamMethod' => array(true, false, null));
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

	function testRequestAction(){
		$result = $this->object->requestAction('/request_action/test_request_action');
		$expected = 'This is a test';
		$this->assertEqual($result, $expected);

		$result = $this->object->requestAction(array('controller' => 'request_action', 'action' => 'test_request_action'));
		$expected = 'This is a test';
		$this->assertEqual($result, $expected);

		$result = $this->object->requestAction('/request_action/another_ra_test/2/5');
		$expected = 7;
		$this->assertEqual($result, $expected);

		$result = $this->object->requestAction(array('controller' => 'request_action', 'action' => 'another_ra_test'), array('pass' => array('5', '7')));
		$expected = 12;
		$this->assertEqual($result, $expected);

		Configure::write('controllerPaths', array(TEST_CAKE_CORE_INCLUDE_PATH . 'tests' . DS . 'test_app' . DS . 'controllers' . DS));
		Configure::write('viewPaths', array(TEST_CAKE_CORE_INCLUDE_PATH . 'tests' . DS . 'test_app' . DS . 'views' . DS));
		Configure::write('pluginPaths', array(TEST_CAKE_CORE_INCLUDE_PATH . 'tests' . DS . 'test_app' . DS . 'plugins' . DS));

		$result = $this->object->requestAction('/tests_apps/index', array('return'));
		$expected = 'This is the TestsAppsController index view';
		$this->assertEqual($result, $expected);

		$result = $this->object->requestAction(array('controller' => 'tests_apps', 'action' => 'index'), array('return'));
		$expected = 'This is the TestsAppsController index view';
		$this->assertEqual($result, $expected);

		$result = $this->object->requestAction('/tests_apps/some_method');
		$expected = 5;
		$this->assertEqual($result, $expected);

		$result = $this->object->requestAction(array('controller' => 'tests_apps', 'action' => 'some_method'));
		$expected = 5;
		$this->assertEqual($result, $expected);

		$result = $this->object->requestAction('/test_plugin/tests_plugins_tests/index', array('return'));
		$expected = 'This is the TestsPluginsTestsController index view';
		$this->assertEqual($result, $expected);

		$result = $this->object->requestAction(array('controller' => 'tests_plugins_tests', 'action' => 'index', 'plugin' => 'test_plugin'), array('return'));
		$expected = 'This is the TestsPluginsTestsController index view';
		$this->assertEqual($result, $expected);

		$result = $this->object->requestAction('/test_plugin/tests_plugins_tests/some_method');
		$expected = 25;
		$this->assertEqual($result, $expected);

		$result = $this->object->requestAction(array('controller' => 'tests_plugins_tests', 'action' => 'some_method', 'plugin' => 'test_plugin'));
		$expected = 25;
		$this->assertEqual($result, $expected);
	}

	function tearDown() {
		unset($this->object);
	}
}
?>