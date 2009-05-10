<?php
/* SVN FILE: $Id$ */
/**
 * The ModelTask handles creating and updating models files.
 *
 * Long description for file
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) :  Rapid Development Framework (http://www.cakephp.org)
 * Copyright 2005-2008, Cake Software Foundation, Inc. (http://www.cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @filesource
 * @copyright     Copyright 2005-2008, Cake Software Foundation, Inc. (http://www.cakefoundation.org)
 * @link          http://www.cakefoundation.org/projects/info/cakephp CakePHP(tm) Project
 * @package       cake
 * @subpackage    cake.cake.console.libs.tasks
 * @since         CakePHP(tm) v 1.2
 * @version       $Revision$
 * @modifiedby    $LastChangedBy$
 * @lastmodified  $Date$
 * @license       http://www.opensource.org/licenses/mit-license.php The MIT License
 */
App::import('Model', 'ConnectionManager');
/**
 * Task class for creating and updating model files.
 *
 * @package       cake
 * @subpackage    cake.cake.console.libs.tasks
 */
class ModelTask extends Shell {
/**
 * Name of plugin
 *
 * @var string
 * @access public
 */
	var $plugin = null;

/**
 * Name of the db connection used.
 *
 * @var string
 * @access public
 */
	var $connection = null;

/**
 * path to MODELS directory
 *
 * @var string
 * @access public
 */
	var $path = MODELS;

/**
 * tasks
 *
 * @var array
 * @access public
 */
	var $tasks = array('DbConfig', 'Fixture', 'Test');

/**
 * Holds tables found on connection.
 *
 * @var array
 **/
	var $__tables = array();

/**
 * Holds validation method map.
 *
 * @var array
 **/
	var $__validations = array();

/**
 * Execution method always used for tasks
 *
 * @access public
 */
	function execute() {
		if (empty($this->args)) {
			$this->__interactive();
		}

		if (!empty($this->args[0])) {
			$this->interactive = false;
			if (!isset($this->connection)) {
				$this->connection = 'default';
			}
			if (strtolower($this->args[0]) == 'all') {
				return $this->all();
			}
			$model = Inflector::camelize($this->args[0]);
			$object = $this->_getModelObject($model);
			if ($this->bake($object, false)) {
				if ($this->_checkUnitTest()) {
					$this->bakeTest($model);
				}
			}
		}
	}

/**
 * Bake all models at once.
 *
 * @return void
 **/
	function all() {
		$this->listAll($ds, false);

		foreach ($this->__tables as $table) {
			$modelClass = Inflector::classify($table);
			$this->out(sprintf(__('Baking %s', true), $modelClass));
			$this->_getModelObject($modelClass);
			$this->bake($object, false);
		}
	}
/**
 * Get a model object for a class name.
 *
 * @param string $className Name of class you want model to be.
 * @return object Model instance
 **/
	function _getModelObject($className) {
		if (App::import('Model', $className)) {
			$object = new $className();
		} else {
			App::import('Model');
			$object = new Model(array('name' => $className, 'ds' => $this->connection));
		}
		return $object;
	}
/**
 * Handles interactive baking
 *
 * @access private
 */
	function __interactive() {
		$this->hr();
		$this->out(sprintf("Bake Model\nPath: %s", $this->path));
		$this->hr();
		$this->interactive = true;

		$primaryKey = 'id';
		$validate = $associations = array();

		if (empty($this->connection)) {
			$this->connection = $this->DbConfig->getConfig();
		}
		$currentModelName = $this->getName();
		$useTable = $this->getTable($currentModelName);
		$db =& ConnectionManager::getDataSource($this->connection);
		$fullTableName = $db->fullTableName($useTable);

		if (in_array($useTable, $this->__tables)) {
			App::import('Model');
			$tempModel = new Model(array('name' => $currentModelName, 'table' => $useTable, 'ds' => $this->connection));
			$fields = $tempModel->schema();
			if (!array_key_exists('id', $fields)) {
				$primaryKey = $this->findPrimaryKey($fields);
			}
		}

		$prompt = __("Would you like to supply validation criteria \nfor the fields in your model?", true);
		$wannaDoValidation = $this->in($prompt, array('y','n'), 'y');
		if (array_search($useTable, $this->__tables) !== false && strtolower($wannaDoValidation) == 'y') {
			$validate = $this->doValidation($tempModel);
		}

		$prompt = __("Would you like to define model associations\n(hasMany, hasOne, belongsTo, etc.)?", true);
		$wannaDoAssoc = $this->in($prompt, array('y','n'), 'y');
		if (strtolower($wannaDoAssoc) == 'y') {
			$associations = $this->doAssociations($tempModel);
		}

		$this->out('');
		$this->hr();
		$this->out(__('The following Model will be created:', true));
		$this->hr();
		$this->out("Name:       " . $currentModelName);

		if ($this->connection !== 'default') {
			$this->out(sprintf(__("DB Config:  %s", true), $useDbConfig));
		}
		if ($fullTableName !== Inflector::tableize($currentModelName)) {
			$this->out(sprintf(__("DB Table:   %s", true), $fullTableName));
		}
		if ($primaryKey != 'id') {
			$this->out(sprintf(__("Primary Key: %s", true), $primaryKey));
		}
		if (!empty($validate)) {
			$this->out(sprintf(__("Validation: %s", true), print_r($validate, true)));
		}
		if (!empty($associations)) {
			$this->out(__("Associations:", true));
			$assocKeys = array('belongsTo', 'hasOne', 'hasMany', 'hasAndBelongsToMany');
			foreach ($assocKeys as $assocKey) {
				$this->_printAssociation($currentModelName, $assocKey, $associations);
			}
		}

		$this->hr();
		$looksGood = $this->in(__('Look okay?', true), array('y','n'), 'y');
		
		if (strtolower($looksGood) == 'y') {
			if ($this->bake($currentModelName, $associations, $validate, $primaryKey, $useTable, $this->connection)) {
				if ($this->_checkUnitTest()) {
					$this->bakeTest($currentModelName, $useTable, $associations);
				}
			}
		} else {
			return false;
		}
	}

/**
 * Print out all the associations of a particular type
 *
 * @param string $modelName Name of the model relations belong to.
 * @param string $type Name of association you want to see. i.e. 'belongsTo'
 * @param string $associations Collection of associations.
 * @access protected
 * @return void
 **/
	function _printAssociation($modelName, $type, $associations) {
		if (!empty($associations[$type])) {
			for ($i = 0; $i < count($associations[$type]); $i++) {
				$out = "\t" . $modelName . ' ' . $type . ' ' . $associations[$type][$i]['alias'];
				$this->out($out);
			}
		}
	}

/**
 * Finds a primary Key in a list of fields.
 *
 * @param array $fields Array of fields that might have a primary key.
 * @return string Name of field that is a primary key.
 * @access public
 **/
	function findPrimaryKey($fields) {
		foreach ($fields as $name => $field) {
			if (isset($field['key']) && $field['key'] == 'primary') {
				break;
			}
		}
		return $this->in(__('What is the primaryKey?', true), null, $name);
	}

/**
 * Handles Generation and user interaction for creating validation.
 *
 * @param object $model
 * @param boolean $interactive
 * @return array $validate
 * @access public
 */
	function doValidation(&$model) {
		if (!is_object($model)) {
			return false;
		}
		$fields = $model->schema();

		if (empty($fields)) {
			return false;
		}
		$validate = array();
		$this->initValidations();
		foreach ($fields as $fieldName => $field) {
			$validation = $this->fieldValidation($fieldName, $field, $model->primaryKey);
			if (!empty($validation)) {
				$validate[$fieldName] = $validation;
			}
		}
		return $validate;
	}
/**
 * Populate the __validations array 
 *
 * @return void
 **/
	function initValidations() {
		$options = $choices = array();
		if (class_exists('Validation')) {
			$parent = get_class_methods(get_parent_class('Validation'));
			$options = array_diff(get_class_methods('Validation'), $parent);
		}
		sort($options);
		$default = 1;
		foreach ($options as $key => $option) {
			if ($option{0} != '_' && strtolower($option) != 'getinstance') {
				$choices[$default] = strtolower($option);
				$default++;
			}
		}
		$this->__validations = $choices;
		return $choices;
	}
/**
 * Does individual field validation handling.
 *
 * @param string $fieldName Name of field to be validated.
 * @param array $metaData metadata for field
 * @return array Array of validation for the field.
 **/
	function fieldValidation($fieldName, $metaData, $primaryKey = 'id') {
		$defaultChoice = count($this->__validations);
		$validate = $alreadyChosen = array();
		
		$anotherValidator = 'y';
		while ($anotherValidator == 'y') {
			if ($this->interactive) {
				$this->out('');
				$this->out(sprintf(__('Field: %s', true), $fieldName));
				$this->out(sprintf(__('Type: %s', true), $metaData['type']));
				$this->hr();
				$this->out(__('Please select one of the following validation options:', true));
				$this->hr();
			}

			$prompt = '';
			for ($i = 1; $i < $defaultChoice; $i++) {
				$prompt .= $i . ' - ' . $this->__validations[$i] . "\n";
			}
			$prompt .=  sprintf(__("%s - Do not do any validation on this field.\n", true), $defaultChoice);
			$prompt .= __("... or enter in a valid regex validation string.\n", true);

			$methods = array_flip($this->__validations);
			$guess = $defaultChoice;
			if ($metaData['null'] != 1 && !in_array($fieldName, array($primaryKey, 'created', 'modified', 'updated'))) {
				if ($fieldName == 'email') {
					$guess = $methods['email'];
				} elseif ($metaData['type'] == 'string') {
					$guess = $methods['notempty'];
				} elseif ($metaData['type'] == 'integer') {
					$guess = $methods['numeric'];
				} elseif ($metaData['type'] == 'boolean') {
					$guess = $methods['numeric'];
				} elseif ($metaData['type'] == 'datetime' || $metaData['type'] == 'date') {
					$guess = $methods['date'];
				} elseif ($metaData['type'] == 'time') {
					$guess = $methods['time'];
				}
			}

			if ($this->interactive === true) {
				$choice = $this->in($prompt, null, $guess);
				if (in_array($choice, $alreadyChosen)) {
					$this->out(__("You have already chosen that validation rule,\nplease choose again", true));
					continue;
				}
				$alreadyChosen[] = $choice;
			} else {
				$choice = $guess;
			}
			$validatorName = $this->__validations[$choice];
			if ($choice != $defaultChoice) {
				if (is_numeric($choice) && isset($this->__validations[$choice])) {
					$validate[$validatorName] = $this->__validations[$choice];
				} else {
					$validate[$validatorName] = $choice;
				}
			}
			if ($this->interactive == true && $choice != $defaultChoice) {
				$anotherValidator = $this->in(__('Would you like to add another validation rule?', true), array('y', 'n'), 'n');
			} else {
				$anotherValidator = 'n';
			}
		}
		return $validate;
	}

/**
 * Handles associations
 *
 * @param object $model
 * @return array $assocaitons
 * @access public
 */
	function doAssociations(&$model) {
		if (!is_object($model)) {
			return false;
		}
		App::import('Model');
		$this->out(__('One moment while the associations are detected.', true));

		$fields = $model->schema();
		if (empty($fields)) {
			return false;
		}

		$associations = array(
			'belongsTo' => array(), 'hasMany' => array(), 'hasOne'=> array(), 'hasAndBelongsToMany' => array()
		);
		$possibleKeys = array();

		$associations = $this->findBelongsTo($model, $associations);
		$associations = $this->findHasOneAndMany($model, $associations);
		$associations = $this->findHasAndBelongsToMany($model, $associations);

		if ($this->interactive !== true) {
			unset($associations['hasOne']);
		}

		if ($this->interactive === true) {
			$this->hr();
			if (empty($associations)) {
				$this->out(__('None found.', true));
			} else {
				$this->out(__('Please confirm the following associations:', true));
				$this->hr();
				foreach ($associations as $type => $settings) {
					if (!empty($associations[$type])) {
						$count = count($associations[$type]);
						$response = 'y';
						for ($i = 0; $i < $count; $i++) {
							$prompt = "{$model->name} {$type} {$associations[$type][$i]['alias']}";
							$response = $this->in("{$prompt}?", array('y','n'), 'y');

							if ('n' == low($response) || 'no' == low($response)) {
								unset($associations[$type][$i]);
							} else {
								if ($model->name === $associations[$type][$i]['alias']) {
									if ($type === 'belongsTo') {
										$alias = 'Parent' . $associations[$type][$i]['alias'];
									}
									if ($type === 'hasOne' || $type === 'hasMany') {
										$alias = 'Child' . $associations[$type][$i]['alias'];
									}

									$alternateAlias = $this->in(sprintf(__('This is a self join. Use %s as the alias', true), $alias), array('y', 'n'), 'y');

									if ('n' == low($alternateAlias) || 'no' == low($alternateAlias)) {
										$associations[$type][$i]['alias'] = $this->in(__('Specify an alternate alias.', true));
									} else {
										$associations[$type][$i]['alias'] = $alias;
									}
								}
							}
						}
						$associations[$type] = array_merge($associations[$type]);
					}
				}
			}

			$wannaDoMoreAssoc = $this->in(__('Would you like to define some additional model associations?', true), array('y','n'), 'n');

			while ((low($wannaDoMoreAssoc) == 'y' || low($wannaDoMoreAssoc) == 'yes')) {
				$assocs = array(1 => 'belongsTo', 2 => 'hasOne', 3 => 'hasMany', 4 => 'hasAndBelongsToMany');
				$bad = true;
				while ($bad) {
					$this->out(__('What is the association type?', true));
					$prompt = "1. belongsTo\n";
					$prompt .= "2. hasOne\n";
					$prompt .= "3. hasMany\n";
					$prompt .= "4. hasAndBelongsToMany\n";
					$assocType = intval($this->in($prompt, null, __("Enter a number", true)));

					if (intval($assocType) < 1 || intval($assocType) > 4) {
						$this->out(__('The selection you entered was invalid. Please enter a number between 1 and 4.', true));
					} else {
						$bad = false;
					}
				}
				$this->out(__('For the following options be very careful to match your setup exactly. Any spelling mistakes will cause errors.', true));
				$this->hr();
				$alias = $this->in(__('What is the alias for this association?', true));
				$className = $this->in(sprintf(__('What className will %s use?', true), $alias), null, $alias );
				$suggestedForeignKey = null;
				if ($assocType == '1') {
					$showKeys = $possibleKeys[$model->table];
					$suggestedForeignKey = $this->_modelKey($alias);
				} else {
					$otherTable = Inflector::tableize($className);
					if (in_array($otherTable, $this->__tables)) {
						if ($assocType < '4') {
							$showKeys = $possibleKeys[$otherTable];
						} else {
							$showKeys = null;
						}
					} else {
						$otherTable = $this->in(__('What is the table for this model?', true));
						$showKeys = $possibleKeys[$otherTable];
					}
					$suggestedForeignKey = $this->_modelKey($model->name);
				}
				if (!empty($showKeys)) {
					$this->out(__('A helpful List of possible keys', true));
					for ($i = 0; $i < count($showKeys); $i++) {
						$this->out($i + 1 . ". " . $showKeys[$i]);
					}
					$foreignKey = $this->in(__('What is the foreignKey?', true), null, __("Enter a number", true));
					if (intval($foreignKey) > 0 && intval($foreignKey) <= $i ) {
						$foreignKey = $showKeys[intval($foreignKey) - 1];
					}
				}
				if (!isset($foreignKey)) {
					$foreignKey = $this->in(__('What is the foreignKey? Specify your own.', true), null, $suggestedForeignKey);
				}
				if ($assocType == '4') {
					$associationForeignKey = $this->in(__('What is the associationForeignKey?', true), null, $this->_modelKey($model->name));
					$joinTable = $this->in(__('What is the joinTable?', true));
				}
				$associations[$assocs[$assocType]] = array_values((array)$associations[$assocs[$assocType]]);
				$count = count($associations[$assocs[$assocType]]);
				$i = ($count > 0) ? $count : 0;
				$associations[$assocs[$assocType]][$i]['alias'] = $alias;
				$associations[$assocs[$assocType]][$i]['className'] = $className;
				$associations[$assocs[$assocType]][$i]['foreignKey'] = $foreignKey;
				if ($assocType == '4') {
					$associations[$assocs[$assocType]][$i]['associationForeignKey'] = $associationForeignKey;
					$associations[$assocs[$assocType]][$i]['joinTable'] = $joinTable;
				}
				$wannaDoMoreAssoc = $this->in(__('Define another association?', true), array('y','n'), 'y');
			}
		}
		return $associations;
	}

/**
 * Find belongsTo relations and add them to the associations list.
 *
 * @param object $model Model instance of model being generated.
 * @param array $associations Array of inprogress associations
 * @return array $associations with belongsTo added in.
 **/
	function findBelongsTo(&$model, $associations) {
		$fields = $model->schema();
		foreach ($fields as $fieldName => $field) {
			$offset = strpos($fieldName, '_id');
			if ($fieldName != $model->primaryKey && $offset !== false) {
				$tmpModelName = $this->_modelNameFromKey($fieldName);
				$associations['belongsTo'][] = array(
					'alias' => $tmpModelName,
					'className' => $tmpModelName,
					'foreignKey' => $fieldName,
				);
			}
		}
		return $associations;
	}

/**
 * Find the hasOne and HasMany relations and add them to associations list
 *
 * @param object $model Model instance being generated 
 * @param array $associations Array of inprogress associations
 * @return array $associations with hasOne and hasMany added in.
 **/
	function findHasOneAndMany(&$model, $associations) {
		$foreignKey = $this->_modelKey($model->name);
		foreach ($this->__tables as $otherTable) {
			$tempOtherModel = $this->_getModelObject($this->_modelName($otherTable));
			$modelFieldsTemp = $tempOtherModel->schema();

			$pattern = '/_' . preg_quote($model->table, '/') . '|' . preg_quote($model->table, '/') . '_/';
			$possibleJoinTable = preg_match($pattern , $otherTable);
			foreach ($modelFieldsTemp as $fieldName => $field) {
				if ($fieldName != $model->primaryKey && $fieldName == $foreignKey && $possibleJoinTable == false) {
					$assoc = array(
						'alias' => $tempOtherModel->name,
						'className' => $tempOtherModel->name,
						'foreignKey' => $fieldName
					);
					$associations['hasOne'][] = $assoc;
					$associations['hasMany'][] = $assoc;
				}
			}
		}
		return $associations;
	}

/**
 * Find the hasAndBelongsToMany relations and add them to associations list
 *
 * @param object $model Model instance being generated 
 * @param array $associations Array of inprogress associations
 * @return array $associations with hasAndBelongsToMany added in.
 **/
	function findHasAndBelongsToMany(&$model, $associations) {
		$foreignKey = $this->_modelKey($model->name);
		foreach ($this->__tables as $otherTable) {
			$tempOtherModel = $this->_getModelObject($this->_modelName($otherTable));
			$modelFieldsTemp = $tempOtherModel->schema();

			$offset = strpos($otherTable, $model->table . '_');
			$otherOffset = strpos($otherTable, '_' . $model->table);

			if ($offset !== false) {
				$offset = strlen($model->table . '_');
				$habtmName = $this->_modelName(substr($otherTable, $offset));
				$associations['hasAndBelongsToMany'][] = array(
					'alias' => $habtmName,
					'className' => $habtmName,
					'foreignKey' => $foreignKey,
					'associationForeignKey' => $this->_modelKey($habtmName),
					'joinTable' => $otherTable
				);
			} elseif ($otherOffset !== false) {
				$habtmName = $this->_modelName(substr($otherTable, 0, $otherOffset));
				$associations['hasAndBelongsToMany'][] = array(
					'alias' => $habtmName,
					'className' => $habtmName,
					'foreignKey' => $foreignKey,
					'associationForeignKey' => $this->_modelKey($habtmName),
					'joinTable' => $otherTable
				);
			}
		}
		return $associations;
	}

/**
 * Assembles and writes a Model file.
 *
 * @param mixed $name Model name or object
 * @param mixed $associations if array and $name is not an object assume Model associations array otherwise boolean interactive
 * @param array $validate Validation rules
 * @param string $primaryKey Primary key to use
 * @param string $useTable Table to use
 * @param string $useDbConfig Database configuration setting to use
 * @access private
 */
	function bake($name, $associations = array(),  $validate = array(), $primaryKey = 'id', $useTable = null, $useDbConfig = 'default') {

		if (is_object($name)) {
			if (!is_array($associations)) {
				$associations = $this->doAssociations($name, $associations);
				$validate = $this->doValidation($name);
			}
			$primaryKey = $name->primaryKey;
			$useTable = $name->table;
			$useDbConfig = $name->useDbConfig;
			$name = $name->name;
		}

		$out = "<?php\n";
		$out .= "class {$name} extends {$this->plugin}AppModel {\n\n";
		$out .= "\tvar \$name = '{$name}';\n";

		if ($useDbConfig !== 'default') {
			$out .= "\tvar \$useDbConfig = '$useDbConfig';\n";
		}

		if (($useTable && $useTable !== Inflector::tableize($name)) || $useTable === false) {
			$table = "'$useTable'";
			if (!$useTable) {
				$table = 'false';
			}
			$out .= "\tvar \$useTable = $table;\n";
		}

		if ($primaryKey !== 'id') {
			$out .= "\tvar \$primaryKey = '$primaryKey';\n";
		}

		$validateCount = count($validate);
		if (is_array($validate) && $validateCount > 0) {
			$out .= "\tvar \$validate = array(\n";
			$keys = array_keys($validate);
			for ($i = 0; $i < $validateCount; $i++) {
				$val = "'" . $validate[$keys[$i]] . "'";
				$out .= "\t\t'" . $keys[$i] . "' => array({$val})";
				if ($i + 1 < $validateCount) {
					$out .= ",";
				}
				$out .= "\n";
			}
			$out .= "\t);\n";
		}
		$out .= "\n";

		if (!empty($associations)) {
			if (!empty($associations['belongsTo']) || !empty($associations['hasOne']) || !empty($associations['hasMany']) || !empty($associations['hasAndBelongsToMany'])) {
				$out.= "\t//The Associations below have been created with all possible keys, those that are not needed can be removed\n";
			}

			if (!empty($associations['belongsTo'])) {
				$out .= "\tvar \$belongsTo = array(\n";
				$belongsToCount = count($associations['belongsTo']);

				for ($i = 0; $i < $belongsToCount; $i++) {
					$out .= "\t\t'{$associations['belongsTo'][$i]['alias']}' => array(\n";
					$out .= "\t\t\t'className' => '{$associations['belongsTo'][$i]['className']}',\n";
					$out .= "\t\t\t'foreignKey' => '{$associations['belongsTo'][$i]['foreignKey']}',\n";
					$out .= "\t\t\t'conditions' => '',\n";
					$out .= "\t\t\t'fields' => '',\n";
					$out .= "\t\t\t'order' => ''\n";
					$out .= "\t\t)";
					if ($i + 1 < $belongsToCount) {
						$out .= ",";
					}
					$out .= "\n";

				}
				$out .= "\t);\n\n";
			}

			if (!empty($associations['hasOne'])) {
				$out .= "\tvar \$hasOne = array(\n";
				$hasOneCount = count($associations['hasOne']);

				for ($i = 0; $i < $hasOneCount; $i++) {
					$out .= "\t\t'{$associations['hasOne'][$i]['alias']}' => array(\n";
					$out .= "\t\t\t'className' => '{$associations['hasOne'][$i]['className']}',\n";
					$out .= "\t\t\t'foreignKey' => '{$associations['hasOne'][$i]['foreignKey']}',\n";
					$out .= "\t\t\t'dependent' => false,\n";
					$out .= "\t\t\t'conditions' => '',\n";
					$out .= "\t\t\t'fields' => '',\n";
					$out .= "\t\t\t'order' => ''\n";
					$out .= "\t\t)";
					if ($i + 1 < $hasOneCount) {
						$out .= ",";
					}
					$out .= "\n";

				}
				$out .= "\t);\n\n";
			}

			if (!empty($associations['hasMany'])) {
				$out .= "\tvar \$hasMany = array(\n";
				$hasManyCount = count($associations['hasMany']);

				for ($i = 0; $i < $hasManyCount; $i++) {
					$out .= "\t\t'{$associations['hasMany'][$i]['alias']}' => array(\n";
					$out .= "\t\t\t'className' => '{$associations['hasMany'][$i]['className']}',\n";
					$out .= "\t\t\t'foreignKey' => '{$associations['hasMany'][$i]['foreignKey']}',\n";
					$out .= "\t\t\t'dependent' => false,\n";
					$out .= "\t\t\t'conditions' => '',\n";
					$out .= "\t\t\t'fields' => '',\n";
					$out .= "\t\t\t'order' => '',\n";
					$out .= "\t\t\t'limit' => '',\n";
					$out .= "\t\t\t'offset' => '',\n";
					$out .= "\t\t\t'exclusive' => '',\n";
					$out .= "\t\t\t'finderQuery' => '',\n";
					$out .= "\t\t\t'counterQuery' => ''\n";
					$out .= "\t\t)";
					if ($i + 1 < $hasManyCount) {
						$out .= ",";
					}
					$out .= "\n";
				}
				$out .= "\t);\n\n";
			}

			if (!empty($associations['hasAndBelongsToMany'])) {
				$out .= "\tvar \$hasAndBelongsToMany = array(\n";
				$hasAndBelongsToManyCount = count($associations['hasAndBelongsToMany']);

				for ($i = 0; $i < $hasAndBelongsToManyCount; $i++) {
					$out .= "\t\t'{$associations['hasAndBelongsToMany'][$i]['alias']}' => array(\n";
					$out .= "\t\t\t'className' => '{$associations['hasAndBelongsToMany'][$i]['className']}',\n";
					$out .= "\t\t\t'joinTable' => '{$associations['hasAndBelongsToMany'][$i]['joinTable']}',\n";
					$out .= "\t\t\t'foreignKey' => '{$associations['hasAndBelongsToMany'][$i]['foreignKey']}',\n";
					$out .= "\t\t\t'associationForeignKey' => '{$associations['hasAndBelongsToMany'][$i]['associationForeignKey']}',\n";
					$out .= "\t\t\t'unique' => true,\n";
					$out .= "\t\t\t'conditions' => '',\n";
					$out .= "\t\t\t'fields' => '',\n";
					$out .= "\t\t\t'order' => '',\n";
					$out .= "\t\t\t'limit' => '',\n";
					$out .= "\t\t\t'offset' => '',\n";
					$out .= "\t\t\t'finderQuery' => '',\n";
					$out .= "\t\t\t'deleteQuery' => '',\n";
					$out .= "\t\t\t'insertQuery' => ''\n";
					$out .= "\t\t)";
					if ($i + 1 < $hasAndBelongsToManyCount) {
						$out .= ",";
					}
					$out .= "\n";
				}
				$out .= "\t);\n\n";
			}
		}
		$out .= "}\n";
		$out .= "?>";
		$filename = $this->path . Inflector::underscore($name) . '.php';
		$this->out("\nBaking model class for $name...");
		return $this->createFile($filename, $out);
	}

/**
 * Assembles and writes a unit test file
 *
 * @param string $className Model class name
 * @access private
 */
	function bakeTest($className, $useTable = null, $associations = array()) {
		$this->fixture($className, $useTable);

		$fixtureInc = 'app';
		if ($this->plugin) {
			$fixtureInc = 'plugin.'.Inflector::underscore($this->plugin);
		}

		$fixture[] = "'{$fixtureInc}." . Inflector::underscore($className) ."'";

		if (!empty($associations)) {
			$assoc[] = Set::extract($associations, 'belongsTo.{n}.className');
			$assoc[] = Set::extract($associations, 'hasOne.{n}.className');
			$assoc[] = Set::extract($associations, 'hasMany.{n}.className');
			foreach ($assoc as $key => $value) {
				if (is_array($value)) {
					foreach ($value as $class) {
						$fixture[] = "'{$fixtureInc}." . Inflector::underscore($class) ."'";
					}
				}
			}
		}
		$fixture = join(", ", $fixture);

		$import = $className;
		if (isset($this->plugin)) {
			$import = $this->plugin . '.' . $className;
		}

		$out = "App::import('Model', '$import');\n\n";
		$out .= "class {$className}TestCase extends CakeTestCase {\n";
		$out .= "\tvar \${$className} = null;\n";
		$out .= "\tvar \$fixtures = array($fixture);\n\n";
		$out .= "\tfunction startTest() {\n";
		$out .= "\t\t\$this->{$className} =& ClassRegistry::init('{$className}');\n";
		$out .= "\t}\n\n";
		$out .= "\tfunction endTest() {\n";
		$out .= "\t\tunset(\$this->{$className});\n";
		$out .= "\t}\n\n";
		$out .= "\tfunction test{$className}Instance() {\n";
		$out .= "\t\t\$this->assertTrue(is_a(\$this->{$className}, '{$className}'));\n";
		$out .= "\t}\n\n";
		$out .= "}\n";

		$path = MODEL_TESTS;
		if (isset($this->plugin)) {
			$pluginPath = 'plugins' . DS . Inflector::underscore($this->plugin) . DS;
			$path = APP . $pluginPath . 'tests' . DS . 'cases' . DS . 'models' . DS;
		}

		$filename = Inflector::underscore($className).'.test.php';
		$this->out("\nBaking unit test for $className...");

		$header = '$Id';
		$content = "<?php \n/* SVN FILE: $header$ */\n/* ". $className ." Test cases generated on: " . date('Y-m-d H:m:s') . " : ". time() . "*/\n{$out}?>";
		return $this->createFile($path . $filename, $content);
	}

/**
 * outputs the a list of possible models or controllers from database
 *
 * @param string $useDbConfig Database configuration name
 * @access public
 */
	function listAll($useDbConfig = null, $interactive = true) {
		if (!isset($useDbConfig)) {
			$useDbConfig = $this->connection;
		}
		$db =& ConnectionManager::getDataSource($useDbConfig);
		$usePrefix = empty($db->config['prefix']) ? '' : $db->config['prefix'];
		if ($usePrefix) {
			$tables = array();
			foreach ($db->listSources() as $table) {
				if (!strncmp($table, $usePrefix, strlen($usePrefix))) {
					$tables[] = substr($table, strlen($usePrefix));
				}
			}
		} else {
			$tables = $db->listSources();
		}
		if (empty($tables)) {
			$this->err(__('Your database does not have any tables.', true));
			$this->_stop();
		}

		$this->__tables = $tables;

		if ($interactive === true) {
			$this->out(__('Possible Models based on your current database:', true));
			$this->_modelNames = array();
			$count = count($tables);
			for ($i = 0; $i < $count; $i++) {
				$this->_modelNames[] = $this->_modelName($tables[$i]);
				$this->out($i + 1 . ". " . $this->_modelNames[$i]);
			}
		}
		return $this->__tables;
	}
/**
 * Interact with the user to determine the table name of a particular model
 * 
 * @param string $modelName Name of the model you want a table for.
 * @param string $useDbConfig Name of the database config you want to get tables from.
 * @return void
 **/
	function getTable($modelName, $useDbConfig = null) {
		if (!isset($useDbConfig)) {
			$useDbConfig = $this->connection;
		}
		$db =& ConnectionManager::getDataSource($useDbConfig);
		$useTable = Inflector::tableize($modelName);
		$fullTableName = $db->fullTableName($useTable, false);
		$tableIsGood = false;

		if (array_search($useTable, $this->__tables) === false) {
			$this->out('');
			$this->out(sprintf(__("Given your model named '%s',\nCake would expect a database table named '%s'", true), $modelName, $fullTableName));
			$tableIsGood = $this->in(__('Do you want to use this table?', true), array('y','n'), 'y');
		}
		if (low($tableIsGood) == 'n' || low($tableIsGood) == 'no') {
			$useTable = $this->in(__('What is the name of the table (enter "null" to use NO table)?', true));
		}
		return $useTable;
	}
/**
 * Forces the user to specify the model he wants to bake, and returns the selected model name.
 *
 * @return string the model name
 * @access public
 */
	function getName($useDbConfig = null) {
		$this->listAll($useDbConfig);

		$enteredModel = '';

		while ($enteredModel == '') {
			$enteredModel = $this->in(__("Enter a number from the list above,\ntype in the name of another model, or 'q' to exit", true), null, 'q');

			if ($enteredModel === 'q') {
				$this->out(__("Exit", true));
				$this->_stop();
			}

			if ($enteredModel == '' || intval($enteredModel) > count($this->_modelNames)) {
				$this->err(__("The model name you supplied was empty,\nor the number you selected was not an option. Please try again.", true));
				$enteredModel = '';
			}
		}
		if (intval($enteredModel) > 0 && intval($enteredModel) <= count($this->_modelNames)) {
			$currentModelName = $this->_modelNames[intval($enteredModel) - 1];
		} else {
			$currentModelName = $enteredModel;
		}
		return $currentModelName;
	}

/**
 * Displays help contents
 *
 * @access public
 */
	function help() {
		$this->hr();
		$this->out("Usage: cake bake model <arg1>");
		$this->hr();
		$this->out('Commands:');
		$this->out("\n\tmodel\n\t\tbakes model in interactive mode.");
		$this->out("\n\tmodel <name>\n\t\tbakes model file with no associations or validation");
		$this->out("\n\tmodel all\n\t\tbakes all model files with associations and validation");
		$this->out("");
		$this->_stop();
	}

/**
 * Interact with FixtureTask to automatically bake fixtures when baking models.
 *
 * @return null.
 **/
	function fixture($className, $useTable = null) {
		$this->Fixture->connection = $this->connection;
		$this->Fixture->bake($className, $useTable);
	}
}
?>