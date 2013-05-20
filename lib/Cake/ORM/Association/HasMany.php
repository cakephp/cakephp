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

	public function attachTo(Query $query, array $options = []) {
		return false;
	}

	public function requiresKeys($options = []) {
		$strategy = isset($options['strategy']) ? $options['strategy'] : $this->strategy();
		return $strategy !== parent::STRATEGY_SUBQUERY;
	}

	public function eagerLoader($parentQuery, $options = [], $parentKeys = null) {
		$options += [
			'foreignKey' => $this->foreignKey(),
			'conditions' => [],
			'sort' => $this->sort(),
			'strategy' => $this->strategy()
		];
		$fetchQuery = $this->_buildQuery($parentQuery, $options, $parentKeys);
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

	protected function _buildQuery($parentQuery, $options, $parentKeys = null) {
		$target = $this->target();
		$alias = $target->alias();
		$fetchQuery = $target->find('all');
		$options['conditions'] = array_merge($this->conditions(), $options['conditions']);
		$key = sprintf('%s.%s', $alias, $options['foreignKey']);

		$filter = ($options['strategy'] == parent::STRATEGY_SUBQUERY) ?
			$this->_buildSubquery($parentQuery, $key) : $parentKeys;

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
