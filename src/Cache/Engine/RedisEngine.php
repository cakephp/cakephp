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
 * @since         2.2.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

namespace Cake\Cache\Engine;

use Cake\Cache\CacheEngine;
use Cake\Cache\Event\CacheAfterAddEvent;
use Cake\Cache\Event\CacheAfterDecrementEvent;
use Cake\Cache\Event\CacheAfterDeleteEvent;
use Cake\Cache\Event\CacheAfterGetEvent;
use Cake\Cache\Event\CacheAfterIncrementEvent;
use Cake\Cache\Event\CacheAfterSetEvent;
use Cake\Cache\Event\CacheBeforeAddEvent;
use Cake\Cache\Event\CacheBeforeDecrementEvent;
use Cake\Cache\Event\CacheBeforeDeleteEvent;
use Cake\Cache\Event\CacheBeforeGetEvent;
use Cake\Cache\Event\CacheBeforeIncrementEvent;
use Cake\Cache\Event\CacheBeforeSetEvent;
use Cake\Cache\Event\CacheClearedEvent;
use Cake\Cache\Event\CacheGroupClearEvent;
use Cake\Core\Exception\CakeException;
use Cake\Log\Log;
use DateInterval;
use Generator;
use Redis;
use RedisCluster;
use RedisClusterException;
use RedisException;

/**
 * Redis storage engine for cache.
 */
class RedisEngine extends CacheEngine
{
    /**
     * Redis wrapper.
     *
     * @var \Redis
     */
    protected Redis|RedisCluster $_Redis;

    /**
     * The default config used unless overridden by runtime configuration
     *
     * - `clusterName` Redis cluster name
     * - `database` database number to use for connection.
     * - `duration` Specify how long items in this cache configuration last.
     * - `groups` List of groups or 'tags' associated to every key stored in this config.
     *    handy for deleting a complete group from cache.
     * - `password` Redis server password.
     * - `persistent` Connect to the Redis server with a persistent connection
     * - `port` port number to the Redis server.
     * - `tls` connect to the Redis server using TLS.
     * - `prefix` Prefix appended to all entries. Good for when you need to share a keyspace
     *    with either another cache config or another application.
     * - `scanCount` Number of keys to ask for each scan (default: 10)
     * - `server` URL or IP to the Redis server host.
     * - `timeout` timeout in seconds (float).
     * - `unix_socket` Path to the unix socket file (default: false)
     * - `readTimeout` Read timeout in seconds (float).
     * - `nodes` When using redis-cluster, the URL or IP addresses of the
     *   Redis cluster nodes.
     *   Format: an array of strings in the form `<ip>:<port>`, like:
     *   [
     *       '<ip>:<port>',
     *       '<ip>:<port>',
     *       '<ip>:<port>',
     *   ]
     * - `failover` Failover mode (distribute,distribute_slaves,error,none). Cluster mode only.
     * - `clearUsesFlushDb` Enable clear() and clearBlocking() to use FLUSHDB. This will be
     *   faster than standard clear()/clearBlocking() but will ignore prefixes and will
     *   cause dataloss if other applications are sharing a redis database.
     *
     * @var array<string, mixed>
     */
    protected array $_defaultConfig = [
        'clusterName' => null,
        'database' => 0,
        'duration' => 3600,
        'groups' => [],
        'password' => false,
        'persistent' => true,
        'port' => 6379,
        'tls' => false,
        'prefix' => 'cake_',
        'host' => null,
        'server' => '127.0.0.1',
        'timeout' => 0,
        'unix_socket' => false,
        'scanCount' => 10,
        'readTimeout' => 0,
        'nodes' => [],
        'failover' => null,
        'clearUsesFlushDb' => false,
    ];

    /**
     * Initialize the Cache Engine
     *
     * Called automatically by the cache frontend
     *
     * @param array<string, mixed> $config array of setting for the engine
     * @return bool True if the engine has been successfully initialized, false if not
     */
    public function init(array $config = []): bool
    {
        if (!extension_loaded('redis')) {
            throw new CakeException('The `redis` extension must be enabled to use RedisEngine.');
        }

        if (!empty($config['host'])) {
            $config['server'] = $config['host'];
        }

        parent::init($config);

        return $this->_connect();
    }

