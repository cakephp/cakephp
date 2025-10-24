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
namespace Cake\ORM\Behavior;

use ArrayObject;
use Cake\Datasource\EntityInterface;
use Cake\Event\EventInterface;
use Cake\ORM\Association;
use Cake\ORM\Association\BelongsTo;
use Cake\ORM\Behavior;
use Cake\ORM\Query\SelectQuery;
use Closure;

/**
 * CounterCache behavior
 *
 * Enables models to cache the amount of connections in a given relation.
 *
 * Examples with Post model belonging to User model
 *
 * Regular counter cache
 * ```
 * [
 *     'Users' => [
 *         'post_count'
 *     ]
 * ]
 * ```
 *
 * Counter cache with scope
 * ```
 * [
 *     'Users' => [
 *         'posts_published' => [
 *             'conditions' => [
 *                 'published' => true
 *             ]
 *         ]
 *     ]
 * ]
 * ```
 *
 * Counter cache using custom find
 * ```
 * [
 *     'Users' => [
 *         'posts_published' => [
 *             'finder' => 'published' // Will be using findPublished()
 *         ]
 *     ]
 * ]
 * ```
 *
 * Counter cache using lambda function returning the count
 * This is equivalent to example #2
 *
 * ```
 * [
 *     'Users' => [
 *         'posts_published' => function (EventInterface $event, EntityInterface $entity, Table $table) {
 *             $query = $table->find('all')->where([
 *                 'published' => true,
 *                 'user_id' => $entity->get('user_id')
 *             ]);
 *             return $query->count();
 *          }
 *     ]
 * ]
 * ```
 *
 * When using a lambda function you can return `false` to disable updating the counter value
 * for the current operation.
 *
 * Ignore updating the field if it is dirty
 * ```
 * [
 *     'Users' => [
 *         'posts_published' => [
 *             'ignoreDirty' => true
 *         ]
 *     ]
 * ]
 * ```
 *
 * You can disable counter updates entirely by sending the `ignoreCounterCache` option
 * to your save operation:
 *
 * ```
 * $this->Articles->save($article, ['ignoreCounterCache' => true]);
 * ```
 */
class CounterCacheBehavior extends Behavior
{
    /**
     * Store the fields which should be ignored
     *
     * @var array<string, array<string, bool>>
     */
    protected array $_ignoreDirty = [];

    /**
     * beforeSave callback.
     *
     * Check if a field, which should be ignored, is dirty
     *
     * @param \Cake\Event\EventInterface<\Cake\ORM\Table> $event The beforeSave event that was fired
     * @param \Cake\Datasource\EntityInterface $entity The entity that is going to be saved
     * @param \ArrayObject<string, mixed> $options The options for the query
     * @return void
     */
    public function beforeSave(EventInterface $event, EntityInterface $entity, ArrayObject $options): void
    {
        if (isset($options['ignoreCounterCache']) && $options['ignoreCounterCache'] === true) {
            return;
        }

        foreach ($this->_config as $assoc => $settings) {
            $assoc = $this->_table->getAssociation($assoc);
            /** @var string|int $field */
            foreach ($settings as $field => $config) {
                if (is_int($field)) {
                    continue;
                }

                $registryAlias = $assoc->getTarget()->getRegistryAlias();
                $entityAlias = $assoc->getProperty();
                /** @var \Cake\Datasource\EntityInterface $assocEntity */
                $assocEntity = $entity->$entityAlias;

                if (
                    !is_callable($config) &&
                    isset($config['ignoreDirty']) &&
                    $config['ignoreDirty'] === true &&
                    $assocEntity->isDirty($field)
                ) {
                    $this->_ignoreDirty[$registryAlias][$field] = true;
                }
            }
        }
    }

