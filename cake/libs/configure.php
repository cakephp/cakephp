<?php
/* SVN FILE: $Id$ */
/**
 * Short description for file.
 *
 * Long description for file
 *
 * PHP versions 4 and 5
 *
 * CakePHP :  Rapid Development Framework <http://www.cakephp.org/>
 * Copyright (c)	2006, Cake Software Foundation, Inc.
 *								1785 E. Sahara Avenue, Suite 490-204
 *								Las Vegas, Nevada 89104
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @filesource
 * @copyright		Copyright (c) 2006, Cake Software Foundation, Inc.
 * @link				http://www.cakefoundation.org/projects/info/cakephp CakePHP Project
 * @package			cake
 * @subpackage		cake.cake.libs
 * @since			CakePHP v 1.0.0.2363
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
 * Enter description here...
 *
 * @var array
 * @access public
 */
	var $modelPaths = array();
/**
 * Enter description here...
 *
 * @var array
 * @access public
 */
	var $helperPaths = array();
/**
 * Enter description here...
 *
 * @var array
 * @access public
 */
	var $componentPaths = array();
/**
 * Enter description here...
 *
 * @var array
 * @access public
 */
	var $behaviorPaths = array();
/**
 * Return a singleton instance of Configure.
 *
 * @return Configure instance
 * @access public
 */
	function &getInstance() {
		static $instance = array();
		if (!$instance) {
			$instance[0] =& new Configure;
			$instance[0]->__loadBootstrap();
		}
		return $instance[0];
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
 *
 * @param array $config
 * @param mixed $value
 * @return void
 * @access public
 */
	function write($config, $value = null){
		$_this =& Configure::getInstance();

		if(!is_array($config) && $value !== null) {
			$name = $_this->__configVarNames($config);

			if(count($name) > 1){
				$_this->{$name[0]}[$name[1]] = $value;
			} else {
				$_this->{$name[0]} = $value;
			}
		} else {

			foreach($config as $names => $value){
				$name = $_this->__configVarNames($names);
				if(count($name) > 1){
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
			} else {
				error_reporting(0);
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
 * @param string $var
 * @return string value of Configure::$var
 * @access public
 */
	function read($var = 'debug'){
		$_this =& Configure::getInstance();
		if($var === 'debug') {
			if(!isset($_this->debug)){
				$_this->debug = DEBUG;
			}
			return $_this->debug;
		}

		$name = $_this->__configVarNames($var);
		if(count($name) > 1){
			if(isset($_this->{$name[0]}[$name[1]])) {
				return $_this->{$name[0]}[$name[1]];
			}
			return null;
		} else {
			if(isset($_this->{$name[0]})) {
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
 * @return void
 * @access public
 */
	function delete($var = null){
		$_this =& Configure::getInstance();

		$name = $_this->__configVarNames($var);
		if(count($name) > 1){
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
 * @return Configure::write
 * @access public
 */
	function load($fileName) {
		$_this =& Configure::getInstance();

		if (file_exists(CONFIGS . $fileName . '.php')) {
			include(CONFIGS . $fileName . '.php');
		} elseif (file_exists(CACHE . 'persistent' . DS . $fileName . '.php')) {
			include(CACHE . 'persistent' . DS . $fileName . '.php');
		} else {
			trigger_error(sprintf(__("Configure::load() - %s.php not found", true), $fileName), E_USER_WARNING);
			return false;
		}

		if(!isset($config)){
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
		if(!isset($_this->Cake['version'])){
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
			$content .= "\$config['$type']['$key'] = array(";
			if(is_array($value)){
				foreach($value as $key1 => $value2){
					$content .= "'$key1' => '$value2', ";
				}
			} else {
				$content .= "'$key' => '$value'";
			}
			$content .= ");\n";
		}
		if(is_null($type)) {
			$write = false;
		}
		$_this->__writeConfig($content, $name, $write);
	}
/**
 * Creates a cached version of a configuration file.
 * Appends values passed from Configure::store() to the cached file
 *
 * @param string $content
 * @param string $name
 * @param boolean $write
 * @access private
 */
	function __writeConfig($content, $name, $write = true){
		$file = CACHE . 'persistent' . DS . $name . '.php';
		if(!file_exists($file)){
			cache('persistent' . DS . $name . '.php', "<?php\n");
		}
		if($write === true){
			if(!class_exists('File')){
				uses('File');
			}
			$fileClass = new File($file);
			$fileClass->append($content);
		}
	}
/**
 * Checks $name for dot notation to create dynamic Configure::$var as an array when needed.
 *
 * @param mixed $name
 * @return array
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
 * @param array $modelPaths
 * @access private
 */
	function __buildModelPaths($modelPaths) {
		$_this =& Configure::getInstance();
		$_this->modelPaths[] = MODELS;
		if (isset($modelPaths)) {
			foreach($modelPaths as $value) {
				$_this->modelPaths[] = $value;
			}
		}
	}
/**
 * Sets the var viewPaths
 *
 * @param array $viewPaths
 * @access private
 */
	function __buildViewPaths($viewPaths) {
		$_this =& Configure::getInstance();
		$_this->viewPaths[] = VIEWS;
		$_this->viewPaths[] = VIEWS . 'errors' . DS;
		if (isset($viewPaths)) {
			foreach($viewPaths as $value) {
				$_this->viewPaths[] = $value;
			}
		}
	}
/**
 * Sets the var controllerPaths
 *
 * @param array $controllerPaths
 * @access private
 */
	function __buildControllerPaths($controllerPaths) {
		$_this =& Configure::getInstance();
		$_this->controllerPaths[] = CONTROLLERS;
		if (isset($controllerPaths)) {
			foreach($controllerPaths as $value) {
				$_this->controllerPaths[] = $value;
			}
		}
	}
/**
 * Sets the var helperPaths
 *
 * @param array $helperPaths
 * @access private
 */
	function __buildHelperPaths($helperPaths) {
		$_this =& Configure::getInstance();
		$_this->helperPaths[] = HELPERS;
		if (isset($helperPaths)) {
			foreach($helperPaths as $value) {
				$_this->helperPaths[] = $value;
			}
		}
	}
/**
 * Sets the var componentPaths
 *
 * @param array $componentPaths
 * @access private
 */
	function __buildComponentPaths($componentPaths) {
		$_this =& Configure::getInstance();
		$_this->componentPaths[] = COMPONENTS;
		if (isset($componentPaths)) {
			foreach($componentPaths as $value) {
				$_this->componentPaths[] = $value;
			}
		}
	}
/**
 * Sets the var behaviorPaths
 *
 * @param array $behaviorPaths
 * @access private
 */
	function __buildBehaviorPaths($behaviorPaths) {
		$_this =& Configure::getInstance();
		$_this->behaviorPaths[] = BEHAVIORS;
		if (isset($behaviorPaths)) {
			foreach($behaviorPaths as $value) {
				$_this->behaviorPaths[] = $value;
			}
		}
	}
/**
 * Loads the app/config/bootstrap.php
 * If the alternative paths are set in this file
 * they will be added to the paths vars
 *
 * @access private
 */
	function __loadBootstrap() {
		$_this =& Configure::getInstance();
		$modelPaths = null;
		$viewPaths = null;
		$controllerPaths = null;
		$helperPaths = null;
		$componentPaths = null;
		$behaviorPaths = null;
		require APP_PATH . 'config' . DS . 'bootstrap.php';
		$_this->__buildModelPaths($modelPaths);
		$_this->__buildViewPaths($viewPaths);
		$_this->__buildControllerPaths($controllerPaths);
		$_this->__buildHelperPaths($helperPaths);
		$_this->__buildComponentPaths($componentPaths);
		$_this->__buildBehaviorPaths($behaviorPaths);
	}
}

?>