<?php
/**
 * Basic Cake functionality.
 *
 * Handles loading of core files needed on every request
 *
 * PHP 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       cake
 * @since         CakePHP(tm) v 0.2.9
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
if (!defined('E_DEPRECATED')) {
	define('E_DEPRECATED', 8192);
}
error_reporting(E_ALL & ~E_DEPRECATED);

require CORE_PATH . 'cake' . DS . 'basics.php';
require CORE_PATH . 'cake' . DS . 'config' . DS . 'paths.php';
require LIBS . 'error' . DS . 'exceptions.php';
require LIBS . 'object.php';
require LIBS . 'inflector.php';
require LIBS . 'app.php';
require LIBS . 'configure.php';
require LIBS . 'set.php';
require LIBS . 'cache.php';
require LIBS . 'error' . DS . 'error_handler.php';

Configure::bootstrap(isset($boot) ? $boot : true);