    /**
     * Connects to a Redis server
     *
     * @return bool True if Redis server was connected
     */
    protected function _connect(): bool
    {
        if (!empty($this->_config['nodes']) || !empty($this->_config['clusterName'])) {
            return $this->connectRedisCluster();
        }

        return $this->connectRedis();
    }

    /**
     * Connects to a Redis cluster server
     *
     * @return bool True if Redis server was connected
     */
    protected function connectRedisCluster(): bool
    {
        $connected = false;

        if (empty($this->_config['nodes'])) {
            // @codeCoverageIgnoreStart
            if (class_exists(Log::class)) {
                Log::error('RedisEngine requires one or more nodes in cluster mode');
            }
            // @codeCoverageIgnoreEnd

            return false;
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
            $this->_Redis = new RedisCluster(
                $this->_config['clusterName'],
                $this->_config['nodes'],
                (float)$this->_config['timeout'],
                (float)$this->_config['readTimeout'],
                $this->_config['persistent'],
                $this->_config['password'],
                $this->_config['tls'] ? ['ssl' => $ssl] : null, // @codeCoverageIgnore
            );

            $connected = true;
        } catch (RedisClusterException $e) {
            $connected = false;

            // @codeCoverageIgnoreStart
            if (class_exists(Log::class)) {
                Log::error('RedisEngine could not connect to the redis cluster. Got error: ' . $e->getMessage());
            }
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
            $this->_Redis->setOption(RedisCluster::OPT_SLAVE_FAILOVER, $failover);
        }

        return $connected;
    }

    /**
     * Connects to a Redis server
     *
     * @return bool True if Redis server was connected
     */
    protected function connectRedis(): bool
    {
        $tls = $this->_config['tls'] === true ? 'tls://' : '';

        $map = [
            'ssl_ca' => 'cafile',
            'ssl_key' => 'local_pk',
            'ssl_cert' => 'local_cert',
        ];

        $ssl = [];
        foreach ($map as $key => $context) {
            if (!empty($this->_config[$key])) {
                $ssl[$context] = $this->_config[$key];
            }
        }

        try {
            $this->_Redis = $this->_createRedisInstance();
            if (!empty($this->_config['unix_socket'])) {
                $return = $this->_Redis->connect($this->_config['unix_socket']);
            } elseif (empty($this->_config['persistent'])) {
                $return = $this->_connectTransient($tls . $this->_config['server'], $ssl);
            } else {
                $return = $this->_connectPersistent($tls . $this->_config['server'], $ssl);
            }
        } catch (RedisException $e) {
            if (class_exists(Log::class)) {
                Log::error('RedisEngine could not connect. Got error: ' . $e->getMessage());
            }

            return false;
        }

        if ($return && $this->_config['password']) {
            $return = $this->_Redis->auth($this->_config['password']);
        }
        if ($return) {
            return $this->_Redis->select((int)$this->_config['database']);
        }

        return $return;
    }

    /**
     * Connects to a Redis server using a new connection.
     *
     * @param string $server Server to connect to.
     * @param array $ssl SSL context options.
     * @throws \RedisException
     * @return bool True if Redis server was connected
     */
    protected function _connectTransient(string $server, array $ssl): bool
    {
        if ($ssl === []) {
            return $this->_Redis->connect(
                $server,
                (int)$this->_config['port'],
                (int)$this->_config['timeout'],
            );
        }

        return $this->_Redis->connect(
            $server,
            (int)$this->_config['port'],
            (int)$this->_config['timeout'],
            null,
            0,
            0.0,
            ['ssl' => $ssl],
        );
    }

    /**
     * Connects to a Redis server using a persistent connection.
     *
     * @param string $server Server to connect to.
     * @param array $ssl SSL context options.
     * @throws \RedisException
     * @return bool True if Redis server was connected
     */
    protected function _connectPersistent(string $server, array $ssl): bool
    {
        $persistentId = $this->_config['port'] . $this->_config['timeout'] . $this->_config['database'];

        if ($ssl === []) {
            return $this->_Redis->pconnect(
                $server,
                (int)$this->_config['port'],
                (int)$this->_config['timeout'],
                $persistentId,
            );
        }

        return $this->_Redis->pconnect(
            $server,
            (int)$this->_config['port'],
            (int)$this->_config['timeout'],
            $persistentId,
            0,
            0.0,
            ['ssl' => $ssl],
        );
    }

