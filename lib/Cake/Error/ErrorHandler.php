<?php
/**
 * PHP 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         CakePHP(tm) v 0.10.5.1732
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Error;

use Cake\Core\App;
use Cake\Core\Configure;
use Cake\Log\Log;
use Cake\Routing\Router;
use Cake\Utility\Debugger;

/**
 *
 * Error Handler provides basic error and exception handling for your application. It captures and
 * handles all unhandled exceptions and errors. Displays helpful framework errors when debug > 1.
 *
 * ### Uncaught exceptions
 *
 * When debug < 1 a CakeException will render 404 or 500 errors. If an uncaught exception is thrown
 * and it is a type that ErrorHandler does not know about it will be treated as a 500 error.
 *
 * ### Implementing application specific exception handling
 *
 * You can implement application specific exception handling in one of a few ways. Each approach
 * gives you different amounts of control over the exception handling process.
 *
 * - Modify App/Config/error.php and setup custom exception handling.
 * - Use the `exceptionRenderer` option to inject an Exception renderer. This will
 *   let you keep the existing handling logic but override the rendering logic.
 *
 * #### Create your own Exception handler
 *
 * This gives you full control over the exception handling process. The class you choose should be
 * loaded in your app/Config/error.php and registered as the default exception handler.
 *
 * #### Using a custom renderer with `exceptionRenderer`
 *
 * If you don't want to take control of the exception handling, but want to change how exceptions are
 * rendered you can use `exceptionRenderer` option to choose a class to render exception pages. By default
 * `Cake\Error\ExceptionRenderer` is used. Your custom exception renderer class should be placed in app/Error.
 *
 * Your custom renderer should expect an exception in its constructor, and implement a render method.
 * Failing to do so will cause additional errors.
 *
 * #### Logging exceptions
 *
 * Using the built-in exception handling, you can log all the exceptions
 * that are dealt with by ErrorHandler by setting `log` option to true in your App/Config/error.php.
 * Enabling this will log every exception to Log and the configured loggers.
 *
 * ### PHP errors
 *
 * Error handler also provides the built in features for handling php errors (trigger_error).
 * While in debug mode, errors will be output to the screen using debugger. While in production mode,
 * errors will be logged to Log.  You can control which errors are logged by setting
 * `errorLevel` option in App/Config/error.php.
 *
 * #### Logging errors
 *
 * When ErrorHandler is used for handling errors, you can enable error logging by setting the `log`
 * option to true. This will log all errors to the configured log handlers.
 *
 * #### Controlling what errors are logged/displayed
 *
 * You can control which errors are logged / displayed by ErrorHandler by setting `errorLevel`. Setting this
 * to one or a combination of a few of the E_* constants will only enable the specified errors.
 *
 * $options['log'] = E_ALL & ~E_NOTICE;
 *
 * Would enable handling for all non Notice errors.
 *
 * @see ExceptionRenderer for more information on how to customize exception rendering.
 */
