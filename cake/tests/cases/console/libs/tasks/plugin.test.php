<?php
/**
 * PluginTask Test file
 *
 * Test Case for plugin generation shell task
 *
 * PHP versions 4 and 5
 *
 * CakePHP : Rapid Development Framework (http://cakephp.org)
 * Copyright 2006-2009, Cake Software Foundation, Inc.
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2006-2009, Cake Software Foundation, Inc.
 * @link          http://cakephp.org CakePHP Project
 * @package       cake
 * @subpackage    cake.tests.cases.console.libs.tasks
 * @since         CakePHP v 1.3.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
App::import('Shell', 'Shell', false);

if (!defined('DISABLE_AUTO_DISPATCH')) {
	define('DISABLE_AUTO_DISPATCH', true);
}

if (!class_exists('ShellDispatcher')) {
	ob_start();
	$argv = false;
	require CAKE . 'console' .  DS . 'cake.php';
	ob_end_clean();
}

require_once CAKE . 'console' .  DS . 'libs' . DS . 'tasks' . DS . 'plugin.php';
require_once CAKE . 'console' .  DS . 'libs' . DS . 'tasks' . DS . 'model.php';

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
 * startTest method
 *
 * @return void
 * @access public
 */
	function startTest() {
		$this->Dispatcher =& new TestPluginTaskMockShellDispatcher();
		$this->Dispatcher->shellPaths = App::path('shells');
		$this->Task =& new MockPluginTask($this->Dispatcher);
		$this->Task->Dispatch =& $this->Dispatcher;
		$this->Task->path = TMP . 'tests' . DS;
	}

/**
 * startCase methods
 *
 * @return void
 * @access public
 */
	function startCase() {
		$this->_paths = $paths = App::path('plugins');
		$this->_testPath = array_push($paths, TMP . 'tests' . DS);
		App::build(array('plugins' => $paths));
	}

/**
 * endCase
 *
 * @return void
 * @access public
 */
	function endCase() {
		App::build(array('plugins' => $this->_paths));
	}

/**
 * endTest method
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
 * @access public
 */
	function testBakeFoldersAndFiles() {
		$this->Task->setReturnValueAt(0, 'in', $this->_testPath);
		$this->Task->setReturnValueAt(1, 'in', 'y');
		$this->Task->bake('BakeTestPlugin');

		$path = $this->Task->path . 'bake_test_plugin';
		$this->assertTrue(is_dir($path), 'No plugin dir %s');

		$this->assertTrue(is_dir($path . DS . 'config'), 'No config dir %s');
		$this->assertTrue(is_dir($path . DS . 'config' . DS . 'schema'), 'No schema dir %s');
		$this->assertTrue(file_exists($path . DS . 'config' . DS . 'schema' . DS . 'empty'), 'No empty file %s');

		$this->assertTrue(is_dir($path . DS . 'controllers'), 'No controllers dir %s');
		$this->assertTrue(is_dir($path . DS . 'controllers' . DS .'components'), 'No components dir %s');
		$this->assertTrue(file_exists($path . DS . 'controllers' . DS . 'components' . DS . 'empty'), 'No empty file %s');

		$this->assertTrue(is_dir($path . DS . 'models'), 'No models dir %s');
		$this->assertTrue(file_exists($path . DS . 'models' . DS . 'behaviors' . DS . 'empty'), 'No empty file %s');
		$this->assertTrue(is_dir($path . DS . 'models' . DS . 'datasources'), 'No datasources dir %s');
		$this->assertTrue(file_exists($path . DS . 'models' . DS . 'datasources' . DS . 'empty'), 'No empty file %s');

		$this->assertTrue(is_dir($path . DS . 'views'), 'No views dir %s');
		$this->assertTrue(is_dir($path . DS . 'views' . DS . 'helpers'), 'No helpers dir %s');
		$this->assertTrue(file_exists($path . DS . 'views' . DS . 'helpers' . DS . 'empty'), 'No empty file %s');

		$this->assertTrue(is_dir($path . DS . 'tests'), 'No tests dir %s');
		$this->assertTrue(is_dir($path . DS . 'tests' . DS . 'cases'), 'No cases dir %s');

		$this->assertTrue(
			is_dir($path . DS . 'tests' . DS . 'cases' . DS . 'components'), 'No components cases dir %s'
		);
		$this->assertTrue(
			file_exists($path . DS . 'tests' . DS . 'cases' . DS . 'components' . DS . 'empty'), 'No empty file %s'
		);

		$this->assertTrue(is_dir($path . DS . 'tests' . DS . 'cases' . DS . 'behaviors'), 'No behaviors cases dir %s');
		$this->assertTrue(
			file_exists($path . DS . 'tests' . DS . 'cases' . DS . 'behaviors' . DS . 'empty'), 'No empty file %s'
		);

		$this->assertTrue(is_dir($path . DS . 'tests' . DS . 'cases' . DS . 'helpers'), 'No helpers cases dir %s');
		$this->assertTrue(
			file_exists($path . DS . 'tests' . DS . 'cases' . DS . 'helpers' . DS . 'empty'), 'No empty file %s'
		);

		$this->assertTrue(is_dir($path . DS . 'tests' . DS . 'cases' . DS . 'models'), 'No models cases dir %s');
		$this->assertTrue(
			file_exists($path . DS . 'tests' . DS . 'cases' . DS . 'models' . DS . 'empty'), 'No empty file %s'
		);

		$this->assertTrue(
			is_dir($path . DS . 'tests' . DS . 'cases' . DS . 'controllers'),
			'No controllers cases dir %s'
		);
		$this->assertTrue(
			file_exists($path . DS . 'tests' . DS . 'cases' . DS . 'controllers' . DS . 'empty'), 'No empty file %s'
		);

		$this->assertTrue(is_dir($path . DS . 'tests' . DS . 'groups'), 'No groups dir %s');
		$this->assertTrue(file_exists($path . DS . 'tests' . DS . 'groups' . DS . 'empty'), 'No empty file %s');

		$this->assertTrue(is_dir($path . DS . 'tests' . DS . 'fixtures'), 'No fixtures dir %s');
		$this->assertTrue(file_exists($path . DS . 'tests' . DS . 'fixtures' . DS . 'empty'), 'No empty file %s');

		$this->assertTrue(is_dir($path . DS . 'vendors'), 'No vendors dir %s');
	
		$this->assertTrue(is_dir($path . DS . 'vendors' . DS . 'shells'), 'No vendors shells dir %s');
		$this->assertTrue(is_dir($path . DS . 'vendors' . DS . 'shells' . DS . 'tasks'), 'No vendors shells tasks dir %s');
		$this->assertTrue(file_exists($path . DS . 'vendors' . DS . 'shells' . DS . 'tasks' . DS . 'empty'), 'No empty file %s');
		$this->assertTrue(is_dir($path . DS . 'libs'), 'No libs dir %s');
		$this->assertTrue(is_dir($path . DS . 'webroot'), 'No webroot dir %s');

		$file = $path . DS . 'bake_test_plugin_app_controller.php';
		$this->Task->expectAt(0, 'createFile', array($file, '*'), 'No AppController %s');

		$file = $path . DS . 'bake_test_plugin_app_model.php';
		$this->Task->expectAt(1, 'createFile', array($file, '*'), 'No AppModel %s');

		$Folder =& new Folder($this->Task->path . 'bake_test_plugin');
		$Folder->delete();
	}

