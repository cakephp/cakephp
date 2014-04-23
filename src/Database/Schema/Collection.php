<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         3.0.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Database\Schema;

use Cake\Cache\Cache;
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
 * @var \Cake\Database\Connection
 */
	protected $_connection;

/**
 * Schema dialect instance.
 *
 * @var \Cake\Database\Schema\BaseSchema
 */
	protected $_dialect;

/**
 * The name of the cache config key to use for caching table metadata,
 * of false if disabled.
 *
 * @var string|bool
 */
	protected $_cache = false;

/**
 * Constructor.
 *
 * @param \Cake\Database\Connection $connection
 */
	public function __construct(Connection $connection) {
		$this->_connection = $connection;
		$this->_dialect = $connection->driver()->schemaDialect();
		$config = $this->_connection->config();

		if (!empty($config['cacheMetadata'])) {
			$this->cacheMetadata(true);
		}
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
		$statement->closeCursor();
		return $result;
	}

/**
 * Get the column metadata for a table.
 *
 * Caching will be applied if `cacheMetadata` key is present in the Connection
 * configuration options. Defaults to _cake_model_ when true.
 *
 * @param string $name The name of the table to describe.
 * @return \Cake\Database\Schema\Table Object with column metadata.
 * @throws \Cake\Database\Exception when table cannot be described.
 */
	public function describe($name) {
		$cacheConfig = $this->cacheMetadata();
		if ($cacheConfig) {
			$cacheKey = $this->_connection->configName() . '_' . $name;
			$cached = Cache::read($cacheKey, $cacheConfig);
			if ($cached !== false) {
				return $cached;
			}
		}

		$config = $this->_connection->config();
		list($sql, $params) = $this->_dialect->describeTableSql($name, $config);
		$statement = $this->_executeSql($sql, $params);
		if (count($statement) === 0) {
			throw new Exception(sprintf('Cannot describe %s. It has 0 columns.', $name));
		}

		$table = new Table($name);
		foreach ($statement->fetchAll('assoc') as $row) {
			$this->_dialect->convertFieldDescription($table, $row);
		}

		list($sql, $params) = $this->_dialect->describeIndexSql($name, $config);
		$statement = $this->_executeSql($sql, $params);
		foreach ($statement->fetchAll('assoc') as $row) {
			$this->_dialect->convertIndexDescription($table, $row);
		}

		list($sql, $params) = $this->_dialect->describeForeignKeySql($name, $config);
		$statement = $this->_executeSql($sql, $params);
		foreach ($statement->fetchAll('assoc') as $row) {
			$this->_dialect->convertForeignKeyDescription($table, $row);
		}
		$statement->closeCursor();

		if (!empty($cacheConfig)) {
			Cache::write($cacheKey, $table, $cacheConfig);
		}
		return $table;
	}

/**
 * Sets the cache config name to use for caching table metadata, or
 * disabels it if false is passed.
 * If called with no arguments it returns the current configuration name.
 *
 * @param bool $enable whether or not to enable caching
 * @return string|bool
 */
	public function cacheMetadata($enable = null) {
		if ($enable === null) {
			return $this->_cache;
		}
		if ($enable === true) {
			$enable = '_cake_model_';
		}
		return $this->_cache = $enable;
	}

/**
 * Helper method to run queries and convert Exceptions to the correct types.
 *
 * @param string $sql The sql to run.
 * @param array $params Parameters for the statement.
 * @return \Cake\Database\StatementInterface Prepared statement
 * @throws \Cake\Database\Exception on query failure.
 */
	protected function _executeSql($sql, $params) {
		try {
			return $this->_connection->execute($sql, $params);
		} catch (\PDOException $e) {
			throw new Exception($e->getMessage(), 500, $e);
		}
	}

}
