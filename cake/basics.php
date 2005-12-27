<?php
/* SVN FILE: $Id$ */

/**
 * Basic Cake functionality.
 *
 * Core functions for including other source files, loading models and so forth.
 *
 * PHP versions 4 and 5
 *
 * CakePHP :  Rapid Development Framework <http://www.cakephp.org/>
 * Copyright (c) 2005, Cake Software Foundation, Inc.
 *                     1785 E. Sahara Avenue, Suite 490-204
 *                     Las Vegas, Nevada 89104
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @filesource
 * @copyright    Copyright (c) 2005, Cake Software Foundation, Inc.
 * @link         http://www.cakefoundation.org/projects/info/cakephp CakePHP Project
 * @package      cake
 * @subpackage   cake.cake
 * @since        CakePHP v 0.2.9
 * @version      $Revision$
 * @modifiedby   $LastChangedBy$
 * @lastmodified $Date$
 * @license      http://www.opensource.org/licenses/mit-license.php The MIT License
 */

/**
 * Basic defines for timing functions.
 */
define('SECOND',  1);
define('MINUTE', 60 * SECOND);
define('HOUR',   60 * MINUTE);
define('DAY',    24 * HOUR);
define('WEEK',    7 * DAY);
define('MONTH',  30 * DAY);
define('YEAR',  365 * DAY);

/**
 * Patch for PHP < 4.3
 */
if (!function_exists("ob_get_clean"))
{
    function ob_get_clean()
    {
        $ob_contents = ob_get_contents();
        ob_end_clean();
        return $ob_contents;
    }
}

/**
 * Loads all models.
 *
 * @uses listModules()
 * @uses APP
 * @uses MODELS
 */
function loadModels ()
{
    if(!class_exists('AppModel', FALSE))
    {
        if(file_exists(APP.'app_model.php'))
        {
            require_once(APP.'app_model.php');
        }
        else
        {
            require_once(CAKE.'app_model.php');
        }
        foreach (listClasses(MODELS) as $model_fn)
        {
            require_once (MODELS.$model_fn);
        }
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
    if(!class_exists('AppController', FALSE))
    {
        if(file_exists(APP.'app_controller.php'))
        {
            require_once(APP.'app_controller.php');
        }
        else
        {
            require_once(CAKE.'app_controller.php');
        }
    }
    foreach (listClasses(CONTROLLERS) as $controller)
    {
        if(!class_exists($controller, FALSE))
        {
            require_once (CONTROLLERS.$controller.'.php');
        }
    }
}

/**
  * Loads a controller and its helper libraries.
  *
  * @param string $name Name of controller
  * @return boolean Success
  */
function loadController ($name)
{
    if(!class_exists('AppController', FALSE))
    {
        if(file_exists(APP.'app_controller.php'))
        {
            require_once(APP.'app_controller.php');
        }
        else
        {
            require_once(CAKE.'app_controller.php');
        }
    }
    if(!class_exists($name, FALSE))
    {
        $name = Inflector::underscore($name);
        if(file_exists(CONTROLLERS.$name.'_controller.php'))
        {
            $controller_fn = CONTROLLERS.$name.'_controller.php';
        }
        elseif(file_exists(LIBS.'controller'.DS.$name.'_controller.php'))
        {
            $controller_fn = LIBS.'controller'.DS.$name.'_controller.php';
        }
        else
        {
            $controller_fn = false;
        }
        return file_exists($controller_fn)? require_once($controller_fn): false;
    }
}

/**
  * Lists PHP files in given directory.
  *
  * @param string $path     Path to scan for files
  * @return array             List of files in directory
  */
function listClasses($path)
{
   $modules = new Folder($path);
   return $modules->find('(.+)\.php');
}

/**
  * Loads configuration files
  *
  * @return boolean Success
  */
function config ()
{
   $args = func_get_args();
   $count = count($args);
   foreach ($args as $arg)
   {
      if (('database' == $arg) && file_exists(CONFIGS.$arg.'.php'))
      {
         include_once(CONFIGS.$arg.'.php');
      }
      elseif (file_exists(CONFIGS.$arg.'.php'))
      {
         include_once (CONFIGS.$arg.'.php');
         if ($count == 1) return true;
      }
      else
      {
         if ($count == 1) return false;
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

/**
 * Require given files in the VENDORS directory. Takes optional number of parameters.
 *
 * @param string $name Filename without the .php part.
 *
 */
function vendor($name)
{
   $args = func_get_args();
   foreach ($args as $arg)
   {
      require_once(VENDORS.$arg.'.php');
   }
}

/**
 * Print out debug information about given variable.
 *
 * Only runs if DEBUG level is non-zero.
 *
 * @param boolean $var        Variable to show debug information for.
 * @param boolean $show_html    If set to true, the method prints the debug data in a screen-friendly way.
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
    {
        return null;
    }
    foreach ($array as $key => $val)
    {
        $sa[$key] = $val[$sortby];
    }
    if($order == 'asc')
    {
        asort($sa, $type);
    }
    else
    {
        arsort($sa, $type);
    }
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

    if ($c1 != $c2)
    {
        return false; // different lenghts
    }
    if ($c1 <= 0)
    {
        return false; // arrays are the same and both are empty
    }

    $output = array();
    for ($i = 0; $i < $c1; $i++)
    {
        $output[$a1[$i]] = $a2[$i];
    }
    return $output;
}
}

/**
 * Convenience method for htmlspecialchars.
 *
 * @param string $text
 * @return string
 */
function h($text)
{
    return htmlspecialchars($text);
}

/**
 * Returns an array of all the given parameters, making parameter lists shorter to write.
 *
 * @return array
 */
function a()
{
    $args = func_get_args();
    return $args;
}

/**
 * Hierarchical arrays.
 *
 * @return array
 * @todo Explain this method better.
 */
function ha()
{
    $args = func_get_args();
    $count = count($args);

    for($i=0 ; $i < $count ; $i++)
    {
        if($i+1 < $count)
        {
           $a[$args[$i]] =  $args[$i+1];
        }
        else
        {
            $a[$args[$i]] = null;
        }
    }
    return $a;
}

/**
 * Convenience method for echo().
 *
 * @param string $text String to echo
 */
function e($text)
{
    echo $text;
}

/**
 * Print_r convenience function, which prints out <PRE> tags around
 * the output of given array. Similar to debug().
 *
 * @see debug
 * @param array $var
 */
function pr($var)
{
    if(DEBUG > 0)
    {
        echo "<pre>";
        print_r($var);
        echo "</pre>";
    }
}

/**
 * Display parameter
 *
 * @param mixed $p Parameter as string or array
 * @return string
 */
function params($p)
{

    if(!is_array($p) || count($p) == 0)
    {
        return null;
    }
    else
    {
        if(is_array($p[0]) && count($p) == 1)
        {
            return $p[0];
        }
        else
        {
            return $p;
        }
    }

}

/**
 * Returns the REQUEST_URI from the server environment, or, failing that, constructs
 * a new one, using the PHP_SELF constant and other variables.
 *
 * @return string
 */
function setUri()
{
    if (isset($_SERVER['REQUEST_URI']))
    {
        $uri = $_SERVER['REQUEST_URI'];
    }
    else
    {
        if (isset($_SERVER['argv']))
        {
            $uri = $_SERVER['PHP_SELF'] .'/'. $_SERVER['argv'][0];
        }
        else
        {
            $uri = $_SERVER['PHP_SELF'] .'/'. $_SERVER['QUERY_STRING'];
        }
    }
    return $uri;
}
?>