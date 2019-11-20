<?php
/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         0.2.9
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Log;

use Cake\Core\StaticConfigTrait;
use Cake\Log\Engine\BaseLog;
use InvalidArgumentException;

/**
 * Logs messages to configured Log adapters. One or more adapters
 * can be configured using Cake Logs's methods. If you don't
 * configure any adapters, and write to Log, the messages will be
 * ignored.
 *
 * ### Configuring Log adapters
 *
 * You can configure log adapters in your applications `config/app.php` file.
 * A sample configuration would look like:
 *
 * ```
 * Log::setConfig('my_log', ['className' => 'FileLog']);
 * ```
 *
 * You can define the className as any fully namespaced classname or use a short hand
 * classname to use loggers in the `App\Log\Engine` & `Cake\Log\Engine` namespaces.
 * You can also use plugin short hand to use logging classes provided by plugins.
 *
 * Log adapters are required to implement `Psr\Log\LoggerInterface`, and there is a
 * built-in base class (`Cake\Log\Engine\BaseLog`) that can be used for custom loggers.
 *
 * Outside of the `className` key, all other configuration values will be passed to the
 * logging adapter's constructor as an array.
 *
 * ### Logging levels
 *
 * When configuring loggers, you can set which levels a logger will handle.
 * This allows you to disable debug messages in production for example:
 *
 * ```
 * Log::setConfig('default', [
 *     'className' => 'File',
 *     'path' => LOGS,
 *     'levels' => ['error', 'critical', 'alert', 'emergency']
 * ]);
 * ```
 *
 * The above logger would only log error messages or higher. Any
 * other log messages would be discarded.
 *
 * ### Logging scopes
 *
 * When configuring loggers you can define the active scopes the logger
 * is for. If defined, only the listed scopes will be handled by the
 * logger. If you don't define any scopes an adapter will catch
 * all scopes that match the handled levels.
 *
 * ```
 * Log::setConfig('payments', [
 *     'className' => 'File',
 *     'scopes' => ['payment', 'order']
 * ]);
 * ```
 *
 * The above logger will only capture log entries made in the
 * `payment` and `order` scopes. All other scopes including the
 * undefined scope will be ignored.
 *
 * ### Writing to the log
 *
 * You write to the logs using Log::write(). See its documentation for more information.
 *
 * ### Logging Levels
 *
 * By default Cake Log supports all the log levels defined in
 * RFC 5424. When logging messages you can either use the named methods,
 * or the correct constants with `write()`:
 *
 * ```
 * Log::error('Something horrible happened');
 * Log::write(LOG_ERR, 'Something horrible happened');
 * ```
 *
 * ### Logging scopes
 *
 * When logging messages and configuring log adapters, you can specify
 * 'scopes' that the logger will handle. You can think of scopes as subsystems
 * in your application that may require different logging setups. For
 * example in an e-commerce application you may want to handle logged errors
 * in the cart and ordering subsystems differently than the rest of the
 * application. By using scopes you can control logging for each part
 * of your application and also use standard log levels.
 */
class Log
{
    use StaticConfigTrait {
        setConfig as protected _setConfig;
    }

    /**
     * An array mapping url schemes to fully qualified Log engine class names
     *
     * @var string[]
     */
    protected static $_dsnClassMap = [
        'console' => 'Cake\Log\Engine\ConsoleLog',
        'file' => 'Cake\Log\Engine\FileLog',
        'syslog' => 'Cake\Log\Engine\SyslogLog',
    ];

    /**
     * Internal flag for tracking whether or not configuration has been changed.
     *
     * @var bool
     */
    protected static $_dirtyConfig = false;

    /**
     * LogEngineRegistry class
     *
     * @var \Cake\Log\LogEngineRegistry|null
     */
    protected static $_registry;

    /**
     * Handled log levels
     *
     * @var string[]
     */
    protected static $_levels = [
        'emergency',
        'alert',
        'critical',
        'error',
        'warning',
        'notice',
        'info',
        'debug',
    ];

    /**
     * Log levels as detailed in RFC 5424
     * https://tools.ietf.org/html/rfc5424
     *
     * @var array
     */
    protected static $_levelMap = [
        'emergency' => LOG_EMERG,
        'alert' => LOG_ALERT,
        'critical' => LOG_CRIT,
        'error' => LOG_ERR,
        'warning' => LOG_WARNING,
        'notice' => LOG_NOTICE,
        'info' => LOG_INFO,
        'debug' => LOG_DEBUG,
    ];

    /**
     * Initializes registry and configurations
     *
     * @return void
     */
    protected static function _init()
    {
        if (empty(static::$_registry)) {
            static::$_registry = new LogEngineRegistry();
        }
        if (static::$_dirtyConfig) {
            static::_loadConfig();
        }
        static::$_dirtyConfig = false;
    }

