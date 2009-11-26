<?php
/**
 * Logging.
 *
 * Log messages to text files.
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2009, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2009, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       cake
 * @subpackage    cake.cake.libs
 * @since         CakePHP(tm) v 0.2.9
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
/**
 * Set up error level constants to be used within the framework if they are not defined within the
 * system.
 *
 */
	if (!defined('LOG_WARNING')) {
		define('LOG_WARNING', 3);
	}
	if (!defined('LOG_NOTICE')) {
		define('LOG_NOTICE', 4);
	}
	if (!defined('LOG_DEBUG')) {
		define('LOG_DEBUG', 5);
	}
	if (!defined('LOG_INFO')) {
		define('LOG_INFO', 6);
	}

/**
 * Logs messages to text files
 *
 * @package       cake
 * @subpackage    cake.cake.libs
 */
class CakeLog {

/**
 * An array of connected streams.
 * Each stream represents a callable that will be called when write() is called.
 *
 * @var array
 * @access protected
 */
	var $_streams = array();

/**
 * Get an instance
 *
 * @return void
 * @static
 */
	function &getInstance() {
		static $instance = array();
		if (!isset($instance[0])) {
			$instance[0] = new CakeLog();
		}
		return $instance[0];
	}

/**
 * Configure and add a new logging stream to CakeLog
 * You can use add loggers from app/libs use app.loggername, or any plugin/libs using plugin.loggername
 *
 * @param string $key The keyname for this logger, used to revmoe the logger later.
 * @param array $config Array of configuration information for the logger
 * @return boolean success of configuration.
 * @static
 */
	function config($key, $config) {
		if (empty($config['engine'])) {
			trigger_error(__('Missing logger classname', true), E_USER_WARNING);
			return false;
		}
		$self = CakeLog::getInstance();
		$className = $self->_getLogger($config['engine']);
		if (!$className) {
			return false;
		}
		unset($config['engine']);
		$self->_streams[$key] = new $className($config);
		return true;
	}

/**
 * Attempts to import a logger class from the various paths it could be on.
 * Checks that the logger class implements a write method as well.
 *
 * @return mixed boolean false on any failures, string of classname to use if search was successful.\
 * @access protected
 */
	function _getLogger($loggerName) {
		list($plugin, $loggerName) = pluginSplit($loggerName);

		if ($plugin) {
			App::import('Lib', $plugin . '.log/' . $loggerName);
		} else {
			if (!App::import('Lib', 'log/' . $loggerName)) {
				App::import('Core', 'log/' . $loggerName);
			}
		}
		if (!class_exists($loggerName)) {
			trigger_error(sprintf(__('Could not load logger class %s', true), $loggerName), E_USER_WARNING);
			return false;
		}
		if (!method_exists($loggerName, 'write')) {
			trigger_error(
				sprintf(__('logger class %s does not implement a write method.', true), $loggerName),
				E_USER_WARNING
			);
			return false;
		}
		return $loggerName;
	}

/**
 * Returns the keynames of the currently active streams
 *
 * @return array
 * @static
 */
	function configured() {
		$self = CakeLog::getInstance();
		return array_keys($self->_streams);
	}

/**
 * Removes a stream from the active streams.  Once a stream has been removed
 * it will no longer have messages sent to it.
 *
 * @param string $keyname Key name of callable to remove.
 * @return void
 * @static
 */
	function drop($streamName) {
		$self = CakeLog::getInstance();
		unset($self->_streams[$streamName]);
	}

/**
 * Configures the automatic/default stream a FileLog.
 *
 * @return void
 * @access protected
 */
	function _autoConfig() {
		if (!class_exists('FileLog')) {
			App::import('Core', 'log/FileLog');
		}
		$this->_streams['default'] = new FileLog(array('path' => LOGS));
	}

/**
 * Writes given message to a log file in the logs directory.
 *
 * @param string $type Type of log, becomes part of the log's filename
 * @param string $message  Message to log
 * @return boolean Success
 * @access public
 * @static
 */
	function write($type, $message) {
		if (!defined('LOG_ERROR')) {
			define('LOG_ERROR', 2);
		}
		if (!defined('LOG_ERR')) {
			define('LOG_ERR', LOG_ERROR);
		}
		$levels = array(
			LOG_WARNING => 'warning',
			LOG_NOTICE => 'notice',
			LOG_INFO => 'info',
			LOG_DEBUG => 'debug',
			LOG_ERR => 'error',
			LOG_ERROR => 'error'
		);

		if (is_int($type) && isset($levels[$type])) {
			$type = $levels[$type];
		}
		$self = CakeLog::getInstance();
		if (empty($self->_streams)) {
			$self->_autoConfig();
		}
		$keys = array_keys($self->_streams);
		foreach ($keys as $key) {
			$logger =& $self->_streams[$key];
			$logger->write($type, $message);
		}
		return true;
	}

/**
 * An error_handler that will log errors to file using CakeLog::write();
 *
 * @param integer $code Code of error
 * @param string $description Error description
 * @param string $file File on which error occurred
 * @param integer $line Line that triggered the error
 * @param array $context Context
 * @return void
 */
	function handleError($code, $description, $file = null, $line = null, $context = null) {
		if ($code === 2048 || $code === 8192) {
			return;
		}
		switch ($code) {
			case E_PARSE:
			case E_ERROR:
			case E_CORE_ERROR:
			case E_COMPILE_ERROR:
			case E_USER_ERROR:
				$error = 'Fatal Error';
				$level = LOG_ERROR;
			break;
			case E_WARNING:
			case E_USER_WARNING:
			case E_COMPILE_WARNING:
			case E_RECOVERABLE_ERROR:
				$error = 'Warning';
				$level = LOG_WARNING;
			break;
			case E_NOTICE:
			case E_USER_NOTICE:
				$error = 'Notice';
				$level = LOG_NOTICE;
			break;
			default:
				return;
			break;
		}
		$message = $error . ' (' . $code . '): ' . $description . ' in [' . $file . ', line ' . $line . ']';
		CakeLog::write($level, $message);
	}
}

if (!defined('DISABLE_DEFAULT_ERROR_HANDLING')) {
	set_error_handler(array('CakeLog', 'handleError'));
}
?>