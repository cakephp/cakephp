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
 * This class represents a SQL Case statement
 *
 * @internal
 */
class CaseExpression implements ExpressionInterface
{

    /**
     * A list of strings or other expression objects that represent the conditions of
     * the case statement. For example one key of the array might look like "sum > :value"
     *
     * @var array
     */
    protected $_conditions = [];

    /**
     * Values that are associated with the conditions in the $_conditions array.
     * Each value represents the 'true' value for the condition with the corresponding key.
     *
     * @var array
     */
    protected $_values = [];

    /**
     * The `ELSE` value for the case statement. If null then no `ELSE` will be included.
     *
     * @var string|\Cake\Database\ExpressionInterface|array|null
     */
    protected $_elseValue = null;

    /**
     * Constructs the case expression
     *
     * @param array|\Cake\Database\ExpressionInterface $conditions The conditions to test. Must be a ExpressionInterface
     * instance, or an array of ExpressionInterface instances.
     * @param array|\Cake\Database\ExpressionInterface $values associative array of values to be associated with the conditions
     * passed in $conditions. If there are more $values than $conditions, the last $value is used as the `ELSE` value
     * @param array $types associative array of types to be associated with the values
     * passed in $values
     */
    public function __construct($conditions = [], $values = [], $types = [])
    {
        if (!empty($conditions)) {
            $this->add($conditions, $values, $types);
        }

        if (is_array($conditions) && is_array($values) && count($values) > count($conditions)) {
            end($values);
            $key = key($values);
            $this->elseValue($values[$key], isset($types[$key]) ? $types[$key] : null);
        }
    }

    /**
     * Adds one or more conditions and their respective true values to the case object.
     * Conditions must be a one dimensional array or a QueryExpression.
     * The trueValues must be a similar structure, but may contain a string value.
     *
     * @param array|\Cake\Database\ExpressionInterface $conditions Must be a ExpressionInterface instance, or an array of ExpressionInterface instances.
     * @param array|\Cake\Database\ExpressionInterface $values associative array of values of each condition
     * @param array $types associative array of types to be associated with the values
     *
     * @return $this
     */
    public function add($conditions = [], $values = [], $types = [])
    {
        if (!is_array($conditions)) {
            $conditions = [$conditions];
        }
        if (!is_array($values)) {
            $values = [$values];
        }
        if (!is_array($types)) {
            $types = [$types];
        }

        $this->_addExpressions($conditions, $values, $types);

        return $this;
    }

    /**
     * Iterates over the passed in conditions and ensures that there is a matching true value for each.
     * If no matching true value, then it is defaulted to '1'.
     *
     * @param array|\Cake\Database\ExpressionInterface $conditions Must be a ExpressionInterface instance, or an array of ExpressionInterface instances.
     * @param array|\Cake\Database\ExpressionInterface $values associative array of values of each condition
     * @param array $types associative array of types to be associated with the values
     *
     * @return void
     */
    protected function _addExpressions($conditions, $values, $types)
    {
        $rawValues = array_values($values);
        $keyValues = array_keys($values);
        foreach ($conditions as $k => $c) {
            $numericKey = is_numeric($k);

            if ($numericKey && empty($c)) {
                continue;
            }
            if (!$c instanceof ExpressionInterface) {
                continue;
            }
            array_push($this->_conditions, $c);

            $value = isset($rawValues[$k]) ? $rawValues[$k] : 1;

            if ($value === 'literal') {
                $value = $keyValues[$k];
                array_push($this->_values, $value);
                continue;
            } elseif ($value === 'identifier') {
                $value = new IdentifierExpression($keyValues[$k]);
                array_push($this->_values, $value);
                continue;
            } elseif ($value instanceof ExpressionInterface) {
                array_push($this->_values, $value);
                continue;
            }

            $type = isset($types[$k]) ? $types[$k] : null;
            array_push($this->_values, ['value' => $value, 'type' => $type]);
        }
    }

    /**
     * Sets the default value
     *
     * @param \Cake\Database\ExpressionInterface|string|array|null $value Value to set
     * @param string $type Type of value
     *
     * @return void
     */
    public function elseValue($value = null, $type = null)
    {
        if (is_array($value)) {
            end($value);
            $value = key($value);
        } elseif ($value !== null && !$value instanceof ExpressionInterface) {
            $value = ['value' => $value, 'type' => $type];
        }

        $this->_elseValue = $value;
    }

    /**
     * Compiles the relevant parts into sql
     *
     * @param array|string|\Cake\Database\ExpressionInterface $part The part to compile
     * @param \Cake\Database\ValueBinder $generator Sql generator
     *
     * @return string
     */
    protected function _compile($part, ValueBinder $generator)
    {
        if ($part instanceof ExpressionInterface) {
            $part = $part->sql($generator);
        } elseif (is_array($part)) {
            $placeholder = $generator->placeholder('param');
            $generator->bind($placeholder, $part['value'], $part['type']);
            $part = $placeholder;
        }

        return $part;
    }

    /**
     * Converts the Node into a SQL string fragment.
     *
     * @param \Cake\Database\ValueBinder $generator Placeholder generator object
     *
     * @return string
     */
    public function sql(ValueBinder $generator)
    {
        $parts = [];
        $parts[] = 'CASE';
        foreach ($this->_conditions as $k => $part) {
            $value = $this->_values[$k];
            $parts[] = 'WHEN ' . $this->_compile($part, $generator) . ' THEN ' . $this->_compile($value, $generator);
        }
        if ($this->_elseValue !== null) {
            $parts[] = 'ELSE';
            $parts[] = $this->_compile($this->_elseValue, $generator);
        }
        $parts[] = 'END';

        return implode(' ', $parts);
    }

    /**
     * {@inheritDoc}
     *
     */
    public function traverse(callable $visitor)
    {
        foreach (['_conditions', '_values'] as $part) {
            foreach ($this->{$part} as $c) {
                if ($c instanceof ExpressionInterface) {
                    $visitor($c);
                    $c->traverse($visitor);
                }
            }
        }
        if ($this->_elseValue instanceof ExpressionInterface) {
            $visitor($this->_elseValue);
            $this->_elseValue->traverse($visitor);
        }
    }
}
