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
 * @since         3.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Core;

use JsonException;
use Stringable;

if (!defined('DS')) {
    /**
     * Defines DS as short form of DIRECTORY_SEPARATOR.
     */
    define('DS', DIRECTORY_SEPARATOR);
}

if (!defined('CAKE_DATE_RFC7231')) {
    define('CAKE_DATE_RFC7231', 'D, d M Y H:i:s \G\M\T');
}

if (!function_exists('Cake\Core\pathCombine')) {
    /**
     * Combines parts with a forward-slash `/`.
     *
     * Skips adding a forward-slash if either `/` or `\` already exists.
     *
     * @param array<string> $parts
     * @param bool|null $trailing Determines how trailing slashes are handled
     *  - If true, ensures a trailing forward-slash is added if one doesn't exist
     *  - If false, ensures any trailing slash is removed
     *  - if null, ignores trailing slashes
     * @return string
     */
    function pathCombine(array $parts, ?bool $trailing = null): string
    {
        $numParts = count($parts);
        if ($numParts === 0) {
            if ($trailing === true) {
                return '/';
            }

            return '';
        }

        $path = $parts[0];
        for ($i = 1; $i < $numParts; ++$i) {
            $part = $parts[$i];
            if ($part === '') {
                continue;
            }

            if ($path[-1] === '/' || $path[-1] === '\\') {
                if ($part[0] === '/' || $part[0] === '\\') {
                    $path .= substr($part, 1);
                } else {
                    $path .= $part;
                }
            } elseif ($part[0] === '/' || $part[0] === '\\') {
                $path .= $part;
            } else {
                $path .= '/' . $part;
            }
        }

        if ($trailing === true) {
            if ($path === '' || ($path[-1] !== '/' && $path[-1] !== '\\')) {
                $path .= '/';
            }
        } elseif ($trailing === false) {
            if ($path !== '' && ($path[-1] === '/' || $path[-1] === '\\')) {
                $path = substr($path, 0, -1);
            }
        }

        return $path;
    }
}

if (!function_exists('Cake\Core\h')) {
    /**
     * Convenience method for htmlspecialchars.
     *
     * @param mixed $text Text to wrap through htmlspecialchars. Also works with arrays, and objects.
     *    Arrays will be mapped and have all their elements escaped. Objects will be string cast if they
     *    implement a `__toString` method. Otherwise, the class name will be used.
     *    Other scalar types will be returned unchanged.
     * @param bool $double Encode existing html entities.
     * @param string|null $charset Character set to use when escaping.
     *   Defaults to config value in `mb_internal_encoding()` or 'UTF-8'.
     * @return mixed Wrapped text.
     * @link https://book.cakephp.org/5/en/core-libraries/global-constants-and-functions.html#h
     */
    function h(mixed $text, bool $double = true, ?string $charset = null): mixed
    {
        if (is_string($text)) {
            //optimize for strings
        } elseif (is_array($text)) {
            $texts = [];
            foreach ($text as $k => $t) {
                $texts[$k] = h($t, $double, $charset);
            }

            return $texts;
        } elseif (is_object($text)) {
            if ($text instanceof Stringable) {
                $text = (string)$text;
            } else {
                $text = '(object)' . $text::class;
            }
        } elseif ($text === null || is_scalar($text)) {
            return $text;
        }

        static $defaultCharset = false;
        if ($defaultCharset === false) {
            $defaultCharset = mb_internal_encoding() ?: 'UTF-8';
        }

        return htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, $charset ?: $defaultCharset, $double);
    }
}

