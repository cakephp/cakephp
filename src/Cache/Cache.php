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

use Cake\Cache\Engine\NullEngine;
use Cake\Core\ObjectRegistry;
use Cake\Core\StaticConfigTrait;
use InvalidArgumentException;
use RuntimeException;

/**
 * Cache provides a consistent interface to Caching in your application. It allows you
 * to use several different Cache engines, without coupling your application to a specific
 * implementation. It also allows you to change out cache storage or configuration without effecting
 * the rest of your application.
 *
 * ### Configuring Cache engines
 *
 * You can configure Cache engines in your application's `Config/cache.php` file.
 * A sample configuration would be:
 *
 * ```
 * Cache::config('shared', [
 *    'className' => 'Cake\Cache\Engine\ApcEngine',
 *    'prefix' => 'my_app_'
 * ]);
 * ```
 *
 * This would configure an APC cache engine to the 'shared' alias. You could then read and write
 * to that cache alias by using it for the `$config` parameter in the various Cache methods.
 *
 * In general all Cache operations are supported by all cache engines.
 * However, Cache::increment() and Cache::decrement() are not supported by File caching.
 *
 * There are 5 built-in caching engines:
 *
 * - `FileEngine` - Uses simple files to store content. Poor performance, but good for
 *    storing large objects, or things that are not IO sensitive.  Well suited to development
 *    as it is an easy cache to inspect and manually flush.
 * - `ApcEngine` - Uses the APC object cache, one of the fastest caching engines.
 * - `MemcacheEngine` - Uses the PECL::Memcache extension and Memcached for storage.
 *   Fast reads/writes, and benefits from memcache being distributed.
 * - `XcacheEngine` - Uses the Xcache extension, an alternative to APC.
 * - `WincacheEngine` - Uses Windows Cache Extension for PHP. Supports wincache 1.1.0 and higher.
 *   This engine is recommended to people deploying on windows with IIS.
 * - `RedisEngine` - Uses redis and php-redis extension to store cache data.
 *
 * See Cache engine documentation for expected configuration keys.
 *
 * @see config/app.php for configuration settings
 * @param string $name Name of the configuration
 * @param array $config Optional associative array of settings passed to the engine
 * @return array [engine, settings] on success, false on failure
 */
class Cache
{

    use StaticConfigTrait;

    /**
     * An array mapping url schemes to fully qualified caching engine
     * class names.
     *
     * @var array
     */
    protected static $_dsnClassMap = [
        'apc' => 'Cake\Cache\Engine\ApcEngine',
        'file' => 'Cake\Cache\Engine\FileEngine',
        'memcached' => 'Cake\Cache\Engine\MemcachedEngine',
        'null' => 'Cake\Cache\Engine\NullEngine',
        'redis' => 'Cake\Cache\Engine\RedisEngine',
        'wincache' => 'Cake\Cache\Engine\WincacheEngine',
        'xcache' => 'Cake\Cache\Engine\XcacheEngine',
    ];

    /**
     * Flag for tracking whether or not caching is enabled.
     *
     * @var bool
     */
    protected static $_enabled = true;

    /**
     * Group to Config mapping
     *
     * @var array
     */
    protected static $_groups = [];

    /**
     * Cache Registry used for creating and using cache adapters.
     *
     * @var \Cake\Core\ObjectRegistry
     */
    protected static $_registry;

    /**
     * Returns the Cache Registry instance used for creating and using cache adapters.
     * Also allows for injecting of a new registry instance.
     *
     * @param \Cake\Core\ObjectRegistry|null $registry Injectable registry object.
     * @return \Cake\Core\ObjectRegistry
     */
    public static function registry(ObjectRegistry $registry = null)
    {
        if ($registry) {
            static::$_registry = $registry;
        }

        if (!static::$_registry) {
            static::$_registry = new CacheRegistry();
        }

        return static::$_registry;
    }

