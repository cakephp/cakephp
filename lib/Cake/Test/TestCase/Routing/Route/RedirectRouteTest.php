<?php
/**
 * CakeRequest Test case file.
 *
 * PHP 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
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
class RedirectRouteTest extends  CakeTestCase {

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
		$header = $route->response->header();
		$this->assertEquals(Router::url('/posts', true), $header['Location']);

		$route = new RedirectRoute('/home', array('controller' => 'posts', 'action' => 'index'));
		$route->stop = false;
		$route->response = $this->getMock('CakeResponse', array('_sendHeader'));
		$result = $route->parse('/home');
		$header = $route->response->header();
		$this->assertEquals(Router::url('/posts', true), $header['Location']);
		$this->assertEquals(301, $route->response->statusCode());

		$route = new RedirectRoute('/google', 'http://google.com');
		$route->stop = false;
		$route->response = $this->getMock('CakeResponse', array('_sendHeader'));
		$result = $route->parse('/google');
		$header = $route->response->header();
		$this->assertEquals('http://google.com', $header['Location']);

		$route = new RedirectRoute('/posts/*', array('controller' => 'posts', 'action' => 'view'), array('status' => 302));
		$route->stop = false;
		$route->response = $this->getMock('CakeResponse', array('_sendHeader'));
		$result = $route->parse('/posts/2');
		$header = $route->response->header();
		$this->assertEquals(Router::url('/posts/view', true), $header['Location']);
		$this->assertEquals(302, $route->response->statusCode());

		$route = new RedirectRoute('/posts/*', array('controller' => 'posts', 'action' => 'view'), array('persist' => true));
		$route->stop = false;
		$route->response = $this->getMock('CakeResponse', array('_sendHeader'));
		$result = $route->parse('/posts/2');
		$header = $route->response->header();
		$this->assertEquals(Router::url('/posts/view/2', true), $header['Location']);

		$route = new RedirectRoute('/posts/*', '/test', array('persist' => true));
		$route->stop = false;
		$route->response = $this->getMock('CakeResponse', array('_sendHeader'));
		$result = $route->parse('/posts/2');
		$header = $route->response->header();
		$this->assertEquals(Router::url('/test', true), $header['Location']);

		$route = new RedirectRoute('/my_controllers/:action/*', array('controller' => 'tags', 'action' => 'add'), array('persist' => true));
		$route->stop = false;
		$route->response = $this->getMock('CakeResponse', array('_sendHeader'));
		$result = $route->parse('/my_controllers/do_something/passme/named:param');
		$header = $route->response->header();
		$this->assertEquals(Router::url('/tags/add/passme/named:param', true), $header['Location']);

		$route = new RedirectRoute('/my_controllers/:action/*', array('controller' => 'tags', 'action' => 'add'));
		$route->stop = false;
		$route->response = $this->getMock('CakeResponse', array('_sendHeader'));
		$result = $route->parse('/my_controllers/do_something/passme/named:param');
		$header = $route->response->header();
		$this->assertEquals(Router::url('/tags/add', true), $header['Location']);
	}

}
