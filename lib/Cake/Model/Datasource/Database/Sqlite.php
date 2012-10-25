<?php
/**
 * SQLite layer for DBO
 *
 * PHP 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       Cake.Model.Datasource.Database
 * @since         CakePHP(tm) v 0.9.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

App::uses('DboSource', 'Model/Datasource');
App::uses('String', 'Utility');

/**
 * DBO implementation for the SQLite3 DBMS.
 *
 * A DboSource adapter for SQLite 3 using PDO
 *
 * @package       Cake.Model.Datasource.Database
 */
class Sqlite extends DboSource {

/**
 * Datasource Description
 *
 * @var string
 */
	public $description = "SQLite DBO Driver";

/**
 * Quote Start
 *
 * @var string
 */
	public $startQuote = '"';

/**
 * Quote End
 *
 * @var string
 */
	public $endQuote = '"';

/**
 * Base configuration settings for SQLite3 driver
 *
 * @var array
 */
	protected $_baseConfig = array(
		'persistent' => false,
		'database' => null
	);

/**
 * SQLite3 column definition
 *
 * @var array
 */
	public $columns = array(
		'primary_key' => array('name' => 'integer primary key autoincrement'),
		'string' => array('name' => 'varchar', 'limit' => '255'),
		'text' => array('name' => 'text'),
		'integer' => array('name' => 'integer', 'limit' => null, 'formatter' => 'intval'),
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
 */
	public $fieldParameters = array(
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
 * @return boolean
 * @throws MissingConnectionException
 */
	public function connect() {
		$config = $this->config;
		$flags = array(
			PDO::ATTR_PERSISTENT => $config['persistent'],
			PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
		);
		try {
			$this->_connection = new PDO('sqlite:' . $config['database'], null, null, $flags);
			$this->connected = true;
		} catch(PDOException $e) {
			throw new MissingConnectionException(array(
				'class' => get_class($this),
				'message' => $e->getMessage()
			));
		}
		return $this->connected;
	}

/**
 * Check whether the SQLite extension is installed/loaded
 *
 * @return boolean
 */
	public function enabled() {
		return in_array('sqlite', PDO::getAvailableDrivers());
	}

/**
 * Returns an array of tables in the database. If there are no tables, an error is raised and the application exits.
 *
 * @param mixed $data
 * @return array Array of table names in the database
 */
	public function listSources($data = null) {
		$cache = parent::listSources();
		if ($cache != null) {
			return $cache;
		}

		$result = $this->fetchAll("SELECT name FROM sqlite_master WHERE type='table' ORDER BY name;", false);

		if (!$result || empty($result)) {
			return array();
		} else {
			$tables = array();
			foreach ($result as $table) {
				$tables[] = $table[0]['name'];
			}
			parent::listSources($tables);
			return $tables;
		}
	}

/**
 * Returns an array of the fields in given table name.
 *
 * @param Model|string $model Either the model or table name you want described.
 * @return array Fields in table. Keys are name and type
 */
	public function describe($model) {
		$table = $this->fullTableName($model, false, false);
		$cache = parent::describe($table);
		if ($cache != null) {
			return $cache;
		}
		$fields = array();
		$result = $this->_execute(
			'PRAGMA table_info(' . $this->value($table, 'string') . ')'
		);

		foreach ($result as $column) {
			$column = (array)$column;
			$default = ($column['dflt_value'] === 'NULL') ? null : trim($column['dflt_value'], "'");

			$fields[$column['name']] = array(
				'type' => $this->column($column['type']),
				'null' => !$column['notnull'],
				'default' => $default,
				'length' => $this->length($column['type'])
			);
			if ($column['pk'] == 1) {
				$fields[$column['name']]['key'] = $this->index['PRI'];
				$fields[$column['name']]['null'] = false;
			}
		}
		$result->closeCursor();

		// add index information
		$indexes = $this->_execute("PRAGMA index_list($table)");
		if ($indexes instanceof PDOStatement) {
			foreach ($indexes as $index) {
				$index_info = $this->_execute('PRAGMA index_info("' . $index['name'] . '")');
				foreach($index_info as $column) {
					if ($column['seqno'] == 0) {
						if ($index['unique']) {
							if (empty($fields[$column['name']]['key']) || ($fields[$column['name']]['key'] != $this->index['PRI'])) {
								$fields[$column['name']]['key'] = $this->index['UNI'];
							}
						} else {
							if (empty($fields[$column['name']]['key'])) {
								$fields[$column['name']]['key'] = $this->index['MUL'];
							}
						}
					}
				}
			}
			$indexes->closeCursor();
		}

		$this->_cacheDescription($table, $fields);
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
	public function update(Model $model, $fields = array(), $values = null, $conditions = null) {
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
		return parent::update($model, $fields, $values, $conditions);
	}

/**
 * Deletes all the records in a table and resets the count of the auto-incrementing
 * primary key, where applicable.
 *
 * @param string|Model $table A string or model class representing the table to be truncated
 * @return boolean	SQL TRUNCATE TABLE statement, false if not applicable.
 */
	public function truncate($table) {
		try {
			$this->_execute('DELETE FROM sqlite_sequence where name=' . $this->startQuote . $this->fullTableName($table, false, false) . $this->endQuote);
		} catch (PDOException $e) {
			// sqlite_squence might not exist
		}
		return $this->execute('DELETE FROM ' . $this->fullTableName($table));
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

		$col = strtolower(str_replace(')', '', $real));
		$limit = null;
		@list($col, $limit) = explode('(', $col);

		if (in_array($col, array('text', 'integer', 'float', 'boolean', 'timestamp', 'date', 'datetime', 'time'))) {
			return $col;
		}
		if (strpos($col, 'char') !== false) {
			return 'string';
		}
		if (in_array($col, array('blob', 'clob'))) {
			return 'binary';
		}
		if (strpos($col, 'numeric') !== false || strpos($col, 'decimal') !== false) {
			return 'float';
		}
		return 'text';
	}

/**
 * Generate ResultSet
 *
 * @param mixed $results
 * @return void
 */
	public function resultSet($results) {
		$this->results = $results;
		$this->map = array();
		$numFields = $results->columnCount();
		$index = 0;
		$j = 0;

		//PDO::getColumnMeta is experimental and does not work with sqlite3,
		//	so try to figure it out based on the querystring
		$querystring = $results->queryString;
		if (stripos($querystring, 'SELECT') === 0) {
			$last = strripos($querystring, 'FROM');
			if ($last !== false) {
				$selectpart = substr($querystring, 7, $last - 8);
				$selects = String::tokenize($selectpart, ',', '(', ')');
			}
		} elseif (strpos($querystring, 'PRAGMA table_info') === 0) {
			$selects = array('cid', 'name', 'type', 'notnull', 'dflt_value', 'pk');
		} elseif (strpos($querystring, 'PRAGMA index_list') === 0) {
			$selects = array('seq', 'name', 'unique');
		} elseif (strpos($querystring, 'PRAGMA index_info') === 0) {
			$selects = array('seqno', 'cid', 'name');
		}
		while ($j < $numFields) {
			if (!isset($selects[$j])) {
				$j++;
				continue;
			}
			if (preg_match('/\bAS\s+(.*)/i', $selects[$j], $matches)) {
				 $columnName = trim($matches[1], '"');
			} else {
				$columnName = trim(str_replace('"', '', $selects[$j]));
			}

			if (strpos($selects[$j], 'DISTINCT') === 0) {
				$columnName = str_ireplace('DISTINCT', '', $columnName);
			}

			$metaType = false;
			try {
				$metaData = (array)$results->getColumnMeta($j);
				if (!empty($metaData['sqlite:decl_type'])) {
					$metaType = trim($metaData['sqlite:decl_type']);
				}
			} catch (Exception $e) {
			}

			if (strpos($columnName, '.')) {
				$parts = explode('.', $columnName);
				$this->map[$index++] = array(trim($parts[0]), trim($parts[1]), $metaType);
			} else {
				$this->map[$index++] = array(0, $columnName, $metaType);
			}
			$j++;
		}
	}

/**
 * Fetches the next row from the current result set
 *
 * @return mixed array with results fetched and mapped to column names or false if there is no results left to fetch
 */
	public function fetchResult() {
		if ($row = $this->_result->fetch(PDO::FETCH_NUM)) {
			$resultRow = array();
			foreach ($this->map as $col => $meta) {
				list($table, $column, $type) = $meta;
				$resultRow[$table][$column] = $row[$col];
				if ($type == 'boolean' && !is_null($row[$col])) {
					$resultRow[$table][$column] = $this->boolean($resultRow[$table][$column]);
				}
			}
			return $resultRow;
		} else {
			$this->_result->closeCursor();
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
	public function limit($limit, $offset = null) {
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
 *    where options can be 'default', 'length', or 'key'.
 * @return string
 */
	public function buildColumn($column) {
		$name = $type = null;
		$column = array_merge(array('null' => true), $column);
		extract($column);

		if (empty($name) || empty($type)) {
			trigger_error(__d('cake_dev', 'Column name or type not defined in schema'), E_USER_WARNING);
			return null;
		}

		if (!isset($this->columns[$type])) {
			trigger_error(__d('cake_dev', 'Column type %s does not exist', $type), E_USER_WARNING);
			return null;
		}

		if (isset($column['key']) && $column['key'] == 'primary' && $type == 'integer') {
			return $this->name($name) . ' ' . $this->columns['primary_key']['name'];
		}
		return parent::buildColumn($column);
	}

/**
 * Sets the database encoding
 *
 * @param string $enc Database encoding
 * @return boolean
 */
	public function setEncoding($enc) {
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
	public function getEncoding() {
		return $this->fetchRow('PRAGMA encoding');
	}

/**
 * Generate a alter syntax from	CakeSchema::compare()
 *
 * See http://www.sqlite.org/faq.html#q11
 *
 * @param mixed $compare
 * @param string $table
 * @return boolean
 */
	public function alterSchema($compare, $table = null) {
		$out = '';
		foreach ($compare as $tableName => $changes) {
			if (!$table || ($tableName == $table)) {
				$out .= $this->_alterSchema($tableName, $changes) . "\n";
			}
		}
		return $out;
	}

	private function _alterSchema($table, $changes) {
		$out = array();

		// step 1. Recreate tables that cannot be altered
		if ($this->_mustRecreateTable($changes)) {
			$out = array_merge($out, $this->_recreateTable($table, $changes));
		}

		// step 2. Alter tables that can be altered
		if (array_key_exists('add', $changes)) {
			$out = array_merge($out, $this->_addColumns($table, $changes['add']));
		}

		// step 3. Drop indexes (including changed)
		if (isset($changes['drop']['indexes'])) {
			foreach($changes['drop']['indexes'] as $index => $details) {
				$out[] = "DROP INDEX IF EXISTS {$this->fullTableName($index)};";
			}
		}

		if (isset($changes['add']['indexes'])) {
			foreach($changes['add']['indexes'] as $index => $details) {
				if (is_array($details['column'])) {
					$details['column'] = join(', ', $details['column']);
				}

				$out[] = ((empty($details['unique']))? 'CREATE INDEX' : 'CREATE UNIQUE INDEX' ) . ' '
							. $this->fullTableName($index)
							. ' on "' . $table . '" ( ' . $details['column'] . ' );';
			}
		}

		if (isset($changes['alter']['indexes'])) {
			foreach($changes['add']['indexes'] as $index => $details) {

			}
		}

		return empty($out) ? '' : implode("\n", $out) . "\n";
	}

	private function _mustRecreateTable($changes) {
		$drop = @array_diff_key($changes['drop'], array('indexes' => 0, 'tableParameters' => 0));
		$change = @array_diff_key($changes['change'], array('indexes => 0', 'tableParameters' => 0));
		return !(empty($drop) && empty($change));
	}

	private function _recreateTable($table, $changes) {
		$out = array();

		$reader = new CakeSchema();
		$schema = $reader->read(array(
			'connection' => ConnectionManager::getSourceName($this),
			'models' => false
		));

		// change columns
		$renamed = array();
		if (array_key_exists('change', $changes)) {
			foreach($changes['change'] as $col => $details) {
				if (!empty($changes['change'][$col]['name'])) {
					$renamed[$changes['change'][$col]['name']] = $col;
					unset($schema['tables'][$table][$col]);
					$schema['tables'][$table][$changes['change'][$col]['name']] = $changes['change'][$col];
				} else {
					$schema['tables'][$table][$col] = $changes['change'][$col];
				}
			}
		}

		// drop columns
		if (array_key_exists('drop', $changes)) {
			// remove column from table
			$schema['tables'][$table] = array_diff_key($schema['tables'][$table], $changes['drop']);

			// remove any indexes that contain the dropped column
			if (!empty($schema['tables'][$table]['indexes'])) {
				foreach ($schema['tables'][$table]['indexes'] as $name => $details) {
					$index_columns = (is_array($details['column'])? $details['column'] : array($details['column']));
					$dropped_columns = array_keys($changes['drop']);
					$intersection = array_intersect($index_columns, $dropped_columns);
					if (!empty($intersection)) {
						unset($schema['tables'][$table]['indexes'][$name]);
					}
				}
			}
		}

		$reader->tables = $schema['tables'];
		$tableSql = $this->createSchema($reader, $table);

		$fullTableName = $this->fullTableName($table);
		$fullTmpName = $this->fullTableName("{$table}_tmp");

		$cols = array_keys($schema['tables'][$table]);
		$cols = array_diff($cols, array('indexes', 'tableParameters'));
		$cols2 = $cols;
		if (!empty($renamed)) {
			foreach ($cols2 as &$name) {
				if (array_key_exists($name, $renamed)) {
					$name = $renamed[$name];
				}
			}
		}
		$cols =  $this->_columnsAsString($cols);
		$cols2 = $this->_columnsAsString($cols2);

		$out[] = preg_replace("/$fullTableName/i", $fullTmpName, $tableSql, 1);
		$out[] = "INSERT INTO $fullTmpName ($cols) SELECT $cols2 FROM $fullTableName;";
		$out[] = "DROP TABLE $fullTableName;";
		$out[] = "ALTER TABLE $fullTmpName RENAME TO $table;";
		return $out;
	}

	private function _addColumns($table, $cols) {
		$out = array();

		$cols = array_diff_key($cols, array('indexes' => 0, 'tableParameters' => 0));
		foreach ($cols as $col => $details) {
			$details['name'] = $col;

			// alter table must have default for fields that cannot be null
			if (isset($details['null']) && !$details['null'] && empty($details['default'])) {
				if(isset($details['type']) && ($details['type'] == 'text') || ($details['type'] == 'string')) {
					$details['default'] = '';
				} else {
					$details['default'] = 0;
				}
			}

			$out[] = "ALTER TABLE {$this->fullTableName($table)} ADD COLUMN {$this->buildColumn($details)};";
		}

		return $out;
	}

/**
 * Generate a database-native schema for the given Schema object
 *
 * @param Model $schema An instance of a subclass of CakeSchema
 * @param string $tableName Optional.  If specified only the table name given will be generated.
 *   Otherwise, all tables defined in the schema are generated.
 * @return string
 */
	public function createSchema($schema, $tableName = null) {
		if (!is_a($schema, 'CakeSchema')) {
			trigger_error(__d('cake_dev', 'Invalid schema object'), E_USER_WARNING);
			return null;
		}
		$out = '';

		foreach ($schema->tables as $curTable => $columns) {
			if (!$tableName || $tableName == $curTable) {
				$cols = $colList = $indexes = $tableParameters = array();
				$table = $this->fullTableName($curTable);

				// prevent duplicate primary key
				// column is higher priority because it allows autoincrement to be used
				foreach ($columns as $name => $col) {
					if (isset($col['key']) && $col['key'] === 'primary') {
						unset($columns['indexes']['PRIMARY']);
					}
				}
				$primaryIndex = isset($columns['indexes']['PRIMARY']) ? $columns['indexes']['PRIMARY'] : '';

				foreach ($columns as $name => $col) {
					if (is_string($col)) {
						$col = array('type' => $col);
					}
					if ($name !== 'indexes' && $name !== 'tableParameters') {
						$col['name'] = $name;
						if (!isset($col['type'])) {
							$col['type'] = 'string';
						}
						$cols[] = $this->buildColumn($col);
					} elseif ($name === 'indexes') {
						$indexes = array_merge($indexes, $this->buildIndex($col, $curTable));
					} elseif ($name === 'tableParameters') {
						$tableParameters = array_merge($tableParameters, $this->buildTableParameters($col, $table));
					}
				}

				$columns = $cols;
				$out .= $this->renderStatement('schema', compact('table', 'columns', 'indexes', 'tableParameters', 'primaryIndex')) . "\n\n";
			}
		}
		return $out;
	}

/**
 * Plain indexes/keys (non-unique, non-primary) cannot be added by the create table statement.
 * The keyword UNIQUE should not be followed by KEY
 * Ignores names because they are not used by SQLite
 *
 * @param array $indexes
 * @param string $table
 * @return string
*/
	public function buildIndex($indexes, $table = null) {
		$join = array();
		if (!empty($table)) {
			$table = $this->startQuote . $table . $this->endQuote;
			foreach ($indexes as $name => $value) {
				if ($name != 'PRIMARY') {
					$unique = empty($value['unique'])? '' : 'UNIQUE';
					$name = $this->fullTableName($name);
					$join[] = "CREATE $unique INDEX $name ON $table ({$this->_columnsAsString($value['column'])})";;
				}
			}
		}
		return $join;
	}

	private function _getPrimaryKey($table) {
		$info = $this->query("PRAGMA table_info($table)");
		$pk = array();
		foreach ($info as $index => $column) {
			$column = $column[0];
			if ($column['pk']) {
				$pk[] = $column['name'];
			}
		}
		return $pk;
	}

/**
 * Overrides DboSource::index to handle SQLite index introspection
 * Returns an array of the indexes in given table name.
 *
 * @param string $model Name of model to inspect
 * @return array Fields in table. Keys are column and unique
 */
	public function index($model) {
		$result = array();
		$table = $this->fullTableName($model, false, false);

		if ($table) {
			$indexes = $this->query("PRAGMA index_list($table)");
			$pk = $this->_getPrimaryKey($table);
			if (is_array($indexes)) {
				foreach ($indexes as $i => $index) {
					$index = array_pop($index);
					$info = $this->query('PRAGMA index_info("' . $index['name'] . '")');
					$autoindex = preg_match('/^sqlite_autoindex/', $index['name']);

					$column = array();
					$first_column = '';
					foreach ($info as $j => $row) {
						$column[] = $row[0]['name'];
						if ($row[0]['seqno'] == 0) {
							$first_column = $row[0]['name'];
						}
					}

					if ($autoindex) {
						$index['name'] = ($column == $pk) ? 'PRIMARY' : $first_column;
					}

					$result[$index['name']] = array(
						'column' => (count($column) == 1) ? $column[0] : $column,
						'unique' => intval($index['unique'] == 1)
					);
				}
			}

			if (empty($result['PRIMARY']) && !empty($pk)) {
				$result['PRIMARY'] = array('column' => (count($pk) == 1) ? $pk[0] : $pk, 'unique' => 1);
			}
		}

		return $result;
	}

/**
 * Overrides DboSource::renderStatement to handle schema generation with SQLite-style indexes
 *
 * @param string $type
 * @param array $data
 * @return string
 */
	public function renderStatement($type, $data) {
		if (strtolower($type) == 'schema') {
			extract($data);

			foreach (array('columns', 'tableParameters') as $var) {
				if (is_array(${$var})) {
					${$var} = "\t" . join(",\n\t", array_filter(${$var}));
				} else {
					${$var} = '';
				}
			}

			if (!empty($primaryIndex)) {
				$columns .= ", PRIMARY KEY ({$this->_columnsAsString($primaryIndex['column'])})";
			}
			$indexes = is_array($indexes) ? join(";\n", $indexes) : '';

			return "CREATE TABLE {$table} (\n{$columns}) {$tableParameters}; {$indexes}";
		}
		return parent::renderStatement($type, $data);
	}

/**
 * PDO deals in objects, not resources, so overload accordingly.
 *
 * @return boolean
 */
	public function hasResult() {
		return is_object($this->_result);
	}

/**
 * Generate a "drop table" statement for the given Schema object
 *
 * @param CakeSchema $schema An instance of a subclass of CakeSchema
 * @param string $table Optional.  If specified only the table name given will be generated.
 *   Otherwise, all tables defined in the schema are generated.
 * @return string
 */
	public function dropSchema(CakeSchema $schema, $table = null) {
		$out = '';
		foreach ($schema->tables as $curTable => $columns) {
			if (!$table || $table == $curTable) {
				$out .= 'DROP TABLE IF EXISTS ' . $this->fullTableName($curTable) . ";\n";
			}
		}
		return $out;
	}

/**
 * Gets the schema name
 *
 * @return string The schema name
 */
	public function getSchemaName() {
		return "main"; // Sqlite Datasource does not support multidb
	}

/**
 * Check if the server support nested transactions
 *
 * @return boolean
 */
	public function nestedTransactionSupported() {
		return $this->useNestedTransactions && version_compare($this->getVersion(), '3.6.8', '>=');
	}

	private function _columnsAsString($cols = array()) {
		if (is_array($cols)) {
			return implode(', ', array_map(array(&$this, 'name'), $cols));
		} else {
			return $this->name($cols);
		}
	}
}
