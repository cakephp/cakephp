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
namespace Cake\Test\TestCase;

use Cake\Core\HttpApplicationInterface;
use Cake\Event\Event;
use Cake\Event\EventManager;
use Cake\Http\BaseApplication;
use Cake\Http\CallbackStream;
use Cake\Http\MiddlewareQueue;
use Cake\Http\Server;
use Cake\TestSuite\TestCase;
use InvalidArgumentException;
use RuntimeException;
use TestApp\Http\BadResponseApplication;
use TestApp\Http\InvalidMiddlewareApplication;
use TestApp\Http\MiddlewareApplication;
use Zend\Diactoros\Response;
use Zend\Diactoros\ServerRequestFactory;

require_once __DIR__ . '/server_mocks.php';

/**
 * Server test case
 */
class ServerTest extends TestCase
{
    /**
     * Setup
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $this->server = $_SERVER;
        $this->config = dirname(dirname(__DIR__));
        $GLOBALS['mockedHeaders'] = [];
        $GLOBALS['mockedHeadersSent'] = true;
    }

    /**
     * Teardown
     *
     * @return void
     */
    public function tearDown()
    {
        parent::tearDown();
        $_SERVER = $this->server;
        unset($GLOBALS['mockedHeadersSent']);
    }

    /**
     * test get/set on the app
     *
     * @return void
     */
    public function testAppGetSet()
    {
        $app = $this->getMockBuilder('Cake\Http\BaseApplication')
            ->setConstructorArgs([$this->config])
            ->getMock();

        $manager = new EventManager();
        $app->method('getEventManager')
            ->willReturn($manager);

        $server = new Server($app);
        $this->assertSame($app, $server->getApp($app));
        $this->assertSame($app->getEventManager(), $server->getEventManager());
    }

    /**
     * test run building a response
     *
     * @return void
     */
    public function testRunWithRequestAndResponse()
    {
        $response = new Response('php://memory', 200, ['X-testing' => 'source header']);
        $request = ServerRequestFactory::fromGlobals();
        $request = $request->withHeader('X-pass', 'request header');

        $app = new MiddlewareApplication($this->config);
        $server = new Server($app);
        $res = $server->run($request, $response);
        $this->assertEquals(
            'source header',
            $res->getHeaderLine('X-testing'),
            'Input response is carried through out middleware'
        );
        $this->assertEquals(
            'request header',
            $res->getHeaderLine('X-pass'),
            'Request is used in middleware'
        );
    }

    /**
     * test run calling plugin hooks
     *
     * @return void
     */
    public function testRunCallingPluginHooks()
    {
        $response = new Response('php://memory', 200, ['X-testing' => 'source header']);
        $request = ServerRequestFactory::fromGlobals();
        $request = $request->withHeader('X-pass', 'request header');

        $app = $this->getMockBuilder(MiddlewareApplication::class)
            ->setMethods(['pluginBootstrap', 'pluginEvents', 'pluginMiddleware'])
            ->setConstructorArgs([$this->config])
            ->getMock();
        $app->expects($this->at(0))
            ->method('pluginBootstrap');
        $app->expects($this->at(1))
            ->method('pluginMiddleware')
            ->with($this->isInstanceOf(MiddlewareQueue::class))
            ->will($this->returnCallback(function ($middleware) {
                return $middleware;
            }));

        $server = new Server($app);
        $res = $server->run($request, $response);
        $this->assertEquals(
            'source header',
            $res->getHeaderLine('X-testing'),
            'Input response is carried through out middleware'
        );
        $this->assertEquals(
            'request header',
            $res->getHeaderLine('X-pass'),
            'Request is used in middleware'
        );
    }

    /**
     * test run building a request from globals.
     *
     * @return void
     */
    public function testRunWithGlobals()
    {
        $_SERVER['HTTP_X_PASS'] = 'globalvalue';

        $app = new MiddlewareApplication($this->config);
        $server = new Server($app);

        $res = $server->run();
        $this->assertEquals(
            'globalvalue',
            $res->getHeaderLine('X-pass'),
            'Default request is made from server'
        );
    }

