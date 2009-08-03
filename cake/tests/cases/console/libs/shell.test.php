<?php
/* SVN FILE: $Id$ */
/**
 * ShellTest file
 *
 * Test Case for Shell
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
 * @subpackage    cake.tests.cases.console.libs
 * @since         CakePHP v 1.2.0.7726
 * @version       $Revision$
 * @modifiedby    $LastChangedBy$
 * @lastmodified  $Date$
 * @license       http://www.opensource.org/licenses/mit-license.php The MIT License
 */
App::import('Core', array('Shell', 'Folder'));

if (!defined('DISABLE_AUTO_DISPATCH')) {
	define('DISABLE_AUTO_DISPATCH', true);
}

if (!class_exists('ShellDispatcher')) {
	ob_start();
	$argv = false;
	require CAKE . 'console' .  DS . 'cake.php';
	ob_end_clean();
}

Mock::generatePartial('ShellDispatcher', 'TestShellMockShellDispatcher', array(
	'getInput', 'stdout', 'stderr', '_stop', '_initEnvironment'
));
/**
 * TestShell class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.console.libs
 */
class TestShell extends Shell {
/**
 * Fixtures used in this test case
 *
 * @var name
 * @access public
 */	
	var $name = 'TestShell';
}
/**
 * TestAppleTask class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.console.libs
 */
class TestAppleTask extends Shell {
}
/**
 * TestBananaTask class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.console.libs
 */
class TestBananaTask extends Shell {
}
/**
 * ShellTest class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.console.libs
 */
