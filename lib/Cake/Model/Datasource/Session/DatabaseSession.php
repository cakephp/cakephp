<?php
/**
 * Database Session save handler.  Allows saving session information into a model.
 *
 * PHP 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       cake.libs
 * @since         CakePHP(tm) v 2.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
/**
 * DatabaseSession provides methods to be used with CakeSession.
 *
 * @package cake.libs.session
 */
class DatabaseSession implements CakeSessionHandlerInterface {

/**
 * Constructor.  Looks at Session configuration information and 
 * sets up the session model.
 *
 * @return void
 */
	function __construct() {
		$modelName = Configure::read('Session.handler.model');
		$database = Configure::read('Session.handler.database');
		$table = Configure::read('Session.handler.table');

		if (empty($database)) {
			$database = 'default';
		}
		$settings = array(
			'class' => 'Session',
			'alias' => 'Session',
			'table' => 'cake_sessions',
			'ds' => $database
		);
		if (!empty($modelName)) {
			$settings['class'] = $modelName;
		}
		if (!empty($table)) {
			$settings['table'] = $table;
		}
		ClassRegistry::init($settings);
	}

/**
 * Method called on open of a database session.
 *
 * @return boolean Success
 * @access private
 */
	public function open() {
		return true;
	}

/**
 * Method called on close of a database session.
 *
 * @return boolean Success
 * @access private
 */
	public function close() {
		$probability = mt_rand(1, 150);
		if ($probability <= 3) {
			DatabaseSession::gc();
		}
		return true;
	}

/**
 * Method used to read from a database session.
 *
 * @param mixed $id The key of the value to read
 * @return mixed The value of the key or false if it does not exist
 * @access private
 */
	public function read($id) {
		$model = ClassRegistry::getObject('Session');

		$row = $model->find('first', array(
			'conditions' => array($model->primaryKey => $id)
		));

		if (empty($row[$model->alias]['data'])) {
			return false;
		}

		return $row[$model->alias]['data'];
	}

/**
 * Helper function called on write for database sessions.
 *
 * @param integer $id ID that uniquely identifies session in database
 * @param mixed $data The value of the data to be saved.
 * @return boolean True for successful write, false otherwise.
 * @access private
 */
	public function write($id, $data) {
		$expires = time() + (Configure::read('Session.timeout') * 60);
		return ClassRegistry::getObject('Session')->save(compact('id', 'data', 'expires'));
	}

/**
 * Method called on the destruction of a database session.
 *
 * @param integer $id ID that uniquely identifies session in database
 * @return boolean True for successful delete, false otherwise.
 * @access private
 */
	public function destroy($id) {
		return ClassRegistry::getObject('Session')->delete($id);
	}

/**
 * Helper function called on gc for database sessions.
 *
 * @param integer $expires Timestamp (defaults to current time)
 * @return boolean Success
 * @access private
 */
	public function gc($expires = null) {
		if (!$expires) {
			$expires = time();
		}
		$model = ClassRegistry::getObject('Session');
		return $model->deleteAll(array($model->alias . ".expires <" => $expires), false, false);
	}
}
