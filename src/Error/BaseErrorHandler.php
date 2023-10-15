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

use Cake\Core\Configure;
use Cake\Core\InstanceConfigTrait;
use Cake\Routing\Router;
use Psr\Http\Message\ServerRequestInterface;
use RuntimeException;
use Throwable;
use function Cake\Core\deprecationWarning;
use function Cake\Core\getTypeName;

/**
 * Base error handler that provides logic common to the CLI + web
 * error/exception handlers.
 *
 * Subclasses are required to implement the template methods to handle displaying
 * the errors in their environment.
 */
abstract class BaseErrorHandler
{
    use InstanceConfigTrait;

    /**
     * Options to use for the Error handling.
     *
     * @var array<string, mixed>
     */
    protected $_defaultConfig = [
        'log' => true,
        'trace' => false,
        'skipLog' => [],
        'errorLogger' => ErrorLogger::class,
    ];

    /**
     * @var bool
     */
    protected $_handled = false;

    /**
     * Exception logger instance.
     *
     * @var \Cake\Error\ErrorLoggerInterface|null
     */
    protected $logger;

    /**
     * Display an error message in an environment specific way.
     *
     * Subclasses should implement this method to display the error as
     * desired for the runtime they operate in.
     *
     * @param array $error An array of error data.
     * @param bool $debug Whether the app is in debug mode.
     * @return void
     */
    abstract protected function _displayError(array $error, bool $debug): void;

    /**
     * Display an exception in an environment specific way.
     *
     * Subclasses should implement this method to display an uncaught exception as
     * desired for the runtime they operate in.
     *
     * @param \Throwable $exception The uncaught exception.
     * @return void
     */
    abstract protected function _displayException(Throwable $exception): void;

