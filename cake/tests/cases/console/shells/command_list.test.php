<?php
/**
 * CommandList file
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
 * @since         CakePHP v 2.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
App::import('Shell', 'CommandList', false);

require_once CAKE . 'console' .  DS . 'shell_dispatcher.php';

class TestStringOutput extends ConsoleOutput {
	public $output = '';

	protected function _write($message) {
		$this->output .= $message;
	}
}

class CommandListTest extends CakeTestCase {
/**
 * setUp method
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();
		App::build(array(
			'plugins' => array(
				TEST_CAKE_CORE_INCLUDE_PATH . 'tests' . DS . 'test_app' . DS . 'plugins' . DS
			),
			'shells' => array(
				CORE_PATH ? CONSOLE_LIBS : ROOT . DS . CONSOLE_LIBS,
				TEST_CAKE_CORE_INCLUDE_PATH . 'tests' . DS . 'test_app' . DS . 'vendors' . DS . 'shells' . DS
			)
		), true);
		App::objects('plugins', null, false);

		$this->Dispatcher = $this->getMock(
			'ShellDispatcher', 
			array('_stop', '_initEnvironment', 'dispatch')
		);
		$out = new TestStringOutput();
		$in = $this->getMock('ConsoleInput', array(), array(), '', false);

		$this->Shell = $this->getMock(
			'CommandListShell',
			array('in', '_stop', 'clear'),
			array(&$this->Dispatcher, $out, $out, $in)
		);
	}

/**
 * teardown
 *
 * @return void
 */
	function tearDown() {
		unset($this->Dispatcher, $this->Shell);
	}

/**
 * test that main finds core shells.
 *
 * @return void
 */
	function testMain() {
		$this->Shell->main();
		$output = $this->Shell->stdout->output;

		$expected = "/example \[.*TestPlugin, TestPluginTwo.*\]/";
	 	$this->assertPattern($expected, $output);

		$expected = "/welcome \[.*TestPluginTwo.*\]/";
	 	$this->assertPattern($expected, $output);

		$expected = "/acl \[.*CORE.*\]/";
	 	$this->assertPattern($expected, $output);

		$expected = "/api \[.*CORE.*\]/";
	 	$this->assertPattern($expected, $output);

		$expected = "/bake \[.*CORE.*\]/";
	 	$this->assertPattern($expected, $output);

		$expected = "/console \[.*CORE.*\]/";
	 	$this->assertPattern($expected, $output);

		$expected = "/i18n \[.*CORE.*\]/";
	 	$this->assertPattern($expected, $output);

		$expected = "/schema \[.*CORE.*\]/";
	 	$this->assertPattern($expected, $output);

		$expected = "/testsuite \[.*CORE.*\]/";
	 	$this->assertPattern($expected, $output);

		$expected = "/sample \[.*test_app.*\]/";
		$this->assertPattern($expected, $output);
	}
}
