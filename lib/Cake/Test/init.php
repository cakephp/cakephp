<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       app.Config
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

use Cake\Core\Configure;

define('DS', DIRECTORY_SEPARATOR);
define('ROOT', dirname(dirname(dirname(__DIR__))));
define('APP_DIR', 'App');
define('WEBROOT_DIR', 'webroot');
define('APP', ROOT . DS . APP_DIR . DS);
define('WWW_ROOT', APP . WEBROOT_DIR . DS);
define('CSS', WWW_ROOT . 'css' . DS);
define('JS', WWW_ROOT . 'js' . DS);
define('IMAGES', WWW_ROOT . 'img' . DS);
define('TESTS', APP . 'Test' . DS);
define('TMP', APP . 'tmp' . DS);
define('LOGS', TMP . 'logs' . DS);
define('CACHE', TMP . 'cache' . DS);
define('IMAGES_URL', 'img/');
define('CSS_URL', 'css/');
define('JS_URL', 'js/');
define('CAKE_CORE_INCLUDE_PATH', ROOT . DS . 'lib');
define('CORE_PATH', CAKE_CORE_INCLUDE_PATH . DS);
define('CAKE', CORE_PATH . 'Cake' . DS);
define('FULL_BASE_URL', 'http://localhost');
define('CORE_TEST_CASES', CAKE . 'Test' . DS . 'TestCase');
define('LOG_ERROR', LOG_ERR);

@mkdir(LOGS);
@mkdir(CACHE);

require CORE_PATH . 'Cake/bootstrap.php';

mb_internal_encoding('UTF-8');

Configure::write('debug', 2);
Configure::write('App', [
	'namespace' => 'App',
	'encoding' => 'UTF-8',
	'base' => false,
	'baseUrl' => false,
	'dir' => APP_DIR,
	'webroot' => WEBROOT_DIR,
	'www_root' => WWW_ROOT,
	'fullBaseURL' => 'http://localhost'
]);

Configure::write('Cache._cake_core_', [
	'engine' => 'File',
	'prefix' => 'cake_core_',
	'serialize' => true
]);

Configure::write('Datasource.test', [
	'datasource' => getenv('db_class'),
	'dsn' => getenv('db_dsn'),
	'database' => getenv('db_database'),
	'login' => getenv('db_login'),
	'password' => getenv('db_password')
]);

Configure::write('Session', [
	'defaults' => 'php'
]);

Configure::write('Log.debug', [
	'engine' => 'Cake\Log\Engine\FileLog',
	'levels' => ['notice', 'info', 'debug'],
	'file' => 'debug',
]);

Configure::write('Log.error', [
	'engine' => 'Cake\Log\Engine\FileLog',
	'levels' => ['warning', 'error', 'critical', 'alert', 'emergency'],
	'file' => 'error',
]);

$autoloader = new Cake\Core\ClassLoader('TestApp', dirname(__DIR__) . '/Test');
$autoloader->register();
