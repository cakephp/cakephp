<?php
/**
 * File Storage stream for Logging
 *
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) :  Rapid Development Framework (http://www.cakephp.org)
 * Copyright 2005-2008, Cake Software Foundation, Inc. (http://www.cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @filesource
 * @copyright     Copyright 2005-2008, Cake Software Foundation, Inc. (http://www.cakefoundation.org)
 * @link          http://www.cakefoundation.org/projects/info/cakephp CakePHP(tm) Project
 * @package       cake
 * @subpackage    cake.cake.libs.log
 * @since         CakePHP(tm) v 1.3
 * @license       http://www.opensource.org/licenses/mit-license.php The MIT License
 */

/**
 * File Storage stream for Logging
 *
 * @package       cake
 * @subpackage    cake.cake.libs.log
 */
class FileLog {
/**
 * Implements writing to log files.
 *
 * @return void
 **/
	function write($type, $message) {
		$levels = array(
			LOG_WARNING => 'warning',
			LOG_NOTICE => 'notice',
			LOG_INFO => 'info',
			LOG_DEBUG => 'debug',
			LOG_ERR => 'error',
			LOG_ERROR => 'error'
		);

		if (is_int($type) && isset($levels[$type])) {
			$type = $levels[$type];
		}
		if ($type == 'error' || $type == 'warning') {
			$filename = LOGS . 'error.log';
		} elseif (in_array($type, $levels)) {
			$filename = LOGS . 'debug.log';
		} else {
			$filename = LOGS . $type . '.log';
		}
		$output = date('Y-m-d H:i:s') . ' ' . ucfirst($type) . ': ' . $message . "\n";
		$log = new File($filename, true);
		if ($log->writable()) {
			return $log->append($output);
		}
	}
}