<?php
/* SVN FILE: $Id$ */
/**
 * PluginTask Test file
 *
 * Test Case for plugin generation shell task
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

if (!class_exists('PluginTask')) {
	require CAKE . 'console' .  DS . 'libs' . DS . 'tasks' . DS . 'plugin.php';
	require CAKE . 'console' .  DS . 'libs' . DS . 'tasks' . DS . 'template.php';
}

Mock::generatePartial(
	'ShellDispatcher', 'TestPluginTaskMockShellDispatcher',
	array('getInput', 'stdout', 'stderr', '_stop', '_initEnvironment')
);
Mock::generatePartial(
	'PluginTask', 'MockPluginTask',
	array('in', '_stop', 'err', 'out', 'createFile', 'isLoadableClass')
);

/**
 * PluginTaskPlugin class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.console.libs.tasks
 */
class PluginTaskTest extends CakeTestCase {

	var $fixtures = array('core.article', 'core.comment', 'core.articles_tag', 'core.tag');
/**
 * setUp method
 *
 * @return void
 * @access public
 */
	function startTest() {
		$this->Dispatcher =& new TestPluginTaskMockShellDispatcher();
		$this->Dispatcher->shellPaths = Configure::read('shellPaths');
		$this->Task =& new MockPluginTask($this->Dispatcher);
		$this->Task->Dispatch =& $this->Dispatcher;
	}

/**
 * tearDown method
 *
 * @return void
 * @access public
 */
	function endTest() {
		ClassRegistry::flush();
	}

/**
 * test bake()
 *
 * @return void
 **/
	function testBake() {
		
	}
}
?>