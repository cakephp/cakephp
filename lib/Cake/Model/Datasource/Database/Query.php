<?php

namespace Cake\Model\Datasource\Database;

use Iterator, IteratorAggregate;

class Query implements IteratorAggregate  {

/**
 * Connection instance to be used to execute this query
 *
 * @var \Cake\Model\Datasource\Database\Connection
 **/
	protected $_connection;

/**
 * Type of this query (select, insert, update, delete)
 *
 * @var string
 **/
	protected $_type;

/**
 * List of SQL parts that will be used to build this query
 *
 * @var array
 **/
	protected $_parts = [
		'select' => [],
		'from' => [],
		'join' => [],
		'set' => [],
		'where' => null,
		'group' => [],
		'having' => null,
		'order' => [],
		'limit' => null,
		'offset' => null
		];

/**
 * Indicates whether internal state of this query was changed and most recent
 * results 
 *
 * @return void
 **/
	protected $_dirty = false;

/**
 * Iterator for statement results
 *
 * @var Iterator
 **/
	protected $_iterator;

	public function __construct($connection) {
		$this->connection($connection);
		$this->_parts['where'] = new Expression;
		$this->_parts['having'] = new Expression;
	}

	public function connection($connection = null) {
		if ($connection === null) {
			return $this->_connection;
		}
		$this->_connection = $connection;
		return $this;
	}

	public function execute() {
		$results = $this->_connection->execute($this->sql());
		if ($results instanceof Iterator) {
			$this->_iterator = $results;
		}
		return $results;
	}

	public function sql() {
		switch ($this->_type) {
			case 'select' :
				return $this->_buildSelect();
		}
	}

	protected function _buildSelect() {
		$statement = sprintf('SELECT %s ', implode(', ', $this->_parts['select']));

		if (!empty($this->_parts['from'])) {
			$statement .= sprintf('FROM %s ', implode(', ', $this->_parts['from']));
		}

		if (!empty($this->_parts['join'])) {
			$statement .= $this->_buildJoins();
		}

		$where = $this->_parts['where'];
		if (count($where->expressions())) {
			$statement .= sprintf(' WHERE %s', $where->sql($this->_connection));
		}

		if (!empty($this->_parts['group'])) {
			$statement .= sprintf(' GROUP BY %s', implode(', ', $this->_parts['group']));
		}

		$having = $this->_parts['having'];
		if (count($having->expressions())) {
			$statement .= sprintf(' HAVING %s', $having->sql($this->_connection));
		}

		if ($this->_parts['limit'] !== null) {
			$statement .= sprintf(' LIMIT %s, %s', $this->_parts['limit'], intval($this->_parts['offset']));
		}

		return $statement;
	}

	protected function _buildJoins() {
		$joins = '';
		foreach ($this->_parts['join'] as $join) {
			$joins .= sprintf(' %s JOIN %s %s', $join['type'], $join['table'], $join['alias']);
			if (!empty($join['conditions'])) {
				$joins .= sprintf(' ON %s', (string) $join['conditions']);
			}
		}
		return trim($joins);
	}

	public function select($fields = [], $overwrite = false) {
		if (empty($fields)) {
			return $this->_parts['select'];
		}

		if (is_string($fields)) {
			$fields = [$fields];
		}

		if ($overwrite) {
			$this->_parts['select'] = array_values($fields);
		} else {
			$this->_parts['select'] = array_merge($this->_parts['select'], array_values($fields));
		}

		$this->_dirty = true;
		$this->_type = 'select';
		return $this;
	}

	public function insert() {
		return $this;
	}

	public function update() {
		return $this;
	}

	public function delete() {
		return $this;
	}

	public function union($query) {
		return $this;
	}

	public function from($tables = [], $overwrite = false) {
		if (empty($tables)) {
			return $this->_parts['from'];
		}

		if (is_string($tables)) {
			$tables = [$tables];
		}

		if ($overwrite) {
			$this->_parts['from'] = $tables;
		} else {
			$this->_parts['from'] =  array_merge($this->_parts['from'], array_values($tables));
		}

		$this->_dirty = true;
		return $this;
	}

	public function join($tables = [], $overwrite = false) {
		if (empty($tables)) {
			return $this->_parts['join'];
		}

		if (is_string($tables) || isset($tables['table'])) {
			$tables = [$tables];
		}

		$joins = array();
		foreach ($tables as $t) {
			if (is_string($t)) {
				$t = array('table' => $t);
			}
			$joins[] = $t + ['type' => 'LEFT', 'alias' => null, 'conditions' => '1 = 1'];
		}

		if ($overwrite) {
			$this->_parts['join'] = $joins;
		} else {
			$this->_parts['join'] = array_merge($this->_parts['join'], array_values($joins));
		}

		$this->_dirty = true;
		return $this;
	}

	public function where($field, $value = null) {
		$this->_parts['where']->where($field, $value);
		return $this;
	}

	public function andWhere($field, $value = null) {
		$this->where($field, $value);
		return $this;
	}

	public function orWhere($field, $value = null) {
		$this->_parts['where']->orWhere($field, $value);
		return $this;
	}

	public function order($field, $direction = 'ASC') {
		$this->_parts['order'] += [$field => $direction];
		return $this;
	}

	public function group($field) {
		$this->_parts['group'][] = $field;
		return $this;
	}

	public function having($field, $value = null) {
		$this->_parts['having']->andWhere($field, $value);
		return $this;
	}

	public function andHaving($field, $value = null) {
		$this->having($field, $value);
		return $this;
	}

	public function orHaving($field, $value = null) {
		$this->_parts['having']->orWhere($field, $value);
		return $this;
	}

	public function limit($num = null) {
		$this->_parts['limit'] = $num;
		return $this;
	}

	public function offset($num = null) {
		$this->_parts['offset'] = $num;
		return $this;
	}

	public function distinct($on = []) {
		return $this;
	}

/**
 * Returns the type of this query (select, insert, update, delete)
 *
 * @return string
 **/
	public function type() {
		return $this->_type;
	}

/**
 * Executes this query and returns a results iterator
 *
 * @return Iterator
 **/
	public function getIterator() {
		if (empty($this->_iterator)) {
			$this->_iterator = $this->execute();
		}
		return $this->_iterator;
	}

/**
 * Returns string representation of this query (complete SQL statement)
 *
 * @return string
 **/
	public function __toString() {
		return $this->sql();
	}

}
