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
namespace Cake\Database\Schema;

use Cake\Database\Connection;
use Cake\Database\Exception;
use Cake\Database\Schema\Table;

/**
 * Represents a database schema collection
 *
 * Used to access information about the tables,
 * and other data in a database.
 */
class Collection {

/**
 * Connection object
 *
 * @var Cake\Database\Connection
 */
	protected $_connection;

/**
 * Schema dialect instance.
 *
 * @var
 */
	protected $_dialect;

/**
 * Constructor.
 *
 * @param Cake\Database\Connection $connection
 */
	public function __construct(Connection $connection) {
		$this->_connection = $connection;
		$this->_dialect = $connection->driver()->schemaDialect();
	}

/**
 * Get the list of tables available in the current connection.
 *
 * @return array The list of tables in the connected database/schema.
 */
	public function listTables() {
		list($sql, $params) = $this->_dialect->listTablesSql($this->_connection->config());
		$result = [];
		$statement = $this->_connection->execute($sql, $params);
		while ($row = $statement->fetch()) {
			$result[] = $row[0];
		}
		return $result;
	}

/**
 * Get the column metadata for a table.
 *
 * @param string $name The name of the table to describe.
 * @return Cake\Schema\Table|null Object with column metadata, or null.
 * @throws Cake\Database\Exception when table cannot be described.
 */
	public function describe($name) {
		list($sql, $params) = $this->_dialect->describeTableSql(
			$name,
			$this->_connection->config()
		);
		$statement = $this->_executeSql($sql, $params);
		if (count($statement) === 0) {
			throw new Exception(__d('cake_dev', 'Cannot describe %s. It has 0 columns.', $name));
		}

		$table = new Table($name);
		foreach ($statement->fetchAll('assoc') as $row) {
			$this->_dialect->convertFieldDescription($table, $row);
		}

		list($sql, $params) = $this->_dialect->describeIndexSql(
			$name,
			$this->_connection->config()
		);
		$statement = $this->_executeSql($sql, $params);
		foreach ($statement->fetchAll('assoc') as $row) {
			$this->_dialect->convertIndexDescription($table, $row);
		}

		list($sql, $params) = $this->_dialect->describeForeignKeySql(
			$name,
			$this->_connection->config()
		);
		$statement = $this->_executeSql($sql, $params);
		foreach ($statement->fetchAll('assoc') as $row) {
			$this->_dialect->convertForeignKey($table, $row);
		}
		return $table;
	}

/**
 * Helper method to run queries and convert Exceptions to the correct types.
 *
 * @param string $sql The sql to run.
 * @param array $params Parameters for the statement.
 * @return Cake\Database\Statement Prepared statement
 * @throws Cake\Database\Exception on query failure.
 */
	protected function _executeSql($sql, $params) {
		try {
			return $this->_connection->execute($sql, $params);
		} catch (\PDOException $e) {
			throw new Exception($e->getMessage(), 500, $e);
		}
	}

}
