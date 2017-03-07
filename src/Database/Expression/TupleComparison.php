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
 * This expression represents SQL fragments that are used for comparing one tuple
 * to another, one tuple to a set of other tuples or one tuple to an expression
 */
class TupleComparison extends Comparison
{

    /**
     * Constructor
     *
     * @param string|array|\Cake\Database\ExpressionInterface $fields the fields to use to form a tuple
     * @param array|\Cake\Database\ExpressionInterface $values the values to use to form a tuple
     * @param array $types the types names to use for casting each of the values, only
     * one type per position in the value array in needed
     * @param string $conjunction the operator used for comparing field and value
     */
    public function __construct($fields, $values, $types = [], $conjunction = '=')
    {
        parent::__construct($fields, $values, $types, $conjunction);
        $this->_type = (array)$types;
    }

    /**
     * Convert the expression into a SQL fragment.
     *
     * @param \Cake\Database\ValueBinder $generator Placeholder generator object
     * @return string
     */
    public function sql(ValueBinder $generator)
    {
        $template = '(%s) %s (%s)';
        $fields = [];
        $originalFields = $this->getField();

        if (!is_array($originalFields)) {
            $originalFields = [$originalFields];
        }

        foreach ($originalFields as $field) {
            $fields[] = $field instanceof ExpressionInterface ? $field->sql($generator) : $field;
        }

        $values = $this->_stringifyValues($generator);

        $field = implode(', ', $fields);

        return sprintf($template, $field, $this->_operator, $values);
    }

    /**
     * Returns a string with the values as placeholders in a string to be used
     * for the SQL version of this expression
     *
     * @param \Cake\Database\ValueBinder $generator The value binder to convert expressions with.
     * @return string
     */
    protected function _stringifyValues($generator)
    {
        $values = [];
        $parts = $this->getValue();

        if ($parts instanceof ExpressionInterface) {
            return $parts->sql($generator);
        }

        foreach ($parts as $i => $value) {
            if ($value instanceof ExpressionInterface) {
                $values[] = $value->sql($generator);
                continue;
            }

            $type = $this->_type;
            $multiType = is_array($type);
            $isMulti = $this->isMulti();
            $type = $multiType ? $type : str_replace('[]', '', $type);
            $type = $type ?: null;

            if ($isMulti) {
                $bound = [];
                foreach ($value as $k => $val) {
                    $valType = $multiType ? $type[$k] : $type;
                    $bound[] = $this->_bindValue($generator, $val, $valType);
                }

                $values[] = sprintf('(%s)', implode(',', $bound));
                continue;
            }

            $valType = $multiType && isset($type[$i]) ? $type[$i] : $type;
            $values[] = $this->_bindValue($generator, $value, $valType);
        }

        return implode(', ', $values);
    }

    /**
     * Registers a value in the placeholder generator and returns the generated
     * placeholder
     *
     * @param \Cake\Database\ValueBinder $generator The value binder
     * @param mixed $value The value to bind
     * @param string $type The type to use
     * @return string generated placeholder
     */
    protected function _bindValue($generator, $value, $type)
    {
        $placeholder = $generator->placeholder('tuple');
        $generator->bind($placeholder, $value, $type);

        return $placeholder;
    }

    /**
     * Traverses the tree of expressions stored in this object, visiting first
     * expressions in the left hand side and then the rest.
     *
     * Callback function receives as its only argument an instance of an ExpressionInterface
     *
     * @param callable $callable The callable to apply to sub-expressions
     * @return void
     */
    public function traverse(callable $callable)
    {
        foreach ($this->getField() as $field) {
            $this->_traverseValue($field, $callable);
        }

        $value = $this->getValue();
        if ($value instanceof ExpressionInterface) {
            $callable($value);
            $value->traverse($callable);

            return;
        }

        foreach ($value as $i => $val) {
            if ($this->isMulti()) {
                foreach ($val as $v) {
                    $this->_traverseValue($v, $callable);
                }
            } else {
                $this->_traverseValue($val, $callable);
            }
        }
    }

    /**
     * Conditionally executes the callback for the passed value if
     * it is an ExpressionInterface
     *
     * @param mixed $value The value to traverse
     * @param callable $callable The callable to use when traversing
     * @return void
     */
    protected function _traverseValue($value, $callable)
    {
        if ($value instanceof ExpressionInterface) {
            $callable($value);
            $value->traverse($callable);
        }
    }

    /**
     * Determines if each of the values in this expressions is a tuple in
     * itself
     *
     * @return bool
     */
    public function isMulti()
    {
        return in_array(strtolower($this->_operator), ['in', 'not in']);
    }
}
