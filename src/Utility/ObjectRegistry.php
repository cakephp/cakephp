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
 * @since         3.0.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Utility;

/**
 * Acts as a registry/factory for objects.
 *
 * Provides registry & factory functionality for object types. Used
 * as a super class for various composition based re-use features in CakePHP.
 *
 * Each subclass needs to implement the various abstract methods to complete
 * the template method load().
 *
 * @see \Cake\Controller\ComponentRegistry
 * @see \Cake\View\HelperRegistry
 * @see \Cake\Console\TaskRegistry
 */
abstract class ObjectRegistry {

/**
 * Map of loaded objects.
 *
 * @var array
 */
	protected $_loaded = [];

/**
 * The event manager to bind components to.
 *
 * @var \Cake\Event\EventManager
 */
	protected $_eventManager;

/**
 * Loads/constructs a object instance.
 *
 * Will return the instance in the registry if it already exists.
 * If a subclass provides event support, you can use `$config['enabled'] = false`
 * to exclude constructed objects from being registered for events.
 *
 * Using Cake\Controller\Controller::$components as an example. You can alias
 * an object by setting the 'className' key, i.e.,
 *
 * {{{
 * public $components = [
 *   'Email' => [
 *     'className' => '\App\Controller\Component\AliasedEmailComponent'
 *   ];
 * ];
 * }}}
 *
 * All calls to the `Email` component would use `AliasedEmail` instead.
 *
 * @param string $objectName The name/class of the object to load.
 * @param array $config Additional settings to use when loading the object.
 * @return mixed
 */
	public function load($objectName, $config = []) {
		list($plugin, $name) = pluginSplit($objectName);
		if (isset($this->_loaded[$name])) {
			return $this->_loaded[$name];
		}
		if (is_array($config) && isset($config['className'])) {
			$className = $this->_resolveClassName($config['className']);
		}
		if (!isset($className)) {
			$className = $this->_resolveClassName($objectName);
		}
		if (!$className) {
			$this->_throwMissingClassError($objectName, substr($plugin, 0, -1));
		}
		$instance = $this->_create($className, $name, $config);
		$this->_loaded[$name] = $instance;
		return $instance;
	}

/**
 * Should resolve the classname for a given object type.
 *
 * @param string $class The class to resolve.
 * @return string|false The resolved name or false for failure.
 */
	abstract protected function _resolveClassName($class);

/**
 * Throw an exception when the requested object name is missing.
 *
 * @param string $class The class that is missing.
 * @param string $plugin The plugin $class is missing from.
 * @throws \Cake\Error\Exception
 */
	abstract protected function _throwMissingClassError($class, $plugin);

/**
 * Create an instance of a given classname.
 *
 * This method should construct and do any other initialization logic
 * required.
 *
 * @param string $class The class to build.
 * @param string $alias The alias of the object.
 * @param array $config The Configuration settings for construction
 * @return mixed
 */
	abstract protected function _create($class, $alias, $config);

/**
 * Get the loaded object list, or get the object instance at a given name.
 *
 * @param null|string $name The object name to get or null.
 * @return array|Helper Either a list of object names, or a loaded object.
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
 * @return bool
 */
	public function __isset($name) {
		return isset($this->_loaded[$name]);
	}

/**
 * Normalizes an object array, creates an array that makes lazy loading
 * easier
 *
 * @param array $objects Array of child objects to normalize.
 * @return array Array of normalized objects.
 */
	public function normalizeArray($objects) {
		$normal = array();
		foreach ($objects as $i => $objectName) {
			$config = array();
			if (!is_int($i)) {
				$config = (array)$objectName;
				$objectName = $i;
			}
			list(, $name) = pluginSplit($objectName);
			$normal[$name] = array('class' => $objectName, 'config' => $config);
		}
		return $normal;
	}

/**
 * Clear loaded instances in the registry.
 *
 * If the registry subclass has an event manager, the objects will be detached from events as well.
 *
 * @return void
 */
	public function reset() {
		foreach (array_keys($this->_loaded) as $name) {
			$this->unload($name);
		}
	}

/**
 * Set an object directly into the registry by name.
 *
 * If this collection implements events, the passed object will
 * be attached into the event manager
 *
 * @param string $objectName The name of the object to set in the registry.
 * @param object $object instance to store in the registry
 * @return void
 */
	public function set($objectName, $object) {
		list($plugin, $name) = pluginSplit($objectName);
		$this->unload($objectName);
		if (isset($this->_eventManager)) {
			$this->_eventManager->attach($object);
		}
		$this->_loaded[$name] = $object;
	}

/**
 * Remove an object from the registry.
 *
 * If this registry has an event manager, the object will be detached from any events as well.
 *
 * @param string $objectName The name of the object to remove from the registry.
 * @return void
 */
	public function unload($objectName) {
		if (empty($this->_loaded[$objectName])) {
			return;
		}
		$object = $this->_loaded[$objectName];
		if (isset($this->_eventManager)) {
			$this->_eventManager->detach($object);
		}
		unset($this->_loaded[$objectName]);
	}

}
