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
namespace Cake\Model\Behavior;

use Cake\Event\Event;
use Cake\ORM\Association;
use Cake\ORM\Behavior;
use Cake\ORM\Entity;
use Cake\ORM\Table;
use Cake\Utility\Inflector;

/**
 * CounterCache behavior
 *
 * Enables models to cache the amount of connections in a given relation.
 *
 * Examples with Post model belonging to User model
 *
 * Regular counter cache
 * {{{
 * [
 *     'User' => [
 *         'post_count'
 *     ]
 * ]
 * }}}
 *
 * Counter cache with scope
 * {{{
 * [
 *     'User' => [
 *         'posts_published' => [
 *             'conditions' => [
 *                 'published' => true
 *             ]
 *         ]
 *     ]
 * ]
 * }}}
 *
 * Counter cache using custom find
 * {{{
 * [
 *     'User' => [
 *         'posts_published' => [
 *             'findType' => 'published' // Will be using findPublished()
 *         ]
 *     ]
 * ]
 * }}}
 *
 * Counter cache using lambda function returning the count
 * This is equivalent to example #2
 * {{{
 * [
 *     'User' => [
 *         'posts_published' => function (Event $event, Entity $entity, Table $table) {
 *             $query = $table->find('all')->where([
 *                 'published' => true,
 *                 'user_id' => $entity->get('user_id')
 *             ]);
 *             return $query->count();
 *          }
 *     ]
 * ]
 * }}}
 *
 */
class CounterCacheBehavior extends Behavior {

/**
 * Keeping a reference to the table in order to,
 * be able to retrieve associations and fetch records for counting.
 *
 * @var \Cake\ORM\Table
 */
	protected $_table;

/**
 * Constructor
 *
 * @param Table $table The table this behavior is attached to.
 * @param array $config The config for this behavior.
 */
	public function __construct(Table $table, array $config = []) {
		parent::__construct($table, $config);
		$this->_table = $table;
	}

/**
 * afterSave callback.
 *
 * Makes sure to update counter cache when a new record is created or updated.
 *
 * @param Event $event
 * @param Entity $entity
 * @return void
 */
	public function afterSave(Event $event, Entity $entity) {
		$this->_processAssociations($event, $entity);
	}

/**
 * afterDelete callback.
 *
 * Makes sure to update counter cache when a record is deleted.
 *
 * @param Event $event
 * @param Entity $entity
 * @return void
 */
	public function afterDelete(Event $event, Entity $entity) {
		$this->_processAssociations($event, $entity);
	}

/**
 * Iterate all associations and update counter caches.
 *
 * @param Event $event
 * @param Entity $entity
 * @return void
 */
	protected function _processAssociations(Event $event, Entity $entity) {
		foreach ($this->_config as $assoc => $settings) {
			$assoc = $this->_table->association($assoc);
			$this->_processAssociation($event, $entity, $assoc, $settings);
		}
	}

/**
 * Updates counter cache for a single association
 *
 * @param Event $event
 * @param Entity $entity
 * @param Association $assoc The association object
 * @param array $settings The settings for for counter cache for this association
 * @return void
 */
	protected function _processAssociation(Event $event, Entity $entity, Association $assoc, array $settings) {
		$foreignKeys = (array)$assoc->foreignKey();
		$primaryKeys = (array)$assoc->target()->primaryKey();
		$countConditions = $entity->extract($foreignKeys);
		$updateConditions = array_combine($primaryKeys, $countConditions);

		foreach ($settings as $field => $config) {
			if (is_int($field)) {
				$field = $config;
				$config = [];
			}

			if (is_callable($config)) {
				$count = $config($event, $entity, $this->_table);
			} else {
				$count = $this->_getCount($config, $countConditions);
			}

			$assoc->target()->updateAll([$field => $count], $updateConditions);
		}
	}

/**
 * Fetches and returns the count for a single field in an association
 *
 * @param array $config The counter cache configuration for a single field
 * @param array $conditions Additional conditions given to the query
 * @return integer The number of relations matching the given config and conditions
 */
	protected function _getCount(array $config, array $conditions) {
		$findType = 'all';
		if (!empty($config['findType'])) {
			$findType = $config['findType'];
			unset($config['findType']);
		}

		if (!isset($config['conditions'])) {
			$config['conditions'] = [];
		}
		$config['conditions'] = array_merge($conditions, $config['conditions']);
		$query = $this->_table->find($findType, $config);

		return $query->count();
	}
}
