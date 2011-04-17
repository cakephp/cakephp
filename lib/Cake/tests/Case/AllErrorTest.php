<?php
/**
 * AllErrorTest file
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
 * AllErrorTest class
 *
 * This test group will run error handling related tests.
 *
 * @package       cake.tests.groups
 */
class AllErrorTest extends PHPUnit_Framework_TestSuite {

/**
 * suite method, defines tests for this suite.
 *
 * @return void
 */
	public static function suite() {
		$suite = new PHPUnit_Framework_TestSuite('All Error handling tests');

		$libs = CORE_TEST_CASES . DS;

		$suite->addTestFile($libs . 'Error' . DS . 'ErrorHandlerTest.php');
		$suite->addTestFile($libs . 'Error' . DS . 'ExceptionRendererTest.php');
		return $suite;
	}
}
