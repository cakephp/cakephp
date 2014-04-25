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
 * @since         3.0.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Console\Command\Task;

use Cake\Console\Command\Task\TemplateTask;
use Cake\Core\Configure;
use Cake\Core\Plugin;
use Cake\TestSuite\TestCase;

/**
 * CellTaskTest class
 */
class CellTaskTest extends TestCase {

/**
 * setup method
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();
		$io = $this->getMock('Cake\Console\ConsoleIo', [], [], '', false);

		$this->Task = $this->getMock(
			'Cake\Console\Command\Task\CellTask',
			['in', 'err', 'createFile', '_stop'],
			[$io]
		);
		$this->Task->Test = $this->getMock('Cake\Console\Command\Task\TestTask',
			[],
			[$io]
		);
		$this->Task->Template = new TemplateTask($io);
		$this->Task->Template->initialize();
		$this->Task->Template->interactive = false;
	}

/**
 * Test the excute method.
 *
 * @return void
 */
	public function testMain() {
		$this->Task->Test->expects($this->once())
			->method('bake')
			->with('cell', 'Example');

		$this->Task->expects($this->at(0))
			->method('createFile')
			->with(
				$this->_normalizePath(APP . 'Template/Cell/Example/display.ctp'),
				''
			);
		$this->Task->expects($this->at(1))
			->method('createFile')
			->with(
				$this->_normalizePath(APP . 'View/Cell/ExampleCell.php'),
				$this->stringContains('class ExampleCell extends Cell')
			);

		$this->Task->main('Example');
	}

/**
 * Test baking within a plugin.
 *
 * @return void
 */
	public function testMainPlugin() {
		Plugin::load('TestPlugin');

		$path = Plugin::path('TestPlugin');

		$this->Task->plugin = 'TestPlugin';
		$this->Task->expects($this->at(0))
			->method('createFile')
			->with(
				$this->_normalizePath($path . 'Template/Cell/Example/display.ctp'),
				''
			);
		$this->Task->expects($this->at(1))
			->method('createFile')
			->with(
				$this->_normalizePath($path . 'View/Cell/ExampleCell.php'),
				$this->stringContains('class ExampleCell extends Cell')
			);

		$result = $this->Task->bake('Example');
		$this->assertContains('namespace TestPlugin\View\Cell;', $result);
		$this->assertContains('use Cake\View\Cell;', $result);
		$this->assertContains('class ExampleCell extends Cell {', $result);
	}

}
