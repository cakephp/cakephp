<?php
namespace Cake\Model\Datasource\Database;

use Iterator;
use IteratorAggregate;
use Cake\Model\Datasource\Database\Expression\QueryExpression;

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

	protected $_distinct = false;

	protected $_templates = [
		'where' => ' WHERE %s',
		'group' => ' GROUP BY %s ',
		'having' => ' HAVING %s ',
		'limit' => ' LIMIT %s',
		'offset' => ' OFFSET %s'
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
	}

	public function connection($connection = null) {
		if ($connection === null) {
			return $this->_connection;
		}
		$this->_connection = $connection;
		return $this;
	}

	public function execute() {
		$statement = $this->_connection->prepare($this->sql());
		$this->_bindParams($statement);
		$statement->execute();
		return $statement;
	}

	public function sql() {
		$sql = '';
		$builder = function($parts, $name) use(&$sql) {
			if (!count($parts)) {
				return;
			}
			if ($parts instanceof QueryExpression || $parts instanceof self) {
				$parts = [$parts->sql()];
			}
			if (isset($this->_templates[$name])) {
				return $sql .= sprintf($this->_templates[$name], implode(', ', (array)$parts));
			}

			return $sql .= $this->{'_build' . ucFirst($name) . 'Part'}($parts, $sql);
		};

		$this->build($builder);
		return $sql;
	}

	public function build($builder) {
		return $this->{'_build' . ucFirst($this->_type)}($builder);
	}

	protected function _buildSelect($builder) {
		$parts = ['select', 'from', 'join', 'where', 'group', 'having', 'order', 'limit', 'offset'];
		foreach ($parts as $part) {
			$builder($this->_parts[$part], $part);
		}
	}

	protected function _buildJoinPart($parts) {
		$joins = '';
		foreach ($parts as $join) {
			$joins .= sprintf(' %s JOIN %s %s', $join['type'], $join['table'], $join['alias']);
			if (isset($join['conditions']) && count($join['conditions'])) {
				$joins .= sprintf(' ON %s', $join['conditions']);
			}
		}
		return $joins;
	}

	public function select($fields = [], $overwrite = false) {
		if ($fields === null) {
			return $this->_parts['select'];
		}

		if (!is_array($fields)) {
			$fields = [$fields];
		}

		if ($overwrite) {
			$this->_parts['select'] = $fields;
		} else {
			$this->_parts['select'] = array_merge($this->_parts['select'], $fields);
		}

		$this->_dirty = true;
		$this->_type = 'select';
		return $this;
	}

	public function distinct($on = [], $overwrite = false) {
		if ($on === []) {
			$on = true;
		}

		if (is_array($on)) {
			$merge = [];
			if (is_array($this->_distinct)) {
				$merge = $this->_distinct;
			}
			$on = ($overwrite) ? array_values($on) : array_merge($merge, array_values($on));
		}

		$this->_distinct = $on;
		$this->_dirty = true;
		return $this;
	}

	protected function _buildSelectPart($parts) {
		$select = 'SELECT %s%s';
		$distinct = null;
		$normalized = [];
		foreach ($parts as $k => $p) {
			if (!is_numeric($k)) {
				$p = $p . ' AS ' . $k;
			}
			$normalized[] = $p;
		}

		if ($this->_distinct === true) {
			$distinct = 'DISTINCT ';
		}

		if (is_array($this->_distinct)) {
			//todo: ask driver if it cannot support distinct on
			if (true) {
				$this->group($this->_distinct, true);
			} else {
				$distinct = sprintf('DISTINCT ON (%s) ', implode(', ', $this->_distinct));
			}
		}

		return sprintf($select, $distinct, implode(', ', $normalized));
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
			$this->_parts['from'] =  array_merge($this->_parts['from'], $tables);
		}

		$this->_dirty = true;
		return $this;
	}

	public function _buildFromPart($parts) {
		$select = ' FROM %s';
		$normalized = [];
		foreach ($parts as $k => $p) {
			if (!is_numeric($k)) {
				$p = $p . ' ' . $k;
			}
			$normalized[] = $p;
		}
		return sprintf($select, implode(', ', $normalized));
	}


	public function join($tables = null, $types = [], $overwrite = false) {
		if ($tables === null) {
			return $this->_parts['join'];
		}

		if (is_string($tables) || isset($tables['table'])) {
			$tables = [$tables];
		}

		$joins = array();
		foreach ($tables as $t) {
			if (is_string($t)) {
				$t = array('table' => $t, 'conditions' => $this->newExpr());
			}
			if (!($t['conditions']) instanceof QueryExpression) {
				$t['conditions'] = $this->newExpr()->add($t['conditions'], $types);
			}
			$joins[] = $t + ['type' => 'INNER', 'alias' => null];
		}

		if ($overwrite) {
			$this->_parts['join'] = $joins;
		} else {
			$this->_parts['join'] = array_merge($this->_parts['join'], array_values($joins));
		}

		$this->_dirty = true;
		return $this;
	}

	public function where($conditions = null, $types = [], $overwrite = false) {
		if ($overwrite) {
			$this->_parts['where'] = $this->newExpr();
		}
		$this->_conjugate('where', $conditions, 'AND', $types);
		return $this;
	}

	public function andWhere($conditions, $types = []) {
		$this->_conjugate('where', $conditions, 'AND', $types);
		return $this;
	}

	public function orWhere($conditions, $types = []) {
		$this->_conjugate('where', $conditions, 'OR', $types);
		return $this;
	}

	public function order($clause, $overwrite = false) {
		$order = $this->_parts['order'];
		if ($overwrite) {
			$order = [];
		}

		if (!is_array($clause)) {
			$clause = [$clause];
		}

		$order = array_merge($order, $clause);
		$this->_parts['order'] = $order;
		$this->_dirty = true;
		return $this;
	}

	protected function _buildOrderPart($parts) {
		$order = [];
		foreach ($parts as $k => $direction) {
			$order[] = is_numeric($k) ? $direction : sprintf('%s %s', $k, $direction);
		}
		return sprintf (' ORDER BY %s', implode(', ', $order));
	}

	public function group($fields, $overwrite = false) {
		if ($overwrite) {
			$this->_parts['group'] = [];
		}

		if (!is_array($fields)) {
			$fields = [$fields];
		}

		$this->_parts['group'] = array_merge($this->_parts['group'], array_values($fields));
		$this->_dirty = true;
		return $this;
	}

	public function having($conditions = null, $types = [], $overwrite = false) {
		if ($overwrite) {
			$this->_parts['having'] = $this->newExpr();
		}
		$this->_conjugate('having', $conditions, 'AND', $types);
		return $this;
	}

	public function andHaving($conditions, $types = []) {
		$this->_conjugate('having', $conditions, 'AND', $types);
		return $this;
	}

	public function orHaving($conditions, $types = []) {
		$this->_conjugate('having', $conditions, 'OR', $types);
		return $this;
	}

	public function limit($num) {
		$this->_parts['limit'] = $num;
		return $this;
	}

	public function offset($num) {
		$this->_parts['offset'] = $num;
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

	public function newExpr() {
		return new QueryExpression;
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

	protected function _conjugate($part, $append, $conjunction, $types) {
		$expression = $this->_parts[$part] ?: $this->newExpr();

		if (is_callable($append)) {
			$append = $append($this->newExpr(), $this);
		}

		if ($expression->type() === $conjunction) {
			$expression->add($append, $types);
		} else {
			$expression = $this->newExpr()
				->type($conjunction)
				->add([$append, $expression], $types);
		}

		$this->_parts[$part] = $expression;
		$this->_dirty = true;
	}

	protected function _bindParams($statement) {
		$visitor = function($expression) use($statement) {
			$params = $types = [];
			foreach ($expression->bindings() as $b) {
				$params[$b['placeholder']] = $b['value'];
				$types[$b['placeholder']] = $b['type'];
			}
			$statement->bind($params, $types);
		};
		$binder = function($expression, $name) use($statement, $visitor, &$binder) {
			if (is_array($expression)) {
				foreach ($expression as $e) {
					$binder($e, $name);
				}
			}

			if ($expression instanceof self) {
				return $expression->build($binder);
			}

			if (!($expression instanceof QueryExpression)) {
				return;
			}

			//Visit all expressions and subexpressions to get every bound value
			$expression->traverse($visitor);
		};

		$this->build($binder);
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
