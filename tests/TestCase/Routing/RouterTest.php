<?php
/**
 * CakePHP(tm) Tests <http://book.cakephp.org/2.0/en/development/testing.html>
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/2.0/en/development/testing.html CakePHP(tm) Tests
 * @since         1.2.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Routing;

use Cake\Core\App;
use Cake\Core\Configure;
use Cake\Core\Plugin;
use Cake\Network\Request;
use Cake\Routing\RouteCollection;
use Cake\Routing\Router;
use Cake\Routing\Route\Route;
use Cake\TestSuite\TestCase;
use TestPlugin\Routing\Route\TestRoute;

/**
 * RouterTest class
 *
 */
class RouterTest extends TestCase {

/**
 * setUp method
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();
		Configure::write('Routing', array('admin' => null, 'prefixes' => []));
		Router::fullbaseUrl('');
		Configure::write('App.fullBaseUrl', 'http://localhost');
	}

/**
 * tearDown method
 *
 * @return void
 */
	public function tearDown() {
		parent::tearDown();
		Plugin::unload();
		Router::reload();
		Router::defaultRouteClass('Cake\Routing\Route\Route');
	}

/**
 * testFullBaseUrl method
 *
 * @return void
 */
	public function testbaseUrl() {
		$this->assertRegExp('/^http(s)?:\/\//', Router::url('/', true));
		$this->assertRegExp('/^http(s)?:\/\//', Router::url(null, true));
		$this->assertRegExp('/^http(s)?:\/\//', Router::url(array('_full' => true)));
	}

/**
 * Tests that the base URL can be changed at runtime.
 *
 * @return void
 */
	public function testfullBaseURL() {
		Router::fullbaseUrl('http://example.com');
		$this->assertEquals('http://example.com/', Router::url('/', true));
		$this->assertEquals('http://example.com', Configure::read('App.fullBaseUrl'));
		Router::fullBaseUrl('https://example.com');
		$this->assertEquals('https://example.com/', Router::url('/', true));
		$this->assertEquals('https://example.com', Configure::read('App.fullBaseUrl'));
	}

/**
 * Test that Router uses App.base to build URL's when there are no stored
 * request objects.
 *
 * @return void
 */
	public function testBaseUrlWithBasePath() {
		Configure::write('App.base', '/cakephp');
		Router::fullBaseUrl('http://example.com');
		$this->assertEquals('http://example.com/cakephp/tasks', Router::url('/tasks', true));
	}

/**
 * testRouteDefaultParams method
 *
 * @return void
 */
	public function testRouteDefaultParams() {
		Router::connect('/:controller', array('controller' => 'posts'));
		$this->assertEquals(Router::url(array('action' => 'index')), '/');
	}

/**
 * testMapResources method
 *
 * @return void
 */
	public function testMapResources() {
		Router::mapResources('Posts');

		$_SERVER['REQUEST_METHOD'] = 'GET';
		$expected = [
			'pass' => [],
			'plugin' => null,
			'controller' => 'Posts',
			'action' => 'index',
			'_method' => 'GET',
			'_ext' => null
		];
		$result = Router::parse('/posts');
		$this->assertEquals($expected, $result);

		$_SERVER['REQUEST_METHOD'] = 'GET';
		$expected = [
			'pass' => ['13'],
			'plugin' => null,
			'controller' => 'Posts',
			'action' => 'view',
			'id' => '13',
			'_method' => 'GET',
			'_ext' => null
		];
		$result = Router::parse('/posts/13');
		$this->assertEquals($expected, $result);

		$_SERVER['REQUEST_METHOD'] = 'POST';
		$expected = [
			'pass' => [],
			'plugin' => null,
			'controller' => 'Posts',
			'action' => 'add',
			'_method' => 'POST',
			'_ext' => null
		];
		$result = Router::parse('/posts');
		$this->assertEquals($expected, $result);

		$_SERVER['REQUEST_METHOD'] = 'PUT';
		$expected = [
			'pass' => ['13'],
			'plugin' => null,
			'controller' => 'Posts',
			'action' => 'edit',
			'id' => '13',
			'_method' => ['PUT', 'PATCH'],
			'_ext' => null
		];
		$result = Router::parse('/posts/13');
		$this->assertEquals($expected, $result);

		$expected = [
			'pass' => ['475acc39-a328-44d3-95fb-015000000000'],
			'plugin' => null,
			'controller' => 'Posts',
			'action' => 'edit',
			'id' => '475acc39-a328-44d3-95fb-015000000000',
			'_method' => ['PUT', 'PATCH'],
			'_ext' => null
		];
		$result = Router::parse('/posts/475acc39-a328-44d3-95fb-015000000000');
		$this->assertEquals($expected, $result);

		$_SERVER['REQUEST_METHOD'] = 'DELETE';
		$expected = [
			'pass' => ['13'],
			'plugin' => null,
			'controller' => 'Posts',
			'action' => 'delete',
			'id' => '13',
			'_method' => 'DELETE',
			'_ext' => null
		];
		$result = Router::parse('/posts/13');
		$this->assertEquals($expected, $result);

		Router::reload();
		Router::mapResources('Posts', ['id' => '[a-z0-9_]+']);

		$_SERVER['REQUEST_METHOD'] = 'GET';
		$expected = [
			'pass' => ['add'],
			'plugin' => null,
			'controller' => 'Posts',
			'action' => 'view',
			'id' => 'add',
			'_method' => 'GET',
			'_ext' => null
		];
		$result = Router::parse('/posts/add');
		$this->assertEquals($expected, $result);

		$_SERVER['REQUEST_METHOD'] = 'PUT';
		$expected = [
			'pass' => ['name'],
			'plugin' => null,
			'controller' => 'Posts',
			'action' => 'edit',
			'id' => 'name',
			'_method' => ['PUT', 'PATCH'],
			'_ext' => null
		];
		$result = Router::parse('/posts/name');
		$this->assertEquals($expected, $result);
	}

/**
 * testMapResources with plugin controllers.
 *
 * @return void
 */
	public function testPluginMapResources() {
		Router::mapResources('TestPlugin.TestPlugin');

		$_SERVER['REQUEST_METHOD'] = 'GET';
		$result = Router::parse('/test_plugin/test_plugin');
		$expected = array(
			'pass' => [],
			'plugin' => 'TestPlugin',
			'controller' => 'TestPlugin',
			'action' => 'index',
			'_method' => 'GET',
			'_ext' => null
		);
		$this->assertEquals($expected, $result);

		$_SERVER['REQUEST_METHOD'] = 'GET';
		$result = Router::parse('/test_plugin/test_plugin/13');
		$expected = array(
			'pass' => array('13'),
			'plugin' => 'TestPlugin',
			'controller' => 'TestPlugin',
			'action' => 'view',
			'id' => '13',
			'_method' => 'GET',
			'_ext' => null
		);
		$this->assertEquals($expected, $result);
	}

/**
 * Test mapResources with a prefix.
 *
 * @return void
 */
	public function testMapResourcesWithPrefix() {
		Router::mapResources('Posts', array('prefix' => 'api'));

		$_SERVER['REQUEST_METHOD'] = 'GET';
		$result = Router::parse('/api/posts');

		$expected = array(
			'plugin' => null,
			'controller' => 'Posts',
			'action' => 'index',
			'pass' => [],
			'prefix' => 'api',
			'_method' => 'GET',
			'_ext' => null
		);
		$this->assertEquals($expected, $result);
	}

/**
 * Test mapResources with a default extension.
 *
 * @return void
 */
	public function testMapResourcesWithExtension() {
		Router::extensions(['json', 'xml'], false);

		Router::mapResources('Posts', ['_ext' => 'json']);
		$_SERVER['REQUEST_METHOD'] = 'GET';

		$expected = array(
			'plugin' => null,
			'controller' => 'Posts',
			'action' => 'index',
			'pass' => [],
			'_method' => 'GET',
			'_ext' => 'json',
		);

		$result = Router::parse('/posts');
		$this->assertEquals($expected, $result);

		$result = Router::parse('/posts.json');
		$this->assertEquals($expected, $result);

		$expected['_ext'] = 'xml';
		$result = Router::parse('/posts.xml');
		$this->assertEquals($expected, $result);
	}

/**
 * testMapResources with custom connectOptions
 */
	public function testMapResourcesConnectOptions() {
		Plugin::load('TestPlugin');
		Router::mapResources('Posts', array(
			'connectOptions' => array(
				'routeClass' => 'TestPlugin.TestRoute',
				'foo' => '^(bar)$',
			),
		));
		$routes = Router::routes();
		$route = $routes[0];
		$this->assertInstanceOf('TestPlugin\Routing\Route\TestRoute', $route);
		$this->assertEquals('^(bar)$', $route->options['foo']);
	}

/**
 * Test mapResources with a plugin and prefix.
 *
 * @return void
 */
	public function testPluginMapResourcesWithPrefix() {
		Router::mapResources('TestPlugin.TestPlugin', array('prefix' => 'api'));

		$_SERVER['REQUEST_METHOD'] = 'GET';
		$result = Router::parse('/api/test_plugin/test_plugin');
		$expected = array(
			'pass' => [],
			'plugin' => 'TestPlugin',
			'controller' => 'TestPlugin',
			'prefix' => 'api',
			'action' => 'index',
			'_method' => 'GET',
			'_ext' => null
		);
		$this->assertEquals($expected, $result);

		$resources = Router::mapResources('Posts', array('prefix' => 'api'));

		$_SERVER['REQUEST_METHOD'] = 'GET';
		$result = Router::parse('/api/posts');
		$expected = array(
			'pass' => [],
			'plugin' => null,
			'controller' => 'Posts',
			'action' => 'index',
			'_method' => 'GET',
			'prefix' => 'api',
			'_ext' => null
		);
		$this->assertEquals($expected, $result);
	}

/**
 * testMultipleResourceRoute method
 *
 * @return void
 */
	public function testMultipleResourceRoute() {
		Router::connect('/:controller', array('action' => 'index', '_method' => array('GET', 'POST')));

		$_SERVER['REQUEST_METHOD'] = 'GET';
		$result = Router::parse('/posts');
		$expected = array(
			'pass' => [],
			'plugin' => null,
			'controller' => 'posts',
			'action' => 'index',
			'_method' => array('GET', 'POST')
		);
		$this->assertEquals($expected, $result);

		$_SERVER['REQUEST_METHOD'] = 'POST';
		$result = Router::parse('/posts');
		$expected = array(
			'pass' => [],
			'plugin' => null,
			'controller' => 'posts',
			'action' => 'index',
			'_method' => array('GET', 'POST')
		);
		$this->assertEquals($expected, $result);
	}

/**
 * testGenerateUrlResourceRoute method
 *
 * @return void
 */
	public function testGenerateUrlResourceRoute() {
		Router::mapResources('Posts');

		$result = Router::url([
			'controller' => 'Posts',
			'action' => 'index',
			'_method' => 'GET'
		]);
		$expected = '/posts';
		$this->assertEquals($expected, $result);

		$result = Router::url([
			'controller' => 'Posts',
			'action' => 'view',
			'_method' => 'GET',
			'id' => 10
		]);
		$expected = '/posts/10';
		$this->assertEquals($expected, $result);

		$result = Router::url(['controller' => 'Posts', 'action' => 'add', '_method' => 'POST']);
		$expected = '/posts';
		$this->assertEquals($expected, $result);

		$result = Router::url(['controller' => 'Posts', 'action' => 'edit', '_method' => 'PUT', 'id' => 10]);
		$expected = '/posts/10';
		$this->assertEquals($expected, $result);

		$result = Router::url(['controller' => 'Posts', 'action' => 'delete', '_method' => 'DELETE', 'id' => 10]);
		$expected = '/posts/10';
		$this->assertEquals($expected, $result);

		$result = Router::url(['controller' => 'Posts', 'action' => 'edit', '_method' => 'PATCH', 'id' => 10]);
		$expected = '/posts/10';
		$this->assertEquals($expected, $result);
	}

/**
 * testUrlNormalization method
 *
 * @return void
 */
	public function testUrlNormalization() {
		Router::connect('/:controller/:action');

		$expected = '/users/logout';

		$result = Router::normalize('/users/logout/');
		$this->assertEquals($expected, $result);

		$result = Router::normalize('//users//logout//');
		$this->assertEquals($expected, $result);

		$result = Router::normalize('users/logout');
		$this->assertEquals($expected, $result);

		$result = Router::normalize(array('controller' => 'users', 'action' => 'logout'));
		$this->assertEquals($expected, $result);

		$result = Router::normalize('/');
		$this->assertEquals('/', $result);

		$result = Router::normalize('http://google.com/');
		$this->assertEquals('http://google.com/', $result);

		$result = Router::normalize('http://google.com//');
		$this->assertEquals('http://google.com//', $result);

		$result = Router::normalize('/users/login/scope://foo');
		$this->assertEquals('/users/login/scope:/foo', $result);

		$result = Router::normalize('/recipe/recipes/add');
		$this->assertEquals('/recipe/recipes/add', $result);

		$request = new Request();
		$request->base = '/us';
		Router::setRequestInfo($request);
		$result = Router::normalize('/us/users/logout/');
		$this->assertEquals('/users/logout', $result);

		Router::reload();

		$request = new Request();
		$request->base = '/cake_12';
		Router::setRequestInfo($request);
		$result = Router::normalize('/cake_12/users/logout/');
		$this->assertEquals('/users/logout', $result);

		Router::reload();
		$_back = Configure::read('App.fullBaseUrl');
		Configure::write('App.fullBaseUrl', '/');

		$request = new Request();
		$request->base = '/';
		Router::setRequestInfo($request);
		$result = Router::normalize('users/login');
		$this->assertEquals('/users/login', $result);
		Configure::write('App.fullBaseUrl', $_back);

		Router::reload();
		$request = new Request();
		$request->base = 'beer';
		Router::setRequestInfo($request);
		$result = Router::normalize('beer/admin/beers_tags/add');
		$this->assertEquals('/admin/beers_tags/add', $result);

		$result = Router::normalize('/admin/beers_tags/add');
		$this->assertEquals('/admin/beers_tags/add', $result);
	}

/**
 * Test generating urls with base paths.
 *
 * @return void
 */
	public function testUrlGenerationWithBasePath() {
		Router::connect('/:controller/:action/*');
		$request = new Request();
		$request->addParams([
			'action' => 'index',
			'plugin' => null,
			'controller' => 'subscribe',
		]);
		$request->base = '/magazine';
		$request->here = '/magazine/';
		$request->webroot = '/magazine/';
		Router::pushRequest($request);

		$result = Router::url();
		$this->assertEquals('/magazine/', $result);

		$result = Router::url('/');
		$this->assertEquals('/magazine/', $result);

		$result = Router::url('/articles/');
		$this->assertEquals('/magazine/articles/', $result);

		$result = Router::url('/articles/view');
		$this->assertEquals('/magazine/articles/view', $result);

		$result = Router::url(['controller' => 'articles', 'action' => 'view', 1]);
		$this->assertEquals('/magazine/articles/view/1', $result);
	}

/**
 * Test that catch all routes work with a variety of falsey inputs.
 *
 * @return void
 */
	public function testUrlCatchAllRoute() {
		Router::connect('/*', array('controller' => 'categories', 'action' => 'index'));
		$result = Router::url(array('controller' => 'categories', 'action' => 'index', '0'));
		$this->assertEquals('/0', $result);

		$expected = [
			'plugin' => null,
			'controller' => 'categories',
			'action' => 'index',
			'pass' => ['0'],
		];
		$result = Router::parse('/0');
		$this->assertEquals($expected, $result);

		$result = Router::parse('0');
		$this->assertEquals($expected, $result);
	}

/**
 * test generation of basic urls.
 *
 * @return void
 */
	public function testUrlGenerationBasic() {
		extract(Router::getNamedExpressions());

		Router::connect('/', array('controller' => 'pages', 'action' => 'display', 'home'));
		$out = Router::url(array('controller' => 'pages', 'action' => 'display', 'home'));
		$this->assertEquals('/', $out);

		Router::connect('/pages/*', array('controller' => 'pages', 'action' => 'display'));
		$result = Router::url(array('controller' => 'pages', 'action' => 'display', 'about'));
		$expected = '/pages/about';
		$this->assertEquals($expected, $result);

		Router::reload();
		Router::connect('/:plugin/:id/*', array('controller' => 'posts', 'action' => 'view'), array('id' => $ID));

		$result = Router::url(array(
			'plugin' => 'cake_plugin',
			'controller' => 'posts',
			'action' => 'view',
			'id' => '1'
		));
		$expected = '/cake_plugin/1';
		$this->assertEquals($expected, $result);

		$result = Router::url(array(
			'plugin' => 'cake_plugin',
			'controller' => 'posts',
			'action' => 'view',
			'id' => '1',
			'0'
		));
		$expected = '/cake_plugin/1/0';
		$this->assertEquals($expected, $result);

		Router::reload();
		Router::connect('/:controller/:action/:id', [], array('id' => $ID));

		$result = Router::url(array('controller' => 'posts', 'action' => 'view', 'id' => '1'));
		$expected = '/posts/view/1';
		$this->assertEquals($expected, $result);

		Router::reload();
		Router::connect('/:controller/:id', array('action' => 'view'));

		$result = Router::url(array('controller' => 'posts', 'action' => 'view', 'id' => '1'));
		$expected = '/posts/1';
		$this->assertEquals($expected, $result);

		Router::connect('/view/*', array('controller' => 'posts', 'action' => 'view'));
		$result = Router::url(array('controller' => 'posts', 'action' => 'view', '1'));
		$expected = '/view/1';
		$this->assertEquals($expected, $result);

		Router::reload();
		Router::connect('/:controller/:action');
		$request = new Request();
		$request->addParams(array(
			'action' => 'index',
			'plugin' => null,
			'controller' => 'users',
		));
		$request->base = '/';
		$request->here = '/';
		$request->webroot = '/';
		Router::setRequestInfo($request);

		$result = Router::url(array('action' => 'login'));
		$expected = '/users/login';
		$this->assertEquals($expected, $result);

		Router::reload();
		Router::connect('/contact/:action', array('plugin' => 'contact', 'controller' => 'contact'));

		$result = Router::url(array(
			'plugin' => 'contact',
			'controller' => 'contact',
			'action' => 'me'
		));

		$expected = '/contact/me';
		$this->assertEquals($expected, $result);

		Router::reload();
		Router::connect('/:controller', array('action' => 'index'));
		$request = new Request();
		$request->addParams(array(
			'action' => 'index',
			'plugin' => 'myplugin',
			'controller' => 'mycontroller',
		));
		$request->base = '/';
		$request->here = '/';
		$request->webroot = '/';
		Router::setRequestInfo($request);

		$result = Router::url(array('plugin' => null, 'controller' => 'myothercontroller'));
		$expected = '/myothercontroller';
		$this->assertEquals($expected, $result);
	}

/**
 * Test that generated names for routes are case-insensitive.
 *
 * @return void
 */
	public function testRouteNameCasing() {
		Router::connect('/articles/:id', ['controller' => 'Articles', 'action' => 'view']);
		Router::connect('/:controller/:action/*', [], ['routeClass' => 'InflectedRoute']);
		$result = Router::url(['controller' => 'Articles', 'action' => 'view', 'id' => 10]);
		$expected = '/articles/10';
		$this->assertEquals($expected, $result);
	}

/**
 * Test generation of routes with query string parameters.
 *
 * @return void
 */
	public function testUrlGenerationWithQueryStrings() {
		Router::connect('/:controller/:action/*');

		$result = Router::url(array(
			'controller' => 'posts',
			'0',
			'?' => array('var' => 'test', 'var2' => 'test2')
		));
		$expected = '/posts/index/0?var=test&var2=test2';
		$this->assertEquals($expected, $result);

		$result = Router::url(array('controller' => 'posts', '0', '?' => array('var' => null)));
		$this->assertEquals('/posts/index/0', $result);

		$result = Router::url(array(
			'controller' => 'posts',
			'0',
			'?' => array(
				'var' => 'test',
				'var2' => 'test2'
			),
			'#' => 'unencoded string %'
		));
		$expected = '/posts/index/0?var=test&var2=test2#unencoded string %';
		$this->assertEquals($expected, $result);
	}

/**
 * test that regex validation of keyed route params is working.
 *
 * @return void
 */
	public function testUrlGenerationWithRegexQualifiedParams() {
		Router::connect(
			':language/galleries',
			array('controller' => 'galleries', 'action' => 'index'),
			array('language' => '[a-z]{3}')
		);

		Router::connect(
			'/:language/:admin/:controller/:action/*',
			array('admin' => 'admin'),
			array('language' => '[a-z]{3}', 'admin' => 'admin')
		);

		Router::connect('/:language/:controller/:action/*',
			[],
			array('language' => '[a-z]{3}')
		);

		$result = Router::url(array('admin' => false, 'language' => 'dan', 'action' => 'index', 'controller' => 'galleries'));
		$expected = '/dan/galleries';
		$this->assertEquals($expected, $result);

		$result = Router::url(array('admin' => false, 'language' => 'eng', 'action' => 'index', 'controller' => 'galleries'));
		$expected = '/eng/galleries';
		$this->assertEquals($expected, $result);

		Router::reload();
		Router::connect('/:language/pages',
			array('controller' => 'pages', 'action' => 'index'),
			array('language' => '[a-z]{3}')
		);
		Router::connect('/:language/:controller/:action/*', [], array('language' => '[a-z]{3}'));

		$result = Router::url(array('language' => 'eng', 'action' => 'index', 'controller' => 'pages'));
		$expected = '/eng/pages';
		$this->assertEquals($expected, $result);

		$result = Router::url(array('language' => 'eng', 'controller' => 'pages'));
		$this->assertEquals($expected, $result);

		$result = Router::url(array('language' => 'eng', 'controller' => 'pages', 'action' => 'add'));
		$expected = '/eng/pages/add';
		$this->assertEquals($expected, $result);

		Router::reload();
		Router::connect('/forestillinger/:month/:year/*',
			array('plugin' => 'shows', 'controller' => 'shows', 'action' => 'calendar'),
			array('month' => '0[1-9]|1[012]', 'year' => '[12][0-9]{3}')
		);

		$result = Router::url(array(
			'plugin' => 'shows',
			'controller' => 'shows',
			'action' => 'calendar',
			'month' => 10,
			'year' => 2007,
			'min-forestilling'
		));
		$expected = '/forestillinger/10/2007/min-forestilling';
		$this->assertEquals($expected, $result);

		Router::reload();
		Router::connect('/kalender/:month/:year/*',
			array('plugin' => 'shows', 'controller' => 'shows', 'action' => 'calendar'),
			array('month' => '0[1-9]|1[012]', 'year' => '[12][0-9]{3}')
		);
		Router::connect('/kalender/*', array('plugin' => 'shows', 'controller' => 'shows', 'action' => 'calendar'));

		$result = Router::url(array('plugin' => 'shows', 'controller' => 'shows', 'action' => 'calendar', 'min-forestilling'));
		$expected = '/kalender/min-forestilling';
		$this->assertEquals($expected, $result);

		$result = Router::url(array(
			'plugin' => 'shows',
			'controller' => 'shows',
			'action' => 'calendar',
			'year' => 2007,
			'month' => 10,
			'min-forestilling'
		));
		$expected = '/kalender/10/2007/min-forestilling';
		$this->assertEquals($expected, $result);
	}

/**
 * Test URL generation with an admin prefix
 *
 * @return void
 */
	public function testUrlGenerationWithPrefix() {
		Configure::write('Routing.prefixes', array('admin'));
		Router::reload();

		Router::connect('/pages/*', array('controller' => 'pages', 'action' => 'display'));
		Router::connect('/reset/*', array('admin' => true, 'controller' => 'users', 'action' => 'reset'));
		Router::connect('/tests', array('controller' => 'tests', 'action' => 'index'));
		Router::connect('/admin/:controller/:action/*', array('prefix' => 'admin'));
		Router::extensions('rss', false);

		$request = new Request();
		$request->addParams(array(
			'controller' => 'registrations',
			'action' => 'admin_index',
			'plugin' => null,
			'prefix' => 'admin',
			'_ext' => 'html'
		));
		$request->base = '';
		$request->here = '/admin/registrations/index';
		$request->webroot = '/';
		Router::setRequestInfo($request);

		$result = Router::url([]);
		$expected = '/admin/registrations/index';
		$this->assertEquals($expected, $result);

		Router::reload();
		Router::connect('/admin/subscriptions/:action/*', array('controller' => 'subscribe', 'prefix' => 'admin'));
		Router::connect('/admin/:controller/:action/*', array('prefix' => 'admin'));

		$request = new Request();
		$request->addParams(array(
			'action' => 'index',
			'plugin' => null,
			'controller' => 'subscribe',
			'prefix' => 'admin',
		));
		$request->base = '/magazine';
		$request->here = '/magazine/admin/subscriptions/edit/1';
		$request->webroot = '/magazine/';
		Router::setRequestInfo($request);

		$result = Router::url(array('action' => 'edit', 1));
		$expected = '/magazine/admin/subscriptions/edit/1';
		$this->assertEquals($expected, $result);

		$result = Router::url(array('prefix' => 'admin', 'controller' => 'users', 'action' => 'login'));
		$expected = '/magazine/admin/users/login';
		$this->assertEquals($expected, $result);

		Router::reload();
		$request = new Request();
		$request->addParams(array(
			'prefix' => 'admin',
			'action' => 'index',
			'plugin' => null,
			'controller' => 'users',
		));
		$request->base = '/';
		$request->here = '/';
		$request->webroot = '/';
		Router::setRequestInfo($request);

		Router::connect('/page/*', array('controller' => 'pages', 'action' => 'view', 'prefix' => 'admin'));

		$result = Router::url(array('prefix' => 'admin', 'controller' => 'pages', 'action' => 'view', 'my-page'));
		$expected = '/page/my-page';
		$this->assertEquals($expected, $result);

		Router::reload();
		Router::connect('/admin/:controller/:action/*', array('prefix' => 'admin'));

		$request = new Request();
		$request->addParams(array(
			'plugin' => null,
			'controller' => 'pages',
			'action' => 'add',
			'prefix' => 'admin'
		));
		$request->base = '';
		$request->here = '/admin/pages/add';
		$request->webroot = '/';
		Router::setRequestInfo($request);

		$result = Router::url(array('plugin' => null, 'controller' => 'pages', 'action' => 'add', 'id' => false));
		$expected = '/admin/pages/add';
		$this->assertEquals($expected, $result);

		Router::reload();
		Router::connect('/admin/:controller/:action/*', array('prefix' => 'admin'));
		$request = new Request();
		$request->addParams(array(
			'plugin' => null,
			'controller' => 'pages',
			'action' => 'add',
			'prefix' => 'admin'
		));
		$request->base = '';
		$request->here = '/admin/pages/add';
		$request->webroot = '/';
		Router::setRequestInfo($request);

		$result = Router::url(array('plugin' => null, 'controller' => 'pages', 'action' => 'add', 'id' => false));
		$expected = '/admin/pages/add';
		$this->assertEquals($expected, $result);

		Router::reload();
		Router::connect('/admin/:controller/:action/:id', array('prefix' => 'admin'), array('id' => '[0-9]+'));
		$request = new Request();
		Router::setRequestInfo(
			$request->addParams(array(
				'plugin' => null,
				'controller' => 'pages',
				'action' => 'edit',
				'pass' => array('284'),
				'prefix' => 'admin'
			))->addPaths(array(
				'base' => '',
				'here' => '/admin/pages/edit/284',
				'webroot' => '/'
			))
		);

		$result = Router::url(array('plugin' => null, 'controller' => 'pages', 'action' => 'edit', 'id' => '284'));
		$expected = '/admin/pages/edit/284';
		$this->assertEquals($expected, $result);

		Router::reload();
		Router::connect('/admin/:controller/:action/*', array('prefix' => 'admin'));

		$request = new Request();
		Router::setRequestInfo(
			$request->addParams(array(
				'plugin' => null, 'controller' => 'pages', 'action' => 'add', 'prefix' => 'admin',
			))->addPaths(array(
				'base' => '', 'here' => '/admin/pages/add', 'webroot' => '/'
			))
		);

		$result = Router::url(array('plugin' => null, 'controller' => 'pages', 'action' => 'add', 'id' => false));
		$expected = '/admin/pages/add';
		$this->assertEquals($expected, $result);

		Router::reload();
		Router::connect('/admin/:controller/:action/*', array('prefix' => 'admin'));

		$request = new Request();
		Router::setRequestInfo(
			$request->addParams(array(
				'plugin' => null, 'controller' => 'pages', 'action' => 'edit', 'prefix' => 'admin',
				'pass' => array('284')
			))->addPaths(array(
				'base' => '', 'here' => '/admin/pages/edit/284', 'webroot' => '/'
			))
		);

		$result = Router::url(array('plugin' => null, 'controller' => 'pages', 'action' => 'edit', 284));
		$expected = '/admin/pages/edit/284';
		$this->assertEquals($expected, $result);

		Router::reload();
		Router::connect('/admin/posts/*', array('controller' => 'posts', 'action' => 'index', 'prefix' => 'admin'));
		Router::setRequestInfo(
			$request->addParams(array(
				'plugin' => null, 'controller' => 'posts', 'action' => 'index', 'prefix' => 'admin',
				'pass' => array('284')
			))->addPaths(array(
				'base' => '', 'here' => '/admin/posts', 'webroot' => '/'
			))
		);

		$result = Router::url(array('all'));
		$expected = '/admin/posts/all';
		$this->assertEquals($expected, $result);
	}

/**
 * Test URL generation inside a prefixed plugin.
 *
 * @return void
 */
	public function testUrlGenerationPrefixedPlugin() {
		Router::prefix('admin', function ($routes) {
			$routes->plugin('MyPlugin', function ($routes) {
				$routes->fallbacks();
			});
		});
		$result = Router::url(['prefix' => 'admin', 'plugin' => 'MyPlugin', 'controller' => 'Forms', 'action' => 'edit', 2]);
		$expected = '/admin/my_plugin/forms/edit/2';
		$this->assertEquals($expected, $result);
	}

/**
 * testUrlGenerationWithExtensions method
 *
 * @return void
 */
	public function testUrlGenerationWithExtensions() {
		Router::connect('/:controller', array('action' => 'index'));
		Router::connect('/:controller/:action');

		$result = Router::url(array(
			'plugin' => null,
			'controller' => 'articles',
			'action' => 'add',
			'id' => null,
			'_ext' => 'json'
		));
		$expected = '/articles/add.json';
		$this->assertEquals($expected, $result);

		$result = Router::url(array(
			'plugin' => null,
			'controller' => 'articles',
			'action' => 'add',
			'_ext' => 'json'
		));
		$expected = '/articles/add.json';
		$this->assertEquals($expected, $result);

		$result = Router::url(array(
			'plugin' => null,
			'controller' => 'articles',
			'action' => 'index',
			'id' => null,
			'_ext' => 'json'
		));
		$expected = '/articles.json';
		$this->assertEquals($expected, $result);
	}

/**
 * test url() when the current request has an extension.
 *
 * @return void
 */
	public function testUrlGenerationWithExtensionInCurrentRequest() {
		Router::extensions('rss');
		Router::scope('/', function ($r) {
			$r->fallbacks();
		});
		$request = new Request();
		$request->addParams(['controller' => 'Tasks', 'action' => 'index', '_ext' => 'rss']);
		Router::pushRequest($request);

		$result = Router::url(array(
			'controller' => 'Tasks',
			'action' => 'view',
			1
		));
		$expected = '/tasks/view/1';
		$this->assertEquals($expected, $result);

		$result = Router::url(array(
			'controller' => 'Tasks',
			'action' => 'view',
			1,
			'_ext' => 'json'
		));
		$expected = '/tasks/view/1.json';
		$this->assertEquals($expected, $result);
	}

/**
 * Test url generation with named routes.
 */
	public function testUrlGenerationNamedRoute() {
		Router::connect(
			'/users',
			array('controller' => 'users', 'action' => 'index'),
			array('_name' => 'users-index')
		);
		Router::connect(
			'/users/:name',
			array('controller' => 'users', 'action' => 'view'),
			array('_name' => 'test')
		);
		Router::connect(
			'/view/*',
			['action' => 'view'],
			['_name' => 'Articles::view']
		);

		$url = Router::url(['_name' => 'test', 'name' => 'mark']);
		$this->assertEquals('/users/mark', $url);

		$url = Router::url([
			'_name' => 'test', 'name' => 'mark',
			'page' => 1, 'sort' => 'title', 'dir' => 'desc'
		]);
		$this->assertEquals('/users/mark?page=1&sort=title&dir=desc', $url);

		$url = Router::url(['_name' => 'Articles::view']);
		$this->assertEquals('/view/', $url);

		$url = Router::url(['_name' => 'Articles::view', '1']);
		$this->assertEquals('/view/1', $url);

		$url = Router::url(['_name' => 'Articles::view', '_full' => true, '1']);
		$this->assertEquals('http://localhost/view/1', $url);

		$url = Router::url(['_name' => 'Articles::view', '1', '#' => 'frag']);
		$this->assertEquals('/view/1#frag', $url);
	}

/**
 * Test that using invalid names causes exceptions.
 *
 * @expectedException \Cake\Routing\Exception\MissingRouteException
 * @return void
 */
	public function testNamedRouteException() {
		Router::connect(
			'/users/:name',
			array('controller' => 'users', 'action' => 'view'),
			array('_name' => 'test')
		);
		$url = Router::url(['_name' => 'junk', 'name' => 'mark']);
	}

/**
 * Test that url filters are applied to url params.
 *
 * @return void
 */
	public function testUrlGenerationWithUrlFilter() {
		Router::connect('/:lang/:controller/:action/*');
		$request = new Request();
		$request->addParams(array(
			'lang' => 'en',
			'controller' => 'posts',
			'action' => 'index'
		))->addPaths(array(
			'base' => '',
			'here' => '/'
		));
		Router::pushRequest($request);

		$calledCount = 0;
		Router::addUrlFilter(function ($url, $request) use (&$calledCount) {
			$calledCount++;
			$url['lang'] = $request->lang;
			return $url;
		});
		Router::addUrlFilter(function ($url, $request) use (&$calledCount) {
			$calledCount++;
			$url[] = '1234';
			return $url;
		});
		$result = Router::url(array('controller' => 'tasks', 'action' => 'edit'));
		$this->assertEquals('/en/tasks/edit/1234', $result);
		$this->assertEquals(2, $calledCount);
	}

/**
 * Test that plain strings urls work
 *
 * @return void
 */
	public function testUrlGenerationPlainString() {
		$mailto = 'mailto:mark@example.com';
		$result = Router::url($mailto);
		$this->assertEquals($mailto, $result);

		$js = 'javascript:alert("hi")';
		$result = Router::url($js);
		$this->assertEquals($js, $result);

		$hash = '#first';
		$result = Router::url($hash);
		$this->assertEquals($hash, $result);
	}

/**
 * test that you can leave active plugin routes with plugin = null
 *
 * @return void
 */
	public function testCanLeavePlugin() {
		Router::connect('/admin/:controller', array('action' => 'index', 'prefix' => 'admin'));
		Router::connect('/admin/:controller/:action/*', array('prefix' => 'admin'));
		$request = new Request();
		Router::setRequestInfo(
			$request->addParams(array(
				'pass' => [],
				'prefix' => 'admin',
				'plugin' => 'this',
				'action' => 'index',
				'controller' => 'interesting',
			))->addPaths(array(
				'base' => '',
				'here' => '/admin/this/interesting/index',
				'webroot' => '/',
			))
		);
		$result = Router::url(array('plugin' => null, 'controller' => 'posts', 'action' => 'index'));
		$this->assertEquals('/admin/posts', $result);
	}

/**
 * testUrlParsing method
 *
 * @return void
 */
	public function testUrlParsing() {
		extract(Router::getNamedExpressions());

		Router::connect(
			'/posts/:value/:somevalue/:othervalue/*',
			array('controller' => 'Posts', 'action' => 'view'),
			array('value', 'somevalue', 'othervalue')
		);
		$result = Router::parse('/posts/2007/08/01/title-of-post-here');
		$expected = array(
			'value' => '2007',
			'somevalue' => '08',
			'othervalue' => '01',
			'controller' => 'Posts',
			'action' => 'view',
			'plugin' => null,
			'pass' => array('0' => 'title-of-post-here')
		);
		$this->assertEquals($expected, $result);

		Router::reload();
		Router::connect(
			'/posts/:year/:month/:day/*',
			array('controller' => 'posts', 'action' => 'view'),
			array('year' => $Year, 'month' => $Month, 'day' => $Day)
		);
		$result = Router::parse('/posts/2007/08/01/title-of-post-here');
		$expected = array(
			'year' => '2007',
			'month' => '08',
			'day' => '01',
			'controller' => 'posts',
			'action' => 'view',
			'plugin' => null,
			'pass' => array('0' => 'title-of-post-here')
		);
		$this->assertEquals($expected, $result);

		Router::reload();
		Router::connect(
			'/posts/:day/:year/:month/*',
			array('controller' => 'posts', 'action' => 'view'),
			array('year' => $Year, 'month' => $Month, 'day' => $Day)
		);
		$result = Router::parse('/posts/01/2007/08/title-of-post-here');
		$expected = array(
			'day' => '01',
			'year' => '2007',
			'month' => '08',
			'controller' => 'posts',
			'action' => 'view',
			'plugin' => null,
			'pass' => array('0' => 'title-of-post-here')
		);
		$this->assertEquals($expected, $result);

		Router::reload();
		Router::connect(
			'/posts/:month/:day/:year/*',
			array('controller' => 'posts', 'action' => 'view'),
			array('year' => $Year, 'month' => $Month, 'day' => $Day)
		);
		$result = Router::parse('/posts/08/01/2007/title-of-post-here');
		$expected = array(
			'month' => '08',
			'day' => '01',
			'year' => '2007',
			'controller' => 'posts',
			'action' => 'view',
			'plugin' => null,
			'pass' => array('0' => 'title-of-post-here')
		);
		$this->assertEquals($expected, $result);

		Router::reload();
		Router::connect(
			'/posts/:year/:month/:day/*',
			array('controller' => 'posts', 'action' => 'view')
		);
		$result = Router::parse('/posts/2007/08/01/title-of-post-here');
		$expected = array(
			'year' => '2007',
			'month' => '08',
			'day' => '01',
			'controller' => 'posts',
			'action' => 'view',
			'plugin' => null,
			'pass' => array('0' => 'title-of-post-here')
		);
		$this->assertEquals($expected, $result);

		Router::reload();
		$this->_connectDefaultRoutes();
		$result = Router::parse('/pages/display/home');
		$expected = array(
			'plugin' => null,
			'pass' => array('home'),
			'controller' => 'Pages',
			'action' => 'display'
		);
		$this->assertEquals($expected, $result);

		$result = Router::parse('pages/display/home/');
		$this->assertEquals($expected, $result);

		$result = Router::parse('pages/display/home');
		$this->assertEquals($expected, $result);

		Router::reload();
		Router::connect('/page/*', array('controller' => 'test'));
		$result = Router::parse('/page/my-page');
		$expected = array('pass' => array('my-page'), 'plugin' => null, 'controller' => 'test', 'action' => 'index');
		$this->assertEquals($expected, $result);

		Router::reload();
		Router::connect(
			'/:language/contact',
			array('language' => 'eng', 'plugin' => 'contact', 'controller' => 'contact', 'action' => 'index'),
			array('language' => '[a-z]{3}')
		);
		$result = Router::parse('/eng/contact');
		$expected = array(
			'pass' => [],
			'language' => 'eng',
			'plugin' => 'contact',
			'controller' => 'contact',
			'action' => 'index'
		);
		$this->assertEquals($expected, $result);

		Router::reload();
		Router::connect('/forestillinger/:month/:year/*',
			array('plugin' => 'shows', 'controller' => 'shows', 'action' => 'calendar'),
			array('month' => '0[1-9]|1[012]', 'year' => '[12][0-9]{3}')
		);

		$result = Router::parse('/forestillinger/10/2007/min-forestilling');
		$expected = array(
			'pass' => array('min-forestilling'),
			'plugin' => 'shows',
			'controller' => 'shows',
			'action' => 'calendar',
			'year' => 2007,
			'month' => 10
		);
		$this->assertEquals($expected, $result);

		Router::reload();
		Router::connect('/:controller/:action/*');
		Router::connect('/', array('plugin' => 'pages', 'controller' => 'pages', 'action' => 'display'));
		$result = Router::parse('/');
		$expected = array('pass' => [], 'controller' => 'pages', 'action' => 'display', 'plugin' => 'pages');
		$this->assertEquals($expected, $result);

		$result = Router::parse('/posts/edit/0');
		$expected = array('pass' => array(0), 'controller' => 'posts', 'action' => 'edit', 'plugin' => null);
		$this->assertEquals($expected, $result);

		Router::reload();
		Router::connect(
			'/posts/:id::url_title',
			array('controller' => 'posts', 'action' => 'view'),
			array('pass' => array('id', 'url_title'), 'id' => '[\d]+')
		);
		$result = Router::parse('/posts/5:sample-post-title');
		$expected = array(
			'pass' => array('5', 'sample-post-title'),
			'id' => 5,
			'url_title' => 'sample-post-title',
			'plugin' => null,
			'controller' => 'posts',
			'action' => 'view'
		);
		$this->assertEquals($expected, $result);

		Router::reload();
		Router::connect(
			'/posts/:id::url_title/*',
			array('controller' => 'posts', 'action' => 'view'),
			array('pass' => array('id', 'url_title'), 'id' => '[\d]+')
		);
		$result = Router::parse('/posts/5:sample-post-title/other/params/4');
		$expected = array(
			'pass' => array('5', 'sample-post-title', 'other', 'params', '4'),
			'id' => 5,
			'url_title' => 'sample-post-title',
			'plugin' => null,
			'controller' => 'posts',
			'action' => 'view'
		);
		$this->assertEquals($expected, $result);

		Router::reload();
		Router::connect('/posts/view/*', array('controller' => 'posts', 'action' => 'view'));
		$result = Router::parse('/posts/view/10?id=123&tab=abc');
		$expected = array('pass' => array(10), 'plugin' => null, 'controller' => 'posts', 'action' => 'view', '?' => array('id' => '123', 'tab' => 'abc'));
		$this->assertEquals($expected, $result);

		Router::reload();
		Router::connect(
			'/posts/:url_title-(uuid::id)',
			array('controller' => 'posts', 'action' => 'view'),
			array('pass' => array('id', 'url_title'), 'id' => $UUID)
		);
		$result = Router::parse('/posts/sample-post-title-(uuid:47fc97a9-019c-41d1-a058-1fa3cbdd56cb)');
		$expected = array(
			'pass' => array('47fc97a9-019c-41d1-a058-1fa3cbdd56cb', 'sample-post-title'),
			'id' => '47fc97a9-019c-41d1-a058-1fa3cbdd56cb',
			'url_title' => 'sample-post-title',
			'plugin' => null,
			'controller' => 'posts',
			'action' => 'view'
		);
		$this->assertEquals($expected, $result);

		Router::reload();
		Router::connect('/posts/view/*', array('controller' => 'posts', 'action' => 'view'));
		$result = Router::parse('/posts/view/foo:bar/routing:fun');
		$expected = array(
			'pass' => array('foo:bar', 'routing:fun'),
			'plugin' => null,
			'controller' => 'posts',
			'action' => 'view'
		);
		$this->assertEquals($expected, $result);
	}

/**
 * testUuidRoutes method
 *
 * @return void
 */
	public function testUuidRoutes() {
		Router::connect(
			'/subjects/add/:category_id',
			array('controller' => 'subjects', 'action' => 'add'),
			array('category_id' => '\w{8}-\w{4}-\w{4}-\w{4}-\w{12}')
		);
		$result = Router::parse('/subjects/add/4795d601-19c8-49a6-930e-06a8b01d17b7');
		$expected = array(
			'pass' => [],
			'category_id' => '4795d601-19c8-49a6-930e-06a8b01d17b7',
			'plugin' => null,
			'controller' => 'subjects',
			'action' => 'add'
		);
		$this->assertEquals($expected, $result);
	}

/**
 * testRouteSymmetry method
 *
 * @return void
 */
	public function testRouteSymmetry() {
		Router::connect(
			"/:extra/page/:slug/*",
			array('controller' => 'pages', 'action' => 'view', 'extra' => null),
			array("extra" => '[a-z1-9_]*', "slug" => '[a-z1-9_]+', "action" => 'view')
		);

		$result = Router::parse('/some_extra/page/this_is_the_slug');
		$expected = array(
			'pass' => [],
			'plugin' => null,
			'controller' => 'pages',
			'action' => 'view',
			'slug' => 'this_is_the_slug',
			'extra' => 'some_extra'
		);
		$this->assertEquals($expected, $result);

		$result = Router::parse('/page/this_is_the_slug');
		$expected = array(
			'pass' => [],
			'plugin' => null,
			'controller' => 'pages',
			'action' => 'view',
			'slug' => 'this_is_the_slug',
			'extra' => null
		);
		$this->assertEquals($expected, $result);

		Router::reload();
		Router::connect(
			"/:extra/page/:slug/*",
			array('controller' => 'pages', 'action' => 'view', 'extra' => null),
			array("extra" => '[a-z1-9_]*', "slug" => '[a-z1-9_]+')
		);

		$result = Router::url(array(
			'admin' => null,
			'plugin' => null,
			'controller' => 'pages',
			'action' => 'view',
			'slug' => 'this_is_the_slug',
			'extra' => null
		));
		$expected = '/page/this_is_the_slug';
		$this->assertEquals($expected, $result);

		$result = Router::url(array(
			'admin' => null,
			'plugin' => null,
			'controller' => 'pages',
			'action' => 'view',
			'slug' => 'this_is_the_slug',
			'extra' => 'some_extra'
		));
		$expected = '/some_extra/page/this_is_the_slug';
		$this->assertEquals($expected, $result);
	}

/**
 * Test exceptions when parsing fails.
 *
 * @expectedException Cake\Routing\Exception\MissingRouteException
 */
	public function testParseError() {
		Router::connect('/', ['controller' => 'Pages', 'action' => 'display', 'home']);
		Router::parse('/nope');
	}

/**
 * Test parse and reverse symmetry
 *
 * @return void
 * @dataProvider parseReverseSymmetryData
 */
	public function testParseReverseSymmetry($url) {
		$this->_connectDefaultRoutes();
		$this->assertSame($url, Router::reverse(Router::parse($url) + array('url' => [])));
	}

/**
 * Data for parse and reverse test
 *
 * @return array
 */
	public function parseReverseSymmetryData() {
		return array(
			array('/controller/action'),
			array('/controller/action/param'),
			array('/controller/action?param1=value1&param2=value2'),
			array('/controller/action/param?param1=value1'),
		);
	}

/**
 * testSetExtensions method
 *
 * @return void
 */
	public function testSetExtensions() {
		Router::extensions('rss', false);
		$this->assertContains('rss', Router::extensions());

		$this->_connectDefaultRoutes();

		$result = Router::parse('/posts.rss');
		$this->assertEquals('rss', $result['_ext']);

		$result = Router::parse('/posts.xml');
		$this->assertFalse(isset($result['_ext']));

		Router::extensions(array('xml'));
	}

/**
 * Test that route builders propagate extensions to the top.
 *
 * @return void
 */
	public function testExtensionsWithScopedRoutes() {
		Router::scope('/', function ($routes) {
			$routes->extensions('rss');
			$routes->connect('/', ['controller' => 'Pages', 'action' => 'index']);

			$routes->scope('/api', function ($routes) {
				$routes->extensions('xml');
				$routes->connect('/docs', ['controller' => 'ApiDocs', 'action' => 'index']);
			});
		});

		$this->assertEquals(['rss', 'xml', 'json'], Router::extensions());
	}

/**
 * testExtensionParsing method
 *
 * @return void
 */
	public function testExtensionParsing() {
		Router::extensions('rss', false);
		$this->_connectDefaultRoutes();

		$result = Router::parse('/posts.rss');
		$expected = array(
			'plugin' => null,
			'controller' => 'Posts',
			'action' => 'index',
			'_ext' => 'rss',
			'pass' => []
		);
		$this->assertEquals($expected, $result);

		$result = Router::parse('/posts/view/1.rss');
		$expected = array(
			'plugin' => null,
			'controller' => 'Posts',
			'action' => 'view',
			'pass' => array('1'),
			'_ext' => 'rss'
		);
		$this->assertEquals($expected, $result);

		$result = Router::parse('/posts/view/1.rss?query=test');
		$expected['?'] = array('query' => 'test');
		$this->assertEquals($expected, $result);

		Router::reload();
		Router::extensions(['rss', 'xml'], false);
		$this->_connectDefaultRoutes();

		$result = Router::parse('/posts.xml');
		$expected = array(
			'plugin' => null,
			'controller' => 'Posts',
			'action' => 'index',
			'_ext' => 'xml',
			'pass' => []
		);
		$this->assertEquals($expected, $result);

		$result = Router::parse('/posts.atom?hello=goodbye');
		$expected = array(
			'plugin' => null,
			'controller' => 'Posts.atom',
			'action' => 'index',
			'pass' => [],
			'?' => array('hello' => 'goodbye')
		);
		$this->assertEquals($expected, $result);

		Router::reload();
		Router::connect('/controller/action', array('controller' => 'controller', 'action' => 'action', '_ext' => 'rss'));
		$result = Router::parse('/controller/action');
		$expected = array('controller' => 'controller', 'action' => 'action', 'plugin' => null, '_ext' => 'rss', 'pass' => []);
		$this->assertEquals($expected, $result);

		Router::reload();
		Router::connect('/controller/action', array('controller' => 'controller', 'action' => 'action', '_ext' => 'rss'));
		$result = Router::parse('/controller/action');
		$expected = array(
			'controller' => 'controller',
			'action' => 'action',
			'plugin' => null,
			'_ext' => 'rss',
			'pass' => []
		);
		$this->assertEquals($expected, $result);

		Router::reload();
		Router::extensions('rss', false);
		Router::connect('/controller/action', array('controller' => 'controller', 'action' => 'action', '_ext' => 'rss'));
		$result = Router::parse('/controller/action');
		$expected = array(
			'controller' => 'controller',
			'action' => 'action',
			'plugin' => null,
			'_ext' => 'rss',
			'pass' => []
		);
		$this->assertEquals($expected, $result);
	}

/**
 * Test newer style automatically generated prefix routes.
 *
 * @return void
 * @see testUrlGenerationWithAutoPrefixes
 */
	public function testUrlGenerationWithAutoPrefixes() {
		Router::reload();
		Router::connect('/protected/:controller/:action/*', array('prefix' => 'protected'));
		Router::connect('/admin/:controller/:action/*', array('prefix' => 'admin'));
		Router::connect('/:controller/:action/*');

		$request = new Request();
		Router::setRequestInfo(
			$request->addParams(array(
				'plugin' => null, 'controller' => 'images', 'action' => 'index',
				'prefix' => null, 'protected' => false, 'url' => array('url' => 'images/index')
			))->addPaths(array(
				'base' => '',
				'here' => '/images/index',
				'webroot' => '/',
			))
		);

		$result = Router::url(array('controller' => 'images', 'action' => 'add'));
		$expected = '/images/add';
		$this->assertEquals($expected, $result);

		$result = Router::url(array('controller' => 'images', 'action' => 'add', 'prefix' => 'protected'));
		$expected = '/protected/images/add';
		$this->assertEquals($expected, $result);

		$result = Router::url(array('controller' => 'images', 'action' => 'add_protected_test', 'prefix' => 'protected'));
		$expected = '/protected/images/add_protected_test';
		$this->assertEquals($expected, $result);

		$result = Router::url(array('action' => 'edit', 1));
		$expected = '/images/edit/1';
		$this->assertEquals($expected, $result);

		$result = Router::url(array('action' => 'edit', 1, 'prefix' => 'protected'));
		$expected = '/protected/images/edit/1';
		$this->assertEquals($expected, $result);

		$result = Router::url(array('action' => 'protectededit', 1, 'prefix' => 'protected'));
		$expected = '/protected/images/protectededit/1';
		$this->assertEquals($expected, $result);

		$result = Router::url(array('action' => 'edit', 1, 'prefix' => 'protected'));
		$expected = '/protected/images/edit/1';
		$this->assertEquals($expected, $result);

		$result = Router::url(array('controller' => 'others', 'action' => 'edit', 1));
		$expected = '/others/edit/1';
		$this->assertEquals($expected, $result);

		$result = Router::url(array('controller' => 'others', 'action' => 'edit', 1, 'prefix' => 'protected'));
		$expected = '/protected/others/edit/1';
		$this->assertEquals($expected, $result);
	}

/**
 * Test that the ssl option works.
 *
 * @return void
 */
	public function testGenerationWithSslOption() {
		Router::connect('/:controller/:action/*');

		$request = new Request();
		$request->env('HTTP_HOST', 'localhost');
		Router::pushRequest(
			$request->addParams(array(
				'plugin' => null, 'controller' => 'images', 'action' => 'index'
			))->addPaths(array(
				'base' => '',
				'here' => '/images/index',
				'webroot' => '/',
			))
		);

		$result = Router::url(array(
			'_ssl' => true
		));
		$this->assertEquals('https://localhost/images/index', $result);

		$result = Router::url(array(
			'_ssl' => false
		));
		$this->assertEquals('http://localhost/images/index', $result);
	}

/**
 * Test ssl option when the current request is ssl.
 *
 * @return void
 */
	public function testGenerateWithSslInSsl() {
		Router::connect('/:controller/:action/*');

		$request = new Request();
		$request->env('HTTP_HOST', 'localhost');
		$request->env('HTTPS', 'on');
		Router::pushRequest(
			$request->addParams(array(
				'plugin' => null,
				'controller' => 'images',
				'action' => 'index'
			))->addPaths(array(
				'base' => '',
				'here' => '/images/index',
				'webroot' => '/',
			))
		);

		$result = Router::url(array(
			'_ssl' => false
		));
		$this->assertEquals('http://localhost/images/index', $result);

		$result = Router::url(array(
			'_ssl' => true
		));
		$this->assertEquals('https://localhost/images/index', $result);
	}

/**
 * test that prefix routes persist when they are in the current request.
 *
 * @return void
 */
	public function testPrefixRoutePersistence() {
		Router::reload();
		Router::connect('/protected/:controller/:action', array('prefix' => 'protected'));
		Router::connect('/:controller/:action');

		$request = new Request();
		Router::setRequestInfo(
			$request->addParams(array(
				'plugin' => null,
				'controller' => 'images',
				'action' => 'index',
				'prefix' => 'protected',
			))->addPaths(array(
				'base' => '',
				'here' => '/protected/images/index',
				'webroot' => '/',
			))
		);

		$result = Router::url(array('prefix' => 'protected', 'controller' => 'images', 'action' => 'add'));
		$expected = '/protected/images/add';
		$this->assertEquals($expected, $result);

		$result = Router::url(array('controller' => 'images', 'action' => 'add'));
		$expected = '/protected/images/add';
		$this->assertEquals($expected, $result);

		$result = Router::url(array('controller' => 'images', 'action' => 'add', 'prefix' => false));
		$expected = '/images/add';
		$this->assertEquals($expected, $result);
	}

/**
 * test that setting a prefix override the current one
 *
 * @return void
 */
	public function testPrefixOverride() {
		Router::connect('/admin/:controller/:action', array('prefix' => 'admin'));
		Router::connect('/protected/:controller/:action', array('prefix' => 'protected'));

		$request = new Request();
		Router::setRequestInfo(
			$request->addParams(array(
				'plugin' => null, 'controller' => 'images', 'action' => 'index', 'prefix' => 'protected',
			))->addPaths(array(
				'base' => '',
				'here' => '/protected/images/index',
				'webroot' => '/',
			))
		);

		$result = Router::url(array('controller' => 'images', 'action' => 'add', 'prefix' => 'admin'));
		$expected = '/admin/images/add';
		$this->assertEquals($expected, $result);

		$request = new Request();
		Router::setRequestInfo(
			$request->addParams(array(
				'plugin' => null,
				'controller' => 'images',
				'action' => 'index',
				'prefix' => 'admin',
			))->addPaths(array(
				'base' => '',
				'here' => '/admin/images/index',
				'webroot' => '/',
			))
		);
		$result = Router::url(array('controller' => 'images', 'action' => 'add', 'prefix' => 'protected'));
		$expected = '/protected/images/add';
		$this->assertEquals($expected, $result);
	}

/**
 * Test that well known route parameters are passed through.
 *
 * @return void
 */
	public function testRouteParamDefaults() {
		Router::connect('/cache/*', array('prefix' => false, 'plugin' => true, 'controller' => 0, 'action' => 1));

		$url = Router::url(array('prefix' => 0, 'plugin' => 1, 'controller' => 0, 'action' => 1, 'test'));
		$expected = '/cache/test';
		$this->assertEquals($expected, $url);

		try {
			Router::url(array('controller' => 0, 'action' => 1, 'test'));
			$this->fail('No exception raised');
		} catch (\Exception $e) {
			$this->assertTrue(true, 'Exception was raised');
		}

		try {
			Router::url(array('prefix' => 1, 'controller' => 0, 'action' => 1, 'test'));
			$this->fail('No exception raised');
		} catch (\Exception $e) {
			$this->assertTrue(true, 'Exception was raised');
		}
	}

/**
 * testRemoveBase method
 *
 * @return void
 */
	public function testRemoveBase() {
		Router::connect('/:controller/:action');

		$request = new Request();
		Router::setRequestInfo(
			$request->addParams(array(
				'plugin' => null, 'controller' => 'controller', 'action' => 'index',
			))->addPaths(array(
				'base' => '/base',
				'here' => '/',
				'webroot' => '/base/',
			))
		);

		$result = Router::url(array('controller' => 'my_controller', 'action' => 'my_action'));
		$expected = '/base/my_controller/my_action';
		$this->assertEquals($expected, $result);

		$result = Router::url(array('controller' => 'my_controller', 'action' => 'my_action', '_base' => false));
		$expected = '/my_controller/my_action';
		$this->assertEquals($expected, $result);
	}

/**
 * testPagesUrlParsing method
 *
 * @return void
 */
	public function testPagesUrlParsing() {
		Router::connect('/', array('controller' => 'pages', 'action' => 'display', 'home'));
		Router::connect('/pages/*', array('controller' => 'pages', 'action' => 'display'));

		$result = Router::parse('/');
		$expected = array('pass' => array('home'), 'plugin' => null, 'controller' => 'pages', 'action' => 'display');
		$this->assertEquals($expected, $result);

		$result = Router::parse('/pages/home/');
		$expected = array('pass' => array('home'), 'plugin' => null, 'controller' => 'pages', 'action' => 'display');
		$this->assertEquals($expected, $result);

		Router::reload();
		Router::connect('/', array('controller' => 'pages', 'action' => 'display', 'home'));

		$result = Router::parse('/');
		$expected = array('pass' => array('home'), 'plugin' => null, 'controller' => 'pages', 'action' => 'display');
		$this->assertEquals($expected, $result);

		Router::reload();
		Router::connect('/', array('controller' => 'posts', 'action' => 'index'));
		Router::connect('/pages/*', array('controller' => 'pages', 'action' => 'display'));
		$result = Router::parse('/pages/contact/');

		$expected = array('pass' => array('contact'), 'plugin' => null, 'controller' => 'pages', 'action' => 'display');
		$this->assertEquals($expected, $result);
	}

/**
 * test that requests with a trailing dot don't loose the do.
 *
 * @return void
 */
	public function testParsingWithTrailingPeriod() {
		Router::reload();
		Router::connect('/:controller/:action/*');
		$result = Router::parse('/posts/view/something.');
		$this->assertEquals('something.', $result['pass'][0], 'Period was chopped off %s');

		$result = Router::parse('/posts/view/something. . .');
		$this->assertEquals('something. . .', $result['pass'][0], 'Period was chopped off %s');
	}

/**
 * test that requests with a trailing dot don't loose the do.
 *
 * @return void
 */
	public function testParsingWithTrailingPeriodAndParseExtensions() {
		Router::reload();
		Router::connect('/:controller/:action/*');
		Router::extensions('json', false);

		$result = Router::parse('/posts/view/something.');
		$this->assertEquals('something.', $result['pass'][0], 'Period was chopped off %s');

		$result = Router::parse('/posts/view/something. . .');
		$this->assertEquals('something. . .', $result['pass'][0], 'Period was chopped off %s');
	}

/**
 * test that patterns work for :action
 *
 * @expectedException Cake\Routing\Exception\MissingRouteException
 * @return void
 */
	public function testParsingWithPatternOnAction() {
		Router::connect(
			'/blog/:action/*',
			array('controller' => 'blog_posts'),
			array('action' => 'other|actions')
		);

		$result = Router::parse('/blog/other');
		$expected = array(
			'plugin' => null,
			'controller' => 'blog_posts',
			'action' => 'other',
			'pass' => [],
		);
		$this->assertEquals($expected, $result);

		Router::parse('/blog/foobar');
	}

/**
 * Test url() works with patterns on :action
 *
 * @expectedException Cake\Routing\Exception\MissingRouteException
 * @return void
 */
	public function testUrlPatternOnAction() {
		Router::connect(
			'/blog/:action/*',
			array('controller' => 'blog_posts'),
			array('action' => 'other|actions')
		);

		$result = Router::url(array('controller' => 'blog_posts', 'action' => 'actions'));
		$this->assertEquals('/blog/actions', $result);

		$result = Router::url(array('controller' => 'blog_posts', 'action' => 'foo'));
		$this->assertEquals('/', $result);
	}

/**
 * testParsingWithLiteralPrefixes method
 *
 * @return void
 */
	public function testParsingWithLiteralPrefixes() {
		Router::reload();
		$adminParams = array('prefix' => 'admin');
		Router::connect('/admin/:controller', $adminParams);
		Router::connect('/admin/:controller/:action/*', $adminParams);

		$request = new Request();
		Router::setRequestInfo(
			$request->addParams(array(
				'plugin' => null, 'controller' => 'controller', 'action' => 'index'
			))->addPaths(array(
				'base' => '/base',
				'here' => '/',
				'webroot' => '/base/',
			))
		);

		$result = Router::parse('/admin/posts/');
		$expected = array('pass' => [], 'prefix' => 'admin', 'plugin' => null, 'controller' => 'posts', 'action' => 'index');
		$this->assertEquals($expected, $result);

		$result = Router::parse('/admin/posts');
		$this->assertEquals($expected, $result);

		$result = Router::url(array('prefix' => 'admin', 'controller' => 'posts'));
		$expected = '/base/admin/posts';
		$this->assertEquals($expected, $result);

		Router::reload();

		$prefixParams = array('prefix' => 'members');
		Router::connect('/members/:controller', $prefixParams);
		Router::connect('/members/:controller/:action', $prefixParams);
		Router::connect('/members/:controller/:action/*', $prefixParams);

		$request = new Request();
		Router::setRequestInfo(
			$request->addParams(array(
				'plugin' => null, 'controller' => 'controller', 'action' => 'index',
			))->addPaths(array(
				'base' => '/base',
				'here' => '/',
				'webroot' => '/',
			))
		);

		$result = Router::parse('/members/posts/index');
		$expected = array('pass' => [], 'prefix' => 'members', 'plugin' => null, 'controller' => 'posts', 'action' => 'index');
		$this->assertEquals($expected, $result);

		$result = Router::url(array('prefix' => 'members', 'controller' => 'users', 'action' => 'add'));
		$expected = '/base/members/users/add';
		$this->assertEquals($expected, $result);
	}

/**
 * Tests URL generation with flags and prefixes in and out of context
 *
 * @return void
 */
	public function testUrlWritingWithPrefixes() {
		Router::connect('/company/:controller/:action/*', array('prefix' => 'company'));
		Router::connect('/:action', array('controller' => 'users'));

		$result = Router::url(array('controller' => 'users', 'action' => 'login', 'prefix' => 'company'));
		$expected = '/company/users/login';
		$this->assertEquals($expected, $result);

		$request = new Request();
		Router::setRequestInfo(
			$request->addParams(array(
				'plugin' => null,
				'controller' => 'users',
				'action' => 'login',
				'prefix' => 'company'
			))->addPaths(array(
				'base' => '/',
				'here' => '/',
				'webroot' => '/base/',
			))
		);

		$result = Router::url(array('controller' => 'users', 'action' => 'login', 'prefix' => false));
		$expected = '/login';
		$this->assertEquals($expected, $result);
	}

/**
 * test url generation with prefixes and custom routes
 *
 * @return void
 */
	public function testUrlWritingWithPrefixesAndCustomRoutes() {
		Router::connect(
			'/admin/login',
			array('controller' => 'users', 'action' => 'login', 'prefix' => 'admin')
		);
		$request = new Request();
		Router::setRequestInfo(
			$request->addParams(array(
				'plugin' => null, 'controller' => 'posts', 'action' => 'index',
				'prefix' => 'admin'
			))->addPaths(array(
				'base' => '/',
				'here' => '/',
				'webroot' => '/',
			))
		);
		$result = Router::url(array('controller' => 'users', 'action' => 'login'));
		$this->assertEquals('/admin/login', $result);

		$result = Router::url(array('controller' => 'users', 'action' => 'login'));
		$this->assertEquals('/admin/login', $result);
	}

/**
 * testPassedArgsOrder method
 *
 * @return void
 */
	public function testPassedArgsOrder() {
		Router::connect('/test-passed/*', array('controller' => 'pages', 'action' => 'display', 'home'));
		Router::connect('/test2/*', array('controller' => 'pages', 'action' => 'display', 2));
		Router::connect('/test/*', array('controller' => 'pages', 'action' => 'display', 1));

		$result = Router::url(array('controller' => 'pages', 'action' => 'display', 1, 'whatever'));
		$expected = '/test/whatever';
		$this->assertEquals($expected, $result);

		$result = Router::url(array('controller' => 'pages', 'action' => 'display', 2, 'whatever'));
		$expected = '/test2/whatever';
		$this->assertEquals($expected, $result);

		$result = Router::url(array('controller' => 'pages', 'action' => 'display', 'home', 'whatever'));
		$expected = '/test-passed/whatever';
		$this->assertEquals($expected, $result);
	}

/**
 * testRegexRouteMatching method
 *
 * @return void
 */
	public function testRegexRouteMatching() {
		Router::connect('/:locale/:controller/:action/*', [], array('locale' => 'dan|eng'));

		$result = Router::parse('/eng/test/test_action');
		$expected = array('pass' => [], 'locale' => 'eng', 'controller' => 'test', 'action' => 'test_action', 'plugin' => null);
		$this->assertEquals($expected, $result);
	}

/**
 * testRegexRouteMatching error
 *
 * @expectedException Cake\Routing\Exception\MissingRouteException
 * @return void
 */
	public function testRegexRouteMatchingError() {
		Router::connect('/:locale/:controller/:action/*', [], array('locale' => 'dan|eng'));
		Router::parse('/badness/test/test_action');
	}

/**
 * testRegexRouteMatching method
 *
 * @expectedException Cake\Routing\Exception\MissingRouteException
 * @return void
 */
	public function testRegexRouteMatchUrl() {
		Router::connect('/:locale/:controller/:action/*', [], array('locale' => 'dan|eng'));

		$request = new Request();
		Router::setRequestInfo(
			$request->addParams(array(
				'plugin' => null,
				'controller' => 'test',
				'action' => 'index',
				'url' => array('url' => 'test/test_action')
			))->addPaths(array(
				'base' => '',
				'here' => '/test/test_action',
				'webroot' => '/',
			))
		);

		$result = Router::url(array('action' => 'test_another_action', 'locale' => 'eng'));
		$expected = '/eng/test/test_another_action';
		$this->assertEquals($expected, $result);

		$result = Router::url(array('action' => 'test_another_action'));
		$expected = '/';
		$this->assertEquals($expected, $result);
	}

/**
 * test using a custom route class for route connection
 *
 * @return void
 */
	public function testUsingCustomRouteClass() {
		Plugin::load('TestPlugin');
		Router::connect(
			'/:slug',
			array('plugin' => 'TestPlugin', 'action' => 'index'),
			array('routeClass' => 'PluginShortRoute', 'slug' => '[a-z_-]+')
		);
		$result = Router::parse('/the-best');
		$expected = [
			'plugin' => 'TestPlugin',
			'controller' => 'TestPlugin',
			'action' => 'index',
			'slug' => 'the-best',
			'pass' => [],
		];
		$this->assertEquals($expected, $result);
	}

/**
 * test using custom route class in PluginDot notation
 *
 * @return void
 */
	public function testUsingCustomRouteClassPluginDotSyntax() {
		Plugin::load('TestPlugin');
		Router::connect(
			'/:slug',
			array('controller' => 'posts', 'action' => 'view'),
			array('routeClass' => 'TestPlugin.TestRoute', 'slug' => '[a-z_-]+')
		);
		$this->assertTrue(true); // Just to make sure the connect do not throw exception
		Plugin::unload('TestPlugin');
	}

/**
 * test that route classes must extend \Cake\Routing\Route\Route
 *
 * @expectedException InvalidArgumentException
 * @return void
 */
	public function testCustomRouteException() {
		Router::connect('/:controller', [], array('routeClass' => 'Object'));
	}

/**
 * test reversing parameter arrays back into strings.
 *
 * Mark the router as initialized so it doesn't auto-load routes
 *
 * @return void
 */
	public function testReverse() {
		Router::connect('/:controller/:action/*');
		$params = array(
			'controller' => 'posts',
			'action' => 'view',
			'pass' => array(1),
			'url' => [],
			'autoRender' => 1,
			'bare' => 1,
			'return' => 1,
			'requested' => 1,
			'_Token' => array('key' => 'sekret')
		);
		$result = Router::reverse($params);
		$this->assertEquals('/posts/view/1', $result);

		Router::reload();
		Router::connect('/:lang/:controller/:action/*', [], array('lang' => '[a-z]{3}'));
		$params = array(
			'lang' => 'eng',
			'controller' => 'posts',
			'action' => 'view',
			'pass' => array(1),
			'url' => array('url' => 'eng/posts/view/1')
		);
		$result = Router::reverse($params);
		$this->assertEquals('/eng/posts/view/1', $result);

		$params = array(
			'lang' => 'eng',
			'controller' => 'posts',
			'action' => 'view',
			1,
			'?' => ['foo' => 'bar']
		);
		$result = Router::reverse($params);
		$this->assertEquals('/eng/posts/view/1?foo=bar', $result);

		$params = array(
			'lang' => 'eng',
			'controller' => 'posts',
			'action' => 'view',
			'pass' => array(1),
			'url' => array('url' => 'eng/posts/view/1', 'foo' => 'bar', 'baz' => 'quu'),
			'paging' => [],
			'models' => []
		);
		$result = Router::reverse($params);
		$this->assertEquals('/eng/posts/view/1?foo=bar&baz=quu', $result);

		$request = new Request('/eng/posts/view/1');
		$request->addParams(array(
			'lang' => 'eng',
			'controller' => 'posts',
			'action' => 'view',
			'pass' => array(1),
		));
		$request->query = array('url' => 'eng/posts/view/1', 'test' => 'value');
		$result = Router::reverse($request);
		$expected = '/eng/posts/view/1?test=value';
		$this->assertEquals($expected, $result);

		$params = array(
			'lang' => 'eng',
			'controller' => 'posts',
			'action' => 'view',
			'pass' => array(1),
			'url' => array('url' => 'eng/posts/view/1')
		);
		$result = Router::reverse($params, true);
		$this->assertRegExp('/^http(s)?:\/\//', $result);
	}

/**
 * Test that extensions work with Router::reverse()
 *
 * @return void
 */
	public function testReverseWithExtension() {
		Router::connect('/:controller/:action/*');
		Router::extensions('json', false);

		$request = new Request('/posts/view/1.json');
		$request->addParams(array(
			'controller' => 'posts',
			'action' => 'view',
			'pass' => array(1),
			'_ext' => 'json',
		));
		$request->query = [];
		$result = Router::reverse($request);
		$expected = '/posts/view/1.json';
		$this->assertEquals($expected, $result);
	}

/**
 * test that setRequestInfo can accept arrays and turn that into a Request object.
 *
 * @return void
 */
	public function testSetRequestInfoLegacy() {
		Router::setRequestInfo(array(
			array(
				'plugin' => null, 'controller' => 'images', 'action' => 'index',
				'url' => array('url' => 'protected/images/index')
			),
			array(
				'base' => '',
				'here' => '/protected/images/index',
				'webroot' => '/',
			)
		));
		$result = Router::getRequest();
		$this->assertEquals('images', $result->controller);
		$this->assertEquals('index', $result->action);
		$this->assertEquals('', $result->base);
		$this->assertEquals('/protected/images/index', $result->here);
		$this->assertEquals('/', $result->webroot);
	}

/**
 * test get request.
 *
 * @return void
 */
	public function testGetRequest() {
		$requestA = new Request('/');
		$requestB = new Request('/posts');

		Router::pushRequest($requestA);
		Router::pushRequest($requestB);

		$this->assertSame($requestA, Router::getRequest(false));
		$this->assertSame($requestB, Router::getRequest(true));
	}

/**
 * Test that Router::url() uses the first request
 */
	public function testUrlWithRequestAction() {
		Router::connect('/:controller', array('action' => 'index'));
		Router::connect('/:controller/:action');

		$firstRequest = new Request('/posts/index');
		$firstRequest->addParams(array(
			'plugin' => null,
			'controller' => 'posts',
			'action' => 'index'
		))->addPaths(array('base' => ''));

		$secondRequest = new Request('/posts/index');
		$secondRequest->addParams(array(
			'requested' => 1,
			'plugin' => null,
			'controller' => 'comments',
			'action' => 'listing'
		))->addPaths(array('base' => ''));

		Router::setRequestInfo($firstRequest);
		Router::setRequestInfo($secondRequest);

		$result = Router::url(array('_base' => false));
		$this->assertEquals('/comments/listing', $result, 'with second requests, the last should win.');

		Router::popRequest();
		$result = Router::url(array('_base' => false));
		$this->assertEquals('/posts', $result, 'with second requests, the last should win.');

		// Make sure that popping an empty request doesn't fail.
		Router::popRequest();
	}

/**
 * test that a route object returning a full URL is not modified.
 *
 * @return void
 */
	public function testUrlFullUrlReturnFromRoute() {
		$url = 'http://example.com/posts/view/1';

		$route = $this->getMock(
			'Cake\Routing\Route\Route',
			['match'],
			['/:controller/:action/*']
		);
		$route->expects($this->any())
			->method('match')
			->will($this->returnValue($url));
		Router::connect($route);

		$result = Router::url(array('controller' => 'posts', 'action' => 'view', 1));
		$this->assertEquals($url, $result);
	}

/**
 * test protocol in url
 *
 * @return void
 */
	public function testUrlProtocol() {
		$url = 'http://example.com';
		$this->assertEquals($url, Router::url($url));

		$url = 'ed2k://example.com';
		$this->assertEquals($url, Router::url($url));

		$url = 'svn+ssh://example.com';
		$this->assertEquals($url, Router::url($url));

		$url = '://example.com';
		$this->assertEquals($url, Router::url($url));

		$url = '//example.com';
		$this->assertEquals($url, Router::url($url));

		$url = 'javascript:void(0)';
		$this->assertEquals($url, Router::url($url));

		$url = 'tel:012345-678';
		$this->assertEquals($url, Router::url($url));

		$url = 'sms:012345-678';
		$this->assertEquals($url, Router::url($url));

		$url = '#here';
		$this->assertEquals($url, Router::url($url));

		$url = '?param=0';
		$this->assertEquals($url, Router::url($url));

		$url = '/posts/index#here';
		$expected = Configure::read('App.fullBaseUrl') . '/posts/index#here';
		$this->assertEquals($expected, Router::url($url, true));
	}

/**
 * Testing that patterns on the :action param work properly.
 *
 * @return void
 */
	public function testPatternOnAction() {
		$route = new Route(
			'/blog/:action/*',
			array('controller' => 'blog_posts'),
			array('action' => 'other|actions')
		);
		$result = $route->match(array('controller' => 'blog_posts', 'action' => 'foo'));
		$this->assertFalse($result);

		$result = $route->match(array('controller' => 'blog_posts', 'action' => 'actions'));
		$this->assertEquals('/blog/actions/', $result);

		$result = $route->parse('/blog/other');
		$expected = array('controller' => 'blog_posts', 'action' => 'other', 'pass' => []);
		$this->assertEquals($expected, $result);

		$result = $route->parse('/blog/foobar');
		$this->assertFalse($result);
	}

/**
 * Test that redirect() works.
 *
 * @return void
 */
	public function testRedirect() {
		Router::redirect('/mobile', '/', ['status' => 301]);
		$routes = Router::routes();
		$route = $routes[0];
		$this->assertInstanceOf('Cake\Routing\Route\RedirectRoute', $route);
	}

/**
 * Test that the compatibility method for incoming urls works.
 *
 * @return void
 */
	public function testParseNamedParameters() {
		$request = new Request();
		$request->addParams(array(
			'controller' => 'posts',
			'action' => 'index',
		));
		$result = Router::parseNamedParams($request);
		$this->assertSame([], $result->params['named']);

		$request = new Request();
		$request->addParams(array(
			'controller' => 'posts',
			'action' => 'index',
			'pass' => array('home', 'one:two', 'three:four', 'five[nested][0]:six', 'five[nested][1]:seven')
		));
		Router::parseNamedParams($request);
		$expected = array(
			'plugin' => null,
			'controller' => 'posts',
			'action' => 'index',
			'_ext' => null,
			'pass' => array('home'),
			'named' => array(
				'one' => 'two',
				'three' => 'four',
				'five' => array(
					'nested' => array('six', 'seven')
				)
			)
		);
		$this->assertEquals($expected, $request->params);
	}

/**
 * Test the scope() method
 *
 * @return void
 */
	public function testScope() {
		Router::scope('/path', ['param' => 'value'], function ($routes) {
			$this->assertInstanceOf('Cake\Routing\RouteBuilder', $routes);
			$this->assertEquals('/path', $routes->path());
			$this->assertEquals(['param' => 'value'], $routes->params());

			$routes->connect('/articles', ['controller' => 'Articles']);
		});
	}

/**
 * Test the scope() method
 *
 * @expectedException \InvalidArgumentException
 * @return void
 */
	public function testScopeError() {
		Router::scope('/path', 'derpy');
	}

/**
 * Test that prefix() creates a scope.
 *
 * @return void
 */
	public function testPrefix() {
		Router::prefix('admin', function ($routes) {
			$this->assertInstanceOf('Cake\Routing\RouteBuilder', $routes);
			$this->assertEquals('/admin', $routes->path());
			$this->assertEquals(['prefix' => 'admin'], $routes->params());
		});
	}

/**
 * Test that plugin() creates a scope.
 *
 * @return void
 */
	public function testPlugin() {
		Router::plugin('DebugKit', function ($routes) {
			$this->assertInstanceOf('Cake\Routing\RouteBuilder', $routes);
			$this->assertEquals('/debug_kit', $routes->path());
			$this->assertEquals(['plugin' => 'DebugKit'], $routes->params());
		});
	}

/**
 * Test that plugin() accepts options
 *
 * @return void
 */
	public function testPluginOptions() {
		Router::plugin('DebugKit', ['path' => '/debugger'], function ($routes) {
			$this->assertInstanceOf('Cake\Routing\RouteBuilder', $routes);
			$this->assertEquals('/debugger', $routes->path());
			$this->assertEquals(['plugin' => 'DebugKit'], $routes->params());
		});
	}

/**
 * Test setting default route class.
 *
 * @return void
 */
	public function testDefaultRouteClass() {
		Router::connect('/:controller', ['action' => 'index']);
		$result = Router::url(['controller' => 'FooBar', 'action' => 'index']);
		$this->assertEquals('/FooBar', $result);

		// This is needed because tests/boostrap.php sets App.namespace to 'App'
		Configure::write('App.namespace', 'TestApp');

		Router::defaultRouteClass('DashedRoute');
		Router::connect('/cake/:controller', ['action' => 'cake']);
		$result = Router::url(['controller' => 'FooBar', 'action' => 'cake']);
		$this->assertEquals('/cake/foo-bar', $result);

		$result = Router::url(['controller' => 'FooBar', 'action' => 'index']);
		$this->assertEquals('/FooBar', $result);

		Router::reload();
		Router::defaultRouteClass('DashedRoute');
		Router::scope('/', function ($routes) {
			$routes->fallbacks();
		});

		$result = Router::url(['controller' => 'FooBar', 'action' => 'index']);
		$this->assertEquals('/foo-bar', $result);
	}

/**
 * Connect some fallback routes for testing router behavior.
 *
 * @return void
 */
	protected function _connectDefaultRoutes() {
		Router::scope('/', function ($routes) {
			$routes->fallbacks();
		});
	}

}
