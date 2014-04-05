<?php
/**
 * AllTests file
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       Cake.Test.Case
 * @since         CakePHP(tm) v 2.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */

/**
 * AllTests class
 *
 * This test group will run all tests.
 *
 * @package       Cake.Test.Case
 */
class AllTests extends PHPUnit_Framework_TestSuite {

/**
 * Suite define the tests for this suite
 *
 * @return void
 */
	public static function suite() {
		$suite = new PHPUnit_Framework_TestSuite('All Tests');

		$path = CORE_TEST_CASES . DS;

		$suite->addTestFile($path . 'BasicsTest.php');
		$suite->addTestFile($path . 'AllConsoleTest.php');
		$suite->addTestFile($path . 'AllBehaviorsTest.php');
		$suite->addTestFile($path . 'AllCacheTest.php');
		$suite->addTestFile($path . 'AllComponentsTest.php');
		$suite->addTestFile($path . 'AllConfigureTest.php');
		$suite->addTestFile($path . 'AllCoreTest.php');
		$suite->addTestFile($path . 'AllControllerTest.php');
		$suite->addTestFile($path . 'AllDatabaseTest.php');
		$suite->addTestFile($path . 'AllErrorTest.php');
		$suite->addTestFile($path . 'AllEventTest.php');
		$suite->addTestFile($path . 'AllHelpersTest.php');
		$suite->addTestFile($path . 'AllLogTest.php');
		$suite->addTestFile($path . 'Model' . DS . 'ModelTest.php');
		$suite->addTestFile($path . 'AllRoutingTest.php');
		$suite->addTestFile($path . 'AllNetworkTest.php');
		$suite->addTestFile($path . 'AllTestSuiteTest.php');
		$suite->addTestFile($path . 'AllUtilityTest.php');
		$suite->addTestFile($path . 'AllViewTest.php');
		$suite->addTestFile($path . 'AllI18nTest.php');
		return $suite;
	}
}
