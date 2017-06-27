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
use Cake\Console\ConsoleIo;
use Cake\Console\Exception\StopException;
use Cake\Console\Shell;
use Cake\Http\BaseApplication;
use Cake\Shell\VersionShell;
use RuntimeException;

/**
 * Run CLI commands for the provided application.
 */
class CommandRunner
{
    /**
     * The application console commands are being run for.
     *
     * @var \Cake\Http\BaseApplication
     */
    protected $app;

    /**
     * The root command name. Defaults to `cake`.
     *
     * @var string
     */
    protected $root;

    /**
     * Alias mappings.
     *
     * @var array
     */
    protected $aliases = [];

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
        $this->aliases = [
            '--version' => 'version'
        ];
    }

    /**
     * Replace the entire alias map for a runner.
     *
     * Aliases allow you to define alternate names for commands
     * in the collection. This can be useful to add top level switches
     * like `--version` or `-h`
     *
     * ### Usage
     *
     * ```
     * $runner->setAliases(['--version' => 'version']);
     * ```
     *
     * @param array $aliases The map of aliases to replace.
     * @return $this
     */
    public function setAliases(array $aliases)
    {
        $this->aliases = $aliases;

        return $this;
    }

    /**
     * Run the command contained in $argv.
     *
     * @param array $argv The arguments from the CLI environment.
     * @param \Cake\Console\ConsoleIo $io The ConsoleIo instance. Used primarily for testing.
     * @return int The exit code of the command.
     * @throws \RuntimeException
     */
    public function run(array $argv, ConsoleIo $io = null)
    {
        $this->app->bootstrap();

        $commands = new CommandCollection([
            'version' => VersionShell::class,
        ]);
        $commands = $this->app->console($commands);
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
        // Remove the root executable segment
        array_shift($argv);

        $io = $io ?: new ConsoleIo();
        $shell = $this->getShell($io, $commands, array_shift($argv));

        try {
            $shell->initialize();
            $result = $shell->runCommand($argv, true);
        } catch (StopException $e) {
            return $e->getCode();
        }

        if ($result === null || $result === true) {
            return Shell::CODE_SUCCESS;
        }
        if (is_int($result)) {
            return $result;
        }

        return Shell::CODE_ERROR;
    }

    /**
     * Get the shell instance for a given command name
     *
     * @param \Cake\Console\ConsoleIo $io The io wrapper for the created shell class.
     * @param \Cake\Console\CommandCollection $commands The command collection to find the shell in.
     * @param string $name The command name to find
     * @return \Cake\Console\Shell
     */
    protected function getShell(ConsoleIo $io, CommandCollection $commands, $name)
    {
        if (isset($this->aliases[$name])) {
            $name = $this->aliases[$name];
        }
        if (!$commands->has($name)) {
            throw new RuntimeException(
                "Unknown command `{$this->root} {$name}`." .
                " Run `{$this->root} --help` to get the list of valid commands."
            );
        }
        $classOrInstance = $commands->get($name);
        if (is_string($classOrInstance)) {
            return new $classOrInstance($io);
        }

        return $classOrInstance;
    }
}