    /**
     * Finds and builds the instance of the required engine class.
     *
     * @param string $name Name of the config array that needs an engine instance built
     * @return void
     * @throws \InvalidArgumentException When a cache engine cannot be created.
     */
    protected static function _buildEngine($name)
    {
        $registry = static::registry();

        if (empty(static::$_config[$name]['className'])) {
            throw new InvalidArgumentException(
                sprintf('The "%s" cache configuration does not exist.', $name)
            );
        }

        $config = static::$_config[$name];
        $registry->load($name, $config);

        if ($config['className'] instanceof CacheEngine) {
            $config = $config['className']->getConfig();
        }

        if (!empty($config['groups'])) {
            foreach ($config['groups'] as $group) {
                static::$_groups[$group][] = $name;
                static::$_groups[$group] = array_unique(static::$_groups[$group]);
                sort(static::$_groups[$group]);
            }
        }
    }

    /**
     * Fetch the engine attached to a specific configuration name.
     *
     * If the cache engine & configuration are missing an error will be
     * triggered.
     *
     * @param string $config The configuration name you want an engine for.
     * @return \Cake\Cache\CacheEngine When caching is disabled a null engine will be returned.
     */
    public static function engine($config)
    {
        if (!static::$_enabled) {
            return new NullEngine();
        }

        $registry = static::registry();

        if (isset($registry->{$config})) {
            return $registry->{$config};
        }

        static::_buildEngine($config);

        return $registry->{$config};
    }

    /**
     * Garbage collection
     *
     * Permanently remove all expired and deleted data
     *
     * @param string $config [optional] The config name you wish to have garbage collected. Defaults to 'default'
     * @param int|null $expires [optional] An expires timestamp. Defaults to NULL
     * @return void
     */
    public static function gc($config = 'default', $expires = null)
    {
        $engine = static::engine($config);
        $engine->gc($expires);
    }

    /**
     * Write data for key into cache.
     *
     * ### Usage:
     *
     * Writing to the active cache config:
     *
     * ```
     * Cache::write('cached_data', $data);
     * ```
     *
     * Writing to a specific cache config:
     *
     * ```
     * Cache::write('cached_data', $data, 'long_term');
     * ```
     *
     * @param string $key Identifier for the data
     * @param mixed $value Data to be cached - anything except a resource
     * @param string $config Optional string configuration name to write to. Defaults to 'default'
     * @return bool True if the data was successfully cached, false on failure
     */
    public static function write($key, $value, $config = 'default')
    {
        $engine = static::engine($config);
        if (is_resource($value)) {
            return false;
        }

        $success = $engine->write($key, $value);
        if ($success === false && $value !== '') {
            trigger_error(
                sprintf(
                    "%s cache was unable to write '%s' to %s cache",
                    $config,
                    $key,
                    get_class($engine)
                ),
                E_USER_WARNING
            );
        }

        return $success;
    }

    /**
     *  Write data for many keys into cache.
     *
     * ### Usage:
     *
     * Writing to the active cache config:
     *
     * ```
     * Cache::writeMany(['cached_data_1' => 'data 1', 'cached_data_2' => 'data 2']);
     * ```
     *
     * Writing to a specific cache config:
     *
     * ```
     * Cache::writeMany(['cached_data_1' => 'data 1', 'cached_data_2' => 'data 2'], 'long_term');
     * ```
     *
     * @param array $data An array of data to be stored in the cache
     * @param string $config Optional string configuration name to write to. Defaults to 'default'
     * @return array of bools for each key provided, indicating true for success or false for fail
     * @throws \RuntimeException
     */
    public static function writeMany($data, $config = 'default')
    {
        $engine = static::engine($config);
        $return = $engine->writeMany($data);
        foreach ($return as $key => $success) {
            if ($success === false && $data[$key] !== '') {
                throw new RuntimeException(sprintf(
                    '%s cache was unable to write \'%s\' to %s cache',
                    $config,
                    $key,
                    get_class($engine)
                ));
            }
        }

        return $return;
    }

