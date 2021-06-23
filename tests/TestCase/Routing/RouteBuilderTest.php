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

use Cake\Core\Plugin;
use Cake\Routing\Route\InflectedRoute;
use Cake\Routing\Route\RedirectRoute;
use Cake\Routing\Route\Route;
use Cake\Routing\RouteBuilder;
use Cake\Routing\RouteCollection;
use Cake\Routing\Router;
use Cake\TestSuite\TestCase;
use InvalidArgumentException;

/**
 * RouteBuilder test case
 */
class RouteBuilderTest extends TestCase
{
    /**
     * @var \Cake\Routing\RouteCollection
     */
    protected $collection;

    /**
     * Setup method
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->collection = new RouteCollection();
    }

    /**
     * Teardown method
     *
     * @return void
     */
    public function tearDown(): void
    {
        parent::tearDown();
        $this->clearPlugins();
    }

    /**
     * Test path()
     *
     * @return void
     */
    public function testPath()
    {
        $routes = new RouteBuilder($this->collection, '/some/path');
        $this->assertSame('/some/path', $routes->path());

        $routes = new RouteBuilder($this->collection, '/:book_id');
        $this->assertSame('/', $routes->path());

        $routes = new RouteBuilder($this->collection, '/path/:book_id');
        $this->assertSame('/path/', $routes->path());

        $routes = new RouteBuilder($this->collection, '/path/book:book_id');
        $this->assertSame('/path/book', $routes->path());
    }

