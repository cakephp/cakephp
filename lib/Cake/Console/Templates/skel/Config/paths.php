<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       app.Config
 * @since         CakePHP(tm) v3.0.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

/**
 * Use the DS to separate the directories in other defines
 */
define('DS', DIRECTORY_SEPARATOR);

/**
 * These defines should only be edited if you have cake installed in
 * a directory layout other than the way it is distributed.
 * When using custom settings be sure to use the DS and do not add a trailing DS.
 */

/**
 * The full path to the directory which holds "App", WITHOUT a trailing DS.
 */
define('ROOT', dirname(dirname(__DIR__)));

/**
 * The actual directory name for the "App".
 */
define('APP_DIR', basename(dirname(__DIR__)));

/**
 * The name of the webroot dir.  Defaults to 'webroot'
 */
define('WEBROOT_DIR', 'webroot');

/**
 * Path to the application's directory.
 */
define('APP', ROOT . DS . APP_DIR . DS);

/**
 * File path to the webroot directory.
 */
define('WWW_ROOT', APP . WEBROOT_DIR . DS);

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
define('TESTS', APP . 'Test' . DS);

/**
 * Path to the temporary files directory.
 */
define('TMP', APP . 'tmp' . DS);

/**
 * Path to the logs directory.
 */
define('LOGS', TMP . 'logs' . DS);

/**
 * Path to the cache files directory. It can be shared between hosts in a multi-server setup.
 */
define('CACHE', TMP . 'cache' . DS);

/**
 * Web path to the public images directory.
 */
define('IMAGES_URL', 'img/');

/**
 * Web path to the CSS files directory.
 */
define('CSS_URL', 'css/');

/**
 * Web path to the js files directory.
 */
define('JS_URL', 'js/');

/**
 * The absolute path to the "cake" directory, WITHOUT a trailing DS.
 *
 * Un-comment this line to specify a fixed path to CakePHP.
 * This should point at the directory containing `Cake`.
 *
 * For ease of development CakePHP uses PHP's include_path.  If you
 * cannot modify your include_path set this value.
 *
 * Leaving this constant undefined will result in it being defined in Cake/bootstrap.php
 */
//TODO include_path support.
define('CAKE_CORE_INCLUDE_PATH', ROOT . DS . 'lib');

define('CORE_PATH', CAKE_CORE_INCLUDE_PATH . DS);

/**
 * Path to the cake directory.
 */
define('CAKE', CORE_PATH . 'Cake' . DS);