    /**
     * Read a key from the cache.
     *
     * ### Usage:
     *
     * Reading from the active cache configuration.
     *
     * ```
     * Cache::read('my_data');
     * ```
     *
     * Reading from a specific cache configuration.
     *
     * ```
     * Cache::read('my_data', 'long_term');
     * ```
     *
     * @param string $key Identifier for the data
     * @param string $config optional name of the configuration to use. Defaults to 'default'
     * @return mixed The cached data, or false if the data doesn't exist, has expired, or if there was an error fetching it
     */
    public static function read($key, $config = 'default')
    {
        $engine = static::engine($config);

        return $engine->read($key);
    }

    /**
     * Read multiple keys from the cache.
     *
     * ### Usage:
     *
     * Reading multiple keys from the active cache configuration.
     *
     * ```
     * Cache::readMany(['my_data_1', 'my_data_2]);
     * ```
     *
     * Reading from a specific cache configuration.
     *
     * ```
     * Cache::readMany(['my_data_1', 'my_data_2], 'long_term');
     * ```
     *
     * @param array $keys an array of keys to fetch from the cache
     * @param string $config optional name of the configuration to use. Defaults to 'default'
     * @return array An array containing, for each of the given $keys, the cached data or false if cached data could not be
     * retrieved.
     */
    public static function readMany($keys, $config = 'default')
    {
        $engine = static::engine($config);

        return $engine->readMany($keys);
    }

    /**
     * Increment a number under the key and return incremented value.
     *
     * @param string $key Identifier for the data
     * @param int $offset How much to add
     * @param string $config Optional string configuration name. Defaults to 'default'
     * @return mixed new value, or false if the data doesn't exist, is not integer,
     *    or if there was an error fetching it.
     */
    public static function increment($key, $offset = 1, $config = 'default')
    {
        $engine = static::engine($config);
        if (!is_int($offset) || $offset < 0) {
            return false;
        }

        return $engine->increment($key, $offset);
    }

    /**
     * Decrement a number under the key and return decremented value.
     *
     * @param string $key Identifier for the data
     * @param int $offset How much to subtract
     * @param string $config Optional string configuration name. Defaults to 'default'
     * @return mixed new value, or false if the data doesn't exist, is not integer,
     *   or if there was an error fetching it
     */
    public static function decrement($key, $offset = 1, $config = 'default')
    {
        $engine = static::engine($config);
        if (!is_int($offset) || $offset < 0) {
            return false;
        }

        return $engine->decrement($key, $offset);
    }

    /**
     * Delete a key from the cache.
     *
     * ### Usage:
     *
     * Deleting from the active cache configuration.
     *
     * ```
     * Cache::delete('my_data');
     * ```
     *
     * Deleting from a specific cache configuration.
     *
     * ```
     * Cache::delete('my_data', 'long_term');
     * ```
     *
     * @param string $key Identifier for the data
     * @param string $config name of the configuration to use. Defaults to 'default'
     * @return bool True if the value was successfully deleted, false if it didn't exist or couldn't be removed
     */
    public static function delete($key, $config = 'default')
    {
        $engine = static::engine($config);

        return $engine->delete($key);
    }

    /**
     * Delete many keys from the cache.
     *
     * ### Usage:
     *
     * Deleting multiple keys from the active cache configuration.
     *
     * ```
     * Cache::deleteMany(['my_data_1', 'my_data_2']);
     * ```
     *
     * Deleting from a specific cache configuration.
     *
     * ```
     * Cache::deleteMany(['my_data_1', 'my_data_2], 'long_term');
     * ```
     *
     * @param array $keys Array of cache keys to be deleted
     * @param string $config name of the configuration to use. Defaults to 'default'
     * @return array of boolean values that are true if the value was successfully deleted, false if it didn't exist or
     * couldn't be removed
     */
    public static function deleteMany($keys, $config = 'default')
    {
        $engine = static::engine($config);

        return $engine->deleteMany($keys);
    }

    /**
     * Delete all keys from the cache.
     *
     * @param bool $check if true will check expiration, otherwise delete all
     * @param string $config name of the configuration to use. Defaults to 'default'
     * @return bool True if the cache was successfully cleared, false otherwise
     */
    public static function clear($check = false, $config = 'default')
    {
        $engine = static::engine($config);

        return $engine->clear($check);
    }

