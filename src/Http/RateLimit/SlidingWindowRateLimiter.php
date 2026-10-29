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
namespace Cake\Http\RateLimit;

use Psr\SimpleCache\CacheInterface;

/**
 * Sliding window rate limiter implementation
 *
 * Counts requests per fixed window and estimates the sliding window by adding
 * the previous window's count, weighted by how much of it still overlaps.
 */
class SlidingWindowRateLimiter implements RateLimiterInterface
{
    /**
     * Cache instance
     *
     * @var \Psr\SimpleCache\CacheInterface
     */
    protected CacheInterface $cache;

    /**
     * Constructor
     *
     * @param \Psr\SimpleCache\CacheInterface $cache Cache instance
     */
    public function __construct(CacheInterface $cache)
    {
        $this->cache = $cache;
    }

    /**
     * @inheritDoc
     */
    public function attempt(string $identifier, int $limit, int $window, int $cost = 1): array
    {
        $now = time();

        $data = $this->cache->get($identifier);
        $windowStart = (int)($data['window_start'] ?? $now);
        // Entries written by older versions only have `count`.
        $current = (int)($data['current'] ?? $data['count'] ?? 0);
        $previous = (int)($data['previous'] ?? 0);

        $elapsed = $now - $windowStart;
        if ($elapsed >= $window) {
            $previous = $elapsed < 2 * $window ? $current : 0;
            $current = 0;
            $windowStart += intdiv($elapsed, $window) * $window;
            $elapsed = $now - $windowStart;
        }

        $used = $current + intdiv($previous * ($window - $elapsed) + $window - 1, $window);
        $allowed = $used + $cost <= $limit;
        $reset = $windowStart + $window;

        if (!$allowed) {
            $reset = $this->retryAt($windowStart, $window, $limit, $cost, $current, $previous);
        } else {
            $current += $cost;
            $used += $cost;
            $this->cache->set($identifier, [
                'window_start' => $windowStart,
                'current' => $current,
                'previous' => $previous,
            ], $windowStart + 2 * $window - $now);
        }

        return [
            'allowed' => $allowed,
            'limit' => $limit,
            'remaining' => max(0, $limit - $used),
            'reset' => $reset,
        ];
    }

    /**
     * Get the earliest timestamp at which a request with the given cost is allowed.
     *
     * @param int $windowStart Start of the current window
     * @param int $window The time window in seconds
     * @param int $limit The maximum number of requests
     * @param int $cost The cost of the request
     * @param int $current Count of the current window
     * @param int $previous Count of the previous window
     * @return int
     */
    protected function retryAt(int $windowStart, int $window, int $limit, int $cost, int $current, int $previous): int
    {
        $room = $limit - $cost - $current;
        if ($room >= 0 && $previous > 0) {
            return $windowStart + $window - intdiv($room * $window, $previous);
        }

        $room = $limit - $cost;
        if ($room < 0 || $current === 0) {
            return $windowStart + 2 * $window;
        }

        return $windowStart + 2 * $window - intdiv($room * $window, $current);
    }

    /**
     * @inheritDoc
     */
    public function reset(string $identifier): void
    {
        $this->cache->delete($identifier);
    }
}
