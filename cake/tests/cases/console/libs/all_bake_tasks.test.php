<?php
/**
 * AllBakeTasksTest file
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
 * @package       cake
 * @subpackage    cake.tests.cases
 * @since         CakePHP(tm) v 2.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

/**
 * AllBakeTasksTest class
 *
 * This test group will run bake and its task's tests.
 *
 * @package       cake
 * @subpackage    cake.tests.groups
 */
class AllBakeTasksTest extends PHPUnit_Framework_TestSuite {

/**
 * suite method, defines tests for this suite.
 *
 * @return void
 */
	public static function suite() {
		$suite = new PHPUnit_Framework_TestSuite('Bake and its task tests');

		$path = CORE_TEST_CASES . DS . 'console' . DS . 'libs' . DS . 'tasks' . DS;

		$suite->addTestFile(CORE_TEST_CASES . DS . 'console' . DS . 'libs' . DS . 'bake.test.php');
		$tasks = array('controller', 'model', 'view', 'fixture', 'test', 'db_config', 'project', 'plugin');
		foreach ($tasks as $task) {
			$suite->addTestFile($path . $task . '.test.php');
		}
		return $suite;
	}
}

