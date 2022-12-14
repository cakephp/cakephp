<?php
declare(strict_types=1);

/**
 * CakePHP(tm) :  Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakefoundation.org CakePHP(tm) Project
 * @since         2.4.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Log\Engine;

use Cake\Log\Formatter\DefaultFormatter;
use Stringable;

/**
 * Syslog stream for Logging. Writes logs to the system logger
 */
class SyslogLog extends BaseLog
{
    /**
     * Default config for this class
     *
     * By default messages are formatted as:
     * level: message
     *
     * To override the log format (e.g. to add your own info) define the format key when configuring
     * this logger
     *
     * If you wish to include a prefix to all messages, for instance to identify the
     * application or the web server, then use the prefix option. Please keep in mind
     * the prefix is shared by all streams using syslog, as it is dependent of
     * the running process. For a local prefix, to be used only by one stream, you
     * can use the format key.
     *
     * ### Example:
     *
     * ```
     *  Log::config('error', ]
     *      'engine' => 'Syslog',
     *      'levels' => ['emergency', 'alert', 'critical', 'error'],
     *      'prefix' => 'Web Server 01'
     *  ]);
     * ```
     *
     * @var array<string, mixed>
     */
    protected array $_defaultConfig = [
        'levels' => [],
        'scopes' => [],
        'flag' => LOG_ODELAY,
        'prefix' => '',
        'facility' => LOG_USER,
        'formatter' => [
            'className' => DefaultFormatter::class,
            'includeDate' => false,
        ],
    ];

    /**
     * Used to map the string names back to their LOG_* constants
     *
     * @var array<int>
     */
    protected array $_levelMap = [
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
     * Whether the logger connection is open or not
     *
     * @var bool
     */
    protected bool $_open = false;

    /**
     * Writes a message to syslog
     *
     * Map the $level back to a LOG_ constant value, split multi-line messages into multiple
     * log messages, pass all messages through the format defined in the configuration
     *
     * @param mixed $level The severity level of log you are making.
     * @param \Stringable|string $message The message you want to log.
     * @param array $context Additional information about the logged message
     * @return void
     * @see \Cake\Log\Log::$_levels
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingNativeTypeHint
     */
    public function log($level, Stringable|string $message, array $context = []): void
    {
        if (!$this->_open) {
            $config = $this->_config;
            $this->_open($config['prefix'], $config['flag'], $config['facility']);
            $this->_open = true;
        }

        $priority = LOG_DEBUG;
        if (isset($this->_levelMap[$level])) {
            $priority = $this->_levelMap[$level];
        }

        $lines = explode("\n", $this->interpolate($message, $context));
        foreach ($lines as $line) {
            $this->_write($priority, $this->formatter->format($level, $line, $context));
        }
    }

    /**
     * Extracts the call to openlog() in order to run unit tests on it. This function
     * will initialize the connection to the system logger
     *
     * @param string $ident the prefix to add to all messages logged
     * @param int $options the options flags to be used for logged messages
     * @param int $facility the stream or facility to log to
     * @return void
     */
    protected function _open(string $ident, int $options, int $facility): void
    {
        openlog($ident, $options, $facility);
    }

    /**
     * Extracts the call to syslog() in order to run unit tests on it. This function
     * will perform the actual write in the system logger
     *
     * @param int $priority Message priority.
     * @param string $message Message to log.
     * @return bool
     */
    protected function _write(int $priority, string $message): bool
    {
        return syslog($priority, $message);
    }

    /**
     * Closes the logger connection
     */
    public function __destruct()
    {
        closelog();
    }
}
