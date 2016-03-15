<?php
/**
 * Logging.
 *
 * Log messages to text files.
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       Cake.Log
 * @since         CakePHP(tm) v 0.2.9
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */

App::uses('LogEngineCollection', 'Log');

/**
 * Logs messages to configured Log adapters.
 * 
 * One or more adapters
 * can be configured using CakeLogs's methods.
 *
 * ### Configuring Log adapters
 *
 * You can configure log adapters in your applications `bootstrap.php` file.
 * A sample configuration would look like:
 *
 * ```
 * CakeLog::config('my_log', array('engine' => 'File'));
 * ```
 *
 * See the documentation on CakeLog::config() for more detail.
 *
 * ### Writing to the log
 *
 * You write to the logs using CakeLog::write(). See its documentation for more
 * information.
 *
 * ### Logging Levels
 *
 * By default CakeLog supports all the log levels defined in
 * RFC 5424. When logging messages you can either use the named methods,
 * or the correct constants with `write()`:
 *
 * ```
 * CakeLog::error('Something horrible happened');
 * CakeLog::write(LOG_ERR, 'Something horrible happened');
 * ```
 *
 * If you require custom logging levels, you can use CakeLog::levels() to
 * append additional logging levels.
 *
 * ### Logging scopes
 *
 * When logging messages and configuring log adapters, you can specify
 * 'scopes' that the logger will handle. You can think of scopes as subsystems
 * in your application that may require different logging setups. For
 * example in an e-commerce application you may want to handle logged errors
 * in the cart and ordering subsystems differently than the rest of the
 * application. By using scopes you can control logging for each part
 * of your application and still keep standard log levels.
 *
 * See CakeLog::config() and CakeLog::write() for more information
 * on scopes
 *
 * @package       Cake.Log
 * @link http://book.cakephp.org/2.0/en/core-libraries/logging.html#logging
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
 *
 * @var array
 */
	protected static $_defaultLevels = array(
		'emergency' => LOG_EMERG,
		'alert' => LOG_ALERT,
		'critical' => LOG_CRIT,
		'error' => LOG_ERR,
		'warning' => LOG_WARNING,
		'notice' => LOG_NOTICE,
		'info' => LOG_INFO,
		'debug' => LOG_DEBUG,
	);

/**
 * Active log levels for this instance.
 *
 * @var array
 */
	protected static $_levels;

/**
 * Mapped log levels
 *
 * @var array
 */
	protected static $_levelMap;

/**
 * initialize ObjectCollection
 *
 * @return void
 */
	protected static function _init() {
		static::$_levels = static::defaultLevels();
		static::$_Collection = new LogEngineCollection();
	}

/**
 * Configure and add a new logging stream to CakeLog
 * You can use add loggers from app/Log/Engine use app.loggername, or any
 * plugin/Log/Engine using plugin.loggername.
 *
 * ### Usage:
 *
 * ```
 * CakeLog::config('second_file', array(
 *     'engine' => 'File',
 *     'path' => '/var/logs/my_app/'
 * ));
 * ```
 *
 * Will configure a FileLog instance to use the specified path.
 * All options that are not `engine` are passed onto the logging adapter,
 * and handled there. Any class can be configured as a logging
 * adapter as long as it implements the methods in CakeLogInterface.
 *
 * ### Logging levels
 *
 * When configuring loggers, you can set which levels a logger will handle.
 * This allows you to disable debug messages in production for example:
 *
 * ```
 * CakeLog::config('default', array(
 *     'engine' => 'File',
 *     'path' => LOGS,
 *     'levels' => array('error', 'critical', 'alert', 'emergency')
 * ));
 * ```
 *
 * The above logger would only log error messages or higher. Any
 * other log messages would be discarded.
 *
 * ### Logging scopes
 *
 * When configuring loggers you can define the active scopes the logger
 * is for. If defined only the listed scopes will be handled by the
 * logger. If you don't define any scopes an adapter will catch
 * all scopes that match the handled levels.
 *
 * ```
 * CakeLog::config('payments', array(
 *     'engine' => 'File',
 *     'types' => array('info', 'error', 'warning'),
 *     'scopes' => array('payment', 'order')
 * ));
 * ```
 *
 * The above logger will only capture log entries made in the
 * `payment` and `order` scopes. All other scopes including the
 * undefined scope will be ignored. Its important to remember that
 * when using scopes you must also define the `types` of log messages
 * that a logger will handle. Failing to do so will result in the logger
 * catching all log messages even if the scope is incorrect.
 *
 * @param string $key The keyname for this logger, used to remove the
 *    logger later.
 * @param array $config Array of configuration information for the logger
 * @return bool success of configuration.
 * @throws CakeLogException
 * @link http://book.cakephp.org/2.0/en/core-libraries/logging.html#creating-and-configuring-log-streams
 */
	public static function config($key, $config) {
		if (!preg_match('/^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*/', $key)) {
			throw new CakeLogException(__d('cake_dev', 'Invalid key name'));
		}
		if (empty($config['engine'])) {
			throw new CakeLogException(__d('cake_dev', 'Missing logger class name'));
		}
		if (empty(static::$_Collection)) {
			static::_init();
		}
		static::$_Collection->load($key, $config);
		return true;
	}

