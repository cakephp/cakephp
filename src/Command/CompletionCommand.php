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
 * @link          https://cakephp.org CakePHP Project
 * @since         2.5.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Command;

use Cake\Console\Attribute\Argument;
use Cake\Console\Attribute\AsCommand;
use Cake\Console\BaseCommand;
use Cake\Console\CommandCollection;
use Cake\Console\CommandCollectionAwareInterface;
use Cake\Console\CommandFactory;
use Cake\Console\CommandHiddenInterface;
use Cake\Console\ConsoleIoInterface;
use Cake\Console\ConsoleOptionParser;

/**
 * Provide command completion shells such as bash.
 */
#[AsCommand('completion', description: 'Used by shells like bash to autocomplete command name, options and arguments')]
#[Argument(
    'mode',
    help: 'The type of thing to get completion on.',
    required: true,
    choices: ['commands', 'subcommands', 'options'],
)]
#[Argument('command', help: 'The command name to get information on.')]
#[Argument('subcommand', help: 'The sub-command related to command to get information on.')]
class CompletionCommand extends Command implements CommandCollectionAwareInterface
{
    /**
     * @var \Cake\Console\CommandCollection
     */
    protected CommandCollection $commands;

    /**
     * Set the command collection used to get completion data on.
     *
     * @param \Cake\Console\CommandCollection $commands The command collection
     * @return void
     */
    public function setCommandCollection(CommandCollection $commands): void
    {
        $this->commands = $commands;
    }

    /**
     * Add the generated completion mode descriptions to the help epilog.
     *
     * @param \Cake\Console\ConsoleOptionParser $parser The parser to build
     * @return \Cake\Console\ConsoleOptionParser
     */
    public function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser
    {
        $modes = [
            'commands' => 'Output a list of available commands',
            'subcommands' => 'Output a list of available sub-commands for a command',
            'options' => 'Output a list of available options for a command and possible subcommand.',
        ];
        $modeHelp = '';
        foreach ($modes as $key => $help) {
            $modeHelp .= "- <info>{$key}</info> {$help}\n";
        }

        return $parser->setEpilog([
            'The various modes allow you to get help information on commands and their arguments.',
            'The available modes are:',
            '',
            $modeHelp,
            '',
            'This command is not intended to be called manually, and should be invoked from a ' .
                'terminal completion script.',
        ]);
    }

    /**
     * Main function Prints out the list of commands.
     *
     * @return int|null
     */
    public function execute(): ?int
    {
        return match ($this->args->getArgument('mode')) {
            'commands' => $this->getCommands(),
            'subcommands' => $this->getSubcommands(),
            'options' => $this->getOptions(),
            default => static::CODE_ERROR,
        };
    }

    /**
     * Get the list of defined commands.
     *
     * @return int
     */
    protected function getCommands(): int
    {
        $options = [];
        $verbose = $this->io->level() >= ConsoleIoInterface::VERBOSE;

        // Build a map of command base names (without subcommands) to their classes
        // to detect true duplicates (plugin-prefixed alias pointing to same command)
        $commandClasses = [];
        foreach ($this->commands as $key => $value) {
            if (is_subclass_of($value, CommandHiddenInterface::class)) {
                continue;
            }
            $parts = explode(' ', $key);
            $commandName = $parts[0];
            // Only track base commands (no subcommands) and prefer first occurrence
            if (count($parts) === 1 && !isset($commandClasses[$commandName])) {
                $commandClasses[$commandName] = $value;
            }
        }

        foreach ($this->commands as $key => $value) {
            if (is_subclass_of($value, CommandHiddenInterface::class)) {
                continue;
            }
            $parts = explode(' ', $key);
            $commandName = $parts[0];

            // Skip plugin-prefixed aliases only if they are true duplicates
            // (i.e., a short form exists that resolves to the same command class)
            if (!$verbose && str_contains($commandName, '.')) {
                $shortName = explode('.', $commandName)[1];
                if (
                    isset($commandClasses[$shortName], $commandClasses[$commandName]) &&
                    $commandClasses[$shortName] === $commandClasses[$commandName]
                ) {
                    continue;
                }
            }
            $options[] = $commandName;
        }
        $options = array_unique($options);
        $this->io->out(implode(' ', $options));

        return static::CODE_SUCCESS;
    }

    /**
     * Get the list of defined sub-commands.
     *
     * @return int
     */
    protected function getSubcommands(): int
    {
        $name = $this->args->getArgument('command');
        if ($name === null || $name === '') {
            return static::CODE_SUCCESS;
        }

        $options = [];
        foreach ($this->commands as $key => $value) {
            if (is_subclass_of($value, CommandHiddenInterface::class)) {
                continue;
            }
            $parts = explode(' ', $key);
            if ($parts[0] !== $name) {
                continue;
            }

            // Space separate command name, collect
            // hits as subcommands
            if (count($parts) > 1) {
                $options[] = implode(' ', array_slice($parts, 1));
            }
        }
        $options = array_unique($options);
        $this->io->out(implode(' ', $options));

        return static::CODE_SUCCESS;
    }

    /**
     * Get the options for a command or subcommand
     *
     * @return int|null
     */
    protected function getOptions(): ?int
    {
        $name = $this->args->getArgument('command');
        $subcommand = $this->args->getArgument('subcommand');

        $options = [];
        foreach ($this->commands as $key => $value) {
            if (is_subclass_of($value, CommandHiddenInterface::class)) {
                continue;
            }
            $parts = explode(' ', $key);
            if ($parts[0] !== $name) {
                continue;
            }
            if ($subcommand && (!isset($parts[1]) || $parts[1] !== $subcommand)) {
                continue;
            }

            if (is_string($value)) {
                $value = ($this->factory ?? new CommandFactory())->create($value);
            }
            assert($value instanceof BaseCommand);

            $value->setName($this->getRootName() . ' ' . $key);
            $parser = $value->getOptionParser();
            foreach ($parser->options() as $name => $option) {
                $options[] = "--{$name}";
                $short = $option->short();
                if ($short) {
                    $options[] = "-{$short}";
                }
            }
        }
        $options = array_unique($options);
        $this->io->out(implode(' ', $options));

        return static::CODE_SUCCESS;
    }
}
