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
 * @since         3.3.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Routing\Middleware;

use Cake\Cache\Cache;
use Cake\Cache\InvalidArgumentException as CacheInvalidArgumentException;
use Cake\Core\Configure;
use Cake\Core\HttpApplicationInterface;
use Cake\Http\ServerRequestFactory;
use Cake\Routing\Exception\FailedRouteCacheException;
use Cake\Routing\Exception\MissingRouteException;
use Cake\Routing\Middleware\RoutingMiddleware;
use Cake\Routing\Route\Route;
use Cake\Routing\RouteBuilder;
use Cake\Routing\RouteCollection;
use Cake\Routing\Router;
use Cake\TestSuite\TestCase;
use Laminas\Diactoros\Response;
use TestApp\Application;
use TestApp\Http\TestRequestHandler;
use TestApp\Middleware\DumbMiddleware;
use TestApp\Middleware\UnserializableMiddleware;

/**
 * Test for RoutingMiddleware
 */
class RoutingMiddlewareTest extends TestCase
{
    protected $log = [];

    /**
     * @var \Cake\Routing\RouteBuilder
     */
    protected $builder;

    /**
     * Setup method
     */
    public function setUp(): void
    {
        parent::setUp();

        Router::reload();
        $this->builder = Router::createRouteBuilder('/');
        $this->builder->connect('/articles', ['controller' => 'Articles', 'action' => 'index']);
        $this->log = [];

        Configure::write('App.base', '');
    }

    public function tearDown(): void
    {
        parent::tearDown();

        Cache::enable();
        if (in_array('_cake_router_', Cache::configured(), true)) {
            Cache::clear('_cake_router_');
        }
        Cache::drop('_cake_router_');
    }

    /**
     * Test redirect responses from redirect routes
     */
    public function testRedirectResponse(): void
    {
        $this->builder->redirect('/testpath', '/pages');
        $request = ServerRequestFactory::fromGlobals(['REQUEST_URI' => '/testpath']);
        $request = $request->withAttribute('base', '/subdir');

        $handler = new TestRequestHandler();
        $middleware = new RoutingMiddleware($this->app());
        $response = $middleware->process($request, $handler);

        $this->assertSame(301, $response->getStatusCode());
        $this->assertSame('http://localhost/subdir/pages', $response->getHeaderLine('Location'));
    }

    /**
     * Test redirects with additional headers
     */
    public function testRedirectResponseWithHeaders(): void
    {
        $this->builder->scope('/', function (RouteBuilder $routes): void {
            $routes->redirect('/testpath', '/pages');
        });
        $request = ServerRequestFactory::fromGlobals(['REQUEST_URI' => '/testpath']);
        $handler = new TestRequestHandler(function ($request) {
            return new Response('php://memory', 200, ['X-testing' => 'Yes']);
        });
        $middleware = new RoutingMiddleware($this->app());
        $response = $middleware->process($request, $handler);

        $this->assertSame(301, $response->getStatusCode());
        $this->assertSame('http://localhost/pages', $response->getHeaderLine('Location'));
    }

    /**
     * Test that Router sets parameters
     */
    public function testRouterSetParams(): void
    {
        $request = ServerRequestFactory::fromGlobals(['REQUEST_URI' => '/articles']);
        $handler = new TestRequestHandler(function ($req) {
            $expected = [
                'controller' => 'Articles',
                'action' => 'index',
                'plugin' => null,
                'pass' => [],
                '_ext' => null,
                '_matchedRoute' => '/articles',
            ];
            $this->assertEquals($expected, $req->getAttribute('params'));

            return new Response();
        });
        $middleware = new RoutingMiddleware($this->app());
        $middleware->process($request, $handler);
    }

    /**
     * Test that Router sets matched routes instance.
     */
    public function testRouterSetRoute(): void
    {
        $request = ServerRequestFactory::fromGlobals(['REQUEST_URI' => '/articles']);
        $handler = new TestRequestHandler(function ($req) {
            $this->assertInstanceOf(Route::class, $req->getAttribute('route'));
            $this->assertSame('/articles', $req->getAttribute('route')->staticPath());

            return new Response();
        });
        $middleware = new RoutingMiddleware($this->app());
        $middleware->process($request, $handler);
    }

