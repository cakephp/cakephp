<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         CakePHP(tm) v 0.10.8.2117
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace App\Config;

// Use composer to load the autoloader.
$root = dirname(dirname(__DIR__));
if (file_exists($root . '/vendor/autoload.php')) {
	require $root . '/vendor/autoload.php';
}

// If you can't use composer, you can use CakePHP's classloader
// to autoload your application and the framework. You will
// also need to add autoloaders for each Plugin you use.
// This code expects that cakephp will be
// setup in vendor/cakephp/cakephp.
/*
require $root . '/vendor/cakephp/cakephp/Cake/Core/ClassLoader.php';
(new \Cake\Core\ClassLoader('App', $root))->register();
(new \Cake\Core\ClassLoader('Cake', $root . '/vendor/cakephp/cakephp'))->register();
*/
unset($root);


/**
 * Configure paths required to find CakePHP + general filepath
 * constants
 */
require __DIR__ . '/paths.php';

/**
 * Bootstrap CakePHP.
 *
 * Does the various bits of setup that CakePHP needs to do.
 * This includes:
 *
 * - Registering the CakePHP autoloader.
 * - Setting the default application paths.
 */
require CORE_PATH . 'Cake/bootstrap.php';

use Cake\Core\App;
use Cake\Cache\Cache;
use Cake\Console\ConsoleErrorHandler;
use Cake\Core\Configure;
use Cake\Configure\Engine\PhpConfig;
use Cake\Core\Plugin;
use Cake\Database\ConnectionManager;
use Cake\Error\ErrorHandler;
use Cake\Log\Log;
use Cake\Network\Email\Email;
use Cake\Utility\Inflector;

/**
 * Read configuration file and inject configuration into various
 * CakePHP classes.
 *
 * By default there is only one configuration file. It is often a good
 * idea to create multiple configuration files, and separate the configuration
 * that changes from configuration that does not. This makes deployment simpler.
 */
try {
	Configure::config('default', new PhpConfig());
	Configure::load('app.php', 'default', false);

	/**
	 * Load an environment local configuration file.
	 * You can use this file to provide local overrides to your
	 * shared configuration.
	 */
	// Configure::load('app.local.php', 'default');
} catch (\Exception $e) {
	die('Unable to load Config/app.php. Create it by copying Config/app.php.default to Config/app.php.');
}

/**
 * Configure an autoloader for the App namespace.
 *
 * Use App\Controller\AppController as a test to see if composer
 * support is being used.
 */
if (!class_exists('App\Controller\AppController')) {
	(new \Cake\Core\ClassLoader(Configure::read('App.namespace'), dirname(APP)))->register();
}

/**
 * Uncomment this line and correct your server timezone to fix
 * any date & time related errors.
 */
	//date_default_timezone_set('UTC');

/**
 * Configure the mbstring extension to use the correct encoding.
 */
mb_internal_encoding(Configure::read('App.encoding'));

/**
 * Register application error and exception handlers.
 */
if (php_sapi_name() == 'cli') {
	(new ConsoleErrorHandler(Configure::consume('Error')))->register();
} else {
	(new ErrorHandler(Configure::consume('Error')))->register();
}


/**
 * Set the full base url.
 * This URL is used as the base of all absolute links.
 *
 * If you define fullBaseUrl in your config file you can remove this.
 */
if (!Configure::read('App.fullBaseUrl')) {
	$s = null;
	if (env('HTTPS')) {
		$s = 's';
	}

	$httpHost = env('HTTP_HOST');
	if (isset($httpHost)) {
		Configure::write('App.fullBaseUrl', 'http' . $s . '://' . $httpHost);
	}
	unset($httpHost, $s);
}

Cache::config(Configure::consume('Cache'));
ConnectionManager::config(Configure::consume('Datasources'));
Email::configTransport(Configure::consume('EmailTransport'));
Email::config(Configure::consume('Email'));
Log::config(Configure::consume('Log'));


/**
 * Custom Inflector rules, can be set to correctly pluralize or singularize table, model, controller names or whatever other
 * string is passed to the inflection functions
 *
 * Inflector::rules('singular', array('rules' => array(), 'irregular' => array(), 'uninflected' => array()));
 * Inflector::rules('plural', array('rules' => array(), 'irregular' => array(), 'uninflected' => array()));
 *
 */

/**
 * Plugins need to be loaded manually, you can either load them one by one or all of them in a single call
 * Uncomment one of the lines below, as you need. make sure you read the documentation on Plugin to use more
 * advanced ways of loading plugins
 *
 * Plugin::loadAll(); // Loads all plugins at once
 * Plugin::load('DebugKit'); //Loads a single plugin named DebugKit
 *
 */
