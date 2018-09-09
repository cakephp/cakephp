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
 * @since         2.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Cache\Engine;

use Cake\Cache\CacheEngine;

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
    public function init(array $config = [])
    {
        if (!extension_loaded('wincache')) {
            return false;
        }

        parent::init($config);

        return true;
    }

    /**
     * Write data for key into cache
     *
     * @param string $key Identifier for the data
     * @param mixed $value Data to be cached
     * @return bool True if the data was successfully cached, false on failure
     */
    public function set($key, $value, $ttl = null)
    {
        $key = $this->_key($key);
        $duration = $this->_config['duration'];
        if ($ttl !== null) {
            $duration = $ttl;
        }

        return wincache_ucache_set($key, $value, $duration);
    }

    /**
     * Read a key from the cache
     *
     * @param string $key Identifier for the data
     * @param mixed $default Default value
     * @return mixed The cached data, or false if the data doesn't exist,
     *   has expired, or if there was an error fetching it
     */
    public function get($key, $default = null)
    {
        $key = $this->_key($key);

        $result = wincache_ucache_get($key, $success);
        if ($success === false) {
            return $default;
        }

        return $result;
    }

    /**
     * Increments the value of an integer cached key
     *
     * @param string $key Identifier for the data
     * @param int $offset How much to increment
     * @return bool|int New incremented value, false otherwise
     */
    public function increment($key, $offset = 1)
    {
        $key = $this->_key($key);

        return wincache_ucache_inc($key, $offset);
    }

    /**
     * Decrements the value of an integer cached key
     *
     * @param string $key Identifier for the data
     * @param int $offset How much to subtract
     * @return bool|int New decremented value, false otherwise
     */
    public function decrement($key, $offset = 1)
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
    public function delete($key)
    {
        $key = $this->_key($key);

        return wincache_ucache_delete($key);
    }

    /**
     * Clears all expired cache entries
     *
     * @return bool
     */
    public function clearExpired()
    {
        return true;
    }

    /**
     * Delete all keys from the cache. This will clear every
     * item in the cache matching the cache config prefix.
     *
     * @return bool True Returns true.
     */
    public function clear()
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
     * Write data for key into cache
     *
     * @deprecated Since 3.6 use set()
     * @param string $key Identifier for the data
     * @param mixed $value Data to be cached
     * @return bool True if the data was successfully cached, false on failure
     */
    public function write($key, $value)
    {
        $key = $this->_key($key);
        $duration = $this->_config['duration'];

        return wincache_ucache_set($key, $value, $duration);
    }

    /**
     * Read a key from the cache
     *
     * @deprecated Since 3.6 use get()
     * @param string $key Identifier for the data
     * @return mixed The cached data, or false if the data doesn't exist,
     *   has expired, or if there was an error fetching it
     */
    public function read($key)
    {
        $key = $this->_key($key);

        return wincache_ucache_get($key);
    }

    /**
     * Returns the `group value` for each of the configured groups
     * If the group initial value was not found, then it initializes
     * the group accordingly.
     *
     * @return array
     */
    public function groups()
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
    public function clearGroup($group)
    {
        $success = false;
        wincache_ucache_inc($this->_config['prefix'] . $group, 1, $success);

        return $success;
    }
}
