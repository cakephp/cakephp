<?php
/* SVN FILE: $Id$ */
/**
 * Library of array functions for Cake.
 *
 * PHP versions 4 and 5
 *
 * CakePHP :  Rapid Development Framework <http://www.cakephp.org/>
 * Copyright (c)	2006, Cake Software Foundation, Inc.
 *								1785 E. Sahara Avenue, Suite 490-204
 *								Las Vegas, Nevada 89104
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @filesource
 * @copyright		Copyright (c) 2006, Cake Software Foundation, Inc.
 * @link				http://www.cakefoundation.org/projects/info/cakephp CakePHP Project
 * @package			cake
 * @subpackage		cake.cake.libs
 * @since			CakePHP v 1.2.0
 * @version			$Revision$
 * @modifiedby		$LastChangedBy$
 * @lastmodified	$Date$
 * @license			http://www.opensource.org/licenses/mit-license.php The MIT License
 */
/**
 * Class used for manipulation of arrays.
 *
 * Long description for class
 *
 * @package		cake
 * @subpackage	cake.cake.libs
 */
class Set extends Object {
/**
 * Value of the Set object.
 *
 * @var array
 * @access public
 */
	var $__value = array();
/**
 * Constructor. Defaults to an empty array.
 *
 * @access public
 */
	function __construct() {
		if (func_num_args() == 1 && is_array(func_get_arg(0))) {
			$this->__value = func_get_arg(0);
		} else {
			$this->__value = func_get_args();
		}
	}
/**
 * Returns the contents of the Set object
 *
 * @access public
 */
	function get() {
		return $this->__value;
	}
/**
 * Merges the contents of the array object with $array
 *
 * @param mixed $array An array, another Set object, or a value to be appended
 * @return array
 * @access public
 */
	function merge($array = null, $array2 = null) {
		if ($array2 != null && is_array($array2)) {
			return array_merge_recursive($array, $array2);
		}
		if ($array == null) {
			$array = $this->__value;
		} elseif (is_object($array) && (is_a($array, 'set') || is_a($array, 'Set'))) {
			$array = $array->get();
		} elseif (is_object($array)) {
			// Throw an error
		} elseif (!is_array($array)) {
			$array = array($array);
		}
		$this->__value = array_merge_recursive($this->__value, $array);
		return $this->__value;
	}
/**
 * Maps the contents of the Set object to an object hierarchy
 *
 * @param string $class A class name of the type of object to map to
 * @return object
 * @access public
 */
	function map($class = 'stdClass', $tmp = 'stdClass') {
		if (is_array($class)) {
			$val = $class;
			$class = $tmp;
		} elseif (is_a($this, 'set') || is_a($this, 'Set')) {
			$val = $this->get();
		}
		
		if (empty($val) || $val == null) {
			return null;
		}
		return Set::__map($val, $class);
	}

	function __map($value, $class) {
		if (Set::numeric(array_keys($value))) {
			$ret = array();
			foreach ($value as $key => $val) {
				$ret[$key] = Set::__map($val, $class);
			}
		} else {
			$ret = new $class;
		}

		$keys = array_keys($value);
		foreach ($value as $key => $val) {
			if (!is_numeric($key) && strlen($key) > 1) {
				if ($key{0} == strtoupper($key{0}) && $key{1} == strtolower($key{1})) {
					if ($key == $keys[0]) {
						$ret = Set::__map($val, $class);
					} else {
						$ret->{$key} = Set::__map($val, $class);
					}
				} else {
					$ret->{$key} = $val;
				}
			}
		}
		return $ret;
	}
/**
 * Checks to see if all the values in the array are numeric
 *
 * @param array $array The array to check.  If null, the value of the current Set object
 * @return boolean
 * @access public
 */
	function numeric($array = null) {
		if ($array == null && (is_a($this, 'set') || is_a($this, 'Set'))) {
			$array = $this->get();
		}

		$numeric = true;
		$keys = array_keys($array);
		$count = count($keys);
		for ($i = 0; $i < $count; $i++) {
			if (!is_numeric($array[$keys[$i]])) {
				$numeric = false;
				break;
			}
		}
		return $numeric;
	}
}

?>