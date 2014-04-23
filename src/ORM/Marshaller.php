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

/**
 * Whether or not this marhshaller is in safe mode.
 *
 * @var bool
 */
	protected $_safe;

/**
 * The table instance this marshaller is for.
 *
 * @var \Cake\ORM\Table
 */
	protected $_table;

/**
 * Constructor.
 *
 * @param \Cake\ORM\Table $table
 * @param bool $safe Whether or not this marshaller is in safe mode
 */
	public function __construct(Table $table, $safe = false) {
		$this->_table = $table;
		$this->_safe = $safe;
	}

/**
 * Build the map of property => association names.
 *
 * @param array $include The array of included associations.
 * @return array
 */
	protected function _buildPropertyMap($include) {
		$map = [];
		foreach ($include as $key => $nested) {
			if (is_int($key) && is_scalar($nested)) {
				$key = $nested;
				$nested = [];
			}
			$nested = isset($nested['associated']) ? $nested['associated'] : [];
			$assoc = $this->_table->association($key);
			if ($assoc) {
				$map[$assoc->property()] = [
					'association' => $assoc,
					'nested' => $nested
				];
			}
		}
		return $map;
	}

/**
 * Hydrate one entity and its associated data.
 *
 * @param array $data The data to hydrate.
 * @param array $include The associations to include.
 * @return \Cake\ORM\Entity
 * @see \Cake\ORM\Table::newEntity()
 */
	public function one(array $data, array $include = []) {
		$propertyMap = $this->_buildPropertyMap($include);

		$schema = $this->_table->schema();
		$tableName = $this->_table->alias();
		$entityClass = $this->_table->entityClass();
		$entity = new $entityClass();
		$entity->source($this->_table->alias());

		if (isset($data[$tableName])) {
			$data = $data[$tableName];
		}

		$properties = [];
		foreach ($data as $key => $value) {
			$columnType = $schema->columnType($key);
			if (isset($propertyMap[$key])) {
				$assoc = $propertyMap[$key]['association'];
				$nested = $propertyMap[$key]['nested'];
				$value = $this->_marshalAssociation($assoc, $value, $nested);
			} elseif ($columnType) {
				$converter = Type::build($columnType);
				$value = $converter->marshal($value);
			}
			$properties[$key] = $value;
		}
		$entity->set($properties);
		return $entity;
	}

/**
 * Create a new sub-marshaller and marshal the associated data.
 *
 * @param \Cake\ORM\Association $assoc
 * @param array $value The data to hydrate
 * @param array $include The associations to include.
 * @return mixed
 */
	protected function _marshalAssociation($assoc, $value, $include) {
		$targetTable = $assoc->target();
		$marshaller = $targetTable->marshaller();
		$types = [Association::ONE_TO_ONE, Association::MANY_TO_ONE];
		if (in_array($assoc->type(), $types)) {
			return $marshaller->one($value, (array)$include);
		}
		if ($assoc->type() === Association::MANY_TO_MANY) {
			return $marshaller->_belongsToMany($assoc, $value, (array)$include);
		}
		return $marshaller->many($value, (array)$include);
	}

