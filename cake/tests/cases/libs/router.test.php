<?php
/* SVN FILE: $Id$ */
/**
 * Short description for file.
 *
 * Long description for file
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) Tests <https://trac.cakephp.org/wiki/Developement/TestSuite>
 * Copyright 2005-2008, Cake Software Foundation, Inc.
 *								1785 E. Sahara Avenue, Suite 490-204
 *								Las Vegas, Nevada 89104
 *
 *  Licensed under The Open Group Test Suite License
 *  Redistributions of files must retain the above copyright notice.
 *
 * @filesource
 * @copyright		Copyright 2005-2008, Cake Software Foundation, Inc.
 * @link				https://trac.cakephp.org/wiki/Developement/TestSuite CakePHP(tm) Tests
 * @package			cake.tests
 * @subpackage		cake.tests.cases.libs
 * @since			CakePHP(tm) v 1.2.0.4206
 * @version			$Revision$
 * @modifiedby		$LastChangedBy$
 * @lastmodified	$Date$
 * @license			http://www.opensource.org/licenses/opengroup.php The Open Group Test Suite License
 */
uses('router', 'debugger');

/**
 * Short description for class.
 *
 * @package		cake.tests
 * @subpackage	cake.tests.cases.libs
 */
class RouterTest extends UnitTestCase {

	function setUp() {
		$this->router =& Router::getInstance();
	}

	function testReturnedInstanceReference() {
		$this->router->testVar = 'test';
		$this->assertIdentical($this->router, Router::getInstance());
		unset($this->router->testVar);
	}

	function testRouteWriting() {
		$this->router->reload();
		$this->router->connect('/');
		$this->assertEqual($this->router->routes[0][0], '/');
		$this->assertEqual($this->router->routes[0][1], '/^[\/]*$/');
		$this->assertEqual($this->router->routes[0][2], array());

		$this->router->reload();
		$this->router->connect('/', array('controller' => 'testing'));
		$this->assertTrue(is_array($this->router->routes[0][3]) && !empty($this->router->routes[0][3]));
		$this->assertEqual($this->router->routes[0][3]['controller'], 'testing');
		$this->assertEqual($this->router->routes[0][3]['action'], 'index');
		$this->assertEqual(count($this->router->routes[0][3]), 3);

		$this->router->routes = array();
		$this->router->connect('/:controller', array('controller' => 'testing2'));
		$this->assertTrue(is_array($this->router->routes[0][3]) && !empty($this->router->routes[0][3]), '/');
		$this->assertEqual($this->router->routes[0][3]['controller'], 'testing2');
		$this->assertEqual($this->router->routes[0][3]['action'], 'index');
		$this->assertEqual(count($this->router->routes[0][3]), 3);

		$this->router->routes = array();
		$this->router->connect('/:controller/:action', array('controller' => 'testing3'));
		$this->assertEqual($this->router->routes[0][0], '/:controller/:action');
		$this->assertEqual($this->router->routes[0][1], '#^(?:\/([^\/]+))?(?:\/([^\/]+))?[\/]*$#');
		$this->assertEqual($this->router->routes[0][2], array('controller', 'action'));
		$this->assertEqual($this->router->routes[0][3], array('controller' => 'testing3', 'action' => 'index', 'plugin' => null));

		$this->router->routes = array();
		$this->router->connect('/:controller/:action/:id', array('controller' => 'testing4', 'id' => null), array('id' => $this->router->__named['ID']));
		$this->assertEqual($this->router->routes[0][0], '/:controller/:action/:id');
		$this->assertEqual($this->router->routes[0][1], '#^(?:\/([^\/]+))?(?:\/([^\/]+))?(?:\/([0-9]+)?)?[\/]*$#');
		$this->assertEqual($this->router->routes[0][2], array('controller', 'action', 'id'));

		$this->router->routes = array();
		$this->router->connect('/:controller/:action/:id', array('controller' => 'testing4'), array('id' => $this->router->__named['ID']));
		$this->assertEqual($this->router->routes[0][1], '#^(?:\/([^\/]+))?(?:\/([^\/]+))?(?:\/([0-9]+))[\/]*$#');
	}

	function testRouterIdentity() {
		$this->router->reload();
		$router2 = new Router();
		$this->assertEqual(get_object_vars($this->router), get_object_vars($router2));
	}

	function testResourceRoutes() {
		$this->router->reload();
		$this->router->mapResources('Posts');

		$_SERVER['REQUEST_METHOD'] = 'GET';
		$result = $this->router->parse('/posts');
		$this->assertEqual($result, array('pass' => array(), 'named' => array(), 'plugin' => '', 'controller' => 'posts', 'action' => 'index', '[method]' => 'GET'));

		$_SERVER['REQUEST_METHOD'] = 'GET';
		$result = $this->router->parse('/posts/13');
		$this->assertEqual($result, array('pass' => array(), 'named' => array(), 'plugin' => '', 'controller' => 'posts', 'action' => 'view', 'id' => '13', '[method]' => 'GET'));

		$_SERVER['REQUEST_METHOD'] = 'POST';
		$result = $this->router->parse('/posts');
		$this->assertEqual($result, array('pass' => array(), 'named' => array(), 'plugin' => '', 'controller' => 'posts', 'action' => 'add', '[method]' => 'POST'));

		$_SERVER['REQUEST_METHOD'] = 'PUT';
		$result = $this->router->parse('/posts/13');
		$this->assertEqual($result, array('pass' => array(), 'named' => array(), 'plugin' => '', 'controller' => 'posts', 'action' => 'edit', 'id' => '13', '[method]' => 'PUT'));

		$result = $this->router->parse('/posts/475acc39-a328-44d3-95fb-015000000000');
		$this->assertEqual($result, array('pass' => array(), 'named' => array(), 'plugin' => '', 'controller' => 'posts', 'action' => 'edit', 'id' => '475acc39-a328-44d3-95fb-015000000000', '[method]' => 'PUT'));

		$_SERVER['REQUEST_METHOD'] = 'DELETE';
		$result = $this->router->parse('/posts/13');
		$this->assertEqual($result, array('pass' => array(), 'named' => array(), 'plugin' => '', 'controller' => 'posts', 'action' => 'delete', 'id' => '13', '[method]' => 'DELETE'));

		$_SERVER['REQUEST_METHOD'] = 'GET';
		$result = $this->router->parse('/posts/add');
		$this->assertEqual($result, array('pass' => array(), 'named' => array(), 'plugin' => '', 'controller' => 'posts', 'action' => 'add'));

		$this->router->reload();
		$this->router->mapResources('Posts', array('id' => '[a-z0-9_]+'));

		$_SERVER['REQUEST_METHOD'] = 'GET';
		$result = $this->router->parse('/posts/add');
		$this->assertEqual($result, array('pass' => array(), 'named' => array(), 'plugin' => '', 'controller' => 'posts', 'action' => 'view', 'id' => 'add', '[method]' => 'GET'));

		$_SERVER['REQUEST_METHOD'] = 'PUT';
		$result = $this->router->parse('/posts/name');
		$this->assertEqual($result, array('pass' => array(), 'named' => array(), 'plugin' => '', 'controller' => 'posts', 'action' => 'edit', 'id' => 'name', '[method]' => 'PUT'));
	}

