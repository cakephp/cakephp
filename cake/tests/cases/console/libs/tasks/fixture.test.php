<?php
/* SVN FILE: $Id$ */
/**
 * TestTaskTest file
 *
 * Test Case for test generation shell task
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

if (!class_exists('FixtureTask')) {
	require CAKE . 'console' .  DS . 'libs' . DS . 'tasks' . DS . 'fixture.php';
}

Mock::generatePartial(
	'ShellDispatcher', 'TestFixtureTaskMockShellDispatcher',
	array('getInput', 'stdout', 'stderr', '_stop', '_initEnvironment')
);

Mock::generatePartial(
	'FixtureTask', 'MockFixtureTask',
	array('in', 'out', 'err', 'createFile', '_stop')
);
/**
 * FixtureTaskTest class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.console.libs.tasks
 */
class FixtureTaskTest extends CakeTestCase {
/**
 * fixtures
 *
 * @var array
 **/
	var $fixtures = array('core.article', 'core.comment');
/**
 * setUp method
 *
 * @return void
 * @access public
 */
	function startTest() {
		$this->Dispatcher =& new TestFixtureTaskMockShellDispatcher();
		$this->Task =& new MockFixtureTask($this->Dispatcher);
		$this->Task->Dispatch = new $this->Dispatcher;
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
 * test that initialize sets the path
 *
 * @return void
 **/
	function testInitialize() {
		$this->Task->params['working'] = '/my/path';
		$this->Task->initialize();

		$expected = '/my/path/tests/fixtures/';
		$this->assertEqual($this->Task->path, $expected);
	}
/**
 * test import option array generation
 *
 * @return void
 **/
	function testImportOptions() {
		$this->Task->setReturnValueAt(0, 'in', 'y');
		$this->Task->setReturnValueAt(1, 'in', 'y');

		$result = $this->Task->importOptions('Article');
		$expected = array('schema' => 'Article', 'records' => true);
		$this->assertEqual($result, $expected);
		
		$this->Task->setReturnValueAt(2, 'in', 'n');
		$this->Task->setReturnValueAt(3, 'in', 'n');

		$result = $this->Task->importOptions('Article');
		$expected = array();
		$this->assertEqual($result, $expected);
	}

}
?>