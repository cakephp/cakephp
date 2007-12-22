<?php
/* SVN FILE: $Id$ */
/**
 * The ModelTask handles creating and updating models files.
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
 * @subpackage		cake.cake.console.libs.tasks
 * @since			CakePHP(tm) v 1.2
 * @version			$Revision$
 * @modifiedby		$LastChangedBy$
 * @lastmodified	$Date$
 * @license			http://www.opensource.org/licenses/mit-license.php The MIT License
 */
/**
 * Task class for creating and updating model files.
 *
 * @package		cake
 * @subpackage	cake.cake.console.libs.tasks
 */
class ModelTask extends Shell {
/**
 * path to MODELS directory
 *
 * @var string
 * @access public
 */
	var $path = MODELS;
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
			$model = $this->args[0];
			if ($this->bake($model)) {
				if ($this->_checkUnitTest()) {
					$this->bakeTest($model);
				}
			}
		}
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

		$useTable = null;
		$primaryKey = 'id';
		$validate = array();
		$associations = array('belongsTo'=> array(), 'hasOne'=> array(), 'hasMany' => array(), 'hasAndBelongsToMany'=> array());

		$useDbConfig = 'default';
		$connections = array_keys(get_class_vars('DATABASE_CONFIG'));
		if (count($connections) > 1) {
        	$useDbConfig = $this->in(__('Use Database Config', true) .':', $connections, 'default');
		}

		$currentModelName = $this->getName($useDbConfig);
		$db =& ConnectionManager::getDataSource($useDbConfig);
		$useTable = Inflector::tableize($currentModelName);
		$fullTableName = $db->fullTableName($useTable, false);
		$tableIsGood = false;

		if (array_search($useTable, $this->__tables) === false) {
			$this->out('');
			$this->out(sprintf(__("Given your model named '%s', Cake would expect a database table named %s", true), $currentModelName, $fullTableName));
			$tableIsGood = $this->in(__('Do you want to use this table?', true), array('y','n'), 'y');
		}

		if (low($tableIsGood) == 'n' || low($tableIsGood) == 'no') {
			$useTable = $this->in(__('What is the name of the table (enter "null" to use NO table)?', true));
		}

		while ($tableIsGood == false && low($useTable) != 'null') {
			if (is_array($this->__tables) && !in_array($useTable, $this->__tables)) {
				$fullTableName = $db->fullTableName($useTable, false);
				$this->out($fullTableName . ' does not exist.');
				$useTable = $this->in(__('What is the name of the table (enter "null" to use NO table)?', true));
				$tableIsGood = false;
			} else {
				$tableIsGood = true;
			}
		}

		$wannaDoValidation = $this->in(__('Would you like to supply validation criteria for the fields in your model?', true), array('y','n'), 'y');

		if (in_array($useTable, $this->__tables)) {
			App::import('Model');
			$tempModel = new Model(array('name' => $currentModelName, 'table' => $useTable, 'ds' => $useDbConfig));

			$fields = $tempModel->schema();
			if (!array_key_exists('id', $fields)) {
				foreach ($fields as $name => $field) {
					if (isset($field['key']) && $field['key'] == 'primary') {
						break;
					}
				}
				$primaryKey = $this->in(__('What is the primaryKey?', true), null, $name);
			}
		}

		if (array_search($useTable, $this->__tables) !== false && (low($wannaDoValidation) == 'y' || low($wannaDoValidation) == 'yes')) {
			$validate = $this->doValidation($tempModel);
		}

		$wannaDoAssoc = $this->in(__('Would you like to define model associations (hasMany, hasOne, belongsTo, etc.)?', true), array('y','n'), 'y');
		if ((low($wannaDoAssoc) == 'y' || low($wannaDoAssoc) == 'yes')) {
			$this->out(__('One moment while the associations are detected.', true));
			$associations = $this->doAssociations($tempModel);
		}

		$this->out('');
		$this->hr();
		$this->out(__('The following Model will be created:', true));
		$this->hr();
		$this->out("Name:       " . $currentModelName);
		$this->out("DB Config:  " . $useDbConfig);
		$this->out("DB Table:   " . $fullTableName);

		if ($primaryKey != 'id') {
			$this->out("Primary Key: " . $primaryKey);
		}
		$this->out("Validation: " . print_r($validate, true));

		if (!empty($associations)) {
			$this->out("Associations:");

			if (!empty($associations['belongsTo'])) {
				for ($i = 0; $i < count($associations['belongsTo']); $i++) {
					$this->out("			$currentModelName belongsTo {$associations['belongsTo'][$i]['alias']}");
				}
			}

			if (!empty($associations['hasOne'])) {
				for ($i = 0; $i < count($associations['hasOne']); $i++) {
					$this->out("			$currentModelName hasOne	{$associations['hasOne'][$i]['alias']}");
				}
			}

			if (!empty($associations['hasMany'])) {
				for ($i = 0; $i < count($associations['hasMany']); $i++) {
					$this->out("			$currentModelName hasMany	{$associations['hasMany'][$i]['alias']}");
				}
			}

			if (!empty($associations['hasAndBelongsToMany'])) {
				for ($i = 0; $i < count($associations['hasAndBelongsToMany']); $i++) {
					$this->out("			$currentModelName hasAndBelongsToMany {$associations['hasAndBelongsToMany'][$i]['alias']}");
				}
			}
		}
		$this->hr();
		$looksGood = $this->in(__('Look okay?', true), array('y','n'), 'y');

		if (low($looksGood) == 'y' || low($looksGood) == 'yes') {
			if ($this->bake($currentModelName, $associations, $validate, $primaryKey, $useTable, $useDbConfig)) {
				if ($this->_checkUnitTest()) {
					$this->bakeTest($currentModelName);
				}
			}
		} else {
			$this->out('Bake Aborted.');
		}
	}
