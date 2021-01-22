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

use Cake\Core\Configure;
use Cake\Error\ErrorHandler;
use Cake\Error\ExceptionRendererInterface;
use Cake\Error\Middleware\ErrorHandlerMiddleware;
use Cake\Http\Exception\MissingControllerException;
use Cake\Http\Exception\RedirectException;
use Cake\Http\Response;
use Cake\Http\ServerRequestFactory;
use Cake\Log\Log;
use Cake\TestSuite\TestCase;
use Error;
use InvalidArgumentException;
use LogicException;
use Psr\Http\Message\ResponseInterface;
use TestApp\Http\TestRequestHandler;

/**
 * Test for ErrorHandlerMiddleware
 */
class ErrorHandlerMiddlewareTest extends TestCase
{
    /**
     * @var \Cake\Log\Engine\ArrayLog
     */
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

        Log::reset();
        Log::setConfig('error_test', [
            'className' => 'Array',
        ]);
        $this->logger = Log::engine('error_test');
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
     * Test constructor error
     *
     * @return void
     */
    public function testConstructorInvalid()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('$errorHandler argument must be a config array or ErrorHandler');
        new ErrorHandlerMiddleware('nope');
    }

    /**
     * Test returning a response works ok.
     *
     * @return void
     */
    public function testNoErrorResponse()
    {
        $request = ServerRequestFactory::fromGlobals();

        $middleware = new ErrorHandlerMiddleware();
        $result = $middleware->process($request, new TestRequestHandler());
        $this->assertInstanceOf(Response::class, $result);
        $this->assertCount(0, $this->logger->read());
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
                ->onlyMethods(['render'])
                ->getMock();
            $mock->expects($this->once())
                ->method('render')
                ->will($this->returnValue($response));

            return $mock;
        };
        $middleware = new ErrorHandlerMiddleware(new ErrorHandler([
            'exceptionRenderer' => $factory,
        ]));
        $handler = new TestRequestHandler(function () {
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
        $handler = new TestRequestHandler(function () {
            throw new \Cake\Http\Exception\NotFoundException('whoops');
        });
        $result = $middleware->process($request, $handler);
        $this->assertInstanceOf('Cake\Http\Response', $result);
        $this->assertSame(404, $result->getStatusCode());
        $this->assertStringContainsString('was not found', '' . $result->getBody());
    }

    /**
     * Test creating a redirect response
     *
     * @return void
     */
    public function testHandleRedirectException()
    {
        $request = ServerRequestFactory::fromGlobals();
        $middleware = new ErrorHandlerMiddleware();
        $handler = new TestRequestHandler(function () {
            throw new RedirectException('http://example.org/login');
        });
        $result = $middleware->process($request, $handler);
        $this->assertInstanceOf(ResponseInterface::class, $result);
        $this->assertSame(302, $result->getStatusCode());
        $this->assertEmpty((string)$result->getBody());
        $expected = [
            'location' => ['http://example.org/login'],
        ];
        $this->assertSame($expected, $result->getHeaders());
    }

    /**
     * Test creating a redirect response
     *
     * @return void
     */
    public function testHandleRedirectExceptionHeaders()
    {
        $request = ServerRequestFactory::fromGlobals();
        $middleware = new ErrorHandlerMiddleware();
        $handler = new TestRequestHandler(function () {
            $err = new RedirectException('http://example.org/login', 301, ['Constructor' => 'yes']);
            $this->deprecated(function () use ($err) {
                $err->addHeaders(['Constructor' => 'no', 'Method' => 'yes']);
            });
            throw $err;
        });

        $result = $middleware->process($request, $handler);
        $this->assertInstanceOf(ResponseInterface::class, $result);
        $this->assertSame(301, $result->getStatusCode());
        $this->assertEmpty('' . $result->getBody());
        $expected = [
            'location' => ['http://example.org/login'],
            'Constructor' => ['yes', 'no'],
            'Method' => ['yes'],
        ];
        $this->assertEquals($expected, $result->getHeaders());
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
        $handler = new TestRequestHandler(function () {
            throw new \Cake\Http\Exception\NotFoundException('whoops');
        });
        $result = $middleware->process($request, $handler);
        $this->assertInstanceOf('Cake\Http\Response', $result);
        $this->assertSame(404, $result->getStatusCode());
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
        $request = ServerRequestFactory::fromGlobals([
            'REQUEST_URI' => '/target/url',
            'HTTP_REFERER' => '/other/path',
        ]);
        $middleware = new ErrorHandlerMiddleware(['log' => true, 'trace' => true]);
        $handler = new TestRequestHandler(function () {
            throw new \Cake\Http\Exception\NotFoundException('Kaboom!');
        });
        $result = $middleware->process($request, $handler);
        $this->assertSame(404, $result->getStatusCode());
        $this->assertStringContainsString('was not found', '' . $result->getBody());

        $logs = $this->logger->read();
        $this->assertCount(1, $logs);
        $this->assertStringContainsString('error', $logs[0]);
        $this->assertStringContainsString('[Cake\Http\Exception\NotFoundException] Kaboom!', $logs[0]);
        $this->assertStringContainsString(
            str_replace('/', DS, 'vendor/phpunit/phpunit/src/Framework/TestCase.php'),
            $logs[0]
        );
        $this->assertStringContainsString('Request URL: /target/url', $logs[0]);
        $this->assertStringContainsString('Referer URL: /other/path', $logs[0]);
        $this->assertStringNotContainsString('Previous:', $logs[0]);
    }

    /**
     * Test rendering an error page logs errors with previous
     *
     * @return void
     */
    public function testHandleExceptionLogAndTraceWithPrevious()
    {
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
        $this->assertSame(404, $result->getStatusCode());
        $this->assertStringContainsString('was not found', '' . $result->getBody());

        $logs = $this->logger->read();
        $this->assertCount(1, $logs);
        $this->assertStringContainsString('error', $logs[0]);
        $this->assertStringContainsString('[Cake\Http\Exception\NotFoundException] Kaboom!', $logs[0]);
        $this->assertStringContainsString(
            'Caused by: [Cake\Datasource\Exception\RecordNotFoundException]',
            $logs[0]
        );
        $this->assertStringContainsString(
            str_replace('/', DS, 'vendor/phpunit/phpunit/src/Framework/TestCase.php'),
            $logs[0]
        );
        $this->assertStringContainsString('Request URL: /target/url', $logs[0]);
        $this->assertStringContainsString('Referer URL: /other/path', $logs[0]);
    }

    /**
     * Test rendering an error page skips logging for specific classes
     *
     * @return void
     */
    public function testHandleExceptionSkipLog()
    {
        $request = ServerRequestFactory::fromGlobals();
        $middleware = new ErrorHandlerMiddleware([
            'log' => true,
            'skipLog' => ['Cake\Http\Exception\NotFoundException'],
        ]);
        $handler = new TestRequestHandler(function () {
            throw new \Cake\Http\Exception\NotFoundException('Kaboom!');
        });
        $result = $middleware->process($request, $handler);
        $this->assertSame(404, $result->getStatusCode());
        $this->assertStringContainsString('was not found', '' . $result->getBody());

        $this->assertCount(0, $this->logger->read());
    }

    /**
     * Test rendering an error page logs exception attributes
     *
     * @return void
     */
    public function testHandleExceptionLogAttributes()
    {
        $request = ServerRequestFactory::fromGlobals();
        $middleware = new ErrorHandlerMiddleware(['log' => true]);
        $handler = new TestRequestHandler(function () {
            throw new MissingControllerException(['class' => 'Articles']);
        });
        $result = $middleware->process($request, $handler);
        $this->assertSame(404, $result->getStatusCode());

        $logs = $this->logger->read();
        $this->assertStringContainsString(
            '[Cake\Http\Exception\MissingControllerException] Controller class Articles could not be found.',
            $logs[0]
        );
        $this->assertStringContainsString('Exception Attributes:', $logs[0]);
        $this->assertStringContainsString("'class' => 'Articles'", $logs[0]);
        $this->assertStringContainsString('Request URL:', $logs[0]);
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
                ->onlyMethods(['render'])
                ->getMock();
            $mock->expects($this->once())
                ->method('render')
                ->will($this->throwException(new LogicException('Rendering failed')));

            return $mock;
        };
        $middleware = new ErrorHandlerMiddleware(new ErrorHandler([
            'exceptionRenderer' => $factory,
        ]));
        $handler = new TestRequestHandler(function () {
            throw new \Cake\Http\Exception\ServiceUnavailableException('whoops');
        });
        $response = $middleware->process($request, $handler);
        $this->assertSame(500, $response->getStatusCode());
        $this->assertSame('An Internal Server Error Occurred', '' . $response->getBody());
    }

    /**
     * Test exception args are not ignored in php7.4 with debug enabled.
     *
     * @return void
     */
    public function testExceptionArgs()
    {
        $this->skipIf(PHP_VERSION_ID < 70400);

        // Force exception_ignore_args to true for test
        ini_set('zend.exception_ignore_args', '1');

        // Debug disabled
        Configure::write('debug', false);
        new ErrorHandlerMiddleware();
        $this->assertSame('1', ini_get('zend.exception_ignore_args'));

        // Debug enabled
        Configure::write('debug', true);
        new ErrorHandlerMiddleware();
        $this->assertSame('0', ini_get('zend.exception_ignore_args'));
    }
}
