<?php
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
 * @since         2.5.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Cache\Engine;

use Cake\Cache\CacheEngine;
use InvalidArgumentException;
use Memcached;

/**
 * Memcached storage engine for cache. Memcached has some limitations in the amount of
 * control you have over expire times far in the future. See MemcachedEngine::write() for
 * more information.
 *
 * Memcached engine supports binary protocol and igbinary
 * serialization (if memcached extension is compiled with --enable-igbinary).
 * Compressed keys can also be incremented/decremented.
 */
class MemcachedEngine extends CacheEngine
{
    /**
     * memcached wrapper.
     *
     * @var \Memcached
     */
    protected $_Memcached;

    /**
     * The default config used unless overridden by runtime configuration
     *
     * - `compress` Whether to compress data
     * - `duration` Specify how long items in this cache configuration last.
     * - `groups` List of groups or 'tags' associated to every key stored in this config.
     *    handy for deleting a complete group from cache.
     * - `username` Login to access the Memcache server
     * - `password` Password to access the Memcache server
     * - `persistent` The name of the persistent connection. All configurations using
     *    the same persistent value will share a single underlying connection.
     * - `prefix` Prepended to all entries. Good for when you need to share a keyspace
     *    with either another cache config or another application.
     * - `probability` Probability of hitting a cache gc cleanup. Setting to 0 will disable
     *    cache::gc from ever being called automatically.
     * - `serialize` The serializer engine used to serialize data. Available engines are php,
     *    igbinary and json. Beside php, the memcached extension must be compiled with the
     *    appropriate serializer support.
     * - `servers` String or array of memcached servers. If an array MemcacheEngine will use
     *    them as a pool.
     * - `options` - Additional options for the memcached client. Should be an array of option => value.
     *    Use the \Memcached::OPT_* constants as keys.
     *
     * @var array
     */
    protected $_defaultConfig = [
        'compress' => false,
        'duration' => 3600,
        'groups' => [],
        'host' => null,
        'username' => null,
        'password' => null,
        'persistent' => false,
        'port' => null,
        'prefix' => 'cake_',
        'probability' => 100,
        'serialize' => 'php',
        'servers' => ['127.0.0.1'],
        'options' => [],
    ];

    /**
     * List of available serializer engines
     *
     * Memcached must be compiled with json and igbinary support to use these engines
     *
     * @var array
     */
    protected $_serializers = [];

    /**
     * @var string[]
     */
    protected $_compiledGroupNames = [];

    /**
     * Initialize the Cache Engine
     *
     * Called automatically by the cache frontend
     *
     * @param array $config array of setting for the engine
     * @return bool True if the engine has been successfully initialized, false if not
     * @throws \InvalidArgumentException When you try use authentication without
     *   Memcached compiled with SASL support
     */
    public function init(array $config = [])
    {
        if (!extension_loaded('memcached')) {
            return false;
        }

        $this->_serializers = [
            'igbinary' => Memcached::SERIALIZER_IGBINARY,
            'json' => Memcached::SERIALIZER_JSON,
            'php' => Memcached::SERIALIZER_PHP,
        ];
        if (defined('Memcached::HAVE_MSGPACK') && Memcached::HAVE_MSGPACK) {
            $this->_serializers['msgpack'] = Memcached::SERIALIZER_MSGPACK;
        }

        parent::init($config);

        if (!empty($config['host'])) {
            if (empty($config['port'])) {
                $config['servers'] = [$config['host']];
            } else {
                $config['servers'] = [sprintf('%s:%d', $config['host'], $config['port'])];
            }
        }

        if (isset($config['servers'])) {
            $this->setConfig('servers', $config['servers'], false);
        }

        if (!is_array($this->_config['servers'])) {
            $this->_config['servers'] = [$this->_config['servers']];
        }

        if (isset($this->_Memcached)) {
            return true;
        }

        if ($this->_config['persistent']) {
            $this->_Memcached = new Memcached((string)$this->_config['persistent']);
        } else {
            $this->_Memcached = new Memcached();
        }
        $this->_setOptions();

        if (count($this->_Memcached->getServerList())) {
            return true;
        }

        $servers = [];
        foreach ($this->_config['servers'] as $server) {
            $servers[] = $this->parseServerString($server);
        }

        if (!$this->_Memcached->addServers($servers)) {
            return false;
        }

        if (is_array($this->_config['options'])) {
            foreach ($this->_config['options'] as $opt => $value) {
                $this->_Memcached->setOption($opt, $value);
            }
        }

        if (empty($this->_config['username']) && !empty($this->_config['login'])) {
            throw new InvalidArgumentException(
                'Please pass "username" instead of "login" for connecting to Memcached'
            );
        }

        if ($this->_config['username'] !== null && $this->_config['password'] !== null) {
            if (!method_exists($this->_Memcached, 'setSaslAuthData')) {
                throw new InvalidArgumentException(
                    'Memcached extension is not built with SASL support'
                );
            }
            $this->_Memcached->setOption(Memcached::OPT_BINARY_PROTOCOL, true);
            $this->_Memcached->setSaslAuthData(
                $this->_config['username'],
                $this->_config['password']
            );
        }

        return true;
    }

