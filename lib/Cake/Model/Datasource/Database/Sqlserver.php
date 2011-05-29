<?php
/**
 * MS SQL Server layer for DBO
 *
 * PHP 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2011, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2011, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       cake.libs.model.datasources.dbo
 * @since         CakePHP(tm) v 0.10.5.1790
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

App::uses('DboSource', 'Model/Datasource');

/**
 * MS SQL layer for DBO
 *
 * Long description for class
 *
 * @package       cake.libs.model.datasources.dbo
 */
class Sqlserver extends DboSource {

/**
 * Driver description
 *
 * @var string
 */
	public $description = "SQL Server DBO Driver";

/**
 * Starting quote character for quoted identifiers
 *
 * @var string
 */
	public $startQuote = "[";

/**
 * Ending quote character for quoted identifiers
 *
 * @var string
 */
	public $endQuote = "]";

/**
 * Creates a map between field aliases and numeric indexes.  Workaround for the
 * SQL Server driver's 30-character column name limitation.
 *
 * @var array
 */
	protected $_fieldMappings = array();

/**
 * Storing the last affected value
 *
 * @var mixed
 */
	protected $_lastAffected = false;

/**
 * Base configuration settings for MS SQL driver
 *
 * @var array
 */
	protected $_baseConfig = array(
		'persistent' => true,
		'host' => '(local)\sqlexpress',
		'login' => '',
		'password' => '',
		'database' => 'cake'
	);

/**
 * MS SQL column definition
 *
 * @var array
 */
	public $columns = array(
		'primary_key' => array('name' => 'IDENTITY (1, 1) NOT NULL'),
		'string'	=> array('name' => 'varchar', 'limit' => '255'),
		'text'		=> array('name' => 'text'),
		'integer'	=> array('name' => 'int', 'formatter' => 'intval'),
		'float'		=> array('name' => 'numeric', 'formatter' => 'floatval'),
		'datetime'	=> array('name' => 'datetime', 'format' => 'Y-m-d H:i:s', 'formatter' => 'date'),
		'timestamp' => array('name' => 'timestamp', 'format' => 'Y-m-d H:i:s', 'formatter' => 'date'),
		'time'		=> array('name' => 'datetime', 'format' => 'H:i:s', 'formatter' => 'date'),
		'date'		=> array('name' => 'datetime', 'format' => 'Y-m-d', 'formatter' => 'date'),
		'binary'	=> array('name' => 'image'),
		'boolean'	=> array('name' => 'bit')
	);

/**
 * Index of basic SQL commands
 *
 * @var array
 */
	protected $_commands = array(
		'begin'    => 'BEGIN TRANSACTION',
		'commit'   => 'COMMIT',
		'rollback' => 'ROLLBACK'
	);

/**
 * Define if the last query had error
 *
 * @var string
 */
	private $__lastQueryHadError = false;

/**
 * Connects to the database using options in the given configuration array.
 *
 * @return boolean True if the database could be connected, else false
 */
	public function connect() {
		$config = $this->config;
		$this->connected = false;
		try {
			$flags = array(PDO::ATTR_PERSISTENT => $config['persistent']);
			if (!empty($config['encoding'])) {
				$flags[PDO::SQLSRV_ATTR_ENCODING] = $config['encoding'];
			}
			$this->_connection = new PDO(
				"sqlsrv:server={$config['host']};Database={$config['database']}",
				$config['login'],
				$config['password'],
				$flags
			);
			$this->connected = true;
		} catch (PDOException $e) {
			throw new MissingConnectionException(array('class' => $e->getMessage()));
		}

//		$this->_execute("SET DATEFORMAT ymd");
		return $this->connected;
	}

/**
 * Check that PDO SQL Server is installed/loaded
 *
 * @return boolean
 */
	public function enabled() {
		return in_array('sqlsrv', PDO::getAvailableDrivers());
	}

/**
 * Returns an array of sources (tables) in the database.
 *
 * @return array Array of tablenames in the database
 */
	public function listSources() {
		$cache = parent::listSources();
		if ($cache !== null) {
			return $cache;
		}
		$result = $this->_execute("SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_TYPE='BASE TABLE'");

		if (!$result) {
			$result->closeCursor();
			return array();
		} else {
			$tables = array();

			while ($line = $result->fetch()) {
				$tables[] = $line[0];
			}

			$result->closeCursor();
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
	public function describe($model) {
		$cache = parent::describe($model);
		if ($cache != null) {
			return $cache;
		}
		$fields = false;
		$table = $this->fullTableName($model, false);
		$cols = $this->_execute("SELECT COLUMN_NAME as Field, DATA_TYPE as Type, COL_LENGTH('" . $table . "', COLUMN_NAME) as Length, IS_NULLABLE As [Null], COLUMN_DEFAULT as [Default], COLUMNPROPERTY(OBJECT_ID('" . $table . "'), COLUMN_NAME, 'IsIdentity') as [Key], NUMERIC_SCALE as Size FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = '" . $table . "'");
		if (!$cols) {
			throw new CakeException(__d('cake_dev', 'Could not describe table for %s', $model->name));
		}

		foreach ($cols as $column) {
			$field = $column->Field;
			$fields[$field] = array(
				'type' => $this->column($column->Type),
				'null' => ($column->Null === 'YES' ? true : false),
				'default' => preg_replace("/^[(]{1,2}'?([^')]*)?'?[)]{1,2}$/", "$1", $column->Default),
				'length' => intval($column->Length),
				'key' => ($column->Key == '1') ? 'primary' : false
			);

			if ($fields[$field]['default'] === 'null') {
				$fields[$field]['default'] = null;
			} else {
				$this->value($fields[$field]['default'], $fields[$field]['type']);
			}

			if ($fields[$field]['key'] !== false && $fields[$field]['type'] == 'integer') {
				$fields[$field]['length'] = 11;
			} elseif ($fields[$field]['key'] === false) {
				unset($fields[$field]['key']);
			}
			if (in_array($fields[$field]['type'], array('date', 'time', 'datetime', 'timestamp'))) {
				$fields[$field]['length'] = null;
			}
		}
		$this->__cacheDescription($table, $fields);
		$cols->closeCursor();
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
	public function value($data, $column = null, $safe = false) {
		$parent = parent::value($data, $column, $safe);

		if ($column === 'float' && strpos($data, '.') !== false) {
			return rtrim($data, '0');
		}
		if ($parent === "''" && ($column === null || $column !== 'string')) {
			return 'NULL';
		}
		if ($parent != null) {
			return $parent;
		}
		if ($data === null) {
			return 'NULL';
		}
		if (in_array($column, array('integer', 'float', 'binary')) && $data === '') {
			return 'NULL';
		}
		if ($data === '') {
			return "''";
		}

		switch ($column) {
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

		if (in_array($column, array('integer', 'float', 'binary')) && is_numeric($data)) {
			return $data;
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
	public function fields($model, $alias = null, $fields = array(), $quote = true) {
		if (empty($alias)) {
			$alias = $model->alias;
		}
		$fields = parent::fields($model, $alias, $fields, false);
		$count = count($fields);

		if ($count >= 1 && strpos($fields[0], 'COUNT(*)') === false) {
			$result = array();
			for ($i = 0; $i < $count; $i++) {
				$prepend = '';

				if (strpos($fields[$i], 'DISTINCT') !== false) {
					$prepend = 'DISTINCT ';
					$fields[$i] = trim(str_replace('DISTINCT', '', $fields[$i]));
				}
				$fieldAlias = count($this->_fieldMappings);

				if (!preg_match('/\s+AS\s+/i', $fields[$i])) {
					if (substr($fields[$i], -1) == '*') {
						if (strpos($fields[$i], '.') !== false && $fields[$i] != $alias . '.*') {
							$build = explode('.', $fields[$i]);
							$AssociatedModel = $model->{$build[0]};
						} else {
							$AssociatedModel = $model;
						}

						$_fields = $this->fields($AssociatedModel, $AssociatedModel->alias, array_keys($AssociatedModel->schema()));
						$result = array_merge($result, $_fields);
						continue;
					}

					if (strpos($fields[$i], '.') === false) {
						$this->_fieldMappings[$alias . '__' . $fieldAlias] = $alias . '.' . $fields[$i];
						$fieldName  = $this->name($alias . '.' . $fields[$i]);
						$fieldAlias = $this->name($alias . '__' . $fieldAlias);
					} else {
						$build = explode('.', $fields[$i]);
						$this->_fieldMappings[$build[0] . '__' . $fieldAlias] = $fields[$i];
						$fieldName  = $this->name($build[0] . '.' . $build[1]);
						$fieldAlias = $this->name(preg_replace("/^\[(.+)\]$/", "$1", $build[0]) . '__' . $fieldAlias);
					}
					if ($model->getColumnType($fields[$i]) == 'datetime') {
						$fieldName = "CONVERT(VARCHAR(20), {$fieldName}, 20)";
					}
					$fields[$i] =  "{$fieldName} AS {$fieldAlias}";
				}
				$result[] = $prepend . $fields[$i];
			}
			return $result;
		} else {
			return $fields;
		}
	}

/**
 * Generates and executes an SQL INSERT statement for given model, fields, and values.
 * Removes Identity (primary key) column from update data before returning to parent, if
 * value is empty.
 *
 * @param Model $model
 * @param array $fields
 * @param array $values
 * @param mixed $conditions
 * @return array
 */
	public function create($model, $fields = null, $values = null) {
		if (!empty($values)) {
			$fields = array_combine($fields, $values);
		}
		$primaryKey = $this->_getPrimaryKey($model);

		if (array_key_exists($primaryKey, $fields)) {
			if (empty($fields[$primaryKey])) {
				unset($fields[$primaryKey]);
			} else {
				$this->_execute('SET IDENTITY_INSERT ' . $this->fullTableName($model) . ' ON');
			}
		}
		$result = parent::create($model, array_keys($fields), array_values($fields));
		if (array_key_exists($primaryKey, $fields) && !empty($fields[$primaryKey])) {
			$this->_execute('SET IDENTITY_INSERT ' . $this->fullTableName($model) . ' OFF');
		}
		return $result;
	}

/**
 * Generates and executes an SQL UPDATE statement for given model, fields, and values.
 * Removes Identity (primary key) column from update data before returning to parent.
 *
 * @param Model $model
 * @param array $fields
 * @param array $values
 * @param mixed $conditions
 * @return array
 */
	public function update($model, $fields = array(), $values = null, $conditions = null) {
		if (!empty($values)) {
			$fields = array_combine($fields, $values);
		}
		if (isset($fields[$model->primaryKey])) {
			unset($fields[$model->primaryKey]);
		}
		if (empty($fields)) {
			return true;
		}
		return parent::update($model, array_keys($fields), array_values($fields), $conditions);
	}

/**
 * Returns a limit statement in the correct format for the particular database.
 *
 * @param integer $limit Limit of results returned
 * @param integer $offset Offset from which to start results
 * @return string SQL limit/offset statement
 */
	public function limit($limit, $offset = null) {
		if ($limit) {
			$rt = '';
			if (!strpos(strtolower($limit), 'top') || strpos(strtolower($limit), 'top') === 0) {
				$rt = ' TOP';
			}
			$rt .= ' ' . $limit;
			if (is_int($offset) && $offset > 0) {
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
	public function column($real) {
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

		if (in_array($col, array('date', 'time', 'datetime', 'timestamp'))) {
			return $col;
		}
		if ($col == 'bit') {
			return 'boolean';
		}
		if (strpos($col, 'int') !== false) {
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
		if (in_array($col, array('float', 'real', 'decimal', 'numeric'))) {
			return 'float';
		}
		return 'text';
	}

/**
 * Builds a map of the columns contained in a result
 *
 * @param PDOStatement $results
 */
	public function resultSet($results) {
		$this->map = array();
		$numFields = $results->columnCount();
		$index = 0;

		while ($numFields-- > 0) {
			$column = $results->getColumnMeta($index);
			$name = $column['name'];

			if (strpos($name, '__')) {
				if (isset($this->_fieldMappings[$name]) && strpos($this->_fieldMappings[$name], '.')) {
					$map = explode('.', $this->_fieldMappings[$name]);
				} elseif (isset($this->_fieldMappings[$name])) {
					$map = array(0, $this->_fieldMappings[$name]);
				} else {
					$map = array(0, $name);
				}
			} else {
				$map = array(0, $name);
			}
			$map[] = ($column['sqlsrv:decl_type'] == 'bit') ? 'boolean' : $column['native_type'];
			$this->map[$index++] = $map;
		}
	}

/**
 * Builds final SQL statement
 *
 * @param string $type Query type
 * @param array $data Query data
 * @return string
 */
	public function renderStatement($type, $data) {
		switch (strtolower($type)) {
			case 'select':
				extract($data);
				$fields = trim($fields);

				if (strpos($limit, 'TOP') !== false && strpos($fields, 'DISTINCT ') === 0) {
					$limit = 'DISTINCT ' . trim($limit);
					$fields = substr($fields, 9);
				}

				if (preg_match('/offset\s+([0-9]+)/i', $limit, $offset)) {
					$limit = preg_replace('/\s*offset.*$/i', '', $limit);
					preg_match('/top\s+([0-9]+)/i', $limit, $limitVal);
					$offset = intval($offset[1]) + intval($limitVal[1]);
					$rOrder = $this->__switchSort($order);
					list($order2, $rOrder) = array($this->__mapFields($order), $this->__mapFields($rOrder));
					return "SELECT * FROM (SELECT {$limit} * FROM (SELECT TOP {$offset} {$fields} FROM {$table} {$alias} {$joins} {$conditions} {$group} {$order}) AS Set1 {$rOrder}) AS Set2 {$order2}";
				} else {
					return "SELECT {$limit} {$fields} FROM {$table} {$alias} {$joins} {$conditions} {$group} {$order}";
				}
			break;
			case "schema":
				extract($data);

				foreach ($indexes as $i => $index) {
					if (preg_match('/PRIMARY KEY/', $index)) {
						unset($indexes[$i]);
						break;
					}
				}

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

/**
 * Reverses the sort direction of ORDER statements to get paging offsets to work correctly
 *
 * @param string $order
 * @return string
 */
	private function __switchSort($order) {
		$order = preg_replace('/\s+ASC/i', '__tmp_asc__', $order);
		$order = preg_replace('/\s+DESC/i', ' ASC', $order);
		return preg_replace('/__tmp_asc__/', ' DESC', $order);
	}

/**
 * Translates field names used for filtering and sorting to shortened names using the field map
 *
 * @param string $sql A snippet of SQL representing an ORDER or WHERE statement
 * @return string The value of $sql with field names replaced
 */
	private function __mapFields($sql) {
		if (empty($sql) || empty($this->_fieldMappings)) {
			return $sql;
		}
		foreach ($this->_fieldMappings as $key => $val) {
			$sql = preg_replace('/' . preg_quote($val) . '/', $this->name($key), $sql);
			$sql = preg_replace('/' . preg_quote($this->name($val)) . '/', $this->name($key), $sql);
		}
		return $sql;
	}

/**
 * Returns an array of all result rows for a given SQL query.
 * Returns false if no rows matched.
 *
 * @param string $sql SQL statement
 * @param boolean $cache Enables returning/storing cached query results
 * @return array Array of resultset rows, or false if no rows matched
 */
	public function read($model, $queryData = array(), $recursive = null) {
		$results = parent::read($model, $queryData, $recursive);
		$this->_fieldMappings = array();
		return $results;
	}

/**
 * Fetches the next row from the current result set
 *
 * @return mixed
 */
	public function fetchResult() {
		if ($row = $this->_result->fetch()) {
			$resultRow = array();
			foreach ($this->map as $col => $meta) {
				list($table, $column, $type) = $meta;
				$resultRow[$table][$column] = $row[$col];
				if ($type === 'boolean' && !is_null($row[$col])) {
					$resultRow[$table][$column] = $this->boolean($resultRow[$table][$column]);
				}
			}
			return $resultRow;
		}
		$this->_result->closeCursor();
		return false;
	}

/**
 * Inserts multiple values into a table
 *
 * @param string $table
 * @param string $fields
 * @param array $values
 */
	public function insertMulti($table, $fields, $values) {
		$primaryKey = $this->_getPrimaryKey($table);
		$hasPrimaryKey = $primaryKey != null && (
			(is_array($fields) && in_array($primaryKey, $fields)
			|| (is_string($fields) && strpos($fields, $this->startQuote . $primaryKey . $this->endQuote) !== false))
		);

		if ($hasPrimaryKey) {
			$this->_execute('SET IDENTITY_INSERT ' . $this->fullTableName($table) . ' ON');
		}

		$table = $this->fullTableName($table);
		$fields = implode(', ', array_map(array(&$this, 'name'), $fields));
		$this->begin();
		foreach ($values as $value) {
			$holder = implode(', ', array_map(array(&$this, 'value'), $value));
			$this->_execute("INSERT INTO {$table} ({$fields}) VALUES ({$holder})");
		}
		$this->commit();

		if ($hasPrimaryKey) {
			$this->_execute('SET IDENTITY_INSERT ' . $this->fullTableName($table) . ' OFF');
		}
	}

/**
 * Generate a database-native column schema string
 *
 * @param array $column An array structured like the following: array('name'=>'value', 'type'=>'value'[, options]),
 *   where options can be 'default', 'length', or 'key'.
 * @return string
 */
	public function buildColumn($column) {
		$result = preg_replace('/(int|integer)\([0-9]+\)/i', '$1', parent::buildColumn($column));
		if (strpos($result, 'DEFAULT NULL') !== false) {
			if (isset($column['default']) && $column['default'] === '') {
				$result = str_replace('DEFAULT NULL', "DEFAULT ''", $result);
			} else {
				$result = str_replace('DEFAULT NULL', 'NULL', $result);
			}
		} else if (array_keys($column) == array('type', 'name')) {
			$result .= ' NULL';
		}
		return $result;
	}

/**
 * Format indexes for create table
 *
 * @param array $indexes
 * @param string $table
 * @return string
 */
	public function buildIndex($indexes, $table = null) {
		$join = array();

		foreach ($indexes as $name => $value) {
			if ($name == 'PRIMARY') {
				$join[] = 'PRIMARY KEY (' . $this->name($value['column']) . ')';
			} else if (isset($value['unique']) && $value['unique']) {
				$out = "ALTER TABLE {$table} ADD CONSTRAINT {$name} UNIQUE";

				if (is_array($value['column'])) {
					$value['column'] = implode(', ', array_map(array(&$this, 'name'), $value['column']));
				} else {
					$value['column'] = $this->name($value['column']);
				}
				$out .= "({$value['column']});";
				$join[] = $out;
			}
		}
		return $join;
	}

/**
 * Makes sure it will return the primary key
 *
 * @param mixed $model Model instance of table name
 * @return string
 */
	protected function _getPrimaryKey($model) {
		if (!is_object($model)) {
			$model = new Model(false, $model);
		}
		$schema = $this->describe($model);
		foreach ($schema as $field => $props) {
			if (isset($props['key']) && $props['key'] == 'primary') {
				return $field;
			}
		}
		return null;
	}

/**
 * Returns number of affected rows in previous database operation. If no previous operation exists,
 * this returns false.
 *
 * @return integer Number of affected rows
 */
	public function lastAffected() {
		$affected = parent::lastAffected();
		if ($affected === null && $this->_lastAffected !== false) {
			return $this->_lastAffected;
		}
		return $affected;
	}
/**
 * Executes given SQL statement.
 *
 * @param string $sql SQL statement
 * @param array $params list of params to be bound to query (supported only in select)
 * @param array $prepareOptions Options to be used in the prepare statement
 * @return mixed PDOStatement if query executes with no problem, true as the result of a succesfull, false on error
 * query returning no rows, suchs as a CREATE statement, false otherwise
 */
	protected function _execute($sql, $params = array(), $prepareOptions = array()) {
		$this->_lastAffected = false;
		if (strncasecmp($sql, 'SELECT', 6) == 0) {
			$prepareOptions += array(PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL);
			return parent::_execute($sql, $params, $prepareOptions);
		}
		try {
			$this->_lastAffected = $this->_connection->exec($sql);
			if ($this->_lastAffected === false) {
				$this->_results = null;
				$error = $this->_connection->errorInfo();
				$this->error = $error[2];
				return false;
			}
			return true;
		} catch (PDOException $e) {
			$this->_results = null;
			$this->error = $e->getMessage();
			return false;
		}
	}

}
