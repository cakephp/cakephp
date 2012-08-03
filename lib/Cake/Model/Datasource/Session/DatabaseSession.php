<?php
/**
 * Database Session save handler.  Allows saving session information into a model.
 *
 * PHP 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       Cake.Model.Datasource.Session
 * @since         CakePHP(tm) v 2.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

App::uses('CakeSessionHandlerInterface', 'Model/Datasource/Session');
App::uses('ClassRegistry', 'Utility');

/**
 * DatabaseSession provides methods to be used with CakeSession.
 *
 * @package       Cake.Model.Datasource.Session
 */
class DatabaseSession implements CakeSessionHandlerInterface {

/**
 * Reference to the model handling the session data
 *
 * @var Model
 */
	protected $_model;

/**
 * Number of seconds to mark the session as expired
 *
 * @var int
 */
	protected $_timeout;

/**
 * Constructor.  Looks at Session configuration information and
 * sets up the session model.
 *
 */
	public function __construct() {
		$modelName = Configure::read('Session.handler.model');

		if (empty($modelName)) {
			$settings = array(
				'class' => 'Session',
				'alias' => 'Session',
				'table' => 'cake_sessions',
			);
		} else {
			$settings = array(
				'class' => $modelName,
				'alias' => 'Session',
			);
		}
		$this->_model = ClassRegistry::init($settings);
		$this->_timeout = Configure::read('Session.timeout') * 60;
	}

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
		return true;
	}

/**
 * Method used to read from a database session.
 *
 * @param integer|string $id The key of the value to read
 * @return mixed The value of the key or false if it does not exist
 */
	public function read($id) {
		$row = $this->_model->find('first', array(
			'conditions' => array($this->_model->primaryKey => $id)
		));

		if (empty($row[$this->_model->alias]['data'])) {
			return false;
		}

		return $row[$this->_model->alias]['data'];
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
		$record = compact('id', 'data', 'expires');
		$record[$this->_model->primaryKey] = $id;
		return $this->_model->save($record);
	}

/**
 * Method called on the destruction of a database session.
 *
 * @param integer $id ID that uniquely identifies session in database
 * @return boolean True for successful delete, false otherwise.
 */
	public function destroy($id) {
		return $this->_model->delete($id);
	}

/**
 * Helper function called on gc for database sessions.
 *
 * @param integer $expires Timestamp (defaults to current time)
 * @return boolean Success
 */
	public function gc($expires = null) {
		if (!$expires) {
			$expires = time();
		} else {
			$expires = time() - $expires;
		}
		return $this->_model->deleteAll(array($this->_model->alias . ".expires <" => $expires), false, false);
	}

}
