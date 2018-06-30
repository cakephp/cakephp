<?php
/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         2.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Routing\Route;

use Cake\Http\ServerRequest;
use Cake\Routing\Router;
use Cake\Routing\Route\RedirectRoute;
use Cake\TestSuite\TestCase;

/**
 * test case for RedirectRoute
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
     * @return void
     */
    public function testParseSimple()
    {
        $this->expectException(\Cake\Routing\Exception\RedirectException::class);
        $this->expectExceptionMessage('http://localhost/posts');
        $this->expectExceptionCode(301);
        $route = new RedirectRoute('/home', ['controller' => 'posts']);
        $route->parse('/home');
    }

    /**
     * test the parsing of routes.
     *
     * @return void
     */
    public function testParseRedirectOption()
    {
        $this->expectException(\Cake\Routing\Exception\RedirectException::class);
        $this->expectExceptionMessage('http://localhost/posts');
        $this->expectExceptionCode(301);
        $route = new RedirectRoute('/home', ['redirect' => ['controller' => 'posts']]);
        $route->parse('/home');
    }

    /**
     * test the parsing of routes.
     *
     * @return void
     */
    public function testParseArray()
    {
        $this->expectException(\Cake\Routing\Exception\RedirectException::class);
        $this->expectExceptionMessage('http://localhost/posts');
        $this->expectExceptionCode(301);
        $route = new RedirectRoute('/home', ['controller' => 'posts', 'action' => 'index']);
        $route->parse('/home');
    }

    /**
     * test redirecting to an external url
     *
     * @return void
     */
    public function testParseAbsolute()
    {
        $this->expectException(\Cake\Routing\Exception\RedirectException::class);
        $this->expectExceptionMessage('http://google.com');
        $this->expectExceptionCode(301);
        $route = new RedirectRoute('/google', 'http://google.com');
        $route->parse('/google');
    }

    /**
     * test redirecting with a status code
     *
     * @return void
     */
    public function testParseStatusCode()
    {
        $this->expectException(\Cake\Routing\Exception\RedirectException::class);
        $this->expectExceptionMessage('http://localhost/posts/view');
        $this->expectExceptionCode(302);
        $route = new RedirectRoute('/posts/*', ['controller' => 'posts', 'action' => 'view'], ['status' => 302]);
        $route->parse('/posts/2');
    }

    /**
     * test redirecting with the persist option
     *
     * @return void
     */
    public function testParsePersist()
    {
        $this->expectException(\Cake\Routing\Exception\RedirectException::class);
        $this->expectExceptionMessage('http://localhost/posts/view/2');
        $this->expectExceptionCode(301);
        $route = new RedirectRoute('/posts/*', ['controller' => 'posts', 'action' => 'view'], ['persist' => true]);
        $route->parse('/posts/2');
    }

    /**
     * test redirecting with persist and a base directory
     *
     * @return void
     */
    public function testParsePersistBaseDirectory()
    {
        $request = new ServerRequest([
            'base' => '/basedir',
            'url' => '/posts/2'
        ]);
        Router::pushRequest($request);

        $this->expectException(\Cake\Routing\Exception\RedirectException::class);
        $this->expectExceptionMessage('http://localhost/basedir/posts/view/2');
        $this->expectExceptionCode(301);
        $route = new RedirectRoute('/posts/*', ['controller' => 'posts', 'action' => 'view'], ['persist' => true]);
        $route->parse('/posts/2');
    }

    /**
     * test redirecting with persist and string target URLs
     *
     * @return void
     */
    public function testParsePersistStringUrl()
    {
        $this->expectException(\Cake\Routing\Exception\RedirectException::class);
        $this->expectExceptionMessage('http://localhost/test');
        $this->expectExceptionCode(301);
        $route = new RedirectRoute('/posts/*', '/test', ['persist' => true]);
        $route->parse('/posts/2');
    }

    /**
     * test redirecting with persist and passed args
     *
     * @return void
     */
    public function testParsePersistPassedArgs()
    {
        $this->expectException(\Cake\Routing\Exception\RedirectException::class);
        $this->expectExceptionMessage('http://localhost/tags/add/passme');
        $this->expectExceptionCode(301);
        $route = new RedirectRoute('/my_controllers/:action/*', ['controller' => 'tags', 'action' => 'add'], ['persist' => true]);
        $route->parse('/my_controllers/do_something/passme');
    }

    /**
     * test redirecting without persist and passed args
     *
     * @return void
     */
    public function testParseNoPersistPassedArgs()
    {
        $this->expectException(\Cake\Routing\Exception\RedirectException::class);
        $this->expectExceptionMessage('http://localhost/tags/add');
        $this->expectExceptionCode(301);
        $route = new RedirectRoute('/my_controllers/:action/*', ['controller' => 'tags', 'action' => 'add']);
        $route->parse('/my_controllers/do_something/passme');
    }

    /**
     * test redirecting with patterns
     *
     * @return void
     */
    public function testParsePersistPatterns()
    {
        $this->expectException(\Cake\Routing\Exception\RedirectException::class);
        $this->expectExceptionMessage('http://localhost/tags/add?lang=nl');
        $this->expectExceptionCode(301);
        $route = new RedirectRoute('/:lang/my_controllers', ['controller' => 'tags', 'action' => 'add'], ['lang' => '(nl|en)', 'persist' => ['lang']]);
        $route->parse('/nl/my_controllers/');
    }

    /**
     * test redirecting with patterns and a routed target
     *
     * @return void
     */
    public function testParsePersistMatchesAnotherRoute()
    {
        $this->expectException(\Cake\Routing\Exception\RedirectException::class);
        $this->expectExceptionMessage('http://localhost/nl/preferred_controllers');
        $this->expectExceptionCode(301);
        Router::connect('/:lang/preferred_controllers', ['controller' => 'tags', 'action' => 'add'], ['lang' => '(nl|en)', 'persist' => ['lang']]);
        $route = new RedirectRoute('/:lang/my_controllers', ['controller' => 'tags', 'action' => 'add'], ['lang' => '(nl|en)', 'persist' => ['lang']]);
        $route->parse('/nl/my_controllers/');
    }
}
