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
 * @since         4.2.0
 * @license       https://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Http\Middleware;

use Cake\Http\Exception\InvalidCsrfTokenException;
use Cake\Http\Middleware\SessionCsrfProtectionMiddleware;
use Cake\Http\Response;
use Cake\Http\ServerRequest;
use Cake\TestSuite\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TestApp\Http\TestRequestHandler;

/**
 * Test for SessionCsrfProtection
 */
class SessionCsrfProtectionMiddlewareTest extends TestCase
{
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
    public function testSettingTokenInSession(): void
    {
        $request = new ServerRequest([
            'environment' => ['REQUEST_METHOD' => 'GET'],
            'webroot' => '/dir/',
        ]);

        /** @var \Cake\Http\ServerRequest|null $updatedRequest */
        $updatedRequest = null;
        $handler = new TestRequestHandler(function ($request) use (&$updatedRequest) {
            $updatedRequest = $request;

            return new Response();
        });

        $middleware = new SessionCsrfProtectionMiddleware();
        $response = $middleware->process($request, $handler);

        $this->assertInstanceOf(Response::class, $response);
        $token = $updatedRequest->getSession()->read('csrfToken');
        $this->assertNotEmpty($token, 'Should set a token.');
        $this->assertMatchesRegularExpression('/^[A-Z0-9+\/]+=*$/i', $token, 'Should look like base64 data.');
        $requestAttr = $updatedRequest->getAttribute('csrfToken');
        $this->assertNotEquals($token, $requestAttr);
        $this->assertEquals(strlen($token) * 2, strlen($requestAttr));
        $this->assertMatchesRegularExpression('/^[A-Z0-9\/+]+=*$/i', $requestAttr);
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
        ]);
        $request->getSession()->write('csrfToken', 'testing123');

        // No exception means the test is valid
        $middleware = new SessionCsrfProtectionMiddleware();
        $response = $middleware->process($request, $this->_getRequestHandler());
        $this->assertInstanceOf(Response::class, $response);
    }

    /**
     * Test that the X-CSRF-Token works with the various http methods.
     *
     * Ensure unsalted tokens work.
     *
     * @dataProvider httpMethodProvider
     */
    public function testValidTokenInHeaderBackwardsCompat(string $method): void
    {
        $middleware = new SessionCsrfProtectionMiddleware();
        $token = $middleware->createToken();
        $request = new ServerRequest([
            'environment' => [
                'REQUEST_METHOD' => $method,
                'HTTP_X_CSRF_TOKEN' => $token,
            ],
            'post' => ['a' => 'b'],
        ]);
        $request->getSession()->write('csrfToken', $token);
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
        $middleware = new SessionCsrfProtectionMiddleware();
        $token = $middleware->createToken();
        $salted = $middleware->saltToken($token);
        $request = new ServerRequest([
            'environment' => [
                'REQUEST_METHOD' => $method,
                'HTTP_X_CSRF_TOKEN' => $salted,
            ],
            'post' => ['a' => 'b'],
        ]);
        $request->getSession()->write('csrfToken', $token);
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
        ]);
        $request->getSession()->write('csrfToken', 'testing123');

        $middleware = new SessionCsrfProtectionMiddleware();

        try {
            $middleware->process($request, $this->_getRequestHandler());

            $this->fail();
        } catch (InvalidCsrfTokenException $exception) {
            $token = $request->getSession()->read('csrfToken');
            $this->assertSame('testing123', $token, 'session token should not change.');
        }
    }

    /**
     * Test that request data works with the various http methods.
     *
     * Ensure unsalted tokens are still accepted.
     *
     * @dataProvider httpMethodProvider
     */
    public function testValidTokenInRequestDataBackwardsCompat(string $method): void
    {
        $middleware = new SessionCsrfProtectionMiddleware();
        $token = $middleware->createToken();

        $request = new ServerRequest([
            'environment' => [
                'REQUEST_METHOD' => $method,
            ],
            'post' => ['_csrfToken' => $token],
        ]);
        $request->getSession()->write('csrfToken', $token);

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
     * Ensure salted tokens are accepted.
     *
     * @dataProvider httpMethodProvider
     */
    public function testValidTokenInRequestData(string $method): void
    {
        $middleware = new SessionCsrfProtectionMiddleware();
        $token = $middleware->createToken();
        $salted = $middleware->saltToken($token);

        $request = new ServerRequest([
            'environment' => [
                'REQUEST_METHOD' => $method,
            ],
            'post' => ['_csrfToken' => $salted],
        ]);
        $request->getSession()->write('csrfToken', $token);

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
    public function testInvalidTokenRequestData(string $method): void
    {
        $middleware = new SessionCsrfProtectionMiddleware();
        $token = $middleware->createToken();
        $request = new ServerRequest([
            'environment' => [
                'REQUEST_METHOD' => $method,
            ],
            'post' => ['_csrfToken' => 'nope'],
        ]);
        $request->getSession()->write('csrfToken', $token);

        $middleware = new SessionCsrfProtectionMiddleware();

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
        ]);
        $request->getSession()->write('csrfToken', 'testing123');

        $middleware = new SessionCsrfProtectionMiddleware();
        $this->expectException(InvalidCsrfTokenException::class);
        $middleware->process($request, $this->_getRequestHandler());
    }

    /**
     * Test that missing session key fails
     *
     * @dataProvider httpMethodProvider
     */
    public function testInvalidTokenMissingSession(string $method): void
    {
        $request = new ServerRequest([
            'environment' => [
                'REQUEST_METHOD' => $method,
            ],
            'post' => ['_csrfToken' => 'could-be-valid'],
            'cookies' => [],
        ]);

        $middleware = new SessionCsrfProtectionMiddleware();

        try {
            $middleware->process($request, $this->_getRequestHandler());

            $this->fail();
        } catch (InvalidCsrfTokenException $exception) {
            $token = $request->getSession()->read('csrfToken');
            $this->assertNotEmpty($token, 'Should set a token in the session on failure.');
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

        $middleware = new SessionCsrfProtectionMiddleware([
            'key' => 'csrf',
        ]);
        $middleware->process($request, $this->_getRequestHandler());

        $session = $request->getSession();
        $this->assertEmpty($session->read('csrfToken'));
        $token = $session->read('csrf');
        $this->assertNotEmpty($token, 'Should set a token.');
        $this->assertMatchesRegularExpression('/^[A-Z0-9\/+]+=*$/i', $token, 'Should look like base64 data.');
    }

    /**
     * Test that the configuration options work.
     *
     * There should be no exception thrown.
     */
    public function testConfigurationValidate(): void
    {
        $middleware = new SessionCsrfProtectionMiddleware([
            'key' => 'csrf',
            'field' => 'token',
        ]);
        $token = $middleware->createToken();
        $request = new ServerRequest([
            'environment' => ['REQUEST_METHOD' => 'POST'],
            'post' => ['_csrfToken' => 'no match', 'token' => $token],
        ]);
        $request->getSession()->write('csrf', $token);

        $response = $middleware->process($request, $this->_getRequestHandler());
        $this->assertInstanceOf(Response::class, $response);
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
        $request->getSession()->write('csrfToken', 'foo');

        $middleware = new SessionCsrfProtectionMiddleware();
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
        $middleware = new SessionCsrfProtectionMiddleware();
        $token = $middleware->createToken();
        $results = [];
        for ($i = 0; $i < 10; $i++) {
            $results[] = $middleware->saltToken($token);
        }
        $this->assertCount(10, array_unique($results));
    }
}
