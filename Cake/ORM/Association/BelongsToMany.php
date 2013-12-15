<?php
/**
 * PHP Version 5.4
 *
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
namespace Cake\ORM\Association;

use Cake\ORM\Association;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\Utility\Inflector;

/**
 * Represents an M - N relationship where there exists a junction - or join - table
 * that contains the association fields between the source and the target table.
 *
 * An example of a BelongsToMany association would be Article belongs to many Tags.
 */
class BelongsToMany extends Association {

	use ExternalAssociationTrait {
		_options as _externalOptions;
	}

/**
 * Whether this association can be expressed directly in a query join
 *
 * @var boolean
 */
	protected $_canBeJoined = false;

/**
 * The type of join to be used when adding the association to a query
 *
 * @var string
 */
	protected $_joinType = 'INNER';

/**
 * The strategy name to be used to fetch associated records.
 *
 * @var string
 */
	protected $_strategy = parent::STRATEGY_SELECT;

/**
 * Junction table instance
 *
 * @var Cake\ORM\Table
 */
	protected $_junctionTable;

/**
 * Junction table name
 *
 * @var string
 */
	protected $_junctionTableName;

/**
 * The name of the hasMany association from the target table
 * to the junction table
 *
 * @var string
 */
	protected $_junctionAssociationName;

/**
 * Sets the table instance for the junction relation. If no arguments
 * are passed, the current configured table instance is returned
 *
 * @param string|Cake\ORM\Table $table Name or instance for the join table
 * @return Cake\ORM\Table
 */
	public function junction($table = null) {
		$target = $this->target();
		$source = $this->source();
		$sAlias = $source->alias();
		$tAlias = $target->alias();

		if ($table === null) {
			if (empty($this->_junctionTable)) {
				$tableName = $this->_junctionTableName();
				$tableAlias = Inflector::camelize($tableName);
				$table = TableRegistry::get($tableAlias, [
					'table' => $tableName
				]);
			} else {
				return $this->_junctionTable;
			}
		}

		if (is_string($table)) {
			$table = TableRegistry::get($table);
		}
		$junctionAlias = $table->alias();

		if (!$table->association($sAlias)) {
			$table->belongsTo($sAlias)->target($source);
		}

		if (!$table->association($tAlias)) {
			$table->belongsTo($tAlias)->target($target);
		}

		if (!$target->association($junctionAlias)) {
			$target->belongsToMany($sAlias);
			$target->hasMany($junctionAlias)->target($table);
		}

		if (!$source->association($table->alias())) {
			$source->hasMany($junctionAlias)->target($table);
		}

		return $this->_junctionTable = $table;
	}

/**
 * Alters a Query object to include the associated target table data in the final
 * result
 *
 * The options array accept the following keys:
 *
 * - includeFields: Whether to include target model fields in the result or not
 * - foreignKey: The name of the field to use as foreign key, if false none
 *   will be used
 * - conditions: array with a list of conditions to filter the join with
 * - fields: a list of fields in the target table to include in the result
 * - type: The type of join to be used (e.g. INNER)
 *
 * @param Query $query the query to be altered to include the target table data
 * @param array $options Any extra options or overrides to be taken in account
 * @return void
 */
	public function attachTo(Query $query, array $options = []) {
		parent::attachTo($query, $options);
		$junction = $this->junction();
		$belongsTo = $junction->association($this->source()->alias());
		$cond = $belongsTo->_joinCondition(['foreignKey' => $belongsTo->foreignKey()]);

		if (isset($options['includeFields'])) {
			$includeFields = $options['includeFields'];
		}

		$options = ['conditions' => [$cond]] + compact('includeFields');
		$this->target()
			->association($junction->alias())
			->attachTo($query, $options);
	}

/**
 * Return false as join conditions are defined in the junction table
 *
 * @param array $options list of options passed to attachTo method
 * @return boolean false
 */
	protected function _joinCondition(array $options) {
		return false;
	}

/**
 * Eager loads a list of records in the target table that are related to another
 * set of records in the source table. Source records can specified in two ways:
 * first one is by passing a Query object setup to find on the source table and
 * the other way is by explicitly passing an array of primary key values from
 * the source table.
 *
 * The required way of passing related source records is controlled by "strategy"
 * By default the subquery strategy is used, which requires a query on the source
 * When using the select strategy, the list of primary keys will be used.
 *
 * Returns a closure that should be run for each record returned in an specific
 * Query. This callable will be responsible for injecting the fields that are
 * related to each specific passed row.
 *
 * Options array accept the following keys:
 *
 * - query: Query object setup to find the source table records
 * - keys: List of primary key values from the source table
 * - foreignKey: The name of the field used to relate both tables
 * - conditions: List of conditions to be passed to the query where() method
 * - sort: The direction in which the records should be returned
 * - fields: List of fields to select from the target table
 * - contain: List of related tables to eager load associated to the target table
 * - strategy: The name of strategy to use for finding target table records
 *
 * @param array $options
 * @return \Closure
 */
	public function eagerLoader(array $options) {
		$options += [
			'foreignKey' => $this->foreignKey(),
			'conditions' => [],
			'sort' => $this->sort(),
			'strategy' => $this->strategy()
		];
		$fetchQuery = $this->_buildQuery($options);
		$resultMap = [];
		$key = $options['foreignKey'];
		$property = $this->target()->association($this->junction()->alias())->property();

		foreach ($fetchQuery->execute() as $result) {
			$resultMap[$result[$property][$key]][] = $result;
		}

		return $this->_resultInjector($fetchQuery, $resultMap);
	}

/**
 * Clear out the data in the junction table for a given entity.
 *
 * @param Cake\ORM\Entity $entity The entity that started the cascading delete.
 * @param array $options The options for the original delete.
 * @return boolean Success.
 */
	public function cascadeDelete(Entity $entity, $options = []) {
		$foreignKey = (array)$this->foreignKey();
		$primaryKey = $this->source()->primaryKey();
		$conditions = [];

		if ($primaryKey) {
			$conditions = array_combine($foreignKey, $entity->extract((array)$primaryKey));
		}

		$conditions = array_merge($conditions, $this->conditions());

		$table = $this->junction();
		if ($this->_cascadeCallbacks) {
			foreach ($table->find('all')->where($conditions) as $related) {
				$table->delete($related, $options);
			}
			return true;
		}
		return $table->deleteAll($conditions);
	}

/**
 * Returns boolean true, as both of the tables 'own' rows in the other side
 * of the association via the joint table.
 *
 * @return boolean
 */
	public function isOwningSide(Table $side) {
		return true;
	}

/**
 * Takes an entity from the source table and looks if there is a field
 * matching the property name for this association. The found entity will be
 * saved on the target table for this association by passing supplied
 * `$options`
 *
 * Using this save function will only create new links between each side
 * of this association. It will not destroy existing ones even though they
 * may not be present in the array of entities to be saved.
 *
 * @param \Cake\ORM\Entity $entity an entity from the source table
 * @param array|\ArrayObject $options options to be passed to the save method in
 * the target table
 * @throws \InvalidArgumentException if the property representing the association
 * in the parent entity cannot be traversed
 * @return boolean|Entity false if $entity could not be saved, otherwise it returns
 * the saved entity
 * @see Table::save()
 */
	public function save(Entity $entity, $options = []) {
		$property = $this->property();
		$targetEntity = $entity->get($this->property());
		$success = false;

		if ($targetEntity) {
			$success = $this->_saveTarget($entity, $targetEntity, $options);
		}

		return $success;
	}

/**
 * Persists each of the entities into the target table and creates links between
 * the parent entity and each one of the saved target entities.
 *
 * @param \Cake\ORM\Entity $parentEntity the source entity containing the target
 * entities to be saved.
 * @param array|\Traversable list of entities to persist in target table and to
 * link to the parent entity
 * @param array $options list of options accepted by Table::save()
 * @throws \InvalidArgumentException if the property representing the association
 * in the parent entity cannot be traversed
 * @return \Cake\ORM\Entity|boolean The parent entity after all links have been
 * created if no errors happened, false otherwise
 */
	protected function _saveTarget(Entity $parentEntity, $entities, $options) {
		if (!(is_array($entities) || $entities instanceof \Traversable)) {
			$name = $this->property();
			$message = __d('cake_dev', 'Could not save %s, it cannot be traversed', $name);
			throw new \InvalidArgumentException($message);
		}

		$table = $this->target();
		$original = $entities;
		$persisted = [];

		foreach ($entities as $k => $entity) {
			if (!empty($options['atomic'])) {
				$entity = clone $entity;
			}

			if ($table->save($entity, $options)) {
				$entities[$k] = $entity;
				$persisted[] = $entity;
				continue;
			}

			if (!empty($options['atomic'])) {
				$original[$k]->errors($entity->errors());
				return false;
			}
		}

		$success = $this->_saveLinks($parentEntity, $persisted, $options);
		if (!$success && !empty($options['atomic'])) {
			$parentEntity->set($this->property(), $original);
			return false;
		}

		$parentEntity->set($this->property(), $entities);
		return $parentEntity;
	}

/**
 * Creates links between the source entity and each of the passed target entities
 *
 * @param \Cake\ORM\Entity $sourceEntity the entity from source table in this
 * association
 * @param array list of entities to link to link to the source entity using the
 * junction table
 * @return boolean success
 */
	protected function _saveLinks(Entity $sourceEntity, $targetEntities, $options) {
		$target = $this->target();
		$junction = $this->junction();
		$source = $this->source();
		$entityClass = $junction->entityClass();
		$belongsTo = $junction->association($target->alias());
		$foreignKey = (array)$this->foreignKey();
		$assocForeignKey = (array)$belongsTo->foreignKey();
		$targetPrimaryKey = (array)$target->primaryKey();
		$sourcePrimaryKey = (array)$source->primaryKey();
		$jointProperty = $target->association($junction->alias())->property();

		foreach ($targetEntities as $k => $e) {
			$joint = $e->get($jointProperty);
			if (!$joint) {
				$joint = new $entityClass;
				$joint->isNew(true);
			}

			$joint->set(array_combine(
				$foreignKey,
				$sourceEntity->extract($sourcePrimaryKey)
			));
			$joint->set(array_combine($assocForeignKey, $e->extract($targetPrimaryKey)));
			$saved = $junction->save($joint, $options);

			if (!$saved && !empty($options['atomic'])) {
				return false;
			}

			$e->set($jointProperty, $joint);
			$e->dirty($jointProperty, false);
		}

		return true;
	}

/**
 * Associates the source entity to each of the target entities provided by
 * creating links in the junction table. Both the source entity and each of
 * the target entities are assumed to be already persisted, if the are marked
 * as new or their status is unknown, an exception will be thrown.
 *
 * When using this method, all entities in `$targetEntities` will be appended to
 * the source entity'property corresponding to this association object.
 *
 * This method does not check link uniqueness.
 *
 * ###Example:
 *
 * {{{
 * $newTags = $tags->find('relevant')->execute();
 * $articles->association('tags')->link($article, $newTags);
 * }}}
 *
 * `$article->get('tags')` will contain all tags in `$newTags` after liking
 *
 * @param \Cake\ORM\Entity $sourceEntity the row belonging to the `source` side
 * of this association
 * @param array $targetEntities list of entities belonging to the `target` side
 * of this association
 * @param array $options list of options to be passed to the save method
 * @throws \InvalidArgumentException when any of the values in $targetEntities is
 * detected to not be already persisted
 * @return boolean true on success, false otherwise
 */
	public function link(Entity $sourceEntity, array $targetEntities, array $options = []) {
		$this->_checkPersitenceStatus($sourceEntity, $targetEntities);
		$property = $this->property();
		$links = $sourceEntity->get($property) ?: [];
		$links = array_merge($links, $targetEntities);
		$sourceEntity->set($property, $links);

		return $this->junction()->connection()->transactional(
			function() use ($sourceEntity, $targetEntities, $options) {
				return $this->_saveLinks($sourceEntity, $targetEntities, $options);
			}
		);
	}

/**
 * Removes all links between the passed source entity and each of the provided
 * target entities. This method assumes that all passed objects are already persisted
 * in the database and that each of them contain a primary key value.
 *
 * By default this method will also unset each of the entity objects stored inside
 * the source entity.
 *
 * @param \Cake\ORM\Entity $sourceEntity an entity persisted in the source table for
 * this association
 * @param array $targetEntities list of entities persisted in the target table for
 * this association
 * @param boolean $cleanProperty whether or not to remove all the objects in $targetEntities
 * that are stored in $sourceEntity
 * @throws \InvalidArgumentException if non persisted entities are passed or if
 * any of them is lacking a primary key value
 * @return void
 */
	public function unlink(Entity $sourceEntity, array $targetEntities, $cleanProperty = true) {
		$this->_checkPersitenceStatus($sourceEntity, $targetEntities);
		$property = $this->property();

		$this->junction()->connection()->transactional(
			function() use ($sourceEntity, $targetEntities) {
				$links = $this->_collectJointEntities($sourceEntity, $targetEntities);
				foreach ($links as $entity) {
					$this->_junctionTable->delete($entity);
				}
			}
		);

		$existing = $sourceEntity->get($property) ?: [];
		if (!$cleanProperty || empty($existing)) {
			return;
		}

		$storage = new \SplObjectStorage;
		foreach ($targetEntities as $e) {
			$storage->attach($e);
		}

		foreach ($existing as $k => $e) {
			if ($storage->contains($e)) {
				unset($existing[$k]);
			}
		}

		$sourceEntity->set($property, array_values($existing));
		$sourceEntity->dirty($property, false);
	}

