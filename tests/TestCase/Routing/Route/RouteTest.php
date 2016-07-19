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
 * @since         2.0.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Routing\Route;

use Cake\Core\Configure;
use Cake\Routing\Router;
use Cake\Routing\Route\Route;
use Cake\TestSuite\TestCase;

/**
 * Test case for Route
 */
class RouteTest extends TestCase
{

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        Configure::write('Routing', ['admin' => null, 'prefixes' => []]);
    }

    /**
     * Test the construction of a Route
     *
     * @return void
     */
    public function testConstruction()
    {
        $route = new Route('/:controller/:action/:id', [], ['id' => '[0-9]+']);

        $this->assertEquals('/:controller/:action/:id', $route->template);
        $this->assertEquals([], $route->defaults);
        $this->assertEquals(['id' => '[0-9]+'], $route->options);
        $this->assertFalse($route->compiled());
    }

    /**
     * test Route compiling.
     *
     * @return void
     */
    public function testBasicRouteCompiling()
    {
        $route = new Route('/', ['controller' => 'pages', 'action' => 'display', 'home']);
        $result = $route->compile();
        $expected = '#^/*$#';
        $this->assertEquals($expected, $result);
        $this->assertEquals([], $route->keys);

        $route = new Route('/:controller/:action', ['controller' => 'posts']);
        $result = $route->compile();

        $this->assertRegExp($result, '/posts/edit');
        $this->assertRegExp($result, '/posts/super_delete');
        $this->assertNotRegExp($result, '/posts');
        $this->assertNotRegExp($result, '/posts/super_delete/1');
        $this->assertSame($result, $route->compile());

        $route = new Route('/posts/foo:id', ['controller' => 'posts', 'action' => 'view']);
        $result = $route->compile();

        $this->assertRegExp($result, '/posts/foo:1');
        $this->assertRegExp($result, '/posts/foo:param');
        $this->assertNotRegExp($result, '/posts');
        $this->assertNotRegExp($result, '/posts/');

        $this->assertEquals(['id'], $route->keys);

        $route = new Route('/:plugin/:controller/:action/*', ['plugin' => 'test_plugin', 'action' => 'index']);
        $result = $route->compile();
        $this->assertRegExp($result, '/test_plugin/posts/index');
        $this->assertRegExp($result, '/test_plugin/posts/edit/5');
        $this->assertRegExp($result, '/test_plugin/posts/edit/5/name:value/nick:name');
    }

    /**
     * Test parsing routes with extensions.
     *
     * @return void
     */
    public function testRouteParsingWithExtensions()
    {
        $route = new Route(
            '/:controller/:action/*',
            [],
            ['_ext' => ['json', 'xml']]
        );

        $result = $route->parse('/posts/index');
        $this->assertFalse(isset($result['_ext']));

        $result = $route->parse('/posts/index.pdf');
        $this->assertFalse(isset($result['_ext']));

        $route->extensions(['pdf', 'json', 'xml']);
        $result = $route->parse('/posts/index.pdf');
        $this->assertEquals('pdf', $result['_ext']);

        $result = $route->parse('/posts/index.json');
        $this->assertEquals('json', $result['_ext']);

        $result = $route->parse('/posts/index.xml');
        $this->assertEquals('xml', $result['_ext']);
    }

    /**
     * test that route parameters that overlap don't cause errors.
     *
     * @return void
     */
    public function testRouteParameterOverlap()
    {
        $route = new Route('/invoices/add/:idd/:id', ['controller' => 'invoices', 'action' => 'add']);
        $result = $route->compile();
        $this->assertRegExp($result, '/invoices/add/1/3');

        $route = new Route('/invoices/add/:id/:idd', ['controller' => 'invoices', 'action' => 'add']);
        $result = $route->compile();
        $this->assertRegExp($result, '/invoices/add/1/3');
    }

    /**
     * test compiling routes with keys that have patterns
     *
     * @return void
     */
    public function testRouteCompilingWithParamPatterns()
    {
        $route = new Route(
            '/:controller/:action/:id',
            [],
            ['id' => Router::ID]
        );
        $result = $route->compile();
        $this->assertRegExp($result, '/posts/edit/1');
        $this->assertRegExp($result, '/posts/view/518098');
        $this->assertNotRegExp($result, '/posts/edit/name-of-post');
        $this->assertNotRegExp($result, '/posts/edit/4/other:param');
        $this->assertEquals(['id', 'controller', 'action'], $route->keys);

        $route = new Route(
            '/:lang/:controller/:action/:id',
            ['controller' => 'testing4'],
            ['id' => Router::ID, 'lang' => '[a-z]{3}']
        );
        $result = $route->compile();
        $this->assertRegExp($result, '/eng/posts/edit/1');
        $this->assertRegExp($result, '/cze/articles/view/1');
        $this->assertNotRegExp($result, '/language/articles/view/2');
        $this->assertNotRegExp($result, '/eng/articles/view/name-of-article');
        $this->assertEquals(['lang', 'id', 'controller', 'action'], $route->keys);

        foreach ([':', '@', ';', '$', '-'] as $delim) {
            $route = new Route('/posts/:id' . $delim . ':title');
            $result = $route->compile();

            $this->assertRegExp($result, '/posts/1' . $delim . 'name-of-article');
            $this->assertRegExp($result, '/posts/13244' . $delim . 'name-of_Article[]');
            $this->assertNotRegExp($result, '/posts/11!nameofarticle');
            $this->assertNotRegExp($result, '/posts/11');

            $this->assertEquals(['title', 'id'], $route->keys);
        }

        $route = new Route(
            '/posts/:id::title/:year',
            ['controller' => 'posts', 'action' => 'view'],
            ['id' => Router::ID, 'year' => Router::YEAR, 'title' => '[a-z-_]+']
        );
        $result = $route->compile();
        $this->assertRegExp($result, '/posts/1:name-of-article/2009/');
        $this->assertRegExp($result, '/posts/13244:name-of-article/1999');
        $this->assertNotRegExp($result, '/posts/hey_now:nameofarticle');
        $this->assertNotRegExp($result, '/posts/:nameofarticle/2009');
        $this->assertNotRegExp($result, '/posts/:nameofarticle/01');
        $this->assertEquals(['year', 'title', 'id'], $route->keys);

        $route = new Route(
            '/posts/:url_title-(uuid::id)',
            ['controller' => 'posts', 'action' => 'view'],
            ['pass' => ['id', 'url_title'], 'id' => Router::ID]
        );
        $result = $route->compile();
        $this->assertRegExp($result, '/posts/some_title_for_article-(uuid:12534)/');
        $this->assertRegExp($result, '/posts/some_title_for_article-(uuid:12534)');
        $this->assertNotRegExp($result, '/posts/');
        $this->assertNotRegExp($result, '/posts/nameofarticle');
        $this->assertNotRegExp($result, '/posts/nameofarticle-12347');
        $this->assertEquals(['url_title', 'id'], $route->keys);
    }

    public function testRouteCompilingWithUnicodePatterns()
    {
        $route = new Route(
            '/test/:slug',
            ['controller' => 'Pages', 'action' => 'display'],
            ['pass' => ['slug'], 'multibytePattern' => false, 'slug' => '[A-zА-я\-\ ]+']
        );
        $result = $route->compile();
        $this->assertNotRegExp($result, '/test/bla-blan-тест');

        $route = new Route(
            '/test/:slug',
            ['controller' => 'Pages', 'action' => 'display'],
            ['pass' => ['slug'], 'multibytePattern' => true, 'slug' => '[A-zА-я\-\ ]+']
        );
        $result = $route->compile();
        $this->assertRegExp($result, '/test/bla-blan-тест');
    }

    /**
     * test more complex route compiling & parsing with mid route greedy stars
     * and optional routing parameters
     *
     * @return void
     */
    public function testComplexRouteCompilingAndParsing()
    {
        $route = new Route(
            '/posts/:month/:day/:year/*',
            ['controller' => 'posts', 'action' => 'view'],
            ['year' => Router::YEAR, 'month' => Router::MONTH, 'day' => Router::DAY]
        );
        $result = $route->compile();
        $this->assertRegExp($result, '/posts/08/01/2007/title-of-post');
        $result = $route->parse('/posts/08/01/2007/title-of-post');

        $this->assertEquals(count($result), 7);
        $this->assertEquals($result['controller'], 'posts');
        $this->assertEquals($result['action'], 'view');
        $this->assertEquals($result['year'], '2007');
        $this->assertEquals($result['month'], '08');
        $this->assertEquals($result['day'], '01');
        $this->assertEquals($result['pass'][0], 'title-of-post');
        $this->assertEquals($result['_matchedRoute'], '/posts/:month/:day/:year/*');

        $route = new Route(
            "/:extra/page/:slug/*",
            ['controller' => 'pages', 'action' => 'view', 'extra' => null],
            ["extra" => '[a-z1-9_]*', "slug" => '[a-z1-9_]+', "action" => 'view']
        );
        $result = $route->compile();

        $this->assertRegExp($result, '/some_extra/page/this_is_the_slug');
        $this->assertRegExp($result, '/page/this_is_the_slug');
        $this->assertEquals(['slug', 'extra'], $route->keys);
        $this->assertEquals(['extra' => '[a-z1-9_]*', 'slug' => '[a-z1-9_]+', 'action' => 'view'], $route->options);
        $expected = [
            'controller' => 'pages',
            'action' => 'view'
        ];
        $this->assertEquals($expected, $route->defaults);

        $route = new Route(
            '/:controller/:action/*',
            ['project' => false],
            [
                'controller' => 'source|wiki|commits|tickets|comments|view',
                'action' => 'branches|history|branch|logs|view|start|add|edit|modify'
            ]
        );
        $this->assertFalse($route->parse('/chaw_test/wiki'));

        $result = $route->compile();
        $this->assertNotRegExp($result, '/some_project/source');
        $this->assertRegExp($result, '/source/view');
        $this->assertRegExp($result, '/source/view/other/params');
        $this->assertNotRegExp($result, '/chaw_test/wiki');
        $this->assertNotRegExp($result, '/source/wierd_action');
    }

    /**
     * test that routes match their pattern.
     *
     * @return void
     */
    public function testMatchBasic()
    {
        $route = new Route('/:controller/:action/:id', ['plugin' => null]);
        $result = $route->match(['controller' => 'posts', 'action' => 'view', 'plugin' => null]);
        $this->assertFalse($result);

        $result = $route->match(['plugin' => null, 'controller' => 'posts', 'action' => 'view', 0]);
        $this->assertFalse($result);

        $result = $route->match(['plugin' => null, 'controller' => 'posts', 'action' => 'view', 'id' => 1]);
        $this->assertEquals('/posts/view/1', $result);

        $route = new Route('/', ['controller' => 'pages', 'action' => 'display', 'home']);
        $result = $route->match(['controller' => 'pages', 'action' => 'display', 'home']);
        $this->assertEquals('/', $result);

        $result = $route->match(['controller' => 'pages', 'action' => 'display', 'about']);
        $this->assertFalse($result);

        $route = new Route('/pages/*', ['controller' => 'pages', 'action' => 'display']);
        $result = $route->match(['controller' => 'pages', 'action' => 'display', 'home']);
        $this->assertEquals('/pages/home', $result);

        $result = $route->match(['controller' => 'pages', 'action' => 'display', 'about']);
        $this->assertEquals('/pages/about', $result);

        $route = new Route('/blog/:action', ['controller' => 'posts']);
        $result = $route->match(['controller' => 'posts', 'action' => 'view']);
        $this->assertEquals('/blog/view', $result);

        $result = $route->match(['controller' => 'posts', 'action' => 'view', 'id' => 2]);
        $this->assertEquals('/blog/view?id=2', $result);

        $result = $route->match(['controller' => 'nodes', 'action' => 'view']);
        $this->assertFalse($result);

        $result = $route->match(['controller' => 'posts', 'action' => 'view', 1]);
        $this->assertFalse($result);

        $route = new Route('/foo/:controller/:action', ['action' => 'index']);
        $result = $route->match(['controller' => 'posts', 'action' => 'view']);
        $this->assertEquals('/foo/posts/view', $result);

        $route = new Route('/:plugin/:id/*', ['controller' => 'posts', 'action' => 'view']);
        $result = $route->match(['plugin' => 'test', 'controller' => 'posts', 'action' => 'view', 'id' => '1']);
        $this->assertEquals('/test/1/', $result);

        $result = $route->match(['plugin' => 'fo', 'controller' => 'posts', 'action' => 'view', 'id' => '1', '0']);
        $this->assertEquals('/fo/1/0', $result);

        $result = $route->match(['plugin' => 'fo', 'controller' => 'nodes', 'action' => 'view', 'id' => 1]);
        $this->assertFalse($result);

        $result = $route->match(['plugin' => 'fo', 'controller' => 'posts', 'action' => 'edit', 'id' => 1]);
        $this->assertFalse($result);

        $route = new Route('/admin/subscriptions/:action/*', [
            'controller' => 'subscribe', 'prefix' => 'admin'
        ]);

        $url = ['controller' => 'subscribe', 'prefix' => 'admin', 'action' => 'edit', 1];
        $result = $route->match($url);
        $expected = '/admin/subscriptions/edit/1';
        $this->assertEquals($expected, $result);

        $url = [
            'controller' => 'subscribe',
            'prefix' => 'admin',
            'action' => 'edit_admin_e',
            1
        ];
        $result = $route->match($url);
        $expected = '/admin/subscriptions/edit_admin_e/1';
        $this->assertEquals($expected, $result);
    }

    /**
     * Test match() with persist option
     *
     * @return void
     */
    public function testMatchWithPersistOption()
    {
        $context = [
            'params' => ['lang' => 'en']
        ];
        $route = new Route('/:lang/:controller/:action', [], ['persist' => ['lang']]);
        $result = $route->match(
            ['controller' => 'tasks', 'action' => 'add'],
            $context
        );
        $this->assertEquals('/en/tasks/add', $result);
    }

    /**
     * Test match() with _host and other keys.
     */
    public function testMatchWithHostKeys()
    {
        $context = [
            '_host' => 'foo.com',
            '_scheme' => 'http',
            '_port' => 80,
            '_base' => ''
        ];
        $route = new Route('/:controller/:action');
        $result = $route->match(
            ['controller' => 'posts', 'action' => 'index', '_host' => 'example.com'],
            $context
        );
        $this->assertEquals('http://example.com/posts/index', $result);

        $result = $route->match(
            ['controller' => 'posts', 'action' => 'index', '_scheme' => 'webcal'],
            $context
        );
        $this->assertEquals('webcal://foo.com/posts/index', $result);

        $result = $route->match(
            ['controller' => 'posts', 'action' => 'index', '_port' => '8080'],
            $context
        );
        $this->assertEquals('http://foo.com:8080/posts/index', $result);

        $result = $route->match(
            ['controller' => 'posts', 'action' => 'index', '_base' => '/dir'],
            $context
        );
        $this->assertEquals('/dir/posts/index', $result);

        $result = $route->match(
            [
                'controller' => 'posts',
                'action' => 'index',
                '_port' => '8080',
                '_host' => 'example.com',
                '_scheme' => 'https',
                '_base' => '/dir'
            ],
            $context
        );
        $this->assertEquals('https://example.com:8080/dir/posts/index', $result);
    }

    /**
     * test that non-greedy routes fail with extra passed args
     *
     * @return void
     */
    public function testMatchGreedyRouteFailurePassedArg()
    {
        $route = new Route('/:controller/:action', ['plugin' => null]);
        $result = $route->match(['controller' => 'posts', 'action' => 'view', '0']);
        $this->assertFalse($result);

        $route = new Route('/:controller/:action', ['plugin' => null]);
        $result = $route->match(['controller' => 'posts', 'action' => 'view', 'test']);
        $this->assertFalse($result);
    }

    /**
     * test that falsey values do not interrupt a match.
     *
     * @return void
     */
    public function testMatchWithFalseyValues()
    {
        $route = new Route('/:controller/:action/*', ['plugin' => null]);
        $result = $route->match([
            'controller' => 'posts', 'action' => 'index', 'plugin' => null, 'admin' => false
        ]);
        $this->assertEquals('/posts/index/', $result);
    }

    /**
     * test match() with greedy routes, and passed args.
     *
     * @return void
     */
    public function testMatchWithPassedArgs()
    {
        $route = new Route('/:controller/:action/*', ['plugin' => null]);

        $result = $route->match(['controller' => 'posts', 'action' => 'view', 'plugin' => null, 5]);
        $this->assertEquals('/posts/view/5', $result);

        $result = $route->match(['controller' => 'posts', 'action' => 'view', 'plugin' => null, 0]);
        $this->assertEquals('/posts/view/0', $result);

        $result = $route->match(['controller' => 'posts', 'action' => 'view', 'plugin' => null, '0']);
        $this->assertEquals('/posts/view/0', $result);

        $result = $route->match(['controller' => 'posts', 'action' => 'view', 'plugin' => null, 'word space']);
        $this->assertEquals('/posts/view/word%20space', $result);

        $route = new Route('/test2/*', ['controller' => 'pages', 'action' => 'display', 2]);
        $result = $route->match(['controller' => 'pages', 'action' => 'display', 1]);
        $this->assertFalse($result);

        $result = $route->match(['controller' => 'pages', 'action' => 'display', 2, 'something']);
        $this->assertEquals('/test2/something', $result);

        $result = $route->match(['controller' => 'pages', 'action' => 'display', 5, 'something']);
        $this->assertFalse($result);
    }

    /**
     * Test that the pass option lets you use positional arguments for the
     * route elements that were named.
     *
     * @return void
     */
    public function testMatchWithPassOption()
    {
        $route = new Route(
            '/blog/:id-:slug',
            ['controller' => 'Blog', 'action' => 'view'],
            ['pass' => ['id', 'slug']]
        );
        $result = $route->match([
            'controller' => 'Blog',
            'action' => 'view',
            'id' => 1,
            'slug' => 'second'
        ]);
        $this->assertEquals('/blog/1-second', $result);

        $result = $route->match([
            'controller' => 'Blog',
            'action' => 'view',
            1,
            'second'
        ]);
        $this->assertEquals('/blog/1-second', $result);

        $result = $route->match([
            'controller' => 'Blog',
            'action' => 'view',
            1,
            'second',
            'query' => 'string'
        ]);
        $this->assertEquals('/blog/1-second?query=string', $result);

        $result = $route->match([
            'controller' => 'Blog',
            'action' => 'view',
            1 => 2,
            2 => 'second'
        ]);
        $this->assertFalse($result, 'Positional args must match exactly.');
    }

    /**
     * Test that match() with pass and greedy routes.
     *
     * @return void
     */
    public function testMatchWithPassOptionGreedy()
    {
        $route = new Route(
            '/blog/:id-:slug/*',
            ['controller' => 'Blog', 'action' => 'view'],
            ['pass' => ['id', 'slug']]
        );
        $result = $route->match([
            'controller' => 'Blog',
            'action' => 'view',
            'id' => 1,
            'slug' => 'second',
            'third',
            'fourth',
            'query' => 'string'
        ]);
        $this->assertEquals('/blog/1-second/third/fourth?query=string', $result);

        $result = $route->match([
            'controller' => 'Blog',
            'action' => 'view',
            1,
            'second',
            'third',
            'fourth',
            'query' => 'string'
        ]);
        $this->assertEquals('/blog/1-second/third/fourth?query=string', $result);
    }

    /**
     * Test that extensions work.
     *
     * @return void
     */
    public function testMatchWithExtension()
    {
        $route = new Route('/:controller/:action');
        $result = $route->match([
            'controller' => 'posts',
            'action' => 'index',
            '_ext' => 'json'
        ]);
        $this->assertEquals('/posts/index.json', $result);

        $route = new Route('/:controller/:action/*');
        $result = $route->match([
            'controller' => 'posts',
            'action' => 'index',
            '_ext' => 'json',
        ]);
        $this->assertEquals('/posts/index.json', $result);

        $result = $route->match([
            'controller' => 'posts',
            'action' => 'view',
            1,
            '_ext' => 'json',
        ]);
        $this->assertEquals('/posts/view/1.json', $result);

        $result = $route->match([
            'controller' => 'posts',
            'action' => 'view',
            1,
            '_ext' => 'json',
            'id' => 'b',
            'c' => 'd'
        ]);
        $this->assertEquals('/posts/view/1.json?id=b&c=d', $result);
    }

    /**
     * test that match with patterns works.
     *
     * @return void
     */
    public function testMatchWithPatterns()
    {
        $route = new Route('/:controller/:action/:id', ['plugin' => null], ['id' => '[0-9]+']);
        $result = $route->match(['controller' => 'posts', 'action' => 'view', 'id' => 'foo']);
        $this->assertFalse($result);

        $result = $route->match(['plugin' => null, 'controller' => 'posts', 'action' => 'view', 'id' => '9']);
        $this->assertEquals('/posts/view/9', $result);

        $result = $route->match(['plugin' => null, 'controller' => 'posts', 'action' => 'view', 'id' => '922']);
        $this->assertEquals('/posts/view/922', $result);

        $result = $route->match(['plugin' => null, 'controller' => 'posts', 'action' => 'view', 'id' => 'a99']);
        $this->assertFalse($result);
    }

    /**
     * Test that match() pulls out extra arguments as query string params.
     *
     * @return void
     */
    public function testMatchExtractQueryStringArgs()
    {
        $route = new Route('/:controller/:action/*');
        $result = $route->match([
            'controller' => 'posts',
            'action' => 'index',
            'page' => 1
        ]);
        $this->assertEquals('/posts/index?page=1', $result);

        $result = $route->match([
            'controller' => 'posts',
            'action' => 'index',
            'page' => 0
        ]);
        $this->assertEquals('/posts/index?page=0', $result);

        $result = $route->match([
            'controller' => 'posts',
            'action' => 'index',
            1,
            'page' => 1,
            'dir' => 'desc',
            'order' => 'title'
        ]);
        $this->assertEquals('/posts/index/1?page=1&dir=desc&order=title', $result);
    }

    /**
     * Test separartor.
     *
     * @return void
     */
    public function testQueryStringGeneration()
    {
        $route = new Route('/:controller/:action/*');

        $restore = ini_get('arg_separator.output');
        ini_set('arg_separator.output', '&amp;');

        $result = $route->match([
            'controller' => 'posts',
            'action' => 'index',
            0,
            'test' => 'var',
            'var2' => 'test2',
            'more' => 'test data'
        ]);
        $expected = '/posts/index/0?test=var&amp;var2=test2&amp;more=test+data';
        $this->assertEquals($expected, $result);
        ini_set('arg_separator.output', $restore);
    }

    /**
     * test the parse method of Route.
     *
     * @return void
     */
    public function testParse()
    {
        $route = new Route(
            '/:controller/:action/:id',
            ['controller' => 'testing4', 'id' => null],
            ['id' => Router::ID]
        );
        $route->compile();
        $result = $route->parse('/posts/view/1');
        $this->assertEquals('posts', $result['controller']);
        $this->assertEquals('view', $result['action']);
        $this->assertEquals('1', $result['id']);

        $route = new Route(
            '/admin/:controller',
            ['prefix' => 'admin', 'admin' => 1, 'action' => 'index']
        );
        $route->compile();
        $result = $route->parse('/admin/');
        $this->assertFalse($result);

        $result = $route->parse('/admin/posts');
        $this->assertEquals('posts', $result['controller']);
        $this->assertEquals('index', $result['action']);

        $route = new Route(
            '/media/search/*',
            ['controller' => 'Media', 'action' => 'search']
        );
        $result = $route->parse('/media/search');
        $this->assertEquals('Media', $result['controller']);
        $this->assertEquals('search', $result['action']);
        $this->assertEquals([], $result['pass']);

        $result = $route->parse('/media/search/tv/shows');
        $this->assertEquals('Media', $result['controller']);
        $this->assertEquals('search', $result['action']);
        $this->assertEquals(['tv', 'shows'], $result['pass']);
    }

    /**
     * Test that :key elements are urldecoded
     *
     * @return void
     */
    public function testParseUrlDecodeElements()
    {
        $route = new Route(
            '/:controller/:slug',
            ['action' => 'view']
        );
        $route->compile();
        $result = $route->parse('/posts/%E2%88%82%E2%88%82');
        $this->assertEquals('posts', $result['controller']);
        $this->assertEquals('view', $result['action']);
        $this->assertEquals('∂∂', $result['slug']);

        $result = $route->parse('/posts/∂∂');
        $this->assertEquals('posts', $result['controller']);
        $this->assertEquals('view', $result['action']);
        $this->assertEquals('∂∂', $result['slug']);

        $result = $route->parse('/posts/ABC%2FD');
        $this->assertEquals('posts', $result['controller']);
        $this->assertEquals('view', $result['action']);
        $this->assertEquals('ABC%2FD', $result['slug']);
    }

    /**
     * test numerically indexed defaults, get appended to pass
     *
     * @return void
     */
    public function testParseWithPassDefaults()
    {
        $route = new Route('/:controller', ['action' => 'display', 'home']);
        $result = $route->parse('/posts');
        $expected = [
            'controller' => 'posts',
            'action' => 'display',
            'pass' => ['home'],
            '_matchedRoute' => '/:controller'
        ];
        $this->assertEquals($expected, $result);
    }

    /**
     * test that http header conditions can cause route failures.
     *
     * @return void
     */
    public function testParseWithHttpHeaderConditions()
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $route = new Route('/sample', ['controller' => 'posts', 'action' => 'index', '_method' => 'POST']);
        $this->assertFalse($route->parse('/sample'));

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $expected = [
            'controller' => 'posts',
            'action' => 'index',
            'pass' => [],
            '_method' => 'POST',
            '_matchedRoute' => '/sample'
        ];
        $this->assertEquals($expected, $route->parse('/sample'));
    }

    /**
     * test that http header conditions can cause route failures.
     *
     * @return void
     */
    public function testParseWithMultipleHttpMethodConditions()
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $route = new Route('/sample', [
            'controller' => 'posts',
            'action' => 'index',
            '_method' => ['PUT', 'POST']
        ]);
        $this->assertFalse($route->parse('/sample'));

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $expected = [
            'controller' => 'posts',
            'action' => 'index',
            'pass' => [],
            '_method' => ['PUT', 'POST'],
            '_matchedRoute' => '/sample'
        ];
        $this->assertEquals($expected, $route->parse('/sample'));
    }

    /**
     * test that http header conditions can work with URL generation
     *
     * @return void
     */
    public function testMatchWithMultipleHttpMethodConditions()
    {
        $route = new Route('/sample', [
            'controller' => 'posts',
            'action' => 'index',
            '_method' => ['PUT', 'POST']
        ]);
        $url = [
            'controller' => 'posts',
            'action' => 'index',
        ];
        $this->assertFalse($route->match($url));

        $url = [
            'controller' => 'posts',
            'action' => 'index',
            '_method' => 'GET',
        ];
        $this->assertFalse($route->match($url));

        $url = [
            'controller' => 'posts',
            'action' => 'index',
            '_method' => 'PUT',
        ];
        $this->assertEquals('/sample', $route->match($url));

        $url = [
            'controller' => 'posts',
            'action' => 'index',
            '_method' => 'POST',
        ];
        $this->assertEquals('/sample', $route->match($url));
    }

    /**
     * Check [method] compatibility.
     *
     * @return void
     */
    public function testMethodCompatibility()
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $route = new Route('/sample', [
            'controller' => 'Articles',
            'action' => 'index',
            '[method]' => 'POST',
        ]);
        $url = [
            'controller' => 'Articles',
            'action' => 'index',
            '_method' => 'POST',
        ];
        $this->assertEquals('/sample', $route->match($url));

        $url = [
            'controller' => 'Articles',
            'action' => 'index',
            '[method]' => 'POST',
        ];
        $this->assertEquals('/sample', $route->match($url));
    }

    /**
     * test that patterns work for :action
     *
     * @return void
     */
    public function testPatternOnAction()
    {
        $route = new Route(
            '/blog/:action/*',
            ['controller' => 'blog_posts'],
            ['action' => 'other|actions']
        );
        $result = $route->match(['controller' => 'blog_posts', 'action' => 'foo']);
        $this->assertFalse($result);

        $result = $route->match(['controller' => 'blog_posts', 'action' => 'actions']);
        $this->assertNotEmpty($result);

        $result = $route->parse('/blog/other');
        $expected = [
            'controller' => 'blog_posts',
            'action' => 'other',
            'pass' => [],
            '_matchedRoute' => '/blog/:action/*'
        ];
        $this->assertEquals($expected, $result);

        $result = $route->parse('/blog/foobar');
        $this->assertFalse($result);
    }

    /**
     * test the parseArgs method
     *
     * @return void
     */
    public function testParsePassedArgument()
    {
        $route = new Route('/:controller/:action/*');
        $result = $route->parse('/posts/edit/1/2/0');
        $expected = [
            'controller' => 'posts',
            'action' => 'edit',
            'pass' => ['1', '2', '0'],
            '_matchedRoute' => '/:controller/:action/*'
        ];
        $this->assertEquals($expected, $result);
    }

    /**
     * Test matching of parameters where one parameter name starts with another parameter name
     *
     * @return void
     */
    public function testMatchSimilarParameters()
    {
        $route = new Route('/:thisParam/:thisParamIsLonger');

        $url = [
            'thisParamIsLonger' => 'bar',
            'thisParam' => 'foo',
        ];

        $result = $route->match($url);
        $expected = '/foo/bar';
        $this->assertEquals($expected, $result);
    }

    /**
     * Test match() with trailing ** style routes.
     *
     * @return void
     */
    public function testMatchTrailing()
    {
        $route = new Route('/pages/**', ['controller' => 'pages', 'action' => 'display']);
        $id = 'test/ spaces/漢字/la†în';
        $result = $route->match([
            'controller' => 'pages',
            'action' => 'display',
            $id
        ]);
        $expected = '/pages/test/%20spaces/%E6%BC%A2%E5%AD%97/la%E2%80%A0%C3%AEn';
        $this->assertEquals($expected, $result);
    }

    /**
     * test restructuring args with pass key
     *
     * @return void
     */
    public function testPassArgRestructure()
    {
        $route = new Route('/:controller/:action/:slug', [], [
            'pass' => ['slug']
        ]);
        $result = $route->parse('/posts/view/my-title');
        $expected = [
            'controller' => 'posts',
            'action' => 'view',
            'slug' => 'my-title',
            'pass' => ['my-title'],
            '_matchedRoute' => '/:controller/:action/:slug'
        ];
        $this->assertEquals($expected, $result, 'Slug should have moved');
    }

    /**
     * Test the /** special type on parsing.
     *
     * @return void
     */
    public function testParseTrailing()
    {
        $route = new Route('/:controller/:action/**');
        $result = $route->parse('/posts/index/1/2/3/foo:bar');
        $expected = [
            'controller' => 'posts',
            'action' => 'index',
            'pass' => ['1/2/3/foo:bar'],
            '_matchedRoute' => '/:controller/:action/**',
        ];
        $this->assertEquals($expected, $result);

        $result = $route->parse('/posts/index/http://example.com');
        $expected = [
            'controller' => 'posts',
            'action' => 'index',
            'pass' => ['http://example.com'],
            '_matchedRoute' => '/:controller/:action/**',
        ];
        $this->assertEquals($expected, $result);
    }

    /**
     * Test the /** special type on parsing - UTF8.
     *
     * @return void
     */
    public function testParseTrailingUTF8()
    {
        $route = new Route('/category/**', ['controller' => 'categories', 'action' => 'index']);
        $result = $route->parse('/category/%D9%85%D9%88%D8%A8%D8%A7%DB%8C%D9%84');
        $expected = [
            'controller' => 'categories',
            'action' => 'index',
            'pass' => ['موبایل'],
            '_matchedRoute' => '/category/**',
        ];
        $this->assertEquals($expected, $result);
    }

    /**
     * test getName();
     *
     * @return void
     */
    public function testGetName()
    {
        $route = new Route('/foo/bar', [], ['_name' => 'testing']);
        $this->assertEquals('', $route->getName());

        $route = new Route('/:controller/:action');
        $this->assertEquals('_controller:_action', $route->getName());

        $route = new Route('/articles/:action', ['controller' => 'posts']);
        $this->assertEquals('posts:_action', $route->getName());

        $route = new Route('/articles/list', ['controller' => 'posts', 'action' => 'index']);
        $this->assertEquals('posts:index', $route->getName());

        $route = new Route('/:controller/:action', ['action' => 'index']);
        $this->assertEquals('_controller:_action', $route->getName());
    }

    /**
     * Test getName() with plugins.
     *
     * @return void
     */
    public function testGetNamePlugins()
    {
        $route = new Route(
            '/a/:controller/:action',
            ['plugin' => 'asset']
        );
        $this->assertEquals('asset._controller:_action', $route->getName());

        $route = new Route(
            '/a/assets/:action',
            ['plugin' => 'asset', 'controller' => 'assets']
        );
        $this->assertEquals('asset.assets:_action', $route->getName());

        $route = new Route(
            '/assets/get',
            ['plugin' => 'asset', 'controller' => 'assets', 'action' => 'get']
        );
        $this->assertEquals('asset.assets:get', $route->getName());
    }

    /**
     * Test getName() with prefixes.
     *
     * @return void
     */
    public function testGetNamePrefix()
    {
        $route = new Route(
            '/admin/:controller/:action',
            ['prefix' => 'admin']
        );
        $this->assertEquals('admin:_controller:_action', $route->getName());

        $route = new Route(
            '/:prefix/assets/:action',
            ['controller' => 'assets']
        );
        $this->assertEquals('_prefix:assets:_action', $route->getName());

        $route = new Route(
            '/admin/assets/get',
            ['prefix' => 'admin', 'plugin' => 'asset', 'controller' => 'assets', 'action' => 'get']
        );
        $this->assertEquals('admin:asset.assets:get', $route->getName());

        $route = new Route(
            '/:prefix/:plugin/:controller/:action/*',
            []
        );
        $this->assertEquals('_prefix:_plugin._controller:_action', $route->getName());
    }

    /**
     * test that utf-8 patterns work for :section
     *
     * @return void
     */
    public function testUTF8PatternOnSection()
    {
        $route = new Route(
            '/:section',
            ['plugin' => 'blogs', 'controller' => 'posts', 'action' => 'index'],
            [
                'persist' => ['section'],
                'section' => 'آموزش|weblog'
            ]
        );

        $result = $route->parse('/%D8%A2%D9%85%D9%88%D8%B2%D8%B4');
        $expected = [
            'section' => 'آموزش',
            'plugin' => 'blogs',
            'controller' => 'posts',
            'action' => 'index',
            'pass' => [],
            '_matchedRoute' => '/:section',
        ];
        $this->assertEquals($expected, $result);

        $result = $route->parse('/weblog');
        $expected = [
            'section' => 'weblog',
            'plugin' => 'blogs',
            'controller' => 'posts',
            'action' => 'index',
            'pass' => [],
            '_matchedRoute' => '/:section',
        ];
        $this->assertEquals($expected, $result);
    }

    /**
     * Test getting the static path for a route.
     *
     * @return void
     */
    public function testStaticPath()
    {
        $route = new Route('/*', ['controller' => 'Pages', 'action' => 'display']);
        $this->assertEquals('/', $route->staticPath());

        $route = new Route('/pages/*', ['controller' => 'Pages', 'action' => 'display']);
        $this->assertEquals('/pages', $route->staticPath());

        $route = new Route('/pages/:id/*', ['controller' => 'Pages', 'action' => 'display']);
        $this->assertEquals('/pages/', $route->staticPath());

        $route = new Route('/:controller/:action/*');
        $this->assertEquals('/', $route->staticPath());

        $route = new Route('/books/reviews', ['controller' => 'Reviews', 'action' => 'index']);
        $this->assertEquals('/books/reviews', $route->staticPath());
    }

    /**
     * Test for __set_state magic method on CakeRoute
     *
     * @return void
     */
    public function testSetState()
    {
        $route = Route::__set_state([
            'keys' => [],
            'options' => [],
            'defaults' => [
                'controller' => 'pages',
                'action' => 'display',
                'home',
            ],
            'template' => '/',
            '_greedy' => false,
            '_compiledRoute' => null,
        ]);
        $this->assertInstanceOf('Cake\Routing\Route\Route', $route);
        $this->assertSame('/', $route->match(['controller' => 'pages', 'action' => 'display', 'home']));
        $this->assertFalse($route->match(['controller' => 'pages', 'action' => 'display', 'about']));
        $expected = [
            'controller' => 'pages',
            'action' => 'display',
            'pass' => ['home'],
            '_matchedRoute' => '/',
        ];
        $this->assertEquals($expected, $route->parse('/'));
    }
}
