<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         CakePHP(tm) v 2.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
namespace Cake\Test\TestCase\Core;

use Cake\Core\App;
use Cake\Core\Configure;
use Cake\Core\Plugin;
use Cake\TestSuite\TestCase;

/**
 * PluginTest class
 *
 * @package Cake.Test.Case.Core
 */
class PluginTest extends TestCase {

/**
 * Sets the plugins folder for this test
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();
		App::build(array(
			'Plugin' => array(CAKE . 'Test' . DS . 'TestApp' . DS . 'Plugin' . DS)
		), App::RESET);
		App::objects('Plugin', null, false);
	}

/**
 * Reverts the changes done to the environment while testing
 *
 * @return void
 */
	public function tearDown() {
		parent::tearDown();
		Plugin::unload();
	}

/**
 * Test the plugin namespace
 *
 * @return void
 */
	public function testGetNamespace() {
		Plugin::load('TestPlugin');
		$this->assertEquals('TestPlugin', Plugin::getNamespace('TestPlugin'));

		App::build(array(
			'Plugin' => array(CAKE . 'Test' . DS . 'TestApp' . DS . 'Plugin2' . DS)
		), App::RESET);

		Plugin::load('TestPluginThree', array('namespace' => 'Company\TestPluginThree'));
		$this->assertEquals('Company\TestPluginThree', Plugin::getNamespace('TestPluginThree'));

		Plugin::load('CustomPlugin', array('namespace' => 'Company\TestPluginThree'));
		$this->assertEquals('Company\TestPluginThree', Plugin::getNamespace('CustomPlugin'));
	}

