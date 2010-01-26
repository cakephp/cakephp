<?php
/* SVN FILE: $Id$ */
/**
 * TestTaskTest file
 *
 * Test Case for test generation shell task
 *
 * PHP versions 4 and 5
 *
 * CakePHP :  Rapid Development Framework (http://cakephp.org)
 * Copyright 2006-2010, Cake Software Foundation, Inc.
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2006-2010, Cake Software Foundation, Inc.
 * @link          http://cakefoundation.org/projects/info/cakephp CakePHP Project
 * @package       cake
 * @subpackage    cake.tests.cases.console.libs.tasks
 * @since         CakePHP v 1.2.0.7726
 * @version       $Revision$
 * @modifiedby    $LastChangedBy$
 * @lastmodified  $Date$
 * @license       http://www.opensource.org/licenses/mit-license.php The MIT License
 */
App::import('Core', 'Shell');

if (!defined('DISABLE_AUTO_DISPATCH')) {
	define('DISABLE_AUTO_DISPATCH', true);
}

if (!class_exists('ShellDispatcher')) {
	ob_start();
	$argv = false;
	require CAKE . 'console' .  DS . 'cake.php';
	ob_end_clean();
}

if (!class_exists('TestTask')) {
	require CAKE . 'console' .  DS . 'libs' . DS . 'tasks' . DS . 'test.php';
}

Mock::generatePartial(
				'ShellDispatcher', 'TestTestTaskMockShellDispatcher',
				array('getInput', 'stdout', 'stderr', '_stop', '_initEnvironment')
				);
Mock::generatePartial(
				'TestTask', 'MockTestTask',
				array('in', 'out', 'createFile')
				);
/**
 * TestTaskTest class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.console.libs.tasks
 */
class TestTaskTest extends CakeTestCase {
/**
 * setUp method
 *
 * @return void
 * @access public
 */
	function setUp() {
		$this->Dispatcher =& new TestTestTaskMockShellDispatcher();
		$this->Task =& new MockTestTask($this->Dispatcher);
		$this->Task->Dispatch =& $this->Dispatcher;
	}
/**
 * tearDown method
 *
 * @return void
 * @access public
 */
	function tearDown() {
		ClassRegistry::flush();
	}
/**
 * Test that file path generation doesn't continuously append paths.
 *
 * @access public
 * @return void
 */
	function testFilePathGeneration () {
		$file = TESTS . 'cases' . DS . 'models' . DS . 'my_class.test.php';

		$this->Task->Dispatch->expectNever('stderr');
		$this->Task->Dispatch->expectNever('_stop');

		$this->Task->setReturnValueAt(0, 'in', 'y');
		$this->Task->expectAt(0, 'createFile', array($file, '*'));
		$this->Task->bake('Model', 'MyClass');

		$this->Task->setReturnValueAt(1, 'in', 'y');
		$this->Task->expectAt(1, 'createFile', array($file, '*'));
		$this->Task->bake('Model', 'MyClass');
	}
}
?>