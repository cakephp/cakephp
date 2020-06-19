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

use InvalidArgumentException;

/**
 * Object wrapper for outputting information from a shell application.
 * Can be connected to any stream resource that can be used with fopen()
 *
 * Can generate colorized output on consoles that support it. There are a few
 * built in styles
 *
 * - `error` Error messages.
 * - `warning` Warning messages.
 * - `info` Informational messages.
 * - `comment` Additional text.
 * - `question` Magenta text used for user prompts
 *
 * By defining styles with addStyle() you can create custom console styles.
 *
 * ### Using styles in output
 *
 * You can format console output using tags with the name of the style to apply. From inside a shell object
 *
 * ```
 * $this->out('<warning>Overwrite:</warning> foo.php was overwritten.');
 * ```
 *
 * This would create orange 'Overwrite:' text, while the rest of the text would remain the normal color.
 * See ConsoleOutput::styles() to learn more about defining your own styles. Nested styles are not supported
 * at this time.
 */
class ConsoleOutput
{
    /**
     * Raw output constant - no modification of output text.
     *
     * @var int
     */
    public const RAW = 0;

    /**
     * Plain output - tags will be stripped.
     *
     * @var int
     */
    public const PLAIN = 1;

    /**
     * Color output - Convert known tags in to ANSI color escape codes.
     *
     * @var int
     */
    public const COLOR = 2;

    /**
     * Constant for a newline.
     *
     * @var string
     */
    public const LF = PHP_EOL;

    /**
     * File handle for output.
     *
     * @var resource
     */
    protected $_output;

    /**
     * The current output type.
     *
     * @see setOutputAs() For manipulation.
     * @var int
     */
    protected $_outputAs = self::COLOR;

    /**
     * text colors used in colored output.
     *
     * @var array
     */
    protected static $_foregroundColors = [
        'black' => 30,
        'red' => 31,
        'green' => 32,
        'yellow' => 33,
        'blue' => 34,
        'magenta' => 35,
        'cyan' => 36,
        'white' => 37,
    ];

    /**
     * background colors used in colored output.
     *
     * @var array
     */
    protected static $_backgroundColors = [
        'black' => 40,
        'red' => 41,
        'green' => 42,
        'yellow' => 43,
        'blue' => 44,
        'magenta' => 45,
        'cyan' => 46,
        'white' => 47,
    ];

    /**
     * Formatting options for colored output.
     *
     * @var array
     */
    protected static $_options = [
        'bold' => 1,
        'underline' => 4,
        'blink' => 5,
        'reverse' => 7,
    ];

    /**
     * Styles that are available as tags in console output.
     * You can modify these styles with ConsoleOutput::styles()
     *
     * @var array
     */
    protected static $_styles = [
        'emergency' => ['text' => 'red'],
        'alert' => ['text' => 'red'],
        'critical' => ['text' => 'red'],
        'error' => ['text' => 'red'],
        'warning' => ['text' => 'yellow'],
        'info' => ['text' => 'cyan'],
        'debug' => ['text' => 'yellow'],
        'success' => ['text' => 'green'],
        'comment' => ['text' => 'blue'],
        'question' => ['text' => 'magenta'],
        'notice' => ['text' => 'cyan'],
    ];

    /**
     * Construct the output object.
     *
     * Checks for a pretty console environment. Ansicon and ConEmu allows
     *  pretty consoles on windows, and is supported.
     *
     * @param string $stream The identifier of the stream to write output to.
     */
    public function __construct(string $stream = 'php://stdout')
    {
        $this->_output = fopen($stream, 'wb');

        if (
            (
                DIRECTORY_SEPARATOR === '\\' &&
                strpos(strtolower(php_uname('v')), 'windows 10') === false &&
                strpos(strtolower((string)env('SHELL')), 'bash.exe') === false &&
                !(bool)env('ANSICON') &&
                env('ConEmuANSI') !== 'ON'
            ) ||
            (
                function_exists('posix_isatty') &&
                !posix_isatty($this->_output)
            ) ||
            (
                env('NO_COLOR') !== null
            )
        ) {
            $this->_outputAs = self::PLAIN;
        }
    }

