<?php
/**
 * ComponentTest file
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) Tests <https://trac.cakephp.org/wiki/Developement/TestSuite>
 * Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 *  Licensed under The Open Group Test Suite License
 *  Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          https://trac.cakephp.org/wiki/Developement/TestSuite CakePHP(tm) Tests
 * @package       cake
 * @subpackage    cake.tests.cases.libs.controller
 * @since         CakePHP(tm) v 1.2.0.5436
 * @license       http://www.opensource.org/licenses/opengroup.php The Open Group Test Suite License
 */
App::import('Controller', 'Controller', false);
App::import('Controller', 'Component', false);

if (!class_exists('AppController')) {

/**
 * AppController class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs.controller
 */
	class AppController extends Controller {

/**
 * name property
 *
 * @var string 'App'
 * @access public
 */
		var $name = 'App';

/**
 * uses property
 *
 * @var array
 * @access public
 */
		var $uses = array();

/**
 * helpers property
 *
 * @var array
 * @access public
 */
		var $helpers = array();

/**
 * components property
 *
 * @var array
 * @access public
 */
		var $components = array('Orange' => array('colour' => 'blood orange'));
	}
} elseif (!defined('APP_CONTROLLER_EXISTS')){
	define('APP_CONTROLLER_EXISTS', true);
}

/**
 * ParamTestComponent
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs.controller
 */
class ParamTestComponent extends Object {

/**
 * name property
 *
 * @var string 'ParamTest'
 * @access public
 */
	var $name = 'ParamTest';

/**
 * components property
 *
 * @var array
 * @access public
 */
	var $components = array('Banana' => array('config' => 'value'));

/**
 * initialize method
 *
 * @param mixed $controller
 * @param mixed $settings
 * @access public
 * @return void
 */
	function initialize(&$controller, $settings) {
		foreach ($settings as $key => $value) {
			if (is_numeric($key)) {
				$this->{$value} = true;
			} else {
				$this->{$key} = $value;
			}
		}
	}
}

/**
 * ComponentTestController class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs.controller
 */
class ComponentTestController extends AppController {

/**
 * name property
 *
 * @var string 'ComponentTest'
 * @access public
 */
	var $name = 'ComponentTest';

/**
 * uses property
 *
 * @var array
 * @access public
 */
	var $uses = array();
}

/**
 * AppleComponent class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs.controller
 */
class AppleComponent extends Object {

/**
 * components property
 *
 * @var array
 * @access public
 */
	var $components = array('Orange');

/**
 * testName property
 *
 * @var mixed null
 * @access public
 */
	var $testName = null;

/**
 * startup method
 *
 * @param mixed $controller
 * @access public
 * @return void
 */
	function startup(&$controller) {
		$this->testName = $controller->name;
	}
}

/**
 * OrangeComponent class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs.controller
 */
class OrangeComponent extends Object {

/**
 * components property
 *
 * @var array
 * @access public
 */
	var $components = array('Banana');

/**
 * initialize method
 *
 * @param mixed $controller
 * @access public
 * @return void
 */
	function initialize(&$controller, $settings) {
		$this->Controller = $controller;
		$this->Banana->testField = 'OrangeField';
		$this->settings = $settings;
	}

/**
 * startup method
 *
 * @param Controller $controller
 * @return string
 * @access public
 */
	function startup(&$controller) {
		$controller->foo = 'pass';
	}
}

/**
 * BananaComponent class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs.controller
 */
class BananaComponent extends Object {

/**
 * testField property
 *
 * @var string 'BananaField'
 * @access public
 */
	var $testField = 'BananaField';

/**
 * startup method
 *
 * @param Controller $controller
 * @return string
 * @access public
 */
	function startup(&$controller) {
		$controller->bar = 'fail';
	}
}

/**
 * MutuallyReferencingOneComponent class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs.controller
 */
class MutuallyReferencingOneComponent extends Object {

/**
 * components property
 *
 * @var array
 * @access public
 */
	var $components = array('MutuallyReferencingTwo');
}

/**
 * MutuallyReferencingTwoComponent class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs.controller
 */
class MutuallyReferencingTwoComponent extends Object {

/**
 * components property
 *
 * @var array
 * @access public
 */
	var $components = array('MutuallyReferencingOne');
}

/**
 * SomethingWithEmailComponent class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs.controller
 */
class SomethingWithEmailComponent extends Object {

/**
 * components property
 *
 * @var array
 * @access public
 */
	var $components = array('Email');
}

Mock::generate('Object', 'ComponentMockComponent', array('startup', 'beforeFilter', 'beforeRender', 'other'));

/**
 * ComponentTest class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs.controller
 */
