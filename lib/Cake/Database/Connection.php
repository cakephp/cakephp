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
 * @since         CakePHP(tm) v 3.0.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
namespace Cake\Database;

use Cake\Database\Exception\MissingConnectionException;
use Cake\Database\Exception\MissingDriverException;
use Cake\Database\Exception\MissingExtensionException;
use Cake\Database\Query;
use Cake\Database\Log\LoggingStatement;
use Cake\Database\Log\QueryLogger;
use Cake\Database\Log\LoggedQuery;

/**
 * Represents a connection with a database server
 */
class Connection {

	use TypeConverterTrait;

/**
 * Contains the configuration params for this connection
 *
 * @var array
 */
	protected $_config;

/**
 * Driver object, responsible for creating the real connection
 * and provide specific SQL dialect
 *
 * @var \Cake\Database\Driver
 */
	protected $_driver;

/**
 * Whether connection was established or not
 *
 * @var boolean
 */
	protected $_connected = false;

/**
 * Contains how many nested transactions have been started
 *
 * @var int
 */
	protected $_transactionLevel = 0;

/**
 * Whether a transaction is active in this connection
 *
 * @var int
 */
	protected $_transactionStarted = false;

/**
 * Whether this connection can and should use savepoints for nested
 * transactions
 *
 * @var boolean
 */
	protected $_useSavePoints = false;

/**
 * Whether to log queries generated during this connection
 *
 * @var boolean
 */
	protected $_logQueries = false;

/**
 * Logger object instance
 *
 * @var QueryLogger
 */
	protected $_logger = null;

/**
 * Constructor
 *
 * @param array $config configuration for connecting to database
 * @throws MissingDriverException if driver class can not be found
 * @throws MissingExtensionException if driver cannot be used
 * @return self
 */
	public function __construct($config) {
		$this->_config = $config;
		if (!class_exists($config['datasource'])) {
			throw new MissingDriverException(['driver' => $config['datasource']]);
		}

		$this->driver($config['datasource']);
		if (!$this->_driver->enabled()) {
			throw new MissingExtensionException(['driver' => get_class($this->_driver)]);
		}

		if (!empty($config['log'])) {
			$this->logQueries($config['log']);
		}
	}

/**
 * Sets the driver instance. If an string is passed it will be treated
 * as a class name and will be instantiated.
 *
 * If no params are passed it will return the current driver instance
 *
 * @param string|Driver $driver
 * @return Driver
 */
	public function driver($driver = null) {
		if ($driver === null) {
			return $this->_driver;
		}
		if (is_string($driver)) {
			$driver = new $driver;
		}
		return $this->_driver = $driver;
	}

/**
 * Connects to the configured database
 *
 * @throws MissingConnectionException if credentials are invalid
 * @return boolean true on success or false if already connected.
 */
	public function connect() {
		if ($this->_connected) {
			return false;
		}
		try {
			return $this->_connected = $this->_driver->connect($this->_config);
		} catch(\Exception $e) {
			throw new MissingConnectionException(['reason' => $e->getMessage()]);
		}
	}

/**
 * Disconnects from database server
 *
 * @return void
 */
	public function disconnect() {
		$this->_driver->disconnect();
		$this->_connected = false;
	}

/**
 * Returns whether connection to database server was already stablished
 *
 * @return boolean
 */
	public function isConnected() {
		return $this->_connected;
	}

/**
 * Prepares a sql statement to be executed
 *
 * @param string $sql
 * @return \Cake\Database\Statement
 */
	public function prepare($sql) {
		$this->connect();
		$statement = $this->_driver->prepare($sql);

		if ($this->_logQueries) {
			$statement = $this->_newLogger($statement);
		}

		return $statement;
	}

/**
 * Executes a query using $params for interpolating values and $types as a hint for each
 * those params
 *
 * @param string $query SQL to be executed and interpolated with $params
 * @param array $params list or associative array of params to be interpolated in $query as values
 * @param array $types list or associative array of types to be used for casting values in query
 * @return \Cake\Database\Statement executed statement
 */
	public function execute($query, array $params = [], array $types = []) {
		$this->connect();
		if ($params) {
			$statement = $this->prepare($query);
			$statement->bind($params, $types);
			$statement->execute();
		} else {
			$statement = $this->query($query);
		}
		return $statement;
	}

/**
 * Executes a SQL statement and returns the Statement object as result
 *
 * @param string $sql
 * @return \Cake\Database\Statement
 */
	public function query($sql) {
		$this->connect();
		$statement = $this->prepare($sql);
		$statement->execute();
		return $statement;
	}

/**
 * Create a new Query instance for this connection.
 *
 * @return Query
 */
	public function newQuery() {
		return new Query($this);
	}

/**
 * Executes an INSERT query on the specified table
 *
 * @param string $table the table to update values in
 * @param array $data values to be inserted
 * @param array $types list of associative array containing the types to be used for casting
 * @return \Cake\Database\Statement
 */
	public function insert($table, array $data, array $types = []) {
		$this->connect();

		$columns = array_keys($data);
		return $this->newQuery()->insert($table, $columns, $types)
			->values($data)
			->execute();
	}

/**
 * Executes an UPDATE statement on the specified table
 *
 * @param string $table the table to delete rows from
 * @param array $data values to be updated
 * @param array $conditions conditions to be set for update statement
 * @param array $types list of associative array containing the types to be used for casting
 * @return \Cake\Database\Statement
 */
	public function update($table, array $data, array $conditions = [], $types = []) {
		$this->connect();
		$columns = array_keys($data);

		return $this->newQuery()->update($table)
			->set($data, $types)
			->where($conditions, $types)
			->execute();
	}

/**
 * Executes a DELETE  statement on the specified table
 *
 * @param string $table the table to delete rows from
 * @param array $conditions conditions to be set for delete statement
 * @param array $types list of associative array containing the types to be used for casting
 * @return \Cake\Database\Statement
 */
	public function delete($table, $conditions = [], $types = []) {
		$this->connect();
		return $this->newQuery()->delete($table)
			->where($conditions, $types)
			->execute();
	}

/**
 * Starts a new transaction
 *
 * @return void
 */
	public function begin() {
		$this->connect();
		if (!$this->_transactionStarted) {
			if ($this->_logQueries) {
				$this->log('BEGIN');
			}
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
 */
	public function commit() {
		if (!$this->_transactionStarted) {
			return false;
		}
		$this->connect();

		if ($this->_transactionLevel === 0) {
			$this->_transactionStarted = false;
			if ($this->_logQueries) {
				$this->log('COMMIT');
			}
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
 * @return boolean
 */
	public function rollback() {
		if (!$this->_transactionStarted) {
			return false;
		}
		$this->connect();

		$useSavePoint = $this->useSavePoints();
		if ($this->_transactionLevel === 0 || !$useSavePoint) {
			$this->_transactionLevel = 0;
			$this->_transactionStarted = false;
			if ($this->_logQueries) {
				$this->log('ROLLBACK');
			}
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
 * function to verify it was enabled successfully
 *
 * ## Example:
 *
 * `$connection->useSavePoints(true)` Returns true if drivers supports save points, false otherwise
 * `$connection->useSavePoints(false)` Disables usage of savepoints and returns false
 * `$connection->useSavePoints()` Returns current status
 *
 * @param boolean|null $enable
 * @return boolean true if enabled, false otherwise
 */
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
 * @param string $name
 * @return void
 */
	public function createSavePoint($name) {
		$this->connect();
		$this->execute($this->_driver->savePointSQL($name));
	}

/**
 * Releases a save point by its name
 *
 * @param string $name
 * @return void
 */
	public function releaseSavePoint($name) {
		$this->connect();
		$this->execute($this->_driver->releaseSavePointSQL($name));
	}

/**
 * Rollsback a save point by its name
 *
 * @param string $name
 * @return void
 */
	public function rollbackSavepoint($name) {
		$this->connect();
		$this->execute($this->_driver->rollbackSavePointSQL($name));
	}

/**
 * Quotes value to be used safely in database query
 *
 * @param mixed $value
 * @param string $type Type to be used for determining kind of quoting to perform
 * @return mixed quoted value
 */
	public function quote($value, $type = null) {
		$this->connect();
		list($value, $type) = $this->cast($value, $type);
		return $this->_driver->quote($value, $type);
	}

/**
 * Checks if the driver supports quoting
 *
 * @return boolean
 */
	public function supportsQuoting() {
		$this->connect();
		return $this->_driver->supportsQuoting();
	}

/**
 * Quotes a database identifier (a column name, table name, etc..) to
 * be used safely in queries without the risk of using reserver words
 *
 * @param string $identifier
 * @return string
 */
	public function quoteIdentifier($identifier) {
		return $this->_driver->quoteIdentifier($identifier);
	}

/**
 * Returns last id generated for a table or sequence in database
 *
 * @param string $table table name or sequence to get last insert value from
 * @return string|integer
 */
	public function lastInsertId($table) {
		$this->connect();
		return $this->_driver->lastInsertId($table);
	}

/**
 * Get the list of tables available in the current connection.
 *
 * @return array The list of tables in the connected database/schema.
 */
	public function listTables() {
		list($sql, $params) = $this->_driver->listTablesSql($this->_config);
		$result = [];
		$statement = $this->execute($sql, $params);
		while ($row = $statement->fetch()) {
			$result[] = $row[0];
		}
		return $result;
	}

/**
 * Get the schema information for a given table/collection
 *
 * @param string $table The table/collection you want schema information for.
 * @return array The schema data for the requested table.
 */
	public function describe($table) {
		list($sql, $params) = $this->_driver->describeTableSql($table, $this->_config);
		$statement = $this->execute($sql, $params);
		$schema = [];

		$fieldParams = $this->_driver->extraSchemaColumns();
		$rows = $statement->fetchAll('assoc');
		foreach ($rows as $row) {
			$schema += $this->_driver->convertFieldDescription($row, $fieldParams);
		}
		return $schema;
	}

/**
 * Enables or disables query logging for this connection.
 *
 * @param boolean $enable whether to turn logging on or disable it
 * @return void
 */
	public function logQueries($enable) {
		$this->_logQueries = $enable;
	}

/**
 * Sets the logger object instance. When called with no arguments
 * it returns the currently setup logger instance
 *
 * @param object $instance logger object instance
 * @return object logger instance
 */
	public function logger($instance = null) {
		if ($instance === null) {
			if ($this->_logger === null) {
				$this->_logger = new QueryLogger;
			}
			return $this->_logger;
		}
		$this->_logger = $instance;
	}

/**
 * Logs a Query string using the configured logger object
 *
 * @param string $sql string to be logged
 * @return void
 */
	public function log($sql) {
		$query = new LoggedQuery;
		$query->query = $sql;
		$this->logger()->log($query);
	}

/**
 * Returns a new statement object that will log the activity
 * for the passed original statement instance.
 *
 * @param Statement $statement the instance to be decorated
 * @return Statement
 */
	protected function _newLogger($statement) {
		$log = new LoggingStatement($statement, $this->driver());
		$log->logger($this->logger());
		return $log;
	}

}
