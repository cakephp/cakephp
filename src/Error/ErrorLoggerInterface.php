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
 * @since         4.1.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Error;

use Psr\Http\Message\ServerRequestInterface;
use Throwable;

/**
 * Interface for error logging handlers.
 *
 * Used by the ErrorHandlerMiddleware and global
 * error handlers to log exceptions and errors.
 *
 * @method void logException(\Throwable $exception, ?\Psr\Http\Message\ServerRequestInterface $request = null, bool $includeTrace = false)
 *   Log an exception with an optional HTTP request.
 * @method void logError(\Cake\Error\PhpError $error, ?\Psr\Http\Message\ServerRequestInterface $request = null, bool $includeTrace = false)
 *   Log an error with an optional HTTP request.
 */
interface ErrorLoggerInterface
{
    /**
     * Log an error for an exception with optional request context.
     *
     * @param \Throwable $exception The exception to log a message for.
     * @param \Psr\Http\Message\ServerRequestInterface|null $request The current request if available.
     * @return bool
     * @deprecated 4.4.0 Implement `logException` instead.
     */
    public function log(
        Throwable $exception,
        ?ServerRequestInterface $request = null
    ): bool;

    /**
     * Log a an error message to the error logger.
     *
     * @param string|int $level The logging level
     * @param string $message The message to be logged.
     * @param array $context Context.
     * @return bool
     * @deprecated 4.4.0 Implement `logError` instead.
     */
    public function logMessage($level, string $message, array $context = []): bool;
}
