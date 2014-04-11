<?php
/**
 * CakePHP :  Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP Project
 * @since         1.2.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Console;

use Cake\Console\Shell;
use Cake\Core\App;
use Cake\Core\Configure;
use Cake\Core\Plugin;
use Cake\Log\Log;
use Cake\TestSuite\TestCase;
use Cake\Utility\Folder;
use Cake\Utility\Hash;

/**
 * Class for testing merging vars
 */
class MergeShell extends Shell {

	public $tasks = array('DbConfig', 'Fixture');

	public $modelClass = 'Articles';

}

/**
 * ShellTestShell class
 *
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

	public function useLogger($enable = true) {
		$this->_useLogger($enable);
	}

}

/**
 * TestAppleTask class
 *
 */
class TestAppleTask extends Shell {
}

/**
 * TestBananaTask class
 *
 */
class TestBananaTask extends Shell {
}

class_alias(__NAMESPACE__ . '\TestAppleTask', 'Cake\Console\Command\Task\TestAppleTask');
class_alias(__NAMESPACE__ . '\TestBananaTask', 'Cake\Console\Command\Task\TestBananaTask');

/**
 * ShellTest class
 *
 */
class ShellTest extends TestCase {

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

		$this->io = $this->getMock('Cake\Console\ConsoleIo', [], [], '', false);
		$this->Shell = new ShellTestShell($this->io);

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
		$this->assertInstanceOf('Cake\Console\ConsoleIo', $this->Shell->io());
	}

/**
 * testInitialize method
 *
 * @return void
 */
	public function testInitialize() {
		Configure::write('App.namespace', 'TestApp');

		Plugin::load('TestPlugin');
		$this->Shell->tasks = array('DbConfig' => array('one', 'two'));
		$this->Shell->plugin = 'TestPlugin';
		$this->Shell->modelClass = 'TestPluginComments';
		$this->Shell->initialize();

		$this->assertTrue(isset($this->Shell->TestPluginComments));
		$this->assertInstanceOf(
			'TestPlugin\Model\Table\TestPluginCommentsTable',
			$this->Shell->TestPluginComments
		);
	}

/**
 * test LoadModel method
 *
 * @return void
 */
	public function testLoadModel() {
		Configure::write('App.namespace', 'TestApp');

		$Shell = new MergeShell();
		$this->assertInstanceOf(
			'TestApp\Model\Table\ArticlesTable',
			$Shell->Articles
		);
		$this->assertEquals('Articles', $Shell->modelClass);

		Plugin::load('TestPlugin');
		$this->Shell->loadModel('TestPlugin.TestPluginComments');
		$this->assertTrue(isset($this->Shell->TestPluginComments));
		$this->assertInstanceOf(
			'TestPlugin\Model\Table\TestPluginCommentsTable',
			$this->Shell->TestPluginComments
		);
	}

/**
 * testIn method
 *
 * @return void
 */
	public function testIn() {
		$this->io->expects($this->at(0))
			->method('askChoice')
			->with('Just a test?', ['y', 'n'], 'n')
			->will($this->returnValue('n'));

		$this->io->expects($this->at(1))
			->method('ask')
			->with('Just a test?', 'n')
			->will($this->returnValue('n'));

		$result = $this->Shell->in('Just a test?', array('y', 'n'), 'n');
		$this->assertEquals('n', $result);

		$result = $this->Shell->in('Just a test?', null, 'n');
		$this->assertEquals('n', $result);
	}

/**
 * Test in() when not interactive.
 *
 * @return void
 */
	public function testInNonInteractive() {
		$this->io->expects($this->never())
			->method('askChoice');
		$this->io->expects($this->never())
			->method('ask');

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
		$this->io->expects($this->once())
			->method('out')
			->with('Just a test', 1);

		$this->Shell->out('Just a test');
	}

/**
 * testErr method
 *
 * @return void
 */
	public function testErr() {
		$this->io->expects($this->once())
			->method('err')
			->with('Just a test', 1);

		$this->Shell->err('Just a test');
	}

/**
 * testNl
 *
 * @return void
 */
	public function testNl() {
		$this->io->expects($this->once())
			->method('nl')
			->with(2);

		$this->Shell->nl(2);
	}

/**
 * testHr
 *
 * @return void
 */
	public function testHr() {
		$this->io->expects($this->once())
			->method('hr')
			->with(2);

		$this->Shell->hr(2);
	}

