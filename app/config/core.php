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
 * Copyright (c)	2006, Cake Software Foundation, Inc.
 *								1785 E. Sahara Avenue, Suite 490-204
 *								Las Vegas, Nevada 89104
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @filesource
 * @copyright		Copyright (c) 2006, Cake Software Foundation, Inc.
 * @link				http://www.cakefoundation.org/projects/info/cakephp CakePHP Project
 * @package			cake
 * @subpackage		cake.app.config
 * @since			CakePHP v 0.2.9
 * @version			$Revision$
 * @modifiedby		$LastChangedBy$
 * @lastmodified	$Date$
 * @license			http://www.opensource.org/licenses/mit-license.php The MIT License
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
//	define ('BASE_URL', env('SCRIPT_NAME'));
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
 * Turn of caching checking wide.
 * You must still use the controller var cacheAction inside you controller class.
 * You can either set it controller wide, or in each controller method.
 * use var $cacheAction = true; or in the controller method $this->cacheAction = true;
 */
	define('CACHE_CHECK', false);
/**
 * Error constant. Used for differentiating error logging and debugging.
 * Currently PHP supports LOG_DEBUG
 */
	define('LOG_ERROR', 2);
/**
 * CakePHP includes 3 types of session saves
 * database or file. Set this to your preferred method.
 * If you want to use your own save handler place it in
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
 * If using you own table name for storing sessions
 * set the table name here.
 * DO NOT INCLUDE PREFIX IF YOU HAVE SET ONE IN database.php
 *
 */
	define('CAKE_SESSION_TABLE', 'cake_sessions');
/**
 * Set a random string of used in session.
 *
 */
	define('CAKE_SESSION_STRING', 'DYhG93b0qyJfIxfs2guVoUubWwvniR2G0FgaC9mi');
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
 * Set Cake Session time out.
 * If CAKE_SECURITY define is set
 * high: multiplied by 10
 * medium: is multiplied by 100
 * low is: multiplied by 300
 *
 *  Number below is seconds.
 */
	define('CAKE_SESSION_TIMEOUT', '120');
/**
 * Uncomment the define below to use cake built in admin routes.
 * You can set this value to anything you want.
 * All methods related to the admin route should be prefixed with the
 * name you set CAKE_ADMIN to.
 * For example: admin_index, admin_edit
 */
//	define('CAKE_ADMIN', 'admin');
/**
 *  The define below is used to turn cake built webservices
 *  on or off. Default setting is off.
 */
	define('WEBSERVICES', 'off');
/**
 * Compress output CSS (removing comments, whitespace, repeating tags etc.)
 * This requires a/var/cache directory to be writable by the web server (caching).
 * To use, prefix the CSS link URL with '/ccss/' instead of '/css/' or use Controller::cssTag().
 */
	define('COMPRESS_CSS', false);
/**
 * If set to false, session would not automatically be started.
 */
	define('AUTO_SESSION', true);
/**
 * Set the max size of file to use md5() .
 */
	define('MAX_MD5SIZE', (5 * 1024) * 1024);
/**
 * To use Access Control Lists with Cake...
 */
	define('ACL_CLASSNAME', 'DB_ACL');
	define('ACL_FILENAME', 'dbacl' . DS . 'db_acl');
?>