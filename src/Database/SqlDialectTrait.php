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

/**
 * Sql dialect trait
 */
trait SqlDialectTrait {

/**
 * Quotes a database identifier (a column name, table name, etc..) to
 * be used safely in queries without the risk of using reserved words
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
				$query = (new IdentifierQuoter($this))->quote($query);
			}

			$query = $this->{'_' . $type . 'QueryTranslator'}($query);
			$translators = $this->_expressionTranslators();
			if (!$translators) {
				return $query;
			}

			$query->traverseExpressions(function($expression) use ($translators, $query) {
				foreach ($translators as $class => $method) {
					if ($expression instanceof $class) {
						$this->{$method}($expression, $query);
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
		return [];
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
 * @param string $name save point name
 * @return string
 */
	public function savePointSQL($name) {
		return 'SAVEPOINT LEVEL' . $name;
	}

/**
 * Returns a SQL snippet for releasing a previously created save point
 *
 * @param string $name save point name
 * @return string
 */
	public function releaseSavePointSQL($name) {
		return 'RELEASE SAVEPOINT LEVEL' . $name;
	}

/**
 * Returns a SQL snippet for rollbacking a previously created save point
 *
 * @param string $name save point name
 * @return string
 */
	public function rollbackSavePointSQL($name) {
		return 'ROLLBACK TO SAVEPOINT LEVEL' . $name;
	}

}
