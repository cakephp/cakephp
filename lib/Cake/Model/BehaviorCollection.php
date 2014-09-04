<?php
/**
 * BehaviorCollection
 *
 * Provides management and interface for interacting with collections of behaviors.
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       Cake.Model
 * @since         CakePHP(tm) v 1.2.0.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */

App::uses('ObjectCollection', 'Utility');
App::uses('CakeEventListener', 'Event');

/**
 * Model behavior collection class.
 *
 * Defines the Behavior interface, and contains common model interaction functionality.
 *
 * @package       Cake.Model
 */
class BehaviorCollection extends ObjectCollection implements CakeEventListener {

/**
 * Stores a reference to the attached name
 *
 * @var string
 */
	public $modelName = null;

/**
 * Keeps a list of all methods of attached behaviors
 *
 * @var array
 */
	protected $_methods = array();

/**
 * Keeps a list of all methods which have been mapped with regular expressions
 *
 * @var array
 */
	protected $_mappedMethods = array();

/**
 * Attaches a model object and loads a list of behaviors
 *
 * @param string $modelName Model name.
 * @param array $behaviors Behaviors list.
 * @return void
 */
	public function init($modelName, $behaviors = array()) {
		$this->modelName = $modelName;

		if (!empty($behaviors)) {
			foreach (BehaviorCollection::normalizeObjectArray($behaviors) as $config) {
				$this->load($config['class'], $config['settings']);
			}
		}
	}

/**
 * Backwards compatible alias for load()
 *
 * @param string $behavior Behavior name.
 * @param array $config Configuration options.
 * @return void
 * @deprecated 3.0.0 Will be removed in 3.0. Replaced with load().
 */
	public function attach($behavior, $config = array()) {
		return $this->load($behavior, $config);
	}

/**
 * Loads a behavior into the collection. You can use use `$config['enabled'] = false`
 * to load a behavior with callbacks disabled. By default callbacks are enabled. Disable behaviors
 * can still be used as normal.
 *
 * You can alias your behavior as an existing behavior by setting the 'className' key, i.e.,
 * {{{
 * public $actsAs = array(
 *   'Tree' => array(
 *     'className' => 'AliasedTree'
 *   );
 * );
 * }}}
 * All calls to the `Tree` behavior would use `AliasedTree` instead.
 *
 * @param string $behavior CamelCased name of the behavior to load
 * @param array $config Behavior configuration parameters
 * @return bool True on success, false on failure
 * @throws MissingBehaviorException when a behavior could not be found.
 */
	public function load($behavior, $config = array()) {
		if (isset($config['className'])) {
			$alias = $behavior;
			$behavior = $config['className'];
		}
		$configDisabled = isset($config['enabled']) && $config['enabled'] === false;
		$priority = isset($config['priority']) ? $config['priority'] : $this->defaultPriority;
		unset($config['enabled'], $config['className'], $config['priority']);

		list($plugin, $name) = pluginSplit($behavior, true);
		if (!isset($alias)) {
			$alias = $name;
		}

		$class = $name . 'Behavior';

		App::uses($class, $plugin . 'Model/Behavior');
		if (!class_exists($class)) {
			throw new MissingBehaviorException(array(
				'class' => $class,
				'plugin' => substr($plugin, 0, -1)
			));
		}

		if (!isset($this->{$alias})) {
			if (ClassRegistry::isKeySet($class)) {
				$this->_loaded[$alias] = ClassRegistry::getObject($class);
			} else {
				$this->_loaded[$alias] = new $class();
				ClassRegistry::addObject($class, $this->_loaded[$alias]);
			}
		} elseif (isset($this->_loaded[$alias]->settings) && isset($this->_loaded[$alias]->settings[$this->modelName])) {
			if ($config !== null && $config !== false) {
				$config = array_merge($this->_loaded[$alias]->settings[$this->modelName], $config);
			} else {
				$config = array();
			}
		}
		if (empty($config)) {
			$config = array();
		}
		$this->_loaded[$alias]->settings['priority'] = $priority;
		$this->_loaded[$alias]->setup(ClassRegistry::getObject($this->modelName), $config);

		foreach ($this->_loaded[$alias]->mapMethods as $method => $methodAlias) {
			$this->_mappedMethods[$method] = array($alias, $methodAlias);
		}
		$methods = get_class_methods($this->_loaded[$alias]);
		$parentMethods = array_flip(get_class_methods('ModelBehavior'));
		$callbacks = array(
			'setup', 'cleanup', 'beforeFind', 'afterFind', 'beforeSave', 'afterSave',
			'beforeDelete', 'afterDelete', 'onError'
		);

		foreach ($methods as $m) {
			if (!isset($parentMethods[$m])) {
				$methodAllowed = (
					$m[0] !== '_' && !array_key_exists($m, $this->_methods) &&
					!in_array($m, $callbacks)
				);
				if ($methodAllowed) {
					$this->_methods[$m] = array($alias, $m);
				}
			}
		}

		if ($configDisabled) {
			$this->disable($alias);
		} elseif (!$this->enabled($alias)) {
			$this->enable($alias);
		} else {
			$this->setPriority($alias, $priority);
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
		list(, $name) = pluginSplit($name);
		if (isset($this->_loaded[$name])) {
			$this->_loaded[$name]->cleanup(ClassRegistry::getObject($this->modelName));
			parent::unload($name);
		}
		foreach ($this->_methods as $m => $callback) {
			if (is_array($callback) && $callback[0] === $name) {
				unset($this->_methods[$m]);
			}
		}
	}

/**
 * Backwards compatible alias for unload()
 *
 * @param string $name Name of behavior
 * @return void
 * @deprecated 3.0.0 Will be removed in 3.0. Use unload instead.
 */
	public function detach($name) {
		return $this->unload($name);
	}

/**
 * Dispatches a behavior method. Will call either normal methods or mapped methods.
 *
 * If a method is not handled by the BehaviorCollection, and $strict is false, a
 * special return of `array('unhandled')` will be returned to signal the method was not found.
 *
 * @param Model $model The model the method was originally called on.
 * @param string $method The method called.
 * @param array $params Parameters for the called method.
 * @param bool $strict If methods are not found, trigger an error.
 * @return array All methods for all behaviors attached to this object
 */
	public function dispatchMethod($model, $method, $params = array(), $strict = false) {
		$method = $this->hasMethod($method, true);

		if ($strict && empty($method)) {
			trigger_error(__d('cake_dev', '%s - Method %s not found in any attached behavior', 'BehaviorCollection::dispatchMethod()', $method), E_USER_WARNING);
			return null;
		}
		if (empty($method)) {
			return array('unhandled');
		}
		if (count($method) === 3) {
			array_unshift($params, $method[2]);
			unset($method[2]);
		}
		return call_user_func_array(
			array($this->_loaded[$method[0]], $method[1]),
			array_merge(array(&$model), $params)
		);
	}

/**
 * Gets the method list for attached behaviors, i.e. all public, non-callback methods.
 * This does not include mappedMethods.
 *
 * @return array All public methods for all behaviors attached to this collection
 */
	public function methods() {
		return $this->_methods;
	}

/**
 * Check to see if a behavior in this collection implements the provided method. Will
 * also check mappedMethods.
 *
 * @param string $method The method to find.
 * @param bool $callback Return the callback for the method.
 * @return mixed If $callback is false, a boolean will be returned, if its true, an array
 *   containing callback information will be returned. For mapped methods the array will have 3 elements.
 */
	public function hasMethod($method, $callback = false) {
		if (isset($this->_methods[$method])) {
			return $callback ? $this->_methods[$method] : true;
		}
		foreach ($this->_mappedMethods as $pattern => $target) {
			if (preg_match($pattern . 'i', $method)) {
				if ($callback) {
					$target[] = $method;
					return $target;
				}
				return true;
			}
		}
		return false;
	}

/**
 * Returns the implemented events that will get routed to the trigger function
 * in order to dispatch them separately on each behavior
 *
 * @return array
 */
	public function implementedEvents() {
		return array(
			'Model.beforeFind' => 'trigger',
			'Model.afterFind' => 'trigger',
			'Model.beforeValidate' => 'trigger',
			'Model.afterValidate' => 'trigger',
			'Model.beforeSave' => 'trigger',
			'Model.afterSave' => 'trigger',
			'Model.beforeDelete' => 'trigger',
			'Model.afterDelete' => 'trigger'
		);
	}

}