    /**
     * Test routing middleware does wipe off existing params keys.
     */
    public function testPreservingExistingParams(): void
    {
        $request = ServerRequestFactory::fromGlobals(['REQUEST_URI' => '/articles']);
        $request = $request->withAttribute('params', ['_csrfToken' => 'i-am-groot']);
        $handler = new TestRequestHandler(function ($req) {
            $expected = [
                'controller' => 'Articles',
                'action' => 'index',
                'plugin' => null,
                'pass' => [],
                '_matchedRoute' => '/articles',
                '_csrfToken' => 'i-am-groot',
            ];
            $this->assertEquals($expected, $req->getAttribute('params'));

            return new Response();
        });
        $middleware = new RoutingMiddleware($this->app());
        $middleware->process($request, $handler);
    }

    /**
     * Test middleware invoking hook method
     */
    public function testRoutesHookInvokedOnApp(): void
    {
        Router::reload();

        $request = ServerRequestFactory::fromGlobals(['REQUEST_URI' => '/app/articles']);
        $handler = new TestRequestHandler(function ($req) {
            $expected = [
                'controller' => 'Articles',
                'action' => 'index',
                'plugin' => null,
                'pass' => [],
                '_ext' => null,
                '_matchedRoute' => '/app/articles',
            ];
            $this->assertEquals($expected, $req->getAttribute('params'));
            $this->assertNotEmpty(Router::routes());
            $this->assertSame('/app/articles', Router::routes()[5]->template);

            return new Response();
        });
        $app = new Application(CONFIG);
        $middleware = new RoutingMiddleware($app);
        $middleware->process($request, $handler);
    }

    /**
     * Test that pluginRoutes hook is called
     */
    public function testRoutesHookCallsPluginHook(): void
    {
        Router::reload();

        $request = ServerRequestFactory::fromGlobals(['REQUEST_URI' => '/app/articles']);
        $app = $this->getMockBuilder(Application::class)
            ->onlyMethods(['pluginRoutes'])
            ->setConstructorArgs([CONFIG])
            ->getMock();
        $app->expects($this->once())
            ->method('pluginRoutes')
            ->with($this->isInstanceOf(RouteBuilder::class));
        $middleware = new RoutingMiddleware($app);
        $middleware->process($request, new TestRequestHandler());
    }

    /**
     * Test that routing is not applied if a controller exists already
     */
    public function testRouterNoopOnController(): void
    {
        $request = ServerRequestFactory::fromGlobals(['REQUEST_URI' => '/articles']);
        $request = $request->withAttribute('params', ['controller' => 'Articles']);
        $handler = new TestRequestHandler(function ($req) {
            $this->assertEquals(['controller' => 'Articles'], $req->getAttribute('params'));

            return new Response();
        });
        $middleware = new RoutingMiddleware($this->app());
        $middleware->process($request, $handler);
    }

    /**
     * Test missing routes not being caught.
     */
    public function testMissingRouteNotCaught(): void
    {
        $this->expectException(MissingRouteException::class);
        $request = ServerRequestFactory::fromGlobals(['REQUEST_URI' => '/missing']);
        $middleware = new RoutingMiddleware($this->app());
        $middleware->process($request, new TestRequestHandler());
    }

    /**
     * Test route with _method being parsed correctly.
     */
    public function testFakedRequestMethodParsed(): void
    {
        $this->builder->connect('/articles-patch', [
            'controller' => 'Articles',
            'action' => 'index',
            '_method' => 'PATCH',
        ]);
        $request = ServerRequestFactory::fromGlobals(
            [
                'REQUEST_METHOD' => 'POST',
                'REQUEST_URI' => '/articles-patch',
            ],
            null,
            ['_method' => 'PATCH']
        );
        $handler = new TestRequestHandler(function ($req) {
            $expected = [
                'controller' => 'Articles',
                'action' => 'index',
                '_method' => 'PATCH',
                'plugin' => null,
                'pass' => [],
                '_matchedRoute' => '/articles-patch',
                '_ext' => null,
            ];
            $this->assertEquals($expected, $req->getAttribute('params'));
            $this->assertSame('PATCH', $req->getMethod());

            return new Response();
        });
        $middleware = new RoutingMiddleware($this->app());
        $middleware->process($request, $handler);
    }

    /**
     * Test invoking simple scoped middleware
     */
    public function testInvokeScopedMiddleware(): void
    {
        $this->builder->scope('/api', function (RouteBuilder $routes): void {
            $routes->registerMiddleware('first', function ($request, $handler) {
                $this->log[] = 'first';

                return $handler->handle($request);
            });
            $routes->registerMiddleware('second', function ($request, $handler) {
                $this->log[] = 'second';

                return $handler->handle($request);
            });
            $routes->registerMiddleware('dumb', DumbMiddleware::class);

            // Connect middleware in reverse to test ordering.
            $routes->applyMiddleware('second', 'first', 'dumb');

            $routes->connect('/ping', ['controller' => 'Pings']);
        });

        $request = ServerRequestFactory::fromGlobals([
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/api/ping',
        ]);
        $app = $this->app(function ($req) {
            $this->log[] = 'last';

            return new Response();
        });
        $middleware = new RoutingMiddleware($app);
        $result = $middleware->process($request, $app);
        $this->assertSame(['second', 'first', 'last'], $this->log);
    }

