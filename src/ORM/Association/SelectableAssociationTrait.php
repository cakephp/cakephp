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
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
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
 * @param array $options
 * @return boolean true if a list of keys will be required
 */
	public function requiresKeys($options = []) {
		$strategy = isset($options['strategy']) ? $options['strategy'] : $this->strategy();
		return $strategy === parent::STRATEGY_SELECT;
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
		if ($options['strategy'] === parent::STRATEGY_SUBQUERY) {
			$filter = $this->_buildSubquery($options['query'], $key);
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
 * @param \Cake\ORM\Query taget table's query
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
 * @param array $options
 * @return string
 */
	protected function _linkField($options) {
		$links = [];
		$name = $this->name();

		foreach ((array)$options['foreignKey'] as $key) {
			$links[] = sprintf('%s.%s', $name, $key);
		}

		if (count($links) === 1) {
			return $links[0];
		}

		return $links;
	}

/**
 * Builds a query to be used as a condition for filtering records in in the
 * target table, it is constructed by cloning the original query that was used
 * to load records in the source table.
 *
 * @param \Cake\ORM\Query $query the original query used to load source records
 * @param string $foreignKey the field to be selected in the query
 * @return \Cake\ORM\Query
 */
	protected function _buildSubquery($query, $foreignKey) {
		$filterQuery = clone $query;
		$filterQuery->limit(null);
		$filterQuery->contain([], true);
		$joins = $filterQuery->join();
		foreach ($joins as $i => $join) {
			if (strtolower($join['type']) !== 'inner') {
				unset($joins[$i]);
			}
		}
		$filterQuery->join($joins, [], true);
		$fields = $query->aliasFields((array)$query->repository()->primaryKey());
		return $filterQuery->select($fields, true);
	}

}
