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
namespace Cake\ORM;

use ArrayObject;
use Cake\Collection\Collection;
use Cake\Database\Expression\TupleComparison;
use Cake\Database\Type;
use Cake\Datasource\EntityInterface;
use Cake\Datasource\InvalidPropertyInterface;
use RuntimeException;

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
    protected $_table;

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
     * Build the map of property => marshalling callable.
     *
     * @param array $data The data being marshalled.
     * @param array $options List of options containing the 'associated' key.
     * @throws \InvalidArgumentException When associations do not exist.
     * @return array
     */
    protected function _buildPropertyMap($data, $options)
    {
        $map = [];
        $schema = $this->_table->schema();

        // Is a concrete column?
        foreach (array_keys($data) as $prop) {
            $columnType = $schema->columnType($prop);
            if ($columnType) {
                $map[$prop] = function ($value, $entity) use ($columnType) {
                    return Type::build($columnType)->marshal($value);
                };
            }
        }

        // Map associations
        if (!isset($options['associated'])) {
            $options['associated'] = [];
        }
        $include = $this->_normalizeAssociations($options['associated']);
        foreach ($include as $key => $nested) {
            if (is_int($key) && is_scalar($nested)) {
                $key = $nested;
                $nested = [];
            }
            $assoc = $this->_table->association($key);
            // If the key is not a special field like _ids or _joinData
            // it is a missing association that we should error on.
            if (!$assoc) {
                if (substr($key, 0, 1) !== '_') {
                    throw new \InvalidArgumentException(sprintf(
                        'Cannot marshal data for "%s" association. It is not associated with "%s".',
                        $key,
                        $this->_table->alias()
                    ));
                }
                continue;
            }
            if (isset($options['forceNew'])) {
                $nested['forceNew'] = $options['forceNew'];
            }
            if (isset($options['isMerge'])) {
                $callback = function ($value, $entity) use ($assoc, $nested) {
                    $options = $nested + ['associated' => []];

                    return $this->_mergeAssociation($entity->get($assoc->property()), $assoc, $value, $options);
                };
            } else {
                $callback = function ($value, $entity) use ($assoc, $nested) {
                    $options = $nested + ['associated' => []];

                    return $this->_marshalAssociation($assoc, $value, $options);
                };
            }
            $map[$assoc->property()] = $callback;
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
     * - associated: Associations listed here will be marshalled as well. Defaults to null.
     * - fieldList: (deprecated) Since 3.4.0. Use fields instead.
     * - fields: A whitelist of fields to be assigned to the entity. If not present,
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
     * @param array $data The data to hydrate.
     * @param array $options List of options
     * @return \Cake\ORM\Entity
     * @see \Cake\ORM\Table::newEntity()
     * @see \Cake\ORM\Entity::$_accessible
     */
    public function one(array $data, array $options = [])
    {
        list($data, $options) = $this->_prepareDataAndOptions($data, $options);

        $primaryKey = (array)$this->_table->primaryKey();
        $entityClass = $this->_table->entityClass();
        /* @var Entity $entity */
        $entity = new $entityClass();
        $entity->source($this->_table->registryAlias());

        if (isset($options['accessibleFields'])) {
            foreach ((array)$options['accessibleFields'] as $key => $value) {
                $entity->accessible($key, $value);
            }
        }
        $errors = $this->_validate($data, $options, true);

        $options['isMerge'] = false;
        $propertyMap = $this->_buildPropertyMap($data, $options);
        $properties = [];
        foreach ($data as $key => $value) {
            if (!empty($errors[$key])) {
                if ($entity instanceof InvalidPropertyInterface) {
                    $entity->invalid($key, $value);
                }
                continue;
            }

            if ($value === '' && in_array($key, $primaryKey, true)) {
                // Skip marshalling '' for pk fields.
                continue;
            } elseif (isset($propertyMap[$key])) {
                $properties[$key] = $propertyMap[$key]($value, $entity);
            } else {
                $properties[$key] = $value;
            }
        }

        if (isset($options['fields'])) {
            foreach ((array)$options['fields'] as $field) {
                if (array_key_exists($field, $properties)) {
                    $entity->set($field, $properties[$field]);
                }
            }
        } else {
            $entity->set($properties);
        }

        $entity->errors($errors);

        return $entity;
    }

    /**
     * Returns the validation errors for a data set based on the passed options
     *
     * @param array $data The data to validate.
     * @param array $options The options passed to this marshaller.
     * @param bool $isNew Whether it is a new entity or one to be updated.
     * @return array The list of validation errors.
     * @throws \RuntimeException If no validator can be created.
     */
    protected function _validate($data, $options, $isNew)
    {
        if (!$options['validate']) {
            return [];
        }
        if ($options['validate'] === true) {
            $options['validate'] = $this->_table->validator('default');
        }
        if (is_string($options['validate'])) {
            $options['validate'] = $this->_table->validator($options['validate']);
        }
        if (!is_object($options['validate'])) {
            throw new RuntimeException(
                sprintf('validate must be a boolean, a string or an object. Got %s.', gettype($options['validate']))
            );
        }

        return $options['validate']->errors($data, $isNew);
    }

    /**
     * Returns data and options prepared to validate and marshall.
     *
     * @param array $data The data to prepare.
     * @param array $options The options passed to this marshaller.
     * @return array An array containing prepared data and options.
     */
    protected function _prepareDataAndOptions($data, $options)
    {
        $options += ['validate' => true];

        if (!isset($options['fields']) && isset($options['fieldList'])) {
            $options['fields'] = $options['fieldList'];
            unset($options['fieldList']);
        }

        $tableName = $this->_table->alias();
        if (isset($data[$tableName])) {
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
     * @param array $value The data to hydrate
     * @param array $options List of options.
     * @return mixed
     */
    protected function _marshalAssociation($assoc, $value, $options)
    {
        if (!is_array($value)) {
            return null;
        }
        $targetTable = $assoc->target();
        $marshaller = $targetTable->marshaller();
        $types = [Association::ONE_TO_ONE, Association::MANY_TO_ONE];
        if (in_array($assoc->type(), $types)) {
            return $marshaller->one($value, (array)$options);
        }
        if ($assoc->type() === Association::ONE_TO_MANY || $assoc->type() === Association::MANY_TO_MANY) {
            $hasIds = array_key_exists('_ids', $value);
            $onlyIds = array_key_exists('onlyIds', $options) && $options['onlyIds'];

            if ($hasIds && is_array($value['_ids'])) {
                return $this->_loadAssociatedByIds($assoc, $value['_ids']);
            }
            if ($hasIds || $onlyIds) {
                return [];
            }
        }
        if ($assoc->type() === Association::MANY_TO_MANY) {
            return $marshaller->_belongsToMany($assoc, $value, (array)$options);
        }

        return $marshaller->many($value, (array)$options);
    }

    /**
     * Hydrate many entities and their associated data.
     *
     * ### Options:
     *
     * - validate: Set to false to disable validation. Can also be a string of the validator ruleset to be applied.
     *   Defaults to true/default.
     * - associated: Associations listed here will be marshalled as well. Defaults to null.
     * - fieldList: (deprecated) Since 3.4.0. Use fields instead
     * - fields: A whitelist of fields to be assigned to the entity. If not present,
     *   the accessible fields list in the entity will be used. Defaults to null.
     * - accessibleFields: A list of fields to allow or deny in entity accessible fields. Defaults to null
     * - forceNew: When enabled, belongsToMany associations will have 'new' entities created
     *   when primary key values are set, and a record does not already exist. Normally primary key
     *   on missing entities would be ignored. Defaults to false.
     *
     * @param array $data The data to hydrate.
     * @param array $options List of options
     * @return array An array of hydrated records.
     * @see \Cake\ORM\Table::newEntities()
     * @see \Cake\ORM\Entity::$_accessible
     */
    public function many(array $data, array $options = [])
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
     * @param \Cake\ORM\Association $assoc The association to marshal.
     * @param array $data The data to convert into entities.
     * @param array $options List of options.
     * @return array An array of built entities.
     */
    protected function _belongsToMany(Association $assoc, array $data, $options = [])
    {
        $associated = isset($options['associated']) ? $options['associated'] : [];
        $forceNew = isset($options['forceNew']) ? $options['forceNew'] : false;

        $data = array_values($data);

        $target = $assoc->target();
        $primaryKey = array_flip((array)$target->primaryKey());
        $records = $conditions = [];
        $primaryCount = count($primaryKey);
        $conditions = [];

        foreach ($data as $i => $row) {
            if (!is_array($row)) {
                continue;
            }
            if (array_intersect_key($primaryKey, $row) === $primaryKey) {
                $keys = array_intersect_key($row, $primaryKey);
                if (count($keys) === $primaryCount) {
                    $rowConditions = [];
                    foreach ($keys as $key => $value) {
                        $rowConditions[][$target->aliasfield($key)] = $value;
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

        if (!empty($conditions)) {
            $query = $target->find();
            $query->andWhere(function ($exp) use ($conditions) {
                return $exp->or_($conditions);
            });

            $keyFields = array_keys($primaryKey);

            $existing = [];
            foreach ($query as $row) {
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
                    $records[$i] = $this->merge($existing[$key], $data[$i], $options);
                }
            }
        }

        $jointMarshaller = $assoc->junction()->marshaller();

        $nested = [];
        if (isset($associated['_joinData'])) {
            $nested = (array)$associated['_joinData'];
        }

        foreach ($records as $i => $record) {
            // Update junction table data in _joinData.
            if (isset($data[$i]['_joinData'])) {
                $joinData = $jointMarshaller->one($data[$i]['_joinData'], $nested);
                $record->set('_joinData', $joinData);
            }
        }

        return $records;
    }

    /**
     * Loads a list of belongs to many from ids.
     *
     * @param \Cake\ORM\Association $assoc The association class for the belongsToMany association.
     * @param array $ids The list of ids to load.
     * @return array An array of entities.
     */
    protected function _loadAssociatedByIds($assoc, $ids)
    {
        if (empty($ids)) {
            return [];
        }

        $target = $assoc->target();
        $primaryKey = (array)$target->primaryKey();
        $multi = count($primaryKey) > 1;
        $primaryKey = array_map([$target, 'aliasField'], $primaryKey);

        if ($multi) {
            if (count(current($ids)) !== count($primaryKey)) {
                return [];
            }
            $filter = new TupleComparison($primaryKey, $ids, [], 'IN');
        } else {
            $filter = [$primaryKey[0] . ' IN' => $ids];
        }

        return $target->find()->where($filter)->toArray();
    }

    /**
     * Loads a list of belongs to many from ids.
     *
     * @param \Cake\ORM\Association $assoc The association class for the belongsToMany association.
     * @param array $ids The list of ids to load.
     * @return array An array of entities.
     * @deprecated Use _loadAssociatedByIds()
     */
    protected function _loadBelongsToMany($assoc, $ids)
    {
        return $this->_loadAssociatedByIds($assoc, $ids);
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
     * - associated: Associations listed here will be marshalled as well.
     * - validate: Whether or not to validate data before hydrating the entities. Can
     *   also be set to a string to use a specific validator. Defaults to true/default.
     * - fieldList: (deprecated) Since 3.4.0. Use fields instead
     * - fields: A whitelist of fields to be assigned to the entity. If not present
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
     * @param \Cake\Datasource\EntityInterface $entity the entity that will get the
     * data merged in
     * @param array $data key value list of fields to be merged into the entity
     * @param array $options List of options.
     * @return \Cake\Datasource\EntityInterface
     * @see \Cake\ORM\Entity::$_accessible
     */
    public function merge(EntityInterface $entity, array $data, array $options = [])
    {
        list($data, $options) = $this->_prepareDataAndOptions($data, $options);

        $isNew = $entity->isNew();
        $keys = [];

        if (!$isNew) {
            $keys = $entity->extract((array)$this->_table->primaryKey());
        }

        if (isset($options['accessibleFields'])) {
            foreach ((array)$options['accessibleFields'] as $key => $value) {
                $entity->accessible($key, $value);
            }
        }

        $errors = $this->_validate($data + $keys, $options, $isNew);
        $schema = $this->_table->schema();
        $options['isMerge'] = true;
        $propertyMap = $this->_buildPropertyMap($data, $options);
        $properties = $marshalledAssocs = [];
        foreach ($data as $key => $value) {
            if (!empty($errors[$key])) {
                if ($entity instanceof InvalidPropertyInterface) {
                    $entity->invalid($key, $value);
                }
                continue;
            }
            $original = $entity->get($key);

            if (isset($propertyMap[$key])) {
                $value = $propertyMap[$key]($value, $entity);

                // Don't dirty scalar values and objects that didn't
                // change. Arrays will always be marked as dirty because
                // the original/updated list could contain references to the
                // same objects, even though those objects may have changed internally.
                if ((is_scalar($value) && $original === $value) ||
                    ($value === null && $original === $value) ||
                    (is_object($value) && !($value instanceof EntityInterface) && $original == $value)
                ) {
                    continue;
                }
            }
            $properties[$key] = $value;
        }

        $entity->errors($errors);
        if (!isset($options['fields'])) {
            $entity->set($properties);

            foreach ($properties as $field => $value) {
                if ($value instanceof EntityInterface) {
                    $entity->dirty($field, $value->dirty());
                }
            }

            return $entity;
        }

        foreach ((array)$options['fields'] as $field) {
            if (array_key_exists($field, $properties)) {
                $entity->set($field, $properties[$field]);
                if ($properties[$field] instanceof EntityInterface) {
                    $entity->dirty($field, $properties[$field]->dirty());
                }
            }
        }

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
     * be marshalled as a new entity.
     *
     * When merging HasMany or BelongsToMany associations, all the entities in the
     * `$data` array will appear, those that can be matched by primary key will get
     * the data merged, but those that cannot, will be discarded.
     *
     * ### Options:
     *
     * - validate: Whether or not to validate data before hydrating the entities. Can
     *   also be set to a string to use a specific validator. Defaults to true/default.
     * - associated: Associations listed here will be marshalled as well.
     * - fieldList: (deprecated) Since 3.4.0. Use fields instead
     * - fields: A whitelist of fields to be assigned to the entity. If not present,
     *   the accessible fields list in the entity will be used.
     * - accessibleFields: A list of fields to allow or deny in entity accessible fields.
     *
     * @param array|\Traversable $entities the entities that will get the
     *   data merged in
     * @param array $data list of arrays to be merged into the entities
     * @param array $options List of options.
     * @return array
     * @see \Cake\ORM\Entity::$_accessible
     */
    public function mergeMany($entities, array $data, array $options = [])
    {
        $primary = (array)$this->_table->primaryKey();

        $indexed = (new Collection($data))
            ->groupBy(function ($el) use ($primary) {
                $keys = [];
                foreach ($primary as $key) {
                    $keys[] = isset($el[$key]) ? $el[$key] : '';
                }

                return implode(';', $keys);
            })
            ->map(function ($element, $key) {
                return $key === '' ? $element : $element[0];
            })
            ->toArray();

        $new = isset($indexed[null]) ? $indexed[null] : [];
        unset($indexed[null]);
        $output = [];

        foreach ($entities as $entity) {
            if (!($entity instanceof EntityInterface)) {
                continue;
            }

            $key = implode(';', $entity->extract($primary));
            if ($key === null || !isset($indexed[$key])) {
                continue;
            }

            $output[] = $this->merge($entity, $indexed[$key], $options);
            unset($indexed[$key]);
        }

        $maybeExistentQuery = (new Collection($indexed))
            ->map(function ($data, $key) {
                return explode(';', $key);
            })
            ->filter(function ($keys) use ($primary) {
                return count(array_filter($keys, 'strlen')) === count($primary);
            })
            ->reduce(function ($query, $keys) use ($primary) {
                $fields = array_map([$this->_table, 'aliasField'], $primary);

                return $query->orWhere($query->newExpr()->and_(array_combine($fields, $keys)));
            }, $this->_table->find());

        if (!empty($indexed) && count($maybeExistentQuery->clause('where'))) {
            foreach ($maybeExistentQuery as $entity) {
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
     * @param \Cake\Datasource\EntityInterface $original The original entity
     * @param \Cake\ORM\Association $assoc The association to merge
     * @param array $value The data to hydrate
     * @param array $options List of options.
     * @return mixed
     */
    protected function _mergeAssociation($original, $assoc, $value, $options)
    {
        if (!$original) {
            return $this->_marshalAssociation($assoc, $value, $options);
        }

        $targetTable = $assoc->target();
        $marshaller = $targetTable->marshaller();
        $types = [Association::ONE_TO_ONE, Association::MANY_TO_ONE];
        if (in_array($assoc->type(), $types)) {
            return $marshaller->merge($original, $value, (array)$options);
        }
        if ($assoc->type() === Association::MANY_TO_MANY) {
            return $marshaller->_mergeBelongsToMany($original, $assoc, $value, (array)$options);
        }

        return $marshaller->mergeMany($original, $value, (array)$options);
    }

    /**
     * Creates a new sub-marshaller and merges the associated data for a BelongstoMany
     * association.
     *
     * @param \Cake\Datasource\EntityInterface $original The original entity
     * @param \Cake\ORM\Association $assoc The association to marshall
     * @param array $value The data to hydrate
     * @param array $options List of options.
     * @return array
     */
    protected function _mergeBelongsToMany($original, $assoc, $value, $options)
    {
        $associated = isset($options['associated']) ? $options['associated'] : [];

        $hasIds = array_key_exists('_ids', $value);
        $onlyIds = array_key_exists('onlyIds', $options) && $options['onlyIds'];

        if ($hasIds && is_array($value['_ids'])) {
            return $this->_loadAssociatedByIds($assoc, $value['_ids']);
        }
        if ($hasIds || $onlyIds) {
            return [];
        }

        if (!empty($associated) && !in_array('_joinData', $associated) && !isset($associated['_joinData'])) {
            return $this->mergeMany($original, $value, $options);
        }

        return $this->_mergeJoinData($original, $assoc, $value, $options);
    }

    /**
     * Merge the special _joinData property into the entity set.
     *
     * @param \Cake\Datasource\EntityInterface $original The original entity
     * @param \Cake\ORM\Association $assoc The association to marshall
     * @param array $value The data to hydrate
     * @param array $options List of options.
     * @return array An array of entities
     */
    protected function _mergeJoinData($original, $assoc, $value, $options)
    {
        $associated = isset($options['associated']) ? $options['associated'] : [];
        $extra = [];
        foreach ($original as $entity) {
            // Mark joinData as accessible so we can marshal it properly.
            $entity->accessible('_joinData', true);

            $joinData = $entity->get('_joinData');
            if ($joinData && $joinData instanceof EntityInterface) {
                $extra[spl_object_hash($entity)] = $joinData;
            }
        }

        $joint = $assoc->junction();
        $marshaller = $joint->marshaller();

        $nested = [];
        if (isset($associated['_joinData'])) {
            $nested = (array)$associated['_joinData'];
        }

        $options['accessibleFields'] = ['_joinData' => true];

        $records = $this->mergeMany($original, $value, $options);
        foreach ($records as $record) {
            $hash = spl_object_hash($record);
            $value = $record->get('_joinData');

            // Already an entity, no further marshalling required.
            if ($value instanceof EntityInterface) {
                continue;
            }

            // Scalar data can't be handled
            if (!is_array($value)) {
                $record->unsetProperty('_joinData');
                continue;
            }

            // Marshal data into the old object, or make a new joinData object.
            if (isset($extra[$hash])) {
                $record->set('_joinData', $marshaller->merge($extra[$hash], $value, $nested));
            } elseif (is_array($value)) {
                $joinData = $marshaller->one($value, $nested);
                $record->set('_joinData', $joinData);
            }
        }

        return $records;
    }
}