    /**
     * afterSave callback.
     *
     * Makes sure to update counter cache when a new record is created or updated.
     *
     * @param \Cake\Event\EventInterface<\Cake\ORM\Table> $event The afterSave event that was fired.
     * @param \Cake\Datasource\EntityInterface $entity The entity that was saved.
     * @param \ArrayObject<string, mixed> $options The options for the query
     * @return void
     */
    public function afterSave(EventInterface $event, EntityInterface $entity, ArrayObject $options): void
    {
        if (isset($options['ignoreCounterCache']) && $options['ignoreCounterCache'] === true) {
            return;
        }

        $this->_processAssociations($event, $entity);
        $this->_ignoreDirty = [];
    }

    /**
     * afterDelete callback.
     *
     * Makes sure to update counter cache when a record is deleted.
     *
     * @param \Cake\Event\EventInterface<\Cake\ORM\Table> $event The afterDelete event that was fired.
     * @param \Cake\Datasource\EntityInterface $entity The entity that was deleted.
     * @param \ArrayObject<string, mixed> $options The options for the query
     * @return void
     */
    public function afterDelete(EventInterface $event, EntityInterface $entity, ArrayObject $options): void
    {
        if (isset($options['ignoreCounterCache']) && $options['ignoreCounterCache'] === true) {
            return;
        }

        $this->_processAssociations($event, $entity);
    }

    /**
     * Update counter cache for a batch of records.
     *
     * Counter caches configured to use closures will not be updated by the method.
     *
     * @param string|null $assocName The association name to update counter cache for.
     *  If null, all configured associations will be processed.
     * @param int $limit The number of records to update per page/iteration.
     * @param int|null $page The page/iteration number. If null (default), all
     *   records will be updated one page at a time.
     * @return void
     * @since 5.2.0
     */
    public function updateCounterCache(?string $assocName = null, int $limit = 100, ?int $page = null): void
    {
        $config = $this->_config;
        if ($assocName !== null) {
            $config = [$assocName => $config[$assocName]];
        }

        foreach ($config as $assoc => $settings) {
            /** @var \Cake\ORM\Association\BelongsTo $belongsTo */
            $belongsTo = $this->_table->getAssociation($assoc);

            foreach ($settings as $field => $config) {
                if ($config instanceof Closure) {
                    // Cannot update counter cache which use a closure
                    return;
                }

                if (is_int($field)) {
                    $field = $config;
                    $config = [];
                }

                $this->updateCountForAssociation($belongsTo, $field, $config, $limit, $page);
            }
        }
    }

    /**
     * Update counter cache for the given association.
     *
     * @param \Cake\ORM\Association\BelongsTo $assoc The association object.
     * @param string $field Counter cache field.
     * @param array $config Config array.
     * @param int $limit Limit.
     * @param int|null $page Page number.
     * @return void
     */
    protected function updateCountForAssociation(
        BelongsTo $assoc,
        string $field,
        array $config,
        int $limit = 100,
        ?int $page = null,
    ): void {
        $primaryKeys = (array)$assoc->getBindingKey();
        /** @var array<string> $foreignKeys */
        $foreignKeys = (array)$assoc->getForeignKey();

        $query = $assoc->getTarget()->find()
            ->select($primaryKeys)
            ->limit($limit);

        foreach ($primaryKeys as $key) {
            $query->orderByAsc($key);
        }

        $singlePage = $page !== null;
        $page ??= 1;

        do {
            $results = $query
                ->page($page++)
                ->all();

            /** @var \Cake\Datasource\EntityInterface $entity */
            foreach ($results as $entity) {
                $updateConditions = $entity->extract($primaryKeys);

                foreach ($updateConditions as $f => $value) {
                    if ($value === null) {
                        $updateConditions[$f . ' IS'] = $value;
                        unset($updateConditions[$f]);
                    }
                }

                $countConditions = array_combine($foreignKeys, $updateConditions);

                $count = $this->_getCount($config, $countConditions);
                $assoc->getTarget()->updateAll([$field => $count], $updateConditions);
            }
        } while (!$singlePage && $results->count() === $limit);
    }

