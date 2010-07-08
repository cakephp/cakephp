<?php
/**
 * ShellDispatcherTest file
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) Tests <http://book.cakephp.org/view/1196/Testing>
 * Copyright 2005-2010, Cake Software Foundation, Inc.
 *
 *  Licensed under The Open Group Test Suite License
 *  Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2010, Cake Software Foundation, Inc.
 * @link          http://book.cakephp.org/view/1196/Testing CakePHP(tm) Tests
 * @package       cake
 * @subpackage    cake.tests.cases.console
 * @since         CakePHP(tm) v 1.2.0.5432
 * @license       http://www.opensource.org/licenses/opengroup.php The Open Group Test Suite License
 */
if (!defined('DISABLE_AUTO_DISPATCH')) {
	define('DISABLE_AUTO_DISPATCH', true);
}

if (!class_exists('ShellDispatcher')) {
	ob_start();
	$argv = false;
	require CAKE . 'console' .  DS . 'cake.php';
	ob_end_clean();
}

require_once CONSOLE_LIBS . 'shell.php';

/**
 * TestShellDispatcher class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.console
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
 * stdout property
 *
 * @var string
 * @access public
 */
	public $stdout = '';

/**
 * stderr property
 *
 * @var string
 * @access public
 */
	public $stderr = '';

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
 * stderr method
 *
 * @return void
 */
	public function stderr($string) {
		$this->stderr .= rtrim($string, ' ');
	}

/**
 * stdout method
 *
 * @return void
 */
	public function stdout($string, $newline = true) {
		if ($newline) {
			$this->stdout .= rtrim($string, ' ') . "\n";
		} else {
			$this->stdout .= rtrim($string, ' ');
		}
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
 * @param mixed $plugin
 * @return mixed
 */
	public function getShell($plugin = null) {
		return $this->_getShell($plugin);
	}

/**
 * _getShell
 *
 * @param mixed $plugin
 * @return mixed
 */
	protected function _getShell($plugin = null) {
		if (isset($this->TestShell)) {
			return $this->TestShell;
		}
		return parent::_getShell($plugin);
	}
}

/**
 * ShellDispatcherTest
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs
 */
class ShellDispatcherTest extends CakeTestCase {

/**
 * setUp method
 *
 * @return void
 */
	public function setUp() {
		App::build(array(
			'plugins' => array(
				TEST_CAKE_CORE_INCLUDE_PATH . 'tests' . DS . 'test_app' . DS . 'plugins' . DS
			),
			'shells' => array(
				CORE_PATH ? CONSOLE_LIBS : ROOT . DS . CONSOLE_LIBS,
				TEST_CAKE_CORE_INCLUDE_PATH . 'tests' . DS . 'test_app' . DS . 'vendors' . DS . 'shells' . DS
			)
		), true);
	}

/**
 * tearDown method
 *
 * @return void
 */
	public function tearDown() {
		App::build();
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
			'dry' => true,
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
			'dry' => true,
			'f' => true,
			'name' => 'DbAcl'
		);
		$Dispatcher->params = $Dispatcher->args = array();
		$Dispatcher->parseParams($params);
		$this->assertEqual($expected, $Dispatcher->params);

