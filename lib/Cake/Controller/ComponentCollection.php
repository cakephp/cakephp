<?php
/**
 * Components collection is used as a registry for loaded components and handles loading
 * and constructing component class objects.
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2011, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2011, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       Cake.Controller
 * @since         CakePHP(tm) v 2.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

App::uses('ObjectCollection', 'Utility');
App::uses('Component', 'Controller');

/**
 * Components collection is used as a registry for loaded components and handles loading
 * and constructing component class objects.
 *
 * @package       Cake.Controller
 */
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
 * @param Controller $Controller Controller to initialize components for.
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
 * You can alias your component as an existing component by setting the 'className' key, i.e.,
 * {{{
 * public $components = array(
 *   'Email' => array(
 *     'className' => 'AliasedEmail'
 *   );
 * );
 * }}}
 * All calls to the `Email` component would use `AliasedEmail` instead.
 *
 * @param string $component Component name to load
 * @param array $settings Settings for the component.
 * @return Component A component object, Either the existing loaded component or a new one.
 * @throws MissingComponentException when the component could not be found
 */
	public function load($component, $settings = array()) {
		if (is_array($settings) && isset($settings['className'])) {
			$alias = $component;
			$component = $settings['className'];
		}
		list($plugin, $name) = pluginSplit($component, true);
		if (!isset($alias)) {
			$alias = $name;
		}
		if (isset($this->_loaded[$alias])) {
			return $this->_loaded[$alias];
		}
		$componentClass = $name . 'Component';
		App::uses($componentClass, $plugin . 'Controller/Component');
		if (!class_exists($componentClass)) {
			throw new MissingComponentException(array(
				'class' => $componentClass
			));
		}
		$this->_loaded[$alias] = new $componentClass($this, $settings);
		$enable = isset($settings['enabled']) ? $settings['enabled'] : true;
		if ($enable) {
			$this->enable($alias);
		}
		return $this->_loaded[$alias];
	}

}