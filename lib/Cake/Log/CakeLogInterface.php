<?php
/**
 * CakeLogInterface
 *
 * PHP 5
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
 * @package       Cake.Log
 * @since         CakePHP(tm) v 2.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */

/**
 * CakeLogStreamInterface is the interface that should be implemented
 * by all classes that are going to be used as Log streams.
 *
 * @package       Cake.Log
 */
interface CakeLogInterface {

/**
 * Write method to handle writes being made to the Logger
 *
 * @param string $type
 * @param string $message
 * @return void
 */
	public function write($type, $message);

}
