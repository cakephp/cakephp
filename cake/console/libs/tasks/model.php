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
 * @var array
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
		if(count($connections) > 1) {
        	$useDbConfig = $this->in(__('Use Database Config', true) .':', $connections, 'default');
		}

		$currentModelName = $this->getName($useDbConfig);
		$db =& ConnectionManager::getDataSource($useDbConfig);
		$tableIsGood = false;
		$useTable = Inflector::tableize($currentModelName);
		$fullTableName = $db->fullTableName($useTable, false);
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
			$tempModel = new Model(false, $useTable, $useDbConfig);
			$modelFields = $db->describe($tempModel);

			if (!array_key_exists('id', $modelFields)) {
				foreach ($modelFields as $name => $field) {
					break;
				}
				$primaryKey = $this->in(__('What is the primaryKey?', true), null, $name);
			}
		}
		$validate = array();

		if (array_search($useTable, $this->__tables) !== false && (low($wannaDoValidation) == 'y' || low($wannaDoValidation) == 'yes')) {
			foreach ($modelFields as $fieldName => $field) {
				$this->out('');
				$prompt = 'Name: ' . $fieldName . "\n";
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

				if ($field['null'] == 1 || $fieldName == $primaryKey || $fieldName == 'created' || $fieldName == 'modified') {
					$validation = $this->in($prompt, null, '5');
				} else {
					$validation = $this->in($prompt, null, '1');
				}

				switch ($validation) {
					case '1':
						$validate[$fieldName] = 'VALID_NOT_EMPTY';
						break;
					case '2':
						$validate[$fieldName] = 'VALID_EMAIL';
						break;
					case '3':
						$validate[$fieldName] = 'VALID_NUMBER';
						break;
					case '4':
						$validate[$fieldName] = 'VALID_YEAR';
						break;
					case '5':
						break;
					default:
						$validate[$fieldName] = $validation;
					break;
				}
			}
		}

		$wannaDoAssoc = $this->in(__('Would you like to define model associations (hasMany, hasOne, belongsTo, etc.)?', true), array('y','n'), 'y');

		if ((low($wannaDoAssoc) == 'y' || low($wannaDoAssoc) == 'yes')) {
			$this->out(__('One moment while the associations are detected.', true));
			$possibleKeys = array();
			//Look for belongsTo
			$i = 0;
			foreach ($modelFields as $fieldName => $field) {
				$offset = strpos($fieldName, '_id');
				if ($fieldName != $primaryKey && $offset !== false) {
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
				$tempOtherModel = & new Model(false, $otherTable, $useDbConfig);
				$modelFieldsTemp = $db->describe($tempOtherModel);
				foreach ($modelFieldsTemp as $fieldName => $field) {
					if ($field['type'] == 'integer' || $field['type'] == 'string') {
						$possibleKeys[$otherTable][] = $fieldName;
					}
					if ($fieldName != $primaryKey && $fieldName == $this->_modelKey($currentModelName)) {
						$tmpModelName = $this->_modelName($otherTable);
						$associations['hasOne'][$j]['alias'] = $tmpModelName;
						$associations['hasOne'][$j]['className'] = $tmpModelName;
						$associations['hasOne'][$j]['foreignKey'] = $fieldName;

						$associations['hasMany'][$j]['alias'] = $tmpModelName;
						$associations['hasMany'][$j]['className'] = $tmpModelName;
						$associations['hasMany'][$j]['foreignKey'] = $fieldName;
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
							if ($currentModelName === $associations[$type][$i]['alias']) {
								$prompt = "{$currentModelName} {$type} {$associations[$type][$i]['alias']}\n";
								$prompt .= __("This looks like a self join. Please specify an alternate association alias.", true);
								$associations[$type][$i]['alias'] = $this->in($prompt, null, $associations[$type][$i]['alias']);
							} else {
								$prompt = "{$currentModelName} {$type} {$associations[$type][$i]['alias']}";
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
					$showKeys = $possibleKeys[$useTable];
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
					$suggestedForeignKey = $this->_modelKey($currentModelName);
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
					$associationForeignKey = $this->in(__('What is the associationForeignKey?', true), null, $this->_modelKey($currentModelName));
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
		$this->out('');
		$this->hr();
		$this->out(__('The following model will be created:', true));
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
 * @param string $name Model name
 * @param object $useDbConfig Database configuration setting to use
 * @param string $useTable Table to use
 * @param string $primaryKey Primary key to use
 * @param array $validate Validation rules
 * @param array $associations Model bindings
 * @access private
 */
	function __bake($name, $useDbConfig = 'default', $useTable = null, $primaryKey = 'id', $validate = array(), $associations = array()) {
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
			if(!empty($associations['belongsTo']) || !empty($associations['$hasOne']) || !empty($associations['hasMany']) || !empty($associations['hasAndBelongsToMany'])) {
				$out.= "\t//The Associations below have been created with all possible keys, those that are not needed can be removed\n";
			}

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
		$filename = $this->path . Inflector::underscore($name) . '.php';
		return $this->createFile($filename, $out);
	}

/**
 * Assembles and writes a unit test file
 *
 * @param string $className Model class name
 * @access private
 */
	function __bakeTest($className) {
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
		$this->out(__('Possible Models based on your current database:', true));
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