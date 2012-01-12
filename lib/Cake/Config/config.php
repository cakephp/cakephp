<?php
/**
 * Core Configurations.
 *
 * PHP 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2011, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2011, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       Cake.Config
 * @since         CakePHP(tm) v 1.1.11.4062
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
$versionFile = file(CAKE . 'VERSION.txt');
$config['Cake.version'] = trim(array_pop($versionFile));
return $config;
