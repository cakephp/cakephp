<?php
/**
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
 * @subpackage    cake.cake.libs.controller
 * @since         CakePHP(tm) v TBD
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

/**
 * Handler for Controller::$components
 *
 * @package       cake
 * @subpackage    cake.cake.libs.controller
 * @link          http://book.cakephp.org/view/62/Components
 */
class Component extends Object {

/**
 * Contains various controller variable information (plugin, name, base).
 *
 * @var object
 * @access private
 */
	var $__controllerVars = array('plugin' => null, 'name' => null, 'base' => null);

/**
 * List of loaded components.
 *
 * @var object
 * @access protected
 */
	var $_loaded = array();

/**
 * List of components attached directly to the controller, which callbacks
 * should be executed on.
 *
 * @var object
 * @access protected
 */
	var $_primary = array();

/**
 * Settings for loaded components.
 *
 * @var array
 * @access private
 */
	var $__settings = array();

/**
 * Used to initialize the components for current controller.
 *
 * @param object $controller Controller with components to load
 * @return void
 * @access public
 */
	function init(&$controller) {
		if (!is_array($controller->components)) {
			return;
		}
		$this->__controllerVars = array(
			'plugin' => $controller->plugin, 'name' => $controller->name,
			'base' => $controller->base
		);

		$this->_loadComponents($controller);
	}

/**
 * Called before the Controller::beforeFilter().
 *
 * @param object $controller Controller with components to initialize
 * @return void
 * @access public
 * @link http://book.cakephp.org/view/65/MVC-Class-Access-Within-Components
 */
	function initialize(&$controller) {
		foreach (array_keys($this->_loaded) as $name) {
			$component =& $this->_loaded[$name];

			if (method_exists($component,'initialize') && $component->enabled === true) {
				$settings = array();
				if (isset($this->__settings[$name])) {
					$settings = $this->__settings[$name];
				}
				$component->initialize($controller, $settings);
			}
		}
	}

/**
 * Called after the Controller::beforeFilter() and before the controller action
 *
 * @param object $controller Controller with components to startup
 * @return void
 * @access public
 * @link http://book.cakephp.org/view/65/MVC-Class-Access-Within-Components
 */
	function startup(&$controller) {
		foreach ($this->_primary as $name) {
			$component =& $this->_loaded[$name];
			if ($component->enabled === true && method_exists($component, 'startup')) {
				$component->startup($controller);
			}
		}
	}

/**
 * Called after the Controller::beforeRender(), after the view class is loaded, and before the
 * Controller::render()
 *
 * @param object $controller Controller with components to beforeRender
 * @return void
 * @access public
 */
	function beforeRender(&$controller) {
		foreach ($this->_primary as $name) {
			$component =& $this->_loaded[$name];
			if ($component->enabled === true && method_exists($component,'beforeRender')) {
				$component->beforeRender($controller);
			}
		}
	}

/**
 * Called before Controller::redirect().
 *
 * @param object $controller Controller with components to beforeRedirect
 * @return void
 * @access public
 */
	function beforeRedirect(&$controller, $url, $status = null, $exit = true) {
		$response = array();

		foreach ($this->_primary as $name) {
			$component =& $this->_loaded[$name];

			if ($component->enabled === true && method_exists($component, 'beforeRedirect')) {
				$resp = $component->beforeRedirect($controller, $url, $status, $exit);
				if ($resp === false) {
					return false;
				}
				$response[] = $resp;
			}
		}
		return $response;
	}

/**
 * Called after Controller::render() and before the output is printed to the browser.
 *
 * @param object $controller Controller with components to shutdown
 * @return void
 * @access public
 */
	function shutdown(&$controller) {
		foreach ($this->_primary as $name) {
			$component =& $this->_loaded[$name];
			if (method_exists($component,'shutdown') && $component->enabled === true) {
				$component->shutdown($controller);
			}
		}
	}

/**
 * Loads components used by this component.
 *
 * @param object $object Object with a Components array
 * @param object $parent the parent of the current object
 * @return void
 * @access protected
 */
	function _loadComponents(&$object, $parent = null) {
		$base = $this->__controllerVars['base'];
		$normal = Set::normalize($object->components);
		foreach ((array)$normal as $component => $config) {
			$plugin = isset($this->__controllerVars['plugin']) ? $this->__controllerVars['plugin'] . '.' : null;
			list($plugin, $component) = pluginSplit($component, true, $plugin);
			$componentCn = $component . 'Component';

			if (!class_exists($componentCn)) {
				if (is_null($plugin) || !App::import('Component', $plugin . $component)) {
					if (!App::import('Component', $component)) {
						$this->cakeError('missingComponentFile', array(array(
							'className' => $this->__controllerVars['name'],
							'component' => $component,
							'file' => Inflector::underscore($component) . '.php',
							'base' => $base,
							'code' => 500
						)));
						return false;
					}
				}

				if (!class_exists($componentCn)) {
					$this->cakeError('missingComponentClass', array(array(
						'className' => $this->__controllerVars['name'],
						'component' => $component,
						'file' => Inflector::underscore($component) . '.php',
						'base' => $base,
						'code' => 500
					)));
					return false;
				}
			}

			if ($parent === null) {
				$this->_primary[] = $component;
			}

			if (isset($this->_loaded[$component])) {
				$object->{$component} =& $this->_loaded[$component];

				if (!empty($config) && isset($this->__settings[$component])) {
					$this->__settings[$component] = array_merge($this->__settings[$component], $config);
				} elseif (!empty($config)) {
					$this->__settings[$component] = $config;
				}
			} else {
				if ($componentCn === 'SessionComponent') {
					$object->{$component} =& new $componentCn($base);
				} else {
					$object->{$component} =& new $componentCn();
				}
				$object->{$component}->enabled = true;
				$this->_loaded[$component] =& $object->{$component};
				if (!empty($config)) {
					$this->__settings[$component] = $config;
				}
			}

			if (isset($object->{$component}->components) && is_array($object->{$component}->components) && (!isset($object->{$component}->{$parent}))) {
				$this->_loadComponents($object->{$component}, $component);
			}
		}
	}
}

?>