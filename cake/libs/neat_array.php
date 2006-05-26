<?php
/* SVN FILE: $Id$ */
/**
 * Library of array functions for Cake.
 *
 * Internal use only.
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
 * @since			CakePHP v 0.2.9
 * @version			$Revision$
 * @modifiedby		$LastChangedBy$
 * @lastmodified	$Date$
 * @license			http://www.opensource.org/licenses/mit-license.php The MIT License
 */
/**
 * Class used for internal manipulation of multi-dimensional arrays (arrays of arrays).
 *
 * Long description for class
 *
 * @package		cake
 * @subpackage	cake.cake.libs
 */
class NeatArray{
/**
 * Value of NeatArray.
 *
 * @var array
 * @access public
 */
	var $value;
/**
 * Constructor. Defaults to an empty array.
 *
 * @param array $value
 * @access public
 * @uses NeatArray::value
 */
	function NeatArray($value = array()) {
		$this->value = $value;
	}
/**
 * Finds and returns records with $fieldName equal to $value from this NeatArray.
 *
 * @param string $fieldName
 * @param string $value
 * @return mixed
 * @access public
 * @uses NeatArray::value
 */
	function findIn($fieldName, $value) {
		if (!is_array($this->value)) {
			return false;
		}
		$out = false;
		$keys = array_keys($this->value);
		$count = sizeof($keys);

		for($i = 0; $i < $count; $i++) {
			if (isset($this->value[$keys[$i]][$fieldName]) && ($this->value[$keys[$i]][$fieldName] == $value))
			{
				$out[$keys[$i]] = $this->value[$keys[$i]];
			}
		}
		return $out;
	}
/**
 * Checks if $this->value is an array, and removes all empty elements.
 *
 * @access public
 * @uses NeatArray::value
 */
	function cleanup() {
		$out = is_array($this->value) ? array(): null;
		foreach($this->value as $k => $v) {
			if ($v == "0") {
				$out[$k] = $v;
			} elseif ($v) {
				$out[$k] = $v;
			}
		}
		$this->value=$out;
	}
/**
 * Adds elements from given array to itself.
 *
 * @param string $value
 * @return bool
 * @access public
 * @uses NeatArray::value
 */
	function add($value) {
		return ($this->value = $this->plus($value)) ? true : false;
	}
/**
 * Returns itself merged with given array.
 *
 * @param array $value Array to add to NeatArray.
 * @return array
 * @access public
 * @uses NeatArray::value
 */
	function plus($value) {
		$merge = array_merge($this->value, (is_array($value) ? $value : array($value)));
		return $merge;
	}
/**
 * Counts repeating strings and returns an array of totals.
 *
 * @param int $sortedBy A value of 1 sorts by values, a value of 2 sorts by keys. Defaults to null (no sorting).
 * @return array
 * @access public
 * @uses NeatArray::value
 */
	function totals($sortedBy = 1, $reverse = true) {
		$out = array();
		foreach($this->value as $val) {
			isset($out[$val]) ? $out[$val]++ : $out[$val] = 1;
		}

		if ($sortedBy == 1) {
			$reverse ? arsort($out, SORT_NUMERIC) : asort($out, SORT_NUMERIC);
		}

		if ($sortedBy == 2) {
			$reverse ? krsort($out, SORT_STRING) : ksort($out, SORT_STRING);
		}
		return $out;
	}
/**
 * Performs an array_filter() on the contents of this NeatArray.
 *
 * @param string $with Name of callback function to perform on each element of this NeatArray.
 * @return array
 */
	function filter($with) {
		return $this->value = array_filter($this->value, $with);
	}
/**
 * Passes each of its values through a specified function or method.
 * Think of PHP's {@link http://php.net/array_walk array_walk()}.
 *
 * @param string $with Name of callback function
 * @return array Returns value of NeatArray::value
 * @access public
 * @uses NeatArray::value
 */
	function walk($with) {
		array_walk($this->value, $with);
		return $this->value;
	}
/**
 * Apply $template to all elements of this NeatArray, and return the array itself.
 *
 * @param string $template {@link http://php.net/sprintf sprintf()}-compatible string to be applied to all values of this NeatArray.
 * @return array
 */
	function sprintf($template) {
		$count = count($this->value);
		for($ii = 0; $ii < $count; $ii++) {
			$this->value[$ii] = sprintf($template, $this->value[$ii]);
		}
		return $this->value;
	}
/**
 * Extracts a value from all array items.
 *
 * @return array
 * @access public
 * @uses NeatArray::value
 */
	function extract($name) {
		$out = array();
		foreach($this->value as $val) {
			if (isset($val[$name]))
			$out[]=$val[$name];
		}
		return $out;
	}
/**
 * Returns a list of unique elements.
 *
 * @return array
 */
	function unique() {
		$unique = array_unique($this->value);
		return $unique;
	}
/**
 * Removes duplicate elements from the value and returns it.
 *
 * @return array
 */
	function makeUnique() {
		return $this->value = array_unique($this->value);
	}
/**
 * Joins an array with myself using a key (like a join between database tables).
 *
 * Example:
 *
 * $alice = array('id'=>'1', 'name'=>'Alice');
 * $bob = array('id'=>'2', 'name'=>'Bob');
 *
 * $users = new NeatArray(array($alice, $bob));
 *
 * $born = array
 * (
 *    array('user_id'=>'1', 'born'=>'1980'),
 *    array('user_id'=>'2', 'born'=>'1976')
 * );
 *
 * $users->joinWith($born, 'id', 'user_id');
 *
 * Result:
 *
 * $users->value == array
 *    (
 *        array('id'=>'1', 'name'=>'Alice', 'born'=>'1980'),
 *        array('id'=>'2', 'name'=>'Bob',	'born'=>'1976')
 *    );
 *
 *
 * @param array $his The array to join with myself.
 * @param string $onMine Key to use on myself.
 * @param string $onHis Key to use on him.
 * @return array
 */
	function joinWith($his, $onMine, $onHis = null) {
		if (empty($onHis)) {
			$onHis = $onMine;
		}
		$his = new NeatArray($his);
		$out = array();

		foreach($this->value as $key => $val) {
			if ($fromHis = $his->findIn($onHis, $val[$onMine])) {
				list($fromHis) = array_values($fromHis);
				$out[$key] = array_merge($val, $fromHis);
			} else {
				$out[$key] = $val;
			}
		}
		return $this->value = $out;
	}
/**
 * Enter description here...
 * @todo Explain this function. almost looks like it creates a tree
 *
 * @param string $root
 * @param string $idKey
 * @param string $parentIdKey
 * @param string $childrenKey
 * @return array
 */
	function threaded($root = null, $idKey = 'id', $parentIdKey = 'parent_id', $childrenKey = 'children') {
		$out = array();
		$sizeof = sizeof($this->value);

		for($ii = 0; $ii < $sizeof; $ii++) {
			if ($this->value[$ii][$parentIdKey] == $root) {
				$tmp = $this->value[$ii];
				$tmp[$childrenKey]=isset($this->value[$ii][$idKey])
											? $this->threaded($this->value[$ii][$idKey], $idKey, $parentIdKey, $childrenKey) : null;
				$out[] = $tmp;
			}
		}
		return $out;
	}
/**
 * Array multi search
 *
 * @param string $search_value
 * @param array $the_array
 * @return array
 * @link http://php.net/array_search#47116
 */
	function multi_search($search_value, $the_array = null) {
		if ($the_array == null) {
			$the_array = $this->value;
		}

		if (is_array($the_array)) {
			foreach($the_array as $key => $value) {
				$result = $this->multi_search($search_value, $value);

				if (is_array($result)) {
					$return = $result;
					array_unshift($return, $key);
					return $return;
				} elseif ($result == true) {
					$return[]=$key;
					return $return;
				}
			}
			return false;
		} else {
			if ($search_value == $the_array) {
				return true;
			} else {
				return false;
			}
		}
	}
}
?>