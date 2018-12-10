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
 * @since         3.7.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Cache\Engine;

use Cake\Cache\CacheEngine;

/**
 * Array storage engine for cache.
 *
 * Not actually a persistent cache engine. All data is only
 * stored in memory for the duration of a single process. While not
 * useful in production settings this engine can be useful in tests
 * or console tools where you don't want the overhead of interacting
 * with a cache servers, but want the work saving properties a cache
 * provides.
 */
class ArrayEngine extends CacheEngine
{
    /**
     * Cached data.
     *
     * Structured as [key => [exp => expiration, val => value]]
     *
     * @var array
     */
    protected $data = [];

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
        $expires = time() + $this->_config['duration'];
        $this->data[$key] = ['exp' => $expires, 'val' => $value];

        return true;
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
        if (!isset($this->data[$key])) {
            return false;
        }
        $data = $this->data[$key];

        // Check expiration
        $now = time();
        if ($data['exp'] <= $now) {
            unset($this->data[$key]);

            return false;
        }

        return $data['val'];
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
        if (!$this->read($key)) {
            $this->write($key, 0);
        }
        $key = $this->_key($key);
        $this->data[$key]['val'] += $offset;

        return $this->data[$key]['val'];
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
        if (!$this->read($key)) {
            $this->write($key, 0);
        }
        $key = $this->_key($key);
        $this->data[$key]['val'] -= $offset;

        return $this->data[$key]['val'];
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
        unset($this->data[$key]);

        return true;
    }

    /**
     * Delete all keys from the cache. This will clear every cache config using APC.
     *
     * @param bool $check Unused argument required by interface.
     * @return bool True Returns true.
     */
    public function clear($check)
    {
        $this->data = [];

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
            $key = $this->_config['prefix'] . $group;
            if (!isset($this->data[$key])) {
                $this->data[$key] = ['exp' => PHP_INT_MAX, 'val' => 1];
            }
            $value = $this->data[$key]['val'];
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
        $key = $this->_config['prefix'] . $group;
        if (isset($this->data[$key])) {
            $this->data[$key]['val'] += 1;
        }

        return true;
    }
}
