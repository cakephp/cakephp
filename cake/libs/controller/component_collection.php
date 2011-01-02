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
 * @package       cake.libs.controller
 * @since         CakePHP(tm) v 2.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
App::import('Core', 'ObjectCollection');

class ComponentCollection extends ObjectCollection {

/**
 * The controller that this collection was initialized with.
 *
 * @var Controller
 */
	protected $_Controller = null;

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
		$this->_Controller = $Controller;
		$components = ComponentCollection::normalizeObjectArray($Controller->components);
		foreach ($components as $name => $properties) {
			$Controller->{$name} = $this->load($properties['class'], $properties['settings']);
		}
	}

/**
 * Get the controller associated with the collection.
 *
 * @return Controller.
 */
	public function getController() {
		return $this->_Controller;
	}

/**
 * Loads/constructs a component.  Will return the instance in the registry if it already exists.
 * You can use `$settings['enabled'] = false` to disable callbacks on a component when loading it.
 * Callbacks default to on.  Disabled component methods work as normal, only callbacks are disabled.
 * 
 * @param string $component Component name to load
 * @param array $settings Settings for the component.
 * @return Component A component object, Either the existing loaded component or a new one.
 * @throws MissingComponentFileException, MissingComponentClassException when the component could not be found
 */
	public function load($component, $settings = array()) {
		list($plugin, $name) = pluginSplit($component);
		if (isset($this->_loaded[$name])) {
			return $this->_loaded[$name];
		}
		$componentClass = $name . 'Component';
		if (!class_exists($componentClass)) {
			if (!App::import('Component', $component)) {
				throw new MissingComponentFileException(array(
					'file' => Inflector::underscore($component) . '.php',
					'class' => $componentClass
				));
			}
			if (!class_exists($componentClass)) {
				throw new MissingComponentClassException(array(
					'file' => Inflector::underscore($component) . '.php',
					'class' => $componentClass
				));
			}
		}
		$this->_loaded[$name] = new $componentClass($this, $settings);
		$enable = isset($settings['enabled']) ? $settings['enabled'] : true;
		if ($enable === true) {
			$this->_enabled[] = $name;
		}
		return $this->_loaded[$name];
	}

}