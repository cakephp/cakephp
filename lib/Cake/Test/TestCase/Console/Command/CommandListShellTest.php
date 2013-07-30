<?php
/**
 * CommandListShellTest file
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
 * @since         CakePHP v 2.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Console\Command;

use Cake\Console\Command\CommandListShell;
use Cake\Console\ConsoleOutput;
use Cake\Core\App;
use Cake\Core\Plugin;
use Cake\TestSuite\TestCase;

/**
 * Class TestStringOutput
 *
 * @package       Cake.Test.Case.Console.Command
 */
class TestStringOutput extends ConsoleOutput {

	public $output = '';

	protected function _write($message) {
		$this->output .= $message;
	}

}

/**
 * Class CommandListShellTest
 *
 * @package       Cake.Test.Case.Console.Command
 */
class CommandListShellTest extends TestCase {

/**
 * setUp method
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();
		App::build(array(
			'Plugin' => array(
				CAKE . 'Test/TestApp/Plugin/'
			),
			'Console/Command' => array(
				CAKE . 'Test/TestApp/Console/Command/'
			)
		), App::RESET);
		Plugin::load(array('TestPlugin', 'TestPluginTwo'));

		$out = new TestStringOutput();
		$in = $this->getMock('Cake\Console\ConsoleInput', array(), array(), '', false);

		$this->Shell = $this->getMock(
			'Cake\Console\Command\CommandListShell',
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
		Plugin::unload();
	}

/**
 * test that main finds core shells.
 *
 * @return void
 */
	public function testMain() {
		$this->Shell->main();
		$output = $this->Shell->stdout->output;

		$expected = "/\[.*TestPlugin.*\] example/";
		$this->assertRegExp($expected, $output);

		$expected = "/\[.*TestPluginTwo.*\] example, welcome/";
		$this->assertRegExp($expected, $output);

		$expected = "/\[.*CORE.*\] acl, api, bake, command_list, console, i18n, schema, server, test, testsuite, upgrade/";
		$this->assertRegExp($expected, $output);

		$expected = "/\[.*app.*\] sample/";
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
