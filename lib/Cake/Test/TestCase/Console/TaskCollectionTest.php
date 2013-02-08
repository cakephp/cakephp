<?php
/**
 * TaskCollectionTest file
 *
 * PHP 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/2.0/en/development/testing.html CakePHP(tm) Tests
 * @package       Cake.Test.Case.Console
 * @since         CakePHP(tm) v 2.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
namespace Cake\Test\TestCase\Console;

use Cake\Console\TaskCollection;
use Cake\Core\App;
use Cake\Core\Plugin;
use Cake\TestSuite\TestCase;

class TaskCollectionTest extends TestCase {

/**
 * setUp
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();
		$shell = $this->getMock('Cake\Console\Shell', array(), array(), '', false);
		$dispatcher = $this->getMock('Cake\Console\ShellDispatcher', array(), array(), '', false);
		$this->Tasks = new TaskCollection($shell, $dispatcher);
	}

/**
 * tearDown
 *
 * @return void
 */
	public function tearDown() {
		unset($this->Tasks);
		parent::tearDown();
	}

/**
 * test triggering callbacks on loaded tasks
 *
 * @return void
 */
	public function testLoad() {
		$result = $this->Tasks->load('DbConfig');
		$this->assertInstanceOf('Cake\Console\Command\Task\DbConfigTask', $result);
		$this->assertInstanceOf('Cake\Console\Command\Task\DbConfigTask', $this->Tasks->DbConfig);

		$result = $this->Tasks->loaded();
		$this->assertEquals(array('DbConfig'), $result, 'loaded() results are wrong.');
	}

/**
 * test load and enable = false
 *
 * @return void
 */
	public function testLoadWithEnableFalse() {
		$result = $this->Tasks->load('DbConfig', array('enabled' => false));
		$this->assertInstanceOf('Cake\Console\Command\Task\DbConfigTask', $result);
		$this->assertInstanceOf('Cake\Console\Command\Task\DbConfigTask', $this->Tasks->DbConfig);

		$this->assertFalse($this->Tasks->enabled('DbConfig'), 'DbConfigTask should be disabled');
	}

/**
 * test missingtask exception
 *
 * @expectedException Cake\Error\MissingTaskException
 * @return void
 */
	public function testLoadMissingTask() {
		$this->Tasks->load('ThisTaskShouldAlwaysBeMissing');
	}

/**
 * test loading a plugin helper.
 *
 * @return void
 */
	public function testLoadPluginTask() {
		$dispatcher = $this->getMock('Cake\Console\ShellDispatcher', array(), array(), '', false);
		$shell = $this->getMock('Cake\Console\Shell', array(), array(), '', false);
		App::build(array(
			'Plugin' => array(CAKE . 'Test/TestApp/Plugin/')
		));
		Plugin::load('TestPlugin');
		$this->Tasks = new TaskCollection($shell, $dispatcher);

		$result = $this->Tasks->load('TestPlugin.OtherTask');
		$this->assertInstanceOf('TestPlugin\Console\Command\Task\OtherTaskTask', $result, 'Task class is wrong.');
		$this->assertInstanceOf('TestPlugin\Console\Command\Task\OtherTaskTask', $this->Tasks->OtherTask, 'Class is wrong');
		Plugin::unload();
	}

/**
 * test unload()
 *
 * @return void
 */
	public function testUnload() {
		$this->Tasks->load('Extract');
		$this->Tasks->load('DbConfig');

		$result = $this->Tasks->loaded();
		$this->assertEquals(array('Extract', 'DbConfig'), $result, 'loaded tasks is wrong');

		$this->Tasks->unload('DbConfig');
		$this->assertFalse(isset($this->Tasks->DbConfig));
		$this->assertTrue(isset($this->Tasks->Extract));

		$result = $this->Tasks->loaded();
		$this->assertEquals(array('Extract'), $result, 'loaded tasks is wrong');
	}

}
