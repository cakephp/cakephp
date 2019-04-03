<?php
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

use Cake\Datasource\EntityInterface;
use Cake\Event\Event;
use Cake\ORM\Association;
use Cake\ORM\Behavior;

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
 * ```
 * [
 *     'Users' => [
 *         'posts_published' => function (Event $event, EntityInterface $entity, Table $table) {
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
     * @var array
     */
    protected $_ignoreDirty = [];

    /**
     * beforeSave callback.
     *
     * Check if a field, which should be ignored, is dirty
     *
     * @param \Cake\Event\Event $event The beforeSave event that was fired
     * @param \Cake\Datasource\EntityInterface $entity The entity that is going to be saved
     * @param \ArrayObject $options The options for the query
     * @return void
     */
    public function beforeSave(Event $event, EntityInterface $entity, $options)
    {
        if (isset($options['ignoreCounterCache']) && $options['ignoreCounterCache'] === true) {
            return;
        }

        foreach ($this->_config as $assoc => $settings) {
            $assoc = $this->_table->association($assoc);
            foreach ($settings as $field => $config) {
                if (is_int($field)) {
                    continue;
                }

                $registryAlias = $assoc->getTarget()->getRegistryAlias();
                $entityAlias = $assoc->getProperty();

                if (!is_callable($config) &&
                    isset($config['ignoreDirty']) &&
                    $config['ignoreDirty'] === true &&
                    $entity->$entityAlias->isDirty($field)
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
     * @param \Cake\Event\Event $event The afterSave event that was fired.
     * @param \Cake\Datasource\EntityInterface $entity The entity that was saved.
     * @param \ArrayObject $options The options for the query
     * @return void
     */
    public function afterSave(Event $event, EntityInterface $entity, $options)
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
     * @param \Cake\Event\Event $event The afterDelete event that was fired.
     * @param \Cake\Datasource\EntityInterface $entity The entity that was deleted.
     * @param \ArrayObject $options The options for the query
     * @return void
     */
    public function afterDelete(Event $event, EntityInterface $entity, $options)
    {
        if (isset($options['ignoreCounterCache']) && $options['ignoreCounterCache'] === true) {
            return;
        }

        $this->_processAssociations($event, $entity);
    }

    /**
     * Iterate all associations and update counter caches.
     *
     * @param \Cake\Event\Event $event Event instance.
     * @param \Cake\Datasource\EntityInterface $entity Entity.
     * @return void
     */
    protected function _processAssociations(Event $event, EntityInterface $entity)
    {
        foreach ($this->_config as $assoc => $settings) {
            $assoc = $this->_table->association($assoc);
            $this->_processAssociation($event, $entity, $assoc, $settings);
        }
    }

    /**
     * Updates counter cache for a single association
     *
     * @param \Cake\Event\Event $event Event instance.
     * @param \Cake\Datasource\EntityInterface $entity Entity
     * @param \Cake\ORM\Association $assoc The association object
     * @param array $settings The settings for for counter cache for this association
     * @return void
     */
    protected function _processAssociation(Event $event, EntityInterface $entity, Association $assoc, array $settings)
    {
        $foreignKeys = (array)$assoc->getForeignKey();
        $primaryKeys = (array)$assoc->getBindingKey();
        $countConditions = $entity->extract($foreignKeys);
        $updateConditions = array_combine($primaryKeys, $countConditions);
        $countOriginalConditions = $entity->extractOriginalChanged($foreignKeys);

        if ($countOriginalConditions !== []) {
            $updateOriginalConditions = array_combine($primaryKeys, $countOriginalConditions);
        }

        foreach ($settings as $field => $config) {
            if (is_int($field)) {
                $field = $config;
                $config = [];
            }

            if (isset($this->_ignoreDirty[$assoc->getTarget()->getRegistryAlias()][$field]) &&
                $this->_ignoreDirty[$assoc->getTarget()->getRegistryAlias()][$field] === true
            ) {
                continue;
            }

            if (!is_string($config) && is_callable($config)) {
                $count = $config($event, $entity, $this->_table, false);
            } else {
                $count = $this->_getCount($config, $countConditions);
            }

            $assoc->getTarget()->updateAll([$field => $count], $updateConditions);

            if (isset($updateOriginalConditions)) {
                if (!is_string($config) && is_callable($config)) {
                    $count = $config($event, $entity, $this->_table, true);
                } else {
                    $count = $this->_getCount($config, $countOriginalConditions);
                }
                $assoc->getTarget()->updateAll([$field => $count], $updateOriginalConditions);
            }
        }
    }

    /**
     * Fetches and returns the count for a single field in an association
     *
     * @param array $config The counter cache configuration for a single field
     * @param array $conditions Additional conditions given to the query
     * @return int The number of relations matching the given config and conditions
     */
    protected function _getCount(array $config, array $conditions)
    {
        $finder = 'all';
        if (!empty($config['finder'])) {
            $finder = $config['finder'];
            unset($config['finder']);
        }

        if (!isset($config['conditions'])) {
            $config['conditions'] = [];
        }
        $config['conditions'] = array_merge($conditions, $config['conditions']);
        $query = $this->_table->find($finder, $config);

        return $query->count();
    }
}
