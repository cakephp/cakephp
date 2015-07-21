<?php
/**
 * CakePlugin class
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       Cake.Core
 * @since         CakePHP(tm) v 2.0.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */

/**
 * CakePlugin is responsible for loading and unloading plugins. It also can
 * retrieve plugin paths and load their bootstrap and routes files.
 *
 * @package       Cake.Core
 * @link http://book.cakephp.org/2.0/en/plugins.html
 */
class CakePlugin {

/**
 * Holds a list of all loaded plugins and their configuration
 *
 * @var array
 */
	protected static $_plugins = array();

/**
 * Loads a plugin and optionally loads bootstrapping, routing files or loads an initialization function
 *
 * Examples:
 *
 * `CakePlugin::load('DebugKit')`
 *
 * Will load the DebugKit plugin and will not load any bootstrap nor route files
 *
 * `CakePlugin::load('DebugKit', array('bootstrap' => true, 'routes' => true))`
 *
 * will load the bootstrap.php and routes.php files
 *
 * `CakePlugin::load('DebugKit', array('bootstrap' => false, 'routes' => true))`
 *
 * will load routes.php file but not bootstrap.php
 *
 * `CakePlugin::load('DebugKit', array('bootstrap' => array('config1', 'config2')))`
 *
 * will load config1.php and config2.php files
 *
 * `CakePlugin::load('DebugKit', array('bootstrap' => 'aCallableMethod'))`
 *
 * will run the aCallableMethod function to initialize it
 *
 * Bootstrap initialization functions can be expressed as a PHP callback type,
 * including closures. Callbacks will receive two parameters
 * (plugin name, plugin configuration)
 *
 * It is also possible to load multiple plugins at once. Examples:
 *
 * `CakePlugin::load(array('DebugKit', 'ApiGenerator'))`
 *
 * will load the DebugKit and ApiGenerator plugins
 *
 * `CakePlugin::load(array('DebugKit', 'ApiGenerator'), array('bootstrap' => true))`
 *
 * will load bootstrap file for both plugins
 *
 * ```
 * 	CakePlugin::load(array(
 * 		'DebugKit' => array('routes' => true),
 * 		'ApiGenerator'
 * 		), array('bootstrap' => true))
 * ```
 *
 * Will only load the bootstrap for ApiGenerator and only the routes for DebugKit.
 * By using the `path` option you can specify an absolute path to the plugin. Make
 * sure that the path is slash terminated or your plugin will not be located properly.
 *
 * @param string|array $plugin name of the plugin to be loaded in CamelCase format or array or plugins to load
 * @param array $config configuration options for the plugin
 * @throws MissingPluginException if the folder for the plugin to be loaded is not found
 * @return void
 */
	public static function load($plugin, $config = array()) {
		if (is_array($plugin)) {
			foreach ($plugin as $name => $conf) {
				list($name, $conf) = (is_numeric($name)) ? array($conf, $config) : array($name, $conf);
				static::load($name, $conf);
			}
			return;
		}
		$config += array('bootstrap' => false, 'routes' => false, 'ignoreMissing' => false);
		if (empty($config['path'])) {
			foreach (App::path('plugins') as $path) {
				if (is_dir($path . $plugin)) {
					static::$_plugins[$plugin] = $config + array('path' => $path . $plugin . DS);
					break;
				}

				//Backwards compatibility to make easier to migrate to 2.0
				$underscored = Inflector::underscore($plugin);
				if (is_dir($path . $underscored)) {
					static::$_plugins[$plugin] = $config + array('path' => $path . $underscored . DS);
					break;
				}
			}
		} else {
			static::$_plugins[$plugin] = $config;
		}

		if (empty(static::$_plugins[$plugin]['path'])) {
			throw new MissingPluginException(array('plugin' => $plugin));
		}
		if (!empty(static::$_plugins[$plugin]['bootstrap'])) {
			static::bootstrap($plugin);
		}
	}

/**
 * Will load all the plugins located in the configured plugins folders
 * If passed an options array, it will be used as a common default for all plugins to be loaded
 * It is possible to set specific defaults for each plugins in the options array. Examples:
 *
 * ```
 * 	CakePlugin::loadAll(array(
 * 		array('bootstrap' => true),
 * 		'DebugKit' => array('routes' => true, 'bootstrap' => false),
 * 	))
 * ```
 *
 * The above example will load the bootstrap file for all plugins, but for DebugKit it will only load
 * the routes file and will not look for any bootstrap script. If you are loading
 * many plugins that inconsistently support routes/bootstrap files, instead of detailing
 * each plugin you can use the `ignoreMissing` option:
 *
 * ```
 * CakePlugin::loadAll(array(
 *   'ignoreMissing' => true,
 *   'bootstrap' => true,
 *   'routes' => true,
 * ));
 * ```
 *
 * The ignoreMissing option will do additional file_exists() calls but is simpler
 * to use.
 *
 * @param array $options Options list. See CakePlugin::load() for valid options.
 * @return void
 */
	public static function loadAll($options = array()) {
		$plugins = App::objects('plugins');
		foreach ($plugins as $p) {
			$opts = isset($options[$p]) ? (array)$options[$p] : array();
			if (isset($options[0])) {
				$opts += $options[0];
			}
			static::load($p, $opts);
		}
	}

/**
 * Returns the filesystem path for a plugin
 *
 * @param string $plugin name of the plugin in CamelCase format
 * @return string path to the plugin folder
 * @throws MissingPluginException if the folder for plugin was not found or plugin has not been loaded
 */
	public static function path($plugin) {
		if (empty(static::$_plugins[$plugin])) {
			throw new MissingPluginException(array('plugin' => $plugin));
		}
		return static::$_plugins[$plugin]['path'];
	}

/**
 * Loads the bootstrapping files for a plugin, or calls the initialization setup in the configuration
 *
 * @param string $plugin name of the plugin
 * @return mixed
 * @see CakePlugin::load() for examples of bootstrap configuration
 */
	public static function bootstrap($plugin) {
		$config = static::$_plugins[$plugin];
		if ($config['bootstrap'] === false) {
			return false;
		}
		if (is_callable($config['bootstrap'])) {
			return call_user_func_array($config['bootstrap'], array($plugin, $config));
		}

		$path = static::path($plugin);
		if ($config['bootstrap'] === true) {
			return static::_includeFile(
				$path . 'Config' . DS . 'bootstrap.php',
				$config['ignoreMissing']
			);
		}

		$bootstrap = (array)$config['bootstrap'];
		foreach ($bootstrap as $file) {
			static::_includeFile(
				$path . 'Config' . DS . $file . '.php',
				$config['ignoreMissing']
			);
		}

		return true;
	}

/**
 * Loads the routes file for a plugin, or all plugins configured to load their respective routes file
 *
 * @param string $plugin name of the plugin, if null will operate on all plugins having enabled the
 * loading of routes files
 * @return bool
 */
	public static function routes($plugin = null) {
		if ($plugin === null) {
			foreach (static::loaded() as $p) {
				static::routes($p);
			}
			return true;
		}
		$config = static::$_plugins[$plugin];
		if ($config['routes'] === false) {
			return false;
		}
		return (bool)static::_includeFile(
			static::path($plugin) . 'Config' . DS . 'routes.php',
			$config['ignoreMissing']
		);
	}

/**
 * Returns true if the plugin $plugin is already loaded
 * If plugin is null, it will return a list of all loaded plugins
 *
 * @param string $plugin Plugin name to check.
 * @return mixed boolean true if $plugin is already loaded.
 * If $plugin is null, returns a list of plugins that have been loaded
 */
	public static function loaded($plugin = null) {
		if ($plugin) {
			return isset(static::$_plugins[$plugin]);
		}
		$return = array_keys(static::$_plugins);
		sort($return);
		return $return;
	}

/**
 * Forgets a loaded plugin or all of them if first parameter is null
 *
 * @param string $plugin name of the plugin to forget
 * @return void
 */
	public static function unload($plugin = null) {
		if ($plugin === null) {
			static::$_plugins = array();
		} else {
			unset(static::$_plugins[$plugin]);
		}
	}

/**
 * Include file, ignoring include error if needed if file is missing
 *
 * @param string $file File to include
 * @param bool $ignoreMissing Whether to ignore include error for missing files
 * @return mixed
 */
	protected static function _includeFile($file, $ignoreMissing = false) {
		if ($ignoreMissing && !is_file($file)) {
			return false;
		}
		return include $file;
	}

}
