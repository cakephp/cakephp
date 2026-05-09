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
use Cake\Console\CommandRunner;
use Cake\Console\ConsoleIo;
use Cake\Console\TestSuite\StubConsoleOutput;
use Cake\Core\BasePlugin;
use Cake\Core\Configure;
use Cake\Core\Container;
use Cake\Core\Plugin;
use Cake\Core\PluginApplicationInterface;
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
use Company\TestPluginThree\TestPluginThreePlugin;
use Mockery;
use PHPUnit\Framework\Attributes\DoesNotPerformAssertions;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TestPlugin\TestPluginPlugin as TestPlugin;

/**
 * BasePluginTest class
 */
class BasePluginTest extends TestCase
{
    /**
     * tearDown method
     */
    protected function tearDown(): void
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

    #[DoesNotPerformAssertions]
    public function testServices(): void
    {
        $plugin = new BasePlugin();
        $container = new Container();
        $plugin->services($container);
    }

    public function testConsoleFind(): void
    {
        $plugin = new TestPlugin();
        Plugin::getCollection()->add($plugin);

        $result = $plugin->console(new CommandCollection());

        $this->assertTrue($result->has('sample'), 'Should have plugin command added');
        $this->assertTrue($result->has('test_plugin.sample'), 'Should have long plugin name');

        $this->assertTrue($result->has('example'), 'Should have plugin shell added');
        $this->assertTrue($result->has('test_plugin.example'), 'Should have long plugin name');
    }

    public function testBootstrap(): void
    {
        $app = new class implements PluginApplicationInterface {
            use BasePluginApplicationTrait;
        };
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
        $app = new class implements PluginApplicationInterface {
            use BasePluginApplicationTrait;
        };
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

    public function testServerBuildMiddlewareEventIsCalledOnBootstrap(): void
    {
        $basePlugin = new class extends BasePlugin
        {
            public bool $isCalled = false;

            public function events(EventManagerInterface $eventManager): EventManagerInterface
            {
                return $eventManager->on('Server.buildMiddleware', function (EventInterface $event): void {
                    $this->isCalled = true;
                });
            }
        };

        $app = new class (dirname(__DIR__, 2) . '/test_app/config') extends BaseApplication
        {
            public function middleware(MiddlewareQueue $middlewareQueue): MiddlewareQueue
            {
                return $middlewareQueue;
            }

            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return new Response(['status' => 200]);
            }
        };
        $app->addPlugin($basePlugin);

        $server = new Server($app);
        $server->run(new ServerRequest());
        $this->assertTrue($basePlugin->isCalled);
    }

    public function testMiddlewareEventIsCaughtByPluginEventsListener(): void
    {
        $basePlugin = new class extends BasePlugin
        {
            public bool $isCalled = false;

            public function events(EventManagerInterface $eventManager): EventManagerInterface
            {
                return $eventManager->on('Test.middlewareEvent', function (EventInterface $event): void {
                    $this->isCalled = true;
                });
            }
        };

        $app = new class (dirname(__DIR__, 2) . '/test_app/config') extends BaseApplication
        {
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

            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return new Response(['status' => 200]);
            }
        };
        $app->addPlugin($basePlugin);

        $server = new Server($app);
        $server->run(new ServerRequest());

        $this->assertTrue($basePlugin->isCalled);
    }

    public function testEventsAreRegistered(): void
    {
        static::setAppNamespace();
        $request = ServerRequestFactory::fromGlobals(['REQUEST_URI' => '/cakes']);
        $request = $request->withAttribute('params', [
            'controller' => 'Cakes',
            'action' => 'index',
            'plugin' => null,
            'pass' => [],
        ]);

        $basePlugin = new class extends BasePlugin
        {
            public function events(EventManagerInterface $eventManager): EventManagerInterface
            {
                $eventManager->on('testTrue', function ($event) {
                    return true;
                });

                return $eventManager;
            }
        };

        $app = new class (dirname(__DIR__, 2)) extends BaseApplication
        {
            public function middleware(MiddlewareQueue $middlewareQueue): MiddlewareQueue
            {
                return $middlewareQueue;
            }
        };
        $app = $app->addPlugin($basePlugin);
        $app->bootstrap();
        $app->handle($request);
        $this->assertNotEmpty($app->getEventManager()->listeners('testTrue'));
    }

    public function testConsoleEventsAreRegistered(): void
    {
        static::setAppNamespace();
        $basePlugin = new class extends BasePlugin
        {
            public function events(EventManagerInterface $eventManager): EventManagerInterface
            {
                $eventManager->on('testTrue', function ($event) {
                    return true;
                });

                return $eventManager;
            }
        };

        $app = new class (dirname(__DIR__, 2)) extends BaseApplication
        {
            public function routes(RouteBuilder $routes): void
            {
            }

            public function middleware(MiddlewareQueue $middlewareQueue): MiddlewareQueue
            {
                return $middlewareQueue;
            }
        };
        $app = $app->addPlugin($basePlugin);
        $output = new StubConsoleOutput();
        $consoleIo = Mockery::mock(ConsoleIo::class, [$output, $output, null, null])
            ->shouldAllowMockingMethod('in')
            ->makePartial();
        $runner = new CommandRunner($app);
        $runner->run(['cake', 'version'], $consoleIo);
        $this->assertNotEmpty($app->getEventManager()->listeners('testTrue'));
    }
}
