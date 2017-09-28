<?php
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
 * @since         3.5.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

namespace Cake\Shell;

use Cake\Console\Shell;
use Cake\Core\Configure;

/**
 * Print out the version of CakePHP in use.
 */
class VersionShell extends Shell
{
    /**
     * Print out the version of CakePHP in use.
     *
     * @return void
     */
    public function main()
    {
        $this->out(Configure::version());
    }
}
