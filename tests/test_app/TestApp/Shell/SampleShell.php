<?php
/**
 * SampleShell file
 *
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
 * SampleShell
 */
namespace TestApp\Shell;

use Cake\Console\Shell;

class SampleShell extends Shell
{

    public $tasks = ['Sample', 'Load'];

    /**
     * main method
     *
     * @return void
     */
    public function main()
    {
        $this->out('This is the main method called from SampleShell');
    }

    /**
     * derp method
     *
     * @return void
     */
    public function derp()
    {
        $this->out('This is the example method called from TestPlugin.SampleShell');
    }

    public function withAbort()
    {
        $this->abort('Bad things');
    }

    public function returnValue()
    {
        return 99;
    }
}
