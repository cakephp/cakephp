<?php
/**
 * ShellTest file
 *
 * Test Case for Shell
 *
 * PHP 5
 *
 * CakePHP :  Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP Project
 * @package       Cake.Test.Case.Console.Command
 * @since         CakePHP v 1.2.0.7726
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */

App::uses('ShellDispatcher', 'Console');
App::uses('Shell', 'Console');
App::uses('Folder', 'Utility');

/**
 * ShellTestShell class
 *
 * @package       Cake.Test.Case.Console.Command
 */
class ShellTestShell extends Shell {

/**
 * name property
 *
 * @var name
 */
	public $name = 'ShellTestShell';

/**
 * stopped property
 *
 * @var integer
 */
	public $stopped;

/**
 * testMessage property
 *
 * @var string
 */
	public $testMessage = 'all your base are belong to us';

/**
 * stop method
 *
 * @param integer $status
 * @return void
 */
	protected function _stop($status = 0) {
		$this->stopped = $status;
	}

	protected function _secret() {
	}

	//@codingStandardsIgnoreStart
	public function do_something() {
	}

	protected function no_access() {
	}

	public function log_something() {
		$this->log($this->testMessage);
	}
	//@codingStandardsIgnoreEnd

	public function mergeVars($properties, $class, $normalize = true) {
		return $this->_mergeVars($properties, $class, $normalize);
	}

	public function useLogger($enable = true) {
		$this->_useLogger($enable);
	}

}

/**
 * Class for testing merging vars
 *
 * @package       Cake.Test.Case.Console.Command
 */
class TestMergeShell extends Shell {

	public $tasks = array('DbConfig', 'Fixture');

	public $uses = array('Comment');

}

/**
 * TestAppleTask class
 *
 * @package       Cake.Test.Case.Console.Command
 */
class TestAppleTask extends Shell {
}

/**
 * TestBananaTask class
 *
 * @package       Cake.Test.Case.Console.Command
 */
class TestBananaTask extends Shell {
}

/**
 * ShellTest class
 *
 * @package       Cake.Test.Case.Console.Command
 */