	function testUrlNormalization() {
		$expected = '/users/logout';

		$result = $this->router->normalize('/users/logout/');
		$this->assertEqual($result, $expected);

		$result = $this->router->normalize('//users//logout//');
		$this->assertEqual($result, $expected);

		$result = $this->router->normalize('users/logout');
		$this->assertEqual($result, $expected);

		$result = $this->router->normalize(array('controller' => 'users', 'action' => 'logout'));
		$this->assertEqual($result, $expected);

		$result = $this->router->normalize('/');
		$this->assertEqual($result, '/');
	}

	function testUrlGeneration() {
		$this->router->reload();
		extract($this->router->getNamedExpressions());

		$this->router->connect('/', array('controller' => 'pages', 'action' => 'display', 'home'));
		$out = $this->router->url(array('controller' => 'pages', 'action' => 'display', 'home'));
		$this->assertEqual($out, '/');

		$this->router->connect('/pages/*', array('controller' => 'pages', 'action' => 'display'));
		$result = $this->router->url(array('controller' => 'pages', 'action' => 'display', 'about'));
		$expected = '/pages/about';
		$this->assertEqual($result, $expected);

		$this->router->reload();
		$this->router->parse('/');

		$this->router->connect('/:plugin/:id/*', array('controller' => 'posts', 'action' => 'view'), array('id' => $ID));
		$result = $this->router->url(array('plugin' => 'cake_plugin', 'controller' => 'posts', 'action' => 'view', 'id' => '1'));
		$expected = '/cake_plugin/1';
		$this->assertEqual($result, $expected);

		$result = $this->router->url(array('plugin' => 'cake_plugin', 'controller' => 'posts', 'action' => 'view', 'id' => '1', '0'));
		$expected = '/cake_plugin/1/0';
		$this->assertEqual($result, $expected);

		$this->router->reload();
		$this->router->parse('/');

		$this->router->connect('/:controller/:action/:id', array(), array('id' => $ID));
		$result = $this->router->url(array('controller' => 'posts', 'action' => 'view', 'id' => '1'));
		$expected = '/posts/view/1';
		$this->assertEqual($result, $expected);

		$this->router->reload();
		$this->router->parse('/');

		$this->router->connect('/:controller/:id', array('action' => 'view', 'id' => '1'));
		$result = $this->router->url(array('controller' => 'posts', 'action' => 'view', 'id' => '1'));
		$expected = '/posts/1';
		$this->assertEqual($result, $expected);

		$result = $this->router->url(array('controller' => 'posts', 'action' => 'index', '0'));
		$expected = '/posts/index/0';
		$this->assertEqual($result, $expected);

		$result = $this->router->url(array('controller' => 'posts', 'action'=>'index', '0', '?' => 'var=test&var2=test2'));
		$expected = '/posts/index/0?var=test&var2=test2';
		$this->assertEqual($result, $expected);

		$result = $this->router->url(array('controller' => 'posts', '0', '?' => 'var=test&var2=test2'));
		$this->assertEqual($result, $expected);

		$result = $this->router->url(array('controller' => 'posts', '0', '?' => array('var' => 'test', 'var2' => 'test2')));
		$this->assertEqual($result, $expected);

		$result = $this->router->url(array('controller' => 'posts', '0', '?' => 'var=test&var2=test2', '#' => 'unencoded string %'));
		$expected = '/posts/index/0?var=test&var2=test2#unencoded+string+%25';
		$this->assertEqual($result, $expected);

		$this->router->connect('/view/*',  array('controller' => 'posts', 'action' => 'view'));
		$this->router->promote();
		$result = $this->router->url(array('controller' => 'posts', 'action' => 'view', '1'));
		$expected = '/view/1';
		$this->assertEqual($result, $expected);

		Configure::write('Routing.admin', 'admin');
		$this->router->reload();
		$this->router->setRequestInfo(array(
			array(
				'pass' => array(), 'action' => 'admin_index', 'plugin' => null, 'controller' => 'subscriptions',
			    'admin' => true, 'url' => array('url' => 'admin/subscriptions/index/page:2'), 'bare' => 0, 'webservices' => ''
			),
			array(
				'base' => '/magazine', 'here' => '/magazine/admin/subscriptions/index/page:2',
				'webroot' => '/magazine/', 'passedArgs' => array('page' => 2),
				'webservices' => null
			)
		));
		$this->router->parse('/');

		$result = $this->router->url(array('page' => 3));
		$expected = '/magazine/admin/subscriptions/index/page:3';
		$this->assertEqual($result, $expected);

		Configure::write('Routing.admin', 'admin');
		$this->router->reload();
		$this->router->connect('/admin/subscriptions/:action/*', array('controller' => 'subscribe', 'admin' => true, 'prefix' => 'admin'));
		$this->router->setRequestInfo(array(
			array(
				'pass' => array(), 'action' => 'admin_index', 'plugin' => null, 'controller' => 'subscribe',
			    'admin' => true, 'url' => array('url' => 'admin/subscriptions/edit/1'), 'bare' => 0, 'webservices' => ''
			),
			array(
				'base' => '/magazine', 'here' => '/magazine/admin/subscriptions/edit/1',
				'webroot' => '/magazine/', 'passedArgs' => array('page' => 2), 'namedArgs' => array('page' => 2),
				'webservices' => null
			)
		));
		$this->router->parse('/');

		$result = $this->router->url(array('action' => 'edit', 1));
		$expected = '/magazine/admin/subscriptions/edit/1';
		$this->assertEqual($result, $expected);

		$this->router->reload();
		$this->router->setRequestInfo(array(
			array(
				'pass' => array(), 'action' => 'index', 'plugin' => null, 'controller' => 'real_controller_name',
			    'url' => array('url' => ''), 'bare' => 0, 'webservices' => ''
			),
			array(
				'base' => '/', 'here' => '/',
				'webroot' => '/', 'passedArgs' => array('page' => 2), 'namedArgs' => array('page' => 2),
				'webservices' => null
			)
		));
		$this->router->connect('short_controller_name/:action/*', array('controller' => 'real_controller_name'));
		$this->router->parse('/');

		$result = $this->router->url(array('controller' => 'real_controller_name', 'page' => '1'));
		$expected = '/short_controller_name/index/page:1';
		$this->assertEqual($result, $expected);

		$result = $this->router->url(array('action' => 'add'));
		$expected = '/short_controller_name/add';
		$this->assertEqual($result, $expected);

		$this->router->reload();

		$this->router->connect(
			':language/galleries',
			array('controller' => 'galleries', 'action' => 'index'),
			array('language' => '[a-z]{3}')
		);

		$this->router->connect(
			'/:language/:admin/:controller/:action/*',
			array('admin' => 'admin'),
			array('language' => '[a-z]{3}', 'admin' => 'admin')
		);

		$this->router->connect('/:language/:controller/:action/*',
			array(),
			array('language' => '[a-z]{3}')
		);

		$result = $this->router->url(array('admin' => false, 'language' => 'dan', 'action' => 'index', 'controller' => 'galleries'));
		$expected = '/dan/galleries';
		$this->assertEqual($result, $expected);

		$result = $this->router->url(array('admin' => false, 'language' => 'eng', 'action' => 'index', 'controller' => 'galleries'));
		$expected = '/eng/galleries';
		$this->assertEqual($result, $expected);

		$this->router->reload();
		$this->router->connect('/:language/pages',
			array(
				  'controller' => 'pages',
				  'action' => 'index'
			),
			array('language' => '[a-z]{3}')
		);

		$this->router->connect('/:language/:controller/:action/*', array(), array('language' => '[a-z]{3}'));

		$result = $this->router->url(array('language' => 'eng', 'action' => 'index', 'controller' => 'pages'));
		$expected = '/eng/pages';
		$this->assertEqual($result, $expected);

		$result = $this->router->url(array('language' => 'eng', 'controller' => 'pages'));
		$this->assertEqual($result, $expected);

		$result = $this->router->url(array('language' => 'eng', 'controller' => 'pages', 'action' => 'add'));
		$expected = '/eng/pages/add';
		$this->assertEqual($result, $expected);

        $this->router->reload();
		$this->router->parse('/');
		$this->router->setRequestInfo(array(
			array(
				'pass' => array(), 'action' => 'index', 'plugin' => null, 'controller' => 'users',
				'url' => array('url' => 'users'), 'bare' => 0, 'webservices' => ''
			),
			array(
				'base' => '/', 'here' => '/',
				'webroot' => '/', 'passedArgs' => array(), 'argSeparator' => ':', 'namedArgs' => array(),
				'webservices' => null
			)
		));

		$result = $this->router->url(array('action' => 'login'));
		$expected = '/users/login';
		$this->assertEqual($result, $expected);

		$this->router->reload();
		$this->router->parse('/');
		$this->router->connect('/page/*', array('plugin' => null, 'controller' => 'pages', 'action' => 'view'));

		$result = $this->router->url(array('plugin' => 'my_plugin', 'controller' => 'pages', 'action' => 'view', 'my-page'));
		$expected = '/my_plugin/pages/view/my-page';
		$this->assertEqual($result, $expected);

		$this->router->reload();
		$this->router->parse('/');
		$this->router->connect('/forestillinger/:month/:year/*',
			array('plugin' => 'shows', 'controller' => 'shows', 'action' => 'calendar'),
			array('month' => '0[1-9]|1[012]', 'year' => '[12][0-9]{3}')
		);

		$result = $this->router->url(array('plugin' => 'shows', 'controller' => 'shows', 'action' => 'calendar', 'month' => 10, 'year' => 2007, 'min-forestilling'));
		$expected = '/forestillinger/10/2007/min-forestilling';
		$this->assertEqual($result, $expected);

		$this->router->reload();
		$this->router->parse('/');

		$this->router->connect('/contact/:action', array('plugin' => 'contact', 'controller' => 'contact'));
		$result = $this->router->url(array('plugin' => 'contact', 'controller' => 'contact', 'action' => 'me'));

		$expected = '/contact/me';
		$this->assertEqual($result, $expected);

		Configure::write('Routing.admin', 'admin');
		$this->router->reload();
		$this->router->parse('/');

		$result = $this->router->url(array('admin' => true, 'controller' => 'users', 'action' => 'login'));
		$expected = '/admin/users/login';
		$this->assertEqual($result, $expected);

		$this->router->reload();
		$this->router->parse('/');

		$this->router->connect('/kalender/:month/:year/*',
			array('plugin' => 'shows', 'controller' => 'shows', 'action' => 'calendar'),
			array('month' => '0[1-9]|1[012]', 'year' => '[12][0-9]{3}')
		);

		$this->router->connect('/kalender/*', array('plugin' => 'shows', 'controller' => 'shows', 'action' => 'calendar'));

		$result = $this->router->url(array('plugin' => 'shows', 'controller' => 'shows', 'action' => 'calendar', 'min-forestilling'));
		$expected = '/kalender/min-forestilling';
		$this->assertEqual($result, $expected);

		$result = $this->router->url(array('plugin' => 'shows', 'controller' => 'shows', 'action' => 'calendar', 'year' => 2007, 'month' => 10, 'min-forestilling'));
		$expected = '/kalender/10/2007/min-forestilling';
		$this->assertEqual($result, $expected);

		Configure::write('Routing.admin', 'admin');
		$this->router->reload();

		$this->router->setRequestInfo(array(
			array(
				'pass' => array(), 'admin' => true, 'action' => 'index', 'plugin' => null, 'controller' => 'users',
				'url' => array('url' => 'users'), 'bare' => 0, 'webservices' => ''
			),
			array(
				'base' => '/', 'here' => '/',
				'webroot' => '/', 'passedArgs' => array(), 'argSeparator' => ':', 'namedArgs' => array(),
				'webservices' => null
			)
		));

		$this->router->connect('/page/*', array('controller' => 'pages', 'action' => 'view', 'admin' => true, 'prefix' => 'admin'));
		$this->router->parse('/');

		$result = $this->router->url(array('admin' => true, 'controller' => 'pages', 'action' => 'view', 'my-page'));
		$expected = '/page/my-page';
		$this->assertEqual($result, $expected);

		$this->router->reload();
		//Configure::write('Routing.admin', false);

		$this->router->setRequestInfo(array(
			array(
				'pass' => array(), 'action' => 'index', 'plugin' => 'myplugin', 'controller' => 'mycontroller',
			    'admin' => false, 'url' => array('url' => array()), 'bare' => 0, 'webservices' => ''
			),
			array(
				'base' => '/', 'here' => '/',
				'webroot' => '/', 'passedArgs' => array(), 'namedArgs' => array(),
				'webservices' => null
			)
		));

		$result = $this->router->url(array('plugin' => null, 'controller' => 'myothercontroller'));
		$expected = '/myothercontroller/';
		$this->assertEqual($result, $expected);

		Configure::write('Routing.admin', 'admin');

		$this->router->reload();

		$this->router->setRequestInfo(array(
			array('plugin' => null, 'controller' => 'pages', 'action' => 'admin_add', 'pass' => array(), 'prefix' => 'admin', 'admin' => true, 'form' => array(), 'url' => array('url' => 'admin/pages/add'), 'bare' => 0, 'webservices' => null),
			array('plugin' => null, 'controller' => null, 'action' => null, 'base' => '', 'here' => '/admin/pages/add', 'webroot' => '/')
		));
		$this->router->parse('/');

		$result = $this->router->url(array('plugin' => null, 'controller' => 'pages', 'action' => 'add', 'id' => false));
		$expected = '/admin/pages/add';
		$this->assertEqual($result, $expected);

		$this->router->reload();

		$this->router->setRequestInfo(array(
			array ('plugin' => null, 'controller' => 'pages', 'action' => 'admin_edit', 'pass' => array('284'), 'prefix' => 'admin', 'admin' => true, 'form' => array(), 'url' => array('url' => 'admin/pages/edit/284'), 'bare' => 0, 'webservices' => null),
			array ('plugin' => null, 'controller' => null, 'action' => null, 'base' => '', 'here' => '/admin/pages/edit/284', 'webroot' => '/')
		));

		$this->router->connect('/admin/:controller/:action/:id', array('admin' => true), array('id' => '[0-9]+'));
		$this->router->parse('/');

		$result = $this->router->url(array('plugin' => null, 'controller' => 'pages', 'action' => 'edit', 'id' => '284'));
		$expected = '/admin/pages/edit/284';
		$this->assertEqual($result, $expected);

		Configure::write('Routing.admin', 'admin');
		$this->router->reload();
		$this->router->setRequestInfo(array(
			array ('plugin' => null, 'controller' => 'pages', 'action' => 'admin_add', 'pass' => array(), 'prefix' => 'admin', 'admin' => true, 'form' => array(), 'url' => array('url' => 'admin/pages/add'), 'bare' => 0, 'webservices' => null),
			array ('plugin' => null, 'controller' => null, 'action' => null, 'base' => '', 'here' => '/admin/pages/add', 'webroot' => '/')
		));

		$this->router->parse('/');

		$result = $this->router->url(array('plugin' => null, 'controller' => 'pages', 'action' => 'add', 'id' => false));
		$expected = '/admin/pages/add';
		$this->assertEqual($result, $expected);


		$this->router->reload();
		$this->router->setRequestInfo(array(
			array('plugin' => null, 'controller' => 'pages', 'action' => 'admin_edit', 'pass' => array('284'), 'prefix' => 'admin', 'admin' => true, 'form' => array(), 'url' => array('url' => 'admin/pages/edit/284'), 'bare' => 0, 'webservices' => null),
			array('plugin' => null, 'controller' => null, 'action' => null, 'base' => '', 'here' => '/admin/pages/edit/284', 'webroot' => '/')
		));

		$this->router->parse('/');

		$result = $this->router->url(array('plugin' => null, 'controller' => 'pages', 'action' => 'edit', 'id' => '284'));
		$expected = '/admin/pages/edit/284';
		$this->assertEqual($result, $expected);

		$this->router->reload();
		$this->router->setRequestInfo(array(
				array ('plugin' => 'shows', 'controller' => 'show_tickets', 'action' => 'admin_edit', 'pass' =>
						array (0 => '6'), 'prefix' => 'admin', 'admin' => true, 'form' => array (), 'url' =>
						array ('url' => 'admin/shows/show_tickets/edit/6'), 'bare' => 0, 'webservices' => NULL),
				array ('plugin' => NULL, 'controller' => NULL, 'action' => NULL, 'base' => '', 'here' => '/admin/shows/show_tickets/edit/6', 'webroot' => '/')));

		$this->router->parse('/');

		$result = $this->router->url(array ( 'plugin' => 'shows', 'controller' => 'show_tickets', 'action' => 'edit', 'id' => '6', 'admin' => true, 'prefix' => 'admin', ));
		$expected = '/admin/shows/show_tickets/edit/6';
		$this->assertEqual($result, $expected);
	}

