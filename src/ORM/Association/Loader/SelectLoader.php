<?php
/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         3.4.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\ORM\Association\Loader;

use Cake\Database\Expression\IdentifierExpression;
use Cake\Database\Expression\TupleComparison;
use Cake\Database\ValueBinder;
use Cake\ORM\Association;
use InvalidArgumentException;
use RuntimeException;

/**
 * Implements the logic for loading an association using a SELECT query
 *
 * @internal
 */
class SelectLoader
{

    /**
     * The alias of the association loading the results
     *
     * @var string
     */
    protected $alias;

    /**
     * The alias of the source association
     *
     * @var string
     */
    protected $sourceAlias;

    /**
     * The alias of the target association
     *
     * @var string
     */
    protected $targetAlias;

    /**
     * The foreignKey to the target association
     *
     * @var string|array
     */
    protected $foreignKey;

    /**
     * The strategy to use for loading, either select or subquery
     *
     * @var string
     */
    protected $strategy;

    /**
     * The binding key for the source association.
     *
     * @var string
     */
    protected $bindingKey;

    /**
     * A callable that will return a query object used for loading the association results
     *
     * @var callable
     */
    protected $finder;

    /**
     * The type of the association triggering the load
     *
     * @var string
     */
    protected $associationType;

    /**
     * The sorting options for loading the association
     *
     * @var string
     */
    protected $sort;

    /**
     * Copies the options array to properties in this class. The keys in the array correspond
     * to properties in this class.
     *
     * @param array $options Properties to be copied to this class
     */
    public function __construct(array $options)
    {
        $this->alias = $options['alias'];
        $this->sourceAlias = $options['sourceAlias'];
        $this->targetAlias = $options['targetAlias'];
        $this->foreignKey = $options['foreignKey'];
        $this->strategy = $options['strategy'];
        $this->bindingKey = $options['bindingKey'];
        $this->finder = $options['finder'];
        $this->associationType = $options['associationType'];
        $this->sort = isset($options['sort']) ? $options['sort'] : null;
    }

    /**
     * Returns a callable that can be used for injecting association results into a given
     * iterator. The options accepted by this method are the same as `Association::eagerLoader()`
     *
     * @param array $options Same options as `Association::eagerLoader()`
     * @return callable
     */
    public function buildEagerLoader(array $options)
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
            'foreignKey' => $this->foreignKey,
            'conditions' => [],
            'strategy' => $this->strategy,
            'nestKey' => $this->alias,
            'sort' => $this->sort
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
        $key = $this->_linkField($options);
        $filter = $options['keys'];
        $useSubquery = $options['strategy'] === Association::STRATEGY_SUBQUERY;
        $finder = $this->finder;

        if (!isset($options['fields'])) {
            $options['fields'] = [];
        }

        $query = $finder();
        if (isset($options['finder'])) {
            list($finderName, $opts) = $this->_extractFinder($options['finder']);
            $query = $query->find($finderName, $opts);
        }

        $fetchQuery = $query
            ->select($options['fields'])
            ->where($options['conditions'])
            ->eagerLoaded(true)
            ->enableHydration($options['query']->isHydrationEnabled());

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
     * Helper method to infer the requested finder and its options.
     *
     * Returns the inferred options from the finder $type.
     *
     * ### Examples:
     *
     * The following will call the finder 'translations' with the value of the finder as its options:
     * $query->contain(['Comments' => ['finder' => ['translations']]]);
     * $query->contain(['Comments' => ['finder' => ['translations' => []]]]);
     * $query->contain(['Comments' => ['finder' => ['translations' => ['locales' => ['en_US']]]]]);
     *
     * @param string|array $finderData The finder name or an array having the name as key
     * and options as value.
     * @return array
     */
    protected function _extractFinder($finderData)
    {
        $finderData = (array)$finderData;

        if (is_numeric(key($finderData))) {
            return [current($finderData), []];
        }

        return [key($finderData), current($finderData)];
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
            $driver = $fetchQuery->getConnection()->getDriver();
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
    protected function _addFilteringJoin($query, $key, $subquery)
    {
        $filter = [];
        $aliasedTable = $this->sourceAlias;

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
        $defaults = $query->getDefaultTypes();
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
    protected function _linkField($options)
    {
        $links = [];
        $name = $this->alias;

        if ($options['foreignKey'] === false && $this->associationType === Association::ONE_TO_MANY) {
            $msg = 'Cannot have foreignKey = false for hasMany associations. ' .
                   'You must provide a foreignKey column.';
            throw new RuntimeException($msg);
        }

        $keys = in_array($this->associationType, [Association::ONE_TO_ONE, Association::ONE_TO_MANY]) ?
            $this->foreignKey :
            $this->bindingKey;

        foreach ((array)$keys as $key) {
            $links[] = sprintf('%s.%s', $name, $key);
        }

        if (count($links) === 1) {
            return $links[0];
        }

        return $links;
    }

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
        $filterQuery->enableAutoFields(false);
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
        $keys = (array)$this->bindingKey;

        if ($this->associationType === Association::MANY_TO_ONE) {
            $keys = (array)$this->foreignKey;
        }

        $fields = $query->aliasFields($keys, $this->sourceAlias);
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
    protected function _buildResultMap($fetchQuery, $options)
    {
        $resultMap = [];
        $singleResult = in_array($this->associationType, [Association::MANY_TO_ONE, Association::ONE_TO_ONE]);
        $keys = in_array($this->associationType, [Association::ONE_TO_ONE, Association::ONE_TO_MANY]) ?
            $this->foreignKey :
            $this->bindingKey;
        $key = (array)$keys;

        foreach ($fetchQuery->all() as $result) {
            $values = [];
            foreach ($key as $k) {
                $values[] = $result[$k];
            }
            if ($singleResult) {
                $resultMap[implode(';', $values)] = $result;
            } else {
                $resultMap[implode(';', $values)][] = $result;
            }
        }

        return $resultMap;
    }

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
        $keys = $this->associationType === Association::MANY_TO_ONE ?
            $this->foreignKey :
            $this->bindingKey;

        $sourceKeys = [];
        foreach ((array)$keys as $key) {
            $f = $fetchQuery->aliasField($key, $this->sourceAlias);
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
