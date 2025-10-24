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

use ArrayObject;
use Cake\Collection\Collection;
use Cake\Database\Expression\QueryExpression;
use Cake\Database\Expression\TupleComparison;
use Cake\Database\TypeFactory;
use Cake\Datasource\EntityInterface;
use Cake\Datasource\InvalidPropertyInterface;
use Cake\ORM\Association\BelongsToMany;
use Cake\Utility\Hash;
use InvalidArgumentException;

/**
 * Contains logic to convert array data into entities.
 *
 * Useful when converting request data into entities.
 *
 * @see \Cake\ORM\Table::newEntity()
 * @see \Cake\ORM\Table::newEntities()
 * @see \Cake\ORM\Table::patchEntity()
 * @see \Cake\ORM\Table::patchEntities()
 */
class Marshaller
{
    use AssociationsNormalizerTrait;

    /**
     * The table instance this marshaller is for.
     *
     * @var \Cake\ORM\Table
     */
    protected Table $_table;

    /**
     * Constructor.
     *
     * @param \Cake\ORM\Table $table The table this marshaller is for.
     */
    public function __construct(Table $table)
    {
        $this->_table = $table;
    }

    /**
     * Build the map of property => marshaling callable.
     *
     * @param array $data The data being marshaled.
     * @param array<string, mixed> $options List of options containing the 'associated' key.
     * @throws \InvalidArgumentException When associations do not exist.
     * @return array
     */
    protected function _buildPropertyMap(array $data, array $options): array
    {
        $map = [];
        $schema = $this->_table->getSchema();

        // Is a concrete column?
        foreach (array_keys($data) as $prop) {
            $prop = (string)$prop;
            $columnType = $schema->getColumnType($prop);
            if ($columnType) {
                $map[$prop] = fn($value) => TypeFactory::build($columnType)->marshal($value);
            }
        }

        // Map associations
        $options['associated'] ??= [];
        $include = $this->_normalizeAssociations($options['associated']);
        foreach ($include as $key => $nested) {
            if (is_int($key) && is_scalar($nested)) {
                $key = $nested;
                $nested = [];
            }

            $stringifiedKey = (string)$key;
            // If the key is not a special field like _ids or _joinData
            // it is a missing association that we should error on.
            if (!$this->_table->hasAssociation($stringifiedKey)) {
                if (
                    !str_starts_with($stringifiedKey, '_')
                    && (!isset($options['junctionProperty']) || $options['junctionProperty'] !== $stringifiedKey)
                ) {
                    throw new InvalidArgumentException(sprintf(
                        'Cannot marshal data for `%s` association. It is not associated with `%s`.',
                        $stringifiedKey,
                        $this->_table->getAlias(),
                    ));
                }
                continue;
            }
            $assoc = $this->_table->getAssociation($stringifiedKey);

            if (isset($options['forceNew'])) {
                $nested['forceNew'] = $options['forceNew'];
            }
            if (isset($options['isMerge'])) {
                $callback = function (
                    $value,
                    EntityInterface $entity,
                ) use (
                    $assoc,
                    $nested,
                ): array|EntityInterface|null {
                    $options = $nested + ['associated' => [], 'association' => $assoc];

                    return $this->_mergeAssociation(
                        $this->fieldValue($entity, $assoc->getProperty()),
                        $assoc,
                        $value,
                        $options,
                    );
                };
            } else {
                $callback = function ($value, $entity) use ($assoc, $nested): array|EntityInterface|null {
                    $options = $nested + ['associated' => []];

                    return $this->_marshalAssociation($assoc, $value, $options);
                };
            }
            $map[$assoc->getProperty()] = $callback;
        }

        $behaviors = $this->_table->behaviors();
        foreach ($behaviors->loaded() as $name) {
            $behavior = $behaviors->get($name);
            if ($behavior instanceof PropertyMarshalInterface) {
                $map += $behavior->buildMarshalMap($this, $map, $options);
            }
        }

        return $map;
    }

