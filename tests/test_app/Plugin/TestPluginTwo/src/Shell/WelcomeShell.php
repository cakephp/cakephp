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
 * @since         1.2.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

/**
 * WelcomeShell
 */
namespace TestPluginTwo\Shell;

use Cake\Console\Shell;

class WelcomeShell extends Shell
{
    /**
     * say_hello method
     */
    public function say_hello(): void
    {
        $this->out('This is the say_hello method called from TestPluginTwo.WelcomeShell');
    }
}
