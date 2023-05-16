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
 * @since         3.5.0
 * @license       https://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Console\TestSuite;

use Cake\Console\ConsoleIo;

/**
 * Class that dispatches to the legacy ShellDispatcher using the same signature
 * as the newer CommandRunner
 */
class LegacyCommandRunner
{
    /**
     * Mimics functionality of Cake\Console\CommandRunner
     *
     * @param array $argv Argument array
     * @param \Cake\Console\ConsoleIo|null $io A ConsoleIo instance.
     * @return int
     */
    public function run(array $argv, ?ConsoleIo $io = null): int
    {
        $dispatcher = new LegacyShellDispatcher($argv, true, $io);

        return $dispatcher->dispatch();
    }
}

// phpcs:disable
class_alias(
    'Cake\Console\TestSuite\LegacyCommandRunner',
    'Cake\TestSuite\LegacyCommandRunner'
);
// phpcs:enable
