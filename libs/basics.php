<?PHP
//////////////////////////////////////////////////////////////////////////
// + $Id$
// +------------------------------------------------------------------+ //
// + Cake <https://developers.nextco.com/cake/>                       + //
// + Copyright: (c) 2005, Cake Authors/Developers                     + //
// + Author(s): Michal Tatarynowicz aka Pies <tatarynowicz@gmail.com> + //
// +            Larry E. Masters aka PhpNut <nut@phpnut.com>          + //
// +            Kamil Dzielinski aka Brego <brego.dk@gmail.com>       + //
// +------------------------------------------------------------------+ //
// + Licensed under The MIT License                                   + //
// + Redistributions of files must retain the above copyright notice. + //
// + See: http://www.opensource.org/licenses/mit-license.php          + //
//////////////////////////////////////////////////////////////////////////

/**
 * Basic Cake functionalities.
 * 
 * @filesource 
 * @author Cake Authors/Developers
 * @copyright Copyright (c) 2005, Cake Authors/Developers
 * @link https://developers.nextco.com/cake/wiki/Authors Authors/Developers
 * @package cake
 * @subpackage cake.libs
 * @since Cake v 0.2.9
 * @version $Revision$
 * @modifiedby $LastChangedBy$
 * @lastmodified $Date$
 * @license http://www.opensource.org/licenses/mit-license.php The MIT License
 *
 */

/**
 * Loads all models.
 *
 * @uses listModules()
 * @uses APP
 * @uses MODELS
 */
function loadModels () {
	require (APP.'app_model.php');
	foreach (listClasses(MODELS) as $model_fn) {
		require (MODELS.$model_fn);
	}
}

/**
 * Loads all controllers.
 *
 * @uses APP
 * @uses listModules()
 * @uses HELPERS
 * @uses CONTROLLERS
 */
function loadControllers () {
	require (APP.'app_controller.php');

	foreach (listClasses(HELPERS) as $helper) {
		require (HELPERS.$helper.'.php');
	}

	foreach (listClasses(CONTROLLERS) as $controller) {
		require (CONTROLLERS.$controller.'.php');
	}
}

/**
  * Loads a controller and its helper libraries.
  *
  * @param string $name
  * @return boolean
  */
function loadController ($name) {
	$controller_fn = CONTROLLERS.Inflector::underscore($name).'_controller.php';
	$helper_fn = HELPERS.Inflector::underscore($name).'_helper.php';

	require(APP.'app_controller.php');

	if (file_exists($helper_fn))
		require($helper_fn);
	
	return file_exists($controller_fn)? require($controller_fn): false;
}

/**
  * Lists PHP files in given directory.
  *
  * @param string $path
  * @return array
  */
function listClasses($path) {
	$modules = new Folder($path);
	return $modules->find('(.+)\.php');
}

/**
  * Loads configuration files
  */
function config () {
	$args = func_get_args();
	foreach ($args as $arg) {
		if (file_exists(CONFIGS.$arg.'.php')) {
			require_once (CONFIGS.$arg.'.php');
			if (count($args) == 1) return true;
		}
		else {
			if (count($args) == 1) return false;
		}
	}
}

/**
 * Loads component/components from LIBS.
 *
 * Example:
 * <code>
 * uses('inflector', 'object');
 * </code>
 *
 * @uses LIBS
 */
function uses () {
	$args = func_get_args();
	foreach ($args as $arg) {
		require_once (LIBS.strtolower($arg).'.php');
	}
}

/**
 * Setup a debug point.
 *
 * @param boolean $var
 * @param boolean $show_html
 */
function debug($var = false, $show_html = false) {
	if (DEBUG) {
		print "\n<pre>\n";
		if ($show_html) $var = str_replace('<', '&lt;', str_replace('>', '&gt;', $var));
		print_r($var);
		print "\n</pre>\n";
	}
}


if (!function_exists('getMicrotime')) {
/**
 * Returns microtime for execution time checking.
 *
 * @return integer
 */
	function getMicrotime() {
		list($usec, $sec) = explode(" ", microtime());
		return ((float)$usec + (float)$sec);
	}
}

if (!function_exists('sortByKey')) {
/**
 * Sorts given $array by key $sortby.
 *
 * @param array $array
 * @param string $sortby
 * @param string $order Sort order asc/desc (ascending or descending).
 * @param integer $type
 * @return mixed
 */
	function sortByKey(&$array, $sortby, $order='asc', $type=SORT_NUMERIC) {

		if( is_array($array) ) {

			foreach( $array AS $key => $val )
			$sa[$key] = $val[$sortby];

			if( $order == 'asc' )
			asort($sa, $type);
			else
			arsort($sa, $type);

			foreach( $sa as $key=>$val )
			$out[] = $array[$key];

			return $out;

		}
		else
		return null;
	}
}

if (!function_exists('array_combine')) {
/**
 * Combines given identical arrays by using the first array's values as keys,
 * and the second one's values as values. (Implemented for back-compatibility with PHP4.)
 *
 * @param array $a1
 * @param array $a2
 * @return mixed Outputs either combined array or false.
 */
	function array_combine($a1, $a2) {
		$a1 = array_values($a1);
		$a2 = array_values($a2);
		$c1 = count($a1);
		$c2 = count($a2);

		if ($c1 != $c2) return false; // different lenghts
		if ($c1 <= 0) return false; // arrays are the same and both are empty
		
		$output = array();
		
		for ($i = 0; $i < $c1; $i++) {
			$output[$a1[$i]] = $a2[$i];
		}
		
		return $output;
	}
}

/**
 * Class used for internal manipulation of multiarrays (arrays of arrays).
 *
 * @package cake
 * @subpackage cake.libs
 * @since Cake v 0.2.9
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
	 * Checks whether $fieldName with $value exists in this NeatArray object.
	 *
	 * @param string $fieldName
	 * @param string $value
	 * @return mixed
	 * @access public
	 * @uses NeatArray::value
	 */
	function findIn ($fieldName, $value) {
		$out = false;
		foreach ($this->value as $k=>$v) {
			if (isset($v[$fieldName]) && ($v[$fieldName] == $value)) {
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

	function unique () {
		return array_unique($this->value);
	}

	function makeUnique () {
		return $this->value = array_unique($this->value);
	}

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