    /**
     * Hydrate one entity and its associated data.
     *
     * ### Options:
     *
     * - validate: Set to false to disable validation. Can also be a string of the validator ruleset to be applied.
     *   Defaults to true/default.
     * - associated: Associations listed here will be marshaled as well. Defaults to null.
     * - fields: An allowed list of fields to be assigned to the entity. If not present,
     *   the accessible fields list in the entity will be used. Defaults to null.
     * - accessibleFields: A list of fields to allow or deny in entity accessible fields. Defaults to null
     * - forceNew: When enabled, belongsToMany associations will have 'new' entities created
     *   when primary key values are set, and a record does not already exist. Normally primary key
     *   on missing entities would be ignored. Defaults to false.
     *
     * The above options can be used in each nested `associated` array. In addition to the above
     * options you can also use the `onlyIds` option for HasMany and BelongsToMany associations.
     * When true this option restricts the request data to only be read from `_ids`.
     *
     * ```
     * $result = $marshaller->one($data, [
     *   'associated' => ['Tags' => ['onlyIds' => true]]
     * ]);
     * ```
     *
     * ```
     * $result = $marshaller->one($data, [
     *   'associated' => [
     *     'Tags' => ['accessibleFields' => ['*' => true]]
     *   ]
     * ]);
     * ```
     *
     *  ```
     *  $result = $marshaller->one($data, [
     *    'associated' => [
     *      'Tags' => [
     *        'associated' => ['DeeperAssoc1', 'DeeperAssoc2']
     *      ]
     *    ]
     *  ]);
     *  ```
     *
     * @param array<string, mixed> $data The data to hydrate.
     * @param array<string, mixed> $options List of options
     * @return \Cake\Datasource\EntityInterface
     * @see \Cake\ORM\Table::newEntity()
     * @see \Cake\ORM\Entity::$_accessible
     */
    public function one(array $data, array $options = []): EntityInterface
    {
        [$data, $options] = $this->_prepareDataAndOptions($data, $options);

        $primaryKey = (array)$this->_table->getPrimaryKey();
        $entity = $this->_table->newEmptyEntity();

        if (isset($options['accessibleFields'])) {
            foreach ((array)$options['accessibleFields'] as $key => $value) {
                $entity->setAccess($key, $value);
            }
        }
        $errors = $this->_validate($data, $options['validate'], true);

        $options['isMerge'] = false;
        $propertyMap = $this->_buildPropertyMap($data, $options);
        $properties = [];
        /**
         * @var string $key
         */
        foreach ($data as $key => $value) {
            if (!empty($errors[$key])) {
                if ($entity instanceof InvalidPropertyInterface) {
                    $entity->setInvalidField($key, $value);
                }
                continue;
            }

            if ($value === '' && in_array($key, $primaryKey, true)) {
                // Skip marshaling '' for pk fields.
                continue;
            }
            if (isset($propertyMap[$key])) {
                $properties[$key] = $propertyMap[$key]($value, $entity);
            } else {
                $properties[$key] = $value;
            }
        }

        if (isset($options['fields'])) {
            foreach ((array)$options['fields'] as $field) {
                if (array_key_exists($field, $properties)) {
                    $entity->set($field, $properties[$field], ['asOriginal' => true]);
                }
            }
        } else {
            if (method_exists($entity, 'patch')) {
                $entity->patch($properties, ['asOriginal' => true]);
            } else {
                $entity->set($properties, ['asOriginal' => true]);
            }
        }

        // Don't flag clean association entities as
        // dirty so we don't persist empty records.
        foreach ($properties as $field => $value) {
            if ($value instanceof EntityInterface) {
                $entity->setDirty($field, $value->isDirty());
            }
        }

        $entity->setErrors($errors);
        $this->dispatchAfterMarshal($entity, $data, $options);

        return $entity;
    }

    /**
     * Returns the validation errors for a data set based on the passed options
     *
     * @param array $data The data to validate.
     * @param string|bool $validator Validator name or `true` for default validator.
     * @param bool $isNew Whether it is a new entity or one to be updated.
     * @return array The list of validation errors.
     * @throws \RuntimeException If no validator can be created.
     */
    protected function _validate(array $data, string|bool $validator, bool $isNew): array
    {
        if (!$validator) {
            return [];
        }

        if ($validator === true) {
            $validator = null;
        }

        return $this->_table->getValidator($validator)->validate($data, $isNew);
    }

