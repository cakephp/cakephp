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
 * @since         3.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Routing;

use Cake\Http\ServerRequest;
use Cake\Routing\Exception\DuplicateNamedRouteException;
use Cake\Routing\Exception\MissingRouteException;
use Cake\Routing\Route\Route;
use Cake\Routing\RouteBuilder;
use Cake\Routing\RouteCollection;
use Cake\TestSuite\TestCase;
use InvalidArgumentException;

class RouteCollectionTest extends TestCase
{
    /**
     * @var \Cake\Routing\RouteCollection
     */
    protected $collection;

    /**
     * Setup method
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->collection = new RouteCollection();
    }

    /**
     * Test parse() throws an error on unknown routes.
     */
    public function testParseMissingRoute(): void
    {
        $this->expectException(MissingRouteException::class);
        $this->expectExceptionMessage('A route matching `/` could not be found');
        $routes = new RouteBuilder($this->collection, '/b', ['key' => 'value']);
        $routes->connect('/', ['controller' => 'Articles']);
        $routes->connect('/{id}', ['controller' => 'Articles', 'action' => 'view']);

        $result = $this->collection->parseRequest(new ServerRequest(['url' => '/']));
        $this->assertEquals([], $result, 'Should not match, missing /b');
    }

    /**
     * Test parse() throws an error on known routes called with unknown methods.
     */
    public function testParseMissingRouteMethod(): void
    {
        $this->expectException(MissingRouteException::class);
        $this->expectExceptionMessage('A route matching `/b` could not be found');
        $routes = new RouteBuilder($this->collection, '/b', ['key' => 'value']);
        $routes->connect('/', ['controller' => 'Articles', '_method' => ['GET']]);

        $result = $this->collection->parseRequest(new ServerRequest([
            'url' => '/b',
            'environment' => [
                'REQUEST_METHOD' => 'GET',
            ],
        ]));
        $this->assertNotEmpty($result, 'Route should be found');
        $result = $this->collection->parseRequest(new ServerRequest([
            'url' => '/b',
            'environment' => [
                'REQUEST_METHOD' => 'POST',
            ],
        ]));
        $this->assertEquals([], $result, 'Should not match with missing method');
    }

    /**
     * Test parsing routes.
     */
    public function testParse(): void
    {
        $routes = new RouteBuilder($this->collection, '/b', ['key' => 'value']);
        $routes->connect('/', ['controller' => 'Articles']);
        $routes->connect('/{id}', ['controller' => 'Articles', 'action' => 'view']);
        $routes->connect('/media/search/*', ['controller' => 'Media', 'action' => 'search']);

        $result = $this->collection->parseRequest(new ServerRequest(['url' => '/b/']));
        unset($result['_route']);
        $expected = [
            'controller' => 'Articles',
            'action' => 'index',
            'pass' => [],
            'plugin' => null,
            'key' => 'value',
            '_matchedRoute' => '/b',
        ];
        $this->assertEquals($expected, $result);

        $result = $this->collection->parseRequest(new ServerRequest(['url' => '/b/the-thing?one=two']));
        unset($result['_route']);
        $expected = [
            'controller' => 'Articles',
            'action' => 'view',
            'id' => 'the-thing',
            'pass' => [],
            'plugin' => null,
            'key' => 'value',
            '?' => ['one' => 'two'],
            '_matchedRoute' => '/b/{id}',
        ];
        $this->assertEquals($expected, $result);

        $result = $this->collection->parseRequest(new ServerRequest(['url' => '/b/media/search']));
        unset($result['_route']);
        $expected = [
            'key' => 'value',
            'pass' => [],
            'plugin' => null,
            'controller' => 'Media',
            'action' => 'search',
            '_matchedRoute' => '/b/media/search/*',
        ];
        $this->assertEquals($expected, $result);

        $result = $this->collection->parseRequest(new ServerRequest(['url' => '/b/media/search/thing']));
        unset($result['_route']);
        $expected = [
            'key' => 'value',
            'pass' => ['thing'],
            'plugin' => null,
            'controller' => 'Media',
            'action' => 'search',
            '_matchedRoute' => '/b/media/search/*',
        ];
        $this->assertEquals($expected, $result);
    }