	function testUrlGenerationWithExtensions() {
		$this->router->reload();
		$this->router->parse('/');
		$result = $this->router->url(array('plugin' => null, 'controller' => 'articles', 'action' => 'add', 'id' => null, 'ext' => 'json'));
		$expected = '/articles/add.json';
		$this->assertEqual($result, $expected);

		$result = $this->router->url(array('plugin' => null, 'controller' => 'articles', 'action' => 'add', 'ext' => 'json'));
		$expected = '/articles/add.json';
		$this->assertEqual($result, $expected);

		$result = $this->router->url(array('plugin' => null, 'controller' => 'articles', 'action' => 'index', 'id' => null, 'ext' => 'json'));
		$expected = '/articles.json';
		$this->assertEqual($result, $expected);

		$result = $this->router->url(array('plugin' => null, 'controller' => 'articles', 'action' => 'index', 'ext' => 'json'));
		$expected = '/articles.json';
		$this->assertEqual($result, $expected);
	}

	function testPluginUrlGeneration() {
		$this->router->setRequestInfo(array(
			array(
				'controller' => 'controller', 'action' => 'index', 'form' => array(),
				'url' => array(), 'bare' => 0, 'webservices' => null, 'plugin' => 'test'
			),
			array(
				'base' => '/base', 'here' => '/clients/sage/portal/donations', 'webroot' => '/base/',
				'passedArgs' => array(), 'argSeparator' => ':', 'namedArgs' => array(), 'webservices' => null
			)
		));

		$this->assertEqual($this->router->url('read/1'), '/base/test/controller/read/1');
		$this->router->reload();
	}

