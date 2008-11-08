<?php
/* SVN FILE: $Id$ */
/**
 * Firebird/Interbase layer for DBO
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
 * @subpackage    cake.cake.libs.model.dbo
 * @since         CakePHP(tm) v 1.2.0.5152
 * @version       $Revision$
 * @modifiedby    $LastChangedBy$
 * @lastmodified  $Date$
 * @license       http://www.opensource.org/licenses/mit-license.php The MIT License
 */
/**
 * Short description for class.
 *
 * Long description for class
 *
 * @package       cake
 * @subpackage    cake.cake.libs.model.dbo
 */
class DboFirebird extends DboSource {
/**
 * Enter description here...
 *
 * @var unknown_type
 */
	var $description = "Firebird/Interbase DBO Driver";
/**
 * Saves the original table name
 *
 * @var unknown_type
 */
	var $modeltmp = array();
/**
 * Enter description here...
 *
 * @var unknown_type
 */
	var $startQuote = "\'";
/**
 * Enter description here...
 *
 * @var unknown_type
 */
	var $endQuote = "\'";
/**
 * Enter description here...
 *
 * @var unknown_type
 */
	var $alias = ' ';
/**
 * Enter description here...
 *
 * @var unknown_type
 */
	var $goofyLimit = true;
/**
 * Creates a map between field aliases and numeric indexes.
 *
 * @var array
 */
	var $__fieldMappings = array();
/**
 * Base configuration settings for Firebird driver
 *
 * @var array
 */
	var $_baseConfig = array(
		'persistent' => true,
		'host' => 'localhost',
		'login' => 'SYSDBA',
		'password' => 'masterkey',
		'database' => 'c:\\CAKE.FDB',
		'port' => '3050',
		'connect' => 'ibase_connect'
	);
/**
 * Firebird column definition
 *
 * @var array
 */
	var $columns = array(
		'primary_key' => array('name' => 'IDENTITY (1, 1) NOT NULL'),
		'string'	=> array('name'	 => 'varchar', 'limit' => '255'),
		'text'		=> array('name' => 'BLOB SUB_TYPE 1 SEGMENT SIZE 100 CHARACTER SET NONE'),
		'integer'	=> array('name' => 'integer'),
		'float'		=> array('name' => 'float', 'formatter' => 'floatval'),
		'datetime'	=> array('name' => 'timestamp', 'format'	=> 'd.m.Y H:i:s', 'formatter' => 'date'),
		'timestamp' => array('name'	=> 'timestamp', 'format'	 => 'd.m.Y H:i:s', 'formatter' => 'date'),
		'time'		=> array('name' => 'time', 'format'	   => 'H:i:s', 'formatter' => 'date'),
		'date'		=> array('name' => 'date', 'format'	   => 'd.m.Y', 'formatter' => 'date'),
		'binary'	=> array('name' => 'blob'),
		'boolean'	=> array('name' => 'smallint')
	);
/**
 * Firebird Transaction commands.
 *
 * @var array
 **/
	var $_commands = array(
		'begin'	   => 'SET TRANSACTION',
		'commit'   => 'COMMIT',
		'rollback' => 'ROLLBACK'
	);
/**
 * Connects to the database using options in the given configuration array.
 *
 * @return boolean True if the database could be connected, else false
 */
	function connect() {
		$config = $this->config;
		$connect = $config['connect'];

		$this->connected = false;
		$this->connection = $connect($config['host'] . ':' . $config['database'], $config['login'], $config['password']);
		$this->connected = true;
	}
/**
 * Disconnects from database.
 *
 * @return boolean True if the database could be disconnected, else false
 */
	function disconnect() {
		$this->connected = false;
		return @ibase_close($this->connection);
	}
/**
 * Executes given SQL statement.
 *
 * @param string $sql SQL statement
 * @return resource Result resource identifier
 * @access protected
 */
	function _execute($sql) {
		return @ibase_query($this->connection,	$sql);
	}
/**
 * Returns a row from given resultset as an array .
 *
 * @return array The fetched row as an array
 */
	function fetchRow() {
		if ($this->hasResult()) {
			$this->resultSet($this->_result);
			$resultRow = $this->fetchResult();
			return $resultRow;
		} else {
			return null;
		}
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
		$sql = "select RDB" . "$" . "RELATION_NAME as name
				FROM RDB" ."$" . "RELATIONS
				Where RDB" . "$" . "SYSTEM_FLAG =0";

		$result = @ibase_query($this->connection,$sql);
		$tables = array();
		while ($row = ibase_fetch_row ($result)) {
			$tables[] = strtolower(trim($row[0]));
		}
		parent::listSources($tables);
		return $tables;
	}
/**
 * Returns an array of the fields in given table name.
 *
 * @param Model $model Model object to describe
 * @return array Fields in table. Keys are name and type
 */
	function describe(&$model) {
		$this->modeltmp[$model->table] = $model->alias;
		$cache = parent::describe($model);

		if ($cache != null) {
			return $cache;
		}
		$fields = false;
		$sql = "SELECT * FROM " . $this->fullTableName($model, false);
		$rs = ibase_query($sql);
		$coln = ibase_num_fields($rs);
		$fields = false;

		for ($i = 0; $i < $coln; $i++) {
			$col_info = ibase_field_info($rs, $i);
			$fields[strtolower($col_info['name'])] = array(
					'type' => $this->column($col_info['type']),
					'null' => '',
					'length' => $col_info['length']
				);
		}
		$this->__cacheDescription($this->fullTableName($model, false), $fields);
		return $fields;
	}
/**
 * Returns a quoted name of $data for use in an SQL statement.
 *
 * @param string $data Name (table.field) to be prepared for use in an SQL statement
 * @return string Quoted for Firebird
 */
	function name($data) {
		if ($data == '*') {
				return '*';
		}
		$pos = strpos($data, '"');

		if ($pos === false) {
			if (!strpos($data, ".")) {
				$data = '"' . strtoupper($data) . '"';
			} else {
				$build = explode('.', $data);
				$data = '"' . strtoupper($build[0]) . '"."' . strtoupper($build[1]) . '"';
			}
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
		if ($data === '') {
			return "''";
		}

		switch($column) {
			case 'boolean':
				$data = $this->boolean((bool)$data);
			break;
			default:
				if (get_magic_quotes_gpc()) {
					$data = stripslashes(str_replace("'", "''", $data));
				} else {
					$data = str_replace("'", "''", $data);
				}
			break;
		}
		return "'" . $data . "'";
	}
/**
 * Removes Identity (primary key) column from update data before returning to parent
 *
 * @param Model $model
 * @param array $fields
 * @param array $values
 * @return array
 */
	function update(&$model, $fields = array(), $values = array()) {
		foreach ($fields as $i => $field) {
			if ($field == $model->primaryKey) {
				unset ($fields[$i]);
				unset ($values[$i]);
				break;
			}
		}
		return parent::update($model, $fields, $values);
	}
/**
 * Returns a formatted error message from previous database operation.
 *
 * @return string Error message with error number
 */
	function lastError() {
		$error = ibase_errmsg();

		if ($error !== false) {
			return $error;
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
			return ibase_affected_rows($this->connection);
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
		return $this->_result? /*ibase_affected_rows($this->_result)*/ 1: false;
	}
/**
 * Returns the ID generated from the previous INSERT operation.
 *
 * @param unknown_type $source
 * @return in
 */
	function lastInsertId($source = null, $field = 'id') {
		$query = "SELECT RDB\$TRIGGER_SOURCE
		FROM RDB\$TRIGGERS WHERE RDB\$RELATION_NAME = '".  strtoupper($source) .  "' AND
		RDB\$SYSTEM_FLAG IS NULL AND  RDB\$TRIGGER_TYPE = 1 ";

		$result = @ibase_query($this->connection,$query);
		$generator = "";

		while ($row = ibase_fetch_row($result, IBASE_TEXT)) {
			if (strpos($row[0], "NEW." . strtoupper($field))) {
				$pos = strpos($row[0], "GEN_ID(");

				if ($pos > 0) {
					$pos2 = strpos($row[0],",",$pos + 7);

					if ($pos2 > 0) {
						$generator = substr($row[0], $pos +7, $pos2 - $pos- 7);
					}
				}
				break;
			}
		}

		if (!empty($generator)) {
			$sql = "SELECT GEN_ID(". $generator	 . ",0) AS maxi FROM RDB" . "$" . "DATABASE";
			$res = $this->rawQuery($sql);
			$data = $this->fetchRow($res);
			return $data['maxi'];
		} else {
			return false;
		}
	}
/**
 * Returns a limit statement in the correct format for the particular database.
 *
 * @param integer $limit Limit of results returned
 * @param integer $offset Offset from which to start results
 * @return string SQL limit/offset statement
 */
	function limit($limit, $offset = null) {
		if ($limit) {
			$rt = '';

			if (!strpos(strtolower($limit), 'top') || strpos(strtolower($limit), 'top') === 0) {
				$rt = ' FIRST';
			}
			$rt .= ' ' . $limit;

			if (is_int($offset) && $offset > 0) {
				$rt .= ' SKIP ' . $offset;
			}
			return $rt;
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
				$col .= '(' . $real['limit'] . ')';
			}
			return $col;
		}

		$col = str_replace(')', '', $real);
		$limit = null;
		if (strpos($col, '(') !== false) {
			list($col, $limit) = explode('(', $col);
		}

		if (in_array($col, array('DATE', 'TIME'))) {
			return strtolower($col);
		}
		if ($col == 'TIMESTAMP') {
			return 'datetime';
		}
		if ($col == 'SMALLINT') {
			return 'boolean';
		}
		if (strpos($col, 'int') !== false || $col == 'numeric' || $col == 'INTEGER') {
			return 'integer';
		}
		if (strpos($col, 'char') !== false) {
			return 'string';
		}
		if (strpos($col, 'text') !== false) {
			return 'text';
		}
		if (strpos($col, 'VARCHAR') !== false) {
			return 'string';
		}
		if (strpos($col, 'BLOB') !== false) {
			return 'text';
		}
		if (in_array($col, array('FLOAT', 'NUMERIC', 'DECIMAL'))) {
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
		$num_fields = ibase_num_fields($results);
		$index = 0;
		$j = 0;

		while ($j < $num_fields) {
			$column = ibase_field_info($results, $j);
			if (!empty($column[2])) {
				$this->map[$index++] = array(ucfirst(strtolower($this->modeltmp[strtolower($column[2])])), strtolower($column[1]));
			} else {
				$this->map[$index++] = array(0, strtolower($column[1]));
			}
			$j++;
		}
	}
/**
 * Builds final SQL statement
 *
 * @param string $type Query type
 * @param array $data Query data
 * @return string
 */
	function renderStatement($type, $data) {
		extract($data);

		if (strtolower($type) == 'select') {
			if (preg_match('/offset\s+([0-9]+)/i', $limit, $offset)) {
				$limit = preg_replace('/\s*offset.*$/i', '', $limit);
				preg_match('/top\s+([0-9]+)/i', $limit, $limitVal);
				$offset = intval($offset[1]) + intval($limitVal[1]);
				$rOrder = $this->__switchSort($order);
				list($order2, $rOrder) = array($this->__mapFields($order), $this->__mapFields($rOrder));
				return "SELECT * FROM (SELECT {$limit} * FROM (SELECT TOP {$offset} {$fields} FROM {$table} {$alias} {$joins} {$conditions} {$order}) AS Set1 {$rOrder}) AS Set2 {$order2}";
			} else {
				return "SELECT {$limit} {$fields} FROM {$table} {$alias} {$joins} {$conditions} {$order}";
			}
		} else {
			return parent::renderStatement($type, $data);
		}
	}
/**
 * Fetches the next row from the current result set
 *
 * @return unknown
 */
	function fetchResult() {
		if ($row = ibase_fetch_row($this->results, IBASE_TEXT)) {
			$resultRow = array();
			$i = 0;

			foreach ($row as $index => $field) {
				list($table, $column) = $this->map[$index];

				if (trim($table) == "") {
					$resultRow[0][$column] = $row[$index];
				} else {
					$resultRow[$table][$column] = $row[$index];
					$i++;
				}
			}
			return $resultRow;
		} else {
			return false;
		}
	}
}
?>