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
 * @subpackage		cake.tests.cases.libs.controller
 * @since			CakePHP(tm) v 1.2.0.5436
 * @version			$Revision$
 * @modifiedby		$LastChangedBy$
 * @lastmodified	$Date$
 * @license			http://www.opensource.org/licenses/opengroup.php The Open Group Test Suite License
 */
App::import('Core', array('Component', 'AppController'));
/**
 * Short description for class.
 *
 * @package    cake.tests
 * @subpackage cake.tests.cases.libs.controller
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
 * @package              cake
 * @subpackage           cake.tests.cases.libs.controller
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
 * @package              cake
 * @subpackage           cake.tests.cases.libs.controller
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
	function initialize(&$controller) {
		$this->Banana->testField = 'OrangeField';
	}
}
/**
 * BananaComponent class
 * 
 * @package              cake
 * @subpackage           cake.tests.cases.libs.controller
 */
class BananaComponent extends Object {
/**
 * testField property
 * 
 * @var string 'BananaField'
 * @access public
 */
	var $testField = 'BananaField';

}
/**
 * ComponentTest class
 * 
 * @package              cake
 * @subpackage           cake.tests.cases.libs.controller
 */
class ComponentTest extends CakeTestCase {
/**
 * setUp method
 * 
 * @access public
 * @return void
 */
	function setUp() {
		Configure::write('pluginPaths', array(TEST_CAKE_CORE_INCLUDE_PATH . 'tests' . DS . 'test_app' . DS . 'plugins' . DS));
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
		$this->assertTrue(is_a($Controller->TestPluginComponent->TestPluginOtherComponent, 'TestPluginOtherComponentComponent'));
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

	function testNestedComponentLoading() {
		$Controller =& new ComponentTestController();
		$Controller->components = array('Apple');
		$Controller->constructClasses();

		$this->assertTrue(is_a($Controller->Apple, 'AppleComponent'));
		$this->assertTrue(is_a($Controller->Apple->Orange, 'OrangeComponent'));
		$this->assertTrue(is_a($Controller->Apple->Orange->Banana, 'BananaComponent'));
	}

	function testComponentStartup() {
		$Controller =& new ComponentTestController();
		$Controller->components = array('Apple');
		$Controller->constructClasses();

		$this->assertTrue(is_a($Controller->Apple, 'AppleComponent'));
		$this->assertEqual($Controller->Apple->testName, null);

		$Controller->Component->startup($Controller);

		$this->assertEqual($Controller->Apple->testName, 'ComponentTest');
	}

	function testMultipleComponentInitialize() {
		$Controller =& new ComponentTestController();
		$Controller->components = array('Orange', 'Banana');
		$Controller->constructClasses();

		$Controller->Component->initialize($Controller);

		$this->assertEqual($Controller->Banana->testField, 'OrangeField');
		$this->assertEqual($Controller->Orange->Banana->testField, 'OrangeField');
	}
}
?>
