<?php
/* SVN FILE: $Id$ */

/**
 * Short description for file.
 *
 * Long description for file
 *
 * PHP versions 4 and 5
 *
 * CakePHP :  Rapid Development Framework <http://www.cakephp.org/>
 * Copyright (c) 2006, Cake Software Foundation, Inc.
 *                     1785 E. Sahara Avenue, Suite 490-204
 *                     Las Vegas, Nevada 89104
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @filesource
 * @copyright    Copyright (c) 2006, Cake Software Foundation, Inc.
 * @link         http://www.cakefoundation.org/projects/info/cakephp CakePHP Project
 * @package      cake
 * @subpackage   cake.cake.app.webroot
 * @since        CakePHP v 0.2.9
 * @version      $Revision$
 * @modifiedby   $LastChangedBy$
 * @lastmodified $Date$
 * @license      http://www.opensource.org/licenses/mit-license.php The MIT License
 */

/**
 * These defines should only be edited if you have cake installed in
 * a directory layout other than the way it is distributed.
 * Each define has a commented line of code that explains what you would change.
 *
 */

if (!defined('ROOT'))
{
//define('ROOT', 'FULL PATH TO DIRECTORY WHERE APP DIRECTORY IS LOCATED DO NOT ADD A TRAILING DIRECTORY SEPARATOR';
    define('ROOT', dirname(dirname(dirname(__FILE__))));
}

if (!defined('APP_DIR'))
{
//define('APP_DIR', 'DIRECTORY NAME OF APPLICATION';
    define ('APP_DIR', basename(dirname(dirname(__FILE__))));
}

/**
 * This only needs to be changed if the cake installed libs are located
 * outside of the distributed directory structure.
 */
if (!defined('CAKE_CORE_INCLUDE_PATH'))
{
//define ('CAKE_CORE_INCLUDE_PATH', FULL PATH TO DIRECTORY WHERE CAKE CORE IS INSTALLED DO NOT ADD A TRAILING DIRECTORY SEPARATOR';
    define('CAKE_CORE_INCLUDE_PATH', ROOT);
}

///////////////////////////////
//DO NOT EDIT BELOW THIS LINE//
///////////////////////////////
if (!defined('DS'))
{
    define('DS', DIRECTORY_SEPARATOR);
}

if (!defined('WEBROOT_DIR'))
{
    define ('WEBROOT_DIR', basename(dirname(__FILE__)));
}

define('WWW_ROOT', dirname(__FILE__));

ini_set('include_path',ini_get('include_path').PATH_SEPARATOR.CAKE_CORE_INCLUDE_PATH.PATH_SEPARATOR.ROOT.DS.APP_DIR.DS);

require 'cake'.DS.'bootstrap.php';

if(isset($_GET['url']) && $_GET['url'] === 'favicon.ico')
{
}
else
{
    $Dispatcher= new Dispatcher ();
    $Dispatcher->dispatch($url);
}

if (DEBUG)
{
    echo "<!-- ". round(getMicrotime() - $TIME_START, 2) ."s -->";
}
?>