if (!function_exists('Cake\Core\pluginSplit')) {
    /**
     * Splits a dot syntax plugin name into its plugin and class name.
     * If $name does not have a dot, then index 0 will be null.
     *
     * Commonly used like
     * ```
     * list($plugin, $name) = pluginSplit($name);
     * ```
     *
     * @param string $name The name you want to plugin split.
     * @param bool $dotAppend Set to true if you want the plugin to have a '.' appended to it.
     * @param string|null $plugin Optional default plugin to use if no plugin is found. Defaults to null.
     * @return array Array with 2 indexes. 0 => plugin name, 1 => class name.
     * @link https://book.cakephp.org/5/en/core-libraries/global-constants-and-functions.html#pluginSplit
     * @phpstan-return array{string|null, string}
     */
    function pluginSplit(string $name, bool $dotAppend = false, ?string $plugin = null): array
    {
        if (str_contains($name, '.')) {
            $parts = explode('.', $name, 2);
            if ($dotAppend) {
                $parts[0] .= '.';
            }

            /** @phpstan-var array{string, string} */
            return $parts;
        }

        return [$plugin, $name];
    }
}

if (!function_exists('Cake\Core\namespaceSplit')) {
    /**
     * Split the namespace from the classname.
     *
     * Commonly used like `list($namespace, $className) = namespaceSplit($class);`.
     *
     * @param string $class The full class name, ie `Cake\Core\App`.
     * @return array{0: string, 1: string} Array with 2 indexes. 0 => namespace, 1 => classname.
     */
    function namespaceSplit(string $class): array
    {
        $pos = strrpos($class, '\\');
        if ($pos === false) {
            return ['', $class];
        }

        return [substr($class, 0, $pos), substr($class, $pos + 1)];
    }
}

if (!function_exists('Cake\Core\pr')) {
    /**
     * print_r() convenience function.
     *
     * In terminals this will act similar to using print_r() directly, when not run on CLI
     * print_r() will also wrap `<pre>` tags around the output of given variable. Similar to debug().
     *
     * This function returns the same variable that was passed.
     *
     * @param mixed $var Variable to print out.
     * @return mixed the same $var that was passed to this function
     * @link https://book.cakephp.org/5/en/core-libraries/global-constants-and-functions.html#pr
     * @see debug()
     */
    function pr(mixed $var): mixed
    {
        if (!Configure::read('debug')) {
            return $var;
        }

        $template = PHP_SAPI !== 'cli' && PHP_SAPI !== 'phpdbg' ? '<pre class="pr">%s</pre>' : "\n%s\n\n";
        printf($template, trim(print_r($var, true)));

        return $var;
    }
}

if (!function_exists('Cake\Core\pj')) {
    /**
     * JSON pretty print convenience function.
     *
     * In terminals this will act similar to using json_encode() with JSON_PRETTY_PRINT directly, when not run on CLI
     * will also wrap `<pre>` tags around the output of given variable. Similar to pr().
     *
     * This function returns the same variable that was passed.
     *
     * @param mixed $var Variable to print out.
     * @return mixed the same $var that was passed to this function
     * @see pr()
     * @link https://book.cakephp.org/5/en/core-libraries/global-constants-and-functions.html#pj
     */
    function pj(mixed $var): mixed
    {
        if (!Configure::read('debug')) {
            return $var;
        }

        $template = PHP_SAPI !== 'cli' && PHP_SAPI !== 'phpdbg' ? '<pre class="pj">%s</pre>' : "\n%s\n\n";
        $flags = JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES;
        printf($template, trim((string)json_encode($var, $flags)));

        return $var;
    }
}

