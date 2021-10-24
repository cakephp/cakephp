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

use Cake\Console\Arguments;
use Cake\Console\CommandCollection;
use Cake\Console\CommandCollectionAwareInterface;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\Console\Shell;
use Cake\Utility\Inflector;
use ReflectionClass;
use ReflectionMethod;

/**
 * Provide command completion shells such as bash.
 */
class CompletionCommand extends Command implements CommandCollectionAwareInterface
{
    /**
     * @var \Cake\Console\CommandCollection
     */
    protected $commands;

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
     * Gets the option parser instance and configures it.
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
            'fuzzy' => 'Does nothing. Only for backwards compatibility',
        ];
        $modeHelp = '';
        foreach ($modes as $key => $help) {
            $modeHelp .= "- <info>{$key}</info> {$help}\n";
        }

        $parser->setDescription(
            'Used by shells like bash to autocomplete command name, options and arguments'
        )->addArgument('mode', [
            'help' => 'The type of thing to get completion on.',
            'required' => true,
            'choices' => array_keys($modes),
        ])->addArgument('command', [
            'help' => 'The command name to get information on.',
            'required' => false,
        ])->addArgument('subcommand', [
            'help' => 'The sub-command related to command to get information on.',
            'required' => false,
        ])->setEpilog([
            'The various modes allow you to get help information on commands and their arguments.',
            'The available modes are:',
            '',
            $modeHelp,
            '',
            'This command is not intended to be called manually, and should be invoked from a ' .
                'terminal completion script.',
        ]);

        return $parser;
    }

    /**
     * Main function Prints out the list of commands.
     *
     * @param \Cake\Console\Arguments $args The command arguments.
     * @param \Cake\Console\ConsoleIo $io The console io
     * @return int
     */
    public function execute(Arguments $args, ConsoleIo $io): ?int
    {
        $mode = $args->getArgument('mode');
        switch ($mode) {
            case 'commands':
                return $this->getCommands($args, $io);
            case 'subcommands':
                return $this->getSubcommands($args, $io);
            case 'options':
                return $this->getOptions($args, $io);
            case 'fuzzy':
                return static::CODE_SUCCESS;
            default:
                $io->err('Invalid mode chosen.');
        }

        return static::CODE_SUCCESS;
    }

    /**
     * Get the list of defined commands.
     *
     * @param \Cake\Console\Arguments $args The command arguments.
     * @param \Cake\Console\ConsoleIo $io The console io
     * @return int
     */
    protected function getCommands(Arguments $args, ConsoleIo $io): int
    {
        $options = [];
        foreach ($this->commands as $key => $value) {
            $parts = explode(' ', $key);
            $options[] = $parts[0];
        }
        $options = array_unique($options);
        $io->out(implode(' ', $options));

        return static::CODE_SUCCESS;
    }

    /**
     * Get the list of defined sub-commands.
     *
     * @param \Cake\Console\Arguments $args The command arguments.
     * @param \Cake\Console\ConsoleIo $io The console io
     * @return int
     */
    protected function getSubcommands(Arguments $args, ConsoleIo $io): int
    {
        $name = $args->getArgument('command');
        if ($name === null || $name === '') {
            return static::CODE_SUCCESS;
        }

        $options = [];
        foreach ($this->commands as $key => $value) {
            $parts = explode(' ', $key);
            if ($parts[0] !== $name) {
                continue;
            }

            // Space separate command name, collect
            // hits as subcommands
            if (count($parts) > 1) {
                $options[] = implode(' ', array_slice($parts, 1));
                continue;
            }

            // Handle class strings
            if (is_string($value)) {
                $reflection = new ReflectionClass($value);
                $value = $reflection->newInstance();
            }
            if ($value instanceof Shell) {
                $shellCommands = $this->shellSubcommands($value);
                $options = array_merge($options, $shellCommands);
            }
        }
        $options = array_unique($options);
        $io->out(implode(' ', $options));

        return static::CODE_SUCCESS;
    }

    /**
     * Reflect the subcommands names out of a shell.
     *
     * @param \Cake\Console\Shell $shell The shell to get commands for
     * @return array<string> A list of commands
     */
    protected function shellSubcommands(Shell $shell): array
    {
        $shell->initialize();
        $shell->loadTasks();

        $optionParser = $shell->getOptionParser();
        $subcommands = $optionParser->subcommands();

        $output = array_keys($subcommands);

        // If there are no formal subcommands all methods
        // on a shell are 'subcommands'
        if (count($subcommands) === 0) {
            /** @psalm-suppress DeprecatedClass */
            $coreShellReflection = new ReflectionClass(Shell::class);
            $reflection = new ReflectionClass($shell);
            foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
                if (
                    $shell->hasMethod($method->getName())
                    && !$coreShellReflection->hasMethod($method->getName())
                ) {
                    $output[] = $method->getName();
                }
            }
        }
        $taskNames = array_map('Cake\Utility\Inflector::underscore', $shell->taskNames);
        $output = array_merge($output, $taskNames);

        return array_unique($output);
    }

    /**
     * Get the options for a command or subcommand
     *
     * @param \Cake\Console\Arguments $args The command arguments.
     * @param \Cake\Console\ConsoleIo $io The console io
     * @return int
     */
    protected function getOptions(Arguments $args, ConsoleIo $io): ?int
    {
        $name = $args->getArgument('command');
        $subcommand = $args->getArgument('subcommand');

        $options = [];
        foreach ($this->commands as $key => $value) {
            $parts = explode(' ', $key);
            if ($parts[0] !== $name) {
                continue;
            }
            if ($subcommand && !isset($parts[1])) {
                continue;
            }
            if ($subcommand && isset($parts[1]) && $parts[1] !== $subcommand) {
                continue;
            }

            // Handle class strings
            if (is_string($value)) {
                $reflection = new ReflectionClass($value);
                $value = $reflection->newInstance();
            }
            $parser = null;
            if ($value instanceof Command) {
                $parser = $value->getOptionParser();
            }
            if ($value instanceof Shell) {
                $value->initialize();
                $value->loadTasks();

                $parser = $value->getOptionParser();
                $subcommand = Inflector::camelize((string)$subcommand);
                if ($subcommand && $value->hasTask($subcommand)) {
                    $parser = $value->{$subcommand}->getOptionParser();
                }
            }

            if ($parser) {
                foreach ($parser->options() as $name => $option) {
                    $options[] = "--$name";
                    $short = $option->short();
                    if ($short) {
                        $options[] = "-$short";
                    }
                }
            }
        }
        $options = array_unique($options);
        $io->out(implode(' ', $options));

        return static::CODE_SUCCESS;
    }
}
