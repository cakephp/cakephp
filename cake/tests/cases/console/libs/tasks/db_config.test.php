<?php
/**
 * DBConfigTask Test Case
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
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
//require_once CAKE . 'console' .  DS . 'libs' . DS . 'tasks' . DS . 'template.php';

Mock::generatePartial(
	'ShellDispatcher', 'TestDbConfigTaskMockShellDispatcher',
	array('getInput', 'stdout', 'stderr', '_stop', '_initEnvironment')
);

Mock::generatePartial(
	'DbConfigTask', 'MockDbConfigTask',
	array('in', 'hr', 'out', 'err', 'createFile', '_stop', '_checkUnitTest')
);

class TEST_DATABASE_CONFIG {
	var $default = array(
		'driver' => 'mysql',
		'persistent' => false,
		'host' => 'localhost',
		'login' => 'user',
		'password' => 'password',
		'database' => 'database_name',
		'prefix' => '',
	);

	var $otherOne = array(
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
 * @access public
 */
	function startTest() {
		$this->Dispatcher =& new TestDbConfigTaskMockShellDispatcher();
		$this->Task =& new MockDbConfigTask($this->Dispatcher);
		$this->Task->Dispatch =& $this->Dispatcher;
		$this->Task->Dispatch->shellPaths = App::path('shells');

		$this->Task->params['working'] = rtrim(APP, DS);
		$this->Task->databaseClassName = 'TEST_DATABASE_CONFIG';
	}

/**
 * endTest method
 *
 * @return void
 * @access public
 */
	function endTest() {
		unset($this->Task, $this->Dispatcher);
		ClassRegistry::flush();
	}

/**
 * Test the getConfig method.
 *
 * @return void
 * @access public
 */
	function testGetConfig() {
		$this->Task->setReturnValueAt(0, 'in', 'otherOne');
		$result = $this->Task->getConfig();
		$this->assertEqual($result, 'otherOne');
	}

/**
 * test that initialize sets the path up.
 *
 * @return void
 * @access public
 */
	function testInitialize() {
		$this->assertTrue(empty($this->Task->path));
		$this->Task->initialize();
		$this->assertFalse(empty($this->Task->path));
		$this->assertEqual($this->Task->path, APP . 'config' . DS);

	}

/**
 * test execute and by extension __interactive
 *
 * @return void
 * @access public
 */
	function testExecuteIntoInteractive() {
		$this->Task->initialize();

		$this->Task->expectOnce('_stop');
		$this->Task->setReturnValue('in', 'y');
		$this->Task->setReturnValueAt(0, 'in', 'default');
		$this->Task->setReturnValueAt(1, 'in', 'n');
		$this->Task->setReturnValueAt(2, 'in', 'localhost');
		$this->Task->setReturnValueAt(3, 'in', 'n');
		$this->Task->setReturnValueAt(4, 'in', 'root');
		$this->Task->setReturnValueAt(5, 'in', 'password');
		$this->Task->setReturnValueAt(6, 'in', 'cake_test');
		$this->Task->setReturnValueAt(7, 'in', 'n');
		$this->Task->setReturnValueAt(8, 'in', 'y');
		$this->Task->setReturnValueAt(9, 'in', 'y');
		$this->Task->setReturnValueAt(10, 'in', 'y');
		$this->Task->setReturnValueAt(11, 'in', 'n');

		$result = $this->Task->execute();
	}
}
