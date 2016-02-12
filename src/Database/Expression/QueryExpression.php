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

use BadMethodCallException;
use Cake\Database\ExpressionInterface;
use Cake\Database\Query;
use Cake\Database\TypeMapTrait;
use Cake\Database\ValueBinder;
use Countable;

/**
 * Represents a SQL Query expression. Internally it stores a tree of
 * expressions that can be compiled by converting this object to string
 * and will contain a correctly parenthesized and nested expression.
 */
class QueryExpression implements ExpressionInterface, Countable
{

    use TypeMapTrait;

    /**
     * String to be used for joining each of the internal expressions
     * this object internally stores for example "AND", "OR", etc.
     *
     * @var string
     */
    protected $_conjunction;

    /**
     * A list of strings or other expression objects that represent the "branches" of
     * the expression tree. For example one key of the array might look like "sum > :value"
     *
     * @var array
     */
    protected $_conditions = [];

    /**
     * Constructor. A new expression object can be created without any params and
     * be built dynamically. Otherwise it is possible to pass an array of conditions
     * containing either a tree-like array structure to be parsed and/or other
     * expression objects. Optionally, you can set the conjunction keyword to be used
     * for joining each part of this level of the expression tree.
     *
     * @param string|array|\Cake\Database\Expression\QueryExpression $conditions tree-like array structure containing all the conditions
     * to be added or nested inside this expression object.
     * @param array|\Cake\Database\TypeMap $types associative array of types to be associated with the values
     * passed in $conditions.
     * @param string $conjunction the glue that will join all the string conditions at this
     * level of the expression tree. For example "AND", "OR", "XOR"...
     * @see QueryExpression::add() for more details on $conditions and $types
     */
    public function __construct($conditions = [], $types = [], $conjunction = 'AND')
    {
        $this->typeMap($types);
        $this->tieWith(strtoupper($conjunction));
        if (!empty($conditions)) {
            $this->add($conditions, $this->typeMap()->types());
        }
    }

    /**
     * Changes the conjunction for the conditions at this level of the expression tree.
     * If called with no arguments it will return the currently configured value.
     *
     * @param string|null $conjunction value to be used for joining conditions. If null it
     * will not set any value, but return the currently stored one
     * @return string|$this
     */
    public function tieWith($conjunction = null)
    {
        if ($conjunction === null) {
            return $this->_conjunction;
        }

        $this->_conjunction = strtoupper($conjunction);
        return $this;
    }

    /**
     * Backwards compatible wrapper for tieWith()
     *
     * @param string|null $conjunction value to be used for joining conditions. If null it
     * will not set any value, but return the currently stored one
     * @return string|$this
     * @deprecated 3.2.0 Use tieWith() instead
     */
    public function type($conjunction = null)
    {
        return $this->tieWith($conjunction);
    }

    /**
     * Adds one or more conditions to this expression object. Conditions can be
     * expressed in a one dimensional array, that will cause all conditions to
     * be added directly at this level of the tree or they can be nested arbitrarily
     * making it create more expression objects that will be nested inside and
     * configured to use the specified conjunction.
     *
     * If the type passed for any of the fields is expressed "type[]" (note braces)
     * then it will cause the placeholder to be re-written dynamically so if the
     * value is an array, it will create as many placeholders as values are in it.
     *
     * @param string|array|\Cake\Database\ExpressionInterface $conditions single or multiple conditions to
     * be added. When using an array and the key is 'OR' or 'AND' a new expression
     * object will be created with that conjunction and internal array value passed
     * as conditions.
     * @param array $types associative array of fields pointing to the type of the
     * values that are being passed. Used for correctly binding values to statements.
     * @see \Cake\Database\Query::where() for examples on conditions
     * @return $this
     */
    public function add($conditions, $types = [])
    {
        if (is_string($conditions)) {
            $this->_conditions[] = $conditions;
            return $this;
        }

        if ($conditions instanceof ExpressionInterface) {
            $this->_conditions[] = $conditions;
            return $this;
        }

        $this->_addConditions($conditions, $types);
        return $this;
    }

    /**
     * Adds a new condition to the expression object in the form "field = value".
     *
     * @param string $field Database field to be compared against value
     * @param mixed $value The value to be bound to $field for comparison
     * @param string $type the type name for $value as configured using the Type map.
     * If it is suffixed with "[]" and the value is an array then multiple placeholders
     * will be created, one per each value in the array.
     * @return $this
     */
    public function eq($field, $value, $type = null)
    {
        if ($type === null) {
            $type = $this->_calculateType($field);
        }
        return $this->add(new Comparison($field, $value, $type, '='));
    }

