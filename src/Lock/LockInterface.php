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
 * @since         5.4.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Lock;

/**
 * Interface for lock engines.
 *
 * Lock engines provide distributed locking mechanisms to prevent
 * race conditions when multiple processes access shared resources.
 */
interface LockInterface
{
    /**
     * Acquire a lock for the given resource.
     *
     * @param string $resource The resource identifier to lock.
     * @param int $ttl Time-to-live in seconds. The lock will automatically
     *   expire after this duration to prevent deadlocks.
     * @return \Cake\Lock\AcquiredLock|null Returns an AcquiredLock on success, null on failure.
     */
    public function acquire(string $resource, int $ttl = 300): ?AcquiredLock;

    /**
     * Acquire a lock, waiting up to $timeout seconds if necessary.
     *
     * This method will block until the lock is acquired or the timeout is reached.
     *
     * @param string $resource The resource identifier to lock.
     * @param int $ttl Time-to-live in seconds for the lock.
     * @param int $timeout Maximum time in seconds to wait for the lock.
     * @param int $retryInterval Milliseconds to wait between retry attempts.
     * @return \Cake\Lock\AcquiredLock|null Returns an AcquiredLock on success, null on timeout.
     */
    public function acquireBlocking(
        string $resource,
        int $ttl = 300,
        int $timeout = 10,
        int $retryInterval = 100,
    ): ?AcquiredLock;

    /**
     * Release a lock.
     *
     * @param \Cake\Lock\AcquiredLock $lock The lock instance to release.
     * @return bool True if the lock was released, false otherwise.
     */
    public function release(AcquiredLock $lock): bool;

    /**
     * Check if a resource is currently locked.
     *
     * Note: This is a point-in-time check and the lock status
     * may change immediately after this method returns.
     *
     * @param string $resource The resource identifier to check.
     * @return bool True if the resource is locked, false otherwise.
     */
    public function isLocked(string $resource): bool;

    /**
     * Refresh a lock's TTL to extend its duration.
     *
     * This is useful for long-running operations that need to
     * hold a lock longer than initially anticipated.
     *
     * @param \Cake\Lock\AcquiredLock $lock The lock instance to refresh.
     * @param int|null $ttl New TTL in seconds. If null, uses the original TTL.
     * @return bool True if the lock was refreshed, false otherwise.
     */
    public function refresh(AcquiredLock $lock, ?int $ttl = null): bool;

    /**
     * Force release a lock without ownership verification.
     *
     * WARNING: This should only be used for administrative purposes
     * as it bypasses owner verification and may cause race conditions.
     *
     * @param string $resource The resource identifier to force release.
     * @return bool True if the lock was released, false otherwise.
     */
    public function forceRelease(string $resource): bool;
}
