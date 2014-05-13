<?php
/**
 * MS SQL Server layer for DBO
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
 * @package       Cake.Model.Datasource.Database
 * @since         CakePHP(tm) v 0.10.5.1790
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */

App::uses('DboSource', 'Model/Datasource');

/**
 * Dbo layer for Microsoft's official SQLServer driver
 *
 * A Dbo layer for MS SQL Server 2005 and higher. Requires the
 * `pdo_sqlsrv` extension to be enabled.
 *
 * @link http://www.php.net/manual/en/ref.pdo-sqlsrv.php
 *
 * @package       Cake.Model.Datasource.Database
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
 * Creates a map between field aliases and numeric indexes. Workaround for the
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
		'host' => 'localhost\SQLEXPRESS',
		'login' => '',
		'password' => '',
		'database' => 'cake',
		'schema' => '',
		'flags' => array()
	);

/**
 * MS SQL column definition
 *
 * @var array
 */
	public $columns = array(
		'primary_key' => array('name' => 'IDENTITY (1, 1) NOT NULL'),
		'string' => array('name' => 'nvarchar', 'limit' => '255'),
		'text' => array('name' => 'nvarchar', 'limit' => 'MAX'),
		'integer' => array('name' => 'int', 'formatter' => 'intval'),
		'biginteger' => array('name' => 'bigint'),
		'numeric' => array('name' => 'decimal', 'formatter' => 'floatval'),
		'decimal' => array('name' => 'decimal', 'formatter' => 'floatval'),
		'float' => array('name' => 'float', 'formatter' => 'floatval'),
		'real' => array('name' => 'float', 'formatter' => 'floatval'),
		'datetime' => array('name' => 'datetime', 'format' => 'Y-m-d H:i:s', 'formatter' => 'date'),
		'timestamp' => array('name' => 'timestamp', 'format' => 'Y-m-d H:i:s', 'formatter' => 'date'),
		'time' => array('name' => 'datetime', 'format' => 'H:i:s', 'formatter' => 'date'),
		'date' => array('name' => 'datetime', 'format' => 'Y-m-d', 'formatter' => 'date'),
		'binary' => array('name' => 'varbinary'),
		'boolean' => array('name' => 'bit')
	);

/**
 * Magic column name used to provide pagination support for SQLServer 2008
 * which lacks proper limit/offset support.
 *
 * @var string
 */
	const ROW_COUNTER = '_cake_page_rownum_';

