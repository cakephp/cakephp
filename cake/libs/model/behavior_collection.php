<?php
/**
 * BehaviorCollection
 *
 * Provides managment and interface for interacting with collections of behaviors.
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
 * @package       cake.libs.model
 * @since         CakePHP(tm) v 1.2.0.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
App::import('Core', 'ObjectCollection');

/**
 * Model behavior collection class.
 *
 * Defines the Behavior interface, and contains common model interaction functionality.
 *
 * @package       cake.libs.model
 */
class BehaviorCollection extends ObjectCollection {

/**
 * Stores a reference to the attached name
 *
 * @var string
 * @access public
 */
	public $modelName = null;

/**
 * Keeps a list of all methods of attached behaviors
 *
 * @var array
 */
	private $__methods = array();

/**
 * Keeps a list of all methods which have been mapped with regular expressions
 *
 * @var array
 */
	private $__mappedMethods = array();

/**
 * Attaches a model object and loads a list of behaviors
 *
 * @todo Make this method a constructor instead..
 * @access public
 * @return void
 */
	function init($modelName, $behaviors = array()) {
		$this->modelName = $modelName;

		if (!empty($behaviors)) {
			foreach (BehaviorCollection::normalizeObjectArray($behaviors) as $behavior => $config) {
				$this->load($config['class'], $config['settings']);
			}
		}
	}

/**
 * Backwards compatible alias for load()
 *
 * @return void
 * @deprecated Replaced with load()
 */
	public function attach($behavior, $config = array()) {
		return $this->load($behavior, $config);
	}

/**
 * Loads a behavior into the collection. You can use use `$config['enabled'] = false`
 * to load a behavior with callbacks disabled. By default callbacks are enabled. Disable behaviors
 * can still be used as normal.
 *
 * @param string $behavior CamelCased name of the behavior to load
 * @param array $config Behavior configuration parameters
 * @return boolean True on success, false on failure
 * @throws MissingBehaviorFileException or MissingBehaviorClassException when a behavior could not be found.
 */
	public function load($behavior, $config = array()) {
		list($plugin, $name) = pluginSplit($behavior);
		$class = $name . 'Behavior';

		if (!App::import('Behavior', $behavior)) {
			throw new MissingBehaviorFileException(array(
				'file' => Inflector::underscore($behavior) . '.php',
				'class' => $class
			));
		}
		if (!class_exists($class)) {
			throw new MissingBehaviorClassException(array(
				'file' => Inflector::underscore($behavior) . '.php',
				'class' => $class
			));
		}

		if (!isset($this->{$name})) {
			if (ClassRegistry::isKeySet($class)) {
				$this->_loaded[$name] = ClassRegistry::getObject($class);
			} else {
				$this->_loaded[$name] = new $class();
				ClassRegistry::addObject($class, $this->_loaded[$name]);
				if (!empty($plugin)) {
					ClassRegistry::addObject($plugin . '.' . $class, $this->_loaded[$name]);
				}
			}
		} elseif (isset($this->_loaded[$name]->settings) && isset($this->_loaded[$name]->settings[$this->modelName])) {
			if ($config !== null && $config !== false) {
				$config = array_merge($this->_loaded[$name]->settings[$this->modelName], $config);
			} else {
				$config = array();
			}
		}
		if (empty($config)) {
			$config = array();
		}
		$this->_loaded[$name]->setup(ClassRegistry::getObject($this->modelName), $config);

		foreach ($this->_loaded[$name]->mapMethods as $method => $alias) {
			$this->__mappedMethods[$method] = array($alias, $name);
		}
		$methods = get_class_methods($this->_loaded[$name]);
		$parentMethods = array_flip(get_class_methods('ModelBehavior'));
		$callbacks = array(
			'setup', 'cleanup', 'beforeFind', 'afterFind', 'beforeSave', 'afterSave',
			'beforeDelete', 'afterDelete', 'afterError'
		);

		foreach ($methods as $m) {
			if (!isset($parentMethods[$m])) {
				$methodAllowed = (
					$m[0] != '_' && !array_key_exists($m, $this->__methods) &&
					!in_array($m, $callbacks)
				);
				if ($methodAllowed) {
					$this->__methods[$m] = array($m, $name);
				}
			}
		}

		$configDisabled = isset($config['enabled']) && $config['enabled'] === false;
		if (!in_array($name, $this->_enabled) && !$configDisabled) {
			$this->enable($name);
		} elseif ($configDisabled) {
			$this->disable($name);
		}
		return true;
	}

/**
 * Detaches a behavior from a model
 *
 * @param string $name CamelCased name of the behavior to unload
 * @return void
 */
	public function unload($name) {
		list($plugin, $name) = pluginSplit($name);
		if (isset($this->_loaded[$name])) {
			$this->_loaded[$name]->cleanup(ClassRegistry::getObject($this->modelName));
			unset($this->_loaded[$name]);
		}
		foreach ($this->__methods as $m => $callback) {
			if (is_array($callback) && $callback[1] == $name) {
				unset($this->__methods[$m]);
			}
		}
		$this->_enabled = array_values(array_diff($this->_enabled, (array)$name));
	}

/**
 * Backwards compatible alias for unload()
 *
 * @param string $name Name of behavior
 * @return void
 * @deprecated Use unload instead.
 */
	public function detach($name) {
		return $this->unload($name);
	}

/**
 * Dispatches a behavior method
 *
 * @return array All methods for all behaviors attached to this object
 */
	public function dispatchMethod(&$model, $method, $params = array(), $strict = false) {
		$methods = array_keys($this->__methods);
		$check = array_flip($methods);
		$found = isset($check[$method]);
		$call = null;

		if ($strict && !$found) {
			trigger_error(__("BehaviorCollection::dispatchMethod() - Method %s not found in any attached behavior", $method), E_USER_WARNING);
			return null;
		} elseif ($found) {
			$methods = array_combine($methods, array_values($this->__methods));
			$call = $methods[$method];
		} else {
			$count = count($this->__mappedMethods);
			$mapped = array_keys($this->__mappedMethods);

			for ($i = 0; $i < $count; $i++) {
				if (preg_match($mapped[$i] . 'i', $method)) {
					$call = $this->__mappedMethods[$mapped[$i]];
					array_unshift($params, $method);
					break;
				}
			}
		}

		if (!empty($call)) {
			return call_user_func_array(
				array(&$this->_loaded[$call[1]], $call[0]),
				array_merge(array(&$model), $params)
			);
		}
		return array('unhandled');
	}

/**
 * Gets the method list for attached behaviors, i.e. all public, non-callback methods
 *
 * @return array All public methods for all behaviors attached to this collection
 */
	public function methods() {
		return $this->__methods;
	}

}
