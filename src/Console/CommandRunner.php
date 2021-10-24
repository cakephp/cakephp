<?php
declare(strict_types=1);

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

use Cake\Command\VersionCommand;
use Cake\Console\Command\HelpCommand;
use Cake\Console\Exception\MissingOptionException;
use Cake\Console\Exception\StopException;
use Cake\Core\ConsoleApplicationInterface;
use Cake\Core\ContainerApplicationInterface;
use Cake\Core\PluginApplicationInterface;
use Cake\Event\EventDispatcherInterface;
use Cake\Event\EventDispatcherTrait;
use Cake\Event\EventManager;
use Cake\Event\EventManagerInterface;
use Cake\Routing\Router;
use Cake\Routing\RoutingApplicationInterface;
use Cake\Utility\Inflector;
use InvalidArgumentException;
use RuntimeException;

/**
 * Run CLI commands for the provided application.
 */
class CommandRunner implements EventDispatcherInterface
{
    use EventDispatcherTrait;

    /**
     * The application console commands are being run for.
     *
     * @var \Cake\Core\ConsoleApplicationInterface
     */
    protected $app;

    /**
     * The application console commands are being run for.
     *
     * @var \Cake\Console\CommandFactoryInterface|null
     */
    protected $factory;

    /**
     * The root command name. Defaults to `cake`.
     *
     * @var string
     */
    protected $root;

    /**
     * Alias mappings.
     *
     * @var array<string>
     */
    protected $aliases = [];