	function testUrlParsing() {
		extract($this->router->getNamedExpressions());

		$this->router->connect('/posts/:value/:somevalue/:othervalue/*', array('controller' => 'posts', 'action' => 'view'), array('value','somevalue', 'othervalue'));
		$result = $this->router->parse('/posts/2007/08/01/title-of-post-here');
		$expected = array('value' => '2007', 'somevalue' => '08', 'othervalue' => '01', 'controller' => 'posts', 'action' => 'view', 'plugin' =>'', 'pass' => array('0' => 'title-of-post-here'), 'named' => array());
		$this->assertEqual($result, $expected);

		$this->router->routes = array();
		$this->router->connect('/posts/:year/:month/:day/*', array('controller' => 'posts', 'action' => 'view'), array('year' => $Year, 'month' => $Month, 'day' => $Day));
		$result = $this->router->parse('/posts/2007/08/01/title-of-post-here');
		$expected = array('year' => '2007', 'month' => '08', 'day' => '01', 'controller' => 'posts', 'action' => 'view', 'plugin' =>'', 'pass' => array('0' => 'title-of-post-here'), 'named' => array());
		$this->assertEqual($result, $expected);

		$this->router->routes = array();
		$this->router->connect('/posts/:day/:year/:month/*', array('controller' => 'posts', 'action' => 'view'), array('year' => $Year, 'month' => $Month, 'day' => $Day));
		$result = $this->router->parse('/posts/01/2007/08/title-of-post-here');
		$expected = array('day' => '01', 'year' => '2007', 'month' => '08', 'controller' => 'posts', 'action' => 'view', 'plugin' =>'', 'pass' => array('0' => 'title-of-post-here'), 'named' => array());
		$this->assertEqual($result, $expected);

		$this->router->routes = array();
		$this->router->connect('/posts/:month/:day/:year//*', array('controller' => 'posts', 'action' => 'view'), array('year' => $Year, 'month' => $Month, 'day' => $Day));
		$result = $this->router->parse('/posts/08/01/2007/title-of-post-here');
		$expected = array('month' => '08', 'day' => '01', 'year' => '2007', 'controller' => 'posts', 'action' => 'view', 'plugin' =>'', 'pass' => array('0' => 'title-of-post-here'), 'named' => array());
		$this->assertEqual($result, $expected);

		$this->router->routes = array();
		$this->router->connect('/posts/:year/:month/:day/*', array('controller' => 'posts', 'action' => 'view'));
		$result = $this->router->parse('/posts/2007/08/01/title-of-post-here');
		$expected = array('year' => '2007', 'month' => '08', 'day' => '01', 'controller' => 'posts', 'action' => 'view', 'plugin' =>'', 'pass' => array('0' => 'title-of-post-here'), 'named' => array());
		$this->assertEqual($result, $expected);

		$this->router->reload();
		$result = $this->router->parse('/pages/display/home');
		$expected = array('plugin' => null, 'pass' => array('home'), 'controller' => 'pages', 'action' => 'display', 'named' => array());
		$this->assertEqual($result, $expected);

		$result = $this->router->parse('pages/display/home/');
		$this->assertEqual($result, $expected);

		$result = $this->router->parse('pages/display/home');
		$this->assertEqual($result, $expected);

		$this->router->reload();
		$this->router->connect('/page/*', array('controller' => 'test'));
		$result = $this->router->parse('/page/my-page');
		$expected = array('pass' => array('my-page'), 'plugin' => null, 'controller' => 'test', 'action' => 'index');

		$this->router->reload();
		$this->router->connect('/:language/contact', array('language' => 'eng', 'plugin' => 'contact', 'controller' => 'contact', 'action' => 'index'), array('language' => '[a-z]{3}'));
		$result = $this->router->parse('/eng/contact');
		$expected = array('pass' => array(), 'named' => array(), 'language' => 'eng', 'plugin' => 'contact', 'controller' => 'contact', 'action' => 'index');
		$this->assertEqual($result, $expected);

		$this->router->reload();
		$this->router->connect('/forestillinger/:month/:year/*',
			array('plugin' => 'shows', 'controller' => 'shows', 'action' => 'calendar'),
			array('month' => '0[1-9]|1[012]', 'year' => '[12][0-9]{3}')
		);

		$result = $this->router->parse('/forestillinger/10/2007/min-forestilling');
		$expected = array('pass' => array('min-forestilling'), 'plugin' => 'shows', 'controller' => 'shows', 'action' => 'calendar', 'year' => 2007, 'month' => 10, 'named' => array());
		$this->assertEqual($result, $expected);

	}

