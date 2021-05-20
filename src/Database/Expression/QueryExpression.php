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
use Cake\Database\Query;
use Cake\Database\TypeMapTrait;
use Cake\Database\ValueBinder;
use Closure;
use Countable;
use InvalidArgumentException;

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
     * @param string|array|\Cake\Database\ExpressionInterface $conditions Tree like array structure
     * containing all the conditions to be added or nested inside this expression object.
     * @param array|\Cake\Database\TypeMap $types associative array of types to be associated with the values
     * passed in $conditions.
     * @param string $conjunction the glue that will join all the string conditions at this
     * level of the expression tree. For example "AND", "OR", "XOR"...
     * @see \Cake\Database\Expression\QueryExpression::add() for more details on $conditions and $types
     */
    public function __construct($conditions = [], $types = [], $conjunction = 'AND')
    {
        $this->setTypeMap($types);
        $this->setConjunction(strtoupper($conjunction));
        if (!empty($conditions)) {
            $this->add($conditions, $this->getTypeMap()->getTypes());
        }
    }

    /**
     * Changes the conjunction for the conditions at this level of the expression tree.
     *
     * @param string $conjunction Value to be used for joining conditions
     * @return $this
     */
    public function setConjunction(string $conjunction)
    {
        $this->_conjunction = strtoupper($conjunction);

        return $this;
    }

    /**
     * Gets the currently configured conjunction for the conditions at this level of the expression tree.
     *
     * @return string
     */
    public function getConjunction(): string
    {
        return $this->_conjunction;
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
    public function add($conditions, array $types = [])
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
     * @param string|\Cake\Database\ExpressionInterface $field Database field to be compared against value
     * @param mixed $value The value to be bound to $field for comparison
     * @param string|null $type the type name for $value as configured using the Type map.
     * If it is suffixed with "[]" and the value is an array then multiple placeholders
     * will be created, one per each value in the array.
     * @return $this
     */
    public function eq($field, $value, ?string $type = null)
    {
        if ($type === null) {
            $type = $this->_calculateType($field);
        }

        return $this->add(new ComparisonExpression($field, $value, $type, '='));
    }

    /**
     * Adds a new condition to the expression object in the form "field != value".
     *
     * @param string|\Cake\Database\ExpressionInterface $field Database field to be compared against value
     * @param mixed $value The value to be bound to $field for comparison
     * @param string|null $type the type name for $value as configured using the Type map.
     * If it is suffixed with "[]" and the value is an array then multiple placeholders
     * will be created, one per each value in the array.
     * @return $this
     */
    public function notEq($field, $value, $type = null)
    {
        if ($type === null) {
            $type = $this->_calculateType($field);
        }

        return $this->add(new ComparisonExpression($field, $value, $type, '!='));
    }

    /**
     * Adds a new condition to the expression object in the form "field > value".
     *
     * @param string|\Cake\Database\ExpressionInterface $field Database field to be compared against value
     * @param mixed $value The value to be bound to $field for comparison
     * @param string|null $type the type name for $value as configured using the Type map.
     * @return $this
     */
    public function gt($field, $value, $type = null)
    {
        if ($type === null) {
            $type = $this->_calculateType($field);
        }

        return $this->add(new ComparisonExpression($field, $value, $type, '>'));
    }

    /**
     * Adds a new condition to the expression object in the form "field < value".
     *
     * @param string|\Cake\Database\ExpressionInterface $field Database field to be compared against value
     * @param mixed $value The value to be bound to $field for comparison
     * @param string|null $type the type name for $value as configured using the Type map.
     * @return $this
     */
    public function lt($field, $value, $type = null)
    {
        if ($type === null) {
            $type = $this->_calculateType($field);
        }

        return $this->add(new ComparisonExpression($field, $value, $type, '<'));
    }

    /**
     * Adds a new condition to the expression object in the form "field >= value".
     *
     * @param string|\Cake\Database\ExpressionInterface $field Database field to be compared against value
     * @param mixed $value The value to be bound to $field for comparison
     * @param string|null $type the type name for $value as configured using the Type map.
     * @return $this
     */
    public function gte($field, $value, $type = null)
    {
        if ($type === null) {
            $type = $this->_calculateType($field);
        }

        return $this->add(new ComparisonExpression($field, $value, $type, '>='));
    }

    /**
     * Adds a new condition to the expression object in the form "field <= value".
     *
     * @param string|\Cake\Database\ExpressionInterface $field Database field to be compared against value
     * @param mixed $value The value to be bound to $field for comparison
     * @param string|null $type the type name for $value as configured using the Type map.
     * @return $this
     */
    public function lte($field, $value, $type = null)
    {
        if ($type === null) {
            $type = $this->_calculateType($field);
        }

        return $this->add(new ComparisonExpression($field, $value, $type, '<='));
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
     * @param string|\Cake\Database\ExpressionInterface $field Database field to be compared against value
     * @param mixed $value The value to be bound to $field for comparison
     * @param string|null $type the type name for $value as configured using the Type map.
     * @return $this
     */
    public function like($field, $value, $type = null)
    {
        if ($type === null) {
            $type = $this->_calculateType($field);
        }

        return $this->add(new ComparisonExpression($field, $value, $type, 'LIKE'));
    }

    /**
     * Adds a new condition to the expression object in the form "field NOT LIKE value".
     *
     * @param string|\Cake\Database\ExpressionInterface $field Database field to be compared against value
     * @param mixed $value The value to be bound to $field for comparison
     * @param string|null $type the type name for $value as configured using the Type map.
     * @return $this
     */
    public function notLike($field, $value, $type = null)
    {
        if ($type === null) {
            $type = $this->_calculateType($field);
        }

        return $this->add(new ComparisonExpression($field, $value, $type, 'NOT LIKE'));
    }

    /**
     * Adds a new condition to the expression object in the form
     * "field IN (value1, value2)".
     *
     * @param string|\Cake\Database\ExpressionInterface $field Database field to be compared against value
     * @param string|array|\Cake\Database\ExpressionInterface $values the value to be bound to $field for comparison
     * @param string|null $type the type name for $value as configured using the Type map.
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

        return $this->add(new ComparisonExpression($field, $values, $type, 'IN'));
    }

    /**
     * Adds a new case expression to the expression object
     *
     * @param array|\Cake\Database\ExpressionInterface $conditions The conditions to test. Must be a ExpressionInterface
     * instance, or an array of ExpressionInterface instances.
     * @param array|\Cake\Database\ExpressionInterface $values associative array of values to be associated with the
     * conditions passed in $conditions. If there are more $values than $conditions,
     * the last $value is used as the `ELSE` value.
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
     * @param string|\Cake\Database\ExpressionInterface $field Database field to be compared against value
     * @param string|array|\Cake\Database\ExpressionInterface $values the value to be bound to $field for comparison
     * @param string|null $type the type name for $value as configured using the Type map.
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

        return $this->add(new ComparisonExpression($field, $values, $type, 'NOT IN'));
    }

    /**
     * Adds a new condition to the expression object in the form "EXISTS (...)".
     *
     * @param \Cake\Database\ExpressionInterface $expression the inner query
     * @return $this
     */
    public function exists(ExpressionInterface $expression)
    {
        return $this->add(new UnaryExpression('EXISTS', $expression, UnaryExpression::PREFIX));
    }

    /**
     * Adds a new condition to the expression object in the form "NOT EXISTS (...)".
     *
     * @param \Cake\Database\ExpressionInterface $expression the inner query
     * @return $this
     */
    public function notExists(ExpressionInterface $expression)
    {
        return $this->add(new UnaryExpression('NOT EXISTS', $expression, UnaryExpression::PREFIX));
    }

    /**
     * Adds a new condition to the expression object in the form
     * "field BETWEEN from AND to".
     *
     * @param string|\Cake\Database\ExpressionInterface $field The field name to compare for values inbetween the range.
     * @param mixed $from The initial value of the range.
     * @param mixed $to The ending value in the comparison range.
     * @param string|null $type the type name for $value as configured using the Type map.
     * @return $this
     */
    public function between($field, $from, $to, $type = null)
    {
        if ($type === null) {
            $type = $this->_calculateType($field);
        }

        return $this->add(new BetweenExpression($field, $from, $to, $type));
    }

    /**
     * Returns a new QueryExpression object containing all the conditions passed
     * and set up the conjunction to be "AND"
     *
     * @param \Closure|string|array|\Cake\Database\ExpressionInterface $conditions to be joined with AND
     * @param array $types associative array of fields pointing to the type of the
     * values that are being passed. Used for correctly binding values to statements.
     * @return \Cake\Database\Expression\QueryExpression
     */
    public function and($conditions, $types = [])
    {
        if ($conditions instanceof Closure) {
            return $conditions(new static([], $this->getTypeMap()->setTypes($types)));
        }

        return new static($conditions, $this->getTypeMap()->setTypes($types));
    }

    /**
     * Returns a new QueryExpression object containing all the conditions passed
     * and set up the conjunction to be "OR"
     *
     * @param \Closure|string|array|\Cake\Database\ExpressionInterface $conditions to be joined with OR
     * @param array $types associative array of fields pointing to the type of the
     * values that are being passed. Used for correctly binding values to statements.
     * @return \Cake\Database\Expression\QueryExpression
     */
    public function or($conditions, $types = [])
    {
        if ($conditions instanceof Closure) {
            return $conditions(new static([], $this->getTypeMap()->setTypes($types), 'OR'));
        }

        return new static($conditions, $this->getTypeMap()->setTypes($types), 'OR');
    }

    // phpcs:disable PSR1.Methods.CamelCapsMethodName.NotCamelCaps

    /**
     * Returns a new QueryExpression object containing all the conditions passed
     * and set up the conjunction to be "AND"
     *
     * @param \Closure|string|array|\Cake\Database\ExpressionInterface $conditions to be joined with AND
     * @param array $types associative array of fields pointing to the type of the
     * values that are being passed. Used for correctly binding values to statements.
     * @return \Cake\Database\Expression\QueryExpression
     * @deprecated 4.0.0 Use {@link and()} instead.
     */
    public function and_($conditions, $types = [])
    {
        deprecationWarning('QueryExpression::and_() is deprecated use and() instead.');

        return $this->and($conditions, $types);
    }

    /**
     * Returns a new QueryExpression object containing all the conditions passed
     * and set up the conjunction to be "OR"
     *
     * @param \Closure|string|array|\Cake\Database\ExpressionInterface $conditions to be joined with OR
     * @param array $types associative array of fields pointing to the type of the
     * values that are being passed. Used for correctly binding values to statements.
     * @return \Cake\Database\Expression\QueryExpression
     * @deprecated 4.0.0 Use {@link or()} instead.
     */
    public function or_($conditions, $types = [])
    {
        deprecationWarning('QueryExpression::or_() is deprecated use or() instead.');

        return $this->or($conditions, $types);
    }

    // phpcs:enable

    /**
     * Adds a new set of conditions to this level of the tree and negates
     * the final result by prepending a NOT, it will look like
     * "NOT ( (condition1) AND (conditions2) )" conjunction depends on the one
     * currently configured for this object.
     *
     * @param string|array|\Closure|\Cake\Database\ExpressionInterface $conditions to be added and negated
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
    public function count(): int
    {
        return count($this->_conditions);
    }

    /**
     * Builds equal condition or assignment with identifier wrapping.
     *
     * @param string $leftField Left join condition field name.
     * @param string $rightField Right join condition field name.
     * @return $this
     */
    public function equalFields(string $leftField, string $rightField)
    {
        $wrapIdentifier = function ($field) {
            if ($field instanceof ExpressionInterface) {
                return $field;
            }

            return new IdentifierExpression($field);
        };

        return $this->eq($wrapIdentifier($leftField), $wrapIdentifier($rightField));
    }

    /**
     * @inheritDoc
     */
    public function sql(ValueBinder $binder): string
    {
        $len = $this->count();
        if ($len === 0) {
            return '';
        }
        $conjunction = $this->_conjunction;
        $template = $len === 1 ? '%s' : '(%s)';
        $parts = [];
        foreach ($this->_conditions as $part) {
            if ($part instanceof Query) {
                $part = '(' . $part->sql($binder) . ')';
            } elseif ($part instanceof ExpressionInterface) {
                $part = $part->sql($binder);
            }
            if (strlen($part)) {
                $parts[] = $part;
            }
        }

        return sprintf($template, implode(" $conjunction ", $parts));
    }

    /**
     * @inheritDoc
     */
    public function traverse(Closure $callback)
    {
        foreach ($this->_conditions as $c) {
            if ($c instanceof ExpressionInterface) {
                $callback($c);
                $c->traverse($callback);
            }
        }

        return $this;
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
     * @param callable $callback The callable to apply to each part.
     * @return $this
     */
    public function iterateParts(callable $callback)
    {
        $parts = [];
        foreach ($this->_conditions as $k => $c) {
            $key = &$k;
            $part = $callback($c, $key);
            if ($part !== null) {
                $parts[$key] = $part;
            }
        }
        $this->_conditions = $parts;

        return $this;
    }

    /**
     * Check whether or not a callable is acceptable.
     *
     * We don't accept ['class', 'method'] style callbacks,
     * as they often contain user input and arrays of strings
     * are easy to sneak in.
     *
     * @param callable|string|array|\Cake\Database\ExpressionInterface $callable The callable to check.
     * @return bool Valid callable.
     * @deprecated 4.2.0 This method is unused.
     * @codeCoverageIgnore
     */
    public function isCallable($callable): bool
    {
        if (is_string($callable)) {
            return false;
        }
        if (is_object($callable) && is_callable($callable)) {
            return true;
        }

        return is_array($callable) && isset($callable[0]) && is_object($callable[0]) && is_callable($callable);
    }

    /**
     * Returns true if this expression contains any other nested
     * ExpressionInterface objects
     *
     * @return bool
     */
    public function hasNestedExpression(): bool
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
    protected function _addConditions(array $conditions, array $types): void
    {
        $operators = ['and', 'or', 'xor'];

        $typeMap = $this->getTypeMap()->setTypes($types);

        foreach ($conditions as $k => $c) {
            $numericKey = is_numeric($k);

            if ($c instanceof Closure) {
                $expr = new static([], $typeMap);
                $c = $c($expr, $this);
            }

            if ($numericKey && empty($c)) {
                continue;
            }

            $isArray = is_array($c);
            $isOperator = $isNot = false;
            if (!$numericKey) {
                $normalizedKey = strtolower($k);
                $isOperator = in_array($normalizedKey, $operators);
                $isNot = $normalizedKey === 'not';
            }

            if (($isOperator || $isNot) && ($isArray || $c instanceof Countable) && count($c) === 0) {
                continue;
            }

            if ($numericKey && $c instanceof ExpressionInterface) {
                $this->_conditions[] = $c;
                continue;
            }

            if ($numericKey && is_string($c)) {
                $this->_conditions[] = $c;
                continue;
            }

            if ($numericKey && $isArray || $isOperator) {
                $this->_conditions[] = new static($c, $typeMap, $numericKey ? 'AND' : $k);
                continue;
            }

            if ($isNot) {
                $this->_conditions[] = new UnaryExpression('NOT', new static($c, $typeMap));
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
     * @param string $field The value from which the actual field and operator will
     * be extracted.
     * @param mixed $value The value to be bound to a placeholder for the field
     * @return string|\Cake\Database\ExpressionInterface
     * @throws \InvalidArgumentException If operator is invalid or missing on NULL usage.
     */
    protected function _parseCondition(string $field, $value)
    {
        $field = trim($field);
        $operator = '=';
        $expression = $field;

        $spaces = substr_count($field, ' ');
        // Handle operators with a space in them like `is not` and `not like`
        if ($spaces > 1) {
            $parts = explode(' ', $field);
            if (preg_match('/(is not|not \w+)$/i', $field)) {
                $last = array_pop($parts);
                $second = array_pop($parts);
                array_push($parts, strtolower("{$second} {$last}"));
            }
            $operator = array_pop($parts);
            $expression = implode(' ', $parts);
        } elseif ($spaces == 1) {
            $parts = explode(' ', $field, 2);
            [$expression, $operator] = $parts;
            $operator = strtolower(trim($operator));
        }
        $type = $this->getTypeMap()->type($expression);

        $typeMultiple = (is_string($type) && strpos($type, '[]') !== false);
        if (in_array($operator, ['in', 'not in']) || $typeMultiple) {
            $type = $type ?: 'string';
            if (!$typeMultiple) {
                $type .= '[]';
            }
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

        if ($value === null && $this->_conjunction !== ',') {
            throw new InvalidArgumentException(
                sprintf('Expression `%s` is missing operator (IS, IS NOT) with `null` value.', $expression)
            );
        }

        return new ComparisonExpression($expression, $value, $type, $operator);
    }

    /**
     * Returns the type name for the passed field if it was stored in the typeMap
     *
     * @param string|\Cake\Database\ExpressionInterface $field The field name to get a type for.
     * @return string|null The computed type or null, if the type is unknown.
     */
    protected function _calculateType($field): ?string
    {
        $field = $field instanceof IdentifierExpression ? $field->getIdentifier() : $field;
        if (is_string($field)) {
            return $this->getTypeMap()->type($field);
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
