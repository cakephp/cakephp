<?php
/**
 * CompletionShellTest file
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
 * @since         CakePHP v 2.5
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */

App::uses('CompletionShell', 'Console/Command');
App::uses('ConsoleOutput', 'Console');
App::uses('ConsoleInput', 'Console');
App::uses('Shell', 'Console');
App::uses('CommandTask', 'Console/Command/Task');

/**
 * Class TestCompletionStringOutput
 *
 * @package       Cake.Test.Case.Console.Command
 */
class TestCompletionStringOutput extends ConsoleOutput {

	public $output = '';

	protected function _write($message) {
		$this->output .= $message;
	}

}

/**
 * Class CompletionShellTest
 *
 * @package       Cake.Test.Case.Console.Command
 */
class CompletionShellTest extends CakeTestCase {

/**
 * setUp method
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();
		App::build(array(
			'Plugin' => array(
				CAKE . 'Test' . DS . 'test_app' . DS . 'Plugin' . DS
			),
			'Console/Command' => array(
				CAKE . 'Test' . DS . 'test_app' . DS . 'Console' . DS . 'Command' . DS
			)
		), App::RESET);
		CakePlugin::load(array('TestPlugin', 'TestPluginTwo'));

		$out = new TestCompletionStringOutput();
		$in = $this->getMock('ConsoleInput', array(), array(), '', false);

		$this->Shell = $this->getMock(
			'CompletionShell',
			array('in', '_stop', 'clear'),
			array($out, $out, $in)
		);

		$this->Shell->Command = $this->getMock(
			'CommandTask',
			array('in', '_stop', 'clear'),
			array($out, $out, $in)
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
		CakePlugin::unload();
	}

/**
 * test that the startup method supresses the shell header
 *
 * @return void
 */
	public function testStartup() {
		$this->Shell->runCommand('main', array());
		$output = $this->Shell->stdout->output;

		$needle = 'Welcome to CakePHP';
		$this->assertTextNotContains($needle, $output);
	}

/**
 * test that main displays a warning
 *
 * @return void
 */
	public function testMain() {
		$this->Shell->runCommand('main', array());
		$output = $this->Shell->stdout->output;

		$expected = "/This command is not intended to be called manually/";
		$this->assertRegExp($expected, $output);
	}

/**
 * test commands method that list all available commands
 *
 * @return void
 */
	public function testCommands() {
		$this->Shell->runCommand('commands', array());
		$output = $this->Shell->stdout->output;

		$expected = "TestPlugin.example TestPluginTwo.example TestPluginTwo.welcome acl api bake command_list completion console i18n schema server test testsuite upgrade sample\n";
		$this->assertEquals($expected, $output);
	}

/**
 * test that options without argument returns the default options
 *
 * @return void
 */
	public function testOptionsNoArguments() {
		$this->Shell->runCommand('options', array());
		$output = $this->Shell->stdout->output;

		$expected = "--help -h --verbose -v --quiet -q\n";
		$this->assertEquals($expected, $output);
	}

/**
 * test that options with a nonexisting command returns the default options
 *
 * @return void
 */
	public function testOptionsNonExistingCommand() {
		$this->Shell->runCommand('options', array('options', 'foo'));
		$output = $this->Shell->stdout->output;

		$expected = "--help -h --verbose -v --quiet -q\n";
		$this->assertEquals($expected, $output);
	}

/**
 * test that options with a existing command returns the proper options
 *
 * @return void
 */
	public function testOptions() {
		$this->Shell->runCommand('options', array('options', 'bake'));
		$output = $this->Shell->stdout->output;

		$expected = "--help -h --verbose -v --quiet -q --connection -c --theme -t\n";
		$this->assertEquals($expected, $output);
	}

/**
 * test that subCommands with a existing CORE command returns the proper sub commands
 *
 * @return void
 */
	public function testSubCommandsCorePlugin() {
		$this->Shell->runCommand('subCommands', array('subCommands', 'CORE.bake'));
		$output = $this->Shell->stdout->output;

		$expected = "controller db_config fixture model plugin project test view\n";
		$this->assertEquals($expected, $output);
	}

/**
 * test that subCommands with a existing APP command returns the proper sub commands (in this case none)
 *
 * @return void
 */
	public function testSubCommandsAppPlugin() {
		$this->Shell->runCommand('subCommands', array('subCommands', 'app.sample'));
		$output = $this->Shell->stdout->output;

		$expected = '';
		$this->assertEquals($expected, $output);
	}

/**
 * test that subCommands with a existing plugin command returns the proper sub commands
 *
 * @return void
 */
	public function testSubCommandsPlugin() {
		$this->Shell->runCommand('subCommands', array('subCommands', 'TestPluginTwo.welcome'));
		$output = $this->Shell->stdout->output;

		$expected = "say_hello\n";
		$this->assertEquals($expected, $output);
	}

/**
 * test that subcommands without arguments returns nothing
 *
 * @return void
 */
	public function testSubCommandsNoArguments() {
		$this->Shell->runCommand('subCommands', array());
		$output = $this->Shell->stdout->output;

		$expected = '';
		$this->assertEquals($expected, $output);
	}

/**
 * test that subcommands with a nonexisting command returns nothing
 *
 * @return void
 */
	public function testSubCommandsNonExistingCommand() {
		$this->Shell->runCommand('subCommands', array('subCommands', 'foo'));
		$output = $this->Shell->stdout->output;

		$expected = '';
		$this->assertEquals($expected, $output);
	}

/**
 * test that subcommands returns the available subcommands for the given command
 *
 * @return void
 */
	public function testSubCommands() {
		$this->Shell->runCommand('subCommands', array('subCommands', 'bake'));
		$output = $this->Shell->stdout->output;

		$expected = "controller db_config fixture model plugin project test view\n";
		$this->assertEquals($expected, $output);
	}

/**
 * test that fuzzy returns nothing
 *
 * @return void
 */
	public function testFuzzy() {
		$this->Shell->runCommand('fuzzy', array());
		$output = $this->Shell->stdout->output;

		$expected = '';
		$this->assertEquals($expected, $output);
	}
}