    /**
     * Test params()
     *
     * @return void
     */
    public function testParams()
    {
        $routes = new RouteBuilder($this->collection, '/api', ['prefix' => 'Api']);
        $this->assertEquals(['prefix' => 'Api'], $routes->params());
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
        $this->assertInstanceOf(Route::class, $all[0]);
        $this->assertInstanceOf(Route::class, $all[1]);
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
        $this->assertInstanceOf(InflectedRoute::class, $all[0]);
        $this->assertInstanceOf(InflectedRoute::class, $all[1]);

        $this->collection = new RouteCollection();
        $routes = new RouteBuilder($this->collection, '/l');
        $this->assertSame($routes, $routes->setRouteClass('TestApp\Routing\Route\DashedRoute'));
        $this->assertSame('TestApp\Routing\Route\DashedRoute', $routes->getRouteClass());

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
        $routes = new RouteBuilder($this->collection, '/l', ['prefix' => 'Api']);

        $route = new Route('/:controller');
        $this->assertSame($route, $routes->connect($route));

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
        $routes = new RouteBuilder($this->collection, '/l', ['prefix' => 'Api']);

        $route = $routes->connect('/:controller');
        $this->assertInstanceOf(Route::class, $route);

        $this->assertSame($route, $this->collection->routes()[0]);
        $this->assertSame('/l/:controller', $route->template);
        $expected = ['prefix' => 'Api', 'action' => 'index', 'plugin' => null];
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
     * Test connect() with short string syntax
     *
     * @return void
     */
    public function testConnectShortStringInvalid()
    {
        $this->expectException(InvalidArgumentException::class);
        $routes = new RouteBuilder($this->collection, '/');
        $routes->connect('/my-articles/view', 'Articles:no');
    }

    /**
     * Test connect() with short string syntax
     *
     * @return void
     */
    public function testConnectShortString()
    {
        $routes = new RouteBuilder($this->collection, '/');
        $routes->connect('/my-articles/view', 'Articles::view');
        $expected = [
            'pass' => [],
            'controller' => 'Articles',
            'action' => 'view',
            'plugin' => null,
            '_matchedRoute' => '/my-articles/view',
        ];
        $this->assertEquals($expected, $this->collection->parse('/my-articles/view'));

        $url = $expected['_matchedRoute'];
        unset($expected['_matchedRoute']);
        $this->assertSame($url, '/' . $this->collection->match($expected, []));
    }

    /**
     * Test connect() with short string syntax
     *
     * @return void
     */
    public function testConnectShortStringPrefix()
    {
        $routes = new RouteBuilder($this->collection, '/');
        $routes->connect('/admin/bookmarks', 'Admin/Bookmarks::index');
        $expected = [
            'pass' => [],
            'plugin' => null,
            'prefix' => 'Admin',
            'controller' => 'Bookmarks',
            'action' => 'index',
            '_matchedRoute' => '/admin/bookmarks',
        ];
        $this->assertEquals($expected, $this->collection->parse('/admin/bookmarks'));

        $url = $expected['_matchedRoute'];
        unset($expected['_matchedRoute']);
        $this->assertSame($url, '/' . $this->collection->match($expected, []));
    }

    /**
     * Test connect() with short string syntax
     *
     * @return void
     */
    public function testConnectShortStringPlugin()
    {
        $routes = new RouteBuilder($this->collection, '/');
        $routes->connect('/blog/articles/view', 'Blog.Articles::view');
        $expected = [
            'pass' => [],
            'plugin' => 'Blog',
            'controller' => 'Articles',
            'action' => 'view',
            '_matchedRoute' => '/blog/articles/view',
        ];
        $this->assertEquals($expected, $this->collection->parse('/blog/articles/view'));

        $url = $expected['_matchedRoute'];
        unset($expected['_matchedRoute']);
        $this->assertSame($url, '/' . $this->collection->match($expected, []));
    }

    /**
     * Test connect() with short string syntax
     *
     * @return void
     */
    public function testConnectShortStringPluginPrefix()
    {
        $routes = new RouteBuilder($this->collection, '/');
        $routes->connect('/admin/blog/articles/view', 'Vendor/Blog.Management/Admin/Articles::view');
        $expected = [
            'pass' => [],
            'plugin' => 'Vendor/Blog',
            'prefix' => 'Management/Admin',
            'controller' => 'Articles',
            'action' => 'view',
            '_matchedRoute' => '/admin/blog/articles/view',
        ];
        $this->assertEquals($expected, $this->collection->parse('/admin/blog/articles/view'));

        $url = $expected['_matchedRoute'];
        unset($expected['_matchedRoute']);
        $this->assertSame($url, '/' . $this->collection->match($expected, []));
    }

    /**
     * Test if a route name already exist
     *
     * @return void
     */
    public function testNameExists()
    {
        $routes = new RouteBuilder($this->collection, '/l', ['prefix' => 'Api']);
        $this->assertFalse($routes->nameExists('myRouteName'));

        $routes->connect('myRouteUrl', ['action' => 'index'], ['_name' => 'myRouteName']);
        $this->assertTrue($routes->nameExists('myRouteName'));
    }

    /**
     * Test setExtensions() and getExtensions().
     *
     * @return void
     */
    public function testExtensions()
    {
        $routes = new RouteBuilder($this->collection, '/l');
        $this->assertSame($routes, $routes->setExtensions(['html']));
        $this->assertSame(['html'], $routes->getExtensions());
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
        $this->assertEquals(['json'], $routes->getExtensions());

        $routes->connect('/:controller');
        $route = $this->collection->routes()[0];

        $this->assertEquals(['json'], $route->options['_ext']);
        $routes->setExtensions(['xml', 'json']);

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
        $this->assertEquals(['json'], $routes->getExtensions());

        $routes->addExtensions(['xml']);
        $this->assertEquals(['json', 'xml'], $routes->getExtensions());

        $routes->addExtensions('csv');
        $this->assertEquals(['json', 'xml', 'csv'], $routes->getExtensions());
    }

    /**
     * test that setExtensions() accepts a string.
     *
     * @return void
     */
    public function testExtensionsString()
    {
        $routes = new RouteBuilder($this->collection, '/l');
        $routes->setExtensions('json');

        $this->assertEquals(['json'], $routes->getExtensions());
    }

    /**
     * Test error on invalid route class
     *
     * @return void
     */
    public function testConnectErrorInvalidRouteClass()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Route class not found, or route class is not a subclass of');
        $routes = new RouteBuilder(
            $this->collection,
            '/l',
            [],
            ['extensions' => ['json']]
        );
        $routes->connect('/:controller', [], ['routeClass' => '\stdClass']);
    }

    /**
     * Test conflicting parameters raises an exception.
     *
     * @return void
     */
    public function testConnectConflictingParameters()
    {
        $this->expectException(\BadMethodCallException::class);
        $this->expectExceptionMessage('You cannot define routes that conflict with the scope.');
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
        $routes->redirect('/p/:id', ['controller' => 'Posts', 'action' => 'view'], ['status' => 301]);
        $route = $this->collection->routes()[0];

        $this->assertInstanceOf(RedirectRoute::class, $route);

        $routes->redirect('/old', '/forums', ['status' => 301]);
        $route = $this->collection->routes()[1];

        $this->assertInstanceOf(RedirectRoute::class, $route);
        $this->assertSame('/forums', $route->redirect[0]);

        $route = $routes->redirect('/old', '/forums');
        $this->assertInstanceOf(RedirectRoute::class, $route);
        $this->assertSame($route, $this->collection->routes()[2]);
    }

