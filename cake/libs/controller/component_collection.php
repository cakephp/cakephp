<?php
/**
 * Components collection is used as a registry for loaded components and handles loading
 * and constructing component class objects.
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
 * @subpackage    cake.libs.controller
 * @since         CakePHP(tm) v 2.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
App::import('Core', 'ObjectCollection');

class ComponentCollection extends ObjectCollection {

/**
 * Initializes all the Components for a controller.
 * Attaches a reference of each component to the Controller.
 *
 * @param Controller $controller Controller to initialize components for.
 * @return void
 */
	public function init(Controller $Controller) {
		if (empty($Controller->components)) {
			return;
		}
		$components = ComponentCollection::normalizeObjectArray($Controller->components);
		foreach ($components as $name => $properties) {
			$Controller->{$name} = $this->load($properties['class'], $properties['settings']);
		}
	}

/**
 * Loads/constructs a component.  Will return the instance in the registry if it already exists.
 * 
 * @param string $component Component name to load
 * @param array $settings Settings for the component.
 * @param boolean $enable Whether or not this component should be enabled by default
 * @return Component A component object, Either the existing loaded component or a new one.
 * @throws MissingComponentFileException, MissingComponentClassException when the component could not be found
 */
	public function load($component, $settings = array(), $enable = true) {
		list($plugin, $name) = pluginSplit($component);
		if (isset($this->_loaded[$name])) {
			return $this->_loaded[$name];
		}
		$componentClass = $name . 'Component';
		if (!class_exists($componentClass)) {
			if (!App::import('Component', $component)) {
				throw new MissingComponentFileException(Inflector::underscore($component) . '.php');
			}
			if (!class_exists($componentClass)) {
				throw new MissingComponentFileException($component);
			}
		}
		$this->_loaded[$name] = new $componentClass($this, $settings);
		if ($enable === true) {
			$this->_enabled[] = $name;
		}
		return $this->_loaded[$name];
	}

}