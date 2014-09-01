<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         2.0.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Core;

use Cake\Core\App;
use Cake\Core\Configure;
use Cake\Core\Plugin;
use Cake\TestSuite\TestCase;

/**
 * PluginTest class
 *
 */
class PluginTest extends TestCase {

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
 * Test load() with the autoload option.
 *
 * @return void
 */
	public function testLoadSingleWithAutoload() {
		$this->assertFalse(class_exists('Company/TestPluginThree\Utility\Hello'));
		Plugin::load('Company/TestPluginThree', [
			'autoload' => true,
		]);
		$this->assertTrue(
			class_exists('Company\TestPluginThree\Utility\Hello'),
			'Class should be loaded'
		);
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

		Plugin::load('Company/TestPluginThree', array('bootstrap' => true));
		$this->assertTrue(Plugin::loaded('Company/TestPluginThree'));
		$this->assertEquals('loaded plugin three bootstrap', Configure::read('PluginTest.test_plugin_three.bootstrap'));
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
		$this->assertTrue(Plugin::routes());
	}

/**
 * Tests that Plugin::load() throws an exception on unknown plugin
 *
 * @return void
 * @expectedException \Cake\Core\Exception\MissingPluginException
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
		Plugin::load(array('TestPlugin', 'TestPluginTwo', 'Company/TestPluginThree'));
		$expected = TEST_APP . 'Plugin' . DS . 'TestPlugin' . DS;
		$this->assertPathEquals(Plugin::path('TestPlugin'), $expected);

		$expected = TEST_APP . 'Plugin' . DS . 'TestPluginTwo' . DS;
		$this->assertPathEquals(Plugin::path('TestPluginTwo'), $expected);

		$expected = TEST_APP . 'Plugin' . DS . 'Company' . DS . 'TestPluginThree' . DS;
		$this->assertPathEquals(Plugin::path('Company/TestPluginThree'), $expected);
	}

/**
 * Tests that Plugin::path() throws an exception on unknown plugin
 *
 * @return void
 * @expectedException \Cake\Core\Exception\MissingPluginException
 */
	public function testPathNotFound() {
		Plugin::path('TestPlugin');
	}

/**
 * Tests that Plugin::classPath() returns the correct path for the loaded plugins
 *
 * @return void
 */
	public function testClassPath() {
		Plugin::load(array('TestPlugin', 'TestPluginTwo', 'Company/TestPluginThree'));
		$expected = TEST_APP . 'Plugin' . DS . 'TestPlugin' . DS . 'src' . DS;
		$this->assertPathEquals(Plugin::classPath('TestPlugin'), $expected);

		$expected = TEST_APP . 'Plugin' . DS . 'TestPluginTwo' . DS . 'src' . DS;
		$this->assertPathEquals(Plugin::classPath('TestPluginTwo'), $expected);

		$expected = TEST_APP . 'Plugin' . DS . 'Company' . DS . 'TestPluginThree' . DS . 'src' . DS;
		$this->assertPathEquals(Plugin::classPath('Company/TestPluginThree'), $expected);
	}

/**
 * Tests that Plugin::classPath() throws an exception on unknown plugin
 *
 * @return void
 * @expectedException \Cake\Core\Exception\MissingPluginException
 */
	public function testClassPathNotFound() {
		Plugin::classPath('TestPlugin');
	}

/**
 * Tests that Plugin::loadAll() will load all plgins in the configured folder
 *
 * @return void
 */
	public function testLoadAll() {
		Plugin::loadAll();
		$expected = ['Company', 'PluginJs', 'TestPlugin', 'TestPluginFour', 'TestPluginTwo', 'TestTheme'];
		$this->assertEquals($expected, Plugin::loaded());
	}

/**
 * Test that plugins don't reload using loadAll();
 *
 * @return void
 */
	public function testLoadAllWithPluginAlreadyLoaded() {
		Plugin::load('Company/TestPluginThree', ['bootstrap' => false]);
		Plugin::loadAll(['bootstrap' => true, 'ignoreMissing' => true]);
		$this->assertEmpty(Configure::read('PluginTest.test_plugin_three.bootstrap'));
	}

/**
 * Tests that Plugin::loadAll() will load all plgins in the configured folder with bootstrap loading
 *
 * @return void
 */
	public function testLoadAllWithDefaults() {
		$defaults = array('bootstrap' => true, 'ignoreMissing' => true);
		Plugin::loadAll(array($defaults));
		$expected = ['Company', 'PluginJs', 'TestPlugin', 'TestPluginFour', 'TestPluginTwo', 'TestTheme'];
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
		Plugin::loadAll(array(
			array('bootstrap' => true, 'ignoreMissing' => true),
			'TestPlugin' => array('routes' => true),
			'TestPluginFour' => array('bootstrap' => true, 'classBase' => '')
		));
		Plugin::routes();

		$expected = ['Company', 'PluginJs', 'TestPlugin', 'TestPluginFour', 'TestPluginTwo', 'TestTheme'];
		$this->assertEquals($expected, Plugin::loaded());
		$this->assertEquals('loaded js plugin bootstrap', Configure::read('PluginTest.js_plugin.bootstrap'));
		$this->assertEquals('loaded plugin routes', Configure::read('PluginTest.test_plugin.routes'));
		$this->assertEquals(null, Configure::read('PluginTest.test_plugin.bootstrap'));
		$this->assertEquals('loaded plugin two bootstrap', Configure::read('PluginTest.test_plugin_two.bootstrap'));
		$this->assertEquals('loaded plugin four bootstrap', Configure::read('PluginTest.test_plugin_four.bootstrap'));

		// TestPluginThree won't get loaded by loadAll() since it's in a sub directory.
		$this->assertEquals(null, Configure::read('PluginTest.test_plugin_three.bootstrap'));
	}

}
