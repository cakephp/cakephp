<?php
/**
 * ShellDispatcherTest file
 *
 * PHP 5
 *
 * CakePHP(tm) Tests <http://book.cakephp.org/view/1196/Testing>
 * Copyright 2005-2010, Cake Software Foundation, Inc.
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright 2005-2010, Cake Software Foundation, Inc.
 * @link          http://book.cakephp.org/view/1196/Testing CakePHP(tm) Tests
 * @package       cake.tests.cases.console
 * @since         CakePHP(tm) v 1.2.0.5432
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
require_once CAKE . 'console' .  DS . 'shell_dispatcher.php';

/**
 * TestShellDispatcher class
 *
 * @package       cake.tests.cases.console
 */
class TestShellDispatcher extends ShellDispatcher {

/**
 * params property
 *
 * @var array
 * @access public
 */
	public $params = array();

/**
 * stopped property
 *
 * @var string
 * @access public
 */
	public $stopped = null;

/**
 * TestShell
 *
 * @var mixed
 * @access public
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
 * @param mixed $shell
 * @return mixed
 */
	public function getShell($shell) {
		return $this->_getShell($shell);
	}

/**
 * _getShell
 *
 * @param mixed $plugin
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
 * @package       cake.tests.cases.libs
 */
class ShellDispatcherTest extends CakeTestCase {

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
				TEST_CAKE_CORE_INCLUDE_PATH . 'tests' . DS . 'test_app' . DS . 'console' . DS . 'shells' . DS
			)
		), true);
	}

