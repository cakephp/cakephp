<?php

App::import('Core', 'route/CakeRoute');
App::import('Core', 'Router');

/**
 * Test case for CakeRoute
 *
 * @package cake.tests.cases.libs.
 **/
class CakeRouteTestCase extends CakeTestCase {
/**
 * setUp method
 *
 * @access public
 * @return void
 */
	function setUp() {
		parent::setUp();
		Configure::write('Routing', array('admin' => null, 'prefixes' => array()));
	}

/**
 * Test the construction of a CakeRoute
 *
 * @return void
 **/
	function testConstruction() {
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
	function testBasicRouteCompiling() {
		$route = new CakeRoute('/', array('controller' => 'pages', 'action' => 'display', 'home'));
		$result = $route->compile();
		$expected = '#^/*$#';
		$this->assertEqual($result, $expected);
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
	function testRouteParameterOverlap() {
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
	function testRouteCompilingWithParamPatterns() {
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
	function testComplexRouteCompilingAndParsing() {
		$route = new CakeRoute(
			'/posts/:month/:day/:year/*',
			array('controller' => 'posts', 'action' => 'view'),
			array('year' => Router::YEAR, 'month' => Router::MONTH, 'day' => Router::DAY)
		);
		$result = $route->compile();
		$this->assertPattern($result, '/posts/08/01/2007/title-of-post');
		$result = $route->parse('/posts/08/01/2007/title-of-post');

		$this->assertEqual(count($result), 8);
		$this->assertEqual($result['controller'], 'posts');
		$this->assertEqual($result['action'], 'view');
		$this->assertEqual($result['year'], '2007');
		$this->assertEqual($result['month'], '08');
		$this->assertEqual($result['day'], '01');

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
			'action' => 'view',
			'extra' => null,
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
	function testMatchBasic() {
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
		$this->assertEqual($result, $expected);
	}

/**
 * test match() with greedy routes, named parameters and passed args.
 *
 * @return void
 */
	function testMatchWithNamedParametersAndPassedArgs() {
		Router::connectNamed(true);

		$route = new CakeRoute('/:controller/:action/*', array('plugin' => null));
		$result = $route->match(array('controller' => 'posts', 'action' => 'index', 'plugin' => null, 'page' => 1));
		$this->assertEqual($result, '/posts/index/page:1');

		$result = $route->match(array('controller' => 'posts', 'action' => 'view', 'plugin' => null, 5));
		$this->assertEqual($result, '/posts/view/5');

		$result = $route->match(array('controller' => 'posts', 'action' => 'view', 'plugin' => null, 5, 'page' => 1, 'limit' => 20, 'order' => 'title'));
		$this->assertEqual($result, '/posts/view/5/page:1/limit:20/order:title');


		$route = new CakeRoute('/test2/*', array('controller' => 'pages', 'action' => 'display', 2));
		$result = $route->match(array('controller' => 'pages', 'action' => 'display', 1));
		$this->assertFalse($result);

		$result = $route->match(array('controller' => 'pages', 'action' => 'display', 2, 'something'));
		$this->assertEqual($result, '/test2/something');

		$result = $route->match(array('controller' => 'pages', 'action' => 'display', 5, 'something'));
		$this->assertFalse($result);
	}

/**
 * test that match with patterns works.
 *
 * @return void
 */
	function testMatchWithPatterns() {
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
	function testPersistParams() {
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
	function testParse() {
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
 * test that patterns work for :action
 *
 * @return void
 */
	function testPatternOnAction() {
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
}