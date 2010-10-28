<?php
/**
 * ProjectTask Test file
 *
 * Test Case for project generation shell task
 *
 * PHP versions 4 and 5
 *
 * CakePHP : Rapid Development Framework (http://cakephp.org)
 * Copyright 2006-2010, Cake Software Foundation, Inc.
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2006-2010, Cake Software Foundation, Inc.
 * @link          http://cakephp.org CakePHP Project
 * @package       cake
 * @subpackage    cake.tests.cases.console.libs.tasks
 * @since         CakePHP v 1.3.0
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

require_once CAKE . 'console' .  DS . 'libs' . DS . 'tasks' . DS . 'project.php';

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
 * @access protected
 */
	function _setupTestProject() {
		$skel = CAKE_CORE_INCLUDE_PATH . DS . CAKE . 'console' . DS . 'templates' . DS . 'skel';
		$this->Task->setReturnValueAt(0, 'in', 'y');
		$this->Task->setReturnValueAt(1, 'in', 'n');
		$this->Task->bake($this->Task->path . 'bake_test_app', $skel);
	}

/**
 * test bake() method and directory creation.
 *
 * @return void
 * @access public
 */
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
 * test bake() method with -empty flag,  directory creation and empty files.
 *
 * @return void
 * @access public
 */
	function testBakeEmptyFlag() {
		$this->Task->params['empty'] = true;
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

		$this->assertTrue(is_file($path . DS . 'controllers' . DS .'components' . DS . 'empty'), 'No empty file in dir %s');
		$this->assertTrue(is_file($path . DS . 'locale' . DS . 'eng' . DS . 'LC_MESSAGES' . DS . 'empty'), 'No empty file in dir %s');
		$this->assertTrue(is_file($path . DS . 'models' . DS . 'behaviors' . DS . 'empty'), 'No empty file in dir %s');
		$this->assertTrue(is_file($path . DS . 'models' . DS . 'datasources' . DS . 'empty'), 'No empty file in dir %s');
		$this->assertTrue(is_file($path . DS . 'plugins' . DS . 'empty'), 'No empty file in dir %s');
		$this->assertTrue(is_file($path . DS . 'tests' . DS . 'cases' . DS . 'behaviors' . DS . 'empty'), 'No empty file in dir %s');
		$this->assertTrue(is_file($path . DS . 'tests' . DS . 'cases' . DS . 'components' . DS . 'empty'), 'No empty file in dir %s');
		$this->assertTrue(is_file($path . DS . 'tests' . DS . 'cases' . DS . 'controllers' . DS . 'empty'), 'No empty file in dir %s');
		$this->assertTrue(is_file($path . DS . 'tests' . DS . 'cases' . DS . 'datasources' . DS . 'empty'), 'No empty file in dir %s');
		$this->assertTrue(is_file($path . DS . 'tests' . DS . 'cases' . DS . 'helpers' . DS . 'empty'), 'No empty file in dir %s');
		$this->assertTrue(is_file($path . DS . 'tests' . DS . 'cases' . DS . 'models' . DS . 'empty'), 'No empty file in dir %s');
		$this->assertTrue(is_file($path . DS . 'tests' . DS . 'cases' . DS . 'shells' . DS . 'empty'), 'No empty file in dir %s');
		$this->assertTrue(is_file($path . DS . 'tests' . DS . 'fixtures' . DS . 'empty'), 'No empty file in dir %s');
		$this->assertTrue(is_file($path . DS . 'tests' . DS . 'groups' . DS . 'empty'), 'No empty file in dir %s');
		$this->assertTrue(is_file($path . DS . 'vendors' . DS . 'shells' . DS . 'tasks' . DS . 'empty'), 'No empty file in dir %s');
		$this->assertTrue(is_file($path . DS . 'views' . DS . 'errors' . DS . 'empty'), 'No empty file in dir %s');
		$this->assertTrue(is_file($path . DS . 'views' . DS . 'helpers' . DS . 'empty'), 'No empty file in dir %s');
		$this->assertTrue(is_file($path . DS . 'views' . DS . 'scaffolds' . DS . 'empty'), 'No empty file in dir %s');
		$this->assertTrue(is_file($path . DS . 'webroot' . DS . 'js' . DS . 'empty'), 'No empty file in dir %s');
	}

