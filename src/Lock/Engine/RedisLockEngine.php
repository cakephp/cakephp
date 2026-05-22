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
use Cake\Log\Log;
use Redis;
use RedisCluster;
use RedisClusterException;
use RedisException;

/**
 * Redis-based lock engine.
 *
 * Uses Redis SET with NX and EX options for atomic lock acquisition.
 * This provides a reliable distributed locking mechanism.
 *
 * Supports both a single Redis node (or primary behind a proxy) and
 * Redis Cluster via phpredis' `RedisCluster` client. Cluster mode is
 * enabled by providing `nodes` (and optionally `clusterName`).
 *
 * ### Configuration options:
 *
 * - `host`: Redis server hostname (default: '127.0.0.1'). Non-cluster only.
 * - `port`: Redis server port (default: 6379). Non-cluster only.
 * - `password`: Redis server password (default: false).
 * - `database`: Redis database index (default: 0). Non-cluster only.
 * - `timeout`: Connection timeout in seconds (default: 0).
 * - `readTimeout`: Read timeout in seconds (default: 0). Cluster only.
 * - `persistent`: Use persistent connections (default: true).
 * - `prefix`: Prefix for lock keys (default: 'lock_').
 * - `ttl`: Default lock TTL in seconds (default: 300).
 * - `nodes`: List of `<ip>:<port>` seed nodes for Redis Cluster. Presence
 *   of this option (or `clusterName`) switches the engine to cluster mode.
 * - `clusterName`: Named cluster entry configured via `redis.clusters.seeds`.
 * - `failover`: Cluster failover mode (`distribute`, `distribute_slaves`,
 *   `error`, `none`). Cluster only.
 * - `tls`: When true, enables TLS for cluster connections. Cluster only.
 */
class RedisLockEngine extends LockEngine
{
    /**
     * Redis connection.
     *
     * @var \Redis|\RedisCluster
     */
    protected Redis|RedisCluster $_redis;

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
        'readTimeout' => 0,
        'persistent' => true,
        'prefix' => 'lock_',
        'ttl' => 300,
        'nodes' => [],
        'clusterName' => null,
        'failover' => null,
        'tls' => false,
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
     * Connect to Redis server or cluster.
     *
     * @return bool True if connection was successful.
     */
    protected function _connect(): bool
    {
        if (!empty($this->_config['nodes']) || !empty($this->_config['clusterName'])) {
            return $this->connectRedisCluster();
        }

        return $this->connectRedis();
    }

    /**
     * Connect to a single Redis server.
     *
     * @return bool True if connection was successful.
     */
    protected function connectRedis(): bool
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
     * Connect to a Redis Cluster.
     *
     * @return bool True if connection was successful.
     */
    protected function connectRedisCluster(): bool
    {
        if (empty($this->_config['nodes']) && empty($this->_config['clusterName'])) {
            // @codeCoverageIgnoreStart
            if (class_exists(Log::class)) {
                Log::error('RedisLockEngine requires nodes or a clusterName in cluster mode');
            }

            return false;
            // @codeCoverageIgnoreEnd
        }

        // @codeCoverageIgnoreStart
        $ssl = [];
        if ($this->_config['tls']) {
            $map = [
                'ssl_ca' => 'cafile',
                'ssl_key' => 'local_pk',
                'ssl_cert' => 'local_cert',
                'verify_peer' => 'verify_peer',
                'verify_peer_name' => 'verify_peer_name',
                'allow_self_signed' => 'allow_self_signed',
            ];

            foreach ($map as $configKey => $sslOption) {
                if (array_key_exists($configKey, $this->_config)) {
                    $ssl[$sslOption] = $this->_config[$configKey];
                }
            }
        }
        // @codeCoverageIgnoreEnd

        try {
            $this->_redis = new RedisCluster(
                $this->_config['clusterName'],
                $this->_config['nodes'] ?: null,
                (float)$this->_config['timeout'],
                (float)$this->_config['readTimeout'],
                (bool)$this->_config['persistent'],
                $this->_config['password'],
                $this->_config['tls'] ? ['ssl' => $ssl] : null, // @codeCoverageIgnore
            );
        } catch (RedisClusterException $e) {
            // @codeCoverageIgnoreStart
            if (class_exists(Log::class)) {
                Log::error('RedisLockEngine could not connect to the redis cluster. Got error: ' . $e->getMessage());
            }

            return false;
            // @codeCoverageIgnoreEnd
        }

        $failover = match ($this->_config['failover']) {
            RedisCluster::FAILOVER_DISTRIBUTE, 'distribute' => RedisCluster::FAILOVER_DISTRIBUTE,
            RedisCluster::FAILOVER_DISTRIBUTE_SLAVES, 'distribute_slaves' => RedisCluster::FAILOVER_DISTRIBUTE_SLAVES,
            RedisCluster::FAILOVER_ERROR, 'error' => RedisCluster::FAILOVER_ERROR,
            RedisCluster::FAILOVER_NONE, 'none' => RedisCluster::FAILOVER_NONE,
            default => null,
        };

        if ($failover !== null) {
            $this->_redis->setOption(RedisCluster::OPT_SLAVE_FAILOVER, $failover);
        }

        return true;
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
        } catch (RedisException | RedisClusterException) {
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
        } catch (RedisException | RedisClusterException) {
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
        /** @phpstan-ignore catch.neverThrown */
        } catch (RedisException | RedisClusterException) {
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
        } catch (RedisException | RedisClusterException) {
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
        /** @phpstan-ignore catch.neverThrown */
        } catch (RedisException | RedisClusterException) {
            return false;
        }
    }
}
