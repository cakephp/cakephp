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
 * @package       cake.tests.cases.console.libs
 * @since         CakePHP v 1.2.0.7726
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
App::import('Core', 'Folder');
App::import('Shell', 'Shell', false);

require_once CAKE . 'console' .  DS . 'shell_dispatcher.php';

/**
 * ShellTestShell class
 *
 * @package       cake.tests.cases.console.libs
 */
class ShellTestShell extends Shell {

/**
 * name property
 *
 * @var name
 * @access public
 */
	public $name = 'ShellTestShell';

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

	public function do_something() {
		
	}
	
	public function _secret() {
		
	}
	
	protected function no_access() {
		
	}
	
	public function mergeVars($properties, $class, $normalize = true) {
		return $this->_mergeVars($properties, $class, $normalize);
	}
}

/**
 * Class for testing merging vars
 *
 * @package cake.tests.cases.console
 */
class TestMergeShell extends Shell {
	var $tasks = array('DbConfig', 'Fixture');
	var $uses = array('Comment');
}

/**
 * TestAppleTask class
 *
 * @package       cake.tests.cases.console.libs
 */
class TestAppleTask extends Shell {
}

/**
 * TestBananaTask class
 *
 * @package       cake.tests.cases.console.libs
 */
class TestBananaTask extends Shell {
}

/**
 * ShellTest class
 *
 * @package       cake.tests.cases.console.libs
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

		$output = $this->getMock('ConsoleOutput', array(), array(), '', false);
		$error = $this->getMock('ConsoleOutput', array(), array(), '', false);
		$in = $this->getMock('ConsoleInput', array(), array(), '', false);
		$this->Shell = new ShellTestShell($output, $error, $in);
	}

/**
 * testConstruct method
 *
 * @return void
 */
	public function testConstruct() {
		$this->assertEqual($this->Shell->name, 'ShellTestShell');
		$this->assertInstanceOf('ConsoleInput', $this->Shell->stdin);
		$this->assertInstanceOf('ConsoleOutput', $this->Shell->stdout);
		$this->assertInstanceOf('ConsoleOutput', $this->Shell->stderr);
	}

/**
 * test merging vars
 *
 * @return void
 */
	function testMergeVars() {
		$this->Shell->tasks = array('DbConfig' => array('one', 'two'));
		$this->Shell->uses = array('Posts');
		$this->Shell->mergeVars(array('tasks'), 'TestMergeShell');
		$this->Shell->mergeVars(array('uses'), 'TestMergeShell', false);

		$expected = array('DbConfig' => null, 'Fixture' => null, 'DbConfig' => array('one', 'two'));
		$this->assertEquals($expected, $this->Shell->tasks);

		$expected = array('Fixture' => null, 'DbConfig' => array('one', 'two'));
		$this->assertEquals($expected, Set::normalize($this->Shell->tasks), 'Normalized results are wrong.');
		$this->assertEquals(array('Comment', 'Posts'), $this->Shell->uses, 'Merged models are wrong.');
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

		App::build();
	}

/**
 * testIn method
 *
 * @return void
 */
	public function testIn() {
		$this->Shell->stdin->expects($this->at(0))
			->method('read')
			->will($this->returnValue('n'));

		$this->Shell->stdin->expects($this->at(1))
			->method('read')
			->will($this->returnValue('Y'));

		$this->Shell->stdin->expects($this->at(2))
			->method('read')
			->will($this->returnValue('y'));

		$this->Shell->stdin->expects($this->at(3))
			->method('read')
			->will($this->returnValue('y'));

		$this->Shell->stdin->expects($this->at(4))
			->method('read')
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
		$this->Shell->stdout->expects($this->at(0))
			->method('write')
			->with("Just a test", 1);

		$this->Shell->stdout->expects($this->at(1))
			->method('write')
			->with(array('Just', 'a', 'test'), 1);

		$this->Shell->stdout->expects($this->at(2))
			->method('write')
			->with(array('Just', 'a', 'test'), 2);

		$this->Shell->stdout->expects($this->at(3))
			->method('write')
			->with('', 1);

		$this->Shell->out('Just a test');

		$this->Shell->out(array('Just', 'a', 'test'));

		$this->Shell->out(array('Just', 'a', 'test'), 2);

		$this->Shell->out();
	}

/**
 * test that verbose and quiet output levels work
 *
 * @return void
 */
	function testVerboseOutput() {
		$this->Shell->stdout->expects($this->at(0))->method('write')
			->with('Verbose', 1);
		$this->Shell->stdout->expects($this->at(1))->method('write')
			->with('Normal', 1);
		$this->Shell->stdout->expects($this->at(2))->method('write')
			->with('Quiet', 1);

		$this->Shell->params['verbose'] = true;
		$this->Shell->params['quiet'] = false;

		$this->Shell->out('Verbose', 1, Shell::VERBOSE);
		$this->Shell->out('Normal', 1, Shell::NORMAL);
		$this->Shell->out('Quiet', 1, Shell::QUIET);
	}