/**
 * Handles associations
 *
 * @param object $model
 * @param boolean $interactive
 * @return array $validate
 * @access public
 */
	function doValidation(&$model, $interactive = true) {
		if (!is_object($model)) {
			return false;
		}
		$fields = $model->schema();

		if (empty($fields)) {
			return false;
		}

		$validate = array();
		$options = array('VALID_NOT_EMPTY', 'VALID_EMAIL', 'VALID_NUMER', 'VALID_YEAR');

		if (class_exists('Validation')) {
			$parent = get_class_methods(get_parent_class('Validation'));
			$options = array_diff(get_class_methods('Validation'), $parent);
			$methods = array_flip($options);
		}

		foreach ($fields as $fieldName => $field) {
			$prompt = 'Field: ' . $fieldName . "\n";
			$prompt .= 'Type: ' . $field['type'] . "\n";
			$prompt .= '---------------------------------------------------------------'."\n";
			$prompt .= 'Please select one of the following validation options:'."\n";
			$prompt .= '---------------------------------------------------------------'."\n";
			$skip = 1;
			foreach ($options as $key => $option) {
				if ($option{0} != '_') {
					$prompt .= "{$key} - {$option}\n";
					$skip++;
				}
			}
			$prompt .=  "{$skip} - Do not do any validation on this field.\n";
			$prompt .= "... or enter in a valid regex validation string.\n";

			$guess = $skip;
			if ($field['null'] != 1 && $fieldName != $model->primaryKey && !in_array($fieldName, array('created', 'modified', 'updated'))) {
				if ($fieldName == 'email') {
					$guess = $methods['email'];
				} elseif ($field['type'] == 'string') {
					$guess = $methods['alphaNumeric'];
				} elseif ($field['type'] == 'integer') {
					$guess = $methods['numeric'];
				} elseif ($field['type'] == 'boolean') {
					$guess = $methods['numeric'];
				} elseif ($field['type'] == 'datetime') {
					$guess = $methods['date'];
				}
			}

			if ($interactive === true) {
				$this->out('');
				$choice = $this->in($prompt, null, $guess);
			} else {
				$choice = $guess;
			}
			if ($choice !== $skip) {
				if (is_numeric($choice) && isset($options[$choice])) {
					$validate[$fieldName] = $options[$choice];
				} else {
					$validate[$fieldName] = $validation;
				}
			}
		}
		return $validate;
	}