    /**
     * Outputs a single or multiple messages to stdout or stderr. If no parameters
     * are passed, outputs just a newline.
     *
     * @param string|string[] $message A string or an array of strings to output
     * @param int $newlines Number of newlines to append
     * @return int The number of bytes returned from writing to output.
     */
    public function write($message, int $newlines = 1): int
    {
        if (is_array($message)) {
            $message = implode(static::LF, $message);
        }

        return $this->_write($this->styleText($message . str_repeat(static::LF, $newlines)));
    }

    /**
     * Apply styling to text.
     *
     * @param string $text Text with styling tags.
     * @return string String with color codes added.
     */
    public function styleText(string $text): string
    {
        if ($this->_outputAs === static::RAW) {
            return $text;
        }
        if ($this->_outputAs === static::PLAIN) {
            $tags = implode('|', array_keys(static::$_styles));

            return preg_replace('#</?(?:' . $tags . ')>#', '', $text);
        }

        return preg_replace_callback(
            '/<(?P<tag>[a-z0-9-_]+)>(?P<text>.*?)<\/(\1)>/ims',
            [$this, '_replaceTags'],
            $text
        );
    }

    /**
     * Replace tags with color codes.
     *
     * @param array $matches An array of matches to replace.
     * @return string
     */
    protected function _replaceTags(array $matches): string
    {
        $style = $this->getStyle($matches['tag']);
        if (empty($style)) {
            return '<' . $matches['tag'] . '>' . $matches['text'] . '</' . $matches['tag'] . '>';
        }

        $styleInfo = [];
        if (!empty($style['text']) && isset(static::$_foregroundColors[$style['text']])) {
            $styleInfo[] = static::$_foregroundColors[$style['text']];
        }
        if (!empty($style['background']) && isset(static::$_backgroundColors[$style['background']])) {
            $styleInfo[] = static::$_backgroundColors[$style['background']];
        }
        unset($style['text'], $style['background']);
        foreach ($style as $option => $value) {
            if ($value) {
                $styleInfo[] = static::$_options[$option];
            }
        }

        return "\033[" . implode(';', $styleInfo) . 'm' . $matches['text'] . "\033[0m";
    }

    /**
     * Writes a message to the output stream.
     *
     * @param string $message Message to write.
     * @return int The number of bytes returned from writing to output.
     */
    protected function _write(string $message): int
    {
        return (int)fwrite($this->_output, $message);
    }

    /**
     * Gets the current styles offered
     *
     * @param string $style The style to get.
     * @return array The style or empty array.
     */
    public function getStyle(string $style): array
    {
        return static::$_styles[$style] ?? [];
    }

    /**
     * Sets style.
     *
     * ### Creates or modifies an existing style.
     *
     * ```
     * $output->setStyle('annoy', ['text' => 'purple', 'background' => 'yellow', 'blink' => true]);
     * ```
     *
     * ### Remove a style
     *
     * ```
     * $this->output->setStyle('annoy', []);
     * ```
     *
     * @param string $style The style to set.
     * @param array $definition The array definition of the style to change or create..
     * @return void
     */
    public function setStyle(string $style, array $definition): void
    {
        if (!$definition) {
            unset(static::$_styles[$style]);

            return;
        }

        static::$_styles[$style] = $definition;
    }

    /**
     * Gets all the style definitions.
     *
     * @return array
     */
    public function styles(): array
    {
        return static::$_styles;
    }

    /**
     * Get the output type on how formatting tags are treated.
     *
     * @return int
     */
    public function getOutputAs(): int
    {
        return $this->_outputAs;
    }

    /**
     * Set the output type on how formatting tags are treated.
     *
     * @param int $type The output type to use. Should be one of the class constants.
     * @return void
     * @throws \InvalidArgumentException in case of a not supported output type.
     */
    public function setOutputAs(int $type): void
    {
        if (!in_array($type, [self::RAW, self::PLAIN, self::COLOR], true)) {
            throw new InvalidArgumentException(sprintf('Invalid output type "%s".', $type));
        }

        $this->_outputAs = $type;
    }

    /**
     * Clean up and close handles
     */
    public function __destruct()
    {
        if (is_resource($this->_output)) {
            fclose($this->_output);
        }
    }
}
