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
namespace Cake\Database\Dialect;

use Cake\Database\ExpressionInterface;
use Cake\Database\Expression\FunctionExpression;
use Cake\Database\Query;
use Cake\Database\SqlDialectTrait;
use Cake\Error;

trait SqliteDialectTrait {

	use SqlDialectTrait;

/**
 *  String used to start a database identifier quoting to make it safe
 *
 * @var string
 **/
	public $startQuote = '"';

/**
 * String used to end a database identifier quoting to make it safe
 *
 * @var string
 **/
	public $endQuote = '"';

/**
 * Returns an dictionary of expressions to be transformed when compiling a Query
 * to SQL. Array keys are method names to be called in this class
 *
 * @return array
 */
	protected function _expressionTranslators() {
		$namespace = 'Cake\Database\Expression';
		return [
			$namespace . '\FunctionExpression' => '_transformFunctionExpression'
		];
	}

/**
 * Receives a FunctionExpression and changes it so that it conforms to this
 * SQL dialect.
 *
 * @param Cake\Database\Expression\FunctionExpression
 * @return void
 */
	protected function _transformFunctionExpression(FunctionExpression $expression) {
		switch ($expression->name()) {
			case 'CONCAT':
				// CONCAT function is expressed as exp1 || exp2
				$expression->name('')->type(' ||');
				break;
			case 'DATEDIFF':
				$expression
					->name('ROUND')
					->type('-')
					->iterateParts(function($p) {
						return new FunctionExpression('JULIANDAY', [$p => 'literal']);
					});
				break;
			case 'NOW':
				$expression->name('DATETIME')->add(["'now'" => 'literal']);
				break;
			case 'CURRENT_DATE':
				$expression->name('DATE')->add(["'now'" => 'literal']);
				break;
			case 'CURRENT_TIME':
				$expression->name('TIME')->add(["'now'" => 'literal']);
				break;
		}
	}

/**
 * Transforms an insert query that is meant to insert multiple tows at a time,
 * otherwise it leaves the query untouched.
 *
 * The way SQLite works with multi insert is by having multiple select statements
 * joined with UNION.
 *
 * @return Query
 */
	protected function _insertQueryTranslator($query) {
		$v = $query->clause('values');
		if (count($v->values()) === 1) {
			return $query;
		}

		$cols = $v->columns();
		$newQuery = $query->connection()->newQuery();
		$values = [];
		foreach ($v->values() as $k => $val) {
			$values[] = $val;
			$val = array_merge($val, array_fill(0, count($cols) - count($val), null));
			$val = array_map(function($val) {
				return $val instanceof ExpressionInterface ? $val : '?';
			}, $val);

			if ($k === 0) {
				array_unshift($values, $newQuery->select(array_combine($cols, $val)));
				continue;
			}

			$q = $newQuery->connection()->newQuery();
			$newQuery->union($q->select(array_combine($cols, $val)), true);
		}

		$v = clone $v;
		$v->values($values);
		return $query->values($v);
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
		if ($col === 'timestamp') {
			return ['datetime', null];
		}
		if (in_array($col, ['blob', 'clob'])) {
			return ['binary', null];
		}
		if (in_array($col, ['date', 'time', 'datetime'])) {
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
 * Additional metadata columns in table descriptions.
 *
 * @return array
 */
	public function extraSchemaColumns() {
		return [];
	}

/**
 * Get the SQL to describe a table in Sqlite.
 *
 * @param string $table The table name to describe
 * @return array An array of (sql, params) to execute.
 */
	public function describeTableSql($table) {
		return ["PRAGMA table_info(" . $this->quoteIdentifier($table) . ")", []];
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
