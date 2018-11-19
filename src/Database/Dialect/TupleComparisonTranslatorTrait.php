<?php
/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         3.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Database\Dialect;

use Cake\Database\Expression\IdentifierExpression;
use Cake\Database\Expression\QueryExpression;
use Cake\Database\Expression\TupleComparison;
use Cake\Database\Query;

/**
 * Provides a translator method for tuple comparisons
 *
 * @internal
 */
trait TupleComparisonTranslatorTrait
{

    /**
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
     * @param \Cake\Database\Expression\TupleComparison $expression The expression to transform
     * @param \Cake\Database\Query $query The query to update.
     * @return void
     */
    protected function _transformTupleComparison(TupleComparison $expression, $query)
    {
        $fields = $expression->getField();

        if (!is_array($fields)) {
            return;
        }

        $value = $expression->getValue();
        $op = $expression->getOperator();
        $true = new QueryExpression('1');

        if ($value instanceof Query) {
            $selected = array_values($value->clause('select'));
            foreach ($fields as $i => $field) {
                $value->andWhere([$field . " $op" => new IdentifierExpression($selected[$i])]);
            }
            $value->select($true, true);
            $expression->setField($true);
            $expression->setOperator('=');

            return;
        }

        $surrogate = $query->getConnection()
            ->newQuery()
            ->select($true);

        if (!is_array(current($value))) {
            $value = [$value];
        }

        $conditions = ['OR' => []];
        foreach ($value as $tuple) {
            $item = [];
            foreach (array_values($tuple) as $i => $value) {
                $item[] = [$fields[$i] => $value];
            }
            $conditions['OR'][] = $item;
        }
        $surrogate->where($conditions);

        $expression->setField($true);
        $expression->setValue($surrogate);
        $expression->setOperator('=');
    }
}
