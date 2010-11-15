<?php
/**
 * Error handler
 *
 * Provides Error Capturing for Framework errors.
 *
 * PHP 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       cake
 * @subpackage    cake.cake.libs
 * @since         CakePHP(tm) v 0.10.5.1732
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

/**
 * Error Handler.
 *
 * Captures and handles all unhandled exceptions. Displays helpful framework errors when debug > 1.
 * When debug < 1 a CakeException will render 404 or  500 errors.  If an uncaught exception is thrown
 * and it is a type that ErrorHandler does not know about it will be treated as a 500 error.
 *
 * ### Implementing application specific exception handling
 *
 * You can implement application specific exception handling in one of a few ways:
 *
 * - Create a AppController::appError();
 * - Create an AppError class.
 *
 * #### Using AppController::appError();
 *
 * This controller method is called instead of the default exception handling.  It receives the 
 * thrown exception as its only argument.  You should implement your error handling in that method.
 *
 * #### Using an AppError class
 *
 * This approach gives more flexibility and power in how you handle exceptions.  You can create 
 * `app/libs/app_error.php` and create a class called `AppError`.  The core ErrorHandler class
 * will attempt to construct this class and let it handle the exception. This provides a more
 * flexible way to handle exceptions in your application.
 *
 * Finally, in your `app/config/bootstrap.php` you can configure use `set_exception_handler()`
 * to take total control over application exception handling.
 *
 * @package       cake
 * @subpackage    cake.cake.libs
 */
class ErrorHandler {

/**
 * Set as the default exception handler by the CakePHP bootstrap process.
 *
 * This will either use an AppError class if your application has one,
 * or use the default ExceptionRenderer.
 *
 * @return void
 * @see http://php.net/manual/en/function.set-exception-handler.php
 */
	public static function handleException(Exception $exception) {
		App::import('Core', 'ExceptionRenderer');
		if (file_exists(APP . 'app_error.php') || class_exists('AppError')) {
			if (!class_exists('AppError')) {
				require(APP . 'app_error.php');
			}
			$AppError = new AppError($exception);
			return $AppError->render();
		}
		if (Configure::read('Exception.log')) {
			if (!class_exists('CakeLog')) {
				require LIBS . 'cake_log.php';
			}
			CakeLog::write(LOG_ERR, '[' . get_class($exception) . '] ' . $exception->getMessage());
		}
		$error = new ExceptionRenderer($exception);
		$error->render();
	}

/**
 * Set as the default error handler by CakePHP. Use Configure::write('Error.handler', $callback), to use your own
 * error handling methods.  This function will use Debugger to display errors when debug > 0.  And 
 * will log errors to CakeLog, when debug == 0.
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
	public static function handleError($code, $description, $file = null, $line = null, $context = null) {
		$errorConfig = Configure::read('Error');
		if (isset($errorConfig['level']) && ($code & ~$errorConfig['level'])) {
			return;
		}
		list($error, $log) = self::_mapErrorCode($code);

		$debug = Configure::read('debug');
		if ($debug) {
			if (!class_exists('Debugger')) {
				require LIBS . 'debugger.php';
			}
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
		} else {
			if (!class_exists('CakeLog')) {
				require LIBS . 'cake_log.php';
			}
			$message = $error . ' (' . $code . '): ' . $description . ' in [' . $file . ', line ' . $line . ']';
			if (!empty($errorConfig['trace'])) {
				if (!class_exists('Debugger')) {
					require LIBS . 'debugger.php';
				}
				$trace = Debugger::trace(array('start' => 1, 'format' => 'log'));
				$message .= "\nTrace:\n" . $trace . "\n";
			}
			return CakeLog::write($log, $message);
		}
	}

/**
 * Map an error code into an Error word, and log location.
 *
 * @param int $code Error code to map
 * @return array Array of error word, and log location.
 */
	protected static function _mapErrorCode($code) {
		switch ($code) {
			case E_PARSE:
			case E_ERROR:
			case E_CORE_ERROR:
			case E_COMPILE_ERROR:
			case E_USER_ERROR:
				$error = 'Fatal Error';
				$log = LOG_ERROR;
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
			default:
				return array();
			break;
		}
		return array($error, $log);
	}
}
