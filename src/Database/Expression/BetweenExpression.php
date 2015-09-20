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
 * An expression object that represents a SQL BETWEEN snippet
 *
 * @internal
 */
class BetweenExpression implements ExpressionInterface, FieldInterface
{

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
     * @param mixed $field The field name to compare for values in between the range.
     * @param mixed $from The initial value of the range.
     * @param mixed $to The ending value in the comparison range.
     * @param string $type The data type name to bind the values with.
     */
    public function __construct($field, $from, $to, $type = null)
    {
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
    public function sql(ValueBinder $generator)
    {
        $parts = [
            'from' => $this->_from,
            'to' => $this->_to
        ];

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
     * {@inheritDoc}
     *
     */
    public function traverse(callable $callable)
    {
        foreach ([$this->_field, $this->_from, $this->_to] as $part) {
            if ($part instanceof ExpressionInterface) {
                $callable($part);
            }
        }
    }

    /**
     * Registers a value in the placeholder generator and returns the generated placeholder
     *
     * @param mixed $value The value to bind
     * @param \Cake\Database\ValueBinder $generator The value binder to use
     * @param string $type The type of $value
     * @return string generated placeholder
     */
    protected function _bindValue($value, $generator, $type)
    {
        $placeholder = $generator->placeholder('c');
        $generator->bind($placeholder, $value, $type);
        return $placeholder;
    }
}