    /**
     * Adds a new condition to the expression object in the form "field != value".
     *
     * @param string $field Database field to be compared against value
     * @param mixed $value The value to be bound to $field for comparison
     * @param string $type the type name for $value as configured using the Type map.
     * If it is suffixed with "[]" and the value is an array then multiple placeholders
     * will be created, one per each value in the array.
     * @return $this
     */
    public function notEq($field, $value, $type = null)
    {
        if ($type === null) {
            $type = $this->_calculateType($field);
        }
        return $this->add(new Comparison($field, $value, $type, '!='));
    }

    /**
     * Adds a new condition to the expression object in the form "field > value".
     *
     * @param string $field Database field to be compared against value
     * @param mixed $value The value to be bound to $field for comparison
     * @param string $type the type name for $value as configured using the Type map.
     * @return $this
     */
    public function gt($field, $value, $type = null)
    {
        if ($type === null) {
            $type = $this->_calculateType($field);
        }
        return $this->add(new Comparison($field, $value, $type, '>'));
    }

    /**
     * Adds a new condition to the expression object in the form "field < value".
     *
     * @param string $field Database field to be compared against value
     * @param mixed $value The value to be bound to $field for comparison
     * @param string $type the type name for $value as configured using the Type map.
     * @return $this
     */
    public function lt($field, $value, $type = null)
    {
        if ($type === null) {
            $type = $this->_calculateType($field);
        }
        return $this->add(new Comparison($field, $value, $type, '<'));
    }

    /**
     * Adds a new condition to the expression object in the form "field >= value".
     *
     * @param string $field Database field to be compared against value
     * @param mixed $value The value to be bound to $field for comparison
     * @param string $type the type name for $value as configured using the Type map.
     * @return $this
     */
    public function gte($field, $value, $type = null)
    {
        if ($type === null) {
            $type = $this->_calculateType($field);
        }
        return $this->add(new Comparison($field, $value, $type, '>='));
    }

    /**
     * Adds a new condition to the expression object in the form "field <= value".
     *
     * @param string $field Database field to be compared against value
     * @param mixed $value The value to be bound to $field for comparison
     * @param string $type the type name for $value as configured using the Type map.
     * @return $this
     */
    public function lte($field, $value, $type = null)
    {
        if ($type === null) {
            $type = $this->_calculateType($field);
        }
        return $this->add(new Comparison($field, $value, $type, '<='));
    }

    /**
     * Adds a new condition to the expression object in the form "field IS NULL".
     *
     * @param string|\Cake\Database\ExpressionInterface $field database field to be
     * tested for null
     * @return $this
     */
    public function isNull($field)
    {
        if (!($field instanceof ExpressionInterface)) {
            $field = new IdentifierExpression($field);
        }
        return $this->add(new UnaryExpression('IS NULL', $field, UnaryExpression::POSTFIX));
    }

    /**
     * Adds a new condition to the expression object in the form "field IS NOT NULL".
     *
     * @param string|\Cake\Database\ExpressionInterface $field database field to be
     * tested for not null
     * @return $this
     */
    public function isNotNull($field)
    {
        if (!($field instanceof ExpressionInterface)) {
            $field = new IdentifierExpression($field);
        }
        return $this->add(new UnaryExpression('IS NOT NULL', $field, UnaryExpression::POSTFIX));
    }

    /**
     * Adds a new condition to the expression object in the form "field LIKE value".
     *
     * @param string $field Database field to be compared against value
     * @param mixed $value The value to be bound to $field for comparison
     * @param string $type the type name for $value as configured using the Type map.
     * @return $this
     */
    public function like($field, $value, $type = null)
    {
        if ($type === null) {
            $type = $this->_calculateType($field);
        }
        return $this->add(new Comparison($field, $value, $type, 'LIKE'));
    }

    /**
     * Adds a new condition to the expression object in the form "field NOT LIKE value".
     *
     * @param string $field Database field to be compared against value
     * @param mixed $value The value to be bound to $field for comparison
     * @param string $type the type name for $value as configured using the Type map.
     * @return $this
     */
    public function notLike($field, $value, $type = null)
    {
        if ($type === null) {
            $type = $this->_calculateType($field);
        }
        return $this->add(new Comparison($field, $value, $type, 'NOT LIKE'));
    }

