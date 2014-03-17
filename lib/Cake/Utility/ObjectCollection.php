<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */

/**
 * Deals with Collections of objects. Keeping registries of those objects,
 * loading and constructing new objects and triggering callbacks. Each subclass needs
 * to implement its own load() functionality.
 *
 * All core subclasses of ObjectCollection by convention loaded objects are stored
 * in `$this->_loaded`. Enabled objects are stored in `$this->_enabled`. In addition,
 * they all support an `enabled` option that controls the enabled/disabled state of the object
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
 * Default object priority. A non zero integer.
 *
 * @var integer
 */
	public $defaultPriority = 10;

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
 * Used to trigger methods on objects in the collection. Will fire the methods in the
 * order they were attached.
 *
 * ### Options
 *
 * - `breakOn` Set to the value or values you want the callback propagation to stop on.
 *    Can either be a scalar value, or an array of values to break on. Defaults to `false`.
 *
 * - `break` Set to true to enabled breaking. When a trigger is broken, the last returned value
 *    will be returned. If used in combination with `collectReturn` the collected results will be returned.
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
 * @param string $callback|CakeEvent Method to fire on all the objects. Its assumed all the objects implement
 *   the method you are calling. If an instance of CakeEvent is provided, then then Event name will parsed to
 *   get the callback name. This is done by getting the last word after any dot in the event name
 *   (eg. `Model.afterSave` event will trigger the `afterSave` callback)
 * @param array $params Array of parameters for the triggered callback.
 * @param array $options Array of options.
 * @return mixed Either the last result or all results if collectReturn is on.
 * @throws CakeException when modParams is used with an index that does not exist.
 */
	public function trigger($callback, $params = array(), $options = array()) {
		if (empty($this->_enabled)) {
			return true;
		}
		if ($callback instanceof CakeEvent) {
			$event = $callback;
			if (is_array($event->data)) {
				$params =& $event->data;
			}
			if (empty($event->omitSubject)) {
				$subject = $event->subject();
			}

			foreach (array('break', 'breakOn', 'collectReturn', 'modParams') as $opt) {
				if (isset($event->{$opt})) {
					$options[$opt] = $event->{$opt};
				}
			}
			$parts = explode('.', $event->name());
			$callback = array_pop($parts);
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
		$list = array_keys($this->_enabled);
		if ($options['modParams'] !== false && !isset($params[$options['modParams']])) {
			throw new CakeException(__d('cake_dev', 'Cannot use modParams with indexes that do not exist.'));
		}
		$result = null;
		foreach ($list as $name) {
			$result = call_user_func_array(array($this->_loaded[$name], $callback), compact('subject') + $params);
			if ($options['collectReturn'] === true) {
				$collected[] = $result;
			}
			if (
				$options['break'] && ($result === $options['breakOn'] ||
				(is_array($options['breakOn']) && in_array($result, $options['breakOn'], true)))
			) {
				return $result;
			} elseif ($options['modParams'] !== false && !in_array($result, array(true, false, null), true)) {
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
 * @param string|array $name CamelCased name of the object(s) to enable (string or array)
 * @param boolean Prioritize enabled list after enabling object(s)
 * @return void
 */
	public function enable($name, $prioritize = true) {
		$enabled = false;
		foreach ((array)$name as $object) {
			if (isset($this->_loaded[$object]) && !isset($this->_enabled[$object])) {
				$priority = $this->defaultPriority;
				if (isset($this->_loaded[$object]->settings['priority'])) {
					$priority = $this->_loaded[$object]->settings['priority'];
				}
				$this->_enabled[$object] = array($priority);
				$enabled = true;
			}
		}
		if ($prioritize && $enabled) {
			$this->prioritize();
		}
	}

/**
 * Prioritize list of enabled object
 *
 * @return array Prioritized list of object
 */
	public function prioritize() {
		$i = 1;
		foreach ($this->_enabled as $name => $priority) {
			$priority[1] = $i++;
			$this->_enabled[$name] = $priority;
		}
		asort($this->_enabled);
		return $this->_enabled;
	}

/**
 * Set priority for an object or array of objects
 *
 * @param string|array $name CamelCased name of the object(s) to enable (string or array)
 * 	If string the second param $priority is used else it should be an associative array
 * 	with keys as object names and values as priorities to set.
 * @param integer|null Integer priority to set or null for default
 * @return void
 */
	public function setPriority($name, $priority = null) {
		if (is_string($name)) {
			$name = array($name => $priority);
		}
		foreach ($name as $object => $objectPriority) {
			if (isset($this->_loaded[$object])) {
				if ($objectPriority === null) {
					$objectPriority = $this->defaultPriority;
				}
				$this->_loaded[$object]->settings['priority'] = $objectPriority;
				if (isset($this->_enabled[$object])) {
					$this->_enabled[$object] = array($objectPriority);
				}
			}
		}
		$this->prioritize();
	}

/**
 * Disables callbacks on a object or array of objects. Public object methods are still
 * callable as normal.
 *
 * @param string|array $name CamelCased name of the objects(s) to disable (string or array)
 * @return void
 */
	public function disable($name) {
		foreach ((array)$name as $object) {
			unset($this->_enabled[$object]);
		}
	}

/**
 * Gets the list of currently-enabled objects, or, the current status of a single objects
 *
 * @param string $name Optional. The name of the object to check the status of. If omitted,
 *   returns an array of currently-enabled object
 * @return mixed If $name is specified, returns the boolean status of the corresponding object.
 *   Otherwise, returns an array of all enabled objects.
 */
	public function enabled($name = null) {
		if (!empty($name)) {
			return isset($this->_enabled[$name]);
		}
		return array_keys($this->_enabled);
	}

/**
 * Gets the list of attached objects, or, whether the given object is attached
 *
 * @param string $name Optional. The name of the object to check the status of. If omitted,
 *   returns an array of currently-attached objects
 * @return mixed If $name is specified, returns the boolean status of the corresponding object.
 *    Otherwise, returns an array of all attached objects.
 * @deprecated Will be removed in 3.0. Use loaded instead.
 */
	public function attached($name = null) {
		return $this->loaded($name);
	}

/**
 * Gets the list of loaded objects, or, whether the given object is loaded
 *
 * @param string $name Optional. The name of the object to check the status of. If omitted,
 *   returns an array of currently-loaded objects
 * @return mixed If $name is specified, returns the boolean status of the corresponding object.
 *    Otherwise, returns an array of all loaded objects.
 */
	public function loaded($name = null) {
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
		list(, $name) = pluginSplit($name);
		unset($this->_loaded[$name], $this->_enabled[$name]);
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
			list(, $name) = pluginSplit($name);
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
			list(, $name) = pluginSplit($objectName);
			$normal[$name] = array('class' => $objectName, 'settings' => $options);
		}
		return $normal;
	}

}