/**
 * testParseParams method
 *
 * @return void
 */
	public function testParseParams() {
		$Dispatcher = new TestShellDispatcher();

		$params = array(
			'/cake/1.2.x.x/cake/console/cake.php',
			'bake',
			'-app',
			'new',
			'-working',
			'/var/www/htdocs'
		);
		$expected = array(
			'app' => 'new',
			'webroot' => 'webroot',
			'working' => '/var/www/htdocs/new',
			'root' => '/var/www/htdocs'
		);
		$Dispatcher->parseParams($params);
		$this->assertEqual($expected, $Dispatcher->params);

		$params = array('cake.php');
		$expected = array(
			'app' => 'app',
			'webroot' => 'webroot',
			'working' => str_replace('\\', '/', CAKE_CORE_INCLUDE_PATH . DS . 'app'),
			'root' => str_replace('\\', '/', CAKE_CORE_INCLUDE_PATH),
		);
		$Dispatcher->params = $Dispatcher->args = array();
		$Dispatcher->parseParams($params);
		$this->assertEqual($expected, $Dispatcher->params);

		$params = array(
			'cake.php',
			'-app',
			'new',
		);
		$expected = array(
			'app' => 'new',
			'webroot' => 'webroot',
			'working' => str_replace('\\', '/', CAKE_CORE_INCLUDE_PATH . DS . 'new'),
			'root' => str_replace('\\', '/', CAKE_CORE_INCLUDE_PATH)
		);
		$Dispatcher->params = $Dispatcher->args = array();
		$Dispatcher->parseParams($params);
		$this->assertEqual($expected, $Dispatcher->params);

		$params = array(
			'./cake.php',
			'bake',
			'-app',
			'new',
			'-working',
			'/cake/1.2.x.x/cake/console'
		);

		$expected = array(
			'app' => 'new',
			'webroot' => 'webroot',
			'working' => str_replace('\\', '/', CAKE_CORE_INCLUDE_PATH . DS . 'new'),
			'root' => str_replace('\\', '/', CAKE_CORE_INCLUDE_PATH)
		);

		$Dispatcher->params = $Dispatcher->args = array();
		$Dispatcher->parseParams($params);
		$this->assertEqual($expected, $Dispatcher->params);

		$params = array(
			'./console/cake.php',
			'bake',
			'-app',
			'new',
			'-working',
			'/cake/1.2.x.x/cake'
		);
		$expected = array(
			'app' => 'new',
			'webroot' => 'webroot',
			'working' => str_replace('\\', '/', CAKE_CORE_INCLUDE_PATH . DS . 'new'),
			'root' => str_replace('\\', '/', CAKE_CORE_INCLUDE_PATH)
		);
		$Dispatcher->params = $Dispatcher->args = array();
		$Dispatcher->parseParams($params);
		$this->assertEqual($expected, $Dispatcher->params);

		$params = array(
			'./console/cake.php',
			'bake',
			'-app',
			'new',
			'-dry',
			'-working',
			'/cake/1.2.x.x/cake'
		);
		$expected = array(
			'app' => 'new',
			'working' => str_replace('\\', '/', CAKE_CORE_INCLUDE_PATH . DS . 'new'),
			'root' => str_replace('\\', '/', CAKE_CORE_INCLUDE_PATH),
			'webroot' => 'webroot'
		);
		$Dispatcher->params = $Dispatcher->args = array();
		$Dispatcher->parseParams($params);
		$this->assertEquals($expected, $Dispatcher->params);

		$params = array(
			'./console/cake.php',
			'-working',
			'/cake/1.2.x.x/cake',
			'schema',
			'run',
			'create',
			'-dry',
			'-f',
			'-name',
			'DbAcl'
		);
		$expected = array(
			'app' => 'app',
			'webroot' => 'webroot',
			'working' => str_replace('\\', '/', CAKE_CORE_INCLUDE_PATH . DS . 'app'),
			'root' => str_replace('\\', '/', CAKE_CORE_INCLUDE_PATH),
		);
		$Dispatcher->params = $Dispatcher->args = array();
		$Dispatcher->parseParams($params);
		$this->assertEqual($expected, $Dispatcher->params);

		$expected = array(
			'./console/cake.php', 'schema', 'run', 'create', '-dry', '-f', '-name', 'DbAcl'
		);
		$this->assertEqual($expected, $Dispatcher->args);

		$params = array(
			'/cake/1.2.x.x/cake/console/cake.php',
			'-working',
			'/cake/1.2.x.x/app',
			'schema',
			'run',
			'create',
			'-dry',
			'-name',
			'DbAcl'
		);
		$expected = array(
			'app' => 'app',
			'webroot' => 'webroot',
			'working' => '/cake/1.2.x.x/app',
			'root' => '/cake/1.2.x.x',
		);
		$Dispatcher->params = $Dispatcher->args = array();
		$Dispatcher->parseParams($params);
		$this->assertEqual($expected, $Dispatcher->params);

		$params = array(
			'cake.php',
			'-working',
			'C:/wamp/www/cake/app',
			'bake',
			'-app',
			'C:/wamp/www/apps/cake/app',
		);
		$expected = array(
			'app' => 'app',
			'webroot' => 'webroot',
			'working' => 'C:\wamp\www\apps\cake\app',
			'root' => 'C:\wamp\www\apps\cake'
		);

		$Dispatcher->params = $Dispatcher->args = array();
		$Dispatcher->parseParams($params);
		$this->assertEqual($expected, $Dispatcher->params);

		$params = array(
			'cake.php',
			'-working',
			'C:\wamp\www\cake\app',
			'bake',
			'-app',
			'C:\wamp\www\apps\cake\app',
		);
		$expected = array(
			'app' => 'app',
			'webroot' => 'webroot',
			'working' => 'C:\wamp\www\apps\cake\app',
			'root' => 'C:\wamp\www\apps\cake'
		);
		$Dispatcher->params = $Dispatcher->args = array();
		$Dispatcher->parseParams($params);
		$this->assertEqual($expected, $Dispatcher->params);

		$params = array(
			'cake.php',
			'-working',
			'C:\wamp\www\apps',
			'bake',
			'-app',
			'cake\app',
			'-url',
			'http://example.com/some/url/with/a/path'
		);
		$expected = array(
			'app' => 'app',
			'webroot' => 'webroot',
			'working' => 'C:\wamp\www\apps\cake\app',
			'root' => 'C:\wamp\www\apps\cake',
		);
		$Dispatcher->params = $Dispatcher->args = array();
		$Dispatcher->parseParams($params);
		$this->assertEqual($expected, $Dispatcher->params);

		$params = array(
			'/home/amelo/dev/cake-common/cake/console/cake.php',
			'-root',
			'/home/amelo/dev/lsbu-vacancy',
			'-working',
			'/home/amelo/dev/lsbu-vacancy',
			'-app',
			'app',
		);
		$expected = array(
			'app' => 'app',
			'webroot' => 'webroot',
			'working' => '/home/amelo/dev/lsbu-vacancy/app',
			'root' => '/home/amelo/dev/lsbu-vacancy',
		);
		$Dispatcher->params = $Dispatcher->args = array();
		$Dispatcher->parseParams($params);
		$this->assertEqual($expected, $Dispatcher->params);

		$params = array(
			'cake.php',
			'-working',
			'D:\www',
			'bake',
			'my_app',
		);
		$expected = array(
			'working' => 'D:\www',
			'app' => 'www',
			'root' => 'D:',
			'webroot' => 'webroot'
		);

		$Dispatcher->params = $Dispatcher->args = array();
		$Dispatcher->parseParams($params);
		$this->assertEqual($expected, $Dispatcher->params);
	}