    /**
     * Test using a custom route class for redirect routes.
     *
     * @return void
     */
    public function testRedirectWithCustomRouteClass()
    {
        $routes = new RouteBuilder($this->collection, '/');

        $routes->redirect('/old', '/forums', ['status' => 301, 'routeClass' => 'InflectedRoute']);
        $route = $this->collection->routes()[0];

        $this->assertInstanceOf(InflectedRoute::class, $route);
    }

    /**
     * Test creating sub-scopes with prefix()
     *
     * @return void
     */
    public function testPrefix()
    {
        $routes = new RouteBuilder($this->collection, '/path', ['key' => 'value']);
        $res = $routes->prefix('admin', ['param' => 'value'], function (RouteBuilder $r) {
            $this->assertInstanceOf(RouteBuilder::class, $r);
            $this->assertCount(0, $this->collection->routes());
            $this->assertSame('/path/admin', $r->path());
            $this->assertEquals(['prefix' => 'Admin', 'key' => 'value', 'param' => 'value'], $r->params());
        });
        $this->assertSame($routes, $res);
    }

    /**
     * Test creating sub-scopes with prefix()
     *
     * @return void
     */
    public function testPrefixWithNoParams()
    {
        $routes = new RouteBuilder($this->collection, '/path', ['key' => 'value']);
        $res = $routes->prefix('admin', function (RouteBuilder $r) {
            $this->assertInstanceOf(RouteBuilder::class, $r);
            $this->assertCount(0, $this->collection->routes());
            $this->assertSame('/path/admin', $r->path());
            $this->assertEquals(['prefix' => 'Admin', 'key' => 'value'], $r->params());
        });
        $this->assertSame($routes, $res);
    }

    /**
     * Test creating sub-scopes with prefix()
     *
     * @return void
     */
    public function testNestedPrefix()
    {
        $routes = new RouteBuilder($this->collection, '/admin', ['prefix' => 'Admin']);
        $res = $routes->prefix('api', ['_namePrefix' => 'api:'], function (RouteBuilder $r) {
            $this->assertSame('/admin/api', $r->path());
            $this->assertEquals(['prefix' => 'Admin/Api'], $r->params());
            $this->assertSame('api:', $r->namePrefix());
        });
        $this->assertSame($routes, $res);
    }

    /**
     * Test creating sub-scopes with prefix()
     *
     * @return void
     */
    public function testPathWithDotInPrefix()
    {
        $routes = new RouteBuilder($this->collection, '/admin', ['prefix' => 'Admin']);
        $res = $routes->prefix('Api', function (RouteBuilder $r) {
            $r->prefix('v10', ['path' => '/v1.0'], function (RouteBuilder $r2) {
                $this->assertSame('/admin/api/v1.0', $r2->path());
                $this->assertEquals(['prefix' => 'Admin/Api/V10'], $r2->params());
                $r2->prefix('b1', ['path' => '/beta.1'], function (RouteBuilder $r3) {
                    $this->assertSame('/admin/api/v1.0/beta.1', $r3->path());
                    $this->assertEquals(['prefix' => 'Admin/Api/V10/B1'], $r3->params());
                });
            });
        });
        $this->assertSame($routes, $res);
    }

    /**
     * Test creating sub-scopes with plugin()
     *
     * @return void
     */
    public function testPlugin()
    {
        $routes = new RouteBuilder($this->collection, '/b', ['key' => 'value']);
        $res = $routes->plugin('Contacts', function (RouteBuilder $r) {
            $this->assertSame('/b/contacts', $r->path());
            $this->assertEquals(['plugin' => 'Contacts', 'key' => 'value'], $r->params());

            $r->connect('/:controller');
            $route = $this->collection->routes()[0];
            $this->assertEquals(
                ['key' => 'value', 'plugin' => 'Contacts', 'action' => 'index'],
                $route->defaults
            );
        });
        $this->assertSame($routes, $res);
    }

