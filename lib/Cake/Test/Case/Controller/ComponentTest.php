<?php
/**
 * ComponentTest file
 *
 * CakePHP(tm) Tests <https://book.cakephp.org/2.0/en/development/testing.html>
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://book.cakephp.org/2.0/en/development/testing.html CakePHP(tm) Tests
 * @package       Cake.Test.Case.Controller
 * @since         CakePHP(tm) v 1.2.0.5436
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

App::uses('Controller', 'Controller');
App::uses('Component', 'Controller');

/**
 * ParamTestComponent
 *
 * @package       Cake.Test.Case.Controller
 */
class ParamTestComponent extends Component {

/**
 * components property
 *
 * @var array
 */
	public $components = array(
		'Apple' => array('enabled' => true),
		'Banana' => array('config' => 'value'),
	);
}

/**
 * ComponentTestController class
 *
 * @package       Cake.Test.Case.Controller
 */
class ComponentTestController extends Controller {

/**
 * uses property
 *
 * @var array
 */
	public $uses = array();

}

/**
 * AppleComponent class
 *
 * @package       Cake.Test.Case.Controller
 */
class AppleComponent extends Component {

/**
 * components property
 *
 * @var array
 */
	public $components = array('Orange');

/**
 * testName property
 *
 * @var mixed
 */
	public $testName = null;

/**
 * startup method
 *
 * @param Controller $controller
 * @return void
 */
	public function startup(Controller $controller) {
		$this->testName = $controller->name;
	}

}

/**
 * OrangeComponent class
 *
 * @package       Cake.Test.Case.Controller
 */
class OrangeComponent extends Component {

/**
 * components property
 *
 * @var array
 */
	public $components = array('Banana');

/**
 * initialize method
 *
 * @param Controller $controller
 * @return void
 */
	public function initialize(Controller $controller) {
		$this->Controller = $controller;
		$this->Banana->testField = 'OrangeField';
	}

/**
 * startup method
 *
 * @param Controller $controller
 * @return string
 */
	public function startup(Controller $controller) {
		$controller->foo = 'pass';
	}

}

/**
 * BananaComponent class
 *
 * @package       Cake.Test.Case.Controller
 */
class BananaComponent extends Component {

/**
 * testField property
 *
 * @var string
 */
	public $testField = 'BananaField';

/**
 * startup method
 *
 * @param Controller $controller
 * @return string
 */
	public function startup(Controller $controller) {
		$controller->bar = 'fail';
	}

}

/**
 * MutuallyReferencingOneComponent class
 *
 * @package       Cake.Test.Case.Controller
 */
class MutuallyReferencingOneComponent extends Component {

/**
 * components property
 *
 * @var array
 */
	public $components = array('MutuallyReferencingTwo');
}

/**
 * MutuallyReferencingTwoComponent class
 *
 * @package       Cake.Test.Case.Controller
 */
class MutuallyReferencingTwoComponent extends Component {

/**
 * components property
 *
 * @var array
 */
	public $components = array('MutuallyReferencingOne');
}

/**
 * SomethingWithEmailComponent class
 *
 * @package       Cake.Test.Case.Controller
 */
class SomethingWithEmailComponent extends Component {

/**
 * components property
 *
 * @var array
 */
	public $components = array('Email');
}

/**
 * ComponentTest class
 *
 * @package       Cake.Test.Case.Controller
 */
class ComponentTest extends CakeTestCase {

/**
 * setUp method
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();
		$this->_pluginPaths = App::path('plugins');
		App::build(array(
			'Plugin' => array(CAKE . 'Test' . DS . 'test_app' . DS . 'Plugin' . DS)
		));
	}

/**
 * test accessing inner components.
 *
 * @return void
 */
	public function testInnerComponentConstruction() {
		$Collection = new ComponentCollection();
		$Component = new AppleComponent($Collection);

		$this->assertInstanceOf('OrangeComponent', $Component->Orange, 'class is wrong');
	}

/**
 * test component loading
 *
 * @return void
 */
	public function testNestedComponentLoading() {
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
	public function testInnerComponentsAreNotEnabled() {
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
	public function testMultipleComponentInitialize() {
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
	public function testSomethingReferencingEmailComponent() {
		$Controller = new ComponentTestController();
		$Controller->components = array('SomethingWithEmail');
		$Controller->uses = false;
		$Controller->constructClasses();
		$Controller->Components->trigger('initialize', array(&$Controller));
		$Controller->beforeFilter();
		$Controller->Components->trigger('startup', array(&$Controller));

		$this->assertInstanceOf('SomethingWithEmailComponent', $Controller->SomethingWithEmail);
		$this->assertInstanceOf('EmailComponent', $Controller->SomethingWithEmail->Email);
	}

/**
 * Test lazy loading of components inside components and both explicit and
 * implicit 'enabled' setting.
 *
 * @return void
 */
	public function testGet() {
		$Collection = new ComponentCollection();
		$ParamTest = $Collection->load('ParamTest');
		$this->assertTrue($ParamTest->Apple->settings['enabled']);
		$this->assertFalse($ParamTest->Banana->settings['enabled']);
	}

}
