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

use Cake\Database\Exception as DatabaseException;
use Cake\Database\ExpressionInterface;
use Cake\Database\ValueBinder;

/**
 * A Comparison is a type of query expression that represents an operation
 * involving a field an operator and a value. In its most common form the
 * string representation of a comparison is `field = value`
 *
 * @internal
 */
class Comparison implements ExpressionInterface, FieldInterface
{

    use FieldTrait;

    /**
     * The value to be used in the right hand side of the operation
     *
     * @var mixed
     */
    protected $_value;

    /**
     * The type to be used for casting the value to a database representation
     *
     * @var string
     */
    protected $_type;

    /**
     * The operator used for comparing field and value
     *
     * @var string
     */
    protected $_operator;

    /**
     * Constructor
     *
     * @param string $field the field name to compare to a value
     * @param mixed $value The value to be used in comparison
     * @param string $type the type name used to cast the value
     * @param string $operator the operator used for comparing field and value
     */
    public function __construct($field, $value, $type, $operator)
    {
        $this->setField($field);
        $this->setValue($value);
        $this->_operator = $operator;

        if (is_string($type)) {
            $this->_type = $type;
        }
    }

    /**
     * Sets the value
     *
     * @param mixed $value The value to compare
     * @return void
     */
    public function setValue($value)
    {
        $this->_value = $value;
    }

    /**
     * Returns the value used for comparison
     *
     * @return mixed
     */
    public function getValue()
    {
        return $this->_value;
    }

    /**
     * Sets the operator to use for the comparison
     *
     * @param string $operator The operator to be used for the comparison.
     * @return void
     */
    public function setOperator($operator)
    {
        $this->_operator = $operator;
    }

    /**
     * Returns the operator used for comparison
     *
     * @return string
     */
    public function getOperator()
    {
        return $this->_operator;
    }

    /**
     * Convert the expression into a SQL fragment.
     *
     * @param \Cake\Database\ValueBinder $generator Placeholder generator object
     * @return string
     */
    public function sql(ValueBinder $generator)
    {
        $field = $this->_field;

        if ($field instanceof ExpressionInterface) {
            $field = $field->sql($generator);
        }

        if ($this->_value instanceof ExpressionInterface) {
            $template = '%s %s (%s)';
            $value = $this->_value->sql($generator);
        } else {
            list($template, $value) = $this->_stringExpression($generator);
        }

        return sprintf($template, $field, $this->_operator, $value);
    }

    /**
     * {@inheritDoc}
     *
     */
    public function traverse(callable $callable)
    {
        if ($this->_field instanceof ExpressionInterface) {
            $callable($this->_field);
            $this->_field->traverse($callable);
        }

        if ($this->_value instanceof ExpressionInterface) {
            $callable($this->_value);
            $this->_value->traverse($callable);
        }
    }

    /**
     * Create a deep clone.
     *
     * Clones the field and value if they are expression objects.
     *
     * @return void
     */
    public function __clone()
    {
        foreach (['_value', '_field'] as $prop) {
            if ($prop instanceof ExpressionInterface) {
                $this->{$prop} = clone $this->{$prop};
            }
        }
    }

    /**
     * Returns a template and a placeholder for the value after registering it
     * with the placeholder $generator
     *
     * @param \Cake\Database\ValueBinder $generator The value binder to use.
     * @return array First position containing the template and the second a placeholder
     */
    protected function _stringExpression($generator)
    {
        $template = '%s ';

        if ($this->_field instanceof ExpressionInterface) {
            $template = '(%s) ';
        }

        if (strpos($this->_type, '[]') !== false) {
            $template .= '%s (%s)';
            $type = str_replace('[]', '', $this->_type);
            $value = $this->_flattenValue($this->_value, $generator, $type);

            // To avoid SQL errors when comparing a field to a list of empty values,
            // better just throw an exception here
            if ($value === '') {
                $field = $this->_field instanceof ExpressionInterface ? $this->_field->sql($generator) : $this->_field;
                throw new DatabaseException(
                    "Impossible to generate condition with empty list of values for field ($field)"
                );
            }
        } else {
            $template .= '%s %s';
            $value = $this->_bindValue($this->_value, $generator, $this->_type);
        }

        return [$template, $value];
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
     * Converts a traversable value into a set of placeholders generated by
     * $generator and separated by `,`
     *
     * @param array|\Traversable $value the value to flatten
     * @param \Cake\Database\ValueBinder $generator The value binder to use
     * @param string|array $type the type to cast values to
     * @return string
     */
    protected function _flattenValue($value, $generator, $type = null)
    {
        $parts = [];
        foreach ($value as $k => $v) {
            $parts[] = $this->_bindValue($v, $generator, $type);
        }

        return implode(',', $parts);
    }
}
