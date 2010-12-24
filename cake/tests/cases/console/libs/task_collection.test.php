<?php
/**
 * TaskCollectionTest file
 *
 * PHP 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/view/1196/Testing CakePHP(tm) Tests
 * @package       cake.tests.cases.libs
 * @since         CakePHP(tm) v 2.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

App::import('Shell', 'TaskCollection', false);
App::import('Shell', 'Shell', false);

class TaskCollectionTest extends CakeTestCase {
/**
 * setup
 *
 * @return void
 */
	function setup() {
		$shell = $this->getMock('Shell', array(), array(), '', false);
		$dispatcher = $this->getMock('ShellDispatcher', array(), array(), '', false);
		$this->Tasks = new TaskCollection($shell, $dispatcher);
	}

/**
 * teardown
 *
 * @return void
 */
	function teardown() {
		unset($this->Tasks);
	}

/**
 * test triggering callbacks on loaded tasks
 *
 * @return void
 */
	function testLoad() {
		$result = $this->Tasks->load('DbConfig');
		$this->assertInstanceOf('DbConfigTask', $result);
		$this->assertInstanceOf('DbConfigTask', $this->Tasks->DbConfig);

		$result = $this->Tasks->attached();
		$this->assertEquals(array('DbConfig'), $result, 'attached() results are wrong.');

		$this->assertTrue($this->Tasks->enabled('DbConfig'));
	}

/**
 * test load and enable = false
 *
 * @return void
 */
	function testLoadWithEnableFalse() {
		$result = $this->Tasks->load('DbConfig', array(), false);
		$this->assertInstanceOf('DbConfigTask', $result);
		$this->assertInstanceOf('DbConfigTask', $this->Tasks->DbConfig);

		$this->assertFalse($this->Tasks->enabled('DbConfig'), 'DbConfigTask should be disabled');
	}
/**
 * test missinghelper exception
 *
 * @expectedException MissingTaskFileException
 * @return void
 */
	function testLoadMissingTaskFile() {
		$result = $this->Tasks->load('ThisTaskShouldAlwaysBeMissing');
	}

/**
 * test loading a plugin helper.
 *
 * @return void
 */
	function testLoadPluginTask() {
		$dispatcher = $this->getMock('ShellDispatcher', array(), array(), '', false);
		$shell = $this->getMock('Shell', array(), array(), '', false);
		App::build(array(
			'plugins' => array(TEST_CAKE_CORE_INCLUDE_PATH . 'tests' . DS . 'test_app' . DS . 'plugins' . DS)
		));
		$this->Tasks = new TaskCollection($shell, $dispatcher);

		$result = $this->Tasks->load('TestPlugin.OtherTask');
		$this->assertInstanceOf('OtherTaskTask', $result, 'Task class is wrong.');
		$this->assertInstanceOf('OtherTaskTask', $this->Tasks->OtherTask, 'Class is wrong');
	}

/**
 * test unload()
 *
 * @return void
 */
	function testUnload() {
		$this->Tasks->load('Extract');
		$this->Tasks->load('DbConfig');

		$result = $this->Tasks->attached();
		$this->assertEquals(array('Extract', 'DbConfig'), $result, 'loaded tasks is wrong');

		$this->Tasks->unload('DbConfig');
		$this->assertFalse(isset($this->Tasks->DbConfig));
		$this->assertTrue(isset($this->Tasks->Extract));

		$result = $this->Tasks->attached();
		$this->assertEquals(array('Extract'), $result, 'loaded tasks is wrong');
	}

}