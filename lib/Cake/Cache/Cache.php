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
 * @package       Cake.Cache
 * @since         CakePHP(tm) v 1.2.0.4933
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Cache;

use Cake\Core\App;
use Cake\Core\Configure;
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

/**
 * Configuraiton backup.
 *
 * Keeps the permanent/default settings for each cache engine.
 * These settings are used to reset the engines after temporary modification.
 *
 * @var array
 */
	protected static $_restore = array();

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
 * This method can be used to define cache adapters for an application
 * during the bootstrapping process. You can use this method to add new cache adapters
 * at runtime as well. New cache configurations will be constructed upon the next write.
 *
 * To change an adapter's configuration at runtime, first drop the adapter and then
 * reconfigure it.
 *
 * Adapters will not be constructed until the first operation is done.
 *
 * @param string|array $key The name of the cache config, or an array of multiple configs.
 * @param array $config An array of name => config data for adapter.
 * @return void
 */
	public static function config($key, $config = null) {
		if ($config !== null && is_string($key)) {
			static::$_config[$key] = $config;
			return;
		}
		static::$_config = array_merge(static::$_config, $key);
	}

/**
 * Finds and builds the instance of the required engine class.
 *
 * @param string $name Name of the config array that needs an engine instance built
 * @return boolean
 * @throws Cake\Error\Exception
 */
	protected static function _buildEngine($name) {
		if (empty(static::$_registry)) {
			static::$_registry = new CacheRegistry();
		}
		if (empty(static::$_config[$name]['engine'])) {
			return false;
		}
		$config = static::$_config[$name];
		$config['className'] = $config['engine'];

		static::$_registry->load($name, $config);

		if (!empty($config['groups'])) {
			foreach ($config['groups'] as $group) {
				static::$_groups[$group][] = $name;
				static::$_groups[$group] = array_unique(static::$_groups[$group]);
				sort(static::$_groups[$group]);
			}
		}

		return true;
	}

/**
 * Returns an array containing the configured Cache engines.
 *
 * @return array Array of configured Cache config names.
 */
	public static function configured() {
		return array_keys(static::$_config);
	}

/**
 * Drops a constructed cache engine.
 *
 * The engine's configuration will remain in Configure. If you wish to re-configure a
 * cache engine you should drop it, change configuration and then re-use it.
 *
 * @param string $config A currently configured cache config you wish to remove.
 * @return boolean success of the removal, returns false when the config does not exist.
 */
	public static function drop($config) {
		if (!isset(static::$_registry->{$config})) {
			return false;
		}
		static::$_registry->unload($config);
		unset(static::$_config[$config], static::$_restore[$config]);
		return true;
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
		if (Configure::read('Cache.disable')) {
			return false;
		}
		if (isset(static::$_registry->{$config})) {
			return static::$_registry->{$config};
		}
		if (!static::_buildEngine($config)) {
			$message = __d(
				'cake_dev',
				'The "%s" cache configuration does not exist.',
				$config
			);
			trigger_error($message, E_USER_WARNING);
		}
		return static::$_registry->{$config};
	}

/**
 * Temporarily change the settings on a cache config. The settings will persist for the next write
 * operation (write, decrement, increment, clear). Any reads that are done before the write, will
 * use the modified settings. If `$settings` is empty, the settings will be reset to the
 * original configuration.
 *
 * Can be called with 2 or 3 parameters. To set multiple values at once.
 *
 * `Cache::set(array('duration' => '+30 minutes'), 'my_config');`
 *
 * Or to set one value.
 *
 * `Cache::set('duration', '+30 minutes', 'my_config');`
 *
 * To reset a config back to the originally configured values.
 *
 * `Cache::set(null, 'my_config');`
 *
 * @param string|array $settings Optional string for simple name-value pair or array
 * @param string $value Optional for a simple name-value pair. Otherwise this param
 *   should be used for the cache config name.
 * @param string $config The configuration name you are changing. Defaults to 'default'
 * @return array Array of settings.
 */
	public static function set($settings = array(), $value = null, $config = 'default') {
		$args = func_num_args();
		if ($args === 2) {
			$config = $value;
		}
		if ($args === 3) {
			$settings = array($settings => $value);
		}

		$engine = static::engine($config);
		if (!$engine) {
			return false;
		}

		if (empty(static::$_restore[$config])) {
			static::$_restore[$config] = $engine->settings();
		}

		if (!empty($settings)) {
			static::$_reset = true;
		}

		if (static::$_reset === true) {
			static::_modifySettings($engine, $config, $settings);
		}
		return static::settings($config);
	}

/**
 * Used to temporarily modify the settings of a caching engine.
 * If $settings is empty the previous settings values will be restored.
 *
 * @param Cake\Cache\CacheEngine $engine The engine to modify
 * @param array $settings The settings to temporarily set.
 * @return void
 */
	protected static function _modifySettings($engine, $config, $settings) {
		$restore = static::$_restore[$config];
		if (empty($settings)) {
			static::$_reset = false;
			$settings = $restore;
			unset(static::$_restore[$config]);
		} else {
			$settings = array_merge($restore, $settings);
			if (isset($settings['duration']) && !is_numeric($settings['duration'])) {
				$settings['duration'] = strtotime($settings['duration']) - time();
			}
		}
		$engine->settings = $settings;
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
		static::set(null, $config);
		if ($success === false && $value !== '') {
			trigger_error(
				__d('cake_dev',
					"%s cache was unable to write '%s' to %s cache",
					$config,
					$key,
					$settings['engine']
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
		$success = $engine->increment($settings['prefix'] . $key, $offset);
		static::set(null, $config);
		return $success;
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
		$success = $engine->decrement($settings['prefix'] . $key, $offset);
		static::set(null, $config);
		return $success;
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

		$success = $engine->delete($settings['prefix'] . $key);
		static::set(null, $config);
		return $success;
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
		$success = $engine->clear($check);
		static::set(null, $config);
		return $success;
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
		static::set(null, $config);
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

}
