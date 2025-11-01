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

use Cake\Console\Exception\ConsoleException;
use Cake\Console\Exception\StopException;
use Cake\Event\EventDispatcherInterface;
use Cake\Event\EventDispatcherTrait;
use Cake\Event\EventInterface;
use Cake\Event\EventListenerInterface;
use Cake\Utility\Inflector;

/**
 * Base class for console commands.
 *
 * Provides hooks for common command features:
 *
 * - `initialize` Acts as a post-construct hook.
 * - `buildOptionParser` Build/Configure the option parser for your command.
 * - `execute` Execute your command with parsed Arguments and ConsoleIo
 *
 * ### Life cycle callbacks
 *
 * CakePHP fires a number of life cycle callbacks during each command execution.
 * By implementing a method you can receive the related events. The available
 * callbacks are:
 *
 * - `beforeExecute(EventInterface $event)`
 *   Called immediately prior to the command's run method. This is a good place to do
 *   general logic that applies to command setup.
 * - `afterExecute(EventInterface $event)`
 *   Called immediately after the command's run method, unless an exception occurs.
 *
 * @implements \Cake\Event\EventDispatcherInterface<\Cake\Command\Command>
 */
abstract class BaseCommand implements CommandInterface, EventDispatcherInterface, EventListenerInterface
{
    /**
     * @use \Cake\Event\EventDispatcherTrait<\Cake\Command\Command>
     */
    use EventDispatcherTrait;

    /**
     * The name of this command.
     *
     * @var string
     */
    protected string $name = 'cake unknown';

    /**
     * The IO instance to interact with IO
     *
     * @var \Cake\Console\ConsoleIoInterface
     */
    protected ConsoleIoInterface $io;

    /**
     * The arguments instance which holds the parsed arguments and options
     *
     * @var \Cake\Console\Arguments
     */
    protected Arguments $args;

    /**
     * Constructor
     *
     * @param \Cake\Console\CommandFactoryInterface|null $factory The factory, which is needed to invoke more commands
     */
    public function __construct(
        protected ?CommandFactoryInterface $factory = null,
    ) {
        $this->getEventManager()->on($this);
    }

    /**
     * @inheritDoc
     */
    public function setName(string $name): static
    {
        assert(
            str_contains($name, ' ') && !str_starts_with($name, ' '),
            "The name '{$name}' is missing a space. Names should look like `cake routes`",
        );
        $this->name = $name;

        return $this;
    }

    /**
     * Get the command name.
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Get the command description.
     *
     * @return string
     */
    public static function getDescription(): string
    {
        return '';
    }

    /**
     * Get the root command name.
     *
     * @return string
     */
    public function getRootName(): string
    {
        [$root] = explode(' ', $this->name);

        return $root;
    }

    /**
     * @param \Cake\Console\ConsoleIoInterface $io
     * @return void
     */
    public function setIo(ConsoleIoInterface $io): void
    {
        $this->io = $io;
    }

    /**
     * Get the command name.
     *
     * Returns the command name based on class name.
     * For e.g. for a command with class name `UpdateTableCommand` the default
     * name returned would be `'update_table'`.
     *
     * @return string
     */
    public static function defaultName(): string
    {
        $pos = strrpos(static::class, '\\');
        $name = substr(static::class, $pos + 1, -7);

        return Inflector::underscore($name);
    }

    /**
     * Get the option parser.
     *
     * You can override buildOptionParser() to define your options & arguments.
     *
     * @return \Cake\Console\ConsoleOptionParser
     * @throws \Cake\Core\Exception\CakeException When the parser is invalid
     */
    public function getOptionParser(): ConsoleOptionParser
    {
        [$root, $name] = explode(' ', $this->name, 2);
        $parser = new ConsoleOptionParser($name);
        $parser->setRootName($root);
        $parser->setDescription(static::getDescription());

        return $this->buildOptionParser($parser);
    }

    /**
     * Hook method for defining this command's option parser.
     *
     * @param \Cake\Console\ConsoleOptionParser $parser The parser to be defined
     * @return \Cake\Console\ConsoleOptionParser The built parser.
     */
    protected function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser
    {
        return $parser;
    }

    /**
     * Hook method invoked by CakePHP when a command is about to be executed.
     *
     * Override this method and implement expensive/important setup steps that
     * should not run on every command run. This method will be called *before*
     * the options and arguments are validated and processed.
     *
     * @return void
     */
    public function initialize(): void
    {
    }

    /**
     * Returns a list of all events that will fire in the command during its lifecycle.
     * You can override this function to add your own listener callbacks
     *
     * @return array<string, mixed>
     */
    public function implementedEvents(): array
    {
        return [
            'Command.beforeExecute' => 'beforeExecute',
            'Command.afterExecute' => 'afterExecute',
        ];
    }

