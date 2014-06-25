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
namespace Cake\ORM;

use Cake\Collection\CollectionTrait;
use Cake\Database\Exception;
use Cake\Database\Type;
use \Countable;
use \Iterator;
use \JsonSerializable;
use \Serializable;

/**
 * Represents the results obtained after executing a query for an specific table
 * This object is responsible for correctly nesting result keys reported from
 * the query, casting each field to the correct type and executing the extra
 * queries required for eager loading external associations.
 *
 */
class ResultSet implements Countable, Iterator, Serializable, JsonSerializable {

	use CollectionTrait;

/**
 * Original query from where results were generated
 *
 * @var Query
 */
	protected $_query;

/**
 * Database statement holding the results
 *
 * @var \Cake\Database\StatementInterface
 */
	protected $_statement;

/**
 * Points to the next record number that should be fetched
 *
 * @var int
 */
	protected $_index = 0;

/**
 * Last record fetched from the statement
 *
 * @var array
 */
	protected $_current;

/**
 * Default table instance
 *
 * @var \Cake\ORM\Table
 */
	protected $_defaultTable;

/**
 * List of associations that should be eager loaded
 *
 * @var array
 */
	protected $_associationMap = [];

/**
 * Map of fields that are fetched from the statement with
 * their type and the table they belong to
 *
 * @var string
 */
	protected $_map;

/**
 * Results that have been fetched or hydrated into the results.
 *
 * @var array
 */
	protected $_results = [];

/**
 * Whether to hydrate results into objects or not
 *
 * @var bool
 */
	protected $_hydrate = true;

/**
 * The fully namespaced name of the class to use for hydrating results
 *
 * @var string
 */
	protected $_entityClass;

/**
 * Whether or not to buffer results fetched from the statement
 *
 * @var bool
 */
	protected $_useBuffering = true;

/**
 * Holds the count of records in this result set
 *
 * @var int
 */
	protected $_count;

/**
 * Constructor
 *
 * @param \Cake\ORM\Query $query Query from where results come
 * @param \Cake\Database\StatementInterface $statement
 */
	public function __construct($query, $statement) {
		$repository = $query->repository();
		$this->_query = $query;
		$this->_statement = $statement;
		$this->_defaultTable = $this->_query->repository();
		$this->_calculateAssociationMap();
		$this->_hydrate = $this->_query->hydrate();
		$this->_entityClass = $repository->entityClass();
		$this->_useBuffering = $query->bufferResults();

		if ($statement) {
			$this->count();
		}
	}

/**
 * Returns the current record in the result iterator
 *
 * Part of Iterator interface.
 *
 * @return array|object
 */
	public function current() {
		return $this->_current;
	}

/**
 * Returns the key of the current record in the iterator
 *
 * Part of Iterator interface.
 *
 * @return int
 */
	public function key() {
		return $this->_index;
	}

/**
 * Advances the iterator pointer to the next record
 *
 * Part of Iterator interface.
 *
 * @return void
 */
	public function next() {
		$this->_index++;
	}

/**
 * Rewinds a ResultSet.
 *
 * Part of Iterator interface.
 *
 * @throws \Cake\Database\Exception
 * @return void
 */
	public function rewind() {
		if ($this->_index == 0) {
			return;
		}

		if (!$this->_useBuffering) {
			$msg = 'You cannot rewind an un-buffered ResultSet. Use Query::bufferResults() to get a buffered ResultSet.';
			throw new Exception($msg);
		}

		$this->_index = 0;
	}

/**
 * Whether there are more results to be fetched from the iterator
 *
 * Part of Iterator interface.
 *
 * @return bool
 */
	public function valid() {
		if (isset($this->_results[$this->_index])) {
			$this->_current = $this->_results[$this->_index];
			return true;
		}

		$this->_current = $this->_fetchResult();
		$valid = $this->_current !== false;
		$hasNext = $this->_index < $this->_count;

		if ($this->_statement && !($valid && $hasNext)) {
			$this->_statement->closeCursor();
		}

		if ($valid) {
			$this->_bufferResult($this->_current);
		}

		return $valid;
	}

/**
 * Serializes a resultset.
 *
 * Part of Serializable interface.
 *
 * @return string Serialized object
 */
	public function serialize() {
		while ($this->valid()) {
			$this->next();
		}
		return serialize($this->_results);
	}

/**
 * Unserializes a resultset.
 *
 * Part of Serializable interface.
 *
 * @param string $serialized Serialized object
 */
	public function unserialize($serialized) {
		$this->_results = unserialize($serialized);
	}

/**
 * Returns the first result in this set and blocks the set so that no other
 * results can be fetched.
 *
 * When using serialized results, the index will be incremented past the
 * end of the results simulating the behavior when the result set is backed
 * by a statement.
 *
 * @return array|object
 */
	public function first() {
		if (isset($this->_results[0])) {
			return $this->_results[0];
		}

		if ($this->valid()) {
			if ($this->_statement) {
				$this->_statement->closeCursor();
			}
			if (!$this->_statement && $this->_results) {
				$this->_index = count($this->_results);
			}
			return $this->_current;
		}
		return null;
	}

/**
 * Gives the number of rows in the result set.
 *
 * Part of the Countable interface.
 *
 * @return int
 */
	public function count() {
		if ($this->_count !== null) {
			return $this->_count;
		}
		if ($this->_statement) {
			return $this->_count = $this->_statement->rowCount();
		}
		return $this->_count = count($this->_results);
	}

/**
 * Calculates the list of associations that should get eager loaded
 * when fetching each record
 *
 * @return void
 */
	protected function _calculateAssociationMap() {
		$contain = $this->_query->eagerLoader()->normalized($this->_defaultTable);
		if (!$contain) {
			return;
		}

		$map = [];
		$visitor = function($level) use (&$visitor, &$map) {
			foreach ($level as $assoc => $meta) {
				$map[$meta['aliasPath']] = [
					'alias' => $assoc,
					'instance' => $meta['instance'],
					'canBeJoined' => $meta['canBeJoined'],
					'entityClass' => $meta['instance']->target()->entityClass(),
					'nestKey' => $meta['canBeJoined'] ? $assoc : $meta['aliasPath']
				];
				if ($meta['canBeJoined'] && !empty($meta['associations'])) {
					$visitor($meta['associations']);
				}
			}
		};
		$visitor($contain, []);
		$this->_associationMap = $map;
	}

/**
 * Helper function to fetch the next result from the statement or
 * seeded results.
 *
 * @return mixed
 */
	protected function _fetchResult() {
		if (!$this->_statement) {
			return false;
		}

		$row = $this->_statement->fetch('assoc');
		if ($row === false) {
			return $row;
		}
		return $this->_groupResult($row);
	}

/**
 * Correctly nests results keys including those coming from associations
 *
 * @param mixed $row Array containing columns and values or false if there is no results
 * @return array Results
 */
	protected function _groupResult($row) {
		$defaultAlias = $this->_defaultTable->alias();
		$results = $presentAliases = [];
		foreach ($row as $key => $value) {
			$table = $defaultAlias;
			$field = $key;

			if (isset($this->_associationMap[$key])) {
				$results[$key] = $value;
				continue;
			}

			if (empty($this->_map[$key])) {
				$parts = explode('__', $key);
				if (count($parts) > 1) {
					$this->_map[$key] = $parts;
				}
			}

			if (!empty($this->_map[$key])) {
				list($table, $field) = $this->_map[$key];
			}

			$presentAliases[$table] = true;
			$results[$table][$field] = $value;
		}

		unset($presentAliases[$defaultAlias]);
		$results[$defaultAlias] = $this->_castValues(
			$this->_defaultTable,
			$results[$defaultAlias]
		);

		$options = [
			'useSetters' => false,
			'markClean' => true,
			'markNew' => false,
			'guard' => false
		];

		foreach (array_reverse($this->_associationMap) as $assoc) {
			$alias = $assoc['nestKey'];
			$instance = $assoc['instance'];

			if (!isset($results[$alias])) {
				$results = $instance->defaultRowValue($results, $assoc['canBeJoined']);
				continue;
			}

			$target = $instance->target();
			$options['source'] = $target->alias();
			unset($presentAliases[$alias]);

			if ($assoc['canBeJoined']) {
				$results[$alias] = $this->_castValues($target, $results[$alias]);
			}

			if ($this->_hydrate && $assoc['canBeJoined']) {
				$entity = new $assoc['entityClass']($results[$alias], $options);
				$entity->clean();
				$results[$alias] = $entity;
			}

			$results = $instance->transformRow($results, $alias, $assoc['canBeJoined']);
		}

		foreach ($presentAliases as $alias => $present) {
			if (!isset($results[$alias])) {
				continue;
			}
			$results[$defaultAlias][$alias] = $results[$alias];
		}

		$options['source'] = $defaultAlias;
		$results = $results[$defaultAlias];
		if ($this->_hydrate && !($results instanceof Entity)) {
			$results = new $this->_entityClass($results, $options);
		}

		return $results;
	}

/**
 * Casts all values from a row brought from a table to the correct
 * PHP type.
 *
 * @param Table $table
 * @param array $values
 * @return array
 */
	protected function _castValues($table, $values) {
		$alias = $table->alias();
		$driver = $this->_query->connection()->driver();
		if (empty($this->types[$alias])) {
			$schema = $table->schema();
			foreach ($schema->columns() as $col) {
				$this->types[$alias][$col] = Type::build($schema->columnType($col));
			}
		}

		foreach ($values as $field => $value) {
			if (!isset($this->types[$alias][$field])) {
				continue;
			}
			$values[$field] = $this->types[$alias][$field]->toPHP($value, $driver);
		}

		return $values;
	}

/**
 * Conditionally buffer the passed result
 *
 * @param array $result the result fetch from the database
 * @return void
 */
	protected function _bufferResult($result) {
		if ($this->_useBuffering) {
			$this->_results[] = $result;
		}
	}

/**
 * Returns an array that can be used to describe the internal state of this
 * object.
 *
 * @return array
 */
	public function __debugInfo() {
		return [
			'query' => $this->_query,
			'items' => $this->toArray(),
		];
	}

}