	function testUuidRoutes() {
		$this->router->reload();
		$this->router->connect(
		    '/subjects/add/:category_id',
		    array('controller' => 'subjects', 'action' => 'add'),
		    array('category_id' => '\w{8}-\w{4}-\w{4}-\w{4}-\w{12}')
		);
 		$result = $this->router->parse('/subjects/add/4795d601-19c8-49a6-930e-06a8b01d17b7');
		$expected = array('pass' => array(), 'named' => array(), 'category_id' => '4795d601-19c8-49a6-930e-06a8b01d17b7', 'plugin' => null, 'controller' => 'subjects', 'action' => 'add');
		$this->assertEqual($result, $expected);
	}

	function testRouteSymmetry() {
		$this->router->reload();

		Router::connect(
		    "/:extra/page/:slug/*",
		    array('controller' => 'pages', 'action' => 'view', 'extra' => null),
		    array("extra" => '[a-z1-9_]*', "slug" => '[a-z1-9_]+', "action" => 'view')
		);

		$result = $this->router->parse('/some_extra/page/this_is_the_slug');
		$expected = array('pass' => array(), 'named' => array(), 'plugin' => null, 'controller' => 'pages', 'action' => 'view', 'slug' => 'this_is_the_slug', 'extra' => 'some_extra');
		$this->assertEqual($result, $expected);

		$result = $this->router->parse('/page/this_is_the_slug');
		$expected = array( 'pass' => array(), 'named' => array(), 'plugin' => null, 'controller' => 'pages', 'action' => 'view', 'slug' => 'this_is_the_slug', 'extra' => null);
		$this->assertEqual($result, $expected);

		$this->router->reload();

		$this->router->connect(
		    "/:extra/page/:slug/*",
		    array('controller' => 'pages', 'action' => 'view', 'extra' => null),
		    array("extra" => '[a-z1-9_]*', "slug" => '[a-z1-9_]+')
		);
		$this->router->parse('/');

		$result = $this->router->url(array('admin' => null, 'plugin' => null, 'controller' => 'pages', 'action' => 'view', 'slug' => 'this_is_the_slug', 'extra' => null));
		$expected = '/page/this_is_the_slug';
		$this->assertEqual($result, $expected);

		$result = $this->router->url(array('admin' => null, 'plugin' => null, 'controller' => 'pages', 'action' => 'view', 'slug' => 'this_is_the_slug', 'extra' => 'some_extra'));
		$expected = '/some_extra/page/this_is_the_slug';
		$this->assertEqual($result, $expected);
	}

