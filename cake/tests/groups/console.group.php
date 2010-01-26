<?php
/**
 * ConsoleGroupTest file
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) Tests <https://trac.cakephp.org/wiki/Developement/TestSuite>
 * Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 *  Licensed under The Open Group Test Suite License
 *  Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          https://trac.cakephp.org/wiki/Developement/TestSuite CakePHP(tm) Tests
 * @package       cake
 * @subpackage    cake.tests.groups
 * @since         CakePHP(tm) v 1.2.0.4206
 * @license       http://www.opensource.org/licenses/opengroup.php The Open Group Test Suite License
 */

/**
 * ConsoleGroupTest class
 *
 * This test group will run all console tests
 *
 * @package       cake
 * @subpackage    cake.tests.groups
 */
class ConsoleGroupTest extends TestSuite {

/**
 * label property
 *
 * @var string 'All core cache engines'
 * @access public
 */
	var $label = 'ShellDispatcher, Shell and all Tasks';

/**
 * ConsoleGroupTest method
 *
 * @access public
 * @return void
 */
	function ConsoleGroupTest() {
		TestManager::addTestFile($this, CORE_TEST_CASES . DS . 'console' . DS . 'cake');
		TestManager::addTestFile($this, CORE_TEST_CASES . DS . 'console' . DS . 'libs' . DS . 'acl');
		TestManager::addTestFile($this, CORE_TEST_CASES . DS . 'console' . DS . 'libs' . DS . 'api');
		TestManager::addTestFile($this, CORE_TEST_CASES . DS . 'console' . DS . 'libs' . DS . 'bake');
		TestManager::addTestFile($this, CORE_TEST_CASES . DS . 'console' . DS . 'libs' . DS . 'schema');
		TestManager::addTestFile($this, CORE_TEST_CASES . DS . 'console' . DS . 'libs' . DS . 'shell');

		$path = CORE_TEST_CASES . DS . 'console' . DS . 'libs' . DS . 'tasks' . DS;
		
		TestManager::addTestFile($this, $path . 'controller');
		TestManager::addTestFile($this, $path . 'db_config');
		TestManager::addTestFile($this, $path . 'extract');
		TestManager::addTestFile($this, $path . 'fixture');
		TestManager::addTestFile($this, $path . 'model');
		TestManager::addTestFile($this, $path . 'plugin');
		TestManager::addTestFile($this, $path . 'project');
		TestManager::addTestFile($this, $path . 'test');
		TestManager::addTestFile($this, $path . 'view');
	}
}
?>