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
namespace Cake\Database\Expression;

use Cake\Database\ExpressionInterface;
use Cake\Database\ValueBinder;

/**
 * An expression object that represents an expression with only a single operand.
 *
 * @internal
 */
class UnaryExpression implements ExpressionInterface
{

    /**
     * Indicates that the operation is in pre-order
     *
     */
    const PREFIX = 0;

    /**
     * Indicates that the operation is in post-order
     *
     */
    const POSTFIX = 1;

    /**
     * The operator this unary expression represents
     *
     * @var string
     */
    protected $_operator;

    /**
     * Holds the value which the unary expression operates
     *
     * @var mixed
     */
    protected $_value;

    /**
     * Where to place the operator
     *
     * @var int
     */
    protected $_mode;

    /**
     * Constructor
     *
     * @param string $operator The operator to used for the expression
     * @param mixed $value the value to use as the operand for the expression
     * @param int $mode either UnaryExpression::PREFIX or UnaryExpression::POSTFIX
     */
    public function __construct($operator, $value, $mode = self::PREFIX)
    {
        $this->_operator = $operator;
        $this->_value = $value;
        $this->_mode = $mode;
    }

    /**
     * Converts the expression to its string representation
     *
     * @param \Cake\Database\ValueBinder $generator Placeholder generator object
     * @return string
     */
    public function sql(ValueBinder $generator)
    {
        $operand = $this->_value;
        if ($operand instanceof ExpressionInterface) {
            $operand = $operand->sql($generator);
        }

        if ($this->_mode === self::POSTFIX) {
            return '(' . $operand . ') ' . $this->_operator;
        }

        return $this->_operator . ' (' . $operand . ')';
    }

    /**
     * {@inheritDoc}
     *
     */
    public function traverse(callable $callable)
    {
        if ($this->_value instanceof ExpressionInterface) {
            $callable($this->_value);
        }
    }
}