    /**
     * Returns data and options prepared to validate and marshall.
     *
     * @param array<string, mixed> $data The data to prepare.
     * @param array<string, mixed> $options The options passed to this marshaller.
     * @return array An array containing prepared data and options.
     */
    protected function _prepareDataAndOptions(array $data, array $options): array
    {
        $options += ['validate' => true];

        $tableName = $this->_table->getAlias();
        if (isset($data[$tableName]) && is_array($data[$tableName])) {
            $data += $data[$tableName];
            unset($data[$tableName]);
        }

        $data = new ArrayObject($data);
        $options = new ArrayObject($options);
        $this->_table->dispatchEvent('Model.beforeMarshal', compact('data', 'options'));

        return [(array)$data, (array)$options];
    }

    /**
     * Create a new sub-marshaller and marshal the associated data.
     *
     * @param \Cake\ORM\Association $assoc The association to marshall
     * @param mixed $value The data to hydrate. If not an array, this method will return null.
     * @param array<string, mixed> $options List of options.
     * @return \Cake\Datasource\EntityInterface|array<\Cake\Datasource\EntityInterface>|null
     */
    protected function _marshalAssociation(Association $assoc, mixed $value, array $options): EntityInterface|array|null
    {
        if (!is_array($value)) {
            return null;
        }
        $targetTable = $assoc->getTarget();
        $marshaller = $targetTable->marshaller();
        $types = [Association::ONE_TO_ONE, Association::MANY_TO_ONE];
        $type = $assoc->type();
        if (in_array($type, $types, true)) {
            return $marshaller->one($value, $options);
        }
        if ($type === Association::ONE_TO_MANY || $type === Association::MANY_TO_MANY) {
            $hasIds = array_key_exists('_ids', $value);
            $onlyIds = array_key_exists('onlyIds', $options) && $options['onlyIds'];

            if ($hasIds && is_array($value['_ids'])) {
                return $this->_loadAssociatedByIds($assoc, $value['_ids']);
            }
            if ($hasIds || $onlyIds) {
                return [];
            }
        }
        if ($type === Association::MANY_TO_MANY) {
            assert($assoc instanceof BelongsToMany);

            return $marshaller->_belongsToMany($assoc, $value, $options);
        }

        return $marshaller->many($value, $options);
    }

    /**
     * Hydrate many entities and their associated data.
     *
     * ### Options:
     *
     * - validate: Set to false to disable validation. Can also be a string of the validator ruleset to be applied.
     *   Defaults to true/default.
     * - associated: Associations listed here will be marshaled as well. Defaults to null.
     * - fields: An allowed list of fields to be assigned to the entity. If not present,
     *   the accessible fields list in the entity will be used. Defaults to null.
     * - accessibleFields: A list of fields to allow or deny in entity accessible fields. Defaults to null
     * - forceNew: When enabled, belongsToMany associations will have 'new' entities created
     *   when primary key values are set, and a record does not already exist. Normally primary key
     *   on missing entities would be ignored. Defaults to false.
     *
     * @param array $data The data to hydrate.
     * @param array<string, mixed> $options List of options
     * @return array<\Cake\Datasource\EntityInterface> An array of hydrated records.
     * @see \Cake\ORM\Table::newEntities()
     * @see \Cake\ORM\Entity::$_accessible
     */
    public function many(array $data, array $options = []): array
    {
        $output = [];
        foreach ($data as $record) {
            if (!is_array($record)) {
                continue;
            }
            $output[] = $this->one($record, $options);
        }

        return $output;
    }

