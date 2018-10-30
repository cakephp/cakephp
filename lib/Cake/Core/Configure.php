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
 * @package       Cake.Core
 * @since         CakePHP(tm) v 1.0.0.2363
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

App::uses('Hash', 'Utility');
App::uses('ConfigReaderInterface', 'Configure');

/**
 * Compatibility with 2.1, which expects Configure to load Set.
 */
App::uses('Set', 'Utility');

/**
 * Configuration class. Used for managing runtime configuration information.
 *
 * Provides features for reading and writing to the runtime configuration, as well
 * as methods for loading additional configuration files or storing runtime configuration
 * for future use.
 *
 * @package       Cake.Core
 * @link          https://book.cakephp.org/2.0/en/development/configuration.html#configure-class
 */
class Configure {

/**
 * Array of values currently stored in Configure.
 *
 * @var array
 */
	protected static $_values = array(
		'debug' => 0
	);

/**
 * Configured reader classes, used to load config files from resources
 *
 * @var array
 * @see Configure::load()
 */
	protected static $_readers = array();

/**
 * Initializes configure and runs the bootstrap process.
 * Bootstrapping includes the following steps:
 *
 * - Setup App array in Configure.
 * - Include app/Config/core.php.
 * - Configure core cache configurations.
 * - Load App cache files.
 * - Include app/Config/bootstrap.php.
 * - Setup error/exception handlers.
 *
 * @param bool $boot Whether to do bootstrapping.
 * @return void
 */
	public static function bootstrap($boot = true) {
		if ($boot) {
			static::_appDefaults();

			if (!include CONFIG . 'core.php') {
				trigger_error(__d('cake_dev',
						"Can't find application core file. Please create %s, and make sure it is readable by PHP.",
						CONFIG . 'core.php'),
					E_USER_ERROR
				);
			}
			App::init();
			App::$bootstrapping = false;
			App::build();

			$exception = array(
				'handler' => 'ErrorHandler::handleException',
			);
			$error = array(
				'handler' => 'ErrorHandler::handleError',
				'level' => E_ALL & ~E_DEPRECATED,
			);
			if (PHP_SAPI === 'cli') {
				App::uses('ConsoleErrorHandler', 'Console');
				$console = new ConsoleErrorHandler();
				$exception['handler'] = array($console, 'handleException');
				$error['handler'] = array($console, 'handleError');
			}
			static::_setErrorHandlers($error, $exception);

			if (!include CONFIG . 'bootstrap.php') {
				trigger_error(__d('cake_dev',
						"Can't find application bootstrap file. Please create %s, and make sure it is readable by PHP.",
						CONFIG . 'bootstrap.php'),
					E_USER_ERROR
				);
			}
			restore_error_handler();

			static::_setErrorHandlers(
				static::$_values['Error'],
				static::$_values['Exception']
			);

			// Preload Debugger + CakeText in case of E_STRICT errors when loading files.
			if (static::$_values['debug'] > 0) {
				class_exists('Debugger');
				class_exists('CakeText');
			}
			if (!defined('TESTS')) {
				define('TESTS', APP . 'Test' . DS);
			}
		}
	}

/**
 * Set app's default configs
 *
 * @return void
 */
	protected static function _appDefaults() {
		static::write('App', (array)static::read('App') + array(
			'base' => false,
			'baseUrl' => false,
			'dir' => APP_DIR,
			'webroot' => WEBROOT_DIR,
			'www_root' => WWW_ROOT
		));
	}

/**
 * Used to store a dynamic variable in Configure.
 *
 * Usage:
 * ```
 * Configure::write('One.key1', 'value of the Configure::One[key1]');
 * Configure::write(array('One.key1' => 'value of the Configure::One[key1]'));
 * Configure::write('One', array(
 *     'key1' => 'value of the Configure::One[key1]',
 *     'key2' => 'value of the Configure::One[key2]'
 * );
 *
 * Configure::write(array(
 *     'One.key1' => 'value of the Configure::One[key1]',
 *     'One.key2' => 'value of the Configure::One[key2]'
 * ));
 * ```
 *
 * @param string|array $config The key to write, can be a dot notation value.
 * Alternatively can be an array containing key(s) and value(s).
 * @param mixed $value Value to set for var
 * @return bool True if write was successful
 * @link https://book.cakephp.org/2.0/en/development/configuration.html#Configure::write
 */
	public static function write($config, $value = null) {
		if (!is_array($config)) {
			$config = array($config => $value);
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
 * ```
 * Configure::read('Name'); will return all values for Name
 * Configure::read('Name.key'); will return only the value of Configure::Name[key]
 * ```
 *
 * @param string|null $var Variable to obtain. Use '.' to access array elements.
 * @return mixed value stored in configure, or null.
 * @link https://book.cakephp.org/2.0/en/development/configuration.html#Configure::read
 */
	public static function read($var = null) {
		if ($var === null) {
			return static::$_values;
		}
		return Hash::get(static::$_values, $var);
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
 * Returns true if given variable is set in Configure.
 *
 * @param string $var Variable name to check for
 * @return bool True if variable is there
 */
	public static function check($var) {
		if (empty($var)) {
			return false;
		}
		return Hash::get(static::$_values, $var) !== null;
	}

/**
 * Used to delete a variable from Configure.
 *
 * Usage:
 * ```
 * Configure::delete('Name'); will delete the entire Configure::Name
 * Configure::delete('Name.key'); will delete only the Configure::Name[key]
 * ```
 *
 * @param string $var the var to be deleted
 * @return void
 * @link https://book.cakephp.org/2.0/en/development/configuration.html#Configure::delete
 */
	public static function delete($var) {
		static::$_values = Hash::remove(static::$_values, $var);
	}

/**
 * Add a new reader to Configure. Readers allow you to read configuration
 * files in various formats/storage locations. CakePHP comes with two built-in readers
 * PhpReader and IniReader. You can also implement your own reader classes in your application.
 *
 * To add a new reader to Configure:
 *
 * `Configure::config('ini', new IniReader());`
 *
 * @param string $name The name of the reader being configured. This alias is used later to
 *   read values from a specific reader.
 * @param ConfigReaderInterface $reader The reader to append.
 * @return void
 */
	public static function config($name, ConfigReaderInterface $reader) {
		static::$_readers[$name] = $reader;
	}

/**
 * Gets the names of the configured reader objects.
 *
 * @param string|null $name Name to check. If null returns all configured reader names.
 * @return array Array of the configured reader objects.
 */
	public static function configured($name = null) {
		if ($name) {
			return isset(static::$_readers[$name]);
		}
		return array_keys(static::$_readers);
	}

/**
 * Remove a configured reader. This will unset the reader
 * and make any future attempts to use it cause an Exception.
 *
 * @param string $name Name of the reader to drop.
 * @return bool Success
 */
	public static function drop($name) {
		if (!isset(static::$_readers[$name])) {
			return false;
		}
		unset(static::$_readers[$name]);
		return true;
	}

/**
 * Loads stored configuration information from a resource. You can add
 * config file resource readers with `Configure::config()`.
 *
 * Loaded configuration information will be merged with the current
 * runtime configuration. You can load configuration files from plugins
 * by preceding the filename with the plugin name.
 *
 * `Configure::load('Users.user', 'default')`
 *
 * Would load the 'user' config file using the default config reader. You can load
 * app config files by giving the name of the resource you want loaded.
 *
 * `Configure::load('setup', 'default');`
 *
 * If using `default` config and no reader has been configured for it yet,
 * one will be automatically created using PhpReader
 *
 * @param string $key name of configuration resource to load.
 * @param string $config Name of the configured reader to use to read the resource identified by $key.
 * @param bool $merge if config files should be merged instead of simply overridden
 * @return bool False if file not found, true if load successful.
 * @throws ConfigureException Will throw any exceptions the reader raises.
 * @link https://book.cakephp.org/2.0/en/development/configuration.html#Configure::load
 */
	public static function load($key, $config = 'default', $merge = true) {
		$reader = static::_getReader($config);
		if (!$reader) {
			return false;
		}
		$values = $reader->read($key);

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
 * is decided by the config reader attached as $config. For example, if the
 * 'default' adapter is a PhpReader, the generated file will be a PHP
 * configuration file loadable by the PhpReader.
 *
 * ## Usage
 *
 * Given that the 'default' reader is an instance of PhpReader.
 * Save all data in Configure to the file `my_config.php`:
 *
 * `Configure::dump('my_config.php', 'default');`
 *
 * Save only the error handling configuration:
 *
 * `Configure::dump('error.php', 'default', array('Error', 'Exception');`
 *
 * @param string $key The identifier to create in the config adapter.
 *   This could be a filename or a cache key depending on the adapter being used.
 * @param string $config The name of the configured adapter to dump data with.
 * @param array $keys The name of the top-level keys you want to dump.
 *   This allows you save only some data stored in Configure.
 * @return bool success
 * @throws ConfigureException if the adapter does not implement a `dump` method.
 */
	public static function dump($key, $config = 'default', $keys = array()) {
		$reader = static::_getReader($config);
		if (!$reader) {
			throw new ConfigureException(__d('cake_dev', 'There is no "%s" adapter.', $config));
		}
		if (!method_exists($reader, 'dump')) {
			throw new ConfigureException(__d('cake_dev', 'The "%s" adapter, does not have a %s method.', $config, 'dump()'));
		}
		$values = static::$_values;
		if (!empty($keys) && is_array($keys)) {
			$values = array_intersect_key($values, array_flip($keys));
		}
		return (bool)$reader->dump($key, $values);
	}

/**
 * Get the configured reader. Internally used by `Configure::load()` and `Configure::dump()`
 * Will create new PhpReader for default if not configured yet.
 *
 * @param string $config The name of the configured adapter
 * @return mixed Reader instance or false
 */
	protected static function _getReader($config) {
		if (!isset(static::$_readers[$config])) {
			if ($config !== 'default') {
				return false;
			}
			App::uses('PhpReader', 'Configure');
			static::config($config, new PhpReader());
		}
		return static::$_readers[$config];
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
			require CAKE . 'Config' . DS . 'config.php';
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
 * @return bool Success
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
 * @return bool Success.
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
 * @return bool Success.
 */
	public static function clear() {
		static::$_values = array();
		return true;
	}

/**
 * Set the error and exception handlers.
 *
 * @param array $error The Error handling configuration.
 * @param array $exception The exception handling configuration.
 * @return void
 */
	protected static function _setErrorHandlers($error, $exception) {
		$level = -1;
		if (isset($error['level'])) {
			error_reporting($error['level']);
			$level = $error['level'];
		}
		if (!empty($error['handler'])) {
			set_error_handler($error['handler'], $level);
		}
		if (!empty($exception['handler'])) {
			set_exception_handler($exception['handler']);
		}
	}

}
