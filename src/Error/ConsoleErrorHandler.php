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
namespace Cake\Error;

use Cake\Command\Command;
use Cake\Console\ConsoleOutput;
use Cake\Console\Exception\ConsoleException;
use Throwable;

/**
 * Error Handler for Cake console. Does simple printing of the
 * exception that occurred and the stack trace of the error.
 */
class ConsoleErrorHandler extends BaseErrorHandler
{
    /**
     * Standard error stream.
     *
     * @var \Cake\Console\ConsoleOutput
     */
    protected $_stderr;

    /**
     * Constructor
     *
     * @param array $config Config options for the error handler.
     */
    public function __construct(array $config = [])
    {
        $config += [
            'stderr' => new ConsoleOutput('php://stderr'),
            'log' => false,
        ];

        $this->setConfig($config);
        $this->_stderr = $this->_config['stderr'];
    }

    /**
     * Handle errors in the console environment. Writes errors to stderr,
     * and logs messages if Configure::read('debug') is false.
     *
     * @param \Throwable $exception Exception instance.
     * @return void
     * @throws \Exception When renderer class not found
     * @see https://secure.php.net/manual/en/function.set-exception-handler.php
     */
    public function handleException(Throwable $exception): void
    {
        $this->_displayException($exception);
        $this->logException($exception);

        $exitCode = Command::CODE_ERROR;
        if ($exception instanceof ConsoleException) {
            $exitCode = $exception->getCode();
        }
        $this->_stop($exitCode);
    }

    /**
     * Prints an exception to stderr.
     *
     * @param \Throwable $exception The exception to handle
     * @return void
     */
    protected function _displayException(Throwable $exception): void
    {
        $errorName = 'Exception:';
        if ($exception instanceof FatalErrorException) {
            $errorName = 'Fatal Error:';
        }

        $message = sprintf(
            "<error>%s</error> %s\nIn [%s, line %s]\n",
            $errorName,
            $exception->getMessage(),
            $exception->getFile(),
            $exception->getLine()
        );
        $this->_stderr->write($message);
    }

    /**
     * Prints an error to stderr.
     *
     * Template method of BaseErrorHandler.
     *
     * @param array $error An array of error data.
     * @param bool $debug Whether or not the app is in debug mode.
     * @return void
     */
    protected function _displayError(array $error, bool $debug): void
    {
        $message = sprintf(
            "%s\nIn [%s, line %s]",
            $error['description'],
            $error['file'],
            $error['line']
        );
        $message = sprintf(
            "<error>%s Error:</error> %s\n",
            $error['error'],
            $message
        );
        $this->_stderr->write($message);
    }

    /**
     * Stop the execution and set the exit code for the process.
     *
     * @param int $code The exit code.
     * @return void
     */
    protected function _stop(int $code): void
    {
        exit($code);
    }
}