/**
 * Handles associations
 *
 * @param object $model
 * @param boolean $interactive
 * @return array $assocaitons
 * @access public
 */
	function doAssociations(&$model, $interactive = true) {

		if (!is_object($model)) {
			return false;
		}

		$fields = $model->schema();

		if (empty($fields)) {
			return false;
		}

		$primaryKey = $model->primaryKey;
		$foreignKey = $this->_modelKey($model->name);

		$associations = $possibleKeys = array();

		//Look for belongsTo
		$i = 0;
		foreach ($fields as $fieldName => $field) {
			$offset = strpos($fieldName, '_id');
			if ($fieldName != $model->primaryKey && $offset !== false) {
				$tmpModelName = $this->_modelNameFromKey($fieldName);
				$associations['belongsTo'][$i]['alias'] = $tmpModelName;
				$associations['belongsTo'][$i]['className'] = $tmpModelName;
				$associations['belongsTo'][$i]['foreignKey'] = $fieldName;
				$i++;
			}
		}
		//Look for hasOne and hasMany and hasAndBelongsToMany
		$i = $j = 0;

		foreach ($this->__tables as $otherTable) {
			App::import('Model');
			$tmpModelName = $this->_modelName($otherTable);
			$tempOtherModel = & new Model(array('name' => $tmpModelName, 'table' => $otherTable, 'ds' => $model->useDbConfig));
			$modelFieldsTemp = $tempOtherModel->schema();

			$offset = strpos($otherTable, $model->table . '_');
			$otherOffset = strpos($otherTable, '_' . $model->table);

			foreach ($modelFieldsTemp as $fieldName => $field) {
				if ($field['type'] == 'integer' || $field['type'] == 'string') {
					$possibleKeys[$otherTable][] = $fieldName;
				}
				if ($fieldName != $model->primaryKey && $fieldName == $foreignKey && $offset === false && $otherOffset === false) {
					$associations['hasOne'][$j]['alias'] = $tempOtherModel->name;
					$associations['hasOne'][$j]['className'] = $tempOtherModel->name;
					$associations['hasOne'][$j]['foreignKey'] = $fieldName;

					$associations['hasMany'][$j]['alias'] = $tempOtherModel->name;
					$associations['hasMany'][$j]['className'] = $tempOtherModel->name;
					$associations['hasMany'][$j]['foreignKey'] = $fieldName;
					$j++;
				}
			}

			if ($offset !== false) {
				$offset = strlen($model->table . '_');
				$tmpModelName = $this->_modelName(substr($otherTable, $offset));
				$associations['hasAndBelongsToMany'][$i]['alias'] = $tmpModelName;
				$associations['hasAndBelongsToMany'][$i]['className'] = $tmpModelName;
				$associations['hasAndBelongsToMany'][$i]['foreignKey'] = $foreignKey;
				$associations['hasAndBelongsToMany'][$i]['associationForeignKey'] = $this->_modelKey($tmpModelName);
				$associations['hasAndBelongsToMany'][$i]['joinTable'] = $otherTable;
				$i++;
			}

			if ($otherOffset !== false) {
				$tmpModelName = $this->_modelName(substr($otherTable, 0, $otherOffset));
				$associations['hasAndBelongsToMany'][$i]['alias'] = $tmpModelName;
				$associations['hasAndBelongsToMany'][$i]['className'] = $tmpModelName;
				$associations['hasAndBelongsToMany'][$i]['foreignKey'] = $foreignKey;
				$associations['hasAndBelongsToMany'][$i]['associationForeignKey'] = $this->_modelKey($tmpModelName);
				$associations['hasAndBelongsToMany'][$i]['joinTable'] = $otherTable;
				$i++;
			}
		}

		if ($interactive !== true) {
			unset($associations['hasOne']);
		}

		if ($interactive === true) {
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
							if ($model->name === $associations[$type][$i]['alias']) {
								$prompt = "{$model->name} {$type} {$associations[$type][$i]['alias']}\n";
								$prompt .= __("This looks like a self join. Please specify an alternate association alias.", true);
								$associations[$type][$i]['alias'] = $this->in($prompt, null, $associations[$type][$i]['alias']);
							} else {
								$prompt = "{$model->name} {$type} {$associations[$type][$i]['alias']}";
								$response = $this->in("{$prompt}?", array('y','n'), 'y');
							}
							if ('n' == low($response) || 'no' == low($response)) {
								unset($associations[$type][$i]);
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
				$associations[$assocs[$assocType]] = array_values($associations[$assocs[$assocType]]);
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
				$validate = $this->doValidation($name, $associations);
			}
			$primaryKey = $name->primaryKey;
			$useTable = $name->table;
			$useDbConfig = $name->useDbConfig;
			$name = $name->name;
		}

		$out = "<?php\n";
		$out .= "class {$name} extends AppModel {\n\n";
		$out .= "\tvar \$name = '{$name}';\n";

		if ($useDbConfig !== 'default') {
			$out .= "\tvar \$useDbConfig = '$useDbConfig';\n";
		}

		if ($useTable === Inflector::tableize($name)) {
			$out .= "\tvar \$useTable = '$useTable';\n";
		}

		if ($primaryKey !== 'id') {
			$out .= "\tvar \$primaryKey = '$primaryKey';\n";
		}

		$validateCount = count($validate);
		if ($validateCount  > 1) {
			$out .= "\tvar \$validate = array(\n";
			$keys = array_keys($validate);
			for ($i = 0; $i < $validateCount; $i++) {
				$out .= "\t\t'" . $keys[$i] . "' => array('" . $validate[$keys[$i]] . "')";
				if ($i + 1 < $validateCount) {
					$out .= ",";
				}
				$out .= "\n";
			}
			$out .= "\t);\n";
		}
		$out .= "\n";

		if (!empty($associations)) {
			if (!empty($associations['belongsTo']) || !empty($associations['$hasOne']) || !empty($associations['hasMany']) || !empty($associations['hasAndBelongsToMany'])) {
				$out.= "\t//The Associations below have been created with all possible keys, those that are not needed can be removed\n";
			}

			if (!empty($associations['belongsTo'])) {
				$out .= "\tvar \$belongsTo = array(\n";
				$belongsToCount = count($associations['belongsTo']);

				for ($i = 0; $i < $belongsToCount; $i++) {
					$out .= "\t\t\t'{$associations['belongsTo'][$i]['alias']}' => ";
					$out .= "array('className' => '{$associations['belongsTo'][$i]['className']}',\n";
					$out .= "\t\t\t\t\t\t\t\t'foreignKey' => '{$associations['belongsTo'][$i]['foreignKey']}',\n";
					$out .= "\t\t\t\t\t\t\t\t'conditions' => '',\n";
					$out .= "\t\t\t\t\t\t\t\t'fields' => '',\n";
					$out .= "\t\t\t\t\t\t\t\t'order' => ''\n";
					$out .= "\t\t\t)";
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
					$out .= "\t\t\t'{$associations['hasOne'][$i]['alias']}' => ";
					$out .= "array('className' => '{$associations['hasOne'][$i]['className']}',\n";
					$out .= "\t\t\t\t\t\t\t\t'foreignKey' => '{$associations['hasOne'][$i]['foreignKey']}',\n";
					$out .= "\t\t\t\t\t\t\t\t'dependent' => false,\n";
					$out .= "\t\t\t\t\t\t\t\t'conditions' => '',\n";
					$out .= "\t\t\t\t\t\t\t\t'fields' => '',\n";
					$out .= "\t\t\t\t\t\t\t\t'order' => ''\n";
					$out .= "\t\t\t)";
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
					$out .= "\t\t\t'{$associations['hasMany'][$i]['alias']}' => ";
					$out .= "array('className' => '{$associations['hasMany'][$i]['className']}',\n";
					$out .= "\t\t\t\t\t\t\t\t'foreignKey' => '{$associations['hasMany'][$i]['foreignKey']}',\n";
					$out .= "\t\t\t\t\t\t\t\t'dependent' => false,\n";
					$out .= "\t\t\t\t\t\t\t\t'conditions' => '',\n";
					$out .= "\t\t\t\t\t\t\t\t'fields' => '',\n";
					$out .= "\t\t\t\t\t\t\t\t'order' => '',\n";
					$out .= "\t\t\t\t\t\t\t\t'limit' => '',\n";
					$out .= "\t\t\t\t\t\t\t\t'offset' => '',\n";
					$out .= "\t\t\t\t\t\t\t\t'exclusive' => '',\n";
					$out .= "\t\t\t\t\t\t\t\t'finderQuery' => '',\n";
					$out .= "\t\t\t\t\t\t\t\t'counterQuery' => ''\n";
					$out .= "\t\t\t)";
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
					$out .= "\t\t\t'{$associations['hasAndBelongsToMany'][$i]['alias']}' => ";
					$out .= "array('className' => '{$associations['hasAndBelongsToMany'][$i]['className']}',\n";
					$out .= "\t\t\t\t\t\t'joinTable' => '{$associations['hasAndBelongsToMany'][$i]['joinTable']}',\n";
					$out .= "\t\t\t\t\t\t'foreignKey' => '{$associations['hasAndBelongsToMany'][$i]['foreignKey']}',\n";
					$out .= "\t\t\t\t\t\t'associationForeignKey' => '{$associations['hasAndBelongsToMany'][$i]['associationForeignKey']}',\n";
					$out .= "\t\t\t\t\t\t'unique' => true,\n";
					$out .= "\t\t\t\t\t\t'conditions' => '',\n";
					$out .= "\t\t\t\t\t\t'fields' => '',\n";
					$out .= "\t\t\t\t\t\t'order' => '',\n";
					$out .= "\t\t\t\t\t\t'limit' => '',\n";
					$out .= "\t\t\t\t\t\t'offset' => '',\n";
					$out .= "\t\t\t\t\t\t'finderQuery' => '',\n";
					$out .= "\t\t\t\t\t\t'deleteQuery' => '',\n";
					$out .= "\t\t\t\t\t\t'insertQuery' => ''\n";
					$out .= "\t\t\t)";
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
		return $this->createFile($filename, $out);
	}

/**
 * Assembles and writes a unit test file
 *
 * @param string $className Model class name
 * @access private
 */
	function bakeTest($className) {
		$out = '<?php '."\n\n";
		$out .= "App::import('Model', '$className');\n\n";
		$out .= "class {$className}TestCase extends CakeTestCase {\n";
		$out .= "\tvar \$TestObject = null;\n\n";
		$out .= "\tfunction setUp() {\n\t\t\$this->TestObject = new {$className}();\n";
		$out .= "\t}\n\n\tfunction tearDown() {\n\t\tunset(\$this->TestObject);\n\t}\n";
		$out .= "\n\t/*\n\tfunction testMe() {\n";
		$out .= "\t\t\$result = \$this->TestObject->findAll();\n";
		$out .= "\t\t\$expected = 1;\n";
		$out .= "\t\t\$this->assertEqual(\$result, \$expected);\n\t}\n\t*/\n}";
		$out .= "\n?>";

		$path = MODEL_TESTS;
		$filename = Inflector::underscore($className).'.test.php';

		$this->out("Baking unit test for $className...");
		return $this->createFile($path . $filename, $out);
	}
/**
 * outputs the a list of possible models or controllers from database
 *
 * @param string $useDbConfig Database configuration name
 * @access public
 */
	function listAll($useDbConfig = 'default', $interactive = true) {
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
	}
/**
 * Forces the user to specify the model he wants to bake, and returns the selected model name.
 *
 * @return string the model name
 * @access public
 */
	function getName($useDbConfig) {
		$this->listAll($useDbConfig);

		$enteredModel = '';

		while ($enteredModel == '') {
			$enteredModel = $this->in(__('Enter a number from the list above, or type in the name of another model.', true));

			if ($enteredModel == '' || intval($enteredModel) > count($this->_modelNames)) {
				$this->err(__("The model name you supplied was empty, or the number you selected was not an option. Please try again.", true));
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
		$this->out("");
		exit();
	}
}
?>