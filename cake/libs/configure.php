<?php
/**
 * App and Configure classes
 *
 * PHP versions 4 and 5
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
 * Configuration class (singleton). Used for managing runtime configuration information.
 *
 * @package       cake
 * @subpackage    cake.cake.libs
 * @link          http://book.cakephp.org/view/924/The-Configuration-Class
 */
class Configure extends Object {

/**
 * Current debug level.
 *
 * @link          http://book.cakephp.org/view/931/CakePHP-Core-Configuration-Variables
 * @var integer
 * @access public
 */
	var $debug = 0;

/**
 * Returns a singleton instance of the Configure class.
 *
 * @return Configure instance
 * @access public
 */
	function &getInstance($boot = true) {
		static $instance = array();
		if (!$instance) {
			if (!class_exists('Set')) {
				require LIBS . 'set.php';
			}
			$instance[0] =& new Configure();
			$instance[0]->__loadBootstrap($boot);
		}
		return $instance[0];
	}

/**
 * Used to store a dynamic variable in the Configure instance.
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
 * @access public
 */
	function write($config, $value = null) {
		$_this =& Configure::getInstance();

		if (!is_array($config)) {
			$config = array($config => $value);
		}

		foreach ($config as $name => $value) {
			if (strpos($name, '.') === false) {
				$_this->{$name} = $value;
			} else {
				$names = explode('.', $name, 4);
				switch (count($names)) {
					case 2:
						$_this->{$names[0]}[$names[1]] = $value;
					break;
					case 3:
						$_this->{$names[0]}[$names[1]][$names[2]] = $value;
					case 4:
						$names = explode('.', $name, 2);
						if (!isset($_this->{$names[0]})) {
							$_this->{$names[0]} = array();
						}
						$_this->{$names[0]} = Set::insert($_this->{$names[0]}, $names[1], $value);
					break;
				}
			}
		}

		if (isset($config['debug']) || isset($config['log'])) {
			$reporting = 0;
			if ($_this->debug) {
				if (!class_exists('Debugger')) {
					require LIBS . 'debugger.php';
				}
				$reporting = E_ALL & ~E_DEPRECATED;
				if (function_exists('ini_set')) {
					ini_set('display_errors', 1);
				}
			} elseif (function_exists('ini_set')) {
				ini_set('display_errors', 0);
			}

			if (isset($_this->log) && $_this->log) {
				if (!class_exists('CakeLog')) {
					require LIBS . 'cake_log.php';
				}
				if (is_integer($_this->log) && !$_this->debug) {
					$reporting = $_this->log;
				} else {
					$reporting = E_ALL & ~E_DEPRECATED;
				}
			}
			error_reporting($reporting);
		}
		return true;
	}

/**
 * Used to read information stored in the Configure instance.
 *
 * Usage:
 * {{{
 * Configure::read('Name'); will return all values for Name
 * Configure::read('Name.key'); will return only the value of Configure::Name[key]
 * }}}
 *
 * @link http://book.cakephp.org/view/927/read
 * @param string $var Variable to obtain.  Use '.' to access array elements.
 * @return string value of Configure::$var
 * @access public
 */
	function read($var = 'debug') {
		$_this =& Configure::getInstance();

		if ($var === 'debug') {
			return $_this->debug;
		}

		if (strpos($var, '.') !== false) {
			$names = explode('.', $var, 3);
			$var = $names[0];
		}
		if (!isset($_this->{$var})) {
			return null;
		}
		if (!isset($names[1])) {
			return $_this->{$var};
		}
		switch (count($names)) {
			case 2:
				if (isset($_this->{$var}[$names[1]])) {
					return $_this->{$var}[$names[1]];
				}
			break;
			case 3:
				if (isset($_this->{$var}[$names[1]][$names[2]])) {
					return $_this->{$var}[$names[1]][$names[2]];
				}
				if (!isset($_this->{$var}[$names[1]])) {
					return null;
				}
				return Set::classicExtract($_this->{$var}[$names[1]], $names[2]);
			break;
		}
		return null;
	}

/**
 * Used to delete a variable from the Configure instance.
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
 * @access public
 */
	function delete($var = null) {
		$_this =& Configure::getInstance();

		if (strpos($var, '.') === false) {
			unset($_this->{$var});
			return;
		}

		$names = explode('.', $var, 2);
		$_this->{$names[0]} = Set::remove($_this->{$names[0]}, $names[1]);
	}

/**
 * Loads a file from app/config/configure_file.php.
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
 * @access public
 */
	function load($fileName) {
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
			trigger_error(sprintf(__('Configure::load() - no variable $config found in %s.php', true), $fileName), E_USER_WARNING);
			return false;
		}
		return Configure::write($config);
	}

