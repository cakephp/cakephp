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
use Redis;
use RedisException;

/**
 * Redis-based lock engine.
 *
 * Uses Redis SET with NX and EX options for atomic lock acquisition.
 * This provides a reliable distributed locking mechanism.
 *
 * ### Configuration options:
 *
 * - `host`: Redis server hostname (default: '127.0.0.1')
 * - `port`: Redis server port (default: 6379)
 * - `password`: Redis server password (default: false)
 * - `database`: Redis database index (default: 0)
 * - `timeout`: Connection timeout in seconds (default: 0)
 * - `persistent`: Use persistent connections (default: true)
 * - `prefix`: Prefix for lock keys (default: 'lock_')
 * - `ttl`: Default lock TTL in seconds (default: 300)
 */
class RedisLockEngine extends LockEngine
{
    /**
     * Redis connection.
     *
     * @var \Redis
     */
    protected Redis $_redis;

    /**
     * Default configuration.
     *
     * @var array<string, mixed>
     */
    protected array $_defaultConfig = [
        'host' => '127.0.0.1',
        'port' => 6379,
        'password' => false,
        'database' => 0,
        'timeout' => 0,
        'persistent' => true,
        'prefix' => 'lock_',
        'ttl' => 300,
    ];

    /**
     * Initialize the Redis lock engine.
     *
     * @param array<string, mixed> $config Configuration options.
     * @return bool True if initialization was successful.
     * @throws \Cake\Core\Exception\CakeException If redis extension is not loaded.
     */
    public function init(array $config = []): bool
    {
        if (!extension_loaded('redis')) {
            throw new CakeException('The `redis` extension must be enabled to use RedisLockEngine.');
        }

        parent::init($config);

        return $this->_connect();
    }

    /**
     * Connect to Redis server.
     *
     * @return bool True if connection was successful.
     */
    protected function _connect(): bool
    {
        $this->_redis = new Redis();

        try {
            if ($this->_config['persistent']) {
                $connected = $this->_redis->pconnect(
                    $this->_config['host'],
                    $this->_config['port'],
                    (float)$this->_config['timeout'],
                    'lock_' . $this->_config['database'],
                );
            } else {
                $connected = $this->_redis->connect(
                    $this->_config['host'],
                    $this->_config['port'],
                    (float)$this->_config['timeout'],
                );
            }

            if (!$connected) {
                return false;
            }

            if ($this->_config['password'] !== false && !$this->_redis->auth($this->_config['password'])) {
                return false;
            }

            if ($this->_config['database'] !== 0) {
                $this->_redis->select((int)$this->_config['database']);
            }

            return true;
        } catch (RedisException) {
            return false;
        }
    }

    /**
     * Acquire a lock for the given resource.
     *
     * Uses Redis SET with NX (only set if not exists) and EX (expiry in seconds)
     * for atomic lock acquisition.
     *
     * @param string $resource The resource identifier to lock.
     * @param int $ttl Time-to-live in seconds.
     * @return \Cake\Lock\AcquiredLock|null Returns an AcquiredLock on success, null on failure.
     */
    public function acquire(string $resource, int $ttl = 300): ?AcquiredLock
    {
        $key = $this->key($resource);
        $token = $this->generateToken();

        try {
            // SET key value EX seconds NX - atomic set if not exists with expiry
            $result = $this->_redis->set($key, $token, ['NX', 'EX' => $ttl]);

            if ($result === true) {
                return new AcquiredLock($resource, $token, $ttl, microtime(true), $this);
            }

            return null;
        } catch (RedisException) {
            return null;
        }
    }

    /**
     * Release a lock.
     *
     * Uses a Lua script for atomic check-and-delete to ensure
     * only the lock owner can release the lock.
     *
     * @param \Cake\Lock\AcquiredLock $lock The lock instance to release.
     * @return bool True if the lock was released, false otherwise.
     */
    public function release(AcquiredLock $lock): bool
    {
        $key = $this->key($lock->getResource());

        // Lua script for atomic check-and-delete
        // Only delete if the token matches (we own the lock)
        $script = <<<'LUA'
            if redis.call("GET", KEYS[1]) == ARGV[1] then
                return redis.call("DEL", KEYS[1])
            else
                return 0
            end
            LUA;

        try {
            $result = $this->_redis->eval($script, [$key, $lock->getToken()], 1);

            return $result === 1;
        } catch (RedisException) {
            return false;
        }
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

        try {
            return $this->_redis->exists($key) === 1;
        } catch (RedisException) {
            return false;
        }
    }

    /**
     * Refresh a lock's TTL.
     *
     * Uses a Lua script to atomically verify ownership and extend TTL.
     *
     * @param \Cake\Lock\AcquiredLock $lock The lock instance to refresh.
     * @param int|null $ttl New TTL in seconds. If null, uses the original TTL.
     * @return bool True if the lock was refreshed, false otherwise.
     */
    public function refresh(AcquiredLock $lock, ?int $ttl = null): bool
    {
        $key = $this->key($lock->getResource());
        $ttl ??= $lock->getTtl();

        // Lua script for atomic check-and-expire
        $script = <<<'LUA'
            if redis.call("GET", KEYS[1]) == ARGV[1] then
                return redis.call("EXPIRE", KEYS[1], ARGV[2])
            else
                return 0
            end
            LUA;

        try {
            $result = $this->_redis->eval($script, [$key, $lock->getToken(), $ttl], 1);

            return $result === 1;
        } catch (RedisException) {
            return false;
        }
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

        try {
            return $this->_redis->del($key) >= 0;
        } catch (RedisException) {
            return false;
        }
    }

    /**
     * Get the Redis connection for testing purposes.
     *
     * @return \Redis
     */
    public function getRedis(): Redis
    {
        return $this->_redis;
    }
}
