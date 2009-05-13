<?php
/**
 * TemplateTask file
 *
 * Test Case for TemplateTask generation shell task
 *
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
 * @link          http://cakephp.org
 * @package       cake
 * @subpackage    cake.
 * @since         CakePHP(tm) v 1.3
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
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

if (!class_exists('TemplateTask')) {
	require_once CAKE . 'console' .  DS . 'libs' . DS . 'tasks' . DS . 'template.php';
}

Mock::generatePartial(
	'ShellDispatcher', 'TestTemplateTaskMockShellDispatcher',
	array('getInput', 'stdout', 'stderr', '_stop', '_initEnvironment')
);

Mock::generatePartial(
	'TemplateTask', 'MockTemplateTask',
	array('in', 'out', 'err', 'createFile', '_stop')
);

/**
 * TemplateTaskTest class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.console.libs.tasks
 */
class TemplateTaskTest extends CakeTestCase {
/**
 * setUp method
 *
 * @return void
 * @access public
 */
	function startTest() {
		$this->Dispatcher =& new TestTemplateTaskMockShellDispatcher();
		$this->Task =& new MockTemplateTask($this->Dispatcher);
		$this->Task->Dispatch = new $this->Dispatcher;
		$this->Task->Dispatch->shellPaths = Configure::read('shellPaths');
	}

/**
 * tearDown method
 *
 * @return void
 * @access public
 */
	function endTest() {
		unset($this->Task, $this->Dispatcher);
		ClassRegistry::flush();
	}

/**
 * test that set sets variables
 *
 * @return void
 **/
	function testSet() {
		$this->Task->set('one', 'two');
		$this->assertTrue(isset($this->Task->templateVars['one']));
		$this->assertEqual($this->Task->templateVars['one'], 'two');

		$this->Task->set(array('one' => 'three', 'four' => 'five'));
		$this->assertTrue(isset($this->Task->templateVars['one']));
		$this->assertEqual($this->Task->templateVars['one'], 'three');
		$this->assertTrue(isset($this->Task->templateVars['four']));
		$this->assertEqual($this->Task->templateVars['four'], 'five');
	}

/**
 * test Initialize
 *
 * @return void
 **/
	function testInitialize() {
		$this->Task->initialize();
		$this->assertEqual($this->Task->templatePaths, $this->Task->Dispatch->shellPaths);
	}

/**
 * test generate
 *
 * @return void
 **/
	function testGenerate() {
		$this->Task->templatePaths = array(
			TEST_CAKE_CORE_INCLUDE_PATH . 'tests' . DS .  'test_app' . DS . 'vendors' . DS . 'shells' . DS
		);
		$result = $this->Task->generate('objects', 'test_object', array('test' => 'foo'));
		$expected = "I got rendered\nfoo";
		$this->assertEqual($result, $expected);
		
		
	}
}
?>