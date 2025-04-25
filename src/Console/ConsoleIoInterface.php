<?php
declare(strict_types=1);

namespace Cake\Console;

/**
 * A wrapper around the various IO operations shell tasks need to do.
 *
 * Packages up the stdout, stderr, and stdin streams providing a simple
 * consistent interface for shells to use. This class also makes mocking streams
 * easy to do in unit tests.
 */
interface ConsoleIoInterface
{
    /**
     * Output constant making verbose shells.
     *
     * @var int
     */
    public const int VERBOSE = 2;

    /**
     * Output constant for making normal shells.
     *
     * @var int
     */
    public const int NORMAL = 1;

    /**
     * Output constants for making quiet shells.
     *
     * @var int
     */
    public const int QUIET = 0;

    /**
     * @param bool $value Value
     * @return void
     */
    public function setInteractive(bool $value): void;

    /**
     * Get/set the current output level.
     *
     * @param int|null $level The current output level.
     * @return int The current output level.
     */
    public function level(?int $level = null): int;

    /**
     * Output at the verbose level.
     *
     * @param array<string>|string $message A string or an array of strings to output
     * @param int $newlines Number of newlines to append
     * @return int|null The number of bytes returned from writing to stdout
     *   or null if current level is less than \Cake\Console\ConsoleIoInterface::VERBOSE
     */
    public function verbose(array|string $message, int $newlines = 1): ?int;

    /**
     * Output at all levels.
     *
     * @param array<string>|string $message A string or an array of strings to output
     * @param int $newlines Number of newlines to append
     * @return int|null The number of bytes returned from writing to stdout
     *   or null if current level is less than \Cake\Console\ConsoleIoInterface::QUIET
     */
    public function quiet(array|string $message, int $newlines = 1): ?int;

    /**
     * Outputs a single or multiple messages to stdout. If no parameters
     * are passed outputs just a newline.
     *
     * ### Output levels
     *
     * There are 3 built-in output level. \Cake\Console\ConsoleIoInterface::QUIET, \Cake\Console\ConsoleIoInterface::NORMAL, \Cake\Console\ConsoleIoInterface::VERBOSE.
     * The verbose and quiet output levels, map to the `verbose` and `quiet` output switches
     * present in most shells. Using \Cake\Console\ConsoleIoInterface::QUIET for a message means it will always display.
     * While using \Cake\Console\ConsoleIoInterface::VERBOSE means it will only display when verbose output is toggled.
     *
     * @param array<string>|string $message A string or an array of strings to output
     * @param int $newlines Number of newlines to append
     * @param int $level The message's output level, see above.
     * @return int|null The number of bytes returned from writing to stdout
     *   or null if provided $level is greater than current level.
     */
    public function out(array|string $message = '', int $newlines = 1, int $level = self::NORMAL): ?int;

    /**
     * Convenience method for out() that wraps message between <info> tag
     *
     * @param array<string>|string $message A string or an array of strings to output
     * @param int $newlines Number of newlines to append
     * @param int $level The message's output level, see above.
     * @return int|null The number of bytes returned from writing to stdout
     *   or null if provided $level is greater than current level.
     * @see https://book.cakephp.org/5/en/console-and-shells.html#\Cake\Console\ConsoleIoInterface::out
     */
    public function info(array|string $message, int $newlines = 1, int $level = self::NORMAL): ?int;

    /**
     * Convenience method for out() that wraps message between <comment> tag
     *
     * @param array<string>|string $message A string or an array of strings to output
     * @param int $newlines Number of newlines to append
     * @param int $level The message's output level, see above.
     * @return int|null The number of bytes returned from writing to stdout
     *   or null if provided $level is greater than current level.
     * @see https://book.cakephp.org/5/en/console-and-shells.html#\Cake\Console\ConsoleIoInterface::out
     */
    public function comment(array|string $message, int $newlines = 1, int $level = self::NORMAL): ?int;

    /**
     * Convenience method for err() that wraps message between <warning> tag
     *
     * @param array<string>|string $message A string or an array of strings to output
     * @param int $newlines Number of newlines to append
     * @return int The number of bytes returned from writing to stderr.
     * @see https://book.cakephp.org/5/en/console-and-shells.html#\Cake\Console\ConsoleIoInterface::err
     */
    public function warning(array|string $message, int $newlines = 1): int;

    /**
     * Convenience method for err() that wraps message between <error> tag
     *
     * @param array<string>|string $message A string or an array of strings to output
     * @param int $newlines Number of newlines to append
     * @return int The number of bytes returned from writing to stderr.
     * @see https://book.cakephp.org/5/en/console-and-shells.html#\Cake\Console\ConsoleIoInterface::err
     */
    public function error(array|string $message, int $newlines = 1): int;

