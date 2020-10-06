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
    protected $start;

    /**
     * The second value in the expression
     *
     * @var mixed
     */
    protected $end;

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
     * @param mixed $start The initial value of the range.
     * @param mixed $end The ending value in the comparison range.
     * @param string|null $type The data type name to bind the values with.
     */
    public function __construct($field, $start, $end, $type = null)
    {
        if ($type !== null) {
            $start = $this->_castToExpression($start, $type);
            $end = $this->_castToExpression($end, $type);
        }

        $this->_field = $field;
        $this->start = $start;
        $this->end = $end;
        $this->_type = $type;
    }

    /**
     * @inheritDoc
     */
    public function sql(ValueBinder $binder): string
    {
        $parts = [
            'start' => $this->start,
            'end' => $this->end,
        ];

        /** @var string|\Cake\Database\ExpressionInterface $field */
        $field = $this->_field;
        if ($field instanceof ExpressionInterface) {
            $field = $field->sql($binder);
        }

        foreach ($parts as $name => $part) {
            if ($part instanceof ExpressionInterface) {
                $parts[$name] = $part->sql($binder);
                continue;
            }
            $parts[$name] = $this->_bindValue($part, $binder, $this->_type);
        }

        return sprintf('%s BETWEEN %s AND %s', $field, $parts['start'], $parts['end']);
    }

    /**
     * @inheritDoc
     */
    public function traverse(Closure $callback)
    {
        foreach ([$this->_field, $this->start, $this->end] as $part) {
            if ($part instanceof ExpressionInterface) {
                $callback($part);
            }
        }

        return $this;
    }

    /**
     * Registers a value in the placeholder generator and returns the generated placeholder
     *
     * @param mixed $value The value to bind
     * @param \Cake\Database\ValueBinder $binder The value binder to use
     * @param string $type The type of $value
     * @return string generated placeholder
     */
    protected function _bindValue($value, $binder, $type): string
    {
        $placeholder = $binder->placeholder('c');
        $binder->bind($placeholder, $value, $type);

        return $placeholder;
    }

    /**
     * Do a deep clone of this expression.
     *
     * @return void
     */
    public function __clone()
    {
        foreach (['_field', 'start', 'ennd'] as $part) {
            if ($this->{$part} instanceof ExpressionInterface) {
                $this->{$part} = clone $this->{$part};
            }
        }
    }
}
