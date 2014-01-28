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

use Cake\Database\Expression\IdentifierExpression;
use Cake\Database\Expression\TupleComparison;
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
 * @return boolean if the 'matching' key in $option is true then this function
 * will return true, false otherwise
 */
	public function canBeJoined($options = []) {
		return !empty($options['matching']);
	}

/**
 * Sets the name of the field representing the foreign key to the source table.
 * If no parameters are passed current field is returned
 *
 * @param string $key the key to be used to link both tables together
 * @return string
 */
	public function foreignKey($key = null) {
		if ($key === null) {
			if ($this->_foreignKey === null) {
				$key = Inflector::singularize($this->source()->alias());
				$this->_foreignKey = Inflector::underscore($key) . '_id';
			}
			return $this->_foreignKey;
		}
		return parent::foreignKey($key);
	}

/**
 * Sets the sort order in which target records should be returned.
 * If no arguments are passed the currently configured value is returned
 * @param mixed $sort A find() compatible order clause
 * @return mixed
 */
	public function sort($sort = null) {
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
 * Correctly nests a result row associated values into the correct array keys inside the
 * source results.
 *
 * @param array $result
 * @return array
 */
	public function transformRow($row) {
		$sourceAlias = $this->source()->alias();
		$targetAlias = $this->target()->alias();
		$values = $row[$this->_name];

		if (isset($values[$this->_name]) && is_array($values[$this->_name])) {
			$values = $values[$this->_name];
		}

		$row[$sourceAlias][$this->property()] = $values;
		return $row;
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
 * @return array
 * @throws \RuntimeException if the number of columns in the foreignKey do not
 * match the number of columns in the source table primaryKey
 */
	protected function _joinCondition(array $options) {
		$conditions = [];
		$tAlias = $this->target()->alias();
		$sAlias = $this->source()->alias();
		$foreignKey = (array)$options['foreignKey'];
		$primaryKey = (array)$this->_sourceTable->primaryKey();

		if (count($foreignKey) !== count($primaryKey)) {
			$msg = 'Cannot match provided foreignKey, got %d columns expected %d';
			throw new \RuntimeException(sprintf($msg, count($foreignKey), count($primaryKey)));
		}

		foreach ($foreignKey as $k => $f) {
			$field = sprintf('%s.%s', $sAlias, $primaryKey[$k]);
			$value = new IdentifierExpression(sprintf('%s.%s', $tAlias, $f));
			$conditions[$field] = $value;
		}

		return $conditions;
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
		$sAlias = $source->alias();
		$tAlias = $this->target()->alias();

		$sourceKeys = [];
		foreach ((array)$source->primaryKey() as $key) {
			$sourceKeys[] = key($fetchQuery->aliasField($key, $sAlias));
		}

		$nestKey = $tAlias . '__' . $tAlias;

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
 * @param array $resultMap a keyed arrays containing the target table
 * @param array $sourceKeys an array with aliased keys to match
 * @param string $nestKey the key under which results should be nested
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

/**
 * Auxiliary function to construct a new Query object to return all the records
 * in the target table that are associated to those specified in $options from
 * the source table
 *
 * @param array $options options accepted by eagerLoader()
 * @return Cake\ORM\Query
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
		$fields = $query->aliasFields((array)$query->repository()->primaryKey());
		return $filterQuery->select($fields, true);
	}

/**
 * Parse extra options passed in the constructor.
 *
 * @param array $opts original list of options passed in constructor
 * @return void
 */
	protected function _options(array $opts) {
		if (isset($opts['sort'])) {
			$this->sort($opts['sort']);
		}
	}

}