if (!function_exists('Cake\Core\env')) {
    /**
     * Gets an environment variable from available sources, and provides emulation
     * for unsupported or inconsistent environment variables (i.e. DOCUMENT_ROOT on
     * IIS, or SCRIPT_NAME in CGI mode). Also exposes some additional custom
     * environment information.
     *
     * @param string $key Environment variable name.
     * @param string|bool|null $default Specify a default value in case the environment variable is not defined.
     * @return string|float|int|bool|null Environment variable setting.
     * @link https://book.cakephp.org/5/en/core-libraries/global-constants-and-functions.html#env
     */
    function env(string $key, string|float|int|bool|null $default = null): string|float|int|bool|null
    {
        if ($key === 'HTTPS') {
            if (isset($_SERVER['HTTPS'])) {
                return !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
            }

            return str_starts_with((string)env('SCRIPT_URI'), 'https://');
        }

        if ($key === 'SCRIPT_NAME' && env('CGI_MODE') && isset($_ENV['SCRIPT_URL'])) {
            $key = 'SCRIPT_URL';
        }

        $val = $_SERVER[$key] ?? $_ENV[$key] ?? null;
        assert($val === null || is_scalar($val));
        if ($val == null && getenv($key) !== false) {
            $val = (string)getenv($key);
        }

        if ($key === 'REMOTE_ADDR' && $val === env('SERVER_ADDR')) {
            $addr = env('HTTP_PC_REMOTE_ADDR');
            if ($addr !== null) {
                $val = $addr;
            }
        }

        if ($val !== null) {
            return $val;
        }

        switch ($key) {
            case 'DOCUMENT_ROOT':
                $name = (string)env('SCRIPT_NAME');
                $filename = (string)env('SCRIPT_FILENAME');
                $offset = 0;
                if (!str_ends_with($name, '.php')) {
                    $offset = 4;
                }

                return substr($filename, 0, -(strlen($name) + $offset));
            case 'PHP_SELF':
                return str_replace((string)env('DOCUMENT_ROOT'), '', (string)env('SCRIPT_FILENAME'));
            case 'CGI_MODE':
                return PHP_SAPI === 'cgi';
        }

        return $default;
    }
}

if (!function_exists('Cake\Core\triggerWarning')) {
    /**
     * Triggers an E_USER_WARNING.
     *
     * @param string $message The warning message.
     * @return void
     */
    function triggerWarning(string $message): void
    {
        trigger_error($message, E_USER_WARNING);
    }
}

if (!function_exists('Cake\Core\deprecationWarning')) {
    /**
     * Helper method for outputting deprecation warnings
     *
     * @param string $version The version that added this deprecation warning.
     * @param string $message The message to output as a deprecation warning.
     * @param int $stackFrame The stack frame to include in the error. Defaults to 1
     *   as that should point to application/plugin code.
     * @return void
     */
    function deprecationWarning(string $version, string $message, int $stackFrame = 1): void
    {
        if (!(error_reporting() & E_USER_DEPRECATED)) {
            return;
        }

        $trace = debug_backtrace();
        if (isset($trace[$stackFrame])) {
            $frame = $trace[$stackFrame];
            $frame += ['file' => '[internal]', 'line' => '??'];

            // Assuming we're installed in vendor/cakephp/cakephp/src/Core/functions.php
            $root = dirname(__DIR__, 5);
            if (defined('ROOT')) {
                $root = ROOT;
            }
            $relative = str_replace(DIRECTORY_SEPARATOR, '/', substr($frame['file'], strlen($root) + 1));
            $patterns = (array)Configure::read('Error.ignoredDeprecationPaths');
            foreach ($patterns as $pattern) {
                $pattern = str_replace(DIRECTORY_SEPARATOR, '/', $pattern);
                if (fnmatch($pattern, $relative)) {
                    return;
                }
            }

            $message = sprintf(
                "Since %s: %s\n%s, line: %s\n" .
                'You can disable all deprecation warnings by setting `Error.errorLevel` to ' .
                '`E_ALL & ~E_USER_DEPRECATED`. Adding `%s` to `Error.ignoredDeprecationPaths` ' .
                'in your `config/app.php` config will mute deprecations from that file only.',
                $version,
                $message,
                $frame['file'],
                $frame['line'],
                $relative,
            );
        }

        static $errors = [];
        $checksum = hash('xxh128', $message);
        $duplicate = (bool)Configure::read('Error.allowDuplicateDeprecations', false);
        if (isset($errors[$checksum]) && !$duplicate) {
            return;
        }
        if (!$duplicate) {
            $errors[$checksum] = true;
        }

        trigger_error($message, E_USER_DEPRECATED);
    }
}

