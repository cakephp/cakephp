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
namespace Cake\Test\TestCase\Console\Command\Task;

use Cake\Console\Command\Task\DbConfigTask;
use Cake\Core\App;
use Cake\Core\Plugin;
use Cake\TestSuite\TestCase;
use Cake\Utility\Folder;

/**
 * PluginTaskPlugin class
 *
 * @package       Cake.Test.Case.Console.Command.Task
 */
class PluginTaskTest extends TestCase {

/**
 * setUp method
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();
		$this->out = $this->getMock('Cake\Console\ConsoleOutput', array(), array(), '', false);
		$this->in = $this->getMock('Cake\Console\ConsoleInput', array(), array(), '', false);

		$this->Task = $this->getMock('Cake\Console\Command\Task\PluginTask',
			array('in', 'err', 'createFile', '_stop', 'clear'),
			array($this->out, $this->out, $this->in)
		);
		$this->Task->path = TMP . 'tests/';
		$this->Task->bootstrap = TMP . 'tests/bootstrap.php';
		touch($this->Task->bootstrap);

		$this->_paths = $paths = App::path('Plugin');
		foreach ($paths as $i => $p) {
			if (!is_dir($p)) {
				array_splice($paths, $i, 1);
			}
		}
		$this->_testPath = array_push($paths, TMP . 'tests/');
		App::build(array('Plugin' => $paths));
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

		$file = $path . DS . 'Controller/BakeTestPluginAppController.php';
		$this->Task->expects($this->at(2))->method('createFile')
			->with($file, new \PHPUnit_Framework_Constraint_IsAnything());

		$file = $path . DS . 'Model/BakeTestPluginAppModel.php';
		$this->Task->expects($this->at(3))->method('createFile')
			->with($file, new \PHPUnit_Framework_Constraint_IsAnything());

		$this->Task->bake('BakeTestPlugin');

		$path = $this->Task->path . 'BakeTestPlugin';
		$this->assertTrue(is_dir($path), 'No plugin dir %s');

		$directories = array(
			'Config/Schema',
			'Model/Behavior',
			'Model/Datasource',
			'Console/Command/Task',
			'Controller/Component',
			'Lib',
			'View/Helper',
			'Test/Case/Controller/Component',
			'Test/Case/View/Helper',
			'Test/Case/Model/Behavior',
			'Test/Fixture',
			'vendor',
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
		$file = $path . DS . 'Controller/TestPluginAppController.php';

		$this->Task->expects($this->at(3))->method('createFile')
			->with($file, new \PHPUnit_Framework_Constraint_IsAnything());

		$file = $path . DS . 'Model/TestPluginAppModel.php';
		$this->Task->expects($this->at(4))->method('createFile')
			->with($file, new \PHPUnit_Framework_Constraint_IsAnything());

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
		$file = $path . DS . 'Controller/BakeTestPluginAppController.php';
		$this->Task->expects($this->at(2))->method('createFile')
			->with($file, new \PHPUnit_Framework_Constraint_IsAnything());

		$path = $this->Task->path . 'BakeTestPlugin';
		$file = $path . DS . 'Model/BakeTestPluginAppModel.php';
		$this->Task->expects($this->at(3))->method('createFile')
			->with($file, new \PHPUnit_Framework_Constraint_IsAnything());

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
		$paths = App::path('Plugin');
		$last = count($paths);
		$paths[] = '/fake/path';

		$this->Task = $this->getMock('Cake\Console\Command\Task\PluginTask',
			array('in', 'out', 'err', 'createFile', '_stop'),
			array($this->out, $this->out, $this->in)
		);
		$this->Task->path = TMP . 'tests/';

		// Make sure the added path is filtered out.
		$this->Task->expects($this->exactly($last))
			->method('out');

		$this->Task->expects($this->once())
			->method('in')
			->will($this->returnValue($last));

		$this->Task->findPath($paths);
	}
}