/**
 * test that verbose and quiet output levels work
 *
 * @return void
 */
	function testQuietOutput() {
		$this->Shell->stdout->expects($this->once())->method('write')
			->with('Quiet', 1);

		$this->Shell->params['verbose'] = false;
		$this->Shell->params['quiet'] = true;

		$this->Shell->out('Verbose', 1, Shell::VERBOSE);
		$this->Shell->out('Normal', 1, Shell::NORMAL);
		$this->Shell->out('Quiet', 1, Shell::QUIET);
	}

/**
 * testErr method
 *
 * @return void
 */
	public function testErr() {
		$this->Shell->stderr->expects($this->at(0))
			->method('write')
			->with("Just a test", 1);

		$this->Shell->stderr->expects($this->at(1))
			->method('write')
			->with(array('Just', 'a', 'test'), 1);

		$this->Shell->stderr->expects($this->at(2))
			->method('write')
			->with(array('Just', 'a', 'test'), 2);

		$this->Shell->stderr->expects($this->at(3))
			->method('write')
			->with('', 1);

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

		$this->Shell->stdout->expects($this->at(0))->method('write')->with('', 0);
        $this->Shell->stdout->expects($this->at(1))->method('write')->with($bar, 1);
        $this->Shell->stdout->expects($this->at(2))->method('write')->with('', 0);

		$this->Shell->stdout->expects($this->at(3))->method('write')->with("", true);
		$this->Shell->stdout->expects($this->at(4))->method('write')->with($bar, 1);
		$this->Shell->stdout->expects($this->at(5))->method('write')->with("", true);

		$this->Shell->stdout->expects($this->at(6))->method('write')->with("", 2);
		$this->Shell->stdout->expects($this->at(7))->method('write')->with($bar, 1);
		$this->Shell->stdout->expects($this->at(8))->method('write')->with("", 2);

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
		$this->Shell->stderr->expects($this->at(0))
			->method('write')
			->with("<error>Error:</error> Foo Not Found", 1);

		$this->Shell->stderr->expects($this->at(1))
			->method('write')
			->with("<error>Error:</error> Foo Not Found", 1);

		$this->Shell->stderr->expects($this->at(2))
			->method('write')
			->with("Searched all...", 1);

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
		$this->assertInstanceOf('TestAppleTask', $this->Shell->TestApple);

		$this->Shell->tasks = 'TestBanana';
		$this->assertTrue($this->Shell->loadTasks());
		$this->assertInstanceOf('TestAppleTask', $this->Shell->TestApple);
		$this->assertInstanceOf('TestBananaTask', $this->Shell->TestBanana);

		unset($this->Shell->ShellTestApple, $this->Shell->TestBanana);

		$this->Shell->tasks = array('TestApple', 'TestBanana');
		$this->assertTrue($this->Shell->loadTasks());
		$this->assertInstanceOf('TestAppleTask', $this->Shell->TestApple);
		$this->assertInstanceOf('TestBananaTask', $this->Shell->TestBanana);
	}

