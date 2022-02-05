<?php
declare(strict_types=1);

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
 * @since         3.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\ORM;

use Cake\Collection\Collection;
use Cake\Collection\CollectionTrait;
use Cake\Datasource\EntityInterface;
use Cake\Datasource\ResultSetInterface;
use SplFixedArray;

/**
 * Represents the results obtained after executing a query for a specific table
 * This object is responsible for correctly nesting result keys reported from
 * the query and hydrating entities.
 */
class ResultSet implements ResultSetInterface
{
    use CollectionTrait;

    /**
     * Points to the next record number that should be fetched
     *
     * @var int
     */
    protected int $_index = 0;

    /**
     * Last record fetched from the statement
     *
     * @var \Cake\Datasource\EntityInterface|array
     */
    protected EntityInterface|array $_current = [];

    /**
     * Default table instance
     *
     * @var \Cake\ORM\Table
     */
    protected Table $_defaultTable;

    /**
     * The default table alias
     *
     * @var string
     */
    protected string $_defaultAlias;

    /**
     * List of associations that should be placed under the `_matchingData`
     * result key.
     *
     * @var array
     */
    protected array $_matchingMap = [];

    /**
     * List of associations that should be eager loaded.
     *
     * @var array
     */
    protected array $_containMap = [];

    /**
     * Map of fields that are fetched with their type and the table they belong to.
     *
     * @var array
     */
    protected array $_map = [];

    /**
     * List of matching associations and the column keys to expect
     * from each of them.
     *
     * @var array
     */
    protected array $_matchingMapColumns = [];

    /**
     * Results that have been fetched or hydrated into the results.
     *
     * @var \SplFixedArray
     */
    protected SplFixedArray $_results;

    /**
     * Whether to hydrate results into entities.
     *
     * @var bool
     */
    protected bool $_hydrate = true;

    /**
     * Tracks value of $_autoFields property of $query passed to constructor.
     *
     * @var bool|null
     */
    protected ?bool $_autoFields = null;

    /**
     * The fully namespaced name of the class to use for hydrating results.
     *
     * @var string
     */
    protected string $_entityClass;

    /**
     * Holds the count of records in this result set
     *
     * @var int
     */
    protected int $_count = 0;

    /**
     * Constructor
     *
     * @param \Cake\ORM\Query $query Query from where results came.
     * @param array $results Results array.
     */
    public function __construct(Query $query, array $results)
    {
        $this->_defaultTable = $query->getRepository();
        $this->_hydrate = $query->isHydrationEnabled();
        $this->_autoFields = $query->isAutoFieldsEnabled();

        $this->_entityClass = $this->_defaultTable->getEntityClass();
        $this->_defaultAlias = $this->_defaultTable->getAlias();

        $this->_calculateAssociationMap($query);
        $this->_calculateColumnMap($query);

        $this->__unserialize($results);
        foreach ($this->_results as $i => $row) {
            $this->_results[$i] = $this->_groupResult($row);
        }
    }

    /**
     * Returns the current record in the result iterator.
     *
     * Part of Iterator interface.
     *
     * @return \Cake\Datasource\EntityInterface|array
     */
    public function current(): EntityInterface|array
    {
        return $this->_current;
    }

    /**
     * Returns the key of the current record in the iterator.
     *
     * Part of Iterator interface.
     *
     * @return int
     */
    public function key(): int
    {
        return $this->_index;
    }

    /**
     * Advances the iterator pointer to the next record.
     *
     * Part of Iterator interface.
     *
     * @return void
     */
    public function next(): void
    {
        $this->_index++;
    }

    /**
     * Rewinds a ResultSet.
     *
     * Part of Iterator interface.
     *
     * @return void
     */
    public function rewind(): void
    {
        $this->_index = 0;
    }

    /**
     * Whether there are more results to be fetched from the iterator.
     *
     * Part of Iterator interface.
     *
     * @return bool
     */
    public function valid(): bool
    {
        if ($this->_index < $this->_count) {
            $this->_current = $this->_results[$this->_index];

            return true;
        }

        return false;
    }

    /**
     * Get the first record from a result set.
     *
     * @return \Cake\Datasource\EntityInterface|array|null
     */
    public function first(): EntityInterface|array|null
    {
        foreach ($this as $result) {
            return $result;
        }

        return null;
    }

    /**
     * Serializes a resultset.
     *
     * @return array
     */
    public function __serialize(): array
    {
        return $this->_results->toArray();
    }

    /**
     * Unserializes a resultset.
     *
     * @param array $data Data array.
     * @return void
     */
    public function __unserialize(array $data): void
    {
        $this->_results = SplFixedArray::fromArray($data);
        $this->_count = $this->_results->count();
    }

    /**
     * Gives the number of rows in the result set.
     *
     * Part of the Countable interface.
     *
     * @return int
     */
    public function count(): int
    {
        return $this->_count;
    }

    /**
     * @inheritDoc
     */
    public function countKeys(): int
    {
        return $this->_count;
    }

    /**
     * @inheritDoc
     */
    public function isEmpty(): bool
    {
        return !$this->_count;
    }

