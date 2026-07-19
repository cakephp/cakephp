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
namespace Cake\Lock\Engine;

use Cake\Lock\AcquiredLock;
use Cake\Lock\LockEngine;

/**
 * Null lock engine that always succeeds.
 *
 * This engine is useful for:
 * - Testing without actual locking
 * - Development environments where locking isn't needed
 * - Fallback when the primary lock engine fails
 *
 * WARNING: This engine provides NO actual locking. Do not use
 * in production where concurrent access protection is required.
 */
class NullLockEngine extends LockEngine
{
    /**
     * Acquire a lock for the given resource.
     *
     * Always succeeds immediately.
     *
     * @param string $resource The resource identifier to lock.
     * @param int $ttl Time-to-live in seconds.
     * @return \Cake\Lock\AcquiredLock Always returns an AcquiredLock.
     */
    public function acquire(string $resource, int $ttl = 300): ?AcquiredLock
    {
        return new AcquiredLock($resource, $this->generateToken(), $ttl, microtime(true), $this);
    }

    /**
     * Release a lock.
     *
     * Always succeeds.
     *
     * @param \Cake\Lock\AcquiredLock $lock The lock instance to release.
     * @return bool Always returns true.
     */
    public function release(AcquiredLock $lock): bool
    {
        return true;
    }

    /**
     * Check if a resource is currently locked.
     *
     * Always returns false (nothing is ever locked).
     *
     * @param string $resource The resource identifier to check.
     * @return bool Always returns false.
     */
    public function isLocked(string $resource): bool
    {
        return false;
    }

    /**
     * Refresh a lock's TTL.
     *
     * Always succeeds.
     *
     * @param \Cake\Lock\AcquiredLock $lock The lock instance to refresh.
     * @param int|null $ttl New TTL in seconds.
     * @return bool Always returns true.
     */
    public function refresh(AcquiredLock $lock, ?int $ttl = null): bool
    {
        return true;
    }

    /**
     * Force release a lock.
     *
     * Always succeeds.
     *
     * @param string $resource The resource identifier to force release.
     * @return bool Always returns true.
     */
    public function forceRelease(string $resource): bool
    {
        return true;
    }
}
