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
 * @since         2.5.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Cache\Engine;

use Cake\Cache\CacheEngine;
use Cake\Cache\Exception\InvalidArgumentException;
use Cake\Core\Exception\CakeException;
use DateInterval;
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
    protected Memcached $_Memcached;

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
     * - `serialize` The serializer engine used to serialize data. Available engines are 'php',
     *    'igbinary' and 'json'. Beside 'php', the memcached extension must be compiled with the
     *    appropriate serializer support.
     * - `servers` String or array of memcached servers. If an array MemcacheEngine will use
     *    them as a pool.
     * - `options` - Additional options for the memcached client. Should be an array of option => value.
     *    Use the \Memcached::OPT_* constants as keys.
     *
     * @var array<string, mixed>
     */
    protected array $_defaultConfig = [
        'compress' => false,
        'duration' => 3600,
        'groups' => [],
        'host' => null,
        'username' => null,
        'password' => null,
        'persistent' => null,
        'port' => null,
        'prefix' => 'cake_',
        'serialize' => 'php',
        'servers' => ['127.0.0.1'],
        'options' => [],
    ];

    /**
     * List of available serializer engines
     *
     * Memcached must be compiled with JSON and igbinary support to use these engines
     *
     * @var array<string, int>
     */
    protected array $_serializers = [];

    /**
     * @var array<string>
     */
    protected array $_compiledGroupNames = [];

    /**
     * Initialize the Cache Engine
     *
     * Called automatically by the cache frontend
     *
     * @param array<string, mixed> $config array of setting for the engine
     * @return bool True if the engine has been successfully initialized, false if not
     * @throws \Cake\Cache\Exception\InvalidArgumentException When you try use authentication without
     *   Memcached compiled with SASL support
     */
    public function init(array $config = []): bool
    {
        if (!extension_loaded('memcached')) {
            throw new CakeException('The `memcached` extension must be enabled to use MemcachedEngine.');
        }

        $this->_serializers = [
            'igbinary' => Memcached::SERIALIZER_IGBINARY,
            'json' => Memcached::SERIALIZER_JSON,
            'php' => Memcached::SERIALIZER_PHP,
        ];
        if (defined('Memcached::HAVE_MSGPACK')) {
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
            $this->_Memcached = new Memcached($this->_config['persistent']);
        } else {
            $this->_Memcached = new Memcached();
        }
        $this->_setOptions();

        $serverList = $this->_Memcached->getServerList();
        if ($serverList) {
            if ($this->_Memcached->isPersistent()) {
                foreach ($serverList as $server) {
                    if (!in_array($server['host'] . ':' . $server['port'], $this->_config['servers'], true)) {
                        throw new InvalidArgumentException(
                            'Invalid cache configuration. Multiple persistent cache configurations are detected' .
                            ' with different `servers` values. `servers` values for persistent cache configurations' .
                            ' must be the same when using the same persistence id.',
                        );
                    }
                }
            }

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
                'Please pass "username" instead of "login" for connecting to Memcached',
            );
        }

        if ($this->_config['username'] !== null && $this->_config['password'] !== null) {
            if (!method_exists($this->_Memcached, 'setSaslAuthData')) {
                throw new InvalidArgumentException(
                    'Memcached extension is not built with SASL support',
                );
            }
            $this->_Memcached->setOption(Memcached::OPT_BINARY_PROTOCOL, true);
            $this->_Memcached->setSaslAuthData(
                $this->_config['username'],
                $this->_config['password'],
            );
        }

        return true;
    }

    /**
     * Settings the memcached instance
     *
     * @return void
     * @throws \Cake\Cache\Exception\InvalidArgumentException When the Memcached extension is not built
     *   with the desired serializer engine.
     */
    protected function _setOptions(): void
    {
        $this->_Memcached->setOption(Memcached::OPT_LIBKETAMA_COMPATIBLE, true);

        $serializer = strtolower($this->_config['serialize']);
        if (!isset($this->_serializers[$serializer])) {
            throw new InvalidArgumentException(
                sprintf('`%s` is not a valid serializer engine for Memcached.', $serializer),
            );
        }

        if (
            $serializer !== 'php' &&
            !constant('Memcached::HAVE_' . strtoupper($serializer))
        ) {
            throw new InvalidArgumentException(
                sprintf('Memcached extension is not compiled with `%s` support.', $serializer),
            );
        }

        $this->_Memcached->setOption(
            Memcached::OPT_SERIALIZER,
            $this->_serializers[$serializer],
        );

        // Check for Amazon ElastiCache instance
        if (
            defined('Memcached::OPT_CLIENT_MODE') &&
            defined('Memcached::DYNAMIC_CLIENT_MODE')
        ) {
            $this->_Memcached->setOption(Memcached::OPT_CLIENT_MODE, Memcached::DYNAMIC_CLIENT_MODE);
        }

        $this->_Memcached->setOption(
            Memcached::OPT_COMPRESSION,
            (bool)$this->_config['compress'],
        );
    }

    /**
     * Parses the server address into the host/port. Handles both IPv6 and IPv4
     * addresses and Unix sockets
     *
     * @param string $server The server address string.
     * @return array Array containing host, port
     */
    public function parseServerString(string $server): array
    {
        $socketTransport = 'unix://';
        if (str_starts_with($server, $socketTransport)) {
            return [substr($server, strlen($socketTransport)), 0];
        }
        if (str_starts_with($server, '[')) {
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
     * Read an option value from the memcached connection.
     *
     * @param int $name The option name to read.
     * @return string|int|bool|null
     * @see https://secure.php.net/manual/en/memcached.getoption.php
     */
    public function getOption(int $name): string|int|bool|null
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
     * @param \DateInterval|int|null $ttl Optional. The TTL value of this item. If no value is sent and
     *   the driver supports TTL then the library may set a default value
     *   for it or let the driver take care of that.
     * @return bool True if the data was successfully cached, false on failure
     * @see https://www.php.net/manual/en/memcached.set.php
     */
    public function set(string $key, mixed $value, DateInterval|int|null $ttl = null): bool
    {
        $duration = $this->duration($ttl);

        return $this->_Memcached->set($this->_key($key), $value, $duration);
    }

    /**
     * Write many cache entries to the cache at once
     *
     * @param iterable $values An array of data to be stored in the cache
     * @param \DateInterval|int|null $ttl Optional. The TTL value of this item. If no value is sent and
     *   the driver supports TTL then the library may set a default value
     *   for it or let the driver take care of that.
     * @return bool Whether the write was successful or not.
     */
    public function setMultiple(iterable $values, DateInterval|int|null $ttl = null): bool
    {
        $cacheData = [];
        foreach ($values as $key => $value) {
            $cacheData[$this->_key($key)] = $value;
        }
        $duration = $this->duration($ttl);

        return $this->_Memcached->setMulti($cacheData, $duration);
    }

    /**
     * Read a key from the cache
     *
     * @param string $key Identifier for the data
     * @param mixed $default Default value to return if the key does not exist.
     * @return mixed The cached data, or default value if the data doesn't exist, has
     * expired, or if there was an error fetching it.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $key = $this->_key($key);
        $value = $this->_Memcached->get($key);
        if ($this->_Memcached->getResultCode() == Memcached::RES_NOTFOUND) {
            return $default;
        }

        return $value;
    }

    /**
     * Read many keys from the cache at once
     *
     * @param iterable<string> $keys An array of identifiers for the data
     * @param mixed $default Default value to return for keys that do not exist.
     * @return iterable<string, mixed> An array containing, for each of the given $keys, the cached data or
     *   `$default` if cached data could not be retrieved.
     */
    public function getMultiple(iterable $keys, mixed $default = null): iterable
    {
        $cacheKeys = [];
        foreach ($keys as $key) {
            $cacheKeys[$key] = $this->_key($key);
        }

        $values = $this->_Memcached->getMulti($cacheKeys);
        if ($values === false) {
            return array_fill_keys(array_keys($cacheKeys), $default);
        }

        $return = [];
        foreach ($cacheKeys as $original => $prefixed) {
            $return[$original] = array_key_exists($prefixed, $values) ? $values[$prefixed] : $default;
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
    public function increment(string $key, int $offset = 1): int|false
    {
        return $this->_Memcached->increment($this->_key($key), $offset);
    }

    /**
     * Decrements the value of an integer cached key
     *
     * @param string $key Identifier for the data
     * @param int $offset How much to subtract
     * @return int|false New decremented value, false otherwise
     */
    public function decrement(string $key, int $offset = 1): int|false
    {
        return $this->_Memcached->decrement($this->_key($key), $offset);
    }

    /**
     * Delete a key from the cache
     *
     * @param string $key Identifier for the data
     * @return bool True if the value was successfully deleted, false if it didn't
     *   exist or couldn't be removed.
     */
    public function delete(string $key): bool
    {
        return $this->_Memcached->delete($this->_key($key));
    }

    /**
     * Delete many keys from the cache at once
     *
     * @param iterable $keys An array of identifiers for the data
     * @return bool of boolean values that are true if the key was successfully
     *   deleted, false if it didn't exist or couldn't be removed.
     */
    public function deleteMultiple(iterable $keys): bool
    {
        $cacheKeys = [];
        foreach ($keys as $key) {
            $cacheKeys[] = $this->_key($key);
        }

        return (bool)$this->_Memcached->deleteMulti($cacheKeys);
    }

    /**
     * Delete all keys from the cache
     *
     * @return bool True if the cache was successfully cleared, false otherwise
     */
    public function clear(): bool
    {
        $keys = $this->_Memcached->getAllKeys();
        if ($keys === false) {
            return false;
        }

        foreach ($keys as $key) {
            if (str_starts_with($key, $this->_config['prefix'])) {
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
    public function add(string $key, mixed $value): bool
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
     * @return array<string>
     */
    public function groups(): array
    {
        if (!$this->_compiledGroupNames) {
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
    public function clearGroup(string $group): bool
    {
        return (bool)$this->_Memcached->increment($this->_config['prefix'] . $group);
    }
}
