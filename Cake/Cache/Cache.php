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
 * @since         CakePHP(tm) v 1.2.0.4933
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Cache;

use Cake\Core\App;
use Cake\Core\Configure;
use Cake\Core\StaticConfigTrait;
use Cake\Error;
use Cake\Utility\Inflector;

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
 * {{{
 * Cache::config('shared', array(
 *    'engine' => 'Cake\Cache\Engine\ApcEngine',
 *    'prefix' => 'my_app_'
 * ));
 * }}}
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
 * The following keys are used in core cache engines:
 *
 * - `duration` Specify how long items in this cache configuration last.
 * - `groups` List of groups or 'tags' associated to every key stored in this config.
 *    handy for deleting a complete group from cache.
 * - `prefix` Prefix appended to all entries. Good for when you need to share a keyspace
 *    with either another cache config or another application.
 * - `probability` Probability of hitting a cache gc cleanup. Setting to 0 will disable
 *    cache::gc from ever being called automatically.
 * - `servers' Used by memcache. Give the address of the memcached servers to use.
 * - `compress` Used by memcache. Enables memcache's compressed format.
 * - `serialize` Used by FileCache. Should cache objects be serialized first.
 * - `path` Used by FileCache. Path to where cachefiles should be saved.
 * - `lock` Used by FileCache. Should files be locked before writing to them?
 * - `user` Used by Xcache. Username for XCache
 * - `password` Used by Xcache/Redis. Password for XCache/Redis
 *
 * @see app/Config/core.php for configuration settings
 * @param string $name Name of the configuration
 * @param array $settings Optional associative array of settings passed to the engine
 * @return array array(engine, settings) on success, false on failure
 * @throws Cake\Error\Exception
 */
class Cache {

	use StaticConfigTrait;

/**
 * Flag for tracking whether or not caching is enabled.
 *
 * @var boolean
 */
	protected static $_enabled = true;

/**
 * Cache configuration.
 *
 * Keeps the permanent/default settings for each cache engine.
 * These settings are used to reset the engines after temporary modification.
 *
 * @var array
 */
	protected static $_config = array();

/**
 * Group to Config mapping
 *
 * @var array
 */
	protected static $_groups = array();

/**
 * Whether to reset the settings with the next call to Cache::set();
 *
 * @var array
 */
	protected static $_reset = false;

/**
 * Cache Registry used for creating and using cache adapters.
 *
 * @var Cake\Cache\CacheRegistry
 */
	protected static $_registry;

/**
 * Finds and builds the instance of the required engine class.
 *
 * @param string $name Name of the config array that needs an engine instance built
 * @throws Cake\Error\Exception When a cache engine cannot be created.
 */
	protected static function _buildEngine($name) {
		if (empty(static::$_registry)) {
			static::$_registry = new CacheRegistry();
		}
		if (empty(static::$_config[$name]['className'])) {
			throw new Error\Exception(__d('cake_dev', 'The "%s" cache configuration does not exist.', $name));
		}

		$config = static::$_config[$name];
		static::$_registry->load($name, $config);

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
 * @return Cake\Cache\Engine
 */
	public static function engine($config) {
		if (!static::$_enabled) {
			return false;
		}

		if (isset(static::$_registry->{$config})) {
			return static::$_registry->{$config};
		}

		static::_buildEngine($config);
		return static::$_registry->{$config};
	}

/**
 * Garbage collection
 *
 * Permanently remove all expired and deleted data
 *
 * @param string $config [optional] The config name you wish to have garbage collected. Defaults to 'default'
 * @param integer $expires [optional] An expires timestamp. Defaults to NULL
 * @return void
 */
	public static function gc($config = 'default', $expires = null) {
		$engine = static::engine($config);
		if (!$engine) {
			return;
		}

		$engine->gc($expires);
	}

/**
 * Write data for key into cache.
 *
 * ### Usage:
 *
 * Writing to the active cache config:
 *
 * `Cache::write('cached_data', $data);`
 *
 * Writing to a specific cache config:
 *
 * `Cache::write('cached_data', $data, 'long_term');`
 *
 * @param string $key Identifier for the data
 * @param mixed $value Data to be cached - anything except a resource
 * @param string $config Optional string configuration name to write to. Defaults to 'default'
 * @return boolean True if the data was successfully cached, false on failure
 */
	public static function write($key, $value, $config = 'default') {
		$engine = static::engine($config);
		$settings = static::settings($config);

		if (!$engine) {
			return false;
		}
		$key = $engine->key($key);

		if (!$key || is_resource($value)) {
			return false;
		}
		$success = $engine->write($settings['prefix'] . $key, $value, $settings['duration']);
		if ($success === false && $value !== '') {
			trigger_error(
				__d('cake_dev',
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
 * Read a key from the cache.
 *
 * ### Usage:
 *
 * Reading from the active cache configuration.
 *
 * `Cache::read('my_data');`
 *
 * Reading from a specific cache configuration.
 *
 * `Cache::read('my_data', 'long_term');`
 *
 * @param string $key Identifier for the data
 * @param string $config optional name of the configuration to use. Defaults to 'default'
 * @return mixed The cached data, or false if the data doesn't exist, has expired, or if there was an error fetching it
 */
	public static function read($key, $config = 'default') {
		$engine = static::engine($config);
		$settings = static::settings($config);
		if (!$engine) {
			return false;
		}

		$key = $engine->key($key);
		if (!$key) {
			return false;
		}

		return $engine->read($settings['prefix'] . $key);
	}

/**
 * Increment a number under the key and return incremented value.
 *
 * @param string $key Identifier for the data
 * @param integer $offset How much to add
 * @param string $config Optional string configuration name. Defaults to 'default'
 * @return mixed new value, or false if the data doesn't exist, is not integer,
 *    or if there was an error fetching it.
 */
	public static function increment($key, $offset = 1, $config = 'default') {
		$engine = static::engine($config);
		$settings = static::settings($config);

		if (!$engine) {
			return false;
		}
		$key = $engine->key($key);

		if (!$key || !is_int($offset) || $offset < 0) {
			return false;
		}
		return $engine->increment($settings['prefix'] . $key, $offset);
	}

/**
 * Decrement a number under the key and return decremented value.
 *
 * @param string $key Identifier for the data
 * @param integer $offset How much to subtract
 * @param string $config Optional string configuration name. Defaults to 'default'
 * @return mixed new value, or false if the data doesn't exist, is not integer,
 *   or if there was an error fetching it
 */
	public static function decrement($key, $offset = 1, $config = 'default') {
		$engine = static::engine($config);
		$settings = static::settings($config);

		if (!$engine) {
			return false;
		}
		$key = $engine->key($key);

		if (!$key || !is_int($offset) || $offset < 0) {
			return false;
		}
		return $engine->decrement($settings['prefix'] . $key, $offset);
	}

/**
 * Delete a key from the cache.
 *
 * ### Usage:
 *
 * Deleting from the active cache configuration.
 *
 * `Cache::delete('my_data');`
 *
 * Deleting from a specific cache configuration.
 *
 * `Cache::delete('my_data', 'long_term');`
 *
 * @param string $key Identifier for the data
 * @param string $config name of the configuration to use. Defaults to 'default'
 * @return boolean True if the value was successfully deleted, false if it didn't exist or couldn't be removed
 */
	public static function delete($key, $config = 'default') {
		$settings = static::settings($config);
		$engine = static::engine($config);

		if (!$engine) {
			return false;
		}

		$key = $engine->key($key);
		if (!$key) {
			return false;
		}

		return $engine->delete($settings['prefix'] . $key);
	}

/**
 * Delete all keys from the cache.
 *
 * @param boolean $check if true will check expiration, otherwise delete all
 * @param string $config name of the configuration to use. Defaults to 'default'
 * @return boolean True if the cache was successfully cleared, false otherwise
 */
	public static function clear($check = false, $config = 'default') {
		$engine = static::engine($config);
		if (!$engine) {
			return false;
		}

		return $engine->clear($check);
	}

/**
 * Delete all keys from the cache belonging to the same group.
 *
 * @param string $group name of the group to be cleared
 * @param string $config name of the configuration to use. Defaults to 'default'
 * @return boolean True if the cache group was successfully cleared, false otherwise
 */
	public static function clearGroup($group, $config = 'default') {
		$engine = static::engine($config);
		if (!$engine) {
			return false;
		}

		$success = $engine->clearGroup($group);
		return $success;
	}

/**
 * Return the settings for the named cache engine.
 *
 * @param string $name Name of the configuration to get settings for. Defaults to 'default'
 * @return array list of settings for this engine
 * @see Cache::config()
 */
	public static function settings($config = 'default') {
		$engine = static::engine($config);
		if (!$engine) {
			return [];
		}

		return $engine->settings();
	}

/**
 * Retrieve group names to config mapping.
 *
 * {{{
 *	Cache::config('daily', ['duration' => '1 day', 'groups' => ['posts']]);
 *	Cache::config('weekly', ['duration' => '1 week', 'groups' => ['posts', 'archive']]);
 *	$configs = Cache::groupConfigs('posts');
 * }}}
 *
 * $config will equal to `['posts' => ['daily', 'weekly']]`
 *
 * @param string $group group name or null to retrieve all group mappings
 * @return array map of group and all configuration that has the same group
 * @throws Cake\Error\Exception
 */
	public static function groupConfigs($group = null) {
		if ($group == null) {
			return static::$_groups;
		}

		if (isset(self::$_groups[$group])) {
			return array($group => self::$_groups[$group]);
		}

		throw new Error\Exception(__d('cake_dev', 'Invalid cache group %s', $group));
	}

/**
 * Re-enable caching.
 *
 * If caching has been disabled with Cache::disable() this method will reverse that effect.
 *
 * @return void
 */
	public static function enable() {
		static::$_enabled = true;
	}

/**
 * Disable caching.
 *
 * When disabled all cache operations will return null.
 *
 * @return void
 */
	public static function disable() {
		static::$_enabled = false;
	}

/**
 * Check whether or not caching is enabled.
 *
 * @return boolean
 */
	public static function enabled() {
		return static::$_enabled;
	}

}
