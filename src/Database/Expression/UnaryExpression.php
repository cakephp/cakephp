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
    public const PREFIX = 0;

    /**
     * Indicates that the operation is in post-order
     *
     * @var int
     */
    public const POSTFIX = 1;

    /**
     * The operator this unary expression represents
     *
     * @var string
     */
    protected string $_operator;

    /**
     * Holds the value which the unary expression operates
     *
     * @var mixed
     */
    protected mixed $_value;

    /**
     * Where to place the operator
     *
     * @var int
     */
    protected int $position;

    /**
     * Constructor
     *
     * @param string $operator The operator to used for the expression
     * @param mixed $value the value to use as the operand for the expression
     * @param int $position either UnaryExpression::PREFIX or UnaryExpression::POSTFIX
     */
    public function __construct(string $operator, mixed $value, int $position = self::PREFIX)
    {
        $this->_operator = $operator;
        $this->_value = $value;
        $this->position = $position;
    }

    /**
     * @inheritDoc
     */
    public function sql(ValueBinder $binder): string
    {
        $operand = $this->_value;
        if ($operand instanceof ExpressionInterface) {
            $operand = $operand->sql($binder);
        }

        if ($this->position === self::POSTFIX) {
            return '(' . $operand . ') ' . $this->_operator;
        }

        return $this->_operator . ' (' . $operand . ')';
    }

    /**
     * @inheritDoc
     */
    public function traverse(Closure $callback)
    {
        if ($this->_value instanceof ExpressionInterface) {
            $callback($this->_value);
            $this->_value->traverse($callback);
        }

        return $this;
    }

    /**
     * Perform a deep clone of the inner expression.
     */
    public function __clone()
    {
        if ($this->_value instanceof ExpressionInterface) {
            $this->_value = clone $this->_value;
        }
    }
}
