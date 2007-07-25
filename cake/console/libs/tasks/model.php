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
 * Execution method always used for tasks
 *
 * @return void
 */
	function execute() {
		if (empty($this->args)) {
			$this->__interactive();
		}
		
		if (!empty($this->args[0])) {
			$model = $this->args[0];
			if ($this->__bake($model)) {
				if ($this->_checkUnitTest()) {
					$this->__bakeTest($model);
				}
			}			
		}
	}
/**
 * Handles interactive baking
 *
 * @access private
 * @return void
 */
	function __interactive() {
		$this->hr();
		$this->out('Model Bake:');
		$this->hr();
		$this->interactive = true;

		$useTable = null;
		$primaryKey = 'id';
		$validate = array();
		$associations = array('belongsTo'=> array(), 'hasOne'=> array(), 'hasMany', 'hasAndBelongsToMany'=> array());
		/*$usingDefault = $this->in('Will your model be using a database connection setting other than the default?');
		if (low($usingDefault) == 'y' || low($usingDefault) == 'yes')
		{
			$useDbConfig = $this->in('Please provide the name of the connection you wish to use.');
		}*/
		$useDbConfig = 'default';
		$currentModelName = $this->getName($useDbConfig);

		$db =& ConnectionManager::getDataSource($useDbConfig);
		$tableIsGood = false;
		$useTable = Inflector::tableize($currentModelName);
		$fullTableName = $db->fullTableName($useTable, false);
		if (array_search($useTable, $this->__tables) === false) {
			$this->out("\nGiven your model named '$currentModelName', Cake would expect a database table named '" . $fullTableName . "'.");
			$tableIsGood = $this->in('do you want to use this table?', array('y','n'), 'y');
		}

		if (low($tableIsGood) == 'n' || low($tableIsGood) == 'no') {
			$useTable = $this->in('What is the name of the table (enter "null" to use NO table)?');
		}
		while ($tableIsGood == false && low($useTable) != 'null') {
			if (is_array($this->__tables) && !in_array($useTable, $this->__tables)) {
				$fullTableName = $db->fullTableName($useTable, false);
				$this->out($fullTableName . ' does not exist.');
				$useTable = $this->in('What is the name of the table (enter "null" to use NO table)?');
				$tableIsGood = false;
			} else {
				$tableIsGood = true;
			}
		}
		$wannaDoValidation = $this->in('Would you like to supply validation criteria for the fields in your model?', array('y','n'), 'y');

		if (in_array($useTable, $this->__tables)) {
			loadModel();
			$tempModel = new Model(false, $useTable);
			$modelFields = $db->describe($tempModel);
			if (isset($modelFields[0]['name']) && $modelFields[0]['name'] != 'id') {
				$primaryKey = $this->in('What is the primaryKey?', null, $modelFields[0]['name']);
			}
		}
		$validate = array();

		if (array_search($useTable, $this->__tables) !== false && (low($wannaDoValidation) == 'y' || low($wannaDoValidation) == 'yes')) {
			foreach ($modelFields as $field) {
				$this->out('');
				$prompt = 'Name: ' . $field['name'] . "\n";
				$prompt .= 'Type: ' . $field['type'] . "\n";
				$prompt .= '---------------------------------------------------------------'."\n";
				$prompt .= 'Please select one of the following validation options:'."\n";
				$prompt .= '---------------------------------------------------------------'."\n";
				$prompt .= "1- VALID_NOT_EMPTY\n";
				$prompt .= "2- VALID_EMAIL\n";
				$prompt .= "3- VALID_NUMBER\n";
				$prompt .= "4- VALID_YEAR\n";
				$prompt .= "5- Do not do any validation on this field.\n\n";
				$prompt .= "... or enter in a valid regex validation string.\n\n";

				if ($field['null'] == 1 || $field['name'] == $primaryKey || $field['name'] == 'created' || $field['name'] == 'modified') {
					$validation = $this->in($prompt, null, '5');
				} else {
					$validation = $this->in($prompt, null, '1');
				}

				switch ($validation) {
					case '1':
						$validate[$field['name']] = 'VALID_NOT_EMPTY';
						break;
					case '2':
						$validate[$field['name']] = 'VALID_EMAIL';
						break;
					case '3':
						$validate[$field['name']] = 'VALID_NUMBER';
						break;
					case '4':
						$validate[$field['name']] = 'VALID_YEAR';
						break;
					case '5':
						break;
					default:
						$validate[$field['name']] = $validation;
					break;
				}
			}
		}

		$wannaDoAssoc = $this->in('Would you like to define model associations (hasMany, hasOne, belongsTo, etc.)?', array('y','n'), 'y');

		if ((low($wannaDoAssoc) == 'y' || low($wannaDoAssoc) == 'yes')) {
			$this->out('One moment while I try to detect any associations...');
			$possibleKeys = array();
			//Look for belongsTo
			$i = 0;
			foreach ($modelFields as $field) {
				$offset = strpos($field['name'], '_id');
				if ($field['name'] != $primaryKey && $offset !== false) {
					$tmpModelName = $this->_modelNameFromKey($field['name']);
					$associations['belongsTo'][$i]['alias'] = $tmpModelName;
					$associations['belongsTo'][$i]['className'] = $tmpModelName;
					$associations['belongsTo'][$i]['foreignKey'] = $field['name'];
					$i++;
				}
			}
			//Look for hasOne and hasMany and hasAndBelongsToMany
			$i = 0;
			$j = 0;
			foreach ($this->__tables as $otherTable) {
				$tempOtherModel = & new Model(false, $otherTable);
				$modelFieldsTemp = $db->describe($tempOtherModel);
				foreach ($modelFieldsTemp as $field) {
					if ($field['type'] == 'integer' || $field['type'] == 'string') {
						$possibleKeys[$otherTable][] = $field['name'];
					}
					if ($field['name'] != $primaryKey && $field['name'] == $this->_modelKey($currentModelName)) {
						$tmpModelName = $this->_modelName($otherTable);
						$associations['hasOne'][$j]['alias'] = $tmpModelName;
						$associations['hasOne'][$j]['className'] = $tmpModelName;
						$associations['hasOne'][$j]['foreignKey'] = $field['name'];

						$associations['hasMany'][$j]['alias'] = $tmpModelName;
						$associations['hasMany'][$j]['className'] = $tmpModelName;
						$associations['hasMany'][$j]['foreignKey'] = $field['name'];
						$j++;
					}
				}
				$offset = strpos($otherTable, $useTable . '_');
				if ($offset !== false) {
					$offset = strlen($useTable . '_');
					$tmpModelName = $this->_modelName(substr($otherTable, $offset));
					$associations['hasAndBelongsToMany'][$i]['alias'] = $tmpModelName;
					$associations['hasAndBelongsToMany'][$i]['className'] = $tmpModelName;
					$associations['hasAndBelongsToMany'][$i]['foreignKey'] = $this->_modelKey($currentModelName);
					$associations['hasAndBelongsToMany'][$i]['associationForeignKey'] = $this->_modelKey($tmpModelName);
					$associations['hasAndBelongsToMany'][$i]['joinTable'] = $otherTable;
					$i++;
				}
				$offset = strpos($otherTable, '_' . $useTable);
				if ($offset !== false) {
					$tmpModelName = $this->_modelName(substr($otherTable, 0, $offset));
					$associations['hasAndBelongsToMany'][$i]['alias'] = $tmpModelName;
					$associations['hasAndBelongsToMany'][$i]['className'] = $tmpModelName;
					$associations['hasAndBelongsToMany'][$i]['foreignKey'] = $this->_modelKey($currentModelName);
					$associations['hasAndBelongsToMany'][$i]['associationForeignKey'] = $this->_modelKey($tmpModelName);
					$associations['hasAndBelongsToMany'][$i]['joinTable'] = $otherTable;
					$i++;
				}
			}
			$this->out('Done.');
			$this->hr();
			//if none found...
			if (empty($associations)) {
				$this->out('None found.');
			} else {
				$this->out('Please confirm the following associations:');
				$this->hr();
				if (!empty($associations['belongsTo'])) {
					$count = count($associations['belongsTo']);
					for ($i = 0; $i < $count; $i++) {
						if ($currentModelName == $associations['belongsTo'][$i]['alias']) {
							$response = $this->in("{$currentModelName} belongsTo {$associations['belongsTo'][$i]['alias']}\nThis looks like a self join. Do you want to specify an alternate association alias?", array('y','n'), 'y');
							if ('y' == low($response) || 'yes' == low($response)) {
								$associations['belongsTo'][$i]['alias'] = $this->in("So what is the alias?", null, $associations['belongsTo'][$i]['alias']);
							}
							if ($currentModelName != $associations['belongsTo'][$i]['alias']) {
								$response = $this->in("$currentModelName belongsTo {$associations['belongsTo'][$i]['alias']}?", array('y','n'), 'y');
							} else {
								$response = 'n';
							}
						} else {
							$response = $this->in("$currentModelName belongsTo {$associations['belongsTo'][$i]['alias']}?", array('y','n'), 'y');
						}
						if ('n' == low($response) || 'no' == low($response)) {
							unset($associations['belongsTo'][$i]);
						}
					}
					$associations['belongsTo'] = array_merge($associations['belongsTo']);
				}

				if (!empty($associations['hasOne'])) {
					$count = count($associations['hasOne']);
					for ($i = 0; $i < $count; $i++) {
						if ($currentModelName == $associations['hasOne'][$i]['alias']) {
							$response = $this->in("{$currentModelName} hasOne {$associations['hasOne'][$i]['alias']}\nThis looks like a self join. Do you want to specify an alternate association alias?", array('y','n'), 'y');
							if ('y' == low($response) || 'yes' == low($response)) {
								$associations['hasOne'][$i]['alias'] = $this->in("So what is the alias?", null, $associations['hasOne'][$i]['alias']);
							}
							if ($currentModelName != $associations['hasOne'][$i]['alias']) {
								$response = $this->in("$currentModelName hasOne {$associations['hasOne'][$i]['alias']}?", array('y','n'), 'y');
							} else {
								$response = 'n';
							}
						} else {
							$response = $this->in("$currentModelName hasOne {$associations['hasOne'][$i]['alias']}?", array('y','n'), 'y');
						}
						if ('n' == low($response) || 'no' == low($response)) {
							unset($associations['hasOne'][$i]);
						}
					}
					$associations['hasOne'] = array_merge($associations['hasOne']);
				}

				if (!empty($associations['hasMany'])) {
					$count = count($associations['hasMany']);
					for ($i = 0; $i < $count; $i++) {
						if ($currentModelName == $associations['hasMany'][$i]['alias']) {
							$response = $this->in("{$currentModelName} hasMany {$associations['hasMany'][$i]['alias']}\nThis looks like a self join. Do you want to specify an alternate association alias?", array('y','n'), 'y');
							if ('y' == low($response) || 'yes' == low($response)) {
								$associations['hasMany'][$i]['alias'] = $this->in("So what is the alias?", null, $associations['hasMany'][$i]['alias']);
							}
							if ($currentModelName != $associations['hasMany'][$i]['alias']) {
								$response = $this->in("$currentModelName hasMany {$associations['hasMany'][$i]['alias']}?", array('y','n'), 'y');
							} else {
								$response = 'n';
							}
						} else {
							$response = $this->in("$currentModelName hasMany {$associations['hasMany'][$i]['alias']}?", array('y','n'), 'y');
						}
						if ('n' == low($response) || 'no' == low($response)) {
							unset($associations['hasMany'][$i]);
						}
					}
					$associations['hasMany'] = array_merge($associations['hasMany']);
				}

				if (!empty($associations['hasAndBelongsToMany'])) {
					$count = count($associations['hasAndBelongsToMany']);
					for ($i = 0; $i < $count; $i++) {
						if ($currentModelName == $associations['hasAndBelongsToMany'][$i]['alias']) {
							$response = $this->in("{$currentModelName} hasAndBelongsToMany {$associations['hasAndBelongsToMany'][$i]['alias']}\nThis looks like a self join. Do you want to specify an alternate association alias?", array('y','n'), 'y');
							if ('y' == low($response) || 'yes' == low($response)) {
								$associations['hasAndBelongsToMany'][$i]['alias'] = $this->in("So what is the alias?", null, $associations['hasAndBelongsToMany'][$i]['alias']);
							}
							if ($currentModelName != $associations['hasAndBelongsToMany'][$i]['alias']) {
								$response = $this->in("$currentModelName hasAndBelongsToMany {$associations['hasAndBelongsToMany'][$i]['alias']}?", array('y','n'), 'y');
							} else {
								$response = 'n';
							}
						} else {
							$response = $this->in("$currentModelName hasAndBelongsToMany {$associations['hasAndBelongsToMany'][$i]['alias']}?", array('y','n'), 'y');
						}
						if ('n' == low($response) || 'no' == low($response)) {
							unset($associations['hasAndBelongsToMany'][$i]);
						}
					}
					$associations['hasAndBelongsToMany'] = array_merge($associations['hasAndBelongsToMany']);
				}
			}
			$wannaDoMoreAssoc = $this->in('Would you like to define some additional model associations?', array('y','n'), 'n');

			while ((low($wannaDoMoreAssoc) == 'y' || low($wannaDoMoreAssoc) == 'yes')) {
				$assocs = array(1 => 'belongsTo', 2 => 'hasOne', 3 => 'hasMany', 4 => 'hasAndBelongsToMany');
				$bad = true;
				while ($bad) {
					$this->out('What is the association type?');
					$prompt = "1- belongsTo\n";
					$prompt .= "2- hasOne\n";
					$prompt .= "3- hasMany\n";
					$prompt .= "4- hasAndBelongsToMany\n";
					$assocType = intval($this->in($prompt, null, null));

					if (intval($assocType) < 1 || intval($assocType) > 4) {
						$this->out('The selection you entered was invalid. Please enter a number between 1 and 4.');
					} else {
						$bad = false;
					}
				}
				$this->out('For the following options be very careful to match your setup exactly. Any spelling mistakes will cause errors.');
				$this->hr();
				$associationName = $this->in('What is the name of this association?');
				$className = $this->in('What className will '.$associationName.' use?', null, $associationName );
				$suggestedForeignKey = null;
				if ($assocType == '1') {
					$showKeys = $possibleKeys[$useTable];
					$suggestedForeignKey = $this->_modelKey($associationName);
				} else {
					$otherTable = Inflector::tableize($className);
					if (in_array($otherTable, $this->__tables)) {
						if ($assocType < '4') {
							$showKeys = $possibleKeys[$otherTable];
						} else {
							$showKeys = null;
						}
					} else {
						$otherTable = $this->in('What is the table for this class?');
						$showKeys = $possibleKeys[$otherTable];
					}
					$suggestedForeignKey = $this->_modelKey($currentModelName);
				}
				if (!empty($showKeys)) {
					$this->out('A helpful List of possible keys');
					for ($i = 0; $i < count($showKeys); $i++) {
						$this->out($i + 1 . ". " . $showKeys[$i]);
					}
					$foreignKey = $this->in('What is the foreignKey? Choose a number.');
					if (intval($foreignKey) > 0 && intval($foreignKey) <= $i ) {
						$foreignKey = $showKeys[intval($foreignKey) - 1];
					}
				}
				if (!isset($foreignKey)) {
					$foreignKey = $this->in('What is the foreignKey? Specify your own.', null, $suggestedForeignKey);
				}
				if ($assocType == '4') {
					$associationForeignKey = $this->in('What is the associationForeignKey?', null, $this->_modelKey($currentModelName));
					$joinTable = $this->in('What is the joinTable?');
				}
				$associations[$assocs[$assocType]] = array_values($associations[$assocs[$assocType]]);
				$count = count($associations[$assocs[$assocType]]);
				$i = ($count > 0) ? $count : 0;
				$associations[$assocs[$assocType]][$i]['alias'] = $associationName;
				$associations[$assocs[$assocType]][$i]['className'] = $className;
				$associations[$assocs[$assocType]][$i]['foreignKey'] = $foreignKey;
				if ($assocType == '4') {
					$associations[$assocs[$assocType]][$i]['associationForeignKey'] = $associationForeignKey;
					$associations[$assocs[$assocType]][$i]['joinTable'] = $joinTable;
				}
				$wannaDoMoreAssoc = $this->in('Define another association?', array('y','n'), 'y');
			}
		}
		$this->out('');
		$this->hr();
		$this->out('The following model will be created:');
		$this->hr();
		$this->out("Model Name:	   $currentModelName");
		$this->out("DB Connection: " . $useDbConfig);
		$this->out("DB Table:	" . $fullTableName);
		if ($primaryKey != 'id') {
			$this->out("Primary Key:   " . $primaryKey);
		}
		$this->out("Validation:	   " . print_r($validate, true));

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
		$looksGood = $this->in('Look okay?', array('y','n'), 'y');

		if (low($looksGood) == 'y' || low($looksGood) == 'yes') {
			if ($useTable == Inflector::tableize($currentModelName)) {
				// set it to null...
				// putting $useTable in the model
				// is unnecessary.
				$useTable = null;
			}
			if ($this->__bake($currentModelName, $useDbConfig, $useTable, $primaryKey, $validate, $associations)) {
				if ($this->_checkUnitTest()) {
					$this->__bakeTest($currentModelName);
				}
			}
		} else {
			$this->out('Bake Aborted.');
		}
	}

