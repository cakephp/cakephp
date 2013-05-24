<?php
/**
 * Behavior for counterCache.
 *
 * Behavior to handle updating counterCache fields from models associations
 *
 * PHP 5
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
 * @package       Cake.Model.Behavior
 * @since         CakePHP(tm) v 2.4.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
App::uses('ModelBehavior', 'Model');

/**
 * Behavior to update countercache fields of belongsTo associations.
 * Add the following keys to belongsTo associations to setup counter caches
 *
 * - `counterCache`: If set to true the associated Model will automatically increase or
 *   decrease the "[singular_model_name]_count" field in the foreign table whenever you do
 *   a save() or delete(). If its a string then its the field name to use. The value in the
 *   counter field represents the number of related rows.
 * - `counterScope`: Optional conditions array to use for updating counter cache field.
 *
 * @package       Cake.Model.Behavior
 * @link http://book.cakephp.org/2.0/en/core-libraries/behaviors/countercache.html
 */
class CounterCacheBehavior extends ModelBehavior {

/**
 * Holds the keys to delete in afterDelete
 *
 * @var array
 */
	protected $_keys = array();

/**
 * Holds prepared update fields
 *
 * @var array
 */
	protected $_cache = array();

/**
 * beforeSave callback
 *
 * CounterCache updates can be avoided by setting $options['counterCache'] = false for save() methods
 *
 * @param Model $Model
 * @param array $options
 * @return boolean
 */
	public function beforeSave(Model $Model, $options = array()) {
		if ((!isset($options['counterCache']) || $options['counterCache']) && $this->hasCounterCache($Model)) {
			list($fields, $values) = $Model->getUpdateFields();
			$this->_cache[$Model->alias] = $this->_prepareUpdateFields($Model, array_combine($fields, $values));
		}
		return true;
	}

/**
 * afterSave callback
 *
 * @param Model $Model
 * @param boolean $created
 */
	public function afterSave(Model $Model, $created) {
		if (isset($this->_cache[$Model->alias]) && $this->hasCounterCache($Model)) {
			$this->updateCounterCache($Model, $this->_cache[$Model->alias], $created);
		}
	}

/**
 * beforeDelete callback
 *
 * @param Model $Model
 * @param boolean $cascade
 * @return boolean
 */
	public function beforeDelete(Model $Model, $cascade = true) {
		if ($this->hasCounterCache($Model)) {
			$this->_keys = $Model->find('first', array(
				'fields' => $this->counterCacheKeys($Model),
				'conditions' => array($Model->alias . '.' . $Model->primaryKey => $Model->id),
				'recursive' => -1,
				'callbacks' => false
			));
		}
		return true;
	}

/**
 *
 * @param Model $Model
 */
	public function afterDelete(Model $Model) {
		if (!empty($this->_keys[$Model->alias]) && $this->hasCounterCache($Model)) {
			$this->updateCounterCache($Model, $this->_keys[$Model->alias]);
		}
	}

/**
 * Checks if any counterCaches have been defined in belongsTo associations
 *
 * @param Model $Model
 * @return boolean
 */
	public function hasCounterCache(Model $Model) {
		return (boolean)$this->counterCacheKeys($Model);
	}

/**
 * Get all foreignKeys from belongsTo association with counterCache defined
 *
 * @param Model $Model
 * @return array Returns array of foreignKeys from belongsTo associations with counterCache
 */
	public function counterCacheKeys(Model $Model) {
		$foreignKeys = array();
		foreach ($Model->belongsTo as $assoc => $info) {
			if (!empty($info['counterCache'])) {
				$foreignKeys[$assoc] = $info['foreignKey'];
			}
		}
		return $foreignKeys;
	}

/**
 * Helper method for `updateCounterCache()`. Checks the fields to be updated for
 *
 * @param Model $Model
 * @param array $data The fields of the record that will be updated
 * @return array Returns updated foreign key values, along with an 'old' key containing the old
 *     values, or empty if no foreign keys are updated.
 */
	protected function _prepareUpdateFields($Model, $data) {
		$foreignKeys = $this->counterCacheKeys($Model);
		$included = array_intersect($foreignKeys, array_keys($data));

		if (empty($included) || !$Model->exists()) {
			return array();
		}
		$old = $Model->find('first', array(
			'conditions' => array($Model->alias . '.' . $Model->primaryKey => $Model->id),
			'fields' => array_values($included),
			'recursive' => -1
		));
		return array_merge($data, array('old' => $old[$Model->alias]));
	}

/**
 * Updates the counter cache of belongsTo associations after a save or delete operation
 *
 * @param Model $Model
 * @param array $keys Optional foreign key data, defaults to the information $Model->data
 * @param boolean $created True if a new record was created, otherwise only associations with
 *   'counterScope' defined get updated
 * @return void
 */
	public function updateCounterCache(Model $Model, $keys = array(), $created = false) {
		$keys = empty($keys) ? $Model->data[$Model->alias] : $keys;
		$keys['old'] = isset($keys['old']) ? $keys['old'] : array();

		foreach ($Model->belongsTo as $parent => $assoc) {
			if (empty($assoc['counterCache'])) {
				return false;
			}
			if (!is_array($assoc['counterCache'])) {
				if (isset($assoc['counterScope'])) {
					$assoc['counterCache'] = array($assoc['counterCache'] => $assoc['counterScope']);
				} else {
					$assoc['counterCache'] = array($assoc['counterCache'] => array());
				}
			}

			$foreignKey = $assoc['foreignKey'];
			$fkQuoted = $Model->escapeField($assoc['foreignKey']);

			foreach ($assoc['counterCache'] as $field => $conditions) {
				if (!is_string($field)) {
					$field = Inflector::underscore($Model->alias) . '_count';
				}
				if (!$Model->{$parent}->hasField($field)) {
					continue;
				}
				if ($conditions === true) {
					$conditions = array();
				} else {
					$conditions = (array)$conditions;
				}

				if (!array_key_exists($foreignKey, $keys)) {
					$keys[$foreignKey] = $Model->field($foreignKey);
				}
				$recursive = (empty($conditions) ? -1 : 0);

				if (isset($keys['old'][$foreignKey])) {
					if ($keys['old'][$foreignKey] != $keys[$foreignKey]) {
						$conditions[$fkQuoted] = $keys['old'][$foreignKey];
						$count = intval($Model->find('count', compact('conditions', 'recursive')));

						$Model->{$parent}->updateAll(
							array($field => $count),
							array($Model->{$parent}->escapeField() => $keys['old'][$foreignKey])
						);
					}
				}
				$conditions[$fkQuoted] = $keys[$foreignKey];

				if ($recursive === 0) {
					$conditions = array_merge($conditions, (array)$conditions);
				}
				$count = intval($Model->find('count', compact('conditions', 'recursive')));

				$Model->{$parent}->updateAll(
					array($field => $count),
					array($Model->{$parent}->escapeField() => $keys[$foreignKey])
				);
			}
		}
	}

}
