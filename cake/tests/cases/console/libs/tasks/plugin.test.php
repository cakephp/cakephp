<?php
/**
 * PluginTask Test file
 *
 * Test Case for plugin generation shell task
 *
 * PHP 5
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
App::import('Core', array('File'));

require_once CAKE . 'console' .  DS . 'shell_dispatcher.php';
require_once CAKE . 'console' .  DS . 'libs' . DS . 'tasks' . DS . 'plugin.php';
require_once CAKE . 'console' .  DS . 'libs' . DS . 'tasks' . DS . 'model.php';

/**
 * PluginTaskPlugin class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.console.libs.tasks
 */
class PluginTaskTest extends CakeTestCase {

/**
 * setup method
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();
		$out = $this->getMock('ConsoleOutput', array(), array(), '', false);
		$in = $this->getMock('ConsoleInput', array(), array(), '', false);

		$this->Dispatcher = $this->getMock('ShellDispatcher', array('_stop', '_initEnvironment'));
		$this->Task = $this->getMock('PluginTask', 
			array('in', 'err', 'createFile', '_stop', 'clear'),
			array(&$this->Dispatcher, $out, $out, $in)
		);
		$this->Task->path = TMP . 'tests' . DS;
		
		$this->_paths = $paths = App::path('plugins');
		$this->_testPath = array_push($paths, TMP . 'tests' . DS);
		App::build(array('plugins' => $paths));
	}

/**
 * teardown
 *
 * @return void
 */
	public function tearDown() {
		parent::tearDown();
		App::build(array('plugins' => $this->_paths));
	}

/**
 * test bake()
 *
 * @return void
 */
	public function testBakeFoldersAndFiles() {
		$this->Task->expects($this->at(0))->method('in')->will($this->returnValue($this->_testPath));
		$this->Task->expects($this->at(1))->method('in')->will($this->returnValue('y'));

		$path = $this->Task->path . 'bake_test_plugin';

		$file = $path . DS . 'bake_test_plugin_app_controller.php';
		$this->Task->expects($this->at(3))->method('createFile')
			->with($file, new PHPUnit_Framework_Constraint_IsAnything());

		$file = $path . DS . 'bake_test_plugin_app_model.php';
		$this->Task->expects($this->at(4))->method('createFile')
			->with($file, new PHPUnit_Framework_Constraint_IsAnything());

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

		$Folder = new Folder($this->Task->path . 'bake_test_plugin');
		$Folder->delete();
	}

/**
 * test execute with no args, flowing into interactive,
 *
 * @return void
 */
	public function testExecuteWithNoArgs() {
		$this->Task->expects($this->at(0))->method('in')->will($this->returnValue('TestPlugin'));
		$this->Task->expects($this->at(1))->method('in')->will($this->returnValue('3'));
		$this->Task->expects($this->at(2))->method('in')->will($this->returnValue('y'));
		$this->Task->expects($this->at(3))->method('in')->will($this->returnValue('n'));

		$path = $this->Task->path . 'test_plugin';
		$file = $path . DS . 'test_plugin_app_controller.php';
		$this->Task->expects($this->at(4))->method('createFile')
			->with($file, new PHPUnit_Framework_Constraint_IsAnything());

		$file = $path . DS . 'test_plugin_app_model.php';
		$this->Task->expects($this->at(5))->method('createFile')
			->with($file, new PHPUnit_Framework_Constraint_IsAnything());

		$this->Task->args = array();
		$this->Task->execute();

		$Folder = new Folder($path);
		$Folder->delete();
	}

/**
 * Test Execute
 *
 * @return void
 */
	public function testExecuteWithOneArg() {
		$this->Task->expects($this->at(0))->method('in')
			->will($this->returnValue($this->_testPath));
		$this->Task->expects($this->at(1))->method('in')
			->will($this->returnValue('y'));

		$path = $this->Task->path . 'bake_test_plugin';
		$file = $path . DS . 'bake_test_plugin_app_controller.php';
		$this->Task->expects($this->at(3))->method('createFile')
			->with($file, new PHPUnit_Framework_Constraint_IsAnything());

		$path = $this->Task->path . 'bake_test_plugin';
		$file = $path . DS . 'bake_test_plugin_app_model.php';
		$this->Task->expects($this->at(4))->method('createFile')
			->with($file, new PHPUnit_Framework_Constraint_IsAnything());
		
		$this->Task->Dispatch->args = array('BakeTestPlugin');
		$this->Task->args =& $this->Task->Dispatch->args;

		$this->Task->execute();

		$Folder = new Folder($this->Task->path . 'bake_test_plugin');
		$Folder->delete();
	}

/**
 * test execute chaining into MVC parts
 *
 * @return void
 */
	public function testExecuteWithTwoArgs() {
		$out = $this->getMock('ConsoleOutput', array(), array(), '', false);
		$in = $this->getMock('ConsoleInput', array(), array(), '', false);

		$this->Task->Model = $this->getMock('ModelTask', array(), array(&$this->Dispatcher, $out, $out, $in));

		$this->Task->expects($this->at(0))->method('in')->will($this->returnValue($this->_testPath));

		$this->Task->Model->expects($this->once())->method('loadTasks');
		$this->Task->Model->expects($this->once())->method('execute');

		$Folder = new Folder($this->Task->path . 'bake_test_plugin', true);

		$this->Task->Dispatch->args = array('BakeTestPlugin', 'model');
		$this->Task->args = $this->Task->Dispatch->args;

		$this->Task->execute();
		$Folder->delete();
	}
}
