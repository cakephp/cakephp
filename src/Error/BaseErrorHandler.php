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
 * @since         2.0.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Error;

use Cake\Core\Configure;
use Cake\Error\Debugger;
use Cake\Log\Log;
use Cake\Routing\Router;

/**
 * Base error handler that provides logic common to the CLI + web
 * error/exception handlers.
 *
 * Subclasses are required to implement the template methods to handle displaying
 * the errors in their environment.
 */
abstract class BaseErrorHandler
{

    /**
     * Display an error message in an environment specific way.
     *
     * Subclasses should implement this method to display the error as
     * desired for the runtime they operate in.
     *
     * @param array $error An array of error data.
     * @param bool $debug Whether or not the app is in debug mode.
     * @return void
     */
    abstract protected function _displayError($error, $debug);

    /**
     * Display an exception in an environment specific way.
     *
     * Subclasses should implement this method to display an uncaught exception as
     * desired for the runtime they operate in.
     *
     * @param \Exception $exception The uncaught exception.
     * @return void
     */
    abstract protected function _displayException($exception);

    /**
     * Register the error and exception handlers.
     *
     * @return void
     */
    public function register()
    {
        $level = -1;
        if (isset($this->_options['errorLevel'])) {
            $level = $this->_options['errorLevel'];
        }
        error_reporting($level);
        set_error_handler([$this, 'handleError'], $level);
        set_exception_handler([$this, 'handleException']);
        register_shutdown_function(function () {
            if (php_sapi_name() === 'cli') {
                return;
            }
            $error = error_get_last();
            if (!is_array($error)) {
                return;
            }
            $fatals = [
                E_USER_ERROR,
                E_ERROR,
                E_PARSE,
            ];
            if (!in_array($error['type'], $fatals, true)) {
                return;
            }
            $this->handleFatalError(
                $error['type'],
                $error['message'],
                $error['file'],
                $error['line']
            );
        });
    }

    /**
     * Set as the default error handler by CakePHP.
     *
     * Use config/error.php to customize or replace this error handler.
     * This function will use Debugger to display errors when debug > 0. And
     * will log errors to Log, when debug == 0.
     *
     * You can use the 'errorLevel' option to set what type of errors will be handled.
     * Stack traces for errors can be enabled with the 'trace' option.
     *
     * @param int $code Code of error
     * @param string $description Error description
     * @param string|null $file File on which error occurred
     * @param int|null $line Line that triggered the error
     * @param array|null $context Context
     * @return bool True if error was handled
     */
    public function handleError($code, $description, $file = null, $line = null, $context = null)
    {
        if (error_reporting() === 0) {
            return false;
        }
        list($error, $log) = $this->mapErrorCode($code);
        if ($log === LOG_ERR) {
            return $this->handleFatalError($code, $description, $file, $line);
        }
        $data = [
            'level' => $log,
            'code' => $code,
            'error' => $error,
            'description' => $description,
            'file' => $file,
            'line' => $line,
        ];

        $debug = Configure::read('debug');
        if ($debug) {
            $data += [
                'context' => $context,
                'start' => 3,
                'path' => Debugger::trimPath($file)
            ];
        }
        $this->_displayError($data, $debug);
        $this->_logError($log, $data);
        return true;
    }

    /**
     * Handle uncaught exceptions.
     *
     * Uses a template method provided by subclasses to display errors in an
     * environment appropriate way.
     *
     * @param \Exception $exception Exception instance.
     * @return void
     * @throws \Exception When renderer class not found
     * @see http://php.net/manual/en/function.set-exception-handler.php
     */
    public function handleException(\Exception $exception)
    {
        $this->_displayException($exception);
        $this->_logException($exception);
        $this->_stop($exception->getCode() ?: 1);
    }

    /**
     * Stop the process.
     *
     * Implemented in subclasses that need it.
     *
     * @param int $code Exit code.
     * @return void
     */
    protected function _stop($code)
    {
        // Do nothing.
    }

    /**
     * Display/Log a fatal error.
     *
     * @param int $code Code of error
     * @param string $description Error description
     * @param string $file File on which error occurred
     * @param int $line Line that triggered the error
     * @return bool
     */
    public function handleFatalError($code, $description, $file, $line)
    {
        $data = [
            'code' => $code,
            'description' => $description,
            'file' => $file,
            'line' => $line,
            'error' => 'Fatal Error',
        ];
        $this->_logError(LOG_ERR, $data);

        $this->handleException(new FatalErrorException($description, 500, $file, $line));
        return true;
    }

    /**
     * Log an error.
     *
     * @param string $level The level name of the log.
     * @param array $data Array of error data.
     * @return void
     */
    protected function _logError($level, $data)
    {
        $message = sprintf(
            '%s (%s): %s in [%s, line %s]',
            $data['error'],
            $data['code'],
            $data['description'],
            $data['file'],
            $data['line']
        );
        if (!empty($this->_options['trace'])) {
            $trace = Debugger::trace([
                'start' => 1,
                'format' => 'log'
            ]);
            $message .= "\nTrace:\n" . $trace . "\n";
        }
        $message .= "\n\n";
        return Log::write($level, $message);
    }

    /**
     * Handles exception logging
     *
     * @param \Exception $exception Exception instance.
     * @return bool
     */
    protected function _logException(\Exception $exception)
    {
        $config = $this->_options;
        if (empty($config['log'])) {
            return false;
        }

        if (!empty($config['skipLog'])) {
            foreach ((array)$config['skipLog'] as $class) {
                if ($exception instanceof $class) {
                    return false;
                }
            }
        }
        return Log::error($this->_getMessage($exception));
    }

    /**
     * Generates a formatted error message
     *
     * @param \Exception $exception Exception instance
     * @return string Formatted message
     */
    protected function _getMessage(\Exception $exception)
    {
        $message = sprintf(
            "[%s] %s",
            get_class($exception),
            $exception->getMessage()
        );
        if (method_exists($exception, 'getAttributes')) {
            $attributes = $exception->getAttributes();
            if ($attributes) {
                $message .= "\nException Attributes: " . var_export($exception->getAttributes(), true);
            }
        }
        if (php_sapi_name() !== 'cli') {
            $request = Router::getRequest();
            if ($request) {
                $message .= "\nRequest URL: " . $request->here();
            }
        }
        $message .= "\nStack Trace:\n" . $exception->getTraceAsString() . "\n\n";
        return $message;
    }

    /**
     * Map an error code into an Error word, and log location.
     *
     * @param int $code Error code to map
     * @return array Array of error word, and log location.
     */
    public static function mapErrorCode($code)
    {
        $levelMap = [
            E_PARSE => 'error',
            E_ERROR => 'error',
            E_CORE_ERROR => 'error',
            E_COMPILE_ERROR => 'error',
            E_USER_ERROR => 'error',
            E_WARNING => 'warning',
            E_USER_WARNING => 'warning',
            E_COMPILE_WARNING => 'warning',
            E_RECOVERABLE_ERROR => 'warning',
            E_NOTICE => 'notice',
            E_USER_NOTICE => 'notice',
            E_STRICT => 'strict',
            E_DEPRECATED => 'deprecated',
            E_USER_DEPRECATED => 'deprecated',
        ];
        $logMap = [
            'error' => LOG_ERR,
            'warning' => LOG_WARNING,
            'notice' => LOG_NOTICE,
            'strict' => LOG_NOTICE,
            'deprecated' => LOG_NOTICE,
        ];

        $error = $levelMap[$code];
        $log = $logMap[$error];
        return [ucfirst($error), $log];
    }
}
