<?php
/**
 * Library of array functions for Cake.
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       Cake.Utility
 * @since         CakePHP(tm) v 1.2.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */

App::uses('String', 'Utility');
App::uses('Hash', 'Utility');

/**
 * Class used for manipulation of arrays.
 *
 * @package       Cake.Utility
 * @deprecated 3.0.0 Will be removed in 3.0. Use Hash instead.
 */
class Set {

/**
 * This function can be thought of as a hybrid between PHP's array_merge and array_merge_recursive. The difference
 * to the two is that if an array key contains another array then the function behaves recursive (unlike array_merge)
 * but does not do if for keys containing strings (unlike array_merge_recursive).
 *
 * Since this method emulates `array_merge`, it will re-order numeric keys. When combined with out of
 * order numeric keys containing arrays, results can be lossy.
 *
 * Note: This function will work with an unlimited amount of arguments and typecasts non-array
 * parameters into arrays.
 *
 * @param array $data Array to be merged
 * @param array $merge Array to merge with
 * @return array Merged array
 * @link http://book.cakephp.org/2.0/en/core-utility-libraries/set.html#Set::merge
 */
	public static function merge($data, $merge = null) {
		$args = func_get_args();
		if (empty($args[1]) && count($args) <= 2) {
			return (array)$args[0];
		}
		if (!is_array($args[0])) {
			$args[0] = (array)$args[0];
		}
		return call_user_func_array('Hash::merge', $args);
	}

/**
 * Filters empty elements out of a route array, excluding '0'.
 *
 * @param array $var Either an array to filter, or value when in callback
 * @return mixed Either filtered array, or true/false when in callback
 * @link http://book.cakephp.org/2.0/en/core-utility-libraries/set.html#Set::filter
 */
	public static function filter(array $var) {
		return Hash::filter($var);
	}

/**
 * Pushes the differences in $array2 onto the end of $array
 *
 * @param array $array Original array
 * @param array $array2 Differences to push
 * @return array Combined array
 * @link http://book.cakephp.org/2.0/en/core-utility-libraries/set.html#Set::pushDiff
 */
	public static function pushDiff($array, $array2) {
		if (empty($array) && !empty($array2)) {
			return $array2;
		}
		if (!empty($array) && !empty($array2)) {
			foreach ($array2 as $key => $value) {
				if (!array_key_exists($key, $array)) {
					$array[$key] = $value;
				} else {
					if (is_array($value)) {
						$array[$key] = Set::pushDiff($array[$key], $array2[$key]);
					}
				}
			}
		}
		return $array;
	}

/**
 * Maps the contents of the Set object to an object hierarchy.
 * Maintains numeric keys as arrays of objects
 *
 * @param string $class A class name of the type of object to map to
 * @param string $tmp A temporary class name used as $class if $class is an array
 * @return object Hierarchical object
 * @link http://book.cakephp.org/2.0/en/core-utility-libraries/set.html#Set::map
 */
	public static function map($class = 'stdClass', $tmp = 'stdClass') {
		if (is_array($class)) {
			$val = $class;
			$class = $tmp;
		}

		if (empty($val)) {
			return null;
		}
		return Set::_map($val, $class);
	}

/**
 * Maps the given value as an object. If $value is an object,
 * it returns $value. Otherwise it maps $value as an object of
 * type $class, and if primary assign _name_ $key on first array.
 * If $value is not empty, it will be used to set properties of
 * returned object (recursively). If $key is numeric will maintain array
 * structure
 *
 * @param array &$array Array to map
 * @param string $class Class name
 * @param bool $primary whether to assign first array key as the _name_
 * @return mixed Mapped object
 */
	protected static function _map(&$array, $class, $primary = false) {
		if ($class === true) {
			$out = new stdClass;
		} else {
			$out = new $class;
		}
		if (is_array($array)) {
			$keys = array_keys($array);
			foreach ($array as $key => $value) {
				if ($keys[0] === $key && $class !== true) {
					$primary = true;
				}
				if (is_numeric($key)) {
					if (is_object($out)) {
						$out = get_object_vars($out);
					}
					$out[$key] = Set::_map($value, $class);
					if (is_object($out[$key])) {
						if ($primary !== true && is_array($value) && Set::countDim($value, true) === 2) {
							if (!isset($out[$key]->_name_)) {
								$out[$key]->_name_ = $primary;
							}
						}
					}
				} elseif (is_array($value)) {
					if ($primary === true) {
						// @codingStandardsIgnoreStart Legacy junk
						if (!isset($out->_name_)) {
							$out->_name_ = $key;
						}
						// @codingStandardsIgnoreEnd
						$primary = false;
						foreach ($value as $key2 => $value2) {
							$out->{$key2} = Set::_map($value2, true);
						}
					} else {
						if (!is_numeric($key)) {
							$out->{$key} = Set::_map($value, true, $key);
							if (is_object($out->{$key}) && !is_numeric($key)) {
								if (!isset($out->{$key}->_name_)) {
									$out->{$key}->_name_ = $key;
								}
							}
						} else {
							$out->{$key} = Set::_map($value, true);
						}
					}
				} else {
					$out->{$key} = $value;
				}
			}
		} else {
			$out = $array;
		}
		return $out;
	}

/**
 * Checks to see if all the values in the array are numeric
 *
 * @param array $array The array to check. If null, the value of the current Set object
 * @return bool true if values are numeric, false otherwise
 * @link http://book.cakephp.org/2.0/en/core-utility-libraries/set.html#Set::numeric
 */
	public static function numeric($array = null) {
		return Hash::numeric($array);
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
 * @param string $select Key in $list to return
 * @param array|string $list can be an array or a comma-separated list.
 * @return string the value of the array key or null if no match
 * @link http://book.cakephp.org/2.0/en/core-utility-libraries/set.html#Set::enum
 */
	public static function enum($select, $list = null) {
		if (empty($list)) {
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
 * Returns a series of values extracted from an array, formatted in a format string.
 *
 * @param array $data Source array from which to extract the data
 * @param string $format Format string into which values will be inserted, see sprintf()
 * @param array $keys An array containing one or more Set::extract()-style key paths
 * @return array An array of strings extracted from $keys and formatted with $format
 * @link http://book.cakephp.org/2.0/en/core-utility-libraries/set.html#Set::format
 */
	public static function format($data, $format, $keys) {
		$extracted = array();
		$count = count($keys);

		if (!$count) {
			return;
		}

		for ($i = 0; $i < $count; $i++) {
			$extracted[] = Set::extract($data, $keys[$i]);
		}
		$out = array();
		$data = $extracted;
		$count = count($data[0]);

		if (preg_match_all('/\{([0-9]+)\}/msi', $format, $keys2) && isset($keys2[1])) {
			$keys = $keys2[1];
			$format = preg_split('/\{([0-9]+)\}/msi', $format);
			$count2 = count($format);

			for ($j = 0; $j < $count; $j++) {
				$formatted = '';
				for ($i = 0; $i <= $count2; $i++) {
					if (isset($format[$i])) {
						$formatted .= $format[$i];
					}
					if (isset($keys[$i]) && isset($data[$keys[$i]][$j])) {
						$formatted .= $data[$keys[$i]][$j];
					}
				}
				$out[] = $formatted;
			}
		} else {
			$count2 = count($data);
			for ($j = 0; $j < $count; $j++) {
				$args = array();
				for ($i = 0; $i < $count2; $i++) {
					if (array_key_exists($j, $data[$i])) {
						$args[] = $data[$i][$j];
					}
				}
				$out[] = vsprintf($format, $args);
			}
		}
		return $out;
	}

/**
 * Implements partial support for XPath 2.0. If $path does not contain a '/' the call
 * is delegated to Set::classicExtract(). Also the $path and $data arguments are
 * reversible.
 *
 * #### Currently implemented selectors:
 *
 * - /User/id (similar to the classic {n}.User.id)
 * - /User[2]/name (selects the name of the second User)
 * - /User[id>2] (selects all Users with an id > 2)
 * - /User[id>2][<5] (selects all Users with an id > 2 but < 5)
 * - /Post/Comment[author_name=john]/../name (Selects the name of all Posts that have at least one Comment written by john)
 * - /Posts[name] (Selects all Posts that have a 'name' key)
 * - /Comment/.[1] (Selects the contents of the first comment)
 * - /Comment/.[:last] (Selects the last comment)
 * - /Comment/.[:first] (Selects the first comment)
 * - /Comment[text=/cakephp/i] (Selects the all comments that have a text matching the regex /cakephp/i)
 * - /Comment/@* (Selects the all key names of all comments)
 *
 * #### Other limitations:
 *
 * - Only absolute paths starting with a single '/' are supported right now
 *
 * **Warning**: Even so it has plenty of unit tests the XPath support has not gone through a lot of
 * real-world testing. Please report Bugs as you find them. Suggestions for additional features to
 * implement are also very welcome!
 *
 * @param string $path An absolute XPath 2.0 path
 * @param array $data An array of data to extract from
 * @param array $options Currently only supports 'flatten' which can be disabled for higher XPath-ness
 * @return mixed An array of matched items or the content of a single selected item or null in any of these cases: $path or $data are null, no items found.
 * @link http://book.cakephp.org/2.0/en/core-utility-libraries/set.html#Set::extract
 */
	public static function extract($path, $data = null, $options = array()) {
		if (is_string($data)) {
			$tmp = $data;
			$data = $path;
			$path = $tmp;
		}
		if (strpos($path, '/') === false) {
			return Set::classicExtract($data, $path);
		}
		if (empty($data)) {
			return array();
		}
		if ($path === '/') {
			return $data;
		}
		$contexts = $data;
		$options += array('flatten' => true);
		if (!isset($contexts[0])) {
			$current = current($data);
			if ((is_array($current) && count($data) < 1) || !is_array($current) || !Set::numeric(array_keys($data))) {
				$contexts = array($data);
			}
		}
		$tokens = array_slice(preg_split('/(?<!=|\\\\)\/(?![a-z-\s]*\])/', $path), 1);

		do {
			$token = array_shift($tokens);
			$conditions = false;
			if (preg_match_all('/\[([^=]+=\/[^\/]+\/|[^\]]+)\]/', $token, $m)) {
				$conditions = $m[1];
				$token = substr($token, 0, strpos($token, '['));
			}
			$matches = array();
			foreach ($contexts as $key => $context) {
				if (!isset($context['trace'])) {
					$context = array('trace' => array(null), 'item' => $context, 'key' => $key);
				}
				if ($token === '..') {
					if (count($context['trace']) === 1) {
						$context['trace'][] = $context['key'];
					}
					$parent = implode('/', $context['trace']) . '/.';
					$context['item'] = Set::extract($parent, $data);
					$context['key'] = array_pop($context['trace']);
					if (isset($context['trace'][1]) && $context['trace'][1] > 0) {
						$context['item'] = $context['item'][0];
					} elseif (!empty($context['item'][$key])) {
						$context['item'] = $context['item'][$key];
					} else {
						$context['item'] = array_shift($context['item']);
					}
					$matches[] = $context;
					continue;
				}
				if ($token === '@*' && is_array($context['item'])) {
					$matches[] = array(
						'trace' => array_merge($context['trace'], (array)$key),
						'key' => $key,
						'item' => array_keys($context['item']),
					);
				} elseif (is_array($context['item'])
					&& array_key_exists($token, $context['item'])
					&& !(strval($key) === strval($token) && count($tokens) === 1 && $tokens[0] === '.')) {
					$items = $context['item'][$token];
					if (!is_array($items)) {
						$items = array($items);
					} elseif (!isset($items[0])) {
						$current = current($items);
						$currentKey = key($items);
						if (!is_array($current) || (is_array($current) && count($items) <= 1 && !is_numeric($currentKey))) {
							$items = array($items);
						}
					}

					foreach ($items as $key => $item) {
						$ctext = array($context['key']);
						if (!is_numeric($key)) {
							$ctext[] = $token;
							$tok = array_shift($tokens);
							if (isset($items[$tok])) {
								$ctext[] = $tok;
								$item = $items[$tok];
								$matches[] = array(
									'trace' => array_merge($context['trace'], $ctext),
									'key' => $tok,
									'item' => $item,
								);
								break;
							} elseif ($tok !== null) {
								array_unshift($tokens, $tok);
							}
						} else {
							$key = $token;
						}

						$matches[] = array(
							'trace' => array_merge($context['trace'], $ctext),
							'key' => $key,
							'item' => $item,
						);
					}
				} elseif ($key === $token || (ctype_digit($token) && $key == $token) || $token === '.') {
					$context['trace'][] = $key;
					$matches[] = array(
						'trace' => $context['trace'],
						'key' => $key,
						'item' => $context['item'],
					);
				}
			}
			if ($conditions) {
				foreach ($conditions as $condition) {
					$filtered = array();
					$length = count($matches);
					foreach ($matches as $i => $match) {
						if (Set::matches(array($condition), $match['item'], $i + 1, $length)) {
							$filtered[$i] = $match;
						}
					}
					$matches = $filtered;
				}
			}
			$contexts = $matches;

			if (empty($tokens)) {
				break;
			}
		} while (1);

		$r = array();

		foreach ($matches as $match) {
			if ((!$options['flatten'] || is_array($match['item'])) && !is_int($match['key'])) {
				$r[] = array($match['key'] => $match['item']);
			} else {
				$r[] = $match['item'];
			}
		}
		return $r;
	}

/**
 * This function can be used to see if a single item or a given xpath match certain conditions.
 *
 * @param string|array $conditions An array of condition strings or an XPath expression
 * @param array $data An array of data to execute the match on
 * @param int $i Optional: The 'nth'-number of the item being matched.
 * @param int $length Length.
 * @return bool
 * @link http://book.cakephp.org/2.0/en/core-utility-libraries/set.html#Set::matches
 */
	public static function matches($conditions, $data = array(), $i = null, $length = null) {
		if (empty($conditions)) {
			return true;
		}
		if (is_string($conditions)) {
			return (bool)Set::extract($conditions, $data);
		}
		foreach ($conditions as $condition) {
			if ($condition === ':last') {
				if ($i != $length) {
					return false;
				}
				continue;
			} elseif ($condition === ':first') {
				if ($i != 1) {
					return false;
				}
				continue;
			}
			if (!preg_match('/(.+?)([><!]?[=]|[><])(.*)/', $condition, $match)) {
				if (ctype_digit($condition)) {
					if ($i != $condition) {
						return false;
					}
				} elseif (preg_match_all('/(?:^[0-9]+|(?<=,)[0-9]+)/', $condition, $matches)) {
					return in_array($i, $matches[0]);
				} elseif (!array_key_exists($condition, $data)) {
					return false;
				}
				continue;
			}
			list(, $key, $op, $expected) = $match;
			if (!(isset($data[$key]) || array_key_exists($key, $data))) {
				return false;
			}

			$val = $data[$key];

			if ($op === '=' && $expected && $expected{0} === '/') {
				return preg_match($expected, $val);
			}
			if ($op === '=' && $val != $expected) {
				return false;
			}
			if ($op === '!=' && $val == $expected) {
				return false;
			}
			if ($op === '>' && $val <= $expected) {
				return false;
			}
			if ($op === '<' && $val >= $expected) {
				return false;
			}
			if ($op === '<=' && $val > $expected) {
				return false;
			}
			if ($op === '>=' && $val < $expected) {
				return false;
			}
		}
		return true;
	}

/**
 * Gets a value from an array or object that is contained in a given path using an array path syntax, i.e.:
 * "{n}.Person.{[a-z]+}" - Where "{n}" represents a numeric key, "Person" represents a string literal,
 * and "{[a-z]+}" (i.e. any string literal enclosed in brackets besides {n} and {s}) is interpreted as
 * a regular expression.
 *
 * @param array $data Array from where to extract
 * @param string|array $path As an array, or as a dot-separated string.
 * @return mixed An array of matched items or the content of a single selected item or null in any of these cases: $path or $data are null, no items found.
 * @link http://book.cakephp.org/2.0/en/core-utility-libraries/set.html#Set::classicExtract
 */
	public static function classicExtract($data, $path = null) {
		if (empty($path)) {
			return $data;
		}
		if (is_object($data)) {
			if (!($data instanceof ArrayAccess || $data instanceof Traversable)) {
				$data = get_object_vars($data);
			}
		}
		if (empty($data)) {
			return null;
		}
		if (is_string($path) && strpos($path, '{') !== false) {
			$path = String::tokenize($path, '.', '{', '}');
		} elseif (is_string($path)) {
			$path = explode('.', $path);
		}
		$tmp = array();

		if (empty($path)) {
			return null;
		}

		foreach ($path as $i => $key) {
			if (is_numeric($key) && (int)$key > 0 || $key === '0') {
				if (isset($data[$key])) {
					$data = $data[$key];
				} else {
					return null;
				}
			} elseif ($key === '{n}') {
				foreach ($data as $j => $val) {
					if (is_int($j)) {
						$tmpPath = array_slice($path, $i + 1);
						if (empty($tmpPath)) {
							$tmp[] = $val;
						} else {
							$tmp[] = Set::classicExtract($val, $tmpPath);
						}
					}
				}
				return $tmp;
			} elseif ($key === '{s}') {
				foreach ($data as $j => $val) {
					if (is_string($j)) {
						$tmpPath = array_slice($path, $i + 1);
						if (empty($tmpPath)) {
							$tmp[] = $val;
						} else {
							$tmp[] = Set::classicExtract($val, $tmpPath);
						}
					}
				}
				return $tmp;
			} elseif (strpos($key, '{') !== false && strpos($key, '}') !== false) {
				$pattern = substr($key, 1, -1);

				foreach ($data as $j => $val) {
					if (preg_match('/^' . $pattern . '/s', $j) !== 0) {
						$tmpPath = array_slice($path, $i + 1);
						if (empty($tmpPath)) {
							$tmp[$j] = $val;
						} else {
							$tmp[$j] = Set::classicExtract($val, $tmpPath);
						}
					}
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
 * @param array $list Where to insert into
 * @param string $path A dot-separated string.
 * @param array $data Data to insert
 * @return array
 * @link http://book.cakephp.org/2.0/en/core-utility-libraries/set.html#Set::insert
 */
	public static function insert($list, $path, $data = null) {
		return Hash::insert($list, $path, $data);
	}

/**
 * Removes an element from a Set or array as defined by $path.
 *
 * @param array $list From where to remove
 * @param string $path A dot-separated string.
 * @return array Array with $path removed from its value
 * @link http://book.cakephp.org/2.0/en/core-utility-libraries/set.html#Set::remove
 */
	public static function remove($list, $path = null) {
		return Hash::remove($list, $path);
	}

/**
 * Checks if a particular path is set in an array
 *
 * @param string|array $data Data to check on
 * @param string|array $path A dot-separated string.
 * @return bool true if path is found, false otherwise
 * @link http://book.cakephp.org/2.0/en/core-utility-libraries/set.html#Set::check
 */
	public static function check($data, $path = null) {
		if (empty($path)) {
			return $data;
		}
		if (!is_array($path)) {
			$path = explode('.', $path);
		}

		foreach ($path as $i => $key) {
			if (is_numeric($key) && (int)$key > 0 || $key === '0') {
				$key = (int)$key;
			}
			if ($i === count($path) - 1) {
				return (is_array($data) && array_key_exists($key, $data));
			}

			if (!is_array($data) || !array_key_exists($key, $data)) {
				return false;
			}
			$data =& $data[$key];
		}
		return true;
	}

/**
 * Computes the difference between a Set and an array, two Sets, or two arrays
 *
 * @param mixed $val1 First value
 * @param mixed $val2 Second value
 * @return array Returns the key => value pairs that are not common in $val1 and $val2
 * The expression for this function is($val1 - $val2) + ($val2 - ($val1 - $val2))
 * @link http://book.cakephp.org/2.0/en/core-utility-libraries/set.html#Set::diff
 */
	public static function diff($val1, $val2 = null) {
		if (empty($val1)) {
			return (array)$val2;
		}
		if (empty($val2)) {
			return (array)$val1;
		}
		$intersection = array_intersect_key($val1, $val2);
		while (($key = key($intersection)) !== null) {
			if ($val1[$key] == $val2[$key]) {
				unset($val1[$key]);
				unset($val2[$key]);
			}
			next($intersection);
		}

		return $val1 + $val2;
	}

/**
 * Determines if one Set or array contains the exact keys and values of another.
 *
 * @param array $val1 First value
 * @param array $val2 Second value
 * @return bool true if $val1 contains $val2, false otherwise
 * @link http://book.cakephp.org/2.0/en/core-utility-libraries/set.html#Set::contains
 */
	public static function contains($val1, $val2 = null) {
		if (empty($val1) || empty($val2)) {
			return false;
		}

		foreach ($val2 as $key => $val) {
			if (is_numeric($key)) {
				Set::contains($val, $val1);
			} else {
				if (!isset($val1[$key]) || $val1[$key] != $val) {
					return false;
				}
			}
		}
		return true;
	}

/**
 * Counts the dimensions of an array. If $all is set to false (which is the default) it will
 * only consider the dimension of the first element in the array.
 *
 * @param array $array Array to count dimensions on
 * @param bool $all Set to true to count the dimension considering all elements in array
 * @param int $count Start the dimension count at this number
 * @return int The number of dimensions in $array
 * @link http://book.cakephp.org/2.0/en/core-utility-libraries/set.html#Set::countDim
 */
	public static function countDim($array, $all = false, $count = 0) {
		if ($all) {
			$depth = array($count);
			if (is_array($array) && reset($array) !== false) {
				foreach ($array as $value) {
					$depth[] = Set::countDim($value, true, $count + 1);
				}
			}
			$return = max($depth);
		} else {
			if (is_array(reset($array))) {
				$return = Set::countDim(reset($array)) + 1;
			} else {
				$return = 1;
			}
		}
		return $return;
	}

/**
 * Normalizes a string or array list.
 *
 * @param mixed $list List to normalize
 * @param bool $assoc If true, $list will be converted to an associative array
 * @param string $sep If $list is a string, it will be split into an array with $sep
 * @param bool $trim If true, separated strings will be trimmed
 * @return array
 * @link http://book.cakephp.org/2.0/en/core-utility-libraries/set.html#Set::normalize
 */
	public static function normalize($list, $assoc = true, $sep = ',', $trim = true) {
		if (is_string($list)) {
			$list = explode($sep, $list);
			if ($trim) {
				foreach ($list as $key => $value) {
					$list[$key] = trim($value);
				}
			}
			if ($assoc) {
				return Hash::normalize($list);
			}
		} elseif (is_array($list)) {
			$list = Hash::normalize($list, $assoc);
		}
		return $list;
	}

/**
 * Creates an associative array using a $path1 as the path to build its keys, and optionally
 * $path2 as path to get the values. If $path2 is not specified, all values will be initialized
 * to null (useful for Set::merge). You can optionally group the values by what is obtained when
 * following the path specified in $groupPath.
 *
 * @param array|object $data Array or object from where to extract keys and values
 * @param string|array $path1 As an array, or as a dot-separated string.
 * @param string|array $path2 As an array, or as a dot-separated string.
 * @param string $groupPath As an array, or as a dot-separated string.
 * @return array Combined array
 * @link http://book.cakephp.org/2.0/en/core-utility-libraries/set.html#Set::combine
 */
	public static function combine($data, $path1 = null, $path2 = null, $groupPath = null) {
		if (empty($data)) {
			return array();
		}

		if (is_object($data)) {
			if (!($data instanceof ArrayAccess || $data instanceof Traversable)) {
				$data = get_object_vars($data);
			}
		}

		if (is_array($path1)) {
			$format = array_shift($path1);
			$keys = Set::format($data, $format, $path1);
		} else {
			$keys = Set::extract($data, $path1);
		}
		if (empty($keys)) {
			return array();
		}

		if (!empty($path2) && is_array($path2)) {
			$format = array_shift($path2);
			$vals = Set::format($data, $format, $path2);
		} elseif (!empty($path2)) {
			$vals = Set::extract($data, $path2);
		} else {
			$count = count($keys);
			for ($i = 0; $i < $count; $i++) {
				$vals[$i] = null;
			}
		}

		if ($groupPath) {
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
		if (empty($vals)) {
			return array();
		}
		return array_combine($keys, $vals);
	}

/**
 * Converts an object into an array.
 *
 * @param object $object Object to reverse
 * @return array Array representation of given object
 * @link http://book.cakephp.org/2.0/en/core-utility-libraries/set.html#Set::reverse
 */
	public static function reverse($object) {
		$out = array();
		if ($object instanceof SimpleXMLElement) {
			return Xml::toArray($object);
		} elseif (is_object($object)) {
			$keys = get_object_vars($object);
			if (isset($keys['_name_'])) {
				$identity = $keys['_name_'];
				unset($keys['_name_']);
			}
			$new = array();
			foreach ($keys as $key => $value) {
				if (is_array($value)) {
					$new[$key] = (array)Set::reverse($value);
				} else {
					// @codingStandardsIgnoreStart Legacy junk
					if (isset($value->_name_)) {
						$new = array_merge($new, Set::reverse($value));
					} else {
						$new[$key] = Set::reverse($value);
					}
					// @codingStandardsIgnoreEnd
				}
			}
			if (isset($identity)) {
				$out[$identity] = $new;
			} else {
				$out = $new;
			}
		} elseif (is_array($object)) {
			foreach ($object as $key => $value) {
				$out[$key] = Set::reverse($value);
			}
		} else {
			$out = $object;
		}
		return $out;
	}

/**
 * Collapses a multi-dimensional array into a single dimension, using a delimited array path for
 * each array element's key, i.e. array(array('Foo' => array('Bar' => 'Far'))) becomes
 * array('0.Foo.Bar' => 'Far').
 *
 * @param array $data Array to flatten
 * @param string $separator String used to separate array key elements in a path, defaults to '.'
 * @return array
 * @link http://book.cakephp.org/2.0/en/core-utility-libraries/set.html#Set::flatten
 */
	public static function flatten($data, $separator = '.') {
		return Hash::flatten($data, $separator);
	}

/**
 * Expand/unflattens a string to an array
 *
 * For example, unflattens an array that was collapsed with `Set::flatten()`
 * into a multi-dimensional array. So, `array('0.Foo.Bar' => 'Far')` becomes
 * `array(array('Foo' => array('Bar' => 'Far')))`.
 *
 * @param array $data Flattened array
 * @param string $separator The delimiter used
 * @return array
 */
	public static function expand($data, $separator = '.') {
		return Hash::expand($data, $separator);
	}

/**
 * Flattens an array for sorting
 *
 * @param array $results Array to flatten.
 * @param string $key Key.
 * @return array
 */
	protected static function _flatten($results, $key = null) {
		$stack = array();
		foreach ($results as $k => $r) {
			$id = $k;
			if ($key !== null) {
				$id = $key;
			}
			if (is_array($r) && !empty($r)) {
				$stack = array_merge($stack, Set::_flatten($r, $id));
			} else {
				$stack[] = array('id' => $id, 'value' => $r);
			}
		}
		return $stack;
	}

/**
 * Sorts an array by any value, determined by a Set-compatible path
 *
 * @param array $data An array of data to sort
 * @param string $path A Set-compatible path to the array value
 * @param string $dir Direction of sorting - either ascending (ASC), or descending (DESC)
 * @return array Sorted array of data
 * @link http://book.cakephp.org/2.0/en/core-utility-libraries/set.html#Set::sort
 */
	public static function sort($data, $path, $dir) {
		if (empty($data)) {
			return $data;
		}
		$originalKeys = array_keys($data);
		$numeric = false;
		if (is_numeric(implode('', $originalKeys))) {
			$data = array_values($data);
			$numeric = true;
		}
		$result = Set::_flatten(Set::extract($data, $path));
		list($keys, $values) = array(Set::extract($result, '{n}.id'), Set::extract($result, '{n}.value'));

		$dir = strtolower($dir);
		if ($dir === 'asc') {
			$dir = SORT_ASC;
		} elseif ($dir === 'desc') {
			$dir = SORT_DESC;
		}
		array_multisort($values, $dir, $keys, $dir);
		$sorted = array();
		$keys = array_unique($keys);

		foreach ($keys as $k) {
			if ($numeric) {
				$sorted[] = $data[$k];
			} else {
				if (isset($originalKeys[$k])) {
					$sorted[$originalKeys[$k]] = $data[$originalKeys[$k]];
				} else {
					$sorted[$k] = $data[$k];
				}
			}
		}
		return $sorted;
	}

/**
 * Allows the application of a callback method to elements of an
 * array extracted by a Set::extract() compatible path.
 *
 * @param mixed $path Set-compatible path to the array value
 * @param array $data An array of data to extract from & then process with the $callback.
 * @param mixed $callback Callback method to be applied to extracted data.
 * See http://ca2.php.net/manual/en/language.pseudo-types.php#language.types.callback for examples
 * of callback formats.
 * @param array $options Options are:
 *                       - type : can be pass, map, or reduce. Map will handoff the given callback
 *                                to array_map, reduce will handoff to array_reduce, and pass will
 *                                use call_user_func_array().
 * @return mixed Result of the callback when applied to extracted data
 * @link http://book.cakephp.org/2.0/en/core-utility-libraries/set.html#Set::apply
 */
	public static function apply($path, $data, $callback, $options = array()) {
		$defaults = array('type' => 'pass');
		$options += $defaults;
		$extracted = Set::extract($path, $data);

		if ($options['type'] === 'map') {
			return array_map($callback, $extracted);
		} elseif ($options['type'] === 'reduce') {
			return array_reduce($extracted, $callback);
		} elseif ($options['type'] === 'pass') {
			return call_user_func_array($callback, array($extracted));
		}
		return null;
	}

/**
 * Takes in a flat array and returns a nested array
 *
 * @param mixed $data Data
 * @param array $options Options are:
 *      children   - the key name to use in the resultset for children
 *      idPath     - the path to a key that identifies each entry
 *      parentPath - the path to a key that identifies the parent of each entry
 *      root       - the id of the desired top-most result
 * @return array of results, nested
 * @link
 */
	public static function nest($data, $options = array()) {
		if (!$data) {
			return $data;
		}

		$alias = key(current($data));
		$options += array(
			'idPath' => "/$alias/id",
			'parentPath' => "/$alias/parent_id",
			'children' => 'children',
			'root' => null
		);

		$return = $idMap = array();
		$ids = Set::extract($data, $options['idPath']);
		$idKeys = explode('/', trim($options['idPath'], '/'));
		$parentKeys = explode('/', trim($options['parentPath'], '/'));

		foreach ($data as $result) {
			$result[$options['children']] = array();

			$id = Set::get($result, $idKeys);
			$parentId = Set::get($result, $parentKeys);

			if (isset($idMap[$id][$options['children']])) {
				$idMap[$id] = array_merge($result, (array)$idMap[$id]);
			} else {
				$idMap[$id] = array_merge($result, array($options['children'] => array()));
			}
			if (!$parentId || !in_array($parentId, $ids)) {
				$return[] =& $idMap[$id];
			} else {
				$idMap[$parentId][$options['children']][] =& $idMap[$id];
			}
		}

		if ($options['root']) {
			$root = $options['root'];
		} else {
			$root = Set::get($return[0], $parentKeys);
		}

		foreach ($return as $i => $result) {
			$id = Set::get($result, $idKeys);
			$parentId = Set::get($result, $parentKeys);
			if ($id !== $root && $parentId != $root) {
				unset($return[$i]);
			}
		}

		return array_values($return);
	}

/**
 * Return the value at the specified position
 *
 * @param array $input an array
 * @param string|array $path string or array of array keys
 * @return the value at the specified position or null if it doesn't exist
 */
	public static function get($input, $path = null) {
		if (is_string($path)) {
			if (strpos($path, '/') !== false) {
				$keys = explode('/', trim($path, '/'));
			} else {
				$keys = explode('.', trim($path, '.'));
			}
		} else {
			$keys = $path;
		}
		return Hash::get($input, $keys);
	}

}
