<?php
/**
 * DBConfigTask Test Case
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2009, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2009, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       cake
 * @subpackage    cake.tests.cases.console.libs.tasks
 * @since         CakePHP(tm) v 1.3
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
App::import('Shell', 'Shell', false);

if (!defined('DISABLE_AUTO_DISPATCH')) {
	define('DISABLE_AUTO_DISPATCH', true);
}

if (!class_exists('ShellDispatcher')) {
	ob_start();
	$argv = false;
	require CAKE . 'console' .  DS . 'cake.php';
	ob_end_clean();
}

require_once CAKE . 'console' .  DS . 'libs' . DS . 'tasks' . DS . 'db_config.php';


class TEST_DATABASE_CONFIG {
	public $default = array(
		'driver' => 'mysql',
		'persistent' => false,
		'host' => 'localhost',
		'login' => 'user',
		'password' => 'password',
		'database' => 'database_name',
		'prefix' => '',
	);

	public $otherOne = array(
		'driver' => 'mysql',
		'persistent' => false,
		'host' => 'localhost',
		'login' => 'user',
		'password' => 'password',
		'database' => 'other_one',
		'prefix' => '',
	);
}

/**
 * DbConfigTest class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.console.libs.tasks
 */
class DbConfigTaskTest extends CakeTestCase {

/**
 * startTest method
 *
 * @return void
 */
	public function startTest() {
		$this->Dispatcher = $this->getMock('ShellDispatcher', array(
			'getInput', 'stdout', 'stderr', '_stop', '_initEnvironment', 'clear'
		));
		$this->Task = $this->getMock('DbConfigTask', 
			array('in', 'out', 'err', 'hr', 'createFile', '_stop', '_checkUnitTest', '_verify'),
			array(&$this->Dispatcher)
		);
		$this->Task->Dispatch->shellPaths = App::path('shells');

		$this->Task->params['working'] = rtrim(APP, DS);
		$this->Task->databaseClassName = 'TEST_DATABASE_CONFIG';
	}

/**
 * endTest method
 *
 * @return void
 */
	public function endTest() {
		unset($this->Task, $this->Dispatcher);
		ClassRegistry::flush();
	}

/**
 * Test the getConfig method.
 *
 * @return void
 */
	public function testGetConfig() {
		$this->Task->expects($this->at(0))->method('in')->will($this->returnValue('otherOne'));
		$result = $this->Task->getConfig();
		$this->assertEqual($result, 'otherOne');
	}

/**
 * test that initialize sets the path up.
 *
 * @return void
 */
	public function testInitialize() {
		$this->assertTrue(empty($this->Task->path));
		$this->Task->initialize();
		$this->assertFalse(empty($this->Task->path));
		$this->assertEqual($this->Task->path, APP . 'config' . DS);

	}

/**
 * test execute and by extension _interactive
 *
 * @return void
 */
	public function testExecuteIntoInteractive() {
		$this->Task->initialize();
		$this->Task = $this->getMock('DbConfigTask', array('in', '_stop', 'createFile'), array(&$this->Dispatcher));

		$this->Task->expects($this->once())->method('_stop');
		$this->Task->expects($this->at(0))->method('in')->will($this->returnValue('default')); //name
		$this->Task->expects($this->at(1))->method('in')->will($this->returnValue('mysql')); //db type
		$this->Task->expects($this->at(2))->method('in')->will($this->returnValue('n')); //persistant
		$this->Task->expects($this->at(3))->method('in')->will($this->returnValue('localhost')); //server
		$this->Task->expects($this->at(4))->method('in')->will($this->returnValue('n')); //port
		$this->Task->expects($this->at(5))->method('in')->will($this->returnValue('root')); //user
		$this->Task->expects($this->at(6))->method('in')->will($this->returnValue('password')); //password
		$this->Task->expects($this->at(10))->method('in')->will($this->returnValue('cake_test')); //db
		$this->Task->expects($this->at(11))->method('in')->will($this->returnValue('n')); //prefix
		$this->Task->expects($this->at(12))->method('in')->will($this->returnValue('n')); //encoding
		$this->Task->expects($this->at(13))->method('in')->will($this->returnValue('y')); //looks good
		$this->Task->expects($this->at(14))->method('in')->will($this->returnValue('n')); //another

		$result = $this->Task->execute();
	}
}