class ShellTest extends CakeTestCase {
/**
 * Fixtures used in this test case
 *
 * @var array
 * @access public
 */
	var $fixtures = array(
		'core.post', 'core.comment', 'core.article', 'core.user',
		'core.tag', 'core.articles_tag', 'core.attachment'
	);
/**
 * setUp method
 *
 * @return void
 * @access public
 */
	function setUp() {
		$this->Dispatcher =& new TestShellMockShellDispatcher();
		$this->Shell =& new TestShell($this->Dispatcher);
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
 * testConstruct method
 *
 * @return void
 * @access public
 */
	function testConstruct() {
		$this->assertIsA($this->Shell->Dispatch, 'TestShellMockShellDispatcher');
		$this->assertEqual($this->Shell->name, 'TestShell');
		$this->assertEqual($this->Shell->alias, 'TestShell');
	}
/**
 * testInitialize method
 *
 * @return void
 * @access public
 */
	function testInitialize() {
		$_back = array(
			'modelPaths' => Configure::read('modelPaths'),
			'pluginPaths' => Configure::read('pluginPaths'),
			'viewPaths' => Configure::read('viewPaths'),
		);
		Configure::write('pluginPaths', array(TEST_CAKE_CORE_INCLUDE_PATH . 'tests' . DS . 'test_app' . DS . 'plugins' . DS));
		Configure::write('modelPaths', array(TEST_CAKE_CORE_INCLUDE_PATH . 'tests' . DS . 'test_app' . DS . 'models' . DS));
		$this->Shell->uses = array('TestPlugin.TestPluginPost');
		$this->Shell->initialize();

		$this->assertTrue(isset($this->Shell->TestPluginPost));
		$this->assertIsA($this->Shell->TestPluginPost, 'TestPluginPost');
		$this->assertEqual($this->Shell->modelClass, 'TestPluginPost');

		$this->Shell->uses = array('Comment');
		$this->Shell->initialize();
		$this->assertTrue(isset($this->Shell->Comment));
		$this->assertIsA($this->Shell->Comment, 'Comment');
		$this->assertEqual($this->Shell->modelClass, 'Comment');

		$this->Shell->uses = true;
		$this->Shell->initialize();
		$this->assertTrue(isset($this->Shell->AppModel));
		$this->assertIsA($this->Shell->AppModel, 'AppModel');

		Configure::write('pluginPaths', $_back['pluginPaths']);
		Configure::write('modelPaths', $_back['modelPaths']);
	}
/**
 * testOut method
 *
 * @return void
 * @access public
 */
	function testOut() {
		$this->Shell->Dispatch->expectAt(0, 'stdout', array('Just a test', true));
		$this->Shell->out('Just a test');

		$this->Shell->Dispatch->expectAt(1, 'stdout', array("Just\na\ntest\n", true));
		$this->Shell->out(array('Just', 'a', 'test'));
	}
/**
 * testIn method
 *
 * @return void
 * @access public
 */
	function testIn() {
		$this->Shell->Dispatch->setReturnValueAt(0, 'getInput', 'n');
		$this->Shell->Dispatch->expectAt(0, 'getInput', array('Just a test?', array('y', 'n'), 'n'));
		$result = $this->Shell->in('Just a test?', array('y', 'n'), 'n');
		$this->assertEqual($result, 'n');

		$this->Shell->Dispatch->setReturnValueAt(1, 'getInput', 'Y');
		$this->Shell->Dispatch->expectAt(1, 'getInput', array('Just a test?', array('y', 'n'), 'n'));
		$result = $this->Shell->in('Just a test?', array('y', 'n'), 'n');
		$this->assertEqual($result, 'Y');

		$this->Shell->Dispatch->setReturnValueAt(2, 'getInput', 'y');
		$this->Shell->Dispatch->expectAt(2, 'getInput', array('Just a test?', 'y,n', 'n'));
		$result = $this->Shell->in('Just a test?', 'y,n', 'n');
		$this->assertEqual($result, 'y');

		$this->Shell->Dispatch->setReturnValueAt(3, 'getInput', 'y');
		$this->Shell->Dispatch->expectAt(3, 'getInput', array('Just a test?', 'y/n', 'n'));
		$result = $this->Shell->in('Just a test?', 'y/n', 'n');
		$this->assertEqual($result, 'y');

		$this->Shell->Dispatch->setReturnValueAt(4, 'getInput', 'y');
		$this->Shell->Dispatch->expectAt(4, 'getInput', array('Just a test?', 'y', 'y'));
		$result = $this->Shell->in('Just a test?', 'y', 'y');
		$this->assertEqual($result, 'y');

		$this->Shell->interactive = false;

		$result = $this->Shell->in('Just a test?', 'y/n', 'n');
		$this->assertEqual($result, 'n');
	}
/**
 * testLoadTasks method
 *
 * @return void
 * @access public
 */
	function testLoadTasks() {
		$this->assertTrue($this->Shell->loadTasks());

		$this->Shell->tasks = null;
		$this->assertTrue($this->Shell->loadTasks());

		$this->Shell->tasks = false;
		$this->assertTrue($this->Shell->loadTasks());

		$this->Shell->tasks = true;
		$this->assertTrue($this->Shell->loadTasks());

		$this->Shell->tasks = array();
		$this->assertTrue($this->Shell->loadTasks());

		// Fatal Error
		// $this->Shell->tasks = 'TestIDontExist';
		// $this->assertFalse($this->Shell->loadTasks());
		// $this->assertFalse(isset($this->Shell->TestIDontExist));

		$this->Shell->tasks = 'TestApple';
		$this->assertTrue($this->Shell->loadTasks());
		$this->assertIsA($this->Shell->TestApple, 'TestAppleTask');

		$this->Shell->tasks = 'TestBanana';
		$this->assertTrue($this->Shell->loadTasks());
		$this->assertIsA($this->Shell->TestApple, 'TestAppleTask');
		$this->assertIsA($this->Shell->TestBanana, 'TestBananaTask');

		unset($this->Shell->ShellTestApple, $this->Shell->TestBanana);

		$this->Shell->tasks = array('TestApple', 'TestBanana');
		$this->assertTrue($this->Shell->loadTasks());
		$this->assertIsA($this->Shell->TestApple, 'TestAppleTask');
		$this->assertIsA($this->Shell->TestBanana, 'TestBananaTask');
	}
/**
 * testShortPath method
 *
 * @return void
 * @access public
 */
	function testShortPath() {
		$path = $expected = DS . 'tmp' . DS . 'ab' . DS . 'cd';
		$this->assertEqual($this->Shell->shortPath($path), $expected);

		$path = $expected = DS . 'tmp' . DS . 'ab' . DS . 'cd' . DS ;
		$this->assertEqual($this->Shell->shortPath($path), $expected);

		$path = $expected = DS . 'tmp' . DS . 'ab' . DS . 'index.php';
		$this->assertEqual($this->Shell->shortPath($path), $expected);

		// Shell::shortPath needs Folder::realpath
		// $path = DS . 'tmp' . DS . 'ab' . DS . '..' . DS . 'cd';
		// $expected = DS . 'tmp' . DS . 'cd';
		// $this->assertEqual($this->Shell->shortPath($path), $expected);

		$path = DS . 'tmp' . DS . 'ab' . DS . DS . 'cd';
		$expected = DS . 'tmp' . DS . 'ab' . DS . 'cd';
		$this->assertEqual($this->Shell->shortPath($path), $expected);

		$path = 'tmp' . DS . 'ab';
		$expected = 'tmp' . DS . 'ab';
		$this->assertEqual($this->Shell->shortPath($path), $expected);

		$path = 'tmp' . DS . 'ab';
		$expected = 'tmp' . DS . 'ab';
		$this->assertEqual($this->Shell->shortPath($path), $expected);

		$path = APP;
		$expected = DS . basename(APP) . DS;
		$this->assertEqual($this->Shell->shortPath($path), $expected);

		$path = APP . 'index.php';
		$expected = DS . basename(APP) . DS . 'index.php';
		$this->assertEqual($this->Shell->shortPath($path), $expected);
	}
/**
 * testCreateFile method
 *
 * @return void
 * @access public
 */
	function testCreateFile() {
		$this->skipIf(DIRECTORY_SEPARATOR === '\\', '%s Not supported on Windows');

		$path = TMP . 'shell_test';
		$file = $path . DS . 'file1.php';

		new Folder($path, true);

		$this->Shell->interactive = false;

		$contents = "<?php\necho 'test';\n\$te = 'st';\n?>";
		$result = $this->Shell->createFile($file, $contents);
		$this->assertTrue($result);
		$this->assertTrue(file_exists($file));
		$this->assertEqual(file_get_contents($file), $contents);

		$contents = "<?php\necho 'another test';\n\$te = 'st';\n?>";
		$result = $this->Shell->createFile($file, $contents);
		$this->assertTrue($result);
		$this->assertTrue(file_exists($file));
		$this->assertEqual(file_get_contents($file), $contents);

		$this->Shell->interactive = true;

		$this->Shell->Dispatch->setReturnValueAt(0, 'getInput', 'n');
		$this->Shell->Dispatch->expectAt(1, 'stdout', array('File exists, overwrite?', '*'));

		$contents = "<?php\necho 'yet another test';\n\$te = 'st';\n?>";
		$result = $this->Shell->createFile($file, $contents);
		$this->assertFalse($result);
		$this->assertTrue(file_exists($file));
		$this->assertNotEqual(file_get_contents($file), $contents);

		$this->Shell->Dispatch->setReturnValueAt(1, 'getInput', 'y');
		$this->Shell->Dispatch->expectAt(3, 'stdout', array('File exists, overwrite?', '*'));

		$result = $this->Shell->createFile($file, $contents);
		$this->assertTrue($result);
		$this->assertTrue(file_exists($file));
		$this->assertEqual(file_get_contents($file), $contents);

		$Folder = new Folder($path);
		$Folder->delete();
	}
/**
 * testCreateFileWindows method
 *
 * @return void
 * @access public
 */
	function testCreateFileWindows() {
		$this->skipUnless(DIRECTORY_SEPARATOR === '\\', '%s Supported on Windows only');

		$path = TMP . 'shell_test';
		$file = $path . DS . 'file1.php';

		new Folder($path, true);

		$this->Shell->interactive = false;

		$contents = "<?php\necho 'test';\r\n\$te = 'st';\r\n?>";
		$result = $this->Shell->createFile($file, $contents);
		$this->assertTrue($result);
		$this->assertTrue(file_exists($file));
		$this->assertEqual(file_get_contents($file), $contents);

		$contents = "<?php\necho 'another test';\r\n\$te = 'st';\r\n?>";
		$result = $this->Shell->createFile($file, $contents);
		$this->assertTrue($result);
		$this->assertTrue(file_exists($file));
		$this->assertEqual(file_get_contents($file), $contents);

		$this->Shell->interactive = true;

		$this->Shell->Dispatch->setReturnValueAt(0, 'getInput', 'n');
		$this->Shell->Dispatch->expectAt(1, 'stdout', array('File exists, overwrite?'));

		$contents = "<?php\necho 'yet another test';\r\n\$te = 'st';\r\n?>";
		$result = $this->Shell->createFile($file, $contents);
		$this->assertFalse($result);
		$this->assertTrue(file_exists($file));
		$this->assertNotEqual(file_get_contents($file), $contents);

		$this->Shell->Dispatch->setReturnValueAt(1, 'getInput', 'y');
		$this->Shell->Dispatch->expectAt(3, 'stdout', array('File exists, overwrite?'));

		$result = $this->Shell->createFile($file, $contents);
		$this->assertTrue($result);
		$this->assertTrue(file_exists($file));
		$this->assertEqual(file_get_contents($file), $contents);

		$Folder = new Folder($path);
		$Folder->delete();
	}
}
?>