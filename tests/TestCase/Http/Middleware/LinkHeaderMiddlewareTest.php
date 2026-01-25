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
 * @since         5.4.0
 * @license       https://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Http\Middleware;

use Cake\Http\Link\Link;
use Cake\Http\Middleware\LinkHeaderMiddleware;
use Cake\Http\Response;
use Cake\Http\ServerRequestFactory;
use Cake\TestSuite\TestCase;
use Laminas\Diactoros\Response as LaminasResponse;
use TestApp\Http\TestRequestHandler;

/**
 * Test for LinkHeaderMiddleware
 */
class LinkHeaderMiddlewareTest extends TestCase
{
    /**
     * Test middleware adds Link headers for PSR-13 links.
     */
    public function testProcessAddsLinkHeaders(): void
    {
        $request = ServerRequestFactory::fromGlobals([
            'REQUEST_URI' => '/',
        ]);

        $handler = new TestRequestHandler(function ($req) {
            $response = new Response();

            return $response
                ->withLink(new Link('/api/users', 'self'))
                ->withLink(new Link('/api/users?page=2', 'next'));
        });

        $middleware = new LinkHeaderMiddleware();
        $result = $middleware->process($request, $handler);

        $this->assertTrue($result->hasHeader('Link'));
        $linkHeaders = $result->getHeader('Link');
        $this->assertCount(2, $linkHeaders);
        $this->assertSame('</api/users>; rel="self"', $linkHeaders[0]);
        $this->assertSame('</api/users?page=2>; rel="next"', $linkHeaders[1]);
    }

    /**
     * Test middleware handles links with attributes.
     */
    public function testProcessWithAttributes(): void
    {
        $request = ServerRequestFactory::fromGlobals([
            'REQUEST_URI' => '/',
        ]);

        $handler = new TestRequestHandler(function ($req) {
            $response = new Response();
            $link = (new Link('/css/app.css'))
                ->withRel('preload')
                ->withAttribute('as', 'style')
                ->withAttribute('crossorigin', 'anonymous');

            return $response->withLink($link);
        });

        $middleware = new LinkHeaderMiddleware();
        $result = $middleware->process($request, $handler);

        $linkHeaders = $result->getHeader('Link');
        $this->assertCount(1, $linkHeaders);
        $this->assertSame('</css/app.css>; rel="preload"; as="style"; crossorigin="anonymous"', $linkHeaders[0]);
    }

    /**
     * Test middleware handles boolean attributes.
     */
    public function testProcessWithBooleanAttributes(): void
    {
        $request = ServerRequestFactory::fromGlobals([
            'REQUEST_URI' => '/',
        ]);

        $handler = new TestRequestHandler(function ($req) {
            $response = new Response();
            $link = (new Link('/script.js'))
                ->withRel('preload')
                ->withAttribute('as', 'script')
                ->withAttribute('nopush', true)
                ->withAttribute('disabled', false);

            return $response->withLink($link);
        });

        $middleware = new LinkHeaderMiddleware();
        $result = $middleware->process($request, $handler);

        $linkHeaders = $result->getHeader('Link');
        $this->assertCount(1, $linkHeaders);
        // Boolean true becomes the attribute name, false is omitted
        $this->assertSame('</script.js>; rel="preload"; as="script"; nopush', $linkHeaders[0]);
    }

    /**
     * Test middleware handles array attributes.
     */
    public function testProcessWithArrayAttributes(): void
    {
        $request = ServerRequestFactory::fromGlobals([
            'REQUEST_URI' => '/',
        ]);

        $handler = new TestRequestHandler(function ($req) {
            $response = new Response();
            $link = (new Link('/api/resource'))
                ->withRel('self')
                ->withAttribute('hreflang', ['en', 'de']);

            return $response->withLink($link);
        });

        $middleware = new LinkHeaderMiddleware();
        $result = $middleware->process($request, $handler);

        $linkHeaders = $result->getHeader('Link');
        $this->assertCount(1, $linkHeaders);
        $this->assertSame('</api/resource>; rel="self"; hreflang="en"; hreflang="de"', $linkHeaders[0]);
    }

    /**
     * Test middleware handles multiple rels.
     */
    public function testProcessWithMultipleRels(): void
    {
        $request = ServerRequestFactory::fromGlobals([
            'REQUEST_URI' => '/',
        ]);

        $handler = new TestRequestHandler(function ($req) {
            $response = new Response();
            $link = (new Link('/api/users'))
                ->withRel('self')
                ->withRel('collection');

            return $response->withLink($link);
        });

        $middleware = new LinkHeaderMiddleware();
        $result = $middleware->process($request, $handler);

        $linkHeaders = $result->getHeader('Link');
        $this->assertCount(1, $linkHeaders);
        $this->assertSame('</api/users>; rel="self collection"', $linkHeaders[0]);
    }

    /**
     * Test middleware escapes special characters in values.
     */
    public function testProcessEscapesValues(): void
    {
        $request = ServerRequestFactory::fromGlobals([
            'REQUEST_URI' => '/',
        ]);

        $handler = new TestRequestHandler(function ($req) {
            $response = new Response();
            $link = (new Link('/api/users'))
                ->withRel('self')
                ->withAttribute('title', 'A "quoted" value');

            return $response->withLink($link);
        });

        $middleware = new LinkHeaderMiddleware();
        $result = $middleware->process($request, $handler);

        $linkHeaders = $result->getHeader('Link');
        $this->assertCount(1, $linkHeaders);
        $this->assertSame('</api/users>; rel="self"; title="A \"quoted\" value"', $linkHeaders[0]);
    }

    /**
     * Test middleware does nothing for non-CakePHP responses.
     */
    public function testProcessNonCakeResponse(): void
    {
        $request = ServerRequestFactory::fromGlobals([
            'REQUEST_URI' => '/',
        ]);

        $handler = new TestRequestHandler(function ($req) {
            return new LaminasResponse();
        });

        $middleware = new LinkHeaderMiddleware();
        $result = $middleware->process($request, $handler);

        $this->assertInstanceOf(LaminasResponse::class, $result);
        $this->assertFalse($result->hasHeader('Link'));
    }

    /**
     * Test middleware does nothing when no links present.
     */
    public function testProcessNoLinks(): void
    {
        $request = ServerRequestFactory::fromGlobals([
            'REQUEST_URI' => '/',
        ]);

        $handler = new TestRequestHandler(function ($req) {
            return new Response();
        });

        $middleware = new LinkHeaderMiddleware();
        $result = $middleware->process($request, $handler);

        $this->assertFalse($result->hasHeader('Link'));
    }

    /**
     * Test middleware handles link without rels.
     */
    public function testProcessLinkWithoutRel(): void
    {
        $request = ServerRequestFactory::fromGlobals([
            'REQUEST_URI' => '/',
        ]);

        $handler = new TestRequestHandler(function ($req) {
            $response = new Response();

            return $response->withLink(new Link('/api/users'));
        });

        $middleware = new LinkHeaderMiddleware();
        $result = $middleware->process($request, $handler);

        $linkHeaders = $result->getHeader('Link');
        $this->assertCount(1, $linkHeaders);
        $this->assertSame('</api/users>', $linkHeaders[0]);
    }
}
