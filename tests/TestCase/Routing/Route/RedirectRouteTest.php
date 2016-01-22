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
        Router::reload();

        Router::connect('/:controller', ['action' => 'index']);
        Router::connect('/:controller/:action/*');
    }

    /**
     * test match
     *
     * @return void
     */
    public function testMatch()
    {
        $route = new RedirectRoute('/home', ['controller' => 'posts']);
        $this->assertFalse($route->match(['controller' => 'posts', 'action' => 'index']));
    }

    /**
     * test parse failure
     *
     * @return void
     */
    public function testParseMiss()
    {
        $route = new RedirectRoute('/home', ['controller' => 'posts']);
        $this->assertFalse($route->parse('/nope'));
        $this->assertFalse($route->parse('/homes'));
    }

    /**
     * test the parsing of routes.
     *
     * @expectedException Cake\Routing\Exception\RedirectException
     * @expectedExceptionMessage http://localhost/posts
     * @expectedExceptionCode 301
     * @return void
     */
    public function testParseSimple()
    {
        $route = new RedirectRoute('/home', ['controller' => 'posts']);
        $route->parse('/home');
    }

    /**
     * test the parsing of routes.
     *
     * @expectedException Cake\Routing\Exception\RedirectException
     * @expectedExceptionMessage http://localhost/posts
     * @expectedExceptionCode 301
     * @return void
     */
    public function testParseRedirectOption()
    {
        $route = new RedirectRoute('/home', ['redirect' => ['controller' => 'posts']]);
        $route->parse('/home');
    }

    /**
     * test the parsing of routes.
     *
     * @expectedException Cake\Routing\Exception\RedirectException
     * @expectedExceptionMessage http://localhost/posts
     * @expectedExceptionCode 301
     * @return void
     */
    public function testParseArray()
    {
        $route = new RedirectRoute('/home', ['controller' => 'posts', 'action' => 'index']);
        $route->parse('/home');
    }

    /**
     * test redirecting to an external url
     *
     * @expectedException Cake\Routing\Exception\RedirectException
     * @expectedExceptionMessage http://google.com
     * @expectedExceptionCode 301
     * @return void
     */
    public function testParseAbsolute()
    {
        $route = new RedirectRoute('/google', 'http://google.com');
        $route->parse('/google');
    }

    /**
     * test redirecting with a status code
     *
     * @expectedException Cake\Routing\Exception\RedirectException
     * @expectedExceptionMessage http://localhost/posts/view
     * @expectedExceptionCode 302
     * @return void
     */
    public function testParseStatusCode()
    {
        $route = new RedirectRoute('/posts/*', ['controller' => 'posts', 'action' => 'view'], ['status' => 302]);
        $route->parse('/posts/2');
    }

    /**
     * test redirecting with the persist option
     *
     * @expectedException Cake\Routing\Exception\RedirectException
     * @expectedExceptionMessage http://localhost/posts/view/2
     * @expectedExceptionCode 301
     * @return void
     */
    public function testParsePersist()
    {
        $route = new RedirectRoute('/posts/*', ['controller' => 'posts', 'action' => 'view'], ['persist' => true]);
        $route->parse('/posts/2');
    }

    /**
     * test redirecting with persist and string target URLs
     *
     * @expectedException Cake\Routing\Exception\RedirectException
     * @expectedExceptionMessage http://localhost/test
     * @expectedExceptionCode 301
     * @return void
     */
    public function testParsePersistStringUrl()
    {
        $route = new RedirectRoute('/posts/*', '/test', ['persist' => true]);
        $route->parse('/posts/2');
    }

    /**
     * test redirecting with persist and passed args
     *
     * @expectedException Cake\Routing\Exception\RedirectException
     * @expectedExceptionMessage http://localhost/tags/add/passme
     * @expectedExceptionCode 301
     * @return void
     */
    public function testParsePersistPassedArgs()
    {
        $route = new RedirectRoute('/my_controllers/:action/*', ['controller' => 'tags', 'action' => 'add'], ['persist' => true]);
        $route->parse('/my_controllers/do_something/passme');
    }

    /**
     * test redirecting without persist and passed args
     *
     * @expectedException Cake\Routing\Exception\RedirectException
     * @expectedExceptionMessage http://localhost/tags/add
     * @expectedExceptionCode 301
     * @return void
     */
    public function testParseNoPersistPassedArgs()
    {
        $route = new RedirectRoute('/my_controllers/:action/*', ['controller' => 'tags', 'action' => 'add']);
        $route->parse('/my_controllers/do_something/passme');
    }

    /**
     * test redirecting with patterns
     *
     * @expectedException Cake\Routing\Exception\RedirectException
     * @expectedExceptionMessage http://localhost/tags/add?lang=nl
     * @expectedExceptionCode 301
     * @return void
     */
    public function testParsePersistPatterns()
    {
        $route = new RedirectRoute('/:lang/my_controllers', ['controller' => 'tags', 'action' => 'add'], ['lang' => '(nl|en)', 'persist' => ['lang']]);
        $route->parse('/nl/my_controllers/');
    }

    /**
     * test redirecting with patterns and a routed target
     *
     * @expectedException Cake\Routing\Exception\RedirectException
     * @expectedExceptionMessage http://localhost/nl/preferred_controllers
     * @expectedExceptionCode 301
     * @return void
     */
    public function testParsePersistMatchesAnotherRoute()
    {
        Router::connect('/:lang/preferred_controllers', ['controller' => 'tags', 'action' => 'add'], ['lang' => '(nl|en)', 'persist' => ['lang']]);
        $route = new RedirectRoute('/:lang/my_controllers', ['controller' => 'tags', 'action' => 'add'], ['lang' => '(nl|en)', 'persist' => ['lang']]);
        $route->parse('/nl/my_controllers/');
    }
}
