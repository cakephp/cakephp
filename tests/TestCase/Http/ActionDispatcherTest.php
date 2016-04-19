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
namespace Cake\Test\TestCase\Http;

use Cake\Core\Configure;
use Cake\Http\ActionDispatcher;
use Cake\Network\Request;
use Cake\Network\Response;
use Cake\Network\Session;
use Cake\Routing\Filter\ControllerFactoryFilter;
use Cake\Routing\Router;
use Cake\TestSuite\TestCase;

/**
 * Test case for the ActionDispatcher.
 */
class ActionDispatcherTest extends TestCase
{
    /**
     * Setup
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        Router::reload();
        Configure::write('App.namespace', 'TestApp');
        $this->dispatcher = new ActionDispatcher();
        $this->dispatcher->addFilter(new ControllerFactoryFilter());
    }

    /**
     * Ensure the constructor args end up on the right protected properties.
     *
     * @return void
     */
    public function testConstructorArgs()
    {
        $factory = $this->getMock('Cake\Http\ControllerFactory');
        $events = $this->getMock('Cake\Event\EventManager');
        $dispatcher = new ActionDispatcher($factory, $events);

        $this->assertAttributeSame($events, '_eventManager', $dispatcher);
        $this->assertAttributeSame($factory, 'factory', $dispatcher);
    }

    /**
     * Test adding routing filters
     *
     * @return void
     */
    public function testAddFilter()
    {
        $this->assertCount(1, $this->dispatcher->getFilters());
        $events = $this->dispatcher->eventManager();
        $this->assertCount(1, $events->listeners('Dispatcher.beforeDispatch'));
        $this->assertCount(1, $events->listeners('Dispatcher.afterDispatch'));

        $filter = $this->getMock(
            'Cake\Routing\DispatcherFilter',
            ['beforeDispatch', 'afterDispatch']
        );
        $this->dispatcher->addFilter($filter);

        $this->assertCount(2, $this->dispatcher->getFilters());
        $this->assertCount(2, $events->listeners('Dispatcher.beforeDispatch'));
        $this->assertCount(2, $events->listeners('Dispatcher.afterDispatch'));
    }

    /**
     * Ensure that aborting in the beforeDispatch doesn't invoke the controller
     *
     * @return void
     */
    public function testBeforeDispatchEventAbort()
    {
        $response = new Response();
        $dispatcher = new ActionDispatcher();
        $filter = $this->getMock(
            'Cake\Routing\DispatcherFilter',
            ['beforeDispatch', 'afterDispatch']
        );
        $filter->expects($this->once())
            ->method('beforeDispatch')
            ->will($this->returnValue($response));

        $req = new Request();
        $res = new Response();
        $dispatcher->addFilter($filter);
        $result = $dispatcher->dispatch($req, $res);
        $this->assertSame($response, $result, 'Should be response from filter.');
    }

    /**
     * Ensure afterDispatch can replace the response
     *
     * @return void
     */
    public function testDispatchAfterDispatchEventModifyResponse()
    {
        $filter = $this->getMock(
            'Cake\Routing\DispatcherFilter',
            ['beforeDispatch', 'afterDispatch']
        );
        $filter->expects($this->once())
            ->method('afterDispatch')
            ->will($this->returnCallback(function ($event) {
                $event->data['response']->body('Filter body');
            }));

        $req = new Request([
            'url' => '/cakes',
            'params' => [
                'plugin' => null,
                'controller' => 'Cakes',
                'action' => 'index',
                'pass' => [],
            ],
            'session' => new Session
        ]);
        $res = new Response();
        $this->dispatcher->addFilter($filter);
        $result = $this->dispatcher->dispatch($req, $res);
        $this->assertSame('Filter body', $result->body(), 'Should be response from filter.');
    }

    /**
     * Test that a controller action returning a response
     * results in no afterDispatch event.
     *
     * @return void
     */
    public function testDispatchActionReturnResponseNoAfterDispatch()
    {
        $filter = $this->getMock(
            'Cake\Routing\DispatcherFilter',
            ['beforeDispatch', 'afterDispatch']
        );
        $filter->expects($this->never())
            ->method('afterDispatch');

        $req = new Request([
            'url' => '/cakes',
            'params' => [
                'plugin' => null,
                'controller' => 'Cakes',
                'action' => 'index',
                'pass' => [],
                'return' => true,
            ],
        ]);
        $res = new Response();
        $this->dispatcher->addFilter($filter);
        $result = $this->dispatcher->dispatch($req, $res);
        $this->assertSame('Hello Jane', $result->body(), 'Response from controller.');
    }

    /**
     * Test that dispatching sets the Router request state.
     *
     * @return void
     */
    public function testDispatchSetsRequestContext()
    {
        $this->assertNull(Router::getRequest());
        $req = new Request([
            'url' => '/cakes',
            'params' => [
                'plugin' => null,
                'controller' => 'Cakes',
                'action' => 'index',
                'pass' => [],
                'return' => true,
            ],
        ]);
        $res = new Response();
        $this->dispatcher->dispatch($req, $res);
        $this->assertSame($req, Router::getRequest(true));
    }

