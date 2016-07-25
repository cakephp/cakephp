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
 * An expression object that represents a SQL LIKE %...% snippet
 *
 * @internal
 */
class SearchExpression implements ExpressionInterface, FieldInterface
{

    use FieldTrait;

    /**
     * The second value in the expression
     *
     * @var mixed
     */
    protected $_value;

    /**
     * The data type for the from and to arguments
     *
     * @var mixed
     */
    protected $_type;

    /**
     * Constructor
     *
     * @param mixed $field The field name to search.
     * @param mixed $value The value to search for.
     * @param string $type The data type name to bind the values with.
     */
    public function __construct($field, $value, $type = null)
    {
        $this->_field = $field;
        $this->_value = $value;
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
        $value = $this->_value;

        $field = $this->_field;
        if ($field instanceof ExpressionInterface) {
            $field = $field->sql($generator);
        }

        if ($value instanceof ExpressionInterface) {
            $value = $value->sql($generator);
        }

        $value = $this->_bindValue($value, $generator, $this->_type);

        return sprintf('%s LIKE %%%s%%', $field, $value);
    }

    /**
     * {@inheritDoc}
     *
     */
    public function traverse(callable $callable)
    {
        foreach ([$this->_field, $this->_value] as $part) {
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

    /**
     * Do a deep clone of this expression.
     *
     * @return void
     */
    public function __clone()
    {
        foreach (['_field', '_value'] as $part) {
            if ($this->{$part} instanceof ExpressionInterface) {
                $this->{$part} = clone $this->{$part};
            }
        }
    }
}
