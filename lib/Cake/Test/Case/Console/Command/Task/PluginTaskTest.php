<?php
/**
 * PluginTask Test file
 *
 * Test Case for plugin generation shell task
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
			'Console' . DS . 'Command' . DS . 'Task',
			'Console' . DS . 'Templates',
			'Controller' . DS . 'Component',
			'Lib',
			'Locale' . DS . 'eng' . DS . 'LC_MESSAGES',
			'Model' . DS . 'Behavior',
			'Model' . DS . 'Datasource',
			'Test' . DS . 'Case' . DS . 'Controller' . DS . 'Component',
			'Test' . DS . 'Case' . DS . 'Lib',
			'Test' . DS . 'Case' . DS . 'Model' . DS . 'Behavior',
			'Test' . DS . 'Case' . DS . 'Model' . DS . 'Datasource',
			'Test' . DS . 'Case' . DS . 'View' . DS . 'Helper',
			'Test' . DS . 'Fixture',
			'View' . DS . 'Elements',
			'View' . DS . 'Helper',
			'View' . DS . 'Layouts',
			'webroot' . DS . 'css',
			'webroot' . DS . 'js',
			'webroot' . DS . 'img',
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

		array_unshift($paths, '/fake/path');
		$paths[] = '/fake/path2';

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
