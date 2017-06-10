<?php
/**
 * Tree Behavior test file - runs all the tree behavior tests
 *
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @package       Cake.Test.Case.Model.Behavior
 * @since         CakePHP(tm) v 2.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

/**
 * Tree Behavior test
 *
 * A test group to run all the component parts
 *
 * @package       Cake.Test.Case.Model.Behavior
 */
class TreeBehaviorTest extends PHPUnit_Framework_TestSuite {

/**
 * suite method, defines tests for this suite.
 *
 * @return void
 */
	public static function suite() {
		$suite = new CakeTestSuite('TreeBehavior tests');

		$suite->addTestFile(CORE_TEST_CASES . DS . 'Model' . DS . 'Behavior' . DS . 'TreeBehaviorNumberTest.php');
		$suite->addTestFile(CORE_TEST_CASES . DS . 'Model' . DS . 'Behavior' . DS . 'TreeBehaviorScopedTest.php');
		$suite->addTestFile(CORE_TEST_CASES . DS . 'Model' . DS . 'Behavior' . DS . 'TreeBehaviorAfterTest.php');
		$suite->addTestFile(CORE_TEST_CASES . DS . 'Model' . DS . 'Behavior' . DS . 'TreeBehaviorUuidTest.php');
		return $suite;
	}
}
