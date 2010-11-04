<?php
/**
 * RouterTest file
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) Tests <http://book.cakephp.org/view/1196/Testing>
 * Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 *	Licensed under The Open Group Test Suite License
 *	Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/view/1196/Testing CakePHP(tm) Tests
 * @package       cake
 * @subpackage    cake.tests.cases.libs
 * @since         CakePHP(tm) v 1.2.0.4206
 * @license       http://www.opensource.org/licenses/opengroup.php The Open Group Test Suite License
 */
App::import('Core', array('Router'));

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
		$this->_routing = Configure::read('Routing');
		Configure::write('Routing', array('admin' => null, 'prefixes' => array()));
		Router::reload();
		$this->router =& Router::getInstance();
	}

/**
 * end the test and reset the environment
 *
 * @return void
 */
	function endTest() {
		Configure::write('Routing', $this->_routing);
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
		$this->assertPattern('/^http(s)?:\/\//', Router::url(array('full_base' => true)));
		$this->assertIdentical(FULL_BASE_URL . '/', Router::url(array('full_base' => true)));
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
 * test generation of basic urls.
 *
 * @access public
 * @return void
 */
	function testUrlGenerationBasic() {
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
		Router::connect('/:plugin/:id/*', array('controller' => 'posts', 'action' => 'view'), array('id' => $ID));
		Router::parse('/');

		$result = Router::url(array('plugin' => 'cake_plugin', 'controller' => 'posts', 'action' => 'view', 'id' => '1'));
		$expected = '/cake_plugin/1';
		$this->assertEqual($result, $expected);

		$result = Router::url(array('plugin' => 'cake_plugin', 'controller' => 'posts', 'action' => 'view', 'id' => '1', '0'));
		$expected = '/cake_plugin/1/0';
		$this->assertEqual($result, $expected);

		Router::reload();
		Router::connect('/:controller/:action/:id', array(), array('id' => $ID));
		Router::parse('/');

		$result = Router::url(array('controller' => 'posts', 'action' => 'view', 'id' => '1'));
		$expected = '/posts/view/1';
		$this->assertEqual($result, $expected);

		Router::reload();
		Router::connect('/:controller/:id', array('action' => 'view'));
		Router::parse('/');

		$result = Router::url(array('controller' => 'posts', 'action' => 'view', 'id' => '1'));
		$expected = '/posts/1';
		$this->assertEqual($result, $expected);

		$result = Router::url(array('controller' => 'posts', 'action' => 'index', '0'));
		$expected = '/posts/index/0';
		$this->assertEqual($result, $expected);

		Router::connect('/view/*', array('controller' => 'posts', 'action' => 'view'));
		Router::promote();
		$result = Router::url(array('controller' => 'posts', 'action' => 'view', '1'));
		$expected = '/view/1';
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
		Router::connect('/page/*', array('plugin' => null, 'controller' => 'pages', 'action' => 'view'));
		Router::parse('/');

		$result = Router::url(array('plugin' => 'my_plugin', 'controller' => 'pages', 'action' => 'view', 'my-page'));
		$expected = '/my_plugin/pages/view/my-page';
		$this->assertEqual($result, $expected);

		Router::reload();
		Router::connect('/contact/:action', array('plugin' => 'contact', 'controller' => 'contact'));
		Router::parse('/');

		$result = Router::url(array('plugin' => 'contact', 'controller' => 'contact', 'action' => 'me'));

		$expected = '/contact/me';
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
		$expected = '/myothercontroller';
		$this->assertEqual($result, $expected);
	}

/**
 * Test generation of routes with query string parameters.
 *
 * @return void
 **/
	function testUrlGenerationWithQueryStrings() {
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
	}

/**
 * test that regex validation of keyed route params is working.
 *
 * @return void
 **/
	function testUrlGenerationWithRegexQualifiedParams() {
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
		Router::connect('/forestillinger/:month/:year/*',
			array('plugin' => 'shows', 'controller' => 'shows', 'action' => 'calendar'),
			array('month' => '0[1-9]|1[012]', 'year' => '[12][0-9]{3}')
		);
		Router::parse('/');

		$result = Router::url(array('plugin' => 'shows', 'controller' => 'shows', 'action' => 'calendar', 'month' => 10, 'year' => 2007, 'min-forestilling'));
		$expected = '/forestillinger/10/2007/min-forestilling';
		$this->assertEqual($result, $expected);

		Router::reload();
		Router::connect('/kalender/:month/:year/*',
			array('plugin' => 'shows', 'controller' => 'shows', 'action' => 'calendar'),
			array('month' => '0[1-9]|1[012]', 'year' => '[12][0-9]{3}')
		);
		Router::connect('/kalender/*', array('plugin' => 'shows', 'controller' => 'shows', 'action' => 'calendar'));
		Router::parse('/');

		$result = Router::url(array('plugin' => 'shows', 'controller' => 'shows', 'action' => 'calendar', 'min-forestilling'));
		$expected = '/kalender/min-forestilling';
		$this->assertEqual($result, $expected);

		$result = Router::url(array('plugin' => 'shows', 'controller' => 'shows', 'action' => 'calendar', 'year' => 2007, 'month' => 10, 'min-forestilling'));
		$expected = '/kalender/10/2007/min-forestilling';
		$this->assertEqual($result, $expected);

		Router::reload();
		Router::connect('/:controller/:action/*', array(), array(
			'controller' => 'source|wiki|commits|tickets|comments|view',
			'action' => 'branches|history|branch|logs|view|start|add|edit|modify'
		));
		Router::defaults(false);
		$result = Router::parse('/foo/bar');
		$expected = array('pass' => array(), 'named' => array());
		$this->assertEqual($result, $expected);
	}

/**
 * Test url generation with an admin prefix
 *
 * @access public
 * @return void
 */
	function testUrlGenerationWithAdminPrefix() {
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

		Router::reload();
		Router::connect('/admin/subscriptions/:action/*', array('controller' => 'subscribe', 'admin' => true, 'prefix' => 'admin'));
		Router::parse('/');
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

		$result = Router::url(array('action' => 'edit', 1));
		$expected = '/magazine/admin/subscriptions/edit/1';
		$this->assertEqual($result, $expected);

		$result = Router::url(array('admin' => true, 'controller' => 'users', 'action' => 'login'));
		$expected = '/magazine/admin/users/login';
		$this->assertEqual($result, $expected);


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
		Router::parse('/');
		Router::setRequestInfo(array(
			array('plugin' => null, 'controller' => 'pages', 'action' => 'admin_add', 'pass' => array(), 'prefix' => 'admin', 'admin' => true, 'form' => array(), 'url' => array('url' => 'admin/pages/add')),
			array('plugin' => null, 'controller' => null, 'action' => null, 'base' => '', 'here' => '/admin/pages/add', 'webroot' => '/')
		));

		$result = Router::url(array('plugin' => null, 'controller' => 'pages', 'action' => 'add', 'id' => false));
		$expected = '/admin/pages/add';
		$this->assertEqual($result, $expected);

		Router::reload();
		Router::connect('/admin/:controller/:action/:id', array('admin' => true), array('id' => '[0-9]+'));
		Router::parse('/');
		Router::setRequestInfo(array(
			array ('plugin' => null, 'controller' => 'pages', 'action' => 'admin_edit', 'pass' => array('284'), 'prefix' => 'admin', 'admin' => true, 'form' => array(), 'url' => array('url' => 'admin/pages/edit/284')),
			array ('plugin' => null, 'controller' => null, 'action' => null, 'base' => '', 'here' => '/admin/pages/edit/284', 'webroot' => '/')
		));

		$result = Router::url(array('plugin' => null, 'controller' => 'pages', 'action' => 'edit', 'id' => '284'));
		$expected = '/admin/pages/edit/284';
		$this->assertEqual($result, $expected);


		Router::reload();
		Router::parse('/');
		Router::setRequestInfo(array(
			array ('plugin' => null, 'controller' => 'pages', 'action' => 'admin_add', 'pass' => array(), 'prefix' => 'admin', 'admin' => true, 'form' => array(), 'url' => array('url' => 'admin/pages/add')),
			array ('plugin' => null, 'controller' => null, 'action' => null, 'base' => '', 'here' => '/admin/pages/add', 'webroot' => '/')
		));

		$result = Router::url(array('plugin' => null, 'controller' => 'pages', 'action' => 'add', 'id' => false));
		$expected = '/admin/pages/add';
		$this->assertEqual($result, $expected);


		Router::reload();
		Router::parse('/');
		Router::setRequestInfo(array(
			array('plugin' => null, 'controller' => 'pages', 'action' => 'admin_edit', 'pass' => array('284'), 'prefix' => 'admin', 'admin' => true, 'form' => array(), 'url' => array('url' => 'admin/pages/edit/284')),
			array('plugin' => null, 'controller' => null, 'action' => null, 'base' => '', 'here' => '/admin/pages/edit/284', 'webroot' => '/')
		));

		$result = Router::url(array('plugin' => null, 'controller' => 'pages', 'action' => 'edit', 284));
		$expected = '/admin/pages/edit/284';
		$this->assertEqual($result, $expected);


		Router::reload();
		Router::connect('/admin/posts/*', array('controller' => 'posts', 'action' => 'index', 'admin' => true));
		Router::parse('/');
		Router::setRequestInfo(array(
			array('pass' => array(), 'action' => 'admin_index', 'plugin' => null, 'controller' => 'posts', 'prefix' => 'admin', 'admin' => true, 'url' => array('url' => 'admin/posts')),
			array('base' => '', 'here' => '/admin/posts', 'webroot' => '/')
		));

		$result = Router::url(array('all'));
		$expected = '/admin/posts/all';
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
	function testUrlGenerationPlugins() {
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
		Router::connect('/:lang/:plugin/:controller/*', array('action' => 'index'));

		Router::setRequestInfo(array(
			array(
				'lang' => 'en',
				'plugin' => 'shows', 'controller' => 'shows', 'action' => 'index', 'pass' =>
					array(), 'form' => array(), 'url' =>
					array('url' => 'en/shows/')),
			array('plugin' => NULL, 'controller' => NULL, 'action' => NULL, 'base' => '',
			'here' => '/en/shows/', 'webroot' => '/')
		));

		Router::parse('/en/shows/');

		$result = Router::url(array(
			'lang' => 'en',
			'controller' => 'shows', 'action' => 'index', 'page' => '1',
		));
		$expected = '/en/shows/shows/page:1';
		$this->assertEqual($result, $expected);
	}

/**
 * test that you can leave active plugin routes with plugin = null
 *
 * @return void
 */
	function testCanLeavePlugin() {
		Router::reload();
		Router::connect(
			'/admin/other/:controller/:action/*',
			array(
				'admin' => 1,
				'plugin' => 'aliased',
				'prefix' => 'admin'
			)
		);
		Router::setRequestInfo(array(
			array(
				'pass' => array(),
				'admin' => true,
				'prefix' => 'admin',
				'plugin' => 'this',
				'action' => 'admin_index',
				'controller' => 'interesting',
				'url' => array('url' => 'admin/this/interesting/index'),
			),
			array(
				'base' => '',
				'here' => '/admin/this/interesting/index',
				'webroot' => '/',
				'passedArgs' => array(),
			)
		));
		$result = Router::url(array('plugin' => null, 'controller' => 'posts', 'action' => 'index'));
		$this->assertEqual($result, '/admin/posts');

		$result = Router::url(array('controller' => 'posts', 'action' => 'index'));
		$this->assertEqual($result, '/admin/this/posts');

		$result = Router::url(array('plugin' => 'aliased', 'controller' => 'posts', 'action' => 'index'));
		$this->assertEqual($result, '/admin/other/posts/index');
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
		Router::connect('/posts/:month/:day/:year/*', array('controller' => 'posts', 'action' => 'view'), array('year' => $Year, 'month' => $Month, 'day' => $Day));
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
		Router::connect('/:controller/:action/*');
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
	}

/**
 * test that the persist key works.
 *
 * @return void
 */
	function testPersistentParameters() {
		Router::reload();
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

		$expected = '/posts/edit/6';
		$result = Router::url(array('controller' => 'posts', 'action' => 'edit', 6, 'color' => null, 'lang' => null));
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
		$expected = array('pass' => array(), 'named' => array(), 'plugin' => null, 'controller' => 'pages', 'action' => 'view', 'slug' => 'this_is_the_slug', 'extra' => null);
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
 * Test that Routing.prefixes and Routing.admin are used when a Router instance is created
 * or reset
 *
 * @return void
 */
	function testRoutingPrefixesSetting() {
		$restore = Configure::read('Routing');

		Configure::write('Routing.admin', 'admin');
		Configure::write('Routing.prefixes', array('member', 'super_user'));
		Router::reload();
		$result = Router::prefixes();
		$expected = array('admin', 'member', 'super_user');
		$this->assertEqual($result, $expected);

		Configure::write('Routing.prefixes', 'member');
		Router::reload();
		$result = Router::prefixes();
		$expected = array('admin', 'member');
		$this->assertEqual($result, $expected);

		Configure::write('Routing', $restore);
	}

/**
 * test compatibility with old Routing.admin config setting.
 *
 * @access public
 * @return void
 * @todo Once Routing.admin is removed update these tests.
 */
	function testAdminRoutingCompatibility() {
		Configure::write('Routing.admin', 'admin');

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
	}

/**
 * Test prefix routing and plugin combinations
 *
 * @return void
 */
	function testPrefixRoutingAndPlugins() {
		Configure::write('Routing.prefixes', array('admin'));
		$paths = App::path('plugins');
		App::build(array(
			'plugins' =>  array(
				TEST_CAKE_CORE_INCLUDE_PATH . 'tests' . DS . 'test_app' . DS . 'plugins' . DS
			)
		), true);
		App::objects('plugin', null, false);

		Router::reload();
		Router::setRequestInfo(array(
			array('admin' => true, 'controller' => 'controller', 'action' => 'action',
				'form' => array(), 'url' => array(), 'plugin' => null, 'prefix' => 'admin'),
			array('base' => '/', 'here' => '/', 'webroot' => '/base/', 'passedArgs' => array(),
				'argSeparator' => ':', 'namedArgs' => array())
		));
		Router::parse('/');

		$result = Router::url(array('plugin' => 'test_plugin', 'controller' => 'test_plugin', 'action' => 'index'));
		$expected = '/admin/test_plugin';
		$this->assertEqual($result, $expected);

		Router::reload();
		Router::parse('/');
		Router::setRequestInfo(array(
			array(
				'plugin' => 'test_plugin', 'controller' => 'show_tickets', 'action' => 'admin_edit',
				'pass' => array('6'), 'prefix' => 'admin', 'admin' => true, 'form' => array(),
				'url' => array('url' => 'admin/shows/show_tickets/edit/6')
			),
			array(
				'plugin' => null, 'controller' => null, 'action' => null, 'base' => '',
				'here' => '/admin/shows/show_tickets/edit/6', 'webroot' => '/'
			)
		));

		$result = Router::url(array(
			'plugin' => 'test_plugin', 'controller' => 'show_tickets', 'action' => 'edit', 6,
			'admin' => true, 'prefix' => 'admin'
		));
		$expected = '/admin/test_plugin/show_tickets/edit/6';
		$this->assertEqual($result, $expected);

		$result = Router::url(array(
			'plugin' => 'test_plugin', 'controller' => 'show_tickets', 'action' => 'index', 'admin' => true
		));
		$expected = '/admin/test_plugin/show_tickets';
		$this->assertEqual($result, $expected);

		App::build(array('plugins' => $paths));
	}

/**
 * testExtensionParsingSetting method
 *
 * @access public
 * @return void
 */
	function testExtensionParsingSetting() {
		$router =& Router::getInstance();
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

		Router::reload();
		Router::parseExtensions('rss');
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

		$result = Router::url(array('controller' => 'graphs', 'action' => 'view', 12, 'file' => 'asdf.foo'));
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
 * test url generation with legacy (1.2) style prefix routes.
 *
 * @access public
 * @return void
 * @todo Remove tests related to legacy style routes.
 * @see testUrlGenerationWithAutoPrefixes
 */
	function testUrlGenerationWithLegacyPrefixes() {
		Router::reload();
		Router::connect('/protected/:controller/:action/*', array(
			'prefix' => 'protected',
			'protected' => true
		));
		Router::parse('/');

		Router::setRequestInfo(array(
			array('plugin' => null, 'controller' => 'images', 'action' => 'index', 'pass' => array(), 'prefix' => null, 'admin' => false, 'form' => array(), 'url' => array('url' => 'images/index')),
			array('plugin' => null, 'controller' => null, 'action' => null, 'base' => '', 'here' => '/images/index', 'webroot' => '/')
		));

		$result = Router::url(array('protected' => true));
		$expected = '/protected/images/index';
		$this->assertEqual($result, $expected);

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
 * test newer style automatically generated prefix routes.
 *
 * @return void
 */
	function testUrlGenerationWithAutoPrefixes() {
		Configure::write('Routing.prefixes', array('protected'));
		Router::reload();
		Router::parse('/');

		Router::setRequestInfo(array(
			array('plugin' => null, 'controller' => 'images', 'action' => 'index', 'pass' => array(), 'prefix' => null, 'protected' => false, 'form' => array(), 'url' => array('url' => 'images/index')),
			array('base' => '', 'here' => '/images/index', 'webroot' => '/')
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
 * test that auto-generated prefix routes persist
 *
 * @return void
 */
	function testAutoPrefixRoutePersistence() {
		Configure::write('Routing.prefixes', array('protected'));
		Router::reload();
		Router::parse('/');

		Router::setRequestInfo(array(
			array('plugin' => null, 'controller' => 'images', 'action' => 'index', 'pass' => array(), 'prefix' => 'protected', 'protected' => true, 'form' => array(), 'url' => array('url' => 'protected/images/index')),
			array('base' => '', 'here' => '/protected/images/index', 'webroot' => '/')
		));

		$result = Router::url(array('controller' => 'images', 'action' => 'add'));
		$expected = '/protected/images/add';
		$this->assertEqual($result, $expected);

		$result = Router::url(array('controller' => 'images', 'action' => 'add', 'protected' => false));
		$expected = '/images/add';
		$this->assertEqual($result, $expected);
	}

/**
 * test that setting a prefix override the current one
 *
 * @return void
 */
	function testPrefixOverride() {
		Configure::write('Routing.prefixes', array('protected', 'admin'));
		Router::reload();
		Router::parse('/');

		Router::setRequestInfo(array(
			array('plugin' => null, 'controller' => 'images', 'action' => 'index', 'pass' => array(), 'prefix' => 'protected', 'protected' => true, 'form' => array(), 'url' => array('url' => 'protected/images/index')),
			array('base' => '', 'here' => '/protected/images/index', 'webroot' => '/')
		));

		$result = Router::url(array('controller' => 'images', 'action' => 'add', 'admin' => true));
		$expected = '/admin/images/add';
		$this->assertEqual($result, $expected);

		Router::setRequestInfo(array(
			array('plugin' => null, 'controller' => 'images', 'action' => 'index', 'pass' => array(), 'prefix' => 'admin', 'admin' => true, 'form' => array(), 'url' => array('url' => 'admin/images/index')),
			array('base' => '', 'here' => '/admin/images/index', 'webroot' => '/')
		));
		$result = Router::url(array('controller' => 'images', 'action' => 'add', 'protected' => true));
		$expected = '/protected/images/add';
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
		$expected = '/base/my_controller/my_action';
		$this->assertEqual($result, $expected);

		$result = Router::url(array('controller' => 'my_controller', 'action' => 'my_action', 'base' => false));
		$expected = '/my_controller/my_action';
		$this->assertEqual($result, $expected);

		$result = Router::url(array('controller' => 'my_controller', 'action' => 'my_action', 'base' => true));
		$expected = '/base/my_controller/my_action/base:1';
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
		$expected = array('pass'=> array('home'), 'named' => array(), 'plugin' => null, 'controller' => 'pages', 'action' => 'display');
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

		$result = Router::parse('/');
		$expected = array('pass' => array('home'), 'named' => array(), 'plugin' => null, 'controller' => 'pages', 'action' => 'display');
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
 * test that requests with a trailing dot don't loose the do.
 *
 * @return void
 */
	function testParsingWithTrailingPeriod() {
		Router::reload();
		$result = Router::parse('/posts/view/something.');
		$this->assertEqual($result['pass'][0], 'something.', 'Period was chopped off %s');

		$result = Router::parse('/posts/view/something. . .');
		$this->assertEqual($result['pass'][0], 'something. . .', 'Period was chopped off %s');
	}

/**
 * test that requests with a trailing dot don't loose the do.
 *
 * @return void
 */
	function testParsingWithTrailingPeriodAndParseExtensions() {
		Router::reload();
		Router::parseExtensions('json');

		$result = Router::parse('/posts/view/something.');
		$this->assertEqual($result['pass'][0], 'something.', 'Period was chopped off %s');

		$result = Router::parse('/posts/view/something. . .');
		$this->assertEqual($result['pass'][0], 'something. . .', 'Period was chopped off %s');
	}

/**
 * test that patterns work for :action
 *
 * @return void
 */
	function testParsingWithPatternOnAction() {
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
			'named' => array()
		);
		$this->assertEqual($expected, $result);

		$result = Router::parse('/blog/foobar');
		$expected = array(
			'plugin' => null,
			'controller' => 'blog',
			'action' => 'foobar',
			'pass' => array(),
			'named' => array()
		);
		$this->assertEqual($expected, $result);

		$result = Router::url(array('controller' => 'blog_posts', 'action' => 'foo'));
		$this->assertEqual('/blog_posts/foo', $result);

		$result = Router::url(array('controller' => 'blog_posts', 'action' => 'actions'));
		$this->assertEqual('/blog/actions', $result);
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
			array('base' => '/', 'here' => '/', 'webroot' => '/base/')
		));

		$result = Router::url(array('controller' => 'users', 'action' => 'login', 'company' => false));
		$expected = '/login';
		$this->assertEqual($result, $expected);
	}

/**
 * test url generation with prefixes and custom routes
 *
 * @return void
 */
	function testUrlWritingWithPrefixesAndCustomRoutes() {
		Router::connect(
			'/admin/login',
			array('controller' => 'users', 'action' => 'login', 'prefix' => 'admin', 'admin' => true)
		);
		Router::setRequestInfo(array(
			array('controller' => 'posts', 'action' => 'index', 'admin' => true, 'prefix' => 'admin',
				'form' => array(), 'url' => array(), 'plugin' => null
			),
			array('base' => '/', 'here' => '/', 'webroot' => '/')
		));
		$result = Router::url(array('controller' => 'users', 'action' => 'login', 'admin' => true));
		$this->assertEqual($result, '/admin/login');

		$result = Router::url(array('controller' => 'users', 'action' => 'login'));
		$this->assertEqual($result, '/admin/login');

		$result = Router::url(array('controller' => 'users', 'action' => 'admin_login'));
		$this->assertEqual($result, '/admin/login');
	}

/**
 * testPassedArgsOrder method
 *
 * @access public
 * @return void
 */
	function testPassedArgsOrder() {
		Router::connect('/test-passed/*', array('controller' => 'pages', 'action' => 'display', 'home'));
		Router::connect('/test2/*', array('controller' => 'pages', 'action' => 'display', 2));
		Router::connect('/test/*', array('controller' => 'pages', 'action' => 'display', 1));
		Router::parse('/');

		$result = Router::url(array('controller' => 'pages', 'action' => 'display', 1, 'whatever'));
		$expected = '/test/whatever';
		$this->assertEqual($result, $expected);

		$result = Router::url(array('controller' => 'pages', 'action' => 'display', 2, 'whatever'));
		$expected = '/test2/whatever';
		$this->assertEqual($result, $expected);

		$result = Router::url(array('controller' => 'pages', 'action' => 'display', 'home', 'whatever'));
		$expected = '/test-passed/whatever';
		$this->assertEqual($result, $expected);

		Configure::write('Routing.prefixes', array('admin'));
		Router::reload();

		Router::setRequestInfo(array(
			array('plugin' => null, 'controller' => 'images', 'action' => 'index', 'pass' => array(), 'named' => array(), 'prefix' => 'protected', 'protected' => true,  'form' => array(), 'url' => array ('url' => 'protected/images/index')),
			array('plugin' => null, 'controller' => null, 'action' => null, 'base' => '', 'here' => '/protected/images/index', 'webroot' => '/')
		));

		Router::connect('/protected/:controller/:action/*', array(
			'controller' => 'users',
			'action' => 'index',
			'prefix' => 'protected'
		));

		Router::parse('/');
		$result = Router::url(array('controller' => 'images', 'action' => 'add'));
		$expected = '/protected/images/add';
		$this->assertEqual($result, $expected);

		$result = Router::prefixes();
		$expected = array('admin', 'protected');
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
		$expected = '/test/test_another_action';
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
	function testCurrentRoute() {
		$url = array('controller' => 'pages', 'action' => 'display', 'government');
		Router::connect('/government', $url);
		Router::parse('/government');
		$route =& Router::currentRoute();
		$this->assertEqual(array_merge($url, array('plugin' => null)), $route->defaults);
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
		$route =& Router::requestRoute();
		$this->assertEqual(array_merge($url, array('plugin' => null)), $route->defaults);

		// test that the first route is matched
		$newUrl = array('controller' => 'products', 'action' => 'display', 6);
		Router::connect('/government', $url);
		Router::parse('/government');
		$route =& Router::requestRoute();
		$this->assertEqual(array_merge($url, array('plugin' => null)), $route->defaults);

		// test that an unmatched route does not change the current route
		$newUrl = array('controller' => 'products', 'action' => 'display', 6);
		Router::connect('/actor', $url);
		Router::parse('/government');
		$route =& Router::requestRoute();
		$this->assertEqual(array_merge($url, array('plugin' => null)), $route->defaults);
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
			'plugin' => null, 'controller' => false, 'action' => false,
			'param1' => '1', 'param2' => '2'
		);
		$this->assertEqual(Router::getparams(), $expected);
		$this->assertEqual(Router::getparam('controller'), false);
		$this->assertEqual(Router::getparam('param1'), '1');
		$this->assertEqual(Router::getparam('param2'), '2');

		Router::reload();

		$params = array('controller' => 'pages', 'action' => 'display');
		Router::setRequestInfo(array($params, $paths));
		$expected = array('plugin' => null, 'controller' => 'pages', 'action' => 'display');
		$this->assertEqual(Router::getparams(), $expected);
		$this->assertEqual(Router::getparams(true), $expected);
	}

/**
 * test that connectDefaults() can disable default route connection
 *
 * @return void
 */
	function testDefaultsMethod() {
		Router::defaults(false);
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
	function testConnectDefaultRoutes() {
		App::build(array(
			'plugins' =>  array(
				TEST_CAKE_CORE_INCLUDE_PATH . 'tests' . DS . 'test_app' . DS . 'plugins' . DS
			)
		), true);
		App::objects('plugin', null, false);
		Router::reload();

		$result = Router::url(array('plugin' => 'plugin_js', 'controller' => 'js_file', 'action' => 'index'));
		$this->assertEqual($result, '/plugin_js/js_file');

		$result = Router::parse('/plugin_js/js_file');
		$expected = array(
			'plugin' => 'plugin_js', 'controller' => 'js_file', 'action' => 'index',
			'named' => array(), 'pass' => array()
		);
		$this->assertEqual($result, $expected);

		$result = Router::url(array('plugin' => 'test_plugin', 'controller' => 'test_plugin', 'action' => 'index'));
		$this->assertEqual($result, '/test_plugin');

		$result = Router::parse('/test_plugin');
		$expected = array(
			'plugin' => 'test_plugin', 'controller' => 'test_plugin', 'action' => 'index',
			'named' => array(), 'pass' => array()
		);

		$this->assertEqual($result, $expected, 'Plugin shortcut route broken. %s');
	}

/**
 * test using a custom route class for route connection
 *
 * @return void
 */
	function testUsingCustomRouteClass() {
		Mock::generate('CakeRoute', 'MockConnectedRoute');
		$routes = Router::connect(
			'/:slug',
			array('controller' => 'posts', 'action' => 'view'),
			array('routeClass' => 'MockConnectedRoute', 'slug' => '[a-z_-]+')
		);
		$this->assertTrue(is_a($routes[0], 'MockConnectedRoute'), 'Incorrect class used. %s');
		$expected = array('controller' => 'posts', 'action' => 'view', 'slug' => 'test');
		$routes[0]->setReturnValue('parse', $expected);
		$result = Router::parse('/test');
		$this->assertEqual($result, $expected);
	}

/**
 * test reversing parameter arrays back into strings.
 *
 * @return void
 */
	function testRouterReverse() {
		$params = array(
			'controller' => 'posts',
			'action' => 'view',
			'pass' => array(1),
			'named' => array(),
			'url' => array(),
			'autoRender' => 1,
			'bare' => 1,
			'return' => 1,
			'requested' => 1
		);
		$result = Router::reverse($params);
		$this->assertEqual($result, '/posts/view/1');

		$params = array(
			'controller' => 'posts',
			'action' => 'index',
			'pass' => array(1),
			'named' => array('page' => 1, 'sort' => 'Article.title', 'direction' => 'desc'),
			'url' => array()
		);
		$result = Router::reverse($params);
		$this->assertEqual($result, '/posts/index/1/page:1/sort:Article.title/direction:desc');

		Router::connect('/:lang/:controller/:action/*', array(), array('lang' => '[a-z]{3}'));
		$params = array(
			'lang' => 'eng',
			'controller' => 'posts',
			'action' => 'view',
			'pass' => array(1),
			'named' => array(),
			'url' => array('url' => 'eng/posts/view/1')
		);
		$result = Router::reverse($params);
		$this->assertEqual($result, '/eng/posts/view/1');

		$params = array(
			'lang' => 'eng',
			'controller' => 'posts',
			'action' => 'view',
			'pass' => array(1),
			'named' => array(),
			'url' => array('url' => 'eng/posts/view/1', 'foo' => 'bar', 'baz' => 'quu'),
			'paging' => array(),
			'models' => array()
		);
		$result = Router::reverse($params);
		$this->assertEqual($result, '/eng/posts/view/1?foo=bar&baz=quu');
	}
}

/**
 * Test case for CakeRoute
 *
 * @package cake.tests.cases.libs.
 **/
class CakeRouteTestCase extends CakeTestCase {
/**
 * startTest method
 *
 * @access public
 * @return void
 */
	function startTest() {
		$this->_routing = Configure::read('Routing');
		Configure::write('Routing', array('admin' => null, 'prefixes' => array()));
		Router::reload();
	}

/**
 * end the test and reset the environment
 *
 * @return void
 **/
	function endTest() {
		Configure::write('Routing', $this->_routing);
	}

/**
 * Test the construction of a CakeRoute
 *
 * @return void
 **/
	function testConstruction() {
		$route =& new CakeRoute('/:controller/:action/:id', array(), array('id' => '[0-9]+'));

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
		$route =& new CakeRoute('/', array('controller' => 'pages', 'action' => 'display', 'home'));
		$result = $route->compile();
		$expected = '#^/*$#';
		$this->assertEqual($result, $expected);
		$this->assertEqual($route->keys, array());

		$route =& new CakeRoute('/:controller/:action', array('controller' => 'posts'));
		$result = $route->compile();

		$this->assertPattern($result, '/posts/edit');
		$this->assertPattern($result, '/posts/super_delete');
		$this->assertNoPattern($result, '/posts');
		$this->assertNoPattern($result, '/posts/super_delete/1');

		$route =& new CakeRoute('/posts/foo:id', array('controller' => 'posts', 'action' => 'view'));
		$result = $route->compile();

		$this->assertPattern($result, '/posts/foo:1');
		$this->assertPattern($result, '/posts/foo:param');
		$this->assertNoPattern($result, '/posts');
		$this->assertNoPattern($result, '/posts/');

		$this->assertEqual($route->keys, array('id'));

		$route =& new CakeRoute('/:plugin/:controller/:action/*', array('plugin' => 'test_plugin', 'action' => 'index'));
		$result = $route->compile();
		$this->assertPattern($result, '/test_plugin/posts/index');
		$this->assertPattern($result, '/test_plugin/posts/edit/5');
		$this->assertPattern($result, '/test_plugin/posts/edit/5/name:value/nick:name');
	}

/**
 * test route names with - in them.
 *
 * @return void
 */
	function testHyphenNames() {
		$route =& new CakeRoute('/articles/:date-from/:date-to', array(
			'controller' => 'articles', 'action' => 'index'
		));
		$expected = array(
			'controller' => 'articles',
			'action' => 'index',
			'date-from' => '2009-07-31',
			'date-to' => '2010-07-31',
			'named' => array(),
			'pass' => array()
		);
		$result = $route->parse('/articles/2009-07-31/2010-07-31');
		$this->assertEqual($result, $expected);
	}

/**
 * test that route parameters that overlap don't cause errors.
 *
 * @return void
 */
	function testRouteParameterOverlap() {
		$route =& new CakeRoute('/invoices/add/:idd/:id', array('controller' => 'invoices', 'action' => 'add'));
		$result = $route->compile();
		$this->assertPattern($result, '/invoices/add/1/3');

		$route =& new CakeRoute('/invoices/add/:id/:idd', array('controller' => 'invoices', 'action' => 'add'));
		$result = $route->compile();
		$this->assertPattern($result, '/invoices/add/1/3');
	}

/**
 * test compiling routes with keys that have patterns
 *
 * @return void
 **/
	function testRouteCompilingWithParamPatterns() {
		extract(Router::getNamedExpressions());

		$route = new CakeRoute(
			'/:controller/:action/:id',
			array(),
			array('id' => $ID)
		);
		$result = $route->compile();
		$this->assertPattern($result, '/posts/edit/1');
		$this->assertPattern($result, '/posts/view/518098');
		$this->assertNoPattern($result, '/posts/edit/name-of-post');
		$this->assertNoPattern($result, '/posts/edit/4/other:param');
		$this->assertEqual($route->keys, array('controller', 'action', 'id'));

		$route =& new CakeRoute(
			'/:lang/:controller/:action/:id',
			array('controller' => 'testing4'),
			array('id' => $ID, 'lang' => '[a-z]{3}')
		);
		$result = $route->compile();
		$this->assertPattern($result, '/eng/posts/edit/1');
		$this->assertPattern($result, '/cze/articles/view/1');
		$this->assertNoPattern($result, '/language/articles/view/2');
		$this->assertNoPattern($result, '/eng/articles/view/name-of-article');
		$this->assertEqual($route->keys, array('lang', 'controller', 'action', 'id'));

		foreach (array(':', '@', ';', '$', '-') as $delim) {
			$route =& new CakeRoute('/posts/:id' . $delim . ':title');
			$result = $route->compile();

			$this->assertPattern($result, '/posts/1' . $delim . 'name-of-article');
			$this->assertPattern($result, '/posts/13244' . $delim . 'name-of_Article[]');
			$this->assertNoPattern($result, '/posts/11!nameofarticle');
			$this->assertNoPattern($result, '/posts/11');

			$this->assertEqual($route->keys, array('id', 'title'));
		}

		$route =& new CakeRoute(
			'/posts/:id::title/:year',
			array('controller' => 'posts', 'action' => 'view'),
			array('id' => $ID, 'year' => $Year, 'title' => '[a-z-_]+')
		);
		$result = $route->compile();
		$this->assertPattern($result, '/posts/1:name-of-article/2009/');
		$this->assertPattern($result, '/posts/13244:name-of-article/1999');
		$this->assertNoPattern($result, '/posts/hey_now:nameofarticle');
		$this->assertNoPattern($result, '/posts/:nameofarticle/2009');
		$this->assertNoPattern($result, '/posts/:nameofarticle/01');
		$this->assertEqual($route->keys, array('id', 'title', 'year'));

		$route =& new CakeRoute(
			'/posts/:url_title-(uuid::id)',
			array('controller' => 'posts', 'action' => 'view'),
			array('pass' => array('id', 'url_title'), 'id' => $ID)
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
		extract(Router::getNamedExpressions());

		$route =& new CakeRoute(
			'/posts/:month/:day/:year/*',
			array('controller' => 'posts', 'action' => 'view'),
			array('year' => $Year, 'month' => $Month, 'day' => $Day)
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

		$route =& new CakeRoute(
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

		$route =& new CakeRoute(
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

		$route =& new CakeRoute('/', array('controller' => 'pages', 'action' => 'display', 'home'));
		$result = $route->match(array('controller' => 'pages', 'action' => 'display', 'home'));
		$this->assertEqual($result, '/');

		$result = $route->match(array('controller' => 'pages', 'action' => 'display', 'about'));
		$this->assertFalse($result);


		$route =& new CakeRoute('/pages/*', array('controller' => 'pages', 'action' => 'display'));
		$result = $route->match(array('controller' => 'pages', 'action' => 'display', 'home'));
		$this->assertEqual($result, '/pages/home');

		$result = $route->match(array('controller' => 'pages', 'action' => 'display', 'about'));
		$this->assertEqual($result, '/pages/about');


		$route =& new CakeRoute('/blog/:action', array('controller' => 'posts'));
		$result = $route->match(array('controller' => 'posts', 'action' => 'view'));
		$this->assertEqual($result, '/blog/view');

		$result = $route->match(array('controller' => 'nodes', 'action' => 'view'));
		$this->assertFalse($result);

		$result = $route->match(array('controller' => 'posts', 'action' => 'view', 1));
		$this->assertFalse($result);

		$result = $route->match(array('controller' => 'posts', 'action' => 'view', 'id' => 2));
		$this->assertFalse($result);


		$route =& new CakeRoute('/foo/:controller/:action', array('action' => 'index'));
		$result = $route->match(array('controller' => 'posts', 'action' => 'view'));
		$this->assertEqual($result, '/foo/posts/view');


		$route =& new CakeRoute('/:plugin/:id/*', array('controller' => 'posts', 'action' => 'view'));
		$result = $route->match(array('plugin' => 'test', 'controller' => 'posts', 'action' => 'view', 'id' => '1'));
		$this->assertEqual($result, '/test/1/');

		$result = $route->match(array('plugin' => 'fo', 'controller' => 'posts', 'action' => 'view', 'id' => '1', '0'));
		$this->assertEqual($result, '/fo/1/0');

		$result = $route->match(array('plugin' => 'fo', 'controller' => 'nodes', 'action' => 'view', 'id' => 1));
		$this->assertFalse($result);

		$result = $route->match(array('plugin' => 'fo', 'controller' => 'posts', 'action' => 'edit', 'id' => 1));
		$this->assertFalse($result);

		$route =& new CakeRoute('/admin/subscriptions/:action/*', array(
			'controller' => 'subscribe', 'admin' => true, 'prefix' => 'admin'
		));

		$url = array('controller' => 'subscribe', 'admin' => true, 'action' => 'edit', 1);
		$result = $route->match($url);
		$expected = '/admin/subscriptions/edit/1';
		$this->assertEqual($result, $expected);

		$route =& new CakeRoute('/articles/:date-from/:date-to', array(
			'controller' => 'articles', 'action' => 'index'
		));
		$url = array(
			'controller' => 'articles',
			'action' => 'index',
			'date-from' => '2009-07-31',
			'date-to' => '2010-07-31'
		);

		$result = $route->match($url);
		$expected = '/articles/2009-07-31/2010-07-31';
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


		$route =& new CakeRoute('/test2/*', array('controller' => 'pages', 'action' => 'display', 2));
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
		$route =& new CakeRoute('/:controller/:action/:id', array('plugin' => null), array('id' => '[0-9]+'));
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
 * test that patterns work for :action
 *
 * @return void
 */
	function testPatternOnAction() {
		$route =& new CakeRoute(
			'/blog/:action/*',
			array('controller' => 'blog_posts'),
			array('action' => 'other|actions')
		);
		$result = $route->match(array('controller' => 'blog_posts', 'action' => 'foo'));
		$this->assertFalse($result);

		$result = $route->match(array('controller' => 'blog_posts', 'action' => 'actions'));
		$this->assertTrue($result);

		$result = $route->parse('/blog/other');
		$expected = array('controller' => 'blog_posts', 'action' => 'other', 'pass' => array(), 'named' => array());
		$this->assertEqual($expected, $result);

		$result = $route->parse('/blog/foobar');
		$this->assertFalse($result);
	}

/**
 * test persistParams ability to persist parameters from $params and remove params.
 *
 * @return void
 */
	function testPersistParams() {
		$route =& new CakeRoute(
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
		extract(Router::getNamedExpressions());
		$route =& new CakeRoute('/:controller/:action/:id', array('controller' => 'testing4', 'id' => null), array('id' => $ID));
		$route->compile();
		$result = $route->parse('/posts/view/1');
		$this->assertEqual($result['controller'], 'posts');
		$this->assertEqual($result['action'], 'view');
		$this->assertEqual($result['id'], '1');

		$route =& new Cakeroute(
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
}

/**
 * test case for PluginShortRoute
 *
 * @package cake.tests.libs
 */
class PluginShortRouteTestCase extends  CakeTestCase {
/**
 * startTest method
 *
 * @access public
 * @return void
 */
	function startTest() {
		$this->_routing = Configure::read('Routing');
		Configure::write('Routing', array('admin' => null, 'prefixes' => array()));
		Router::reload();
	}

/**
 * end the test and reset the environment
 *
 * @return void
 **/
	function endTest() {
		Configure::write('Routing', $this->_routing);
	}

/**
 * test the parsing of routes.
 *
 * @return void
 */
	function testParsing() {
		$route =& new PluginShortRoute('/:plugin', array('action' => 'index'), array('plugin' => 'foo|bar'));

		$result = $route->parse('/foo');
		$this->assertEqual($result['plugin'], 'foo');
		$this->assertEqual($result['controller'], 'foo');
		$this->assertEqual($result['action'], 'index');

		$result = $route->parse('/wrong');
		$this->assertFalse($result, 'Wrong plugin name matched %s');
	}

/**
 * test the reverse routing of the plugin shortcut urls.
 *
 * @return void
 */
	function testMatch() {
		$route =& new PluginShortRoute('/:plugin', array('action' => 'index'), array('plugin' => 'foo|bar'));

		$result = $route->match(array('plugin' => 'foo', 'controller' => 'posts', 'action' => 'index'));
		$this->assertFalse($result, 'plugin controller mismatch was converted. %s');

		$result = $route->match(array('plugin' => 'foo', 'controller' => 'foo', 'action' => 'index'));
		$this->assertEqual($result, '/foo');
	}
}
