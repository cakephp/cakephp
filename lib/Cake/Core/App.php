<?php
/**
 * App class
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
 * @since         CakePHP(tm) v 1.2.0.6001
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
namespace Cake\Core;
use Cake\Cache\Cache,
	Cake\Utility\Inflector,
	Cake\Error\ErrorHandler;

/**
 * App is responsible for path management, class location and class loading.
 *
 * ### Adding paths
 *
 * You can add paths to the search indexes App uses to find classes using `App::build()`.  Adding
 * additional controller paths for example would alter where CakePHP looks for controllers.
 * This allows you to split your application up across the filesystem.
 *
 * ### Packages
 *
 * CakePHP is organized around the idea of packages, each class belongs to a package or folder where other
 * classes reside. You can configure each package location in your application using `App::build('APackage/SubPackage', $paths)`
 * to inform the framework where should each class be loaded. Almost every class in the CakePHP framework can be swapped
 * by your own compatible implementation. If you wish to use you own class instead of the classes the framework provides,
 * just add the class to your libs folder mocking the directory location of where CakePHP expects to find it.
 *
 * For instance if you'd like to use your own HttpSocket class, put it under
 *
 *		app/Network/Http/HttpSocket.php
 *
 * ### Inspecting loaded paths
 *
 * You can inspect the currently loaded paths using `App::path('Controller')` for example to see loaded
 * controller paths.
 *
 * It is also possible to inspect paths for plugin classes, for instance, to see a plugin's helpers you would call
 * `App::path('View/Helper', 'MyPlugin')`
 *
 * ### Locating plugins and themes
 *
 * Plugins and Themes can be located with App as well.  Using App::pluginPath('DebugKit') for example, will
 * give you the full path to the DebugKit plugin.  App::themePath('purple'), would give the full path to the
 * `purple` theme.
 *
 * ### Inspecting known objects
 *
 * You can find out which objects App knows about using App::objects('Controller') for example to find
 * which application controllers App knows about.
 *
 * @link          http://book.cakephp.org/2.0/en/core-utility-libraries/app.html
 * @package       Cake.Core
 */