    /**
     * test invalid response from dispatch process.
     *
     * @expectedException \LogicException
     * @expectedExceptionMessage Controller actions can only Cake\Network\Response instances
     * @return void
     */
    public function testDispatchInvalidResponse()
    {
        $req = new Request([
            'url' => '/cakes',
            'params' => [
                'plugin' => null,
                'controller' => 'Cakes',
                'action' => 'invalid',
                'pass' => [],
            ],
        ]);
        $res = new Response();
        $result = $this->dispatcher->dispatch($req, $res);
    }

    /**
     * Test dispatch with autorender
     *
     * @return void
     */
    public function testDispatchAutoRender()
    {
        $request = new Request([
            'url' => 'posts',
            'params' => [
                'controller' => 'Posts',
                'action' => 'index',
                'pass' => [],
            ]
        ]);
        $response = new Response();
        $result = $this->dispatcher->dispatch($request, $response);
        $this->assertInstanceOf('Cake\Network\Response', $result);
        $this->assertContains('posts index', $result->body());
    }

    /**
     * Test dispatch with autorender=false
     *
     * @return void
     */
    public function testDispatchAutoRenderFalse()
    {
        $request = new Request([
            'url' => 'posts',
            'params' => [
                'controller' => 'Cakes',
                'action' => 'noRender',
                'pass' => [],
            ]
        ]);
        $response = new Response();
        $result = $this->dispatcher->dispatch($request, $response);
        $this->assertInstanceOf('Cake\Network\Response', $result);
        $this->assertContains('autoRender false body', $result->body());
    }

    /**
     * testMissingController method
     *
     * @expectedException \Cake\Routing\Exception\MissingControllerException
     * @expectedExceptionMessage Controller class SomeController could not be found.
     * @return void
     */
    public function testMissingController()
    {
        $request = new Request([
            'url' => 'some_controller/home',
            'params' => [
                'controller' => 'SomeController',
                'action' => 'home',
            ]
        ]);
        $response = $this->getMock('Cake\Network\Response');
        $this->dispatcher->dispatch($request, $response);
    }

    /**
     * testMissingControllerInterface method
     *
     * @expectedException \Cake\Routing\Exception\MissingControllerException
     * @expectedExceptionMessage Controller class Interface could not be found.
     * @return void
     */
    public function testMissingControllerInterface()
    {
        $request = new Request([
            'url' => 'interface/index',
            'params' => [
                'controller' => 'Interface',
                'action' => 'index',
            ]
        ]);
        $response = $this->getMock('Cake\Network\Response');
        $this->dispatcher->dispatch($request, $response);
    }

    /**
     * testMissingControllerInterface method
     *
     * @expectedException \Cake\Routing\Exception\MissingControllerException
     * @expectedExceptionMessage Controller class Abstract could not be found.
     * @return void
     */
    public function testMissingControllerAbstract()
    {
        $request = new Request([
            'url' => 'abstract/index',
            'params' => [
                'controller' => 'Abstract',
                'action' => 'index',
            ]
        ]);
        $response = $this->getMock('Cake\Network\Response');
        $this->dispatcher->dispatch($request, $response);
    }

    /**
     * Test that lowercase controller names result in missing controller errors.
     *
     * In case-insensitive file systems, lowercase controller names will kind of work.
     * This causes annoying deployment issues for lots of folks.
     *
     * @expectedException \Cake\Routing\Exception\MissingControllerException
     * @expectedExceptionMessage Controller class somepages could not be found.
     * @return void
     */
    public function testMissingControllerLowercase()
    {
        $request = new Request([
            'url' => 'pages/home',
            'params' => [
                'plugin' => null,
                'controller' => 'somepages',
                'action' => 'display',
                'pass' => ['home'],
            ]
        ]);
        $response = $this->getMock('Cake\Network\Response');
        $this->dispatcher->dispatch($request, $response);
    }

    /**
     * Ensure that a controller's startup event can stop the request.
     *
     * @return void
     */
    public function testStartupProcessAbort()
    {
        $request = new Request([
            'url' => 'cakes/index',
            'params' => [
                'plugin' => null,
                'controller' => 'Cakes',
                'action' => 'index',
                'stop' => 'startup',
                'pass' => [],
            ]
        ]);
        $response = new Response();
        $result = $this->dispatcher->dispatch($request, $response);
        $this->assertSame('startup stop', $result->body());
    }

    /**
     * Ensure that a controllers startup process can emit a response
     *
     * @return void
     */
    public function testShutdownProcessResponse()
    {
        $request = new Request([
            'url' => 'cakes/index',
            'params' => [
                'plugin' => null,
                'controller' => 'Cakes',
                'action' => 'index',
                'stop' => 'shutdown',
                'pass' => [],
            ]
        ]);
        $response = new Response();
        $result = $this->dispatcher->dispatch($request, $response);
        $this->assertSame('shutdown stop', $result->body());
    }
}
