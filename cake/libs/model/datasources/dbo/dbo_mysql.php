<?php
/**
 * MySQL layer for DBO
 *
 * PHP 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       cake
 * @subpackage    cake.cake.libs.model.datasources.dbo
 * @since         CakePHP(tm) v 0.10.5.1790
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

/**
 * MySQL DBO driver object
 *
 * Provides connection and SQL generation for MySQL RDMS
 *
 * @package       cake
 * @subpackage    cake.cake.libs.model.datasources.dbo
 */
class DboMysql extends DboSource {

/**
 * Datasource description
 *
 * @var string
 */
	public $description = "MySQL DBO Driver";

/**
 * Base configuration settings for MySQL driver
 *
 * @var array
 */
	protected $_baseConfig = array(
		'persistent' => true,
		'host' => 'localhost',
		'login' => 'root',
		'password' => '',
		'database' => 'cake',
		'port' => '3306'
	);

/**
 * Reference to the PDO object connection
 *
 * @var PDO $_connection
 */
	protected $_connection = null;

/**
 * Start quote
 *
 * @var string
 */
	public $startQuote = "`";

/**
 * End quote
 *
 * @var string
 */
	public $endQuote = "`";

/**
 * use alias for update and delete. Set to true if version >= 4.1
 *
 * @var boolean
 * @access protected
 */
	protected $_useAlias = true;

/**
 * Index of basic SQL commands
 *
 * @var array
 * @access protected
 */
	protected $_commands = array(
		'begin'    => 'START TRANSACTION',
		'commit'   => 'COMMIT',
		'rollback' => 'ROLLBACK'
	);

/**
 * List of engine specific additional field parameters used on table creating
 *
 * @var array
 * @access public
 */
	public $fieldParameters = array(
		'charset' => array('value' => 'CHARACTER SET', 'quote' => false, 'join' => ' ', 'column' => false, 'position' => 'beforeDefault'),
		'collate' => array('value' => 'COLLATE', 'quote' => false, 'join' => ' ', 'column' => 'Collation', 'position' => 'beforeDefault'),
		'comment' => array('value' => 'COMMENT', 'quote' => true, 'join' => ' ', 'column' => 'Comment', 'position' => 'afterDefault')
	);

/**
 * List of table engine specific parameters used on table creating
 *
 * @var array
 * @access public
 */
	public $tableParameters = array(
		'charset' => array('value' => 'DEFAULT CHARSET', 'quote' => false, 'join' => '=', 'column' => 'charset'),
		'collate' => array('value' => 'COLLATE', 'quote' => false, 'join' => '=', 'column' => 'Collation'),
		'engine' => array('value' => 'ENGINE', 'quote' => false, 'join' => '=', 'column' => 'Engine')
	);

/**
 * MySQL column definition
 *
 * @var array
 */
	public $columns = array(
		'primary_key' => array('name' => 'NOT NULL AUTO_INCREMENT'),
		'string' => array('name' => 'varchar', 'limit' => '255'),
		'text' => array('name' => 'text'),
		'integer' => array('name' => 'int', 'limit' => '11', 'formatter' => 'intval'),
		'float' => array('name' => 'float', 'formatter' => 'floatval'),
		'datetime' => array('name' => 'datetime', 'format' => 'Y-m-d H:i:s', 'formatter' => 'date'),
		'timestamp' => array('name' => 'timestamp', 'format' => 'Y-m-d H:i:s', 'formatter' => 'date'),
		'time' => array('name' => 'time', 'format' => 'H:i:s', 'formatter' => 'date'),
		'date' => array('name' => 'date', 'format' => 'Y-m-d', 'formatter' => 'date'),
		'binary' => array('name' => 'blob'),
		'boolean' => array('name' => 'tinyint', 'limit' => '1')
	);

/**
 * Connects to the database using options in the given configuration array.
 *
 * @return boolean True if the database could be connected, else false
 */
	function connect() {
		$config = $this->config;
		$this->connected = false;
		try {
			$flags = array(
				PDO::ATTR_PERSISTENT => $config['persistent'],
				PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true
			);
			if (!empty($config['encoding'])) {
				$flags[PDO::MYSQL_ATTR_INIT_COMMAND] = 'SET NAMES ' . $config['encoding'];
			}
			$this->_connection = new PDO(
				"mysql:{$config['host']};port={$config['port']};dbname={$config['database']}",
				$config['login'],
				$config['password'],
				$flags
			);
			$this->connected = true;
		} catch (PDOException $e) {
			$this->errors[] = $e->getMessage();
		}

		$this->_useAlias = (bool)version_compare($this->getVersion(), "4.1", ">=");

		return $this->connected;
	}

