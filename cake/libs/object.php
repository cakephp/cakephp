<?php
/**
 * Object class, allowing __construct and __destruct in PHP4.
 *
 * Also includes methods for logging and the special method RequestAction,
 * to call other Controllers' Actions from anywhere.
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
 * @since         CakePHP(tm) v 0.2.9
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

/**
 * Object class provides a few generic methods used in several subclasses.
 *
 * Also includes methods for logging and the special method RequestAction,
 * to call other Controllers' Actions from anywhere.
 *
 * @package    cake.libs
 */
class Object {

/**
 * constructor, no-op
 *
 * @return void
 */
	public function __construct() {

	}

/**
 * Object-to-string conversion.
 * Each class can override this method as necessary.
 *
 * @return string The name of this class
 */
	public function toString() {
		$class = get_class($this);
		return $class;
	}

/**
 * Calls a controller's method from any location. Can be used to connect controllers together
 * or tie plugins into a main application. requestAction can be used to return rendered views
 * or fetch the return value from controller actions.
 *
 * @param mixed $url String or array-based url.
 * @param array $extra if array includes the key "return" it sets the AutoRender to true.
 * @return mixed Boolean true or false on success/failure, or contents
 *    of rendered action if 'return' is set in $extra.
 */
	public function requestAction($url, $extra = array()) {
		if (empty($url)) {
			return false;
		}
		if (!class_exists('dispatcher')) {
			require LIBS . 'dispatcher.php';
		}
		if (in_array('return', $extra, true)) {
			$extra = array_merge($extra, array('return' => 0, 'autoRender' => 1));
		}
		if (is_array($url) && !isset($extra['url'])) {
			$extra['url'] = array();
		}
		$extra = array_merge(array('autoRender' => 0, 'return' => 1, 'bare' => 1, 'requested' => 1), $extra);
		
		if (is_string($url)) {
			$request = new CakeRequest($url);
		} elseif (is_array($url)) {
			$params = $url + array('pass' => array(), 'named' => array(), 'base' => false);
			$params = array_merge($params, $extra);
			$request = new CakeRequest(Router::reverse($params), false);
			if (isset($params['data'])) {
				$request->data = $params['data'];
			}
		}

		$dispatcher = new Dispatcher();
		return $dispatcher->dispatch($request, $extra);
	}

/**
 * Calls a method on this object with the given parameters. Provides an OO wrapper
 * for `call_user_func_array`
 *
 * @param string $method  Name of the method to call
 * @param array $params  Parameter list to use when calling $method
 * @return mixed  Returns the result of the method call
 */
	public function dispatchMethod($method, $params = array()) {
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
 * Stop execution of the current script.  Wraps exit() making 
 * testing easier.
 *
 * @param $status see http://php.net/exit for values
 * @return void
 */
	protected function _stop($status = 0) {
		exit($status);
	}

/**
 * Convience method to write a message to CakeLog.  See CakeLog::write()
 * for more information on writing to logs.
 *
 * @param string $msg Log message
 * @param integer $type Error type constant. Defined in app/config/core.php.
 * @return boolean Success of log write
 */
	public function log($msg, $type = LOG_ERROR) {
		if (!class_exists('CakeLog')) {
			require LIBS . 'cake_log.php';
		}
		if (!is_string($msg)) {
			$msg = print_r($msg, true);
		}
		return CakeLog::write($type, $msg);
	}

/**
 * Allows setting of multiple properties of the object in a single line of code.  Will only set 
 * properties that are part of a class declaration.
 *
 * @param array $properties An associative array containing properties and corresponding values.
 * @return void
 */
	protected function _set($properties = array()) {
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
 * Merges this objects $property with the property in $class' definition.
 * This classes value for the property will be merged on top of $class'
 *
 * This provides some of the DRY magic CakePHP provides.  If you want to shut it off, redefine
 * this method as an empty function.
 *
 * @param array $properties The name of the properties to merge.
 * @param sting $class The class to merge the property with.
 * @param boolean $normalize Set to true to run the properties through Set::normalize() before merging.
 * @return void
 */
	protected function _mergeVars($properties, $class, $normalize = true) {
		$classProperties = get_class_vars($class);
		foreach ($properties as $var) {
			if (
				isset($classProperties[$var]) &&
				!empty($classProperties[$var]) && 
				is_array($this->{$var}) &&
				$this->{$var} != $classProperties[$var]
			) {
				if ($normalize) {
					$classProperties[$var] = Set::normalize($classProperties[$var]);
					$this->{$var} = Set::normalize($this->{$var});
				}
				$this->{$var} = Set::merge($classProperties[$var], $this->{$var});
			}
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
 */
	protected function _savePersistent($name, &$object) {
		$file = 'persistent' . DS . strtolower($name) . '.php';
		$objectArray = array(&$object);
		$data = str_replace('\\', '\\\\', serialize($objectArray));
		$data = '<?php $' . $name . ' = \'' . str_replace('\'', '\\\'', $data) . '\' ?>';
		$duration = '+999 days';
		if (Configure::read('debug') >= 1) {
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
						App::import('Model', Inflector::camelize($key));
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
