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

use Cake\Model\Datasource\Database\Expression;
use Cake\Model\Datasource\Database\Expression\FunctionExpression;
use Cake\Model\Datasource\Database\Query;

trait SqliteDialectTrait {

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
				return $val instanceof Expression ? $val : '?';
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

}
