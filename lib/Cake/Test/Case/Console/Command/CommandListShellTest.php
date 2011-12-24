<?php
/**
 * CommandListShellTest file
 *
 * PHP 5
 *
 * CakePHP :  Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2011, Cake Software Foundation, Inc.
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2011, Cake Software Foundation, Inc.
 * @link          http://cakephp.org CakePHP Project
 * @package       Cake.Test.Case.Console.Command
 * @since         CakePHP v 2.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

App::uses('CommandListShell', 'Console/Command');
App::uses('ConsoleOutput', 'Console');
App::uses('ConsoleInput', 'Console');
App::uses('Shell', 'Console');


class TestStringOutput extends ConsoleOutput {
	public $output = '';

	protected function _write($message) {
		$this->output .= $message;
	}
}

class CommandListShellTest extends CakeTestCase {
/**
 * setUp method
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();
		App::build(array(
			'plugins' => array(
				CAKE . 'Test' . DS . 'test_app' . DS . 'Plugin' . DS
			),
			'Console/Command' => array(
				CAKE . 'Test' . DS . 'test_app' . DS . 'Console' . DS . 'Command' . DS
			)
		), true);
		CakePlugin::loadAll();

		$out = new TestStringOutput();
		$in = $this->getMock('ConsoleInput', array(), array(), '', false);

		$this->Shell = $this->getMock(
			'CommandListShell',
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
 * test that main finds core shells.
 *
 * @return void
 */
	public function testMain() {
		$this->Shell->main();
		$output = $this->Shell->stdout->output;

		$expected = "/example \[.*TestPlugin, TestPluginTwo.*\]/";
		$this->assertRegExp($expected, $output);

		$expected = "/welcome \[.*TestPluginTwo.*\]/";
		$this->assertRegExp($expected, $output);


		$expected = "/acl \[.*CORE.*\]/";
		$this->assertRegExp($expected, $output);

		$expected = "/api \[.*CORE.*\]/";
		$this->assertRegExp($expected, $output);

		$expected = "/bake \[.*CORE.*\]/";
		$this->assertRegExp($expected, $output);

		$expected = "/console \[.*CORE.*\]/";
		$this->assertRegExp($expected, $output);

		$expected = "/i18n \[.*CORE.*\]/";
		$this->assertRegExp($expected, $output);

		$expected = "/schema \[.*CORE.*\]/";
		$this->assertRegExp($expected, $output);

		$expected = "/testsuite \[.*CORE.*\]/";
		$this->assertRegExp($expected, $output);

		$expected = "/sample \[.*app.*\]/";
		$this->assertRegExp($expected, $output);
	}

/**
 * Test the sort param
 *
 * @return void
 */
	public function testSortPlugin() {
		$this->Shell->params['sort'] = true;
		$this->Shell->main();

		$output = $this->Shell->stdout->output;

		$expected = "/\[.*App.*\]\\v*[ ]+sample/";
		$this->assertRegExp($expected, $output);

		$expected = "/\[.*TestPluginTwo.*\]\\v*[ ]+example, welcome/";
		$this->assertRegExp($expected, $output);

		$expected = "/\[.*TestPlugin.*\]\\v*[ ]+example/";
		$this->assertRegExp($expected, $output);

		$expected = "/\[.*Core.*\]\\v*[ ]+acl, api, bake, command_list, console, i18n, schema, test, testsuite/";
		$this->assertRegExp($expected, $output);
	}

/**
 * test xml output.
 *
 * @return void
 */
	public function testMainXml() {
		$this->Shell->params['xml'] = true;
		$this->Shell->main();

		$output = $this->Shell->stdout->output;

		$find = '<shell name="sample" call_as="sample" provider="app" help="sample -h"/>';
		$this->assertContains($find, $output);

		$find = '<shell name="bake" call_as="bake" provider="CORE" help="bake -h"/>';
		$this->assertContains($find, $output);

		$find = '<shell name="welcome" call_as="TestPluginTwo.welcome" provider="TestPluginTwo" help="TestPluginTwo.welcome -h"/>';
		$this->assertContains($find, $output);
	}
}
