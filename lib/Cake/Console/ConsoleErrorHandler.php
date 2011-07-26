<?php
/**
 * ErrorHandler for Console Shells
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
 * @since         CakePHP(tm) v 2.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
App::uses('ErrorHandler', 'Error');
App::uses('ConsoleOutput', 'Console');
App::uses('CakeLog', 'Log');

/**
 * Error Handler for Cake console. Does simple printing of the 
 * exception that occurred and the stack trace of the error.
 *
 * @package       Cake.Console
 */
class ConsoleErrorHandler extends ErrorHandler {

/**
 * Standard error stream.
 *
 * @var filehandle
 * @access public
 */
	public static $stderr;

/**
 * Get the stderr object for the console error handling.
 *
 * @param Exception $error Exception to handle.
 * @param array $messages Error messages
 */
	public static function getStderr() {
		if (empty(self::$stderr)) {
			self::$stderr = new ConsoleOutput('php://stderr');
		}
		return self::$stderr;
	}

/**
 * Handle a exception in the console environment. Prints a message to stderr.
 *
 * @param Exception $exception The exception to handle
 * @return void
 */
	public static function handleException(Exception $exception) {
		$stderr = self::getStderr();
		$stderr->write(__d('cake_console', "<error>Error:</error> %s\n%s",
			$exception->getMessage(), 
			$exception->getTraceAsString()
		));
	}

/**
 * Handle errors in the console environment. Writes errors to stderr,
 * and logs messages if Configure::read('debug') is 0.
 *
 * @param int $code Error code
 * @param string $description Description of the error.
 * @param string $file The file the error occurred in.
 * @param int $line The line the error occurred on.
 * @param array $context The backtrace of the error.
 * @return void
 */
	public static function handleError($code, $description, $file = null, $line = null, $context = null) {
		if (error_reporting() === 0) {
			return;
		}
		$stderr = self::getStderr();
		list($name, $log) = self::_mapErrorCode($code);
		$message = __d('cake_console', '%s in [%s, line %s]', $description, $file, $line);
		$stderr->write(__d('cake_console', "<error>%s Error:</error> %s\n", $name, $message));

		if (Configure::read('debug') == 0) {
			CakeLog::write($log, $message);
		}
	}

}