    /**
     * Settings the memcached instance
     *
     * @return void
     * @throws \InvalidArgumentException When the Memcached extension is not built
     *   with the desired serializer engine.
     */
    protected function _setOptions()
    {
        $this->_Memcached->setOption(Memcached::OPT_LIBKETAMA_COMPATIBLE, true);

        $serializer = strtolower($this->_config['serialize']);
        if (!isset($this->_serializers[$serializer])) {
            throw new InvalidArgumentException(
                sprintf('%s is not a valid serializer engine for Memcached', $serializer)
            );
        }

        if (
            $serializer !== 'php' &&
            !constant('Memcached::HAVE_' . strtoupper($serializer))
        ) {
            throw new InvalidArgumentException(
                sprintf('Memcached extension is not compiled with %s support', $serializer)
            );
        }

        $this->_Memcached->setOption(
            Memcached::OPT_SERIALIZER,
            $this->_serializers[$serializer]
        );

        // Check for Amazon ElastiCache instance
        if (
            defined('Memcached::OPT_CLIENT_MODE') &&
            defined('Memcached::DYNAMIC_CLIENT_MODE')
        ) {
            $this->_Memcached->setOption(
                Memcached::OPT_CLIENT_MODE,
                Memcached::DYNAMIC_CLIENT_MODE
            );
        }

        $this->_Memcached->setOption(
            Memcached::OPT_COMPRESSION,
            (bool)$this->_config['compress']
        );
    }

    /**
     * Parses the server address into the host/port. Handles both IPv6 and IPv4
     * addresses and Unix sockets
     *
     * @param string $server The server address string.
     * @return array Array containing host, port
     */
    public function parseServerString($server)
    {
        $socketTransport = 'unix://';
        if (strpos($server, $socketTransport) === 0) {
            return [substr($server, strlen($socketTransport)), 0];
        }
        if (substr($server, 0, 1) === '[') {
            $position = strpos($server, ']:');
            if ($position !== false) {
                $position++;
            }
        } else {
            $position = strpos($server, ':');
        }
        $port = 11211;
        $host = $server;
        if ($position !== false) {
            $host = substr($server, 0, $position);
            $port = substr($server, $position + 1);
        }

        return [$host, (int)$port];
    }

    /**
     * Backwards compatible alias of parseServerString
     *
     * @param string $server The server address string.
     * @return array Array containing host, port
     * @deprecated 3.4.13 Will be removed in 4.0.0
     */
    protected function _parseServerString($server)
    {
        return $this->parseServerString($server);
    }

    /**
     * Read an option value from the memcached connection.
     *
     * @param string $name The option name to read.
     * @return string|int|bool|null
     */
    public function getOption($name)
    {
        return $this->_Memcached->getOption($name);
    }

    /**
     * Write data for key into cache. When using memcached as your cache engine
     * remember that the Memcached pecl extension does not support cache expiry
     * times greater than 30 days in the future. Any duration greater than 30 days
     * will be treated as real Unix time value rather than an offset from current time.
     *
     * @param string $key Identifier for the data
     * @param mixed $value Data to be cached
     * @return bool True if the data was successfully cached, false on failure
     * @see https://www.php.net/manual/en/memcached.set.php
     */
    public function write($key, $value)
    {
        $duration = $this->_config['duration'];
        $key = $this->_key($key);

        return $this->_Memcached->set($key, $value, $duration);
    }

    /**
     * Write many cache entries to the cache at once
     *
     * @param array $data An array of data to be stored in the cache
     * @return array of bools for each key provided, true if the data was
     *   successfully cached, false on failure
     */
    public function writeMany($data)
    {
        $cacheData = [];
        foreach ($data as $key => $value) {
            $cacheData[$this->_key($key)] = $value;
        }

        $success = $this->_Memcached->setMulti($cacheData);

        $return = [];
        foreach (array_keys($data) as $key) {
            $return[$key] = $success;
        }

        return $return;
    }

