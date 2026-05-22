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
 * @since         5.4.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Lock;

use Cake\Lock\AcquiredLock;
use Cake\Lock\LockInterface;
use Cake\TestSuite\TestCase;

/**
 * AcquiredLockTest class
 */
class AcquiredLockTest extends TestCase
{
    /**
     * Test constructor and getters.
     */
    public function testConstructorAndGetters(): void
    {
        $acquiredAt = microtime(true);
        $lock = new AcquiredLock('test-resource', 'test-token', 300, $acquiredAt);

        $this->assertSame('test-resource', $lock->getResource());
        $this->assertSame('test-token', $lock->getToken());
        $this->assertSame(300, $lock->getTtl());
        $this->assertSame($acquiredAt, $lock->getAcquiredAt());
    }

    /**
     * Test isExpired() returns false for fresh lock.
     */
    public function testIsExpiredFalseForFreshLock(): void
    {
        $lock = new AcquiredLock('test-resource', 'test-token', 300, microtime(true));

        $this->assertFalse($lock->isExpired());
    }

    /**
     * Test isExpired() returns true for expired lock.
     */
    public function testIsExpiredTrueForOldLock(): void
    {
        // Create a lock that was acquired 400 seconds ago with 300s TTL
        $lock = new AcquiredLock('test-resource', 'test-token', 300, microtime(true) - 400);

        $this->assertTrue($lock->isExpired());
    }

    /**
     * Test getRemainingTtl() returns positive value for fresh lock.
     */
    public function testGetRemainingTtlPositive(): void
    {
        $lock = new AcquiredLock('test-resource', 'test-token', 300, microtime(true));

        $remaining = $lock->getRemainingTtl();
        $this->assertGreaterThan(299, $remaining);
        $this->assertLessThanOrEqual(300, $remaining);
    }

    /**
     * Test getRemainingTtl() returns negative value for expired lock.
     */
    public function testGetRemainingTtlNegative(): void
    {
        // Create a lock that was acquired 400 seconds ago with 300s TTL
        $lock = new AcquiredLock('test-resource', 'test-token', 300, microtime(true) - 400);

        $remaining = $lock->getRemainingTtl();
        $this->assertLessThan(0, $remaining);
    }

    /**
     * Test immutability of AcquiredLock.
     */
    public function testImmutability(): void
    {
        $acquiredAt = microtime(true);
        $lock = new AcquiredLock('test-resource', 'test-token', 300, $acquiredAt);

        $this->assertSame('test-resource', $lock->getResource());
        $this->assertSame('test-token', $lock->getToken());
        $this->assertSame(300, $lock->getTtl());
        $this->assertSame($acquiredAt, $lock->getAcquiredAt());
    }

    /**
     * Test release() delegates to the owning engine.
     */
    public function testRelease(): void
    {
        $engine = $this->createMock(LockInterface::class);
        $engine->expects($this->once())
            ->method('release')
            ->with($this->isInstanceOf(AcquiredLock::class))
            ->willReturn(true);

        $lock = new AcquiredLock('test-resource', 'test-token', 300, microtime(true), $engine);

        $this->assertTrue($lock->release());
        $this->assertTrue($lock->isReleased());
    }

    /**
     * Test refresh() delegates to the owning engine.
     */
    public function testRefresh(): void
    {
        $engine = $this->createMock(LockInterface::class);
        $engine->expects($this->once())
            ->method('refresh')
            ->with($this->isInstanceOf(AcquiredLock::class), 120)
            ->willReturn(true);
        $engine->expects($this->once())
            ->method('release')
            ->with($this->isInstanceOf(AcquiredLock::class))
            ->willReturn(true);

        $lock = new AcquiredLock('test-resource', 'test-token', 300, microtime(true), $engine);

        $this->assertTrue($lock->refresh(120));
        $this->assertTrue($lock->release());
    }

    /**
     * Test release() fails when no owning engine is present.
     */
    public function testReleaseWithoutEngine(): void
    {
        $lock = new AcquiredLock('test-resource', 'test-token', 300, microtime(true));

        $this->assertFalse($lock->release());
        $this->assertFalse($lock->isReleased());
    }

    /**
     * Test destruction triggers a best-effort release.
     */
    public function testDestructorReleasesLock(): void
    {
        $engine = $this->createMock(LockInterface::class);
        $engine->expects($this->once())
            ->method('release')
            ->with($this->isInstanceOf(AcquiredLock::class))
            ->willReturn(true);

        $lock = new AcquiredLock('test-resource', 'test-token', 300, microtime(true), $engine);

        unset($lock);
    }
}
