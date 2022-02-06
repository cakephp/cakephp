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
 * @since         1.2.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Routing;

use Cake\Core\Configure;
use Cake\Http\ServerRequest;
use Cake\Http\ServerRequestFactory;
use Cake\Routing\Exception\DuplicateNamedRouteException;
use Cake\Routing\Exception\MissingRouteException;
use Cake\Routing\Route\Route;
use Cake\Routing\RouteBuilder;
use Cake\Routing\RouteCollection;
use Cake\Routing\Router;
use Cake\TestSuite\TestCase;
use Exception;
use InvalidArgumentException;
use RuntimeException;

/**
 * RouterTest class
 */
class RouterTest extends TestCase
{
    /**
     * setUp method
     */
    public function setUp(): void
    {
        parent::setUp();

        Configure::write('Error.ignoredDeprecationPaths', [
            'src/Routing/Router.php',
            'tests/TestCase/Routing/RouterTest.php',
        ]);
        Configure::write('Routing', ['prefixes' => []]);
        Router::reload();
    }

    /**
     * tearDown method
     */
    public function tearDown(): void
    {
        parent::tearDown();
        $this->clearPlugins();
        Router::defaultRouteClass('Cake\Routing\Route\Route');
    }

    /**
     * testFullBaseUrl method
     */
    public function testBaseUrl(): void
    {
        Router::scope('/', function (RouteBuilder $routes): void {
            $routes->fallbacks();
        });
        $this->assertMatchesRegularExpression('/^http(s)?:\/\//', Router::url('/', true));
        $this->assertMatchesRegularExpression('/^http(s)?:\/\//', Router::url(null, true));
        $this->assertMatchesRegularExpression('/^http(s)?:\/\//', Router::url(['controller' => 'Test', '_full' => true]));
    }

    /**
     * Tests that the base URL can be changed at runtime.
     */
    public function testFullBaseURL(): void
    {
        Router::scope('/', function (RouteBuilder $routes): void {
            $routes->fallbacks();
        });
        Router::fullBaseUrl('http://example.com');
        $this->assertSame('http://example.com/', Router::url('/', true));
        $this->assertSame('http://example.com', Configure::read('App.fullBaseUrl'));
        Router::fullBaseUrl('https://example.com');
        $this->assertSame('https://example.com/', Router::url('/', true));
        $this->assertSame('https://example.com', Configure::read('App.fullBaseUrl'));
    }

    /**
     * Test that Router uses App.base to build URL's when there are no stored
     * request objects.
     */
    public function testBaseUrlWithBasePath(): void
    {
        Configure::write('App.base', '/cakephp');
        Router::fullBaseUrl('http://example.com');
        $this->assertSame('http://example.com/cakephp/tasks', Router::url('/tasks', true));
    }

    /**
     * Test that Router uses App.base to build URL's when there are no stored
     * request objects.
     */
    public function testBaseUrlWithBasePathArrayUrl(): void
    {
        Configure::write('App.base', '/cakephp');
        Router::fullBaseUrl('http://example.com');
        Router::scope('/', function (RouteBuilder $routes): void {
            $routes->get('/{controller}', ['action' => 'index']);
        });

        $out = Router::url([
            'controller' => 'Tasks',
            'action' => 'index',
            '_method' => 'GET',
        ], true);
        $this->assertSame('http://example.com/cakephp/Tasks', $out);

        $out = Router::url([
            'controller' => 'Tasks',
            'action' => 'index',
            '_base' => false,
            '_method' => 'GET',
        ], true);
        $this->assertSame('http://example.com/Tasks', $out);
    }

    /**
     * Test that Router uses the correct url including base path for requesting the current actions.
     */
    public function testCurrentUrlWithBasePath(): void
    {
        Router::fullBaseUrl('http://example.com');
        $request = new ServerRequest([
            'params' => [
                'action' => 'view',
                'plugin' => null,
                'controller' => 'Pages',
                'pass' => ['1'],
            ],
            'here' => '/cakephp',
            'url' => '/cakephp/pages/view/1',
        ]);
        Router::setRequest($request);
        $this->assertSame('http://example.com/cakephp/pages/view/1', Router::url(null, true));
        $this->assertSame('/cakephp/pages/view/1', Router::url());
    }

    /**
     * Test that full base URL can be generated from request context too if
     * App.fullBaseUrl is not set.
     */
    public function testFullBaseURLFromRequest(): void
    {
        Configure::write('App.fullBaseUrl', false);
        $server = [
            'HTTP_HOST' => 'cake.local',
        ];

        $request = ServerRequestFactory::fromGlobals($server);
        Router::setRequest($request);
        $this->assertSame('http://cake.local', Router::fullBaseUrl());
    }

    /**
     * testRouteExists method
     */
    public function testRouteExists(): void
    {
        Router::connect('/posts/{action}', ['controller' => 'Posts']);
        $this->assertTrue(Router::routeExists(['controller' => 'Posts', 'action' => 'view']));

        $this->assertFalse(Router::routeExists(['action' => 'view', 'controller' => 'Users', 'plugin' => 'test']));
    }

    /**
     * testMultipleResourceRoute method
     */
    public function testMultipleResourceRoute(): void
    {
        Router::connect('/{controller}', ['action' => 'index', '_method' => ['GET', 'POST']]);

        $result = Router::parseRequest($this->makeRequest('/Posts', 'GET'));
        unset($result['_route']);
        $expected = [
            'pass' => [],
            'plugin' => null,
            'controller' => 'Posts',
            'action' => 'index',
            '_method' => ['GET', 'POST'],
            '_matchedRoute' => '/{controller}',
        ];
        $this->assertEquals($expected, $result);

        $result = Router::parseRequest($this->makeRequest('/Posts', 'POST'));
        unset($result['_route']);
        $expected = [
            'pass' => [],
            'plugin' => null,
            'controller' => 'Posts',
            'action' => 'index',
            '_method' => ['GET', 'POST'],
            '_matchedRoute' => '/{controller}',
        ];
        $this->assertEquals($expected, $result);
    }

    /**
     * testGenerateUrlResourceRoute method
     */
    public function testGenerateUrlResourceRoute(): void
    {
        Router::scope('/', function (RouteBuilder $routes): void {
            $routes->resources('Posts');
        });

        $result = Router::url([
            'controller' => 'Posts',
            'action' => 'index',
            '_method' => 'GET',
        ]);
        $expected = '/posts';
        $this->assertSame($expected, $result);

        $result = Router::url([
            'controller' => 'Posts',
            'action' => 'view',
            '_method' => 'GET',
            'id' => '10',
        ]);
        $expected = '/posts/10';
        $this->assertSame($expected, $result);

        $result = Router::url(['controller' => 'Posts', 'action' => 'add', '_method' => 'POST']);
        $expected = '/posts';
        $this->assertSame($expected, $result);

        $result = Router::url(['controller' => 'Posts', 'action' => 'edit', '_method' => 'PUT', 'id' => '10']);
        $expected = '/posts/10';
        $this->assertSame($expected, $result);

        $result = Router::url([
            'controller' => 'Posts',
            'action' => 'delete',
            '_method' => 'DELETE',
            'id' => '10',
        ]);
        $expected = '/posts/10';
        $this->assertSame($expected, $result);

        $result = Router::url([
            'controller' => 'Posts',
            'action' => 'edit',
            '_method' => 'PATCH',
            'id' => '10',
        ]);
        $expected = '/posts/10';
        $this->assertSame($expected, $result);
    }

    /**
     * testUrlNormalization method
     */
    public function testUrlNormalization(): void
    {
        Router::connect('/{controller}/{action}');

        $expected = '/users/logout';

        $result = Router::normalize('/users/logout/');
        $this->assertSame($expected, $result);

        $result = Router::normalize('//users//logout//');
        $this->assertSame($expected, $result);

        $result = Router::normalize('users/logout');
        $this->assertSame($expected, $result);

        $expected = '/Users/logout';
        $result = Router::normalize(['controller' => 'Users', 'action' => 'logout']);
        $this->assertSame($expected, $result);

        $result = Router::normalize('/');
        $this->assertSame('/', $result);

        $result = Router::normalize('http://google.com/');
        $this->assertSame('http://google.com/', $result);

        $result = Router::normalize('http://google.com//');
        $this->assertSame('http://google.com//', $result);

        $result = Router::normalize('/users/login/scope://foo');
        $this->assertSame('/users/login/scope:/foo', $result);

        $result = Router::normalize('/recipe/recipes/add');
        $this->assertSame('/recipe/recipes/add', $result);

        $request = new ServerRequest(['base' => '/us']);
        Router::setRequest($request);
        $result = Router::normalize('/us/users/logout/');
        $this->assertSame('/users/logout', $result);

        Router::reload();

        $request = new ServerRequest(['base' => '/cake_12']);
        Router::setRequest($request);
        $result = Router::normalize('/cake_12/users/logout/');
        $this->assertSame('/users/logout', $result);

        Router::reload();
        $_back = Configure::read('App.fullBaseUrl');
        Configure::write('App.fullBaseUrl', '/');

        $request = new ServerRequest();
        Router::setRequest($request);
        $result = Router::normalize('users/login');
        $this->assertSame('/users/login', $result);
        Configure::write('App.fullBaseUrl', $_back);

        Router::reload();
        $request = new ServerRequest(['base' => 'beer']);
        Router::setRequest($request);
        $result = Router::normalize('beer/admin/beers_tags/add');
        $this->assertSame('/admin/beers_tags/add', $result);

        $result = Router::normalize('/admin/beers_tags/add');
        $this->assertSame('/admin/beers_tags/add', $result);
    }

    /**
     * Test generating urls with base paths.
     */
    public function testUrlGenerationWithBasePath(): void
    {
        Router::connect('/{controller}/{action}/*');
        $request = new ServerRequest([
            'params' => [
                'action' => 'index',
                'plugin' => null,
                'controller' => 'Subscribe',
            ],
            'url' => '/subscribe',
            'base' => '/magazine',
            'webroot' => '/magazine/',
        ]);
        Router::setRequest($request);

        $result = Router::url();
        $this->assertSame('/magazine/subscribe', $result);

        $result = Router::url([]);
        $this->assertSame('/magazine/subscribe', $result);

        $result = Router::url('/');
        $this->assertSame('/magazine/', $result);

        $result = Router::url('/articles/');
        $this->assertSame('/magazine/articles/', $result);

        $result = Router::url('/articles::index');
        $this->assertSame('/magazine/articles::index', $result);

        $result = Router::url('/articles/view');
        $this->assertSame('/magazine/articles/view', $result);

        $result = Router::url(['controller' => 'Articles', 'action' => 'view', 1]);
        $this->assertSame('/magazine/Articles/view/1', $result);
    }

    /**
     * Test url() with _host option routes with request context
     */
    public function testUrlGenerationHostOptionRequestContext(): void
    {
        $server = [
            'HTTP_HOST' => 'foo.example.com',
            'DOCUMENT_ROOT' => '/Users/markstory/Sites',
            'SCRIPT_FILENAME' => '/Users/markstory/Sites/subdir/webroot/index.php',
            'PHP_SELF' => '/subdir/webroot/index.php/articles/view/1',
            'REQUEST_URI' => '/subdir/articles/view/1',
            'QUERY_STRING' => '',
            'SERVER_PORT' => 80,
        ];

        Router::connect('/fallback', ['controller' => 'Articles'], ['_host' => '*.example.com']);
        $request = ServerRequestFactory::fromGlobals($server);
        Router::setRequest($request);

        $result = Router::url(['controller' => 'Articles', 'action' => 'index']);
        $this->assertSame('http://foo.example.com/subdir/fallback', $result);

        $result = Router::url(['controller' => 'Articles', 'action' => 'index'], true);
        $this->assertSame('http://foo.example.com/subdir/fallback', $result);
    }

    /**
     * Test that catch all routes work with a variety of falsey inputs.
     */
    public function testUrlCatchAllRoute(): void
    {
        Router::connect('/*', ['controller' => 'Categories', 'action' => 'index']);
        $result = Router::url(['controller' => 'Categories', 'action' => 'index', '0']);
        $this->assertSame('/0', $result);

        $expected = [
            'plugin' => null,
            'controller' => 'Categories',
            'action' => 'index',
            'pass' => ['0'],
            '_matchedRoute' => '/*',
        ];
        $result = Router::parseRequest($this->makeRequest('/0', 'GET'));
        unset($result['_route']);
        $this->assertEquals($expected, $result);

        $result = Router::parseRequest($this->makeRequest('0', 'GET'));
        unset($result['_route']);
        $this->assertEquals($expected, $result);
    }

