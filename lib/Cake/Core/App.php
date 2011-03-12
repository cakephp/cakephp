<?php
/**
 * App class
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
 * @package       cake.libs
 * @since         CakePHP(tm) v 1.2.0.6001
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
/**
 * App is responsible for path managment, class location and class loading.
 *
 * ### Adding paths
 *
 * You can add paths to the search indexes App uses to find classes using `App::build()`.  Adding
 * additional controller paths for example would alter where CakePHP looks for controllers.
 * This allows you to split your application up across the filesystem.
 *
 * ### Inspecting loaded paths
 *
 * You can inspect the currently loaded paths using `App::path('controller')` for example to see loaded
 * controller paths.
 *
 * ### Locating plugins and themes
 *
 * Plugins and Themes can be located with App as well.  Using App::pluginPath('DebugKit') for example, will
 * give you the full path to the DebugKit plugin.  App::themePath('purple'), would give the full path to the
 * `purple` theme.
 *
 * ### Inspecting known objects
 *
 * You can find out which objects App knows about using App::objects('controller') for example to find
 * which application controllers App knows about.
 *
 * @link          http://book.cakephp.org/view/933/The-App-Class
 * @package       cake.libs
 */
class App {

/**
 * List of object types and their properties
 *
 * @var array
 */
	public static $types = array(
		'class' => array('extends' => null, 'core' => true),
		'file' => array('extends' => null, 'core' => true),
		'model' => array('extends' => 'AppModel', 'core' => false),
		'behavior' => array('extends' => 'ModelBehavior', 'core' => true),
		'controller' => array('suffix' => 'Controller', 'extends' => 'AppController', 'core' => true),
		'component' => array('suffix' => 'Component', 'extends' => null, 'core' => true),
		'lib' => array('extends' => null, 'core' => true),
		'view' => array('suffix' => 'View', 'extends' => null, 'core' => true),
		'helper' => array('suffix' => 'Helper', 'extends' => 'AppHelper', 'core' => true),
		'vendor' => array('extends' => null, 'core' => true),
		'shell' => array('suffix' => 'Shell', 'extends' => 'Shell', 'core' => true),
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
 * Determines if $__maps and $__paths cache should be written.
 *
 * @var boolean
 */
	private static $__cache = false;

/**
 * Holds key/value pairs of $type => file path.
 *
 * @var array
 */
	private static $__map = array();

/**
 * Holds paths for deep searching of files.
 *
 * @var array
 */
	private static $__paths = array();

/**
 * Holds loaded files.
 *
 * @var array
 */
	private static $__loaded = array();

/**
 * Holds and key => value array of object types.
 *
 * @var array
 */
	private static $__objects = array();

/**
 * Holds the location of each class
 *
 */
	private static $__classMap = array();

/**
 * Holds the possible paths for each package name
 *
 */
	private static $__packages = array();

/**
 *
 *
 */
	private static $__packageFormat = array();

/**
 * Maps an old style CakePHP class type to the corresponding package
 *
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
		'libs' => 'Lib'
	);

/**
 * Inicates whether the class cache should be stored again because of an addition to it
 *
 */
	private static $_cacheChange = false;

/**
 * Inicates whether the object cache should be stored again because of an addition to it
 *
 */
	private static $_objectCacheChange = false;

/**
 * Used to read information stored path
 *
 * Usage:
 *
 * `App::path('models'); will return all paths for models`
 *
 * @param string $type type of path
 * @return string array
 */
	public static function path($type, $plugin = null) {
		if (!empty(self::$legacy[$type])) {
			$type = self::$legacy[$type];
		}

		if (!empty($plugin)) {
			$path = array();
			$pluginPath = self::pluginPath($plugin);
			if (!empty(self::$__packageFormat[$type])) {
				foreach (self::$__packageFormat[$type] as $f) {
					$path[] = sprintf($f, $pluginPath);
				}
			}
			$path[] = $pluginPath . 'libs' . DS . $type . DS;
			return $path;
		}

		if (!isset(self::$__packages[$type])) {
			return array();
		}
		return self::$__packages[$type];
	}

/**
 * Build path references. Merges the supplied $paths
 * with the base paths and the default core paths.
 *
 * @param array $paths paths defines in config/bootstrap.php
 * @param boolean $reset true will set paths, false merges paths [default] false
 * @return void
 */
	public static function build($paths = array(), $reset = false) {
		if (empty(self::$__packageFormat)) {
			self::$__packageFormat = array(
				'Model' => array('%s' . 'models' . DS),
				'Model/Behavior' => array('%s' . 'models' . DS . 'behaviors' . DS),
				'Model/Datasource' => array('%s' . 'models' . DS . 'datasources' . DS),
				'Model/Datasource/Database' => array('%s' . 'models' . DS . 'datasources' . DS . 'Database' . DS),
				'Model/Datasource/Session' => array('%s' . 'models' . DS . 'datasources' . DS . 'Session' . DS),
				'Controller' => array('%s' . 'controllers' . DS),
				'Controller/Component' => array('%s' . 'controllers' . DS . 'components' . DS),
				'View' => array('%s' . 'views' . DS),
				'View/Helper' => array('%s' . 'views' . DS . 'helpers' . DS),
				'Console' => array(
					'%s' . 'console' . DS . 'shells' . DS,
					'%s' . 'vendors' . DS . 'shells' . DS,
					VENDORS . 'shells' . DS
				),
				'Console/Command' => array(
					'%s' . 'console' . DS . 'shells' . DS,
					'%s' . 'vendors' . DS . 'shells' . DS,
					VENDORS . 'shells' . DS
				),
				'Console/Command/Task' => array(
					'%s' . 'console' . DS . 'shells' . DS . 'tasks' . DS,
					'%s' . 'vendors' . DS . 'shells' . DS . 'tasks' . DS,
					VENDORS . 'shells' . DS . 'tasks' . DS
				),
				'Lib' => array('%s' . 'libs' . DS),
				'locales' => array('%s' . 'locale' . DS),
				'vendors' => array('%s' . 'vendors' . DS, VENDORS),
				'plugins' => array(APP . 'plugins' . DS, CAKE_CORE_INCLUDE_PATH . DS . 'plugins' . DS)
			);
		}

		if ($reset == true) {
			foreach ($paths as $type => $new) {
				if (!empty(self::$legacy[$type])) {
					$type = self::$legacy[$type];
				}
				self::$__packages[$type] = (array)$new;
			}
			return $paths;
		}

		//Provides Backwards compatibility for old-style package names
		$legacyPaths = array();
		foreach ($paths as $type => $path) {
			if (!empty(self::$legacy[$type])) {
				$type = self::$legacy[$type];
			}
			$legacyPaths[$type] = $path;
		}

		$paths = $legacyPaths;
		$defaults = array();
		foreach (self::$__packageFormat as $package => $format) {
			foreach ($format as $f) {
				$defaults[$package][] = sprintf($f, APP);
			}
		}

		$mergeExclude = array('Lib', 'locales', 'vendors', 'plugins');
		$appLibs = empty($paths['Lib']) ? $defaults['Lib'] : $paths['Lib'];

		foreach ($defaults as $type => $default) {
			if (empty(self::$__packages[$type]) || empty($paths)) {
				self::$__packages[$type] = $default;
			}

			if (!empty($paths[$type])) {
				$path = array_merge((array)$paths[$type], self::$__packages[$type]);
			} else {
				$path = self::$__packages[$type];
			}

			self::$__packages[$type] = array_values(array_unique($path));
		}
	}

/**
 * Get the path that a plugin is on.  Searches through the defined plugin paths.
 *
 * @param string $plugin CamelCased/lower_cased plugin name to find the path of.
 * @return string full path to the plugin.
 */
	public static function pluginPath($plugin) {
		$pluginDir = Inflector::underscore($plugin);
		foreach (self::$__packages['plugins'] as $pluginPath) {
			if (is_dir($pluginPath . $pluginDir)) {
				return $pluginPath . $pluginDir . DS ;
			}
		}
		return self::$__packages['plugins'][0] . $pluginDir . DS;
	}

/**
 * Find the path that a theme is on.  Search through the defined theme paths.
 *
 * @param string $theme lower_cased theme name to find the path of.
 * @return string full path to the theme.
 */
	public static function themePath($theme) {
		$themeDir = 'themed' . DS . Inflector::underscore($theme);
		foreach (self::$__packages['View'] as $path) {
			if (is_dir($path . $themeDir)) {
				return $path . $themeDir . DS ;
			}
		}
		return self::$__packages['View'][0] . $themeDir . DS;
	}

/**
 * Returns a key/value list of all paths where core libs are found.
 * Passing $type only returns the values for a given value of $key.
 *
 * @param string $type valid values are: 'cake' ,'plugins', 'vendors' and 'shells'
 * @return array numeric keyed array of core lib paths
 */
	public static function core($type = null) {
		static $paths = false;
		if (!$paths) {
			$paths = array();
			$root = dirname(dirname(LIBS)) . DS;
			$paths['cake'][] = LIBS;
			$paths['plugins'][] = $root . 'plugins' . DS;
			$paths['vendors'][] = $root . 'vendors' . DS;
			$paths['shells'][] = LIBS . 'Console' . DS . 'Command' . DS;
			// Provide BC path to vendors/shells
			$paths['shells'][] = $root . 'vendors' . DS . 'shells' . DS;
		}
		if ($type) {
			return isset($paths[$type]) ? $paths[$type] : array(LIBS . $type . DS);
		}
		return $paths;
	}

/**
 * Returns an array of objects of the given type.
 *
 * Example usage:
 *
 * `App::objects('plugin');` returns `array('DebugKit', 'Blog', 'User');`
 *
 * You can also search only within a plugin's objects by using the plugin dot
 * syntax.
 *
 * `App::objects('MyPlugin.model');` returns `array('Post', 'Comment');`
 *
 * @param string $type Type of object, i.e. 'model', 'controller', 'helper', or 'plugin'
 * @param mixed $path Optional Scan only the path given. If null, paths for the chosen
 *   type will be used.
 * @param boolean $cache Set to false to rescan objects of the chosen type. Defaults to true.
 * @return mixed Either false on incorrect / miss.  Or an array of found objects.
 */
	public static function objects($type, $path = null, $cache = true) {
		$objects = array();
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

		if (empty(self::$__objects) && $cache === true) {
			self::$__objects = Cache::read('object_map', '_cake_core_');
		}

		$cacheLocation = empty($plugin) ? 'app' : $plugin;

		if ($cache !== true || !isset(self::$__objects[$cacheLocation][$name])) {
			$objects = array();

			if (empty($path)) {
				$path = self::path($type, $plugin);
			}

			$items = array();

			foreach ((array)$path as $dir) {
				if ($dir != APP && is_dir($dir)) {
					$files = new RegexIterator(new DirectoryIterator($dir), $extension);
					foreach ($files as $file) {
						if (!$file->isDot()) {
							$isDir = $file->isDir() ;
							if ($isDir && $includeDirectories) {
								$objects[] = basename($file);
							} elseif (!$includeDirectories && !$isDir) {
								$objects[] = substr(basename($file), 0, -4);
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

			if ($cache === true) {
				self::$__cache = true;
			}
			if ($plugin) {
				return $objects;
			}
			self::$__objects[$cacheLocation][$name] = $objects;
			self::$_objectCacheChange = true;
		}

		return self::$__objects[$cacheLocation][$name];
	}

/**
 * Allows you to modify the object listings that App maintains inside of it
 * Useful for testing
 *
 * @param string $type Type of object listing you are changing
 * @param array $values The values $type should be set to.
 * @return void
 */
	public static function setObjects($type, $values) {
		list($plugin, $type) = pluginSplit($type);
		$cacheLocation = empty($plugin) ? 'app' : $plugin;
		self::$__objects[$cacheLocation][$type] = $values;
	}

	public static function uses($className, $location) {
		self::$__classMap[$className] = $location;
	}

	public static function load($className) {
		if (isset(self::$__classMap[$className])) {
			if ($file = self::__mapped($className)) {
				return include $file;
			}

			$parts = explode('.', self::$__classMap[$className], 2);
			list($plugin, $package) = count($parts) > 1 ? $parts : array(null, current($parts));
			$paths = self::path($package, $plugin);

			if (empty($plugin)) {
				$appLibs = empty(self::$__packages['libs']) ? APPLIBS : current(self::$__packages['libs']);
				$paths[] =  $appLibs . self::$__classMap[$className] . DS;
				$paths[] = LIBS . self::$__classMap[$className] . DS;
			}

			foreach ($paths as $path) {
				$file = $path . $className . '.php';
				if (file_exists($file)) {
					self::__map($file, $className);
					return include $file;
				}
			}

			//To help apps migrate to 2.0 old style file names are allowed
			foreach ($paths as $path) {
				$underscored = Inflector::underscore($className);
				$tries = array($path . $underscored . '.php');
				$parts = explode('_', $underscored);
				if (count($parts) > 1) {
					array_pop($parts);
					$tries[] = $path . implode('_', $parts) . '.php';
				}
				foreach ($tries as $file) {
					if (file_exists($file)) {
						self::__map($file, $className);
						return include $file;
					}
				}
			}
		}

		return false;
	}

/**
 * Finds classes based on $name or specific file(s) to search.  Calling App::import() will
 * not construct any classes contained in the files. It will only find and require() the file.
 *
 * @link          http://book.cakephp.org/view/934/Using-App-import
 * @param mixed $type The type of Class if passed as a string, or all params can be passed as
 *                    an single array to $type,
 * @param string $name Name of the Class or a unique name for the file
 * @param mixed $parent boolean true if Class Parent should be searched, accepts key => value
 *              array('parent' => $parent ,'file' => $file, 'search' => $search, 'ext' => '$ext');
 *              $ext allows setting the extension of the file name
 *              based on Inflector::underscore($name) . ".$ext";
 * @param array $search paths to search for files, array('path 1', 'path 2', 'path 3');
 * @param string $file full name of the file to search for including extension
 * @param boolean $return, return the loaded file, the file must have a return
 *                         statement in it to work: return $variable;
 * @return boolean true if Class is already in memory or if file is found and loaded, false if not
 */
	public static function import($type = null, $name = null, $parent = true, $search = array(), $file = null, $return = false) {
		$ext = $plugin = $directory = null;

		if (is_array($type)) {
			extract($type, EXTR_OVERWRITE);
		}

		if (is_array($parent)) {
			extract($parent, EXTR_OVERWRITE);
		}

		if ($name == null && $file == null) {
			return false;
		}

		if (is_array($name)) {
			foreach ($name as $class) {
				if (!App::import(compact('type', 'parent', 'search', 'file', 'return') + array('name' => $class))) {
					return false;
				}
			}
			return true;
		}

		$originalType = $type = strtolower($type);
		$specialPackage = in_array($type, array('file', 'vendor'));
		if (!$specialPackage && isset(self::$legacy[$type . 's'])) {
			$type = self::$legacy[$type . 's'];
		}
		list($plugin, $name) = pluginSplit($name);
		if (!empty($plugin)) {
			$plugin = Inflector::camelize($plugin);
		}

		if (!$specialPackage) {
			return self::_loadClass($name, $plugin, $type, $originalType, $parent);
		}

		if ($type == 'file' && !empty($file)) {
			return self::_loadFile($name, $plugin, $search, $file, $return);
		}

		if ($type == 'vendor') {
			return self::_loadVendor($name, $plugin, $file, $ext);
		}

		return false;
	}

/**
 * Helper function to include classes
 * This is a compatibility wrapper around using App::uses() and automatic class loading
 *
 * @param string $name unique name of the file for identifying it inside the application
 * @param string $plugin camel cased plugin name if any
 * @param string $type name of the packed where the class is located
 * @param string $file filename if known, the $name param will be used otherwise
 * @param string $originalType type name as supplied initially by the user
 * @param boolean $parent whether to load the class parent or not
 * @return boolean true indicating the successful load and existence of the class
 */
	private function _loadClass($name, $plugin, $type, $originalType, $parent) {
		if ($type == 'Console/Command' && $name == 'Shell') {
			$type = 'Console';
		} else if (isset(self::$types[$originalType]['suffix'])) {
			$suffix = self::$types[$originalType]['suffix'];
			$name .= ($suffix == $name) ? '' : $suffix;
		}

		if ($parent && isset(self::$types[$originalType]['extends'])) {
			$extends = self::$types[$originalType]['extends'];
			App::uses($extends, $type);
			if ($plugin && in_array($originalType, array('controller', 'model'))) {
				App::uses($plugin . $extends, $plugin . '.' .$type);
			}
		}
		if ($plugin) {
			$plugin .= '.';
		}
		$name = Inflector::camelize($name);
		App::uses($name, $plugin . $type);
		return class_exists($name);
	}

/**
 * Helper function to include single files
 *
 * @param string $name unique name of the file for identifying it inside the application
 * @param string $plugin camel cased plugin name if any
 * @param array $search list of paths to search the file into
 * @param string $file filename if known, the $name param will be used otherwise
 * @param boolean $return whether this function should return the contents of the file after being parsed by php or just a success notice
 * @return mixed, if $return contents of the file after php parses it, boolean indicating success otherwise
 */
	private function _loadFile($name, $plugin, $search, $file, $return) {
		$mapped = self::__mapped($name, $plugin);
		if ($mapped) {
			$file = $mapped;
		} else if (!empty($search)) {
			foreach ($search as $path) {
				$found = false;
				if (file_exists($path . $file)) {
					$file = $path . $file;
					$found = true;
					break;
				}
				if (empty($found)) {
					$file = false;
				}
			}
		}
		if (!empty($file) && file_exists($file)) {
			self::__map($file, $name, $plugin);
			$returnValue = include $file;
			if ($return) {
				return $returnValue;
			}
			return (bool) $returnValue;
		}
		return false;
	}

/**
 * Helper function to load files from vendors folders
 *
 * @param string $name unique name of the file for identifying it inside the application
 * @param string $plugin camel cased plugin name if any
 * @param string $file file name if known
 * @param string $ext file extension if known
 * @return boolean true if the file was loaded successfully, false otherwise
 */
	private function _loadVendor($name, $plugin, $file, $ext) {
		if ($mapped = self::__mapped($name, $plugin)) {
			return (bool) include_once($mapped);
		}
		$fileTries = array();
		$paths = ($plugin) ? App::path('vendors', $plugin) : App::path('vendors');
		if (empty($ext)) {
			$ext = 'php';
		}
		if (empty($file)) {
			$fileTries[] = $name . '.' . $ext;
			$fileTries[] = Inflector::underscore($name) . '.' . $ext;
		} else {
			$fileTries[] = $file;
		}

		foreach ($fileTries as $file) {
			foreach ($paths as $path) {
				if (file_exists($path . $file)) {
					self::__map($path . $file, $name, $plugin);
					return (bool) include($path . $file); 
				}
			}
		}
		return false;
	}

/**
 * Initializes the cache for App, registers a shutdown function.
 *
 * @return void
 */
	public static function init() {
		self::$__map = (array)Cache::read('file_map', '_cake_core_');
		self::$__objects = (array)Cache::read('object_map', '_cake_core_');
		register_shutdown_function(array('App', 'shutdown'));
	}

/**
 * Maps the $name to the $file.
 *
 * @param string $file full path to file
 * @param string $name unique name for this map
 * @param string $plugin camelized if object is from a plugin, the name of the plugin
 * @return void
 * @access private
 */
	private static function __map($file, $name, $plugin = null) {
		if ($plugin) {
			self::$__map['Plugin'][$plugin][$name] = $file;
		} else {
			self::$__map[$name] = $file;
		}
		self::$_cacheChange = true;
	}

/**
 * Returns a file's complete path.
 *
 * @param string $name unique name
 * @param string $plugin camelized if object is from a plugin, the name of the plugin
 * @return mixed, file path if found, false otherwise
 * @access private
 */
	private static function __mapped($name, $plugin = null) {
		if ($plugin) {
			if (isset(self::$__map['Plugin'][$plugin][$name])) {
				return self::$__map['Plugin'][$plugin][$name];
			}
			return false;
		}

		if (isset(self::$__map[$name])) {
			return self::$__map[$name];
		}
		return false;
	}

/**
 * Object destructor.
 *
 * Writes cache file if changes have been made to the $__map or $__paths
 *
 * @return void
 */
	public static function shutdown() {
		if (self::$__cache && self::$_cacheChange) {
			Cache::write('file_map', array_filter(self::$__map), '_cake_core_');
		}
		if (self::$__cache && self::$_objectCacheChange) {
			Cache::write('object_map', self::$__objects, '_cake_core_');
		}
	}
}
