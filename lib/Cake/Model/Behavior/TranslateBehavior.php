<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       Cake.Model.Behavior
 * @since         CakePHP(tm) v 1.2.0.4525
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

App::uses('I18n', 'I18n');
App::uses('I18nModel', 'Model');

/**
 * Translate behavior
 *
 * @package       Cake.Model.Behavior
 * @link http://book.cakephp.org/2.0/en/core-libraries/behaviors/translate.html
 */
class TranslateBehavior extends ModelBehavior {

/**
 * Used for runtime configuration of model
 *
 * @var array
 */
	public $runtime = array();

/**
 * Stores the joinTable object for generating joins.
 *
 * @var object
 */
	protected $_joinTable;

/**
 * Stores the runtime model for generating joins.
 *
 * @var Model
 */
	protected $_runtimeModel;

/**
 * Callback
 *
 * $config for TranslateBehavior should be
 * array('fields' => array('field_one',
 * 'field_two' => 'FieldAssoc', 'field_three'))
 *
 * With above example only one permanent hasMany will be joined (for field_two
 * as FieldAssoc)
 *
 * $config could be empty - and translations configured dynamically by
 * bindTranslation() method
 *
 * @param Model $model Model the behavior is being attached to.
 * @param array $config Array of configuration information.
 * @return mixed
 */
	public function setup(Model $model, $config = array()) {
		$db = ConnectionManager::getDataSource($model->useDbConfig);
		if (!$db->connected) {
			trigger_error(
				__d('cake_dev', 'Datasource %s for TranslateBehavior of model %s is not connected', $model->useDbConfig, $model->alias),
				E_USER_ERROR
			);
			return false;
		}

		$this->settings[$model->alias] = array();
		$this->runtime[$model->alias] = array('fields' => array());
		$this->translateModel($model);
		return $this->bindTranslation($model, $config, false);
	}

/**
 * Cleanup Callback unbinds bound translations and deletes setting information.
 *
 * @param Model $model Model being detached.
 * @return void
 */
	public function cleanup(Model $model) {
		$this->unbindTranslation($model);
		unset($this->settings[$model->alias]);
		unset($this->runtime[$model->alias]);
	}

/**
 * beforeFind Callback
 *
 * @param Model $model Model find is being run on.
 * @param array $query Array of Query parameters.
 * @return array Modified query
 */
	public function beforeFind(Model $model, $query) {
		$this->runtime[$model->alias]['virtualFields'] = $model->virtualFields;
		$locale = $this->_getLocale($model);
		if (empty($locale)) {
			return $query;
		}
		$db = $model->getDataSource();
		$RuntimeModel = $this->translateModel($model);

		if (!empty($RuntimeModel->tablePrefix)) {
			$tablePrefix = $RuntimeModel->tablePrefix;
		} else {
			$tablePrefix = $db->config['prefix'];
		}
		$joinTable = new StdClass();
		$joinTable->tablePrefix = $tablePrefix;
		$joinTable->table = $RuntimeModel->table;
		$joinTable->schemaName = $RuntimeModel->getDataSource()->getSchemaName();

		$this->_joinTable = $joinTable;
		$this->_runtimeModel = $RuntimeModel;

		if (is_string($query['fields']) && 'COUNT(*) AS ' . $db->name('count') == $query['fields']) {
			$query['fields'] = 'COUNT(DISTINCT(' . $db->name($model->alias . '.' . $model->primaryKey) . ')) ' . $db->alias . 'count';
			$query['joins'][] = array(
				'type' => 'INNER',
				'alias' => $RuntimeModel->alias,
				'table' => $joinTable,
				'conditions' => array(
					$model->alias . '.' . $model->primaryKey => $db->identifier($RuntimeModel->alias . '.foreign_key'),
					$RuntimeModel->alias . '.model' => $model->name,
					$RuntimeModel->alias . '.locale' => $locale
				)
			);
			$conditionFields = $this->_checkConditions($model, $query);
			foreach ($conditionFields as $field) {
				$query = $this->_addJoin($model, $query, $field, $field, $locale);
			}
			unset($this->_joinTable, $this->_runtimeModel);
			return $query;
		}

		$fields = array_merge($this->settings[$model->alias], $this->runtime[$model->alias]['fields']);
		$addFields = array();
		if (empty($query['fields'])) {
			$addFields = $fields;
		} elseif (is_array($query['fields'])) {
			foreach ($fields as $key => $value) {
				$field = (is_numeric($key)) ? $value : $key;

				if (in_array($model->alias . '.*', $query['fields']) || in_array($model->alias . '.' . $field, $query['fields']) || in_array($field, $query['fields'])) {
					$addFields[] = $field;
				}
			}
		}

		$this->runtime[$model->alias]['virtualFields'] = $model->virtualFields;
		if ($addFields) {
			foreach ($addFields as $_f => $field) {
				$aliasField = is_numeric($_f) ? $field : $_f;

				foreach (array($aliasField, $model->alias . '.' . $aliasField) as $_field) {
					$key = array_search($_field, (array)$query['fields']);

					if ($key !== false) {
						unset($query['fields'][$key]);
					}
				}
				$query = $this->_addJoin($model, $query, $field, $aliasField, $locale);
			}
		}
		$this->runtime[$model->alias]['beforeFind'] = $addFields;
		unset($this->_joinTable, $this->_runtimeModel);
		return $query;
	}

/**
 * Check a query's conditions for translated fields.
 * Return an array of translated fields found in the conditions.
 *
 * @param Model $model The model being read.
 * @param array $query The query array.
 * @return array The list of translated fields that are in the conditions.
 */
	protected function _checkConditions(Model $model, $query) {
		$conditionFields = array();
		if (empty($query['conditions']) || (!empty($query['conditions']) && !is_array($query['conditions'])) ) {
			return $conditionFields;
		}
		foreach ($query['conditions'] as $col => $val) {
			foreach ($this->settings[$model->alias] as $field => $assoc) {
				if (is_numeric($field)) {
					$field = $assoc;
				}
				if (strpos($col, $field) !== false) {
					$conditionFields[] = $field;
				}
			}
		}
		return $conditionFields;
	}

/**
 * Appends a join for translated fields and possibly a field.
 *
 * @param Model $model The model being worked on.
 * @param object $joinTable The jointable object.
 * @param array $query The query array to append a join to.
 * @param string $field The field name being joined.
 * @param string $aliasField The aliased field name being joined.
 * @param string|array $locale The locale(s) having joins added.
 * @param boolean $addField Whether or not to add a field.
 * @return array The modfied query
 */
	protected function _addJoin(Model $model, $query, $field, $aliasField, $locale, $addField = false) {
		$db = ConnectionManager::getDataSource($model->useDbConfig);

		$RuntimeModel = $this->_runtimeModel;
		$joinTable = $this->_joinTable;

		if (is_array($locale)) {
			foreach ($locale as $_locale) {
				$model->virtualFields['i18n_' . $field . '_' . $_locale] = 'I18n__' . $field . '__' . $_locale . '.content';
				if (!empty($query['fields']) && is_array($query['fields'])) {
					$query['fields'][] = 'i18n_' . $field . '_' . $_locale;
				}
				$query['joins'][] = array(
					'type' => 'LEFT',
					'alias' => 'I18n__' . $field . '__' . $_locale,
					'table' => $joinTable,
					'conditions' => array(
						$model->alias . '.' . $model->primaryKey => $db->identifier("I18n__{$field}__{$_locale}.foreign_key"),
						'I18n__' . $field . '__' . $_locale . '.model' => $model->name,
						'I18n__' . $field . '__' . $_locale . '.' . $RuntimeModel->displayField => $aliasField,
						'I18n__' . $field . '__' . $_locale . '.locale' => $_locale
					)
				);
			}
		} else {
			$model->virtualFields['i18n_' . $field] = 'I18n__' . $field . '.content';
			if (!empty($query['fields']) && is_array($query['fields'])) {
				$query['fields'][] = 'i18n_' . $field;
			}
			$query['joins'][] = array(
				'type' => 'INNER',
				'alias' => 'I18n__' . $field,
				'table' => $joinTable,
				'conditions' => array(
					$model->alias . '.' . $model->primaryKey => $db->identifier("I18n__{$field}.foreign_key"),
					'I18n__' . $field . '.model' => $model->name,
					'I18n__' . $field . '.' . $RuntimeModel->displayField => $aliasField,
					'I18n__' . $field . '.locale' => $locale
				)
			);
		}
		return $query;
	}

/**
 * afterFind Callback
 *
 * @param Model $model Model find was run on
 * @param array $results Array of model results.
 * @param boolean $primary Did the find originate on $model.
 * @return array Modified results
 */
	public function afterFind(Model $model, $results, $primary) {
		$model->virtualFields = $this->runtime[$model->alias]['virtualFields'];
		$this->runtime[$model->alias]['virtualFields'] = $this->runtime[$model->alias]['fields'] = array();
		$locale = $this->_getLocale($model);

		if (empty($locale) || empty($results) || empty($this->runtime[$model->alias]['beforeFind'])) {
			return $results;
		}
		$beforeFind = $this->runtime[$model->alias]['beforeFind'];

		foreach ($results as $key => &$row) {
			$results[$key][$model->alias]['locale'] = (is_array($locale)) ? current($locale) : $locale;
			foreach ($beforeFind as $_f => $field) {
				$aliasField = is_numeric($_f) ? $field : $_f;

				if (is_array($locale)) {
					foreach ($locale as $_locale) {
						if (!isset($row[$model->alias][$aliasField]) && !empty($row[$model->alias]['i18n_' . $field . '_' . $_locale])) {
							$row[$model->alias][$aliasField] = $row[$model->alias]['i18n_' . $field . '_' . $_locale];
							$row[$model->alias]['locale'] = $_locale;
						}
						unset($row[$model->alias]['i18n_' . $field . '_' . $_locale]);
					}

					if (!isset($row[$model->alias][$aliasField])) {
						$row[$model->alias][$aliasField] = '';
					}
				} else {
					$value = '';
					if (!empty($row[$model->alias]['i18n_' . $field])) {
						$value = $row[$model->alias]['i18n_' . $field];
					}
					$row[$model->alias][$aliasField] = $value;
					unset($row[$model->alias]['i18n_' . $field]);
				}
			}
		}
		return $results;
	}

/**
 * beforeValidate Callback
 *
 * @param Model $model Model invalidFields was called on.
 * @return boolean
 */
	public function beforeValidate(Model $model) {
		unset($this->runtime[$model->alias]['beforeSave']);
		$this->_setRuntimeData($model);
		return true;
	}

/**
 * beforeSave callback.
 *
 * Copies data into the runtime property when `$options['validate']` is
 * disabled.  Or the runtime data hasn't been set yet.
 *
 * @param Model $model Model save was called on.
 * @return boolean true.
 */
	public function beforeSave(Model $model, $options = array()) {
		if (isset($options['validate']) && $options['validate'] == false) {
			unset($this->runtime[$model->alias]['beforeSave']);
		}
		if (isset($this->runtime[$model->alias]['beforeSave'])) {
			return true;
		}
		$this->_setRuntimeData($model);
		return true;
	}

/**
 * Sets the runtime data.
 *
 * Used from beforeValidate() and beforeSave() for compatibility issues,
 * and to allow translations to be persisted even when validation
 * is disabled.
 *
 * @param Model $model
 * @return void
 */
	protected function _setRuntimeData(Model $model) {
		$locale = $this->_getLocale($model);
		if (empty($locale)) {
			return true;
		}
		$fields = array_merge($this->settings[$model->alias], $this->runtime[$model->alias]['fields']);
		$tempData = array();

		foreach ($fields as $key => $value) {
			$field = (is_numeric($key)) ? $value : $key;

			if (isset($model->data[$model->alias][$field])) {
				$tempData[$field] = $model->data[$model->alias][$field];
				if (is_array($model->data[$model->alias][$field])) {
					if (is_string($locale) && !empty($model->data[$model->alias][$field][$locale])) {
						$model->data[$model->alias][$field] = $model->data[$model->alias][$field][$locale];
					} else {
						$values = array_values($model->data[$model->alias][$field]);
						$model->data[$model->alias][$field] = $values[0];
					}
				}
			}
		}
		$this->runtime[$model->alias]['beforeSave'] = $tempData;
	}

/**
 * afterSave Callback
 *
 * @param Model $model Model the callback is called on
 * @param boolean $created Whether or not the save created a record.
 * @return void
 */
	public function afterSave(Model $model, $created) {
		if (!isset($this->runtime[$model->alias]['beforeValidate']) && !isset($this->runtime[$model->alias]['beforeSave'])) {
			return true;
		}
		$locale = $this->_getLocale($model);
		if (isset($this->runtime[$model->alias]['beforeValidate'])) {
			$tempData = $this->runtime[$model->alias]['beforeValidate'];
		} else {
			$tempData = $this->runtime[$model->alias]['beforeSave'];
		}

		unset($this->runtime[$model->alias]['beforeValidate'], $this->runtime[$model->alias]['beforeSave']);
		$conditions = array('model' => $model->alias, 'foreign_key' => $model->id);
		$RuntimeModel = $this->translateModel($model);

		$fields = array_merge($this->settings[$model->alias], $this->runtime[$model->alias]['fields']);
		if ($created) {
			foreach ($fields as $field) {
				if (!isset($tempData[$field])) {
					//set the field value to an empty string
					$tempData[$field] = '';
				}
			}
		}

		foreach ($tempData as $field => $value) {
			unset($conditions['content']);
			$conditions['field'] = $field;
			if (is_array($value)) {
				$conditions['locale'] = array_keys($value);
			} else {
				$conditions['locale'] = $locale;
				if (is_array($locale)) {
					$value = array($locale[0] => $value);
				} else {
					$value = array($locale => $value);
				}
			}
			$translations = $RuntimeModel->find('list', array('conditions' => $conditions, 'fields' => array($RuntimeModel->alias . '.locale', $RuntimeModel->alias . '.id')));
			foreach ($value as $_locale => $_value) {
				$RuntimeModel->create();
				$conditions['locale'] = $_locale;
				$conditions['content'] = $_value;
				if (array_key_exists($_locale, $translations)) {
					$RuntimeModel->save(array($RuntimeModel->alias => array_merge($conditions, array('id' => $translations[$_locale]))));
				} else {
					$RuntimeModel->save(array($RuntimeModel->alias => $conditions));
				}
			}
		}
	}

/**
 * afterDelete Callback
 *
 * @param Model $model Model the callback was run on.
 * @return void
 */
	public function afterDelete(Model $model) {
		$RuntimeModel = $this->translateModel($model);
		$conditions = array('model' => $model->alias, 'foreign_key' => $model->id);
		$RuntimeModel->deleteAll($conditions);
	}

/**
 * Get selected locale for model
 *
 * @param Model $model Model the locale needs to be set/get on.
 * @return mixed string or false
 */
	protected function _getLocale(Model $model) {
		if (!isset($model->locale) || is_null($model->locale)) {
			$I18n = I18n::getInstance();
			$I18n->l10n->get(Configure::read('Config.language'));
			$model->locale = $I18n->l10n->locale;
		}

		return $model->locale;
	}

/**
 * Get instance of model for translations.
 *
 * If the model has a translateModel property set, this will be used as the class
 * name to find/use.  If no translateModel property is found 'I18nModel' will be used.
 *
 * @param Model $model Model to get a translatemodel for.
 * @return Model
 */
	public function translateModel(Model $model) {
		if (!isset($this->runtime[$model->alias]['model'])) {
			if (!isset($model->translateModel) || empty($model->translateModel)) {
				$className = 'I18nModel';
			} else {
				$className = $model->translateModel;
			}

			$this->runtime[$model->alias]['model'] = ClassRegistry::init($className, 'Model');
		}
		if (!empty($model->translateTable) && $model->translateTable !== $this->runtime[$model->alias]['model']->useTable) {
			$this->runtime[$model->alias]['model']->setSource($model->translateTable);
		} elseif (empty($model->translateTable) && empty($model->translateModel)) {
			$this->runtime[$model->alias]['model']->setSource('i18n');
		}
		return $this->runtime[$model->alias]['model'];
	}

/**
 * Bind translation for fields, optionally with hasMany association for
 * fake field.
 *
 * *Note* You should avoid binding translations that overlap existing model properties.
 * This can cause un-expected and un-desirable behavior.
 *
 * @param Model $model instance of model
 * @param string|array $fields string with field or array(field1, field2=>AssocName, field3)
 * @param boolean $reset Leave true to have the fields only modified for the next operation.
 *   if false the field will be added for all future queries.
 * @return boolean
 * @throws CakeException when attempting to bind a translating called name.  This is not allowed
 *   as it shadows Model::$name.
 */
	public function bindTranslation(Model $model, $fields, $reset = true) {
		if (is_string($fields)) {
			$fields = array($fields);
		}
		$associations = array();
		$RuntimeModel = $this->translateModel($model);
		$default = array('className' => $RuntimeModel->alias, 'foreignKey' => 'foreign_key');

		foreach ($fields as $key => $value) {
			if (is_numeric($key)) {
				$field = $value;
				$association = null;
			} else {
				$field = $key;
				$association = $value;
			}
			if ($association === 'name') {
				throw new CakeException(
					__d('cake_dev', 'You cannot bind a translation named "name".')
				);
			}

			$this->_removeField($model, $field);

			if (is_null($association)) {
				if ($reset) {
					$this->runtime[$model->alias]['fields'][] = $field;
				} else {
					$this->settings[$model->alias][] = $field;
				}
			} else {
				if ($reset) {
					$this->runtime[$model->alias]['fields'][$field] = $association;
				} else {
					$this->settings[$model->alias][$field] = $association;
				}

				foreach (array('hasOne', 'hasMany', 'belongsTo', 'hasAndBelongsToMany') as $type) {
					if (isset($model->{$type}[$association]) || isset($model->__backAssociation[$type][$association])) {
						trigger_error(
							__d('cake_dev', 'Association %s is already bound to model %s', $association, $model->alias),
							E_USER_ERROR
						);
						return false;
					}
				}
				$associations[$association] = array_merge($default, array('conditions' => array(
					'model' => $model->alias,
					$RuntimeModel->displayField => $field
				)));
			}
		}

		if (!empty($associations)) {
			$model->bindModel(array('hasMany' => $associations), $reset);
		}
		return true;
	}

/**
 * Update runtime setting for a given field.
 *
 * @param string $field The field to update.
 */
	protected function _removeField(Model $model, $field) {
		if (array_key_exists($field, $this->settings[$model->alias])) {
			unset($this->settings[$model->alias][$field]);
		} elseif (in_array($field, $this->settings[$model->alias])) {
			$this->settings[$model->alias] = array_merge(array_diff($this->settings[$model->alias], array($field)));
		}

		if (array_key_exists($field, $this->runtime[$model->alias]['fields'])) {
			unset($this->runtime[$model->alias]['fields'][$field]);
		} elseif (in_array($field, $this->runtime[$model->alias]['fields'])) {
			$this->runtime[$model->alias]['fields'] = array_merge(array_diff($this->runtime[$model->alias]['fields'], array($field)));
		}
	}

/**
 * Unbind translation for fields, optionally unbinds hasMany association for
 * fake field
 *
 * @param Model $model instance of model
 * @param string|array $fields string with field, or array(field1, field2=>AssocName, field3), or null for
 *    unbind all original translations
 * @return boolean
 */
	public function unbindTranslation(Model $model, $fields = null) {
		if (empty($fields) && empty($this->settings[$model->alias])) {
			return false;
		}
		if (empty($fields)) {
			return $this->unbindTranslation($model, $this->settings[$model->alias]);
		}

		if (is_string($fields)) {
			$fields = array($fields);
		}
		$RuntimeModel = $this->translateModel($model);
		$associations = array();

		foreach ($fields as $key => $value) {
			if (is_numeric($key)) {
				$field = $value;
				$association = null;
			} else {
				$field = $key;
				$association = $value;
			}

			$this->_removeField($model, $field);

			if (!is_null($association) && (isset($model->hasMany[$association]) || isset($model->__backAssociation['hasMany'][$association]))) {
				$associations[] = $association;
			}
		}

		if (!empty($associations)) {
			$model->unbindModel(array('hasMany' => $associations), false);
		}
		return true;
	}

}

