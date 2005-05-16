<?PHP
//////////////////////////////////////////////////////////////////////////
// + $Id$
// +------------------------------------------------------------------+ //
// + Cake <https://developers.nextco.com/cake/>                       + //
// + Copyright: (c) 2005 Cake Authors/Developers                      + //
// +                                                                  + //
// + Author(s): Michal Tatarynowicz aka Pies <tatarynowicz@gmail.com> + //
// +            Larry E. Masters aka PhpNut <nut@phpnut.com>          + //
// +            Kamil Dzielinski aka Brego <brego.dk@gmail.com>       + //
// +                                                                  + //
// +------------------------------------------------------------------+ //
// + Licensed under The MIT License                                   + //
// + Redistributions of files must retain the above copyright notice. + //
// + You may not use this file except in compliance with the License. + //
// +                                                                  + //
// + You may obtain a copy of the License at:                         + //
// + License page: http://www.opensource.org/licenses/mit-license.php + //
// +------------------------------------------------------------------+ //
//////////////////////////////////////////////////////////////////////////

/**
  * Purpose: Basics
  * Basic Cake functionalities.
  * 
  * @filesource 
  * @author Michal Tatarynowicz <tatarynowicz@gmail.com>
  * @author Larry E. Masters aka PhpNut <nut@phpnut.com>
  * @author Kamil Dzielinski aka Brego <brego.dk@gmail.com>
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
  * Enter description here...
  *
  */
function load_libs () {
	foreach (list_modules(LIBS) as $lib) {
		if ($lib != 'basics') {
			include_once (LIBS.$lib.'.php');
		}
	}
}

/**
  * Enter description here...
  *
  */
function load_models () {
	require (APP.'app_model.php');
	foreach (list_modules(MODELS) as $model) {
		require (MODELS.$model.'.php');
	}
}

/**
  * Enter description here...
  *
  */
function load_controllers () {
	require (APP.'app_controller.php');

	foreach (list_modules(HELPERS) as $helper) {
		require (HELPERS.$helper.'.php');
	}

	foreach (list_modules(CONTROLLERS) as $controller) {
		require (CONTROLLERS.$controller.'.php');
	}
}

/**
  * Enter description here...
  *
  * @param unknown_type $path
  * @param unknown_type $sort
  * @return unknown
  */
function list_modules($path, $sort=true) {
	if ($d = opendir($path)) {
		$out = array();
		$r = null;
		while (false !== ($fn = readdir($d))) {
			if (preg_match('#^(.+)\.php$#', $fn, $r)) {
				$out[] = $r[1];
			}
		}
		if ($sort || $this->sort) {
			sort($out);
		}

		return $out;
	}
	else {
		return false;
	}
}

/**
  * Enter description here...
  *
  */
function uses_config () {
	global $TIME_START;

	require (CONFIGS.'core.php');
}

/**
  * Enter description here...
  *
  */
function uses_database ($level='devel') {
	global $DB;

	if ($config = loadDatabaseConfig($level)) {

		$db_driver_class = 'DBO_'.$config['driver'];
		$db_driver_fn = LIBS.strtolower($db_driver_class.'.php');

 		if (file_exists($db_driver_fn)) {
			uses (strtolower($db_driver_class));
			$DB = new $db_driver_class ($config);
		}
		else {
			 die('Specified ('.$config['driver'].') database driver not found.');
		}
	}
}

/**
  * Enter description here...
  *
  * @return unknown
  */
function loadDatabaseConfig ($level='devel') {
	if (file_exists(CONFIGS.'database.php'))
		require (CONFIGS.'database.php');

	if (empty($DATABASE_CONFIG))
		 return false;

	if (empty($DATABASE_CONFIG[$level]))
		 return false;

	if (!is_array($DATABASE_CONFIG[$level]))
		 return false;

	return $DATABASE_CONFIG[$level];
}

/**
  * Enter description here...
  *
  */
function uses_tag_generator () {
	require (CONFIGS.'tags.php');
}

/**
  * Enter description here...
  *
  */
function uses () {
	$args = func_get_args();
	foreach ($args as $arg) {
		require_once (LIBS.$arg.'.php');
	}
}

/**
  * Enter description here...
  *
  * @param unknown_type $var
  * @param unknown_type $show_html
  */
function debug($var = FALSE, $show_html = false) {
	if (DEBUG) {
		print "\n<pre>\n";
		if ($show_html) $var = str_replace('<', '&lt;', str_replace('>', '&gt;', $var));
		print_r($var);
		print "\n</pre>\n";
	}
}


if (!function_exists('getMicrotime')) {

/**
  * Enter description here...
  *
  * @return unknown
  */
	function getMicrotime() {
		list($usec, $sec) = explode(" ", microtime());
		return ((float)$usec + (float)$sec);
	}
}
if (!function_exists('sortByKey')) {
/**
  * Enter description here...
  *
  * @param unknown_type $array
  * @param unknown_type $sortby
  * @param unknown_type $order
  * @param unknown_type $type
  * @return unknown
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

			Return $out;

		}
		else
		Return null;
	}
}

if (!function_exists('array_combine')) {
/**
  * Enter description here...
  *
  * @param unknown_type $a1
  * @param unknown_type $a2
  * @return unknown
  */
	function array_combine($a1, $a2) {
		$a1 = array_values($a1);
		$a2 = array_values($a2);

		if (count($a1) != count($a2)) return false; // different lenghts
		if (count($a1) <= 0) return false; // arrays are the same and both are empty
		
		$output = array();
		
		for ($i = 0; $i < count($a1); $i++) {
			$output[$a1[$i]] = $a2[$i];
		}
		
		return $output;
	}
}

/**
  * Enter description here...
  *
  *
  * @package cake
  * @subpackage cake.libs
  * @since Cake v 0.2.9
  *
  */
class neatArray {

/**
  * Enter description here...
  *
  * @param unknown_type $value
  * @return neatArray
  */
	function neatArray ($value) {
		$this->value = $value;
	}

/**
  * Enter description here...
  *
  * @param unknown_type $field_name
  * @param unknown_type $value
  * @return unknown
  */
	function findIn ($field_name, $value) {
		$out = false;
		foreach ($this->value as $k=>$v) {
			if (isset($v[$field_name]) && ($v[$field_name] == $value)) {
				$out[$k] = $v;
			}
		}

		return $out;
	}

/**
  * Enter description here...
  *
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
}

?>