    /**
     * test generation of basic urls.
     */
    public function testUrlGenerationBasic(): void
    {
        /**
         * @var string $ID
         * @var string $UUID
         * @var string $Year
         * @var string $Month
         * @var string $Action
         */
        extract(Router::getNamedExpressions());

        Router::connect('/', ['controller' => 'Pages', 'action' => 'display', 'home']);
        $out = Router::url(['controller' => 'Pages', 'action' => 'display', 'home']);
        $this->assertSame('/', $out);

        Router::connect('/pages/*', ['controller' => 'Pages', 'action' => 'display']);
        $result = Router::url(['controller' => 'Pages', 'action' => 'display', 'about']);
        $expected = '/pages/about';
        $this->assertSame($expected, $result);

        Router::reload();
        Router::connect('/{plugin}/{id}/*', ['controller' => 'Posts', 'action' => 'view'], ['id' => $ID]);

        $result = Router::url([
            'plugin' => 'CakePlugin',
            'controller' => 'Posts',
            'action' => 'view',
            'id' => '1',
        ]);
        $expected = '/CakePlugin/1';
        $this->assertSame($expected, $result);

        $result = Router::url([
            'plugin' => 'CakePlugin',
            'controller' => 'Posts',
            'action' => 'view',
            'id' => '1',
            '0',
        ]);
        $expected = '/CakePlugin/1/0';
        $this->assertSame($expected, $result);

        Router::reload();
        Router::connect('/{controller}/{action}/{id}', [], ['id' => $ID]);

        $result = Router::url(['controller' => 'Posts', 'action' => 'view', 'id' => '1']);
        $expected = '/Posts/view/1';
        $this->assertSame($expected, $result);

        Router::reload();
        Router::connect('/{controller}/{id}', ['action' => 'view']);

        $result = Router::url(['controller' => 'Posts', 'action' => 'view', 'id' => '1']);
        $expected = '/Posts/1';
        $this->assertSame($expected, $result);

        Router::connect('/view/*', ['controller' => 'Posts', 'action' => 'view']);
        $result = Router::url(['controller' => 'Posts', 'action' => 'view', '1']);
        $expected = '/view/1';
        $this->assertSame($expected, $result);

        Router::reload();
        Router::connect('/{controller}/{action}');
        $request = new ServerRequest([
            'params' => [
                'action' => 'index',
                'plugin' => null,
                'controller' => 'Users',
            ],
        ]);
        Router::setRequest($request);

        $result = Router::url(['action' => 'login']);
        $expected = '/Users/login';
        $this->assertSame($expected, $result);

        Router::reload();
        Router::connect('/contact/{action}', ['plugin' => 'Contact', 'controller' => 'Contact']);

        $result = Router::url([
            'plugin' => 'Contact',
            'controller' => 'Contact',
            'action' => 'me',
        ]);

        $expected = '/contact/me';
        $this->assertSame($expected, $result);

        Router::reload();
        Router::connect('/{controller}', ['action' => 'index']);
        $request = new ServerRequest([
            'params' => [
                'action' => 'index',
                'plugin' => 'Myplugin',
                'controller' => 'Mycontroller',
            ],
        ]);
        Router::setRequest($request);

        $result = Router::url(['plugin' => null, 'controller' => 'Myothercontroller']);
        $expected = '/Myothercontroller';
        $this->assertSame($expected, $result);
    }

    /**
     * Test that generated names for routes are case-insensitive.
     */
    public function testRouteNameCasing(): void
    {
        Router::connect('/articles/{id}', ['controller' => 'Articles', 'action' => 'view']);
        Router::connect('/{controller}/{action}/*', [], ['routeClass' => 'InflectedRoute']);
        $result = Router::url(['controller' => 'Articles', 'action' => 'view', 'id' => 10]);
        $expected = '/articles/10';
        $this->assertSame($expected, $result);
    }

    /**
     * Test generation of routes with query string parameters.
     */
    public function testUrlGenerationWithQueryStrings(): void
    {
        Router::connect('/{controller}/{action}/*');

        $result = Router::url([
            'controller' => 'Posts',
            '0',
            '?' => ['var' => 'test', 'var2' => 'test2'],
        ]);
        $expected = '/Posts/index/0?var=test&var2=test2';
        $this->assertSame($expected, $result);

        $result = Router::url(['controller' => 'Posts', '0', '?' => ['var' => null]]);
        $this->assertSame('/Posts/index/0', $result);

        $result = Router::url([
            'controller' => 'Posts',
            '0',
            '?' => [
                'var' => 'test',
                'var2' => 'test2',
            ],
            '#' => 'unencoded string %',
        ]);
        $expected = '/Posts/index/0?var=test&var2=test2#unencoded string %';
        $this->assertSame($expected, $result);
    }

    /**
     * test that regex validation of keyed route params is working.
     */
    public function testUrlGenerationWithRegexQualifiedParams(): void
    {
        Router::connect(
            '{language}/galleries',
            ['controller' => 'Galleries', 'action' => 'index'],
            ['language' => '[a-z]{3}']
        );

        Router::connect(
            '/{language}/{admin}/{controller}/{action}/*',
            ['admin' => 'admin'],
            ['language' => '[a-z]{3}', 'admin' => 'admin']
        );

        Router::connect(
            '/{language}/{controller}/{action}/*',
            [],
            ['language' => '[a-z]{3}']
        );

        $result = Router::url(['admin' => false, 'language' => 'dan', 'action' => 'index', 'controller' => 'Galleries']);
        $expected = '/dan/galleries';
        $this->assertSame($expected, $result);

        $result = Router::url(['admin' => false, 'language' => 'eng', 'action' => 'index', 'controller' => 'Galleries']);
        $expected = '/eng/galleries';
        $this->assertSame($expected, $result);

        Router::reload();
        Router::connect(
            '/{language}/pages',
            ['controller' => 'Pages', 'action' => 'index'],
            ['language' => '[a-z]{3}']
        );
        Router::connect('/{language}/{controller}/{action}/*', [], ['language' => '[a-z]{3}']);

        $result = Router::url(['language' => 'eng', 'action' => 'index', 'controller' => 'Pages']);
        $expected = '/eng/pages';
        $this->assertSame($expected, $result);

        $result = Router::url(['language' => 'eng', 'controller' => 'Pages']);
        $this->assertSame($expected, $result);

        $result = Router::url(['language' => 'eng', 'controller' => 'Pages', 'action' => 'add']);
        $expected = '/eng/Pages/add';
        $this->assertSame($expected, $result);

        Router::reload();
        Router::connect(
            '/forestillinger/{month}/{year}/*',
            ['plugin' => 'Shows', 'controller' => 'Shows', 'action' => 'calendar'],
            ['month' => '0[1-9]|1[012]', 'year' => '[12][0-9]{3}']
        );

        $result = Router::url([
            'plugin' => 'Shows',
            'controller' => 'Shows',
            'action' => 'calendar',
            'month' => '10',
            'year' => '2007',
            'min-forestilling',
        ]);
        $expected = '/forestillinger/10/2007/min-forestilling';
        $this->assertSame($expected, $result);

        Router::reload();
        Router::connect(
            '/kalender/{month}/{year}/*',
            ['plugin' => 'Shows', 'controller' => 'Shows', 'action' => 'calendar'],
            ['month' => '0[1-9]|1[012]', 'year' => '[12][0-9]{3}']
        );
        Router::connect('/kalender/*', ['plugin' => 'Shows', 'controller' => 'Shows', 'action' => 'calendar']);

        $result = Router::url(['plugin' => 'Shows', 'controller' => 'Shows', 'action' => 'calendar', 'min-forestilling']);
        $expected = '/kalender/min-forestilling';
        $this->assertSame($expected, $result);

        $result = Router::url([
            'plugin' => 'Shows',
            'controller' => 'Shows',
            'action' => 'calendar',
            'year' => '2007',
            'month' => '10',
            'min-forestilling',
        ]);
        $expected = '/kalender/10/2007/min-forestilling';
        $this->assertSame($expected, $result);
    }

    /**
     * Test URL generation with an admin prefix
     */
    public function testUrlGenerationWithPrefix(): void
    {
        Router::reload();

        Router::connect('/pages/*', ['controller' => 'Pages', 'action' => 'display']);
        Router::connect('/reset/*', ['admin' => true, 'controller' => 'Users', 'action' => 'reset']);
        Router::connect('/tests', ['controller' => 'Tests', 'action' => 'index']);
        Router::connect('/admin/{controller}/{action}/*', ['prefix' => 'Admin']);
        Router::extensions('rss', false);

        $request = new ServerRequest([
            'params' => [
                'controller' => 'Registrations',
                'action' => 'index',
                'plugin' => null,
                'prefix' => 'Admin',
                '_ext' => 'html',
            ],
            'url' => '/admin/registrations/index',
        ]);
        Router::setRequest($request);

        $result = Router::url([]);
        $expected = '/admin/registrations/index';
        $this->assertSame($expected, $result);

        Router::reload();
        Router::connect('/admin/subscriptions/{action}/*', ['controller' => 'Subscribe', 'prefix' => 'Admin']);
        Router::connect('/admin/{controller}/{action}/*', ['prefix' => 'Admin']);

        $request = new ServerRequest([
            'params' => [
                'action' => 'index',
                'plugin' => null,
                'controller' => 'Subscribe',
                'prefix' => 'Admin',
            ],
            'webroot' => '/magazine/',
            'base' => '/magazine',
            'url' => '/admin/subscriptions/edit/1',
        ]);
        Router::setRequest($request);

        $result = Router::url(['action' => 'edit', 1]);
        $expected = '/magazine/admin/subscriptions/edit/1';
        $this->assertSame($expected, $result);

        $result = Router::url(['prefix' => 'Admin', 'controller' => 'Users', 'action' => 'login']);
        $expected = '/magazine/admin/Users/login';
        $this->assertSame($expected, $result);

        Router::reload();
        $request = new ServerRequest([
            'params' => [
                'prefix' => 'Admin',
                'action' => 'index',
                'plugin' => null,
                'controller' => 'Users',
            ],
            'webroot' => '/',
            'url' => '/admin/users/index',
        ]);
        Router::setRequest($request);

        Router::connect('/page/*', ['controller' => 'Pages', 'action' => 'view', 'prefix' => 'Admin']);

        $result = Router::url(['prefix' => 'Admin', 'controller' => 'Pages', 'action' => 'view', 'my-page']);
        $expected = '/page/my-page';
        $this->assertSame($expected, $result);

        Router::reload();
        Router::connect('/admin/{controller}/{action}/*', ['prefix' => 'Admin']);

        $request = new ServerRequest([
            'params' => [
                'plugin' => null,
                'controller' => 'Pages',
                'action' => 'add',
                'prefix' => 'Admin',
            ],
            'webroot' => '/',
            'url' => '/admin/pages/add',
        ]);
        Router::setRequest($request);

        $result = Router::url(['plugin' => null, 'controller' => 'Pages', 'action' => 'add', 'id' => false]);
        $expected = '/admin/Pages/add';
        $this->assertSame($expected, $result);

        Router::reload();
        Router::connect('/admin/{controller}/{action}/*', ['prefix' => 'Admin']);
        $request = new ServerRequest([
            'params' => [
                'plugin' => null,
                'controller' => 'Pages',
                'action' => 'add',
                'prefix' => 'Admin',
            ],
            'webroot' => '/',
            'url' => '/admin/pages/add',
        ]);
        Router::setRequest($request);

        $result = Router::url(['plugin' => null, 'controller' => 'Pages', 'action' => 'add', 'id' => false]);
        $expected = '/admin/Pages/add';
        $this->assertSame($expected, $result);

        Router::reload();
        Router::connect('/admin/{controller}/{action}/{id}', ['prefix' => 'Admin'], ['id' => '[0-9]+']);
        $request = new ServerRequest([
            'params' => [
                'plugin' => null,
                'controller' => 'Pages',
                'action' => 'edit',
                'pass' => ['284'],
                'prefix' => 'Admin',
            ],
            'url' => '/admin/pages/edit/284',
        ]);
        Router::setRequest($request);

        $result = Router::url(['plugin' => null, 'controller' => 'Pages', 'action' => 'edit', 'id' => '284']);
        $expected = '/admin/Pages/edit/284';
        $this->assertSame($expected, $result);

        Router::reload();
        Router::connect('/admin/{controller}/{action}/*', ['prefix' => 'Admin']);

        $request = new ServerRequest([
            'params' => [
                'plugin' => null, 'controller' => 'Pages', 'action' => 'add', 'prefix' => 'Admin',
            ],
            'url' => '/admin/pages/add',
        ]);
        Router::setRequest($request);

        $result = Router::url(['plugin' => null, 'controller' => 'Pages', 'action' => 'add', 'id' => false]);
        $expected = '/admin/Pages/add';
        $this->assertSame($expected, $result);

        Router::reload();
        Router::connect('/admin/{controller}/{action}/*', ['prefix' => 'Admin']);

        $request = new ServerRequest([
            'params' => [
                'plugin' => null, 'controller' => 'Pages', 'action' => 'edit', 'prefix' => 'Admin',
            ],
            'url' => '/admin/pages/edit/284',
        ]);
        Router::setRequest($request);

        $result = Router::url(['plugin' => null, 'controller' => 'Pages', 'action' => 'edit', 284]);
        $expected = '/admin/Pages/edit/284';
        $this->assertSame($expected, $result);

        Router::reload();
        Router::connect('/admin/posts/*', ['controller' => 'Posts', 'action' => 'index', 'prefix' => 'Admin']);
        $request = new ServerRequest([
            'params' => [
                'plugin' => null, 'controller' => 'Posts', 'action' => 'index', 'prefix' => 'Admin',
                'pass' => ['284'],
            ],
            'url' => '/admin/pages/edit/284',
        ]);
        Router::setRequest($request);

        $result = Router::url(['all']);
        $expected = '/admin/posts/all';
        $this->assertSame($expected, $result);
    }

