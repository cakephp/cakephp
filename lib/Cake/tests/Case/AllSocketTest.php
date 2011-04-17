<?php
/**
 * AllSocketTest file
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
 * AllSocketTest class
 *
 * This test group will run socket class tests
 *
 * @package       cake.tests.groups
 */
class AllSocketTest extends PHPUnit_Framework_TestSuite {

/**
 * suite method, defines tests for this suite.
 *
 * @return void
 */
	public static function suite() {
		$suite = new CakeTestSuite('All Socket related class tests');

		$suite->addTestFile(CORE_TEST_CASES . DS . 'Network' . DS . 'CakeSocketTest.php');
		$suite->addTestDirectory(CORE_TEST_CASES . DS . 'Network' . DS . 'Http');
		return $suite;
	}
}