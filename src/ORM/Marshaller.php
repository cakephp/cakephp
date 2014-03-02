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
 * @since         CakePHP(tm) v 3.0.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
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
 */
class Marshaller {

/**
 * Whether or not this marhshaller is in safe mode.
 *
 * @var boolean
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
 * @param boolean Whether or not this marshaller is in safe mode
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
	public function one(array $data, $include = []) {
		$propertyMap = $this->_buildPropertyMap($include);

		$schema = $this->_table->schema();
		$tableName = $this->_table->alias();
		$entityClass = $this->_table->entityClass();
		$entity = new $entityClass();

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
				$value = $converter->marshall($value);
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
		if ($assoc->type() === Association::ONE_TO_ONE) {
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
	public function many(array $data, $include = []) {
		$output = [];
		foreach ($data as $record) {
			$output[] = $this->one($record, $include);
		}
		return $output;
	}

/**
 * Marshalls data for belongsToMany associations.
 *
 * Builds the related entities and handles the special casing
 * for junction table entities.
 *
 * @param Association $assoc The association to marshall.
 * @param array $data The data to convert into entities.
 * @param array $include The nested associations to convert.
 * @return array An array of built entities.
 */
	protected function _belongsToMany(Association $assoc, array $data, $include = []) {
		if (isset($data['_ids']) && is_array($data['_ids'])) {
			return $this->_loadBelongsToMany($assoc, $data['_ids']);
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

	public function merge(EntityInterface $entity, array $data, $include = []) {
		$propertyMap = $this->_buildPropertyMap($include);
		$tableName = $this->_table->alias();

		if (isset($data[$tableName])) {
			$data = $data[$tableName];
		}

		$properties = [];
		foreach ($data as $key => $value) {
			$original = $entity->get($key);
			if (isset($propertyMap[$key])) {
				$assoc = $propertyMap[$key]['association'];
				$nested = $propertyMap[$key]['nested'];
				$value = $this->_mergeAssociation($original, $assoc, $value, $nested);
			} elseif ($original == $value) {
				continue;
			}
			$properties[$key] = $value;
		}

		$entity->set($properties);
		return $entity;
	}

	public function mergeMany($entities, array $data, $include = []) {
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

	protected function _mergeAssociation($original, $assoc, $value, $include) {
		if (!$original) {
			return $this->_marshalAssociation($assoc, $value, $include);
		}

		$targetTable = $assoc->target();
		$marshaller = $targetTable->marshaller();
		if ($assoc->type() === Association::ONE_TO_ONE) {
			return $marshaller->merge($original, $value, (array)$include);
		}
		if ($assoc->type() === Association::MANY_TO_MANY) {
			return $marshaller->_mergeBelongsToMany($original, $assoc, $value, (array)$include);
		}
		return $marshaller->mergeMany($original, $value, (array)$include);
	}

	protected function _mergeBelongsToMany($original, $assoc, $data, $include) {
		if (isset($data['_ids']) && is_array($data['_ids'])) {
			return $this->_loadBelongsToMany($assoc, $data['_ids']);
		}

		if (!in_array('_joinData', $include) && !isset($include['_joinData'])) {
			return $this->mergeMany($original, $data, $include);
		}

		$extra = [];
		foreach ($original as $entity) {
			$joinData = $entity->get('_joinData');
			if ($joinData) {
				$extra[spl_object_hash($entity)] = $joinData;
			}
		}

		$joint = $assoc->junction();
		$marshaller = $joint->marshaller();

		$nested = [];
		if (isset($include['_joinData']['associated'])) {
			$nested = (array)$include['_joinData']['associated'];
		}

		$records = $this->mergeMany($original, $data, $include);
		foreach ($records as $record) {
			$hash = spl_object_hash($record);
			$data = $record->get('_joinData');
			if (isset($extra[$hash])) {
				$record->set('_joinData', $marshaller->merge($extra[$hash], (array)$data, $nested));
			} else {
				$joinData = $marshaller->one($data, $nested);
				$record->set('_joinData', $joinData);
			}
		}

		return $records;
	}

}
