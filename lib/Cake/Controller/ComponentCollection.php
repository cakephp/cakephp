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

/**
 * Components collection is used as a registry for loaded components
 *
 * Handles loading, constructing and binding events for component class objects.
 */
class ComponentCollection {

/**
 * Loaded objects
 *
 * @var array
 */
	protected $_loaded = [];

/**
 * The controller that this collection was initialized with.
 *
 * @var Controller
 */
	protected $_Controller = null;

/**
 * The event manager to bind components to.
 *
 * @var Cake\Event\EventManager
 */
	protected $_eventManager = null;

/**
 * Constructor.
 *
 * @param Cake\Controller\Controller $Controller
 */
	public function __construct(Controller $Controller) {
		$this->_Controller = $Controller;
		$this->_eventManager = $Controller->getEventManager();
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
 *     'className' => '\App\Controller\Component\AliasedEmailComponent'
 *   );
 * );
 * }}}
 * All calls to the `Email` component would use `AliasedEmail` instead.
 *
 * @param string $component Component name to load
 * @param array $settings Settings for the component.
 * @return Component A component object, Either the existing loaded component or a new one.
 * @throws Cake\Error\MissingComponentException when the component could not be found
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
		$componentClass = App::classname($plugin . $name, 'Controller/Component', 'Component');
		if (!$componentClass) {
			throw new Error\MissingComponentException(array(
				'class' => $component,
				'plugin' => substr($plugin, 0, -1)
			));
		}
		$component = new $componentClass($this, $settings);
		$enable = isset($settings['enabled']) ? $settings['enabled'] : true;
		if ($enable) {
			$this->_eventManager->attach($component);
		}
		$this->_loaded[$alias] = $component;
		return $this->_loaded[$alias];
	}

/**
 * Get the loaded components list, or get the component instance at a given name.
 *
 * @param null|string $name The component name to get or null.
 * @return array|Helper Either a list of components names, or a loaded component.
 */
	public function loaded($name = null) {
		if (!empty($name)) {
			return isset($this->_loaded[$name]);
		}
		return array_keys($this->_loaded);
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
		return null;
	}

/**
 * Provide isset access to _loaded
 *
 * @param string $name Name of object being checked.
 * @return boolean
 */
	public function __isset($name) {
		return isset($this->_loaded[$name]);
	}

}