    /**
     * Convenience method for out() that wraps message between <success> tag
     *
     * @param array<string>|string $message A string or an array of strings to output
     * @param int $newlines Number of newlines to append
     * @param int $level The message's output level, see above.
     * @return int|null The number of bytes returned from writing to stdout
     *   or null if provided $level is greater than current level.
     * @see https://book.cakephp.org/5/en/console-and-shells.html#\Cake\Console\ConsoleIoInterface::out
     */
    public function success(array|string $message, int $newlines = 1, int $level = self::NORMAL): ?int;

    /**
     * Halts the the current process with a StopException.
     *
     * @param string $message Error message.
     * @param int $code Error code.
     * @return never
     * @throws \Cake\Console\Exception\StopException
     */
    public function abort(string $message, int $code = CommandInterface::CODE_ERROR): never;

    /**
     * Overwrite some already output text.
     *
     * Useful for building progress bars, or when you want to replace
     * text already output to the screen with new text.
     *
     * **Warning** You cannot overwrite text that contains newlines.
     *
     * @param array<string>|string $message The message to output.
     * @param int $newlines Number of newlines to append.
     * @param int|null $size The number of bytes to overwrite. Defaults to the
     *    length of the last message output.
     * @return void
     */
    public function overwrite(array|string $message, int $newlines = 1, ?int $size = null): void;

    /**
     * Outputs a single or multiple error messages to stderr. If no parameters
     * are passed outputs just a newline.
     *
     * @param array<string>|string $message A string or an array of strings to output
     * @param int $newlines Number of newlines to append
     * @return int The number of bytes returned from writing to stderr.
     */
    public function err(array|string $message = '', int $newlines = 1): int;

    /**
     * Returns a single or multiple linefeeds sequences.
     *
     * @param int $multiplier Number of times the linefeed sequence should be repeated
     * @return string
     */
    public function nl(int $multiplier = 1): string;

    /**
     * Outputs a series of minus characters to the standard output, acts as a visual separator.
     *
     * @param int $newlines Number of newlines to pre- and append
     * @param int $width Width of the line, defaults to 79
     * @return void
     */
    public function hr(int $newlines = 0, int $width = 79): void;

    /**
     * Prompts the user for input, and returns it.
     *
     * @param string $prompt Prompt text.
     * @param string|null $default Default input value.
     * @return string Either the default value, or the user-provided input.
     */
    public function ask(string $prompt, ?string $default = null): string;

    /**
     * Change the output mode of the stdout stream
     *
     * @param int $mode The output mode.
     * @return void
     * @see \Cake\Console\ConsoleOutput::setOutputAs()
     */
    public function setOutputAs(int $mode): void;

    /**
     * Gets defined styles.
     *
     * @return array
     * @see \Cake\Console\ConsoleOutput::styles()
     */
    public function styles(): array;

    /**
     * Get defined style.
     *
     * @param string $style The style to get.
     * @return array
     * @see \Cake\Console\ConsoleOutput::getStyle()
     */
    public function getStyle(string $style): array;

    /**
     * Adds a new output style.
     *
     * @param string $style The style to set.
     * @param array $definition The array definition of the style to change or create.
     * @return void
     * @see \Cake\Console\ConsoleOutput::setStyle()
     */
    public function setStyle(string $style, array $definition): void;

    /**
     * Prompts the user for input based on a list of options, and returns it.
     *
     * @param string $prompt Prompt text.
     * @param array<string>|string $options Array or string of options.
     * @param string|null $default Default input value.
     * @return string Either the default value, or the user-provided input.
     */
    public function askChoice(string $prompt, array|string $options, ?string $default = null): string;

    /**
     * Connects or disconnects the loggers to the console output.
     *
     * Used to enable or disable logging stream output to stdout and stderr
     * If you don't wish all log output in stdout or stderr
     * through Cake's Log class, call this function with `$enable=false`.
     *
     * If you would like to take full control of how console application logging
     * to stdout works add a logger that uses `'className' => 'Console'`. By
     * providing a console logger you replace the framework default behavior.
     *
     * @param int|bool $enable Use a boolean to enable/toggle all logging. Use
     *   one of the verbosity constants (self::VERBOSE, self::QUIET, self::NORMAL)
     *   to control logging levels. VERBOSE enables debug logs, NORMAL does not include debug logs,
     *   QUIET disables notice, info and debug logs.
     * @return void
     */
    public function setLoggers(int|bool $enable): void;

    /**
     * Render a Console Helper
     *
     * Create and render the output for a helper object. If the helper
     * object has not already been loaded, it will be loaded and constructed.
     *
     * @param string $name The name of the helper to render
     * @param array<string, mixed> $config Configuration data for the helper.
     * @return \Cake\Console\Helper The created helper instance.
     */
    public function helper(string $name, array $config = []): Helper;

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
     * @param bool $forceOverwrite Whether the file should be overwritten.
     *   If true, no question will be asked about whether to overwrite existing files.
     * @return bool Success.
     * @throws \Cake\Console\Exception\StopException When `q` is given as an answer
     *   to whether a file should be overwritten.
     */
    public function createFile(string $path, string $contents, bool $forceOverwrite = false): bool;
}
