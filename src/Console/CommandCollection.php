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
     * Add a command to the collection
     *
     * @param string $name The name of the command you want to map.
     * @param \Cake\Console\CommandInterface|class-string<\Cake\Console\CommandInterface> $command The command to map.
     *   Can be a FQCN or CommandInterface instance.
     * @return $this
     * @throws \InvalidArgumentException
     */
    public function add(string $name, CommandInterface|string $command)
    {
        if (is_string($command)) {
            assert(
                is_subclass_of($command, CommandInterface::class),
                sprintf(
                    'Cannot use `%s` for command `%s`. ' .
                    'It is not a subclass of `%s`.',
                    $command,
                    $name,
                    CommandInterface::class
                )
            );
        }
        if (!preg_match('/^[^\s]+(?:(?: [^\s]+){1,2})?$/ui', $name)) {
            throw new InvalidArgumentException(
                "The command name `{$name}` is invalid. Names can only be a maximum of three words."
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
    public function addMany(array $commands)
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
     * @return $this
     */
    public function remove(string $name)
    {
        unset($this->commands[$name]);

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
     * @return \Traversable
     * @psalm-return \Traversable<string, \Cake\Console\CommandInterface|class-string<\Cake\Console\CommandInterface>>
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
     * Auto-discover commands from the named plugin.
     *
     * Discovered commands will have their names de-duplicated with
     * existing commands in the collection. If a command is already
     * defined in the collection and discovered in a plugin, only
     * the long name (`plugin.command`) will be returned.
     *
     * @param string $plugin The plugin to scan.
     * @return array<string, class-string<\Cake\Console\CommandInterface>> Discovered plugin commands.
     */
    public function discoverPlugin(string $plugin): array
    {
        $scanner = new CommandScanner();
        $shells = $scanner->scanPlugin($plugin);

        return $this->resolveNames($shells);
    }

    /**
     * Resolve names based on existing commands
     *
     * @param array<array<string, string>> $input The results of a CommandScanner operation.
     * @return array<string, class-string<\Cake\Console\CommandInterface>> A flat map of command names => class names.
     */
    protected function resolveNames(array $input): array
    {
        $out = [];
        foreach ($input as $info) {
            $name = $info['name'];
            $addLong = $name !== $info['fullName'];

            // If the short name has been used, use the full name.
            // This allows app shells to have name preference.
            // and app shells to overwrite core shells.
            if ($this->has($name) && $addLong) {
                $name = $info['fullName'];
            }

            /** @var class-string<\Cake\Console\CommandInterface> $class */
            $class = $info['class'];
            $out[$name] = $class;
            if ($addLong) {
                $out[$info['fullName']] = $class;
            }
        }

        return $out;
    }

    /**
     * Automatically discover commands in CakePHP, the application and all plugins.
     *
     * Commands will be located using filesystem conventions. Commands are
     * discovered in the following order:
     *
     * - CakePHP provided commands
     * - Application commands
     *
     * Commands defined in the application will overwrite commands with
     * the same name provided by CakePHP.
     *
     * @return array<string, class-string<\Cake\Console\CommandInterface>> An array of command names and their classes.
     */
    public function autoDiscover(): array
    {
        $scanner = new CommandScanner();

        $core = $this->resolveNames($scanner->scanCore());
        $app = $this->resolveNames($scanner->scanApp());

        return $app + $core;
    }

    /**
     * Get the list of available command names.
     *
     * @return list<string> Command names
     */
    public function keys(): array
    {
        return array_keys($this->commands);
    }
}
