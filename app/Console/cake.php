#!/usr/bin/php -q
<?php
/**
 * Command-line code generation utility to automate programmer chores.
 *
 * Shell dispatcher class
 *
 * PHP 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2010, Cake Software Foundation, Inc.
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       app.console
 * @since         CakePHP(tm) v 1.2.0.5012
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
require_once(dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR . 'lib'. DIRECTORY_SEPARATOR . 'Cake' . DIRECTORY_SEPARATOR . 'Console' . DIRECTORY_SEPARATOR . 'ShellDispatcher.php');

return ShellDispatcher::run($argv);
