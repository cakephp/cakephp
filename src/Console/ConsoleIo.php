<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         3.0.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Console;

use Cake\Log\Engine\ConsoleLog;
use Cake\Log\Log;

/**
 * A wrapper around the various IO operations shell tasks need to do.
 *
 * Packages up the stdout, stderr, and stdin streams providing a simple
 * consistent interface for shells to use. This class also makes mocking streams
 * easy to do in unit tests.
 */
class ConsoleIo
{

    /**
     * The output stream
     *
     * @var \Cake\Console\ConsoleOutput
     */
    protected $_out;

    /**
     * The error stream
     *
     * @var \Cake\Console\ConsoleOutput
     */
    protected $_err;

    /**
     * The input stream
     *
     * @var \Cake\Console\ConsoleInput
     */
    protected $_in;

    /**
     * Output constant making verbose shells.
     *
     * @var int
     */
    const VERBOSE = 2;

    /**
     * Output constant for making normal shells.
     *
     * @var int
     */
    const NORMAL = 1;

    /**
     * Output constants for making quiet shells.
     *
     * @var int
     */
    const QUIET = 0;

    /**
     * The current output level.
     *
     * @var int
     */
    protected $_level = ConsoleIo::NORMAL;

    /**
     * The number of bytes last written to the output stream
     * used when overwriting the previous message.
     *
     * @var int
     */
    protected $_lastWritten = 0;

    /**
     * Constructor
     *
     * @param \Cake\Console\ConsoleOutput|null $out A ConsoleOutput object for stdout.
     * @param \Cake\Console\ConsoleOutput|null $err A ConsoleOutput object for stderr.
     * @param \Cake\Console\ConsoleInput|null $in A ConsoleInput object for stdin.
     */
    public function __construct(ConsoleOutput $out = null, ConsoleOutput $err = null, ConsoleInput $in = null)
    {
        $this->_out = $out ? $out : new ConsoleOutput('php://stdout');
        $this->_err = $err ? $err : new ConsoleOutput('php://stderr');
        $this->_in = $in ? $in : new ConsoleInput('php://stdin');
    }

    /**
     * Get/set the current output level.
     *
     * @param null|int $level The current output level.
     * @return int The current output level.
     */
    public function level($level = null)
    {
        if ($level !== null) {
            $this->_level = $level;
        }
        return $this->_level;
    }

    /**
     * Output at the verbose level.
     *
     * @param string|array $message A string or an array of strings to output
     * @param int $newlines Number of newlines to append
     * @return int|bool Returns the number of bytes returned from writing to stdout.
     */
    public function verbose($message, $newlines = 1)
    {
        return $this->out($message, $newlines, self::VERBOSE);
    }

    /**
     * Output at all levels.
     *
     * @param string|array $message A string or an array of strings to output
     * @param int $newlines Number of newlines to append
     * @return int|bool Returns the number of bytes returned from writing to stdout.
     */
    public function quiet($message, $newlines = 1)
    {
        return $this->out($message, $newlines, self::QUIET);
    }

    /**
     * Outputs a single or multiple messages to stdout. If no parameters
     * are passed outputs just a newline.
     *
     * ### Output levels
     *
     * There are 3 built-in output level. Shell::QUIET, Shell::NORMAL, Shell::VERBOSE.
     * The verbose and quiet output levels, map to the `verbose` and `quiet` output switches
     * present in most shells. Using Shell::QUIET for a message means it will always display.
     * While using Shell::VERBOSE means it will only display when verbose output is toggled.
     *
     * @param string|array $message A string or an array of strings to output
     * @param int $newlines Number of newlines to append
     * @param int $level The message's output level, see above.
     * @return int|bool Returns the number of bytes returned from writing to stdout.
     */
    public function out($message = null, $newlines = 1, $level = ConsoleIo::NORMAL)
    {
        if ($level <= $this->_level) {
            $this->_lastWritten = $this->_out->write($message, $newlines);
            return $this->_lastWritten;
        }
        return true;
    }

    /**
     * Overwrite some already output text.
     *
     * Useful for building progress bars, or when you want to replace
     * text already output to the screen with new text.
     *
     * **Warning** You cannot overwrite text that contains newlines.
     *
     * @param array|string $message The message to output.
     * @param int $newlines Number of newlines to append.
     * @param int $size The number of bytes to overwrite. Defaults to the
     *    length of the last message output.
     * @return void
     */
    public function overwrite($message, $newlines = 1, $size = null)
    {
        $size = $size ?: $this->_lastWritten;

        // Output backspaces.
        $this->out(str_repeat("\x08", $size), 0);

        $newBytes = $this->out($message, 0);

        // Fill any remaining bytes with spaces.
        $fill = $size - $newBytes;
        if ($fill > 0) {
            $this->out(str_repeat(' ', $fill), 0);
        }
        if ($newlines) {
            $this->out($this->nl($newlines), 0);
        }
    }