    /**
     * Called immediately prior to the command's run method. You can use this method to configure and customize the
     * command or perform logic that needs to happen before the command runs.
     *
     * @param \Cake\Event\EventInterface<\Cake\Console\BaseCommand> $event An Event instance
     * @return void
     * @link https://book.cakephp.org/5/en/console-commands/commands.html#lifecycle-callbacks
     */
    public function beforeExecute(EventInterface $event, Arguments $args, ConsoleIoInterface $io): void
    {
    }

    /**
     * Called immediately after the command's run method, unless an exception occurs. You can use this method to
     * perform logic that needs to happen after the command runs.
     *
     * @param \Cake\Event\EventInterface<\Cake\Console\BaseCommand> $event An Event instance
     * @param int|null $result
     * @return void
     * @link https://book.cakephp.org/5/en/console-commands/commands.html#lifecycle-callbacks
     */
    public function afterExecute(EventInterface $event, Arguments $args, ConsoleIoInterface $io, ?int $result): void
    {
    }

    /**
     * @inheritDoc
     */
    public function run(array $argv, ?ConsoleIoInterface $io = null): ?int
    {
        if ($io !== null) {
            $this->io = $io;
        }

        $parser = $this->getOptionParser();
        try {
            $this->parseArguments($parser, $argv);
        } catch (ConsoleException $e) {
            $this->io->error('Error: ' . $e->getMessage());

            return static::CODE_ERROR;
        }
        $this->setOutputLevel();

        if ($this->args->getOption('help')) {
            $this->displayHelp($parser);

            return static::CODE_SUCCESS;
        }

        if ($this->args->getOption('quiet')) {
            $this->io->setInteractive(false);
        }

        $this->initialize();

        $this->dispatchEvent('Command.beforeExecute', ['args' => $this->args, 'io' => $this->io]);
        /** @var int|null $result */
        $result = $this->execute();
        $this->dispatchEvent('Command.afterExecute', ['args' => $this->args, 'io' => $this->io, 'result' => $result]);

        return $result;
    }

    /**
     * Output help content
     *
     * @param \Cake\Console\ConsoleOptionParser $parser The option parser.
     * @return void
     */
    protected function displayHelp(ConsoleOptionParser $parser): void
    {
        $format = 'text';
        if ($this->args->getArgumentAt(0) === 'xml') {
            $format = 'xml';
            $this->io->setOutputAs(ConsoleOutput::RAW);
        }

        $this->io->out($parser->help($format));
    }

    /**
     * Set the output level based on the Arguments.
     *
     * @return void
     */
    protected function setOutputLevel(): void
    {
        $this->io->setLoggers(ConsoleIoInterface::NORMAL);
        if ($this->args->getOption('quiet')) {
            $this->io->level(ConsoleIoInterface::QUIET);
            $this->io->setLoggers(ConsoleIoInterface::QUIET);
        }
        if ($this->args->getOption('verbose')) {
            $this->io->level(ConsoleIoInterface::VERBOSE);
            $this->io->setLoggers(ConsoleIoInterface::VERBOSE);
        }
    }

    /**
     * Parses the command-line arguments using the provided option parser and assigns
     * the parsed options and arguments to the command's arguments property.
     *
     * @param \Cake\Console\ConsoleOptionParser $parser
     * @param array $argv
     * @return void
     */
    protected function parseArguments(ConsoleOptionParser $parser, array $argv): void
    {
        [$options, $arguments] = $parser->parse($argv, $this->io);
        $this->args = new Arguments(
            $arguments,
            $options,
            $parser->argumentNames(),
        );
    }

    /**
     * Implement this method with your command's logic.
     *
     * @return int|null|void The exit code or null for success
     */
    abstract public function execute();

    /**
     * Halt the current process with a StopException.
     *
     * @param int $code The exit code to use.
     * @throws \Cake\Console\Exception\StopException
     * @return never
     */
    public function abort(int $code = self::CODE_ERROR): never
    {
        throw new StopException('Command aborted', $code);
    }

    /**
     * Execute another command with the provided set of arguments.
     *
     * If you are using a string command name, that command's dependencies
     * will not be resolved with the application container. Instead you will
     * need to pass the command as an object with all of its dependencies.
     *
     * @param \Cake\Console\CommandInterface|class-string<\Cake\Console\CommandInterface> $command The command class name or command instance.
     * @param array $args The arguments to invoke the command with.
     * @param \Cake\Console\ConsoleIoInterface|null $io The ConsoleIo instance to use for the executed command.
     * @return int|null The exit code or null for success of the command.
     */
    public function executeCommand(
        CommandInterface|string $command,
        array $args = [],
        ?ConsoleIoInterface $io = null,
    ): ?int {
        if (is_string($command)) {
            assert(
                is_subclass_of($command, CommandInterface::class),
                sprintf('Command `%s` is not a subclass of `%s`.', $command, CommandInterface::class),
            );

            $command = $this->factory?->create($command) ?? new $command();
        }

        try {
            return $command->run($args, $io ?? $this->io);
        } catch (StopException $e) {
            return $e->getCode();
        }
    }
}
