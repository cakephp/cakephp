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
 * @return array
 * @access public
 */
	function &get() {
		return $this->value;
	}
/**
 * This function can be thought of as a hybrid between PHP's array_merge and array_merge_recursive. The difference
 * to the two is that if an array key contains another array then the function behaves recursive (unlike array_merge)
 * but does not do if for keys containing strings (unlike array_merge_recursive). See the unit test for more information.
 *
 * Note: This function will work with an unlimited amount of arguments and typecasts non-array parameters into arrays.
 *
 * @param array $arr1 Array to be merged
 * @param array $arr2 Array to merge with
 * @return array Merged array
 * @access public
 */
	function merge($arr1, $arr2 = null) {
		$args = func_get_args();

		if (is_a($this, 'set')) {
			$backtrace = debug_backtrace();
			$previousCall = low($backtrace[1]['class'].'::'.$backtrace[1]['function']);
			if ($previousCall != 'set::merge') {
				$r =& $this->value;
				array_unshift($args, null);
			}
		}
		if (!isset($r)) {
			$r = (array)current($args);
		}

		while (($arg = next($args)) !== false) {
			if (is_a($arg, 'set')) {
				$arg = $arg->get();
			}

			foreach ((array)$arg as $key => $val)	 {
				if (is_array($val) && isset($r[$key]) && is_array($r[$key])) {
					$r[$key] = Set::merge($r[$key], $val);
				} elseif (is_int($key)) {
					$r[] = $val;
				} else {
					$r[$key] = $val;
				}
			}
		}
		return $r;
	}
/**
 * Filters empty elements out of a route array, excluding '0'.
 *
 * @param mixed $var Either an array to filter, or value when in callback
 * @param boolean $isArray Force to tell $var is an array when $var is empty
 * @return mixed Either filtered array, or true/false when in callback
 * @access public
 */
	function filter($var, $isArray = false) {
		if (is_array($var) && (!empty($var) || $isArray)) {
			$set = new Set();
			return array_filter($var, array(&$set, 'filter'));
		} else {
			if ($var === 0 || $var === '0' || !empty($var)) {
				return true;
			} else {
				return false;
			}
		}
	}
/**
 * Pushes the differences in $array2 onto the end of $array
 *
 * @param mixed $array Original array
 * @param mixed $array2 Diferences to push
 * @return array Combined array
 * @access public
 */
	function pushDiff($array = null, $array2 = null) {
		if ($array2 !== null && is_array($array2)) {
			foreach ($array2 as $key => $value) {
				if (!array_key_exists($key, $array)) {
					$array[$key] = $value;
				} else {
					if (is_array($value)) {
						$array[$key] = Set::pushDiff($array[$key], $array2[$key]);
					}
				}
			}
			return $array;
		}

		if (!isset($this->value)) {
			$this->value = array();
		}
		$this->value = Set::pushDiff($this->value, Set::__array($array));
		return $this->value;
	}
/**
 * Maps the contents of the Set object to an object hierarchy
 *
 * @param string $class A class name of the type of object to map to
 * @param string $tmp A temporary class name used as $class if $class is an array
 * @return object Hierarchical object
 * @access public
 */
	function map($class = 'stdClass', $tmp = 'stdClass') {
		if (is_array($class)) {
			$val = $class;
			$class = $tmp;
		} elseif (is_a($this, 'set')) {
			$val = $this->get();
		}

		if (empty($val) || $val == null) {
			return null;
		}
		return Set::__map($val, $class);
	}

/**
 * Get the array value of $array. If $array is null, it will return
 * the current array Set holds. If it is an object of type Set, it
 * will return its value. If it is another object, its object variables.
 * If it is anything else but an array, it will return an array whose first
 * element is $array.
 *
 * @param mixed $array Data from where to get the array.
 * @return array Array from $array.
 * @access private
 */
	function __array($array) {
		if ($array == null) {
			$array = $this->value;
		} elseif (is_object($array) && (is_a($array, 'set'))) {
			$array = $array->get();
		} elseif (is_object($array)) {
			$array = get_object_vars($array);
		} elseif (!is_array($array)) {
			$array = array($array);
		}
		return $array;
	}