    /**
     * Test URL generation inside a prefixed plugin.
     */
    public function testUrlGenerationPrefixedPlugin(): void
    {
        Router::prefix('admin', function (RouteBuilder $routes): void {
            $routes->plugin('MyPlugin', function (RouteBuilder $routes): void {
                $routes->fallbacks('InflectedRoute');
            });
        });
        $result = Router::url([
            'prefix' => 'Admin',
            'plugin' => 'MyPlugin',
            'controller' => 'Forms',
            'action' => 'edit',
            2,
        ]);
        $expected = '/admin/my-plugin/forms/edit/2';
        $this->assertSame($expected, $result);
    }

    /**
     * Test URL generation with multiple prefixes.
     */
    public function testUrlGenerationMultiplePrefixes(): void
    {
        Router::prefix('admin', function (RouteBuilder $routes): void {
            $routes->prefix('backoffice', function (RouteBuilder $routes): void {
                $routes->fallbacks('InflectedRoute');
            });
        });
        $result = Router::url([
            'prefix' => 'Admin/Backoffice',
            'controller' => 'Dashboards',
            'action' => 'home',
        ]);
        $expected = '/admin/backoffice/dashboards/home';
        $this->assertSame($expected, $result);
    }

    /**
     * testUrlGenerationWithExtensions method
     */
    public function testUrlGenerationWithExtensions(): void
    {
        Router::connect('/{controller}', ['action' => 'index']);
        Router::connect('/{controller}/{action}');

        $result = Router::url([
            'plugin' => null,
            'controller' => 'Articles',
            'action' => 'add',
            'id' => null,
            '_ext' => 'json',
        ]);
        $expected = '/Articles/add.json';
        $this->assertSame($expected, $result);

        $result = Router::url([
            'plugin' => null,
            'controller' => 'Articles',
            'action' => 'add',
            '_ext' => 'json',
        ]);
        $expected = '/Articles/add.json';
        $this->assertSame($expected, $result);

        $result = Router::url([
            'plugin' => null,
            'controller' => 'Articles',
            'action' => 'index',
            'id' => null,
            '_ext' => 'json',
        ]);
        $expected = '/Articles.json';
        $this->assertSame($expected, $result);

        $result = Router::url([
            'plugin' => null,
            'controller' => 'Articles',
            'action' => 'index',
            '?' => ['id' => 'testing'],
            '_ext' => 'json',
        ]);
        $expected = '/Articles.json?id=testing';
        $this->assertSame($expected, $result);
    }

    /**
     * test url() when the current request has an extension.
     */
    public function testUrlGenerationWithExtensionInCurrentRequest(): void
    {
        Router::extensions('rss');
        Router::scope('/', function (RouteBuilder $r): void {
            $r->fallbacks('InflectedRoute');
        });
        $request = new ServerRequest([
            'params' => ['plugin' => null, 'controller' => 'Tasks', 'action' => 'index', '_ext' => 'rss'],
        ]);
        Router::setRequest($request);

        $result = Router::url([
            'controller' => 'Tasks',
            'action' => 'view',
            1,
        ]);
        $expected = '/tasks/view/1';
        $this->assertSame($expected, $result);

        $result = Router::url([
            'controller' => 'Tasks',
            'action' => 'view',
            1,
            '_ext' => 'json',
        ]);
        $expected = '/tasks/view/1.json';
        $this->assertSame($expected, $result);
    }

    /**
     * Test url generation with named routes.
     */
    public function testUrlGenerationNamedRoute(): void
    {
        Router::connect(
            '/users',
            ['controller' => 'Users', 'action' => 'index'],
            ['_name' => 'users-index']
        );
        Router::connect(
            '/users/{name}',
            ['controller' => 'Users', 'action' => 'view'],
            ['_name' => 'test']
        );
        Router::connect(
            '/view/*',
            ['action' => 'view'],
            ['_name' => 'Articles::view']
        );

        $url = Router::url(['_name' => 'test', 'name' => 'mark']);
        $this->assertSame('/users/mark', $url);

        $url = Router::url([
            '_name' => 'test', 'name' => 'mark',
            '?' => ['page' => 1, 'sort' => 'title', 'dir' => 'desc', ],
        ]);
        $this->assertSame('/users/mark?page=1&sort=title&dir=desc', $url);

        $url = Router::url(['_name' => 'Articles::view']);
        $this->assertSame('/view/', $url);

        $url = Router::url(['_name' => 'Articles::view', '1']);
        $this->assertSame('/view/1', $url);

        $url = Router::url(['_name' => 'Articles::view', '_full' => true, '1']);
        $this->assertSame('http://localhost/view/1', $url);

        $url = Router::url(['_name' => 'Articles::view', '1', '#' => 'frag']);
        $this->assertSame('/view/1#frag', $url);
    }

    /**
     * Test that using invalid names causes exceptions.
     */
    public function testNamedRouteException(): void
    {
        $this->expectException(MissingRouteException::class);
        Router::connect(
            '/users/{name}',
            ['controller' => 'Users', 'action' => 'view'],
            ['_name' => 'test']
        );
        $url = Router::url(['_name' => 'junk', 'name' => 'mark']);
    }

    /**
     * Test that using duplicate names causes exceptions.
     */
    public function testDuplicateNamedRouteException(): void
    {
        $this->expectException(DuplicateNamedRouteException::class);
        Router::connect(
            '/users/{name}',
            ['controller' => 'Users', 'action' => 'view'],
            ['_name' => 'test']
        );
        Router::connect(
            '/users/{name}',
            ['controller' => 'Users', 'action' => 'view'],
            ['_name' => 'otherName']
        );
        Router::connect(
            '/users/{name}',
            ['controller' => 'Users', 'action' => 'view'],
            ['_name' => 'test']
        );
    }

    /**
     * Test that url filters are applied to url params.
     */
    public function testUrlGenerationWithUrlFilter(): void
    {
        Router::connect('/{lang}/{controller}/{action}/*');
        $request = new ServerRequest([
            'params' => [
                'plugin' => null,
                'lang' => 'en',
                'controller' => 'Posts',
                'action' => 'index',
            ],
        ]);
        Router::setRequest($request);

        $calledCount = 0;
        Router::addUrlFilter(function ($url, $request) use (&$calledCount) {
            $calledCount++;
            $url['lang'] = $request->getParam('lang');

            return $url;
        });
        Router::addUrlFilter(function ($url, $request) use (&$calledCount) {
            $calledCount++;
            $url[] = '1234';

            return $url;
        });
        $result = Router::url(['controller' => 'Tasks', 'action' => 'edit']);
        $this->assertSame('/en/Tasks/edit/1234', $result);
        $this->assertSame(2, $calledCount);
    }

    /**
     * Test that url filter failure gives better errors
     */
    public function testUrlGenerationWithUrlFilterFailureClosure(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches(
            '/URL filter defined in .*RouterTest\.php on line \d+ could not be applied\.' .
            ' The filter failed with: nope/'
        );
        Router::connect('/{lang}/{controller}/{action}/*');
        $request = new ServerRequest([
            'params' => [
                'plugin' => null,
                'lang' => 'en',
                'controller' => 'Posts',
                'action' => 'index',
            ],
        ]);
        Router::setRequest($request);

        Router::addUrlFilter(function ($url, $request): void {
            throw new RuntimeException('nope');
        });
        Router::url(['controller' => 'Posts', 'action' => 'index', 'lang' => 'en']);
    }

    /**
     * Test that url filter failure gives better errors
     */
    public function testUrlGenerationWithUrlFilterFailureMethod(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches(
            '/URL filter defined in .*RouterTest\.php on line \d+ could not be applied\.' .
            ' The filter failed with: /'
        );
        Router::connect('/{lang}/{controller}/{action}/*');
        $request = new ServerRequest([
            'params' => [
                'plugin' => null,
                'lang' => 'en',
                'controller' => 'Posts',
                'action' => 'index',
            ],
        ]);
        Router::setRequest($request);

        Router::addUrlFilter([$this, 'badFilter']);
        Router::url(['controller' => 'Posts', 'action' => 'index', 'lang' => 'en']);
    }

    /**
     * Testing stub for broken URL filters.
     *
     * @throws \RuntimeException
     */
    public function badFilter(): void
    {
        throw new RuntimeException('nope');
    }

    /**
     * Test url param persistence.
     */
    public function testUrlParamPersistence(): void
    {
        Router::connect('/{lang}/{controller}/{action}/*', [], ['persist' => ['lang']]);
        $request = new ServerRequest([
            'url' => '/en/posts/index',
            'params' => [
                'plugin' => null,
                'lang' => 'en',
                'controller' => 'Posts',
                'action' => 'index',
            ],
        ]);
        Router::setRequest($request);

        $result = Router::url(['controller' => 'Tasks', 'action' => 'edit', '1234']);
        $this->assertSame('/en/Tasks/edit/1234', $result);
    }

    /**
     * Test that plain strings urls work
     */
    public function testUrlGenerationPlainString(): void
    {
        $mailto = 'mailto:mark@example.com';
        $result = Router::url($mailto);
        $this->assertSame($mailto, $result);

        $js = 'javascript:alert("hi")';
        $result = Router::url($js);
        $this->assertSame($js, $result);

        $hash = '#first';
        $result = Router::url($hash);
        $this->assertSame($hash, $result);
    }

    /**
     * test that you can leave active plugin routes with plugin = null
     */
    public function testCanLeavePlugin(): void
    {
        Router::connect('/admin/{controller}', ['action' => 'index', 'prefix' => 'Admin']);
        Router::connect('/admin/{controller}/{action}/*', ['prefix' => 'Admin']);
        $request = new ServerRequest([
            'url' => '/admin/this/interesting/index',
            'params' => [
                'pass' => [],
                'prefix' => 'Admin',
                'plugin' => 'this',
                'action' => 'index',
                'controller' => 'Interesting',
            ],
        ]);
        Router::setRequest($request);
        $result = Router::url(['plugin' => null, 'controller' => 'Posts', 'action' => 'index']);
        $this->assertSame('/admin/Posts', $result);
    }

