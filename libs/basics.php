<?php
//////////////////////////////////////////////////////////////////////////
// + $Id$
// +------------------------------------------------------------------+ //
// + CakePHP : Rapid Development Framework <http://www.cakephp.org/>  + //
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
 * @link https://trac.cakephp.org/wiki/Authors Authors/Developers
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
 * Basic defines
 */
define('SECOND',  1);
define('MINUTE', 60 * SECOND);
define('HOUR',   60 * MINUTE);
define('DAY',    24 * HOUR);
define('WEEK',    7 * DAY);
define('MONTH',  30 * DAY);
define('YEAR',  365 * DAY);

/**
 * Loads all models.
 *
 * @uses listModules()
 * @uses APP
 * @uses MODELS
 */
function loadModels () 
{
	require (APP.'app_model.php');
	foreach (listClasses(MODELS) as $model_fn) 
	{
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
function loadControllers () 
{
	require (APP.'app_controller.php');

	foreach (listClasses(HELPERS) as $helper) 
	{
		require (HELPERS.$helper.'.php');
	}

	foreach (listClasses(CONTROLLERS) as $controller) 
	{
		require (CONTROLLERS.$controller.'.php');
	}
}

/**
  * Loads a controller and its helper libraries.
  *
  * @param string $name
  * @return boolean
  */
function loadController ($name) 
{
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
function listClasses($path) 
{
	$modules = new Folder($path);
	return $modules->find('(.+)\.php');
}

/**
  * Loads configuration files
  */
function config () 
{
	$args = func_get_args();
	foreach ($args as $arg) 
	{
		if (('database' == $arg) && file_exists(CONFIGS.$arg.'.php'))
		{
			include_once(CONFIGS.$arg.'.php');
		}
		elseif (file_exists(CONFIGS.$arg.'.php')) 
		{
			include (CONFIGS.$arg.'.php');
			if (count($args) == 1) return true;
		}
		else 
		{
			if (count($args) == 1) return false;
		}
	}
	
	return true;
}

/**
 * Loads component/components from LIBS.
 *
 * Example:
 * <code>
 * uses('flay', 'time');
 * </code>
 *
 * @uses LIBS
 */
function uses ()
{
	$args = func_get_args();
	foreach ($args as $arg) 
	{
		require_once(LIBS.strtolower($arg).'.php');
	}
}

function vendor($name)
{
	$args = func_get_args();
	foreach ($args as $arg) 
	{
		require_once(VENDORS.$arg.'.php');
	}
}

/**
 * Setup a debug point.
 *
 * @param boolean $var
 * @param boolean $show_html
 */
function debug($var = false, $show_html = false) 
{
	if (DEBUG) 
	{
		print "\n<pre>\n";
		if ($show_html) $var = str_replace('<', '&lt;', str_replace('>', '&gt;', $var));
		print_r($var);
		print "\n</pre>\n";
	}
}


if (!function_exists('getMicrotime')) 
{
/**
 * Returns microtime for execution time checking.
 *
 * @return integer
 */
	function getMicrotime() 
	{
		list($usec, $sec) = explode(" ", microtime());
		return ((float)$usec + (float)$sec);
	}
}

if (!function_exists('sortByKey')) 
{
/**
 * Sorts given $array by key $sortby.
 *
 * @param array $array
 * @param string $sortby
 * @param string $order Sort order asc/desc (ascending or descending).
 * @param integer $type
 * @return mixed
 */
	function sortByKey(&$array, $sortby, $order='asc', $type=SORT_NUMERIC) 
	{
		if (!is_array($array))
			return null;

		foreach ($array as $key => $val)
		{
			$sa[$key] = $val[$sortby];
		}

		$order == 'asc'
			? asort($sa, $type)
			: arsort($sa, $type);

		foreach ($sa as $key=>$val)
		{
			$out[] = $array[$key];
		}

		return $out;
	}
}

if (!function_exists('array_combine')) 
{
/**
 * Combines given identical arrays by using the first array's values as keys,
 * and the second one's values as values. (Implemented for back-compatibility with PHP4.)
 *
 * @param array $a1
 * @param array $a2
 * @return mixed Outputs either combined array or false.
 */
	function array_combine($a1, $a2) 
	{
		$a1 = array_values($a1);
		$a2 = array_values($a2);
		$c1 = count($a1);
		$c2 = count($a2);

		if ($c1 != $c2) return false; // different lenghts
		if ($c1 <= 0)   return false; // arrays are the same and both are empty
		
		$output = array();
		
		for ($i = 0; $i < $c1; $i++) 
		{
			$output[$a1[$i]] = $a2[$i];
		}
		
		return $output;
	}
}

?>