    /**
     * Constructor
     *
     * @param \Cake\Core\ConsoleApplicationInterface $app The application to run CLI commands for.
     * @param string $root The root command name to be removed from argv.
     * @param \Cake\Console\CommandFactoryInterface|null $factory Command factory instance.
     */
    public function __construct(
        ConsoleApplicationInterface $app,
        string $root = 'cake',
        ?CommandFactoryInterface $factory = null
    ) {
        $this->app = $app;
        $this->root = $root;
        $this->factory = $factory;
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
     * @param array<string> $aliases The map of aliases to replace.
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
     * @param \Cake\Console\ConsoleIo|null $io The ConsoleIo instance. Used primarily for testing.
     * @return int The exit code of the command.
     * @throws \RuntimeException
     */
    public function run(array $argv, ?ConsoleIo $io = null): int
    {
        $this->bootstrap();

        $commands = new CommandCollection([
            'help' => HelpCommand::class,
        ]);
        if (class_exists(VersionCommand::class)) {
            $commands->add('version', VersionCommand::class);
        }
        $commands = $this->app->console($commands);

        if ($this->app instanceof PluginApplicationInterface) {
            $commands = $this->app->pluginConsole($commands);
        }
        $this->dispatchEvent('Console.buildCommands', ['commands' => $commands]);
        $this->loadRoutes();

        if (empty($argv)) {
            throw new RuntimeException('Cannot run any commands. No arguments received.');
        }
        // Remove the root executable segment
        array_shift($argv);

        $io = $io ?: new ConsoleIo();

        try {
            [$name, $argv] = $this->longestCommandName($commands, $argv);
            $name = $this->resolveName($commands, $io, $name);
        } catch (MissingOptionException $e) {
            $io->error($e->getFullMessage());

            return CommandInterface::CODE_ERROR;
        }

        $result = CommandInterface::CODE_ERROR;
        $shell = $this->getCommand($io, $commands, $name);
        if ($shell instanceof Shell) {
            $result = $this->runShell($shell, $argv);
        }
        if ($shell instanceof CommandInterface) {
            $result = $this->runCommand($shell, $argv, $io);
        }

        if ($result === null || $result === true) {
            return CommandInterface::CODE_SUCCESS;
        }
        if (is_int($result) && $result >= 0 && $result <= 255) {
            return $result;
        }

        return CommandInterface::CODE_ERROR;
    }

    /**
     * Application bootstrap wrapper.
     *
     * Calls the application's `bootstrap()` hook. After the application the
     * plugins are bootstrapped.
     *
     * @return void
     */
    protected function bootstrap(): void
    {
        $this->app->bootstrap();
        if ($this->app instanceof PluginApplicationInterface) {
            $this->app->pluginBootstrap();
        }
    }

    /**
     * Get the application's event manager or the global one.
     *
     * @return \Cake\Event\EventManagerInterface
     */
    public function getEventManager(): EventManagerInterface
    {
        if ($this->app instanceof PluginApplicationInterface) {
            return $this->app->getEventManager();
        }

        return EventManager::instance();
    }

    /**
     * Get/set the application's event manager.
     *
     * If the application does not support events and this method is used as
     * a setter, an exception will be raised.
     *
     * @param \Cake\Event\EventManagerInterface $eventManager The event manager to set.
     * @return $this
     * @throws \InvalidArgumentException
     */
    public function setEventManager(EventManagerInterface $eventManager)
    {
        if ($this->app instanceof PluginApplicationInterface) {
            $this->app->setEventManager($eventManager);

            return $this;
        }

        throw new InvalidArgumentException('Cannot set the event manager, the application does not support events.');
    }

    /**
     * Get the shell instance for a given command name
     *
     * @param \Cake\Console\ConsoleIo $io The IO wrapper for the created shell class.
     * @param \Cake\Console\CommandCollection $commands The command collection to find the shell in.
     * @param string $name The command name to find
     * @return \Cake\Console\CommandInterface|\Cake\Console\Shell
     */
    protected function getCommand(ConsoleIo $io, CommandCollection $commands, string $name)
    {
        $instance = $commands->get($name);
        if (is_string($instance)) {
            $instance = $this->createCommand($instance, $io);
        }
        if ($instance instanceof Shell) {
            $instance->setRootName($this->root);
        }
        if ($instance instanceof CommandInterface) {
            $instance->setName("{$this->root} {$name}");
        }
        if ($instance instanceof CommandCollectionAwareInterface) {
            $instance->setCommandCollection($commands);
        }

        return $instance;
    }

    /**
     * Build the longest command name that exists in the collection
     *
     * Build the longest command name that matches a
     * defined command. This will traverse a maximum of 3 tokens.
     *
     * @param \Cake\Console\CommandCollection $commands The command collection to check.
     * @param array $argv The CLI arguments.
     * @return array An array of the resolved name and modified argv.
     */
    protected function longestCommandName(CommandCollection $commands, array $argv): array
    {
        for ($i = 3; $i > 1; $i--) {
            $parts = array_slice($argv, 0, $i);
            $name = implode(' ', $parts);
            if ($commands->has($name)) {
                return [$name, array_slice($argv, $i)];
            }
        }
        $name = array_shift($argv);

        return [$name, $argv];
    }

    /**
     * Resolve the command name into a name that exists in the collection.
     *
     * Apply backwards compatible inflections and aliases.
     * Will step forward up to 3 tokens in $argv to generate
     * a command name in the CommandCollection. More specific
     * command names take precedence over less specific ones.
     *
     * @param \Cake\Console\CommandCollection $commands The command collection to check.
     * @param \Cake\Console\ConsoleIo $io ConsoleIo object for errors.
     * @param string|null $name The name from the CLI args.
     * @return string The resolved name.
     * @throws \Cake\Console\Exception\MissingOptionException
     */
    protected function resolveName(CommandCollection $commands, ConsoleIo $io, ?string $name): string
    {
        if (!$name) {
            $io->err('<error>No command provided. Choose one of the available commands.</error>', 2);
            $name = 'help';
        }
        $name = $this->aliases[$name] ?? $name;
        if (!$commands->has($name)) {
            $name = Inflector::underscore($name);
        }
        if (!$commands->has($name)) {
            throw new MissingOptionException(
                "Unknown command `{$this->root} {$name}`. " .
                "Run `{$this->root} --help` to get the list of commands.",
                $name,
                $commands->keys()
            );
        }

        return $name;
    }

    /**
     * Execute a Command class.
     *
     * @param \Cake\Console\CommandInterface $command The command to run.
     * @param array $argv The CLI arguments to invoke.
     * @param \Cake\Console\ConsoleIo $io The console io
     * @return int|null Exit code
     */
    protected function runCommand(CommandInterface $command, array $argv, ConsoleIo $io): ?int
    {
        try {
            return $command->run($argv, $io);
        } catch (StopException $e) {
            return $e->getCode();
        }
    }

    /**
     * Execute a Shell class.
     *
     * @param \Cake\Console\Shell $shell The shell to run.
     * @param array $argv The CLI arguments to invoke.
     * @return int|bool|null Exit code
     */
    protected function runShell(Shell $shell, array $argv)
    {
        try {
            $shell->initialize();

            return $shell->runCommand($argv, true);
        } catch (StopException $e) {
            return $e->getCode();
        }
    }

    /**
     * The wrapper for creating shell instances.
     *
     * @param string $className Shell class name.
     * @param \Cake\Console\ConsoleIo $io The IO wrapper for the created shell class.
     * @return \Cake\Console\CommandInterface|\Cake\Console\Shell
     */
    protected function createCommand(string $className, ConsoleIo $io)
    {
        if (!$this->factory) {
            $container = null;
            if ($this->app instanceof ContainerApplicationInterface) {
                $container = $this->app->getContainer();
            }
            $this->factory = new CommandFactory($container);
        }

        $shell = $this->factory->create($className);
        if ($shell instanceof Shell) {
            $shell->setIo($io);
        }

        return $shell;
    }

    /**
     * Ensure that the application's routes are loaded.
     *
     * Console commands and shells often need to generate URLs.
     *
     * @return void
     */
    protected function loadRoutes(): void
    {
        if (!($this->app instanceof RoutingApplicationInterface)) {
            return;
        }
        $builder = Router::createRouteBuilder('/');

        $this->app->routes($builder);
        if ($this->app instanceof PluginApplicationInterface) {
            $this->app->pluginRoutes($builder);
        }
    }
}
