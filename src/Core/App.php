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
 * @since         1.2.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Core;

use Cake\Cache\Cache;
use Cake\Error\ErrorHandler;
use Cake\Utility\Inflector;

/**
 * App is responsible for resource location, and path management.
 *
 * ### Adding paths
 *
 * Additional paths for Templates and Plugins are configured with Configure now. See App/Config/app.php for an
 * example. The `App.paths.plugins` and `App.paths.templates` variables are used to configure paths for plugins
 * and templates respectively. All class based resources should be mapped using your application's autoloader.
 *
 * ### Inspecting loaded paths
 *
 * You can inspect the currently loaded paths using `App::path('Controller')` for example to see loaded
 * controller paths.
 *
 * It is also possible to inspect paths for plugin classes, for instance, to get
 * the path to a plugin's helpers you would call `App::path('View/Helper', 'MyPlugin')`
 *
 * ### Locating plugins and themes
 *
 * Plugins and Themes can be located with App as well. Using App::pluginPath('DebugKit') for example, will
 * give you the full path to the DebugKit plugin. App::themePath('purple'), would give the full path to the
 * `purple` theme.
 *
 * ### Inspecting known objects
 *
 * You can find out which objects App knows about using App::objects('Controller') for example to find
 * which application controllers App knows about. This method will not find objects in sub-namespaces
 * by default.
 *
 * @link http://book.cakephp.org/2.0/en/core-utility-libraries/app.html
 */
