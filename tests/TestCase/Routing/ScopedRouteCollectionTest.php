<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         3.0.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Routing;

use Cake\Routing\Route\Route;
use Cake\Routing\Router;
use Cake\Routing\ScopedRouteCollection;
use Cake\TestSuite\TestCase;

/**
 * ScopedRouteCollection test case
 */
class ScopedRouteCollectionTest extends TestCase {

/**
 * Test path()
 *
 * @return void
 */
	public function testPath() {
		$routes = new ScopedRouteCollection('/some/path');
		$this->assertEquals('/some/path', $routes->path());

		$routes = new ScopedRouteCollection('/:book_id');
		$this->assertEquals('/', $routes->path());

		$routes = new ScopedRouteCollection('/path/:book_id');
		$this->assertEquals('/path/', $routes->path());

		$routes = new ScopedRouteCollection('/path/book:book_id');
		$this->assertEquals('/path/book', $routes->path());
	}

/**
 * Test params()
 *
 * @return void
 */
	public function testParams() {
		$routes = new ScopedRouteCollection('/api', ['prefix' => 'api']);
		$this->assertEquals(['prefix' => 'api'], $routes->params());
	}

/**
 * Test getting connected routes.
 *
 * @return void
 */
	public function testRoutes() {
		$routes = new ScopedRouteCollection('/l');
		$routes->connect('/:controller', ['action' => 'index']);
		$routes->connect('/:controller/:action/*');

		$all = $routes->routes();
		$this->assertCount(2, $all);
		$this->assertInstanceOf('Cake\Routing\Route\Route', $all[0]);
		$this->assertInstanceOf('Cake\Routing\Route\Route', $all[1]);
	}

/**
 * Test getting named routes.
 *
 * @return void
 */
	public function testNamed() {
		$routes = new ScopedRouteCollection('/l');
		$routes->connect('/:controller', ['action' => 'index'], ['_name' => 'cntrl']);
		$routes->connect('/:controller/:action/*');

		$all = $routes->named();
		$this->assertCount(1, $all);
		$this->assertInstanceOf('Cake\Routing\Route\Route', $all['cntrl']);
		$this->assertEquals('/l/:controller', $all['cntrl']->template);
	}

/**
 * Test getting named routes.
 *
 * @return void
 */
	public function testGetNamed() {
		$routes = new ScopedRouteCollection('/l');
		$routes->connect('/:controller', ['action' => 'index'], ['_name' => 'cntrl']);
		$routes->connect('/:controller/:action/*');

		$this->assertFalse($routes->get('nope'));
		$route = $routes->get('cntrl');
		$this->assertInstanceOf('Cake\Routing\Route\Route', $route);
		$this->assertEquals('/l/:controller', $route->template);
	}

/**
 * Test connecting basic routes.
 *
 * @return void
 */
	public function testConnectBasic() {
		$routes = new ScopedRouteCollection('/l', ['prefix' => 'api']);

		$this->assertNull($routes->connect('/:controller'));
		$route = $routes->routes()[0];

		$this->assertInstanceOf('Cake\Routing\Route\Route', $route);
		$this->assertEquals('/l/:controller', $route->template);
		$expected = ['prefix' => 'api', 'action' => 'index', 'plugin' => null];
		$this->assertEquals($expected, $route->defaults);
	}

/**
 * Test extensions being connected to routes.
 *
 * @return void
 */
	public function testConnectExtensions() {
		$routes = new ScopedRouteCollection('/l', [], ['json']);
		$routes->connect('/:controller');
		$route = $routes->routes()[0];

		$this->assertEquals(['json'], $route->options['_ext']);
		$routes->extensions(['xml', 'json']);

		$routes->connect('/:controller/:action');
		$new = $routes->routes()[1];
		$this->assertEquals(['json'], $route->options['_ext']);
		$this->assertEquals(['xml', 'json'], $new->options['_ext']);
	}

/**
 * Test error on invalid route class
 *
 * @expectedException \Cake\Error\Exception
 * @expectedExceptionMessage Route class not found, or route class is not a subclass of
 * @return void
 */
	public function testConnectErrorInvalidRouteClass() {
		$routes = new ScopedRouteCollection('/l', [], ['json']);
		$routes->connect('/:controller', [], ['routeClass' => '\StdClass']);
	}

/**
 * Test connecting redirect routes.
 *
 * @return void
 */
	public function testRedirect() {
		$routes = new ScopedRouteCollection('/');
		$routes->redirect('/p/:id', ['controller' => 'posts', 'action' => 'view'], ['status' => 301]);
		$route = $routes->routes()[0];

		$this->assertInstanceOf('Cake\Routing\Route\RedirectRoute', $route);

		$routes->redirect('/old', '/forums', ['status' => 301]);
		$route = $routes->routes()[1];

		$this->assertInstanceOf('Cake\Routing\Route\RedirectRoute', $route);
		$this->assertEquals('/forums', $route->redirect[0]);
	}

}
