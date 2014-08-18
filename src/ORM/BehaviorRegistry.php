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
 * @since         3.0.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\ORM;

use Cake\Core\App;
use Cake\Error\Exception;
use Cake\Event\EventManagerTrait;
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

	use EventManagerTrait;

/**
 * The table using this registry.
 *
 * @var \Cake\ORM\Table
 */
	protected $_table;

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
 * @param \Cake\ORM\Table $table The table this registry is attached to
 */
	public function __construct(Table $table) {
		$this->_table = $table;
		$this->eventManager($table->eventManager());
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
		return App::className($class, 'Model/Behavior', 'Behavior');
	}

/**
 * Throws an exception when a behavior is missing.
 *
 * Part of the template method for Cake\Utility\ObjectRegistry::load()
 *
 * @param string $class The classname that is missing.
 * @param string $plugin The plugin the behavior is missing in.
 * @return void
 * @throws \Cake\ORM\Error\MissingBehaviorException
 */
	protected function _throwMissingClassError($class, $plugin) {
		throw new Error\MissingBehaviorException([
			'class' => $class . 'Behavior',
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
 * @param array $config An array of config to use for the behavior.
 * @return Behavior The constructed behavior class.
 */
	protected function _create($class, $alias, $config) {
		$instance = new $class($this->_table, $config);
		$enable = isset($config['enabled']) ? $config['enabled'] : true;
		if ($enable) {
			$this->eventManager()->attach($instance);
		}
		$methods = $this->_getMethods($instance, $class, $alias);
		$this->_methodMap += $methods['methods'];
		$this->_finderMap += $methods['finders'];
		return $instance;
	}

/**
 * Get the behavior methods and ensure there are no duplicates.
 *
 * Use the implementedEvents() method to exclude callback methods.
 * Methods starting with `_` will be ignored, as will methods
 * declared on Cake\ORM\Behavior
 *
 * @param \Cake\ORM\Behavior $instance The behavior to get methods from.
 * @param string $class The classname that is missing.
 * @param string $alias The alias of the object.
 * @return void
 * @throws \Cake\Error\Exception when duplicate methods are connected.
 */
	protected function _getMethods(Behavior $instance, $class, $alias) {
		$finders = array_change_key_case($instance->implementedFinders());
		$methods = array_change_key_case($instance->implementedMethods());

		foreach ($finders as $finder => $methodName) {
			if (isset($this->_finderMap[$finder]) && $this->loaded($this->_finderMap[$finder][0])) {
				$duplicate = $this->_finderMap[$finder];
				$error = sprintf(
					'%s contains duplicate finder "%s" which is already provided by "%s"',
					$class,
					$finder,
					$duplicate[0]
				);
				throw new Exception($error);
			}
			$finders[$finder] = [$alias, $methodName];
		}

		foreach ($methods as $method => $methodName) {
			if (isset($this->_methodMap[$method]) && $this->loaded($this->_methodMap[$method][0])) {
				$duplicate = $this->_methodMap[$method];
				$error = sprintf(
					'%s contains duplicate method "%s" which is already provided by "%s"',
					$class,
					$method,
					$duplicate[0]
				);
				throw new Exception($error);
			}
			$methods[$method] = [$alias, $methodName];
		}

		return compact('methods', 'finders');
	}

/**
 * Check if any loaded behavior implements a method.
 *
 * Will return true if any behavior provides a public non-finder method
 * with the chosen name.
 *
 * @param string $method The method to check for.
 * @return bool
 */
	public function hasMethod($method) {
		$method = strtolower($method);
		return isset($this->_methodMap[$method]);
	}

/**
 * Check if any loaded behavior implements the named finder.
 *
 * Will return true if any behavior provides a public method with
 * the chosen name.
 *
 * @param string $method The method to check for.
 * @return bool
 */
	public function hasFinder($method) {
		$method = strtolower($method);
		return isset($this->_finderMap[$method]);
	}

/**
 * Invoke a method on a behavior.
 *
 * @param string $method The method to invoke.
 * @param array $args The arguments you want to invoke the method with.
 * @return mixed The return value depends on the underlying behavior method.
 * @throws \Cake\Error\Exception When the method is unknown.
 */
	public function call($method, array $args = []) {
		$method = strtolower($method);
		if ($this->hasMethod($method) && $this->loaded($this->_methodMap[$method][0])) {
			list($behavior, $callMethod) = $this->_methodMap[$method];
			return call_user_func_array([$this->_loaded[$behavior], $callMethod], $args);
		}

		throw new Exception(sprintf('Cannot call "%s" it does not belong to any attached behavior.', $method));
	}

/**
 * Invoke a finder on a behavior.
 *
 * @param string $type The finder type to invoke.
 * @param array $args The arguments you want to invoke the method with.
 * @return mixed The return value depends on the underlying behavior method.
 * @throws \Cake\Error\Exception When the method is unknown.
 */
	public function callFinder($type, array $args = []) {
		$type = strtolower($type);

		if ($this->hasFinder($type) && $this->loaded($this->_finderMap[$type][0])) {
			list($behavior, $callMethod) = $this->_finderMap[$type];
			return call_user_func_array([$this->_loaded[$behavior], $callMethod], $args);
		}

		throw new Exception(sprintf('Cannot call finder "%s" it does not belong to any attached behavior.', $type));
	}

}
