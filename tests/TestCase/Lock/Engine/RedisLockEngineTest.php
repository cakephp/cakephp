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
namespace Cake\Test\TestCase\Lock\Engine;

use Cake\Lock\Engine\RedisLockEngine;
use Cake\Lock\LockInstance;
use Cake\TestSuite\TestCase;
use function Cake\Core\env;

/**
 * RedisLockEngineTest class
 */
class RedisLockEngineTest extends TestCase
{
    /**
     * @var \Cake\Lock\Engine\RedisLockEngine|null
     */
    protected ?RedisLockEngine $engine = null;

    /**
     * setUp method
     */
    protected function setUp(): void
    {
        parent::setUp();

        if (!extension_loaded('redis')) {
            $this->markTestSkipped('Redis extension is not loaded.');
        }

        $this->engine = new RedisLockEngine();
        $result = $this->engine->init([
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'port' => (int)env('REDIS_PORT', 6379),
            'prefix' => 'test_lock_',
            'database' => 15, // Use database 15 for tests
        ]);

        if (!$result) {
            $this->markTestSkipped('Could not connect to Redis server.');
        }

        // Clean up any existing test locks
        $this->cleanupTestLocks();
    }

    /**
     * tearDown method
     */
    protected function tearDown(): void
    {
        parent::tearDown();
        if ($this->engine !== null) {
            $this->cleanupTestLocks();
        }
    }

    /**
     * Clean up test locks
     */
    protected function cleanupTestLocks(): void
    {
        $redis = $this->engine->getRedis();
        $keys = $redis->keys('test_lock_*');
        if (!empty($keys)) {
            $redis->del($keys);
        }
    }

    /**
     * Test acquire() returns LockInstance
     */
    public function testAcquire(): void
    {
        $lock = $this->engine->acquire('test-resource', 60);

        $this->assertInstanceOf(LockInstance::class, $lock);
        $this->assertSame('test-resource', $lock->getResource());
        $this->assertSame(60, $lock->getTtl());
        $this->assertNotEmpty($lock->getToken());
    }

    /**
     * Test acquire() fails when resource is already locked
     */
    public function testAcquireFailsWhenLocked(): void
    {
        $lock1 = $this->engine->acquire('test-resource', 60);
        $this->assertInstanceOf(LockInstance::class, $lock1);

        $lock2 = $this->engine->acquire('test-resource', 60);
        $this->assertNull($lock2);
    }

    /**
     * Test release() works correctly
     */
    public function testRelease(): void
    {
        $lock = $this->engine->acquire('test-resource', 60);
        $this->assertInstanceOf(LockInstance::class, $lock);

        $result = $this->engine->release($lock);
        $this->assertTrue($result);

        // Should be able to acquire again
        $lock2 = $this->engine->acquire('test-resource', 60);
        $this->assertInstanceOf(LockInstance::class, $lock2);
    }

    /**
     * Test release() fails with wrong token
     */
    public function testReleaseFailsWithWrongToken(): void
    {
        $lock = $this->engine->acquire('test-resource', 60);
        $this->assertInstanceOf(LockInstance::class, $lock);

        // Create a fake lock with different token
        $fakeLock = new LockInstance('test-resource', 'wrong-token', 60, microtime(true));

        $result = $this->engine->release($fakeLock);
        $this->assertFalse($result);

        // Original lock should still be held
        $this->assertTrue($this->engine->isLocked('test-resource'));
    }

    /**
     * Test isLocked() returns correct status
     */
    public function testIsLocked(): void
    {
        $this->assertFalse($this->engine->isLocked('test-resource'));

        $lock = $this->engine->acquire('test-resource', 60);
        $this->assertTrue($this->engine->isLocked('test-resource'));

        $this->engine->release($lock);
        $this->assertFalse($this->engine->isLocked('test-resource'));
    }

    /**
     * Test refresh() extends TTL
     */
    public function testRefresh(): void
    {
        $lock = $this->engine->acquire('test-resource', 10);
        $this->assertInstanceOf(LockInstance::class, $lock);

        $result = $this->engine->refresh($lock, 120);
        $this->assertTrue($result);

        // Verify TTL was extended
        $redis = $this->engine->getRedis();
        $ttl = $redis->ttl('test_lock_test-resource');
        $this->assertGreaterThan(100, $ttl);
    }

    /**
     * Test refresh() fails with wrong token
     */
    public function testRefreshFailsWithWrongToken(): void
    {
        $lock = $this->engine->acquire('test-resource', 60);
        $this->assertInstanceOf(LockInstance::class, $lock);

        // Create a fake lock with different token
        $fakeLock = new LockInstance('test-resource', 'wrong-token', 60, microtime(true));

        $result = $this->engine->refresh($fakeLock, 120);
        $this->assertFalse($result);
    }

    /**
     * Test forceRelease() works without ownership
     */
    public function testForceRelease(): void
    {
        $this->engine->acquire('test-resource', 60);
        $this->assertTrue($this->engine->isLocked('test-resource'));

        $result = $this->engine->forceRelease('test-resource');
        $this->assertTrue($result);

        $this->assertFalse($this->engine->isLocked('test-resource'));
    }

    /**
     * Test acquireBlocking() waits for lock
     */
    public function testAcquireBlocking(): void
    {
        $lock = $this->engine->acquireBlocking('test-resource', 60, 5, 100);

        $this->assertInstanceOf(LockInstance::class, $lock);
    }

    /**
     * Test acquireBlocking() times out
     */
    public function testAcquireBlockingTimeout(): void
    {
        // Acquire lock first
        $lock1 = $this->engine->acquire('test-resource', 60);
        $this->assertInstanceOf(LockInstance::class, $lock1);

        // Try to acquire with short timeout
        $start = microtime(true);
        $lock2 = $this->engine->acquireBlocking('test-resource', 60, 1, 100);
        $elapsed = microtime(true) - $start;

        $this->assertNull($lock2);
        // Should have waited approximately 1 second
        $this->assertGreaterThan(0.9, $elapsed);
        $this->assertLessThan(1.5, $elapsed);
    }

    /**
     * Test lock expires automatically after TTL
     */
    public function testLockExpiresAfterTtl(): void
    {
        $lock = $this->engine->acquire('test-resource', 1);
        $this->assertInstanceOf(LockInstance::class, $lock);
        $this->assertTrue($this->engine->isLocked('test-resource'));

        // Wait for lock to expire
        sleep(2);

        $this->assertFalse($this->engine->isLocked('test-resource'));

        // Should be able to acquire again
        $lock2 = $this->engine->acquire('test-resource', 60);
        $this->assertInstanceOf(LockInstance::class, $lock2);
    }

    /**
     * Test concurrent locks on different resources
     */
    public function testConcurrentLocksOnDifferentResources(): void
    {
        $lock1 = $this->engine->acquire('resource-1', 60);
        $lock2 = $this->engine->acquire('resource-2', 60);
        $lock3 = $this->engine->acquire('resource-3', 60);

        $this->assertInstanceOf(LockInstance::class, $lock1);
        $this->assertInstanceOf(LockInstance::class, $lock2);
        $this->assertInstanceOf(LockInstance::class, $lock3);

        $this->assertTrue($this->engine->release($lock1));
        $this->assertTrue($this->engine->release($lock2));
        $this->assertTrue($this->engine->release($lock3));
    }
}
