<?php
/**
 * AllRoutingTest file
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
 * AllRoutingTest class
 *
 * This test group will run view class tests (view, theme)
 *
 * @package       cake.tests.groups
 */
class AllRoutingTest extends PHPUnit_Framework_TestSuite {

/**
 * suite method, defines tests for this suite.
 *
 * @return void
 */
	public static function suite() {
		$suite = new CakeTestSuite('All Router and Dispatcher class tests');

		$libs = CORE_TEST_CASES . DS;

		$suite->addTestDirectory($libs . 'Routing');
		$suite->addTestFile($libs . 'Network' . DS . 'CakeResponseTest.php');
		$suite->addTestFile($libs . 'Network' . DS . 'CakeRequestTest.php');
		return $suite;
	}
}
