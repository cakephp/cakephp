<?php
/**
 * PHP Version 5.4
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
 * @since         CakePHP(tm) v 3.0.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
namespace Cake\Database\Schema;

use Cake\Database\Connection;
use Cake\Database\Exception;

/**
 * Represents a single table in a database schema.
 *
 * Can either be populated using the reflection API's
 * or by incrementally building an instance using
 * methods.
 *
 * Once created Table instances can be added to
 * Schema\Collection objects.
 */
class Table {

/**
 * The name of the table
 *
 * @var string
 */
	protected $_table;

/**
 * Columns in the table.
 *
 * @var array
 */
	protected $_columns = [];

/**
 * Indexes in the table.
 *
 * @var array
 */
	protected $_indexes = [];

/**
 * Constraints in the table.
 *
 * @var array
 */
	protected $_constraints = [];

/**
 * Options for the table.
 *
 * @var array
 */
	protected $_options = [];

/**
 * The valid keys that can be used in a column
 * definition.
 *
 * @var array
 */
	protected $_columnKeys = [
		'type' => null,
		'length' => null,
		'precision' => null,
		'null' => null,
		'default' => null,
		'fixed' => null,
		'comment' => null,
	];

/**
 * The valid keys that can be used in an index
 * definition.
 *
 * @var array
 */
	protected $_indexKeys = [
		'type' => null,
		'columns' => [],
		'length' => [],
		'references' => [],
		'update' => 'restrict',
		'delete' => 'restrict',
	];

/**
 * Names of the valid index types.
 *
 * @var array
 */
	protected $_validIndexTypes = [
		self::INDEX_INDEX,
		self::INDEX_FULLTEXT,
	];

/**
 * Names of the valid constraint types.
 *
 * @var array
 */
	protected $_validConstraintTypes = [
		self::CONSTRAINT_PRIMARY,
		self::CONSTRAINT_UNIQUE,
		self::CONSTRAINT_FOREIGN,
	];

	const CONSTRAINT_PRIMARY = 'primary';
	const CONSTRAINT_UNIQUE = 'unique';
	const CONSTRAINT_FOREIGN = 'foreign';

	const INDEX_INDEX = 'index';
	const INDEX_FULLTEXT = 'fulltext';