    /**
     * Test creating sub-scopes with plugin() + path option
     *
     * @return void
     */
    public function testPluginPathOption()
    {
        $routes = new RouteBuilder($this->collection, '/b', ['key' => 'value']);
        $routes->plugin('Contacts', ['path' => '/people'], function (RouteBuilder $r) {
            $this->assertSame('/b/people', $r->path());
            $this->assertEquals(['plugin' => 'Contacts', 'key' => 'value'], $r->params());
        });
    }

    /**
     * Test creating sub-scopes with plugin() + namePrefix option
     *
     * @return void
     */
    public function testPluginNamePrefix()
    {
        $routes = new RouteBuilder($this->collection, '/b', ['key' => 'value']);
        $routes->plugin('Contacts', ['_namePrefix' => 'contacts.'], function (RouteBuilder $r) {
            $this->assertEquals('contacts.', $r->namePrefix());
        });

        $routes = new RouteBuilder($this->collection, '/b', ['key' => 'value']);
        $routes->namePrefix('default.');
        $routes->plugin('Blog', ['_namePrefix' => 'blog.'], function (RouteBuilder $r) {
            $this->assertEquals('default.blog.', $r->namePrefix(), 'Should combine nameprefix');
        });
    }

    /**
     * Test connecting resources.
     *
     * @return void
     */
    public function testResources()
    {
        $routes = new RouteBuilder($this->collection, '/api', ['prefix' => 'Api']);
        $routes->resources('Articles', ['_ext' => 'json']);

        $all = $this->collection->routes();
        $this->assertCount(5, $all);

        $this->assertSame('/api/articles', $all[4]->template);
        $this->assertEquals(
            ['controller', 'action', '_method', 'prefix', 'plugin'],
            array_keys($all[0]->defaults)
        );
        $this->assertSame('json', $all[0]->options['_ext']);
        $this->assertSame('Articles', $all[0]->defaults['controller']);
    }

    /**
     * Test connecting resources with a path
     *
     * @return void
     */
    public function testResourcesPathOption()
    {
        $routes = new RouteBuilder($this->collection, '/api');
        $routes->resources('Articles', ['path' => 'posts'], function (RouteBuilder $routes) {
            $routes->resources('Comments');
        });
        $all = $this->collection->routes();
        $this->assertSame('Articles', $all[8]->defaults['controller']);
        $this->assertSame('/api/posts', $all[8]->template);
        $this->assertSame('/api/posts/:id', $all[1]->template);
        $this->assertSame(
            '/api/posts/:article_id/comments',
            $all[4]->template,
            'parameter name should reflect resource name'
        );
    }

    /**
     * Test connecting resources with a prefix
     *
     * @return void
     */
    public function testResourcesPrefix()
    {
        $routes = new RouteBuilder($this->collection, '/api');
        $routes->resources('Articles', ['prefix' => 'Rest']);
        $all = $this->collection->routes();
        $this->assertSame('Rest', $all[0]->defaults['prefix']);
    }

    /**
     * Test that resource prefixes work within a prefixed scope.
     *
     * @return void
     */
    public function testResourcesNestedPrefix()
    {
        $routes = new RouteBuilder($this->collection, '/api', ['prefix' => 'Api']);
        $routes->resources('Articles', ['prefix' => 'Rest']);

        $all = $this->collection->routes();
        $this->assertCount(5, $all);

        $this->assertSame('/api/articles', $all[4]->template);
        foreach ($all as $route) {
            $this->assertSame('Api/Rest', $route->defaults['prefix']);
            $this->assertSame('Articles', $route->defaults['controller']);
        }
    }

    /**
     * Test connecting resources with the inflection option
     *
     * @return void
     */
    public function testResourcesInflection()
    {
        $routes = new RouteBuilder($this->collection, '/api', ['prefix' => 'Api']);
        $routes->resources('BlogPosts', ['_ext' => 'json', 'inflect' => 'dasherize']);

        $all = $this->collection->routes();
        $this->assertCount(5, $all);

        $this->assertSame('/api/blog-posts', $all[4]->template);
        $this->assertEquals(
            ['controller', 'action', '_method', 'prefix', 'plugin'],
            array_keys($all[0]->defaults)
        );
        $this->assertSame('BlogPosts', $all[0]->defaults['controller']);
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
            function (RouteBuilder $routes) {
                $routes->resources('Attributes');
            }
        );

        $all = $this->collection->routes();
        $this->assertCount(10, $all);

