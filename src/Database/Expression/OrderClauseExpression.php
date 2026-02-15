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
use Cake\Database\Query;
use Cake\Database\ValueBinder;
use Closure;

/**
 * An expression object for complex ORDER BY clauses
 */
class OrderClauseExpression implements ExpressionInterface, FieldInterface
{
    use FieldTrait;

    /**
     * The direction of sorting.
     *
     * @var string
     */
    protected string $direction;

    /**
     * Constructor
     *
     * @param \Cake\Database\ExpressionInterface|string $field The field to order on.
     * @param string $direction The direction to sort on.
     */
    public function __construct(ExpressionInterface|string $field, string $direction)
    {
        $this->field = $field;
        $this->direction = strtolower($direction) === 'asc' ? 'ASC' : 'DESC';
    }

    /**
     * @inheritDoc
     */
    public function sql(ValueBinder $binder): string
    {
        $field = $this->field;
        if ($field instanceof Query) {
            $field = sprintf('(%s)', $field->sql($binder));
        } elseif ($field instanceof ExpressionInterface) {
            $field = $field->sql($binder);
        }
        assert(is_string($field));

        return sprintf('%s %s', $field, $this->direction);
    }

    /**
     * @inheritDoc
     */
    public function traverse(Closure $callback): static
    {
        if ($this->field instanceof ExpressionInterface) {
            $callback($this->field);
            $this->field->traverse($callback);
        }

        return $this;
    }

    /**
     * Create a deep clone of the order clause.
     */
    public function __clone()
    {
        if ($this->field instanceof ExpressionInterface) {
            $this->field = clone $this->field;
        }
    }
}
