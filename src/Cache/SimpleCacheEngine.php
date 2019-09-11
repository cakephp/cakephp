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

namespace Cake\Cache;

use Cake\Cache\CacheEngineInterface;
use Psr\SimpleCache\CacheInterface;

/**
 * Wrapper for Cake engines that allow them to support
 * the PSR16 Simple Cache Interface
 *
 * @since 3.7.0
 * @link https://www.php-fig.org/psr/psr-16/
 */
class SimpleCacheEngine implements CacheInterface, CacheEngineInterface
{
    /**
     * The wrapped cache engine object.
     *
     * @var \Cake\Cache\CacheEngine
     */
    protected $innerEngine;

    /**
     * Constructor
     *
     * @param \Cake\Cache\CacheEngine $innerEngine The decorated engine.
     */
    public function __construct(CacheEngine $innerEngine)
    {
        $this->innerEngine = $innerEngine;
    }

    /**
     * Ensure the validity of the given cache key.
     *
     * @param string $key Key to check.
     * @return void
     * @throws \Cake\Cache\InvalidArgumentException When the key is not valid.
     */
    protected function ensureValidKey($key)
    {
        if (!is_string($key) || strlen($key) === 0) {
            throw new InvalidArgumentException('A cache key must be a non-empty string.');
        }
    }

    /**
     * Ensure the validity of the given cache keys.
     *
     * @param mixed $keys The keys to check.
     * @return void
     * @throws \Cake\Cache\InvalidArgumentException When the keys are not valid.
     */
    protected function ensureValidKeys($keys)
    {
        if (!is_array($keys) && !($keys instanceof \Traversable)) {
            throw new InvalidArgumentException('A cache key set must be either an array or a Traversable.');
        }

        foreach ($keys as $key) {
            $this->ensureValidKey($key);
        }
    }

    /**
     * Fetches the value for a given key from the cache.
     *
     * @param string $key The unique key of this item in the cache.
     * @param mixed $default Default value to return if the key does not exist.
     * @return mixed The value of the item from the cache, or $default in case of cache miss.
     * @throws \Cake\Cache\InvalidArgumentException If the $key string is not a legal value.
     */
    public function get($key, $default = null)
    {
        $this->ensureValidKey($key);
        $result = $this->innerEngine->read($key);
        if ($result === false) {
            return $default;
        }

        return $result;
    }

    /**
     * Persists data in the cache, uniquely referenced by the given key with an optional expiration TTL time.
     *
     * @param string $key The key of the item to store.
     * @param mixed $value The value of the item to store, must be serializable.
     * @param \DateInterval|int|null $ttl Optional. The TTL value of this item. If no value is sent and
     *   the driver supports TTL then the library may set a default value
     *   for it or let the driver take care of that.
     * @return bool True on success and false on failure.
     * @throws \Cake\Cache\InvalidArgumentException
     *   MUST be thrown if the $key string is not a legal value.
     */
    public function set($key, $value, $ttl = null)
    {
        $this->ensureValidKey($key);
        if ($ttl !== null) {
            $restore = $this->innerEngine->getConfig('duration');
            $this->innerEngine->setConfig('duration', $ttl);
        }
        try {
            $result = $this->innerEngine->write($key, $value);

            return (bool)$result;
        } finally {
            if (isset($restore)) {
                $this->innerEngine->setConfig('duration', $restore);
            }
        }
    }

    /**
     * Delete an item from the cache by its unique key.
     *
     * @param string $key The unique cache key of the item to delete.
     * @return bool True if the item was successfully removed. False if there was an error.
     * @throws \Cake\Cache\InvalidArgumentException If the $key string is not a legal value.
     */
    public function delete($key)
    {
        $this->ensureValidKey($key);

        return $this->innerEngine->delete($key);
    }

    /**
     * Wipes clean the entire cache's keys.
     *
     * @return bool True on success and false on failure.
     */
    public function clear()
    {
        return $this->innerEngine->clear(false);
    }

    /**
     * Obtains multiple cache items by their unique keys.
     *
     * @param iterable $keys A list of keys that can obtained in a single operation.
     * @param mixed $default Default value to return for keys that do not exist.
     * @return iterable A list of key => value pairs. Cache keys that do not exist or are stale will have $default as value.
     * @throws \Cake\Cache\InvalidArgumentException If $keys is neither an array nor a Traversable,
     *   or if any of the $keys are not a legal value.
     */
    public function getMultiple($keys, $default = null)
    {
        $this->ensureValidKeys($keys);

        $results = $this->innerEngine->readMany($keys);
        foreach ($results as $key => $value) {
            if ($value === false) {
                $results[$key] = $default;
            }
        }

        return $results;
    }

    /**
     * Persists a set of key => value pairs in the cache, with an optional TTL.
     *
     * @param iterable $values A list of key => value pairs for a multiple-set operation.
     * @param \DateInterval|int|null $ttl Optional. The TTL value of this item. If no value is sent and
     *   the driver supports TTL then the library may set a default value
     *   for it or let the driver take care of that.
     * @return bool True on success and false on failure.
     * @throws \Cake\Cache\InvalidArgumentException If $values is neither an array nor a Traversable,
     *   or if any of the $values are not a legal value.
     */
    public function setMultiple($values, $ttl = null)
    {
        $this->ensureValidKeys(array_keys($values));

        if ($ttl !== null) {
            $restore = $this->innerEngine->getConfig('duration');
            $this->innerEngine->setConfig('duration', $ttl);
        }
        try {
            $result = $this->innerEngine->writeMany($values);
            foreach ($result as $key => $success) {
                if ($success === false) {
                    return false;
                }
            }

            return true;
        } finally {
            if (isset($restore)) {
                $this->innerEngine->setConfig('duration', $restore);
            }
        }

        return false;
    }

    /**
     * Deletes multiple cache items in a single operation.
     *
     * @param iterable $keys A list of string-based keys to be deleted.
     * @return bool True if the items were successfully removed. False if there was an error.
     * @throws \Cake\Cache\InvalidArgumentException If $keys is neither an array nor a Traversable,
     *   or if any of the $keys are not a legal value.
     */
    public function deleteMultiple($keys)
    {
        $this->ensureValidKeys($keys);

        $result = $this->innerEngine->deleteMany($keys);
        foreach ($result as $key => $success) {
            if ($success === false) {
                return false;
            }
        }

        return true;
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
     * @throws \Cake\Cache\InvalidArgumentException If the $key string is not a legal value.
     */
    public function has($key)
    {
        return $this->get($key) !== null;
    }

    /**
     * {@inheritDoc}
     */
    public function add($key, $value)
    {
        return $this->innerEngine->add($key, $value);
    }

    /**
     * {@inheritDoc}
     */
    public function increment($key, $offset = 1)
    {
        return $this->innerEngine->increment($key, $offset);
    }

    /**
     * {@inheritDoc}
     */
    public function decrement($key, $offset = 1)
    {
        return $this->innerEngine->decrement($key, $offset);
    }

    /**
     * {@inheritDoc}
     */
    public function clearGroup($group)
    {
        return $this->innerEngine->clearGroup($group);
    }
}
