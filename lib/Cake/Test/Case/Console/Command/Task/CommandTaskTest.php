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
 * @package       Cake.Test.Case.Console.Command
 * @since         CakePHP v 2.5
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */

App::uses('CommandTask', 'Console/Command/Task');

/**
 * CommandTaskTest class
 *
 * @package   Cake.Test.Case.Console.Command.Task
 */
class CommandTaskTest extends CakeTestCase {

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

		$out = $this->getMock('ConsoleOutput', array(), array(), '', false);
		$in = $this->getMock('ConsoleInput', array(), array(), '', false);

		$this->CommandTask = $this->getMock(
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
		unset($this->CommandTask);
		CakePlugin::unload();
	}

/**
 * Test the resulting list of shells
 *
 * @return void
 */
	public function testGetShellList() {
		$result = $this->CommandTask->getShellList();

		$expected = array(
			'CORE' => array(
				'acl',
				'api',
				'bake',
				'command_list',
				'completion',
				'console',
				'i18n',
				'schema',
				'server',
				'test',
				'testsuite',
				'upgrade'
			),
			'TestPlugin' => array(
				'example',
				'test_plugin'
			),
			'TestPluginTwo' => array(
				'example',
				'welcome'
			),
			'app' => array(
				'sample'
			),
		);
		$this->assertEquals($expected, $result);
	}

/**
 * Test the resulting list of commands
 *
 * @return void
 */
	public function testCommands() {
		$result = $this->CommandTask->commands();

		$expected = array(
			'TestPlugin.example',
			'TestPlugin.test_plugin',
			'TestPluginTwo.example',
			'TestPluginTwo.welcome',
			'acl',
			'api',
			'bake',
			'command_list',
			'completion',
			'console',
			'i18n',
			'schema',
			'server',
			'test',
			'testsuite',
			'upgrade',
			'sample'
		);
		$this->assertEquals($expected, $result);
	}

/**
 * Test the resulting list of subcommands for the given command
 *
 * @return void
 */
	public function testSubCommands() {
		$result = $this->CommandTask->subCommands('acl');

		$expected = array(
			'check',
			'create',
			'db_config',
			'delete',
			'deny',
			'getPath',
			'grant',
			'inherit',
			'initdb',
			'nodeExists',
			'parseIdentifier',
			'setParent',
			'view'
		);
		$this->assertEquals($expected, $result);
	}

/**
 * Test that unknown commands return an empty array
 *
 * @return void
 */
	public function testSubCommandsUnknownCommand() {
		$result = $this->CommandTask->subCommands('yoghurt');

		$expected = array();
		$this->assertEquals($expected, $result);
	}

/**
 * Test that getting a existing shell returns the shell instance
 *
 * @return void
 */
	public function testGetShell() {
		$result = $this->CommandTask->getShell('acl');
		$this->assertInstanceOf('AclShell', $result);
	}

/**
 * Test that getting a non-existing shell returns false
 *
 * @return void
 */
	public function testGetShellNonExisting() {
		$result = $this->CommandTask->getShell('strawberry');
		$this->assertFalse($result);
	}

/**
 * Test that getting a existing core shell with 'core.' prefix returns the correct shell instance
 *
 * @return void
 */
	public function testGetShellCore() {
		$result = $this->CommandTask->getShell('core.bake');
		$this->assertInstanceOf('BakeShell', $result);
	}

/**
 * Test the options array for a known command
 *
 * @return void
 */
	public function testOptions() {
		$result = $this->CommandTask->options('bake');

		$expected = array(
			'--help',
			'-h',
			'--verbose',
			'-v',
			'--quiet',
			'-q',
			'--connection',
			'-c',
			'--theme',
			'-t'
		);
		$this->assertEquals($expected, $result);
	}

/**
 * Test the options array for an unknown command
 *
 * @return void
 */
	public function testOptionsUnknownCommand() {
		$result = $this->CommandTask->options('pie');

		$expected = array(
			'--help',
			'-h',
			'--verbose',
			'-v',
			'--quiet',
			'-q'
		);
		$this->assertEquals($expected, $result);
	}

}
