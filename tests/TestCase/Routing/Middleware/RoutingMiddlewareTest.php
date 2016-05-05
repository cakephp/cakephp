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
 * @since         3.3.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Routing\Middleware;

use Cake\Routing\Middleware\RoutingMiddleware;
use Cake\Routing\Router;
use Cake\TestSuite\TestCase;
use Zend\Diactoros\Request;
use Zend\Diactoros\Response;
use Zend\Diactoros\ServerRequestFactory;

/**
 * Test for RoutingMiddleware
 */
class RoutingMiddlewareTest extends TestCase
{
    /**
     * Setup method
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        Router::reload();
        Router::connect('/articles', ['controller' => 'Articles', 'action' => 'index']);
    }

    /**
     * Test redirect responses from redirect routes
     *
     * @return void
     */
    public function testRedirectResponse()
    {
        Router::redirect('/testpath', '/pages');
        $request = ServerRequestFactory::fromGlobals(['REQUEST_URI' => '/testpath']);
        $response = new Response();
        $next = function ($req, $res) {
        };
        $middleware = new RoutingMiddleware();
        $response = $middleware($request, $response, $next);

        $this->assertEquals(301, $response->getStatusCode());
        $this->assertEquals('http://localhost/pages', $response->getHeaderLine('Location'));
    }

    /**
     * Test redirects with additional headers
     *
     * @return void
     */
    public function testRedirectResponseWithHeaders()
    {
        Router::redirect('/testpath', '/pages');
        $request = ServerRequestFactory::fromGlobals(['REQUEST_URI' => '/testpath']);
        $response = new Response('php://memory', 200, ['X-testing' => 'Yes']);
        $next = function ($req, $res) {
        };
        $middleware = new RoutingMiddleware();
        $response = $middleware($request, $response, $next);

        $this->assertEquals(301, $response->getStatusCode());
        $this->assertEquals('http://localhost/pages', $response->getHeaderLine('Location'));
        $this->assertEquals('Yes', $response->getHeaderLine('X-testing'));
    }

    /**
     * Test that Router sets parameters
     *
     * @return void
     */
    public function testRouterSetParams()
    {
        $request = ServerRequestFactory::fromGlobals(['REQUEST_URI' => '/articles']);
        $response = new Response();
        $next = function ($req, $res) {
            $expected = [
                'controller' => 'Articles',
                'action' => 'index',
                'plugin' => null,
                'pass' => []
            ];
            $this->assertEquals($expected, $req->getAttribute('params'));
        };
        $middleware = new RoutingMiddleware();
        $middleware($request, $response, $next);
    }

    /**
     * Test that routing is not applied if a controller exists already
     *
     * @return void
     */
    public function testRouterNoopOnController()
    {
        $request = ServerRequestFactory::fromGlobals(['REQUEST_URI' => '/articles']);
        $request = $request->withAttribute('params', ['controller' => 'Articles']);
        $response = new Response();
        $next = function ($req, $res) {
            $this->assertEquals(['controller' => 'Articles'], $req->getAttribute('params'));
        };
        $middleware = new RoutingMiddleware();
        $middleware($request, $response, $next);
    }

    /**
     * Test missing routes not being caught.
     *
     * @expectedException \Cake\Routing\Exception\MissingRouteException
     */
    public function testMissingRouteNotCaught()
    {
        $request = ServerRequestFactory::fromGlobals(['REQUEST_URI' => '/missing']);
        $response = new Response();
        $next = function ($req, $res) {
        };
        $middleware = new RoutingMiddleware();
        $middleware($request, $response, $next);
    }
}
