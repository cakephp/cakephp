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
 * @package       cake.tests.cases.console.libs.tasks
 * @since         CakePHP v 1.3.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
App::import('Shell', 'Shell', false);
App::import('Shell', array(
	'tasks/plugin',
	'tasks/model'
));

App::import('Core', array('File'));

require_once CAKE . 'console' .  DS . 'shell_dispatcher.php';

/**
 * PluginTaskPlugin class
 *
 * @package       cake.tests.cases.console.libs.tasks
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

		$this->Task = $this->getMock('PluginTask', 
			array('in', 'err', 'createFile', '_stop', 'clear'),
			array($out, $out, $in)
		);
		$this->Task->path = TMP . 'tests' . DS;
		
		$this->_paths = $paths = App::path('plugins');
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

		$path = $this->Task->path . 'bake_test_plugin';

		$file = $path . DS . 'bake_test_plugin_app_controller.php';
		$this->Task->expects($this->at(2))->method('createFile')
			->with($file, new PHPUnit_Framework_Constraint_IsAnything());

		$file = $path . DS . 'bake_test_plugin_app_model.php';
		$this->Task->expects($this->at(3))->method('createFile')
			->with($file, new PHPUnit_Framework_Constraint_IsAnything());

		$this->Task->bake('BakeTestPlugin');

		$path = $this->Task->path . 'bake_test_plugin';
		$this->assertTrue(is_dir($path), 'No plugin dir %s');
		
		$directories = array(
			'config' . DS . 'schema',
			'models' . DS . 'behaviors',
			'models' . DS . 'datasources',
			'console' . DS . 'shells' . DS . 'tasks',
			'controllers' . DS . 'components',
			'libs',
			'views' . DS . 'helpers',
			'tests' . DS . 'cases' . DS . 'components',
			'tests' . DS . 'cases' . DS . 'helpers',
			'tests' . DS . 'cases' . DS . 'behaviors',
			'tests' . DS . 'cases' . DS . 'controllers',
			'tests' . DS . 'cases' . DS . 'models',
			'tests' . DS . 'groups',
			'tests' . DS . 'fixtures',
			'vendors',
			'webroot'
		);
		foreach ($directories as $dir) {
			$this->assertTrue(is_dir($path . DS . $dir), 'Missing directory for ' . $dir);
		}

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

		$path = $this->Task->path . 'test_plugin';
		$file = $path . DS . 'test_plugin_app_controller.php';
		$this->Task->expects($this->at(3))->method('createFile')
			->with($file, new PHPUnit_Framework_Constraint_IsAnything());

		$file = $path . DS . 'test_plugin_app_model.php';
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

		$path = $this->Task->path . 'bake_test_plugin';
		$file = $path . DS . 'bake_test_plugin_app_controller.php';
		$this->Task->expects($this->at(2))->method('createFile')
			->with($file, new PHPUnit_Framework_Constraint_IsAnything());

		$path = $this->Task->path . 'bake_test_plugin';
		$file = $path . DS . 'bake_test_plugin_app_model.php';
		$this->Task->expects($this->at(3))->method('createFile')
			->with($file, new PHPUnit_Framework_Constraint_IsAnything());
		
		$this->Task->args = array('BakeTestPlugin');

		$this->Task->execute();

		$Folder = new Folder($this->Task->path . 'bake_test_plugin');
		$Folder->delete();
	}

}
