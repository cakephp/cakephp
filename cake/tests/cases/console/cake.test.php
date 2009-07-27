<?php
/* SVN FILE: $Id$ */
/**
 * ShellDispatcherTest file
 *
 * Long description for file
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) Tests <https://trac.cakephp.org/wiki/Developement/TestSuite>
 * Copyright 2005-2007, Cake Software Foundation, Inc.
 *
 *  Licensed under The Open Group Test Suite License
 *  Redistributions of files must retain the above copyright notice.
 *
 * @filesource
 * @copyright     Copyright 2005-2007, Cake Software Foundation, Inc.
 * @link          https://trac.cakephp.org/wiki/Developement/TestSuite CakePHP(tm) Tests
 * @package       cake
 * @subpackage    cake.tests.cases.console
 * @since         CakePHP(tm) v 1.2.0.5432
 * @version       $Revision$
 * @modifiedby    $LastChangedBy$
 * @lastmodified  $Date$
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
	var $params = array();
/**
 * stdout property
 *
 * @var string
 * @access public
 */
	var $stdout = '';
/**
 * stderr property
 *
 * @var string
 * @access public
 */
	var $stderr = '';
/**
 * stopped property
 *
 * @var string
 * @access public
 */
	var $stopped = null;
/**
 * _initEnvironment method
 *
 * @access protected
 * @return void
 */
	function _initEnvironment() {
	}
/**
 * stderr method
 *
 * @access public
 * @return void
 */
	function stderr($string) {
		$this->stderr .= rtrim($string, ' ');
	}
/**
 * stdout method
 *
 * @access public
 * @return void
 */
	function stdout($string, $newline = true) {
		if ($newline) {
			$this->stdout .= rtrim($string, ' ') . "\n";
		} else {
			$this->stdout .= rtrim($string, ' ');
		}
	}
/**
 * _stop method
 *
 * @access protected
 * @return void
 */
	function _stop($status = 0) {
		$this->stopped = 'Stopped with status: ' . $status;
	}
}
/**
 * ShellDispatcherTest
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs
 */
class ShellDispatcherTest extends UnitTestCase {
/**
 * setUp method
 *
 * @access public
 * @return void
 */
	function setUp() {
		$this->_pluginPaths = Configure::read('pluginPaths');
		$this->_shellPaths = Configure::read('shellPaths');

		Configure::write('pluginPaths', array(
			TEST_CAKE_CORE_INCLUDE_PATH . 'tests' . DS . 'test_app' . DS . 'plugins' . DS
		));
		Configure::write('shellPaths', array(
			CORE_PATH ? CONSOLE_LIBS : ROOT . DS . CONSOLE_LIBS,
			TEST_CAKE_CORE_INCLUDE_PATH . 'tests' . DS . 'test_app' . DS . 'vendors' . DS . 'shells' . DS
		));
	}
/**
 * tearDown method
 *
 * @access public
 * @return void
 */
	function tearDown() {
		Configure::write('pluginPaths', $this->_pluginPaths);
		Configure::write('shellPaths', $this->_shellPaths);
	}
/**
 * testParseParams method
 *
 * @access public
 * @return void
 */
	function testParseParams() {
		$Dispatcher =& new TestShellDispatcher();

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
			'webroot' => 'webroot',
			'working' => str_replace('\\', '/', CAKE_CORE_INCLUDE_PATH . DS . 'new'),
			'root' => str_replace('\\', '/', CAKE_CORE_INCLUDE_PATH),
			'dry' => 1
		);
		$Dispatcher->params = $Dispatcher->args = array();
		$Dispatcher->parseParams($params);
		$this->assertEqual($expected, $Dispatcher->params);


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
			'dry' => 1,
			'f' => 1,
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
			'dry' => 1,
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
 * @access public
 * @return void
 */
	function testBuildPaths() {
		$Dispatcher =& new TestShellDispatcher();

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
 * testDispatch method
 *
 * @access public
 * @return void
 */
	function testDispatch() {
		$Dispatcher =& new TestShellDispatcher(array('sample'));
		$this->assertPattern('/This is the main method called from SampleShell/', $Dispatcher->stdout);

		$Dispatcher =& new TestShellDispatcher(array('test_plugin_two.example'));
		$this->assertPattern('/This is the main method called from TestPluginTwo.ExampleShell/', $Dispatcher->stdout);

		$Dispatcher =& new TestShellDispatcher(array('test_plugin_two.welcome', 'say_hello'));
		$this->assertPattern('/This is the say_hello method called from TestPluginTwo.WelcomeShell/', $Dispatcher->stdout);
	}
/**
 * testHelpCommand method
 *
 * @access public
 * @return void
 */
	function testHelpCommand() {
		$Dispatcher =& new TestShellDispatcher();

		$expected = "/ CORE(\\\|\/)tests(\\\|\/)test_app(\\\|\/)plugins(\\\|\/)test_plugin(\\\|\/)vendors(\\\|\/)shells:";
	 	$expected .= "\n\t example";
	 	$expected .= "\n/";
	 	$this->assertPattern($expected, $Dispatcher->stdout);

	 	$expected = "/ CORE(\\\|\/)tests(\\\|\/)test_app(\\\|\/)plugins(\\\|\/)test_plugin_two(\\\|\/)vendors(\\\|\/)shells:";
	 	$expected .= "\n\t example";
	 	$expected .= "\n\t welcome";
	 	$expected .= "\n/";
	 	$this->assertPattern($expected, $Dispatcher->stdout);

	 	$expected = "/ APP(\\\|\/)vendors(\\\|\/)shells:";
	 	$expected .= "\n/";
	 	$this->assertPattern($expected, $Dispatcher->stdout);

	 	$expected = "/ ROOT(\\\|\/)vendors(\\\|\/)shells:";
	 	$expected .= "\n/";
	 	$this->assertPattern($expected, $Dispatcher->stdout);

	 	$expected = "/ CORE(\\\|\/)console(\\\|\/)libs:";
	 	$expected .= "\n\t acl";
	 	$expected .= "\n\t api";
	 	$expected .= "\n\t bake";
	 	$expected .= "\n\t console";
	 	$expected .= "\n\t i18n";
	 	$expected .= "\n\t schema";
	 	$expected .= "\n\t testsuite";
	 	$expected .= "\n/";
	 	$this->assertPattern($expected, $Dispatcher->stdout);

	 	$expected = "/ CORE(\\\|\/)tests(\\\|\/)test_app(\\\|\/)vendors(\\\|\/)shells:";
	 	$expected .= "\n\t sample";
	 	$expected .= "\n/";
	 	$this->assertPattern($expected, $Dispatcher->stdout);
	}
}
?>