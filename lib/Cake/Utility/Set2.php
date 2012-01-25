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
		while (($key = array_shift($parts)) !== null) {
			if (is_array($data) && isset($data[$key])) {
				$data =& $data[$key];
			} else {
				return null;
			}
			
		}
		return $data;
	}

/**
 * Gets the values from an array matching the $path expression.
 * The path expression is a dot separated expression, that can contain a set
 * of patterns and expressions:
 *
 * - `{n}` Matches any numeric key.
 * - `{s}` Matches any string key.
 * - `[id]` Matches elements with an `id` index.
 * - `[id>2]` Matches elements that have an `id` index greater than 2.  
 *
 * There are a number of attribute operators:
 *
 *  - `=`, `!=` Equality.
 *  - `>`, `<`, `>=`, `<=` Value comparison.
 *  - `=/.../` Regular expression pattern match.
 *
 * Given a set of User array data, from a `$User->find('all')` call:
 *
 * - `1.User.name` Get the name of the user at index 1.
 * - `{n}.User.name` Get the name of every user in the set of users.
 * - `{n}.User[id]` Get the name of every user with an id key.
 * - `{n}.User[id>=2]` Get the name of every user with an id key greater than or equal to 2.
 * - `{n}.User[username=/^paul/]` Get User elements with username containing `^paul`.
 *
 * @param array $data The data to extract from.
 * @param string $path The path to extract.
 * @return array An array of the extracted values.  Returns an empty array 
 *   if there are no matches.
 */
	public static function extract(array $data, $path) {
		if (empty($path)) {
			return $data;
		}

		// Simple paths.
		if (!preg_match('/[{\[]/', $path)) {
			return (array) self::get($data, $path);
		}

		return self::_traverse($data, $path, function ($value) {
			return $value;
		});
	}

/**
 * Traverses $data for $path.  $callback is called for each terminal element.
 * The results of all the callbacks are returned.
 *
 * @param array $data The data to traverse.
 * @param string $path The set path to walk.
 * @param callable $callback to call on the result set.
 * @return array Results of the callback mapped over the leaf nodes of the path expression.
 */
	protected static function _traverse(array &$data, $path, $callback) {
		$result = array();

		if (strpos('[', $path) === false) {
			$tokens = explode('.', $path);
		} else {
			$tokens = String::tokenize($path, '.', '[', ']');
		}

		$_key = '__set_item__';

		$context = array($_key => array($data));

		do  {
			$token = array_shift($tokens);
			$next = array();

			$conditions = false;
			$position = strpos($token, '[');
			if ($position !== false) {
				$conditions = substr($token, $position);
				$token = substr($token, 0, $position);
			}

			foreach ($context[$_key] as $item) {
				if ($token === '{n}') {
					// any numeric key
					foreach ($item as $k => $v) {
						if (is_numeric($k)) {
							$next[] = $v;
						}
					}
				} elseif ($token === '{s}') {
					// any string key
					foreach ($item as $k => $v) {
						if (is_string($k)) {
							$next[] = $v;
						}
					}
				} elseif (is_numeric($token)) {
					// numeric keys like 0, 1, 2
					foreach ($item as $k => $v) {
						if ($k == $token) {
							$next[] = $v;
						}
					}
				} else {
					// bare string key
					foreach ($item as $k => $v) {
						// index or key match.
						if ($k === $token) {
							$next[] = $v;
						}
					}
				}
			}
	
			// Filter for attributes.
			if ($conditions) {
				$filter = array();
				foreach ($next as $item) {
					if (self::_matches($item, $conditions)) {
						$filter[] = $item;
					}
				}
				$next = $filter;
			}

			$context = array($_key => $next);

		} while (!empty($tokens));

		return array_map($callback, $context[$_key]);
	}

