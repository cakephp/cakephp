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
 * @since         3.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Routing\Filter;

use Cake\Event\Event;
use Cake\Http\Response;
use Cake\Http\ServerRequest;
use Cake\Routing\Filter\RoutingFilter;
use Cake\Routing\Router;
use Cake\TestSuite\TestCase;

/**
 * Routing filter test.
 *
 * @group deprecated
 */
class RoutingFilterTest extends TestCase
{

    /**
     * test setting parameters in beforeDispatch method
     *
     * @return void
     * @triggers __CLASS__ $this, compact(request)
     */
    public function testBeforeDispatchSkipWhenControllerSet()
    {
        $filter = new RoutingFilter();

        $request = new ServerRequest([
            'url' => '/testcontroller/testaction/params1/params2/params3',
            'params' => ['controller' => 'articles']
        ]);
        $event = new Event(__CLASS__, $this, compact('request'));
        $filter->beforeDispatch($event);

        $this->assertSame($request->getParam('controller'), 'articles');
        $this->assertEmpty($request->getParam('action'));
    }

    /**
     * test setting parameters in beforeDispatch method
     *
     * @return void
     * @triggers __CLASS__ $this, compact(request)
     */
    public function testBeforeDispatchSetsParameters()
    {
        $this->deprecated(function () {
            Router::connect('/:controller/:action/*');
            $filter = new RoutingFilter();

            $request = new ServerRequest('/testcontroller/testaction/params1/params2/params3');
            $event = new Event(__CLASS__, $this, compact('request'));
            $filter->beforeDispatch($event);

            $this->assertSame($request->getParam('controller'), 'testcontroller');
            $this->assertSame($request->getParam('action'), 'testaction');
            $this->assertSame($request->getParam('pass.0'), 'params1');
            $this->assertSame($request->getParam('pass.1'), 'params2');
            $this->assertSame($request->getParam('pass.2'), 'params3');
        });
    }

    /**
     * test setting parameters in beforeDispatch method
     *
     * @return void
     * @triggers __CLASS__ $this, compact(request)
     */
    public function testBeforeDispatchRedirectRoute()
    {
        Router::scope('/', function ($routes) {
            $routes->redirect('/home', ['controller' => 'articles']);
            $routes->connect('/:controller/:action/*');
        });
        $filter = new RoutingFilter();

        $request = new ServerRequest('/home');
        $response = new Response();
        $event = new Event(__CLASS__, $this, compact('request', 'response'));
        $response = $filter->beforeDispatch($event);
        $this->assertInstanceOf('Cake\Http\Response', $response);
        $this->assertSame('http://localhost/articles', $response->getHeaderLine('Location'));
        $this->assertSame(301, $response->getStatusCode());
        $this->assertTrue($event->isStopped());
    }

    /**
     * test setting parameters in beforeDispatch method
     *
     * @return void
     * @triggers __CLASS__ $this, compact(request)
     * @triggers __CLASS__ $this, compact(request)
     */
    public function testQueryStringOnRoot()
    {
        $this->deprecated(function () {
            Router::reload();
            Router::connect('/', ['controller' => 'pages', 'action' => 'display', 'home']);
            Router::connect('/pages/*', ['controller' => 'pages', 'action' => 'display']);
            Router::connect('/:controller/:action/*');

            $_GET = ['coffee' => 'life', 'sleep' => 'sissies'];
            $filter = new RoutingFilter();
            $request = new ServerRequest('posts/home/?coffee=life&sleep=sissies');

            $event = new Event(__CLASS__, $this, compact('request'));
            $filter->beforeDispatch($event);

            $this->assertRegExp('/posts/', $request->getParam('controller'));
            $this->assertRegExp('/home/', $request->getParam('action'));
            $this->assertSame('sissies', $request->getQuery('sleep'));
            $this->assertSame('life', $request->getQuery('coffee'));

            $request = new ServerRequest('/?coffee=life&sleep=sissy');

            $event = new Event(__CLASS__, $this, compact('request'));
            $filter->beforeDispatch($event);

            $this->assertRegExp('/pages/', $request->getParam('controller'));
            $this->assertRegExp('/display/', $request->getParam('action'));
            $this->assertSame('sissy', $request->getQuery('sleep'));
            $this->assertSame('life', $request->getQuery('coffee'));
            $this->assertEquals('life', $request->getQuery('coffee'));
        });
    }
}
