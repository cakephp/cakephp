<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         3.5.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Http;

use Cake\Core\BasePlugin;
use Cake\Core\Configure;
use Cake\Core\Container;
use Cake\Core\ContainerInterface;
use Cake\Http\BaseApplication;
use Cake\Http\MiddlewareQueue;
use Cake\Http\ServerRequestFactory;
use Cake\Routing\RouteBuilder;
use Cake\Routing\RouteCollection;
use Cake\TestSuite\TestCase;
use Psr\Http\Message\ResponseInterface;
use TestPlugin\Plugin as TestPlugin;

/**
 * Base application test.
 *
 * @coversDefaultClass \Cake\Http\BaseApplication
 */
class BaseApplicationTest extends TestCase
{
    /**
     * @var string
     */
    protected $path;

    /**
     * Setup
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        static::setAppNamespace();
        $this->path = dirname(dirname(__DIR__));
    }

    public function tearDown(): void
    {
        parent::tearDown();
        $this->clearPlugins();
    }

    /**
     * Integration test for a simple controller.
     *
     * @return void
     */
    public function testHandle()
    {
        $request = ServerRequestFactory::fromGlobals(['REQUEST_URI' => '/cakes']);
        $request = $request->withAttribute('params', [
            'controller' => 'Cakes',
            'action' => 'index',
            'plugin' => null,
            'pass' => [],
        ]);

        $app = $this->getMockForAbstractClass(BaseApplication::class, [$this->path]);
        $result = $app->handle($request);
        $this->assertInstanceOf(ResponseInterface::class, $result);
        $this->assertSame('Hello Jane', '' . $result->getBody());
    }

    /**
     * Ensure that plugins with no plugin class can be loaded.
     * This makes adopting the new API easier
     */
    public function testAddPluginUnknownClass()
    {
        $app = $this->getMockForAbstractClass(BaseApplication::class, [$this->path]);
        $app->addPlugin('PluginJs');
        $plugin = $app->getPlugins()->get('PluginJs');
        $this->assertInstanceOf(BasePlugin::class, $plugin);

        $this->assertSame(
            TEST_APP . 'Plugin' . DS . 'PluginJs' . DS,
            $plugin->getPath()
        );
        $this->assertSame(
            TEST_APP . 'Plugin' . DS . 'PluginJs' . DS . 'config' . DS,
            $plugin->getConfigPath()
        );
        $this->assertSame(
            TEST_APP . 'Plugin' . DS . 'PluginJs' . DS . 'src' . DS,
            $plugin->getClassPath()
        );
    }

    public function testAddPluginValidShortName()
    {
        $app = $this->getMockForAbstractClass(BaseApplication::class, [$this->path]);
        $app->addPlugin('TestPlugin');

        $this->assertCount(1, $app->getPlugins());
        $this->assertTrue($app->getPlugins()->has('TestPlugin'));

        $app->addPlugin('Company/TestPluginThree');
        $this->assertCount(2, $app->getPlugins());
        $this->assertTrue($app->getPlugins()->has('Company/TestPluginThree'));
    }

    public function testAddPluginValid()
    {
        $app = $this->getMockForAbstractClass(BaseApplication::class, [$this->path]);
        $app->addPlugin(TestPlugin::class);

        $this->assertCount(1, $app->getPlugins());
        $this->assertTrue($app->getPlugins()->has('TestPlugin'));
    }

    public function testPluginMiddleware()
    {
        $start = new MiddlewareQueue();
        $app = $this->getMockForAbstractClass(
            BaseApplication::class,
            [$this->path]
        );
        $app->addPlugin(TestPlugin::class);

        $after = $app->pluginMiddleware($start);
        $this->assertSame($start, $after);
        $this->assertCount(1, $after);
    }

    public function testPluginRoutes()
    {
        $collection = new RouteCollection();
        $routes = new RouteBuilder($collection, '/');
        $app = $this->getMockForAbstractClass(
            BaseApplication::class,
            [$this->path]
        );
        $app->addPlugin(TestPlugin::class);

        $result = $app->pluginRoutes($routes);
        $this->assertSame($routes, $result);
        $url = [
            'plugin' => 'TestPlugin',
            'controller' => 'TestPlugin',
            'action' => 'index',
            '_method' => 'GET',
        ];
        $this->assertNotEmpty($collection->match($url, []));
    }

