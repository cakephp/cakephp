<?php
namespace Cake\Console;

use ArrayIterator;
use InvalidArgumentException;
use IteratorAggregate;

class CommandCollection implements IteratorAggregate
{
    protected $commands = [];

    public function __construct(array $commands = [])
    {
        foreach ($commands as $name => $command) {
            $this->add($name, $command);
        }
    }

    public function add($name, $command)
    {
        // Once we have a new Command class this should check
        // against that interface.
        if (!is_subclass_of($command, 'Cake\Console\Shell')) {
            throw new InvalidArgumentException(
                "'$name' is not a subclass of Cake\Console\Shell or a valid command."
            );
        }
        $this->commands[$name] = $command;

        return $this;
    }

    public function remove($name)
    {
    }

    /**
     * Check whether the named shell exists in the collection.
     *
     * @param string $name The named shell.
     * @return bool
     */
    public function has($name)
    {
        return isset($this->commands[$name]);
    }

    /**
     * Get the target for a command.
     *
     * @param string $name The named shell.
     * @return string|Cake\Console\Shell Either the shell class or an instance.
     * @throws \InvalidArgumentException when unknown commands are fetched.
     */
    public function get($name)
    {
        if (!$this->has($name)) {
            throw new InvalidArgumentException("The $name is not a known command name.");
        }
        return $this->commands[$name];
    }

    public function getIterator()
    {
        return new ArrayIterator($this->commands);
    }
}
