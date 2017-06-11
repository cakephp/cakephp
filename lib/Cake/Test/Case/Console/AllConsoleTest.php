<?php
/**
 * AllConsoleTest file
 *
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @package       Cake.Test.Case.Console
 * @since         CakePHP(tm) v 2.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
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