class App {

/**
 * Append paths
 *
 * @constant APPEND
 */
	const APPEND = 'append';

/**
 * Prepend paths
 *
 * @constant PREPEND
 */
	const PREPEND = 'prepend';

/**
 * Register package
 *
 * @constant REGISTER
 */
	const REGISTER = 'register';

/**
 * Reset paths instead of merging
 *
 * @constant RESET
 */
	const RESET = true;

/**
 * List of object types and their properties
 *
 * @var array
 */
	public static $types = array(
		'class' => array('extends' => null, 'core' => true),
		'file' => array('extends' => null, 'core' => true),
		'model' => array('extends' => 'AppModel', 'core' => false),
		'behavior' => array('suffix' => 'Behavior', 'extends' => 'Model/ModelBehavior', 'core' => true),
		'controller' => array('suffix' => 'Controller', 'extends' => 'AppController', 'core' => true),
		'component' => array('suffix' => 'Component', 'extends' => null, 'core' => true),
		'lib' => array('extends' => null, 'core' => true),
		'view' => array('suffix' => 'View', 'extends' => null, 'core' => true),
		'helper' => array('suffix' => 'Helper', 'extends' => 'AppHelper', 'core' => true),
		'vendor' => array('extends' => null, 'core' => true),
		'shell' => array('suffix' => 'Shell', 'extends' => 'AppShell', 'core' => true),
		'plugin' => array('extends' => null, 'core' => true)
	);

/**
 * Paths to search for files.
 *
 * @var array
 */
	public static $search = array();

/**
 * Whether or not to return the file that is loaded.
 *
 * @var boolean
 */
	public static $return = false;

/**
 * Holds key/value pairs of $type => file path.
 *
 * @var array
 */
	protected static $_map = array();

/**
 * Holds and key => value array of object types.
 *
 * @var array
 */
	protected static $_objects = array();

/**
 * Holds the location of each class
 *
 * @var array
 */
	protected static $_classMap = array();

/**
 * Holds the possible paths for each package name
 *
 * @var array
 */
	protected static $_packages = array();

/**
 * Holds the templates for each customizable package path in the application
 *
 * @var array
 */
	protected static $_packageFormat = array();

/**
 * Maps an old style CakePHP class type to the corresponding package
 *
 * @var array
 */
	public static $legacy = array(
		'models' => 'Model',
		'behaviors' => 'Model/Behavior',
		'datasources' => 'Model/Datasource',
		'controllers' => 'Controller',
		'components' => 'Controller/Component',
		'views' => 'View',
		'helpers' => 'View/Helper',
		'shells' => 'Console/Command',
		'libs' => 'Lib',
		'vendors' => 'Vendor',
		'plugins' => 'Plugin',
		'locales' => 'Locale'
	);

/**
 * Indicates whether the class cache should be stored again because of an addition to it
 *
 * @var boolean
 */
	protected static $_cacheChange = false;

/**
 * Indicates whether the object cache should be stored again because of an addition to it
 *
 * @var boolean
 */
	protected static $_objectCacheChange = false;

/**
 * Indicates the the Application is in the bootstrapping process. Used to better cache
 * loaded classes while the cache libraries have not been yet initialized
 *
 * @var boolean
 */
	public static $bootstrapping = false;

/**
 * Return the classname namespaced. This method check if the class is defined on the
 * application/plugin, otherwise try to load from the CakePHP core
 *
 * @param string $class Classname
 * @param strign $type Type of class
 * @param string $suffix Classname suffix
 * @return boolean|string False if the class is not found or namespaced classname
 */
	public static function classname($class, $type = '', $suffix = '') {
		if (strpos($class, '\\') !== false) {
			return $class;
		}

		list($plugin, $name) = pluginSplit($class);
		$checkCore = true;
		if ($plugin) {
			$base = Plugin::getNamespace($plugin);
			$checkCore = false;
		} else {
			$base = Configure::read('App.namespace');
		}
		$base = rtrim($base, '\\');

		if ($type === 'Lib') {
			$fullname = '\\' . $name . $suffix;
			if (class_exists($base . $fullname)) {
				return $base . $fullname;
			}
		}
		$fullname = '\\' . str_replace('/', '\\', $type) . '\\' . $name . $suffix;
		if (class_exists($base . $fullname)) {
			return $base . $fullname;
		}

		if ($checkCore) {
			if ($type === 'Lib') {
				$fullname = '\\' . $name . $suffix;
			}
			if (class_exists('Cake' . $fullname)) {
				return 'Cake' . $fullname;
			}
		}
		return false;
	}

/**
 * Used to read information stored path
 *
 * Usage:
 *
 * `App::path('Model'); will return all paths for models`
 *
 * `App::path('Model/Datasource', 'MyPlugin'); will return the path for datasources under the 'MyPlugin' plugin`
 *
 * @param string $type type of path
 * @param string $plugin name of plugin
 * @return array
 * @link http://book.cakephp.org/2.0/en/core-utility-libraries/app.html#App::path
 */
	public static function path($type, $plugin = null) {
		if (!empty(self::$legacy[$type])) {
			$type = self::$legacy[$type];
		}

		if (!empty($plugin)) {
			$path = array();
			$pluginPath = self::pluginPath($plugin);
			$packageFormat = self::_packageFormat();
			if (!empty($packageFormat[$type])) {
				foreach ($packageFormat[$type] as $f) {
					$path[] = sprintf($f, $pluginPath);
				}
			}
			return $path;
		}

		if (!isset(self::$_packages[$type])) {
			return array();
		}
		return self::$_packages[$type];
	}

/**
 * Get all the currently loaded paths from App. Useful for inspecting
 * or storing all paths App knows about.  For a paths to a specific package
 * use App::path()
 *
 * @return array An array of packages and their associated paths.
 * @link http://book.cakephp.org/2.0/en/core-utility-libraries/app.html#App::paths
 */
	public static function paths() {
		return self::$_packages;
	}

/**
 * Sets up each package location on the file system. You can configure multiple search paths
 * for each package, those will be used to look for files one folder at a time in the specified order
 * All paths should be terminated with a Directory separator
 *
 * Usage:
 *
 * `App::build(array(Model' => array('/a/full/path/to/models/'))); will setup a new search path for the Model package`
 *
 * `App::build(array('Model' => array('/path/to/models/')), App::RESET); will setup the path as the only valid path for searching models`
 *
 * `App::build(array('View/Helper' => array('/path/to/helpers/', '/another/path/'))); will setup multiple search paths for helpers`
 *
 * `App::build(array('Service' => array('%s' . 'Service' . DS)), App::REGISTER); will register new package 'Service'`
 *
 * If reset is set to true, all loaded plugins will be forgotten and they will be needed to be loaded again.
 *
 * @param array $paths associative array with package names as keys and a list of directories for new search paths
 * @param boolean|string $mode App::RESET will set paths, App::APPEND with append paths, App::PREPEND will prepend paths (default)
 * 	App::REGISTER will register new packages and their paths, %s in path will be replaced by APP path
 * @return void
 * @link http://book.cakephp.org/2.0/en/core-utility-libraries/app.html#App::build
 */
	public static function build($paths = array(), $mode = App::PREPEND) {
		//Provides Backwards compatibility for old-style package names
		$legacyPaths = array();
		foreach ($paths as $type => $path) {
			if (!empty(self::$legacy[$type])) {
				$type = self::$legacy[$type];
			}
			$legacyPaths[$type] = $path;
		}
		$paths = $legacyPaths;

		if ($mode === App::RESET) {
			foreach ($paths as $type => $new) {
				self::$_packages[$type] = (array)$new;
				self::objects($type, null, false);
			}
			return;
		}

		if (empty($paths)) {
			self::$_packageFormat = null;
		}

		$packageFormat = self::_packageFormat();

		if ($mode === App::REGISTER) {
			foreach ($paths as $package => $formats) {
				if (empty($packageFormat[$package])) {
					$packageFormat[$package] = $formats;
				} else {
					$formats = array_merge($packageFormat[$package], $formats);
					$packageFormat[$package] = array_values(array_unique($formats));
				}
			}
			self::$_packageFormat = $packageFormat;
		}

		$defaults = array();
		foreach ($packageFormat as $package => $format) {
			foreach ($format as $f) {
				$defaults[$package][] = sprintf($f, APP);
			}
		}

		if (empty($paths)) {
			self::$_packages = $defaults;
			return;
		}

		if ($mode === App::REGISTER) {
			$paths = array();
		}

		foreach ($defaults as $type => $default) {
			if (!empty(self::$_packages[$type])) {
				$path = self::$_packages[$type];
			} else {
				$path = $default;
			}

			if (!empty($paths[$type])) {
				$newPath = (array)$paths[$type];

				if ($mode === App::PREPEND) {
					$path = array_merge($newPath, $path);
				} else {
					$path = array_merge($path, $newPath);
				}

				$path = array_values(array_unique($path));
			}

			self::$_packages[$type] = $path;
		}
	}

/**
 * Gets the path that a plugin is on. Searches through the defined plugin paths.
 *
 * Usage:
 *
 * `App::pluginPath('MyPlugin'); will return the full path to 'MyPlugin' plugin'`
 *
 * @param string $plugin CamelCased/lower_cased plugin name to find the path of.
 * @return string full path to the plugin.
 * @link http://book.cakephp.org/2.0/en/core-utility-libraries/app.html#App::pluginPath
 */
	public static function pluginPath($plugin) {
		return Plugin::path($plugin);
	}

/**
 * Finds the path that a theme is on.  Searches through the defined theme paths.
 *
 * Usage:
 *
 * `App::themePath('MyTheme'); will return the full path to the 'MyTheme' theme`
 *
 * @param string $theme theme name to find the path of.
 * @return string full path to the theme.
 * @link http://book.cakephp.org/2.0/en/core-utility-libraries/app.html#App::themePath
 */
	public static function themePath($theme) {
		$themeDir = 'Themed' . DS . Inflector::camelize($theme);
		foreach (self::$_packages['View'] as $path) {
			if (is_dir($path . $themeDir)) {
				return $path . $themeDir . DS;
			}
		}
		return self::$_packages['View'][0] . $themeDir . DS;
	}

/**
 * Returns the full path to a package inside the CakePHP core
 *
 * Usage:
 *
 * `App::core('Cache/Engine'); will return the full path to the cache engines package`
 *
 * @param string $type
 * @return array full path to package
 * @link http://book.cakephp.org/2.0/en/core-utility-libraries/app.html#App::core
 */
	public static function core($type) {
		return array(CAKE . str_replace('/', DS, $type) . DS);
	}

/**
 * Returns an array of objects of the given type.
 *
 * Example usage:
 *
 * `App::objects('plugin');` returns `array('DebugKit', 'Blog', 'User');`
 *
 * `App::objects('Controller');` returns `array('PagesController', 'BlogController');`
 *
 * You can also search only within a plugin's objects by using the plugin dot
 * syntax.
 *
 * `App::objects('MyPlugin.Model');` returns `array('MyPluginPost', 'MyPluginComment');`
 *
 * When scanning directories, files and directories beginning with `.` will be excluded as these
 * are commonly used by version control systems.
 *
 * @param string $type Type of object, i.e. 'Model', 'Controller', 'View/Helper', 'file', 'class' or 'plugin'
 * @param string|array $path Optional Scan only the path given. If null, paths for the chosen type will be used.
 * @param boolean $cache Set to false to rescan objects of the chosen type. Defaults to true.
 * @return mixed Either false on incorrect / miss.  Or an array of found objects.
 * @link http://book.cakephp.org/2.0/en/core-utility-libraries/app.html#App::objects
 */
	public static function objects($type, $path = null, $cache = true) {
		$extension = '/\.php$/';
		$includeDirectories = false;
		$name = $type;

		if ($type === 'plugin') {
			$type = 'plugins';
		}

		if ($type == 'plugins') {
			$extension = '/.*/';
			$includeDirectories = true;
		}

		list($plugin, $type) = pluginSplit($type);

		if (isset(self::$legacy[$type . 's'])) {
			$type = self::$legacy[$type . 's'];
		}

		if ($type === 'file' && !$path) {
			return false;
		} elseif ($type === 'file') {
			$extension = '/\.php$/';
			$name = $type . str_replace(DS, '', $path);
		}

		if (empty(self::$_objects) && $cache === true) {
			self::$_objects = Cache::read('object_map', '_cake_core_');
		}

		$cacheLocation = empty($plugin) ? 'app' : $plugin;

		if ($cache !== true || !isset(self::$_objects[$cacheLocation][$name])) {
			$objects = array();

			if (empty($path)) {
				$path = self::path($type, $plugin);
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

			self::$_objects[$cacheLocation][$name] = $objects;
			if ($cache) {
				self::$_objectCacheChange = true;
			}
		}

		return self::$_objects[$cacheLocation][$name];
	}

/**
 * Method to handle the automatic class loading. It will look for each class' package
 * defined using App::uses() and with this information it will resolve the package name to a full path
 * to load the class from. File name for each class should follow the class name. For instance,
 * if a class is name `MyCustomClass` the file name should be `MyCustomClass.php`
 *
 * @param string $className the name of the class to load
 * @return boolean
 */
	public static function load($className) {
		if (!isset(self::$_classMap[$className])) {
			return false;
		}

		$parts = explode('.', self::$_classMap[$className], 2);
		list($plugin, $package) = count($parts) > 1 ? $parts : array(null, current($parts));

		if ($file = self::_mapped($className, $plugin)) {
			return include $file;
		}
		$paths = self::path($package, $plugin);

		if (empty($plugin)) {
			$appLibs = empty(self::$_packages['Lib']) ? APPLIBS : current(self::$_packages['Lib']);
			$paths[] = $appLibs . $package . DS;
			$paths[] = APP . $package . DS;
			$paths[] = CAKE . $package . DS;
		} else {
			$pluginPath = self::pluginPath($plugin);
			$paths[] = $pluginPath . 'Lib' . DS . $package . DS;
			$paths[] = $pluginPath . $package . DS;
		}
		foreach ($paths as $path) {
			$file = $path . $className . '.php';
			if (file_exists($file)) {
				self::_map($file, $className, $plugin);
				return include $file;
			}
		}

		return false;
	}

/**
 * Returns the package name where a class was defined to be located at
 *
 * @param string $className name of the class to obtain the package name from
 * @return string package name or null if not declared
 * @link http://book.cakephp.org/2.0/en/core-utility-libraries/app.html#App::location
 */
	public static function location($className) {
		if (!empty(self::$_classMap[$className])) {
			return self::$_classMap[$className];
		}
		return null;
	}

/**
 * Initializes the cache for App, registers a shutdown function.
 *
 * @return void
 */
	public static function init() {
		$loader = new ClassLoader(Configure::read('App.namespace'), dirname(APP));
		$loader->register();

		self::$_map += (array)Cache::read('file_map', '_cake_core_');
		self::$_objects += (array)Cache::read('object_map', '_cake_core_');
		register_shutdown_function(array('Cake\Core\App', 'shutdown'));
	}

/**
 * Maps the $name to the $file.
 *
 * @param string $file full path to file
 * @param string $name unique name for this map
 * @param string $plugin camelized if object is from a plugin, the name of the plugin
 * @return void
 */
	protected static function _map($file, $name, $plugin = null) {
		$key = $name;
		if ($plugin) {
			$key = 'plugin.' . $name;
		}
		if ($plugin && empty(self::$_map[$name])) {
			self::$_map[$key] = $file;
		}
		if (!$plugin && empty(self::$_map['plugin.' . $name])) {
			self::$_map[$key] = $file;
		}
		if (!self::$bootstrapping) {
			self::$_cacheChange = true;
		}
	}

/**
 * Returns a file's complete path.
 *
 * @param string $name unique name
 * @param string $plugin camelized if object is from a plugin, the name of the plugin
 * @return mixed file path if found, false otherwise
 */
	protected static function _mapped($name, $plugin = null) {
		$key = $name;
		if ($plugin) {
			$key = 'plugin.' . $name;
		}
		return isset(self::$_map[$key]) ? self::$_map[$key] : false;
	}

/**
 * Sets then returns the templates for each customizable package path
 *
 * @return array templates for each customizable package path
 */
	protected static function _packageFormat() {
		if (empty(self::$_packageFormat)) {
			self::$_packageFormat = array(
				'Model' => array(
					'%s' . 'Model' . DS
				),
				'Model/Behavior' => array(
					'%s' . 'Model' . DS . 'Behavior' . DS
				),
				'Model/Datasource' => array(
					'%s' . 'Model' . DS . 'Datasource' . DS
				),
				'Model/Datasource/Database' => array(
					'%s' . 'Model' . DS . 'Datasource' . DS . 'Database' . DS
				),
				'Model/Datasource/Session' => array(
					'%s' . 'Model' . DS . 'Datasource' . DS . 'Session' . DS
				),
				'Controller' => array(
					'%s' . 'Controller' . DS
				),
				'Controller/Component' => array(
					'%s' . 'Controller' . DS . 'Component' . DS
				),
				'Controller/Component/Auth' => array(
					'%s' . 'Controller' . DS . 'Component' . DS . 'Auth' . DS
				),
				'Controller/Component/Acl' => array(
					'%s' . 'Controller' . DS . 'Component' . DS . 'Acl' . DS
				),
				'View' => array(
					'%s' . 'View' . DS
				),
				'View/Helper' => array(
					'%s' . 'View' . DS . 'Helper' . DS
				),
				'Console' => array(
					'%s' . 'Console' . DS
				),
				'Console/Command' => array(
					'%s' . 'Console' . DS . 'Command' . DS
				),
				'Console/Command/Task' => array(
					'%s' . 'Console' . DS . 'Command' . DS . 'Task' . DS
				),
				'Lib' => array(
					'%s' . 'Lib' . DS
				),
				'Locale' => array(
					'%s' . 'Locale' . DS
				),
				'Vendor' => array(
					'%s' . 'Vendor' . DS,
					dirname(dirname(CAKE)) . DS . 'vendors' . DS,
				),
				'Plugin' => array(
					APP . 'Plugin' . DS,
					dirname(dirname(CAKE)) . DS . 'plugins' . DS
				)
			);
		}

		return self::$_packageFormat;
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
		if (self::$_cacheChange) {
			Cache::write('file_map', array_filter(self::$_map), '_cake_core_');
		}
		if (self::$_objectCacheChange) {
			Cache::write('object_map', self::$_objects, '_cake_core_');
		}

		self::_checkFatalError();
	}

/**
 * Check if a fatal error happened and trigger the configured handler if configured
 *
 * @return void
 */
	protected static function _checkFatalError() {
		$lastError = error_get_last();
		if (!is_array($lastError)) {
			return;
		}

		list(, $log) = ErrorHandler::mapErrorCode($lastError['type']);
		if ($log !== LOG_ERR) {
			return;
		}

		if (PHP_SAPI === 'cli') {
			$errorHandler = Configure::read('Error.consoleHandler');
		} else {
			$errorHandler = Configure::read('Error.handler');
		}
		if (!is_callable($errorHandler)) {
			return;
		}
		call_user_func($errorHandler, $lastError['type'], $lastError['message'], $lastError['file'], $lastError['line'], array());
	}

}
