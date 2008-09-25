<?php
/* SVN FILE: $Id$ */
/**
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) :  Rapid Development Framework <http://www.cakephp.org/>
 * Copyright 2005-2008, Cake Software Foundation, Inc.
 *								1785 E. Sahara Avenue, Suite 490-204
 *								Las Vegas, Nevada 89104
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @filesource
 * @copyright		Copyright 2005-2008, Cake Software Foundation, Inc.
 * @link				http://www.cakefoundation.org/projects/info/cakephp CakePHP(tm) Project
 * @package			cake
 * @subpackage		cake.cake.libs.controller
 * @since			CakePHP(tm) v TBD
 * @version			$Revision$
 * @modifiedby		$LastChangedBy$
 * @lastmodified	$Date$
 * @license			http://www.opensource.org/licenses/mit-license.php The MIT License
 */
/**
 * Handler for Controller::$components
 *
 * @package		cake
 * @subpackage	cake.cake.libs.controller
 */
class Component extends Object {
/**
 * Some vars from controller (plugin, name, base)
 *
 * @var object
 * @access private
 */
	var $__controllerVars = array('plugin' => null, 'name' => null, 'base' => null);
/**
 * All loaded components
 *
 * @var object
 * @access private
 */
	var $__loaded = array();
/**
 * Settings for loaded components.
 *
 * @var array
 * @access private
 **/
	var $__settings = array();
/**
 * Used to initialize the components for current controller
 *
 * @param object $controller Controller with components to load
 * @return void
 * @access public
 */
	function init(&$controller) {
		if ($controller->components !== false && is_array($controller->components)) {
			$this->__controllerVars = array(
				'plugin' => $controller->plugin, 'name' => $controller->name, 'base' => $controller->base
			);

			if (!in_array('Session', $controller->components)) {
				array_unshift($controller->components, 'Session');
			}
			$this->_loadComponents($controller);
		}
	}
/**
 * Called before the Controller::beforeFilter()
 *
 * @param object $controller Controller with components to initialize
 * @return void
 * @access public
 */
	function initialize(&$controller) {
		foreach (array_keys($this->__loaded) as $name) {
			$component =& $this->__loaded[$name];
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
 */
	function startup(&$controller) {
		foreach (array_keys($this->__loaded) as $name) {
			$component =& $this->__loaded[$name];
			if (method_exists($component,'startup') && $component->enabled === true) {
				$component->startup($controller);
			}
		}
	}
/**
 * Called after the Controller::beforeRender(), after the view class is loaded, and before the Controller::render()
 *
 * @param object $controller Controller with components to beforeRender
 * @return void
 * @access public
 */
	function beforeRender(&$controller) {
		foreach (array_keys($this->__loaded) as $name) {
			$component =& $this->__loaded[$name];
			if (method_exists($component,'beforeRender') && $component->enabled === true) {
				$component->beforeRender($controller);
			}
		}
	}
/**
 * Called before Controller::redirect();
 *
 * @param object $controller Controller with components to beforeRedirect
 * @return void
 * @access public
 */
	function beforeRedirect(&$controller, $url, $status = null, $exit = true) {
		$response = array();
		foreach (array_keys($this->__loaded) as $name) {
			$component =& $this->__loaded[$name];
			if (method_exists($component,'beforeRedirect') && $component->enabled === true) {
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
 * Called after Controller::render() and before the output is printed to the browser
 *
 * @param object $controller Controller with components to shutdown
 * @return void
 * @access public
 */
	function shutdown(&$controller) {
		foreach (array_keys($this->__loaded) as $name) {
			$component =& $this->__loaded[$name];
			if (method_exists($component,'shutdown') && $component->enabled === true) {
				$component->shutdown($controller);
			}
		}
	}
/**
 * Load components used by this component.
 *
 * @param object $object Object with a Components array
 * @param object $parent the parent of the current object
 * @return void
 * @access protected
 */
	function _loadComponents(&$object, $parent = null) {
		$components = $object->components;
		$base = $this->__controllerVars['base'];

		if (is_array($object->components)) {
			$normal = Set::normalize($object->components);
			foreach ($normal as $component => $config) {
				$plugin = null;

				if (isset($this->__controllerVars['plugin'])) {
					$plugin = $this->__controllerVars['plugin'] . '.';
				}

				if (strpos($component, '.') !== false) {
					list($plugin, $component) = explode('.', $component);
					$plugin = $plugin . '.';
				}
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

				if (isset($this->__loaded[$component])) {
					$object->{$component} =& $this->__loaded[$component];

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
					$this->__loaded[$component] =& $object->{$component};
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
}

?>