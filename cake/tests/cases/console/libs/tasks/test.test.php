<?php
/* SVN FILE: $Id$ */
/**
 * Test Case for test generation shell task
 *
 * 
 *
 * PHP versions 4 and 5
 *
 * CakePHP :  Rapid Development Framework (http://www.cakephp.org)
 * Copyright 2006-2008, Cake Software Foundation, Inc.
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @filesource
 * @copyright     Copyright 2006-2008, Cake Software Foundation, Inc.
 * @link          http://www.cakefoundation.org/projects/info/cakephp CakePHP Project
 * @package       cake
 * @subpackage    cake.cake.libs.
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

class TestTestShellDispatcher extends ShellDispatcher {

	function _initEnvironment() {
	}

	function stdout($string, $newline = true) {
	}

	function stderr($string) {
	}
	
	function getInput($prompt, $options, $default) {
	}

	function _stop($status = 0) {
		$this->stopped = 'Stopped with status: ' . $status;
	}
}

Mock::generatePartial('TestTask', 'MockTestTask', array('createFile', 'out', 'in'));

class TestTaskTest extends CakeTestCase {

	function setUp() {
		$this->dispatcher = new TestTestShellDispatcher();
		$this->task = new MockTestTask($this->dispatcher);
	}
/**
 * Test that file path generation doesn't continuously append paths.
 * 
 * @access public
 * @return void
 */
	function testFilePathGeneration () {
		$this->task->setReturnValue('in', 'y');
		$this->task->expectAt(0, 'createFile', array(TESTS . 'cases' . DS . 'models' . DS . 'my_class.test.php', '*'));
		$this->task->bake('Model', 'MyClass');
		
		$this->task->expectAt(1, 'createFile', array(TESTS . 'cases' . DS . 'models' . DS . 'my_class.test.php', '*'));
		$this->task->bake('Model', 'MyClass');
	}

	function tearDown() {
		unset($this->task, $this->dispatcher);
	}
}

?>