    /**
     * Test an application failing to build middleware properly
     *
     * @return void
     */
    public function testRunWithApplicationNotMakingMiddleware()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The application `middleware` method');
        $app = new InvalidMiddlewareApplication($this->config);
        $server = new Server($app);
        $server->run();
    }

    /**
     * Test middleware being invoked.
     *
     * @return void
     */
    public function testRunMultipleMiddlewareSuccess()
    {
        $app = new MiddlewareApplication($this->config);
        $server = new Server($app);
        $res = $server->run();
        $this->assertSame('first', $res->getHeaderLine('X-First'));
        $this->assertSame('second', $res->getHeaderLine('X-Second'));
    }

    /**
     * Test middleware not creating a response.
     *
     * @return void
     */
    public function testRunMiddlewareNoResponse()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Application did not create a response. Got "Not a response" instead.');
        $app = new BadResponseApplication($this->config);
        $server = new Server($app);
        $server->run();
    }

    /**
     * Test that emit invokes the appropriate methods on the emitter.
     *
     * @return void
     */
    public function testEmit()
    {
        $response = new Response('php://memory', 200, ['x-testing' => 'source header']);
        $final = $response
            ->withHeader('X-First', 'first')
            ->withHeader('X-Second', 'second');

        $emitter = $this->getMockBuilder('Zend\Diactoros\Response\EmitterInterface')->getMock();
        $emitter->expects($this->once())
            ->method('emit')
            ->with($final);

        $app = new MiddlewareApplication($this->config);
        $server = new Server($app);
        $server->emit($server->run(null, $response), $emitter);
    }

    /**
     * Test that emit invokes the appropriate methods on the emitter.
     *
     * @return void
     */
    public function testEmitCallbackStream()
    {
        $GLOBALS['mockedHeadersSent'] = false;
        $response = new Response('php://memory', 200, ['x-testing' => 'source header']);
        $response = $response->withBody(new CallbackStream(function () {
            echo 'body content';
        }));

        $app = new MiddlewareApplication($this->config);
        $server = new Server($app);
        ob_start();
        $server->emit($response);
        $result = ob_get_clean();
        $this->assertEquals('body content', $result);
    }

    /**
     * Ensure that the Server.buildMiddleware event is fired.
     *
     * @return void
     */
    public function testBuildMiddlewareEvent()
    {
        $app = new MiddlewareApplication($this->config);
        $server = new Server($app);
        $this->called = false;

        $server->getEventManager()->on('Server.buildMiddleware', function (Event $event, $middleware) {
            $this->assertInstanceOf('Cake\Http\MiddlewareQueue', $middleware);
            $middleware->add(function ($req, $res, $next) {
                $this->called = true;

                return $next($req, $res);
            });
            $this->middleware = $middleware;
        });
        $server->run();
        $this->assertTrue($this->called, 'Middleware added in the event was not triggered.');
        $this->assertInstanceOf('Closure', $this->middleware->get(3), '2nd last middleware is a closure');
        $this->assertSame($app, $this->middleware->get(4), 'Last middleware is an app instance');
    }

    /**
     * test event manager proxies to the application.
     *
     * @return void
     */
    public function testEventManagerProxies()
    {
        $app = $this->getMockForAbstractClass(
            BaseApplication::class,
            [$this->config]
        );

        $server = new Server($app);
        $this->assertSame($app->getEventManager(), $server->getEventManager());
    }

    /**
     * test event manager cannot be set on applications without events.
     *
     * @return void
     */
    public function testGetEventManagerNonEventedApplication()
    {
        $app = $this->createMock(HttpApplicationInterface::class);

        $server = new Server($app);
        $this->assertSame(EventManager::instance(), $server->getEventManager());
    }

    /**
     * test event manager cannot be set on applications without events.
     *
     * @return void
     */
    public function testSetEventManagerNonEventedApplication()
    {
        $this->expectException(InvalidArgumentException::class);
        $app = $this->createMock(HttpApplicationInterface::class);

        $events = new EventManager();
        $server = new Server($app);
        $server->setEventManager($events);
    }

    /**
     * test deprecated method defined in interface
     *
     * @return void
     */
    public function testEventManagerCompat()
    {
        $this->deprecated(function () {
            $app = $this->createMock(HttpApplicationInterface::class);

            $server = new Server($app);
            $this->assertSame(EventManager::instance(), $server->eventManager());
        });
    }
}