/**
 * Used to determine the current version of CakePHP.
 *
 * Usage `Configure::version();`
 *
 * @link http://book.cakephp.org/view/930/version
 * @return string Current version of CakePHP
 * @access public
 */
	function version() {
		$_this =& Configure::getInstance();

		if (!isset($_this->Cake['version'])) {
			require(CORE_PATH . 'cake' . DS . 'config' . DS . 'config.php');
			$_this->write($config);
		}
		return $_this->Cake['version'];
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
 * @access public
 */
	function store($type, $name, $data = array()) {
		$write = true;
		$content = '';

		foreach ($data as $key => $value) {
			$content .= "\$config['$type']['$key'] = " . var_export($value, true) . ";\n";
		}
		if (is_null($type)) {
			$write = false;
		}
		Configure::__writeConfig($content, $name, $write);
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
	function __writeConfig($content, $name, $write = true) {
		$file = CACHE . 'persistent' . DS . $name . '.php';

		if (Configure::read() > 0) {
			$expires = "+10 seconds";
		} else {
			$expires = "+999 days";
		}
		$cache = cache('persistent' . DS . $name . '.php', null, $expires);

		if ($cache === null) {
			cache('persistent' . DS . $name . '.php', "<?php\n\$config = array();\n", $expires);
		}

		if ($write === true) {
			if (!class_exists('File')) {
				require LIBS . 'file.php';
			}
			$fileClass = new File($file);

			if ($fileClass->writable()) {
				$fileClass->append($content);
			}
		}
	}

/**
 * @deprecated
 * @see App::objects()
 */
	function listObjects($type, $path = null, $cache = true) {
		return App::objects($type, $path, $cache);
	}

/**
 * @deprecated
 * @see App::core()
 */
	function corePaths($type = null) {
		return App::core($type);
	}

/**
 * @deprecated
 * @see App::build()
 */
	function buildPaths($paths) {
		return App::build($paths);
	}

/**
 * Loads app/config/bootstrap.php.
 * If the alternative paths are set in this file
 * they will be added to the paths vars.
 *
 * @param boolean $boot Load application bootstrap (if true)
 * @return void
 * @access private
 */
	function __loadBootstrap($boot) {
		if ($boot) {
			Configure::write('App', array('base' => false, 'baseUrl' => false, 'dir' => APP_DIR, 'webroot' => WEBROOT_DIR, 'www_root' => WWW_ROOT));

			if (!include(CONFIGS . 'core.php')) {
				trigger_error(sprintf(__("Can't find application core file. Please create %score.php, and make sure it is readable by PHP.", true), CONFIGS), E_USER_ERROR);
			}

			if (Configure::read('Cache.disable') !== true) {
				$cache = Cache::config('default');

				if (empty($cache['settings'])) {
					trigger_error(__('Cache not configured properly. Please check Cache::config(); in APP/config/core.php', true), E_USER_WARNING);
					$cache = Cache::config('default', array('engine' => 'File'));
				}
				$path = $prefix = $duration = null;

				if (!empty($cache['settings']['path'])) {
					$path = realpath($cache['settings']['path']);
				} else {
					$prefix = $cache['settings']['prefix'];
				}

				if (Configure::read() >= 1) {
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
				Cache::config('default');
			}
			App::build();
			if (!include(CONFIGS . 'bootstrap.php')) {
				trigger_error(sprintf(__("Can't find application bootstrap file. Please create %sbootstrap.php, and make sure it is readable by PHP.", true), CONFIGS), E_USER_ERROR);
			}
		}
	}
}

/**
 * Class/file loader and path management.
 *
 * @link          http://book.cakephp.org/view/933/The-App-Class
 * @since         CakePHP(tm) v 1.2.0.6001
 * @package       cake
 * @subpackage    cake.cake.libs
 */
class App extends Object {

/**
 * List of object types and their properties
 *
 * @var array
 * @access public
 */
	var $types = array(
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
 * @access public
 */
	var $models = array();

/**
 * List of additional path(s) where behavior files reside.
 *
 * @var array
 * @access public
 */
	var $behaviors = array();

/**
 * List of additional path(s) where controller files reside.
 *
 * @var array
 * @access public
 */
	var $controllers = array();

/**
 * List of additional path(s) where component files reside.
 *
 * @var array
 * @access public
 */
	var $components = array();

/**
 * List of additional path(s) where datasource files reside.
 *
 * @var array
 * @access public
 */
	var $datasources = array();

/**
 * List of additional path(s) where libs files reside.
 *
 * @var array
 * @access public
 */
	var $libs = array();
/**
 * List of additional path(s) where view files reside.
 *
 * @var array
 * @access public
 */
	var $views = array();

/**
 * List of additional path(s) where helper files reside.
 *
 * @var array
 * @access public
 */
	var $helpers = array();

/**
 * List of additional path(s) where plugins reside.
 *
 * @var array
 * @access public
 */
	var $plugins = array();

/**
 * List of additional path(s) where vendor packages reside.
 *
 * @var array
 * @access public
 */
	var $vendors = array();

/**
 * List of additional path(s) where locale files reside.
 *
 * @var array
 * @access public
 */
	var $locales = array();

/**
 * List of additional path(s) where console shell files reside.
 *
 * @var array
 * @access public
 */
	var $shells = array();

/**
 * Paths to search for files.
 *
 * @var array
 * @access public
 */
	var $search = array();

/**
 * Whether or not to return the file that is loaded.
 *
 * @var boolean
 * @access public
 */
	var $return = false;

/**
 * Determines if $__maps and $__paths cache should be written.
 *
 * @var boolean
 * @access private
 */
	var $__cache = false;

/**
 * Holds key/value pairs of $type => file path.
 *
 * @var array
 * @access private
 */
	var $__map = array();

/**
 * Holds paths for deep searching of files.
 *
 * @var array
 * @access private
 */
	var $__paths = array();

/**
 * Holds loaded files.
 *
 * @var array
 * @access private
 */
	var $__loaded = array();

/**
 * Holds and key => value array of object types.
 *
 * @var array
 * @access private
 */
	var $__objects = array();

/**
 * Used to read information stored path
 *
 * Usage:
 *
 * `App::path('models'); will return all paths for models`
 *
 * @param string $type type of path
 * @return string array
 * @access public
 */
	function path($type) {
		$_this =& App::getInstance();
		if (!isset($_this->{$type})) {
			return array();
		}
		return $_this->{$type};
	}

/**
 * Build path references. Merges the supplied $paths
 * with the base paths and the default core paths.
 *
 * @param array $paths paths defines in config/bootstrap.php
 * @param boolean $reset true will set paths, false merges paths [default] false
 * @return void
 * @access public
 */
	function build($paths = array(), $reset = false) {
		$_this =& App::getInstance();
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
			'shells' => array(APP . 'vendors' . DS . 'shells' . DS, VENDORS . 'shells' . DS),
			'vendors' => array(APP . 'vendors' . DS, VENDORS),
			'plugins' => array(APP . 'plugins' . DS)
		);

		if ($reset == true) {
			foreach ($paths as $type => $new) {
				$_this->{$type} = (array)$new;
			}
			return $paths;
		}

		$core = $_this->core();
		$app = array('models' => true, 'controllers' => true, 'helpers' => true);

		foreach ($defaults as $type => $default) {
			$merge = array();

			if (isset($app[$type])) {
				$merge = array(APP);
			}
			if (isset($core[$type])) {
				$merge = array_merge($merge, (array)$core[$type]);
			}

			if (empty($_this->{$type}) || empty($paths)) {
				$_this->{$type} = $default;
			}

			if (!empty($paths[$type])) {
				$path = array_flip(array_flip(array_merge(
					(array)$paths[$type], $_this->{$type}, $merge
				)));
				$_this->{$type} = array_values($path);
			} else {
				$path = array_flip(array_flip(array_merge($_this->{$type}, $merge)));
				$_this->{$type} = array_values($path);
			}
		}
	}

/**
 * Get the path that a plugin is on.  Searches through the defined plugin paths.
 *
 * @param string $plugin CamelCased/lower_cased plugin name to find the path of.
 * @return string full path to the plugin.
 */
	function pluginPath($plugin) {
		$_this =& App::getInstance();
		$pluginDir = Inflector::underscore($plugin);
		for ($i = 0, $length = count($_this->plugins); $i < $length; $i++) {
			if (is_dir($_this->plugins[$i] . $pluginDir)) {
				return $_this->plugins[$i] . $pluginDir . DS ;
			}
		}
		return $_this->plugins[0] . $pluginDir . DS;
	}

/**
 * Find the path that a theme is on.  Search through the defined theme paths.
 *
 * @param string $theme lower_cased theme name to find the path of.
 * @return string full path to the theme.
 */
	function themePath($theme) {
		$_this =& App::getInstance();
		$themeDir = 'themed' . DS . Inflector::underscore($theme);
		for ($i = 0, $length = count($_this->views); $i < $length; $i++) {
			if (is_dir($_this->views[$i] . $themeDir)) {
				return $_this->views[$i] . $themeDir . DS ;
			}
		}
		return $_this->views[0] . $themeDir . DS;
	}

/**
 * Returns a key/value list of all paths where core libs are found.
 * Passing $type only returns the values for a given value of $key.
 *
 * @param string $type valid values are: 'model', 'behavior', 'controller', 'component',
 *    'view', 'helper', 'datasource', 'libs', and 'cake'
 * @return array numeric keyed array of core lib paths
 * @access public
 */
	function core($type = null) {
		static $paths = false;
		if ($paths === false) {
			$paths = Cache::read('core_paths', '_cake_core_');
		}
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
			$paths['shells'][] = $cake . 'console' . DS . 'libs' . DS;

			Cache::write('core_paths', array_filter($paths), '_cake_core_');
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
 * @access public
 */
	function objects($type, $path = null, $cache = true) {
		$objects = array();
		$extension = false;
		$name = $type;

		if ($type === 'file' && !$path) {
			return false;
		} elseif ($type === 'file') {
			$extension = true;
			$name = $type . str_replace(DS, '', $path);
		}
		$_this =& App::getInstance();

		if (empty($_this->__objects) && $cache === true) {
			$_this->__objects = Cache::read('object_map', '_cake_core_');
		}

		if (!isset($_this->__objects[$name]) || $cache !== true) {
			$types = $_this->types;

			if (!isset($types[$type])) {
				return false;
			}
			$objects = array();

			if (empty($path)) {
				$path = $_this->{"{$type}s"};
				if (isset($types[$type]['core']) && $types[$type]['core'] === false) {
					array_pop($path);
				}
			}
			$items = array();

			foreach ((array)$path as $dir) {
				if ($dir != APP) {
					$items = $_this->__list($dir, $types[$type]['suffix'], $extension);
					$objects = array_merge($items, array_diff($objects, $items));
				}
			}

			if ($type !== 'file') {
				foreach ($objects as $key => $value) {
					$objects[$key] = Inflector::camelize($value);
				}
			}

			if ($cache === true) {
				$_this->__cache = true;
			}
			$_this->__objects[$name] = $objects;
		}

		return $_this->__objects[$name];
	}

/**
 * Finds classes based on $name or specific file(s) to search.
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
 * @access public
 */
	function import($type = null, $name = null, $parent = true, $search = array(), $file = null, $return = false) {
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
		$_this =& App::getInstance();
		$_this->return = $return;

		if (isset($ext)) {
			$file = Inflector::underscore($name) . ".{$ext}";
		}
		$ext = $_this->__settings($type, $plugin, $parent);
		if ($name != null && !class_exists($name . $ext['class'])) {
			if ($load = $_this->__mapped($name . $ext['class'], $type, $plugin)) {
				if ($_this->__load($load)) {
					$_this->__overload($type, $name . $ext['class'], $parent);

					if ($_this->return) {
						return include($load);
					}
					return true;
				} else {
					$_this->__remove($name . $ext['class'], $type, $plugin);
					$_this->__cache = true;
				}
			}
			if (!empty($search)) {
				$_this->search = $search;
			} elseif ($plugin) {
				$_this->search = $_this->__paths('plugin');
			} else {
				$_this->search = $_this->__paths($type);
			}
			$find = $file;

			if ($find === null) {
				$find = Inflector::underscore($name . $ext['suffix']).'.php';

				if ($plugin) {
					$paths = $_this->search;
					foreach ($paths as $key => $value) {
						$_this->search[$key] = $value . $ext['path'];
					}
				}
			}

			if (strtolower($type) !== 'vendor' && empty($search) && $_this->__load($file)) {
				$directory = false;
			} else {
				$file = $find;
				$directory = $_this->__find($find, true);
			}

			if ($directory !== null) {
				$_this->__cache = true;
				$_this->__map($directory . $file, $name . $ext['class'], $type, $plugin);
				$_this->__overload($type, $name . $ext['class'], $parent);

				if ($_this->return) {
					return include($directory . $file);
				}
				return true;
			}
			return false;
		}
		return true;
	}

/**
 * Returns a single instance of App.
 *
 * @return object
 * @access public
 */
	function &getInstance() {
		static $instance = array();
		if (!$instance) {
			$instance[0] =& new App();
			$instance[0]->__map = (array)Cache::read('file_map', '_cake_core_');
		}
		return $instance[0];
	}

/**
 * Locates the $file in $__paths, searches recursively.
 *
 * @param string $file full file name
 * @param boolean $recursive search $__paths recursively
 * @return mixed boolean on fail, $file directory path on success
 * @access private
 */
	function __find($file, $recursive = true) {
		static $appPath = false;

		if (empty($this->search)) {
			return null;
		} elseif (is_string($this->search)) {
			$this->search = array($this->search);
		}

		if (empty($this->__paths)) {
			$this->__paths = Cache::read('dir_map', '_cake_core_');
		}

		foreach ($this->search as $path) {
			if ($appPath === false) {
				$appPath = rtrim(APP, DS);
			}
			$path = rtrim($path, DS);

			if ($path === $appPath) {
				$recursive = false;
			}
			if ($recursive === false) {
				if ($this->__load($path . DS . $file)) {
					return $path . DS;
				}
				continue;
			}

			if (!isset($this->__paths[$path])) {
				if (!class_exists('Folder')) {
					require LIBS . 'folder.php';
				}
				$Folder =& new Folder();
				$directories = $Folder->tree($path, array('.svn', '.git', 'CVS', 'tests', 'templates'), 'dir');
				sort($directories);
				$this->__paths[$path] = $directories;
			}

			foreach ($this->__paths[$path] as $directory) {
				if ($this->__load($directory . DS . $file)) {
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
	function __load($file) {
		if (empty($file)) {
			return false;
		}
		if (!$this->return && isset($this->__loaded[$file])) {
			return true;
		}
		if (file_exists($file)) {
			if (!$this->return) {
				require($file);
				$this->__loaded[$file] = true;
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
	function __map($file, $name, $type, $plugin) {
		if ($plugin) {
			$this->__map['Plugin'][$plugin][$type][$name] = $file;
		} else {
			$this->__map[$type][$name] = $file;
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
	function __mapped($name, $type, $plugin) {
		if ($plugin) {
			if (isset($this->__map['Plugin'][$plugin][$type]) && isset($this->__map['Plugin'][$plugin][$type][$name])) {
				return $this->__map['Plugin'][$plugin][$type][$name];
			}
			return false;
		}

		if (isset($this->__map[$type]) && isset($this->__map[$type][$name])) {
			return $this->__map[$type][$name];
		}
		return false;
	}

/**
 * Used to overload objects as needed.
 *
 * @param string $type Model or Helper
 * @param string $name Class name to overload
 * @access private
 */
	function __overload($type, $name, $parent) {
		if (($type === 'Model' || $type === 'Helper') && $parent !== false) {
			Overloadable::overload($name);
		}
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
	function __settings($type, $plugin, $parent) {
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
 * @access private
 */
	function __paths($type) {
		$type = strtolower($type);
		$paths = array();

		if ($type === 'core') {
			return App::core('libs');
		}
		if (isset($this->{$type . 's'})) {
			return $this->{$type . 's'};
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
 * @access private
 */
	function __remove($name, $type, $plugin) {
		if ($plugin) {
			unset($this->__map['Plugin'][$plugin][$type][$name]);
		} else {
			unset($this->__map[$type][$name]);
		}
	}

/**
 * Returns an array of filenames of PHP files in the given directory.
 *
 * @param string $path Path to scan for files
 * @param string $suffix if false, return only directories. if string, match and return files
 * @return array  List of directories or files in directory
 * @access private
 */
	function __list($path, $suffix = false, $extension = false) {
		if (!class_exists('Folder')) {
			require LIBS . 'folder.php';
		}
		$items = array();
		$Folder =& new Folder($path);
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
 * @access private
 */
	function __destruct() {
		if ($this->__cache) {
			$core = App::core('cake');
			unset($this->__paths[rtrim($core[0], DS)]);
			Cache::write('dir_map', array_filter($this->__paths), '_cake_core_');
			Cache::write('file_map', array_filter($this->__map), '_cake_core_');
			Cache::write('object_map', $this->__objects, '_cake_core_');
		}
	}
}
