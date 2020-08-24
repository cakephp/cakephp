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
 * @since         3.3.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Http\Middleware;

use Cake\Http\Cookie\Cookie;
use Cake\Http\Cookie\CookieCollection;
use Cake\Http\Middleware\EncryptedCookieMiddleware;
use Cake\Http\Response;
use Cake\Http\ServerRequest;
use Cake\TestSuite\TestCase;
use Cake\Utility\CookieCryptTrait;
use TestApp\Http\TestRequestHandler;

/**
 * Test for EncryptedCookieMiddleware
 */
class EncryptedCookieMiddlewareTest extends TestCase
{
    use CookieCryptTrait;

    protected $middleware;

    protected function _getCookieEncryptionKey(): string
    {
        return 'super secret key that no one can guess';
    }

    /**
     * Setup
     */
    public function setUp(): void
    {
        $this->middleware = new EncryptedCookieMiddleware(
            ['secret', 'ninja'],
            $this->_getCookieEncryptionKey(),
            'aes'
        );
    }

    /**
     * Test decoding request cookies
     *
     * @return void
     */
    public function testDecodeRequestCookies()
    {
        $request = new ServerRequest(['url' => '/cookies/nom']);
        $request = $request->withCookieParams([
            'plain' => 'always plain',
            'secret' => $this->_encrypt('decoded', 'aes'),
        ]);
        $this->assertNotEquals('decoded', $request->getCookie('decoded'));

        $handler = new TestRequestHandler(function ($req) {
            $this->assertSame('decoded', $req->getCookie('secret'));
            $this->assertSame('always plain', $req->getCookie('plain'));

            return (new Response())->withHeader('called', 'yes');
        });
        $response = $this->middleware->process($request, $handler);
        $this->assertSame('yes', $response->getHeaderLine('called'), 'Inner middleware not invoked');
    }

    /**
     * Test decoding malformed cookies
     *
     * @dataProvider malformedCookies
     * @param string $cookie
     * @return void
     */
    public function testDecodeMalformedCookies($cookie)
    {
        $request = new ServerRequest(['url' => '/cookies/nom']);
        $request = $request->withCookieParams(['secret' => $cookie]);

        $handler = new TestRequestHandler(function ($req) {
            $this->assertSame('', $req->getCookie('secret'));

            return new Response();
        });
        $middleware = new EncryptedCookieMiddleware(
            ['secret'],
            $this->_getCookieEncryptionKey(),
            'aes'
        );
        $middleware->process($request, $handler);
    }

    /**
     * Data provider for malformed cookies.
     *
     * @return array
     */
    public function malformedCookies()
    {
        $encrypted = $this->_encrypt('secret data', 'aes');

        return [
            'empty' => [''],
            'wrong prefix' => [substr_replace($encrypted, 'foo', 0, 3)],
            'altered' => [str_replace('M', 'A', $encrypted)],
            'invalid chars' => [str_replace('M', 'M#', $encrypted)],
        ];
    }

    /**
     * Test encoding cookies in the set-cookie header.
     *
     * @return void
     */
    public function testEncodeResponseSetCookieHeader()
    {
        $request = new ServerRequest(['url' => '/cookies/nom']);
        $handler = new TestRequestHandler(function ($req) {
            return (new Response())->withAddedHeader('Set-Cookie', 'secret=be%20quiet')
                ->withAddedHeader('Set-Cookie', 'plain=in%20clear')
                ->withAddedHeader('Set-Cookie', 'ninja=shuriken');
        });
        $response = $this->middleware->process($request, $handler);
        $this->assertStringNotContainsString('ninja=shuriken', $response->getHeaderLine('Set-Cookie'));
        $this->assertStringContainsString('plain=in%20clear', $response->getHeaderLine('Set-Cookie'));

        $cookies = CookieCollection::createFromHeader($response->getHeader('Set-Cookie'));
        $this->assertTrue($cookies->has('ninja'));
        $this->assertSame(
            'shuriken',
            $this->_decrypt($cookies->get('ninja')->getValue(), 'aes')
        );
    }

    /**
     * Test encoding cookies in the cookie collection.
     *
     * @return void
     */
    public function testEncodeResponseCookieData()
    {
        $request = new ServerRequest(['url' => '/cookies/nom']);
        $handler = new TestRequestHandler(function ($req) {
            return (new Response())->withCookie(new Cookie('secret', 'be quiet'))
                ->withCookie(new Cookie('plain', 'in clear'))
                ->withCookie(new Cookie('ninja', 'shuriken'));
        });
        $response = $this->middleware->process($request, $handler);
        $this->assertNotSame('shuriken', $response->getCookie('ninja'));
        $this->assertSame(
            'shuriken',
            $this->_decrypt($response->getCookie('ninja')['value'], 'aes')
        );
    }
}
