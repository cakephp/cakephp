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
 * @since         3.5.0
 * @license       https://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Console;

use ArrayIterator;
use Countable;
use InvalidArgumentException;
use IteratorAggregate;
use Traversable;

/**
 * Collection for Commands.
 *
 * Used by Applications to specify their console commands.
 * CakePHP will use the mapped commands to construct and dispatch
 * shell commands.
 *
 * @template-implements \IteratorAggregate<string, \Cake\Console\CommandInterface|class-string<\Cake\Console\CommandInterface>>
 */
class CommandCollection implements IteratorAggregate, Countable
{
    /**
     * Command list
     *
     * @var array<string, \Cake\Console\CommandInterface|class-string<\Cake\Console\CommandInterface>>
     */
    protected array $commands = [];

    /**
     * Constructor
     *
     * @param array<string, \Cake\Console\CommandInterface|class-string<\Cake\Console\CommandInterface>> $commands The map of commands to add to the collection.
     */
    public function __construct(array $commands = [])
    {
        foreach ($commands as $name => $command) {
            $this->add($name, $command);
        }
    }

    /**
     * Add a command under an explicit name or its class's default name.
     *
     * When only a BaseCommand class name is supplied, its defaultName() is used
     * without constructing the command. An explicit name takes precedence.
     *
     * @param string $name The registration name, or a BaseCommand class name when $command is omitted.
     * @param \Cake\Console\CommandInterface|class-string<\Cake\Console\CommandInterface>|null $command The command
     *   class or instance to map, or null to use $name as the command class.
     * @return $this
     * @throws \InvalidArgumentException
     */
    public function add(string $name, CommandInterface|string|null $command = null): static
    {
        if ($command === null) {
            if (!is_subclass_of($name, BaseCommand::class)) {
                throw new InvalidArgumentException(sprintf(
                    'Cannot infer a command name for `%s`. ' .
                    'Pass a BaseCommand subclass or provide an explicit name and command.',
                    $name,
                ));
            }

            $command = $name;
            $name = $command::defaultName();
        }

        if (is_string($command)) {
            assert(
                is_subclass_of($command, CommandInterface::class),
                sprintf(
                    'Cannot use `%s` for command `%s`. ' .
                    'It is not a subclass of `%s`.',
                    $command,
                    $name,
                    CommandInterface::class,
                ),
            );
        }
        if (!preg_match('/^[^\s]+(?:(?: [^\s]+){1,2})?$/ui', $name)) {
            throw new InvalidArgumentException(
                "The command name `{$name}` is invalid. Names can only be a maximum of three words.",
            );
        }

        $this->commands[$name] = $command;

        return $this;
    }

    /**
     * Add multiple commands at once.
     *
     * @param array<string, \Cake\Console\CommandInterface|class-string<\Cake\Console\CommandInterface>> $commands A map of command names => command classes/instances.
     * @return $this
     * @see \Cake\Console\CommandCollection::add()
     */
    public function addMany(array $commands): static
    {
        foreach ($commands as $name => $class) {
            $this->add($name, $class);
        }

        return $this;
    }

    /**
     * Remove a command from the collection if it exists.
     *
     * @param string $name The named shell.
     * : static
     */
    public function remove(string $name): static
    {
        unset($this->commands[$name]);

        return $this;
    }

    /**
     * Replace a command from the collection with another command if it exists.
     *
     * @param string $oldName Name of command to remove.
     * @param string $newName The name of the command you want to map.
     * @param \Cake\Console\CommandInterface|class-string<\Cake\Console\CommandInterface> $command The command to map.
     * Can be a FQCN or CommandInterface instance.
     * @return $this
     */
    public function replace(string $oldName, string $newName, CommandInterface|string $command): static
    {
        $this->remove($oldName);
        $this->add($newName, $command);

        return $this;
    }

    /**
     * Check whether the named shell exists in the collection.
     *
     * @param string $name The named shell.
     * @return bool
     */
    public function has(string $name): bool
    {
        return isset($this->commands[$name]);
    }

    /**
     * Get the target for a command.
     *
     * @param string $name The named shell.
     * @return \Cake\Console\CommandInterface|class-string<\Cake\Console\CommandInterface> Either the command class or an instance.
     * @throws \InvalidArgumentException when unknown commands are fetched.
     */
    public function get(string $name): CommandInterface|string
    {
        if (!$this->has($name)) {
            throw new InvalidArgumentException(sprintf('The `%s` is not a known command name.', $name));
        }

        return $this->commands[$name];
    }

    /**
     * Implementation of IteratorAggregate.
     *
     * @return \Traversable<string, \Cake\Console\CommandInterface|class-string<\Cake\Console\CommandInterface>>
     */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->commands);
    }

    /**
     * Implementation of Countable.
     *
     * Get the number of commands in the collection.
     *
     * @return int
     */
    public function count(): int
    {
        return count($this->commands);
    }

    /**
     * Register attributed commands from a named AttributeResolver configuration.
     *
     * Configure resolver paths and caching, then call this from Application::console().
     * Unannotated commands can be registered explicitly with add().
     *
     * @param string $config Attribute resolver configuration name.
     * @return $this
     */
    public function addFromAttributes(string $config = 'default'): static
    {
        new AttributeCommandConnector($this)->connect($config);

        return $this;
    }

    /**
     * Get the list of available command names.
     *
     * @return array<string> Command names
     */
    public function keys(): array
    {
        return array_keys($this->commands);
    }
}