    /**
     * Test parse() handling query strings.
     */
    public function testParseQueryString(): void
    {
        $routes = new RouteBuilder($this->collection, '/');
        $routes->connect('/{id}', ['controller' => 'Articles', 'action' => 'view']);
        $routes->connect('/media/search/*', ['controller' => 'Media', 'action' => 'search']);

        $result = $this->collection->parseRequest(new ServerRequest(['url' => '/media/search/php?one=two']));
        unset($result['_route']);
        $expected = [
            'controller' => 'Media',
            'action' => 'search',
            'pass' => ['php'],
            'plugin' => null,
            '_matchedRoute' => '/media/search/*',
            '?' => ['one' => 'two'],
        ];
        $this->assertEquals($expected, $result);

        $result = $this->collection->parseRequest(new ServerRequest(['url' => '/thing?one=two']));
        unset($result['_route']);
        $expected = [
            'controller' => 'Articles',
            'action' => 'view',
            'pass' => [],
            'id' => 'thing',
            'plugin' => null,
            '_matchedRoute' => '/{id}',
            '?' => ['one' => 'two'],
        ];
        $this->assertEquals($expected, $result);
    }

    /**
     * Test parsing routes with and without _name.
     */
    public function testParseWithNameParameter(): void
    {
        $routes = new RouteBuilder($this->collection, '/b', ['key' => 'value']);
        $routes->connect('/', ['controller' => 'Articles']);
        $routes->connect('/{id}', ['controller' => 'Articles', 'action' => 'view']);
        $routes->connect('/media/search/*', ['controller' => 'Media', 'action' => 'search'], ['_name' => 'media_search']);

        $result = $this->collection->parseRequest(new ServerRequest(['url' => '/b/']));
        unset($result['_route']);
        $expected = [
            'controller' => 'Articles',
            'action' => 'index',
            'pass' => [],
            'plugin' => null,
            'key' => 'value',
            '_matchedRoute' => '/b',
        ];
        $this->assertEquals($expected, $result);

        $result = $this->collection->parseRequest(new ServerRequest(['url' => '/b/the-thing?one=two']));
        unset($result['_route']);
        $expected = [
            'controller' => 'Articles',
            'action' => 'view',
            'id' => 'the-thing',
            'pass' => [],
            'plugin' => null,
            'key' => 'value',
            '?' => ['one' => 'two'],
            '_matchedRoute' => '/b/{id}',
        ];
        $this->assertEquals($expected, $result);

        $result = $this->collection->parseRequest(new ServerRequest(['url' => '/b/media/search']));
        unset($result['_route']);
        $expected = [
            'key' => 'value',
            'pass' => [],
            'plugin' => null,
            'controller' => 'Media',
            'action' => 'search',
            '_matchedRoute' => '/b/media/search/*',
            '_name' => 'media_search',
        ];
        $this->assertEquals($expected, $result);

        $result = $this->collection->parseRequest(new ServerRequest(['url' => '/b/media/search/thing']));
        unset($result['_route']);
        $expected = [
            'key' => 'value',
            'pass' => ['thing'],
            'plugin' => null,
            'controller' => 'Media',
            'action' => 'search',
            '_matchedRoute' => '/b/media/search/*',
            '_name' => 'media_search',
        ];
        $this->assertEquals($expected, $result);
    }

    /**
     * Test that parse decodes URL data before matching.
     * This is important for multibyte URLs that pass through URL rewriting.
     */
    public function testParseEncodedBytesInFixedSegment(): void
    {
        $routes = new RouteBuilder($this->collection, '/');
        $routes->connect('/ден/{day}-{month}', ['controller' => 'Events', 'action' => 'index']);

        $url = '/%D0%B4%D0%B5%D0%BD/15-%D0%BE%D0%BA%D1%82%D0%BE%D0%BC%D0%B2%D1%80%D0%B8?test=foo';
        $result = $this->collection->parseRequest(new ServerRequest(['url' => $url]));
        unset($result['_route']);
        $expected = [
            'pass' => [],
            'plugin' => null,
            'controller' => 'Events',
            'action' => 'index',
            'day' => '15',
            'month' => 'октомври',
            '?' => ['test' => 'foo'],
            '_matchedRoute' => '/ден/{day}-{month}',
        ];
        $this->assertEquals($expected, $result);
    }

