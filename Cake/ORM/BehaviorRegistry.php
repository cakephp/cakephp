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
 * @since         CakePHP(tm) v 3.0.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
namespace Cake\ORM;

use Cake\Core\App;
use Cake\Error;
use Cake\ORM\Behavior;
use Cake\ORM\Table;
use Cake\Utility\ObjectRegistry;

/**
 * BehaviorRegistry is used as a registry for loaded behaviors and handles loading
 * and constructing behavior objects.
 *
 * This class also provides method for checking and dispatching behavior methods.
 */
class BehaviorRegistry extends ObjectRegistry {

/**
 * The table using this registry.
 *
 * @var Cake\ORM\Table
 */
	protected $_table;

/**
 * EventManager instance.
 *
 * Behaviors constructed by this object will be subscribed to this manager.
 *
 * @var Cake\Event\EventManager
 */
	protected $_eventManager;

/**
 * Method mappings.
 *
 * @var array
 */
	protected $_methodMap = [];

/**
 * Finder method mappings.
 *
 * @var array
 */
	protected $_finderMap = [];

/**
 * Constructor
 *
 * @param Cake\ORM\Table $table
 */
	public function __construct(Table $table) {
		$this->_table = $table;
		$this->_eventManager = $table->getEventManager();
	}

/**
 * Resolve a behavior classname.
 *
 * Part of the template method for Cake\Utility\ObjectRegistry::load()
 *
 * @param string $class Partial classname to resolve.
 * @return string|false Either the correct classname or false.
 */
	protected function _resolveClassName($class) {
		return App::classname($class, 'Model/Behavior', 'Behavior');
	}

/**
 * Throws an exception when a behavior is missing.
 *
 * Part of the template method for Cake\Utility\ObjectRegistry::load()
 *
 * @param string $class The classname that is missing.
 * @param string $plugin The plugin the behavior is missing in.
 * @throws Cake\Error\MissingBehaviorException
 */
	protected function _throwMissingClassError($class, $plugin) {
		throw new Error\MissingBehaviorException([
			'class' => $class,
			'plugin' => $plugin
		]);
	}

/**
 * Create the behavior instance.
 *
 * Part of the template method for Cake\Utility\ObjectRegistry::load()
 * Enabled behaviors will be registered with the event manager.
 *
 * @param string $class The classname that is missing.
 * @param string $alias The alias of the object.
 * @param array $settings An array of settings to use for the behavior.
 * @return Component The constructed behavior class.
 */
	protected function _create($class, $alias, $settings) {
		$instance = new $class($this->_table, $settings);
		$enable = isset($settings['enabled']) ? $settings['enabled'] : true;
		if ($enable) {
			$this->_eventManager->attach($instance);
		}
		$this->_mapMethods($instance, $class, $alias);
		return $instance;
	}

/**
 * Store the map of behavior methods and ensure there are no duplicates.
 *
 * Use the implementedEvents() method to exclude callback methods.
 *
 * @param Cake\ORM\Behavior $instance
 * @return void
 */
	protected function _mapMethods(Behavior $instance, $class, $alias) {
		$events = $instance->implementedEvents();
		$methods = get_class_methods($instance);
		foreach ($events as $e => $binding) {
			if (is_array($binding) && isset($binding['callable']) && isset($binding['callable'])) {
				$binding = $binding['callable'];
			}
			$index = array_search($binding, $methods);
			unset($methods[$index]);
		}

		foreach ($methods as $method) {
			$isFinder = substr($method, 0, 4) === 'find';
			if (($isFinder && isset($this->_finderMap[$method])) || isset($this->_methodMap[$method])) {
				$message = '%s contains duplicate method "%s" which is already provided by %s';
				$error = __d('cake_dev', $message, $class, $method, $this->_methodMap[$method]);
				throw new Error\Exception($error);
			}
			if ($isFinder) {
				$this->_finderMap[$method] = $alias;
			} else {
				$this->_methodMap[$method] = $alias;
			}
		}
		return $instance;
	}

/**
 * Check if any loaded behavior implements a method.
 *
 * Will return true if any behavior provides a public non-finder method 
 * with the chosen name.
 *
 * @param string $method The method to check for.
 * @return boolean
 */
	public function hasMethod($method) {
		return isset($this->_methodMap[$method]);
	}

/**
 * Check if any loaded behavior implements the named finder.
 *
 * Will return true if any behavior provides a public method with
 * the chosen name.
 *
 * @param string $method The method to check for.
 * @return boolean
 */
	public function hasFinder($method) {
		return isset($this->_finderMap[$method]);
	}

/**
 * Invoke a method or finder on a behavior.
 *
 * @param string $method The method to invoke.
 * @param array $args The arguments you want to invoke the method with.
 * @return mixed The return value depends on the underlying behavior method.
 * @throw Cake\Error\Exception When the method is unknown.
 */
	public function call($method, array $args = []) {
		if ($this->hasMethod($method)) {
			$alias = $this->_methodMap[$method];
			return call_user_func_array([$this->_loaded[$alias], $method], $args);
		}
		if ($this->hasFinder($method)) {
			$alias = $this->_finderMap[$method];
			return call_user_func_array([$this->_loaded[$alias], $method], $args);
		}
		throw new Error\Exception(__d('cake_dev', 'Cannot call "%s" it does not belong to any attached behaviors.', $method));
	}

}
