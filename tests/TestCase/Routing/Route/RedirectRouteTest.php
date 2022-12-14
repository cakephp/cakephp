<?php
declare(strict_types=1);

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

use Cake\Http\Exception\RedirectException;
use Cake\Http\ServerRequest;
use Cake\Routing\Route\RedirectRoute;
use Cake\Routing\Router;
use Cake\TestSuite\TestCase;

/**
 * test case for RedirectRoute
 */
class RedirectRouteTest extends TestCase
{
    /**
     * @var \Cake\Routing\RouteBuilder
     */
    protected $builder;

    /**
     * setUp method
     */
    public function setUp(): void
    {
        parent::setUp();
        Router::reload();

        $this->builder = Router::createRouteBuilder('/');
        $this->builder->connect('/{controller}', ['action' => 'index']);
        $this->builder->connect('/{controller}/{action}/*');
    }

    /**
     * test match
     */
    public function testMatch(): void
    {
        $route = new RedirectRoute('/home', ['controller' => 'Posts']);
        $this->assertNull($route->match(['controller' => 'Posts', 'action' => 'index']));
    }

    /**
     * test parse failure
     */
    public function testParseMiss(): void
    {
        $route = new RedirectRoute('/home', ['controller' => 'Posts']);
        $this->assertNull($route->parse('/nope'));
        $this->assertNull($route->parse('/homes'));
    }

    /**
     * test the parsing of routes.
     */
    public function testParseSimple(): void
    {
        $this->expectException(RedirectException::class);
        $this->expectExceptionMessage('http://localhost/Posts');
        $this->expectExceptionCode(301);
        $route = new RedirectRoute('/home', ['controller' => 'Posts']);
        $route->parse('/home');
    }

    /**
     * test the parsing of routes.
     */
    public function testParseRedirectOption(): void
    {
        $this->expectException(RedirectException::class);
        $this->expectExceptionMessage('http://localhost/Posts');
        $this->expectExceptionCode(301);
        $route = new RedirectRoute('/home', ['redirect' => ['controller' => 'Posts']]);
        $route->parse('/home');
    }

    /**
     * test the parsing of routes.
     */
    public function testParseArray(): void
    {
        $this->expectException(RedirectException::class);
        $this->expectExceptionMessage('http://localhost/Posts');
        $this->expectExceptionCode(301);
        $route = new RedirectRoute('/home', ['controller' => 'Posts', 'action' => 'index']);
        $route->parse('/home');
    }

    /**
     * test redirecting to an external url
     */
    public function testParseAbsolute(): void
    {
        $this->expectException(RedirectException::class);
        $this->expectExceptionMessage('http://google.com');
        $this->expectExceptionCode(301);
        $route = new RedirectRoute('/google', ['redirect' => 'http://google.com']);
        $route->parse('/google');
    }

    /**
     * test redirecting with a status code
     */
    public function testParseStatusCode(): void
    {
        $this->expectException(RedirectException::class);
        $this->expectExceptionMessage('http://localhost/Posts/view');
        $this->expectExceptionCode(302);
        $route = new RedirectRoute('/posts/*', ['controller' => 'Posts', 'action' => 'view'], ['status' => 302]);
        $route->parse('/posts/2');
    }

    /**
     * test redirecting with the persist option
     */
    public function testParsePersist(): void
    {
        $this->expectException(RedirectException::class);
        $this->expectExceptionMessage('http://localhost/Posts/view/2');
        $this->expectExceptionCode(301);
        $route = new RedirectRoute('/posts/*', ['controller' => 'Posts', 'action' => 'view'], ['persist' => true]);
        $route->parse('/posts/2');
    }

    /**
     * test redirecting with persist and a base directory
     */
    public function testParsePersistBaseDirectory(): void
    {
        $request = new ServerRequest([
            'base' => '/basedir',
            'url' => '/posts/2',
        ]);
        Router::setRequest($request);

        $this->expectException(RedirectException::class);
        $this->expectExceptionMessage('http://localhost/basedir/Posts/view/2');
        $this->expectExceptionCode(301);
        $route = new RedirectRoute('/posts/*', ['controller' => 'Posts', 'action' => 'view'], ['persist' => true]);
        $route->parse('/posts/2');
    }

    /**
     * test redirecting with persist and string target URLs
     */
    public function testParsePersistStringUrl(): void
    {
        $this->expectException(RedirectException::class);
        $this->expectExceptionMessage('http://localhost/test');
        $this->expectExceptionCode(301);
        $route = new RedirectRoute('/posts/*', ['redirect' => '/test'], ['persist' => true]);
        $route->parse('/posts/2');
    }

    /**
     * test redirecting with persist and passed args
     */
    public function testParsePersistPassedArgs(): void
    {
        $this->expectException(RedirectException::class);
        $this->expectExceptionMessage('http://localhost/Tags/add/passme');
        $this->expectExceptionCode(301);
        $route = new RedirectRoute('/my_controllers/{action}/*', ['controller' => 'Tags', 'action' => 'add'], ['persist' => true]);
        $route->parse('/my_controllers/do_something/passme');
    }

    /**
     * test redirecting without persist and passed args
     */
    public function testParseNoPersistPassedArgs(): void
    {
        $this->expectException(RedirectException::class);
        $this->expectExceptionMessage('http://localhost/Tags/add');
        $this->expectExceptionCode(301);
        $route = new RedirectRoute('/my_controllers/{action}/*', ['controller' => 'Tags', 'action' => 'add']);
        $route->parse('/my_controllers/do_something/passme');
    }

    /**
     * test redirecting with patterns
     */
    public function testParsePersistPatterns(): void
    {
        $this->expectException(RedirectException::class);
        $this->expectExceptionMessage('http://localhost/Tags/add');
        $this->expectExceptionCode(301);
        $route = new RedirectRoute('/{lang}/my_controllers', ['controller' => 'Tags', 'action' => 'add'], ['lang' => '(nl|en)', 'persist' => ['lang']]);
        $route->parse('/nl/my_controllers/');
    }

    /**
     * test redirecting with patterns and a routed target
     */
    public function testParsePersistMatchesAnotherRoute(): void
    {
        $this->expectException(RedirectException::class);
        $this->expectExceptionMessage('http://localhost/nl/preferred_controllers');
        $this->expectExceptionCode(301);

        $this->builder->connect('/{lang}/preferred_controllers', ['controller' => 'Tags', 'action' => 'add'], ['lang' => '(nl|en)', 'persist' => ['lang']]);
        $route = new RedirectRoute('/{lang}/my_controllers', ['controller' => 'Tags', 'action' => 'add'], ['lang' => '(nl|en)', 'persist' => ['lang']]);
        $route->parse('/nl/my_controllers/');
    }

    /**
     * Test setting HTTP status
     */
    public function testSetStatus(): void
    {
        $route = new RedirectRoute('/home', ['controller' => 'Posts']);
        $result = $route->setStatus(302);
        $this->assertSame($result, $route);
        $this->assertSame(302, $route->options['status']);
    }
}
