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
 * @since         3.6.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Core;

use Cake\Console\CommandCollection;
use Cake\Core\BasePlugin;
use Cake\Core\Configure;
use Cake\Core\Container;
use Cake\Core\Plugin;
use Cake\Core\PluginApplicationInterface;
use Cake\Http\MiddlewareQueue;
use Cake\Routing\RouteBuilder;
use Cake\Routing\RouteCollection;
use Cake\TestSuite\TestCase;
use Company\TestPluginThree\TestPluginThreePlugin;
use TestPlugin\Plugin as TestPlugin;

/**
 * BasePluginTest class
 */
class BasePluginTest extends TestCase
{
    /**
     * tearDown method
     */
    public function tearDown(): void
    {
        parent::tearDown();
        $this->clearPlugins();
    }

    /**
     * testConfigForRoutesAndBootstrap
     */
    public function testConfigForRoutesAndBootstrap(): void
    {
        $plugin = new BasePlugin([
            'bootstrap' => false,
            'routes' => false,
        ]);

        $this->assertFalse($plugin->isEnabled('routes'));
        $this->assertFalse($plugin->isEnabled('bootstrap'));
        $this->assertTrue($plugin->isEnabled('console'));
        $this->assertTrue($plugin->isEnabled('middleware'));
        $this->assertTrue($plugin->isEnabled('services'));
    }

    public function testGetName(): void
    {
        $plugin = new TestPlugin();
        $this->assertSame('TestPlugin', $plugin->getName());

        $plugin = new TestPluginThreePlugin();
        $this->assertSame('Company/TestPluginThree', $plugin->getName());
    }

    public function testGetNameOption(): void
    {
        $plugin = new TestPlugin(['name' => 'Elephants']);
        $this->assertSame('Elephants', $plugin->getName());
    }

    public function testMiddleware(): void
    {
        $plugin = new BasePlugin();
        $middleware = new MiddlewareQueue();
        $this->assertSame($middleware, $plugin->middleware($middleware));
    }

    public function testConsole(): void
    {
        $plugin = new BasePlugin();
        $commands = new CommandCollection();
        $this->assertSame($commands, $plugin->console($commands));
    }

    public function testServices(): void
    {
        $plugin = new BasePlugin();
        $container = new Container();
        $this->assertNull($plugin->services($container));
    }

    public function testConsoleFind(): void
    {
        $plugin = new TestPlugin();
        Plugin::getCollection()->add($plugin);

        $result = $plugin->console(new CommandCollection());

        $this->assertTrue($result->has('widget'), 'Should have plugin command added');
        $this->assertTrue($result->has('test_plugin.widget'), 'Should have long plugin name');

        $this->assertTrue($result->has('example'), 'Should have plugin shell added');
        $this->assertTrue($result->has('test_plugin.example'), 'Should have long plugin name');
    }

    public function testBootstrap(): void
    {
        $app = $this->createMock(PluginApplicationInterface::class);
        $plugin = new TestPlugin();

        $this->assertFalse(Configure::check('PluginTest.test_plugin.bootstrap'));
        $plugin->bootstrap($app);
        $this->assertTrue(Configure::check('PluginTest.test_plugin.bootstrap'));
    }

    /**
     * No errors should be emitted when a plugin doesn't have a bootstrap file.
     */
    public function testBootstrapSkipMissingFile(): void
    {
        $app = $this->createMock(PluginApplicationInterface::class);
        $plugin = new BasePlugin();
        $plugin->bootstrap($app);
        $this->assertTrue(true);
    }

    /**
     * No errors should be emitted when a plugin doesn't have a routes file.
     */
    public function testRoutesSkipMissingFile(): void
    {
        $plugin = new BasePlugin();
        $routeBuilder = new RouteBuilder(new RouteCollection(), '/');
        $plugin->routes($routeBuilder);
        $this->assertTrue(true);
    }

    public function testConstructorArguments(): void
    {
        $plugin = new BasePlugin([
            'routes' => false,
            'bootstrap' => false,
            'console' => false,
            'middleware' => false,
            'templatePath' => '/plates/',
        ]);
        $this->assertFalse($plugin->isEnabled('routes'));
        $this->assertFalse($plugin->isEnabled('bootstrap'));
        $this->assertFalse($plugin->isEnabled('console'));
        $this->assertFalse($plugin->isEnabled('middleware'));

        $this->assertSame('/plates/', $plugin->getTemplatePath());
    }

    public function testGetPathBaseClass(): void
    {
        $plugin = new BasePlugin();

        $expected = CAKE . 'Core' . DS;
        $this->assertSame($expected, $plugin->getPath());
        $this->assertSame($expected . 'config' . DS, $plugin->getConfigPath());
        $this->assertSame($expected . 'src' . DS, $plugin->getClassPath());
        $this->assertSame($expected . 'templates' . DS, $plugin->getTemplatePath());
    }

    public function testGetPathOptionValue(): void
    {
        $plugin = new BasePlugin(['path' => '/some/path']);
        $expected = '/some/path';
        $this->assertSame($expected, $plugin->getPath());
        $this->assertSame($expected . 'config' . DS, $plugin->getConfigPath());
        $this->assertSame($expected . 'src' . DS, $plugin->getClassPath());
        $this->assertSame($expected . 'templates' . DS, $plugin->getTemplatePath());
    }

    public function testGetPathSubclass(): void
    {
        $plugin = new TestPlugin();
        $expected = TEST_APP . 'Plugin' . DS . 'TestPlugin' . DS;
        $this->assertSame($expected, $plugin->getPath());
        $this->assertSame($expected . 'config' . DS, $plugin->getConfigPath());
        $this->assertSame($expected . 'src' . DS, $plugin->getClassPath());
        $this->assertSame($expected . 'templates' . DS, $plugin->getTemplatePath());
    }
}
