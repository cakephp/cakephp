<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         5.2.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Lock;

use Cake\Lock\LockInstance;
use Cake\TestSuite\TestCase;

/**
 * LockInstanceTest class
 */
class LockInstanceTest extends TestCase
{
    /**
     * Test constructor and getters
     */
    public function testConstructorAndGetters(): void
    {
        $acquiredAt = microtime(true);
        $lock = new LockInstance('test-resource', 'test-token', 300, $acquiredAt);

        $this->assertSame('test-resource', $lock->getResource());
        $this->assertSame('test-token', $lock->getToken());
        $this->assertSame(300, $lock->getTtl());
        $this->assertSame($acquiredAt, $lock->getAcquiredAt());
    }

    /**
     * Test isExpired() returns false for fresh lock
     */
    public function testIsExpiredFalseForFreshLock(): void
    {
        $lock = new LockInstance('test-resource', 'test-token', 300, microtime(true));

        $this->assertFalse($lock->isExpired());
    }

    /**
     * Test isExpired() returns true for expired lock
     */
    public function testIsExpiredTrueForOldLock(): void
    {
        // Create a lock that was acquired 400 seconds ago with 300s TTL
        $lock = new LockInstance('test-resource', 'test-token', 300, microtime(true) - 400);

        $this->assertTrue($lock->isExpired());
    }

    /**
     * Test getRemainingTtl() returns positive value for fresh lock
     */
    public function testGetRemainingTtlPositive(): void
    {
        $lock = new LockInstance('test-resource', 'test-token', 300, microtime(true));

        $remaining = $lock->getRemainingTtl();
        $this->assertGreaterThan(299, $remaining);
        $this->assertLessThanOrEqual(300, $remaining);
    }

    /**
     * Test getRemainingTtl() returns negative value for expired lock
     */
    public function testGetRemainingTtlNegative(): void
    {
        // Create a lock that was acquired 400 seconds ago with 300s TTL
        $lock = new LockInstance('test-resource', 'test-token', 300, microtime(true) - 400);

        $remaining = $lock->getRemainingTtl();
        $this->assertLessThan(0, $remaining);
    }

    /**
     * Test immutability of LockInstance
     */
    public function testImmutability(): void
    {
        $acquiredAt = microtime(true);
        $lock = new LockInstance('test-resource', 'test-token', 300, $acquiredAt);

        // Properties should be readonly - this is enforced by PHP's readonly modifier
        // We just verify the values remain constant
        $this->assertSame('test-resource', $lock->getResource());
        $this->assertSame('test-token', $lock->getToken());
        $this->assertSame(300, $lock->getTtl());
        $this->assertSame($acquiredAt, $lock->getAcquiredAt());
    }
}
