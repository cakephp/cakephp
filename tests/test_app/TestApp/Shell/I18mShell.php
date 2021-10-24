<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         3.0.8
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

/**
 * I18mShell
 */
namespace TestApp\Shell;

use Cake\Console\Shell;

class I18mShell extends Shell
{
    /**
     * main method
     */
    public function main(): void
    {
        $this->out('This is the main method called from I18mShell');
    }
}