    /**
     * Read a key from the cache
     *
     * @param string $key Identifier for the data
     * @return mixed The cached data, or false if the data doesn't exist, has
     * expired, or if there was an error fetching it.
     */
    public function read($key)
    {
        $key = $this->_key($key);

        return $this->_Memcached->get($key);
    }

    /**
     * Read many keys from the cache at once
     *
     * @param array $keys An array of identifiers for the data
     * @return array An array containing, for each of the given $keys, the cached data or
     *   false if cached data could not be retrieved.
     */
    public function readMany($keys)
    {
        $cacheKeys = [];
        foreach ($keys as $key) {
            $cacheKeys[] = $this->_key($key);
        }

        $values = $this->_Memcached->getMulti($cacheKeys);
        $return = [];
        foreach ($keys as &$key) {
            $return[$key] = array_key_exists($this->_key($key), $values) ?
                $values[$this->_key($key)] : false;
        }

        return $return;
    }

    /**
     * Increments the value of an integer cached key
     *
     * @param string $key Identifier for the data
     * @param int $offset How much to increment
     * @return int|false New incremented value, false otherwise
     */
    public function increment($key, $offset = 1)
    {
        $key = $this->_key($key);

        return $this->_Memcached->increment($key, $offset);
    }

    /**
     * Decrements the value of an integer cached key
     *
     * @param string $key Identifier for the data
     * @param int $offset How much to subtract
     * @return int|false New decremented value, false otherwise
     */
    public function decrement($key, $offset = 1)
    {
        $key = $this->_key($key);

        return $this->_Memcached->decrement($key, $offset);
    }

    /**
     * Delete a key from the cache
     *
     * @param string $key Identifier for the data
     * @return bool True if the value was successfully deleted, false if it didn't
     *   exist or couldn't be removed.
     */
    public function delete($key)
    {
        $key = $this->_key($key);

        return $this->_Memcached->delete($key);
    }

    /**
     * Delete many keys from the cache at once
     *
     * @param array $keys An array of identifiers for the data
     * @return array of boolean values that are true if the key was successfully
     *   deleted, false if it didn't exist or couldn't be removed.
     */
    public function deleteMany($keys)
    {
        $cacheKeys = [];
        foreach ($keys as $key) {
            $cacheKeys[] = $this->_key($key);
        }

        $success = $this->_Memcached->deleteMulti($cacheKeys);

        $return = [];
        foreach ($keys as $key) {
            $return[$key] = $success;
        }

        return $return;
    }

    /**
     * Delete all keys from the cache
     *
     * @param bool $check If true will check expiration, otherwise delete all.
     * @return bool True if the cache was successfully cleared, false otherwise
     */
    public function clear($check)
    {
        if ($check) {
            return true;
        }

        $keys = $this->_Memcached->getAllKeys();
        if ($keys === false) {
            return false;
        }

        foreach ($keys as $key) {
            if (strpos($key, $this->_config['prefix']) === 0) {
                $this->_Memcached->delete($key);
            }
        }

        return true;
    }

    /**
     * Add a key to the cache if it does not already exist.
     *
     * @param string $key Identifier for the data.
     * @param mixed $value Data to be cached.
     * @return bool True if the data was successfully cached, false on failure.
     */
    public function add($key, $value)
    {
        $duration = $this->_config['duration'];
        $key = $this->_key($key);

        return $this->_Memcached->add($key, $value, $duration);
    }

    /**
     * Returns the `group value` for each of the configured groups
     * If the group initial value was not found, then it initializes
     * the group accordingly.
     *
     * @return string[]
     */
    public function groups()
    {
        if (empty($this->_compiledGroupNames)) {
            foreach ($this->_config['groups'] as $group) {
                $this->_compiledGroupNames[] = $this->_config['prefix'] . $group;
            }
        }

        $groups = $this->_Memcached->getMulti($this->_compiledGroupNames) ?: [];
        if (count($groups) !== count($this->_config['groups'])) {
            foreach ($this->_compiledGroupNames as $group) {
                if (!isset($groups[$group])) {
                    $this->_Memcached->set($group, 1, 0);
                    $groups[$group] = 1;
                }
            }
            ksort($groups);
        }

        $result = [];
        $groups = array_values($groups);
        foreach ($this->_config['groups'] as $i => $group) {
            $result[] = $group . $groups[$i];
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
    public function clearGroup($group)
    {
        return (bool)$this->_Memcached->increment($this->_config['prefix'] . $group);
    }
}
