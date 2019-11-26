<?php
/**
 * Core Configurations.
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
 * @since         1.1.11
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
$versionFile = file(dirname(__DIR__) . '/VERSION.txt');

return [
    'Cake.version' => trim(array_pop($versionFile)),
];
