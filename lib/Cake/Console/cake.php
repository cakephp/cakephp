#!/usr/bin/php -q
<?php
/**
 * Command-line code generation utility to automate programmer chores.
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       Cake.Console
 * @since         CakePHP(tm) v 1.2.0.5012
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */

if (!defined('DS')) {
	define('DS', DIRECTORY_SEPARATOR);
}

/**
 * These are the debug modes of CakePHP framework
 *
 * 0 = Production mode. No output.
 * 1 = Show errors and warnings.
 * 2 = Show errors, warnings, and SQL. [SQL log is only shown when you add $this->element(‘sql_dump’) to your view or layout.]
 */
if (!defined('CAKE_PRODUCTION_MODE')) {
	define('CAKE_PRODUCTION_MODE', 0);
}
if (!defined('CAKE_DEBUG_MODE')) {
	define('CAKE_DEBUG_MODE', 1);
}
if (!defined('CAKE_DEEP_DEBUG_MODE')) {
	define('CAKE_DEEP_DEBUG_MODE', 2);
}

$dispatcher = 'Cake' . DS . 'Console' . DS . 'ShellDispatcher.php';
$found = false;
$paths = explode(PATH_SEPARATOR, ini_get('include_path'));

foreach ($paths as $path) {
	if (file_exists($path . DS . $dispatcher)) {
		$found = $path;
		break;
	}
}

if (!$found) {
	$rootInstall = dirname(dirname(dirname(__FILE__))) . DS . $dispatcher;
	$composerInstall = dirname(dirname(__FILE__)) . DS . $dispatcher;

	if (file_exists($composerInstall)) {
		include $composerInstall;
	} elseif (file_exists($rootInstall)) {
		include $rootInstall;
	} else {
		trigger_error('Could not locate CakePHP core files.', E_USER_ERROR);
	}
	unset($rootInstall, $composerInstall);

} else {
	include $found . DS . $dispatcher;
}

unset($paths, $path, $found, $dispatcher);

return ShellDispatcher::run($argv);
