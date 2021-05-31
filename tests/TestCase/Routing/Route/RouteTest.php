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
 * @since         2.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Routing\Route;

use Cake\Core\Configure;
use Cake\Http\ServerRequest;
use Cake\Routing\Route\Route;
use Cake\Routing\Router;
use Cake\TestSuite\TestCase;
use TestApp\Routing\Route\ProtectedRoute;

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
    public function setUp(): void
    {
        parent::setUp();
        Configure::write('Routing', ['prefixes' => []]);
    }

    /**
     * Test the construction of a Route
     *
     * @return void
     */
    public function testConstruction()
    {
        $route = new Route('/:controller/:action/:id', [], ['id' => '[0-9]+']);

        $this->assertSame('/:controller/:action/:id', $route->template);
        $this->assertEquals([], $route->defaults);
        $this->assertEquals(['id' => '[0-9]+', '_ext' => []], $route->options);
        $this->assertFalse($route->compiled());
    }

    public function testConstructionWithInvalidMethod()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid HTTP method received. `NOPE` is invalid');
        $route = new Route('/books/reviews', ['controller' => 'Reviews', 'action' => 'index', '_method' => 'nope']);
    }

    /**
     * Test set middleware in the constructor
     *
     * @return void
     */
    public function testConstructorSetMiddleware()
    {
        $route = new Route('/:controller/:action/*', [], ['_middleware' => ['auth', 'cookie']]);
        $this->assertSame(['auth', 'cookie'], $route->getMiddleware());
    }

    /**
     * Test Route compiling.
     *
     * @return void
     */
    public function testBasicRouteCompiling()
    {
        $route = new Route('/', ['controller' => 'Pages', 'action' => 'display', 'home']);
        $result = $route->compile();
        $expected = '#^/*$#';
        $this->assertSame($expected, $result);
        $this->assertEquals([], $route->keys);

        $route = new Route('/:controller/:action', ['controller' => 'Posts']);
        $result = $route->compile();

        $this->assertMatchesRegularExpression($result, '/posts/edit');
        $this->assertMatchesRegularExpression($result, '/posts/super_delete');
        $this->assertDoesNotMatchRegularExpression($result, '/posts');
        $this->assertDoesNotMatchRegularExpression($result, '/posts/super_delete/1');
        $this->assertSame($result, $route->compile());

        $route = new Route('/posts/foo:id', ['controller' => 'Posts', 'action' => 'view']);
        $result = $route->compile();

        $this->assertMatchesRegularExpression($result, '/posts/foo:1');
        $this->assertMatchesRegularExpression($result, '/posts/foo:param');
        $this->assertDoesNotMatchRegularExpression($result, '/posts');
        $this->assertDoesNotMatchRegularExpression($result, '/posts/');

        $this->assertEquals(['id'], $route->keys);

        $route = new Route('/:plugin/:controller/:action/*', ['plugin' => 'test_plugin', 'action' => 'index']);
        $result = $route->compile();
        $this->assertMatchesRegularExpression($result, '/test_plugin/posts/index');
        $this->assertMatchesRegularExpression($result, '/test_plugin/posts/edit/5');
        $this->assertMatchesRegularExpression($result, '/test_plugin/posts/edit/5/name:value/nick:name');
    }

    /**
     * Test that single letter placeholders work.
     *
     * @return void
     */
    public function testRouteCompileSmallPlaceholders()
    {
        $route = new Route(
            '/fighters/:id/move/:x/:y',
            ['controller' => 'Fighters', 'action' => 'move'],
            ['id' => '\d+', 'x' => '\d+', 'y' => '\d+', 'pass' => ['id', 'x', 'y']]
        );
        $pattern = $route->compile();
        $this->assertMatchesRegularExpression($pattern, '/fighters/123/move/8/42');

        $result = $route->match([
            'controller' => 'Fighters',
            'action' => 'move',
            'id' => '123',
            'x' => '8',
            'y' => '42',
        ]);
        $this->assertSame('/fighters/123/move/8/42', $result);
    }

    /**
     * Test route compile with brace format.
     *
     * @return void
     */
    public function testRouteCompileBraces()
    {
        $route = new Route(
            '/fighters/{id}/move/{x}/{y}',
            ['controller' => 'Fighters', 'action' => 'move'],
            ['id' => '\d+', 'x' => '\d+', 'y' => '\d+', 'pass' => ['id', 'x', 'y']]
        );
        $this->assertMatchesRegularExpression($route->compile(), '/fighters/123/move/8/42');

        $result = $route->match([
            'controller' => 'Fighters',
            'action' => 'move',
            'id' => '123',
            'x' => '8',
            'y' => '42',
        ]);
        $this->assertSame('/fighters/123/move/8/42', $result);

        $route = new Route(
            '/images/{id}/{x}x{y}',
            ['controller' => 'Images', 'action' => 'view']
        );
        $this->assertMatchesRegularExpression($route->compile(), '/images/123/640x480');

        $result = $route->match([
            'controller' => 'Images',
            'action' => 'view',
            'id' => '123',
            'x' => '8',
            'y' => '42',
        ]);
        $this->assertSame('/images/123/8x42', $result);
    }

    /**
     * Test route compile with brace format.
     *
     * @return void
     */
    public function testRouteCompileBracesVariableName()
    {
        $route = new Route(
            '/fighters/{0id}',
            ['controller' => 'Fighters', 'action' => 'move']
        );
        $pattern = $route->compile();
        $this->assertDoesNotMatchRegularExpression($route->compile(), '/fighters/123', 'Placeholders must start with letter');

        $route = new Route('/fighters/{Id}', ['controller' => 'Fighters', 'action' => 'move']);
        $this->assertMatchesRegularExpression($route->compile(), '/fighters/123');

        $route = new Route('/fighters/{i_d}', ['controller' => 'Fighters', 'action' => 'move']);
        $this->assertMatchesRegularExpression($route->compile(), '/fighters/123');

        $route = new Route('/fighters/{id99}', ['controller' => 'Fighters', 'action' => 'move']);
        $this->assertMatchesRegularExpression($route->compile(), '/fighters/123');
    }

    /**
     * Test route compile with brace format.
     *
     * @return void
     */
    public function testRouteCompileBracesInvalid()
    {
        $route = new Route(
            '/fighters/{ id }',
            ['controller' => 'Fighters', 'action' => 'move']
        );
        $this->assertDoesNotMatchRegularExpression($route->compile(), '/fighters/123', 'no spaces in placeholder');

        $route = new Route(
            '/fighters/{i d}',
            ['controller' => 'Fighters', 'action' => 'move']
        );
        $this->assertDoesNotMatchRegularExpression($route->compile(), '/fighters/123', 'no spaces in placeholder');
    }

    /**
     * Test route compile with mixed placeholder types brace format.
     *
     * @return void
     */
    public function testRouteCompileMixedPlaceholders()
    {
        $route = new Route(
            '/images/{open/:id',
            ['controller' => 'Images', 'action' => 'open']
        );
        $pattern = $route->compile();
        $this->assertMatchesRegularExpression($pattern, '/images/{open/9', 'Need both {} to enable brace mode');
        $result = $route->match([
            'controller' => 'Images',
            'action' => 'open',
            'id' => 123,
        ]);
        $this->assertSame('/images/{open/123', $result);

        $route = new Route(
            '/fighters/{id}/move/{x}/:y',
            ['controller' => 'Fighters', 'action' => 'move'],
            ['id' => '\d+', 'x' => '\d+', 'pass' => ['id', 'x']]
        );
        $pattern = $route->compile();
        $this->assertMatchesRegularExpression($pattern, '/fighters/123/move/8/:y');

        $result = $route->match([
            'controller' => 'Fighters',
            'action' => 'move',
            'id' => '123',
            'x' => '8',
            '?' => ['y' => '9'],
        ]);
        $this->assertSame('/fighters/123/move/8/:y?y=9', $result);
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

        $result = $route->parse('/posts/index', 'GET');
        $this->assertArrayNotHasKey('_ext', $result);

        $result = $route->parse('/posts/index.pdf', 'GET');
        $this->assertArrayNotHasKey('_ext', $result);

        $result = $route->setExtensions(['pdf', 'json', 'xml', 'xml.gz'])->parse('/posts/index.pdf', 'GET');
        $this->assertSame('pdf', $result['_ext']);

        $result = $route->parse('/posts/index.json', 'GET');
        $this->assertSame('json', $result['_ext']);

        $result = $route->parse('/posts/index.xml', 'GET');
        $this->assertSame('xml', $result['_ext']);

        $result = $route->parse('/posts/index.xml.gz', 'GET');
        $this->assertSame('xml.gz', $result['_ext']);
    }

    /**
     * @return array
     */
    public function provideMatchParseExtension()
    {
        return [
            ['/foo/bar.xml', ['/foo/bar', 'xml'], ['xml', 'json', 'xml.gz']],
            ['/foo/bar.json', ['/foo/bar', 'json'], ['xml', 'json', 'xml.gz']],
            ['/foo/bar.xml.gz', ['/foo/bar', 'xml.gz'], ['xml', 'json', 'xml.gz']],
            ['/foo/with.dots.json.xml.zip', ['/foo/with.dots.json.xml', 'zip'], ['zip']],
            ['/foo/confusing.extensions.dots.json.xml.zip', ['/foo/confusing.extensions.dots.json.xml', 'zip'], ['json', 'xml', 'zip']],
            ['/foo/confusing.extensions.dots.json.xml', ['/foo/confusing.extensions.dots.json', 'xml'], ['json', 'xml', 'zip']],
            ['/foo/confusing.extensions.dots.json', ['/foo/confusing.extensions.dots', 'json'], ['json', 'xml', 'zip']],
        ];
    }

    /**
     * Expects _parseExtension to match extensions in URLs
     *
     * @param string $url
     * @param array $expected
     * @param array $ext
     * @return void
     * @dataProvider provideMatchParseExtension
     */
    public function testMatchParseExtension($url, array $expected, array $ext)
    {
        $route = new ProtectedRoute('/:controller/:action/*', [], ['_ext' => $ext]);
        $result = $route->parseExtension($url);
        $this->assertEquals($expected, $result);
    }

    /**
     * @return array
     */
    public function provideNoMatchParseExtension()
    {
        return [
            ['/foo/bar', ['xml']],
            ['/foo/bar.zip', ['xml']],
            ['/foo/bar.xml.zip', ['xml']],
            ['/foo/bar.', ['xml']],
            ['/foo/bar.xml', []],
            ['/foo/bar...xml...zip...', ['xml']],
        ];
    }

    /**
     * Expects _parseExtension to not match extensions in URLs
     *
     * @param string $url
     * @param array $ext
     * @return void
     * @dataProvider provideNoMatchParseExtension
     */
    public function testNoMatchParseExtension($url, array $ext)
    {
        $route = new ProtectedRoute('/:controller/:action/*', [], ['_ext' => $ext]);
        [$outUrl, $outExt] = $route->parseExtension($url);
        $this->assertSame($url, $outUrl);
        $this->assertNull($outExt);
    }

    /**
     * Expects extensions to be set
     *
     * @return void
     */
    public function testSetExtensions()
    {
        $route = new ProtectedRoute('/:controller/:action/*', []);
        $this->assertEquals([], $route->getExtensions());
        $route->setExtensions(['xml']);
        $this->assertEquals(['xml'], $route->getExtensions());
        $route->setExtensions(['xml', 'json', 'zip']);
        $this->assertEquals(['xml', 'json', 'zip'], $route->getExtensions());
        $route->setExtensions([]);
        $this->assertEquals([], $route->getExtensions());

        $route = new ProtectedRoute('/:controller/:action/*', [], ['_ext' => ['one', 'two']]);
        $this->assertEquals(['one', 'two'], $route->getExtensions());
    }

    /**
     * Expects extensions to be return.
     *
     * @return void
     */
    public function testGetExtensions()
    {
        $route = new ProtectedRoute('/:controller/:action/*', []);
        $this->assertEquals([], $route->getExtensions());

        $route = new ProtectedRoute('/:controller/:action/*', [], ['_ext' => ['one', 'two']]);
        $this->assertEquals(['one', 'two'], $route->getExtensions());

        $route = new ProtectedRoute('/:controller/:action/*', []);
        $this->assertEquals([], $route->getExtensions());
        $route->setExtensions(['xml', 'json', 'zip']);
        $this->assertEquals(['xml', 'json', 'zip'], $route->getExtensions());
    }

    /**
     * Test that route parameters that overlap don't cause errors.
     *
     * @return void
     */
    public function testRouteParameterOverlap()
    {
        $route = new Route('/invoices/add/:idd/:id', ['controller' => 'Invoices', 'action' => 'add']);
        $result = $route->compile();
        $this->assertMatchesRegularExpression($result, '/invoices/add/1/3');

        $route = new Route('/invoices/add/:id/:idd', ['controller' => 'Invoices', 'action' => 'add']);
        $result = $route->compile();
        $this->assertMatchesRegularExpression($result, '/invoices/add/1/3');
    }

    /**
     * Test compiling routes with keys that have patterns
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
        $this->assertMatchesRegularExpression($result, '/posts/edit/1');
        $this->assertMatchesRegularExpression($result, '/posts/view/518098');
        $this->assertDoesNotMatchRegularExpression($result, '/posts/edit/name-of-post');
        $this->assertDoesNotMatchRegularExpression($result, '/posts/edit/4/other:param');
        $this->assertEquals(['id', 'controller', 'action'], $route->keys);

        $route = new Route(
            '/:lang/:controller/:action/:id',
            ['controller' => 'Testing4'],
            ['id' => Router::ID, 'lang' => '[a-z]{3}']
        );
        $result = $route->compile();
        $this->assertMatchesRegularExpression($result, '/eng/posts/edit/1');
        $this->assertMatchesRegularExpression($result, '/cze/articles/view/1');
        $this->assertDoesNotMatchRegularExpression($result, '/language/articles/view/2');
        $this->assertDoesNotMatchRegularExpression($result, '/eng/articles/view/name-of-article');
        $this->assertEquals(['lang', 'id', 'controller', 'action'], $route->keys);

        foreach ([':', '@', ';', '$', '-'] as $delim) {
            $route = new Route('/posts/:id' . $delim . ':title');
            $result = $route->compile();

            $this->assertMatchesRegularExpression($result, '/posts/1' . $delim . 'name-of-article');
            $this->assertMatchesRegularExpression($result, '/posts/13244' . $delim . 'name-of_Article[]');
            $this->assertDoesNotMatchRegularExpression($result, '/posts/11!nameofarticle');
            $this->assertDoesNotMatchRegularExpression($result, '/posts/11');

            $this->assertEquals(['title', 'id'], $route->keys);
        }

        $route = new Route(
            '/posts/:id::title/:year',
            ['controller' => 'Posts', 'action' => 'view'],
            ['id' => Router::ID, 'year' => Router::YEAR, 'title' => '[a-z-_]+']
        );
        $result = $route->compile();
        $this->assertMatchesRegularExpression($result, '/posts/1:name-of-article/2009/');
        $this->assertMatchesRegularExpression($result, '/posts/13244:name-of-article/1999');
        $this->assertDoesNotMatchRegularExpression($result, '/posts/hey_now:nameofarticle');
        $this->assertDoesNotMatchRegularExpression($result, '/posts/:nameofarticle/2009');
        $this->assertDoesNotMatchRegularExpression($result, '/posts/:nameofarticle/01');
        $this->assertEquals(['year', 'title', 'id'], $route->keys);

        $route = new Route(
            '/posts/:url_title-(uuid::id)',
            ['controller' => 'Posts', 'action' => 'view'],
            ['pass' => ['id', 'url_title'], 'id' => Router::ID]
        );
        $result = $route->compile();
        $this->assertMatchesRegularExpression($result, '/posts/some_title_for_article-(uuid:12534)/');
        $this->assertMatchesRegularExpression($result, '/posts/some_title_for_article-(uuid:12534)');
        $this->assertDoesNotMatchRegularExpression($result, '/posts/');
        $this->assertDoesNotMatchRegularExpression($result, '/posts/nameofarticle');
        $this->assertDoesNotMatchRegularExpression($result, '/posts/nameofarticle-12347');
        $this->assertEquals(['url_title', 'id'], $route->keys);
    }

    /**
     * Test route with unicode
     *
     * @return void
     */
    public function testCompileWithUnicodePatterns()
    {
        $route = new Route(
            '/test/:slug',
            ['controller' => 'Pages', 'action' => 'display'],
            ['pass' => ['slug'], 'multibytePattern' => false, 'slug' => '[A-zА-я\-\ ]+']
        );
        $result = $route->compile();
        $this->assertDoesNotMatchRegularExpression($result, '/test/bla-blan-тест');

        $route = new Route(
            '/test/:slug',
            ['controller' => 'Pages', 'action' => 'display'],
            ['pass' => ['slug'], 'multibytePattern' => true, 'slug' => '[A-zА-я\-\ ]+']
        );
        $result = $route->compile();
        $this->assertMatchesRegularExpression($result, '/test/bla-blan-тест');
    }

    /**
     * Test more complex route compiling & parsing with mid route greedy stars
     * and optional routing parameters
     *
     * @return void
     */
    public function testComplexRouteCompilingAndParsing()
    {
        $route = new Route(
            '/posts/:month/:day/:year/*',
            ['controller' => 'Posts', 'action' => 'view'],
            ['year' => Router::YEAR, 'month' => Router::MONTH, 'day' => Router::DAY]
        );
        $result = $route->compile();
        $this->assertMatchesRegularExpression($result, '/posts/08/01/2007/title-of-post');
        $result = $route->parse('/posts/08/01/2007/title-of-post', 'GET');

        $this->assertCount(7, $result);
        $this->assertEquals($result['controller'], 'Posts');
        $this->assertEquals($result['action'], 'view');
        $this->assertEquals($result['year'], '2007');
        $this->assertEquals($result['month'], '08');
        $this->assertEquals($result['day'], '01');
        $this->assertEquals($result['pass'][0], 'title-of-post');
        $this->assertEquals($result['_matchedRoute'], '/posts/:month/:day/:year/*');

        $route = new Route(
            '/:extra/page/:slug/*',
            ['controller' => 'Pages', 'action' => 'view', 'extra' => null],
            ['extra' => '[a-z1-9_]*', 'slug' => '[a-z1-9_]+', 'action' => 'view']
        );
        $result = $route->compile();

        $this->assertMatchesRegularExpression($result, '/some_extra/page/this_is_the_slug');
        $this->assertMatchesRegularExpression($result, '/page/this_is_the_slug');
        $this->assertEquals(['slug', 'extra'], $route->keys);
        $this->assertEquals(['extra' => '[a-z1-9_]*', 'slug' => '[a-z1-9_]+', 'action' => 'view', '_ext' => []], $route->options);
        $expected = [
            'controller' => 'Pages',
            'action' => 'view',
        ];
        $this->assertEquals($expected, $route->defaults);

        $route = new Route(
            '/:controller/:action/*',
            ['project' => false],
            [
                'controller' => 'source|wiki|commits|tickets|comments|view',
                'action' => 'branches|history|branch|logs|view|start|add|edit|modify',
            ]
        );
        $this->assertNull($route->parse('/chaw_test/wiki', 'GET'));

        $result = $route->compile();
        $this->assertDoesNotMatchRegularExpression($result, '/some_project/source');
        $this->assertMatchesRegularExpression($result, '/source/view');
        $this->assertMatchesRegularExpression($result, '/source/view/other/params');
        $this->assertDoesNotMatchRegularExpression($result, '/chaw_test/wiki');
        $this->assertDoesNotMatchRegularExpression($result, '/source/weird_action');
    }

    /**
     * Test that routes match their pattern.
     *
     * @return void
     */
    public function testMatchBasic()
    {
        $route = new Route('/:controller/:action/:id', ['plugin' => null]);
        $result = $route->match(['controller' => 'Posts', 'action' => 'view', 'plugin' => null]);
        $this->assertNull($result);

        $result = $route->match(['plugin' => null, 'controller' => 'Posts', 'action' => 'view', 0]);
        $this->assertNull($result);

        $result = $route->match(['plugin' => null, 'controller' => 'Posts', 'action' => 'view', 'id' => 1]);
        $this->assertSame('/Posts/view/1', $result);

        $route = new Route('/', ['controller' => 'Pages', 'action' => 'display', 'home']);
        $result = $route->match(['controller' => 'Pages', 'action' => 'display', 'home']);
        $this->assertSame('/', $result);

        $result = $route->match(['controller' => 'Pages', 'action' => 'display', 'about']);
        $this->assertNull($result);

        $route = new Route('/pages/*', ['controller' => 'Pages', 'action' => 'display']);
        $result = $route->match(['controller' => 'Pages', 'action' => 'display', 'home']);
        $this->assertSame('/pages/home', $result);

        $result = $route->match(['controller' => 'Pages', 'action' => 'display', 'about']);
        $this->assertSame('/pages/about', $result);

        $route = new Route('/blog/:action', ['controller' => 'Posts']);
        $result = $route->match(['controller' => 'Posts', 'action' => 'view']);
        $this->assertSame('/blog/view', $result);

        $result = $route->match(['controller' => 'Posts', 'action' => 'view', '?' => ['id' => 2]]);
        $this->assertSame('/blog/view?id=2', $result);

        $result = $route->match(['controller' => 'Nodes', 'action' => 'view']);
        $this->assertNull($result);

        $result = $route->match(['controller' => 'Posts', 'action' => 'view', 1]);
        $this->assertNull($result);

        $route = new Route('/foo/:controller/:action', ['action' => 'index']);
        $result = $route->match(['controller' => 'Posts', 'action' => 'view']);
        $this->assertSame('/foo/Posts/view', $result);

        $route = new Route('/:plugin/:id/*', ['controller' => 'Posts', 'action' => 'view']);
        $result = $route->match(['plugin' => 'test', 'controller' => 'Posts', 'action' => 'view', 'id' => '1']);
        $this->assertSame('/test/1/', $result);

        $result = $route->match(['plugin' => 'fo', 'controller' => 'Posts', 'action' => 'view', 'id' => '1', '0']);
        $this->assertSame('/fo/1/0', $result);

        $result = $route->match(['plugin' => 'fo', 'controller' => 'Nodes', 'action' => 'view', 'id' => 1]);
        $this->assertNull($result);

        $result = $route->match(['plugin' => 'fo', 'controller' => 'Posts', 'action' => 'edit', 'id' => 1]);
        $this->assertNull($result);

        $route = new Route('/admin/subscriptions/:action/*', [
            'controller' => 'Subscribe', 'prefix' => 'Admin',
        ]);

        $url = ['controller' => 'Subscribe', 'prefix' => 'Admin', 'action' => 'edit', 1];
        $result = $route->match($url);
        $expected = '/admin/subscriptions/edit/1';
        $this->assertSame($expected, $result);

        $url = [
            'controller' => 'Subscribe',
            'prefix' => 'Admin',
            'action' => 'edit_admin_e',
            1,
        ];
        $result = $route->match($url);
        $expected = '/admin/subscriptions/edit_admin_e/1';
        $this->assertSame($expected, $result);
    }

    /**
     * Test match() with persist option
     *
     * @return void
     */
    public function testMatchWithPersistOption()
    {
        $context = [
            'params' => ['lang' => 'en'],
        ];
        $route = new Route('/:lang/:controller/:action', [], ['persist' => ['lang']]);
        $result = $route->match(
            ['controller' => 'Tasks', 'action' => 'add'],
            $context
        );
        $this->assertSame('/en/Tasks/add', $result);
    }

    /**
     * Test match() with _host and other keys.
     *
     * @return void
     */
    public function testMatchWithHostKeys()
    {
        $context = [
            '_host' => 'foo.com',
            '_scheme' => 'http',
            '_port' => 80,
            '_base' => '',
        ];
        $route = new Route('/:controller/:action');
        $result = $route->match(
            ['controller' => 'Posts', 'action' => 'index', '_host' => 'example.com'],
            $context
        );
        // Http has port 80 as default, do not include it in the url
        $this->assertSame('http://example.com/Posts/index', $result);

        $result = $route->match(
            ['controller' => 'Posts', 'action' => 'index', '_scheme' => 'webcal'],
            $context
        );
        // Webcal is not on port 80 by default, include it in url
        $this->assertSame('webcal://foo.com:80/Posts/index', $result);

        $result = $route->match(
            ['controller' => 'Posts', 'action' => 'index', '_port' => '8080'],
            $context
        );
        $this->assertSame('http://foo.com:8080/Posts/index', $result);

        $result = $route->match(
            ['controller' => 'Posts', 'action' => 'index', '_base' => '/dir'],
            $context
        );
        $this->assertSame('/dir/Posts/index', $result);

        $result = $route->match(
            [
                'controller' => 'Posts',
                'action' => 'index',
                '_port' => '8080',
                '_host' => 'example.com',
                '_scheme' => 'https',
                '_base' => '/dir',
            ],
            $context
        );
        $this->assertSame('https://example.com:8080/dir/Posts/index', $result);

        $context = [
            '_host' => 'foo.com',
            '_scheme' => 'http',
            '_port' => 8080,
            '_base' => '',
        ];
        $result = $route->match(
            [
                'controller' => 'Posts',
                'action' => 'index',
                '_port' => '8080',
                '_host' => 'example.com',
                '_scheme' => 'https',
                '_base' => '/dir',
            ],
            $context
        );
        // Https scheme is not on port 8080 by default, include the port
        $this->assertSame('https://example.com:8080/dir/Posts/index', $result);
    }

    /**
     * Test that the _host option sets the default host.
     *
     * @return void
     */
    public function testMatchWithHostOption()
    {
        $route = new Route(
            '/fallback',
            ['controller' => 'Articles', 'action' => 'index'],
            ['_host' => 'www.example.com']
        );
        $result = $route->match([
            'controller' => 'Articles',
            'action' => 'index',
        ]);
        $this->assertSame('http://www.example.com/fallback', $result);
    }

    /**
     * Test wildcard host options
     *
     * @return void
     */
    public function testMatchWithHostWildcardOption()
    {
        $route = new Route(
            '/fallback',
            ['controller' => 'Articles', 'action' => 'index'],
            ['_host' => '*.example.com']
        );
        $result = $route->match([
            'controller' => 'Articles',
            'action' => 'index',
        ]);
        $this->assertNull($result, 'No request context means no match');

        $result = $route->match([
            'controller' => 'Articles',
            'action' => 'index',
        ], ['_host' => 'wrong.com']);
        $this->assertNull($result, 'Request context has bad host');

        $result = $route->match([
            'controller' => 'Articles',
            'action' => 'index',
            '_host' => 'wrong.com',
        ]);
        $this->assertNull($result, 'Url param is wrong');

        $result = $route->match([
            'controller' => 'Articles',
            'action' => 'index',
            '_host' => 'foo.example.com',
        ]);
        $this->assertSame('http://foo.example.com/fallback', $result);

        $result = $route->match([
            'controller' => 'Articles',
            'action' => 'index',
        ], [
            '_host' => 'foo.example.com',
        ]);
        $this->assertSame('http://foo.example.com/fallback', $result);

        $result = $route->match([
            'controller' => 'Articles',
            'action' => 'index',
        ], [
            '_scheme' => 'https',
            '_host' => 'foo.example.com',
            '_port' => 8080,
        ]);
        // When the port and scheme in the context are not present in the original url, they should be added
        $this->assertSame('https://foo.example.com:8080/fallback', $result);
    }

    /**
     * Test that non-greedy routes fail with extra passed args
     *
     * @return void
     */
    public function testMatchGreedyRouteFailurePassedArg()
    {
        $route = new Route('/:controller/:action', ['plugin' => null]);
        $result = $route->match(['controller' => 'Posts', 'action' => 'view', '0']);
        $this->assertNull($result);

        $route = new Route('/:controller/:action', ['plugin' => null]);
        $result = $route->match(['controller' => 'Posts', 'action' => 'view', 'test']);
        $this->assertNull($result);
    }

    /**
     * Test that falsey values do not interrupt a match.
     *
     * @return void
     */
    public function testMatchWithFalseyValues()
    {
        $route = new Route('/:controller/:action/*', ['plugin' => null]);
        $result = $route->match([
            'controller' => 'Posts', 'action' => 'index', 'plugin' => null, 'admin' => false,
        ]);
        $this->assertSame('/Posts/index/', $result);
    }

    /**
     * Test match() with greedy routes, and passed args.
     *
     * @return void
     */
    public function testMatchWithPassedArgs()
    {
        $route = new Route('/:controller/:action/*', ['plugin' => null]);

        $result = $route->match(['controller' => 'Posts', 'action' => 'view', 'plugin' => null, 5]);
        $this->assertSame('/Posts/view/5', $result);

        $result = $route->match(['controller' => 'Posts', 'action' => 'view', 'plugin' => null, 0]);
        $this->assertSame('/Posts/view/0', $result);

        $result = $route->match(['controller' => 'Posts', 'action' => 'view', 'plugin' => null, '0']);
        $this->assertSame('/Posts/view/0', $result);

        $result = $route->match(['controller' => 'Posts', 'action' => 'view', 'plugin' => null, 'word space']);
        $this->assertSame('/Posts/view/word%20space', $result);

        $route = new Route('/test2/*', ['controller' => 'Pages', 'action' => 'display', 2]);
        $result = $route->match(['controller' => 'Pages', 'action' => 'display', 1]);
        $this->assertNull($result);

        $result = $route->match(['controller' => 'Pages', 'action' => 'display', 2, 'something']);
        $this->assertSame('/test2/something', $result);

        $result = $route->match(['controller' => 'Pages', 'action' => 'display', 5, 'something']);
        $this->assertNull($result);
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
            'slug' => 'second',
        ]);
        $this->assertSame('/blog/1-second', $result);

        $result = $route->match([
            'controller' => 'Blog',
            'action' => 'view',
            1,
            'second',
        ]);
        $this->assertSame('/blog/1-second', $result);

        $result = $route->match([
            'controller' => 'Blog',
            'action' => 'view',
            1,
            'second',
            '?' => ['query' => 'string'],
        ]);
        $this->assertSame('/blog/1-second?query=string', $result);

        $result = $route->match([
            'controller' => 'Blog',
            'action' => 'view',
            1 => 2,
            2 => 'second',
        ]);
        $this->assertNull($result, 'Positional args must match exactly.');
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
            '?' => ['query' => 'string'],
        ]);
        $this->assertSame('/blog/1-second/third/fourth?query=string', $result);

        $result = $route->match([
            'controller' => 'Blog',
            'action' => 'view',
            1,
            'second',
            'third',
            'fourth',
            '?' => ['query' => 'string'],
        ]);
        $this->assertSame('/blog/1-second/third/fourth?query=string', $result);
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
            'controller' => 'Posts',
            'action' => 'index',
            '_ext' => 'json',
        ]);
        $this->assertSame('/Posts/index.json', $result);

        $route = new Route('/:controller/:action/*');
        $result = $route->match([
            'controller' => 'Posts',
            'action' => 'index',
            '_ext' => 'json',
        ]);
        $this->assertSame('/Posts/index.json', $result);

        $result = $route->match([
            'controller' => 'Posts',
            'action' => 'view',
            1,
            '_ext' => 'json',
        ]);
        $this->assertSame('/Posts/view/1.json', $result);

        $result = $route->match([
            'controller' => 'Posts',
            'action' => 'view',
            1,
            '_ext' => 'json',
            '?' => ['id' => 'b', 'c' => 'd', ],
        ]);
        $this->assertSame('/Posts/view/1.json?id=b&c=d', $result);

        $result = $route->match([
            'controller' => 'Posts',
            'action' => 'index',
            '_ext' => 'json.gz',
        ]);
        $this->assertSame('/Posts/index.json.gz', $result);
    }

    /**
     * Test that match with patterns works.
     *
     * @return void
     */
    public function testMatchWithPatterns()
    {
        $route = new Route('/:controller/:action/:id', ['plugin' => null], ['id' => '[0-9]+']);
        $result = $route->match(['controller' => 'Posts', 'action' => 'view', 'id' => 'foo']);
        $this->assertNull($result);

        $result = $route->match(['plugin' => null, 'controller' => 'Posts', 'action' => 'view', 'id' => 9]);
        $this->assertSame('/Posts/view/9', $result);

        $result = $route->match(['plugin' => null, 'controller' => 'Posts', 'action' => 'view', 'id' => '9']);
        $this->assertSame('/Posts/view/9', $result);

        $result = $route->match(['plugin' => null, 'controller' => 'Posts', 'action' => 'view', 'id' => '922']);
        $this->assertSame('/Posts/view/922', $result);

        $result = $route->match(['plugin' => null, 'controller' => 'Posts', 'action' => 'view', 'id' => 'a99']);
        $this->assertNull($result);
    }

    /**
     * Test that match() with multibyte pattern
     *
     * @return void
     */
    public function testMatchWithMultibytePattern()
    {
        $route = new Route(
            '/articles/:action/:id',
            ['controller' => 'Articles'],
            ['multibytePattern' => true, 'id' => '\pL+']
        );
        $result = $route->match([
            'controller' => 'Articles',
            'action' => 'view',
            'id' => "\xC4\x81",
        ]);
        $this->assertSame("/articles/view/\xC4\x81", $result);
    }

    /**
     * Test that match() matches explicit GET routes
     *
     * @return void
     */
    public function testMatchWithExplicitGet()
    {
        $route = new Route(
            '/anything',
            ['controller' => 'Articles', 'action' => 'foo', '_method' => 'GET']
        );
        $result = $route->match([
            'controller' => 'Articles',
            'action' => 'foo',
        ]);
        $this->assertSame('/anything', $result);
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
            'controller' => 'Posts',
            'action' => 'index',
            0,
            '?' => [
                'test' => 'var',
                'var2' => 'test2',
                'more' => 'test data',
            ],
        ]);
        $expected = '/Posts/index/0?test=var&amp;var2=test2&amp;more=test+data';
        $this->assertSame($expected, $result);
        ini_set('arg_separator.output', $restore);
    }

    /**
     * Ensure that parseRequest() calls parse() as that is required
     * for backwards compat
     *
     * @return void
     */
    public function testParseRequestDelegates()
    {
        /** @var \Cake\Routing\Route\Route|\PHPUnit\Framework\MockObject\MockObject $route */
        $route = $this->getMockBuilder('Cake\Routing\Route\Route')
            ->onlyMethods(['parse'])
            ->setConstructorArgs(['/forward', ['controller' => 'Articles', 'action' => 'index']])
            ->getMock();

        $route->expects($this->once())
            ->method('parse')
            ->with('/forward', 'GET')
            ->will($this->returnValue(['works!']));

        $request = new ServerRequest([
            'environment' => [
                'REQUEST_METHOD' => 'GET',
                'PATH_INFO' => '/forward',
            ],
        ]);
        $result = $route->parseRequest($request);
        $this->assertNotEmpty($result);
    }

    /**
     * Test that parseRequest() applies host conditions
     *
     * @return void
     */
    public function testParseRequestHostConditions()
    {
        $route = new Route(
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
        $result = $route->parseRequest($request);
        $expected = [
            'controller' => 'Articles',
            'action' => 'index',
            'pass' => [],
            '_matchedRoute' => '/fallback',
        ];
        $this->assertEquals($expected, $result, 'Should match, domain is correct');

        $request = new ServerRequest([
            'environment' => [
                'HTTP_HOST' => 'foo.bar.example.com',
                'PATH_INFO' => '/fallback',
            ],
        ]);
        $result = $route->parseRequest($request);
        $this->assertEquals($expected, $result, 'Should match, domain is a matching subdomain');

        $request = new ServerRequest([
            'environment' => [
                'HTTP_HOST' => 'example.test.com',
                'PATH_INFO' => '/fallback',
            ],
        ]);
        $this->assertNull($route->parseRequest($request));
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
            ['controller' => 'Testing4', 'id' => null],
            ['id' => Router::ID]
        );
        $route->compile();
        $result = $route->parse('/Posts/view/1', 'GET');
        $this->assertSame('Posts', $result['controller']);
        $this->assertSame('view', $result['action']);
        $this->assertSame('1', $result['id']);

        $route = new Route(
            '/admin/:controller',
            ['prefix' => 'Admin', 'action' => 'index']
        );
        $route->compile();
        $result = $route->parse('/admin/', 'GET');
        $this->assertNull($result);

        $result = $route->parse('/admin/Posts', 'GET');
        $this->assertSame('Posts', $result['controller']);
        $this->assertSame('index', $result['action']);

        $route = new Route(
            '/media/search/*',
            ['controller' => 'Media', 'action' => 'search']
        );
        $result = $route->parse('/media/search', 'GET');
        $this->assertSame('Media', $result['controller']);
        $this->assertSame('search', $result['action']);
        $this->assertEquals([], $result['pass']);

        $result = $route->parse('/media/search/tv/shows', 'GET');
        $this->assertSame('Media', $result['controller']);
        $this->assertSame('search', $result['action']);
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
        $result = $route->parse('/posts/%E2%88%82%E2%88%82', 'GET');
        $this->assertSame('posts', $result['controller']);
        $this->assertSame('view', $result['action']);
        $this->assertSame('∂∂', $result['slug']);

        $result = $route->parse('/posts/∂∂', 'GET');
        $this->assertSame('posts', $result['controller']);
        $this->assertSame('view', $result['action']);
        $this->assertSame('∂∂', $result['slug']);
    }

    /**
     * Test numerically indexed defaults, get appended to pass
     *
     * @return void
     */
    public function testParseWithPassDefaults()
    {
        $route = new Route('/:controller', ['action' => 'display', 'home']);
        $result = $route->parse('/Posts', 'GET');
        $expected = [
            'controller' => 'Posts',
            'action' => 'display',
            'pass' => ['home'],
            '_matchedRoute' => '/:controller',
        ];
        $this->assertEquals($expected, $result);
    }

    /**
     * Test that middleware is returned from parse()
     *
     * @return void
     */
    public function testParseWithMiddleware()
    {
        $route = new Route('/:controller', ['action' => 'display', 'home']);
        $route->setMiddleware(['auth', 'cookie']);
        $result = $route->parse('/Posts', 'GET');
        $expected = [
            'controller' => 'Posts',
            'action' => 'display',
            'pass' => ['home'],
            '_matchedRoute' => '/:controller',
            '_middleware' => ['auth', 'cookie'],
        ];
        $this->assertEquals($expected, $result);
    }

    /**
     * Test that http header conditions can cause route failures.
     *
     * @return void
     */
    public function testParseWithHttpHeaderConditions()
    {
        $route = new Route('/sample', ['controller' => 'Posts', 'action' => 'index', '_method' => 'POST']);
        $this->assertNull($route->parse('/sample', 'GET'));

        $expected = [
            'controller' => 'Posts',
            'action' => 'index',
            'pass' => [],
            '_method' => 'POST',
            '_matchedRoute' => '/sample',
        ];
        $this->assertEquals($expected, $route->parse('/sample', 'post'));
    }

    /**
     * Test that http header conditions can cause route failures.
     * And that http method names are normalized.
     *
     * @return void
     */
    public function testParseWithMultipleHttpMethodConditions()
    {
        $route = new Route('/sample', [
            'controller' => 'Posts',
            'action' => 'index',
            '_method' => ['put', 'post'],
        ]);
        $this->assertNull($route->parse('/sample', 'GET'));

        $expected = [
            'controller' => 'Posts',
            'action' => 'index',
            'pass' => [],
            '_method' => ['PUT', 'POST'],
            '_matchedRoute' => '/sample',
        ];
        $this->assertEquals($expected, $route->parse('/sample', 'POST'));
    }

    /**
     * Test that http header conditions can work with URL generation
     *
     * @return void
     */
    public function testMatchWithMultipleHttpMethodConditions()
    {
        $route = new Route('/sample', [
            'controller' => 'Posts',
            'action' => 'index',
            '_method' => ['PUT', 'POST'],
        ]);
        $url = [
            'controller' => 'Posts',
            'action' => 'index',
        ];
        $this->assertNull($route->match($url));

        $url = [
            'controller' => 'Posts',
            'action' => 'index',
            '_method' => 'GET',
        ];
        $this->assertNull($route->match($url));

        $url = [
            'controller' => 'Posts',
            'action' => 'index',
            '_method' => 'PUT',
        ];
        $this->assertSame('/sample', $route->match($url));

        $url = [
            'controller' => 'Posts',
            'action' => 'index',
            '_method' => 'POST',
        ];
        $this->assertSame('/sample', $route->match($url));

        $url = [
            'controller' => 'Posts',
            'action' => 'index',
            '_method' => ['PUT', 'POST'],
        ];
        $this->assertSame('/sample', $route->match($url));
    }

    /**
     * Test that patterns work for :action
     *
     * @return void
     */
    public function testPatternOnAction()
    {
        $route = new Route(
            '/blog/:action/*',
            ['controller' => 'BlogPosts'],
            ['action' => 'other|actions']
        );
        $result = $route->match(['controller' => 'BlogPosts', 'action' => 'foo']);
        $this->assertNull($result);

        $result = $route->match(['controller' => 'BlogPosts', 'action' => 'actions']);
        $this->assertNotEmpty($result);

        $result = $route->parse('/blog/other', 'GET');
        $expected = [
            'controller' => 'BlogPosts',
            'action' => 'other',
            'pass' => [],
            '_matchedRoute' => '/blog/:action/*',
        ];
        $this->assertEquals($expected, $result);

        $result = $route->parse('/blog/foobar', 'GET');
        $this->assertNull($result);
    }

    /**
     * Test the parseArgs method
     *
     * @return void
     */
    public function testParsePassedArgument()
    {
        $route = new Route('/:controller/:action/*');
        $result = $route->parse('/Posts/edit/1/2/0', 'GET');
        $expected = [
            'controller' => 'Posts',
            'action' => 'edit',
            'pass' => ['1', '2', '0'],
            '_matchedRoute' => '/:controller/:action/*',
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
        $this->assertSame($expected, $result);
    }

    /**
     * Test match() with trailing ** style routes.
     *
     * @return void
     */
    public function testMatchTrailing()
    {
        $route = new Route('/pages/**', ['controller' => 'Pages', 'action' => 'display']);
        $id = 'test/ spaces/漢字/la†în';
        $result = $route->match([
            'controller' => 'Pages',
            'action' => 'display',
            $id,
        ]);
        $expected = '/pages/test/%20spaces/%E6%BC%A2%E5%AD%97/la%E2%80%A0%C3%AEn';
        $this->assertSame($expected, $result);
    }

    /**
     * Test match handles optional keys
     *
     * @return void
     */
    public function testMatchNullValueOptionalKey()
    {
        $route = new Route('/path/:optional/fixed');
        $this->assertSame('/path/fixed', $route->match(['optional' => null]));

        $route = new Route('/path/{optional}/fixed');
        $this->assertSame('/path/fixed', $route->match(['optional' => null]));
    }

    /**
     * Test matching fails on required keys (controller/action)
     *
     * @return void
     */
    public function testMatchControllerRequiredKeys()
    {
        $route = new Route('/:controller/', ['action' => 'index']);
        $this->assertNull($route->match(['controller' => null, 'action' => 'index']));

        $route = new Route('/test/:action', ['controller' => 'Thing']);
        $this->assertNull($route->match(['action' => null, 'controller' => 'Thing']));
    }

    /**
     * Test restructuring args with pass key
     *
     * @return void
     */
    public function testPassArgRestructure()
    {
        $route = new Route('/:controller/:action/:slug', [], [
            'pass' => ['slug'],
        ]);
        $result = $route->parse('/Posts/view/my-title', 'GET');
        $expected = [
            'controller' => 'Posts',
            'action' => 'view',
            'slug' => 'my-title',
            'pass' => ['my-title'],
            '_matchedRoute' => '/:controller/:action/:slug',
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
        $result = $route->parse('/Posts/index/1/2/3/foo:bar', 'GET');
        $expected = [
            'controller' => 'Posts',
            'action' => 'index',
            'pass' => ['1/2/3/foo:bar'],
            '_matchedRoute' => '/:controller/:action/**',
        ];
        $this->assertEquals($expected, $result);

        $result = $route->parse('/Posts/index/http://example.com', 'GET');
        $expected = [
            'controller' => 'Posts',
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
        $route = new Route('/category/**', ['controller' => 'Categories', 'action' => 'index']);
        $result = $route->parse('/category/%D9%85%D9%88%D8%A8%D8%A7%DB%8C%D9%84', 'GET');
        $expected = [
            'controller' => 'Categories',
            'action' => 'index',
            'pass' => ['موبایل'],
            '_matchedRoute' => '/category/**',
        ];
        $this->assertEquals($expected, $result);
    }

    /**
     * Test getName();
     *
     * @return void
     */
    public function testGetName()
    {
        $route = new Route('/foo/bar', [], ['_name' => 'testing']);
        $this->assertSame('', $route->getName());

        $route = new Route('/:controller/:action');
        $this->assertSame('_controller:_action', $route->getName());

        $route = new Route('/{controller}/{action}');
        $this->assertSame('_controller:_action', $route->getName());

        $route = new Route('/{controller}/{action}');
        $this->assertSame('_controller:_action', $route->getName());

        $route = new Route('/{controller}/{action}');
        $this->assertSame('_controller:_action', $route->getName());

        $route = new Route('/articles/:action', ['controller' => 'Posts']);
        $this->assertSame('posts:_action', $route->getName());

        $route = new Route('/articles/list', ['controller' => 'Posts', 'action' => 'index']);
        $this->assertSame('posts:index', $route->getName());

        $route = new Route('/:controller/:action', ['action' => 'index']);
        $this->assertSame('_controller:_action', $route->getName());
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
            ['plugin' => 'Asset']
        );
        $this->assertSame('asset._controller:_action', $route->getName());

        $route = new Route(
            '/a/assets/:action',
            ['plugin' => 'Asset', 'controller' => 'Assets']
        );
        $this->assertSame('asset.assets:_action', $route->getName());

        $route = new Route(
            '/assets/get',
            ['plugin' => 'Asset', 'controller' => 'Assets', 'action' => 'get']
        );
        $this->assertSame('asset.assets:get', $route->getName());
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
            ['prefix' => 'Admin']
        );
        $this->assertSame('admin:_controller:_action', $route->getName());

        $route = new Route(
            '/:prefix/assets/:action',
            ['controller' => 'Assets']
        );
        $this->assertSame('_prefix:assets:_action', $route->getName());

        $route = new Route(
            '/admin/assets/get',
            ['prefix' => 'Admin', 'plugin' => 'Asset', 'controller' => 'Assets', 'action' => 'get']
        );
        $this->assertSame('admin:asset.assets:get', $route->getName());

        $route = new Route(
            '/:prefix/:plugin/:controller/:action/*',
            []
        );
        $this->assertSame('_prefix:_plugin._controller:_action', $route->getName());
    }

    /**
     * Test that utf-8 patterns work for :section
     *
     * @return void
     */
    public function testUTF8PatternOnSection()
    {
        $route = new Route(
            '/:section',
            ['plugin' => 'blogs', 'controller' => 'Posts', 'action' => 'index'],
            [
                'persist' => ['section'],
                'section' => 'آموزش|weblog',
            ]
        );

        $result = $route->parse('/%D8%A2%D9%85%D9%88%D8%B2%D8%B4', 'GET');
        $expected = [
            'section' => 'آموزش',
            'plugin' => 'blogs',
            'controller' => 'Posts',
            'action' => 'index',
            'pass' => [],
            '_matchedRoute' => '/:section',
        ];
        $this->assertEquals($expected, $result);

        $result = $route->parse('/weblog', 'GET');
        $expected = [
            'section' => 'weblog',
            'plugin' => 'blogs',
            'controller' => 'Posts',
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
        $this->assertSame('/', $route->staticPath());

        $route = new Route('/pages/*', ['controller' => 'Pages', 'action' => 'display']);
        $this->assertSame('/pages', $route->staticPath());

        $route = new Route('/pages/:id/*', ['controller' => 'Pages', 'action' => 'display']);
        $this->assertSame('/pages/', $route->staticPath());

        $route = new Route('/:controller/:action/*');
        $this->assertSame('/', $route->staticPath());

        $route = new Route('/api/{/:action/*');
        $this->assertSame('/api/{/', $route->staticPath());

        $route = new Route('/books/reviews', ['controller' => 'Reviews', 'action' => 'index']);
        $this->assertSame('/books/reviews', $route->staticPath());
    }

    /**
     * Test getting the static path for a route.
     *
     * @return void
     */
    public function testStaticPathBrace()
    {
        $route = new Route('/pages/{id}/*', ['controller' => 'Pages', 'action' => 'display']);
        $this->assertSame('/pages/', $route->staticPath());

        $route = new Route('/{controller}/{action}/*');
        $this->assertSame('/', $route->staticPath());

        $route = new Route('/books/reviews', ['controller' => 'Reviews', 'action' => 'index']);
        $this->assertSame('/books/reviews', $route->staticPath());
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
                'controller' => 'Pages',
                'action' => 'display',
                'home',
            ],
            'template' => '/',
            '_greedy' => false,
            '_compiledRoute' => null,
        ]);
        $this->assertInstanceOf('Cake\Routing\Route\Route', $route);
        $this->assertSame('/', $route->match(['controller' => 'Pages', 'action' => 'display', 'home']));
        $this->assertNull($route->match(['controller' => 'Pages', 'action' => 'display', 'about']));
        $expected = [
            'controller' => 'Pages',
            'action' => 'display',
            'pass' => ['home'],
            '_matchedRoute' => '/',
        ];
        $this->assertEquals($expected, $route->parse('/', 'GET'));
    }

    /**
     * Test setting the method on a route.
     *
     * @return void
     */
    public function testSetMethods()
    {
        $route = new Route('/books/reviews', ['controller' => 'Reviews', 'action' => 'index']);
        $result = $route->setMethods(['put']);

        $this->assertSame($result, $route, 'Should return this');
        $this->assertSame(['PUT'], $route->defaults['_method'], 'method is wrong');

        $route->setMethods(['post', 'get', 'patch']);
        $this->assertSame(['POST', 'GET', 'PATCH'], $route->defaults['_method']);
    }

    /**
     * Test setting the method on a route to an invalid method
     *
     * @return void
     */
    public function testSetMethodsInvalid()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid HTTP method received. `NOPE` is invalid');
        $route = new Route('/books/reviews', ['controller' => 'Reviews', 'action' => 'index']);
        $route->setMethods(['nope']);
    }

    /**
     * Test setting patterns through the method
     *
     * @return void
     */
    public function testSetPatterns()
    {
        $route = new Route('/reviews/:date/:id', ['controller' => 'Reviews', 'action' => 'view']);
        $result = $route->setPatterns([
            'date' => '\d+\-\d+\-\d+',
            'id' => '[a-z]+',
        ]);
        $this->assertSame($result, $route, 'Should return this');
        $this->assertArrayHasKey('id', $route->options);
        $this->assertArrayHasKey('date', $route->options);
        $this->assertSame('[a-z]+', $route->options['id']);
        $this->assertArrayNotHasKey('multibytePattern', $route->options);

        $this->assertNull($route->parse('/reviews/a-b-c/xyz', 'GET'));
        $this->assertNotEmpty($route->parse('/reviews/2016-05-12/xyz', 'GET'));
    }

    /**
     * Test setting patterns enables multibyte mode
     *
     * @return void
     */
    public function testSetPatternsMultibyte()
    {
        $route = new Route('/reviews/:accountid/:slug', ['controller' => 'Reviews', 'action' => 'view']);
        $result = $route->setPatterns([
            'date' => '[A-zА-я\-\ ]+',
            'accountid' => '[a-z]+',
        ]);
        $this->assertArrayHasKey('multibytePattern', $route->options);

        $this->assertNotEmpty($route->parse('/reviews/abcs/bla-blan-тест', 'GET'));
    }

    /**
     * Test setting host requirements
     *
     * @return void
     */
    public function testSetHost()
    {
        $route = new Route('/reviews', ['controller' => 'Reviews', 'action' => 'index']);
        $result = $route->setHost('blog.example.com');
        $this->assertSame($result, $route, 'Should return this');

        $request = new ServerRequest([
            'environment' => [
                'HTTP_HOST' => 'a.example.com',
                'PATH_INFO' => '/reviews',
            ],
        ]);
        $this->assertNull($route->parseRequest($request));

        $uri = $request->getUri();
        $request = $request->withUri($uri->withHost('blog.example.com'));
        $this->assertNotEmpty($route->parseRequest($request));
    }

    /**
     * Test setting pass parameters
     *
     * @return void
     */
    public function testSetPass()
    {
        $route = new Route('/reviews/:date/:id', ['controller' => 'Reviews', 'action' => 'view']);
        $result = $route->setPass(['date', 'id']);
        $this->assertSame($result, $route, 'Should return this');
        $this->assertEquals(['date', 'id'], $route->options['pass']);
    }

    /**
     * Test setting persisted parameters
     *
     * @return void
     */
    public function testSetPersist()
    {
        $route = new Route('/reviews/:date/:id', ['controller' => 'Reviews', 'action' => 'view']);
        $result = $route->setPersist(['date']);
        $this->assertSame($result, $route, 'Should return this');
        $this->assertEquals(['date'], $route->options['persist']);
    }

    /**
     * Test setting/getting middleware.
     *
     * @return void
     */
    public function testSetMiddleware()
    {
        $route = new Route('/reviews/:date/:id', ['controller' => 'Reviews', 'action' => 'view']);
        $result = $route->setMiddleware(['auth', 'cookie']);
        $this->assertSame($result, $route);
        $this->assertSame(['auth', 'cookie'], $route->getMiddleware());
    }
}
