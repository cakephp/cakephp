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
namespace Cake\Test\TestCase\Error\Middleware;

use Cake\Error\Middleware\ErrorHandlerMiddleware;
use Cake\Http\Response;
use Cake\Http\ServerRequestFactory;
use Cake\Log\Log;
use Cake\TestSuite\TestCase;
use Error;
use LogicException;
use Psr\Log\LoggerInterface;

/**
 * Test for ErrorHandlerMiddleware
 */
class ErrorHandlerMiddlewareTest extends TestCase
{
    protected $logger;

    /**
     * setup
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();

        static::setAppNamespace();
        $this->logger = $this->getMockBuilder(LoggerInterface::class)->getMock();

        Log::reset();
        Log::config('error_test', [
            'engine' => $this->logger
        ]);
    }

    /**
     * Teardown
     *
     * @return void
     */
    public function tearDown()
    {
        parent::tearDown();
        Log::drop('error_test');
    }

    /**
     * Test returning a response works ok.
     *
     * @return void
     */
    public function testNoErrorResponse()
    {
        $this->logger->expects($this->never())->method('log');

        $request = ServerRequestFactory::fromGlobals();
        $response = new Response();

        $middleware = new ErrorHandlerMiddleware();
        $next = function ($req, $res) {
            return $res;
        };
        $result = $middleware($request, $response, $next);
        $this->assertSame($result, $response);
    }

    /**
     * Test an invalid rendering class.
     *
     */
    public function testInvalidRenderer()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('The \'TotallyInvalid\' renderer class could not be found');
        $request = ServerRequestFactory::fromGlobals();
        $response = new Response();

        $middleware = new ErrorHandlerMiddleware('TotallyInvalid');
        $next = function ($req, $res) {
            throw new \Exception('Something bad');
        };
        $middleware($request, $response, $next);
    }

    /**
     * Test using a factory method to make a renderer.
     *
     * @return void
     */
    public function testRendererFactory()
    {
        $request = ServerRequestFactory::fromGlobals();
        $response = new Response();

        $factory = function ($exception) {
            $this->assertInstanceOf('LogicException', $exception);
            $response = new Response;
            $mock = $this->getMockBuilder('StdClass')
                ->setMethods(['render'])
                ->getMock();
            $mock->expects($this->once())
                ->method('render')
                ->will($this->returnValue($response));

            return $mock;
        };
        $middleware = new ErrorHandlerMiddleware($factory);
        $next = function ($req, $res) {
            throw new LogicException('Something bad');
        };
        $middleware($request, $response, $next);
    }

    /**
     * Test rendering an error page
     *
     * @return void
     */
    public function testHandleException()
    {
        $request = ServerRequestFactory::fromGlobals();
        $response = new Response();
        $middleware = new ErrorHandlerMiddleware();
        $next = function ($req, $res) {
            throw new \Cake\Network\Exception\NotFoundException('whoops');
        };
        $result = $middleware($request, $response, $next);
        $this->assertInstanceOf('Psr\Http\Message\ResponseInterface', $result);
        $this->assertInstanceOf('Cake\Http\Response', $result);
        $this->assertNotSame($result, $response);
        $this->assertEquals(404, $result->getStatusCode());
        $this->assertContains('was not found', '' . $result->getBody());
    }

    /**
     * Test handling PHP 7's Error instance.
     *
     * @return void
     */
    public function testHandlePHP7Error()
    {
        $this->skipIf(version_compare(PHP_VERSION, '7.0.0', '<'), 'Error class only exists since PHP 7.');

        $middleware = new ErrorHandlerMiddleware();
        $request = ServerRequestFactory::fromGlobals();
        $response = new Response();
        $error = new Error();

        $result = $middleware->handleException($error, $request, $response);
        $this->assertInstanceOf(Response::class, $result);
    }

    /**
     * Test rendering an error page logs errors
     *
     * @return void
     */
    public function testHandleExceptionLogAndTrace()
    {
        $this->logger->expects($this->at(0))
            ->method('log')
            ->with('error', $this->logicalAnd(
                $this->stringContains('[Cake\Network\Exception\NotFoundException] Kaboom!'),
                $this->stringContains('ErrorHandlerMiddlewareTest->testHandleException'),
                $this->stringContains('Request URL: /target/url'),
                $this->stringContains('Referer URL: /other/path')
            ));

        $request = ServerRequestFactory::fromGlobals([
            'REQUEST_URI' => '/target/url',
            'HTTP_REFERER' => '/other/path'
        ]);
        $response = new Response();
        $middleware = new ErrorHandlerMiddleware(null, ['log' => true, 'trace' => true]);
        $next = function ($req, $res) {
            throw new \Cake\Network\Exception\NotFoundException('Kaboom!');
        };
        $result = $middleware($request, $response, $next);
        $this->assertNotSame($result, $response);
        $this->assertEquals(404, $result->getStatusCode());
        $this->assertContains('was not found', '' . $result->getBody());
    }

    /**
     * Test rendering an error page skips logging for specific classes
     *
     * @return void
     */
    public function testHandleExceptionSkipLog()
    {
        $this->logger->expects($this->never())->method('log');

        $request = ServerRequestFactory::fromGlobals();
        $response = new Response();
        $middleware = new ErrorHandlerMiddleware(null, [
            'log' => true,
            'skipLog' => ['Cake\Network\Exception\NotFoundException']
        ]);
        $next = function ($req, $res) {
            throw new \Cake\Network\Exception\NotFoundException('Kaboom!');
        };
        $result = $middleware($request, $response, $next);
        $this->assertNotSame($result, $response);
        $this->assertEquals(404, $result->getStatusCode());
        $this->assertContains('was not found', '' . $result->getBody());
    }

    /**
     * Test rendering an error page logs exception attributes
     *
     * @return void
     */
    public function testHandleExceptionLogAttributes()
    {
        $this->logger->expects($this->at(0))
            ->method('log')
            ->with('error', $this->logicalAnd(
                $this->stringContains(
                    '[Cake\Routing\Exception\MissingControllerException] ' .
                    'Controller class Articles could not be found.'
                ),
                $this->stringContains('Exception Attributes:'),
                $this->stringContains("'class' => 'Articles'"),
                $this->stringContains('Request URL:')
            ));

        $request = ServerRequestFactory::fromGlobals();
        $response = new Response();
        $middleware = new ErrorHandlerMiddleware(null, ['log' => true]);
        $next = function ($req, $res) {
            throw new \Cake\Routing\Exception\MissingControllerException(['class' => 'Articles']);
        };
        $result = $middleware($request, $response, $next);
        $this->assertNotSame($result, $response);
        $this->assertEquals(404, $result->getStatusCode());
    }

    /**
     * Test handling an error and having rendering fail.
     *
     * @return void
     */
    public function testHandleExceptionRenderingFails()
    {
        $request = ServerRequestFactory::fromGlobals();
        $response = new Response();

        $factory = function ($exception) {
            $mock = $this->getMockBuilder('StdClass')
                ->setMethods(['render'])
                ->getMock();
            $mock->expects($this->once())
                ->method('render')
                ->will($this->throwException(new LogicException('Rendering failed')));

            return $mock;
        };
        $middleware = new ErrorHandlerMiddleware($factory);
        $next = function ($req, $res) {
            throw new \Cake\Network\Exception\ServiceUnavailableException('whoops');
        };
        $response = $middleware($request, $response, $next);
        $this->assertEquals(500, $response->getStatusCode());
        $this->assertEquals('An Internal Server Error Occurred', '' . $response->getBody());
    }
}
