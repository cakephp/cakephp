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
use Cake\Datasource\Exception\RecordNotFoundException;
use Cake\Error\ErrorHandler;
use Cake\Error\ExceptionRendererInterface;
use Cake\Error\ExceptionTrap;
use Cake\Error\Middleware\ErrorHandlerMiddleware;
use Cake\Error\Renderer\WebExceptionRenderer;
use Cake\Event\EventInterface;
use Cake\Event\EventManager;
use Cake\Http\Exception\MissingControllerException;
use Cake\Http\Exception\NotFoundException;
use Cake\Http\Exception\RedirectException;
use Cake\Http\Exception\ServiceUnavailableException;
use Cake\Http\Response;
use Cake\Http\ServerRequestFactory;
use Cake\Log\Log;
use Cake\Routing\Router;
use Cake\TestSuite\TestCase;
use Error;
use InvalidArgumentException;
use LogicException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TestApp\Application;
use TestApp\Http\TestRequestHandler;
use Throwable;

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
     */
    public function tearDown(): void
    {
        parent::tearDown();
        Log::drop('error_test');
    }

    /**
     * Test constructor error
     */
    public function testConstructorInvalid(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            '$errorHandler argument must be a config array or ExceptionTrap'
        );
        new ErrorHandlerMiddleware('nope');
    }

    /**
     * Test returning a response works ok.
     */
    public function testNoErrorResponse(): void
    {
        $request = ServerRequestFactory::fromGlobals();

        $middleware = new ErrorHandlerMiddleware();
        $result = $middleware->process($request, new TestRequestHandler());
        $this->assertInstanceOf(Response::class, $result);
        $this->assertCount(0, $this->logger->read());
    }

    /**
     * Test using a factory method to make a renderer.
     */
    public function testRendererFactory(): void
    {
        $this->deprecated(function () {
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
            $handler = new TestRequestHandler(function (): void {
                throw new LogicException('Something bad');
            });
            $middleware->process($request, $handler);
        });
    }

    /**
     * Test rendering an error page
     */
    public function testHandleException(): void
    {
        $request = ServerRequestFactory::fromGlobals();
        $middleware = new ErrorHandlerMiddleware();
        $handler = new TestRequestHandler(function (): void {
            throw new NotFoundException('whoops');
        });
        $result = $middleware->process($request, $handler);
        $this->assertInstanceOf('Cake\Http\Response', $result);
        $this->assertSame(404, $result->getStatusCode());
        $this->assertStringContainsString('was not found', '' . $result->getBody());
    }

    /**
     * Test rendering an error page with an exception trap
     */
    public function testHandleExceptionWithExceptionTrap(): void
    {
        $request = ServerRequestFactory::fromGlobals();
        $middleware = new ErrorHandlerMiddleware(new ExceptionTrap([
            'exceptionRenderer' => WebExceptionRenderer::class,
        ]));
        $handler = new TestRequestHandler(function (): void {
            throw new NotFoundException('whoops');
        });
        $result = $middleware->process($request, $handler);
        $this->assertInstanceOf('Cake\Http\Response', $result);
        $this->assertSame(404, $result->getStatusCode());
        $this->assertStringContainsString('was not found', '' . $result->getBody());
    }

    /**
     * Test creating a redirect response
     */
    public function testHandleRedirectException(): void
    {
        $request = ServerRequestFactory::fromGlobals();
        $middleware = new ErrorHandlerMiddleware();
        $handler = new TestRequestHandler(function (): void {
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
     */
    public function testHandleRedirectExceptionHeaders(): void
    {
        $request = ServerRequestFactory::fromGlobals();
        $middleware = new ErrorHandlerMiddleware();
        $handler = new TestRequestHandler(function (): void {
            $err = new RedirectException('http://example.org/login', 301, ['Constructor' => 'yes']);
            $this->deprecated(function () use ($err): void {
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
     */
    public function testHandleExceptionPreserveRequest(): void
    {
        $request = ServerRequestFactory::fromGlobals();
        $request = $request->withHeader('Accept', 'application/json');

        $middleware = new ErrorHandlerMiddleware();
        $handler = new TestRequestHandler(function (): void {
            throw new NotFoundException('whoops');
        });
        $result = $middleware->process($request, $handler);
        $this->assertInstanceOf('Cake\Http\Response', $result);
        $this->assertSame(404, $result->getStatusCode());
        $this->assertStringContainsString('"message": "whoops"', (string)$result->getBody());
        $this->assertStringContainsString('application/json', $result->getHeaderLine('Content-type'));
    }

    /**
     * Test handling PHP 7's Error instance.
     */
    public function testHandlePHP7Error(): void
    {
        $middleware = new ErrorHandlerMiddleware();
        $request = ServerRequestFactory::fromGlobals();
        $error = new Error();

        $result = $middleware->handleException($error, $request);
        $this->assertInstanceOf(Response::class, $result);
    }

    /**
     * Test rendering an error page logs errors
     */
    public function testHandleExceptionLogAndTrace(): void
    {
        $request = ServerRequestFactory::fromGlobals([
            'REQUEST_URI' => '/target/url',
            'HTTP_REFERER' => '/other/path',
        ]);
        $middleware = new ErrorHandlerMiddleware(['log' => true, 'trace' => true]);
        $handler = new TestRequestHandler(function (): void {
            throw new NotFoundException('Kaboom!');
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
     */
    public function testHandleExceptionLogAndTraceWithPrevious(): void
    {
        $request = ServerRequestFactory::fromGlobals([
            'REQUEST_URI' => '/target/url',
            'HTTP_REFERER' => '/other/path',
        ]);
        $middleware = new ErrorHandlerMiddleware(['log' => true, 'trace' => true]);
        $handler = new TestRequestHandler(function ($req): void {
            $previous = new RecordNotFoundException('Previous logged');
            throw new NotFoundException('Kaboom!', null, $previous);
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
     */
    public function testHandleExceptionSkipLog(): void
    {
        $request = ServerRequestFactory::fromGlobals();
        $middleware = new ErrorHandlerMiddleware([
            'log' => true,
            'skipLog' => ['Cake\Http\Exception\NotFoundException'],
        ]);
        $handler = new TestRequestHandler(function (): void {
            throw new NotFoundException('Kaboom!');
        });
        $result = $middleware->process($request, $handler);
        $this->assertSame(404, $result->getStatusCode());
        $this->assertStringContainsString('was not found', '' . $result->getBody());

        $this->assertCount(0, $this->logger->read());
    }

    /**
     * Test rendering an error page logs exception attributes
     */
    public function testHandleExceptionLogAttributes(): void
    {
        $request = ServerRequestFactory::fromGlobals();
        $middleware = new ErrorHandlerMiddleware(['log' => true]);
        $handler = new TestRequestHandler(function (): void {
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

    public function testExceptionBeforeRenderEvent(): void
    {
        $request = ServerRequestFactory::fromGlobals();
        $middleware = new ErrorHandlerMiddleware(new ExceptionTrap([
            'exceptionRenderer' => WebExceptionRenderer::class,
        ]));
        $handler = new TestRequestHandler(function (): void {
            throw new NotFoundException('whoops');
        });

        EventManager::instance()->on(
            'Exception.beforeRender',
            function (EventInterface $event, Throwable $e, ServerRequestInterface $req) {
                return 'Response string from event';
            }
        );

        $result = $middleware->process($request, $handler);
        $this->assertInstanceOf(Response::class, $result);
        $this->assertSame('Response string from event', (string)$result->getBody());
    }

    /**
     * Test handling an error and having rendering fail.
     */
    public function testHandleExceptionRenderingFails(): void
    {
        $this->deprecated(function () {
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
            $handler = new TestRequestHandler(function (): void {
                throw new ServiceUnavailableException('whoops');
            });
            $response = $middleware->process($request, $handler);
            $this->assertSame(500, $response->getStatusCode());
            $this->assertSame('An Internal Server Error Occurred', '' . $response->getBody());
        });
    }

    /**
     * Test that the middleware loads routes if not already loaded, which is the
     * case when an exception occurs before RoutingMiddleware is run.
     *
     * @return void
     */
    public function testRoutesLoading(): void
    {
        $request = ServerRequestFactory::fromGlobals();
        $app = new Application(CONFIG);
        $middleware = new ErrorHandlerMiddleware(
            new ExceptionTrap([
                'exceptionRenderer' => WebExceptionRenderer::class,
            ]),
            $app
        );

        $this->assertSame([], Router::routes());

        $middleware->process($request, $app);
        $this->assertNotEmpty(Router::routes());
    }

    /**
     * Test exception args are not ignored in php7.4 with debug enabled.
     */
    public function testExceptionArgs(): void
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
