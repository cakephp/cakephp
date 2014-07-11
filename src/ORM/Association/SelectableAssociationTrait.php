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
namespace Cake\ORM\Association;

use Cake\Database\Expression\TupleComparison;

/**
 * Represents a type of association that that can be fetched using another query
 */
trait SelectableAssociationTrait {

/**
 * Returns true if the eager loading process will require a set of parent table's
 * primary keys in order to use them as a filter in the finder query.
 *
 * @param array $options The options containing the strategy to be used.
 * @return bool true if a list of keys will be required
 */
	public function requiresKeys(array $options = []) {
		$strategy = isset($options['strategy']) ? $options['strategy'] : $this->strategy();
		return $strategy === $this::STRATEGY_SELECT;
	}

/**
 * {@inheritDoc}
 */
	public function eagerLoader(array $options) {
		$options += $this->_defaultOptions();
		$queryBuilder = false;
		if (!empty($options['queryBuilder'])) {
			$queryBuilder = $options['queryBuilder'];
			unset($options['queryBuilder']);
		}

		$fetchQuery = $this->_buildQuery($options);
		if ($queryBuilder) {
			$fetchQuery = $queryBuilder($fetchQuery);
		}
		$resultMap = $this->_buildResultMap($fetchQuery, $options);
		return $this->_resultInjector($fetchQuery, $resultMap, $options);
	}

/**
 * Returns the default options to use for the eagerLoader
 *
 * @return array
 */
	protected function _defaultOptions() {
		return [
			'foreignKey' => $this->foreignKey(),
			'conditions' => [],
			'strategy' => $this->strategy(),
			'nestKey' => $this->_name
		];
	}

/**
 * Auxiliary function to construct a new Query object to return all the records
 * in the target table that are associated to those specified in $options from
 * the source table
 *
 * @param array $options options accepted by eagerLoader()
 * @return \Cake\ORM\Query
 * @throws \InvalidArgumentException When a key is required for associations but not selected.
 */
	protected function _buildQuery($options) {
		$target = $this->target();
		$alias = $target->alias();
		$key = $this->_linkField($options);
		$filter = $options['keys'];

		if ($options['strategy'] === $this::STRATEGY_SUBQUERY) {
			$filter = $this->_buildSubquery($options['query']);
		}

		$fetchQuery = $this
			->find('all')
			->where($options['conditions'])
			->eagerLoaded(true)
			->hydrate($options['query']->hydrate());
		$fetchQuery = $this->_addFilteringCondition($fetchQuery, $key, $filter);

		if (!empty($options['fields'])) {
			$fields = $fetchQuery->aliasFields($options['fields'], $alias);
			if (!in_array($key, $fields)) {
				throw new \InvalidArgumentException(
					sprintf('You are required to select the "%s" field', $key)
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

		if (!empty($options['queryBuilder'])) {
			$options['queryBuilder']($fetchQuery);
		}

		return $fetchQuery;
	}

/**
 * Appends any conditions required to load the relevant set of records in the
 * target table query given a filter key and some filtering values.
 *
 * @param \Cake\ORM\Query $query Target table's query
 * @param string $key the fields that should be used for filtering
 * @param mixed $filter the value that should be used to match for $key
 * @return \Cake\ORM\Query
 */
	protected function _addFilteringCondition($query, $key, $filter) {
		if (is_array($key)) {
			$types = [];
			$defaults = $query->defaultTypes();
			foreach ($key as $k) {
				if (isset($defaults[$k])) {
					$types[] = $defaults[$k];
				}
			}
			return $query->andWhere(new TupleComparison($key, $filter, $types, 'IN'));
		}

		return $query->andWhere([$key . ' IN' => $filter]);
	}

/**
 * Generates a string used as a table field that contains the values upon
 * which the filter should be applied
 *
 * @param array $options The options for getting the link field.
 * @return string|array
 */
	protected abstract function _linkField($options);

/**
 * Builds a query to be used as a condition for filtering records in the
 * target table, it is constructed by cloning the original query that was used
 * to load records in the source table.
 *
 * @param \Cake\ORM\Query $query the original query used to load source records
 * @return \Cake\ORM\Query
 */
	protected function _buildSubquery($query) {
		$filterQuery = clone $query;
		$filterQuery->limit(null);
		$filterQuery->order([], true);
		$filterQuery->contain([], true);
		$joins = $filterQuery->join();
		foreach ($joins as $i => $join) {
			if (strtolower($join['type']) !== 'inner') {
				unset($joins[$i]);
			}
		}

		$keys = (array)$query->repository()->primaryKey();

		if ($this->type() === $this::MANY_TO_ONE) {
			$keys = (array)$this->foreignKey();
		}

		$filterQuery->join($joins, [], true);
		$fields = $query->aliasFields($keys);
		return $filterQuery->select($fields, true);
	}

/**
 * Builds an array containing the results from fetchQuery indexed by
 * the foreignKey value corresponding to this association.
 *
 * @param \Cake\ORM\Query $fetchQuery The query to get results from
 * @param array $options The options passed to the eager loader
 * @return array
 */
	protected abstract function _buildResultMap($fetchQuery, $options);

/**
 * Returns a callable to be used for each row in a query result set
 * for injecting the eager loaded rows
 *
 * @param \Cake\ORM\Query $fetchQuery the Query used to fetch results
 * @param array $resultMap an array with the foreignKey as keys and
 * the corresponding target table results as value.
 * @param array $options The options passed to the eagerLoader method
 * @return \Closure
 */
	protected function _resultInjector($fetchQuery, $resultMap, $options) {
		$source = $this->source();
		$sAlias = $source->alias();
		$keys = $this->type() === $this::MANY_TO_ONE ?
			$this->foreignKey() :
			$source->primaryKey();

		$sourceKeys = [];
		foreach ((array)$keys as $key) {
			$sourceKeys[] = key($fetchQuery->aliasField($key, $sAlias));
		}

		$nestKey = $options['nestKey'];
		if (count($sourceKeys) > 1) {
			return $this->_multiKeysInjector($resultMap, $sourceKeys, $nestKey);
		}

		$sourceKey = $sourceKeys[0];
		return function($row) use ($resultMap, $sourceKey, $nestKey) {
			if (isset($resultMap[$row[$sourceKey]])) {
				$row[$nestKey] = $resultMap[$row[$sourceKey]];
			}
			return $row;
		};
	}

/**
 * Returns a callable to be used for each row in a query result set
 * for injecting the eager loaded rows when the matching needs to
 * be done with multiple foreign keys
 *
 * @param array $resultMap A keyed arrays containing the target table
 * @param array $sourceKeys An array with aliased keys to match
 * @param string $nestKey The key under which results should be nested
 * @return \Closure
 */
	protected function _multiKeysInjector($resultMap, $sourceKeys, $nestKey) {
		return function($row) use ($resultMap, $sourceKeys, $nestKey) {
			$values = [];
			foreach ($sourceKeys as $key) {
				$values[] = $row[$key];
			}

			$key = implode(';', $values);
			if (isset($resultMap[$key])) {
				$row[$nestKey] = $resultMap[$key];
			}
			return $row;
		};
	}

}
