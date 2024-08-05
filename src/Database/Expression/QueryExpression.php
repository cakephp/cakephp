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
use Cake\Database\TypeMap;
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
    protected string $_conjunction;

    /**
     * A list of strings or other expression objects that represent the "branches" of
     * the expression tree. For example one key of the array might look like "sum > :value"
     *
     * @var array
     */
    protected array $_conditions = [];

    /**
     * Constructor. A new expression object can be created without any params and
     * be built dynamically. Otherwise, it is possible to pass an array of conditions
     * containing either a tree-like array structure to be parsed and/or other
     * expression objects. Optionally, you can set the conjunction keyword to be used
     * for joining each part of this level of the expression tree.
     *
     * @param \Cake\Database\ExpressionInterface|array|string $conditions Tree like array structure
     * containing all the conditions to be added or nested inside this expression object.
     * @param \Cake\Database\TypeMap|array $types Associative array of types to be associated with the values
     * passed in $conditions.
     * @param string $conjunction the glue that will join all the string conditions at this
     * level of the expression tree. For example "AND", "OR", "XOR"...
     * @see \Cake\Database\Expression\QueryExpression::add() for more details on $conditions and $types
     */
    public function __construct(
        ExpressionInterface|array|string $conditions = [],
        TypeMap|array $types = [],
        string $conjunction = 'AND'
    ) {
        $this->setTypeMap($types);
        $this->setConjunction(strtoupper($conjunction));
        if ($conditions) {
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
     * @param \Cake\Database\ExpressionInterface|array|string $conditions single or multiple conditions to
     * be added. When using an array and the key is 'OR' or 'AND' a new expression
     * object will be created with that conjunction and internal array value passed
     * as conditions.
     * @param array<int|string, string> $types Associative array of fields pointing to the type of the
     * values that are being passed. Used for correctly binding values to statements.
     * @see \Cake\Database\Query::where() for examples on conditions
     * @return $this
     */
    public function add(ExpressionInterface|array|string $conditions, array $types = [])
    {
        if (is_string($conditions) || $conditions instanceof ExpressionInterface) {
            $this->_conditions[] = $conditions;

            return $this;
        }

        $this->_addConditions($conditions, $types);

        return $this;
    }

    /**
     * Adds a new condition to the expression object in the form "field = value".
     *
     * @param \Cake\Database\ExpressionInterface|string $field Database field to be compared against value
     * @param mixed $value The value to be bound to $field for comparison
     * @param string|null $type the type name for $value as configured using the Type map.
     * If it is suffixed with "[]" and the value is an array then multiple placeholders
     * will be created, one per each value in the array.
     * @return $this
     */
    public function eq(ExpressionInterface|string $field, mixed $value, ?string $type = null)
    {
        $type ??= $this->_calculateType($field);

        return $this->add(new ComparisonExpression($field, $value, $type, '='));
    }

    /**
     * Adds a new condition to the expression object in the form "field != value".
     *
     * @param \Cake\Database\ExpressionInterface|string $field Database field to be compared against value
     * @param mixed $value The value to be bound to $field for comparison
     * @param string|null $type the type name for $value as configured using the Type map.
     * If it is suffixed with "[]" and the value is an array then multiple placeholders
     * will be created, one per each value in the array.
     * @return $this
     */
    public function notEq(ExpressionInterface|string $field, mixed $value, ?string $type = null)
    {
        $type ??= $this->_calculateType($field);

        return $this->add(new ComparisonExpression($field, $value, $type, '!='));
    }

    /**
     * Adds a new condition to the expression object in the form "field > value".
     *
     * @param \Cake\Database\ExpressionInterface|string $field Database field to be compared against value
     * @param mixed $value The value to be bound to $field for comparison
     * @param string|null $type the type name for $value as configured using the Type map.
     * @return $this
     */
    public function gt(ExpressionInterface|string $field, mixed $value, ?string $type = null)
    {
        $type ??= $this->_calculateType($field);

        return $this->add(new ComparisonExpression($field, $value, $type, '>'));
    }

    /**
     * Adds a new condition to the expression object in the form "field < value".
     *
     * @param \Cake\Database\ExpressionInterface|string $field Database field to be compared against value
     * @param mixed $value The value to be bound to $field for comparison
     * @param string|null $type the type name for $value as configured using the Type map.
     * @return $this
     */
    public function lt(ExpressionInterface|string $field, mixed $value, ?string $type = null)
    {
        $type ??= $this->_calculateType($field);

        return $this->add(new ComparisonExpression($field, $value, $type, '<'));
    }

    /**
     * Adds a new condition to the expression object in the form "field >= value".
     *
     * @param \Cake\Database\ExpressionInterface|string $field Database field to be compared against value
     * @param mixed $value The value to be bound to $field for comparison
     * @param string|null $type the type name for $value as configured using the Type map.
     * @return $this
     */
    public function gte(ExpressionInterface|string $field, mixed $value, ?string $type = null)
    {
        $type ??= $this->_calculateType($field);

        return $this->add(new ComparisonExpression($field, $value, $type, '>='));
    }

    /**
     * Adds a new condition to the expression object in the form "field <= value".
     *
     * @param \Cake\Database\ExpressionInterface|string $field Database field to be compared against value
     * @param mixed $value The value to be bound to $field for comparison
     * @param string|null $type the type name for $value as configured using the Type map.
     * @return $this
     */
    public function lte(ExpressionInterface|string $field, mixed $value, ?string $type = null)
    {
        $type ??= $this->_calculateType($field);

        return $this->add(new ComparisonExpression($field, $value, $type, '<='));
    }

    /**
     * Adds a new condition to the expression object in the form "field IS NULL".
     *
     * @param \Cake\Database\ExpressionInterface|string $field database field to be
     * tested for null
     * @return $this
     */
    public function isNull(ExpressionInterface|string $field)
    {
        if (!($field instanceof ExpressionInterface)) {
            $field = new IdentifierExpression($field);
        }

        return $this->add(new UnaryExpression('IS NULL', $field, UnaryExpression::POSTFIX));
    }

    /**
     * Adds a new condition to the expression object in the form "field IS NOT NULL".
     *
     * @param \Cake\Database\ExpressionInterface|string $field database field to be
     * tested for not null
     * @return $this
     */
    public function isNotNull(ExpressionInterface|string $field)
    {
        if (!($field instanceof ExpressionInterface)) {
            $field = new IdentifierExpression($field);
        }

        return $this->add(new UnaryExpression('IS NOT NULL', $field, UnaryExpression::POSTFIX));
    }

    /**
     * Adds a new condition to the expression object in the form "field LIKE value".
     *
     * @param \Cake\Database\ExpressionInterface|string $field Database field to be compared against value
     * @param mixed $value The value to be bound to $field for comparison
     * @param string|null $type the type name for $value as configured using the Type map.
     * @return $this
     */
    public function like(ExpressionInterface|string $field, mixed $value, ?string $type = null)
    {
        $type ??= $this->_calculateType($field);

        return $this->add(new ComparisonExpression($field, $value, $type, 'LIKE'));
    }

    /**
     * Adds a new condition to the expression object in the form "field NOT LIKE value".
     *
     * @param \Cake\Database\ExpressionInterface|string $field Database field to be compared against value
     * @param mixed $value The value to be bound to $field for comparison
     * @param string|null $type the type name for $value as configured using the Type map.
     * @return $this
     */
    public function notLike(ExpressionInterface|string $field, mixed $value, ?string $type = null)
    {
        $type ??= $this->_calculateType($field);

        return $this->add(new ComparisonExpression($field, $value, $type, 'NOT LIKE'));
    }

    /**
     * Adds a new condition to the expression object in the form
     * "field IN (value1, value2)".
     *
     * @param \Cake\Database\ExpressionInterface|string $field Database field to be compared against value
     * @param \Cake\Database\ExpressionInterface|array|string $values the value to be bound to $field for comparison
     * @param string|null $type the type name for $value as configured using the Type map.
     * @return $this
     */
    public function in(
        ExpressionInterface|string $field,
        ExpressionInterface|array|string $values,
        ?string $type = null
    ) {
        $type ??= $this->_calculateType($field);
        $type = $type ?: 'string';
        $type .= '[]';
        $values = $values instanceof ExpressionInterface ? $values : (array)$values;

        return $this->add(new ComparisonExpression($field, $values, $type, 'IN'));
    }

    /**
     * Returns a new case expression object.
     *
     * When a value is set, the syntax generated is
     * `CASE case_value WHEN when_value ... END` (simple case),
     * where the `when_value`'s are compared against the
     * `case_value`.
     *
     * When no value is set, the syntax generated is
     * `CASE WHEN when_conditions ... END` (searched case),
     * where the conditions hold the comparisons.
     *
     * Note that `null` is a valid case value, and thus should
     * only be passed if you actually want to create the simple
     * case expression variant!
     *
     * @param \Cake\Database\ExpressionInterface|object|scalar|null $value The case value.
     * @param string|null $type The case value type. If no type is provided, the type will be tried to be inferred
     *  from the value.
     * @return \Cake\Database\Expression\CaseStatementExpression
     */
    public function case(mixed $value = null, ?string $type = null): CaseStatementExpression
    {
        if (func_num_args() > 0) {
            $expression = new CaseStatementExpression($value, $type);
        } else {
            $expression = new CaseStatementExpression();
        }

        return $expression->setTypeMap($this->getTypeMap());
    }

    /**
     * Adds a new condition to the expression object in the form
     * "field NOT IN (value1, value2)".
     *
     * @param \Cake\Database\ExpressionInterface|string $field Database field to be compared against value
     * @param \Cake\Database\ExpressionInterface|array|string $values the value to be bound to $field for comparison
     * @param string|null $type the type name for $value as configured using the Type map.
     * @return $this
     */
    public function notIn(
        ExpressionInterface|string $field,
        ExpressionInterface|array|string $values,
        ?string $type = null
    ) {
        $type ??= $this->_calculateType($field);
        $type = $type ?: 'string';
        $type .= '[]';
        $values = $values instanceof ExpressionInterface ? $values : (array)$values;

        return $this->add(new ComparisonExpression($field, $values, $type, 'NOT IN'));
    }

    /**
     * Adds a new condition to the expression object in the form
     * "(field NOT IN (value1, value2) OR field IS NULL".
     *
     * @param \Cake\Database\ExpressionInterface|string $field Database field to be compared against value
     * @param \Cake\Database\ExpressionInterface|array|string $values the value to be bound to $field for comparison
     * @param string|null $type the type name for $value as configured using the Type map.
     * @return $this
     */
    public function notInOrNull(
        ExpressionInterface|string $field,
        ExpressionInterface|array|string $values,
        ?string $type = null
    ) {
        $or = new static([], [], 'OR');
        $or
            ->notIn($field, $values, $type)
            ->isNull($field);

        return $this->add($or);
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
     * @param \Cake\Database\ExpressionInterface|string $field The field name to compare for values inbetween the range.
     * @param mixed $from The initial value of the range.
     * @param mixed $to The ending value in the comparison range.
     * @param string|null $type the type name for $value as configured using the Type map.
     * @return $this
     */
    public function between(ExpressionInterface|string $field, mixed $from, mixed $to, ?string $type = null)
    {
        $type ??= $this->_calculateType($field);

        return $this->add(new BetweenExpression($field, $from, $to, $type));
    }

    /**
     * Returns a new QueryExpression object containing all the conditions passed
     * and set up the conjunction to be "AND"
     *
     * @param \Cake\Database\ExpressionInterface|\Closure|array|string $conditions to be joined with AND
     * @param array<string, string> $types Associative array of fields pointing to the type of the
     * values that are being passed. Used for correctly binding values to statements.
     * @return static
     */
    public function and(ExpressionInterface|Closure|array|string $conditions, array $types = []): static
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
     * @param \Cake\Database\ExpressionInterface|\Closure|array|string $conditions to be joined with OR
     * @param array<string, string> $types Associative array of fields pointing to the type of the
     * values that are being passed. Used for correctly binding values to statements.
     * @return static
     */
    public function or(ExpressionInterface|Closure|array|string $conditions, array $types = []): static
    {
        if ($conditions instanceof Closure) {
            return $conditions(new static([], $this->getTypeMap()->setTypes($types), 'OR'));
        }

        return new static($conditions, $this->getTypeMap()->setTypes($types), 'OR');
    }

    /**
     * Adds a new set of conditions to this level of the tree and negates
     * the final result by prepending a NOT, it will look like
     * "NOT ( (condition1) AND (conditions2) )" conjunction depends on the one
     * currently configured for this object.
     *
     * @param \Cake\Database\ExpressionInterface|\Closure|array|string $conditions to be added and negated
     * @param array<string, string> $types Associative array of fields pointing to the type of the
     * values that are being passed. Used for correctly binding values to statements.
     * @return $this
     */
    public function not(ExpressionInterface|Closure|array|string $conditions, array $types = [])
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
        $wrapIdentifier = function ($field): ExpressionInterface {
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
            if ($part !== '') {
                $parts[] = $part;
            }
        }

        return sprintf($template, implode(" {$conjunction} ", $parts));
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
     * Executes a callback for each of the parts that form this expression.
     *
     * The callback is required to return a value with which the currently
     * visited part will be replaced. If the callback returns null then
     * the part will be discarded completely from this expression.
     *
     * The callback function will receive each of the conditions as first param and
     * the key as second param. It is possible to declare the second parameter as
     * passed by reference, this will enable you to change the key under which the
     * modified part is stored.
     *
     * @param \Closure $callback The callback to run for each part
     * @return $this
     */
    public function iterateParts(Closure $callback)
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
     * @param array<int|string, string> $types list of types associated on fields referenced in $conditions
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
            $isOperator = false;
            $isNot = false;
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
     * @param string $condition The value from which the actual field and operator will
     * be extracted.
     * @param mixed $value The value to be bound to a placeholder for the field
     * @return \Cake\Database\ExpressionInterface|string
     * @throws \InvalidArgumentException If operator is invalid or missing on NULL usage.
     */
    protected function _parseCondition(string $condition, mixed $value): ExpressionInterface|string
    {
        $expression = trim($condition);
        $operator = '=';

        $spaces = substr_count($expression, ' ');
        // Handle expression values that contain multiple spaces, such as
        // operators with a space in them like `field IS NOT` and
        // `field NOT LIKE`, or combinations with function expressions
        // like `CONCAT(first_name, ' ', last_name) IN`.
        if ($spaces > 1) {
            $parts = explode(' ', $expression);
            if (preg_match('/(is not|not \w+)$/i', $expression)) {
                $last = array_pop($parts);
                $second = array_pop($parts);
                $parts[] = "{$second} {$last}";
            }
            $operator = array_pop($parts);
            $expression = implode(' ', $parts);
        } elseif ($spaces == 1) {
            $parts = explode(' ', $expression, 2);
            [$expression, $operator] = $parts;
        }
        $operator = strtoupper(trim($operator));

        $type = $this->getTypeMap()->type($expression);
        $typeMultiple = (is_string($type) && str_contains($type, '[]'));
        if (in_array($operator, ['IN', 'NOT IN']) || $typeMultiple) {
            $type = $type ?: 'string';
            if (!$typeMultiple) {
                $type .= '[]';
            }
            $operator = $operator === '=' ? 'IN' : $operator;
            $operator = $operator === '!=' ? 'NOT IN' : $operator;
            $typeMultiple = true;
        }

        /** @psalm-suppress RedundantCondition */
        if ($typeMultiple) {
            $value = $value instanceof ExpressionInterface ? $value : (array)$value;
        }

        if ($operator === 'IS' && $value === null) {
            return new UnaryExpression(
                'IS NULL',
                new IdentifierExpression($expression),
                UnaryExpression::POSTFIX
            );
        }

        if ($operator === 'IS NOT' && $value === null) {
            return new UnaryExpression(
                'IS NOT NULL',
                new IdentifierExpression($expression),
                UnaryExpression::POSTFIX
            );
        }

        if ($operator === 'IS' && $value !== null) {
            $operator = '=';
        }

        if ($operator === 'IS NOT' && $value !== null) {
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
     * @param \Cake\Database\ExpressionInterface|string $field The field name to get a type for.
     * @return string|null The computed type or null, if the type is unknown.
     */
    protected function _calculateType(ExpressionInterface|string $field): ?string
    {
        $field = $field instanceof IdentifierExpression ? $field->getIdentifier() : $field;
        if (!is_string($field)) {
            return null;
        }

        return $this->getTypeMap()->type($field);
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
