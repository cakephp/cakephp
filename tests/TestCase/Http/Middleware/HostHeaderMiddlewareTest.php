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
 * @since         5.5.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Http\Middleware;

use Cake\Core\Configure;
use Cake\Http\Exception\BadRequestException;
use Cake\Http\Exception\InternalErrorException;
use Cake\Http\Middleware\HostHeaderMiddleware;
use Cake\Http\Response;
use Cake\Http\ServerRequest;
use Cake\TestSuite\TestCase;
use Laminas\Diactoros\Uri;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Tests host validation against application URLs and trusted host patterns.
 */
class HostHeaderMiddlewareTest extends TestCase
{
    /**
     * Configure a production application with a canonical URL.
     */
    protected function setUp(): void
    {
        parent::setUp();

        Configure::write('debug', false);
        Configure::write('App.fullBaseUrl', 'https://example.com');
        Configure::delete('App.trustedHostPatterns');
    }

    /**
     * Test delimiterless regular expressions and rejection of untrusted hostnames.
     *
     * @param array<string> $patterns The trusted host patterns.
     * @param string $uri The request URI.
     * @param bool $trusted Whether the host should be accepted.
     */
    #[DataProvider('trustedHostPatternsProvider')]
    public function testTrustedHostPatterns(array $patterns, string $uri, bool $trusted): void
    {
        $middleware = new HostHeaderMiddleware(trustedHostPatterns: $patterns);
        $request = new ServerRequest(['uri' => new Uri($uri)]);

        if ($trusted) {
            $this->assertHostAccepted($middleware, $request);

            return;
        }

        $this->assertHostRejected($middleware, $request);
    }

    /**
     * Provide exact, wildcard, and custom pattern matching scenarios.
     *
     * @return array<string, array{array<string>, string, bool}>
     */
    public static function trustedHostPatternsProvider(): array
    {
        $patterns = ['^example\\.com$', '^[a-z0-9-]+\\.preview\\.example\\.com$', '^localhost$'];

        return [
            'canonical host' => [$patterns, 'https://example.com', true],
            'case insensitive' => [['^EXAMPLE\\.COM$'], 'https://Example.Com', true],
            'preview host' => [$patterns, 'https://feature-123.preview.example.com', true],
            'localhost with port' => [$patterns, 'http://localhost:8765', true],
            'unrelated host' => [$patterns, 'https://evil.test', false],
            'suffix attack' => [$patterns, 'https://example.com.evil.test', false],
            'prefix attack' => [$patterns, 'https://notexample.com', false],
            'escaped dots' => [$patterns, 'https://exampleXcom', false],
            'nested preview host' => [$patterns, 'https://nested.feature.preview.example.com', false],
            'missing preview label' => [$patterns, 'https://preview.example.com', false],
            'base URL is not implicitly trusted' => [['^localhost$'], 'https://example.com', false],
            'IPv4 host' => [['^127\\.0\\.0\\.1$'], 'http://127.0.0.1:8080', true],
            'IPv6 host' => [['^\\[::1\\]$'], 'http://[::1]:8080', true],
            'quantifier' => [['^[a-z]{1,3}\\.example\\.com$'], 'https://abc.example.com', true],
            'quantifier exceeded' => [['^[a-z]{1,3}\\.example\\.com$'], 'https://abcd.example.com', false],
            'pattern with slash' => [['^example\\.com(?=/|$)'], 'https://example.com', true],
            'caller controls anchors' => [['example\\.com'], 'https://sub.example.com', true],
            'empty host' => [['^$'], '/', false],
        ];
    }

    /**
     * Test exact hostname matching when patterns are not configured.
     *
     * @param string $fullBaseUrl The configured application URL.
     * @param string $uri The request URI.
     * @param bool $trusted Whether the host should be accepted.
     */
    #[DataProvider('fullBaseUrlProvider')]
    public function testFullBaseUrlFallback(string $fullBaseUrl, string $uri, bool $trusted): void
    {
        Configure::write('App.fullBaseUrl', $fullBaseUrl);
        $middleware = new HostHeaderMiddleware();
        $request = new ServerRequest(['uri' => new Uri($uri)]);

        if ($trusted) {
            $this->assertHostAccepted($middleware, $request);

            return;
        }

        $this->assertHostRejected($middleware, $request);
    }

    /**
     * Provide URL fallback scenarios without comparing schemes, ports, or paths.
     *
     * @return array<string, array{string, string, bool}>
     */
    public static function fullBaseUrlProvider(): array
    {
        return [
            'canonical host' => ['https://example.com', 'https://example.com', true],
            'case insensitive' => ['https://EXAMPLE.COM', 'https://Example.Com', true],
            'scheme port and path ignored' => ['https://example.com:8443/app', 'http://example.com:8080/other', true],
            'IPv4 host' => ['http://127.0.0.1', 'http://127.0.0.1:8080', true],
            'IPv6 host' => ['http://[::1]:8765', 'http://[::1]:8080', true],
            'zero hostname' => ['http://0', 'http://0:8080', true],
            'unrelated host' => ['https://example.com', 'https://evil.test', false],
            'subdomain' => ['https://example.com', 'https://sub.example.com', false],
            'suffix attack' => ['https://example.com', 'https://example.com.evil.test', false],
            'host is escaped literally' => ['https://example.com', 'https://exampleXcom', false],
            'empty host' => ['https://example.com', '/', false],
        ];
    }

