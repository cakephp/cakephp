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
use Closure;

/**
 * An expression object that represents an expression with only a single operand.
 */
class UnaryExpression implements ExpressionInterface
{
    /**
     * Indicates that the operation is in pre-order
     *
     * @var int
     */
    public const int PREFIX = 0;

    /**
     * Indicates that the operation is in post-order
     *
     * @var int
     */
    public const int POSTFIX = 1;

    /**
     * Constructor
     *
     * @param string $operator The operator this unary expression represents
     * @param mixed $value Holds the value which the unary expression operates
     * @param int $position either UnaryExpression::PREFIX or UnaryExpression::POSTFIX
     */
    public function __construct(
        protected string $operator,
        protected mixed $value,
        protected int $position = self::PREFIX,
    ) {
    }

    /**
     * @inheritDoc
     */
    public function sql(ValueBinder $binder): string
    {
        $operand = $this->value;
        if ($operand instanceof ExpressionInterface) {
            $operand = $operand->sql($binder);
        }

        if ($this->position === self::POSTFIX) {
            return '(' . $operand . ') ' . $this->operator;
        }

        return $this->operator . ' (' . $operand . ')';
    }

    /**
     * @inheritDoc
     */
    public function traverse(Closure $callback): static
    {
        if ($this->value instanceof ExpressionInterface) {
            $callback($this->value);
            $this->value->traverse($callback);
        }

        return $this;
    }

    /**
     * Perform a deep clone of the inner expression.
     */
    public function __clone()
    {
        if ($this->value instanceof ExpressionInterface) {
            $this->value = clone $this->value;
        }
    }
}
