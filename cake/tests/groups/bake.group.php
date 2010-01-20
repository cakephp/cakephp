<?php
/**
 * Bake Group test file
 *
 * Run all the test cases related to bake.
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) Tests <https://trac.cakephp.org/wiki/Developement/TestSuite>
 * Copyright 2005-2009, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 *  Licensed under The Open Group Test Suite License
 *  Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2009, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          https://trac.cakephp.org/wiki/Developement/TestSuite CakePHP(tm) Tests
 * @package       cake
 * @subpackage    cake.tests.groups
 * @since         CakePHP(tm) v 1.3
 * @license       http://www.opensource.org/licenses/opengroup.php The Open Group Test Suite License
 */

/**
 * BakeGroupTest class
 *
 * This test group will run all bake tests
 *
 * @package       cake
 * @subpackage    cake.tests.groups
 */
class BakeGroupTest extends TestSuite {

/**
 * label property
 *
 * @var string 'All core cache engines'
 * @access public
 */
	var $label = 'All Tasks related to bake.';

/**
 * BakeGroupTest method
 *
 * @access public
 * @return void
 */
	function BakeGroupTest() {
		$path = CORE_TEST_CASES . DS . 'console' . DS . 'libs' . DS . 'tasks' . DS;
		TestManager::addTestFile($this, CORE_TEST_CASES . DS . 'console' . DS . 'libs' . DS . 'bake');
		TestManager::addTestFile($this, $path . 'controller');
		TestManager::addTestFile($this, $path . 'model');
		TestManager::addTestFile($this, $path . 'view');
		TestManager::addTestFile($this, $path . 'fixture');
		TestManager::addTestFile($this, $path . 'test');
		TestManager::addTestFile($this, $path . 'db_config');
		TestManager::addTestFile($this, $path . 'plugin');
		TestManager::addTestFile($this, $path . 'project');
	}
}
?>