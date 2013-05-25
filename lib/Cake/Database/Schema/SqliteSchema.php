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

use Cake\Database\Schema\Table;
use Cake\Error;

/**
 * Schema management/reflection features for Sqlite
 */
class SqliteSchema {

/**
 * The driver instance being used.
 *
 * @var Cake\Database\Driver\Sqlite
 */
	protected $_driver;

/**
 * Constructor
 *
 * @param Cake\Database\Driver\Sqlite $driver Driver to use.
 * @return void
 */
	public function __construct($driver) {
		$this->_driver = $driver;
	}

/**
 * Convert a column definition to the abstract types.
 *
 * The returned type will be a type that
 * Cake\Database\Type can handle.
 *
 * @param string $column The column type + length
 * @throws Cake\Error\Exception
 * @return array Array of column information.
 */
	public function convertColumn($column) {
		preg_match('/([a-z]+)(?:\(([0-9,]+)\))?/i', $column, $matches);
		if (empty($matches)) {
			throw new Error\Exception(__d('cake_dev', 'Unable to parse column type from "%s"', $column));
		}
		$col = strtolower($matches[1]);
		$length = null;
		if (isset($matches[2])) {
			$length = (int)$matches[2];
		}

		if ($col === 'bigint') {
			return ['type' => 'biginteger', 'length' => $length];
		}
		if (in_array($col, ['blob', 'clob'])) {
			return ['type' => 'binary', 'length' => null];
		}
		if (in_array($col, ['date', 'time', 'timestamp', 'datetime'])) {
			return ['type' => $col, 'length' => null];
		}
		if (strpos($col, 'decimal') !== false) {
			return ['type' => 'decimal', 'length' => null];
		}

		if (strpos($col, 'boolean') !== false) {
			return ['type' => 'boolean', 'length' => null];
		}
		if (strpos($col, 'int') !== false) {
			return ['type' => 'integer', 'length' => $length];
		}
		if ($col === 'char') {
			return ['type' => 'string', 'fixed' => true, 'length' => $length];
		}
		if (strpos($col, 'char') !== false) {
			return ['type' => 'string', 'length' => $length];
		}
		if (in_array($col, ['float', 'real', 'double'])) {
			return ['type' => 'float', 'length' => null];
		}
		return ['type' => 'text', 'length' => null];
	}

/**
 * Get the SQL to list the tables in Sqlite
 *
 * @param array $config The connection configuration to use for
 *    getting tables from.
 * @return array An array of (sql, params) to execute.
 */
	public function listTablesSql() {
		return ["SELECT name FROM sqlite_master WHERE type='table' ORDER BY name", []];
	}

/**
 * Get the SQL to describe a table in Sqlite.
 *
 * @param string $table The table name to describe
 * @return array An array of (sql, params) to execute.
 */
	public function describeTableSql($table) {
		return ["PRAGMA table_info(" . $this->_driver->quoteIdentifier($table) . ")", []];
	}

/**
 * Convert field description results into abstract schema fields.
 *
 * @param Cake\Database\Schema\Table $table The table object to append fields to.
 * @param array $row The row data from describeTableSql
 * @param array $fieldParams Additional field parameters to parse.
 */
	public function convertFieldDescription(Table $table, $row, $fieldParams = []) {
		$field = $this->convertColumn($row['type']);
		$field += [
			'null' => !$row['notnull'],
			'default' => $row['dflt_value'] === null ? null : trim($row['dflt_value'], "'"),
		];
		if ($row['pk'] == true) {
			$field['null'] = false;
		}
		$table->addColumn($row['name'], $field);
		if ($row['pk'] == true) {
			$table->addConstraint('primary', [
				'type' => Table::CONSTRAINT_PRIMARY,
				'columns' => [$row['name']]
			]);
		}
	}

/**
 * Generate the SQL fragment for a single column in Sqlite
 *
 * @param Cake\Database\Schema\Table $table The table object the column is in.
 * @param string $name The name of the column.
 * @return string SQL fragment.
 */
	public function columnSql(Table $table, $name) {
		$data = $table->column($name);
		$typeMap = [
			'string' => ' VARCHAR',
			'integer' => ' INTEGER',
			'biginteger' => ' BIGINT',
			'boolean' => ' BOOLEAN',
			'binary' => ' BLOB',
			'float' => ' FLOAT',
			'decimal' => ' DECIMAL',
			'text' => ' TEXT',
			'date' => ' DATE',
			'time' => ' TIME',
			'datetime' => ' DATETIME',
			'timestamp' => ' TIMESTAMP',
		];
		if (!isset($typeMap[$data['type']])) {
			throw new Error\Exception(__d('cake_dev', 'Unknown column type "%s"', $data['type']));
		}

		$out = $this->_driver->quoteIdentifier($name);
		$out .= $typeMap[$data['type']];

		$hasLength = ['integer', 'string'];
		if (in_array($data['type'], $hasLength, true) && isset($data['length'])) {
			$out .= '(' . (int)$data['length'] . ')';
		}
		$hasPrecision = ['float', 'decimal'];
		if (
			in_array($data['type'], $hasPrecision, true) &&
			(isset($data['length']) || isset($data['precision']))
		) {
			$out .= '(' . (int)$data['length'] . ',' . (int)$data['precision'] . ')';
		}
		if (isset($data['null']) && $data['null'] === false) {
			$out .= ' NOT NULL';
		}
		if ($data['type'] === 'integer' && $name == $table->primaryKey()[0]) {
			$out .= ' PRIMARY KEY AUTOINCREMENT';
		}
		if (isset($data['null']) && $data['null'] === true) {
			$out .= ' DEFAULT NULL';
			unset($data['default']);
		}
		if (isset($data['default'])) {
			$out .= ' DEFAULT ' . $this->_driver->schemaValue($data['default']);
		}
		return $out;
	}

/**
 * Generate the SQL fragments for defining table constraints.
 *
 * Note integer primary keys will return ''. This is intentional as Sqlite requires
 * that integer primary keys be defined in the column definition.
 *
 * @param Cake\Database\Schema\Table $table The table object the column is in.
 * @param string $name The name of the column.
 * @return string SQL fragment.
 */
	public function constraintSql(Table $table, $name) {
		$data = $table->constraint($name);
		if (
			count($data['columns']) === 1 &&
			$table->column($data['columns'][0])['type'] === 'integer'
		) {
			return '';
		}
		if ($data['type'] === Table::CONSTRAINT_PRIMARY) {
			$type = 'PRIMARY KEY';
		}
		if ($data['type'] === Table::CONSTRAINT_UNIQUE) {
			$type = 'UNIQUE';
		}
		$columns = array_map(
			[$this->_driver, 'quoteIdentifier'],
			$data['columns']
		);
		return sprintf('CONSTRAINT %s %s (%s)',
			$this->_driver->quoteIdentifier($name),
			$type,
			implode(', ', $columns)
		);
	}

/**
 * Generate the SQL fragment for a single index.
 *
 * @param Cake\Database\Schema\Table $table The table object the column is in.
 * @param string $name The name of the column.
 * @return string SQL fragment.
 */
	public function indexSql(Table $table, $name) {
		$data = $table->index($name);
		$columns = array_map(
			[$this->_driver, 'quoteIdentifier'],
			$data['columns']
		);
		return sprintf('CREATE INDEX %s ON %s (%s)',
			$this->_driver->quoteIdentifier($name),
			$this->_driver->quoteIdentifier($table->name()),
			implode(', ', $columns)
		);
	}

/**
 * Generate the SQL to create a table.
 *
 * @param Table $table Cake\Database\Schema\Table instance
 * @param array $columns The columns to go inside the table.
 * @param array $constraints The constraints for the table.
 * @param array $indexes The indexes for the table.
 * @return array Complete CREATE TABLE statement(s)S
 */
	public function createTableSql($table, $columns, $constraints, $indexes) {
		$lines = array_merge($columns, $constraints);
		$content = implode(",\n", array_filter($lines));
		$table = sprintf("CREATE TABLE \"%s\" (\n%s\n)", $table->name(), $content);
		$out = [$table];
		foreach ($indexes as $index) {
			$out[] = $index;
		}
		return $out;
	}

/**
 * Generate the SQL to drop a table.
 *
 * @param Cake\Database\Schema\Table $table Table instance
 * @return string DROP TABLE sql
 */
	public function dropTableSql(Table $table) {
		return [sprintf('DROP TABLE "%s"', $table->name())];
	}

/**
 * Generate the SQL to truncate a table.
 *
 * @param Cake\Database\Schema\Table $table Table instance
 * @return array SQL statements to drop truncate a table.
 */
	public function truncateTableSql(Table $table) {
		$name = $table->name();
		return [
			sprintf('DELETE FROM sqlite_sequence WHERE name="%s"', $name),
			sprintf('DELETE FROM "%s"', $name)
		];
	}

}
