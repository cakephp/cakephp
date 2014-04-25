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
		_addFilteringCondition as _addExternalConditions;
	}

/**
 * Saving strategy that will only append to the links set
 *
 * @var string
 */
	const SAVE_APPEND = 'append';

/**
 * Saving strategy that will replace the links with the provided set
 *
 * @var string
 */
	const SAVE_REPLACE = 'replace';

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
 * @var \Cake\ORM\Table
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
 * The name of the property to be set containing data from the junction table
 * once a record from the target table is hydrated
 *
 * @var string
 */
	protected $_junctionProperty = '_joinData';

/**
 * Saving strategy to be used by this association
 *
 * @var string
 */
	protected $_saveStrategy = self::SAVE_REPLACE;

/**
 * The name of the field representing the foreign key to the target table
 *
 * @var string|array
 */
	protected $_targetForeignKey;

/**
 * The table instance for the junction relation.
 *
 * @var string|\Cake\ORM\Table
 */
	protected $_through;

/**
 * Sets the name of the field representing the foreign key to the target table.
 * If no parameters are passed current field is returned
 *
 * @param string $key the key to be used to link both tables together
 * @return string
 */
	public function targetForeignKey($key = null) {
		if ($key === null) {
			if ($this->_targetForeignKey === null) {
				$key = Inflector::singularize($this->target()->alias());
				$this->_targetForeignKey = Inflector::underscore($key) . '_id';
			}
			return $this->_targetForeignKey;
		}
		return $this->_targetForeignKey = $key;
	}

