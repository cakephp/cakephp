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
 * @since         2.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Cache\Engine;

use Cake\Cache\CacheEngine;
use RuntimeException;

/**
 * Wincache storage engine for cache
 *
 * Supports wincache 1.1.0 and higher.
 */
class WincacheEngine extends CacheEngine
{
    /**
     * Contains the compiled group names
     * (prefixed with the global configuration prefix)
     *
     * @var array
     */
    protected $_compiledGroupNames = [];

    /**
     * Initialize the Cache Engine
     *
     * Called automatically by the cache frontend
     *
     * @param array $config array of setting for the engine
     * @return bool True if the engine has been successfully initialized, false if not
     */
    public function init(array $config = []): bool
    {
        if (!extension_loaded('wincache')) {
            throw new RuntimeException('The `wincache` extension must be enabled to use WincacheEngine.');
        }

        parent::init($config);

        return true;
    }

    /**
     * Write data for key into cache
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
        $duration = $this->duration($ttl);

        return wincache_ucache_set($key, $value, $duration);
    }

    /**
     * Read a key from the cache
     *
     * @param string $key Identifier for the data
     * @param mixed $default Default value to return if the key does not exist.
     * @return mixed The cached data, or default value if the data doesn't exist,
     *   has expired, or if there was an error fetching it
     */
    public function get($key, $default = null)
    {
        $value = wincache_ucache_get($this->_key($key), $success);
        if ($success === false) {
            return $default;
        }

        return $value;
    }

    /**
     * Increments the value of an integer cached key
     *
     * @param string $key Identifier for the data
     * @param int $offset How much to increment
     * @return int|false New incremented value, false otherwise
     */
    public function increment(string $key, int $offset = 1)
    {
        $key = $this->_key($key);

        return wincache_ucache_inc($key, $offset);
    }

    /**
     * Decrements the value of an integer cached key
     *
     * @param string $key Identifier for the data
     * @param int $offset How much to subtract
     * @return int|false New decremented value, false otherwise
     */
    public function decrement(string $key, int $offset = 1)
    {
        $key = $this->_key($key);

        return wincache_ucache_dec($key, $offset);
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

        return wincache_ucache_delete($key);
    }

    /**
     * Delete all keys from the cache. This will clear every
     * item in the cache matching the cache config prefix.
     *
     * @return bool True Returns true.
     */
    public function clear(): bool
    {
        $info = wincache_ucache_info();
        $cacheKeys = $info['ucache_entries'];
        unset($info);
        foreach ($cacheKeys as $key) {
            if (strpos($key['key_name'], $this->_config['prefix']) === 0) {
                wincache_ucache_delete($key['key_name']);
            }
        }

        return true;
    }

    /**
     * Returns the `group value` for each of the configured groups
     * If the group initial value was not found, then it initializes
     * the group accordingly.
     *
     * @return string[]
     */
    public function groups(): array
    {
        if (empty($this->_compiledGroupNames)) {
            foreach ($this->_config['groups'] as $group) {
                $this->_compiledGroupNames[] = $this->_config['prefix'] . $group;
            }
        }

        $groups = wincache_ucache_get($this->_compiledGroupNames);
        if (count($groups) !== count($this->_config['groups'])) {
            foreach ($this->_compiledGroupNames as $group) {
                if (!isset($groups[$group])) {
                    wincache_ucache_set($group, 1);
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
     * @param string $group The group to clear.
     * @return bool success
     */
    public function clearGroup(string $group): bool
    {
        $success = false;
        wincache_ucache_inc($this->_config['prefix'] . $group, 1, $success);

        return $success;
    }
}
