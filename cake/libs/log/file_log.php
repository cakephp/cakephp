<?php
/**
 * File Storage stream for Logging
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) :  Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2009, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2009, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://www.cakefoundation.org/projects/info/cakephp CakePHP(tm) Project
 * @package       cake
 * @subpackage    cake.cake.libs.log
 * @since         CakePHP(tm) v 1.3
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
if (!class_exists('File')) {
	require LIBS . 'file.php';
}
/**
 * File Storage stream for Logging.  Writes logs to different files
 * based on the type of log it is.
 *
 * @package cake
 * @subpackage cake.cake.libs.log
 */
class FileLog {

/**
 * Path to save log files on.
 *
 * @var string
 */
	var $_path = null;

/**
 * Constructs a new File Logger.
 * 
 * Options
 *
 * - `path` the path to save logs on.
 *
 * @param array $options Options for the FileLog, see above.
 * @return void
 */
	function FileLog($options = array()) {
		$options += array('path' => LOGS);
		$this->_path = $options['path'];
	}

/**
 * Implements writing to log files.
 *
 * @param string $type The type of log you are making.
 * @param string $message The message you want to log.
 * @return boolean success of write.
 */
	function write($type, $message) {
		$debugTypes = array('notice', 'info', 'debug');

		if ($type == 'error' || $type == 'warning') {
			$filename = $this->_path  . 'error.log';
		} elseif (in_array($type, $debugTypes)) {
			$filename = $this->_path . 'debug.log';
		} else {
			$filename = $this->_path . $type . '.log';
		}
		$output = date('Y-m-d H:i:s') . ' ' . ucfirst($type) . ': ' . $message . "\n";
		$log = new File($filename, true);
		if ($log->writable()) {
			return $log->append($output);
		}
	}
}