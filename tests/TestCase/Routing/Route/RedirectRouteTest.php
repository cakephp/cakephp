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
 * @since         2.0.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Routing\Route;

use Cake\Core\App;
use Cake\Core\Configure;
use Cake\Network\Response;
use Cake\Routing\Router;
use Cake\Routing\Route\RedirectRoute;
use Cake\TestSuite\TestCase;

/**
 * test case for RedirectRoute
 *
 */
class RedirectRouteTest extends TestCase
{

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        Configure::write('Routing', ['admin' => null, 'prefixes' => []]);
        Router::reload();
    }

    /**
     * test the parsing of routes.
     *
     * @return void
     */
    public function testParsing()
    {
        Router::connect('/:controller', ['action' => 'index']);
        Router::connect('/:controller/:action/*');

        $route = new RedirectRoute('/home', ['controller' => 'posts']);
        $route->response = $this->getMock('Cake\Network\Response', ['_sendHeader', 'stop']);
        $result = $route->parse('/home');
        $header = $route->response->header();
        $this->assertEquals(Router::url('/posts', true), $header['Location']);

        $route = new RedirectRoute('/home', ['controller' => 'posts', 'action' => 'index']);
        $route->response = $this->getMock('Cake\Network\Response', ['_sendHeader', 'stop']);
        $result = $route->parse('/home');
        $header = $route->response->header();
        $this->assertEquals(Router::url('/posts', true), $header['Location']);
        $this->assertEquals(301, $route->response->statusCode());

        $route = new RedirectRoute('/google', 'http://google.com');
        $route->response = $this->getMock('Cake\Network\Response', ['_sendHeader', 'stop']);
        $result = $route->parse('/google');
        $header = $route->response->header();
        $this->assertEquals('http://google.com', $header['Location']);

        $route = new RedirectRoute('/posts/*', ['controller' => 'posts', 'action' => 'view'], ['status' => 302]);
        $route->response = $this->getMock('Cake\Network\Response', ['_sendHeader', 'stop']);
        $result = $route->parse('/posts/2');
        $header = $route->response->header();
        $this->assertEquals(Router::url('/posts/view', true), $header['Location']);
        $this->assertEquals(302, $route->response->statusCode());

        $route = new RedirectRoute('/posts/*', ['controller' => 'posts', 'action' => 'view'], ['persist' => true]);
        $route->response = $this->getMock('Cake\Network\Response', ['_sendHeader', 'stop']);
        $result = $route->parse('/posts/2');
        $header = $route->response->header();
        $this->assertEquals(Router::url('/posts/view/2', true), $header['Location']);

        $route = new RedirectRoute('/posts/*', '/test', ['persist' => true]);
        $route->response = $this->getMock('Cake\Network\Response', ['_sendHeader', 'stop']);
        $result = $route->parse('/posts/2');
        $header = $route->response->header();
        $this->assertEquals(Router::url('/test', true), $header['Location']);

        $route = new RedirectRoute('/my_controllers/:action/*', ['controller' => 'tags', 'action' => 'add'], ['persist' => true]);
        $route->response = $this->getMock('Cake\Network\Response', ['_sendHeader', 'stop']);
        $result = $route->parse('/my_controllers/do_something/passme');
        $header = $route->response->header();
        $this->assertEquals(Router::url('/tags/add/passme', true), $header['Location']);

        $route = new RedirectRoute('/my_controllers/:action/*', ['controller' => 'tags', 'action' => 'add']);
        $route->response = $this->getMock('Cake\Network\Response', ['_sendHeader', 'stop']);
        $result = $route->parse('/my_controllers/do_something/passme');
        $header = $route->response->header();
        $this->assertEquals(Router::url('/tags/add', true), $header['Location']);

        $route = new RedirectRoute('/:lang/my_controllers', ['controller' => 'tags', 'action' => 'add'], ['lang' => '(nl|en)', 'persist' => ['lang']]);
        $route->response = $this->getMock('Cake\Network\Response', ['_sendHeader', 'stop']);
        $result = $route->parse('/nl/my_controllers/');
        $header = $route->response->header();
        $this->assertEquals(Router::url('/tags/add?lang=nl', true), $header['Location']);

        Router::reload(); // reset default routes
        Router::connect('/:lang/preferred_controllers', ['controller' => 'tags', 'action' => 'add'], ['lang' => '(nl|en)', 'persist' => ['lang']]);
        $route = new RedirectRoute('/:lang/my_controllers', ['controller' => 'tags', 'action' => 'add'], ['lang' => '(nl|en)', 'persist' => ['lang']]);
        $route->response = $this->getMock('Cake\Network\Response', ['_sendHeader', 'stop']);
        $result = $route->parse('/nl/my_controllers/');
        $header = $route->response->header();
        $this->assertEquals(Router::url('/nl/preferred_controllers', true), $header['Location']);
    }
}
