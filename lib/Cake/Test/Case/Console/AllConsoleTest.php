<?php
/**
 * AllConsoleTest file
 *
 * PHP 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2011, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2011, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       Cake.Test.Case.Console
 * @since         CakePHP(tm) v 2.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

/**
 * AllConsoleTest class
 *
 * This test group will run all console classes.
 *
 * @package       Cake.Test.Case.Console
 */
class AllConsoleTest extends PHPUnit_Framework_TestSuite {

/**
 * suite method, defines tests for this suite.
 *
 * @return void
 */
	public static function suite() {
		$suite = new CakeTestSuite('All console classes');

		$path = CORE_TEST_CASES . DS . 'Console' . DS;

		$suite->addTestFile($path . 'AllConsoleLibsTest.php');
		$suite->addTestFile($path . 'AllTasksTest.php');
		$suite->addTestFile($path . 'AllShellsTest.php');
		return $suite;
	}
}
