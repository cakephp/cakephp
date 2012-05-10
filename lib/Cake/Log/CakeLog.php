<?php
/**
 * Logging.
 *
 * Log messages to text files.
 *
 * PHP 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       Cake.Log
 * @since         CakePHP(tm) v 0.2.9
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

/**
 * Set up error level constants to be used within the framework if they are not defined within the
 * system.
 *
 */
if (!defined('LOG_ERROR')) {
	define('LOG_ERROR', 2);
}
if (!defined('LOG_ERR')) {
	define('LOG_ERR', LOG_ERROR);
}
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

App::uses('LogEngineCollection', 'Log');

/**
 * Logs messages to configured Log adapters.  One or more adapters can be configured
 * using CakeLogs's methods.  If you don't configure any adapters, and write to the logs
 * a default FileLog will be autoconfigured for you.
 *
 * ### Configuring Log adapters
 *
 * You can configure log adapters in your applications `bootstrap.php` file.  A sample configuration
 * would look like:
 *
 * `CakeLog::config('my_log', array('engine' => 'FileLog'));`
 *
 * See the documentation on CakeLog::config() for more detail.
 *
 * ### Writing to the log
 *
 * You write to the logs using CakeLog::write().  See its documentation for more information.
 *
 * @package       Cake.Log
 */
class CakeLog {

/**
 * LogEngineCollection class
 *
 * @var LogEngineCollection
 */
	protected static $_Collection;

/**
 * initialize ObjectCollection
 *
 * @return void
 */
	protected static function _init() {
		self::$_Collection = new LogEngineCollection();
	}

/**
 * Configure and add a new logging stream to CakeLog
 * You can use add loggers from app/Log/Engine use app.loggername, or any plugin/Log/Engine using plugin.loggername.
 *
 * ### Usage:
 *
 * {{{
 * CakeLog::config('second_file', array(
 * 		'engine' => 'FileLog',
 * 		'path' => '/var/logs/my_app/'
 * ));
 * }}}
 *
 * Will configure a FileLog instance to use the specified path.  All options that are not `engine`
 * are passed onto the logging adapter, and handled there.  Any class can be configured as a logging
 * adapter as long as it implements the methods in CakeLogInterface.
 *
 * @param string $key The keyname for this logger, used to remove the logger later.
 * @param array $config Array of configuration information for the logger
 * @return boolean success of configuration.
 * @throws CakeLogException
 */
	public static function config($key, $config) {
		if (!preg_match('/^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*/', $key)) {
			throw new CakeLogException(__d('cake_dev', 'Invalid key name'));
		}
		if (empty($config['engine'])) {
			throw new CakeLogException(__d('cake_dev', 'Missing logger classname'));
		}
		if (empty(self::$_Collection)) {
			self::_init();
		}
		self::$_Collection->load($key, $config);
		return true;
	}

/**
 * Returns the keynames of the currently active streams
 *
 * @return array Array of configured log streams.
 */
	public static function configured() {
		if (empty(self::$_Collection)) {
			self::_init();
		}
		return self::$_Collection->attached();
	}

/**
 * Removes a stream from the active streams.  Once a stream has been removed
 * it will no longer have messages sent to it.
 *
 * @param string $streamName Key name of a configured stream to remove.
 * @return void
 */
	public static function drop($streamName) {
		if (empty(self::$_Collection)) {
			self::_init();
		}
		self::$_Collection->unload($streamName);
	}

/**
 * Checks wether $streamName is enabled
 *
 * @param string $streamName to check
 * @return bool
 * @throws CakeLogException
 */
	public static function enabled($streamName) {
		if (empty(self::$_Collection)) {
			self::_init();
		}
		if (!isset(self::$_Collection->{$streamName})) {
			throw new CakeLogException(__d('cake_dev', 'Stream %s not found', $streamName));
		}
		return self::$_Collection->enabled($streamName);
	}

/**
 * Enable stream
 *
 * @param string $streamName to enable
 * @return void
 * @throws CakeLogException
 */
	public static function enable($streamName) {
		if (empty(self::$_Collection)) {
			self::_init();
		}
		if (!isset(self::$_Collection->{$streamName})) {
			throw new CakeLogException(__d('cake_dev', 'Stream %s not found', $streamName));
		}
		self::$_Collection->enable($streamName);
	}

/**
 * Disable stream
 *
 * @param string $streamName to disable
 * @return void
 * @throws CakeLogException
 */
	public static function disable($streamName) {
		if (empty(self::$_Collection)) {
			self::_init();
		}
		if (!isset(self::$_Collection->{$streamName})) {
			throw new CakeLogException(__d('cake_dev', 'Stream %s not found', $streamName));
		}
		self::$_Collection->disable($streamName);
	}

/**
 * Gets the logging engine from the active streams.
 *
 * @see BaseLog
 * @param string $streamName Key name of a configured stream to get.
 * @return $mixed instance of BaseLog or false if not found
 */
	public static function stream($streamName) {
		if (empty(self::$_Collection)) {
			self::_init();
		}
		if (!empty(self::$_Collection->{$streamName})) {
			return self::$_Collection->{$streamName};
		}
		return false;
	}

/**
 * Configures the automatic/default stream a FileLog.
 *
 * @return void
 */
	protected static function _autoConfig() {
		if (empty(self::$_Collection)) {
			self::_init();
		}
		self::$_Collection->load('error', array(
			'engine' => 'FileLog',
			'types' => array('error', 'warning'),
			'path' => LOGS,
		));
	}

/**
 * Writes the given message and type to all of the configured log adapters.
 * Configured adapters are passed both the $type and $message variables. $type
 * is one of the following strings/values.
 *
 * ### Types:
 *
 * - `LOG_WARNING` => 'warning',
 * - `LOG_NOTICE` => 'notice',
 * - `LOG_INFO` => 'info',
 * - `LOG_DEBUG` => 'debug',
 * - `LOG_ERR` => 'error',
 * - `LOG_ERROR` => 'error'
 *
 * ### Usage:
 *
 * Write a message to the 'warning' log:
 *
 * `CakeLog::write('warning', 'Stuff is broken here');`
 *
 * @param mixed $type Type of message being written. When value is an integer
 *                    or a string matching the recognized levels, then it will
 *                    be treated log levels. Otherwise it's treated as scope.
 * @param string $message Message content to log
 * @param mixed $scope string or array
 * @return boolean Success
 */
	public static function write($type, $message, $scope = array()) {
		if (empty(self::$_Collection)) {
			self::_init();
		}
		$levels = array(
			LOG_ERROR => 'error',
			LOG_ERR => 'error',
			LOG_WARNING => 'warning',
			LOG_NOTICE => 'notice',
			LOG_DEBUG => 'debug',
			LOG_INFO => 'info',
		);

		if (is_int($type) && isset($levels[$type])) {
			$type = $levels[$type];
		}
		if (is_string($type) && empty($scope) && !in_array($type, $levels)) {
			$scope = $type;
		}
		if (empty(self::$_streams)) {
			self::_autoConfig();
		}
		foreach (self::$_Collection->enabled() as $streamName) {
			$logger = self::$_Collection->{$streamName};
			$config = $logger->config();
			$types = $config['types'];
			$scopes = $config['scopes'];
			if (is_string($scope)) {
				$inScope = in_array($scope, $scopes);
			} else {
				$intersect = array_intersect($scope, $scopes);
				$inScope = !empty($intersect);
			}
			if (empty($types) || in_array($type, $types) || in_array($type, $scopes) && $inScope) {
				$logger->write($type, $message);
			}
		}
		return true;
	}

}
