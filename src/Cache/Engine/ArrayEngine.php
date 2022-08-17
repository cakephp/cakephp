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
     * @var array<string, array>
     */
    protected $data = [];

    /**
     * Write data for key into cache
     *
     * @param string $key Identifier for the data
     * @param mixed $value Data to be cached
     * @param \DateInterval|int|null $ttl Optional. The TTL value of this item. If no value is sent and
     *   the driver supports TTL then the library may set a default value
     *   for it or let the driver take care of that.
     * @return bool True on success and false on failure.
     */
    public function set($key, $value, $ttl = null): bool
    {
        $key = $this->_key($key);
        $expires = time() + $this->duration($ttl);
        $this->data[$key] = ['exp' => $expires, 'val' => $value];

        return true;
    }

    /**
     * Read a key from the cache
     *
     * @param string $key Identifier for the data
     * @param mixed $default Default value to return if the key does not exist.
     * @return mixed The cached data, or default value if the data doesn't exist, has
     * expired, or if there was an error fetching it.
     */
    public function get($key, $default = null)
    {
        $key = $this->_key($key);
        if (!isset($this->data[$key])) {
            return $default;
        }
        $data = $this->data[$key];

        // Check expiration
        $now = time();
        if ($data['exp'] <= $now) {
            unset($this->data[$key]);

            return $default;
        }

        return $data['val'];
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
        if ($this->get($key) === null) {
            $this->set($key, 0);
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
     * @return int|false New decremented value, false otherwise
     */
    public function decrement(string $key, int $offset = 1)
    {
        if ($this->get($key) === null) {
            $this->set($key, 0);
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
    public function delete($key): bool
    {
        $key = $this->_key($key);
        unset($this->data[$key]);

        return true;
    }

    /**
     * Delete all keys from the cache. This will clear every cache config using APC.
     *
     * @return bool True Returns true.
     */
    public function clear(): bool
    {
        $this->data = [];

        return true;
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
    public function clearGroup(string $group): bool
    {
        $key = $this->_config['prefix'] . $group;
        if (isset($this->data[$key])) {
            $this->data[$key]['val'] += 1;
        }

        return true;
    }
}
