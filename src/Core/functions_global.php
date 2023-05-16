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

use function Cake\Core\deprecationWarning as cakeDeprecationWarning;
use function Cake\Core\env as cakeEnv;
use function Cake\Core\getTypeName as cakeGetTypeName;
use function Cake\Core\h as cakeH;
use function Cake\Core\namespaceSplit as cakeNamespaceSplit;
use function Cake\Core\pj as cakePj;
use function Cake\Core\pluginSplit as cakePluginSplit;
use function Cake\Core\pr as cakePr;
use function Cake\Core\triggerWarning as cakeTriggerWarning;

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
        return cakeH($text, $double, $charset);
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
        return cakePluginSplit($name, $dotAppend, $plugin);
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
        return cakeNamespaceSplit($class);
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
        return cakePr($var);
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
        return cakePj($var);
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
        return cakeEnv($key, $default);
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
        cakeTriggerWarning($message);
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
        cakeDeprecationWarning($message, $stackFrame + 1);
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
        return cakeGetTypeName($var);
    }
}