	public function getConnection() {
		return $this->_connection;
	}

/**
 * Check whether the MySQL extension is installed/loaded
 *
 * @return boolean
 */
	function enabled() {
		return in_array('mysql', PDO::getAvailableDrivers());
	}
/**
 * Disconnects from database.
 *
 * @return boolean True if the database could be disconnected, else false
 */
	function disconnect() {
		if (is_a($this->_result, 'PDOStatement')) {
			$this->_result->closeCursor();
		}
		unset($this->_connection);
		$this->connected = false;
		return !$this->connected;
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
		$result = $this->_execute('SHOW TABLES FROM ' . $this->config['database']);

		if (!$result) {
			return array();
		} else {
			$tables = array();

			while ($line = $result->fetch()) {
				$tables[] = $line[0];
			}
			parent::listSources($tables);
			return $tables;
		}
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
			return $this->_connection->quote($data, PDO::PARAM_NULL);
		}
		if ($data === '' && $column !== 'integer' && $column !== 'float' && $column !== 'boolean') {
			return $this->_connection->quote($data, PDO::PARAM_STR);
		}
		if (empty($column)) {
			$column = $this->introspectType($data);
		}

		switch ($column) {
			case 'boolean':
				return $this->boolean((bool)$data);
			break;
			case 'integer':
			case 'float':
				if ($data === '') {
					return 'NULL';
				}
				if (is_float($data)) {
					return sprintf('%F', $data);
				}
				if ((is_int($data) || $data === '0') || (
					is_numeric($data) && strpos($data, ',') === false &&
					$data[0] != '0' && strpos($data, 'e') === false)
				) {
					return $data;
				}
			default:
				return $this->_connection->quote($data, PDO::PARAM_STR);
			break;
		}
	}

