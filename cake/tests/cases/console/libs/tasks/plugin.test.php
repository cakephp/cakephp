<?php
/* SVN FILE: $Id$ */
/**
 * PluginTask Test file
 *
 * Test Case for plugin generation shell task
 *
 * PHP versions 4 and 5
 *
 * CakePHP :  Rapid Development Framework (http://www.cakephp.org)
 * Copyright 2006-2008, Cake Software Foundation, Inc.
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @filesource
 * @copyright     Copyright 2006-2008, Cake Software Foundation, Inc.
 * @link          http://www.cakefoundation.org/projects/info/cakephp CakePHP Project
 * @package       cake
 * @subpackage    cake.tests.cases.console.libs.tasks
 * @since         CakePHP v 1.2.0.7726
 * @version       $Revision$
 * @modifiedby    $LastChangedBy$
 * @lastmodified  $Date$
 * @license       http://www.opensource.org/licenses/mit-license.php The MIT License
 */
App::import('Core', 'Shell');

if (!defined('DISABLE_AUTO_DISPATCH')) {
	define('DISABLE_AUTO_DISPATCH', true);
}

if (!class_exists('ShellDispatcher')) {
	ob_start();
	$argv = false;
	require CAKE . 'console' .  DS . 'cake.php';
	ob_end_clean();
}

if (!class_exists('PluginTask')) {
	require CAKE . 'console' .  DS . 'libs' . DS . 'tasks' . DS . 'plugin.php';
	require CAKE . 'console' .  DS . 'libs' . DS . 'tasks' . DS . 'model.php';
}

Mock::generatePartial(
	'ShellDispatcher', 'TestPluginTaskMockShellDispatcher',
	array('getInput', 'stdout', 'stderr', '_stop', '_initEnvironment')
);
Mock::generatePartial(
	'PluginTask', 'MockPluginTask',
	array('in', '_stop', 'err', 'out', 'createFile')
);

Mock::generate('ModelTask', 'PluginTestMockModelTask');

/**
 * PluginTaskPlugin class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.console.libs.tasks
 */
class PluginTaskTest extends CakeTestCase {

/**
 * setUp method
 *
 * @return void
 * @access public
 */
	function startTest() {
		$this->Dispatcher =& new TestPluginTaskMockShellDispatcher();
		$this->Dispatcher->shellPaths = Configure::read('shellPaths');
		$this->Task =& new MockPluginTask($this->Dispatcher);
		$this->Task->Dispatch =& $this->Dispatcher;
		$this->Task->path = TMP;
	}

/**
 * startCase methods
 *
 * @return void
 **/
	function startCase() {
		$this->_paths = $paths = Configure::read('pluginPaths');
		$this->_testPath = array_push($paths, TMP);
		Configure::write('pluginPaths', $paths);
	}

/**
 * endCase
 *
 * @return void
 **/
	function endCase() {
		Configure::write('pluginPaths', $this->_paths);
	}

/**
 * tearDown method
 *
 * @return void
 * @access public
 */
	function endTest() {
		ClassRegistry::flush();
	}

/**
 * test bake()
 *
 * @return void
 **/
	function testBakeFoldersAndFiles() {
		$this->Task->setReturnValueAt(0, 'in', $this->_testPath);
		$this->Task->setReturnValueAt(1, 'in', 'y');
		$this->Task->bake('BakeTestPlugin');

		$path = TMP . 'bake_test_plugin';
		$this->assertTrue(is_dir($path), 'No plugin dir %s');
		$this->assertTrue(is_dir($path . DS . 'controllers'), 'No controllers dir %s');
		$this->assertTrue(is_dir($path . DS . 'controllers' . DS .'components'), 'No components dir %s');
		$this->assertTrue(is_dir($path . DS . 'models'), 'No models dir %s');
		$this->assertTrue(is_dir($path . DS . 'views'), 'No views dir %s');
		$this->assertTrue(is_dir($path . DS . 'views' . DS . 'helpers'), 'No helpers dir %s');
		$this->assertTrue(is_dir($path . DS . 'tests'), 'No tests dir %s');
		$this->assertTrue(is_dir($path . DS . 'tests' . DS . 'cases'), 'No cases dir %s');
		$this->assertTrue(is_dir($path . DS . 'tests' . DS . 'groups'), 'No groups dir %s');
		$this->assertTrue(is_dir($path . DS . 'tests' . DS . 'fixtures'), 'No fixtures dir %s');

		$file = $path . DS . 'bake_test_plugin_app_controller.php';
		$this->Task->expectAt(0, 'createFile', array($file, '*'), 'No AppController %s');

		$file = $path . DS . 'bake_test_plugin_app_model.php';
		$this->Task->expectAt(1, 'createFile', array($file, '*'), 'No AppModel %s');

		$Folder =& new Folder(TMP . 'bake_test_plugin');
		$Folder->delete();
	}

/**
 * Test Execute
 *
 * @return void
 **/
	function testExecuteWithOneArg() {
		$this->Task->setReturnValueAt(0, 'in', $this->_testPath);
		$this->Task->setReturnValueAt(1, 'in', 'y');
		$this->Task->Dispatch->args = array('BakeTestPlugin');
		$this->Task->args =& $this->Task->Dispatch->args;

		$path = TMP . 'bake_test_plugin';
		$file = $path . DS . 'bake_test_plugin_app_controller.php';
		$this->Task->expectAt(0, 'createFile', array($file, '*'), 'No AppController %s');

		$file = $path . DS . 'bake_test_plugin_app_model.php';
		$this->Task->expectAt(1, 'createFile', array($file, '*'), 'No AppModel %s');

		$this->Task->execute();
		$Folder =& new Folder(TMP . 'bake_test_plugin');
		$Folder->delete();
	}

/**
 * test execute chaining into MVC parts
 *
 * @return void
 **/
	function testExecuteWithTwoArgs() {
		$this->Task->Model =& new PluginTestMockModelTask();
		$this->Task->setReturnValueAt(0, 'in', $this->_testPath);
		$this->Task->setReturnValueAt(1, 'in', 'y');

		$Folder =& new Folder(TMP . 'bake_test_plugin', true);

		$this->Task->Dispatch->args = array('BakeTestPlugin', 'model');
		$this->Task->args =& $this->Task->Dispatch->args;

		$this->Task->Model->expectOnce('loadTasks');
		$this->Task->Model->expectOnce('execute');
		$this->Task->execute();
		$Folder->delete();
	}
}
?>