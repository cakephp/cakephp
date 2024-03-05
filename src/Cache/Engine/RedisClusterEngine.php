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
 * @since         4.5.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

namespace Cake\Cache\Engine;

use Cake\Cache\CacheEngine;
use Cake\Log\Log;
use RedisCluster;
use RedisClusterException;
use RuntimeException;

/**
 * Redis cluster storage engine for cache.
 */
class RedisClusterEngine extends CacheEngine
{
    use RedisEngineTrait;

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
        'readTimeout' => 0,
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
                $this->_config['readTimeout'],
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

    protected function setRetryOption(): void
    {
        $this->_Redis->setOption(RedisCluster::OPT_SCAN, (string)RedisCluster::SCAN_RETRY);
    }

    /**
     * Delete all keys from the cache
     *
     * @return bool True if the cache was successfully cleared, false otherwise
     */
    public function clear(): bool
    {
        $this->setRetryOption();

        $isAllDeleted = true;
        $iterator = null;
        $pattern = $this->_config['prefix'] . '*';

        foreach ($this->_Redis->_masters() as $node) {
            while (true) {
                $keys = $this->_Redis->scan($iterator, $node, $pattern, (int)$this->_config['scanCount']);

                if ($keys === false) {
                    break;
                }

                foreach ($keys as $key) {
                    $isDeleted = ($this->_Redis->del($key) > 0);
                    $isAllDeleted = $isAllDeleted && $isDeleted;
                }
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
        $this->setRetryOption();

        $isAllDeleted = true;
        $iterator = null;
        $pattern = $this->_config['prefix'] . '*';

        foreach ($this->_Redis->_masters() as $node) {
            while (true) {
                $keys = $this->_Redis->scan($iterator, $node, $pattern, (int)$this->_config['scanCount']);

                if ($keys === false) {
                    break;
                }

                foreach ($keys as $key) {
                    $isDeleted = $this->deleteAsync($key);
                    $isAllDeleted = $isAllDeleted && $isDeleted;
                }
            }
        }

        return $isAllDeleted;
    }

    /**
     * Unlink a key from the cache. The actual removal will happen later asynchronously.
     * That what we should do but there is no easy way to do this now. So fallback to normal delete.
     *
     * @param string $key Identifier for the data
     * @return bool True if the value was successfully deleted, false if it didn't exist or couldn't be removed
     */
    protected function unlink(string $key): bool
    {
        return $this->_Redis->del($key) > 0;
    }

    /**
     * Disconnects from the redis server
     */
    public function __destruct()
    {
        if (empty($this->_config['persistent']) && $this->_Redis instanceof RedisCluster) {
            $this->_Redis->close();
        }
    }
}
