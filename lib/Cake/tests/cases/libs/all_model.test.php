<?php
/**
 * AllModelTest file
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
 * AllModelTest class
 *
 * This test group will run model class tests
 *
 * @package       cake.tests.groups
 */
class AllModelTest extends PHPUnit_Framework_TestSuite {

/**
 * suite method, defines tests for this suite.
 *
 * @return void
 */
	public static function suite() {
		$suite = new PHPUnit_Framework_TestSuite('All Model related class tests');

		$suite->addTestFile(CORE_TEST_CASES . DS . 'libs' . DS . 'model' . DS . 'behavior_collection.test.php');
		$suite->addTestFile(CORE_TEST_CASES . DS . 'libs' . DS . 'model' . DS . 'model_read.test.php');
		$suite->addTestFile(CORE_TEST_CASES . DS . 'libs' . DS . 'model' . DS . 'model_write.test.php');
		$suite->addTestFile(CORE_TEST_CASES . DS . 'libs' . DS . 'model' . DS . 'model_validation.test.php');
		$suite->addTestFile(CORE_TEST_CASES . DS . 'libs' . DS . 'model' . DS . 'model_integration.test.php');
		return $suite;
	}
}