/**
 * Verify loading of (plugin-) shells
 *
 * @return void
 */
	public function testGetShell() {
		$this->skipIf(class_exists('SampleShell'), '%s SampleShell Class already loaded');
		$this->skipIf(class_exists('ExampleShell'), '%s ExampleShell Class already loaded');

		$Dispatcher = new TestShellDispatcher();

		$result = $Dispatcher->getShell('sample');
		$this->assertInstanceOf('SampleShell', $result);

		$Dispatcher = new TestShellDispatcher();
		$result = $Dispatcher->getShell('test_plugin.example');
		$this->assertInstanceOf('ExampleShell', $result);
	}

/**
 * Verify correct dispatch of Shell subclasses with a main method
 *
 * @return void
 */
	public function testDispatchShellWithMain() {
		$Dispatcher = new TestShellDispatcher();
		$Mock = $this->getMock('Shell', array(), array(&$Dispatcher), 'MockWithMainShell');

		$Mock->expects($this->once())->method('initialize');
		$Mock->expects($this->once())->method('loadTasks');
		$Mock->expects($this->once())->method('runCommand')
			->with(null, array())
			->will($this->returnValue(true));

		$Dispatcher->TestShell = $Mock;

		$Dispatcher->args = array('mock_with_main');
		$result = $Dispatcher->dispatch();
		$this->assertTrue($result);
		$this->assertEqual($Dispatcher->args, array());
	}

/**
 * Verify correct dispatch of Shell subclasses without a main method
 *
 * @return void
 */
	public function testDispatchShellWithoutMain() {
		$Dispatcher = new TestShellDispatcher();
		$Shell = $this->getMock('Shell', array(), array(&$Dispatcher), 'MockWithoutMainShell');

		$Shell = new MockWithoutMainShell($Dispatcher);
		$this->mockObjects[] = $Shell;

		$Shell->expects($this->once())->method('initialize');
		$Shell->expects($this->once())->method('loadTasks');
		$Shell->expects($this->once())->method('runCommand')
			->with('initdb', array('initdb'))
			->will($this->returnValue(true));

		$Dispatcher->TestShell = $Shell;

		$Dispatcher->args = array('mock_without_main', 'initdb');
		$result = $Dispatcher->dispatch();
		$this->assertTrue($result);
	}

