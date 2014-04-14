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
 * @since         2.0.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Core;

use Cake\Core\ClassLoader;
use Cake\Utility\Inflector;

/**
 * Plugin is used to load and locate plugins.
 *
 * It also can retrieve plugin paths and load their bootstrap and routes files.
 *
 * @link http://book.cakephp.org/3.0/en/plugins.html
 */
class Plugin {

/**
 * Holds a list of all loaded plugins and their configuration
 *
 * @var array
 */
	protected static $_plugins = [];

/**
 * Class loader instance
 *
 * @var \Cake\Core\ClassLoader
 */
	protected static $_loader;

/**
 * Loads a plugin and optionally loads bootstrapping,
 * routing files or runs a initialization function.
 *
 * Plugins only need to be loaded if you want bootstrapping/routes/cli commands to
 * be exposed. If your plugin does not expose any of these features you do not need
 * to load them.
 *
 * This method does not configure any autoloaders. That must be done separately either
 * through composer, or your own code during App/Config/bootstrap.php.
 *
 * ## Examples:
 *
 * `Plugin::load('DebugKit')`
 *
 * Will load the DebugKit plugin and will not load any bootstrap nor route files.
 * However, the plugin will be part of the framework default routes, and have its
 * CLI tools (if any) available for use.
 *
 * `Plugin::load('DebugKit', ['bootstrap' => true, 'routes' => true])`
 *
 * Will load the bootstrap.php and routes.php files.
 *
 * `Plugin::load('DebugKit', ['bootstrap' => false, 'routes' => true])`
 *
 * Will load routes.php file but not bootstrap.php
 *
 * `Plugin::load('DebugKit', ['namespace' => 'Cake\DebugKit'])`
 *
 * Will load files on APP/Plugin/Cake/DebugKit/...
 *
 * Bootstrap initialization functions can be expressed as a PHP callback type,
 * including closures. Callbacks will receive two parameters (plugin name, plugin configuration)
 *
 * It is also possible to load multiple plugins at once. Examples:
 *
 * `Plugin::load(['DebugKit', 'ApiGenerator'])`
 *
 * Will load the DebugKit and ApiGenerator plugins.
 *
 * `Plugin::load(['DebugKit', 'ApiGenerator'], ['bootstrap' => true])`
 *
 * Will load bootstrap file for both plugins
 *
 * {{{
 *   Plugin::load([
 *     'DebugKit' => ['routes' => true],
 *     'ApiGenerator'
 *     ],
 *     ['bootstrap' => true])
 * }}}
 *
 * Will only load the bootstrap for ApiGenerator and only the routes for DebugKit
 *
 * ## Configuration options
 *
 * - `bootstrap` - array - Whether or not you want the $plugin/Config/bootstrap.php file loaded.
 * - `routes` - boolean - Whether or not you want to load the $plugin/Config/routes.php file.
 * - `namespace` - string - A custom namespace for the plugin. It will default to the plugin name.
 * - `ignoreMissing` - boolean - Set to true to ignore missing bootstrap/routes files.
 * - `path` - string - The path the plugin can be found on. If empty the default plugin path (App.pluginPaths) will be used.
 * - `autoload` - boolean - Whether or not you want an autoloader registered. This defaults to false. The framework
 *   assumes you have configured autoloaders using composer. However, if your application source tree is made up of
 *   plugins, this can be a useful option.
 *
 * @param string|array $plugin name of the plugin to be loaded in CamelCase format or array or plugins to load
 * @param array $config configuration options for the plugin
 * @throws \Cake\Core\Error\MissingPluginException if the folder for the plugin to be loaded is not found
 * @return void
 */
	public static function load($plugin, array $config = []) {
		if (is_array($plugin)) {
			foreach ($plugin as $name => $conf) {
				list($name, $conf) = (is_numeric($name)) ? [$conf, $config] : [$name, $conf];
				static::load($name, $conf);
			}
			return;
		}

		$config += ['autoload' => false, 'bootstrap' => false, 'routes' => false, 'namespace' => $plugin, 'ignoreMissing' => false];
		if (empty($config['path'])) {
			$paths = App::path('Plugin');
			foreach ($paths as $path) {
				$namespacePath = str_replace('\\', DS, $config['namespace']);
				if (is_dir($path . $plugin)) {
					$config += ['path' => $path . $plugin . DS];
					break;
				}
				if ($plugin !== $config['namespace'] && is_dir($path . $namespacePath)) {
					$config += ['path' => $path . $namespacePath . DS];
					break;
				}
			}
		}

		if (empty($config['path'])) {
			throw new Error\MissingPluginException(['plugin' => $plugin]);
		}

		static::$_plugins[$plugin] = $config;

		if ($config['bootstrap'] === true) {
			static::bootstrap($plugin);
		}

		if ($config['autoload'] === true) {
			if (empty(static::$_loader)) {
				static::$_loader = new ClassLoader;
				static::$_loader->register();
			}
			static::$_loader->addNamespace($config['namespace'], $config['path']);
		}
	}

/**
 * Will load all the plugins located in the default plugin folder.
 *
 * If passed an options array, it will be used as a common default for all plugins to be loaded
 * It is possible to set specific defaults for each plugins in the options array. Examples:
 *
 * {{{
 *  Plugin::loadAll([
 *      ['bootstrap' => true],
 *      'DebugKit' => ['routes' => true],
 *  ]);
 * }}}
 *
 * The above example will load the bootstrap file for all plugins, but for DebugKit it will only load the routes file
 * and will not look for any bootstrap script.
 *
 * If a plugin has been loaded already, it will not be reloaded by loadAll().
 *
 * @param array $options
 * @return void
 */
	public static function loadAll(array $options = []) {
		$plugins = App::objects('Plugin');
		foreach ($plugins as $p) {
			$opts = isset($options[$p]) ? $options[$p] : null;
			if ($opts === null && isset($options[0])) {
				$opts = $options[0];
			}
			if (isset(static::$_plugins[$p])) {
				continue;
			}
			static::load($p, (array)$opts);
		}
	}

/**
 * Returns the filesystem path for a plugin
 *
 * @param string $plugin name of the plugin in CamelCase format
 * @return string path to the plugin folder
 * @throws \Cake\Core\Error\MissingPluginException if the folder for plugin was not found or plugin has not been loaded
 */
	public static function path($plugin) {
		if (empty(static::$_plugins[$plugin])) {
			throw new Error\MissingPluginException(['plugin' => $plugin]);
		}
		return static::$_plugins[$plugin]['path'];
	}

/**
 * Return the namespace for a plugin
 *
 * If a plugin is unknown, the plugin name will be used as the namespace.
 * This lets you access vendor libraries or unloaded plugins using `Plugin.Class`.
 *
 * @param string $plugin name of the plugin in CamelCase format
 * @return string namespace to the plugin
 */
	public static function getNamespace($plugin) {
		if (empty(static::$_plugins[$plugin])) {
			return $plugin;
		}
		return static::$_plugins[$plugin]['namespace'];
	}

/**
 * Loads the bootstrapping files for a plugin, or calls the initialization setup in the configuration
 *
 * @param string $plugin name of the plugin
 * @return mixed
 * @see Plugin::load() for examples of bootstrap configuration
 */
	public static function bootstrap($plugin) {
		$config = static::$_plugins[$plugin];
		if ($config['bootstrap'] === false) {
			return false;
		}
		$path = static::path($plugin);
		if ($config['bootstrap'] === true) {
			return static::_includeFile(
				$path . 'Config/bootstrap.php',
				$config['ignoreMissing']
			);
		}
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
 * @param string $plugin
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
			static::$_plugins = [];
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
