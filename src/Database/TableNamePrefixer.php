<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         3.0.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Database;

use Cake\Database\Expression\FieldInterface;
use Cake\Database\Expression\IdentifierExpression;
use Cake\Database\Expression\OrderByExpression;
use Cake\Database\Expression\QueryExpression;
use Cake\Database\Expression\TableNameExpression;
use Cake\Database\Expression\UnaryExpression;

/**
 * Contains all the logic related to prefixing table names in a Query object
 *
 * @internal
 *
 */
class TableNamePrefixer {

/**
 * The Query instance used of the current query
 *
 * @var \Cake\Database\Query
 */
	protected $_query;

/**
 * The ValueBinder instance used in the current query
 *
 * @var \Cake\Database\ValueBinder
 */
	protected $_binder;

/**
 * List of the query parts to prefix
 *
 * @var array
 */
	protected $_partsToPrefix = ['select', 'from', 'join', 'group', 'update', 'insert'];

/**
 * Iterates over each of the clauses in a query looking for table names and
 * prefix them
 *
 * @param \Cake\Database\Query $query The query to have its table names prefixed
 * @return \Cake\Database\Query
 */
	public function prefix(Query $query) {
		$this->_binder = $query->valueBinder();
		$this->_query = $query;
		$query->valueBinder(false);

		$this->_prefixParts();

		$query->traverseExpressions([$this, 'prefixExpression']);
		$query->valueBinder($this->_binder);
		return $query;
	}

/**
 * Prefixes table name or field name inside Expression objects
 *
 * @param \Cake\Database\ExpressionInterface $expression The expression object to traverse and prefix
 * @return void
 */
	public function prefixExpression($expression) {
		if ($expression instanceof FieldInterface) {
			$this->_prefixFieldInterface($expression);
			return;
		}

		if ($expression instanceof OrderByExpression) {
			$this->_prefixOrderByExpression($expression);
			return;
		}

		if ($expression instanceof IdentifierExpression) {
			$this->_prefixIdentifierExpression($expression);
		}

		if ($expression instanceof QueryExpression) {
			$this->_prefixQueryExpression($expression);
			return;
		}
	}

/**
 * Prefix Expressions implementing the FieldInterface
 *
 * @param \Cake\Database\Expression\FieldInterface $expression The expression to prefix
 * @return void
 */
	protected function _prefixFieldInterface(FieldInterface $expression) {
		$field = $expression->getField();

		if (is_string($field) && strpos($field, '.') !== false && $this->_query->hasTableName($field) === true) {
			$field = $this->_query->connection()->fullFieldName($field, $this->_query->tablesNames);
			$expression->setField($field);
		}
	}

/**
 * Prefix OrderByExpression object
 *
 * @param \Cake\Database\Expression\OrderByExpression $expression The expression to prefix
 * @return void
 */
	protected function _prefixOrderByExpression(OrderByExpression $expression) {
		$query = $this->_query;
		$binder = $this->_binder;
		$expression->iterateParts(function ($condition, &$key) use ($query, $binder) {
			if ($query->hasTableName($key) === true && $query->connection()->isTableNamePrefixed($key) === false) {
				$key = $query->connection()->fullFieldName($key, $query->tablesNames);

				if ($key instanceof ExpressionInterface) {
					$key = $key->sql($binder);
				}
			}
			return $condition;
		});
	}

/**
 * Prefix IdentifierExpression object
 *
 * @param \Cake\Database\Expression\IdentifierExpression $expression The expression to prefix
 * @return void
 */
	protected function _prefixIdentifierExpression(IdentifierExpression $expression) {
		$identifier = $expression->getIdentifier();

		if (is_string($identifier) && strpos($identifier, '.') !== false && $this->_query->hasTableName($identifier) === true) {
			$identifier = $this->_query->connection()->fullFieldName($identifier, $this->_query->tablesNames);
			$expression->setIdentifier($identifier);
		}
	}

/**
 * Prefix QueryExpression object
 *
 * @param \Cake\Database\Expression\QueryExpression $expression The expression to prefix
 * @return void
 */
	protected function _prefixQueryExpression(QueryExpression $expression) {
		$query = $this->_query;
		$expression->iterateParts(function ($condition, $key) use ($query) {
			if (is_string($condition) && $this->_query->hasTableName($condition)) {
				$condition = new TableNameExpression(
					$condition,
					$query->connection()->getPrefix(),
					[
						'snippet' => true, 'tablesNames' => $query->tablesNames,
						'quoteStrings' => $this->_query->connection()->driver()->getQuoteStrings()
					]
				);
			}
			return $condition;
		});
	}

/**
 * Quotes all identifiers in each of the clauses of a query
 *
 * @return void
 */
	protected function _prefixParts() {
		foreach ($this->_partsToPrefix as $part) {
			$contents = $this->_query->clause($part);

			if (empty($contents)) {
				continue;
			}

			$methodName = '_prefix' . ucfirst($part) . 'Parts';
			if (method_exists($this, $methodName)) {
				$this->{$methodName}($contents);
			}
		}
	}

/**
 * Prefixes the table name in the "update" clause
 *
 * @param array $parts the parts of the query to prefix
 * @return array
 */
	protected function _prefixInsertParts($parts) {
		$parts = $this->_query->connection()->fullTableName($parts);
		$this->_query->into($parts[0]);
	}

/**
 * Prefixes the table name in the "update" clause
 *
 * @param array $parts the parts of the query to prefix
 * @return array
 */
	protected function _prefixUpdateParts($parts) {
		$parts = $this->_query->connection()->fullTableName($parts);
		$this->_query->update($parts[0]);
	}

/**
 * Prefixes the table name in clause of the Query having a basic forms
 *
 * @param array $parts the parts of the query to prefix
 * @return array
 */
	protected function _prefixFromParts($parts) {
		$parts = $this->_query->connection()->fullTableName($parts);
		$this->_query->from($parts, true);
	}

/**
 * Prefixes the table names for the "select" clause
 *
 * @param array $parts The parts of the query to prefix
 *
 * @return void
 */
	protected function _prefixSelectParts($parts) {
		if (!empty($parts)) {
			foreach ($parts as $alias => $part) {
				if ($this->_query->hasTableName($part) === true) {
					$parts[$alias] = $this->_query->connection()->fullFieldName($part, $this->_query->tablesNames);
				}
			}

			$this->_query->select($parts, true);
		}
	}

/**
 * Prefixes the table names for the "join" clause
 *
 * @param array $parts The parts of the query to prefix
 *
 * @return void
 */
	protected function _prefixJoinParts($parts) {
		if (!empty($parts)) {
			foreach ($parts as $alias => $join) {
				$join['table'] = $this->_query->connection()->fullTableName($join['table']);

				$parts[$alias] = $join;
			}

			$this->_query->join($parts, [], true);
		}
	}

/**
 * Prefixes the table names for the "group" clause
 *
 * @param array $parts The parts of the query to prefix
 *
 * @return void
 */
	protected function _prefixGroupParts($parts) {
		if (!empty($parts)) {
			foreach ($parts as $key => $part) {
				if ($this->_query->hasTableName($part) === true) {
					$parts[$key] = $this->_query->connection()->fullFieldName($part, $this->_query->tablesNames);
				}
			}
		}

		$this->_query->group($parts, true);
	}

}