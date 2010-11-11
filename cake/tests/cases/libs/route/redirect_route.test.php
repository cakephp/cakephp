<?php
App::import('Core', 'route/RedirectRoute');
App::import('Core', 'CakeResponse');
App::import('Core', 'Router');
/**
 * test case for RedirectRoute
 *
 * @package cake.tests.libs.route
 */
class RedirectRouteTestCase extends  CakeTestCase {
/**
 * setUp method
 *
 * @return void
 */
	function setUp() {
		parent::setUp();
		Configure::write('Routing', array('admin' => null, 'prefixes' => array()));
		Router::reload();
	}

/**
 * test the parsing of routes.
 *
 * @return void
 */
	function testParsing() {
		$route = new RedirectRoute('/home', array('controller' => 'posts'));
		$route->response = $this->getMock('CakeResponse', array('_sendHeader'));
		$result = $route->parse('/home');
		$this->assertEqual($route->response->header(), array('Location' => Router::url('/posts', true)));

		$route = new RedirectRoute('/home', array('controller' => 'posts', 'action' => 'index'));
		$route->response = $this->getMock('CakeResponse', array('_sendHeader'));
		$result = $route->parse('/home');
		$this->assertEqual($route->response->header(), array('Location' => Router::url('/posts', true)));
		$this->assertEqual($route->response->statusCode(), 301);

		$route = new RedirectRoute('/google', 'http://google.com');
		$route->response = $this->getMock('CakeResponse', array('_sendHeader'));
		$result = $route->parse('/google');
		$this->assertEqual($route->response->header(), array('Location' => 'http://google.com'));

		$route = new RedirectRoute('/posts/*', array('controller' => 'posts', 'action' => 'view'), array('status' => 302));
		$route->response = $this->getMock('CakeResponse', array('_sendHeader'));
		$result = $route->parse('/posts/2');
		$this->assertEqual($route->response->header(), array('Location' => Router::url('/posts/view', true)));
		$this->assertEqual($route->response->statusCode(), 302);

		$route = new RedirectRoute('/posts/*', array('controller' => 'posts', 'action' => 'view'), array('persist' => true));
		$route->response = $this->getMock('CakeResponse', array('_sendHeader'));
		$result = $route->parse('/posts/2');
		$this->assertEqual($route->response->header(), array('Location' => Router::url('/posts/view/2', true)));

		$route = new RedirectRoute('/posts/*', '/test', array('persist' => true));
		$route->response = $this->getMock('CakeResponse', array('_sendHeader'));
		$result = $route->parse('/posts/2');
		$this->assertEqual($route->response->header(), array('Location' => Router::url('/test', true)));

		$route = new RedirectRoute('/my_controllers/:action/*', array('controller' => 'tags', 'action' => 'add'), array('persist' => true));
		$route->response = $this->getMock('CakeResponse', array('_sendHeader'));
		$result = $route->parse('/my_controllers/do_something/passme/named:param');
		$this->assertEqual($route->response->header(), array('Location' => Router::url('/tags/add/passme/named:param', true)));

		$route = new RedirectRoute('/my_controllers/:action/*', array('controller' => 'tags', 'action' => 'add'));
		$route->response = $this->getMock('CakeResponse', array('_sendHeader'));
		$result = $route->parse('/my_controllers/do_something/passme/named:param');
		$this->assertEqual($route->response->header(), array('Location' => Router::url('/tags/add', true)));
	}

}