    /**
     * Write data for key into cache.
     *
     * @param string $key Identifier for the data
     * @param mixed $value Data to be cached
     * @param \DateInterval|int|null $ttl Optional. The TTL value of this item. If no value is sent and
     *   the driver supports TTL then the library may set a default value
     *   for it or let the driver take care of that.
     * @return bool True if the data was successfully cached, false on failure
     */
    public function set(string $key, mixed $value, DateInterval|int|null $ttl = null): bool
    {
        $key = $this->_key($key);
        $value = $this->serialize($value);
        $duration = $this->duration($ttl);
        $this->_eventClass = CacheBeforeSetEvent::class;
        $this->dispatchEvent(CacheBeforeSetEvent::NAME, ['key' => $key, 'value' => $value, 'ttl' => $duration]);

        $this->_eventClass = CacheAfterSetEvent::class;
        if ($duration === 0) {
            $success = $this->_Redis->set($key, $value);
            $this->dispatchEvent(CacheAfterSetEvent::NAME, [
                'key' => $key, 'value' => $value, 'success' => $success, 'ttl' => $duration,
            ]);

            return $success;
        }

        $success = $this->_Redis->setEx($key, $duration, $value);
        $this->dispatchEvent(CacheAfterSetEvent::NAME, [
            'key' => $key, 'value' => $value, 'success' => $success, 'ttl' => $duration,
        ]);

        return $success;
    }

    /**
     * Read a key from the cache
     *
     * @param string $key Identifier for the data
     * @param mixed $default Default value to return if the key does not exist.
     * @return mixed The cached data, or the default if the data doesn't exist, has
     *   expired, or if there was an error fetching it
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $key = $this->_key($key);
        $this->_eventClass = CacheBeforeGetEvent::class;
        $this->dispatchEvent(CacheBeforeGetEvent::NAME, ['key' => $key, 'default' => $default]);

        $value = $this->_Redis->get($key);

        $this->_eventClass = CacheAfterGetEvent::class;
        if ($value === false) {
            $this->dispatchEvent(CacheAfterGetEvent::NAME, ['key' => $key, 'value' => null, 'success' => false]);

            return $default;
        }

        $data = $this->unserialize($value);
        $this->dispatchEvent(CacheAfterGetEvent::NAME, ['key' => $key, 'value' => $value, 'success' => true]);

        return $data;
    }

    /**
     * @inheritDoc
     */
    public function has(string $key): bool
    {
        $res = $this->_Redis->exists($this->_key($key));

        return is_int($res) ? $res > 0 : $res === true;
    }

    /**
     * Increments the value of an integer cached key & update the expiry time
     *
     * @param string $key Identifier for the data
     * @param int $offset How much to increment
     * @return int|false New incremented value, false otherwise
     */
    public function increment(string $key, int $offset = 1): int|false
    {
        $duration = $this->_config['duration'];
        $key = $this->_key($key);

        $this->_eventClass = CacheBeforeIncrementEvent::class;
        $this->dispatchEvent(CacheBeforeIncrementEvent::NAME, ['key' => $key, 'offset' => $offset]);

        $value = $this->_Redis->incrBy($key, $offset);

        $this->_eventClass = CacheAfterIncrementEvent::class;
        $this->dispatchEvent(CacheAfterIncrementEvent::NAME, [
            'key' => $key, 'offset' => $offset, 'success' => $value !== false, 'value' => $value,
        ]);
        if ($duration > 0) {
            $this->_Redis->expire($key, $duration);
        }

        return $value;
    }

    /**
     * Decrements the value of an integer cached key & update the expiry time
     *
     * @param string $key Identifier for the data
     * @param int $offset How much to subtract
     * @return int|false New decremented value, false otherwise
     */
    public function decrement(string $key, int $offset = 1): int|false
    {
        $duration = $this->_config['duration'];
        $key = $this->_key($key);
        $this->_eventClass = CacheBeforeDecrementEvent::class;
        $this->dispatchEvent(CacheBeforeDecrementEvent::NAME, ['key' => $key, 'offset' => $offset]);

        $value = $this->_Redis->decrBy($key, $offset);

        $this->_eventClass = CacheAfterDecrementEvent::class;
        $this->dispatchEvent(CacheAfterDecrementEvent::NAME, [
            'key' => $key, 'offset' => $offset, 'success' => $value !== false, 'value' => $value,
        ]);
        if ($duration > 0) {
            $this->_Redis->expire($key, $duration);
        }

        return $value;
    }