    /**
     * Marshals data for belongsToMany associations.
     *
     * Builds the related entities and handles the special casing
     * for junction table entities.
     *
     * @param \Cake\ORM\Association\BelongsToMany $assoc The association to marshal.
     * @param array $data The data to convert into entities.
     * @param array<string, mixed> $options List of options.
     * @return array<\Cake\Datasource\EntityInterface> An array of built entities.
     * @throws \BadMethodCallException
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     */
    protected function _belongsToMany(BelongsToMany $assoc, array $data, array $options = []): array
    {
        $associated = $options['associated'] ?? [];
        $forceNew = $options['forceNew'] ?? false;

        $data = array_values($data);

        $target = $assoc->getTarget();
        $primaryKey = array_flip((array)$target->getPrimaryKey());
        $records = [];
        $conditions = [];
        $primaryCount = count($primaryKey);
        $junctionProperty = $assoc->getJunctionProperty();
        $options += ['junctionProperty' => $junctionProperty];

        foreach ($data as $i => $row) {
            if (!is_array($row)) {
                continue;
            }
            if (array_intersect_key($primaryKey, $row) === $primaryKey) {
                $keys = array_intersect_key($row, $primaryKey);
                if (count($keys) === $primaryCount) {
                    $rowConditions = [];
                    foreach ($keys as $key => $value) {
                        $rowConditions[][$target->aliasField($key)] = $value;
                    }

                    if ($forceNew && !$target->exists($rowConditions)) {
                        $records[$i] = $this->one($row, $options);
                    }

                    $conditions = array_merge($conditions, $rowConditions);
                }
            } else {
                $records[$i] = $this->one($row, $options);
            }
        }

        if ($conditions) {
            /** @var \Traversable<\Cake\Datasource\EntityInterface> $results */
            $results = $target->find()
                ->andWhere(fn(QueryExpression $exp) => $exp->or($conditions))
                ->all();

            $keyFields = array_keys($primaryKey);

            $existing = [];
            foreach ($results as $row) {
                $k = implode(';', $row->extract($keyFields));
                $existing[$k] = $row;
            }

            foreach ($data as $i => $row) {
                $key = [];
                foreach ($keyFields as $k) {
                    if (isset($row[$k])) {
                        $key[] = $row[$k];
                    }
                }
                $key = implode(';', $key);

                // Update existing record and child associations
                if (isset($existing[$key])) {
                    $records[$i] = $this->merge($existing[$key], $row, $options);
                }
            }
        }

        $jointMarshaller = $assoc->junction()->marshaller();

        $nested = [];
        if (isset($associated[$junctionProperty])) {
            $nested = (array)$associated[$junctionProperty];
        }

        foreach ($records as $i => $record) {
            // Update junction table data in the junction property (_joinData).
            if (isset($data[$i][$junctionProperty])) {
                $joinData = $jointMarshaller->one($data[$i][$junctionProperty], $nested);
                $record->set($junctionProperty, $joinData);
            }
        }

        return $records;
    }

    /**
     * Loads a list of belongs to many from ids.
     *
     * @param \Cake\ORM\Association $assoc The association class for the belongsToMany association.
     * @param array $ids The list of ids to load.
     * @return array<\Cake\Datasource\EntityInterface> An array of entities.
     */
    protected function _loadAssociatedByIds(Association $assoc, array $ids): array
    {
        if (!$ids) {
            return [];
        }

        $target = $assoc->getTarget();
        $primaryKey = (array)$target->getPrimaryKey();
        $multi = count($primaryKey) > 1;
        $primaryKey = array_map($target->aliasField(...), $primaryKey);

        if ($multi) {
            $first = current($ids);
            if (!is_array($first) || count($first) !== count($primaryKey)) {
                return [];
            }
            $type = [];
            $schema = $target->getSchema();
            foreach ((array)$target->getPrimaryKey() as $column) {
                $type[] = $schema->getColumnType($column);
            }
            $filter = new TupleComparison($primaryKey, $ids, $type, 'IN');
        } else {
            $filter = [$primaryKey[0] . ' IN' => $ids];
        }

        return $target->find()->where($filter)->toArray();
    }

