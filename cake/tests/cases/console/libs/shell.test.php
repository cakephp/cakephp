<?php
/**
 * ShellTest file
 *
 * Test Case for Shell
 *
 * PHP 5
 *
 * CakePHP :  Rapid Development Framework (http://cakephp.org)
 * Copyright 2006-2010, Cake Software Foundation, Inc.
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2006-2010, Cake Software Foundation, Inc.
 * @link          http://cakephp.org CakePHP Project
 * @package       cake
 * @subpackage    cake.tests.cases.console.libs
 * @since         CakePHP v 1.2.0.7726
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
App::import('Core', 'Folder');
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


/**
 * TestShell class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.console.libs
 */
class TestShell extends Shell {

/**
 * name property
 *
 * @var name
 * @access public
 */
	public $name = 'TestShell';
/**
 * stopped property
 *
 * @var integer
 * @access public
 */
	public $stopped;

/**
 * stop method
 *
 * @param integer $status
 * @return void
 */
	protected function _stop($status = 0) {
		$this->stopped = $status;
	}
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
	public $fixtures = array(
		'core.post', 'core.comment', 'core.article', 'core.user',
		'core.tag', 'core.articles_tag', 'core.attachment'
	);

/**
 * setUp method
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();

		$this->Dispatcher = $this->getMock(
			'ShellDispatcher',
			array('getInput', 'stdout', 'stderr', '_stop', '_initEnvironment', 'clear')
		);
		$this->Shell =& new TestShell($this->Dispatcher);
	}

/**
 * tearDown method
 *
 * @return void
 */
	public function tearDown() {
		parent::tearDown();
		ClassRegistry::flush();
	}

/**
 * testConstruct method
 *
 * @return void
 */
	public function testConstruct() {
		$this->assertEquals($this->Dispatcher, $this->Shell->Dispatch);
		$this->assertEqual($this->Shell->name, 'TestShell');
		$this->assertEqual($this->Shell->alias, 'TestShell');
	}

/**
 * testInitialize method
 *
 * @return void
 */
	public function testInitialize() {
		App::build(array(
			'plugins' => array(TEST_CAKE_CORE_INCLUDE_PATH . 'tests' . DS . 'test_app' . DS . 'plugins' . DS),
			'models' => array(TEST_CAKE_CORE_INCLUDE_PATH . 'tests' . DS . 'test_app' . DS . 'models' . DS)
		), true);

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

		App::build();
	}

/**
 * testIn method
 *
 * @return void
 */
	public function testIn() {
		$this->Dispatcher->expects($this->at(0))
			->method('getInput')
			->with('Just a test?', array('y', 'n'), 'n')
			->will($this->returnValue('n'));

		$this->Dispatcher->expects($this->at(1))
			->method('getInput')
			->with('Just a test?', array('y', 'n'), 'n')
			->will($this->returnValue('Y'));

		$this->Dispatcher->expects($this->at(2))
			->method('getInput')
			->with('Just a test?', 'y,n', 'n')
			->will($this->returnValue('y'));

		$this->Dispatcher->expects($this->at(3))
			->method('getInput')
			->with('Just a test?', 'y/n', 'n')
			->will($this->returnValue('y'));

		$this->Dispatcher->expects($this->at(4))
			->method('getInput')
			->with('Just a test?', 'y', 'y')
			->will($this->returnValue('y'));

		$result = $this->Shell->in('Just a test?', array('y', 'n'), 'n');
		$this->assertEqual($result, 'n');

		$result = $this->Shell->in('Just a test?', array('y', 'n'), 'n');
		$this->assertEqual($result, 'Y');

		$result = $this->Shell->in('Just a test?', 'y,n', 'n');
		$this->assertEqual($result, 'y');

		$result = $this->Shell->in('Just a test?', 'y/n', 'n');
		$this->assertEqual($result, 'y');

		$result = $this->Shell->in('Just a test?', 'y', 'y');
		$this->assertEqual($result, 'y');

		$this->Shell->interactive = false;

		$result = $this->Shell->in('Just a test?', 'y/n', 'n');
		$this->assertEqual($result, 'n');
	}

/**
 * testOut method
 *
 * @return void
 */
	public function testOut() {
		$this->Shell->Dispatch->expects($this->at(0))
			->method('stdout')
			->with("Just a test\n", false);

		$this->Shell->Dispatch->expects($this->at(1))
			->method('stdout')
			->with("Just\na\ntest\n", false);

		$this->Shell->Dispatch->expects($this->at(2))
			->method('stdout')
			->with("Just\na\ntest\n\n", false);

		$this->Shell->Dispatch->expects($this->at(3))
			->method('stdout')
			->with("\n", false);

		$this->Shell->out('Just a test');

		$this->Shell->out(array('Just', 'a', 'test'));

		$this->Shell->out(array('Just', 'a', 'test'), 2);

		$this->Shell->out();
	}

/**
 * testErr method
 *
 * @return void
 */
	public function testErr() {
		$this->Shell->Dispatch->expects($this->at(0))
			->method('stderr')
			->with("Just a test\n");

		$this->Shell->Dispatch->expects($this->at(1))
			->method('stderr')
			->with("Just\na\ntest\n");

		$this->Shell->Dispatch->expects($this->at(2))
			->method('stderr')
			->with("Just\na\ntest\n\n");

		$this->Shell->Dispatch->expects($this->at(3))
			->method('stderr')
			->with("\n");

		$this->Shell->err('Just a test');

		$this->Shell->err(array('Just', 'a', 'test'));

		$this->Shell->err(array('Just', 'a', 'test'), 2);

		$this->Shell->err();
	}

/**
 * testNl
 *
 * @return void
 */
	public function testNl() {
		$this->assertEqual($this->Shell->nl(), "\n");
		$this->assertEqual($this->Shell->nl(true), "\n");
		$this->assertEqual($this->Shell->nl(false), "");
		$this->assertEqual($this->Shell->nl(2), "\n\n");
		$this->assertEqual($this->Shell->nl(1), "\n");
	}

/**
 * testHr
 *
 * @return void
 */
	public function testHr() {
		$bar = '---------------------------------------------------------------';

		$this->Shell->Dispatch->expects($this->at(0))->method('stdout')->with('', false);
		$this->Shell->Dispatch->expects($this->at(1))->method('stdout')->with($bar . "\n", false);
		$this->Shell->Dispatch->expects($this->at(2))->method('stdout')->with('', false);

		$this->Shell->Dispatch->expects($this->at(3))->method('stdout')->with("\n", false);
		$this->Shell->Dispatch->expects($this->at(4))->method('stdout')->with($bar . "\n", false);
		$this->Shell->Dispatch->expects($this->at(5))->method('stdout')->with("\n", false);

		$this->Shell->Dispatch->expects($this->at(6))->method('stdout')->with("\n\n", false);
		$this->Shell->Dispatch->expects($this->at(7))->method('stdout')->with($bar . "\n", false);
		$this->Shell->Dispatch->expects($this->at(8))->method('stdout')->with("\n\n", false);

		$this->Shell->hr();

		$this->Shell->hr(true);

		$this->Shell->hr(2);
	}

/**
 * testError
 *
 * @return void
 */
	public function testError() {
		$this->Shell->Dispatch->expects($this->at(0))
			->method('stderr')
			->with("Error: Foo Not Found\n");

		$this->Shell->Dispatch->expects($this->at(1))
			->method('stderr')
			->with("Error: Foo Not Found\n");

		$this->Shell->Dispatch->expects($this->at(2))
			->method('stderr')
			->with("Searched all...\n");

		$this->Shell->error('Foo Not Found');
		$this->assertIdentical($this->Shell->stopped, 1);

		$this->Shell->stopped = null;

		$this->Shell->error('Foo Not Found', 'Searched all...');
		$this->assertIdentical($this->Shell->stopped, 1);
	}

/**
 * testLoadTasks method
 *
 * @return void
 */
	public function testLoadTasks() {	
		$this->assertTrue($this->Shell->loadTasks());

		$this->Shell->tasks = null;
		$this->assertTrue($this->Shell->loadTasks());

		$this->Shell->tasks = false;
		$this->assertTrue($this->Shell->loadTasks());

		$this->Shell->tasks = true;
		$this->assertTrue($this->Shell->loadTasks());

		$this->Shell->tasks = array();
		$this->assertTrue($this->Shell->loadTasks());

		$this->Shell->tasks = array('TestApple');
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
 */
	public function testShortPath() {
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
 */
	public function testCreateFileNonInteractive() {
		$this->skipIf(DIRECTORY_SEPARATOR === '\\', '%s Not supported on Windows');

		$path = TMP . 'shell_test';
		$file = $path . DS . 'file1.php';

		$Folder = new Folder($path, true);

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

		$Folder->delete();
	}

/**
 * test createFile when the shell is interactive.
 *
 * @return void
 */
	function testCreateFileInteractive() {
		$this->skipIf(DIRECTORY_SEPARATOR === '\\', '%s Not supported on Windows');

		$path = TMP . 'shell_test';
		$file = $path . DS . 'file1.php';
		$Folder = new Folder($path, true);

		$this->Shell->interactive = true;

		$this->Shell->Dispatch->expects($this->at(5))
			->method('getInput')
			->withAnyParameters()
			->will($this->returnValue('n'));
	
		$this->Shell->Dispatch->expects($this->at(9))
			->method('getInput')
			->withAnyParameters()
			->will($this->returnValue('y'));


		$contents = "<?php\necho 'yet another test';\n\$te = 'st';\n?>";
		$result = $this->Shell->createFile($file, $contents);
		$this->assertTrue($result);
		$this->assertTrue(file_exists($file));
		$this->assertEqual(file_get_contents($file), $contents);

		// no overwrite
		$contents = 'new contents';
		$result = $this->Shell->createFile($file, $contents);
		$this->assertFalse($result);
		$this->assertTrue(file_exists($file));
		$this->assertNotEqual($contents, file_get_contents($file));

		// overwrite
		$contents = 'more new contents';
		$result = $this->Shell->createFile($file, $contents);
		$this->assertTrue($result);
		$this->assertTrue(file_exists($file));
		$this->assertEquals($contents, file_get_contents($file));

		$Folder->delete();
	}

/**
 * testCreateFileWindows method
 *
 * @return void
 */
	public function testCreateFileWindowsNonInteractive() {
		$this->skipIf(DIRECTORY_SEPARATOR === '/', 'testCreateFileWindows supported on Windows only');

		$path = TMP . 'shell_test';
		$file = $path . DS . 'file1.php';

		$Folder = new Folder($path, true);

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

		$Folder = new Folder($path);
		$Folder->delete();
	}

/**
 * test createFile on windows with interactive on.
 *
 * @return void
 */
	function testCreateFileWindowsInteractive() {
		$this->skipIf(DIRECTORY_SEPARATOR === '/', 'testCreateFileWindowsInteractive supported on Windows only');

		$path = TMP . 'shell_test';
		$file = $path . DS . 'file1.php';

		$Folder = new Folder($path, true);

		$this->Shell->interactive = true;

		$this->Shell->Dispatch->expects($this->at(5))
			->method('getInput')
			->will($this->returnValue('y'));

		$this->Shell->Dispatch->expects($this->at(9))
			->method('getInput')
			->will($this->returnValue('n'));

		$contents = "<?php\necho 'yet another test';\r\n\$te = 'st';\r\n?>";
		$result = $this->Shell->createFile($file, $contents);
		$this->assertFalse($result);
		$this->assertTrue(file_exists($file));
		$this->assertNotEqual(file_get_contents($file), $contents);

		$result = $this->Shell->createFile($file, $contents);
		$this->assertTrue($result);
		$this->assertTrue(file_exists($file));
		$this->assertEqual(file_get_contents($file), $contents);

		$Folder->delete();
	}
}
