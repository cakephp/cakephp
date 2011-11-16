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

App::uses('RedirectRoute', 'Routing/Route');
App::uses('CakeResponse', 'Network');
App::uses('Router', 'Routing');

/**
 * test case for RedirectRoute
 *
 * @package       Cake.Test.Case.Routing.Route
 */
class RedirectRouteTestCase extends  CakeTestCase {
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
 * test the parsing of routes.
 *
 * @return void
 */
	public function testParsing() {
		$route = new RedirectRoute('/home', array('controller' => 'posts'));
		$route->stop = false;
		$route->response = $this->getMock('CakeResponse', array('_sendHeader'));
		$result = $route->parse('/home');
		$this->assertEquals($route->response->header(), array('Location' => Router::url('/posts', true)));

		$route = new RedirectRoute('/home', array('controller' => 'posts', 'action' => 'index'));
		$route->stop = false;
		$route->response = $this->getMock('CakeResponse', array('_sendHeader'));
		$result = $route->parse('/home');
		$this->assertEquals($route->response->header(), array('Location' => Router::url('/posts', true)));
		$this->assertEquals($route->response->statusCode(), 301);

		$route = new RedirectRoute('/google', 'http://google.com');
		$route->stop = false;
		$route->response = $this->getMock('CakeResponse', array('_sendHeader'));
		$result = $route->parse('/google');
		$this->assertEquals($route->response->header(), array('Location' => 'http://google.com'));

		$route = new RedirectRoute('/posts/*', array('controller' => 'posts', 'action' => 'view'), array('status' => 302));
		$route->stop = false;
		$route->response = $this->getMock('CakeResponse', array('_sendHeader'));
		$result = $route->parse('/posts/2');
		$this->assertEquals($route->response->header(), array('Location' => Router::url('/posts/view', true)));
		$this->assertEquals($route->response->statusCode(), 302);

		$route = new RedirectRoute('/posts/*', array('controller' => 'posts', 'action' => 'view'), array('persist' => true));
		$route->stop = false;
		$route->response = $this->getMock('CakeResponse', array('_sendHeader'));
		$result = $route->parse('/posts/2');
		$this->assertEquals($route->response->header(), array('Location' => Router::url('/posts/view/2', true)));

		$route = new RedirectRoute('/posts/*', '/test', array('persist' => true));
		$route->stop = false;
		$route->response = $this->getMock('CakeResponse', array('_sendHeader'));
		$result = $route->parse('/posts/2');
		$this->assertEquals($route->response->header(), array('Location' => Router::url('/test', true)));

		$route = new RedirectRoute('/my_controllers/:action/*', array('controller' => 'tags', 'action' => 'add'), array('persist' => true));
		$route->stop = false;
		$route->response = $this->getMock('CakeResponse', array('_sendHeader'));
		$result = $route->parse('/my_controllers/do_something/passme/named:param');
		$this->assertEquals($route->response->header(), array('Location' => Router::url('/tags/add/passme/named:param', true)));

		$route = new RedirectRoute('/my_controllers/:action/*', array('controller' => 'tags', 'action' => 'add'));
		$route->stop = false;
		$route->response = $this->getMock('CakeResponse', array('_sendHeader'));
		$result = $route->parse('/my_controllers/do_something/passme/named:param');
		$this->assertEquals($route->response->header(), array('Location' => Router::url('/tags/add', true)));
	}

}
