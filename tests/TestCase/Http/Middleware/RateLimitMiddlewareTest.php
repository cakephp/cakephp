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
 * @since         5.3.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Http\Middleware;

use Cake\Cache\Cache;
use Cake\Http\Exception\TooManyRequestsException;
use Cake\Http\Middleware\RateLimitMiddleware;
use Cake\Http\Response;
use Cake\Http\ServerRequest;
use Cake\TestSuite\TestCase;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use Psr\Http\Server\RequestHandlerInterface;
use stdClass;

/**
 * RateLimitMiddleware test case
 */
#[AllowMockObjectsWithoutExpectations]
class RateLimitMiddlewareTest extends TestCase
{
    /**
     * @var \Psr\Http\Server\RequestHandlerInterface
     */
    protected $handler;

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        Cache::setConfig('rate_limit_test', [
            'className' => 'Array',
        ]);

        $this->handler = $this->createMock(RequestHandlerInterface::class);
        $this->handler->method('handle')
            ->willReturn(new Response());
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown(): void
    {
        parent::tearDown();
        Cache::drop('rate_limit_test');
    }

    /**
     * Test basic rate limiting
     *
     * @return void
     */
    public function testBasicRateLimit(): void
    {
        $middleware = new RateLimitMiddleware([
            'limit' => 2,
            'window' => 60,
            'cache' => 'rate_limit_test',
        ]);

        $request = new ServerRequest([
            'environment' => ['REMOTE_ADDR' => '127.0.0.1'],
        ]);

        // First request should pass
        $response = $middleware->process($request, $this->handler);
        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals('2', $response->getHeaderLine('X-RateLimit-Limit'));
        $this->assertEquals('1', $response->getHeaderLine('X-RateLimit-Remaining'));

        // Second request should pass
        $response = $middleware->process($request, $this->handler);
        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals('0', $response->getHeaderLine('X-RateLimit-Remaining'));

        // Third request should fail
        $this->expectException(TooManyRequestsException::class);
        $this->expectExceptionMessage('Rate limit exceeded. Please try again later.');
        $middleware->process($request, $this->handler);
    }

    /**
     * Test IP-based identification
     *
     * @return void
     */
    public function testIpIdentification(): void
    {
        $middleware = new RateLimitMiddleware([
            'limit' => 1,
            'window' => 60,
            'identifier' => 'ip',
            'cache' => 'rate_limit_test',
        ]);

        $request1 = new ServerRequest([
            'environment' => ['REMOTE_ADDR' => '192.168.1.1'],
        ]);
        $request2 = new ServerRequest([
            'environment' => ['REMOTE_ADDR' => '192.168.1.2'],
        ]);

        // Different IPs should have separate limits
        $middleware->process($request1, $this->handler);
        $middleware->process($request2, $this->handler);

        // Second request from first IP should fail
        $this->expectException(TooManyRequestsException::class);
        $middleware->process($request1, $this->handler);
    }

    /**
     * Test skip check callback
     *
     * @return void
     */
    public function testSkipCheck(): void
    {
        $middleware = new RateLimitMiddleware([
            'limit' => 1,
            'window' => 60,
            'cache' => 'rate_limit_test',
            'skipCheck' => function ($request) {
                return $request->getParam('action') === 'health';
            },
        ]);

        $request = new ServerRequest([
            'environment' => ['REMOTE_ADDR' => '127.0.0.1'],
            'params' => ['action' => 'health'],
        ]);

        // Should not be rate limited
        $middleware->process($request, $this->handler);
        $middleware->process($request, $this->handler);
        $middleware->process($request, $this->handler);

        // No exception thrown
        $this->assertTrue(true);
    }

    /**
     * Test custom identifier callback
     *
     * @return void
     */
    public function testCustomIdentifier(): void
    {
        $middleware = new RateLimitMiddleware([
            'limit' => 1,
            'window' => 60,
            'cache' => 'rate_limit_test',
            'identifierCallback' => function ($request) {
                return $request->getHeaderLine('X-API-Key');
            },
        ]);

        $request1 = (new ServerRequest())->withHeader('X-API-Key', 'key1');
        $request2 = (new ServerRequest())->withHeader('X-API-Key', 'key2');

        // Different API keys should have separate limits
        $middleware->process($request1, $this->handler);
        $middleware->process($request2, $this->handler);

        // Second request with same key should fail
        $this->expectException(TooManyRequestsException::class);
        $middleware->process($request1, $this->handler);
    }

