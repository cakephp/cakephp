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
    protected string $name;

    /**
     * Short (1 character) alias for the option.
     *
     * @var string
     */
    protected string $short;

    /**
     * Help text for the option.
     *
     * @var string
     */
    protected string $help;

    /**
     * Is the option a boolean option. Boolean options do not consume a parameter.
     *
     * @var bool
     */
    protected bool $boolean;

    /**
     * Default value for the option
     *
     * @var string|bool|null
     */
    protected string|bool|null $default = null;

    /**
     * Can the option accept multiple value definition.
     *
     * @var bool
     */
    protected bool $multiple;

    /**
     * An array of choices for the option.
     *
     * @var array<string>
     */
    protected array $choices;

    /**
     * The prompt string
     *
     * @var string|null
     */
    protected ?string $prompt = null;

    /**
     * Is the option required.
     *
     * @var bool
     */
    protected bool $required;

    /**
     * The multiple separator.
     *
     * @var string|null
     */
    protected ?string $separator = null;

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
     * @param string|null $prompt The prompt string.
     * @throws \Cake\Console\Exception\ConsoleException
     */
    public function __construct(
        string $name,
        string $short = '',
        string $help = '',
        bool $isBoolean = false,
        string|bool|null $default = null,
        array $choices = [],
        bool $multiple = false,
        bool $required = false,
        ?string $prompt = null,
        ?string $separator = null,
    ) {
        $this->name = $name;
        $this->short = $short;
        $this->help = $help;
        $this->boolean = $isBoolean;
        $this->choices = $choices;
        $this->multiple = $multiple;
        $this->required = $required;
        $this->prompt = $prompt;
        $this->separator = $separator;

        if ($isBoolean) {
            $this->default = (bool)$default;
        } elseif ($default !== null) {
            $this->default = (string)$default;
        }

        if (strlen($this->short) > 1) {
            throw new ConsoleException(
                sprintf('Short option `%s` is invalid, short options must be one letter.', $this->short),
            );
        }
        if ($this->default !== null && $this->prompt) {
            throw new ConsoleException(
                'You cannot set both `prompt` and `default` options. ' .
                'Use either a static `default` or interactive `prompt`',
            );
        }

        if ($this->separator !== null && str_contains($this->separator, ' ')) {
            throw new ConsoleException(
                sprintf(
                    'The option separator must not contain spaces for `%s`.',
                    $this->name,
                ),
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
        return $this->name;
    }

    /**
     * Get the value of the short attribute.
     *
     * @return string Value of this->_short.
     */
    public function short(): string
    {
        return $this->short;
    }

    /**
     * Generate the help for this option.
     *
     * @param int $width The width to make the name of the option.
     * @return string
     */
    public function help(int $width = 0): string
    {
        $default = '';
        $short = '';
        if ($this->default && $this->default !== true) {
            $default = sprintf(' <comment>(default: %s)</comment>', $this->default);
        }
        if ($this->choices) {
            $default .= sprintf(' <comment>(choices: %s)</comment>', implode('|', $this->choices));
        }
        if ($this->multiple && $this->separator) {
            $default .= sprintf(' <comment>(separator: `%s`)</comment>', $this->separator);
        }

        if ($this->short !== '') {
            $short = ', -' . $this->short;
        }
        $name = sprintf('--%s%s', $this->name, $short);
        if (strlen($name) < $width) {
            $name = str_pad($name, $width, ' ');
        }
        $required = '';
        if ($this->isRequired()) {
            $required = ' <comment>(required)</comment>';
        }

        return sprintf('%s%s%s%s', $name, $this->help, $default, $required);
    }

    /**
     * Get the usage value for this option
     *
     * @return string
     */
    public function usage(): string
    {
        $name = $this->short === '' ? '--' . $this->name : '-' . $this->short;
        $default = '';
        if ($this->default !== null && !is_bool($this->default) && $this->default !== '') {
            $default = ' ' . $this->default;
        }
        if ($this->choices) {
            $default = ' ' . implode('|', $this->choices);
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
    public function defaultValue(): string|bool|null
    {
        return $this->default;
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
        return $this->boolean;
    }

    /**
     * Check if this option accepts multiple values.
     *
     * @return bool
     */
    public function acceptsMultiple(): bool
    {
        return $this->multiple;
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
        if ($this->choices === []) {
            return true;
        }
        if (is_string($value) && $this->separator) {
            $values = explode($this->separator, $value);
        } else {
            $values = [$value];
        }
        if ($this->boolean) {
            $values = array_map('boolval', $values);
        }

        $unwanted = array_filter($values, fn(bool|string $value) => !in_array($value, $this->choices, true));
        if ($unwanted) {
            throw new ConsoleException(
                sprintf(
                    '`%s` is not a valid value for `--%s`. Please use one of `%s`',
                    $value,
                    $this->name,
                    implode('|', $this->choices),
                ),
            );
        }

        return true;
    }

    /**
     * Get the list of choices this option has.
     *
     * @return array<string>
     */
    public function choices(): array
    {
        return $this->choices;
    }

    /**
     * Get the prompt string
     *
     * @return string
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
        $option->addAttribute('name', '--' . $this->name);
        $short = '';
        if ($this->short !== '') {
            $short = '-' . $this->short;
        }
        $default = $this->default;
        if ($default === true) {
            $default = 'true';
        } elseif ($default === false) {
            $default = 'false';
        }
        $option->addAttribute('short', $short);
        $option->addAttribute('help', $this->help);
        $option->addAttribute('boolean', (string)(int)$this->boolean);
        $option->addAttribute('required', (string)(int)$this->required);
        $option->addChild('default', (string)$default);
        $choices = $option->addChild('choices');
        foreach ($this->choices as $valid) {
            $choices->addChild('choice', $valid);
        }

        return $parent;
    }

    /**
     * Get the value of the separator.
     *
     * @return string|null Value of this->_separator.
     */
    public function separator(): ?string
    {
        return $this->separator;
    }
}
