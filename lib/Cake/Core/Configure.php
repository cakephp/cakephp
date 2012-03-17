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
 * @package       Cake.Core
 * @since         CakePHP(tm) v 1.0.0.2363
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

App::uses('Set', 'Utility');
App::uses('ConfigReaderInterface', 'Configure');

/**
 * Configuration class. Used for managing runtime configuration information.
 *
 * Provides features for reading and writing to the runtime configuration, as well
 * as methods for loading additional configuration files or storing runtime configuration
 * for future use.
 *
 * @package       Cake.Core
 * @link          http://book.cakephp.org/2.0/en/development/configuration.html#configure-class
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
 * @param boolean $boot
 * @return void
 */
	public static function bootstrap($boot = true) {
		if ($boot) {
			self::write('App', array(
				'base' => false,
				'baseUrl' => false,
				'dir' => APP_DIR,
				'webroot' => WEBROOT_DIR,
				'www_root' => WWW_ROOT
			));

			if (!include APP . 'Config' . DS . 'core.php') {
				trigger_error(__d('cake_dev', "Can't find application core file. Please create %score.php, and make sure it is readable by PHP.", APP . 'Config' . DS), E_USER_ERROR);
			}
			App::$bootstrapping = false;
			App::init();
			App::build();

			$exception = array(
				'handler' => 'ErrorHandler::handleException',
			);
			$error = array(
				'handler' => 'ErrorHandler::handleError',
				'level' => E_ALL & ~E_DEPRECATED,
			);
			self::_setErrorHandlers($error, $exception);

			if (!include APP . 'Config' . DS . 'bootstrap.php') {
				trigger_error(__d('cake_dev', "Can't find application bootstrap file. Please create %sbootstrap.php, and make sure it is readable by PHP.", APP . 'Config' . DS), E_USER_ERROR);
			}
			restore_error_handler();

			self::_setErrorHandlers(
				self::$_values['Error'],
				self::$_values['Exception']
			);
			unset($error, $exception);
		}
	}

/**
 * Used to store a dynamic variable in Configure.
 *
 * Usage:
 * {{{
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
 * }}}
 *
 * @link http://book.cakephp.org/2.0/en/development/configuration.html#Configure::write
 * @param array $config Name of var to write
 * @param mixed $value Value to set for var
 * @return boolean True if write was successful
 */
	public static function write($config, $value = null) {
		if (!is_array($config)) {
			$config = array($config => $value);
		}

		foreach ($config as $name => $value) {
			$pointer = &self::$_values;
			foreach (explode('.', $name) as $key) {
				$pointer = &$pointer[$key];
			}
			$pointer = $value;
			unset($pointer);
		}

		if (isset($config['debug']) && function_exists('ini_set')) {
			if (self::$_values['debug']) {
				ini_set('display_errors', 1);
			} else {
				ini_set('display_errors', 0);
			}
		}
		return true;
	}

/**
 * Used to read information stored in Configure.  Its not
 * possible to store `null` values in Configure.
 *
 * Usage:
 * {{{
 * Configure::read('Name'); will return all values for Name
 * Configure::read('Name.key'); will return only the value of Configure::Name[key]
 * }}}
 *
 * @linkhttp://book.cakephp.org/2.0/en/development/configuration.html#Configure::read
 * @param string $var Variable to obtain.  Use '.' to access array elements.
 * @return mixed value stored in configure, or null.
 */
	public static function read($var = null) {
		if ($var === null) {
			return self::$_values;
		}
		if (isset(self::$_values[$var])) {
			return self::$_values[$var];
		}
		$pointer = &self::$_values;
		foreach (explode('.', $var) as $key) {
			if (isset($pointer[$key])) {
				$pointer = &$pointer[$key];
			} else {
				return null;
			}
		}
		return $pointer;
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
		$keys = explode('.', $var);
		$last = array_pop($keys);
		$pointer = &self::$_values;
		foreach ($keys as $key) {
			$pointer = &$pointer[$key];
		}
		unset($pointer[$last]);
	}

