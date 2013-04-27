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

class SqliteSchema {

/**
 * The driver instance being used.
 *
 * @var Cake\Database\Driver\Sqlite
 */
	protected $driver;

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

		if ($col === 'bigint') {
			return ['biginteger', $length];
		}
		if (in_array($col, ['blob', 'clob'])) {
			return ['binary', null];
		}
		if (in_array($col, ['date', 'time', 'timestamp', 'datetime'])) {
			return [$col, null];
		}
		if (strpos($col, 'decimal') !== false) {
			return ['decimal', null];
		}

		if (strpos($col, 'boolean') !== false) {
			return ['boolean', null];
		}
		if (strpos($col, 'int') !== false) {
			return ['integer', $length];
		}
		if (strpos($col, 'char') !== false) {
			return ['string', $length];
		}
		if (in_array($col, ['float', 'real', 'double'])) {
			return ['float', null];
		}
		return ['text', null];
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
 * Additional metadata columns in table descriptions.
 *
 * @return array
 */
	public function extraSchemaColumns() {
		return [];
	}

/**
 * Convert field description results into abstract schema fields.
 *
 * @return array An array of with the key/values of schema data.
 */
	public function convertFieldDescription($row, $fieldParams = []) {
		list($type, $length) = $this->convertColumn($row['type']);
		$schema = [];
		$schema[$row['name']] = [
			'type' => $type,
			'null' => !$row['notnull'],
			'default' => $row['dflt_value'] === null ? null : trim($row['dflt_value'], "'"),
			'length' => $length,
		];
		if ($row['pk'] == true) {
			$schema[$row['name']]['key'] = 'primary';
			$schema[$row['name']]['null'] = false;
		}
		return $schema;
	}

}
