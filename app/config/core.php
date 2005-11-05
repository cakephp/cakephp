<?php
/* SVN FILE: $Id$ */

/**
 * This is core configuration file. 
 * 
 * Use it to configure core behaviour ofCake.
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
 * @subpackage   cake.cake.app.config
 * @since        CakePHP v 0.2.9
 * @version      $Revision$
 * @modifiedby   $LastChangedBy$
 * @lastmodified $Date$
 * @license      http://www.opensource.org/licenses/mit-license.php The MIT License
 */

/**
 * If you do not have mod rewrite on your system
 * or if you prefer to use CakePHP pretty urls.
 * uncomment the line below.
 * Note: If you do have mod rewrite but prefer the
 * CakePHP pretty urls, you also have to remove the
 * .htaccess files  
 * release/.htaccess 
 * release/app/.htaccess  
 * release/app/webroot/.htaccess 
 */
//define ('BASE_URL', $_SERVER['SCRIPT_NAME']);


/**
 * Set debug level here:
 * - 0: production
 * - 1: development
 * - 2: full debug with sql
 * - 3: full debug with sql and dump of the current object
 *
 * In production, the "flash messages" redirect after a time interval. 
 * With the other debug levels you get to click the "flash message" to continue.
 *
 */
define('DEBUG', 1);
/**
 * Set the name of session cookie
 *
 */
define('CAKE_SESSION_COOKIE', 'CAKEPHP');

/**
 * Set level of Cake security.
 *
 */
define('CAKE_SECURITY', 'high');

/**
 * CakePHP includes 3 types of session saves
 * database or file. Set this to your preffered method.
 * If you want to use your own save handeler place it in
 * app/config/name.php DO NOT USE file or database as the name.
 * and use just the name portion below.
 *
 * Setting this to cake will save files to /cakedistro/tmp directory
 * Setting it to php will use the php default save path
 * Setting it to database will use the database
 * 
 *
 */
define('CAKE_SESSION_SAVE', 'php');

/**
 * Uncomment the define below to use cake built in admin routes.
 * You can set this value to anything you want.
 * All methods related to the admin route should be prefixed with the
 * name you set CAKE_ADMIN to.
 * For example: admin_index, admin_edit
 */
//define('CAKE_ADMIN', 'admin');

/**
 * Compress output CSS (removing comments, whitespace, repeating tags etc.)
 * This requires a /var/cache directory to be writable by the web server (caching).
 * To use, prefix the CSS link URL with '/ccss/' instead of '/css/' or use Controller::cssTag().
 */
define('COMPRESS_CSS', false);

/**
 * If set to true, helpers would output data instead of returning it.
 */
define('AUTO_OUTPUT', false);

/**
 * If set to false, session would not automatically be started.
 */
define('AUTO_SESSION', true);

/**
 * Set the max size of file to use md5() .
 */
define('MAX_MD5SIZE', (5*1024)*1024 );

/**
 * To use Access Control Lists with Cake...
 */
define('ACL_CLASSNAME',  'DB_ACL');
define('ACL_FILENAME',   'dbacl'.DS.'db_acl');

?>