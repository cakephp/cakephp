<?php
namespace Cake\Model\Datasource\Database;

class Expression {

	/**
	 * @var Expression[]
	 */
	protected $_expressions = [];

	/**
	 * @var string
	 */
	protected $_expression;

	/**
	 * @var string
	 */
	protected $_type;

	/**
	 * @var string
	 */
	protected $_field;

	/**
	 * @var mixed
	 */
	protected $_value;

	/**
	 * @var string
	 */
	protected $_operator;

	/**
	 * @param string $type
	 * @param null $field
	 * @param null $value
	 */
	public function __construct($type = null, $field = null, $value = null, $operator = null) {
		$this->_type = $type;
		$this->_field = $field;
		$this->_value = $value;
		$this->_operator = $operator;
	}

	public static function create() {
		return new self;
	}

	public function isNest() {
		return (bool)count($this->_expressions);
	}

	/**
	 * @param null $type
	 * @return string
	 */
	public function type($type = null) {
		if ($type === null) {
			return $this->_type;
		}
		$this->_type = $type;
	}

	/**
	 * @return null|string
	 */
	public function field() {
		return $this->_field;
	}

	/**
	 * @return mixed|null
	 */
	public function value() {
		return $this->_value;
	}

	/**
	 * @return null|string
	 */
	public function operator() {
		return $this->_operator;
	}

	/**
	 * @return array|Expression[]
	 */
	public function expressions() {
		return $this->_expressions;
	}

	/**
	 * @return string
	 */
	public function expression($connection) {
		$this->_evaluate($connection);
		return $this->_expression;
	}

	public function where($field, $value = null) {
		return $this->_addExpression('AND', $field, $value);
	}

	public function andWhere($field, $value = null) {
		return $this->where($field, $value);
	}

	public function orWhere($field, $value = null) {
		return $this->_addExpression('OR', $field, $value);
	}

	public function andNot($field, $value = null) {
		return $this->_addExpression('AND NOT', $field, $value);
	}

	public function orNot($field, $value = null) {
		return $this->_addExpression('OR NOT', $field, $value);
	}

	public function lt($field, $value = null) {
		return $this->_addExpression('AND', $field, $value, '<');
	}

	public function andLt($field, $value = null) {
		return $this->lt($field, $value);
	}

	public function orLt($field, $value = null) {
		return $this->_addExpression('OR', $field, $value, '<');
	}

	public function gt($field, $value = null) {
		return $this->_addExpression('AND', $field, $value, '>');
	}

	public function lte($field, $value = null) {
		return $this->_addExpression('AND', $field, $value, '<=');
	}

	public function gte($field, $value = null) {
		return $this->_addExpression('AND', $field, $value, '>=');
	}

	public function like($field, $value) {
		return $this->_addExpression('AND', $field, $value, 'LIKE');
	}

	public function andLike($field, $value = null) {
		return $this->like($field, $value);
	}

	public function orLike($field, $value = null) {
		return $this->_addExpression('OR', $field, $value, 'LIKE');
	}

	public function andIsNull($field) {
		return $this->_addExpression('AND', $field, null, 'IS');
	}

	public function orIsNull($field) {
		return $this->_addExpression('OR', $field, null, 'IS');
	}

	/**
	 * @return string
	 */
	public function sql($connection) {
		$sql = '';
		foreach ($this->_expressions as $expression) {
			if ($expression->isNest()) {
				$open = $close = '';
				if (count($expression->expressions()) > 1) {
					$open = '(';
					$close = ')';
				}
				$sql .= sprintf('%s %s%s%s', $expression->type(), $open, $expression->sql($connection), $close);
				continue;
			}
			$sql .= $expression->expression($connection) . ' ';
		}
		return trim($sql);
	}

	protected function _addExpression($type, $field, $value, $operator = '=') {
		$expression = ($field instanceof self) ? $field : new self($type, $field, $value, $operator);
		$expression->type(count($this->_expressions) > 0 ? $type : '');
		$this->_expressions[] = $expression;
		return $this;
	}

	/**
	 * @param $connection Connection
	 */
	protected function _evaluate($connection) {
		if ($this->isNest()) {
			return;
		}
		// TODO: escaping, etc
		$value = $connection->quote($this->_value);
		$this->_expression = trim(sprintf('%s %s %s %s', $this->_type, $this->_field, $this->_operator, $value));
	}

}