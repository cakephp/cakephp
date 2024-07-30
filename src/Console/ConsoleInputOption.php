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
 * @since         2.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Console;

use Cake\Console\Exception\ConsoleException;
use SimpleXMLElement;

/**
 * An object to represent a single option used in the command line.
 * ConsoleOptionParser creates these when you use addOption()
 *
 * @see \Cake\Console\ConsoleOptionParser::addOption()
 */
class ConsoleInputOption
{
    /**
     * Default value for the option
     */
    protected string|bool|null $_default = null;

    /**
     * Make a new Input Option
     *
     * @param string $_name The long name of the option, or an array with all the properties.
     * @param string $_short The short alias for this option
     * @param string $_help The help text for this option
     * @param bool $_boolean Whether this option is a boolean option. Boolean options don't consume extra tokens
     * @param string|bool|null $default The default value for this option.
     * @param list<string> $_choices Valid choices for this option.
     * @param bool $_multiple Whether this option can accept multiple value definition.
     * @param bool $required Whether this option is required or not.
     * @param string|null $prompt The prompt string.
     * @throws \Cake\Console\Exception\ConsoleException
     */
    public function __construct(
        protected string $_name,
        protected string $_short = '',
        protected string $_help = '',
        protected bool $_boolean = false,
        string|bool|null $default = null,
        protected array $_choices = [],
        protected bool $_multiple = false,
        protected bool $required = false,
        protected ?string $prompt = null
    ) {
        if ($this->_boolean) {
            $this->_default = (bool)$default;
        } elseif ($default !== null) {
            $this->_default = (string)$default;
        }

        if (strlen($this->_short) > 1) {
            throw new ConsoleException(
                sprintf('Short option `%s` is invalid, short options must be one letter.', $this->_short)
            );
        }

        if ($this->_default !== null && $this->prompt) {
            throw new ConsoleException(
                'You cannot set both `prompt` and `default` options. ' .
                'Use either a static `default` or interactive `prompt`'
            );
        }
    }

    /**
     * Get the value of the name attribute.
     *
     * @return string Value of this->_name.
     */
    public function name(): string
    {
        return $this->_name;
    }

    /**
     * Get the value of the short attribute.
     *
     * @return string Value of this->_short.
     */
    public function short(): string
    {
        return $this->_short;
    }

    /**
     * Generate the help for this this option.
     *
     * @param int $width The width to make the name of the option.
     */
    public function help(int $width = 0): string
    {
        $default = '';
        $short = '';
        if ($this->_default && $this->_default !== true) {
            $default = sprintf(' <comment>(default: %s)</comment>', $this->_default);
        }

        if ($this->_choices) {
            $default .= sprintf(' <comment>(choices: %s)</comment>', implode('|', $this->_choices));
        }

        if ($this->_short !== '') {
            $short = ', -' . $this->_short;
        }

        $name = sprintf('--%s%s', $this->_name, $short);
        if (strlen($name) < $width) {
            $name = str_pad($name, $width, ' ');
        }

        $required = '';
        if ($this->isRequired()) {
            $required = ' <comment>(required)</comment>';
        }

        return sprintf('%s%s%s%s', $name, $this->_help, $default, $required);
    }

    /**
     * Get the usage value for this option
     */
    public function usage(): string
    {
        $name = $this->_short === '' ? '--' . $this->_name : '-' . $this->_short;
        $default = '';
        if ($this->_default !== null && !is_bool($this->_default) && $this->_default !== '') {
            $default = ' ' . $this->_default;
        }

        if ($this->_choices) {
            $default = ' ' . implode('|', $this->_choices);
        }

        $template = '[%s%s]';
        if ($this->isRequired()) {
            $template = '%s%s';
        }

        return sprintf($template, $name, $default);
    }

    /**
     * Get the default value for this option
     */
    public function defaultValue(): string|bool|null
    {
        return $this->_default;
    }

    /**
     * Check if this option is required
     */
    public function isRequired(): bool
    {
        return $this->required;
    }

    /**
     * Check if this option is a boolean option
     */
    public function isBoolean(): bool
    {
        return $this->_boolean;
    }

    /**
     * Check if this option accepts multiple values.
     */
    public function acceptsMultiple(): bool
    {
        return $this->_multiple;
    }

    /**
     * Check that a value is a valid choice for this option.
     *
     * @param string|bool $value The choice to validate.
     * @return true
     * @throws \Cake\Console\Exception\ConsoleException
     */
    public function validChoice(string|bool $value): bool
    {
        if (empty($this->_choices)) {
            return true;
        }

        if (!in_array($value, $this->_choices, true)) {
            throw new ConsoleException(
                sprintf(
                    '`%s` is not a valid value for `--%s`. Please use one of `%s`',
                    (string)$value,
                    $this->_name,
                    implode(', ', $this->_choices)
                )
            );
        }

        return true;
    }

    /**
     * Get the list of choices this option has.
     */
    public function choices(): array
    {
        return $this->_choices;
    }

    /**
     * Get the prompt string
     */
    public function prompt(): string
    {
        return (string)$this->prompt;
    }

    /**
     * Append the option's XML into the parent.
     *
     * @param \SimpleXMLElement $parent The parent element.
     * @return \SimpleXMLElement The parent with this option appended.
     */
    public function xml(SimpleXMLElement $parent): SimpleXMLElement
    {
        $option = $parent->addChild('option');
        $option->addAttribute('name', '--' . $this->_name);

        $short = '';
        if ($this->_short !== '') {
            $short = '-' . $this->_short;
        }

        $default = $this->_default;
        if ($default === true) {
            $default = 'true';
        } elseif ($default === false) {
            $default = 'false';
        }

        $option->addAttribute('short', $short);
        $option->addAttribute('help', $this->_help);
        $option->addAttribute('boolean', (string)(int)$this->_boolean);
        $option->addAttribute('required', (string)(int)$this->required);
        $option->addChild('default', (string)$default);

        $choices = $option->addChild('choices');
        foreach ($this->_choices as $valid) {
            $choices->addChild('choice', $valid);
        }

        return $parent;
    }
}
