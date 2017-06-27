<?php
/**
 * ProjectTask Test file
 *
 * Test Case for project generation shell task
 *
 * CakePHP : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP Project
 * @package       Cake.Test.Case.Console.Command.Task
 * @since         CakePHP v 1.3.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
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
 * @package       Cake.Test.Case.Console.Command.Task
 */
class ProjectTaskTest extends CakeTestCase {

/**
 * setUp method
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
 * tearDown method
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
		$skel = CAKE . 'Console' . DS . 'Templates' . DS . 'skel';
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
			'Config',
			'Config' . DS . 'Schema',
			'Console',
			'Console' . DS . 'Command',
			'Console' . DS . 'Templates',
			'Console' . DS . 'Command' . DS . 'Task',
			'Controller',
			'Controller' . DS . 'Component',
			'Locale',
			'Model',
			'Model' . DS . 'Behavior',
			'Model' . DS . 'Datasource',
			'Plugin',
			'Test',
			'Test' . DS . 'Case',
			'Test' . DS . 'Case' . DS . 'Controller',
			'Test' . DS . 'Case' . DS . 'Controller' . DS . 'Component',
			'Test' . DS . 'Case' . DS . 'Model',
			'Test' . DS . 'Case' . DS . 'Model' . DS . 'Behavior',
			'Test' . DS . 'Fixture',
			'Vendor',
			'View',
			'View' . DS . 'Helper',
			'tmp',
			'tmp' . DS . 'cache',
			'tmp' . DS . 'cache' . DS . 'models',
			'tmp' . DS . 'cache' . DS . 'persistent',
			'tmp' . DS . 'cache' . DS . 'views',
			'tmp' . DS . 'logs',
			'tmp' . DS . 'sessions',
			'tmp' . DS . 'tests',
			'webroot',
			'webroot' . DS . 'css',
			'webroot' . DS . 'files',
			'webroot' . DS . 'img',
			'webroot' . DS . 'js',

		);
		foreach ($dirs as $dir) {
			$this->assertTrue(is_dir($path . DS . $dir), 'Missing ' . $dir);
		}
	}

/**
 * test bake with an absolute path.
 *
 * @return void
 */
	public function testExecuteWithAbsolutePath() {
		$path = $this->Task->args[0] = TMP . 'tests' . DS . 'bake_test_app';
		$this->Task->params['skel'] = CAKE . 'Console' . DS . 'Templates' . DS . 'skel';
		$this->Task->expects($this->at(0))->method('in')->will($this->returnValue('y'));
		$this->Task->execute();

		$this->assertTrue(is_dir($this->Task->args[0]), 'No project dir');
		$File = new File($path . DS . 'webroot' . DS . 'index.php');
		$contents = $File->read();
		$this->assertRegExp('/define\(\'CAKE_CORE_INCLUDE_PATH\', .*?DS/', $contents);
		$File = new File($path . DS . 'webroot' . DS . 'test.php');
		$contents = $File->read();
		$this->assertRegExp('/define\(\'CAKE_CORE_INCLUDE_PATH\', .*?DS/', $contents);
	}

/**
 * test bake with CakePHP on the include path. The constants should remain commented out.
 *
 * @return void
 */
	public function testExecuteWithCakeOnIncludePath() {
		if (!function_exists('ini_set')) {
			$this->markTestAsSkipped('Not access to ini_set, cannot proceed.');
		}
		$restore = ini_get('include_path');
		ini_set('include_path', CAKE_CORE_INCLUDE_PATH . PATH_SEPARATOR . $restore);

		$path = $this->Task->args[0] = TMP . 'tests' . DS . 'bake_test_app';
		$this->Task->params['skel'] = CAKE . 'Console' . DS . 'Templates' . DS . 'skel';
		$this->Task->expects($this->at(0))->method('in')->will($this->returnValue('y'));
		$this->Task->execute();

		$this->assertTrue(is_dir($this->Task->args[0]), 'No project dir');
		$contents = file_get_contents($path . DS . 'webroot' . DS . 'index.php');
		$this->assertRegExp('#//define\(\'CAKE_CORE_INCLUDE_PATH#', $contents);

		$contents = file_get_contents($path . DS . 'webroot' . DS . 'test.php');
		$this->assertRegExp('#//define\(\'CAKE_CORE_INCLUDE_PATH#', $contents);

		ini_set('include_path', $restore);
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
			'Console' . DS . 'Command' . DS . 'Task' => 'empty',
			'Controller' . DS . 'Component' => 'empty',
			'Lib' => 'empty',
			'Model' . DS . 'Behavior' => 'empty',
			'Model' . DS . 'Datasource' => 'empty',
			'Plugin' => 'empty',
			'Test' . DS . 'Case' . DS . 'Model' . DS . 'Behavior' => 'empty',
			'Test' . DS . 'Case' . DS . 'Controller' . DS . 'Component' => 'empty',
			'Test' . DS . 'Case' . DS . 'View' . DS . 'Helper' => 'empty',
			'Test' . DS . 'Fixture' => 'empty',
			'Vendor' => 'empty',
			'View' . DS . 'Scaffolds' => 'empty',
			'tmp' . DS . 'cache' . DS . 'models' => 'empty',
			'tmp' . DS . 'cache' . DS . 'persistent' => 'empty',
			'tmp' . DS . 'cache' . DS . 'views' => 'empty',
			'tmp' . DS . 'logs' => 'empty',
			'tmp' . DS . 'sessions' => 'empty',
			'tmp' . DS . 'tests' => 'empty',
			'webroot' . DS . 'js' => 'empty',
			'webroot' . DS . 'files' => 'empty'
		);

