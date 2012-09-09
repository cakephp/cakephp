<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         CakePHP(tm) v 0.2.9
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
namespace Cake\Log;

use Cake\Core\App;
use Cake\Core\Configure;
use Cake\Error;
use Cake\Log\Engine\BaseLog;
use Cake\Log\Engine\FileLog;

/**
 * Logs messages to configured Log adapters.  One or more adapters
 * can be configured using Cake Logs's methods.  If you don't
 * configure any adapters, and write to the logs a default
 * FileLog will be autoconfigured for you.
 *
 * ### Configuring Log adapters
 *
 * You can configure log adapters in your applications `bootstrap.php` file.
 * A sample configuration would look like:
 *
 * {{{
 * Log::config('my_log', array('engine' => 'FileLog'));
 * }}}
 *
 * See the documentation on Log::config() for more detail.
 *
 * ### Writing to the log
 *
 * You write to the logs using Log::write().  See its documentation for more information.
 *
 * ### Logging Levels
 *
 * By default Cake Log supports all the log levels defined in
 * RFC 5424. When logging messages you can either use the named methods,
 * or the correct constants with `write()`:
 *
 * {{{
 * Log::error('Something horrible happened');
 * Log::write(LOG_ERR, 'Something horrible happened');
 * }}}
 *
 * If you require custom logging levels, you can use Log::levels() to
 * append additoinal logging levels.
 *
 * ### Logging scopes
 *
 * When logging messages and configuring log adapters, you can specify
 * 'scopes' that the logger will handle.  You can think of scopes as subsystems
 * in your application that may require different logging setups.  For
 * example in an e-commerce application you may want to handle logged errors
 * in the cart and ordering subsystems differently than the rest of the
 * application.  By using scopes you can control logging for each part
 * of your application and still keep standard log levels.
 *
 * See Log::config() and Log::write() for more information
 * on scopes
 *
 * @package       Cake.Log
 */
