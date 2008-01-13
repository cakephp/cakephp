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
uses('controller' . DS . 'component', 'controller' . DS . 'app_controller');
/**
 * Short description for class.
 *
 * @package    cake.tests
 * @subpackage cake.tests.cases.libs.controller
 */
class ComponentTestController extends AppController {
	var $name = 'ComponentTestController';
	var $uses = array();
}
class ComponentTest extends CakeTestCase {

	function setUp() {
		Configure::write('pluginPaths', array(TEST_CAKE_CORE_INCLUDE_PATH . 'tests' . DS . 'test_app' . DS . 'plugins' . DS));
		$this->Controller = new ComponentTestController();
	}

	function tearDown() {
		unset($this->Controller);
	}

	function testLoadComponents() {
		$this->Controller->components = array('RequestHandler');
		$Component = new Component($this->Controller);

		$loaded = array();
		$result = $Component->init($this->Controller);
		$this->assertTrue(is_object($this->Controller->RequestHandler));

		$this->Controller->plugin = 'test_plugin';
		$this->Controller->components = array('RequestHandler', 'TestPluginComponent');

		$result = $Component->init($this->Controller);

		$this->assertTrue(is_object($this->Controller->RequestHandler));
		$this->assertTrue(is_object($this->Controller->TestPluginComponent));
		$this->assertTrue(is_object($this->Controller->TestPluginComponent->TestPluginOtherComponent));
		$this->assertFalse(isset($this->Controller->TestPluginOtherComponent));
		
		$this->Controller->components = array('Security');
		
		$result = $Component->init($this->Controller);
		$this->assertTrue(is_object($this->Controller->Security));
		$this->assertTrue(is_object($this->Controller->Security->Session));
		
		
	}
}
?>