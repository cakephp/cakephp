<?php
/**
 * ProjectTask Test file
 *
 * Test Case for project generation shell task
 *
 * PHP 5
 *
 * CakePHP : Rapid Development Framework (http://cakephp.org)
 * Copyright 2006-2010, Cake Software Foundation, Inc.
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2006-2010, Cake Software Foundation, Inc.
 * @link          http://cakephp.org CakePHP Project
 * @package       cake.tests.cases.console.libs.tasks
 * @since         CakePHP v 1.3.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

App::uses('ShellDispatcher', 'Console');
App::uses('ConsoleOutput', 'Console');
App::uses('ConsoleInput', 'Console');
App::uses('Shell', 'Console');
App::uses('ProjectTask', 'Console/Command/Task');
App::uses('Folder', 'Utility');
App::uses('File', 'Utility');

/**
 * ProjectTask Test class
 *
 * @package       cake.tests.cases.console.libs.tasks
 */
class ProjectTaskTest extends CakeTestCase {

/**
 * setup method
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();
		$out = $this->getMock('ConsoleOutput', array(), array(), '', false);
		$in = $this->getMock('ConsoleInput', array(), array(), '', false);
		
		$this->Task = $this->getMock('ProjectTask', 
			array('in', 'err', 'createFile', '_stop'),
			array($out, $out, $in)
		);
		$this->Task->path = TMP . 'tests' . DS;
	}

/**
 * teardown method
 *
 * @return void
 */
	public function tearDown() {
		parent::tearDown();

		$Folder = new Folder($this->Task->path . 'bake_test_app');
		$Folder->delete();
		unset($this->Task);
	}

/**
 * creates a test project that is used for testing project task.
 *
 * @return void
 */
	protected function _setupTestProject() {
		$skel = LIBS . 'Console' . DS . 'templates' . DS . 'skel';
		$this->Task->expects($this->at(0))->method('in')->will($this->returnValue('y'));
		$this->Task->bake($this->Task->path . 'bake_test_app', $skel);
	}

/**
 * test bake() method and directory creation.
 *
 * @return void
 */
	public function testBake() {
		$this->_setupTestProject();
		$path = $this->Task->path . 'bake_test_app';

		$this->assertTrue(is_dir($path), 'No project dir %s');
		$dirs = array(
			'config',
			'config' . DS . 'schema',
			'Console',
			'Console' . DS . 'Command',
			'Console' . DS . 'Command' . DS . 'Task',
			'Controller', 
			'Model', 
			'View',
			'View' . DS . 'Helper',
			'tests',
			'tests' . DS . 'Case', 
			'tests' . DS . 'Case' . DS . 'Model',
			'tests' . DS . 'Fixture',
			'tmp',
			'webroot',
			'webroot' . DS . 'js',
			'webroot' . DS . 'css',
		);
		foreach ($dirs as $dir) {
			$this->assertTrue(is_dir($path . DS . $dir), 'Missing ' . $dir);
		}
	}

/**
 * test bake() method with -empty flag,  directory creation and empty files.
 *
 * @return void
 */
	public function testBakeEmptyFlag() {
		$this->Task->params['empty'] = true;
		$this->_setupTestProject();
		$path = $this->Task->path . 'bake_test_app';
	
		$empty = array(
			'Console' . DS . 'Command' . DS . 'Task',
			'Controller' . DS . 'Component', 
			'Model' . DS . 'Behavior', 
			'View' . DS . 'Helper',
			'View' . DS . 'errors',
			'View' . DS . 'scaffolds',
			'tests' . DS . 'Case' . DS . 'Model', 
			'tests' . DS . 'Case' . DS . 'Controller',
			'tests' . DS . 'Case' . DS . 'View' . DS . 'Helper',
			'tests' . DS . 'Fixture',
			'webroot' . DS . 'js'
		);
	
		foreach ($empty as $dir) {
			$this->assertTrue(is_file($path . DS . $dir . DS . 'empty'), 'Missing empty file in ' . $dir);
		}
	}

/**
 * test generation of Security.salt
 *
 * @return void
 */
	public function testSecuritySaltGeneration() {
		$this->_setupTestProject();

		$path = $this->Task->path . 'bake_test_app' . DS;
		$result = $this->Task->securitySalt($path);
		$this->assertTrue($result);

		$file = new File($path . 'config' . DS . 'core.php');
		$contents = $file->read();
		$this->assertNoPattern('/DYhG93b0qyJfIxfs2guVoUubWwvniR2G0FgaC9mi/', $contents, 'Default Salt left behind. %s');
	}

/**
 * test generation of Security.cipherSeed
 *
 * @return void
 */
	public function testSecurityCipherSeedGeneration() {
		$this->_setupTestProject();

		$path = $this->Task->path . 'bake_test_app' . DS;
		$result = $this->Task->securityCipherSeed($path);
		$this->assertTrue($result);

		$file = new File($path . 'config' . DS . 'core.php');
		$contents = $file->read();
		$this->assertNoPattern('/76859309657453542496749683645/', $contents, 'Default CipherSeed left behind. %s');
	}

/**
 * Test that index.php is generated correctly.
 *
 * @return void
 */
	public function testIndexPhpGeneration() {
		$this->_setupTestProject();

		$path = $this->Task->path . 'bake_test_app' . DS;
		$this->Task->corePath($path);

		$file = new File($path . 'webroot' . DS . 'index.php');
		$contents = $file->read();
		$this->assertNoPattern('/define\(\'CAKE_CORE_INCLUDE_PATH\', \'ROOT/', $contents);

		$file = new File($path . 'webroot' . DS . 'test.php');
		$contents = $file->read();
		$this->assertNoPattern('/define\(\'CAKE_CORE_INCLUDE_PATH\', \'ROOT/', $contents);
	}

/**
 * test getPrefix method, and that it returns Routing.prefix or writes to config file.
 *
 * @return void
 */
	public function testGetPrefix() {
		Configure::write('Routing.prefixes', array('admin'));
		$result = $this->Task->getPrefix();
		$this->assertEqual($result, 'admin_');

		Configure::write('Routing.prefixes', null);
		$this->_setupTestProject();
		$this->Task->configPath = $this->Task->path . 'bake_test_app' . DS . 'config' . DS;
		$this->Task->expects($this->once())->method('in')->will($this->returnValue('super_duper_admin'));

		$result = $this->Task->getPrefix();
		$this->assertEqual($result, 'super_duper_admin_');

		$file = new File($this->Task->configPath . 'core.php');
		$file->delete();
	}

/**
 * test cakeAdmin() writing core.php
 *
 * @return void
 */
	public function testCakeAdmin() {
		$file = new File(CONFIGS . 'core.php');
		$contents = $file->read();;
		$file = new File(TMP . 'tests' . DS . 'core.php');
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
 */
	public function testGetPrefixWithMultiplePrefixes() {
		Configure::write('Routing.prefixes', array('admin', 'ninja', 'shinobi'));
		$this->_setupTestProject();
		$this->Task->configPath = $this->Task->path . 'bake_test_app' . DS . 'config' . DS;
		$this->Task->expects($this->once())->method('in')->will($this->returnValue(2));

		$result = $this->Task->getPrefix();
		$this->assertEqual($result, 'ninja_');
	}

/**
 * Test execute method with one param to destination folder.
 *
 * @return void
 */
	public function testExecute() {
		$this->Task->params['skel'] = LIBS . 'Console' . DS. 'templates' . DS . 'skel';
		$this->Task->params['working'] = TMP . 'tests' . DS;

		$path = $this->Task->path . 'bake_test_app';
		$this->Task->expects($this->at(0))->method('in')->will($this->returnValue($path));
		$this->Task->expects($this->at(1))->method('in')->will($this->returnValue('y'));

		$this->Task->execute();
		$this->assertTrue(is_dir($path), 'No project dir');
		$this->assertTrue(is_dir($path . DS . 'Controller'), 'No controllers dir ');
		$this->assertTrue(is_dir($path . DS . 'Controller' . DS .'Component'), 'No components dir ');
		$this->assertTrue(is_dir($path . DS . 'Model'), 'No models dir');
		$this->assertTrue(is_dir($path . DS . 'View'), 'No views dir');
		$this->assertTrue(is_dir($path . DS . 'View' . DS . 'Helper'), 'No helpers dir');
		$this->assertTrue(is_dir($path . DS . 'tests'), 'No tests dir');
		$this->assertTrue(is_dir($path . DS . 'tests' . DS . 'Case'), 'No cases dir');
		$this->assertTrue(is_dir($path . DS . 'tests' . DS . 'Fixture'), 'No fixtures dir');
	}

/**
 * test console path
 *
 * @return void
 */
	function testConsolePath() {
		$this->_setupTestProject();
		
		$path = $this->Task->path . 'bake_test_app' . DS;
		$result = $this->Task->consolePath($path);
		$this->assertTrue($result);

		$file = new File($path . 'Console' . DS . 'cake.php');
		$contents = $file->read();
		$this->assertNoPattern('/__CAKE_PATH__/', $contents, 'Console path placeholder left behind.');
	}
}