    /**
     * Adds a new condition to the expression object in the form
     * "field IN (value1, value2)".
     *
     * @param string $field Database field to be compared against value
     * @param string|array $values the value to be bound to $field for comparison
     * @param string $type the type name for $value as configured using the Type map.
     * @return $this
     */
    public function in($field, $values, $type = null)
    {
        if ($type === null) {
            $type = $this->_calculateType($field);
        }
        $type = $type ?: 'string';
        $type .= '[]';
        $values = $values instanceof ExpressionInterface ? $values : (array)$values;
        return $this->add(new Comparison($field, $values, $type, 'IN'));
    }

    /**
     * Adds a new case expression to the expression object
     *
     * @param array|\Cake\Database\ExpressionInterface $conditions The conditions to test. Must be a ExpressionInterface
     * instance, or an array of ExpressionInterface instances.
     * @param array|\Cake\Database\ExpressionInterface $values associative array of values to be associated with the conditions
     * passed in $conditions. If there are more $values than $conditions, the last $value is used as the `ELSE` value
     * @param array $types associative array of types to be associated with the values
     * passed in $values
     * @return $this
     */
    public function addCase($conditions, $values = [], $types = [])
    {
        return $this->add(new CaseExpression($conditions, $values, $types));
    }

    /**
     * Adds a new condition to the expression object in the form
     * "field NOT IN (value1, value2)".
     *
     * @param string $field Database field to be compared against value
     * @param array $values the value to be bound to $field for comparison
     * @param string $type the type name for $value as configured using the Type map.
     * @return $this
     */
    public function notIn($field, $values, $type = null)
    {
        if ($type === null) {
            $type = $this->_calculateType($field);
        }
        $type = $type ?: 'string';
        $type .= '[]';
        $values = $values instanceof ExpressionInterface ? $values : (array)$values;
        return $this->add(new Comparison($field, $values, $type, 'NOT IN'));
    }

    /**
     * Adds a new condition to the expression object in the form
     * "field BETWEEN from AND to".
     *
     * @param mixed $field The field name to compare for values in between the range.
     * @param mixed $from The initial value of the range.
     * @param mixed $to The ending value in the comparison range.
     * @param string $type the type name for $value as configured using the Type map.
     * @return $this
     */
    public function between($field, $from, $to, $type = null)
    {
        if ($type === null) {
            $type = $this->_calculateType($field);
        }
        return $this->add(new BetweenExpression($field, $from, $to, $type));
    }

// @codingStandardsIgnoreStart
    /**
     * Returns a new QueryExpression object containing all the conditions passed
     * and set up the conjunction to be "AND"
     *
     * @param string|array|QueryExpression $conditions to be joined with AND
     * @param array $types associative array of fields pointing to the type of the
     * values that are being passed. Used for correctly binding values to statements.
     * @return \Cake\Database\Expression\QueryExpression
     */
    public function and_($conditions, $types = [])
    {
        if ($this->isCallable($conditions)) {
            return $conditions(new self([], $this->typeMap()->types($types)));
        }
        return new self($conditions, $this->typeMap()->types($types));
    }

    /**
     * Returns a new QueryExpression object containing all the conditions passed
     * and set up the conjunction to be "OR"
     *
     * @param string|array|QueryExpression $conditions to be joined with OR
     * @param array $types associative array of fields pointing to the type of the
     * values that are being passed. Used for correctly binding values to statements.
     * @return \Cake\Database\Expression\QueryExpression
     */
    public function or_($conditions, $types = [])
    {
        if ($this->isCallable($conditions)) {
            return $conditions(new self([], $this->typeMap()->types($types), 'OR'));
        }
        return new self($conditions, $this->typeMap()->types($types), 'OR');
    }
// @codingStandardsIgnoreEnd

    /**
     * Adds a new set of conditions to this level of the tree and negates
     * the final result by prepending a NOT, it will look like
     * "NOT ( (condition1) AND (conditions2) )" conjunction depends on the one
     * currently configured for this object.
     *
     * @param string|array|\Cake\Database\Expression\QueryExpression $conditions to be added and negated
     * @param array $types associative array of fields pointing to the type of the
     * values that are being passed. Used for correctly binding values to statements.
     * @return $this
     */
    public function not($conditions, $types = [])
    {
        return $this->add(['NOT' => $conditions], $types);
    }

