<?php
/**
 * PHP Version 5.4
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
 * @package       Cake.Model
 * @since         CakePHP(tm) v 3.0.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
namespace Cake\Database\Driver;

use Cake\Database\Statement\PDOStatement;
use PDO;

trait PDODriverTrait {

/**
 * Instance of PDO.
 *
 * @var \PDO
 */
	protected $_connection;

/**
 * Establishes a connection to the databse server
 *
 * @param array $config configuration to be used for creating connection
 * @return boolean true on success
 */
	protected function _connect(array $config) {
		$connection = new PDO(
			$config['dsn'],
			$config['login'],
			$config['password'],
			$config['flags']
		);
		$this->connection($connection);
		return true;
	}

/**
 * Returns correct connection resource or object that is internally used
 * If first argument is passed, it will set internal conenction object or
 * result to the value passed
 *
 * @return mixed connection object used internally
 */
	public function connection($connection = null) {
		if ($connection !== null) {
			$this->_connection = $connection;
		}
		return $this->_connection;
	}

/**
 * Disconnects from database server
 *
 * @return void
 */
	public function disconnect() {
		$this->_connection = null;
	}

/**
 * Prepares a sql statement to be executed
 *
 * @param string $sql
 * @return Cake\Database\Statement
 */
	public function prepare($sql) {
		$this->connect();
		$statement = $this->_connection->prepare($sql);
		return new PDOStatement($statement, $this);
	}

/**
 * Starts a transaction
 *
 * @return boolean true on success, false otherwise
 */
	public function beginTransaction() {
		$this->connect();
		return $this->_connection->beginTransaction();
	}

/**
 * Commits a transaction
 *
 * @return boolean true on success, false otherwise
 */
	public function commitTransaction() {
		$this->connect();
		return $this->_connection->commit();
	}

/**
 * Rollsback a transaction
 *
 * @return boolean true on success, false otherwise
 */
	public function rollbackTransaction() {
		$this->connect();
		return $this->_connection->rollback();
	}

/**
 * Returns a value in a safe representation to be used in a query string
 *
 * @return string
 */
	public function quote($value, $type) {
		$this->connect();
		return $this->_connection->quote($value, $type);
	}

/**
 * Returns last id generated for a table or sequence in database
 *
 * @param string $table table name or sequence to get last insert value from
 * @return string|integer
 */
	public function lastInsertId($table = null) {
		$this->connect();
		return $this->_connection->lastInsertId();
	}

/**
 * Checks if the driver supports quoting, as PDO_ODBC does not support it.
 *
 * @return boolean
 */
	public function supportsQuoting() {
		$this->connect();
		return $this->_connection->getAttribute(PDO::ATTR_DRIVER_NAME) !== 'odbc';
	}

}
