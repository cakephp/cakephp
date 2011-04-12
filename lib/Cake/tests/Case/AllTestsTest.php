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
 * @package       cakeTests.cases
 * @since         CakePHP(tm) v 2.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
/**
 * AllTests class
 *
 * This test group will run all test in the cases/libs/models/behaviors directory
 *
 * @package       cakeTests.groups
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
		$console = $path . 'Console' . DS;

		$suite->addTestFile($console . 'AllConsoleTest.php');
		$suite->addTestFile($path . 'AllBehaviorsTest.php');
		$suite->addTestFile($path . 'AllCacheEnginesTest.php');
		$suite->addTestFile($path . 'AllComponentsTest.php');
		$suite->addTestFile($path . 'AllConfigureTest.php');
		$suite->addTestFile($path . 'AllControllersTest.php');
		$suite->addTestFile($path . 'AllDatabaseTest.php');
		$suite->addTestFile($path . 'AllErrorTest.php');
		$suite->addTestFile($path . 'AllHelpersTest.php');
		$suite->addTestFile($path . 'AllLibsTest.php');
		$suite->addTestFile($path . 'AllLocalizationTest.php');
		$suite->addTestFile($path . 'AllModelTest.php');
		$suite->addTestFile($path . 'AllRoutingTest.php');
		$suite->addTestFile($path . 'AllSocketTest.php');
		$suite->addTestFile($path . 'AllTestSuiteTest.php');;
		$suite->addTestFile($path . 'AllViewsTest.php');
		$suite->addTestFile($path . 'AllXmlTest.php');
		return $suite;
	}
}
