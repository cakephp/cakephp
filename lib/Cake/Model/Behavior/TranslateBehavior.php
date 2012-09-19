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

App::uses('ModelBehavior', 'Model');
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
 * @param Model $Model Model the behavior is being attached to.
 * @param array $config Array of configuration information.
 * @return mixed
 */
	public function setup(Model $Model, $config = array()) {
		$db = ConnectionManager::getDataSource($Model->useDbConfig);
		if (!$db->connected) {
			trigger_error(
				__d('cake_dev', 'Datasource %s for TranslateBehavior of model %s is not connected', $Model->useDbConfig, $Model->alias),
				E_USER_ERROR
			);
			return false;
		}

		$this->settings[$Model->alias] = array();
		$this->runtime[$Model->alias] = array('fields' => array());
		$this->translateModel($Model);
		return $this->bindTranslation($Model, $config, false);
	}

/**
 * Cleanup Callback unbinds bound translations and deletes setting information.
 *
 * @param Model $Model Model being detached.
 * @return void
 */
	public function cleanup(Model $Model) {
		$this->unbindTranslation($Model);
		unset($this->settings[$Model->alias]);
		unset($this->runtime[$Model->alias]);
	}

/**
 * beforeFind Callback
 *
 * @param Model $Model Model find is being run on.
 * @param array $query Array of Query parameters.
 * @return array Modified query
 */
	public function beforeFind(Model $Model, $query) {
		$this->runtime[$Model->alias]['virtualFields'] = $Model->virtualFields;
		$locale = $this->_getLocale($Model);
		if (empty($locale)) {
			return $query;
		}
		$db = $Model->getDataSource();
		$RuntimeModel = $this->translateModel($Model);

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

		if (is_string($query['fields']) && "COUNT(*) AS {$db->name('count')}" == $query['fields']) {
			$query['fields'] = "COUNT(DISTINCT({$db->name($Model->escapeField())})) {$db->alias}count";
			$query['joins'][] = array(
				'type' => 'INNER',
				'alias' => $RuntimeModel->alias,
				'table' => $joinTable,
				'conditions' => array(
					$Model->escapeField() => $db->identifier($RuntimeModel->escapeField('foreign_key')),
					$RuntimeModel->escapeField('model') => $Model->name,
					$RuntimeModel->escapeField('locale')  => $locale
				)
			);
			$conditionFields = $this->_checkConditions($Model, $query);
			foreach ($conditionFields as $field) {
				$query = $this->_addJoin($Model, $query, $field, $field, $locale);
			}
			unset($this->_joinTable, $this->_runtimeModel);
			return $query;
		}

		$fields = array_merge($this->settings[$Model->alias], $this->runtime[$Model->alias]['fields']);
		$addFields = array();
		if (empty($query['fields'])) {
			$addFields = $fields;
		} elseif (is_array($query['fields'])) {
			foreach ($fields as $key => $value) {
				$field = (is_numeric($key)) ? $value : $key;

				if (in_array($Model->escapeField('*'), $query['fields']) || in_array($Model->alias . '.' . $field, $query['fields']) || in_array($field, $query['fields'])) {
					$addFields[] = $field;
				}
			}
		}

		$this->runtime[$Model->alias]['virtualFields'] = $Model->virtualFields;
		if ($addFields) {
			foreach ($addFields as $_f => $field) {
				$aliasField = is_numeric($_f) ? $field : $_f;

				foreach (array($aliasField, $Model->alias . '.' . $aliasField) as $_field) {
					$key = array_search($_field, (array)$query['fields']);

					if ($key !== false) {
						unset($query['fields'][$key]);
					}
				}
				$query = $this->_addJoin($Model, $query, $field, $aliasField, $locale);
			}
		}
		$this->runtime[$Model->alias]['beforeFind'] = $addFields;
		unset($this->_joinTable, $this->_runtimeModel);
		return $query;
	}

