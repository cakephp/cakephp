<?php
/**
 * File Storage stream for Logging
 *
 * PHP 5
 *
 * CakePHP(tm) :  Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://www.cakefoundation.org/projects/info/cakephp CakePHP(tm) Project
 * @package       Cake.Log.Engine
 * @since         CakePHP(tm) v 1.3
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

App::uses('BaseLog', 'Log/Engine');
App::uses('Hash', 'Utility');

/**
 * File Storage stream for Logging.  Writes logs to different files
 * based on the type of log it is.
 *
 * @package       Cake.Log.Engine
 */
class FileLog extends BaseLog {

/**
 * Path to save log files on.
 *
 * @var string
 */
	protected $_path = null;

/**
 * Constructs a new File Logger.
 *
 * Config
 *
 * - `types` string or array, levels the engine is interested in
 * - `scopes` string or array, scopes the engine is interested in
 * - `file` log file name
 * - `path` the path to save logs on.
 *
 * @param array $options Options for the FileLog, see above.
 */
	public function __construct($config = array()) {
		parent::__construct($config);
		$config = Hash::merge(array(
			'path' => LOGS,
			'file' => null,
			'types' => null,
			'scopes' => array(),
			), $this->_config);
		$config = $this->config($config);
		$this->_path = $config['path'];
		$this->_file = $config['file'];
		if (!empty($this->_file) && !preg_match('/\.log$/', $this->_file)) {
			$this->_file .= '.log';
		}
	}

/**
 * Implements writing to log files.
 *
 * @param string $type The type of log you are making.
 * @param string $message The message you want to log.
 * @return boolean success of write.
 */
	public function write($type, $message) {
		$debugTypes = array('notice', 'info', 'debug');

		if (!empty($this->_file)) {
			$filename = $this->_path . $this->_file;
		} elseif ($type == 'error' || $type == 'warning') {
			$filename = $this->_path . 'error.log';
		} elseif (in_array($type, $debugTypes)) {
			$filename = $this->_path . 'debug.log';
		} elseif (in_array($type, $this->_config['scopes'])) {
			$filename = $this->_path . $this->_file;
		} else {
			$filename = $this->_path . $type . '.log';
		}
		$output = date('Y-m-d H:i:s') . ' ' . ucfirst($type) . ': ' . $message . "\n";
		return file_put_contents($filename, $output, FILE_APPEND);
	}

}