		$expected = array('./console/cake.php', 'schema', 'run', 'create');
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
			'dry' => true,
			'name' => 'DbAcl'
		);
		$Dispatcher->params = $Dispatcher->args = array();
		$Dispatcher->parseParams($params);
		$this->assertEqual($expected, $Dispatcher->params);

		$expected = array('/cake/1.2.x.x/cake/console/cake.php', 'schema', 'run', 'create');
		$this->assertEqual($expected, $Dispatcher->args);
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
			'url' => 'http://example.com/some/url/with/a/path'
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
 * testBuildPaths method
 *
 * @return void
 */
	public function testBuildPaths() {
		$Dispatcher = new TestShellDispatcher();

		$result = $Dispatcher->shellPaths;

		$expected = array(
			TEST_CAKE_CORE_INCLUDE_PATH . 'tests' . DS . 'test_app' . DS . 'plugins' . DS . 'test_plugin' . DS . 'vendors' . DS . 'shells' . DS,
			TEST_CAKE_CORE_INCLUDE_PATH . 'tests' . DS . 'test_app' . DS . 'plugins' . DS . 'test_plugin_two' . DS . 'vendors' . DS . 'shells' . DS,
			APP . 'vendors' . DS . 'shells' . DS,
			VENDORS . 'shells' . DS,
			CORE_PATH ? CONSOLE_LIBS : ROOT . DS . CONSOLE_LIBS,
			TEST_CAKE_CORE_INCLUDE_PATH . 'tests' . DS . 'test_app' . DS . 'vendors' . DS . 'shells' . DS,
		);
		$this->assertIdentical(array_diff($result, $expected), array());
		$this->assertIdentical(array_diff($expected, $result), array());
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

		$Dispatcher->shell = 'sample';
		$Dispatcher->shellName = 'Sample';
		$Dispatcher->shellClass = 'SampleShell';

		$result = $Dispatcher->getShell();
		$this->assertIsA($result, 'SampleShell');

		$Dispatcher = new TestShellDispatcher();

		$Dispatcher->shell = 'example';
		$Dispatcher->shellName = 'Example';
		$Dispatcher->shellClass = 'ExampleShell';

		$result = $Dispatcher->getShell('test_plugin');
		$this->assertIsA($result, 'ExampleShell');
	}

/**
 * Verify correct dispatch of Shell subclasses with a main method
 *
 * @return void
 */
	public function testDispatchShellWithMain() {
		$Dispatcher = new TestShellDispatcher();
		$methods = get_class_methods('Shell');
		array_push($methods, 'main', '_secret');
		$Mock = $this->getMock('Shell', $methods, array(&$Dispatcher), 'MockWithMainShell');

		$Mock->expects($this->once())->method('main')->will($this->returnValue(true));
		$Mock->expects($this->once())->method('initialize');
		$Mock->expects($this->once())->method('loadTasks');
		$Mock->expects($this->once())->method('startup');
		$Dispatcher->TestShell = $Mock;

		$Dispatcher->args = array('mock_with_main');
		$result = $Dispatcher->dispatch();
		$this->assertTrue($result);
		$this->assertEqual($Dispatcher->args, array());

		$Shell = new MockWithMainShell($Dispatcher);
		$this->mockObjects[] = $Shell;
		$Shell->expects($this->once())->method('main')->will($this->returnValue(true));
		$Shell->expects($this->once())->method('startup');
		$Dispatcher->TestShell = $Shell;

		$Dispatcher->args = array('mock_with_main', 'initdb');
		$result = $Dispatcher->dispatch();
		$this->assertTrue($result);
		$this->assertEqual($Dispatcher->args, array('initdb'));

		$Shell = new MockWithMainShell($Dispatcher);
		$this->mockObjects[] = $Shell;
		$Shell->expects($this->once())->method('startup');
		$Shell->expects($this->once())->method('help');
		$Dispatcher->TestShell = $Shell;

		$Dispatcher->args = array('mock_with_main', 'help');
		$result = $Dispatcher->dispatch();
		$this->assertNull($result);
		$this->assertEqual($Dispatcher->args, array());

		$Shell = new MockWithMainShell($Dispatcher);
		$this->mockObjects[] = $Shell;

		$Shell->expects($this->once())->method('main')->will($this->returnValue(true));
		$Shell->expects($this->never())->method('hr');
		$Shell->expects($this->once())->method('startup');
		$Shell->expects($this->once())->method('main');
		$Dispatcher->TestShell = $Shell;

		$Dispatcher->args = array('mock_with_main', 'hr');
		$result = $Dispatcher->dispatch();
		$this->assertTrue($result);
		$this->assertEqual($Dispatcher->args, array('hr'));

		$Shell = new MockWithMainShell($Dispatcher);
		$this->mockObjects[] = $Shell;
		$Shell->expects($this->once())->method('main')->will($this->returnValue(true));
		$Shell->expects($this->once())->method('startup');
		$Dispatcher->TestShell = $Shell;

		$Dispatcher->args = array('mock_with_main', 'dispatch');
		$result = $Dispatcher->dispatch();
		$this->assertTrue($result);
		$this->assertEqual($Dispatcher->args, array('dispatch'));

		$Shell = new MockWithMainShell($Dispatcher);
		$this->mockObjects[] = $Shell;
		$Shell->expects($this->once())->method('main')->will($this->returnValue(true));
		$Shell->expects($this->once())->method('startup');
		$Dispatcher->TestShell = $Shell;

		$Dispatcher->args = array('mock_with_main', 'idontexist');
		$result = $Dispatcher->dispatch();
		$this->assertTrue($result);
		$this->assertEqual($Dispatcher->args, array('idontexist'));

		$Shell = new MockWithMainShell($Dispatcher);
		$this->mockObjects[] = $Shell;
		$Shell->expects($this->never())->method('main');
		$Shell->expects($this->never())->method('startup');
		$Shell->expects($this->never())->method('_secret');
		$Dispatcher->TestShell = $Shell;

		$Dispatcher->args = array('mock_with_main', '_secret');
		$result = $Dispatcher->dispatch();
		$this->assertFalse($result);
	}

