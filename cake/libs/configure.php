<?php
/**
 * Configure class
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
 * @package       cake
 * @subpackage    cake.cake.libs
 * @since         CakePHP(tm) v 1.0.0.2363
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

/**
 * Configuration class. Used for managing runtime configuration information.
 *
 * Provides features for reading and writing to the runtime configuration, as well
 * as methods for loading additional configuration files or storing runtime configuration 
 * for future use.
 *
 * @package       cake
 * @subpackage    cake.cake.libs
 * @link          http://book.cakephp.org/view/924/The-Configuration-Class
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
 * Initializes configure and runs the bootstrap process.
 * Bootstrapping includes the following steps:
 *
 * - Setup App array in Configure.
 * - Include app/config/core.php.
 * - Configure core cache configurations.
 * - Load App cache files.
 * - Include app/config/bootstrap.php.
 * - Setup error/exception handlers.
 *
 * @return void
 */
	public static function bootstrap($boot = true) {
		if ($boot) {
			self::write('App', array('base' => false, 'baseUrl' => false, 'dir' => APP_DIR, 'webroot' => WEBROOT_DIR, 'www_root' => WWW_ROOT));

			if (!include(CONFIGS . 'core.php')) {
				trigger_error(sprintf(__("Can't find application core file. Please create %score.php, and make sure it is readable by PHP."), CONFIGS), E_USER_ERROR);
			}

			if (Configure::read('Cache.disable') !== true) {
				$cache = Cache::config('default');

				if (empty($cache['settings'])) {
					trigger_error(__('Cache not configured properly. Please check Cache::config(); in APP/config/core.php'), E_USER_WARNING);
					$cache = Cache::config('default', array('engine' => 'File'));
				}
				$path = $prefix = $duration = null;

				if (!empty($cache['settings']['path'])) {
					$path = realpath($cache['settings']['path']);
				} else {
					$prefix = $cache['settings']['prefix'];
				}

				if (Configure::read('debug') >= 1) {
					$duration = '+10 seconds';
				} else {
					$duration = '+999 days';
				}

				if (Cache::config('_cake_core_') === false) {
					Cache::config('_cake_core_', array_merge((array)$cache['settings'], array(
						'prefix' => $prefix . 'cake_core_', 'path' => $path . DS . 'persistent' . DS,
						'serialize' => true, 'duration' => $duration
					)));
				}

				if (Cache::config('_cake_model_') === false) {
					Cache::config('_cake_model_', array_merge((array)$cache['settings'], array(
						'prefix' => $prefix . 'cake_model_', 'path' => $path . DS . 'models' . DS,
						'serialize' => true, 'duration' => $duration
					)));
				}
			}

			App::init();
			App::build();
			if (!include(CONFIGS . 'bootstrap.php')) {
				trigger_error(sprintf(__("Can't find application bootstrap file. Please create %sbootstrap.php, and make sure it is readable by PHP."), CONFIGS), E_USER_ERROR);
			}
			$level = -1;
			if (isset(self::$_values['Error']['level'])) {
				error_reporting(self::$_values['Error']['level']);
				$level = self::$_values['Error']['level'];
			}
			if (!empty(self::$_values['Error']['handler'])) {
				set_error_handler(self::$_values['Error']['handler'], $level);
			}
			if (!empty(self::$_values['Exception']['handler'])) {
				set_exception_handler(self::$_values['Exception']['handler']);
			}
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
 * @link http://book.cakephp.org/view/926/write
 * @param array $config Name of var to write
 * @param mixed $value Value to set for var
 * @return boolean True if write was successful
 */
	public static function write($config, $value = null) {
		if (!is_array($config)) {
			$config = array($config => $value);
		}

		foreach ($config as $name => $value) {
			if (strpos($name, '.') === false) {
				self::$_values[$name] = $value;
			} else {
				$names = explode('.', $name, 4);
				switch (count($names)) {
					case 2:
						self::$_values[$names[0]][$names[1]] = $value;
					break;
					case 3:
						self::$_values[$names[0]][$names[1]][$names[2]] = $value;
					break;
					case 4:
						$names = explode('.', $name, 2);
						if (!isset(self::$_values[$names[0]])) {
							self::$_values[$names[0]] = array();
						}
						self::$_values[$names[0]] = Set::insert(self::$_values[$names[0]], $names[1], $value);
					break;
				}
			}
		}

		if (isset($config['debug']) || isset($config['log'])) {
			if (function_exists('ini_set')) {
				if (self::$_values['debug']) {
					ini_set('display_errors', 1);
				} else {
					ini_set('display_errors', 0);
				}
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
 * @link http://book.cakephp.org/view/927/read
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
		if (strpos($var, '.') !== false) {
			$names = explode('.', $var, 3);
			$var = $names[0];
		}
		if (!isset(self::$_values[$var])) {
			return null;
		}
		switch (count($names)) {
			case 2:
				if (isset(self::$_values[$var][$names[1]])) {
					return self::$_values[$var][$names[1]];
				}
			break;
			case 3:
				if (isset(self::$_values[$var][$names[1]][$names[2]])) {
					return self::$_values[$var][$names[1]][$names[2]];
				}
				if (!isset(self::$_values[$var][$names[1]])) {
					return null;
				}
				return Set::classicExtract(self::$_values[$var][$names[1]], $names[2]);
			break;
		}
		return null;
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
 * @link http://book.cakephp.org/view/928/delete
 * @param string $var the var to be deleted
 * @return void
 */
	public static function delete($var = null) {
		if (strpos($var, '.') === false) {
			unset(self::$_values[$var]);
			return;
		}

		$names = explode('.', $var, 2);
		self::$_values[$names[0]] = Set::remove(self::$_values[$names[0]], $names[1]);
	}

/**
 * Loads a file from app/config/configure_file.php.
 *
 * Config file variables should be formated like:
 *  `$config['name'] = 'value';`
 * These will be used to create dynamic Configure vars. load() is also used to
 * load stored config files created with Configure::store()
 *
 * - To load config files from app/config use `Configure::load('configure_file');`.
 * - To load config files from a plugin `Configure::load('plugin.configure_file');`.
 *
 * @link http://book.cakephp.org/view/929/load
 * @param string $fileName name of file to load, extension must be .php and only the name
 *     should be used, not the extenstion
 * @return mixed false if file not found, void if load successful
 */
	public static function load($fileName) {
		$found = $plugin = $pluginPath = false;
		list($plugin, $fileName) = pluginSplit($fileName);
		if ($plugin) {
			$pluginPath = App::pluginPath($plugin);
		}
		$pos = strpos($fileName, '..');

		if ($pos === false) {
			if ($pluginPath && file_exists($pluginPath . 'config' . DS . $fileName . '.php')) {
				include($pluginPath . 'config' . DS . $fileName . '.php');
				$found = true;
			} elseif (file_exists(CONFIGS . $fileName . '.php')) {
				include(CONFIGS . $fileName . '.php');
				$found = true;
			} elseif (file_exists(CACHE . 'persistent' . DS . $fileName . '.php')) {
				include(CACHE . 'persistent' . DS . $fileName . '.php');
				$found = true;
			} else {
				foreach (App::core('cake') as $key => $path) {
					if (file_exists($path . DS . 'config' . DS . $fileName . '.php')) {
						include($path . DS . 'config' . DS . $fileName . '.php');
						$found = true;
						break;
					}
				}
			}
		}

		if (!$found) {
			return false;
		}

		if (!isset($config)) {
			trigger_error(sprintf(__('Configure::load() - no variable $config found in %s.php'), $fileName), E_USER_WARNING);
			return false;
		}
		return self::write($config);
	}

/**
 * Used to determine the current version of CakePHP.
 *
 * Usage `Configure::version();`
 *
 * @link http://book.cakephp.org/view/930/version
 * @return string Current version of CakePHP
 */
	public static function version() {
		if (!isset(self::$_values['Cake']['version'])) {
			require(CORE_PATH . 'cake' . DS . 'config' . DS . 'config.php');
			self::write($config);
		}
		return self::$_values['Cake']['version'];
	}

/**
 * Used to write a config file to disk.
 *
 * {{{
 * Configure::store('Model', 'class_paths', array('Users' => array(
 *      'path' => 'users', 'plugin' => true
 * )));
 * }}}
 *
 * @param string $type Type of config file to write, ex: Models, Controllers, Helpers, Components
 * @param string $name file name.
 * @param array $data array of values to store.
 * @return void
 */
	public static function store($type, $name, $data = array()) {
		$write = true;
		$content = '';

		foreach ($data as $key => $value) {
			$content .= "\$config['$type']['$key'] = " . var_export($value, true) . ";\n";
		}
		if (is_null($type)) {
			$write = false;
		}
		self::__writeConfig($content, $name, $write);
	}

/**
 * Creates a cached version of a configuration file.
 * Appends values passed from Configure::store() to the cached file
 *
 * @param string $content Content to write on file
 * @param string $name Name to use for cache file
 * @param boolean $write true if content should be written, false otherwise
 * @return void
 * @access private
 */
	private static function __writeConfig($content, $name, $write = true) {
		$file = CACHE . 'persistent' . DS . $name . '.php';

		if (self::read('debug') > 0) {
			$expires = "+10 seconds";
		} else {
			$expires = "+999 days";
		}
		$cache = cache('persistent' . DS . $name . '.php', null, $expires);

		if ($cache === null) {
			cache('persistent' . DS . $name . '.php', "<?php\n\$config = array();\n", $expires);
		}

		if ($write === true) {
			$fileClass = new SplFileObject($file, 'a');
			if ($fileClass->isWritable()) {
				$fileClass->fwrite($content);
			}
		}
	}

}