/**
 * Returns the keynames of the currently active streams
 *
 * @return array Array of configured log streams.
 */
	public static function configured() {
		if (empty(static::$_Collection)) {
			static::_init();
		}
		return static::$_Collection->loaded();
	}

/**
 * Gets/sets log levels
 *
 * Call this method without arguments, eg: `CakeLog::levels()` to obtain current
 * level configuration.
 *
 * To append additional level 'user0' and 'user1' to to default log levels:
 *
 * ```
 * CakeLog::levels(array('user0, 'user1'));
 * // or
 * CakeLog::levels(array('user0, 'user1'), true);
 * ```
 *
 * will result in:
 *
 * ```
 * array(
 *     0 => 'emergency',
 *     1 => 'alert',
 *     ...
 *     8 => 'user0',
 *     9 => 'user1',
 * );
 * ```
 *
 * To set/replace existing configuration, pass an array with the second argument
 * set to false.
 *
 * ```
 * CakeLog::levels(array('user0, 'user1'), false);
 * ```
 *
 * will result in:
 *
 * ```
 * array(
 *      0 => 'user0',
 *      1 => 'user1',
 * );
 * ```
 *
 * @param array $levels array
 * @param bool $append true to append, false to replace
 * @return array Active log levels
 */
	public static function levels($levels = array(), $append = true) {
		if (empty(static::$_Collection)) {
			static::_init();
		}
		if (empty($levels)) {
			return static::$_levels;
		}
		$levels = array_values($levels);
		if ($append) {
			static::$_levels = array_merge(static::$_levels, $levels);
		} else {
			static::$_levels = $levels;
		}
		static::$_levelMap = array_flip(static::$_levels);
		return static::$_levels;
	}

/**
 * Reset log levels to the original value
 *
 * @return array Default log levels
 */
	public static function defaultLevels() {
		static::$_levelMap = static::$_defaultLevels;
		static::$_levels = array_flip(static::$_levelMap);
		return static::$_levels;
	}

/**
 * Removes a stream from the active streams. Once a stream has been removed
 * it will no longer have messages sent to it.
 *
 * @param string $streamName Key name of a configured stream to remove.
 * @return void
 */
	public static function drop($streamName) {
		if (empty(static::$_Collection)) {
			static::_init();
		}
		static::$_Collection->unload($streamName);
	}

/**
 * Checks whether $streamName is enabled
 *
 * @param string $streamName to check
 * @return bool
 * @throws CakeLogException
 */
	public static function enabled($streamName) {
		if (empty(static::$_Collection)) {
			static::_init();
		}
		if (!isset(static::$_Collection->{$streamName})) {
			throw new CakeLogException(__d('cake_dev', 'Stream %s not found', $streamName));
		}
		return static::$_Collection->enabled($streamName);
	}

/**
 * Enable stream. Streams that were previously disabled
 * can be re-enabled with this method.
 *
 * @param string $streamName to enable
 * @return void
 * @throws CakeLogException
 */
	public static function enable($streamName) {
		if (empty(static::$_Collection)) {
			static::_init();
		}
		if (!isset(static::$_Collection->{$streamName})) {
			throw new CakeLogException(__d('cake_dev', 'Stream %s not found', $streamName));
		}
		static::$_Collection->enable($streamName);
	}

/**
 * Disable stream. Disabling a stream will
 * prevent that log stream from receiving any messages until
 * its re-enabled.
 *
 * @param string $streamName to disable
 * @return void
 * @throws CakeLogException
 */
	public static function disable($streamName) {
		if (empty(static::$_Collection)) {
			static::_init();
		}
		if (!isset(static::$_Collection->{$streamName})) {
			throw new CakeLogException(__d('cake_dev', 'Stream %s not found', $streamName));
		}
		static::$_Collection->disable($streamName);
	}