class App {

/**
 * Holds and key => value array of object types.
 *
 * @var array
 */
	protected static $_objects = [];

/**
 * Indicates whether the object cache should be stored again because of an addition to it
 *
 * @var bool
 */
	protected static $_objectCacheChange = false;

/**
 * Return the classname namespaced. This method checks if the class is defined on the
 * application/plugin, otherwise try to load from the CakePHP core
 *
 * @param string $class Classname
 * @param string $type Type of class
 * @param string $suffix Classname suffix
 * @return bool|string False if the class is not found or namespaced classname
 */
	public static function classname($class, $type = '', $suffix = '') {
		if (strpos($class, '\\') !== false) {
			return $class;
		}

		list($plugin, $name) = pluginSplit($class);
		if ($plugin) {
			$base = Plugin::getNamespace($plugin);
		} else {
			$base = Configure::read('App.namespace');
		}
		$base = rtrim($base, '\\');

		$fullname = '\\' . str_replace('/', '\\', $type . '\\' . $name) . $suffix;

		if (static::_classExistsInBase($fullname, $base)) {
			return $base . $fullname;
		}
		if ($plugin) {
			return false;
		}
		if (static::_classExistsInBase($fullname, 'Cake')) {
			return 'Cake' . $fullname;
		}
		return false;
	}

/**
 * _classExistsInBase
 *
 * Test isolation wrapper
 *
 * @param string $name
 * @param string $namespace
 * @return bool
 */
	protected static function _classExistsInBase($name, $namespace) {
		return class_exists($namespace . $name);
	}

/**
 * Used to read information stored path
 *
 * Usage:
 *
 * `App::path('Plugin');`
 *
 * Will return the configured paths for plugins. This is a simpler way to access
 * the `App.paths.plugins` configure variable.
 *
 * `App::path('Model/Datasource', 'MyPlugin');`
 *
 * Will return the path for datasources under the 'MyPlugin' plugin.
 *
 * @param string $type type of path
 * @param string $plugin name of plugin
 * @return array
 * @link http://book.cakephp.org/2.0/en/core-utility-libraries/app.html#App::path
 */
	public static function path($type, $plugin = null) {
		if ($type === 'Plugin') {
			return (array)Configure::read('App.paths.plugins');
		}
		if (empty($plugin) && $type === 'Template') {
			return (array)Configure::read('App.paths.templates');
		}
		if (!empty($plugin)) {
			return [static::pluginPath($plugin) . $type . DS];
		}
		return [APP . $type . DS];
	}

/**
 * Gets the path that a plugin is on. Searches through the defined plugin paths.
 *
 * Usage:
 *
 * `App::pluginPath('MyPlugin');`
 *
 * Will return the full path to 'MyPlugin' plugin
 *
 * @param string $plugin CamelCased/lower_cased plugin name to find the path of.
 * @return string full path to the plugin.
 * @link http://book.cakephp.org/2.0/en/core-utility-libraries/app.html#App::pluginPath
 */
	public static function pluginPath($plugin) {
		return Plugin::path($plugin);
	}

/**
 * Finds the path that a theme is on. Searches through the defined theme paths.
 *
 * Usage:
 *
 * `App::themePath('MyTheme');` will return the full path to the 'MyTheme' theme.
 *
 * @param string $theme theme name to find the path of.
 * @return string full path to the theme.
 * @link http://book.cakephp.org/2.0/en/core-utility-libraries/app.html#App::themePath
 */
	public static function themePath($theme) {
		$themeDir = 'Themed' . DS . Inflector::camelize($theme);
		$paths = static::path('Template');
		foreach ($paths as $path) {
			if (is_dir($path . $themeDir)) {
				return $path . $themeDir . DS;
			}
		}
		return $paths[0] . $themeDir . DS;
	}

/**
 * Returns the full path to a package inside the CakePHP core
 *
 * Usage:
 *
 * `App::core('Cache/Engine');`
 *
 * Will return the full path to the cache engines package.
 *
 * @param string $type
 * @return array full path to package
 * @link http://book.cakephp.org/2.0/en/core-utility-libraries/app.html#App::core
 */
	public static function core($type) {
		return [CAKE . str_replace('/', DS, $type) . DS];
	}

/**
 * Returns an array of objects of the given type.
 *
 * Example usage:
 *
 * `App::objects('Plugin');` returns `['DebugKit', 'Blog', 'User'];`
 *
 * `App::objects('Controller');` returns `['PagesController', 'BlogController'];`
 *
 * You can also search only within a plugin's objects by using the plugin dot
 * syntax.
 *
 * `App::objects('MyPlugin.Model');` returns `['MyPluginPost', 'MyPluginComment'];`
 *
 * When scanning directories, files and directories beginning with `.` will be excluded as these
 * are commonly used by version control systems.
 *
 * @param string $type Type of object, i.e. 'Model', 'Controller', 'View/Helper', 'file', 'class' or 'Plugin'
 * @param string|array $path Optional Scan only the path given. If null, paths for the chosen type will be used.
 * @param bool $cache Set to false to rescan objects of the chosen type. Defaults to true.
 * @return mixed Either false on incorrect / miss. Or an array of found objects.
 * @link http://book.cakephp.org/2.0/en/core-utility-libraries/app.html#App::objects
 */
	public static function objects($type, $path = null, $cache = true) {
		if (empty(static::$_objects) && $cache === true) {
			static::$_objects = (array)Cache::read('object_map', '_cake_core_');
		}

		$extension = '/\.php$/';
		$includeDirectories = false;
		$name = $type;

		if ($type === 'Plugin') {
			$extension = '/.*/';
			$includeDirectories = true;
		}

		list($plugin, $type) = pluginSplit($type);

		if ($type === 'file' && !$path) {
			return false;
		} elseif ($type === 'file') {
			$extension = '/\.php$/';
			$name = $type . str_replace(DS, '', $path);
		}

		$cacheLocation = empty($plugin) ? 'app' : $plugin;

		if ($cache !== true || !isset(static::$_objects[$cacheLocation][$name])) {
			$objects = [];

			if (empty($path)) {
				$path = static::path($type, $plugin);
			}
			foreach ((array)$path as $dir) {
				if ($dir != APP && is_dir($dir)) {
					$files = new \RegexIterator(new \DirectoryIterator($dir), $extension);
					foreach ($files as $file) {
						$fileName = basename($file);
						if (!$file->isDot() && $fileName[0] !== '.') {
							$isDir = $file->isDir();
							if ($isDir && $includeDirectories) {
								$objects[] = $fileName;
							} elseif (!$includeDirectories && !$isDir) {
								$objects[] = substr($fileName, 0, -4);
							}
						}
					}
				}
			}

			if ($type !== 'file') {
				foreach ($objects as $key => $value) {
					$objects[$key] = Inflector::camelize($value);
				}
			}

			sort($objects);
			if ($plugin) {
				return $objects;
			}

			static::$_objects[$cacheLocation][$name] = $objects;
			if ($cache) {
				static::$_objectCacheChange = true;
			}
		}

		return static::$_objects[$cacheLocation][$name];
	}

/**
 * Initializes the App, registers a shutdown function.
 *
 * @return void
 */
	public static function init() {
		register_shutdown_function([get_called_class(), 'shutdown']);
	}

/**
 * Object destructor.
 *
 * Writes cache file if changes have been made to the $_map. Also, check if a fatal
 * error happened and call the handler.
 *
 * @return void
 */
	public static function shutdown() {
		if (static::$_objectCacheChange) {
			Cache::write('object_map', static::$_objects, '_cake_core_');
		}
	}

}
