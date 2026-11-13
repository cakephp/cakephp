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
 * @since         5.4.5
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Http\RateLimit;

use Cake\Cache\Cache;
use Cake\Http\RateLimit\TokenBucketRateLimiter;
use Cake\I18n\DateTime;
use Cake\TestSuite\TestCase;

/**
 * TokenBucketRateLimiter test case
 */
class TokenBucketRateLimiterTest extends TestCase
{
    /**
     * @var \Cake\Http\RateLimit\TokenBucketRateLimiter
     */
    protected $limiter;

    /**
     * @var \Cake\Chronos\Chronos|null
     */
    protected $testNow;

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->testNow = DateTime::getTestNow();
        Cache::setConfig('rate_limiter_test', [
            'className' => 'Array',
        ]);

        $this->limiter = new TokenBucketRateLimiter(Cache::pool('rate_limiter_test'));
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown(): void
    {
        parent::tearDown();
        DateTime::setTestNow($this->testNow);
        Cache::drop('rate_limiter_test');
    }

    /**
     * Test that tokens refill with the frozen clock, including fractions of a second
     *
     * @return void
     */
    public function testAttemptUsesTestNow(): void
    {
        $start = DateTime::createFromTimestamp(1800000000);
        DateTime::setTestNow($start);

        $this->assertTrue($this->limiter->attempt('test', 2, 10)['allowed']);
        $this->assertTrue($this->limiter->attempt('test', 2, 10)['allowed']);

        $result = $this->limiter->attempt('test', 2, 10);
        $this->assertFalse($result['allowed']);
        $this->assertSame(1800000010, $result['reset']);

        // One token refills every 5 seconds.
        DateTime::setTestNow($start->modify('+4900 milliseconds'));
        $this->assertFalse($this->limiter->attempt('test', 2, 10)['allowed']);

        DateTime::setTestNow($start->addSeconds(5));
        $this->assertTrue($this->limiter->attempt('test', 2, 10)['allowed']);
        $this->assertFalse($this->limiter->attempt('test', 2, 10)['allowed']);
    }
}
