#!/usr/bin/php -q
<?php
/**
 * Command-line code generation utility to automate programmer chores.
 *
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @package       Cake.Console
 * @since         CakePHP(tm) v 1.2.0.5012
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

if (!defined('DS')) {
	define('DS', DIRECTORY_SEPARATOR);
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
