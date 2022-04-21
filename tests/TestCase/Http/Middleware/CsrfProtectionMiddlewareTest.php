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
 * @since         3.5.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Http\Middleware;

use Cake\Http\Cookie\Cookie;
use Cake\Http\Cookie\CookieInterface;
use Cake\Http\Exception\InvalidCsrfTokenException;
use Cake\Http\Middleware\CsrfProtectionMiddleware;
use Cake\Http\Response;
use Cake\Http\ServerRequest;
use Cake\TestSuite\TestCase;
use Cake\Utility\Security;
use Laminas\Diactoros\Response as DiactorosResponse;
use Laminas\Diactoros\Response\RedirectResponse;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RuntimeException;
use TestApp\Http\TestRequestHandler;

/**
 * Test for CsrfProtection
 */
class CsrfProtectionMiddlewareTest extends TestCase
{
    protected function createOldToken(): string
    {
        // Create an old style token. These tokens are hexadecimal with an hmac.
        $random = Security::randomString(CsrfProtectionMiddleware::TOKEN_VALUE_LENGTH);

        return $random . hash_hmac('sha1', $random, Security::getSalt());
    }

    /**
     * Data provider for HTTP method tests.
     *
     * HEAD and GET do not populate $_POST or request->data.
     *
     * @return array
     */
    public static function safeHttpMethodProvider(): array
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
    public static function httpMethodProvider(): array
    {
        return [
            ['OPTIONS'], ['PATCH'], ['PUT'], ['POST'], ['DELETE'], ['PURGE'], ['INVALIDMETHOD'],
        ];
    }

    /**
     * Provides the request handler
     */
    protected function _getRequestHandler(): RequestHandlerInterface
    {
        return new TestRequestHandler(function () {
            return new Response();
        });
    }

    /**
     * Test setting the cookie value
     */
    public function testSettingCookie(): void
    {
        $request = new ServerRequest([
            'environment' => ['REQUEST_METHOD' => 'GET'],
            'webroot' => '/dir/',
        ]);

        /** @var \Cake\Http\ServerRequest $updatedRequest */
        $updatedRequest = null;
        $handler = new TestRequestHandler(function ($request) use (&$updatedRequest) {
            $updatedRequest = $request;

            return new Response();
        });

        $middleware = new CsrfProtectionMiddleware();
        $response = $middleware->process($request, $handler);

        $cookie = $response->getCookie('csrfToken');
        $this->assertNotEmpty($cookie, 'Should set a token.');
        $this->assertMatchesRegularExpression('/^[a-z0-9\/+]+={0,2}$/i', $cookie['value'], 'Should look like base64.');
        $this->assertSame(0, $cookie['expires'], 'session duration.');
        $this->assertSame('/dir/', $cookie['path'], 'session path.');
        $requestAttr = $updatedRequest->getAttribute('csrfToken');

        $this->assertNotEquals($cookie['value'], $requestAttr);
        $this->assertEquals(strlen($cookie['value']) * 2, strlen($requestAttr));
        $this->assertMatchesRegularExpression('/^[A-Z0-9\/+]+=*$/i', $requestAttr);
    }

    /**
     * Test setting request attribute based on old cookie value.
     */
    public function testRequestAttributeCompatWithOldToken(): void
    {
        $middleware = new CsrfProtectionMiddleware();
        $oldToken = $this->createOldToken();

        $request = new ServerRequest([
            'environment' => [
                'REQUEST_METHOD' => 'GET',
            ],
            'cookies' => ['csrfToken' => $oldToken],
        ]);

        /** @var \Cake\Http\ServerRequest $updatedRequest */
        $updatedRequest = null;
        $handler = new TestRequestHandler(function ($request) use (&$updatedRequest) {
            $updatedRequest = $request;

            return new Response();
        });
        $response = $middleware->process($request, $handler);
        $this->assertInstanceOf(Response::class, $response);

        $requestAttr = $updatedRequest->getAttribute('csrfToken');
        $this->assertSame($requestAttr, $oldToken, 'Request attribute should match the token.');
    }

