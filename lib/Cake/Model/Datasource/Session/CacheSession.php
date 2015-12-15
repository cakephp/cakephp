<?php
/**
 * Cache Session save handler. Allows saving session information into Cache.
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
 * @package       Cake.Model.Datasource.Session
 * @since         CakePHP(tm) v 2.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */

App::uses('Cache', 'Cache');
App::uses('CakeSessionHandlerInterface', 'Model/Datasource/Session');

/**
 * CacheSession provides method for saving sessions into a Cache engine. Used with CakeSession
 *
 * @package       Cake.Model.Datasource.Session
 * @see CakeSession for configuration information.
 */
class CacheSession implements CakeSessionHandlerInterface {

/**
 * Method called on open of a database session.
 *
 * @return bool Success
 */
	public function open() {
		return true;
	}

/**
 * Method called on close of a database session.
 *
 * @return bool Success
 */
	public function close() {
		return true;
	}

/**
 * Method used to read from a database session.
 *
 * @param string $id The key of the value to read
 * @return mixed The value of the key or false if it does not exist
 */
	public function read($id) {
		return Cache::read($id, Configure::read('Session.handler.config'));
	}

/**
 * Helper function called on write for database sessions.
 *
 * @param int $id ID that uniquely identifies session in database
 * @param mixed $data The value of the data to be saved.
 * @return bool True for successful write, false otherwise.
 */
	public function write($id, $data) {
		return Cache::write($id, $data, Configure::read('Session.handler.config'));
	}

/**
 * Method called on the destruction of a database session.
 *
 * @param int $id ID that uniquely identifies session in cache
 * @return bool True for successful delete, false otherwise.
 */
	public function destroy($id) {
		return Cache::delete($id, Configure::read('Session.handler.config'));
	}

/**
 * Helper function called on gc for cache sessions.
 *
 * @param int $expires Timestamp (defaults to current time)
 * @return bool Success
 */
	public function gc($expires = null) {
		return Cache::gc(Configure::read('Session.handler.config'), $expires);
	}

}
