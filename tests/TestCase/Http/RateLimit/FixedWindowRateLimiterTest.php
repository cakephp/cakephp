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
use Cake\Http\RateLimit\FixedWindowRateLimiter;
use Cake\I18n\DateTime;
use Cake\TestSuite\TestCase;

/**
 * FixedWindowRateLimiter test case
 */
class FixedWindowRateLimiterTest extends TestCase
{
    /**
     * A timestamp that is a multiple of every window used here.
     */
    protected const BOUNDARY = 1800000000;

    /**
     * @var \Cake\Http\RateLimit\FixedWindowRateLimiter
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

        $this->limiter = new FixedWindowRateLimiter(Cache::pool('rate_limiter_test'));
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
     * Test that the window follows the frozen clock
     *
     * @return void
     */
    public function testAttemptUsesTestNow(): void
    {
        DateTime::setTestNow(DateTime::createFromTimestamp(static::BOUNDARY + 119));

        $this->assertTrue($this->limiter->attempt('test', 2, 120)['allowed']);
        $this->assertTrue($this->limiter->attempt('test', 2, 120)['allowed']);

        $result = $this->limiter->attempt('test', 2, 120);
        $this->assertFalse($result['allowed']);
        $this->assertSame(0, $result['remaining']);
        $this->assertSame(static::BOUNDARY + 120, $result['reset']);

        DateTime::setTestNow(DateTime::createFromTimestamp(static::BOUNDARY + 120));

        $result = $this->limiter->attempt('test', 2, 120);
        $this->assertTrue($result['allowed']);
        $this->assertSame(1, $result['remaining']);
        $this->assertSame(static::BOUNDARY + 240, $result['reset']);
    }

    /**
     * Test that reset() clears the window of the frozen clock
     *
     * @return void
     */
    public function testResetUsesTestNow(): void
    {
        DateTime::setTestNow(DateTime::createFromTimestamp(static::BOUNDARY + 10));

        $this->assertTrue($this->limiter->attempt('test', 1, 3600)['allowed']);
        $this->assertFalse($this->limiter->attempt('test', 1, 3600)['allowed']);

        $this->limiter->reset('test');

        $this->assertTrue($this->limiter->attempt('test', 1, 3600)['allowed']);
    }
}
