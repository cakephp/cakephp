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
 * @since         CakePHP(tm) v 3.0.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
namespace Cake\Log;

/**
 * A trait providing an object short-cut method
 * to logging.
 */
trait LogTrait {

/**
 * Convenience method to write a message to Log. See Log::write()
 * for more information on writing to logs.
 *
 * @param string $msg Log message.
 * @param integer|string $level Error level.
 * @param string|array $scope The name of the log scope.
 * @return boolean Success of log write.
 */
	public function log($msg, $level = LOG_ERR, $scope = []) {
		if (!is_string($msg)) {
			$msg = print_r($msg, true);
		}
		return Log::write($level, $msg, $scope);
	}

}
