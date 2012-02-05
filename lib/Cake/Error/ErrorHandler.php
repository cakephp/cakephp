<?php
/**
 * Error handler
 *
 * Provides Error Capturing for Framework errors.
 *
 * PHP 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2011, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2011, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       Cake.Error
 * @since         CakePHP(tm) v 0.10.5.1732
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

App::uses('Debugger', 'Utility');
App::uses('CakeLog', 'Log');
App::uses('ExceptionRenderer', 'Error');
App::uses('AppController', 'Controller');

/**
 *
 * Error Handler provides basic error and exception handling for your application. It captures and
 * handles all unhandled exceptions and errors. Displays helpful framework errors when debug > 1.
 *
 * ### Uncaught exceptions
 *
 * When debug < 1 a CakeException will render 404 or  500 errors.  If an uncaught exception is thrown
 * and it is a type that ErrorHandler does not know about it will be treated as a 500 error.
 *
 * ### Implementing application specific exception handling
 *
 * You can implement application specific exception handling in one of a few ways.  Each approach
 * gives you different amounts of control over the exception handling process.
 *
 * - Set Configure::write('Exception.handler', 'YourClass::yourMethod');
 * - Create AppController::appError();
 * - Set Configure::write('Exception.renderer', 'YourClass');
 *
 * #### Create your own Exception handler with `Exception.handler`
 *
 * This gives you full control over the exception handling process.  The class you choose should be
 * loaded in your app/Config/bootstrap.php, so its available to handle any exceptions.  You can
 * define the handler as any callback type. Using Exception.handler overrides all other exception
 * handling settings and logic.
 *
 * #### Using `AppController::appError();`
 *
 * This controller method is called instead of the default exception rendering.  It receives the
 * thrown exception as its only argument.  You should implement your error handling in that method.
 * Using AppController::appError(), will supersede any configuration for Exception.renderer.
 *
 * #### Using a custom renderer with `Exception.renderer`
 *
 * If you don't want to take control of the exception handling, but want to change how exceptions are
 * rendered you can use `Exception.renderer` to choose a class to render exception pages.  By default
 * `ExceptionRenderer` is used.  Your custom exception renderer class should be placed in app/Lib/Error.
 *
 * Your custom renderer should expect an exception in its constructor, and implement a render method.
 * Failing to do so will cause additional errors.
 *
 * #### Logging exceptions
 *
 * Using the built-in exception handling, you can log all the exceptions
 * that are dealt with by ErrorHandler by setting `Exception.log` to true in your core.php.
 * Enabling this will log every exception to CakeLog and the configured loggers.
 *
 * ### PHP errors
 *
 * Error handler also provides the built in features for handling php errors (trigger_error).
 * While in debug mode, errors will be output to the screen using debugger.  While in production mode,
 * errors will be logged to CakeLog.  You can control which errors are logged by setting
 * `Error.level` in your core.php.
 *
 * #### Logging errors
 *
 * When ErrorHandler is used for handling errors, you can enable error logging by setting `Error.log` to true.
 * This will log all errors to the configured log handlers.
 *
 * #### Controlling what errors are logged/displayed
 *
 * You can control which errors are logged / displayed by ErrorHandler by setting `Error.level`.  Setting this
 * to one or a combination of a few of the E_* constants will only enable the specified errors.
 *
 * e.g. `Configure::write('Error.level', E_ALL & ~E_NOTICE);`
 *
 * Would enable handling for all non Notice errors.
 *
 * @package       Cake.Error
 * @see ExceptionRenderer for more information on how to customize exception rendering.
 */
class ErrorHandler {

/**
 * Set as the default exception handler by the CakePHP bootstrap process.
 *
 * This will either use custom exception renderer class if configured,
 * or use the default ExceptionRenderer.
 *
 * @param Exception $exception
 * @return void
 * @see http://php.net/manual/en/function.set-exception-handler.php
 */
	public static function handleException(Exception $exception) {
		$config = Configure::read('Exception');
		if (!empty($config['log'])) {
			$message = sprintf("[%s] %s\n%s",
				get_class($exception),
				$exception->getMessage(),
				$exception->getTraceAsString()
			);
			CakeLog::write(LOG_ERR, $message);
		}
		$renderer = $config['renderer'];
		if ($renderer !== 'ExceptionRenderer') {
			list($plugin, $renderer) = pluginSplit($renderer, true);
			App::uses($renderer, $plugin . 'Error');
		}
		try {
			$error = new $renderer($exception);
			$error->render();
		} catch (Exception $e) {
			set_error_handler(Configure::read('Error.handler')); // Should be using configured ErrorHandler
			Configure::write('Error.trace', false); // trace is useless here since it's internal
			$message = sprintf("[%s] %s\n%s", // Keeping same message format
				get_class($e),
				$e->getMessage(),
				$e->getTraceAsString()
			);
			trigger_error($message, E_USER_ERROR);
		}
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
		if (error_reporting() === 0) {
			return false;
		}
		$errorConfig = Configure::read('Error');
		list($error, $log) = self::mapErrorCode($code);

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
		} else {
			$message = $error . ' (' . $code . '): ' . $description . ' in [' . $file . ', line ' . $line . ']';
			if (!empty($errorConfig['trace'])) {
				$trace = Debugger::trace(array('start' => 1, 'format' => 'log'));
				$message .= "\nTrace:\n" . $trace . "\n";
			}
			return CakeLog::write($log, $message);
		}
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
			case E_DEPRECATED:
			case E_USER_DEPRECATED:
				$error = 'Deprecated';
				$log = LOG_NOTICE;
			break;
		}
		return array($error, $log);
	}
}
