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
use Cake\Database\Type\ExpressionTypeCasterTrait;
use Cake\Database\ValueBinder;
use Closure;

/**
 * An expression object that represents a SQL BETWEEN snippet
 */
class BetweenExpression implements ExpressionInterface, FieldInterface
{
    use ExpressionTypeCasterTrait;
    use FieldTrait;

    /**
     * The first value in the expression
     *
     * @var mixed
     */
    protected $_from;

    /**
     * The second value in the expression
     *
     * @var mixed
     */
    protected $_to;

    /**
     * The data type for the from and to arguments
     *
     * @var mixed
     */
    protected $_type;

    /**
     * Constructor
     *
     * @param string|\Cake\Database\ExpressionInterface $field The field name to compare for values inbetween the range.
     * @param mixed $from The initial value of the range.
     * @param mixed $to The ending value in the comparison range.
     * @param string|null $type The data type name to bind the values with.
     */
    public function __construct($field, $from, $to, $type = null)
    {
        if ($type !== null) {
            $from = $this->_castToExpression($from, $type);
            $to = $this->_castToExpression($to, $type);
        }

        $this->_field = $field;
        $this->_from = $from;
        $this->_to = $to;
        $this->_type = $type;
    }

    /**
     * Converts the expression to its string representation
     *
     * @param \Cake\Database\ValueBinder $generator Placeholder generator object
     * @return string
     */
    public function sql(ValueBinder $generator): string
    {
        $parts = [
            'from' => $this->_from,
            'to' => $this->_to,
        ];

        /** @var string|\Cake\Database\ExpressionInterface $field */
        $field = $this->_field;
        if ($field instanceof ExpressionInterface) {
            $field = $field->sql($generator);
        }

        foreach ($parts as $name => $part) {
            if ($part instanceof ExpressionInterface) {
                $parts[$name] = $part->sql($generator);
                continue;
            }
            $parts[$name] = $this->_bindValue($part, $generator, $this->_type);
        }

        return sprintf('%s BETWEEN %s AND %s', $field, $parts['from'], $parts['to']);
    }

    /**
     * @inheritDoc
     */
    public function traverse(Closure $visitor)
    {
        foreach ([$this->_field, $this->_from, $this->_to] as $part) {
            if ($part instanceof ExpressionInterface) {
                $visitor($part);
            }
        }

        return $this;
    }

    /**
     * Registers a value in the placeholder generator and returns the generated placeholder
     *
     * @param mixed $value The value to bind
     * @param \Cake\Database\ValueBinder $generator The value binder to use
     * @param string $type The type of $value
     * @return string generated placeholder
     */
    protected function _bindValue($value, $generator, $type): string
    {
        $placeholder = $generator->placeholder('c');
        $generator->bind($placeholder, $value, $type);

        return $placeholder;
    }

    /**
     * Do a deep clone of this expression.
     *
     * @return void
     */
    public function __clone()
    {
        foreach (['_field', '_from', '_to'] as $part) {
            if ($this->{$part} instanceof ExpressionInterface) {
                $this->{$part} = clone $this->{$part};
            }
        }
    }
}