	public function replaceLinks(Entity $sourceEntity, array $targetEntities, array $options = []) {
		$primaryKey = (array)$this->source()->primaryKey();
		$primaryValue = $sourceEntity->extract($primaryKey);

		if (count(array_filter($primaryValue, 'strlen')) !== count($primaryKey)) {
			$message = __d('cake_dev', 'Could not find primary key value for source entity');
			throw new \InvalidArgumentException($message);
		}

		return $this->junction()->connection()->transactional(
			function() use ($sourceEntity, $targetEntities, $primaryValue, $options) {
				$foreignKey = (array)$this->foreignKey();
				$existing = $this->_junctionTable->find('all')
					->where(array_combine($foreignKey, $primaryValue))
					->andWHere($this->conditions());

				$jointEntities = $this->_collectJointEntities($sourceEntity, $targetEntities);
				$inserts = $this->_diffLinks($existing, $jointEntities, $targetEntities);

				$property = $this->property();
				$sourceEntity->set($property, $inserts);

				if ($inserts && !$this->save($sourceEntity, $options + ['associated' => false])) {
					return false;
				}

				$sourceEntity->set($property, $targetEntities);
				$sourceEntity->dirty($property, false);
				return true;
			}
		);
	}

	protected function _diffLinks($existing, $jointEntities, $targetEntities) {
		$junction = $this->junction();
		$target = $this->target();
		$belongsTo = $junction->association($target->alias());
		$foreignKey = (array)$this->foreignKey();
		$assocForeignKey = (array)$belongsTo->foreignKey();

		$keys = array_merge($foreignKey, $assocForeignKey);
		$deletes = $indexed = $present = [];

		foreach ($jointEntities as $i => $entity) {
			$indexed[$i] = $entity->extract($keys);
			$present[$i] = array_values($entity->extract($assocForeignKey));
		}

		foreach ($existing as $result) {
			$fields = $result->extract($keys);
			$found = false;
			foreach ($indexed as $i => $data) {
				if ($fields === $data) {
					unset($indexed[$i]);
					$found = true;
					break;
				}
			}

			if (!$found) {
				$deletes[] = $result;
			}
		}

		$primary = (array)$target->primaryKey();
		$jointProperty = $target->association($junction->alias())->property();
		foreach ($targetEntities as $k => $entity) {
			$key = array_values($entity->extract($primary));
			foreach ($present as $i => $data) {
				if ($key === $data && !$entity->get($jointProperty)) {
					unset($targetEntities[$k], $present[$i]);
					break;
				}
			}
		}

		if ($deletes) {
			foreach ($deletes as $entity) {
				$junction->delete($entity);
			}
		}

		return array_values($targetEntities);
	}

/**
 * Throws an exception should any of the passed entities is not persisted.
 *
 * @throws \InvalidArgumentException
 * @return boolean
 */
	protected function _checkPersitenceStatus($sourceEntity, array $targetEntities) {
		if ($sourceEntity->isNew() !== false) {
			$error = __d('cake_dev', 'Source entity needs to be persisted before proceeding');
			throw new \InvalidArgumentException($error);
		}

		foreach ($targetEntities as $entity) {
			if ($entity->isNew() !== false) {
				$error = __d('cake_dev', 'Cannot link not persisted entities');
				throw new \InvalidArgumentException($error);
			}
		}

		return true;
	}

/**
 * Returns the list of joint entities that exist between the source entity
 * and each of the passed target entities
 *
 * @param \Cake\ORM\Entity $sourceEntity
 * @param array $targetEntities
 * @throws \InvalidArgumentException if any of the entities is lacking a primary
 * key value
 * @return array
 */
	protected function _collectJointEntities($sourceEntity, $targetEntities) {
		$target = $this->target();
		$source = $this->source();
		$junction = $this->junction();
		$jointProperty = $target->association($junction->alias())->property();
		$primary = (array)$target->primaryKey();

		$result = [];
		$missing = [];

		foreach ($targetEntities as $entity) {
			$joint = $entity->get($jointProperty);

			if (!$joint) {
				$missing[] = $entity->extract($primary);
				continue;
			}

			$joint->isNew(false);
			$result[] = $joint;
		}

		if (empty($missing)) {
			return $result;
		}

		$belongsTo = $junction->association($target->alias());
		$foreignKey = (array)$this->foreignKey();
		$assocForeignKey = (array)$belongsTo->foreignKey();
		$sourceKey = $sourceEntity->extract((array)$source->primaryKey());

		foreach($missing as $key) {
			$unions[] = $junction->find('all')
				->where(array_combine($foreignKey, $sourceKey))
				->andWhere(array_combine($assocForeignKey, $key))
				->andWhere($belongsTo->conditions());
		}

		$query = array_shift($unions);
		foreach ($unions as $q) {
			$query->union($q);
		}

		return array_merge($result, $query->toArray());
	}

/**
 * Appends any conditions required to load the relevant set of records in the
 * target table query given a filter key and some filtering values.
 *
 * @param \Cake\ORM\Query target table's query
 * @param string $key the fields that should be used for filtering
 * @param mixed $filter the value that should be used to match for $key
 * @return \Cake\ORM\Query
 */
	protected function _addFilteringCondition($query, $key, $filter) {
		return $query->contain([
			$this->_junctionAssociationName() => [
				'conditions' => [$key . ' in' => $filter],
				'matching' => true
			]
		]);
	}

/**
 * Generates a string used as a table field that contains the values upon
 * which the filter should be applied
 *
 * params array $options
 * @return string
 */
	protected function _linkField($options) {
		return sprintf('%s.%s', $this->_junctionAssociationName(), $options['foreignKey']);
	}

/**
 * Returns the name of the association from the target table to the junction table,
 * this name is used to generate alias in the query and to later on retrieve the
 * results.
 *
 * @return string
 */
	protected function _junctionAssociationName() {
		if (!$this->_junctionAssociationName) {
			$this->_junctionAssociationName = $this->target()
				->association($this->junction()->alias())
				->name();
		}
		return $this->_junctionAssociationName;
	}

/**
 * Sets the name of the junction table.
 * If no arguments are passed the current configured name is returned. A default
 * name based of the associated tables will be generated if none found.
 *
 * @param string $name
 * @return string
 */
	protected function _junctionTableName($name = null) {
		if ($name === null) {
			if (empty($this->_junctionTableName)) {
				$aliases = array_map('\Cake\Utility\Inflector::underscore', [
					$sAlias = $this->source()->alias(),
					$tAlias = $this->target()->alias()
				]);
				sort($aliases);
				$this->_junctionTableName = implode('_', $aliases);
			}
			return $this->_junctionTableName;
		}
		return $this->_junctionTableName = $name;
	}

/**
 * Parse extra options passed in the constructor.
 * @param array $opts original list of options passed in constructor
 *
 * @return void
 */
	protected function _options(array $opts) {
		if (!empty($opts['through'])) {
			$this->junction($opts['through']);
		}
		if (!empty($opts['joinTable'])) {
			$this->_junctionTableName($opts['joinTable']);
		}
		$this->_externalOptions($opts);
	}

}
