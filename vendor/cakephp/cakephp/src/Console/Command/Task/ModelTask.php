<?php
/**
 * The ModelTask handles creating and updating models files.
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
 * @since         CakePHP(tm) v 1.2
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Console\Command\Task;

use Cake\Console\Shell;
use Cake\Core\App;
use Cake\Database\ConnectionManager;
use Cake\Database\Schema\Collection;
use Cake\ORM\TableRegistry;
use Cake\ORM\Table;
use Cake\Utility\ClassRegistry;
use Cake\Utility\Inflector;
use Cake\Validation\Validation;

/**
 * Task class for creating and updating model files.
 *
 * @codingStandardsIgnoreFile
 */
class ModelTask extends BakeTask {

/**
 * path to Table directory
 *
 * @var string
 */
	public $path = null;

/**
 * tasks
 *
 * @var array
 */
	public $tasks = ['DbConfig', 'Fixture', 'Test', 'Template'];

/**
 * Tables to skip when running all()
 *
 * @var array
 */
	public $skipTables = ['i18n'];

/**
 * Holds tables found on connection.
 *
 * @var array
 */
	protected $_tables = [];

/**
 * Holds the model names
 *
 * @var array
 */
	protected $_modelNames = [];

/**
 * Holds validation method map.
 *
 * @var array
 */
	protected $_validations = [];

/**
 * Override initialize
 *
 * @return void
 */
	public function initialize() {
		$this->path = current(App::path('Model'.DS.'Repository'));
	}

/**
 * Execution method always used for tasks
 *
 * @return void
 */
	public function execute() {
		parent::execute();

		if (empty($this->args)) {
			$this->_interactive();
		}

		if (!empty($this->args[0])) {
			$this->interactive = false;
			if (!isset($this->connection)) {
				$this->connection = 'default';
			}
			if (strtolower($this->args[0]) === 'all') {
				return $this->all();
			}
			$model = $this->_modelName($this->args[0]);
			$this->listAll($this->connection);
			$useTable = $this->getTable($model);
			$object = $this->_getTableObject($model, $useTable);
			if ($this->bake($object, false)) {
				temp. disable until model bake works
				// if ($this->_checkUnitTest()) {
				// 	$this->bakeFixture($model, $useTable);
				// 	$this->bakeTest($model);
				// }
			// }
		}
	}

/**
 * Bake all models at once.
 *
 * @return void
 */
	public function all() {
		$this->listAll($this->connection, false);
		$unitTestExists = $this->_checkUnitTest();
		foreach ($this->_tables as $table) {
			if (in_array($table, $this->skipTables)) {
				continue;
			}
			$modelClass = Inflector::classify($table);
			$this->out(__d('cake_console', 'Baking %s', $modelClass));
			$object = $this->_getTableObject($modelClass, $table);
			if ($this->bake($object, false) && $unitTestExists) {
				$this->bakeFixture($modelClass, $table);
				$this->bakeTest($modelClass);
			}
		}
	}

/**
 * Get a model object for a class name.
 *
 * @param string $className Name of class you want model to be.
 * @param string $table Table name
 * @return Table Table instance
 */
	protected function _getTableObject($className, $table = null) {
		if (!$table) {
			$table = Inflector::tableize($className);
		}
		// throw new \Exception('Baking models does not work currently.');

		$object = TableRegistry::get($className, [
			'name' => $className,
			'table' => $table,
			'ds' => $this->connection
		]);
		$fields = $object->schema();
		foreach ($fields as $name => $field) {
			if (isset($field['key']) && $field['key'] === 'primary') {
				$object->primaryKey($name);
				break;
			}
		}
		return $object;
	}

/**
 * Generate a key value list of options and a prompt.
 *
 * @param array $options Array of options to use for the selections. indexes must start at 0
 * @param string $prompt Prompt to use for options list.
 * @param integer $default The default option for the given prompt.
 * @return integer Result of user choice.
 */
	public function inOptions($options, $prompt = null, $default = null) {
		$valid = false;
		$max = count($options);
		while (!$valid) {
			$len = strlen(count($options) + 1);
			foreach ($options as $i => $option) {
				$this->out(sprintf("%${len}d. %s", $i + 1, $option));
			}
			if (empty($prompt)) {
				$prompt = __d('cake_console', 'Make a selection from the choices above');
			}
			$choice = $this->in($prompt, null, $default);
			if (intval($choice) > 0 && intval($choice) <= $max) {
				$valid = true;
			}
		}
		return $choice - 1;
	}

/**
 * Handles interactive baking
 *
 * @return boolean
 */
	protected function _interactive() {
		$this->hr();
		$this->out(__d('cake_console', "Bake Model\nPath: %s", $this->getPath()));
		$this->hr();
		$this->interactive = true;

		$primaryKey = 'id';
		$validate = $associations = [];

		if (empty($this->connection)) {
			$this->connection = $this->DbConfig->getConfig();
		}
		// throw new \Exception('Baking models does not work yet.');

		$currentTableName = $this->getName();
		$useTable = $this->getTable($currentTableName);
		$db = ConnectionManager::getDataSource($this->connection);
		// $fullTableName = $this->_tables[$useTable];
		$fullTableName = $useTable; // temp.
		if (!in_array($useTable, $this->_tables)) {
			$prompt = __d('cake_console', "The table %s doesn't exist or could not be automatically detected\ncontinue anyway?", $useTable);
			$continue = $this->in($prompt, ['y', 'n']);
			if (strtolower($continue) === 'n') {
				return false;
			}
		}

		$tempTable = new Table(['alias' => $currentTableName, 'table' => $useTable, 'connection' => $db]);

		$knownToExist = false;
		try {
			$fields = $tempTable->schema();
			$knownToExist = true;
		} catch (\Exception $e) {
			$fields = [$tempTable->primaryKey()];
		}
		if (!array_key_exists('id', $fields->columns())) {
			$primaryKey = $this->findPrimaryKey($fields);
		}

		if ($knownToExist) {
			$displayField = $tempTable->hasAnyFields(['name', 'title']);
			if (!$displayField) {
				$displayField = $this->findDisplayField($tempTable->schema());
			}

			$prompt = __d('cake_console', "Would you like to supply validation criteria \nfor the fields in your model?");
			$wannaDoValidation = $this->in($prompt, ['y', 'n'], 'y');
			if (array_search($useTable, $this->_tables) !== false && strtolower($wannaDoValidation) === 'y') {
				$validate = $this->doValidation($tempTable);
			}

			$prompt = __d('cake_console', "Would you like to define model associations\n(hasMany, hasOne, belongsTo, etc.)?");
			$wannaDoAssoc = $this->in($prompt, ['y', 'n'], 'y');
			if (strtolower($wannaDoAssoc) === 'y') {
				$associations = $this->doAssociations($tempTable);
			}
		}

		$this->out();
		$this->hr();
		$this->out(__d('cake_console', 'The following Model will be created:'));
		$this->hr();
		$this->out(__d('cake_console', "Name:       %s", $currentTableName));

		if ($this->connection !== 'default') {
			$this->out(__d('cake_console', "DB Config:  %s", $this->connection));
		}
		if ($fullTableName !== Inflector::tableize($currentTableName)) {
			$this->out(__d('cake_console', 'DB Table:   %s', $fullTableName));
		}
		if ($primaryKey !== 'id') {
			$this->out(__d('cake_console', 'Primary Key: %s', $primaryKey));
		}
		if (!empty($validate)) {
			$this->out(__d('cake_console', 'Validation: %s', print_r($validate, true)));
		}
		if (!empty($associations)) {
			$this->out(__d('cake_console', 'Associations:'));
			$assocKeys = ['belongsTo', 'hasOne', 'hasMany', 'hasAndBelongsToMany'];
			foreach ($assocKeys as $assocKey) {
				$this->_printAssociation($currentTableName, $assocKey, $associations);
			}
		}

		$this->hr();
		$looksGood = $this->in(__d('cake_console', 'Look okay?'), ['y', 'n'], 'y');

		if (strtolower($looksGood) === 'y') {
			$vars = compact('associations', 'validate', 'primaryKey', 'useTable', 'displayField');
			$vars['useDbConfig'] = $this->connection;
			if ($this->bake($currentTableName, $vars)) {
				// temp. disable until model bakes well
				// if ($this->_checkUnitTest()) {
				// 	$this->bakeFixture($currentTableName, $useTable);
				// 	$this->bakeTest($currentTableName, $useTable, $associations);
				// }
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
 * @return void
 */
	protected function _printAssociation($modelName, $type, $associations) {
		if (!empty($associations[$type])) {
			for ($i = 0, $len = count($associations[$type]); $i < $len; $i++) {
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
 */
	public function findPrimaryKey($fields) {
		$name = 'id';
		foreach ($fields as $name => $field) {
			if (isset($field['key']) && $field['key'] === 'primary') {
				break;
			}
		}
		return $this->in(__d('cake_console', 'What is the primaryKey?'), null, $name);
	}

/**
 * interact with the user to find the displayField value for a model.
 *
 * @param array $fields Array of fields to look for and choose as a displayField
 * @return mixed Name of field to use for displayField or false if the user declines to choose
 */
	public function findDisplayField($fields) {
		$fieldNames = $fields->columns();
		$prompt = __d('cake_console', "A displayField could not be automatically detected\nwould you like to choose one?");
		$continue = $this->in($prompt, ['y', 'n']);
		if (strtolower($continue) === 'n') {
			return false;
		}
		$prompt = __d('cake_console', 'Choose a field from the options above:');
		$choice = $this->inOptions($fieldNames, $prompt);
		return $fieldNames[$choice];
	}

/**
 * Handles Generation and user interaction for creating validation.
 *
 * @param Table $model Table to have validations generated for.
 * @return array $validate Array of user selected validations.
 */
	public function doValidation($table) {
		if (!$table instanceof Table) {
			return false;
		}

		$fields = $table->schema()->columns();
		if (empty($fields)) {
			return false;
		}

		$skipFields = false;
		$validate = array();
		$this->initValidations();
		foreach ($fields as $fieldName) {
			$validation = $this->fieldValidation($fieldName, $table->schema()->column($fieldName), $table->primaryKey());
			if (isset($validation['_skipFields'])) {
				unset($validation['_skipFields']);
				$skipFields = true;
			}
			if (!empty($validation)) {
				$validate[$fieldName] = $validation;
			}
			if ($skipFields) {
				return $validate;
			}
		}
		return $validate;
	}

/**
 * Populate the _validations array
 *
 * @return void
 */
	public function initValidations() {
		$options = $choices = [];
		$options = get_class_methods(new Validation);
		sort($options);
		$default = 1;
		foreach ($options as $option) {
			if ($option{0} !== '_') {
				$choices[$default] = $option;
				$default++;
			}
		}
		$choices[$default] = 'none'; // Needed since index starts at 1
		$this->_validations = $choices;
		return $choices;
	}

/**
 * Does individual field validation handling.
 *
 * @param string $fieldName Name of field to be validated.
 * @param array $metaData metadata for field
 * @param string $primaryKey
 * @return array Array of validation for the field.
 */
	public function fieldValidation($fieldName, $metaData, $primaryKey = 'id') {
		$defaultChoice = count($this->_validations);
		$validate = $alreadyChosen = [];

		$prompt = __d('cake_console',
			"or enter in a valid regex validation string.\nAlternatively [s] skip the rest of the fields.\n"
		);
		$methods = array_flip($this->_validations);

		$anotherValidator = 'y';
		while ($anotherValidator === 'y') {
			if ($this->interactive) {
				$this->out();
				$this->out(__d('cake_console', 'Field: <info>%s</info>', $fieldName));
				$this->out(__d('cake_console', 'Type: <info>%s</info>', $metaData['type']));
				$this->hr();
				$this->out(__d('cake_console', 'Please select one of the following validation options:'));
				$this->hr();

				$optionText = '';
				for ($i = 1, $m = $defaultChoice / 2; $i <= $m; $i++) {
					$line = sprintf("%2d. %s", $i, $this->_validations[$i]);
					$optionText .= $line . str_repeat(" ", 31 - strlen($line));
					if ($m + $i !== $defaultChoice) {
						$optionText .= sprintf("%2d. %s\n", $m + $i, $this->_validations[$m + $i]);
					}
				}
				$this->out($optionText);
				$this->out(__d('cake_console', "%s - Do not do any validation on this field.", $defaultChoice));
				$this->hr();
			}

			$guess = $defaultChoice;
			if ($metaData['null'] != 1 && !in_array($fieldName, [$primaryKey, 'created', 'modified', 'updated'])) {
				if ($fieldName === 'email') {
					$guess = $methods['email'];
				} elseif ($metaData['type'] === 'string' && $metaData['length'] == 36) {
					$guess = $methods['uuid'];
				} elseif ($metaData['type'] === 'string') {
					$guess = $methods['notEmpty'];
				} elseif ($metaData['type'] === 'text') {
					$guess = $methods['notEmpty'];
				} elseif ($metaData['type'] === 'integer') {
					$guess = $methods['numeric'];
				} elseif ($metaData['type'] === 'float') {
					$guess = $methods['numeric'];
				} elseif ($metaData['type'] === 'boolean') {
					$guess = $methods['boolean'];
				} elseif ($metaData['type'] === 'date') {
					$guess = $methods['date'];
				} elseif ($metaData['type'] === 'time') {
					$guess = $methods['time'];
				} elseif ($metaData['type'] === 'datetime') {
					$guess = $methods['datetime'];
				} elseif ($metaData['type'] === 'inet') {
					$guess = $methods['ip'];
				}
			}

			if ($this->interactive === true) {
				$choice = $this->in($prompt, null, $guess);
				if ($choice === 's') {
					$validate['_skipFields'] = true;
					return $validate;
				}
				if (in_array($choice, $alreadyChosen)) {
					$this->out(__d('cake_console', "You have already chosen that validation rule,\nplease choose again"));
					continue;
				}
				if (!isset($this->_validations[$choice]) && is_numeric($choice)) {
					$this->out(__d('cake_console', 'Please make a valid selection.'));
					continue;
				}
				$alreadyChosen[] = $choice;
			} else {
				$choice = $guess;
			}

			if (isset($this->_validations[$choice])) {
				$validatorName = $this->_validations[$choice];
			} else {
				$validatorName = Inflector::slug($choice);
			}

			if ($choice != $defaultChoice) {
				$validate[$validatorName] = $choice;
				if (is_numeric($choice) && isset($this->_validations[$choice])) {
					$validate[$validatorName] = $this->_validations[$choice];
				}
			}
			$anotherValidator = 'n';
			if ($this->interactive && $choice != $defaultChoice) {
				$anotherValidator = $this->in(__d('cake_console', "Would you like to add another validation rule\n" .
					"or skip the rest of the fields?"), array('y', 'n', 's'), 'n');
				if ($anotherValidator === 's') {
					$validate['_skipFields'] = true;
					return $validate;
				}
			}
		}
		return $validate;
	}

/**
 * Handles associations
 *
 * @param Table $table
 * @return array Associations
 */
	public function doAssociations($table) {
		if (!$table instanceof Table) {
			return false;
		}
		if ($this->interactive === true) {
			$this->out(__d('cake_console', 'One moment while the associations are detected.'));
		}

		$fields = $table->schema();
		if (empty($fields)) {
			return [];
		}

		if (empty($this->_tables)) {
			$this->_tables = (array)$this->getAllTables();
		}

		$associations = [
			'belongsTo' => [],
			'hasMany' => [],
			'hasOne' => [],
			'hasAndBelongsToMany' => []
		];

		$associations = $this->findBelongsTo($table, $associations);
		$associations = $this->findHasOneAndMany($table, $associations);
		$associations = $this->findHasAndBelongsToMany($table, $associations);

		if ($this->interactive !== true) {
			unset($associations['hasOne']);
		}

		if ($this->interactive === true) {
			$this->hr();
			if (empty($associations)) {
				$this->out(__d('cake_console', 'None found.'));
			} else {
				$this->out(__d('cake_console', 'Please confirm the following associations:'));
				$this->hr();
				$associations = $this->confirmAssociations($table, $associations);
			}
			$associations = $this->doMoreAssociations($table, $associations);
		}
		return $associations;
	}

/**
 * Handles behaviors
 *
 * @param Table $model
 * @return array Behaviors
 */
	public function doActsAs($table) {
		if (!$table instanceof Table) {
			return false;
		}
		$behaviors = [];
		$fields = $table->schema();
		if (empty($fields)) {
			return [];
		}

		if (isset($fields['lft']) && $fields['lft']['type'] === 'integer' &&
			isset($fields['rght']) && $fields['rght']['type'] === 'integer' &&
			isset($fields['parent_id'])) {
			$behaviors[] = 'Tree';
		}
		return $behaviors;
	}

/**
 * Find belongsTo relations and add them to the associations list.
 *
 * @param Table $table Table instance of table being generated.
 * @param array $associations Array of in progress associations
 * @return array Associations with belongsTo added in.
 */
	public function findBelongsTo($table, $associations) {
		$fieldNames = $table->schema()->columns();
		foreach ($fieldNames as $fieldName) {
			$offset = strpos($fieldName, '_id');
			if ($fieldName != $table->primaryKey() && $fieldName !== 'parent_id' && $offset !== false) {
				$tmpTableName = $this->_modelNameFromKey($fieldName);
				$associations['belongsTo'][] = [
					'alias' => $tmpTableName,
					'className' => $tmpTableName,
					'foreignKey' => $fieldName,
				];
			} elseif ($fieldName === 'parent_id') {
				$associations['belongsTo'][] = [
					'alias' => 'Parent' . $table->name,
					'className' => $table->name,
					'foreignKey' => $fieldName,
				];
			}
		}
		return $associations;
	}

/**
 * Find the hasOne and hasMany relations and add them to associations list
 *
 * @param Table $table Table instance being generated
 * @param array $associations Array of in progress associations
 * @return array Associations with hasOne and hasMany added in.
 */
	public function findHasOneAndMany($table, $associations) {
		$foreignKey = $this->_modelKey($table->table());
		foreach ($this->_tables as $otherTable) {
			$tempOtherTable = $this->_getTableObject($this->_modelName($otherTable), $otherTable);
			$tempFieldNames = $tempOtherTable->schema();

			$pattern = '/_' . preg_quote($table->table(), '/') . '|' . preg_quote($table->table(), '/') . '_/';
			$possibleJoinTable = preg_match($pattern, $otherTable);
			if ($possibleJoinTable) {
				continue;
			}
			foreach ($tempFieldNames as $fieldName) {
				$assoc = false;
				if ($fieldName != $table->primaryKey() && $fieldName == $foreignKey) {
					$assoc = [
						'alias' => $tempOtherTable->name,
						'className' => $tempOtherTable->name,
						'foreignKey' => $fieldName
					];
				} elseif ($otherTable == $table->table() && $fieldName === 'parent_id') {
					$assoc = [
						'alias' => 'Child' . $table->name,
						'className' => $table->name,
						'foreignKey' => $fieldName
					];
				}
				if ($assoc) {
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
 * @param Table $model Table instance being generated
 * @param array $associations Array of in-progress associations
 * @return array Associations with hasAndBelongsToMany added in.
 */
	public function findHasAndBelongsToMany($model, $associations) {
		$foreignKey = $this->_modelKey($model->table());
		foreach ($this->_tables as $otherTable) {
			$tableName = null;
			$offset = strpos($otherTable, $model->table() . '_');
			$otherOffset = strpos($otherTable, '_' . $model->table());

			if ($offset !== false) {
				$tableName = substr($otherTable, strlen($model->table() . '_'));
			} elseif ($otherOffset !== false) {
				$tableName = substr($otherTable, 0, $otherOffset);
			}
			if ($tableName && in_array($tableName, $this->_tables)) {
				$habtmName = $this->_modelName($tableName);
				$associations['hasAndBelongsToMany'][] = [
					'alias' => $habtmName,
					'className' => $habtmName,
					'foreignKey' => $foreignKey,
					'associationForeignKey' => $this->_modelKey($habtmName),
					'joinTable' => $otherTable
				];
			}
		}
		return $associations;
	}

/**
 * Interact with the user and confirm associations.
 *
 * @param array $model Temporary Table instance.
 * @param array $associations Array of associations to be confirmed.
 * @return array Array of confirmed associations
 */
	public function confirmAssociations($model, $associations) {
		foreach ($associations as $type => $settings) {
			if (!empty($associations[$type])) {
				foreach ($associations[$type] as $i => $assoc) {
					$prompt = $model->table() . " {$type} {$assoc['alias']}?";
					$response = $this->in($prompt, ['y', 'n'], 'y');

					if (strtolower($response) === 'n') {
						unset($associations[$type][$i]);
					} elseif ($type === 'hasMany') {
						unset($associations['hasOne'][$i]);
					}
				}
				$associations[$type] = array_merge($associations[$type]);
			}
		}
		return $associations;
	}

/**
 * Interact with the user and generate additional non-conventional associations
 *
 * @param Table $model Temporary model instance
 * @param array $associations Array of associations.
 * @return array Array of associations.
 */
	public function doMoreAssociations($model, $associations) {
		$prompt = __d('cake_console', 'Would you like to define some additional model associations?');
		$wannaDoMoreAssoc = $this->in($prompt, ['y', 'n'], 'n');
		$possibleKeys = $this->_generatePossibleKeys();
		while (strtolower($wannaDoMoreAssoc) === 'y') {
			$assocs = ['belongsTo', 'hasOne', 'hasMany', 'hasAndBelongsToMany'];
			$this->out(__d('cake_console', 'What is the association type?'));
			$assocType = intval($this->inOptions($assocs, __d('cake_console', 'Enter a number')));

			$this->out(__d('cake_console', "For the following options be very careful to match your setup exactly.\n" .
				"Any spelling mistakes will cause errors."));
			$this->hr();

			$alias = $this->in(__d('cake_console', 'What is the alias for this association?'));
			$className = $this->in(__d('cake_console', 'What className will %s use?', $alias), null, $alias);

			if ($assocType === 0) {
				if (!empty($possibleKeys[$model->table()])) {
					$showKeys = $possibleKeys[$model->table()];
				} else {
					$showKeys = null;
				}
				$suggestedForeignKey = $this->_modelKey($alias);
			} else {
				$otherTable = Inflector::tableize($className);
				if (in_array($otherTable, $this->_tables)) {
					if ($assocType < 3) {
						if (!empty($possibleKeys[$otherTable])) {
							$showKeys = $possibleKeys[$otherTable];
						} else {
							$showKeys = null;
						}
					} else {
						$showKeys = null;
					}
				} else {
					$otherTable = $this->in(__d('cake_console', 'What is the table for this model?'));
					$showKeys = $possibleKeys[$otherTable];
				}
				$suggestedForeignKey = $this->_modelKey($model->name);
			}
			if (!empty($showKeys)) {
				$this->out(__d('cake_console', 'A helpful List of possible keys'));
				$foreignKey = $this->inOptions($showKeys, __d('cake_console', 'What is the foreignKey?'));
				$foreignKey = $showKeys[intval($foreignKey)];
			}
			if (!isset($foreignKey)) {
				$foreignKey = $this->in(__d('cake_console', 'What is the foreignKey? Specify your own.'), null, $suggestedForeignKey);
			}
			if ($assocType === 3) {
				$associationForeignKey = $this->in(__d('cake_console', 'What is the associationForeignKey?'), null, $this->_modelKey($model->name));
				$joinTable = $this->in(__d('cake_console', 'What is the joinTable?'));
			}
			$associations[$assocs[$assocType]] = array_values((array)$associations[$assocs[$assocType]]);
			$count = count($associations[$assocs[$assocType]]);
			$i = ($count > 0) ? $count : 0;
			$associations[$assocs[$assocType]][$i]['alias'] = $alias;
			$associations[$assocs[$assocType]][$i]['className'] = $className;
			$associations[$assocs[$assocType]][$i]['foreignKey'] = $foreignKey;
			if ($assocType === 3) {
				$associations[$assocs[$assocType]][$i]['associationForeignKey'] = $associationForeignKey;
				$associations[$assocs[$assocType]][$i]['joinTable'] = $joinTable;
			}
			$wannaDoMoreAssoc = $this->in(__d('cake_console', 'Define another association?'), ['y', 'n'], 'y');
		}
		return $associations;
	}

/**
 * Finds all possible keys to use on custom associations.
 *
 * @return array Array of tables and possible keys
 */
	protected function _generatePossibleKeys() {
		$possible = [];
		foreach ($this->_tables as $otherTable) {
			$tempOtherTable = new Table(['table' => $otherTable, 'ds' => $this->connection]);
			$modelFieldsTemp = $tempOtherTable->schema();
			foreach ($modelFieldsTemp as $fieldName => $field) {
				if ($field['type'] === 'integer' || $field['type'] === 'string') {
					$possible[$otherTable][] = $fieldName;
				}
			}
		}
		return $possible;
	}

/**
 * Assembles and writes a Table file.
 *
 * @param string|object $name Table name or object
 * @param array|boolean $data if array and $name is not an object assume bake data, otherwise boolean.
 * @return string
 */
	public function bake($name, $data = []) {
		if ($name instanceof Table) {
			if (!$data) {
				$data = [];
				$data['associations'] = $this->doAssociations($name);
				$data['validate'] = $this->doValidation($name);
				$data['actsAs'] = $this->doActsAs($name);
			}
			$data['primaryKey'] = $name->primaryKey();
			$data['useTable'] = $name->table;
			$data['useDbConfig'] = $name->useDbConfig;
			$data['name'] = $name = $name->name;
		} else {
			$data['name'] = $name;
		}
		$data['className'] = $className = Inflector::pluralize($name) . 'Table';

		$defaults = [
			'associations' => [],
			'actsAs' => [],
			'validate' => [],
			'primaryKey' => 'id',
			'useTable' => null,
			'useDbConfig' => 'default',
			'displayField' => null
		];
		$data = array_merge($defaults, $data);

		$pluginPath = '';
		if ($this->plugin) {
			$pluginPath = $this->plugin . '.';
		}

		$this->Template->set($data);
		$this->Template->set([
			'plugin' => $this->plugin,
			'pluginPath' => $pluginPath
		]);
		$out = $this->Template->generate('classes', 'model');

		$path = $this->getPath();
		$filename = $path . $className . '.php';
		$this->out("\n" . __d('cake_console', 'Baking model class for %s...', $className), 1, Shell::QUIET);
		$this->createFile($filename, $out);
		TableRegistry::clear();
		return $out;
	}

/**
 * Assembles and writes a unit test file
 *
 * @param string $className Table class name
 * @return string
 */
	public function bakeTest($className) {
		$this->Test->interactive = $this->interactive;
		$this->Test->plugin = $this->plugin;
		$this->Test->connection = $this->connection;
		return $this->Test->bake('Table', $className);
	}

/**
 * outputs the a list of possible models or controllers from database
 *
 * @param string $useDbConfig Database configuration name
 * @return array
 */
	public function listAll($useDbConfig = null) {
		$this->_tables = $this->getAllTables($useDbConfig);

		$this->_modelNames = [];
		$count = count($this->_tables);
		for ($i = 0; $i < $count; $i++) {
			$this->_modelNames[] = $this->_modelName($this->_tables[$i]);
		}
		if ($this->interactive === true) {
			$this->out(__d('cake_console', 'Possible Tables based on your current database:'));
			$len = strlen($count + 1);
			for ($i = 0; $i < $count; $i++) {
				$this->out(sprintf("%${len}d. %s", $i + 1, $this->_modelNames[$i]));
			}
		}
		return $this->_tables;
	}

/**
 * Interact with the user to determine the table name of a particular model
 *
 * @param string $modelName Name of the model you want a table for.
 * @param string $useDbConfig Name of the database config you want to get tables from.
 * @return string Table name
 */
	public function getTable($modelName, $useDbConfig = null) {
		$useTable = Inflector::tableize($modelName);
		if (in_array($modelName, $this->_modelNames)) {
			$modelNames = array_flip($this->_modelNames);
			$useTable = $this->_tables[$modelNames[$modelName]];
		}

		if ($this->interactive === true) {
			if (!isset($useDbConfig)) {
				$useDbConfig = $this->connection;
			}
			$db = ConnectionManager::getDataSource($useDbConfig);
			// $fullTableName = $this->_tables[$useTable];
			$fullTableName = $useTable; // temp.
			$tableIsGood = false;
			if (array_search($useTable, $this->_tables) === false) {
				$this->out();
				$this->out(__d('cake_console', "Given your model named '%s',\nCake would expect a database table named '%s'", $modelName, $fullTableName));
				$tableIsGood = $this->in(__d('cake_console', 'Do you want to use this table?'), ['y', 'n'], 'y');
			}
			if (strtolower($tableIsGood) === 'n') {
				$useTable = $this->in(__d('cake_console', 'What is the name of the table?'));
			}
		}
		return $useTable;
	}

/**
 * Get an Array of all the tables in the supplied connection
 * will halt the script if no tables are found.
 *
 * @param string $useDbConfig Connection name to scan.
 * @return array Array of tables in the database.
 */
	public function getAllTables($useDbConfig = null) {
		if (!isset($useDbConfig)) {
			$useDbConfig = $this->connection;
		}

		$tables = [];
		$db = ConnectionManager::getDataSource($useDbConfig);
		$db->cacheSources = false;
		$usePrefix = empty($db->config['prefix']) ? '' : $db->config['prefix'];
		$collection = new Collection($db);
		if ($usePrefix) {
			foreach ($db->listSources() as $table) {
				if (!strncmp($table, $usePrefix, strlen($usePrefix))) {
					$tables[] = substr($table, strlen($usePrefix));
				}
			}
		} else {
			$tables = $collection->listTables();
		}
		if (empty($tables)) {
			$this->err(__d('cake_console', 'Your database does not have any tables.'));
			return $this->_stop();
		}
		sort($tables);
		return $tables;
	}

/**
 * Forces the user to specify the model he wants to bake, and returns the selected model name.
 *
 * @param string $useDbConfig Database config name
 * @return string The model name
 */
	public function getName($useDbConfig = null) {
		$this->listAll($useDbConfig);

		$enteredTable = '';

		while (!$enteredTable) {
			$enteredTable = $this->in(__d('cake_console', "Enter a number from the list above,\n" .
				"type in the name of another model, or 'q' to exit"), null, 'q');

			if ($enteredTable === 'q') {
				$this->out(__d('cake_console', 'Exit'));
				return $this->_stop();
			}

			if (!$enteredTable || intval($enteredTable) > count($this->_modelNames)) {
				$this->err(__d('cake_console', "The model name you supplied was empty,\n" .
					"or the number you selected was not an option. Please try again."));
				$enteredTable = '';
			}
		}
		if (intval($enteredTable) > 0 && intval($enteredTable) <= count($this->_modelNames)) {
			return $this->_modelNames[intval($enteredTable) - 1];
		}

		return $enteredTable;
	}

/**
 * Gets the option parser instance and configures it.
 *
 * @return ConsoleOptionParser
 */
	public function getOptionParser() {
		$parser = parent::getOptionParser();

		$parser->description(
			__d('cake_console', 'Bake models.')
		)->addArgument('name', [
			'help' => __d('cake_console', 'Name of the model to bake. Can use Plugin.name to bake plugin models.')
		])->addSubcommand('all', [
			'help' => __d('cake_console', 'Bake all model files with associations and validation.')
		])->addOption('plugin', [
			'short' => 'p',
			'help' => __d('cake_console', 'Plugin to bake the model into.')
		])->addOption('theme', [
			'short' => 't',
			'help' => __d('cake_console', 'Theme to use when baking code.')
		])->addOption('connection', [
			'short' => 'c',
			'help' => __d('cake_console', 'The connection the model table is on.')
		])->addOption('force', [
			'short' => 'f',
			'help' => __d('cake_console', 'Force overwriting existing files without prompting.')
		])->epilog(
			__d('cake_console', 'Omitting all arguments and options will enter into an interactive mode.')
		);

		return $parser;
	}

/**
 * Interact with FixtureTask to automatically bake fixtures when baking models.
 *
 * @param string $className Name of class to bake fixture for
 * @param string $useTable Optional table name for fixture to use.
 * @return void
 * @see FixtureTask::bake
 */
	public function bakeFixture($className, $useTable = null) {
		$this->Fixture->interactive = $this->interactive;
		$this->Fixture->connection = $this->connection;
		$this->Fixture->plugin = $this->plugin;
		$this->Fixture->bake($className, $useTable);
	}

}
