<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2011, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2011, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

/**
 * Deals with Collections of objects.  Keeping registries of those objects,
 * loading and constructing new objects and triggering callbacks. Each subclass needs
 * to implement its own load() functionality.
 *
 * All core subclasses of ObjectCollection by convention loaded objects are stored
 * in `$this->_loaded`. Enabled objects are stored in `$this->_enabled`.  In addition
 * the all support an `enabled` option that controls the enabled/disabled state of the object
 * when loaded.
 *
 * @package       Cake.Utility
 * @since CakePHP(tm) v 2.0
 */
abstract class ObjectCollection {

/**
 * List of the currently-enabled objects
 *
 * @var array
 */
	protected $_enabled = array();

/**
 * A hash of loaded objects, indexed by name
 *
 * @var array
 */
	protected $_loaded = array();

/**
 * Loads a new object onto the collection. Can throw a variety of exceptions
 *
 * Implementations of this class support a `$options['enabled']` flag which enables/disables
 * a loaded object.
 *
 * @param string $name Name of object to load.
 * @param array $options Array of configuration options for the object to be constructed.
 * @return object the constructed object
 */
	abstract public function load($name, $options = array());

/**
 * Trigger a callback method on every object in the collection.
 * Used to trigger methods on objects in the collection.  Will fire the methods in the
 * order they were attached.
 *
 * ### Options
 *
 * - `breakOn` Set to the value or values you want the callback propagation to stop on.
 *    Can either be a scalar value, or an array of values to break on. Defaults to `false`.
 *
 * - `break` Set to true to enabled breaking. When a trigger is broken, the last returned value
 *    will be returned.  If used in combination with `collectReturn` the collected results will be returned.
 *    Defaults to `false`.
 *
 * - `collectReturn` Set to true to collect the return of each object into an array.
 *    This array of return values will be returned from the trigger() call. Defaults to `false`.
 *
 * - `modParams` Allows each object the callback gets called on to modify the parameters to the next object.
 *    Setting modParams to an integer value will allow you to modify the parameter with that index.
 *    Any non-null value will modify the parameter index indicated.
 *    Defaults to false.
 *
 *
 * @param string $callback Method to fire on all the objects. Its assumed all the objects implement
 *   the method you are calling.
 * @param array $params Array of parameters for the triggered callback.
 * @param array $options Array of options.
 * @return mixed Either the last result or all results if collectReturn is on.
 * @throws CakeException when modParams is used with an index that does not exist.
 */
	public function trigger($callback, $params = array(), $options = array()) {
		if (empty($this->_enabled)) {
			return true;
		}
		$options = array_merge(
			array(
				'break' => false,
				'breakOn' => false,
				'collectReturn' => false,
				'modParams' => false
			),
			$options
		);
		$collected = array();
		$list = $this->_enabled;
		if ($options['modParams'] !== false && !isset($params[$options['modParams']])) {
			throw new CakeException(__d('cake_dev', 'Cannot use modParams with indexes that do not exist.'));
		}
		foreach ($list as $name) {
			$result = call_user_func_array(array($this->_loaded[$name], $callback), $params);
			if ($options['collectReturn'] === true) {
				$collected[] = $result;
			}
			if (
				$options['break'] && ($result === $options['breakOn'] ||
				(is_array($options['breakOn']) && in_array($result, $options['breakOn'], true)))
			) {
				return $result;
			} elseif ($options['modParams'] !== false && is_array($result)) {
				$params[$options['modParams']] = $result;
			}
		}
		if ($options['modParams'] !== false) {
			return $params[$options['modParams']];
		}
		return $options['collectReturn'] ? $collected : $result;
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
 * @param string $name Name of object being checked.
 * @return boolean
 */
	public function __isset($name) {
		return isset($this->_loaded[$name]);
	}

/**
 * Enables callbacks on an object or array of objects
 *
 * @param mixed $name CamelCased name of the object(s) to enable (string or array)
 * @return void
 */
	public function enable($name) {
		foreach ((array)$name as $object) {
			if (isset($this->_loaded[$object]) && array_search($object, $this->_enabled) === false) {
				$this->_enabled[] = $object;
			}
		}
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
			$index = array_search($object, $this->_enabled);
			unset($this->_enabled[$index]);
		}
		$this->_enabled = array_values($this->_enabled);
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
			return in_array($name, $this->_enabled);
		}
		return $this->_enabled;
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
			return isset($this->_loaded[$name]);
		}
		return array_keys($this->_loaded);
	}

/**
 * Name of the object to remove from the collection
 *
 * @param string $name Name of the object to delete.
 * @return void
 */
	public function unload($name) {
		list($plugin, $name) = pluginSplit($name);
		unset($this->_loaded[$name]);
		$this->_enabled = array_values(array_diff($this->_enabled, (array)$name));
	}

/**
 * Adds or overwrites an instantiated object to the collection
 *
 * @param string $name Name of the object
 * @param Object $object The object to use
 * @return array Loaded objects
 */
	public function set($name = null, $object = null) {
		if (!empty($name) && !empty($object)) {
			list($plugin, $name) = pluginSplit($name);
			$this->_loaded[$name] = $object;
		}
		return $this->_loaded;
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
				$options = (array)$objectName;
				$objectName = $i;
			}
			list($plugin, $name) = pluginSplit($objectName);
			$normal[$name] = array('class' => $objectName, 'settings' => $options);
		}
		return $normal;
	}
}