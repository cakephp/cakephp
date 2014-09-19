<?php
/**
 * CakeLogInterface
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         2.0.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Log;

use Psr\Log\LoggerInterface;

/**
 * LogInterface is the interface that should be implemented
 * by all classes that are going to be used as Log streams.
 */
interface LogInterface extends LoggerInterface {

/**
 * Write method to handle writes being made to the Logger
 *
 * @param string $level The severity level of the message being written.
 *    See Cake\Log\Log::$_levels for list of possible levels.
 * @param string $message Message content to log
 * @param array $scope The scope(s) a log message is being created in.
 *    See Cake\Log\Log::config() for more information on logging scopes.
 * @return void
 */
	public function write($level, $message, $scope = []);
}