    /**
     * Merges `$data` into `$entity` and recursively does the same for each one of
     * the association names passed in `$options`. When merging associations, if an
     * entity is not present in the parent entity for a given association, a new one
     * will be created.
     *
     * When merging HasMany or BelongsToMany associations, all the entities in the
     * `$data` array will appear, those that can be matched by primary key will get
     * the data merged, but those that cannot, will be discarded. `ids` option can be used
     * to determine whether the association must use the `_ids` format.
     *
     * ### Options:
     *
     * - associated: Associations listed here will be marshaled as well.
     * - validate: Whether to validate data before hydrating the entities. Can
     *   also be set to a string to use a specific validator. Defaults to true/default.
     * - fields: An allowed list of fields to be assigned to the entity. If not present
     *   the accessible fields list in the entity will be used.
     * - accessibleFields: A list of fields to allow or deny in entity accessible fields.
     *
     * The above options can be used in each nested `associated` array. In addition to the above
     * options you can also use the `onlyIds` option for HasMany and BelongsToMany associations.
     * When true this option restricts the request data to only be read from `_ids`.
     *
     * ```
     * $result = $marshaller->merge($entity, $data, [
     *   'associated' => ['Tags' => ['onlyIds' => true]]
     * ]);
     * ```
     *
     * ```
     * $result = $marshaller->merge($entity, $data, [
     *   'associated' => [
     *     'Tags' => [
     *       'associated' => ['DeeperAssoc1', 'DeeperAssoc2']
     *     ]
     *   ]
     * ]);
     * ```
     *
     * @param \Cake\Datasource\EntityInterface $entity the entity that will get the
     * data merged in
     * @param array $data key value list of fields to be merged into the entity
     * @param array<string, mixed> $options List of options.
     * @return \Cake\Datasource\EntityInterface
     * @see \Cake\ORM\Entity::$_accessible
     */
    public function merge(EntityInterface $entity, array $data, array $options = []): EntityInterface
    {
        [$data, $options] = $this->_prepareDataAndOptions($data, $options);

        $isNew = $entity->isNew();
        $keys = [];

        if (!$isNew) {
            $keys = $entity->extract((array)$this->_table->getPrimaryKey());
        }

        if (isset($options['accessibleFields'])) {
            foreach ((array)$options['accessibleFields'] as $key => $value) {
                $entity->setAccess($key, $value);
            }
        }

        $errors = $this->_validate($data + $keys, $options['validate'], $isNew);
        $options['isMerge'] = true;
        $propertyMap = $this->_buildPropertyMap($data, $options);
        $properties = [];
        /**
         * @var string $key
         */
        foreach ($data as $key => $value) {
            if (!empty($errors[$key])) {
                if ($entity instanceof InvalidPropertyInterface) {
                    $entity->setInvalidField($key, $value);
                }
                continue;
            }

            if (isset($propertyMap[$key])) {
                $value = $propertyMap[$key]($value, $entity);
            }
            $properties[$key] = $value;
        }

        $entity->setErrors($errors);
        if (!isset($options['fields'])) {
            if (method_exists($entity, 'patch')) {
                $entity->patch($properties);
            } else {
                $entity->set($properties);
            }

            foreach ($properties as $field => $value) {
                if ($value instanceof EntityInterface) {
                    $entity->setDirty($field, $value->isDirty());
                }
            }
            $this->dispatchAfterMarshal($entity, $data, $options);

            return $entity;
        }

        foreach ((array)$options['fields'] as $field) {
            assert(is_string($field));
            if (!array_key_exists($field, $properties)) {
                continue;
            }
            $entity->set($field, $properties[$field]);
            if ($properties[$field] instanceof EntityInterface) {
                $entity->setDirty($field, $properties[$field]->isDirty());
            }
        }
        $this->dispatchAfterMarshal($entity, $data, $options);

        return $entity;
    }

