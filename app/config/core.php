<?php
/* SVN FILE: $Id$ */
/**
 * This is core configuration file.
 *
 * Use it to configure core behaviour ofCake.
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) :  Rapid Development Framework <http://www.cakephp.org/>
 * Copyright 2005-2007, Cake Software Foundation, Inc.
 *								1785 E. Sahara Avenue, Suite 490-204
 *								Las Vegas, Nevada 89104
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @filesource
 * @copyright		Copyright 2005-2007, Cake Software Foundation, Inc.
 * @link				http://www.cakefoundation.org/projects/info/cakephp CakePHP(tm) Project
 * @package			cake
 * @subpackage		cake.app.config
 * @since			CakePHP(tm) v 0.2.9
 * @version			$Revision$
 * @modifiedby		$LastChangedBy$
 * @lastmodified	$Date$
 * @license			http://www.opensource.org/licenses/mit-license.php The MIT License
 */
/**
 * To configure CakePHP *not* to use mod_rewrite and to
 * use CakePHP pretty URLs, remove these .htaccess
 * files:
 *
 * /.htaccess
 * /app/.htaccess
 * /app/webroot/.htaccess
 *
 * And uncomment the define below:
 */

//	define ('BASE_URL', env('SCRIPT_NAME'));

/**
 * CakePHP Debug Level:
 *
 * Production Mode:
 * 	0: No error messages, errors, or warnings shown. Flash messages redirect.
 *
 * Development Mode:
 * 	1: Errors and warnings shown, model caches refreshed, flash messages halted.
 * 	2: As in 1, but also with full debug messages and SQL output.
 * 	3: As in 2, but also with full controller dump.
 *
 * In production mode, flash messages redirect after a time interval.
 * In development mode, you need to click the flash message to continue.
 */
	define('DEBUG', 2);
/**
 * Turn off or enable cache checking application-wide.
 *
 * If set to true, you must still use the controller var $cacheAction inside
 * your controllers to define caching settings. You can either set it
 * controller-wide by setting var $cacheAction = true, or in each action
 * using $this->cacheAction = true.
 */
	define('CACHE_CHECK', false);
/**
 * Defines the default error type when using the log() function. Used for
 * differentiating error logging and debugging. Currently PHP supports LOG_DEBUG.
 */
	define('LOG_ERROR', 2);
/**
 * The preferred session handling method. Valid values:
 *
 * 'php'	 		Uses settings defined in your php.ini.
 * 'cake'		Saves session files in CakePHP's /tmp directory.
 * 'database'	Uses CakePHP's database sessions.
 *
 * To define a custom session handler, save it at /app/config/<name>.php.
 * Set the value of CAKE_SESSION_SAVE to <name> to utilize it in CakePHP.
 *
 * To use database sessions, execute the SQL file found at /app/config/sql/sessions.sql.
 *
 */
	define('CAKE_SESSION_SAVE', 'php');
/**
 * The name of the table used to store CakePHP database sessions.
 *
 * CAKE_SESSION_SAVE must be set to 'database' in order to utilize this constant.
 *
 * The table name set here should *not* include any table prefix defined elsewhere.
 */
	define('CAKE_SESSION_TABLE', 'cake_sessions');
/**
 * A random string used in session management.
 */
	define('CAKE_SESSION_STRING', 'DYhG93b0qyJfIxfs2guVoUubWwvniR2G0FgaC9mi');
/**
 * The name of CakePHP's session cookie.
 */
	define('CAKE_SESSION_COOKIE', 'CAKEPHP');
/**
 * The level of CakePHP session security. The session timeout time defined
 * in CAKE_SESSION_TIMEOUT is multiplied according to the settings here.
 * Valid values:
 *
 * 'high'	Session timeout in CAKE_SESSION_TIMEOUT x 10
 * 'medium'	Session timeout in CAKE_SESSION_TIMEOUT x 100
 * 'low'		Session timeout in CAKE_SESSION_TIMEOUT x 300
 *
 * CakePHP session IDs are also regenerated between requests if
 * CAKE_SECURITY is set to 'high'.
 */
	define('CAKE_SECURITY', 'high');
/**
 * Session time out time (in seconds).
 * Actual value depends on CAKE_SECURITY setting.
 */
	define('CAKE_SESSION_TIMEOUT', '120');
/**
 * Uncomment the define below to use CakePHP admin routes.
 *
 * The value of the define determines the name of the route
 * and its associated controller actions:
 *
 * 'admin' 		-> admin_index() and /admin/controller/index
 * 'superuser' -> superuser_index() and /superuser/controller/index
 */
//	define('CAKE_ADMIN', 'admin');
/**
 *  Enable or disable CakePHP webservices routing. Set to 'off' or 'on'.
 */
	define('WEBSERVICES', 'off');
/**
 * Compress CSS output by removing comments, whitespace, repeating tags, etc.
 * This requires a/var/cache directory to be writable by the web server for caching.
 *
 * To use, prefix the CSS link URL with '/ccss/' instead of '/css/' or use Controller::cssTag().
 */
	define('COMPRESS_CSS', false);
/**
 * If set to false, sessions are not automatically started.
 */
	define('AUTO_SESSION', true);
/**
 * The max size of file allowed for MD5 hashes (in bytes).
 */
	define('MAX_MD5SIZE', (5 * 1024) * 1024);
/**
 * The classname and database used in CakePHP's
 * access control lists.
 */
	define('ACL_CLASSNAME', 'DB_ACL');
	define('ACL_DATABASE', 'default');
/**
 * How long to cache data if not defined
 * 3600 = 1 hour
 */
	define('CACHE_DEFAULT_DURATION', 3600);
/**
 * How often to do garbage collection
 * about once in every hundred page loads
 */
	define('CACHE_GC_PROBABILITY', 100);
/**
 * Use the file storage engine with default parameters.
 * Cached data is kept in app/tmp/cache/
 *
 * File storage
 * 	$cakeCache = array('File', [optional]array(
 * 		'dir' => '/tmp/', // use system tmp directory - remember to use absolute path
 * 		'prefix' => 'cakecache_', // prefix every cache file with this string
 * 		'lock' => true, // use file locking
 * 		));
 * 	$cakeCache = array('File');
 *
 * APC (Alternative PHP Cache)
 * 	$cakeCache = array('APC');
 *
 * Xcache (PHP opcode cacher)
 *  $cakeCache  = array('Xcache', array(
 *      'user' => 'admin', // user from xcache.admin.user settings
 *      'password' => 'your_password', // plaintext password (xcache.admin.pass)
 *  ));
 *
 * Memcache
 * 	$cakeCache = array('Memcache', [optional]array(
 * 		'servers' => array(
 * 				'127.0.0.1', // localhost, default port
 * 				'10.0.0.1:12345', // port 12345
 * 				),
 * 				'compress' => true, // compress data in Memcache (slower, but uses less memory)
 * 				));
 *
 * Cake Model
 * 	$cakeCache = array('Model', [optional]array(
 * 		'modelName' => 'DbModel',
 * 		'dataField' => 'value',
 * 		'expiryField' => 'expires'));
 */
	$cakeCache = array('File');
?>