<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         5.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

/**
 * Create global aliases for all functions in the split modules.
 *
 * This uses a bit of codegen hackery and we recommend you don't plan on using
 * it long term. If there are scenarios where you find global aliases are greatly
 * beneficial please look for an open issue for function aliases or create a new
 * one if your usecase hasn't been covered yet.
 */
$setup = function (): void {
    $packages = ['Collection', 'Core', 'I18n', 'Routing'];
    foreach ($packages as $packageName) {
        $path = __DIR__ . "/{$packageName}/functions_global.php";
        if (file_exists($path)) {
            require_once $path;
        }
    }
};

$setup();
unset($setup);