/**
 * test generation of Security.salt
 *
 * @return void
 * @access public
 */
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
	 * test generation of Security.cipherSeed
	 *
	 * @return void
	 * @access public
	 */
		function testSecurityCipherSeedGeneration() {
			$this->_setupTestProject();

			$path = $this->Task->path . 'bake_test_app' . DS;
			$result = $this->Task->securityCipherSeed($path);
			$this->assertTrue($result);

			$file =& new File($path . 'config' . DS . 'core.php');
			$contents = $file->read();
			$this->assertNoPattern('/76859309657453542496749683645/', $contents, 'Default CipherSeed left behind. %s');
		}

/**
 * Test that index.php is generated correctly.
 *
 * @return void
 * @access public
 */
	function testIndexPhpGeneration() {
		$this->_setupTestProject();

		$path = $this->Task->path . 'bake_test_app' . DS;
		$this->Task->corePath($path);

		$file =& new File($path . 'webroot' . DS . 'index.php');
		$contents = $file->read();
		$this->assertNoPattern('/define\(\'CAKE_CORE_INCLUDE_PATH\', \'ROOT/', $contents);

		$file =& new File($path . 'webroot' . DS . 'test.php');
		$contents = $file->read();
		$this->assertNoPattern('/define\(\'CAKE_CORE_INCLUDE_PATH\', \'ROOT/', $contents);
	}

/**
 * test getPrefix method, and that it returns Routing.prefix or writes to config file.
 *
 * @return void
 * @access public
 */
	function testGetPrefix() {
		Configure::write('Routing.prefixes', array('admin'));
		$result = $this->Task->getPrefix();
		$this->assertEqual($result, 'admin_');

		Configure::write('Routing.prefixes', null);
		$this->_setupTestProject();
		$this->Task->configPath = $this->Task->path . 'bake_test_app' . DS . 'config' . DS;
		$this->Task->setReturnValue('in', 'super_duper_admin');

		$result = $this->Task->getPrefix();
		$this->assertEqual($result, 'super_duper_admin_');

		$file =& new File($this->Task->configPath . 'core.php');
		$file->delete();
	}

/**
 * test cakeAdmin() writing core.php
 *
 * @return void
 * @access public
 */
	function testCakeAdmin() {
		$file =& new File(CONFIGS . 'core.php');
		$contents = $file->read();;
		$file =& new File(TMP . 'tests' . DS . 'core.php');
		$file->write($contents);

		Configure::write('Routing.prefixes', null);
		$this->Task->configPath = TMP . 'tests' . DS;
		$result = $this->Task->cakeAdmin('my_prefix');
		$this->assertTrue($result);

		$this->assertEqual(Configure::read('Routing.prefixes'), array('my_prefix'));
		$file->delete();
	}

/**
 * test getting the prefix with more than one prefix setup
 *
 * @return void
 * @access public
 */
	function testGetPrefixWithMultiplePrefixes() {
		Configure::write('Routing.prefixes', array('admin', 'ninja', 'shinobi'));
		$this->_setupTestProject();
		$this->Task->configPath = $this->Task->path . 'bake_test_app' . DS . 'config' . DS;
		$this->Task->setReturnValue('in', 2);

		$result = $this->Task->getPrefix();
		$this->assertEqual($result, 'ninja_');
	}

/**
 * Test execute method with one param to destination folder.
 *
 * @return void
 * @access public
 */
	function testExecute() {
		$this->Task->params['skel'] = CAKE_CORE_INCLUDE_PATH . DS . CAKE . DS . 'console' . DS. 'templates' . DS . 'skel';
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