        $this->assertSame('/api/network-objects', $all[8]->template);
        $this->assertSame('/api/network-objects/:id', $all[2]->template);
        $this->assertSame('/api/network-objects/:network_object_id/attributes', $all[4]->template);
    }

    /**
     * Test connecting resources with additional mappings
     *
     * @return void
     */
    public function testResourcesMappings()
    {
        $routes = new RouteBuilder($this->collection, '/api', ['prefix' => 'Api']);
        $routes->resources('Articles', [
            '_ext' => 'json',
            'map' => [
                'delete_all' => ['action' => 'deleteAll', 'method' => 'DELETE'],
                'update_many' => ['action' => 'updateAll', 'method' => 'DELETE', 'path' => '/updateAll'],
            ],
        ]);

        $all = $this->collection->routes();
        $this->assertCount(7, $all);

        $this->assertSame('/api/articles/delete_all', $all[1]->template, 'Path defaults to key name.');
        $this->assertEquals(
            ['controller', 'action', '_method', 'prefix', 'plugin'],
            array_keys($all[5]->defaults)
        );
        $this->assertSame('Articles', $all[5]->defaults['controller']);
        $this->assertSame('deleteAll', $all[1]->defaults['action']);

        $this->assertSame('/api/articles/updateAll', $all[0]->template, 'Explicit path option');
        $this->assertEquals(
            ['controller', 'action', '_method', 'prefix', 'plugin'],
            array_keys($all[6]->defaults)
        );
        $this->assertSame('Articles', $all[6]->defaults['controller']);
        $this->assertSame('updateAll', $all[0]->defaults['action']);
    }

    /**
     * Test connecting resources with restricted mappings.
     *
     * @return void
     */
    public function testResourcesWithMapOnly()
    {
        $routes = new RouteBuilder($this->collection, '/api', ['prefix' => 'Api']);
        $routes->resources('Articles', [
            'map' => [
                'conditions' => ['action' => 'conditions', 'method' => 'DeLeTe'],
            ],
            'only' => ['conditions'],
        ]);

        $all = $this->collection->routes();
        $this->assertCount(1, $all);
        $this->assertSame('DELETE', $all[0]->defaults['_method'], 'method should be normalized.');
        $this->assertSame('Articles', $all[0]->defaults['controller']);
        $this->assertSame('conditions', $all[0]->defaults['action']);

        $result = $this->collection->parse('/api/articles/conditions', 'DELETE');
        $this->assertNotNull($result);
    }

    /**
     * Test connecting resources.
     *
     * @return void
     */
    public function testResourcesInScope()
    {
        Router::scope('/api', ['prefix' => 'Api'], function (RouteBuilder $routes) {
            $routes->setExtensions(['json']);
            $routes->resources('Articles');
        });
        $url = Router::url([
            'prefix' => 'Api',
            'controller' => 'Articles',
            'action' => 'edit',
            '_method' => 'PUT',
            'id' => '99',
        ]);
        $this->assertSame('/api/articles/99', $url);

        $url = Router::url([
            'prefix' => 'Api',
            'controller' => 'Articles',
            'action' => 'edit',
            '_method' => 'PUT',
            '_ext' => 'json',
            'id' => '99',
        ]);
        $this->assertSame('/api/articles/99.json', $url);
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

        $result = $this->collection->parse('/articles', 'GET');
        $this->assertSame('Articles', $result['controller']);
        $this->assertSame('index', $result['action']);
        $this->assertEquals([], $result['pass']);

        $result = $this->collection->parse('/articles/1', 'GET');
        $this->assertSame('Articles', $result['controller']);
        $this->assertSame('view', $result['action']);
        $this->assertEquals([1], $result['pass']);

        $result = $this->collection->parse('/articles', 'POST');
        $this->assertSame('Articles', $result['controller']);
        $this->assertSame('add', $result['action']);
        $this->assertEquals([], $result['pass']);

        $result = $this->collection->parse('/articles/1', 'PUT');
        $this->assertSame('Articles', $result['controller']);
        $this->assertSame('edit', $result['action']);
        $this->assertEquals([1], $result['pass']);

        $result = $this->collection->parse('/articles/1', 'DELETE');
        $this->assertSame('Articles', $result['controller']);
        $this->assertSame('delete', $result['action']);
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
        $this->assertSame('/articles', $result[0]->template);
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
        $this->assertSame('/articles', $result[1]->template);
        $this->assertSame('index', $result[1]->defaults['action']);
        $this->assertSame('GET', $result[1]->defaults['_method']);

        $this->assertSame('/articles/:id', $result[0]->template);
        $this->assertSame('delete', $result[0]->defaults['action']);
        $this->assertSame('DELETE', $result[0]->defaults['_method']);
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
            'actions' => ['index' => 'showList'],
        ]);

        $result = $this->collection->routes();
        $this->assertCount(2, $result);
        $this->assertSame('/articles', $result[1]->template);
        $this->assertSame('showList', $result[1]->defaults['action']);

        $this->assertSame('/articles/:id', $result[0]->template);
        $this->assertSame('delete', $result[0]->defaults['action']);
    }

    /**
     * Test nesting resources
     *
     * @return void
     */
    public function testResourcesNested()
    {
        $routes = new RouteBuilder($this->collection, '/api', ['prefix' => 'Api']);
        $routes->resources('Articles', function (RouteBuilder $routes) {
            $this->assertSame('/api/articles/', $routes->path());
            $this->assertEquals(['prefix' => 'Api'], $routes->params());

            $routes->resources('Comments');
            $route = $this->collection->routes()[3];
            $this->assertSame('/api/articles/:article_id/comments', $route->template);
        });
    }

    /**
     * Test connecting fallback routes.
     *
     * @return void
     */
    public function testFallbacks()
    {
        $routes = new RouteBuilder($this->collection, '/api', ['prefix' => 'Api']);
        $routes->fallbacks();

        $all = $this->collection->routes();
        $this->assertSame('/api/{controller}', $all[0]->template);
        $this->assertSame('/api/{controller}/{action}/*', $all[1]->template);
        $this->assertInstanceOf(Route::class, $all[0]);
    }

    /**
     * Test connecting fallback routes with specific route class
     *
     * @return void
     */
    public function testFallbacksWithClass()
    {
        $routes = new RouteBuilder($this->collection, '/api', ['prefix' => 'Api']);
        $routes->fallbacks('InflectedRoute');

        $all = $this->collection->routes();
        $this->assertSame('/api/{controller}', $all[0]->template);
        $this->assertSame('/api/{controller}/{action}/*', $all[1]->template);
        $this->assertInstanceOf(InflectedRoute::class, $all[0]);
    }

    /**
     * Test connecting fallback routes after setting default route class.
     *
     * @return void
     */
    public function testDefaultRouteClassFallbacks()
    {
        $routes = new RouteBuilder($this->collection, '/api', ['prefix' => 'Api']);
        $routes->setRouteClass('TestApp\Routing\Route\DashedRoute');
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
        $routes = new RouteBuilder($this->collection, '/api', ['prefix' => 'Api']);
        $routes->scope('/v1', ['version' => 1], function (RouteBuilder $routes) {
            $this->assertSame('/api/v1', $routes->path());
            $this->assertEquals(['prefix' => 'Api', 'version' => 1], $routes->params());
        });
    }

    /**
     * Test adding a scope with action in the scope
     *
     * @return void
     */
    public function testScopeWithAction()
    {
        $routes = new RouteBuilder($this->collection, '/api', ['prefix' => 'Api']);
        $routes->scope('/prices', ['controller' => 'Prices', 'action' => 'view'], function (RouteBuilder $routes) {
            $routes->connect('/shared', ['shared' => true]);
            $routes->get('/exclusive', ['exclusive' => true]);
        });
        $all = $this->collection->routes();
        $this->assertCount(2, $all);
        $this->assertSame('view', $all[0]->defaults['action']);
        $this->assertArrayHasKey('shared', $all[0]->defaults);

        $this->assertSame('view', $all[1]->defaults['action']);
        $this->assertArrayHasKey('exclusive', $all[1]->defaults);
    }

    /**
     * Test that exception is thrown if callback is not a valid callable.
     *
     * @return void
     */
    public function testScopeException()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Need a valid callable to connect routes. Got `string` instead.');

        $routes = new RouteBuilder($this->collection, '/api', ['prefix' => 'Api']);
        $routes->scope('/v1', 'fail');
    }

    /**
     * Test that nested scopes inherit middleware.
     *
     * @return void
     */
    public function testScopeInheritMiddleware()
    {
        $routes = new RouteBuilder(
            $this->collection,
            '/api',
            ['prefix' => 'Api'],
            ['middleware' => ['auth']]
        );
        $routes->scope('/v1', function (RouteBuilder $routes) {
            $this->assertSame(['auth'], $routes->getMiddleware(), 'Should inherit middleware');
            $this->assertSame('/api/v1', $routes->path());
            $this->assertEquals(['prefix' => 'Api'], $routes->params());
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
        $routes->scope('/v1', ['version' => 1, '_namePrefix' => 'v1:'], function (RouteBuilder $routes) {
            $this->assertSame('api:v1:', $routes->namePrefix());
            $routes->connect('/ping', ['controller' => 'Pings'], ['_name' => 'ping']);

            $routes->namePrefix('web:');
            $routes->connect('/pong', ['controller' => 'Pongs'], ['_name' => 'pong']);
        });

        $all = $this->collection->named();
        $this->assertArrayHasKey('api:v1:ping', $all);
        $this->assertArrayHasKey('web:pong', $all);
    }

    /**
     * Test adding middleware to the collection.
     *
     * @return void
     */
    public function testRegisterMiddleware()
    {
        $func = function () {
        };
        $routes = new RouteBuilder($this->collection, '/api');
        $result = $routes->registerMiddleware('test', $func);

        $this->assertSame($result, $routes);
        $this->assertTrue($this->collection->hasMiddleware('test'));
        $this->assertTrue($this->collection->middlewareExists('test'));
    }

    /**
     * Test middleware group
     *
     * @return void
     */
    public function testMiddlewareGroup()
    {
        $func = function () {
        };
        $routes = new RouteBuilder($this->collection, '/api');
        $routes->registerMiddleware('test', $func);
        $routes->registerMiddleware('test_two', $func);
        $result = $routes->middlewareGroup('group', ['test', 'test_two']);

        $this->assertSame($result, $routes);
        $this->assertTrue($this->collection->hasMiddlewareGroup('group'));
        $this->assertTrue($this->collection->middlewareExists('group'));
    }

    /**
     * Test overlap between middleware name and group name
     *
     * @return void
     */
    public function testMiddlewareGroupOverlap()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Cannot add middleware group \'test\'. A middleware by this name has already been registered.');
        $func = function () {
        };
        $routes = new RouteBuilder($this->collection, '/api');
        $routes->registerMiddleware('test', $func);
        $result = $routes->middlewareGroup('test', ['test']);
    }

    /**
     * Test applying middleware to a scope when it doesn't exist
     *
     * @return void
     */
    public function testApplyMiddlewareInvalidName()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Cannot apply \'bad\' middleware or middleware group. Use registerMiddleware() to register middleware');
        $routes = new RouteBuilder($this->collection, '/api');
        $routes->applyMiddleware('bad');
    }

    /**
     * Test applying middleware to a scope
     *
     * @return void
     */
    public function testApplyMiddleware()
    {
        $func = function () {
        };
        $routes = new RouteBuilder($this->collection, '/api');
        $routes->registerMiddleware('test', $func)
            ->registerMiddleware('test2', $func);
        $result = $routes->applyMiddleware('test', 'test2');

        $this->assertSame($result, $routes);
    }

    /**
     * Test that applyMiddleware() merges with previous data.
     *
     * @return void
     */
    public function testApplyMiddlewareMerges()
    {
        $func = function () {
        };
        $routes = new RouteBuilder($this->collection, '/api');
        $routes->registerMiddleware('test', $func)
            ->registerMiddleware('test2', $func);
        $routes->applyMiddleware('test');
        $routes->applyMiddleware('test2');

        $this->assertSame(['test', 'test2'], $routes->getMiddleware());
    }

    /**
     * Test that applyMiddleware() uses unique middleware set
     *
     * @return void
     */
    public function testApplyMiddlewareUnique()
    {
        $func = function () {
        };
        $routes = new RouteBuilder($this->collection, '/api');
        $routes->registerMiddleware('test', $func)
            ->registerMiddleware('test2', $func);

        $routes->applyMiddleware('test', 'test2');
        $routes->applyMiddleware('test2', 'test');

        $this->assertEquals(['test', 'test2'], $routes->getMiddleware());
    }

    /**
     * Test applying middleware results in middleware attached to the route.
     *
     * @return void
     */
    public function testApplyMiddlewareAttachToRoutes()
    {
        $func = function () {
        };
        $routes = new RouteBuilder($this->collection, '/api');
        $routes->registerMiddleware('test', $func)
            ->registerMiddleware('test2', $func);
        $routes->applyMiddleware('test', 'test2');
        $route = $routes->get('/docs', ['controller' => 'Docs']);

        $this->assertSame(['test', 'test2'], $route->getMiddleware());
    }

    /**
     * @return array
     */
    public static function httpMethodProvider()
    {
        return [
            ['GET'],
            ['POST'],
            ['PUT'],
            ['PATCH'],
            ['DELETE'],
            ['OPTIONS'],
            ['HEAD'],
        ];
    }

    /**
     * Test that the HTTP method helpers create the right kind of routes.
     *
     * @dataProvider httpMethodProvider
     * @return void
     */
    public function testHttpMethods($method)
    {
        $routes = new RouteBuilder($this->collection, '/', [], ['namePrefix' => 'app:']);
        $route = $routes->{strtolower($method)}(
            '/bookmarks/:id',
            ['controller' => 'Bookmarks', 'action' => 'view'],
            'route-name'
        );
        $this->assertInstanceOf(Route::class, $route, 'Should return a route');
        $this->assertSame($method, $route->defaults['_method']);
        $this->assertSame('app:route-name', $route->options['_name']);
        $this->assertSame('/bookmarks/:id', $route->template);
        $this->assertEquals(
            ['plugin' => null, 'controller' => 'Bookmarks', 'action' => 'view', '_method' => $method],
            $route->defaults
        );
    }

    /**
     * Test that the HTTP method helpers create the right kind of routes.
     *
     * @dataProvider httpMethodProvider
     * @return void
     */
    public function testHttpMethodsStringTarget($method)
    {
        $routes = new RouteBuilder($this->collection, '/', [], ['namePrefix' => 'app:']);
        $route = $routes->{strtolower($method)}(
            '/bookmarks/:id',
            'Bookmarks::view',
            'route-name'
        );
        $this->assertInstanceOf(Route::class, $route, 'Should return a route');
        $this->assertSame($method, $route->defaults['_method']);
        $this->assertSame('app:route-name', $route->options['_name']);
        $this->assertSame('/bookmarks/:id', $route->template);
        $this->assertEquals(
            ['plugin' => null, 'controller' => 'Bookmarks', 'action' => 'view', '_method' => $method],
            $route->defaults
        );
    }

    /**
     * Integration test for http method helpers and route fluent method
     *
     * @return void
     */
    public function testHttpMethodIntegration()
    {
        $routes = new RouteBuilder($this->collection, '/');
        $routes->scope('/', function (RouteBuilder $routes) {
            $routes->get('/faq/:page', ['controller' => 'Pages', 'action' => 'faq'], 'faq')
                ->setPatterns(['page' => '[a-z0-9_]+'])
                ->setHost('docs.example.com');

            $routes->post('/articles/:id', ['controller' => 'Articles', 'action' => 'update'], 'article:update')
                ->setPatterns(['id' => '[0-9]+'])
                ->setPass(['id']);
        });
        $this->assertCount(2, $this->collection->routes());
        $this->assertEquals(['faq', 'article:update'], array_keys($this->collection->named()));
        $this->assertNotEmpty($this->collection->parse('/faq/things_you_know', 'GET'));
        $result = $this->collection->parse('/articles/123', 'POST');
        $this->assertEquals(['123'], $result['pass']);
    }

    /**
     * Test loading routes from a missing plugin
     *
     * @return void
     */
    public function testLoadPluginBadPlugin()
    {
        $this->expectException(\Cake\Core\Exception\MissingPluginException::class);
        $routes = new RouteBuilder($this->collection, '/');
        $routes->loadPlugin('Nope');
    }

    /**
     * Test loading routes with success
     *
     * @return void
     */
    public function testLoadPlugin()
    {
        $this->loadPlugins(['TestPlugin']);
        $routes = new RouteBuilder($this->collection, '/');
        $routes->loadPlugin('TestPlugin');
        $this->assertCount(1, $this->collection->routes());
        $this->assertNotEmpty($this->collection->parse('/test_plugin', 'GET'));

        $plugin = Plugin::getCollection()->get('TestPlugin');
        $this->assertFalse($plugin->isEnabled('routes'), 'Hook should be disabled preventing duplicate routes');
    }
}