/**
 * Hydrate many entities and their associated data.
 *
 * @param array $data The data to hydrate.
 * @param array $include The associations to include.
 * @return array An array of hydrated records.
 * @see \Cake\ORM\Table::newEntities()
 */
	public function many(array $data, array $include = []) {
		$output = [];
		foreach ($data as $record) {
			$output[] = $this->one($record, $include);
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
 * @param array $include The nested associations to convert.
 * @return array An array of built entities.
 */
	protected function _belongsToMany(Association $assoc, array $data, $include = []) {
		$hasIds = isset($data['_ids']);
		if ($hasIds && is_array($data['_ids'])) {
			return $this->_loadBelongsToMany($assoc, $data['_ids']);
		}
		if ($hasIds) {
			return [];
		}

		$records = $this->many($data, $include);
		if (!in_array('_joinData', $include) && !isset($include['_joinData'])) {
			return $records;
		}

		$joint = $assoc->junction();
		$jointMarshaller = $joint->marshaller();

		$nested = [];
		if (isset($include['_joinData']['associated'])) {
			$nested = (array)$include['_joinData']['associated'];
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

		if ($multi) {
			if (count(current($ids)) !== count($primaryKey)) {
				return [];
			}
			$filter = new TupleComparison($primaryKey, $ids, [], 'IN');
		} else {
			$filter = [$primaryKey[0] . ' IN' => $ids];
		}

		return $assoc->find()->where($filter)->toArray();
	}

/**
 * Merges `$data` into `$entity` and recursively does the same for each one of
 * the association names passed in `$include`. When merging associations, if an
 * entity is not present in the parent entity for a given association, a new one
 * will be created.
 *
 * When merging HasMany or BelongsToMany associations, all the entities in the
 * `$data` array will appear, those that can be matched by primary key will get
 * the data merged, but those that cannot, will be discarded.
 *
 * @param \Cake\Datasource\EntityInterface $entity the entity that will get the
 * data merged in
 * @param array $data key value list of fields to be merged into the entity
 * @param array $include The list of associations to be merged
 * @return \Cake\Datasource\EntityInterface
 */
	public function merge(EntityInterface $entity, array $data, array $include = []) {
		$propertyMap = $this->_buildPropertyMap($include);
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
				$nested = $propertyMap[$key]['nested'];
				$value = $this->_mergeAssociation($original, $assoc, $value, $nested);
			} elseif ($columnType) {
				$converter = Type::build($columnType);
				$value = $converter->marshal($value);
				if ($original == $value) {
					continue;
				}
			}

			$properties[$key] = $value;
		}

		$entity->set($properties);
		return $entity;
	}

/**
 * Merges each of the elements from `$data` into each of the entities in `$entities
 * and recursively does the same for each one of the association names passed in
 * `$include`. When merging associations, if an entity is not present in the parent
 * entity for such association, a new one will be created.
 *
 * Records in `$data` are matched against the entities by using the primary key
 * column. Entries in `$entities` that cannot be matched to any record in
 * `$data` will be discarded. Records in `$data` that could not be matched will
 * be marshalled as a new entity.
 *
 * When merging HasMany or BelongsToMany associations, all the entities in the
 * `$data` array will appear, those that can be matched by primary key will get
 * the data merged, but those that cannot, will be discarded.
 *
 * @param array|\Traversable $entities the entities that will get the
 * data merged in
 * @param array $data list of arrays to be merged into the entities
 * @param array $include The list of associations to be merged
 * @return array
 */
	public function mergeMany($entities, array $data, array $include = []) {
		$primary = (array)$this->_table->primaryKey();
		$indexed = (new Collection($data))->groupBy($primary[0])->toArray();
		$new = isset($indexed[null]) ? [$indexed[null]] : [];
		unset($indexed[null]);
		$output = [];

		foreach ($entities as $entity) {
			if (!($entity instanceof EntityInterface)) {
				continue;
			}

			$key = $entity->get($primary[0]);

			if ($key === null || !isset($indexed[$key])) {
				continue;
			}

			$output[] = $this->merge($entity, $indexed[$key][0], $include);
			unset($indexed[$key]);
		}

		foreach (array_merge($indexed, $new) as $record) {
			foreach ($record as $value) {
				$output[] = $this->one($value, $include);
			}
		}
		return $output;
	}

/**
 * Creates a new sub-marshaller and merges the associated data.
 *
 * @param \Cake\Datasource\EntityInterface $original
 * @param \Cake\ORM\Association $assoc
 * @param array $value The data to hydrate
 * @param array $include The associations to include.
 * @return mixed
 */
	protected function _mergeAssociation($original, $assoc, $value, $include) {
		if (!$original) {
			return $this->_marshalAssociation($assoc, $value, $include);
		}

		$targetTable = $assoc->target();
		$marshaller = $targetTable->marshaller();
		$types = [Association::ONE_TO_ONE, Association::MANY_TO_ONE];
		if (in_array($assoc->type(), $types)) {
			return $marshaller->merge($original, $value, (array)$include);
		}
		if ($assoc->type() === Association::MANY_TO_MANY) {
			return $marshaller->_mergeBelongsToMany($original, $assoc, $value, (array)$include);
		}
		return $marshaller->mergeMany($original, $value, (array)$include);
	}

/**
 * Creates a new sub-marshaller and merges the associated data for a BelongstoMany
 * association.
 *
 * @param \Cake\Datasource\EntityInterface $original
 * @param \Cake\ORM\Association $assoc
 * @param array $value The data to hydrate
 * @param array $include The associations to include.
 * @return mixed
 */
	protected function _mergeBelongsToMany($original, $assoc, $value, $include) {
		if (isset($value['_ids']) && is_array($value['_ids'])) {
			return $this->_loadBelongsToMany($assoc, $value['_ids']);
		}

		if (!in_array('_joinData', $include) && !isset($include['_joinData'])) {
			return $this->mergeMany($original, $value, $include);
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
		if (isset($include['_joinData']['associated'])) {
			$nested = (array)$include['_joinData']['associated'];
		}

		$records = $this->mergeMany($original, $value, $include);
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
