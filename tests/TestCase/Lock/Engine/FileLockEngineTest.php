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
use Cake\Lock\Engine\FileLockEngine;
use Cake\TestSuite\TestCase;

/**
 * FileLockEngineTest class
 */
class FileLockEngineTest extends TestCase
{
    /**
     * @var \Cake\Lock\Engine\FileLockEngine
     */
    protected FileLockEngine $engine;

    /**
     * @var string
     */
    protected string $lockPath;

    /**
     * setUp method
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->lockPath = TMP . 'lock_test_' . uniqid();
        $this->engine = new FileLockEngine();
        $this->engine->init(['path' => $this->lockPath]);
    }

    /**
     * tearDown method
     */
    protected function tearDown(): void
    {
        parent::tearDown();
        // Clean up lock files
        if (is_dir($this->lockPath)) {
            $files = glob($this->lockPath . '/*');
            if ($files) {
                foreach ($files as $file) {
                    @unlink($file);
                }
            }
            @rmdir($this->lockPath);
        }
    }

    /**
     * Test init() creates lock directory
     */
    public function testInitCreatesDirectory(): void
    {
        $this->assertDirectoryExists($this->lockPath);
    }

    /**
     * Test acquire() returns AcquiredLock
     */
    public function testAcquire(): void
    {
        $lock = $this->engine->acquire('test-resource', 60);

        $this->assertInstanceOf(AcquiredLock::class, $lock);
        $this->assertSame('test-resource', $lock->getResource());
        $this->assertSame(60, $lock->getTtl());
    }

    /**
     * Test acquire() creates lock file
     */
    public function testAcquireCreatesLockFile(): void
    {
        $lock = $this->engine->acquire('test-resource', 60);
        $this->assertInstanceOf(AcquiredLock::class, $lock);

        $files = glob($this->lockPath . '/*.lock');
        $this->assertNotEmpty($files);
    }

    /**
     * Test acquire() fails when resource is already locked
     */
    public function testAcquireFailsWhenLocked(): void
    {
        $lock1 = $this->engine->acquire('test-resource', 60);
        $this->assertInstanceOf(AcquiredLock::class, $lock1);

        // Second acquire should fail
        $lock2 = $this->engine->acquire('test-resource', 60);
        $this->assertNull($lock2);
    }

    /**
     * Test release() works correctly
     */
    public function testRelease(): void
    {
        $lock = $this->engine->acquire('test-resource', 60);
        $this->assertInstanceOf(AcquiredLock::class, $lock);

        $result = $this->engine->release($lock);
        $this->assertTrue($result);

        // Should be able to acquire again
        $lock2 = $this->engine->acquire('test-resource', 60);
        $this->assertInstanceOf(AcquiredLock::class, $lock2);
    }

    /**
     * Test acquired lock destructor releases the resource.
     */
    public function testAcquiredLockDestructorReleasesResource(): void
    {
        $lock = $this->engine->acquire('test-resource', 60);
        $this->assertInstanceOf(AcquiredLock::class, $lock);
        $this->assertTrue($this->engine->isLocked('test-resource'));

        unset($lock);
        gc_collect_cycles();

        $this->assertFalse($this->engine->isLocked('test-resource'));
    }

    /**
     * Test release() fails with wrong token
     */
    public function testReleaseFailsWithWrongToken(): void
    {
        $lock = $this->engine->acquire('test-resource', 60);
        $this->assertInstanceOf(AcquiredLock::class, $lock);

        // Create a fake lock with different token
        $fakeLock = new AcquiredLock('test-resource', 'wrong-token', 60, microtime(true));

        $result = $this->engine->release($fakeLock);
        $this->assertFalse($result);
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
     * Test refresh() updates lock metadata
     */
    public function testRefresh(): void
    {
        $lock = $this->engine->acquire('test-resource', 60);

        $result = $this->engine->refresh($lock, 120);
        $this->assertTrue($result);
    }

    /**
     * Test forceRelease() works without ownership
     */
    public function testForceRelease(): void
    {
        $lock = $this->engine->acquire('test-resource', 60);
        $this->assertInstanceOf(AcquiredLock::class, $lock);
        $this->assertTrue($this->engine->isLocked('test-resource'));

        // Force release ignores ownership
        $result = $this->engine->forceRelease('test-resource');
        $this->assertTrue($result);

        // Resource should be available
        $lock2 = $this->engine->acquire('test-resource', 60);
        $this->assertInstanceOf(AcquiredLock::class, $lock2);
    }

    /**
     * Test acquireBlocking() waits for lock
     */
    public function testAcquireBlocking(): void
    {
        $lock = $this->engine->acquireBlocking('test-resource', 60, 5, 100);

        $this->assertInstanceOf(AcquiredLock::class, $lock);
    }

    /**
     * Test acquireBlocking() times out
     */
    public function testAcquireBlockingTimeout(): void
    {
        // Acquire lock first
        $lock1 = $this->engine->acquire('test-resource', 60);
        $this->assertInstanceOf(AcquiredLock::class, $lock1);

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
     * Test stale lock cleanup
     */
    public function testStaleLockCleanup(): void
    {
        // Manually create a stale lock file
        $lockFile = $this->lockPath . '/lock_stale-resource.lock';
        file_put_contents($lockFile, json_encode([
            'token' => 'old-token',
            'ttl' => 1,
            'acquired_at' => microtime(true) - 100,
        ]));
        // Set old modification time
        touch($lockFile, time() - 100);

        // Acquire should succeed and clean up stale lock
        $lock = $this->engine->acquire('stale-resource', 1);
        $this->assertInstanceOf(AcquiredLock::class, $lock);
    }
}
