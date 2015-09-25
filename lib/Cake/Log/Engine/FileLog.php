<?php
/**
 * File Storage stream for Logging
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
 * @since         CakePHP(tm) v 1.3
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */

App::uses('BaseLog', 'Log/Engine');
App::uses('Hash', 'Utility');
App::uses('CakeNumber', 'Utility');

/**
 * File Storage stream for Logging. Writes logs to different files
 * based on the type of log it is.
 *
 * @package       Cake.Log.Engine
 */
class FileLog extends BaseLog {

/**
 * Default configuration values
 *
 * @var array
 * @see FileLog::__construct()
 */
	protected $_defaults = array(
		'path' => LOGS,
		'file' => null,
		'types' => null,
		'scopes' => array(),
		'rotate' => 10,
		'size' => 10485760, // 10MB
		'mask' => null,
	);

/**
 * Path to save log files on.
 *
 * @var string
 */
	protected $_path = null;

/**
 * Log file name
 *
 * @var string
 */
	protected $_file = null;

/**
 * Max file size, used for log file rotation.
 *
 * @var int
 */
	protected $_size = null;

/**
 * Constructs a new File Logger.
 *
 * Config
 *
 * - `types` string or array, levels the engine is interested in
 * - `scopes` string or array, scopes the engine is interested in
 * - `file` Log file name
 * - `path` The path to save logs on.
 * - `size` Used to implement basic log file rotation. If log file size
 *   reaches specified size the existing file is renamed by appending timestamp
 *   to filename and new log file is created. Can be integer bytes value or
 *   human reabable string values like '10MB', '100KB' etc.
 * - `rotate` Log files are rotated specified times before being removed.
 *   If value is 0, old versions are removed rather then rotated.
 * - `mask` A mask is applied when log files are created. Left empty no chmod
 *   is made.
 *
 * @param array $config Options for the FileLog, see above.
 */
	public function __construct($config = array()) {
		$config = Hash::merge($this->_defaults, $config);
		parent::__construct($config);
	}

/**
 * Sets protected properties based on config provided
 *
 * @param array $config Engine configuration
 * @return array
 */
	public function config($config = array()) {
		parent::config($config);

		if (!empty($config['path'])) {
			$this->_path = $config['path'];
		}
		if (Configure::read('debug') && !is_dir($this->_path)) {
			mkdir($this->_path, 0775, true);
		}

		if (!empty($config['file'])) {
			$this->_file = $config['file'];
			if (substr($this->_file, -4) !== '.log') {
				$this->_file .= '.log';
			}
		}
		if (!empty($config['size'])) {
			if (is_numeric($config['size'])) {
				$this->_size = (int)$config['size'];
			} else {
				$this->_size = CakeNumber::fromReadableSize($config['size']);
			}
		}

		return $this->_config;
	}

/**
 * Implements writing to log files.
 *
 * @param string $type The type of log you are making.
 * @param string $message The message you want to log.
 * @return bool success of write.
 */
	public function write($type, $message) {
		$output = date('Y-m-d H:i:s') . ' ' . ucfirst($type) . ': ' . $message . "\n";
		$filename = $this->_getFilename($type);
		if (!empty($this->_size)) {
			$this->_rotateFile($filename);
		}

		$pathname = $this->_path . $filename;
		if (empty($this->_config['mask'])) {
			return file_put_contents($pathname, $output, FILE_APPEND);
		}

		$exists = file_exists($pathname);
		$result = file_put_contents($pathname, $output, FILE_APPEND);
		static $selfError = false;
		if (!$selfError && !$exists && !chmod($pathname, (int)$this->_config['mask'])) {
			$selfError = true;
			trigger_error(__d(
				'cake_dev', 'Could not apply permission mask "%s" on log file "%s"',
				array($this->_config['mask'], $pathname)), E_USER_WARNING);
			$selfError = false;
		}
		return $result;
	}

/**
 * Get filename
 *
 * @param string $type The type of log.
 * @return string File name
 */
	protected function _getFilename($type) {
		$debugTypes = array('notice', 'info', 'debug');

		if (!empty($this->_file)) {
			$filename = $this->_file;
		} elseif ($type === 'error' || $type === 'warning') {
			$filename = 'error.log';
		} elseif (in_array($type, $debugTypes)) {
			$filename = 'debug.log';
		} else {
			$filename = $type . '.log';
		}

		return $filename;
	}

/**
 * Rotate log file if size specified in config is reached.
 * Also if `rotate` count is reached oldest file is removed.
 *
 * @param string $filename Log file name
 * @return mixed True if rotated successfully or false in case of error, otherwise null.
 *   Void if file doesn't need to be rotated.
 */
	protected function _rotateFile($filename) {
		$filepath = $this->_path . $filename;
		if (version_compare(PHP_VERSION, '5.3.0') >= 0) {
			clearstatcache(true, $filepath);
		} else {
			clearstatcache();
		}

		if (!file_exists($filepath) ||
			filesize($filepath) < $this->_size
		) {
			return null;
		}

		if ($this->_config['rotate'] === 0) {
			$result = unlink($filepath);
		} else {
			$result = rename($filepath, $filepath . '.' . time());
		}

		$files = glob($filepath . '.*');
		if ($files) {
			$filesToDelete = count($files) - $this->_config['rotate'];
			while ($filesToDelete > 0) {
				unlink(array_shift($files));
				$filesToDelete--;
			}
		}

		return $result;
	}

}
