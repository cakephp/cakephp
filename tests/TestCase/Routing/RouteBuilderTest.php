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
use Cake\Routing\Router;
use Cake\Routing\Route\Route;
use Cake\TestSuite\TestCase;

/**
 * RouteBuilder test case
 */
class RouteBuilderTest extends TestCase
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
     * Test path()
     *
     * @return void
     */
    public function testPath()
    {
        $routes = new RouteBuilder($this->collection, '/some/path');
        $this->assertEquals('/some/path', $routes->path());

        $routes = new RouteBuilder($this->collection, '/:book_id');
        $this->assertEquals('/', $routes->path());

        $routes = new RouteBuilder($this->collection, '/path/:book_id');
        $this->assertEquals('/path/', $routes->path());

        $routes = new RouteBuilder($this->collection, '/path/book:book_id');
        $this->assertEquals('/path/book', $routes->path());
    }

    /**
     * Test params()
     *
     * @return void
     */
    public function testParams()
    {
        $routes = new RouteBuilder($this->collection, '/api', ['prefix' => 'api']);
        $this->assertEquals(['prefix' => 'api'], $routes->params());
    }

    /**
     * Test getting connected routes.
     *
     * @return void
     */
    public function testRoutes()
    {
        $routes = new RouteBuilder($this->collection, '/l');
        $routes->connect('/:controller', ['action' => 'index']);
        $routes->connect('/:controller/:action/*');

        $all = $this->collection->routes();
        $this->assertCount(2, $all);
        $this->assertInstanceOf('Cake\Routing\Route\Route', $all[0]);
        $this->assertInstanceOf('Cake\Routing\Route\Route', $all[1]);
    }

    /**
     * Test setting default route class
     *
     * @return void
     */
    public function testRouteClass()
    {
        $routes = new RouteBuilder(
            $this->collection,
            '/l',
            [],
            ['routeClass' => 'InflectedRoute']
        );
        $routes->connect('/:controller', ['action' => 'index']);
        $routes->connect('/:controller/:action/*');

        $all = $this->collection->routes();
        $this->assertInstanceOf('Cake\Routing\Route\InflectedRoute', $all[0]);
        $this->assertInstanceOf('Cake\Routing\Route\InflectedRoute', $all[1]);

        $this->collection = new RouteCollection();
        $routes = new RouteBuilder($this->collection, '/l');
        $routes->routeClass('TestApp\Routing\Route\DashedRoute');

        $routes->connect('/:controller', ['action' => 'index']);
        $all = $this->collection->routes();
        $this->assertInstanceOf('TestApp\Routing\Route\DashedRoute', $all[0]);
    }

    /**
     * Test connecting an instance routes.
     *
     * @return void
     */
    public function testConnectInstance()
    {
        $routes = new RouteBuilder($this->collection, '/l', ['prefix' => 'api']);

        $route = new Route('/:controller');
        $this->assertNull($routes->connect($route));

        $result = $this->collection->routes()[0];
        $this->assertSame($route, $result);
    }

    /**
     * Test connecting basic routes.
     *
     * @return void
     */
    public function testConnectBasic()
    {
        $routes = new RouteBuilder($this->collection, '/l', ['prefix' => 'api']);

        $this->assertNull($routes->connect('/:controller'));
        $route = $this->collection->routes()[0];

        $this->assertInstanceOf('Cake\Routing\Route\Route', $route);
        $this->assertEquals('/l/:controller', $route->template);
        $expected = ['prefix' => 'api', 'action' => 'index', 'plugin' => null];
        $this->assertEquals($expected, $route->defaults);
    }

    /**
     * Test that compiling a route results in an trailing / optional pattern.
     *
     * @return void
     */
    public function testConnectTrimTrailingSlash()
    {
        $routes = new RouteBuilder($this->collection, '/articles', ['controller' => 'Articles']);
        $routes->connect('/', ['action' => 'index']);

        $expected = [
            'plugin' => null,
            'controller' => 'Articles',
            'action' => 'index',
            'pass' => [],
            '_matchedRoute' => '/articles',
        ];
        $this->assertEquals($expected, $this->collection->parse('/articles'));
        $this->assertEquals($expected, $this->collection->parse('/articles/'));
    }

    /**
     * Test if a route name already exist
     *
     * @return void
     */
    public function testNameExists()
    {
        $routes = new RouteBuilder($this->collection, '/l', ['prefix' => 'api']);

        $this->assertFalse($routes->nameExists('myRouteName'));

        $routes->connect('myRouteUrl', ['action' => 'index'], ['_name' => 'myRouteName']);

        $this->assertTrue($routes->nameExists('myRouteName'));
    }

    /**
     * Test extensions being connected to routes.
     *
     * @return void
     */
    public function testConnectExtensions()
    {
        $routes = new RouteBuilder(
            $this->collection,
            '/l',
            [],
            ['extensions' => ['json']]
        );
        $this->assertEquals(['json'], $routes->extensions());

        $routes->connect('/:controller');
        $route = $this->collection->routes()[0];

        $this->assertEquals(['json'], $route->options['_ext']);
        $routes->extensions(['xml', 'json']);

        $routes->connect('/:controller/:action');
        $new = $this->collection->routes()[1];
        $this->assertEquals(['json'], $route->options['_ext']);
        $this->assertEquals(['xml', 'json'], $new->options['_ext']);
    }

    /**
     * Test adding additional extensions will be merged with current.
     *
     * @return void
     */
    public function testConnectExtensionsAdd()
    {
        $routes = new RouteBuilder(
            $this->collection,
            '/l',
            [],
            ['extensions' => ['json']]
        );
        $this->assertEquals(['json'], $routes->extensions());

        $routes->addExtensions(['xml']);
        $this->assertEquals(['json', 'xml'], $routes->extensions());

        $routes->addExtensions('csv');
        $this->assertEquals(['json', 'xml', 'csv'], $routes->extensions());
    }

    /**
     * test that extensions() accepts a string.
     *
     * @return void
     */
    public function testExtensionsString()
    {
        $routes = new RouteBuilder($this->collection, '/l');
        $routes->extensions('json');

        $this->assertEquals(['json'], $routes->extensions());
    }

    /**
     * Test error on invalid route class
     *
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Route class not found, or route class is not a subclass of
     * @return void
     */
    public function testConnectErrorInvalidRouteClass()
    {
        $routes = new RouteBuilder(
            $this->collection,
            '/l',
            [],
            ['extensions' => ['json']]
        );
        $routes->connect('/:controller', [], ['routeClass' => '\StdClass']);
    }

    /**
     * Test conflicting parameters raises an exception.
     *
     * @expectedException \BadMethodCallException
     * @expectedExceptionMessage You cannot define routes that conflict with the scope.
     * @return void
     */
    public function testConnectConflictingParameters()
    {
        $routes = new RouteBuilder($this->collection, '/admin', ['plugin' => 'TestPlugin']);
        $routes->connect('/', ['plugin' => 'TestPlugin2', 'controller' => 'Dashboard', 'action' => 'view']);
    }

    /**
     * Test connecting redirect routes.
     *
     * @return void
     */
    public function testRedirect()
    {
        $routes = new RouteBuilder($this->collection, '/');
        $routes->redirect('/p/:id', ['controller' => 'posts', 'action' => 'view'], ['status' => 301]);
        $route = $this->collection->routes()[0];

        $this->assertInstanceOf('Cake\Routing\Route\RedirectRoute', $route);

        $routes->redirect('/old', '/forums', ['status' => 301]);
        $route = $this->collection->routes()[1];

        $this->assertInstanceOf('Cake\Routing\Route\RedirectRoute', $route);
        $this->assertEquals('/forums', $route->redirect[0]);
    }

    /**
     * Test creating sub-scopes with prefix()
     *
     * @return void
     */
    public function testPrefix()
    {
        $routes = new RouteBuilder($this->collection, '/path', ['key' => 'value']);
        $res = $routes->prefix('admin', function ($r) {
            $this->assertInstanceOf('Cake\Routing\RouteBuilder', $r);
            $this->assertCount(0, $this->collection->routes());
            $this->assertEquals('/path/admin', $r->path());
            $this->assertEquals(['prefix' => 'admin', 'key' => 'value'], $r->params());
        });
        $this->assertNull($res);
    }

    /**
     * Test creating sub-scopes with prefix()
     *
     * @return void
     */
    public function testNestedPrefix()
    {
        $routes = new RouteBuilder($this->collection, '/admin', ['prefix' => 'admin']);
        $res = $routes->prefix('api', function ($r) {
            $this->assertEquals('/admin/api', $r->path());
            $this->assertEquals(['prefix' => 'admin/api'], $r->params());
        });
        $this->assertNull($res);
    }

    /**
     * Test creating sub-scopes with plugin()
     *
     * @return void
     */
    public function testNestedPlugin()
    {
        $routes = new RouteBuilder($this->collection, '/b', ['key' => 'value']);
        $res = $routes->plugin('Contacts', function ($r) {
            $this->assertEquals('/b/contacts', $r->path());
            $this->assertEquals(['plugin' => 'Contacts', 'key' => 'value'], $r->params());

            $r->connect('/:controller');
            $route = $this->collection->routes()[0];
            $this->assertEquals(
                ['key' => 'value', 'plugin' => 'Contacts', 'action' => 'index'],
                $route->defaults
            );
        });
        $this->assertNull($res);
    }

    /**
     * Test creating sub-scopes with plugin() + path option
     *
     * @return void
     */
    public function testNestedPluginPathOption()
    {
        $routes = new RouteBuilder($this->collection, '/b', ['key' => 'value']);
        $routes->plugin('Contacts', ['path' => '/people'], function ($r) {
            $this->assertEquals('/b/people', $r->path());
            $this->assertEquals(['plugin' => 'Contacts', 'key' => 'value'], $r->params());
        });
    }

    /**
     * Test connecting resources.
     *
     * @return void
     */
    public function testResources()
    {
        $routes = new RouteBuilder($this->collection, '/api', ['prefix' => 'api']);
        $routes->resources('Articles', ['_ext' => 'json']);

        $all = $this->collection->routes();
        $this->assertCount(5, $all);

        $this->assertEquals('/api/articles', $all[0]->template);
        $this->assertEquals(
            ['controller', 'action', '_method', 'prefix', 'plugin'],
            array_keys($all[0]->defaults)
        );
        $this->assertEquals('json', $all[0]->options['_ext']);
        $this->assertEquals('Articles', $all[0]->defaults['controller']);
    }

    /**
     * Test connecting resources with a prefix
     *
     * @return void
     */
    public function testResourcesPrefix()
    {
        $routes = new RouteBuilder($this->collection, '/api');
        $routes->resources('Articles', ['prefix' => 'rest']);
        $all = $this->collection->routes();
        $this->assertEquals('rest', $all[0]->defaults['prefix']);
    }

    /**
     * Test that resource prefixes work within a prefixed scope.
     *
     * @return void
     */
    public function testResourcesNestedPrefix()
    {
        $routes = new RouteBuilder($this->collection, '/api', ['prefix' => 'api']);
        $routes->resources('Articles', ['prefix' => 'rest']);

        $all = $this->collection->routes();
        $this->assertCount(5, $all);

        $this->assertEquals('/api/articles', $all[0]->template);
        foreach ($all as $route) {
            $this->assertEquals('api/rest', $route->defaults['prefix']);
            $this->assertEquals('Articles', $route->defaults['controller']);
        }
    }

    /**
     * Test connecting resources with the inflection option
     *
     * @return void
     */
    public function testResourcesInflection()
    {
        $routes = new RouteBuilder($this->collection, '/api', ['prefix' => 'api']);
        $routes->resources('BlogPosts', ['_ext' => 'json', 'inflect' => 'dasherize']);

        $all = $this->collection->routes();
        $this->assertCount(5, $all);

        $this->assertEquals('/api/blog-posts', $all[0]->template);
        $this->assertEquals(
            ['controller', 'action', '_method', 'prefix', 'plugin'],
            array_keys($all[0]->defaults)
        );
        $this->assertEquals('BlogPosts', $all[0]->defaults['controller']);
    }

    /**
     * Test connecting nested resources with the inflection option
     *
     * @return void
     */
    public function testResourcesNestedInflection()
    {
        $routes = new RouteBuilder($this->collection, '/api');
        $routes->resources(
            'NetworkObjects',
            ['inflect' => 'dasherize'],
            function ($routes) {
                $routes->resources('Attributes');
            }
        );

        $all = $this->collection->routes();
        $this->assertCount(10, $all);

        $this->assertEquals('/api/network-objects', $all[0]->template);
        $this->assertEquals('/api/network-objects/:id', $all[2]->template);
        $this->assertEquals('/api/network-objects/:network_object_id/attributes', $all[5]->template);
    }

    /**
     * Test connecting resources with additional mappings
     *
     * @return void
     */
    public function testResourcesMappings()
    {
        $routes = new RouteBuilder($this->collection, '/api', ['prefix' => 'api']);
        $routes->resources('Articles', [
            '_ext' => 'json',
            'map' => [
                'delete_all' => ['action' => 'deleteAll', 'method' => 'DELETE'],
                'update_many' => ['action' => 'updateAll', 'method' => 'DELETE', 'path' => '/updateAll'],
            ]
        ]);

        $all = $this->collection->routes();
        $this->assertCount(7, $all);

        $this->assertEquals('/api/articles/delete_all', $all[5]->template, 'Path defaults to key name.');
        $this->assertEquals(
            ['controller', 'action', '_method', 'prefix', 'plugin'],
            array_keys($all[5]->defaults)
        );
        $this->assertEquals('Articles', $all[5]->defaults['controller']);
        $this->assertEquals('deleteAll', $all[5]->defaults['action']);

        $this->assertEquals('/api/articles/updateAll', $all[6]->template, 'Explicit path option');
        $this->assertEquals(
            ['controller', 'action', '_method', 'prefix', 'plugin'],
            array_keys($all[6]->defaults)
        );
        $this->assertEquals('Articles', $all[6]->defaults['controller']);
        $this->assertEquals('updateAll', $all[6]->defaults['action']);
    }

    /**
     * Test connecting resources.
     *
     * @return void
     */
    public function testResourcesInScope()
    {
        Router::scope('/api', ['prefix' => 'api'], function ($routes) {
            $routes->extensions(['json']);
            $routes->resources('Articles');
        });
        $url = Router::url([
            'prefix' => 'api',
            'controller' => 'Articles',
            'action' => 'edit',
            '_method' => 'PUT',
            'id' => 99
        ]);
        $this->assertEquals('/api/articles/99', $url);

        $url = Router::url([
            'prefix' => 'api',
            'controller' => 'Articles',
            'action' => 'edit',
            '_method' => 'PUT',
            '_ext' => 'json',
            'id' => 99
        ]);
        $this->assertEquals('/api/articles/99.json', $url);
    }

    /**
     * Test resource parsing.
     *
     * @return void
     */
    public function testResourcesParsing()
    {
        $routes = new RouteBuilder($this->collection, '/');
        $routes->resources('Articles');

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $result = $this->collection->parse('/articles');
        $this->assertEquals('Articles', $result['controller']);
        $this->assertEquals('index', $result['action']);
        $this->assertEquals([], $result['pass']);

        $result = $this->collection->parse('/articles/1');
        $this->assertEquals('Articles', $result['controller']);
        $this->assertEquals('view', $result['action']);
        $this->assertEquals([1], $result['pass']);

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $result = $this->collection->parse('/articles');
        $this->assertEquals('Articles', $result['controller']);
        $this->assertEquals('add', $result['action']);
        $this->assertEquals([], $result['pass']);

        $_SERVER['REQUEST_METHOD'] = 'PUT';
        $result = $this->collection->parse('/articles/1');
        $this->assertEquals('Articles', $result['controller']);
        $this->assertEquals('edit', $result['action']);
        $this->assertEquals([1], $result['pass']);

        $_SERVER['REQUEST_METHOD'] = 'DELETE';
        $result = $this->collection->parse('/articles/1');
        $this->assertEquals('Articles', $result['controller']);
        $this->assertEquals('delete', $result['action']);
        $this->assertEquals([1], $result['pass']);
    }

    /**
     * Test the only option of RouteBuilder.
     *
     * @return void
     */
    public function testResourcesOnlyString()
    {
        $routes = new RouteBuilder($this->collection, '/');
        $routes->resources('Articles', ['only' => 'index']);

        $result = $this->collection->routes();
        $this->assertCount(1, $result);
        $this->assertEquals('/articles', $result[0]->template);
    }

    /**
     * Test the only option of RouteBuilder.
     *
     * @return void
     */
    public function testResourcesOnlyArray()
    {
        $routes = new RouteBuilder($this->collection, '/');
        $routes->resources('Articles', ['only' => ['index', 'delete']]);

        $result = $this->collection->routes();
        $this->assertCount(2, $result);
        $this->assertEquals('/articles', $result[0]->template);
        $this->assertEquals('index', $result[0]->defaults['action']);
        $this->assertEquals('GET', $result[0]->defaults['_method']);

        $this->assertEquals('/articles/:id', $result[1]->template);
        $this->assertEquals('delete', $result[1]->defaults['action']);
        $this->assertEquals('DELETE', $result[1]->defaults['_method']);
    }

    /**
     * Test the actions option of RouteBuilder.
     *
     * @return void
     */
    public function testResourcesActions()
    {
        $routes = new RouteBuilder($this->collection, '/');
        $routes->resources('Articles', [
            'only' => ['index', 'delete'],
            'actions' => ['index' => 'showList']
        ]);

        $result = $this->collection->routes();
        $this->assertCount(2, $result);
        $this->assertEquals('/articles', $result[0]->template);
        $this->assertEquals('showList', $result[0]->defaults['action']);

        $this->assertEquals('/articles/:id', $result[1]->template);
        $this->assertEquals('delete', $result[1]->defaults['action']);
    }

    /**
     * Test nesting resources
     *
     * @return void
     */
    public function testResourcesNested()
    {
        $routes = new RouteBuilder($this->collection, '/api', ['prefix' => 'api']);
        $routes->resources('Articles', function ($routes) {
            $this->assertEquals('/api/articles/', $routes->path());
            $this->assertEquals(['prefix' => 'api'], $routes->params());

            $routes->resources('Comments');
            $route = $this->collection->routes()[6];
            $this->assertEquals('/api/articles/:article_id/comments', $route->template);
        });
    }

    /**
     * Test connecting fallback routes.
     *
     * @return void
     */
    public function testFallbacks()
    {
        $routes = new RouteBuilder($this->collection, '/api', ['prefix' => 'api']);
        $routes->fallbacks();

        $all = $this->collection->routes();
        $this->assertEquals('/api/:controller', $all[0]->template);
        $this->assertEquals('/api/:controller/:action/*', $all[1]->template);
        $this->assertInstanceOf('Cake\Routing\Route\Route', $all[0]);
    }

    /**
     * Test connecting fallback routes with specific route class
     *
     * @return void
     */
    public function testFallbacksWithClass()
    {
        $routes = new RouteBuilder($this->collection, '/api', ['prefix' => 'api']);
        $routes->fallbacks('InflectedRoute');

        $all = $this->collection->routes();
        $this->assertEquals('/api/:controller', $all[0]->template);
        $this->assertEquals('/api/:controller/:action/*', $all[1]->template);
        $this->assertInstanceOf('Cake\Routing\Route\InflectedRoute', $all[0]);
    }

    /**
     * Test connecting fallback routes after setting default route class.
     *
     * @return void
     */
    public function testDefaultRouteClassFallbacks()
    {
        $routes = new RouteBuilder($this->collection, '/api', ['prefix' => 'api']);
        $routes->routeClass('TestApp\Routing\Route\DashedRoute');
        $routes->fallbacks();

        $all = $this->collection->routes();
        $this->assertInstanceOf('TestApp\Routing\Route\DashedRoute', $all[0]);
    }

    /**
     * Test adding a scope.
     *
     * @return void
     */
    public function testScope()
    {
        $routes = new RouteBuilder($this->collection, '/api', ['prefix' => 'api']);
        $routes->scope('/v1', ['version' => 1], function ($routes) {
            $this->assertEquals('/api/v1', $routes->path());
            $this->assertEquals(['prefix' => 'api', 'version' => 1], $routes->params());
        });

        $routes = new RouteBuilder($this->collection, '/api', ['prefix' => 'api']);
        $routes->scope('/v1', function ($routes) {
            $this->assertEquals('/api/v1', $routes->path());
            $this->assertEquals(['prefix' => 'api'], $routes->params());
        });
    }

    /**
     * Test using name prefixes.
     *
     * @return void
     */
    public function testNamePrefixes()
    {
        $routes = new RouteBuilder($this->collection, '/api', [], ['namePrefix' => 'api:']);
        $routes->scope('/v1', ['version' => 1, '_namePrefix' => 'v1:'], function ($routes) {
            $this->assertEquals('api:v1:', $routes->namePrefix());
            $routes->connect('/ping', ['controller' => 'Pings'], ['_name' => 'ping']);

            $routes->namePrefix('web:');
            $routes->connect('/pong', ['controller' => 'Pongs'], ['_name' => 'pong']);
        });

        $all = $this->collection->named();
        $this->assertArrayHasKey('api:v1:ping', $all);
        $this->assertArrayHasKey('web:pong', $all);
    }
}
