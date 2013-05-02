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

/**
 * Schema dialect/support for MySQL
 */
class MysqlSchema {

/**
 * The driver instance being used.
 *
 * @var Cake\Database\Driver\Mysql
 */
	protected $driver;

/**
 * Constructor
 *
 * @param Cake\Database\Driver $driver The driver to use.
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
 * @return array List of (type, length)
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
			return ['type' => 'float', 'length' => $length];
		}
		if (strpos($col, 'decimal') !== false) {
			return ['type' => 'decimal', 'length' => null];
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

}
