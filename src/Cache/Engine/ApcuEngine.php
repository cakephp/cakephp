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
 * @since         3.5.4
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Cache\Engine;

use APCUIterator;
use Cake\Cache\CacheEngine;

/**
 * APCu storage engine for cache
 */
class ApcuEngine extends CacheEngine
{

    /**
     * Contains the compiled group names
     * (prefixed with the global configuration prefix)
     *
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
     */
    public function init(array $config = [])
    {
        if (!extension_loaded('apcu')) {
            return false;
        }

        return parent::init($config);
    }

    /**
     * Write data for key into cache
     *
     * @param string $key Identifier for the data
     * @param mixed $value Data to be cached
     * @return bool True if the data was successfully cached, false on failure
     * @link https://secure.php.net/manual/en/function.apcu-store.php
     */
    public function write($key, $value)
    {
        $key = $this->_key($key);

        $expires = 0;
        $duration = $this->_config['duration'];
        if ($duration) {
            $expires = time() + $duration;
        }

        return apcu_store($key . '_expires', $expires, $duration) === true
            && apcu_store($key, $value, $duration) === true;
    }

    /**
     * Read a key from the cache
     *
     * @param string $key Identifier for the data
     * @return mixed The cached data, or false if the data doesn't exist,
     *   has expired, or if there was an error fetching it
     * @link https://secure.php.net/manual/en/function.apcu-fetch.php
     */
    public function read($key)
    {
        $key = $this->_key($key);

        $time = time();
        $success = false;
        $cachetime = (int)apcu_fetch($key . '_expires', $success);
        if ($success === false) {
            $this->warn(sprintf('Failed to fetch key "%s" from APCu cache.', $key . '_expires'));
        }
        if ($cachetime !== 0 && ($cachetime < $time || ($time + $this->_config['duration']) < $cachetime)) {
            return false;
        }

        $success = false;
        $value = apcu_fetch($key, $success);
        if ($success === false) {
            $this->warn(sprintf('Failed to fetch key "%s" from APCu cache.', $key));
        }

        return $value;
    }

    /**
     * Increments the value of an integer cached key
     *
     * @param string $key Identifier for the data
     * @param int $offset How much to increment
     * @return bool|int New incremented value, false otherwise
     * @link https://secure.php.net/manual/en/function.apcu-inc.php
     */
    public function increment($key, $offset = 1)
    {
        $key = $this->_key($key);

        return apcu_inc($key, $offset);
    }

    /**
     * Decrements the value of an integer cached key
     *
     * @param string $key Identifier for the data
     * @param int $offset How much to subtract
     * @return bool|int New decremented value, false otherwise
     * @link https://secure.php.net/manual/en/function.apcu-dec.php
     */
    public function decrement($key, $offset = 1)
    {
        $key = $this->_key($key);

        return apcu_dec($key, $offset);
    }

    /**
     * Delete a key from the cache
     *
     * @param string $key Identifier for the data
     * @return bool True if the value was successfully deleted, false if it didn't exist or couldn't be removed
     * @link https://secure.php.net/manual/en/function.apcu-delete.php
     */
    public function delete($key)
    {
        $key = $this->_key($key);

        return apcu_delete($key);
    }

    /**
     * Delete all keys from the cache. This will clear every cache config using APC.
     *
     * @param bool $check If true, nothing will be cleared, as entries are removed
     *    from APC as they expired. This flag is really only used by FileEngine.
     * @return bool True Returns true.
     * @link https://secure.php.net/manual/en/function.apcu-cache-info.php
     * @link https://secure.php.net/manual/en/function.apcu-delete.php
     */
    public function clear($check)
    {
        if ($check) {
            return true;
        }
        if (class_exists('APCUIterator', false)) {
            $iterator = new APCUIterator(
                '/^' . preg_quote($this->_config['prefix'], '/') . '/',
                APC_ITER_NONE
            );
            if (apcu_delete($iterator) === false) {
                $this->warn(
                    sprintf('Failed to remove stored key "%s" from APCu cache.', $key['info'])
                );
            }

            return true;
        }
        $cache = apcu_cache_info();
        if ($cache === false) {
            $this->warn('Could not retrieve cache info from APCu cache.');
        }
        foreach ($cache['cache_list'] as $key) {
            if (strpos($key['info'], $this->_config['prefix']) === 0) {
                if (apcu_delete($key['info']) === false) {
                    $this->warn(
                        sprintf('Failed to remove stored key "%s" from APCu cache.', $key['info'])
                    );
                }
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
     * @link https://secure.php.net/manual/en/function.apcu-add.php
     */
    public function add($key, $value)
    {
        $key = $this->_key($key);

        $expires = 0;
        $duration = $this->_config['duration'];
        if ($duration) {
            $expires = time() + $duration;
        }

        return apcu_add($key, $value, $duration) === true
            && apcu_add($key . '_expires', $expires, $duration) === true;
    }

    /**
     * Returns the `group value` for each of the configured groups
     * If the group initial value was not found, then it initializes
     * the group accordingly.
     *
     * @return array
     * @link https://secure.php.net/manual/en/function.apcu-fetch.php
     * @link https://secure.php.net/manual/en/function.apcu-store.php
     */
    public function groups()
    {
        if (empty($this->_compiledGroupNames)) {
            foreach ($this->_config['groups'] as $group) {
                $this->_compiledGroupNames[] = $this->_config['prefix'] . $group;
            }
        }

        $success = false;
        $groups = apcu_fetch($this->_compiledGroupNames, $success);
        if ($success === false) {
            $this->warn(sprintf(
                'Failed to fetch %s "%s" from APCu cache.',
                count($this->_compiledGroupNames) === 1 ? 'key' : 'keys',
                join(',', $this->_compiledGroupNames)
            ));
        }
        if (count($groups) !== count($this->_config['groups'])) {
            foreach ($this->_compiledGroupNames as $group) {
                if (!isset($groups[$group])) {
                    $value = 1;
                    if (apcu_store($group, $value) === false) {
                        $this->warn(
                            sprintf('Failed to store key "%s" with value "%s" into APCu cache.', $group, $value)
                        );
                    }
                    $groups[$group] = $value;
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
     * @link https://secure.php.net/manual/en/function.apcu-inc.php
     */
    public function clearGroup($group)
    {
        $success = false;
        apcu_inc($this->_config['prefix'] . $group, 1, $success);

        return $success;
    }
}