    /**
     * Test control flow in scoped middleware.
     *
     * Scoped middleware should be able to generate a response
     * and abort lower layers.
     */
    public function testInvokeScopedMiddlewareReturnResponse(): void
    {
        $this->builder->registerMiddleware('first', function ($request, $handler) {
            $this->log[] = 'first';

            return $handler->handle($request);
        });
        $this->builder->registerMiddleware('second', function ($request, $handler) {
            $this->log[] = 'second';

            return new Response();
        });

        $this->builder->applyMiddleware('first');
        $this->builder->connect('/', ['controller' => 'Home']);

        $this->builder->scope('/api', function (RouteBuilder $routes): void {
            $routes->applyMiddleware('second');
            $routes->connect('/articles', ['controller' => 'Articles']);
        });

        $request = ServerRequestFactory::fromGlobals([
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/api/articles',
        ]);
        $handler = new TestRequestHandler(function ($req): void {
            $this->fail('Should not be invoked as first should be ignored.');
        });
        $middleware = new RoutingMiddleware($this->app());
        $result = $middleware->process($request, $handler);

        $this->assertSame(['first', 'second'], $this->log);
    }

    /**
     * Test control flow in scoped middleware.
     */
    public function testInvokeScopedMiddlewareReturnResponseMainScope(): void
    {
        $this->builder->registerMiddleware('first', function ($request, $handler) {
            $this->log[] = 'first';

            return new Response();
        });
        $this->builder->registerMiddleware('second', function ($request, $handler) {
            $this->log[] = 'second';

            return $handler->handle($request);
        });

        $this->builder->applyMiddleware('first');
        $this->builder->connect('/', ['controller' => 'Home']);

        $this->builder->scope('/api', function (RouteBuilder $routes): void {
            $routes->applyMiddleware('second');
            $routes->connect('/articles', ['controller' => 'Articles']);
        });

        $request = ServerRequestFactory::fromGlobals([
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/',
        ]);
        $handler = new TestRequestHandler(function ($req): void {
            $this->fail('Should not be invoked as first should be ignored.');
        });
        $middleware = new RoutingMiddleware($this->app());
        $result = $middleware->process($request, $handler);

        $this->assertSame(['first'], $this->log);
    }

    /**
     * Test invoking middleware & scope separation
     *
     * Re-opening a scope should not inherit middleware declared
     * in the first context.
     *
     * @dataProvider scopedMiddlewareUrlProvider
     */
    public function testInvokeScopedMiddlewareIsolatedScopes(string $url, array $expected): void
    {
        $this->builder->registerMiddleware('first', function ($request, $handler) {
            $this->log[] = 'first';

            return $handler->handle($request);
        });
        $this->builder->registerMiddleware('second', function ($request, $handler) {
            $this->log[] = 'second';

            return $handler->handle($request);
        });

        $this->builder->scope('/api', function (RouteBuilder $routes): void {
            $routes->applyMiddleware('first');
            $routes->connect('/ping', ['controller' => 'Pings']);
        });

        $this->builder->scope('/api', function (RouteBuilder $routes): void {
            $routes->applyMiddleware('second');
            $routes->connect('/version', ['controller' => 'Version']);
        });

        $request = ServerRequestFactory::fromGlobals([
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => $url,
        ]);
        $app = $this->app(function ($req) {
            $this->log[] = 'last';

            return new Response();
        });
        $middleware = new RoutingMiddleware($app);
        $result = $middleware->process($request, $app);
        $this->assertSame($expected, $this->log);
    }

    /**
     * Provider for scope isolation test.
     *
     * @return array
     */
    public function scopedMiddlewareUrlProvider(): array
    {
        return [
            ['/api/ping', ['first', 'last']],
            ['/api/version', ['second', 'last']],
        ];
    }

