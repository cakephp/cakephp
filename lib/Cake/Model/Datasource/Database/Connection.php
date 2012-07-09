<?php

namespace Cake\Model\Datasource\Database;

use Cake\Model\Datasource\Database\Exception\MissingConnectionException;
use Cake\Model\Datasource\Database\Exception\MissingDriverException;
use Cake\Model\Datasource\Database\Exception\MissingExtensionException;
use PDOException;

/**
 * Represents a connection with a database server
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
 * and provide specific SQL dialect
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
 * Contains how many nested transactions have been started
 *
 * @var int
 **/
	protected $_transactionLevel = 0;

/**
 * Whether a transaction is active in this connection
 *
 * @var int
 **/
	protected $_transactionStarted = false;

/**
 * Whether this connection can and should use savepoints for nested
 * transactions
 *
 * @var boolean
 **/
	protected $_useSavePoints = false;

/**
 * Constructor
 *
 * @param array $config configuration for connecting to database
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
 * Connects to the configured database
 *
 * @throws \Cake\Model\Datasource\Database\Exception\MissingConnectionException if credentials are invalid
 * @return boolean true on success or false if already connected
 **/
	public function connect() {
		if ($this->_connected) {
			return false;
		}
		try {
			return $this->_connected = $this->_driver->connect($this->_config);
		} catch(\Exception $e) {
			throw new MissingConnectionException(array('reason' => $e->getMessage()));
		}
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
 * Returns whether connection to database server was already stablished
 *
 * @return boolean
 **/
	public function isConnected() {
		return $this->_connected;
	}

/**
 * Prepares a sql statement to be executed
 *
 * @param string $sql
 * @return Cake\Model\Datasource\Database\Statement
 **/
	public function prepare($sql) {
		$this->connect();
		return $this->_driver->prepare($sql);
	}

/**
 * Executes a query using $params for interpolating values and $types as a hint for each
 * those params
 *
 * @param string $query SQL to be executed and interpolated with $params
 * @param array $params list or associative array of params to be interpolated in $query as values
 * @param array $types list or associative array of types to be used for casting values in query
 * @return Cake\Model\Datasource\Database\Statement executed statement
 **/
	public function execute($query, array $params = array(), array $types = array()) {
		$this->connect();
		if ($params) {
			$statement = $this->prepare($query);
			$this->_bindValues($statement, $params, $types);
			$result = $statement->execute();
		} else {
			$statement = $this->query($query);
		}
		return $statement;
	}

/**
 * Executes a SQL statement and returns the Statement object as result
 *
 * @return Cake\Model\Datasource\Database\Statement
 **/
	public function query($sql) {
		$this->connect();
		$statement = $this->prepare($sql);
		$statement->execute();
		return $statement;
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
		$this->connect();
		$keys = array_keys($data);
		$sql = 'INSERT INTO %s (%s) VALUES (%s)';
		$sql = sprintf(
			$sql,
			$table,
			implode(',', $keys),
			implode(',', array_fill(0, count($data), '?'))
		);
		$types = $this->_mapTypes($keys, $types);
		return $this->execute($sql, array_values($data), $types);
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
		$this->connect();
		$keys = array_keys($data);
		$conditionsKeys = array_keys($conditions);
		$sql = 'UPDATE %s SET %s %s';
		list($conditions, $params) = $this->_parseConditions($conditions);
		$sql = sprintf(
			$sql,
			$table,
			implode(', ', array_map(function($k) {return $k . ' = ?';}, $keys)),
			$conditions
		);
		if (!empty($types)) {
			$types = $this->_mapTypes($keys, $types);
			$types = array_merge($types,  $this->_mapTypes($conditionsKeys, $types));
		}
		return $this->execute($sql, array_merge(array_values($data), $params), $types);
	}

/**
 * Executes a DELETE  statement on the specified table
 *
 * @param string $table the table to delete rows from
 * @param array $conditions conditions to be set for delete statement
 * @param array $types list of associative array containing the types to be used for casting
 * @return Cake\Model\Datasource\Database\Statement
 **/
	public function delete($table, $conditions = array(), $types = array()) {
		$this->connect();
		$conditionsKeys = array_keys($conditions);
		$sql = 'DELETE FROM %s %s';
		list($conditions, $params) = $this->_parseConditions($conditions);
		$sql = sprintf(
			$sql,
			$table,
			$conditions
		);
		if (!empty($types)) {
			$types = $this->_mapTypes($conditionsKeys, $types);
		}
		return $this->execute($sql, $params, $types);
	}

/**
 * Starts a new transaction
 *
 * @return void
 **/
	public function begin() {
		$this->connect();
		if (!$this->_transactionStarted) {
			$this->_driver->beginTransaction();
			$this->_transactionLevel = 0;
			$this->_transactionStarted = true;
			return;
		}

		$this->_transactionLevel++;
		if ($this->useSavePoints()) {
			$this->createSavePoint($this->_transactionLevel);
		}
	}

/**
 * Commits current transaction
 *
 * @return boolean true on success, false otherwise
 **/
	public function commit() {
		if (!$this->_transactionStarted) {
			return false;
		}
		$this->connect();

		if ($this->_transactionLevel === 0) {
			$this->_transactionStarted = false;
			return $this->_driver->commitTransaction();
		}
		if ($this->useSavePoints()) {
			$this->releaseSavePoint($this->_transactionLevel);
		}

		$this->_transactionLevel--;
		return true;
	}

/**
 * Rollback current transaction
 *
 * @return void
 **/
	public function rollback() {
		if (!$this->_transactionStarted) {
			return false;
		}
		$this->connect();

		$useSavePoint = $this->useSavePoints();
		if ($this->_transactionLevel === 0 || !$useSavePoint) {
			$this->_transactionLevel = 0;
			$this->_transactionStarted = false;
			$this->_driver->rollbackTransaction();
			return true;
		}

		if ($useSavePoint) {
			$this->rollbackSavepoint($this->_transactionLevel--);
		}
		return true;
	}

/**
 * Returns whether this connection is using savepoints for nested transactions
 * If a boolean is passed as argument it will enable/disable the usage of savepoints
 * only if driver the allows it.
 *
 * If you are trying to enable this feature, make sure you check the return value of this
 * function to verify it was enabled successfuly
 *
 * ## Example:
 *
 * `$connection->useSavePoints(true)` Returns true if drivers supports save points, false otherwise
 * `$connection->useSavePoints(false)` Disables usage of savepoints and returns false
 * `$connection->useSavePoints()` Returns current status
 *
 * @return boolean true if enabled, false otherwise
 **/
	public function useSavePoints($enable = null) {
		if ($enable === null) {
			return $this->_useSavePoints;
		}
		if ($enable === false) {
			return $this->_useSavePoints = false;
		}

		return $this->_useSavePoints = $this->_driver->supportsSavePoints();
	}

/**
 * Creates a new save point for nested transactions
 *
 * @return void
 **/
	public function createSavePoint($name) {
		$this->connect();
		$this->execute($this->_driver->savePointSQL($name));
	}

/**
 * Releases a save point by its name
 *
 * @return void
 **/
	public function releaseSavePoint($name) {
		$this->connect();
		$this->execute($this->_driver->releaseSavePointSQL($name));
	}

/**
 * Rollsback a save point by its name
 *
 * @return void
 **/
	public function rollbackSavepoint($name) {
		$this->connect();
		$this->execute($this->_driver->rollbackSavePointSQL($name));
	}

/**
 * Quotes value to be used safely in database query
 *
 * @param mixed $value
 * @param Type to be used for determining kind of quoting to perform
 * @return mixed quoted value
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
 * @param string $table table name or sequence to get last insert value from
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

/**
 * Binds values to statement object with corresponding type
 *
 * @param \Cake\Model\Datasource\Database\Statement The statement objet to bind values to
 * @param array $params list of values to be bound
 * @param array $types list of types to be used, keys should match those in $params
 * @return void
 **/
	protected function _bindValues($statement, $params, $types) {
		if (empty($params)) {
			return;
		}

		$annonymousParams = is_int(key($params)) ? true : false;
		$offset = 1;
		foreach ($params as $index => $value) {
			$type = null;
			if (isset($types[$index])) {
				$type = $types[$index];
			}
			if ($annonymousParams) {
				$index += $offset;
			}
			$statement->bindValue($index, $value, $type);
		}
	}

/**
 * Auxiliary method to map columns to corresponding types
 *
 * Both $columns and $types should either be numeric based or string key based at
 * the same time.
 *
 * @param array $columns list or associative array of columns and parameters to be bound with types
 * @param array $types list or associative array of types
 * @return array
 **/
	protected function _mapTypes($columns, $types) {
		if (!is_int(key($types))) {
			$positons = array_intersect_key(array_flip($columns), $types);
			$types = array_intersect_key($types, $positons);
			$types = array_combine($positons, $types);
		}
		return $types;
	}

/**
 * Simple conditions parser joined by AND
 *
 * @param array conditions key value array or list of conditions to be joined
 * to construct a WHERE clause
 * @return string
 **/
	protected function _parseConditions($conditions) {
		$params = array();
		if (empty($conditions)) {
			return array('', $params);
		}
		$sql = 'WHERE %s';
		$conds = array();
		foreach ($conditions as $key => $value) {
			if (is_numeric($key)) {
				$conds[] = $value;
				continue;
			}
			$conds[] = $key . ' = ?';
			$params[] = $value;
		}
		return array(sprintf($sql, implode(' AND ', $conds)), $params);
	}

}
