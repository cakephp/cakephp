<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @since         3.5.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\TestSuite;

use Cake\Console\ConsoleIo;

/**
 * Class that dispatches to the legacy ShellDispatcher using the same signature
 * as the newer CommandRunner
 */
class LegacyCommandRunner
{
    /**
     * @var \Cake\Console\ConsoleIo
     */
    protected $_io;

    /**
     * Mimics functionality of Cake\Console\CommandRunner
     *
     * @param array $argv Argument array
     * @param ConsoleIo $io ConsoleIo
     * @return int
     */
    public function run(array $argv, ConsoleIo $io = null)
    {
        $dispatcher = new LegacyShellDispatcher($argv, true, $io);

        return $dispatcher->dispatch();
    }
}