    /**
     * Load the defined configuration and create all the defined logging
     * adapters.
     *
     * @return void
     */
    protected static function _loadConfig()
    {
        foreach (static::$_config as $name => $properties) {
            if (isset($properties['engine'])) {
                $properties['className'] = $properties['engine'];
            }
            if (!static::$_registry->has($name)) {
                static::$_registry->load($name, $properties);
            }
        }
    }

    /**
     * Reset all the connected loggers. This is useful to do when changing the logging
     * configuration or during testing when you want to reset the internal state of the
     * Log class.
     *
     * Resets the configured logging adapters, as well as any custom logging levels.
     * This will also clear the configuration data.
     *
     * @return void
     */
    public static function reset()
    {
        static::$_registry = null;
        static::$_config = [];
        static::$_dirtyConfig = true;
    }

    /**
     * Gets log levels
     *
     * Call this method to obtain current
     * level configuration.
     *
     * @return string[] active log levels
     */
    public static function levels()
    {
        return static::$_levels;
    }

    /**
     * This method can be used to define logging adapters for an application
     * or read existing configuration.
     *
     * To change an adapter's configuration at runtime, first drop the adapter and then
     * reconfigure it.
     *
     * Loggers will not be constructed until the first log message is written.
     *
     * ### Usage
     *
     * Setting a cache engine up.
     *
     * ```
     * Log::setConfig('default', $settings);
     * ```
     *
     * Injecting a constructed adapter in:
     *
     * ```
     * Log::setConfig('default', $instance);
     * ```
     *
     * Using a factory function to get an adapter:
     *
     * ```
     * Log::setConfig('default', function () { return new FileLog(); });
     * ```
     *
     * Configure multiple adapters at once:
     *
     * ```
     * Log::setConfig($arrayOfConfig);
     * ```
     *
     * @param string|array $key The name of the logger config, or an array of multiple configs.
     * @param array|null $config An array of name => config data for adapter.
     * @return void
     * @throws \BadMethodCallException When trying to modify an existing config.
     */
    public static function setConfig($key, $config = null)
    {
        static::_setConfig($key, $config);
        static::$_dirtyConfig = true;
    }

