<?php
/* SVN FILE: $Id$ */
/**
 * Short description for file.
 *
 * Long description for file
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) :  Rapid Development Framework <http://www.cakephp.org/>
 * Copyright 2005-2007, Cake Software Foundation, Inc.
 *								1785 E. Sahara Avenue, Suite 490-204
 *								Las Vegas, Nevada 89104
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @filesource
 * @copyright		Copyright 2005-2007, Cake Software Foundation, Inc.
 * @link				http://www.cakefoundation.org/projects/info/cakephp CakePHP(tm) Project
 * @package			cake
 * @subpackage		cake.cake.libs.model.behaviors
 * @since			CakePHP(tm) v 1.2.0.4525
 * @version			$Revision$
 * @modifiedby		$LastChangedBy$
 * @lastmodified	$Date$
 * @license			http://www.opensource.org/licenses/mit-license.php The MIT License
 */
/**
 * Short description for file.
 *
 * Long description for file
 *
 * @package	 	cake
 * @subpackage	cake.cake.libs.model.behaviors
 */
class TranslateBehavior extends ModelBehavior {
/**
 * Used for runtime configuration of model
 */
	var $runtime = array();
/**
 * Instance of I18nModel class, used internally
 */
	var $_model = null;
/**
 * Constructor
 */
	function __construct() {
		parent::__construct();

		$this->_model =& new I18nModel();
		ClassRegistry::addObject('I18nModel', $this->_model);
	}
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
 */
	function setup(&$model, $config = array()) {
		$this->settings[$model->name] = array();
		$this->runtime[$model->name] = array('fields' => array());
		$db =& ConnectionManager::getDataSource($model->useDbConfig);

		if (!$db->connected) {
			trigger_error('Datasource '.$model->useDbConfig.' for I18nBehavior of model '.$model->name.' is not connected', E_USER_ERROR);
			return false;
		}
		$this->runtime[$model->name]['tablePrefix'] = $db->config['prefix'];
		return $this->bindTranslation($model, $config, false);
	}
/**
 * Callback
 */
	function beforeFind(&$model, $query) {
		$locale = $this->_getLocale($model);

		if (is_string($query['fields']) && 'COUNT(*) AS count' == $query['fields']) {
			$this->runtime[$model->name]['count'] = true;

			$db =& ConnectionManager::getDataSource($model->useDbConfig);
			$tablePrefix = $this->runtime[$model->name]['tablePrefix'];

			$query['fields'] = 'COUNT(DISTINCT('.$db->name($model->name).'.'.$db->name($model->primaryKey).')) ' . $db->alias . 'count';
			$query['joins'][] = array(
				'type' => 'INNER',
				'alias' => 'I18nModel',
				'table' => $tablePrefix . 'i18n',
				'conditions' => array(
					$model->name.'.id'	=> '{$__cakeIdentifier[I18nModel.row_id]__$}',
					'I18nModel.model'	=> $model->name,
					'I18nModel.locale'	=> $locale
				)
			);
			return $query;
		}

		if (empty($locale) || is_array($locale)) {
			return $query;
		}
		$autoFields = false;

		if (empty($query['fields'])) {
			$query['fields'] = array($model->name.'.*');

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
			$autoFields = true;
		}

		$fields = am($this->settings[$model->name], $this->runtime[$model->name]['fields']);
		$tablePrefix = $this->runtime[$model->name]['tablePrefix'];
		$addFields = array();

		if (is_array($query['fields'])) {
			if (in_array($model->name.'.*', $query['fields'])) {
				foreach ($fields as $key => $value) {
					$addFields[] = ife(is_numeric($key), $value, $key);
				}
			} else {
				foreach ($fields as $key => $value) {
					$field = ife(is_numeric($key), $value, $key);
					if ($autoFields || in_array($model->name.'.'.$field, $query['fields']) || in_array($field, $query['fields'])) {
						$addFields[] = $field;
					}
				}
			}
		}

		if ($addFields) {
			$db =& ConnectionManager::getDataSource($model->useDbConfig);

			foreach ($addFields as $field) {
				$key = array_search($model->name.'.'.$field, $query['fields']);
				if (false !== $key) {
					unset($query['fields'][$key]);
				}

				$query['fields'][] = 'I18n__'.$field.'.content';

				$query['joins'][] = 'LEFT JOIN '.$db->name($tablePrefix.'i18n').' AS '.$db->name('I18n__'.$field.'Model').' ON '.$db->name($model->name.'.id').' = '.$db->name('I18n__'.$field.'Model.row_id');
				$query['joins'][] = 'LEFT JOIN '.$db->name($tablePrefix.'i18n_content').' AS '.$db->name('I18n__'.$field).' ON '.$db->name('I18n__'.$field.'Model.i18n_content_id').' = '.$db->name('I18n__'.$field.'.id');
				$query['conditions'][$db->name('I18n__'.$field.'Model.model')] = $model->name;
				$query['conditions'][$db->name('I18n__'.$field.'Model.field')] = $field;
				$query['conditions'][$db->name('I18n__'.$field.'Model`.`locale')] = $locale;
			}
		}
		$query['fields'] = am($query['fields']);
		$this->runtime[$model->name]['beforeFind'] = $addFields;
		return $query;
	}
/**
 * Callback
 */
	function afterFind(&$model, $results, $primary) {
		if (!empty($this->runtime[$model->name]['count'])) {
			unset($this->runtime[$model->name]['count']);
			return $results;
		}
		$this->runtime[$model->name]['fields'] = array();
		$locale = $this->_getLocale($model);

		if (empty($locale) || empty($results)) {
			return $results;
		}

		if (is_array($locale)) {
			$fields = am($this->settings[$model->name], $this->runtime[$model->name]['fields']);
			$emptyFields = array('locale' => '');

			foreach ($fields as $key => $value) {
				$field = ife(is_numeric($key), $value, $key);
				$emptyFields[$field] = '';
			}
			unset($fields);

			foreach ($results as $key => $row) {
				$results[$key][$model->name] = am($results[$key][$model->name], $emptyFields);
			}
			unset($emptyFields);
		} elseif (!empty($this->runtime[$model->name]['beforeFind'])) {
			$beforeFind = $this->runtime[$model->name]['beforeFind'];

			foreach ($results as $key => $row) {
				$results[$key][$model->name]['locale'] = $locale;

				foreach ($beforeFind as $field) {
					$value = ife(empty($results[$key]['I18n__'.$field]['content']), '', $results[$key]['I18n__'.$field]['content']);
					$results[$key][$model->name][$field] = $value;
					unset($results[$key]['I18n__'.$field]);
				}
			}
		}
		return $results;
	}
/**
 * Callback
 */
	function beforeSave(&$model) {
		$locale = $this->_getLocale($model);

		if (empty($locale) || is_array($locale)) {
			return true;
		}
		$fields = am($this->settings[$model->name], $this->runtime[$model->name]['fields']);
		$tempData = array();

		foreach ($fields as $key => $value) {
			$field = ife(is_numeric($key), $value, $key);

			if (isset($model->data[$model->name][$field])) {
				$tempData[$field] = $model->data[$model->name][$field];
				unset($model->data[$model->name][$field]);
			}
		}
		$this->runtime[$model->name]['beforeSave'] = $tempData;
		$this->runtime[$model->name]['ignoreUserAbort'] = ignore_user_abort();
		@ignore_user_abort(true);
		return true;
	}
/**
 * Callback
 */
	function afterSave(&$model, $created) {
		$locale = $this->_getLocale($model);

		if (empty($locale) || is_array($locale) || empty($this->runtime[$model->name]['beforeSave'])) {
			return true;
		}
		$tempData = $this->runtime[$model->name]['beforeSave'];
		unset($this->runtime[$model->name]['beforeSave']);

		$conditions = array('locale' => $locale,
									'model' => $model->name,
									'row_id' => $model->id);

		if ($created) {
			foreach ($tempData as $field => $value) {
				$this->_model->Content->create();
				$this->_model->Content->save(array('I18nContent' => array('content' => $value)));

				$this->_model->create();
				$this->_model->save(array('I18nModel' => am($conditions, array(
													'i18n_content_id' => $this->_model->Content->getInsertID(),
													'field' => $field))));
			}
		} else {
			$this->_model->recursive = -1;
			$translations = $this->_model->findAll($conditions, array('field', 'i18n_content_id'));
			$fields = Set::extract($translations, '{n}.I18nModel.field');
			$ids = Set::extract($translations, '{n}.I18nModel.i18n_content_id');

			foreach ($fields as $key => $field) {
				if (array_key_exists($field, $tempData)) {
					$this->_model->Content->create();
					$this->_model->Content->save(array('I18nContent' => array(
																	'id' => $ids[$key],
																	'content' => $tempData[$field])));
				}
			}
		}
		@ignore_user_abort((bool) $this->runtime[$model->name]['ignoreUserAbort']);
		unset($this->runtime[$model->name]['ignoreUserAbort']);
	}
/**
 * Callback
 */
	function beforeDelete(&$model) {
		$this->runtime[$model->name]['ignoreUserAbort'] = ignore_user_abort();
		@ignore_user_abort(true);
		return true;
	}
/**
 * Callback
 */
	function afterDelete(&$model) {
		$this->_model->recursive = -1;
		$conditions = array('model' => $model->name, 'row_id' => $model->id);
		$translations = $this->_model->findAll($conditions, array('i18n_content_id'));
		$ids = Set::extract($translations, '{n}.I18nModel.i18n_content_id');

		$db =& ConnectionManager::getDataSource($model->useDbConfig);
		$db->delete($this->_model->Content, array('id' => $ids));
		$db->delete($this->_model, $conditions);

		@ignore_user_abort((bool) $this->runtime[$model->name]['ignoreUserAbort']);
		unset($this->runtime[$model->name]['ignoreUserAbort']);
	}
/**
 * Autodetects locale for application
 *
 * @todo
 * @return string
 */
	function _autoDetectLocale() {
		// just fast hack to obtain selected locale
		__d('core', 'Notice', true);
		$I18n =& I18n::getInstance();
		return $I18n->locale;
	}
/**
 * Get selected locale for model
 *
 * @return mixed string or false
 */
	function _getLocale(&$model) {
		if (!isset($model->locale) || is_null($model->locale)) {
			$model->locale = $this->_autoDetectLocale();
		}
		return $model->locale;
	}

/**
 * Bind translation for fields, optionally with hasMany association for
 * fake field
 *
 * @param object instance of model
 * @param mixed string with field or array(field1, field2=>AssocName, field3)
 * @param boolead $reset
 * @return boolean
 */
	function bindTranslation(&$model, $fields, $reset = true) {
		if (empty($fields)) {
			return true;
		}

		if (is_string($fields)) {
			$fields = array($fields);
		}
		$settings =& $this->settings[$model->name];
		$runtime =& $this->runtime[$model->name]['fields'];
		$associations = array();

		$default = array('className' => 'I18nModel', 'foreignKey' => 'row_id');

		foreach ($fields as $key => $value) {

			if (is_numeric($key)) {
				$field = $value;
				$association = null;
			} else {
				$field = $key;
				$association = $value;
			}

			if (in_array($field, $settings)) {
				$this->settings[$model->name] = array_diff_assoc($settings, array($field));
			} elseif (array_key_exists($field, $settings)) {
				unset($settings[$field]);
			}

			if (in_array($field, $runtime)) {
				$this->runtime[$model->name]['fields'] = array_diff_assoc($runtime, array($field));
			} elseif (array_key_exists($field, $runtime)) {
				unset($runtime[$field]);
			}

			if (is_null($association)) {
				if ($reset) {
					$runtime[] = $field;
				} else {
					$settings[] = $field;
				}
			} else {
				if ($reset) {
					$runtime[$field] = $association;
				} else {
					$settings[$field] = $association;
				}

				foreach (array('hasOne', 'hasMany', 'belongsTo', 'hasAndBelongsToMany') as $type) {
					if (isset($model->{$type}[$association]) || isset($model->__backAssociation[$type][$association])) {
						trigger_error('Association '.$association.' is already binded to model '.$model->name, E_USER_ERROR);
						return false;
					}
				}
				$associations[$association] = am($default, array('conditions' => array(
																					'model' => $model->name,
																					'field' => $field)));
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
 * @param mixed string with field or array(field1, field2=>AssocName, field3)
 * @return boolean
 */
	function unbindTranslation(&$model, $fields) {
		if (empty($fields)) {
			return true;
		}

		if (is_string($fields)) {
			$fields = array($fields);
		}
		$settings =& $this->settings[$model->name];
		$runtime =& $this->runtime[$model->name]['fields'];

		$default = array('className' => 'I18nModel', 'foreignKey' => 'row_id');
		$associations = array();

		foreach ($fields as $key => $value) {
			if (is_numeric($key)) {
				$field = $value;
				$association = null;
			} else {
				$field = $key;
				$association = $value;
			}

			if (in_array($field, $settings)) {
				$this->settings[$model->name] = array_diff_assoc($settings, array($field));
			} elseif (array_key_exists($field, $settings)) {
				unset($settings[$field]);
			}

			if (in_array($field, $runtime)) {
				$this->runtime[$model->name]['fields'] = array_diff_assoc($runtime, array($field));
			} elseif (array_key_exists($field, $runtime)) {
				unset($runtime[$field]);
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
 * @package	 	cake
 * @subpackage	cake.cake.libs.model.behaviors
 */
class I18nContent extends AppModel {
	var $name = 'I18nContent';
	var $useTable = 'i18n_content';
}
/**
 * @package	 	cake
 * @subpackage	cake.cake.libs.model.behaviors
 */
class I18nModel extends AppModel {
	var $name = 'I18nModel';
	var $useTable = 'i18n';
	var $belongsTo = array('Content' => array('className' => 'I18nContent', 'foreignKey' => 'i18n_content_id'));
}
?>
