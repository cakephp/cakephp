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
 * @since         5.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\ORM;

use Cake\Collection\Collection;
use Cake\Datasource\EntityInterface;
use Cake\ORM\Query\SelectQuery;

/**
 * Factory class for generation ResulSet instances.
 *
 * It is responsible for correctly nesting result keys reported from the query
 * and hydrating entities.
 *
 * @template T of array|\Cake\Datasource\EntityInterface
 */
class ResultSetFactory
{
    /**
     * Constructor
     *
     * @param \Cake\ORM\Query\SelectQuery<T> $query Query from where results came.
     * @param array $results Results array.
     * @return \Cake\ORM\ResultSet<array|\Cake\Datasource\EntityInterface>
     */
    public function createResultSet(SelectQuery $query, array $results): ResultSet
    {
        $data = $this->collectData($query);

        foreach ($results as $i => $row) {
            $results[$i] = $this->groupResult($row, $data);
        }

        return new ResultSet($results);
    }

    /**
     * Get repository and it's associations data for nesting results key and
     * entity hydration.
     *
     * @param \Cake\ORM\Query\SelectQuery $query The query from where to derive the data.
     * @return array
     */
    protected function collectData(SelectQuery $query): array
    {
        $primaryTable = $query->getRepository();
        $data = [
            'primaryAlias' => $primaryTable->getAlias(),
            'registryAlias' => $primaryTable->getRegistryAlias(),
            'entityClass' => $primaryTable->getEntityClass(),
            'hydrate' => $query->isHydrationEnabled(),
            'autoFields' => $query->isAutoFieldsEnabled(),
            'matchingColumns' => [],
        ];

        $assocMap = $query->getEagerLoader()->associationsMap($primaryTable);
        $data['matchingAssoc'] = (new Collection($assocMap))
            ->match(['matching' => true])
            ->indexBy('alias')
            ->toArray();

        $data['containAssoc'] = (new Collection(array_reverse($assocMap)))
            ->match(['matching' => false])
            ->indexBy('nestKey')
            ->toArray();

        $fields = [];
        foreach ($query->clause('select') as $key => $field) {
            $key = trim($key, '"`[]');

            if (strpos($key, '__') <= 0) {
                $fields[$data['primaryAlias']][$key] = $key;
                continue;
            }

            $parts = explode('__', $key, 2);
            $fields[$parts[0]][$key] = $parts[1];
        }

        foreach ($data['matchingAssoc'] as $alias => $assoc) {
            if (!isset($fields[$alias])) {
                continue;
            }
            $data['matchingColumns'][$alias] = $fields[$alias];
            unset($fields[$alias]);
        }

        $data['fields'] = $fields;

        return $data;
    }

    /**
     * Correctly nests results keys including those coming from associations.
     *
     * Hydrate row array into entity if hydration is enabled.
     *
     * @param array $row Array containing columns and values.
     * @param array $data Array containing table and query metadata
     * @return \Cake\Datasource\EntityInterface|array
     */
    protected function groupResult(array $row, array $data): EntityInterface|array
    {
        $results = $presentAliases = [];
        $options = [
            'useSetters' => false,
            'markClean' => true,
            'markNew' => false,
            'guard' => false,
        ];

        foreach ($data['matchingColumns'] as $alias => $keys) {
            $matching = $data['matchingAssoc'][$alias];
            $results['_matchingData'][$alias] = array_combine(
                $keys,
                array_intersect_key($row, $keys)
            );
            if ($data['hydrate']) {
                $table = $matching['instance'];
                assert($table instanceof Table || $table instanceof Association);

                $options['source'] = $table->getRegistryAlias();
                $entity = new $matching['entityClass']($results['_matchingData'][$alias], $options);
                assert($entity instanceof EntityInterface);

                $results['_matchingData'][$alias] = $entity;
            }
        }

        foreach ($data['fields'] as $table => $keys) {
            $results[$table] = array_combine($keys, array_intersect_key($row, $keys));
            $presentAliases[$table] = true;
        }

        // If the default table is not in the results, set
        // it to an empty array so that any contained
        // associations hydrate correctly.
        $results[$data['primaryAlias']] ??= [];

        unset($presentAliases[$data['primaryAlias']]);

        foreach ($data['containAssoc'] as $assoc) {
            $alias = $assoc['nestKey'];
            /** @var bool $canBeJoined */
            $canBeJoined = $assoc['canBeJoined'];
            if ($canBeJoined && empty($data['fields'][$alias])) {
                continue;
            }

            $instance = $assoc['instance'];
            assert($instance instanceof Association);

            if (!$canBeJoined && !isset($row[$alias])) {
                $results = $instance->defaultRowValue($results, $canBeJoined);
                continue;
            }

            if (!$canBeJoined) {
                $results[$alias] = $row[$alias];
            }

            $target = $instance->getTarget();
            $options['source'] = $target->getRegistryAlias();
            unset($presentAliases[$alias]);

            if ($assoc['canBeJoined'] && $data['autoFields'] !== false) {
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

            if ($data['hydrate'] && $results[$alias] !== null && $assoc['canBeJoined']) {
                $entity = new $assoc['entityClass']($results[$alias], $options);
                $results[$alias] = $entity;
            }

            $results = $instance->transformRow($results, $alias, $assoc['canBeJoined'], $assoc['targetProperty']);
        }

        foreach ($presentAliases as $alias => $present) {
            if (!isset($results[$alias])) {
                continue;
            }
            $results[$data['primaryAlias']][$alias] = $results[$alias];
        }

        if (isset($results['_matchingData'])) {
            $results[$data['primaryAlias']]['_matchingData'] = $results['_matchingData'];
        }

        $options['source'] = $data['registryAlias'];
        if (isset($results[$data['primaryAlias']])) {
            $results = $results[$data['primaryAlias']];
        }
        if ($data['hydrate'] && !($results instanceof EntityInterface)) {
            $results = new $data['entityClass']($results, $options);
        }

        return $results;
    }
}
