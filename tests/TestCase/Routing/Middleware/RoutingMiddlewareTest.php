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
use Cake\Http\ServerRequestFactory;
use Cake\Routing\Middleware\RoutingMiddleware;
use Cake\Routing\RouteBuilder;
use Cake\Routing\RouteCollection;
use Cake\Routing\Router;
use Cake\TestSuite\TestCase;
use Laminas\Diactoros\Response;
use TestApp\Application;
use TestApp\Http\TestRequestHandler;
use TestApp\Middleware\DumbMiddleware;

/**
 * Test for RoutingMiddleware
 */
class RoutingMiddlewareTest extends TestCase
{
    protected $log = [];

    /**
     * Setup method
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        Router::reload();
        Router::connect('/articles', ['controller' => 'Articles', 'action' => 'index']);
        $this->log = [];

        Configure::write('App.base', '');
    }

    /**
     * Test redirect responses from redirect routes
     *
     * @return void
     */
    public function testRedirectResponse()
    {
        Router::scope('/', function (RouteBuilder $routes) {
            $routes->redirect('/testpath', '/pages');
        });
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
     *
     * @return void
     */
    public function testRedirectResponseWithHeaders()
    {
        Router::scope('/', function (RouteBuilder $routes) {
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
     *
     * @return void
     */
    public function testRouterSetParams()
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
     * Test routing middleware does wipe off existing params keys.
     *
     * @return void
     */
    public function testPreservingExistingParams()
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
     *
     * @return void
     */
    public function testRoutesHookInvokedOnApp()
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
     *
     * @return void
     */
    public function testRoutesHookCallsPluginHook()
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
     *
     * @return void
     */
    public function testRouterNoopOnController()
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
    public function testMissingRouteNotCaught()
    {
        $this->expectException(\Cake\Routing\Exception\MissingRouteException::class);
        $request = ServerRequestFactory::fromGlobals(['REQUEST_URI' => '/missing']);
        $middleware = new RoutingMiddleware($this->app());
        $middleware->process($request, new TestRequestHandler());
    }

    /**
     * Test route with _method being parsed correctly.
     *
     * @return void
     */
    public function testFakedRequestMethodParsed()
    {
        Router::connect('/articles-patch', [
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
     *
     * @return void
     */
    public function testInvokeScopedMiddleware()
    {
        Router::scope('/api', function (RouteBuilder $routes) {
            $routes->registerMiddleware('first', function ($req, $res, $next) {
                $this->log[] = 'first';

                return $next($req, $res);
            });
            $routes->registerMiddleware('second', function ($req, $res, $next) {
                $this->log[] = 'second';

                return $next($req, $res);
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
     *
     * @return void
     */
    public function testInvokeScopedMiddlewareReturnResponse()
    {
        Router::scope('/', function (RouteBuilder $routes) {
            $routes->registerMiddleware('first', function ($req, $res, $next) {
                $this->log[] = 'first';

                return $next($req, $res);
            });
            $routes->registerMiddleware('second', function ($req, $res, $next) {
                $this->log[] = 'second';

                return $res;
            });

            $routes->applyMiddleware('first');
            $routes->connect('/', ['controller' => 'Home']);

            $routes->scope('/api', function (RouteBuilder $routes) {
                $routes->applyMiddleware('second');
                $routes->connect('/articles', ['controller' => 'Articles']);
            });
        });

        $request = ServerRequestFactory::fromGlobals([
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/api/articles',
        ]);
        $handler = new TestRequestHandler(function ($req) {
            $this->fail('Should not be invoked as first should be ignored.');
        });
        $middleware = new RoutingMiddleware($this->app());
        $result = $middleware->process($request, $handler);

        $this->assertSame(['first', 'second'], $this->log);
    }

    /**
     * Test control flow in scoped middleware.
     *
     * @return void
     */
    public function testInvokeScopedMiddlewareReturnResponseMainScope()
    {
        Router::scope('/', function (RouteBuilder $routes) {
            $routes->registerMiddleware('first', function ($req, $res, $next) {
                $this->log[] = 'first';

                return $res;
            });
            $routes->registerMiddleware('second', function ($req, $res, $next) {
                $this->log[] = 'second';

                return $next($req, $res);
            });

            $routes->applyMiddleware('first');
            $routes->connect('/', ['controller' => 'Home']);

            $routes->scope('/api', function (RouteBuilder $routes) {
                $routes->applyMiddleware('second');
                $routes->connect('/articles', ['controller' => 'Articles']);
            });
        });

        $request = ServerRequestFactory::fromGlobals([
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/',
        ]);
        $handler = new TestRequestHandler(function ($req) {
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
     * @return void
     */
    public function testInvokeScopedMiddlewareIsolatedScopes($url, $expected)
    {
        Router::scope('/', function (RouteBuilder $routes) {
            $routes->registerMiddleware('first', function ($req, $res, $next) {
                $this->log[] = 'first';

                return $next($req, $res);
            });
            $routes->registerMiddleware('second', function ($req, $res, $next) {
                $this->log[] = 'second';

                return $next($req, $res);
            });

            $routes->scope('/api', function (RouteBuilder $routes) {
                $routes->applyMiddleware('first');
                $routes->connect('/ping', ['controller' => 'Pings']);
            });

            $routes->scope('/api', function (RouteBuilder $routes) {
                $routes->applyMiddleware('second');
                $routes->connect('/version', ['controller' => 'Version']);
            });
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
    public function scopedMiddlewareUrlProvider()
    {
        return [
            ['/api/ping', ['first', 'last']],
            ['/api/version', ['second', 'last']],
        ];
    }

    /**
     * Test we store route collection in cache.
     *
     * @return void
     */
    public function testCacheRoutes()
    {
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

        Cache::clear($cacheConfigName);
        Cache::drop($cacheConfigName);
    }

    /**
     * Test we don't cache routes if cache is disabled.
     *
     * @return void
     */
    public function testCacheNotUsedIfCacheDisabled()
    {
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

        Cache::clear($cacheConfigName);
        Cache::drop($cacheConfigName);
        Cache::enable();
    }

    /**
     * Test cache name is used
     *
     * @return void
     */
    public function testCacheConfigNotFound()
    {
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

        Cache::drop('_cake_router_');
    }

    /**
     * Create a stub application for testing.
     *
     * @param callable|null $handleCallback Callback for "handle" method.
     * @return \Cake\Core\HttpApplicationInterface
     */
    protected function app($handleCallback = null)
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
