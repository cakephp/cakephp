<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         1.2.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Cache\Engine;

use APCIterator;
use Cake\Cache\CacheEngine;

/**
 * APC storage engine for cache
 *
 */
class ApcEngine extends CacheEngine
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
        if (!extension_loaded('apc')) {
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

        $expires = 0;
        $duration = $this->_config['duration'];
        if ($duration) {
            $expires = time() + $duration;
        }
        apc_store($key . '_expires', $expires, $duration);
        return apc_store($key, $value, $duration);
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

        $time = time();
        $cachetime = (int)apc_fetch($key . '_expires');
        if ($cachetime !== 0 && ($cachetime < $time || ($time + $this->_config['duration']) < $cachetime)) {
            return false;
        }
        return apc_fetch($key);
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

        return apc_inc($key, $offset);
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

        return apc_dec($key, $offset);
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

        return apc_delete($key);
    }

    /**
     * Delete all keys from the cache. This will clear every cache config using APC.
     *
     * @param bool $check If true, nothing will be cleared, as entries are removed
     *    from APC as they expired. This flag is really only used by FileEngine.
     * @return bool True Returns true.
     */
    public function clear($check)
    {
        if ($check) {
            return true;
        }
        if (class_exists('APCIterator', false)) {
            $iterator = new APCIterator(
                'user',
                '/^' . preg_quote($this->_config['prefix'], '/') . '/',
                APC_ITER_NONE
            );
            apc_delete($iterator);
            return true;
        }
        $cache = apc_cache_info('user');
        foreach ($cache['cache_list'] as $key) {
            if (strpos($key['info'], $this->_config['prefix']) === 0) {
                apc_delete($key['info']);
            }
        }
        return true;
    }

    /**
     * Write data for key into cache if it doesn't exist already.
     * If it already exists, it fails and returns false.
     *
     * @param string $key Identifier for the data.
     * @param mixed $value Data to be cached.
     * @return bool True if the data was successfully cached, false on failure.
     * @link http://php.net/manual/en/function.apc-add.php
     */
    public function add($key, $value)
    {
        $key = $this->_key($key);

        $expires = 0;
        $duration = $this->_config['duration'];
        if ($duration) {
            $expires = time() + $duration;
        }
        apc_add($key . '_expires', $expires, $duration);
        return apc_add($key, $value, $duration);
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

        $groups = apc_fetch($this->_compiledGroupNames);
        if (count($groups) !== count($this->_config['groups'])) {
            foreach ($this->_compiledGroupNames as $group) {
                if (!isset($groups[$group])) {
                    apc_store($group, 1);
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
        apc_inc($this->_config['prefix'] . $group, 1, $success);
        return $success;
    }
}