    /**
     * Test that parsing checks all the related path scopes.
     */
    public function testParseFallback(): void
    {
        $routes = new RouteBuilder($this->collection, '/', []);

        $routes->resources('Articles');
        $routes->connect('/{controller}', ['action' => 'index'], ['routeClass' => 'InflectedRoute']);
        $routes->connect('/{controller}/{action}', [], ['routeClass' => 'InflectedRoute']);

        $result = $this->collection->parseRequest(new ServerRequest(['url' => '/articles/add']));
        unset($result['_route']);
        $expected = [
            'controller' => 'Articles',
            'action' => 'add',
            'plugin' => null,
            'pass' => [],
            '_matchedRoute' => '/{controller}/{action}',

        ];
        $this->assertEquals($expected, $result);
    }

    /**
     * Test parseRequest() throws an error on unknown routes.
     */
    public function testParseRequestMissingRoute(): void
    {
        $this->expectException(MissingRouteException::class);
        $this->expectExceptionMessage('A route matching `/` could not be found');
        $routes = new RouteBuilder($this->collection, '/b', ['key' => 'value']);
        $routes->connect('/', ['controller' => 'Articles']);
        $routes->connect('/{id}', ['controller' => 'Articles', 'action' => 'view']);

        $request = new ServerRequest(['url' => '/']);
        $result = $this->collection->parseRequest($request);
        $this->assertEquals([], $result, 'Should not match, missing /b');
    }

    /**
     * Test parseRequest() handling query strings.
     */
    public function testParseRequestQueryString(): void
    {
        $routes = new RouteBuilder($this->collection, '/');
        $routes->connect('/{id}', ['controller' => 'Articles', 'action' => 'view']);
        $routes->connect('/media/search/*', ['controller' => 'Media', 'action' => 'search']);

        $request = new ServerRequest(['url' => '/media/search/php?one=two']);
        $result = $this->collection->parseRequest($request);
        unset($result['_route']);
        $expected = [
            'controller' => 'Media',
            'action' => 'search',
            'pass' => ['php'],
            'plugin' => null,
            '_matchedRoute' => '/media/search/*',
            '?' => ['one' => 'two'],
        ];
        $this->assertEquals($expected, $result);

        $request = new ServerRequest(['url' => '/thing?one=two']);
        $result = $this->collection->parseRequest($request);
        unset($result['_route']);
        $expected = [
            'controller' => 'Articles',
            'action' => 'view',
            'pass' => [],
            'id' => 'thing',
            'plugin' => null,
            '_matchedRoute' => '/{id}',
            '?' => ['one' => 'two'],
        ];
        $this->assertEquals($expected, $result);
    }

    /**
     * Test parseRequest() checks host conditions
     */
    public function testParseRequestCheckHostCondition(): void
    {
        $routes = new RouteBuilder($this->collection, '/');
        $routes->connect(
            '/fallback',
            ['controller' => 'Articles', 'action' => 'index'],
            ['_host' => '*.example.com']
        );

        $request = new ServerRequest([
            'environment' => [
                'HTTP_HOST' => 'a.example.com',
                'PATH_INFO' => '/fallback',
            ],
        ]);
        $result = $this->collection->parseRequest($request);
        unset($result['_route']);
        $expected = [
            'controller' => 'Articles',
            'action' => 'index',
            'pass' => [],
            'plugin' => null,
            '_matchedRoute' => '/fallback',
        ];
        $this->assertEquals($expected, $result, 'Should match, domain is correct');

        $request = new ServerRequest([
            'environment' => [
                'HTTP_HOST' => 'foo.bar.example.com',
                'PATH_INFO' => '/fallback',
            ],
        ]);
        $result = $this->collection->parseRequest($request);
        unset($result['_route']);
        $this->assertEquals($expected, $result, 'Should match, domain is a matching subdomain');

        $request = new ServerRequest([
            'environment' => [
                'HTTP_HOST' => 'example.test.com',
                'PATH_INFO' => '/fallback',
            ],
        ]);
        try {
            $this->collection->parseRequest($request);
            $this->fail('No exception raised');
        } catch (MissingRouteException $e) {
            $this->assertStringContainsString('/fallback', $e->getMessage());
        }
    }

    /**
     * Get a list of hostnames
     *
     * @return array
     */
    public static function hostProvider(): array
    {
        return [
            ['wrong.example'],
            ['example.com'],
            ['aexample.com'],
        ];
    }

