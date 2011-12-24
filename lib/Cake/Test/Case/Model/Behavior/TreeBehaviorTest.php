<?php
/**
 * Tree Behavior test file - runs all the tree behavior tests
 *
 * PHP 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2011, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2011, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       Cake.Test.Case.Model.Behavior
 * @since         CakePHP(tm) v 2.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
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
