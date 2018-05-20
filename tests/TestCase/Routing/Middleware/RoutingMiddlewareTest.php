<?php
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
use Cake\Routing\Middleware\RoutingMiddleware;
use Cake\Routing\RouteBuilder;
use Cake\Routing\RouteCollection;
use Cake\Routing\Router;
use Cake\TestSuite\TestCase;
use TestApp\Application;
use TestApp\Middleware\DumbMiddleware;
use Zend\Diactoros\Response;
use Zend\Diactoros\ServerRequestFactory;

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
    public function setUp()
    {
        parent::setUp();
        Router::reload();
        Router::connect('/articles', ['controller' => 'Articles', 'action' => 'index']);
        $this->log = [];
    }

    /**
     * Test redirect responses from redirect routes
     *
     * @return void
     */
    public function testRedirectResponse()
    {
        Router::scope('/', function ($routes) {
            $routes->redirect('/testpath', '/pages');
        });
        $request = ServerRequestFactory::fromGlobals(['REQUEST_URI' => '/testpath']);
        $request = $request->withAttribute('base', '/subdir');

        $response = new Response();
        $next = function ($req, $res) {
        };
        $middleware = new RoutingMiddleware();
        $response = $middleware($request, $response, $next);

        $this->assertEquals(301, $response->getStatusCode());
        $this->assertEquals('http://localhost/subdir/pages', $response->getHeaderLine('Location'));
    }

    /**
     * Test redirects with additional headers
     *
     * @return void
     */
    public function testRedirectResponseWithHeaders()
    {
        Router::scope('/', function ($routes) {
            $routes->redirect('/testpath', '/pages');
        });
        $request = ServerRequestFactory::fromGlobals(['REQUEST_URI' => '/testpath']);
        $response = new Response('php://memory', 200, ['X-testing' => 'Yes']);
        $next = function ($req, $res) {
        };
        $middleware = new RoutingMiddleware();
        $response = $middleware($request, $response, $next);

        $this->assertEquals(301, $response->getStatusCode());
        $this->assertEquals('http://localhost/pages', $response->getHeaderLine('Location'));
        $this->assertEquals('Yes', $response->getHeaderLine('X-testing'));
    }

    /**
     * Test that Router sets parameters
     *
     * @return void
     */
    public function testRouterSetParams()
    {
        $request = ServerRequestFactory::fromGlobals(['REQUEST_URI' => '/articles']);
        $response = new Response();
        $next = function ($req, $res) {
            $expected = [
                'controller' => 'Articles',
                'action' => 'index',
                'plugin' => null,
                'pass' => [],
                '_matchedRoute' => '/articles'
            ];
            $this->assertEquals($expected, $req->getAttribute('params'));
        };
        $middleware = new RoutingMiddleware();
        $middleware($request, $response, $next);
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
        $response = new Response();
        $next = function ($req, $res) {
            $expected = [
                'controller' => 'Articles',
                'action' => 'index',
                'plugin' => null,
                'pass' => [],
                '_matchedRoute' => '/articles',
                '_csrfToken' => 'i-am-groot'
            ];
            $this->assertEquals($expected, $req->getAttribute('params'));
        };
        $middleware = new RoutingMiddleware();
        $middleware($request, $response, $next);
    }

    /**
     * Test middleware invoking hook method
     *
     * @return void
     */
    public function testRoutesHookInvokedOnApp()
    {
        Router::reload();
        $this->assertFalse(Router::$initialized, 'Router precondition failed');

        $request = ServerRequestFactory::fromGlobals(['REQUEST_URI' => '/app/articles']);
        $response = new Response();
        $next = function ($req, $res) {
            $expected = [
                'controller' => 'Articles',
                'action' => 'index',
                'plugin' => null,
                'pass' => [],
                '_matchedRoute' => '/app/articles'
            ];
            $this->assertEquals($expected, $req->getAttribute('params'));
            $this->assertTrue(Router::$initialized, 'Router state should indicate routes loaded');
            $this->assertNotEmpty(Router::routes());
            $this->assertEquals('/app/articles', Router::routes()[0]->template);
        };
        $app = new Application(CONFIG);
        $middleware = new RoutingMiddleware($app);
        $middleware($request, $response, $next);
    }

    /**
     * Test that pluginRoutes hook is called
     *
     * @return void
     */
    public function testRoutesHookCallsPluginHook()
    {
        Router::reload();
        $this->assertFalse(Router::$initialized, 'Router precondition failed');

        $request = ServerRequestFactory::fromGlobals(['REQUEST_URI' => '/app/articles']);
        $response = new Response();
        $next = function ($req, $res) {
            return $res;
        };
        $app = $this->getMockBuilder(Application::class)
            ->setMethods(['pluginRoutes'])
            ->setConstructorArgs([CONFIG])
            ->getMock();
        $app->expects($this->once())
            ->method('pluginRoutes')
            ->with($this->isInstanceOf(RouteBuilder::class));
        $middleware = new RoutingMiddleware($app);
        $middleware($request, $response, $next);
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
        $response = new Response();
        $next = function ($req, $res) {
            $this->assertEquals(['controller' => 'Articles'], $req->getAttribute('params'));
        };
        $middleware = new RoutingMiddleware();
        $middleware($request, $response, $next);
    }

    /**
     * Test missing routes not being caught.
     *
     */
    public function testMissingRouteNotCaught()
    {
        $this->expectException(\Cake\Routing\Exception\MissingRouteException::class);
        $request = ServerRequestFactory::fromGlobals(['REQUEST_URI' => '/missing']);
        $response = new Response();
        $next = function ($req, $res) {
        };
        $middleware = new RoutingMiddleware();
        $middleware($request, $response, $next);
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
            '_method' => 'PATCH'
        ]);
        $request = ServerRequestFactory::fromGlobals(
            [
                'REQUEST_METHOD' => 'POST',
                'REQUEST_URI' => '/articles-patch'
            ],
            null,
            ['_method' => 'PATCH']
        );
        $response = new Response();
        $next = function ($req, $res) {
            $expected = [
                'controller' => 'Articles',
                'action' => 'index',
                '_method' => 'PATCH',
                'plugin' => null,
                'pass' => [],
                '_matchedRoute' => '/articles-patch'
            ];
            $this->assertEquals($expected, $req->getAttribute('params'));
            $this->assertEquals('PATCH', $req->getMethod());
        };
        $middleware = new RoutingMiddleware();
        $middleware($request, $response, $next);
    }

    /**
     * Test invoking simple scoped middleware
     *
     * @return void
     */
    public function testInvokeScopedMiddleware()
    {
        Router::scope('/api', function ($routes) {
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
            'REQUEST_URI' => '/api/ping'
        ]);
        $response = new Response();
        $next = function ($req, $res) {
            $this->log[] = 'last';

            return $res;
        };
        $middleware = new RoutingMiddleware();
        $result = $middleware($request, $response, $next);
        $this->assertSame($response, $result, 'Should return result');
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
        Router::scope('/', function ($routes) {
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

            $routes->scope('/api', function ($routes) {
                $routes->applyMiddleware('second');
                $routes->connect('/articles', ['controller' => 'Articles']);
            });
        });

        $request = ServerRequestFactory::fromGlobals([
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/api/articles'
        ]);
        $response = new Response();
        $next = function ($req, $res) {
            $this->fail('Should not be invoked as first should be ignored.');
        };
        $middleware = new RoutingMiddleware();
        $result = $middleware($request, $response, $next);

        $this->assertSame($response, $result, 'Should return result');
        $this->assertSame(['first', 'second'], $this->log);
    }

    /**
     * Test control flow in scoped middleware.
     *
     * @return void
     */
    public function testInvokeScopedMiddlewareReturnResponseMainScope()
    {
        Router::scope('/', function ($routes) {
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

            $routes->scope('/api', function ($routes) {
                $routes->applyMiddleware('second');
                $routes->connect('/articles', ['controller' => 'Articles']);
            });
        });

        $request = ServerRequestFactory::fromGlobals([
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/'
        ]);
        $response = new Response();
        $next = function ($req, $res) {
            $this->fail('Should not be invoked as second should be ignored.');
        };
        $middleware = new RoutingMiddleware();
        $result = $middleware($request, $response, $next);

        $this->assertSame($response, $result, 'Should return result');
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
        Router::scope('/', function ($routes) {
            $routes->registerMiddleware('first', function ($req, $res, $next) {
                $this->log[] = 'first';

                return $next($req, $res);
            });
            $routes->registerMiddleware('second', function ($req, $res, $next) {
                $this->log[] = 'second';

                return $next($req, $res);
            });

            $routes->scope('/api', function ($routes) {
                $routes->applyMiddleware('first');
                $routes->connect('/ping', ['controller' => 'Pings']);
            });

            $routes->scope('/api', function ($routes) {
                $routes->applyMiddleware('second');
                $routes->connect('/version', ['controller' => 'Version']);
            });
        });

        $request = ServerRequestFactory::fromGlobals([
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => $url
        ]);
        $response = new Response();
        $next = function ($req, $res) {
            $this->log[] = 'last';

            return $res;
        };
        $middleware = new RoutingMiddleware();
        $result = $middleware($request, $response, $next);
        $this->assertSame($response, $result, 'Should return result');
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
            'path' => TMP,
        ]);
        Router::$initialized = false;
        $request = ServerRequestFactory::fromGlobals(['REQUEST_URI' => '/articles']);
        $response = new Response();
        $next = function ($req, $res) use ($cacheConfigName) {
            $routeCollection = Cache::read('routeCollection', $cacheConfigName);
            $this->assertInstanceOf(RouteCollection::class, $routeCollection);

            return $res;
        };
        $app = new Application(CONFIG);
        $middleware = new RoutingMiddleware($app, $cacheConfigName);
        $middleware($request, $response, $next);

        Cache::clear(false, $cacheConfigName);
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
        Cache::disable();
        Cache::setConfig($cacheConfigName, [
            'engine' => 'File',
            'path' => TMP,
        ]);
        Router::$initialized = false;
        $request = ServerRequestFactory::fromGlobals(['REQUEST_URI' => '/articles']);
        $response = new Response();
        $next = function ($req, $res) use ($cacheConfigName) {
            $routeCollection = Cache::read('routeCollection', $cacheConfigName);
            $this->assertFalse($routeCollection);

            return $res;
        };
        $app = new Application(CONFIG);
        $middleware = new RoutingMiddleware($app, $cacheConfigName);
        $middleware($request, $response, $next);

        Cache::clear(false, $cacheConfigName);
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
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The "notfound" cache configuration does not exist');

        Cache::setConfig('_cake_router_', [
            'engine' => 'File',
            'path' => TMP,
        ]);
        Router::$initialized = false;
        $request = ServerRequestFactory::fromGlobals(['REQUEST_URI' => '/articles']);
        $response = new Response();
        $next = function ($req, $res) {
            return $res;
        };
        $app = new Application(CONFIG);
        $middleware = new RoutingMiddleware($app, 'notfound');
        $middleware($request, $response, $next);

        Cache::drop('_cake_router_');
    }
}
