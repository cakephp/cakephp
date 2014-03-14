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
 * @since         1.2.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Console\Command\Task;

use Cake\Console\Shell;
use Cake\Core\App;
use Cake\Datasource\ConnectionManager;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\Utility\ClassRegistry;
use Cake\Utility\Inflector;

/**
 * Task class for creating and updating model files.
 *
 * @codingStandardsIgnoreFile
 */
class ModelTask extends BakeTask {

/**
 * path to Model directory
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
		$this->path = current(App::path('Model'));
	}

/**
 * Execution method always used for tasks
 *
 * @return void
 */
	public function execute() {
		parent::execute();

		if (!isset($this->connection)) {
			$this->connection = 'default';
		}

		if (empty($this->args)) {
			$this->out(__d('cake_console', 'Choose a model to bake from the following:'));
			foreach ($this->listAll() as $table) {
				$this->out('- ' . $table);
			}
			return true;
		}

		if (strtolower($this->args[0]) === 'all') {
			return $this->all();
		}

		$model = $this->args[0];
		$table = $this->getTable();

		$object = $this->getTableObject($model, $table);
		$associations = $this->getAssociations($object);
		$primaryKey = $this->getPrimaryKey($model);
		$displayField = $this->getDisplayField($model);

		if ($this->bake($object, false)) {
			if ($this->_checkUnitTest()) {
				$this->bakeFixture($model, $useTable);
				$this->bakeTest($model);
			}
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
			$object = $this->_getModelObject($modelClass, $table);
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
 * @return Cake\ORM\Table Table instance
 */
	public function getTableObject($className, $table) {
		if (TableRegistry::exists($className)) {
			return TableRegistry::get($className);
		}
		return TableRegistry::get($className, [
			'name' => $className,
			'table' => $table,
			'connection' => ConnectionManager::get($this->connection)
		]);
	}

/**
 * Get the array of associations to generate.
 *
 * @return array
 */
	public function getAssociations(Table $table) {
		if (!empty($this->params['no-associations'])) {
			return [];
		}
		$assocs = [];
		$this->out(__d('cake_console', 'One moment while associations are detected.'));

		$this->listAll();

		$associations = [
			'belongsTo' => [],
			'hasMany' => [],
			'belongsToMany' => []
		];

		$associations = $this->findBelongsTo($table, $associations);
		$associations = $this->findHasMany($table, $associations);
		$associations = $this->findBelongsToMany($table, $associations);
		return $associations;
	}

/**
 * Find belongsTo relations and add them to the associations list.
 *
 * @param ORM\Table $table Database\Table instance of table being generated.
 * @param array $associations Array of in progress associations
 * @return array Associations with belongsTo added in.
 */
	public function findBelongsTo($model, $associations) {
		$schema = $model->schema();
		$primary = $schema->primaryKey();
		foreach ($schema->columns() as $fieldName) {
			$offset = strpos($fieldName, '_id');
			if (!in_array($fieldName, $primary) && $fieldName !== 'parent_id' && $offset !== false) {
				$tmpModelName = $this->_modelNameFromKey($fieldName);
				$associations['belongsTo'][] = [
					'alias' => $tmpModelName,
					'className' => $tmpModelName,
					'foreignKey' => $fieldName,
				];
			} elseif ($fieldName === 'parent_id') {
				$associations['belongsTo'][] = [
					'alias' => 'Parent' . $model->alias(),
					'className' => $model->alias(),
					'foreignKey' => $fieldName,
				];
			}
		}
		return $associations;
	}

/**
 * Find the hasMany relations and add them to associations list
 *
 * @param Model $model Model instance being generated
 * @param array $associations Array of in progress associations
 * @return array Associations with hasMany added in.
 */
	public function findHasMany($model, $associations) {
		$schema = $model->schema();
		$primaryKey = (array)$schema->primaryKey();
		$tableName = $schema->name();
		$foreignKey = $this->_modelKey($tableName);

		foreach ($this->listAll() as $otherTable) {
			$otherModel = $this->getTableObject($this->_modelName($otherTable), $otherTable);
			$otherSchema = $otherModel->schema();

			// Exclude habtm join tables.
			$pattern = '/_' . preg_quote($tableName, '/') . '|' . preg_quote($tableName, '/') . '_/';
			$possibleJoinTable = preg_match($pattern, $otherTable);
			if ($possibleJoinTable) {
				continue;
			}

			foreach ($otherSchema->columns() as $fieldName) {
				$assoc = false;
				if (!in_array($fieldName, $primaryKey) && $fieldName == $foreignKey) {
					$assoc = [
						'alias' => $otherModel->alias(),
						'className' => $otherModel->alias(),
						'foreignKey' => $fieldName
					];
				} elseif ($otherTable == $tableName && $fieldName === 'parent_id') {
					$assoc = [
						'alias' => 'Child' . $model->alias(),
						'className' => $model->alias(),
						'foreignKey' => $fieldName
					];
				}
				if ($assoc) {
					$associations['hasMany'][] = $assoc;
				}
			}
		}
		return $associations;
	}

/**
 * Find the BelongsToMany relations and add them to associations list
 *
 * @param Model $model Model instance being generated
 * @param array $associations Array of in-progress associations
 * @return array Associations with belongsToMany added in.
 */
	public function findBelongsToMany($model, $associations) {
		$schema = $model->schema();
		$primaryKey = (array)$schema->primaryKey();
		$tableName = $schema->name();
		$foreignKey = $this->_modelKey($tableName);

		$tables = $this->listAll();
		foreach ($tables as $otherTable) {
			$assocTable = null;
			$offset = strpos($otherTable, $tableName . '_');
			$otherOffset = strpos($otherTable, '_' . $tableName);

			if ($offset !== false) {
				$assocTable = substr($otherTable, strlen($tableName . '_'));
			} elseif ($otherOffset !== false) {
				$assocTable = substr($otherTable, 0, $otherOffset);
			}
			if ($assocTable && in_array($assocTable, $tables)) {
				$habtmName = $this->_modelName($assocTable);
				$associations['belongsToMany'][] = [
					'alias' => $habtmName,
					'className' => $habtmName,
					'foreignKey' => $foreignKey,
					'targetForeignKey' => $this->_modelKey($habtmName),
					'joinTable' => $otherTable
				];
			}
		}
		return $associations;
	}

/**
 * Get the display field from the model or parameters
 *
 * @param Cake\ORM\Table $model The model to introspect.
 * @return string
 */
	public function getDisplayField($model) {
		if (!empty($this->params['display-field'])) {
			return $this->params['display-field'];
		}
		return $model->displayField();
	}

/**
 * Get the primary key field from the model or parameters
 *
 * @param Cake\ORM\Table $model The model to introspect.
 * @return array The columns in the primary key
 */
	public function getPrimaryKey($model) {
		if (!empty($this->params['primary-key'])) {
			return (array)$this->params['primary-key'];
		}
		return (array)$model->primaryKey();
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
 * @throws \Exception Will throw this until baking models works
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
		throw new \Exception('Baking models does not work yet.');

		$currentModelName = $this->getName();
		$useTable = $this->getTable($currentModelName);
		$db = ConnectionManager::getDataSource($this->connection);
		$fullTableName = $db->fullTableName($useTable);
		if (!in_array($useTable, $this->_tables)) {
			$prompt = __d('cake_console', "The table %s doesn't exist or could not be automatically detected\ncontinue anyway?", $useTable);
			$continue = $this->in($prompt, ['y', 'n']);
			if (strtolower($continue) === 'n') {
				return false;
			}
		}

		$tempModel = new Model(['name' => $currentModelName, 'table' => $useTable, 'ds' => $this->connection]);

		$knownToExist = false;
		try {
			$fields = $tempModel->schema(true);
			$knownToExist = true;
		} catch (\Exception $e) {
			$fields = [$tempModel->primaryKey];
		}
		if (!array_key_exists('id', $fields)) {
			$primaryKey = $this->findPrimaryKey($fields);
		}

		if ($knownToExist) {
			$displayField = $tempModel->hasField(['name', 'title']);
			if (!$displayField) {
				$displayField = $this->findDisplayField($tempModel->schema());
			}

			$prompt = __d('cake_console', "Would you like to supply validation criteria \nfor the fields in your model?");
			$wannaDoValidation = $this->in($prompt, ['y', 'n'], 'y');
			if (array_search($useTable, $this->_tables) !== false && strtolower($wannaDoValidation) === 'y') {
				$validate = $this->doValidation($tempModel);
			}

			$prompt = __d('cake_console', "Would you like to define model associations\n(hasMany, hasOne, belongsTo, etc.)?");
			$wannaDoAssoc = $this->in($prompt, ['y', 'n'], 'y');
			if (strtolower($wannaDoAssoc) === 'y') {
				$associations = $this->doAssociations($tempModel);
			}
		}

		$this->out();
		$this->hr();
		$this->out(__d('cake_console', 'The following Model will be created:'));
		$this->hr();
		$this->out(__d('cake_console', "Name:       %s", $currentModelName));

		if ($this->connection !== 'default') {
			$this->out(__d('cake_console', "DB Config:  %s", $this->connection));
		}
		if ($fullTableName !== Inflector::tableize($currentModelName)) {
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
				$this->_printAssociation($currentModelName, $assocKey, $associations);
			}
		}

		$this->hr();
		$looksGood = $this->in(__d('cake_console', 'Look okay?'), ['y', 'n'], 'y');

		if (strtolower($looksGood) === 'y') {
			$vars = compact('associations', 'validate', 'primaryKey', 'useTable', 'displayField');
			$vars['useDbConfig'] = $this->connection;
			if ($this->bake($currentModelName, $vars)) {
				if ($this->_checkUnitTest()) {
					$this->bakeFixture($currentModelName, $useTable);
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
 * Handles Generation and user interaction for creating validation.
 *
 * @param Model $model Model to have validations generated for.
 * @return array $validate Array of user selected validations.
 */
	public function doValidation($model) {
		if (!$model instanceof Model) {
			return false;
		}

		$fields = $model->schema();
		if (empty($fields)) {
			return false;
		}

		$skipFields = false;
		$validate = array();
		$this->initValidations();
		foreach ($fields as $fieldName => $field) {
			$validation = $this->fieldValidation($fieldName, $field, $model->primaryKey);
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
		if (class_exists('Validation')) {
			$options = get_class_methods('Validation');
		}
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
 * Handles behaviors
 *
 * @param Model $model
 * @return array Behaviors
 */
	public function doActsAs($model) {
		if (!$model instanceof Model) {
			return false;
		}
		$behaviors = [];
		$fields = $model->schema(true);
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
 * Interact with the user and confirm associations.
 *
 * @param array $model Temporary Model instance.
 * @param array $associations Array of associations to be confirmed.
 * @return array Array of confirmed associations
 */
	public function confirmAssociations($model, $associations) {
		foreach ($associations as $type => $settings) {
			if (!empty($associations[$type])) {
				foreach ($associations[$type] as $i => $assoc) {
					$prompt = "{$model->name} {$type} {$assoc['alias']}?";
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
 * @param Model $model Temporary model instance
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
				if (!empty($possibleKeys[$model->table])) {
					$showKeys = $possibleKeys[$model->table];
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
			$tempOtherModel = new Model(['table' => $otherTable, 'ds' => $this->connection]);
			$modelFieldsTemp = $tempOtherModel->schema(true);
			foreach ($modelFieldsTemp as $fieldName => $field) {
				if ($field['type'] === 'integer' || $field['type'] === 'string') {
					$possible[$otherTable][] = $fieldName;
				}
			}
		}
		return $possible;
	}

/**
 * Assembles and writes a Model file.
 *
 * @param string|object $name Model name or object
 * @param array|boolean $data if array and $name is not an object assume bake data, otherwise boolean.
 * @return string
 */
	public function bake($name, $data = []) {
		if ($name instanceof Model) {
			if (!$data) {
				$data = [];
				$data['associations'] = $this->doAssociations($name);
				$data['validate'] = $this->doValidation($name);
				$data['actsAs'] = $this->doActsAs($name);
			}
			$data['primaryKey'] = $name->primaryKey;
			$data['useTable'] = $name->table;
			$data['useDbConfig'] = $name->useDbConfig;
			$data['name'] = $name = $name->name;
		} else {
			$data['name'] = $name;
		}

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
		$filename = $path . $name . '.php';
		$this->out("\n" . __d('cake_console', 'Baking model class for %s...', $name), 1, Shell::QUIET);
		$this->createFile($filename, $out);
		ClassRegistry::flush();
		return $out;
	}

/**
 * Assembles and writes a unit test file
 *
 * @param string $className Model class name
 * @return string
 */
	public function bakeTest($className) {
		$this->Test->plugin = $this->plugin;
		$this->Test->connection = $this->connection;
		return $this->Test->bake('Model', $className);
	}

/**
 * Outputs the a list of possible models or controllers from database
 *
 * @param string $useDbConfig Database configuration name
 * @return array
 */
	public function listAll() {
		if (!empty($this->_tables)) {
			return $this->_tables;
		}

		$this->_modelNames = [];
		$this->_tables = $this->_getAllTables();
		foreach ($this->_tables as $table) {
			$this->_modelNames[] = $this->_modelName($table);
		}
		return $this->_tables;
	}

/**
 * Get an Array of all the tables in the supplied connection
 * will halt the script if no tables are found.
 *
 * @return array Array of tables in the database.
 * @throws InvalidArgumentException When connection class
 *   has a schemaCollection method.
 */
	protected function _getAllTables() {
		$tables = [];
		$db = ConnectionManager::get($this->connection);
		if (!method_exists($db, 'schemaCollection')) {
			$this->err(__d(
				'cake_console',
				'Connections need to implement schemaCollection() to be used with bake.'
			));
			return $this->_stop();
		}
		$schema = $db->schemaCollection();
		$tables = $schema->listTables();
		if (empty($tables)) {
			$this->err(__d('cake_console', 'Your database does not have any tables.'));
			return $this->_stop();
		}
		sort($tables);
		return $tables;
	}

/**
 * Get the table name for the model being baked.
 *
 * Uses the `table` option if it is set.
 *
 * @return string.
 */
	public function getTable() {
		if (isset($this->params['table'])) {
			return $this->params['table'];
		}
		return Inflector::tableize($this->args[0]);
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
		])->addOption('table', [
			'help' => __d('cake_console', 'The table name to use if you have non-conventional table names.')
		])->addOption('no-entity', [
			'boolean' => true,
			'help' => __d('cake_console', 'Disable generating an entity class.')
		])->addOption('no-table', [
			'boolean' => true,
			'help' => __d('cake_console', 'Disable generating a table class.')
		])->addOption('no-validation', [
			'boolean' => true,
			'help' => __d('cake_console', 'Disable generating validation rules.')
		])->addOption('no-associations', [
			'boolean' => true,
			'help' => __d('cake_console', 'Disable generating associations.')
		])->addOption('no-fields', [
			'boolean' => true,
			'help' => __d('cake_console', 'Disable generating accessible fields in the entity.')
		])->addOption('fields', [
			'help' => __d('cake_console', 'A comma separated list of fields to make accessible.')
		])->addOption('primary-key', [
			'help' => __d('cake_console', 'The primary key if you would like to manually set one.')
		])->addOption('display-field', [
			'help' => __d('cake_console', 'The displayField if you would like to choose one.')
		])->epilog(
			__d('cake_console', 'Omitting all arguments and options will list ' .
				'the table names you can generate models for')
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
		$this->Fixture->connection = $this->connection;
		$this->Fixture->plugin = $this->plugin;
		$this->Fixture->bake($className, $useTable);
	}

}