    /**
     * Calculates the list of associations that where eager loaded for this query.
     *
     * @param \Cake\ORM\Query $query The query from where to derive the associations.
     * @return void
     */
    protected function _calculateAssociationMap(Query $query): void
    {
        $map = $query->getEagerLoader()->associationsMap($this->_defaultTable);
        $this->_matchingMap = (new Collection($map))
            ->match(['matching' => true])
            ->indexBy('alias')
            ->toArray();

        $this->_containMap = (new Collection(array_reverse($map)))
            ->match(['matching' => false])
            ->indexBy('nestKey')
            ->toArray();
    }

    /**
     * Creates a map of row keys out of the query's select clause that can be
     * used to hydrate nested result sets more quickly.
     *
     * @param \Cake\ORM\Query $query The query from where to derive the column map.
     * @return void
     */
    protected function _calculateColumnMap(Query $query): void
    {
        $map = [];
        foreach ($query->clause('select') as $key => $field) {
            $key = trim($key, '"`[]');

            if (strpos($key, '__') <= 0) {
                $map[$this->_defaultAlias][$key] = $key;
                continue;
            }

            $parts = explode('__', $key, 2);
            $map[$parts[0]][$key] = $parts[1];
        }

        foreach ($this->_matchingMap as $alias => $assoc) {
            if (!isset($map[$alias])) {
                continue;
            }
            $this->_matchingMapColumns[$alias] = $map[$alias];
            unset($map[$alias]);
        }

        $this->_map = $map;
    }

    /**
     * Correctly nests results keys including those coming from associations.
     *
     * Hyrate row array into entity if hydration is enabled.
     *
     * @param array $row Array containing columns and values.
     * @return \Cake\Datasource\EntityInterface|array
     */
    protected function _groupResult(array $row): EntityInterface|array
    {
        $defaultAlias = $this->_defaultAlias;
        $results = $presentAliases = [];
        $options = [
            'useSetters' => false,
            'markClean' => true,
            'markNew' => false,
            'guard' => false,
        ];

        foreach ($this->_matchingMapColumns as $alias => $keys) {
            $matching = $this->_matchingMap[$alias];
            $results['_matchingData'][$alias] = array_combine(
                $keys,
                array_intersect_key($row, $keys)
            );
            if ($this->_hydrate) {
                /** @var \Cake\ORM\Table $table */
                $table = $matching['instance'];
                $options['source'] = $table->getRegistryAlias();
                /** @var \Cake\Datasource\EntityInterface $entity */
                $entity = new $matching['entityClass']($results['_matchingData'][$alias], $options);
                $results['_matchingData'][$alias] = $entity;
            }
        }

        foreach ($this->_map as $table => $keys) {
            $results[$table] = array_combine($keys, array_intersect_key($row, $keys));
            $presentAliases[$table] = true;
        }

        // If the default table is not in the results, set
        // it to an empty array so that any contained
        // associations hydrate correctly.
        $results[$defaultAlias] = $results[$defaultAlias] ?? [];

        unset($presentAliases[$defaultAlias]);

        foreach ($this->_containMap as $assoc) {
            $alias = $assoc['nestKey'];

            if ($assoc['canBeJoined'] && empty($this->_map[$alias])) {
                continue;
            }

            /** @var \Cake\ORM\Association $instance */
            $instance = $assoc['instance'];

            if (!$assoc['canBeJoined'] && !isset($row[$alias])) {
                $results = $instance->defaultRowValue($results, $assoc['canBeJoined']);
                continue;
            }

            if (!$assoc['canBeJoined']) {
                $results[$alias] = $row[$alias];
            }

            $target = $instance->getTarget();
            $options['source'] = $target->getRegistryAlias();
            unset($presentAliases[$alias]);

            if ($assoc['canBeJoined'] && $this->_autoFields !== false) {
                $hasData = false;
                foreach ($results[$alias] as $v) {
                    if ($v !== null && $v !== []) {
                        $hasData = true;
                        break;
                    }
                }

                if (!$hasData) {
                    $results[$alias] = null;
                }
            }

            if ($this->_hydrate && $results[$alias] !== null && $assoc['canBeJoined']) {
                $entity = new $assoc['entityClass']($results[$alias], $options);
                $results[$alias] = $entity;
            }

            $results = $instance->transformRow($results, $alias, $assoc['canBeJoined'], $assoc['targetProperty']);
        }

        foreach ($presentAliases as $alias => $present) {
            if (!isset($results[$alias])) {
                continue;
            }
            $results[$defaultAlias][$alias] = $results[$alias];
        }

        if (isset($results['_matchingData'])) {
            $results[$defaultAlias]['_matchingData'] = $results['_matchingData'];
        }

        $options['source'] = $this->_defaultTable->getRegistryAlias();
        if (isset($results[$defaultAlias])) {
            $results = $results[$defaultAlias];
        }
        if ($this->_hydrate && !($results instanceof EntityInterface)) {
            $results = new $this->_entityClass($results, $options);
        }

        return $results;
    }

    /**
     * Returns an array that can be used to describe the internal state of this
     * object.
     *
     * @return array
     */
    public function __debugInfo(): array
    {
        return [
            'items' => $this->toArray(),
        ];
    }
}
