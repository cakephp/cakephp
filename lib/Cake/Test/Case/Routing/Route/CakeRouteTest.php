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

		$this->assertEqual($route->template, '/:controller/:action/:id');
		$this->assertEqual($route->defaults, array());
		$this->assertEqual($route->options, array('id' => '[0-9]+'));
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
		$this->assertEqual($expected, $result);
		$this->assertEqual($route->keys, array());

		$route = new CakeRoute('/:controller/:action', array('controller' => 'posts'));
		$result = $route->compile();

		$this->assertPattern($result, '/posts/edit');
		$this->assertPattern($result, '/posts/super_delete');
		$this->assertNoPattern($result, '/posts');
		$this->assertNoPattern($result, '/posts/super_delete/1');

		$route = new CakeRoute('/posts/foo:id', array('controller' => 'posts', 'action' => 'view'));
		$result = $route->compile();

		$this->assertPattern($result, '/posts/foo:1');
		$this->assertPattern($result, '/posts/foo:param');
		$this->assertNoPattern($result, '/posts');
		$this->assertNoPattern($result, '/posts/');

		$this->assertEqual($route->keys, array('id'));

		$route = new CakeRoute('/:plugin/:controller/:action/*', array('plugin' => 'test_plugin', 'action' => 'index'));
		$result = $route->compile();
		$this->assertPattern($result, '/test_plugin/posts/index');
		$this->assertPattern($result, '/test_plugin/posts/edit/5');
		$this->assertPattern($result, '/test_plugin/posts/edit/5/name:value/nick:name');
	}

