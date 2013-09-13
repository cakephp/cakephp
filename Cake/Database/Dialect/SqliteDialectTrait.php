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

trait SqliteDialectTrait {

	use SqlDialectTrait;

/**
 *  String used to start a database identifier quoting to make it safe
 *
 * @var string
 */
	protected $_startQuote = '"';

/**
 * String used to end a database identifier quoting to make it safe
 *
 * @var string
 */
	protected $_endQuote = '"';

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
						return new FunctionExpression('JULIANDAY', [$p['value']], [$p['type']]);
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
 * Transforms an insert query that is meant to insert multiple rows at a time,
 * otherwise it leaves the query untouched.
 *
 * The way SQLite works with multi insert is by having multiple select statements
 * joined with UNION.
 *
 * @return Query
 */
	protected function _insertQueryTranslator($query) {
		$v = $query->clause('values');
		if (count($v->values()) === 1 || $v->query()) {
			return $query;
		}

		$newQuery = $query->connection()->newQuery();
		$cols = $v->columns();
		foreach ($v->values() as $k => $val) {
			$fillLength = count($cols) - count($val);
			if ($fillLength > 0) {
				$val = array_merge($val, array_fill(0, $fillLength, null));
			}
			$val = array_map(function($val) {
				return $val instanceof ExpressionInterface ? $val : '?';
			}, $val);

			$select = array_combine($cols, $val);
			if ($k === 0) {
				$newQuery->select($select);
				continue;
			}

			$q = $newQuery->connection()->newQuery();
			$newQuery->union($q->select($select), true);
		}

		$v->query($newQuery);
		return $query;
	}

/**
 * Get the schema dialect.
 *
 * Used by Cake\Schema package to reflect schema and
 * generate schema.
 *
 * @return Cake\Database\Schema\SqliteSchema
 */
	public function schemaDialect() {
		return new \Cake\Database\Schema\SqliteSchema($this);
	}

}
