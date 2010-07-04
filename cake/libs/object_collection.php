<?php
/**
 * Deals with Collections of objects.  Keeping registries of those objects,
 * loading and constructing new objects and triggering callbacks.
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
 * @package       cake
 * @subpackage    cake.cake.libs.view
 * @since         CakePHP(tm) v 0.10.0.1076
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
abstract class ObjectCollection {
/**
 * Lists the currently-attached objects
 *
 * @var array
 * @access private
 */
	protected $_attached = array();

/**
 * Lists the currently-attached objects which are disabled
 *
 * @var array
 * @access private
 */
	protected $_disabled = array();

/**
 * A hash of loaded helpers, indexed by the classname
 *
 * @var array
 */
	protected $_loaded = array();

/**
 * Loads a new object onto the collection. Can throw a variety of exceptions
 *
 * @param string $name Name of object to load.
 * @param array $options Array of configuration options for the object to be constructed.
 * @param boolean $enable Whether or not this helper should be enabled by default
 * @return object the constructed object
 */
	abstract public function load($name, $options = array(), $enable = true);

/**
 * Unloads/deletes an object from the collection.
 *
 * @param string $name Name of the object to delete.
 * @return void
 */
	abstract public function unload($name);

/**
 * Trigger a callback method on every object in the collection.
 * Used to trigger methods on objects in the collection.  Will fire the methods in the 
 * order they were attached.
 *
 * ### Options
 *
 * - `breakOn` Set to the value or values you want the callback propagation to stop on.
 *    Defaults to `false`
 * - `break` Set to true to enabled breaking.
 * 
 * @param string $callback Method to fire on all the objects. Its assumed all the objects implement
 *   the method you are calling.
 * @param array $params Array of parameters for the triggered callback.
 * @param array $options Array of options.
 * @return Returns true.
 */
	public function trigger($callback, $params = array(), $options = array()) {
		if (empty($this->_attached)) {
			return true;
		}
		$options = array_merge(
			array('break' => false, 'breakOn' => false),
			$options
		);
		$count = count($this->_attached);
		for ($i = 0; $i < $count; $i++) {
			$name = $this->_attached[$i];
			if (in_array($name, $this->_disabled)) {
				continue;
			}
			$result = call_user_func_array(array($this->{$name}, $callback), $params);

			if (
				$options['break'] && ($result === $options['breakOn'] || 
				(is_array($options['breakOn']) && in_array($result, $options['breakOn'], true)))
			) {
				return $result;
			}
		}
		return true;
	}

/**
 * Provide public read access to the loaded objects
 *
 * @param string $name Name of property to read
 * @return mixed
 */
	public function __get($name) {
		if (isset($this->_loaded[$name])) {
			return $this->_loaded[$name];
		}
		return null;
	}

/**
 * Provide isset access to _loaded
 *
 * @param sting $name Name of object being checked.
 * @return boolean
 */
	public function __isset($name) {
		return isset($this->_loaded[$name]);
	}

/**
 * Enables callbacks on a behavior or array of behaviors
 *
 * @param mixed $name CamelCased name of the behavior(s) to enable (string or array)
 * @return void
 */
	public function enable($name) {
		$this->_disabled = array_diff($this->_disabled, (array)$name);
	}

/**
 * Disables callbacks on a object or array of objects.  Public object methods are still
 * callable as normal.
 *
 * @param mixed $name CamelCased name of the objects(s) to disable (string or array)
 * @return void
 */
	public function disable($name) {
		foreach ((array)$name as $object) {
			if (in_array($object, $this->_attached) && !in_array($object, $this->_disabled)) {
				$this->_disabled[] = $object;
			}
		}
	}

/**
 * Gets the list of currently-enabled objects, or, the current status of a single objects
 *
 * @param string $name Optional.  The name of the object to check the status of.  If omitted,
 *   returns an array of currently-enabled object
 * @return mixed If $name is specified, returns the boolean status of the corresponding object.
 *   Otherwise, returns an array of all enabled objects.
 */
	public function enabled($name = null) {
		if (!empty($name)) {
			return (in_array($name, $this->_attached) && !in_array($name, $this->_disabled));
		}
		return array_diff($this->_attached, $this->_disabled);
	}

/**
 * Gets the list of attached behaviors, or, whether the given behavior is attached
 *
 * @param string $name Optional.  The name of the behavior to check the status of.  If omitted,
 *   returns an array of currently-attached behaviors
 * @return mixed If $name is specified, returns the boolean status of the corresponding behavior.
 *    Otherwise, returns an array of all attached behaviors.
 */
	public function attached($name = null) {
		if (!empty($name)) {
			return (in_array($name, $this->_attached));
		}
		return $this->_attached;
	}

/**
 * Normalizes an object array, creates an array that makes lazy loading
 * easier
 *
 * @param array $objects Array of child objects to normalize.
 * @return array Array of normalized objects.
 */
	public static function normalizeObjectArray($objects) {
		$normal = array();
		foreach ($objects as $i => $objectName) {
			$options = array();
			if (!is_int($i)) {
				list($options, $objectName) = array($objectName, $i);
			}
			list($plugin, $name) = pluginSplit($objectName);
			$normal[$name] = array('class' => $objectName, 'settings' => $options);
		}
		return $normal;
	}
}