<?php
/**
 * CakeLogInterface
 *
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @package       Cake.Log
 * @since         CakePHP(tm) v 2.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
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
 * @param string $type Message type.
 * @param string $message Message to write.
 * @return void
 */
	public function write($type, $message);

}
