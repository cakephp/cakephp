<?php
/* SVN FILE: $Id$ */
/**
 * Short description for file.
 *
 * Long description for filec
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
 * @version       $Revision$
 * @modifiedby    $LastChangedBy$
 * @lastmodified  $Date$
 * @license       http://www.opensource.org/licenses/mit-license.php The MIT License
 */
/**
 * Configuration class (singleton). Used for managing runtime configuration information.
 *
 * @package       cake
 * @subpackage    cake.cake.libs
 * @link          http://book.cakephp.org/view/42/The-Configuration-Class
 */
class Configure extends Object {
/**
 * List of additional path(s) where model files reside.
 *
 * @var array
 * @access public
 */
	var $modelPaths = array();
/**
 * List of additional path(s) where behavior files reside.
 *
 * @var array
 * @access public
 */
	var $behaviorPaths = array();
/**
 * List of additional path(s) where controller files reside.
 *
 * @var array
 * @access public
 */
	var $controllerPaths = array();
/**
 * List of additional path(s) where component files reside.
 *
 * @var array
 * @access public
 */
	var $componentPaths = array();
/**
 * List of additional path(s) where view files reside.
 *
 * @var array
 * @access public
 */
	var $viewPaths = array();
/**
 * List of additional path(s) where helper files reside.
 *
 * @var array
 * @access public
 */
	var $helperPaths = array();
/**
 * List of additional path(s) where plugins reside.
 *
 * @var array
 * @access public
 */
	var $pluginPaths = array();
/**
 * List of additional path(s) where vendor packages reside.
 *
 * @var array
 * @access public
 */
	var $vendorPaths = array();
/**
 * List of additional path(s) where locale files reside.
 *
 * @var array
 * @access public
 */
	var $localePaths = array();
/**
 * List of additional path(s) where console shell files reside.
 *
 * @var array
 * @access public
 */
	var $shellPaths = array();
/**
 * Current debug level.
 *
 * @link          http://book.cakephp.org/view/44/CakePHP-Core-Configuration-Variables
 * @var integer
 * @access public
 */
	var $debug = null;
/**
 * Determines if $__objects cache should be written.
 *
 * @var boolean
 * @access private
 */
	var $__cache = false;
/**
 * Holds and key => value array of objects' types.
 *
 * @var array
 * @access private
 */
	var $__objects = array();
/**
 * Returns a singleton instance of the Configure class.
 *
 * @return Configure instance
 * @access public
 */
	function &getInstance($boot = true) {
		static $instance = array();
		if (!$instance) {
			$instance[0] =& new Configure();
			$instance[0]->__loadBootstrap($boot);
		}
		return $instance[0];
	}
/**
 * Returns an index of objects of the given type, with the physical path to each object.
 *
 * @param string	$type Type of object, i.e. 'model', 'controller', 'helper', or 'plugin'
 * @param mixed		$path Optional
 * @return Configure instance
 * @access public
 */
	function listObjects($type, $path = null, $cache = true) {
		$objects = array();
		$extension = false;
		$name = $type;

		if ($type === 'file' && !$path) {
			return false;
		} elseif ($type === 'file') {
			$extension = true;
			$name = $type . str_replace(DS, '', $path);
		}
		$_this =& Configure::getInstance();

		if (empty($_this->__objects) && $cache === true) {
			$_this->__objects = Cache::read('object_map', '_cake_core_');
		}

		if (empty($_this->__objects) || !isset($_this->__objects[$type]) || $cache !== true) {
			$types = array(
				'model' => array('suffix' => '.php', 'base' => 'AppModel', 'core' => false),
				'behavior' => array('suffix' => '.php', 'base' => 'ModelBehavior'),
				'controller' => array('suffix' => '_controller.php', 'base' => 'AppController'),
				'component' => array('suffix' => '.php', 'base' => null),
				'view' => array('suffix' => '.php', 'base' => null),
				'helper' => array('suffix' => '.php', 'base' => 'AppHelper'),
				'plugin' => array('suffix' => '', 'base' => null),
				'vendor' => array('suffix' => '', 'base' => null),
				'class' => array('suffix' => '.php', 'base' => null),
				'file' => array('suffix' => '.php', 'base' => null)
			);

			if (!isset($types[$type])) {
				return false;
			}
			$objects = array();

			if (empty($path)) {
				$path = $_this->{$type . 'Paths'};
				if (isset($types[$type]['core']) && $types[$type]['core'] === false) {
					array_pop($path);
				}
			}
			$items = array();

			foreach ((array)$path as $dir) {
				if ($type === 'file' || $type === 'class' || strpos($dir, $type) !== false) {
					$items = $_this->__list($dir, $types[$type]['suffix'], $extension);
					$objects = array_merge($items, array_diff($objects, $items));
				}
			}

			if ($type !== 'file') {
				foreach ($objects as $key => $value) {
					$objects[$key] = Inflector::camelize($value);
				}
			}
			if ($cache === true && !empty($objects)) {
				$_this->__objects[$name] = $objects;
				$_this->__cache = true;
			} else {
				return $objects;
			}
		}
		return $_this->__objects[$name];
	}
/**
 * Returns an array of filenames of PHP files in the given directory.
 *
 * @param  string $path Path to scan for files
 * @param  string $suffix if false, return only directories. if string, match and return files
 * @return array  List of directories or files in directory
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
 * @link http://book.cakephp.org/view/412/write
 * @param array $config Name of var to write
 * @param mixed $value Value to set for var
 * @return void
 * @access public
 */
	function write($config, $value = null) {
		$_this =& Configure::getInstance();

		if (!is_array($config)) {
			$config = array($config => $value);
		}

		foreach ($config as $names => $value) {
			$name = $_this->__configVarNames($names);

			switch (count($name)) {
				case 3:
					$_this->{$name[0]}[$name[1]][$name[2]] = $value;
				break;
				case 2:
					$_this->{$name[0]}[$name[1]] = $value;
				break;
				case 1:
					$_this->{$name[0]} = $value;
				break;
			}
		}

		if (isset($config['debug'])) {
			if ($_this->debug) {
				error_reporting(E_ALL & ~E_DEPRECATED);

				if (function_exists('ini_set')) {
					ini_set('display_errors', 1);
				}

				if (!class_exists('Debugger')) {
					require LIBS . 'debugger.php';
				}
				if (!class_exists('CakeLog')) {
					require LIBS . 'cake_log.php';
				}
				Configure::write('log', LOG_NOTICE);
			} else {
				error_reporting(0);
				Configure::write('log', LOG_NOTICE);
			}
		}
	}
/**
 * Used to read information stored in the Configure instance.
 *
 * Usage
 * Configure::read('Name'); will return all values for Name
 * Configure::read('Name.key'); will return only the value of Configure::Name[key]
 *
 * @link          http://book.cakephp.org/view/413/read
 * @param string $var Variable to obtain
 * @return string value of Configure::$var
 * @access public
 */
	function read($var = 'debug') {
		$_this =& Configure::getInstance();

		if ($var === 'debug') {
			if (!isset($_this->debug)) {
				if (defined('DEBUG')) {
					$_this->debug = DEBUG;
				} else {
					$_this->debug = 0;
				}
			}
			return $_this->debug;
		}
		$name = $_this->__configVarNames($var);

		switch (count($name)) {
			case 3:
				if (isset($_this->{$name[0]}[$name[1]][$name[2]])) {
					return $_this->{$name[0]}[$name[1]][$name[2]];
				}
			break;
			case 2:
				if (isset($_this->{$name[0]}[$name[1]])) {
					return $_this->{$name[0]}[$name[1]];
				}
			break;
			case 1:
				if (isset($_this->{$name[0]})) {
					return $_this->{$name[0]};
				}
			break;
		}
		return null;
	}
/**
 * Used to delete a variable from the Configure instance.
 *
 * Usage:
 * Configure::delete('Name'); will delete the entire Configure::Name
 * Configure::delete('Name.key'); will delete only the Configure::Name[key]
 *
 * @link          http://book.cakephp.org/view/414/delete
 * @param string $var the var to be deleted
 * @return void
 * @access public
 */
	function delete($var = null) {
		$_this =& Configure::getInstance();
		$name = $_this->__configVarNames($var);

		if (isset($name[1])) {
			unset($_this->{$name[0]}[$name[1]]);
		} else {
			unset($_this->{$name[0]});
		}
	}
/**
 * Loads a file from app/config/configure_file.php.
 * Config file variables should be formated like:
 *  $config['name'] = 'value';
 * These will be used to create dynamic Configure vars.
 *
 * Usage Configure::load('configure_file');
 *
 * @link          http://book.cakephp.org/view/415/load
 * @param string $fileName name of file to load, extension must be .php and only the name
 *                         should be used, not the extenstion
 * @return mixed false if file not found, void if load successful
 * @access public
 */
	function load($fileName) {
		$found = false;

		if (file_exists(CONFIGS . $fileName . '.php')) {
			include(CONFIGS . $fileName . '.php');
			$found = true;
		} elseif (file_exists(CACHE . 'persistent' . DS . $fileName . '.php')) {
			include(CACHE . 'persistent' . DS . $fileName . '.php');
			$found = true;
		} else {
			foreach (Configure::corePaths('cake') as $key => $path) {
				if (file_exists($path . DS . 'config' . DS . $fileName . '.php')) {
					include($path . DS . 'config' . DS . $fileName . '.php');
					$found = true;
					break;
				}
			}
		}

		if (!$found) {
			return false;
		}

		if (!isset($config)) {
			$error = __("Configure::load() - no variable \$config found in %s.php", true);
			trigger_error(sprintf($error, $fileName), E_USER_WARNING);
			return false;
		}
		return Configure::write($config);
	}
/**
 * Used to determine the current version of CakePHP.
 *
 * Usage Configure::version();
 *
 * @link          http://book.cakephp.org/view/416/version
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
 * Configure::store('Model', 'class.paths', array('Users' => array(
 *      'path' => 'users', 'plugin' => true
 * )));
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
 * Returns a key/value list of all paths where core libs are found.
 * Passing $type only returns the values for a given value of $key.
 *
 * @param string $type valid values are: 'model', 'behavior', 'controller', 'component',
 *                      'view', 'helper', 'datasource', 'libs', and 'cake'
 * @return array numeric keyed array of core lib paths
 * @access public
 */
	function corePaths($type = null) {
		$paths = Cache::read('core_paths', '_cake_core_');
		if (!$paths) {
			$paths = array();
			$openBasedir = ini_get('open_basedir');
			if ($openBasedir) {
				$all = explode(PATH_SEPARATOR, $openBasedir);
				$all = array_flip(array_flip((array_merge(array(CAKE_CORE_INCLUDE_PATH), $all))));
			} else {
				$all = explode(PATH_SEPARATOR, ini_get('include_path'));
				$all = array_flip(array_flip((array_merge(array(CAKE_CORE_INCLUDE_PATH), $all))));
			}
			foreach ($all as $path) {
				if ($path !== DS) {
					$path = rtrim($path, DS);
				}
				if (empty($path) || $path === '.') {
					continue;
				}
				$cake = $path .  DS . 'cake' . DS;
				$libs = $cake . 'libs' . DS;
				if (is_dir($libs)) {
					$paths['libs'][] = $libs;
					$paths['model'][] = $libs . 'model' . DS;
					$paths['behavior'][] = $libs . 'model' . DS . 'behaviors' . DS;
					$paths['controller'][] = $libs . 'controller' . DS;
					$paths['component'][] = $libs . 'controller' . DS . 'components' . DS;
					$paths['view'][] = $libs . 'view' . DS;
					$paths['helper'][] = $libs . 'view' . DS . 'helpers' . DS;
					$paths['cake'][] = $cake;
					$paths['vendor'][] = $path . DS . 'vendors' . DS;
					$paths['shell'][] = $cake . 'console' . DS . 'libs' . DS;
					break;
				}
			}
			Cache::write('core_paths', array_filter($paths), '_cake_core_');
		}
		if ($type && isset($paths[$type])) {
			return $paths[$type];
		}
		return $paths;
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
 * Checks $name for dot notation to create dynamic Configure::$var as an array when needed.
 *
 * @param mixed $name Name to split
 * @return array Name separated in items through dot notation
 * @access private
 */
	function __configVarNames($name) {
		if (is_string($name)) {
			if (strpos($name, ".")) {
				return explode(".", $name);
			}
			return array($name);
		}
		return $name;
	}
/**
 * Build path references. Merges the supplied $paths
 * with the base paths and the default core paths.
 *
 * @param array $paths paths defines in config/bootstrap.php
 * @return void
 * @access public
 */
	function buildPaths($paths) {
		$_this =& Configure::getInstance();
		$core = $_this->corePaths();
		$basePaths = array(
			'model' => array(MODELS),
			'behavior' => array(BEHAVIORS),
			'controller' => array(CONTROLLERS),
			'component' => array(COMPONENTS),
			'view' => array(VIEWS),
			'helper' => array(HELPERS),
			'plugin' => array(APP . 'plugins' . DS),
			'vendor' => array(APP . 'vendors' . DS, VENDORS),
			'locale' => array(APP . 'locale' . DS),
			'shell' => array(),
			'datasource' => array(MODELS . 'datasources')
		);

		foreach ($basePaths as $type => $default) {
			$pathsVar = $type . 'Paths';
			$merge = array();

			if (isset($core[$type])) {
				$merge = $core[$type];
			}
			if ($type === 'model' || $type === 'controller' || $type === 'helper') {
				$merge = array_merge(array(APP), $merge);
			}

			if (!is_array($default)) {
				$default = array($default);
			}
			$_this->{$pathsVar} = $default;

			if (isset($paths[$pathsVar]) && !empty($paths[$pathsVar])) {
				$path = array_flip(array_flip((array_merge(
					$_this->{$pathsVar}, (array)$paths[$pathsVar], $merge
				))));
				$_this->{$pathsVar} = array_values($path);
			} else {
				$path = array_flip(array_flip((array_merge($_this->{$pathsVar}, $merge))));
				$_this->{$pathsVar} = array_values($path);
			}
		}
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
		$modelPaths = $behaviorPaths = $controllerPaths = $componentPaths = $viewPaths = $helperPaths = $pluginPaths = $vendorPaths = $localePaths = $shellPaths = null;

		if ($boot) {
			Configure::write('App', array('base' => false, 'baseUrl' => false, 'dir' => APP_DIR, 'webroot' => WEBROOT_DIR));

			if (!include(CONFIGS . 'core.php')) {
				trigger_error(sprintf(__("Can't find application core file. Please create %score.php, and make sure it is readable by PHP.", true), CONFIGS), E_USER_ERROR);
			}

			if (Configure::read('Cache.disable') !== true) {
				$cache = Cache::config('default');

				if (empty($cache['settings'])) {
					trigger_error('Cache not configured properly. Please check Cache::config(); in APP/config/core.php', E_USER_WARNING);
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
					Cache::config('_cake_core_', array_merge($cache['settings'], array(
						'prefix' => $prefix . 'cake_core_', 'path' => $path . DS . 'persistent' . DS,
						'serialize' => true, 'duration' => $duration
					)));
				}

				if (Cache::config('_cake_model_') === false) {
					Cache::config('_cake_model_', array_merge($cache['settings'], array(
						'prefix' => $prefix . 'cake_model_', 'path' => $path . DS . 'models' . DS,
						'serialize' => true, 'duration' => $duration
					)));
				}
				Cache::config('default');
			}

			if (!include(CONFIGS . 'bootstrap.php')) {
				trigger_error(sprintf(__("Can't find application bootstrap file. Please create %sbootstrap.php, and make sure it is readable by PHP.", true), CONFIGS), E_USER_ERROR);
			}

			Configure::buildPaths(compact(
				'modelPaths', 'viewPaths', 'controllerPaths', 'helperPaths', 'componentPaths',
				'behaviorPaths', 'pluginPaths', 'vendorPaths', 'localePaths', 'shellPaths'
			));
		}
	}
/**
 * Caches the object map when the instance of the Configure class is destroyed
 *
 * @access public
 */
	function __destruct() {
		if ($this->__cache) {
			Cache::write('object_map', array_filter($this->__objects), '_cake_core_');
		}
	}
}
/**
 * Class and file loader.
 *
 * @link          http://book.cakephp.org/view/499/The-App-Class
 * @since         CakePHP(tm) v 1.2.0.6001
 * @package       cake
 * @subpackage    cake.cake.libs
 */
class App extends Object {
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
 * Finds classes based on $name or specific file(s) to search.
 *
 * @link          http://book.cakephp.org/view/529/Using-App-import
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

