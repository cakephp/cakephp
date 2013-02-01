<?php

namespace Cake\Model\Datasource\Database\Driver;

use Cake\Model\Datasource\Database\Statement;
use PDO;

trait PDODriver {

/**
 * Establishes a connection to the databse server
 *
 * @param array $config configuration to be used for creating connection
 * @return boolean true on success
 **/
	public function connect(array $config) {
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
 **/
	public  function connection($connection = null) {
		if ($connection !== null) {
			$this->_connection = $connection;
		}
		return $this->_connection;
	}

/**
 * Disconnects from database server
 *
 * @return void
 **/
	public function disconnect() {
		$this->_connection = null;
	}

/**
 * Prepares a sql statement to be executed
 *
 * @param string $sql
 * @return Cake\Model\Datasource\Database\Statement
 **/
	public  function prepare($sql) {
		$statement = $this->connection()->prepare($sql);
		return new Statement($statement, $this);
	}

/**
 * Starts a transaction
 *
 * @return boolean true on success, false otherwise
 **/
	public function beginTransaction() {
		return $this->connection()->beginTransaction();
	}

/**
 * Commits a transaction
 *
 * @return boolean true on success, false otherwise
 **/
	public function commitTransaction() {
		return $this->connection()->commit();
	}

/**
 * Rollsback a transaction
 *
 * @return boolean true on success, false otherwise
 **/
	public function rollbackTransaction() {
		return $this->connection()->rollback();
	}

/**
 * Returns a value in a safe representation to be used in a query string
 *
 * @return string
 **/
	public function quote($value, $type) {
		return $this->connection()->quote($value, $type);
	}

/**
 * Returns last id generated for a table or sequence in database
 *
 * @param string $table table name or sequence to get last insert value from
 * @return string|integer
 **/
	public function lastInsertId($table = null) {
		return $this->connection()->lastInsertId();
	}

/**
 * Checks if the driver supports quoting, as PDO_ODBC does not support it.
 *
 * @return boolean
 **/
	public function supportsQuoting() {
		return $this->connection()->getAttribute(PDO::ATTR_DRIVER_NAME) !== 'odbc';
	}

}
