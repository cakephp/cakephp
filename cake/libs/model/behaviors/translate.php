<?php
/* SVN FILE: $Id$ */
/**
 * Short description for file.
 *
 * Long description for file
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       cake
 * @subpackage    cake.cake.libs.model.behaviors
 * @since         CakePHP(tm) v 1.2.0.4525
 * @version       $Revision$
 * @modifiedby    $LastChangedBy$
 * @lastmodified  $Date$
 * @license       http://www.opensource.org/licenses/mit-license.php The MIT License
 */
/**
 * Short description for file.
 *
 * Long description for file
 *
 * @package       cake
 * @subpackage    cake.cake.libs.model.behaviors
 */
class TranslateBehavior extends ModelBehavior {
/**
 * Used for runtime configuration of model
 */
	var $runtime = array();
/**
 * Callback
 *
 * $config for TranslateBehavior should be
 * array( 'fields' => array('field_one',
 * 'field_two' => 'FieldAssoc', 'field_three'))
 *
 * With above example only one permanent hasMany will be joined (for field_two
 * as FieldAssoc)
 *
 * $config could be empty - and translations configured dynamically by
 * bindTranslation() method
 *
 * @param array $config
 * @return mixed
 * @access public
 */
	function setup(&$model, $config = array()) {
		$db =& ConnectionManager::getDataSource($model->useDbConfig);
		if (!$db->connected) {
			trigger_error(
				sprintf(__('Datasource %s for TranslateBehavior of model %s is not connected', true), $model->useDbConfig, $model->alias),
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
 * Callback
 *
 * @return void
 * @access public
 */
	function cleanup(&$model) {
		$this->unbindTranslation($model);
		unset($this->settings[$model->alias]);
		unset($this->runtime[$model->alias]);
	}
/**
 * beforeFind Callback
 *
 * @param array $query
 * @return array Modified query
 * @access public
 */
	function beforeFind(&$model, $query) {
		$locale = $this->_getLocale($model);
		if (empty($locale)) {
			return $query;
		}
		$db =& ConnectionManager::getDataSource($model->useDbConfig);
		$RuntimeModel =& $this->translateModel($model);
		if (!empty($RuntimeModel->tablePrefix)) {
			$tablePrefix = $RuntimeModel->tablePrefix;
		} else {
			$tablePrefix = $db->config['prefix'];
		}

		if (is_string($query['fields']) && 'COUNT(*) AS '.$db->name('count') == $query['fields']) {
			$query['fields'] = 'COUNT(DISTINCT('.$db->name($model->alias . '.' . $model->primaryKey) . ')) ' . $db->alias . 'count';
			$query['joins'][] = array(
				'type' => 'INNER',
				'alias' => $RuntimeModel->alias,
				'table' => $db->name($tablePrefix . $RuntimeModel->useTable),
				'conditions' => array(
					$model->alias . '.' . $model->primaryKey => $db->identifier($RuntimeModel->alias.'.foreign_key'),
					$RuntimeModel->alias.'.model' => $model->name,
					$RuntimeModel->alias.'.locale' => $locale
				)
			);
			return $query;
		}
		$autoFields = false;

		if (empty($query['fields'])) {
			$query['fields'] = array($model->alias.'.*');

			$recursive = $model->recursive;
			if (isset($query['recursive'])) {
				$recursive = $query['recursive'];
			}

			if ($recursive >= 0) {
				foreach (array('hasOne', 'belongsTo') as $type) {
					foreach ($model->{$type} as $key => $value) {

						if (empty($value['fields'])) {
							$query['fields'][] = $key.'.*';
						} else {
							foreach ($value['fields'] as $field) {
								$query['fields'][] = $key.'.'.$field;
							}
						}
					}
				}
			}
			$autoFields = true;
		}
		$fields = array_merge($this->settings[$model->alias], $this->runtime[$model->alias]['fields']);
		$addFields = array();
		if (is_array($query['fields'])) {
			foreach ($fields as $key => $value) {
				$field = (is_numeric($key)) ? $value : $key;

				if (in_array($model->alias.'.*', $query['fields']) || $autoFields || in_array($model->alias.'.'.$field, $query['fields']) || in_array($field, $query['fields'])) {
					$addFields[] = $field;
				}
			}
		}

		if ($addFields) {
			foreach ($addFields as $field) {
				foreach (array($field, $model->alias.'.'.$field) as $_field) {
					$key = array_search($_field, $query['fields']);

					if ($key !== false) {
						unset($query['fields'][$key]);
					}
				}

				if (is_array($locale)) {
					foreach ($locale as $_locale) {
						$query['fields'][] = 'I18n__'.$field.'__'.$_locale.'.content';
						$query['joins'][] = array(
							'type' => 'LEFT',
							'alias' => 'I18n__'.$field.'__'.$_locale,
							'table' => $db->name($tablePrefix . $RuntimeModel->useTable),
							'conditions' => array(
								$model->alias . '.' . $model->primaryKey => $db->identifier("I18n__{$field}__{$_locale}.foreign_key"),
								'I18n__'.$field.'__'.$_locale.'.model' => $model->name,
								'I18n__'.$field.'__'.$_locale.'.'.$RuntimeModel->displayField => $field,
								'I18n__'.$field.'__'.$_locale.'.locale' => $_locale
							)
						);
					}
				} else {
					$query['fields'][] = 'I18n__'.$field.'.content';
					$query['joins'][] = array(
						'type' => 'LEFT',
						'alias' => 'I18n__'.$field,
						'table' => $db->name($tablePrefix . $RuntimeModel->useTable),
						'conditions' => array(
							$model->alias . '.' . $model->primaryKey => $db->identifier("I18n__{$field}.foreign_key"),
							'I18n__'.$field.'.model' => $model->name,
							'I18n__'.$field.'.'.$RuntimeModel->displayField => $field
						)
					);

					if (is_string($query['conditions'])) {
						$query['conditions'] = $db->conditions($query['conditions'], true, false, $model) . ' AND '.$db->name('I18n__'.$field.'.locale').' = \''.$locale.'\'';
					} else {
						$query['conditions'][$db->name("I18n__{$field}.locale")] = $locale;
					}
				}
			}
		}
		if (is_array($query['fields'])) {
			$query['fields'] = array_merge($query['fields']);
		}
		$this->runtime[$model->alias]['beforeFind'] = $addFields;
		return $query;
	}
/**
 * afterFind Callback
 *
 * @param array $results
 * @param boolean $primary
 * @return array Modified results
 * @access public
 */
	function afterFind(&$model, $results, $primary) {
		$this->runtime[$model->alias]['fields'] = array();
		$locale = $this->_getLocale($model);

		if (empty($locale) || empty($results) || empty($this->runtime[$model->alias]['beforeFind'])) {
			return $results;
		}
		$beforeFind = $this->runtime[$model->alias]['beforeFind'];

		foreach ($results as $key => $row) {
			$results[$key][$model->alias]['locale'] = (is_array($locale)) ? @$locale[0] : $locale;

			foreach ($beforeFind as $field) {
				if (is_array($locale)) {
					foreach ($locale as $_locale) {
						if (!isset($results[$key][$model->alias][$field]) && !empty($results[$key]['I18n__'.$field.'__'.$_locale]['content'])) {
							$results[$key][$model->alias][$field] = $results[$key]['I18n__'.$field.'__'.$_locale]['content'];
						}
						unset($results[$key]['I18n__'.$field.'__'.$_locale]);
					}

					if (!isset($results[$key][$model->alias][$field])) {
						$results[$key][$model->alias][$field] = '';
					}
				} else {
					$value = '';
					if (!empty($results[$key]['I18n__'.$field]['content'])) {
						$value = $results[$key]['I18n__'.$field]['content'];
					}
					$results[$key][$model->alias][$field] = $value;
					unset($results[$key]['I18n__'.$field]);
				}
			}
		}
		return $results;
	}
/**
 * beforeValidate Callback
 *
 * @return boolean
 * @access public
 */
	function beforeValidate(&$model) {
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
 * @param boolean $created
 * @return void
 * @access public
 */
	function afterSave(&$model, $created) {
		if (!isset($this->runtime[$model->alias]['beforeSave'])) {
			return true;
		}
		$locale = $this->_getLocale($model);
		$tempData = $this->runtime[$model->alias]['beforeSave'];
		unset($this->runtime[$model->alias]['beforeSave']);
		$conditions = array('model' => $model->alias, 'foreign_key' => $model->id);
		$RuntimeModel =& $this->translateModel($model);

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
 * @return void
 * @access public
 */
	function afterDelete(&$model) {
		$RuntimeModel =& $this->translateModel($model);
		$conditions = array('model' => $model->alias, 'foreign_key' => $model->id);
		$RuntimeModel->deleteAll($conditions);
	}
/**
 * Get selected locale for model
 *
 * @return mixed string or false
 * @access protected
 */
	function _getLocale(&$model) {
		if (!isset($model->locale) || is_null($model->locale)) {
			if (!class_exists('I18n')) {
				App::import('Core', 'i18n');
			}
			$I18n =& I18n::getInstance();
			$I18n->l10n->get(Configure::read('Config.language'));
			$model->locale = $I18n->l10n->locale;
		}

		return $model->locale;
	}
/**
 * Get instance of model for translations
 *
 * @return object
 * @access public
 */
	function &translateModel(&$model) {
		if (!isset($this->runtime[$model->alias]['model'])) {
			if (!isset($model->translateModel) || empty($model->translateModel)) {
				$className = 'I18nModel';
			} else {
				$className = $model->translateModel;
			}

			if (PHP5) {
				$this->runtime[$model->alias]['model'] = ClassRegistry::init($className, 'Model');
			} else {
				$this->runtime[$model->alias]['model'] =& ClassRegistry::init($className, 'Model');
			}
		}
		if (!empty($model->translateTable) && $model->translateTable !== $this->runtime[$model->alias]['model']->useTable) {
			$this->runtime[$model->alias]['model']->setSource($model->translateTable);
		} elseif (empty($model->translateTable) && empty($model->translateModel)) {
			$this->runtime[$model->alias]['model']->setSource('i18n');
		}
		$model =& $this->runtime[$model->alias]['model'];
		return $model;
	}
/**
 * Bind translation for fields, optionally with hasMany association for
 * fake field
 *
 * @param object instance of model
 * @param mixed string with field or array(field1, field2=>AssocName, field3)
 * @param boolean $reset
 * @return bool
 */
	function bindTranslation(&$model, $fields, $reset = true) {
		if (is_string($fields)) {
			$fields = array($fields);
		}
		$associations = array();
		$RuntimeModel =& $this->translateModel($model);
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
							sprintf(__('Association %s is already binded to model %s', true), $association, $model->alias),
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
 * @param object instance of model
 * @param mixed string with field, or array(field1, field2=>AssocName, field3), or null for unbind all original translations
 * @return bool
 */
	function unbindTranslation(&$model, $fields = null) {
		if (empty($fields)) {
			return $this->unbindTranslation($model, $this->settings[$model->alias]);
		}

		if (is_string($fields)) {
			$fields = array($fields);
		}
		$RuntimeModel =& $this->translateModel($model);
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
if (!defined('CAKEPHP_UNIT_TEST_EXECUTION')) {
/**
 * @package       cake
 * @subpackage    cake.cake.libs.model.behaviors
 */
	class I18nModel extends AppModel {
		var $name = 'I18nModel';
		var $useTable = 'i18n';
		var $displayField = 'field';
	}
}
?>