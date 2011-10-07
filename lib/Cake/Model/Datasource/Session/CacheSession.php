<?php
/**
 * Cache Session save handler.  Allows saving session information into Cache.
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
 * @package       Cake.Model.Datasource.Session
 * @since         CakePHP(tm) v 2.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

App::uses('Cache', 'Cache');

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
 * @return boolean Success
 */
	public function open() {
		return true;
	}

/**
 * Method called on close of a database session.
 *
 * @return boolean Success
 */
	public function close() {
		$probability = mt_rand(1, 150);
		if ($probability <= 3) {
			Cache::gc();
		}
		return true;
	}

/**
 * Method used to read from a database session.
 *
 * @param mixed $id The key of the value to read
 * @return mixed The value of the key or false if it does not exist
 */
	public function read($id) {
		return Cache::read($id, Configure::read('Session.handler.config'));
	}

/**
 * Helper function called on write for database sessions.
 *
 * @param integer $id ID that uniquely identifies session in database
 * @param mixed $data The value of the data to be saved.
 * @return boolean True for successful write, false otherwise.
 */
	public function write($id, $data) {
		return Cache::write($id, $data, Configure::read('Session.handler.config'));
	}

/**
 * Method called on the destruction of a database session.
 *
 * @param integer $id ID that uniquely identifies session in database
 * @return boolean True for successful delete, false otherwise.
 */
	public function destroy($id) {
		return Cache::delete($id, Configure::read('Session.handler.config'));
	}

/**
 * Helper function called on gc for database sessions.
 *
 * @param integer $expires Timestamp (defaults to current time)
 * @return boolean Success
 */
	public function gc($expires = null) {
		return Cache::gc();
	}

/**
 * Closes the session before the objects handling it become unavailable
 *
 * @return void
 */
	public function __destruct() {
		session_write_close();
	}
}