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
		$suite = new PHPUnit_Framework_TestSuite('All non-MVC lib class tests');
		
		$suite->addTestFile(CORE_TEST_CASES . DS . 'basics.test.php');
		$suite->addTestFile(CORE_TEST_CASES . DS . 'libs' . DS . 'cake_session.test.php');
		$suite->addTestFile(CORE_TEST_CASES . DS . 'libs' . DS . 'debugger.test.php');
		$suite->addTestFile(CORE_TEST_CASES . DS . 'libs' . DS . 'file.test.php');
		$suite->addTestFile(CORE_TEST_CASES . DS . 'libs' . DS . 'folder.test.php');
		$suite->addTestFile(CORE_TEST_CASES . DS . 'libs' . DS . 'inflector.test.php');
		$suite->addTestFile(CORE_TEST_CASES . DS . 'libs' . DS . 'log' . DS . 'file_log.test.php');
		$suite->addTestFile(CORE_TEST_CASES . DS . 'libs' . DS . 'cake_log.test.php');
		$suite->addTestFile(CORE_TEST_CASES . DS . 'libs' . DS . 'class_registry.test.php');
		$suite->addTestFile(CORE_TEST_CASES . DS . 'libs' . DS . 'sanitize.test.php');
		$suite->addTestFile(CORE_TEST_CASES . DS . 'libs' . DS . 'set.test.php');
		$suite->addTestFile(CORE_TEST_CASES . DS . 'libs' . DS . 'string.test.php');
		$suite->addTestFile(CORE_TEST_CASES . DS . 'libs' . DS . 'validation.test.php');
		$suite->addTestFile(CORE_TEST_CASES . DS . 'libs' . DS . 'object_collection.test.php');
		return $suite;
	}
}