class Log {

/**
 * LogEngineCollection class
 *
 * @var LogEngineCollection
 */
	protected static $_Collection;

/**
 * Log levels as detailed in RFC 5424
 * http://tools.ietf.org/html/rfc5424
 *
 * @var array
 */
	protected static $_levels = array(
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
 * Mapped log levels
 *
 * @var array
 */
	protected static $_levelMap = array(
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
 * initialize ObjectCollection
 *
 * @return void
 */
	protected static function _init() {
		if (empty(static::$_Collection)) {
			static::$_Collection = new LogEngineCollection();
			static::_loadConfig();
		}
	}

/**
 * Load the defined configuration and create all the defined logging
 * adapters.
 *
 * @return void
 */
	protected static function _loadConfig() {
		$loggers = Configure::read('Log');
		foreach ($loggers as $key => $config) {
			static::$_Collection->load($key, $config);
		}
	}

/**
 * Reset all the connected loggers.  This is useful to do when changing the logging
 * configuration or during testing when you want to reset the internal state of the
 * Log class.
 *
 * Resets the configured logging adapters, as well as any custom logging levels.
 *
 * @return void
 */
	public static function reset() {
		static::$_Collection = null;
	}

/**
 * Configure and add a new logging stream to Cake Log
 * You can use add loggers from app/Log/Engine use app.loggername, or any
 * plugin/Log/Engine using plugin.loggername.
 *
 * ### Usage:
 *
 * {{{
 * Log::config('second_file', array(
 *     'engine' => 'FileLog',
 *     'path' => '/var/logs/my_app/'
 * ));
 * }}}
 *
 * Will configure a FileLog instance to use the specified path.
 * All options that are not `engine` are passed onto the logging adapter,
 * and handled there.  Any class can be configured as a logging
 * adapter as long as it implements the methods in Cake\Log\LogInterface.
 *
 * ### Logging levels
 *
 * When configuring loggers, you can set which levels a logger will handle.
 * This allows you to disable debug messages in production for example:
 *
 * {{{
 * Log::config('default', array(
 *     'engine' => 'File',
 *     'path' => LOGS,
 *     'levels' => array('error', 'critical', 'alert', 'emergency')
 * ));
 * }}}
 *
 * The above logger would only log error messages or higher. Any
 * other log messages would be discarded.
 *
 * ### Logging scopes
 *
 * When configuring loggers you can define the active scopes the logger
 * is for.  If defined only the listed scopes will be handled by the
 * logger.  If you don't define any scopes an adapter will catch
 * all scopes that match the handled levels.
 *
 * {{{
 * Log::config('payments', array(
 *     'engine' => 'File',
 *     'scopes' => array('payment', 'order')
 * ));
 * }}}
 *
 * The above logger will only capture log entries made in the
 * `payment` and `order` scopes. All other scopes including the
 * undefined scope will be ignored.
 *
 * @param string $key The keyname for this logger, used to remove the
 *    logger later.
 * @param array $config Array of configuration information for the logger
 * @return boolean success of configuration.
 * @throws Cake\Error\LogException
 */
	public static function config($key, $config) {
		trigger_error(
			__d('cake_dev', 'You must use Configure::write() to define logging configuration. Or use engine() to inject new adapter.'),
			E_USER_WARNING
		);
	}

/**
 * Returns the keynames of the currently active streams
 *
 * @return array Array of configured log streams.
 */
	public static function configured() {
		static::_init();
		return static::$_Collection->attached();
	}

/**
 * Gets log levels
 *
 * Call this method to obtain current
 * level configuration.
 *
 * @return array active log levels
 */
	public static function levels() {
		return static::$_levels;
	}

/**
 * Removes a stream from the active streams.  Once a stream has been removed
 * it will no longer have messages sent to it.
 *
 * @param string $streamName Key name of a configured stream to remove.
 * @return void
 */
	public static function drop($streamName) {
		static::_init();
		static::$_Collection->unload($streamName);
	}

/**
 * Checks wether $streamName is enabled
 *
 * @param string $streamName to check
 * @return bool
 * @throws Cake\Error\LogException
 */
	public static function enabled($streamName) {
		static::_init();
		if (!isset(static::$_Collection->{$streamName})) {
			throw new Error\LogException(__d('cake_dev', 'Stream %s not found', $streamName));
		}
		return static::$_Collection->enabled($streamName);
	}

/**
 * Enable stream.  Streams that were previously disabled
 * can be re-enabled with this method.
 *
 * @param string $streamName to enable
 * @return void
 * @throws Cake\Error\LogException
 */
	public static function enable($streamName) {
		static::_init();
		if (!isset(static::$_Collection->{$streamName})) {
			throw new Error\LogException(__d('cake_dev', 'Stream %s not found', $streamName));
		}
		static::$_Collection->enable($streamName);
	}

/**
 * Disable stream.  Disabling a stream will
 * prevent that log stream from receiving any messages until
 * its re-enabled.
 *
 * @param string $streamName to disable
 * @return void
 * @throws Cake\Error\LogException
 */
	public static function disable($streamName) {
		static::_init();
		if (!isset(static::$_Collection->{$streamName})) {
			throw new Error\LogException(__d('cake_dev', 'Stream %s not found', $streamName));
		}
		static::$_Collection->disable($streamName);
	}

/**
 * Get/Set a logging engine.
 *
 * @see BaseLog
 * @param string $name Key name of a configured adapter to get/set
 * @param LogInterface $engine The logging instance to inject/add to Log.
 * @return $mixed instance of BaseLog or false if not found
 */
	public static function engine($name, $engine = null) {
		static::_init();
		if ($engine) {
			static::$_Collection->load($name, $engine);
			return;
		}
		if (static::$_Collection->{$name}) {
			return static::$_Collection->{$name};
		}
		return false;
	}

/**
 * Writes the given message and type to all of the configured log adapters.
 * Configured adapters are passed both the $level and $message variables. $level
 * is one of the following strings/values.
 *
 * ### Levels:
 *
 * - `LOG_EMERG` => 'emergency',
 * - `LOG_ALERT` => 'alert',
 * - `LOG_CRIT` => 'critical',
 * - `LOG_ERR` => 'error',
 * - `LOG_WARNING` => 'warning',
 * - `LOG_NOTICE` => 'notice',
 * - `LOG_INFO` => 'info',
 * - `LOG_DEBUG` => 'debug',
 *
 * ### Basic usage
 *
 * Write a 'warning' message to the logs:
 *
 * `Log::write('warning', 'Stuff is broken here');`
 *
 * ### Using scopes
 *
 * When writing a log message you can define one or many scopes for the message.
 * This allows you to handle messages differently based on application section/feature.
 *
 * `Log::write('warning', 'Payment failed', 'payment');`
 *
 * When configuring loggers you can configure the scopes a particular logger will handle.
 * When using scopes, you must ensure that the level of the message, and the scope of the message
 * intersect with the defined levels & scopes for a logger.
 *
 * ### Unhandled log messages
 *
 * If no configured logger can handle a log message (because of level or scope restrictions)
 * then the logged message will be ignored and silently dropped. You can check if this has happened
 * by inspecting the return of write().  If false the message was not handled.
 *
 * @param integer|string $level The level of the message being written. The value must be
 *    an integer or string matching a known level.
 * @param string $message Message content to log
 * @param string|array $scope The scope(s) a log message is being created in.
 *    See Cake\Log\Log::config() for more information on logging scopes.
 * @return boolean Success
 */
	public static function write($level, $message, $scope = array()) {
		static::_init();
		if (is_int($level) && isset(static::$_levels[$level])) {
			$level = static::$_levels[$level];
		}
		$logged = false;
		foreach (static::$_Collection->enabled() as $streamName) {
			$logger = static::$_Collection->{$streamName};
			$levels = $scopes = null;
			if ($logger instanceof BaseLog) {
				$config = $logger->config();
				if (isset($config['types'])) {
					$levels = $config['types'];
				}
				if (isset($config['scopes'])) {
					$scopes = $config['scopes'];
				}
			}
			$correctLevel = (
				empty($levels) ||
				in_array($level, $levels)
			);
			$inScope = (
				empty($scopes) ||
				count(array_intersect((array)$scope, $scopes)) > 0
			);
			if ($correctLevel && $inScope) {
				$logger->write($level, $message);
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
 *    See Cake\Log\Log::config() for more information on logging scopes.
 * @return boolean Success
 */
	public static function emergency($message, $scope = array()) {
		return static::write(static::$_levelMap['emergency'], $message, $scope);
	}

/**
 * Convenience method to log alert messages
 *
 * @param string $message log message
 * @param string|array $scope The scope(s) a log message is being created in.
 *    See Cake\Log\Log::config() for more information on logging scopes.
 * @return boolean Success
 */
	public static function alert($message, $scope = array()) {
		return static::write(static::$_levelMap['alert'], $message, $scope);
	}

/**
 * Convenience method to log critical messages
 *
 * @param string $message log message
 * @param string|array $scope The scope(s) a log message is being created in.
 *    See Cake\Log\Log::config() for more information on logging scopes.
 * @return boolean Success
 */
	public static function critical($message, $scope = array()) {
		return static::write(static::$_levelMap['critical'], $message, $scope);
	}

/**
 * Convenience method to log error messages
 *
 * @param string $message log message
 * @param string|array $scope The scope(s) a log message is being created in.
 *    See Cake\Log\Log::config() for more information on logging scopes.
 * @return boolean Success
 */
	public static function error($message, $scope = array()) {
		return static::write(static::$_levelMap['error'], $message, $scope);
	}

/**
 * Convenience method to log warning messages
 *
 * @param string $message log message
 * @param string|array $scope The scope(s) a log message is being created in.
 *    See Cake\Log\Log::config() for more information on logging scopes.
 * @return boolean Success
 */
	public static function warning($message, $scope = array()) {
		return static::write(static::$_levelMap['warning'], $message, $scope);
	}

/**
 * Convenience method to log notice messages
 *
 * @param string $message log message
 * @param string|array $scope The scope(s) a log message is being created in.
 *    See Cake\Log\Log::config() for more information on logging scopes.
 * @return boolean Success
 */
	public static function notice($message, $scope = array()) {
		return static::write(static::$_levelMap['notice'], $message, $scope);
	}

/**
 * Convenience method to log debug messages
 *
 * @param string $message log message
 * @param string|array $scope The scope(s) a log message is being created in.
 *    See Cake\Log\Log::config() for more information on logging scopes.
 * @return boolean Success
 */
	public static function debug($message, $scope = array()) {
		return static::write(static::$_levelMap['debug'], $message, $scope);
	}

/**
 * Convenience method to log info messages
 *
 * @param string $message log message
 * @param string|array $scope The scope(s) a log message is being created in.
 *    See Cake\Log\Log::config() for more information on logging scopes.
 * @return boolean Success
 */
	public static function info($message, $scope = array()) {
		return static::write(static::$_levelMap['info'], $message, $scope);
	}

}
