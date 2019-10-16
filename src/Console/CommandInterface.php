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
 * @since         4.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Console;

/**
 * Describe the interface between a command
 * and the surrounding console libraries.
 */
interface CommandInterface
{
    /**
     * Default error code
     *
     * @var int
     */
    public const CODE_ERROR = 1;

    /**
     * Default success code
     *
     * @var int
     */
    public const CODE_SUCCESS = 0;

    /**
     * Set the name this command uses in the collection.
     *
     * Generally invoked by the CommandCollection when the command is added.
     * Required to have at least one space in the name so that the root
     * command can be calculated.
     *
     * @param string $name The name the command uses in the collection.
     * @return $this
     * @throws \InvalidArgumentException
     */
    public function setName(string $name);

    /**
     * Run the command.
     *
     * @param array $argv Arguments from the CLI environment.
     * @param \Cake\Console\ConsoleIo $io The console io
     * @return int|null Exit code or null for success.
     */
    public function run(array $argv, ConsoleIo $io): ?int;
}