    /**
     * Register the error and exception handlers.
     *
     * @return void
     */
    public function register(): void
    {
        deprecationWarning(
            'Use of `BaseErrorHandler` and subclasses are deprecated. ' .
            'Upgrade to the new `ErrorTrap` and `ExceptionTrap` subsystem. ' .
            'See https://book.cakephp.org/4/en/appendices/4-4-migration-guide.html'
        );

        $level = $this->_config['errorLevel'] ?? -1;
        error_reporting($level);
        set_error_handler([$this, 'handleError'], $level);
        set_exception_handler([$this, 'handleException']);
        register_shutdown_function(function (): void {
            if ((PHP_SAPI === 'cli' || PHP_SAPI === 'phpdbg') && $this->_handled) {
                return;
            }
            $megabytes = $this->_config['extraFatalErrorMemory'] ?? 4;
            if ($megabytes > 0) {
                $this->increaseMemoryLimit($megabytes * 1024);
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
     * This function will use Debugger to display errors when debug mode is on. And
     * will log errors to Log, when debug mode is off.
     *
     * You can use the 'errorLevel' option to set what type of errors will be handled.
     * Stack traces for errors can be enabled with the 'trace' option.
     *
     * @param int $code Code of error
     * @param string $description Error description
     * @param string|null $file File on which error occurred
     * @param int|null $line Line that triggered the error
     * @param array<string, mixed>|null $context Context
     * @return bool True if error was handled
     */
    public function handleError(
        int $code,
        string $description,
        ?string $file = null,
        ?int $line = null,
        ?array $context = null
    ): bool {
        if (!(error_reporting() & $code)) {
            return false;
        }
        $this->_handled = true;
        [$error, $log] = static::mapErrorCode($code);
        if ($log === LOG_ERR) {
            /** @psalm-suppress PossiblyNullArgument */
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

        $debug = (bool)Configure::read('debug');
        if ($debug) {
            // By default trim 3 frames off for the public and protected methods
            // used by ErrorHandler instances.
            $start = 3;

            // Can be used by error handlers that wrap other error handlers
            // to coerce the generated stack trace to the correct point.
            if (isset($context['_trace_frame_offset'])) {
                $start += $context['_trace_frame_offset'];
                unset($context['_trace_frame_offset']);
            }
            $data += [
                'context' => $context,
                'start' => $start,
                'path' => Debugger::trimPath((string)$file),
            ];
        }
        $this->_displayError($data, $debug);
        $this->_logError($log, $data);

        return true;
    }

    /**
     * Checks the passed exception type. If it is an instance of `Error`
     * then, it wraps the passed object inside another Exception object
     * for backwards compatibility purposes.
     *
     * @param \Throwable $exception The exception to handle
     * @return void
     * @deprecated 4.0.0 Unused method will be removed in 5.0
     */
    public function wrapAndHandleException(Throwable $exception): void
    {
        deprecationWarning('This method is no longer in use. Call handleException instead.');
        $this->handleException($exception);
    }

    /**
     * Handle uncaught exceptions.
     *
     * Uses a template method provided by subclasses to display errors in an
     * environment appropriate way.
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
        $code = $exception->getCode() ?: 1;
        $this->_stop((int)$code);
    }

    /**
     * Stop the process.
     *
     * Implemented in subclasses that need it.
     *
     * @param int $code Exit code.
     * @return void
     */
    protected function _stop(int $code): void
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
    public function handleFatalError(int $code, string $description, string $file, int $line): bool
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
     * Increases the PHP "memory_limit" ini setting by the specified amount
     * in kilobytes
     *
     * @param int $additionalKb Number in kilobytes
     * @return void
     */
    public function increaseMemoryLimit(int $additionalKb): void
    {
        $limit = ini_get('memory_limit');
        if ($limit === false || $limit === '' || $limit === '-1') {
            return;
        }
        $limit = trim($limit);
        $units = strtoupper(substr($limit, -1));
        $current = (int)substr($limit, 0, strlen($limit) - 1);
        if ($units === 'M') {
            $current *= 1024;
            $units = 'K';
        }
        if ($units === 'G') {
            $current = $current * 1024 * 1024;
            $units = 'K';
        }

        if ($units === 'K') {
            ini_set('memory_limit', ceil($current + $additionalKb) . 'K');
        }
    }

    /**
     * Log an error.
     *
     * @param string|int $level The level name of the log.
     * @param array $data Array of error data.
     * @return bool
     */
    protected function _logError($level, array $data): bool
    {
        $message = sprintf(
            '%s (%s): %s in [%s, line %s]',
            $data['error'],
            $data['code'],
            $data['description'],
            $data['file'],
            $data['line']
        );
        $context = [];
        if (!empty($this->_config['trace'])) {
            $context['trace'] = Debugger::trace([
                'start' => 1,
                'format' => 'log',
            ]);
            $context['request'] = Router::getRequest();
        }

        return $this->getLogger()->logMessage($level, $message, $context);
    }

    /**
     * Log an error for the exception if applicable.
     *
     * @param \Throwable $exception The exception to log a message for.
     * @param \Psr\Http\Message\ServerRequestInterface|null $request The current request.
     * @return bool
     */
    public function logException(Throwable $exception, ?ServerRequestInterface $request = null): bool
    {
        if (empty($this->_config['log'])) {
            return false;
        }
        foreach ($this->_config['skipLog'] as $class) {
            if ($exception instanceof $class) {
                return false;
            }
        }

        return $this->getLogger()->log($exception, $request ?? Router::getRequest());
    }

    /**
     * Get exception logger.
     *
     * @return \Cake\Error\ErrorLoggerInterface
     */
    public function getLogger()
    {
        if ($this->logger === null) {
            /** @var \Cake\Error\ErrorLoggerInterface $logger */
            $logger = new $this->_config['errorLogger']($this->_config);

            if (!$logger instanceof ErrorLoggerInterface) {
                // Set the logger so that the next error can be logged.
                $this->logger = new ErrorLogger($this->_config);

                $interface = ErrorLoggerInterface::class;
                $type = getTypeName($logger);
                throw new RuntimeException("Cannot create logger. `{$type}` does not implement `{$interface}`.");
            }
            $this->logger = $logger;
        }

        return $this->logger;
    }

    /**
     * Map an error code into an Error word, and log location.
     *
     * @param int $code Error code to map
     * @return array Array of error word, and log location.
     */
    public static function mapErrorCode(int $code): array
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
