<?php
/**
 * AllCoreJavascriptHelpersGroupTest file
 *
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) Tests <http://book.cakephp.org/view/1196/Testing>
 * Copyright 2005-2009, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 *  Licensed under The Open Group Test Suite License
 *  Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2009, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/view/1196/Testing CakePHP(tm) Tests
 * @package       cake
 * @subpackage    cake.tests.groups
 * @since         CakePHP(tm) v 1.3
 * @license       http://www.opensource.org/licenses/opengroup.php The Open Group Test Suite License
 */
/**
 * AllCoreJavascriptHelpersGroupTest class
 *
 * This test group will run all test in the cases/libs/view/helpers directory related
 * to Js helper and its engines
 *
 * @package       cake
 * @subpackage    cake.tests.groups
 */
class AllJavascriptHelpersTest extends PHPUnit_Framework_TestSuite {

/**
 * Suite define the tests for this suite
 *
 * @return void
 */
	public static function suite() {
		$suite = new PHPUnit_Framework_TestSuite('Js Helper and all Engine Helpers');

		$helperTestPath = CORE_TEST_CASES . DS . 'libs' . DS . 'view' . DS . 'helpers' . DS;
		$suite->addTestFile($helperTestPath . 'js.test.php');
		$suite->addTestFile($helperTestPath . 'jquery_engine.test.php');
		$suite->addTestFile($helperTestPath . 'mootools_engine.test.php');
		$suite->addTestFile($helperTestPath . 'prototype_engine.test.php');
		return $suite;
	}
}
