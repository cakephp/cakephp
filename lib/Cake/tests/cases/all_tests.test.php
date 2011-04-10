<?php
/**
 * AllTests file
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
 * AllTests class
 *
 * This test group will run all test in the cases/libs/models/behaviors directory
 *
 * @package       cake.tests.groups
 */
class AllTests extends PHPUnit_Framework_TestSuite {

/**
 * Suite define the tests for this suite
 *
 * @return void
 */
	public static function suite() {
		$suite = new PHPUnit_Framework_TestSuite('All Tests');

		$path = CORE_TEST_CASES . DS . 'libs' . DS;
		$console = CORE_TEST_CASES . DS . 'console' . DS;

		$suite->addTestFile($console . 'all_console_libs.test.php');
		$suite->addTestFile($console . 'all_shells.test.php');
		$suite->addTestFile($console . 'all_tasks.test.php');

		$suite->addTestFile($path . 'all_behaviors.test.php');
		$suite->addTestFile($path . 'all_cache_engines.test.php');
		$suite->addTestFile($path . 'all_components.test.php');
		$suite->addTestFile($path . 'all_configure.test.php');
		$suite->addTestFile($path . 'all_controllers.test.php');
		$suite->addTestFile($path . 'all_database.test.php');
		$suite->addTestFile($path . 'all_error.test.php');
		$suite->addTestFile($path . 'all_helpers.test.php');
		$suite->addTestFile($path . 'all_libs.test.php');
		$suite->addTestFile($path . 'all_localization.test.php');
		$suite->addTestFile($path . 'all_model.test.php');
		$suite->addTestFile($path . 'all_routing.test.php');
		$suite->addTestFile($path . 'all_socket.test.php');
		$suite->addTestFile($path . 'all_test_suite.test.php');;
		$suite->addTestFile($path . 'all_views.test.php');
		$suite->addTestFile($path . 'all_xml.test.php');
		return $suite;
	}
}
