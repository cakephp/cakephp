<?php
/**
 * SampleShell file
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         1.2.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
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
}
