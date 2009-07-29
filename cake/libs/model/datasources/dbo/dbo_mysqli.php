<?php
/* SVN FILE: $Id$ */
/**
 * MySQLi layer for DBO
 *
 * Long description for file
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) :  Rapid Development Framework (http://www.cakephp.org)
 * Copyright 2005-2008, Cake Software Foundation, Inc. (http://www.cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @filesource
 * @copyright     Copyright 2005-2008, Cake Software Foundation, Inc. (http://www.cakefoundation.org)
 * @link          http://www.cakefoundation.org/projects/info/cakephp CakePHP(tm) Project
 * @package       cake
 * @subpackage    cake.cake.libs.model.datasources.dbo
 * @since         CakePHP(tm) v 1.1.4.2974
 * @version       $Revision$
 * @modifiedby    $LastChangedBy$
 * @lastmodified  $Date$
 * @license       http://www.opensource.org/licenses/mit-license.php The MIT License
 */
App::import('Core', 'DboMysql');
/**
 * MySQLi DBO driver object
 *
 * Provides connection and SQL generation for MySQL RDMS using PHP's MySQLi Interface
 *
 * @package       cake
 * @subpackage    cake.cake.libs.model.datasources.dbo
 */
class DboMysqli extends DboMysqlBase {
/**
 * Enter description here...
 *
 * @var unknown_type
 */
	var $description = "Mysqli DBO Driver";
/**
 * Base configuration settings for Mysqli driver
 *
 * @var array
 */
	var $_baseConfig = array(
		'persistent' => true,
		'host' => 'localhost',
		'login' => 'root',
		'password' => '',
		'database' => 'cake',
		'port' => '3306',
		'connect' => 'mysqli_connect'
	);
/**
 * Connects to the database using options in the given configuration array.
 *
 * @return boolean True if the database could be connected, else false
 */
	function connect() {
		$config = $this->config;
		$this->connected = false;

		if (is_numeric($config['port'])) {
			$config['socket'] = null;
		} else {
			$config['socket'] = $config['port'];
			$config['port'] = null;
		}

		$this->connection = mysqli_connect($config['host'], $config['login'], $config['password'], $config['database'], $config['port'], $config['socket']);

		if ($this->connection !== false) {
			$this->connected = true;
		}
		
		$this->_useAlias = (bool)version_compare(mysqli_get_server_info($this->connection), "4.1", ">=");
		
		if (!empty($config['encoding'])) {
			$this->setEncoding($config['encoding']);
		}
		return $this->connected;
	}
/**
 * Disconnects from database.
 *
 * @return boolean True if the database could be disconnected, else false
 */
	function disconnect() {
		if (isset($this->results) && is_resource($this->results)) {
			mysqli_free_result($this->results);
		}
		$this->connected = !@mysqli_close($this->connection);
		return !$this->connected;
	}
/**
 * Executes given SQL statement.
 *
 * @param string $sql SQL statement
 * @return resource Result resource identifier
 * @access protected
 */
	function _execute($sql) {
		if (preg_match('/^\s*call/i', $sql)) {
			return $this->_executeProcedure($sql);
		}
		return mysqli_query($this->connection, $sql);
	}
/**
 * Executes given SQL statement (procedure call).
 *
 * @param string $sql SQL statement (procedure call)
 * @return resource Result resource identifier for first recordset
 * @access protected
 */
	function _executeProcedure($sql) {
		$answer = mysqli_multi_query($this->connection, $sql);

		$firstResult = mysqli_store_result($this->connection);

		if (mysqli_more_results($this->connection)) {
			while ($lastResult = mysqli_next_result($this->connection));
		}
		return $firstResult;
	}
/**
 * Returns an array of sources (tables) in the database.
 *
 * @return array Array of tablenames in the database
 */
	function listSources() {
		$cache = parent::listSources();
		if ($cache != null) {
			return $cache;
		}
		$result = $this->_execute('SHOW TABLES FROM ' . $this->name($this->config['database']) . ';');

		if (!$result) {
			return array();
		}

		$tables = array();

		while ($line = mysqli_fetch_array($result)) {
			$tables[] = $line[0];
		}
		parent::listSources($tables);
		return $tables;
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

		$fields = false;
		$cols = $this->query('DESCRIBE ' . $this->fullTableName($model));

		foreach ($cols as $column) {
			$colKey = array_keys($column);
			if (isset($column[$colKey[0]]) && !isset($column[0])) {
				$column[0] = $column[$colKey[0]];
			}
			if (isset($column[0])) {
				$fields[$column[0]['Field']] = array(
					'type'		=> $this->column($column[0]['Type']),
					'null'		=> ($column[0]['Null'] == 'YES' ? true : false),
					'default'	=> $column[0]['Default'],
					'length'	=> $this->length($column[0]['Type'])
				);
				if (!empty($column[0]['Key']) && isset($this->index[$column[0]['Key']])) {
					$fields[$column[0]['Field']]['key']	= $this->index[$column[0]['Key']];
				}
			}
		}

		$this->__cacheDescription($this->fullTableName($model, false), $fields);
		return $fields;
	}
/**
 * Returns a quoted and escaped string of $data for use in an SQL statement.
 *
 * @param string $data String to be prepared for use in an SQL statement
 * @param string $column The column into which this data will be inserted
 * @param boolean $safe Whether or not numeric data should be handled automagically if no column data is provided
 * @return string Quoted and escaped data
 */
	function value($data, $column = null, $safe = false) {
		$parent = parent::value($data, $column, $safe);

		if ($parent != null) {
			return $parent;
		}
		if ($data === null || (is_array($data) && empty($data))) {
			return 'NULL';
		}
		if ($data === '' && $column !== 'integer' && $column !== 'float' && $column !== 'boolean') {
			return "''";
		}
		if (empty($column)) {
			$column = $this->introspectType($data);
		}

		switch ($column) {
			case 'boolean':
				return $this->boolean((bool)$data);
			break;
			case 'integer' :
			case 'float' :
			case null :
				if ($data === '') {
					return 'NULL';
				}
				if ((is_int($data) || is_float($data) || $data === '0') || (
					is_numeric($data) && strpos($data, ',') === false &&
					$data[0] != '0' && strpos($data, 'e') === false)) {
						return $data;
					}
			default:
				$data = "'" . mysqli_real_escape_string($this->connection, $data) . "'";
			break;
		}

		return $data;
	}
/**
 * Returns a formatted error message from previous database operation.
 *
 * @return string Error message with error number
 */
	function lastError() {
		if (mysqli_errno($this->connection)) {
			return mysqli_errno($this->connection).': '.mysqli_error($this->connection);
		}
		return null;
	}
/**
 * Returns number of affected rows in previous database operation. If no previous operation exists,
 * this returns false.
 *
 * @return integer Number of affected rows
 */
	function lastAffected() {
		if ($this->_result) {
			return mysqli_affected_rows($this->connection);
		}
		return null;
	}
/**
 * Returns number of rows in previous resultset. If no previous resultset exists,
 * this returns false.
 *
 * @return integer Number of rows in resultset
 */
	function lastNumRows() {
		if ($this->hasResult()) {
			return mysqli_num_rows($this->_result);
		}
		return null;
	}
/**
 * Returns the ID generated from the previous INSERT operation.
 *
 * @param unknown_type $source
 * @return in
 */
	function lastInsertId($source = null) {
		$id = $this->fetchRow('SELECT LAST_INSERT_ID() AS insertID', false);
		if ($id !== false && !empty($id) && !empty($id[0]) && isset($id[0]['insertID'])) {
			return $id[0]['insertID'];
		}
		return null;
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

		$col = str_replace(')', '', $real);
		$limit = $this->length($real);
		if (strpos($col, '(') !== false) {
			list($col, $vals) = explode('(', $col);
		}

		if (in_array($col, array('date', 'time', 'datetime', 'timestamp'))) {
			return $col;
		}
		if (($col == 'tinyint' && $limit == 1) || $col == 'boolean') {
			return 'boolean';
		}
		if (strpos($col, 'int') !== false) {
			return 'integer';
		}
		if (strpos($col, 'char') !== false || $col == 'tinytext') {
			return 'string';
		}
		if (strpos($col, 'text') !== false) {
			return 'text';
		}
		if (strpos($col, 'blob') !== false || $col == 'binary') {
			return 'binary';
		}
		if (strpos($col, 'float') !== false || strpos($col, 'double') !== false || strpos($col, 'decimal') !== false) {
			return 'float';
		}
		if (strpos($col, 'enum') !== false) {
			return "enum($vals)";
		}
		return 'text';
	}
/**
 * Gets the length of a database-native column description, or null if no length
 *
 * @param string $real Real database-layer column type (i.e. "varchar(255)")
 * @return integer An integer representing the length of the column
 */
	function length($real) {
		$col = str_replace(array(')', 'unsigned'), '', $real);
		$limit = null;
	
		if (strpos($col, '(') !== false) {
			list($col, $limit) = explode('(', $col);
		}
	
		if ($limit != null) {
			return intval($limit);
		}
		return null;
	}
/**
 * Enter description here...
 *
 * @param unknown_type $results
 */
	function resultSet(&$results) {
		if (isset($this->results) && is_resource($this->results) && $this->results != $results) {
			mysqli_free_result($this->results);
		}
		$this->results =& $results;
		$this->map = array();
		$numFields = mysqli_num_fields($results);
		$index = 0;
		$j = 0;
		while ($j < $numFields) {
			$column = mysqli_fetch_field_direct($results, $j);
			if (!empty($column->table)) {
				$this->map[$index++] = array($column->table, $column->name);
			} else {
				$this->map[$index++] = array(0, $column->name);
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
		if ($row = mysqli_fetch_row($this->results)) {
			$resultRow = array();
			$i = 0;
			foreach ($row as $index => $field) {
				$table = $column = null;
				if (count($this->map[$index]) == 2) {
					list($table, $column) = $this->map[$index];
				}
				$resultRow[$table][$column] = $row[$index];
				$i++;
			}
			return $resultRow;
		}
		return false;
	}
/**
 * Gets the database encoding
 *
 * @return string The database encoding
 */
	function getEncoding() {
		return mysqli_client_encoding($this->connection);
	}
/**
 * Checks if the result is valid
 *
 * @return boolean True if the result is valid, else false
 */
	function hasResult() {
		return is_object($this->_result);
	}
}
?>