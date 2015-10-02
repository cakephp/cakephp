<?php
/**
 * Core Configurations.
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
 * @since         1.1.11
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
$versionFile = file(CORE_PATH . 'VERSION.txt');
return [
    'Cake.version' => trim(array_pop($versionFile))
];