    /**
     * Test App patterns without requiring a full base URL.
     */
    public function testApplicationPatternsWithoutFullBaseUrl(): void
    {
        Configure::write('App.trustedHostPatterns', ['^localhost$']);
        Configure::write('App.fullBaseUrl', false);

        $this->assertHostAccepted(
            new HostHeaderMiddleware(),
            new ServerRequest(['uri' => new Uri('http://localhost:8080')]),
        );
    }

    /**
     * Test explicit patterns taking precedence over application configuration.
     */
    public function testPatternArgumentOverridesApplicationConfiguration(): void
    {
        Configure::write('App.trustedHostPatterns', ['^other\\.example$']);
        Configure::write('App.fullBaseUrl', 'invalid');

        $this->assertHostAccepted(
            new HostHeaderMiddleware(trustedHostPatterns: ['^example\\.com$']),
            new ServerRequest(['uri' => new Uri('https://example.com')]),
        );
    }

    /**
     * Test an explicit empty list selecting the full base URL fallback.
     */
    public function testEmptyPatternsOverrideApplicationPatterns(): void
    {
        Configure::write('App.trustedHostPatterns', ['^other\\.example$']);
        $middleware = new HostHeaderMiddleware(trustedHostPatterns: []);

        $this->assertHostAccepted($middleware, new ServerRequest(['uri' => new Uri('https://example.com')]));
        $this->assertHostRejected($middleware, new ServerRequest(['uri' => new Uri('https://other.example')]));
    }

    /**
     * Test an explicit URL taking precedence over the configured application URL.
     */
    public function testFullBaseUrlArgumentOverridesApplicationConfiguration(): void
    {
        Configure::write('App.fullBaseUrl', 'https://other.example');

        $this->assertHostAccepted(
            new HostHeaderMiddleware(fullBaseUrl: 'https://example.com'),
            new ServerRequest(['uri' => new Uri('https://example.com')]),
        );
    }

    /**
     * Test that an explicit empty URL does not fall back to App configuration.
     */
    public function testEmptyFullBaseUrlOverridesApplicationConfiguration(): void
    {
        $this->assertHostRejected(
            new HostHeaderMiddleware(fullBaseUrl: ''),
            new ServerRequest(['uri' => new Uri('https://example.com')]),
            InternalErrorException::class,
        );
    }

    /**
     * Test that a null pattern setting selects the full base URL fallback.
     */
    public function testNullApplicationPatterns(): void
    {
        Configure::write('App.trustedHostPatterns');

        $this->assertHostAccepted(
            new HostHeaderMiddleware(),
            new ServerRequest(['uri' => new Uri('https://example.com')]),
        );
    }

    /**
     * Test that application host settings are resolved at construction.
     */
    public function testConfigurationIsResolvedAtConstruction(): void
    {
        Configure::write('App.trustedHostPatterns', ['^example\\.com$']);
        $middleware = new HostHeaderMiddleware();
        Configure::write('App.trustedHostPatterns', ['^other\\.example$']);
        Configure::write('App.fullBaseUrl', 'https://other.example');

        $this->assertHostAccepted($middleware, new ServerRequest(['uri' => new Uri('https://example.com')]));
    }

    /**
     * Test debug bypass without requiring valid host configuration.
     */
    public function testDebugBypass(): void
    {
        Configure::write('App.trustedHostPatterns', 'invalid');
        Configure::write('App.fullBaseUrl', false);
        $middleware = new HostHeaderMiddleware();
        Configure::write('debug', true);

        $this->assertHostAccepted($middleware, new ServerRequest(['uri' => new Uri('/')]));
    }

    /**
     * Test host enforcement in debug mode when the bypass is disabled.
     */
    public function testDebugBypassCanBeDisabled(): void
    {
        Configure::write('debug', true);
        $middleware = new HostHeaderMiddleware(disableOnDebug: false);

        $this->assertHostAccepted($middleware, new ServerRequest(['uri' => new Uri('https://example.com')]));
        $this->assertHostRejected($middleware, new ServerRequest(['uri' => new Uri('https://evil.test')]));
    }

    /**
     * Test configuration errors when validation is explicitly enabled in debug mode.
     */
    public function testInvalidConfigurationWithDebugBypassDisabled(): void
    {
        Configure::write('debug', true);
        Configure::write('App.fullBaseUrl', false);

        $this->assertHostRejected(
            new HostHeaderMiddleware(disableOnDebug: false),
            new ServerRequest(['uri' => new Uri('https://example.com')]),
            InternalErrorException::class,
        );
    }

