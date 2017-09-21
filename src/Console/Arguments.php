<?php
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

/**
 * Provides an interface for interacting with
 * a command's options and arguments.
 */
class Arguments
{
    /**
     * Positional argument name map
     *
     * @var array
     */
    protected $argNames;

    /**
     * Positional arguments.
     *
     * @var string[]
     */
    protected $args;

    /**
     * Named options
     *
     * @var string[]
     */
    protected $options;

    /**
     * Constructor
     *
     * @param string[] $args Positional arguments
     * @param array $options Named arguments
     * @param array $argNames Map of argument names and their indexes.
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
     * @return array
     */
    public function getArguments()
    {
        return $this->args;
    }

    /**
     * Get positional arguments by index.
     *
     * @param int $index The argument index to access.
     * @return string|null The argument value or null
     */
    public function getArgumentAt($index)
    {
        if ($this->hasArgumentAt($index)) {
            return $this->args[$index];
        }

        return null;
    }

    /**
     * Check if a positional argument exists
     *
     * @param int $index The argument index to check.
     * @return bool
     */
    public function hasArgumentAt($index)
    {
        return isset($this->args[$index]);
    }

    /**
     * Check if a positional argument exists by name
     *
     * @param string $name The argument name to check.
     * @return bool
     */
    public function hasArgument($name)
    {
        if (!isset($this->argNames[$name])) {
            return false;
        }
        $index = $this->argNames[$name];

        return isset($this->args[$index]);
    }

    /**
     * Check if a positional argument exists by name
     *
     * @param string $name The argument name to check.
     * @return string|null
     */
    public function getArgument($name)
    {
        if (!isset($this->argNames[$name])) {
            return null;
        }
        $index = $this->argNames[$name];

        return $this->args[$index];
    }
}
