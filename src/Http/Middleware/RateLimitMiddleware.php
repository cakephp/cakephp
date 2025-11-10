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
namespace Cake\Http\Middleware;

use Cake\Cache\Cache;
use Cake\Http\Exception\TooManyRequestsException;
use Cake\Http\RateLimit\FixedWindowRateLimiter;
use Cake\Http\RateLimit\RateLimiterInterface;
use Cake\Http\RateLimit\SlidingWindowRateLimiter;
use Cake\Http\RateLimit\TokenBucketRateLimiter;
use Closure;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Rate limiting middleware
 *
 * Provides configurable rate limiting based on various identifiers.
 * Supports multiple strategies including sliding window, token bucket, and fixed window.
 */
class RateLimitMiddleware implements MiddlewareInterface
{
    /**
     * Default configuration
     *
     * - `skipCheck`: Closure|null to determine if rate limiting should be skipped
     * - `costCallback`: Closure|null to calculate request cost
     * - `identifierCallback`: Closure|null to generate custom identifier
     * - `limitCallback`: Closure|null to determine dynamic limits
     * - `keyGenerator`: Closure|null to generate cache keys
     * - `limiterResolver`: Closure|null to resolve named limiters
     *
     * @var array<string, mixed>
     */
    protected array $defaultConfig = [
        'limit' => 60,
        'window' => 60,
        'identifier' => 'ip',
        'strategy' => 'sliding_window',
        'cache' => 'default',
        'headers' => true,
        'message' => 'Rate limit exceeded. Please try again later.',
        'skipCheck' => null,
        'costCallback' => null,
        'identifierCallback' => null,
        'limitCallback' => null,
        'ipHeader' => 'x-forwarded-for',
        'includeRetryAfter' => true,
        'keyGenerator' => null,
        'tokenHeaders' => ['Authorization', 'X-API-Key'],
        'limiters' => [],
        'limiterResolver' => null,
    ];

    /**
     * Configuration
     *
     * @var array<string, mixed>
     */
    protected array $config;

    /**
     * Constructor
     *
     * @param array<string, mixed> $config Configuration options
     */
    public function __construct(array $config = [])
    {
        $this->config = $config + $this->defaultConfig;
    }

    /**
     * Process the request and add rate limiting
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request The request
     * @param \Psr\Http\Server\RequestHandlerInterface $handler The handler
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if ($this->shouldSkip($request)) {
            return $handler->handle($request);
        }

        $limiterConfig = $this->resolveLimiterConfig($request);
        $identifier = $this->getIdentifier($request);
        $limit = $limiterConfig['limit'] ?? $this->getLimit($request, $identifier);
        $window = $limiterConfig['window'] ?? $this->config['window'];
        $cost = $this->getCost($request);
        $key = $this->generateKey($identifier, $request);

        $rateLimiter = $this->getRateLimiter($limiterConfig['strategy'] ?? null);
        $result = $rateLimiter->attempt($key, $limit, $window, $cost);

        if (!$result['allowed']) {
            $message = $limiterConfig['message'] ?? $this->config['message'];
            $exception = new TooManyRequestsException($message);
            if ($this->config['includeRetryAfter'] && isset($result['reset'])) {
                $retryAfter = max(1, $result['reset'] - time());
                $exception->setHeader('Retry-After', (string)$retryAfter);
            }
            throw $exception;
        }

        $response = $handler->handle($request);

        if ($this->config['headers']) {
            return $this->addRateLimitHeaders($response, $result);
        }

        return $response;
    }

    /**
     * Resolve limiter configuration for the current request
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request The request
     * @return array<string, mixed>
     */
    protected function resolveLimiterConfig(ServerRequestInterface $request): array
    {
        $resolver = $this->config['limiterResolver'];
        if ($resolver instanceof Closure) {
            $name = $resolver($request);
            if ($name && isset($this->config['limiters'][$name])) {
                return $this->config['limiters'][$name];
            }
        }

        $params = $request->getAttribute('params', []);
        if (isset($params['_rateLimiter']) && isset($this->config['limiters'][$params['_rateLimiter']])) {
            return $this->config['limiters'][$params['_rateLimiter']];
        }

        return [];
    }

    /**
     * Check if rate limiting should be skipped for this request
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request The request
     * @return bool
     */
    protected function shouldSkip(ServerRequestInterface $request): bool
    {
        $skipCheck = $this->config['skipCheck'];
        if ($skipCheck instanceof Closure) {
            return (bool)$skipCheck($request);
        }

        return false;
    }

    /**
     * Get the identifier for rate limiting
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request The request
     * @return string
     */
    protected function getIdentifier(ServerRequestInterface $request): string
    {
        $callback = $this->config['identifierCallback'];
        if ($callback instanceof Closure) {
            return (string)$callback($request);
        }

        $identifier = $this->config['identifier'];

        if (is_array($identifier)) {
            $parts = [];
            foreach ($identifier as $type) {
                $parts[] = $this->getIdentifierByType($type, $request);
            }

            return implode('_', $parts);
        }

        return $this->getIdentifierByType($identifier, $request);
    }