/**
 * Verify correct dispatch of Shell subclasses without a main method
 *
 * @return void
 */
	public function testDispatchShellWithoutMain() {
		$Dispatcher = new TestShellDispatcher();
		$methods = get_class_methods('Shell');
		array_push($methods, 'initDb', '_secret');
		$Shell = $this->getMock('Shell', $methods, array(&$Dispatcher), 'MockWithoutMainShell');

		$Shell->expects($this->once())->method('initialize');
		$Shell->expects($this->once())->method('loadTasks');
		$Shell->expects($this->never())->method('startup');
		$Dispatcher->TestShell = $Shell;

		$Dispatcher->args = array('mock_without_main');
		$result = $Dispatcher->dispatch();
		$this->assertFalse($result);
		$this->assertEqual($Dispatcher->args, array());

		$Shell = new MockWithoutMainShell($Dispatcher);
		$this->mockObjects[] = $Shell;
		$Shell->expects($this->once())->method('initDb')->will($this->returnValue(true));
		$Shell->expects($this->once())->method('initialize');
		$Shell->expects($this->once())->method('loadTasks');
		$Shell->expects($this->once())->method('startup');
		$Dispatcher->TestShell = $Shell;

		$Dispatcher->args = array('mock_without_main', 'initdb');
		$result = $Dispatcher->dispatch();
		$this->assertTrue($result);
		$this->assertEqual($Dispatcher->args, array());

		$Shell = new MockWithoutMainShell($Dispatcher);
		$this->mockObjects[] = $Shell;
		$Shell->expects($this->never())->method('hr');
		$Shell->expects($this->never())->method('startup');
		$Dispatcher->TestShell = $Shell;

		$Dispatcher->args = array('mock_without_main', 'hr');
		$result = $Dispatcher->dispatch();
		$this->assertFalse($result);
		$this->assertEqual($Dispatcher->args, array('hr'));

		$Shell = new MockWithoutMainShell($Dispatcher);
		$this->mockObjects[] = $Shell;
		$Shell->expects($this->never())->method('startup');
		$Dispatcher->TestShell = $Shell;

		$Dispatcher->args = array('mock_without_main', 'dispatch');
		$result = $Dispatcher->dispatch();
		$this->assertFalse($result);

		$Shell = new MockWithoutMainShell($Dispatcher);
		$this->mockObjects[] = $Shell;
		$Shell->expects($this->never())->method('startup');
		$Dispatcher->TestShell = $Shell;

		$Dispatcher->args = array('mock_without_main', 'idontexist');
		$result = $Dispatcher->dispatch();
		$this->assertFalse($result);

		$Shell = new MockWithoutMainShell($Dispatcher);
		$this->mockObjects[] = $Shell;
		$Shell->expects($this->never())->method('startup');
		$Shell->expects($this->never())->method('_secret');
		$Dispatcher->TestShell = $Shell;

		$Dispatcher->args = array('mock_without_main', '_secret');
		$result = $Dispatcher->dispatch();
		$this->assertFalse($result);
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
		$Shell = $this->getMock('Object', $methods, array(&$Dispatcher), 'MockWithMainNotAShell');

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

		$Shell = new MockWithMainNotAShell($Dispatcher);
		$this->mockObjects[] = $Shell;
		$Shell->expects($this->once())->method('main')->will($this->returnValue(true));
		$Shell->expects($this->once())->method('startup');
		$Dispatcher->TestShell = $Shell;

		$Dispatcher->args = array('mock_with_main_not_a', 'hr');
		$result = $Dispatcher->dispatch();
		$this->assertTrue($result);
		$this->assertEqual($Dispatcher->args, array('hr'));


		$Shell = new MockWithMainNotAShell($Dispatcher);
		$this->mockObjects[] = $Shell;
		$Shell->expects($this->once())->method('main')->will($this->returnValue(true));
		$Shell->expects($this->once())->method('startup');
		$Dispatcher->TestShell = $Shell;

		$Dispatcher->args = array('mock_with_main_not_a', 'dispatch');
		$result = $Dispatcher->dispatch();
		$this->assertTrue($result);
		$this->assertEqual($Dispatcher->args, array('dispatch'));

		$Shell = new MockWithMainNotAShell($Dispatcher);
		$this->mockObjects[] = $Shell;
		$Shell->expects($this->once())->method('main')->will($this->returnValue(true));
		$Shell->expects($this->once())->method('startup');
		$Dispatcher->TestShell = $Shell;

		$Dispatcher->args = array('mock_with_main_not_a', 'idontexist');
		$result = $Dispatcher->dispatch();
		$this->assertTrue($result);
		$this->assertEqual($Dispatcher->args, array('idontexist'));

		$Shell = new MockWithMainNotAShell($Dispatcher);
		$this->mockObjects[] = $Shell;
		$Shell->expects($this->never())->method('_secret');
		$Shell->expects($this->never())->method('main');
		$Shell->expects($this->never())->method('startup');
		$Dispatcher->TestShell = $Shell;

		$Dispatcher->args = array('mock_with_main_not_a', '_secret');
		$result = $Dispatcher->dispatch();
		$this->assertFalse($result);
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

		$Shell = new MockWithoutMainNotAShell($Dispatcher);
		$this->mockObjects[] = $Shell;
		$Shell->expects($this->once())->method('main')->will($this->returnValue(true));
		$Shell->expects($this->once())->method('startup');
		$Dispatcher->TestShell = $Shell;

		$Dispatcher->args = array('mock_without_main_not_a', 'hr');
		$result = $Dispatcher->dispatch();
		$this->assertTrue($result);
		$this->assertEqual($Dispatcher->args, array('hr'));


		$Shell = new MockWithoutMainNotAShell($Dispatcher);
		$this->mockObjects[] = $Shell;
		$Shell->expects($this->once())->method('main')->will($this->returnValue(true));
		$Shell->expects($this->once())->method('startup');
		$Dispatcher->TestShell = $Shell;

		$Dispatcher->args = array('mock_without_main_not_a', 'dispatch');
		$result = $Dispatcher->dispatch();
		$this->assertTrue($result);
		$this->assertEqual($Dispatcher->args, array('dispatch'));

		$Shell = new MockWithoutMainNotAShell($Dispatcher);
		$this->mockObjects[] = $Shell;
		$Shell->expects($this->once())->method('main')->will($this->returnValue(true));
		$Shell->expects($this->once())->method('startup');
		$Dispatcher->TestShell = $Shell;

		$Dispatcher->args = array('mock_without_main_not_a', 'idontexist');
		$result = $Dispatcher->dispatch();
		$this->assertTrue($result);
		$this->assertEqual($Dispatcher->args, array('idontexist'));

		$Shell = new MockWithoutMainNotAShell($Dispatcher);
		$this->mockObjects[] = $Shell;
		$Shell->expects($this->never())->method('_secret');
		$Shell->expects($this->never())->method('main');
		$Shell->expects($this->never())->method('startup');
		$Dispatcher->TestShell = $Shell;

		$Dispatcher->args = array('mock_without_main_not_a', '_secret');
		$result = $Dispatcher->dispatch();
		$this->assertFalse($result);
	}

