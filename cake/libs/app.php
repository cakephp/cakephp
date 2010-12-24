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
 * additional controller paths for example would alter where CakePHP looks for controllers when you 
 * call App::import('Controller', 'Posts');  This allows you to split your application up across the filesystem.
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
		'class' => array('suffix' => '.php', 'extends' => null, 'core' => true),
		'file' => array('suffix' => '.php', 'extends' => null, 'core' => true),
		'model' => array('suffix' => '.php', 'extends' => 'AppModel', 'core' => false),
		'behavior' => array('suffix' => '.php', 'extends' => 'ModelBehavior', 'core' => true),
		'controller' => array('suffix' => '_controller.php', 'extends' => 'AppController', 'core' => true),
		'component' => array('suffix' => '.php', 'extends' => null, 'core' => true),
		'lib' => array('suffix' => '.php', 'extends' => null, 'core' => true),
		'view' => array('suffix' => '.php', 'extends' => null, 'core' => true),
		'helper' => array('suffix' => '.php', 'extends' => 'AppHelper', 'core' => true),
		'vendor' => array('suffix' => '', 'extends' => null, 'core' => true),
		'shell' => array('suffix' => '.php', 'extends' => 'Shell', 'core' => true),
		'plugin' => array('suffix' => '', 'extends' => null, 'core' => true)
	);

/**
 * List of additional path(s) where model files reside.
 *
 * @var array
 */
	public static $models = array();

/**
 * List of additional path(s) where behavior files reside.
 *
 * @var array
 */
	public static $behaviors = array();

/**
 * List of additional path(s) where controller files reside.
 *
 * @var array
 */
	public static $controllers = array();

/**
 * List of additional path(s) where component files reside.
 *
 * @var array
 */
	public static $components = array();

/**
 * List of additional path(s) where datasource files reside.
 *
 * @var array
 */
	public static $datasources = array();

/**
 * List of additional path(s) where libs files reside.
 *
 * @var array
 */
	public static $libs = array();

/**
 * List of additional path(s) where view files reside.
 *
 * @var array
 */
	public static $views = array();

/**
 * List of additional path(s) where helper files reside.
 *
 * @var array
 */
	public static $helpers = array();

/**
 * List of additional path(s) where plugins reside.
 *
 * @var array
 */
	public static $plugins = array();

/**
 * List of additional path(s) where vendor packages reside.
 *
 * @var array
 */
	public static $vendors = array();

/**
 * List of additional path(s) where locale files reside.
 *
 * @var array
 */
	public static $locales = array();