    /**
     * Merges each of the elements from `$data` into each of the entities in `$entities`
     * and recursively does the same for each of the association names passed in
     * `$options`. When merging associations, if an entity is not present in the parent
     * entity for a given association, a new one will be created.
     *
     * Records in `$data` are matched against the entities using the primary key
     * column. Entries in `$entities` that cannot be matched to any record in
     * `$data` will be discarded. Records in `$data` that could not be matched will
     * be marshaled as a new entity.
     *
     * When merging HasMany or BelongsToMany associations, all the entities in the
     * `$data` array will appear, those that can be matched by primary key will get
     * the data merged, but those that cannot, will be discarded.
     *
     * ### Options:
     *
     * - validate: Whether to validate data before hydrating the entities. Can
     *   also be set to a string to use a specific validator. Defaults to true/default.
     * - associated: Associations listed here will be marshaled as well.
     * - fields: An allowed list of fields to be assigned to the entity. If not present,
     *   the accessible fields list in the entity will be used.
     * - accessibleFields: A list of fields to allow or deny in entity accessible fields.
     *
     * @param iterable<\Cake\Datasource\EntityInterface> $entities the entities that will get the
     *   data merged in
     * @param array $data list of arrays to be merged into the entities
     * @param array<string, mixed> $options List of options.
     * @return array<\Cake\Datasource\EntityInterface>
     * @see \Cake\ORM\Entity::$_accessible
     */
    public function mergeMany(iterable $entities, array $data, array $options = []): array
    {
        $primary = (array)$this->_table->getPrimaryKey();

        $indexed = (new Collection($data))
            ->groupBy(function ($el) use ($primary) {
                $keys = [];
                foreach ($primary as $key) {
                    $keys[] = $el[$key] ?? '';
                }

                return implode(';', $keys);
            })
            ->map(function ($element, $key) {
                return $key === '' ? $element : $element[0];
            })
            ->toArray();

        $new = $indexed[''] ?? [];
        unset($indexed['']);
        $output = [];

        foreach ($entities as $entity) {
            if (!($entity instanceof EntityInterface)) {
                continue;
            }

            $key = implode(';', $entity->extract($primary));
            if (!isset($indexed[$key])) {
                continue;
            }

            $output[] = $this->merge($entity, $indexed[$key], $options);
            unset($indexed[$key]);
        }

        $conditions = (new Collection($indexed))
            ->map(function ($data, $key) {
                return explode(';', (string)$key);
            })
            ->filter(fn($keys) => count(Hash::filter($keys)) === count($primary))
            ->reduce(function ($conditions, $keys) use ($primary) {
                $fields = array_map($this->_table->aliasField(...), $primary);
                $conditions['OR'][] = array_combine($fields, $keys);

                return $conditions;
            }, ['OR' => []]);
        $maybeExistentQuery = $this->_table->find()->where($conditions);

        if ($indexed && count($maybeExistentQuery->clause('where'))) {
            /** @var \Traversable<\Cake\Datasource\EntityInterface> $existent */
            $existent = $maybeExistentQuery->all();
            foreach ($existent as $entity) {
                $key = implode(';', $entity->extract($primary));
                if (isset($indexed[$key])) {
                    $output[] = $this->merge($entity, $indexed[$key], $options);
                    unset($indexed[$key]);
                }
            }
        }

        foreach ((new Collection($indexed))->append($new) as $value) {
            if (!is_array($value)) {
                continue;
            }
            $output[] = $this->one($value, $options);
        }

        return $output;
    }

    /**
     * Creates a new sub-marshaller and merges the associated data.
     *
     * @param \Cake\Datasource\EntityInterface|non-empty-array<\Cake\Datasource\EntityInterface>|null $original The original entity
     * @param \Cake\ORM\Association $assoc The association to merge
     * @param mixed $value The array of data to hydrate. If not an array, this method will return null.
     * @param array<string, mixed> $options List of options.
     * @return \Cake\Datasource\EntityInterface|array<\Cake\Datasource\EntityInterface>|null
     */
    protected function _mergeAssociation(
        EntityInterface|array|null $original,
        Association $assoc,
        mixed $value,
        array $options,
    ): EntityInterface|array|null {
        if (!$original) {
            return $this->_marshalAssociation($assoc, $value, $options);
        }
        if (!is_array($value)) {
            return null;
        }

        $targetTable = $assoc->getTarget();
        $marshaller = $targetTable->marshaller();
        $types = [Association::ONE_TO_ONE, Association::MANY_TO_ONE];
        $type = $assoc->type();
        if (in_array($type, $types, true)) {
            /** @var \Cake\Datasource\EntityInterface $original */
            return $marshaller->merge($original, $value, $options);
        }
        if ($type === Association::MANY_TO_MANY && is_array($original)) {
            assert($assoc instanceof BelongsToMany);

            return $marshaller->_mergeBelongsToMany($original, $assoc, $value, $options);
        }

        if ($type === Association::ONE_TO_MANY) {
            $hasIds = array_key_exists('_ids', $value);
            $onlyIds = array_key_exists('onlyIds', $options) && $options['onlyIds'];
            if ($hasIds && is_array($value['_ids'])) {
                return $this->_loadAssociatedByIds($assoc, $value['_ids']);
            }
            if ($hasIds || $onlyIds) {
                return [];
            }
        }

        /**
         * @var non-empty-array<\Cake\Datasource\EntityInterface> $original
         */
        return $marshaller->mergeMany($original, $value, $options);
    }

