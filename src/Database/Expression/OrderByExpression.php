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
namespace Cake\Database\Expression;

use Cake\Database\ExpressionInterface;
use Cake\Database\ValueBinder;

/**
 * An expression object for ORDER BY clauses
 */
class OrderByExpression extends QueryExpression
{

    /**
     * Constructor
     *
     * @param string|array|\Cake\Database\ExpressionInterface $conditions The sort columns
     * @param array|\Cake\Database\TypeMap $types The types for each column.
     * @param string $conjunction The glue used to join conditions together.
     */
    public function __construct($conditions = [], $types = [], $conjunction = '')
    {
        parent::__construct($conditions, $types, $conjunction);
    }

    /**
     * Convert the expression into a SQL fragment.
     *
     * @param \Cake\Database\ValueBinder $generator Placeholder generator object
     * @return string
     */
    public function sql(ValueBinder $generator)
    {
        $order = [];
        foreach ($this->_conditions as $k => $direction) {
            if ($direction instanceof ExpressionInterface) {
                $direction = $direction->sql($generator);
            }
            $order[] = is_numeric($k) ? $direction : sprintf('%s %s', $k, $direction);
        }

        return sprintf('ORDER BY %s', implode(', ', $order));
    }

    /**
     * Auxiliary function used for decomposing a nested array of conditions and
     * building a tree structure inside this object to represent the full SQL expression.
     *
     * New order by expressions are merged to existing ones
     *
     * @param array $orders list of order by expressions
     * @param array $types list of types associated on fields referenced in $conditions
     * @return void
     */
    protected function _addConditions(array $orders, array $types)
    {
        $this->_conditions = array_merge($this->_conditions, $orders);
    }
}
