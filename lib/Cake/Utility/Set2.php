<?php
/**
 * PHP 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2011, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2011, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       Cake.Utility
 * @since         CakePHP(tm) v 1.2.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

App::uses('String', 'Utility');

/**
 * Library of array functions for manipulating and extracting data
 * from arrays or 'sets' of data.
 *
 * `Set2` provides an improved interface and more consistent and 
 * predictable set of features over `Set`.  While it lacks the spotty
 * support for pseudo Xpath, its more fully feature dot notation provides
 * the same utility.
 *
 * @package       Cake.Utility
 */
class Set2 {

/**
 * Get a single value specified by $path out of $data.
 * Does not support the full dot notation feature set, 
 * but is faster for simple operations.
 *
 * @param array $data Array of data to operate on.
 * @param string $path The path being searched for.
 * @return mixed The value fetched from the array, or null.
 */
	public static function get(array $data, $path) {
		if (empty($data) || empty($path)) {
			return null;
		}
		$parts = explode('.', $path);
		while ($key = array_shift($parts)) {
			if (isset($data[$key])) {
				$data =& $data[$key];
			} else {
				return null;
			}
		}
		return $data;
	}

	public static function extract(array $data, $path) {

	}

	public static function insert(array $data, $path, $values = null) {

	}

	public static function remove(array $data, $path) {

	}

	public static function combine(array $data, $keyPath, $valuePath = null) {

	}

	public static function contains(array $data, $needle) {

	}

	public static function check(array $data, $path) {

	}

	public static function filter(array $data) {
	
	}

/**
 * Collapses a multi-dimensional array into a single dimension, using a delimited array path for
 * each array element's key, i.e. array(array('Foo' => array('Bar' => 'Far'))) becomes
 * array('0.Foo.Bar' => 'Far').)
 *
 * @param array $data Array to flatten
 * @param string $separator String used to separate array key elements in a path, defaults to '.'
 * @return array
 * @link http://book.cakephp.org/2.0/en/core-utility-libraries/set.html#Set::flatten
 */
	public static function flatten(array $data, $separator = '.') {
		$result = array();
		$stack = array();
		$path = null;

		reset($data);
		while (!empty($data)) {
			$key = key($data);
			$element = $data[$key];
			unset($data[$key]);

			if (is_array($element)) {
				if (!empty($data)) {
					$stack[] = array($data, $path);
				}
				$data = $element;
				$path .= $key . $separator;
			} else {
				$result[$path . $key] = $element;
			}

			if (empty($data) && !empty($stack)) {
				list($data, $path) = array_pop($stack);
			}
		}
		return $result;
	}

/**
 * This function can be thought of as a hybrid between PHP's `array_merge` and `array_merge_recursive`.
 *
 * The difference between this method and the built-in ones, is that if an array key contains another array, then 
 * Set2::merge() will behave in a recursive fashion (unlike `array_merge`).  But it will not act recursively for
 * keys that contain scalar values (unlike `array_merge_recursive`).
 *
 * Note: This function will work with an unlimited amount of arguments and typecasts non-array parameters into arrays.
 *
 * @param array $data Array to be merged
 * @param mixed $merge Array to merge with. The argument and all trailing arguments will be array cast when merged
 * @return array Merged array
 * @link http://book.cakephp.org/2.0/en/core-utility-libraries/set.html#Set::merge
 */
	public static function merge(array $data, $merge) {
		$args = func_get_args();
		$return = current($args);

		while (($arg = next($args)) !== false) {
			foreach ((array)$arg as $key => $val)	 {
				if (!empty($return[$key]) && is_array($return[$key]) && is_array($val)) {
					$return[$key] = self::merge($return[$key], $val);
				} elseif (is_int($key)) {
					$return[] = $val;
				} else {
					$return[$key] = $val;
				}
			}
		}
		return $return;
	}

/**
 * Counts the dimensions of an array. 
 * Only considers the dimension of the first element in the array.
 *
 * If you have an un-even or hetrogenous array, consider using Set2::maxDimensions() 
 * to get the dimensions of the array.
 *
 * @param array $array Array to count dimensions on
 * @return integer The number of dimensions in $data
 * @link http://book.cakephp.org/2.0/en/core-utility-libraries/set.html#Set::countDim
 */
	public static function dimensions(array $data) {
		if (empty($data)) {
			return 0;
		}
		reset($data);
		$depth = 1;
		while ($elem = array_shift($data)) {
			if (is_array($elem)) {
				$depth += 1;
				$data =& $elem;
			} else {
				break;
			}
		}
		return $depth;
	}

/**
 * Counts the dimensions of *all* array elements. Useful for finding the maximum
 * number of dimensions in a mixed array.
 * 
 * @param array $data Array to count dimensions on
 * @return integer The maximum number of dimensions in $data
 * @link http://book.cakephp.org/2.0/en/core-utility-libraries/set.html#Set::countDim
 */
	public static function maxDimensions(array $data) {
		$depth = array();
		if (is_array($data) && reset($data) !== false) {
			foreach ($data as $value) {
				$depth[] = Set2::dimensions((array)$value) + 1;
			}
		}
		return max($depth);
	}

/**
 * Map a callback across all elements in a set.
 * Can be provided a path to only modify slices of the set.
 *
 */
	public static function map(array $data, $path, $function = null) {

	}

	public static function sort(array $data, $path, $dir) {

	}

/**
 * Computes the difference between two complex arrays.
 * This method differs from the built-in array_diff() in that it will preserve keys
 * and work on multi-dimensional arrays.
 *
 * @param mixed $data First value
 * @param mixed $data2 Second value
 * @return array Returns the key => value pairs that are not common in $data and $data2
 *    The expression for this function is ($data - $data2) + ($data2 - ($data - $data2))
 * @link http://book.cakephp.org/2.0/en/core-utility-libraries/set.html#Set::diff
 */
	public static function diff(array $data, $data2) {
		if (empty($data)) {
			return (array)$data2;
		}
		if (empty($data2)) {
			return (array)$data;
		}
		$intersection = array_intersect_key($data, $data2);
		while (($key = key($intersection)) !== null) {
			if ($data[$key] == $data2[$key]) {
				unset($data[$key]);
				unset($data2[$key]);
			}
			next($intersection);
		}
		return $data + $data2;
	}

/**
 * Normalizes an array, and converts it to a standard format.
 *
 * @param mixed $data List to normalize
 * @param boolean $assoc If true, $data will be converted to an associative array.
 * @return array
 * @link http://book.cakephp.org/2.0/en/core-utility-libraries/set.html#Set::normalize
 */
	public static function normalize(array $data, $assoc = true) {
		$keys = array_keys($data);
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
					$newList[$data[$keys[$i]]] = null;
				} else {
					$newList[$keys[$i]] = $data[$keys[$i]];
				}
			}
			$data = $newList;
		}
		return $data;
	}

}