class ShellTest extends CakeTestCase {

/**
 * Fixtures used in this test case
 *
 * @var array
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

		if (is_dir(TMP . 'shell_test')) {
			$Folder = new Folder(TMP . 'shell_test');
			$Folder->delete();
		}
	}

/**
 * testConstruct method
 *
 * @return void
 */
	public function testConstruct() {
		$this->assertEquals('ShellTestShell', $this->Shell->name);
		$this->assertInstanceOf('ConsoleInput', $this->Shell->stdin);
		$this->assertInstanceOf('ConsoleOutput', $this->Shell->stdout);
		$this->assertInstanceOf('ConsoleOutput', $this->Shell->stderr);
	}

/**
 * test merging vars
 *
 * @return void
 */
	public function testMergeVars() {
		$this->Shell->tasks = array('DbConfig' => array('one', 'two'));
		$this->Shell->uses = array('Posts');
		$this->Shell->mergeVars(array('tasks'), 'TestMergeShell');
		$this->Shell->mergeVars(array('uses'), 'TestMergeShell', false);

		$expected = array('DbConfig' => null, 'Fixture' => null, 'DbConfig' => array('one', 'two'));
		$this->assertEquals($expected, $this->Shell->tasks);

		$expected = array('Fixture' => null, 'DbConfig' => array('one', 'two'));
		$this->assertEquals($expected, Hash::normalize($this->Shell->tasks), 'Normalized results are wrong.');
		$this->assertEquals(array('Comment', 'Posts'), $this->Shell->uses, 'Merged models are wrong.');
	}

/**
 * testInitialize method
 *
 * @return void
 */
	public function testInitialize() {
		App::build(array(
			'Plugin' => array(CAKE . 'Test' . DS . 'test_app' . DS . 'Plugin' . DS),
			'Model' => array(CAKE . 'Test' . DS . 'test_app' . DS . 'Model' . DS)
		), App::RESET);

		CakePlugin::load('TestPlugin');
		$this->Shell->uses = array('TestPlugin.TestPluginPost');
		$this->Shell->initialize();

		$this->assertTrue(isset($this->Shell->TestPluginPost));
		$this->assertInstanceOf('TestPluginPost', $this->Shell->TestPluginPost);
		$this->assertEquals('TestPluginPost', $this->Shell->modelClass);
		CakePlugin::unload('TestPlugin');

		$this->Shell->uses = array('Comment');
		$this->Shell->initialize();
		$this->assertTrue(isset($this->Shell->Comment));
		$this->assertInstanceOf('Comment', $this->Shell->Comment);
		$this->assertEquals('Comment', $this->Shell->modelClass);

		App::build();
	}

/**
 * testLoadModel method
 *
 * @return void
 */
	public function testLoadModel() {
		App::build(array(
			'Plugin' => array(CAKE . 'Test' . DS . 'test_app' . DS . 'Plugin' . DS),
			'Model' => array(CAKE . 'Test' . DS . 'test_app' . DS . 'Model' . DS)
		), App::RESET);

		$Shell = new TestMergeShell();
		$this->assertEquals('Comment', $Shell->Comment->alias);
		$this->assertInstanceOf('Comment', $Shell->Comment);
		$this->assertEquals('Comment', $Shell->modelClass);

		CakePlugin::load('TestPlugin');
		$this->Shell->loadModel('TestPlugin.TestPluginPost');
		$this->assertTrue(isset($this->Shell->TestPluginPost));
		$this->assertInstanceOf('TestPluginPost', $this->Shell->TestPluginPost);
		$this->assertEquals('TestPluginPost', $this->Shell->modelClass);
		CakePlugin::unload('TestPlugin');

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

		$this->Shell->stdin->expects($this->at(5))
			->method('read')
			->will($this->returnValue('0'));

		$result = $this->Shell->in('Just a test?', array('y', 'n'), 'n');
		$this->assertEquals('n', $result);

		$result = $this->Shell->in('Just a test?', array('y', 'n'), 'n');
		$this->assertEquals('Y', $result);

		$result = $this->Shell->in('Just a test?', 'y,n', 'n');
		$this->assertEquals('y', $result);

		$result = $this->Shell->in('Just a test?', 'y/n', 'n');
		$this->assertEquals('y', $result);

		$result = $this->Shell->in('Just a test?', 'y', 'y');
		$this->assertEquals('y', $result);

		$result = $this->Shell->in('Just a test?', array(0, 1, 2), '0');
		$this->assertEquals('0', $result);
	}

/**
 * Test in() when not interactive.
 *
 * @return void
 */
	public function testInNonInteractive() {
		$this->Shell->interactive = false;

		$result = $this->Shell->in('Just a test?', 'y/n', 'n');
		$this->assertEquals('n', $result);
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
	public function testVerboseOutput() {
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
	public function testQuietOutput() {
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
		$newLine = "\n";
		if (DS === '\\') {
			$newLine = "\r\n";
		}
		$this->assertEquals($this->Shell->nl(), $newLine);
		$this->assertEquals($this->Shell->nl(true), $newLine);
		$this->assertEquals("", $this->Shell->nl(false));
		$this->assertEquals($this->Shell->nl(2), $newLine . $newLine);
		$this->assertEquals($this->Shell->nl(1), $newLine);
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
		$this->assertSame($this->Shell->stopped, 1);

		$this->Shell->stopped = null;

		$this->Shell->error('Foo Not Found', 'Searched all...');
		$this->assertSame($this->Shell->stopped, 1);
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
	public function testMagicGetArgAndParamReferences() {
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
		$this->assertEquals($expected, $this->Shell->shortPath($path));

		$path = $expected = DS . 'tmp' . DS . 'ab' . DS . 'cd' . DS;
		$this->assertEquals($expected, $this->Shell->shortPath($path));

		$path = $expected = DS . 'tmp' . DS . 'ab' . DS . 'index.php';
		$this->assertEquals($expected, $this->Shell->shortPath($path));

		$path = DS . 'tmp' . DS . 'ab' . DS . DS . 'cd';
		$expected = DS . 'tmp' . DS . 'ab' . DS . 'cd';
		$this->assertEquals($expected, $this->Shell->shortPath($path));

		$path = 'tmp' . DS . 'ab';
		$expected = 'tmp' . DS . 'ab';
		$this->assertEquals($expected, $this->Shell->shortPath($path));

		$path = 'tmp' . DS . 'ab';
		$expected = 'tmp' . DS . 'ab';
		$this->assertEquals($expected, $this->Shell->shortPath($path));

		$path = APP;
		$expected = DS . basename(APP) . DS;
		$this->assertEquals($expected, $this->Shell->shortPath($path));

		$path = APP . 'index.php';
		$expected = DS . basename(APP) . DS . 'index.php';
		$this->assertEquals($expected, $this->Shell->shortPath($path));
	}

/**
 * testCreateFile method
 *
 * @return void
 */
	public function testCreateFileNonInteractive() {
		$eol = PHP_EOL;

		$path = TMP . 'shell_test';
		$file = $path . DS . 'file1.php';

		new Folder($path, true);

		$this->Shell->interactive = false;

		$contents = "<?php{$eol}echo 'test';${eol}\$te = 'st';{$eol}";
		$result = $this->Shell->createFile($file, $contents);
		$this->assertTrue($result);
		$this->assertTrue(file_exists($file));
		$this->assertEquals(file_get_contents($file), $contents);

		$contents = "<?php\necho 'another test';\n\$te = 'st';\n";
		$result = $this->Shell->createFile($file, $contents);
		$this->assertTrue($result);
		$this->assertTrue(file_exists($file));
		$this->assertTextEquals(file_get_contents($file), $contents);
	}

/**
 * test createFile when the shell is interactive.
 *
 * @return void
 */
	public function testCreateFileInteractive() {
		$eol = PHP_EOL;

		$path = TMP . 'shell_test';
		$file = $path . DS . 'file1.php';
		new Folder($path, true);

		$this->Shell->interactive = true;

		$this->Shell->stdin->expects($this->at(0))
			->method('read')
			->will($this->returnValue('n'));

		$this->Shell->stdin->expects($this->at(1))
			->method('read')
			->will($this->returnValue('y'));

		$contents = "<?php{$eol}echo 'yet another test';{$eol}\$te = 'st';{$eol}";
		$result = $this->Shell->createFile($file, $contents);
		$this->assertTrue($result);
		$this->assertTrue(file_exists($file));
		$this->assertEquals(file_get_contents($file), $contents);

		// no overwrite
		$contents = 'new contents';
		$result = $this->Shell->createFile($file, $contents);
		$this->assertFalse($result);
		$this->assertTrue(file_exists($file));
		$this->assertNotEquals($contents, file_get_contents($file));

		// overwrite
		$contents = 'more new contents';
		$result = $this->Shell->createFile($file, $contents);
		$this->assertTrue($result);
		$this->assertTrue(file_exists($file));
		$this->assertEquals($contents, file_get_contents($file));
	}

/**
 * Test that you can't create files that aren't writable.
 *
 * @return void
 */
	public function testCreateFileNoPermissions() {
		$this->skipIf(DIRECTORY_SEPARATOR === '\\', 'Cant perform operations using permissions on windows.');

		$path = TMP . 'shell_test';
		$file = $path . DS . 'no_perms';

		if (!is_dir($path)) {
			mkdir($path);
		}
		chmod($path, 0444);

		$this->Shell->createFile($file, 'testing');
		$this->assertFalse(file_exists($file));

		chmod($path, 0744);
		rmdir($path);
	}

/**
 * test hasTask method
 *
 * @return void
 */
	public function testHasTask() {
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
	public function testHasMethod() {
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
	public function testRunCommandMain() {
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
	public function testRunCommandWithMethod() {
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
	public function testRunCommandBaseclassMethod() {
		$Mock = $this->getMock('Shell', array('startup', 'getOptionParser', 'out'), array(), '', false);
		$Parser = $this->getMock('ConsoleOptionParser', array(), array(), '', false);

		$Parser->expects($this->once())->method('help');
		$Mock->expects($this->once())->method('getOptionParser')
			->will($this->returnValue($Parser));
		$Mock->expects($this->never())->method('hr');
		$Mock->expects($this->once())->method('out');

		$Mock->runCommand('hr', array());
	}

/**
 * test run command causing exception on Shell method.
 *
 * @return void
 */
	public function testRunCommandMissingMethod() {
		$Mock = $this->getMock('Shell', array('startup', 'getOptionParser', 'out'), array(), '', false);
		$Parser = $this->getMock('ConsoleOptionParser', array(), array(), '', false);

		$Parser->expects($this->once())->method('help');
		$Mock->expects($this->never())->method('idontexist');
		$Mock->expects($this->once())->method('getOptionParser')
			->will($this->returnValue($Parser));
		$Mock->expects($this->once())->method('out');

		$result = $Mock->runCommand('idontexist', array());
		$this->assertFalse($result);
	}

/**
 * test that a --help causes help to show.
 *
 * @return void
 */
	public function testRunCommandTriggeringHelp() {
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
	public function testRunCommandHittingTask() {
		$Shell = $this->getMock('Shell', array('hasTask', 'startup'), array(), '', false);
		$task = $this->getMock('Shell', array('execute', 'runCommand'), array(), '', false);
		$task->expects($this->any())
			->method('runCommand')
			->with('execute', array('one', 'value'));

		$Shell->expects($this->once())->method('startup');
		$Shell->expects($this->any())
			->method('hasTask')
			->will($this->returnValue(true));

		$Shell->RunCommand = $task;

		$Shell->runCommand('run_command', array('run_command', 'one', 'value'));
	}

/**
 * test wrapBlock wrapping text.
 *
 * @return void
 */
	public function testWrapText() {
		$text = 'This is the song that never ends. This is the song that never ends. This is the song that never ends.';
		$result = $this->Shell->wrapText($text, 33);
		$expected = <<<TEXT
This is the song that never ends.
This is the song that never ends.
This is the song that never ends.
TEXT;
		$this->assertTextEquals($expected, $result, 'Text not wrapped.');

		$result = $this->Shell->wrapText($text, array('indent' => '  ', 'width' => 33));
		$expected = <<<TEXT
  This is the song that never ends.
  This is the song that never ends.
  This is the song that never ends.
TEXT;
		$this->assertTextEquals($expected, $result, 'Text not wrapped.');
	}

/**
 * Testing camel cased naming of tasks
 *
 * @return void
 */
	public function testShellNaming() {
		$this->Shell->tasks = array('TestApple');
		$this->Shell->loadTasks();
		$expected = 'TestApple';
		$this->assertEquals($expected, $this->Shell->TestApple->name);
	}

/**
 * Test that option parsers are created with the correct name/command.
 *
 * @return void
 */
	public function testGetOptionParser() {
		$this->Shell->name = 'test';
		$this->Shell->plugin = 'plugin';
		$parser = $this->Shell->getOptionParser();

		$this->assertEquals('plugin.test', $parser->command());
	}

/**
 * Test file and console and logging
 */
	public function testFileAndConsoleLogging() {
		// file logging
		$this->Shell->log_something();
		$this->assertTrue(file_exists(LOGS . 'error.log'));

		unlink(LOGS . 'error.log');
		$this->assertFalse(file_exists(LOGS . 'error.log'));

		// both file and console logging
		require_once CORE_TEST_CASES . DS . 'Log' . DS . 'Engine' . DS . 'ConsoleLogTest.php';
		$mock = $this->getMock('ConsoleLog', array('write'), array(
			array('types' => 'error'),
		));
		TestCakeLog::config('console', array(
			'engine' => 'Console',
			'stream' => 'php://stderr',
			));
		TestCakeLog::replace('console', $mock);
		$mock->expects($this->once())
			->method('write')
			->with('error', $this->Shell->testMessage);
		$this->Shell->log_something();
		$this->assertTrue(file_exists(LOGS . 'error.log'));
		$contents = file_get_contents(LOGS . 'error.log');
		$this->assertContains($this->Shell->testMessage, $contents);
	}

/**
 * Tests that _useLogger works properly
 *
 * @return void
 */
	public function testProtectedUseLogger() {
		CakeLog::drop('stdout');
		CakeLog::drop('stderr');
		$this->Shell->useLogger(true);
		$this->assertNotEmpty(CakeLog::stream('stdout'));
		$this->assertNotEmpty(CakeLog::stream('stderr'));
		$this->Shell->useLogger(false);
		$this->assertFalse(CakeLog::stream('stdout'));
		$this->assertFalse(CakeLog::stream('stderr'));
	}

/**
 * Test file and console and logging quiet output
 */
	public function testQuietLog() {
		$output = $this->getMock('ConsoleOutput', array(), array(), '', false);
		$error = $this->getMock('ConsoleOutput', array(), array(), '', false);
		$in = $this->getMock('ConsoleInput', array(), array(), '', false);
		$this->Shell = $this->getMock('ShellTestShell', array('_useLogger'), array($output, $error, $in));
		$this->Shell->expects($this->once())->method('_useLogger')->with(false);
		$this->Shell->runCommand('foo', array('--quiet'));
	}

}
