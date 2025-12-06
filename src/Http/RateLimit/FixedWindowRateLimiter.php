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
 * Fixed window rate limiter implementation
 */
class FixedWindowRateLimiter implements RateLimiterInterface
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
        $windowStart = (int)($now / $window) * $window;
        $key = $identifier . '_' . $windowStart;

        $count = (int)$this->cache->get($key, 0);
        $allowed = $count + $cost <= $limit;

        if ($allowed) {
            $count += $cost;
            $ttl = $windowStart + $window - $now;
            $this->cache->set($key, $count, $ttl);
        }

        return [
            'allowed' => $allowed,
            'limit' => $limit,
            'remaining' => max(0, $limit - $count),
            'reset' => $windowStart + $window,
        ];
    }

    /**
     * @inheritDoc
     */
    public function reset(string $identifier): void
    {
        $now = time();
        $window = 3600; // Assume max window of 1 hour for reset
        $windowStart = (int)($now / $window) * $window;
        $this->cache->delete($identifier . '_' . $windowStart);
    }
}
