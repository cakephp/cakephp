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
use Cake\Lock\Engine\NullLockEngine;
use Cake\Lock\Exception\InvalidArgumentException;
use Cake\Lock\Lock;
use Cake\Lock\LockRegistry;
use Cake\TestSuite\TestCase;

/**
 * LockTest class
 */
class LockTest extends TestCase
{
    /**
     * setUp method
     */
    protected function setUp(): void
    {
        parent::setUp();
        Lock::setRegistry(new LockRegistry());
    }

    /**
     * tearDown method
     */
    protected function tearDown(): void
    {
        parent::tearDown();
        Lock::setRegistry(new LockRegistry());
        Lock::drop('mock');
        Lock::drop('tests');
        Lock::drop('tests_file');
    }

    /**
     * Configure lock settings for test
     */
    protected function _configLock(): void
    {
        Lock::setConfig('tests', [
            'className' => NullLockEngine::class,
            'prefix' => 'test_',
        ]);
    }

    /**
     * Test that getRegistry returns a LockRegistry instance
     */
    public function testGetRegistry(): void
    {
        $registry = Lock::getRegistry();
        $this->assertInstanceOf(LockRegistry::class, $registry);
    }

    /**
     * Test setting custom registry
     */
    public function testSetRegistry(): void
    {
        $registry = new LockRegistry();
        Lock::setRegistry($registry);
        $this->assertSame($registry, Lock::getRegistry());
    }

    /**
     * Test engine() throws exception for missing config
     */
    public function testEngineThrowsExceptionForMissingConfig(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The `nonexistent` lock configuration does not exist.');
        Lock::engine('nonexistent');
    }

    /**
     * Test acquire() returns AcquiredLock
     */
    public function testAcquire(): void
    {
        $this->_configLock();
        $lock = Lock::acquire('test-resource', 60, 'tests');

        $this->assertInstanceOf(AcquiredLock::class, $lock);
        $this->assertSame('test-resource', $lock->getResource());
    }

    /**
     * Test release() returns true for valid lock
     */
    public function testRelease(): void
    {
        $this->_configLock();
        $lock = Lock::acquire('test-resource', 60, 'tests');

        $this->assertTrue(Lock::release($lock));
    }

    /**
     * Test acquired lock can release itself
     */
    public function testAcquiredLockCanReleaseItself(): void
    {
        $this->_configLock();
        $lock = Lock::acquire('test-resource', 60, 'tests');

        $this->assertTrue($lock->release());
    }

    /**
     * Test isLocked() returns correct status
     */
    public function testIsLocked(): void
    {
        $this->_configLock();

        // NullLockEngine always returns false for isLocked
        $this->assertFalse(Lock::isLocked('test-resource', 'tests'));
    }

    /**
     * Test refresh() returns true
     */
    public function testRefresh(): void
    {
        $this->_configLock();
        $lock = Lock::acquire('test-resource', 60, 'tests');

        $this->assertTrue(Lock::refresh($lock, 120));
    }

    /**
     * Test forceRelease() returns true
     */
    public function testForceRelease(): void
    {
        $this->_configLock();

        $this->assertTrue(Lock::forceRelease('test-resource', 'tests'));
    }

    /**
     * Test synchronized() executes callback and returns result
     */
    public function testSynchronized(): void
    {
        $this->_configLock();

        $result = Lock::synchronized('test-resource', function () {
            return 'callback-result';
        }, config: 'tests');

        $this->assertSame('callback-result', $result);
    }

    /**
     * Test synchronized() returns null when lock cannot be acquired
     */
    public function testSynchronizedReturnsNullOnFailure(): void
    {
        $mockEngine = $this->createStub(NullLockEngine::class);
        $mockEngine->method('acquireBlocking')->willReturn(null);

        $registry = new LockRegistry();
        $registry->set('mock', $mockEngine);
        Lock::setRegistry($registry);

        Lock::setConfig('mock', ['className' => NullLockEngine::class]);

        $result = Lock::synchronized('test-resource', function () {
            return 'should-not-execute';
        }, timeout: 0, config: 'mock');

        $this->assertNull($result);
    }

    /**
     * Test acquireBlocking() with timeout
     */
    public function testAcquireBlocking(): void
    {
        $this->_configLock();

        $lock = Lock::acquireBlocking('test-resource', 60, 5, 100, 'tests');

        $this->assertInstanceOf(AcquiredLock::class, $lock);
    }

    /**
     * Test configuration with DSN
     */
    public function testConfigWithDsn(): void
    {
        Lock::setConfig('tests_file', [
            'url' => 'file:///tmp/locks',
        ]);

        $config = Lock::getConfig('tests_file');
        $this->assertSame('file', $config['scheme']);
    }
}
