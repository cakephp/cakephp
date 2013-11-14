<?php
/**
 * HelpersGroupTest file
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
 * HelpersGroupTest class
 *
 * This test group will run all Helper related tests.
 *
 * @package       Cake.Test.Case
 */
class AllHelpersTest extends PHPUnit_Framework_TestSuite {

/**
 * suite declares tests to run
 *
 * @return void
 */
	public static function suite() {
		$suite = new CakeTestSuite('All Helper tests');

		$suite->addTestFile(CORE_TEST_CASES . DS . 'View' . DS . 'HelperTest.php');
		$suite->addTestFile(CORE_TEST_CASES . DS . 'View' . DS . 'HelperCollectionTest.php');
		$suite->addTestDirectory(CORE_TEST_CASES . DS . 'View' . DS . 'Helper' . DS);
		return $suite;
	}
}
