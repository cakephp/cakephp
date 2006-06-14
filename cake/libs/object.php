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
 * @since			CakePHP v 0.2.9
 * @version			$Revision$
 * @modifiedby		$LastChangedBy$
 * @lastmodified	$Date$
 * @license			http://www.opensource.org/licenses/mit-license.php The MIT License
 */
/**
 * Object class, allowing __construct and __destruct in PHP4.
 *
 * Also includes methods for logging and the special method RequestAction,
 * to call other Controllers' Actions from anywhere.
 *
 * @package		cake
 * @subpackage	cake.cake.libs
 */
class Object{
/**
 * Log object
 *
 * @var object
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
 */
	function toString() {
		$class = get_class($this);
		return $class;
	}
/**
 * Calls a controller's method from any location.
 *
 * @param string $url  URL in the form of Cake URL ("/controller/method/parameter")
 * @param array $extra If array includes the key "return" it sets the AutoRender to true.
 * @return boolean  Success
 */
	function requestAction($url, $extra = array()) {
		if (!empty($url)) {
			$dispatcher =& new Dispatcher();
			if(isset($this->plugin)){
				$extra['plugin'] = $this->plugin;
			}
			if (in_array('return', $extra)) {
				$extra['return'] = 0;
				$extra['bare'] = 1;
				ob_start();
				$out = $dispatcher->dispatch($url, $extra);
				$out = ob_get_clean();
				return $out;
			} else {
				$extra['return'] = 1;
				$extra['bare'] = 1;
				return $dispatcher->dispatch($url, $extra);
			}
		} else {
			return false;
		}
	}
/**
 * API for logging events.
 *
 * @param string $msg Log message
 * @param int $type Error type constant. Defined in app/config/core.php.
 */
	function log($msg, $type = LOG_ERROR) {
		if (!class_exists('CakeLog')) {
			uses('cake_log');
		}

		if (is_null($this->_log)) {
			$this->_log = new CakeLog();
		}

		if (!is_string($msg)) {
			ob_start();
			print_r ($msg);
			$msg=ob_get_contents();
			ob_end_clean();
		}

		switch($type) {
			case LOG_DEBUG:
				return $this->_log->write('debug', $msg);
			break;
			default:
				return $this->_log->write('error', $msg);
			break;
		}
	}

/**
 * Used to report user friendly errors.
 * If there is a file app/error.php this file will be loaded
 * error.php is the AppError class it should extend ErrorHandler class.
 *
 * @param string $method Method to be called in the error class (AppError or ErrorHandler classes)
 * @param array $messages Message that is to be displayed by the error class
 * @return error message
 */
	function cakeError($method, $messages) {
		if (!class_exists('ErrorHandler')) {
			uses('error');
			if (file_exists(APP . 'error.php')) {
				include_once (APP . 'error.php');
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
 * @return boolean
 * @param string $object the object to persist
 * @access public
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
 * When a Model::persist; is set to true, a file with the model name it auto created.
 * If used in other locations of the model you should choose a unique name for the persistent file
 * There are many uses for this method, see manual for examples
 *
 * @param string $name name used for object to cach
 * @param string $object the object to persist
 * @return true on save, throws error if file can not be created
 * @access public
 * @todo add examples to manual
 */
	function _savePersistent($name, &$object) {
		$file = 'persistent' . DS . strtolower($name) . '.php';
		$objectArray = array(&$object);
		$data = '<?php $' . $name . ' = \'' . str_replace('\'', '\\\'', serialize($objectArray)) . '\' ?>';
		cache($file, $data, '+1 day');
	}
/**
 * Open the persistent class file for reading
 *
 * @param string $name name of the class
 * @access private
 */
	function __openPersistent($name, $type = null) {
		$file = CACHE . 'persistent' . DS . strtolower($name) . '.php';
		include($file);

		switch($type) {
			case 'registry':
				$vars = unserialize(${$name});
				foreach($vars['0'] as $key => $value) {
					ClassRegistry::addObject($key, $value);
					unset ($value);
				}
			break;
			default:
				$vars = unserialize(${$name});
				$this->{$name} = $vars['0'];
			break;
		}
	}
}
?>