    /**
     * Test we store route collection in cache.
     */
    public function testCacheRoutes(): void
    {
        Configure::write('Error.ignoredDeprecationPaths', [
            'tests/TestCase/Routing/Middleware/RoutingMiddlewareTest.php',
        ]);
        $cacheConfigName = '_cake_router_';
        Cache::setConfig($cacheConfigName, [
            'engine' => 'File',
            'path' => CACHE,
        ]);
        $request = ServerRequestFactory::fromGlobals(['REQUEST_URI' => '/articles']);
        $handler = new TestRequestHandler(function ($req) use ($cacheConfigName) {
            $routeCollection = Cache::read('routeCollection', $cacheConfigName);
            $this->assertInstanceOf(RouteCollection::class, $routeCollection);

            return new Response();
        });
        $app = new Application(CONFIG);
        $middleware = new RoutingMiddleware($app, $cacheConfigName);
        $middleware->process($request, $handler);
        Configure::delete('Error.ignoredDeprecationPaths');
    }

    /**
     * Test we don't cache routes if cache is disabled.
     */
    public function testCacheNotUsedIfCacheDisabled(): void
    {
        Configure::write('Error.ignoredDeprecationPaths', [
            'tests/TestCase/Routing/Middleware/RoutingMiddlewareTest.php',
        ]);
        $cacheConfigName = '_cake_router_';
        Cache::drop($cacheConfigName);
        Cache::disable();
        Cache::setConfig($cacheConfigName, [
            'engine' => 'File',
            'path' => CACHE,
        ]);
        $request = ServerRequestFactory::fromGlobals(['REQUEST_URI' => '/articles']);
        $handler = new TestRequestHandler(function ($req) use ($cacheConfigName) {
            $routeCollection = Cache::read('routeCollection', $cacheConfigName);
            $this->assertNull($routeCollection);

            return new Response();
        });
        $app = new Application(CONFIG);
        $middleware = new RoutingMiddleware($app, $cacheConfigName);
        $middleware->process($request, $handler);
        Configure::delete('Error.ignoredDeprecationPaths');
    }

    /**
     * Test cache name is used
     */
    public function testCacheConfigNotFound(): void
    {
        Configure::write('Error.ignoredDeprecationPaths', [
            'tests/TestCase/Routing/Middleware/RoutingMiddlewareTest.php',
        ]);
        $this->expectException(CacheInvalidArgumentException::class);
        $this->expectExceptionMessage('The "notfound" cache configuration does not exist.');

        Cache::setConfig('_cake_router_', [
            'engine' => 'File',
            'path' => CACHE,
        ]);
        $request = ServerRequestFactory::fromGlobals(['REQUEST_URI' => '/articles']);
        $app = new Application(CONFIG);
        $middleware = new RoutingMiddleware($app, 'notfound');
        $middleware->process($request, new TestRequestHandler());
        Configure::delete('Error.ignoredDeprecationPaths');
    }

    public function testFailedRouteCache(): void
    {
        Cache::setConfig('_cake_router_', [
            'engine' => 'File',
            'path' => CACHE,
        ]);

        $app = $this->createMock(Application::class);
        $this->expectDeprecation();
        $this->expectDeprecationMessage(
            'Use of routing cache is deprecated and will be removed in 5.0. ' .
            'Upgrade to the new `CakeDC/CachedRouting` plugin. ' .
            'See https://github.com/CakeDC/cakephp-cached-routing'
        );
        new RoutingMiddleware($app, '_cake_router_');
    }

    public function testDeprecatedRouteCache(): void
    {
        Configure::write('Error.ignoredDeprecationPaths', [
            'tests/TestCase/Routing/Middleware/RoutingMiddlewareTest.php',
        ]);
        Cache::setConfig('_cake_router_', [
            'engine' => 'File',
            'path' => CACHE,
        ]);

        $app = $this->createMock(Application::class);
        $app
            ->method('routes')
            ->will($this->returnCallback(function (RouteBuilder $routes) use ($app) {
                return $routes->registerMiddleware('should fail', new UnserializableMiddleware($app));
            }));

        $middleware = new RoutingMiddleware($app, '_cake_router_');
        $request = ServerRequestFactory::fromGlobals(['REQUEST_URI' => '/articles']);

        $this->expectException(FailedRouteCacheException::class);
        $this->expectExceptionMessage('Unable to cache route collection.');
        $middleware->process($request, new TestRequestHandler());
        Configure::delete('Error.ignoredDeprecationPaths');
    }

    /**
     * Create a stub application for testing.
     *
     * @param callable|null $handleCallback Callback for "handle" method.
     */
    protected function app($handleCallback = null): HttpApplicationInterface
    {
        $mock = $this->createMock(Application::class);
        $mock->method('routes')
            ->will($this->returnCallback(function (RouteBuilder $routes) {
                return $routes;
            }));

        if ($handleCallback) {
            $mock->method('handle')
                ->will($this->returnCallback($handleCallback));
        }

        return $mock;
    }
}