    /**
     * Iterate all associations and update counter caches.
     *
     * @param \Cake\Event\EventInterface<\Cake\ORM\Table> $event Event instance.
     * @param \Cake\Datasource\EntityInterface $entity Entity.
     * @return void
     */
    protected function _processAssociations(EventInterface $event, EntityInterface $entity): void
    {
        foreach ($this->_config as $assoc => $settings) {
            $assoc = $this->_table->getAssociation($assoc);
            $this->_processAssociation($event, $entity, $assoc, $settings);
        }
    }

    /**
     * Updates counter cache for a single association
     *
     * @param \Cake\Event\EventInterface<\Cake\ORM\Table> $event Event instance.
     * @param \Cake\Datasource\EntityInterface $entity Entity
     * @param \Cake\ORM\Association $assoc The association object
     * @param array $settings The settings for counter cache for this association
     * @return void
     * @throws \RuntimeException If invalid callable is passed.
     */
    protected function _processAssociation(
        EventInterface $event,
        EntityInterface $entity,
        Association $assoc,
        array $settings,
    ): void {
        /** @var array<string> $foreignKeys */
        $foreignKeys = (array)$assoc->getForeignKey();
        $countConditions = $entity->extract($foreignKeys);

        foreach ($countConditions as $field => $value) {
            if ($value === null) {
                $countConditions[$field . ' IS'] = $value;
                unset($countConditions[$field]);
            }
        }

        $primaryKeys = (array)$assoc->getBindingKey();
        $updateConditions = array_combine($primaryKeys, $countConditions);

        $countOriginalConditions = $entity->extractOriginalChanged($foreignKeys);
        $updateOriginalConditions = null;
        if ($countOriginalConditions !== []) {
            $updateOriginalConditions = array_combine($primaryKeys, $countOriginalConditions);
        }

        foreach ($settings as $field => $config) {
            if (is_int($field)) {
                $field = $config;
                $config = [];
            }

            if (
                isset($this->_ignoreDirty[$assoc->getTarget()->getRegistryAlias()][$field]) &&
                $this->_ignoreDirty[$assoc->getTarget()->getRegistryAlias()][$field] === true
            ) {
                continue;
            }

            if ($this->_shouldUpdateCount($updateConditions)) {
                if ($config instanceof Closure) {
                    $count = $config($event, $entity, $this->_table, false);
                } else {
                    $count = $this->_getCount($config, $countConditions);
                }
                if ($count !== false) {
                    $assoc->getTarget()->updateAll([$field => $count], $updateConditions);
                }
            }

            if ($updateOriginalConditions && $this->_shouldUpdateCount($updateOriginalConditions)) {
                if ($config instanceof Closure) {
                    $count = $config($event, $entity, $this->_table, true);
                } else {
                    $count = $this->_getCount($config, $countOriginalConditions);
                }
                if ($count !== false) {
                    $assoc->getTarget()->updateAll([$field => $count], $updateOriginalConditions);
                }
            }
        }
    }

    /**
     * Checks if the count should be updated given a set of conditions.
     *
     * @param array $conditions Conditions to update count.
     * @return bool True if the count update should happen, false otherwise.
     */
    protected function _shouldUpdateCount(array $conditions): bool
    {
        return !empty(array_filter($conditions, function ($value) {
            return $value !== null;
        }));
    }

    /**
     * Fetches and returns the count for a single field in an association
     *
     * @param array<string, mixed> $config The counter cache configuration for a single field
     * @param array $conditions Additional conditions given to the query
     * @return \Cake\ORM\Query\SelectQuery|int The query to fetch the number of
     *   relations matching the given config and conditions or the number itself.
     */
    protected function _getCount(array $config, array $conditions): SelectQuery|int
    {
        $finder = 'all';
        if (!empty($config['finder'])) {
            $finder = $config['finder'];
            unset($config['finder']);
        }

        $config['conditions'] = array_merge($conditions, $config['conditions'] ?? []);
        $query = $this->_table->find($finder, ...$config);

        if (isset($config['useSubQuery']) && $config['useSubQuery'] === false) {
            return $query->count();
        }

        return $query
            ->select(['count' => $query->func()->count('*')], true)
            ->orderBy([], true);
    }
}