if (!function_exists('Cake\Core\toString')) {
    /**
     * Converts the given value to a string.
     *
     * This method attempts to convert the given value to a string.
     * If the value is already a string, it returns the value as it is.
     * ``null`` is returned if the conversion is not possible.
     *
     * @param mixed $value The value to be converted.
     * @return ?string Returns the string representation of the value, or null if the value is not a string.
     * @since 5.1.0
     */
    function toString(mixed $value): ?string
    {
        if (is_string($value)) {
            return $value;
        }
        if (is_int($value)) {
            return (string)$value;
        }
        if (is_bool($value)) {
            return $value ? '1' : '0';
        }
        if (is_float($value)) {
            if (is_nan($value) || is_infinite($value)) {
                return null;
            }
            try {
                $return = json_encode($value, JSON_THROW_ON_ERROR);
            } catch (JsonException) {
                $return = null;
            }

            if ($return === null || str_contains($return, 'e')) {
                return rtrim(sprintf('%.' . (PHP_FLOAT_DIG + 3) . 'F', $value), '.0');
            }

            return $return;
        }
        if ($value instanceof Stringable) {
            return (string)$value;
        }

        return null;
    }
}

if (!function_exists('Cake\Core\toInt')) {
    /**
     * Converts a value to an integer.
     *
     * This method attempts to convert the given value to an integer.
     * If the conversion is successful, it returns the value as an integer.
     * If the conversion fails, it returns NULL.
     *
     * String values are trimmed using trim().
     *
     * @param mixed $value The value to be converted to an integer.
     * @return int|null Returns the converted integer value or null if the conversion fails.
     * @since 5.1.0
     */
    function toInt(mixed $value): ?int
    {
        if (is_int($value)) {
            return $value;
        }
        if (is_string($value)) {
            $value = trim($value);
            if (preg_match('/^0+[^0]{1}/', $value)) {
                $value = ltrim($value, '0');
            }

            $value = filter_var($value, FILTER_VALIDATE_INT, FILTER_NULL_ON_FAILURE);

            return $value === PHP_INT_MIN ? null : $value;
        }
        if (is_float($value)) {
            if (is_nan($value) || is_infinite($value)) {
                return null;
            }

            return (int)$value;
        }
        if (is_bool($value)) {
            return (int)$value;
        }

        return null;
    }
}

if (!function_exists('Cake\Core\toFloat')) {
    /**
     * Converts a value to a float.
     *
     * This method attempts to convert the given value to a float.
     * If the conversion is successful, it returns the value as an float.
     * If the conversion fails, it returns NULL.
     *
     * String values are trimmed using trim().
     *
     * @param mixed $value The value to be converted to a float.
     * @return float|null Returns the converted float value or null if the conversion fails.
     * @since 5.1.0
     */
    function toFloat(mixed $value): ?float
    {
        if (is_string($value)) {
            $value = trim($value);
            if (preg_match('/^0+[^0]{1}/', $value)) {
                $value = ltrim($value, '0');
            }

            $value = filter_var($value, FILTER_VALIDATE_FLOAT, FILTER_NULL_ON_FAILURE);

            return $value === PHP_FLOAT_MIN ? null : $value;
        }
        if (is_float($value)) {
            if (is_nan($value) || is_infinite($value)) {
                return null;
            }

            return $value;
        }
        if (is_int($value)) {
            return (float)$value;
        }
        if (is_bool($value)) {
            return (float)$value;
        }

        return null;
    }
}

if (!function_exists('Cake\Core\toBool')) {
    /**
     * Converts a value to boolean.
     *
     *  1 | '1' | 1.0 | true  - values returns as true
     *  0 | '0' | 0.0 | false - values returns as false
     *  Other values returns as null.
     *
     * @param mixed $value The value to convert to boolean.
     * @return bool|null Returns true if the value is truthy, false if it's falsy, or NULL otherwise.
     * @since 5.1.0
     */
    function toBool(mixed $value): ?bool
    {
        if ($value === '1' || $value === 1 || $value === 1.0 || $value === true) {
            return true;
        }
        if ($value === '0' || $value === 0 || $value === 0.0 || $value === false) {
            return false;
        }

        return null;
    }
}
