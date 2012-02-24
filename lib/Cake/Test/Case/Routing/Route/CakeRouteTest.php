<?php
/**
 * CakeRequest Test case file.
 *
 * PHP 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2011, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2011, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       Cake.Test.Case.Routing.Route
 * @since         CakePHP(tm) v 2.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

App::uses('CakeRoute', 'Routing/Route');
App::uses('Router', 'Routing');

/**
 * Test case for CakeRoute
 *
 * @package       Cake.Test.Case.Routing.Route
 **/
class CakeRouteTest extends CakeTestCase {
/**
 * setUp method
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();
		Configure::write('Routing', array('admin' => null, 'prefixes' => array()));
	}

/**
 * Test the construction of a CakeRoute
 *
 * @return void
 **/
	public function testConstruction() {
		$route = new CakeRoute('/:controller/:action/:id', array(), array('id' => '[0-9]+'));

		$this->assertEquals($route->template, '/:controller/:action/:id');
		$this->assertEquals($route->defaults, array());
		$this->assertEquals($route->options, array('id' => '[0-9]+'));
		$this->assertFalse($route->compiled());
	}

/**
 * test Route compiling.
 *
 * @return void
 **/
	public function testBasicRouteCompiling() {
		$route = new CakeRoute('/', array('controller' => 'pages', 'action' => 'display', 'home'));
		$result = $route->compile();
		$expected = '#^/*$#';
		$this->assertEquals($expected, $result);
		$this->assertEquals($route->keys, array());

		$route = new CakeRoute('/:controller/:action', array('controller' => 'posts'));
		$result = $route->compile();

		$this->assertRegExp($result, '/posts/edit');
		$this->assertRegExp($result, '/posts/super_delete');
		$this->assertNotRegExp($result, '/posts');
		$this->assertNotRegExp($result, '/posts/super_delete/1');

		$route = new CakeRoute('/posts/foo:id', array('controller' => 'posts', 'action' => 'view'));
		$result = $route->compile();

		$this->assertRegExp($result, '/posts/foo:1');
		$this->assertRegExp($result, '/posts/foo:param');
		$this->assertNotRegExp($result, '/posts');
		$this->assertNotRegExp($result, '/posts/');

		$this->assertEquals($route->keys, array('id'));

		$route = new CakeRoute('/:plugin/:controller/:action/*', array('plugin' => 'test_plugin', 'action' => 'index'));
		$result = $route->compile();
		$this->assertRegExp($result, '/test_plugin/posts/index');
		$this->assertRegExp($result, '/test_plugin/posts/edit/5');
		$this->assertRegExp($result, '/test_plugin/posts/edit/5/name:value/nick:name');
	}

/**
 * test that route parameters that overlap don't cause errors.
 *
 * @return void
 */
	public function testRouteParameterOverlap() {
		$route = new CakeRoute('/invoices/add/:idd/:id', array('controller' => 'invoices', 'action' => 'add'));
		$result = $route->compile();
		$this->assertRegExp($result, '/invoices/add/1/3');

		$route = new CakeRoute('/invoices/add/:id/:idd', array('controller' => 'invoices', 'action' => 'add'));
		$result = $route->compile();
		$this->assertRegExp($result, '/invoices/add/1/3');
	}

/**
 * test compiling routes with keys that have patterns
 *
 * @return void
 **/
	public function testRouteCompilingWithParamPatterns() {
		$route = new CakeRoute(
			'/:controller/:action/:id',
			array(),
			array('id' => Router::ID)
		);
		$result = $route->compile();
		$this->assertRegExp($result, '/posts/edit/1');
		$this->assertRegExp($result, '/posts/view/518098');
		$this->assertNotRegExp($result, '/posts/edit/name-of-post');
		$this->assertNotRegExp($result, '/posts/edit/4/other:param');
		$this->assertEquals($route->keys, array('controller', 'action', 'id'));

		$route = new CakeRoute(
			'/:lang/:controller/:action/:id',
			array('controller' => 'testing4'),
			array('id' => Router::ID, 'lang' => '[a-z]{3}')
		);
		$result = $route->compile();
		$this->assertRegExp($result, '/eng/posts/edit/1');
		$this->assertRegExp($result, '/cze/articles/view/1');
		$this->assertNotRegExp($result, '/language/articles/view/2');
		$this->assertNotRegExp($result, '/eng/articles/view/name-of-article');
		$this->assertEquals($route->keys, array('lang', 'controller', 'action', 'id'));

		foreach (array(':', '@', ';', '$', '-') as $delim) {
			$route = new CakeRoute('/posts/:id' . $delim . ':title');
			$result = $route->compile();

			$this->assertRegExp($result, '/posts/1' . $delim . 'name-of-article');
			$this->assertRegExp($result, '/posts/13244' . $delim . 'name-of_Article[]');
			$this->assertNotRegExp($result, '/posts/11!nameofarticle');
			$this->assertNotRegExp($result, '/posts/11');

			$this->assertEquals($route->keys, array('id', 'title'));
		}

		$route = new CakeRoute(
			'/posts/:id::title/:year',
			array('controller' => 'posts', 'action' => 'view'),
			array('id' => Router::ID, 'year' => Router::YEAR, 'title' => '[a-z-_]+')
		);
		$result = $route->compile();
		$this->assertRegExp($result, '/posts/1:name-of-article/2009/');
		$this->assertRegExp($result, '/posts/13244:name-of-article/1999');
		$this->assertNotRegExp($result, '/posts/hey_now:nameofarticle');
		$this->assertNotRegExp($result, '/posts/:nameofarticle/2009');
		$this->assertNotRegExp($result, '/posts/:nameofarticle/01');
		$this->assertEquals($route->keys, array('id', 'title', 'year'));

		$route = new CakeRoute(
			'/posts/:url_title-(uuid::id)',
			array('controller' => 'posts', 'action' => 'view'),
			array('pass' => array('id', 'url_title'), 'id' => Router::ID)
		);
		$result = $route->compile();
		$this->assertRegExp($result, '/posts/some_title_for_article-(uuid:12534)/');
		$this->assertRegExp($result, '/posts/some_title_for_article-(uuid:12534)');
		$this->assertNotRegExp($result, '/posts/');
		$this->assertNotRegExp($result, '/posts/nameofarticle');
		$this->assertNotRegExp($result, '/posts/nameofarticle-12347');
		$this->assertEquals($route->keys, array('url_title', 'id'));
	}

/**
 * test more complex route compiling & parsing with mid route greedy stars
 * and optional routing parameters
 *
 * @return void
 */
	public function testComplexRouteCompilingAndParsing() {
		$route = new CakeRoute(
			'/posts/:month/:day/:year/*',
			array('controller' => 'posts', 'action' => 'view'),
			array('year' => Router::YEAR, 'month' => Router::MONTH, 'day' => Router::DAY)
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


		$route = new CakeRoute(
			"/:extra/page/:slug/*",
			array('controller' => 'pages', 'action' => 'view', 'extra' => null),
			array("extra" => '[a-z1-9_]*', "slug" => '[a-z1-9_]+', "action" => 'view')
		);
		$result = $route->compile();

		$this->assertRegExp($result, '/some_extra/page/this_is_the_slug');
		$this->assertRegExp($result, '/page/this_is_the_slug');
		$this->assertEquals($route->keys, array('extra', 'slug'));
		$this->assertEquals($route->options, array('extra' => '[a-z1-9_]*', 'slug' => '[a-z1-9_]+', 'action' => 'view'));
		$expected = array(
			'controller' => 'pages',
			'action' => 'view'
		);
		$this->assertEquals($route->defaults, $expected);

		$route = new CakeRoute(
			'/:controller/:action/*',
			array('project' => false),
			array(
				'controller' => 'source|wiki|commits|tickets|comments|view',
				'action' => 'branches|history|branch|logs|view|start|add|edit|modify'
			)
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
 **/
	public function testMatchBasic() {
		$route = new CakeRoute('/:controller/:action/:id', array('plugin' => null));
		$result = $route->match(array('controller' => 'posts', 'action' => 'view', 'plugin' => null));
		$this->assertFalse($result);

		$result = $route->match(array('plugin' => null, 'controller' => 'posts', 'action' => 'view', 0));
		$this->assertFalse($result);

		$result = $route->match(array('plugin' => null, 'controller' => 'posts', 'action' => 'view', 'id' => 1));
		$this->assertEquals($result, '/posts/view/1');

		$route = new CakeRoute('/', array('controller' => 'pages', 'action' => 'display', 'home'));
		$result = $route->match(array('controller' => 'pages', 'action' => 'display', 'home'));
		$this->assertEquals($result, '/');

		$result = $route->match(array('controller' => 'pages', 'action' => 'display', 'about'));
		$this->assertFalse($result);


		$route = new CakeRoute('/pages/*', array('controller' => 'pages', 'action' => 'display'));
		$result = $route->match(array('controller' => 'pages', 'action' => 'display', 'home'));
		$this->assertEquals($result, '/pages/home');

		$result = $route->match(array('controller' => 'pages', 'action' => 'display', 'about'));
		$this->assertEquals($result, '/pages/about');


		$route = new CakeRoute('/blog/:action', array('controller' => 'posts'));
		$result = $route->match(array('controller' => 'posts', 'action' => 'view'));
		$this->assertEquals($result, '/blog/view');

		$result = $route->match(array('controller' => 'nodes', 'action' => 'view'));
		$this->assertFalse($result);

		$result = $route->match(array('controller' => 'posts', 'action' => 'view', 1));
		$this->assertFalse($result);

		$result = $route->match(array('controller' => 'posts', 'action' => 'view', 'id' => 2));
		$this->assertFalse($result);


		$route = new CakeRoute('/foo/:controller/:action', array('action' => 'index'));
		$result = $route->match(array('controller' => 'posts', 'action' => 'view'));
		$this->assertEquals($result, '/foo/posts/view');


		$route = new CakeRoute('/:plugin/:id/*', array('controller' => 'posts', 'action' => 'view'));
		$result = $route->match(array('plugin' => 'test', 'controller' => 'posts', 'action' => 'view', 'id' => '1'));
		$this->assertEquals($result, '/test/1/');

		$result = $route->match(array('plugin' => 'fo', 'controller' => 'posts', 'action' => 'view', 'id' => '1', '0'));
		$this->assertEquals($result, '/fo/1/0');

		$result = $route->match(array('plugin' => 'fo', 'controller' => 'nodes', 'action' => 'view', 'id' => 1));
		$this->assertFalse($result);

		$result = $route->match(array('plugin' => 'fo', 'controller' => 'posts', 'action' => 'edit', 'id' => 1));
		$this->assertFalse($result);


		$route = new CakeRoute('/admin/subscriptions/:action/*', array(
			'controller' => 'subscribe', 'admin' => true, 'prefix' => 'admin'
		));

		$url = array('controller' => 'subscribe', 'admin' => true, 'action' => 'edit', 1);
		$result = $route->match($url);
		$expected = '/admin/subscriptions/edit/1';
		$this->assertEquals($expected, $result);
	}

/**
 * test that non-greedy routes fail with extra passed args
 *
 * @return void
 */
	public function testGreedyRouteFailurePassedArg() {
		$route = new CakeRoute('/:controller/:action', array('plugin' => null));
		$result = $route->match(array('controller' => 'posts', 'action' => 'view', '0'));
		$this->assertFalse($result);

		$route = new CakeRoute('/:controller/:action', array('plugin' => null));
		$result = $route->match(array('controller' => 'posts', 'action' => 'view', 'test'));
		$this->assertFalse($result);
	}

/**
 * test that non-greedy routes fail with extra passed args
 *
 * @return void
 */
	public function testGreedyRouteFailureNamedParam() {
		$route = new CakeRoute('/:controller/:action', array('plugin' => null));
		$result = $route->match(array('controller' => 'posts', 'action' => 'view', 'page' => 1));
		$this->assertFalse($result);
	}

/**
 * test that falsey values do not interrupt a match.
 *
 * @return void
 */
	public function testMatchWithFalseyValues() {
		$route = new CakeRoute('/:controller/:action/*', array('plugin' => null));
		$result = $route->match(array(
			'controller' => 'posts', 'action' => 'index', 'plugin' => null, 'admin' => false
		));
		$this->assertEquals($result, '/posts/index/');
	}

/**
 * test match() with greedy routes, named parameters and passed args.
 *
 * @return void
 */
	public function testMatchWithNamedParametersAndPassedArgs() {
		Router::connectNamed(true);

		$route = new CakeRoute('/:controller/:action/*', array('plugin' => null));
		$result = $route->match(array('controller' => 'posts', 'action' => 'index', 'plugin' => null, 'page' => 1));
		$this->assertEquals($result, '/posts/index/page:1');

		$result = $route->match(array('controller' => 'posts', 'action' => 'view', 'plugin' => null, 5));
		$this->assertEquals($result, '/posts/view/5');

		$result = $route->match(array('controller' => 'posts', 'action' => 'view', 'plugin' => null, 0));
		$this->assertEquals($result, '/posts/view/0');

		$result = $route->match(array('controller' => 'posts', 'action' => 'view', 'plugin' => null, '0'));
		$this->assertEquals($result, '/posts/view/0');

		$result = $route->match(array('controller' => 'posts', 'action' => 'view', 'plugin' => null, 5, 'page' => 1, 'limit' => 20, 'order' => 'title'));
		$this->assertEquals($result, '/posts/view/5/page:1/limit:20/order:title');

		$result = $route->match(array('controller' => 'posts', 'action' => 'view', 'plugin' => null, 'word space', 'order' => 'Θ'));
		$this->assertEquals($result, '/posts/view/word%20space/order:%CE%98');

		$route = new CakeRoute('/test2/*', array('controller' => 'pages', 'action' => 'display', 2));
		$result = $route->match(array('controller' => 'pages', 'action' => 'display', 1));
		$this->assertFalse($result);

		$result = $route->match(array('controller' => 'pages', 'action' => 'display', 2, 'something'));
		$this->assertEquals($result, '/test2/something');

		$result = $route->match(array('controller' => 'pages', 'action' => 'display', 5, 'something'));
		$this->assertFalse($result);
	}

/**
 * Ensure that named parameters are urldecoded
 *
 * @return void
 */
	public function testParseNamedParametersUrlDecode() {
		Router::connectNamed(true);
		$route = new CakeRoute('/:controller/:action/*', array('plugin' => null));

		$result = $route->parse('/posts/index/page:%CE%98');
		$this->assertEquals('Θ', $result['named']['page']);

		$result = $route->parse('/posts/index/page[]:%CE%98');
		$this->assertEquals('Θ', $result['named']['page'][0]);

		$result = $route->parse('/posts/index/something%20else/page[]:%CE%98');
		$this->assertEquals('Θ', $result['named']['page'][0]);
		$this->assertEquals('something else', $result['pass'][0]);
	}

/**
 * Ensure that keys at named parameters are urldecoded
 *
 * @return void
 */
	public function testParseNamedKeyUrlDecode() {
		Router::connectNamed(true);
		$route = new CakeRoute('/:controller/:action/*', array('plugin' => null));

		// checking /post/index/user[0]:a/user[1]:b
		$result = $route->parse('/posts/index/user%5B0%5D:a/user%5B1%5D:b');
		$this->assertArrayHasKey('user', $result['named']);
		$this->assertEquals(array('a', 'b'), $result['named']['user']);

		// checking /post/index/user[]:a/user[]:b
		$result = $route->parse('/posts/index/user%5B%5D:a/user%5B%5D:b');
		$this->assertArrayHasKey('user', $result['named']);
		$this->assertEquals(array('a', 'b'), $result['named']['user']);
	}

/**
 * test that named params with null/false are excluded
 *
 * @return void
 */
	public function testNamedParamsWithNullFalse() {
		$route = new CakeRoute('/:controller/:action/*');
		$result = $route->match(array('controller' => 'posts', 'action' => 'index', 'page' => null, 'sort' => false));
		$this->assertEquals('/posts/index/', $result);
	}

/**
 * test that match with patterns works.
 *
 * @return void
 */
	public function testMatchWithPatterns() {
		$route = new CakeRoute('/:controller/:action/:id', array('plugin' => null), array('id' => '[0-9]+'));
		$result = $route->match(array('controller' => 'posts', 'action' => 'view', 'id' => 'foo'));
		$this->assertFalse($result);

		$result = $route->match(array('plugin' => null, 'controller' => 'posts', 'action' => 'view', 'id' => '9'));
		$this->assertEquals($result, '/posts/view/9');

		$result = $route->match(array('plugin' => null, 'controller' => 'posts', 'action' => 'view', 'id' => '922'));
		$this->assertEquals($result, '/posts/view/922');

		$result = $route->match(array('plugin' => null, 'controller' => 'posts', 'action' => 'view', 'id' => 'a99'));
		$this->assertFalse($result);
	}

/**
 * test persistParams ability to persist parameters from $params and remove params.
 *
 * @return void
 */
	public function testPersistParams() {
		$route = new CakeRoute(
			'/:lang/:color/blog/:action',
			array('controller' => 'posts'),
			array('persist' => array('lang', 'color'))
		);
		$url = array('controller' => 'posts', 'action' => 'index');
		$params = array('lang' => 'en', 'color' => 'blue');
		$result = $route->persistParams($url, $params);
		$this->assertEquals($result['lang'], 'en');
		$this->assertEquals($result['color'], 'blue');

		$url = array('controller' => 'posts', 'action' => 'index', 'color' => 'red');
		$params = array('lang' => 'en', 'color' => 'blue');
		$result = $route->persistParams($url, $params);
		$this->assertEquals($result['lang'], 'en');
		$this->assertEquals($result['color'], 'red');
	}

/**
 * test the parse method of CakeRoute.
 *
 * @return void
 */
	public function testParse() {
		$route = new CakeRoute(
			'/:controller/:action/:id',
			array('controller' => 'testing4', 'id' => null),
			array('id' => Router::ID)
		);
		$route->compile();
		$result = $route->parse('/posts/view/1');
		$this->assertEquals($result['controller'], 'posts');
		$this->assertEquals($result['action'], 'view');
		$this->assertEquals($result['id'], '1');

		$route = new Cakeroute(
			'/admin/:controller',
			array('prefix' => 'admin', 'admin' => 1, 'action' => 'index')
		);
		$route->compile();
		$result = $route->parse('/admin/');
		$this->assertFalse($result);

		$result = $route->parse('/admin/posts');
		$this->assertEquals($result['controller'], 'posts');
		$this->assertEquals($result['action'], 'index');
	}

/**
 * Test that :key elements are urldecoded
 *
 * @return void
 */
	public function testParseUrlDecodeElements() {
		$route = new Cakeroute(
			'/:controller/:slug',
			array('action' => 'view')
		);
		$route->compile();
		$result = $route->parse('/posts/%E2%88%82%E2%88%82');
		$this->assertEquals($result['controller'], 'posts');
		$this->assertEquals($result['action'], 'view');
		$this->assertEquals($result['slug'], '∂∂');

		$result = $route->parse('/posts/∂∂');
		$this->assertEquals($result['controller'], 'posts');
		$this->assertEquals($result['action'], 'view');
		$this->assertEquals($result['slug'], '∂∂');
	}

/**
 * test numerically indexed defaults, get appeneded to pass
 *
 * @return void
 */
	public function testParseWithPassDefaults() {
		$route = new Cakeroute('/:controller', array('action' => 'display', 'home'));
		$result = $route->parse('/posts');
		$expected = array(
			'controller' => 'posts',
			'action' => 'display',
			'pass' => array('home'),
			'named' => array()
		);
		$this->assertEquals($expected, $result);
	}

/**
 * test that http header conditions can cause route failures.
 *
 * @return void
 */
	public function testParseWithHttpHeaderConditions() {
		$_SERVER['REQUEST_METHOD'] = 'GET';
		$route = new CakeRoute('/sample', array('controller' => 'posts', 'action' => 'index', '[method]' => 'POST'));

		$this->assertFalse($route->parse('/sample'));
	}

/**
 * test that patterns work for :action
 *
 * @return void
 */
	public function testPatternOnAction() {
		$route = new CakeRoute(
			'/blog/:action/*',
			array('controller' => 'blog_posts'),
			array('action' => 'other|actions')
		);
		$result = $route->match(array('controller' => 'blog_posts', 'action' => 'foo'));
		$this->assertFalse($result);

		$result = $route->match(array('controller' => 'blog_posts', 'action' => 'actions'));
		$this->assertNotEmpty($result);

		$result = $route->parse('/blog/other');
		$expected = array('controller' => 'blog_posts', 'action' => 'other', 'pass' => array(), 'named' => array());
		$this->assertEquals($expected, $result);

		$result = $route->parse('/blog/foobar');
		$this->assertFalse($result);
	}

/**
 * test the parseArgs method
 *
 * @return void
 */
	public function testParsePassedArgument() {
		$route = new CakeRoute('/:controller/:action/*');
		$result = $route->parse('/posts/edit/1/2/0');
		$expected = array(
			'controller' => 'posts',
			'action' => 'edit',
			'pass' => array('1', '2', '0'),
			'named' => array()
		);
		$this->assertEquals($expected, $result);

		$result = $route->parse('/posts/edit/a-string/page:1/sort:value');
		$expected = array(
			'controller' => 'posts',
			'action' => 'edit',
			'pass' => array('a-string'),
			'named' => array(
				'page' => 1,
				'sort' => 'value'
			)
		);
		$this->assertEquals($expected, $result);
	}

/**
 * test that only named parameter rules are followed.
 *
 * @return void
 */
	public function testParseNamedParametersWithRules() {
		$route = new CakeRoute('/:controller/:action/*', array(), array(
			'named' => array(
				'wibble',
				'fish' => array('action' => 'index'),
				'fizz' => array('controller' => array('comments', 'other')),
				'pattern' => 'val-[\d]+'
			)
		));
		$result = $route->parse('/posts/display/wibble:spin/fish:trout/fizz:buzz/unknown:value');
		$expected = array(
			'controller' => 'posts',
			'action' => 'display',
			'pass' => array('fish:trout', 'fizz:buzz', 'unknown:value'),
			'named' => array(
				'wibble' => 'spin'
			)
		);
		$this->assertEquals($expected, $result, 'Fish should not be parsed, as action != index');

		$result = $route->parse('/posts/index/wibble:spin/fish:trout/fizz:buzz');
		$expected = array(
			'controller' => 'posts',
			'action' => 'index',
			'pass' => array('fizz:buzz'),
			'named' => array(
				'wibble' => 'spin',
				'fish' => 'trout'
			)
		);
		$this->assertEquals($expected, $result, 'Fizz should be parsed, as controller == comments|other');

		$result = $route->parse('/comments/index/wibble:spin/fish:trout/fizz:buzz');
		$expected = array(
			'controller' => 'comments',
			'action' => 'index',
			'pass' => array(),
			'named' => array(
				'wibble' => 'spin',
				'fish' => 'trout',
				'fizz' => 'buzz'
			)
		);
		$this->assertEquals($expected, $result, 'All params should be parsed as conditions were met.');

		$result = $route->parse('/comments/index/pattern:val--');
		$expected = array(
			'controller' => 'comments',
			'action' => 'index',
			'pass' => array('pattern:val--'),
			'named' => array()
		);
		$this->assertEquals($expected, $result, 'Named parameter pattern unmet.');

		$result = $route->parse('/comments/index/pattern:val-2');
		$expected = array(
			'controller' => 'comments',
			'action' => 'index',
			'pass' => array(),
			'named' => array('pattern' => 'val-2')
		);
		$this->assertEquals($expected, $result, 'Named parameter pattern met.');
	}

/**
 * test that greedyNamed ignores rules.
 *
 * @return void
 */
	public function testParseGreedyNamed() {
		$route = new CakeRoute('/:controller/:action/*', array(), array(
			'named' => array(
				'fizz' => array('controller' => 'comments'),
				'pattern' => 'val-[\d]+',
			),
			'greedyNamed' => true
		));
		$result = $route->parse('/posts/display/wibble:spin/fizz:buzz/pattern:ignored');
		$expected = array(
			'controller' => 'posts',
			'action' => 'display',
			'pass' => array('fizz:buzz', 'pattern:ignored'),
			'named' => array(
				'wibble' => 'spin',
			)
		);
		$this->assertEquals($expected, $result, 'Greedy named grabs everything, rules are followed');
	}

/**
 * Having greedNamed enabled should not capture routing.prefixes.
 *
 * @return void
 */
	public function testMatchGreedyNamedExcludesPrefixes() {
		Configure::write('Routing.prefixes', array('admin'));
		Router::reload();

		$route = new CakeRoute('/sales/*', array('controller' => 'sales', 'action' => 'index'));
		$this->assertFalse($route->match(array('controller' => 'sales', 'action' => 'index', 'admin' => 1)), 'Greedy named consume routing prefixes.');
	}

/**
 * test that parsing array format named parameters works
 *
 * @return void
 */
	public function testParseArrayNamedParameters() {
		$route = new CakeRoute('/:controller/:action/*');
		$result = $route->parse('/tests/action/var[]:val1/var[]:val2');
		$expected = array(
			'controller' => 'tests',
			'action' => 'action',
			'named' => array(
				'var' => array(
					'val1',
					'val2'
				)
			),
			'pass' => array(),
		);
		$this->assertEquals($expected, $result);

		$result = $route->parse('/tests/action/theanswer[is]:42/var[]:val2/var[]:val3');
		$expected = array(
			'controller' => 'tests',
			'action' => 'action',
			'named' => array(
				'theanswer' => array(
					'is' => 42
				),
				'var' => array(
					'val2',
					'val3'
				)
			),
			'pass' => array(),
		);
		$this->assertEquals($expected, $result);

		$result = $route->parse('/tests/action/theanswer[is][not]:42/theanswer[]:5/theanswer[is]:6');
		$expected = array(
			'controller' => 'tests',
			'action' => 'action',
			'named' => array(
				'theanswer' => array(
					5,
					'is' => array(
						6,
						'not' => 42
					)
				),
			),
			'pass' => array(),
		);
		$this->assertEquals($expected, $result);
	}

/**
 * Test that match can handle array named parameters
 *
 * @return void
 */
	public function testMatchNamedParametersArray() {
		$route = new CakeRoute('/:controller/:action/*');

		$url = array(
			'controller' => 'posts',
			'action' => 'index',
			'filter' => array(
				'one',
				'model' => 'value'
			)
		);
		$result = $route->match($url);
		$expected = '/posts/index/filter[0]:one/filter[model]:value';
		$this->assertEquals($expected, $result);

		$url = array(
			'controller' => 'posts',
			'action' => 'index',
			'filter' => array(
				'one',
				'model' => array(
					'two',
					'order' => 'field'
				)
			)
		);
		$result = $route->match($url);
		$expected = '/posts/index/filter[0]:one/filter[model][0]:two/filter[model][order]:field';
		$this->assertEquals($expected, $result);
	}

/**
 * test restructuring args with pass key
 *
 * @return void
 */
	public function testPassArgRestructure() {
		$route = new CakeRoute('/:controller/:action/:slug', array(), array(
			'pass' => array('slug')
		));
		$result = $route->parse('/posts/view/my-title');
		$expected = array(
			'controller' => 'posts',
			'action' => 'view',
			'slug' => 'my-title',
			'pass' => array('my-title'),
			'named' => array()
		);
		$this->assertEquals($expected, $result, 'Slug should have moved');
	}
}
