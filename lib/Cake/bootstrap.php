<?php
/**
 * Basic Cake functionality.
 *
 * Handles loading of core files needed on every request
 *
 * PHP 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       Cake
 * @since         CakePHP(tm) v 0.2.9
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
define('TIME_START', microtime(true));

if (!defined('E_DEPRECATED')) {
	define('E_DEPRECATED', 8192);
}

if (!defined('E_USER_DEPRECATED')) {
	define('E_USER_DEPRECATED', E_USER_NOTICE);
}
error_reporting(E_ALL & ~E_DEPRECATED);

if (!defined('CAKE_CORE_INCLUDE_PATH')) {
	define('CAKE_CORE_INCLUDE_PATH', dirname(dirname(__FILE__)));
}

if (!defined('CORE_PATH')) {
	define('CORE_PATH', CAKE_CORE_INCLUDE_PATH . DS);
}

if (!defined('WEBROOT_DIR')) {
	define('WEBROOT_DIR', 'webroot');
}

/**
 * Path to the cake directory.
 */
	define('CAKE', CORE_PATH . 'Cake' . DS);

/**
 * Path to the application's directory.
 */
if (!defined('APP')) {
	define('APP', ROOT . DS . APP_DIR . DS);
}

/**
 * Path to the application's libs directory.
 */
	define('APPLIBS', APP . 'Lib' . DS);

/**
 * Path to the public CSS directory.
 */
	define('CSS', WWW_ROOT . 'css' . DS);

/**
 * Path to the public JavaScript directory.
 */
	define('JS', WWW_ROOT . 'js' . DS);

/**
 * Path to the public images directory.
 */
	define('IMAGES', WWW_ROOT . 'img' . DS);

/**
 * Path to the tests directory.
 */
if (!defined('TESTS')) {
	define('TESTS', APP . 'Test' . DS);
}

/**
 * Path to the temporary files directory.
 */
if (!defined('TMP')) {
	define('TMP', APP . 'tmp' . DS);
}

/**
 * Path to the logs directory.
 */
if (!defined('LOGS')) {
	define('LOGS', TMP . 'logs' . DS);
}

/**
 * Path to the cache files directory. It can be shared between hosts in a multi-server setup.
 */
if (!defined('CACHE')) {
	define('CACHE', TMP . 'cache' . DS);
}

/**
 * Path to the vendors directory.
 */
if (!defined('VENDORS')) {
	define('VENDORS', ROOT . DS . 'vendors' . DS);
}

/**
 * Web path to the public images directory.
 */
if (!defined('IMAGES_URL')) {
	define('IMAGES_URL', 'img/');
}

/**
 * Web path to the CSS files directory.
 */
if (!defined('CSS_URL')) {
	define('CSS_URL', 'css/');
}

/**
 * Web path to the js files directory.
 */
if (!defined('JS_URL')) {
	define('JS_URL', 'js/');
}


require CAKE . 'basics.php';
require CAKE . 'Core' . DS . 'App.php';
require CAKE . 'Error' . DS . 'exceptions.php';

spl_autoload_register(array('App', 'load'));

App::uses('ErrorHandler', 'Error');
App::uses('Configure', 'Core');
App::uses('CakePlugin', 'Core');
App::uses('Cache', 'Cache');
App::uses('Object', 'Core');
App::$bootstrapping = true;

Configure::bootstrap(isset($boot) ? $boot : true);

if (function_exists('mb_internal_encoding')) {
	$encoding = Configure::read('App.encoding');
	if (!empty($encoding)) {
		mb_internal_encoding($encoding);
	}
}

/**
 *  Full url prefix
 */
if (!defined('FULL_BASE_URL')) {
	$s = null;
	if (env('HTTPS')) {
		$s = 's';
	}

	$httpHost = env('HTTP_HOST');

	if (isset($httpHost)) {
		define('FULL_BASE_URL', 'http' . $s . '://' . $httpHost);
	}
	unset($httpHost, $s);
}