				if (!App::import($tempType, $plugin . $class)) {
					return false;
				}
			}
			return true;
		}

		if ($name != null && strpos($name, '.') !== false) {
			list($plugin, $name) = explode('.', $name);
		}
		$_this =& App::getInstance();
		$_this->return = $return;

		if (isset($ext)) {
			$file = Inflector::underscore($name) . ".$ext";
		}
		$ext = $_this->__settings($type, $plugin, $parent);

		if ($name != null && !class_exists($name . $ext['class'])) {
			if ($load = $_this->__mapped($name . $ext['class'], $type, $plugin)) {
				if ($_this->__load($load)) {
					$_this->__overload($type, $name . $ext['class']);

					if ($_this->return) {
						$value = include $load;
						return $value;
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
					$plugin = Inflector::camelize($plugin);
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
				$_this->__overload($type, $name . $ext['class']);

				if ($_this->return) {
					$value = include $directory . $file;
					return $value;
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
			$instance[0]->__map = Cache::read('file_map', '_cake_core_');
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
		if (empty($this->search)) {
			return null;
		} elseif (is_string($this->search)) {
			$this->search = array($this->search);
		}

		if (empty($this->__paths)) {
			$this->__paths = Cache::read('dir_map', '_cake_core_');
		}

		foreach ($this->search as $path) {
			$path = rtrim($path, DS);

			if ($path === rtrim(APP, DS)) {
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
				$directories = $Folder->tree($path, false, 'dir');
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
 * @param string $plugin if object is from a plugin, the name of the plugin
 * @access private
 */
	function __map($file, $name, $type, $plugin) {
		if ($plugin) {
			$plugin = Inflector::camelize($plugin);
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
 * @param string $plugin if object is from a plugin, the name of the plugin
 * @return mixed, file path if found, false otherwise
 * @access private
 */
	function __mapped($name, $type, $plugin) {
		if ($plugin) {
			$plugin = Inflector::camelize($plugin);

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
	function __overload($type, $name) {
		if (($type === 'Model' || $type === 'Helper') && strtolower($name) != 'schema') {
			Overloadable::overload($name);
		}
	}
/**
 * Loads parent classes based on $type.
 * Returns a prefix or suffix needed for loading files.
 *
 * @param string $type type of object
 * @param string $plugin name of plugin
 * @param boolean $parent false will not attempt to load parent
 * @return array
 * @access private
 */
	function __settings($type, $plugin, $parent) {
		if (!$parent) {
			return null;
		}

		if ($plugin) {
			$plugin = Inflector::underscore($plugin);
			$name = Inflector::camelize($plugin);
		}
		$path = null;
		$load = strtolower($type);

		switch ($load) {
			case 'model':
				if (!class_exists('Model')) {
					App::import('Core', 'Model', false, Configure::corePaths('model'));
				}
				if (!class_exists('AppModel')) {
					App::import($type, 'AppModel', false, Configure::read('modelPaths'));
				}
				if ($plugin) {
					if (!class_exists($name . 'AppModel')) {
						App::import($type, $plugin . '.' . $name . 'AppModel', false, array(), $plugin . DS . $plugin . '_app_model.php');
					}
					$path = $plugin . DS . 'models' . DS;
				}
				return array('class' => null, 'suffix' => null, 'path' => $path);
			break;
			case 'behavior':
				if ($plugin) {
					$path = $plugin . DS . 'models' . DS . 'behaviors' . DS;
				}
				return array('class' => $type, 'suffix' => null, 'path' => $path);
			break;
			case 'controller':
				App::import($type, 'AppController', false);
				if ($plugin) {
					App::import($type, $plugin . '.' . $name . 'AppController', false, array(), $plugin . DS . $plugin . '_app_controller.php');
					$path = $plugin . DS . 'controllers' . DS;
				}
				return array('class' => $type, 'suffix' => $type, 'path' => $path);
			break;
			case 'component':
				if ($plugin) {
					$path = $plugin . DS . 'controllers' . DS . 'components' . DS;
				}
				return array('class' => $type, 'suffix' => null, 'path' => $path);
			break;
			case 'view':
				if ($plugin) {
					$path = $plugin . DS . 'views' . DS;
				}
				return array('class' => $type, 'suffix' => null, 'path' => $path);
			break;
			case 'helper':
				if (!class_exists('AppHelper')) {
					App::import($type, 'AppHelper', false);
				}
				if ($plugin) {
					$path = $plugin . DS . 'views' . DS . 'helpers' . DS;
				}
				return array('class' => $type, 'suffix' => null, 'path' => $path);
			break;
			case 'vendor':
				if ($plugin) {
					$path = $plugin . DS . 'vendors' . DS;
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

		if ($type === 'core') {
			$path = Configure::corePaths();
			$paths = array();

			foreach ($path as $key => $value) {
				$count = count($key);
				for ($i = 0; $i < $count; $i++) {
					$paths[] = $path[$key][$i];
				}
			}
			return $paths;
		}

		if ($paths = Configure::read($type . 'Paths')) {
			return $paths;
		}

		switch ($type) {
			case 'plugin':
				return array(APP . 'plugins' . DS);
			case 'vendor':
				return array(APP . 'vendors' . DS, VENDORS, APP . 'plugins' . DS);
			case 'controller':
				return array(APP . 'controllers' . DS, APP);
			case 'model':
				return array(APP . 'models' . DS, APP);
			case 'view':
				return array(APP . 'views' . DS);
		}
	}
/**
 * Removes file location from map if the file has been deleted.
 *
 * @param string $name name of object
 * @param string $type type of object
 * @param string $plugin name of plugin
 * @return void
 * @access private
 */
	function __remove($name, $type, $plugin) {
		if ($plugin) {
			$plugin = Inflector::camelize($plugin);
			unset($this->__map['Plugin'][$plugin][$type][$name]);
		} else {
			unset($this->__map[$type][$name]);
		}
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
			$core = Configure::corePaths('cake');
			unset($this->__paths[rtrim($core[0], DS)]);
			Cache::write('dir_map', array_filter($this->__paths), '_cake_core_');
			Cache::write('file_map', array_filter($this->__map), '_cake_core_');
		}
	}
}
?>