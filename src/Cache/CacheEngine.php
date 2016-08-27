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
namespace Cake\Cache;

use Cake\Core\InstanceConfigTrait;
use InvalidArgumentException;

/**
 * Storage engine for CakePHP caching
 */
abstract class CacheEngine
{

    use InstanceConfigTrait;

    /**
     * The default cache configuration is overridden in most cache adapters. These are
     * the keys that are common to all adapters. If overridden, this property is not used.
     *
     * - `duration` Specify how long items in this cache configuration last.
     * - `groups` List of groups or 'tags' associated to every key stored in this config.
     *    handy for deleting a complete group from cache.
     * - `prefix` Prefix appended to all entries. Good for when you need to share a keyspace
     *    with either another cache config or another application.
     * - `probability` Probability of hitting a cache gc cleanup. Setting to 0 will disable
     *    cache::gc from ever being called automatically.
     *
     * @var array
     */
    protected $_defaultConfig = [
        'duration' => 3600,
        'groups' => [],
        'prefix' => 'cake_',
        'probability' => 100
    ];

    /**
     * Contains the compiled string with all groups
     * prefixes to be prepended to every key in this cache engine
     *
     * @var string
     */
    protected $_groupPrefix = null;

    /**
     * Initialize the cache engine
     *
     * Called automatically by the cache frontend. Merge the runtime config with the defaults
     * before use.
     *
     * @param array $config Associative array of parameters for the engine
     * @return bool True if the engine has been successfully initialized, false if not
     */
    public function init(array $config = [])
    {
        $this->config($config);

        if (!empty($this->_config['groups'])) {
            sort($this->_config['groups']);
            $this->_groupPrefix = str_repeat('%s_', count($this->_config['groups']));
        }
        if (!is_numeric($this->_config['duration'])) {
            $this->_config['duration'] = strtotime($this->_config['duration']) - time();
        }

        return true;
    }

    /**
     * Garbage collection
     *
     * Permanently remove all expired and deleted data
     *
     * @param int|null $expires [optional] An expires timestamp, invalidating all data before.
     * @return void
     */
    public function gc($expires = null)
    {
    }

    /**
     * Write value for a key into cache
     *
     * @param string $key Identifier for the data
     * @param mixed $value Data to be cached
     * @return bool True if the data was successfully cached, false on failure
     */
    abstract public function write($key, $value);

    /**
     * Write data for many keys into cache
     *
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
     * Read a key from the cache
     *
     * @param string $key Identifier for the data
     * @return mixed The cached data, or false if the data doesn't exist, has expired, or if there was an error fetching it
     */
    abstract public function read($key);

    /**
     * Read multiple keys from the cache
     *
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
     * Increment a number under the key and return incremented value
     *
     * @param string $key Identifier for the data
     * @param int $offset How much to add
     * @return bool|int New incremented value, false otherwise
     */
    abstract public function increment($key, $offset = 1);

    /**
     * Decrement a number under the key and return decremented value
     *
     * @param string $key Identifier for the data
     * @param int $offset How much to subtract
     * @return bool|int New incremented value, false otherwise
     */
    abstract public function decrement($key, $offset = 1);

    /**
     * Delete a key from the cache
     *
     * @param string $key Identifier for the data
     * @return bool True if the value was successfully deleted, false if it didn't exist or couldn't be removed
     */
    abstract public function delete($key);


    /**
     * Delete all keys from the cache
     *
     * @param bool $check if true will check expiration, otherwise delete all
     * @return bool True if the cache was successfully cleared, false otherwise
     */
    abstract public function clear($check);

    /**
     * Deletes keys from the cache
     *
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

    /**
     * Add a key to the cache if it does not already exist.
     *
     * Defaults to a non-atomic implementation. Subclasses should
     * prefer atomic implementations.
     *
     * @param string $key Identifier for the data.
     * @param mixed $value Data to be cached.
     * @return bool True if the data was successfully cached, false on failure.
     */
    public function add($key, $value)
    {
        $cachedValue = $this->read($key);
        if ($cachedValue === false) {
            return $this->write($key, $value);
        }

        return false;
    }

    /**
     * Clears all values belonging to a group. Is up to the implementing engine
     * to decide whether actually delete the keys or just simulate it to achieve
     * the same result.
     *
     * @param string $group name of the group to be cleared
     * @return bool
     */
    public function clearGroup($group)
    {
        return false;
    }

    /**
     * Does whatever initialization for each group is required
     * and returns the `group value` for each of them, this is
     * the token representing each group in the cache key
     *
     * @return array
     */
    public function groups()
    {
        return $this->_config['groups'];
    }

    /**
     * Generates a safe key for use with cache engine storage engines.
     *
     * @param string $key the key passed over
     * @return bool|string string key or false
     */
    public function key($key)
    {
        if (empty($key)) {
            return false;
        }

        $prefix = '';
        if (!empty($this->_groupPrefix)) {
            $prefix = vsprintf($this->_groupPrefix, $this->groups());
        }

        $key = preg_replace('/[\s]+/', '_', strtolower(trim(str_replace([DIRECTORY_SEPARATOR, '/', '.'], '_', (string)$key))));

        return $prefix . $key;
    }

    /**
     * Generates a safe key, taking account of the configured key prefix
     *
     * @param string $key the key passed over
     * @return mixed string $key or false
     * @throws \InvalidArgumentException If key's value is empty
     */
    protected function _key($key)
    {
        $key = $this->key($key);
        if ($key === false) {
            throw new InvalidArgumentException('An empty value is not valid as a cache key');
        }

        return $this->_config['prefix'] . $key;
    }
}
