<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         3.5.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Console;

use Cake\Console\CommandCollection;
use Cake\Http\BaseApplication;
use RuntimeException;

/**
 * Run CLI commands for the provided application.
 */
class CommandRunner
{
    protected $app, $root;

    /**
     * Constructor
     *
     * @param \Cake\Http\BaseApplication $app The application to run CLI commands for.
     * @param string $root The root command name to be removed from argv.
     */
    public function __construct(BaseApplication $app, $root = 'cake')
    {
        $this->app = $app;
        $this->root = $root;
    }

    /**
     * Run the command contained in $argv.
     *
     * @param array $argv The arguments from the CLI environment.
     * @return int The exit code of the command.
     * @throws \RuntimeException
     */
    public function run(array $argv)
    {
        $this->app->bootstrap();

        $commands = $this->app->console(new CommandCollection());
        if (!($commands instanceof CommandCollection)) {
            $type = is_object($commands) ? get_class($commands) : gettype($commands);
            throw new RuntimeException(
                "The application's `console` method did not return a CommandCollection." .
                " Got '{$type}' instead."
            );
        }
        if (empty($argv) || $argv[0] !== $this->root) {
            $command = empty($argv) ? '' : " `{$argv[0]}`";
            throw new RuntimeException(
                "Unknown root command{$command}. Was expecting `{$this->root}`."
            );
        }
        // Remove the root command
        array_shift($argv);

        $shell = $this->getShell($commands, $argv);
    }

    /**
     * Get the shell instance for the argv list.
     *
     * @return \Cake\Console\Shell
     */
    protected function getShell(CommandCollection $commands, array $argv)
    {
        $command = array_shift($argv);
        if (!$commands->has($command)) {
            throw new RuntimeException(
                "Unknown command `{$this->root} {$command}`." .
                " Run `{$this->root} --help` to get the list of valid commands."
            );
        }
    }
}
