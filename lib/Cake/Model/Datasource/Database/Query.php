<?php
/**
 * 
 * PHP Version 5.4
 *
 * CakePHP(tm) Tests <http://book.cakephp.org/2.0/en/development/testing.html>
 * Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The Open Group Test Suite License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2013, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/2.0/en/development/testing.html CakePHP(tm) Tests
 * @package       Cake.Test.Case.Model.Datasource.Database
 * @since         CakePHP(tm) v 3.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
namespace Cake\Model\Datasource\Database;

use Iterator;
use IteratorAggregate;
use Cake\Model\Datasource\Database\Expression\QueryExpression;
use Cake\Model\Datasource\Database\Expression\OrderByExpression;
use Cake\Model\Datasource\Database\Expression\Comparisson;
use Cake\Model\Datasource\Database\Statement\CallbackStatement;

class Query implements Expression, IteratorAggregate {

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
		'distinct' => false,
		'from' => [],
		'join' => [],
		'set' => [],
		'where' => null,
		'group' => [],
		'having' => null,
		'order' => [],
		'limit' => null,
		'offset' => null,
		'union' => []
	];

	protected $_templates = [
		'where' => ' WHERE %s',
		'group' => ' GROUP BY %s ',
		'having' => ' HAVING %s ',
		'order' => ' %s',
		'limit' => ' LIMIT %s',
		'offset' => ' OFFSET %s',
	];

	protected $_transformedQuery;

/**
 * Indicates whether internal state of this query was changed and most recent
 * results 
 *
 * @return void
 **/
	protected $_dirty = false;

/**
 * A set of callback functions to be called to alter 
 *
 * @return void
 **/
	protected $_resultDecorators = [];

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
		$this->_transformedQuery = null;
		$this->_dirty = false;

		$query = $this->_transformQuery();
		$statement = $this->_connection->prepare($query->sql(false));
		$query->_bindParams($statement);
		$statement->execute();

		return $query->_decorateResults($statement);
	}

	public function sql($transform = false) {
		$sql = '';
		$builder = function($parts, $name) use(&$sql) {
			if (!count($parts)) {
				return;
			}
			if ($parts instanceof Expression) {
				$parts = [$parts->sql()];
			}
			if (isset($this->_templates[$name])) {
				return $sql .= sprintf($this->_templates[$name], implode(', ', (array)$parts));
			}

			return $sql .= $this->{'_build' . ucFirst($name) . 'Part'}($parts, $sql);
		};

		$query = $transform ? $this->_transformQuery() : $this;
		$query->build($builder->bindTo($query));
		return $sql;
	}

	public function build($builder) {
		return $this->{'_build' . ucFirst($this->_type)}($builder);
	}

	protected function _buildSelect($builder) {
		$parts = ['select', 'from', 'join', 'where', 'group', 'having', 'order', 'limit', 'offset', 'union'];
		foreach ($parts as $name) {
			$builder($this->_parts[$name], $name);
		}
	}

	protected function _buildJoinPart($parts) {
		$joins = '';
		foreach ($parts as $join) {
			$joins .= sprintf(' %s JOIN %s %s', $join['type'], $join['table'], $join['alias']);
			if (isset($join['conditions']) && count($join['conditions'])) {
				$joins .= sprintf(' ON %s', $join['conditions']);
			} else {
				$joins .= ' ON 1 = 1';
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
			if (is_array($this->_parts['distinct'])) {
				$merge = $this->_parts['distinct'];
			}
			$on = ($overwrite) ? array_values($on) : array_merge($merge, array_values($on));
		}

		$this->_parts['distinct'] = $on;
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

		if ($this->_parts['distinct'] === true) {
			$distinct = 'DISTINCT ';
		}

		if (is_array($this->_parts['distinct'])) {
			$distinct = sprintf('DISTINCT ON (%s) ', implode(', ', $this->_parts['distinct']));
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
		foreach ($tables as $alias => $t) {
			$hasAlias = is_string($alias);
			if (!is_array($t)) {
				$t = array('table' => $t, 'conditions' => $this->newExpr());
			}
			if (!($t['conditions']) instanceof Expression) {
				$t['conditions'] = $this->newExpr()->add($t['conditions'], $types);
			}

			$joins[] = $t + ['type' => 'INNER', 'alias' => $hasAlias ? $alias : null];
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
		if ($overwrite || !$this->_parts['order']) {
			$this->_parts['order'] = new OrderByExpression;
		}
		$this->_conjugate('order', $clause, '', []);
		return $this;
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

	public function union($query, $overwrite = false) {
		if ($overwrite) {
			$this->_parts['union'] = [];
		}
		$this->_parts['union'][] = $query;
		$this->_dirty = true;
		return $this;
	}

	protected function _buildUnionPart($parts) {
		$parts = array_map(function($p) {
			$p =(string)$p;
			return $p[0] === '(' ? trim($p, '()') : $p;
		}, $parts);
		return sprintf("\nUNION %s", implode("\nUNION ", $parts));
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

	public function clause($name) {
		return $this->_parts[$name];
	}

	public function decorateResults($callback, $overwrite = false) {
		if ($overwrite) {
			$this->_resultDecorators = [];
		}
		$this->_resultDecorators[] = $callback;

		return $this;
	}

	protected function _decorateResults($statement) {
		foreach ($this->_resultDecorators as $f) {
			$statement = new CallbackStatement($statement, $this->connection()->driver(), $f);
		}
		return $statement;
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

			if ($expression instanceof Comparisson) {
				if ($expression->getValue() instanceof self) {
					$expression->getValue()->_bindParams($statement);
				}
			}

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

		$this->_transformQuery()->build($binder);
	}

/**
 * Returns a query object as returned by the connection object as a result of
 * transforming this query instance to conform to any dialect specifics
 *
 * @return void
 **/
	protected function _transformQuery() {
		if (isset($this->_transformedQuery) && !$this->_dirty) {
			return $this->_transformedQuery;
		}
		// TODO: Should Query actually get the driver or just let the connection decide where
		// to get the query translator?
		$translator = $this->connection()->driver()->queryTranslator($this->_type);
		return $this->_transformedQuery = $translator($this);
	}

/**
 * Returns string representation of this query (complete SQL statement)
 *
 * @return string
 **/
	public function __toString() {
		return sprintf('(%s)', $this->sql());
	}

}
