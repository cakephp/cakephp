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
namespace Cake\Test\TestCase;

use Cake\Http\Server;
use Cake\TestSuite\TestCase;
use TestApp\Http\BadResponseApplication;
use TestApp\Http\InvalidMiddlewareApplication;
use TestApp\Http\MiddlewareApplication;
use Zend\Diactoros\Response;
use Zend\Diactoros\ServerRequestFactory;

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
    }

    /**
     * test get/set on the app
     *
     * @return void
     */
    public function testAppGetSet()
    {
        $app = $this->getMock('Cake\Http\BaseApplication', [], [$this->config]);
        $server = new Server($app);
        $this->assertSame($app, $server->getApp($app));
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
     * @expectedException RuntimeException
     * @expectedExceptionMessage The application `middleware` method
     */
    public function testRunWithApplicationNotMakingMiddleware()
    {
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
     * @expectedException RuntimeException
     * @expectedExceptionMessage Application did not create a response. Got "Not a response" instead.
     */
    public function testRunMiddlewareNoResponse()
    {
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

        $emitter = $this->getMock('Zend\Diactoros\Response\EmitterInterface');
        $emitter->expects($this->once())
            ->method('emit')
            ->with($final);

        $app = new MiddlewareApplication($this->config);
        $server = new Server($app);
        $server->emit($server->run(null, $response), $emitter);
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

        $server->eventManager()->on('Server.buildMiddleware', function ($event, $middleware) {
            $this->assertInstanceOf('Cake\Http\MiddlewareStack', $middleware);
            $middleware->push(function ($req, $res, $next) {
                $this->called = true;
                return $next($req, $res);
            });
            $this->middleware = $middleware;
        });
        $server->run();
        $this->assertTrue($this->called, 'Middleware added in the event was not triggered.');
        $this->assertInstanceOf('Closure', $this->middleware->get(3), '2nd last middleware is a clousure');
        $this->assertSame($app, $this->middleware->get(4), 'Last middleware is an app instance');
    }
}
