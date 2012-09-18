#!/usr/bin/php -q
<?php
/**
 * Command-line code generation utility to automate programmer chores.
 *
 * PHP 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2012, Cake Software Foundation, Inc.
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       Cake.Console
 * @since         CakePHP(tm) v 1.2.0.5012
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
$root = dirname(dirname(dirname(__DIR__)));
$loaded = false;

$appIndex = array_search('-app', $argv);
if ($appIndex !== false) {
	$loaded = true;
	$dir = $argv[$appIndex + 1];
	require $dir . '/Config/bootstrap.php';
}
// Default app directory layout
if (!$loaded && file_exists($root . '/App/Config/bootstrap.php')) {
	require $root . '/App/Config/bootstrap.php';
}
unset($root, $loaded, $appIndex, $dir);
return Cake\Console\ShellDispatcher::run($argv);
