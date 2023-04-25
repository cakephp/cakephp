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
use Laminas\Diactoros\Response as LaminasResponse;
use Laminas\Diactoros\ServerRequest as LaminasServerRequest;
use Psr\Http\Message\ResponseInterface;
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
     */
    public function tearDown(): void
    {
        parent::tearDown();
        $_SERVER = $this->server;
        unset($GLOBALS['mockedHeadersSent']);
    }

    /**
     * test get/set on the app
     */
    public function testAppGetSet(): void
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
     */
    public function testRunWithRequest(): void
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
     */
    public function testRunCallingPluginHooks(): void
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
     */
    public function testRunWithGlobals(): void
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
     */
    public function testRunMultipleMiddlewareSuccess(): void
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
    public function testRunClosesSessionIfServerRequestUsed(): void
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
    public function testRunDoesNotCloseSessionIfServerRequestNotUsed(): void
    {
        $request = new LaminasServerRequest();

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
     */
    public function testEmit(): void
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
     */
    public function testEmitCallbackStream(): void
    {
        $GLOBALS['mockedHeadersSent'] = false;
        $response = new LaminasResponse('php://memory', 200, ['x-testing' => 'source header']);
        $response = $response->withBody(new CallbackStream(function (): void {
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
     */
    public function testBuildMiddlewareEvent(): void
    {
        $app = new MiddlewareApplication($this->config);
        $server = new Server($app);
        $called = false;

        $server->getEventManager()->on('Server.buildMiddleware', function (EventInterface $event, MiddlewareQueue $middlewareQueue) use (&$called): void {
            $middlewareQueue->add(function ($request, $handler) use (&$called) {
                $called = true;

                return $handler->handle($request);
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
     */
    public function testEventManagerProxies(): void
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
     */
    public function testGetEventManagerNonEventedApplication(): void
    {
        /** @var \Cake\Core\HttpApplicationInterface|\PHPUnit\Framework\MockObject\MockObject $app */
        $app = $this->createMock(HttpApplicationInterface::class);

        $server = new Server($app);
        $this->assertSame(EventManager::instance(), $server->getEventManager());
    }

    /**
     * test event manager cannot be set on applications without events.
     */
    public function testSetEventManagerNonEventedApplication(): void
    {
        /** @var \Cake\Core\HttpApplicationInterface|\PHPUnit\Framework\MockObject\MockObject $app */
        $app = $this->createMock(HttpApplicationInterface::class);

        $events = new EventManager();
        $server = new Server($app);

        $this->expectException(InvalidArgumentException::class);

        $server->setEventManager($events);
    }

    /**
     * Test server run works without an application implementing ContainerApplicationInterface
     */
    public function testAppWithoutContainerApplicationInterface(): void
    {
        /** @var \Cake\Core\HttpApplicationInterface|\PHPUnit\Framework\MockObject\MockObject $app */
        $app = $this->createMock(HttpApplicationInterface::class);
        $server = new Server($app);

        $request = new ServerRequest();
        $this->assertInstanceOf(ResponseInterface::class, $server->run($request));
    }
}
