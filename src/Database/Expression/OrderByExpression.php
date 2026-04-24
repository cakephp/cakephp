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
use Cake\Database\TypeMap;
use Cake\Database\ValueBinder;
use InvalidArgumentException;

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
    public function __construct(
        ExpressionInterface|array|string $conditions = [],
        TypeMap|array $types = [],
        string $conjunction = '',
    ) {
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
     * Return the ordering as a list of `[field, direction]` pairs.
     *
     * `field` is returned as a string for simple column ordering, or as an
     * `ExpressionInterface` instance for complex expressions where the caller
     * must resolve the field identity. `direction` is the upper-cased direction,
     * either `'ASC'` or `'DESC'`.
     *
     * Ordering entries that cannot be decomposed into a field/direction pair
     * (for example a raw SQL fragment expression) are returned with a `null`
     * direction so the caller can decide how to handle them.
     *
     * @return array<int, array{0: \Cake\Database\ExpressionInterface|array|string, 1: 'ASC'|'DESC'|null}>
     */
    public function toList(): array
    {
        $pairs = [];
        foreach ($this->_conditions as $key => $value) {
            if (is_string($key) && (is_string($value) || $value instanceof ExpressionInterface)) {
                $direction = is_string($value) ? strtoupper($value) : null;
                if ($direction !== null && !in_array($direction, ['ASC', 'DESC'], true)) {
                    $direction = null;
                }
                $pairs[] = [$key, $direction];
                continue;
            }

            if ($value instanceof OrderClauseExpression) {
                $field = $value->getField();
                $direction = strtoupper($value->getDirection() ?: '');
                if (!in_array($direction, ['ASC', 'DESC'], true)) {
                    $direction = null;
                }
                $pairs[] = [$field, $direction];
                continue;
            }

            if ($value instanceof ExpressionInterface) {
                $pairs[] = [$value, null];
            }
        }

        return $pairs;
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
                throw new InvalidArgumentException(
                    sprintf(
                        "Passing extra expressions by associative array (`'%s' => '%s'`) " .
                        'is not allowed to avoid potential SQL injection. ' .
                        'Use QueryExpression or numeric array instead.',
                        $key,
                        $val,
                    ),
                );
            }
        }

        $this->_conditions = array_merge($this->_conditions, $conditions);
    }
}
