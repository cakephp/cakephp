<?php
/**
 * AllControllersTest file
 *
 * PHP 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       cake.tests.cases
 * @since         CakePHP(tm) v 2.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

/**
 * AllControllersTest class
 *
 * This test group will run cache engine tests.
 *
 * @package       cake.tests.groups
 */
class AllControllersTest extends PHPUnit_Framework_TestSuite {

/**
 * suite method, defines tests for this suite.
 *
 * @return void
 */
	public static function suite() {
		$suite = new PHPUnit_Framework_TestSuite('All Controller related class tests');

		$suite->addTestFile(CORE_TEST_CASES . DS . 'libs' . DS . 'controller' . DS . 'controller.test.php');
		$suite->addTestFile(CORE_TEST_CASES . DS . 'libs' . DS . 'controller' . DS . 'scaffold.test.php');
		$suite->addTestFile(CORE_TEST_CASES . DS . 'libs' . DS . 'controller' . DS . 'pages_controller.test.php');
		$suite->addTestFile(CORE_TEST_CASES . DS . 'libs' . DS . 'controller' . DS . 'component.test.php');
		$suite->addTestFile(CORE_TEST_CASES . DS . 'libs' . DS . 'controller' . DS . 'controller_merge_vars.test.php');
		return $suite;
	}
}