    /**
     * testUrlParsing method
     */
    public function testUrlParsing(): void
    {
        /**
         * @var string $ID
         * @var string $UUID
         * @var string $Year
         * @var string $Month
         * @var string $Day
         * @var string $Action
         */
        extract(Router::getNamedExpressions());

        Router::connect(
            '/posts/{value}/{somevalue}/{othervalue}/*',
            ['controller' => 'Posts', 'action' => 'view'],
            ['value', 'somevalue', 'othervalue']
        );
        $result = Router::parseRequest($this->makeRequest('/posts/2007/08/01/title-of-post-here', 'GET'));
        unset($result['_route']);
        $expected = [
            'value' => '2007',
            'somevalue' => '08',
            'othervalue' => '01',
            'controller' => 'Posts',
            'action' => 'view',
            'plugin' => null,
            'pass' => ['0' => 'title-of-post-here'],
            '_matchedRoute' => '/posts/{value}/{somevalue}/{othervalue}/*',
        ];
        $this->assertEquals($expected, $result);

        Router::reload();
        Router::connect(
            '/posts/{year}/{month}/{day}/*',
            ['controller' => 'Posts', 'action' => 'view'],
            ['year' => $Year, 'month' => $Month, 'day' => $Day]
        );
        $result = Router::parseRequest($this->makeRequest('/posts/2007/08/01/title-of-post-here', 'GET'));
        unset($result['_route']);
        $expected = [
            'year' => '2007',
            'month' => '08',
            'day' => '01',
            'controller' => 'Posts',
            'action' => 'view',
            'plugin' => null,
            'pass' => ['0' => 'title-of-post-here'],
            '_matchedRoute' => '/posts/{year}/{month}/{day}/*',
        ];
        $this->assertEquals($expected, $result);

        Router::reload();
        Router::connect(
            '/posts/{day}/{year}/{month}/*',
            ['controller' => 'Posts', 'action' => 'view'],
            ['year' => $Year, 'month' => $Month, 'day' => $Day]
        );
        $result = Router::parseRequest($this->makeRequest('/posts/01/2007/08/title-of-post-here', 'GET'));
        unset($result['_route']);
        $expected = [
            'day' => '01',
            'year' => '2007',
            'month' => '08',
            'controller' => 'Posts',
            'action' => 'view',
            'plugin' => null,
            'pass' => ['0' => 'title-of-post-here'],
            '_matchedRoute' => '/posts/{day}/{year}/{month}/*',
        ];
        $this->assertEquals($expected, $result);

        Router::reload();
        Router::connect(
            '/posts/{month}/{day}/{year}/*',
            ['controller' => 'Posts', 'action' => 'view'],
            ['year' => $Year, 'month' => $Month, 'day' => $Day]
        );
        $result = Router::parseRequest($this->makeRequest('/posts/08/01/2007/title-of-post-here', 'GET'));
        unset($result['_route']);
        $expected = [
            'month' => '08',
            'day' => '01',
            'year' => '2007',
            'controller' => 'Posts',
            'action' => 'view',
            'plugin' => null,
            'pass' => ['0' => 'title-of-post-here'],
            '_matchedRoute' => '/posts/{month}/{day}/{year}/*',
        ];
        $this->assertEquals($expected, $result);

        Router::reload();
        Router::connect(
            '/posts/{year}/{month}/{day}/*',
            ['controller' => 'Posts', 'action' => 'view']
        );
        $result = Router::parseRequest($this->makeRequest('/posts/2007/08/01/title-of-post-here', 'GET'));
        unset($result['_route']);
        $expected = [
            'year' => '2007',
            'month' => '08',
            'day' => '01',
            'controller' => 'Posts',
            'action' => 'view',
            'plugin' => null,
            'pass' => ['0' => 'title-of-post-here'],
            '_matchedRoute' => '/posts/{year}/{month}/{day}/*',
        ];
        $this->assertEquals($expected, $result);

        Router::reload();
        $this->_connectDefaultRoutes();
        $result = Router::parseRequest($this->makeRequest('/pages/display/home', 'GET'));
        unset($result['_route']);
        $expected = [
            'plugin' => null,
            'pass' => ['home'],
            'controller' => 'Pages',
            'action' => 'display',
            '_matchedRoute' => '/{controller}/{action}/*',
        ];
        $this->assertEquals($expected, $result);

        $result = Router::parseRequest($this->makeRequest('pages/display/home/', 'GET'));
        unset($result['_route']);
        $this->assertEquals($expected, $result);

        $result = Router::parseRequest($this->makeRequest('pages/display/home', 'GET'));
        unset($result['_route']);
        $this->assertEquals($expected, $result);

        Router::reload();
        Router::connect('/page/*', ['controller' => 'Test']);
        $result = Router::parseRequest($this->makeRequest('/page/my-page', 'GET'));
        unset($result['_route']);
        $expected = [
            'pass' => ['my-page'],
            'plugin' => null,
            'controller' => 'Test',
            'action' => 'index',
            '_matchedRoute' => '/page/*',
        ];
        $this->assertEquals($expected, $result);

        Router::reload();
        Router::connect(
            '/{language}/contact',
            ['language' => 'eng', 'plugin' => 'Contact', 'controller' => 'Contact', 'action' => 'index'],
            ['language' => '[a-z]{3}']
        );
        $result = Router::parseRequest($this->makeRequest('/eng/contact', 'GET'));
        unset($result['_route']);
        $expected = [
            'pass' => [],
            'language' => 'eng',
            'plugin' => 'Contact',
            'controller' => 'Contact',
            'action' => 'index',
            '_matchedRoute' => '/{language}/contact',
        ];
        $this->assertEquals($expected, $result);

        Router::reload();
        Router::connect(
            '/forestillinger/{month}/{year}/*',
            ['plugin' => 'Shows', 'controller' => 'Shows', 'action' => 'calendar'],
            ['month' => '0[1-9]|1[012]', 'year' => '[12][0-9]{3}']
        );

        $result = Router::parseRequest($this->makeRequest('/forestillinger/10/2007/min-forestilling', 'GET'));
        unset($result['_route']);
        $expected = [
            'pass' => ['min-forestilling'],
            'plugin' => 'Shows',
            'controller' => 'Shows',
            'action' => 'calendar',
            'year' => 2007,
            'month' => 10,
            '_matchedRoute' => '/forestillinger/{month}/{year}/*',
        ];
        $this->assertEquals($expected, $result);

        Router::reload();
        Router::connect('/{controller}/{action}/*');
        Router::connect('/', ['plugin' => 'pages', 'controller' => 'Pages', 'action' => 'display']);
        $result = Router::parseRequest($this->makeRequest('/', 'GET'));
        unset($result['_route']);
        $expected = [
            'pass' => [],
            'controller' => 'Pages',
            'action' => 'display',
            'plugin' => 'pages',
            '_matchedRoute' => '/',
        ];
        $this->assertEquals($expected, $result);

        $result = Router::parseRequest($this->makeRequest('/Posts/edit/0', 'GET'));
        unset($result['_route']);
        $expected = [
            'pass' => [0],
            'controller' => 'Posts',
            'action' => 'edit',
            'plugin' => null,
            '_matchedRoute' => '/{controller}/{action}/*',
        ];
        $this->assertEquals($expected, $result);

        Router::reload();
        Router::connect(
            '/Posts/{id}:{url_title}',
            ['controller' => 'Posts', 'action' => 'view'],
            ['pass' => ['id', 'url_title'], 'id' => '[\d]+']
        );
        $result = Router::parseRequest($this->makeRequest('/Posts/5:sample-post-title', 'GET'));
        unset($result['_route']);
        $expected = [
            'pass' => ['5', 'sample-post-title'],
            'id' => '5',
            'url_title' => 'sample-post-title',
            'plugin' => null,
            'controller' => 'Posts',
            'action' => 'view',
            '_matchedRoute' => '/Posts/{id}:{url_title}',
        ];
        $this->assertEquals($expected, $result);

        Router::reload();
        Router::connect(
            '/Posts/{id}:{url_title}/*',
            ['controller' => 'Posts', 'action' => 'view'],
            ['pass' => ['id', 'url_title'], 'id' => '[\d]+']
        );
        $result = Router::parseRequest($this->makeRequest('/Posts/5:sample-post-title/other/params/4', 'GET'));
        unset($result['_route']);
        $expected = [
            'pass' => ['5', 'sample-post-title', 'other', 'params', '4'],
            'id' => 5,
            'url_title' => 'sample-post-title',
            'plugin' => null,
            'controller' => 'Posts',
            'action' => 'view',
            '_matchedRoute' => '/Posts/{id}:{url_title}/*',
        ];
        $this->assertEquals($expected, $result);

        Router::reload();
        Router::connect('/posts/view/*', ['controller' => 'Posts', 'action' => 'view']);
        $result = Router::parseRequest($this->makeRequest('/posts/view/10?id=123&tab=abc', 'GET'));
        unset($result['_route']);
        $expected = [
            'pass' => [10],
            'plugin' => null,
            'controller' => 'Posts',
            'action' => 'view',
            '?' => ['id' => '123', 'tab' => 'abc'],
            '_matchedRoute' => '/posts/view/*',
        ];
        $this->assertEquals($expected, $result);

        Router::reload();
        Router::connect(
            '/posts/{url_title}-(uuid:{id})',
            ['controller' => 'Posts', 'action' => 'view'],
            ['pass' => ['id', 'url_title'], 'id' => $UUID]
        );
        $result = Router::parseRequest($this->makeRequest('/posts/sample-post-title-(uuid:47fc97a9-019c-41d1-a058-1fa3cbdd56cb)', 'GET'));
        unset($result['_route']);
        $expected = [
            'pass' => ['47fc97a9-019c-41d1-a058-1fa3cbdd56cb', 'sample-post-title'],
            'id' => '47fc97a9-019c-41d1-a058-1fa3cbdd56cb',
            'url_title' => 'sample-post-title',
            'plugin' => null,
            'controller' => 'Posts',
            'action' => 'view',
            '_matchedRoute' => '/posts/{url_title}-(uuid:{id})',
        ];
        $this->assertEquals($expected, $result);

        Router::reload();
        Router::connect('/posts/view/*', ['controller' => 'Posts', 'action' => 'view']);
        $result = Router::parseRequest($this->makeRequest('/posts/view/foo:bar/routing:fun', 'GET'));
        unset($result['_route']);
        $expected = [
            'pass' => ['foo:bar', 'routing:fun'],
            'plugin' => null,
            'controller' => 'Posts',
            'action' => 'view',
            '_matchedRoute' => '/posts/view/*',
        ];
        $this->assertEquals($expected, $result);
    }

    /**
     * test parseRequest
     */
    public function testParseRequest(): void
    {
        Router::connect('/articles/{action}/*', ['controller' => 'Articles']);
        $request = new ServerRequest(['url' => '/articles/view/1']);
        $result = Router::parseRequest($request);
        unset($result['_route']);
        $expected = [
            'pass' => ['1'],
            'plugin' => null,
            'controller' => 'Articles',
            'action' => 'view',
            '_matchedRoute' => '/articles/{action}/*',
        ];
        $this->assertEquals($expected, $result);
    }

    /**
     * testUuidRoutes method
     */
    public function testUuidRoutes(): void
    {
        Router::connect(
            '/subjects/add/{category_id}',
            ['controller' => 'Subjects', 'action' => 'add'],
            ['category_id' => '\w{8}-\w{4}-\w{4}-\w{4}-\w{12}']
        );
        $result = Router::parseRequest($this->makeRequest('/subjects/add/4795d601-19c8-49a6-930e-06a8b01d17b7', 'GET'));
        unset($result['_route']);
        $expected = [
            'pass' => [],
            'category_id' => '4795d601-19c8-49a6-930e-06a8b01d17b7',
            'plugin' => null,
            'controller' => 'Subjects',
            'action' => 'add',
            '_matchedRoute' => '/subjects/add/{category_id}',
        ];
        $this->assertEquals($expected, $result);
    }

    /**
     * testRouteSymmetry method
     */
    public function testRouteSymmetry(): void
    {
        Router::connect(
            '/{extra}/page/{slug}/*',
            ['controller' => 'Pages', 'action' => 'view', 'extra' => null],
            ['extra' => '[a-z1-9_]*', 'slug' => '[a-z1-9_]+', 'action' => 'view']
        );

        $result = Router::parseRequest($this->makeRequest('/some_extra/page/this_is_the_slug', 'GET'));
        unset($result['_route']);
        $expected = [
            'pass' => [],
            'plugin' => null,
            'controller' => 'Pages',
            'action' => 'view',
            'slug' => 'this_is_the_slug',
            'extra' => 'some_extra',
            '_matchedRoute' => '/{extra}/page/{slug}/*',
        ];
        $this->assertEquals($expected, $result);

        $result = Router::parseRequest($this->makeRequest('/page/this_is_the_slug', 'GET'));
        unset($result['_route']);
        $expected = [
            'pass' => [],
            'plugin' => null,
            'controller' => 'Pages',
            'action' => 'view',
            'slug' => 'this_is_the_slug',
            'extra' => null,
            '_matchedRoute' => '/{extra}/page/{slug}/*',
        ];
        $this->assertEquals($expected, $result);

        Router::reload();
        Router::connect(
            '/{extra}/page/{slug}/*',
            ['controller' => 'Pages', 'action' => 'view', 'extra' => null],
            ['extra' => '[a-z1-9_]*', 'slug' => '[a-z1-9_]+']
        );

        $result = Router::url([
            'plugin' => null,
            'controller' => 'Pages',
            'action' => 'view',
            'slug' => 'this_is_the_slug',
            'extra' => null,
        ]);
        $expected = '/page/this_is_the_slug';
        $this->assertSame($expected, $result);

        $result = Router::url([
            'plugin' => null,
            'controller' => 'Pages',
            'action' => 'view',
            'slug' => 'this_is_the_slug',
            'extra' => 'some_extra',
        ]);
        $expected = '/some_extra/page/this_is_the_slug';
        $this->assertSame($expected, $result);
    }

    /**
     * Test exceptions when parsing fails.
     */
    public function testParseError(): void
    {
        $this->expectException(MissingRouteException::class);
        Router::connect('/', ['controller' => 'Pages', 'action' => 'display', 'home']);
        Router::parseRequest($this->makeRequest('/nope', 'GET'));
    }

    /**
     * Test parse and reverse symmetry
     *
     * @dataProvider parseReverseSymmetryData
     */
    public function testParseReverseSymmetry(string $url): void
    {
        $this->_connectDefaultRoutes();
        $this->assertSame($url, Router::reverse(Router::parseRequest($this->makeRequest($url, 'GET')) + ['url' => []]));
    }

    /**
     * Data for parse and reverse test
     *
     * @return array
     */
    public function parseReverseSymmetryData(): array
    {
        return [
            ['/controller/action'],
            ['/controller/action/param'],
            ['/controller/action?param1=value1&param2=value2'],
            ['/controller/action/param?param1=value1'],
        ];
    }