    /**
     * Delete a key from the cache
     *
     * @param string $key Identifier for the data
     * @return bool True if the value was successfully deleted, false if it didn't exist or couldn't be removed
     */
    public function delete(string $key): bool
    {
        $key = $this->_key($key);
        $this->_eventClass = CacheBeforeDeleteEvent::class;
        $this->dispatchEvent(CacheBeforeDeleteEvent::NAME, ['key' => $key]);

        $success = (int)$this->_Redis->del($key) > 0;

        $this->_eventClass = CacheAfterDeleteEvent::class;
        $this->dispatchEvent(CacheAfterDeleteEvent::NAME, ['key' => $key, 'success' => $success]);

        return $success;
    }

    /**
     * Delete a key from the cache asynchronously
     *
     * Just unlink a key from the cache. The actual removal will happen later asynchronously.
     *
     * @param string $key Identifier for the data
     * @return bool True if the value was successfully deleted, false if it didn't exist or couldn't be removed
     */
    public function deleteAsync(string $key): bool
    {
        $key = $this->_key($key);
        $this->_eventClass = CacheBeforeDeleteEvent::class;
        $this->dispatchEvent(CacheBeforeDeleteEvent::NAME, ['key' => $key]);

        $result = $this->_Redis->unlink($key);
        $success = is_int($result) && $result > 0;

        $this->_eventClass = CacheAfterDeleteEvent::class;
        $this->dispatchEvent(CacheAfterDeleteEvent::NAME, ['key' => $key, 'success' => $success]);

        return $success;
    }

    /**
     * Delete all keys from the cache
     *
     * @return bool True if the cache was successfully cleared, false otherwise
     */
    public function clear(): bool
    {
        if ($this->getConfig('clearUsesFlushDb')) {
            $this->flushDB(true);
            $this->_eventClass = CacheClearedEvent::class;
            $this->dispatchEvent(CacheClearedEvent::NAME);

            return true;
        }

        $isAllDeleted = true;
        $pattern = $this->_config['prefix'] . '*';

        foreach ($this->scanKeys($pattern) as $key) {
            $result = $this->_Redis->unlink($key);
            $isDeleted = is_int($result) && $result > 0;
            $isAllDeleted = $isAllDeleted && $isDeleted;
        }
        $this->_eventClass = CacheClearedEvent::class;
        $this->dispatchEvent(CacheClearedEvent::NAME);

        return $isAllDeleted;
    }

    /**
     * Delete all keys from the cache by a blocking operation
     *
     * @return bool True if the cache was successfully cleared, false otherwise
     */
    public function clearBlocking(): bool
    {
        if ($this->getConfig('clearUsesFlushDb')) {
            $this->flushDB(false);
            $this->_eventClass = CacheClearedEvent::class;
            $this->dispatchEvent(CacheClearedEvent::NAME);

            return true;
        }

        $isAllDeleted = true;
        $pattern = $this->_config['prefix'] . '*';

        foreach ($this->scanKeys($pattern) as $key) {
            // Blocking delete
            $isDeleted = ((int)$this->_Redis->del($key) > 0);
            $isAllDeleted = $isAllDeleted && $isDeleted;
        }
        $this->_eventClass = CacheClearedEvent::class;
        $this->dispatchEvent(CacheClearedEvent::NAME);

        return $isAllDeleted;
    }

    /**
     * Write data for key into cache if it doesn't exist already.
     * If it already exists, it fails and returns false.
     *
     * @param string $key Identifier for the data.
     * @param mixed $value Data to be cached.
     * @return bool True if the data was successfully cached, false on failure.
     * @link https://github.com/phpredis/phpredis#set
     */
    public function add(string $key, mixed $value): bool
    {
        $duration = $this->_config['duration'];
        $key = $this->_key($key);
        $origValue = $value;
        $value = $this->serialize($value);

        $this->_eventClass = CacheBeforeAddEvent::class;
        $this->dispatchEvent(CacheBeforeAddEvent::NAME, [
            'key' => $key, 'value' => $origValue, 'ttl' => $duration,
        ]);

        $this->_eventClass = CacheAfterAddEvent::class;
        if ($this->_Redis->set($key, $value, ['nx', 'ex' => $duration])) {
            $this->dispatchEvent(CacheAfterAddEvent::NAME, [
                'key' => $key, 'value' => $origValue, 'success' => true, 'ttl' => $duration,
            ]);

            return true;
        }
        $this->dispatchEvent(CacheAfterAddEvent::NAME, [
            'key' => $key, 'value' => $origValue, 'success' => false, 'ttl' => $duration,
        ]);

        return false;
    }

