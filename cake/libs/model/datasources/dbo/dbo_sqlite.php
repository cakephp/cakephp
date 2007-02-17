<?php
/* SVN FILE: $Id$ */

/**
 * SQLite layer for DBO
 *
 * Long description for file
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) :  Rapid Development Framework <http://www.cakephp.org/>
 * Copyright 2005-2007, Cake Software Foundation, Inc.
 *								1785 E. Sahara Avenue, Suite 490-204
 *								Las Vegas, Nevada 89104
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @filesource
 * @copyright		Copyright 2005-2007, Cake Software Foundation, Inc.
 * @link				http://www.cakefoundation.org/projects/info/cakephp CakePHP(tm) Project
 * @package			cake
 * @subpackage		cake.cake.libs.model.datasources.dbo
 * @since			CakePHP(tm) v 0.9.0
 * @version			$Revision$
 * @modifiedby		$LastChangedBy$
 * @lastmodified	$Date$
 * @license			http://www.opensource.org/licenses/mit-license.php The MIT License
 */
/**
 * DBO implementation for the SQLite DBMS.
 *
 * Long description for class
 *
 * @package		cake
 * @subpackage	cake.cake.libs.model.datasources.dbo
 */
class DboSqlite extends DboSource {

/**
 * Enter description here...
 *
 * @var unknown_type
 */
	var $description = "SQLite DBO Driver";
/**
 * Enter description here...
 *
 * @var unknown_type
 */
	var $startQuote = '"';
/**
 * Enter description here...
 *
 * @var unknown_type
 */
	var $endQuote = '"';
/**
 * Base configuration settings for SQLite driver
 *
 * @var array
 */
	var $_baseConfig = array(
		'persistent' => true,
		'database' => null,
		'connect' => 'sqlite_popen'
	);
/**
 * SQLite column definition
 *
 * @var array
 */
	var $columns = array(
		'primary_key' => array('name' => 'integer primary key'),
		'string' => array('name' => 'varchar', 'limit' => '255'),
		'text' => array('name' => 'text'),
		'integer' => array('name' => 'integer', 'limit' => '11', 'formatter' => 'intval'),
		'float' => array('name' => 'float', 'formatter' => 'floatval'),
		'datetime' => array('name' => 'timestamp', 'format' => 'YmdHis', 'formatter' => 'date'),
		'timestamp' => array('name' => 'timestamp', 'format' => 'YmdHis', 'formatter' => 'date'),
		'time' => array('name' => 'timestamp', 'format' => 'His', 'formatter' => 'date'),
		'date' => array('name' => 'date', 'format' => 'Ymd', 'formatter' => 'date'),
		'binary' => array('name' => 'blob'),
		'boolean' => array('name' => 'integer', 'limit' => '1')
	);
/**
 * Connects to the database using config['database'] as a filename.
 *
 * @param array $config Configuration array for connecting
 * @return mixed
 */
	function connect() {
		$config = $this->config;
		$this->connection = $config['connect']($config['database']);
		$this->connected = is_resource($this->connection);
		return $this->connected;
	}
/**
 * Disconnects from database.
 *
 * @return boolean True if the database could be disconnected, else false
 */
	function disconnect() {
		$this->connected = !@sqlite_close($this->connection);
		return !$this->connected;
	}
/**
 * Executes given SQL statement.
 *
 * @param string $sql SQL statement
 * @return resource Result resource identifier
 */
	function _execute($sql) {
		return sqlite_query($this->connection, $sql);
	}
/**
 * Returns an array of tables in the database. If there are no tables, an error is raised and the application exits.
 *
 * @return array Array of tablenames in the database
 */
	function listSources() {
		$db = $this->config['database'];
		$this->config['database'] = basename($this->config['database']);

		$cache = parent::listSources();
		if ($cache != null) {
			return $cache;
		}

		$result = $this->fetchAll("SELECT name FROM sqlite_master WHERE type='table' ORDER BY name;");

		if (!$result || empty($result)) {
			return array();
		} else {
			$tables = array();
			foreach ($result as $table) {
				$tables[] = $table[0]['name'];
			}
			parent::listSources($tables);

			$this->config['database'] = $db;
			return $tables;
		}
		$this->config['database'] = $db;
		return array();
	}
/**
 * Returns an array of the fields in given table name.
 *
 * @param string $tableName Name of database table to inspect
 * @return array Fields in table. Keys are name and type
 */
	function describe(&$model) {
		$cache = parent::describe($model);
		if ($cache != null) {
			return $cache;
		}
		$fields = array();
		$result = $this->fetchAll('PRAGMA table_info(' . $model->tablePrefix . $model->table . ')');

		foreach ($result as $column) {
			$fields[] = array(
				'name' => $column[0]['name'],
				'type' => $this->column($column[0]['type']),
				'null' => ! $column[0]['notnull'],
				'default' => $column[0]['dflt_value']
			);
		}

		$this->__cacheDescription($model->tablePrefix . $model->table, $fields);
		return $fields;
	}
/**
 * Returns a quoted name of $data for use in an SQL statement.
 *
 * @param string $data Name (table.field) to be prepared for use in an SQL statement
 * @return string Quoted for SQLite
 */
	function name ($data) {
		if ($data == '*') {
			return '*';
		}
		$pos = strpos($data, '"');
		if ($pos === false) {
			$data = '"'. str_replace('.', '"."', $data) .'"';
		}
		return $data;
	}
/**
 * Returns a quoted and escaped string of $data for use in an SQL statement.
 *
 * @param string $data String to be prepared for use in an SQL statement
 * @return string Quoted and escaped
 */
	function value ($data, $column = null, $safe = false) {
		$parent = parent::value($data, $column, $safe);

		if ($parent != null) {
			return $parent;
		}

		if ($data === null) {
			return 'NULL';
		}

		if($data === '') {
			return  "''";
		}

		switch ($column) {
			case 'boolean':
				$data = $this->boolean((bool)$data);
			break;
			default:
				$data = sqlite_escape_string($data);
			break;
		}
		return "'" . $data . "'";
	}
/**
 * Begin a transaction
 *
 * @param unknown_type $model
 * @return boolean True on success, false on fail
 * (i.e. if the database/model does not support transactions).
 */
	function begin (&$model) {
		if (parent::begin($model)) {
			if ($this->execute('BEGIN')) {
				$this->_transactionStarted = true;
				return true;
			}
		}
		return false;
	}
/**
 * Commit a transaction
 *
 * @param unknown_type $model
 * @return boolean True on success, false on fail
 * (i.e. if the database/model does not support transactions,
 * or a transaction has not started).
 */
	function commit (&$model) {
		if (parent::commit($model)) {
			$this->_transactionStarted = false;
			return $this->execute('COMMIT');
		}
		return false;
	}
/**
 * Rollback a transaction
 *
 * @param unknown_type $model
 * @return boolean True on success, false on fail
 * (i.e. if the database/model does not support transactions,
 * or a transaction has not started).
 */
	function rollback (&$model) {
		if (parent::rollback($model)) {
			return $this->execute('ROLLBACK');
		}
		return false;
	}
/**
 * Returns a formatted error message from previous database operation.
 *
 * @return string Error message
 */
	function lastError() {
		$error = sqlite_last_error($this->connection);
		if ($error) {
			return $error.': '.sqlite_error_string($error);
		}
		return null;
	}
/**
 * Returns number of affected rows in previous database operation. If no previous operation exists, this returns false.
 *
 * @return int Number of affected rows
 */
	function lastAffected() {
		if ($this->_result) {
			return sqlite_changes($this->connection);
		}
		return false;
	}
/**
 * Returns number of rows in previous resultset. If no previous resultset exists,
 * this returns false.
 *
 * @return int Number of rows in resultset
 */
	function lastNumRows() {
		if ($this->_result) {
			sqlite_num_rows($this->_result);
		}
		return false;
	}
/**
 * Returns the ID generated from the previous INSERT operation.
 *
 * @return int
 */
	function lastInsertId() {
		return sqlite_last_insert_rowid($this->connection);
	}
/**
 * Converts database-layer column types to basic types
 *
 * @param string $real Real database-layer column type (i.e. "varchar(255)")
 * @return string Abstract column type (i.e. "string")
 */
	function column($real) {
		if (is_array($real)) {
			$col = $real['name'];
			if (isset($real['limit'])) {
				$col .= '('.$real['limit'].')';
			}
			return $col;
		}

		$col = low(r(')', '', $real));
		$limit = null;
		@list($col, $limit) = explode('(', $col);

		if (in_array($col, array('text', 'integer', 'float', 'boolean', 'timestamp', 'datetime'))) {
			return $col;
		}
		if (strpos($col, 'varchar') !== false) {
			return 'string';
		}
		if (in_array($col, array('blob', 'clob'))) {
			return 'binary';
		}
		if (strpos($col, 'numeric') !== false) {
			return 'float';
		}

		return 'text';
	}
/**
 * Enter description here...
 *
 * @param unknown_type $results
 */
	function resultSet(&$results) {
		$this->results =& $results;
		$this->map = array();
		$num_fields = sqlite_num_fields($results);
		$index = 0;
		$j = 0;

		while ($j < $num_fields) {
			$columnName = str_replace('"', '', sqlite_field_name($results, $j));

			if (strpos($columnName, '.')) {
				$parts = explode('.', $columnName);
				$this->map[$index++] = array($parts[0], $parts[1]);
			} else {
				$this->map[$index++] = array(0, $columnName);
			}
			$j++;
		}
	}
/**
 * Fetches the next row from the current result set
 *
 * @return unknown
 */
	function fetchResult() {
		if ($row = sqlite_fetch_array($this->results, SQLITE_ASSOC)) {
			$resultRow = array();
			$i = 0;

			foreach ($row as $index => $field) {
				if (strpos($index, '.')) {
					list($table, $column) = explode('.', str_replace('"', '', $index));
					$resultRow[$table][$column] = $row[$index];
				} else {
					$resultRow[0][str_replace('"', '', $index)] = $row[$index];
				}
				$i++;
			}
			return $resultRow;
		} else {
			return false;
		}
	}
/**
 * Returns a limit statement in the correct format for the particular database.
 *
 * @param int $limit Limit of results returned
 * @param int $offset Offset from which to start results
 * @return string SQL limit/offset statement
 */
	function limit ($limit, $offset = null) {
		if ($limit) {
			$rt = '';
			if (!strpos(strtolower($limit), 'limit') || strpos(strtolower($limit), 'limit') === 0) {
				$rt = ' LIMIT';
			}
			$rt .= ' ' . $limit;
			if ($offset) {
				$rt .= ', ' . $offset;
			}
			return $rt;
		}
		return null;
	}
}

?>