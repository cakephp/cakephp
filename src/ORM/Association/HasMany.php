<?php
/**
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
 * @since         3.0.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\ORM\Association;

use Cake\Collection\Collection;
use Cake\Database\Expression\FieldInterface;
use Cake\Database\Expression\QueryExpression;
use Cake\Datasource\EntityInterface;
use Cake\ORM\Association;
use Cake\ORM\Association\Loader\SelectLoader;
use Cake\ORM\Table;
use InvalidArgumentException;
use Traversable;

/**
 * Represents an N - 1 relationship where the target side of the relationship
 * will have one or multiple records per each one in the source side.
 *
 * An example of a HasMany association would be Author has many Articles.
 */
class HasMany extends Association
{

    use DependentDeleteTrait;

    /**
     * Order in which target records should be returned
     *
     * @var mixed
     */
    protected $_sort;

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
     * Valid strategies for this type of association
     *
     * @var array
     */
    protected $_validStrategies = [self::STRATEGY_SELECT, self::STRATEGY_SUBQUERY];

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
     * Saving strategy to be used by this association
     *
     * @var string
     */
    protected $_saveStrategy = self::SAVE_APPEND;

    /**
     * Returns whether or not the passed table is the owning side for this
     * association. This means that rows in the 'target' table would miss important
     * or required information if the row in 'source' did not exist.
     *
     * @param \Cake\ORM\Table $side The potential Table with ownership
     * @return bool
     */
    public function isOwningSide(Table $side)
    {
        return $side === $this->source();
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
     * @param \Cake\Datasource\EntityInterface $entity an entity from the source table
     * @param array|\ArrayObject $options options to be passed to the save method in
     * the target table
     * @return bool|\Cake\Datasource\EntityInterface false if $entity could not be saved, otherwise it returns
     * the saved entity
     * @see \Cake\ORM\Table::save()
     * @throws \InvalidArgumentException when the association data cannot be traversed.
     */
    public function saveAssociated(EntityInterface $entity, array $options = [])
    {
        $targetEntities = $entity->get($this->property());
        if (empty($targetEntities) && $this->_saveStrategy !== self::SAVE_REPLACE) {
            return $entity;
        }

        if (!is_array($targetEntities) && !($targetEntities instanceof Traversable)) {
            $name = $this->property();
            $message = sprintf('Could not save %s, it cannot be traversed', $name);
            throw new InvalidArgumentException($message);
        }

        $foreignKey = (array)$this->foreignKey();
        $properties = array_combine(
            $foreignKey,
            $entity->extract((array)$this->bindingKey())
        );
        $target = $this->target();
        $original = $targetEntities;
        $options['_sourceTable'] = $this->source();

        $unlinkSuccessful = null;
        if ($this->_saveStrategy === self::SAVE_REPLACE) {
            $unlinkSuccessful = $this->_unlinkAssociated($properties, $entity, $target, $targetEntities, $options);
        }

        if ($unlinkSuccessful === false) {
            return false;
        }

        foreach ($targetEntities as $k => $targetEntity) {
            if (!($targetEntity instanceof EntityInterface)) {
                break;
            }

            if (!empty($options['atomic'])) {
                $targetEntity = clone $targetEntity;
            }

            if ($properties !== $targetEntity->extract($foreignKey)) {
                $targetEntity->set($properties, ['guard' => false]);
            }

            if ($target->save($targetEntity, $options)) {
                $targetEntities[$k] = $targetEntity;
                continue;
            }

            if (!empty($options['atomic'])) {
                $original[$k]->errors($targetEntity->errors());
                $entity->set($this->property(), $original);

                return false;
            }
        }

        $entity->set($this->property(), $targetEntities);

        return $entity;
    }

    /**
     * Associates the source entity to each of the target entities provided.
     * When using this method, all entities in `$targetEntities` will be appended to
     * the source entity's property corresponding to this association object.
     *
     * This method does not check link uniqueness.
     * Changes are persisted in the database and also in the source entity.
     *
     * ### Example:
     *
     * ```
     * $user = $users->get(1);
     * $allArticles = $articles->find('all')->toArray();
     * $users->Articles->link($user, $allArticles);
     * ```
     *
     * `$user->get('articles')` will contain all articles in `$allArticles` after linking
     *
     * @param \Cake\Datasource\EntityInterface $sourceEntity the row belonging to the `source` side
     * of this association
     * @param array $targetEntities list of entities belonging to the `target` side
     * of this association
     * @param array $options list of options to be passed to the internal `save` call
     * @return bool true on success, false otherwise
     */
    public function link(EntityInterface $sourceEntity, array $targetEntities, array $options = [])
    {
        $saveStrategy = $this->saveStrategy();
        $this->saveStrategy(self::SAVE_APPEND);
        $property = $this->property();

        $currentEntities = array_unique(
            array_merge(
                (array)$sourceEntity->get($property),
                $targetEntities
            )
        );

        $sourceEntity->set($property, $currentEntities);

        $savedEntity = $this->saveAssociated($sourceEntity, $options);

        $ok = ($savedEntity instanceof EntityInterface);

        $this->saveStrategy($saveStrategy);

        if ($ok) {
            $sourceEntity->set($property, $savedEntity->get($property));
            $sourceEntity->dirty($property, false);
        }

        return $ok;
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
     * Changes are persisted in the database and also in the source entity.
     *
     * ### Example:
     *
     * ```
     * $user = $users->get(1);
     * $user->articles = [$article1, $article2, $article3, $article4];
     * $users->save($user, ['Associated' => ['Articles']]);
     * $allArticles = [$article1, $article2, $article3];
     * $users->Articles->unlink($user, $allArticles);
     * ```
     *
     * `$article->get('articles')` will contain only `[$article4]` after deleting in the database
     *
     * @param \Cake\Datasource\EntityInterface $sourceEntity an entity persisted in the source table for
     * this association
     * @param array $targetEntities list of entities persisted in the target table for
     * this association
     * @param array $options list of options to be passed to the internal `delete` call
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
        if (count($targetEntities) === 0) {
            return;
        }

        $foreignKey = (array)$this->foreignKey();
        $target = $this->target();
        $targetPrimaryKey = array_merge((array)$target->primaryKey(), $foreignKey);
        $property = $this->property();

        $conditions = [
            'OR' => (new Collection($targetEntities))
                ->map(function ($entity) use ($targetPrimaryKey) {
                    return $entity->extract($targetPrimaryKey);
                })
                ->toList()
        ];

        $this->_unlink($foreignKey, $target, $conditions, $options);

        $result = $sourceEntity->get($property);
        if ($options['cleanProperty'] && $result !== null) {
            $sourceEntity->set(
                $property,
                (new Collection($sourceEntity->get($property)))
                ->reject(
                    function ($assoc) use ($targetEntities) {
                        return in_array($assoc, $targetEntities);
                    }
                )
                ->toList()
            );
        }

        $sourceEntity->dirty($property, false);
    }

    /**
     * Replaces existing association links between the source entity and the target
     * with the ones passed. This method does a smart cleanup, links that are already
     * persisted and present in `$targetEntities` will not be deleted, new links will
     * be created for the passed target entities that are not already in the database
     * and the rest will be removed.
     *
     * For example, if an author has many articles, such as 'article1','article 2' and 'article 3' and you pass
     * to this method an array containing the entities for articles 'article 1' and 'article 4',
     * only the link for 'article 1' will be kept in database, the links for 'article 2' and 'article 3' will be
     * deleted and the link for 'article 4' will be created.
     *
     * Existing links are not deleted and created again, they are either left untouched
     * or updated.
     *
     * This method does not check link uniqueness.
     *
     * On success, the passed `$sourceEntity` will contain `$targetEntities` as  value
     * in the corresponding property for this association.
     *
     * Additional options for new links to be saved can be passed in the third argument,
     * check `Table::save()` for information on the accepted options.
     *
     * ### Example:
     *
     * ```
     * $author->articles = [$article1, $article2, $article3, $article4];
     * $authors->save($author);
     * $articles = [$article1, $article3];
     * $authors->association('articles')->replace($author, $articles);
     * ```
     *
     * `$author->get('articles')` will contain only `[$article1, $article3]` at the end
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
    public function replace(EntityInterface $sourceEntity, array $targetEntities, array $options = [])
    {
        $property = $this->property();
        $sourceEntity->set($property, $targetEntities);
        $saveStrategy = $this->saveStrategy();
        $this->saveStrategy(self::SAVE_REPLACE);
        $result = $this->saveAssociated($sourceEntity, $options);
        $ok = ($result instanceof EntityInterface);

        if ($ok) {
            $sourceEntity = $result;
        }
        $this->saveStrategy($saveStrategy);

        return $ok;
    }

    /**
     * Deletes/sets null the related objects according to the dependency between source and targets and foreign key nullability
     * Skips deleting records present in $remainingEntities
     *
     * @param array $properties array of foreignKey properties
     * @param \Cake\Datasource\EntityInterface $entity the entity which should have its associated entities unassigned
     * @param \Cake\ORM\Table $target The associated table
     * @param array $remainingEntities Entities that should not be deleted
     * @param array $options list of options accepted by `Table::delete()`
     * @return bool success
     */
    protected function _unlinkAssociated(array $properties, EntityInterface $entity, Table $target, array $remainingEntities = [], array $options = [])
    {
        $primaryKey = (array)$target->primaryKey();
        $exclusions = new Collection($remainingEntities);
        $exclusions = $exclusions->map(
            function ($ent) use ($primaryKey) {
                return $ent->extract($primaryKey);
            }
        )
        ->filter(
            function ($v) {
                return !in_array(null, array_values($v), true);
            }
        )
        ->toArray();

        $conditions = $properties;

        if (count($exclusions) > 0) {
            $conditions = [
                'NOT' => [
                    'OR' => $exclusions
                ],
                $properties
            ];
        }

        return $this->_unlink(array_keys($properties), $target, $conditions, $options);
    }

    /**
     * Deletes/sets null the related objects matching $conditions.
     * The action which is taken depends on the dependency between source and targets and also on foreign key nullability
     *
     * @param array $foreignKey array of foreign key properties
     * @param \Cake\ORM\Table $target The associated table
     * @param array $conditions The conditions that specifies what are the objects to be unlinked
     * @param array $options list of options accepted by `Table::delete()`
     * @return bool success
     */
    protected function _unlink(array $foreignKey, Table $target, array $conditions = [], array $options = [])
    {
        $mustBeDependent = (!$this->_foreignKeyAcceptsNull($target, $foreignKey) || $this->dependent());

        if ($mustBeDependent) {
            if ($this->_cascadeCallbacks) {
                $conditions = new QueryExpression($conditions);
                $conditions->traverse(function ($entry) use ($target) {
                    if ($entry instanceof FieldInterface) {
                        $entry->setField($target->aliasField($entry->getField()));
                    }
                });
                $query = $this->find('all')->where($conditions);
                $ok = true;
                foreach ($query as $assoc) {
                    $ok = $ok && $target->delete($assoc, $options);
                }

                return $ok;
            }

            $target->deleteAll($conditions);

            return true;
        }

        $updateFields = array_fill_keys($foreignKey, null);
        $target->updateAll($updateFields, $conditions);

        return true;
    }

    /**
     * Checks the nullable flag of the foreign key
     *
     * @param \Cake\ORM\Table $table the table containing the foreign key
     * @param array $properties the list of fields that compose the foreign key
     * @return bool
     */
    protected function _foreignKeyAcceptsNull(Table $table, array $properties)
    {
        return !in_array(
            false,
            array_map(
                function ($prop) use ($table) {
                    return $table->schema()->isNullable($prop);
                },
                $properties
            )
        );
    }

    /**
     * Get the relationship type.
     *
     * @return string
     */
    public function type()
    {
        return self::ONE_TO_MANY;
    }

    /**
     * Whether this association can be expressed directly in a query join
     *
     * @param array $options custom options key that could alter the return value
     * @return bool if the 'matching' key in $option is true then this function
     * will return true, false otherwise
     */
    public function canBeJoined(array $options = [])
    {
        return !empty($options['matching']);
    }

    /**
     * Sets the name of the field representing the foreign key to the source table.
     * If no parameters are passed current field is returned
     *
     * @param string|null $key the key to be used to link both tables together
     * @return string
     */
    public function foreignKey($key = null)
    {
        if ($key === null) {
            if ($this->_foreignKey === null) {
                $this->_foreignKey = $this->_modelKey($this->source()->table());
            }

            return $this->_foreignKey;
        }

        return parent::foreignKey($key);
    }

    /**
     * Sets the sort order in which target records should be returned.
     * If no arguments are passed the currently configured value is returned
     *
     * @param mixed $sort A find() compatible order clause
     * @return mixed
     */
    public function sort($sort = null)
    {
        if ($sort !== null) {
            $this->_sort = $sort;
        }

        return $this->_sort;
    }

    /**
     * {@inheritDoc}
     */
    public function defaultRowValue($row, $joined)
    {
        $sourceAlias = $this->source()->alias();
        if (isset($row[$sourceAlias])) {
            $row[$sourceAlias][$this->property()] = $joined ? null : [];
        }

        return $row;
    }

    /**
     * Parse extra options passed in the constructor.
     *
     * @param array $opts original list of options passed in constructor
     * @return void
     */
    protected function _options(array $opts)
    {
        if (!empty($opts['saveStrategy'])) {
            $this->saveStrategy($opts['saveStrategy']);
        }
        if (isset($opts['sort'])) {
            $this->sort($opts['sort']);
        }
    }

    /**
     * {@inheritDoc}
     *
     * @return callable
     */
    public function eagerLoader(array $options)
    {
        $loader = new SelectLoader([
            'alias' => $this->alias(),
            'sourceAlias' => $this->source()->alias(),
            'targetAlias' => $this->target()->alias(),
            'foreignKey' => $this->foreignKey(),
            'bindingKey' => $this->bindingKey(),
            'strategy' => $this->strategy(),
            'associationType' => $this->type(),
            'sort' => $this->sort(),
            'finder' => [$this, 'find']
        ]);

        return $loader->buildLoadingQuery($options);
    }
}
