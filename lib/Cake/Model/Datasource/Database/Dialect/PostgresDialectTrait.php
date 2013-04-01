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

use Cake\Model\Datasource\Database\Expression\UnaryExpression;
use Cake\Model\Datasource\Database\Expression\FunctionExpression;
use Cake\Model\Datasource\Database\Query;
use Cake\Model\Datasource\Database\SqlDialectTrait;

/**
 * Contains functions that encapsulates the SQL dialect used by Postgres,
 * including query translators and schema introspection.
 */
trait PostgresDialectTrait {

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
 * Returns a query that has been transformed to the specific SQL dialect
 * by changing or re-arranging SQL clauses as required.
 *
 * @param Cake\Model\Datasource\Database\Query $query
 * @return Cake\Model\Datasource\Database\Query
 */
	protected function _selectQueryTranslator($query) {
		$limit = $query->clause('limit');
		$offset = $query->clause('offset');
		$order = $query->clause('order');

		if ($limit || $offset) {
			$query = clone $query;
			$field = '_cake_paging_._cake_page_rownum_';
			$outer = (new Query($query->connection()))
				->select('*')
				->from(['_cake_paging_' => $query]);

			if ($offset) {
				$outer->where(["$field >" => $offset]);
			}
			if ($limit) {
				$outer->where(["$field <=" => (int)$offset + (int)$limit]);
			}

			$query
				->select(['_cake_page_rownum_' => new UnaryExpression($order, [], 'ROW_NUMBER() OVER')])
				->limit(null)
				->offset(null)
				->order([], true);
			return $outer->decorateResults($this->_rowNumberRemover());
		}

		return $query;
	}

/**
 * Returns a function that will be used as a callback for a results decorator.
 * this function is responsible for deleting the artificial column in results
 * used for paginating the query.
 *
 * @return \Closure
 */
	protected function _rowNumberRemover() {
		return function($row) {
			if (isset($row['_cake_page_rownum_'])) {
				unset($row['_cake_page_rownum_']);
			} else {
				array_pop($row);
			}
			return $row;
		};
	}


/**
 * Returns an dictionary of expressions to be transformed when compiling a Query
 * to SQL. Array keys are method names to be called in this class
 *
 * @return array
 */
	protected function _expressionTranslators() {
		$namespace = 'Cake\Model\Datasource\Database\Expression';
		return [
			$namespace . '\FunctionExpression' => '_transformFunctionExpression'
		];
	}

/**
 * Receives a FunctionExpression and changes it so that it conforms to this
 * SQL dialect.
 *
 * @param Cake\Model\Datasource\Database\Expression\FunctionExpression
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
					->name('')
					->type('-')
					->iterateParts(function($p) {
						return new FunctionExpression('DATE', [$p => 'literal']);
					});
				break;
			case 'CURRENT_DATE':
				$time = new FunctionExpression('LOCALTIMESTAMP', [' 0 ' => 'literal']);
				$expression->name('CAST')->type(' AS ')->add([$time, 'date' => 'literal']);
				break;
			case 'CURRENT_TIME':
				$time = new FunctionExpression('LOCALTIMESTAMP', [' 0 ' => 'literal']);
				$expression->name('CAST')->type(' AS ')->add([$time, 'time' => 'literal']);
				break;
			case 'NOW':
				$expression->name('LOCALTIMESTAMP')->add([' 0 ' => 'literal']);
				break;
		}
	}

/**
 * Get the SQL to list the tables
 *
 * @param array $config The connection configuration to use for
 *    getting tables from.
 * @return array An array of (sql, params) to execute.
 */
	public function listTablesSql() {
		$sql = "SELECT table_name as name FROM INFORMATION_SCHEMA.tables WHERE table_schema = ? ORDER BY name";
		$schema = empty($config['schema']) ? 'public' : $config['schema'];
		return [$sql, [$schema]];
	}

/**
 * Get the SQL to describe a table in Sqlite.
 *
 * @param string $table The table name to describe
 * @return array An array of (sql, params) to execute.
 */
	public function describeTableSql($table) {
	}


/**
 * Convert a column definition to the abstract types.
 *
 * The returned type will be a type that
 * Cake\Model\Datasource\Database\Type can handle.
 *
 * @param string $column The column type + length
 * @return array List of (type, length)
 */
	public function convertColumn($column) {
	}

/**
 * Additional metadata columns in table descriptions.
 *
 * @return array
 */
	public function extraSchemaColumns() {
	}

/**
 * Convert field description results into abstract schema fields.
 *
 * @return array An array of with the key/values of schema data.
 */
	public function convertFieldDescription($row, $fieldParams = []) {
	}

}