    /**
     * Returns the number of internal conditions that are stored in this expression.
     * Useful to determine if this expression object is void or it will generate
     * a non-empty string when compiled
     *
     * @return int
     */
    public function count()
    {
        return count($this->_conditions);
    }

    /**
     * Builds equal condition or assignment with identifier wrapping.
     *
     * @param string $left Left join condition field name.
     * @param string $right Right join condition field name.
     * @return $this
     */
    public function equalFields($left, $right)
    {
        $wrapIdentifier = function($field) {
            if ($field instanceof ExpressionInterface) {
                return $field;
            }
            return new IdentifierExpression($field);
        };
        return $this->eq($wrapIdentifier($left), $wrapIdentifier($right));
    }

    /**
     * Returns the string representation of this object so that it can be used in a
     * SQL query. Note that values condition values are not included in the string,
     * in their place placeholders are put and can be replaced by the quoted values
     * accordingly.
     *
     * @param \Cake\Database\ValueBinder $generator Placeholder generator object
     * @return string
     */
    public function sql(ValueBinder $generator)
    {
        $len = $this->count();
        if ($len === 0) {
            return '';
        }
        $conjunction = $this->_conjunction;
        $template = ($len === 1) ? '%s' : '(%s)';
        $parts = [];
        foreach ($this->_conditions as $part) {
            if ($part instanceof Query) {
                $part = '(' . $part->sql($generator) . ')';
            } elseif ($part instanceof ExpressionInterface) {
                $part = $part->sql($generator);
            }
            if (strlen($part)) {
                $parts[] = $part;
            }
        }
        return sprintf($template, implode(" $conjunction ", $parts));
    }

    /**
     * Traverses the tree structure of this query expression by executing a callback
     * function for each of the conditions that are included in this object.
     * Useful for compiling the final expression, or doing
     * introspection in the structure.
     *
     * Callback function receives as only argument an instance of a QueryExpression
     *
     * @param callable $callable The callable to apply to all sub-expressions.
     * @return void
     */
    public function traverse(callable $callable)
    {
        foreach ($this->_conditions as $c) {
            if ($c instanceof ExpressionInterface) {
                $callable($c);
                $c->traverse($callable);
            }
        }
    }

    /**
     * Executes a callable function for each of the parts that form this expression.
     *
     * The callable function is required to return a value with which the currently
     * visited part will be replaced. If the callable function returns null then
     * the part will be discarded completely from this expression.
     *
     * The callback function will receive each of the conditions as first param and
     * the key as second param. It is possible to declare the second parameter as
     * passed by reference, this will enable you to change the key under which the
     * modified part is stored.
     *
     * @param callable $callable The callable to apply to each part.
     * @return $this
     */
    public function iterateParts(callable $callable)
    {
        $parts = [];
        foreach ($this->_conditions as $k => $c) {
            $key =& $k;
            $part = $callable($c, $key);
            if ($part !== null) {
                $parts[$key] = $part;
            }
        }
        $this->_conditions = $parts;

        return $this;
    }

    /**
     * Helps calling the `and()` and `or()` methods transparently.
     *
     * @param string $method The method name.
     * @param array $args The arguments to pass to the method.
     * @return \Cake\Database\Expression\QueryExpression
     * @throws \BadMethodCallException
     */
    public function __call($method, $args)
    {
        if (in_array($method, ['and', 'or'])) {
            return call_user_func_array([$this, $method . '_'], $args);
        }
        throw new BadMethodCallException(sprintf('Method %s does not exist', $method));
    }

    /**
     * Check whether or not a callable is acceptable.
     *
     * We don't accept ['class', 'method'] style callbacks,
     * as they often contain user input and arrays of strings
     * are easy to sneak in.
     *
     * @param callable $c The callable to check.
     * @return bool Valid callable.
     */
    public function isCallable($c)
    {
        if (is_string($c)) {
            return false;
        }
        if (is_object($c) && is_callable($c)) {
            return true;
        }
        return is_array($c) && isset($c[0]) && is_object($c[0]) && is_callable($c);
    }

    /**
     * Returns true if this expression contains any other nested
     * ExpressionInterface objects
     *
     * @return bool
     */
    public function hasNestedExpression()
    {
        foreach ($this->_conditions as $c) {
            if ($c instanceof ExpressionInterface) {
                return true;
            }
        }
        return false;
    }

