<?php
/**
 * CakePHP : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP Project
 * @since         CakePHP v 1.3.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Console\Command\Task;

use Cake\Console\Command\Task\ProjectTask;
use Cake\Core\Configure;
use Cake\TestSuite\TestCase;
use Cake\Utility\File;
use Cake\Utility\Folder;

/**
 * ProjectTask Test class
 *
 * @package       Cake.Test.Case.Console.Command.Task
 */
class ProjectTaskTest extends TestCase {

/**
 * setUp method
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();
		$out = $this->getMock('Cake\Console\ConsoleOutput', array(), array(), '', false);
		$in = $this->getMock('Cake\Console\ConsoleInput', array(), array(), '', false);

		$this->Task = $this->getMock('Cake\Console\Command\Task\ProjectTask',
			array('in', 'err', 'createFile', '_stop'),
			array($out, $out, $in)
		);
		$this->Task->path = TMP . 'tests/';
	}

/**
 * tearDown method
 *
 * @return void
 */
	public function tearDown() {
		parent::tearDown();

		$Folder = new Folder($this->Task->path . 'BakeTestApp');
		$Folder->delete();
		unset($this->Task);
	}

/**
 * creates a test project that is used for testing project task.
 *
 * @return void
 */
	protected function _setupTestProject() {
		$skel = CAKE . 'Console/Templates/skel';
		$this->Task->expects($this->at(0))->method('in')->will($this->returnValue('y'));
		$this->Task->bake($this->Task->path . 'BakeTestApp', $skel);
	}

/**
 * test bake() method and directory creation.
 *
 * @return void
 */
	public function testBake() {
		$this->_setupTestProject();
		$path = $this->Task->path . 'BakeTestApp';

		$this->assertTrue(is_dir($path), 'No project dir %s');
		$dirs = array(
			'Config',
			'Config/Schema',
			'Console',
			'Console/Command',
			'Console/Templates',
			'Console/Command/Task',
			'Controller',
			'Controller/Component',
			'Locale',
			'Model',
			'Model/Behavior',
			'Model/Datasource',
			'Plugin',
			'Test',
			'Test/TestCase',
			'Test/TestCase/Controller',
			'Test/TestCase/Controller/Component',
			'Test/TestCase/Model',
			'Test/TestCase/Model/Behavior',
			'Test/TestCase/View',
			'Test/TestCase/View/Helper',
			'Test/Fixture',
			'vendor',
			'View',
			'View/Helper',
			'tmp',
			'tmp/cache',
			'tmp/cache/models',
			'tmp/cache/persistent',
			'tmp/cache/views',
			'tmp/logs',
			'tmp/sessions',
			'tmp/tests',
			'webroot',
			'webroot/css',
			'webroot/files',
			'webroot/img',
			'webroot/js',
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
		$path = $this->Task->args[0] = TMP . 'tests/BakeTestApp';
		$this->Task->params['skel'] = CAKE . 'Console/Templates/skel';
		$this->Task->expects($this->at(0))->method('in')->will($this->returnValue('y'));
		$this->Task->execute();

		$this->assertTrue(is_dir($this->Task->args[0]), 'No project dir');
		$File = new File($path . DS . 'Config/paths.php');
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

		$path = $this->Task->args[0] = TMP . 'tests/BakeTestApp';
		$this->Task->params['skel'] = CAKE . 'Console/Templates/skel';
		$this->Task->expects($this->at(0))->method('in')->will($this->returnValue('y'));
		$this->Task->execute();

		$this->assertTrue(is_dir($this->Task->args[0]), 'No project dir');
		$contents = file_get_contents($path . DS . 'Config/paths.php');
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
		$path = $this->Task->path . 'BakeTestApp';

		$empty = array(
			'Console/Command/Task' => 'empty',
			'Controller/Component' => 'empty',
			'Lib' => 'empty',
			'Model/Behavior' => 'empty',
			'Model/Datasource' => 'empty',
			'Plugin' => 'empty',
			'Test/TestCase/Model/Behavior' => 'empty',
			'Test/TestCase/Controller/Component' => 'empty',
			'Test/TestCase/View/Helper' => 'empty',
			'Test/Fixture' => 'empty',
			'vendor' => 'empty',
			'View/Elements' => 'empty',
			'View/Scaffolds' => 'empty',
			'tmp/cache/models' => 'empty',
			'tmp/cache/persistent' => 'empty',
			'tmp/cache/views' => 'empty',
			'tmp/logs' => 'empty',
			'tmp/sessions' => 'empty',
			'tmp/tests' => 'empty',
			'webroot/js' => 'empty',
			'webroot/files' => 'empty'
		);

		foreach ($empty as $dir => $file) {
			$this->assertTrue(is_file($path . DS . $dir . DS . $file), sprintf('Missing %s file in %s', $file, $dir));
		}
	}

/**
 * test App.namespace and namespace
 *
 * @return void
 */
	public function testAppNamespace() {
		$this->_setupTestProject();

		$path = $this->Task->path . 'BakeTestApp/';
		$result = $this->Task->appNamespace($path);
		$this->assertTrue($result);

		$File = new File($path . 'Config/app.php');
		$contents = $File->read();
		$this->assertRegExp('/\$namespace = \'BakeTestApp\';/', $contents);

		$files = array(
			'Config/bootstrap',
			'Controller/AppController',
			'Model/AppModel',
			'View/Helper/AppHelper'
		);
		foreach ($files as $file) {
			$file = $path . $file . '.php';
			$this->assertRegExp('/namespace BakeTestApp\\\/', $contents);
		}
	}

/**
 * test generation of Security.salt
 *
 * @return void
 */
	public function testSecuritySaltGeneration() {
		$this->_setupTestProject();

		$path = $this->Task->path . 'BakeTestApp/';
		$result = $this->Task->securitySalt($path);
		$this->assertTrue($result);

		$File = new File($path . 'Config/app.php');
		$contents = $File->read();
		$this->assertNotRegExp('/DYhG93b0qyJfIxfs2guVoUubWwvniR2G0FgaC9mi/', $contents, 'Default Salt left behind. %s');
	}

/**
 * test generation of cache prefix
 *
 * @return void
 */
	public function testCachePrefixGeneration() {
		$this->_setupTestProject();

		$path = $this->Task->path . 'BakeTestApp/';
		$result = $this->Task->cachePrefix($path);
		$this->assertTrue($result);

		$File = new File($path . 'Config/cache.php');
		$contents = $File->read();
		$this->assertRegExp('/\$prefix = \'.+\';/', $contents, '$prefix is not defined');
		$this->assertNotRegExp('/\$prefix = \'myapp_\';/', $contents, 'Default cache prefix left behind. %s');
	}

/**
 * Test that Config/paths.php is generated correctly.
 *
 * @return void
 */
	public function testIndexPhpGeneration() {
		$this->_setupTestProject();

		$path = $this->Task->path . 'BakeTestApp/';
		$this->Task->corePath($path);

		$File = new File($path . 'Config/paths.php');
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
		$this->Task->configPath = $this->Task->path . 'BakeTestApp/Config/';
		$this->Task->expects($this->once())->method('in')->will($this->returnValue('super_duper_admin'));

		$result = $this->Task->getPrefix();
		$this->assertEquals('super_duper_admin_', $result);

		$File = new File($this->Task->configPath . 'routes.php');
		$File->delete();
	}

/**
 * test cakeAdmin() writing routes.php
 *
 * @return void
 */
	public function testCakeAdmin() {
		$File = new File(APP . 'Config/routes.php');
		$contents = $File->read();
		$File = new File(TMP . 'tests/routes.php');
		$File->write($contents);

		Configure::write('Routing.prefixes', null);
		$this->Task->configPath = TMP . 'tests/';
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
		$this->Task->configPath = $this->Task->path . 'BakeTestApp/Config/';
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
		$this->Task->params['skel'] = CAKE . 'Console/Templates/skel';
		$this->Task->params['working'] = TMP . 'tests/';

		$invalidPath = $this->Task->path . 'bake-test-app';
		$path = $this->Task->path . 'BakeTestApp';
		$this->Task->expects($this->at(0))->method('in')->will($this->returnValue($invalidPath));
		$this->Task->expects($this->at(2))->method('in')->will($this->returnValue($path));
		$this->Task->expects($this->at(3))->method('in')->will($this->returnValue('y'));

		$this->Task->execute();
		$this->assertTrue(is_dir($path), 'No project dir');
		$this->assertTrue(is_dir($path . DS . 'Controller'), 'No controllers dir ');
		$this->assertTrue(is_dir($path . DS . 'Controller/Component'), 'No components dir ');
		$this->assertTrue(is_dir($path . DS . 'Model'), 'No models dir');
		$this->assertTrue(is_dir($path . DS . 'View'), 'No views dir');
		$this->assertTrue(is_dir($path . DS . 'View/Helper'), 'No helpers dir');
		$this->assertTrue(is_dir($path . DS . 'Test'), 'No tests dir');
		$this->assertTrue(is_dir($path . DS . 'Test/TestCase'), 'No cases dir');
		$this->assertTrue(is_dir($path . DS . 'Test/Fixture'), 'No fixtures dir');
	}
}