/**
 * Assembles and writes a Model file.
 *
 * @param string $name
 * @param object $useDbConfig
 * @param string $useTable
 * @param string $primaryKey
 * @param array $validate
 * @param array $associations
 */
	function __bake($name, $useDbConfig = 'default', $useTable = null, $primaryKey = 'id', $validate = array(), $associations = array()) {
		$out = "<?php\n";
		$out .= "class {$name} extends AppModel {\n\n";
		$out .= "\tvar \$name = '{$name}';\n";

		if ($useDbConfig != 'default') {
			$out .= "\tvar \$useDbConfig = '$useDbConfig';\n";
		}

		if ($useTable != null) {
			$out .= "\tvar \$useTable = '$useTable';\n";
		}

		if ($primaryKey != 'id') {
			$out .= "\tvar \$primaryKey = '$primaryKey';\n";
		}


		if (count($validate)) {
			$out .= "\tvar \$validate = array(\n";
			$keys = array_keys($validate);
			for ($i = 0; $i < count($validate); $i++) {
				$out .= "\t\t'" . $keys[$i] . "' => " . $validate[$keys[$i]] . ",\n";
			}
			$out .= "\t);\n";
		}
		$out .= "\n";

		if (!empty($associations)) {
			$out.= "\t//The Associations below have been created with all possible keys, those that are not needed can be removed\n";
			if (!empty($associations['belongsTo'])) {
				$out .= "\tvar \$belongsTo = array(\n";

				for ($i = 0; $i < count($associations['belongsTo']); $i++) {
					$out .= "\t\t\t'{$associations['belongsTo'][$i]['alias']}' => ";
					$out .= "array('className' => '{$associations['belongsTo'][$i]['className']}',\n";
					$out .= "\t\t\t\t\t\t\t\t'foreignKey' => '{$associations['belongsTo'][$i]['foreignKey']}',\n";
					$out .= "\t\t\t\t\t\t\t\t'conditions' => '',\n";
					$out .= "\t\t\t\t\t\t\t\t'fields' => '',\n";
					$out .= "\t\t\t\t\t\t\t\t'order' => '',\n";
					$out .= "\t\t\t\t\t\t\t\t'counterCache' => ''";
					$out .= "),\n";
				}
				$out .= "\t);\n\n";
			}

			if (!empty($associations['hasOne'])) {
				$out .= "\tvar \$hasOne = array(\n";

				for ($i = 0; $i < count($associations['hasOne']); $i++) {
					$out .= "\t\t\t'{$associations['hasOne'][$i]['alias']}' => ";
					$out .= "array('className' => '{$associations['hasOne'][$i]['className']}',\n";
					$out .= "\t\t\t\t\t\t\t\t'foreignKey' => '{$associations['hasOne'][$i]['foreignKey']}',\n";
					$out .= "\t\t\t\t\t\t\t\t'conditions' => '',\n";
					$out .= "\t\t\t\t\t\t\t\t'fields' => '',\n";
					$out .= "\t\t\t\t\t\t\t\t'order' => '',\n";
					$out .= "\t\t\t\t\t\t\t\t'dependent' => ''";
					$out .= "),\n";
				}
				$out .= "\t);\n\n";
			}

			if (!empty($associations['hasMany'])) {
				$out .= "\tvar \$hasMany = array(\n";

				for ($i = 0; $i < count($associations['hasMany']); $i++) {
					$out .= "\t\t\t'{$associations['hasMany'][$i]['alias']}' => ";
					$out .= "array('className' => '{$associations['hasMany'][$i]['className']}',\n";
					$out .= "\t\t\t\t\t\t\t\t'foreignKey' => '{$associations['hasMany'][$i]['foreignKey']}',\n";
					$out .= "\t\t\t\t\t\t\t\t'conditions' => '',\n";
					$out .= "\t\t\t\t\t\t\t\t'fields' => '',\n";
					$out .= "\t\t\t\t\t\t\t\t'order' => '',\n";
					$out .= "\t\t\t\t\t\t\t\t'limit' => '',\n";
					$out .= "\t\t\t\t\t\t\t\t'offset' => '',\n";
					$out .= "\t\t\t\t\t\t\t\t'dependent' => '',\n";
					$out .= "\t\t\t\t\t\t\t\t'exclusive' => '',\n";
					$out .= "\t\t\t\t\t\t\t\t'finderQuery' => '',\n";
					$out .= "\t\t\t\t\t\t\t\t'counterQuery' => ''";
					$out .= "),\n";
				}
				$out .= "\t);\n\n";
			}

			if (!empty($associations['hasAndBelongsToMany'])) {
				$out .= "\tvar \$hasAndBelongsToMany = array(\n";

				for ($i = 0; $i < count($associations['hasAndBelongsToMany']); $i++) {
					$out .= "\t\t\t'{$associations['hasAndBelongsToMany'][$i]['alias']}' => ";
					$out .= "array('className' => '{$associations['hasAndBelongsToMany'][$i]['className']}',\n";
					$out .= "\t\t\t\t\t\t'joinTable' => '{$associations['hasAndBelongsToMany'][$i]['joinTable']}',\n";
					$out .= "\t\t\t\t\t\t'foreignKey' => '{$associations['hasAndBelongsToMany'][$i]['foreignKey']}',\n";
					$out .= "\t\t\t\t\t\t'associationForeignKey' => '{$associations['hasAndBelongsToMany'][$i]['associationForeignKey']}',\n";
					$out .= "\t\t\t\t\t\t'conditions' => '',\n";
					$out .= "\t\t\t\t\t\t'fields' => '',\n";
					$out .= "\t\t\t\t\t\t'order' => '',\n";
					$out .= "\t\t\t\t\t\t'limit' => '',\n";
					$out .= "\t\t\t\t\t\t'offset' => '',\n";
					$out .= "\t\t\t\t\t\t'unique' => '',\n";
					$out .= "\t\t\t\t\t\t'finderQuery' => '',\n";
					$out .= "\t\t\t\t\t\t'deleteQuery' => '',\n";
					$out .= "\t\t\t\t\t\t'insertQuery' => ''";
					$out .= "),\n";
				}
				$out .= "\t);\n\n";
			}
		}
		$out .= "}\n";
		$out .= "?>";
		$filename = MODELS . Inflector::underscore($name) . '.php';
		return $this->createFile($filename, $out);
	}

