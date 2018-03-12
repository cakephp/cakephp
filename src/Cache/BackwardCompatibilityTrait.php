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
 * @since         3.6.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Cache;

/**
 * Trait for methods that are going to be removed
 */
trait BackwardCompatibilityTrait
{

    /**
     * Garbage collection
     *
     * Permanently remove all expired and deleted data
     *
     * @deprecated Use clearExpired() instead
     * @param int|null $expires [optional] An expires timestamp, invalidating all data before.
     * @return void
     */
    public function gc($expires = null)
    {
        $this->clearExpired();
    }

    /**
     * Read a key from the cache
     *
     * @deprecated Use get() instead
     * @param string $key Identifier for the data
     * @return mixed The cached data, or false if the data doesn't exist, has expired, or if there was an error fetching it
     */
    public function read($key)
    {
        $result = $this->get($key);
        if ($result === null) {
            return false;
        }

        return $result;
    }

    /**
     * Write data for key into cache
     *
     * @deprecated Use set() instead
     * @param string $key Identifier for the data
     * @param mixed $data Data to be cached
     * @return bool True if the data was successfully cached, false on failure
     */
    public function write($key, $data)
    {
        return $this->set($key, $data);
    }

    /**
     * Write data for many keys into cache
     *
     * @deprecated Use setMultiple()
     * @param array $data An array of data to be stored in the cache
     * @return array of bools for each key provided, true if the data was successfully cached, false on failure
     */
    public function writeMany($data)
    {
        $return = [];
        foreach ($data as $key => $value) {
            $return[$key] = $this->write($key, $value);
        }

        return $return;
    }

    /**
     * Read multiple keys from the cache
     *
     * @deprecated Use getMultiple() instead
     * @param array $keys An array of identifiers for the data
     * @return array For each cache key (given as the array key) the cache data associated or false if the data doesn't
     * exist, has expired, or if there was an error fetching it
     */
    public function readMany($keys)
    {
        $return = [];
        foreach ($keys as $key) {
            $return[$key] = $this->read($key);
        }

        return $return;
    }

    /**
     * Deletes keys from the cache
     *
     * @deprecated Use deleteMultiple() instead
     * @param array $keys An array of identifiers for the data
     * @return array For each provided cache key (given back as the array key) true if the value was successfully deleted,
     * false if it didn't exist or couldn't be removed
     */
    public function deleteMany($keys)
    {
        $return = [];
        foreach ($keys as $key) {
            $return[$key] = $this->delete($key);
        }

        return $return;
    }
}
