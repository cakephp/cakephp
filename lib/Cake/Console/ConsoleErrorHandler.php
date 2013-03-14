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
 * @since         CakePHP(tm) v 2.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
namespace Cake\Console;

use Cake\Core\Configure;
use Cake\Error\ErrorHandler;

/**
 * Error Handler for Cake console. Does simple printing of the
 * exception that occurred and the stack trace of the error.
 *
 * @package       Cake.Console
 */
class ConsoleErrorHandler {

/**
 * Standard error stream.
 *
 * @var ConsoleOutput
 */
	public static $stderr;

/**
 * Get the stderr object for the console error handling.
 *
 * @return ConsoleOutput
 */
	public static function getStderr() {
		if (empty(static::$stderr)) {
			static::$stderr = new ConsoleOutput('php://stderr');
		}
		return static::$stderr;
	}

/**
 * Handle a exception in the console environment. Prints a message to stderr.
 *
 * @param Exception $exception The exception to handle
 * @return integer Exit code from exception caught.
 */
	public static function handleException(\Exception $exception) {
		$stderr = static::getStderr();
		$stderr->write(__d('cake_console', "<error>Error:</error> %s\n%s",
			$exception->getMessage(),
			$exception->getTraceAsString()
		));
		return $exception->getCode() ?: 1;
	}

/**
 * Handle errors in the console environment. Writes errors to stderr,
 * and logs messages if Configure::read('debug') is 0.
 *
 * @param integer $code Error code
 * @param string $description Description of the error.
 * @param string $file The file the error occurred in.
 * @param integer $line The line the error occurred on.
 * @param array $context The backtrace of the error.
 * @return void
 */
	public static function handleError($code, $description, $file = null, $line = null, $context = null) {
		if (error_reporting() === 0) {
			return;
		}
		$stderr = static::getStderr();
		list($name, $log) = ErrorHandler::mapErrorCode($code);
		$message = __d('cake_console', '%s in [%s, line %s]', $description, $file, $line);
		$stderr->write(__d('cake_console', "<error>%s Error:</error> %s\n", $name, $message));

		if (!Configure::read('debug')) {
			Log::write($log, $message);
		}

		if ($log === LOG_ERR) {
			// @todo define how to handle exit
			return 1;
		}
	}

}