    /**
     * Returns the `group value` for each of the configured groups
     * If the group initial value was not found, then it initializes
     * the group accordingly.
     *
     * @return array<string>
     */
    public function groups(): array
    {
        $result = [];
        foreach ($this->_config['groups'] as $group) {
            $value = $this->_Redis->get($this->_config['prefix'] . $group);
            if (!$value) {
                $value = $this->serialize(1);
                $this->_Redis->set($this->_config['prefix'] . $group, $value);
            }
            $result[] = $group . $value;
        }

        return $result;
    }

    /**
     * Increments the group value to simulate deletion of all keys under a group
     * old values will remain in storage until they expire.
     *
     * @param string $group name of the group to be cleared
     * @return bool success
     */
    public function clearGroup(string $group): bool
    {
        $success = (bool)$this->_Redis->incr($this->_config['prefix'] . $group);
        $this->_eventClass = CacheGroupClearEvent::class;
        $this->dispatchEvent(CacheGroupClearEvent::NAME, ['group' => $group]);

        return $success;
    }

    /**
     * Serialize value for saving to Redis.
     *
     * This is needed instead of using Redis' in built serialization feature
     * as it creates problems incrementing/decrementing initially set integer value.
     *
     * @param mixed $value Value to serialize.
     * @return string
     * @link https://github.com/phpredis/phpredis/issues/81
     */
    protected function serialize(mixed $value): string
    {
        if (is_int($value)) {
            return (string)$value;
        }

        return serialize($value);
    }

    /**
     * Unserialize string value fetched from Redis.
     *
     * @param string $value Value to unserialize.
     * @return mixed
     */
    protected function unserialize(string $value): mixed
    {
        if (preg_match('/^[-]?\d+$/', $value)) {
            return (int)$value;
        }

        return unserialize($value);
    }

    /**
     * Create new Redis instance.
     *
     * @return \Redis
     */
    protected function _createRedisInstance(): Redis
    {
        return new Redis();
    }

    /**
     * Unifies Redis and RedisCluster scan() calls and simplifies its use.
     *
     * @param string $pattern Pattern to scan
     * @return \Generator<string>
     */
    private function scanKeys(string $pattern): Generator
    {
        $this->_Redis->setOption(Redis::OPT_SCAN, (string)Redis::SCAN_RETRY);

        if ($this->_Redis instanceof RedisCluster) {
            foreach ($this->_Redis->_masters() as $node) {
                $iterator = null;
                while (true) {
                    $keys = $this->_Redis->scan($iterator, $node, $pattern, (int)$this->_config['scanCount']);
                    if ($keys === false) {
                        break;
                    }

                    if (is_array($keys)) {
                        foreach ($keys as $key) {
                            yield $key;
                        }
                    }
                }
            }
        } else {
            $iterator = null;
            while (true) {
                $keys = $this->_Redis->scan($iterator, $pattern, (int)$this->_config['scanCount']);
                if ($keys === false) {
                    break;
                }

                foreach ($keys as $key) {
                    yield $key;
                }
            }
        }
    }

    /**
     * Flushes DB
     *
     * @param bool $async Whether to use asynchronous mode
     * @return void
     */
    private function flushDB(bool $async): void
    {
        if ($this->_Redis instanceof RedisCluster) {
            foreach ($this->_Redis->_masters() as $node) {
                // @phpstan-ignore arguments.count
                $this->_Redis->flushDB($node, $async);
            }
        } else {
            $this->_Redis->flushDB($async);
        }
    }

    /**
     * Disconnects from the redis server
     */
    public function __destruct()
    {
        if (isset($this->_Redis) && empty($this->_config['persistent'])) {
            $this->_Redis->close();
        }
    }
}
