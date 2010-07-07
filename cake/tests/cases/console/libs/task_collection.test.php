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
 * @package       cake
 * @subpackage    cake.tests.cases.libs
 * @since         CakePHP(tm) v 2.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
if (!defined('DISABLE_AUTO_DISPATCH')) {
	define('DISABLE_AUTO_DISPATCH', true);
}
if (!class_exists('ShellDispatcher')) {
	ob_start();
	$argv = false;
	require CAKE . 'console' .  DS . 'cake.php';
	ob_end_clean();
}
App::import('Shell', 'TaskCollection', false);
App::import('Shell', 'Shell', false);

class TaskCollectionTest extends CakeTestCase {
/**
 * setup
 *
 * @return void
 */
	function setup() {
		$dispatcher = $this->getMock('ShellDispatcher', array(), array(), '', false);
		$dispatcher->shellPaths = App::path('shells');
		$this->Tasks = new TaskCollection($dispatcher);
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
		$this->assertType('DbConfigTask', $result);
		$this->assertType('DbConfigTask', $this->Tasks->DbConfig);

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
		$this->assertType('DbConfigTask', $result);
		$this->assertType('DbConfigTask', $this->Tasks->DbConfig);

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
		$this->markTestIncomplete('Not done');
		App::build(array(
			'plugins' => array(TEST_CAKE_CORE_INCLUDE_PATH . 'tests' . DS . 'test_app' . DS . 'plugins' . DS),
		));
		$result = $this->Tasks->load('TestPlugin.OtherTask');
		$this->assertType('OtherTaskTask', $result, 'Task class is wrong.');
		$this->assertType('OtherTaskTask', $this->Tasks->OtherTask, 'Class is wrong');

		App::build();
	}

/**
 * test unload()
 *
 * @return void
 */
	function testUnload() {
		$this->markTestIncomplete('Not done');
		$this->Tasks->load('Extract');
		$this->Tasks->load('Fixture');

		$result = $this->Tasks->attached();
		$this->assertEquals(array('Extract', 'Fixture'), $result, 'loaded tasks is wrong');

		$this->Tasks->unload('Fixture');
		$this->assertFalse(isset($this->Tasks->Fixture));
		$this->assertTrue(isset($this->Tasks->Extract));

		$result = $this->Tasks->attached();
		$this->assertEquals(array('Extract'), $result, 'loaded tasks is wrong');
	}

/**
 * test triggering callbacks.
 *
 * @return void
 */
	function testTrigger() {
		$this->markTestIncomplete('Not done');
	}

/**
 * test trigger and disabled tasks.
 *
 * @return void
 */
	function testTriggerWithDisabledTasks() {
		$this->markTestIncomplete('Not done');
	}

/**
 * test normalizeObjectArray
 *
 * @return void
 */
	function testnormalizeObjectArray() {
		$tasks = array(
			'Html', 
			'Foo.Bar' => array('one', 'two'),
			'Something',
			'Banana.Apple' => array('foo' => 'bar')
		);
		$result = TaskCollection::normalizeObjectArray($tasks);
		$expected = array(
			'Html' => array('class' => 'Html', 'settings' => array()),
			'Bar' => array('class' => 'Foo.Bar', 'settings' => array('one', 'two')),
			'Something' => array('class' => 'Something', 'settings' => array()),
			'Apple' => array('class' => 'Banana.Apple', 'settings' => array('foo' => 'bar')),
		);
		$this->assertEquals($expected, $result);
	}
}