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
namespace Cake\Test\TestCase\Error\Middleware;

use Cake\Error\ErrorHandler;
use Cake\Error\ExceptionRendererInterface;
use Cake\Error\Middleware\ErrorHandlerMiddleware;
use Cake\Http\Exception\MissingControllerException;
use Cake\Http\Response;
use Cake\Http\ServerRequestFactory;
use Cake\Log\Log;
use Cake\TestSuite\TestCase;
use Error;
use LogicException;
use Psr\Log\LoggerInterface;
use TestApp\Http\TestRequestHandler;

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
    public function setUp(): void
    {
        parent::setUp();

        static::setAppNamespace();
        $this->logger = $this->getMockBuilder(LoggerInterface::class)->getMock();

        Log::reset();
        Log::setConfig('error_test', [
            'engine' => $this->logger,
        ]);
    }

    /**
     * Teardown
     *
     * @return void
     */
    public function tearDown(): void
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

        $middleware = new ErrorHandlerMiddleware();
        $result = $middleware->process($request, new TestRequestHandler());
        $this->assertInstanceOf(Response::class, $result);
    }

    /**
     * Test using a factory method to make a renderer.
     *
     * @return void
     */
    public function testRendererFactory()
    {
        $request = ServerRequestFactory::fromGlobals();

        $factory = function ($exception) {
            $this->assertInstanceOf('LogicException', $exception);
            $response = new Response();
            $mock = $this->getMockBuilder(ExceptionRendererInterface::class)
                ->setMethods(['render'])
                ->getMock();
            $mock->expects($this->once())
                ->method('render')
                ->will($this->returnValue($response));

            return $mock;
        };
        $middleware = new ErrorHandlerMiddleware(new ErrorHandler([
            'exceptionRenderer' => $factory,
        ]));
        $handler = new TestRequestHandler(function ($req) {
            throw new LogicException('Something bad');
        });
        $middleware->process($request, $handler);
    }

    /**
     * Test rendering an error page
     *
     * @return void
     */
    public function testHandleException()
    {
        $request = ServerRequestFactory::fromGlobals();
        $middleware = new ErrorHandlerMiddleware();
        $handler = new TestRequestHandler(function ($req) {
            throw new \Cake\Http\Exception\NotFoundException('whoops');
        });
        $result = $middleware->process($request, $handler);
        $this->assertInstanceOf('Cake\Http\Response', $result);
        $this->assertEquals(404, $result->getStatusCode());
        $this->assertStringContainsString('was not found', '' . $result->getBody());
    }

    /**
     * Test rendering an error page holds onto the original request.
     *
     * @return void
     */
    public function testHandleExceptionPreserveRequest()
    {
        $request = ServerRequestFactory::fromGlobals();
        $request = $request->withHeader('Accept', 'application/json');

        $middleware = new ErrorHandlerMiddleware();
        $handler = new TestRequestHandler(function ($req) {
            throw new \Cake\Http\Exception\NotFoundException('whoops');
        });
        $result = $middleware->process($request, $handler);
        $this->assertInstanceOf('Cake\Http\Response', $result);
        $this->assertEquals(404, $result->getStatusCode());
        $this->assertStringContainsString('"message": "whoops"', (string)$result->getBody());
        $this->assertStringContainsString('application/json', $result->getHeaderLine('Content-type'));
    }

    /**
     * Test handling PHP 7's Error instance.
     *
     * @return void
     */
    public function testHandlePHP7Error()
    {
        $middleware = new ErrorHandlerMiddleware();
        $request = ServerRequestFactory::fromGlobals();
        $error = new Error();

        $result = $middleware->handleException($error, $request);
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
                $this->stringContains('[Cake\Http\Exception\NotFoundException] Kaboom!'),
                $this->stringContains(str_replace('/', DS, 'vendor/phpunit/phpunit/src/Framework/TestCase.php')),
                $this->stringContains('Request URL: /target/url'),
                $this->stringContains('Referer URL: /other/path'),
                $this->logicalNot(
                    $this->stringContains('Previous: ')
                )
            ));

        $request = ServerRequestFactory::fromGlobals([
            'REQUEST_URI' => '/target/url',
            'HTTP_REFERER' => '/other/path',
        ]);
        $middleware = new ErrorHandlerMiddleware(['log' => true, 'trace' => true]);
        $handler = new TestRequestHandler(function ($req) {
            throw new \Cake\Http\Exception\NotFoundException('Kaboom!');
        });
        $result = $middleware->process($request, $handler);
        $this->assertEquals(404, $result->getStatusCode());
        $this->assertStringContainsString('was not found', '' . $result->getBody());
    }

    /**
     * Test rendering an error page logs errors with previous
     *
     * @return void
     */
    public function testHandleExceptionLogAndTraceWithPrevious()
    {
        $this->logger->expects($this->at(0))
            ->method('log')
            ->with('error', $this->logicalAnd(
                $this->stringContains('[Cake\Http\Exception\NotFoundException] Kaboom!'),
                $this->stringContains('Caused by: [Cake\Datasource\Exception\RecordNotFoundException] Previous logged'),
                $this->stringContains(str_replace('/', DS, 'vendor/phpunit/phpunit/src/Framework/TestCase.php')),
                $this->stringContains('Request URL: /target/url'),
                $this->stringContains('Referer URL: /other/path')
            ));

        $request = ServerRequestFactory::fromGlobals([
            'REQUEST_URI' => '/target/url',
            'HTTP_REFERER' => '/other/path',
        ]);
        $middleware = new ErrorHandlerMiddleware(['log' => true, 'trace' => true]);
        $handler = new TestRequestHandler(function ($req) {
            $previous = new \Cake\Datasource\Exception\RecordNotFoundException('Previous logged');
            throw new \Cake\Http\Exception\NotFoundException('Kaboom!', null, $previous);
        });
        $result = $middleware->process($request, $handler);
        $this->assertEquals(404, $result->getStatusCode());
        $this->assertStringContainsString('was not found', '' . $result->getBody());
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
        $middleware = new ErrorHandlerMiddleware([
            'log' => true,
            'skipLog' => ['Cake\Http\Exception\NotFoundException'],
        ]);
        $handler = new TestRequestHandler(function ($req) {
            throw new \Cake\Http\Exception\NotFoundException('Kaboom!');
        });
        $result = $middleware->process($request, $handler);
        $this->assertEquals(404, $result->getStatusCode());
        $this->assertStringContainsString('was not found', '' . $result->getBody());
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
                    '[Cake\Http\Exception\MissingControllerException] ' .
                    'Controller class Articles could not be found.'
                ),
                $this->stringContains('Exception Attributes:'),
                $this->stringContains("'class' => 'Articles'"),
                $this->stringContains('Request URL:')
            ));

        $request = ServerRequestFactory::fromGlobals();
        $middleware = new ErrorHandlerMiddleware(['log' => true]);
        $handler = new TestRequestHandler(function ($req) {
            throw new MissingControllerException(['class' => 'Articles']);
        });
        $result = $middleware->process($request, $handler);
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

        $factory = function ($exception) {
            $mock = $this->getMockBuilder(ExceptionRendererInterface::class)
                ->setMethods(['render'])
                ->getMock();
            $mock->expects($this->once())
                ->method('render')
                ->will($this->throwException(new LogicException('Rendering failed')));

            return $mock;
        };
        $middleware = new ErrorHandlerMiddleware(new ErrorHandler([
            'exceptionRenderer' => $factory,
        ]));
        $handler = new TestRequestHandler(function ($req) {
            throw new \Cake\Http\Exception\ServiceUnavailableException('whoops');
        });
        $response = $middleware->process($request, $handler);
        $this->assertEquals(500, $response->getStatusCode());
        $this->assertSame('An Internal Server Error Occurred', '' . $response->getBody());
    }
}
