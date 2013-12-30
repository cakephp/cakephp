<?php
/**
 * PostgreSQL layer for DBO.
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
 * @since         CakePHP(tm) v 0.9.1.114
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */

App::uses('DboSource', 'Model/Datasource');

/**
 * PostgreSQL layer for DBO.
 *
 * @package       Cake.Model.Datasource.Database
 */
class Postgres extends DboSource {

/**
 * Driver description
 *
 * @var string
 */
	public $description = "PostgreSQL DBO Driver";

/**
 * Base driver configuration settings. Merged with user settings.
 *
 * @var array
 */
	protected $_baseConfig = array(
		'persistent' => true,
		'host' => 'localhost',
		'login' => 'root',
		'password' => '',
		'database' => 'cake',
		'schema' => 'public',
		'port' => 5432,
		'encoding' => ''
	);

/**
 * Columns
 *
 * @var array
 */
	public $columns = array(
		'primary_key' => array('name' => 'serial NOT NULL'),
		'string' => array('name' => 'varchar', 'limit' => '255'),
		'text' => array('name' => 'text'),
		'integer' => array('name' => 'integer', 'formatter' => 'intval'),
		'biginteger' => array('name' => 'bigint', 'limit' => '20'),
		'float' => array('name' => 'float', 'formatter' => 'floatval'),
		'datetime' => array('name' => 'timestamp', 'format' => 'Y-m-d H:i:s', 'formatter' => 'date'),
		'timestamp' => array('name' => 'timestamp', 'format' => 'Y-m-d H:i:s', 'formatter' => 'date'),
		'time' => array('name' => 'time', 'format' => 'H:i:s', 'formatter' => 'date'),
		'date' => array('name' => 'date', 'format' => 'Y-m-d', 'formatter' => 'date'),
		'binary' => array('name' => 'bytea'),
		'boolean' => array('name' => 'boolean'),
		'number' => array('name' => 'numeric'),
		'inet' => array('name' => 'inet')
	);

/**
 * Starting Quote
 *
 * @var string
 */
	public $startQuote = '"';

/**
 * Ending Quote
 *
 * @var string
 */
	public $endQuote = '"';

/**
 * Contains mappings of custom auto-increment sequences, if a table uses a sequence name
 * other than what is dictated by convention.
 *
 * @var array
 */
	protected $_sequenceMap = array();

/**
 * The set of valid SQL operations usable in a WHERE statement
 *
 * @var array
 */
	protected $_sqlOps = array('like', 'ilike', 'or', 'not', 'in', 'between', '~', '~*', '!~', '!~*', 'similar to');

/**
 * Connects to the database using options in the given configuration array.
 *
 * @return boolean True if successfully connected.
 * @throws MissingConnectionException
 */
	public function connect() {
		$config = $this->config;
		$this->connected = false;

		$flags = array(
			PDO::ATTR_PERSISTENT => $config['persistent'],
			PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
		);

		try {
			$this->_connection = new PDO(
				"pgsql:host={$config['host']};port={$config['port']};dbname={$config['database']}",
				$config['login'],
				$config['password'],
				$flags
			);

			$this->connected = true;
			if (!empty($config['encoding'])) {
				$this->setEncoding($config['encoding']);
			}
			if (!empty($config['schema'])) {
				$this->_execute('SET search_path TO "' . $config['schema'] . '"');
			}
			if (!empty($config['settings'])) {
				foreach ($config['settings'] as $key => $value) {
					$this->_execute("SET $key TO $value");
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
 * Check if PostgreSQL is enabled/loaded
 *
 * @return boolean
 */
	public function enabled() {
		return in_array('pgsql', PDO::getAvailableDrivers());
	}

/**
 * Returns an array of tables in the database. If there are no tables, an error is raised and the application exits.
 *
 * @param mixed $data
 * @return array Array of table names in the database
 */
	public function listSources($data = null) {
		$cache = parent::listSources();

		if ($cache) {
			return $cache;
		}

		$schema = $this->config['schema'];
		$sql = "SELECT table_name as name FROM INFORMATION_SCHEMA.tables WHERE table_schema = ?";
		$result = $this->_execute($sql, array($schema));

		if (!$result) {
			return array();
		}

		$tables = array();

		foreach ($result as $item) {
			$tables[] = $item->name;
		}

		$result->closeCursor();
		parent::listSources($tables);
		return $tables;
	}

/**
 * Returns an array of the fields in given table name.
 *
 * @param Model|string $model Name of database table to inspect
 * @return array Fields in table. Keys are name and type
 */
	public function describe($model) {
		$table = $this->fullTableName($model, false, false);
		$fields = parent::describe($table);
		$this->_sequenceMap[$table] = array();
		$cols = null;

		if ($fields === null) {
			$cols = $this->_execute(
				"SELECT DISTINCT table_schema AS schema, column_name AS name, data_type AS type, is_nullable AS null,
					column_default AS default, ordinal_position AS position, character_maximum_length AS char_length,
					character_octet_length AS oct_length FROM information_schema.columns
				WHERE table_name = ? AND table_schema = ?  ORDER BY position",
				array($table, $this->config['schema'])
			);

			// @codingStandardsIgnoreStart
			// Postgres columns don't match the coding standards.
			foreach ($cols as $c) {
				$type = $c->type;
				if (!empty($c->oct_length) && $c->char_length === null) {
					if ($c->type === 'character varying') {
						$length = null;
						$type = 'text';
					} elseif ($c->type === 'uuid') {
						$length = 36;
					} else {
						$length = intval($c->oct_length);
					}
				} elseif (!empty($c->char_length)) {
					$length = intval($c->char_length);
				} else {
					$length = $this->length($c->type);
				}
				if (empty($length)) {
					$length = null;
				}
				$fields[$c->name] = array(
					'type' => $this->column($type),
					'null' => ($c->null === 'NO' ? false : true),
					'default' => preg_replace(
						"/^'(.*)'$/",
						"$1",
						preg_replace('/::.*/', '', $c->default)
					),
					'length' => $length
				);
				if ($model instanceof Model) {
					if ($c->name == $model->primaryKey) {
						$fields[$c->name]['key'] = 'primary';
						if ($fields[$c->name]['type'] !== 'string') {
							$fields[$c->name]['length'] = 11;
						}
					}
				}
				if (
					$fields[$c->name]['default'] === 'NULL' ||
					preg_match('/nextval\([\'"]?([\w.]+)/', $c->default, $seq)
				) {
					$fields[$c->name]['default'] = null;
					if (!empty($seq) && isset($seq[1])) {
						if (strpos($seq[1], '.') === false) {
							$sequenceName = $c->schema . '.' . $seq[1];
						} else {
							$sequenceName = $seq[1];
						}
						$this->_sequenceMap[$table][$c->name] = $sequenceName;
					}
				}
				if ($fields[$c->name]['type'] === 'boolean' && !empty($fields[$c->name]['default'])) {
					$fields[$c->name]['default'] = constant($fields[$c->name]['default']);
				}
			}
			$this->_cacheDescription($table, $fields);
		}
		// @codingStandardsIgnoreEnd

		if (isset($model->sequence)) {
			$this->_sequenceMap[$table][$model->primaryKey] = $model->sequence;
		}

		if ($cols) {
			$cols->closeCursor();
		}
		return $fields;
	}

/**
 * Returns the ID generated from the previous INSERT operation.
 *
 * @param string $source Name of the database table
 * @param string $field Name of the ID database field. Defaults to "id"
 * @return integer
 */
	public function lastInsertId($source = null, $field = 'id') {
		$seq = $this->getSequence($source, $field);
		return $this->_connection->lastInsertId($seq);
	}

/**
 * Gets the associated sequence for the given table/field
 *
 * @param string|Model $table Either a full table name (with prefix) as a string, or a model object
 * @param string $field Name of the ID database field. Defaults to "id"
 * @return string The associated sequence name from the sequence map, defaults to "{$table}_{$field}_seq"
 */
	public function getSequence($table, $field = 'id') {
		if (is_object($table)) {
			$table = $this->fullTableName($table, false, false);
		}
		if (!isset($this->_sequenceMap[$table])) {
			$this->describe($table);
		}
		if (isset($this->_sequenceMap[$table][$field])) {
			return $this->_sequenceMap[$table][$field];
		}
		return "{$table}_{$field}_seq";
	}

/**
 * Reset a sequence based on the MAX() value of $column. Useful
 * for resetting sequences after using insertMulti().
 *
 * @param string $table The name of the table to update.
 * @param string $column The column to use when resetting the sequence value,
 *   the sequence name will be fetched using Postgres::getSequence();
 * @return boolean success.
 */
	public function resetSequence($table, $column) {
		$tableName = $this->fullTableName($table, false, false);
		$fullTable = $this->fullTableName($table);

		$sequence = $this->value($this->getSequence($tableName, $column));
		$column = $this->name($column);
		$this->execute("SELECT setval($sequence, (SELECT MAX($column) FROM $fullTable))");
		return true;
	}

/**
 * Deletes all the records in a table and drops all associated auto-increment sequences
 *
 * @param string|Model $table A string or model class representing the table to be truncated
 * @param boolean $reset true for resetting the sequence, false to leave it as is.
 *    and if 1, sequences are not modified
 * @return boolean SQL TRUNCATE TABLE statement, false if not applicable.
 */
	public function truncate($table, $reset = false) {
		$table = $this->fullTableName($table, false, false);
		if (!isset($this->_sequenceMap[$table])) {
			$cache = $this->cacheSources;
			$this->cacheSources = false;
			$this->describe($table);
			$this->cacheSources = $cache;
		}
		if ($this->execute('DELETE FROM ' . $this->fullTableName($table))) {
			if (isset($this->_sequenceMap[$table]) && $reset != true) {
				foreach ($this->_sequenceMap[$table] as $sequence) {
					list($schema, $sequence) = explode('.', $sequence);
					$this->_execute("ALTER SEQUENCE \"{$schema}\".\"{$sequence}\" RESTART WITH 1");
				}
			}
			return true;
		}
		return false;
	}

/**
 * Prepares field names to be quoted by parent
 *
 * @param string $data
 * @return string SQL field
 */
	public function name($data) {
		if (is_string($data)) {
			$data = str_replace('"__"', '__', $data);
		}
		return parent::name($data);
	}

/**
 * Generates the fields list of an SQL query.
 *
 * @param Model $model
 * @param string $alias Alias table name
 * @param mixed $fields
 * @param boolean $quote
 * @return array
 */
	public function fields(Model $model, $alias = null, $fields = array(), $quote = true) {
		if (empty($alias)) {
			$alias = $model->alias;
		}
		$fields = parent::fields($model, $alias, $fields, false);

		if (!$quote) {
			return $fields;
		}
		$count = count($fields);

		if ($count >= 1 && !preg_match('/^\s*COUNT\(\*/', $fields[0])) {
			$result = array();
			for ($i = 0; $i < $count; $i++) {
				if (!preg_match('/^.+\\(.*\\)/', $fields[$i]) && !preg_match('/\s+AS\s+/', $fields[$i])) {
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

					$prepend = '';
					if (strpos($fields[$i], 'DISTINCT') !== false) {
						$prepend = 'DISTINCT ';
						$fields[$i] = trim(str_replace('DISTINCT', '', $fields[$i]));
					}

					if (strrpos($fields[$i], '.') === false) {
						$fields[$i] = $prepend . $this->name($alias) . '.' . $this->name($fields[$i]) . ' AS ' . $this->name($alias . '__' . $fields[$i]);
					} else {
						$build = explode('.', $fields[$i]);
						$fields[$i] = $prepend . $this->name($build[0]) . '.' . $this->name($build[1]) . ' AS ' . $this->name($build[0] . '__' . $build[1]);
					}
				} else {
					$fields[$i] = preg_replace_callback('/\(([\s\.\w]+)\)/', array(&$this, '_quoteFunctionField'), $fields[$i]);
				}
				$result[] = $fields[$i];
			}
			return $result;
		}
		return $fields;
	}

/**
 * Auxiliary function to quote matched `(Model.fields)` from a preg_replace_callback call
 * Quotes the fields in a function call.
 *
 * @param string $match matched string
 * @return string quoted string
 */
	protected function _quoteFunctionField($match) {
		$prepend = '';
		if (strpos($match[1], 'DISTINCT') !== false) {
			$prepend = 'DISTINCT ';
			$match[1] = trim(str_replace('DISTINCT', '', $match[1]));
		}
		$constant = preg_match('/^\d+|NULL|FALSE|TRUE$/i', $match[1]);

		if (!$constant && strpos($match[1], '.') === false) {
			$match[1] = $this->name($match[1]);
		} elseif (!$constant) {
			$parts = explode('.', $match[1]);
			if (!Hash::numeric($parts)) {
				$match[1] = $this->name($match[1]);
			}
		}
		return '(' . $prepend . $match[1] . ')';
	}

/**
 * Returns an array of the indexes in given datasource name.
 *
 * @param string $model Name of model to inspect
 * @return array Fields in table. Keys are column and unique
 */
	public function index($model) {
		$index = array();
		$table = $this->fullTableName($model, false, false);
		if ($table) {
			$indexes = $this->query("SELECT c2.relname, i.indisprimary, i.indisunique, i.indisclustered, i.indisvalid, pg_catalog.pg_get_indexdef(i.indexrelid, 0, true) as statement, c2.reltablespace
			FROM pg_catalog.pg_class c, pg_catalog.pg_class c2, pg_catalog.pg_index i
			WHERE c.oid  = (
				SELECT c.oid
				FROM pg_catalog.pg_class c LEFT JOIN pg_catalog.pg_namespace n ON n.oid = c.relnamespace
				WHERE c.relname ~ '^(" . $table . ")$'
					AND pg_catalog.pg_table_is_visible(c.oid)
					AND n.nspname ~ '^(" . $this->config['schema'] . ")$'
			)
			AND c.oid = i.indrelid AND i.indexrelid = c2.oid
			ORDER BY i.indisprimary DESC, i.indisunique DESC, c2.relname", false);
			foreach ($indexes as $info) {
				$key = array_pop($info);
				if ($key['indisprimary']) {
					$key['relname'] = 'PRIMARY';
				}
				preg_match('/\(([^\)]+)\)/', $key['statement'], $indexColumns);
				$parsedColumn = $indexColumns[1];
				if (strpos($indexColumns[1], ',') !== false) {
					$parsedColumn = explode(', ', $indexColumns[1]);
				}
				$index[$key['relname']]['unique'] = $key['indisunique'];
				$index[$key['relname']]['column'] = $parsedColumn;
			}
		}
		return $index;
	}

/**
 * Alter the Schema of a table.
 *
 * @param array $compare Results of CakeSchema::compare()
 * @param string $table name of the table
 * @return array
 */
	public function alterSchema($compare, $table = null) {
		if (!is_array($compare)) {
			return false;
		}
		$out = '';
		$colList = array();
		foreach ($compare as $curTable => $types) {
			$indexes = $colList = array();
			if (!$table || $table == $curTable) {
				$out .= 'ALTER TABLE ' . $this->fullTableName($curTable) . " \n";
				foreach ($types as $type => $column) {
					if (isset($column['indexes'])) {
						$indexes[$type] = $column['indexes'];
						unset($column['indexes']);
					}
					switch ($type) {
						case 'add':
							foreach ($column as $field => $col) {
								$col['name'] = $field;
								$colList[] = 'ADD COLUMN ' . $this->buildColumn($col);
							}
							break;
						case 'drop':
							foreach ($column as $field => $col) {
								$col['name'] = $field;
								$colList[] = 'DROP COLUMN ' . $this->name($field);
							}
							break;
						case 'change':
							foreach ($column as $field => $col) {
								if (!isset($col['name'])) {
									$col['name'] = $field;
								}
								$fieldName = $this->name($field);

								$default = isset($col['default']) ? $col['default'] : null;
								$nullable = isset($col['null']) ? $col['null'] : null;
								unset($col['default'], $col['null']);
								if ($field !== $col['name']) {
									$newName = $this->name($col['name']);
									$out .= "\tRENAME {$fieldName} TO {$newName};\n";
									$out .= 'ALTER TABLE ' . $this->fullTableName($curTable) . " \n";
									$fieldName = $newName;
								}
								$colList[] = 'ALTER COLUMN ' . $fieldName . ' TYPE ' . str_replace(array($fieldName, 'NOT NULL'), '', $this->buildColumn($col));
								if (isset($nullable)) {
									$nullable = ($nullable) ? 'DROP NOT NULL' : 'SET NOT NULL';
									$colList[] = 'ALTER COLUMN ' . $fieldName . '  ' . $nullable;
								}

								if (isset($default)) {
									$colList[] = 'ALTER COLUMN ' . $fieldName . '  SET DEFAULT ' . $this->value($default, $col['type']);
								} else {
									$colList[] = 'ALTER COLUMN ' . $fieldName . '  DROP DEFAULT';
								}

							}
							break;
					}
				}
				if (isset($indexes['drop']['PRIMARY'])) {
					$colList[] = 'DROP CONSTRAINT ' . $curTable . '_pkey';
				}
				if (isset($indexes['add']['PRIMARY'])) {
					$cols = $indexes['add']['PRIMARY']['column'];
					if (is_array($cols)) {
						$cols = implode(', ', $cols);
					}
					$colList[] = 'ADD PRIMARY KEY (' . $cols . ')';
				}

				if (!empty($colList)) {
					$out .= "\t" . implode(",\n\t", $colList) . ";\n\n";
				} else {
					$out = '';
				}
				$out .= implode(";\n\t", $this->_alterIndexes($curTable, $indexes));
			}
		}
		return $out;
	}

/**
 * Generate PostgreSQL index alteration statements for a table.
 *
 * @param string $table Table to alter indexes for
 * @param array $indexes Indexes to add and drop
 * @return array Index alteration statements
 */
	protected function _alterIndexes($table, $indexes) {
		$alter = array();
		if (isset($indexes['drop'])) {
			foreach ($indexes['drop'] as $name => $value) {
				$out = 'DROP ';
				if ($name === 'PRIMARY') {
					continue;
				} else {
					$out .= 'INDEX ' . $name;
				}
				$alter[] = $out;
			}
		}
		if (isset($indexes['add'])) {
			foreach ($indexes['add'] as $name => $value) {
				$out = 'CREATE ';
				if ($name === 'PRIMARY') {
					continue;
				} else {
					if (!empty($value['unique'])) {
						$out .= 'UNIQUE ';
					}
					$out .= 'INDEX ';
				}
				if (is_array($value['column'])) {
					$out .= $name . ' ON ' . $table . ' (' . implode(', ', array_map(array(&$this, 'name'), $value['column'])) . ')';
				} else {
					$out .= $name . ' ON ' . $table . ' (' . $this->name($value['column']) . ')';
				}
				$alter[] = $out;
			}
		}
		return $alter;
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
			$rt = sprintf(' LIMIT %u', $limit);
			if ($offset) {
				$rt .= sprintf(' OFFSET %u', $offset);
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

		$floats = array(
			'float', 'float4', 'float8', 'double', 'double precision', 'decimal', 'real', 'numeric'
		);

		switch (true) {
			case (in_array($col, array('date', 'time', 'inet', 'boolean'))):
				return $col;
			case (strpos($col, 'timestamp') !== false):
				return 'datetime';
			case (strpos($col, 'time') === 0):
				return 'time';
			case ($col === 'bigint'):
				return 'biginteger';
			case (strpos($col, 'int') !== false && $col !== 'interval'):
				return 'integer';
			case (strpos($col, 'char') !== false || $col === 'uuid'):
				return 'string';
			case (strpos($col, 'text') !== false):
				return 'text';
			case (strpos($col, 'bytea') !== false):
				return 'binary';
			case (in_array($col, $floats)):
				return 'float';
			default:
				return 'text';
		}
	}

/**
 * Gets the length of a database-native column description, or null if no length
 *
 * @param string $real Real database-layer column type (i.e. "varchar(255)")
 * @return integer An integer representing the length of the column
 */
	public function length($real) {
		$col = str_replace(array(')', 'unsigned'), '', $real);
		$limit = null;

		if (strpos($col, '(') !== false) {
			list($col, $limit) = explode('(', $col);
		}
		if ($col === 'uuid') {
			return 36;
		}
		if ($limit) {
			return intval($limit);
		}
		return null;
	}

/**
 * resultSet method
 *
 * @param array $results
 * @return void
 */
	public function resultSet(&$results) {
		$this->map = array();
		$numFields = $results->columnCount();
		$index = 0;
		$j = 0;

		while ($j < $numFields) {
			$column = $results->getColumnMeta($j);
			if (strpos($column['name'], '__')) {
				list($table, $name) = explode('__', $column['name']);
				$this->map[$index++] = array($table, $name, $column['native_type']);
			} else {
				$this->map[$index++] = array(0, $column['name'], $column['native_type']);
			}
			$j++;
		}
	}

/**
 * Fetches the next row from the current result set
 *
 * @return array
 */
	public function fetchResult() {
		if ($row = $this->_result->fetch(PDO::FETCH_NUM)) {
			$resultRow = array();

			foreach ($this->map as $index => $meta) {
				list($table, $column, $type) = $meta;

				switch ($type) {
					case 'bool':
						$resultRow[$table][$column] = $row[$index] === null ? null : $this->boolean($row[$index]);
						break;
					case 'binary':
					case 'bytea':
						$resultRow[$table][$column] = $row[$index] === null ? null : stream_get_contents($row[$index]);
						break;
					default:
						$resultRow[$table][$column] = $row[$index];
				}
			}
			return $resultRow;
		}
		$this->_result->closeCursor();
		return false;
	}

/**
 * Translates between PHP boolean values and PostgreSQL boolean values
 *
 * @param mixed $data Value to be translated
 * @param boolean $quote true to quote a boolean to be used in a query, false to return the boolean value
 * @return boolean Converted boolean value
 */
	public function boolean($data, $quote = false) {
		switch (true) {
			case ($data === true || $data === false):
				$result = $data;
				break;
			case ($data === 't' || $data === 'f'):
				$result = ($data === 't');
				break;
			case ($data === 'true' || $data === 'false'):
				$result = ($data === 'true');
				break;
			case ($data === 'TRUE' || $data === 'FALSE'):
				$result = ($data === 'TRUE');
				break;
			default:
				$result = (bool)$data;
		}

		if ($quote) {
			return ($result) ? 'TRUE' : 'FALSE';
		}
		return (bool)$result;
	}

/**
 * Sets the database encoding
 *
 * @param mixed $enc Database encoding
 * @return boolean True on success, false on failure
 */
	public function setEncoding($enc) {
		return $this->_execute('SET NAMES ' . $this->value($enc)) !== false;
	}

/**
 * Gets the database encoding
 *
 * @return string The database encoding
 */
	public function getEncoding() {
		$result = $this->_execute('SHOW client_encoding')->fetch();
		if ($result === false) {
			return false;
		}
		return (isset($result['client_encoding'])) ? $result['client_encoding'] : false;
	}

/**
 * Generate a Postgres-native column schema string
 *
 * @param array $column An array structured like the following:
 *                      array('name'=>'value', 'type'=>'value'[, options]),
 *                      where options can be 'default', 'length', or 'key'.
 * @return string
 */
	public function buildColumn($column) {
		$col = $this->columns[$column['type']];
		if (!isset($col['length']) && !isset($col['limit'])) {
			unset($column['length']);
		}
		$out = parent::buildColumn($column);

		$out = preg_replace(
			'/integer\([0-9]+\)/',
			'integer',
			$out
		);
		$out = preg_replace(
			'/bigint\([0-9]+\)/',
			'bigint',
			$out
		);

		$out = str_replace('integer serial', 'serial', $out);
		$out = str_replace('bigint serial', 'bigserial', $out);
		if (strpos($out, 'timestamp DEFAULT')) {
			if (isset($column['null']) && $column['null']) {
				$out = str_replace('DEFAULT NULL', '', $out);
			} else {
				$out = str_replace('DEFAULT NOT NULL', '', $out);
			}
		}
		if (strpos($out, 'DEFAULT DEFAULT')) {
			if (isset($column['null']) && $column['null']) {
				$out = str_replace('DEFAULT DEFAULT', 'DEFAULT NULL', $out);
			} elseif (in_array($column['type'], array('integer', 'float'))) {
				$out = str_replace('DEFAULT DEFAULT', 'DEFAULT 0', $out);
			} elseif ($column['type'] === 'boolean') {
				$out = str_replace('DEFAULT DEFAULT', 'DEFAULT FALSE', $out);
			}
		}
		return $out;
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
		if (!is_array($indexes)) {
			return array();
		}
		foreach ($indexes as $name => $value) {
			if ($name === 'PRIMARY') {
				$out = 'PRIMARY KEY  (' . $this->name($value['column']) . ')';
			} else {
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
			}
			$join[] = $out;
		}
		return $join;
	}

/**
 * Overrides DboSource::renderStatement to handle schema generation with Postgres-style indexes
 *
 * @param string $type
 * @param array $data
 * @return string
 */
	public function renderStatement($type, $data) {
		switch (strtolower($type)) {
			case 'schema':
				extract($data);

				foreach ($indexes as $i => $index) {
					if (preg_match('/PRIMARY KEY/', $index)) {
						unset($indexes[$i]);
						$columns[] = $index;
						break;
					}
				}
				$join = array('columns' => ",\n\t", 'indexes' => "\n");

				foreach (array('columns', 'indexes') as $var) {
					if (is_array(${$var})) {
						${$var} = implode($join[$var], array_filter(${$var}));
					}
				}
				return "CREATE TABLE {$table} (\n\t{$columns}\n);\n{$indexes}";
			default:
				return parent::renderStatement($type, $data);
		}
	}

/**
 * Gets the schema name
 *
 * @return string The schema name
 */
	public function getSchemaName() {
		return $this->config['schema'];
	}

/**
 * Check if the server support nested transactions
 *
 * @return boolean
 */
	public function nestedTransactionSupported() {
		return $this->useNestedTransactions && version_compare($this->getVersion(), '8.0', '>=');
	}

}
