<?php
/**
 * ProjectTask Test file
 *
 * Test Case for project generation shell task
 *
 * PHP versions 4 and 5
 *
 * CakePHP :  Rapid Development Framework (http://www.cakephp.org)
 * Copyright 2006-2009, Cake Software Foundation, Inc.
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @filesource
 * @copyright     Copyright 2006-2009, Cake Software Foundation, Inc.
 * @link          http://www.cakefoundation.org/projects/info/cakephp CakePHP Project
 * @package       cake
 * @subpackage    cake.tests.cases.console.libs.tasks
 * @since         CakePHP v 1.3.0
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

if (!class_exists('ProjectTask')) {
	require CAKE . 'console' .  DS . 'libs' . DS . 'tasks' . DS . 'project.php';
}

Mock::generatePartial(
	'ShellDispatcher', 'TestProjectTaskMockShellDispatcher',
	array('getInput', 'stdout', 'stderr', '_stop', '_initEnvironment')
);
Mock::generatePartial(
	'ProjectTask', 'MockProjectTask',
	array('in', '_stop', 'err', 'out', 'createFile')
);

/**
 * ProjectTask Test class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.console.libs.tasks
 */
class ProjectTaskTest extends CakeTestCase {

/**
 * startTest method
 *
 * @return void
 * @access public
 */
	function startTest() {
		$this->Dispatcher =& new TestProjectTaskMockShellDispatcher();
		$this->Dispatcher->shellPaths = App::path('shells');
		$this->Task =& new MockProjectTask($this->Dispatcher);
		$this->Task->Dispatch =& $this->Dispatcher;
		$this->Task->path = TMP . 'tests' . DS;
	}

/**
 * endTest method
 *
 * @return void
 * @access public
 */
	function endTest() {
		ClassRegistry::flush();

		$Folder =& new Folder($this->Task->path . 'bake_test_app');
		$Folder->delete();
	}

/**
 * creates a test project that is used for testing project task.
 *
 * @return void
 **/
	function _setupTestProject() {
		$skel = CAKE_CORE_INCLUDE_PATH . DS . CONSOLE_LIBS . 'templates' . DS . 'skel';
		$this->Task->setReturnValueAt(0, 'in', 'y');
		$this->Task->setReturnValueAt(1, 'in', 'n');
		$this->Task->bake($this->Task->path . 'bake_test_app', $skel);
	}

/**
 * test bake() method and directory creation.
 *
 * @return void
 **/
	function testBake() {
		$this->_setupTestProject();

		$path = $this->Task->path . 'bake_test_app';
		$this->assertTrue(is_dir($path), 'No project dir %s');
		$this->assertTrue(is_dir($path . DS . 'controllers'), 'No controllers dir %s');
		$this->assertTrue(is_dir($path . DS . 'controllers' . DS .'components'), 'No components dir %s');
		$this->assertTrue(is_dir($path . DS . 'models'), 'No models dir %s');
		$this->assertTrue(is_dir($path . DS . 'views'), 'No views dir %s');
		$this->assertTrue(is_dir($path . DS . 'views' . DS . 'helpers'), 'No helpers dir %s');
		$this->assertTrue(is_dir($path . DS . 'tests'), 'No tests dir %s');
		$this->assertTrue(is_dir($path . DS . 'tests' . DS . 'cases'), 'No cases dir %s');
		$this->assertTrue(is_dir($path . DS . 'tests' . DS . 'groups'), 'No groups dir %s');
		$this->assertTrue(is_dir($path . DS . 'tests' . DS . 'fixtures'), 'No fixtures dir %s');
	}

/**
 * test generation of Security.salt
 *
 * @return void
 **/
	function testSecuritySaltGeneration() {
		$this->_setupTestProject();

		$path = $this->Task->path . 'bake_test_app' . DS;
		$result = $this->Task->securitySalt($path);
		$this->assertTrue($result);

		$file =& new File($path . 'config' . DS . 'core.php');
		$contents = $file->read();
		$this->assertNoPattern('/DYhG93b0qyJfIxfs2guVoUubWwvniR2G0FgaC9mi/', $contents, 'Default Salt left behind. %s');
	}

/**
 * test getAdmin method, and that it returns Routing.admin or writes to config file.
 *
 * @return void
 **/
	function testGetAdmin() {
		Configure::write('Routing.admin', 'admin');
		$result = $this->Task->getAdmin();
		$this->assertEqual($result, 'admin_');

		Configure::write('Routing.admin', null);
		$this->_setupTestProject();
		$this->Task->configPath = $this->Task->path . 'bake_test_app' . DS . 'config' . DS;
		$this->Task->setReturnValue('in', 'super_duper_admin');

		$result = $this->Task->getAdmin();
		$this->assertEqual($result, 'super_duper_admin_');
	}

/**
 * Test execute method with one param to destination folder.
 *
 * @return void
 **/
	function testExecute() {
		$this->Task->params['skel'] = CAKE_CORE_INCLUDE_PATH . DS . CONSOLE_LIBS . 'templates' . DS . 'skel';
		$this->Task->params['working'] = TMP . 'tests' . DS;

		$path = $this->Task->path . 'bake_test_app';
		$this->Task->setReturnValue('in', 'y');
		$this->Task->setReturnValueAt(0, 'in', $path);

		$this->Task->execute();
		$this->assertTrue(is_dir($path), 'No project dir %s');
		$this->assertTrue(is_dir($path . DS . 'controllers'), 'No controllers dir %s');
		$this->assertTrue(is_dir($path . DS . 'controllers' . DS .'components'), 'No components dir %s');
		$this->assertTrue(is_dir($path . DS . 'models'), 'No models dir %s');
		$this->assertTrue(is_dir($path . DS . 'views'), 'No views dir %s');
		$this->assertTrue(is_dir($path . DS . 'views' . DS . 'helpers'), 'No helpers dir %s');
		$this->assertTrue(is_dir($path . DS . 'tests'), 'No tests dir %s');
		$this->assertTrue(is_dir($path . DS . 'tests' . DS . 'cases'), 'No cases dir %s');
		$this->assertTrue(is_dir($path . DS . 'tests' . DS . 'groups'), 'No groups dir %s');
		$this->assertTrue(is_dir($path . DS . 'tests' . DS . 'fixtures'), 'No fixtures dir %s');
	}
}
?>