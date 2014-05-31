<?php
/**
 * Syslog logger engine for CakePHP
 *
 * CakePHP(tm) :  Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://www.cakefoundation.org/projects/info/cakephp CakePHP(tm) Project
 * @package       Cake.Log.Engine
 * @since         CakePHP(tm) v 2.4
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */

App::uses('BaseLog', 'Log/Engine');

/**
 * Syslog stream for Logging. Writes logs to the system logger
 *
 * @package       Cake.Log.Engine
 */
class SyslogLog extends BaseLog {

/**
 *
 * By default messages are formatted as:
 * 	type: message
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
 * ## Example:
 *
 * {{{
 *	CakeLog::config('error', array(
 *		'engine' => 'Syslog',
 *		'types' => array('emergency', 'alert', 'critical', 'error'),
 *		'format' => "%s: My-App - %s",
 *		'prefix' => 'Web Server 01'
 *	));
 * }}}
 *
 * @var array
 */
	protected $_defaults = array(
		'format' => '%s: %s',
		'flag' => LOG_ODELAY,
		'prefix' => '',
		'facility' => LOG_USER
	);

/**
 *
 * Used to map the string names back to their LOG_* constants
 *
 * @var array
 */
	protected $_priorityMap = array(
		'emergency' => LOG_EMERG,
		'alert' => LOG_ALERT,
		'critical' => LOG_CRIT,
		'error' => LOG_ERR,
		'warning' => LOG_WARNING,
		'notice' => LOG_NOTICE,
		'info' => LOG_INFO,
		'debug' => LOG_DEBUG
	);

/**
 * Whether the logger connection is open or not
 *
 * @var boolean
 */
	protected $_open = false;

/**
 * Make sure the configuration contains the format parameter, by default it uses
 * the error number and the type as a prefix to the message
 *
 * @param array $config Options list.
 */
	public function __construct($config = array()) {
		$config += $this->_defaults;
		parent::__construct($config);
	}

/**
 * Writes a message to syslog
 *
 * Map the $type back to a LOG_ constant value, split multi-line messages into multiple
 * log messages, pass all messages through the format defined in the configuration
 *
 * @param string $type The type of log you are making.
 * @param string $message The message you want to log.
 * @return boolean success of write.
 */
	public function write($type, $message) {
		if (!$this->_open) {
			$config = $this->_config;
			$this->_open($config['prefix'], $config['flag'], $config['facility']);
			$this->_open = true;
		}

		$priority = LOG_DEBUG;
		if (isset($this->_priorityMap[$type])) {
			$priority = $this->_priorityMap[$type];
		}

		$messages = explode("\n", $message);
		foreach ($messages as $message) {
			$message = sprintf($this->_config['format'], $type, $message);
			$this->_write($priority, $message);
		}

		return true;
	}

/**
 * Extracts the call to openlog() in order to run unit tests on it. This function
 * will initialize the connection to the system logger
 *
 * @param string $ident the prefix to add to all messages logged
 * @param integer $options the options flags to be used for logged messages
 * @param integer $facility the stream or facility to log to
 * @return void
 */
	protected function _open($ident, $options, $facility) {
		openlog($ident, $options, $facility);
	}

/**
 * Extracts the call to syslog() in order to run unit tests on it. This function
 * will perform the actual write in the system logger
 *
 * @param integer $priority Message priority.
 * @param string $message Message to log.
 * @return boolean
 */
	protected function _write($priority, $message) {
		return syslog($priority, $message);
	}

/**
 * Closes the logger connection
 */
	public function __destruct() {
		closelog();
	}

}