/**
 * Maps the given value as an object. If $value is an object,
 * it returns $value. Otherwise it maps $value as an object of
 * type $class, and identity $identity. If $value is not empty,
 * it will be used to set properties of returned object
 * (recursively).
 *
 * @param mixed $value Value to map
 * @param string $class Class name
 * @param string $identity Identity to assign to class
 * @return mixed Mapped object
 * @access private
 */
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
 * @return boolean true if values are numeric, false otherwise
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
 * @param mixed $select Key in $list to return
 * @param mixed $list can be an array or a comma-separated list.
 * @return string the value of the array key or null if no match
 * @access public
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
 * Gets a value from an array or object that maps a given path.
 * The special {n}, as seen in the Model::generateList method, is taken care of here.
 *
 * @param array $data Array from where to extract
 * @param mixed $path As an array, or as a dot-separated string.
 * @return array Extracted data
 * @access public
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

		foreach ($path as $i => $key) {
			if (is_numeric($key) && intval($key) > 0 || $key == '0') {
				if (isset($data[intval($key)])) {
					$data = $data[intval($key)];
				} else {
					return null;
				}
			} elseif ($key == '{n}') {
				foreach ($data as $j => $val) {
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
 * @param mixed $list Where to insert into
 * @param mixed $path A dot-separated string.
 * @param array $data Data to insert
 * @return array
 * @access public
 */
	function insert($list, $path, $data = null) {
		if (empty($data) && is_a($this, 'Set')) {
			$data = $path;
			$path = $list;
			$list =& $this->get();
		}
		if (!is_array($path)) {
			$path = explode('.', $path);
		}
		$_list =& $list;

		foreach ($path as $i => $key) {
			if (is_numeric($key) && intval($key) > 0 || $key == '0') {
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
 * Removes an element from a Set or array as defined by $path.
 *
 * @param mixed $list From where to remove
 * @param mixed $path A dot-separated string.
 * @return array Array with $path removed from its value
 * @access public
 */
	function remove($list, $path = null) {
		if (empty($path) && is_a($this, 'Set')) {
			$path = $list;
			$list =& $this->get();
		}
		if (!is_array($path)) {
			$path = explode('.', $path);
		}
		$_list =& $list;

		foreach ($path as $i => $key) {
			if (is_numeric($key) && intval($key) > 0 || $key == '0') {
				$key = intval($key);
			}
			if ($i == count($path) - 1) {
				unset($_list[$key]);
			} else {
				if (!isset($_list[$key])) {
					return $list;
				}
				$_list =& $_list[$key];
			}
		}

		if (is_a($this, 'Set')) {
			$this->value = $list;
			return $this;
		} else {
			return $list;
		}
	}
/**
 * Checks if a particular path is set in an array
 *
 * @param mixed $data Data to check on
 * @param mixed $path A dot-separated string.
 * @return boolean true if path is found, false otherwise
 * @access public
 */
	function check($data, $path = null) {
		if (empty($path) && is_a($this, 'Set')) {
			$path = $data;
			$data = $this->get();
		}
		if (!is_array($path)) {
			$path = explode('.', $path);
		}

		foreach ($path as $i => $key) {
			if (is_numeric($key) && intval($key) > 0 || $key == '0') {
				$key = intval($key);
			}
			if ($i == count($path) - 1) {
				return isset($data[$key]);
			} else {
				if (!isset($data[$key])) {
					return false;
				}
				$data =& $data[$key];
			}
		}
		return true;
	}
/**
 * Computes the difference between a Set and an array, two Sets, or two arrays
 *
 * @param mixed $val1 First value
 * @param mixed $val2 Second value
 * @return array Computed difference
 * @access public
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
			if (isset($val2[$key]) && $val2[$key] != $val) {
				$out[$key] = $val;
			} elseif (!array_key_exists($key, $val2)) {
				$out[$key] = $val;
			}
		}
		return $out;
	}
/**
 * Determines if two Sets or arrays are equal
 *
 * @param array $val1 First value
 * @param array $val2 Second value
 * @return boolean true if they are equal, false otherwise
 * @access public
 */
	function isEqual($val1, $val2 = null) {
		if ($val2 == null && (is_a($this, 'set') || is_a($this, 'Set'))) {
			$val2 = $val1;
			$val1 = $this->get();
		}

		return ($val1 == $val2);
	}
/**
 * Determines if one Set or array contains the exact keys and values of another.
 *
 * @param array $val1 First value
 * @param array $val2 Second value
 * @return boolean true if $val1 contains $val2, false otherwise
 * @access public
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
 * Counts the dimensions of an array.
 *
 * @param array $array Array to count dimensions on
 * @return int The number of dimensions in $array
 * @access public
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
 * Normalizes a string or array list.
 *
 * @param mixed $list List to normalize
 * @param boolean $assoc If true, $list will be converted to an associative array
 * @param string $sep If $list is a string, it will be split into an array with $sep
 * @param boolean $trim If true, separated strings will be trimmed
 * @return array
 * @access public
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
/**
 * Creates an associative array using a $path1 as the path to build its keys, and optionally
 * $path2 as path to get the values. If $path2 is not specified, all values will be initialized
 * to null (useful for Set::merge). You can optionally group the values by what is obtained when
 * following the path specified in $groupPath.
 *
 * @param array $data Array from where to extract keys and values
 * @param mixed $path1 As an array, or as a dot-separated string.
 * @param mixed $path2 As an array, or as a dot-separated string.
 * @param string $groupPath As an array, or as a dot-separated string.
 * @return array Combined array
 * @access public
 */
	function combine($data, $path1 = null, $path2 = null, $groupPath = null) {
		if (is_a($this, 'set') && is_string($data) && is_string($path1) && is_string($path2)) {
			$groupPath = $path2;
			$path2 = $path1;
			$path1 = $data;
			$data = $this->get();
		} else if (is_a($this, 'set') && is_string($data) && empty($path2)) {
			$path2 = $path1;
			$path1 = $data;
			$data = $this->get();
		}

		if (is_object($data)) {
			$data = get_object_vars($data);
		}

		$keys = Set::extract($data, $path1);

		if (!empty($path2)) {
			$vals = Set::extract($data, $path2);
		} else {
			$count = count($keys);
			for($i=0; $i < $count; $i++) {
				$vals[$i] = null;
			}
		}

		if ($groupPath != null) {
			$group = Set::extract($data, $groupPath);
			if (!empty($group)) {
				$c = count($keys);
				for ($i = 0; $i < $c; $i++) {
					if (!isset($group[$i])) {
						$group[$i] = 0;
					}
					if (!isset($out[$group[$i]])) {
						$out[$group[$i]] = array();
					}
					$out[$group[$i]][$keys[$i]] = $vals[$i];
				}
				return $out;
			}
		}

		return array_combine($keys, $vals);
	}

	function reverse($object) {
		if (is_object($object)) {
			$merge = array();
			if (is_a($object, 'xmlnode') || is_a($object, 'XMLNode')) {
				if ($object->name != Inflector::underscore($this->name)) {
					if (is_object($object->child(Inflector::underscore($this->name)))) {
						$object = $object->child(Inflector::underscore($this->name));
						$object = $object->attributes;
					} else {
						return null;
					}
				}
			} elseif (is_a($object, 'stdclass') || is_a($object, 'stdClass')) {
				$object = get_object_vars($object);
				$keys = array_keys($object);
				$count = count($keys);

				for ($i = 0; $i < $count; $i++) {
					if ($keys[$i] == '__identity__') {
						unset($object[$keys[$i]]);
					} elseif (is_array($object[$keys[$i]])) {
						$keys1 = array_keys($object[$keys[$i]]);
						$count1 = count($keys1);
						for ($ii = 0; $ii < $count1; $ii++) {
							if (is_object($object[$keys[$i]][$keys1[$ii]])) {
								$merge[$keys[$i]][$keys1[$ii]] = Set::reverse($object[$keys[$i]][$keys1[$ii]]);
							} else {
								$merge[$keys[$i]][$keys1[$ii]] = $object[$keys[$i]][$keys1[$ii]];
							}
						}
						unset($object[$keys[$i]]);
					} elseif (is_object($object[$keys[$i]])) {
						$merge[$keys[$i]] = Set::reverse($object[$keys[$i]]);
						unset($object[$keys[$i]]);
					}
				}
			}
			$return = $object;
			if(!empty($merge)) {
				$object = array($object, $merge);
			}
			return $object;
		}
	}
}
?>
