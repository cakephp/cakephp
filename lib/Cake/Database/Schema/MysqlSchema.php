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
 * Schema management/reflection features for MySQL
 */
class MysqlSchema {

/**
 * The driver instance being used.
 *
 * @var Cake\Database\Driver\Mysql
 */
	protected $_driver;

/**
 * Constructor
 *
 * @param Cake\Database\Driver $driver The driver to use.
 * @return void
 */
	public function __construct($driver) {
		$this->_driver = $driver;
	}

/**
 * Get the SQL to list the tables in MySQL
 *
 * @param array $config The connection configuration to use for
 *    getting tables from.
 * @return array An array of (sql, params) to execute.
 */
	public function listTablesSql(array $config) {
		return ["SHOW TABLES FROM " . $this->_driver->quoteIdentifier($config['database']), []];
	}

/**
 * Get the SQL to describe a table in MySQL.
 *
 * @param string $table The table name to describe.
 * @return array An array of (sql, params) to execute.
 */
	public function describeTableSql($table) {
		return ["SHOW FULL COLUMNS FROM " . $this->_driver->quoteIdentifier($table), []];
	}

/**
 * Convert a MySQL column type into an abstract type.
 *
 * The returned type will be a type that Cake\Database\Type can handle.
 *
 * @param string $column The column type + length
 * @return array Array of column information.
 * @throws Cake\Error\Exception When column type cannot be parsed.
 */
	public function convertColumn($column) {
		preg_match('/([a-z]+)(?:\(([0-9,]+)\))?/i', $column, $matches);
		if (empty($matches)) {
			throw new Error\Exception(__d('cake_dev', 'Unable to parse column type from "%s"', $column));
		}

		$col = strtolower($matches[1]);
		$length = $precision = null;
		if (isset($matches[2])) {
			$length = $matches[2];
			if (strpos($matches[2], ',') !== false) {
				list($length, $precision) = explode(',', $length);
			}
			$length = (int)$length;
			$precision = (int)$precision;
		}

		if (in_array($col, array('date', 'time', 'datetime', 'timestamp'))) {
			return ['type' => $col, 'length' => null];
		}
		if (($col === 'tinyint' && $length === 1) || $col === 'boolean') {
			return ['type' => 'boolean', 'length' => null];
		}
		if (strpos($col, 'bigint') !== false || $col === 'bigint') {
			return ['type' => 'biginteger', 'length' => $length];
		}
		if (strpos($col, 'int') !== false) {
			return ['type' => 'integer', 'length' => $length];
		}
		if ($col === 'char') {
			return ['type' => 'string', 'fixed' => true, 'length' => $length];
		}
		if (strpos($col, 'char') !== false || $col === 'tinytext') {
			return ['type' => 'string', 'length' => $length];
		}
		if (strpos($col, 'text') !== false) {
			return ['type' => 'text', 'length' => $length];
		}
		if (strpos($col, 'blob') !== false || $col === 'binary') {
			return ['type' => 'binary', 'length' => $length];
		}
		if (strpos($col, 'float') !== false || strpos($col, 'double') !== false) {
			return ['type' => 'float', 'length' => $length, 'precision' => $precision];
		}
		if (strpos($col, 'decimal') !== false) {
			return ['type' => 'decimal', 'length' => $length, 'precision' => $precision];
		}
		return ['type' => 'text', 'length' => null];
	}

/**
 * Convert field description results into abstract schema fields.
 *
 * @param Cake\Database\Schema\Table $table The table object to append fields to.
 * @param array $row The row data from describeTableSql
 * @param array $fieldParams Additional field parameters to parse.
 * @return void
 */
	public function convertFieldDescription(Table $table, $row, $fieldParams = []) {
		$field = $this->convertColumn($row['Type']);
		$field += [
			'null' => $row['Null'] === 'YES' ? true : false,
			'default' => $row['Default'],
		];
		foreach ($fieldParams as $key => $metadata) {
			if (!empty($row[$metadata['column']])) {
				$field[$key] = $row[$metadata['column']];
			}
		}
		$table->addColumn($row['Field'], $field);
		if (!empty($row['Key']) && $row['Key'] === 'PRI') {
			$table->addIndex('primary', [
				'type' => Table::INDEX_PRIMARY,
				'columns' => [$row['Field']]
			]);
		}
	}

/**
 * Get additional column meta data used in schema reflections.
 *
 * @return array
 */
	public function extraSchemaColumns() {
		return [
			'charset' => [
				'column' => false,
			],
			'collate' => [
				'column' => 'Collation',
			],
			'comment' => [
				'column' => 'Comment',
			]
		];
	}

/**
 * Generate the SQL to create a table.
 *
 * @param string $table The name of the table.
 * @param array $lines The lines (columns + indexes) to go inside the table.
 * @return string A complete CREATE TABLE statement
 */
	public function createTableSql($table, $lines) {
		$content = implode(",\n", $lines);
		return sprintf("CREATE TABLE `%s` (\n%s\n);", $table, $content);
	}

/**
 * Generate the SQL fragment for a single column in MySQL
 *
 * @param Cake\Database\Schema\Table $table The table object the column is in.
 * @param string $name The name of the column.
 * @return string SQL fragment.
 */
	public function columnSql(Table $table, $name) {
		$data = $table->column($name);
		$out = $this->_driver->quoteIdentifier($name);
		$typeMap = [
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
		$specialMap = [
			'string' => true,
		];
		if (isset($typeMap[$data['type']])) {
			$out .= $typeMap[$data['type']];
		}
		if (isset($specialMap[$data['type']])) {
			switch ($data['type']) {
				case 'string':
					$out .= !empty($data['fixed']) ? ' CHAR' : ' VARCHAR';
					if (!isset($data['length'])) {
						$data['length'] = 255;
					}
				break;
			}
		}
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
		if (in_array($data['type'], ['integer', 'biginteger']) && in_array($name, (array)$table->primaryKey())) {
			$out .= ' AUTO_INCREMENT';
		}
		if (isset($data['null']) && $data['null'] === true) {
			$out .= $data['type'] === 'timestamp' ? ' NULL' : ' DEFAULT NULL';
			unset($data['default']);
		}
		if (isset($data['default']) && $data['type'] !== 'timestamp') {
			$out .= ' DEFAULT ' . $this->_driver->schemaValue($data['default']);
		}
		if (
			isset($data['default']) &&
			$data['type'] === 'timestamp' &&
			strtolower($data['default']) === 'current_timestamp'
		) {
			$out .= ' DEFAULT CURRENT_TIMESTAMP';
		}
		if (isset($data['comment'])) {
			$out .= ' COMMENT ' . $this->_driver->schemaValue($data['comment']);
		}
		return $out;
	}

/**
 * Generate the SQL fragment for a single index in MySQL
 *
 * @param Cake\Database\Schema\Table $table The table object the column is in.
 * @param string $name The name of the column.
 * @return string SQL fragment.
 */
	public function indexSql(Table $table, $name) {
		$data = $table->index($name);
		if ($data['type'] === Table::INDEX_PRIMARY) {
			$columns = array_map(
				[$this->_driver, 'quoteIdentifier'],
				$data['columns']
			);
			return sprintf('PRIMARY KEY (%s)', implode(', ', $columns));
		}
		if ($data['type'] === Table::INDEX_UNIQUE) {
			$out = 'UNIQUE KEY ';
		}
		if ($data['type'] === Table::INDEX_INDEX) {
			$out = 'KEY ';
		}
		if ($data['type'] === Table::INDEX_FULLTEXT) {
			$out = 'FULLTEXT KEY ';
		}
		$out .= $this->_driver->quoteIdentifier($name);

		$columns = array_map(
			[$this->_driver, 'quoteIdentifier'],
			$data['columns']
		);
		foreach ($data['columns'] as $i => $column) {
			if (isset($data['length'][$column])) {
				$columns[$i] .= sprintf('(%d)', $data['length'][$column]);
			}
		}
		return $out . ' (' . implode(', ', $columns) . ')';
	}

}
