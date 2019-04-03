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
 * @link          https://cakephp.org CakePHP Project
 * @since         2.5.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Shell;

use Cake\Console\Shell;

/**
 * Provide command completion shells such as bash.
 *
 * @property \Cake\Shell\Task\CommandTask $Command
 */
class CompletionShell extends Shell
{

    /**
     * Contains tasks to load and instantiate
     *
     * @var array
     */
    public $tasks = ['Command'];

    /**
     * Echo no header by overriding the startup method
     *
     * @return void
     */
    public function startup()
    {
    }

    /**
     * Not called by the autocomplete shell - this is for curious users
     *
     * @return int|bool Returns the number of bytes returned from writing to stdout.
     */
    public function main()
    {
        return $this->out($this->getOptionParser()->help());
    }

    /**
     * list commands
     *
     * @return int|bool|null Returns the number of bytes returned from writing to stdout.
     */
    public function commands()
    {
        $options = $this->Command->commands();

        return $this->_output($options);
    }

    /**
     * list options for the named command
     *
     * @return int|bool|null Returns the number of bytes returned from writing to stdout.
     */
    public function options()
    {
        $commandName = $subCommandName = '';
        if (!empty($this->args[0])) {
            $commandName = $this->args[0];
        }
        if (!empty($this->args[1])) {
            $subCommandName = $this->args[1];
        }
        $options = $this->Command->options($commandName, $subCommandName);

        return $this->_output($options);
    }

    /**
     * list subcommands for the named command
     *
     * @return int|bool|null Returns the number of bytes returned from writing to stdout.
     */
    public function subcommands()
    {
        if (!$this->args) {
            return $this->_output();
        }

        $options = $this->Command->subCommands($this->args[0]);

        return $this->_output($options);
    }

    /**
     * Guess autocomplete from the whole argument string
     *
     * @return int|bool|null Returns the number of bytes returned from writing to stdout.
     */
    public function fuzzy()
    {
        return $this->_output();
    }

    /**
     * Gets the option parser instance and configures it.
     *
     * @return \Cake\Console\ConsoleOptionParser
     */
    public function getOptionParser()
    {
        $parser = parent::getOptionParser();

        $parser->setDescription(
            'Used by shells like bash to autocomplete command name, options and arguments'
        )->addSubcommand('commands', [
            'help' => 'Output a list of available commands',
            'parser' => [
                'description' => 'List all available',
            ]
        ])->addSubcommand('subcommands', [
            'help' => 'Output a list of available subcommands',
            'parser' => [
                'description' => 'List subcommands for a command',
                'arguments' => [
                    'command' => [
                        'help' => 'The command name',
                        'required' => false,
                    ]
                ]
            ]
        ])->addSubcommand('options', [
            'help' => 'Output a list of available options',
            'parser' => [
                'description' => 'List options',
                'arguments' => [
                    'command' => [
                        'help' => 'The command name',
                        'required' => false,
                    ],
                    'subcommand' => [
                        'help' => 'The subcommand name',
                        'required' => false,
                    ]
                ]
            ]
        ])->addSubcommand('fuzzy', [
            'help' => 'Guess autocomplete'
        ])->setEpilog([
            'This command is not intended to be called manually',
        ]);

        return $parser;
    }

    /**
     * Emit results as a string, space delimited
     *
     * @param array $options The options to output
     * @return int|bool|null Returns the number of bytes returned from writing to stdout.
     */
    protected function _output($options = [])
    {
        if ($options) {
            return $this->out(implode($options, ' '));
        }
    }
}
