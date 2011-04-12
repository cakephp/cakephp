<?php
/**
 * AllLibsTest file
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
 * AllLibsTest class
 *
 * This test group will run all non mvc related lib class tests
 *
 * @package       cake.tests.cases
 */
class AllLibsTest extends PHPUnit_Framework_TestSuite {

/**
 * suite method, defines tests for this suite.
 *
 * @return void
 */
	public static function suite() {
		$suite = new CakeTestSuite('All non-MVC lib class tests');
		
		$suite->addTestFile(CORE_TEST_CASES . DS . 'BasicsTest.php');
		$suite->addTestDirectory(CORE_TEST_CASES . DS . 'Utility');
		$suite->addTestDirectory(CORE_TEST_CASES . DS . 'Log');
		$suite->addTestFile(CORE_TEST_CASES . DS . 'Model' . DS . 'Datasource' . DS . 'CakeSessionTest.php');
		//$suite->addTestDirectory(CORE_TEST_CASES . DS . 'Model' . DS . 'Datasource' . DS . 'Session');
		return $suite;
	}
}