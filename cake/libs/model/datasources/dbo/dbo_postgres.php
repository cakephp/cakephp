<?php
/* SVN FILE: $Id$ */

/**
 * PostgreSQL layer for DBO.
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
 * @subpackage		cake.cake.libs.model.datasources.dbo
 * @since			CakePHP v 0.9.1.114
 * @version			$Revision$
 * @modifiedby		$LastChangedBy$
 * @lastmodified	$Date$
 * @license			http://www.opensource.org/licenses/mit-license.php The MIT License
 */

/**
 * PostgreSQL layer for DBO.
 *
 * Long description for class
 *
 * @package		cake
 * @subpackage	cake.cake.libs.model.datasources.dbo
 */
class DboPostgres extends DboSource {

	var $description = "PostgreSQL DBO Driver";

	var $_baseConfig = array(
		'connect'	=> 'pg_pconnect',
		'persistent' => true,
		'host' => 'localhost',
		'login' => 'root',
		'password' => '',
		'database' => 'cake',
		'port' => 5432
	);

	var $columns = array(
		'primary_key' => array('name' => 'serial NOT NULL'),
		'string' => array('name'  => 'varchar', 'limit' => '255'),
		'text' => array('name' => 'text'),
		'integer' => array('name' => 'integer'),
		'float' => array('name' => 'float'),
		'datetime' => array('name' => 'timestamp'),
		'timestamp' => array('name' => 'timestamp'),
		'time' => array('name' => 'time'),
		'date' => array('name' => 'date'),
		'binary' => array('name' => 'bytea'),
		'boolean' => array('name' => 'boolean'),
		'number' => array('name' => 'numeric'),
		'inet' => array('name'  => 'inet')
	);

	var $startQuote = '"';

