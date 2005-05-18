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
 * Loads all libs from LIBS directory.
 *
 * @uses listModules()
 * @uses LIBS
 */
function loadLibs () {
	foreach (listModules(LIBS) as $lib) {
		if ($lib != 'basics') {
			include_once (LIBS.$lib.'.php');
		}
	}
}

/**
 * Loads all models.
 *
 * @uses listModules()
 * @uses APP
 * @uses MODELS
 */
function loadModels () {
	require (APP.'app_model.php');
	foreach (listModules(MODELS) as $model) {
		require (MODELS.$model.'.php');
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

	foreach (listModules(HELPERS) as $helper) {
		require (HELPERS.$helper.'.php');
	}

	foreach (listModules(CONTROLLERS) as $controller) {
		require (CONTROLLERS.$controller.'.php');
	}
}

/**
 * Lists all .php files from a given path.
 *
 * @param string $path
 * @param boolean $sort
 * @return array
 */
function listModules($path, $sort=true) {
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
 * Loads core config.
 *
 * @uses $TIME_START
 * @uses CONFIGS
 */
function usesConfig () {
	global $TIME_START;

	require (CONFIGS.'core.php');
}

/**
 * Loads database connection identified by $level.
 *
 * @param string $level
 * @uses $DB
 * @uses DbFactory::make()
 * @uses loadDatabaseConfig()
 */
function usesDatabase ($level='devel') {
	global $DB;

	$DB = DbFactory::make(loadDatabaseConfig($level));
}

/**
 * Loads database configuration identified by $level from CONFIGS/database.php.
 *
 * @param string $level
 * @return mixed
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
 * Loads tags configuration from CONFIGS/tags.php.
 *
 * @uses CONFIGS
 */
function usesTagGenerator () {
	require (CONFIGS.'tags.php');
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
 * @param string $order
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
 * and second one's values as values.
 *
 * @param array $a1
 * @param array $a2
 * @return mixed Outputs either combined array or false.
 */
	function array_combine($a1, $a2) {
		$a1 = array_values($a1);
		$a2 = array_values($a2);

		if (count($a1) != count($a2)) return false; // different lenghts
		if (count($a1) <= 0) return false; // arrays are the same and both are empty
		
		$output = array();
		
		for ($i = 0, $c = count($a1); $i < $c; $i++) {
			$output[$a1[$i]] = $a2[$i];
		}
		
		return $output;
	}
}

/**
 * Class used for internal manipulation with recordsets (?).
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
	 * Constructor.
	 *
	 * @param array $value
	 * @access public
	 * @uses NeatArray::value
	 */
	function NeatArray ($value) {
		$this->value = $value;
	}

	/**
	 * Checks wheter $fieldName with $value exists in this NeatArray object.
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
	function cleanup ()
	{
		$out = is_array($this->value)? array(): null;
		foreach ($this->value as $k=>$v)
		{
			if ($v)
			{
				$out[$k] = $v;
			}
		}
		$this->value = $out;
	}
}

?>
