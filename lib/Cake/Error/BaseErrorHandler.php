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
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Error;

use Cake\Core\Configure;
use Cake\Log\Log;
use Cake\Utility\Debugger;

/**
 * Base error handler that provides logic common to the CLI + web
 * error/exception handlers.
 *
 * Subclasses are required to implement the template methods to handle displaying
 * the errors in their environment.
 */
abstract class BaseErrorHandler {

	abstract protected function _displayError($error, $debug);

	abstract protected function _displayException($exception);

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
 * Set as the default error handler by CakePHP.
 *
 * Use App/Config/error.php to customize or replace this error handler.
 * This function will use Debugger to display errors when debug > 0. And
 * will log errors to Log, when debug == 0.
 *
 * You can use the 'errorLevel' option to set what type of errors will be handled.
 * Stack traces for errors can be enabled with the 'trace' option.
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
		list($error, $log) = $this->mapErrorCode($code);
		if ($log === LOG_ERR) {
			return $this->handleFatalError($code, $description, $file, $line);
		}
		$data = array(
			'level' => $log,
			'code' => $code,
			'error' => $error,
			'description' => $description,
			'file' => $file,
			'line' => $line,
		);

		$debug = Configure::read('debug');
		if ($debug) {
			$data += [
				'context' => $context,
				'start' => 2,
				'path' => Debugger::trimPath($file)
			];
		}
		$this->_displayError($data, $debug);
		$this->_logError($log, $data);
	}

/**
 * Log an error.
 *
 * @param string $level The level name of the log.
 * @param array $data Array of error data.
 * @return void
 */
	protected function _logError($level, $data) {
		$message = sprintf('%s (%s): %s in [%s, line %s]',
			$data['error'],
			$data['code'],
			$data['description'],
			$data['file'],
			$data['line']
		);
		if (!empty($this->_options['trace'])) {
			$trace = Debugger::trace(array(
				'start' => 1,
				'format' => 'log'
			));
			$message .= "\nTrace:\n" . $trace . "\n";
		}
		return Log::write($level, $message);
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
