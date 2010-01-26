<?php
/**
 * Caching for CakePHP.
 *
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       cake
 * @subpackage    cake.cake.libs
 * @since         CakePHP(tm) v 1.2.0.4933
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

/**
 * Caching for CakePHP.
 *
 * @package       cake
 * @subpackage    cake.cake.libs
 */
class Cache {

/**
 * Cache configuration stack
 * Keeps the permanent/default settings for each cache engine.
 * These settings are used to reset the engines after temporary modification.
 *
 * @var array
 * @access private
 */
	var $__config = array();

/**
 * Holds name of the current configuration name being used.
 *
 * @var array
 * @access private
 */
	var $__name = 'default';

/**
 * Whether to reset the settings with the next call to Cache::set();
 *
 * @var array
 * @access private
 */
	var $__reset = false;

/**
 * Engine instances keyed by configuration name.
 *
 * @var array
 */
	var $_engines = array();

/**
 * Returns a singleton instance
 *
 * @return object
 * @access public
 * @static
 */
	function &getInstance() {
		static $instance = array();
		if (!$instance) {
			$instance[0] =& new Cache();
		}
		return $instance[0];
	}

/**
 * Set the cache configuration to use.  config() can
 * both create new configurations, return the settings for already configured
 * configurations.  It also sets the 'default' configuration to use for subsequent
 * operations.
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
 * @access public
 * @static
 */
	function config($name = null, $settings = array()) {
		$self =& Cache::getInstance();
		if (is_array($name)) {
			$settings = $name;
		}

		if ($name === null || !is_string($name)) {
			$name = $self->__name;
		}

		$current = array();
		if (isset($self->__config[$name])) {
			$current = $self->__config[$name];
		}

		if (!empty($settings)) {
			$self->__config[$name] = array_merge($current, $settings);
		}

		if (empty($self->__config[$name]['engine'])) {
			return false;
		}

		$engine = $self->__config[$name]['engine'];
		$self->__name = $name;

		if (!isset($self->_engines[$name])) {
			$self->_buildEngine($name);
			$settings = $self->__config[$name] = $self->settings($name);
		} elseif ($settings = $self->set($self->__config[$name])) {
			$self->__config[$name] = $settings;
		}
		return compact('engine', 'settings');
	}

/**
 * Finds and builds the instance of the required engine class.
 *
 * @param string $name Name of the config array that needs an engine instance built
 * @return void
 * @access protected
 */
	function _buildEngine($name) {
		$config = $this->__config[$name];

		list($plugin, $class) = pluginSplit($config['engine']);
		$cacheClass = $class . 'Engine';
		if (!class_exists($cacheClass) && $this->__loadEngine($class, $plugin) === false) {
			return false;
		}
		$cacheClass = $class . 'Engine';
		$this->_engines[$name] =& new $cacheClass();
		if ($this->_engines[$name]->init($config)) {
			if (time() % $this->_engines[$name]->settings['probability'] === 0) {
				$this->_engines[$name]->gc();
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
	function configured() {
		$self = Cache::getInstance();
		return array_keys($self->__config);
	}

/**
 * Drops a cache engine.  Deletes the cache configuration information
 * If the deleted configuration is the last configuration using an certain engine,
 * the Engine instance is also unset.
 *
 * @param string $name A currently configured cache config you wish to remove.
 * @return boolen success of the removal, returns false when the config does not exist.
 */
	function drop($name) {
		$self = Cache::getInstance();
		if (!isset($self->__config[$name])) {
			return false;
		}
		unset($self->__config[$name]);
		unset($self->_engines[$name]);
		return true;
	}

/**
 * Tries to find and include a file for a cache engine and returns object instance
 *
 * @param $name Name of the engine (without 'Engine')
 * @return mixed $engine object or null
 * @access private
 */
	function __loadEngine($name, $plugin = null) {
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
 * Temporarily change settings to current config options. if no params are passed, resets settings if needed
 * Cache::write() will reset the configuration changes made
 *
 * @param mixed $settings Optional string for simple name-value pair or array
 * @param string $value Optional for a simple name-value pair
 * @return array Array of settings.
 * @access public
 * @static
 */
	function set($settings = array(), $value = null) {
		$self =& Cache::getInstance();
		if (!isset($self->__config[$self->__name]) || !isset($self->_engines[$self->__name])) {
			return false;
		}
		$name = $self->__name;
		if (!empty($settings)) {
			$self->__reset = true;
		}

		if ($self->__reset === true) {
			if (empty($settings)) {
				$self->__reset = false;
				$settings = $self->__config[$name];
			} else {
				if (is_string($settings) && $value !== null) {
					$settings = array($settings => $value);
				}
				$settings = array_merge($self->__config[$self->__name], $settings);
				if (isset($settings['duration']) && !is_numeric($settings['duration'])) {
					$settings['duration'] = strtotime($settings['duration']) - time();
				}
			}
			$self->_engines[$name]->settings = $settings;
		}
		return $self->settings($name);
	}

/**
 * Garbage collection
 *
 * Permanently remove all expired and deleted data
 *
 * @return void
 * @access public
 * @static
 */
	function gc() {
		$self =& Cache::getInstance();
		$self->_engines[$self->__name]->gc();
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
 * @param string $config Optional string configuration name to write to.
 * @return boolean True if the data was successfully cached, false on failure
 * @access public
 * @static
 */
	function write($key, $value, $config = null) {
		$self =& Cache::getInstance();

		if (!$config) {
			$config = $self->__name;
		}
		$settings = $self->settings($config);

		if (empty($settings)) {
			return null;
		}
		if (!$self->isInitialized($config)) {
			return false;
		}
		$key = $self->_engines[$config]->key($key);

		if (!$key || is_resource($value) || $settings['duration'] < 1) {
			return false;
		}

		$success = $self->_engines[$config]->write($settings['prefix'] . $key, $value, $settings['duration']);
		$self->set();
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
 * @param string $config optional name of the configuration to use.
 * @return mixed The cached data, or false if the data doesn't exist, has expired, or if there was an error fetching it
 * @access public
 * @static
 */
	function read($key, $config = null) {
		$self =& Cache::getInstance();

		if (!$config) {
			$config = $self->__name;
		}
		$settings = $self->settings($config);

		if (empty($settings)) {
			return null;
		}
		if (!$self->isInitialized($config)) {
			return false;
		}
		$key = $self->_engines[$config]->key($key);
		if (!$key) {
			return false;
		}
		$success = $self->_engines[$config]->read($settings['prefix'] . $key);

		if ($config !== null && $config !== $self->__name) {
			$self->set();
		}
		return $success;
	}

/**
 * Increment a number under the key and return incremented value.
 *
 * @param string $key Identifier for the data
 * @param integer $offset How much to add
 * @param string $config Optional string configuration name.  If not specified the current
 *   default config will be used.
 * @return mixed new value, or false if the data doesn't exist, is not integer,
 *    or if there was an error fetching it.
 * @access public
 */
	function increment($key, $offset = 1, $config = null) {
		$self =& Cache::getInstance();

		if (!$config) {
			$config = $self->__name;
		}
		$settings = $self->settings($config);

		if (empty($settings)) {
			return null;
		}
		if (!$self->isInitialized($config)) {
			return false;
		}
		$key = $self->_engines[$config]->key($key);

		if (!$key || !is_integer($offset) || $offset < 0) {
			return false;
		}
		$success = $self->_engines[$config]->increment($settings['prefix'] . $key, $offset);
		$self->set();
		return $success;
	}
/**
 * Decrement a number under the key and return decremented value.
 *
 * @param string $key Identifier for the data
 * @param integer $offset How much to substract
 * @param string $config Optional string configuration name, if not specified the current
 *   default config will be used.
 * @return mixed new value, or false if the data doesn't exist, is not integer,
 *   or if there was an error fetching it
 * @access public
 */
	function decrement($key, $offset = 1, $config = null) {
		$self =& Cache::getInstance();

		if (!$config) {
			$config = $self->__name;
		}
		$settings = $self->settings($config);

		if (empty($settings)) {
			return null;
		}
		if (!$self->isInitialized($config)) {
			return false;
		}
		$key = $self->_engines[$config]->key($key);

		if (!$key || !is_integer($offset) || $offset < 0) {
			return false;
		}
		$success = $self->_engines[$config]->decrement($settings['prefix'] . $key, $offset);
		$self->set();
		return $success;
	}
/**
 * Delete a key from the cache. Will automatically use the currently
 * active cache configuration.  To set the currently active configuration use
 * Cache::config()
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
 * @param string $config name of the configuration to use
 * @return boolean True if the value was succesfully deleted, false if it didn't exist or couldn't be removed
 * @access public
 * @static
 */
	function delete($key, $config = null) {
		$self =& Cache::getInstance();
		if (!$config) {
			$config = $self->__name;
		}
		$settings = $self->settings($config);

		if (empty($settings)) {
			return null;
		}
		if (!$self->isInitialized($config)) {
			return false;
		}
		$key = $self->_engines[$config]->key($key);
		if (!$key) {
			return false;
		}

		$success = $self->_engines[$config]->delete($settings['prefix'] . $key);
		$self->set();
		return $success;
	}

/**
 * Delete all keys from the cache.
 *
 * @param boolean $check if true will check expiration, otherwise delete all
 * @param string $config name of the configuration to use
 * @return boolean True if the cache was succesfully cleared, false otherwise
 * @access public
 * @static
 */
	function clear($check = false, $config = null) {
		$self =& Cache::getInstance();
		if (!$config) {
			$config = $self->__name;
		}
		$settings = $self->settings($config);

		if (empty($settings)) {
			return null;
		}

		if (!$self->isInitialized($config)) {
			return false;
		}
		$success = $self->_engines[$config]->clear($check);
		$self->set();
		return $success;
	}

/**
 * Check if Cache has initialized a working config for the given name.
 *
 * @param string $engine Name of the engine
 * @param string $config Name of the configuration setting
 * @return bool Whether or not the config name has been initialized.
 * @access public
 * @static
 */
	function isInitialized($name = null) {
		if (Configure::read('Cache.disable')) {
			return false;
		}
		$self =& Cache::getInstance();
		if (!$name && isset($self->__config[$self->__name])) {
			$name = $self->__name;
		}
		return isset($self->_engines[$name]);
	}

/**
 * Return the settings for current cache engine. If no name is supplied the settings
 * for the 'active default' configuration will be returned.  To set the 'active default'
 * configuration use `Cache::config()`
 *
 * @param string $engine Name of the configuration to get settings for.
 * @return array list of settings for this engine
 * @see Cache::config()
 * @access public
 * @static
 */
	function settings($name = null) {
		$self =& Cache::getInstance();
		if (!$name && isset($self->__config[$self->__name])) {
			$name = $self->__name;
		}
		if (!empty($self->_engines[$name])) {
			return $self->_engines[$name]->settings();
		}
		return array();
	}
}

/**
 * Storage engine for CakePHP caching
 *
 * @package       cake
 * @subpackage    cake.cake.libs
 */
class CacheEngine {

/**
 * Settings of current engine instance
 *
 * @var int
 * @access public
 */
	var $settings = array();

/**
 * Initialize the cache engine
 *
 * Called automatically by the cache frontend
 *
 * @param array $params Associative array of parameters for the engine
 * @return boolean True if the engine has been succesfully initialized, false if not
 * @access public
 */
	function init($settings = array()) {
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
 *
 * @access public
 */
	function gc() {
	}

/**
 * Write value for a key into cache
 *
 * @param string $key Identifier for the data
 * @param mixed $value Data to be cached
 * @param mixed $duration How long to cache the data, in seconds
 * @return boolean True if the data was succesfully cached, false on failure
 * @access public
 */
	function write($key, &$value, $duration) {
		trigger_error(sprintf(__('Method write() not implemented in %s', true), get_class($this)), E_USER_ERROR);
	}

/**
 * Read a key from the cache
 *
 * @param string $key Identifier for the data
 * @return mixed The cached data, or false if the data doesn't exist, has expired, or if there was an error fetching it
 * @access public
 */
	function read($key) {
		trigger_error(sprintf(__('Method read() not implemented in %s', true), get_class($this)), E_USER_ERROR);
	}

/**
 * Increment a number under the key and return incremented value
 *
 * @param string $key Identifier for the data
 * @param integer $offset How much to add
 * @return New incremented value, false otherwise
 * @access public
 */
	function increment($key, $offset = 1) {
		trigger_error(sprintf(__('Method increment() not implemented in %s', true), get_class($this)), E_USER_ERROR);
	}
/**
 * Decrement a number under the key and return decremented value
 *
 * @param string $key Identifier for the data
 * @param integer $value How much to substract
 * @return New incremented value, false otherwise
 * @access public
 */
	function decrement($key, $offset = 1) {
		trigger_error(sprintf(__('Method decrement() not implemented in %s', true), get_class($this)), E_USER_ERROR);
	}
/**
 * Delete a key from the cache
 *
 * @param string $key Identifier for the data
 * @return boolean True if the value was succesfully deleted, false if it didn't exist or couldn't be removed
 * @access public
 */
	function delete($key) {
	}

/**
 * Delete all keys from the cache
 *
 * @param boolean $check if true will check expiration, otherwise delete all
 * @return boolean True if the cache was succesfully cleared, false otherwise
 * @access public
 */
	function clear($check) {
	}

/**
 * Cache Engine settings
 *
 * @return array settings
 * @access public
 */
	function settings() {
		return $this->settings;
	}

/**
 * Generates a safe key for use with cache engine storage engines.
 *
 * @param string $key the key passed over
 * @return mixed string $key or false
 * @access public
 */
	function key($key) {
		if (empty($key)) {
			return false;
		}
		$key = Inflector::underscore(str_replace(array(DS, '/', '.'), '_', strval($key)));
		return $key;
	}
}
?>