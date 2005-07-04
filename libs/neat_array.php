<?php

/**
 * Class used for internal manipulation of multiarrays (arrays of arrays).
 *
 * @package cake
 * @subpackage cake.libs
 * @since CakePHP v 0.2.9
 */
class NeatArray {
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
	function NeatArray ($value=array()) {
		$this->value = $value;
	}

	/**
	 * Finds and returns records with $fieldName equal $value from this NeatArray.
	 *
	 * @param string $fieldName
	 * @param string $value
	 * @return mixed
	 * @access public
	 * @uses NeatArray::value
	 */
	function findIn ($fieldName, $value) 
	{
		if (!is_array($this->value))
		{
			return false;
		}
			
		$out = false;
		foreach ($this->value as $k=>$v) 
		{
			if (isset($v[$fieldName]) && ($v[$fieldName] == $value)) 
			{
				$out[$k] = $v;
			}
		}

		return $out;
	}

	/**
	 * Checks if $this->value is array, and removes all empty elements.
	 *
	 * @access public
	 * @uses NeatArray::value
	 */
	function cleanup () {
		$out = is_array($this->value)? array(): null;
		foreach ($this->value as $k=>$v) {
			if ($v) {
				$out[$k] = $v;
			}
		}
		$this->value = $out;
	}

	/**
	 * Adds elements from the supplied array to itself.
	 *
	 * @param string $value 
	 * @return bool
	 * @access public
	 * @uses NeatArray::value
	 */
	 function add ($value) {
		 return ($this->value = $this->plus($value))? true: false;
	 }

	/**
	 * Returns itself merged with given array.
	 *
	 * @param array $value Array to add to NeatArray.
	 * @return array
	 * @access public
	 * @uses NeatArray::value
	 */
	 function plus ($value) {
		 return array_merge($this->value, (is_array($value)? $value: array($value)));
	 }

	/**
	 * Counts repeating strings and returns an array of totals.
	 *
	 * @param int $sortedBy A value of 1 sorts by values, a value of 2 sorts by keys. Defaults to null (no sorting).
	 * @return array
	 * @access public
	 * @uses NeatArray::value
	 */
	function totals ($sortedBy=1,$reverse=true) {
		$out = array();
		foreach ($this->value as $val)
			isset($out[$val])? $out[$val]++: $out[$val] = 1;

		if ($sortedBy == 1) {
			$reverse? arsort($out, SORT_NUMERIC): asort($out, SORT_NUMERIC);
		}
		
		if ($sortedBy == 2) {
			$reverse? krsort($out, SORT_STRING): ksort($out, SORT_STRING);
		}

		return $out;
	}

	/**
	 * Performs an array_filter() on the contents.
	 *
	 * @param unknown_type $with
	 * @return unknown
	 */
	function filter ($with) {
		return $this->value = array_filter($this->value, $with);
	}

	/**
	 * Passes each of its values through a specified function or method. Think of PHP's array_walk.
	 *
	 * @return array
	 * @access public
	 * @uses NeatArray::value
	 */
	function walk ($with) {
		array_walk($this->value, $with);
		return $this->value;
	}
	
	function sprintf($template)
	{
		for ($ii=0; $ii<count($this->value); $ii++)
		{
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
	function extract ($name) {
		$out = array();
		foreach ($this->value as $val) {
			if (isset($val[$name]))
				$out[] = $val[$name];
		}
		return $out;
	}

	/**
	 * Returns a list of unique elements.
	 *
	 * @return array
	 */
	function unique () {
		return array_unique($this->value);
	}

	/**
	 * Removes duplicate elements from the value and returns it.
	 *
	 * @return array
	 */
	function makeUnique () {
		return $this->value = array_unique($this->value);
	}

	/**
	 * Joins an array with myself using a key (like a join between database tables).
	 *
	 * Example:
	 *
	 *     $alice = array('id'=>'1', 'name'=>'Alice');
	 *     $bob   = array('id'=>'2', 'name'=>'Bob');
	 *
	 *     $users = new NeatArray(array($alice, $bob));
	 * 
	 *     $born = array
	 *         ( 
	 *         array('user_id'=>'1', 'born'=>'1980'),
	 *         array('user_id'=>'2', 'born'=>'1976')
	 *         );
	 *
	 *     $users->joinWith($born, 'id', 'user_id');
	 *
	 * Result:
	 *
	 *     $users->value == array
	 *         (
	 *         array('id'=>'1', 'name'=>'Alice', 'born'=>'1980'),
	 *         array('id'=>'2', 'name'=>'Bob',   'born'=>'1976')
	 *         );
	 *
	 *
	 * @param array $his The array to join with myself.
	 * @param string $onMine Key to use on myself.
	 * @param string $onHis Key to use on him.
	 * @return array
	 */

	function joinWith ($his, $onMine, $onHis=null) {
		if (empty($onHis)) $onHis = $onMine;

		$his = new NeatArray($his);

		$out = array();
		foreach ($this->value as $key=>$val) {
			if ($fromHis = $his->findIn($onHis, $val[$onMine])) {
				list($fromHis) = array_values($fromHis);
				$out[$key] = array_merge($val, $fromHis);
			}
			else {
				$out[$key] = $val;
			}
		}

		return $this->value = $out;
	}

	/**
	 * Enter description here...
	 *
	 * @param unknown_type $root
	 * @param unknown_type $idKey
	 * @param unknown_type $parentIdKey
	 * @param unknown_type $childrenKey
	 * @return unknown
	 */
	function threaded ($root=null, $idKey='id', $parentIdKey='parent_id', $childrenKey='children') {
		$out = array();

		for ($ii=0; $ii<sizeof($this->value); $ii++) {
			if ($this->value[$ii][$parentIdKey] == $root) {
				$tmp = $this->value[$ii];
				$tmp[$childrenKey] = isset($this->value[$ii][$idKey])? 
					$this->threaded($this->value[$ii][$idKey], $idKey, $parentIdKey, $childrenKey): 
					null;
				$out[] = $tmp;
			}
		}
		
		return $out;
	}
}



?>