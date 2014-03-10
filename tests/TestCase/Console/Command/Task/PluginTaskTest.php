<?php
/**
 * CakePHP : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP Project
 * @since         CakePHP v 1.3.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Console\Command\Task;

use Cake\Console\Command\Task\DbConfigTask;
use Cake\Console\Command\Task\TemplateTask;
use Cake\Core\App;
use Cake\Core\Configure;
use Cake\Core\Plugin;
use Cake\TestSuite\TestCase;
use Cake\Utility\Folder;

/**
 * PluginTaskPlugin class
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
		$this->Task->Template = new TemplateTask($this->out, $this->out, $this->in);

		$this->Task->path = TMP . 'tests/';
		$this->Task->bootstrap = TMP . 'tests/bootstrap.php';

		if (!is_dir($this->Task->path)) {
			mkdir($this->Task->path);
		}
		touch($this->Task->bootstrap);

		$this->_path = App::path('Plugin');
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
		$this->Task->expects($this->at(0))->method('in')->will($this->returnValue('y'));

		$path = $this->Task->path . 'BakeTestPlugin';

		$file = $path . '/Controller/BakeTestPluginAppController.php';
		$this->Task->expects($this->at(1))->method('createFile')
			->with($file, new \PHPUnit_Framework_Constraint_IsAnything());

		$this->Task->bake('BakeTestPlugin');

		$path = $this->Task->path . 'BakeTestPlugin';
		$this->assertTrue(is_dir($path), 'No plugin dir');

		$directories = array(
			'Config/Schema',
			'Model/Behavior',
			'Model/Table',
			'Model/Entity',
			'Console/Command/Task',
			'Controller/Component',
			'Lib',
			'View/Helper',
			'Test/TestCase/Controller/Component',
			'Test/TestCase/View/Helper',
			'Test/TestCase/Model/Behavior',
			'Test/Fixture',
			'Template',
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
		$this->Task->expects($this->at(0))
			->method('in')
			->will($this->returnValue('TestPlugin'));

		$this->Task->expects($this->at(1))
			->method('in')
			->will($this->returnValue('y'));

		$path = $this->Task->path . 'TestPlugin';
		$file = $path . '/Controller/TestPluginAppController.php';

		$this->Task->expects($this->at(2))->method('createFile')
			->with($file, new \PHPUnit_Framework_Constraint_IsAnything());

		$file = $path . '/phpunit.xml';
		$this->Task->expects($this->at(3))->method('createFile')
			->with($file, new \PHPUnit_Framework_Constraint_IsAnything());

		$file = $path . '/Test/bootstrap.php';
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
			->will($this->returnValue('y'));

		$path = $this->Task->path . 'BakeTestPlugin';
		$file = $path . DS . 'Controller/BakeTestPluginAppController.php';
		$this->Task->expects($this->at(1))->method('createFile')
			->with($file, new \PHPUnit_Framework_Constraint_IsAnything());

		$file = $path . '/phpunit.xml';
		$this->Task->expects($this->at(2))->method('createFile')
			->with($file, new \PHPUnit_Framework_Constraint_IsAnything());

		$file = $path . '/Test/bootstrap.php';
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

		array_unshift($paths, '/fake/path');
		$paths[] = '/fake/path2';

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
