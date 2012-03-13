<?php
/**
 * SQLite layer for DBO
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       cake
 * @subpackage    cake.cake.libs.model.datasources.dbo
 * @since         CakePHP(tm) v 0.9.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

/**
 * DBO implementation for the SQLite DBMS.
 *
 * Long description for class
 *
 * @package       cake
 * @subpackage    cake.cake.libs.model.datasources.dbo
 */
class DboSqlite extends DboSource {

/**
 * Datasource Description
 *
 * @var string
 */
	var $description = "SQLite DBO Driver";

/**
 * Opening quote for quoted identifiers
 *
 * @var string
 */
	var $startQuote = '"';

/**
 * Closing quote for quoted identifiers
 *
 * @var string
 */
	var $endQuote = '"';

/**
 * Keeps the transaction statistics of CREATE/UPDATE/DELETE queries
 *
 * @var array
 * @access protected
 */
	var $_queryStats = array();

/**
 * Base configuration settings for SQLite driver
 *
 * @var array
 */
	var $_baseConfig = array(
		'persistent' => true,
		'database' => null
	);

/**
 * Index of basic SQL commands
 *
 * @var array
 * @access protected
 */
	var $_commands = array(
		'begin'    => 'BEGIN TRANSACTION',
		'commit'   => 'COMMIT TRANSACTION',
		'rollback' => 'ROLLBACK TRANSACTION'
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
		'integer' => array('name' => 'integer', 'limit' => 11, 'formatter' => 'intval'),
		'float' => array('name' => 'float', 'formatter' => 'floatval'),
		'datetime' => array('name' => 'datetime', 'format' => 'Y-m-d H:i:s', 'formatter' => 'date'),
		'timestamp' => array('name' => 'timestamp', 'format' => 'Y-m-d H:i:s', 'formatter' => 'date'),
		'time' => array('name' => 'time', 'format' => 'H:i:s', 'formatter' => 'date'),
		'date' => array('name' => 'date', 'format' => 'Y-m-d', 'formatter' => 'date'),
		'binary' => array('name' => 'blob'),
		'boolean' => array('name' => 'boolean')
	);

/**
 * List of engine specific additional field parameters used on table creating
 *
 * @var array
 * @access public
 */
	var $fieldParameters = array(
		'collate' => array(
			'value' => 'COLLATE',
			'quote' => false,
			'join' => ' ', 
			'column' => 'Collate', 
			'position' => 'afterDefault',
			'options' => array(
				'BINARY', 'NOCASE', 'RTRIM'
			)
		),
	);

/**
 * Connects to the database using config['database'] as a filename.
 *
 * @param array $config Configuration array for connecting
 * @return mixed
 */
	function connect() {
		$config = $this->config;

		if (!$config['persistent']) {
			$this->connection = sqlite_open($config['database']);
		} else {
			$this->connection = sqlite_popen($config['database']);
		}
		$this->connected = is_resource($this->connection);

		if ($this->connected) {
			$this->_execute('PRAGMA count_changes = 1;');
		}
		return $this->connected;
	}

/**
 * Check that SQLite is enabled/installed
 *
 * @return boolean
 */
	function enabled() {
		return extension_loaded('sqlite');
	}
/**
 * Disconnects from database.
 *
 * @return boolean True if the database could be disconnected, else false
 */
	function disconnect() {
		@sqlite_close($this->connection);
		$this->connected = false;
		return $this->connected;
	}

/**
 * Executes given SQL statement.
 *
 * @param string $sql SQL statement
 * @return resource Result resource identifier
 */
	function _execute($sql) {
		$result = sqlite_query($this->connection, $sql);

		if (preg_match('/^(INSERT|UPDATE|DELETE)/', $sql)) {
			$this->resultSet($result);
			list($this->_queryStats) = $this->fetchResult();
		}
		return $result;
	}

/**
 * Overrides DboSource::execute() to correctly handle query statistics
 *
 * @param string $sql
 * @return unknown
 */
	function execute($sql) {
		$result = parent::execute($sql);
		$this->_queryStats = array();
		return $result;
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
		$result = $this->fetchAll("SELECT name FROM sqlite_master WHERE type='table' ORDER BY name;", false);

		if (empty($result)) {
			return array();
		} else {
			$tables = array();
			foreach ($result as $table) {
				$tables[] = $table[0]['name'];
			}
			parent::listSources($tables);
			return $tables;
		}
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
		$result = $this->fetchAll('PRAGMA table_info(' . $this->fullTableName($model) . ')');

		foreach ($result as $column) {
			$fields[$column[0]['name']] = array(
				'type' => $this->column($column[0]['type']),
				'null' => !$column[0]['notnull'],
				'default' => $column[0]['dflt_value'],
				'length' => $this->length($column[0]['type'])
			);
			if ($column[0]['pk'] == 1) {
				$colLength = $this->length($column[0]['type']);
				$fields[$column[0]['name']] = array(
					'type' => $fields[$column[0]['name']]['type'],
					'null' => false,
					'default' => $column[0]['dflt_value'],
					'key' => $this->index['PRI'],
					'length'=> ($colLength != null) ? $colLength : 11
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
	function value($data, $column = null, $safe = false) {
		$parent = parent::value($data, $column, $safe);

		if ($parent != null) {
			return $parent;
		}
		if ($data === null) {
			return 'NULL';
		}
		if ($data === '' && $column !== 'integer' && $column !== 'float' && $column !== 'boolean') {
			return  "''";
		}
		switch ($column) {
			case 'boolean':
				$data = $this->boolean((bool)$data);
			break;
			case 'integer':
			case 'float':
				if ($data === '') {
					return 'NULL';
				}
			default:
				$data = sqlite_escape_string($data);
			break;
		}
		return "'" . $data . "'";
	}

/**
 * Generates and executes an SQL UPDATE statement for given model, fields, and values.
 *
 * @param Model $model
 * @param array $fields
 * @param array $values
 * @param mixed $conditions
 * @return array
 */
	function update(&$model, $fields = array(), $values = null, $conditions = null) {
		if (empty($values) && !empty($fields)) {
			foreach ($fields as $field => $value) {
				if (strpos($field, $model->alias . '.') !== false) {
					unset($fields[$field]);
					$field = str_replace($model->alias . '.', "", $field);
					$field = str_replace($model->alias . '.', "", $field);
					$fields[$field] = $value;
				}
			}
		}
		$result = parent::update($model, $fields, $values, $conditions);
		return $result;
	}

/**
 * Deletes all the records in a table and resets the count of the auto-incrementing
 * primary key, where applicable.
 *
 * @param mixed $table A string or model class representing the table to be truncated
 * @return boolean	SQL TRUNCATE TABLE statement, false if not applicable.
 * @access public
 */
	function truncate($table) {
		return $this->execute('DELETE From ' . $this->fullTableName($table));
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
 * @return integer Number of affected rows
 */
	function lastAffected() {
		if (!empty($this->_queryStats)) {
			foreach (array('rows inserted', 'rows updated', 'rows deleted') as $key) {
				if (array_key_exists($key, $this->_queryStats)) {
					return $this->_queryStats[$key];
				}
			}
		}
		return false;
	}

/**
 * Returns number of rows in previous resultset. If no previous resultset exists,
 * this returns false.
 *
 * @return integer Number of rows in resultset
 */
	function lastNumRows() {
		if ($this->hasResult()) {
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

		$col = strtolower(str_replace(')', '', $real));
		$limit = null;
		if (strpos($col, '(') !== false) {
			list($col, $limit) = explode('(', $col);
		}

		if (in_array($col, array('text', 'integer', 'float', 'boolean', 'timestamp', 'date', 'datetime', 'time'))) {
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
		$fieldCount = sqlite_num_fields($results);
		$index = $j = 0;

		while ($j < $fieldCount) {
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
 * @param integer $limit Limit of results returned
 * @param integer $offset Offset from which to start results
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
 * Generate a database-native column schema string
 *
 * @param array $column An array structured like the following: array('name'=>'value', 'type'=>'value'[, options]),
 *                      where options can be 'default', 'length', or 'key'.
 * @return string
 */
	function buildColumn($column) {
		$name = $type = null;
		$column = array_merge(array('null' => true), $column);
		extract($column);

		if (empty($name) || empty($type)) {
			trigger_error(__('Column name or type not defined in schema', true), E_USER_WARNING);
			return null;
		}

		if (!isset($this->columns[$type])) {
			trigger_error(sprintf(__('Column type %s does not exist', true), $type), E_USER_WARNING);
			return null;
		}

		$real = $this->columns[$type];
		$out = $this->name($name) . ' ' . $real['name'];
		if (isset($column['key']) && $column['key'] == 'primary' && $type == 'integer') {
			return $this->name($name) . ' ' . $this->columns['primary_key']['name'];
		}
		return parent::buildColumn($column);
	}

/**
 * Sets the database encoding
 *
 * @param string $enc Database encoding
 */
	function setEncoding($enc) {
		if (!in_array($enc, array("UTF-8", "UTF-16", "UTF-16le", "UTF-16be"))) {
			return false;
		}
		return $this->_execute("PRAGMA encoding = \"{$enc}\"") !== false;
	}

/**
 * Gets the database encoding
 *
 * @return string The database encoding
 */
	function getEncoding() {
		return $this->fetchRow('PRAGMA encoding');
	}

/**
 * Removes redundant primary key indexes, as they are handled in the column def of the key.
 *
 * @param array $indexes
 * @param string $table
 * @return string
 */
	function buildIndex($indexes, $table = null) {
		$join = array();

		foreach ($indexes as $name => $value) {

			if ($name == 'PRIMARY') {
				continue;
			}
			$out = 'CREATE ';

			if (!empty($value['unique'])) {
				$out .= 'UNIQUE ';
			}
			if (is_array($value['column'])) {
				$value['column'] = implode(', ', array_map(array(&$this, 'name'), $value['column']));
			} else {
				$value['column'] = $this->name($value['column']);
			}
			$out .= "INDEX {$name} ON {$table}({$value['column']});";
			$join[] = $out;
		}
		return $join;
	}

/**
 * Overrides DboSource::index to handle SQLite indexe introspection
 * Returns an array of the indexes in given table name.
 *
 * @param string $model Name of model to inspect
 * @return array Fields in table. Keys are column and unique
 */
	function index(&$model) {
		$index = array();
		$table = $this->fullTableName($model);
		if ($table) {
			$indexes = $this->query('PRAGMA index_list(' . $table . ')');
			$tableInfo = $this->query('PRAGMA table_info(' . $table . ')');
			foreach ($indexes as $i => $info) {
				$key = array_pop($info);
				$keyInfo = $this->query('PRAGMA index_info("' . $key['name'] . '")');
				foreach ($keyInfo as $keyCol) {
					if (!isset($index[$key['name']])) {
						$col = array();
						if (preg_match('/autoindex/', $key['name'])) {
							$key['name'] = 'PRIMARY';
						}
						$index[$key['name']]['column'] = $keyCol[0]['name'];
						$index[$key['name']]['unique'] = intval($key['unique'] == 1);
					} else {
						if (!is_array($index[$key['name']]['column'])) {
							$col[] = $index[$key['name']]['column'];
						}
						$col[] = $keyCol[0]['name'];
						$index[$key['name']]['column'] = $col;
					}
				}
			}
		}
		return $index;
	}

/**
 * Overrides DboSource::renderStatement to handle schema generation with SQLite-style indexes
 *
 * @param string $type
 * @param array $data
 * @return string
 */
	function renderStatement($type, $data) {
		switch (strtolower($type)) {
			case 'schema':
				extract($data);

				foreach (array('columns', 'indexes') as $var) {
					if (is_array(${$var})) {
						${$var} = "\t" . implode(",\n\t", array_filter(${$var}));
					}
				}
				return "CREATE TABLE {$table} (\n{$columns});\n{$indexes}";
			break;
			default:
				return parent::renderStatement($type, $data);
			break;
		}
	}
}
