<?php
/* SVN FILE: $Id$ */
/**
 * Short description for file.
 *
 * Long description for filec
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) :  Rapid Development Framework <http://www.cakephp.org/>
 * Copyright 2005-2007, Cake Software Foundation, Inc.
 *								1785 E. Sahara Avenue, Suite 490-204
 *								Las Vegas, Nevada 89104
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @filesource
 * @copyright		Copyright 2005-2007, Cake Software Foundation, Inc.
 * @link				http://www.cakefoundation.org/projects/info/cakephp CakePHP(tm) Project
 * @package			cake
 * @subpackage		cake.cake.libs
 * @since			CakePHP(tm) v 1.0.0.2363
 * @version			$Revision$
 * @modifiedby		$LastChangedBy$
 * @lastmodified	$Date$
 * @license			http://www.opensource.org/licenses/mit-license.php The MIT License
 */
/**
 * Short description for file.
 *
 * Long description for file
 *
 * @package		cake
 * @subpackage	cake.cake.libs
 */
class Configure extends Object {
/**
 * Hold array with paths to view files
 *
 * @var array
 * @access public
 */
	var $viewPaths = array();
/**
 * Hold array with paths to controller files
 *
 * @var array
 * @access public
 */
	var $controllerPaths = array();
/**
 * Hold array with paths to model files
 *
 * @var array
 * @access public
 */
	var $modelPaths = array();
/**
 * Hold array with paths to helper files
 *
 * @var array
 * @access public
 */
	var $helperPaths = array();
/**
 * Hold array with paths to component files
 *
 * @var array
 * @access public
 */
	var $componentPaths = array();
/**
 * Hold array with paths to behavior files
 *
 * @var array
 * @access public
 */
	var $behaviorPaths = array();
/**
 * Current debug level
 *
 * @var integer
 * @access public
 */
	var $debug = null;
/**
 * Determine if $__objects cache should be wrote
 *
 * @var boolean
 * @access private
 */
	var $__cache = false;
/**
 * Holds and key => value array of objects type
 *
 * @var array
 * @access private
 */
	var $__objects = array();
/**
 * Return a singleton instance of Configure.
 *
 * @return Configure instance
 * @access public
 */
	function &getInstance($boot = true) {
		static $instance = array();
		if (!$instance) {
			$instance[0] =& new Configure;
			$instance[0]->__loadBootstrap($boot);
		}
		return $instance[0];
	}
/**
 * Returns an index of objects of the given type, with the physical path to each object
 *
 * @param string	$type Type of object, i.e. 'model', 'controller', 'helper', or 'plugin'
 * @param mixed		$path Optional
 * @return Configure instance
 * @access public
 */
	function listObjects($type, $path = null) {
		$_this =& Configure::getInstance();
		$objects = array();
		if (empty($_this->__objects)) {
			$_this->__objects = Cache::read('object_map', '_cake_core_');
		}

		if (empty($_this->__objects) || !isset($_this->__objects[$type])) {
			$Inflector =& Inflector::getInstance();

			$types = array(
				'model'			=> array('suffix' => '.php', 'base' => 'AppModel'),
				'controller'	=> array('suffix' => '_controller.php', 'base' => 'AppController'),
				'helper'		=> array('suffix' => '.php', 'base' => 'AppHelper'),
				'plugin'		=> array('suffix' => '', 'base' => null),
				'class'			=> array('suffix' => '.php', 'base' => null)
			);

			if (!isset($types[$type])) {
				return false;
			}

			if (empty($path)) {
				$pathVar = $type . 'Paths';
				$path = $_this->{$pathVar};
			}
			$objects = array();
			$search = array_merge(array(APP), $_this->corePaths($type));

			foreach ($search as $delete) {
				if (in_array($delete, $path)) {
					$remove = array_flip($path);
					unset($remove[$delete]);
					$path = array_flip($remove);
				}
			}

			foreach ((array)$path as $dir) {
				$items = $_this->__list($dir, $types[$type]['suffix']);
				$objects = am($items, $objects);

				/*if (file_exists($path . $name . '.php')) {
					Configure::store('Models', 'class.paths', array($className => array('path' => $path . $name . '.php')));
					require($path . $name . '.php');
					return true;
				}*/
			}
			$_this->__objects[$type] = array_map(array(&$Inflector, 'camelize'), $objects);
			$_this->__cache = true;
		}
		return $_this->__objects[$type];
	}
/**
 * Returns an array of filenames of PHP files in given directory.
 *
 * @param  string $path Path to scan for files
 * @param  string $suffix if false, return only directories. if string, match and return files
 * @return array  List of directories or files in directory
 */
	function __list($path, $suffix = false) {
		if(!class_exists('folder')) {
			uses('folder');
		}
		$items = array();
		$Folder =& new Folder($path);
		$contents = $Folder->read(false, true);
		if (is_array($contents)) {
			if(!$suffix) {
				return $contents[0];
			} else {
				foreach($contents[1] as $item) {
					if (substr($item, -strlen($suffix)) == $suffix) {
						$items[] = substr($item, 0, strlen($item) - strlen($suffix));
					}
				}
			}
		}
		return $items;
	}
/**
 * Used to write a dynamic var in the Configure instance.
 *
 * Usage
 * Configure::write('One.key1', 'value of the Configure::One[key1]');
 * Configure::write(array('One.key1' => 'value of the Configure::One[key1]'));
 * Configure::write('One', array('key1'=>'value of the Configure::One[key1]', 'key2'=>'value of the Configure::One[key2]');
 * Configure::write(array('One.key1' => 'value of the Configure::One[key1]', 'One.key2' => 'value of the Configure::One[key2]'));
 *
 * @param array $config Name of var to write
 * @param mixed $value Value to set for var
 * @access public
 */
	function write($config, $value = null) {
		$_this =& Configure::getInstance();

		if (!is_array($config) && $value !== null) {
			$name = $_this->__configVarNames($config);

			if (count($name) > 1) {
				$_this->{$name[0]}[$name[1]] = $value;
			} else {
				$_this->{$name[0]} = $value;
			}
		} else {

			foreach ($config as $names => $value) {
				$name = $_this->__configVarNames($names);
				if (count($name) > 1) {
					$_this->{$name[0]}[$name[1]] = $value;
				} else {
					$_this->{$name[0]} = $value;
				}
			}
		}

		if ($config == 'debug' || (is_array($config) && in_array('debug', $config))) {
			if ($_this->debug) {
				error_reporting(E_ALL);

				if (function_exists('ini_set')) {
					ini_set('display_errors', 1);
				}

				if (!class_exists('Debugger')) {
					require LIBS . 'debugger.php';
				}
				if (!class_exists('CakeLog')) {
					uses('cake_log');
				}
				Configure::write('log', LOG_NOTICE);
			} else {
				error_reporting(0);
				Configure::write('log', LOG_NOTICE);
			}
		}
	}
/**
 * Used to read Configure::$var
 *
 * Usage
 * Configure::read('Name'); will return all values for Name
 * Configure::read('Name.key'); will return only the value of Configure::Name[key]
 *
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
		if (count($name) > 1) {
			if (isset($_this->{$name[0]}[$name[1]])) {
				return $_this->{$name[0]}[$name[1]];
			}
			return null;
		} else {
			if (isset($_this->{$name[0]})) {
				return $_this->{$name[0]};
			}
			return null;
		}
	}
/**
 * Used to delete a var from the Configure instance.
 *
 * Usage:
 * Configure::delete('Name'); will delete the entire Configure::Name
 * Configure::delete('Name.key'); will delete only the Configure::Name[key]
 *
 * @param string $var the var to be deleted
 * @access public
 */
	function delete($var = null) {
		$_this =& Configure::getInstance();

		$name = $_this->__configVarNames($var);
		if (count($name) > 1) {
			unset($_this->{$name[0]}[$name[1]]);
		} else {
			unset($_this->{$name[0]});
		}
	}
/**
 * Will load a file from app/config/configure_file.php
 * variables in the files should be formated like:
 *  $config['name'] = 'value';
 * These will be used to create dynamic Configure vars.
 *
 * Usage Configure::load('configure_file');
 *
 * @param string $fileName name of file to load, extension must be .php and only the name should be used, not the extenstion
 * @access public
 */
	function load($fileName) {
		$_this =& Configure::getInstance();
		if (file_exists(CONFIGS . $fileName . '.php')) {
			include(CONFIGS . $fileName . '.php');
		} elseif (file_exists(CACHE . 'persistent' . DS . $fileName . '.php')) {
			include(CACHE . 'persistent' . DS . $fileName . '.php');
		} elseif (file_exists(CAKE_CORE_INCLUDE_PATH . DS . 'cake' . DS . 'config' . DS . $fileName . '.php')) {
			include(CAKE_CORE_INCLUDE_PATH . DS . 'cake' . DS . 'config' . DS . $fileName . '.php');
		} else {
			return false;
		}

		if (!isset($config)) {
			trigger_error(sprintf(__("Configure::load() - no variable \$config found in %s.php", true), $fileName), E_USER_WARNING);
			return false;
		}
		return $_this->write($config);
	}
/**
 * Used to determine the current version of CakePHP
 *
 * Usage Configure::version();
 *
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
 * Used to write a config file to the server.
 *
 * Configure::store('Model', 'class.paths', array('Users' => array('path' => 'users', 'plugin' => true)));
 *
 * @param string $type Type of config file to write, ex: Models, Controllers, Helpers, Components
 * @param string $name file name.
 * @param array $data array of values to store.
 * @access public
 */
	function store($type, $name, $data = array()) {
		$_this =& Configure::getInstance();
		$write = true;
		$content = '';
		foreach ($data as $key => $value) {
			$content .= "\$config['$type']['$key']";
			if (is_array($value)) {
				$content .= " = array(";
				foreach ($value as $key1 => $value2) {
					$value2 = addslashes($value2);
					$content .= "'$key1' => '$value2', ";
				}
				$content .= ");\n";
			} else {
				$value = addslashes($value);
				$content .= " = '$value';\n";
			}
		}
		if (is_null($type)) {
			$write = false;
		}
		$_this->__writeConfig($content, $name, $write);
	}
/**
 * Returns key => value list of all paths where core libs are found
 * passing $type will only return the values for $key.
 *
 * @param string $type valid values are: 'model', 'behavior', 'controller', 'component', 'view', 'helper', 'libs', and 'cake'
 * @return array numeric keyed array of core lib paths
 * @access public
 */
	function corePaths($type = null) {
		$paths = Cache::read('core_paths', '_cake_core_');

		if (!$paths) {
			$all = explode(PATH_SEPARATOR, ini_get('include_path'));
			$all = array_flip(array_flip((array_merge(array(CAKE_CORE_INCLUDE_PATH), $all))));

			foreach ($all as $path) {
				$path = rtrim($path, DS);

				if ($path == '.') {
					continue;
				}
				if (is_dir($path .  DS . 'cake' . DS . 'libs' . DS . 'model')) {
					$paths['model'][] = $path .  DS . 'cake' . DS . 'libs' . DS . 'model';
				}
				if (is_dir($path . DS . 'cake' . DS . 'libs' . DS . 'model' . DS . 'behaviors')) {
					$paths['behavior'][] = $path . DS . 'cake' . DS . 'libs' . DS . 'model' . DS . 'behaviors';
				}
				if (is_dir($path . DS . 'cake' . DS . 'libs' . DS . 'controller')) {
					$paths['controller'][] = $path . DS . 'cake' . DS . 'libs' . DS . 'controller';
				}
				if (is_dir($path . DS . 'cake' . DS . 'libs' . DS . 'controller' . DS . 'components')) {
					$paths['component'][] = $path . DS . 'cake' . DS . 'libs' . DS . 'controller' . DS . 'components';
				}
				if (is_dir($path . DS . 'cake' . DS . 'libs' . DS . 'view')) {
					$paths['view'][] = $path . DS . 'cake' . DS . 'libs' . DS . 'view';
				}
				if (is_dir($path . DS . 'cake' . DS . 'libs' . DS . 'view' . DS . 'helpers')) {
					$paths['helper'][] = $path . DS . 'cake' . DS . 'libs' . DS . 'view' . DS . 'helpers';
				}
				if (is_dir($path .  DS . 'cake' . DS . 'libs')) {
					$paths['libs'][] = $path .  DS . 'cake' . DS . 'libs';
				}
				if (is_dir($path .  DS . 'cake')) {
					$paths['cake'][] = $path .  DS . 'cake';
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
 * @access private
 */
	function __writeConfig($content, $name, $write = true) {
		$file = CACHE . 'persistent' . DS . $name . '.php';
		$_this =& Configure::getInstance();
		if ($_this->read() > 0) {
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
				uses('File');
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
				$name = explode(".", $name);
			} else {
				$name = array($name);
			}
		}
		return $name;
	}
/**
 * Sets the paths for the given object type
 *
 * @param array $paths paths defines in config/bootstrap.php
 * @access private
 */
	function __buildPaths($paths) {
		$_this =& Configure::getInstance();
		$core = $_this->corePaths();
		$basePaths = array(
			'plugin' => APP . 'plugins' . DS,
			'behavior' => array(BEHAVIORS),
			'component' => array(COMPONENTS),
			'helper' => array(HELPERS),
			'controller' => array(CONTROLLERS),
			'view' => array(VIEWS),
			'model' => array(MODELS));

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
				$_this->{$pathsVar} = array_merge($_this->{$pathsVar}, (array)$paths[$pathsVar], $merge);
			} else {
				$_this->{$pathsVar} = array_merge($_this->{$pathsVar}, $merge);
			}
		}
	}
/**
 * Loads the app/config/bootstrap.php
 * If the alternative paths are set in this file
 * they will be added to the paths vars
 *
 * @param boolean $boot Load application bootstrap (if true)
 * @access private
 */
	function __loadBootstrap($boot) {
		$_this =& Configure::getInstance(false);
		$modelPaths = $viewPaths = $controllerPaths = $helperPaths = $componentPaths = $behaviorPaths = $pluginPaths = null;

		if ($boot) {
			$_this->write('App', array('base' => false, 'baseUrl' => false, 'dir' => APP_DIR, 'webroot' => WEBROOT_DIR));

			if (!include(APP_PATH . 'config' . DS . 'core.php')) {
				trigger_error(sprintf(__("Can't find application core file. Please create %score.php, and make sure it is readable by PHP.", true), CONFIGS), E_USER_ERROR);
			}

			if (!include(APP_PATH . 'config' . DS . 'bootstrap.php')) {
				trigger_error(sprintf(__("Can't find application bootstrap file. Please create %sbootstrap.php, and make sure it is readable by PHP.", true), CONFIGS), E_USER_ERROR);
			}

			if ($_this->read('Cache.disable') !== true) {
				$cache = Cache::settings();
				if(empty($cache)) {
					trigger_error('Cache not configured properly. Please check Cache::config(); in APP/config/core.php', E_USER_WARNING);
					Cache::config('default', array('engine' => 'File'));
					$cache = Cache::settings();
				}
				$settings = array('prefix' => 'cake_core_', 'path' => CACHE . 'persistent' . DS);
				if (Configure::read() > 1) {
					$settings = array('prefix' => 'cake_core_', 'duration' => 10, 'path' => CACHE . 'persistent' . DS);
				}
				Cache::config('_cake_core_' , array_merge($cache, $settings));
			}
		}

		$_this->__buildPaths(compact('modelPaths', 'viewPaths', 'controllerPaths', 'helperPaths', 'componentPaths', 'behaviorPaths', 'pluginPaths'));

		if (defined('BASE_URL')) {
			trigger_error('BASE_URL Deprecated: See Configure::write(\'App.baseUrl\', \'' . BASE_URL . '\');  in APP/config/core.php', E_USER_WARNING);
			$_this->write('App.baseUrl', BASE_URL);
		}
		if (defined('DEBUG')) {
			trigger_error('DEBUG Deprecated: Use Configure::write(\'debug\', ' . DEBUG . ');  in APP/config/core.php', E_USER_WARNING);
			$_this->write('debug', DEBUG);
		}
		if (defined('CAKE_ADMIN')) {
			trigger_error('CAKE_ADMIN Deprecated: Use Configure::write(\'Routing.admin\', \'' . CAKE_ADMIN . '\');  in APP/config/core.php', E_USER_WARNING);
			$_this->write('Routing.admin', CAKE_ADMIN);
		}
		if (defined('WEBSERVICES')) {
			trigger_error('WEBSERVICES Deprecated: Use Router::parseExtensions(); or add Configure::write(\'Routing.webservices\', \'' . WEBSERVICES . '\');', E_USER_WARNING);
			$_this->write('Routing.webservices', WEBSERVICES);
		}
		if (defined('ACL_CLASSNAME')) {
			trigger_error('ACL_CLASSNAME Deprecated. Use Configure::write(\'Acl.classname\', \'' . ACL_CLASSNAME . '\'); in APP/config/core.php', E_USER_WARNING);
			$_this->write('Acl.classname', ACL_CLASSNAME);
		}
		if (defined('ACL_DATABASE')) {
			trigger_error('ACL_DATABASE Deprecated. Use Configure::write(\'Acl.database\', \'' . ACL_CLASSNAME . '\'); in APP/config/core.php', E_USER_WARNING);
			$_this->write('Acl.database', ACL_CLASSNAME);
		}
		if (defined('CAKE_SESSION_SAVE')) {
			trigger_error('CAKE_SESSION_SAVE Deprecated. Use Configure::write(\'Session.save\', \'' . CAKE_SESSION_SAVE . '\'); in APP/config/core.php', E_USER_WARNING);
			$_this->write('Session.save', CAKE_SESSION_SAVE);
		}
		if (defined('CAKE_SESSION_TABLE')) {
			trigger_error('CAKE_SESSION_TABLE Deprecated. Use Configure::write(\'Session.table\', \'' . CAKE_SESSION_TABLE . '\'); in APP/config/core.php', E_USER_WARNING);
			$_this->write('Session.table', CAKE_SESSION_TABLE);
		}
		if (defined('CAKE_SESSION_STRING')) {
			trigger_error('CAKE_SESSION_STRING Deprecated. Use Configure::write(\'Security.salt\', \'' . CAKE_SESSION_STRING . '\'); in APP/config/core.php', E_USER_WARNING);
			$_this->write('Security.salt', CAKE_SESSION_STRING);
		}
		if (defined('CAKE_SESSION_COOKIE')) {
			trigger_error('CAKE_SESSION_COOKIE Deprecated. Use Configure::write(\'Session.cookie\', \'' . CAKE_SESSION_COOKIE . '\'); in APP/config/core.php', E_USER_WARNING);
			$_this->write('Session.cookie', CAKE_SESSION_COOKIE);
		}
		if (defined('CAKE_SECURITY')) {
			trigger_error('CAKE_SECURITY Deprecated. Use Configure::write(\'Security.level\', \'' . CAKE_SECURITY . '\'); in APP/config/core.php', E_USER_WARNING);
			$_this->write('Security.level', CAKE_SECURITY);
		}
		if (defined('CAKE_SESSION_TIMEOUT')) {
			trigger_error('CAKE_SESSION_TIMEOUT Deprecated. Use Configure::write(\'Session.timeout\', \'' . CAKE_SESSION_TIMEOUT . '\'); in APP/config/core.php', E_USER_WARNING);
			$_this->write('Session.timeout', CAKE_SESSION_TIMEOUT);
		}
		if (defined('AUTO_SESSION')) {
			trigger_error('AUTO_SESSION Deprecated. Use Configure::write(\'Session.start\', \'' . AUTO_SESSION . '\'); in APP/config/core.php', E_USER_WARNING);
			$_this->write('Session.start', AUTO_SESSION);
		}
	}
	function __destruct() {
		$_this = & Configure::getInstance();
		if ($_this->__cache) {
			Cache::write('object_map', array_filter($_this->__objects), '_cake_core_');
		}
	}
}
/**
 * Class and file loader.
 *
 * @since		CakePHP(tm) v 1.2.0.6001
 * @package		cake
 * @subpackage	cake.cake.libs
 */
class App extends Object {
/**
 * Paths to search for files
 *
 * @var array
 * @access public
 */
	var $search = array();
/**
 * Return the file that is loaded
 *
 * @var array
 * @access public
 */
	var $return = false;
/**
 * Determine if $__maps and $__paths cache should be wrote
 *
 * @var boolean
 * @access private
 */
	var $__cache = false;
/**
 * Holds key => values  pairs of $type => file path
 *
 * @var array
 * @access private
 */
	var $__map = array();
/**
 * Holds paths for deep searching of files
 *
 * @var array
 * @access private
 */
	var $__paths = array();
/**
 * Will find Classes based on the $name, or can accept specific file to search for
 *
 * @param mixed $type The type of Class if passed as a string, or all params can be passed as an single array to $type,
 * @param string $name Name of the Class or a unique name for the file
 * @param mixed $parent boolean true if Class Parent should be searched, accepts key => value array('parent' => $parent ,'file' => $file, 'search' => $search);
 * @param array $search paths to search for files, array('path 1', 'path 2', 'path 3');
 * @param string $file full name of the file to search for including extension
 * @param boolean $return, return the loaded file, the file must have a return statement in it to work: return $variable;
 * @return boolean true if Class is already in memory or if file is found and loaded, false if not
 * @access public
 * @todo when App::import() is called without params initialize all the files from the core in one call
 */
	function import($type = null, $name = null, $parent = true, $search = array(), $file = null, $return = false) {
		$plugin = null;
		$directory = null;
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
		$_this =& App::getInstance();

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
					} elseif ($count === 2 && ($type === 'Core' || $type === 'File')){
						$tempType = $value[0];
						$class = $value[1];
					} else {
						$plugin = $value[0] . '.';
						$class = $value[1];
					}
				}
				if (!$_this->import($tempType, $plugin . $class)) {
					//trigger_error(sprintf(__('%1$s type with name %2$s was not found', true), $tempType, $class, E_USER_WARNING));
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

		$ext = $_this->__settings($type, $plugin, $parent);

		if ($name != null && !class_exists($name . $ext['class'])) {
			if ($load = $_this->__mapped($name . $ext['class'], $type, $plugin)) {
				if ($_this->__load($load)) {
					$_this->__overload($type, $name . $ext['class']);
					if($_this->return) {
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
					$find = $ext['path'] . $find;
					$plugin = Inflector::camelize($plugin);
				}
			}

			if (empty($search) && $_this->__load($file)) {
				$directory = false;
			} else {
				$file = $find;
				$directory = $_this->__find($find, true);
			}

			if ($directory !== null) {
				$_this->__cache = true;
				$_this->__map($directory . $file, $name . $ext['class'], $type, $plugin);
				$_this->__overload($type, $name . $ext['class']);
				if( $_this->return) {
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
 * Returns a single instance of App
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
 * Locates the $file in $__paths, searches recursively
 *
 * @param string $file full file name
 * @param boolean $recursive search $__paths recursively
 * @return mixed boolean on fail, $file directory path on success
 * @access private
 */
	function __find($file, $recursive = true) {
		$_this =& App::getInstance();
		if (empty($_this->search)) {
			return null;
		} elseif (is_string($_this->search)) {
			$_this->search = array($_this->search);
		}

		if (empty($_this->__paths)) {
			$_this->__paths = Cache::read('dir_map', '_cake_core_');
		}

		foreach ($_this->search as $path) {
			$path = rtrim($path, DS);
			if ($path === rtrim(APP, DS)) {
				$recursive = false;
			}
			if ($recursive === false) {
				if ($_this->__load($path . DS . $file)) {
					return $path . DS;
				}
				continue;
			}
			if (!isset($_this->__paths[$path])) {
				if (!class_exists('Folder')) {
					uses('Folder');
				}
				$Folder =& new Folder();
				$directories = $Folder->tree($path, false, 'dir');
				$_this->__paths[$path] = $directories;
			}

			foreach ($_this->__paths[$path] as $directory) {
				if ($_this->__load($directory  . DS . $file)) {
					return $directory . DS;
				}
			}
		}
		return null;
	}
/**
 * Attempts to load $file
 *
 * @param string $file full path to file including file name
 * @return boolean
 */
	function __load($file) {
		$_this =& App::getInstance();
		if (file_exists($file)) {
			if(!$_this->return) {
				require($file);
			}
			return true;
		}
		return false;
	}
/**
 * Maps the $name to the $file
 *
 * @param string $file full path to file
 * @param string $name unique name for this map
 * @param string $type type object being mapped
 * @param string $plugin if object is from a plugin, the name of the plugin
 * @access private
 */
	function __map($file, $name, $type, $plugin) {
		$_this =& App::getInstance();

		if ($plugin) {
			$plugin = Inflector::camelize($plugin);
			$_this->__map['Plugin'][$plugin][$type][$name] = $file;
		} else {
			$_this->__map[$type][$name] = $file;
		}
	}
/**
 * Return files complete path
 *
 * @param string $name unique name
 * @param string $type type object
 * @param string $plugin if object is from a plugin, the name of the plugin
 * @return mixed, file path if found, false otherwise
 * @access private
 */
	function __mapped($name, $type, $plugin) {
		$_this =& App::getInstance();

		if ($plugin) {
			$plugin = Inflector::camelize($plugin);
			if (isset($_this->__map['Plugin'][$plugin][$type])) {
				if (array_key_exists($name, $_this->__map['Plugin'][$plugin][$type])) {
					return $_this->__map['Plugin'][$plugin][$type][$name];
				}
				return false;
			}
		}

		if (isset($_this->__map[$type])) {
			if (array_key_exists($name, $_this->__map[$type])) {
				return $_this->__map[$type][$name];

			}
			return false;
		}
	}
/**
 * Used to overload Objects as needed
 *
 * @param string $type Model or Helper
 * @param string $name Class name to overload
 * @access private
 */
	function __overload($type, $name) {
		$overload = array('Model', 'Helper');
		if (in_array($type, $overload)) {
			Overloadable::overload($name);
		}
	}
/**
 * Loads parent classes based on the $type
 * Returns and prefix or suffix needed for load files
 *
 * @param string $type type of object
 * @param string $plugin name of plugin
 * @param boolean $parent false will not attempt to load parent
 * @return array
 * @access private
 */
	function __settings($type, $plugin, $parent) {
		$_this = & App::getInstance();
		if (!$parent) {
			return null;
		}

		if ($plugin) {
			$plugin = Inflector::underscore($plugin);
			$name = Inflector::camelize($plugin);
			$path = $plugin . DS;

		}
		$load = strtolower($type);
		$path = null;

		switch ($load) {
			case 'model':
				if (!class_exists('Model')) {
					$_this->import('Core', 'Model', false, Configure::corePaths('model'));
				}
				$_this->import($type, 'AppModel', false, Configure::read('modelPaths'));
				if ($plugin) {
					$_this->import($type, $plugin . '.' . $name . 'AppModel', false, array(), $plugin . DS . $plugin . '_app_model.php');
					$path = $plugin . DS . 'models' . DS;
				}
			break;
			case 'view':
				return;
			break;
			case 'controller':
				$_this->import($type, 'AppController', false);
				if ($plugin) {
					$_this->import($type, $plugin . '.' . $name . 'AppController', false, array(), $plugin . DS . $plugin . '_app_controller.php');
					$path = $plugin . DS . 'controllers' . DS;
				}
				return array('class' => $type, 'suffix' => $type, 'path' => null);
			break;
			case 'helper':
				$_this->import($type, 'AppHelper', false);
				if ($plugin) {
					$path = $plugin . DS . 'views' . DS . 'helpers' . DS;
				}
				return array('class' => $type, 'suffix' => null, 'path' => $path);
			break;
			case 'component':
				if ($plugin) {
					$path = $plugin . DS . 'controllers' . DS . 'components' . DS;
				}
				return array('class' => $type, 'suffix' => null, 'path' => $path);
			break;
			case 'behavior':
				$_this->import($type, 'Behavior', false);
				if ($plugin) {
					$path = $plugin . DS . 'models' . DS . 'behaviors' . DS;
				}
				return array('class' => $type, 'suffix' => null, 'path' => $path);
			break;
			case 'vendor':
				die('Not Implemented');
				if ($plugin) {
					$path = $plugin . DS . 'vendors' . DS;
				}
			break;
			default:
				$type = $suffix = $path = null;
			break;
		}
		return array('class' => null, 'suffix' => null, 'path' => null);
	}
/**
 * Returns default paths to search
 *
 * @param string $type type of object to be searched
 * @return array list of paths
 * @access private
 */
	function __paths($type) {
		if ($type === 'Core') {
			$path = Configure::corePaths();
			foreach ($path as $key => $value) {
				$count = count($key);
				for ($i = 0; $i < $count; $i++) {
					$paths[] = $path[$key][$i];
				}
			}
			return $paths;
		}
		$paths = Configure::read(strtolower($type) . 'Paths');
		return $paths;
	}
/**
 * Removes file location from map if file has been deleted
 *
 * @param string $name name of object
 * @param string $type type of object
 * @param string $plugin name of plugin
 */
	function __remove($name, $type, $plugin) {
		$_this =& App::getInstance();
		if ($plugin) {
			$plugin = Inflector::camelize($plugin);
			unset($_this->__map['Plugin'][$plugin][$type][$name]);
		} else {
			unset($_this->__map[$type][$name]);
		}
	}
/**
 * Object destructor
 *
 * Write cache file if changes have been made to the $__map or $__paths
 * @access private
 */
	function __destruct() {
		$_this = & App::getInstance();
		if ($_this->__cache) {
			Cache::write('dir_map', array_filter($_this->__paths), '_cake_core_');
			Cache::write('file_map', array_filter($_this->__map), '_cake_core_');
		}
	}
}
?>