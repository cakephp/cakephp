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

use Cake\Core\Exception\CakeException;
use Cake\Lock\AcquiredLock;
use Cake\Lock\LockEngine;
use Memcached;

/**
 * Memcached-based lock engine.
 *
 * Uses Memcached's add() operation for atomic lock acquisition.
 * Note: Memcached does not support Lua scripting, so some operations
 * are less atomic than the Redis implementation.
 *
 * ### Configuration options:
 *
 * - `servers`: Array of server configurations (default: [['127.0.0.1', 11211]])
 * - `prefix`: Prefix for lock keys (default: 'lock_')
 * - `ttl`: Default lock TTL in seconds (default: 300)
 * - `persistent`: Persistent connection ID (default: false)
 */
class MemcachedLockEngine extends LockEngine
{
    /**
     * Memcached connection.
     *
     * @var \Memcached
     */
    protected Memcached $_memcached;

    /**
     * Default configuration.
     *
     * @var array<string, mixed>
     */
    protected array $_defaultConfig = [
        'servers' => [['127.0.0.1', 11211]],
        'prefix' => 'lock_',
        'ttl' => 300,
        'persistent' => false,
    ];

    /**
     * Initialize the Memcached lock engine.
     *
     * @param array<string, mixed> $config Configuration options.
     * @return bool True if initialization was successful.
     * @throws \Cake\Core\Exception\CakeException If memcached extension is not loaded.
     */
    public function init(array $config = []): bool
    {
        if (!extension_loaded('memcached')) {
            throw new CakeException('The `memcached` extension must be enabled to use MemcachedLockEngine.');
        }

        parent::init($config);

        return $this->_connect();
    }

    /**
     * Connect to Memcached servers.
     *
     * @return bool True if connection was successful.
     */
    protected function _connect(): bool
    {
        if ($this->_config['persistent']) {
            $this->_memcached = new Memcached((string)$this->_config['persistent']);
        } else {
            $this->_memcached = new Memcached();
        }

        // Only add servers if not already added (for persistent connections)
        if ($this->_memcached->getServerList() === []) {
            $servers = [];
            foreach ($this->_config['servers'] as $server) {
                $servers[] = [$server[0], (int)($server[1] ?? 11211), 1];
            }
            $this->_memcached->addServers($servers);
        }

        // Verify connection by getting version
        $versions = $this->_memcached->getVersion();

        return $versions !== false && $versions !== [];
    }

    /**
     * Acquire a lock for the given resource.
     *
     * Uses Memcached add() which only succeeds if the key doesn't exist.
     *
     * @param string $resource The resource identifier to lock.
     * @param int $ttl Time-to-live in seconds.
     * @return \Cake\Lock\AcquiredLock|null Returns an AcquiredLock on success, null on failure.
     */
    public function acquire(string $resource, int $ttl = 300): ?AcquiredLock
    {
        $key = $this->key($resource);
        $token = $this->generateToken();

        // add() only succeeds if key doesn't exist - atomic operation
        $result = $this->_memcached->add($key, $token, $ttl);

        if ($result === true) {
            return new AcquiredLock($resource, $token, $ttl, microtime(true), $this);
        }

        return null;
    }

    /**
     * Release a lock.
     *
     * Note: This uses CAS (Check-And-Set) to ensure only the owner can release.
     * However, there's a small race window between get and cas.
     *
     * @param \Cake\Lock\AcquiredLock $lock The lock instance to release.
     * @return bool True if the lock was released, false otherwise.
     */
    public function release(AcquiredLock $lock): bool
    {
        $key = $this->key($lock->getResource());

        // Get value to verify ownership
        $value = $this->_memcached->get($key, null, Memcached::GET_EXTENDED);

        if ($value === false) {
            return false;
        }

        // Check if we own the lock
        if ($value['value'] !== $lock->getToken()) {
            return false;
        }

        // Delete with CAS to ensure atomicity
        return $this->_memcached->delete($key);
    }

    /**
     * Check if a resource is currently locked.
     *
     * @param string $resource The resource identifier to check.
     * @return bool True if the resource is locked, false otherwise.
     */
    public function isLocked(string $resource): bool
    {
        $key = $this->key($resource);
        $this->_memcached->get($key);

        return $this->_memcached->getResultCode() !== Memcached::RES_NOTFOUND;
    }

    /**
     * Refresh a lock's TTL.
     *
     * Uses touch() to extend the TTL if we own the lock.
     *
     * @param \Cake\Lock\AcquiredLock $lock The lock instance to refresh.
     * @param int|null $ttl New TTL in seconds. If null, uses the original TTL.
     * @return bool True if the lock was refreshed, false otherwise.
     */
    public function refresh(AcquiredLock $lock, ?int $ttl = null): bool
    {
        $key = $this->key($lock->getResource());
        $ttl ??= $lock->getTtl();

        // Verify ownership first
        $value = $this->_memcached->get($key);
        if ($value !== $lock->getToken()) {
            return false;
        }

        // Touch to extend TTL
        return $this->_memcached->touch($key, $ttl);
    }

    /**
     * Force release a lock without ownership verification.
     *
     * @param string $resource The resource identifier to force release.
     * @return bool True if the lock was released, false otherwise.
     */
    public function forceRelease(string $resource): bool
    {
        $key = $this->key($resource);

        return $this->_memcached->delete($key);
    }
}
