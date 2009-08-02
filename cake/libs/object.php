<?php
/* SVN FILE: $Id$ */
/**
 * Object class, allowing __construct and __destruct in PHP4.
 *
 * Also includes methods for logging and the special method RequestAction,
 * to call other Controllers' Actions from anywhere.
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) :  Rapid Development Framework (http://www.cakephp.org)
 * Copyright 2005-2008, Cake Software Foundation, Inc. (http://www.cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @filesource
 * @copyright     Copyright 2005-2008, Cake Software Foundation, Inc. (http://www.cakefoundation.org)
 * @link          http://www.cakefoundation.org/projects/info/cakephp CakePHP(tm) Project
 * @package       cake
 * @subpackage    cake.cake.libs
 * @since         CakePHP(tm) v 0.2.9
 * @version       $Revision$
 * @modifiedby    $LastChangedBy$
 * @lastmodified  $Date$
 * @license       http://www.opensource.org/licenses/mit-license.php The MIT License
 */
/**
 * Object class, allowing __construct and __destruct in PHP4.
 *
 * Also includes methods for logging and the special method RequestAction,
 * to call other Controllers' Actions from anywhere.
 *
 * @package       cake
 * @subpackage    cake.cake.libs
 */
class Object {
/**
 * Log object
 *
 * @var CakeLog
 * @access protected
 */
	var $_log = null;
/**
 * A hack to support __construct() on PHP 4
 * Hint: descendant classes have no PHP4 class_name() constructors,
 * so this constructor gets called first and calls the top-layer __construct()
 * which (if present) should call parent::__construct()
 *
 * @return Object
 */
	function Object() {
		$args = func_get_args();
		if (method_exists($this, '__destruct')) {
			register_shutdown_function (array(&$this, '__destruct'));
		}
		call_user_func_array(array(&$this, '__construct'), $args);
	}
/**
 * Class constructor, overridden in descendant classes.
 */
	function __construct() {
	}

/**
 * Object-to-string conversion.
 * Each class can override this method as necessary.
 *
 * @return string The name of this class
 * @access public
 */
	function toString() {
		$class = get_class($this);
		return $class;
	}
/**
 * Calls a controller's method from any location.
 *
 * @param mixed $url String or array-based url.
 * @param array $extra if array includes the key "return" it sets the AutoRender to true.
 * @return mixed Boolean true or false on success/failure, or contents
 *               of rendered action if 'return' is set in $extra.
 * @access public
 */
	function requestAction($url, $extra = array()) {
		if (empty($url)) {
			return false;
		}
		if (!class_exists('dispatcher')) {
			require CAKE . 'dispatcher.php';
		}
		if (in_array('return', $extra, true)) {
			$extra = array_merge($extra, array('return' => 0, 'autoRender' => 1));
		}
		if (is_array($url) && !isset($extra['url'])) {
			$extra['url'] = array();
		}
		$params = array_merge(array('autoRender' => 0, 'return' => 1, 'bare' => 1, 'requested' => 1), $extra);
		$dispatcher = new Dispatcher;
		return $dispatcher->dispatch($url, $params);
	}
/**
 * Calls a method on this object with the given parameters. Provides an OO wrapper
 * for call_user_func_array, and improves performance by using straight method calls
 * in most cases.
 *
 * @param string $method  Name of the method to call
 * @param array $params  Parameter list to use when calling $method
 * @return mixed  Returns the result of the method call
 * @access public
 */
	function dispatchMethod($method, $params = array()) {
		switch (count($params)) {
			case 0:
				return $this->{$method}();
			case 1:
				return $this->{$method}($params[0]);
			case 2:
				return $this->{$method}($params[0], $params[1]);
			case 3:
				return $this->{$method}($params[0], $params[1], $params[2]);
			case 4:
				return $this->{$method}($params[0], $params[1], $params[2], $params[3]);
			case 5:
				return $this->{$method}($params[0], $params[1], $params[2], $params[3], $params[4]);
			default:
				return call_user_func_array(array(&$this, $method), $params);
			break;
		}
	}
/**
 * Stop execution of the current script
 *
 * @param $status see http://php.net/exit for values
 * @return void
 * @access public
 */
	function _stop($status = 0) {
		exit($status);
	}
/**
 * API for logging events.
 *
 * @param string $msg Log message
 * @param integer $type Error type constant. Defined in app/config/core.php.
 * @return boolean Success of log write
 * @access public
 */
	function log($msg, $type = LOG_ERROR) {
		if (!class_exists('CakeLog')) {
			uses('cake_log');
		}
		if (is_null($this->_log)) {
			$this->_log = new CakeLog();
		}
		if (!is_string($msg)) {
			$msg = print_r($msg, true);
		}
		return $this->_log->write($type, $msg);
	}
/**
 * Allows setting of multiple properties of the object in a single line of code.
 *
 * @param array $properties An associative array containing properties and corresponding values.
 * @return void
 * @access protected
 */
	function _set($properties = array()) {
		if (is_array($properties) && !empty($properties)) {
			$vars = get_object_vars($this);
			foreach ($properties as $key => $val) {
				if (array_key_exists($key, $vars)) {
					$this->{$key} = $val;
				}
			}
		}
	}
/**
 * Used to report user friendly errors.
 * If there is a file app/error.php or app/app_error.php this file will be loaded
 * error.php is the AppError class it should extend ErrorHandler class.
 *
 * @param string $method Method to be called in the error class (AppError or ErrorHandler classes)
 * @param array $messages Message that is to be displayed by the error class
 * @return error message
 * @access public
 */
	function cakeError($method, $messages = array()) {
		if (!class_exists('ErrorHandler')) {
			App::import('Core', 'Error');

			if (file_exists(APP . 'error.php')) {
				include_once (APP . 'error.php');
			} elseif (file_exists(APP . 'app_error.php')) {
				include_once (APP . 'app_error.php');
			}
		}

		if (class_exists('AppError')) {
			$error = new AppError($method, $messages);
		} else {
			$error = new ErrorHandler($method, $messages);
		}
		return $error;
	}
/**
 * Checks for a persistent class file, if found file is opened and true returned
 * If file is not found a file is created and false returned
 * If used in other locations of the model you should choose a unique name for the persistent file
 * There are many uses for this method, see manual for examples
 *
 * @param string $name name of the class to persist
 * @param string $object the object to persist
 * @return boolean Success
 * @access protected
 * @todo add examples to manual
 */
	function _persist($name, $return = null, &$object, $type = null) {
		$file = CACHE . 'persistent' . DS . strtolower($name) . '.php';
		if ($return === null) {
			if (!file_exists($file)) {
				return false;
			} else {
				return true;
			}
		}

		if (!file_exists($file)) {
			$this->_savePersistent($name, $object);
			return false;
		} else {
			$this->__openPersistent($name, $type);
			return true;
		}
	}
/**
 * You should choose a unique name for the persistent file
 *
 * There are many uses for this method, see manual for examples
 *
 * @param string $name name used for object to cache
 * @param object $object the object to persist
 * @return boolean true on save, throws error if file can not be created
 * @access protected
 */
	function _savePersistent($name, &$object) {
		$file = 'persistent' . DS . strtolower($name) . '.php';
		$objectArray = array(&$object);
		$data = str_replace('\\', '\\\\', serialize($objectArray));
		$data = '<?php $' . $name . ' = \'' . str_replace('\'', '\\\'', $data) . '\' ?>';
		$duration = '+999 days';
		if (Configure::read() >= 1) {
			$duration = '+10 seconds';
		}
		cache($file, $data, $duration);
	}
/**
 * Open the persistent class file for reading
 * Used by Object::_persist()
 *
 * @param string $name Name of persisted class
 * @param string $type Type of persistance (e.g: registry)
 * @return void
 * @access private
 */
	function __openPersistent($name, $type = null) {
		$file = CACHE . 'persistent' . DS . strtolower($name) . '.php';
		include($file);

		switch ($type) {
			case 'registry':
				$vars = unserialize(${$name});
				foreach ($vars['0'] as $key => $value) {
					if (strpos($key, '_behavior') !== false) {
						App::import('Behavior', Inflector::classify(substr($key, 0, -9)));
					} else {
						App::import('Model', Inflector::classify($key));
					}
					unset ($value);
				}
				unset($vars);
				$vars = unserialize(${$name});
				foreach ($vars['0'] as $key => $value) {
					ClassRegistry::addObject($key, $value);
					unset ($value);
				}
				unset($vars);
			break;
			default:
				$vars = unserialize(${$name});
				$this->{$name} = $vars['0'];
				unset($vars);
			break;
		}
	}
}
?>