<?php
/**
 * CakePlugin class
 *
 * PHP 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       Cake.Core
 * @since         CakePHP(tm) v 2.0.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
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
 * Loads a plugin and optionally loads bootstrapping, routing files or loads a initialization function
 *
 * Examples:
 *
 * 	`CakePlugin::load('DebugKit')` will load the DebugKit plugin and will not load any bootstrap nor route files
 *	`CakePlugin::load('DebugKit', array('bootstrap' => true, 'routes' => true))` will load the bootstrap.php and routes.php files
 * 	`CakePlugin::load('DebugKit', array('bootstrap' => false, 'routes' => true))` will load routes.php file but not bootstrap.php
 * 	`CakePlugin::load('DebugKit', array('bootstrap' => array('config1', 'config2')))` will load config1.php and config2.php files
 *	`CakePlugin::load('DebugKit', array('bootstrap' => 'aCallableMethod'))` will run the aCallableMethod function to initialize it
 *
 * Bootstrap initialization functions can be expressed as a PHP callback type, including closures. Callbacks will receive two
 * parameters (plugin name, plugin configuration)
 *
 * It is also possible to load multiple plugins at once. Examples:
 *
 * `CakePlugin::load(array('DebugKit', 'ApiGenerator'))` will load the DebugKit and ApiGenerator plugins
 * `CakePlugin::load(array('DebugKit', 'ApiGenerator'), array('bootstrap' => true))` will load bootstrap file for both plugins
 *
 * {{{
 * 	CakePlugin::load(array(
 * 		'DebugKit' => array('routes' => true),
 * 		'ApiGenerator'
 * 		), array('bootstrap' => true))
 * }}}
 *
 * Will only load the bootstrap for ApiGenerator and only the routes for DebugKit
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
				self::load($name, $conf);
			}
			return;
		}
		$config += array('bootstrap' => false, 'routes' => false);
		if (empty($config['path'])) {
			foreach (App::path('plugins') as $path) {
				if (is_dir($path . $plugin)) {
					self::$_plugins[$plugin] = $config + array('path' => $path . $plugin . DS);
					break;
				}

				//Backwards compatibility to make easier to migrate to 2.0
				$underscored = Inflector::underscore($plugin);
				if (is_dir($path . $underscored)) {
					self::$_plugins[$plugin] = $config + array('path' => $path . $underscored . DS);
					break;
				}
			}
		} else {
			self::$_plugins[$plugin] = $config;
		}

		if (empty(self::$_plugins[$plugin]['path'])) {
			throw new MissingPluginException(array('plugin' => $plugin));
		}
		if (!empty(self::$_plugins[$plugin]['bootstrap'])) {
			self::bootstrap($plugin);
		}
	}

/**
 * Will load all the plugins located in the configured plugins folders
 * If passed an options array, it will be used as a common default for all plugins to be loaded
 * It is possible to set specific defaults for each plugins in the options array. Examples:
 *
 * {{{
 * 	CakePlugin::loadAll(array(
 *		array('bootstrap' => true),
 * 		'DebugKit' => array('routes' => true),
 * 	))
 * }}}
 *
 * The above example will load the bootstrap file for all plugins, but for DebugKit it will only load the routes file
 * and will not look for any bootstrap script.
 *
 * @param array $options
 * @return void
 */
	public static function loadAll($options = array()) {
		$plugins = App::objects('plugins');
		foreach ($plugins as $p) {
			$opts = isset($options[$p]) ? $options[$p] : null;
			if ($opts === null && isset($options[0])) {
				$opts = $options[0];
			}
			self::load($p, (array)$opts);
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
		if (empty(self::$_plugins[$plugin])) {
			throw new MissingPluginException(array('plugin' => $plugin));
		}
		return self::$_plugins[$plugin]['path'];
	}

/**
 * Loads the bootstrapping files for a plugin, or calls the initialization setup in the configuration
 *
 * @param string $plugin name of the plugin
 * @return mixed
 * @see CakePlugin::load() for examples of bootstrap configuration
 */
	public static function bootstrap($plugin) {
		$config = self::$_plugins[$plugin];
		if ($config['bootstrap'] === false) {
			return false;
		}
		if (is_callable($config['bootstrap'])) {
			return call_user_func_array($config['bootstrap'], array($plugin, $config));
		}

		$path = self::path($plugin);
		if ($config['bootstrap'] === true) {
			return include $path . 'Config' . DS . 'bootstrap.php';
		}

		$bootstrap = (array)$config['bootstrap'];
		foreach ($bootstrap as $file) {
			include $path . 'Config' . DS . $file . '.php';
		}

		return true;
	}

/**
 * Loads the routes file for a plugin, or all plugins configured to load their respective routes file
 *
 * @param string $plugin name of the plugin, if null will operate on all plugins having enabled the
 * loading of routes files
 * @return boolean
 */
	public static function routes($plugin = null) {
		if ($plugin === null) {
			foreach (self::loaded() as $p) {
				self::routes($p);
			}
			return true;
		}
		$config = self::$_plugins[$plugin];
		if ($config['routes'] === false) {
			return false;
		}
		return (bool)include self::path($plugin) . 'Config' . DS . 'routes.php';
	}

/**
 * Returns true if the plugin $plugin is already loaded
 * If plugin is null, it will return a list of all loaded plugins
 *
 * @param string $plugin
 * @return mixed boolean true if $plugin is already loaded.
 * If $plugin is null, returns a list of plugins that have been loaded
 */
	public static function loaded($plugin = null) {
		if ($plugin) {
			return isset(self::$_plugins[$plugin]);
		}
		$return = array_keys(self::$_plugins);
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
		if (is_null($plugin)) {
			self::$_plugins = array();
		} else {
			unset(self::$_plugins[$plugin]);
		}
	}

}
