<?php
/* SVN FILE: $Id$ */

/**
 * Short description for file.
 * 
 * In this file you set paths to different directories used by Cake.
 *
 * PHP versions 4 and 5
 *
 * CakePHP :  Rapid Development Framework <http://www.cakephp.org/>
 * Copyright (c) 2005, CakePHP Authors/Developers
 *
 * Author(s): Michal Tatarynowicz aka Pies <tatarynowicz@gmail.com>
 *            Larry E. Masters aka PhpNut <nut@phpnut.com>
 *            Kamil Dzielinski aka Brego <brego.dk@gmail.com>
 *
 *  Licensed under The MIT License
 *  Redistributions of files must retain the above copyright notice.
 *
 * @filesource 
 * @author       CakePHP Authors/Developers
 * @copyright    Copyright (c) 2005, CakePHP Authors/Developers
 * @link         https://trac.cakephp.org/wiki/Authors Authors/Developers
 * @package      cake
 * @subpackage   cake.config
 * @since        CakePHP v 0.2.9
 * @version      $Revision$
 * @modifiedby   $LastChangedBy$
 * @lastmodified $Date$
 * @license      http://www.opensource.org/licenses/mit-license.php The MIT License
 */

/**
 * If the index.php file is used instead of an .htaccess file
 * or if the user can not set the web root to use the public
 * directory we will define ROOT there, otherwise we set it
 * here.
 */
if(!defined('ROOT'))
{
   define ('ROOT',   '../');
}

if(!defined('WEBROOT_DIR'))
{
   define ('WEBROOT_DIR',   'webroot');
}

/**
 * Path to the application's directory.
 */
define ('CAKE',         ROOT.'cake'.DS);

/**
 * Path to the application's directory.
 */
define ('APP',         ROOT.APP_DIR.DS);

/**
 * Path to the application's models directory.
 */
define ('MODELS',          APP.'models'.DS);

/**
 * Path to the application's controllers directory.
 */
define ('CONTROLLERS',     APP.'controllers'.DS);

/**
 * Path to the application's views directory.
 */
define ('VIEWS',           APP.'views'.DS);

/**
 * Path to the application's helpers directory.
 */
define ('HELPERS',         VIEWS.'helpers'.DS);

/**
 * Path to the application's view's layouts directory.
 */
define ('LAYOUTS',         APP.'views'.DS.'layouts'.DS);

/**
 * Path to the application's view's elements directory.
 * It's supposed to hold pieces of PHP/HTML that are used on multiple pages
 * and are not linked to a particular layout (like polls, footers and so on).
 */
define ('ELEMENTS',        APP.'views'.DS.'elements'.DS);

/**
 * Path to the configuration files directory.
 */
define ('CONFIGS',     APP.'config'.DS);

/**
 * Path to the libs directory.
 */
define ('LIBS',        CAKE.'libs'.DS);

/**
 * Path to the logs directory.
 */
define ('LOGS',        ROOT.'logs'.DS);

/**
 * Path to the modules directory.
 */
define ('MODULES',     ROOT.'modules'.DS);

/**
 * Path to the public directory.
 */
define ('WWW_ROOT',    APP.WEBROOT_DIR.DS);

/**
 * Path to the public directory.
 */
define ('CSS',            WWW_ROOT.'css'.DS);

/**
 * Path to the public directory.
 */
define ('JS',            WWW_ROOT.'js'.DS);

/**
 * Path to the scripts direcotry.
 */
define('SCRIPTS',      CAKE.'scripts'.DS);

/**
 * Path to the tests directory.
 */
define ('TESTS',       ROOT.'tests'.DS);

/**
 * Path to the controller test directory.
 */
define ('CONTROLLER_TESTS',TESTS.APP_DIR.'controllers'.DS);

/**
 * Path to the helpers test directory.
 */
define ('HELPER_TESTS',    TESTS.APP_DIR.'helpers'.DS);

/**
 * Path to the models' test directory.
 */
define ('MODEL_TESTS',     TESTS.APP_DIR.'models'.DS);

/**
 * Path to the lib test directory.
 */
define ('LIB_TESTS',       TESTS.'libs'.DS);

/**
 * Path to the temporary files directory.
 */
define ('TMP',     ROOT.'tmp'.DS);

/**
 * Path to the cache files directory. It can be shared between hosts in a multi-server setup.
 */
define('CACHE', TMP.'cache'.DS);

/**
 * Path to the vendors directory.
 */
define ('VENDORS',     ROOT.'vendors'.DS);

/**
 * Path to the Pear directory
 * The purporse is to make it easy porting Pear libs into Cake
 * without setting the include_path PHP variable.
 */
define ('PEAR',            VENDORS.'Pear'.DS);

/**
 *  Full url prefix
 */
define('FULL_BASE_URL', 'http://'.$_SERVER['HTTP_HOST']);

/**
 * Web path to the public images directory.
 */
define ('IMAGES_URL',          'img/');

/**
 * Web path to the CSS files directory.
 */
define ('CSS_URL',            'css/');

/**
 * Web path to the js files directory.
 */
define ('JS_URL',            'js/');


?>