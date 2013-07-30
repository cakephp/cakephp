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
 * @package       Cake.Console
 * @since         CakePHP(tm) v 1.2.0.5012
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */

$root = dirname(dirname(dirname(__DIR__)));
$loaded = false;

$appIndex = array_search('-app', $argv);
if ($appIndex !== false) {
	$loaded = true;
	$dir = $argv[$appIndex + 1];
	require $dir . '/Config/bootstrap.php';
}

$locations = [
	// Default repository layout.
	$root . '/App/Config/bootstrap.php',
	// Composer vendor directory
	$root . '/../../Config/bootstrap.php',
];

foreach ($locations as $path) {
	if (file_exists($path)) {
		$loaded = true;
		require $path;
		break;
	}
}
if (!$loaded) {
	fwrite(STDERR, "Unable to load CakePHP libraries, check your configuration/installation.\n");
	exit(10);
}
unset($root, $loaded, $appIndex, $dir, $path, $locations);
exit(Cake\Console\ShellDispatcher::run($argv));