	const ACTION_CASCADE = 'cascade';
	const ACTION_SET_NULL = 'setNull';
	const ACTION_NO_ACTION = 'noAction';
	const ACTION_RESTRICT = 'restrict';

/**
 * Constructor.
 *
 * @param string $table The table name.
 * @param array $columns The list of columns for the schema.
 */
	public function __construct($table, $columns = array()) {
		$this->_table = $table;
		foreach ($columns as $field => $definition) {
			$this->addColumn($field, $definition);
		}
	}

/**
 * Get the name of the table.
 *
 * @return string
 */
	public function name() {
		return $this->_table;
	}

/**
 * Add a column to the table.
 *
 * ### Attributes
 *
 * Columns can have several attributes:
 *
 * - `type` The type of the column. This should be
 *   one of CakePHP's abstract types.
 * - `length` The length of the column.
 * - `precision` The number of decimal places to store
 *   for float and decimal types.
 * - `default` The default value of the column.
 * - `null` Whether or not the column can hold nulls.
 * - `fixed` Whether or not the column is a fixed length column.
 *   this is only useful for string colums.
 *
 * In addition to the above keys, the following keys are
 * implemented in some database dialects, but not all:
 *
 * - `comment` The comment for the column.
 *
 * @param string $name The name of the column
 * @param array $attrs The attributes for the column.
 * @return Table $this
 */
	public function addColumn($name, $attrs) {
		if (is_string($attrs)) {
			$attrs = ['type' => $attrs];
		}
		$attrs = array_intersect_key($attrs, $this->_columnKeys);
		$this->_columns[$name] = $attrs + $this->_columnKeys;
		return $this;
	}

/**
 * Get the column names in the table.
 *
 * @return array
 */
	public function columns() {
		return array_keys($this->_columns);
	}

/**
 * Get column data in the table.
 *
 * @param string $name The column name.
 * @return array|null Column data or null.
 */
	public function column($name) {
		if (!isset($this->_columns[$name])) {
			return null;
		}
		return $this->_columns[$name];
	}

/**
 * Convenience method for getting the type of a given column.
 *
 * @param string $name The column to get the type of.
 * @return string|null Either the column type or null.
 */
	public function columnType($name) {
		if (!isset($this->_columns[$name])) {
			return null;
		}
		return $this->_columns[$name]['type'];
	}

/**
 * Add an index.
 *
 * Used to add indexes, and full text indexes in platforms that support
 * them.
 *
 * ### Attributes
 *
 * - `type` The type of index being added.
 * - `columns` The columns in the index.
 *
 * @param string $name The name of the index.
 * @param array $attrs The attributes for the index.
 * @return Table $this
 * @throws Cake\Database\Exception
 */
	public function addIndex($name, $attrs) {
		if (is_string($attrs)) {
			$attrs = ['type' => $attrs];
		}
		$attrs = array_intersect_key($attrs, $this->_indexKeys);
		$attrs = $attrs + $this->_indexKeys;
		unset($attrs['references'], $attrs['update'], $attrs['delete']);

		if (!in_array($attrs['type'], $this->_validIndexTypes, true)) {
			throw new Exception(__d('cake_dev', 'Invalid index type "%s"', $attrs['type']));
		}
		if (empty($attrs['columns'])) {
			throw new Exception(__d('cake_dev', 'Indexes must define columns.'));
		}
		$attrs['columns'] = (array)$attrs['columns'];
		foreach ($attrs['columns'] as $field) {
			if (empty($this->_columns[$field])) {
				throw new Exception(__d('cake_dev', 'Columns used in indexes must already exist.'));
			}
		}
		$this->_indexes[$name] = $attrs;
		return $this;
	}

/**
 * Get the names of all the indexes in the table.
 *
 * @return array
 */
	public function indexes() {
		return array_keys($this->_indexes);
	}

/**
 * Read information about an index based on name.
 *
 * @param string $name The name of the index.
 * @return array|null Array of index data, or null
 */
	public function index($name) {
		if (!isset($this->_indexes[$name])) {
			return null;
		}
		return $this->_indexes[$name];
	}

/**
 * Get the column(s) used for the primary key.
 *
 * @return array|null Column name(s) for the primary key.
 *   Null will be returned if a table has no primary key.
 */
	public function primaryKey() {
		foreach ($this->_constraints as $name => $data) {
			if ($data['type'] === self::CONSTRAINT_PRIMARY) {
				return $data['columns'];
			}
		}
		return null;
	}

/**
 * Add a constraint.
 *
 * Used to add constraints to a table. For example primary keys, unique
 * keys and foriegn keys.
 *
 * ### Attributes
 *
 * - `type` The type of constraint being added.
 * - `columns` The columns in the index.
 * - `references` The table, column a foreign key references.
 * - `update` The behavior on update. Options are 'restrict', 'setNull', 'cascade', 'noAction'.
 * - `delete` The behavior on delete. Options are 'restrict', 'setNull', 'cascade', 'noAction'.
 *
 * The default for 'update' & 'delete' is 'cascade'.
 *
 * @param string $name The name of the constraint.
 * @param array $attrs The attributes for the constraint.
 * @return Table $this
 * @throws Cake\Database\Exception
 */
	public function addConstraint($name, $attrs) {
		if (is_string($attrs)) {
			$attrs = ['type' => $attrs];
		}
		$attrs = array_intersect_key($attrs, $this->_indexKeys);
		$attrs = $attrs + $this->_indexKeys;
		if (!in_array($attrs['type'], $this->_validConstraintTypes, true)) {
			throw new Exception(__d('cake_dev', 'Invalid constraint type "%s"', $attrs['type']));
		}
		if (empty($attrs['columns'])) {
			throw new Exception(__d('cake_dev', 'Constraints must define columns.'));
		}
		$attrs['columns'] = (array)$attrs['columns'];
		foreach ($attrs['columns'] as $field) {
			if (empty($this->_columns[$field])) {
				throw new Exception(__d('cake_dev', 'Columns used in constraints must already exist.'));
			}
		}
		if ($attrs['type'] === static::CONSTRAINT_FOREIGN) {
			$attrs = $this->_checkForeignKey($attrs);
		} else {
			unset($attrs['references'], $attrs['update'], $attrs['delete']);
		}
		$this->_constraints[$name] = $attrs;
		return $this;
	}

/**
 * Helper method to check/validate foreign keys.
 *
 * @param array $attrs Attributes to set.
 * @return array
 * @throws Cake\Database\Exception When foreign key definition is not valid.
 */
	protected function _checkForeignKey($attrs) {
		if (count($attrs['references']) < 2) {
			throw new Exception(__d('cake_dev', 'References must contain a table and column.'));
		}
		$validActions = [static::ACTION_CASCADE, static::ACTION_RESTRICT, static::ACTION_SET_NULL, static::ACTION_NO_ACTION];
		if (!in_array($attrs['update'], $validActions)) {
			throw new Exception(__d('cake_dev', 'Update action is invalid. Must be one of %s', implode(',', $validActions)));
		}
		if (!in_array($attrs['delete'], $validActions)) {
			throw new Exception(__d('cake_dev', 'Delete action is invalid. Must be one of %s', implode(',', $validActions)));
		}
		return $attrs;
	}

/**
 * Get the names of all the constraints in the table.
 *
 * @return array
 */
	public function constraints() {
		return array_keys($this->_constraints);
	}

/**
 * Read information about an constraint based on name.
 *
 * @param string $name The name of the constraint.
 * @return array|null Array of constraint data, or null
 */
	public function constraint($name) {
		if (!isset($this->_constraints[$name])) {
			return null;
		}
		return $this->_constraints[$name];
	}

/**
 * Get/set the options for a table.
 *
 * Table options allow you to set platform specific table level options.
 * For example the engine type in MySQL.
 *
 * @param array|null $options The options to set, or null to read options.
 * @return this|array Either the table instance, or an array of options when reading.
 */
	public function options($options = null) {
		if ($options === null) {
			return $this->_options;
		}
		$this->_options = array_merge($this->_options, $options);
		return $this;
	}

/**
 * Generate the SQL to create the Table.
 *
 * Uses the connection to access the schema dialect
 * to generate platform specific SQL.
 *
 * @param Connection $connection The connection to generate SQL for
 * @return array List of SQL statements to create the table and the
 *    required indexes.
 */
	public function createSql(Connection $connection) {
		$dialect = $connection->driver()->schemaDialect();
		$columns = $constraints = $indexes = [];
		foreach (array_keys($this->_columns) as $name) {
			$columns[] = $dialect->columnSql($this, $name);
		}
		foreach (array_keys($this->_constraints) as $name) {
			$constraints[] = $dialect->constraintSql($this, $name);
		}
		foreach (array_keys($this->_indexes) as $name) {
			$indexes[] = $dialect->indexSql($this, $name);
		}
		return $dialect->createTableSql($this, $columns, $constraints, $indexes);
	}

/**
 * Generate the SQL to drop a table.
 *
 * Uses the connection to access the schema dialect to generate platform
 * specific SQL.
 *
 * @param Connection $connection The connection to generate SQL for.
 * @return array SQL to drop a table.
 */
	public function dropSql(Connection $connection) {
		$dialect = $connection->driver()->schemaDialect();
		return $dialect->dropTableSql($this);
	}

/**
 * Generate the SQL statements to truncate a table
 *
 * @param Connection $connection The connection to generate SQL for.
 * @return array SQL to drop a table.
 */
	public function truncateSql(Connection $connection) {
		$dialect = $connection->driver()->schemaDialect();
		return $dialect->truncateTableSql($this);
	}

}