	function testAdminRouting() {
		Configure::write('Routing.admin', 'admin');
		$this->router->reload();
		$this->router->parse('/');

		$this->router->reload();
		$this->router->connect('/admin', array('admin' => true, 'controller' => 'users'));
		$result = $this->router->parse('/admin');
		$expected = array('pass' => array(), 'named' => array(), 'plugin' => '', 'controller' => 'users', 'action' => 'index', 'admin' => true, 'prefix' => 'admin');
		$this->assertEqual($result, $expected);


		$result = $this->router->url(array('admin' => true, 'controller' => 'posts', 'action' => 'index', '0', '?' => 'var=test&var2=test2'));
		$expected = '/admin/posts/index/0?var=test&var2=test2';
		$this->assertEqual($result, $expected);

		$this->router->reload();
		$this->router->parse('/');
		$result = $this->router->url(array('admin' => false, 'controller' => 'posts', 'action' => 'index', '0', '?' => 'var=test&var2=test2'));
		$expected = '/posts/index/0?var=test&var2=test2';
		$this->assertEqual($result, $expected);

		$this->router->reload();
		$this->router->setRequestInfo(array(
			array('admin' => true, 'controller' => 'controller', 'action' => 'index', 'form' => array(), 'url' => array(), 'bare' => 0, 'webservices' => null, 'plugin' => null),
			array('base' => '/', 'here' => '/', 'webroot' => '/base/', 'passedArgs' => array(), 'argSeparator' => ':', 'namedArgs' => array(), 'webservices' => null)
		));

		$this->router->parse('/');
		$result = $this->router->url(array('admin' => false, 'controller' => 'posts', 'action' => 'index', '0', '?' => 'var=test&var2=test2'));
		$expected = '/posts/index/0?var=test&var2=test2';
		$this->assertEqual($result, $expected);

		$result = $this->router->url(array('controller' => 'posts', 'action' => 'index', '0', '?' => 'var=test&var2=test2'));
		$expected = '/admin/posts/index/0?var=test&var2=test2';
		$this->assertEqual($result, $expected);

		$this->router->reload();
		$result = $this->router->parse('admin/users/view/');
		$expected = array('pass' => array(), 'named' => array(), 'controller' => 'users', 'action' => 'view', 'plugin' => null, 'prefix' => 'admin', 'admin' => true);
		$this->assertEqual($result, $expected);

		Configure::write('Routing.admin', 'beheer');

		$this->router->reload();
		$this->router->setRequestInfo(array(
			array('beheer' => true, 'controller' => 'posts', 'action' => 'index', 'form' => array(), 'url' => array(), 'bare' => 0, 'webservices' => null, 'plugin' => null),
			array('base' => '/', 'here' => '/beheer/posts/index', 'webroot' => '/', 'passedArgs' => array(), 'argSeparator' => ':', 'namedArgs' => array(), 'webservices' => null)
		));

		$result = $this->router->parse('beheer/users/view/');
		$expected = array('pass' => array(), 'named' => array(), 'controller' => 'users', 'action' => 'view', 'plugin' => null, 'prefix' => 'beheer', 'beheer' => true);
		$this->assertEqual($result, $expected);


		$result = $this->router->url(array('controller' => 'posts', 'action' => 'index', '0', '?' => 'var=test&var2=test2'));
		$expected = '/beheer/posts/index/0?var=test&var2=test2';
		$this->assertEqual($result, $expected);
	}

	function testExtensionParsingSetting() {
		$router = Router::getInstance();
		$this->router->reload();
		$this->assertFalse($this->router->__parseExtensions);

		$router->parseExtensions();
		$this->assertTrue($this->router->__parseExtensions);
	}

	function testExtensionParsing() {
		$this->router->reload();
		$this->router->parseExtensions();

		$result = $this->router->parse('/posts.rss');
		$expected = array('plugin' => null, 'controller' => 'posts', 'action' => 'index', 'url' => array('ext' => 'rss'), 'pass'=> array(), 'named' => array());
		$this->assertEqual($result, $expected);

		$result = $this->router->parse('/posts/view/1.rss');
		$expected = array('plugin' => null, 'controller' => 'posts', 'action' => 'view', 'pass' => array('1'), 'named' => array(), 'url' => array('ext' => 'rss'), 'named' => array());
		$this->assertEqual($result, $expected);

		$result = $this->router->parse('/posts/view/1.rss?query=test');
		$this->assertEqual($result, $expected);

		$result = $this->router->parse('/posts/view/1.atom');
		$expected['url'] = array('ext' => 'atom');
		$this->assertEqual($result, $expected);

		$this->router->reload();
		$this->router->parseExtensions('rss', 'xml');

		$result = $this->router->parse('/posts.xml');
		$expected = array('plugin' => null, 'controller' => 'posts', 'action' => 'index', 'url' => array('ext' => 'xml'), 'pass'=> array(), 'named' => array());
		$this->assertEqual($result, $expected);

		$result = $this->router->parse('/posts.atom?hello=goodbye');
		$expected = array('plugin' => null, 'controller' => 'posts.atom', 'action' => 'index', 'pass' => array(), 'named' => array());
		$this->assertEqual($result, $expected);

		$this->router->reload();
		$this->router->parseExtensions();
		$result = $this->router->__parseExtension('/posts.atom');
		$expected = array('ext' => 'atom', 'url' => '/posts');
		$this->assertEqual($result, $expected);
	}

	function testQuerystringGeneration() {
		$result = $this->router->url(array('controller' => 'posts', 'action'=>'index', '0', '?' => 'var=test&var2=test2'));
		$expected = '/posts/index/0?var=test&var2=test2';
		$this->assertEqual($result, $expected);

		$result = $this->router->url(array('controller' => 'posts', 'action'=>'index', '0', '?' => array('var' => 'test', 'var2' => 'test2')));
		$this->assertEqual($result, $expected);

		$expected .= '&more=test+data';
		$result = $this->router->url(array('controller' => 'posts', 'action'=>'index', '0', '?' => array('var' => 'test', 'var2' => 'test2', 'more' => 'test data')));
		$this->assertEqual($result, $expected);
	}