    /**
     * testSetExtensions method
     */
    public function testSetExtensions(): void
    {
        Router::extensions('rss', false);
        $this->assertContains('rss', Router::extensions());

        $this->_connectDefaultRoutes();

        $result = Router::parseRequest($this->makeRequest('/posts.rss', 'GET'));
        $this->assertSame('rss', $result['_ext']);

        $result = Router::parseRequest($this->makeRequest('/posts.xml', 'GET'));
        $this->assertArrayNotHasKey('_ext', $result);

        Router::extensions(['xml']);
    }

    /**
     * Test that route builders propagate extensions to the top.
     */
    public function testExtensionsWithScopedRoutes(): void
    {
        Router::extensions(['json']);

        Router::scope('/', function (RouteBuilder $routes): void {
            $routes->setExtensions('rss');
            $routes->connect('/', ['controller' => 'Pages', 'action' => 'index']);

            $routes->scope('/api', function (RouteBuilder $routes): void {
                $routes->setExtensions('xml');
                $routes->connect('/docs', ['controller' => 'ApiDocs', 'action' => 'index']);
            });
        });

        $this->assertEquals(['json', 'rss', 'xml'], array_values(Router::extensions()));
    }

    /**
     * Test connecting resources.
     */
    public function testResourcesInScope(): void
    {
        Router::scope('/api', ['prefix' => 'Api'], function (RouteBuilder $routes): void {
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
     * testExtensionParsing method
     */
    public function testExtensionParsing(): void
    {
        Router::extensions('rss', false);
        $this->_connectDefaultRoutes();

        $result = Router::parseRequest($this->makeRequest('/posts.rss', 'GET'));
        unset($result['_route']);
        $expected = [
            'plugin' => null,
            'controller' => 'Posts',
            'action' => 'index',
            '_ext' => 'rss',
            'pass' => [],
            '_matchedRoute' => '/{controller}',
        ];
        $this->assertEquals($expected, $result);

        $result = Router::parseRequest($this->makeRequest('/posts/view/1.rss', 'GET'));
        unset($result['_route']);
        $expected = [
            'plugin' => null,
            'controller' => 'Posts',
            'action' => 'view',
            'pass' => ['1'],
            '_ext' => 'rss',
            '_matchedRoute' => '/{controller}/{action}/*',
        ];
        $this->assertEquals($expected, $result);

        $result = Router::parseRequest($this->makeRequest('/posts/view/1.rss?query=test', 'GET'));
        unset($result['_route']);
        $expected['?'] = ['query' => 'test'];
        $this->assertEquals($expected, $result);

        Router::reload();
        Router::extensions(['rss', 'xml'], false);
        $this->_connectDefaultRoutes();

        $result = Router::parseRequest($this->makeRequest('/posts.xml', 'GET'));
        unset($result['_route']);
        $expected = [
            'plugin' => null,
            'controller' => 'Posts',
            'action' => 'index',
            '_ext' => 'xml',
            'pass' => [],
            '_matchedRoute' => '/{controller}',
        ];
        $this->assertEquals($expected, $result);

        $result = Router::parseRequest($this->makeRequest('/posts.atom?hello=goodbye', 'GET'));
        unset($result['_route']);
        $expected = [
            'plugin' => null,
            'controller' => 'Posts.atom',
            'action' => 'index',
            'pass' => [],
            '?' => ['hello' => 'goodbye'],
            '_matchedRoute' => '/{controller}',
        ];
        $this->assertEquals($expected, $result);

        Router::reload();
        Router::connect('/controller/action', ['controller' => 'Controller', 'action' => 'action', '_ext' => 'rss']);
        $result = Router::parseRequest($this->makeRequest('/controller/action', 'GET'));
        unset($result['_route']);
        $expected = [
            'controller' => 'Controller',
            'action' => 'action',
            'plugin' => null,
            '_ext' => 'rss',
            'pass' => [],
            '_matchedRoute' => '/controller/action',
        ];
        $this->assertEquals($expected, $result);

        Router::reload();
        Router::connect('/controller/action', ['controller' => 'Controller', 'action' => 'action', '_ext' => 'rss']);
        $result = Router::parseRequest($this->makeRequest('/controller/action', 'GET'));
        unset($result['_route']);
        $expected = [
            'controller' => 'Controller',
            'action' => 'action',
            'plugin' => null,
            '_ext' => 'rss',
            'pass' => [],
            '_matchedRoute' => '/controller/action',
        ];
        $this->assertEquals($expected, $result);

        Router::reload();
        Router::extensions('rss', false);
        Router::connect('/controller/action', ['controller' => 'Controller', 'action' => 'action', '_ext' => 'rss']);
        $result = Router::parseRequest($this->makeRequest('/controller/action', 'GET'));
        unset($result['_route']);
        $expected = [
            'controller' => 'Controller',
            'action' => 'action',
            'plugin' => null,
            '_ext' => 'rss',
            'pass' => [],
            '_matchedRoute' => '/controller/action',
        ];
        $this->assertEquals($expected, $result);
    }

    /**
     * Test newer style automatically generated prefix routes.
     *
     * @see testUrlGenerationWithAutoPrefixes
     */
    public function testUrlGenerationWithAutoPrefixes(): void
    {
        Router::reload();
        Router::connect('/protected/{controller}/{action}/*', ['prefix' => 'Protected']);
        Router::connect('/admin/{controller}/{action}/*', ['prefix' => 'Admin']);
        Router::connect('/{controller}/{action}/*');

        $request = new ServerRequest([
            'url' => '/images/index',
            'params' => [
                'plugin' => null, 'controller' => 'Images', 'action' => 'index',
                'prefix' => null, 'protected' => false, 'url' => ['url' => 'images/index'],
            ],
        ]);
        Router::setRequest($request);

        $result = Router::url(['controller' => 'Images', 'action' => 'add']);
        $expected = '/Images/add';
        $this->assertSame($expected, $result);

        $result = Router::url(['controller' => 'Images', 'action' => 'add', 'prefix' => 'Protected']);
        $expected = '/protected/Images/add';
        $this->assertSame($expected, $result);

        $result = Router::url(['controller' => 'Images', 'action' => 'add_protected_test', 'prefix' => 'Protected']);
        $expected = '/protected/Images/add_protected_test';
        $this->assertSame($expected, $result);

        $result = Router::url(['action' => 'edit', 1]);
        $expected = '/Images/edit/1';
        $this->assertSame($expected, $result);

        $result = Router::url(['action' => 'edit', 1, 'prefix' => 'Protected']);
        $expected = '/protected/Images/edit/1';
        $this->assertSame($expected, $result);

        $result = Router::url(['action' => 'protectedEdit', 1, 'prefix' => 'Protected']);
        $expected = '/protected/Images/protectedEdit/1';
        $this->assertSame($expected, $result);

        $result = Router::url(['action' => 'edit', 1, 'prefix' => 'Protected']);
        $expected = '/protected/Images/edit/1';
        $this->assertSame($expected, $result);

        $result = Router::url(['controller' => 'Others', 'action' => 'edit', 1]);
        $expected = '/Others/edit/1';
        $this->assertSame($expected, $result);

        $result = Router::url(['controller' => 'Others', 'action' => 'edit', 1, 'prefix' => 'Protected']);
        $expected = '/protected/Others/edit/1';
        $this->assertSame($expected, $result);
    }

    /**
     * Test that the ssl option works.
     */
    public function testGenerationWithSslOption(): void
    {
        Router::fullBaseUrl('http://app.test');
        Router::connect('/{controller}/{action}/*');
        $request = new ServerRequest([
            'url' => '/images/index',
            'params' => [
                'plugin' => null, 'controller' => 'Images', 'action' => 'index',
            ],
            'environment' => ['HTTP_HOST' => 'localhost'],
        ]);
        Router::setRequest($request);

        $result = Router::url([
            '_ssl' => true,
        ]);
        $this->assertSame('https://app.test/Images/index', $result);

        $result = Router::url([
            '_ssl' => false,
        ]);
        $this->assertSame('http://app.test/Images/index', $result);
    }

    /**
     * Test ssl option when the current request is ssl.
     */
    public function testGenerateWithSslInSsl(): void
    {
        Router::connect('/{controller}/{action}/*');
        $request = new ServerRequest([
            'url' => '/images/index',
            'environment' => ['HTTP_HOST' => 'app.test', 'HTTPS' => 'on'],
            'params' => [
                'plugin' => null,
                'controller' => 'Images',
                'action' => 'index',
            ],
        ]);
        Router::setRequest($request);

        $result = Router::url([
            '_ssl' => false,
        ]);
        $this->assertSame('http://app.test/Images/index', $result);

        $result = Router::url([
            '_ssl' => true,
        ]);
        $this->assertSame('https://app.test/Images/index', $result);
    }

    /**
     * test that prefix routes persist when they are in the current request.
     */
    public function testPrefixRoutePersistence(): void
    {
        Router::reload();
        Router::connect('/protected/{controller}/{action}', ['prefix' => 'Protected']);
        Router::connect('/{controller}/{action}');

        $request = new ServerRequest([
            'url' => '/protected/images/index',
            'params' => [
                'plugin' => null,
                'controller' => 'Images',
                'action' => 'index',
                'prefix' => 'Protected',
            ],
        ]);
        Router::setRequest($request);

        $result = Router::url(['prefix' => 'Protected', 'controller' => 'Images', 'action' => 'add']);
        $expected = '/protected/Images/add';
        $this->assertSame($expected, $result);

        $result = Router::url(['controller' => 'Images', 'action' => 'add']);
        $expected = '/protected/Images/add';
        $this->assertSame($expected, $result);

        $result = Router::url(['controller' => 'Images', 'action' => 'add', 'prefix' => false]);
        $expected = '/Images/add';
        $this->assertSame($expected, $result);
    }

    /**
     * test that setting a prefix override the current one
     */
    public function testPrefixOverride(): void
    {
        Router::connect('/admin/{controller}/{action}', ['prefix' => 'Admin']);
        Router::connect('/protected/{controller}/{action}', ['prefix' => 'Protected']);

        $request = new ServerRequest([
            'url' => '/protected/images/index',
            'params' => [
                'plugin' => null, 'controller' => 'Images', 'action' => 'index', 'prefix' => 'Protected',
            ],
        ]);
        Router::setRequest($request);

        $result = Router::url(['controller' => 'Images', 'action' => 'add', 'prefix' => 'Admin']);
        $expected = '/admin/Images/add';
        $this->assertSame($expected, $result);

        $request = new ServerRequest([
            'url' => '/admin/images/index',
            'params' => [
                'plugin' => null, 'controller' => 'Images', 'action' => 'index', 'prefix' => 'Admin',
            ],
        ]);
        Router::setRequest($request);
        $result = Router::url(['controller' => 'Images', 'action' => 'add', 'prefix' => 'Protected']);
        $expected = '/protected/Images/add';
        $this->assertSame($expected, $result);
    }

    /**
     * Test that well known route parameters are passed through.
     */
    public function testRouteParamDefaults(): void
    {
        Router::connect('/cache/*', ['prefix' => false, 'plugin' => true, 'controller' => 0, 'action' => 1]);

        $url = Router::url(['prefix' => '0', 'plugin' => '1', 'controller' => '0', 'action' => '1', 'test']);
        $expected = '/cache/test';
        $this->assertSame($expected, $url);

        try {
            Router::url(['controller' => '0', 'action' => '1', 'test']);
            $this->fail('No exception raised');
        } catch (Exception $e) {
            $this->assertTrue(true, 'Exception was raised');
        }

        try {
            Router::url(['prefix' => '1', 'controller' => '0', 'action' => '1', 'test']);
            $this->fail('No exception raised');
        } catch (Exception $e) {
            $this->assertTrue(true, 'Exception was raised');
        }
    }

    /**
     * testRemoveBase method
     */
    public function testRemoveBase(): void
    {
        Router::connect('/{controller}/{action}');
        $request = new ServerRequest([
            'url' => '/',
            'base' => '/base',
            'params' => [
                'plugin' => null, 'controller' => 'Controller', 'action' => 'index',
            ],
        ]);
        Router::setRequest($request);

        $result = Router::url(['controller' => 'MyController', 'action' => 'myAction']);
        $expected = '/base/MyController/myAction';
        $this->assertSame($expected, $result);

        $result = Router::url(['controller' => 'MyController', 'action' => 'myAction', '_base' => false]);
        $expected = '/MyController/myAction';
        $this->assertSame($expected, $result);
    }

    /**
     * testPagesUrlParsing method
     */
    public function testPagesUrlParsing(): void
    {
        Router::connect('/', ['controller' => 'Pages', 'action' => 'display', 'home']);
        Router::connect('/pages/*', ['controller' => 'Pages', 'action' => 'display']);

        $result = Router::parseRequest($this->makeRequest('/', 'GET'));
        unset($result['_route']);
        $expected = [
            'pass' => ['home'],
            'plugin' => null,
            'controller' => 'Pages',
            'action' => 'display',
            '_matchedRoute' => '/',
        ];
        $this->assertEquals($expected, $result);

        $result = Router::parseRequest($this->makeRequest('/pages/home/', 'GET'));
        unset($result['_route']);
        $expected = [
            'pass' => ['home'],
            'plugin' => null,
            'controller' => 'Pages',
            'action' => 'display',
            '_matchedRoute' => '/pages/*',
        ];
        $this->assertEquals($expected, $result);

        Router::reload();
        Router::connect('/', ['controller' => 'Pages', 'action' => 'display', 'home']);

        $result = Router::parseRequest($this->makeRequest('/', 'GET'));
        unset($result['_route']);
        $expected = [
            'pass' => ['home'],
            'plugin' => null,
            'controller' => 'Pages',
            'action' => 'display',
            '_matchedRoute' => '/',
        ];
        $this->assertEquals($expected, $result);

        Router::reload();
        Router::connect('/', ['controller' => 'Posts', 'action' => 'index']);
        Router::connect('/pages/*', ['controller' => 'Pages', 'action' => 'display']);
        $result = Router::parseRequest($this->makeRequest('/pages/contact/', 'GET'));
        unset($result['_route']);

        $expected = [
            'pass' => ['contact'],
            'plugin' => null,
            'controller' => 'Pages',
            'action' => 'display',
            '_matchedRoute' => '/pages/*',
        ];
        $this->assertEquals($expected, $result);
    }

    /**
     * test that requests with a trailing dot don't loose the do.
     */
    public function testParsingWithTrailingPeriod(): void
    {
        Router::reload();
        Router::connect('/{controller}/{action}/*');
        $result = Router::parseRequest($this->makeRequest('/posts/view/something.', 'GET'));
        $this->assertSame('something.', $result['pass'][0], 'Period was chopped off');

        $result = Router::parseRequest($this->makeRequest('/posts/view/something. . .', 'GET'));
        $this->assertSame('something. . .', $result['pass'][0], 'Period was chopped off');
    }

    /**
     * test that requests with a trailing dot don't loose the do.
     */
    public function testParsingWithTrailingPeriodAndParseExtensions(): void
    {
        Router::reload();
        Router::connect('/{controller}/{action}/*');

        $result = Router::parseRequest($this->makeRequest('/posts/view/something.', 'GET'));
        $this->assertSame('something.', $result['pass'][0], 'Period was chopped off');

        $result = Router::parseRequest($this->makeRequest('/posts/view/something. . .', 'GET'));
        $this->assertSame('something. . .', $result['pass'][0], 'Period was chopped off');
    }

    /**
     * test that patterns work for {action}
     */
    public function testParsingWithPatternOnAction(): void
    {
        $this->expectException(MissingRouteException::class);
        Router::connect(
            '/blog/{action}/*',
            ['controller' => 'BlogPosts'],
            ['action' => 'other|actions']
        );

        $result = Router::parseRequest($this->makeRequest('/blog/other', 'GET'));
        unset($result['_route']);
        $expected = [
            'plugin' => null,
            'controller' => 'BlogPosts',
            'action' => 'other',
            'pass' => [],
            '_matchedRoute' => '/blog/{action}/*',
        ];
        $this->assertEquals($expected, $result);

        Router::parseRequest($this->makeRequest('/blog/foobar', 'GET'));
    }

    /**
     * Test parseRoutePath() with valid strings
     */
    public function testParseRoutePath(): void
    {
        $expected = [
            'controller' => 'Bookmarks',
            'action' => 'view',
        ];
        $this->assertSame($expected, Router::parseRoutePath('Bookmarks::view'));

        $expected = [
            'prefix' => 'Admin',
            'controller' => 'Bookmarks',
            'action' => 'view',
        ];
        $this->assertSame($expected, Router::parseRoutePath('Admin/Bookmarks::view'));

        $expected = [
            'prefix' => 'LongPrefix/BackEnd',
            'controller' => 'Bookmarks',
            'action' => 'view',
        ];
        $this->assertSame($expected, Router::parseRoutePath('LongPrefix/BackEnd/Bookmarks::view'));

        $expected = [
            'plugin' => 'Cms',
            'controller' => 'Articles',
            'action' => 'edit',
        ];
        $this->assertSame($expected, Router::parseRoutePath('Cms.Articles::edit'));

        $expected = [
            'plugin' => 'Vendor/Cms',
            'prefix' => 'Management/Admin',
            'controller' => 'Articles',
            'action' => 'view',
        ];
        $this->assertSame($expected, Router::parseRoutePath('Vendor/Cms.Management/Admin/Articles::view'));
    }

    /**
     * @return array
     */
    public function invalidRoutePathProvider(): array
    {
        return [
            ['view'],
            ['Bookmarks:view'],
            ['Bookmarks/view'],
            ['Vendor\Cms.Articles::edit'],
            ['Vendor//Cms.Articles::edit'],
            ['Cms./Articles::edit'],
            ['Cms./Admin/Articles::edit'],
            ['Cms.Admin//Articles::edit'],
            ['Vendor\Cms.Management\Admin\Articles::edit'],
        ];
    }

    /**
     * Test parseRoutePath() with invalid strings
     *
     * @dataProvider invalidRoutePathProvider
     */
    public function testParseInvalidRoutePath(string $value): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Could not parse a string route path');

        Router::parseRoutePath($value);
    }

    /**
     * Tests that convenience wrapper urlArray() works as the internal
     * Router::parseRoutePath() does.
     */
    public function testUrlArray(): void
    {
        $expected = [
            'controller' => 'Bookmarks',
            'action' => 'view',
            'plugin' => false,
            'prefix' => false,
        ];
        $this->assertSame($expected, urlArray('Bookmarks::view'));

        $expected = [
            'prefix' => 'Admin',
            'controller' => 'Bookmarks',
            'action' => 'view',
            'plugin' => false,
        ];
        $this->assertSame($expected, urlArray('Admin/Bookmarks::view'));

        $expected = [
            'plugin' => 'Vendor/Cms',
            'prefix' => 'Management/Admin',
            'controller' => 'Articles',
            'action' => 'view',
            3,
            '?' => ['query' => 'string'],
        ];
        $params = [3, '?' => ['query' => 'string']];
        $this->assertSame($expected, urlArray('Vendor/Cms.Management/Admin/Articles::view', $params));
    }

    /**
     * Test url() works with patterns on {action}
     */
    public function testUrlPatternOnAction(): void
    {
        $this->expectException(MissingRouteException::class);
        Router::connect(
            '/blog/{action}/*',
            ['controller' => 'BlogPosts'],
            ['action' => 'other|actions']
        );

        $result = Router::url(['controller' => 'BlogPosts', 'action' => 'actions']);
        $this->assertSame('/blog/actions', $result);

        $result = Router::url(['controller' => 'BlogPosts', 'action' => 'foo']);
        $this->assertSame('/', $result);
    }

    /**
     * testParsingWithLiteralPrefixes method
     */
    public function testParsingWithLiteralPrefixes(): void
    {
        Router::reload();
        $adminParams = ['prefix' => 'Admin'];
        Router::connect('/admin/{controller}', $adminParams);
        Router::connect('/admin/{controller}/{action}/*', $adminParams);

        $request = new ServerRequest([
            'url' => '/',
            'base' => '/base',
            'params' => ['plugin' => null, 'controller' => 'Controller', 'action' => 'index'],
        ]);
        Router::setRequest($request);

        $result = Router::parseRequest($this->makeRequest('/admin/Posts/', 'GET'));
        unset($result['_route']);
        $expected = [
            'pass' => [],
            'prefix' => 'Admin',
            'plugin' => null,
            'controller' => 'Posts',
            'action' => 'index',
            '_matchedRoute' => '/admin/{controller}',
        ];
        $this->assertEquals($expected, $result);

        $result = Router::parseRequest($this->makeRequest('/admin/Posts', 'GET'));
        unset($result['_route']);
        $this->assertEquals($expected, $result);

        $result = Router::url(['prefix' => 'Admin', 'controller' => 'Posts']);
        $expected = '/base/admin/Posts';
        $this->assertSame($expected, $result);

        Router::reload();

        $prefixParams = ['prefix' => 'Members'];
        Router::connect('/members/{controller}', $prefixParams);
        Router::connect('/members/{controller}/{action}', $prefixParams);
        Router::connect('/members/{controller}/{action}/*', $prefixParams);

        $request = new ServerRequest([
            'url' => '/',
            'base' => '/base',
            'params' => ['plugin' => null, 'controller' => 'Controller', 'action' => 'index'],
        ]);
        Router::setRequest($request);

        $result = Router::parseRequest($this->makeRequest('/members/Posts/index', 'GET'));
        unset($result['_route']);
        $expected = [
            'pass' => [],
            'prefix' => 'Members',
            'plugin' => null,
            'controller' => 'Posts',
            'action' => 'index',
            '_matchedRoute' => '/members/{controller}/{action}',
        ];
        $this->assertEquals($expected, $result);

        $result = Router::url(['prefix' => 'Members', 'controller' => 'Users', 'action' => 'add']);
        $expected = '/base/members/Users/add';
        $this->assertSame($expected, $result);
    }

    /**
     * Tests URL generation with flags and prefixes in and out of context
     */
    public function testUrlWritingWithPrefixes(): void
    {
        Router::connect('/company/{controller}/{action}/*', ['prefix' => 'Company']);
        Router::connect('/{action}', ['controller' => 'Users']);

        $result = Router::url(['controller' => 'Users', 'action' => 'login', 'prefix' => 'Company']);
        $expected = '/company/Users/login';
        $this->assertSame($expected, $result);

        $request = new ServerRequest([
            'url' => '/',
            'params' => [
                'plugin' => null,
                'controller' => 'Users',
                'action' => 'login',
                'prefix' => 'Company',
            ],
        ]);
        Router::setRequest($request);

        $result = Router::url(['controller' => 'Users', 'action' => 'login', 'prefix' => false]);
        $expected = '/login';
        $this->assertSame($expected, $result);
    }

    /**
     * test url generation with prefixes and custom routes
     */
    public function testUrlWritingWithPrefixesAndCustomRoutes(): void
    {
        Router::connect(
            '/admin/login',
            ['controller' => 'Users', 'action' => 'login', 'prefix' => 'Admin']
        );
        $request = new ServerRequest([
            'url' => '/',
            'params' => [
                'plugin' => null,
                'controller' => 'Posts',
                'action' => 'index',
                'prefix' => 'Admin',
            ],
            'webroot' => '/',
        ]);
        Router::setRequest($request);
        $result = Router::url(['controller' => 'Users', 'action' => 'login']);
        $this->assertSame('/admin/login', $result);

        $result = Router::url(['controller' => 'Users', 'action' => 'login']);
        $this->assertSame('/admin/login', $result);
    }

    /**
     * testPassedArgsOrder method
     */
    public function testPassedArgsOrder(): void
    {
        Router::connect('/test-passed/*', ['controller' => 'Pages', 'action' => 'display', 'home']);
        Router::connect('/test2/*', ['controller' => 'Pages', 'action' => 'display', 2]);
        Router::connect('/test/*', ['controller' => 'Pages', 'action' => 'display', 1]);

        $result = Router::url(['controller' => 'Pages', 'action' => 'display', 1, 'whatever']);
        $expected = '/test/whatever';
        $this->assertSame($expected, $result);

        $result = Router::url(['controller' => 'Pages', 'action' => 'display', 2, 'whatever']);
        $expected = '/test2/whatever';
        $this->assertSame($expected, $result);

        $result = Router::url(['controller' => 'Pages', 'action' => 'display', 'home', 'whatever']);
        $expected = '/test-passed/whatever';
        $this->assertSame($expected, $result);
    }

    /**
     * testRegexRouteMatching method
     */
    public function testRegexRouteMatching(): void
    {
        Router::connect('/{locale}/{controller}/{action}/*', [], ['locale' => 'dan|eng']);

        $result = Router::parseRequest($this->makeRequest('/eng/Test/testAction', 'GET'));
        unset($result['_route']);
        $expected = [
            'pass' => [],
            'locale' => 'eng',
            'controller' => 'Test',
            'action' => 'testAction',
            'plugin' => null,
            '_matchedRoute' => '/{locale}/{controller}/{action}/*',
        ];
        $this->assertEquals($expected, $result);
    }

    /**
     * testRegexRouteMatching method
     */
    public function testRegexRouteMatchUrl(): void
    {
        $this->expectException(MissingRouteException::class);
        Router::connect('/{locale}/{controller}/{action}/*', [], ['locale' => 'dan|eng']);

        $request = new ServerRequest([
            'url' => '/test/test_action',
            'params' => [
                'plugin' => null,
                'controller' => 'Test',
                'action' => 'index',
            ],
            'webroot' => '/',
        ]);
        Router::setRequest($request);

        $result = Router::url(['action' => 'testAnotherAction', 'locale' => 'eng']);
        $expected = '/eng/Test/testAnotherAction';
        $this->assertSame($expected, $result);

        $result = Router::url(['action' => 'testAnotherAction']);
        $expected = '/';
        $this->assertSame($expected, $result);
    }

    /**
     * test using a custom route class for route connection
     */
    public function testUsingCustomRouteClass(): void
    {
        $this->loadPlugins(['TestPlugin']);
        Router::connect(
            '/{slug}',
            ['plugin' => 'TestPlugin', 'action' => 'index'],
            ['routeClass' => 'PluginShortRoute', 'slug' => '[a-z_-]+']
        );
        $result = Router::parseRequest($this->makeRequest('/the-best', 'GET'));
        unset($result['_route']);
        $expected = [
            'plugin' => 'TestPlugin',
            'controller' => 'TestPlugin',
            'action' => 'index',
            'slug' => 'the-best',
            'pass' => [],
            '_matchedRoute' => '/{slug}',
        ];
        $this->assertEquals($expected, $result);
    }

    /**
     * test using custom route class in PluginDot notation
     */
    public function testUsingCustomRouteClassPluginDotSyntax(): void
    {
        $this->loadPlugins(['TestPlugin']);
        Router::connect(
            '/{slug}',
            ['controller' => 'Posts', 'action' => 'view'],
            ['routeClass' => 'TestPlugin.TestRoute', 'slug' => '[a-z_-]+']
        );
        $this->assertTrue(true); // Just to make sure the connect do not throw exception
        $this->removePlugins(['TestPlugin']);
    }

    /**
     * test that route classes must extend \Cake\Routing\Route\Route
     */
    public function testCustomRouteException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Router::connect('/{controller}', [], ['routeClass' => 'Object']);
    }

    public function testReverseLocalized(): void
    {
        Router::reload();
        Router::connect('/{lang}/{controller}/{action}/*', [], ['lang' => '[a-z]{3}']);
        $params = [
            'lang' => 'eng',
            'controller' => 'Posts',
            'action' => 'view',
            'pass' => [1],
            'url' => ['url' => 'eng/posts/view/1'],
        ];
        $result = Router::reverse($params);
        $this->assertSame('/eng/Posts/view/1', $result);
    }

    public function testReverseRouteKeyAndPass(): void
    {
        Router::reload();
        $routes = Router::createRouteBuilder('/');
        $routes->connect('/articles/{slug}', ['controller' => 'Articles', 'action' => 'view'])->setPass(['slug']);

        $request = new ServerRequest([
            'url' => '/articles/first-post',
            'params' => [
                'lang' => 'eng',
                'controller' => 'Articles',
                'action' => 'view',
                'pass' => ['first-post'],
                'slug' => 'first-post',
                '_matchedRoute' => '/articles/{slug}',
            ],
        ]);
        $result = Router::reverse($request);
        $this->assertSame('/articles/first-post', $result);
    }

    public function testReverseRouteKeyAndPassDuplicateValues(): void
    {
        Router::reload();
        $routes = Router::createRouteBuilder('/');
        $routes->connect('/authors/{author_id}/articles/{id}', ['controller' => 'Articles', 'action' => 'view'])
            ->setPass(['id']);

        $request = new ServerRequest([
            'url' => '/authors/1/articles/1',
            'params' => [
                'controller' => 'Articles',
                'action' => 'view',
                'pass' => ['1'],
                'author_id' => '1',
                'id' => '1',
                '_matchedRoute' => '/authors/{author_id}/articles/{id}',
            ],
        ]);
        $result = Router::reverse($request);
        $this->assertSame('/authors/1/articles/1', $result);

        Router::reload();
        $routes = Router::createRouteBuilder('/');
        $routes->connect('/authors/{author_id}/articles/{id}/*', ['controller' => 'Articles', 'action' => 'view'])
            ->setPass(['id', 'author_id']);

        $request = new ServerRequest([
            'url' => '/authors/88/articles/11',
            'params' => [
                'controller' => 'Articles',
                'action' => 'view',
                'pass' => ['11', '88', '99'],
                'author_id' => '88',
                'id' => '11',
                '_matchedRoute' => '/authors/{author_id}/articles/{id}/*',
            ],
        ]);
        $result = Router::reverse($request);
        $this->assertSame('/authors/88/articles/11/99', $result);

        $request = new ServerRequest([
            'url' => '/authors/1/articles/1/1',
            'params' => [
                'controller' => 'Articles',
                'action' => 'view',
                'pass' => ['1', '1', '1'],
                'author_id' => '1',
                'id' => '1',
                '_matchedRoute' => '/authors/{author_id}/articles/{id}/*',
            ],
        ]);
        $result = Router::reverse($request);
        $this->assertSame('/authors/1/articles/1/1', $result);
    }

    public function testReverseArrayQuery(): void
    {
        Router::connect('/{lang}/{controller}/{action}/*', [], ['lang' => '[a-z]{3}']);
        $params = [
            'lang' => 'eng',
            'controller' => 'Posts',
            'action' => 'view',
            1,
            '?' => ['foo' => 'bar'],
        ];
        $result = Router::reverse($params);
        $this->assertSame('/eng/Posts/view/1?foo=bar', $result);

        $params = [
            'lang' => 'eng',
            'controller' => 'Posts',
            'action' => 'view',
            'pass' => [1],
            'url' => ['url' => 'eng/posts/view/1'],
            'models' => [],
        ];
        $result = Router::reverse($params);
        $this->assertSame('/eng/Posts/view/1', $result);
    }

    public function testReverseCakeRequestQuery(): void
    {
        Router::connect('/{lang}/{controller}/{action}/*', [], ['lang' => '[a-z]{3}']);
        $request = new ServerRequest([
            'url' => '/eng/posts/view/1',
            'params' => [
                'lang' => 'eng',
                'controller' => 'Posts',
                'action' => 'view',
                'pass' => [1],
            ],
            'query' => ['test' => 'value'],
        ]);
        $result = Router::reverse($request);
        $expected = '/eng/Posts/view/1?test=value';
        $this->assertSame($expected, $result);
    }

    public function testReverseFull(): void
    {
        Router::scope('/', function (RouteBuilder $routes): void {
            $routes->fallbacks();
        });
        $params = [
            'lang' => 'eng',
            'controller' => 'Posts',
            'action' => 'view',
            'pass' => [1],
            'url' => ['url' => 'eng/posts/view/1'],
        ];
        $result = Router::reverse($params, true);
        $this->assertMatchesRegularExpression('/^http(s)?:\/\//', $result);
    }

    /**
     * Test that extensions work with Router::reverse()
     */
    public function testReverseWithExtension(): void
    {
        Router::connect('/{controller}/{action}/*');
        Router::extensions('json', false);

        $request = new ServerRequest([
            'url' => '/posts/view/1.json',
            'params' => [
                'controller' => 'Posts',
                'action' => 'view',
                'pass' => [1],
                '_ext' => 'json',
            ],
        ]);
        $result = Router::reverse($request);
        $expected = '/Posts/view/1.json';
        $this->assertSame($expected, $result);
    }

    public function testReverseToArrayQuery(): void
    {
        Router::connect('/{lang}/{controller}/{action}/*', [], ['lang' => '[a-z]{3}']);
        $params = [
            'lang' => 'eng',
            'controller' => 'Posts',
            'action' => 'view',
            'pass' => [123],
            '?' => ['foo' => 'bar', 'baz' => 'quu'],
        ];
        $actual = Router::reverseToArray($params);
        $expected = [
            'lang' => 'eng',
            'controller' => 'Posts',
            'action' => 'view',
            123,
            '?' => ['foo' => 'bar', 'baz' => 'quu'],
        ];
        $this->assertEquals($expected, $actual);
    }

    public function testReverseToArrayRequestQuery(): void
    {
        $builder = Router::createRouteBuilder('/');
        $route = $builder->connect('/{lang}/{controller}/{action}/*', [], ['lang' => '[a-z]{3}']);
        $request = new ServerRequest([
            'url' => '/eng/posts/view/1',
            'params' => [
                'lang' => 'eng',
                'controller' => 'Posts',
                'action' => 'view',
                'pass' => [123],
            ],
            'query' => ['test' => 'value'],
        ]);
        $actual = Router::reverseToArray($request);
        $expected = [
            'lang' => 'eng',
            'controller' => 'Posts',
            'action' => 'view',
            123,
            '?' => [
                'test' => 'value',
            ],
        ];
        $this->assertEquals($expected, $actual);

        $request = $request->withAttribute('route', $route)
            ->withQueryParams(['x' => 'y']);
        $expected['?'] = ['x' => 'y'];
        $actual = Router::reverseToArray($request);
        $this->assertEquals($expected, $actual);
    }

    /**
     * test get request.
     */
    public function testGetRequest(): void
    {
        $requestA = new ServerRequest(['url' => '/']);
        Router::setRequest($requestA);
        $this->assertSame($requestA, Router::getRequest());

        $requestB = new ServerRequest(['url' => '/posts']);
        Router::setRequest($requestB);
        $this->assertSame($requestB, Router::getRequest());
    }

    /**
     * test that a route object returning a full URL is not modified.
     */
    public function testUrlFullUrlReturnFromRoute(): void
    {
        $url = 'http://example.com/posts/view/1';

        $route = $this->getMockBuilder('Cake\Routing\Route\Route')
            ->onlyMethods(['match'])
            ->setConstructorArgs(['/{controller}/{action}/*'])
            ->getMock();
        $route->expects($this->any())
            ->method('match')
            ->will($this->returnValue($url));
        Router::connect($route);

        $result = Router::url(['controller' => 'Posts', 'action' => 'view', 1]);
        $this->assertSame($url, $result);
    }

    /**
     * test protocol in url
     */
    public function testUrlProtocol(): void
    {
        $url = 'http://example.com';
        $this->assertSame($url, Router::url($url));

        $url = 'ed2k://example.com';
        $this->assertSame($url, Router::url($url));

        $url = 'svn+ssh://example.com';
        $this->assertSame($url, Router::url($url));

        $url = '://example.com';
        $this->assertSame($url, Router::url($url));

        $url = '//example.com';
        $this->assertSame($url, Router::url($url));

        $url = 'javascript:void(0)';
        $this->assertSame($url, Router::url($url));

        $url = 'tel:012345-678';
        $this->assertSame($url, Router::url($url));

        $url = 'sms:012345-678';
        $this->assertSame($url, Router::url($url));

        $url = '#here';
        $this->assertSame($url, Router::url($url));

        $url = '?param=0';
        $this->assertSame($url, Router::url($url));

        $url = '/posts/index#here';
        $expected = Configure::read('App.fullBaseUrl') . '/posts/index#here';
        $this->assertSame($expected, Router::url($url, true));
    }

    /**
     * Testing that patterns on the {action} param work properly.
     */
    public function testPatternOnAction(): void
    {
        $route = new Route(
            '/blog/{action}/*',
            ['controller' => 'BlogPosts'],
            ['action' => 'other|actions']
        );
        $result = $route->match(['controller' => 'BlogPosts', 'action' => 'foo']);
        $this->assertNull($result);

        $result = $route->match(['controller' => 'BlogPosts', 'action' => 'actions']);
        $this->assertSame('/blog/actions/', $result);

        $result = $route->parseRequest($this->makeRequest('/blog/other', 'GET'));
        unset($result['_route']);
        $expected = [
            'controller' => 'BlogPosts',
            'action' => 'other',
            'pass' => [],
            '_matchedRoute' => '/blog/{action}/*',
        ];
        $this->assertEquals($expected, $result);

        $result = $route->parseRequest($this->makeRequest('/blog/foobar', 'GET'));
        $this->assertNull($result);
    }

    /**
     * Test the scope() method
     */
    public function testScope(): void
    {
        Router::scope('/path', ['param' => 'value'], function (RouteBuilder $routes): void {
            $this->assertSame('/path', $routes->path());
            $this->assertEquals(['param' => 'value'], $routes->params());
            $this->assertSame('', $routes->namePrefix());

            $routes->connect('/articles', ['controller' => 'Articles']);
        });
    }

    /**
     * Test the scope() method
     */
    public function testScopeError(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        Router::scope('/path', 'derpy');
    }

    /**
     * Test to ensure that extensions defined in scopes don't leak.
     * And that global extensions are propagated.
     */
    public function testScopeExtensionsContained(): void
    {
        Router::extensions(['json']);
        Router::scope('/', function (RouteBuilder $routes): void {
            $this->assertEquals(['json'], $routes->getExtensions(), 'Should default to global extensions.');
            $routes->setExtensions(['rss']);

            $this->assertEquals(
                ['rss'],
                $routes->getExtensions(),
                'Should include new extensions.'
            );
            $routes->connect('/home', []);
        });

        $this->assertEquals(['json', 'rss'], array_values(Router::extensions()));

        Router::scope('/api', function (RouteBuilder $routes): void {
            $this->assertEquals(['json'], $routes->getExtensions(), 'Should default to global extensions.');

            $routes->setExtensions(['json', 'csv']);
            $routes->connect('/export', []);

            $routes->scope('/v1', function (RouteBuilder $routes): void {
                $this->assertEquals(['json', 'csv'], $routes->getExtensions());
            });
        });

        $this->assertEquals(['json', 'rss', 'csv'], array_values(Router::extensions()));
    }

    /**
     * Test the scope() options
     */
    public function testScopeOptions(): void
    {
        $options = ['param' => 'value', 'routeClass' => 'InflectedRoute', 'extensions' => ['json']];
        Router::scope('/path', $options, function (RouteBuilder $routes): void {
            $this->assertSame('InflectedRoute', $routes->getRouteClass());
            $this->assertSame(['json'], $routes->getExtensions());
            $this->assertSame('/path', $routes->path());
            $this->assertEquals(['param' => 'value'], $routes->params());
        });
    }

    /**
     * Test the scope() method
     */
    public function testScopeNamePrefix(): void
    {
        Router::scope('/path', ['param' => 'value', '_namePrefix' => 'path:'], function (RouteBuilder $routes): void {
            $this->assertSame('/path', $routes->path());
            $this->assertEquals(['param' => 'value'], $routes->params());
            $this->assertSame('path:', $routes->namePrefix());

            $routes->connect('/articles', ['controller' => 'Articles']);
        });
    }

    /**
     * Test that prefix() creates a scope.
     */
    public function testPrefix(): void
    {
        Router::prefix('admin', function (RouteBuilder $routes): void {
            $this->assertSame('/admin', $routes->path());
            $this->assertEquals(['prefix' => 'Admin'], $routes->params());
        });

        Router::prefix('admin', ['_namePrefix' => 'admin:'], function (RouteBuilder $routes): void {
            $this->assertSame('admin:', $routes->namePrefix());
            $this->assertEquals(['prefix' => 'Admin'], $routes->params());
        });
    }

    /**
     * Test that prefix() accepts options
     */
    public function testPrefixOptions(): void
    {
        Router::prefix('admin', ['param' => 'value'], function (RouteBuilder $routes): void {
            $this->assertSame('/admin', $routes->path());
            $this->assertEquals(['prefix' => 'Admin', 'param' => 'value'], $routes->params());
        });

        Router::prefix('CustomPath', ['path' => '/custom-path'], function (RouteBuilder $routes): void {
            $this->assertSame('/custom-path', $routes->path());
            $this->assertEquals(['prefix' => 'CustomPath'], $routes->params());
        });
    }

    /**
     * Test that plugin() creates a scope.
     */
    public function testPlugin(): void
    {
        Router::plugin('DebugKit', function (RouteBuilder $routes): void {
            $this->assertSame('/debug-kit', $routes->path());
            $this->assertEquals(['plugin' => 'DebugKit'], $routes->params());
        });
    }

    /**
     * Test that plugin() accepts options
     */
    public function testPluginOptions(): void
    {
        Router::plugin('DebugKit', ['path' => '/debugger'], function (RouteBuilder $routes): void {
            $this->assertSame('/debugger', $routes->path());
            $this->assertEquals(['plugin' => 'DebugKit'], $routes->params());
        });

        Router::plugin('Contacts', ['_namePrefix' => 'contacts:'], function (RouteBuilder $routes): void {
            $this->assertSame('contacts:', $routes->namePrefix());
        });
    }

    /**
     * Test setting default route class.
     */
    public function testDefaultRouteClass(): void
    {
        Router::connect('/{controller}', ['action' => 'index']);
        $result = Router::url(['controller' => 'FooBar', 'action' => 'index']);
        $this->assertSame('/FooBar', $result);

        // This is needed because tests/bootstrap.php sets App.namespace to 'App'
        static::setAppNamespace();

        Router::defaultRouteClass('DashedRoute');
        Router::connect('/cake/{controller}', ['action' => 'cake']);
        $result = Router::url(['controller' => 'FooBar', 'action' => 'cake']);
        $this->assertSame('/cake/foo-bar', $result);

        $result = Router::url(['controller' => 'FooBar', 'action' => 'index']);
        $this->assertSame('/FooBar', $result);

        Router::reload();
        Router::defaultRouteClass('DashedRoute');
        Router::scope('/', function (RouteBuilder $routes): void {
            $routes->fallbacks();
        });

        $result = Router::url(['controller' => 'FooBar', 'action' => 'index']);
        $this->assertSame('/foo-bar', $result);
    }

    /**
     * Test setting the request context.
     */
    public function testSetRequestContextCakePHP(): void
    {
        Router::connect('/{controller}/{action}/*');
        $request = new ServerRequest([
            'base' => '/subdir',
            'url' => 'articles/view/1',
        ]);
        Router::setRequest($request);
        $result = Router::url(['controller' => 'Things', 'action' => 'add']);
        $this->assertSame('/subdir/Things/add', $result);

        $result = Router::url(['controller' => 'Things', 'action' => 'add'], true);
        $this->assertSame('http://localhost/subdir/Things/add', $result);

        $result = Router::url('/pages/home');
        $this->assertSame('/subdir/pages/home', $result);
    }

    /**
     * Test setting the request context.
     */
    public function testSetRequestContextPsr(): void
    {
        $server = [
            'DOCUMENT_ROOT' => '/Users/markstory/Sites',
            'SCRIPT_FILENAME' => '/Users/markstory/Sites/subdir/webroot/index.php',
            'PHP_SELF' => '/subdir/webroot/index.php/articles/view/1',
            'REQUEST_URI' => '/subdir/articles/view/1',
            'QUERY_STRING' => '',
            'SERVER_PORT' => 80,
        ];

        Router::connect('/{controller}/{action}/*');
        $request = ServerRequestFactory::fromGlobals($server);
        Router::setRequest($request);

        $result = Router::url(['controller' => 'Things', 'action' => 'add']);
        $this->assertSame('/subdir/Things/add', $result);

        $result = Router::url(['controller' => 'Things', 'action' => 'add'], true);
        $this->assertSame('http://localhost/subdir/Things/add', $result);

        $result = Router::url('/pages/home');
        $this->assertSame('/subdir/pages/home', $result);
    }

    /**
     * Test getting the route collection
     */
    public function testGetRouteCollection(): void
    {
        $collection = Router::getRouteCollection();
        $this->assertInstanceOf(RouteCollection::class, $collection);
        $this->assertCount(0, $collection->routes());
    }

    /**
     * Test getting a route builder instance.
     */
    public function testCreateRouteBuilder(): void
    {
        $builder = Router::createRouteBuilder('/api');
        $this->assertInstanceOf(RouteBuilder::class, $builder);
        $this->assertSame('/api', $builder->path());

        $builder = Router::createRouteBuilder('/', [
            'routeClass' => 'InflectedRoute',
            'extensions' => ['json'],
        ]);
        $this->assertInstanceOf(RouteBuilder::class, $builder);
        $this->assertSame(['json'], $builder->getExtensions());
    }

    /**
     * test connect() with short string syntax
     */
    public function testConnectShortStringSyntax(): void
    {
        Router::connect('/admin/articles/view', 'Admin/Articles::view');
        $result = Router::parseRequest($this->makeRequest('/admin/articles/view', 'GET'));
        unset($result['_route']);
        $expected = [
            'pass' => [],
            'prefix' => 'Admin',
            'controller' => 'Articles',
            'action' => 'view',
            'plugin' => null,
            '_matchedRoute' => '/admin/articles/view',

        ];
        $this->assertEquals($result, $expected);
    }

    /**
     * test url() with a string route path
     */
    public function testUrlGenerationWithPathUrl(): void
    {
        Router::connect('/articles', 'Articles::index');
        Router::connect('/articles/view/*', 'Articles::view');
        Router::connect('/article/{slug}', 'Articles::read');
        Router::connect('/admin/articles', 'Admin/Articles::index');
        Router::connect('/cms/articles', 'Cms.Articles::index');
        Router::connect('/cms/admin/articles', 'Cms.Admin/Articles::index');

        $result = Router::pathUrl('Articles::index');
        $expected = '/articles';
        $this->assertSame($result, $expected);

        $result = Router::pathUrl('Articles::view', [3]);
        $expected = '/articles/view/3';
        $this->assertSame($result, $expected);

        $result = Router::pathUrl('Articles::read', ['slug' => 'title']);
        $expected = '/article/title';
        $this->assertSame($result, $expected);

        $result = Router::pathUrl('Admin/Articles::index');
        $expected = '/admin/articles';
        $this->assertSame($result, $expected);

        $result = Router::pathUrl('Cms.Admin/Articles::index');
        $expected = '/cms/admin/articles';
        $this->assertSame($result, $expected);

        $result = Router::pathUrl('Cms.Articles::index');
        $expected = '/cms/articles';
        $this->assertSame($result, $expected);
    }

    /**
     * test url() with a string route path doesn't take parameters from current request
     */
    public function testUrlGenerationWithRoutePathWithContext(): void
    {
        Router::connect('/articles', 'Articles::index');
        Router::connect('/articles/view/*', 'Articles::view');
        Router::connect('/admin/articles', 'Admin/Articles::index');
        Router::connect('/cms/articles', 'Cms.Articles::index');
        Router::connect('/cms/admin/articles', 'Cms.Admin/Articles::index');

        $request = new ServerRequest([
            'params' => [
                'plugin' => 'Cms',
                'prefix' => 'Admin',
                'controller' => 'Articles',
                'action' => 'edit',
                'pass' => ['3'],
            ],
            'url' => '/admin/articles/edit/3',
        ]);
        Router::setRequest($request);

        $expected = '/articles';
        $result = Router::pathUrl('Articles::index');
        $this->assertSame($result, $expected);
        $result = Router::url(['_path' => 'Articles::index']);
        $this->assertSame($result, $expected);

        $expected = '/articles/view/3';
        $result = Router::pathUrl('Articles::view', [3]);
        $this->assertSame($result, $expected);
        $result = Router::url(['_path' => 'Articles::view', 3]);
        $this->assertSame($result, $expected);

        $expected = '/admin/articles';
        $result = Router::pathUrl('Admin/Articles::index');
        $this->assertSame($result, $expected);
        $result = Router::url(['_path' => 'Admin/Articles::index']);
        $this->assertSame($result, $expected);

        $expected = '/cms/admin/articles';
        $result = Router::pathUrl('Cms.Admin/Articles::index');
        $this->assertSame($result, $expected);
        $result = Router::url(['_path' => 'Cms.Admin/Articles::index']);
        $this->assertSame($result, $expected);

        $expected = '/cms/articles';
        $result = Router::pathUrl('Cms.Articles::index');
        $this->assertSame($result, $expected);
        $result = Router::url(['_path' => 'Cms.Articles::index']);
        $this->assertSame($result, $expected);
    }

    /**
     * @return array
     */
    public function invalidRoutePathParametersArrayProvider(): array
    {
        return [
            [['plugin' => false]],
            [['plugin' => 'Cms']],
            [['prefix' => false]],
            [['prefix' => 'Manager']],
            [['controller' => 'Bookmarks']],
            [['controller' => 'Articles']],
            [['action' => 'edit']],
            [['action' => 'index']],
        ];
    }

    /**
     * Test url() doesn't let override parts of string route path
     *
     * @param array $params
     * @dataProvider invalidRoutePathParametersArrayProvider
     */
    public function testUrlGenerationOverridingShortString(array $params): void
    {
        Router::connect('/articles', 'Articles::index');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('cannot be used when defining route targets with a string route path.');

        Router::pathUrl('Articles::index', $params);
    }

    /**
     * Test url() doesn't let override parts of string route path from `_path` key
     *
     * @param array $params
     * @dataProvider invalidRoutePathParametersArrayProvider
     */
    public function testUrlGenerationOverridingPathKey(array $params): void
    {
        Router::connect('/articles', 'Articles::index');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('cannot be used when defining route targets with a string route path.');

        Router::url(['_path' => 'Articles::index'] + $params);
    }

    /**
     * Connect some fallback routes for testing router behavior.
     */
    protected function _connectDefaultRoutes(): void
    {
        Router::scope('/', function (RouteBuilder $routes): void {
            $routes->fallbacks('InflectedRoute');
        });
    }

    /**
     * Helper to create a request for a given URL and method.
     *
     * @param string $url The URL to create a request for
     * @param string $method The HTTP method to use.
     */
    protected function makeRequest($url, $method): ServerRequest
    {
        $request = new ServerRequest([
            'url' => $url,
            'environment' => ['REQUEST_METHOD' => $method],
        ]);

        return $request;
    }
}