    /**
     * Delete all keys from the cache from all configurations.
     *
     * @param bool $check if true will check expiration, otherwise delete all
     * @return array Status code. For each configuration, it reports the status of the operation
     */
    public static function clearAll($check = false)
    {
        $status = [];

        foreach (self::configured() as $config) {
            $status[$config] = self::clear($check, $config);
        }

        return $status;
    }

    /**
     * Delete all keys from the cache belonging to the same group.
     *
     * @param string $group name of the group to be cleared
     * @param string $config name of the configuration to use. Defaults to 'default'
     * @return bool True if the cache group was successfully cleared, false otherwise
     */
    public static function clearGroup($group, $config = 'default')
    {
        $engine = static::engine($config);

        return $engine->clearGroup($group);
    }

    /**
     * Retrieve group names to config mapping.
     *
     * ```
     * Cache::config('daily', ['duration' => '1 day', 'groups' => ['posts']]);
     * Cache::config('weekly', ['duration' => '1 week', 'groups' => ['posts', 'archive']]);
     * $configs = Cache::groupConfigs('posts');
     * ```
     *
     * $configs will equal to `['posts' => ['daily', 'weekly']]`
     * Calling this method will load all the configured engines.
     *
     * @param string|null $group group name or null to retrieve all group mappings
     * @return array map of group and all configuration that has the same group
     * @throws \InvalidArgumentException
     */
    public static function groupConfigs($group = null)
    {
        foreach (array_keys(static::$_config) as $config) {
            static::engine($config);
        }
        if ($group === null) {
            return static::$_groups;
        }

        if (isset(self::$_groups[$group])) {
            return [$group => self::$_groups[$group]];
        }

        throw new InvalidArgumentException(sprintf('Invalid cache group %s', $group));
    }

    /**
     * Re-enable caching.
     *
     * If caching has been disabled with Cache::disable() this method will reverse that effect.
     *
     * @return void
     */
    public static function enable()
    {
        static::$_enabled = true;
    }

    /**
     * Disable caching.
     *
     * When disabled all cache operations will return null.
     *
     * @return void
     */
    public static function disable()
    {
        static::$_enabled = false;
    }

    /**
     * Check whether or not caching is enabled.
     *
     * @return bool
     */
    public static function enabled()
    {
        return static::$_enabled;
    }

    /**
     * Provides the ability to easily do read-through caching.
     *
     * When called if the $key is not set in $config, the $callable function
     * will be invoked. The results will then be stored into the cache config
     * at key.
     *
     * Examples:
     *
     * Using a Closure to provide data, assume `$this` is a Table object:
     *
     * ```
     * $results = Cache::remember('all_articles', function () {
     *      return $this->find('all');
     * });
     * ```
     *
     * @param string $key The cache key to read/store data at.
     * @param callable $callable The callable that provides data in the case when
     *   the cache key is empty. Can be any callable type supported by your PHP.
     * @param string $config The cache configuration to use for this operation.
     *   Defaults to default.
     * @return mixed If the key is found: the cached data, false if the data
     *   missing/expired, or an error. If the key is not found: boolean of the
     *   success of the write
     */
    public static function remember($key, $callable, $config = 'default')
    {
        $existing = self::read($key, $config);
        if ($existing !== false) {
            return $existing;
        }
        $results = call_user_func($callable);
        self::write($key, $results, $config);

        return $results;
    }

    /**
     * Write data for key into a cache engine if it doesn't exist already.
     *
     * ### Usage:
     *
     * Writing to the active cache config:
     *
     * ```
     * Cache::add('cached_data', $data);
     * ```
     *
     * Writing to a specific cache config:
     *
     * ```
     * Cache::add('cached_data', $data, 'long_term');
     * ```
     *
     * @param string $key Identifier for the data.
     * @param mixed $value Data to be cached - anything except a resource.
     * @param string $config Optional string configuration name to write to. Defaults to 'default'.
     * @return bool True if the data was successfully cached, false on failure.
     *   Or if the key existed already.
     */
    public static function add($key, $value, $config = 'default')
    {
        $engine = static::engine($config);
        if (is_resource($value)) {
            return false;
        }

        return $engine->add($key, $value);
    }
}
