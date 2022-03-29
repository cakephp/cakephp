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
 * @since         4.4.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

use Cake\Log\Log;
use Psr\Log\LogLevel;

if (!function_exists('logError')) {
    /**
     * Logs an error message using sprintf() style arguments.
     *
     * If you need to pass a context such as scope, use `Log::error()`.
     *
     * @param string $format The message format string.
     * @param mixed ...$values The placeholder values.
     * @return void
     * @see https://www.php.net/manual/en/function.sprintf.php
     */
    function logError(string $format, ...$values): void
    {
        Log::write(LogLevel::ERROR, sprintf($format, ...$values));
    }
}

if (!function_exists('logWarning')) {
    /**
     * Logs an error message using sprintf() style arguments.
     *
     * If you need to pass a context such as scope, use `Log::error()`.
     *
     * @param string $format The message format string.
     * @param mixed ...$values The placeholder values.
     * @return void
     * @see https://www.php.net/manual/en/function.sprintf.php
     */
    function logWarning(string $format, ...$values): void
    {
        Log::write(LogLevel::WARNING, sprintf($format, ...$values));
    }
}

if (!function_exists('logInfo')) {
    /**
     * Logs an info message using sprintf() style arguments.
     *
     * If you need to pass a context such as scope, use `Log::error()`.
     *
     * @param string $format The message format string.
     * @param mixed ...$values The placeholder values.
     * @return void
     * @see https://www.php.net/manual/en/function.sprintf.php
     */
    function logInfo(string $format, ...$values): void
    {
        Log::write(LogLevel::INFO, sprintf($format, ...$values));
    }
}

if (!function_exists('logDebug')) {
    /**
     * Logs a debug message using sprintf() style arguments.
     *
     * If you need to pass a context such as scope, use `Log::debug()`.
     *
     * @param string $format The message format string.
     * @param mixed ...$values The placeholder values.
     * @return void
     * @see https://www.php.net/manual/en/function.sprintf.php
     */
    function logDebug(string $format, ...$values): void
    {
        Log::write(LogLevel::DEBUG, sprintf($format, ...$values));
    }
}
