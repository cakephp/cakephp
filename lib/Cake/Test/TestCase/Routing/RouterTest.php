<?php
/**
 * RouterTest file
 *
 * PHP 5
 *
 * CakePHP(tm) Tests <http://book.cakephp.org/2.0/en/development/testing.html>
 * Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 *	Licensed under The Open Group Test Suite License
 *	Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/2.0/en/development/testing.html CakePHP(tm) Tests
 * @package       Cake.Test.Case.Routing
 * @since         CakePHP(tm) v 1.2.0.4206
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
namespace Cake\Test\TestCase\Routing;

use Cake\Core\App;
use Cake\Core\Configure;
use Cake\Core\Plugin;
use Cake\Network\Request;
use Cake\Routing\Route\Route;
use Cake\Routing\Router;
use Cake\TestSuite\TestCase;

if (!defined('FULL_BASE_URL')) {
	define('FULL_BASE_URL', 'http://cakephp.org');
}

/**
 * RouterTest class
 *
 * @package       Cake.Test.Case.Routing
 */
class RouterTest extends TestCase {

/**
 * setUp method
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();
		Configure::write('Routing', array('admin' => null, 'prefixes' => array()));
		Router::reload();
	}

/**
 * tearDown method
 *
 * @return void
 */
	public function tearDown() {
		parent::tearDown();
		Plugin::unload();
	}

/**
 * testFullBaseURL method
 *
 * @return void
 */
	public function testFullBaseURL() {
		$skip = PHP_SAPI == 'cli';
		if ($skip) {
			$this->markTestSkipped('Cannot validate base urls in CLI');
		}
		$this->assertRegExp('/^http(s)?:\/\//', Router::url('/', true));
		$this->assertRegExp('/^http(s)?:\/\//', Router::url(null, true));
		$this->assertRegExp('/^http(s)?:\/\//', Router::url(array('_full' => true)));
		$this->assertSame(FULL_BASE_URL . '/', Router::url(array('_full' => true)));
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
		$resources = Router::mapResources('Posts');

		$_SERVER['REQUEST_METHOD'] = 'GET';
		$result = Router::parse('/posts');
		$this->assertEquals($result, array('pass' => array(), 'plugin' => '', 'controller' => 'posts', 'action' => 'index', '[method]' => 'GET'));
		$this->assertEquals($resources, array('posts'));

		$_SERVER['REQUEST_METHOD'] = 'GET';
		$result = Router::parse('/posts/13');
		$this->assertEquals($result, array('pass' => array('13'), 'plugin' => '', 'controller' => 'posts', 'action' => 'view', 'id' => '13', '[method]' => 'GET'));

		$_SERVER['REQUEST_METHOD'] = 'POST';
		$result = Router::parse('/posts');
		$this->assertEquals($result, array('pass' => array(), 'plugin' => '', 'controller' => 'posts', 'action' => 'add', '[method]' => 'POST'));

		$_SERVER['REQUEST_METHOD'] = 'PUT';
		$result = Router::parse('/posts/13');
		$this->assertEquals($result, array('pass' => array('13'), 'plugin' => '', 'controller' => 'posts', 'action' => 'edit', 'id' => '13', '[method]' => 'PUT'));

		$result = Router::parse('/posts/475acc39-a328-44d3-95fb-015000000000');
		$this->assertEquals($result, array('pass' => array('475acc39-a328-44d3-95fb-015000000000'), 'plugin' => '', 'controller' => 'posts', 'action' => 'edit', 'id' => '475acc39-a328-44d3-95fb-015000000000', '[method]' => 'PUT'));

		$_SERVER['REQUEST_METHOD'] = 'DELETE';
		$result = Router::parse('/posts/13');
		$this->assertEquals($result, array('pass' => array('13'), 'plugin' => '', 'controller' => 'posts', 'action' => 'delete', 'id' => '13', '[method]' => 'DELETE'));

		$_SERVER['REQUEST_METHOD'] = 'GET';
		$result = Router::parse('/posts/add');
		$this->assertEquals(array(), $result);

		Router::reload();
		$resources = Router::mapResources('Posts', array('id' => '[a-z0-9_]+'));
		$this->assertEquals(array('posts'), $resources);

		$_SERVER['REQUEST_METHOD'] = 'GET';
		$result = Router::parse('/posts/add');
		$this->assertEquals(array('pass' => array('add'), 'plugin' => '', 'controller' => 'posts', 'action' => 'view', 'id' => 'add', '[method]' => 'GET'), $result);

		$_SERVER['REQUEST_METHOD'] = 'PUT';
		$result = Router::parse('/posts/name');
		$this->assertEquals(array('pass' => array('name'), 'plugin' => '', 'controller' => 'posts', 'action' => 'edit', 'id' => 'name', '[method]' => 'PUT'), $result);
	}

/**
 * testMapResources with plugin controllers.
 *
 * @return void
 */
	public function testPluginMapResources() {
		App::build(array(
			'Plugin' => array(
				CAKE . 'Test' . DS . 'TestApp' . DS . 'Plugin' . DS
			)
		));
		$resources = Router::mapResources('TestPlugin.TestPlugin');

		$_SERVER['REQUEST_METHOD'] = 'GET';
		$result = Router::parse('/test_plugin/test_plugin');
		$expected = array(
			'pass' => array(),
			'plugin' => 'test_plugin',
			'controller' => 'test_plugin',
			'action' => 'index',
			'[method]' => 'GET'
		);
		$this->assertEquals($expected, $result);
		$this->assertEquals(array('test_plugin'), $resources);

		$_SERVER['REQUEST_METHOD'] = 'GET';
		$result = Router::parse('/test_plugin/test_plugin/13');
		$expected = array(
			'pass' => array('13'),
			'plugin' => 'test_plugin',
			'controller' => 'test_plugin',
			'action' => 'view',
			'id' => '13',
			'[method]' => 'GET'
		);
		$this->assertEquals($expected, $result);
	}

/**
 * Test mapResources with a plugin and prefix.
 *
 * @return void
 */
	public function testPluginMapResourcesWithPrefix() {
		App::build(array(
			'Plugin' => array(
				CAKE . 'Test' . DS . 'TestApp' . DS . 'Plugin' . DS
			)
		));
		$resources = Router::mapResources('TestPlugin.TestPlugin', array('prefix' => '/api/'));

		$_SERVER['REQUEST_METHOD'] = 'GET';
		$result = Router::parse('/api/test_plugin');
		$expected = array(
			'pass' => array(),
			'plugin' => 'test_plugin',
			'controller' => 'test_plugin',
			'action' => 'index',
			'[method]' => 'GET'
		);
		$this->assertEquals($expected, $result);
		$this->assertEquals(array('test_plugin'), $resources);
	}

/**
 * testMultipleResourceRoute method
 *
 * @return void
 */
	public function testMultipleResourceRoute() {
		Router::connect('/:controller', array('action' => 'index', '[method]' => array('GET', 'POST')));

		$_SERVER['REQUEST_METHOD'] = 'GET';
		$result = Router::parse('/posts');
		$this->assertEquals(array('pass' => array(), 'plugin' => '', 'controller' => 'posts', 'action' => 'index', '[method]' => array('GET', 'POST')), $result);

		$_SERVER['REQUEST_METHOD'] = 'POST';
		$result = Router::parse('/posts');
		$this->assertEquals(array('pass' => array(), 'plugin' => '', 'controller' => 'posts', 'action' => 'index', '[method]' => array('GET', 'POST')), $result);
	}

/**
 * testGenerateUrlResourceRoute method
 *
 * @return void
 */
	public function testGenerateUrlResourceRoute() {
		Router::mapResources('Posts');

		$result = Router::url(array(
			'controller' => 'posts',
			'action' => 'index',
			'[method]' => 'GET'
		));
		$expected = '/posts';
		$this->assertEquals($expected, $result);

		$result = Router::url(array(
			'controller' => 'posts',
			'action' => 'view',
			'[method]' => 'GET',
			'id' => 10
		));
		$expected = '/posts/10';
		$this->assertEquals($expected, $result);

		$result = Router::url(array('controller' => 'posts', 'action' => 'add', '[method]' => 'POST'));
		$expected = '/posts';
		$this->assertEquals($expected, $result);

		$result = Router::url(array('controller' => 'posts', 'action' => 'edit', '[method]' => 'PUT', 'id' => 10));
		$expected = '/posts/10';
		$this->assertEquals($expected, $result);

		$result = Router::url(array('controller' => 'posts', 'action' => 'delete', '[method]' => 'DELETE', 'id' => 10));
		$expected = '/posts/10';
		$this->assertEquals($expected, $result);

		$result = Router::url(array('controller' => 'posts', 'action' => 'edit', '[method]' => 'POST', 'id' => 10));
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
		$_back = Configure::read('App.baseUrl');
		Configure::write('App.baseUrl', '/');

		$request = new Request();
		$request->base = '/';
		Router::setRequestInfo($request);
		$result = Router::normalize('users/login');
		$this->assertEquals('/users/login', $result);
		Configure::write('App.baseUrl', $_back);

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
 * test generation of basic urls.
 *
 * @return void
 */
	public function testUrlGenerationBasic() {
		extract(Router::getNamedExpressions());

		$request = new Request();
		$request->addParams(array(
			'action' => 'index', 'plugin' => null, 'controller' => 'subscribe', 'admin' => true
		));
		$request->base = '/magazine';
		$request->here = '/magazine';
		$request->webroot = '/magazine/';
		Router::setRequestInfo($request);

		$result = Router::url();
		$this->assertEquals('/magazine', $result);

		Router::reload();

		Router::connect('/', array('controller' => 'pages', 'action' => 'display', 'home'));
		$out = Router::url(array('controller' => 'pages', 'action' => 'display', 'home'));
		$this->assertEquals('/', $out);

		Router::connect('/pages/*', array('controller' => 'pages', 'action' => 'display'));
		$result = Router::url(array('controller' => 'pages', 'action' => 'display', 'about'));
		$expected = '/pages/about';
		$this->assertEquals($expected, $result);

		Router::reload();
		Router::connect('/:plugin/:id/*', array('controller' => 'posts', 'action' => 'view'), array('id' => $ID));
		Router::parse('/');

		$result = Router::url(array('plugin' => 'cake_plugin', 'controller' => 'posts', 'action' => 'view', 'id' => '1'));
		$expected = '/cake_plugin/1';
		$this->assertEquals($expected, $result);

		$result = Router::url(array('plugin' => 'cake_plugin', 'controller' => 'posts', 'action' => 'view', 'id' => '1', '0'));
		$expected = '/cake_plugin/1/0';
		$this->assertEquals($expected, $result);

		Router::reload();
		Router::connect('/:controller/:action/:id', array(), array('id' => $ID));
		Router::parse('/');

		$result = Router::url(array('controller' => 'posts', 'action' => 'view', 'id' => '1'));
		$expected = '/posts/view/1';
		$this->assertEquals($expected, $result);

		Router::reload();
		Router::connect('/:controller/:id', array('action' => 'view'));
		Router::parse('/');

		$result = Router::url(array('controller' => 'posts', 'action' => 'view', 'id' => '1'));
		$expected = '/posts/1';
		$this->assertEquals($expected, $result);

		Router::connect('/view/*', array('controller' => 'posts', 'action' => 'view'));
		Router::promote();
		$result = Router::url(array('controller' => 'posts', 'action' => 'view', '1'));
		$expected = '/view/1';
		$this->assertEquals($expected, $result);

		Router::reload();
		Router::connect('/:controller/:action');
		Router::parse('/');
		$request = new Request();
		$request->addParams(array(
			'action' => 'index', 'plugin' => null, 'controller' => 'users', 'url' => array('url' => 'users')
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
		Router::parse('/');

		$result = Router::url(array('plugin' => 'contact', 'controller' => 'contact', 'action' => 'me'));

		$expected = '/contact/me';
		$this->assertEquals($expected, $result);

		Router::reload();
		Router::connect('/:controller', array('action' => 'index'));
		$request = new Request();
		$request->addParams(array(
			'action' => 'index', 'plugin' => 'myplugin', 'controller' => 'mycontroller', 'admin' => false
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
 **/
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
			array(),
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
		Router::connect('/:language/:controller/:action/*', array(), array('language' => '[a-z]{3}'));

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
		Router::parse('/');

		$result = Router::url(array('plugin' => 'shows', 'controller' => 'shows', 'action' => 'calendar', 'month' => 10, 'year' => 2007, 'min-forestilling'));
		$expected = '/forestillinger/10/2007/min-forestilling';
		$this->assertEquals($expected, $result);

		Router::reload();
		Router::connect('/kalender/:month/:year/*',
			array('plugin' => 'shows', 'controller' => 'shows', 'action' => 'calendar'),
			array('month' => '0[1-9]|1[012]', 'year' => '[12][0-9]{3}')
		);
		Router::connect('/kalender/*', array('plugin' => 'shows', 'controller' => 'shows', 'action' => 'calendar'));
		Router::parse('/');

		$result = Router::url(array('plugin' => 'shows', 'controller' => 'shows', 'action' => 'calendar', 'min-forestilling'));
		$expected = '/kalender/min-forestilling';
		$this->assertEquals($expected, $result);

		$result = Router::url(array('plugin' => 'shows', 'controller' => 'shows', 'action' => 'calendar', 'year' => 2007, 'month' => 10, 'min-forestilling'));
		$expected = '/kalender/10/2007/min-forestilling';
		$this->assertEquals($expected, $result);

		Router::reload();
		Router::connect('/:controller/:action/*', array(), array(
			'controller' => 'source|wiki|commits|tickets|comments|view',
			'action' => 'branches|history|branch|logs|view|start|add|edit|modify'
		));
	}

/**
 * Test url generation with an admin prefix
 *
 * @return void
 */
	public function testUrlGenerationWithAdminPrefix() {
		Configure::write('Routing.prefixes', array('admin'));
		Router::reload();

		Router::connect('/pages/*', array('controller' => 'pages', 'action' => 'display'));
		Router::connect('/reset/*', array('admin' => true, 'controller' => 'users', 'action' => 'reset'));
		Router::connect('/tests', array('controller' => 'tests', 'action' => 'index'));
		Router::connect('/admin/:controller/:action/*', array('admin' => true, 'prefix' => 'admin'));
		Router::parseExtensions('rss');

		$request = new Request();
		$request->addParams(array(
			'controller' => 'registrations',
			'action' => 'admin_index',
			'plugin' => null,
			'prefix' => 'admin',
			'admin' => true,
			'ext' => 'html'
		));
		$request->base = '';
		$request->here = '/admin/registrations/index';
		$request->webroot = '/';
		Router::setRequestInfo($request);

		$result = Router::url(array());
		$expected = '/admin/registrations/index';
		$this->assertEquals($expected, $result);

		Router::reload();
		Router::connect('/admin/subscriptions/:action/*', array('controller' => 'subscribe', 'admin' => true, 'prefix' => 'admin'));
		Router::connect('/admin/:controller/:action/*', array('admin' => true, 'prefix' => 'admin'));
		Router::parse('/');

		$request = new Request();
		$request->addParams(array(
			'action' => 'admin_index',
			'plugin' => null,
			'controller' => 'subscribe',
			'admin' => true,
			'url' => array('url' => 'admin/subscriptions/edit/1')
		));
		$request->base = '/magazine';
		$request->here = '/magazine/admin/subscriptions/edit/1';
		$request->webroot = '/magazine/';
		Router::setRequestInfo($request);

		$result = Router::url(array('action' => 'edit', 1));
		$expected = '/magazine/admin/subscriptions/edit/1';
		$this->assertEquals($expected, $result);

		$result = Router::url(array('admin' => true, 'controller' => 'users', 'action' => 'login'));
		$expected = '/magazine/admin/users/login';
		$this->assertEquals($expected, $result);

		Router::reload();
		$request = new Request();
		$request->addParams(array(
			'admin' => true,
			'action' => 'index',
			'plugin' => null,
			'controller' => 'users',
			'url' => array('url' => 'users')
		));
		$request->base = '/';
		$request->here = '/';
		$request->webroot = '/';
		Router::setRequestInfo($request);

		Router::connect('/page/*', array('controller' => 'pages', 'action' => 'view', 'admin' => true, 'prefix' => 'admin'));
		Router::parse('/');

		$result = Router::url(array('admin' => true, 'controller' => 'pages', 'action' => 'view', 'my-page'));
		$expected = '/page/my-page';
		$this->assertEquals($expected, $result);

		Router::reload();
		Router::connect('/admin/:controller/:action/*', array('admin' => true, 'prefix' => 'admin'));

		$request = new Request();
		$request->addParams(array(
			'plugin' => null, 'controller' => 'pages', 'action' => 'admin_add', 'prefix' => 'admin', 'admin' => true,
			'url' => array('url' => 'admin/pages/add')
		));
		$request->base = '';
		$request->here = '/admin/pages/add';
		$request->webroot = '/';
		Router::setRequestInfo($request);
		Router::parse('/');

		$result = Router::url(array('plugin' => null, 'controller' => 'pages', 'action' => 'add', 'id' => false));
		$expected = '/admin/pages/add';
		$this->assertEquals($expected, $result);

		Router::reload();
		Router::connect('/admin/:controller/:action/*', array('admin' => true, 'prefix' => 'admin'));
		Router::parse('/');
		$request = new Request();
		$request->addParams(array(
			'plugin' => null, 'controller' => 'pages', 'action' => 'admin_add', 'prefix' => 'admin', 'admin' => true,
			'url' => array('url' => 'admin/pages/add')
		));
		$request->base = '';
		$request->here = '/admin/pages/add';
		$request->webroot = '/';
		Router::setRequestInfo($request);

		$result = Router::url(array('plugin' => null, 'controller' => 'pages', 'action' => 'add', 'id' => false));
		$expected = '/admin/pages/add';
		$this->assertEquals($expected, $result);

		Router::reload();
		Router::connect('/admin/:controller/:action/:id', array('admin' => true), array('id' => '[0-9]+'));
		Router::parse('/');
		$request = new Request();
		Router::setRequestInfo(
			$request->addParams(array(
				'plugin' => null, 'controller' => 'pages', 'action' => 'admin_edit', 'pass' => array('284'),
				'prefix' => 'admin', 'admin' => true,
				'url' => array('url' => 'admin/pages/edit/284')
			))->addPaths(array(
				'base' => '', 'here' => '/admin/pages/edit/284', 'webroot' => '/'
			))
		);

		$result = Router::url(array('plugin' => null, 'controller' => 'pages', 'action' => 'edit', 'id' => '284'));
		$expected = '/admin/pages/edit/284';
		$this->assertEquals($expected, $result);

		Router::reload();
		Router::connect('/admin/:controller/:action/*', array('admin' => true, 'prefix' => 'admin'));
		Router::parse('/');

		$request = new Request();
		Router::setRequestInfo(
			$request->addParams(array(
				'plugin' => null, 'controller' => 'pages', 'action' => 'admin_add', 'prefix' => 'admin',
				'admin' => true, 'url' => array('url' => 'admin/pages/add')
			))->addPaths(array(
				'base' => '', 'here' => '/admin/pages/add', 'webroot' => '/'
			))
		);

		$result = Router::url(array('plugin' => null, 'controller' => 'pages', 'action' => 'add', 'id' => false));
		$expected = '/admin/pages/add';
		$this->assertEquals($expected, $result);

		Router::reload();
		Router::connect('/admin/:controller/:action/*', array('admin' => true, 'prefix' => 'admin'));
		Router::parse('/');

		$request = new Request();
		Router::setRequestInfo(
			$request->addParams(array(
				'plugin' => null, 'controller' => 'pages', 'action' => 'admin_edit', 'prefix' => 'admin',
				'admin' => true, 'pass' => array('284'), 'url' => array('url' => 'admin/pages/edit/284')
			))->addPaths(array(
				'base' => '', 'here' => '/admin/pages/edit/284', 'webroot' => '/'
			))
		);

		$result = Router::url(array('plugin' => null, 'controller' => 'pages', 'action' => 'edit', 284));
		$expected = '/admin/pages/edit/284';
		$this->assertEquals($expected, $result);

		Router::reload();
		Router::connect('/admin/posts/*', array('controller' => 'posts', 'action' => 'index', 'admin' => true));
		Router::parse('/');
		Router::setRequestInfo(
			$request->addParams(array(
				'plugin' => null, 'controller' => 'posts', 'action' => 'admin_index', 'prefix' => 'admin',
				'admin' => true, 'pass' => array('284'), 'url' => array('url' => 'admin/posts')
			))->addPaths(array(
				'base' => '', 'here' => '/admin/posts', 'webroot' => '/'
			))
		);

		$result = Router::url(array('all'));
		$expected = '/admin/posts/all';
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
		Router::parse('/');

		$result = Router::url(array('plugin' => null, 'controller' => 'articles', 'action' => 'add', 'id' => null, 'ext' => 'json'));
		$expected = '/articles/add.json';
		$this->assertEquals($expected, $result);

		$result = Router::url(array('plugin' => null, 'controller' => 'articles', 'action' => 'add', 'ext' => 'json'));
		$expected = '/articles/add.json';
		$this->assertEquals($expected, $result);

		$result = Router::url(array('plugin' => null, 'controller' => 'articles', 'action' => 'index', 'id' => null, 'ext' => 'json'));
		$expected = '/articles.json';
		$this->assertEquals($expected, $result);

		$result = Router::url(array('plugin' => null, 'controller' => 'articles', 'action' => 'index', 'ext' => 'json'));
		$expected = '/articles.json';
		$this->assertEquals($expected, $result);
	}

/**
 * testPluginUrlGeneration method
 *
 * @return void
 */
	public function testUrlGenerationPlugins() {
		$request = new Request();
		Router::setRequestInfo(
			$request->addParams(array(
				'plugin' => 'test', 'controller' => 'controller', 'action' => 'index'
			))->addPaths(array(
				'base' => '/base', 'here' => '/clients/sage/portal/donations', 'webroot' => '/base/'
			))
		);

		$this->assertEquals(Router::url('read/1'), '/base/test/controller/read/1');

		Router::reload();
		Router::connect('/:lang/:plugin/:controller/*', array('action' => 'index'));

		$request = new Request();
		Router::setRequestInfo(
			$request->addParams(array(
				'lang' => 'en',
				'plugin' => 'shows', 'controller' => 'shows', 'action' => 'index',
				'url' => array('url' => 'en/shows/'),
			))->addPaths(array(
				'base' => '', 'here' => '/en/shows', 'webroot' => '/'
			))
		);
	}

/**
 * test that you can leave active plugin routes with plugin = null
 *
 * @return void
 */
	public function testCanLeavePlugin() {
		Router::connect('/admin/:controller', array('action' => 'index', 'admin' => true, 'prefix' => 'admin'));
		Router::connect('/admin/:controller/:action/*', array('admin' => true, 'prefix' => 'admin'));
		Router::connect(
			'/admin/other/:controller/:action/*',
			array(
				'admin' => true,
				'plugin' => 'aliased',
				'prefix' => 'admin'
			)
		);
		$request = new Request();
		Router::setRequestInfo(
			$request->addParams(array(
				'pass' => array(),
				'admin' => true,
				'prefix' => 'admin',
				'plugin' => 'this',
				'action' => 'admin_index',
				'controller' => 'interesting',
				'url' => array('url' => 'admin/this/interesting/index'),
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

		Router::connect('/posts/:value/:somevalue/:othervalue/*', array('controller' => 'posts', 'action' => 'view'), array('value','somevalue', 'othervalue'));
		$result = Router::parse('/posts/2007/08/01/title-of-post-here');
		$expected = array('value' => '2007', 'somevalue' => '08', 'othervalue' => '01', 'controller' => 'posts', 'action' => 'view', 'plugin' => '', 'pass' => array('0' => 'title-of-post-here'));
		$this->assertEquals($expected, $result);

		Router::reload();
		Router::connect('/posts/:year/:month/:day/*', array('controller' => 'posts', 'action' => 'view'), array('year' => $Year, 'month' => $Month, 'day' => $Day));
		$result = Router::parse('/posts/2007/08/01/title-of-post-here');
		$expected = array('year' => '2007', 'month' => '08', 'day' => '01', 'controller' => 'posts', 'action' => 'view', 'plugin' => '', 'pass' => array('0' => 'title-of-post-here'));
		$this->assertEquals($expected, $result);

		Router::reload();
		Router::connect('/posts/:day/:year/:month/*', array('controller' => 'posts', 'action' => 'view'), array('year' => $Year, 'month' => $Month, 'day' => $Day));
		$result = Router::parse('/posts/01/2007/08/title-of-post-here');
		$expected = array('day' => '01', 'year' => '2007', 'month' => '08', 'controller' => 'posts', 'action' => 'view', 'plugin' => '', 'pass' => array('0' => 'title-of-post-here'));
		$this->assertEquals($expected, $result);

		Router::reload();
		Router::connect('/posts/:month/:day/:year/*', array('controller' => 'posts', 'action' => 'view'), array('year' => $Year, 'month' => $Month, 'day' => $Day));
		$result = Router::parse('/posts/08/01/2007/title-of-post-here');
		$expected = array('month' => '08', 'day' => '01', 'year' => '2007', 'controller' => 'posts', 'action' => 'view', 'plugin' => '', 'pass' => array('0' => 'title-of-post-here'));
		$this->assertEquals($expected, $result);

		Router::reload();
		Router::connect('/posts/:year/:month/:day/*', array('controller' => 'posts', 'action' => 'view'));
		$result = Router::parse('/posts/2007/08/01/title-of-post-here');
		$expected = array('year' => '2007', 'month' => '08', 'day' => '01', 'controller' => 'posts', 'action' => 'view', 'plugin' => '', 'pass' => array('0' => 'title-of-post-here'));
		$this->assertEquals($expected, $result);

		Router::reload();
		require CAKE . 'Config' . DS . 'routes.php';
		$result = Router::parse('/pages/display/home');
		$expected = array('plugin' => null, 'pass' => array('home'), 'controller' => 'pages', 'action' => 'display');
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
		Router::connect('/:language/contact', array('language' => 'eng', 'plugin' => 'contact', 'controller' => 'contact', 'action' => 'index'), array('language' => '[a-z]{3}'));
		$result = Router::parse('/eng/contact');
		$expected = array('pass' => array(), 'language' => 'eng', 'plugin' => 'contact', 'controller' => 'contact', 'action' => 'index');
		$this->assertEquals($expected, $result);

		Router::reload();
		Router::connect('/forestillinger/:month/:year/*',
			array('plugin' => 'shows', 'controller' => 'shows', 'action' => 'calendar'),
			array('month' => '0[1-9]|1[012]', 'year' => '[12][0-9]{3}')
		);

		$result = Router::parse('/forestillinger/10/2007/min-forestilling');
		$expected = array('pass' => array('min-forestilling'), 'plugin' => 'shows', 'controller' => 'shows', 'action' => 'calendar', 'year' => 2007, 'month' => 10);
		$this->assertEquals($expected, $result);

		Router::reload();
		Router::connect('/:controller/:action/*');
		Router::connect('/', array('plugin' => 'pages', 'controller' => 'pages', 'action' => 'display'));
		$result = Router::parse('/');
		$expected = array('pass' => array(), 'controller' => 'pages', 'action' => 'display', 'plugin' => 'pages');
		$this->assertEquals($expected, $result);

		$result = Router::parse('/posts/edit/0');
		$expected = array('pass' => array(0), 'controller' => 'posts', 'action' => 'edit', 'plugin' => null);
		$this->assertEquals($expected, $result);

		Router::reload();
		Router::connect('/posts/:id::url_title', array('controller' => 'posts', 'action' => 'view'), array('pass' => array('id', 'url_title'), 'id' => '[\d]+'));
		$result = Router::parse('/posts/5:sample-post-title');
		$expected = array('pass' => array('5', 'sample-post-title'), 'id' => 5, 'url_title' => 'sample-post-title', 'plugin' => null, 'controller' => 'posts', 'action' => 'view');
		$this->assertEquals($expected, $result);

		Router::reload();
		Router::connect('/posts/:id::url_title/*', array('controller' => 'posts', 'action' => 'view'), array('pass' => array('id', 'url_title'), 'id' => '[\d]+'));
		$result = Router::parse('/posts/5:sample-post-title/other/params/4');
		$expected = array('pass' => array('5', 'sample-post-title', 'other', 'params', '4'), 'id' => 5, 'url_title' => 'sample-post-title', 'plugin' => null, 'controller' => 'posts', 'action' => 'view');
		$this->assertEquals($expected, $result);

		Router::reload();
		Router::connect('/posts/:url_title-(uuid::id)', array('controller' => 'posts', 'action' => 'view'), array('pass' => array('id', 'url_title'), 'id' => $UUID));
		$result = Router::parse('/posts/sample-post-title-(uuid:47fc97a9-019c-41d1-a058-1fa3cbdd56cb)');
		$expected = array('pass' => array('47fc97a9-019c-41d1-a058-1fa3cbdd56cb', 'sample-post-title'), 'id' => '47fc97a9-019c-41d1-a058-1fa3cbdd56cb', 'url_title' => 'sample-post-title', 'plugin' => null, 'controller' => 'posts', 'action' => 'view');
		$this->assertEquals($expected, $result);

		Router::reload();
		Router::connect('/posts/view/*', array('controller' => 'posts', 'action' => 'view'));
		$result = Router::parse('/posts/view/foo:bar/routing:fun');
		$expected = array('pass' => array('foo:bar', 'routing:fun'), 'plugin' => null, 'controller' => 'posts', 'action' => 'view');
		$this->assertEquals($expected, $result);
	}

/**
 * test that the persist key works.
 *
 * @return void
 */
	public function testPersistentParameters() {
		Router::reload();
		Router::connect('/:controller', array('action' => 'index'));
		Router::connect('/:controller/:action/*');
		Router::connect(
			'/:lang/:color/posts/view/*',
			array('controller' => 'posts', 'action' => 'view'),
			array('persist' => array('lang', 'color')
		));
		Router::connect(
			'/:lang/:color/posts/index',
			array('controller' => 'posts', 'action' => 'index'),
			array('persist' => array('lang')
		));
		Router::connect('/:lang/:color/posts/edit/*', array('controller' => 'posts', 'action' => 'edit'));
		Router::connect('/about', array('controller' => 'pages', 'action' => 'view', 'about'));

		Router::parse('/en/red/posts/view/5');

		$request = new Request();
		Router::setRequestInfo(
			$request->addParams(array(
				'lang' => 'en',
				'color' => 'red',
				'prefix' => 'admin',
				'plugin' => null,
				'action' => 'view',
				'controller' => 'posts',
			))->addPaths(array(
				'base' => '/',
				'here' => '/en/red/posts/view/5',
				'webroot' => '/',
			))
		);

		$expected = '/en/red/posts/view/6';
		$result = Router::url(array('controller' => 'posts', 'action' => 'view', 6));
		$this->assertEquals($expected, $result);

		$expected = '/en/blue/posts/index';
		$result = Router::url(array('controller' => 'posts', 'action' => 'index', 'color' => 'blue'));
		$this->assertEquals($expected, $result);

		$expected = '/posts/edit/6';
		$result = Router::url(array('controller' => 'posts', 'action' => 'edit', 6, 'color' => null, 'lang' => null));
		$this->assertEquals($expected, $result);

		$expected = '/posts';
		$result = Router::url(array('controller' => 'posts', 'action' => 'index'));
		$this->assertEquals($expected, $result);

		$expected = '/posts/edit/7';
		$result = Router::url(array('controller' => 'posts', 'action' => 'edit', 7));
		$this->assertEquals($expected, $result);

		$expected = '/about';
		$result = Router::url(array('controller' => 'pages', 'action' => 'view', 'about'));
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
		$expected = array('pass' => array(), 'category_id' => '4795d601-19c8-49a6-930e-06a8b01d17b7', 'plugin' => null, 'controller' => 'subjects', 'action' => 'add');
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
		$expected = array('pass' => array(), 'plugin' => null, 'controller' => 'pages', 'action' => 'view', 'slug' => 'this_is_the_slug', 'extra' => 'some_extra');
		$this->assertEquals($expected, $result);

		$result = Router::parse('/page/this_is_the_slug');
		$expected = array('pass' => array(), 'plugin' => null, 'controller' => 'pages', 'action' => 'view', 'slug' => 'this_is_the_slug', 'extra' => null);
		$this->assertEquals($expected, $result);

		Router::reload();
		Router::connect(
			"/:extra/page/:slug/*",
			array('controller' => 'pages', 'action' => 'view', 'extra' => null),
			array("extra" => '[a-z1-9_]*', "slug" => '[a-z1-9_]+')
		);
		Router::parse('/');

		$result = Router::url(array('admin' => null, 'plugin' => null, 'controller' => 'pages', 'action' => 'view', 'slug' => 'this_is_the_slug', 'extra' => null));
		$expected = '/page/this_is_the_slug';
		$this->assertEquals($expected, $result);

		$result = Router::url(array('admin' => null, 'plugin' => null, 'controller' => 'pages', 'action' => 'view', 'slug' => 'this_is_the_slug', 'extra' => 'some_extra'));
		$expected = '/some_extra/page/this_is_the_slug';
		$this->assertEquals($expected, $result);
	}

/**
 * Test that Routing.prefixes are used when a Router instance is created
 * or reset
 *
 * @return void
 */
	public function testRoutingPrefixesSetting() {
		$restore = Configure::read('Routing');

		Configure::write('Routing.prefixes', array('admin', 'member', 'super_user'));
		Router::reload();
		$result = Router::prefixes();
		$expected = array('admin', 'member', 'super_user');
		$this->assertEquals($expected, $result);

		Configure::write('Routing.prefixes', array('admin', 'member'));
		Router::reload();
		$result = Router::prefixes();
		$expected = array('admin', 'member');
		$this->assertEquals($expected, $result);

		Configure::write('Routing', $restore);
	}

/**
 * Test prefix routing and plugin combinations
 *
 * @return void
 */
	public function testPrefixRoutingAndPlugins() {
		Configure::write('Routing.prefixes', array('admin'));
		$paths = App::path('Plugin');
		App::build(array(
			'Plugin' =>  array(
				CAKE . 'Test' . DS . 'TestApp' . DS . 'Plugin' . DS
			)
		), App::RESET);
		Plugin::load(array('TestPlugin'));

		Router::reload();
		require CAKE . 'Config' . DS . 'routes.php';
		$request = new Request();
		Router::setRequestInfo(
			$request->addParams(array(
				'admin' => true, 'controller' => 'controller', 'action' => 'action',
				'plugin' => null, 'prefix' => 'admin'
			))->addPaths(array(
				'base' => '/',
				'here' => '/',
				'webroot' => '/base/',
			))
		);
		Router::parse('/');

		$result = Router::url(array('plugin' => 'test_plugin', 'controller' => 'test_plugin', 'action' => 'index'));
		$expected = '/admin/test_plugin';
		$this->assertEquals($expected, $result);

		Router::reload();
		require CAKE . 'Config' . DS . 'routes.php';
		$request = new Request();
		Router::setRequestInfo(
			$request->addParams(array(
				'plugin' => 'test_plugin', 'controller' => 'show_tickets', 'action' => 'admin_edit',
				'pass' => array('6'), 'prefix' => 'admin', 'admin' => true, 'form' => array(),
				'url' => array('url' => 'admin/shows/show_tickets/edit/6')
			))->addPaths(array(
				'base' => '/',
				'here' => '/admin/shows/show_tickets/edit/6',
				'webroot' => '/',
			))
		);

		$result = Router::url(array(
			'plugin' => 'test_plugin', 'controller' => 'show_tickets', 'action' => 'edit', 6,
			'admin' => true, 'prefix' => 'admin'
		));
		$expected = '/admin/test_plugin/show_tickets/edit/6';
		$this->assertEquals($expected, $result);

		$result = Router::url(array(
			'plugin' => 'test_plugin', 'controller' => 'show_tickets', 'action' => 'index', 'admin' => true
		));
		$expected = '/admin/test_plugin/show_tickets';
		$this->assertEquals($expected, $result);

		App::build(array('Plugin' => $paths));
	}

/**
 * testParseExtensions method
 *
 * @return void
 */
	public function testParseExtensions() {
		$this->assertEquals(array(), Router::extensions());

		Router::parseExtensions('rss');
		$this->assertEquals(array('rss'), Router::extensions());
	}

/**
 * testSetExtensions method
 *
 * @return void
 */
	public function testSetExtensions() {
		Router::setExtensions(array('rss'));
		$this->assertEquals(array('rss'), Router::extensions());

		require CAKE . 'Config' . DS . 'routes.php';
		$result = Router::parse('/posts.rss');
		$this->assertFalse(isset($result['ext']));

		Router::parseExtensions();
		$result = Router::parse('/posts.rss');
		$this->assertEquals('rss', $result['ext']);

		$result = Router::parse('/posts.xml');
		$this->assertFalse(isset($result['ext']));

		Router::setExtensions(array('xml'));
		$result = Router::extensions();
		$this->assertEquals(array('rss', 'xml'), $result);

		$result = Router::parse('/posts.xml');
		$this->assertEquals('xml', $result['ext']);

		$result = Router::setExtensions(array('pdf'), false);
		$this->assertEquals(array('pdf'), $result);
	}

/**
 * testExtensionParsing method
 *
 * @return void
 */
	public function testExtensionParsing() {
		Router::parseExtensions();
		require CAKE . 'Config' . DS . 'routes.php';

		$result = Router::parse('/posts.rss');
		$expected = array('plugin' => null, 'controller' => 'posts', 'action' => 'index', 'ext' => 'rss', 'pass' => array());
		$this->assertEquals($expected, $result);

		$result = Router::parse('/posts/view/1.rss');
		$expected = array('plugin' => null, 'controller' => 'posts', 'action' => 'view', 'pass' => array('1'), 'ext' => 'rss');
		$this->assertEquals($expected, $result);

		$result = Router::parse('/posts/view/1.rss?query=test');
		$this->assertEquals($expected, $result);

		$result = Router::parse('/posts/view/1.atom');
		$expected['ext'] = 'atom';
		$this->assertEquals($expected, $result);

		Router::reload();
		require CAKE . 'Config' . DS . 'routes.php';

		Router::parseExtensions('rss', 'xml');

		$result = Router::parse('/posts.xml');
		$expected = array('plugin' => null, 'controller' => 'posts', 'action' => 'index', 'ext' => 'xml', 'pass' => array());
		$this->assertEquals($expected, $result);

		$result = Router::parse('/posts.atom?hello=goodbye');
		$expected = array('plugin' => null, 'controller' => 'posts.atom', 'action' => 'index', 'pass' => array());
		$this->assertEquals($expected, $result);

		Router::reload();
		Router::connect('/controller/action', array('controller' => 'controller', 'action' => 'action', 'ext' => 'rss'));
		$result = Router::parse('/controller/action');
		$expected = array('controller' => 'controller', 'action' => 'action', 'plugin' => null, 'ext' => 'rss', 'pass' => array());
		$this->assertEquals($expected, $result);

		Router::reload();
		Router::parseExtensions('rss');
		Router::connect('/controller/action', array('controller' => 'controller', 'action' => 'action', 'ext' => 'rss'));
		$result = Router::parse('/controller/action');
		$expected = array('controller' => 'controller', 'action' => 'action', 'plugin' => null, 'ext' => 'rss', 'pass' => array());
		$this->assertEquals($expected, $result);
	}

/**
 * test newer style automatically generated prefix routes.
 *
 * @return void
 */
	public function testUrlGenerationWithAutoPrefixes() {
		Router::reload();
		Router::connect('/protected/:controller/:action/*', array('prefix' => 'protected', 'protected' => true));
		Router::connect('/admin/:controller/:action/*', array('prefix' => 'admin', 'admin' => true));
		Router::connect('/:controller/:action/*');

		Router::parse('/');

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

		$result = Router::url(array('controller' => 'images', 'action' => 'add', 'protected' => true));
		$expected = '/protected/images/add';
		$this->assertEquals($expected, $result);

		$result = Router::url(array('controller' => 'images', 'action' => 'add_protected_test', 'protected' => true));
		$expected = '/protected/images/add_protected_test';
		$this->assertEquals($expected, $result);

		$result = Router::url(array('action' => 'edit', 1));
		$expected = '/images/edit/1';
		$this->assertEquals($expected, $result);

		$result = Router::url(array('action' => 'edit', 1, 'protected' => true));
		$expected = '/protected/images/edit/1';
		$this->assertEquals($expected, $result);

		$result = Router::url(array('action' => 'protected_edit', 1, 'protected' => true));
		$expected = '/protected/images/edit/1';
		$this->assertEquals($expected, $result);

		$result = Router::url(array('action' => 'protectededit', 1, 'protected' => true));
		$expected = '/protected/images/protectededit/1';
		$this->assertEquals($expected, $result);

		$result = Router::url(array('action' => 'edit', 1, 'protected' => true));
		$expected = '/protected/images/edit/1';
		$this->assertEquals($expected, $result);

		$result = Router::url(array('controller' => 'others', 'action' => 'edit', 1));
		$expected = '/others/edit/1';
		$this->assertEquals($expected, $result);

		$result = Router::url(array('controller' => 'others', 'action' => 'edit', 1, 'protected' => true));
		$expected = '/protected/others/edit/1';
		$this->assertEquals($expected, $result);
	}

/**
 * test that auto-generated prefix routes persist
 *
 * @return void
 */
	public function testAutoPrefixRoutePersistence() {
		Router::reload();
		Router::connect('/protected/:controller/:action', array('prefix' => 'protected', 'protected' => true));
		Router::connect('/:controller/:action');
		Router::parse('/');

		$request = new Request();
		Router::setRequestInfo(
			$request->addParams(array(
				'plugin' => null, 'controller' => 'images', 'action' => 'index', 'prefix' => 'protected',
				'protected' => true, 'url' => array('url' => 'protected/images/index')
			))->addPaths(array(
				'base' => '',
				'here' => '/protected/images/index',
				'webroot' => '/',
			))
		);

		$result = Router::url(array('controller' => 'images', 'action' => 'add'));
		$expected = '/protected/images/add';
		$this->assertEquals($expected, $result);

		$result = Router::url(array('controller' => 'images', 'action' => 'add', 'protected' => false));
		$expected = '/images/add';
		$this->assertEquals($expected, $result);
	}

/**
 * test that setting a prefix override the current one
 *
 * @return void
 */
	public function testPrefixOverride() {
		Router::reload();
		Router::connect('/admin/:controller/:action', array('prefix' => 'admin', 'admin' => true));
		Router::connect('/protected/:controller/:action', array('prefix' => 'protected', 'protected' => true));
		Router::parse('/');

		$request = new Request();
		Router::setRequestInfo(
			$request->addParams(array(
				'plugin' => null, 'controller' => 'images', 'action' => 'index', 'prefix' => 'protected',
				'protected' => true, 'url' => array('url' => 'protected/images/index')
			))->addPaths(array(
				'base' => '',
				'here' => '/protected/images/index',
				'webroot' => '/',
			))
		);

		$result = Router::url(array('controller' => 'images', 'action' => 'add', 'admin' => true));
		$expected = '/admin/images/add';
		$this->assertEquals($expected, $result);

		$request = new Request();
		Router::setRequestInfo(
			$request->addParams(array(
				'plugin' => null, 'controller' => 'images', 'action' => 'index', 'prefix' => 'admin',
				'admin' => true, 'url' => array('url' => 'admin/images/index')
			))->addPaths(array(
				'base' => '',
				'here' => '/admin/images/index',
				'webroot' => '/',
			))
		);
		$result = Router::url(array('controller' => 'images', 'action' => 'add', 'protected' => true));
		$expected = '/protected/images/add';
		$this->assertEquals($expected, $result);
	}

/**
 * Test that setting a prefix to false is ignored, as its generally user error.
 *
 * @return void
 */
	public function testPrefixFalseIgnored() {
		Configure::write('Routing.prefixes', array('admin'));
		Router::reload();

		Router::connect('/cache_css/*', array('admin' => false, 'controller' => 'asset_compress', 'action' => 'get'));

		$url = Router::url(array('controller' => 'asset_compress', 'action' => 'get', 'test'));
		$expected = '/cache_css/test';
		$this->assertEquals($expected, $url);

		$url = Router::url(array('admin' => false, 'controller' => 'asset_compress', 'action' => 'get', 'test'));
		$expected = '/cache_css/test';
		$this->assertEquals($expected, $url);
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
				'bare' => 0, 'url' => array('url' => 'protected/images/index')
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
		require CAKE . 'Config' . DS . 'routes.php';
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
		Router::parseExtensions('json');

		$result = Router::parse('/posts/view/something.');
		$this->assertEquals('something.', $result['pass'][0], 'Period was chopped off %s');

		$result = Router::parse('/posts/view/something. . .');
		$this->assertEquals('something. . .', $result['pass'][0], 'Period was chopped off %s');
	}

/**
 * test that patterns work for :action
 *
 * @return void
 */
	public function testParsingWithPatternOnAction() {
		Router::reload();
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
			'pass' => array(),
		);
		$this->assertEquals($expected, $result);

		$result = Router::parse('/blog/foobar');
		$this->assertEquals(array(), $result);

		$result = Router::url(array('controller' => 'blog_posts', 'action' => 'foo'));
		$this->assertEquals('/', $result);

		$result = Router::url(array('controller' => 'blog_posts', 'action' => 'actions'));
		$this->assertEquals('/blog/actions', $result);
	}

/**
 * testParsingWithPrefixes method
 *
 * @return void
 */
	public function testParsingWithPrefixes() {
		$adminParams = array('prefix' => 'admin', 'admin' => true);
		Router::connect('/admin/:controller', $adminParams);
		Router::connect('/admin/:controller/:action', $adminParams);
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
		$expected = array('pass' => array(), 'prefix' => 'admin', 'plugin' => null, 'controller' => 'posts', 'action' => 'admin_index', 'admin' => true);
		$this->assertEquals($expected, $result);

		$result = Router::parse('/admin/posts');
		$this->assertEquals($expected, $result);

		$result = Router::url(array('admin' => true, 'controller' => 'posts'));
		$expected = '/base/admin/posts';
		$this->assertEquals($expected, $result);

		$result = Router::prefixes();
		$expected = array('admin');
		$this->assertEquals($expected, $result);

		Router::reload();

		$prefixParams = array('prefix' => 'members', 'members' => true);
		Router::connect('/members/:controller', $prefixParams);
		Router::connect('/members/:controller/:action', $prefixParams);
		Router::connect('/members/:controller/:action/*', $prefixParams);

		$request = new Request();
		Router::setRequestInfo(
			$request->addParams(array(
				'plugin' => null, 'controller' => 'controller', 'action' => 'index',
				'bare' => 0
			))->addPaths(array(
				'base' => '/base',
				'here' => '/',
				'webroot' => '/',
			))
		);

		$result = Router::parse('/members/posts/index');
		$expected = array('pass' => array(), 'prefix' => 'members', 'plugin' => null, 'controller' => 'posts', 'action' => 'members_index', 'members' => true);
		$this->assertEquals($expected, $result);

		$result = Router::url(array('members' => true, 'controller' => 'users', 'action' => 'add'));
		$expected = '/base/members/users/add';
		$this->assertEquals($expected, $result);
	}

/**
 * Tests URL generation with flags and prefixes in and out of context
 *
 * @return void
 */
	public function testUrlWritingWithPrefixes() {
		Router::connect('/company/:controller/:action/*', array('prefix' => 'company', 'company' => true));
		Router::connect('/:action', array('controller' => 'users'));

		$result = Router::url(array('controller' => 'users', 'action' => 'login', 'company' => true));
		$expected = '/company/users/login';
		$this->assertEquals($expected, $result);

		$result = Router::url(array('controller' => 'users', 'action' => 'company_login', 'company' => true));
		$expected = '/company/users/login';
		$this->assertEquals($expected, $result);

		$request = new Request();
		Router::setRequestInfo(
			$request->addParams(array(
				'plugin' => null, 'controller' => 'users', 'action' => 'login',
				'company' => true
			))->addPaths(array(
				'base' => '/',
				'here' => '/',
				'webroot' => '/base/',
			))
		);

		$result = Router::url(array('controller' => 'users', 'action' => 'login', 'company' => false));
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
			array('controller' => 'users', 'action' => 'login', 'prefix' => 'admin', 'admin' => true)
		);
		$request = new Request();
		Router::setRequestInfo(
			$request->addParams(array(
				'plugin' => null, 'controller' => 'posts', 'action' => 'index',
				'admin' => true, 'prefix' => 'admin'
			))->addPaths(array(
				'base' => '/',
				'here' => '/',
				'webroot' => '/',
			))
		);
		$result = Router::url(array('controller' => 'users', 'action' => 'login', 'admin' => true));
		$this->assertEquals('/admin/login', $result);

		$result = Router::url(array('controller' => 'users', 'action' => 'login'));
		$this->assertEquals('/admin/login', $result);

		$result = Router::url(array('controller' => 'users', 'action' => 'admin_login'));
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
		Router::parse('/');

		$result = Router::url(array('controller' => 'pages', 'action' => 'display', 1, 'whatever'));
		$expected = '/test/whatever';
		$this->assertEquals($expected, $result);

		$result = Router::url(array('controller' => 'pages', 'action' => 'display', 2, 'whatever'));
		$expected = '/test2/whatever';
		$this->assertEquals($expected, $result);

		$result = Router::url(array('controller' => 'pages', 'action' => 'display', 'home', 'whatever'));
		$expected = '/test-passed/whatever';
		$this->assertEquals($expected, $result);

		Configure::write('Routing.prefixes', array('admin'));
		Router::reload();

		$request = new Request();
		Router::setRequestInfo(
			$request->addParams(array(
				'plugin' => null, 'controller' => 'images', 'action' => 'index',
				'url' => array('url' => 'protected/images/index')
			))->addPaths(array(
				'base' => '',
				'here' => '/protected/images/index',
				'webroot' => '/',
			))
		);

		Router::connect('/protected/:controller/:action/*', array(
			'controller' => 'users',
			'action' => 'index',
			'prefix' => 'protected'
		));

		Router::parse('/');
		$result = Router::url(array('controller' => 'images', 'action' => 'add'));
		$expected = '/protected/images/add';
		$this->assertEquals($expected, $result);

		$result = Router::prefixes();
		$expected = array('admin', 'protected');
		$this->assertEquals($expected, $result);
	}

/**
 * testRegexRouteMatching method
 *
 * @return void
 */
	public function testRegexRouteMatching() {
		Router::connect('/:locale/:controller/:action/*', array(), array('locale' => 'dan|eng'));

		$result = Router::parse('/eng/test/test_action');
		$expected = array('pass' => array(), 'locale' => 'eng', 'controller' => 'test', 'action' => 'test_action', 'plugin' => null);
		$this->assertEquals($expected, $result);

		$result = Router::parse('/badness/test/test_action');
		$this->assertEquals(array(), $result);

		Router::reload();
		Router::connect('/:locale/:controller/:action/*', array(), array('locale' => 'dan|eng'));

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

		$result = Router::url(array('action' => 'test_another_action'));
		$expected = '/';
		$this->assertEquals($expected, $result);

		$result = Router::url(array('action' => 'test_another_action', 'locale' => 'eng'));
		$expected = '/eng/test/test_another_action';
		$this->assertEquals($expected, $result);
	}

/**
 * testStripPlugin
 *
 * @return void
 */
	public function testStripPlugin() {
		$pluginName = 'forums';
		$url = 'example.com/' . $pluginName . '/';
		$expected = 'example.com';

		$this->assertEquals($expected, Router::stripPlugin($url, $pluginName));
		$this->assertEquals(Router::stripPlugin($url), $url);
		$this->assertEquals(Router::stripPlugin($url, null), $url);
	}

/**
 * testCurrentRoute
 *
 * This test needs some improvement and actual requestAction() usage
 *
 * @return void
 */
	public function testCurrentRoute() {
		$this->markTestIncomplete('Fails due to changes for RouteCollection.');

		$url = array('controller' => 'pages', 'action' => 'display', 'government');
		Router::connect('/government', $url);
		Router::parse('/government');
		$route = Router::currentRoute();
		$this->assertEquals(array_merge($url, array('plugin' => null)), $route->defaults);
	}

/**
 * testRequestRoute
 *
 * @return void
 */
	public function testRequestRoute() {
		$this->markTestIncomplete('Fails due to changes for RouteCollection.');

		$url = array('controller' => 'products', 'action' => 'display', 5);
		Router::connect('/government', $url);
		Router::parse('/government');
		$route = Router::requestRoute();
		$this->assertEquals(array_merge($url, array('plugin' => null)), $route->defaults);

		// test that the first route is matched
		$newUrl = array('controller' => 'products', 'action' => 'display', 6);
		Router::connect('/government', $url);
		Router::parse('/government');
		$route = Router::requestRoute();
		$this->assertEquals(array_merge($url, array('plugin' => null)), $route->defaults);

		// test that an unmatched route does not change the current route
		$newUrl = array('controller' => 'products', 'action' => 'display', 6);
		Router::connect('/actor', $url);
		Router::parse('/government');
		$route = Router::requestRoute();
		$this->assertEquals(array_merge($url, array('plugin' => null)), $route->defaults);
	}

/**
 * test that connectDefaults() can disable default route connection
 *
 * @return void
 */
	public function testDefaultsMethod() {
		Router::connect('/test/*', array('controller' => 'pages', 'action' => 'display', 2));
		$result = Router::parse('/posts/edit/5');
		$this->assertFalse(isset($result['controller']));
		$this->assertFalse(isset($result['action']));
	}

/**
 * test that the required default routes are connected.
 *
 * @return void
 */
	public function testConnectDefaultRoutes() {
		App::build(array(
			'Plugin' =>  array(
				CAKE . 'Test' . DS . 'TestApp' . DS . 'Plugin' . DS
			)
		), App::RESET);
		Plugin::load(array('TestPlugin', 'PluginJs'));
		Router::reload();
		require CAKE . 'Config' . DS . 'routes.php';

		$result = Router::url(array('plugin' => 'plugin_js', 'controller' => 'js_file', 'action' => 'index'));
		$this->assertEquals('/plugin_js/js_file', $result);

		$result = Router::parse('/plugin_js/js_file');
		$expected = array(
			'plugin' => 'plugin_js', 'controller' => 'js_file', 'action' => 'index',
			'pass' => array()
		);
		$this->assertEquals($expected, $result);

		$result = Router::url(array('plugin' => 'test_plugin', 'controller' => 'test_plugin', 'action' => 'index'));
		$this->assertEquals('/test_plugin', $result);

		$result = Router::parse('/test_plugin');
		$expected = array(
			'plugin' => 'test_plugin', 'controller' => 'test_plugin', 'action' => 'index',
			'pass' => array()
		);

		$this->assertEquals($expected, $result, 'Plugin shortcut route broken. %s');
	}

/**
 * test using a custom route class for route connection
 *
 * @return void
 */
	public function testUsingCustomRouteClass() {
		$routes = $this->getMock('Cake\Routing\RouteCollection');
		$this->getMock('Cake\Routing\Route\Route', array(), array(), 'MockConnectedRoute', false);
		Router::setRouteCollection($routes);

		$routes->expects($this->once())
			->method('add')
			->with($this->isInstanceOf('\MockConnectedRoute'));

		Router::connect(
			'/:slug',
			array('controller' => 'posts', 'action' => 'view'),
			array('routeClass' => '\MockConnectedRoute', 'slug' => '[a-z_-]+')
		);
	}

/**
 * test that route classes must extend \Cake\Routing\Route\Route
 *
 * @expectedException Cake\Error\RouterException
 * @return void
 */
	public function testCustomRouteException() {
		Router::connect('/:controller', array(), array('routeClass' => 'Object'));
	}

/**
 * test reversing parameter arrays back into strings.
 *
 * @return void
 */
	public function testRouterReverse() {
		Router::connect('/:controller/:action/*');
		$params = array(
			'controller' => 'posts',
			'action' => 'view',
			'pass' => array(1),
			'url' => array(),
			'autoRender' => 1,
			'bare' => 1,
			'return' => 1,
			'requested' => 1,
			'_Token' => array('key' => 'sekret')
		);
		$result = Router::reverse($params);
		$this->assertEquals('/posts/view/1', $result);

		Router::connect('/:lang/:controller/:action/*', array(), array('lang' => '[a-z]{3}'));
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
			'pass' => array(1),
			'url' => array('url' => 'eng/posts/view/1', 'foo' => 'bar', 'baz' => 'quu'),
			'paging' => array(),
			'models' => array()
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
		Router::parseExtensions('json');

		$request = new Request('/posts/view/1.json');
		$request->addParams(array(
			'controller' => 'posts',
			'action' => 'view',
			'pass' => array(1),
			'ext' => 'json',
		));
		$request->query = array();
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
	}

/**
 * test that a route object returning a full url is not modified.
 *
 * @return void
 */
	public function testUrlFullUrlReturnFromRoute() {
		$url = 'http://example.com/posts/view/1';

		$routes = $this->getMock('Cake\Routing\RouteCollection');
		Router::setRouteCollection($routes);
		$routes->expects($this->any())->method('match')
			->will($this->returnValue($url));

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
		$expected = array('controller' => 'blog_posts', 'action' => 'other', 'pass' => array());
		$this->assertEquals($expected, $result);

		$result = $route->parse('/blog/foobar');
		$this->assertFalse($result);
	}

/**
 * Tests resourceMap as getter and setter.
 *
 * @return void
 */
	public function testResourceMap() {
		$default = Router::resourceMap();
		$expected = array(
			array('action' => 'index',	'method' => 'GET',		'id' => false),
			array('action' => 'view',	'method' => 'GET',		'id' => true),
			array('action' => 'add',	'method' => 'POST',		'id' => false),
			array('action' => 'edit',	'method' => 'PUT', 		'id' => true),
			array('action' => 'delete',	'method' => 'DELETE',	'id' => true),
			array('action' => 'edit',	'method' => 'POST', 	'id' => true)
		);
		$this->assertEquals($default, $expected);

		$custom = array(
			array('action' => 'index',	'method' => 'GET',		'id' => false),
			array('action' => 'view',	'method' => 'GET',		'id' => true),
			array('action' => 'add',	'method' => 'POST',		'id' => false),
			array('action' => 'edit',	'method' => 'PUT', 		'id' => true),
			array('action' => 'delete',	'method' => 'DELETE',	'id' => true),
			array('action' => 'update',	'method' => 'POST', 	'id' => true)
		);
		Router::resourceMap($custom);
		$this->assertEquals(Router::resourceMap(), $custom);

		Router::resourceMap($default);
	}

/**
 * test setting redirect routes
 *
 * @return void
 */
	public function testRouteRedirection() {
		$this->markTestIncomplete('Fails due to changes for RouteCollection.');
		Router::redirect('/blog', array('controller' => 'posts'), array('status' => 302));
		$this->assertEquals(1, count(Router::$routes));
		Router::$routes[0]->response = $this->getMock('Cake\Network\Response', array('_sendHeader'));
		Router::$routes[0]->stop = false;
		$this->assertEquals(302, Router::$routes[0]->options['status']);

		Router::parse('/blog');
		$header = Router::$routes[0]->response->header();
		$this->assertEquals(Router::url('/posts', true), $header['Location']);
		$this->assertEquals(302, Router::$routes[0]->response->statusCode());

		Router::$routes[0]->response = $this->getMock('Cake\Network\Response', array('_sendHeader'));
		Router::parse('/not-a-match');
		$this->assertEquals(array(), Router::$routes[0]->response->header());
	}

/**
 * Test setting the default route class
 *
 * @return void
 */
	public function testDefaultRouteClass() {
		$routes = $this->getMock('Cake\Routing\RouteCollection');
		$this->getMock('Cake\Routing\Route\Route', array(), array('/test'), 'TestDefaultRouteClass');

		$routes->expects($this->once())
			->method('add')
			->with($this->isInstanceOf('\TestDefaultRouteClass'));

		Router::setRouteCollection($routes);
		Router::defaultRouteClass('\TestDefaultRouteClass');

		Router::connect('/', array('controller' => 'pages', 'action' => 'display', 'home'));
	}

/**
 * Test getting the default route class
 *
 * @return void
 */
	public function testDefaultRouteClassGetter() {
		$routeClass = '\TestDefaultRouteClass';
		Router::defaultRouteClass($routeClass);

		$this->assertEquals($routeClass, Router::defaultRouteClass());
		$this->assertEquals($routeClass, Router::defaultRouteClass(null));
	}

/**
 * Test that route classes must extend Cake\Routing\Route\Route
 *
 * @expectedException Cake\Error\RouterException
 * @return void
 */
	public function testDefaultRouteException() {
		Router::defaultRouteClass('');
		Router::connect('/:controller', array());
	}

/**
 * Test that route classes must extend Cake\Routing\Route\Route
 *
 * @expectedException Cake\Error\RouterException
 * @return void
 */
	public function testSettingInvalidDefaultRouteException() {
		Router::defaultRouteClass('Object');
	}

/**
 * Test that class must exist
 *
 * @expectedException Cake\Error\RouterException
 * @return void
 */
	public function testSettingNonExistentDefaultRouteException() {
		Router::defaultRouteClass('NonExistentClass');
	}

/**
 * Tests generating well-formed querystrings
 *
 * @return void
 */
	public function testQueryString() {
		$result = Router::queryString(array('var' => 'foo bar'));
		$expected = '?var=foo+bar';
		$this->assertEquals($expected, $result);

		$result = Router::queryString(false, array('some' => 'param', 'foo' => 'bar'));
		$expected = '?some=param&foo=bar';
		$this->assertEquals($expected, $result);

		$existing = array('apple' => 'red', 'pear' => 'green');
		$result = Router::queryString($existing, array('some' => 'param', 'foo' => 'bar'));
		$expected = '?apple=red&pear=green&some=param&foo=bar';
		$this->assertEquals($expected, $result);

		$existing = 'apple=red&pear=green';
		$result = Router::queryString($existing, array('some' => 'param', 'foo' => 'bar'));
		$expected = '?apple=red&pear=green&some=param&foo=bar';
		$this->assertEquals($expected, $result);

		$existing = '?apple=red&pear=green';
		$result = Router::queryString($existing, array('some' => 'param', 'foo' => 'bar'));
		$expected = '?apple=red&pear=green&some=param&foo=bar';
		$this->assertEquals($expected, $result);

		$result = Router::queryString('apple=red&pear=green');
		$expected = '?apple=red&pear=green';
		$this->assertEquals($expected, $result);

		$result = Router::queryString('foo=bar', array('php' => 'nut', 'jose' => 'zap'), true);
		$expected = '?foo=bar&amp;php=nut&amp;jose=zap';
		$this->assertEquals($expected, $result);

		$result = Router::queryString('foo=bar&amp;', array('php' => 'nut', 'jose' => 'zap'), true);
		$expected = '?foo=bar&amp;php=nut&amp;jose=zap';
		$this->assertEquals($expected, $result);

		$result = Router::queryString('foo=bar&', array('php' => 'nut', 'jose' => 'zap'));
		$expected = '?foo=bar&php=nut&jose=zap';
		$this->assertEquals($expected, $result);
	}
}
