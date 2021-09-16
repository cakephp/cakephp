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
     * Name of the option
     *
     * @var string
     */
    protected $_name;

    /**
     * Short (1 character) alias for the option.
     *
     * @var string
     */
    protected $_short;

    /**
     * Help text for the option.
     *
     * @var string
     */
    protected $_help;

    /**
     * Is the option a boolean option. Boolean options do not consume a parameter.
     *
     * @var bool
     */
    protected $_boolean;

    /**
     * Default value for the option
     *
     * @var string|bool|null
     */
    protected $_default;

    /**
     * Can the option accept multiple value definition.
     *
     * @var bool
     */
    protected $_multiple;

    /**
     * An array of choices for the option.
     *
     * @var array<string>
     */
    protected $_choices;

    /**
     * Is the option required.
     *
     * @var bool
     */
    protected $required;

    /**
     * Make a new Input Option
     *
     * @param string $name The long name of the option, or an array with all the properties.
     * @param string $short The short alias for this option
     * @param string $help The help text for this option
     * @param bool $isBoolean Whether this option is a boolean option. Boolean options don't consume extra tokens
     * @param string|bool|null $default The default value for this option.
     * @param array<string> $choices Valid choices for this option.
     * @param bool $multiple Whether this option can accept multiple value definition.
     * @param bool $required Whether this option is required or not.
     * @throws \Cake\Console\Exception\ConsoleException
     */
    public function __construct(
        string $name,
        string $short = '',
        string $help = '',
        bool $isBoolean = false,
        $default = null,
        array $choices = [],
        bool $multiple = false,
        bool $required = false
    ) {
        $this->_name = $name;
        $this->_short = $short;
        $this->_help = $help;
        $this->_boolean = $isBoolean;
        $this->_choices = $choices;
        $this->_multiple = $multiple;
        $this->required = $required;

        if ($isBoolean) {
            $this->_default = (bool)$default;
        } elseif ($default !== null) {
            $this->_default = (string)$default;
        }

        if (strlen($this->_short) > 1) {
            throw new ConsoleException(
                sprintf('Short option "%s" is invalid, short options must be one letter.', $this->_short)
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
     * @return string
     */
    public function help(int $width = 0): string
    {
        $default = $short = '';
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
     *
     * @return string
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
     *
     * @return string|bool|null
     */
    public function defaultValue()
    {
        return $this->_default;
    }

    /**
     * Check if this option is required
     *
     * @return bool
     */
    public function isRequired(): bool
    {
        return $this->required;
    }

    /**
     * Check if this option is a boolean option
     *
     * @return bool
     */
    public function isBoolean(): bool
    {
        return $this->_boolean;
    }

    /**
     * Check if this option accepts multiple values.
     *
     * @return bool
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
    public function validChoice($value): bool
    {
        if (empty($this->_choices)) {
            return true;
        }
        if (!in_array($value, $this->_choices, true)) {
            throw new ConsoleException(
                sprintf(
                    '"%s" is not a valid value for --%s. Please use one of "%s"',
                    (string)$value,
                    $this->_name,
                    implode(', ', $this->_choices)
                )
            );
        }

        return true;
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
