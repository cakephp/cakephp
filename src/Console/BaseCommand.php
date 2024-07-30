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
 * @implements \Cake\Event\EventDispatcherInterface<\Cake\Command\Command>
 */
abstract class BaseCommand implements CommandInterface, EventDispatcherInterface
{
    /**
     * @use \Cake\Event\EventDispatcherTrait<\Cake\Command\Command>
     */
    use EventDispatcherTrait;

    /**
     * The name of this command.
     */
    protected string $name = 'cake unknown';

    /**
     * @inheritDoc
     */
    public function setName(string $name)
    {
        assert(
            str_contains($name, ' ') && !str_starts_with($name, ' '),
            sprintf("The name '%s' is missing a space. Names should look like `cake routes`", $name)
        );
        $this->name = $name;

        return $this;
    }

    /**
     * Get the command name.
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Get the command description.
     */
    public static function getDescription(): string
    {
        return '';
    }

    /**
     * Get the root command name.
     */
    public function getRootName(): string
    {
        [$root] = explode(' ', $this->name);

        return $root;
    }

    /**
     * Get the command name.
     *
     * Returns the command name based on class name.
     * For e.g. for a command with class name `UpdateTableCommand` the default
     * name returned would be `'update_table'`.
     */
    public static function defaultName(): string
    {
        $pos = strrpos(static::class, '\\');
        /** @psalm-suppress PossiblyFalseOperand */
        $name = substr(static::class, $pos + 1, -7);

        return Inflector::underscore($name);
    }

    /**
     * Get the option parser.
     *
     * You can override buildOptionParser() to define your options & arguments.
     *
     * @throws \Cake\Core\Exception\CakeException When the parser is invalid
     */
    public function getOptionParser(): ConsoleOptionParser
    {
        [$root, $name] = explode(' ', $this->name, 2);
        $parser = new ConsoleOptionParser($name);
        $parser->setRootName($root);
        $parser->setDescription(static::getDescription());

        $parser = $this->buildOptionParser($parser);

        return $parser;
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
     */
    public function initialize(): void
    {
    }

    /**
     * @inheritDoc
     */
    public function run(array $argv, ConsoleIo $io): ?int
    {
        $this->initialize();

        $parser = $this->getOptionParser();
        try {
            [$options, $arguments] = $parser->parse($argv, $io);
            $args = new Arguments(
                $arguments,
                $options,
                $parser->argumentNames()
            );
        } catch (ConsoleException $consoleException) {
            $io->err('Error: ' . $consoleException->getMessage());

            return static::CODE_ERROR;
        }

        $this->setOutputLevel($args, $io);

        if ($args->getOption('help')) {
            $this->displayHelp($parser, $args, $io);

            return static::CODE_SUCCESS;
        }

        if ($args->getOption('quiet')) {
            $io->setInteractive(false);
        }

        $this->dispatchEvent('Command.beforeExecute', ['args' => $args]);
        /** @var int|null $result */
        $result = $this->execute($args, $io);
        $this->dispatchEvent('Command.afterExecute', ['args' => $args, 'result' => $result]);

        return $result;
    }

    /**
     * Output help content
     *
     * @param \Cake\Console\ConsoleOptionParser $parser The option parser.
     * @param \Cake\Console\Arguments $args The command arguments.
     * @param \Cake\Console\ConsoleIo $io The console io
     */
    protected function displayHelp(ConsoleOptionParser $parser, Arguments $args, ConsoleIo $io): void
    {
        $format = 'text';
        if ($args->getArgumentAt(0) === 'xml') {
            $format = 'xml';
            $io->setOutputAs(ConsoleOutput::RAW);
        }

        $io->out($parser->help($format));
    }

    /**
     * Set the output level based on the Arguments.
     *
     * @param \Cake\Console\Arguments $args The command arguments.
     * @param \Cake\Console\ConsoleIo $io The console io
     */
    protected function setOutputLevel(Arguments $args, ConsoleIo $io): void
    {
        $io->setLoggers(ConsoleIo::NORMAL);
        if ($args->getOption('quiet')) {
            $io->level(ConsoleIo::QUIET);
            $io->setLoggers(ConsoleIo::QUIET);
        }

        if ($args->getOption('verbose')) {
            $io->level(ConsoleIo::VERBOSE);
            $io->setLoggers(ConsoleIo::VERBOSE);
        }
    }

    /**
     * Implement this method with your command's logic.
     *
     * @param \Cake\Console\Arguments $args The command arguments.
     * @param \Cake\Console\ConsoleIo $io The console io
     * @return int|null|void The exit code or null for success
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ReturnTypeHint.MissingNativeTypeHint
     */
    abstract public function execute(Arguments $args, ConsoleIo $io);

    /**
     * Halt the current process with a StopException.
     *
     * @param int $code The exit code to use.
     * @throws \Cake\Console\Exception\StopException
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
     * @param \Cake\Console\CommandInterface|string $command The command class name or command instance.
     * @param array $args The arguments to invoke the command with.
     * @param \Cake\Console\ConsoleIo|null $io The ConsoleIo instance to use for the executed command.
     * @return int|null The exit code or null for success of the command.
     */
    public function executeCommand(CommandInterface|string $command, array $args = [], ?ConsoleIo $io = null): ?int
    {
        if (is_string($command)) {
            assert(
                is_subclass_of($command, CommandInterface::class),
                sprintf('Command `%s` is not a subclass of `%s`.', $command, CommandInterface::class)
            );

            $command = new $command();
        }

        $io = $io ?: new ConsoleIo();

        try {
            return $command->run($args, $io);
        } catch (StopException $stopException) {
            return $stopException->getCode();
        }
    }
}