/**
 * Add a new reader to Configure.  Readers allow you to read configuration
 * files in various formats/storage locations.  CakePHP comes with two built-in readers
 * PhpReader and IniReader.  You can also implement your own reader classes in your application.
 *
 * To add a new reader to Configure:
 *
 * `Configure::config('ini', new IniReader());`
 *
 * @param string $name The name of the reader being configured.  This alias is used later to
 *   read values from a specific reader.
 * @param ConfigReaderInterface $reader The reader to append.
 * @return void
 */
	public static function config($name, ConfigReaderInterface $reader) {
		self::$_readers[$name] = $reader;
	}

/**
 * Gets the names of the configured reader objects.
 *
 * @param string $name
 * @return array Array of the configured reader objects.
 */
	public static function configured($name = null) {
		if ($name) {
			return isset(self::$_readers[$name]);
		}
		return array_keys(self::$_readers);
	}

/**
 * Remove a configured reader.  This will unset the reader
 * and make any future attempts to use it cause an Exception.
 *
 * @param string $name Name of the reader to drop.
 * @return boolean Success
 */
	public static function drop($name) {
		if (!isset(self::$_readers[$name])) {
			return false;
		}
		unset(self::$_readers[$name]);
		return true;
	}

/**
 * Loads stored configuration information from a resource.  You can add
 * config file resource readers with `Configure::config()`.
 *
 * Loaded configuration information will be merged with the current
 * runtime configuration. You can load configuration files from plugins
 * by preceding the filename with the plugin name.
 *
 * `Configure::load('Users.user', 'default')`
 *
 * Would load the 'user' config file using the default config reader.  You can load
 * app config files by giving the name of the resource you want loaded.
 *
 * `Configure::load('setup', 'default');`
 *
 * If using `default` config and no reader has been configured for it yet,
 * one will be automatically created using PhpReader
 *
 * @link http://book.cakephp.org/2.0/en/development/configuration.html#Configure::load
 * @param string $key name of configuration resource to load.
 * @param string $config Name of the configured reader to use to read the resource identified by $key.
 * @param boolean $merge if config files should be merged instead of simply overridden
 * @return mixed false if file not found, void if load successful.
 * @throws ConfigureException Will throw any exceptions the reader raises.
 */
	public static function load($key, $config = 'default', $merge = true) {
		if (!isset(self::$_readers[$config])) {
			if ($config === 'default') {
				App::uses('PhpReader', 'Configure');
				self::$_readers[$config] = new PhpReader();
			} else {
				return false;
			}
		}
		$values = self::$_readers[$config]->read($key);

		if ($merge) {
			$keys = array_keys($values);
			foreach ($keys as $key) {
				if (($c = self::read($key)) && is_array($values[$key]) && is_array($c)) {
					$values[$key] = Set::merge($c, $values[$key]);
				}
			}
		}

		return self::write($values);
	}

/**
 * Used to determine the current version of CakePHP.
 *
 * Usage `Configure::version();`
 *
 * @return string Current version of CakePHP
 */
	public static function version() {
		if (!isset(self::$_values['Cake']['version'])) {
			require CAKE . 'Config' . DS . 'config.php';
			self::write($config);
		}
		return self::$_values['Cake']['version'];
	}

/**
 * Used to write runtime configuration into Cache.  Stored runtime configuration can be
 * restored using `Configure::restore()`.  These methods can be used to enable configuration managers
 * frontends, or other GUI type interfaces for configuration.
 *
 * @param string $name The storage name for the saved configuration.
 * @param string $cacheConfig The cache configuration to save into.  Defaults to 'default'
 * @param array $data Either an array of data to store, or leave empty to store all values.
 * @return boolean Success
 */
	public static function store($name, $cacheConfig = 'default', $data = null) {
		if ($data === null) {
			$data = self::$_values;
		}
		return Cache::write($name, $data, $cacheConfig);
	}

/**
 * Restores configuration data stored in the Cache into configure.  Restored
 * values will overwrite existing ones.
 *
 * @param string $name Name of the stored config file to load.
 * @param string $cacheConfig Name of the Cache configuration to read from.
 * @return boolean Success.
 */
	public static function restore($name, $cacheConfig = 'default') {
		$values = Cache::read($name, $cacheConfig);
		if ($values) {
			return self::write($values);
		}
		return false;
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
