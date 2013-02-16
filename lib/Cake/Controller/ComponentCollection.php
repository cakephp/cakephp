<?php
/**
 * Components collection is used as a registry for loaded components and handles loading
 * and constructing component class objects.
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       Cake.Controller
 * @since         CakePHP(tm) v 2.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

App::uses('ObjectCollection', 'Utility');
App::uses('Component', 'Controller');
App::uses('CakeEventListener', 'Event');

/**
 * Components collection is used as a registry for loaded components and handles loading
 * and constructing component class objects.
 *
 * @package       Cake.Controller
 */
class ComponentCollection extends ObjectCollection implements CakeEventListener {

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
 * @return Controller Controller instance
 */
	public function getController() {
		return $this->_Controller;
	}

/**
 * Loads/constructs a component. Will return the instance in the registry if it already exists.
 * You can use `$settings['enabled'] = false` to disable callbacks on a component when loading it.
 * Callbacks default to on. Disabled component methods work as normal, only callbacks are disabled.
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
				'class' => $componentClass,
				'plugin' => substr($plugin, 0, -1)
			));
		}
		$this->_loaded[$alias] = new $componentClass($this, $settings);
		$enable = isset($settings['enabled']) ? $settings['enabled'] : true;
		if ($enable) {
			$this->enable($alias);
		}
		return $this->_loaded[$alias];
	}

/**
 * Returns the implemented events that will get routed to the trigger function
 * in order to dispatch them separately on each component
 *
 * @return array
 */
	public function implementedEvents() {
		return array(
			'Controller.initialize' => array('callable' => 'trigger'),
			'Controller.startup' => array('callable' => 'trigger'),
			'Controller.beforeRender' => array('callable' => 'trigger'),
			'Controller.beforeRedirect' => array('callable' => 'trigger'),
			'Controller.shutdown' => array('callable' => 'trigger'),
		);
	}

}