    /**
     * Auxiliary function used for decomposing a nested array of conditions and build
     * a tree structure inside this object to represent the full SQL expression.
     * String conditions are stored directly in the conditions, while any other
     * representation is wrapped around an adequate instance or of this class.
     *
     * @param array $conditions list of conditions to be stored in this object
     * @param array $types list of types associated on fields referenced in $conditions
     * @return void
     */
    protected function _addConditions(array $conditions, array $types)
    {
        $operators = ['and', 'or', 'xor'];

        $typeMap = $this->typeMap()->types($types);

        foreach ($conditions as $k => $c) {
            $numericKey = is_numeric($k);

            if ($numericKey && empty($c)) {
                continue;
            }

            if ($this->isCallable($c)) {
                $expr = new QueryExpression([], $typeMap);
                $c = $c($expr, $this);
            }

            if ($numericKey && is_string($c)) {
                $this->_conditions[] = $c;
                continue;
            }

            if ($numericKey && is_array($c) || in_array(strtolower($k), $operators)) {
                $this->_conditions[] = new self($c, $typeMap, $numericKey ? 'AND' : $k);
                continue;
            }

            if (strtolower($k) === 'not') {
                $this->_conditions[] = new UnaryExpression('NOT', new self($c, $typeMap));
                continue;
            }

            if ($c instanceof self && count($c) === 0) {
                continue;
            }

            if ($numericKey && $c instanceof ExpressionInterface) {
                $this->_conditions[] = $c;
                continue;
            }

            if (!$numericKey) {
                $this->_conditions[] = $this->_parseCondition($k, $c);
            }
        }
    }

    /**
     * Parses a string conditions by trying to extract the operator inside it if any
     * and finally returning either an adequate QueryExpression object or a plain
     * string representation of the condition. This function is responsible for
     * generating the placeholders and replacing the values by them, while storing
     * the value elsewhere for future binding.
     *
     * @param string $field The value from with the actual field and operator will
     * be extracted.
     * @param mixed $value The value to be bound to a placeholder for the field
     * @return string|\Cake\Database\ExpressionInterface
     */
    protected function _parseCondition($field, $value)
    {
        $operator = '=';
        $expression = $field;
        $parts = explode(' ', trim($field), 2);

        if (count($parts) > 1) {
            list($expression, $operator) = $parts;
        }

        $type = $this->typeMap()->type($expression);
        $operator = strtolower(trim($operator));

        $typeMultiple = strpos($type, '[]') !== false;
        if (in_array($operator, ['in', 'not in']) || $typeMultiple) {
            $type = $type ?: 'string';
            $type .= $typeMultiple ? null : '[]';
            $operator = $operator === '=' ? 'IN' : $operator;
            $operator = $operator === '!=' ? 'NOT IN' : $operator;
            $typeMultiple = true;
        }

        if ($typeMultiple) {
            $value = $value instanceof ExpressionInterface ? $value : (array)$value;
        }

        if ($operator === 'is' && $value === null) {
            return new UnaryExpression(
                'IS NULL',
                new IdentifierExpression($expression),
                UnaryExpression::POSTFIX
            );
        }

        if ($operator === 'is not' && $value === null) {
            return new UnaryExpression(
                'IS NOT NULL',
                new IdentifierExpression($expression),
                UnaryExpression::POSTFIX
            );
        }

        if ($operator === 'is' && $value !== null) {
            $operator = '=';
        }

        if ($operator === 'is not' && $value !== null) {
            $operator = '!=';
        }

        return new Comparison($expression, $value, $type, $operator);
    }

    /**
     * Returns the type name for the passed field if it was stored in the typeMap
     *
     * @param string|\Cake\Database\Expression\QueryExpression $field The field name to get a type for.
     * @return string|null The computed type or null, if the type is unknown.
     */
    protected function _calculateType($field)
    {
        $field = $field instanceof IdentifierExpression ? $field->getIdentifier() : $field;
        if (is_string($field)) {
            return $this->typeMap()->type($field);
        }
        return null;
    }

    /**
     * Clone this object and its subtree of expressions.
     *
     * @return void
     */
    public function __clone()
    {
        foreach ($this->_conditions as $i => $condition) {
            if ($condition instanceof ExpressionInterface) {
                $this->_conditions[$i] = clone $condition;
            }
        }
    }
}
