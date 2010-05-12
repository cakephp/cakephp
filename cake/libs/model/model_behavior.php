<?php
/**
 * Model behaviors base class.
 *
 * Adds methods and automagic functionality to Cake Models.
 *
 * PHP versions 4 and 5
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
 * @subpackage    cake.cake.libs.model
 * @since         CakePHP(tm) v 1.2.0.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

/**
 * Model behavior base class.
 *
 * Defines the Behavior interface, and contains common model interaction functionality.
 *
 * @package       cake
 * @subpackage    cake.cake.libs.model
 */
class ModelBehavior extends Object {

/**
 * Contains configuration settings for use with individual model objects.  This
 * is used because if multiple models use this Behavior, each will use the same
 * object instance.  Individual model settings should be stored as an
 * associative array, keyed off of the model name.
 *
 * @var array
 * @access public
 * @see Model::$alias
 */
	var $settings = array();

/**
 * Allows the mapping of preg-compatible regular expressions to public or
 * private methods in this class, where the array key is a /-delimited regular
 * expression, and the value is a class method.  Similar to the functionality of
 * the findBy* / findAllBy* magic methods.
 *
 * @var array
 * @access public
 */
	var $mapMethods = array();

/**
 * Setup this behavior with the specified configuration settings.
 *
 * @param object $model Model using this behavior
 * @param array $config Configuration settings for $model
 * @access public
 */
	function setup(&$model, $config = array()) { }

/**
 * Clean up any initialization this behavior has done on a model.  Called when a behavior is dynamically
 * detached from a model using Model::detach().
 *
 * @param object $model Model using this behavior
 * @access public
 * @see BehaviorCollection::detach()
 */
	function cleanup(&$model) {
		if (isset($this->settings[$model->alias])) {
			unset($this->settings[$model->alias]);
		}
	}

/**
 * Before find callback
 *
 * @param object $model Model using this behavior
 * @param array $queryData Data used to execute this query, i.e. conditions, order, etc.
 * @return boolean True if the operation should continue, false if it should abort
 * @access public
 */
	function beforeFind(&$model, $query) { }

/**
 * After find callback. Can be used to modify any results returned by find and findAll.
 *
 * @param object $model Model using this behavior
 * @param mixed $results The results of the find operation
 * @param boolean $primary Whether this model is being queried directly (vs. being queried as an association)
 * @return mixed Result of the find operation
 * @access public
 */
	function afterFind(&$model, $results, $primary) { }

/**
 * Before validate callback
 *
 * @param object $model Model using this behavior
 * @return boolean True if validate operation should continue, false to abort
 * @access public
 */
	function beforeValidate(&$model) { }

/**
 * Before save callback
 *
 * @param object $model Model using this behavior
 * @return boolean True if the operation should continue, false if it should abort
 * @access public
 */
	function beforeSave(&$model) { }

/**
 * After save callback
 *
 * @param object $model Model using this behavior
 * @param boolean $created True if this save created a new record
 * @access public
 */
	function afterSave(&$model, $created) { }

/**
 * Before delete callback
 *
 * @param object $model Model using this behavior
 * @param boolean $cascade If true records that depend on this record will also be deleted
 * @return boolean True if the operation should continue, false if it should abort
 * @access public
 */
	function beforeDelete(&$model, $cascade = true) { }

/**
 * After delete callback
 *
 * @param object $model Model using this behavior
 * @access public
 */
	function afterDelete(&$model) { }

/**
 * DataSource error callback
 *
 * @param object $model Model using this behavior
 * @param string $error Error generated in DataSource
 * @access public
 */
	function onError(&$model, $error) { }

/**
 * Overrides Object::dispatchMethod to account for PHP4's broken reference support
 *
 * @see Object::dispatchMethod
 * @access public
 * @return mixed
 */
	function dispatchMethod(&$model, $method, $params = array()) {
		if (empty($params)) {
			return $this->{$method}($model);
		}
		$params = array_values($params);

		switch (count($params)) {
			case 1:
				return $this->{$method}($model, $params[0]);
			case 2:
				return $this->{$method}($model, $params[0], $params[1]);
			case 3:
				return $this->{$method}($model, $params[0], $params[1], $params[2]);
			case 4:
				return $this->{$method}($model, $params[0], $params[1], $params[2], $params[3]);
			case 5:
				return $this->{$method}($model, $params[0], $params[1], $params[2], $params[3], $params[4]);
			default:
				array_unshift($params, $model);
				return call_user_func_array(array(&$this, $method), $params);
			break;
		}
	}

/**
 * If $model's whitelist property is non-empty, $field will be added to it.
 * Note: this method should *only* be used in beforeValidate or beforeSave to ensure
 * that it only modifies the whitelist for the current save operation.  Also make sure
 * you explicitly set the value of the field which you are allowing.
 *
 * @param object $model Model using this behavior
 * @param string $field Field to be added to $model's whitelist
 * @access protected
 * @return void
 */
	function _addToWhitelist(&$model, $field) {
		if (is_array($field)) {
			foreach ($field as $f) {
				$this->_addToWhitelist($model, $f);
			}
			return;
		}
		if (!empty($model->whitelist) && !in_array($field, $model->whitelist)) {
			$model->whitelist[] = $field;
		}
	}
}

