#!/usr/bin/php -q
<?php
/**
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
 * @since         1.2.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
$cakeRoot = dirname(dirname(dirname(__DIR__)));

$app = 'App';
$appIndex = array_search('-app', $argv);
if ($appIndex !== false) {
	$app = $argv[$appIndex + 1];
}

// Path to default App skeleton.
$path = $cakeRoot . '/../../' . $app . '/Config/bootstrap.php';

if (!file_exists($path)) {
	fwrite(STDERR, "Unable to load CakePHP libraries. If you are not using the default App directory, you will need to use the -app flag.\n");
	exit(10);
}

require $path;

unset($cakeRoot, $path, $app, $appIndex);
exit(Cake\Console\ShellDispatcher::run($argv));
