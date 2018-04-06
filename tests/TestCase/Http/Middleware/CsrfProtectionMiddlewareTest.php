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
 * @since         3.5.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Http\Middleware;

use Cake\Http\Middleware\CsrfProtectionMiddleware;
use Cake\Http\Response;
use Cake\Http\ServerRequest;
use Cake\I18n\Time;
use Cake\TestSuite\TestCase;

/**
 * Test for CsrfProtection
 */
class CsrfProtectionMiddlewareTest extends TestCase
{

    /**
     * Data provider for HTTP method tests.
     *
     * HEAD and GET do not populate $_POST or request->data.
     *
     * @return array
     */
    public static function safeHttpMethodProvider()
    {
        return [
            ['GET'],
            ['HEAD'],
        ];
    }

    /**
     * Data provider for HTTP methods that can contain request bodies.
     *
     * @return array
     */
    public static function httpMethodProvider()
    {
        return [
            ['OPTIONS'], ['PATCH'], ['PUT'], ['POST'], ['DELETE'], ['PURGE'], ['INVALIDMETHOD']
        ];
    }

    /**
     * Provides the callback for the next middleware
     *
     * @return callable
     */
    protected function _getNextClosure()
    {
        return function ($request, $response) {
            return $response;
        };
    }

    /**
     * Test setting the cookie value
     *
     * @return void
     */
    public function testSettingCookie()
    {
        $request = new ServerRequest([
            'environment' => ['REQUEST_METHOD' => 'GET'],
            'webroot' => '/dir/',
        ]);
        $response = new Response();

        $closure = function ($request, $response) {
            $cookie = $response->cookie('csrfToken');
            $this->assertNotEmpty($cookie, 'Should set a token.');
            $this->assertRegExp('/^[a-f0-9]+$/', $cookie['value'], 'Should look like a hash.');
            $this->assertEquals(0, $cookie['expire'], 'session duration.');
            $this->assertEquals('/dir/', $cookie['path'], 'session path.');
            $this->assertEquals($cookie['value'], $request->params['_csrfToken']);
        };

        $middleware = new CsrfProtectionMiddleware();
        $middleware($request, $response, $closure);
    }

    /**
     * Test that the CSRF tokens are not required for idempotent operations
     *
     * @dataProvider safeHttpMethodProvider
     * @return void
     */
    public function testSafeMethodNoCsrfRequired($method)
    {
        $request = new ServerRequest([
            'environment' => [
                'REQUEST_METHOD' => $method,
                'HTTP_X_CSRF_TOKEN' => 'nope',
            ],
            'cookies' => ['csrfToken' => 'testing123']
        ]);
        $response = new Response();

        // No exception means the test is valid
        $middleware = new CsrfProtectionMiddleware();
        $response = $middleware($request, $response, $this->_getNextClosure());
        $this->assertInstanceOf(Response::class, $response);
    }

    /**
     * Test that the X-CSRF-Token works with the various http methods.
     *
     * @dataProvider httpMethodProvider
     * @return void
     */
    public function testValidTokenInHeader($method)
    {
        $request = new ServerRequest([
            'environment' => [
                'REQUEST_METHOD' => $method,
                'HTTP_X_CSRF_TOKEN' => 'testing123',
            ],
            'post' => ['a' => 'b'],
            'cookies' => ['csrfToken' => 'testing123']
        ]);
        $response = new Response();

        // No exception means the test is valid
        $middleware = new CsrfProtectionMiddleware();
        $response = $middleware($request, $response, $this->_getNextClosure());
        $this->assertInstanceOf(Response::class, $response);
    }

    /**
     * Test that the X-CSRF-Token works with the various http methods.
     *
     * @dataProvider httpMethodProvider
     * @return void
     */
    public function testInvalidTokenInHeader($method)
    {
        $this->expectException(\Cake\Network\Exception\InvalidCsrfTokenException::class);
        $request = new ServerRequest([
            'environment' => [
                'REQUEST_METHOD' => $method,
                'HTTP_X_CSRF_TOKEN' => 'nope',
            ],
            'post' => ['a' => 'b'],
            'cookies' => ['csrfToken' => 'testing123']
        ]);
        $response = new Response();

        $middleware = new CsrfProtectionMiddleware();
        $middleware($request, $response, $this->_getNextClosure());
    }

