<?php
/* SVN FILE: $Id$ */
/**
 * MySQLi layer for DBO
 *
 * Long description for file
 *
 * PHP versions 4 and 5
 *
 * CakePHP :  Rapid Development Framework <http://www.cakephp.org/>
 * Copyright (c)	2006, Cake Software Foundation, Inc.
 *								1785 E. Sahara Avenue, Suite 490-204
 *								Las Vegas, Nevada 89104
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @filesource
 * @copyright		Copyright (c) 2006, Cake Software Foundation, Inc.
 * @link				http://www.cakefoundation.org/projects/info/cakephp CakePHP Project
 * @package			cake
 * @subpackage		cake.cake.libs.model.dbo
 * @since			CakePHP v 1.1.4.2974
 * @version			$Revision$
 * @modifiedby		$LastChangedBy$
 * @lastmodified	$Date$
 * @license			http://www.opensource.org/licenses/mit-license.php The MIT License
 */
/**
 * Include DBO.
 */
uses('model'.DS.'datasources'.DS.'dbo_source');
/**
 * Short description for class.
 *
 * Long description for class
 *
 * @package		cake
 * @subpackage	cake.cake.libs.model.dbo
 */
class DboMysqli extends DboSource {
/**
 * Enter description here...
 *
 * @var unknown_type
 */
	var $description = "Mysqli DBO Driver";
/**
 * Enter description here...
 *
 * @var unknown_type
 */
	var $startQuote = "`";
/**
 * Enter description here...
 *
 * @var unknown_type
 */
	var $endQuote = "`";
/**
 * Base configuration settings for Mysqli driver
 *
 * @var array
 */
	var $_baseConfig = array('persistent' => true,
								'host' => 'localhost',
								'login' => 'root',
								'password' => '',
								'database' => 'cake',
								'port' => '3306',
								'connect' => 'mysqli_pconnect');
/**
 * Mysqli column definition
 *
 * @var array
 */
	var $columns = array('primary_key' => array('name' => 'int(11) DEFAULT NULL auto_increment'),
						'string' => array('name' => 'varchar', 'limit' => '255'),
						'text' => array('name' => 'text'),
						'integer' => array('name' => 'int', 'limit' => '11', 'formatter' => 'intval'),
						'float' => array('name' => 'float', 'formatter' => 'floatval'),
						'datetime' => array('name' => 'datetime', 'format' => 'Y-m-d H:i:s', 'formatter' => 'date'),
						'timestamp' => array('name' => 'timestamp', 'format' => 'Y-m-d H:i:s', 'formatter' => 'date'),
						'time' => array('name' => 'time', 'format' => 'H:i:s', 'formatter' => 'date'),
						'date' => array('name' => 'date', 'format' => 'Y-m-d', 'formatter' => 'date'),
						'binary' => array('name' => 'blob'),
						'boolean' => array('name' => 'tinyint', 'limit' => '1'));
/**
 * Connects to the database using options in the given configuration array.
 *
 * @return boolean True if the database could be connected, else false
 */
	function connect() {
		$config = $this->config;
		$connect = $config['connect'];
		$this->connected = false;

		if (!$config['persistent']) {
			$this->connection = mysqli_connect($config['host'], $config['login'], $config['password'], true);
		} else {
			$this->connection = $connect($config['host'], $config['login'], $config['password']);
		}

		if (mysqli_select_db($this->connection, $config['database'])) {
			$this->connected = true;
		}

		return $this->connected;
	}
/**
 * Disconnects from database.
 *
 * @return boolean True if the database could be disconnected, else false
 */
	function disconnect() {
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
		return mysqli_query($this->connection, $sql);
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
		$result = $this->_execute('SHOW TABLES FROM ' . $this->config['database'] . ';');
		if (!$result) {
			return array();
		} else {

			$tables = array();
			while ($line = mysqli_fetch_array($result)) {
				$tables[] = $line[0];
			}

			parent::listSources($tables);
			return $tables;
		}
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
				$fields[] = array(
					'name' => $column[0]['Field'],
					'type' => $this->column($column[0]['Type']),
					'null' => $column[0]['Null'],
					'default' => $column[0]['Default']
				);
			}
		}

		$this->__cacheDescription($model->tablePrefix.$model->table, $fields);
		return $fields;
	}