/**
 * Sets the table instance for the junction relation. If no arguments
 * are passed, the current configured table instance is returned
 *
 * @param string|\Cake\ORM\Table $table Name or instance for the join table
 * @return \Cake\ORM\Table
 */
	public function junction($table = null) {
		$target = $this->target();
		$source = $this->source();
		$sAlias = $source->alias();
		$tAlias = $target->alias();

		if ($table === null) {
			if (!empty($this->_junctionTable)) {
				return $this->_junctionTable;
			}

			if (!empty($this->_through)) {
				$table = $this->_through;
			} else {
				$tableName = $this->_junctionTableName();
				$tableAlias = Inflector::camelize($tableName);

				$config = [];
				if (!TableRegistry::exists($tableAlias)) {
					$config = ['table' => $tableName];
				}
				$table = TableRegistry::get($tableAlias, $config);
			}
		}

		if (is_string($table)) {
			$table = TableRegistry::get($table);
		}
		$junctionAlias = $table->alias();

		if (!$table->association($sAlias)) {
			$table
				->belongsTo($sAlias, ['foreignKey' => $this->foreignKey()])
				->target($source);
		}

		if (!$table->association($tAlias)) {
			$table
				->belongsTo($tAlias, ['foreignKey' => $this->targetForeignKey()])
				->target($target);
		}

		if (!$target->association($junctionAlias)) {
			$target->belongsToMany($sAlias, [
				'sourceTable' => $source,
				'foreignKey' => $this->targetForeignKey(),
				'targetForeignKey' => $this->foreignKey()
			]);
			$target->hasMany($junctionAlias, [
				'targetTable' => $table,
				'foreignKey' => $this->targetForeignKey(),
			]);
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

		unset($options['queryBuilder']);
		$options = ['conditions' => [$cond]] + compact('includeFields');
		$options['foreignKey'] = $this->targetForeignKey();
		$this->_targetTable
			->association($junction->alias())
			->attachTo($query, $options);
	}

/**
 * {@inheritDoc}
 */
	public function transformRow($row, $nestKey, $joined) {
		$alias = $this->junction()->alias();
		if ($joined) {
			$row[$this->target()->alias()][$this->_junctionProperty] = $row[$alias];
			unset($row[$alias]);
		}

		return parent::transformRow($row, $nestKey, $joined);
	}

/**
 * Get the relationship type.
 *
 * @return string
 */
	public function type() {
		return self::MANY_TO_MANY;
	}

/**
 * Return false as join conditions are defined in the junction table
 *
 * @param array $options list of options passed to attachTo method
 * @return bool false
 */
	protected function _joinCondition($options) {
		return false;
	}

/**
 * Builds an array containing the results from fetchQuery indexed by
 * the foreignKey value corresponding to this association.
 *
 * @param \Cake\ORM\Query $fetchQuery The query to get results from
 * @param array $options The options passed to the eager loader
 * @return array
 */
	protected function _buildResultMap($fetchQuery, $options) {
		$resultMap = [];
		$key = (array)$options['foreignKey'];
		$property = $this->target()->association($this->junction()->alias())->property();
		$hydrated = $fetchQuery->hydrate();

		foreach ($fetchQuery->all() as $result) {
			$result[$this->_junctionProperty] = $result[$property];
			unset($result[$property]);

			if ($hydrated) {
				$result->dirty($this->_junctionProperty, false);
			}

			$values = [];
			foreach ($key as $k) {
				$values[] = $result[$this->_junctionProperty][$k];
			}
			$resultMap[implode(';', $values)][] = $result;
		}
		return $resultMap;
	}

/**
 * Clear out the data in the junction table for a given entity.
 *
 * @param \Cake\ORM\Entity $entity The entity that started the cascading delete.
 * @param array $options The options for the original delete.
 * @return bool Success.
 */
	public function cascadeDelete(Entity $entity, array $options = []) {
		$foreignKey = (array)$this->foreignKey();
		$primaryKey = (array)$this->source()->primaryKey();
		$conditions = [];

		if ($primaryKey) {
			$conditions = array_combine($foreignKey, $entity->extract((array)$primaryKey));
		}

		$table = $this->junction();
		$hasMany = $this->source()->association($table->alias());
		if ($this->_cascadeCallbacks) {
			foreach ($hasMany->find('all')->where($conditions) as $related) {
				$table->delete($related, $options);
			}
			return true;
		}

		$conditions = array_merge($conditions, $hasMany->conditions());
		return $table->deleteAll($conditions);
	}

/**
 * Returns boolean true, as both of the tables 'own' rows in the other side
 * of the association via the joint table.
 *
 * @param \Cake\ORM\Table $side The potential Table with ownership
 * @return bool
 */
	public function isOwningSide(Table $side) {
		return true;
	}

/**
 * Sets the strategy that should be used for saving. If called with no
 * arguments, it will return the currently configured strategy
 *
 * @param string $strategy the strategy name to be used
 * @throws \InvalidArgumentException if an invalid strategy name is passed
 * @return string the strategy to be used for saving
 */
	public function saveStrategy($strategy = null) {
		if ($strategy === null) {
			return $this->_saveStrategy;
		}
		if (!in_array($strategy, [self::SAVE_APPEND, self::SAVE_REPLACE])) {
			$msg = sprintf('Invalid save strategy "%s"', $strategy);
			throw new \InvalidArgumentException($msg);
		}
		return $this->_saveStrategy = $strategy;
	}

/**
 * Takes an entity from the source table and looks if there is a field
 * matching the property name for this association. The found entity will be
 * saved on the target table for this association by passing supplied
 * `$options`
 *
 * When using the 'append' strategy, this function will only create new links
 * between each side of this association. It will not destroy existing ones even
 * though they may not be present in the array of entities to be saved.
 *
 * When using the 'replace' strategy, existing links will be removed and new links
 * will be created in the joint table. If there exists links in the database to some
 * of the entities intended to be saved by this method, they will be updated,
 * not deleted.
 *
 * @param \Cake\ORM\Entity $entity an entity from the source table
 * @param array|\ArrayObject $options options to be passed to the save method in
 * the target table
 * @throws \InvalidArgumentException if the property representing the association
 * in the parent entity cannot be traversed
 * @return bool|Entity false if $entity could not be saved, otherwise it returns
 * the saved entity
 * @see Table::save()
 * @see BelongsToMany::replaceLinks()
 */
	public function save(Entity $entity, array $options = []) {
		$targetEntity = $entity->get($this->property());
		$strategy = $this->saveStrategy();

		if (!$targetEntity) {
			return false;
		}

		if ($strategy === self::SAVE_APPEND) {
			return $this->_saveTarget($entity, $targetEntity, $options);
		}

		if ($this->replaceLinks($entity, $targetEntity, $options)) {
			return $entity;
		}

		return false;
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
 * @return \Cake\ORM\Entity|bool The parent entity after all links have been
 * created if no errors happened, false otherwise
 */
	protected function _saveTarget(Entity $parentEntity, $entities, $options) {
		if (!(is_array($entities) || $entities instanceof \Traversable)) {
			$name = $this->property();
			$message = sprintf('Could not save %s, it cannot be traversed', $name);
			throw new \InvalidArgumentException($message);
		}

		$table = $this->target();
		$original = $entities;
		$persisted = [];

		foreach ($entities as $k => $entity) {
			if (!($entity instanceof Entity)) {
				break;
			}

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
 * @param array $targetEntities list of entities to link to link to the source entity using the
 * junction table
 * @param array $options list of options accepted by Table::save()
 * @return bool success
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
		$jointProperty = $this->_junctionProperty;
		$junctionAlias = $junction->alias();

		foreach ($targetEntities as $e) {
			$joint = $e->get($jointProperty);
			if (!$joint) {
				$joint = new $entityClass;
				$joint->isNew(true);
			}

			$joint->set(array_combine(
				$foreignKey,
				$sourceEntity->extract($sourcePrimaryKey)
			), ['guard' => false]);
			$joint->set(array_combine($assocForeignKey, $e->extract($targetPrimaryKey)), ['guard' => false]);
			$saved = $junction->save($joint, $options);

			if (!$saved && !empty($options['atomic'])) {
				return false;
			}

			$e->set($jointProperty, $joint);
			$e->dirty($jointProperty, false);
			$joint->source($junctionAlias);
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
 * the source entity's property corresponding to this association object.
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
 * @return bool true on success, false otherwise
 */
	public function link(Entity $sourceEntity, array $targetEntities, array $options = []) {
		$this->_checkPersistenceStatus($sourceEntity, $targetEntities);
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
 * ###Example:
 *
 * {{{
 * $article->tags = [$tag1, $tag2, $tag3, $tag4];
 * $tags = [$tag1, $tag2, $tag3];
 * $articles->association('tags')->unlink($article, $tags);
 * }}}
 *
 * `$article->get('tags')` will contain only `[$tag4]` after deleting in the database
 *
 * @param \Cake\ORM\Entity $sourceEntity an entity persisted in the source table for
 * this association
 * @param array $targetEntities list of entities persisted in the target table for
 * this association
 * @param bool $cleanProperty whether or not to remove all the objects in $targetEntities
 * that are stored in $sourceEntity
 * @throws \InvalidArgumentException if non persisted entities are passed or if
 * any of them is lacking a primary key value
 * @return void
 */
	public function unlink(Entity $sourceEntity, array $targetEntities, $cleanProperty = true) {
		$this->_checkPersistenceStatus($sourceEntity, $targetEntities);
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

/**
 * Replaces existing association links between the source entity and the target
 * with the ones passed. This method does a smart cleanup, links that are already
 * persisted and present in `$targetEntities` will not be deleted, new links will
 * be created for the passed target entities that are not already in the database
 * and the rest will be removed.
 *
 * For example, if an article is linked to tags 'cake' and 'framework' and you pass
 * to this method an array containing the entities for tags 'cake', 'php' and 'awesome',
 * only the link for cake will be kept in database, the link for 'framework' will be
 * deleted and the links for 'php' and 'awesome' will be created.
 *
 * Existing links are not deleted and created again, they are either left untouched
 * or updated so that potential extra information stored in the joint row is not
 * lost. Updating the link row can be done by making sure the corresponding passed
 * target entity contains the joint property with its primary key and any extra
 * information to be stored.
 *
 * On success, the passed `$sourceEntity` will contain `$targetEntities` as  value
 * in the corresponding property for this association.
 *
 * This method assumes that links between both the source entity and each of the
 * target entities are unique. That is, for any given row in the source table there
 * can only be one link in the junction table pointing to any other given row in
 * the target table.
 *
 * Additional options for new links to be saved can be passed in the third argument,
 * check `Table::save()` for information on the accepted options.
 *
 * ###Example:
 *
 * {{{
 * $article->tags = [$tag1, $tag2, $tag3, $tag4];
 * $articles->save($article);
 * $tags = [$tag1, $tag3];
 * $articles->association('tags')->replaceLinks($article, $tags);
 * }}}
 *
 * `$article->get('tags')` will contain only `[$tag1, $tag3]` at the end
 *
 * @param \Cake\ORM\Entity $sourceEntity an entity persisted in the source table for
 * this association
 * @param array $targetEntities list of entities from the target table to be linked
 * @param array $options list of options to be passed to `save` persisting or
 * updating new links
 * @throws \InvalidArgumentException if non persisted entities are passed or if
 * any of them is lacking a primary key value
 * @return bool success
 */
	public function replaceLinks(Entity $sourceEntity, array $targetEntities, array $options = []) {
		$primaryKey = (array)$this->source()->primaryKey();
		$primaryValue = $sourceEntity->extract($primaryKey);

		if (count(array_filter($primaryValue, 'strlen')) !== count($primaryKey)) {
			$message = 'Could not find primary key value for source entity';
			throw new \InvalidArgumentException($message);
		}

		return $this->junction()->connection()->transactional(
			function() use ($sourceEntity, $targetEntities, $primaryValue, $options) {
				$foreignKey = (array)$this->foreignKey();
				$hasMany = $this->source()->association($this->_junctionTable->alias());
				$existing = $hasMany->find('all')
					->where(array_combine($foreignKey, $primaryValue));

				$jointEntities = $this->_collectJointEntities($sourceEntity, $targetEntities);
				$inserts = $this->_diffLinks($existing, $jointEntities, $targetEntities);
				$options += ['associated' => false];

				if ($inserts && !$this->_saveTarget($sourceEntity, $inserts, $options)) {
					return false;
				}

				$property = $this->property();
				$inserted = array_combine(
					array_keys($inserts),
					(array)$sourceEntity->get($property)
				);
				$targetEntities = $inserted + $targetEntities;
				ksort($targetEntities);
				$sourceEntity->set($property, array_values($targetEntities));
				$sourceEntity->dirty($property, false);
				return true;
			}
		);
	}

/**
 * Helper method used to delete the difference between the links passed in
 * `$existing` and `$jointEntities`. This method will return the values from
 * `$targetEntities` that were not deleted from calculating the difference.
 *
 * @param \Cake\ORM\Query $existing a query for getting existing links
 * @param array $jointEntities link entities that should be persisted
 * @param array $targetEntities entities in target table that are related to
 * the `$jointEntitites`
 * @return array
 */
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
		$jointProperty = $this->_junctionProperty;
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

		return $targetEntities;
	}

/**
 * Throws an exception should any of the passed entities is not persisted.
 *
 * @throws \InvalidArgumentException
 * @param \Cake\ORM\Entity $sourceEntity the row belonging to the `source` side
 * of this association
 * @param array $targetEntities list of entities belonging to the `target` side
 * of this association
 * @return bool
 */
	protected function _checkPersistenceStatus($sourceEntity, array $targetEntities) {
		if ($sourceEntity->isNew() !== false) {
			$error = 'Source entity needs to be persisted before proceeding';
			throw new \InvalidArgumentException($error);
		}

		foreach ($targetEntities as $entity) {
			if ($entity->isNew() !== false) {
				$error = 'Cannot link not persisted entities';
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
		$jointProperty = $this->_junctionProperty;
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
		$hasMany = $source->association($junction->alias());
		$foreignKey = (array)$this->foreignKey();
		$assocForeignKey = (array)$belongsTo->foreignKey();
		$sourceKey = $sourceEntity->extract((array)$source->primaryKey());

		foreach ($missing as $key) {
			$unions[] = $hasMany->find('all')
				->where(array_combine($foreignKey, $sourceKey))
				->andWhere(array_combine($assocForeignKey, $key));
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
		$name = $this->_junctionAssociationName();
		return $query->matching($name, function($q) use ($key, $filter) {
			return $this->_addExternalConditions($q, $key, $filter);
		});
	}

/**
 * Generates a string used as a table field that contains the values upon
 * which the filter should be applied
 *
 * @param array $options
 * @return string
 */
	protected function _linkField($options) {
		$links = [];
		$name = $this->_junctionAssociationName();

		foreach ((array)$options['foreignKey'] as $key) {
			$links[] = sprintf('%s.%s', $name, $key);
		}

		if (count($links) === 1) {
			return $links[0];
		}

		return $links;
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
					$this->source()->alias(),
					$this->target()->alias()
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
 *
 * @param array $opts original list of options passed in constructor
 * @return void
 */
	protected function _options(array $opts) {
		$this->_externalOptions($opts);
		if (!empty($opts['targetForeignKey'])) {
			$this->targetForeignKey($opts['targetForeignKey']);
		}
		if (!empty($opts['joinTable'])) {
			$this->_junctionTableName($opts['joinTable']);
		}
		if (!empty($opts['through'])) {
			$this->_through = $opts['through'];
		}
		if (!empty($opts['saveStrategy'])) {
			$this->saveStrategy($opts['saveStrategy']);
		}
	}

}
