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
 * @since         3.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Console;

use Cake\Console\Exception\StopException;
use Cake\Log\Engine\ConsoleLog;
use Cake\Log\Log;
use RuntimeException;
use SplFileObject;

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
     * The helper registry.
     *
     * @var \Cake\Console\HelperRegistry
     */
    protected $_helpers;

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
    protected $_level = self::NORMAL;

    /**
     * The number of bytes last written to the output stream
     * used when overwriting the previous message.
     *
     * @var int
     */
    protected $_lastWritten = 0;

    /**
     * Whether or not files should be overwritten
     *
     * @var bool
     */
    protected $forceOverwrite = false;

    /**
     * Constructor
     *
     * @param \Cake\Console\ConsoleOutput|null $out A ConsoleOutput object for stdout.
     * @param \Cake\Console\ConsoleOutput|null $err A ConsoleOutput object for stderr.
     * @param \Cake\Console\ConsoleInput|null $in A ConsoleInput object for stdin.
     * @param \Cake\Console\HelperRegistry|null $helpers A HelperRegistry instance
     */
    public function __construct(ConsoleOutput $out = null, ConsoleOutput $err = null, ConsoleInput $in = null, HelperRegistry $helpers = null)
    {
        $this->_out = $out ?: new ConsoleOutput('php://stdout');
        $this->_err = $err ?: new ConsoleOutput('php://stderr');
        $this->_in = $in ?: new ConsoleInput('php://stdin');
        $this->_helpers = $helpers ?: new HelperRegistry();
        $this->_helpers->setIo($this);
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
     * @return int|bool The number of bytes returned from writing to stdout.
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
     * @return int|bool The number of bytes returned from writing to stdout.
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
     * There are 3 built-in output level. ConsoleIo::QUIET, ConsoleIo::NORMAL, ConsoleIo::VERBOSE.
     * The verbose and quiet output levels, map to the `verbose` and `quiet` output switches
     * present in most shells. Using ConsoleIo::QUIET for a message means it will always display.
     * While using ConsoleIo::VERBOSE means it will only display when verbose output is toggled.
     *
     * @param string|array $message A string or an array of strings to output
     * @param int $newlines Number of newlines to append
     * @param int $level The message's output level, see above.
     * @return int|bool The number of bytes returned from writing to stdout.
     */
    public function out($message = '', $newlines = 1, $level = self::NORMAL)
    {
        if ($level <= $this->_level) {
            $this->_lastWritten = (int)$this->_out->write($message, $newlines);

            return $this->_lastWritten;
        }

        return true;
    }

    /**
     * Convenience method for out() that wraps message between <info /> tag
     *
     * @param string|array|null $message A string or an array of strings to output
     * @param int $newlines Number of newlines to append
     * @param int $level The message's output level, see above.
     * @return int|bool The number of bytes returned from writing to stdout.
     * @see https://book.cakephp.org/3.0/en/console-and-shells.html#ConsoleIo::out
     */
    public function info($message = null, $newlines = 1, $level = self::NORMAL)
    {
        $messageType = 'info';
        $message = $this->wrapMessageWithType($messageType, $message);

        return $this->out($message, $newlines, $level);
    }

    /**
     * Convenience method for err() that wraps message between <warning /> tag
     *
     * @param string|array|null $message A string or an array of strings to output
     * @param int $newlines Number of newlines to append
     * @return int|bool The number of bytes returned from writing to stderr.
     * @see https://book.cakephp.org/3.0/en/console-and-shells.html#ConsoleIo::err
     */
    public function warning($message = null, $newlines = 1)
    {
        $messageType = 'warning';
        $message = $this->wrapMessageWithType($messageType, $message);

        return $this->err($message, $newlines);
    }

    /**
     * Convenience method for err() that wraps message between <error /> tag
     *
     * @param string|array|null $message A string or an array of strings to output
     * @param int $newlines Number of newlines to append
     * @return int|bool The number of bytes returned from writing to stderr.
     * @see https://book.cakephp.org/3.0/en/console-and-shells.html#ConsoleIo::err
     */
    public function error($message = null, $newlines = 1)
    {
        $messageType = 'error';
        $message = $this->wrapMessageWithType($messageType, $message);

        return $this->err($message, $newlines);
    }

    /**
     * Convenience method for out() that wraps message between <success /> tag
     *
     * @param string|array|null $message A string or an array of strings to output
     * @param int $newlines Number of newlines to append
     * @param int $level The message's output level, see above.
     * @return int|bool The number of bytes returned from writing to stdout.
     * @see https://book.cakephp.org/3.0/en/console-and-shells.html#ConsoleIo::out
     */
    public function success($message = null, $newlines = 1, $level = self::NORMAL)
    {
        $messageType = 'success';
        $message = $this->wrapMessageWithType($messageType, $message);

        return $this->out($message, $newlines, $level);
    }

    /**
     * Wraps a message with a given message type, e.g. <warning>
     *
     * @param string $messageType The message type, e.g. "warning".
     * @param string|array $message The message to wrap.
     * @return array|string The message wrapped with the given message type.
     */
    protected function wrapMessageWithType($messageType, $message)
    {
        if (is_array($message)) {
            foreach ($message as $k => $v) {
                $message[$k] = "<{$messageType}>{$v}</{$messageType}>";
            }
        } else {
            $message = "<{$messageType}>{$message}</{$messageType}>";
        }

        return $message;
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
     * @param int|null $size The number of bytes to overwrite. Defaults to the
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

        // Store length of content + fill so if the new content
        // is shorter than the old content the next overwrite
        // will work.
        if ($fill > 0) {
            $this->_lastWritten = $newBytes + $fill;
        }
    }

    /**
     * Outputs a single or multiple error messages to stderr. If no parameters
     * are passed outputs just a newline.
     *
     * @param string|array $message A string or an array of strings to output
     * @param int $newlines Number of newlines to append
     * @return int|bool The number of bytes returned from writing to stderr.
     */
    public function err($message = '', $newlines = 1)
    {
        return $this->_err->write($message, $newlines);
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
     * @see \Cake\Console\ConsoleOutput::setOutputAs()
     */
    public function setOutputAs($mode)
    {
        $this->_out->setOutputAs($mode);
    }

    /**
     * Change the output mode of the stdout stream
     *
     * @deprecated 3.5.0 Use setOutputAs() instead.
     * @param int $mode The output mode.
     * @return void
     * @see \Cake\Console\ConsoleOutput::outputAs()
     */
    public function outputAs($mode)
    {
        deprecationWarning('ConsoleIo::outputAs() is deprecated. Use ConsoleIo::setOutputAs() instead.');
        $this->_out->setOutputAs($mode);
    }

    /**
     * Add a new output style or get defined styles.
     *
     * @param string|null $style The style to get or create.
     * @param array|bool|null $definition The array definition of the style to change or create a style
     *   or false to remove a style.
     * @return mixed If you are getting styles, the style or null will be returned. If you are creating/modifying
     *   styles true will be returned.
     * @see \Cake\Console\ConsoleOutput::styles()
     */
    public function styles($style = null, $definition = null)
    {
        return $this->_out->styles($style, $definition);
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
     * @param int|bool $enable Use a boolean to enable/toggle all logging. Use
     *   one of the verbosity constants (self::VERBOSE, self::QUIET, self::NORMAL)
     *   to control logging levels. VERBOSE enables debug logs, NORMAL does not include debug logs,
     *   QUIET disables notice, info and debug logs.
     * @return void
     */
    public function setLoggers($enable)
    {
        Log::drop('stdout');
        Log::drop('stderr');
        if ($enable === false) {
            return;
        }
        $outLevels = ['notice', 'info'];
        if ($enable === static::VERBOSE || $enable === true) {
            $outLevels[] = 'debug';
        }
        if ($enable !== static::QUIET) {
            $stdout = new ConsoleLog([
                'types' => $outLevels,
                'stream' => $this->_out
            ]);
            Log::setConfig('stdout', ['engine' => $stdout]);
        }
        $stderr = new ConsoleLog([
            'types' => ['emergency', 'alert', 'critical', 'error', 'warning'],
            'stream' => $this->_err,
        ]);
        Log::setConfig('stderr', ['engine' => $stderr]);
    }

    /**
     * Render a Console Helper
     *
     * Create and render the output for a helper object. If the helper
     * object has not already been loaded, it will be loaded and constructed.
     *
     * @param string $name The name of the helper to render
     * @param array $settings Configuration data for the helper.
     * @return \Cake\Console\Helper The created helper instance.
     */
    public function helper($name, array $settings = [])
    {
        $name = ucfirst($name);

        return $this->_helpers->load($name, $settings);
    }

    /**
     * Create a file at the given path.
     *
     * This method will prompt the user if a file will be overwritten.
     * Setting `forceOverwrite` to true will suppress this behavior
     * and always overwrite the file.
     *
     * If the user replies `a` subsequent `forceOverwrite` parameters will
     * be coerced to true and all files will be overwritten.
     *
     * @param string $path The path to create the file at.
     * @param string $contents The contents to put into the file.
     * @param bool $forceOverwrite Whether or not the file should be overwritten.
     *   If true, no question will be asked about whether or not to overwrite existing files.
     * @return bool Success.
     * @throws \Cake\Console\Exception\StopException When `q` is given as an answer
     *   to whether or not a file should be overwritten.
     */
    public function createFile($path, $contents, $forceOverwrite = false)
    {
        $this->out();
        $forceOverwrite = $forceOverwrite || $this->forceOverwrite;

        if (file_exists($path) && $forceOverwrite === false) {
            $this->warning("File `{$path}` exists");
            $key = $this->askChoice('Do you want to overwrite?', ['y', 'n', 'a', 'q'], 'n');
            $key = strtolower($key);

            if ($key === 'q') {
                $this->error('Quitting.', 2);
                throw new StopException('Not creating file. Quitting.');
            }
            if ($key === 'a') {
                $this->forceOverwrite = true;
                $key = 'y';
            }
            if ($key !== 'y') {
                $this->out("Skip `{$path}`", 2);

                return false;
            }
        } else {
            $this->out("Creating file {$path}");
        }

        try {
            // Create the directory using the current user permissions.
            $directory = dirname($path);
            if (!file_exists($directory)) {
                mkdir($directory, 0777 ^ umask(), true);
            }

            $file = new SplFileObject($path, 'w');
        } catch (RuntimeException $e) {
            $this->error("Could not write to `{$path}`. Permission denied.", 2);

            return false;
        }

        $file->rewind();
        if ($file->fwrite($contents) > 0) {
            $this->out("<success>Wrote</success> `{$path}`");

            return true;
        }
        $this->error("Could not write to `{$path}`.", 2);

        return false;
    }
}
