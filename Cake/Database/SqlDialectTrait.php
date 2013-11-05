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
use Cake\Database\Expression\OrderByExpression;
use Cake\Database\Expression\FieldExpression;

trait SqlDialectTrait {

/**
 * Quotes a database identifier (a column name, table name, etc..) to
 * be used safely in queries without the risk of using reserver words
 *
 * @param string $identifier
 * @return string
 */
	public function quoteIdentifier($identifier) {
		$identifier = trim($identifier);

		if ($identifier === '*') {
			return '*';
		}

		if (preg_match('/^[\w-]+(?:\.[^ \*]*)*$/', $identifier)) { // string, string.string
			if (strpos($identifier, '.') === false) { // string
				return $this->_startQuote . $identifier . $this->_endQuote;
			}
			$items = explode('.', $identifier);
			return $this->_startQuote . implode($this->_endQuote . '.' . $this->_startQuote, $items) . $this->_endQuote;
		}

		if (preg_match('/^[\w-]+\.\*$/', $identifier)) { // string.*
			return $this->_startQuote . str_replace('.*', $this->_endQuote . '.*', $identifier);
		}

		if (preg_match('/^([\w-]+)\((.*)\)$/', $identifier, $matches)) { // Functions
			return $matches[1] . '(' . $this->quoteIdentifier($matches[2]) . ')';
		}

		if (preg_match('/^([\w-]+(\.[\w-]+|\(.*\))*)\s+AS\s*([\w-]+)$/i', $identifier, $matches)) {
			return preg_replace(
				'/\s{2,}/', ' ', $this->quoteIdentifier($matches[1]) . ' AS  ' . $this->quoteIdentifier($matches[3])
			);
		}

		if (preg_match('/^[\w-_\s]*[\w-_]+/', $identifier)) {
			return $this->_startQuote . $identifier . $this->_endQuote;
		}

		return $identifier;
	}

/**
 * Returns a callable function that will be used to transform a passed Query object.
 * This function, in turn, will return an instance of a Query object that has been
 * transformed to accommodate any specificities of the SQL dialect in use.
 *
 * @param string $type the type of query to be transformed
 * (select, insert, update, delete)
 * @return callable
 */
	public function queryTranslator($type) {
		return function($query) use ($type) {
			if ($this->autoQuoting()) {
				$binder = $query->valueBinder();
				$query->valueBinder(false);
				$query = $this->_quoteQueryIdentifiers($type, $query);
				$query->valueBinder($binder);
			}

			$query = $this->{'_' . $type . 'QueryTranslator'}($query);
			$translators = $this->_expressionTranslators();
			if (!$translators) {
				return $query;
			}

			$query->traverseExpressions(function($expression) use ($translators) {
				foreach ($translators as $class => $method) {
					if ($expression instanceof $class) {
						$this->{$method}($expression);
					}
				}
			});
			return $query;
		};
	}

/**
 * Returns an associative array of methods that will transform Expression
 * objects to conform with the specific SQL dialect. Keys are class names
 * and values a method in this class.
 *
 * @return array
 */
	protected function _expressionTranslators() {
		if ($this->autoQuoting()) {
			$namespace = 'Cake\Database\Expression';
			return [
				$namespace . '\Comparison' => '_quoteComparison',
				$namespace . '\OrderByExpression' => '_quoteOrderBy',
				$namespace . '\FieldExpression' => '_quoteField'
			];
		}

		return [];
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
			$expression->field($this->quoteIdentifier($field));
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
				$field = $this->quoteIdentifier($field);
			}
			return $part;
		});
	}

/**
 * Quotes identifiers in "order by" expression objects
 *
 * @param \Cake\Database\Expression\FieldExpression $expression
 * @return void
 */
	protected function _quoteField(FieldExpression $expression) {
		$expression->setField($this->quoteIdentifier($expression->getField()));
	}

/**
 * Apply translation steps to select queries.
 *
 * @param Query $query The query to translate
 * @return Query The modified query
 */
	protected function _selectQueryTranslator($query) {
		return $this->_transformDistinct($query);
	}

/**
 * Returns the passed query after rewriting the DISTINCT clause, so that drivers
 * that do not support the "ON" part can provide the actual way it should be done
 *
 * @param Query $query The query to be transformed
 * @return Query
 */
	protected function _transformDistinct($query) {
		if (is_array($query->clause('distinct'))) {
			$query->group($query->clause('distinct'), true);
			$query->distinct(false);
		}
		return $query;
	}

/**
 * Iterates over each of the clauses in a query looking for identifiers and
 * quotes them
 *
 * @param string $type the type of query to be quoted
 * @param Query $query The query to have its identifiers quoted
 * @return Query
 */
	protected function _quoteQueryIdentifiers($type, $query) {
		if ($type === 'insert') {
			return $this->_quoteInsertIdentifiers($query);
		}

		$quoter = function($part) use ($query) {
			$result = [];
			foreach ((array)$query->clause($part) as $alias => $value) {
				$value = !is_string($value) ? $value : $this->quoteIdentifier($value);
				$alias = is_numeric($alias) ? $alias : $this->quoteIdentifier($alias);
				$result[$alias] = $value;
			}
			if ($result) {
				$query->{$part}($result, true);
			}
		};

		if (is_array($query->clause('distinct'))) {
			$quoter('distinct', $query);
		}

		$quoter('select');
		$quoter('from');
		$quoter('group');

		$result = [];
		foreach ((array)$query->clause('join') as $value) {
			$alias =  empty($value['alias']) ? null : $this->quoteIdentifier($value['alias']);
			$value['alias'] = $alias;

			if (is_string($value['table'])) {
				$value['table'] = $this->quoteIdentifier($value['table']);
			}

			$result[$alias] = $value;
		}

		if ($result) {
			$query->join($result, [], true);
		}

		return $query;
	}

/**
 * Quotes the table name and columns for an insert query
 *
 * @param Query $query
 * @return Query
 */
	protected function _quoteInsertIdentifiers($query) {
		list($table, $columns) = $query->clause('insert');
		$table = $this->quoteIdentifier($table);
		foreach ($columns as &$column) {
			$column = $this->quoteIdentifier($column);
		}
		$query->insert($table, $columns);
		return $query;
	}

/**
 * Apply translation steps to delete queries.
 *
 * @param Query $query The query to translate
 * @return Query The modified query
 */
	protected function _deleteQueryTranslator($query) {
		return $query;
	}

/**
 * Apply translation steps to update queries.
 *
 * @param Query $query The query to translate
 * @return Query The modified query
 */
	protected function _updateQueryTranslator($query) {
		return $query;
	}

/**
 * Apply translation steps to insert queries.
 *
 * @param Query $query The query to translate
 * @return Query The modified query
 */
	protected function _insertQueryTranslator($query) {
		return $query;
	}

/**
 * Returns a SQL snippet for creating a new transaction savepoint
 *
 * @param string save point name
 * @return string
 */
	public function savePointSQL($name) {
		return 'SAVEPOINT LEVEL' . $name;
	}

/**
 * Returns a SQL snippet for releasing a previously created save point
 *
 * @param string save point name
 * @return string
 */
	public function releaseSavePointSQL($name) {
		return 'RELEASE SAVEPOINT LEVEL' . $name;
	}

/**
 * Returns a SQL snippet for rollbacking a previously created save point
 *
 * @param string save point name
 * @return string
 */
	public function rollbackSavePointSQL($name) {
		return 'ROLLBACK TO SAVEPOINT LEVEL' . $name;
	}

}
