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
namespace Cake\View;

use Cake\Core\App;
use Cake\Error;
use Cake\Event\EventManager;
use Cake\Utility\ObjectRegistry;
use Cake\View\View;

/**
 * HelperRegistry is used as a registry for loaded helpers and handles loading
 * and constructing helper class objects.
 */
class HelperRegistry extends ObjectRegistry {

/**
 * View object to use when making helpers.
 *
 * @var View
 */
	protected $_View;

/**
 * EventManager instance.
 *
 * Helpers constructed by this object will be subscribed to this manager.
 *
 * @var \Cake\Event\EventManager
 */
	protected $_eventManager;

/**
 * Constructor
 *
 * @param View $view
 */
	public function __construct(View $view) {
		$this->_View = $view;
		$this->_eventManager = $view->getEventManager();
	}

/**
 * Tries to lazy load a helper based on its name, if it cannot be found
 * in the application folder, then it tries looking under the current plugin
 * if any
 *
 * @param string $helper The helper name to be loaded
 * @return boolean whether the helper could be loaded or not
 * @throws \Cake\Error\MissingHelperException When a helper could not be found.
 *    App helpers are searched, and then plugin helpers.
 */
	public function __isset($helper) {
		if (isset($this->_loaded[$helper])) {
			return true;
		}

		try {
			$this->load($helper);
		} catch (Error\MissingHelperException $exception) {
			if ($this->_View->plugin) {
				$this->load($this->_View->plugin . '.' . $helper);
				return true;
			}
		}

		if (!empty($exception)) {
			throw $exception;
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
		if (isset($this->$name)) {
			return $this->_loaded[$name];
		}
		return null;
	}

/**
 * Resolve a helper classname.
 *
 * Part of the template method for Cake\Utility\ObjectRegistry::load()
 *
 * @param string $class Partial classname to resolve.
 * @return string|false Either the correct classname or false.
 */
	protected function _resolveClassName($class) {
		return App::classname($class, 'View/Helper', 'Helper');
	}

/**
 * Throws an exception when a helper is missing.
 *
 * Part of the template method for Cake\Utility\ObjectRegistry::load()
 *
 * @param string $class The classname that is missing.
 * @param string $plugin The plugin the helper is missing in.
 * @throws \Cake\Error\MissingHelperException
 */
	protected function _throwMissingClassError($class, $plugin) {
		throw new Error\MissingHelperException([
			'class' => $class,
			'plugin' => $plugin
		]);
	}

/**
 * Create the helper instance.
 *
 * Part of the template method for Cake\Utility\ObjectRegistry::load()
 * Enabled helpers will be registered with the event manager.
 *
 * @param string $class The class to create.
 * @param string $alias The alias of the loaded helper.
 * @param array $settings An array of settings to use for the helper.
 * @return Component The constructed helper class.
 */
	protected function _create($class, $alias, $settings) {
		$instance = new $class($this->_View, $settings);
		$vars = array('request', 'theme', 'plugin');
		foreach ($vars as $var) {
			$instance->{$var} = $this->_View->{$var};
		}
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
		foreach ($this->_loaded as $helper) {
			$this->_eventManager->detach($helper);
		}
		parent::reset();
	}

}
