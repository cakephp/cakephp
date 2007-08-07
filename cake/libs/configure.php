<?php
/* SVN FILE: $Id$ */
/**
 * Short description for file.
 *
 * Long description for file
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

		foreach ((array)$path as $dir) {
			$items = $_this->__list($dir, $types[$type]['suffix']);
			$objects = am($items, $objects);

			/*if (file_exists($path . $name . '.php')) {
				Configure::store('Models', 'class.paths', array($className => array('path' => $path . $name . '.php')));
				require($path . $name . '.php');
				Overloadable::overload($className);
				return true;
			}*/
		}
		return array_map(array(&$Inflector, 'camelize'), $objects);
	}
/**
 * Returns an array of filenames of PHP files in given directory.
 *
 * @param  string $path Path to scan for files
 * @return array  List of files in directory
 */
	function __list($path, $suffix = null) {
		$dir = opendir($path);
		$items = array();

		while (false !== ($item = readdir($dir))) {
			if (substr($item, 0, 1) != '.') {
				if (empty($suffix) || (!empty($suffix) && substr($item, -strlen($suffix)) == $suffix)) {
					if (!empty($suffix)) {
						$item = substr($item, 0, strlen($item) - strlen($suffix));
					}
					$items[] = $item;
				}
			}
		}
		closedir($dir);
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
				$_this->debug = DEBUG;
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
 * Sets the var modelPaths
 *
 * @param array $modelPaths Path to model files
 * @access private
 */
	function __buildModelPaths($modelPaths) {
		$_this =& Configure::getInstance();
		$_this->modelPaths[] = MODELS;
		if (isset($modelPaths)) {
			foreach ($modelPaths as $value) {
				$_this->modelPaths[] = $value;
			}
		}
	}
/**
 * Sets the var viewPaths
 *
 * @param array $viewPaths Path to view files
 * @access private
 */
	function __buildViewPaths($viewPaths) {
		$_this =& Configure::getInstance();
		$_this->viewPaths[] = VIEWS;
		if (isset($viewPaths)) {
			foreach ($viewPaths as $value) {
				$_this->viewPaths[] = $value;
			}
		}
	}
/**
 * Sets the var controllerPaths
 *
 * @param array $controllerPaths Path to controller files
 * @access private
 */
	function __buildControllerPaths($controllerPaths) {
		$_this =& Configure::getInstance();
		$_this->controllerPaths[] = CONTROLLERS;
		if (isset($controllerPaths)) {
			foreach ($controllerPaths as $value) {
				$_this->controllerPaths[] = $value;
			}
		}
	}
/**
 * Sets the var helperPaths
 *
 * @param array $helperPaths Path to helper files
 * @access private
 */
	function __buildHelperPaths($helperPaths) {
		$_this =& Configure::getInstance();
		$_this->helperPaths[] = HELPERS;
		if (isset($helperPaths)) {
			foreach ($helperPaths as $value) {
				$_this->helperPaths[] = $value;
			}
		}
	}
/**
 * Sets the var componentPaths
 *
 * @param array $componentPaths Path to component files
 * @access private
 */
	function __buildComponentPaths($componentPaths) {
		$_this =& Configure::getInstance();
		$_this->componentPaths[] = COMPONENTS;
		if (isset($componentPaths)) {
			foreach ($componentPaths as $value) {
				$_this->componentPaths[] = $value;
			}
		}
	}
/**
 * Sets the var behaviorPaths
 *
 * @param array $behaviorPaths Path to behavior files
 * @access private
 */
	function __buildBehaviorPaths($behaviorPaths) {
		$_this =& Configure::getInstance();
		$_this->behaviorPaths[] = BEHAVIORS;
		if (isset($behaviorPaths)) {
			foreach ($behaviorPaths as $value) {
				$_this->behaviorPaths[] = $value;
			}
		}
	}
/**
 * Sets the var pluginPaths
 *
 * @param array $pluginPaths Path to plugins
 * @access private
 */
	function __buildPluginPaths($pluginPaths) {
		$_this =& Configure::getInstance();
		$_this->pluginPaths[] = APP . 'plugins' . DS;
		if (isset($pluginPaths)) {
			foreach ($pluginPaths as $value) {
				$_this->pluginPaths[] = $value;
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
		$_this =& Configure::getInstance();
		$baseUrl = false;
		if (defined('BASE_URL')) {
			$baseUrl = BASE_URL;
		}
		$_this->write('App', array('base'=> false, 'baseUrl'=> $baseUrl, 'dir'=> APP_DIR, 'webroot'=> WEBROOT_DIR));
		$modelPaths = null;
		$viewPaths = null;
		$controllerPaths = null;
		$helperPaths = null;
		$componentPaths = null;
		$behaviorPaths = null;
		$pluginPaths = null;
		if ($boot) {
			if (!include(APP_PATH . 'config' . DS . 'bootstrap.php')) {
				trigger_error(sprintf(__("Can't find application bootstrap file. Please create %sbootstrap.php, and make sure it is readable by PHP.", true), CONFIGS), E_USER_ERROR);
			}
		}
		$_this->__buildModelPaths($modelPaths);
		$_this->__buildViewPaths($viewPaths);
		$_this->__buildControllerPaths($controllerPaths);
		$_this->__buildHelperPaths($helperPaths);
		$_this->__buildComponentPaths($componentPaths);
		$_this->__buildBehaviorPaths($behaviorPaths);
		$_this->__buildPluginPaths($pluginPaths);
	}
}
?>