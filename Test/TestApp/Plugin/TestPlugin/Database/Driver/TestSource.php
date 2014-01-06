<?php
namespace TestPlugin\Database\Driver;

use Cake\Database\Driver;

class TestSource extends Driver {

/**
 * Establishes a connection to the database server
 *
 * @return boolean true con success
 */
	public function connect() {
	}

/**
 * Disconnects from database server
 *
 * @return void
 */
	public function disconnect() {
	}

/**
 * Returns correct connection resource or object that is internally used
 * If first argument is passed,
 *
 * @return void
 */
	public function connection($connection = null) {
	}

/**
 * Returns whether php is able to use this driver for connecting to database
 *
 * @return boolean true if it is valid to use this driver
 */
	public function enabled() {
		return true;
	}

/**
 * Prepares a sql statement to be executed
 *
 * @param string $sql
 * @return Cake\Database\Statement
 */
	public function prepare($sql) {
	}

/**
 * Starts a transaction
 *
 * @return boolean true on success, false otherwise
 */
	public function beginTransaction() {
	}

/**
 * Commits a transaction
 *
 * @return boolean true on success, false otherwise
 */
	public function commitTransaction() {
	}

/**
 * Rollsback a transaction
 *
 * @return boolean true on success, false otherwise
 */
	public function rollbackTransaction() {
	}

/**
 * Returns a value in a safe representation to be used in a query string
 *
 * @return string
 */
	public function quote($value, $type) {
	}

}
