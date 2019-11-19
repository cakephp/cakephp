<?php
/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         2.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Core;

use Cake\Core\BasePlugin;
use Cake\Core\Configure;
use Cake\Core\Plugin;
use Cake\TestSuite\TestCase;
use TestPlugin\Plugin as TestPlugin;

/**
 * PluginTest class
 */
class PluginTest extends TestCase
{
    /**
     * Setup
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $this->clearPlugins();
    }

    /**
     * Reverts the changes done to the environment while testing
     *
     * @return void
     */
    public function tearDown()
    {
        parent::tearDown();
        $this->clearPlugins();
    }

    /**
     * Tests loading a single plugin
     *
     * @return void
     */
    public function testLoad()
    {
        $this->deprecated(function () {
            Plugin::load('TestPlugin');
            $expected = ['TestPlugin'];
            $this->assertEquals($expected, Plugin::loaded());
        });
    }

    /**
     * Tests loading a plugin with a class
     *
     * @return void
     */
    public function testLoadConcreteClass()
    {
        $this->deprecated(function () {
            Plugin::load('TestPlugin');
            $instance = Plugin::getCollection()->get('TestPlugin');
            $this->assertSame(TestPlugin::class, get_class($instance));
        });
    }

    /**
     * Tests loading a plugin without a class
     *
     * @return void
     */
    public function testLoadDynamicClass()
    {
        $this->deprecated(function () {
            Plugin::load('TestPluginTwo');
            $instance = Plugin::getCollection()->get('TestPluginTwo');
            $this->assertSame(BasePlugin::class, get_class($instance));
        });
    }

    /**
     * Tests unloading plugins
     *
     * @deprecated
     * @return void
     */
    public function testUnload()
    {
        $this->deprecated(function () {
            $this->loadPlugins(['TestPlugin' => ['bootstrap' => false, 'routes' => false]]);
            $expected = ['TestPlugin'];
            $this->assertEquals($expected, Plugin::loaded());
            $this->assertTrue(Plugin::isLoaded('TestPlugin'));

            Plugin::unload('TestPlugin');
            $this->assertEquals([], Plugin::loaded());
            $this->assertFalse(Plugin::isLoaded('TestPlugin'));

            $this->loadPlugins(['TestPlugin' => ['bootstrap' => false, 'routes' => false]]);
            $expected = ['TestPlugin'];
            $this->assertEquals($expected, Plugin::loaded());

            Plugin::unload('TestFakePlugin');
            $this->assertEquals($expected, Plugin::loaded());
            $this->assertFalse(Plugin::isLoaded('TestFakePlugin'));
        });
    }

    /**
     * Test load() with the autoload option.
     *
     * @return void
     */
    public function testLoadWithAutoload()
    {
        $this->deprecated(function () {
            $this->assertFalse(class_exists('Company\TestPluginFive\Utility\Hello'));
            Plugin::load('Company/TestPluginFive', [
                'autoload' => true,
            ]);
            $this->assertTrue(
                class_exists('Company\TestPluginFive\Utility\Hello'),
                'Class should be loaded'
            );
        });
    }

    /**
     * Test load() with the autoload option.
     *
     * @return void
     */
    public function testLoadWithAutoloadAndBootstrap()
    {
        $this->deprecated(function () {
            Plugin::load(
                'Company/TestPluginFive',
                [
                    'autoload' => true,
                    'bootstrap' => true,
                ]
            );
            $this->assertTrue(Configure::read('PluginTest.test_plugin_five.autoload'));
            $this->assertEquals('loaded plugin five bootstrap', Configure::read('PluginTest.test_plugin_five.bootstrap'));
            $this->assertTrue(
                class_exists('Company\TestPluginFive\Utility\Hello'),
                'Class should be loaded'
            );
        });
    }

    /**
     * Tests deprecated usage of loaded()
     *
     * @deprecated
     * @return void
     */
    public function testIsLoaded()
    {
        $this->deprecated(function () {
            Plugin::load('TestPlugin');
            $this->assertTrue(Plugin::loaded('TestPlugin'));
            $this->assertFalse(Plugin::loaded('Unknown'));
        });
    }

    /**
     * Tests loading a plugin and its bootstrap file
     *
     * @deprecated
     * @return void
     */
    public function testLoadWithBootstrap()
    {
        $this->deprecated(function () {
            Plugin::load('TestPlugin', ['bootstrap' => true]);
            $this->assertTrue(Plugin::isLoaded('TestPlugin'));
            $this->assertEquals('loaded plugin bootstrap', Configure::read('PluginTest.test_plugin.bootstrap'));

            Plugin::load('Company/TestPluginThree', ['bootstrap' => true]);
            $this->assertTrue(Plugin::isLoaded('Company/TestPluginThree'));
            $this->assertEquals('loaded plugin three bootstrap', Configure::read('PluginTest.test_plugin_three.bootstrap'));
        });
    }

