<?PHP
//////////////////////////////////////////////////////////////////////////
// + $Id$
// +------------------------------------------------------------------+ //
// + Cake <http://sputnik.pl/cake>                                    + //
// + Copyright: (c) 2005 Michal Tatarynowicz                          + //
// +                                                                  + //
// + Author(s): (c) 2005 Michal Tatarynowicz <tatarynowicz@gmail.com> + //
// +                                                                  + //
// +------------------------------------------------------------------+ //
// + Licensed under the Public Domain Licence                         + //
// +------------------------------------------------------------------+ //
//////////////////////////////////////////////////////////////////////////

/**
  * Purpose: Basics
  * Basic Cake functionalities.
  * 
  * @filesource 
  * @modifiedby $LastChangedBy$  
  * @lastmodified $Date$
  * @author Michal Tatarynowicz <tatarynowicz@gmail.com>
  * @copyright Copyright (c) 2005, Michal Tatarynowicz <tatarynowicz@gmail.com>
  * @package cake
  * @subpackage cake.libs
  * @since Cake v 0.2.9
  * @version $Revision$
  * @license Public_Domain
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
function uses_database () {
    global $DB;

    if (file_exists(CONFIGS.'database.php')) {
        require (CONFIGS.'database.php');
        $DB = new DBO ($DATABASE_CONFIG['devel'], DEBUG > 1);
    }
}

/**
  * Enter description here...
  *
  */
function uses_tags () {
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
    function find_in ($field_name, $value) {
        $out = false;
        foreach ($this->value as $k=>$v) {
            if (isset($v[$field_name]) && ($v[$field_name] == $value)) {
                $out[$k] = $v;
            }
        }

        return $out;
    }
}

?>