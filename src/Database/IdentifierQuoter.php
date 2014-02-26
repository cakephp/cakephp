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
 * @since         CakePHP(tm) v 3.0.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
namespace Cake\Database;

use Cake\Database\Expression\Comparison;
use Cake\Database\Expression\IdentifierExpression;
use Cake\Database\Expression\OrderByExpression;

/**
 * Contains all the logic related to quoting identifiers in a Query object
 *
 */
class IdentifierQuoter {

/**
 * The driver instance used to do the identifier quoting
 *
 * @var \Cake\Database\Driver
 */
	protected $_driver;

/**
 * Constructor
 *
 * @param \Cake\Database\Driver The driver instance used to do the identifier quoting
 */
	public function __construct(Driver $driver) {
		$this->_driver = $driver;
	}

/**
 * Iterates over each of the clauses in a query looking for identifiers and
 * quotes them
 *
 * @param Query $query The query to have its identifiers quoted
 * @return Query
 */
	public function quote(Query $query) {
		$binder = $query->valueBinder();
		$query->valueBinder(false);

		if ($query->type() === 'insert') {
			$this->_quoteInsert($query);
		} else {
			$this->_quoteParts($query);
		}

		$query->traverseExpressions(function($expression) {
			if ($expression instanceof Comparison) {
				$this->_quoteComparison($expression);
				return;
			}

			if ($expression instanceof OrderByExpression) {
				$this->_quoteOrderBy($expression);
				return;
			}

			if ($expression instanceof IdentifierExpression) {
				$this->_quoteIndetifierExpression($expression);
				return;
			}
		});

		$query->valueBinder($binder);
		return $query;
	}

/**
 * Quotes all identifiers in each of the clauses of a query
 *
 * @param Query
 * @return void
 */
	protected function _quoteParts($query) {
		foreach (['distinct', 'select', 'from', 'group'] as $part) {
			$contents = $query->clause($part);

			if (!is_array($contents)) {
				continue;
			}

			$result = $this->_basicQuoter($contents);
			if ($result) {
				$query->{$part}($result, true);
			}
		}

		$joins = $query->clause('join');
		if ($joins) {
			$joins = $this->_quoteJoins($joins);
			$query->join($joins, [], true);
		}
	}

/**
 * A generic identifier quoting function used for various parts of the query
 *
 * @param array $part the part of the query to quote
 * @return array
 */
	protected function _basicQuoter($part) {
		$result = [];
		foreach ((array)$part as $alias => $value) {
			$value = !is_string($value) ? $value : $this->_driver->quoteIdentifier($value);
			$alias = is_numeric($alias) ? $alias : $this->_driver->quoteIdentifier($alias);
			$result[$alias] = $value;
		}
		return $result;
	}

/**
 * Quotes both the table and alias for an array of joins as stored in a Query
 * object
 *
 * @param array $joins
 * @return array
 */
	protected function _quoteJoins($joins) {
		$result = [];
		foreach ($joins as $value) {
			$alias = null;
			if (!empty($value['alias'])) {
				$alias = $this->_driver->quoteIdentifier($value['alias']);
				$value['alias'] = $alias;
			}

			if (is_string($value['table'])) {
				$value['table'] = $this->_driver->quoteIdentifier($value['table']);
			}

			$result[$alias] = $value;
		}

		return $result;
	}

/**
 * Quotes the table name and columns for an insert query
 *
 * @param Query $query
 * @return void
 */
	protected function _quoteInsert($query) {
		list($table, $columns) = $query->clause('insert');
		$table = $this->_driver->quoteIdentifier($table);
		foreach ($columns as &$column) {
			if (is_string($column)) {
				$column = $this->_driver->quoteIdentifier($column);
			}
		}
		$query->insert($columns)->into($table);
	}

/**
 * Quotes identifiers in comparison expression objects
 *
 * @param \Cake\Database\Expression\Comparison $expression
 * @return void
 */
	protected function _quoteComparison(Comparison $expression) {
		$field = $expression->getField();
		if (is_string($field)) {
			$expression->field($this->_driver->quoteIdentifier($field));
		}
	}

/**
 * Quotes identifiers in "order by" expression objects
 *
 * @param \Cake\Database\Expression\OrderByExpression $expression
 * @return void
 */
	protected function _quoteOrderBy(OrderByExpression $expression) {
		$expression->iterateParts(function($part, &$field) {
			if (is_string($field)) {
				$field = $this->_driver->quoteIdentifier($field);
			}
			return $part;
		});
	}

/**
 * Quotes identifiers in "order by" expression objects
 *
 * @param \Cake\Database\Expression\IdentifierExpression $expression
 * @return void
 */
	protected function _quoteIndetifierExpression(IdentifierExpression $expression) {
		$expression->setIdentifier(
			$this->_driver->quoteIdentifier($expression->getIdentifier())
		);
	}

}
