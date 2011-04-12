<?php
/**
 * Basic Cake functionality.
 *
 * Handles loading of core files needed on every request
 *
 * PHP 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       cake.config
 * @since         CakePHP(tm) v 0.2.9
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
if (!defined('E_DEPRECATED')) {
	define('E_DEPRECATED', 8192);
}
error_reporting(E_ALL & ~E_DEPRECATED);

/**
 * If the index.php file is used instead of an .htaccess file
 * or if the user can not set the web root to use the public
 * directory we will define ROOT there, otherwise we set it
 * here.
 */
	if (!defined('ROOT')) {
		define('ROOT', '../');
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
	define('APP', ROOT.DS.APP_DIR.DS);
}

/**
 * Path to the application's models directory.
 */
	define('MODELS', APP.'Model'.DS);

/**
 * Path to model behaviors directory.
 */
	define('BEHAVIORS', MODELS.'Behavior'.DS);

/**
 * Path to the application's controllers directory.
 */
	define('CONTROLLERS', APP.'Controller'.DS);

/**
 * Path to the application's components directory.
 */
	define('COMPONENTS', CONTROLLERS.'Component'.DS);

/**
 * Path to the application's libs directory.
 */
	define('APPLIBS', APP.'Lib'.DS);

/**
 * Path to the application's views directory.
 */
	define('VIEWS', APP.'View'.DS);

/**
 * Path to the application's helpers directory.
 */
	define('HELPERS', VIEWS.'Helper'.DS);

/**
 * Path to the application's view's layouts directory.
 */
	define('LAYOUTS', VIEWS.'layouts'.DS);

/**
 * Path to the application's view's elements directory.
 * It's supposed to hold pieces of PHP/HTML that are used on multiple pages
 * and are not linked to a particular layout (like polls, footers and so on).
 */
	define('ELEMENTS', VIEWS.'elements'.DS);

/**
 * Path to the configuration files directory.
 */
if (!defined('CONFIGS')) {
	define('CONFIGS', APP.'config'.DS);
}

/**
 * Path to the libs directory.
 */
	define('LIBS', CAKE);

/**
 * Path to the public CSS directory.
 */
	define('CSS', WWW_ROOT.'css'.DS);

/**
 * Path to the public JavaScript directory.
 */
	define('JS', WWW_ROOT.'js'.DS);

/**
 * Path to the public images directory.
 */
	define('IMAGES', WWW_ROOT.'img'.DS);

/**
 * Path to the console libs direcotry.
 */
	define('CONSOLE_LIBS', CAKE . 'Console' . DS);

/**
 * Path to the tests directory.
 */
if (!defined('TESTS')) {
	define('TESTS', APP.'tests'.DS);
}

/**
 * Path to the core tests directory.
 */
if (!defined('CAKE_TESTS')) {
	define('CAKE_TESTS', CAKE.'tests'.DS);
}

/**
 * Path to the test suite.
 */
	define('CAKE_TESTS_LIB', LIBS . 'TestSuite' . DS);

/**
 * Path to the controller test directory.
 */
	define('CONTROLLER_TESTS', TESTS.'Case'.DS.'Controller'.DS);

/**
 * Path to the components test directory.
 */
	define('COMPONENT_TESTS', TESTS.'Case'.DS.'Component'.DS);

/**
 * Path to the helpers test directory.
 */
	define('HELPER_TESTS', TESTS.'Case'.DS.'View'.DS.'Helper'.DS);

/**
 * Path to the models' test directory.
 */
	define('MODEL_TESTS', TESTS.'Case'.DS.'Model'.DS);

/**
 * Path to the lib test directory.
 */
	define('LIB_TESTS', CAKE_TESTS.'Case'.DS.'Lib'.DS);

/**
 * Path to the temporary files directory.
 */
if (!defined('TMP')) {
	define('TMP', APP.'tmp'.DS);
}

/**
 * Path to the logs directory.
 */
	define('LOGS', TMP.'logs'.DS);

/**
 * Path to the cache files directory. It can be shared between hosts in a multi-server setup.
 */
	define('CACHE', TMP.'cache'.DS);

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


require LIBS . 'basics.php';
require LIBS . 'Core' . DS .'App.php';
require LIBS . 'Error' . DS . 'exceptions.php';

spl_autoload_register(array('App', 'load'));

App::uses('ErrorHandler', 'Error');
App::uses('Configure', 'Core');
App::uses('Cache', 'Cache');
App::uses('Object', 'Core');

Configure::bootstrap(isset($boot) ? $boot : true);

/**
 *  Full url prefix
 */
if (!defined('FULL_BASE_URL')) {
	$s = null;
	if (env('HTTPS')) {
		$s ='s';
	}

	$httpHost = env('HTTP_HOST');

	if (isset($httpHost)) {
		define('FULL_BASE_URL', 'http'.$s.'://'.$httpHost);
	}
	unset($httpHost, $s);
}
