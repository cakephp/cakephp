<?php
/* SVN FILE: $Id$ */
/**
 * Library of array functions for Cake.
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
 * @since			CakePHP(tm) v 1.2.0
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
	var $value = array();
/**
 * Constructor. Defaults to an empty array.
 *
 * @access public
 */
	function __construct() {
		if (func_num_args() == 1 && is_array(func_get_arg(0))) {
			$this->value = func_get_arg(0);
		} else {
			$this->value = func_get_args();
		}
	}
/**
 * Returns the contents of the Set object
 *
 * @access public
 */
	function get() {
		return $this->value;
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
			$array = $this->value;
		} elseif (is_object($array) && (is_a($array, 'set') || is_a($array, 'Set'))) {
			$array = $array->get();
		} elseif (is_object($array)) {
			// Throw an error
		} elseif (!is_array($array)) {
			$array = array($array);
		}
		$this->value = array_merge_recursive($this->value, $array);
		return $this->value;
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

	function __map($value, $class, $identity = null) {

		if (is_object($value)) {
			return $value;
		}

		if (!empty($value) && Set::numeric(array_keys($value))) {
			$ret = array();
			foreach ($value as $key => $val) {
				$ret[$key] = Set::__map($val, $class);
			}
		} else {
			$ret = new $class;
			if ($identity != null) {
				$ret->__identity__ = $identity;
			}
		}

		if (empty($value)) {
			return $ret;
		}

		$keys = array_keys($value);
		foreach ($value as $key => $val) {
			if (!is_numeric($key) && strlen($key) > 1) {
				if ($key{0} == strtoupper($key{0}) && $key{1} == strtolower($key{1}) && (is_array($val) || is_object($val))) {
					if ($key == $keys[0]) {
						$ret = Set::__map($val, $class, $key);
					} else {
						$ret->{$key} = Set::__map($val, $class, $key);
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
/**
 * Return a value from an array list if the key exists.
 *
 * If a comma separated $list is passed arrays are numeric with the key of the first being 0
 * $list = 'no, yes' would translate to  $list = array(0 => 'no', 1 => 'yes');
 *
 * If an array is used, keys can be strings example: array('no' => 0, 'yes' => 1);
 *
 * $list defaults to 0 = no 1 = yes if param is not passed
 *
 * @param mixed $selected
 * @param mixed $list can be an array or a comma-separated list.
 * @return string the value of the array key or null if no match
 */
	function enum($select, $list = null) {
		if (empty($list) && is_a($this, 'Set')) {
			$list = $this->get();
		} elseif (empty($list)) {
			$list = array('no', 'yes');
		}

		$return = null;
		$list = Set::normalize($list, false);

		if (array_key_exists($select, $list)) {
			$return = $list[$select];
		}
		return $return;
	}
/**
 * Gets a value from an array or object.
 * The special {n}, as seen in the Model::generateList method, is taken care of here.
 *
 * @param array $data
 * @param mixed $path	As an array, or as a dot-separated string.
 * @return array
 */
	function extract($data, $path = null) {
		if ($path === null && is_a($this, 'set')) {
			$path = $data;
			$data = $this->get();
		}
		if (is_object($data)) {
			$data = get_object_vars($data);
		}

		if (!is_array($path)) {
			if (strpos($path, '/') !== 0 && strpos($path, './') === false) {
				$path = explode('.', $path);
			} else {
			}
		}
		$tmp = array();
		if (!is_array($path) || empty($path)) {
			return null;
		}

		foreach($path as $i => $key) {
			if (intval($key) > 0 || $key == '0') {
				if (isset($data[intval($key)])) {
					$data = $data[intval($key)];
				} else {
					return null;
				}
			} elseif ($key == '{n}') {
				foreach($data as $j => $val) {
					$tmp[] = Set::extract($val, array_slice($path, $i + 1));
				}
				return $tmp;
			} else {
				if (isset($data[$key])) {
					$data = $data[$key];
				} else {
					return null;
				}
			}
		}
		return $data;
	}
/**
 * Inserts $data into an array as defined by $path.
 *
 * @param mixed $list
 * @param array $data
 * @param mixed $path A dot-separated string.
 * @return array
 */
	function insert(&$list, $path, $data = null) {
		if (empty($data) && is_a($this, 'Set')) {
			$data = $path;
			$path = $list;
			$list = $this->get();
		}
		if (!is_array($path)) {
			$path = explode('.', $path);
		}
		$_list =& $list;

		foreach($path as $i => $key) {
			if (intval($key) > 0 || $key == '0') {
				$key = intval($key);
			}
			if ($i == count($path) - 1) {
				$_list[$key] = $data;
			} else {
				if (!isset($_list[$key])) {
					$_list[$key] = array();
				}
				$_list =& $_list[$key];
			}
		}
		return $list;
	}
/**
 * Computes the difference between a Set and an array, two Sets, or two arrays
 *
 * @param mixed $val1
 * @param mixed $val2
 * @return array
 */
	function diff($val1, $val2 = null) {
		if ($val2 == null && (is_a($this, 'set') || is_a($this, 'Set'))) {
			$val2 = $val1;
			$val1 = $this->get();
		}
		if (is_object($val2) && (is_a($val2, 'set') || is_a($val2, 'Set'))) {
			$val2 = $val2->get();
		}

		$out = array();
		if (empty($val1)) {
			return (array)$val2;
		} elseif (empty($val2)) {
			return (array)$val1;
		}
		foreach ($val1 as $key => $val) {
			if (!isset($val2[$key]) || $val2[$key] != $val) {
				$out[$key] = $val;
			}
		}
		return $out;
	}
/**
 * Determines if two Sets or arrays are equal
 *
 * @param array $val1
 * @param array $val2
 * @return boolean
 */
	function isEqual($val1, $val2 = null) {
		if ($val2 == null && (is_a($this, 'set') || is_a($this, 'Set'))) {
			$val2 = $val1;
			$val1 = $this->get();
		}
	}
/**
 * Determines if one Set or array contains the exact keys and values of another.
 *
 * @param array $val1
 * @param array $val2
 * @return boolean
 */
	function contains($val1, $val2 = null) {
		if ($val2 == null && is_a($this, 'set')) {
			$val2 = $val1;
			$val1 = $this->get();
		} elseif ($val2 != null && is_object($val2) && is_a($val2, 'set')) {
			$val2 = $val2->get();
		}

		foreach ($val2 as $key => $val) {
			if (is_numeric($key)) {
				if (!in_array($val, $val1)) {
					return false;
				}
			} else {
				if (!isset($val1[$key]) || $val1[$key] != $val) {
					return false;
				}
			}
		}
		return true;
	}
/**
 * Counts the dimensions of an array
 *
 * @param array $array
 * @return int The number of dimensions in $array
 */
	function countDim($array = null) {
		if ($array === null) {
			$array = $this->get();
		} elseif (is_object($array) && is_a($array, 'set')) {
			$array = $array->get();
		}
		if (is_array(reset($array))) {
			$return = Set::countDim(reset($array)) + 1;
		} else {
			$return = 1;
		}
		return $return;
	}
/**
 * Normalizes a string or array list
 *
 * @param mixed $list
 * @param boolean $assoc If true, $list will be converted to an associative array
 * @param string $sep If $list is a string, it will be split into an array with $sep
 * @param boolean $trim If true, separated strings will be trimmed
 * @return array
 */
	function normalize($list, $assoc = true, $sep = ',', $trim = true) {
		if (is_string($list)) {
			$list = explode($sep, $list);
			if ($trim) {
				$list = array_map('trim', $list);
			}
			if ($assoc) {
				return Set::normalize($list);
			}
		} elseif (is_array($list)) {
			$keys = array_keys($list);
			$count = count($keys);
			$numeric = true;

			if (!$assoc) {
				for ($i = 0; $i < $count; $i++) {
					if (!is_int($keys[$i])) {
						$numeric = false;
						break;
					}
				}
			}
			if (!$numeric || $assoc) {
				$newList = array();
				for ($i = 0; $i < $count; $i++) {
					if (is_int($keys[$i])) {
						$newList[$list[$keys[$i]]] = null;
					} else {
						$newList[$keys[$i]] = $list[$keys[$i]];
					}
				}
				$list = $newList;
			}
		}
		return $list;
	}
}

?>