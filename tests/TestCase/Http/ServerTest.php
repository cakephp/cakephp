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
 * @since         3.3.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Http;

use Cake\Core\HttpApplicationInterface;
use Cake\Event\EventInterface;
use Cake\Event\EventManager;
use Cake\Http\BaseApplication;
use Cake\Http\CallbackStream;
use Cake\Http\MiddlewareQueue;
use Cake\Http\Server;
use Cake\Http\ServerRequest;
use Cake\Http\Session;
use Cake\TestSuite\TestCase;
use InvalidArgumentException;
use Laminas\Diactoros\Response;
use TestApp\Http\MiddlewareApplication;

require_once __DIR__ . '/server_mocks.php';

/**
 * Server test case
 */
class ServerTest extends TestCase
{
    /**
     * @var string
     */
    protected $config;

    /**
     * @var array
     */
    protected $server;

    /**
     * @var \Cake\Http\MiddlewareQueue
     */
    protected $middlewareQueue;

    /**
     * Setup
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->server = $_SERVER;
        $this->config = dirname(dirname(__DIR__)) . '/test_app/config';
        $GLOBALS['mockedHeaders'] = [];
        $GLOBALS['mockedHeadersSent'] = true;
    }

    /**
     * Teardown
     *
     * @return void
     */
    public function tearDown(): void
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
        /** @var \Cake\Http\BaseApplication|\PHPUnit\Framework\MockObject\MockObject $app */
        $app = $this->getMockBuilder(BaseApplication::class)
            ->setConstructorArgs([$this->config])
            ->getMock();

        $manager = new EventManager();
        $app->method('getEventManager')
            ->willReturn($manager);

        $server = new Server($app);
        $this->assertSame($app, $server->getApp());
        $this->assertSame($app->getEventManager(), $server->getEventManager());
    }

    /**
     * test run building a response
     *
     * @return void
     */
    public function testRunWithRequest()
    {
        $request = new ServerRequest();
        $request = $request->withHeader('X-pass', 'request header');

        $app = new MiddlewareApplication($this->config);
        $server = new Server($app);
        $res = $server->run($request);
        $this->assertSame(
            'source header',
            $res->getHeaderLine('X-testing'),
            'Input response is carried through out middleware'
        );
        $this->assertSame(
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
        $request = new ServerRequest();
        $request = $request->withHeader('X-pass', 'request header');

        /** @var \TestApp\Http\MiddlewareApplication|\PHPUnit\Framework\MockObject\MockObject $app */
        $app = $this->getMockBuilder(MiddlewareApplication::class)
            ->onlyMethods(['pluginBootstrap', 'pluginMiddleware'])
            ->addMethods(['pluginEvents'])
            ->setConstructorArgs([$this->config])
            ->getMock();
        $app->expects($this->once())
            ->method('pluginBootstrap');
        $app->expects($this->once())
            ->method('pluginMiddleware')
            ->with($this->isInstanceOf(MiddlewareQueue::class))
            ->will($this->returnCallback(function ($middleware) {
                return $middleware;
            }));

        $server = new Server($app);
        $res = $server->run($request);
        $this->assertSame(
            'source header',
            $res->getHeaderLine('X-testing'),
            'Input response is carried through out middleware'
        );
        $this->assertSame(
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
        $this->assertSame(
            'globalvalue',
            $res->getHeaderLine('X-pass'),
            'Default request is made from server'
        );
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
     * Test that run closes session after invoking the application (if CakePHP ServerRequest is used).
     */
    public function testRunClosesSessionIfServerRequestUsed()
    {
        $sessionMock = $this->createMock(Session::class);

        $sessionMock->expects($this->once())
            ->method('close');

        $app = new MiddlewareApplication($this->config);
        $server = new Server($app);
        $request = new ServerRequest(['session' => $sessionMock]);
        $res = $server->run($request);

        // assert that app was executed correctly
        $this->assertSame(
            200,
            $res->getStatusCode(),
            'Application was expected to be executed'
        );
        $this->assertSame(
            'source header',
            $res->getHeaderLine('X-testing'),
            'Application was expected to be executed'
        );
    }

    /**
     * Test that run does not close the session if CakePHP ServerRequest is not used.
     */
    public function testRunDoesNotCloseSessionIfServerRequestNotUsed()
    {
        $request = new \Laminas\Diactoros\ServerRequest();

        $app = new MiddlewareApplication($this->config);
        $server = new Server($app);
        $res = $server->run($request);

        // assert that app was executed correctly
        $this->assertSame(
            200,
            $res->getStatusCode(),
            'Application was expected to be executed'
        );
        $this->assertSame(
            'source header',
            $res->getHeaderLine('X-testing'),
            'Application was expected to be executed'
        );
    }

    /**
     * Test that emit invokes the appropriate methods on the emitter.
     *
     * @return void
     */
    public function testEmit()
    {
        $app = new MiddlewareApplication($this->config);
        $server = new Server($app);
        $response = $server->run();
        $final = $response
            ->withHeader('X-First', 'first')
            ->withHeader('X-Second', 'second');

        $emitter = $this->getMockBuilder('Laminas\HttpHandlerRunner\Emitter\EmitterInterface')->getMock();
        $emitter->expects($this->once())
            ->method('emit')
            ->with($final);

        $server->emit($final, $emitter);
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
        $this->assertSame('body content', $result);
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
        $called = false;

        $server->getEventManager()->on('Server.buildMiddleware', function (EventInterface $event, MiddlewareQueue $middlewareQueue) use (&$called) {
            $middlewareQueue->add(function ($req, $res, $next) use (&$called) {
                $called = true;

                return $next($req, $res);
            });
            $this->middlewareQueue = $middlewareQueue;
        });
        $server->run();
        $this->assertTrue($called, 'Middleware added in the event was not triggered.');
        $this->middlewareQueue->seek(3);
        $this->assertInstanceOf('Closure', $this->middlewareQueue->current()->getCallable(), '2nd last middleware is a closure');
    }

    /**
     * test event manager proxies to the application.
     *
     * @return void
     */
    public function testEventManagerProxies()
    {
        /** @var \Cake\Http\BaseApplication|\PHPUnit\Framework\MockObject\MockObject $app */
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
        /** @var \Cake\Core\HttpApplicationInterface|\PHPUnit\Framework\MockObject\MockObject $app */
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
        /** @var \Cake\Core\HttpApplicationInterface|\PHPUnit\Framework\MockObject\MockObject $app */
        $app = $this->createMock(HttpApplicationInterface::class);

        $events = new EventManager();
        $server = new Server($app);

        $this->expectException(InvalidArgumentException::class);

        $server->setEventManager($events);
    }
}
