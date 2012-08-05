<?php
namespace App\Config;

use Cake\Core\Configure;

/**
 * CakePHP Debug Level:
 *
 * Production Mode:
 * 	0: No error messages, errors, or warnings shown. Flash messages redirect.
 *
 * Development Mode:
 * 	1: Errors and warnings shown, model caches refreshed, flash messages halted.
 * 	2: As in 1, but also with full debug messages and SQL output.
 *
 * In production mode, flash messages redirect after a time interval.
 * In development mode, you need to click the flash message to continue.
 */
	Configure::write('debug', 2);

/**
 * Configure basic information about the application.
 *
 * - namespace - The namespace to find app classes under.
 * - encoding - The encoding used for HTML + database connections.
 * - baseUrl - To configure CakePHP *not* to use mod_rewrite and to
 *   use CakePHP pretty URLs, remove these .htaccess
 *   files:
 *      /.htaccess
 *      /app/.htaccess
 *      /app/webroot/.htaccess
 *   And uncomment the baseUrl key below.
 * - base - The base directory the app resides in. If false this
 *   will be auto detected.
 * - webroot - The webroot directory.
 * - www_root - The file path to webroot.
 */
	Configure::write('App', array(
		'namespace' => 'App',
		'encoding' => 'UTF-8',
		'baseUrl' => false,
		//'baseUrl' => env('SCRIPT_NAME'),
		'base' => false,
		'dir' => APP_DIR,
		'webroot' => WEBROOT_DIR,
		'www_root' => WWW_ROOT,
	));