    /**
     * Outputs a single or multiple error messages to stderr. If no parameters
     * are passed outputs just a newline.
     *
     * @param string|array $message A string or an array of strings to output
     * @param int $newlines Number of newlines to append
     * @return void
     */
    public function err($message = null, $newlines = 1)
    {
        $this->_err->write($message, $newlines);
    }

    /**
     * Returns a single or multiple linefeeds sequences.
     *
     * @param int $multiplier Number of times the linefeed sequence should be repeated
     * @return string
     */
    public function nl($multiplier = 1)
    {
        return str_repeat(ConsoleOutput::LF, $multiplier);
    }

    /**
     * Outputs a series of minus characters to the standard output, acts as a visual separator.
     *
     * @param int $newlines Number of newlines to pre- and append
     * @param int $width Width of the line, defaults to 79
     * @return void
     */
    public function hr($newlines = 0, $width = 79)
    {
        $this->out(null, $newlines);
        $this->out(str_repeat('-', $width));
        $this->out(null, $newlines);
    }

    /**
     * Prompts the user for input, and returns it.
     *
     * @param string $prompt Prompt text.
     * @param string|null $default Default input value.
     * @return mixed Either the default value, or the user-provided input.
     */
    public function ask($prompt, $default = null)
    {
        return $this->_getInput($prompt, null, $default);
    }

    /**
     * Change the output mode of the stdout stream
     *
     * @param int $mode The output mode.
     * @return void
     * @see \Cake\Console\ConsoleOutput::outputAs()
     */
    public function outputAs($mode)
    {
        $this->_out->outputAs($mode);
    }

    /**
     * Add a new output style or get defined styles.
     *
     * @param string $style The style to get or create.
     * @param array|bool|null $definition The array definition of the style to change or create a style
     *   or false to remove a style.
     * @return mixed If you are getting styles, the style or null will be returned. If you are creating/modifying
     *   styles true will be returned.
     * @see \Cake\Console\ConsoleOutput::styles()
     */
    public function styles($style = null, $definition = null)
    {
        $this->_out->styles($style, $definition);
    }

    /**
     * Prompts the user for input based on a list of options, and returns it.
     *
     * @param string $prompt Prompt text.
     * @param string|array $options Array or string of options.
     * @param string|null $default Default input value.
     * @return mixed Either the default value, or the user-provided input.
     */
    public function askChoice($prompt, $options, $default = null)
    {
        if ($options && is_string($options)) {
            if (strpos($options, ',')) {
                $options = explode(',', $options);
            } elseif (strpos($options, '/')) {
                $options = explode('/', $options);
            } else {
                $options = [$options];
            }
        }

        $printOptions = '(' . implode('/', $options) . ')';
        $options = array_merge(
            array_map('strtolower', $options),
            array_map('strtoupper', $options),
            $options
        );
        $in = '';
        while ($in === '' || !in_array($in, $options)) {
            $in = $this->_getInput($prompt, $printOptions, $default);
        }
        return $in;
    }

    /**
     * Prompts the user for input, and returns it.
     *
     * @param string $prompt Prompt text.
     * @param string|null $options String of options. Pass null to omit.
     * @param string|null $default Default input value. Pass null to omit.
     * @return string Either the default value, or the user-provided input.
     */
    protected function _getInput($prompt, $options, $default)
    {
        $optionsText = '';
        if (isset($options)) {
            $optionsText = " $options ";
        }

        $defaultText = '';
        if ($default !== null) {
            $defaultText = "[$default] ";
        }
        $this->_out->write('<question>' . $prompt . "</question>$optionsText\n$defaultText> ", 0);
        $result = $this->_in->read();

        $result = trim($result);
        if ($default !== null && ($result === '' || $result === null)) {
            return $default;
        }
        return $result;
    }

    /**
     * Connects or disconnects the loggers to the console output.
     *
     * Used to enable or disable logging stream output to stdout and stderr
     * If you don't wish all log output in stdout or stderr
     * through Cake's Log class, call this function with `$enable=false`.
     *
     * @param bool $enable Whether you want loggers on or off.
     * @return void
     */
    public function setLoggers($enable)
    {
        Log::drop('stdout');
        Log::drop('stderr');
        if (!$enable) {
            return;
        }
        $stdout = new ConsoleLog([
            'types' => ['notice', 'info', 'debug'],
            'stream' => $this->_out
        ]);
        Log::config('stdout', ['engine' => $stdout]);
        $stderr = new ConsoleLog([
            'types' => ['emergency', 'alert', 'critical', 'error', 'warning'],
            'stream' => $this->_err,
        ]);
        Log::config('stderr', ['engine' => $stderr]);
    }
}
