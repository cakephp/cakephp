<?php
/* SVN FILE: $Id$ */
/**
 * Class collections.
 *
 * A repository for class objects, each registered with a key.
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
 * @subpackage		cake.cake.libs
 * @since			CakePHP(tm) v 0.9.2
 * @version			$Revision$
 * @modifiedby		$LastChangedBy$
 * @lastmodified	$Date$
 * @license			http://www.opensource.org/licenses/mit-license.php The MIT License
 */
/**
 * Class Collections.
 *
 * A repository for class objects, each registered with a key.
 * If you try to add an object with the same key twice, nothing will come of it.
 * If you need a second instance of an object, give it another key.
 *
 * @package		cake
 * @subpackage	cake.cake.libs
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
 * Add $object to the registry, associating it with the name $key.
 *
 * @param string $key	Key for the object in registry
 * @param mixed $object	Object to store
 * @access public
 */
	function addObject($key, &$object) {
		$_this =& ClassRegistry::getInstance();
		$key = Inflector::underscore($key);
		if (array_key_exists($key, $_this->__objects) === false) {
			$_this->__objects[$key] = &$object;
		}
	}
/**
 * Remove object which corresponds to given key.
 *
 * @param string $key	Key of object to remove from registry
 * @access public
 */
	function removeObject($key) {
		$_this =& ClassRegistry::getInstance();
		$key = Inflector::underscore($key);
		if (array_key_exists($key, $_this->__objects) === true) {
			unset($_this->__objects[$key]);
		}
	}
/**
 * Returns true if given key is present in the ClassRegistry.
 *
 * @param string $key Key to look for
 * @return boolean true if key exists in registry, false otherwise
 * @access public
 */
	function isKeySet($key) {
		$_this =& ClassRegistry::getInstance();
		$key = Inflector::underscore($key);
		if(array_key_exists($key, $_this->__objects)) {
			return true;
		} elseif (array_key_exists($key, $_this->__map)) {
			return true;
		}
		return false;
	}
/**
 * Get all keys from the regisrty.
 *
 * @return array Set of keys stored in registry
 * @access public
 */
	function keys() {
		$_this =& ClassRegistry::getInstance();
		return array_keys($_this->__objects);
	}
/**
 * Return object which corresponds to given key.
 *
 * @param string $key Key of object to look for
 * @return mixed Object stored in registry
 * @access public
 */
	function &getObject($key) {
		$_this =& ClassRegistry::getInstance();
		$key = Inflector::underscore($key);

		if(isset($_this->__objects[$key])){
			return $_this->__objects[$key];
		} else {
			$key = $_this->__getMap($key);
			if(isset($_this->__objects[$key])){
				return $_this->__objects[$key];
			}
		}

		$return = false;
		return $return;
	}
/**
 * Add a key name pair to the registry to map name to class in the regisrty.
 *
 * @param string $key Key to include in map
 * @param string $name Key that is being mapped
 * @access public
 */
	function map($key, $name) {
		$_this =& ClassRegistry::getInstance();
		$key = Inflector::underscore($key);
		$name = Inflector::underscore($name);
		if (array_key_exists($key, $_this->__map) === false) {
			$_this->__map[$key] = $name;
		}
	}
/**
 * Get all keys from the map in the regisrty.
 *
 * @return array Keys of registry's map
 * @access public
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
 */
	function __getMap($key) {
		$_this =& ClassRegistry::getInstance();
		if (array_key_exists($key, $_this->__map)) {
			return $_this->__map[$key];
		}
	}
}
?>