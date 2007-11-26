<?php
/* SVN FILE: $Id$ */
/**
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) :  Rapid Development Framework <http://www.cakephp.org/>
 * Copyright 2005-2007, Cake Software Foundation, Inc.
 *								1785 E. Sahara Avenue, Suite 490-204
 *								Las Vegas, Nevada 89104
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @filesource
 * @copyright		Copyright 2005-2007, Cake Software Foundation, Inc.
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
 * Base class for all CakePHP Components.
 *
 * @package		cake
 * @subpackage	cake.cake.libs.controller
 */
class Component extends Object {
/**
 * Components used by this component.
 *
 * @var array
 * @access public
 */
	var $components = array();
/**
 * Controller to which this component is linked.
 *
 * @var object
 * @access public
 */
	var $controller = null;

/**
 * Constructor
 *
 * @return object
 */
	function __construct() {
	}
/**
 * Used to initialize the components for current controller
 *
 * @param object $controller Controller using this component.
 * @access public
 */
	function init(&$controller) {
		$this->controller =& $controller;
		if ($this->controller->components !== false) {
			$loaded = array();

			if(in_array('Security', $this->controller->components)) {
				$remove = array_flip($this->controller->components);
				unset($remove['Security']);
				$this->controller->components = array_merge(array('Session', 'Security'), array_flip($remove));
			} else {
				$this->controller->components = array_merge(array('Session'), $this->controller->components);
			}
			$loaded = $this->_loadComponents($loaded, $this->controller->components);

			foreach (array_keys($loaded) as $component) {
				$tempComponent =& $loaded[$component];
				if (isset($tempComponent->components) && is_array($tempComponent->components)) {
					foreach ($tempComponent->components as $subComponent) {
						$this->controller->{$component}->{$subComponent} =& $loaded[$subComponent];
					}
				}
				if (is_callable(array($tempComponent, 'initialize'))) {
					$tempComponent->initialize($controller);
				}
			}
		}
	}
/**
 * Load components used by this component.
 *
 * @param array $loaded Components already loaded (indexed by component name)
 * @param array $components Components to load
 * @return array Components loaded
 * @access protected
 */
	function &_loadComponents(&$loaded, $components) {
		foreach ($components as $component) {
			$parts = preg_split('/\/|\./', $component);

			if (count($parts) === 1) {
				$plugin = $this->controller->plugin;
			} else {
				$plugin = Inflector::underscore($parts['0']);
				$component = $parts[count($parts) - 1];
			}

			$componentCn = $component . 'Component';

			if (in_array($component, array_keys($loaded)) !== true) {
				if (!class_exists($componentCn)) {
					if (is_null($plugin) || !App::import('Component', $plugin . '.' . $component)) {
						if (!App::import('Component', $component)) {
							$this->cakeError('missingComponentFile', array(array(
													'className' => $this->controller->name,
													'component' => $component,
													'file' => Inflector::underscore($component) . '.php',
													'base' => $this->controller->base)));
							exit();
						}
					}

					if (!class_exists($componentCn)) {
						$this->cakeError('missingComponentClass', array(array(
												'className' => $this->controller->name,
												'component' => $component,
												'file' => Inflector::underscore($component) . '.php',
												'base' => $this->controller->base)));
						exit();
					}
				}

				if ($componentCn == 'SessionComponent') {
					$param = Router::stripPlugin($this->controller->base, $this->controller->plugin) . '/';
				} else {
					$param = null;
				}
				$this->controller->{$component} =& new $componentCn($param);
				$loaded[$component] =& $this->controller->{$component};
				if (isset($this->controller->{$component}->components) && is_array($this->controller->{$component}->components)) {
					$loaded =& $this->_loadComponents($loaded, $this->controller->{$component}->components);
				}
			}
		}
		return $loaded;
	}
}
?>