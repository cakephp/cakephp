<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         1.3.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Console\Command;

use Cake\Console\Command\BakeShellShell;
use Cake\Controller\Controller;
use Cake\Core\App;
use Cake\Core\Configure;
use Cake\Core\Plugin;
use Cake\TestSuite\TestCase;

class BakeShellTest extends TestCase {

/**
 * fixtures
 *
 * @var array
 */
	public $fixtures = array('core.comment');

/**
 * setup test
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();
		$this->io = $this->getMock('Cake\Console\ConsoleIo', [], [], '', false);

		$this->Shell = $this->getMock(
			'Cake\Console\Command\BakeShell',
			['in', 'out', 'hr', 'err', 'createFile', '_stop'],
			[$this->io]
		);
		Configure::write('App.namespace', 'TestApp');
	}

/**
 * tearDown method
 *
 * @return void
 */
	public function tearDown() {
		parent::tearDown();
		unset($this->Shell);
	}

/**
 * test bake all
 *
 * @return void
 */
	public function testAllWithModelName() {
		$this->Shell->Model = $this->getMock('Cake\Console\Command\Task\ModelTask');
		$this->Shell->Controller = $this->getMock('Cake\Console\Command\Task\ControllerTask');
		$this->Shell->View = $this->getMock('Cake\Console\Command\Task\ModelTask');

		$this->Shell->Model->expects($this->once())
			->method('bake')
			->with('Comments')
			->will($this->returnValue(true));

		$this->Shell->Controller->expects($this->once())
			->method('bake')
			->with('Comments')
			->will($this->returnValue(true));

		$this->Shell->View->expects($this->once())
			->method('main')
			->with('Comments');

		$this->Shell->expects($this->at(0))
			->method('out')
			->with('Bake All');

		$this->Shell->expects($this->at(2))
			->method('out')
			->with('<success>Bake All complete</success>');

		$this->Shell->connection = '';
		$this->Shell->params = [];
		$this->Shell->all('Comment');
	}

/**
 * Test the main function.
 *
 * @return void
 */
	public function testMain() {
		$this->Shell->expects($this->at(0))
			->method('out')
			->with($this->stringContains('The following commands'));

		$this->Shell->expects($this->exactly(19))
			->method('out');

		$this->Shell->loadTasks();
		$this->Shell->main();
	}

/**
 * Test that the generated option parser reflects all tasks.
 *
 * @return void
 */
	public function testGetOptionParser() {
		$this->Shell->loadTasks();
		$parser = $this->Shell->getOptionParser();
		$commands = $parser->subcommands();
		$this->assertArrayHasKey('fixture', $commands);
		$this->assertArrayHasKey('view', $commands);
		$this->assertArrayHasKey('controller', $commands);
		$this->assertArrayHasKey('model', $commands);
	}

/**
 * Test loading tasks from core directories.
 *
 * @return void
 */
	public function testLoadTasksCoreAndApp() {
		$this->Shell->loadTasks();
		$expected = [
			'Behavior',
			'Cell',
			'Component',
			'Controller',
			'Fixture',
			'Helper',
			'Model',
			'Plugin',
			'Project',
			'Shell',
			'Test',
			'View',
			'Zerg',
		];
		sort($this->Shell->tasks);
		sort($expected);
		$this->assertEquals($expected, $this->Shell->tasks);
	}

/**
 * Test loading tasks from plugins
 *
 * @return void
 */
	public function testLoadTasksPlugin() {
		Plugin::load('TestPlugin');
		$this->Shell->loadTasks();
		$this->assertContains('TestPlugin.Widget', $this->Shell->tasks);
		$this->assertContains('TestPlugin.Zerg', $this->Shell->tasks);
	}

}
