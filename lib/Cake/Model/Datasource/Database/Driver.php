<?php

namespace Cake\Model\Datasource\Database;

/**
 * Represents a database diver containing all specificities for
 * a database engine including its SQL dialect
 *
 **/
abstract class Driver {

/**
 * Establishes a connection to the database server
 *
 * @param array $config configuration to be used for creating connection
 * @return boolean true con success
 **/
	public abstract function connect(array $config);

/**
 * Disconnects from database server
 *
 * @return void
 **/
	public abstract function disconnect();

/**
 * Returns whether php is able to use this driver for connecting to database
 *
 * @return boolean true if it is valid to use this driver
 **/
	public abstract function enabled();

/**
 * Prepares a sql statement to be executed
 *
 * @param string $sql
 * @return Cake\Model\Datasource\Database\Statement
 **/
	public abstract function prepare($sql);

/**
 * Starts a transaction
 *
 * @return boolean true on success, false otherwise
 **/
	public abstract function beginTransaction();

/**
 * Commits a transaction
 *
 * @return boolean true on success, false otherwise
 **/
	public abstract function commitTransaction();

/**
 * Rollsback a transaction
 *
 * @return boolean true on success, false otherwise
 **/
	public abstract function rollbackTransaction();


/**
 * Returns whether this driver supports save points for nested transactions
 *
 * @return boolean true if save points are supported, false otherwise
 **/
	public function supportsSavePoints() {
		return true;
	}

/**
 * Returns a SQL snippet for creating a new transaction savepoint
 *
 * @param string save point name
 * @return string
 **/
	public function savePointSQL($name) {
		return 'SAVEPOINT LEVEL' . $name;
	}

/**
 * Returns a SQL snippet for releasing a previously created save point
 *
 * @param string save point name
 * @return string
 **/
	public function releaseSavePointSQL($name) {
		return 'RELEASE SAVEPOINT LEVEL' . $name;
	}

/**
 * Returns a SQL snippet for rollbacking a previously created save point
 *
 * @param string save point name
 * @return string
 **/
	public function rollbackSavePointSQL($name) {
		return 'ROLLBACK TO SAVEPOINT LEVEL' . $name;
	}

/**
 * Returns a value in a safe representation to be used in a query string
 *
 * @return string
 **/
	public abstract function quote($value, $type);
}
