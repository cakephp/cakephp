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
 * @since         2.5.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Console\Command;

use Cake\Console\Command\CompletionShell;
use Cake\Console\Command\Task\CommandTask;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOutput;
use Cake\Console\Shell;
use Cake\Core\Plugin;
use Cake\TestSuite\TestCase;

/**
 * Class TestCompletionStringOutput
 *
 */
class TestCompletionStringOutput extends ConsoleOutput {

	public $output = '';

	protected function _write($message) {
		$this->output .= $message;
	}

}

/**
 * Class CompletionShellTest
 */
class CompletionShellTest extends TestCase {

/**
 * setUp method
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();
		Plugin::load(array('TestPlugin', 'TestPluginTwo'));

		$this->out = new TestCompletionStringOutput();
		$io = new ConsoleIo($this->out);

		$this->Shell = $this->getMock(
			'Cake\Console\Command\CompletionShell',
			['in', '_stop', 'clear'],
			[$io]
		);

		$this->Shell->Command = $this->getMock(
			'Cake\Console\Command\Task\CommandTask',
			['in', '_stop', 'clear'],
			[$io]
		);
	}

/**
 * tearDown
 *
 * @return void
 */
	public function tearDown() {
		parent::tearDown();
		unset($this->Shell);
		Plugin::unload();
	}

/**
 * test that the startup method supresses the shell header
 *
 * @return void
 */
	public function testStartup() {
		$this->Shell->runCommand(['main']);
		$output = $this->out->output;

		$needle = 'Welcome to CakePHP';
		$this->assertTextNotContains($needle, $output);
	}

/**
 * test that main displays a warning
 *
 * @return void
 */
	public function testMain() {
		$this->Shell->runCommand(['main']);
		$output = $this->out->output;

		$expected = "/This command is not intended to be called manually/";
		$this->assertRegExp($expected, $output);
	}

/**
 * test commands method that list all available commands
 *
 * @return void
 */
	public function testCommands() {
		$this->Shell->runCommand(['commands']);
		$output = $this->out->output;

		$expected = "TestPlugin.example TestPluginTwo.example TestPluginTwo.welcome bake i18n server test sample\n";
		$this->assertEquals($expected, $output);
	}

/**
 * test that options without argument returns the default options
 *
 * @return void
 */
	public function testOptionsNoArguments() {
		$this->Shell->runCommand(['options']);
		$output = $this->out->output;

		$expected = "--help -h --verbose -v --quiet -q\n";
		$this->assertEquals($expected, $output);
	}

/**
 * test that options with a nonexisting command returns the default options
 *
 * @return void
 */
	public function testOptionsNonExistingCommand() {
		$this->Shell->runCommand(['options', 'foo']);
		$output = $this->out->output;

		$expected = "--help -h --verbose -v --quiet -q\n";
		$this->assertEquals($expected, $output);
	}

/**
 * test that options with a existing command returns the proper options
 *
 * @return void
 */
	public function testOptions() {
		$this->Shell->runCommand(['options', 'bake']);
		$output = $this->out->output;

		$expected = "--help -h --verbose -v --quiet -q --connection -c --theme -t\n";
		$this->assertEquals($expected, $output);
	}

/**
 * test that subCommands with a existing CORE command returns the proper sub commands
 *
 * @return void
 */
	public function testSubCommandsCorePlugin() {
		$this->Shell->runCommand(['subcommands', 'CORE.bake']);
		$output = $this->out->output;

		$expected = "behavior cell component controller fixture helper model plugin project shell test view widget zerg\n";
		$this->assertEquals($expected, $output);
	}

/**
 * test that subCommands with a existing APP command returns the proper sub commands (in this case none)
 *
 * @return void
 */
	public function testSubCommandsAppPlugin() {
		$this->Shell->runCommand(['subcommands', 'app.sample']);
		$output = $this->out->output;

		$expected = '';
		$this->assertEquals($expected, $output);
	}

/**
 * test that subCommands with a existing plugin command returns the proper sub commands
 *
 * @return void
 */
	public function testSubCommandsPlugin() {
		$this->Shell->runCommand(['subcommands', 'TestPluginTwo.welcome']);
		$output = $this->out->output;

		$expected = "say_hello\n";
		$this->assertEquals($expected, $output);
	}

/**
 * test that subcommands without arguments returns nothing
 *
 * @return void
 */
	public function testSubCommandsNoArguments() {
		$this->Shell->runCommand(['subcommands']);
		$output = $this->out->output;

		$expected = '';
		$this->assertEquals($expected, $output);
	}

/**
 * test that subcommands with a nonexisting command returns nothing
 *
 * @return void
 */
	public function testSubCommandsNonExistingCommand() {
		$this->Shell->runCommand(['subcommands', 'foo']);
		$output = $this->out->output;

		$expected = '';
		$this->assertEquals($expected, $output);
	}

/**
 * test that subcommands returns the available subcommands for the given command
 *
 * @return void
 */
	public function testSubCommands() {
		$this->Shell->runCommand(['subcommands', 'bake']);
		$output = $this->out->output;

		$expected = "behavior cell component controller fixture helper model plugin project shell test view widget zerg\n";
		$this->assertEquals($expected, $output);
	}

/**
 * test that fuzzy returns nothing
 *
 * @return void
 */
	public function testFuzzy() {
		$this->Shell->runCommand(['fuzzy']);
		$output = $this->out->output;

		$expected = '';
		$this->assertEquals($expected, $output);
	}

}
