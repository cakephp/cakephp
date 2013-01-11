<?php
/**
 * CakePluginTest file.
 *
 * PHP 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       Cake.Test.Case.Core
 * @since         CakePHP(tm) v 2.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

App::uses('CakePlugin', 'Core');

/**
 * CakePluginTest class
 *
 */
class CakePluginTest extends CakeTestCase {

/**
 * Sets the plugins folder for this test
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();
		App::build(array(
			'Plugin' => array(CAKE . 'Test' . DS . 'test_app' . DS . 'Plugin' . DS)
		), App::RESET);
		App::objects('plugins', null, false);
	}

/**
 * Reverts the changes done to the environment while testing
 *
 * @return void
 */
	public function tearDown() {
		parent::tearDown();
		CakePlugin::unload();
	}

/**
 * Tests loading a single plugin
 *
 * @return void
 */
	public function testLoadSingle() {
		CakePlugin::unload();
		CakePlugin::load('TestPlugin');
		$expected = array('TestPlugin');
		$this->assertEquals($expected, CakePlugin::loaded());
	}

/**
 * Tests unloading plugins
 *
 * @return void
 */
	public function testUnload() {
		CakePlugin::load('TestPlugin');
		$expected = array('TestPlugin');
		$this->assertEquals($expected, CakePlugin::loaded());

		CakePlugin::unload('TestPlugin');
		$this->assertEquals(array(), CakePlugin::loaded());

		CakePlugin::load('TestPlugin');
		$expected = array('TestPlugin');
		$this->assertEquals($expected, CakePlugin::loaded());

		CakePlugin::unload('TestFakePlugin');
		$this->assertEquals($expected, CakePlugin::loaded());
	}

/**
 * Tests loading a plugin and its bootstrap file
 *
 * @return void
 */
	public function testLoadSingleWithBootstrap() {
		CakePlugin::load('TestPlugin', array('bootstrap' => true));
		$this->assertTrue(CakePlugin::loaded('TestPlugin'));
		$this->assertEquals('loaded plugin bootstrap', Configure::read('CakePluginTest.test_plugin.bootstrap'));
	}

/**
 * Tests loading a plugin with bootstrap file and routes file
 *
 * @return void
 */
	public function testLoadSingleWithBootstrapAndRoutes() {
		CakePlugin::load('TestPlugin', array('bootstrap' => true, 'routes' => true));
		$this->assertTrue(CakePlugin::loaded('TestPlugin'));
		$this->assertEquals('loaded plugin bootstrap', Configure::read('CakePluginTest.test_plugin.bootstrap'));

		CakePlugin::routes();
		$this->assertEquals('loaded plugin routes', Configure::read('CakePluginTest.test_plugin.routes'));
	}

/**
 * Tests loading multiple plugins at once
 *
 * @return void
 */
	public function testLoadMultiple() {
		CakePlugin::load(array('TestPlugin', 'TestPluginTwo'));
		$expected = array('TestPlugin', 'TestPluginTwo');
		$this->assertEquals($expected, CakePlugin::loaded());
	}

/**
 * Tests loading multiple plugins and their bootstrap files
 *
 * @return void
 */
	public function testLoadMultipleWithDefaults() {
		CakePlugin::load(array('TestPlugin', 'TestPluginTwo'), array('bootstrap' => true, 'routes' => false));
		$expected = array('TestPlugin', 'TestPluginTwo');
		$this->assertEquals($expected, CakePlugin::loaded());
		$this->assertEquals('loaded plugin bootstrap', Configure::read('CakePluginTest.test_plugin.bootstrap'));
		$this->assertEquals('loaded plugin two bootstrap', Configure::read('CakePluginTest.test_plugin_two.bootstrap'));
	}

/**
 * Tests loading multiple plugins with default loading params and some overrides
 *
 * @return void
 */
	public function testLoadMultipleWithDefaultsAndOverride() {
		CakePlugin::load(
			array('TestPlugin', 'TestPluginTwo' => array('routes' => false)),
			array('bootstrap' => true, 'routes' => true)
		);
		$expected = array('TestPlugin', 'TestPluginTwo');
		$this->assertEquals($expected, CakePlugin::loaded());
		$this->assertEquals('loaded plugin bootstrap', Configure::read('CakePluginTest.test_plugin.bootstrap'));
		$this->assertEquals(null, Configure::read('CakePluginTest.test_plugin_two.bootstrap'));
	}

/**
 * Tests that it is possible to load multiple bootstrap files at once
 *
 * @return void
 */
	public function testMultipleBootstrapFiles() {
		CakePlugin::load('TestPlugin', array('bootstrap' => array('bootstrap', 'custom_config')));
		$this->assertTrue(CakePlugin::loaded('TestPlugin'));
		$this->assertEquals('loaded plugin bootstrap', Configure::read('CakePluginTest.test_plugin.bootstrap'));
	}

/**
 * Tests that it is possible to load plugin bootstrap by calling a callback function
 *
 * @return void
 */
	public function testCallbackBootstrap() {
		CakePlugin::load('TestPlugin', array('bootstrap' => array($this, 'pluginBootstrap')));
		$this->assertTrue(CakePlugin::loaded('TestPlugin'));
		$this->assertEquals('called plugin bootstrap callback', Configure::read('CakePluginTest.test_plugin.bootstrap'));
	}

/**
 * Tests that loading a missing routes file throws a warning
 *
 * @return void
 * @expectedException PHPUNIT_FRAMEWORK_ERROR_WARNING
 */
	public function testLoadMultipleWithDefaultsMissingFile() {
		CakePlugin::load(array('TestPlugin', 'TestPluginTwo'), array('bootstrap' => true, 'routes' => true));
		CakePlugin::routes();
	}

/**
 * Test ignoring missing bootstrap/routes file
 *
 * @return void
 */
	public function testIgnoreMissingFiles() {
		CakePlugin::loadAll(array(array(
			'bootstrap' => true,
			'routes' => true,
			'ignoreMissing' => true
		)));
		CakePlugin::routes();
	}

/**
 * Tests that CakePlugin::load() throws an exception on unknown plugin
 *
 * @return void
 * @expectedException MissingPluginException
 */
	public function testLoadNotFound() {
		CakePlugin::load('MissingPlugin');
	}

/**
 * Tests that CakePlugin::path() returns the correct path for the loaded plugins
 *
 * @return void
 */
	public function testPath() {
		CakePlugin::load(array('TestPlugin', 'TestPluginTwo'));
		$expected = CAKE . 'Test' . DS . 'test_app' . DS . 'Plugin' . DS . 'TestPlugin' . DS;
		$this->assertEquals(CakePlugin::path('TestPlugin'), $expected);

		$expected = CAKE . 'Test' . DS . 'test_app' . DS . 'Plugin' . DS . 'TestPluginTwo' . DS;
		$this->assertEquals(CakePlugin::path('TestPluginTwo'), $expected);
	}

/**
 * Tests that CakePlugin::path() throws an exception on unknown plugin
 *
 * @return void
 * @expectedException MissingPluginException
 */
	public function testPathNotFound() {
		CakePlugin::path('TestPlugin');
	}

/**
 * Tests that CakePlugin::loadAll() will load all plugins in the configured folder
 *
 * @return void
 */
	public function testLoadAll() {
		CakePlugin::loadAll();
		$expected = array('PluginJs', 'TestPlugin', 'TestPluginTwo');
		$this->assertEquals($expected, CakePlugin::loaded());
	}

/**
 * Tests that CakePlugin::loadAll() will load all plugins in the configured folder with bootstrap loading
 *
 * @return void
 */
	public function testLoadAllWithDefaults() {
		$defaults = array('bootstrap' => true);
		CakePlugin::loadAll(array($defaults));
		$expected = array('PluginJs', 'TestPlugin', 'TestPluginTwo');
		$this->assertEquals($expected, CakePlugin::loaded());
		$this->assertEquals('loaded js plugin bootstrap', Configure::read('CakePluginTest.js_plugin.bootstrap'));
		$this->assertEquals('loaded plugin bootstrap', Configure::read('CakePluginTest.test_plugin.bootstrap'));
		$this->assertEquals('loaded plugin two bootstrap', Configure::read('CakePluginTest.test_plugin_two.bootstrap'));
	}

/**
 * Tests that CakePlugin::loadAll() will load all plugins in the configured folder wit defaults
 * and overrides for a plugin
 *
 * @return void
 */
	public function testLoadAllWithDefaultsAndOverride() {
		CakePlugin::loadAll(array(array('bootstrap' => true), 'TestPlugin' => array('routes' => true)));
		CakePlugin::routes();

		$expected = array('PluginJs', 'TestPlugin', 'TestPluginTwo');
		$this->assertEquals($expected, CakePlugin::loaded());
		$this->assertEquals('loaded js plugin bootstrap', Configure::read('CakePluginTest.js_plugin.bootstrap'));
		$this->assertEquals('loaded plugin routes', Configure::read('CakePluginTest.test_plugin.routes'));
		$this->assertEquals(null, Configure::read('CakePluginTest.test_plugin.bootstrap'));
		$this->assertEquals('loaded plugin two bootstrap', Configure::read('CakePluginTest.test_plugin_two.bootstrap'));
	}

/**
 * Auxiliary function to test plugin bootstrap callbacks
 *
 * @return void
 */
	public function pluginBootstrap() {
		Configure::write('CakePluginTest.test_plugin.bootstrap', 'called plugin bootstrap callback');
	}
}