    /**
     * Tests loading a plugin and its bootstrap file
     *
     * @deprecated
     * @return void
     */
    public function testLoadWithBootstrapDisableBootstrapHook()
    {
        $this->deprecated(function () {
            Plugin::load('TestPlugin', ['bootstrap' => true]);
            $this->assertTrue(Plugin::isLoaded('TestPlugin'));
            $this->assertEquals('loaded plugin bootstrap', Configure::read('PluginTest.test_plugin.bootstrap'));

            $plugin = Plugin::getCollection()->get('TestPlugin');
            $this->assertFalse($plugin->isEnabled('bootstrap'), 'Should be disabled as hook has been run.');
        });
    }

    /**
     * Tests loading a plugin with bootstrap file and routes file
     *
     * @deprecated
     * @return void
     */
    public function testLoadSingleWithBootstrapAndRoutes()
    {
        $this->deprecated(function () {
            Plugin::load('TestPlugin', ['bootstrap' => true, 'routes' => true]);
            $this->assertTrue(Plugin::loaded('TestPlugin'));
            $this->assertEquals('loaded plugin bootstrap', Configure::read('PluginTest.test_plugin.bootstrap'));

            Plugin::routes();
            $this->assertEquals('loaded plugin routes', Configure::read('PluginTest.test_plugin.routes'));
        });
    }

    /**
     * Test load() with path configuration data
     *
     * @return void
     */
    public function testLoadSingleWithPathConfig()
    {
        $this->deprecated(function () {
            Configure::write('plugins.TestPlugin', APP);
            Plugin::load('TestPlugin');
            $this->assertEquals(APP . 'src' . DS, Plugin::classPath('TestPlugin'));
        });
    }

    /**
     * Tests loading multiple plugins at once
     *
     * @return void
     */
    public function testLoadMultiple()
    {
        $this->deprecated(function () {
            Plugin::load(['TestPlugin', 'TestPluginTwo']);
            $expected = ['TestPlugin', 'TestPluginTwo'];
            $this->assertEquals($expected, Plugin::loaded());
        });
    }

    /**
     * Tests loading multiple plugins and their bootstrap files
     *
     * @return void
     */
    public function testLoadMultipleWithDefaults()
    {
        $this->deprecated(function () {
            Plugin::load(['TestPlugin', 'TestPluginTwo'], ['bootstrap' => true, 'routes' => false]);
            $expected = ['TestPlugin', 'TestPluginTwo'];
            $this->assertEquals($expected, Plugin::loaded());
            $this->assertEquals('loaded plugin bootstrap', Configure::read('PluginTest.test_plugin.bootstrap'));
            $this->assertEquals('loaded plugin two bootstrap', Configure::read('PluginTest.test_plugin_two.bootstrap'));
        });
    }

    /**
     * Tests loading multiple plugins with default loading params and some overrides
     *
     * @return void
     */
    public function testLoadMultipleWithDefaultsAndOverride()
    {
        $this->deprecated(function () {
            Plugin::load(
                ['TestPlugin', 'TestPluginTwo' => ['routes' => false]],
                ['bootstrap' => true, 'routes' => true]
            );
            $expected = ['TestPlugin', 'TestPluginTwo'];
            $this->assertEquals($expected, Plugin::loaded());
            $this->assertEquals('loaded plugin bootstrap', Configure::read('PluginTest.test_plugin.bootstrap'));
            $this->assertNull(Configure::read('PluginTest.test_plugin_two.bootstrap'));
        });
    }

    /**
     * Test ignoring missing bootstrap/routes file
     *
     * @deprecated
     * @return void
     */
    public function testIgnoreMissingFiles()
    {
        $this->deprecated(function () {
            Plugin::loadAll([[
                'bootstrap' => true,
                'routes' => true,
                'ignoreMissing' => true,
            ]]);
            $this->assertTrue(Plugin::routes());
        });
    }

    /**
     * Tests that Plugin::load() throws an exception on unknown plugin
     *
     * @return void
     */
    public function testLoadNotFound()
    {
        $this->deprecated(function () {
            $this->expectException(\Cake\Core\Exception\MissingPluginException::class);
            Plugin::load('MissingPlugin');
        });
    }

