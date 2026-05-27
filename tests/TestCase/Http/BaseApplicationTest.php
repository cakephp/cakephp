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

use Cake\Console\CommandRunner;
use Cake\Console\ConsoleIo;
use Cake\Console\TestSuite\StubConsoleOutput;
use Cake\Core\BasePlugin;
use Cake\Core\Configure;
use Cake\Core\Container;
use Cake\Core\ContainerInterface;
use Cake\Event\Event;
use Cake\Event\EventInterface;
use Cake\Event\EventManagerInterface;
use Cake\Http\BaseApplication;
use Cake\Http\MiddlewareQueue;
use Cake\Http\Response;
use Cake\Http\Server;
use Cake\Http\ServerRequest;
use Cake\Http\ServerRequestFactory;
use Cake\Routing\RouteBuilder;
use Cake\Routing\RouteCollection;
use Cake\TestSuite\TestCase;
use Mockery;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TestApp\Event\DependencyInjectedEventListener;
use TestApp\Event\GreeterService;
use TestPlugin\TestPluginPlugin as TestPlugin;

/**
 * Base application test.
 */
class BaseApplicationTest extends TestCase
{
    /**
     * @var \Cake\Http\BaseApplication
     */
    protected BaseApplication $app;

    /**
     * Setup
     */
    protected function setUp(): void
    {
        parent::setUp();
        static::setAppNamespace();
        $this->app = new class (dirname(__DIR__, 2)) extends BaseApplication
        {
            public function middleware(MiddlewareQueue $middlewareQueue): MiddlewareQueue
            {
                return $middlewareQueue;
            }

            public function events(EventManagerInterface $eventManager): EventManagerInterface
            {
                $eventManager->on('testTrue', function ($event) {
                    return true;
                });

                return $eventManager;
            }
        };
    }

    /**
     * @return void
     */
    protected function tearDown(): void
    {
        parent::tearDown();
        $this->clearPlugins();
        unset($this->app);
    }

    /**
     * Integration test for a simple controller.
     */
    public function testHandle(): void
    {
        $request = ServerRequestFactory::fromGlobals(['REQUEST_URI' => '/cakes']);
        $request = $request->withAttribute('params', [
            'controller' => 'Cakes',
            'action' => 'index',
            'plugin' => null,
            'pass' => [],
        ]);

        $app = $this->app;
        $result = $app->handle($request);
        $this->assertInstanceOf(ResponseInterface::class, $result);
        $this->assertSame('Hello Jane', '' . $result->getBody());
        $container = $app->getContainer();
        $this->assertSame($request, $container->get(ServerRequest::class));
        $this->assertSame($container, $container->get(ContainerInterface::class));
    }

    /**
     * Ensure that plugins with no plugin class can be loaded.
     * This makes adopting the new API easier
     *
     * @deprecated
     */
    public function testAddPluginUnknownClass(): void
    {
        $app = $this->app;
        $app->addPlugin('PluginJs');
        $plugin = $app->getPlugins()->get('PluginJs');
        $this->assertInstanceOf(BasePlugin::class, $plugin);

        $this->assertSame(
            TEST_APP . 'Plugin' . DS . 'PluginJs' . DS,
            $plugin->getPath(),
        );
        $this->assertSame(
            TEST_APP . 'Plugin' . DS . 'PluginJs' . DS . 'config' . DS,
            $plugin->getConfigPath(),
        );
        $this->assertSame(
            TEST_APP . 'Plugin' . DS . 'PluginJs' . DS . 'src' . DS,
            $plugin->getClassPath(),
        );
    }

    public function testAddPluginValidShortName(): void
    {
        $app = $this->app;
        $app->addPlugin('TestPlugin');

        $this->assertCount(1, $app->getPlugins());
        $this->assertTrue($app->getPlugins()->has('TestPlugin'));

        $app->addPlugin('Company/TestPluginThree');
        $this->assertCount(2, $app->getPlugins());
        $this->assertTrue($app->getPlugins()->has('Company/TestPluginThree'));
    }

    public function testAddPluginValid(): void
    {
        $app = $this->app;
        $app->addPlugin(TestPlugin::class);

        $this->assertCount(1, $app->getPlugins());
        $this->assertTrue($app->getPlugins()->has('TestPlugin'));
    }

    public function testPluginMiddleware(): void
    {
        $start = new MiddlewareQueue();
        $app = $this->app;
        $app->addPlugin(TestPlugin::class);

        $after = $app->pluginMiddleware($start);
        $this->assertSame($start, $after);
        $this->assertCount(1, $after);
    }