/**
 * Checks whether or not $data matches the selector
 *
 * @param array $data Array of data to match.
 * @param string $selector The selector to match.
 * @return boolean Fitness of expression.
 */ 
	protected static function _matches(array $data, $selector) {
		preg_match_all(
			'/(\[ (?<attr>[^=><!]+?) (\s* (?<op>[><!]?[=]|[><]) \s* (?<val>[^\]]+) )? \])/x',
			$selector,
			$conditions,
			PREG_SET_ORDER
		);

		$ok = true;
		while ($ok) {
			if (empty($conditions)) {
				break;
			}
			$cond = array_shift($conditions);
			$attr = $cond['attr'];
			$op = isset($cond['op']) ? $cond['op'] : null;
			$val = isset($cond['val']) ? $cond['val'] : null;

			// Presence test.
			if (empty($op) && empty($val) && !isset($data[$attr])) {
				return false;
			}

			// Empty attribute = fail.
			if (!isset($data[$attr])) {
				return false;
			}

			$prop = isset($data[$attr]) ? $data[$attr] : null;

			// Pattern matches and other operators.
			if ($op === '=' && $val && $val[0] === '/') {
				if (!preg_match($val, $prop)) {
					return false;
				}
			} elseif (
				($op === '=' && $prop != $val) ||
				($op === '!=' && $prop == $val) ||
				($op === '>' && $prop <= $val) ||
				($op === '<' && $prop >= $val) ||
				($op === '>=' && $prop < $val) ||
				($op === '<=' && $prop > $val)
			) {
				return false;
			}

		}
		return true;
	}

	public static function insert(array $data, $path, $values = null) {

	}

	public static function remove(array $data, $path) {

	}

	public static function combine(array $data, $keyPath, $valuePath = null) {

	}

/**
 * Determines if one array contains the exact keys and values of another.
 *
 * @param array $data The data to search through.
 * @param array $needle The values to file in $data
 * @return boolean true if $data contains $needle, false otherwise
 * @link http://book.cakephp.org/2.0/en/core-utility-libraries/set.html#Set::contains
 */
	public static function contains(array $data, array $needle) {
		if (empty($data) || empty($needle)) {
			return false;
		}
		$stack = array();

		$i = 1;
		while (!empty($needle)) {
			$key = key($needle);
			$val = $needle[$key];
			unset($needle[$key]);

			if (isset($data[$key]) && is_array($val)) {
				$next = $data[$key];
				unset($data[$key]);

				if (!empty($val)) {
					$stack[] = array($val, $next);
				}
			} elseif (!isset($data[$key]) || $data[$key] != $val) {
				return false;
			}

			if (empty($needle) && !empty($stack)) {
				list($needle, $data) = array_pop($stack);
			}
		}
		return true;
	}

	public static function check(array $data, $path) {

	}

/**
 * Recursively filters empty elements out of a route array, excluding '0'.
 *
 * @param array $data Either an array to filter, or value when in callback
 * @return array Filtered array
 * @link http://book.cakephp.org/2.0/en/core-utility-libraries/set.html#Set::filter
 */
	public static function filter(array $data) {
		foreach ($data as $k => $v) {
			if (is_array($v)) {
				$data[$k] = Set2::filter($v);
			}
		}
		return array_filter($data, array('Set2', '_filter'));
	}

/**
 * Callback function for filtering.
 *
 * @param array $var Array to filter.
 * @return boolean
 */
	protected static function _filter($var) {
		if ($var === 0 || $var === '0' || !empty($var)) {
			return true;
		}
		return false;
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
 * Checks to see if all the values in the array are numeric
 *
 * @param array $array The array to check.
 * @return boolean true if values are numeric, false otherwise
 * @link http://book.cakephp.org/2.0/en/core-utility-libraries/set.html#Set::numeric
 */
	public static function numeric(array $data) {
		if (empty($data)) {
			return false;
		}
		$values = array_values($data);
		$str = implode('', $values);
		return (bool) ctype_digit($str);
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
