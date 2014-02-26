<?php
/**
 * Database Session save handler. Allows saving session information into a model.
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
 * @since         CakePHP(tm) v 2.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */

namespace Cake\Network\Session;

use Cake\Core\Configure;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use SessionHandlerInterface;

/**
 * DatabaseSession provides methods to be used with Session.
 *
 */
class DatabaseSession implements SessionHandlerInterface {

/**
 * Reference to the table handling the session data
 *
 * @var Model
 */
	protected $_table;

/**
 * Number of seconds to mark the session as expired
 *
 * @var integer
 */
	protected $_timeout;

/**
 * Constructor. Looks at Session configuration information and
 * sets up the session model.
 *
 */
	public function __construct() {
		$modelAlias = Configure::read('Session.handler.model');

		if (empty($modelAlias)) {
			$config = TableRegistry::exists('Sessions') ? [] : ['table' => 'cake_sessions'];
			$this->_table = TableRegistry::get('Sessions', $config);
		} else {
			$this->_table = TableRegistry::get($modelAlias);
		}

		$this->_timeout = Configure::read('Session.timeout') * 60;
	}

/**
 * Method called on open of a database session.
 *
 * @param string $savePath The path where to store/retrieve the session.
 * @param string $name The session name.
 * @return boolean Success
 */
	public function open($savePath, $name) {
		return true;
	}

/**
 * Method called on close of a database session.
 *
 * @return boolean Success
 */
	public function close() {
		return true;
	}

/**
 * Method used to read from a database session.
 *
 * @param integer|string $id The key of the value to read
 * @return mixed The value of the key or false if it does not exist
 */
	public function read($id) {
		$result = $this->_table
			->find('all')
			->select(['data'])
			->where([$this->_table->primaryKey() => $id])
			->hydrate(false)
			->first();

		if (empty($result)) {
			return false;
		}

		return $result['data'];
	}

/**
 * Helper function called on write for database sessions.
 *
 * @param integer $id ID that uniquely identifies session in database
 * @param mixed $data The value of the data to be saved.
 * @return boolean True for successful write, false otherwise.
 */
	public function write($id, $data) {
		if (!$id) {
			return false;
		}
		$expires = time() + $this->_timeout;
		$record = compact('data', 'expires');
		$record[$this->_table->primaryKey()] = $id;
		$result = $this->_table->save(new Entity($record));
		if ($result) {
			return $result->toArray();
		}
		return false;
	}

/**
 * Method called on the destruction of a database session.
 *
 * @param integer $id ID that uniquely identifies session in database
 * @return boolean True for successful delete, false otherwise.
 */
	public function destroy($id) {
		return $this->_table->delete(new Entity([$this->_table->primaryKey() => $id]));
	}

/**
 * Helper function called on gc for database sessions.
 *
 * @param string $maxlifetime Sessions that have not updated for the last maxlifetime seconds will be removed.
 * @return boolean True on success, false on failure.
 */
	public function gc($maxlifetime) {
		return $this->_table->deleteAll(['expires <' => time() - $maxlifetime]);
	}

}
