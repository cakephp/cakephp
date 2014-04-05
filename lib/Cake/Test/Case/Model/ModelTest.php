<?php
/**
 * ModelTest file
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
 * ModelTest class
 *
 * This test group will run model class tests
 *
 * @package       Cake.Test.Case
 */
class ModelTest extends PHPUnit_Framework_TestSuite {

/**
 * suite method, defines tests for this suite.
 *
 * @return void
 */
	public static function suite() {
		$suite = new PHPUnit_Framework_TestSuite('All Model related class tests');

		$suite->addTestFile(CORE_TEST_CASES . DS . 'Model' . DS . 'Validator' . DS . 'CakeValidationSetTest.php');
		$suite->addTestFile(CORE_TEST_CASES . DS . 'Model' . DS . 'Validator' . DS . 'CakeValidationRuleTest.php');
		$suite->addTestFile(CORE_TEST_CASES . DS . 'Model' . DS . 'ModelReadTest.php');
		$suite->addTestFile(CORE_TEST_CASES . DS . 'Model' . DS . 'ModelWriteTest.php');
		$suite->addTestFile(CORE_TEST_CASES . DS . 'Model' . DS . 'ModelDeleteTest.php');
		$suite->addTestFile(CORE_TEST_CASES . DS . 'Model' . DS . 'ModelValidationTest.php');
		$suite->addTestFile(CORE_TEST_CASES . DS . 'Model' . DS . 'ModelIntegrationTest.php');
		$suite->addTestFile(CORE_TEST_CASES . DS . 'Model' . DS . 'ModelCrossSchemaHabtmTest.php');
		return $suite;
	}
}
