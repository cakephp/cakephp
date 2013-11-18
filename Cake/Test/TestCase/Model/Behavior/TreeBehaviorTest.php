<?php
/**
 * Tree Behavior test file - runs all the tree behavior tests
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
 * @since         CakePHP(tm) v 2.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Model\Behavior;

use Cake\Model\Model;
use Cake\TestSuite\TestCase;
use Cake\TestSuite\TestSuite;

/**
 * Tree Behavior test
 *
 * A test group to run all the component parts
 *
 */
class TreeBehaviorTest extends \PHPUnit_Framework_TestSuite {

/**
 * suite method, defines tests for this suite.
 *
 * @return void
 */
	public static function suite() {
		$suite = new TestSuite('TreeBehavior tests');

		$suite->addTestFile(CORE_TEST_CASES . DS . 'Model/Behavior/TreeBehaviorNumberTest.php');
		$suite->addTestFile(CORE_TEST_CASES . DS . 'Model/Behavior/TreeBehaviorScopedTest.php');
		$suite->addTestFile(CORE_TEST_CASES . DS . 'Model/Behavior/TreeBehaviorAfterTest.php');
		$suite->addTestFile(CORE_TEST_CASES . DS . 'Model/Behavior/TreeBehaviorUuidTest.php');
		return $suite;
	}
}
