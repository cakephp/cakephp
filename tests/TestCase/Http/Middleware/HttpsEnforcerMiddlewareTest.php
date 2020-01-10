<?php
declare(strict_types=1);

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
 * @since         4.0.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Http\Middleware;

use Cake\Core\Configure;
use Cake\Http\Exception\BadRequestException;
use Cake\Http\Middleware\HttpsEnforcerMiddleware;
use Cake\Http\Response;
use Cake\Http\ServerRequest;
use Cake\TestSuite\TestCase;
use Laminas\Diactoros\Response\RedirectResponse;
use Laminas\Diactoros\Uri;
use TestApp\Http\TestRequestHandler;

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

    public function testForRequestWithHttps()
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

    public function testRedirect()
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

    /**
     * Test that exception is thrown when redirect is disabled.
     *
     * @return void
     */
    public function testNoRedirectException()
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
     *
     * @return void
     */
    public function testExceptionForNonGetRequest()
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
     *
     * @return void
     */
    public function testNoCheckWithDebugOn()
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
}
