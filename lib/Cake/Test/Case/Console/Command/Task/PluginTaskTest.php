<?php
/**
 * PluginTask Test file
 *
 * Test Case for plugin generation shell task
 *
 * PHP 5
 *
 * CakePHP : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP Project
 * @package       Cake.Test.Case.Console.Command.Task
 * @since         CakePHP v 1.3.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
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
		$this->Task->bootstrap = TMP . 'tests' . DS . 'bootstrap.php';
		touch($this->Task->bootstrap);

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
 * tearDown()
 *
 * @return void
 */
	public function tearDown() {
		if (file_exists($this->Task->bootstrap)) {
			unlink($this->Task->bootstrap);
		}
		parent::tearDown();
	}

/**
 * test bake() method and directory creation.
 *
 * @return void
 */
	public function testBake() {
		$i = 0;

		$this->Task
			->expects($this->at($i++))
			->method('in')
			->will($this->returnValue($this->_testPath));

		$sourcePath = CAKE . 'Console' . DS . 'Templates' . DS . 'plugin';
		$this->Task
			->expects($this->at($i++))
			->method('in')
			->will($this->returnValue($sourcePath));

		$this->Task
			->expects($this->at($i++))
			->method('in')
			->will($this->returnValue('y'));

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

		$files = array(
			'Controller' . DS . 'BakeTestPluginAppController.php',
			'Model' . DS . 'BakeTestPluginAppModel.php'
		);
		foreach ($files as $file) {
			$this->assertTrue(is_file($path . DS . $file), 'Missing file for ' . $file);
		}

		$Folder = new Folder($this->Task->path . 'BakeTestPlugin');
		$Folder->delete();
	}

/**
 * test bake() method with -empty flag,  directory creation and empty files.
 *
 * @return void
 */
	public function testBakeEmptyFlag() {
		$i = 0;

		$this->Task
			->expects($this->at($i++))
			->method('in')
			->will($this->returnValue($this->_testPath));

		$sourcePath = CAKE . 'Console' . DS . 'Templates' . DS . 'plugin';
		$this->Task
			->expects($this->at($i++))
			->method('in')
			->will($this->returnValue($sourcePath));

		$this->Task
			->expects($this->at($i++))
			->method('in')
			->will($this->returnValue('y'));

		$this->Task->params['empty'] = true;
		$this->Task->bake('BakeTestPlugin');

		$path = $this->Task->path . 'BakeTestPlugin';
		$this->assertTrue(is_dir($path), 'No plugin dir %s');


		$empty = array(
			'Config' . DS . 'Schema' . DS . 'empty',
			'Model' . DS . 'Behavior' . DS . 'empty',
			'Model' . DS . 'Datasource' . DS . 'empty',
			'Console' . DS . 'Command' . DS . 'Task' . DS . 'empty',
			'Controller' . DS . 'Component' . DS . 'empty',
			'Lib' . DS . 'empty',
			'View' . DS . 'Helper' . DS . 'empty',
			'Test' . DS . 'Case' . DS . 'Controller' . DS . 'Component' . DS . 'empty',
			'Test' . DS . 'Case' . DS . 'View' . DS . 'Helper' . DS . 'empty',
			'Test' . DS . 'Case' . DS . 'Model' . DS . 'Behavior' . DS . 'empty',
			'Test' . DS . 'Fixture' . DS . 'empty',
			'Vendor' . DS . 'empty',
			'webroot' . DS . 'js' . DS . 'empty',
			'webroot' . DS . 'files' . DS .  'empty'
		);

		foreach ($empty as $file) {
			$file = $path . DS . $file;
			$this->assertTrue(is_file($file), sprintf('Missing file: %s', $file));
		}
	}

/**
 * test execute with no args, flowing into interactive,
 *
 * @return void
 */
	public function testExecuteWithNoArgs() {
		$i = 0;

		$this->Task
			->expects($this->at($i++))
			->method('in')
			->will($this->returnValue('TestPlugin'));

		$this->Task
			->expects($this->at($i++))
			->method('in')
			->will($this->returnValue($this->_testPath));

		$sourcePath = CAKE . 'Console' . DS . 'Templates' . DS . 'plugin';
		$this->Task
			->expects($this->at($i++))
			->method('in')
			->will($this->returnValue($sourcePath));

		$this->Task
			->expects($this->at($i++))
			->method('in')
			->will($this->returnValue('y'));

		$this->Task->args = array();
		$this->Task->execute();

		$path = $this->Task->path . 'TestPlugin';
		$Folder = new Folder($path);
		$Folder->delete();
	}

/**
 * Test Execute
 *
 * @return void
 */
	public function testExecuteWithOneArg() {
		$i = 0;

		$this->Task
			->expects($this->at($i++))
			->method('in')
			->will($this->returnValue($this->_testPath));

		$sourcePath = CAKE . 'Console' . DS . 'Templates' . DS . 'plugin';
		$this->Task
			->expects($this->at($i++))
			->method('in')
			->will($this->returnValue($sourcePath));

		$this->Task
			->expects($this->at($i++))
			->method('in')
			->will($this->returnValue('y'));

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
