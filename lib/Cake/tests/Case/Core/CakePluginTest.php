<?php


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
		App::build(array(
			'plugins' => array(CAKE_TESTS . 'test_app' . DS . 'plugins' . DS)
		), true);
	}

/**
 * Reverts the changes done to the environment while testing
 *
 * @return void
 */
	public function tearDown() {
		App::build();
		CakePlugin::unload();
		Configure::delete('CakePluginTest');
	}

/**
 * Tests loading a single plugin
 *
 * @return void
 */
	public function testLoadSingle() {
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
		$expected = array('TestPlugin');
		$this->assertEquals($expected, CakePlugin::loaded());
		$this->assertEquals('loaded plugin bootstrap', Configure::read('CakePluginTest.test_plugin.bootstrap'));
	}

/**
 * Tests loading a plugin with bootstrap file and routes file
 *
 * @return void
 */
	public function testLoadSingleWithBootstrapAndRoutes() {
		CakePlugin::load('TestPlugin', array('bootstrap' => true, 'routes' => true));
		$expected = array('TestPlugin');
		$this->assertEquals($expected, CakePlugin::loaded());
		$this->assertEquals('loaded plugin bootstrap', Configure::read('CakePluginTest.test_plugin.bootstrap'));
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
		$expected = array('TestPlugin');
		$this->assertEquals($expected, CakePlugin::loaded());
		$this->assertEquals('loaded plugin bootstrap', Configure::read('CakePluginTest.test_plugin.bootstrap'));
	}

	public function testCallbackBootstrap() {
		CakePlugin::load('TestPlugin', array('bootstrap' => array($this, 'pluginBootstrap')));
		$expected = array('TestPlugin');
		$this->assertEquals($expected, CakePlugin::loaded());
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