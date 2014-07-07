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
 * Test connecting an instance routes.
 *
 * @return void
 */
	public function testConnectInstance() {
		$routes = new ScopedRouteCollection('/l', ['prefix' => 'api']);

		$route = new Route('/:controller');
		$this->assertNull($routes->connect($route));

		$result = $routes->routes()[0];
		$this->assertSame($route, $result);
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
		$this->assertEquals(['json'], $routes->extensions());

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
 * Test conflicting parameters raises an exception.
 *
 * @expectedException \Cake\Error\Exception
 * @expectedExceptionMessage You cannot define routes that conflict with the scope.
 * @return void
 */
	public function testConnectConflictingParameters() {
		$routes = new ScopedRouteCollection('/admin', ['prefix' => 'admin'], []);
		$routes->connect('/', ['prefix' => 'manager', 'controller' => 'Dashboard', 'action' => 'view']);
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

/**
 * Test creating sub-scopes with prefix()
 *
 * @return void
 */
	public function testPrefix() {
		$routes = new ScopedRouteCollection('/path', ['key' => 'value']);
		$res = $routes->prefix('admin', function($r) {
			$this->assertInstanceOf('Cake\Routing\ScopedRouteCollection', $r);
			$this->assertCount(0, $r->routes());
			$this->assertEquals('/path/admin', $r->path());
			$this->assertEquals(['prefix' => 'admin', 'key' => 'value'], $r->params());
		});
		$this->assertNull($res);
	}

/**
 * Test creating sub-scopes with prefix()
 *
 * @return void
 */
	public function testNestedPrefix() {
		$routes = new ScopedRouteCollection('/admin', ['prefix' => 'admin']);
		$res = $routes->prefix('api', function($r) {
			$this->assertEquals('/admin/api', $r->path());
			$this->assertEquals(['prefix' => 'admin/api'], $r->params());
		});
		$this->assertNull($res);
	}

/**
 * Test creating sub-scopes with plugin()
 *
 * @return void
 */
	public function testNestedPlugin() {
		$routes = new ScopedRouteCollection('/b', ['key' => 'value']);
		$res = $routes->plugin('Contacts', function($r) {
			$this->assertEquals('/b/contacts', $r->path());
			$this->assertEquals(['plugin' => 'Contacts', 'key' => 'value'], $r->params());

			$r->connect('/:controller');
			$route = $r->routes()[0];
			$this->assertEquals(
				['key' => 'value', 'plugin' => 'Contacts', 'action' => 'index'],
				$route->defaults
			);
		});
		$this->assertNull($res);
	}

/**
 * Test creating sub-scopes with plugin() + path option
 *
 * @return void
 */
	public function testNestedPluginPathOption() {
		$routes = new ScopedRouteCollection('/b', ['key' => 'value']);
		$routes->plugin('Contacts', ['path' => '/people'], function($r) {
			$this->assertEquals('/b/people', $r->path());
			$this->assertEquals(['plugin' => 'Contacts', 'key' => 'value'], $r->params());
		});
	}

/**
 * Test parsing routes.
 *
 * @return void
 */
	public function testParse() {
		$routes = new ScopedRouteCollection('/b', ['key' => 'value']);
		$routes->connect('/', ['controller' => 'Articles']);
		$routes->connect('/:id', ['controller' => 'Articles', 'action' => 'view']);

		$result = $routes->parse('/');
		$this->assertEquals([], $result, 'Should not match, missing /b');

		$result = $routes->parse('/b/');
		$expected = [
			'controller' => 'Articles',
			'action' => 'index',
			'pass' => [],
			'plugin' => null,
			'key' => 'value',
		];
		$this->assertEquals($expected, $result);

		$result = $routes->parse('/b/the-thing?one=two');
		$expected = [
			'controller' => 'Articles',
			'action' => 'view',
			'id' => 'the-thing',
			'pass' => [],
			'plugin' => null,
			'key' => 'value',
			'?' => ['one' => 'two'],
		];
		$this->assertEquals($expected, $result);
	}

/**
 * Test matching routes.
 *
 * @return void
 */
	public function testMatch() {
		$context = [
			'_base' => '/',
			'_scheme' => 'http',
			'_host' => 'example.org',
		];
		$routes = new ScopedRouteCollection('/b');
		$routes->connect('/', ['controller' => 'Articles']);
		$routes->connect('/:id', ['controller' => 'Articles', 'action' => 'view']);

		$result = $routes->match(['plugin' => null, 'controller' => 'Articles', 'action' => 'index'], $context);
		$this->assertEquals('b', $result);

		$result = $routes->match(
			['id' => 'thing', 'plugin' => null, 'controller' => 'Articles', 'action' => 'view'],
			$context);
		$this->assertEquals('b/thing', $result);

		$result = $routes->match(['plugin' => null, 'controller' => 'Articles', 'action' => 'add'], $context);
		$this->assertFalse($result, 'No matches');
	}

/**
 * Test matching plugin routes.
 *
 * @return void
 */
	public function testMatchPlugin() {
		$context = [
			'_base' => '/',
			'_scheme' => 'http',
			'_host' => 'example.org',
		];
		$routes = new ScopedRouteCollection('/contacts', ['plugin' => 'Contacts']);
		$routes->connect('/', ['controller' => 'Contacts']);

		$result = $routes->match(['controller' => 'Contacts', 'action' => 'index'], $context);
		$this->assertFalse($result);

		$result = $routes->match(['plugin' => 'Contacts', 'controller' => 'Contacts', 'action' => 'index'], $context);
		$this->assertEquals('contacts', $result);
	}

/**
 * Test connecting resources.
 *
 * @return void
 */
	public function testResources() {
		$routes = new ScopedRouteCollection('/api', ['prefix' => 'api']);
		$routes->resources('Articles', ['_ext' => 'json']);

		$all = $routes->routes();
		$this->assertCount(6, $all);

		$this->assertEquals('/api/articles', $all[0]->template);
		$this->assertEquals('json', $all[0]->defaults['_ext']);
		$this->assertEquals('Articles', $all[0]->defaults['controller']);
	}

/**
 * Test nesting resources
 *
 * @return void
 */
	public function testResourcesNested() {
		$routes = new ScopedRouteCollection('/api', ['prefix' => 'api']);
		$routes->resources('Articles', function($routes) {
			$this->assertEquals('/api/articles/', $routes->path());
			$this->assertEquals(['prefix' => 'api'], $routes->params());

			$routes->resources('Comments');
			$route = $routes->routes()[0];
			$this->assertEquals('/api/articles/:article_id/comments', $route->template);
		});
	}

/**
 * Test connecting fallback routes.
 *
 * @return void
 */
	public function testFallbacks() {
		$routes = new ScopedRouteCollection('/api', ['prefix' => 'api']);
		$routes->fallbacks();

		$all = $routes->routes();
		$this->assertEquals('/api/:controller', $all[0]->template);
		$this->assertEquals('/api/:controller/:action/*', $all[1]->template);
	}

}