/**
 * test that route parameters that overlap don't cause errors.
 *
 * @return void
 */
	public function testRouteParameterOverlap() {
		$route = new CakeRoute('/invoices/add/:idd/:id', array('controller' => 'invoices', 'action' => 'add'));
		$result = $route->compile();
		$this->assertPattern($result, '/invoices/add/1/3');

		$route = new CakeRoute('/invoices/add/:id/:idd', array('controller' => 'invoices', 'action' => 'add'));
		$result = $route->compile();
		$this->assertPattern($result, '/invoices/add/1/3');
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
		$this->assertPattern($result, '/posts/edit/1');
		$this->assertPattern($result, '/posts/view/518098');
		$this->assertNoPattern($result, '/posts/edit/name-of-post');
		$this->assertNoPattern($result, '/posts/edit/4/other:param');
		$this->assertEqual($route->keys, array('controller', 'action', 'id'));

		$route = new CakeRoute(
			'/:lang/:controller/:action/:id',
			array('controller' => 'testing4'),
			array('id' => Router::ID, 'lang' => '[a-z]{3}')
		);
		$result = $route->compile();
		$this->assertPattern($result, '/eng/posts/edit/1');
		$this->assertPattern($result, '/cze/articles/view/1');
		$this->assertNoPattern($result, '/language/articles/view/2');
		$this->assertNoPattern($result, '/eng/articles/view/name-of-article');
		$this->assertEqual($route->keys, array('lang', 'controller', 'action', 'id'));

		foreach (array(':', '@', ';', '$', '-') as $delim) {
			$route = new CakeRoute('/posts/:id' . $delim . ':title');
			$result = $route->compile();

			$this->assertPattern($result, '/posts/1' . $delim . 'name-of-article');
			$this->assertPattern($result, '/posts/13244' . $delim . 'name-of_Article[]');
			$this->assertNoPattern($result, '/posts/11!nameofarticle');
			$this->assertNoPattern($result, '/posts/11');

			$this->assertEqual($route->keys, array('id', 'title'));
		}

		$route = new CakeRoute(
			'/posts/:id::title/:year',
			array('controller' => 'posts', 'action' => 'view'),
			array('id' => Router::ID, 'year' => Router::YEAR, 'title' => '[a-z-_]+')
		);
		$result = $route->compile();
		$this->assertPattern($result, '/posts/1:name-of-article/2009/');
		$this->assertPattern($result, '/posts/13244:name-of-article/1999');
		$this->assertNoPattern($result, '/posts/hey_now:nameofarticle');
		$this->assertNoPattern($result, '/posts/:nameofarticle/2009');
		$this->assertNoPattern($result, '/posts/:nameofarticle/01');
		$this->assertEqual($route->keys, array('id', 'title', 'year'));

		$route = new CakeRoute(
			'/posts/:url_title-(uuid::id)',
			array('controller' => 'posts', 'action' => 'view'),
			array('pass' => array('id', 'url_title'), 'id' => Router::ID)
		);
		$result = $route->compile();
		$this->assertPattern($result, '/posts/some_title_for_article-(uuid:12534)/');
		$this->assertPattern($result, '/posts/some_title_for_article-(uuid:12534)');
		$this->assertNoPattern($result, '/posts/');
		$this->assertNoPattern($result, '/posts/nameofarticle');
		$this->assertNoPattern($result, '/posts/nameofarticle-12347');
		$this->assertEqual($route->keys, array('url_title', 'id'));
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
		$this->assertPattern($result, '/posts/08/01/2007/title-of-post');
		$result = $route->parse('/posts/08/01/2007/title-of-post');

		$this->assertEqual(count($result), 7);
		$this->assertEqual($result['controller'], 'posts');
		$this->assertEqual($result['action'], 'view');
		$this->assertEqual($result['year'], '2007');
		$this->assertEqual($result['month'], '08');
		$this->assertEqual($result['day'], '01');
		$this->assertEquals($result['pass'][0], 'title-of-post');


		$route = new CakeRoute(
			"/:extra/page/:slug/*",
			array('controller' => 'pages', 'action' => 'view', 'extra' => null),
			array("extra" => '[a-z1-9_]*', "slug" => '[a-z1-9_]+', "action" => 'view')
		);
		$result = $route->compile();

		$this->assertPattern($result, '/some_extra/page/this_is_the_slug');
		$this->assertPattern($result, '/page/this_is_the_slug');
		$this->assertEqual($route->keys, array('extra', 'slug'));
		$this->assertEqual($route->options, array('extra' => '[a-z1-9_]*', 'slug' => '[a-z1-9_]+', 'action' => 'view'));
		$expected = array(
			'controller' => 'pages',
			'action' => 'view'
		);
		$this->assertEqual($route->defaults, $expected);

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
		$this->assertNoPattern($result, '/some_project/source');
		$this->assertPattern($result, '/source/view');
		$this->assertPattern($result, '/source/view/other/params');
		$this->assertNoPattern($result, '/chaw_test/wiki');
		$this->assertNoPattern($result, '/source/wierd_action');
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
		$this->assertEqual($result, '/posts/view/1');

		$route = new CakeRoute('/', array('controller' => 'pages', 'action' => 'display', 'home'));
		$result = $route->match(array('controller' => 'pages', 'action' => 'display', 'home'));
		$this->assertEqual($result, '/');

		$result = $route->match(array('controller' => 'pages', 'action' => 'display', 'about'));
		$this->assertFalse($result);


		$route = new CakeRoute('/pages/*', array('controller' => 'pages', 'action' => 'display'));
		$result = $route->match(array('controller' => 'pages', 'action' => 'display', 'home'));
		$this->assertEqual($result, '/pages/home');

		$result = $route->match(array('controller' => 'pages', 'action' => 'display', 'about'));
		$this->assertEqual($result, '/pages/about');


		$route = new CakeRoute('/blog/:action', array('controller' => 'posts'));
		$result = $route->match(array('controller' => 'posts', 'action' => 'view'));
		$this->assertEqual($result, '/blog/view');

		$result = $route->match(array('controller' => 'nodes', 'action' => 'view'));
		$this->assertFalse($result);

		$result = $route->match(array('controller' => 'posts', 'action' => 'view', 1));
		$this->assertFalse($result);

		$result = $route->match(array('controller' => 'posts', 'action' => 'view', 'id' => 2));
		$this->assertFalse($result);


		$route = new CakeRoute('/foo/:controller/:action', array('action' => 'index'));
		$result = $route->match(array('controller' => 'posts', 'action' => 'view'));
		$this->assertEqual($result, '/foo/posts/view');


		$route = new CakeRoute('/:plugin/:id/*', array('controller' => 'posts', 'action' => 'view'));
		$result = $route->match(array('plugin' => 'test', 'controller' => 'posts', 'action' => 'view', 'id' => '1'));
		$this->assertEqual($result, '/test/1/');

		$result = $route->match(array('plugin' => 'fo', 'controller' => 'posts', 'action' => 'view', 'id' => '1', '0'));
		$this->assertEqual($result, '/fo/1/0');

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
		$this->assertEqual($expected, $result);
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
		$this->assertEqual($result, '/posts/index/');
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
		$this->assertEqual($result, '/posts/index/page:1');

		$result = $route->match(array('controller' => 'posts', 'action' => 'view', 'plugin' => null, 5));
		$this->assertEqual($result, '/posts/view/5');

		$result = $route->match(array('controller' => 'posts', 'action' => 'view', 'plugin' => null, 0));
		$this->assertEqual($result, '/posts/view/0');

		$result = $route->match(array('controller' => 'posts', 'action' => 'view', 'plugin' => null, '0'));
		$this->assertEqual($result, '/posts/view/0');

		$result = $route->match(array('controller' => 'posts', 'action' => 'view', 'plugin' => null, 5, 'page' => 1, 'limit' => 20, 'order' => 'title'));
		$this->assertEqual($result, '/posts/view/5/page:1/limit:20/order:title');

		$result = $route->match(array('controller' => 'posts', 'action' => 'view', 'plugin' => null, 'word space', 'order' => 'Θ'));
		$this->assertEqual($result, '/posts/view/word%20space/order:%CE%98');

		$route = new CakeRoute('/test2/*', array('controller' => 'pages', 'action' => 'display', 2));
		$result = $route->match(array('controller' => 'pages', 'action' => 'display', 1));
		$this->assertFalse($result);

		$result = $route->match(array('controller' => 'pages', 'action' => 'display', 2, 'something'));
		$this->assertEqual($result, '/test2/something');

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
		$this->assertEqual($result, '/posts/view/9');

		$result = $route->match(array('plugin' => null, 'controller' => 'posts', 'action' => 'view', 'id' => '922'));
		$this->assertEqual($result, '/posts/view/922');

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
		$this->assertEqual($result['lang'], 'en');
		$this->assertEqual($result['color'], 'blue');

		$url = array('controller' => 'posts', 'action' => 'index', 'color' => 'red');
		$params = array('lang' => 'en', 'color' => 'blue');
		$result = $route->persistParams($url, $params);
		$this->assertEqual($result['lang'], 'en');
		$this->assertEqual($result['color'], 'red');
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
		$this->assertEqual($result['controller'], 'posts');
		$this->assertEqual($result['action'], 'view');
		$this->assertEqual($result['id'], '1');

		$route = new Cakeroute(
			'/admin/:controller',
			array('prefix' => 'admin', 'admin' => 1, 'action' => 'index')
		);
		$route->compile();
		$result = $route->parse('/admin/');
		$this->assertFalse($result);

		$result = $route->parse('/admin/posts');
		$this->assertEqual($result['controller'], 'posts');
		$this->assertEqual($result['action'], 'index');
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
		$this->assertEqual($expected, $result);

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
		$this->assertEquals($expected, $result, 'Fish should be parsed, as action == index');

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
		$this->assertEqual($expected, $result);

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
		$this->assertEqual($expected, $result);

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
		$this->assertEqual($expected, $result);
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
