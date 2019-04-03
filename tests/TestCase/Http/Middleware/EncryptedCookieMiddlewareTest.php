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
namespace Cake\Test\TestCase\Http\Middleware;

use Cake\Http\Cookie\CookieCollection;
use Cake\Http\Middleware\EncryptedCookieMiddleware;
use Cake\Http\Response;
use Cake\Http\ServerRequest;
use Cake\TestSuite\TestCase;
use Cake\Utility\CookieCryptTrait;

/**
 * Test for EncryptedCookieMiddleware
 */
class EncryptedCookieMiddlewareTest extends TestCase
{
    use CookieCryptTrait;

    protected $middleware;

    protected function _getCookieEncryptionKey()
    {
        return 'super secret key that no one can guess';
    }

    /**
     * Setup
     */
    public function setUp()
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
            'secret' => $this->_encrypt('decoded', 'aes')
        ]);
        $this->assertNotEquals('decoded', $request->getCookie('decoded'));

        $response = new Response();
        $next = function ($req, $res) {
            $this->assertSame('decoded', $req->getCookie('secret'));
            $this->assertSame('always plain', $req->getCookie('plain'));

            return $res->withHeader('called', 'yes');
        };
        $middleware = $this->middleware;
        $response = $middleware($request, $response, $next);
        $this->assertSame('yes', $response->getHeaderLine('called'), 'Inner middleware not invoked');
    }

    /**
     * Test encoding cookies in the set-cookie header.
     *
     * @return void
     */
    public function testEncodeResponseSetCookieHeader()
    {
        $request = new ServerRequest(['url' => '/cookies/nom']);
        $response = new Response();
        $next = function ($req, $res) {
            return $res->withAddedHeader('Set-Cookie', 'secret=be%20quiet')
                ->withAddedHeader('Set-Cookie', 'plain=in%20clear')
                ->withAddedHeader('Set-Cookie', 'ninja=shuriken');
        };
        $middleware = $this->middleware;
        $response = $middleware($request, $response, $next);
        $this->assertNotContains('ninja=shuriken', $response->getHeaderLine('Set-Cookie'));
        $this->assertContains('plain=in%20clear', $response->getHeaderLine('Set-Cookie'));

        $cookies = CookieCollection::createFromHeader($response->getHeader('Set-Cookie'));
        $this->assertTrue($cookies->has('ninja'));
        $this->assertEquals(
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
        $response = new Response();
        $next = function ($req, $res) {
            return $res->withCookie('secret', 'be quiet')
                ->withCookie('plain', 'in clear')
                ->withCookie('ninja', 'shuriken');
        };
        $middleware = $this->middleware;
        $response = $middleware($request, $response, $next);
        $this->assertNotSame('shuriken', $response->getCookie('ninja'));
        $this->assertEquals(
            'shuriken',
            $this->_decrypt($response->getCookie('ninja')['value'], 'aes')
        );
    }
}
