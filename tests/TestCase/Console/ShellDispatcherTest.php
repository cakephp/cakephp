<?php
/**
 * CakePHP(tm) Tests <http://book.cakephp.org/2.0/en/development/testing.html>
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/2.0/en/development/testing.html CakePHP(tm) Tests
 * @since         1.2.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Console;

use Cake\Console\ShellDispatcher;
use Cake\Core\App;
use Cake\Core\Configure;
use Cake\Core\Plugin;
use Cake\TestSuite\TestCase;

/**
 * ShellDispatcherTest
 *
 */
class ShellDispatcherTest extends TestCase {

/**
 * setUp method
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();
		Plugin::load('TestPlugin');
		Configure::write('App.namespace', 'TestApp');
		$this->dispatcher = $this->getMock('Cake\Console\ShellDispatcher', ['_stop']);
	}

/**
 * teardown
 *
 * @return void
 */
	public function tearDown() {
		parent::tearDown();
		ShellDispatcher::resetAliases();
	}

/**
 * Test error on missing shell
 *
 * @expectedException \Cake\Console\Exception\MissingShellException
 * @return void
 */
	public function testFindShellMissing() {
		$this->dispatcher->findShell('nope');
	}

/**
 * Test error on missing plugin shell
 *
 * @expectedException \Cake\Console\Exception\MissingShellException
 * @return void
 */
	public function testFindShellMissingPlugin() {
		$this->dispatcher->findShell('test_plugin.nope');
	}

/**
 * Verify loading of (plugin-) shells
 *
 * @return void
 */
	public function testFindShell() {
		$result = $this->dispatcher->findShell('sample');
		$this->assertInstanceOf('TestApp\Shell\SampleShell', $result);

		$result = $this->dispatcher->findShell('test_plugin.example');
		$this->assertInstanceOf('TestPlugin\Shell\ExampleShell', $result);
		$this->assertEquals('TestPlugin', $result->plugin);
		$this->assertEquals('Example', $result->name);

		$result = $this->dispatcher->findShell('TestPlugin.example');
		$this->assertInstanceOf('TestPlugin\Shell\ExampleShell', $result);

		$result = $this->dispatcher->findShell('TestPlugin.Example');
		$this->assertInstanceOf('TestPlugin\Shell\ExampleShell', $result);
	}

/**
 * testAddShortPluginAlias
 *
 * @return void
 */
	public function testAddShortPluginAlias() {
		$expected = [
			'Example' => 'TestPlugin.example'
		];
		$result = $this->dispatcher->addShortPluginAliases();
		$this->assertSame($expected, $result, 'Should return the list of aliased plugin shells');

		ShellDispatcher::alias('Example', 'SomeOther.PluginsShell');
		$expected = [
			'Example' => 'SomeOther.PluginsShell'
		];
		$result = $this->dispatcher->addShortPluginAliases();
		$this->assertSame($expected, $result, 'Should not overwrite existing aliases');
	}

/**
 * Test getting shells with aliases.
 *
 * @return void
 */
	public function testFindShellAliased() {
		ShellDispatcher::alias('short', 'test_plugin.example');

		$result = $this->dispatcher->findShell('short');
		$this->assertInstanceOf('TestPlugin\Shell\ExampleShell', $result);
		$this->assertEquals('TestPlugin', $result->plugin);
		$this->assertEquals('Example', $result->name);
	}

/**
 * Test finding a shell that has a matching alias.
 *
 * Aliases should not overload concrete shells.
 *
 * @return void
 */
	public function testFindShellAliasedAppShadow() {
		ShellDispatcher::alias('sample', 'test_plugin.example');

		$result = $this->dispatcher->findShell('sample');
		$this->assertInstanceOf('TestApp\Shell\SampleShell', $result);
		$this->assertEmpty($result->plugin);
		$this->assertEquals('Sample', $result->name);
	}

/**
 * Verify correct dispatch of Shell subclasses with a main method
 *
 * @return void
 */
	public function testDispatchShellWithMain() {
		$dispatcher = $this->getMock('Cake\Console\ShellDispatcher', ['findShell']);
		$Shell = $this->getMock('Cake\Console\Shell');

		$Shell->expects($this->exactly(2))->method('initialize');
		$Shell->expects($this->at(0))->method('runCommand')
			->will($this->returnValue(true));
		$Shell->expects($this->at(1))->method('runCommand')
			->will($this->returnValue(null));

		$dispatcher->expects($this->any())
			->method('findShell')
			->with('mock_with_main')
			->will($this->returnValue($Shell));

		$dispatcher->args = array('mock_with_main');
		$result = $dispatcher->dispatch();
		$this->assertSame(0, $result);
		$this->assertEquals(array(), $dispatcher->args);

		$dispatcher->args = array('mock_with_main');
		$result = $dispatcher->dispatch();
		$this->assertSame(0, $result);
		$this->assertEquals(array(), $dispatcher->args);
	}

/**
 * Verify correct dispatch of Shell subclasses without a main method
 *
 * @return void
 */
	public function testDispatchShellWithoutMain() {
		$dispatcher = $this->getMock('Cake\Console\ShellDispatcher', ['findShell']);
		$Shell = $this->getMock('Cake\Console\Shell');

		$Shell->expects($this->once())->method('initialize');
		$Shell->expects($this->once())->method('runCommand')
			->with(['initdb'])
			->will($this->returnValue(true));

		$dispatcher->expects($this->any())
			->method('findShell')
			->with('mock_without_main')
			->will($this->returnValue($Shell));

		$dispatcher->args = array('mock_without_main', 'initdb');
		$result = $dispatcher->dispatch();
		$this->assertEquals(0, $result);
	}

/**
 * Verify you can dispatch a plugin's main shell with the shell name alone
 *
 * @return void
 */
	public function testDispatchShortPluginAlias() {
		$dispatcher = $this->getMock(
			'Cake\Console\ShellDispatcher',
			['_shellExists', '_createShell']
		);
		$Shell = $this->getMock('Cake\Console\Shell');

		$dispatcher->expects($this->at(1))
			->method('_shellExists')
			->with('TestPlugin.Example')
			->will($this->returnValue('TestPlugin\Console\Command\TestPluginShell'));

		$dispatcher->expects($this->at(2))
			->method('_createShell')
			->with('TestPlugin\Console\Command\TestPluginShell', 'TestPlugin.Example')
			->will($this->returnValue($Shell));

		$dispatcher->args = array('example');
		$result = $dispatcher->dispatch();
		$this->assertEquals(0, $result);
	}

/**
 * Ensure short plugin shell usage is case/camelized insensitive
 *
 * @return void
 */
	public function testDispatchShortPluginAliasCamelized() {
		$dispatcher = $this->getMock(
			'Cake\Console\ShellDispatcher',
			['_shellExists', '_createShell']
		);
		$Shell = $this->getMock('Cake\Console\Shell');

		$dispatcher->expects($this->at(1))
			->method('_shellExists')
			->with('TestPlugin.Example')
			->will($this->returnValue('TestPlugin\Console\Command\TestPluginShell'));

		$dispatcher->expects($this->at(2))
			->method('_createShell')
			->with('TestPlugin\Console\Command\TestPluginShell', 'TestPlugin.Example')
			->will($this->returnValue($Shell));

		$dispatcher->args = ['Example'];
		$result = $dispatcher->dispatch();
		$this->assertEquals(0, $result);
	}

/**
 * Verify that in case of conflict, app shells take precedence in alias list
 *
 * @return void
 */
	public function testDispatchShortPluginAliasConflict() {
		$dispatcher = $this->getMock(
			'Cake\Console\ShellDispatcher',
			['_shellExists', '_createShell']
		);
		$Shell = $this->getMock('Cake\Console\Shell');

		$dispatcher->expects($this->at(1))
			->method('_shellExists')
			->with('Sample')
			->will($this->returnValue('App\Shell\SampleShell'));

		$dispatcher->expects($this->at(2))
			->method('_createShell')
			->with('App\Shell\SampleShell', 'Sample')
			->will($this->returnValue($Shell));

		$dispatcher->args = array('sample');
		$result = $dispatcher->dispatch();
		$this->assertEquals(0, $result);
	}

/**
 * Verify shifting of arguments
 *
 * @return void
 */
	public function testShiftArgs() {
		$this->dispatcher->args = array('a', 'b', 'c');
		$this->assertEquals('a', $this->dispatcher->shiftArgs());
		$this->assertSame($this->dispatcher->args, array('b', 'c'));

		$this->dispatcher->args = array('a' => 'b', 'c', 'd');
		$this->assertEquals('b', $this->dispatcher->shiftArgs());
		$this->assertSame($this->dispatcher->args, array('c', 'd'));

		$this->dispatcher->args = array('a', 'b' => 'c', 'd');
		$this->assertEquals('a', $this->dispatcher->shiftArgs());
		$this->assertSame($this->dispatcher->args, array('b' => 'c', 'd'));

		$this->dispatcher->args = array(0 => 'a', 2 => 'b', 30 => 'c');
		$this->assertEquals('a', $this->dispatcher->shiftArgs());
		$this->assertSame($this->dispatcher->args, array(0 => 'b', 1 => 'c'));

		$this->dispatcher->args = array();
		$this->assertNull($this->dispatcher->shiftArgs());
		$this->assertSame(array(), $this->dispatcher->args);
	}

}
