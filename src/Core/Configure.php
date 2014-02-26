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
 * @since         CakePHP(tm) v 1.0.0.2363
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Core;

use Cake\Cache\Cache;
use Cake\Configure\ConfigEngineInterface;
use Cake\Configure\Engine\PhpConfig;
use Cake\Error;
use Cake\Utility\Hash;

/**
 * Configuration class. Used for managing runtime configuration information.
 *
 * Provides features for reading and writing to the runtime configuration, as well
 * as methods for loading additional configuration files or storing runtime configuration
 * for future use.
 *
 * @link          http://book.cakephp.org/2.0/en/development/configuration.html#configure-class
 */
class Configure {

/**
 * Array of values currently stored in Configure.
 *
 * @var array
 */
	protected static $_values = [
		'debug' => 0
	];

/**
 * Configured engine classes, used to load config files from resources
 *
 * @var array
 * @see Configure::load()
 */
	protected static $_engines = [];

/**
 * Used to store a dynamic variable in Configure.
 *
 * Usage:
 * {{{
 * Configure::write('One.key1', 'value of the Configure::One[key1]');
 * Configure::write(['One.key1' => 'value of the Configure::One[key1]']);
 * Configure::write('One', [
 *     'key1' => 'value of the Configure::One[key1]',
 *     'key2' => 'value of the Configure::One[key2]'
 * ]);
 *
 * Configure::write([
 *     'One.key1' => 'value of the Configure::One[key1]',
 *     'One.key2' => 'value of the Configure::One[key2]'
 * ]);
 * }}}
 *
 * @link http://book.cakephp.org/2.0/en/development/configuration.html#Configure::write
 * @param string|array $config The key to write, can be a dot notation value.
 * Alternatively can be an array containing key(s) and value(s).
 * @param mixed $value Value to set for var
 * @return boolean True if write was successful
 */
	public static function write($config, $value = null) {
		if (!is_array($config)) {
			$config = [$config => $value];
		}

		foreach ($config as $name => $value) {
			static::$_values = Hash::insert(static::$_values, $name, $value);
		}

		if (isset($config['debug']) && function_exists('ini_set')) {
			if (static::$_values['debug']) {
				ini_set('display_errors', 1);
			} else {
				ini_set('display_errors', 0);
			}
		}
		return true;
	}

/**
 * Used to read information stored in Configure. It's not
 * possible to store `null` values in Configure.
 *
 * Usage:
 * {{{
 * Configure::read('Name'); will return all values for Name
 * Configure::read('Name.key'); will return only the value of Configure::Name[key]
 * }}}
 *
 * @link http://book.cakephp.org/2.0/en/development/configuration.html#Configure::read
 * @param string $var Variable to obtain. Use '.' to access array elements.
 * @return mixed value stored in configure, or null.
 */
	public static function read($var = null) {
		if ($var === null) {
			return static::$_values;
		}
		return Hash::get(static::$_values, $var);
	}

/**
 * Returns true if given variable is set in Configure.
 *
 * @param string $var Variable name to check for
 * @return boolean True if variable is there
 */
	public static function check($var = null) {
		if (empty($var)) {
			return false;
		}
		return Hash::get(static::$_values, $var) !== null;
	}

/**
 * Used to delete a variable from Configure.
 *
 * Usage:
 * {{{
 * Configure::delete('Name'); will delete the entire Configure::Name
 * Configure::delete('Name.key'); will delete only the Configure::Name[key]
 * }}}
 *
 * @link http://book.cakephp.org/2.0/en/development/configuration.html#Configure::delete
 * @param string $var the var to be deleted
 * @return void
 */
	public static function delete($var = null) {
		static::$_values = Hash::remove(static::$_values, $var);
	}

/**
 * Used to read and delete a variable from Configure.
 *
 * This is primarily used during bootstrapping to move configuration data
 * out of configure into the various other classes in CakePHP.
 *
 * @param string $var The key to read and remove.
 * @return array|null
 */
	public static function consume($var) {
		$simple = strpos($var, '.') === false;
		if ($simple && !isset(static::$_values[$var])) {
			return null;
		}
		if ($simple) {
			$value = static::$_values[$var];
			unset(static::$_values[$var]);
			return $value;
		}
		$value = Hash::get(static::$_values, $var);
		static::$_values = Hash::remove(static::$_values, $var);
		return $value;
	}

/**
 * Add a new engine to Configure. Engines allow you to read configuration
 * files in various formats/storage locations. CakePHP comes with two built-in engines
 * PhpConfig and IniConfig. You can also implement your own engine classes in your application.
 *
 * To add a new engine to Configure:
 *
 * `Configure::config('ini', new IniConfig());`
 *
 * @param string $name The name of the engine being configured. This alias is used later to
 *   read values from a specific engine.
 * @param ConfigEngineInterface $engine The engine to append.
 * @return void
 */
	public static function config($name, ConfigEngineInterface $engine) {
		static::$_engines[$name] = $engine;
	}

/**
 * Gets the names of the configured Engine objects.
 *
 * @param string $name
 * @return array Array of the configured Engine objects.
 */
	public static function configured($name = null) {
		if ($name) {
			return isset(static::$_engines[$name]);
		}
		return array_keys(static::$_engines);
	}

/**
 * Remove a configured engine. This will unset the engine
 * and make any future attempts to use it cause an Exception.
 *
 * @param string $name Name of the engine to drop.
 * @return boolean Success
 */
	public static function drop($name) {
		if (!isset(static::$_engines[$name])) {
			return false;
		}
		unset(static::$_engines[$name]);
		return true;
	}

/**
 * Loads stored configuration information from a resource. You can add
 * config file resource engines with `Configure::config()`.
 *
 * Loaded configuration information will be merged with the current
 * runtime configuration. You can load configuration files from plugins
 * by preceding the filename with the plugin name.
 *
 * `Configure::load('Users.user', 'default')`
 *
 * Would load the 'user' config file using the default config engine. You can load
 * app config files by giving the name of the resource you want loaded.
 *
 * `Configure::load('setup', 'default');`
 *
 * If using `default` config and no engine has been configured for it yet,
 * one will be automatically created using PhpConfig
 *
 * @link http://book.cakephp.org/2.0/en/development/configuration.html#Configure::load
 * @param string $key name of configuration resource to load.
 * @param string $config Name of the configured engine to use to read the resource identified by $key.
 * @param boolean $merge if config files should be merged instead of simply overridden
 * @return mixed false if file not found, void if load successful.
 * @throws \Cake\Error\ConfigureException Will throw any exceptions the engine raises.
 */
	public static function load($key, $config = 'default', $merge = true) {
		$engine = static::_getEngine($config);
		if (!$engine) {
			return false;
		}
		$values = $engine->read($key);

		if ($merge) {
			$keys = array_keys($values);
			foreach ($keys as $key) {
				if (($c = static::read($key)) && is_array($values[$key]) && is_array($c)) {
					$values[$key] = Hash::merge($c, $values[$key]);
				}
			}
		}

		return static::write($values);
	}

/**
 * Dump data currently in Configure into $key. The serialization format
 * is decided by the config engine attached as $config. For example, if the
 * 'default' adapter is a PhpConfig, the generated file will be a PHP
 * configuration file loadable by the PhpConfig.
 *
 * ## Usage
 *
 * Given that the 'default' engine is an instance of PhpConfig.
 * Save all data in Configure to the file `my_config.php`:
 *
 * `Configure::dump('my_config.php', 'default');`
 *
 * Save only the error handling configuration:
 *
 * `Configure::dump('error.php', 'default', ['Error', 'Exception'];`
 *
 * @param string $key The identifier to create in the config adapter.
 *   This could be a filename or a cache key depending on the adapter being used.
 * @param string $config The name of the configured adapter to dump data with.
 * @param array $keys The name of the top-level keys you want to dump.
 *   This allows you save only some data stored in Configure.
 * @return boolean success
 * @throws \Cake\Error\ConfigureException if the adapter does not implement a `dump` method.
 */
	public static function dump($key, $config = 'default', $keys = []) {
		$engine = static::_getEngine($config);
		if (!$engine) {
			throw new Error\ConfigureException(sprintf('There is no "%s" config engine.', $config));
		}
		if (!method_exists($engine, 'dump')) {
			throw new Error\ConfigureException(sprintf('The "%s" config engine, does not have a dump() method.', $config));
		}
		$values = static::$_values;
		if (!empty($keys) && is_array($keys)) {
			$values = array_intersect_key($values, array_flip($keys));
		}
		return (bool)$engine->dump($key, $values);
	}

/**
 * Get the configured engine. Internally used by `Configure::load()` and `Configure::dump()`
 * Will create new PhpConfig for default if not configured yet.
 *
 * @param string $config The name of the configured adapter
 * @return mixed Engine instance or false
 */
	protected static function _getEngine($config) {
		if (!isset(static::$_engines[$config])) {
			if ($config !== 'default') {
				return false;
			}
			static::config($config, new PhpConfig());
		}
		return static::$_engines[$config];
	}

/**
 * Used to determine the current version of CakePHP.
 *
 * Usage `Configure::version();`
 *
 * @return string Current version of CakePHP
 */
	public static function version() {
		if (!isset(static::$_values['Cake']['version'])) {
			require CAKE . 'Config/config.php';
			static::write($config);
		}
		return static::$_values['Cake']['version'];
	}

/**
 * Used to write runtime configuration into Cache. Stored runtime configuration can be
 * restored using `Configure::restore()`. These methods can be used to enable configuration managers
 * frontends, or other GUI type interfaces for configuration.
 *
 * @param string $name The storage name for the saved configuration.
 * @param string $cacheConfig The cache configuration to save into. Defaults to 'default'
 * @param array $data Either an array of data to store, or leave empty to store all values.
 * @return boolean Success
 */
	public static function store($name, $cacheConfig = 'default', $data = null) {
		if ($data === null) {
			$data = static::$_values;
		}
		return Cache::write($name, $data, $cacheConfig);
	}

/**
 * Restores configuration data stored in the Cache into configure. Restored
 * values will overwrite existing ones.
 *
 * @param string $name Name of the stored config file to load.
 * @param string $cacheConfig Name of the Cache configuration to read from.
 * @return boolean Success.
 */
	public static function restore($name, $cacheConfig = 'default') {
		$values = Cache::read($name, $cacheConfig);
		if ($values) {
			return static::write($values);
		}
		return false;
	}

/**
 * Clear all values stored in Configure.
 *
 * @return boolean success.
 */
	public static function clear() {
		static::$_values = [];
		return true;
	}

}
