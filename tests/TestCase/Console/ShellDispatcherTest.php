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
 * TestShellDispatcher class
 *
 */
class TestShellDispatcher extends ShellDispatcher {

/**
 * params property
 *
 * @var array
 */
	public $params = array();

/**
 * stopped property
 *
 * @var string
 */
	public $stopped = null;

/**
 * TestShell
 *
 * @var mixed
 */
	public $TestShell;

/**
 * _initEnvironment method
 *
 * @return void
 */
	protected function _initEnvironment() {
	}

/**
 * clear method
 *
 * @return void
 */
	public function clear() {
	}

/**
 * _stop method
 *
 * @return void
 */
	protected function _stop($status = 0) {
		$this->stopped = 'Stopped with status: ' . $status;
		return $status;
	}

/**
 * getShell
 *
 * @param string $shell
 * @return mixed
 */
	public function getShell($shell) {
		return $this->_getShell($shell);
	}

/**
 * _getShell
 *
 * @param string $shell
 * @return mixed
 */
	protected function _getShell($shell) {
		if (isset($this->TestShell)) {
			return $this->TestShell;
		}
		return parent::_getShell($shell);
	}

}

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
	}

/**
 * Verify loading of (plugin-) shells
 *
 * @return void
 */
	public function testGetShell() {
		$this->skipIf(class_exists('SampleShell'), 'SampleShell Class already loaded.');
		$this->skipIf(class_exists('ExampleShell'), 'ExampleShell Class already loaded.');

		Configure::write('App.namespace', 'TestApp');
		$Dispatcher = new TestShellDispatcher();

		$result = $Dispatcher->getShell('sample');
		$this->assertInstanceOf('TestApp\Console\Command\SampleShell', $result);

		$Dispatcher = new TestShellDispatcher();
		$result = $Dispatcher->getShell('test_plugin.example');
		$this->assertInstanceOf('TestPlugin\Console\Command\ExampleShell', $result);
		$this->assertEquals('TestPlugin', $result->plugin);
		$this->assertEquals('Example', $result->name);

		$Dispatcher = new TestShellDispatcher();
		$result = $Dispatcher->getShell('TestPlugin.example');
		$this->assertInstanceOf('TestPlugin\Console\Command\ExampleShell', $result);
	}

/**
 * Verify correct dispatch of Shell subclasses with a main method
 *
 * @return void
 */
	public function testDispatchShellWithMain() {
		$Dispatcher = new TestShellDispatcher();
		$Shell = $this->getMock('Cake\Console\Shell');

		$Shell->expects($this->once())->method('initialize');
		$Shell->expects($this->once())->method('runCommand')
			->with(null, array())
			->will($this->returnValue(true));

		$Dispatcher->TestShell = $Shell;

		$Dispatcher->args = array('mock_with_main');
		$result = $Dispatcher->dispatch();
		$this->assertEquals(0, $result);
		$this->assertEquals(array(), $Dispatcher->args);
	}

/**
 * Verify correct dispatch of Shell subclasses without a main method
 *
 * @return void
 */
	public function testDispatchShellWithoutMain() {
		$Dispatcher = new TestShellDispatcher();
		$Shell = $this->getMock('Cake\Console\Shell');

		$Shell->expects($this->once())->method('initialize');
		$Shell->expects($this->once())->method('runCommand')
			->with('initdb', array('initdb'))
			->will($this->returnValue(true));

		$Dispatcher->TestShell = $Shell;

		$Dispatcher->args = array('mock_without_main', 'initdb');
		$result = $Dispatcher->dispatch();
		$this->assertEquals(0, $result);
	}

/**
 * Verify correct dispatch of custom classes with a main method
 *
 * @return void
 */
	public function testDispatchNotAShellWithMain() {
		$Dispatcher = new TestShellDispatcher();
		$methods = get_class_methods('Cake\Core\Object');
		array_push($methods, 'main', 'initdb', 'initialize', 'loadTasks', 'startup', '_secret');
		$Shell = $this->getMock('Cake\Core\Object', $methods);

		$Shell->expects($this->never())->method('initialize');
		$Shell->expects($this->once())->method('startup');
		$Shell->expects($this->once())->method('main')->will($this->returnValue(true));
		$Dispatcher->TestShell = $Shell;

		$Dispatcher->args = array('mock_with_main_not_a');
		$result = $Dispatcher->dispatch();
		$this->assertEquals(0, $result);
		$this->assertEquals(array(), $Dispatcher->args);

		$Shell = $this->getMock('Cake\Core\Object', $methods);
		$Shell->expects($this->once())->method('initdb')->will($this->returnValue(true));
		$Shell->expects($this->once())->method('startup');
		$Dispatcher->TestShell = $Shell;

		$Dispatcher->args = array('mock_with_main_not_a', 'initdb');
		$result = $Dispatcher->dispatch();
		$this->assertEquals(0, $result);
	}

/**
 * Verify correct dispatch of custom classes without a main method
 *
 * @return void
 */
	public function testDispatchNotAShellWithoutMain() {
		$Dispatcher = new TestShellDispatcher();
		$methods = get_class_methods('Cake\Core\Object');
		array_push($methods, 'main', 'initdb', 'initialize', 'loadTasks', 'startup', '_secret');
		$Shell = $this->getMock('Cake\Core\Object', $methods);

		$Shell->expects($this->never())->method('initialize');
		$Shell->expects($this->once())->method('startup');
		$Shell->expects($this->once())->method('main')->will($this->returnValue(true));
		$Dispatcher->TestShell = $Shell;

		$Dispatcher->args = array('mock_without_main_not_a');
		$result = $Dispatcher->dispatch();
		$this->assertEquals(0, $result);
		$this->assertEquals(array(), $Dispatcher->args);

		$Shell = $this->getMock('Cake\Core\Object', $methods);
		$Shell->expects($this->once())->method('initdb')->will($this->returnValue(true));
		$Shell->expects($this->once())->method('startup');
		$Dispatcher->TestShell = $Shell;

		$Dispatcher->args = array('mock_without_main_not_a', 'initdb');
		$result = $Dispatcher->dispatch();
		$this->assertEquals(0, $result);
	}

/**
 * Verify shifting of arguments
 *
 * @return void
 */
	public function testShiftArgs() {
		$Dispatcher = new TestShellDispatcher();

		$Dispatcher->args = array('a', 'b', 'c');
		$this->assertEquals('a', $Dispatcher->shiftArgs());
		$this->assertSame($Dispatcher->args, array('b', 'c'));

		$Dispatcher->args = array('a' => 'b', 'c', 'd');
		$this->assertEquals('b', $Dispatcher->shiftArgs());
		$this->assertSame($Dispatcher->args, array('c', 'd'));

		$Dispatcher->args = array('a', 'b' => 'c', 'd');
		$this->assertEquals('a', $Dispatcher->shiftArgs());
		$this->assertSame($Dispatcher->args, array('b' => 'c', 'd'));

		$Dispatcher->args = array(0 => 'a', 2 => 'b', 30 => 'c');
		$this->assertEquals('a', $Dispatcher->shiftArgs());
		$this->assertSame($Dispatcher->args, array(0 => 'b', 1 => 'c'));

		$Dispatcher->args = array();
		$this->assertNull($Dispatcher->shiftArgs());
		$this->assertSame(array(), $Dispatcher->args);
	}

}