    /**
     * Test that the CSRF tokens are not required for idempotent operations
     *
     * @dataProvider safeHttpMethodProvider
     */
    public function testSafeMethodNoCsrfRequired(string $method): void
    {
        $request = new ServerRequest([
            'environment' => [
                'REQUEST_METHOD' => $method,
                'HTTP_X_CSRF_TOKEN' => 'nope',
            ],
            'cookies' => ['csrfToken' => 'testing123'],
        ]);

        // No exception means the test is valid
        $middleware = new CsrfProtectionMiddleware();
        $response = $middleware->process($request, $this->_getRequestHandler());
        $this->assertInstanceOf(Response::class, $response);
    }

    /**
     * Test that the CSRF tokens are regenerated when token is not valid
     *
     * @return void
     */
    public function testRegenerateTokenOnGetWithInvalidData()
    {
        $request = new ServerRequest([
            'environment' => [
                'REQUEST_METHOD' => 'GET',
            ],
            'cookies' => ['csrfToken' => "\x20\x26"],
        ]);

        $middleware = new CsrfProtectionMiddleware();
        /** @var \Cake\Http\Response $response */
        $response = $middleware->process($request, $this->_getRequestHandler());
        $this->assertInstanceOf(Response::class, $response);
        $this->assertGreaterThan(32, strlen($response->getCookie('csrfToken')['value']));
    }

    /**
     * Test that the CSRF tokens are set for redirect responses
     */
    public function testRedirectResponseCookies(): void
    {
        $request = new ServerRequest([
            'environment' => ['REQUEST_METHOD' => 'GET'],
        ]);
        $handler = new TestRequestHandler(function () {
            return new RedirectResponse('/');
        });

        $middleware = new CsrfProtectionMiddleware();
        $response = $middleware->process($request, $handler);
        $this->assertStringContainsString('csrfToken=', $response->getHeaderLine('Set-Cookie'));
    }

    /**
     * Test that double applying CSRF causes a failure.
     */
    public function testDoubleApplicationFailure(): void
    {
        $request = new ServerRequest([
            'environment' => ['REQUEST_METHOD' => 'GET'],
        ]);
        $request = $request->withAttribute('csrfToken', 'not-good');
        $handler = new TestRequestHandler(function () {
            return new RedirectResponse('/');
        });

        $middleware = new CsrfProtectionMiddleware();
        $this->expectException(RuntimeException::class);
        $middleware->process($request, $handler);
    }

    /**
     * Test that the CSRF tokens are set for diactoros responses
     */
    public function testDiactorosResponseCookies(): void
    {
        $request = new ServerRequest([
            'environment' => ['REQUEST_METHOD' => 'GET'],
        ]);
        $handler = new TestRequestHandler(function () {
            return new DiactorosResponse();
        });

        $middleware = new CsrfProtectionMiddleware();
        $response = $middleware->process($request, $handler);
        $this->assertStringContainsString('csrfToken=', $response->getHeaderLine('Set-Cookie'));
    }

    /**
     * Test that the X-CSRF-Token works with the various http methods.
     *
     * @dataProvider httpMethodProvider
     */
    public function testValidTokenInHeaderCompat(string $method): void
    {
        $middleware = new CsrfProtectionMiddleware();
        $token = $middleware->createToken();
        $request = new ServerRequest([
            'environment' => [
                'REQUEST_METHOD' => $method,
                'HTTP_X_CSRF_TOKEN' => $token,
            ],
            'post' => ['a' => 'b'],
            'cookies' => ['csrfToken' => $token],
        ]);
        $response = new Response();

        // No exception means the test is valid
        $response = $middleware->process($request, $this->_getRequestHandler());
        $this->assertInstanceOf(Response::class, $response);
    }

    /**
     * Test that the X-CSRF-Token works with the various http methods.
     *
     * @dataProvider httpMethodProvider
     */
    public function testValidTokenInHeader(string $method): void
    {
        $middleware = new CsrfProtectionMiddleware();
        $token = $middleware->createToken();
        $salted = $middleware->saltToken($token);
        $request = new ServerRequest([
            'environment' => [
                'REQUEST_METHOD' => $method,
                'HTTP_X_CSRF_TOKEN' => $salted,
            ],
            'post' => ['a' => 'b'],
            'cookies' => ['csrfToken' => $token],
        ]);
        $response = new Response();

        // No exception means the test is valid
        $response = $middleware->process($request, $this->_getRequestHandler());
        $this->assertInstanceOf(Response::class, $response);
    }