/**
 * Connects to the database using options in the given configuration array.
 *
 * @return boolean True if the database could be connected, else false
 * @throws MissingConnectionException
 */
	public function connect() {
		$config = $this->config;
		$this->connected = false;

		$flags = $config['flags'] + array(
			PDO::ATTR_PERSISTENT => $config['persistent'],
			PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
		);

		if (!empty($config['encoding'])) {
			$flags[PDO::SQLSRV_ATTR_ENCODING] = $config['encoding'];
		}

		try {
			$this->_connection = new PDO(
				"sqlsrv:server={$config['host']};Database={$config['database']}",
				$config['login'],
				$config['password'],
				$flags
			);
			$this->connected = true;
			if (!empty($config['settings'])) {
				foreach ($config['settings'] as $key => $value) {
					$this->_execute("SET $key $value");
				}
			}
		} catch (PDOException $e) {
			throw new MissingConnectionException(array(
				'class' => get_class($this),
				'message' => $e->getMessage()
			));
		}

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
 * @param mixed $data
 * @return array Array of table names in the database
 */
	public function listSources($data = null) {
		$cache = parent::listSources();
		if ($cache !== null) {
			return $cache;
		}
		$result = $this->_execute("SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES");

		if (!$result) {
			$result->closeCursor();
			return array();
		}
		$tables = array();

		while ($line = $result->fetch(PDO::FETCH_NUM)) {
			$tables[] = $line[0];
		}

		$result->closeCursor();
		parent::listSources($tables);
		return $tables;
	}

/**
 * Returns an array of the fields in given table name.
 *
 * @param Model|string $model Model object to describe, or a string table name.
 * @return array Fields in table. Keys are name and type
 * @throws CakeException
 */
	public function describe($model) {
		$table = $this->fullTableName($model, false, false);
		$fulltable = $this->fullTableName($model, false, true);

		$cache = parent::describe($fulltable);
		if ($cache) {
			return $cache;
		}

		$fields = array();
		$schema = is_object($model) ? $model->schemaName : false;

		$cols = $this->_execute(
			"SELECT
				COLUMN_NAME as Field,
				DATA_TYPE as Type,
				COL_LENGTH('" . ($schema ? $fulltable : $table) . "', COLUMN_NAME) as Length,
				IS_NULLABLE As [Null],
				COLUMN_DEFAULT as [Default],
				COLUMNPROPERTY(OBJECT_ID('" . ($schema ? $fulltable : $table) . "'), COLUMN_NAME, 'IsIdentity') as [Key],
				NUMERIC_SCALE as Size
			FROM INFORMATION_SCHEMA.COLUMNS
			WHERE TABLE_NAME = '" . $table . "'" . ($schema ? " AND TABLE_SCHEMA = '" . $schema . "'" : '')
		);

		if (!$cols) {
			throw new CakeException(__d('cake_dev', 'Could not describe table for %s', $table));
		}

		while ($column = $cols->fetch(PDO::FETCH_OBJ)) {
			$field = $column->Field;
			$fields[$field] = array(
				'type' => $this->column($column),
				'null' => ($column->Null === 'YES' ? true : false),
				'default' => $column->Default,
				'length' => $this->length($column),
				'key' => ($column->Key == '1') ? 'primary' : false
			);

			if ($fields[$field]['default'] === 'null') {
				$fields[$field]['default'] = null;
			}
			if ($fields[$field]['default'] !== null) {
				$fields[$field]['default'] = preg_replace(
					"/^[(]{1,2}'?([^')]*)?'?[)]{1,2}$/",
					"$1",
					$fields[$field]['default']
				);
				$this->value($fields[$field]['default'], $fields[$field]['type']);
			}

			if ($fields[$field]['key'] !== false && $fields[$field]['type'] === 'integer') {
				$fields[$field]['length'] = 11;
			} elseif ($fields[$field]['key'] === false) {
				unset($fields[$field]['key']);
			}
			if (in_array($fields[$field]['type'], array('date', 'time', 'datetime', 'timestamp'))) {
				$fields[$field]['length'] = null;
			}
			if ($fields[$field]['type'] === 'float' && !empty($column->Size)) {
				$fields[$field]['length'] = $fields[$field]['length'] . ',' . $column->Size;
			}
		}
		$this->_cacheDescription($table, $fields);
		$cols->closeCursor();
		return $fields;
	}

/**
 * Generates the fields list of an SQL query.
 *
 * @param Model $model
 * @param string $alias Alias table name
 * @param array $fields
 * @param boolean $quote
 * @return array
 */
	public function fields(Model $model, $alias = null, $fields = array(), $quote = true) {
		if (empty($alias)) {
			$alias = $model->alias;
		}
		$fields = parent::fields($model, $alias, $fields, false);
		$count = count($fields);

		if ($count >= 1 && strpos($fields[0], 'COUNT(*)') === false) {
			$result = array();
			for ($i = 0; $i < $count; $i++) {
				$prepend = '';

				if (strpos($fields[$i], 'DISTINCT') !== false && strpos($fields[$i], 'COUNT') === false) {
					$prepend = 'DISTINCT ';
					$fields[$i] = trim(str_replace('DISTINCT', '', $fields[$i]));
				}

				if (!preg_match('/\s+AS\s+/i', $fields[$i])) {
					if (substr($fields[$i], -1) === '*') {
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
						$this->_fieldMappings[$alias . '__' . $fields[$i]] = $alias . '.' . $fields[$i];
						$fieldName = $this->name($alias . '.' . $fields[$i]);
						$fieldAlias = $this->name($alias . '__' . $fields[$i]);
					} else {
						$build = explode('.', $fields[$i]);
						$build[0] = trim($build[0], '[]');
						$build[1] = trim($build[1], '[]');
						$name = $build[0] . '.' . $build[1];
						$alias = $build[0] . '__' . $build[1];

						$this->_fieldMappings[$alias] = $name;
						$fieldName = $this->name($name);
						$fieldAlias = $this->name($alias);
					}
					if ($model->getColumnType($fields[$i]) === 'datetime') {
						$fieldName = "CONVERT(VARCHAR(20), {$fieldName}, 20)";
					}
					$fields[$i] = "{$fieldName} AS {$fieldAlias}";
				}
				$result[] = $prepend . $fields[$i];
			}
			return $result;
		}
		return $fields;
	}

/**
 * Generates and executes an SQL INSERT statement for given model, fields, and values.
 * Removes Identity (primary key) column from update data before returning to parent, if
 * value is empty.
 *
 * @param Model $model
 * @param array $fields
 * @param array $values
 * @return array
 */
	public function create(Model $model, $fields = null, $values = null) {
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
	public function update(Model $model, $fields = array(), $values = null, $conditions = null) {
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
			$rt .= sprintf(' %u', $limit);
			if (is_int($offset) && $offset > 0) {
				$rt = sprintf(' OFFSET %u ROWS FETCH FIRST %u ROWS ONLY', $offset, $limit);
			}
			return $rt;
		}
		return null;
	}

/**
 * Converts database-layer column types to basic types
 *
 * @param mixed $real Either the string value of the fields type.
 *    or the Result object from Sqlserver::describe()
 * @return string Abstract column type (i.e. "string")
 */
	public function column($real) {
		$limit = null;
		$col = $real;
		if (is_object($real) && isset($real->Field)) {
			$limit = $real->Length;
			$col = $real->Type;
		}

		if ($col === 'datetime2') {
			return 'datetime';
		}
		if (in_array($col, array('date', 'time', 'datetime', 'timestamp'))) {
			return $col;
		}
		if ($col === 'bit') {
			return 'boolean';
		}
		if (strpos($col, 'bigint') !== false) {
			return 'biginteger';
		}
		if (strpos($col, 'int') !== false) {
			return 'integer';
		}
		if (strpos($col, 'char') !== false && $limit == -1) {
			return 'text';
		}
		if (strpos($col, 'char') !== false) {
			return 'string';
		}
		if (strpos($col, 'text') !== false) {
			return 'text';
		}
		if (strpos($col, 'binary') !== false || $col === 'image') {
			return 'binary';
		}
		if (in_array($col, array('float', 'real'))) {
			return 'float';
		}
		if (in_array($col, array('decimal', 'numeric'))) {
			return 'decimal';
		}
		return 'text';
	}

/**
 * Handle SQLServer specific length properties.
 * SQLServer handles text types as nvarchar/varchar with a length of -1.
 *
 * @param mixed $length Either the length as a string, or a Column descriptor object.
 * @return mixed null|integer with length of column.
 */
	public function length($length) {
		if (is_object($length) && isset($length->Length)) {
			if ($length->Length == -1 && strpos($length->Type, 'char') !== false) {
				return null;
			}
			if (in_array($length->Type, array('nchar', 'nvarchar'))) {
				return floor($length->Length / 2);
			}
			return $length->Length;
		}
		return parent::length($length);
	}

/**
 * Builds a map of the columns contained in a result
 *
 * @param PDOStatement $results
 * @return void
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
			$map[] = ($column['sqlsrv:decl_type'] === 'bit') ? 'boolean' : $column['native_type'];
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

				// hack order as SQLServer requires an order if there is a limit.
				if ($limit && !$order) {
					$order = 'ORDER BY (SELECT NULL)';
				}

				// For older versions use the subquery version of pagination.
				if (version_compare($this->getVersion(), '11', '<') && preg_match('/FETCH\sFIRST\s+([0-9]+)/i', $limit, $offset)) {
					preg_match('/OFFSET\s*(\d+)\s*.*?(\d+)\s*ROWS/', $limit, $limitOffset);

					$limit = 'TOP ' . intval($limitOffset[2]);
					$page = intval($limitOffset[1] / $limitOffset[2]);
					$offset = intval($limitOffset[2] * $page);

					$rowCounter = self::ROW_COUNTER;
					$sql = "SELECT {$limit} * FROM (
							SELECT {$fields}, ROW_NUMBER() OVER ({$order}) AS {$rowCounter}
							FROM {$table} {$alias} {$joins} {$conditions} {$group}
						) AS _cake_paging_
						WHERE _cake_paging_.{$rowCounter} > {$offset}
						ORDER BY _cake_paging_.{$rowCounter}
					";
					return trim($sql);
				}
				if (strpos($limit, 'FETCH') !== false) {
					return trim("SELECT {$fields} FROM {$table} {$alias} {$joins} {$conditions} {$group} {$order} {$limit}");
				}
				return trim("SELECT {$limit} {$fields} FROM {$table} {$alias} {$joins} {$conditions} {$group} {$order}");
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
				return trim("CREATE TABLE {$table} (\n{$columns});\n{$indexes}");
			default:
				return parent::renderStatement($type, $data);
		}
	}

/**
 * Returns a quoted and escaped string of $data for use in an SQL statement.
 *
 * @param string $data String to be prepared for use in an SQL statement
 * @param string $column The column into which this data will be inserted
 * @return string Quoted and escaped data
 */
	public function value($data, $column = null) {
		if ($data === null || is_array($data) || is_object($data)) {
			return parent::value($data, $column);
		}
		if (in_array($data, array('{$__cakeID__$}', '{$__cakeForeignKey__$}'), true)) {
			return $data;
		}

		if (empty($column)) {
			$column = $this->introspectType($data);
		}

		switch ($column) {
			case 'string':
			case 'text':
				return 'N' . $this->_connection->quote($data, PDO::PARAM_STR);
			default:
				return parent::value($data, $column);
		}
	}

/**
 * Returns an array of all result rows for a given SQL query.
 * Returns false if no rows matched.
 *
 * @param Model $model
 * @param array $queryData
 * @param integer $recursive
 * @return array|false Array of resultset rows, or false if no rows matched
 */
	public function read(Model $model, $queryData = array(), $recursive = null) {
		$results = parent::read($model, $queryData, $recursive);
		$this->_fieldMappings = array();
		return $results;
	}

/**
 * Fetches the next row from the current result set.
 * Eats the magic ROW_COUNTER variable.
 *
 * @return mixed
 */
	public function fetchResult() {
		if ($row = $this->_result->fetch(PDO::FETCH_NUM)) {
			$resultRow = array();
			foreach ($this->map as $col => $meta) {
				list($table, $column, $type) = $meta;
				if ($table === 0 && $column === self::ROW_COUNTER) {
					continue;
				}
				$resultRow[$table][$column] = $row[$col];
				if ($type === 'boolean' && $row[$col] !== null) {
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
 * @return void
 */
	public function insertMulti($table, $fields, $values) {
		$primaryKey = $this->_getPrimaryKey($table);
		$hasPrimaryKey = $primaryKey && (
			(is_array($fields) && in_array($primaryKey, $fields)
			|| (is_string($fields) && strpos($fields, $this->startQuote . $primaryKey . $this->endQuote) !== false))
		);

		if ($hasPrimaryKey) {
			$this->_execute('SET IDENTITY_INSERT ' . $this->fullTableName($table) . ' ON');
		}

		parent::insertMulti($table, $fields, $values);

		if ($hasPrimaryKey) {
			$this->_execute('SET IDENTITY_INSERT ' . $this->fullTableName($table) . ' OFF');
		}
	}

/**
 * Generate a database-native column schema string
 *
 * @param array $column An array structured like the
 *   following: array('name'=>'value', 'type'=>'value'[, options]),
 *   where options can be 'default', 'length', or 'key'.
 * @return string
 */
	public function buildColumn($column) {
		$result = parent::buildColumn($column);
		$result = preg_replace('/(bigint|int|integer)\([0-9]+\)/i', '$1', $result);
		$result = preg_replace('/(bit)\([0-9]+\)/i', '$1', $result);
		if (strpos($result, 'DEFAULT NULL') !== false) {
			if (isset($column['default']) && $column['default'] === '') {
				$result = str_replace('DEFAULT NULL', "DEFAULT ''", $result);
			} else {
				$result = str_replace('DEFAULT NULL', 'NULL', $result);
			}
		} elseif (array_keys($column) === array('type', 'name')) {
			$result .= ' NULL';
		} elseif (strpos($result, "DEFAULT N'")) {
			$result = str_replace("DEFAULT N'", "DEFAULT '", $result);
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
			if ($name === 'PRIMARY') {
				$join[] = 'PRIMARY KEY (' . $this->name($value['column']) . ')';
			} elseif (isset($value['unique']) && $value['unique']) {
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
 * @param Model|string $model Model instance of table name
 * @return string
 */
	protected function _getPrimaryKey($model) {
		$schema = $this->describe($model);
		foreach ($schema as $field => $props) {
			if (isset($props['key']) && $props['key'] === 'primary') {
				return $field;
			}
		}
		return null;
	}

/**
 * Returns number of affected rows in previous database operation. If no previous operation exists,
 * this returns false.
 *
 * @param mixed $source
 * @return integer Number of affected rows
 */
	public function lastAffected($source = null) {
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
 * @return mixed PDOStatement if query executes with no problem, true as the result of a successful, false on error
 * query returning no rows, such as a CREATE statement, false otherwise
 * @throws PDOException
 */
	protected function _execute($sql, $params = array(), $prepareOptions = array()) {
		$this->_lastAffected = false;
		$sql = trim($sql);
		if (strncasecmp($sql, 'SELECT', 6) === 0 || preg_match('/^EXEC(?:UTE)?\s/mi', $sql) > 0) {
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
			if (isset($query->queryString)) {
				$e->queryString = $query->queryString;
			} else {
				$e->queryString = $sql;
			}
			throw $e;
		}
	}

/**
 * Generate a "drop table" statement for the given table
 *
 * @param type $table Name of the table to drop
 * @return string Drop table SQL statement
 */
	protected function _dropTable($table) {
		return "IF OBJECT_ID('" . $this->fullTableName($table, false) . "', 'U') IS NOT NULL DROP TABLE " . $this->fullTableName($table) . ";";
	}

/**
 * Gets the schema name
 *
 * @return string The schema name
 */
	public function getSchemaName() {
		return $this->config['schema'];
	}

}
