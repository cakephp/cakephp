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

use Cake\Collection\Collection;
use Cake\Database\Expression\TupleComparison;
use Cake\Database\Type;
use Cake\Datasource\EntityInterface;
use Cake\ORM\Association;
use Cake\ORM\AssociationsNormalizerTrait;
use Cake\ORM\Table;

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
class Marshaller {

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
	public function __construct(Table $table) {
		$this->_table = $table;
	}

/**
 * Build the map of property => association names.
 *
 * @param array $options List of options containing the 'associated' key.
 * @return array
 */
	protected function _buildPropertyMap($options) {
		if (empty($options['associated'])) {
			return [];
		}

		$include = $options['associated'];
		$map = [];
		$include = $this->_normalizeAssociations($include);
		foreach ($include as $key => $nested) {
			if (is_int($key) && is_scalar($nested)) {
				$key = $nested;
				$nested = [];
			}
			$assoc = $this->_table->association($key);
			if ($assoc) {
				$map[$assoc->property()] = ['association' => $assoc] + $nested + ['associated' => []];
			}
		}
		return $map;
	}

/**
 * Hydrate one entity and its associated data.
 *
 * ### Options:
 *
 * * associated: Associations listed here will be marshalled as well.
 * * fieldList: A whitelist of fields to be assigned to the entity. If not present,
 *   the accessible fields list in the entity will be used.
 * * accessibleFields: A list of fields to allow or deny in entity accessible fields.
 *
 * @param array $data The data to hydrate.
 * @param array $options List of options
 * @return \Cake\ORM\Entity
 * @see \Cake\ORM\Table::newEntity()
 */
	public function one(array $data, array $options = []) {
		$propertyMap = $this->_buildPropertyMap($options);

		$schema = $this->_table->schema();
		$tableName = $this->_table->alias();
		$entityClass = $this->_table->entityClass();
		$entity = new $entityClass();
		$entity->source($this->_table->alias());

		if (isset($data[$tableName])) {
			$data = $data[$tableName];
		}

		if (isset($options['accessibleFields'])) {
			foreach ((array)$options['accessibleFields'] as $key => $value) {
				$entity->accessible($key, $value);
			}
		}

		$primaryKey = $schema->primaryKey();
		$properties = [];
		foreach ($data as $key => $value) {
			$columnType = $schema->columnType($key);
			if (isset($propertyMap[$key])) {
				$assoc = $propertyMap[$key]['association'];
				$value = $this->_marshalAssociation($assoc, $value, $propertyMap[$key]);
			} elseif ($value === '' && in_array($key, $primaryKey, true)) {
				// Skip marshalling '' for pk fields.
				continue;
			} elseif ($columnType) {
				$converter = Type::build($columnType);
				$value = $converter->marshal($value);
			}
			$properties[$key] = $value;
		}

		if (!isset($options['fieldList'])) {
			$entity->set($properties);
			return $entity;
		}

		foreach ((array)$options['fieldList'] as $field) {
			if (isset($properties[$field])) {
				$entity->set($field, $properties[$field]);
			}
		}

		return $entity;
	}

/**
 * Create a new sub-marshaller and marshal the associated data.
 *
 * @param \Cake\ORM\Association $assoc The association to marshall
 * @param array $value The data to hydrate
 * @param array $options List of options.
 * @return mixed
 */
	protected function _marshalAssociation($assoc, $value, $options) {
		$targetTable = $assoc->target();
		$marshaller = $targetTable->marshaller();
		$types = [Association::ONE_TO_ONE, Association::MANY_TO_ONE];
		if (in_array($assoc->type(), $types)) {
			return $marshaller->one($value, (array)$options);
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
 * * associated: Associations listed here will be marshalled as well.
 * * fieldList: A whitelist of fields to be assigned to the entity. If not present,
 *   the accessible fields list in the entity will be used.
 *
 * @param array $data The data to hydrate.
 * @param array $options List of options
 * @return array An array of hydrated records.
 * @see \Cake\ORM\Table::newEntities()
 */
	public function many(array $data, array $options = []) {
		$output = [];
		foreach ($data as $record) {
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
 * @param Association $assoc The association to marshal.
 * @param array $data The data to convert into entities.
 * @param array $options List of options.
 * @return array An array of built entities.
 */
	protected function _belongsToMany(Association $assoc, array $data, $options = []) {
		$associated = isset($options['associated']) ? $options['associated'] : [];
		$hasIds = array_key_exists('_ids', $data);
		if ($hasIds && is_array($data['_ids'])) {
			return $this->_loadBelongsToMany($assoc, $data['_ids']);
		}
		if ($hasIds) {
			return [];
		}

		$records = $this->many($data, $options);
		$joint = $assoc->junction();
		$jointMarshaller = $joint->marshaller();

		$nested = [];
		if (isset($associated['_joinData'])) {
			$nested = (array)$associated['_joinData'];
		}

		foreach ($records as $i => $record) {
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
 * @param Association $assoc The association class for the belongsToMany association.
 * @param array $ids The list of ids to load.
 * @return array An array of entities.
 */
	protected function _loadBelongsToMany($assoc, $ids) {
		$target = $assoc->target();
		$primaryKey = (array)$target->primaryKey();
		$multi = count($primaryKey) > 1;
		$primaryKey = array_map(function ($key) use ($target) {
			return $target->alias() . '.' . $key;
		}, $primaryKey);

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
 * Merges `$data` into `$entity` and recursively does the same for each one of
 * the association names passed in `$options`. When merging associations, if an
 * entity is not present in the parent entity for a given association, a new one
 * will be created.
 *
 * When merging HasMany or BelongsToMany associations, all the entities in the
 * `$data` array will appear, those that can be matched by primary key will get
 * the data merged, but those that cannot, will be discarded.
 *
 * ### Options:
 *
 * * associated: Associations listed here will be marshalled as well.
 * * fieldList: A whitelist of fields to be assigned to the entity. If not present
 *   the accessible fields list in the entity will be used.
 *
 * @param \Cake\Datasource\EntityInterface $entity the entity that will get the
 * data merged in
 * @param array $data key value list of fields to be merged into the entity
 * @param array $options List of options.
 * @return \Cake\Datasource\EntityInterface
 */
	public function merge(EntityInterface $entity, array $data, array $options = []) {
		$propertyMap = $this->_buildPropertyMap($options);
		$tableName = $this->_table->alias();

		if (isset($data[$tableName])) {
			$data = $data[$tableName];
		}

		$schema = $this->_table->schema();
		$properties = [];
		foreach ($data as $key => $value) {
			$columnType = $schema->columnType($key);
			$original = $entity->get($key);

			if (isset($propertyMap[$key])) {
				$assoc = $propertyMap[$key]['association'];
				$value = $this->_mergeAssociation($original, $assoc, $value, $propertyMap[$key]);
			} elseif ($columnType) {
				$converter = Type::build($columnType);
				$value = $converter->marshal($value);
				$isObject = is_object($value);
				if (
					(!$isObject && $original === $value) ||
					($isObject && $original == $value)
				) {
					continue;
				}
			}

			$properties[$key] = $value;
		}

		if (!isset($options['fieldList'])) {
			$entity->set($properties);
			return $entity;
		}

		foreach ((array)$options['fieldList'] as $field) {
			if (isset($properties[$field])) {
				$entity->set($field, $properties[$field]);
			}
		}

		return $entity;
	}

/**
 * Merges each of the elements from `$data` into each of the entities in `$entities
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
 * - associated: Associations listed here will be marshalled as well.
 * - fieldList: A whitelist of fields to be assigned to the entity. If not present,
 *   the accessible fields list in the entity will be used.
 *
 * @param array|\Traversable $entities the entities that will get the
 *   data merged in
 * @param array $data list of arrays to be merged into the entities
 * @param array $options List of options.
 * @return array
 */
	public function mergeMany($entities, array $data, array $options = []) {
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
				return $query->orWhere($query->newExpr()->and_(array_combine($primary, $keys)));
			}, $this->_table->find());

		if (count($maybeExistentQuery->clause('where'))) {
			foreach ($maybeExistentQuery as $entity) {
				$key = implode(';', $entity->extract($primary));
				$output[] = $this->merge($entity, $indexed[$key], $options);
				unset($indexed[$key]);
			}
		}

		foreach ((new Collection($indexed))->append($new) as $value) {
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
	protected function _mergeAssociation($original, $assoc, $value, $options) {
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
 * @return mixed
 */
	protected function _mergeBelongsToMany($original, $assoc, $value, $options) {
		$hasIds = array_key_exists('_ids', $value);
		$associated = isset($options['associated']) ? $options['associated'] : [];
		if ($hasIds && is_array($value['_ids'])) {
			return $this->_loadBelongsToMany($assoc, $value['_ids']);
		}
		if ($hasIds) {
			return [];
		}

		if (!in_array('_joinData', $associated) && !isset($associated['_joinData'])) {
			return $this->mergeMany($original, $value, $options);
		}

		$extra = [];
		foreach ($original as $entity) {
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

		$records = $this->mergeMany($original, $value, $options);
		foreach ($records as $record) {
			$hash = spl_object_hash($record);
			$value = $record->get('_joinData');
			if (isset($extra[$hash])) {
				$record->set('_joinData', $marshaller->merge($extra[$hash], (array)$value, $nested));
			} else {
				$joinData = $marshaller->one($value, $nested);
				$record->set('_joinData', $joinData);
			}
		}

		return $records;
	}

}
