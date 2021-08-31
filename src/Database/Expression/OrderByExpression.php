<?php
declare(strict_types=1);

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
use RuntimeException;

/**
 * An expression object for ORDER BY clauses
 */
class OrderByExpression extends QueryExpression
{
    /**
     * Constructor
     *
     * @param \Cake\Database\ExpressionInterface|array|string $conditions The sort columns
     * @param \Cake\Database\TypeMap|array<string, string> $types The types for each column.
     * @param string $conjunction The glue used to join conditions together.
     */
    public function __construct($conditions = [], $types = [], $conjunction = '')
    {
        parent::__construct($conditions, $types, $conjunction);
    }

    /**
     * @inheritDoc
     */
    public function sql(ValueBinder $binder): string
    {
        $order = [];
        foreach ($this->_conditions as $k => $direction) {
            if ($direction instanceof ExpressionInterface) {
                $direction = $direction->sql($binder);
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
     * @param array $conditions list of order by expressions
     * @param array $types list of types associated on fields referenced in $conditions
     * @return void
     */
    protected function _addConditions(array $conditions, array $types): void
    {
        foreach ($conditions as $key => $val) {
            if (
                is_string($key) &&
                is_string($val) &&
                !in_array(strtoupper($val), ['ASC', 'DESC'], true)
            ) {
                throw new RuntimeException(
                    sprintf(
                        'Passing extra expressions by associative array (`\'%s\' => \'%s\'`) ' .
                        'is not allowed to avoid potential SQL injection. ' .
                        'Use QueryExpression or numeric array instead.',
                        $key,
                        $val
                    )
                );
            }
        }

        $this->_conditions = array_merge($this->_conditions, $conditions);
    }
}
