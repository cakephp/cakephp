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

use Cake\Core\Exception\CakeException;
use Cake\Log\Log;
use Redis;
use RedisCluster;
use RedisClusterException;

/**
 * Redis cluster cache storage engine.
 */
class RedisClusterEngine extends RedisEngine
{
    /**
     * Redis cluster wrapper.
     *
     * @var \RedisCluster
     */
    protected RedisCluster|Redis $_Redis;

    /**
     * The default config used unless overridden by runtime configuration
     *
     * - `name` Redis cluster name
     * - `duration` Specify how long items in this cache configuration last.
     * - `groups` List of groups or 'tags' associated to every key stored in this config.
     * - `password` Redis server password.
     * - `persistent` Connect to the Redis server with a persistent connection
     * - `prefix` Prefix appended to all entries. Good for when you need to share a keyspace
     *    with either another cache config or another application.
     * - `scanCount` Number of keys to ask for each scan (default: 10)
     * - `readTimeout` Read timeout in seconds (float).
     * - `timeout` Timeout in seconds (float).
     * - `nodes` URL or IP addresses of the Redis cluster nodes.
     *   Format: an array of strings in the form `<ip>:<port>`, like:
     *   [
     *       '<ip>:<port>',
     *       '<ip>:<port>',
     *       '<ip>:<port>',
     *   ]
     *
     * @var array<string, mixed>
     */
    protected array $_defaultConfig = [
        'name' => null,
        'duration' => 3600,
        'groups' => [],
        'password' => null,
        'persistent' => true,
        'prefix' => 'cake_',
        'scanCount' => 10,
        'readTimeout' => 0,
        'timeout' => 0,
        'nodes' => [],
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
            throw new CakeException('The `redis` extension must be enabled to use RedisClusterEngine.');
        }

        parent::init($config);

        return $this->_connect();
    }

    /**
     * Connects to a Redis cluster server
     *
     * @return bool True if Redis server was connected
     */
    protected function _connect(): bool
    {
        $connected = false;

        try {
            $this->_Redis = new RedisCluster(
                $this->_config['name'],
                $this->_config['nodes'],
                (float)$this->_config['timeout'],
                (float)$this->_config['readTimeout'],
                $this->_config['persistent'],
                $this->_config['password'],
            );

            $connected = true;
        } catch (RedisClusterException $e) {
            $connected = false;

            if (class_exists(Log::class)) {
                Log::error('RedisClusterEngine could not connect. Got error: ' . $e->getMessage());
            }
        }

        return $connected;
    }

    /**
     * Delete all keys from the cache
     *
     * @return bool True if the cache was successfully cleared, false otherwise
     */
    public function clear(): bool
    {
        $isAllDeleted = true;
        $pattern = $this->_config['prefix'] . '*';

        foreach ($this->_Redis->_masters() as $masterNode) {
            $iterator = null;

            while (true) {
                $keys = $this->_Redis->scan($iterator, $masterNode, $pattern, (int)$this->_config['scanCount']);

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
        $isAllDeleted = true;
        $pattern = $this->_config['prefix'] . '*';

        foreach ($this->_Redis->_masters() as $masterNode) {
            $iterator = null;

            while (true) {
                $keys = $this->_Redis->scan($iterator, $masterNode, $pattern, (int)$this->_config['scanCount']);

                if ($keys === false) {
                    break;
                }

                foreach ($keys as $key) {
                    $isDeleted = ((int)$this->_Redis->unlink($key) > 0);
                    $isAllDeleted = $isAllDeleted && $isDeleted;
                }
            }
        }

        return $isAllDeleted;
    }

    /**
     * Disconnects from the redis server
     */
    public function __destruct()
    {
        if (isset($this->_Redis)) {
            if (empty($this->_config['persistent']) && $this->_Redis instanceof RedisCluster) {
                $this->_Redis->close();
            }
        }
    }
}