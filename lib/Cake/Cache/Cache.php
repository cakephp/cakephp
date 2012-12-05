<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       Cake.Cache
 * @since         CakePHP(tm) v 1.2.0.4933
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

App::uses('Inflector', 'Utility');
App::uses('CacheEngine', 'Cache');

/**
 * Cache provides a consistent interface to Caching in your application. It allows you
 * to use several different Cache engines, without coupling your application to a specific
 * implementation.  It also allows you to change out cache storage or configuration without effecting
 * the rest of your application.
 *
 * You can configure Cache engines in your application's `bootstrap.php` file.  A sample configuration would
 * be
 *
 * {{{
 *	Cache::config('shared', array(
 *		'engine' => 'Apc',
 *		'prefix' => 'my_app_'
 *  ));
 * }}}
 *
 * This would configure an APC cache engine to the 'shared' alias.  You could then read and write
 * to that cache alias by using it for the `$config` parameter in the various Cache methods.  In
 * general all Cache operations are supported by all cache engines.  However, Cache::increment() and
 * Cache::decrement() are not supported by File caching.
 *
 * @package       Cake.Cache
 */
class Cache {

/**
 * Cache configuration stack
 * Keeps the permanent/default settings for each cache engine.
 * These settings are used to reset the engines after temporary modification.
 *
 * @var array
 */
	protected static $_config = array();

/**
 * Whether to reset the settings with the next call to Cache::set();
 *
 * @var array
 */
	protected static $_reset = false;

/**
 * Engine instances keyed by configuration name.
 *
 * @var array
 */
	protected static $_engines = array();

/**
 * Set the cache configuration to use.  config() can
 * both create new configurations, return the settings for already configured
 * configurations.
 *
 * To create a new configuration, or to modify an existing configuration permanently:
 *
 * `Cache::config('my_config', array('engine' => 'File', 'path' => TMP));`
 *
 * If you need to modify a configuration temporarily, use Cache::set().
 * To get the settings for a configuration:
 *
 * `Cache::config('default');`
 *
 * There are 5 built-in caching engines:
 *
 * - `FileEngine` - Uses simple files to store content. Poor performance, but good for
 *    storing large objects, or things that are not IO sensitive.
 * - `ApcEngine` - Uses the APC object cache, one of the fastest caching engines.
 * - `MemcacheEngine` - Uses the PECL::Memcache extension and Memcached for storage.
 *   Fast reads/writes, and benefits from memcache being distributed.
 * - `XcacheEngine` - Uses the Xcache extension, an alternative to APC.
 * - `WincacheEngine` - Uses Windows Cache Extension for PHP. Supports wincache 1.1.0 and higher.
 *
 * The following keys are used in core cache engines:
 *
 * - `duration` Specify how long items in this cache configuration last.
 * - `groups` List of groups or 'tags' associated to every key stored in this config.
 *    handy for deleting a complete group from cache.
 * - `prefix` Prefix appended to all entries. Good for when you need to share a keyspace
 *    with either another cache config or another application.
 * - `probability` Probability of hitting a cache gc cleanup.  Setting to 0 will disable
 *    cache::gc from ever being called automatically.
 * - `servers' Used by memcache. Give the address of the memcached servers to use.
 * - `compress` Used by memcache.  Enables memcache's compressed format.
 * - `serialize` Used by FileCache.  Should cache objects be serialized first.
 * - `path` Used by FileCache.  Path to where cachefiles should be saved.
 * - `lock` Used by FileCache.  Should files be locked before writing to them?
 * - `user` Used by Xcache.  Username for XCache
 * - `password` Used by Xcache/Redis.  Password for XCache/Redis
 *
 * @see app/Config/core.php for configuration settings
 * @param string $name Name of the configuration
 * @param array $settings Optional associative array of settings passed to the engine
 * @return array array(engine, settings) on success, false on failure
 * @throws CacheException
 */
	public static function config($name = null, $settings = array()) {
		if (is_array($name)) {
			$settings = $name;
		}

		$current = array();
		if (isset(self::$_config[$name])) {
			$current = self::$_config[$name];
		}

		if (!empty($settings)) {
			self::$_config[$name] = array_merge($current, $settings);
		}

		if (empty(self::$_config[$name]['engine'])) {
			return false;
		}

		$engine = self::$_config[$name]['engine'];

		if (!isset(self::$_engines[$name])) {
			self::_buildEngine($name);
			$settings = self::$_config[$name] = self::settings($name);
		} elseif ($settings = self::set(self::$_config[$name], null, $name)) {
			self::$_config[$name] = $settings;
		}
		return compact('engine', 'settings');
	}

/**
 * Finds and builds the instance of the required engine class.
 *
 * @param string $name Name of the config array that needs an engine instance built
 * @return boolean
 * @throws CacheException
 */
	protected static function _buildEngine($name) {
		$config = self::$_config[$name];

		list($plugin, $class) = pluginSplit($config['engine'], true);
		$cacheClass = $class . 'Engine';
		App::uses($cacheClass, $plugin . 'Cache/Engine');
		if (!class_exists($cacheClass)) {
			return false;
		}
		$cacheClass = $class . 'Engine';
		if (!is_subclass_of($cacheClass, 'CacheEngine')) {
			throw new CacheException(__d('cake_dev', 'Cache engines must use CacheEngine as a base class.'));
		}
		self::$_engines[$name] = new $cacheClass();
		if (self::$_engines[$name]->init($config)) {
			if (self::$_engines[$name]->settings['probability'] && time() % self::$_engines[$name]->settings['probability'] === 0) {
				self::$_engines[$name]->gc();
			}
			return true;
		}
		return false;
	}

/**
 * Returns an array containing the currently configured Cache settings.
 *
 * @return array Array of configured Cache config names.
 */
	public static function configured() {
		return array_keys(self::$_config);
	}

/**
 * Drops a cache engine.  Deletes the cache configuration information
 * If the deleted configuration is the last configuration using an certain engine,
 * the Engine instance is also unset.
 *
 * @param string $name A currently configured cache config you wish to remove.
 * @return boolean success of the removal, returns false when the config does not exist.
 */
	public static function drop($name) {
		if (!isset(self::$_config[$name])) {
			return false;
		}
		unset(self::$_config[$name], self::$_engines[$name]);
		return true;
	}

/**
 * Temporarily change the settings on a cache config.  The settings will persist for the next write
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
 * @param string $value Optional for a simple name-value pair
 * @param string $config The configuration name you are changing. Defaults to 'default'
 * @return array Array of settings.
 */
	public static function set($settings = array(), $value = null, $config = 'default') {
		if (is_array($settings) && $value !== null) {
			$config = $value;
		}
		if (!isset(self::$_config[$config]) || !isset(self::$_engines[$config])) {
			return false;
		}
		if (!empty($settings)) {
			self::$_reset = true;
		}

		if (self::$_reset === true) {
			if (empty($settings)) {
				self::$_reset = false;
				$settings = self::$_config[$config];
			} else {
				if (is_string($settings) && $value !== null) {
					$settings = array($settings => $value);
				}
				$settings = array_merge(self::$_config[$config], $settings);
				if (isset($settings['duration']) && !is_numeric($settings['duration'])) {
					$settings['duration'] = strtotime($settings['duration']) - time();
				}
			}
			self::$_engines[$config]->settings = $settings;
		}
		return self::settings($config);
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
		self::$_engines[$config]->gc($expires);
	}

/**
 * Write data for key into cache. Will automatically use the currently
 * active cache configuration.  To set the currently active configuration use
 * Cache::config()
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
		$settings = self::settings($config);

		if (empty($settings)) {
			return false;
		}
		if (!self::isInitialized($config)) {
			return false;
		}
		$key = self::$_engines[$config]->key($key);

		if (!$key || is_resource($value)) {
			return false;
		}

		$success = self::$_engines[$config]->write($settings['prefix'] . $key, $value, $settings['duration']);
		self::set(null, $config);
		if ($success === false && $value !== '') {
			trigger_error(
				__d('cake_dev',
					"%s cache was unable to write '%s' to %s cache",
					$config,
					$key,
					self::$_engines[$config]->settings['engine']
				),
				E_USER_WARNING
			);
		}
		return $success;
	}

/**
 * Read a key from the cache.  Will automatically use the currently
 * active cache configuration.  To set the currently active configuration use
 * Cache::config()
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
		$settings = self::settings($config);

		if (empty($settings)) {
			return false;
		}
		if (!self::isInitialized($config)) {
			return false;
		}
		$key = self::$_engines[$config]->key($key);
		if (!$key) {
			return false;
		}
		return self::$_engines[$config]->read($settings['prefix'] . $key);
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
		$settings = self::settings($config);

		if (empty($settings)) {
			return false;
		}
		if (!self::isInitialized($config)) {
			return false;
		}
		$key = self::$_engines[$config]->key($key);

		if (!$key || !is_int($offset) || $offset < 0) {
			return false;
		}
		$success = self::$_engines[$config]->increment($settings['prefix'] . $key, $offset);
		self::set(null, $config);
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
		$settings = self::settings($config);

		if (empty($settings)) {
			return false;
		}
		if (!self::isInitialized($config)) {
			return false;
		}
		$key = self::$_engines[$config]->key($key);

		if (!$key || !is_int($offset) || $offset < 0) {
			return false;
		}
		$success = self::$_engines[$config]->decrement($settings['prefix'] . $key, $offset);
		self::set(null, $config);
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
		$settings = self::settings($config);

		if (empty($settings)) {
			return false;
		}
		if (!self::isInitialized($config)) {
			return false;
		}
		$key = self::$_engines[$config]->key($key);
		if (!$key) {
			return false;
		}

		$success = self::$_engines[$config]->delete($settings['prefix'] . $key);
		self::set(null, $config);
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
		if (!self::isInitialized($config)) {
			return false;
		}
		$success = self::$_engines[$config]->clear($check);
		self::set(null, $config);
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
		if (!self::isInitialized($config)) {
			return false;
		}
		$success = self::$_engines[$config]->clearGroup($group);
		self::set(null, $config);
		return $success;
	}

/**
 * Check if Cache has initialized a working config for the given name.
 *
 * @param string $config name of the configuration to use. Defaults to 'default'
 * @return boolean Whether or not the config name has been initialized.
 */
	public static function isInitialized($config = 'default') {
		if (Configure::read('Cache.disable')) {
			return false;
		}
		return isset(self::$_engines[$config]);
	}

/**
 * Return the settings for the named cache engine.
 *
 * @param string $name Name of the configuration to get settings for. Defaults to 'default'
 * @return array list of settings for this engine
 * @see Cache::config()
 */
	public static function settings($name = 'default') {
		if (!empty(self::$_engines[$name])) {
			return self::$_engines[$name]->settings();
		}
		return array();
	}

}