	function testNamedArgsUrlGeneration() {
		$this->router->setRequestInfo(array(null, array('base' => '/', 'argSeparator' => ':')));
		$this->router->connectNamed(array(
			'published' => array('regex' => '[01]'),
			'deleted' => array('regex' => '[01]')
		));
		$this->router->parse('/');

		$result = $this->router->url(array('controller' => 'posts', 'action' => 'index', 'published' => 1, 'deleted' => 1));
		$expected = '/posts/index/published:1/deleted:1';
		$this->assertEqual($result, $expected);

		$result = $this->router->url(array('controller' => 'posts', 'action' => 'index', 'published' => 0, 'deleted' => 0));
		$expected = '/posts/index/published:0/deleted:0';
		$this->assertEqual($result, $expected);

		$this->router->reload();
		extract($this->router->getNamedExpressions());
		$this->router->setRequestInfo(array(null, array('base' => '/', 'argSeparator' => ':')));
		$this->router->connectNamed(array('file'=> '[\w\.\-]+\.(html|png)'));
		$this->router->connect('/', array('controller' => 'graphs', 'action' => 'index'));
		$this->router->connect('/:id/*', array('controller' => 'graphs', 'action' => 'view'), array('id' => $ID));

		$result = $this->router->url(array('controller' => 'graphs', 'action' => 'view', 'id' => 12, 'file' => 'asdf.png'));
		$expected = '/12/file:asdf.png';
		$this->assertEqual($result, $expected);

		Configure::write('Routing.admin', 'admin');

		$this->router->reload();
		$this->router->setRequestInfo(array(
			array('admin' => true, 'controller' => 'controller', 'action' => 'index', 'form' => array(), 'url' => array(), 'bare' => 0, 'webservices' => null, 'plugin' => null),
			array('base' => '/', 'here' => '/', 'webroot' => '/base/', 'passedArgs' => array(), 'argSeparator' => ':', 'namedArgs' => array(), 'webservices' => null)
		));
		$this->router->parse('/');

		$result = $this->router->url(array('page' => 1, 0 => null, 'sort' => 'controller', 'direction' => 'asc', 'order' => null));
		$expected = "/admin/controller/index/page:1/sort:controller/direction:asc";
		$this->assertEqual($result, $expected);

		$this->router->reload();
		$this->router->setRequestInfo(array(
			array('admin' => true, 'controller' => 'controller', 'action' => 'index', 'form' => array(), 'url' => array(), 'bare' => 0, 'webservices' => null, 'plugin' => null),
			array('base' => '/', 'here' => '/', 'webroot' => '/base/', 'passedArgs' => array('type'=> 'whatever'), 'argSeparator' => ':', 'namedArgs' => array('type'=> 'whatever'), 'webservices' => null)
		));

		$this->router->connectNamed(array('type'));

		$this->router->parse('/admin/controller/index/type:whatever');

		$result = $this->router->url(array('type'=> 'new'));
		$expected = "/admin/controller/index/type:new";
		$this->assertEqual($result, $expected);
	}

	function testNamedArgsUrlParsing() {
		$result = $this->router->parse('/controller/action/param1:value1:1/param2:value2:3/param:value');
		$expected = array('pass' => array(), 'named' => array('param1' => 'value1:1', 'param2' => 'value2:3', 'param' => 'value'), 'controller' => 'controller', 'action' => 'action', 'plugin' => null);
		$this->assertEqual($result, $expected);
	}

	function testUrlGenerationWithPrefixes() {
		$this->router->reload();

		$this->router->connect('/protected/:controller/:action/*', array(
			'controller'    => 'users',
			'action'        => 'index',
			'prefix'        => 'protected',
			'protected'		=> true
		));
		$this->router->parse('/');

        $this->router->setRequestInfo(array(
            array('plugin' => null, 'controller' => 'images', 'action' => 'index', 'pass' => array(), 'prefix' => null, 'admin' => false, 'form' => array(), 'url' => array('url' => 'images/index'), 'bare' => 0, 'webservices' => null),
			array('plugin' => null, 'controller' => null, 'action' => null, 'base' => '', 'here' => '/images/index', 'webroot' => '/')
		));

        $result = $this->router->url(array('controller' => 'images', 'action' => 'add'));
        $expected = '/images/add';
        $this->assertEqual($result, $expected);

        $result = $this->router->url(array('controller' => 'images', 'action' => 'add', 'protected' => true));
        $expected = '/protected/images/add';
        $this->assertEqual($result, $expected);
	}

	function testRemoveBase() {
		$this->router->reload();
		$this->router->setRequestInfo(array(
			array('controller' => 'controller', 'action' => 'index', 'form' => array(), 'url' => array(), 'bare' => 0, 'webservices' => null, 'plugin' => null),
			array('base' => '/base', 'here' => '/', 'webroot' => '/base/', 'passedArgs' => array(), 'argSeparator' => ':', 'namedArgs' => array(), 'webservices' => null)
		));

		$result = $this->router->url(array('controller' => 'my_controller', 'action' => 'my_action'));
		$expected = '/base/my_controller/my_action/';
		$this->assertEqual($result, $expected);

		$result = $this->router->url(array('controller' => 'my_controller', 'action' => 'my_action', 'base' => false));
		$expected = '/my_controller/my_action/';
		$this->assertEqual($result, $expected);

		$result = $this->router->url(array('controller' => 'my_controller', 'action' => 'my_action', 'base' => true));
		$expected = '/base/my_controller/my_action/base:1';
		$this->assertEqual($result, $expected);
	}

	function testParamsUrlParsing() {
		$this->router->reload();
		$this->router->connect('/', array('controller' => 'posts', 'action' => 'index'));
		$this->router->connect('/view/:user/*', array('controller' => 'posts', 'action' => 'view'), array('user'));
		$result = $this->router->parse('/view/gwoo/');
		$expected = array('user' => 'gwoo', 'controller' => 'posts', 'action' => 'view', 'plugin' =>'', 'pass' => array(), 'named' => array());
		$this->assertEqual($result, $expected);

		$this->router->reload();
		$this->router->connect('/([0-9]+)-p-(.*)/', array('controller' => 'products', 'action' => 'show'));
		$this->router->connect('/(.*)-q-(.*)/', array('controller' => 'products', 'action' => 'show'));
		$result = $this->router->parse('/100-p-500/');
		$expected = array('pass' => array('100', '500'), 'named' => array(), 'controller' => 'products', 'action' => 'show', 'plugin' => null);
		$this->assertEqual($result, $expected);

		$result = $this->router->parse('/bob-q-500/');
		$expected = array('pass' => array('bob', '500'), 'named' => array(), 'controller' => 'products', 'action' => 'show', 'plugin' => null);
		$this->assertEqual($result, $expected);

		$result = $this->router->parse('/bob-p-500/');
		$expected = array('pass' => array(), 'named' => array(), 'controller' => 'bob-p-500', 'plugin' => null, 'action' => 'index');
		$this->assertEqual($result, $expected);
	}

