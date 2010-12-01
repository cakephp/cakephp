<?php
/**
 * ErrorHandler for Console Shells
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
 * @subpackage    cake.cake.console
 * @since         CakePHP(tm) v 2.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
App::import('Core', 'ErrorHandler');
require_once 'console_output.php';

/**
 * Error Handler for Cake console. Does simple printing of the 
 * exception that occurred and the stack trace of the error.
 *
 * @package       cake
 * @subpackage    cake.cake.console
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
 * Handle a exception in the console environment.
 *
 * @return void
 */
	public static function handleException($exception) {
		$stderr = self::getStderr();
		$stderr->write(sprintf(
			__("<error>Error:</error> %s\n%s"), 
			$exception->getMessage(), 
			$exception->getTraceAsString()
		));
	}

/**
 * Handle errors in the console environment.
 *
 * @return void
 */
	public static function handleError($code, $description, $file = null, $line = null, $context = null) {
		$stderr = self::getStderr();
		list($name, $log) = self::_mapErrorCode($code);
		$message = sprintf(__('%s in [%s, line %s]'), $description, $file, $line);
		$stderr->write(sprintf(__("<error>%s Error:</error> %s\n"), $name, $message));

		if (Configure::read('debug') == 0) {
			App::import('Core', 'CakeLog');
			CakeLog::write($log, $message);
		}
	}

/**
 * undocumented function
 *
 * @return void
 */
	public function render() {
		$this->stderr->write(sprintf(
			__("<error>Error:</error> %s\n%s"), 
			$this->error->getMessage(), 
			$this->error->getTraceAsString()
		));
	}

}