class ComponentTest extends CakeTestCase {

/**
 * setUp method
 *
 * @access public
 * @return void
 */
	function setUp() {
		$this->_pluginPaths = App::path('plugins');
		App::build(array(
			'plugins' => array(TEST_CAKE_CORE_INCLUDE_PATH . 'tests' . DS . 'test_app' . DS . 'plugins' . DS)
		));
	}

/**
 * tearDown method
 *
 * @access public
 * @return void
 */
	function tearDown() {
		App::build();
		ClassRegistry::flush();
	}

/**
 * testLoadComponents method
 *
 * @access public
 * @return void
 */
	function testLoadComponents() {
		$Controller =& new ComponentTestController();
		$Controller->components = array('RequestHandler');

		$Component =& new Component();
		$Component->init($Controller);

		$this->assertTrue(is_a($Controller->RequestHandler, 'RequestHandlerComponent'));

		$Controller =& new ComponentTestController();
		$Controller->plugin = 'test_plugin';
		$Controller->components = array('RequestHandler', 'TestPluginComponent');

		$Component =& new Component();
		$Component->init($Controller);

		$this->assertTrue(is_a($Controller->RequestHandler, 'RequestHandlerComponent'));
		$this->assertTrue(is_a($Controller->TestPluginComponent, 'TestPluginComponentComponent'));
		$this->assertTrue(is_a(
			$Controller->TestPluginComponent->TestPluginOtherComponent,
			'TestPluginOtherComponentComponent'
		));
		$this->assertFalse(isset($Controller->TestPluginOtherComponent));

		$Controller =& new ComponentTestController();
		$Controller->components = array('Security');

		$Component =& new Component();
		$Component->init($Controller);

		$this->assertTrue(is_a($Controller->Security, 'SecurityComponent'));
		$this->assertTrue(is_a($Controller->Security->Session, 'SessionComponent'));

		$Controller =& new ComponentTestController();
		$Controller->components = array('Security', 'Cookie', 'RequestHandler');

		$Component =& new Component();
		$Component->init($Controller);

		$this->assertTrue(is_a($Controller->Security, 'SecurityComponent'));
		$this->assertTrue(is_a($Controller->Security->RequestHandler, 'RequestHandlerComponent'));
		$this->assertTrue(is_a($Controller->RequestHandler, 'RequestHandlerComponent'));
		$this->assertTrue(is_a($Controller->Cookie, 'CookieComponent'));
	}

/**
 * test component loading
 *
 * @return void
 */
	function testNestedComponentLoading() {
		$Controller =& new ComponentTestController();
		$Controller->components = array('Apple');
		$Controller->uses = false;
		$Controller->constructClasses();
		$Controller->Component->initialize($Controller);

		$this->assertTrue(is_a($Controller->Apple, 'AppleComponent'));
		$this->assertTrue(is_a($Controller->Apple->Orange, 'OrangeComponent'));
		$this->assertTrue(is_a($Controller->Apple->Orange->Banana, 'BananaComponent'));
		$this->assertTrue(is_a($Controller->Apple->Orange->Controller, 'ComponentTestController'));
		$this->assertTrue(empty($Controller->Apple->Session));
		$this->assertTrue(empty($Controller->Apple->Orange->Session));
	}

/**
 * Tests Component::startup() and only running callbacks for components directly attached to
 * the controller.
 *
 * @return void
 */
	function testComponentStartup() {
		$Controller =& new ComponentTestController();
		$Controller->components = array('Apple');
		$Controller->uses = false;
		$Controller->constructClasses();
		$Controller->Component->initialize($Controller);
		$Controller->beforeFilter();
		$Controller->Component->startup($Controller);

		$this->assertTrue(is_a($Controller->Apple, 'AppleComponent'));
		$this->assertEqual($Controller->Apple->testName, 'ComponentTest');

		$expected = !(defined('APP_CONTROLLER_EXISTS') && APP_CONTROLLER_EXISTS);
		$this->assertEqual(isset($Controller->foo), $expected);
		$this->assertFalse(isset($Controller->bar));
	}

/**
 * test that triggerCallbacks fires methods on all the components, and can trigger any method.
 *
 * @return void
 */
	function testTriggerCallback() {
		$Controller =& new ComponentTestController();
		$Controller->components = array('ComponentMock');
		$Controller->uses = null;
		$Controller->constructClasses();

		$Controller->ComponentMock->expectOnce('beforeRender');
		$Controller->Component->triggerCallback('beforeRender', $Controller);

		$Controller->ComponentMock->expectNever('beforeFilter');
		$Controller->ComponentMock->enabled = false;
		$Controller->Component->triggerCallback('beforeFilter', $Controller);
	}

/**
 * test a component being used more than once.
 *
 * @return void
 */
	function testMultipleComponentInitialize() {
		$Controller =& new ComponentTestController();
		$Controller->uses = false;
		$Controller->components = array('Orange', 'Banana');
		$Controller->constructClasses();
		$Controller->Component->initialize($Controller);

		$this->assertEqual($Controller->Banana->testField, 'OrangeField');
		$this->assertEqual($Controller->Orange->Banana->testField, 'OrangeField');
	}

/**
 * Test Component declarations with Parameters
 * tests merging of component parameters and merging / construction of components.
 *
 * @return void
 */
	function testComponentsWithParams() {
		if ($this->skipIf(defined('APP_CONTROLLER_EXISTS'), '%s Need a non-existent AppController')) {
			return;
		}

		$Controller =& new ComponentTestController();
		$Controller->components = array('ParamTest' => array('test' => 'value', 'flag'), 'Apple');
		$Controller->uses = false;
		$Controller->constructClasses();
		$Controller->Component->initialize($Controller);

		$this->assertTrue(is_a($Controller->ParamTest, 'ParamTestComponent'));
		$this->assertTrue(is_a($Controller->ParamTest->Banana, 'BananaComponent'));
		$this->assertTrue(is_a($Controller->Orange, 'OrangeComponent'));
		$this->assertFalse(isset($Controller->Session));
		$this->assertEqual($Controller->Orange->settings, array('colour' => 'blood orange'));
		$this->assertEqual($Controller->ParamTest->test, 'value');
		$this->assertEqual($Controller->ParamTest->flag, true);

		//Settings are merged from app controller and current controller.
		$Controller =& new ComponentTestController();
		$Controller->components = array(
			'ParamTest' => array('test' => 'value'),
			'Orange' => array('ripeness' => 'perfect')
		);
		$Controller->constructClasses();
		$Controller->Component->initialize($Controller);

		$expected = array('colour' => 'blood orange', 'ripeness' => 'perfect');
		$this->assertEqual($Controller->Orange->settings, $expected);
		$this->assertEqual($Controller->ParamTest->test, 'value');
	}

/**
 * Ensure that settings are not duplicated when passed into component initialize.
 *
 * @return void
 */
	function testComponentParamsNoDuplication() {
		if ($this->skipIf(defined('APP_CONTROLLER_EXISTS'), '%s Need a non-existent AppController')) {
			return;
		}
		$Controller =& new ComponentTestController();
		$Controller->components = array('Orange' => array('setting' => array('itemx')));
		$Controller->uses = false;

		$Controller->constructClasses();
		$Controller->Component->initialize($Controller);
		$expected = array('setting' => array('itemx'), 'colour' => 'blood orange');
		$this->assertEqual($Controller->Orange->settings, $expected, 'Params duplication has occured %s');
	}

/**
 * Test mutually referencing components.
 *
 * @return void
 */
	function testMutuallyReferencingComponents() {
		$Controller =& new ComponentTestController();
		$Controller->components = array('MutuallyReferencingOne');
		$Controller->uses = false;
		$Controller->constructClasses();
		$Controller->Component->initialize($Controller);

		$this->assertTrue(is_a(
			$Controller->MutuallyReferencingOne,
			'MutuallyReferencingOneComponent'
		));
		$this->assertTrue(is_a(
			$Controller->MutuallyReferencingOne->MutuallyReferencingTwo,
			'MutuallyReferencingTwoComponent'
		));
		$this->assertTrue(is_a(
			$Controller->MutuallyReferencingOne->MutuallyReferencingTwo->MutuallyReferencingOne,
			'MutuallyReferencingOneComponent'
		));
	}

/**
 * Test mutually referencing components.
 *
 * @return void
 */
	function testSomethingReferencingEmailComponent() {
		$Controller =& new ComponentTestController();
		$Controller->components = array('SomethingWithEmail');
		$Controller->uses = false;
		$Controller->constructClasses();
		$Controller->Component->initialize($Controller);
		$Controller->beforeFilter();
		$Controller->Component->startup($Controller);

		$this->assertTrue(is_a(
			$Controller->SomethingWithEmail,
			'SomethingWithEmailComponent'
		));
		$this->assertTrue(is_a(
			$Controller->SomethingWithEmail->Email,
			'EmailComponent'
		));
		$this->assertTrue(is_a(
			$Controller->SomethingWithEmail->Email->Controller,
			'ComponentTestController'
		));
	}

/**
 * Test that SessionComponent doesn't get added if its already in the components array.
 *
 * @return void
 * @access public
 */
	function testDoubleLoadingOfSessionComponent() {
		if ($this->skipIf(defined('APP_CONTROLLER_EXISTS'), '%s Need a non-existent AppController')) {
			return;
		}

		$Controller =& new ComponentTestController();
		$Controller->uses = false;
		$Controller->components = array('Session');
		$Controller->constructClasses();

		$this->assertEqual($Controller->components, array('Session' => '', 'Orange' => array('colour' => 'blood orange')));
	}

}
?>