<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         3.0.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
namespace Cake\Database\Dialect;

use Cake\Database\Expression\IdentifierExpression;
use Cake\Database\Expression\QueryExpression;
use Cake\Database\Expression\TupleComparison;
use Cake\Database\Query;

/**
 * Provides a translator method for tuple comparisons
 */
trait TupleComparisonTranslatorTrait {

/**
 *
 * Receives a TupleExpression and changes it so that it conforms to this
 * SQL dialect.
 *
 * @param \Cake\Database\Expression\TupleComparison $expression
 * @param \Cake\Database\Query $query
 * @return void
 */
	protected function _transformTupleComparison(TupleComparison $expression, $query) {
		$fields = $expression->getField();

		if (!is_array($fields)) {
			return;
		}

		$value = $expression->getValue();
		$op = $expression->type();
		$true = new QueryExpression('1');

		if ($value instanceof Query) {
			$selected = array_values($value->clause('select'));
			foreach ($fields as $i => $field) {
				$value->andWhere([$field . " $op" => new IdentifierExpression($selected[$i])]);
			}
			$value->select($true, true);
			$expression->field($true);
			$expression->type('=');
			return;
		}

		$surrogate = $query->connection()
			->newQuery()
			->select($true);

		foreach ($value as $tuple) {
			$surrogate->orWhere(function($exp) use ($fields, $tuple) {
				foreach ($tuple as $i => $value) {
					$exp->add([$fields[$i] => $value]);
				}
				return $exp;
			});
		}

		$expression->field($true);
		$expression->value($surrogate);
		$expression->type('=');
	}

}