/**
 * Gets the logging engine from the active streams.
 *
 * @param string $streamName Key name of a configured stream to get.
 * @return mixed instance of BaseLog or false if not found
 * @see BaseLog
 */
	public static function stream($streamName) {
		if (empty(static::$_Collection)) {
			static::_init();
		}
		if (!empty(static::$_Collection->{$streamName})) {
			return static::$_Collection->{$streamName};
		}
		return false;
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
 * @param int|string $type Type of message being written. When value is an integer
 *    or a string matching the recognized levels, then it will
 *    be treated log levels. Otherwise it's treated as scope.
 * @param string $message Message content to log
 * @param string|array $scope The scope(s) a log message is being created in.
 *    See CakeLog::config() for more information on logging scopes.
 * @return bool Success
 * @link http://book.cakephp.org/2.0/en/core-libraries/logging.html#writing-to-logs
 */
	public static function write($type, $message, $scope = array()) {
		if (empty(static::$_Collection)) {
			static::_init();
		}

		if (is_int($type) && isset(static::$_levels[$type])) {
			$type = static::$_levels[$type];
		}
		if (is_string($type) && empty($scope) && !in_array($type, static::$_levels)) {
			$scope = $type;
		}
		$logged = false;
		foreach (static::$_Collection->enabled() as $streamName) {
			$logger = static::$_Collection->{$streamName};
			$types = $scopes = $config = array();
			if (method_exists($logger, 'config')) {
				$config = $logger->config();
			}
			if (isset($config['types'])) {
				$types = $config['types'];
			}
			if (isset($config['scopes'])) {
				$scopes = $config['scopes'];
			}
			$inScope = (count(array_intersect((array)$scope, $scopes)) > 0);
			$correctLevel = in_array($type, $types);

			if (
				// No config is a catch all (bc mode)
				(empty($types) && empty($scopes)) ||
				// BC layer for mixing scope & level
				(in_array($type, $scopes)) ||
				// no scopes, but has level
				(empty($scopes) && $correctLevel) ||
				// exact scope + level
				($correctLevel && $inScope)
			) {
				$logger->write($type, $message);
				$logged = true;
			}
		}
		return $logged;
	}

/**
 * Convenience method to log emergency messages
 *
 * @param string $message log message
 * @param string|array $scope The scope(s) a log message is being created in.
 *    See CakeLog::config() for more information on logging scopes.
 * @return bool Success
 */
	public static function emergency($message, $scope = array()) {
		return static::write(static::$_levelMap['emergency'], $message, $scope);
	}

/**
 * Convenience method to log alert messages
 *
 * @param string $message log message
 * @param string|array $scope The scope(s) a log message is being created in.
 *    See CakeLog::config() for more information on logging scopes.
 * @return bool Success
 */
	public static function alert($message, $scope = array()) {
		return static::write(static::$_levelMap['alert'], $message, $scope);
	}

/**
 * Convenience method to log critical messages
 *
 * @param string $message log message
 * @param string|array $scope The scope(s) a log message is being created in.
 *    See CakeLog::config() for more information on logging scopes.
 * @return bool Success
 */
	public static function critical($message, $scope = array()) {
		return static::write(static::$_levelMap['critical'], $message, $scope);
	}

/**
 * Convenience method to log error messages
 *
 * @param string $message log message
 * @param string|array $scope The scope(s) a log message is being created in.
 *    See CakeLog::config() for more information on logging scopes.
 * @return bool Success
 */
	public static function error($message, $scope = array()) {
		return static::write(static::$_levelMap['error'], $message, $scope);
	}

/**
 * Convenience method to log warning messages
 *
 * @param string $message log message
 * @param string|array $scope The scope(s) a log message is being created in.
 *    See CakeLog::config() for more information on logging scopes.
 * @return bool Success
 */
	public static function warning($message, $scope = array()) {
		return static::write(static::$_levelMap['warning'], $message, $scope);
	}

/**
 * Convenience method to log notice messages
 *
 * @param string $message log message
 * @param string|array $scope The scope(s) a log message is being created in.
 *    See CakeLog::config() for more information on logging scopes.
 * @return bool Success
 */
	public static function notice($message, $scope = array()) {
		return static::write(static::$_levelMap['notice'], $message, $scope);
	}

/**
 * Convenience method to log debug messages
 *
 * @param string $message log message
 * @param string|array $scope The scope(s) a log message is being created in.
 *    See CakeLog::config() for more information on logging scopes.
 * @return bool Success
 */
	public static function debug($message, $scope = array()) {
		return static::write(static::$_levelMap['debug'], $message, $scope);
	}

/**
 * Convenience method to log info messages
 *
 * @param string $message log message
 * @param string|array $scope The scope(s) a log message is being created in.
 *    See CakeLog::config() for more information on logging scopes.
 * @return bool Success
 */
	public static function info($message, $scope = array()) {
		return static::write(static::$_levelMap['info'], $message, $scope);
	}

}
