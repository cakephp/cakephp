<?php
/**
 * Translate behavior
 *
 * PHP 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2011, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2011, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       Cake.Model.Behavior
 * @since         CakePHP(tm) v 1.2.0.4525
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

App::uses('I18n', 'I18n');

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
	public function setup($model, $config = array()) {
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
	public function cleanup($model) {
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
	public function beforeFind($model, $query) {
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

		if (is_string($query['fields']) && 'COUNT(*) AS ' . $db->name('count') == $query['fields']) {
			$query['fields'] = 'COUNT(DISTINCT(' . $db->name($model->alias . '.' . $model->primaryKey) . ')) ' . $db->alias . 'count';
			$query['joins'][] = array(
				'type' => 'INNER',
				'alias' => $RuntimeModel->alias,
				'table' => $joinTable,
				'conditions' => array(
					$model->alias . '.' . $model->primaryKey => $db->identifier($RuntimeModel->alias.'.foreign_key'),
					$RuntimeModel->alias.'.model' => $model->name,
					$RuntimeModel->alias.'.locale' => $locale
				)
			);
			return $query;
		}

		$fields = array_merge($this->settings[$model->alias], $this->runtime[$model->alias]['fields']);
		$addFields = array();
		if (empty($query['fields'])) {
			$addFields = $fields;
		} else if (is_array($query['fields'])) {
			foreach ($fields as $key => $value) {
				$field = (is_numeric($key)) ? $value : $key;

				if (in_array($model->alias.'.*', $query['fields']) || in_array($model->alias.'.' . $field, $query['fields']) || in_array($field, $query['fields'])) {
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

				if (is_array($locale)) {
					foreach ($locale as $_locale) {
						$model->virtualFields['i18n_' . $field . '_' . $_locale] = 'I18n__' . $field . '__' . $_locale . '.content';
						if (!empty($query['fields'])) {
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
					if (!empty($query['fields'])) {
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
			}
		}
		$this->runtime[$model->alias]['beforeFind'] = $addFields;
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
	public function afterFind($model, $results, $primary) {
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
	public function beforeValidate($model) {
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
		return true;
	}

/**
 * afterSave Callback
 *
 * @param Model $model Model the callback is called on
 * @param boolean $created Whether or not the save created a record.
 * @return void
 */
	public function afterSave($model, $created) {
		if (!isset($this->runtime[$model->alias]['beforeSave'])) {
			return true;
		}
		$locale = $this->_getLocale($model);
		$tempData = $this->runtime[$model->alias]['beforeSave'];
		unset($this->runtime[$model->alias]['beforeSave']);
		$conditions = array('model' => $model->alias, 'foreign_key' => $model->id);
		$RuntimeModel = $this->translateModel($model);

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
	public function afterDelete($model) {
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
	protected function _getLocale($model) {
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
	public function translateModel($model) {
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
 * fake field
 *
 * @param Model $model instance of model
 * @param string|array $fields string with field or array(field1, field2=>AssocName, field3)
 * @param boolean $reset
 * @return boolean
 */
	public function bindTranslation($model, $fields, $reset = true) {
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

			if (array_key_exists($field, $this->settings[$model->alias])) {
				unset($this->settings[$model->alias][$field]);
			} elseif (in_array($field, $this->settings[$model->alias])) {
				$this->settings[$model->alias] = array_merge(array_diff_assoc($this->settings[$model->alias], array($field)));
			}

			if (array_key_exists($field, $this->runtime[$model->alias]['fields'])) {
				unset($this->runtime[$model->alias]['fields'][$field]);
			} elseif (in_array($field, $this->runtime[$model->alias]['fields'])) {
				$this->runtime[$model->alias]['fields'] = array_merge(array_diff_assoc($this->runtime[$model->alias]['fields'], array($field)));
			}

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
 * Unbind translation for fields, optionally unbinds hasMany association for
 * fake field
 *
 * @param Model $model instance of model
 * @param mixed $fields string with field, or array(field1, field2=>AssocName, field3), or null for
 *    unbind all original translations
 * @return boolean
 */
	public function unbindTranslation($model, $fields = null) {
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

			if (array_key_exists($field, $this->settings[$model->alias])) {
				unset($this->settings[$model->alias][$field]);
			} elseif (in_array($field, $this->settings[$model->alias])) {
				$this->settings[$model->alias] = array_merge(array_diff_assoc($this->settings[$model->alias], array($field)));
			}

			if (array_key_exists($field, $this->runtime[$model->alias]['fields'])) {
				unset($this->runtime[$model->alias]['fields'][$field]);
			} elseif (in_array($field, $this->runtime[$model->alias]['fields'])) {
				$this->runtime[$model->alias]['fields'] = array_merge(array_diff_assoc($this->runtime[$model->alias]['fields'], array($field)));
			}

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

/**
 * @package       Cake.Model.Behavior
 */
class I18nModel extends AppModel {

/**
 * Model name
 *
 * @var string
 */
	public $name = 'I18nModel';

/**
 * Table name
 *
 * @var string
 */
	public $useTable = 'i18n';

/**
 * Display field
 *
 * @var string
 */
	public $displayField = 'field';

}