    /**
     * Test that malformed pattern configuration cannot use the full base URL fallback.
     *
     * @param mixed $patterns The invalid application pattern setting.
     */
    #[DataProvider('invalidPatternsProvider')]
    public function testInvalidPatterns(mixed $patterns): void
    {
        Configure::write('App.trustedHostPatterns', $patterns);

        $this->assertHostRejected(
            new HostHeaderMiddleware(),
            new ServerRequest(['uri' => new Uri('https://example.com')]),
            InternalErrorException::class,
        );
    }

    /**
     * Provide malformed regular expressions, entries, and pattern-list types.
     *
     * @return array<string, array{mixed}>
     */
    public static function invalidPatternsProvider(): array
    {
        return [
            'string instead of array' => ['^example\\.com$'],
            'false instead of array' => [false],
            'integer instead of array' => [123],
            'empty pattern' => [['']],
            'integer entry' => [[123]],
            'null entry' => [[null]],
            'nested array' => [[['^example\\.com$']]],
            'invalid regex' => [['[']],
            'regex execution failure' => [['(*LIMIT_MATCH=1)^(e+)+x$']],
            'invalid regex after matching pattern' => [['^example\\.com$', '(']],
            'invalid entry after matching pattern' => [['^example\\.com$', false]],
        ];
    }

    /**
     * Test that a missing or unusable fallback URL is a configuration error.
     *
     * @param mixed $fullBaseUrl The invalid application URL setting.
     */
    #[DataProvider('invalidFullBaseUrlProvider')]
    public function testInvalidFullBaseUrl(mixed $fullBaseUrl): void
    {
        Configure::write('App.fullBaseUrl', $fullBaseUrl);

        $this->assertHostRejected(
            new HostHeaderMiddleware(),
            new ServerRequest(['uri' => new Uri('https://example.com')]),
            InternalErrorException::class,
        );
    }

    /**
     * Provide missing URLs, incorrect types, and malformed absolute URLs.
     *
     * @return array<string, array{mixed}>
     */
    public static function invalidFullBaseUrlProvider(): array
    {
        return [
            'null' => [null],
            'false' => [false],
            'empty string' => [''],
            'boolean' => [true],
            'integer' => [123],
            'array' => [[]],
            'bare hostname' => ['example.com'],
            'relative URL' => ['/example.com'],
            'scheme relative URL' => ['//example.com'],
            'missing host' => ['https://'],
            'invalid port' => ['https://example.com:99999'],
            'unsupported scheme' => ['ftp://example.com'],
        ];
    }

    /**
     * Test that Host and forwarded headers do not replace URI host validation.
     *
     * @param string $uriHost The request URI host.
     * @param string $headerHost The separate header values.
     * @param bool $trusted Whether the URI host should be accepted.
     */
    #[DataProvider('requestHostSourceProvider')]
    public function testRequestHostSource(string $uriHost, string $headerHost, bool $trusted): void
    {
        $request = (new ServerRequest(['uri' => new Uri('https://' . $uriHost)]))
            ->withHeader('Host', $headerHost)
            ->withHeader('X-Forwarded-Host', $headerHost);
        $middleware = new HostHeaderMiddleware();

        if ($trusted) {
            $this->assertHostAccepted($middleware, $request);

            return;
        }

        $this->assertHostRejected($middleware, $request);
    }

    /**
     * Provide separate URI and header hostnames.
     *
     * @return array<string, array{string, string, bool}>
     */
    public static function requestHostSourceProvider(): array
    {
        return [
            'untrusted URI host' => ['evil.test', 'example.com', false],
            'trusted URI host' => ['example.com', 'evil.test', true],
        ];
    }

    /**
     * Assert that the original request and response pass through exactly once.
     *
     * @param \Cake\Http\Middleware\HostHeaderMiddleware $middleware The middleware to process.
     * @param \Psr\Http\Message\ServerRequestInterface $request The request to accept.
     */
    protected function assertHostAccepted(HostHeaderMiddleware $middleware, ServerRequestInterface $request): void
    {
        $response = new Response();
        $handler = Mockery::mock(RequestHandlerInterface::class);
        $handler->shouldReceive('handle')->once()->with($request)->andReturn($response);

        $this->assertSame($response, $middleware->process($request, $handler));
    }

    /**
     * Assert that a rejected request never reaches the handler.
     *
     * @param \Cake\Http\Middleware\HostHeaderMiddleware $middleware The middleware to process.
     * @param \Psr\Http\Message\ServerRequestInterface $request The request to reject.
     * @param class-string<\Cake\Http\Exception\HttpException> $exception The expected exception class.
     */
    protected function assertHostRejected(
        HostHeaderMiddleware $middleware,
        ServerRequestInterface $request,
        string $exception = BadRequestException::class,
    ): void {
        $handler = Mockery::mock(RequestHandlerInterface::class);
        $handler->shouldNotReceive('handle');

        $this->expectException($exception);
        $this->expectExceptionCode($exception === BadRequestException::class ? 400 : 500);

        $middleware->process($request, $handler);
    }
}
