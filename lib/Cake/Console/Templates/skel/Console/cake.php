#!/usr/bin/php -q
<?php
/**
 * Command-line code generation utility to automate programmer chores.
 *
 * PHP 5
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
 * @package       app.Console
 * @since         CakePHP(tm) v 2.0
 */

$ds = DIRECTORY_SEPARATOR;
$root = dirname(dirname(dirname(__FILE__)));
$appDir = basename(dirname(dirname(__FILE__)));
$dispatcher = 'Cake' . $ds . 'Console' . $ds . 'ShellDispatcher.php';
$paths = array(
	$root . $ds . $appDir . $ds . 'Lib',
	$root . $ds . 'lib'
);
$found = false;

foreach ($paths as $path) {
	if (file_exists($path . $ds . $dispatcher)) {
		$found = $path;
		break;
	}
}

if (function_exists('ini_set')) {

	// the following line differs from its sibling
	// /app/Console/cake.php
	ini_set('include_path', $root . PATH_SEPARATOR . __CAKE_PATH__ . PATH_SEPARATOR . ini_get('include_path'));

}

if (!include ($found . $ds . $dispatcher)) {
	trigger_error('Could not locate CakePHP core files.', E_USER_ERROR);
}

unset($paths, $path, $dispatcher, $root, $ds, $appDir, $found);
return ShellDispatcher::run($argv);
