<?php
/* SVN FILE: $Id$ */

/**
 * MS SQL layer for DBO
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
 * @since			CakePHP v 0.10.5.1790
 * @version			$Revision$
 * @modifiedby		$LastChangedBy$
 * @lastmodified	$Date$
 * @license			http://www.opensource.org/licenses/mit-license.php The MIT License
 */

/**
 * Short description for class.
 *
 * Long description for class
 *
 * @package		cake
 * @subpackage	cake.cake.libs.model.dbo
 */
class DboMssql extends DboSource {
/**
 * Enter description here...
 *
 * @var unknown_type
 */
	var $description = "MS SQL DBO Driver";
/**
 * Enter description here...
 *
 * @var unknown_type
 */
	var $startQuote = "[";
/**
 * Enter description here...
 *
 * @var unknown_type
 */
	var $endQuote = "]";
/**
 * Enter description here...
 *
 * @var unknown_type
 */
	var $goofyLimit = true;
 /**
 * Creates a map between field aliases and numeric indexes.  Workaround for the
 * SQL Server driver's 30-character column name limitation.
 *
 * @var array
 */
	var $__fieldMappings = array();
/**
 * Base configuration settings for MS SQL driver
 *
 * @var array
 */
	var $_baseConfig = array(
		'persistent' => true,
		'host' => 'localhost',
		'login' => 'root',
		'password' => '',
		'database' => 'cake',
		'port' => '1433',
		'connect' => 'mssql_pconnect'
	);
/**
 * MS SQL column definition
 *
 * @var array
 */
	var $columns = array(
		'primary_key' => array('name' => 'int(11) DEFAULT NULL auto_increment'),
		'string'	=> array('name'  => 'varchar', 'limit' => '255'),
		'text'		=> array('name' => 'text'),
		'integer'	=> array('name'      => 'int', 'limit'     => '11', 'formatter' => 'intval'),
		'float'		=> array('name'      => 'float', 'formatter' => 'floatval'),
		'datetime'	=> array('name' => 'datetime', 'format'    => 'Y-m-d H:i:s', 'formatter' => 'date'),
		'timestamp' => array('name'      => 'timestamp', 'format'    => 'Y-m-d H:i:s', 'formatter' => 'date'),
		'time'		=> array('name' => 'datetime', 'format'    => 'H:i:s', 'formatter' => 'date'),
		'date'		=> array('name' => 'datetime', 'format'    => 'Y-m-d', 'formatter' => 'date'),
		'binary'	=> array('name' => 'image'),
		'boolean'	=> array('name' => 'bit')
	);

/**
 * MS SQL DBO driver constructor; sets SQL Server error reporting defaults
 *
 * @param array $config Configuration data from app/config/databases.php
 * @return boolean True if connected successfully, false on error
 */
	function __construct($config) {
		mssql_min_message_severity (15);
		mssql_min_error_severity (2);
		return parent::__construct($config);
	}

/**
 * Connects to the database using options in the given configuration array.
 *
 * @return boolean True if the database could be connected, else false
 */
	function connect() {
		$config = $this->config;
		$connect = $config['connect'];

		$os = env('OS');
		if (!empty($os) && strpos($os, 'Windows') !== false) {
			$sep = ',';
		} else {
			$sep = ':';
		}

		$this->connected = false;

		if (is_numeric($config['port'])) {
			$port = $sep . $config['port'];	// Port number
		} elseif ($config['port'] === null) {
			$port = '';						// No port - SQL Server 2005
		} else {
			$port = '\\' . $config['port'];	// Named pipe
		}

		$this->connection = $connect($config['host'] . $port, $config['login'], $config['password']);

		if (mssql_select_db($config['database'], $this->connection)) {
			$this->connected = true;
		}
	}

/**
 * Disconnects from database.
 *
 * @return boolean True if the database could be disconnected, else false
 */
	function disconnect() {
		return @mssql_close($this->connection);
	}
/**
 * Executes given SQL statement.
 *
 * @param string $sql SQL statement
 * @return resource Result resource identifier
 * @access protected
 */
	function _execute($sql) {
		return mssql_query($sql, $this->connection);
	}
/**
 * Returns an array of sources (tables) in the database.
 *
 * @return array Array of tablenames in the database
 */
	function listSources() {
		$cache=parent::listSources();

		if ($cache != null) {
			return $cache;
		}

		$result = $this->fetchAll('SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES');

		if (!$result || empty($result)) {
			return array();
		} else {
			$tables=array();

			foreach($result as $table) {
				$tables[] = $table[0]['TABLE_NAME'];
			}

			parent::listSources($tables);
			return $tables;
		}
	}

/**
 * Returns an array of the fields in given table name.
 *
 * @param Model $model Model object to describe
 * @return array Fields in table. Keys are name and type
 */
	function describe(&$model) {
		$cache = parent::describe($model);

		if ($cache != null) {
			return $cache;
		}

		$fields = false;
		$cols = $this->fetchAll("SELECT COLUMN_NAME as Field, DATA_TYPE as Type, COL_LENGTH('" . $this->fullTableName($model, false) . "', COLUMN_NAME) as Length, IS_NULLABLE As [Null], COLUMN_DEFAULT as [Default], COLUMNPROPERTY(OBJECT_ID('" . $this->fullTableName($model, false) . "'), COLUMN_NAME, 'IsIdentity') as [Key], NUMERIC_SCALE as Size FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = '" . $this->fullTableName($model, false) . "'", false);

		foreach($cols as $column) {
			$fields[] = array(
				'name' => $column[0]['Field'],
				'type' => $this->column($column[0]['Type'] . $column[0]['Length']),
				'null' => up($column[0]['Null']),
				'default' => $column[0]['Default']
			);
		}

		$this->__cacheDescription($this->fullTableName($model, false), $fields);
		return $fields;
	}

/**
 * Returns a quoted name of $data for use in an SQL statement.
 *
 * @param string $data Name (table.field) to be prepared for use in an SQL statement
 * @return string Quoted for MS SQL
 */
	function name($data) {
		if ($data == '*') {
			return '*';
		}

		$pos = strpos($data, '[');
		if ($pos === false) {
			$data = '[' . r('.', '].[', $data) . ']';
		}

		return r(']]', ']', r('[[', '[', $data));
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
				$data = stripslashes(r("'", "''", $data));
			break;
		}
		return "'" . $data . "'";
	}

