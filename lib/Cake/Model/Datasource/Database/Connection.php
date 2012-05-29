<?php

namespace Cake\Model\Datasource\Database;

use PDOException,
	Cake\Model\Datasource\Database\Exception\MissingDriverException,
	Cake\Model\Datasource\Database\Exception\MissingExtensionException;

/**
 * Represents a conection with a database server
 *
 **/
class Connection {

/**
 * Contains the configuration params for this connection
 *
 * @var array
 **/
	protected $_config;

/**
 * Driver object, responsible for creating the real connection
 * and provide specific SQL dialact
 *
 * @var Cake\Model\Datasource\Database\Driver
 **/
	protected $_driver;

/**
 * Whether connection was established or not
 *
 * @var boolean
 **/
	protected $_connected = false;

/**
 * Constructor
 *
 * @param array $config configuration for conencting to database
 * @throws \Cake\Model\Datasource\Database\Exception\MissingDriverException if driver class can not be found
 * @throws  \Cake\Model\Datasource\Database\Exception\MissingExtensionException if driver cannot be used
 * @return void
 **/
	public function __construct($config) {
		$this->_config = $config;
		if (!class_exists($config['datasource'])) {
			throw new MissingDriverException(array('driver' => $config['datasource']));
		}
		$this->_driver = new $config['datasource'];

		if (!$this->_driver->enabled()) {
			throw new MissingExtensionException(array('driver' => get_class($this->_driver)));
		}
	}

/**
 * Connects to the configured databatase
 *
 * @return boolean true on success or false if already connected
 **/
	public function connect() {
		if ($this->_connected) {
			return false;
		}
		return $this->_connected = $this->_driver->connect($this->_config);
	}

/**
 * Disconnects from database server
 *
 * @return void
 **/
	public function disconnect() {
		$this->_driver->disconnect();
		$this->_connected = false;
	}

/**
 * Returns wheter connection to database server was already stablished
 *
 * @return boolean
 **/
	public function isConnected() {

	}

/**
 * Prepares a sql statement to be used multiple times
 *
 * @param string $sql
 * @return Cake\Model\Datasource\Database\Statement
 **/
	public function prepare($sql) {

	}

/**
 * Executes a query using $params for interpolating values and $types as a hint for each
 * those params
 *
 * @param string $query SQL to be executed and interpolated with $params
 * @param array $params list or associative array of params to be interpolated in $query as values
 * @param array $types list or associative array of types to be used for casting values in query
 * @return Cake\Model\Datasource\Database\Statement executed statament
 **/
	public function execute($query, array $params = array(), array $types = array()) {

	}

/**
 * Executes a SQL statament and returns the Statement object as result
 *
 * @return Cake\Model\Datasource\Database\Statement
 **/
	public function query($sql) {

	}

/**
 * Executes an INSERT query on the specified table
 *
 * @param string $table the table to update values in
 * @param array $data values to be inserted
 * @params array $types list of associative array containing the types to be used for casting
 * @return Cake\Model\Datasource\Database\Statement
 **/
	public function insert($table, array $data, array $types = array()) {

	}

/**
 * Executes an UPDATE statement on the specified table
 *
 * @param string $table the table to delete rows from
 * @param array $data values to be updated
 * @param array $conditions conditions to be set for update statement
 * @param array $types list of associative array containing the types to be used for casting
 * @return Cake\Model\Datasource\Database\Statement
 **/
	public function update($table, array $data, array $conditions = array(), $types = array()) {

	}

/**
 * Executes a DELETE  statement on the specified table
 *
 * @param string $table the table to delete rows from
 * @param array $conditions conditions to be set for delete statement
 * @return Cake\Model\Datasource\Database\Statement
 **/
	public function delete($table, $conditions = array()) {

	}

/**
 * Starts a new transaction
 *
 * @return void
 **/
	public function begin() {

	}

/**
 * Commits current transaction
 *
 * @return void
 **/

	public function commit() {

	}

/**
 * Rollsabck current transaction
 *
 * @return void
 **/
	public function rollback() {

	}

/**
 * Quotes value to be used safely in database query
 *
 * @param mixed $value
 * @param Type to be used for determining kind of quoting to perform
 * @return mixed queted value
 **/
	public function quote($value, $type = null) {

	}

/**
 * Quotes a database identifier (a column name, table name, etc..) to
 * be used safely in queries without the risk of using reserver words
 *
 * @param string $identifier
 * @return string
 **/
	public function quoteIdentifier($identifier) {

	}

/**
 * Returns last id generated for a table or sequence in database
 *
 * @param strin $table table name or sequence to get last insert value from
 * @return string|integer
 **/
	public function lastInsertId($table) {

	}

/**
 * Sets database charset for this connection
 *
 * @param string $collection the charset name
 **/
	public function charset($collation) {

	}

}