    /**
     * Test that the X-CSRF-Token works with the various http methods.
     *
     * @dataProvider httpMethodProvider
     */
    public function testInvalidTokenInHeader(string $method): void
    {
        $request = new ServerRequest([
            'environment' => [
                'REQUEST_METHOD' => $method,
                'HTTP_X_CSRF_TOKEN' => 'nope',
            ],
            'post' => ['a' => 'b'],
            'cookies' => ['csrfToken' => 'testing123'],
        ]);

        $middleware = new CsrfProtectionMiddleware();

        try {
            $middleware->process($request, $this->_getRequestHandler());

            $this->fail();
        } catch (InvalidCsrfTokenException $exception) {
            $responseHeaders = $exception->getHeaders();

            $this->assertArrayHasKey('Set-Cookie', $responseHeaders);

            $cookie = Cookie::createFromHeaderString($responseHeaders['Set-Cookie']);
            $this->assertSame('csrfToken', $cookie->getName(), 'Should automatically delete cookie with invalid CSRF token');
            $this->assertTrue($cookie->isExpired(), 'Should automatically delete cookie with invalid CSRF token');
        }
    }

    /**
     * Test that request data works with the various http methods.
     *
     * @dataProvider httpMethodProvider
     */
    public function testValidTokenRequestDataCompat(string $method): void
    {
        $middleware = new CsrfProtectionMiddleware();
        $token = $this->createOldToken();

        $request = new ServerRequest([
            'environment' => [
                'REQUEST_METHOD' => $method,
            ],
            'post' => ['_csrfToken' => $token],
            'cookies' => ['csrfToken' => $token],
        ]);

        $handler = new TestRequestHandler(function ($request) {
            $this->assertNull($request->getData('_csrfToken'));

            return new Response();
        });

        // No exception means everything is OK
        $middleware->process($request, $handler);
    }

    /**
     * Test that request data works with the various http methods.
     *
     * @dataProvider httpMethodProvider
     */
    public function testValidTokenRequestDataSalted(string $method): void
    {
        $middleware = new CsrfProtectionMiddleware();
        $token = $middleware->createToken();
        $salted = $middleware->saltToken($token);

        $request = new ServerRequest([
            'environment' => [
                'REQUEST_METHOD' => $method,
            ],
            'post' => ['_csrfToken' => $salted],
            'cookies' => ['csrfToken' => $token],
        ]);

        $handler = new TestRequestHandler(function ($request) {
            $this->assertNull($request->getData('_csrfToken'));

            return new Response();
        });

        // No exception means everything is OK
        $middleware->process($request, $handler);
    }

    /**
     * Test that invalid string cookies are rejected.
     *
     * @return void
     */
    public function testInvalidTokenStringCookies()
    {
        $this->expectException(InvalidCsrfTokenException::class);
        $request = new ServerRequest([
            'environment' => [
                'REQUEST_METHOD' => 'POST',
            ],
            'post' => ['_csrfToken' => ["\x20\x26"]],
            'cookies' => ['csrfToken' => ["\x20\x26"]],
        ]);
        $middleware = new CsrfProtectionMiddleware();
        $middleware->process($request, $this->_getRequestHandler());
    }

    /**
     * Test that empty value cookies are rejected
     *
     * @return void
     */
    public function testInvalidTokenEmptyStringCookies()
    {
        $this->expectException(InvalidCsrfTokenException::class);
        $request = new ServerRequest([
            'environment' => [
                'REQUEST_METHOD' => 'POST',
            ],
            'post' => ['_csrfToken' => '*(&'],
            // Invalid data that can't be base64 decoded.
            'cookies' => ['csrfToken' => '*(&'],
        ]);
        $middleware = new CsrfProtectionMiddleware();
        $middleware->process($request, $this->_getRequestHandler());
    }

