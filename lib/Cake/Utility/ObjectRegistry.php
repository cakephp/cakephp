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
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Utility;

/**
 * Acts as a registry/factory for objects.
 *
 * Provides registry & factory functionality for object types. Used
 * as a super class for various composition based re-use features in CakePHP.
 *
 * Each subclass needs to implement its own load() functionality. Replaces ObjectCollection
 * in previous versions of CakePHP.
 *
 * @since CakePHP 3.0
 * @see Cake\Controller\ComponentRegistry
 * @see Cake\View\HelperRegistry
 * @see Cake\Console\TaskRegistry
 */
abstract class ObjectRegistry {

/**
 * Map of loaded objects.
 *
 * @var array
 */
	protected $_loaded = [];

/**
 * Load instances for this registry.
 *
 * Overridden in subclasses.
 *
 * @param string $name The name/class of the object to load.
 * @param array $settings Additional settings to use when loading the object.
 * @return mixed.
 */
	abstract public function load($name, $settings = []);

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
 * @return boolean
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
			$options = array();
			if (!is_int($i)) {
				$options = (array)$objectName;
				$objectName = $i;
			}
			list(, $name) = pluginSplit($objectName);
			$normal[$name] = array('class' => $objectName, 'settings' => $options);
		}
		return $normal;
	}

}