    /**
     * Test cost callback
     *
     * @return void
     */
    public function testCostCallback(): void
    {
        $middleware = new RateLimitMiddleware([
            'limit' => 10,
            'window' => 60,
            'cache' => 'rate_limit_test',
            'costCallback' => function ($request) {
                return $request->getMethod() === 'POST' ? 5 : 1;
            },
        ]);

        $getRequest = new ServerRequest([
            'environment' => ['REMOTE_ADDR' => '127.0.0.1', 'REQUEST_METHOD' => 'GET'],
        ]);
        $postRequest = new ServerRequest([
            'environment' => ['REMOTE_ADDR' => '127.0.0.1', 'REQUEST_METHOD' => 'POST'],
        ]);

        // GET request costs 1
        $response = $middleware->process($getRequest, $this->handler);
        $this->assertEquals('9', $response->getHeaderLine('X-RateLimit-Remaining'));

        // POST request costs 5
        $response = $middleware->process($postRequest, $this->handler);
        $this->assertEquals('4', $response->getHeaderLine('X-RateLimit-Remaining'));
    }

    /**
     * Test headers are not added when disabled
     *
     * @return void
     */
    public function testNoHeaders(): void
    {
        $middleware = new RateLimitMiddleware([
            'limit' => 10,
            'window' => 60,
            'cache' => 'rate_limit_test',
            'headers' => false,
        ]);

        $request = new ServerRequest([
            'environment' => ['REMOTE_ADDR' => '127.0.0.1'],
        ]);

        $response = $middleware->process($request, $this->handler);
        $this->assertFalse($response->hasHeader('X-RateLimit-Limit'));
        $this->assertFalse($response->hasHeader('X-RateLimit-Remaining'));
        $this->assertFalse($response->hasHeader('X-RateLimit-Reset'));
    }

    /**
     * Test user-based identification
     *
     * @return void
     */
    public function testUserIdentification(): void
    {
        $middleware = new RateLimitMiddleware([
            'limit' => 1,
            'window' => 60,
            'identifier' => 'user',
            'cache' => 'rate_limit_test',
        ]);

        $identity = new stdClass();
        $identity->id = '123';

        $request = (new ServerRequest([
            'environment' => ['REMOTE_ADDR' => '127.0.0.1'],
        ]))->withAttribute('identity', $identity);

        $response = $middleware->process($request, $this->handler);
        $this->assertEquals('0', $response->getHeaderLine('X-RateLimit-Remaining'));

        // Second request should fail
        $this->expectException(TooManyRequestsException::class);
        $middleware->process($request, $this->handler);
    }

    /**
     * Test route-based identification
     *
     * @return void
     */
    public function testRouteIdentification(): void
    {
        $middleware = new RateLimitMiddleware([
            'limit' => 1,
            'window' => 60,
            'identifier' => 'route',
            'cache' => 'rate_limit_test',
        ]);

        $request = (new ServerRequest([
            'environment' => ['REMOTE_ADDR' => '127.0.0.1'],
        ]))->withAttribute('params', [
            'controller' => 'Users',
            'action' => 'login',
        ]);

        $response = $middleware->process($request, $this->handler);
        $this->assertEquals('0', $response->getHeaderLine('X-RateLimit-Remaining'));

        // Different route should work
        $request2 = (new ServerRequest([
            'environment' => ['REMOTE_ADDR' => '127.0.0.1'],
        ]))->withAttribute('params', [
            'controller' => 'Users',
            'action' => 'logout',
        ]);

        $middleware->process($request2, $this->handler);

        // Same route should fail
        $this->expectException(TooManyRequestsException::class);
        $middleware->process($request, $this->handler);
    }

    /**
     * Test proxy headers for IP detection
     *
     * @return void
     */
    public function testProxyHeaders(): void
    {
        $middleware = new RateLimitMiddleware([
            'limit' => 1,
            'window' => 60,
            'cache' => 'rate_limit_test',
        ]);

        // Test Cloudflare header
        $request = new ServerRequest([
            'environment' => [
                'REMOTE_ADDR' => '127.0.0.1',
                'HTTP_CF_CONNECTING_IP' => '192.168.1.100',
            ],
        ]);
        $middleware->process($request, $this->handler);

        // Test X-Forwarded-For
        $request2 = new ServerRequest([
            'environment' => [
                'REMOTE_ADDR' => '127.0.0.1',
                'HTTP_X_FORWARDED_FOR' => '192.168.1.101, 10.0.0.1',
            ],
        ]);
        $middleware->process($request2, $this->handler);

        // Different IPs should work
        $this->assertTrue(true);
    }
}