    /**
     * Test that request data works with the various http methods.
     *
     * @dataProvider httpMethodProvider
     * @return void
     */
    public function testValidTokenRequestData($method)
    {
        $request = new ServerRequest([
            'environment' => [
                'REQUEST_METHOD' => $method,
            ],
            'post' => ['_csrfToken' => 'testing123'],
            'cookies' => ['csrfToken' => 'testing123']
        ]);
        $response = new Response();

        $closure = function ($request, $response) {
            $this->assertNull($request->getData('_csrfToken'));
        };

        // No exception means everything is OK
        $middleware = new CsrfProtectionMiddleware();
        $middleware($request, $response, $closure);
    }

    /**
     * Test that request data works with the various http methods.
     *
     * @dataProvider httpMethodProvider
     * @return void
     */
    public function testInvalidTokenRequestData($method)
    {
        $this->expectException(\Cake\Network\Exception\InvalidCsrfTokenException::class);
        $request = new ServerRequest([
            'environment' => [
                'REQUEST_METHOD' => $method,
            ],
            'post' => ['_csrfToken' => 'nope'],
            'cookies' => ['csrfToken' => 'testing123']
        ]);
        $response = new Response();

        $middleware = new CsrfProtectionMiddleware();
        $middleware($request, $response, $this->_getNextClosure());
    }

    /**
     * Test that missing post field fails
     *
     * @return void
     */
    public function testInvalidTokenRequestDataMissing()
    {
        $this->expectException(\Cake\Network\Exception\InvalidCsrfTokenException::class);
        $request = new ServerRequest([
            'environment' => [
                'REQUEST_METHOD' => 'POST',
            ],
            'post' => [],
            'cookies' => ['csrfToken' => 'testing123']
        ]);
        $response = new Response();

        $middleware = new CsrfProtectionMiddleware();
        $middleware($request, $response, $this->_getNextClosure());
    }

    /**
     * Test that missing header and cookie fails
     *
     * @dataProvider httpMethodProvider
     * @return void
     */
    public function testInvalidTokenMissingCookie($method)
    {
        $this->expectException(\Cake\Network\Exception\InvalidCsrfTokenException::class);
        $request = new ServerRequest([
            'environment' => [
                'REQUEST_METHOD' => $method
            ],
            'post' => ['_csrfToken' => 'could-be-valid'],
            'cookies' => []
        ]);
        $response = new Response();

        $middleware = new CsrfProtectionMiddleware();
        $middleware($request, $response, $this->_getNextClosure());
    }

    /**
     * Test that the configuration options work.
     *
     * @return void
     */
    public function testConfigurationCookieCreate()
    {
        $request = new ServerRequest([
            'environment' => ['REQUEST_METHOD' => 'GET'],
            'webroot' => '/dir/'
        ]);
        $response = new Response();

        $closure = function ($request, $response) {
            $this->assertEmpty($response->cookie('csrfToken'));
            $cookie = $response->cookie('token');
            $this->assertNotEmpty($cookie, 'Should set a token.');
            $this->assertRegExp('/^[a-f0-9]+$/', $cookie['value'], 'Should look like a hash.');
            $this->assertWithinRange((new Time('+1 hour'))->format('U'), $cookie['expire'], 1, 'session duration.');
            $this->assertEquals('/dir/', $cookie['path'], 'session path.');
            $this->assertTrue($cookie['secure'], 'cookie security flag missing');
            $this->assertTrue($cookie['httpOnly'], 'cookie httpOnly flag missing');
        };

        $middleware = new CsrfProtectionMiddleware([
            'cookieName' => 'token',
            'expiry' => '+1 hour',
            'secure' => true,
            'httpOnly' => true
        ]);
        $middleware($request, $response, $closure);
    }

    /**
     * Test that the configuration options work.
     *
     * There should be no exception thrown.
     *
     * @return void
     */
    public function testConfigurationValidate()
    {
        $request = new ServerRequest([
            'environment' => ['REQUEST_METHOD' => 'POST'],
            'cookies' => ['csrfToken' => 'nope', 'token' => 'yes'],
            'post' => ['_csrfToken' => 'no match', 'token' => 'yes'],
        ]);
        $response = new Response();

        $middleware = new CsrfProtectionMiddleware([
            'cookieName' => 'token',
            'field' => 'token',
            'expiry' => 90,
        ]);
        $response = $middleware($request, $response, $this->_getNextClosure());
        $this->assertInstanceOf(Response::class, $response);
    }
}