/**
 * Model behavior collection class.
 *
 * Defines the Behavior interface, and contains common model interaction functionality.
 *
 * @package       cake
 * @subpackage    cake.cake.libs.model
 */
class BehaviorCollection extends Object {

/**
 * Stores a reference to the attached name
 *
 * @var string
 * @access public
 */
	var $modelName = null;

/**
 * Lists the currently-attached behavior objects
 *
 * @var array
 * @access private
 */
	var $_attached = array();

/**
 * Lists the currently-attached behavior objects which are disabled
 *
 * @var array
 * @access private
 */
	var $_disabled = array();

/**
 * Keeps a list of all methods of attached behaviors
 *
 * @var array
 */
	var $__methods = array();

/**
 * Keeps a list of all methods which have been mapped with regular expressions
 *
 * @var array
 */
	var $__mappedMethods = array();

/**
 * Attaches a model object and loads a list of behaviors
 *
 * @access public
 * @return void
 */
	function init($modelName, $behaviors = array()) {
		$this->modelName = $modelName;

		if (!empty($behaviors)) {
			foreach (Set::normalize($behaviors) as $behavior => $config) {
				$this->attach($behavior, $config);
			}
		}
	}

/**
 * Attaches a behavior to a model
 *
 * @param string $behavior CamelCased name of the behavior to load
 * @param array $config Behavior configuration parameters
 * @return boolean True on success, false on failure
 * @access public
 */
	function attach($behavior, $config = array()) {
		list($plugin, $name) = pluginSplit($behavior);
		$class = $name . 'Behavior';

		if (!App::import('Behavior', $behavior)) {
			$this->cakeError('missingBehaviorFile', array(array(
				'behavior' => $behavior,
				'file' => Inflector::underscore($behavior) . '.php',
				'code' => 500,
				'base' => '/'
			)));
			return false;
		}
		if (!class_exists($class)) {
			$this->cakeError('missingBehaviorClass', array(array(
				'behavior' => $class,
				'file' => Inflector::underscore($class) . '.php',
				'code' => 500,
				'base' => '/'
			)));
			return false;
		}

		if (!isset($this->{$name})) {
			if (ClassRegistry::isKeySet($class)) {
				if (PHP5) {
					$this->{$name} = ClassRegistry::getObject($class);
				} else {
					$this->{$name} =& ClassRegistry::getObject($class);
				}
			} else {
				if (PHP5) {
					$this->{$name} = new $class;
				} else {
					$this->{$name} =& new $class;
				}
				ClassRegistry::addObject($class, $this->{$name});
				if (!empty($plugin)) {
					ClassRegistry::addObject($plugin.'.'.$class, $this->{$name});
				}
			}
		} elseif (isset($this->{$name}->settings) && isset($this->{$name}->settings[$this->modelName])) {
			if ($config !== null && $config !== false) {
				$config = array_merge($this->{$name}->settings[$this->modelName], $config);
			} else {
				$config = array();
			}
		}
		if (empty($config)) {
			$config = array();
		}
		$this->{$name}->setup(ClassRegistry::getObject($this->modelName), $config);

		foreach ($this->{$name}->mapMethods as $method => $alias) {
			$this->__mappedMethods[$method] = array($alias, $name);
		}
		$methods = get_class_methods($this->{$name});
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

		if (!in_array($name, $this->_attached)) {
			$this->_attached[] = $name;
		}
		if (in_array($name, $this->_disabled) && !(isset($config['enabled']) && $config['enabled'] === false)) {
			$this->enable($name);
		} elseif (isset($config['enabled']) && $config['enabled'] === false) {
			$this->disable($name);
		}
		return true;
	}

/**
 * Detaches a behavior from a model
 *
 * @param string $name CamelCased name of the behavior to unload
 * @return void
 * @access public
 */
	function detach($name) {
		list($plugin, $name) = pluginSplit($name);
		if (isset($this->{$name})) {
			$this->{$name}->cleanup(ClassRegistry::getObject($this->modelName));
			unset($this->{$name});
		}
		foreach ($this->__methods as $m => $callback) {
			if (is_array($callback) && $callback[1] == $name) {
				unset($this->__methods[$m]);
			}
		}
		$this->_attached = array_values(array_diff($this->_attached, (array)$name));
	}

/**
 * Enables callbacks on a behavior or array of behaviors
 *
 * @param mixed $name CamelCased name of the behavior(s) to enable (string or array)
 * @return void
 * @access public
 */
	function enable($name) {
		$this->_disabled = array_diff($this->_disabled, (array)$name);
	}

/**
 * Disables callbacks on a behavior or array of behaviors.  Public behavior methods are still
 * callable as normal.
 *
 * @param mixed $name CamelCased name of the behavior(s) to disable (string or array)
 * @return void
 * @access public
 */
	function disable($name) {
		foreach ((array)$name as $behavior) {
			if (in_array($behavior, $this->_attached) && !in_array($behavior, $this->_disabled)) {
				$this->_disabled[] = $behavior;
			}
		}
	}

/**
 * Gets the list of currently-enabled behaviors, or, the current status of a single behavior
 *
 * @param string $name Optional.  The name of the behavior to check the status of.  If omitted,
 *   returns an array of currently-enabled behaviors
 * @return mixed If $name is specified, returns the boolean status of the corresponding behavior.
 *   Otherwise, returns an array of all enabled behaviors.
 * @access public
 */
	function enabled($name = null) {
		if (!empty($name)) {
			return (in_array($name, $this->_attached) && !in_array($name, $this->_disabled));
		}
		return array_diff($this->_attached, $this->_disabled);
	}

/**
 * Dispatches a behavior method
 *
 * @return array All methods for all behaviors attached to this object
 * @access public
 */
	function dispatchMethod(&$model, $method, $params = array(), $strict = false) {
		$methods = array_keys($this->__methods);
		foreach ($methods as $key => $value) {
			$methods[$key] = strtolower($value);
		}
		$method = strtolower($method);
		$check = array_flip($methods);
		$found = isset($check[$method]);
		$call = null;

		if ($strict && !$found) {
			trigger_error(sprintf(__("BehaviorCollection::dispatchMethod() - Method %s not found in any attached behavior", true), $method), E_USER_WARNING);
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
			return $this->{$call[1]}->dispatchMethod($model, $call[0], $params);
		}
		return array('unhandled');
	}

/**
 * Dispatches a behavior callback on all attached behavior objects
 *
 * @param model $model
 * @param string $callback
 * @param array $params
 * @param array $options
 * @return mixed
 * @access public
 */
	function trigger(&$model, $callback, $params = array(), $options = array()) {
		if (empty($this->_attached)) {
			return true;
		}
		$_params = $params;
		$options = array_merge(array('break' => false, 'breakOn' => array(null, false), 'modParams' => false), $options);
		$count = count($this->_attached);

		for ($i = 0; $i < $count; $i++) {
			$name = $this->_attached[$i];
			if (in_array($name, $this->_disabled)) {
				continue;
			}
			$result = $this->{$name}->dispatchMethod($model, $callback, $params);

			if ($options['break'] && ($result === $options['breakOn'] || (is_array($options['breakOn']) && in_array($result, $options['breakOn'], true)))) {
				return $result;
			} elseif ($options['modParams'] && is_array($result)) {
				$params[0] = $result;
			}
		}
		if ($options['modParams'] && isset($params[0])) {
			return $params[0];
		}
		return true;
	}

/**
 * Gets the method list for attached behaviors, i.e. all public, non-callback methods
 *
 * @return array All public methods for all behaviors attached to this collection
 * @access public
 */
	function methods() {
		return $this->__methods;
	}

/**
 * Gets the list of attached behaviors, or, whether the given behavior is attached
 *
 * @param string $name Optional.  The name of the behavior to check the status of.  If omitted,
 *   returns an array of currently-attached behaviors
 * @return mixed If $name is specified, returns the boolean status of the corresponding behavior.
 *    Otherwise, returns an array of all attached behaviors.
 * @access public
 */
	function attached($name = null) {
		if (!empty($name)) {
			return (in_array($name, $this->_attached));
		}
		return $this->_attached;
	}
}
