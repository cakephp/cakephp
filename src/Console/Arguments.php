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
 * @since         3.6.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Console;

use Cake\Console\Exception\ConsoleException;

/**
 * Provides an interface for interacting with
 * a command's options and arguments.
 */
class Arguments
{
    /**
     * Positional argument name map
     *
     * @var array<int, string>
     */
    protected array $argNames;

    /**
     * Positional arguments.
     *
     * @var array<int, string>
     */
    protected array $args;

    /**
     * Named options
     *
     * @var array<string, list<string>|string|bool|null>
     */
    protected array $options;

    /**
     * Constructor
     *
     * @param array<int, string> $args Positional arguments
     * @param array<string, list<string>|string|bool|null> $options Named arguments
     * @param array<int, string> $argNames List of argument names. Order is expected to be
     *  the same as $args.
     */
    public function __construct(array $args, array $options, array $argNames)
    {
        $this->args = $args;
        $this->options = $options;
        $this->argNames = $argNames;
    }

    /**
     * Get all positional arguments.
     *
     * @return array<int, string>
     */
    public function getArguments(): array
    {
        return $this->args;
    }

    /**
     * Get positional arguments by index.
     *
     * @param int $index The argument index to access.
     * @return string|null The argument value or null
     */
    public function getArgumentAt(int $index): ?string
    {
        if (!$this->hasArgumentAt($index)) {
            return null;
        }

        return $this->args[$index];
    }

    /**
     * Check if a positional argument exists by index
     *
     * @param int $index The argument index to check.
     * @return bool
     */
    public function hasArgumentAt(int $index): bool
    {
        return isset($this->args[$index]);
    }

    /**
     * Check if a positional argument exists by name
     *
     * @param string $name The argument name to check.
     * @return bool
     */
    public function hasArgument(string $name): bool
    {
        $offset = array_search($name, $this->argNames, true);
        if ($offset === false) {
            return false;
        }

        return isset($this->args[$offset]);
    }

    /**
     * Returns positional argument value by name or null if doesn't exist
     *
     * @param string $name The argument name to check.
     * @return string|null
     */
    public function getArgument(string $name): ?string
    {
        $this->assertArgumentExists($name);

        $offset = array_search($name, $this->argNames, true);
        if ($offset === false || !isset($this->args[$offset])) {
            return null;
        }

        return $this->args[$offset];
    }

    /**
     * Get an array of all the options
     *
     * @return array<string, list<string>|string|bool|null>
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * Get a non-multiple option's value or null if not set.
     *
     * @param string $name The name of the option to check.
     * @return string|bool|null
     */
    public function getOption(string $name): string|bool|null
    {
        $value = $this->options[$name] ?? null;
        if (is_array($value)) {
            throw new ConsoleException(sprintf(
                'Cannot get multiple values for option `%s`, use `getMultipleOption()` instead.',
                $name
            ));
        }

        assert($value === null || is_string($value) || is_bool($value));

        return $value;
    }

    /**
     * Get a boolean option's value or null if not set.
     *
     * @return bool|null
     */
    public function getBooleanOption(string $name): ?bool
    {
        $value = $this->options[$name] ?? null;
        if ($value !== null && !is_bool($value)) {
            throw new ConsoleException(sprintf(
                'Option `%s` is not of type `bool`, use `getOption()` instead.',
                $name
            ));
        }

        return $value;
    }

    /**
     * Gets a multiple option's value or null if not set.
     *
     * @return list<string>|null
     */
    public function getMultipleOption(string $name): ?array
    {
        $value = $this->options[$name] ?? null;
        if ($value !== null && !is_array($value)) {
            throw new ConsoleException(sprintf(
                'Option `%s` is not of type `array`, use `getOption()` instead.',
                $name
            ));
        }

        return $value;
    }

    /**
     * Check if an option is defined and not null.
     *
     * @param string $name The name of the option to check.
     * @return bool
     */
    public function hasOption(string $name): bool
    {
        return isset($this->options[$name]);
    }

    /**
     * @param string $name
     * @return void
     */
    protected function assertArgumentExists(string $name): void
    {
        if (in_array($name, $this->argNames, true)) {
            return;
        }

        throw new ConsoleException(sprintf(
            'Argument `%s` is not defined on this Command. Could this be an option maybe?',
            $name
        ));
    }
}