		foreach ($empty as $dir => $file) {
			$this->assertTrue(is_file($path . DS . $dir . DS . $file), sprintf('Missing %s file in %s', $file, $dir));
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

		$File = new File($path . 'Config' . DS . 'core.php');
		$contents = $File->read();
		$this->assertNotRegExp('/DYhG93b0qyJfIxfs2guVoUubWwvniR2G0FgaC9mi/', $contents, 'Default Salt left behind. %s');
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

		$File = new File($path . 'Config' . DS . 'core.php');
		$contents = $File->read();
		$this->assertNotRegExp('/76859309657453542496749683645/', $contents, 'Default CipherSeed left behind. %s');
	}

/**
 * test generation of cache prefix
 *
 * @return void
 */
	public function testCachePrefixGeneration() {
		$this->_setupTestProject();

		$path = $this->Task->path . 'bake_test_app' . DS;
		$result = $this->Task->cachePrefix($path);
		$this->assertTrue($result);

		$File = new File($path . 'Config' . DS . 'core.php');
		$contents = $File->read();
		$this->assertRegExp('/\$prefix = \'.+\';/', $contents, '$prefix is not defined');
		$this->assertNotRegExp('/\$prefix = \'myapp_\';/', $contents, 'Default cache prefix left behind. %s');
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

		$File = new File($path . 'webroot' . DS . 'index.php');
		$contents = $File->read();
		$this->assertNotRegExp('/define\(\'CAKE_CORE_INCLUDE_PATH\', ROOT/', $contents);
		$File = new File($path . 'webroot' . DS . 'test.php');
		$contents = $File->read();
		$this->assertNotRegExp('/define\(\'CAKE_CORE_INCLUDE_PATH\', ROOT/', $contents);
	}

/**
 * test getPrefix method, and that it returns Routing.prefix or writes to config file.
 *
 * @return void
 */
	public function testGetPrefix() {
		Configure::write('Routing.prefixes', array('admin'));
		$result = $this->Task->getPrefix();
		$this->assertEquals('admin_', $result);

		Configure::write('Routing.prefixes', null);
		$this->_setupTestProject();
		$this->Task->configPath = $this->Task->path . 'bake_test_app' . DS . 'Config' . DS;
		$this->Task->expects($this->once())->method('in')->will($this->returnValue('super_duper_admin'));

		$result = $this->Task->getPrefix();
		$this->assertEquals('super_duper_admin_', $result);

		$File = new File($this->Task->configPath . 'core.php');
		$File->delete();
	}

/**
 * test cakeAdmin() writing core.php
 *
 * @return void
 */
	public function testCakeAdmin() {
		$File = new File(CONFIG . 'core.php');
		$contents = $File->read();
		$File = new File(TMP . 'tests' . DS . 'core.php');
		$File->write($contents);

		Configure::write('Routing.prefixes', null);
		$this->Task->configPath = TMP . 'tests' . DS;
		$result = $this->Task->cakeAdmin('my_prefix');
		$this->assertTrue($result);

		$this->assertEquals(Configure::read('Routing.prefixes'), array('my_prefix'));
		$File->delete();
	}

/**
 * test getting the prefix with more than one prefix setup
 *
 * @return void
 */
	public function testGetPrefixWithMultiplePrefixes() {
		Configure::write('Routing.prefixes', array('admin', 'ninja', 'shinobi'));
		$this->_setupTestProject();
		$this->Task->configPath = $this->Task->path . 'bake_test_app' . DS . 'Config' . DS;
		$this->Task->expects($this->once())->method('in')->will($this->returnValue(2));

		$result = $this->Task->getPrefix();
		$this->assertEquals('ninja_', $result);
	}

/**
 * Test execute method with one param to destination folder.
 *
 * @return void
 */
	public function testExecute() {
		$this->Task->params['skel'] = CAKE . 'Console' . DS . 'Templates' . DS . 'skel';
		$this->Task->params['working'] = TMP . 'tests' . DS;

		$path = $this->Task->path . 'bake_test_app';
		$this->Task->expects($this->at(0))->method('in')->will($this->returnValue($path));
		$this->Task->expects($this->at(1))->method('in')->will($this->returnValue('y'));

		$this->Task->execute();
		$this->assertTrue(is_dir($path), 'No project dir');
		$this->assertTrue(is_dir($path . DS . 'Controller'), 'No controllers dir ');
		$this->assertTrue(is_dir($path . DS . 'Controller' . DS . 'Component'), 'No components dir ');
		$this->assertTrue(is_dir($path . DS . 'Model'), 'No models dir');
		$this->assertTrue(is_dir($path . DS . 'View'), 'No views dir');
		$this->assertTrue(is_dir($path . DS . 'View' . DS . 'Helper'), 'No helpers dir');
		$this->assertTrue(is_dir($path . DS . 'Test'), 'No tests dir');
		$this->assertTrue(is_dir($path . DS . 'Test' . DS . 'Case'), 'No cases dir');
		$this->assertTrue(is_dir($path . DS . 'Test' . DS . 'Fixture'), 'No fixtures dir');
	}

/**
 * test console path
 *
 * @return void
 */
	public function testConsolePath() {
		$this->_setupTestProject();

		$path = $this->Task->path . 'bake_test_app' . DS;
		$result = $this->Task->consolePath($path);
		$this->assertTrue($result);

		$File = new File($path . 'Console' . DS . 'cake.php');
		$contents = $File->read();
		$this->assertNotRegExp('/__CAKE_PATH__/', $contents, 'Console path placeholder left behind.');
	}
}
