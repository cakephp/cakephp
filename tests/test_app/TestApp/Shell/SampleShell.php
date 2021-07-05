<?php
declare(strict_types=1);

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

use Cake\Console\ConsoleOptionParser;
use Cake\Console\Shell;

class SampleShell extends Shell
{
    public $tasks = ['Sample'];

    /**
     * main method
     */
    public function main(): void
    {
        $this->out('This is the main method called from SampleShell');
    }

    /**
     * derp method
     */
    public function derp(): void
    {
        $this->out('This is the example method called from TestPlugin.SampleShell');
    }

    public function withAbort(): void
    {
        $this->abort('Bad things');
    }

    public function returnValue(): int
    {
        return 99;
    }

    /**
     * @inheritDoc
     */
    public function runCommand(array $argv, bool $autoMethod = false, array $extra = [])
    {
        return parent::runCommand($argv, $autoMethod, $extra);
    }

    /**
     * @inheritDoc
     */
    public function getOptionParser(): ConsoleOptionParser
    {
        return parent::getOptionParser();
    }
}
