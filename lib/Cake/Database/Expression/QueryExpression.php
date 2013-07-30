<?php
/**
 * PHP Version 5.4
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       Cake.Model
 * @since         CakePHP(tm) v 3.0.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
namespace Cake\Database\Expression;

use Cake\Database\ExpressionInterface;
use Cake\Database\Query;
use \Countable;

/**
 * Represents a SQL Query expression. Internally it stores a tree of
 * expressions that can be compiled by converting this object to string
 * and will contain a correctly parenthesized and nested expression.
 *
 * This class also deals with internally binding values to parts of the expression,
 * used for condition comparisons. When a string representation of an instance
 * of this class is built any value bound will be expressed as a placeholder,
 * thus this class exposes methods for getting the actual bound values for each of
 * them so they can be used in statements or replaced directly.
 */
class QueryExpression implements ExpressionInterface, Countable {

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
 * Array containing a list of bound values to the conditions on this
 * object. Each array entry is another array structure containing the actual
 * bound value, its type and the placeholder it is bound to.
 *
 * @var array
 */
	protected $_bindings = [];

/**
 * An unique string that identifies this object. It is used to create unique
 * placeholders.
 *
 * @car string
 */
	protected $_identifier;

/**
 * A counter of the number of parameters bound in this expression object
 *
 * @var integer
 */
	protected $_bindingsCount = 0;

/**
 * Whether to process placeholders that are meant to bind multiple other
 * placeholders out of an array of values. This value is automatically
 * set to true when an "IN" condition is used or when a value is bound
 * with an array type.
 *
 * @var boolean
 */
	protected $_replaceArrayParams = false;

/**
 * Constructor. A new expression object can be created without any params and
 * be built dynamically. Otherwise it is possible to pass an array of conditions
 * containing either a tree-like array structure to be parsed and/or other
 * expression objects. Optionally, you can set the conjunction keyword to be used
 * for joining each part of this level of the expression tree.
 *
 * @param array $conditions tree-like array structure containing all the conditions
 * to be added or nested inside this expression object.
 * @param array types associative array of types to be associated with the values
 * passed in $conditions.
 * @param string $conjunction the glue that will join all the string conditions at this
 * level of the expression tree. For example "AND", "OR", "XOR"...
 * @see QueryExpression::add() for more details on $conditions and $types
 * @return void
 */
	public function __construct($conditions = [], $types = [], $conjunction = 'AND') {
		$this->type(strtoupper($conjunction));
		$this->id(substr(spl_object_hash($this), 7, 9));
		if (!empty($conditions)) {
			$this->add($conditions, $types);
		}
	}

/**
 * Changes the conjunction for the conditions at this level of the expression tree.
 * If called with no arguments it will return the currently configured value.
 *
 * @param string $conjunction value to be used for joining conditions. If null it
 * will not set any value, but return the currently stored one
 * @return string
 */
	public function type($conjunction = null) {
		if ($conjunction === null) {
			return $this->_conjunction;
		}

		$this->_conjunction = strtoupper($conjunction);
		return $this;
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
 * @param string|array|QueryExpression $conditions single or multiple conditions to
 * be added. When using and array and the key is 'OR' or 'AND' a new expression
 * object will be created with that conjunction and internal array value passed
 * as conditions.
 * @param array $types associative array of fields pointing to the type of the
 * values that are being passed. Used for correctly binding values to statements.
 * @see Cake\Database\Query::where() for examples on conditions
 * @return QueryExpression
 */
	public function add($conditions, $types = []) {
		if (is_string($conditions)) {
			$this->_conditions[] = $conditions;
			return $this;
		}

		if ($conditions instanceof self && count($conditions) > 0) {
			$this->_conditions[] = $conditions;
			return $this;
		}

		$this->_addConditions($conditions, $types);
		return $this;
	}

/**
 * Adds a new condition to the expression object in the form "field = value".
 *
 * @param string $field database field to be compared against value
 * @param mixed $value the value to be bound to $field for comparison
 * @param string $type the type name for $value as configured using the Type map.
 * If it is suffixed with "[]" and the value is an array then multiple placeholders
 * will be created, one per each value in the array.
 * @return QueryExpression
 */
	public function eq($field, $value, $type = null) {
		return $this->add([$field => $value], $type ? [$field => $type] : []);
	}

/**
 * Adds a new condition to the expression object in the form "field != value".
 *
 * @param string $field database field to be compared against value
 * @param mixed $value the value to be bound to $field for comparison
 * @param string $type the type name for $value as configured using the Type map.
 * If it is suffixed with "[]" and the value is an array then multiple placeholders
 * will be created, one per each value in the array.
 * @return QueryExpression
 */
	public function notEq($field, $value, $type = null) {
		return $this->add([$field . ' !=' => $value], $type ? [$field => $type] : []);
	}

/**
 * Adds a new condition to the expression object in the form "field > value".
 *
 * @param string $field database field to be compared against value
 * @param mixed $value the value to be bound to $field for comparison
 * @param string $type the type name for $value as configured using the Type map.
 * @return QueryExpression
 */
	public function gt($field, $value, $type = null) {
		return $this->add([$field . ' >' => $value], $type ? [$field => $type] : []);
	}

/**
 * Adds a new condition to the expression object in the form "field < value".
 *
 * @param string $field database field to be compared against value
 * @param mixed $value the value to be bound to $field for comparison
 * @param string $type the type name for $value as configured using the Type map.
 * @return QueryExpression
 */
	public function lt($field, $value, $type = null) {
		return $this->add([$field . ' <' => $value], $type ? [$field => $type] : []);
	}

/**
 * Adds a new condition to the expression object in the form "field >= value".
 *
 * @param string $field database field to be compared against value
 * @param mixed $value the value to be bound to $field for comparison
 * @param string $type the type name for $value as configured using the Type map.
 * @return QueryExpression
 */
	public function gte($field, $value, $type = null) {
		return $this->add([$field . ' >=' => $value], $type ? [$field => $type] : []);
	}

/**
 * Adds a new condition to the expression object in the form "field <= value".
 *
 * @param string $field database field to be compared against value
 * @param mixed $value the value to be bound to $field for comparison
 * @param string $type the type name for $value as configured using the Type map.
 * @return QueryExpression
 */
	public function lte($field, $value, $type = null) {
		return $this->add([$field . ' <=' => $value], $type ? [$field => $type] : []);
	}

/**
 * Adds a new condition to the expression object in the form "field IS NULL".
 *
 * @param string $field database field to be tested for null
 * @return QueryExpression
 */
	public function isNull($field) {
		return $this->add($field . ' IS NULL');
	}

/**
 * Adds a new condition to the expression object in the form "field IS NOT NULL".
 *
 * @param string $field database field to be tested for not null
 * @return QueryExpression
 */
	public function isNotNull($field) {
		return $this->add($field . ' IS NOT NULL');
	}

/**
 * Adds a new condition to the expression object in the form "field LIKE value".
 *
 * @param string $field database field to be compared against value
 * @param mixed $value the value to be bound to $field for comparison
 * @param string $type the type name for $value as configured using the Type map.
 * @return QueryExpression
 */
	public function like($field, $value, $type = null) {
		return $this->add([$field . ' LIKE' => $value], $type ? [$field => $type] : []);
	}

/**
 * Adds a new condition to the expression object in the form "field NOT LIKE value".
 *
 * @param string $field database field to be compared against value
 * @param mixed $value the value to be bound to $field for comparison
 * @param string $type the type name for $value as configured using the Type map.
 * @return QueryExpression
 */
	public function notLike($field, $value, $type = null) {
		return $this->add([$field . ' NOT LIKE' => $value], $type ? [$field => $type] : []);
	}

/**
 * Adds a new condition to the expression object in the form
 * "field IN (value1, value2)".
 *
 * @param string $field database field to be compared against value
 * @param array $value the value to be bound to $field for comparison
 * @param string $type the type name for $value as configured using the Type map.
 * @return QueryExpression
 */
	public function in($field, $values, $type = null) {
		return $this->add([$field . ' IN' => $values], $type ? [$field => $type] : []);
	}

/**
 * Adds a new condition to the expression object in the form
 * "field NOT IN (value1, value2)".
 *
 * @param string $field database field to be compared against value
 * @param array $value the value to be bound to $field for comparison
 * @param string $type the type name for $value as configured using the Type map.
 * @return QueryExpression
 */
	public function notIn($field, $values, $type = null) {
		return $this->add([$field . ' NOT IN' => $values], $type ? [$field => $type] : []);
	}

	public function and_($conditions, $types = []) {
		if (is_callable($conditions)) {
			return $conditions(new self);
		}
		return new self($conditions, $types);
	}

	public function or_($conditions, $types = []) {
		if (is_callable($conditions)) {
			return $conditions(new self([], [], 'OR'));
		}
		return new self($conditions, $types, 'OR');
	}

/**
 * Adds a new set of conditions to this level of the tree and negates
 * the final result by prepending a NOT, it will look like
 * "NOT ( (condition1) AND (conditions2) )" conjunction depends on the one
 * currently configured for this object.
 *
 * @param string|array|QueryExpression $conditions to be added and negated
 * @param array $types associative array of fields pointing to the type of the
 * values that are being passed. Used for correctly binding values to statements.
 * @return QueryExpression
 */
	public function not($conditions, $types = []) {
		return $this->add(['NOT' => $conditions], $types);
	}

/**
 * Associates a query placeholder to a value and a type for this level of the
 * expressions tree.
 *
 * If type is expressed as "atype[]" (note braces) then it will cause the
 * placeholder to be re-written dynamically so if the value is an array, it
 * will create as many placeholders as values are in it. For example "string[]"
 * will create several placeholders of type string.
 *
 * @param string|integer $token placeholder to be replaced with quoted version
 * of $value
 * @param mixed $value the value to be bound
 * @param string|integer $type the mapped type name, used for casting when sending
 * to database
 * @return string placeholder name or question mark to be used in the query string
 */
	public function bind($param, $value, $type) {
		$number = $this->_bindingsCount;
		$this->_bindings[$number] = compact('value', 'type') + [
			'placeholder' => substr($param, 1)
		];
		if (strpos($type, '[]') !== false) {
			$this->_replaceArrayParams = true;
		}
		return $this;
	}

/**
 * Creates a unique placeholder name if the token provided does not start with ":"
 * otherwise, it will return the same string and internally increment the number
 * of placeholders generated by this object.
 *
 * @param string $token string from which the placeholder will be derived from,
 * if it starts with a colon, then the same string is returned
 * @return string to be used as a placeholder in a query expression
 */
	public function placeholder($token) {
		$param = $token;
		$number = $this->_bindingsCount++;

		if ($param[0] !== ':') {
			$param = sprintf(':c%s%s', $this->_identifier, $number);
		}

		return $param;
	}

/**
 * Returns all values bound to this expression object at this nesting level.
 * Subexpression bound values will not be returned with this function.
 *
 * @return array
 */
	public function bindings() {
		return $this->_bindings;
	}

/**
 * Returns the number of internal conditions that are stored in this expression.
 * Useful to determine if this expression object is void or it will generate
 * a non-empty string when compiled
 *
 * @return integer
 */
	public function count() {
		return count($this->_conditions);
	}

/**
 * Returns the string representation of this object so that it can be used in a
 * SQL query. Note that values condition values are not included in the string,
 * in their place placeholders are put and can be replaced by the quoted values
 * accordingly.
 *
 * @return string
 */
	public function sql() {
		if ($this->_replaceArrayParams) {
			$this->_replaceArrays();
		}
		$conjunction = $this->_conjunction;
		$template = ($this->count() === 1) ? '%s' : '(%s)';
		return sprintf($template, implode(" $conjunction ", $this->_conditions));
	}

/**
 * Traverses the tree structure of this query expression by executing a callback
 * function for each of the conditions that are included in this object.
 * Useful for compiling the final expression, or doing
 * introspection in the structure.
 *
 * Callback function receives as only argument an instance of a QueryExpression
 *
 * @param callable $callable
 * @return void
 */
	public function traverse(callable $callable) {
		foreach ($this->_conditions as $c) {
			if ($c instanceof self) {
				$callable($c);
				$c->traverse($callable);
			}
		}
	}

/**
 * Executes a callable function for each of the parts that form this expression
 * Callable function is required to return a value, which will the one with
 * which the currently visited part will be replaced. If the callable function
 * returns null then the part will be discarded completely from this expression
 *
 * @param callable $callable
 * @return QueryExpression
 */
	public function iterateParts(callable $callable) {
		$parts = [];
		foreach ($this->_conditions as $c) {
			$part = $callable($c);
			if ($part !== null) {
				$parts[] = $part;
			}
		}
		$this->_conditions = $parts;

		return $this;
	}

/**
 * Sets the unique identifier string for this object, which is used for generating
 * placeholders. If called with no arguments it will return the currently defined
 * identifier.
 *
 * @param string $identifier the string to be used as prefix for generating
 * placeholders. If null current identifier is returned
 * @return string|QueryExpression
 */
	public function id($identifier = null) {
		if ($identifier === null) {
			return $this->_identifier;
		}
		$this->_identifier = $identifier;
		return $this;
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
	protected function _addConditions(array $conditions, array $types) {
		$operators = ['and', 'or', 'xor'];

		foreach ($conditions as $k => $c) {
			$numericKey = is_numeric($k);

			if ($numericKey && empty($c)) {
				continue;
			}

			if ($numericKey && is_string($c)) {
				$this->_conditions[] = $c;
				continue;
			}

			if ($numericKey && is_array($c) || in_array(strtolower($k), $operators)) {
				$this->_conditions[] = new self($c, $types, $numericKey ? 'AND' : $k);
				continue;
			}

			if (strtolower($k) === 'not') {
				$this->_conditions[] = new UnaryExpression(new self($c, $types), [], 'NOT');
				continue;
			}

			if ($c instanceof self && count($c) > 0) {
				$this->_conditions[] = $c;
				continue;
			}

			if (!$numericKey) {
				$this->_conditions[] = $this->_parseCondition($k, $c, $types);
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
 * $param array $types List of types where the field can be found so the value
 * can be converted accordingly.
 * @return string|QueryExpression
 */
	protected function _parseCondition($field, $value, $types) {
		$operator = '=';
		$expression = $field;
		$parts = explode(' ', trim($field), 2);

		if (count($parts) > 1) {
			list($expression, $operator) = $parts;
		}

		$type = isset($types[$expression]) ? $types[$expression] : null;
		$multi = false;

		$typeMultiple = strpos($type, '[]') !== false;
		if (in_array(strtolower(trim($operator)), ['in', 'not in']) || $typeMultiple) {
			$type = $type ?: 'string';
			$type .= $typeMultiple ? null : '[]';
			$operator = $operator == '=' ? 'IN' : $operator;
			$operator = $operator == '!=' ? 'NOT IN' : $operator;
			$multi = true;
		}

		if ($value instanceof ExpressionInterface || $multi === false) {
			return new Comparison($expression, $value, $type, $operator);
		}

		$placeholder = $this->_bindValue($field, $value, $type);
		return sprintf('%s %s (%s)', $expression, $operator, $placeholder);
	}

/**
 * Helper function used to bind a value to a field and return the placeholder
 * generated for it.
 *
 * @param string $field field to generate placeholder for
 * @param mixed $value the value to be bound to the field
 * @param string $type the type that will be associated to the value
 * @return string generated placeholder
 */
	protected function _bindValue($field, $value, $type) {
		$param = $this->placeholder($field);
		$this->bind($param, $value, $type);
		return $param;
	}

/**
 * Replaces placeholders that are bound for values with array types,
 * it does so by generating multiple new placeholders and joining them
 * with commas.
 *
 * @return void
 */
	protected function _replaceArrays() {
		$replacements = [];
		foreach ($this->_bindings as $n => $b) {
			if (strpos($b['type'], '[]') === false) {
				continue;
			}
			$token = ':' . $b['placeholder'];
			$replacements[$token] = $this->_bindMultiplePlaceholders(
				$b['placeholder'],
				$b['value'],
				$b['type']
			);
			unset($this->_bindings[$n]);
		}

		foreach ($this->_conditions as $k => $condition) {
			if (!is_string($condition)) {
				continue;
			}
			foreach ($replacements as $token => $r) {
				$this->_conditions[$k] = str_replace($token, $r, $condition);
			}
		}
	}

/**
 * Returns an array of placeholders that will have a bound value corresponding
 * to each value in the first argument.
 *
 * @param array $values
 * @param string $type the type to be used to bind the values
 * @return array
 */
	protected function _bindMultiplePlaceholders($field, $values, $type) {
		$type = str_replace('[]', '', $type);
		$params = [];
		foreach ($values as $value) {
			$params[] = $this->_bindValue($field, $value, $type);
		}
		return implode(', ', $params);
	}

/**
 * Returns a string representation of this object
 *
 * @return string
 */
	public function __toString() {
		return $this->sql();
	}

}
