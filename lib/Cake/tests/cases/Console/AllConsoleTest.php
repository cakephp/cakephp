<?php
/**
 * AllConsoleTest file
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
 * AllConsoleTest class
 *
 * This test group will run all console classes.
 *
 * @package       cake.tests.cases.console
 */
class AllConsoleTest extends PHPUnit_Framework_TestSuite {

/**
 * suite method, defines tests for this suite.
 *
 * @return void
 */
	public static function suite() {
		$suite = new CakeTestSuite('All console classes');

		$path = CORE_TEST_CASES . DS . 'console' . DS;

		$suite->addTestFile($path . 'all_console_libs.test.php');
		$suite->addTestFile($path . 'all_shells.test.php');
		$suite->addTestFile($path . 'all_tasks.test.php');
		return $suite;
	}
}