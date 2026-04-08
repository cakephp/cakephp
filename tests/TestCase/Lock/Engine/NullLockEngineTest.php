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
namespace Cake\Test\TestCase\Lock\Engine;

use Cake\Lock\AcquiredLock;
use Cake\Lock\Engine\NullLockEngine;
use Cake\TestSuite\TestCase;

/**
 * NullLockEngineTest class
 */
class NullLockEngineTest extends TestCase
{
    /**
     * @var \Cake\Lock\Engine\NullLockEngine
     */
    protected NullLockEngine $engine;

    /**
     * setUp method
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->engine = new NullLockEngine();
        $this->engine->init();
    }

    /**
     * Test acquire() always succeeds
     */
    public function testAcquireAlwaysSucceeds(): void
    {
        $lock = $this->engine->acquire('test-resource', 60);

        $this->assertInstanceOf(AcquiredLock::class, $lock);
        $this->assertSame('test-resource', $lock->getResource());
        $this->assertSame(60, $lock->getTtl());
    }

    /**
     * Test acquire() can be called multiple times for same resource
     */
    public function testAcquireMultipleTimes(): void
    {
        $lock1 = $this->engine->acquire('test-resource', 60);
        $lock2 = $this->engine->acquire('test-resource', 60);

        $this->assertInstanceOf(AcquiredLock::class, $lock1);
        $this->assertInstanceOf(AcquiredLock::class, $lock2);
        // Tokens should be different
        $this->assertNotSame($lock1->getToken(), $lock2->getToken());
    }

    /**
     * Test release() always returns true
     */
    public function testReleaseAlwaysSucceeds(): void
    {
        $lock = $this->engine->acquire('test-resource', 60);

        $this->assertTrue($this->engine->release($lock));
    }

    /**
     * Test isLocked() always returns false
     */
    public function testIsLockedAlwaysReturnsFalse(): void
    {
        $this->assertFalse($this->engine->isLocked('test-resource'));

        $this->engine->acquire('test-resource', 60);
        $this->assertFalse($this->engine->isLocked('test-resource'));
    }

    /**
     * Test refresh() always returns true
     */
    public function testRefreshAlwaysSucceeds(): void
    {
        $lock = $this->engine->acquire('test-resource', 60);

        $this->assertTrue($this->engine->refresh($lock, 120));
        $this->assertTrue($this->engine->refresh($lock));
    }

    /**
     * Test forceRelease() always returns true
     */
    public function testForceReleaseAlwaysSucceeds(): void
    {
        $this->assertTrue($this->engine->forceRelease('test-resource'));

        $this->engine->acquire('test-resource', 60);
        $this->assertTrue($this->engine->forceRelease('test-resource'));
    }

    /**
     * Test acquireBlocking() returns immediately
     */
    public function testAcquireBlockingReturnsImmediately(): void
    {
        $start = microtime(true);
        $lock = $this->engine->acquireBlocking('test-resource', 60, 10);
        $elapsed = microtime(true) - $start;

        $this->assertInstanceOf(AcquiredLock::class, $lock);
        // Should return almost immediately (within 100ms)
        $this->assertLessThan(0.1, $elapsed);
    }
}