    public function testPluginBootstrap()
    {
        $app = $this->getMockForAbstractClass(
            BaseApplication::class,
            [$this->path]
        );
        $app->addPlugin(TestPlugin::class);

        $this->assertFalse(Configure::check('PluginTest.test_plugin.bootstrap'));
        $app->pluginBootstrap();
        $this->assertTrue(Configure::check('PluginTest.test_plugin.bootstrap'));
    }

    /**
     * Test that plugins loaded with addPlugin() can load additional
     * plugins.
     *
     * @return void
     */
    public function testPluginBootstrapRecursivePlugins()
    {
        $app = $this->getMockForAbstractClass(
            BaseApplication::class,
            [$this->path]
        );
        $app->addPlugin('ParentPlugin');
        $app->pluginBootstrap();
        $this->assertTrue(
            Configure::check('ParentPlugin.bootstrap'),
            'Plugin bootstrap should be run'
        );
        $this->assertTrue(
            Configure::check('PluginTest.test_plugin.bootstrap'),
            'Nested plugin should have bootstrap run'
        );
        $this->assertTrue(
            Configure::check('PluginTest.test_plugin_two.bootstrap'),
            'Nested plugin should have bootstrap run'
        );
    }

    /**
     * Tests that loading a nonexistent plugin through addOptionalPlugin() does not throw an exception
     *
     * @return void
     * @covers ::addOptionalPlugin
     */
    public function testAddOptionalPluginLoadingNonExistentPlugin()
    {
        $app = $this->getMockForAbstractClass(BaseApplication::class, [$this->path]);
        $pluginCountBefore = count($app->getPlugins());
        $nonExistingPlugin = 'NonExistentPlugin';
        $app->addOptionalPlugin($nonExistingPlugin);
        $pluginCountAfter = count($app->getPlugins());
        $this->assertSame($pluginCountBefore, $pluginCountAfter);
    }

    /**
     * Tests that loading an existing plugin through addOptionalPlugin() works
     *
     * @return void
     * @covers ::addOptionalPlugin
     */
    public function testAddOptionalPluginLoadingNonExistentPluginValid()
    {
        $app = $this->getMockForAbstractClass(BaseApplication::class, [$this->path]);
        $app->addOptionalPlugin(TestPlugin::class);

        $this->assertCount(1, $app->getPlugins());
        $this->assertTrue($app->getPlugins()->has('TestPlugin'));
    }

    public function testGetContainer()
    {
        $app = $this->getMockForAbstractClass(BaseApplication::class, [$this->path]);
        $container = $app->getContainer();

        $this->assertInstanceOf(ContainerInterface::class, $container);
        $this->assertSame($container, $app->getContainer(), 'Should return a reference');
    }

    public function testBuildContainerEvent()
    {
        $app = $this->getMockForAbstractClass(BaseApplication::class, [$this->path]);
        $called = false;
        $app->getEventManager()->on('Application.buildContainer', function ($event, $container) use (&$called) {
            $this->assertInstanceOf(BaseApplication::class, $event->getSubject());
            $this->assertInstanceOf(ContainerInterface::class, $container);
            $called = true;
        });

        $container = $app->getContainer();
        $this->assertInstanceOf(ContainerInterface::class, $container);
        $this->assertTrue($called, 'Listener should be called');
    }

    public function testBuildContainerEventReplaceContainer()
    {
        $app = $this->getMockForAbstractClass(BaseApplication::class, [$this->path]);
        $app->getEventManager()->on('Application.buildContainer', function () {
            $new = new Container();
            $new->add('testing', 'yes');

            return $new;
        });

        $container = $app->getContainer();
        $this->assertInstanceOf(ContainerInterface::class, $container);
        $this->assertTrue($container->has('testing'));
    }
}
