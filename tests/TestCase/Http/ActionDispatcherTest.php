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
 * @since         3.3.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Http;

use Cake\Event\Event;
use Cake\Http\ActionDispatcher;
use Cake\Http\Response;
use Cake\Http\ServerRequest;
use Cake\Network\Session;
use Cake\Routing\DispatcherFactory;
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
        static::setAppNamespace();
        $this->dispatcher = new ActionDispatcher();
        $this->dispatcher->addFilter(new ControllerFactoryFilter());
    }

    /**
     * Teardown
     *
     * @return void
     */
    public function tearDown()
    {
        parent::tearDown();
        DispatcherFactory::clear();
    }

    /**
     * Ensure the constructor args end up on the right protected properties.
     *
     * @return void
     */
    public function testConstructorArgs()
    {
        $factory = $this->getMockBuilder('Cake\Http\ControllerFactory')->getMock();
        $events = $this->getMockBuilder('Cake\Event\EventManager')->getMock();
        $dispatcher = new ActionDispatcher($factory, $events);

        $this->assertAttributeSame($events, '_eventManager', $dispatcher);
        $this->assertAttributeSame($factory, 'factory', $dispatcher);
    }

    /**
     * Ensure that filters connected to the DispatcherFactory are
     * also applied
     */
    public function testDispatcherFactoryCompat()
    {
        $filter = $this->getMockBuilder('Cake\Routing\DispatcherFilter')
            ->setMethods(['beforeDispatch', 'afterDispatch'])
            ->getMock();
        DispatcherFactory::add($filter);
        $dispatcher = new ActionDispatcher(null, null, DispatcherFactory::filters());
        $this->assertCount(1, $dispatcher->getFilters());
        $this->assertSame($filter, $dispatcher->getFilters()[0]);
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

        $filter = $this->getMockBuilder('Cake\Routing\DispatcherFilter')
            ->setMethods(['beforeDispatch', 'afterDispatch'])
            ->getMock();
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
        $filter = $this->getMockBuilder('Cake\Routing\DispatcherFilter')
            ->setMethods(['beforeDispatch', 'afterDispatch'])
            ->getMock();
        $filter->expects($this->once())
            ->method('beforeDispatch')
            ->will($this->returnValue($response));

        $req = new ServerRequest();
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
        $filter = $this->getMockBuilder('Cake\Routing\DispatcherFilter')
            ->setMethods(['beforeDispatch', 'afterDispatch'])
            ->getMock();
        $filter->expects($this->once())
            ->method('afterDispatch')
            ->will($this->returnCallback(function (Event $event) {
                $event->data('response')->body('Filter body');
            }));

        $req = new ServerRequest([
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
        $filter = $this->getMockBuilder('Cake\Routing\DispatcherFilter')
            ->setMethods(['beforeDispatch', 'afterDispatch'])
            ->getMock();
        $filter->expects($this->never())
            ->method('afterDispatch');

        $req = new ServerRequest([
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
        $req = new ServerRequest([
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
     * @expectedExceptionMessage Controller actions can only return Cake\Http\Response or null
     * @return void
     */
    public function testDispatchInvalidResponse()
    {
        $req = new ServerRequest([
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
        $request = new ServerRequest([
            'url' => 'posts',
            'params' => [
                'controller' => 'Posts',
                'action' => 'index',
                'pass' => [],
            ]
        ]);
        $response = new Response();
        $result = $this->dispatcher->dispatch($request, $response);
        $this->assertInstanceOf('Cake\Http\Response', $result);
        $this->assertContains('posts index', $result->body());
    }

    /**
     * Test dispatch with autorender=false
     *
     * @return void
     */
    public function testDispatchAutoRenderFalse()
    {
        $request = new ServerRequest([
            'url' => 'posts',
            'params' => [
                'controller' => 'Cakes',
                'action' => 'noRender',
                'pass' => [],
            ]
        ]);
        $response = new Response();
        $result = $this->dispatcher->dispatch($request, $response);
        $this->assertInstanceOf('Cake\Http\Response', $result);
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
        $request = new ServerRequest([
            'url' => 'some_controller/home',
            'params' => [
                'controller' => 'SomeController',
                'action' => 'home',
            ]
        ]);
        $response = $this->getMockBuilder('Cake\Http\Response')->getMock();
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
        $request = new ServerRequest([
            'url' => 'interface/index',
            'params' => [
                'controller' => 'Interface',
                'action' => 'index',
            ]
        ]);
        $response = $this->getMockBuilder('Cake\Http\Response')->getMock();
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
        $request = new ServerRequest([
            'url' => 'abstract/index',
            'params' => [
                'controller' => 'Abstract',
                'action' => 'index',
            ]
        ]);
        $response = $this->getMockBuilder('Cake\Http\Response')->getMock();
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
        $request = new ServerRequest([
            'url' => 'pages/home',
            'params' => [
                'plugin' => null,
                'controller' => 'somepages',
                'action' => 'display',
                'pass' => ['home'],
            ]
        ]);
        $response = $this->getMockBuilder('Cake\Http\Response')->getMock();
        $this->dispatcher->dispatch($request, $response);
    }

    /**
     * Ensure that a controller's startup event can stop the request.
     *
     * @return void
     */
    public function testStartupProcessAbort()
    {
        $request = new ServerRequest([
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
        $request = new ServerRequest([
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
