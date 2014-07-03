<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       Cake.Model.Datasource
 * @since         CakePHP(tm) v 2.1
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */

/**
 * Interface for Session handlers. Custom session handler classes should implement
 * this interface as it allows CakeSession know how to map methods to session_set_save_handler()
 *
 * @package       Cake.Model.Datasource.Session
 */
interface CakeSessionHandlerInterface {

/**
 * Method called on open of a session.
 *
 * @return bool Success
 */
	public function open();

/**
 * Method called on close of a session.
 *
 * @return bool Success
 */
	public function close();

/**
 * Method used to read from a session.
 *
 * @param string $id The key of the value to read
 * @return mixed The value of the key or false if it does not exist
 */
	public function read($id);

/**
 * Helper function called on write for sessions.
 *
 * @param int $id ID that uniquely identifies session in database
 * @param mixed $data The value of the data to be saved.
 * @return bool True for successful write, false otherwise.
 */
	public function write($id, $data);

/**
 * Method called on the destruction of a session.
 *
 * @param int $id ID that uniquely identifies session in database
 * @return bool True for successful delete, false otherwise.
 */
	public function destroy($id);

/**
 * Run the Garbage collection on the session storage. This method should vacuum all
 * expired or dead sessions.
 *
 * @param int $expires Timestamp (defaults to current time)
 * @return bool Success
 */
	public function gc($expires = null);

}