/**
 * Verify correct dispatch of custom classes with a main method
 *
 * @return void
 */
	public function testDispatchNotAShellWithMain() {
		$Dispatcher = new TestShellDispatcher();
		$methods = get_class_methods('Object');
		array_push($methods, 'main', 'initdb', 'initialize', 'loadTasks', 'startup', '_secret');
		$Shell = $this->getMock('Object', $methods, array(), 'MockWithMainNotAShell');

		$Shell->expects($this->never())->method('initialize');
		$Shell->expects($this->never())->method('loadTasks');
		$Shell->expects($this->once())->method('startup');
		$Shell->expects($this->once())->method('main')->will($this->returnValue(true));
		$Dispatcher->TestShell = $Shell;

		$Dispatcher->args = array('mock_with_main_not_a');
		$result = $Dispatcher->dispatch();
		$this->assertTrue($result);
		$this->assertEqual($Dispatcher->args, array());

		$Shell = new MockWithMainNotAShell($Dispatcher);
		$this->mockObjects[] = $Shell;
		$Shell->expects($this->once())->method('initdb')->will($this->returnValue(true));
		$Shell->expects($this->once())->method('startup');
		$Dispatcher->TestShell = $Shell;

		$Dispatcher->args = array('mock_with_main_not_a', 'initdb');
		$result = $Dispatcher->dispatch();
		$this->assertTrue($result);
	}

/**
 * Verify correct dispatch of custom classes without a main method
 *
 * @return void
 */
	public function testDispatchNotAShellWithoutMain() {
		$Dispatcher = new TestShellDispatcher();
		$methods = get_class_methods('Object');
		array_push($methods, 'main', 'initdb', 'initialize', 'loadTasks', 'startup', '_secret');
		$Shell = $this->getMock('Object', $methods, array(&$Dispatcher), 'MockWithoutMainNotAShell');

		$Shell->expects($this->never())->method('initialize');
		$Shell->expects($this->never())->method('loadTasks');
		$Shell->expects($this->once())->method('startup');
		$Shell->expects($this->once())->method('main')->will($this->returnValue(true));
		$Dispatcher->TestShell = $Shell;

		$Dispatcher->args = array('mock_without_main_not_a');
		$result = $Dispatcher->dispatch();
		$this->assertTrue($result);
		$this->assertEqual($Dispatcher->args, array());

		$Shell = new MockWithoutMainNotAShell($Dispatcher);
		$this->mockObjects[] = $Shell;
		$Shell->expects($this->once())->method('initdb')->will($this->returnValue(true));
		$Shell->expects($this->once())->method('startup');
		$Dispatcher->TestShell = $Shell;

		$Dispatcher->args = array('mock_without_main_not_a', 'initdb');
		$result = $Dispatcher->dispatch();
		$this->assertTrue($result);
	}

/**
 * Verify shifting of arguments
 *
 * @return void
 */
	public function testShiftArgs() {
		$Dispatcher = new TestShellDispatcher();

		$Dispatcher->args = array('a', 'b', 'c');
		$this->assertEqual($Dispatcher->shiftArgs(), 'a');
		$this->assertIdentical($Dispatcher->args, array('b', 'c'));

		$Dispatcher->args = array('a' => 'b', 'c', 'd');
		$this->assertEqual($Dispatcher->shiftArgs(), 'b');
		$this->assertIdentical($Dispatcher->args, array('c', 'd'));

		$Dispatcher->args = array('a', 'b' => 'c', 'd');
		$this->assertEqual($Dispatcher->shiftArgs(), 'a');
		$this->assertIdentical($Dispatcher->args, array('b' => 'c', 'd'));

		$Dispatcher->args = array(0 => 'a',  2 => 'b', 30 => 'c');
		$this->assertEqual($Dispatcher->shiftArgs(), 'a');
		$this->assertIdentical($Dispatcher->args, array(0 => 'b', 1 => 'c'));

		$Dispatcher->args = array();
		$this->assertNull($Dispatcher->shiftArgs());
		$this->assertIdentical($Dispatcher->args, array());
	}

}
