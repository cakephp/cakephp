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
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

use Cake\Cache\Cache;
use Cake\Core\Configure;
use Cake\Datasource\ConnectionManager;
use Cake\I18n\I18n;
use Cake\Log\Log;

define('DS', DIRECTORY_SEPARATOR);
define('ROOT', dirname(__DIR__));
define('APP_DIR', 'TestApp');
define('WEBROOT_DIR', 'webroot');

define('TMP', sys_get_temp_dir() . DS);
define('LOGS', TMP . 'logs' . DS);
define('CACHE', TMP . 'cache' . DS);
define('SESSIONS', TMP . 'sessions' . DS);

define('CAKE_CORE_INCLUDE_PATH', ROOT);
define('CORE_PATH', CAKE_CORE_INCLUDE_PATH . DS);
define('CAKE', CORE_PATH . 'src' . DS);
define('CORE_TESTS', CORE_PATH . 'tests' . DS);
define('CORE_TEST_CASES', CORE_TESTS . 'TestCase');
define('TEST_APP', CORE_TESTS . 'test_app' . DS);
define('LOG_ERROR', LOG_ERR);

// Point app constants to the test app.
define('APP', TEST_APP . 'TestApp' . DS);
define('WWW_ROOT', TEST_APP . WEBROOT_DIR . DS);
define('TESTS', TEST_APP . 'tests' . DS);

//@codingStandardsIgnoreStart
@mkdir(LOGS);
@mkdir(SESSIONS);
@mkdir(CACHE);
@mkdir(CACHE . 'views');
@mkdir(CACHE . 'models');
//@codingStandardsIgnoreEnd

require CAKE . 'Core/ClassLoader.php';

$loader = new Cake\Core\ClassLoader;
$loader->register();

$loader->addNamespace('Cake', CAKE);
$loader->addNamespace('Cake\Test', CORE_TESTS);
$loader->addNamespace('TestApp', APP);
$loader->addNamespace('TestPlugin', TEST_APP . 'Plugin/TestPlugin');
$loader->addNamespace('TestPluginTwo', TEST_APP . 'Plugin/TestPluginTwo');
$loader->addNamespace('PluginJs', TEST_APP . 'Plugin/PluginJs');

require CAKE . 'bootstrap.php';

date_default_timezone_set('UTC');
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
	'fullBaseUrl' => 'http://localhost',
	'imageBaseUrl' => 'img/',
	'jsBaseUrl' => 'js/',
	'cssBaseUrl' => 'css/',
	'paths' => [
		'plugins' => [TEST_APP . 'Plugin/'],
		'templates' => [APP . 'Template/']
	]
]);

Cache::config([
	'_cake_core_' => [
		'engine' => 'File',
		'prefix' => 'cake_core_',
		'serialize' => true
	],
	'_cake_model_' => [
		'engine' => 'File',
		'prefix' => 'cake_model_',
		'serialize' => true
	]
]);

ConnectionManager::config('test', [
	'className' => 'Cake\Database\Connection',
	'driver' => getenv('db_class'),
	'dsn' => getenv('db_dsn'),
	'database' => getenv('db_database'),
	'login' => getenv('db_login'),
	'password' => getenv('db_password'),
	'timezone' => 'UTC'
]);

Configure::write('Session', [
	'defaults' => 'php'
]);

Log::config([
	'debug' => [
		'engine' => 'Cake\Log\Engine\FileLog',
		'levels' => ['notice', 'info', 'debug'],
		'file' => 'debug',
	],
	'error' => [
		'engine' => 'Cake\Log\Engine\FileLog',
		'levels' => ['warning', 'error', 'critical', 'alert', 'emergency'],
		'file' => 'error',
	]
]);

// Initialize the empty language.
I18n::translate('empty');
