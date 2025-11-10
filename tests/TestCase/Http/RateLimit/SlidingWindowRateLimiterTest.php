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
namespace Cake\Test\TestCase\Http\RateLimit;

use Cake\Cache\Cache;
use Cake\Http\RateLimit\SlidingWindowRateLimiter;
use Cake\TestSuite\TestCase;

/**
 * SlidingWindowRateLimiter test case
 */
class SlidingWindowRateLimiterTest extends TestCase
{
    /**
     * @var \Cake\Http\RateLimit\SlidingWindowRateLimiter
     */
    protected $limiter;

    /**
     * @var \Psr\SimpleCache\CacheInterface
     */
    protected $cache;

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        Cache::setConfig('rate_limiter_test', [
            'className' => 'Array',
        ]);

        $this->cache = Cache::pool('rate_limiter_test');
        $this->limiter = new SlidingWindowRateLimiter($this->cache);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown(): void
    {
        parent::tearDown();
        Cache::drop('rate_limiter_test');
    }

    /**
     * Test basic rate limiting
     *
     * @return void
     */
    public function testBasicAttempt(): void
    {
        $result = $this->limiter->attempt('test_user', 5, 60);

        $this->assertTrue($result['allowed']);
        $this->assertEquals(5, $result['limit']);
        $this->assertEquals(4, $result['remaining']);
        $this->assertGreaterThan(time(), $result['reset']);
    }

    /**
     * Test rate limit exceeded
     *
     * @return void
     */
    public function testRateLimitExceeded(): void
    {
        $identifier = 'test_user_limit';
        $limit = 3;
        $window = 60;

        // Use up the limit
        for ($i = 0; $i < $limit; $i++) {
            $result = $this->limiter->attempt($identifier, $limit, $window);
            $this->assertTrue($result['allowed']);
            $this->assertEquals($limit - $i - 1, $result['remaining']);
        }

        // Next attempt should fail
        $result = $this->limiter->attempt($identifier, $limit, $window);
        $this->assertFalse($result['allowed']);
        $this->assertEquals(0, $result['remaining']);
    }

    /**
     * Test different identifiers have separate limits
     *
     * @return void
     */
    public function testSeparateIdentifiers(): void
    {
        $result1 = $this->limiter->attempt('user1', 1, 60);
        $this->assertTrue($result1['allowed']);
        $this->assertEquals(0, $result1['remaining']);

        $result2 = $this->limiter->attempt('user2', 1, 60);
        $this->assertTrue($result2['allowed']);
        $this->assertEquals(0, $result2['remaining']);

        // First user should be blocked
        $result3 = $this->limiter->attempt('user1', 1, 60);
        $this->assertFalse($result3['allowed']);

        // Second user should still work
        $result4 = $this->limiter->attempt('user2', 1, 60);
        $this->assertFalse($result4['allowed']);
    }

    /**
     * Test cost parameter
     *
     * @return void
     */
    public function testCost(): void
    {
        $identifier = 'test_cost';
        $limit = 10;
        $window = 60;

        // First request with cost 3
        $result = $this->limiter->attempt($identifier, $limit, $window, 3);
        $this->assertTrue($result['allowed']);
        $this->assertEquals(7, $result['remaining']);

        // Second request with cost 5
        $result = $this->limiter->attempt($identifier, $limit, $window, 5);
        $this->assertTrue($result['allowed']);
        $this->assertEquals(2, $result['remaining']);

        // Third request with cost 3 should fail
        $result = $this->limiter->attempt($identifier, $limit, $window, 3);
        $this->assertFalse($result['allowed']);
        $this->assertEquals(2, $result['remaining']);

        // But cost 2 should work
        $result = $this->limiter->attempt($identifier, $limit, $window, 2);
        $this->assertTrue($result['allowed']);
        $this->assertEquals(0, $result['remaining']);
    }

    /**
     * Test reset functionality
     *
     * @return void
     */
    public function testReset(): void
    {
        $identifier = 'test_reset';
        $limit = 2;
        $window = 60;

        // Use up limit
        $this->limiter->attempt($identifier, $limit, $window);
        $this->limiter->attempt($identifier, $limit, $window);

        $result = $this->limiter->attempt($identifier, $limit, $window);
        $this->assertFalse($result['allowed']);

        // Reset the limit
        $this->limiter->reset($identifier);

        // Should be able to make requests again
        $result = $this->limiter->attempt($identifier, $limit, $window);
        $this->assertTrue($result['allowed']);
        $this->assertEquals(1, $result['remaining']);
    }

    /**
     * Test sliding window behavior
     *
     * @return void
     */
    public function testSlidingWindowDecay(): void
    {
        $identifier = 'test_sliding';
        $limit = 10;
        $window = 2; // 2 second window for testing

        // Make initial request
        $result = $this->limiter->attempt($identifier, $limit, $window, 5);
        $this->assertTrue($result['allowed']);
        $this->assertEquals(5, $result['remaining']);

        // Wait for half the window
        sleep(1);

        // The count should have decayed by ~50%
        $result = $this->limiter->attempt($identifier, $limit, $window, 1);
        $this->assertTrue($result['allowed']);
        // Due to decay, remaining should be more than 4
        $this->assertGreaterThan(4, $result['remaining']);
    }

    /**
     * Test window expiration
     *
     * @return void
     */
    public function testWindowExpiration(): void
    {
        $identifier = 'test_expiration';
        $limit = 1;
        $window = 1; // 1 second window

        // Use up the limit
        $result = $this->limiter->attempt($identifier, $limit, $window);
        $this->assertTrue($result['allowed']);

        // Should be blocked
        $result = $this->limiter->attempt($identifier, $limit, $window);
        $this->assertFalse($result['allowed']);

        // Wait for window to expire
        sleep(2);

        // Should be allowed again
        $result = $this->limiter->attempt($identifier, $limit, $window);
        $this->assertTrue($result['allowed']);
    }

    /**
     * Test reset time calculation
     *
     * @return void
     */
    public function testResetTime(): void
    {
        $identifier = 'test_reset_time';
        $limit = 5;
        $window = 60;
        $startTime = time();

        $result = $this->limiter->attempt($identifier, $limit, $window);

        $this->assertGreaterThanOrEqual($startTime + $window, $result['reset']);
        $this->assertLessThanOrEqual($startTime + $window + 1, $result['reset']);
    }
}
