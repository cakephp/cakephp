<?php
/**
 * AllBehaviorsTest file
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
 * AllBehaviorsTest class
 *
 * This test group will run all test in the cases/libs/models/behaviors directory
 *
 * @package       cake.tests.groups
 */
class AllBehaviorsTest extends PHPUnit_Framework_TestSuite {

/**
 * Suite define the tests for this suite
 *
 * @return void
 */
	public static function suite() {
		$suite = new PHPUnit_Framework_TestSuite('Model Behavior and all behaviors');

		$path = CORE_TEST_CASES . DS . 'libs' . DS . 'model' . DS . 'behaviors' . DS;
		$suite->addTestFile(CORE_TEST_CASES . DS . 'libs' . DS . 'model' . DS . 'behavior_collection.test.php');

		$suite->addTestFile($path . 'acl.test.php');
		$suite->addTestFile($path . 'containable.test.php');
		$suite->addTestFile($path . 'translate.test.php');
		$suite->addTestFile($path . 'tree.test.php');
		return $suite;
	}
}
