<?php
declare(strict_types=1);

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
use Cake\Core\Exception\MissingPluginException;
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
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->clearPlugins();
    }

    /**
     * Reverts the changes done to the environment while testing
     */
    public function tearDown(): void
    {
        parent::tearDown();
        $this->clearPlugins();
    }

    /**
     * Tests loading a plugin with a class
     */
    public function testLoadConcreteClass(): void
    {
        $this->loadPlugins(['TestPlugin']);
        $instance = Plugin::getCollection()->get('TestPlugin');
        $this->assertSame(TestPlugin::class, get_class($instance));
    }

    /**
     * Tests loading a plugin without a class
     */
    public function testLoadDynamicClass(): void
    {
        $this->loadPlugins(['TestPluginTwo']);
        $instance = Plugin::getCollection()->get('TestPluginTwo');
        $this->assertSame(BasePlugin::class, get_class($instance));
    }

    /**
     * Tests that Plugin::path() returns the correct path for the loaded plugins
     */
    public function testPath(): void
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
     */
    public function testPathNotFound(): void
    {
        $this->expectException(MissingPluginException::class);
        Plugin::path('NonExistentPlugin');
    }

    /**
     * Tests that Plugin::classPath() returns the correct path for the loaded plugins
     */
    public function testClassPath(): void
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
     * Tests that Plugin::templatePath() returns the correct path for the loaded plugins
     */
    public function testTemplatePath(): void
    {
        $this->loadPlugins(['TestPlugin', 'TestPluginTwo', 'Company/TestPluginThree']);
        $expected = TEST_APP . 'Plugin' . DS . 'TestPlugin' . DS . 'templates' . DS;
        $this->assertPathEquals(Plugin::templatePath('TestPlugin'), $expected);

        $expected = TEST_APP . 'Plugin' . DS . 'TestPluginTwo' . DS . 'templates' . DS;
        $this->assertPathEquals(Plugin::templatePath('TestPluginTwo'), $expected);

        $expected = TEST_APP . 'Plugin' . DS . 'Company' . DS . 'TestPluginThree' . DS . 'templates' . DS;
        $this->assertPathEquals(Plugin::templatePath('Company/TestPluginThree'), $expected);
    }

    /**
     * Tests that Plugin::classPath() throws an exception on unknown plugin
     */
    public function testClassPathNotFound(): void
    {
        $this->expectException(MissingPluginException::class);
        Plugin::classPath('NonExistentPlugin');
    }
}
