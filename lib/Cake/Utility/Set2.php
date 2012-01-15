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

	public static function flatten(array $data) {

	}

	public static function merge(array $data, $merge) {

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

	public static function diff(array $data, $data2) {

	}

	public static function normalize(array $data, $assoc = true) {

	}

}
