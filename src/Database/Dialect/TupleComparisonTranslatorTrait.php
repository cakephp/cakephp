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
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
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
 * It transforms expressions looking like '(a, b) IN ((c, d), (e, f)' into an
 * equivalent expression of the form '((a = c) AND (b = d)) OR ((a = e) AND (b = f))'.
 *
 * It can also transform transform expressions where the right hand side is a query
 * selecting the same amount of columns as the elements in the left hand side of
 * the expression:
 *
 * (a, b) IN (SELECT c, d FROM a_table) is transformed into
 *
 * 1 = (SELECT 1 FROM a_table WHERE (a = c) AND (b = d))
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
