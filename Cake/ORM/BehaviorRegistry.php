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
 * @param array $settings An array of settings to use for the behavior.
 * @return Component The constructed behavior class.
 */
	protected function _create($class, $settings) {
		$instance = new $class($this->_table, $settings);
		$enable = isset($settings['enabled']) ? $settings['enabled'] : true;
		if ($enable) {
			$this->_eventManager->attach($instance);
		}
		return $instance;
	}

/**
 * Check if any of the loaded behaviors implement a method.
 *
 * Will return true if any behavior provides a public method with
 * the chosen name.
 *
 * @param string $method The method to check for.
 * @return boolean
 */
	public function hasMethod($method) {
	}

/**
 * Invoke a method on a behavior.
 *
 * @param string $method The method to invoke.
 * @param mixed $args The arguments you want to invoke the method with should
 *  be provided as the remaining arguments to call()
 * @return mixed The return value depends on the underlying behavior method.
 */
	public function call($method) {
	}

}