    /**
     * Get a logging engine.
     *
     * @param string $name Key name of a configured adapter to get.
     * @return \Cake\Log\Engine\BaseLog|false Instance of BaseLog or false if not found
     */
    public static function engine($name)
    {
        static::_init();
        if (static::$_registry->{$name}) {
            return static::$_registry->{$name};
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
     * ```
     * Log::write('warning', 'Stuff is broken here');
     * ```
     *
     * ### Using scopes
     *
     * When writing a log message you can define one or many scopes for the message.
     * This allows you to handle messages differently based on application section/feature.
     *
     * ```
     * Log::write('warning', 'Payment failed', ['scope' => 'payment']);
     * ```
     *
     * When configuring loggers you can configure the scopes a particular logger will handle.
     * When using scopes, you must ensure that the level of the message, and the scope of the message
     * intersect with the defined levels & scopes for a logger.
     *
     * ### Unhandled log messages
     *
     * If no configured logger can handle a log message (because of level or scope restrictions)
     * then the logged message will be ignored and silently dropped. You can check if this has happened
     * by inspecting the return of write(). If false the message was not handled.
     *
     * @param int|string $level The severity level of the message being written.
     *    The value must be an integer or string matching a known level.
     * @param mixed $message Message content to log
     * @param string|array $context Additional data to be used for logging the message.
     *  The special `scope` key can be passed to be used for further filtering of the
     *  log engines to be used. If a string or a numerically index array is passed, it
     *  will be treated as the `scope` key.
     *  See Cake\Log\Log::setConfig() for more information on logging scopes.
     * @return bool Success
     * @throws \InvalidArgumentException If invalid level is passed.
     */
    public static function write($level, $message, $context = [])
    {
        static::_init();
        if (is_int($level) && in_array($level, static::$_levelMap)) {
            $level = array_search($level, static::$_levelMap);
        }

        if (!in_array($level, static::$_levels)) {
            throw new InvalidArgumentException(sprintf('Invalid log level "%s"', $level));
        }

        $logged = false;
        $context = (array)$context;
        if (isset($context[0])) {
            $context = ['scope' => $context];
        }
        $context += ['scope' => []];

        foreach (static::$_registry->loaded() as $streamName) {
            $logger = static::$_registry->{$streamName};
            $levels = $scopes = null;

            if ($logger instanceof BaseLog) {
                $levels = $logger->levels();
                $scopes = $logger->scopes();
            }
            if ($scopes === null) {
                $scopes = [];
            }

            $correctLevel = empty($levels) || in_array($level, $levels);
            $inScope = $scopes === false && empty($context['scope']) || $scopes === [] ||
                is_array($scopes) && array_intersect((array)$context['scope'], $scopes);

            if ($correctLevel && $inScope) {
                $logger->log($level, $message, $context);
                $logged = true;
            }
        }

        return $logged;
    }

    /**
     * Convenience method to log emergency messages
     *
     * @param string $message log message
     * @param string|array $context Additional data to be used for logging the message.
     *  The special `scope` key can be passed to be used for further filtering of the
     *  log engines to be used. If a string or a numerically index array is passed, it
     *  will be treated as the `scope` key.
     *  See Cake\Log\Log::setConfig() for more information on logging scopes.
     * @return bool Success
     */
    public static function emergency($message, $context = [])
    {
        return static::write(__FUNCTION__, $message, $context);
    }

    /**
     * Convenience method to log alert messages
     *
     * @param string $message log message
     * @param string|array $context Additional data to be used for logging the message.
     *  The special `scope` key can be passed to be used for further filtering of the
     *  log engines to be used. If a string or a numerically index array is passed, it
     *  will be treated as the `scope` key.
     *  See Cake\Log\Log::setConfig() for more information on logging scopes.
     * @return bool Success
     */
    public static function alert($message, $context = [])
    {
        return static::write(__FUNCTION__, $message, $context);
    }

    /**
     * Convenience method to log critical messages
     *
     * @param string $message log message
     * @param string|array $context Additional data to be used for logging the message.
     *  The special `scope` key can be passed to be used for further filtering of the
     *  log engines to be used. If a string or a numerically index array is passed, it
     *  will be treated as the `scope` key.
     *  See Cake\Log\Log::setConfig() for more information on logging scopes.
     * @return bool Success
     */
    public static function critical($message, $context = [])
    {
        return static::write(__FUNCTION__, $message, $context);
    }

    /**
     * Convenience method to log error messages
     *
     * @param string $message log message
     * @param string|array $context Additional data to be used for logging the message.
     *  The special `scope` key can be passed to be used for further filtering of the
     *  log engines to be used. If a string or a numerically index array is passed, it
     *  will be treated as the `scope` key.
     *  See Cake\Log\Log::setConfig() for more information on logging scopes.
     * @return bool Success
     */
    public static function error($message, $context = [])
    {
        return static::write(__FUNCTION__, $message, $context);
    }

    /**
     * Convenience method to log warning messages
     *
     * @param string $message log message
     * @param string|array $context Additional data to be used for logging the message.
     *  The special `scope` key can be passed to be used for further filtering of the
     *  log engines to be used. If a string or a numerically index array is passed, it
     *  will be treated as the `scope` key.
     *  See Cake\Log\Log::setConfig() for more information on logging scopes.
     * @return bool Success
     */
    public static function warning($message, $context = [])
    {
        return static::write(__FUNCTION__, $message, $context);
    }

    /**
     * Convenience method to log notice messages
     *
     * @param string $message log message
     * @param string|array $context Additional data to be used for logging the message.
     *  The special `scope` key can be passed to be used for further filtering of the
     *  log engines to be used. If a string or a numerically index array is passed, it
     *  will be treated as the `scope` key.
     *  See Cake\Log\Log::setConfig() for more information on logging scopes.
     * @return bool Success
     */
    public static function notice($message, $context = [])
    {
        return static::write(__FUNCTION__, $message, $context);
    }

    /**
     * Convenience method to log debug messages
     *
     * @param string $message log message
     * @param string|array $context Additional data to be used for logging the message.
     *  The special `scope` key can be passed to be used for further filtering of the
     *  log engines to be used. If a string or a numerically index array is passed, it
     *  will be treated as the `scope` key.
     *  See Cake\Log\Log::setConfig() for more information on logging scopes.
     * @return bool Success
     */
    public static function debug($message, $context = [])
    {
        return static::write(__FUNCTION__, $message, $context);
    }

    /**
     * Convenience method to log info messages
     *
     * @param string $message log message
     * @param string|array $context Additional data to be used for logging the message.
     *  The special `scope` key can be passed to be used for further filtering of the
     *  log engines to be used. If a string or a numerically index array is passed, it
     *  will be treated as the `scope` key.
     *  See Cake\Log\Log::setConfig() for more information on logging scopes.
     * @return bool Success
     */
    public static function info($message, $context = [])
    {
        return static::write(__FUNCTION__, $message, $context);
    }
}