    /**
     * Creates a new sub-marshaller and merges the associated data for a BelongstoMany
     * association.
     *
     * @param array<\Cake\Datasource\EntityInterface> $original The original entities list.
     * @param \Cake\ORM\Association\BelongsToMany $assoc The association to marshall
     * @param array $value The data to hydrate
     * @param array<string, mixed> $options List of options.
     * @return array<\Cake\Datasource\EntityInterface>
     */
    protected function _mergeBelongsToMany(array $original, BelongsToMany $assoc, array $value, array $options): array
    {
        $associated = $options['associated'] ?? [];

        $hasIds = array_key_exists('_ids', $value);
        $onlyIds = array_key_exists('onlyIds', $options) && $options['onlyIds'];

        if ($hasIds && is_array($value['_ids'])) {
            return $this->_loadAssociatedByIds($assoc, $value['_ids']);
        }
        if ($hasIds || $onlyIds) {
            return [];
        }

        $junctionProperty = $assoc->getJunctionProperty();
        if ($associated && !in_array($junctionProperty, $associated, true) && !isset($associated[$junctionProperty])) {
            return $this->mergeMany($original, $value, $options);
        }

        return $this->_mergeJoinData($original, $assoc, $value, $options);
    }

    /**
     * Merge the special junction property (_joinData) into the entity set.
     *
     * @param array<\Cake\Datasource\EntityInterface> $original The original entities list.
     * @param \Cake\ORM\Association\BelongsToMany $assoc The association to marshall
     * @param array $value The data to hydrate
     * @param array<string, mixed> $options List of options.
     * @return array<\Cake\Datasource\EntityInterface> An array of entities
     */
    protected function _mergeJoinData(array $original, BelongsToMany $assoc, array $value, array $options): array
    {
        $associated = $options['associated'] ?? [];
        $extra = [];
        $junctionProperty = $assoc->getJunctionProperty();
        foreach ($original as $entity) {
            // Mark joinData as accessible so we can marshal it properly.
            $entity->setAccess($junctionProperty, true);

            $joinData = $this->fieldValue($entity, $junctionProperty);
            if ($joinData instanceof EntityInterface) {
                $extra[spl_object_hash($entity)] = $joinData;
            }
        }

        $joint = $assoc->junction();
        $marshaller = $joint->marshaller();

        $nested = [];
        if (isset($associated[$junctionProperty])) {
            $nested = (array)$associated[$junctionProperty];
        }

        $options['accessibleFields'] = [$junctionProperty => true];

        $records = $this->mergeMany($original, $value, $options);
        foreach ($records as $record) {
            $hash = spl_object_hash($record);
            $value = $this->fieldValue($record, $junctionProperty);

            // Already an entity, no further marshaling required.
            if ($value instanceof EntityInterface) {
                continue;
            }

            // Scalar data can't be handled
            if (!is_array($value)) {
                $record->unset($junctionProperty);
                continue;
            }

            // Marshal data into the old object, or make a new joinData object.
            if (isset($extra[$hash])) {
                $record->set($junctionProperty, $marshaller->merge($extra[$hash], $value, $nested));
            } else {
                $joinData = $marshaller->one($value, $nested);
                $record->set($junctionProperty, $joinData);
            }
        }

        return $records;
    }

    /**
     * dispatch Model.afterMarshal event.
     *
     * @param \Cake\Datasource\EntityInterface $entity The entity that was marshaled.
     * @param array $data readOnly $data to use.
     * @param array<string, mixed> $options List of options that are readOnly.
     * @return void
     */
    protected function dispatchAfterMarshal(EntityInterface $entity, array $data, array $options = []): void
    {
        $data = new ArrayObject($data);
        $options = new ArrayObject($options);
        $this->_table->dispatchEvent('Model.afterMarshal', compact('entity', 'data', 'options'));
    }

    /**
     * Get the value of a field from an entity.
     *
     * It checks whether the field exists in the entity before getting the value
     * to avoid MissingPropertyException if `requireFieldPresence` is enabled.
     *
     * @param \Cake\Datasource\EntityInterface $entity The entity to extract the field from.
     * @param string $field The field to extract.
     * @return mixed
     */
    protected function fieldValue(EntityInterface $entity, string $field): mixed
    {
        return $entity->has($field) ? $entity->get($field) : null;
    }
}