    /**
     * Test that request non string cookies are ignored.
     */
    public function testInvalidTokenNonStringCookies(): void
    {
        $this->expectException(InvalidCsrfTokenException::class);
        $request = new ServerRequest([
            'environment' => [
                'REQUEST_METHOD' => 'POST',
            ],
            'post' => ['_csrfToken' => ['nope']],
            'cookies' => ['csrfToken' => ['nope']],
        ]);
        $middleware = new CsrfProtectionMiddleware();
        $middleware->process($request, $this->_getRequestHandler());
    }

    /**
     * Test that request data works with the various http methods.
     *
     * @dataProvider httpMethodProvider
     */
    public function testInvalidTokenRequestData(string $method): void
    {
        $request = new ServerRequest([
            'environment' => [
                'REQUEST_METHOD' => $method,
            ],
            'post' => ['_csrfToken' => 'nope'],
            'cookies' => ['csrfToken' => 'testing123'],
        ]);

        $middleware = new CsrfProtectionMiddleware();

        try {
            $middleware->process($request, $this->_getRequestHandler());

            $this->fail();
        } catch (InvalidCsrfTokenException $exception) {
            $responseHeaders = $exception->getHeaders();
            $this->assertArrayHasKey('Set-Cookie', $responseHeaders);

            $cookie = Cookie::createFromHeaderString($responseHeaders['Set-Cookie']);
            $this->assertSame('csrfToken', $cookie->getName(), 'Should automatically delete cookie with invalid CSRF token');
            $this->assertTrue($cookie->isExpired(), 'Should automatically delete cookie with invalid CSRF token');
        }
    }

    /**
     * Test that tokens cannot be simple matches and must pass our hmac.
     */
    public function testInvalidTokenIncorrectOrigin(): void
    {
        $request = new ServerRequest([
            'environment' => [
                'REQUEST_METHOD' => 'POST',
            ],
            'post' => ['_csrfToken' => 'this is a match'],
            'cookies' => ['csrfToken' => 'this is a match'],
        ]);

        $middleware = new CsrfProtectionMiddleware();

        $this->expectException(InvalidCsrfTokenException::class);
        $middleware->process($request, $this->_getRequestHandler());
    }

    /**
     * Test that missing post field fails
     */
    public function testInvalidTokenRequestDataMissing(): void
    {
        $request = new ServerRequest([
            'environment' => [
                'REQUEST_METHOD' => 'POST',
            ],
            'post' => [],
            'cookies' => ['csrfToken' => 'testing123'],
        ]);

        $middleware = new CsrfProtectionMiddleware();
        $this->expectException(InvalidCsrfTokenException::class);
        $middleware->process($request, $this->_getRequestHandler());
    }

    /**
     * Test that missing header and cookie fails
     *
     * @dataProvider httpMethodProvider
     */
    public function testInvalidTokenMissingCookie(string $method): void
    {
        $request = new ServerRequest([
            'environment' => [
                'REQUEST_METHOD' => $method,
            ],
            'post' => ['_csrfToken' => 'could-be-valid'],
            'cookies' => [],
        ]);

        $middleware = new CsrfProtectionMiddleware();

        try {
            $middleware->process($request, $this->_getRequestHandler());

            $this->fail();
        } catch (InvalidCsrfTokenException $exception) {
            $responseHeaders = $exception->getHeaders();
            $this->assertEmpty($responseHeaders, 'Should not send any header');
        }
    }

    /**
     * Test that the configuration options work.
     */
    public function testConfigurationCookieCreate(): void
    {
        $request = new ServerRequest([
            'environment' => ['REQUEST_METHOD' => 'GET'],
            'webroot' => '/dir/',
        ]);

        $middleware = new CsrfProtectionMiddleware([
            'cookieName' => 'token',
            'expiry' => '+1 hour',
            'secure' => true,
            'httponly' => true,
            'samesite' => CookieInterface::SAMESITE_STRICT,
        ]);
        $response = $middleware->process($request, $this->_getRequestHandler());

        $this->assertEmpty($response->getCookie('csrfToken'));
        $cookie = $response->getCookie('token');
        $this->assertNotEmpty($cookie, 'Should set a token.');
        $this->assertMatchesRegularExpression('/^[a-z0-9\/+]+={0,2}$/i', $cookie['value'], 'Should look like base64.');
        $this->assertWithinRange(strtotime('+1 hour'), $cookie['expires'], 1, 'session duration.');
        $this->assertSame('/dir/', $cookie['path'], 'session path.');
        $this->assertTrue($cookie['secure'], 'cookie security flag missing');
        $this->assertTrue($cookie['httponly'], 'cookie httponly flag missing');
        $this->assertSame(CookieInterface::SAMESITE_STRICT, $cookie['samesite'], 'samesite attribute missing');
    }

