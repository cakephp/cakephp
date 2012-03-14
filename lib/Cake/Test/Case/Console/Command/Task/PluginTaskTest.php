<?php
/**
 * PluginTask Test file
 *
 * Test Case for plugin generation shell task
 *
 * PHP 5
 *
 * CakePHP : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2012, Cake Software Foundation, Inc.
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2012, Cake Software Foundation, Inc.
 * @link          http://cakephp.org CakePHP Project
 * @package       Cake.Test.Case.Console.Command.Task
 * @since         CakePHP v 1.3.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

App::uses('ShellDispatcher', 'Console');
App::uses('ConsoleOutput', 'Console');
App::uses('ConsoleInput', 'Console');
App::uses('Shell', 'Console');
App::uses('PluginTask', 'Console/Command/Task');
App::uses('ModelTask', 'Console/Command/Task');
App::uses('Folder', 'Utility');
App::uses('File', 'Utility');

/**
 * PluginTaskPlugin class
 *
 * @package       Cake.Test.Case.Console.Command.Task
 */
class PluginTaskTest extends CakeTestCase {

/**
 * setUp method
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();
		$this->out = $this->getMock('ConsoleOutput', array(), array(), '', false);
		$this->in = $this->getMock('ConsoleInput', array(), array(), '', false);

		$this->Task = $this->getMock('PluginTask',
			array('in', 'err', 'createFile', '_stop', 'clear'),
			array($this->out, $this->out, $this->in)
		);
		$this->Task->path = TMP . 'tests' . DS;

		$this->_paths = $paths = App::path('plugins');
		foreach ($paths as $i => $p) {
			if (!is_dir($p)) {
				array_splice($paths, $i, 1);
			}
		}
		$this->_testPath = array_push($paths, TMP . 'tests' . DS);
		App::build(array('plugins' => $paths));
	}

/**
 * test bake()
 *
 * @return void
 */
	public function testBakeFoldersAndFiles() {
		$this->Task->expects($this->at(0))->method('in')->will($this->returnValue($this->_testPath));
		$this->Task->expects($this->at(1))->method('in')->will($this->returnValue('y'));

		$path = $this->Task->path . 'BakeTestPlugin';

		$file = $path . DS . 'Controller' . DS . 'BakeTestPluginAppController.php';
		$this->Task->expects($this->at(2))->method('createFile')
			->with($file, new PHPUnit_Framework_Constraint_IsAnything());

		$file = $path . DS . 'Model' . DS . 'BakeTestPluginAppModel.php';
		$this->Task->expects($this->at(3))->method('createFile')
			->with($file, new PHPUnit_Framework_Constraint_IsAnything());

		$this->Task->bake('BakeTestPlugin');

		$path = $this->Task->path . 'BakeTestPlugin';
		$this->assertTrue(is_dir($path), 'No plugin dir %s');

		$directories = array(
			'Config' . DS . 'Schema',
			'Model' . DS . 'Behavior',
			'Model' . DS . 'Datasource',
			'Console' . DS . 'Command' . DS . 'Task',
			'Controller' . DS . 'Component',
			'Lib',
			'View' . DS . 'Helper',
			'Test' . DS . 'Case' . DS . 'Controller' . DS . 'Component',
			'Test' . DS . 'Case' . DS . 'View' . DS . 'Helper',
			'Test' . DS . 'Case' . DS . 'Model' . DS . 'Behavior',
			'Test' . DS . 'Fixture',
			'Vendor',
			'webroot'
		);
		foreach ($directories as $dir) {
			$this->assertTrue(is_dir($path . DS . $dir), 'Missing directory for ' . $dir);
		}

		$Folder = new Folder($this->Task->path . 'BakeTestPlugin');
		$Folder->delete();
	}

/**
 * test execute with no args, flowing into interactive,
 *
 * @return void
 */
	public function testExecuteWithNoArgs() {
		$this->Task->expects($this->at(0))->method('in')->will($this->returnValue('TestPlugin'));
		$this->Task->expects($this->at(1))->method('in')->will($this->returnValue($this->_testPath));
		$this->Task->expects($this->at(2))->method('in')->will($this->returnValue('y'));

		$path = $this->Task->path . 'TestPlugin';
		$file = $path . DS . 'Controller' . DS . 'TestPluginAppController.php';

		$this->Task->expects($this->at(3))->method('createFile')
			->with($file, new PHPUnit_Framework_Constraint_IsAnything());

		$file = $path . DS . 'Model' . DS . 'TestPluginAppModel.php';
		$this->Task->expects($this->at(4))->method('createFile')
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

		$path = $this->Task->path . 'BakeTestPlugin';
		$file = $path . DS . 'Controller' . DS . 'BakeTestPluginAppController.php';
		$this->Task->expects($this->at(2))->method('createFile')
			->with($file, new PHPUnit_Framework_Constraint_IsAnything());

		$path = $this->Task->path . 'BakeTestPlugin';
		$file = $path . DS . 'Model' . DS . 'BakeTestPluginAppModel.php';
		$this->Task->expects($this->at(3))->method('createFile')
			->with($file, new PHPUnit_Framework_Constraint_IsAnything());

		$this->Task->args = array('BakeTestPlugin');

		$this->Task->execute();

		$Folder = new Folder($this->Task->path . 'BakeTestPlugin');
		$Folder->delete();
	}

/**
 * Test that findPath ignores paths that don't exist.
 *
 * @return void
 */
	public function testFindPathNonExistant() {
		$paths = App::path('plugins');
		$last = count($paths);
		$paths[] = '/fake/path';

		$this->Task = $this->getMock('PluginTask',
			array('in', 'out', 'err', 'createFile', '_stop'),
			array($this->out, $this->out, $this->in)
		);
		$this->Task->path = TMP . 'tests' . DS;

		// Make sure the added path is filtered out.
		$this->Task->expects($this->exactly($last))
			->method('out');

		$this->Task->expects($this->once())
			->method('in')
			->will($this->returnValue($last));

		$this->Task->findPath($paths);
	}
}
