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
namespace Cake\Cache;

use Cake\Cache\Exception\InvalidArgumentException;
use Cake\Core\InstanceConfigTrait;
use Psr\SimpleCache\CacheInterface;
use Traversable;

/**
 * Storage engine for CakePHP caching
 *
 * @mixin \Cake\Core\InstanceConfigTrait
 */
abstract class CacheEngine implements CacheInterface
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
     * - `warnOnWriteFailures` Some engines, such as ApcuEngine, may raise warnings on
     *    write failures.
     *
     * @var array
     */
    protected $_defaultConfig = [
        'duration' => 3600,
        'groups' => [],
        'prefix' => 'cake_',
        'probability' => 100,
        'warnOnWriteFailures' => true,
    ];

    /**
     * Contains the compiled string with all groups
     * prefixes to be prepended to every key in this cache engine
     *
     * @var string
     */
    protected $_groupPrefix;

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
        $this->setConfig($config);

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
        if (!$key) {
            return false;
        }

        $prefix = '';
        if ($this->_groupPrefix) {
            $prefix = md5(implode('_', $this->groups()));
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

    /**
     * Cache Engines may trigger warnings if they encounter failures during operation,
     * if option warnOnWriteFailures is set to true.
     *
     * @param string $message The warning message.
     * @return void
     */
    protected function warning($message)
    {
        if ($this->getConfig('warnOnWriteFailures') !== true) {
            return;
        }

        triggerWarning($message);
    }

    /**
     * Delete all keys from the cache
     *
     * @return bool True if the cache was successfully cleared, false otherwise
     */
    abstract public function clear();

    /**
     * Delete all expired keys from the cache
     *
     * @return bool True if the cache was successfully cleared, false otherwise
     */
    abstract public function clearExpired();

    /**
     * Garbage collection
     *
     * Permanently remove all expired and deleted data
     *
     * @deprecated Since 3.6 use clear() and clearExpired()
     * @param int|null $expires [optional] An expires timestamp, invalidating all data before.
     * @return void
     */
    public function gc($expires = null)
    {
    }

    /**
     * Fetches a value from the cache.
     *
     * @param string $key The unique key of this item in the cache.
     * @param mixed $default Default value to return if the key does not exist.
     * @return mixed The value of the item from the cache, or $default in case of cache miss.
     * @throws \Psr\SimpleCache\InvalidArgumentException
     *   MUST be thrown if the $key string is not a legal value.
     */
    abstract public function get($key, $default = null);

    /**
     * Persists data in the cache, uniquely referenced by a key with an optional expiration TTL time.
     *
     * @param string $key The key of the item to store.
     * @param mixed $value The value of the item to store, must be serializable.
     * @param null|int|DateInterval $ttl Optional. The TTL value of this item. If no value is sent and
     *                                     the driver supports TTL then the library may set a default value
     *                                     for it or let the driver take care of that.
     * @return bool True on success and false on failure.
     * @throws \Psr\SimpleCache\InvalidArgumentException
     *   MUST be thrown if the $key string is not a legal value.
     */
    abstract public function set($key, $value, $ttl = null);

    /**
     * Read multiple keys from the cache
     *
     * @deprecated Since 3.6 use getMultiple() instead
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
     * Obtains multiple cache items by their unique keys.
     *
     * @param iterable $keys A list of keys that can obtained in a single operation.
     * @param mixed $default Default value to return for keys that do not exist.
     * @return iterable A list of key => value pairs. Cache keys that do not exist or are stale will have $default as value.
     * @throws \Psr\SimpleCache\InvalidArgumentException
     *   MUST be thrown if $keys is neither an array nor a Traversable,
     *   or if any of the $keys are not a legal value.
     */
    public function getMultiple($keys, $default = null)
    {
        $this->_checkTraversable($keys);

        $return = [];
        foreach ($keys as $key) {
            $return[$key] = $this->get($key);
        }

        return $return;
    }

    /**
     * Write value for a key into cache
     *
     * @deprecated Since 3.6 use set() instead
     * @param string $key Identifier for the data
     * @param mixed $value Data to be cached
     * @return bool True if the data was successfully cached, false on failure
     */
    abstract public function write($key, $value);

    /**
     * Read a key from the cache
     *
     * @deprecated Since 3.6 use get() instead
     * @param string $key Identifier for the data
     * @return mixed The cached data, or false if the data doesn't exist, has expired, or if there was an error fetching it
     */
    abstract public function read($key);

    /**
     * Write data for many keys into cache
     *
     * @deprecated Since 3.6 use setMultiple()
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
     * Persists a set of key => value pairs in the cache, with an optional TTL.
     *
     * @param iterable $values A list of key => value pairs for a multiple-set operation.
     * @param null|int|DateInterval $ttl Optional. The TTL value of this item. If no value is sent and
     *                                      the driver supports TTL then the library may set a default value
     *                                      for it or let the driver take care of that.
     * @return bool True on success and false on failure.
     * @throws \Psr\SimpleCache\InvalidArgumentException
     *   MUST be thrown if $values is neither an array nor a Traversable,
     *   or if any of the $values are not a legal value.
     */
    public function setMultiple($values, $ttl = null)
    {
        $this->_checkTraversable($values);

        $return = true;
        foreach ($values as $key => $value) {
            if (!is_string($key)) {
                throw new InvalidArgumentException();
            }
            if (!$this->set($key, $value, $ttl)) {
                $return = false;
            }
        }

        return $return;
    }

    /**
     * Deletes keys from the cache
     *
     * @deprecated Since 3.6 use deleteMany()
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
     * Deletes multiple cache items in a single operation.
     *
     * @param iterable $keys A list of string-based keys to be deleted.
     * @return bool True if the items were successfully removed. False if there was an error.
     * @throws \Psr\SimpleCache\InvalidArgumentException
     *   MUST be thrown if $keys is neither an array nor a Traversable,
     *   or if any of the $keys are not a legal value.
     */
    public function deleteMultiple($keys)
    {
        $this->_checkTraversable($keys);

        $return = true;
        foreach ($keys as $key) {
            if (!$this->delete($key)) {
                $return = false;
            }
        }

        return $return;
    }

    /**
     * Checks if the given value is traversable or an array
     *
     * @throws \Cake\Cache\Exception\InvalidArgumentException
     * @param mixed $key Key to check
     * @return void
     */
    protected function _checkValidKey($key)
    {
        if (is_string($key)) {
            throw new InvalidArgumentException(sprintf('Invalid key, expected a string but got `%s`', getTypeName($key)));
        }
    }

    /**
     * Checks if the given value is traversable or an array
     *
     * @throws \Cake\Cache\Exception\InvalidArgumentException
     * @param mixed $value Value to check
     * @return void
     */
    protected function _checkTraversable($value)
    {
        if (!is_array($value) && !$value instanceof Traversable) {
            throw new InvalidArgumentException(sprintf('Array or Traversable expected, but got `%s`', getTypeName($value)));
        }
    }

    /**
     * Determines whether an item is present in the cache.
     *
     * NOTE: It is recommended that has() is only to be used for cache warming type purposes
     * and not to be used within your live applications operations for get/set, as this method
     * is subject to a race condition where your has() will return true and immediately after,
     * another script can remove it making the state of your app out of date.
     *
     * @param string $key The cache item key.
     * @return bool
     * @throws \Psr\SimpleCache\InvalidArgumentException
     *   MUST be thrown if the $key string is not a legal value.
     */
    public function has($key)
    {
        $this->_checkValidKey($key);

        return $this->get($key) !== null;
    }
}
