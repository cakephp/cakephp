<?php
/* SVN FILE: $Id$ */
/**
 * Class collections.
 *
 * A repository for class objects, each registered with a key.
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
 * @subpackage    cake.cake.libs
 * @since         CakePHP(tm) v 0.9.2
 * @version       $Revision$
 * @modifiedby    $LastChangedBy$
 * @lastmodified  $Date$
 * @license       http://www.opensource.org/licenses/mit-license.php The MIT License
 */
/**
 * Class Collections.
 *
 * A repository for class objects, each registered with a key.
 * If you try to add an object with the same key twice, nothing will come of it.
 * If you need a second instance of an object, give it another key.
 *
 * @package       cake
 * @subpackage    cake.cake.libs
 */
class ClassRegistry {
/**
 * Names of classes with their objects.
 *
 * @var array
 * @access private
 */
	var $__objects = array();
/**
 * Names of class names mapped to the object in the registry.
 *
 * @var array
 * @access private
 */
	var $__map = array();
/**
 * Default constructor parameter settings, indexed by type
 *
 * @var array
 * @access private
 */
	var $__config = array();
/**
 * Return a singleton instance of the ClassRegistry.
 *
 * @return ClassRegistry instance
 * @access public
 */
	function &getInstance() {
		static $instance = array();
		if (!$instance) {
			$instance[0] =& new ClassRegistry();
		}
		return $instance[0];
	}
/**
 * Loads a class, registers the object in the registry and returns instance of the object.
 *
 * Examples
 * Simple Use: Get a Post model instance ```ClassRegistry::init('Post');```
 * 
 * Exapanded: ```array('class' => 'ClassName', 'alias' => 'AliasNameStoredInTheRegistry', 'type' => 'TypeOfClass');```
 * 
 * Model Classes can accept optional ```array('id' => $id, 'table' => $table, 'ds' => $ds, 'alias' => $alias);```
 * 
 * When $class is a numeric keyed array, multiple class instances will be stored in the registry,
 *  no instance of the object will be returned
 * {{{
 * array(
 *		array('class' => 'ClassName', 'alias' => 'AliasNameStoredInTheRegistry', 'type' => 'TypeOfClass'),
 *		array('class' => 'ClassName', 'alias' => 'AliasNameStoredInTheRegistry', 'type' => 'TypeOfClass'),
 *		array('class' => 'ClassName', 'alias' => 'AliasNameStoredInTheRegistry', 'type' => 'TypeOfClass')
 * );
 * }}}
 * @param mixed $class as a string or a single key => value array instance will be created,
 *  stored in the registry and returned.
 * @param string $type TypeOfClass
 * @return object instance of ClassName
 * @access public
 * @static
 */
	function &init($class, $type = null) {
		$_this =& ClassRegistry::getInstance();
		$id = $false = false;
		$true = true;

		if (!$type) {
			$type = 'Model';
		}

		if (is_array($class)) {
			$objects = $class;
			if (!isset($class[0])) {
				$objects = array($class);
			}
		} else {
			$objects = array(array('class' => $class));
		}
		$defaults = isset($_this->__config[$type]) ? $_this->__config[$type] : array();
		$count = count($objects);

		foreach ($objects as $key => $settings) {
			if (is_array($settings)) {
				$plugin = $pluginPath = null;
				$settings = array_merge($defaults, $settings);
				$class = $settings['class'];

				if (strpos($class, '.') !== false) {
					list($plugin, $class) = explode('.', $class);
					$pluginPath = $plugin . '.';
				}

				if (empty($settings['alias'])) {
					$settings['alias'] = $class;
				}
				$alias = $settings['alias'];

				if ($model =& $_this->__duplicate($alias, $class)) {
					$_this->map($alias, $class);
					return $model;
				}

				if (class_exists($class) || App::import($type, $pluginPath . $class)) {
					${$class} =& new $class($settings);
				} elseif ($type === 'Model') {
					if ($plugin && class_exists($plugin . 'AppModel')) {
						$appModel = $plugin . 'AppModel';
					} else {
						$appModel = 'AppModel';
					}
					$settings['name'] = $class;
					${$class} =& new $appModel($settings);
				}

				if (!isset(${$class})) {
					trigger_error(sprintf(__('(ClassRegistry::init() could not create instance of %1$s class %2$s ', true), $class, $type), E_USER_WARNING);
					return $false;
				}

				if ($type !== 'Model') {
					$_this->addObject($alias, ${$class});
				} else {
					$_this->map($alias, $class);
				}
			} elseif (is_numeric($settings)) {
				trigger_error(__('(ClassRegistry::init() Attempted to create instance of a class with a numeric name', true), E_USER_WARNING);
				return $false;
			}
		}

		if ($count > 1) {
			return $true;
		}
		return ${$class};
	}
/**
 * Add $object to the registry, associating it with the name $key.
 *
 * @param string $key	Key for the object in registry
 * @param mixed $object	Object to store
 * @return boolean True if the object was written, false if $key already exists
 * @access public
 * @static
 */
	function addObject($key, &$object) {
		$_this =& ClassRegistry::getInstance();
		$key = Inflector::underscore($key);
		if (!isset($_this->__objects[$key])) {
			$_this->__objects[$key] =& $object;
			return true;
		}
		return false;
	}
/**
 * Remove object which corresponds to given key.
 *
 * @param string $key	Key of object to remove from registry
 * @return void
 * @access public
 * @static
 */
	function removeObject($key) {
		$_this =& ClassRegistry::getInstance();
		$key = Inflector::underscore($key);
		if (isset($_this->__objects[$key])) {
			unset($_this->__objects[$key]);
		}
	}
/**
 * Returns true if given key is present in the ClassRegistry.
 *
 * @param string $key Key to look for
 * @return boolean true if key exists in registry, false otherwise
 * @access public
 * @static
 */
	function isKeySet($key) {
		$_this =& ClassRegistry::getInstance();
		$key = Inflector::underscore($key);
		if (isset($_this->__objects[$key])) {
			return true;
		} elseif (isset($_this->__map[$key])) {
			return true;
		}
		return false;
	}
/**
 * Get all keys from the registry.
 *
 * @return array Set of keys stored in registry
 * @access public
 * @static
 */
	function keys() {
		$_this =& ClassRegistry::getInstance();
		return array_keys($_this->__objects);
	}
/**
 * Return object which corresponds to given key.
 *
 * @param string $key Key of object to look for
 * @return mixed Object stored in registry or boolean false if the object does not exist.
 * @access public
 * @static
 */
	function &getObject($key) {
		$_this =& ClassRegistry::getInstance();
		$key = Inflector::underscore($key);
		$return = false;
		if (isset($_this->__objects[$key])) {
			$return =& $_this->__objects[$key];
		} else {
			$key = $_this->__getMap($key);
			if (isset($_this->__objects[$key])) {
				$return =& $_this->__objects[$key];
			}
		}
		return $return;
	}
/**
 * Sets the default constructor parameter for an object type
 *
 * @param string $type Type of object.  If this parameter is omitted, defaults to "Model"
 * @param array $param The parameter that will be passed to object constructors when objects
 *                      of $type are created
 * @return mixed Void if $param is being set.  Otherwise, if only $type is passed, returns
 *               the previously-set value of $param, or null if not set.
 * @access public
 * @static
 */
	function config($type, $param = array()) {
		$_this =& ClassRegistry::getInstance();

		if (empty($param) && is_array($type)) {
			$param = $type;
			$type = 'Model';
		} elseif (is_null($param)) {
			unset($_this->__config[$type]);
		} elseif (empty($param) && is_string($type)) {
			return isset($_this->__config[$type]) ? $_this->__config[$type] : null;
		}
		$_this->__config[$type] = $param;
	}
/**
 * Checks to see if $alias is a duplicate $class Object
 *
 * @param string $alias
 * @param string $class
 * @return boolean
 * @access private
 * @static
 */
	function &__duplicate($alias,  $class) {
		$duplicate = false;
		if ($this->isKeySet($alias)) {
			$model =& $this->getObject($alias);
			if (is_object($model) && (is_a($model, $class) || $model->alias === $class)) {
				$duplicate =& $model;
			}
			unset($model);
		}
		return $duplicate;
	}
/**
 * Add a key name pair to the registry to map name to class in the registry.
 *
 * @param string $key Key to include in map
 * @param string $name Key that is being mapped
 * @access public
 * @static
 */
	function map($key, $name) {
		$_this =& ClassRegistry::getInstance();
		$key = Inflector::underscore($key);
		$name = Inflector::underscore($name);
		if (!isset($_this->__map[$key])) {
			$_this->__map[$key] = $name;
		}
	}
/**
 * Get all keys from the map in the registry.
 *
 * @return array Keys of registry's map
 * @access public
 * @static
 */
	function mapKeys() {
		$_this =& ClassRegistry::getInstance();
		return array_keys($_this->__map);
	}
/**
 * Return the name of a class in the registry.
 *
 * @param string $key Key to find in map
 * @return string Mapped value
 * @access private
 * @static
 */
	function __getMap($key) {
		if (isset($this->__map[$key])) {
			return $this->__map[$key];
		}
	}
/**
 * Flushes all objects from the ClassRegistry.
 *
 * @return void
 * @access public
 * @static
 */
	function flush() {
		$_this =& ClassRegistry::getInstance();
		$_this->__objects = array();
		$_this->__map = array();
	}
}
?>