/**
 * test that __get() makes args and params references
 *
 * @return void
 */
	function test__getArgAndParamReferences() {
		$this->Shell->tasks = array('TestApple');
		$this->Shell->args = array('one');
		$this->Shell->params = array('help' => false);
		$this->Shell->loadTasks();
		$result = $this->Shell->TestApple;
		
		$this->Shell->args = array('one', 'two');
		
		$this->assertSame($this->Shell->args, $result->args);
		$this->assertSame($this->Shell->params, $result->params);
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

		$this->Shell->stdin->expects($this->at(0))
			->method('read')
			->will($this->returnValue('n'));
	
		$this->Shell->stdin->expects($this->at(1))
			->method('read')
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

/**
 * test hasTask method
 *
 * @return void
 */
	function testHasTask() {
		$this->Shell->tasks = array('Extract', 'DbConfig');
		$this->Shell->loadTasks();
		
		$this->assertTrue($this->Shell->hasTask('extract'));
		$this->assertTrue($this->Shell->hasTask('Extract'));
		$this->assertFalse($this->Shell->hasTask('random'));
		
		$this->assertTrue($this->Shell->hasTask('db_config'));
		$this->assertTrue($this->Shell->hasTask('DbConfig'));
	}

/**
 * test the hasMethod
 *
 * @return void
 */
	function testHasMethod() {
		$this->assertTrue($this->Shell->hasMethod('do_something'));
		$this->assertFalse($this->Shell->hasMethod('hr'), 'hr is callable');
		$this->assertFalse($this->Shell->hasMethod('_secret'), '_secret is callable');
		$this->assertFalse($this->Shell->hasMethod('no_access'), 'no_access is callable');
	}

/**
 * test run command calling main.
 *
 * @return void
 */
	function testRunCommandMain() {
		$methods = get_class_methods('Shell');
		$Mock = $this->getMock('Shell', array('main', 'startup'), array(), '', false);

		$Mock->expects($this->once())->method('main')->will($this->returnValue(true));
		$result = $Mock->runCommand(null, array());
		$this->assertTrue($result);
	}

/**
 * test run command calling a legit method.
 *
 * @return void
 */
	function testRunCommandWithMethod() {
		$methods = get_class_methods('Shell');
		$Mock = $this->getMock('Shell', array('hit_me', 'startup'), array(), '', false);

		$Mock->expects($this->once())->method('hit_me')->will($this->returnValue(true));
		$result = $Mock->runCommand('hit_me', array());
		$this->assertTrue($result);
	}

/**
 * test run command causing exception on Shell method.
 *
 * @return void
 */
	function testRunCommandBaseclassMethod() {
		$Mock = $this->getMock('Shell', array('startup', 'getOptionParser', 'out'), array(), '', false);
		$Parser = $this->getMock('ConsoleOptionParser', array(), array(), '', false);

		$Parser->expects($this->once())->method('help');
		$Mock->expects($this->once())->method('getOptionParser')
			->will($this->returnValue($Parser));
		$Mock->expects($this->never())->method('hr');
		$Mock->expects($this->once())->method('out');

		$result = $Mock->runCommand('hr', array());
	}

/**
 * test run command causing exception on Shell method.
 *
 * @return void
 */
	function testRunCommandMissingMethod() {
		$methods = get_class_methods('Shell');
		$Mock = $this->getMock('Shell', array('startup', 'getOptionParser', 'out'), array(), '', false);
		$Parser = $this->getMock('ConsoleOptionParser', array(), array(), '', false);

		$Parser->expects($this->once())->method('help');
		$Mock->expects($this->never())->method('idontexist');
		$Mock->expects($this->once())->method('getOptionParser')
			->will($this->returnValue($Parser));
		$Mock->expects($this->once())->method('out');


		$result = $Mock->runCommand('idontexist', array());
	}

/**
 * test that a --help causes help to show.
 *
 * @return void
 */
	function testRunCommandTriggeringHelp() {
		$Parser = $this->getMock('ConsoleOptionParser', array(), array(), '', false);
		$Parser->expects($this->once())->method('parse')
			->with(array('--help'))
			->will($this->returnValue(array(array('help' => true), array())));
		$Parser->expects($this->once())->method('help');
		
		$Shell = $this->getMock('Shell', array('getOptionParser', 'out', 'startup', '_welcome'), array(), '', false);
		$Shell->expects($this->once())->method('getOptionParser')
			->will($this->returnValue($Parser));
		$Shell->expects($this->once())->method('out');

		$Shell->runCommand(null, array('--help'));
	}

/**
 * test that runCommand will call runCommand on the task.
 *
 * @return void
 */
	function testRunCommandHittingTask() {
		$Shell = $this->getMock('Shell', array('hasTask', 'startup'), array(), '', false);
		$task = $this->getMock('Shell', array('execute', 'runCommand'), array(), '', false);
		$task->expects($this->any())->method('runCommand')
			->with('execute', array('one', 'value'));

		$Shell->expects($this->once())->method('startup');
		$Shell->expects($this->any())->method('hasTask')->will($this->returnValue(true));
		$Shell->RunCommand = $task;

		$Shell->runCommand('run_command', array('run_command', 'one', 'value'));
	}

/**
 * test wrapBlock wrapping text.
 *
 * @return void
 */
	function testWrapText() {
		$text = 'This is the song that never ends. This is the song that never ends. This is the song that never ends.';
		$result = $this->Shell->wrapText($text, 33);
		$expected = <<<TEXT
This is the song that never ends.
This is the song that never ends.
This is the song that never ends.
TEXT;
		$this->assertEquals($expected, $result, 'Text not wrapped.');

		$result = $this->Shell->wrapText($text, array('indent' => '  ', 'width' => 33));
		$expected = <<<TEXT
  This is the song that never ends.
  This is the song that never ends.
  This is the song that never ends.
TEXT;
		$this->assertEquals($expected, $result, 'Text not wrapped.');
	}
}