	public function testLoadNamespacedPlugin() {
		App::build(array(
			'Plugin' => array(CAKE . 'Test' . DS . 'TestApp' . DS . 'Plugin2' . DS)
		), App::RESET);

		$mock = $this->getMock('Cake\Core\Plugin', array('_addClassLoader'));
		$mock->staticExpects($this->once())
			->method('_addClassLoader')
			->with('Company\TestPluginThree', CAKE . 'Test' . DS . 'TestApp' . DS . 'Plugin2');
		$mock::load('Company\TestPluginThree');

		$expected = array('TestPluginThree');
		$this->assertEquals($expected, $mock::loaded());
	}

/**
 * Tests loading a single plugin
 *
 * @return void
 */
	public function testLoadSingle() {
		Plugin::unload();
		Plugin::load('TestPlugin');
		$expected = array('TestPlugin');
		$this->assertEquals($expected, Plugin::loaded());
	}

/**
 * Tests unloading plugins
 *
 * @return void
 */
	public function testUnload() {
		Plugin::load('TestPlugin');
		$expected = array('TestPlugin');
		$this->assertEquals($expected, Plugin::loaded());

		Plugin::unload('TestPlugin');
		$this->assertEquals(array(), Plugin::loaded());

		Plugin::load('TestPlugin');
		$expected = array('TestPlugin');
		$this->assertEquals($expected, Plugin::loaded());

		Plugin::unload('TestFakePlugin');
		$this->assertEquals($expected, Plugin::loaded());
	}

/**
 * Tests loading a plugin and its bootstrap file
 *
 * @return void
 */
	public function testLoadSingleWithBootstrap() {
		Plugin::load('TestPlugin', array('bootstrap' => true));
		$this->assertTrue(Plugin::loaded('TestPlugin'));
		$this->assertEquals('loaded plugin bootstrap', Configure::read('PluginTest.test_plugin.bootstrap'));
	}

/**
 * Tests loading a plugin with bootstrap file and routes file
 *
 * @return void
 */
	public function testLoadSingleWithBootstrapAndRoutes() {
		Plugin::load('TestPlugin', array('bootstrap' => true, 'routes' => true));
		$this->assertTrue(Plugin::loaded('TestPlugin'));
		$this->assertEquals('loaded plugin bootstrap', Configure::read('PluginTest.test_plugin.bootstrap'));

		Plugin::routes();
		$this->assertEquals('loaded plugin routes', Configure::read('PluginTest.test_plugin.routes'));
	}

/**
 * Tests loading multiple plugins at once
 *
 * @return void
 */
	public function testLoadMultiple() {
		Plugin::load(array('TestPlugin', 'TestPluginTwo'));
		$expected = array('TestPlugin', 'TestPluginTwo');
		$this->assertEquals($expected, Plugin::loaded());
	}

/**
 * Tests loading multiple plugins and their bootstrap files
 *
 * @return void
 */
	public function testLoadMultipleWithDefaults() {
		Plugin::load(array('TestPlugin', 'TestPluginTwo'), array('bootstrap' => true, 'routes' => false));
		$expected = array('TestPlugin', 'TestPluginTwo');
		$this->assertEquals($expected, Plugin::loaded());
		$this->assertEquals('loaded plugin bootstrap', Configure::read('PluginTest.test_plugin.bootstrap'));
		$this->assertEquals('loaded plugin two bootstrap', Configure::read('PluginTest.test_plugin_two.bootstrap'));
	}

/**
 * Tests loading multiple plugins with default loading params and some overrides
 *
 * @return void
 */
	public function testLoadMultipleWithDefaultsAndOverride() {
		Plugin::load(
			array('TestPlugin', 'TestPluginTwo' => array('routes' => false)),
			array('bootstrap' => true, 'routes' => true)
		);
		$expected = array('TestPlugin', 'TestPluginTwo');
		$this->assertEquals($expected, Plugin::loaded());
		$this->assertEquals('loaded plugin bootstrap', Configure::read('PluginTest.test_plugin.bootstrap'));
		$this->assertEquals(null, Configure::read('PluginTest.test_plugin_two.bootstrap'));
	}

/**
 * Tests that it is possible to load multiple bootstrap files at once
 *
 * @return void
 */
	public function testMultipleBootstrapFiles() {
		Plugin::load('TestPlugin', array('bootstrap' => array('bootstrap', 'custom_config')));
		$this->assertTrue(Plugin::loaded('TestPlugin'));
		$this->assertEquals('loaded plugin bootstrap', Configure::read('PluginTest.test_plugin.bootstrap'));
	}

/**
 * Tests that it is possible to load plugin bootstrap by calling a callback function
 *
 * @return void
 */
	public function testCallbackBootstrap() {
		Plugin::load('TestPlugin', array('bootstrap' => array($this, 'pluginBootstrap')));
		$this->assertTrue(Plugin::loaded('TestPlugin'));
		$this->assertEquals('called plugin bootstrap callback', Configure::read('PluginTest.test_plugin.bootstrap'));
	}

/**
 * Tests that loading a missing routes file throws a warning
 *
 * @return void
 * @expectedException \PHPUNIT_FRAMEWORK_ERROR_WARNING
 */
	public function testLoadMultipleWithDefaultsMissingFile() {
		Plugin::load(array('TestPlugin', 'TestPluginTwo'), array('bootstrap' => true, 'routes' => true));
		Plugin::routes();
	}

/**
 * Test ignoring missing bootstrap/routes file
 *
 * @return void
 */
	public function testIgnoreMissingFiles() {
		Plugin::loadAll(array(array(
				'bootstrap' => true,
				'routes' => true,
				'ignoreMissing' => true
		)));
		Plugin::routes();
	}

/**
 * Tests that Plugin::load() throws an exception on unknown plugin
 *
 * @return void
 * @expectedException Cake\Error\MissingPluginException
 */
	public function testLoadNotFound() {
		Plugin::load('MissingPlugin');
	}

/**
 * Tests that Plugin::path() returns the correct path for the loaded plugins
 *
 * @return void
 */
	public function testPath() {
		Plugin::load(array('TestPlugin', 'TestPluginTwo'));
		$expected = CAKE . 'Test' . DS . 'TestApp' . DS . 'Plugin' . DS . 'TestPlugin' . DS;
		$this->assertEquals(Plugin::path('TestPlugin'), $expected);

		$expected = CAKE . 'Test' . DS . 'TestApp' . DS . 'Plugin' . DS . 'TestPluginTwo' . DS;
		$this->assertEquals(Plugin::path('TestPluginTwo'), $expected);
	}

/**
 * Tests that Plugin::path() throws an exception on unknown plugin
 *
 * @return void
 * @expectedException Cake\Error\MissingPluginException
 */
	public function testPathNotFound() {
		Plugin::path('TestPlugin');
	}

/**
 * Tests that Plugin::loadAll() will load all plgins in the configured folder
 *
 * @return void
 */
	public function testLoadAll() {
		Plugin::loadAll();
		$expected = ['PluginJs', 'TestPlugin', 'TestPluginTwo'];
		$this->assertEquals($expected, Plugin::loaded());
	}

/**
 * Tests that Plugin::loadAll() will load all plgins in the configured folder with bootstrap loading
 *
 * @return void
 */
	public function testLoadAllWithDefaults() {
		$defaults = array('bootstrap' => true);
		Plugin::loadAll(array($defaults));
		$expected = ['PluginJs', 'TestPlugin', 'TestPluginTwo'];
		$this->assertEquals($expected, Plugin::loaded());
		$this->assertEquals('loaded js plugin bootstrap', Configure::read('PluginTest.js_plugin.bootstrap'));
		$this->assertEquals('loaded plugin bootstrap', Configure::read('PluginTest.test_plugin.bootstrap'));
		$this->assertEquals('loaded plugin two bootstrap', Configure::read('PluginTest.test_plugin_two.bootstrap'));
	}

/**
 * Tests that Plugin::loadAll() will load all plgins in the configured folder wit defaults
 * and overrides for a plugin
 *
 * @return void
 */
	public function testLoadAllWithDefaultsAndOverride() {
		Plugin::loadAll(array(array('bootstrap' => true), 'TestPlugin' => array('routes' => true)));
		Plugin::routes();

		$expected = ['PluginJs', 'TestPlugin', 'TestPluginTwo'];
		$this->assertEquals($expected, Plugin::loaded());
		$this->assertEquals('loaded js plugin bootstrap', Configure::read('PluginTest.js_plugin.bootstrap'));
		$this->assertEquals('loaded plugin routes', Configure::read('PluginTest.test_plugin.routes'));
		$this->assertEquals(null, Configure::read('PluginTest.test_plugin.bootstrap'));
		$this->assertEquals('loaded plugin two bootstrap', Configure::read('PluginTest.test_plugin_two.bootstrap'));
	}

/**
 * Auxiliary function to test plugin bootstrap callbacks
 *
 * @return void
 */
	public function pluginBootstrap() {
		Configure::write('PluginTest.test_plugin.bootstrap', 'called plugin bootstrap callback');
	}
}
