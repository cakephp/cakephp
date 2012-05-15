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
 * Default log levels as detailed in RFC 5424
 * http://tools.ietf.org/html/rfc5424
 */
	protected static $_defaultLevels = array(
		LOG_EMERG => 'emergency',
		LOG_ALERT => 'alert',
		LOG_CRIT => 'critical',
		LOG_ERR => 'error',
		LOG_WARNING => 'warning',
		LOG_NOTICE => 'notice',
		LOG_INFO => 'info',
		LOG_DEBUG => 'debug',
	);

/**
 * Active log levels for this instance.
 */
	protected static $_levels;

/**
 * Mapped log levels
 */
	protected static $_levelMap;

/**
 * initialize ObjectCollection
 *
 * @return void
 */
	protected static function _init() {
		self::$_levels = self::defaultLevels();
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
 * Gets/sets log levels
 *
 * Call this method without arguments, eg: `CakeLog::levels()` to obtain current
 * level configuration.
 *
 * To append additional level 'user0' and 'user1' to to default log levels:
 *
 *     `CakeLog::levels(array('user0, 'user1'))` or
 *     `CakeLog::levels(array('user0, 'user1'), true)`
 *
 * will result in:
 *
 * array(
 *     0 => 'emergency',
 *     1 => 'alert',
 *     ...
 *     8 => 'user0',
 *     9 => 'user1',
 * );
 *
 * To set/replace existing configuration, pass an array with the second argument
 * set to false.
 *
 *     `CakeLog::levels(array('user0, 'user1'), false);
 *
 * will result in:
 * array(
 *      0 => 'user0',
 *      1 => 'user1',
 * );
 *
 * @param mixed $levels array
 * @param bool $append true to append, false to replace
 * @return array active log levels
 */
	public static function levels($levels = array(), $append = true) {
		if (empty(self::$_Collection)) {
			self::_init();
		}
		if (empty($levels)) {
			return self::$_levels;
		}
		$levels = array_values($levels);
		if ($append) {
			self::$_levels = array_merge(self::$_levels, $levels);
		} else {
			self::$_levels = $levels;
		}
		self::$_levelMap = array_flip(self::$_levels);
		return self::$_levels;
	}

/**
 * Reset log levels to the original value
 *
 * @return array default log levels
 */
	public static function defaultLevels() {
		self::$_levels = self::$_defaultLevels;
		self::$_levelMap = array_flip(self::$_levels);
		return self::$_levels;
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
		self::$_Collection->load('default', array(
			'engine' => 'FileLog',
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
 * -  LOG_EMERG => 'emergency',
 * -  LOG_ALERT => 'alert',
 * -  LOG_CRIT => 'critical',
 * - `LOG_ERR` => 'error',
 * - `LOG_WARNING` => 'warning',
 * - `LOG_NOTICE` => 'notice',
 * - `LOG_INFO` => 'info',
 * - `LOG_DEBUG` => 'debug',
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

		if (is_int($type) && isset(self::$_levels[$type])) {
			$type = self::$_levels[$type];
		}
		if (is_string($type) && empty($scope) && !in_array($type, self::$_levels)) {
			$scope = $type;
		}
		if (!self::$_Collection->attached()) {
			self::_autoConfig();
		}
		foreach (self::$_Collection->enabled() as $streamName) {
			$logger = self::$_Collection->{$streamName};
			$types = null;
			$scopes = array();
			if ($logger instanceof BaseLog) {
				$config = $logger->config();
				if (isset($config['types'])) {
					$types = $config['types'];
				}
				if (isset($config['scopes'])) {
					$scopes = $config['scopes'];
				}
			}
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

/**
 * Convenience method to log emergency messages
 *
 * @param string $message log message
 * @param mixed $scope string or array of scopes
 * @return boolean Success
 */
	public static function emergency($message, $scope = array()) {
		return self::write(self::$_levelMap['emergency'], $message, $scope);
	}

/**
 * Convenience method to log alert messages
 *
 * @param string $message log message
 * @param mixed $scope string or array of scopes
 * @return boolean Success
 */
	public static function alert($message, $scope = array()) {
		return self::write(self::$_levelMap['alert'], $message, $scope);
	}

/**
 * Convenience method to log critical messages
 *
 * @param string $message log message
 * @param mixed $scope string or array of scopes
 * @return boolean Success
 */
	public static function critical($message, $scope = array()) {
		return self::write(self::$_levelMap['critical'], $message, $scope);
	}

/**
 * Convenience method to log error messages
 *
 * @param string $message log message
 * @param mixed $scope string or array of scopes
 * @return boolean Success
 */
	public static function error($message, $scope = array()) {
		return self::write(self::$_levelMap['error'], $message, $scope);
	}

/**
 * Convenience method to log warning messages
 *
 * @param string $message log message
 * @param mixed $scope string or array of scopes
 * @return boolean Success
 */
	public static function warning($message, $scope = array()) {
		return self::write(self::$_levelMap['warning'], $message, $scope);
	}

/**
 * Convenience method to log notice messages
 *
 * @param string $message log message
 * @param mixed $scope string or array of scopes
 * @return boolean Success
 */
	public static function notice($message, $scope = array()) {
		return self::write(self::$_levelMap['notice'], $message, $scope);
	}

/**
 * Convenience method to log debug messages
 *
 * @param string $message log message
 * @param mixed $scope string or array of scopes
 * @return boolean Success
 */
	public static function debug($message, $scope = array()) {
		return self::write(self::$_levelMap['debug'], $message, $scope);
	}

/**
 * Convenience method to log info messages
 *
 * @param string $message log message
 * @param mixed $scope string or array of scopes
 * @return boolean Success
 */
	public static function info($message, $scope = array()) {
		return self::write(self::$_levelMap['info'], $message, $scope);
	}

}
