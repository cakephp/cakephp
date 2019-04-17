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
use Cake\Http\Session;
use Cake\Routing\DispatcherFactory;
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
     *
     * @group deprecated
     * @return void
     */
    public function testDispatcherFactoryCompat()
    {
        $this->deprecated(function () {
            $filter = $this->getMockBuilder('Cake\Routing\DispatcherFilter')
                ->setMethods(['beforeDispatch', 'afterDispatch'])
                ->getMock();
            DispatcherFactory::add($filter);
            $dispatcher = new ActionDispatcher(null, null, DispatcherFactory::filters());
            $this->assertCount(1, $dispatcher->getFilters());
            $this->assertSame($filter, $dispatcher->getFilters()[0]);
        });
    }

    /**
     * Test adding routing filters
     *
     * @group deprecated
     * @return void
     */
    public function testAddFilter()
    {
        $this->deprecated(function () {
            $this->assertCount(0, $this->dispatcher->getFilters());
            $events = $this->dispatcher->getEventManager();
            $this->assertCount(0, $events->listeners('Dispatcher.beforeDispatch'));
            $this->assertCount(0, $events->listeners('Dispatcher.afterDispatch'));

            $filter = $this->getMockBuilder('Cake\Routing\DispatcherFilter')
                ->setMethods(['beforeDispatch', 'afterDispatch'])
                ->getMock();
            $this->dispatcher->addFilter($filter);

            $this->assertCount(1, $this->dispatcher->getFilters());
            $this->assertCount(1, $events->listeners('Dispatcher.beforeDispatch'));
            $this->assertCount(1, $events->listeners('Dispatcher.afterDispatch'));
        });
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

        $req = new ServerRequest();
        $res = new Response();
        $dispatcher->getEventManager()->on('Dispatcher.beforeDispatch', function () use ($response) {
            return $response;
        });
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
        $this->dispatcher->getEventManager()->on('Dispatcher.afterDispatch', function (Event $event) {
            $response = $event->getData('response');
            $event->setData('response', $response->withStringBody('Filter body'));
        });
        $result = $this->dispatcher->dispatch($req, $res);
        $this->assertSame('Filter body', (string)$result->getBody(), 'Should be response from filter.');
    }

    /**
     * Test that a controller action returning a response
     * results in no afterDispatch event.
     *
     * @return void
     */
    public function testDispatchActionReturnResponseNoAfterDispatch()
    {
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
        $this->dispatcher->getEventManager()->on('Dispatcher.afterDispatch', function () {
            $this->fail('no afterDispatch event should be fired');
        });
        $result = $this->dispatcher->dispatch($req, $res);
        $this->assertSame('Hello Jane', (string)$result->getBody(), 'Response from controller.');
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
     * @return void
     */
    public function testDispatchInvalidResponse()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Controller actions can only return Cake\Http\Response or null');
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
        $this->assertContains('posts index', (string)$result->getBody());
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
        $this->assertContains('autoRender false body', (string)$result->getBody());
    }

    /**
     * testMissingController method
     *
     * @return void
     */
    public function testMissingController()
    {
        $this->expectException(\Cake\Routing\Exception\MissingControllerException::class);
        $this->expectExceptionMessage('Controller class SomeController could not be found.');
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
     * @return void
     */
    public function testMissingControllerInterface()
    {
        $this->expectException(\Cake\Routing\Exception\MissingControllerException::class);
        $this->expectExceptionMessage('Controller class Interface could not be found.');
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
     * @return void
     */
    public function testMissingControllerAbstract()
    {
        $this->expectException(\Cake\Routing\Exception\MissingControllerException::class);
        $this->expectExceptionMessage('Controller class Abstract could not be found.');
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
     * @return void
     */
    public function testMissingControllerLowercase()
    {
        $this->expectException(\Cake\Routing\Exception\MissingControllerException::class);
        $this->expectExceptionMessage('Controller class somepages could not be found.');
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
        $this->assertSame('startup stop', (string)$result->getBody());
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
        $this->assertSame('shutdown stop', (string)$result->getBody());
    }
}