	var $endQuote = '"';

/**
 * Connects to the database using options in the given configuration array.
 *
 * @return True if successfully connected.
 */
	function connect() {

		$config = $this->config;
		$connect = $config['connect'];
		$this->connection = $connect("host='{$config['host']}' port='{$config['port']}' dbname='{$config['database']}' user='{$config['login']}' password='{$config['password']}'");

		if ($this->connection) {
			$this->connected = true;
		} else {
			$this->connected = false;
		}

		if (isset($config['encoding']) && !empty($config['encoding'])) {
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
		$this->connected = !@pg_close($this->connection);
		return !$this->connected;
	}

/**
 * Executes given SQL statement.
 *
 * @param string $sql SQL statement
 * @return resource Result resource identifier
 */
	function _execute($sql) {
		return pg_query($this->connection, $sql);
	}
/**
 * Returns an array of tables in the database. If there are no tables, an error is raised and the application exits.
 *
 * @return array Array of tablenames in the database
 */
	function listSources() {
		$cache = parent::listSources();

		if ($cache != null) {
			return $cache;
		}

		$sql = "SELECT table_name as name FROM INFORMATION_SCHEMA.tables WHERE table_schema = 'public';";
		$result = $this->fetchAll($sql);

		if (!$result) {
			return array();
		} else {
			$tables = array();

			foreach($result as $item) {
				$tables[] = $item[0]['name'];
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
	function &describe(&$model) {

		$cache = parent::describe($model);
		if ($cache != null) {
			return $cache;
		}

		$fields = false;
		$cols = $this->fetchAll("SELECT DISTINCT column_name AS name, data_type AS type, is_nullable AS null, column_default AS default, ordinal_position FROM information_schema.columns WHERE table_name =" . $this->value($model->tablePrefix . $model->table) . " ORDER BY ordinal_position");

		foreach($cols as $column) {
			$colKey = array_keys($column);

			if (isset($column[$colKey[0]]) && !isset($column[0])) {
				$column[0] = $column[$colKey[0]];
			}

			if (isset($column[0])) {
				if (strpos($column[0]['default'], 'nextval(') === 0) {
					$column[0]['default'] = null;
				}
				$fields[] = array(
					'name' => $column[0]['name'],
					'type' => $this->column($column[0]['type']),
					'null' => $column[0]['null'],
					'default' => $column[0]['default']
				);
			}
		}
		$this->__cacheDescription($model->tablePrefix . $model->table, $fields);
		return $fields;
	}

/**
 * Returns a quoted and escaped string of $data for use in an SQL statement.
 *
 * @param string $data String to be prepared for use in an SQL statement
 * @return string Quoted and escaped
 */
	function name($data) {
		if ($data == '*') {
			return '*';
		}

		$pos = strpos($data, '"');

		if ($pos === false) {
			$data = '"' . str_replace('.', '"."', $data) . '"';
		}
		return $data;
	}

/**
 * Returns a quoted and escaped string of $data for use in an SQL statement.
 *
 * @param string $data String to be prepared for use in an SQL statement
 * @param string $column The column into which this data will be inserted
 * @return string Quoted and escaped
 * @todo Add logic that formats/escapes data based on column type
 */
	function value($data, $column = null) {

		$parent = parent::value($data, $column);
		if ($parent != null) {
			return $parent;
		}

		if ($data === null) {
			return 'NULL';
		}

		switch($column) {
			case 'inet':
				if (!strlen($data)){
					return 'DEFAULT';
				} else {
					$data = pg_escape_string($data);
				}
			break;
			case 'integer':
				if ($data === '') {
					return 'DEFAULT';
				} else {
					$data = pg_escape_string($data);
				}
			break;
			case 'binary':
				$data = pg_escape_bytea($data);

			break;
			case 'boolean':
				$data = $this->boolean((bool)$data);
				if ($data === true) {
					$data = '1';
				} elseif ($data === false) {
					$data = '0';
				}
			break;
			default:
				$data = pg_escape_string($data);
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
			if ($this->execute('BEGIN')) {
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
 * Returns a formatted error message from previous database operation.
 *
 * @return string Error message
 */
	function lastError() {
		$last_error = pg_last_error($this->connection);
		if ($last_error) {
			return $last_error;
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
			$return = pg_affected_rows($this->_result);
			return $return;
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
			$return = pg_num_rows($this->_result);
			return $return;
		}
		return false;
	}
/**
 * Returns the ID generated from the previous INSERT operation.
 *
 * @param string $source Name of the database table
 * @param string $field Name of the ID database field. Defaults to "id"
 * @return int
 */
	function lastInsertId($source, $field = 'id') {
		$sql = "SELECT last_value AS max FROM {$source}_{$field}_seq";
		$res = $this->rawQuery($sql);
		$data = $this->fetchRow($res);
		return $data[0]['max'];
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
				if (!preg_match('/^.+\\(.*\\)/', $fields[$i])) {
					$prepend = '';
					if (strpos($fields[$i], 'DISTINCT') !== false) {
						$prepend = 'DISTINCT ';
						$fields[$i] = trim(r('DISTINCT', '', $fields[$i]));
					}

					$dot = strrpos($fields[$i], '.');
					if ($dot === false) {
						$fields[$i] = $prepend . $this->name($alias) . '.' . $this->name($fields[$i]) . ' AS ' . $this->name($alias . '__' . $fields[$i]);
					} else {
						$build = explode('.', $fields[$i]);
						$fields[$i] = $prepend . $this->name($build[0]) . '.' . $this->name($build[1]) . ' AS ' . $this->name($build[0] . '__' . $build[1]);
					}
				}
			}
		}
		return $fields;
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
			if (!strpos(strtolower($limit), 'limit') || strpos(strtolower($limit), 'limit') === 0) {
				$rt = ' LIMIT';
			}

			$rt .= ' ' . $limit;
			if ($offset) {
				$rt .= ' OFFSET ' . $offset;
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

		$col = r(')', '', $real);
		$limit = null;
		@list($col, $limit) = explode('(', $col);

		if (in_array($col, array('date', 'time'))) {
			return $col;
		}
		if (strpos($col, 'timestamp') !== false) {
			return 'datetime';
		}
		if ($col == 'inet') {
			return('inet');
		}
		if ($col == 'boolean') {
			return 'boolean';
		}
		if (strpos($col, 'int') !== false && $col != 'interval') {
			return 'integer';
		}
		if (strpos($col, 'char') !== false) {
				return 'string';
		}
		if (strpos($col, 'text') !== false) {
				return 'text';
		}
		if (strpos($col, 'bytea') !== false) {
				return 'binary';
		}
		if (in_array($col, array('float', 'float4', 'float8', 'double', 'double precision', 'decimal', 'real', 'numeric'))) {
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
		$num_fields = pg_num_fields($results);
		$index = 0;
		$j = 0;

		while($j < $num_fields) {
			$columnName = pg_field_name($results, $j);

			if (strpos($columnName, '__')) {
				$parts = explode('__', $columnName);
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
		if ($row = pg_fetch_row($this->results)) {
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
/**
 * Translates between PHP boolean values and PostgreSQL boolean values
 *
 * @param mixed $data Value to be translated
 * @return mixed Converted boolean value
 */
	function boolean($data) {
		if ($data === true || $data === false) {
			return $data;
		} elseif (is_string($data) && !is_numeric($data)) {
			if (strpos($data, 't') !== false) {
				return true;
			}

			return false;
		} else {
			return (bool)$data;
		}
	}
/**
 * Sets the database encoding
 *
 * @param mixed $enc Database encoding
 * @return boolean True on success, false on failure
 */
	function setEncoding($enc) {
		return pg_set_client_encoding($this->connection, $enc) == 0;
	}
/**
 * Gets the database encoding
 *
 * @return string The database encoding
 */
	function getEncoding() {
		return pg_client_encoding($this->connection);
	}
}

?>