    public function testPluginRoutes(): void
    {
        $collection = new RouteCollection();
        $routes = new RouteBuilder($collection, '/');
        $app = $this->app;
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

    public function testAppBootstrapPlugins(): void
    {
        $app = new class (dirname(__DIR__, 2) . DS . 'test_app' . DS . 'config_plugins') extends BaseApplication
        {
            public function middleware(MiddlewareQueue $middlewareQueue): MiddlewareQueue
            {
                return $middlewareQueue;
            }
        };
        $app->bootstrap();
        $this->assertTrue($app->getPlugins()->has('TestPlugin'), 'TestPlugin was not loaded via plugins.php');
    }

    public function testPluginBootstrap(): void
    {
        $app = $this->app;
        $app->addPlugin(TestPlugin::class);

        $this->assertFalse(Configure::check('PluginTest.test_plugin.bootstrap'));
        $app->pluginBootstrap();
        $this->assertTrue(Configure::check('PluginTest.test_plugin.bootstrap'));
    }

    /**
     * Test that plugins loaded with addPlugin() can load additional
     * plugins.
     */
    public function testPluginBootstrapRecursivePlugins(): void
    {
        $app = $this->app;
        $app->addPlugin('Named');
        // Remove the deprecated() wrapping once plugin class is added to TestPluginTwo
        $this->deprecated(function () use ($app): void {
            $app->pluginBootstrap();
        });
        $this->assertTrue(
            Configure::check('Named.bootstrap'),
            'Plugin bootstrap should be run',
        );
        $this->assertTrue(
            Configure::check('PluginTest.test_plugin.bootstrap'),
            'Nested plugin should have bootstrap run',
        );
        $this->assertTrue(
            Configure::check('PluginTest.test_plugin_two.bootstrap'),
            'Nested plugin should have bootstrap run',
        );
    }

    /**
     * Tests that loading a nonexistent plugin through addOptionalPlugin() does not throw an exception
     */
    public function testAddOptionalPluginLoadingNonExistentPlugin(): void
    {
        $app = $this->app;
        $pluginCountBefore = count($app->getPlugins());
        $nonExistingPlugin = 'NonExistentPlugin';
        $app->addOptionalPlugin($nonExistingPlugin);
        $pluginCountAfter = count($app->getPlugins());
        $this->assertSame($pluginCountBefore, $pluginCountAfter);
    }

    /**
     * Tests that loading an existing plugin through addOptionalPlugin() works
     */
    public function testAddOptionalPluginLoadingNonExistentPluginValid(): void
    {
        $app = $this->app;
        $app->addOptionalPlugin(TestPlugin::class);

        $this->assertCount(1, $app->getPlugins());
        $this->assertTrue($app->getPlugins()->has('TestPlugin'));
    }

    public function testGetContainer(): void
    {
        $app = $this->app;
        $container = $app->getContainer();

        $this->assertInstanceOf(ContainerInterface::class, $container);
        $this->assertSame($container, $app->getContainer(), 'Should return a reference');
    }

    public function testBuildContainerEvent(): void
    {
        $app = $this->app;
        $called = false;
        $app->getEventManager()->on('Application.buildContainer', function ($event, $container) use (&$called): void {
            $this->assertInstanceOf(BaseApplication::class, $event->getSubject());
            $this->assertInstanceOf(ContainerInterface::class, $container);
            $called = true;
        });

        $container = $app->getContainer();
        $this->assertInstanceOf(ContainerInterface::class, $container);
        $this->assertTrue($called, 'Listener should be called');
    }

    public function testBuildContainerEventReplaceContainer(): void
    {
        $app = $this->app;
        $app->getEventManager()->on('Application.buildContainer', function (EventInterface $event): void {
            $new = new Container();
            $new->add('testing', 'yes');

            $event->setResult($new);
        });

        $container = $app->getContainer();
        $this->assertInstanceOf(ContainerInterface::class, $container);
        $this->assertTrue($container->has('testing'));
    }

    public function testServerBuildMiddlewareEventIsCalledOnBootstrap(): void
    {
        $app = new class (dirname(__DIR__, 2) . '/test_app/config') extends BaseApplication {
            public bool $isCalled = false;

            public function middleware(MiddlewareQueue $middlewareQueue): MiddlewareQueue
            {
                return $middlewareQueue;
            }

            public function events(EventManagerInterface $eventManager): EventManagerInterface
            {
                return $eventManager->on('Server.buildMiddleware', function (EventInterface $event): void {
                    $this->isCalled = true;
                });
            }

            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return new Response(['status' => 200]);
            }
        };
        $server = new Server($app);
        $server->run(new ServerRequest());

        $this->assertTrue($app->isCalled);
    }

