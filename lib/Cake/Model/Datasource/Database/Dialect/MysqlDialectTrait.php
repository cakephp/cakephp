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
 * @package       Cake.Model
 * @since         CakePHP(tm) v 3.0.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
namespace Cake\Model\Datasource\Database\Dialect;

use Cake\Error;
use Cake\Model\Datasource\Database\SqlDialectTrait;

/**
 * Contains functions that encapsulates the SQL dialect used by MySQL,
 * including query translators and schema introspection.
 */
trait MysqlDialectTrait {

	use SqlDialectTrait;

/**
 *  String used to start a database identifier quoting to make it safe
 *
 * @var string
 */
	public $startQuote = '`';

/**
 * String used to end a database identifier quoting to make it safe
 *
 * @var string
 */
	public $endQuote = '`';

/**
 * Get the SQL to list the tables in MySQL
 *
 * @param array $config The connection configuration to use for
 *    getting tables from.
 * @return array An array of (sql, params) to execute.
 */
	public function listTablesSql(array $config) {
		return ["SHOW TABLES FROM " . $this->quoteIdentifier($config['database']), []];
	}

/**
 * Get the SQL to describe a table in MySQL.
 *
 * @param string $table The table name to describe.
 * @return array An array of (sql, params) to execute.
 */
	public function describeTableSql($table) {
		return ["SHOW FULL COLUMNS FROM " . $this->quoteIdentifier($table), []];
	}

/**
 * Convert a platform specific index type to the abstract type
 *
 * @param string $key The key type to convert.
 * @return string The abstract key type (primary, unique, index)
 */
	public function convertIndex($key) {
		if ($key === 'PRI') {
			return 'primary';
		}
		if ($key === 'MUL') {
			return 'index';
		}
		if ($key === 'UNI') {
			return 'unique';
		}
	}

/**
 * Convert a MySQL column type into an abstract type.
 *
 * The returnned type will be a type that Cake\Model\Datasource\Database\Type can handle.
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
			return [$col, null];
		}
		if (($col === 'tinyint' && $length === 1) || $col === 'boolean') {
			return ['boolean', null];
		}
		if (strpos($col, 'bigint') !== false || $col === 'bigint') {
			return ['biginteger', $length];
		}
		if (strpos($col, 'int') !== false) {
			return ['integer', $length];
		}
		if (strpos($col, 'char') !== false || $col === 'tinytext') {
			return ['string', $length];
		}
		if (strpos($col, 'text') !== false) {
			return ['text', $length];
		}
		if (strpos($col, 'blob') !== false || $col === 'binary') {
			return ['binary', $length];
		}
		if (strpos($col, 'float') !== false || strpos($col, 'double') !== false) {
			return ['float', $length];
		}
		if (strpos($col, 'decimal') !== false) {
			return ['decimal', null];
		}
		return ['text', null];
	}

/**
 * Convert field description results into abstract schema fields.
 *
 * @return array An array of with the key/values of schema data.
 */
	public function convertFieldDescription($row, $fieldParams = []) {
		list($type, $length) = $this->convertColumn($row['Type']);
		$schema = [];
		$schema[$row['Field']] = [
			'type' => $type,
			'null' => $row['Null'] === 'YES' ? true : false,
			'default' => $row['Default'],
			'length' => $length,
		];
		if (!empty($row['Key'])) {
			$schema[$row['Field']]['key'] = $this->convertIndex($row['Key']);
		}
		foreach ($fieldParams as $key => $metadata) {
			if (!empty($row[$metadata['column']])) {
				$schema[$row['Field']][$key] = $row[$metadata['column']];
			}
		}
		return $schema;
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