    /**
     * Test parseRequest() checks host conditions
     *
     * @dataProvider hostProvider
     */
    public function testParseRequestCheckHostConditionFail(string $host): void
    {
        $this->expectException(MissingRouteException::class);
        $this->expectExceptionMessage('A route matching `/fallback` could not be found');
        $routes = new RouteBuilder($this->collection, '/');
        $routes->connect(
            '/fallback',
            ['controller' => 'Articles', 'action' => 'index'],
            ['_host' => '*.example.com']
        );

        $request = new ServerRequest([
            'environment' => [
                'HTTP_HOST' => $host,
                'PATH_INFO' => '/fallback',
            ],
        ]);
        $this->collection->parseRequest($request);
    }

    /**
     * Test parsing routes.
     */
    public function testParseRequest(): void
    {
        $routes = new RouteBuilder($this->collection, '/b', ['key' => 'value']);
        $routes->connect('/', ['controller' => 'Articles']);
        $routes->connect('/{id}', ['controller' => 'Articles', 'action' => 'view']);
        $routes->connect('/media/search/*', ['controller' => 'Media', 'action' => 'search']);

        $request = new ServerRequest(['url' => '/b/']);
        $result = $this->collection->parseRequest($request);
        unset($result['_route']);
        $expected = [
            'controller' => 'Articles',
            'action' => 'index',
            'pass' => [],
            'plugin' => null,
            'key' => 'value',
            '_matchedRoute' => '/b',
        ];
        $this->assertEquals($expected, $result);

        $request = new ServerRequest(['url' => '/b/media/search']);
        $result = $this->collection->parseRequest($request);
        unset($result['_route']);
        $expected = [
            'key' => 'value',
            'pass' => [],
            'plugin' => null,
            'controller' => 'Media',
            'action' => 'search',
            '_matchedRoute' => '/b/media/search/*',
        ];
        $this->assertEquals($expected, $result);

        $request = new ServerRequest(['url' => '/b/media/search/thing']);
        $result = $this->collection->parseRequest($request);
        unset($result['_route']);
        $expected = [
            'key' => 'value',
            'pass' => ['thing'],
            'plugin' => null,
            'controller' => 'Media',
            'action' => 'search',
            '_matchedRoute' => '/b/media/search/*',
        ];
        $this->assertEquals($expected, $result);

        $request = new ServerRequest(['url' => '/b/the-thing?one=two']);
        $result = $this->collection->parseRequest($request);
        unset($result['_route']);
        $expected = [
            'controller' => 'Articles',
            'action' => 'view',
            'id' => 'the-thing',
            'pass' => [],
            'plugin' => null,
            'key' => 'value',
            '?' => ['one' => 'two'],
            '_matchedRoute' => '/b/{id}',
        ];
        $this->assertEquals($expected, $result);
    }

    public function testParseRequestExtension(): void
    {
        $builder = new RouteBuilder($this->collection, '/');
        $builder->connect('/foo', ['controller' => 'Articles'])->setExtensions(['json']);

        $request = new ServerRequest(['url' => '/foo']);
        $result = $this->collection->parseRequest($request);
        unset($result['_route']);
        $expected = [
            'controller' => 'Articles',
            'action' => 'index',
            'pass' => [],
            'plugin' => null,
            '_matchedRoute' => '/foo',
        ];
        $this->assertEquals($expected, $result);

        $request = new ServerRequest(['url' => '/foo.json']);
        $result = $this->collection->parseRequest($request);
        unset($result['_route']);
        $expected = [
            'controller' => 'Articles',
            'action' => 'index',
            'pass' => [],
            'plugin' => null,
            '_ext' => 'json',
            '_matchedRoute' => '/foo',
        ];
        $this->assertEquals($expected, $result);
    }

    /**
     * Test parsing routes that match non-ascii urls
     */
    public function testParseRequestUnicode(): void
    {
        $routes = new RouteBuilder($this->collection, '/b', []);
        $routes->connect('/alta/confirmación', ['controller' => 'Media', 'action' => 'confirm']);

        $request = new ServerRequest(['url' => '/b/alta/confirmaci%C3%B3n']);
        $result = $this->collection->parseRequest($request);
        unset($result['_route']);
        $expected = [
            'controller' => 'Media',
            'action' => 'confirm',
            'pass' => [],
            'plugin' => null,
            '_matchedRoute' => '/b/alta/confirmación',
        ];
        $this->assertEquals($expected, $result);

        $request = new ServerRequest(['url' => '/b/alta/confirmación']);
        $result = $this->collection->parseRequest($request);
        unset($result['_route']);
        $this->assertEquals($expected, $result);
    }

