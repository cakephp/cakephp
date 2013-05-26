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

use Cake\ORM\Query;
use Cake\Utility\Inflector;

/**
 * Represents a type of association that that needs to be recovered by performing
 * a extra query.
 */
trait ExternalAssociationTrait {

/**
 * Order in which target records should be returned
 *
 * @var mixed
 */
	protected $_sort;

/**
 * Whether this association can be expressed directly in a query join
 *
 * @param array $options custom options key that could alter the return value
 * @return boolean if the 'filtering' key in $option is true then this function
 * will return true, false otherwise
 */
	public function canBeJoined($options = []) {
		return !empty($options['filtering']);
	}

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
 * set of records in the source table.
 *
 * @param array $options
 * @return \Closure
 */
	public abstract function eagerLoader(array $options);

/**
 * Returns a single or multiple conditions to be appended to the generated join
 * clause for getting the results on the target table.
 *
 * @param array $options list of options passed to attachTo method
 * @return string|array
 */
	protected function _joinCondition(array $options) {
		return sprintf('%s.%s = %s.%s',
				$this->_sourceTable->alias(),
				$this->_sourceTable->primaryKey(),
				$this->_targetTable->alias(),
				$options['foreignKey']
			);
	}

/**
 * Returns a callable to be used for each row in a query result set
 * for injecting the eager loaded rows
 *
 * @param Cake\ORM\Query $fetchQuery the Query used to fetch results
 * @param array $resultMap an array with the foreignKey as keys and
 * the corresponding target table results as value.
 * @return \Closure
 */
	protected function _resultInjector($fetchQuery, $resultMap) {
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

}