/**
 * List of additional path(s) where console shell files reside.
 *
 * @var array
 */
	public static $shells = array();

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
 * Used to read information stored path
 *
 * Usage:
 *
 * `App::path('models'); will return all paths for models`
 *
 * @param string $type type of path
 * @return string array
 */
	public static function path($type) {
		if (!isset(self::${$type})) {
			return array();
		}
		return self::${$type};
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
		$defaults = array(
			'models' => array(MODELS),
			'behaviors' => array(BEHAVIORS),
			'datasources' => array(MODELS . 'datasources'),
			'controllers' => array(CONTROLLERS),
			'components' => array(COMPONENTS),
			'libs' => array(APPLIBS),
			'views' => array(VIEWS),
			'helpers' => array(HELPERS),
			'locales' => array(APP . 'locale' . DS),
			'shells' => array(
				APP . 'console' . DS . 'shells' . DS,
				APP . 'vendors' . DS . 'shells' . DS, 
				VENDORS . 'shells' . DS
			),
			'vendors' => array(APP . 'vendors' . DS, VENDORS),
			'plugins' => array(APP . 'plugins' . DS)
		);

		if ($reset == true) {
			foreach ($paths as $type => $new) {
				self::${$type} = (array)$new;
			}
			return $paths;
		}

		$core = self::core();
		$app = array('models' => true, 'controllers' => true, 'helpers' => true);

		foreach ($defaults as $type => $default) {
			$merge = array();

			if (isset($app[$type])) {
				$merge = array(APP);
			}
			if (isset($core[$type])) {
				$merge = array_merge($merge, (array)$core[$type]);
			}

			if (empty(self::${$type}) || empty($paths)) {
				self::${$type} = $default;
			}

			if (!empty($paths[$type])) {
				$path = array_flip(array_flip(array_merge(
					(array)$paths[$type], self::${$type}, $merge
				)));
				self::${$type} = array_values($path);
			} else {
				$path = array_flip(array_flip(array_merge(self::${$type}, $merge)));
				self::${$type} = array_values($path);
			}
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
		for ($i = 0, $length = count(self::$plugins); $i < $length; $i++) {
			if (is_dir(self::$plugins[$i] . $pluginDir)) {
				return self::$plugins[$i] . $pluginDir . DS ;
			}
		}
		return self::$plugins[0] . $pluginDir . DS;
	}

/**
 * Find the path that a theme is on.  Search through the defined theme paths.
 *
 * @param string $theme lower_cased theme name to find the path of.
 * @return string full path to the theme.
 */
	public static function themePath($theme) {
		$themeDir = 'themed' . DS . Inflector::underscore($theme);
		for ($i = 0, $length = count(self::$views); $i < $length; $i++) {
			if (is_dir(self::$views[$i] . $themeDir)) {
				return self::$views[$i] . $themeDir . DS ;
			}
		}
		return self::$views[0] . $themeDir . DS;
	}

/**
 * Returns a key/value list of all paths where core libs are found.
 * Passing $type only returns the values for a given value of $key.
 *
 * @param string $type valid values are: 'model', 'behavior', 'controller', 'component',
 *    'view', 'helper', 'datasource', 'libs', and 'cake'
 * @return array numeric keyed array of core lib paths
 */
	public static function core($type = null) {
		static $paths = false;
		if (!$paths) {
			$paths = array();
			$libs = dirname(__FILE__) . DS;
			$cake = dirname($libs) . DS;
			$path = dirname($cake) . DS;

			$paths['cake'][] = $cake;
			$paths['libs'][] = $libs;
			$paths['models'][] = $libs . 'model' . DS;
			$paths['datasources'][] = $libs . 'model' . DS . 'datasources' . DS;
			$paths['behaviors'][] = $libs . 'model' . DS . 'behaviors' . DS;
			$paths['controllers'][] = $libs . 'controller' . DS;
			$paths['components'][] = $libs . 'controller' . DS . 'components' . DS;
			$paths['views'][] = $libs . 'view' . DS;
			$paths['helpers'][] = $libs . 'view' . DS . 'helpers' . DS;
			$paths['plugins'][] = $path . 'plugins' . DS;
			$paths['vendors'][] = $path . 'vendors' . DS;
			$paths['shells'][] = $cake . 'console' . DS . 'shells' . DS;
			// Provide BC path to vendors/shells
			$paths['shells'][] = $path . 'vendors' . DS . 'shells' . DS;
		}
		if ($type && isset($paths[$type])) {
			return $paths[$type];
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
 * @param string $type Type of object, i.e. 'model', 'controller', 'helper', or 'plugin'
 * @param mixed $path Optional Scan only the path given. If null, paths for the chosen
 *   type will be used.
 * @param boolean $cache Set to false to rescan objects of the chosen type. Defaults to true.
 * @return mixed Either false on incorrect / miss.  Or an array of found objects.
 */
	public static function objects($type, $path = null, $cache = true) {
		$objects = array();
		$extension = false;
		$name = $type;

		if ($type === 'file' && !$path) {
			return false;
		} elseif ($type === 'file') {
			$extension = true;
			$name = $type . str_replace(DS, '', $path);
		}

		if (empty(self::$__objects) && $cache === true) {
			self::$__objects = Cache::read('object_map', '_cake_core_');
		}

		if (!isset(self::$__objects[$name]) || $cache !== true) {
			$types = self::$types;

			if (!isset($types[$type])) {
				return false;
			}
			$objects = array();

			if (empty($path)) {
				$path = self::${"{$type}s"};
				if (isset($types[$type]['core']) && $types[$type]['core'] === false) {
					array_pop($path);
				}
			}
			$items = array();

			foreach ((array)$path as $dir) {
				if ($dir != APP) {
					$items = self::__list($dir, $types[$type]['suffix'], $extension);
					$objects = array_merge($items, array_diff($objects, $items));
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
			self::$__objects[$name] = $objects;
		}

		return self::$__objects[$name];
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
		self::$__objects[$type] = $values;
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
		$plugin = $directory = null;

		if (is_array($type)) {
			extract($type, EXTR_OVERWRITE);
		}

		if (is_array($parent)) {
			extract($parent, EXTR_OVERWRITE);
		}

		if ($name === null && $file === null) {
			$name = $type;
			$type = 'Core';
		} elseif ($name === null) {
			$type = 'File';
		}

		if (is_array($name)) {
			foreach ($name as $class) {
				$tempType = $type;
				$plugin = null;

				if (strpos($class, '.') !== false) {
					$value = explode('.', $class);
					$count = count($value);

					if ($count > 2) {
						$tempType = $value[0];
						$plugin = $value[1] . '.';
						$class = $value[2];
					} elseif ($count === 2 && ($type === 'Core' || $type === 'File')) {
						$tempType = $value[0];
						$class = $value[1];
					} else {
						$plugin = $value[0] . '.';
						$class = $value[1];
					}
				}

				if (!App::import($tempType, $plugin . $class, $parent)) {
					return false;
				}
			}
			return true;
		}

		if ($name != null && strpos($name, '.') !== false) {
			list($plugin, $name) = explode('.', $name);
			$plugin = Inflector::camelize($plugin);
		}
		self::$return = $return;

		if (isset($ext)) {
			$file = Inflector::underscore($name) . ".{$ext}";
		}
		$ext = self::__settings($type, $plugin, $parent);
		$className = $name;
		if (strpos($className, '/') !== false) {
			$className = substr($className, strrpos($className, '/') + 1);
		}
		if ($name != null && !class_exists($className . $ext['class'])) {
			if ($load = self::__mapped($name . $ext['class'], $type, $plugin)) {
				if (self::__load($load)) {
					if (self::$return) {
						return include($load);
					}
					return true;
				} else {
					self::__remove($name . $ext['class'], $type, $plugin);
					self::$__cache = true;
				}
			}
			if (!empty($search)) {
				self::$search = $search;
			} elseif ($plugin) {
				self::$search = self::__paths('plugin');
			} else {
				self::$search = self::__paths($type);
			}
			$find = $file;

			if ($find === null) {
				$find = Inflector::underscore($name . $ext['suffix']).'.php';

				if ($plugin) {
					$paths = self::$search;
					foreach ($paths as $key => $value) {
						self::$search[$key] = $value . $ext['path'];
					}
				}
			}

			if (strtolower($type) !== 'vendor' && empty($search) && self::__load($file)) {
				$directory = false;
			} else {
				$file = $find;
				$directory = self::__find($find, true);
			}

			if ($directory !== null) {
				self::$__cache = true;
				self::__map($directory . $file, $name . $ext['class'], $type, $plugin);

				if (self::$return) {
					return include($directory . $file);
				}
				return true;
			}
			return false;
		}
		return true;
	}

/**
 * Initializes the cache for App, registers a shutdown function.
 *
 * @return void
 */
	public static function init() {
		self::$__map = (array)Cache::read('file_map', '_cake_core_');
		register_shutdown_function(array('App', 'shutdown'));
	}

/**
 * Locates the $file in $__paths, searches recursively.
 *
 * @param string $file full file name
 * @param boolean $recursive search $__paths recursively
 * @return mixed boolean on fail, $file directory path on success
 */
	private static function __find($file, $recursive = true) {
		static $appPath = false;

		if (empty(self::$search)) {
			return null;
		} elseif (is_string(self::$search)) {
			$this->search = array(self::$search);
		}

		if (empty(self::$__paths)) {
			self::$__paths = Cache::read('dir_map', '_cake_core_');
		}

		foreach (self::$search as $path) {
			if ($appPath === false) {
				$appPath = rtrim(APP, DS);
			}
			$path = rtrim($path, DS);

			if ($path === $appPath) {
				$recursive = false;
			}
			if ($recursive === false) {
				if (self::__load($path . DS . $file)) {
					return $path . DS;
				}
				continue;
			}

			if (!isset(self::$__paths[$path])) {
				if (!class_exists('Folder')) {
					require LIBS . 'folder.php';
				}
				$Folder = new Folder();
				$directories = $Folder->tree($path, array('.svn', '.git', 'CVS', 'tests', 'templates'), 'dir');
				sort($directories);
				self::$__paths[$path] = $directories;
			}

			foreach (self::$__paths[$path] as $directory) {
				if (self::__load($directory . DS . $file)) {
					return $directory . DS;
				}
			}
		}
		return null;
	}

/**
 * Attempts to load $file.
 *
 * @param string $file full path to file including file name
 * @return boolean
 * @access private
 */
	private static function __load($file) {
		if (empty($file)) {
			return false;
		}
		if (!self::$return && isset(self::$__loaded[$file])) {
			return true;
		}
		if (file_exists($file)) {
			if (!self::$return) {
				require($file);
				self::$__loaded[$file] = true;
			}
			return true;
		}
		return false;
	}

/**
 * Maps the $name to the $file.
 *
 * @param string $file full path to file
 * @param string $name unique name for this map
 * @param string $type type object being mapped
 * @param string $plugin camelized if object is from a plugin, the name of the plugin
 * @return void
 * @access private
 */
	private static function __map($file, $name, $type, $plugin) {
		if ($plugin) {
			self::$__map['Plugin'][$plugin][$type][$name] = $file;
		} else {
			self::$__map[$type][$name] = $file;
		}
	}

/**
 * Returns a file's complete path.
 *
 * @param string $name unique name
 * @param string $type type object
 * @param string $plugin camelized if object is from a plugin, the name of the plugin
 * @return mixed, file path if found, false otherwise
 * @access private
 */
	private static function __mapped($name, $type, $plugin) {
		if ($plugin) {
			if (isset(self::$__map['Plugin'][$plugin][$type]) && isset(self::$__map['Plugin'][$plugin][$type][$name])) {
				return self::$__map['Plugin'][$plugin][$type][$name];
			}
			return false;
		}

		if (isset(self::$__map[$type]) && isset(self::$__map[$type][$name])) {
			return self::$__map[$type][$name];
		}
		return false;
	}

/**
 * Loads parent classes based on $type.
 * Returns a prefix or suffix needed for loading files.
 *
 * @param string $type type of object
 * @param string $plugin camelized name of plugin
 * @param boolean $parent false will not attempt to load parent
 * @return array
 * @access private
 */
	private static function __settings($type, $plugin, $parent) {
		if (!$parent) {
			return array('class' => null, 'suffix' => null, 'path' => null);
		}

		if ($plugin) {
			$pluginPath = Inflector::underscore($plugin);
		}
		$path = null;
		$load = strtolower($type);

		switch ($load) {
			case 'model':
				if (!class_exists('Model')) {
					require LIBS . 'model' . DS . 'model.php';
				}
				if (!class_exists('AppModel')) {
					App::import($type, 'AppModel', false);
				}
				if ($plugin) {
					if (!class_exists($plugin . 'AppModel')) {
						App::import($type, $plugin . '.' . $plugin . 'AppModel', false, array(), $pluginPath . DS . $pluginPath . '_app_model.php');
					}
					$path = $pluginPath . DS . 'models' . DS;
				}
				return array('class' => null, 'suffix' => null, 'path' => $path);
			break;
			case 'behavior':
				if ($plugin) {
					$path = $pluginPath . DS . 'models' . DS . 'behaviors' . DS;
				}
				return array('class' => $type, 'suffix' => null, 'path' => $path);
			break;
			case 'datasource':
				if ($plugin) {
					$path = $pluginPath . DS . 'models' . DS . 'datasources' . DS;
				}
				return array('class' => $type, 'suffix' => null, 'path' => $path);
			case 'controller':
				App::import($type, 'AppController', false);
				if ($plugin) {
					App::import($type, $plugin . '.' . $plugin . 'AppController', false, array(), $pluginPath . DS . $pluginPath . '_app_controller.php');
					$path = $pluginPath . DS . 'controllers' . DS;
				}
				return array('class' => $type, 'suffix' => $type, 'path' => $path);
			break;
			case 'component':
				App::import('Core', 'Component', false);
				if ($plugin) {
					$path = $pluginPath . DS . 'controllers' . DS . 'components' . DS;
				}
				return array('class' => $type, 'suffix' => null, 'path' => $path);
			break;
			case 'lib':
				if ($plugin) {
					$path = $pluginPath . DS . 'libs' . DS;
				}
				return array('class' => null, 'suffix' => null, 'path' => $path);
			break;
			case 'view':
				App::import('View', 'View', false);
				if ($plugin) {
					$path = $pluginPath . DS . 'views' . DS;
				}
				return array('class' => $type, 'suffix' => null, 'path' => $path);
			break;
			case 'helper':
				if (!class_exists('AppHelper')) {
					App::import($type, 'AppHelper', false);
				}
				if ($plugin) {
					$path = $pluginPath . DS . 'views' . DS . 'helpers' . DS;
				}
				return array('class' => $type, 'suffix' => null, 'path' => $path);
			break;
			case 'shell':
				if (!class_exists('Shell')) {
					App::import($type, 'Shell', false);
				}
				if (!class_exists('AppShell')) {
					App::import($type, 'AppShell', false);
				}
				if ($plugin) {
					$path = $pluginPath . DS . 'console' . DS . 'shells' . DS;
				}
				return array('class' => $type, 'suffix' => null, 'path' => $path);
			break;
			case 'vendor':
				if ($plugin) {
					$path = $pluginPath . DS . 'vendors' . DS;
				}
				return array('class' => null, 'suffix' => null, 'path' => $path);
			break;
			default:
				$type = $suffix = $path = null;
			break;
		}
		return array('class' => null, 'suffix' => null, 'path' => null);
	}

/**
 * Returns default search paths.
 *
 * @param string $type type of object to be searched
 * @return array list of paths
 */
	private static function __paths($type) {
		$type = strtolower($type);
		$paths = array();

		if ($type === 'core') {
			return App::core('libs');
		}
		if (isset(self::${$type . 's'})) {
			return self::${$type . 's'};
		}
		return $paths;
	}

/**
 * Removes file location from map if the file has been deleted.
 *
 * @param string $name name of object
 * @param string $type type of object
 * @param string $plugin camelized name of plugin
 * @return void
 */
	private static function __remove($name, $type, $plugin) {
		if ($plugin) {
			unset(self::$__map['Plugin'][$plugin][$type][$name]);
		} else {
			unset(self::$__map[$type][$name]);
		}
	}

/**
 * Returns an array of filenames of PHP files in the given directory.
 *
 * @param string $path Path to scan for files
 * @param string $suffix if false, return only directories. if string, match and return files
 * @return array  List of directories or files in directory
 */
	private static function __list($path, $suffix = false, $extension = false) {
		if (!class_exists('Folder')) {
			require LIBS . 'folder.php';
		}
		$items = array();
		$Folder = new Folder($path);
		$contents = $Folder->read(false, true);

		if (is_array($contents)) {
			if (!$suffix) {
				return $contents[0];
			} else {
				foreach ($contents[1] as $item) {
					if (substr($item, - strlen($suffix)) === $suffix) {
						if ($extension) {
							$items[] = $item;
						} else {
							$items[] = substr($item, 0, strlen($item) - strlen($suffix));
						}
					}
				}
			}
		}
		return $items;
	}

/**
 * Object destructor.
 *
 * Writes cache file if changes have been made to the $__map or $__paths
 *
 * @return void
 */
	public static function shutdown() {
		if (self::$__cache) {
			$core = App::core('cake');
			unset(self::$__paths[rtrim($core[0], DS)]);
			Cache::write('dir_map', array_filter(self::$__paths), '_cake_core_');
			Cache::write('file_map', array_filter(self::$__map), '_cake_core_');
			Cache::write('object_map', self::$__objects, '_cake_core_');
		}
	}
}
