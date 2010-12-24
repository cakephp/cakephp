<?php
/**
 * ComponentTest file
 *
 * PHP 5
 *
 * CakePHP(tm) Tests <http://book.cakephp.org/view/1196/Testing>
 * Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/view/1196/Testing CakePHP(tm) Tests
 * @package       cake.tests.cases.libs.controller
 * @since         CakePHP(tm) v 1.2.0.5436
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
App::import('Controller', 'Controller', false);
App::import('Controller', 'Component', false);

/**
 * ParamTestComponent
 *
 * @package       cake.tests.cases.libs.controller
 */
class ParamTestComponent extends Component {

/**
 * name property
 *
 * @var string 'ParamTest'
 * @access public
 */
	public $name = 'ParamTest';

/**
 * components property
 *
 * @var array
 * @access public
 */
	public $components = array('Banana' => array('config' => 'value'));

/**
 * initialize method
 *
 * @param mixed $controller
 * @param mixed $settings
 * @access public
 * @return void
 */
	function initialize($controllerz) {
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
 * @package       cake.tests.cases.libs.controller
 */
class ComponentTestController extends Controller {

/**
 * name property
 *
 * @var string 'ComponentTest'
 * @access public
 */
	public $name = 'ComponentTest';

/**
 * uses property
 *
 * @var array
 * @access public
 */
	public $uses = array();

}

/**
 * AppleComponent class
 *
 * @package       cake.tests.cases.libs.controller
 */
class AppleComponent extends Component {

/**
 * components property
 *
 * @var array
 * @access public
 */
	public $components = array('Orange');

/**
 * testName property
 *
 * @var mixed null
 * @access public
 */
	public $testName = null;

/**
 * startup method
 *
 * @param mixed $controller
 * @access public
 * @return void
 */
	function startup($controller) {
		$this->testName = $controller->name;
	}
}

/**
 * OrangeComponent class
 *
 * @package       cake.tests.cases.libs.controller
 */
class OrangeComponent extends Component {

/**
 * components property
 *
 * @var array
 * @access public
 */
	public $components = array('Banana');

/**
 * initialize method
 *
 * @param mixed $controller
 * @access public
 * @return void
 */
	function initialize($controller) {
		$this->Controller = $controller;
		$this->Banana->testField = 'OrangeField';
	}

/**
 * startup method
 *
 * @param Controller $controller
 * @return string
 */
	public function startup($controller) {
		$controller->foo = 'pass';
	}
}

/**
 * BananaComponent class
 *
 * @package       cake.tests.cases.libs.controller
 */
class BananaComponent extends Component {

/**
 * testField property
 *
 * @var string 'BananaField'
 * @access public
 */
	public $testField = 'BananaField';

/**
 * startup method
 *
 * @param Controller $controller
 * @return string
 */
	public function startup($controller) {
		$controller->bar = 'fail';
	}
}

/**
 * MutuallyReferencingOneComponent class
 *
 * @package       cake.tests.cases.libs.controller
 */
class MutuallyReferencingOneComponent extends Component {

/**
 * components property
 *
 * @var array
 * @access public
 */
	public $components = array('MutuallyReferencingTwo');
}

/**
 * MutuallyReferencingTwoComponent class
 *
 * @package       cake.tests.cases.libs.controller
 */
class MutuallyReferencingTwoComponent extends Component {

/**
 * components property
 *
 * @var array
 * @access public
 */
	public $components = array('MutuallyReferencingOne');
}

/**
 * SomethingWithEmailComponent class
 *
 * @package       cake.tests.cases.libs.controller
 */
class SomethingWithEmailComponent extends Component {

/**
 * components property
 *
 * @var array
 * @access public
 */
	public $components = array('Email');
}


/**
 * ComponentTest class
 *
 * @package       cake.tests.cases.libs.controller
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
 * test accessing inner components.
 *
 * @return void
 */
	function testInnerComponentConstruction() {
		$Collection = new ComponentCollection();
		$Component = new AppleComponent($Collection);

		$this->assertInstanceOf('OrangeComponent', $Component->Orange, 'class is wrong');
	}

/**
 * test component loading
 *
 * @return void
 */
	function testNestedComponentLoading() {
		$Collection = new ComponentCollection();
		$Apple = new AppleComponent($Collection);

		$this->assertInstanceOf('OrangeComponent', $Apple->Orange, 'class is wrong');
		$this->assertInstanceOf('BananaComponent', $Apple->Orange->Banana, 'class is wrong');
		$this->assertTrue(empty($Apple->Session));
		$this->assertTrue(empty($Apple->Orange->Session));
	}

/**
 * test that component components are not enabled in the collection.
 *
 * @return void
 */
	function testInnerComponentsAreNotEnabled() {
		$Collection = new ComponentCollection();
		$Apple = $Collection->load('Apple');

		$this->assertInstanceOf('OrangeComponent', $Apple->Orange, 'class is wrong');
		$result = $Collection->enabled();
		$this->assertEquals(array('Apple'), $result, 'Too many components enabled.');
	}

/**
 * test a component being used more than once.
 *
 * @return void
 */
	function testMultipleComponentInitialize() {
		$Collection = new ComponentCollection();
		$Banana = $Collection->load('Banana');
		$Orange = $Collection->load('Orange');
		
		$this->assertSame($Banana, $Orange->Banana, 'Should be references');
		$Banana->testField = 'OrangeField';
		
		$this->assertSame($Banana->testField, $Orange->Banana->testField, 'References are broken');
	}

/**
 * Test mutually referencing components.
 *
 * @return void
 */
	function testSomethingReferencingEmailComponent() {
		$Controller = new ComponentTestController();
		$Controller->components = array('SomethingWithEmail');
		$Controller->uses = false;
		$Controller->constructClasses();
		$Controller->Components->trigger('initialize', array(&$Controller));
		$Controller->beforeFilter();
		$Controller->Components->trigger('startup', array(&$Controller));

		$this->assertInstanceOf('SomethingWithEmailComponent', $Controller->SomethingWithEmail);
		$this->assertInstanceOf('EmailComponent', $Controller->SomethingWithEmail->Email);
		$this->assertInstanceOf('ComponentTestController', $Controller->SomethingWithEmail->Email->Controller);
	}

}
