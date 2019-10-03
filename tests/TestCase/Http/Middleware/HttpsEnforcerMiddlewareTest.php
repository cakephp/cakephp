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

use Cake\Controller\Exception\SecurityException;
use Cake\Http\Middleware\HttpsEnforcerMiddleware;
use Cake\Http\Response;
use Cake\Http\ServerRequest;
use Cake\TestSuite\TestCase;
use TestApp\Http\TestRequestHandler;
use Zend\Diactoros\Response\RedirectResponse;
use Zend\Diactoros\Uri;

/**
 * Test for HttpsEnforcerMiddleware
 */
class HttpsEnforcerMiddlewareTest extends TestCase
{
    public function testForRequestWithHttp()
    {
        $uri = new Uri('https://localhost/foo');
        $request = new ServerRequest();
        $request = $request->withUri($uri);

        $handler = new TestRequestHandler(function ($req) {
            return new Response();
        });

        $middleware = new HttpsEnforcerMiddleware();

        $result = $middleware->process($request, $handler);
        $this->assertInstanceOf(Response::class, $result);
    }

    public function testRedirect()
    {
        $uri = new Uri('http://localhost/foo');
        $request = new ServerRequest();
        $request = $request->withUri($uri);

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

    public function testSecurityException()
    {
        $this->expectException(SecurityException::class);

        $uri = new Uri('http://localhost/foo');
        $request = new ServerRequest();
        $request = $request->withUri($uri);

        $handler = new TestRequestHandler(function ($req) {
            return new Response();
        });

        $middleware = new HttpsEnforcerMiddleware(['redirect' => false]);
        $middleware->process($request, $handler);
    }
}