    /**
     * Test match() throws an error on unknown routes.
     */
    public function testMatchError(): void
    {
        $this->expectException(MissingRouteException::class);
        $this->expectExceptionMessage('A route matching `array (');
        $context = [
            '_base' => '/',
            '_scheme' => 'http',
            '_host' => 'example.org',
        ];
        $routes = new RouteBuilder($this->collection, '/b');
        $routes->connect('/', ['controller' => 'Articles']);

        $this->collection->match(['plugin' => null, 'controller' => 'Articles', 'action' => 'add'], $context);
    }

    /**
     * Test matching routes.
     */
    public function testMatch(): void
    {
        $context = [
            '_base' => '/',
            '_scheme' => 'http',
            '_host' => 'example.org',
        ];
        $routes = new RouteBuilder($this->collection, '/b');
        $routes->connect('/', ['controller' => 'Articles']);
        $routes->connect('/{id}', ['controller' => 'Articles', 'action' => 'view']);

        $result = $this->collection->match(['plugin' => null, 'controller' => 'Articles', 'action' => 'index'], $context);
        $this->assertSame('b', $result);

        $result = $this->collection->match(
            ['id' => 'thing', 'plugin' => null, 'controller' => 'Articles', 'action' => 'view'],
            $context
        );
        $this->assertSame('b/thing', $result);
    }

    /**
     * Test matching routes with names
     */
    public function testMatchNamed(): void
    {
        $context = [
            '_base' => '/',
            '_scheme' => 'http',
            '_host' => 'example.org',
        ];
        $routes = new RouteBuilder($this->collection, '/b');
        $routes->connect('/', ['controller' => 'Articles']);
        $routes->connect('/{id}', ['controller' => 'Articles', 'action' => 'view'], ['_name' => 'article:view']);

        $result = $this->collection->match(['_name' => 'article:view', 'id' => '2'], $context);
        $this->assertSame('/b/2', $result);

        $result = $this->collection->match(['plugin' => null, 'controller' => 'Articles', 'action' => 'view', 'id' => '2'], $context);
        $this->assertSame('b/2', $result);
    }

    /**
     * Test match() throws an error on named routes that fail to match
     */
    public function testMatchNamedError(): void
    {
        $this->expectException(MissingRouteException::class);
        $this->expectExceptionMessage('A named route was found for `fail`, but matching failed');
        $context = [
            '_base' => '/',
            '_scheme' => 'http',
            '_host' => 'example.org',
        ];
        $routes = new RouteBuilder($this->collection, '/b');
        $routes->connect('/{lang}/articles', ['controller' => 'Articles'], ['_name' => 'fail']);

        $this->collection->match(['_name' => 'fail'], $context);
    }

    /**
     * Test matching routes with names and failing
     */
    public function testMatchNamedMissingError(): void
    {
        $this->expectException(MissingRouteException::class);
        $context = [
            '_base' => '/',
            '_scheme' => 'http',
            '_host' => 'example.org',
        ];
        $routes = new RouteBuilder($this->collection, '/b');
        $routes->connect('/{id}', ['controller' => 'Articles', 'action' => 'view'], ['_name' => 'article:view']);

        $this->collection->match(['_name' => 'derp'], $context);
    }

    /**
     * Test matching plugin routes.
     */
    public function testMatchPlugin(): void
    {
        $context = [
            '_base' => '/',
            '_scheme' => 'http',
            '_host' => 'example.org',
        ];
        $routes = new RouteBuilder($this->collection, '/contacts', ['plugin' => 'Contacts']);
        $routes->connect('/', ['controller' => 'Contacts']);

        $result = $this->collection->match(['plugin' => 'Contacts', 'controller' => 'Contacts', 'action' => 'index'], $context);
        $this->assertSame('contacts', $result);
    }

    /**
     * Test that prefixes increase the specificity of a route match.
     *
     * Connect the admin route after the non prefixed version, this means
     * the non-prefix route would have a more specific name (users:index)
     */
    public function testMatchPrefixSpecificity(): void
    {
        $context = [
            '_base' => '/',
            '_scheme' => 'http',
            '_host' => 'example.org',
        ];
        $routes = new RouteBuilder($this->collection, '/');
        $routes->connect('/{action}/*', ['controller' => 'Users']);
        $routes->connect('/admin/{controller}', ['prefix' => 'Admin', 'action' => 'index']);

        $url = [
            'plugin' => null,
            'prefix' => 'Admin',
            'controller' => 'Users',
            'action' => 'index',
        ];
        $result = $this->collection->match($url, $context);
        $this->assertSame('admin/Users', $result);

        $url = [
            'plugin' => null,
            'controller' => 'Users',
            'action' => 'index',
        ];
        $result = $this->collection->match($url, $context);
        $this->assertSame('index', $result);
    }

