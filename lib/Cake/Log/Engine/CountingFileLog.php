<?php
/**
 * File Storage stream using counting instead of appending for Logging
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

App::uses('FileLog', 'Log/Engine');

/**
 * File Storage stream using counting instead of appending for Logging.
 * Writes logs to different files based on the configuration type.
 *
 * @package	   Cake.Log.Engine
 */
class CountingFileLog extends FileLog {

/**
 * Constructs a new Counting File Logger.
 *
 * Config
 *
 * - `file` log file name
 * - `path` the path to save logs on.
 *
 * @param array $options Options for the CountingFileLog, see above.
 */
	public function __construct(array $config = array()) {
		$config = Hash::merge(array(
			'file' => 'incremental.json.log',
			), $config);
		parent::__construct($config);
	}

/**
 * Implements writing to log files.
 *
 * @param string $type  Type of error log
 * @param string $value The message you want to log.
 * @return boolean success of write.
 */
	public function write($type, $path) {
		$useFile = $this->_path . $this->_file;
		if (is_file($useFile)) {
			$content = file_get_contents($useFile);
			$content = json_decode($content, true);
		} else {
			$content = array();
		}
		if (!is_array($content)) {
			$content = array();
		}
		$value = ((int)Hash::get($content, $path)) + 1;
		$content = Hash::insert($content, $path, $value);
		return file_put_contents($useFile, json_encode($content), LOCK_EX);
	}

}