	function testPagesUrlParsing() {
		$this->router->reload();
		$this->router->connect('/', array('controller' => 'pages', 'action' => 'display', 'home'));
		$this->router->connect('/pages/*', array('controller' => 'pages', 'action' => 'display'));

		$result = $this->router->parse('/');
		$expected = array('pass'=>array('home'), 'named' => array(), 'plugin' => null, 'controller' => 'pages', 'action' => 'display');
		$this->assertEqual($result, $expected);

		$result = $this->router->parse('/pages/home/');
		$expected = array('pass' => array('home'), 'named' => array(), 'plugin' => null, 'controller' => 'pages', 'action' => 'display', 'named' => array());
		$this->assertEqual($result, $expected);

		$this->router->reload();
		$this->router->connect('/', array('controller' => 'posts', 'action' => 'index'));
		$this->router->connect('/pages/*', array('controller' => 'pages', 'action' => 'display'));
		$result = $this->router->parse('/pages/contact/');

		$expected = array('pass'=>array('contact'), 'named' => array(), 'plugin'=> null, 'controller'=>'pages', 'action'=>'display', 'named' => array());
		$this->assertEqual($result, $expected);
	}

	function testParsingWithPrefixes() {
		$this->router->reload();
		$adminParams = array('prefix' => 'admin', 'admin' => true);
		$this->router->connect('/admin/:controller', $adminParams);
		$this->router->connect('/admin/:controller/:action', $adminParams);
		$this->router->connect('/admin/:controller/:action/*', $adminParams);

		$this->router->setRequestInfo(array(
			array('controller' => 'controller', 'action' => 'index', 'form' => array(), 'url' => array(), 'bare' => 0, 'webservices' => null, 'plugin' => null),
			array('base' => '/base', 'here' => '/', 'webroot' => '/base/', 'passedArgs' => array(), 'argSeparator' => ':', 'namedArgs' => array(), 'webservices' => null)
		));

		$result = $this->router->parse('/admin/posts/');
		$expected = array('pass' => array(), 'named' => array(), 'prefix' => 'admin', 'plugin' => null, 'controller' => 'posts', 'action' => 'index', 'admin' => true);
		$this->assertEqual($result, $expected);

		$result = $this->router->parse('/admin/posts');
		$this->assertEqual($result, $expected);

		$result = $this->router->url(array('admin' => true, 'controller' => 'posts'));
		$expected = '/base/admin/posts';
		$this->assertEqual($result, $expected);

		$result = $this->router->prefixes();
		$expected = array('admin');
		$this->assertEqual($result, $expected);
	}

	function testPassedArgsOrder() {
		$this->router->reload();

		$this->router->connect('/test2/*', array('controller' => 'pages', 'action' => 'display', 2));
		$this->router->connect('/test/*', array('controller' => 'pages', 'action' => 'display', 1));
		$this->router->parse('/');

		$result = $this->router->url(array('controller' => 'pages', 'action' => 'display', 1, 'whatever'));
		$expected = '/test/whatever';
		$this->assertEqual($result, $expected);

		$result = $this->router->url(array('controller' => 'pages', 'action' => 'display', 2, 'whatever'));
		$expected = '/test2/whatever';
		$this->assertEqual($result, $expected);

		$this->router->reload();

        $this->router->setRequestInfo(array(
            array('plugin' => null, 'controller' => 'images', 'action' => 'index', 'pass' => array(), 'named' => array(), 'prefix' => 'protected', 'admin' => false,  'form' => array(), 'url' => array ('url' => 'protected/images/index'), 'bare' => 0, 'webservices' => null),
			array('plugin' => null, 'controller' => null, 'action' => null, 'base' => '', 'here' => '/protected/images/index', 'webroot' => '/')
		));

		$this->router->connect('/protected/:controller/:action/*', array(
			'controller'    => 'users',
			'action'        => 'index',
			'prefix'        => 'protected'
		));

		$this->router->parse('/');
        $result = $this->router->url(array('controller' => 'images', 'action' => 'add'));
        $expected = '/protected/images/add';
        $this->assertEqual($result, $expected);

		$result = $this->router->prefixes();
		$expected = array('protected', 'admin');
		$this->assertEqual($result, $expected);
	}

	function testRegexRouteMatching() {
		$this->router->reload();
		$this->router->connect('/:locale/:controller/:action/*', array(), array('locale' => 'dan|eng'));

		$result = $this->router->parse('/test/test_action');
		$expected = array('pass' => array(), 'named' => array(), 'controller' => 'test', 'action' => 'test_action', 'plugin' => null);
		$this->assertEqual($result, $expected);

		$result = $this->router->parse('/eng/test/test_action');
		$expected = array('pass' => array(), 'named' => array(), 'locale' => 'eng', 'controller' => 'test', 'action' => 'test_action', 'plugin' => null);
		$this->assertEqual($result, $expected);

		$result = $this->router->parse('/badness/test/test_action');
		$expected = array('pass' => array('test_action'), 'named' => array(), 'controller' => 'badness', 'action' => 'test', 'plugin' => null);
		$this->assertEqual($result, $expected);

		$this->router->reload();
		$this->router->connect('/:locale/:controller/:action/*', array(), array('locale' => 'dan|eng'));

		$this->router->setRequestInfo(array(
			array('plugin' => null, 'controller' => 'test', 'action' => 'index', 'pass' => array(), 'form' => array(), 'url' => array ('url' => 'test/test_action'), 'bare' => 0, 'webservices' => null),
			array('plugin' => null, 'controller' => null, 'action' => null, 'base' => '', 'here' => '/test/test_action', 'webroot' => '/')
		));

		$result = $this->router->url(array('action' => 'test_another_action'));
		$expected = '/test/test_another_action/';
		$this->assertEqual($result, $expected);

		$result = $this->router->url(array('action' => 'test_another_action', 'locale' => 'eng'));
		$expected = '/eng/test/test_another_action';
		$this->assertEqual($result, $expected);

		$result = $this->router->url(array('action' => 'test_another_action', 'locale' => 'badness'));
		$expected = '/test/test_another_action/locale:badness';
		$this->assertEqual($result, $expected);
	}

	function testMultiResourceRoute() {
		$this->router->reload();
		$this->router->connect('/:controller', array('action' => 'index', '[method]' => array('GET', 'POST')));

		$_SERVER['REQUEST_METHOD'] = 'GET';
		$result = $this->router->parse('/posts');
		$this->assertEqual($result, array('pass' => array(), 'named' => array(), 'plugin' => '', 'controller' => 'posts', 'action' => 'index', '[method]' => array('GET', 'POST')));

		$_SERVER['REQUEST_METHOD'] = 'POST';
		$result = $this->router->parse('/posts');
		$this->assertEqual($result, array('pass' => array(), 'named' => array(), 'plugin' => '', 'controller' => 'posts', 'action' => 'index', '[method]' => array('GET', 'POST')));
	}
}
?>