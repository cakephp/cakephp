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
 * Used by the ErrorHandlerMiddleware and global error handlers to log exceptions and errors.
 */
interface ErrorLoggerInterface
{
    /**
     * Log an error for an exception with optional request context.
     *
     * @param \Throwable $exception The exception to log a message for.
     * @param \Psr\Http\Message\ServerRequestInterface|null $request The current request if available.
     * @param bool $includeTrace Should the log message include a stacktrace.
     * @return void
     */
    public function logException(
        Throwable $exception,
        ?ServerRequestInterface $request = null,
        bool $includeTrace = false,
    ): void;

    /**
     * Log an error to Cake's Log subsystem
     *
     * @param \Cake\Error\PhpError $error The error to log.
     * @param \Psr\Http\Message\ServerRequestInterface|null $request The request if in an HTTP context.
     * @param bool $includeTrace Should the log message include a stacktrace.
     * @return void
     */
    public function logError(
        PhpError $error,
        ?ServerRequestInterface $request = null,
        bool $includeTrace = false,
    ): void;
}
