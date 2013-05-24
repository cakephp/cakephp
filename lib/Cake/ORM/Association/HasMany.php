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
namespace Cake\ORM\Association;

use Cake\ORM\Association;
use Cake\ORM\Query;
use Cake\Utility\Inflector;

/**
 * Represents an N - 1 relationship. Where the target side of the relationship
 * will have one or multiple records per each one in the source side.
 */
class HasMany extends Association {

/**
 * Whether this association can be expressed directly in a query join
 *
 * @var boolean
 */
	protected $_canBeJoined = false;

/**
 * The type of join to be used when adding the association to a query
 *
 * @var string
 */
	protected $_joinType = 'INNER';

/**
 * Order in which target records should be returned
 *
 * @var mixed
 */
	protected $_sort;

/**
 * The strategy name to be used to fetch associated records. Some association
 * types might not implement but one strategy to fetch records.
 *
 * @var string
 */
	protected $_strategy = parent::STRATEGY_SUBQUERY;

/**
 * Sets the name of the field representing the foreign key to the target table.
 * If no parameters are passed current field is returned
 *
 * @param string $key the key to be used to link both tables together
 * @return string
 */
	public function foreignKey($key = null) {
		if ($key === null) {
			if ($this->_foreignKey === null) {
				$this->_foreignKey =  Inflector::underscore($this->source()->alias()) . '_id';
			}
			return $this->_foreignKey;
		}
		return parent::foreignKey($key);
	}

/**
 * Sets the sort order in which target records should be returned.
 * If no arguments are passed the currently configured value is returned
 *
 * @return string
 */
	function sort($sort = null) {
		if ($sort !== null) {
			$this->_sort = $sort;
		}
		return $this->_sort;
	}

/**
 * Not implemented
 *
 * @return boolean false
 */
	public function attachTo(Query $query, array $options = []) {
		return false;
	}

/**
 * Returns true if the eager loading process will require a set of parent table's
 * primary keys in order to use them as a filter in the finder query.
 *
 * @return boolean true if a list of keys will be required
 */
	public function requiresKeys($options = []) {
		$strategy = isset($options['strategy']) ? $options['strategy'] : $this->strategy();
		return $strategy !== parent::STRATEGY_SUBQUERY;
	}

/**
 * Eager loads a list of records in the target table that are related to another
 * set of records in the source table. Source records can specified in two ways:
 * first one is by passing a Query object setup to find on the source table and
 * the other way is by explicitly passing an array of primary key values from
 * the source table.
 *
 * The required way of passing related source records is controlled by "strategy"
 * By default the subquery strategy is used, which requires a query on the source
 * When using the select strategy, the list of primary keys will be used.
 *
 * Returns a closure that should be run for each record returned in an specific
 * Query. This callable will be responsible for injecting the fields that are
 * related to each specific passed row.
 *
 * Options array accept the following keys:
 *
 * - query: Query object setup to find the source table records
 * - keys: List of primary key values from the source table
 * - foreignKey: The name of the field used to relate both tables
 * - conditions: List of conditions to be passed to the query where() method
 * - sort: The direction in which the records should be returned
 * - fields: List of fields to select from the target table
 * - contain: List of related tables to eager load associated to the target table
 * - strategy: The name of strategy to use for finding target table records
 *
 * @param array $options
 * @return \Closure
 */
	public function eagerLoader(array $options) {
		$options += [
			'foreignKey' => $this->foreignKey(),
			'conditions' => [],
			'sort' => $this->sort(),
			'strategy' => $this->strategy()
		];
		$fetchQuery = $this->_buildQuery($options);
		$resultMap = [];
		$key = $options['foreignKey'];
		foreach ($fetchQuery->execute() as $result) {
			$resultMap[$result[$key]][] = $result;
		}

		$source = $this->source();
		$sourceKey = key($fetchQuery->aliasField(
			$source->primaryKey(),
			$source->alias()
		));
		$alias = $this->target()->alias();
		$targetKey = key($fetchQuery->aliasField($this->property(), $source->alias()));
		return function($row) use ($alias, $resultMap, $sourceKey, $targetKey) {
			if (isset($resultMap[$row[$sourceKey]])) {
				$row[$targetKey] = $resultMap[$row[$sourceKey]];
			}
			return $row;
		};
	}

/**
 * Auxiliary function to construct a new Query object to return all the records
 * in the target table that are associated to those specified in $options from
 * the source table
 *
 * @param array $options options accepted by eagerLoader()
 * @return Cake\ORM\Query
 */
	protected function _buildQuery($options) {
		$target = $this->target();
		$alias = $target->alias();
		$fetchQuery = $target->find('all');
		$options['conditions'] = array_merge($this->conditions(), $options['conditions']);
		$key = sprintf('%s.%s', $alias, $options['foreignKey']);

		$filter = ($options['strategy'] == parent::STRATEGY_SUBQUERY) ?
			$this->_buildSubquery($options['query'], $key) : $options['keys'];

		$fetchQuery
			->where($options['conditions'])
			->andWhere([$key . ' in' => $filter]);

		if (!empty($options['fields'])) {
			$fields = $fetchQuery->aliasFields($options['fields'], $alias);
			$required = $alias . '.' . $options['foreignKey'];
			if (!in_array($required, $fields)) {
				throw new \InvalidArgumentException(
					sprintf('You are required to select the "%s" field', $required)
				);
			}
			$fetchQuery->select($fields);
		}

		if (!empty($options['sort'])) {
			$fetchQuery->order($options['sort']);
		}

		if (!empty($options['contain'])) {
			$fetchQuery->contain($options['contain']);
		}

		return $fetchQuery;
	}

/**
 * Parse extra options passed in the constructor.
 * @param array $opts original list of options passed in constructor
 *
 * @return void
 */
	protected function _options(array $opts) {
		if (isset($opts['sort'])) {
			$this->sort($opts['sort']);
		}
	}

/**
 * Builds a query to be used as a condition for filtering records in in the
 * target table, it is constructed by cloning the original query that was used
 * to load records in the source table.
 *
 * @param Cake\ORM\Query $query the original query used to load source records
 * @param strong $foreignKey the field to be selected in the query
 * @return Cake\ORM\Query
 */
	protected function _buildSubquery($query, $foreignKey) {
		$filterQuery = clone $query;
		$filterQuery->contain([], true);
		$joins = $filterQuery->join();
		foreach ($joins as $i => $join) {
			if (strtolower($join['type']) !== 'inner') {
				unset($joins[$i]);
			}
		}
		$filterQuery->join($joins, [], true);
		return $filterQuery->select($foreignKey, true);
	}

}
