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
use Cake\Datasource\ResultSetInterface;
use Cake\ORM\Query\SelectQuery;
use InvalidArgumentException;
use SplFixedArray;

/**
 * Factory class for generating ResulSet instances.
 *
 * It is responsible for correctly nesting result keys reported from the query
 * and hydrating entities.
 *
 * @template T of array|\Cake\Datasource\EntityInterface
 */
class ResultSetFactory
{
    /**
     * @var class-string<\Cake\Datasource\ResultSetInterface>
     */
    protected string $resultSetClass = ResultSet::class;

    /**
     * Create a result set instance.
     *
     * @param iterable $results Results.
     * @param \Cake\ORM\Query\SelectQuery<T>|null $query Query from where results came.
     * @return \Cake\Datasource\ResultSetInterface
     */
    public function createResultSet(iterable $results, ?SelectQuery $query = null): ResultSetInterface
    {
        if ($query) {
            $data = $this->collectData($query);

            if (is_array($results)) {
                foreach ($results as $i => $row) {
                    $results[$i] = $this->groupResult($row, $data);
                }

                $results = SplFixedArray::fromArray($results);
            } else {
                $results = (new Collection($results))
                    ->map(function ($row) use ($data) {
                        return $this->groupResult($row, $data);
                    });
            }
        }

        return new $this->resultSetClass($results);
    }

    /**
     * Get repository and it's associations data for nesting results key and
     * entity hydration.
     *
     * @param \Cake\ORM\Query\SelectQuery $query The query from where to derive the data.
     * @return array{primaryAlias: string, registryAlias: string, entityClass: class-string<\Cake\Datasource\EntityInterface>, hydrate: bool, autoFields: bool|null, matchingColumns: array, dtoClass: class-string|null, matchingAssoc: array, containAssoc: array, fields: array}
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
            'dtoClass' => $query->getDtoClass(),
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
            $key = trim((string)$key, '"`[]');

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
        $results = [];
        $presentAliases = [];
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
                array_intersect_key($row, $keys),
            );
            if ($data['hydrate'] && $data['dtoClass'] === null) {
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

            if ($data['hydrate'] && $data['dtoClass'] === null && $results[$alias] !== null && $assoc['canBeJoined']) {
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

        // DTO projection returns arrays - DTO mapping happens in formatter phase
        if ($data['dtoClass'] !== null) {
            return $results;
        }

        if ($data['hydrate'] && !($results instanceof EntityInterface)) {
            /** @var \Cake\Datasource\EntityInterface */
            return new $data['entityClass']($results, $options);
        }

        return $results;
    }

    /**
     * Cached DtoMapper instance
     *
     * @var \Cake\ORM\DtoMapper|null
     */
    protected ?DtoMapper $dtoMapper = null;

    /**
     * Hydrate a row into a DTO.
     *
     * Supports two patterns:
     * - Static `createFromArray($data, $nested)` factory method (cakephp-dto style)
     * - Constructor with named parameters (DtoMapper reflection)
     *
     * @param array $row Nested array data
     * @param class-string $dtoClass DTO class name
     * @return object
     */
    public function hydrateDto(array $row, string $dtoClass): object
    {
        // Check for array style static factory method
        if (method_exists($dtoClass, 'createFromArray')) {
            return $dtoClass::createFromArray($row, true);
        }

        // Use DtoMapper for plain readonly DTOs with named constructor params
        return $this->getDtoMapper()->map($row, $dtoClass);
    }

    /**
     * Get or create the DtoMapper instance.
     *
     * @return \Cake\ORM\DtoMapper
     */
    public function getDtoMapper(): DtoMapper
    {
        if ($this->dtoMapper === null) {
            $this->dtoMapper = new DtoMapper();
        }

        return $this->dtoMapper;
    }

    /**
     * Set the ResultSet class to use.
     *
     * @param class-string<\Cake\Datasource\ResultSetInterface> $resultSetClass Class name.
     * @return $this
     */
    public function setResultSetClass(string $resultSetClass)
    {
        if (!is_a($resultSetClass, ResultSetInterface::class, true)) {
            throw new InvalidArgumentException(sprintf(
                'Invalid ResultSet class `%s`. It must implement `%s`',
                $resultSetClass,
                ResultSetInterface::class,
            ));
        }

        $this->resultSetClass = $resultSetClass;

        return $this;
    }

    /**
     * Get the ResultSet class to use.
     *
     * @return class-string<\Cake\Datasource\ResultSetInterface>
     */
    public function getResultSetClass(): string
    {
        return $this->resultSetClass;
    }
}