/**
 * testError
 *
 * @return void
 */
	public function testError() {
		$this->io->expects($this->at(0))
			->method('err')
			->with('<error>Error:</error> Foo Not Found');

		$this->io->expects($this->at(1))
			->method('err')
			->with("Searched all...");

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
		$this->assertInstanceOf('Cake\Console\Command\Task\TestAppleTask', $this->Shell->TestApple);

		$this->Shell->tasks = 'TestBanana';
		$this->assertTrue($this->Shell->loadTasks());
		$this->assertInstanceOf('Cake\Console\Command\Task\TestAppleTask', $this->Shell->TestApple);
		$this->assertInstanceOf('Cake\Console\Command\Task\TestBananaTask', $this->Shell->TestBanana);

		unset($this->Shell->ShellTestApple, $this->Shell->TestBanana);

		$this->Shell->tasks = array('TestApple', 'TestBanana');
		$this->assertTrue($this->Shell->loadTasks());
		$this->assertInstanceOf('Cake\Console\Command\Task\TestAppleTask', $this->Shell->TestApple);
		$this->assertInstanceOf('Cake\Console\Command\Task\TestBananaTask', $this->Shell->TestBanana);
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
		$path = $expected = DS . 'tmp/ab/cd';
		$this->assertPathEquals($expected, $this->Shell->shortPath($path));

		$path = $expected = DS . 'tmp/ab/cd/';
		$this->assertPathEquals($expected, $this->Shell->shortPath($path));

		$path = $expected = DS . 'tmp/ab/index.php';
		$this->assertPathEquals($expected, $this->Shell->shortPath($path));

		$path = DS . 'tmp/ab/' . DS . 'cd';
		$expected = DS . 'tmp/ab/cd';
		$this->assertPathEquals($expected, $this->Shell->shortPath($path));

		$path = 'tmp/ab';
		$expected = 'tmp/ab';
		$this->assertPathEquals($expected, $this->Shell->shortPath($path));

		$path = 'tmp/ab';
		$expected = 'tmp/ab';
		$this->assertPathEquals($expected, $this->Shell->shortPath($path));

		$path = APP;
		$result = $this->Shell->shortPath($path);
		$this->assertNotContains(ROOT, $result, 'Short paths should not contain ROOT');
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

		$contents = "<?php{$eol}echo 'test';${eol}\$te = 'st';{$eol}";
		$result = $this->Shell->createFile($file, $contents);
		$this->assertTrue($result);
		$this->assertTrue(file_exists($file));
		$this->assertEquals(file_get_contents($file), $contents);
	}

/**
 * Test that files are not changed with a 'n' reply.
 *
 * @return void
 */
	public function testCreateFileNoReply() {
		$eol = PHP_EOL;
		$path = TMP . 'shell_test';
		$file = $path . DS . 'file1.php';

		new Folder($path, true);

		$this->io->expects($this->once())
			->method('askChoice')
			->will($this->returnValue('n'));

		touch($file);
		$this->assertTrue(file_exists($file));

		$contents = "My content";
		$result = $this->Shell->createFile($file, $contents);
		$this->assertTrue(file_exists($file));
		$this->assertTextEquals('', file_get_contents($file));
		$this->assertFalse($result, 'Did not create file.');
	}

/**
 * Test that files are changed with a 'y' reply.
 *
 * @return void
 */
	public function testCreateFileOverwrite() {
		$eol = PHP_EOL;
		$path = TMP . 'shell_test';
		$file = $path . DS . 'file1.php';

		new Folder($path, true);

		$this->io->expects($this->once())
			->method('askChoice')
			->will($this->returnValue('y'));

		touch($file);
		$this->assertTrue(file_exists($file));

		$contents = "My content";
		$result = $this->Shell->createFile($file, $contents);
		$this->assertTrue(file_exists($file));
		$this->assertTextEquals($contents, file_get_contents($file));
		$this->assertTrue($result, 'Did create file.');
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
		$Mock = $this->getMock('Cake\Console\Shell', array('main', 'startup'), array(), '', false);

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
		$Mock = $this->getMock('Cake\Console\Shell', array('hit_me', 'startup'), array(), '', false);

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
		$Mock = $this->getMock('Cake\Console\Shell', array('startup', 'getOptionParser', 'out'), array(), '', false);
		$Parser = $this->getMock('Cake\Console\ConsoleOptionParser', array(), array(), '', false);

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
		$Mock = $this->getMock('Cake\Console\Shell', array('startup', 'getOptionParser', 'out'), array(), '', false);
		$Parser = $this->getMock('Cake\Console\ConsoleOptionParser', array(), array(), '', false);

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
		$Parser = $this->getMock('Cake\Console\ConsoleOptionParser', array(), array(), '', false);
		$Parser->expects($this->once())->method('parse')
			->with(array('--help'))
			->will($this->returnValue(array(array('help' => true), array())));
		$Parser->expects($this->once())->method('help');

		$Shell = $this->getMock('Cake\Console\Shell', array('getOptionParser', 'out', 'startup', '_welcome'), array(), '', false);
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
		$Shell = $this->getMock('Cake\Console\Shell', array('hasTask', 'startup'), array(), '', false);
		$task = $this->getMock('Cake\Console\Shell', array('execute', 'runCommand'), array(), '', false);
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
 * Test file and console and logging quiet output
 *
 * @return void
 */
	public function testQuietLog() {
		$io = $this->getMock('Cake\Console\ConsoleIo', [], [], '', false);
		$io->expects($this->once())
			->method('level')
			->with(Shell::QUIET);
		$io->expects($this->at(0))
			->method('setLoggers')
			->with(true);
		$io->expects($this->at(2))
			->method('setLoggers')
			->with(false);

		$this->Shell = $this->getMock(__NAMESPACE__ . '\ShellTestShell', array('_useLogger'), array($io));
		$this->Shell->runCommand('foo', array('--quiet'));
	}

}
