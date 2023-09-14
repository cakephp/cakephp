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
use Cake\Log\Log;
use Redis;
use RedisException;
use RuntimeException;

/**
 * Redis storage engine for cache.
 */
class RedisEngine extends CacheEngine
{
    use RedisEngineTrait;

    /**
     * Redis wrapper.
     *
     * @var \Redis
     */
    protected $_Redis;

    /**
     * The default config used unless overridden by runtime configuration
     *
     * - `database` database number to use for connection.
     * - `duration` Specify how long items in this cache configuration last.
     * - `groups` List of groups or 'tags' associated to every key stored in this config.
     *    handy for deleting a complete group from cache.
     * - `password` Redis server password.
     * - `persistent` Connect to the Redis server with a persistent connection
     * - `port` port number to the Redis server.
     * - `prefix` Prefix appended to all entries. Good for when you need to share a keyspace
     *    with either another cache config or another application.
     * - `scanCount` Number of keys to ask for each scan (default: 10)
     * - `server` URL or IP to the Redis server host.
     * - `timeout` timeout in seconds (float).
     * - `unix_socket` Path to the unix socket file (default: false)
     *
     * @var array<string, mixed>
     */
    protected $_defaultConfig = [
        'database' => 0,
        'duration' => 3600,
        'groups' => [],
        'password' => false,
        'persistent' => true,
        'port' => 6379,
        'prefix' => 'cake_',
        'host' => null,
        'server' => '127.0.0.1',
        'timeout' => 0,
        'unix_socket' => false,
        'scanCount' => 10,
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
            throw new RuntimeException('The `redis` extension must be enabled to use RedisEngine.');
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
        try {
            $this->_Redis = new Redis();
            if (!empty($this->_config['unix_socket'])) {
                $return = $this->_Redis->connect($this->_config['unix_socket']);
            } elseif (empty($this->_config['persistent'])) {
                $return = $this->_Redis->connect(
                    $this->_config['server'],
                    (int)$this->_config['port'],
                    (int)$this->_config['timeout']
                );
            } else {
                $persistentId = $this->_config['port'] . $this->_config['timeout'] . $this->_config['database'];
                $return = $this->_Redis->pconnect(
                    $this->_config['server'],
                    (int)$this->_config['port'],
                    (int)$this->_config['timeout'],
                    $persistentId
                );
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
            $return = $this->_Redis->select((int)$this->_config['database']);
        }

        return $return;
    }

    protected function setRetryOption(): void
    {
        $this->_Redis->setOption(Redis::OPT_SCAN, (string)Redis::SCAN_RETRY);
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
        $this->setRetryOption();

        $isAllDeleted = true;
        $iterator = null;
        $pattern = $this->_config['prefix'] . '*';

        while (true) {
            $keys = $this->_Redis->scan($iterator, $pattern, (int)$this->_config['scanCount']);

            if ($keys === false) {
                break;
            }

            foreach ($keys as $key) {
                $isDeleted = $this->unlink($key);
                $isAllDeleted = $isAllDeleted && $isDeleted;
            }
        }

        return $isAllDeleted;
    }

    /**
     * Unlink a key from the cache. The actual removal will happen later asynchronously.
     *
     * @param string $key Identifier for the data
     * @return bool True if the value was successfully deleted, false if it didn't exist or couldn't be removed
     */
    protected function unlink(string $key): bool
    {
        return $this->_Redis->unlink($key) > 0;
    }

    /**
     * Disconnects from the redis server
     */
    public function __destruct()
    {
        if (empty($this->_config['persistent']) && $this->_Redis instanceof Redis) {
            $this->_Redis->close();
        }
    }
}
