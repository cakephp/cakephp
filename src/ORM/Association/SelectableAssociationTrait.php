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

use Cake\Database\Expression\IdentifierExpression;
use Cake\Database\Expression\TupleComparison;
use Cake\Database\ValueBinder;
use InvalidArgumentException;

/**
 * Represents a type of association that that can be fetched using another query
 */
trait SelectableAssociationTrait
{

    /**
     * Returns true if the eager loading process will require a set of the owning table's
     * binding keys in order to use them as a filter in the finder query.
     *
     * @param array $options The options containing the strategy to be used.
     * @return bool true if a list of keys will be required
     */
    public function requiresKeys(array $options = [])
    {
        $strategy = isset($options['strategy']) ? $options['strategy'] : $this->strategy();

        return $strategy === $this::STRATEGY_SELECT;
    }

    /**
     * {@inheritDoc}
     */
    public function eagerLoader(array $options)
    {
        $options += $this->_defaultOptions();
        $fetchQuery = $this->_buildQuery($options);
        $resultMap = $this->_buildResultMap($fetchQuery, $options);

        return $this->_resultInjector($fetchQuery, $resultMap, $options);
    }

    /**
     * Returns the default options to use for the eagerLoader
     *
     * @return array
     */
    protected function _defaultOptions()
    {
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
    protected function _buildQuery($options)
    {
        $target = $this->target();
        $alias = $target->alias();
        $key = $this->_linkField($options);
        $filter = $options['keys'];
        $useSubquery = $options['strategy'] === $this::STRATEGY_SUBQUERY;

        $finder = isset($options['finder']) ? $options['finder'] : $this->finder();
        list($finder, $opts) = $this->_extractFinder($finder);
        if (!isset($options['fields'])) {
            $options['fields'] = [];
        }

        $fetchQuery = $this
            ->find($finder, $opts)
            ->select($options['fields'])
            ->where($options['conditions'])
            ->eagerLoaded(true)
            ->hydrate($options['query']->hydrate());

        if ($useSubquery) {
            $filter = $this->_buildSubquery($options['query']);
            $fetchQuery = $this->_addFilteringJoin($fetchQuery, $key, $filter);
        } else {
            $fetchQuery = $this->_addFilteringCondition($fetchQuery, $key, $filter);
        }

        if (!empty($options['sort'])) {
            $fetchQuery->order($options['sort']);
        }

        if (!empty($options['contain'])) {
            $fetchQuery->contain($options['contain']);
        }

        if (!empty($options['queryBuilder'])) {
            $fetchQuery = $options['queryBuilder']($fetchQuery);
        }

        $this->_assertFieldsPresent($fetchQuery, (array)$key);

        return $fetchQuery;
    }

    /**
     * Checks that the fetching query either has auto fields on or
     * has the foreignKey fields selected.
     * If the required fields are missing, throws an exception.
     *
     * @param \Cake\ORM\Query $fetchQuery The association fetching query
     * @param array $key The foreign key fields to check
     * @return void
     * @throws InvalidArgumentException
     */
    protected function _assertFieldsPresent($fetchQuery, $key)
    {
        $select = $fetchQuery->aliasFields($fetchQuery->clause('select'));
        if (empty($select)) {
            return;
        }
        $missingKey = function ($fieldList, $key) {
            foreach ($key as $keyField) {
                if (!in_array($keyField, $fieldList, true)) {
                    return true;
                }
            }

            return false;
        };

        $missingFields = $missingKey($select, $key);
        if ($missingFields) {
            $driver = $fetchQuery->connection()->driver();
            $quoted = array_map([$driver, 'quoteIdentifier'], $key);
            $missingFields = $missingKey($select, $quoted);
        }

        if ($missingFields) {
            throw new InvalidArgumentException(
                sprintf(
                    'You are required to select the "%s" field(s)',
                    implode(', ', (array)$key)
                )
            );
        }
    }

    /**
     * Appends any conditions required to load the relevant set of records in the
     * target table query given a filter key and some filtering values when the
     * filtering needs to be done using a subquery.
     *
     * @param \Cake\ORM\Query $query Target table's query
     * @param string $key the fields that should be used for filtering
     * @param \Cake\ORM\Query $subquery The Subquery to use for filtering
     * @return \Cake\ORM\Query
     */
    public function _addFilteringJoin($query, $key, $subquery)
    {
        $filter = [];
        $aliasedTable = $this->source()->alias();

        foreach ($subquery->clause('select') as $aliasedField => $field) {
            if (is_int($aliasedField)) {
                $filter[] = new IdentifierExpression($field);
            } else {
                $filter[$aliasedField] = $field;
            }
        }
        $subquery->select($filter, true);

        if (is_array($key)) {
            $conditions = $this->_createTupleCondition($query, $key, $filter, '=');
        } else {
            $filter = current($filter);
        }

        $conditions = isset($conditions) ? $conditions : $query->newExpr([$key => $filter]);

        return $query->innerJoin(
            [$aliasedTable => $subquery],
            $conditions
        );
    }

    /**
     * Appends any conditions required to load the relevant set of records in the
     * target table query given a filter key and some filtering values.
     *
     * @param \Cake\ORM\Query $query Target table's query
     * @param string|array $key the fields that should be used for filtering
     * @param mixed $filter the value that should be used to match for $key
     * @return \Cake\ORM\Query
     */
    protected function _addFilteringCondition($query, $key, $filter)
    {
        if (is_array($key)) {
            $conditions = $this->_createTupleCondition($query, $key, $filter, 'IN');
        }

        $conditions = isset($conditions) ? $conditions : [$key . ' IN' => $filter];

        return $query->andWhere($conditions);
    }

    /**
     * Returns a TupleComparison object that can be used for matching all the fields
     * from $keys with the tuple values in $filter using the provided operator.
     *
     * @param \Cake\ORM\Query $query Target table's query
     * @param array $keys the fields that should be used for filtering
     * @param mixed $filter the value that should be used to match for $key
     * @param string $operator The operator for comparing the tuples
     * @return \Cake\Database\Expression\TupleComparison
     */
    protected function _createTupleCondition($query, $keys, $filter, $operator)
    {
        $types = [];
        $defaults = $query->defaultTypes();
        foreach ($keys as $k) {
            if (isset($defaults[$k])) {
                $types[] = $defaults[$k];
            }
        }

        return new TupleComparison($keys, $filter, $types, $operator);
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
    protected function _buildSubquery($query)
    {
        $filterQuery = clone $query;
        $filterQuery->autoFields(false);
        $filterQuery->mapReduce(null, null, true);
        $filterQuery->formatResults(null, true);
        $filterQuery->contain([], true);
        $filterQuery->valueBinder(new ValueBinder());

        if (!$filterQuery->clause('limit')) {
            $filterQuery->limit(null);
            $filterQuery->order([], true);
            $filterQuery->offset(null);
        }

        $fields = $this->_subqueryFields($query);
        $filterQuery->select($fields['select'], true)->group($fields['group']);

        return $filterQuery;
    }

    /**
     * Calculate the fields that need to participate in a subquery.
     *
     * Normally this includes the binding key columns. If there is a an ORDER BY,
     * those columns are also included as the fields may be calculated or constant values,
     * that need to be present to ensure the correct association data is loaded.
     *
     * @param \Cake\ORM\Query $query The query to get fields from.
     * @return array The list of fields for the subquery.
     */
    protected function _subqueryFields($query)
    {
        $keys = (array)$this->bindingKey();
        if ($this->type() === $this::MANY_TO_ONE) {
            $keys = (array)$this->foreignKey();
        }
        $fields = $query->aliasFields($keys, $this->source()->alias());
        $group = $fields = array_values($fields);

        $order = $query->clause('order');
        if ($order) {
            $columns = $query->clause('select');
            $order->iterateParts(function ($direction, $field) use (&$fields, $columns) {
                if (isset($columns[$field])) {
                    $fields[$field] = $columns[$field];
                }
            });
        }

        return ['select' => $fields, 'group' => $group];
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
    protected function _resultInjector($fetchQuery, $resultMap, $options)
    {
        $source = $this->source();
        $sAlias = $source->alias();
        $keys = $this->type() === $this::MANY_TO_ONE ?
            $this->foreignKey() :
            $this->bindingKey();

        $sourceKeys = [];
        foreach ((array)$keys as $key) {
            $f = $fetchQuery->aliasField($key, $sAlias);
            $sourceKeys[] = key($f);
        }

        $nestKey = $options['nestKey'];
        if (count($sourceKeys) > 1) {
            return $this->_multiKeysInjector($resultMap, $sourceKeys, $nestKey);
        }

        $sourceKey = $sourceKeys[0];

        return function ($row) use ($resultMap, $sourceKey, $nestKey) {
            if (isset($row[$sourceKey], $resultMap[$row[$sourceKey]])) {
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
    protected function _multiKeysInjector($resultMap, $sourceKeys, $nestKey)
    {
        return function ($row) use ($resultMap, $sourceKeys, $nestKey) {
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
