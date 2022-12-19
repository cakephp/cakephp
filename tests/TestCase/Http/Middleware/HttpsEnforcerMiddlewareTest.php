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
 * @since         4.0.0
 * @license       https://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Http\Middleware;

use Cake\Core\Configure;
use Cake\Http\Exception\BadRequestException;
use Cake\Http\Middleware\HttpsEnforcerMiddleware;
use Cake\Http\Response;
use Cake\Http\ServerRequest;
use Cake\Http\ServerRequestFactory;
use Cake\TestSuite\TestCase;
use Laminas\Diactoros\Response\RedirectResponse;
use Laminas\Diactoros\Uri;
use TestApp\Http\TestRequestHandler;
use UnexpectedValueException;

/**
 * Test for HttpsEnforcerMiddleware
 */
class HttpsEnforcerMiddlewareTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        Configure::write('debug', false);
    }

    public function tearDown(): void
    {
        parent::tearDown();

        Configure::write('debug', true);
    }

    public function testForRequestWithHttps(): void
    {
        $uri = new Uri('https://localhost/foo');
        $request = new ServerRequest();
        $request = $request->withUri($uri);

        $handler = new TestRequestHandler(function ($req) {
            return new Response(['body' => 'success']);
        });

        $middleware = new HttpsEnforcerMiddleware();

        $result = $middleware->process($request, $handler);
        $this->assertInstanceOf(Response::class, $result);
        $this->assertSame('success', (string)$result->getBody());
    }

    public function testHstsResponse(): void
    {
        $uri = new Uri('https://localhost/foo');
        $request = new ServerRequest();
        $request = $request->withUri($uri);

        $handler = new TestRequestHandler(function ($req) {
            return new Response(['body' => 'success']);
        });

        $middleware = new HttpsEnforcerMiddleware(['hsts' => ['maxAge' => 63072000]]);

        $result = $middleware->process($request, $handler);
        $this->assertInstanceOf(Response::class, $result);
        $this->assertSame('max-age=63072000', $result->getHeaderLine('strict-transport-security'));
    }

    public function testHstsResponseWithDirectives(): void
    {
        $uri = new Uri('https://localhost/foo');
        $request = new ServerRequest();
        $request = $request->withUri($uri);

        $handler = new TestRequestHandler(function ($req) {
            return new Response(['body' => 'success']);
        });

        $middleware = new HttpsEnforcerMiddleware(['hsts' => ['maxAge' => 63072000, 'includeSubDomains' => true, 'preload' => true]]);

        $result = $middleware->process($request, $handler);
        $this->assertInstanceOf(Response::class, $result);
        $this->assertSame('max-age=63072000; includeSubDomains; preload', $result->getHeaderLine('strict-transport-security'));
    }

    public function testHstsResponseInvalidConfig(): void
    {
        $uri = new Uri('https://localhost/foo');
        $request = new ServerRequest();
        $request = $request->withUri($uri);

        $handler = new TestRequestHandler(function ($req) {
            return new Response(['body' => 'success']);
        });

        $middleware = new HttpsEnforcerMiddleware(['hsts' => true]);

        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('The `hsts` config must be an array.');
        $middleware->process($request, $handler);
    }

    public function testRedirect(): void
    {
        $uri = new Uri('http://localhost/foo');
        $request = new ServerRequest();
        $request = $request->withUri($uri)->withMethod('GET');

        $handler = new TestRequestHandler(function ($req) {
            return new Response();
        });

        $middleware = new HttpsEnforcerMiddleware();

        $result = $middleware->process($request, $handler);
        $this->assertInstanceOf(RedirectResponse::class, $result);
        $this->assertSame(301, $result->getStatusCode());
        $this->assertEquals(['location' => ['https://localhost/foo']], $result->getHeaders());

        $middleware = new HttpsEnforcerMiddleware([
            'statusCode' => 302,
            'headers' => ['X-Foo' => 'bar'],
        ]);
        $result = $middleware->process($request, $handler);
        $this->assertInstanceOf(RedirectResponse::class, $result);
        $this->assertSame(302, $result->getStatusCode());
        $this->assertEquals(
            [
                'location' => ['https://localhost/foo'],
                'X-Foo' => ['bar'],
            ],
            $result->getHeaders()
        );
    }

    public function testRedirectBasePath(): void
    {
        $request = new ServerRequest([
            'url' => '/articles',
            'base' => '/base',
            'method' => 'GET',
        ]);

        $handler = new TestRequestHandler(function () {
            return new Response();
        });

        $middleware = new HttpsEnforcerMiddleware();

        $result = $middleware->process($request, $handler);
        $this->assertInstanceOf(RedirectResponse::class, $result);
        $this->assertSame(301, $result->getStatusCode());
        $this->assertEquals(['location' => ['https://localhost/base/articles']], $result->getHeaders());
    }

    /**
     * Test that exception is thrown when redirect is disabled.
     */
    public function testNoRedirectException(): void
    {
        $this->expectException(BadRequestException::class);

        $uri = new Uri('http://localhost/foo');
        $request = new ServerRequest();
        $request = $request->withUri($uri);

        $handler = new TestRequestHandler(function ($req) {
            return new Response();
        });

        $middleware = new HttpsEnforcerMiddleware(['redirect' => false]);
        $middleware->process($request, $handler);
    }

    /**
     * Test that exception is thrown for non GET request even if redirect is enabled.
     */
    public function testExceptionForNonGetRequest(): void
    {
        $this->expectException(BadRequestException::class);

        $uri = new Uri('http://localhost/foo');
        $request = new ServerRequest();
        $request = $request->withUri($uri)->withMethod('POST');

        $handler = new TestRequestHandler(function ($req) {
            return new Response();
        });

        $middleware = new HttpsEnforcerMiddleware(['redirect' => true]);
        $middleware->process($request, $handler);
    }

    /**
     * Test that HTTPS check is skipped when debug is on.
     */
    public function testNoCheckWithDebugOn(): void
    {
        Configure::write('debug', true);

        $uri = new Uri('http://localhost/foo');
        $request = new ServerRequest();
        $request = $request->withUri($uri);

        $handler = new TestRequestHandler(function ($req) {
            return new Response(['body' => 'skipped']);
        });

        $middleware = new HttpsEnforcerMiddleware();

        $result = $middleware->process($request, $handler);
        $this->assertInstanceOf(Response::class, $result);
        $this->assertSame('skipped', (string)$result->getBody());
    }

    /**
     * Test that setting trustedProxies works correctly
     */
    public function testTrustedProxies(): void
    {
        $server = [
            'DOCUMENT_ROOT' => '/cake/repo/webroot',
            'PHP_SELF' => '/index.php',
            'REQUEST_URI' => '/posts/add',
            'HTTP_X_FORWARDED_PROTO' => 'https',
        ];
        $request = ServerRequestFactory::fromGlobals($server);

        $handler = new TestRequestHandler(function () {
            return new Response();
        });

        $middleware = new HttpsEnforcerMiddleware();
        $result = $middleware->process($request, $handler);
        $this->assertInstanceOf(RedirectResponse::class, $result);

        $middleware = new HttpsEnforcerMiddleware(['trustedProxies' => []]);
        $result = $middleware->process($request, $handler);
        $this->assertInstanceOf(Response::class, $result);
    }
}
