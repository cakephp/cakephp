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
 * @since         3.6.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Command;

use Cake\Console\Arguments;
use Cake\Console\BaseCommand;
use Cake\Console\ConsoleIo;
use Cake\Event\EventInterface;
use Cake\Log\LogTrait;
use Cake\ORM\Locator\LocatorAwareTrait;

/**
 * Base class for commands using the full stack
 * CakePHP Framework.
 *
 * Includes traits that integrate logging
 * and ORM models to console commands.
 */
class Command extends BaseCommand
{
    use LocatorAwareTrait;
    use LogTrait;

    /**
     * Implement this method with your command's logic.
     *
     * @param \Cake\Console\Arguments $args The command arguments.
     * @param \Cake\Console\ConsoleIo $io The console io
     * @return int|null|void The exit code or null for success
     */
    public function execute(Arguments $args, ConsoleIo $io)
    {
    }

    /**
     * Called before the command argument parsing logic. You can use this method to configure and customize the command
     * or perform logic that needs to happen before the command parses arguments.
     *
     * @param \Cake\Event\EventInterface<\Cake\Command\Command> $event An Event instance
     * @return void
     * @link https://book.cakephp.org/5/en/console-commands/commands.html#lifecycle-callbacks
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ReturnTypeHint.MissingNativeTypeHint
     */
    public function beforeFilter(EventInterface $event)
    {
    }

    /**
     * Called immediately prior to the command's run method. You can use this method to configure and customize the
     * command or perform logic that needs to happen before the command runs.
     *
     * @param \Cake\Event\EventInterface<\Cake\Command\Command> $event An Event instance
     * @return void
     * @link https://book.cakephp.org/5/en/console-commands/commands.html#lifecycle-callbacks
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ReturnTypeHint.MissingNativeTypeHint
     */
    public function beforeExecute(EventInterface $event)
    {
    }

    /**
     * Called immediately after the command's run method, unless an exception occurs. You can use this method to
     * perform logic that needs to happen after the command runs.
     *
     * @param \Cake\Event\EventInterface<\Cake\Command\Command> $event An Event instance
     * @return void
     * @link https://book.cakephp.org/5/en/console-commands/commands.html#lifecycle-callbacks
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ReturnTypeHint.MissingNativeTypeHint
     */
    public function afterExecute(EventInterface $event)
    {
    }
}
