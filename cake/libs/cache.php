<?php
/**
 * Caching for CakePHP.
 *
 *
 * PHP 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       cake.libs
 * @since         CakePHP(tm) v 1.2.0.4933
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

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
 * @package    cake.libs
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
 * To create a new configuration:
 *
 * `Cache::config('my_config', array('engine' => 'File', 'path' => TMP));`
 *
 * To get the settings for a configuration, and set it as the currently selected configuration
 *
 * `Cache::config('default');`
 *
 * @see app/config/core.php for configuration settings
 * @param string $name Name of the configuration
 * @param array $settings Optional associative array of settings passed to the engine
 * @return array(engine, settings) on success, false on failure
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
 * @return void
 */
	protected static function _buildEngine($name) {
		$config = self::$_config[$name];

		list($plugin, $class) = pluginSplit($config['engine']);
		$cacheClass = $class . 'Engine';
		if (!class_exists($cacheClass) && self::_loadEngine($class, $plugin) === false) {
			return false;
		}
		$cacheClass = $class . 'Engine';
		if (!is_subclass_of($cacheClass, 'CacheEngine')) {
			throw new CacheException(__('Cache engines must use CacheEngine as a base class.'));
		}
		self::$_engines[$name] = new $cacheClass();
		if (self::$_engines[$name]->init($config)) {
			if (time() % self::$_engines[$name]->settings['probability'] === 0) {
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
 * @return boolen success of the removal, returns false when the config does not exist.
 */
	public static function drop($name) {
		if (!isset(self::$_config[$name])) {
			return false;
		}
		unset(self::$_config[$name], self::$_engines[$name]);
		return true;
	}

/**
 * Tries to find and include a file for a cache engine and returns object instance
 *
 * @param $name Name of the engine (without 'Engine')
 * @return mixed $engine object or null
 */
	protected static function _loadEngine($name, $plugin = null) {
		if ($plugin) {
			return App::import('Lib', $plugin . '.cache' . DS . $name, false);
		} else {
			$core = App::core();
			$path = $core['libs'][0] . 'cache' . DS . strtolower($name) . '.php';
			if (file_exists($path)) {
				require $path;
				return true;
			}
			return App::import('Lib', 'cache' . DS . $name, false);
		}
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
 * @param mixed $settings Optional string for simple name-value pair or array
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
 * @param string $config The config name you wish to have garbage collected. Defaults to 'default'
 * @return void
 */
	public static function gc($config = 'default') {
		self::$_engines[$config]->gc();
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
			return null;
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
				__("%s cache was unable to write '%s' to cache", $config, $key),
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
			return null;
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
			return null;
		}
		if (!self::isInitialized($config)) {
			return false;
		}
		$key = self::$_engines[$config]->key($key);

		if (!$key || !is_integer($offset) || $offset < 0) {
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
 * @param integer $offset How much to substract
 * @param string $config Optional string configuration name. Defaults to 'default' 
 * @return mixed new value, or false if the data doesn't exist, is not integer,
 *   or if there was an error fetching it
 */
	public static function decrement($key, $offset = 1, $config = 'default') {
		$settings = self::settings($config);

		if (empty($settings)) {
			return null;
		}
		if (!self::isInitialized($config)) {
			return false;
		}
		$key = self::$_engines[$config]->key($key);

		if (!$key || !is_integer($offset) || $offset < 0) {
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
 * @return boolean True if the value was succesfully deleted, false if it didn't exist or couldn't be removed
 */
	public static function delete($key, $config = 'default') {
		$settings = self::settings($config);

		if (empty($settings)) {
			return null;
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
 * @return boolean True if the cache was succesfully cleared, false otherwise
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
 * Check if Cache has initialized a working config for the given name.
 *
 * @param string $engine Name of the engine, Defaults to default
 * @param string $config Name of the configuration setting
 * @return bool Whether or not the config name has been initialized.
 */
	public static function isInitialized($name = 'default') {
		if (Configure::read('Cache.disable')) {
			return false;
		}
		return isset(self::$_engines[$name]);
	}

/**
 * Return the settings for the named cache engine.
 *
 * @param string $engine Name of the configuration to get settings for. Defaults to 'default'
 * @return array list of settings for this engine
 * @see Cache::config()
 * @access public
 * @static
 */
	public static function settings($name = 'default') {
		if (!empty(self::$_engines[$name])) {
			return self::$_engines[$name]->settings();
		}
		return array();
	}
}

/**
 * Storage engine for CakePHP caching
 *
 * @package       cake.libs
 */
abstract class CacheEngine {

/**
 * Settings of current engine instance
 *
 * @var int
 * @access public
 */
	public $settings = array();

/**
 * Initialize the cache engine
 *
 * Called automatically by the cache frontend
 *
 * @param array $params Associative array of parameters for the engine
 * @return boolean True if the engine has been succesfully initialized, false if not
 */
	public function init($settings = array()) {
		$this->settings = array_merge(
			array('prefix' => 'cake_', 'duration'=> 3600, 'probability'=> 100),
			$this->settings,
			$settings
		);
		if (!is_numeric($this->settings['duration'])) {
			$this->settings['duration'] = strtotime($this->settings['duration']) - time();
		}
		return true;
	}

/**
 * Garbage collection
 *
 * Permanently remove all expired and deleted data
 * @return void
 */
	public function gc() { }

/**
 * Write value for a key into cache
 *
 * @param string $key Identifier for the data
 * @param mixed $value Data to be cached
 * @param mixed $duration How long to cache for.
 * @return boolean True if the data was succesfully cached, false on failure
 */
	abstract public function write($key, $value, $duration);

/**
 * Read a key from the cache
 *
 * @param string $key Identifier for the data
 * @return mixed The cached data, or false if the data doesn't exist, has expired, or if there was an error fetching it
 */
	abstract public function read($key);

/**
 * Increment a number under the key and return incremented value
 *
 * @param string $key Identifier for the data
 * @param integer $offset How much to add
 * @return New incremented value, false otherwise
 */
	abstract public function increment($key, $offset = 1);

/**
 * Decrement a number under the key and return decremented value
 *
 * @param string $key Identifier for the data
 * @param integer $value How much to substract
 * @return New incremented value, false otherwise
 */
	abstract public function decrement($key, $offset = 1);

/**
 * Delete a key from the cache
 *
 * @param string $key Identifier for the data
 * @return boolean True if the value was succesfully deleted, false if it didn't exist or couldn't be removed
 */
	abstract public function delete($key);

/**
 * Delete all keys from the cache
 *
 * @param boolean $check if true will check expiration, otherwise delete all
 * @return boolean True if the cache was succesfully cleared, false otherwise
 */
	abstract public function clear($check);

/**
 * Cache Engine settings
 *
 * @return array settings
 */
	public function settings() {
		return $this->settings;
	}

/**
 * Generates a safe key for use with cache engine storage engines.
 *
 * @param string $key the key passed over
 * @return mixed string $key or false
 */
	public function key($key) {
		if (empty($key)) {
			return false;
		}
		$key = Inflector::underscore(str_replace(array(DS, '/', '.'), '_', strval($key)));
		return $key;
	}
}
