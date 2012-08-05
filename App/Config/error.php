<?php
namespace App\Config;

use Cake\Core\Configure;

/**
 * Configure the Error handler used to handle errors for your application.  By default
 * ErrorHandler::handleError() is used.  It will display errors using Debugger, when debug > 0
 * and log errors with Cake Log when debug = 0.
 *
 * Options:
 *
 * - `handler` - callback - The callback to handle errors. You can set this to any callable type,
 *    including anonymous functions.
 * - `consoleHandler` - callback - The callback to handle errors. You can set this to any callable type,
 *    including anonymous functions.
 * - `level` - int - The level of errors you are interested in capturing.
 * - `trace` - boolean - Include stack traces for errors in log files.
 *
 * @see ErrorHandler for more information on error handling and configuration.
 */
	$error = array(
		'handler' => 'Cake\Error\ErrorHandler::handleError',
		'consoleHandler' => 'Cake\Console\ConsoleErrorHandler::handleError',
		'level' => E_ALL & ~E_DEPRECATED,
		'trace' => true
	);

/**
 * Configure the Exception handler used for uncaught exceptions.  By default,
 * ErrorHandler::handleException() is used. It will display a HTML page for the exception, and
 * while debug > 0, framework errors like Missing Controller will be displayed.  When debug = 0,
 * framework errors will be coerced into generic HTTP errors.
 *
 * Options:
 *
 * - `handler` - callback - The callback to handle exceptions. You can set this to any callback type,
 *   including anonymous functions.
 * - `renderer` - string - The class responsible for rendering uncaught exceptions.  If you choose a custom class you
 *   should place the file for that class in app/Lib/Error. This class needs to implement a render method.
 * - `log` - boolean - Should Exceptions be logged?
 *
 * @see ErrorHandler for more information on exception handling and configuration.
 */
	$exception = array(
		'handler' => 'Cake\Error\ErrorHandler::handleException',
		'consoleHandler' => 'Cake\Console\ConsoleErrorHandler::handleException',
		'renderer' => 'Cake\Error\ExceptionRenderer',
		'log' => true
	);

	Configure::setErrorHandlers(
		$error,
		$exception
	);

	unset($error, $exception);
