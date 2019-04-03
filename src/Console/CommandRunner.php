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
use Cake\Console\CommandCollectionAwareInterface;
use Cake\Console\ConsoleIo;
use Cake\Console\Exception\StopException;
use Cake\Console\Shell;
use Cake\Core\ConsoleApplicationInterface;
use Cake\Event\EventManagerTrait;
use Cake\Shell\HelpShell;
use Cake\Shell\VersionShell;
use Cake\Utility\Inflector;
use RuntimeException;

/**
 * Run CLI commands for the provided application.
 */
class CommandRunner
{
    use EventManagerTrait;

    /**
     * The application console commands are being run for.
     *
     * @var \Cake\Core\ConsoleApplicationInterface
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
     * @param \Cake\Core\ConsoleApplicationInterface $app The application to run CLI commands for.
     * @param string $root The root command name to be removed from argv.
     */
    public function __construct(ConsoleApplicationInterface $app, $root = 'cake')
    {
        $this->app = $app;
        $this->root = $root;
        $this->aliases = [
            '--version' => 'version',
            '--help' => 'help',
            '-h' => 'help',
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
     * Use the application to do the following:
     *
     * - Bootstrap the application
     * - Create the CommandCollection using the console() hook on the application.
     * - Trigger the `Console.buildCommands` event of auto-wiring plugins.
     * - Run the requested command.
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
            'help' => HelpShell::class,
        ]);
        $commands = $this->app->console($commands);
        if (!($commands instanceof CommandCollection)) {
            $type = is_object($commands) ? get_class($commands) : gettype($commands);
            throw new RuntimeException(
                "The application's `console` method did not return a CommandCollection." .
                " Got '{$type}' instead."
            );
        }
        $this->dispatchEvent('Console.buildCommands', ['commands' => $commands]);

        if (empty($argv)) {
            throw new RuntimeException("Cannot run any commands. No arguments received.");
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
     * @param \Cake\Console\ConsoleIo $io The IO wrapper for the created shell class.
     * @param \Cake\Console\CommandCollection $commands The command collection to find the shell in.
     * @param string $name The command name to find
     * @return \Cake\Console\Shell
     */
    protected function getShell(ConsoleIo $io, CommandCollection $commands, $name)
    {
        if (!$name) {
            $io->err('<error>No command provided. Choose one of the available commands.</error>', 2);
            $name = 'help';
        }
        if (isset($this->aliases[$name])) {
            $name = $this->aliases[$name];
        }
        if (!$commands->has($name)) {
            $name = Inflector::underscore($name);
        }
        if (!$commands->has($name)) {
            throw new RuntimeException(
                "Unknown command `{$this->root} {$name}`." .
                " Run `{$this->root} --help` to get the list of valid commands."
            );
        }
        $instance = $commands->get($name);
        if (is_string($instance)) {
            $instance = $this->createShell($instance, $io);
        }
        $instance->setRootName($this->root);
        if ($instance instanceof CommandCollectionAwareInterface) {
            $instance->setCommandCollection($commands);
        }

        return $instance;
    }

    /**
     * The wrapper for creating shell instances.
     *
     * @param string $className Shell class name.
     * @param \Cake\Console\ConsoleIo $io The IO wrapper for the created shell class.
     * @return \Cake\Console\Shell
     */
    protected function createShell($className, ConsoleIo $io)
    {
        return new $className($io);
    }
}