    public function testMiddlewareEventIsCaughtByApplicationEventsListener(): void
    {
        $app = new class (dirname(__DIR__, 2) . '/test_app/config') extends BaseApplication {
            public bool $isCalled = false;

            public function middleware(MiddlewareQueue $middlewareQueue): MiddlewareQueue
            {
                return $middlewareQueue->add(function (
                    ServerRequestInterface $request,
                    RequestHandlerInterface $handler,
                ): ResponseInterface {
                    $this->getEventManager()->dispatch(new Event('Test.middlewareEvent'));

                    return $handler->handle($request);
                });
            }

            public function events(EventManagerInterface $eventManager): EventManagerInterface
            {
                return $eventManager->on('Test.middlewareEvent', function (EventInterface $event): void {
                    $this->isCalled = true;
                });
            }

            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return new Response(['status' => 200]);
            }
        };
        $server = new Server($app);
        $server->run(new ServerRequest());

        $this->assertTrue($app->isCalled);
    }

    public function testEventsAreRegistered(): void
    {
        $request = ServerRequestFactory::fromGlobals(['REQUEST_URI' => '/cakes']);
        $request = $request->withAttribute('params', [
            'controller' => 'Cakes',
            'action' => 'index',
            'plugin' => null,
            'pass' => [],
        ]);

        $app = $this->app;
        $app->bootstrap();
        $app->pluginBootstrap();
        $app->handle($request);
        $this->assertNotEmpty($app->getEventManager()->listeners('testTrue'));
    }

    public function testConsoleEventsAreRegistered(): void
    {
        static::setAppNamespace();
        $app = new class (dirname(__DIR__, 2)) extends BaseApplication
        {
            public function routes(RouteBuilder $routes): void
            {
            }

            public function middleware(MiddlewareQueue $middlewareQueue): MiddlewareQueue
            {
                return $middlewareQueue;
            }

            public function events(EventManagerInterface $eventManager): EventManagerInterface
            {
                $eventManager->on('testTrue', function ($event) {
                    return true;
                });

                return $eventManager;
            }
        };
        $output = new StubConsoleOutput();
        $consoleIo = Mockery::mock(ConsoleIo::class, [$output, $output, null, null])
            ->shouldAllowMockingMethod('in')
            ->makePartial();
        $runner = new CommandRunner($app);
        $runner->run(['cake', 'version'], $consoleIo);
        $this->assertNotEmpty($app->getEventManager()->listeners('testTrue'));
    }

    public function testEventListenersWithDependencyInjection(): void
    {
        static::setAppNamespace();

        $app = new class (dirname(__DIR__, 2) . '/test_app/config') extends BaseApplication {
            public function eventListeners(): array
            {
                return [DependencyInjectedEventListener::class];
            }

            public function middleware(MiddlewareQueue $middlewareQueue): MiddlewareQueue
            {
                return $middlewareQueue;
            }

            public function services(ContainerInterface $container): void
            {
                $container->addShared(GreeterService::class);
                $container->addShared(DependencyInjectedEventListener::class)
                    ->addArgument(GreeterService::class);
            }
        };

        $app->bootstrap();
        $app->getEventManager()->dispatch(new Event('Greeting.before', $app, ['name' => 'Jane']));

        $listener = $app->getContainer()->get(DependencyInjectedEventListener::class);
        $this->assertInstanceOf(DependencyInjectedEventListener::class, $listener);
        $this->assertSame('Hello, Jane', $listener->lastGreeting);
    }

    public function testPluginEventsRegisteredForPluginsAddedAfterParentBootstrap(): void
    {
        $app = new class (dirname(__DIR__, 2) . '/test_app/config') extends BaseApplication {
            public bool $pluginEventFired = false;

            public function bootstrap(): void
            {
                parent::bootstrap();

                $this->addPlugin(new class ($this) extends BasePlugin {
                    public function __construct(protected BaseApplication $app)
                    {
                        parent::__construct();
                    }

                    public function events(EventManagerInterface $eventManager): EventManagerInterface
                    {
                        return $eventManager->on('DynamicPlugin.event', function (): void {
                            $this->app->pluginEventFired = true;
                        });
                    }
                });
            }

            public function middleware(MiddlewareQueue $middlewareQueue): MiddlewareQueue
            {
                return $middlewareQueue;
            }
        };

        $app->bootstrap();
        $app->pluginBootstrap();
        $app->getEventManager()->dispatch('DynamicPlugin.event');

        $this->assertTrue($app->pluginEventFired);
    }
}