    /**
     * Get identifier by type
     *
     * @param string $type The identifier type
     * @param \Psr\Http\Message\ServerRequestInterface $request The request
     * @return string
     */
    protected function getIdentifierByType(string $type, ServerRequestInterface $request): string
    {
        return match ($type) {
            'ip' => $this->getClientIp($request),
            'user' => $this->getUserIdentifier($request),
            'route' => $this->getRouteIdentifier($request),
            'api_key', 'token' => $this->getApiKeyIdentifier($request),
            default => $this->getClientIp($request),
        };
    }

    /**
     * Get client IP address
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request The request
     * @return string
     */
    protected function getClientIp(ServerRequestInterface $request): string
    {
        $params = $request->getServerParams();

        if (is_array($this->config['ipHeader'])) {
            foreach ($this->config['ipHeader'] as $header) {
                $headerKey = 'HTTP_' . strtoupper(str_replace('-', '_', $header));
                if (!empty($params[$headerKey])) {
                    $ips = explode(',', $params[$headerKey]);

                    return trim($ips[0]);
                }
            }
        } elseif (is_string($this->config['ipHeader'])) {
            $headerKey = 'HTTP_' . strtoupper(str_replace('-', '_', $this->config['ipHeader']));
            if (!empty($params[$headerKey])) {
                $ips = explode(',', $params[$headerKey]);

                return trim($ips[0]);
            }
        }

        return $params['REMOTE_ADDR'] ?? 'unknown';
    }

    /**
     * Get user identifier
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request The request
     * @return string
     */
    protected function getUserIdentifier(ServerRequestInterface $request): string
    {
        /** @var object|null $user */
        $user = $request->getAttribute('identity');
        if ($user) {
            if (method_exists($user, 'getIdentifier')) {
                return 'user_' . $user->getIdentifier();
            }
            if (isset($user->id)) {
                return 'user_' . $user->id;
            }
        }

        return $this->getClientIp($request);
    }

    /**
     * Get route identifier
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request The request
     * @return string
     */
    protected function getRouteIdentifier(ServerRequestInterface $request): string
    {
        $params = $request->getAttribute('params', []);
        $route = sprintf(
            '%s::%s.%s',
            $params['plugin'] ?? 'app',
            $params['controller'] ?? 'unknown',
            $params['action'] ?? 'unknown',
        );

        return $route . '_' . $this->getClientIp($request);
    }

    /**
     * Generate cache key for rate limiting
     *
     * @param string $identifier The identifier
     * @param \Psr\Http\Message\ServerRequestInterface $request The request
     * @return string
     */
    protected function generateKey(string $identifier, ServerRequestInterface $request): string
    {
        $generator = $this->config['keyGenerator'];
        if ($generator instanceof Closure) {
            return (string)$generator($identifier, $request);
        }

        return 'rate_limit_' . hash('xxh3', $identifier);
    }

    /**
     * Get API key/token identifier
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request The request
     * @return string
     */
    protected function getApiKeyIdentifier(ServerRequestInterface $request): string
    {
        foreach ($this->config['tokenHeaders'] as $header) {
            $value = $request->getHeaderLine($header);
            if ($value) {
                if ($header === 'Authorization') {
                    $parts = explode(' ', $value, 2);
                    if (count($parts) === 2) {
                        $scheme = strtolower($parts[0]);
                        $token = $parts[1];

                        return sprintf('%s_%s', $scheme, hash('xxh3', $token));
                    }
                }

                return 'token_' . hash('xxh3', $value);
            }
        }

        return $this->getClientIp($request);
    }

    /**
     * Get rate limit for the request
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request The request
     * @param string $identifier The identifier
     * @return int
     */
    protected function getLimit(ServerRequestInterface $request, string $identifier): int
    {
        $callback = $this->config['limitCallback'];
        if ($callback instanceof Closure) {
            return (int)$callback($request, $identifier);
        }

        return (int)$this->config['limit'];
    }

    /**
     * Get the cost of the request
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request The request
     * @return int
     */
    protected function getCost(ServerRequestInterface $request): int
    {
        $callback = $this->config['costCallback'];
        if ($callback instanceof Closure) {
            return (int)$callback($request);
        }

        return 1;
    }

    /**
     * Get rate limiter instance based on strategy
     *
     * @param string|null $strategy Optional strategy override
     * @return \Cake\Http\RateLimit\RateLimiterInterface
     */
    protected function getRateLimiter(?string $strategy = null): RateLimiterInterface
    {
        $strategy ??= $this->config['strategy'];
        $cache = Cache::pool($this->config['cache']);

        return match ($strategy) {
            'token_bucket' => new TokenBucketRateLimiter($cache),
            'fixed_window' => new FixedWindowRateLimiter($cache),
            'sliding_window' => new SlidingWindowRateLimiter($cache),
            default => new SlidingWindowRateLimiter($cache),
        };
    }

    /**
     * Add rate limit headers to response
     *
     * @param \Psr\Http\Message\ResponseInterface $response The response
     * @param array<string, mixed> $result Rate limit result
     * @return \Psr\Http\Message\ResponseInterface
     */
    protected function addRateLimitHeaders(ResponseInterface $response, array $result): ResponseInterface
    {
        return $response
            ->withHeader('X-RateLimit-Limit', (string)$result['limit'])
            ->withHeader('X-RateLimit-Remaining', (string)$result['remaining'])
            ->withHeader('X-RateLimit-Reset', (string)$result['reset'])
            ->withHeader('X-RateLimit-Reset-Date', date('c', $result['reset']));
    }
}