/**
 * Returns a formatted error message from previous database operation.
 *
 * @return string Error message with error number
 */
	function lastError() {
		if ($this->hasResult()) {
			$error = $this->_result->errorInfo();
			if (empty($error)) {
				$error;
			}
			return $error[1] . ': ' . $error[2];
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
		if ($this->hasResult()) {
			return $this->_result->rowCount();
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
			return mysql_num_rows($this->_result);
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
 * Enter description here...
 *
 * @param unknown_type $results
 */
	function resultSet(&$results) {
		if (isset($this->results) && is_resource($this->results) && $this->results != $results) {
			mysql_free_result($this->results);
		}
		$this->results =& $results;
		$this->map = array();
		$numFields = mysql_num_fields($results);
		$index = 0;
		$j = 0;

		while ($j < $numFields) {
			$column = mysql_fetch_field($results, $j);
			if (!empty($column->table) && strpos($column->name, $this->virtualFieldSeparator) === false) {
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
		if ($row = mysql_fetch_row($this->results)) {
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
 * Gets the database encoding
 *
 * @return string The database encoding
 */
	public function getEncoding() {
		return $this->_execute('SHOW VARIABLES LIKE ?', array('character_set_client'))->fetchObject()->Value;
	}

/**
 * Gets the version string of the database server
 *
 * @return string The database encoding
 */
	public function getVersion() {
		return $this->_connection->getAttribute(PDO::ATTR_SERVER_VERSION);
	}

/**
 * Query charset by collation
 *
 * @param string $name Collation name
 * @return string Character set name
 */
	public function getCharsetName($name) {
		if ((bool)version_compare($this->getVersion(), "5", ">=")) {
			$r = $this->_execute('SELECT CHARACTER_SET_NAME FROM INFORMATION_SCHEMA.COLLATIONS WHERE COLLATION_NAME = ?', array($name));
			$cols = $r->fetchArray();
			if (isset($cols['COLLATIONS']['CHARACTER_SET_NAME'])) {
				return $cols['COLLATIONS']['CHARACTER_SET_NAME'];
			}
		}
		return false;
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
		$cols = $this->query('SHOW FULL COLUMNS FROM ' . $this->fullTableName($model));

		foreach ($cols as $column) {
			$colKey = array_keys($column);
			if (isset($column[$colKey[0]]) && !isset($column[0])) {
				$column[0] = $column[$colKey[0]];
			}
			if (isset($column[0])) {
				$fields[$column[0]['Field']] = array(
					'type' => $this->column($column[0]['Type']),
					'null' => ($column[0]['Null'] == 'YES' ? true : false),
					'default' => $column[0]['Default'],
					'length' => $this->length($column[0]['Type']),
				);
				if (!empty($column[0]['Key']) && isset($this->index[$column[0]['Key']])) {
					$fields[$column[0]['Field']]['key'] = $this->index[$column[0]['Key']];
				}
				foreach ($this->fieldParameters as $name => $value) {
					if (!empty($column[0][$value['column']])) {
						$fields[$column[0]['Field']][$name] = $column[0][$value['column']];
					}
				}
				if (isset($fields[$column[0]['Field']]['collate'])) {
					$charset = $this->getCharsetName($fields[$column[0]['Field']]['collate']);
					if ($charset) {
						$fields[$column[0]['Field']]['charset'] = $charset;
					}
				}
			}
		}
		$this->__cacheDescription($this->fullTableName($model, false), $fields);
		return $fields;
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
		if (!$this->_useAlias) {
			return parent::update($model, $fields, $values, $conditions);
		}

		if ($values == null) {
			$combined = $fields;
		} else {
			$combined = array_combine($fields, $values);
		}

		$alias = $joins = false;
		$fields = $this->_prepareUpdateFields($model, $combined, empty($conditions), !empty($conditions));
		$fields = implode(', ', $fields);
		$table = $this->fullTableName($model);

		if (!empty($conditions)) {
			$alias = $this->name($model->alias);
			if ($model->name == $model->alias) {
				$joins = implode(' ', $this->_getJoins($model));
			}
		}
		$conditions = $this->conditions($this->defaultConditions($model, $conditions, $alias), true, true, $model);

		if ($conditions === false) {
			return false;
		}

		if (!$this->execute($this->renderStatement('update', compact('table', 'alias', 'joins', 'fields', 'conditions')))) {
			$model->onError();
			return false;
		}
		return true;
	}

/**
 * Generates and executes an SQL DELETE statement for given id/conditions on given model.
 *
 * @param Model $model
 * @param mixed $conditions
 * @return boolean Success
 */
	function delete(&$model, $conditions = null) {
		if (!$this->_useAlias) {
			return parent::delete($model, $conditions);
		}
		$alias = $this->name($model->alias);
		$table = $this->fullTableName($model);
		$joins = implode(' ', $this->_getJoins($model));

		if (empty($conditions)) {
			$alias = $joins = false;
		}
		$conditions = $this->conditions($this->defaultConditions($model, $conditions, $alias), true, true, $model);

		if ($conditions === false) {
			return false;
		}

		if ($this->execute($this->renderStatement('delete', compact('alias', 'table', 'joins', 'conditions'))) === false) {
			$model->onError();
			return false;
		}
		return true;
	}

/**
 * Sets the database encoding
 *
 * @param string $enc Database encoding
 */
	function setEncoding($enc) {
		return $this->_execute('SET NAMES ' . $enc) != false;
	}

/**
 * Returns an array of the indexes in given datasource name.
 *
 * @param string $model Name of model to inspect
 * @return array Fields in table. Keys are column and unique
 */
	function index($model) {
		$index = array();
		$table = $this->fullTableName($model);
		$old = version_compare($this->getVersion(), '4.1', '<=');
		if ($table) {
			$indices = $this->_execute('SHOW INDEX FROM ' . $table);
			while ($idx = $indices->fetch()) {
				if ($old) {
					$idx = (object) current((array)$idx);
				}
				if (!isset($index[$idx->Key_name]['column'])) {
					$col = array();
					$index[$idx->Key_name]['column'] = $idx->Column_name;
					$index[$idx->Key_name]['unique'] = intval($idx->Non_unique == 0);
				} else {
					if (!empty($index[$idx->Key_name]['column']) && !is_array($index[$idx->Key_name]['column'])) {
						$col[] = $index[$idx->Key_name]['column'];
					}
					$col[] = $idx->Column_name;
					$index[$idx->Key_name]['column'] = $col;
				}
			}
		}
		return $index;
	}

/**
 * Generate a MySQL Alter Table syntax for the given Schema comparison
 *
 * @param array $compare Result of a CakeSchema::compare()
 * @return array Array of alter statements to make.
 */
	function alterSchema($compare, $table = null) {
		if (!is_array($compare)) {
			return false;
		}
		$out = '';
		$colList = array();
		foreach ($compare as $curTable => $types) {
			$indexes = $tableParameters = $colList = array();
			if (!$table || $table == $curTable) {
				$out .= 'ALTER TABLE ' . $this->fullTableName($curTable) . " \n";
				foreach ($types as $type => $column) {
					if (isset($column['indexes'])) {
						$indexes[$type] = $column['indexes'];
						unset($column['indexes']);
					}
					if (isset($column['tableParameters'])) {
						$tableParameters[$type] = $column['tableParameters'];
						unset($column['tableParameters']);
					}
					switch ($type) {
						case 'add':
							foreach ($column as $field => $col) {
								$col['name'] = $field;
								$alter = 'ADD ' . $this->buildColumn($col);
								if (isset($col['after'])) {
									$alter .= ' AFTER ' . $this->name($col['after']);
								}
								$colList[] = $alter;
							}
						break;
						case 'drop':
							foreach ($column as $field => $col) {
								$col['name'] = $field;
								$colList[] = 'DROP ' . $this->name($field);
							}
						break;
						case 'change':
							foreach ($column as $field => $col) {
								if (!isset($col['name'])) {
									$col['name'] = $field;
								}
								$colList[] = 'CHANGE ' . $this->name($field) . ' ' . $this->buildColumn($col);
							}
						break;
					}
				}
				$colList = array_merge($colList, $this->_alterIndexes($curTable, $indexes));
				$colList = array_merge($colList, $this->_alterTableParameters($curTable, $tableParameters));
				$out .= "\t" . join(",\n\t", $colList) . ";\n\n";
			}
		}
		return $out;
	}

/**
 * Generate a MySQL "drop table" statement for the given Schema object
 *
 * @param object $schema An instance of a subclass of CakeSchema
 * @param string $table Optional.  If specified only the table name given will be generated.
 *                      Otherwise, all tables defined in the schema are generated.
 * @return string
 */
	function dropSchema($schema, $table = null) {
		if (!is_a($schema, 'CakeSchema')) {
			trigger_error(__('Invalid schema object'), E_USER_WARNING);
			return null;
		}
		$out = '';
		foreach ($schema->tables as $curTable => $columns) {
			if (!$table || $table == $curTable) {
				$out .= 'DROP TABLE IF EXISTS ' . $this->fullTableName($curTable) . ";\n";
			}
		}
		return $out;
	}

/**
 * Generate MySQL table parameter alteration statementes for a table.
 *
 * @param string $table Table to alter parameters for.
 * @param array $parameters Parameters to add & drop.
 * @return array Array of table property alteration statementes.
 * @todo Implement this method.
 */
	function _alterTableParameters($table, $parameters) {
		if (isset($parameters['change'])) {
			return $this->buildTableParameters($parameters['change']);
		}
		return array();
	}

/**
 * Generate MySQL index alteration statements for a table.
 *
 * @param string $table Table to alter indexes for
 * @param array $new Indexes to add and drop
 * @return array Index alteration statements
 */
	function _alterIndexes($table, $indexes) {
		$alter = array();
		if (isset($indexes['drop'])) {
			foreach($indexes['drop'] as $name => $value) {
				$out = 'DROP ';
				if ($name == 'PRIMARY') {
					$out .= 'PRIMARY KEY';
				} else {
					$out .= 'KEY ' . $name;
				}
				$alter[] = $out;
			}
		}
		if (isset($indexes['add'])) {
			foreach ($indexes['add'] as $name => $value) {
				$out = 'ADD ';
				if ($name == 'PRIMARY') {
					$out .= 'PRIMARY ';
					$name = null;
				} else {
					if (!empty($value['unique'])) {
						$out .= 'UNIQUE ';
					}
				}
				if (is_array($value['column'])) {
					$out .= 'KEY '. $name .' (' . implode(', ', array_map(array(&$this, 'name'), $value['column'])) . ')';
				} else {
					$out .= 'KEY '. $name .' (' . $this->name($value['column']) . ')';
				}
				$alter[] = $out;
			}
		}
		return $alter;
	}

/**
 * Inserts multiple values into a table
 *
 * @param string $table
 * @param string $fields
 * @param array $values
 */
	function insertMulti($table, $fields, $values) {
		$table = $this->fullTableName($table);
		if (is_array($fields)) {
			$fields = implode(', ', array_map(array(&$this, 'name'), $fields));
		}
		$values = implode(', ', $values);
		$this->query("INSERT INTO {$table} ({$fields}) VALUES {$values}");
	}
/**
 * Returns an detailed array of sources (tables) in the database.
 *
 * @param string $name Table name to get parameters 
 * @return array Array of tablenames in the database
 */
	function listDetailedSources($name = null) {
		$condition = '';
		if (is_string($name)) {
			$condition = ' LIKE ' . $this->value($name);
		}
		$result = $this->query('SHOW TABLE STATUS FROM ' . $this->name($this->config['database']) . $condition . ';');
		if (!$result) {
			return array();
		} else {
			$tables = array();
			foreach ($result as $row) {
				$tables[$row['TABLES']['Name']] = $row['TABLES'];
				if (!empty($row['TABLES']['Collation'])) {
					$charset = $this->getCharsetName($row['TABLES']['Collation']);
					if ($charset) {
						$tables[$row['TABLES']['Name']]['charset'] = $charset;
					}
				}
			}
			if (is_string($name)) {
				return $tables[$name];
			}
			return $tables;
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
}