class ErrorHandler {

/**
 * Options to use for the Error handling.
 *
 * @var array
 */
	protected $_options = [];

/**
 * Constructor
 *
 * @param array $options The options for error handling.
 */
	public function __construct($options = []) {
		$defaults = [
			'log' => true,
			'trace' => false,
			'exceptionRenderer' => 'Cake\Error\ExceptionRenderer',
		];
		$this->_options = array_merge($defaults, $options);
	}

/**
 * Register the error and exception handlers.
 *
 * @return void
 */
	public function register() {
		$level = -1;
		if (isset($this->_options['errorLevel'])) {
			$level = $this->_options['errorLevel'];
		}
		error_reporting($level);
		set_error_handler([$this, 'handleError'], $level);
		set_exception_handler([$this, 'handleException']);
	}

/**
 * Set as the default exception handler by the CakePHP bootstrap process.
 *
 * This will either use custom exception renderer class if configured,
 * or use the default ExceptionRenderer.
 *
 * @param \Exception $exception
 * @return void
 * @throws Exception When renderer class not found
 * @see http://php.net/manual/en/function.set-exception-handler.php
 */
	public function handleException(\Exception $exception) {
		$config = $this->_options;
		self::_log($exception, $config);

		$renderer = isset($config['exceptionRenderer']) ? $config['exceptionRenderer'] : 'Cake\Error\ExceptionRenderer';
		$renderer = App::classname($renderer, 'Error');
		try {
			if (!$renderer) {
				throw new \Exception("$renderer is an invalid class.");
			}
			$error = new $renderer($exception);
			$error->render();
		} catch (\Exception $e) {
			// Disable trace for internal errors.
			$this->_options['trace'] = false;
			$message = sprintf("[%s] %s\n%s", // Keeping same message format
				get_class($e),
				$e->getMessage(),
				$e->getTraceAsString()
			);
			trigger_error($message, E_USER_ERROR);
		}
	}

/**
 * Generates a formatted error message
 *
 * @param Exception $exception Exception instance
 * @return string Formatted message
 */
	protected static function _getMessage($exception) {
		$message = sprintf("[%s] %s",
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
		$message .= "\nStack Trace:\n" . $exception->getTraceAsString();
		return $message;
	}

/**
 * Handles exception logging
 *
 * @param Exception $exception
 * @param array $config
 * @return boolean
 */
	protected static function _log(\Exception $exception, $config) {
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
		return Log::write('error', self::_getMessage($exception));
	}

/**
 * Set as the default error handler by CakePHP. Use Configure::write('Error.handler', $callback), to use your own
 * error handling methods. This function will use Debugger to display errors when debug > 0. And
 * will log errors to Log, when debug == 0.
 *
 * You can use Configure::write('Error.level', $value); to set what type of errors will be handled here.
 * Stack traces for errors can be enabled with Configure::write('Error.trace', true);
 *
 * @param integer $code Code of error
 * @param string $description Error description
 * @param string $file File on which error occurred
 * @param integer $line Line that triggered the error
 * @param array $context Context
 * @return boolean true if error was handled
 */
	public function handleError($code, $description, $file = null, $line = null, $context = null) {
		if (error_reporting() === 0) {
			return false;
		}
		list($error, $log) = static::mapErrorCode($code);
		if ($log === LOG_ERR) {
			return $this->handleFatalError($code, $description, $file, $line);
		}

		$debug = Configure::read('debug');
		if ($debug) {
			$data = array(
				'level' => $log,
				'code' => $code,
				'error' => $error,
				'description' => $description,
				'file' => $file,
				'line' => $line,
				'context' => $context,
				'start' => 2,
				'path' => Debugger::trimPath($file)
			);
			return Debugger::getInstance()->outputError($data);
		}
		$message = $error . ' (' . $code . '): ' . $description . ' in [' . $file . ', line ' . $line . ']';
		if (!empty($this->_options['trace'])) {
			$trace = Debugger::trace(array('start' => 1, 'format' => 'log'));
			$message .= "\nTrace:\n" . $trace . "\n";
		}
		return Log::write($log, $message);
	}

/**
 * Generate an error page when some fatal error happens.
 *
 * @param integer $code Code of error
 * @param string $description Error description
 * @param string $file File on which error occurred
 * @param integer $line Line that triggered the error
 * @return boolean
 */
	public function handleFatalError($code, $description, $file, $line) {
		$logMessage = 'Fatal Error (' . $code . '): ' . $description . ' in [' . $file . ', line ' . $line . ']';
		Log::write(LOG_ERR, $logMessage);

		if (ob_get_level()) {
			ob_end_clean();
		}

		if (Configure::read('debug')) {
			$this->handleException(new FatalErrorException($description, 500, $file, $line));
		} else {
			$this->handleException(new InternalErrorException());
		}
		return false;
	}

/**
 * Map an error code into an Error word, and log location.
 *
 * @param integer $code Error code to map
 * @return array Array of error word, and log location.
 */
	public static function mapErrorCode($code) {
		$error = $log = null;
		switch ($code) {
			case E_PARSE:
			case E_ERROR:
			case E_CORE_ERROR:
			case E_COMPILE_ERROR:
			case E_USER_ERROR:
				$error = 'Fatal Error';
				$log = LOG_ERR;
				break;
			case E_WARNING:
			case E_USER_WARNING:
			case E_COMPILE_WARNING:
			case E_RECOVERABLE_ERROR:
				$error = 'Warning';
				$log = LOG_WARNING;
				break;
			case E_NOTICE:
			case E_USER_NOTICE:
				$error = 'Notice';
				$log = LOG_NOTICE;
				break;
			case E_STRICT:
				$error = 'Strict';
				$log = LOG_NOTICE;
				break;
			case E_DEPRECATED:
			case E_USER_DEPRECATED:
				$error = 'Deprecated';
				$log = LOG_NOTICE;
				break;
		}
		return array($error, $log);
	}

}
