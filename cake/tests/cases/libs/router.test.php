<?php
/* SVN FILE: $Id$ */
/**
 * RouterTest file
 *
 * Long description for file
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) Tests <https://trac.cakephp.org/wiki/Developement/TestSuite>
 * Copyright 2005-2008, Cake Software Foundation, Inc. (http://www.cakefoundation.org)
 *
 *	Licensed under The Open Group Test Suite License
 *	Redistributions of files must retain the above copyright notice.
 *
 * @filesource
 * @copyright     Copyright 2005-2008, Cake Software Foundation, Inc. (http://www.cakefoundation.org)
 * @link          https://trac.cakephp.org/wiki/Developement/TestSuite CakePHP(tm) Tests
 * @package       cake
 * @subpackage    cake.tests.cases.libs
 * @since         CakePHP(tm) v 1.2.0.4206
 * @version       $Revision$
 * @modifiedby    $LastChangedBy$
 * @lastmodified  $Date$
 * @license       http://www.opensource.org/licenses/opengroup.php The Open Group Test Suite License
 */
App::import('Core', array('Router', 'Debugger'));

if (!defined('FULL_BASE_URL')) {
	define('FULL_BASE_URL', 'http://cakephp.org');
}
/**
 * RouterTest class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs
 */
class RouterTest extends CakeTestCase {
/**
 * setUp method
 *
 * @access public
 * @return void
 */
	function setUp() {
		Configure::write('Routing.admin', null);
		Router::reload();
		$this->router =& Router::getInstance();
	}
/**
 * testReturnedInstanceReference method
 *
 * @access public
 * @return void
 */
	function testReturnedInstanceReference() {
		$this->router->testVar = 'test';
		$this->assertIdentical($this->router, Router::getInstance());
		unset($this->router->testVar);
	}
/**
 * testFullBaseURL method
 *
 * @access public
 * @return void
 */
	function testFullBaseURL() {
		$this->assertPattern('/^http(s)?:\/\//', Router::url('/', true));
		$this->assertPattern('/^http(s)?:\/\//', Router::url(null, true));
	}
/**
 * testRouteWriting method
 *
 * @access public
 * @return void
 */
	function testRouteWriting() {
		Router::connect('/');
		Router::parse('/');
		$this->assertEqual($this->router->routes[0][0], '/');
		$this->assertEqual($this->router->routes[0][1], '/^[\/]*$/');
		$this->assertEqual($this->router->routes[0][2], array());

		Router::reload();
		Router::connect('/', array('controller' => 'testing'));
		Router::parse('/');
		$this->assertTrue(is_array($this->router->routes[0][3]) && !empty($this->router->routes[0][3]));
		$this->assertEqual($this->router->routes[0][3]['controller'], 'testing');
		$this->assertEqual($this->router->routes[0][3]['action'], 'index');
		$this->assertEqual(count($this->router->routes[0][3]), 3);

		$this->router->routes = array();
		Router::connect('/:controller', array('controller' => 'testing2'));
		Router::parse('/testing2');
		$this->assertTrue(is_array($this->router->routes[0][3]) && !empty($this->router->routes[0][3]), '/');
		$this->assertEqual($this->router->routes[0][3]['controller'], 'testing2');
		$this->assertEqual($this->router->routes[0][3]['action'], 'index');
		$this->assertEqual(count($this->router->routes[0][3]), 3);

		$this->router->routes = array();
		Router::connect('/:controller/:action', array('controller' => 'testing3'));
		Router::parse('/testing3/index');
		$this->assertEqual($this->router->routes[0][0], '/:controller/:action');
		$this->assertEqual($this->router->routes[0][1], '#^(?:/([^\/]+))?(?:/([^\/]+))?[\/]*$#');
		$this->assertEqual($this->router->routes[0][2], array('controller', 'action'));
		$this->assertEqual($this->router->routes[0][3], array('controller' => 'testing3', 'action' => 'index', 'plugin' => null));

		$this->router->routes = array();
		Router::connect('/:controller/:action/:id', array('controller' => 'testing4', 'id' => null), array('id' => $this->router->__named['ID']));
		Router::parse('/testing4/view/5');
		$this->assertEqual($this->router->routes[0][0], '/:controller/:action/:id');
		$this->assertEqual($this->router->routes[0][1], '#^(?:/([^\/]+))?(?:/([^\/]+))?(?:/([0-9]+)?)?[\/]*$#');
		$this->assertEqual($this->router->routes[0][2], array('controller', 'action', 'id'));

		$this->router->routes = array();
		Router::connect('/:controller/:action/:id', array('controller' => 'testing4'), array('id' => $this->router->__named['ID']));
		Router::parse('/testing4/view/5');
		$this->assertEqual($this->router->routes[0][1], '#^(?:/([^\/]+))?(?:/([^\/]+))?(?:/([0-9]+))[\/]*$#');

		$this->router->routes = array();
		Router::connect('/posts/foo:id');
		Router::parse('/posts/foo5');
		$this->assertEqual($this->router->routes[0][2], array('id'));
		$this->assertEqual($this->router->routes[0][1], '#^/posts(?:/foo([^\/]+))?[\/]*$#');

		foreach (array(':', '@', ';', '$', '-') as $delim) {
			$this->router->routes = array();
			Router::connect('/posts/:id'.$delim.':title');
			Router::parse('/posts/5' . $delim . 'foo');
			$this->assertEqual($this->router->routes[0][2], array('id', 'title'));
			$this->assertEqual($this->router->routes[0][1], '#^/posts(?:/([^\/]+))?(?:'.preg_quote($delim, '#').'([^\/]+))?[\/]*$#');
		}

		$this->router->routes = array();
		Router::connect('/posts/:id::title/:year');
		Router::parse('/posts/5:foo:2007');
		$this->assertEqual($this->router->routes[0][2], array('id', 'title', 'year'));
		$this->assertEqual($this->router->routes[0][1], '#^/posts(?:/([^\/]+))?(?:\\:([^\/]+))?(?:/([^\/]+))?[\/]*$#');
	}
/**
 * testRouteDefaultParams method
 *
 * @access public
 * @return void
 */
	function testRouteDefaultParams() {
		Router::connect('/:controller', array('controller' => 'posts'));
		$this->assertEqual(Router::url(array('action' => 'index')), '/');
	}
/**
 * testRouterIdentity method
 *
 * @access public
 * @return void
 */
	function testRouterIdentity() {
		$router2 = new Router();
		$this->assertEqual(get_object_vars($this->router), get_object_vars($router2));
	}
/**
 * testResourceRoutes method
 *
 * @access public
 * @return void
 */
	function testResourceRoutes() {
		Router::mapResources('Posts');

		$_SERVER['REQUEST_METHOD'] = 'GET';
		$result = Router::parse('/posts');
		$this->assertEqual($result, array('pass' => array(), 'named' => array(), 'plugin' => '', 'controller' => 'posts', 'action' => 'index', '[method]' => 'GET'));
		$this->assertEqual($this->router->__resourceMapped, array('posts'));

		$_SERVER['REQUEST_METHOD'] = 'GET';
		$result = Router::parse('/posts/13');
		$this->assertEqual($result, array('pass' => array('13'), 'named' => array(), 'plugin' => '', 'controller' => 'posts', 'action' => 'view', 'id' => '13', '[method]' => 'GET'));
		$this->assertEqual($this->router->__resourceMapped, array('posts'));

		$_SERVER['REQUEST_METHOD'] = 'POST';
		$result = Router::parse('/posts');
		$this->assertEqual($result, array('pass' => array(), 'named' => array(), 'plugin' => '', 'controller' => 'posts', 'action' => 'add', '[method]' => 'POST'));
		$this->assertEqual($this->router->__resourceMapped, array('posts'));

		$_SERVER['REQUEST_METHOD'] = 'PUT';
		$result = Router::parse('/posts/13');
		$this->assertEqual($result, array('pass' => array('13'), 'named' => array(), 'plugin' => '', 'controller' => 'posts', 'action' => 'edit', 'id' => '13', '[method]' => 'PUT'));
		$this->assertEqual($this->router->__resourceMapped, array('posts'));

		$result = Router::parse('/posts/475acc39-a328-44d3-95fb-015000000000');
		$this->assertEqual($result, array('pass' => array('475acc39-a328-44d3-95fb-015000000000'), 'named' => array(), 'plugin' => '', 'controller' => 'posts', 'action' => 'edit', 'id' => '475acc39-a328-44d3-95fb-015000000000', '[method]' => 'PUT'));
		$this->assertEqual($this->router->__resourceMapped, array('posts'));

		$_SERVER['REQUEST_METHOD'] = 'DELETE';
		$result = Router::parse('/posts/13');
		$this->assertEqual($result, array('pass' => array('13'), 'named' => array(), 'plugin' => '', 'controller' => 'posts', 'action' => 'delete', 'id' => '13', '[method]' => 'DELETE'));
		$this->assertEqual($this->router->__resourceMapped, array('posts'));

		$_SERVER['REQUEST_METHOD'] = 'GET';
		$result = Router::parse('/posts/add');
		$this->assertEqual($result, array('pass' => array(), 'named' => array(), 'plugin' => '', 'controller' => 'posts', 'action' => 'add'));
		$this->assertEqual($this->router->__resourceMapped, array('posts'));

		Router::reload();
		Router::mapResources('Posts', array('id' => '[a-z0-9_]+'));

		$_SERVER['REQUEST_METHOD'] = 'GET';
		$result = Router::parse('/posts/add');
		$this->assertEqual($result, array('pass' => array('add'), 'named' => array(), 'plugin' => '', 'controller' => 'posts', 'action' => 'view', 'id' => 'add', '[method]' => 'GET'));
		$this->assertEqual($this->router->__resourceMapped, array('posts'));

		$_SERVER['REQUEST_METHOD'] = 'PUT';
		$result = Router::parse('/posts/name');
		$this->assertEqual($result, array('pass' => array('name'), 'named' => array(), 'plugin' => '', 'controller' => 'posts', 'action' => 'edit', 'id' => 'name', '[method]' => 'PUT'));
		$this->assertEqual($this->router->__resourceMapped, array('posts'));
	}
/**
 * testMultipleResourceRoute method
 *
 * @access public
 * @return void
 */
	function testMultipleResourceRoute() {
		Router::connect('/:controller', array('action' => 'index', '[method]' => array('GET', 'POST')));

		$_SERVER['REQUEST_METHOD'] = 'GET';
		$result = Router::parse('/posts');
		$this->assertEqual($result, array('pass' => array(), 'named' => array(), 'plugin' => '', 'controller' => 'posts', 'action' => 'index', '[method]' => array('GET', 'POST')));

		$_SERVER['REQUEST_METHOD'] = 'POST';
		$result = Router::parse('/posts');
		$this->assertEqual($result, array('pass' => array(), 'named' => array(), 'plugin' => '', 'controller' => 'posts', 'action' => 'index', '[method]' => array('GET', 'POST')));
	}
/**
 * testGenerateUrlResourceRoute method
 *
 * @access public
 * @return void
 */
	function testGenerateUrlResourceRoute() {
		Router::mapResources('Posts');

		$result = Router::url(array('controller' => 'posts', 'action' => 'index', '[method]' => 'GET'));
		$expected = '/posts';
		$this->assertEqual($result, $expected);

		$result = Router::url(array('controller' => 'posts', 'action' => 'view', '[method]' => 'GET', 'id' => 10));
		$expected = '/posts/10';
		$this->assertEqual($result, $expected);

		$result = Router::url(array('controller' => 'posts', 'action' => 'add', '[method]' => 'POST'));
		$expected = '/posts';
		$this->assertEqual($result, $expected);

		$result = Router::url(array('controller' => 'posts', 'action' => 'edit', '[method]' => 'PUT', 'id' => 10));
		$expected = '/posts/10';
		$this->assertEqual($result, $expected);

		$result = Router::url(array('controller' => 'posts', 'action' => 'delete', '[method]' => 'DELETE', 'id' => 10));
		$expected = '/posts/10';
		$this->assertEqual($result, $expected);

		$result = Router::url(array('controller' => 'posts', 'action' => 'edit', '[method]' => 'POST', 'id' => 10));
		$expected = '/posts/10';
		$this->assertEqual($result, $expected);
	}
/**
 * testUrlNormalization method
 *
 * @access public
 * @return void
 */
	function testUrlNormalization() {
		$expected = '/users/logout';

		$result = Router::normalize('/users/logout/');
		$this->assertEqual($result, $expected);

		$result = Router::normalize('//users//logout//');
		$this->assertEqual($result, $expected);

		$result = Router::normalize('users/logout');
		$this->assertEqual($result, $expected);

		$result = Router::normalize(array('controller' => 'users', 'action' => 'logout'));
		$this->assertEqual($result, $expected);

		$result = Router::normalize('/');
		$this->assertEqual($result, '/');

		$result = Router::normalize('http://google.com/');
		$this->assertEqual($result, 'http://google.com/');

		$result = Router::normalize('http://google.com//');
		$this->assertEqual($result, 'http://google.com//');

		$result = Router::normalize('/users/login/scope://foo');
		$this->assertEqual($result, '/users/login/scope:/foo');

		$result = Router::normalize('/recipe/recipes/add');
		$this->assertEqual($result, '/recipe/recipes/add');

		Router::setRequestInfo(array(array(), array('base' => '/us')));
		$result = Router::normalize('/us/users/logout/');
		$this->assertEqual($result, '/users/logout');

		Router::reload();

		Router::setRequestInfo(array(array(), array('base' => '/cake_12')));
		$result = Router::normalize('/cake_12/users/logout/');
		$this->assertEqual($result, '/users/logout');

		Router::reload();
		$_back = Configure::read('App.baseUrl');
		Configure::write('App.baseUrl', '/');
		
		Router::setRequestInfo(array(array(), array('base' => '/')));
		$result = Router::normalize('users/login');
		$this->assertEqual($result, '/users/login');
		Configure::write('App.baseUrl', $_back);
		
		Router::reload();
		Router::setRequestInfo(array(array(), array('base' => 'beer')));
		$result = Router::normalize('beer/admin/beers_tags/add');
		$this->assertEqual($result, '/admin/beers_tags/add');

		$result = Router::normalize('/admin/beers_tags/add');
		$this->assertEqual($result, '/admin/beers_tags/add');
	}
/**
 * testUrlGeneration method
 *
 * @access public
 * @return void
 */
	function testUrlGeneration() {
		extract(Router::getNamedExpressions());

		Router::setRequestInfo(array(
			array(
				'pass' => array(), 'action' => 'index', 'plugin' => null, 'controller' => 'subscribe',
				'admin' => true, 'url' => array('url' => '')
			),
			array(
				'base' => '/magazine', 'here' => '/magazine',
				'webroot' => '/magazine/', 'passedArgs' => array('page' => 2), 'namedArgs' => array('page' => 2),
			)
		));
		$result = Router::url();
		$this->assertEqual('/magazine', $result);

		Router::reload();

		Router::connect('/', array('controller' => 'pages', 'action' => 'display', 'home'));
		$out = Router::url(array('controller' => 'pages', 'action' => 'display', 'home'));
		$this->assertEqual($out, '/');

		Router::connect('/pages/*', array('controller' => 'pages', 'action' => 'display'));
		$result = Router::url(array('controller' => 'pages', 'action' => 'display', 'about'));
		$expected = '/pages/about';
		$this->assertEqual($result, $expected);

		Router::reload();
		Router::parse('/');

		Router::connect('/:plugin/:id/*', array('controller' => 'posts', 'action' => 'view'), array('id' => $ID));
		$result = Router::url(array('plugin' => 'cake_plugin', 'controller' => 'posts', 'action' => 'view', 'id' => '1'));
		$expected = '/cake_plugin/1';
		$this->assertEqual($result, $expected);

		$result = Router::url(array('plugin' => 'cake_plugin', 'controller' => 'posts', 'action' => 'view', 'id' => '1', '0'));
		$expected = '/cake_plugin/1/0';
		$this->assertEqual($result, $expected);

		Router::reload();
		Router::parse('/');

		Router::connect('/:controller/:action/:id', array(), array('id' => $ID));
		$result = Router::url(array('controller' => 'posts', 'action' => 'view', 'id' => '1'));
		$expected = '/posts/view/1';
		$this->assertEqual($result, $expected);

		Router::reload();
		Router::parse('/');

		Router::connect('/:controller/:id', array('action' => 'view', 'id' => '1'));
		$result = Router::url(array('controller' => 'posts', 'action' => 'view', 'id' => '1'));
		$expected = '/posts/1';
		$this->assertEqual($result, $expected);

		$result = Router::url(array('controller' => 'posts', 'action' => 'index', '0'));
		$expected = '/posts/index/0';
		$this->assertEqual($result, $expected);

		$result = Router::url(array('controller' => 'posts', 'action'=>'index', '0', '?' => 'var=test&var2=test2'));
		$expected = '/posts/index/0?var=test&var2=test2';
		$this->assertEqual($result, $expected);

		$result = Router::url(array('controller' => 'posts', '0', '?' => 'var=test&var2=test2'));
		$this->assertEqual($result, $expected);

		$result = Router::url(array('controller' => 'posts', '0', '?' => array('var' => 'test', 'var2' => 'test2')));
		$this->assertEqual($result, $expected);

		$result = Router::url(array('controller' => 'posts', '0', '?' => array('var' => null)));
		$this->assertEqual($result, '/posts/index/0');

		$result = Router::url(array('controller' => 'posts', '0', '?' => 'var=test&var2=test2', '#' => 'unencoded string %'));
		$expected = '/posts/index/0?var=test&var2=test2#unencoded+string+%25';
		$this->assertEqual($result, $expected);

		Router::connect('/view/*',	array('controller' => 'posts', 'action' => 'view'));
		Router::promote();
		$result = Router::url(array('controller' => 'posts', 'action' => 'view', '1'));
		$expected = '/view/1';
		$this->assertEqual($result, $expected);

		Configure::write('Routing.admin', 'admin');
		Router::reload();
		Router::setRequestInfo(array(
			array(
				'pass' => array(), 'action' => 'admin_index', 'plugin' => null, 'controller' => 'subscriptions',
				'admin' => true, 'url' => array('url' => 'admin/subscriptions/index/page:2'),
			),
			array(
				'base' => '/magazine', 'here' => '/magazine/admin/subscriptions/index/page:2',
				'webroot' => '/magazine/', 'passedArgs' => array('page' => 2),
			)
		));
		Router::parse('/');

		$result = Router::url(array('page' => 3));
		$expected = '/magazine/admin/subscriptions/index/page:3';
		$this->assertEqual($result, $expected);

		Configure::write('Routing.admin', 'admin');
		Router::reload();
		Router::connect('/admin/subscriptions/:action/*', array('controller' => 'subscribe', 'admin' => true, 'prefix' => 'admin'));
		Router::setRequestInfo(array(
			array(
				'pass' => array(), 'action' => 'admin_index', 'plugin' => null, 'controller' => 'subscribe',
				'admin' => true, 'url' => array('url' => 'admin/subscriptions/edit/1')
			),
			array(
				'base' => '/magazine', 'here' => '/magazine/admin/subscriptions/edit/1',
				'webroot' => '/magazine/', 'passedArgs' => array('page' => 2), 'namedArgs' => array('page' => 2),
			)
		));
		Router::parse('/');

		$result = Router::url(array('action' => 'edit', 1));
		$expected = '/magazine/admin/subscriptions/edit/1';
		$this->assertEqual($result, $expected);

		Router::reload();
		Router::setRequestInfo(array(
			array('pass' => array(), 'action' => 'index', 'plugin' => null, 'controller' => 'real_controller_name', 'url' => array('url' => '')),
			array(
				'base' => '/', 'here' => '/',
				'webroot' => '/', 'passedArgs' => array('page' => 2), 'namedArgs' => array('page' => 2),
			)
		));
		Router::connect('short_controller_name/:action/*', array('controller' => 'real_controller_name'));
		Router::parse('/');

		$result = Router::url(array('controller' => 'real_controller_name', 'page' => '1'));
		$expected = '/short_controller_name/index/page:1';
		$this->assertEqual($result, $expected);

		$result = Router::url(array('action' => 'add'));
		$expected = '/short_controller_name/add';
		$this->assertEqual($result, $expected);

		Router::reload();

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
		$this->assertEqual($result, $expected);

		$result = Router::url(array('admin' => false, 'language' => 'eng', 'action' => 'index', 'controller' => 'galleries'));
		$expected = '/eng/galleries';
		$this->assertEqual($result, $expected);

		Router::reload();
		Router::connect('/:language/pages',
			array('controller' => 'pages', 'action' => 'index'),
			array('language' => '[a-z]{3}')
		);

		Router::connect('/:language/:controller/:action/*', array(), array('language' => '[a-z]{3}'));

		$result = Router::url(array('language' => 'eng', 'action' => 'index', 'controller' => 'pages'));
		$expected = '/eng/pages';
		$this->assertEqual($result, $expected);

		$result = Router::url(array('language' => 'eng', 'controller' => 'pages'));
		$this->assertEqual($result, $expected);

		$result = Router::url(array('language' => 'eng', 'controller' => 'pages', 'action' => 'add'));
		$expected = '/eng/pages/add';
		$this->assertEqual($result, $expected);

		Router::reload();
		Router::parse('/');
		Router::setRequestInfo(array(
			array('pass' => array(), 'action' => 'index', 'plugin' => null, 'controller' => 'users', 'url' => array('url' => 'users')),
			array(
				'base' => '/', 'here' => '/',
				'webroot' => '/', 'passedArgs' => array(), 'argSeparator' => ':', 'namedArgs' => array(),
			)
		));

		$result = Router::url(array('action' => 'login'));
		$expected = '/users/login';
		$this->assertEqual($result, $expected);

		Router::reload();
		Router::parse('/');
		Router::connect('/page/*', array('plugin' => null, 'controller' => 'pages', 'action' => 'view'));

		$result = Router::url(array('plugin' => 'my_plugin', 'controller' => 'pages', 'action' => 'view', 'my-page'));
		$expected = '/my_plugin/pages/view/my-page';
		$this->assertEqual($result, $expected);

		Router::reload();
		Router::parse('/');
		Router::connect('/forestillinger/:month/:year/*',
			array('plugin' => 'shows', 'controller' => 'shows', 'action' => 'calendar'),
			array('month' => '0[1-9]|1[012]', 'year' => '[12][0-9]{3}')
		);

		$result = Router::url(array('plugin' => 'shows', 'controller' => 'shows', 'action' => 'calendar', 'month' => 10, 'year' => 2007, 'min-forestilling'));
		$expected = '/forestillinger/10/2007/min-forestilling';
		$this->assertEqual($result, $expected);

		Router::reload();
		Router::parse('/');

		Router::connect('/contact/:action', array('plugin' => 'contact', 'controller' => 'contact'));
		$result = Router::url(array('plugin' => 'contact', 'controller' => 'contact', 'action' => 'me'));

		$expected = '/contact/me';
		$this->assertEqual($result, $expected);

		Configure::write('Routing.admin', 'admin');
		Router::reload();
		Router::parse('/');

		$result = Router::url(array('admin' => true, 'controller' => 'users', 'action' => 'login'));
		$expected = '/admin/users/login';
		$this->assertEqual($result, $expected);

		Router::reload();
		Router::parse('/');

		Router::connect('/kalender/:month/:year/*',
			array('plugin' => 'shows', 'controller' => 'shows', 'action' => 'calendar'),
			array('month' => '0[1-9]|1[012]', 'year' => '[12][0-9]{3}')
		);

		Router::connect('/kalender/*', array('plugin' => 'shows', 'controller' => 'shows', 'action' => 'calendar'));

		$result = Router::url(array('plugin' => 'shows', 'controller' => 'shows', 'action' => 'calendar', 'min-forestilling'));
		$expected = '/kalender/min-forestilling';
		$this->assertEqual($result, $expected);

		$result = Router::url(array('plugin' => 'shows', 'controller' => 'shows', 'action' => 'calendar', 'year' => 2007, 'month' => 10, 'min-forestilling'));
		$expected = '/kalender/10/2007/min-forestilling';
		$this->assertEqual($result, $expected);

		Configure::write('Routing.admin', 'admin');
		Router::reload();

		Router::setRequestInfo(array(
			array('pass' => array(), 'admin' => true, 'action' => 'index', 'plugin' => null, 'controller' => 'users', 'url' => array('url' => 'users')),
			array(
				'base' => '/', 'here' => '/',
				'webroot' => '/', 'passedArgs' => array(), 'argSeparator' => ':', 'namedArgs' => array(),
			)
		));

		Router::connect('/page/*', array('controller' => 'pages', 'action' => 'view', 'admin' => true, 'prefix' => 'admin'));
		Router::parse('/');

		$result = Router::url(array('admin' => true, 'controller' => 'pages', 'action' => 'view', 'my-page'));
		$expected = '/page/my-page';
		$this->assertEqual($result, $expected);

		Router::reload();

		Router::setRequestInfo(array(
			array(
				'pass' => array(), 'action' => 'index', 'plugin' => 'myplugin', 'controller' => 'mycontroller',
				'admin' => false, 'url' => array('url' => array())
			),
			array(
				'base' => '/', 'here' => '/',
				'webroot' => '/', 'passedArgs' => array(), 'namedArgs' => array(),
			)
		));

		$result = Router::url(array('plugin' => null, 'controller' => 'myothercontroller'));
		$expected = '/myothercontroller/';
		$this->assertEqual($result, $expected);

		Configure::write('Routing.admin', 'admin');
		Router::reload();

		Router::setRequestInfo(array(
			array('plugin' => null, 'controller' => 'pages', 'action' => 'admin_add', 'pass' => array(), 'prefix' => 'admin', 'admin' => true, 'form' => array(), 'url' => array('url' => 'admin/pages/add')),
			array('plugin' => null, 'controller' => null, 'action' => null, 'base' => '', 'here' => '/admin/pages/add', 'webroot' => '/')
		));
		Router::parse('/');

		$result = Router::url(array('plugin' => null, 'controller' => 'pages', 'action' => 'add', 'id' => false));
		$expected = '/admin/pages/add';
		$this->assertEqual($result, $expected);

		Router::reload();

		Router::setRequestInfo(array(
			array ('plugin' => null, 'controller' => 'pages', 'action' => 'admin_edit', 'pass' => array('284'), 'prefix' => 'admin', 'admin' => true, 'form' => array(), 'url' => array('url' => 'admin/pages/edit/284')),
			array ('plugin' => null, 'controller' => null, 'action' => null, 'base' => '', 'here' => '/admin/pages/edit/284', 'webroot' => '/')
		));

		Router::connect('/admin/:controller/:action/:id', array('admin' => true), array('id' => '[0-9]+'));
		Router::parse('/');

		$result = Router::url(array('plugin' => null, 'controller' => 'pages', 'action' => 'edit', 'id' => '284'));
		$expected = '/admin/pages/edit/284';
		$this->assertEqual($result, $expected);

		Configure::write('Routing.admin', 'admin');
		Router::reload();
		Router::setRequestInfo(array(
			array ('plugin' => null, 'controller' => 'pages', 'action' => 'admin_add', 'pass' => array(), 'prefix' => 'admin', 'admin' => true, 'form' => array(), 'url' => array('url' => 'admin/pages/add')),
			array ('plugin' => null, 'controller' => null, 'action' => null, 'base' => '', 'here' => '/admin/pages/add', 'webroot' => '/')
		));

		Router::parse('/');

		$result = Router::url(array('plugin' => null, 'controller' => 'pages', 'action' => 'add', 'id' => false));
		$expected = '/admin/pages/add';
		$this->assertEqual($result, $expected);


		Router::reload();
		Router::setRequestInfo(array(
			array('plugin' => null, 'controller' => 'pages', 'action' => 'admin_edit', 'pass' => array('284'), 'prefix' => 'admin', 'admin' => true, 'form' => array(), 'url' => array('url' => 'admin/pages/edit/284')),
			array('plugin' => null, 'controller' => null, 'action' => null, 'base' => '', 'here' => '/admin/pages/edit/284', 'webroot' => '/')
		));

		Router::parse('/');

		$result = Router::url(array('plugin' => null, 'controller' => 'pages', 'action' => 'edit', 'id' => '284'));
		$expected = '/admin/pages/edit/284';
		$this->assertEqual($result, $expected);

		Router::reload();
		Router::setRequestInfo(array(
			array(
				'plugin' => 'shows', 'controller' => 'show_tickets', 'action' => 'admin_edit',
				'pass' => array('6'), 'prefix' => 'admin', 'admin' => true, 'form' => array(),
				'url' => array('url' => 'admin/shows/show_tickets/edit/6')
			),
			array(
				'plugin' => null, 'controller' => null, 'action' => null, 'base' => '',
				'here' => '/admin/shows/show_tickets/edit/6', 'webroot' => '/'
			)
		));

		Router::parse('/');

		$result = Router::url(array(
			'plugin' => 'shows', 'controller' => 'show_tickets', 'action' => 'edit', 'id' => '6',
			'admin' => true, 'prefix' => 'admin'
		));
		$expected = '/admin/shows/show_tickets/edit/6';
		$this->assertEqual($result, $expected);

		Router::reload();

		Router::setRequestInfo(array(
			array('pass' => array(), 'action' => 'admin_index', 'plugin' => null, 'controller' => 'posts', 'prefix' => 'admin', 'admin' => true, 'url' => array('url' => 'admin/posts')),
			array('base' => '', 'here' => '/admin/posts', 'webroot' => '/')
		));

		Router::connect('/admin/posts/*', array('controller' => 'posts', 'action' => 'index', 'admin' => true));
		Router::parse('/');

		$result = Router::url(array('all'));
		$expected = '/admin/posts/all';
		$this->assertEqual($result, $expected);
	}
/**
 * testUrlGenerationWithPrefix method
 *
 * @access public
 * @return void
 */
	function testUrlGenerationWithPrefix() {
		Configure::write('Routing.admin', 'admin');
		Router::reload();

		Router::connectNamed(array('event', 'lang'));
		Router::connect('/', array('controller' => 'pages', 'action' => 'display', 'home'));
		Router::connect('/pages/contact_us', array('controller' => 'pages', 'action' => 'contact_us'));
		Router::connect('/pages/*', array('controller' => 'pages', 'action' => 'display'));
		Router::connect('/reset/*', array('admin' => true, 'controller' => 'users', 'action' => 'reset'));
		Router::connect('/tests', array('controller' => 'tests', 'action' => 'index'));
		Router::parseExtensions('rss');

		Router::setRequestInfo(array(
			array('pass' => array(), 'named' => array(), 'controller' => 'registrations', 'action' => 'admin_index', 'plugin' => '', 'prefix' => 'admin', 'admin' => true, 'url' => array('ext' => 'html', 'url' => 'admin/registrations/index'), 'form' => array()),
			array('base' => '', 'here' => '/admin/registrations/index', 'webroot' => '/')
		));

		$result = Router::url(array('page' => 2));
		$expected = '/admin/registrations/index/page:2';
		$this->assertEqual($result, $expected);
	}
/**
 * testUrlGenerationWithExtensions method
 *
 * @access public
 * @return void
 */
	function testUrlGenerationWithExtensions() {
		Router::parse('/');
		$result = Router::url(array('plugin' => null, 'controller' => 'articles', 'action' => 'add', 'id' => null, 'ext' => 'json'));
		$expected = '/articles/add.json';
		$this->assertEqual($result, $expected);

		$result = Router::url(array('plugin' => null, 'controller' => 'articles', 'action' => 'add', 'ext' => 'json'));
		$expected = '/articles/add.json';
		$this->assertEqual($result, $expected);

		$result = Router::url(array('plugin' => null, 'controller' => 'articles', 'action' => 'index', 'id' => null, 'ext' => 'json'));
		$expected = '/articles.json';
		$this->assertEqual($result, $expected);

		$result = Router::url(array('plugin' => null, 'controller' => 'articles', 'action' => 'index', 'ext' => 'json'));
		$expected = '/articles.json';
		$this->assertEqual($result, $expected);
	}
/**
 * testPluginUrlGeneration method
 *
 * @access public
 * @return void
 */
	function testPluginUrlGeneration() {
		Router::setRequestInfo(array(
			array(
				'controller' => 'controller', 'action' => 'index', 'form' => array(),
				'url' => array(), 'plugin' => 'test'
			),
			array(
				'base' => '/base', 'here' => '/clients/sage/portal/donations', 'webroot' => '/base/',
				'passedArgs' => array(), 'argSeparator' => ':', 'namedArgs' => array()
			)
		));

		$this->assertEqual(Router::url('read/1'), '/base/test/controller/read/1');

		Router::reload();

		Router::connect('/:lang/:plugin/:controller/*', array(), array('action' => 'index'));

		Router::setRequestInfo(array(
				array(
					'lang' => 'en',
					'plugin' => 'shows', 'controller' => 'shows', 'action' => 'index', 'pass' =>
						array(), 'form' => array(), 'url' =>
						array('url' => 'en/shows/')),
				array('plugin' => NULL, 'controller' => NULL, 'action' => NULL, 'base' => '',
				'here' => '/en/shows/', 'webroot' => '/')));

		Router::parse('/en/shows/');

		$result = Router::url(array(
			'lang' => 'en',
			'controller' => 'shows', 'action' => 'index', 'page' => '1',
		));
		$expected = '/en/shows/page:1';
		$this->assertEqual($result, $expected);
	}
/**
 * testUrlParsing method
 *
 * @access public
 * @return void
 */
	function testUrlParsing() {
		extract(Router::getNamedExpressions());

		Router::connect('/posts/:value/:somevalue/:othervalue/*', array('controller' => 'posts', 'action' => 'view'), array('value','somevalue', 'othervalue'));
		$result = Router::parse('/posts/2007/08/01/title-of-post-here');
		$expected = array('value' => '2007', 'somevalue' => '08', 'othervalue' => '01', 'controller' => 'posts', 'action' => 'view', 'plugin' =>'', 'pass' => array('0' => 'title-of-post-here'), 'named' => array());
		$this->assertEqual($result, $expected);

		$this->router->routes = array();
		Router::connect('/posts/:year/:month/:day/*', array('controller' => 'posts', 'action' => 'view'), array('year' => $Year, 'month' => $Month, 'day' => $Day));
		$result = Router::parse('/posts/2007/08/01/title-of-post-here');
		$expected = array('year' => '2007', 'month' => '08', 'day' => '01', 'controller' => 'posts', 'action' => 'view', 'plugin' =>'', 'pass' => array('0' => 'title-of-post-here'), 'named' => array());
		$this->assertEqual($result, $expected);

		$this->router->routes = array();
		Router::connect('/posts/:day/:year/:month/*', array('controller' => 'posts', 'action' => 'view'), array('year' => $Year, 'month' => $Month, 'day' => $Day));
		$result = Router::parse('/posts/01/2007/08/title-of-post-here');
		$expected = array('day' => '01', 'year' => '2007', 'month' => '08', 'controller' => 'posts', 'action' => 'view', 'plugin' =>'', 'pass' => array('0' => 'title-of-post-here'), 'named' => array());
		$this->assertEqual($result, $expected);

		$this->router->routes = array();
		Router::connect('/posts/:month/:day/:year//*', array('controller' => 'posts', 'action' => 'view'), array('year' => $Year, 'month' => $Month, 'day' => $Day));
		$result = Router::parse('/posts/08/01/2007/title-of-post-here');
		$expected = array('month' => '08', 'day' => '01', 'year' => '2007', 'controller' => 'posts', 'action' => 'view', 'plugin' =>'', 'pass' => array('0' => 'title-of-post-here'), 'named' => array());
		$this->assertEqual($result, $expected);

		$this->router->routes = array();
		Router::connect('/posts/:year/:month/:day/*', array('controller' => 'posts', 'action' => 'view'));
		$result = Router::parse('/posts/2007/08/01/title-of-post-here');
		$expected = array('year' => '2007', 'month' => '08', 'day' => '01', 'controller' => 'posts', 'action' => 'view', 'plugin' =>'', 'pass' => array('0' => 'title-of-post-here'), 'named' => array());
		$this->assertEqual($result, $expected);

		Router::reload();
		$result = Router::parse('/pages/display/home');
		$expected = array('plugin' => null, 'pass' => array('home'), 'controller' => 'pages', 'action' => 'display', 'named' => array());
		$this->assertEqual($result, $expected);

		$result = Router::parse('pages/display/home/');
		$this->assertEqual($result, $expected);

		$result = Router::parse('pages/display/home');
		$this->assertEqual($result, $expected);

		Router::reload();
		Router::connect('/page/*', array('controller' => 'test'));
		$result = Router::parse('/page/my-page');
		$expected = array('pass' => array('my-page'), 'plugin' => null, 'controller' => 'test', 'action' => 'index');

		Router::reload();
		Router::connect('/:language/contact', array('language' => 'eng', 'plugin' => 'contact', 'controller' => 'contact', 'action' => 'index'), array('language' => '[a-z]{3}'));
		$result = Router::parse('/eng/contact');
		$expected = array('pass' => array(), 'named' => array(), 'language' => 'eng', 'plugin' => 'contact', 'controller' => 'contact', 'action' => 'index');
		$this->assertEqual($result, $expected);

		Router::reload();
		Router::connect('/forestillinger/:month/:year/*',
			array('plugin' => 'shows', 'controller' => 'shows', 'action' => 'calendar'),
			array('month' => '0[1-9]|1[012]', 'year' => '[12][0-9]{3}')
		);

		$result = Router::parse('/forestillinger/10/2007/min-forestilling');
		$expected = array('pass' => array('min-forestilling'), 'plugin' => 'shows', 'controller' => 'shows', 'action' => 'calendar', 'year' => 2007, 'month' => 10, 'named' => array());
		$this->assertEqual($result, $expected);

		Router::reload();
		Router::connect('/:controller/:action/*', array(), array('controller' => 'some_controller'));
		Router::connect('/', array('plugin' => 'pages', 'controller' => 'pages', 'action' => 'display'));
		$result = Router::parse('/');
		$expected = array('pass' => array(), 'named' => array(), 'controller' => 'pages', 'action' => 'display', 'plugin' => 'pages');
		$this->assertEqual($result, $expected);

		$result = Router::parse('/posts/edit/0');
		$expected = array('pass' => array(0), 'named' => array(), 'controller' => 'posts', 'action' => 'edit', 'plugin' => null);
		$this->assertEqual($result, $expected);

		Router::reload();
		Router::connect('/posts/:id::url_title', array('controller' => 'posts', 'action' => 'view'), array('pass' => array('id', 'url_title'), 'id' => '[\d]+'));
		$result = Router::parse('/posts/5:sample-post-title');
		$expected = array('pass' => array('5', 'sample-post-title'), 'named' => array(), 'id' => 5, 'url_title' => 'sample-post-title', 'plugin' => null, 'controller' => 'posts', 'action' => 'view');
		$this->assertEqual($result, $expected);

		Router::reload();
		Router::connect('/posts/:id::url_title/*', array('controller' => 'posts', 'action' => 'view'), array('pass' => array('id', 'url_title'), 'id' => '[\d]+'));
		$result = Router::parse('/posts/5:sample-post-title/other/params/4');
		$expected = array('pass' => array('5', 'sample-post-title', 'other', 'params', '4'), 'named' => array(), 'id' => 5, 'url_title' => 'sample-post-title', 'plugin' => null, 'controller' => 'posts', 'action' => 'view');
		$this->assertEqual($result, $expected);

		Router::reload();
		Router::connect('/posts/:url_title-(uuid::id)', array('controller' => 'posts', 'action' => 'view'), array('pass' => array('id', 'url_title'), 'id' => $UUID));
		$result = Router::parse('/posts/sample-post-title-(uuid:47fc97a9-019c-41d1-a058-1fa3cbdd56cb)');
		$expected = array('pass' => array('47fc97a9-019c-41d1-a058-1fa3cbdd56cb', 'sample-post-title'), 'named' => array(), 'id' => '47fc97a9-019c-41d1-a058-1fa3cbdd56cb', 'url_title' => 'sample-post-title', 'plugin' => null, 'controller' => 'posts', 'action' => 'view');
		$this->assertEqual($result, $expected);

		Router::reload();
		Router::connect('/posts/view/*', array('controller' => 'posts', 'action' => 'view'), array('named' => false));
		$result = Router::parse('/posts/view/foo:bar/routing:fun');
		$expected = array('pass' => array('foo:bar', 'routing:fun'), 'named' => array(), 'plugin' => null, 'controller' => 'posts', 'action' => 'view');
		$this->assertEqual($result, $expected);

		Router::reload();
		Router::connect('/posts/view/*', array('controller' => 'posts', 'action' => 'view'), array('named' => array('foo', 'answer')));
		$result = Router::parse('/posts/view/foo:bar/routing:fun/answer:42');
		$expected = array('pass' => array('routing:fun'), 'named' => array('foo' => 'bar', 'answer' => '42'), 'plugin' => null, 'controller' => 'posts', 'action' => 'view');
		$this->assertEqual($result, $expected);

		Router::reload();
		Router::connect('/posts/view/*', array('controller' => 'posts', 'action' => 'view'), array('named' => array('foo', 'answer'), 'greedy' => true));
		$result = Router::parse('/posts/view/foo:bar/routing:fun/answer:42');
		$expected = array('pass' => array(), 'named' => array('foo' => 'bar', 'routing' => 'fun', 'answer' => '42'), 'plugin' => null, 'controller' => 'posts', 'action' => 'view');
		$this->assertEqual($result, $expected);

		Router::reload();
		Router::connect('/:lang/:color/posts/view/*', array('controller' => 'posts', 'action' => 'view'), array('persist' => array('lang', 'color')));
		Router::connect('/:lang/:color/posts/index', array('controller' => 'posts', 'action' => 'index'), array('persist' => array('lang')));
		Router::connect('/:lang/:color/posts/edit/*', array('controller' => 'posts', 'action' => 'index'));
		Router::connect('/about', array('controller' => 'pages', 'action' => 'view', 'about'));
		Router::parse('/en/red/posts/view/5');
		Router::setRequestInfo(array(
			array('controller' => 'posts', 'action' => 'view', 'lang' => 'en', 'color' => 'red', 'form' => array(), 'url' => array(), 'plugin' => null),
			array('base' => '/', 'here' => '/en/red/posts/view/5', 'webroot' => '/', 'passedArgs' => array(), 'argSeparator' => ':', 'namedArgs' => array())
		));
		$expected = '/en/red/posts/view/6';
		$result = Router::url(array('controller' => 'posts', 'action' => 'view', 6));
		$this->assertEqual($result, $expected);

		$expected = '/en/blue/posts/index';
		$result = Router::url(array('controller' => 'posts', 'action' => 'index', 'color' => 'blue'));
		$this->assertEqual($result, $expected);

		$expected = '/posts';
		$result = Router::url(array('controller' => 'posts', 'action' => 'index'));
		$this->assertEqual($result, $expected);

		$expected = '/posts/edit/7';
		$result = Router::url(array('controller' => 'posts', 'action' => 'edit', 7));
		$this->assertEqual($result, $expected);

		$expected = '/about';
		$result = Router::url(array('controller' => 'pages', 'action' => 'view', 'about'));
		$this->assertEqual($result, $expected);
	}
/**
 * testUuidRoutes method
 *
 * @access public
 * @return void
 */
	function testUuidRoutes() {
		Router::connect(
			'/subjects/add/:category_id',
			array('controller' => 'subjects', 'action' => 'add'),
			array('category_id' => '\w{8}-\w{4}-\w{4}-\w{4}-\w{12}')
		);
		$result = Router::parse('/subjects/add/4795d601-19c8-49a6-930e-06a8b01d17b7');
		$expected = array('pass' => array(), 'named' => array(), 'category_id' => '4795d601-19c8-49a6-930e-06a8b01d17b7', 'plugin' => null, 'controller' => 'subjects', 'action' => 'add');
		$this->assertEqual($result, $expected);
	}
/**
 * testRouteSymmetry method
 *
 * @access public
 * @return void
 */
	function testRouteSymmetry() {
		Router::connect(
			"/:extra/page/:slug/*",
			array('controller' => 'pages', 'action' => 'view', 'extra' => null),
			array("extra" => '[a-z1-9_]*', "slug" => '[a-z1-9_]+', "action" => 'view')
		);

		$result = Router::parse('/some_extra/page/this_is_the_slug');
		$expected = array('pass' => array(), 'named' => array(), 'plugin' => null, 'controller' => 'pages', 'action' => 'view', 'slug' => 'this_is_the_slug', 'extra' => 'some_extra');
		$this->assertEqual($result, $expected);

		$result = Router::parse('/page/this_is_the_slug');
		$expected = array( 'pass' => array(), 'named' => array(), 'plugin' => null, 'controller' => 'pages', 'action' => 'view', 'slug' => 'this_is_the_slug', 'extra' => null);
		$this->assertEqual($result, $expected);

		Router::reload();

		Router::connect(
			"/:extra/page/:slug/*",
			array('controller' => 'pages', 'action' => 'view', 'extra' => null),
			array("extra" => '[a-z1-9_]*', "slug" => '[a-z1-9_]+')
		);
		Router::parse('/');

		$result = Router::url(array('admin' => null, 'plugin' => null, 'controller' => 'pages', 'action' => 'view', 'slug' => 'this_is_the_slug', 'extra' => null));
		$expected = '/page/this_is_the_slug';
		$this->assertEqual($result, $expected);

		$result = Router::url(array('admin' => null, 'plugin' => null, 'controller' => 'pages', 'action' => 'view', 'slug' => 'this_is_the_slug', 'extra' => 'some_extra'));
		$expected = '/some_extra/page/this_is_the_slug';
		$this->assertEqual($result, $expected);
	}
/**
 * testAdminRouting method
 *
 * @access public
 * @return void
 */
	function testAdminRouting() {
		Configure::write('Routing.admin', 'admin');
		Router::reload();
		Router::parse('/');

		Router::reload();
		Router::connect('/admin', array('admin' => true, 'controller' => 'users'));
		$result = Router::parse('/admin');
		$expected = array('pass' => array(), 'named' => array(), 'plugin' => '', 'controller' => 'users', 'action' => 'index', 'admin' => true, 'prefix' => 'admin');
		$this->assertEqual($result, $expected);


		$result = Router::url(array('admin' => true, 'controller' => 'posts', 'action' => 'index', '0', '?' => 'var=test&var2=test2'));
		$expected = '/admin/posts/index/0?var=test&var2=test2';
		$this->assertEqual($result, $expected);

		Router::reload();
		Router::parse('/');
		$result = Router::url(array('admin' => false, 'controller' => 'posts', 'action' => 'index', '0', '?' => 'var=test&var2=test2'));
		$expected = '/posts/index/0?var=test&var2=test2';
		$this->assertEqual($result, $expected);

		Router::reload();
		Router::setRequestInfo(array(
			array('admin' => true, 'controller' => 'controller', 'action' => 'index', 'form' => array(), 'url' => array(), 'plugin' => null),
			array('base' => '/', 'here' => '/', 'webroot' => '/base/', 'passedArgs' => array(), 'argSeparator' => ':', 'namedArgs' => array())
		));

		Router::parse('/');
		$result = Router::url(array('admin' => false, 'controller' => 'posts', 'action' => 'index', '0', '?' => 'var=test&var2=test2'));
		$expected = '/posts/index/0?var=test&var2=test2';
		$this->assertEqual($result, $expected);

		$result = Router::url(array('controller' => 'posts', 'action' => 'index', '0', '?' => 'var=test&var2=test2'));
		$expected = '/admin/posts/index/0?var=test&var2=test2';
		$this->assertEqual($result, $expected);

		Router::reload();
		$result = Router::parse('admin/users/view/');
		$expected = array('pass' => array(), 'named' => array(), 'controller' => 'users', 'action' => 'view', 'plugin' => null, 'prefix' => 'admin', 'admin' => true);
		$this->assertEqual($result, $expected);

		Configure::write('Routing.admin', 'beheer');

		Router::reload();
		Router::setRequestInfo(array(
			array('beheer' => true, 'controller' => 'posts', 'action' => 'index', 'form' => array(), 'url' => array(), 'plugin' => null),
			array('base' => '/', 'here' => '/beheer/posts/index', 'webroot' => '/', 'passedArgs' => array(), 'argSeparator' => ':', 'namedArgs' => array())
		));

		$result = Router::parse('beheer/users/view/');
		$expected = array('pass' => array(), 'named' => array(), 'controller' => 'users', 'action' => 'view', 'plugin' => null, 'prefix' => 'beheer', 'beheer' => true);
		$this->assertEqual($result, $expected);


		$result = Router::url(array('controller' => 'posts', 'action' => 'index', '0', '?' => 'var=test&var2=test2'));
		$expected = '/beheer/posts/index/0?var=test&var2=test2';
		$this->assertEqual($result, $expected);

		Configure::write('Routing.admin', 'admin');
		$paths = Configure::read('pluginPaths');
		Configure::write('pluginPaths', array(
			TEST_CAKE_CORE_INCLUDE_PATH . 'tests' . DS . 'test_app' . DS . 'plugins' . DS
		));
		Configure::write('__objects.plugin', array('test_plugin'));

		Router::reload();
		Router::setRequestInfo(array(
			array('admin' => true, 'controller' => 'controller', 'action' => 'action', 
				'form' => array(), 'url' => array(), 'plugin' => null),
			array('base' => '/', 'here' => '/', 'webroot' => '/base/', 'passedArgs' => array(), 
				'argSeparator' => ':', 'namedArgs' => array())
		));
		Router::parse('/');

		$result = Router::url(array('plugin' => 'test_plugin', 'controller' => 'test_plugin', 'action' => 'index'));
		$expected = '/admin/test_plugin';
		$this->assertEqual($result, $expected);

		Configure::write('pluginPaths', $paths);
	}
/**
 * testExtensionParsingSetting method
 *
 * @access public
 * @return void
 */
	function testExtensionParsingSetting() {
		$router = Router::getInstance();
		$this->assertFalse($this->router->__parseExtensions);

		$router->parseExtensions();
		$this->assertTrue($this->router->__parseExtensions);
	}
/**
 * testExtensionParsing method
 *
 * @access public
 * @return void
 */
	function testExtensionParsing() {
		Router::parseExtensions();

		$result = Router::parse('/posts.rss');
		$expected = array('plugin' => null, 'controller' => 'posts', 'action' => 'index', 'url' => array('ext' => 'rss'), 'pass'=> array(), 'named' => array());
		$this->assertEqual($result, $expected);

		$result = Router::parse('/posts/view/1.rss');
		$expected = array('plugin' => null, 'controller' => 'posts', 'action' => 'view', 'pass' => array('1'), 'named' => array(), 'url' => array('ext' => 'rss'), 'named' => array());
		$this->assertEqual($result, $expected);

		$result = Router::parse('/posts/view/1.rss?query=test');
		$this->assertEqual($result, $expected);

		$result = Router::parse('/posts/view/1.atom');
		$expected['url'] = array('ext' => 'atom');
		$this->assertEqual($result, $expected);

		Router::reload();
		Router::parseExtensions('rss', 'xml');

		$result = Router::parse('/posts.xml');
		$expected = array('plugin' => null, 'controller' => 'posts', 'action' => 'index', 'url' => array('ext' => 'xml'), 'pass'=> array(), 'named' => array());
		$this->assertEqual($result, $expected);

		$result = Router::parse('/posts.atom?hello=goodbye');
		$expected = array('plugin' => null, 'controller' => 'posts.atom', 'action' => 'index', 'pass' => array(), 'named' => array(), 'url' => array('ext' => 'html'));
		$this->assertEqual($result, $expected);

		Router::reload();
		Router::parseExtensions();
		$result = $this->router->__parseExtension('/posts.atom');
		$expected = array('ext' => 'atom', 'url' => '/posts');
		$this->assertEqual($result, $expected);

		Router::reload();
		Router::connect('/controller/action', array('controller' => 'controller', 'action' => 'action', 'url' => array('ext' => 'rss')));
		$result = Router::parse('/controller/action');
		$expected = array('controller' => 'controller', 'action' => 'action', 'plugin' => null, 'url' => array('ext' => 'rss'), 'named' => array(), 'pass' => array());
		$this->assertEqual($result, $expected);
	}
/**
 * testQuerystringGeneration method
 *
 * @access public
 * @return void
 */
	function testQuerystringGeneration() {
		$result = Router::url(array('controller' => 'posts', 'action'=>'index', '0', '?' => 'var=test&var2=test2'));
		$expected = '/posts/index/0?var=test&var2=test2';
		$this->assertEqual($result, $expected);

		$result = Router::url(array('controller' => 'posts', 'action'=>'index', '0', '?' => array('var' => 'test', 'var2' => 'test2')));
		$this->assertEqual($result, $expected);

		$expected .= '&more=test+data';
		$result = Router::url(array('controller' => 'posts', 'action'=>'index', '0', '?' => array('var' => 'test', 'var2' => 'test2', 'more' => 'test data')));
		$this->assertEqual($result, $expected);

// Test bug #4614
		$restore = ini_get('arg_separator.output');
		ini_set('arg_separator.output', '&amp;');
		$result = Router::url(array('controller' => 'posts', 'action'=>'index', '0', '?' => array('var' => 'test', 'var2' => 'test2', 'more' => 'test data')));
		$this->assertEqual($result, $expected);
		ini_set('arg_separator.output', $restore);

		$result = Router::url(array('controller' => 'posts', 'action'=>'index', '0', '?' => array('var' => 'test', 'var2' => 'test2')), array('escape' => true));
		$expected = '/posts/index/0?var=test&amp;var2=test2';
		$this->assertEqual($result, $expected);
	}
/**
 * testConnectNamed method
 *
 * @access public
 * @return void
 */
	function testConnectNamed() {
		$named = Router::connectNamed(false, array('default' => true));
		$this->assertFalse($named['greedy']);
		$this->assertEqual(array_keys($named['rules']), $named['default']);

		Router::reload();
		Router::connect('/foo/*', array('controller' => 'bar', 'action' => 'fubar'));
		Router::connectNamed(array(), array('argSeparator' => '='));
		$result = Router::parse('/foo/param1=value1/param2=value2');
		$expected = array('pass' => array(), 'named' => array('param1' => 'value1', 'param2' => 'value2'), 'controller' => 'bar', 'action' => 'fubar', 'plugin' => null);
		$this->assertEqual($result, $expected);

		Router::reload();
		Router::connect('/controller/action/*', array('controller' => 'controller', 'action' => 'action'), array('named' => array('param1' => 'value[\d]')));
		Router::connectNamed(array(), array('greedy' => false, 'argSeparator' => '='));
		$result = Router::parse('/controller/action/param1=value1/param2=value2');
		$expected = array('pass' => array('param2=value2'), 'named' => array('param1' => 'value1'), 'controller' => 'controller', 'action' => 'action', 'plugin' => null);
		$this->assertEqual($result, $expected);

		Router::reload();
		Router::connect('/:controller/:action/*');
		Router::connectNamed(array('page'), array('default' => false, 'greedy' => false));
		$result = Router::parse('/categories/index?limit=5');
		$this->assertTrue(empty($result['named']));
	}
/**
 * testNamedArgsUrlGeneration method
 *
 * @access public
 * @return void
 */
	function testNamedArgsUrlGeneration() {
		$result = Router::url(array('controller' => 'posts', 'action' => 'index', 'published' => 1, 'deleted' => 1));
		$expected = '/posts/index/published:1/deleted:1';
		$this->assertEqual($result, $expected);

		$result = Router::url(array('controller' => 'posts', 'action' => 'index', 'published' => 0, 'deleted' => 0));
		$expected = '/posts/index/published:0/deleted:0';
		$this->assertEqual($result, $expected);

		Router::reload();
		extract(Router::getNamedExpressions());
		Router::connectNamed(array('file'=> '[\w\.\-]+\.(html|png)'));
		Router::connect('/', array('controller' => 'graphs', 'action' => 'index'));
		Router::connect('/:id/*', array('controller' => 'graphs', 'action' => 'view'), array('id' => $ID));

		$result = Router::url(array('controller' => 'graphs', 'action' => 'view', 'id' => 12, 'file' => 'asdf.png'));
		$expected = '/12/file:asdf.png';
		$this->assertEqual($result, $expected);

		$result = Router::url(array('controller' => 'graphs', 'action' => 'view', 'id' => 12, 'file' => 'asdf.foo'));
		$expected = '/graphs/view/12/file:asdf.foo';
		$this->assertEqual($result, $expected);

		Configure::write('Routing.admin', 'admin');

		Router::reload();
		Router::setRequestInfo(array(
			array('admin' => true, 'controller' => 'controller', 'action' => 'index', 'form' => array(), 'url' => array(), 'plugin' => null),
			array('base' => '/', 'here' => '/', 'webroot' => '/base/', 'passedArgs' => array(), 'argSeparator' => ':', 'namedArgs' => array())
		));
		Router::parse('/');

		$result = Router::url(array('page' => 1, 0 => null, 'sort' => 'controller', 'direction' => 'asc', 'order' => null));
		$expected = "/admin/controller/index/page:1/sort:controller/direction:asc";
		$this->assertEqual($result, $expected);

		Router::reload();
		Router::setRequestInfo(array(
			array('admin' => true, 'controller' => 'controller', 'action' => 'index', 'form' => array(), 'url' => array(), 'plugin' => null),
			array('base' => '/', 'here' => '/', 'webroot' => '/base/', 'passedArgs' => array('type'=> 'whatever'), 'argSeparator' => ':', 'namedArgs' => array('type'=> 'whatever'))
		));

		$result = Router::parse('/admin/controller/index/type:whatever');
		$result = Router::url(array('type'=> 'new'));
		$expected = "/admin/controller/index/type:new";
		$this->assertEqual($result, $expected);
	}
/**
 * testNamedArgsUrlParsing method
 *
 * @access public
 * @return void
 */
	function testNamedArgsUrlParsing() {
		$Router =& Router::getInstance();
		Router::reload();
		$result = Router::parse('/controller/action/param1:value1:1/param2:value2:3/param:value');
		$expected = array('pass' => array(), 'named' => array('param1' => 'value1:1', 'param2' => 'value2:3', 'param' => 'value'), 'controller' => 'controller', 'action' => 'action', 'plugin' => null);
		$this->assertEqual($result, $expected);

		Router::reload();
		$result = Router::connectNamed(false);
		$this->assertEqual(array_keys($result['rules']), array());
		$this->assertFalse($result['greedy']);
		$result = Router::parse('/controller/action/param1:value1:1/param2:value2:3/param:value');
		$expected = array('pass' => array('param1:value1:1', 'param2:value2:3', 'param:value'), 'named' => array(), 'controller' => 'controller', 'action' => 'action', 'plugin' => null);
		$this->assertEqual($result, $expected);

		Router::reload();
		$result = Router::connectNamed(true);
		$this->assertEqual(array_keys($result['rules']), $Router->named['default']);
		$this->assertTrue($result['greedy']);
		Router::reload();
		Router::connectNamed(array('param1' => 'not-matching'));
		$result = Router::parse('/controller/action/param1:value1:1/param2:value2:3/param:value');
		$expected = array('pass' => array('param1:value1:1'), 'named' => array('param2' => 'value2:3', 'param' => 'value'), 'controller' => 'controller', 'action' => 'action', 'plugin' => null);
		$this->assertEqual($result, $expected);

		Router::reload();
		Router::connect('/foo/:action/*', array('controller' => 'bar'), array('named' => array('param1' => array('action' => 'index')), 'greedy' => true));
		$result = Router::parse('/foo/index/param1:value1:1/param2:value2:3/param:value');
		$expected = array('pass' => array(), 'named' => array('param1' => 'value1:1', 'param2' => 'value2:3', 'param' => 'value'), 'controller' => 'bar', 'action' => 'index', 'plugin' => null);
		$this->assertEqual($result, $expected);

		$result = Router::parse('/foo/view/param1:value1:1/param2:value2:3/param:value');
		$expected = array('pass' => array('param1:value1:1'), 'named' => array('param2' => 'value2:3', 'param' => 'value'), 'controller' => 'bar', 'action' => 'view', 'plugin' => null);
		$this->assertEqual($result, $expected);

		Router::reload();
		Router::connectNamed(array('param1' => '[\d]', 'param2' => '[a-z]', 'param3' => '[\d]'));
		$result = Router::parse('/controller/action/param1:1/param2:2/param3:3');
		$expected = array('pass' => array('param2:2'), 'named' => array('param1' => '1', 'param3' => '3'), 'controller' => 'controller', 'action' => 'action', 'plugin' => null);
		$this->assertEqual($result, $expected);

		Router::reload();
		Router::connectNamed(array('param1' => '[\d]', 'param2' => true, 'param3' => '[\d]'));
		$result = Router::parse('/controller/action/param1:1/param2:2/param3:3');
		$expected = array('pass' => array(), 'named' => array('param1' => '1', 'param2' => '2', 'param3' => '3'), 'controller' => 'controller', 'action' => 'action', 'plugin' => null);
		$this->assertEqual($result, $expected);

		Router::reload();
		Router::connectNamed(array('param1' => 'value[\d]+:[\d]+'), array('greedy' => false));
		$result = Router::parse('/controller/action/param1:value1:1/param2:value2:3/param3:value');
		$expected = array('pass' => array('param2:value2:3', 'param3:value'), 'named' => array('param1' => 'value1:1'), 'controller' => 'controller', 'action' => 'action', 'plugin' => null);
		$this->assertEqual($result, $expected);

		Router::reload();
		Router::connect('/foo/*', array('controller' => 'bar', 'action' => 'fubar'), array('named' => array('param1' => 'value[\d]:[\d]')));
		Router::connectNamed(array(), array('greedy' => false));
		$result = Router::parse('/foo/param1:value1:1/param2:value2:3/param3:value');
		$expected = array('pass' => array('param2:value2:3', 'param3:value'), 'named' => array('param1' => 'value1:1'), 'controller' => 'bar', 'action' => 'fubar', 'plugin' => null);
		$this->assertEqual($result, $expected);
	}
/**
 * testUrlGenerationWithPrefixes method
 *
 * @access public
 * @return void
 */
	function testUrlGenerationWithPrefixes() {
		Router::reload();
		Router::connect('/protected/:controller/:action/*', array(
			'controller'	=> 'users',
			'action'		=> 'index',
			'prefix'		=> 'protected',
			'protected'		=> true
		));
		Router::parse('/');

		Router::setRequestInfo(array(
			array('plugin' => null, 'controller' => 'images', 'action' => 'index', 'pass' => array(), 'prefix' => null, 'admin' => false, 'form' => array(), 'url' => array('url' => 'images/index')),
			array('plugin' => null, 'controller' => null, 'action' => null, 'base' => '', 'here' => '/images/index', 'webroot' => '/')
		));

		$result = Router::url(array('controller' => 'images', 'action' => 'add'));
		$expected = '/images/add';
		$this->assertEqual($result, $expected);

		$result = Router::url(array('controller' => 'images', 'action' => 'add', 'protected' => true));
		$expected = '/protected/images/add';
		$this->assertEqual($result, $expected);

		$result = Router::url(array('action' => 'edit', 1));
		$expected = '/images/edit/1';
		$this->assertEqual($result, $expected);

		$result = Router::url(array('action' => 'edit', 1, 'protected' => true));
		$expected = '/protected/images/edit/1';
		$this->assertEqual($result, $expected);

		$result = Router::url(array('action' => 'protected_edit', 1, 'protected' => true));
		$expected = '/protected/images/edit/1';
		$this->assertEqual($result, $expected);

		$result = Router::url(array('action' => 'edit', 1, 'protected' => true));
		$expected = '/protected/images/edit/1';
		$this->assertEqual($result, $expected);

		$result = Router::url(array('controller' => 'others', 'action' => 'edit', 1));
		$expected = '/others/edit/1';
		$this->assertEqual($result, $expected);

		$result = Router::url(array('controller' => 'others', 'action' => 'edit', 1, 'protected' => true));
		$expected = '/protected/others/edit/1';
		$this->assertEqual($result, $expected);
		
		$result = Router::url(array('controller' => 'others', 'action' => 'edit', 1, 'protected' => true, 'page' => 1));
		$expected = '/protected/others/edit/1/page:1';
		$this->assertEqual($result, $expected);
		
		Router::connectNamed(array('random'));
		$result = Router::url(array('controller' => 'others', 'action' => 'edit', 1, 'protected' => true, 'random' => 'my-value'));
		$expected = '/protected/others/edit/1/random:my-value';
		$this->assertEqual($result, $expected);
	}
/**
 * testRemoveBase method
 *
 * @access public
 * @return void
 */
	function testRemoveBase() {
		Router::setRequestInfo(array(
			array('controller' => 'controller', 'action' => 'index', 'form' => array(), 'url' => array(), 'bare' => 0, 'plugin' => null),
			array('base' => '/base', 'here' => '/', 'webroot' => '/base/', 'passedArgs' => array(), 'argSeparator' => ':', 'namedArgs' => array())
		));

		$result = Router::url(array('controller' => 'my_controller', 'action' => 'my_action'));
		$expected = '/base/my_controller/my_action/';
		$this->assertEqual($result, $expected);

		$result = Router::url(array('controller' => 'my_controller', 'action' => 'my_action', 'base' => false));
		$expected = '/my_controller/my_action/';
		$this->assertEqual($result, $expected);

		$result = Router::url(array('controller' => 'my_controller', 'action' => 'my_action', 'base' => true));
		$expected = '/base/my_controller/my_action/base:1';
		$this->assertEqual($result, $expected);
	}
/**
 * testParamsUrlParsing method
 *
 * @access public
 * @return void
 */
	function testParamsUrlParsing() {
		Router::connect('/', array('controller' => 'posts', 'action' => 'index'));
		Router::connect('/view/:user/*', array('controller' => 'posts', 'action' => 'view'), array('user'));
		$result = Router::parse('/view/gwoo/');
		$expected = array('user' => 'gwoo', 'controller' => 'posts', 'action' => 'view', 'plugin' =>'', 'pass' => array(), 'named' => array());
		$this->assertEqual($result, $expected);

		Router::reload();
		Router::connect('/([0-9]+)-p-(.*)/', array('controller' => 'products', 'action' => 'show'));
		Router::connect('/(.*)-q-(.*)/', array('controller' => 'products', 'action' => 'show'));
		$result = Router::parse('/100-p-500/');
		$expected = array('pass' => array('100', '500'), 'named' => array(), 'controller' => 'products', 'action' => 'show', 'plugin' => null);
		$this->assertEqual($result, $expected);

		$result = Router::parse('/bob-q-500/');
		$expected = array('pass' => array('bob', '500'), 'named' => array(), 'controller' => 'products', 'action' => 'show', 'plugin' => null);
		$this->assertEqual($result, $expected);

		$result = Router::parse('/bob-p-500/');
		$expected = array('pass' => array(), 'named' => array(), 'controller' => 'bob-p-500', 'plugin' => null, 'action' => 'index');
		$this->assertEqual($result, $expected);
	}
/**
 * testPagesUrlParsing method
 *
 * @access public
 * @return void
 */
	function testPagesUrlParsing() {
		Router::connect('/', array('controller' => 'pages', 'action' => 'display', 'home'));
		Router::connect('/pages/*', array('controller' => 'pages', 'action' => 'display'));

		$result = Router::parse('/');
		$expected = array('pass'=>array('home'), 'named' => array(), 'plugin' => null, 'controller' => 'pages', 'action' => 'display');
		$this->assertEqual($result, $expected);

		$result = Router::parse('/pages/home/');
		$expected = array('pass' => array('home'), 'named' => array(), 'plugin' => null, 'controller' => 'pages', 'action' => 'display');
		$this->assertEqual($result, $expected);

		Router::reload();
		Router::connect('/', array('controller' => 'pages', 'action' => 'display', 'home'));

		$result = Router::parse('/pages/display/home/parameter:value');
		$expected = array('pass' => array('home'), 'named' => array('parameter' => 'value'), 'plugin' => null, 'controller' => 'pages', 'action' => 'display');
		$this->assertEqual($result, $expected);

		Router::reload();
		Router::connect('/', array('controller' => 'pages', 'action' => 'display', 'home'));
		Router::connect('/pages/*/:event', array('controller' => 'pages', 'action' => 'display'), array('event' => '[a-z0-9_-]+'));

		$result = Router::parse('/');
		$expected = array('pass'=>array('home'), 'named' => array(), 'plugin' => null, 'controller' => 'pages', 'action' => 'display');
		$this->assertEqual($result, $expected);

		$result = Router::parse('/pages/home');
		$expected = array('pass' => array('home'), 'named' => array(), 'event' => '', 'plugin' => null, 'controller' => 'pages', 'action' => 'display');
		$this->assertEqual($result, $expected);

		$result = Router::parse('/pages/home/');
		$expected = array('pass' => array('home'), 'named' => array(), 'event' => '', 'plugin' => null, 'controller' => 'pages', 'action' => 'display');
		$this->assertEqual($result, $expected);

		$result = Router::parse('/pages/display/home/event:value');
		$expected = array('pass' => array('home'), 'named' => array('event' => 'value'), 'plugin' => null, 'controller' => 'pages', 'action' => 'display');
		$this->assertEqual($result, $expected);

		$result = Router::parse('/pages/display/home/event:Val_u2');
		$expected = array('pass' => array('home'), 'named' => array('event' => 'Val_u2'), 'plugin' => null, 'controller' => 'pages', 'action' => 'display');
		$this->assertEqual($result, $expected);

		$result = Router::parse('/pages/display/home/event:val-ue');
		$expected = array('pass' => array('home'), 'named' => array('event' => 'val-ue'), 'plugin' => null, 'controller' => 'pages', 'action' => 'display');
		$this->assertEqual($result, $expected);

		Router::reload();
		Router::connect('/', array('controller' => 'posts', 'action' => 'index'));
		Router::connect('/pages/*', array('controller' => 'pages', 'action' => 'display'));
		$result = Router::parse('/pages/contact/');

		$expected = array('pass'=>array('contact'), 'named' => array(), 'plugin'=> null, 'controller'=>'pages', 'action'=>'display');
		$this->assertEqual($result, $expected);
	}
/**
 * testParsingWithPrefixes method
 *
 * @access public
 * @return void
 */
	function testParsingWithPrefixes() {
		$adminParams = array('prefix' => 'admin', 'admin' => true);
		Router::connect('/admin/:controller', $adminParams);
		Router::connect('/admin/:controller/:action', $adminParams);
		Router::connect('/admin/:controller/:action/*', $adminParams);

		Router::setRequestInfo(array(
			array('controller' => 'controller', 'action' => 'index', 'form' => array(), 'url' => array(), 'plugin' => null),
			array('base' => '/base', 'here' => '/', 'webroot' => '/base/', 'passedArgs' => array(), 'argSeparator' => ':', 'namedArgs' => array())
		));

		$result = Router::parse('/admin/posts/');
		$expected = array('pass' => array(), 'named' => array(), 'prefix' => 'admin', 'plugin' => null, 'controller' => 'posts', 'action' => 'index', 'admin' => true);
		$this->assertEqual($result, $expected);

		$result = Router::parse('/admin/posts');
		$this->assertEqual($result, $expected);

		$result = Router::url(array('admin' => true, 'controller' => 'posts'));
		$expected = '/base/admin/posts';
		$this->assertEqual($result, $expected);

		$result = Router::prefixes();
		$expected = array('admin');
		$this->assertEqual($result, $expected);

		Router::reload();

		$prefixParams = array('prefix' => 'members', 'members' => true);
		Router::connect('/members/:controller', $prefixParams);
		Router::connect('/members/:controller/:action', $prefixParams);
		Router::connect('/members/:controller/:action/*', $prefixParams);

		Router::setRequestInfo(array(
			array('controller' => 'controller', 'action' => 'index', 'form' => array(), 'url' => array(), 'plugin' => null),
			array('base' => '/base', 'here' => '/', 'webroot' => '/', 'passedArgs' => array(), 'argSeparator' => ':', 'namedArgs' => array())
		));

		$result = Router::parse('/members/posts/index');
		$expected = array('pass' => array(), 'named' => array(), 'prefix' => 'members', 'plugin' => null, 'controller' => 'posts', 'action' => 'index', 'members' => true);
		$this->assertEqual($result, $expected);

		$result = Router::url(array('members' => true, 'controller' => 'posts', 'action' =>'index', 'page' => 2));
		$expected = '/base/members/posts/index/page:2';
		$this->assertEqual($result, $expected);

		$result = Router::url(array('members' => true, 'controller' => 'users', 'action' => 'add'));
		$expected = '/base/members/users/add';
		$this->assertEqual($result, $expected);

		$result = Router::parse('/posts/index');
		$expected = array('pass' => array(), 'named' => array(), 'plugin' => null, 'controller' => 'posts', 'action' => 'index');
		$this->assertEqual($result, $expected);
	}
/**
 * Tests URL generation with flags and prefixes in and out of context
 *
 * @access public
 * @return void
 */
	function testUrlWritingWithPrefixes() {
		Router::connect('/company/:controller/:action/*', array('prefix' => 'company', 'company' => true));
		Router::connect('/login', array('controller' => 'users', 'action' => 'login'));

		$result = Router::url(array('controller' => 'users', 'action' => 'login', 'company' => true));
		$expected = '/company/users/login';
		$this->assertEqual($result, $expected);

		Router::setRequestInfo(array(
			array('controller' => 'users', 'action' => 'login', 'company' => true, 'form' => array(), 'url' => array(), 'plugin' => null),
			array('base' => '/', 'here' => '/', 'webroot' => '/base/', 'passedArgs' => array(), 'argSeparator' => ':', 'namedArgs' => array())
		));

		$result = Router::url(array('controller' => 'users', 'action' => 'login', 'company' => false));
		$expected = '/login';
		$this->assertEqual($result, $expected);
	}
/**
 * testPassedArgsOrder method
 *
 * @access public
 * @return void
 */
	function testPassedArgsOrder() {
		Router::connect('/test2/*', array('controller' => 'pages', 'action' => 'display', 2));
		Router::connect('/test/*', array('controller' => 'pages', 'action' => 'display', 1));
		Router::parse('/');

		$result = Router::url(array('controller' => 'pages', 'action' => 'display', 1, 'whatever'));
		$expected = '/test/whatever';
		$this->assertEqual($result, $expected);

		$result = Router::url(array('controller' => 'pages', 'action' => 'display', 2, 'whatever'));
		$expected = '/test2/whatever';
		$this->assertEqual($result, $expected);

		Configure::write('Routing.admin', 'admin');
		Router::reload();

		Router::setRequestInfo(array(
			array('plugin' => null, 'controller' => 'images', 'action' => 'index', 'pass' => array(), 'named' => array(), 'prefix' => 'protected', 'admin' => false,  'form' => array(), 'url' => array ('url' => 'protected/images/index')),
			array('plugin' => null, 'controller' => null, 'action' => null, 'base' => '', 'here' => '/protected/images/index', 'webroot' => '/')
		));

		Router::connect('/protected/:controller/:action/*', array(
			'controller'	=> 'users',
			'action'		=> 'index',
			'prefix'		=> 'protected'
		));

		Router::parse('/');
		$result = Router::url(array('controller' => 'images', 'action' => 'add'));
		$expected = '/protected/images/add';
		$this->assertEqual($result, $expected);

		$result = Router::prefixes();
		$expected = array('protected', 'admin');
		$this->assertEqual($result, $expected);
	}
/**
 * testRegexRouteMatching method
 *
 * @access public
 * @return void
 */
	function testRegexRouteMatching() {
		Router::connect('/:locale/:controller/:action/*', array(), array('locale' => 'dan|eng'));

		$result = Router::parse('/test/test_action');
		$expected = array('pass' => array(), 'named' => array(), 'controller' => 'test', 'action' => 'test_action', 'plugin' => null);
		$this->assertEqual($result, $expected);

		$result = Router::parse('/eng/test/test_action');
		$expected = array('pass' => array(), 'named' => array(), 'locale' => 'eng', 'controller' => 'test', 'action' => 'test_action', 'plugin' => null);
		$this->assertEqual($result, $expected);

		$result = Router::parse('/badness/test/test_action');
		$expected = array('pass' => array('test_action'), 'named' => array(), 'controller' => 'badness', 'action' => 'test', 'plugin' => null);
		$this->assertEqual($result, $expected);

		Router::reload();
		Router::connect('/:locale/:controller/:action/*', array(), array('locale' => 'dan|eng'));

		Router::setRequestInfo(array(
			array('plugin' => null, 'controller' => 'test', 'action' => 'index', 'pass' => array(), 'form' => array(), 'url' => array ('url' => 'test/test_action')),
			array('plugin' => null, 'controller' => null, 'action' => null, 'base' => '', 'here' => '/test/test_action', 'webroot' => '/')
		));

		$result = Router::url(array('action' => 'test_another_action'));
		$expected = '/test/test_another_action/';
		$this->assertEqual($result, $expected);

		$result = Router::url(array('action' => 'test_another_action', 'locale' => 'eng'));
		$expected = '/eng/test/test_another_action';
		$this->assertEqual($result, $expected);

		$result = Router::url(array('action' => 'test_another_action', 'locale' => 'badness'));
		$expected = '/test/test_another_action/locale:badness';
		$this->assertEqual($result, $expected);
	}
/**
 * testStripPlugin
 *
 * @return void
 * @access public
 */
	function testStripPlugin() {
		$pluginName = 'forums';
		$url = 'example.com/' . $pluginName . '/';
		$expected = 'example.com';

		$this->assertEqual(Router::stripPlugin($url, $pluginName), $expected);
		$this->assertEqual(Router::stripPlugin($url), $url);
		$this->assertEqual(Router::stripPlugin($url, null), $url);
	}
/**
 * testCurentRoute
 *
 * This test needs some improvement and actual requestAction() usage
 * 
 * @return void
 * @access public
 */
	function testCurentRoute() {
		$url = array('controller' => 'pages', 'action' => 'display', 'government');
		Router::connect('/government', $url);
		Router::parse('/government');
		$route = Router::currentRoute();
		$this->assertEqual(array_merge($url, array('plugin' => false)), $route[3]);
	}
/**
 * testRequestRoute
 *
 * @return void
 * @access public
 */
	function testRequestRoute() {
		$url = array('controller' => 'products', 'action' => 'display', 5);
		Router::connect('/government', $url);
		Router::parse('/government');
		$route = Router::requestRoute();
		$this->assertEqual(array_merge($url, array('plugin' => false)), $route[3]);

		// test that the first route is matched
		$newUrl = array('controller' => 'products', 'action' => 'display', 6);
		Router::connect('/government', $url);
		Router::parse('/government');
		$route = Router::requestRoute();
		$this->assertEqual(array_merge($url, array('plugin' => false)), $route[3]);

		// test that an unmatched route does not change the current route
		$newUrl = array('controller' => 'products', 'action' => 'display', 6);
		Router::connect('/actor', $url);
		Router::parse('/government');
		$route = Router::requestRoute();
		$this->assertEqual(array_merge($url, array('plugin' => false)), $route[3]);
	}
/**
 * testGetParams
 *
 * @return void
 * @access public
 */
	function testGetParams() {
		$paths = array('base' => '/', 'here' => '/products/display/5', 'webroot' => '/webroot');
		$params = array('param1' => '1', 'param2' => '2');
		Router::setRequestInfo(array($params, $paths));
		$expected = array(
			'plugin' => false, 'controller' => false, 'action' => false,
			'param1' => '1', 'param2' => '2'
		);
		$this->assertEqual(Router::getparams(), $expected);
		$this->assertEqual(Router::getparam('controller'), false);
		$this->assertEqual(Router::getparam('param1'), '1');
		$this->assertEqual(Router::getparam('param2'), '2');

		Router::reload();

		$params = array('controller' => 'pages', 'action' => 'display');
		Router::setRequestInfo(array($params, $paths));
		$expected = array('plugin' => false, 'controller' => 'pages', 'action' => 'display');
		$this->assertEqual(Router::getparams(), $expected);
		$this->assertEqual(Router::getparams(true), $expected);
	}
}
?>