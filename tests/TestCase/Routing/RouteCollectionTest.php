<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         3.0.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Routing;

use Cake\Http\ServerRequest;
use Cake\Routing\Exception\MissingRouteException;
use Cake\Routing\RouteBuilder;
use Cake\Routing\RouteCollection;
use Cake\Routing\Route\Route;
use Cake\TestSuite\TestCase;

class RouteCollectionTest extends TestCase
{

    /**
     * Setup method
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $this->collection = new RouteCollection();
    }

    /**
     * Test parse() throws an error on unknown routes.
     *
     * @expectedException \Cake\Routing\Exception\MissingRouteException
     * @expectedExceptionMessage A route matching "/" could not be found
     */
    public function testParseMissingRoute()
    {
        $routes = new RouteBuilder($this->collection, '/b', ['key' => 'value']);
        $routes->connect('/', ['controller' => 'Articles']);
        $routes->connect('/:id', ['controller' => 'Articles', 'action' => 'view']);

        $result = $this->collection->parse('/');
        $this->assertEquals([], $result, 'Should not match, missing /b');
    }

    /**
     * Test parsing routes.
     *
     * @return void
     */
    public function testParse()
    {
        $routes = new RouteBuilder($this->collection, '/b', ['key' => 'value']);
        $routes->connect('/', ['controller' => 'Articles']);
        $routes->connect('/:id', ['controller' => 'Articles', 'action' => 'view']);
        $routes->connect('/media/search/*', ['controller' => 'Media', 'action' => 'search']);

        $result = $this->collection->parse('/b/');
        $expected = [
            'controller' => 'Articles',
            'action' => 'index',
            'pass' => [],
            'plugin' => null,
            'key' => 'value',
            '_matchedRoute' => '/b',
        ];
        $this->assertEquals($expected, $result);

        $result = $this->collection->parse('/b/the-thing?one=two');
        $expected = [
            'controller' => 'Articles',
            'action' => 'view',
            'id' => 'the-thing',
            'pass' => [],
            'plugin' => null,
            'key' => 'value',
            '?' => ['one' => 'two'],
            '_matchedRoute' => '/b/:id',
        ];
        $this->assertEquals($expected, $result);

        $result = $this->collection->parse('/b/media/search');
        $expected = [
            'key' => 'value',
            'pass' => [],
            'plugin' => null,
            'controller' => 'Media',
            'action' => 'search',
            '_matchedRoute' => '/b/media/search/*',
        ];
        $this->assertEquals($expected, $result);

        $result = $this->collection->parse('/b/media/search/thing');
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
     * Test that parse decodes URL data before matching.
     * This is important for multibyte URLs that pass through URL rewriting.
     *
     * @return void
     */
    public function testParseEncodedBytesInFixedSegment()
    {
        $routes = new RouteBuilder($this->collection, '/');
        $routes->connect('/ден/:day-:month', ['controller' => 'Events', 'action' => 'index']);
        $url = '/%D0%B4%D0%B5%D0%BD/15-%D0%BE%D0%BA%D1%82%D0%BE%D0%BC%D0%B2%D1%80%D0%B8?test=foo';
        $result = $this->collection->parse($url);
        $expected = [
            'pass' => [],
            'plugin' => null,
            'controller' => 'Events',
            'action' => 'index',
            'day' => '15',
            'month' => 'октомври',
            '?' => ['test' => 'foo'],
            '_matchedRoute' => '/ден/:day-:month',
        ];
        $this->assertEquals($expected, $result);

        $request = new ServerRequest(['url' => $url]);
        $result = $this->collection->parseRequest($request);
        $this->assertEquals($expected, $result);
    }

    /**
     * Test that parsing checks all the related path scopes.
     *
     * @return void
     */
    public function testParseFallback()
    {
        $routes = new RouteBuilder($this->collection, '/', []);

        $routes->resources('Articles');
        $routes->connect('/:controller', ['action' => 'index'], ['routeClass' => 'InflectedRoute']);
        $routes->connect('/:controller/:action', [], ['routeClass' => 'InflectedRoute']);

        $result = $this->collection->parse('/articles/add');
        $expected = [
            'controller' => 'Articles',
            'action' => 'add',
            'plugin' => null,
            'pass' => [],
            '_matchedRoute' => '/:controller/:action',

        ];
        $this->assertEquals($expected, $result);
    }

    /**
     * Test parseRequest() throws an error on unknown routes.
     *
     * @expectedException \Cake\Routing\Exception\MissingRouteException
     * @expectedExceptionMessage A route matching "/" could not be found
     */
    public function testParseRequestMissingRoute()
    {
        $routes = new RouteBuilder($this->collection, '/b', ['key' => 'value']);
        $routes->connect('/', ['controller' => 'Articles']);
        $routes->connect('/:id', ['controller' => 'Articles', 'action' => 'view']);

        $request = new ServerRequest(['url' => '/']);
        $result = $this->collection->parseRequest($request);
        $this->assertEquals([], $result, 'Should not match, missing /b');
    }

    /**
     * Test parseRequest() checks host conditions
     *
     * @return void
     */
    public function testParseRequestCheckHostCondition()
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
                'PATH_INFO' => '/fallback'
            ]
        ]);
        $result = $this->collection->parseRequest($request);
        $expected = [
            'controller' => 'Articles',
            'action' => 'index',
            'pass' => [],
            'plugin' => null,
            '_matchedRoute' => '/fallback'
        ];
        $this->assertEquals($expected, $result, 'Should match, domain is correct');

        $request = new ServerRequest([
            'environment' => [
                'HTTP_HOST' => 'foo.bar.example.com',
                'PATH_INFO' => '/fallback'
            ]
        ]);
        $result = $this->collection->parseRequest($request);
        $this->assertEquals($expected, $result, 'Should match, domain is a matching subdomain');

        $request = new ServerRequest([
            'environment' => [
                'HTTP_HOST' => 'example.test.com',
                'PATH_INFO' => '/fallback'
            ]
        ]);
        try {
            $this->collection->parseRequest($request);
            $this->fail('No exception raised');
        } catch (MissingRouteException $e) {
            $this->assertContains('/fallback', $e->getMessage());
        }
    }

    /**
     * Get a list of hostnames
     *
     * @return array
     */
    public static function hostProvider()
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
     * @expectedException \Cake\Routing\Exception\MissingRouteException
     * @expectedExceptionMessage A route matching "/fallback" could not be found
     */
    public function testParseRequestCheckHostConditionFail($host)
    {
        $routes = new RouteBuilder($this->collection, '/');
        $routes->connect(
            '/fallback',
            ['controller' => 'Articles', 'action' => 'index'],
            ['_host' => '*.example.com']
        );

        $request = new ServerRequest([
            'environment' => [
                'HTTP_HOST' => $host,
                'PATH_INFO' => '/fallback'
            ]
        ]);
        $this->collection->parseRequest($request);
    }

    /**
     * Test parsing routes.
     *
     * @return void
     */
    public function testParseRequest()
    {
        $routes = new RouteBuilder($this->collection, '/b', ['key' => 'value']);
        $routes->connect('/', ['controller' => 'Articles']);
        $routes->connect('/:id', ['controller' => 'Articles', 'action' => 'view']);
        $routes->connect('/media/search/*', ['controller' => 'Media', 'action' => 'search']);

        $request = new ServerRequest(['url' => '/b/']);
        $result = $this->collection->parseRequest($request);
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
        $expected = [
            'controller' => 'Articles',
            'action' => 'view',
            'id' => 'the-thing',
            'pass' => [],
            'plugin' => null,
            'key' => 'value',
            '?' => ['one' => 'two'],
            '_matchedRoute' => '/b/:id',
        ];
        $this->assertEquals($expected, $result);
    }

    /**
     * Test parsing routes that match non-ascii urls
     *
     * @return void
     */
    public function testParseRequestUnicode()
    {
        $routes = new RouteBuilder($this->collection, '/b', []);
        $routes->connect('/alta/confirmación', ['controller' => 'Media', 'action' => 'confirm']);

        $request = new ServerRequest(['url' => '/b/alta/confirmaci%C3%B3n']);
        $result = $this->collection->parseRequest($request);
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
        $this->assertEquals($expected, $result);
    }

    /**
     * Test match() throws an error on unknown routes.
     *
     * @expectedException \Cake\Routing\Exception\MissingRouteException
     * @expectedExceptionMessage A route matching "array (
     */
    public function testMatchError()
    {
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
     *
     * @return void
     */
    public function testMatch()
    {
        $context = [
            '_base' => '/',
            '_scheme' => 'http',
            '_host' => 'example.org',
        ];
        $routes = new RouteBuilder($this->collection, '/b');
        $routes->connect('/', ['controller' => 'Articles']);
        $routes->connect('/:id', ['controller' => 'Articles', 'action' => 'view']);

        $result = $this->collection->match(['plugin' => null, 'controller' => 'Articles', 'action' => 'index'], $context);
        $this->assertEquals('b', $result);

        $result = $this->collection->match(
            ['id' => 'thing', 'plugin' => null, 'controller' => 'Articles', 'action' => 'view'],
            $context
        );
        $this->assertEquals('b/thing', $result);
    }

    /**
     * Test matching routes with names
     *
     * @return void
     */
    public function testMatchNamed()
    {
        $context = [
            '_base' => '/',
            '_scheme' => 'http',
            '_host' => 'example.org',
        ];
        $routes = new RouteBuilder($this->collection, '/b');
        $routes->connect('/', ['controller' => 'Articles']);
        $routes->connect('/:id', ['controller' => 'Articles', 'action' => 'view'], ['_name' => 'article:view']);

        $result = $this->collection->match(['_name' => 'article:view', 'id' => '2'], $context);
        $this->assertEquals('/b/2', $result);

        $result = $this->collection->match(['plugin' => null, 'controller' => 'Articles', 'action' => 'view', 'id' => '2'], $context);
        $this->assertEquals('b/2', $result);
    }

    /**
     * Test match() throws an error on named routes that fail to match
     *
     * @expectedException \Cake\Routing\Exception\MissingRouteException
     * @expectedExceptionMessage A named route was found for "fail", but matching failed
     */
    public function testMatchNamedError()
    {
        $context = [
            '_base' => '/',
            '_scheme' => 'http',
            '_host' => 'example.org',
        ];
        $routes = new RouteBuilder($this->collection, '/b');
        $routes->connect('/:lang/articles', ['controller' => 'Articles'], ['_name' => 'fail']);

        $this->collection->match(['_name' => 'fail'], $context);
    }

    /**
     * Test matching routes with names and failing
     *
     * @expectedException \Cake\Routing\Exception\MissingRouteException
     * @return void
     */
    public function testMatchNamedMissingError()
    {
        $context = [
            '_base' => '/',
            '_scheme' => 'http',
            '_host' => 'example.org',
        ];
        $routes = new RouteBuilder($this->collection, '/b');
        $routes->connect('/:id', ['controller' => 'Articles', 'action' => 'view'], ['_name' => 'article:view']);

        $this->collection->match(['_name' => 'derp'], $context);
    }

    /**
     * Test matching plugin routes.
     *
     * @return void
     */
    public function testMatchPlugin()
    {
        $context = [
            '_base' => '/',
            '_scheme' => 'http',
            '_host' => 'example.org',
        ];
        $routes = new RouteBuilder($this->collection, '/contacts', ['plugin' => 'Contacts']);
        $routes->connect('/', ['controller' => 'Contacts']);

        $result = $this->collection->match(['plugin' => 'Contacts', 'controller' => 'Contacts', 'action' => 'index'], $context);
        $this->assertEquals('contacts', $result);
    }

    /**
     * Test that prefixes increase the specificity of a route match.
     *
     * Connect the admin route after the non prefixed version, this means
     * the non-prefix route would have a more specific name (users:index)
     *
     * @return void
     */
    public function testMatchPrefixSpecificity()
    {
        $context = [
            '_base' => '/',
            '_scheme' => 'http',
            '_host' => 'example.org',
        ];
        $routes = new RouteBuilder($this->collection, '/');
        $routes->connect('/:action/*', ['controller' => 'Users']);
        $routes->connect('/admin/:controller', ['prefix' => 'admin', 'action' => 'index']);

        $url = [
            'plugin' => null,
            'prefix' => 'admin',
            'controller' => 'Users',
            'action' => 'index'
        ];
        $result = $this->collection->match($url, $context);
        $this->assertEquals('admin/Users', $result);

        $url = [
            'plugin' => null,
            'controller' => 'Users',
            'action' => 'index'
        ];
        $result = $this->collection->match($url, $context);
        $this->assertEquals('index', $result);
    }

    /**
     * Test getting named routes.
     *
     * @return void
     */
    public function testNamed()
    {
        $routes = new RouteBuilder($this->collection, '/l');
        $routes->connect('/:controller', ['action' => 'index'], ['_name' => 'cntrl']);
        $routes->connect('/:controller/:action/*');

        $all = $this->collection->named();
        $this->assertCount(1, $all);
        $this->assertInstanceOf('Cake\Routing\Route\Route', $all['cntrl']);
        $this->assertEquals('/l/:controller', $all['cntrl']->template);
    }

    /**
     * Test the add() and routes() method.
     *
     * @return void
     */
    public function testAddingRoutes()
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
     *
     * @expectedException \Cake\Routing\Exception\DuplicateNamedRouteException
     *
     * @return void
     */
    public function testAddingDuplicateNamedRoutes()
    {
        $one = new Route('/pages/*', ['controller' => 'Pages', 'action' => 'display']);
        $two = new Route('/', ['controller' => 'Dashboards', 'action' => 'display']);
        $this->collection->add($one, ['_name' => 'test']);
        $this->collection->add($two, ['_name' => 'test']);
    }

    /**
     * Test basic get/set of extensions.
     *
     * @return void
     */
    public function testExtensions()
    {
        $this->assertEquals([], $this->collection->extensions());

        $this->collection->extensions('json');
        $this->assertEquals(['json'], $this->collection->extensions());

        $this->collection->extensions(['rss', 'xml']);
        $this->assertEquals(['json', 'rss', 'xml'], $this->collection->extensions());

        $this->collection->extensions(['csv'], false);
        $this->assertEquals(['csv'], $this->collection->extensions());
    }
}
