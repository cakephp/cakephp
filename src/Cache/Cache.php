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
 * @since         1.2.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Cache;

use Cake\Cache\Engine\NullEngine;
use Cake\Cache\Exception\CacheWriteException;
use Cake\Cache\Exception\InvalidArgumentException;
use Cake\Core\StaticConfigTrait;
use Closure;
use Psr\SimpleCache\CacheInterface;
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
 *    'className' => Cake\Cache\Engine\ApcuEngine::class,
 *    'prefix' => 'my_app_'
 * ]);
 * ```
 *
 * This would configure an APCu cache engine to the 'shared' alias. You could then read and write
 * to that cache alias by using it for the `$config` parameter in the various Cache methods.
 *
 * In general all Cache operations are supported by all cache engines.
 * However, Cache::increment() and Cache::decrement() are not supported by File caching.
 *
 * There are 7 built-in caching engines:
 *
 * - `ApcuEngine` - Uses the APCu object cache, one of the fastest caching engines.
 * - `ArrayEngine` - Uses only memory to store all data, not actually a persistent engine.
 *    Can be useful in test or CLI environment.
 * - `FileEngine` - Uses simple files to store content. Poor performance, but good for
 *    storing large objects, or things that are not IO sensitive. Well suited to development
 *    as it is an easy cache to inspect and manually flush.
 * - `MemcacheEngine` - Uses the PECL::Memcache extension and Memcached for storage.
 *    Fast reads/writes, and benefits from memcache being distributed.
 * - `RedisEngine` - Uses redis and php-redis extension to store cache data.
 * - `XcacheEngine` - Uses the Xcache extension, an alternative to APCu.
 *
 * See Cache engine documentation for expected configuration keys.
 *
 * @see config/app.php for configuration settings
 */
class Cache
{
    use StaticConfigTrait;

    /**
     * An array mapping URL schemes to fully qualified caching engine
     * class names.
     *
     * @var array<string, string>
     * @psalm-var array<string, class-string>
     */
    protected static array $_dsnClassMap = [
        'array' => Engine\ArrayEngine::class,
        'apcu' => Engine\ApcuEngine::class,
        'file' => Engine\FileEngine::class,
        'memcached' => Engine\MemcachedEngine::class,
        'null' => Engine\NullEngine::class,
        'redis' => Engine\RedisEngine::class,
    ];

    /**
     * Flag for tracking whether caching is enabled.
     *
     * @var bool
     */
    protected static bool $_enabled = true;

    /**
     * Group to Config mapping
     *
     * @var array<string, array>
     */
    protected static array $_groups = [];

    /**
     * Cache Registry used for creating and using cache adapters.
     *
     * @var \Cake\Cache\CacheRegistry
     */
    protected static CacheRegistry $_registry;

    /**
     * Returns the Cache Registry instance used for creating and using cache adapters.
     *
     * @return \Cake\Cache\CacheRegistry
     */
    public static function getRegistry(): CacheRegistry
    {
        return static::$_registry ??= new CacheRegistry();
    }

    /**
     * Sets the Cache Registry instance used for creating and using cache adapters.
     *
     * Also allows for injecting of a new registry instance.
     *
     * @param \Cake\Cache\CacheRegistry $registry Injectable registry object.
     * @return void
     */
    public static function setRegistry(CacheRegistry $registry): void
    {
        static::$_registry = $registry;
    }

    /**
     * Finds and builds the instance of the required engine class.
     *
     * @param string $name Name of the config array that needs an engine instance built
     * @throws \Cake\Cache\Exception\InvalidArgumentException When a cache engine cannot be created.
     * @throws \RuntimeException If loading of the engine failed.
     * @return void
     */
    protected static function _buildEngine(string $name): void
    {
        $registry = static::getRegistry();

        if (empty(static::$_config[$name]['className'])) {
            throw new InvalidArgumentException(
                sprintf('The `%s` cache configuration does not exist.', $name)
            );
        }

        $config = static::$_config[$name];

        try {
            $registry->load($name, $config);
        } catch (RuntimeException $e) {
            if (!array_key_exists('fallback', $config)) {
                $registry->set($name, new NullEngine());
                trigger_error($e->getMessage(), E_USER_WARNING);

                return;
            }

            if ($config['fallback'] === false) {
                throw $e;
            }

            if ($config['fallback'] === $name) {
                throw new InvalidArgumentException(sprintf(
                    '`%s` cache configuration cannot fallback to itself.',
                    $name
                ), 0, $e);
            }

            $fallbackEngine = clone static::pool($config['fallback']);
            assert($fallbackEngine instanceof CacheEngine);

            $newConfig = $config + ['groups' => [], 'prefix' => null];
            $fallbackEngine->setConfig('groups', $newConfig['groups'], false);
            if ($newConfig['prefix']) {
                $fallbackEngine->setConfig('prefix', $newConfig['prefix'], false);
            }
            $registry->set($name, $fallbackEngine);
        }

        if ($config['className'] instanceof CacheEngine) {
            $config = $config['className']->getConfig();
        }

        if (!empty($config['groups'])) {
            /** @var string $group */
            foreach ($config['groups'] as $group) {
                static::$_groups[$group][] = $name;
                static::$_groups[$group] = array_unique(static::$_groups[$group]);
                sort(static::$_groups[$group]);
            }
        }
    }

    /**
     * Get a SimpleCacheEngine object for the named cache pool.
     *
     * @param string $config The name of the configured cache backend.
     * @return \Psr\SimpleCache\CacheInterface&\Cake\Cache\CacheEngineInterface
     */
    public static function pool(string $config): CacheInterface&CacheEngineInterface
    {
        if (!static::$_enabled) {
            return new NullEngine();
        }

        $registry = static::getRegistry();

        if (isset($registry->{$config})) {
            return $registry->{$config};
        }

        static::_buildEngine($config);

        return $registry->{$config};
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
    public static function write(string $key, mixed $value, string $config = 'default'): bool
    {
        if (is_resource($value)) {
            return false;
        }

        $backend = static::pool($config);
        $success = $backend->set($key, $value);
        if ($success === false && $value !== '') {
            throw new CacheWriteException(sprintf(
                "%s cache was unable to write '%s' to %s cache",
                $config,
                $key,
                get_class($backend)
            ));
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
     * @param iterable $data An array or Traversable of data to be stored in the cache
     * @param string $config Optional string configuration name to write to. Defaults to 'default'
     * @return bool True on success, false on failure
     * @throws \Cake\Cache\Exception\InvalidArgumentException
     */
    public static function writeMany(iterable $data, string $config = 'default'): bool
    {
        return static::pool($config)->setMultiple($data);
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
     * @return mixed The cached data, or null if the data doesn't exist, has expired,
     *  or if there was an error fetching it.
     */
    public static function read(string $key, string $config = 'default'): mixed
    {
        return static::pool($config)->get($key);
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
     * @param iterable $keys An array or Traversable of keys to fetch from the cache
     * @param string $config optional name of the configuration to use. Defaults to 'default'
     * @return iterable An array containing, for each of the given $keys,
     *   the cached data or false if cached data could not be retrieved.
     * @throws \Cake\Cache\Exception\InvalidArgumentException
     */
    public static function readMany(iterable $keys, string $config = 'default'): iterable
    {
        return static::pool($config)->getMultiple($keys);
    }

    /**
     * Increment a number under the key and return incremented value.
     *
     * @param string $key Identifier for the data
     * @param int $offset How much to add
     * @param string $config Optional string configuration name. Defaults to 'default'
     * @return int|false New value, or false if the data doesn't exist, is not integer,
     *    or if there was an error fetching it.
     * @throws \Cake\Cache\Exception\InvalidArgumentException When offset < 0
     */
    public static function increment(string $key, int $offset = 1, string $config = 'default'): int|false
    {
        if ($offset < 0) {
            throw new InvalidArgumentException('Offset cannot be less than `0`.');
        }

        return static::pool($config)->increment($key, $offset);
    }

    /**
     * Decrement a number under the key and return decremented value.
     *
     * @param string $key Identifier for the data
     * @param int $offset How much to subtract
     * @param string $config Optional string configuration name. Defaults to 'default'
     * @return int|false New value, or false if the data doesn't exist, is not integer,
     *   or if there was an error fetching it
     * @throws \Cake\Cache\Exception\InvalidArgumentException when offset < 0
     */
    public static function decrement(string $key, int $offset = 1, string $config = 'default'): int|false
    {
        if ($offset < 0) {
            throw new InvalidArgumentException('Offset cannot be less than `0`.');
        }

        return static::pool($config)->decrement($key, $offset);
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
    public static function delete(string $key, string $config = 'default'): bool
    {
        return static::pool($config)->delete($key);
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
     * @param iterable $keys Array or Traversable of cache keys to be deleted
     * @param string $config name of the configuration to use. Defaults to 'default'
     * @return bool True on success, false on failure.
     * @throws \Cake\Cache\Exception\InvalidArgumentException
     */
    public static function deleteMany(iterable $keys, string $config = 'default'): bool
    {
        return static::pool($config)->deleteMultiple($keys);
    }

    /**
     * Delete all keys from the cache.
     *
     * @param string $config name of the configuration to use. Defaults to 'default'
     * @return bool True if the cache was successfully cleared, false otherwise
     */
    public static function clear(string $config = 'default'): bool
    {
        return static::pool($config)->clear();
    }

    /**
     * Delete all keys from the cache from all configurations.
     *
     * @return array<string, bool> Status code. For each configuration, it reports the status of the operation
     */
    public static function clearAll(): array
    {
        $status = [];

        foreach (self::configured() as $config) {
            $status[$config] = self::clear($config);
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
    public static function clearGroup(string $group, string $config = 'default'): bool
    {
        return static::pool($config)->clearGroup($group);
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
     * @param string|null $group Group name or null to retrieve all group mappings
     * @return array<string, array> Map of group and all configuration that has the same group
     * @throws \Cake\Cache\Exception\InvalidArgumentException
     */
    public static function groupConfigs(?string $group = null): array
    {
        foreach (static::configured() as $config) {
            static::pool($config);
        }
        if ($group === null) {
            return static::$_groups;
        }

        if (isset(self::$_groups[$group])) {
            return [$group => self::$_groups[$group]];
        }

        throw new InvalidArgumentException(sprintf('Invalid cache group `%s`.', $group));
    }

    /**
     * Re-enable caching.
     *
     * If caching has been disabled with Cache::disable() this method will reverse that effect.
     *
     * @return void
     */
    public static function enable(): void
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
    public static function disable(): void
    {
        static::$_enabled = false;
    }

    /**
     * Check whether caching is enabled.
     *
     * @return bool
     */
    public static function enabled(): bool
    {
        return static::$_enabled;
    }

    /**
     * Provides the ability to easily do read-through caching.
     *
     * If the key is not set, the default callback is run to get the default value.
     * The results will then be stored into the cache config
     * at key.
     *
     * Examples:
     *
     * Using a Closure to provide data, assume `$this` is a Table object:
     *
     * ```
     * $results = Cache::remember('all_articles', function () {
     *      return $this->find('all')->toArray();
     * });
     * ```
     *
     * @param string $key The cache key to read/store data at.
     * @param \Closure $default The callback that provides data in the case when
     *   the cache key is empty.
     * @param string $config The cache configuration to use for this operation.
     *   Defaults to default.
     * @return mixed If the key is found: the cached data.
     *   If the key is not found the value returned by the the default callback.
     */
    public static function remember(string $key, Closure $default, string $config = 'default'): mixed
    {
        $existing = self::read($key, $config);
        if ($existing !== null) {
            return $existing;
        }
        $results = $default();
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
    public static function add(string $key, mixed $value, string $config = 'default'): bool
    {
        if (is_resource($value)) {
            return false;
        }

        return static::pool($config)->add($key, $value);
    }
}
