<?php

namespace Cake\Model\Datasource\Database\Expression;
use \Countable;

class QueryExpression implements Countable {

	protected $_conjunction;

	protected $_conditions = [];

	protected $_bindings = [];

	protected $_identifier;

	protected $_bindingsCount = 0;

	protected $_replaceArrayParams = false;


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

		if ($conditions instanceof self && count($conditions) > 0) {
			$this->_conditions[] = $conditions;
			return $this;
		}

		$this->_addConditions($conditions, $types);
		return $this;
	}

	public function eq($field, $value, $type = null) {
		return $this->add([$field => $value], $type ? [$field => $type] : []);
	}

	public function notEq($field, $value, $type = null) {
		return $this->add([$field . ' !=' => $value], $type ? [$field => $type] : []);
	}

	public function gt($field, $value, $type = null) {
		return $this->add([$field . ' >' => $value], $type ? [$field => $type] : []);
	}

	public function lt($field, $value, $type = null) {
		return $this->add([$field . ' <' => $value], $type ? [$field => $type] : []);
	}

	public function gte($field, $value, $type = null) {
		return $this->add([$field . ' >=' => $value], $type ? [$field => $type] : []);
	}

	public function lte($field, $value, $type = null) {
		return $this->add([$field . ' <=' => $value], $type ? [$field => $type] : []);
	}

	public function isNull($field) {
		return $this->add($field . ' IS NULL');
	}

	public function isNotNull($field) {
		return $this->add($field . ' IS NOT NULL');
	}

	public function like($field, $value, $type = null) {
		return $this->add([$field . ' LIKE' => $value], $type ? [$field => $type] : []);
	}

	public function notLike($field, $value, $type = null) {
		return $this->add([$field . ' NOT LIKE' => $value], $type ? [$field => $type] : []);
	}

	public function in($field, $values, $type = null) {
		return $this->add([$field . ' IN' => $values], $type ? [$field => $type] : []);
	}

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

	public function not($conditions, $types = []) {
		return $this->add(['NOT' => $conditions], $types);
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
		$number = $this->_bindingsCount++;

		if (is_numeric($token)) {
			$param = '?';
		} else if ($param[0] !== ':') {
			$param = sprintf(':c%s%s', $this->_identifier, $number);
		}

		if (strpos($type, '[]') !== false) {
			$param = sprintf(':array%d', $number);
			$type = str_replace('[]', '', $type);
		}

		$this->_bindings[$number] = compact('value', 'type', 'token') + [
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
		if ($this->_replaceArrayParams) {
			$this->_replaceArrays();
		}
		$conjunction = $this->_conjunction;
		$template = ($this->count() === 1) ? '%s' : '(%s)';
		return sprintf($template, implode(" $conjunction ", $this->_conditions));
	}

	public function traverse($callable) {
		foreach ($this->_conditions as $c) {
			if ($c instanceof self) {
				$c->traverse($callable);
			}
		}
		$callable($this);
	}

	protected function _addConditions(array $conditions, array $types) {
		$operators = array('and', 'or', 'xor');

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

	protected function _parseCondition($field, $value, $types) {
		$operator = '=';
		$expression = $field;
		$parts = explode(' ', trim($field), 2);

		if (count($parts) > 1) {
			list($expression, $operator) = $parts;
		}

		$type = isset($types[$expression]) ? $types[$expression] : null;
		$template = '%s %s %s';

		if (in_array(strtolower(trim($operator)), ['in', 'not in'])) {
			$type = $type ?: 'string';
			$type .= strpos($type, '[]') === false ? '[]' : null;
			$template = '%s %s (%s)';
			$this->_replaceArrayParams = true;
		}

		return sprintf($template, $expression,  $operator, $this->bind($field, $value, $type));
	}

	protected function _replaceArrays() {
		foreach ($this->_conditions as $k => $condition) {
			if (!is_string($condition)) {
				continue;
			}
			$condition = preg_replace_callback('/(:array(\d+))/', function($match) {
				$params = [];
				$binding = $this->_bindings[$match[2]];
				foreach ($this->_bindings[$match[2]]['value'] as $value) {
					$params[] = $this->bind($binding['token'], $value, $binding['type']);
				}
				unset($this->_bindings[$match[2]]);
				return implode(', ', $params);
			}, $condition);
			$this->_conditions[$k] = $condition;
		}
	}

}
