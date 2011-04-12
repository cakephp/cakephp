<?php
/**
 * AllJavascriptHelpersTest file
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
 * AllJavascriptHelpersTest class
 *
 * This test group will run all test in the cases/libs/view/helpers directory related
 * to Js helper and its engines
 *
 * @package       cake.tests.groups
 */
class AllJavascriptHelpersTest extends PHPUnit_Framework_TestSuite {

/**
 * Suite define the tests for this suite
 *
 * @return void
 */
	public static function suite() {
		$suite = new PHPUnit_Framework_TestSuite('Js Helper and all Engine Helpers');

		$helperTestPath = CORE_TEST_CASES . DS .  'View' . DS . 'Helper' . DS;
		$suite->addTestFile($helperTestPath . 'JsHelperTest.php');
		$suite->addTestFile($helperTestPath . 'JqueryEngineHelperTest.php');
		$suite->addTestFile($helperTestPath . 'MootoolsEngineHelperTest.php');
		$suite->addTestFile($helperTestPath . 'PrototypeEngineHelperTest.php');
		return $suite;
	}
}
