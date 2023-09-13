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
 * @link          https://cakephp.org CakePHP(tm) Project
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @since         2.2.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

namespace Cake\Cache\Engine;

use Cake\Cache\CacheEngine;
use Cake\Log\Log;
use Redis;
use RedisCluster;
use RedisClusterException;
use RuntimeException;

/**
 * Redis storage engine for cache.
 */
class RedisClusterEngine extends CacheEngine
{
    /**
     * Redis wrapper.
     *
     * @var \RedisCluster
     */
    protected $_Redis;

    /**
     * The default config used unless overridden by runtime configuration
     * - `cluster` Cluster name.
     * - `duration` Specify how long items in this cache configuration last.
     * - `groups` List of groups or 'tags' associated to every key stored in this config.
     *    handy for deleting a complete group from cache.
     * - `persistent` Connect to the Redis server with a persistent connection
     * - `prefix` Prefix appended to all entries. Good for when you need to share a keyspace
     *    with either another cache config or another application.
     * - `read_timeout` Read timeout in seconds (float).
     * - `scanCount` Number of keys to ask for each scan (default: 10)
     * - `seeds` Seed nodes. Used when not using named cluster.
     * - `timeout` timeout in seconds (float).
     *
     * @var array<string, mixed>
     */
    protected $_defaultConfig = [
        'cluster' => null,
        'seeds' => [],
        'timeout' => 0,
        'read_timeout' => 0,
        'persistent' => true,
        'auth' => null,

        'duration' => 3600,
        'scanCount' => 10,

        'groups' => [],
        'prefix' => 'cake_',
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
            throw new RuntimeException('The `redis` extension must be enabled to use RedisClusterEngine.');
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
        try {
            $this->_Redis = new RedisCluster(
                $this->_config['cluster'],
                $this->_config['seeds'],
                $this->_config['timeout'],
                $this->_config['read_timeout'],
                $this->_config['persistent'],
                $this->_config['auth']
            );
        } catch (RedisClusterException $e) {
            if (class_exists(Log::class)) {
                Log::error('RedisClusterEngine could not connect. Got error: ' . $e->getMessage());
            }

            return false;
        }

        return true;
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
    public function set($key, $value, $ttl = null): bool
    {
        $key = $this->_key($key);
        $value = $this->serialize($value);

        $duration = $this->duration($ttl);
        if ($duration === 0) {
            return $this->_Redis->set($key, $value);
        }

        return $this->_Redis->setex($key, $duration, $value);
    }

    /**
     * Read a key from the cache
     *
     * @param string $key Identifier for the data
     * @param mixed $default Default value to return if the key does not exist.
     * @return mixed The cached data, or the default if the data doesn't exist, has
     *   expired, or if there was an error fetching it
     */
    public function get($key, $default = null)
    {
        $value = $this->_Redis->get($this->_key($key));
        if ($value === false) {
            return $default;
        }

        if ($value === true) {
            throw new RuntimeException('Found true in redis');
        }

        return $this->unserialize($value);
    }

    /**
     * Increments the value of an integer cached key & update the expiry time
     *
     * @param string $key Identifier for the data
     * @param int $offset How much to increment
     * @return int|false New incremented value, false otherwise
     */
    public function increment(string $key, int $offset = 1)
    {
        $duration = $this->_config['duration'];
        $key = $this->_key($key);

        $value = $this->_Redis->incrBy($key, $offset);
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
    public function decrement(string $key, int $offset = 1)
    {
        $duration = $this->_config['duration'];
        $key = $this->_key($key);

        $value = $this->_Redis->decrBy($key, $offset);
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
    public function delete($key): bool
    {
        $key = $this->_key($key);

        return $this->_Redis->del($key) > 0;
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
        return $this->delete($key);
    }

    /**
     * Delete all keys from the cache
     *
     * @return bool True if the cache was successfully cleared, false otherwise
     */
    public function clear(): bool
    {
        $this->_Redis->setOption(Redis::OPT_SCAN, (string)Redis::SCAN_RETRY);

        $isAllDeleted = true;
        $iterator = null;
        $pattern = $this->_config['prefix'] . '*';

        while (true) {
            $keys = $this->_Redis->scan($iterator, $pattern, (int)$this->_config['scanCount']);

            if ($keys === false) {
                break;
            }

            foreach ($keys as $key) {
                $isDeleted = ($this->_Redis->del($key) > 0);
                $isAllDeleted = $isAllDeleted && $isDeleted;
            }
        }

        return $isAllDeleted;
    }

    /**
     * Delete all keys from the cache by a blocking operation
     *
     * Faster than clear() using unlink method.
     *
     * @return bool True if the cache was successfully cleared, false otherwise
     */
    public function clearBlocking(): bool
    {
        $this->_Redis->setOption(Redis::OPT_SCAN, (string)Redis::SCAN_RETRY);

        $isAllDeleted = true;
        $iterator = null;
        $pattern = $this->_config['prefix'] . '*';

        while (true) {
            $keys = $this->_Redis->scan($iterator, $pattern, (int)$this->_config['scanCount']);

            if ($keys === false) {
                break;
            }

            foreach ($keys as $key) {
                $isDeleted = $this->deleteAsync($key);
                $isAllDeleted = $isAllDeleted && $isDeleted;
            }
        }

        return $isAllDeleted;
    }

    /**
     * Write data for key into cache if it doesn't exist already.
     * If it already exists, it fails and returns false.
     *
     * @link https://github.com/phpredis/phpredis#set
     * @param string $key Identifier for the data.
     * @param mixed $value Data to be cached.
     * @return bool True if the data was successfully cached, false on failure.
     */
    public function add(string $key, $value): bool
    {
        $duration = $this->_config['duration'];
        $key = $this->_key($key);
        $value = $this->serialize($value);

        if ($this->_Redis->set($key, $value, ['nx', 'ex' => $duration])) {
            return true;
        }

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
        return (bool)$this->_Redis->incr($this->_config['prefix'] . $group);
    }

    /**
     * Serialize value for saving to Redis.
     *
     * This is needed instead of using Redis' in built serialization feature
     * as it creates problems incrementing/decrementing intially set integer value.
     *
     * @link https://github.com/phpredis/phpredis/issues/81
     * @param mixed $value Value to serialize.
     * @return string
     */
    protected function serialize($value): string
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
    protected function unserialize(string $value)
    {
        if (preg_match('/^[-]?\d+$/', $value)) {
            return (int)$value;
        }

        return unserialize($value);
    }

    /**
     * Disconnects from the redis server
     */
    public function __destruct()
    {
        if (empty($this->_config['persistent']) && $this->_Redis !== null) {
            $this->_Redis->close();
        }
    }
}
