<?php

namespace Cake\Model\Datasource\Database\Expression;
use \Countable;

class QueryExpression implements Countable {

	protected $_conjunction;

	protected $_conditions = [];

	protected $_bindings = [];

	protected $_identifier;

	public function __construct($conditions = [], $types = [], $conjunction = 'AND') {
		$this->_conjunction = strtoupper($conjunction);
		$this->_identifier = substr(spl_object_hash($this), 7, 9);
		if (!empty($conditions)) {
			$this->add($conditions, $types);
		}
	}

	public function type($conjunction = null) {
		if ($conjunction === null) {
			return $this->_conjunction;
		}

		$this->_conjunction = strtoupper($conjunction);
		return $this;
	}

	public function add($conditions, $types = []) {
		if (is_string($conditions)) {
			$this->_conditions[] = $conditions;
			return $this;
		}

		if ($conditions instanceof QueryExpression && count($conditions) > 0) {
			$this->_conditions[] = $conditions;
			return $this;
		}

		$this->_addConditions($conditions, $types);
		return $this;
	}

	public function equals($field, $value, $type = null) {
		return $this->add([$field => $value], $type ? [$field => $type] : []);
	}

/**
 * Associates a query placeholder to a value and a type for next execution
 *
 * @param string|integer $token placeholder to be replaced with quoted version
 *of $value
 * @param mixed $value the value to be bound
 * @param string|integer $type the mapped type name, used for casting when sending
 * to database
 * @return string placeholder name or question mark to be used in the query string
 */
	public function bind($token, $value, $type) {
		$param = $token;
		$number = count($this->_bindings);

		if (is_numeric($token)) {
			$param = '?';
		} else if ($param[0] !== ':') {
			$param = sprintf(':c%s%s', $this->_identifier, $number);
		}

		$this->_bindings[$number] = compact('value', 'type') + [
			'placeholder' => substr($param, 1)
		];
		return $param;
	}

/**
 * Returns all values bound to this expression object at this nesting level.
 * Subexpression bound values will nit be returned with this function.
 *
 * @return array
 **/
	public function bindings() {
		return $this->_bindings;
	}

	public function count() {
		return count($this->_conditions);
	}

	public function __toString() {
		return $this->sql();
	}

	public function sql() {
		$conjunction = $this->_conjunction;
		return '(' . implode(" $conjunction ", $this->_conditions) . ')';
	}

	public function traverse($callable) {
		foreach ($this->_conditions as $c) {
			if ($c instanceof QueryExpression) {
				$c->traverse($callable);
			}
		}
		$callable($this);
	}

	protected function _addConditions(array $conditions, array $types) {
		$operators = array('and', 'or', 'not', 'xor');

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

			if ($c instanceof QueryExpression && count($c) > 0) {
				$this->_conditions[] = $c;
				continue;
			}

			if (!$numericKey) {
				$this->_conditions[] = $this->_parseCondition($k, $c, $types);
			}
		}
	}

	protected function _parseCondition($field, $value, $types) {
		$operator = '=';
		$expression = $field;
		$parts = explode(' ', trim($field), 2);

		if (count($parts) > 1) {
			list($expression, $operator) = $parts;
		}

		$type = isset($types[$expression]) ? $types[$expression] : null;
		return sprintf('%s %s %s', $expression,  $operator, $this->bind($field, $value, $type));
	}

}
