<?php
/**
 * CakeLogInterface
 *
 * PHP 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2011, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2011, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       Cake.Log
 * @since         CakePHP(tm) v 2.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
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