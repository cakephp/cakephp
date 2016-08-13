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
        $this->collection->add($one,['_name' => 'test']);
        $this->collection->add($two,['_name' => 'test']);

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
