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

use Cake\Core\Configure;

if (!defined('DS')) {
    /**
     * Defines DS as short form of DIRECTORY_SEPARATOR.
     */
    define('DS', DIRECTORY_SEPARATOR);
}

if (!function_exists('h')) {
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
     * @link https://book.cakephp.org/4/en/core-libraries/global-constants-and-functions.html#h
     */
    function h($text, bool $double = true, ?string $charset = null)
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
            if (method_exists($text, '__toString')) {
                $text = $text->__toString();
            } else {
                $text = '(object)' . get_class($text);
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

if (!function_exists('pluginSplit')) {
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
     * @link https://book.cakephp.org/4/en/core-libraries/global-constants-and-functions.html#pluginSplit
     * @psalm-return array{string|null, string}
     */
    function pluginSplit(string $name, bool $dotAppend = false, ?string $plugin = null): array
    {
        if (strpos($name, '.') !== false) {
            $parts = explode('.', $name, 2);
            if ($dotAppend) {
                $parts[0] .= '.';
            }

            /** @psalm-var array{string, string}*/
            return $parts;
        }

        return [$plugin, $name];
    }

}

if (!function_exists('namespaceSplit')) {
    /**
     * Split the namespace from the classname.
     *
     * Commonly used like `list($namespace, $className) = namespaceSplit($class);`.
     *
     * @param string $class The full class name, ie `Cake\Core\App`.
     * @return array<string> Array with 2 indexes. 0 => namespace, 1 => classname.
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

if (!function_exists('pr')) {
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
     * @link https://book.cakephp.org/4/en/core-libraries/global-constants-and-functions.html#pr
     * @see debug()
     */
    function pr($var)
    {
        if (!Configure::read('debug')) {
            return $var;
        }

        $template = PHP_SAPI !== 'cli' && PHP_SAPI !== 'phpdbg' ? '<pre class="pr">%s</pre>' : "\n%s\n\n";
        printf($template, trim(print_r($var, true)));

        return $var;
    }

}

if (!function_exists('pj')) {
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
     * @link https://book.cakephp.org/4/en/core-libraries/global-constants-and-functions.html#pj
     */
    function pj($var)
    {
        if (!Configure::read('debug')) {
            return $var;
        }

        $template = PHP_SAPI !== 'cli' && PHP_SAPI !== 'phpdbg' ? '<pre class="pj">%s</pre>' : "\n%s\n\n";
        printf($template, trim(json_encode($var, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)));

        return $var;
    }

}

if (!function_exists('env')) {
    /**
     * Gets an environment variable from available sources, and provides emulation
     * for unsupported or inconsistent environment variables (i.e. DOCUMENT_ROOT on
     * IIS, or SCRIPT_NAME in CGI mode). Also exposes some additional custom
     * environment information.
     *
     * @param string $key Environment variable name.
     * @param string|bool|null $default Specify a default value in case the environment variable is not defined.
     * @return string|bool|null Environment variable setting.
     * @link https://book.cakephp.org/4/en/core-libraries/global-constants-and-functions.html#env
     */
    function env(string $key, $default = null)
    {
        if ($key === 'HTTPS') {
            if (isset($_SERVER['HTTPS'])) {
                return !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
            }

            return strpos((string)env('SCRIPT_URI'), 'https://') === 0;
        }

        if ($key === 'SCRIPT_NAME' && env('CGI_MODE') && isset($_ENV['SCRIPT_URL'])) {
            $key = 'SCRIPT_URL';
        }

        /** @var string|null $val */
        $val = $_SERVER[$key] ?? $_ENV[$key] ?? null;
        if ($val == null && getenv($key) !== false) {
            /** @var string|false $val */
            $val = getenv($key);
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
                if (!strpos($name, '.php')) {
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

if (!function_exists('triggerWarning')) {
    /**
     * Triggers an E_USER_WARNING.
     *
     * @param string $message The warning message.
     * @return void
     */
    function triggerWarning(string $message): void
    {
        $trace = debug_backtrace();
        if (isset($trace[1])) {
            $frame = $trace[1];
            $frame += ['file' => '[internal]', 'line' => '??'];
            $message = sprintf(
                '%s - %s, line: %s',
                $message,
                $frame['file'],
                $frame['line']
            );
        }
        trigger_error($message, E_USER_WARNING);
    }
}

if (!function_exists('deprecationWarning')) {
    /**
     * Helper method for outputting deprecation warnings
     *
     * @param string $message The message to output as a deprecation warning.
     * @param int $stackFrame The stack frame to include in the error. Defaults to 1
     *   as that should point to application/plugin code.
     * @return void
     */
    function deprecationWarning(string $message, int $stackFrame = 1): void
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
                "%s\n%s, line: %s\n" .
                'You can disable all deprecation warnings by setting `Error.errorLevel` to ' .
                '`E_ALL & ~E_USER_DEPRECATED`. Adding `%s` to `Error.ignoredDeprecationPaths` ' .
                'in your `config/app.php` config will mute deprecations from that file only.',
                $message,
                $frame['file'],
                $frame['line'],
                $relative
            );
        }

        static $errors = [];
        $checksum = md5($message);
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

if (!function_exists('getTypeName')) {
    /**
     * Returns the objects class or var type of it's not an object
     *
     * @param mixed $var Variable to check
     * @return string Returns the class name or variable type
     */
    function getTypeName($var): string
    {
        return is_object($var) ? get_class($var) : gettype($var);
    }
}
