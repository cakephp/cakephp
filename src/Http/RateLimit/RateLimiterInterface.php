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

/**
 * Rate limiter interface
 */
interface RateLimiterInterface
{
    /**
     * Attempt to consume from the rate limit
     *
     * @param string $identifier The identifier to rate limit
     * @param int $limit The maximum number of requests
     * @param int $window The time window in seconds
     * @param int $cost The cost of this request
     * @return array{allowed: bool, limit: int, remaining: int, reset: int}
     */
    public function attempt(string $identifier, int $limit, int $window, int $cost = 1): array;

    /**
     * Reset rate limit for an identifier
     *
     * @param string $identifier The identifier to reset
     * @return void
     */
    public function reset(string $identifier): void;
}
