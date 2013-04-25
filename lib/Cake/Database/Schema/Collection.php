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
use Cake\Error;

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
 *
 * @param string $name The name of the table to describe
 * @return Cake\Schema\Table object with column metdata.
 * @see Collection::fullDescribe()
 */
	public function describe($name) {
	}

/**
 * Get column & index metadata for a table.
 *
 * @param string $name The name of the table to describe
 * @return Cake\Schema\Table object for table
 * @see Collection::describe()
 */
	public function fullDescribe($table) {
	}

}
