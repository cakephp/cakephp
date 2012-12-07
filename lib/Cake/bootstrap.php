<?php
/**
 * Basic Cake functionality.
 *
 * Handles loading of core files needed on every request
 *
 * PHP 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       Cake
 * @since         CakePHP(tm) v 0.2.9
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
define('TIME_START', microtime(true));

require CAKE . 'basics.php';

if (!class_exists('Cake\Core\App')) {
	require CAKE . 'Core/ClassLoader.php';
	$loader = new \Cake\Core\ClassLoader('Cake', CORE_PATH);
	$loader->register();
}

use Cake\Core\App;
use Cake\Core\Configure;

App::init();
App::build();
