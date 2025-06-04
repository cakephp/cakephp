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
use Cake\Core\Exception\CakeException;
use Cake\Log\Log;
use Generator;
use Redis;
use RedisException;

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
    protected Redis $_Redis;

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
     * - `tls` connect to the Redis server using TLS.
     * - `prefix` Prefix appended to all entries. Good for when you need to share a keyspace
     *    with either another cache config or another application.
     * - `scanCount` Number of keys to ask for each scan (default: 10)
     * - `server` URL or IP to the Redis server host.
     * - `timeout` timeout in seconds (float).
     * - `unix_socket` Path to the unix socket file (default: false)
     * - `clearUsesFlushDb` Enable clear() and clearBlocking() to use FLUSHDB. This will be
     *   faster than standard clear()/clearBlocking() but will ignore prefixes and will
     *   cause data loss if other applications are sharing a redis database.
     *
     * @var array<string, mixed>
     */
    protected array $_defaultConfig = [
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
     * @param string $pattern Pattern to scaen
     * @return \Generator<array>
     */
    protected function scan(string $pattern): Generator
    {
        $iterator = null;
        while (true) {
            $keys = $this->_Redis->scan($iterator, $pattern, (int)$this->_config['scanCount']);
            if ($keys === false) {
                break;
            }

            yield $keys;
        }
    }

    /**
     * Flushes DB
     *
     * @param bool $async Whether to use asynchronous mode
     * @return void
     */
    protected function flushDB(bool $async = true): void
    {
        $this->_Redis->flushDB($async);
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
     * Disconnects from the redis server
     */
    public function __destruct()
    {
        if (empty($this->_config['persistent']) && isset($this->_Redis)) {
            $this->_Redis->close();
        }
    }
}