/**
 * Check a query's conditions for translated fields.
 * Return an array of translated fields found in the conditions.
 *
 * @param Model $Model The model being read.
 * @param array $query The query array.
 * @return array The list of translated fields that are in the conditions.
 */
	protected function _checkConditions(Model $Model, $query) {
		$conditionFields = array();
		if (empty($query['conditions']) || (!empty($query['conditions']) && !is_array($query['conditions']))) {
			return $conditionFields;
		}
		foreach ($query['conditions'] as $col => $val) {
			foreach ($this->settings[$Model->alias] as $field => $assoc) {
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
 * Appends a join for translated fields.
 *
 * @param Model $Model The model being worked on.
 * @param object $joinTable The jointable object.
 * @param array $query The query array to append a join to.
 * @param string $field The field name being joined.
 * @param string $aliasField The aliased field name being joined.
 * @param string|array $locale The locale(s) having joins added.
 * @return array The modfied query
 */
	protected function _addJoin(Model $Model, $query, $field, $aliasField, $locale) {
		$db = ConnectionManager::getDataSource($Model->useDbConfig);
		$RuntimeModel = $this->_runtimeModel;
		$joinTable = $this->_joinTable;
		$aliasVirtual = "i18n_{$field}";
		$alias = "I18n__{$field}";
		if (is_array($locale)) {
			foreach ($locale as $_locale) {
				$aliasVirtualLocale = "{$aliasVirtual}_{$_locale}";
				$aliasLocale = "{$alias}__{$_locale}";
				$Model->virtualFields[$aliasVirtualLocale] = "{$aliasLocale}.content";
				if (!empty($query['fields']) && is_array($query['fields'])) {
					$query['fields'][] = $aliasVirtualLocale;
				}
				$query['joins'][] = array(
					'type' => 'LEFT',
					'alias' => $aliasLocale,
					'table' => $joinTable,
					'conditions' => array(
						$Model->escapeField() => $db->identifier("{$aliasLocale}.foreign_key"),
						"{$aliasLocale}.model" => $Model->name,
						"{$aliasLocale}.{$RuntimeModel->displayField}" => $aliasField,
						"{$aliasLocale}.locale" => $_locale
					)
				);
			}
		} else {
			$Model->virtualFields[$aliasVirtual] = "{$alias}.content";
			if (!empty($query['fields']) && is_array($query['fields'])) {
				$query['fields'][] = $aliasVirtual;
			}
			$query['joins'][] = array(
				'type' => 'INNER',
				'alias' => $alias,
				'table' => $joinTable,
				'conditions' => array(
					"{$Model->alias}.{$Model->primaryKey}" => $db->identifier("{$alias}.foreign_key"),
					"{$alias}.model" => $Model->name,
					"{$alias}.{$RuntimeModel->displayField}" => $aliasField,
					"{$alias}.locale" => $locale
				)
			);
		}
		return $query;
	}

/**
 * afterFind Callback
 *
 * @param Model $Model Model find was run on
 * @param array $results Array of model results.
 * @param boolean $primary Did the find originate on $model.
 * @return array Modified results
 */
	public function afterFind(Model $Model, $results, $primary) {
		$Model->virtualFields = $this->runtime[$Model->alias]['virtualFields'];
		$this->runtime[$Model->alias]['virtualFields'] = $this->runtime[$Model->alias]['fields'] = array();
		$locale = $this->_getLocale($Model);

		if (empty($locale) || empty($results) || empty($this->runtime[$Model->alias]['beforeFind'])) {
			return $results;
		}
		$beforeFind = $this->runtime[$Model->alias]['beforeFind'];

		foreach ($results as $key => &$row) {
			$results[$key][$Model->alias]['locale'] = (is_array($locale)) ? current($locale) : $locale;
			foreach ($beforeFind as $_f => $field) {
				$aliasField = is_numeric($_f) ? $field : $_f;
				$aliasVirtual = "i18n_{$field}";
				if (is_array($locale)) {
					foreach ($locale as $_locale) {
						$aliasVirtualLocale = "{$aliasVirtual}_{$_locale}";
						if (!isset($row[$Model->alias][$aliasField]) && !empty($row[$Model->alias][$aliasVirtualLocale])) {
							$row[$Model->alias][$aliasField] = $row[$Model->alias][$aliasVirtualLocale];
							$row[$Model->alias]['locale'] = $_locale;
						}
						unset($row[$Model->alias][$aliasVirtualLocale]);
					}

					if (!isset($row[$Model->alias][$aliasField])) {
						$row[$Model->alias][$aliasField] = '';
					}
				} else {
					$value = '';
					if (!empty($row[$Model->alias][$aliasVirtual])) {
						$value = $row[$Model->alias][$aliasVirtual];
					}
					$row[$Model->alias][$aliasField] = $value;
					unset($row[$Model->alias][$aliasVirtual]);
				}
			}
		}
		return $results;
	}

/**
 * beforeValidate Callback
 *
 * @param Model $Model Model invalidFields was called on.
 * @return boolean
 */
	public function beforeValidate(Model $Model) {
		unset($this->runtime[$Model->alias]['beforeSave']);
		$this->_setRuntimeData($Model);
		return true;
	}

/**
 * beforeSave callback.
 *
 * Copies data into the runtime property when `$options['validate']` is
 * disabled.  Or the runtime data hasn't been set yet.
 *
 * @param Model $Model Model save was called on.
 * @return boolean true.
 */
	public function beforeSave(Model $Model, $options = array()) {
		if (isset($options['validate']) && $options['validate'] == false) {
			unset($this->runtime[$Model->alias]['beforeSave']);
		}
		if (isset($this->runtime[$Model->alias]['beforeSave'])) {
			return true;
		}
		$this->_setRuntimeData($Model);
		return true;
	}

/**
 * Sets the runtime data.
 *
 * Used from beforeValidate() and beforeSave() for compatibility issues,
 * and to allow translations to be persisted even when validation
 * is disabled.
 *
 * @param Model $Model
 * @return void
 */
	protected function _setRuntimeData(Model $Model) {
		$locale = $this->_getLocale($Model);
		if (empty($locale)) {
			return true;
		}
		$fields = array_merge($this->settings[$Model->alias], $this->runtime[$Model->alias]['fields']);
		$tempData = array();

		foreach ($fields as $key => $value) {
			$field = (is_numeric($key)) ? $value : $key;

			if (isset($Model->data[$Model->alias][$field])) {
				$tempData[$field] = $Model->data[$Model->alias][$field];
				if (is_array($Model->data[$Model->alias][$field])) {
					if (is_string($locale) && !empty($Model->data[$Model->alias][$field][$locale])) {
						$Model->data[$Model->alias][$field] = $Model->data[$Model->alias][$field][$locale];
					} else {
						$values = array_values($Model->data[$Model->alias][$field]);
						$Model->data[$Model->alias][$field] = $values[0];
					}
				}
			}
		}
		$this->runtime[$Model->alias]['beforeSave'] = $tempData;
	}

/**
 * afterSave Callback
 *
 * @param Model $Model Model the callback is called on
 * @param boolean $created Whether or not the save created a record.
 * @return void
 */
	public function afterSave(Model $Model, $created) {
		if (!isset($this->runtime[$Model->alias]['beforeValidate']) && !isset($this->runtime[$Model->alias]['beforeSave'])) {
			return true;
		}
		if (isset($this->runtime[$Model->alias]['beforeValidate'])) {
			$tempData = $this->runtime[$Model->alias]['beforeValidate'];
		} else {
			$tempData = $this->runtime[$Model->alias]['beforeSave'];
		}

		unset($this->runtime[$Model->alias]['beforeValidate'], $this->runtime[$Model->alias]['beforeSave']);
		$conditions = array('model' => $Model->alias, 'foreign_key' => $Model->id);
		$RuntimeModel = $this->translateModel($Model);

		if ($created) {
			$tempData = $this->_prepareTranslations($Model, $tempData);
		}
		$locale = $this->_getLocale($Model);

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
			$translations = $RuntimeModel->find('list', array(
				'conditions' => $conditions,
				'fields' => array(
					$RuntimeModel->alias . '.locale',
					$RuntimeModel->alias . '.id'
				)
			));
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
 * Prepares the data to be saved for translated records.
 * Add blank fields, and populates data for multi-locale saves.
 *
 * @param array $data The sparse data that was provided.
 * @return array The fully populated data to save.
 */
	protected function _prepareTranslations(Model $Model, $data) {
		$fields = array_merge($this->settings[$Model->alias], $this->runtime[$Model->alias]['fields']);
		$locales = array();
		foreach ($data as $key => $value) {
			if (is_array($value)) {
				$locales = array_merge($locales, array_keys($value));
			}
		}
		$locales = array_unique($locales);
		$hasLocales = count($locales) > 0;

		foreach ($fields as $key => $field) {
			if (!is_numeric($key)) {
				$field = $key;
			}
			if ($hasLocales && !isset($data[$field])) {
				$data[$field] = array_fill_keys($locales, '');
			} elseif (!isset($data[$field])) {
				$data[$field] = '';
			}
		}
		return $data;
	}

/**
 * afterDelete Callback
 *
 * @param Model $Model Model the callback was run on.
 * @return void
 */
	public function afterDelete(Model $Model) {
		$RuntimeModel = $this->translateModel($Model);
		$conditions = array('model' => $Model->alias, 'foreign_key' => $Model->id);
		$RuntimeModel->deleteAll($conditions);
	}

/**
 * Get selected locale for model
 *
 * @param Model $Model Model the locale needs to be set/get on.
 * @return mixed string or false
 */
	protected function _getLocale(Model $Model) {
		if (!isset($Model->locale) || is_null($Model->locale)) {
			$I18n = I18n::getInstance();
			$I18n->l10n->get(Configure::read('Config.language'));
			$Model->locale = $I18n->l10n->locale;
		}

		return $Model->locale;
	}

/**
 * Get instance of model for translations.
 *
 * If the model has a translateModel property set, this will be used as the class
 * name to find/use.  If no translateModel property is found 'I18nModel' will be used.
 *
 * @param Model $Model Model to get a translatemodel for.
 * @return Model
 */
	public function translateModel(Model $Model) {
		if (!isset($this->runtime[$Model->alias]['model'])) {
			if (!isset($Model->translateModel) || empty($Model->translateModel)) {
				$className = 'I18nModel';
			} else {
				$className = $Model->translateModel;
			}

			$this->runtime[$Model->alias]['model'] = ClassRegistry::init($className, 'Model');
		}
		if (!empty($Model->translateTable) && $Model->translateTable !== $this->runtime[$Model->alias]['model']->useTable) {
			$this->runtime[$Model->alias]['model']->setSource($Model->translateTable);
		} elseif (empty($Model->translateTable) && empty($Model->translateModel)) {
			$this->runtime[$Model->alias]['model']->setSource('i18n');
		}
		return $this->runtime[$Model->alias]['model'];
	}

/**
 * Bind translation for fields, optionally with hasMany association for
 * fake field.
 *
 * *Note* You should avoid binding translations that overlap existing model properties.
 * This can cause un-expected and un-desirable behavior.
 *
 * @param Model $Model instance of model
 * @param string|array $fields string with field or array(field1, field2=>AssocName, field3)
 * @param boolean $reset Leave true to have the fields only modified for the next operation.
 *   if false the field will be added for all future queries.
 * @return boolean
 * @throws CakeException when attempting to bind a translating called name.  This is not allowed
 *   as it shadows Model::$name.
 */
	public function bindTranslation(Model $Model, $fields, $reset = true) {
		if (is_string($fields)) {
			$fields = array($fields);
		}
		$associations = array();
		$RuntimeModel = $this->translateModel($Model);
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

			$this->_removeField($Model, $field);

			if (is_null($association)) {
				if ($reset) {
					$this->runtime[$Model->alias]['fields'][] = $field;
				} else {
					$this->settings[$Model->alias][] = $field;
				}
			} else {
				if ($reset) {
					$this->runtime[$Model->alias]['fields'][$field] = $association;
				} else {
					$this->settings[$Model->alias][$field] = $association;
				}

				foreach (array('hasOne', 'hasMany', 'belongsTo', 'hasAndBelongsToMany') as $type) {
					if (isset($Model->{$type}[$association]) || isset($Model->__backAssociation[$type][$association])) {
						trigger_error(
							__d('cake_dev', 'Association %s is already bound to model %s', $association, $Model->alias),
							E_USER_ERROR
						);
						return false;
					}
				}
				$associations[$association] = array_merge($default, array('conditions' => array(
					'model' => $Model->alias,
					$RuntimeModel->displayField => $field
				)));
			}
		}

		if (!empty($associations)) {
			$Model->bindModel(array('hasMany' => $associations), $reset);
		}
		return true;
	}

/**
 * Update runtime setting for a given field.
 *
 * @param string $field The field to update.
 */
	protected function _removeField(Model $Model, $field) {
		if (array_key_exists($field, $this->settings[$Model->alias])) {
			unset($this->settings[$Model->alias][$field]);
		} elseif (in_array($field, $this->settings[$Model->alias])) {
			$this->settings[$Model->alias] = array_merge(array_diff($this->settings[$Model->alias], array($field)));
		}

		if (array_key_exists($field, $this->runtime[$Model->alias]['fields'])) {
			unset($this->runtime[$Model->alias]['fields'][$field]);
		} elseif (in_array($field, $this->runtime[$Model->alias]['fields'])) {
			$this->runtime[$Model->alias]['fields'] = array_merge(array_diff($this->runtime[$Model->alias]['fields'], array($field)));
		}
	}

/**
 * Unbind translation for fields, optionally unbinds hasMany association for
 * fake field
 *
 * @param Model $Model instance of model
 * @param string|array $fields string with field, or array(field1, field2=>AssocName, field3), or null for
 *    unbind all original translations
 * @return boolean
 */
	public function unbindTranslation(Model $Model, $fields = null) {
		if (empty($fields) && empty($this->settings[$Model->alias])) {
			return false;
		}
		if (empty($fields)) {
			return $this->unbindTranslation($Model, $this->settings[$Model->alias]);
		}

		if (is_string($fields)) {
			$fields = array($fields);
		}
		$RuntimeModel = $this->translateModel($Model);
		$associations = array();

		foreach ($fields as $key => $value) {
			if (is_numeric($key)) {
				$field = $value;
				$association = null;
			} else {
				$field = $key;
				$association = $value;
			}

			$this->_removeField($Model, $field);

			if (!is_null($association) && (isset($Model->hasMany[$association]) || isset($Model->__backAssociation['hasMany'][$association]))) {
				$associations[] = $association;
			}
		}

		if (!empty($associations)) {
			$Model->unbindModel(array('hasMany' => $associations), false);
		}
		return true;
	}

}