/**
 * Generates the fields list of an SQL query.
 *
 * @param Model $model
 * @param string $alias Alias tablename
 * @param mixed $fields
 * @return array
 */
	function fields(&$model, $alias, $fields) {
		if (is_array($fields)) {
			$fields = $fields;
		} else {
			if ($fields != null) {
				if (strpos($fields, ',')) {
					$fields = explode(',', $fields);
				} else {
					$fields = array($fields);
				}

				$fields = array_map('trim', $fields);
			} else {
				foreach($model->_tableInfo->value as $field) {
					$fields[] = $field['name'];
				}
			}
		}

		$count = count($fields);

		if ($count >= 1 && $fields[0] != '*' && strpos($fields[0], 'COUNT(*)') === false) {
			for($i = 0; $i < $count; $i++) {
				$dot = strrpos($fields[$i], '.');
				$fieldAlias = count($this->__fieldMappings);

				if ($dot === false) {
					$this->__fieldMappings[] = $fields[$i];
					$fields[$i] = $this->name($alias) . '.' . $this->name($fields[$i]) . ' AS ' . $this->name($alias . '__' . $fieldAlias);
				} else {
					$build = explode('.', $fields[$i]);
					$this->__fieldMappings[] = $build[0];
					$fields[$i] = $this->name($build[0]) . '.' . $this->name($build[1]) . ' AS ' . $this->name($build[0] . '__' . $fieldAlias);
				}
			}
		}
		return $fields;
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
			if ($this->execute('BEGIN TRANSACTION')) {
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
			$this->__transactionStarted = false;
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
 * Removes Identity (primary key) column from update data before returning to parent
 *
 * @param Model $model
 * @param array $fields
 * @param array $values
 * @return array
 */
	function update(&$model, $fields = array(), $values = array()) {
		foreach($fields as $i => $field) {
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
		$error = mssql_get_last_message($this->connection);

		if ($error) {
			if (strpos('changed database', low($error)) !== false) {
				return $error;
			}
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
			return mssql_rows_affected($this->connection);
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
		if ($this->_result) {
			return @mssql_num_rows($this->_result);
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
		$id = $this->fetchAll('SELECT SCOPE_IDENTITY() AS insertID', false);
		return $id[0][0]['insertID'];
	}

/**
 * Returns a limit statement in the correct format for the particular database.
 *
 * @param int $limit Limit of results returned
 * @param int $offset Offset from which to start results
 * @return string SQL limit/offset statement
 */
	function limit($limit, $offset = null) {
		if ($limit) {
			$rt = '';
			if (!strpos(strtolower($limit), 'top') || strpos(strtolower($limit), 'top') === 0) {
				$rt = ' TOP';
			}

			$rt .= ' ' . $limit;
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
		$col               =r(')', '', $real);
		$limit             =null;
		@list($col, $limit)=explode('(', $col);

		if (in_array($col, array('date', 'time', 'datetime', 'timestamp'))) {
			return $col;
		}

		if ($col == 'bit') {
			return 'boolean';
		}

		if (strpos($col, 'int') !== false || $col == 'numeric') {
			return 'integer';
		}

		if (strpos($col, 'char') !== false) {
			return 'string';
		}

		if (strpos($col, 'text') !== false) {
			return 'text';
		}

		if (strpos($col, 'binary') !== false || $col == 'image') {
			return 'binary';
		}

		if (in_array($col, array('float', 'real', 'decimal'))) {
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
		$num_fields = mssql_num_fields($results);
		$index = 0;
		$j = 0;

		while($j < $num_fields) {
			$column = mssql_field_name($results, $j);

			if (strpos($column, '__')) {
				$map = explode('__', $column);
				if (is_numeric($map[1])) {
					$map[1] = $this->__fieldMappings[intval($map[1])];
				}

				$this->map[$index++] = $map;
			} else {
				$this->map[$index++] = array(0, $column);
			}

			$j++;
		}
	}
/**
 * Returns an array of all result rows for a given SQL query.
 * Returns false if no rows matched.
 *
 * @param string $sql SQL statement
 * @param boolean $cache Enables returning/storing cached query results
 * @return array Array of resultset rows, or false if no rows matched
 */
	function fetchAll($sql, $cache = true, $modelName = null) {
		$results = parent::fetchAll($sql, $cache, $modelName);
		$this->__fieldMappings = array();
		return $results;
	}
/**
 * Fetches the next row from the current result set
 *
 * @return unknown
 */
	function fetchResult() {
		if ($row = mssql_fetch_row($this->results)) {
			$resultRow = array();
			$i = 0;

			foreach($row as $index => $field) {
				list($table, $column) = $this->map[$index];
				$resultRow[$table][$column] = $row[$index];
				$i++;
			}

			return $resultRow;
		} else {
			return false;
		}
	}

	function buildSchemaQuery($schema) {
		$search = array('{AUTOINCREMENT}', '{PRIMARY}', '{UNSIGNED}', '{FULLTEXT}', '{BOOLEAN}', '{UTF_8}');

		$replace = array('int(11) not null auto_increment', 'primary key', 'unsigned', 'FULLTEXT',
		'enum (\'true\', \'false\') NOT NULL default \'true\'', '/*!40100 CHARACTER SET utf8 COLLATE utf8_unicode_ci */');

		$query = trim(r($search, $replace, $schema));
		return $query;
	}
}

?>