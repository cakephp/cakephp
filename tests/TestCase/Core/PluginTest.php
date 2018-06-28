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
use Cake\Routing\Router;
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
        Configure::write('App.namespace', 'TestApp');
        Plugin::unload();
    }

    /**
     * Reverts the changes done to the environment while testing
     *
     * @return void
     */
    public function tearDown()
    {
        parent::tearDown();
        Plugin::unload();
    }

    /**
     * simulate running the Application
     *
     * @return \Cake\Http\BaseApplication
     */
    public function runApplication()
    {
        $app = $this->getMockForAbstractClass(
            Configure::read('App.namespace') . '\ApplicationWithDefaultRoutes',
            ['']
        );
        $app->pluginBootstrap();
        $builder = Router::createRouteBuilder('/');
        $app->pluginRoutes($builder);

        return $app;
    }

    /**
     * Tests loading a single plugin
     *
     * @return void
     */
    public function testLoad()
    {
        Plugin::load('TestPlugin');
        $expected = ['TestPlugin'];
        $this->assertEquals($expected, Plugin::loaded());
    }

    /**
     * Tests loading a plugin with a class
     *
     * @return void
     */
    public function testLoadConcreteClass()
    {
        Plugin::load('TestPlugin');
        $instance = Plugin::getCollection()->get('TestPlugin');
        $this->assertSame(TestPlugin::class, get_class($instance));
    }

    /**
     * Tests loading a plugin without a class
     *
     * @return void
     */
    public function testLoadDynamicClass()
    {
        Plugin::load('TestPluginTwo');
        $instance = Plugin::getCollection()->get('TestPluginTwo');
        $this->assertSame(BasePlugin::class, get_class($instance));
    }

    /**
     * Tests unloading plugins
     *
     * @return void
     */
    public function testUnload()
    {
        Plugin::load('TestPlugin');
        $expected = ['TestPlugin'];
        $this->assertEquals($expected, Plugin::loaded());

        Plugin::unload('TestPlugin');
        $this->assertEquals([], Plugin::loaded());

        Plugin::load('TestPlugin');
        $expected = ['TestPlugin'];
        $this->assertEquals($expected, Plugin::loaded());

        Plugin::unload('TestFakePlugin');
        $this->assertEquals($expected, Plugin::loaded());
    }

    /**
     * Test load() with the autoload option.
     *
     * @return void
     */
    public function testLoadWithAutoload()
    {
        $this->assertFalse(class_exists('Company\TestPluginFive\Utility\Hello'));
        Plugin::load('Company/TestPluginFive', [
            'autoload' => true,
        ]);
        $this->assertTrue(
            class_exists('Company\TestPluginFive\Utility\Hello'),
            'Class should be loaded'
        );
    }

    /**
     * Test load() with the autoload option.
     *
     * @return void
     */
    public function testLoadWithAutoloadAndBootstrap()
    {
        Plugin::load(
            'Company/TestPluginFive',
            [
                'autoload' => true,
                'bootstrap' => true
            ]
        );
        $this->runApplication();
        $this->assertTrue(Configure::read('PluginTest.test_plugin_five.autoload'));
        $this->assertEquals('loaded plugin five bootstrap', Configure::read('PluginTest.test_plugin_five.bootstrap'));
        $this->assertTrue(
            class_exists('Company\TestPluginFive\Utility\Hello'),
            'Class should be loaded'
        );
    }

    /**
     * Tests loading a plugin and its bootstrap file
     *
     * @return void
     */
    public function testLoadWithBootstrap()
    {
        Plugin::load('TestPlugin', ['bootstrap' => true]);
        $this->assertTrue(Plugin::loaded('TestPlugin'));
        $this->runApplication();
        $this->assertEquals('loaded plugin bootstrap', Configure::read('PluginTest.test_plugin.bootstrap'));

        Plugin::load('Company/TestPluginThree', ['bootstrap' => true]);
        $this->assertTrue(Plugin::loaded('Company/TestPluginThree'));
        $this->runApplication();
        $this->assertEquals('loaded plugin three bootstrap', Configure::read('PluginTest.test_plugin_three.bootstrap'));
    }

    /**
     * Tests loading a plugin and its bootstrap file
     *
     * @return void
     */
    public function testLoadWithBootstrapEnableBootstrapHook()
    {
        Plugin::load('TestPlugin', ['bootstrap' => true]);
        $this->assertTrue(Plugin::loaded('TestPlugin'));
        $this->runApplication();
        $this->assertEquals('loaded plugin bootstrap', Configure::read('PluginTest.test_plugin.bootstrap'));

        $plugin = Plugin::getCollection()->get('TestPlugin');
        $this->assertTrue($plugin->isEnabled('bootstrap'), 'Should be disabled as hook has been run.');
    }

    /**
     * Tests loading a plugin with bootstrap file and routes file
     *
     * @return void
     */
    public function testLoadSingleWithBootstrapAndRoutes()
    {
        Plugin::load('TestPlugin', ['bootstrap' => true, 'routes' => true]);
        $this->assertTrue(Plugin::loaded('TestPlugin'));
        $this->runApplication();
        $this->assertEquals('loaded plugin bootstrap', Configure::read('PluginTest.test_plugin.bootstrap'));

        $this->assertEquals('loaded plugin routes', Configure::read('PluginTest.test_plugin.routes'));
    }

    /**
     * Test load() with path configuration data
     *
     * @return void
     */
    public function testLoadSingleWithPathConfig()
    {
        Configure::write('plugins.TestPlugin', APP);
        Plugin::load('TestPlugin');
        $this->assertEquals(APP . 'src' . DS, Plugin::classPath('TestPlugin'));
    }

    /**
     * Tests loading multiple plugins at once
     *
     * @return void
     */
    public function testLoadMultiple()
    {
        Plugin::load(['TestPlugin', 'TestPluginTwo']);
        $expected = ['TestPlugin', 'TestPluginTwo'];
        $this->assertEquals($expected, Plugin::loaded());
    }

    /**
     * Tests loading multiple plugins and their bootstrap files
     *
     * @return void
     */
    public function testLoadMultipleWithDefaults()
    {
        Plugin::load(['TestPlugin', 'TestPluginTwo'], ['bootstrap' => true, 'routes' => false]);
        $expected = ['TestPlugin', 'TestPluginTwo'];
        $this->assertEquals($expected, Plugin::loaded());
        $this->runApplication();
        $this->assertEquals('loaded plugin bootstrap', Configure::read('PluginTest.test_plugin.bootstrap'));
        $this->assertEquals('loaded plugin two bootstrap', Configure::read('PluginTest.test_plugin_two.bootstrap'));
    }

    /**
     * Tests loading multiple plugins with default loading params and some overrides
     *
     * @return void
     */
    public function testLoadMultipleWithDefaultsAndOverride()
    {
        Plugin::load(
            ['TestPlugin', 'TestPluginTwo' => ['routes' => false]],
            ['bootstrap' => true, 'routes' => true]
        );
        $expected = ['TestPlugin', 'TestPluginTwo'];
        $this->assertEquals($expected, Plugin::loaded());
        $this->runApplication();
        $this->assertEquals('loaded plugin bootstrap', Configure::read('PluginTest.test_plugin.bootstrap'));
        $this->assertNull(Configure::read('PluginTest.test_plugin_two.bootstrap'));
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
                'ignoreMissing' => true
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
        $this->expectException(\Cake\Core\Exception\MissingPluginException::class);
        Plugin::load('MissingPlugin');
    }

    /**
     * Tests that Plugin::path() returns the correct path for the loaded plugins
     *
     * @return void
     */
    public function testPath()
    {
        Plugin::load(['TestPlugin', 'TestPluginTwo', 'Company/TestPluginThree']);
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
        Plugin::load(['TestPlugin', 'TestPluginTwo', 'Company/TestPluginThree']);
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
        Plugin::loadAll();
        $expected = [
            'Company', 'ParentPlugin', 'PluginJs', 'TestPlugin',
            'TestPluginFour', 'TestPluginTwo', 'TestTheme'
        ];
        $this->assertEquals($expected, Plugin::loaded());
    }

    /**
     * Test loadAll() with path configuration data
     *
     * @return void
     */
    public function testLoadAllWithPathConfig()
    {
        Configure::write('plugins.FakePlugin', APP);
        Plugin::loadAll();
        $this->assertContains('FakePlugin', Plugin::loaded());
    }

    /**
     * Test that plugins don't reload using loadAll();
     *
     * @return void
     */
    public function testLoadAllWithPluginAlreadyLoaded()
    {
        Plugin::load('Company/TestPluginThree', ['bootstrap' => false]);
        Plugin::loadAll(['bootstrap' => true, 'ignoreMissing' => true]);
        $this->assertEmpty(Configure::read('PluginTest.test_plugin_three.bootstrap'));
    }

    /**
     * Tests that Plugin::loadAll() will load all plugins in the configured folder with bootstrap loading
     *
     * @return void
     */
    public function testLoadAllWithDefaults()
    {
        $defaults = ['bootstrap' => true, 'ignoreMissing' => true];
        Plugin::loadAll([$defaults]);
        $expected = [
            'Company', 'ParentPlugin', 'PluginJs', 'TestPlugin',
            'TestPluginFour', 'TestPluginTwo', 'TestTheme'
        ];
        $this->assertEquals($expected, Plugin::loaded());
        $this->runApplication();
        $this->assertEquals('loaded js plugin bootstrap', Configure::read('PluginTest.js_plugin.bootstrap'));
        $this->assertEquals('loaded plugin bootstrap', Configure::read('PluginTest.test_plugin.bootstrap'));
        $this->assertEquals('loaded plugin two bootstrap', Configure::read('PluginTest.test_plugin_two.bootstrap'));
    }

    /**
     * Tests that Plugin::loadAll() will load all plugins in the configured folder wit defaults
     * and overrides for a plugin
     *
     * @return void
     */
    public function testLoadAllWithDefaultsAndOverride()
    {
        Plugin::loadAll([
            ['bootstrap' => true, 'ignoreMissing' => true],
            'TestPlugin' => ['routes' => true],
            'TestPluginFour' => ['bootstrap' => true, 'classBase' => '']
        ]);

        $expected = [
            'Company', 'ParentPlugin', 'PluginJs', 'TestPlugin',
            'TestPluginFour', 'TestPluginTwo', 'TestTheme'
        ];
        $this->assertEquals($expected, Plugin::loaded());
        $app = $this->runApplication();

        $this->assertEquals('loaded js plugin bootstrap', Configure::read('PluginTest.js_plugin.bootstrap'));
        $this->assertEquals('loaded plugin routes', Configure::read('PluginTest.test_plugin.routes'));

        // run pluginBootstrap again to cover new plugin
        $app->pluginBootstrap();
        // loading bootstrap by ParentPlugin\Plugin class
        $this->assertEquals('loaded plugin bootstrap', Configure::read('PluginTest.test_plugin.bootstrap'));
        $this->assertEquals('loaded plugin two bootstrap', Configure::read('PluginTest.test_plugin_two.bootstrap'));
        $this->assertEquals('loaded plugin four bootstrap', Configure::read('PluginTest.test_plugin_four.bootstrap'));

        // TestPluginThree won't get loaded by loadAll() since it's in a sub directory.
        $this->assertNull(Configure::read('PluginTest.test_plugin_three.bootstrap'));
    }
}
