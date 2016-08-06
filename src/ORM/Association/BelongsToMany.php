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

use Cake\Core\App;
use Cake\Database\ExpressionInterface;
use Cake\Database\Expression\IdentifierExpression;
use Cake\Datasource\EntityInterface;
use Cake\ORM\Association;
use Cake\ORM\Query;
use Cake\ORM\Table;
use Cake\Utility\Inflector;
use InvalidArgumentException;
use RuntimeException;
use SplObjectStorage;
use Traversable;

/**
 * Represents an M - N relationship where there exists a junction - or join - table
 * that contains the association fields between the source and the target table.
 *
 * An example of a BelongsToMany association would be Article belongs to many Tags.
 */
class BelongsToMany extends Association
{

    use ExternalAssociationTrait {
        _options as _externalOptions;
        _buildQuery as _buildBaseQuery;
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
    protected $_strategy = self::STRATEGY_SELECT;

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
     * Valid strategies for this type of association
     *
     * @var array
     */
    protected $_validStrategies = [self::STRATEGY_SELECT, self::STRATEGY_SUBQUERY];

    /**
     * Whether the records on the joint table should be removed when a record
     * on the source table is deleted.
     *
     * Defaults to true for backwards compatibility.
     *
     * @var bool
     */
    protected $_dependent = true;

    /**
     * Filtered conditions that reference the target table.
     *
     * @var null|array
     */
    protected $_targetConditions;

    /**
     * Filtered conditions that reference the junction table.
     *
     * @var null|array
     */
    protected $_junctionConditions;

    /**
     * Sets the name of the field representing the foreign key to the target table.
     * If no parameters are passed current field is returned
     *
     * @param string|null $key the key to be used to link both tables together
     * @return string
     */
    public function targetForeignKey($key = null)
    {
        if ($key === null) {
            if ($this->_targetForeignKey === null) {
                $this->_targetForeignKey = $this->_modelKey($this->target()->alias());
            }

            return $this->_targetForeignKey;
        }

        return $this->_targetForeignKey = $key;
    }

    /**
     * Sets the table instance for the junction relation. If no arguments
     * are passed, the current configured table instance is returned
     *
     * @param string|\Cake\ORM\Table|null $table Name or instance for the join table
     * @return \Cake\ORM\Table
     */
    public function junction($table = null)
    {
        if ($table === null && $this->_junctionTable) {
            return $this->_junctionTable;
        }

        $tableLocator = $this->tableLocator();
        if ($table === null && $this->_through) {
            $table = $this->_through;
        } elseif ($table === null) {
            $tableName = $this->_junctionTableName();
            $tableAlias = Inflector::camelize($tableName);

            $config = [];
            if (!$tableLocator->exists($tableAlias)) {
                $config = ['table' => $tableName];

                // Propagate the connection if we'll get an auto-model
                if (!App::className($tableAlias, 'Model/Table', 'Table')) {
                    $config['connection'] = $this->source()->connection();
                }
            }
            $table = $tableLocator->get($tableAlias, $config);
        }

        if (is_string($table)) {
            $table = $tableLocator->get($table);
        }
        $source = $this->source();
        $target = $this->target();

        $this->_generateSourceAssociations($table, $source);
        $this->_generateTargetAssociations($table, $source, $target);
        $this->_generateJunctionAssociations($table, $source, $target);

        return $this->_junctionTable = $table;
    }

    /**
     * Generate reciprocal associations as necessary.
     *
     * Generates the following associations:
     *
     * - target hasMany junction e.g. Articles hasMany ArticlesTags
     * - target belongsToMany source e.g Articles belongsToMany Tags.
     *
     * You can override these generated associations by defining associations
     * with the correct aliases.
     *
     * @param \Cake\ORM\Table $junction The junction table.
     * @param \Cake\ORM\Table $source The source table.
     * @param \Cake\ORM\Table $target The target table.
     * @return void
     */
    protected function _generateTargetAssociations($junction, $source, $target)
    {
        $junctionAlias = $junction->alias();
        $sAlias = $source->alias();

        if (!$target->association($junctionAlias)) {
            $target->hasMany($junctionAlias, [
                'targetTable' => $junction,
                'foreignKey' => $this->targetForeignKey(),
            ]);
        }
        if (!$target->association($sAlias)) {
            $target->belongsToMany($sAlias, [
                'sourceTable' => $target,
                'targetTable' => $source,
                'foreignKey' => $this->targetForeignKey(),
                'targetForeignKey' => $this->foreignKey(),
                'through' => $junction,
                'conditions' => $this->conditions(),
            ]);
        }
    }

    /**
     * Generate additional source table associations as necessary.
     *
     * Generates the following associations:
     *
     * - source hasMany junction e.g. Tags hasMany ArticlesTags
     *
     * You can override these generated associations by defining associations
     * with the correct aliases.
     *
     * @param \Cake\ORM\Table $junction The junction table.
     * @param \Cake\ORM\Table $source The source table.
     * @return void
     */
    protected function _generateSourceAssociations($junction, $source)
    {
        $junctionAlias = $junction->alias();
        if (!$source->association($junctionAlias)) {
            $source->hasMany($junctionAlias, [
                'targetTable' => $junction,
                'foreignKey' => $this->foreignKey(),
            ]);
        }
    }

    /**
     * Generate associations on the junction table as necessary
     *
     * Generates the following associations:
     *
     * - junction belongsTo source e.g. ArticlesTags belongsTo Tags
     * - junction belongsTo target e.g. ArticlesTags belongsTo Articles
     *
     * You can override these generated associations by defining associations
     * with the correct aliases.
     *
     * @param \Cake\ORM\Table $junction The junction table.
     * @param \Cake\ORM\Table $source The source table.
     * @param \Cake\ORM\Table $target The target table.
     * @return void
     */
    protected function _generateJunctionAssociations($junction, $source, $target)
    {
        $tAlias = $target->alias();
        $sAlias = $source->alias();

        if (!$junction->association($tAlias)) {
            $junction->belongsTo($tAlias, [
                'foreignKey' => $this->targetForeignKey(),
                'targetTable' => $target
            ]);
        }
        if (!$junction->association($sAlias)) {
            $junction->belongsTo($sAlias, [
                'foreignKey' => $this->foreignKey(),
                'targetTable' => $source
            ]);
        }
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
     * @param \Cake\ORM\Query $query the query to be altered to include the target table data
     * @param array $options Any extra options or overrides to be taken in account
     * @return void
     */
    public function attachTo(Query $query, array $options = [])
    {
        if (!empty($options['negateMatch'])) {
            $this->_appendNotMatching($query, $options);

            return;
        }

        $junction = $this->junction();
        $belongsTo = $junction->association($this->source()->alias());
        $cond = $belongsTo->_joinCondition(['foreignKey' => $belongsTo->foreignKey()]);
        $cond += $this->junctionConditions();

        if (isset($options['includeFields'])) {
            $includeFields = $options['includeFields'];
        }

        // Attach the junction table as well we need it to populate _joinData.
        $assoc = $this->_targetTable->association($junction->alias());
        $newOptions = array_intersect_key($options, ['joinType' => 1, 'fields' => 1]);
        $newOptions += [
            'conditions' => $cond,
            'includeFields' => $includeFields,
            'foreignKey' => false,
        ];
        $assoc->attachTo($query, $newOptions);
        $query->eagerLoader()->addToJoinsMap($junction->alias(), $assoc, true);

        parent::attachTo($query, $options);

        $foreignKey = $this->targetForeignKey();
        $thisJoin = $query->clause('join')[$this->name()];
        $thisJoin['conditions']->add($assoc->_joinCondition(['foreignKey' => $foreignKey]));
    }

    /**
     * {@inheritDoc}
     */
    protected function _appendNotMatching($query, $options)
    {
        if (empty($options['negateMatch'])) {
            return;
        }
        $options += ['conditions' => []];
        $junction = $this->junction();
        $belongsTo = $junction->association($this->source()->alias());
        $conds = $belongsTo->_joinCondition(['foreignKey' => $belongsTo->foreignKey()]);

        $subquery = $this->find()
            ->select(array_values($conds))
            ->where($options['conditions'])
            ->andWhere($this->junctionConditions());

        $subquery = $options['queryBuilder']($subquery);

        $assoc = $junction->association($this->target()->alias());
        $conditions = $assoc->_joinCondition([
            'foreignKey' => $this->targetForeignKey()
        ]);
        $subquery = $this->_appendJunctionJoin($subquery, $conditions);

        $query
            ->andWhere(function ($exp) use ($subquery, $conds) {
                $identifiers = [];
                foreach (array_keys($conds) as $field) {
                    $identifiers = new IdentifierExpression($field);
                }
                $identifiers = $subquery->newExpr()->add($identifiers)->tieWith(',');
                $nullExp = clone $exp;

                return $exp
                    ->or_([
                        $exp->notIn($identifiers, $subquery),
                        $nullExp->and(array_map([$nullExp, 'isNull'], array_keys($conds))),
                    ]);
            });
    }

    /**
     * {@inheritDoc}
     */
    public function transformRow($row, $nestKey, $joined)
    {
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
    public function type()
    {
        return self::MANY_TO_MANY;
    }

    /**
     * Return false as join conditions are defined in the junction table
     *
     * @param array $options list of options passed to attachTo method
     * @return bool false
     */
    protected function _joinCondition($options)
    {
        return false;
    }

    /**
     * Builds an array containing the results from fetchQuery indexed by
     * the foreignKey value corresponding to this association.
     *
     * @param \Cake\ORM\Query $fetchQuery The query to get results from
     * @param array $options The options passed to the eager loader
     * @return array
     * @throws \RuntimeException when the association property is not part of the results set.
     */
    protected function _buildResultMap($fetchQuery, $options)
    {
        $resultMap = [];
        $key = (array)$options['foreignKey'];
        $property = $this->target()->association($this->junction()->alias())->property();
        $hydrated = $fetchQuery->hydrate();

        foreach ($fetchQuery->all() as $result) {
            if (!isset($result[$property])) {
                throw new RuntimeException(sprintf(
                    '"%s" is missing from the belongsToMany results. Results cannot be created.',
                    $property
                ));
            }
            $result[$this->_junctionProperty] = $result[$property];


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
     * @param \Cake\Datasource\EntityInterface $entity The entity that started the cascading delete.
     * @param array $options The options for the original delete.
     * @return bool Success.
     */
    public function cascadeDelete(EntityInterface $entity, array $options = [])
    {
        if (!$this->dependent()) {
            return true;
        }
        $foreignKey = (array)$this->foreignKey();
        $bindingKey = (array)$this->bindingKey();
        $conditions = [];

        if (!empty($bindingKey)) {
            $conditions = array_combine($foreignKey, $entity->extract($bindingKey));
        }

        $table = $this->junction();
        $hasMany = $this->source()->association($table->alias());
        if ($this->_cascadeCallbacks) {
            foreach ($hasMany->find('all')->where($conditions)->toList() as $related) {
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
    public function isOwningSide(Table $side)
    {
        return true;
    }

    /**
     * Sets the strategy that should be used for saving. If called with no
     * arguments, it will return the currently configured strategy
     *
     * @param string|null $strategy the strategy name to be used
     * @throws \InvalidArgumentException if an invalid strategy name is passed
     * @return string the strategy to be used for saving
     */
    public function saveStrategy($strategy = null)
    {
        if ($strategy === null) {
            return $this->_saveStrategy;
        }
        if (!in_array($strategy, [self::SAVE_APPEND, self::SAVE_REPLACE])) {
            $msg = sprintf('Invalid save strategy "%s"', $strategy);
            throw new InvalidArgumentException($msg);
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
     * @param \Cake\Datasource\EntityInterface $entity an entity from the source table
     * @param array|\ArrayObject $options options to be passed to the save method in
     * the target table
     * @throws \InvalidArgumentException if the property representing the association
     * in the parent entity cannot be traversed
     * @return bool|\Cake\Datasource\EntityInterface false if $entity could not be saved, otherwise it returns
     * the saved entity
     * @see \Cake\ORM\Table::save()
     * @see \Cake\ORM\Association\BelongsToMany::replaceLinks()
     */
    public function saveAssociated(EntityInterface $entity, array $options = [])
    {
        $targetEntity = $entity->get($this->property());
        $strategy = $this->saveStrategy();

        $isEmpty = in_array($targetEntity, [null, [], '', false], true);
        if ($isEmpty && $entity->isNew()) {
            return $entity;
        }
        if ($isEmpty) {
            $targetEntity = [];
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
     * @param \Cake\Datasource\EntityInterface $parentEntity the source entity containing the target
     * entities to be saved.
     * @param array|\Traversable $entities list of entities to persist in target table and to
     * link to the parent entity
     * @param array $options list of options accepted by `Table::save()`
     * @throws \InvalidArgumentException if the property representing the association
     * in the parent entity cannot be traversed
     * @return \Cake\Datasource\EntityInterface|bool The parent entity after all links have been
     * created if no errors happened, false otherwise
     */
    protected function _saveTarget(EntityInterface $parentEntity, $entities, $options)
    {
        $joinAssociations = false;
        if (!empty($options['associated'][$this->_junctionProperty]['associated'])) {
            $joinAssociations = $options['associated'][$this->_junctionProperty]['associated'];
        }
        unset($options['associated'][$this->_junctionProperty]);

        if (!(is_array($entities) || $entities instanceof Traversable)) {
            $name = $this->property();
            $message = sprintf('Could not save %s, it cannot be traversed', $name);
            throw new InvalidArgumentException($message);
        }

        $table = $this->target();
        $original = $entities;
        $persisted = [];

        foreach ($entities as $k => $entity) {
            if (!($entity instanceof EntityInterface)) {
                break;
            }

            if (!empty($options['atomic'])) {
                $entity = clone $entity;
            }

            $saved = $table->save($entity, $options);
            if ($saved) {
                $entities[$k] = $entity;
                $persisted[] = $entity;
                continue;
            }

            // Saving the new linked entity failed, copy errors back into the
            // original entity if applicable and abort.
            if (!empty($options['atomic'])) {
                $original[$k]->errors($entity->errors());
            }
            if (!$saved) {
                return false;
            }
        }

        $options['associated'] = $joinAssociations;
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
     * @param \Cake\Datasource\EntityInterface $sourceEntity the entity from source table in this
     * association
     * @param array $targetEntities list of entities to link to link to the source entity using the
     * junction table
     * @param array $options list of options accepted by `Table::save()`
     * @return bool success
     */
    protected function _saveLinks(EntityInterface $sourceEntity, $targetEntities, $options)
    {
        $target = $this->target();
        $junction = $this->junction();
        $entityClass = $junction->entityClass();
        $belongsTo = $junction->association($target->alias());
        $foreignKey = (array)$this->foreignKey();
        $assocForeignKey = (array)$belongsTo->foreignKey();
        $targetPrimaryKey = (array)$target->primaryKey();
        $bindingKey = (array)$this->bindingKey();
        $jointProperty = $this->_junctionProperty;
        $junctionAlias = $junction->alias();

        foreach ($targetEntities as $e) {
            $joint = $e->get($jointProperty);
            if (!$joint || !($joint instanceof EntityInterface)) {
                $joint = new $entityClass([], ['markNew' => true, 'source' => $junctionAlias]);
            }
            $sourceKeys = array_combine($foreignKey, $sourceEntity->extract($bindingKey));
            $targetKeys = array_combine($assocForeignKey, $e->extract($targetPrimaryKey));

            if ($sourceKeys !== $joint->extract($foreignKey)) {
                $joint->set($sourceKeys, ['guard' => false]);
            }

            if ($targetKeys !== $joint->extract($assocForeignKey)) {
                $joint->set($targetKeys, ['guard' => false]);
            }

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
     * the target entities are assumed to be already persisted, if they are marked
     * as new or their status is unknown then an exception will be thrown.
     *
     * When using this method, all entities in `$targetEntities` will be appended to
     * the source entity's property corresponding to this association object.
     *
     * This method does not check link uniqueness.
     *
     * ### Example:
     *
     * ```
     * $newTags = $tags->find('relevant')->toArray();
     * $articles->association('tags')->link($article, $newTags);
     * ```
     *
     * `$article->get('tags')` will contain all tags in `$newTags` after liking
     *
     * @param \Cake\Datasource\EntityInterface $sourceEntity the row belonging to the `source` side
     * of this association
     * @param array $targetEntities list of entities belonging to the `target` side
     * of this association
     * @param array $options list of options to be passed to the internal `save` call
     * @throws \InvalidArgumentException when any of the values in $targetEntities is
     * detected to not be already persisted
     * @return bool true on success, false otherwise
     */
    public function link(EntityInterface $sourceEntity, array $targetEntities, array $options = [])
    {
        $this->_checkPersistenceStatus($sourceEntity, $targetEntities);
        $property = $this->property();
        $links = $sourceEntity->get($property) ?: [];
        $links = array_merge($links, $targetEntities);
        $sourceEntity->set($property, $links);

        return $this->junction()->connection()->transactional(
            function () use ($sourceEntity, $targetEntities, $options) {
                return $this->_saveLinks($sourceEntity, $targetEntities, $options);
            }
        );
    }

    /**
     * Removes all links between the passed source entity and each of the provided
     * target entities. This method assumes that all passed objects are already persisted
     * in the database and that each of them contain a primary key value.
     *
     * ### Options
     *
     * Additionally to the default options accepted by `Table::delete()`, the following
     * keys are supported:
     *
     * - cleanProperty: Whether or not to remove all the objects in `$targetEntities` that
     * are stored in `$sourceEntity` (default: true)
     *
     * By default this method will unset each of the entity objects stored inside the
     * source entity.
     *
     * ### Example:
     *
     * ```
     * $article->tags = [$tag1, $tag2, $tag3, $tag4];
     * $tags = [$tag1, $tag2, $tag3];
     * $articles->association('tags')->unlink($article, $tags);
     * ```
     *
     * `$article->get('tags')` will contain only `[$tag4]` after deleting in the database
     *
     * @param \Cake\Datasource\EntityInterface $sourceEntity an entity persisted in the source table for
     * this association
     * @param array $targetEntities list of entities persisted in the target table for
     * this association
     * @param array|bool $options list of options to be passed to the internal `delete` call,
     * or a `boolean`
     * @throws \InvalidArgumentException if non persisted entities are passed or if
     * any of them is lacking a primary key value
     * @return void
     */
    public function unlink(EntityInterface $sourceEntity, array $targetEntities, $options = [])
    {
        if (is_bool($options)) {
            $options = [
                'cleanProperty' => $options
            ];
        } else {
            $options += ['cleanProperty' => true];
        }

        $this->_checkPersistenceStatus($sourceEntity, $targetEntities);
        $property = $this->property();

        $this->junction()->connection()->transactional(
            function () use ($sourceEntity, $targetEntities, $options) {
                $links = $this->_collectJointEntities($sourceEntity, $targetEntities);
                foreach ($links as $entity) {
                    $this->_junctionTable->delete($entity, $options);
                }
            }
        );

        $existing = $sourceEntity->get($property) ?: [];
        if (!$options['cleanProperty'] || empty($existing)) {
            return;
        }

        $storage = new SplObjectStorage;
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
     * {@inheritDoc}
     */
    public function conditions($conditions = null)
    {
        if ($conditions !== null) {
            $this->_conditions = $conditions;
            $this->_targetConditions = $this->_junctionConditions = null;
        }

        return $this->_conditions;
    }

    /**
     * Returns filtered conditions that reference the target table.
     *
     * Any string expressions, or expression objects will
     * also be returned in this list.
     *
     * @return mixed Generally an array. If the conditions
     *   are not an array, the association conditions will be
     *   returned unmodified.
     */
    protected function targetConditions()
    {
        if ($this->_targetConditions !== null) {
            return $this->_targetConditions;
        }
        $conditions = $this->conditions();
        if (!is_array($conditions)) {
            return $conditions;
        }
        $matching = [];
        $alias = $this->alias() . '.';
        foreach ($conditions as $field => $value) {
            if (is_string($field) && strpos($field, $alias) === 0) {
                $matching[$field] = $value;
            } elseif (is_int($field) || $value instanceof ExpressionInterface) {
                $matching[$field] = $value;
            }
        }

        return $this->_targetConditions = $matching;
    }

    /**
     * Returns filtered conditions that specifically reference
     * the junction table.
     *
     * @return array
     */
    protected function junctionConditions()
    {
        if ($this->_junctionConditions !== null) {
            return $this->_junctionConditions;
        }
        $matching = [];
        $conditions = $this->conditions();
        if (!is_array($conditions)) {
            return $matching;
        }
        $alias = $this->_junctionAssociationName() . '.';
        foreach ($conditions as $field => $value) {
            $isString = is_string($field);
            if ($isString && strpos($field, $alias) === 0) {
                $matching[$field] = $value;
            }
            // Assume that operators contain junction conditions.
            // Trying to munge complex conditions could result in incorrect queries.
            if ($isString && in_array(strtoupper($field), ['OR', 'NOT', 'AND', 'XOR'])) {
                $matching[$field] = $value;
            }
        }

        return $this->_junctionConditions = $matching;
    }

    /**
     * Proxies the finding operation to the target table's find method
     * and modifies the query accordingly based of this association
     * configuration.
     *
     * If your association includes conditions, the junction table will be
     * included in the query's contained associations.
     *
     * @param string|array|null $type the type of query to perform, if an array is passed,
     *   it will be interpreted as the `$options` parameter
     * @param array $options The options to for the find
     * @see \Cake\ORM\Table::find()
     * @return \Cake\ORM\Query
     */
    public function find($type = null, array $options = [])
    {
        $type = $type ?: $this->finder();
        list($type, $opts) = $this->_extractFinder($type);
        $query = $this->target()
            ->find($type, $options + $opts)
            ->where($this->targetConditions());

        if (!$this->junctionConditions()) {
            return $query;
        }

        $belongsTo = $this->junction()->association($this->target()->alias());
        $conditions = $belongsTo->_joinCondition([
            'foreignKey' => $this->targetForeignKey()
        ]);
        $conditions += $this->junctionConditions();

        return $this->_appendJunctionJoin($query, $conditions);
    }

    /**
     * Append a join to the junction table.
     *
     * @param \Cake\ORM\Query $query The query to append.
     * @param string|array $conditions The query conditions to use.
     * @return \Cake\ORM\Query The modified query.
     */
    protected function _appendJunctionJoin($query, $conditions)
    {
        $name = $this->_junctionAssociationName();
        $joins = $query->join();
        $matching = [
            $name => [
                'table' => $this->junction()->table(),
                'conditions' => $conditions,
                'type' => 'INNER'
            ]
        ];

        $assoc = $this->target()->association($name);
        $query
            ->addDefaultTypes($assoc->target())
            ->join($matching + $joins, [], true);
        $query->eagerLoader()->addToJoinsMap($name, $assoc);

        return $query;
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
     * ### Example:
     *
     * ```
     * $article->tags = [$tag1, $tag2, $tag3, $tag4];
     * $articles->save($article);
     * $tags = [$tag1, $tag3];
     * $articles->association('tags')->replaceLinks($article, $tags);
     * ```
     *
     * `$article->get('tags')` will contain only `[$tag1, $tag3]` at the end
     *
     * @param \Cake\Datasource\EntityInterface $sourceEntity an entity persisted in the source table for
     * this association
     * @param array $targetEntities list of entities from the target table to be linked
     * @param array $options list of options to be passed to the internal `save`/`delete` calls
     * when persisting/updating new links, or deleting existing ones
     * @throws \InvalidArgumentException if non persisted entities are passed or if
     * any of them is lacking a primary key value
     * @return bool success
     */
    public function replaceLinks(EntityInterface $sourceEntity, array $targetEntities, array $options = [])
    {
        $bindingKey = (array)$this->bindingKey();
        $primaryValue = $sourceEntity->extract($bindingKey);

        if (count(array_filter($primaryValue, 'strlen')) !== count($bindingKey)) {
            $message = 'Could not find primary key value for source entity';
            throw new InvalidArgumentException($message);
        }

        return $this->junction()->connection()->transactional(
            function () use ($sourceEntity, $targetEntities, $primaryValue, $options) {
                $foreignKey = array_map([$this->_junctionTable, 'aliasField'], (array)$this->foreignKey());
                $hasMany = $this->source()->association($this->_junctionTable->alias());
                $existing = $hasMany->find('all')
                    ->where(array_combine($foreignKey, $primaryValue));

                $associationConditions = $this->conditions();
                if ($associationConditions) {
                    $existing->contain($this->target()->alias());
                    $existing->andWhere($associationConditions);
                }

                $jointEntities = $this->_collectJointEntities($sourceEntity, $targetEntities);
                $inserts = $this->_diffLinks($existing, $jointEntities, $targetEntities, $options);

                if ($inserts && !$this->_saveTarget($sourceEntity, $inserts, $options)) {
                    return false;
                }

                $property = $this->property();

                if (count($inserts)) {
                    $inserted = array_combine(
                        array_keys($inserts),
                        (array)$sourceEntity->get($property)
                    );
                    $targetEntities = $inserted + $targetEntities;
                }

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
     * the `$jointEntities`
     * @param array $options list of options accepted by `Table::delete()`
     * @return array
     */
    protected function _diffLinks($existing, $jointEntities, $targetEntities, $options = [])
    {
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
            if (!($entity instanceof EntityInterface)) {
                continue;
            }
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
                $junction->delete($entity, $options);
            }
        }

        return $targetEntities;
    }

    /**
     * Throws an exception should any of the passed entities is not persisted.
     *
     * @param \Cake\Datasource\EntityInterface $sourceEntity the row belonging to the `source` side
     *   of this association
     * @param array $targetEntities list of entities belonging to the `target` side
     *   of this association
     * @return bool
     * @throws \InvalidArgumentException
     */
    protected function _checkPersistenceStatus($sourceEntity, array $targetEntities)
    {
        if ($sourceEntity->isNew()) {
            $error = 'Source entity needs to be persisted before proceeding';
            throw new InvalidArgumentException($error);
        }

        foreach ($targetEntities as $entity) {
            if ($entity->isNew()) {
                $error = 'Cannot link not persisted entities';
                throw new InvalidArgumentException($error);
            }
        }

        return true;
    }

    /**
     * Returns the list of joint entities that exist between the source entity
     * and each of the passed target entities
     *
     * @param \Cake\Datasource\EntityInterface $sourceEntity The row belonging to the source side
     *   of this association.
     * @param array $targetEntities The rows belonging to the target side of this
     *   association.
     * @throws \InvalidArgumentException if any of the entities is lacking a primary
     *   key value
     * @return array
     */
    protected function _collectJointEntities($sourceEntity, $targetEntities)
    {
        $target = $this->target();
        $source = $this->source();
        $junction = $this->junction();
        $jointProperty = $this->_junctionProperty;
        $primary = (array)$target->primaryKey();

        $result = [];
        $missing = [];

        foreach ($targetEntities as $entity) {
            if (!($entity instanceof EntityInterface)) {
                continue;
            }
            $joint = $entity->get($jointProperty);

            if (!$joint || !($joint instanceof EntityInterface)) {
                $missing[] = $entity->extract($primary);
                continue;
            }

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
     * Auxiliary function to construct a new Query object to return all the records
     * in the target table that are associated to those specified in $options from
     * the source table.
     *
     * This is used for eager loading records on the target table based on conditions.
     *
     * @param array $options options accepted by eagerLoader()
     * @return \Cake\ORM\Query
     * @throws \InvalidArgumentException When a key is required for associations but not selected.
     */
    protected function _buildQuery($options)
    {
        $name = $this->_junctionAssociationName();
        $assoc = $this->target()->association($name);
        $queryBuilder = false;

        if (!empty($options['queryBuilder'])) {
            $queryBuilder = $options['queryBuilder'];
            unset($options['queryBuilder']);
        }

        $query = $this->_buildBaseQuery($options);
        $query->addDefaultTypes($assoc->target());

        if ($queryBuilder) {
            $query = $queryBuilder($query);
        }

        $query = $this->_appendJunctionJoin($query, []);

        if ($query->autoFields() === null) {
            $query->autoFields($query->clause('select') === []);
        }

        // Ensure that association conditions are applied
        // and that the required keys are in the selected columns.
        $query
            ->where($this->junctionConditions())
            ->select($query->aliasFields((array)$assoc->foreignKey(), $name));

        $assoc->attachTo($query);
        return $query;
    }

    /**
     * Generates a string used as a table field that contains the values upon
     * which the filter should be applied
     *
     * @param array $options the options to use for getting the link field.
     * @return string
     */
    protected function _linkField($options)
    {
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
    protected function _junctionAssociationName()
    {
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
     * @param string|null $name The name of the junction table.
     * @return string
     */
    protected function _junctionTableName($name = null)
    {
        if ($name === null) {
            if (empty($this->_junctionTableName)) {
                $tablesNames = array_map('\Cake\Utility\Inflector::underscore', [
                    $this->source()->table(),
                    $this->target()->table()
                ]);
                sort($tablesNames);
                $this->_junctionTableName = implode('_', $tablesNames);
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
    protected function _options(array $opts)
    {
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