/**
 * test execute with no args, flowing into interactive,
 *
 * @return void
 * @access public
 */
	function testExecuteWithNoArgs() {
		$this->Task->setReturnValueAt(0, 'in', 'TestPlugin');
		$this->Task->setReturnValueAt(1, 'in', '3');
		$this->Task->setReturnValueAt(2, 'in', 'y');
		$this->Task->setReturnValueAt(3, 'in', 'n');

		$path = $this->Task->path . 'test_plugin';
		$file = $path . DS . 'test_plugin_app_controller.php';
		$this->Task->expectAt(0, 'createFile', array($file, '*'), 'No AppController %s');

		$file = $path . DS . 'test_plugin_app_model.php';
		$this->Task->expectAt(1, 'createFile', array($file, '*'), 'No AppModel %s');

		$this->Task->args = array();
		$this->Task->execute();

		$Folder =& new Folder($path);
		$Folder->delete();
	}

/**
 * Test Execute
 *
 * @return void
 * @access public
 */
	function testExecuteWithOneArg() {
		$this->Task->setReturnValueAt(0, 'in', $this->_testPath);
		$this->Task->setReturnValueAt(1, 'in', 'y');
		$this->Task->Dispatch->args = array('BakeTestPlugin');
		$this->Task->args =& $this->Task->Dispatch->args;

		$path = $this->Task->path . 'bake_test_plugin';
		$file = $path . DS . 'bake_test_plugin_app_controller.php';
		$this->Task->expectAt(0, 'createFile', array($file, '*'), 'No AppController %s');

		$file = $path . DS . 'bake_test_plugin_app_model.php';
		$this->Task->expectAt(1, 'createFile', array($file, '*'), 'No AppModel %s');

		$this->Task->execute();

		$Folder =& new Folder($this->Task->path . 'bake_test_plugin');
		$Folder->delete();
	}

/**
 * test execute chaining into MVC parts
 *
 * @return void
 * @access public
 */
	function testExecuteWithTwoArgs() {
		$this->Task->Model =& new PluginTestMockModelTask();
		$this->Task->setReturnValueAt(0, 'in', $this->_testPath);
		$this->Task->setReturnValueAt(1, 'in', 'y');

		$Folder =& new Folder($this->Task->path . 'bake_test_plugin', true);

		$this->Task->Dispatch->args = array('BakeTestPlugin', 'model');
		$this->Task->args =& $this->Task->Dispatch->args;

		$this->Task->Model->expectOnce('loadTasks');
		$this->Task->Model->expectOnce('execute');
		$this->Task->execute();
		$Folder->delete();
	}
}
?>