    /**
     * Tests that Plugin::path() returns the correct path for the loaded plugins
     *
     * @return void
     */
    public function testPath()
    {
        $this->loadPlugins(['TestPlugin', 'TestPluginTwo', 'Company/TestPluginThree']);
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
     */
    public function testPathNotFound()
    {
        $this->expectException(\Cake\Core\Exception\MissingPluginException::class);
        Plugin::path('TestPlugin');
    }

    /**
     * Tests that Plugin::classPath() returns the correct path for the loaded plugins
     *
     * @return void
     */
    public function testClassPath()
    {
        $this->loadPlugins(['TestPlugin', 'TestPluginTwo', 'Company/TestPluginThree']);
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
     */
    public function testClassPathNotFound()
    {
        $this->expectException(\Cake\Core\Exception\MissingPluginException::class);
        Plugin::classPath('TestPlugin');
    }

    /**
     * Tests that Plugin::loadAll() will load all plugins in the configured folder
     *
     * @return void
     */
    public function testLoadAll()
    {
        $this->deprecated(function () {
            Plugin::loadAll();
            $expected = [
                'Company', 'ParentPlugin', 'PluginJs', 'TestPlugin',
                'TestPluginFour', 'TestPluginTwo', 'TestTheme',
            ];
            $this->assertEquals($expected, Plugin::loaded());
        });
    }

    /**
     * Test loadAll() with path configuration data
     *
     * @return void
     */
    public function testLoadAllWithPathConfig()
    {
        $this->deprecated(function () {
            Configure::write('plugins.FakePlugin', APP);
            Plugin::loadAll();
            $this->assertContains('FakePlugin', Plugin::loaded());
        });
    }

    /**
     * Test that plugins don't reload using loadAll();
     *
     * @return void
     */
    public function testLoadAllWithPluginAlreadyLoaded()
    {
        $this->deprecated(function () {
            Plugin::load('Company/TestPluginThree', ['bootstrap' => false]);
            Plugin::loadAll(['bootstrap' => true, 'ignoreMissing' => true]);
            $this->assertEmpty(Configure::read('PluginTest.test_plugin_three.bootstrap'));
        });
    }

    /**
     * Tests that Plugin::loadAll() will load all plugins in the configured folder with bootstrap loading
     *
     * @return void
     */
    public function testLoadAllWithDefaults()
    {
        $this->deprecated(function () {
            $defaults = ['bootstrap' => true, 'ignoreMissing' => true];
            Plugin::loadAll([$defaults]);
            $expected = [
                'Company', 'ParentPlugin', 'PluginJs', 'TestPlugin',
                'TestPluginFour', 'TestPluginTwo', 'TestTheme',
            ];
            $this->assertEquals($expected, Plugin::loaded());
            $this->assertEquals('loaded js plugin bootstrap', Configure::read('PluginTest.js_plugin.bootstrap'));
            $this->assertEquals('loaded plugin bootstrap', Configure::read('PluginTest.test_plugin.bootstrap'));
            $this->assertEquals('loaded plugin two bootstrap', Configure::read('PluginTest.test_plugin_two.bootstrap'));
        });
    }

    /**
     * Tests that Plugin::loadAll() will load all plugins in the configured folder wit defaults
     * and overrides for a plugin
     *
     * @deprecated
     * @return void
     */
    public function testLoadAllWithDefaultsAndOverride()
    {
        $this->deprecated(function () {
            Plugin::loadAll([
                ['bootstrap' => true, 'ignoreMissing' => true],
                'TestPlugin' => ['routes' => true],
                'TestPluginFour' => ['bootstrap' => true, 'classBase' => ''],
            ]);
            Plugin::routes();

            $expected = [
                'Company', 'ParentPlugin', 'PluginJs', 'TestPlugin',
                'TestPluginFour', 'TestPluginTwo', 'TestTheme',
            ];
            $this->assertEquals($expected, Plugin::loaded());
            $this->assertEquals('loaded js plugin bootstrap', Configure::read('PluginTest.js_plugin.bootstrap'));
            $this->assertEquals('loaded plugin routes', Configure::read('PluginTest.test_plugin.routes'));
            $this->assertNull(Configure::read('PluginTest.test_plugin.bootstrap'));
            $this->assertEquals('loaded plugin two bootstrap', Configure::read('PluginTest.test_plugin_two.bootstrap'));
            $this->assertEquals('loaded plugin four bootstrap', Configure::read('PluginTest.test_plugin_four.bootstrap'));

            // TestPluginThree won't get loaded by loadAll() since it's in a sub directory.
            $this->assertNull(Configure::read('PluginTest.test_plugin_three.bootstrap'));
        });
    }
}
