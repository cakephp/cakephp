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
 * @since         1.2.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Cache\Engine;

use Cake\Cache\CacheEngine;

/**
 * Xcache storage engine for cache
 *
 * @link          http://trac.lighttpd.net/xcache/ Xcache
 * @deprecated 3.6.0 Xcache engine has been deprecated and will be removed in 4.0.0.
 */
class XcacheEngine extends CacheEngine
{
    /**
     * The default config used unless overridden by runtime configuration
     *
     * - `duration` Specify how long items in this cache configuration last.
     * - `groups` List of groups or 'tags' associated to every key stored in this config.
     *    handy for deleting a complete group from cache.
     * - `prefix` Prefix appended to all entries. Good for when you need to share a keyspace
     *    with either another cache config or another application.
     * - `probability` Probability of hitting a cache gc cleanup. Setting to 0 will disable
     *    cache::gc from ever being called automatically.
     * - `PHP_AUTH_USER` xcache.admin.user
     * - `PHP_AUTH_PW` xcache.admin.password
     *
     * @var array
     */
    protected $_defaultConfig = [
        'duration' => 3600,
        'groups' => [],
        'prefix' => null,
        'probability' => 100,
        'PHP_AUTH_USER' => 'user',
        'PHP_AUTH_PW' => 'password'
    ];

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
        if (!extension_loaded('xcache')) {
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
    public function write($key, $value)
    {
        $key = $this->_key($key);

        if (!is_numeric($value)) {
            $value = serialize($value);
        }

        $duration = $this->_config['duration'];
        $expires = time() + $duration;
        xcache_set($key . '_expires', $expires, $duration);

        return xcache_set($key, $value, $duration);
    }

    /**
     * Read a key from the cache
     *
     * @param string $key Identifier for the data
     * @return mixed The cached data, or false if the data doesn't exist,
     *   has expired, or if there was an error fetching it
     */
    public function read($key)
    {
        $key = $this->_key($key);

        if (xcache_isset($key)) {
            $time = time();
            $cachetime = (int)xcache_get($key . '_expires');
            if ($cachetime < $time || ($time + $this->_config['duration']) < $cachetime) {
                return false;
            }

            $value = xcache_get($key);
            if (is_string($value) && !is_numeric($value)) {
                $value = unserialize($value);
            }

            return $value;
        }

        return false;
    }

    /**
     * Increments the value of an integer cached key
     * If the cache key is not an integer it will be treated as 0
     *
     * @param string $key Identifier for the data
     * @param int $offset How much to increment
     * @return int|false New incremented value, false otherwise
     */
    public function increment($key, $offset = 1)
    {
        $key = $this->_key($key);

        return xcache_inc($key, $offset);
    }

    /**
     * Decrements the value of an integer cached key.
     * If the cache key is not an integer it will be treated as 0
     *
     * @param string $key Identifier for the data
     * @param int $offset How much to subtract
     * @return int|false New decremented value, false otherwise
     */
    public function decrement($key, $offset = 1)
    {
        $key = $this->_key($key);

        return xcache_dec($key, $offset);
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

        return xcache_unset($key);
    }

    /**
     * Delete all keys from the cache
     *
     * @param bool $check If true no deletes will occur and instead CakePHP will rely
     *   on key TTL values.
     *   Unused for Xcache engine.
     * @return bool True if the cache was successfully cleared, false otherwise
     */
    public function clear($check)
    {
        $this->_auth();
        $max = xcache_count(XC_TYPE_VAR);
        for ($i = 0; $i < $max; $i++) {
            xcache_clear_cache(XC_TYPE_VAR, $i);
        }
        $this->_auth(true);

        return true;
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
        $result = [];
        foreach ($this->_config['groups'] as $group) {
            $value = xcache_get($this->_config['prefix'] . $group);
            if (!$value) {
                $value = 1;
                xcache_set($this->_config['prefix'] . $group, $value, 0);
            }
            $result[] = $group . $value;
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
        return (bool)xcache_inc($this->_config['prefix'] . $group, 1);
    }

    /**
     * Populates and reverses $_SERVER authentication values
     * Makes necessary changes (and reverting them back) in $_SERVER
     *
     * This has to be done because xcache_clear_cache() needs to pass Basic Http Auth
     * (see xcache.admin configuration config)
     *
     * @param bool $reverse Revert changes
     * @return void
     */
    protected function _auth($reverse = false)
    {
        static $backup = [];
        $keys = ['PHP_AUTH_USER' => 'user', 'PHP_AUTH_PW' => 'password'];
        foreach ($keys as $key => $value) {
            if ($reverse) {
                if (isset($backup[$key])) {
                    $_SERVER[$key] = $backup[$key];
                    unset($backup[$key]);
                } else {
                    unset($_SERVER[$key]);
                }
            } else {
                $value = env($key);
                if (!empty($value)) {
                    $backup[$key] = $value;
                }
                if (!empty($this->_config[$value])) {
                    $_SERVER[$key] = $this->_config[$value];
                } elseif (!empty($this->_config[$key])) {
                    $_SERVER[$key] = $this->_config[$key];
                } else {
                    $_SERVER[$key] = $value;
                }
            }
        }
    }
}