    public function testUsingDeprecatedConfigKey(): void
    {
        $this->deprecated(function (): void {
            $request = new ServerRequest([
                'environment' => ['REQUEST_METHOD' => 'GET'],
                'webroot' => '/dir/',
            ]);

            $middleware = new CsrfProtectionMiddleware([
                'cookieName' => 'token',
                'expiry' => '+1 hour',
                'secure' => true,
                'httpOnly' => true,
                'samesite' => CookieInterface::SAMESITE_STRICT,
            ]);
            $response = $middleware->process($request, $this->_getRequestHandler());

            $cookie = $response->getCookie('token');
            $this->assertTrue($cookie['httponly'], 'cookie httponly flag missing');
        });
    }

    /**
     * Test that the configuration options work.
     *
     * There should be no exception thrown.
     */
    public function testConfigurationValidate(): void
    {
        $middleware = new CsrfProtectionMiddleware([
            'cookieName' => 'token',
            'field' => 'token',
            'expiry' => 90,
        ]);
        $token = $middleware->createToken();
        $request = new ServerRequest([
            'environment' => ['REQUEST_METHOD' => 'POST'],
            'cookies' => ['csrfToken' => 'nope', 'token' => $token],
            'post' => ['_csrfToken' => 'no match', 'token' => $token],
        ]);
        $response = new Response();

        $response = $middleware->process($request, $this->_getRequestHandler());
        $this->assertInstanceOf(Response::class, $response);
    }

    public function testSkippingTokenCheckUsingWhitelistCallback(): void
    {
        $this->deprecated(function (): void {
            $request = new ServerRequest([
                'post' => [
                    '_csrfToken' => 'foo',
                ],
                'environment' => [
                    'REQUEST_METHOD' => 'POST',
                ],
            ]);
            $response = new Response();

            $middleware = new CsrfProtectionMiddleware();
            $middleware->whitelistCallback(function (ServerRequestInterface $request) {
                $this->assertSame('POST', $request->getServerParams()['REQUEST_METHOD']);

                return true;
            });

            $handler = new TestRequestHandler(function ($request) {
                $this->assertEmpty($request->getParsedBody());

                return new Response();
            });

            $response = $middleware->process($request, $handler);
            $this->assertInstanceOf(Response::class, $response);
        });
    }

    public function testSkippingTokenCheckUsingSkipCheckCallback(): void
    {
        $request = new ServerRequest([
            'post' => [
                '_csrfToken' => 'foo',
            ],
            'environment' => [
                'REQUEST_METHOD' => 'POST',
            ],
        ]);
        $response = new Response();

        $middleware = new CsrfProtectionMiddleware();
        $middleware->skipCheckCallback(function (ServerRequestInterface $request) {
            $this->assertSame('POST', $request->getServerParams()['REQUEST_METHOD']);

            return true;
        });

        $handler = new TestRequestHandler(function ($request) {
            $this->assertEmpty($request->getParsedBody());

            return new Response();
        });

        $response = $middleware->process($request, $handler);
        $this->assertInstanceOf(Response::class, $response);
    }

    /**
     * Ensure salting is not consistent
     */
    public function testSaltToken(): void
    {
        $middleware = new CsrfProtectionMiddleware();
        $token = $middleware->createToken();
        $results = [];
        for ($i = 0; $i < 10; $i++) {
            $results[] = $middleware->saltToken($token);
        }
        $this->assertCount(10, array_unique($results));
    }
}