    /**
     * Test getting named routes.
     */
    public function testNamed(): void
    {
        $routes = new RouteBuilder($this->collection, '/l');
        $routes->connect('/{controller}', ['action' => 'index'], ['_name' => 'cntrl']);
        $routes->connect('/{controller}/{action}/*');

        $all = $this->collection->named();
        $this->assertCount(1, $all);
        $this->assertInstanceOf('Cake\Routing\Route\Route', $all['cntrl']);
        $this->assertSame('/l/{controller}', $all['cntrl']->template);
    }

    /**
     * Test the add() and routes() method.
     */
    public function testAddingRoutes(): void
    {
        $one = new Route('/pages/*', ['controller' => 'Pages', 'action' => 'display']);
        $two = new Route('/', ['controller' => 'Dashboards', 'action' => 'display']);
        $this->collection->add($one);
        $this->collection->add($two);

        $routes = $this->collection->routes();
        $this->assertCount(2, $routes);
        $this->assertSame($one, $routes[0]);
        $this->assertSame($two, $routes[1]);
    }

    /**
     * Test the add() with some _name.
     */
    public function testAddingDuplicateNamedRoutes(): void
    {
        $this->expectException(DuplicateNamedRouteException::class);
        $one = new Route('/pages/*', ['controller' => 'Pages', 'action' => 'display']);
        $two = new Route('/', ['controller' => 'Dashboards', 'action' => 'display']);
        $this->collection->add($one, ['_name' => 'test']);
        $this->collection->add($two, ['_name' => 'test']);
    }

    /**
     * Test basic setExtension and its getter.
     */
    public function testSetExtensions(): void
    {
        $this->assertEquals([], $this->collection->getExtensions());

        $this->collection->setExtensions(['json']);
        $this->assertEquals(['json'], $this->collection->getExtensions());

        $this->collection->setExtensions(['rss', 'xml']);
        $this->assertEquals(['json', 'rss', 'xml'], $this->collection->getExtensions());

        $this->collection->setExtensions(['csv'], false);
        $this->assertEquals(['csv'], $this->collection->getExtensions());
    }

    /**
     * Test adding middleware to the collection.
     */
    public function testRegisterMiddleware(): void
    {
        $result = $this->collection->registerMiddleware('closure', function (): void {
        });
        $this->assertSame($result, $this->collection);

        $callable = function () {
        };
        $result = $this->collection->registerMiddleware('callable', $callable);
        $this->assertSame($result, $this->collection);

        $this->assertTrue($this->collection->hasMiddleware('closure'));
        $this->assertTrue($this->collection->hasMiddleware('callable'));

        $this->collection->registerMiddleware('class', 'Dumb');
    }

    /**
     * Test adding a middleware group to the collection.
     */
    public function testMiddlewareGroup(): void
    {
        $this->collection->registerMiddleware('closure', function (): void {
        });

        $callable = function () {
        };
        $this->collection->registerMiddleware('callable', $callable);

        $this->collection->middlewareGroup('group', ['closure', 'callable']);

        $this->assertTrue($this->collection->hasMiddlewareGroup('group'));
    }

    /**
     * Test adding a middleware group with the same name overwrites the original list
     */
    public function testMiddlewareGroupOverwrite(): void
    {
        $stub = function (): void {
        };
        $this->collection->registerMiddleware('closure', $stub);
        $this->collection->registerMiddleware('callable', $stub);

        $this->collection->middlewareGroup('group', ['callable']);
        $this->collection->middlewareGroup('group', ['closure', 'callable']);
        $this->assertSame([$stub, $stub], $this->collection->getMiddleware(['group']));
    }

    /**
     * Test adding ab unregistered middleware to a middleware group fails.
     */
    public function testMiddlewareGroupUnregisteredMiddleware(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot add \'bad\' middleware to group \'group\'. It has not been registered.');
        $this->collection->middlewareGroup('group', ['bad']);
    }
}
