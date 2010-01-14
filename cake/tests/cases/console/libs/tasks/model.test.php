<?php
/* SVN FILE: $Id$ */
/**
 * ModelTaskTest file
 *
 * Test Case for test generation shell task
 *
 * PHP versions 4 and 5
 *
 * CakePHP :  Rapid Development Framework (http://www.cakephp.org)
 * Copyright 2006-2010, Cake Software Foundation, Inc.
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @filesource
 * @copyright     Copyright 2006-2010, Cake Software Foundation, Inc.
 * @link          http://www.cakefoundation.org/projects/info/cakephp CakePHP Project
 * @package       cake
 * @subpackage    cake.tests.cases.console.libs.tasks
 * @since         CakePHP v 1.2.6
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
	require CAKE . 'console' .  DS . 'libs' . DS . 'tasks' . DS . 'model.php';
}

Mock::generatePartial(
	'ShellDispatcher', 'TestModelTaskMockShellDispatcher',
	array('getInput', 'stdout', 'stderr', '_stop', '_initEnvironment')
);
Mock::generatePartial(
	'ModelTask', 'MockModelTask',
	array('in', 'out', 'createFile')
);
/**
 * ModelTaskTest class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.console.libs.tasks
 */
class ModelTaskTest extends CakeTestCase {
	var $fixtures = array('core.datatype', 'core.binary_test');
/**
 * setUp method
 *
 * @return void
 * @access public
 */
	function setUp() {
		$this->Dispatcher =& new TestModelTaskMockShellDispatcher();
		$this->Task =& new MockModelTask($this->Dispatcher);
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
 * test fixture generation with floats
 *
 * @return void
 **/
	function testFixtureGeneration() {
		$this->Task->useDbConfig = 'test_suite';
		$this->Task->setReturnValue('createFile', true);
		$result = $this->Task->fixture('Datatype');
		$this->assertPattern('/float_field\' => 1/', $result);

		$result = $this->Task->fixture('BinaryTest');
		$this->assertPattern("/'data' => 'Lorem ipsum dolor sit amet'/", $result);
	}
}
?>