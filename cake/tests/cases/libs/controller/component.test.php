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
	}

	function testLoadComponents() {
		$Controller = new ComponentTestController();
		$Controller->components = array('RequestHandler');

		$Component = new Component($Controller);
		$Component->init($Controller);

		$this->assertTrue(is_a($Controller->RequestHandler, 'RequestHandlerComponent'));

		$Controller = new ComponentTestController();
		$Controller->plugin = 'test_plugin';
		$Controller->components = array('RequestHandler', 'TestPluginComponent');

		$Component->init($Controller);

		$this->assertTrue(is_a($Controller->RequestHandler, 'RequestHandlerComponent'));
		$this->assertTrue(is_a($Controller->TestPluginComponent, 'TestPluginComponentComponent'));
		$this->assertTrue(is_a($Controller->TestPluginComponent->TestPluginOtherComponent, 'TestPluginOtherComponentComponent'));
		$this->assertFalse(isset($Controller->TestPluginOtherComponent));

		$Controller = new ComponentTestController();
		$Controller->components = array('Security');

		$result = $Component->init($Controller);
		$this->assertTrue(is_object($Controller->Security));
		$this->assertTrue(is_object($Controller->Security->Session));

		$Controller = new ComponentTestController();
		$Controller->components = array('Security', 'Cookie', 'RequestHandler');

		$result = $Component->init($Controller);
		$this->assertTrue(is_a($Controller->Security, 'SecurityComponent'));
		$this->assertTrue(is_a($Controller->Security->RequestHandler, 'RequestHandlerComponent'));
		$this->assertTrue(is_a($Controller->RequestHandler, 'RequestHandlerComponent'));
		$this->assertTrue(is_a($Controller->Cookie, 'CookieComponent'));
	}
}
?>