/**
 * Returns a quoted name of $data for use in an SQL statement.
 *
 * @param string $data Name (table.field) to be prepared for use in an SQL statement
 * @return string Quoted for MySQL
 */
	function name($data) {
		if ($data == '*') {
			return '*';
		}
		$pos = strpos($data, '`');
		if ($pos === false) {
			$data = '`'. str_replace('.', '`.`', $data) .'`';
		}
		return $data;
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
				if (ini_get('magic_quotes_gpc') == 1) {
					$data = stripslashes($data);
				}
				$data = mysqli_real_escape_string($this->connection, $data);
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
	function begin(&$model) {
		if (parent::begin($model)) {
			if ($this->execute('START TRANSACTION')) {
				$this->__transactionStarted = true;
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
	function commit(&$model) {
		if (parent::commit($model)) {
			$this->__transactionStarted;
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
	function rollback(&$model) {
		if (parent::rollback($model)) {
			return $this->execute('ROLLBACK');
		}
		return false;
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
 * @return int Number of affected rows
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
 * @return int Number of rows in resultset
 */
	function lastNumRows() {
		if ($this->_result and is_object($this->_result)) {
			return @mysqli_num_rows($this->_result);
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
		$id = mysqli_insert_id($this->connection);
		if ($id) {
			return $id;
		}

		$data = $this->fetchAll('SELECT LAST_INSERT_ID() as id From '.$source);
		if ($data && isset($data[0]['id'])) {
			return $data[0]['id'];
		}
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
			if (isset($real['limit']))
			{
				$col .= '('.$real['limit'].')';
			}
			return $col;
		}

		$col = r(')', '', $real);
		$limit = null;
		@list($col, $limit) = explode('(', $col);

		if (in_array($col, array('date', 'time', 'datetime', 'timestamp'))) {
			return $col;
		}
		if ($col == 'tinyint' && $limit == '1') {
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
		if (strpos($col, 'blob') !== false) {
			return 'binary';
		}
		if (in_array($col, array('float', 'double', 'decimal'))) {
			return 'float';
		}
		if (strpos($col, 'enum') !== false) {
			return "enum($limit)";
		}
		if ($col == 'boolean') {
			return $col;
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
		$num_fields = mysqli_num_fields($results);
		$index = 0;
		$j = 0;
		while ($j < $num_fields) {
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
				list($table, $column) = $this->map[$index];
				$resultRow[$table][$column] = $row[$index];
				$i++;
			}
			return $resultRow;
		} else {
			return false;
		}
	}
/**
 * Returns a row from given resultset as an array .
 *
 * @param bool $assoc Associative array only, or both?
 * @return array The fetched row as an array
*/
	function fetchRow($assoc = false) {
		if (is_object($this->_result)) {
			$this->resultSet($this->_result);
			$resultRow = $this->fetchResult();
			return $resultRow;
		} else {
			return null;
		}
	}
/**
 * Enter description here...
 *
 * @param unknown_type $schema
 *  @return unknown
 */
	function buildSchemaQuery($schema) {
		$search = array('{AUTOINCREMENT}', '{PRIMARY}', '{UNSIGNED}', '{FULLTEXT}',
						'{FULLTEXT_MYSQL}', '{BOOLEAN}', '{UTF_8}');
		$replace = array('int(11) not null auto_increment', 'primary key', 'unsigned',
						'FULLTEXT', 'FULLTEXT', 'enum (\'true\', \'false\') NOT NULL default \'true\'',
						'/*!40100 CHARACTER SET utf8 COLLATE utf8_unicode_ci */');
		$query = trim(r($search, $replace, $schema));
		return $query;
	}
}
?>