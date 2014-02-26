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
 * @since         CakePHP(tm) v 2.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Controller;

use Cake\Core\App;
use Cake\Error;
use Cake\Event\EventListener;
use Cake\Event\EventManager;
use Cake\Utility\ObjectRegistry;

/**
 * ComponentRegistry is a registry for loaded components
 *
 * Handles loading, constructing and binding events for component class objects.
 */
class ComponentRegistry extends ObjectRegistry {

/**
 * The controller that this collection was initialized with.
 *
 * @var Controller
 */
	protected $_Controller = null;

/**
 * The event manager to bind components to.
 *
 * @var \Cake\Event\EventManager
 */
	protected $_eventManager = null;

/**
 * Constructor.
 *
 * @param \Cake\Controller\Controller $Controller
 */
	public function __construct(Controller $Controller = null) {
		if ($Controller) {
			$this->_Controller = $Controller;
			$this->_eventManager = $Controller->getEventManager();
		} else {
			$this->_eventManager = new EventManager();
		}
	}

/**
 * Get the controller associated with the collection.
 *
 * @return Controller Controller instance
 */
	public function getController() {
		return $this->_Controller;
	}

/**
 * Resolve a component classname.
 *
 * Part of the template method for Cake\Utility\ObjectRegistry::load()
 *
 * @param string $class Partial classname to resolve.
 * @return string|false Either the correct classname or false.
 */
	protected function _resolveClassName($class) {
		return App::classname($class, 'Controller/Component', 'Component');
	}

/**
 * Throws an exception when a component is missing.
 *
 * Part of the template method for Cake\Utility\ObjectRegistry::load()
 *
 * @param string $class The classname that is missing.
 * @param string $plugin The plugin the component is missing in.
 * @throws \Cake\Error\MissingComponentException
 */
	protected function _throwMissingClassError($class, $plugin) {
		throw new Error\MissingComponentException([
			'class' => $class,
			'plugin' => $plugin
		]);
	}

/**
 * Create the component instance.
 *
 * Part of the template method for Cake\Utility\ObjectRegistry::load()
 * Enabled components will be registered with the event manager.
 *
 * @param string $class The classname to create.
 * @param string $alias The alias of the component.
 * @param array $settings An array of settings to use for the component.
 * @return Component The constructed component class.
 */
	protected function _create($class, $alias, $settings) {
		$instance = new $class($this, $settings);
		$enable = isset($settings['enabled']) ? $settings['enabled'] : true;
		if ($enable) {
			$this->_eventManager->attach($instance);
		}
		return $instance;
	}

/**
 * Destroys all objects in the registry.
 *
 * Removes all attached listeners and destroys all stored instances.
 *
 * @return void
 */
	public function reset() {
		foreach ($this->_loaded as $component) {
			$this->_eventManager->detach($component);
		}
		parent::reset();
	}

}
