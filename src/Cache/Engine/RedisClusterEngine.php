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
 * @since         5.3.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

namespace Cake\Cache\Engine;

use Cake\Cache\CacheEngine;
use Cake\Core\Exception\CakeException;
use Cake\Log\Log;
use Generator;
use RedisCluster;
use RedisClusterException;

/**
 * Redis Cluster storage engine for cache.
 */
class RedisClusterEngine extends CacheEngine
{
    use RedisEngineTrait;

    /**
     * Redis wrapper.
     */
    protected RedisCluster $_Redis;

    /**
     * The default config used unless overridden by runtime configuration
     * - `cluster` Redis cluster name (must be defined in redis.ini)
     * - `seeds` An array with the seed nodes to connect to. Only use seeds or cluster, not both.
     * - `read_timeout` Read timeout.
     * - `tls` Whether to use TLS
     * - `duration` Specify how long items in this cache configuration last.
     * - `groups` List of groups or 'tags' associated to every key stored in this config.
     *    handy for deleting a complete group from cache.
     * - `persistent` Connect to the Redis server with a persistent connection
     * - `prefix` Prefix appended to all entries. Good for when you need to share a keyspace
     *    with either another cache config or another application.
     * - `scanCount` Number of keys to ask for each scan (default: 10)
     * - `server` URL or IP to the Redis server host.
     * - `timeout` timeout in seconds (float).
     * - `clearUsesFlushDb` Enable clear() and clearBlocking() to use FLUSHDB. This will be
     *   faster than standard clear()/clearBlocking() but will ignore prefixes and will
     *   cause data loss if other applications are sharing a redis database.
     *
     * @var array<string, mixed>
     */
    protected array $_defaultConfig = [
        'cluster' => null,
        'seeds' => [],
        'read_timeout' => 0,
        'auth' => null,
        'persistent' => true,
        'duration' => 3600,
        'groups' => [],
        'tls' => false,
        'prefix' => 'cake_',
        'timeout' => 0,
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
        if (!class_exists('RedisCluster')) {
            throw new CakeException('The `redis` extension must be enabled to use RedisClusterEngine.');
        }

        parent::init($config);

        return $this->_connect();
    }

    /**
     * Creates a connection to the cluster
     * This is public for mocking in tests and it should be considered
     * internal otherwise.
     */
    public function connect(): RedisCluster
    {
        return new RedisCluster(
            $this->_config['cluster'],
            $this->_config['seeds'],
            $this->_config['timeout'],
            $this->_config['read_timeout'],
            $this->_config['persistent'],
            $this->_config['auth'],
            // See: https://github.com/phpredis/phpredis/commit/8144db374338006a316beb11549f37926bd40c5d
            $this->_config['tls'] === true ? [] : null,
        );
    }

    /**
     * Connects to a Redis server
     *
     * @return bool True if Redis server was connected
     */
    protected function _connect(): bool
    {
        try {
            $this->_Redis = $this->connect();
        } catch (RedisClusterException $e) {
            if (class_exists(Log::class)) {
                Log::error('RedisEngine could not connect. Got error: ' . $e->getMessage());
            }

            return false;
        }

        return true;
    }

    /**
     * @param string $pattern Pattern to scaen
     * @return \Generator<array>
     */
    protected function scan(string $pattern): Generator
    {
        $iterator = null;
        foreach ($this->_Redis->_masters() as $node) {
            while (true) {
                // @phpstan-ignore arguments.count, argument.type
                $keys = $this->_Redis->scan($iterator, $node, $pattern, (int)$this->_config['scanCount']);
                if ($keys === false) {
                    break;
                }

                yield $keys;
            }
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
        foreach ($this->_Redis->_masters() as $node) {
            // @phpstan-ignore arguments.count
            $this->_Redis->flushDB($node, $async);
        }
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