/**
 * Assembles and writes a unit test file.
 *
 * @param string $type One of "model", and "controller".
 * @param string $className
 */
	function __bakeTest($className) {
		$out = '<?php '."\n\n";
		$out .= "loadModel('$className');\n\n";
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
		$filename = $this->_singularName($className).'.test.php';

		$this->out("Baking unit test for $className...");
		$Folder =& new Folder($path, true);
		if ($path = $Folder->cd($path)) {
			$path = $Folder->slashTerm($path);
			return $this->createFile($path . $filename, $out);
		}
		return false;
	}

/**
 * outputs the a list of possible models or controllers from database
 *
 * @param string $useDbConfig
 * @param string $type = Models or Controllers
 * @return output
 */
	function listAll($useDbConfig = 'default') {
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
		$this->out('Possible Models based on your current database:');
		$this->_modelNames = array();
		$count = count($tables);
		for ($i = 0; $i < $count; $i++) {
			$this->_modelNames[] = $this->_modelName($tables[$i]);
			$this->out($i + 1 . ". " . $this->_modelNames[$i]);
		}
	}

/**
 * Forces the user to specify the model he wants to bake, and returns the selected model name.
 *
 * @return the model name
 */
	function getName($useDbConfig) {
		$this->listAll($useDbConfig);

		$enteredModel = '';

		while ($enteredModel == '') {
			$enteredModel = $this->in('Enter a number from the list above, or type in the name of another model.');

			if ($enteredModel == '' || intval($enteredModel) > count($this->_modelNames)) {
				$this->out('Error:');
				$this->out("The model name you supplied was empty, or the number \nyou selected was not an option. Please try again.");
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
 * @return void
 */
	function help() {
		$this->hr();
		$this->out("Usage: cake bake model");
		$this->hr();
		$this->out("this task is currently only run in interactive mode");
		$this->out("");
		exit();
	}
}
?>