/**
 * Verify that a task is called instead of the shell if the first arg equals
 * the name of the task
 *
 * @return void
 */
	public function testDispatchTask() {
		$Dispatcher = new TestShellDispatcher();
		$mainMethods = $executeMethods = get_class_methods('Shell');
		array_push($mainMethods, 'main');
		array_push($executeMethods, 'execute');

		$Week = $this->getMock('Shell', $mainMethods, array(&$Dispatcher), 'MockWeekShell');
		$Sunday = $this->getMock('Shell', $executeMethods, array(&$Dispatcher), 'MockOnSundayTask');

		$Shell = new MockWeekShell($Dispatcher);
		$this->mockObjects[] = $Shell;
		$Shell->expects($this->once())->method('initialize');
		$Shell->expects($this->once())->method('loadTasks');
		$Shell->expects($this->never())->method('startup');
		$Shell->expects($this->never())->method('main');

		$Task = new MockOnSundayTask($Dispatcher);
		$this->mockObjects[] = $Task;
		$Task->expects($this->once())->method('execute')->will($this->returnValue(true));
		$Task->expects($this->once())->method('initialize');;
		$Task->expects($this->once())->method('loadTasks');
		$Task->expects($this->once())->method('startup');
		$Task->expects($this->once())->method('execute');

		$Shell->MockOnSunday = $Task;
		$Shell->taskNames = array('MockOnSunday');
		$Dispatcher->TestShell = $Shell;

		$Dispatcher->args = array('mock_week', 'mock_on_sunday');
		$result = $Dispatcher->dispatch();
		$this->assertTrue($result);
		$this->assertEqual($Dispatcher->args, array());

		$Shell = new MockWeekShell($Dispatcher);
		$Task = new MockOnSundayTask($Dispatcher);
		array_push($this->mockObjects, $Shell, $Task);

		$Task->expects($this->never())->method('execute');
		$Task->expects($this->once())->method('help');

		$Shell->MockOnSunday = $Task;
		$Shell->taskNames = array('MockOnSunday');
		$Dispatcher->TestShell = $Shell;

		$Dispatcher->args = array('mock_week', 'mock_on_sunday', 'help');
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

/**
 * testHelpCommand method
 *
 * @return void
 */
	public function testHelpCommand() {
		$Dispatcher = new TestShellDispatcher();

		$expected = "/example \[.*TestPlugin, TestPluginTwo.*\]/";
	 	$this->assertPattern($expected, $Dispatcher->stdout);

		$expected = "/welcome \[.*TestPluginTwo.*\]/";
	 	$this->assertPattern($expected, $Dispatcher->stdout);

		$expected = "/acl \[.*CORE.*\]/";
	 	$this->assertPattern($expected, $Dispatcher->stdout);

		$expected = "/api \[.*CORE.*\]/";
	 	$this->assertPattern($expected, $Dispatcher->stdout);

		$expected = "/bake \[.*CORE.*\]/";
	 	$this->assertPattern($expected, $Dispatcher->stdout);

		$expected = "/console \[.*CORE.*\]/";
	 	$this->assertPattern($expected, $Dispatcher->stdout);

		$expected = "/i18n \[.*CORE.*\]/";
	 	$this->assertPattern($expected, $Dispatcher->stdout);

		$expected = "/schema \[.*CORE.*\]/";
	 	$this->assertPattern($expected, $Dispatcher->stdout);

		$expected = "/testsuite \[.*CORE.*\]/";
	 	$this->assertPattern($expected, $Dispatcher->stdout);

		$expected = "/sample \[.*test_app.*\]/";
		$this->assertPattern($expected, $Dispatcher->stdout);
	}
}
