<?php
/**
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
namespace Cake\Shell\Task;

use Cake\Console\Shell;
use Cake\Core\Configure;
use Cake\Datasource\ConnectionManager;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\Utility\Inflector;

/**
 * Task class for generating model files.
 */
class ModelTask extends BakeTask {

/**
 * path to Model directory
 *
 * @var string
 */
	public $pathFragment = 'Model/';

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
 * Execution method always used for tasks
 *
 * @param string $name The name of the table to bake.
 * @return void
 */
	public function main($name = null) {
		parent::main();
		$name = $this->_getName($name);

		if (empty($name)) {
			$this->out('Choose a model to bake from the following:');
			foreach ($this->listAll() as $table) {
				$this->out('- ' . $this->_camelize($table));
			}
			return true;
		}

		$this->bake($this->_camelize($name));
	}

/**
 * Generate code for the given model name.
 *
 * @param string $name The model name to generate.
 * @return void
 */
	public function bake($name) {
		$table = $this->getTable($name);
		$model = $this->getTableObject($name, $table);

		$associations = $this->getAssociations($model);
		$this->applyAssociations($model, $associations);

		$primaryKey = $this->getPrimaryKey($model);
		$displayField = $this->getDisplayField($model);
		$fields = $this->getFields($model);
		$validation = $this->getValidation($model);
		$behaviors = $this->getBehaviors($model);

		$data = compact(
			'associations', 'primaryKey', 'displayField',
			'table', 'fields', 'validation', 'behaviors'
		);
		$this->bakeTable($model, $data);
		$this->bakeEntity($model, $data);
		$this->bakeFixture($model->alias(), $table);
		$this->bakeTest($model->alias());
	}

/**
 * Bake all models at once.
 *
 * @return void
 */
	public function all() {
		$this->listAll($this->connection, false);
		foreach ($this->_tables as $table) {
			if (in_array($table, $this->skipTables)) {
				continue;
			}
			TableRegistry::clear();
			$this->main($table);
		}
	}

/**
 * Get a model object for a class name.
 *
 * @param string $className Name of class you want model to be.
 * @param string $table Table name
 * @return \Cake\ORM\Table Table instance
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
 * @param \Cake\ORM\Table $table The table to get associations for.
 * @return array
 */
	public function getAssociations(Table $table) {
		if (!empty($this->params['no-associations'])) {
			return [];
		}
		$this->out('One moment while associations are detected.');

		$this->listAll();

		$associations = [
			'belongsTo' => [],
			'hasMany' => [],
			'belongsToMany' => []
		];

		$primary = $table->primaryKey();
		$associations = $this->findBelongsTo($table, $associations);

		if (is_array($primary) && count($primary) > 1) {
			$this->err(
				'<warning>Bake cannot generate associations for composite primary keys at this time</warning>.'
			);
			return $associations;
		}

		$associations = $this->findHasMany($table, $associations);
		$associations = $this->findBelongsToMany($table, $associations);
		return $associations;
	}

/**
 * Sync the in memory table object.
 *
 * Composer's class cache prevents us from loading the
 * newly generated class. Applying associations if we have a
 * generic table object means fields will be detected correctly.
 *
 * @param \Cake\ORM\Table $model The table to apply associations to.
 * @param array $associations The associations to append.
 * @return void
 */
	public function applyAssociations($model, $associations) {
		if (get_class($model) !== 'Cake\ORM\Table') {
			return;
		}
		foreach ($associations as $type => $assocs) {
			foreach ($assocs as $assoc) {
				$alias = $assoc['alias'];
				unset($assoc['alias']);
				$model->{$type}($alias, $assoc);
			}
		}
	}

/**
 * Find belongsTo relations and add them to the associations list.
 *
 * @param \Cake\ORM\Table $model Database\Table instance of table being generated.
 * @param array $associations Array of in progress associations
 * @return array Associations with belongsTo added in.
 */
	public function findBelongsTo($model, array $associations) {
		$schema = $model->schema();
		$primary = (array)$schema->primaryKey();
		foreach ($schema->columns() as $fieldName) {
			if (!preg_match('/^.*_id$/', $fieldName)) {
				continue;
			}

			$assoc = false;
			if ($fieldName === 'parent_id') {
				$className = ($this->plugin) ? $this->plugin . '.' . $model->alias() : $model->alias();
				$assoc = [
					'alias' => 'Parent' . $model->alias(),
					'className' => $className,
					'foreignKey' => $fieldName
				];
			} else {
				$tmpModelName = $this->_modelNameFromKey($fieldName);
				$assoc = [
					'alias' => $tmpModelName,
					'foreignKey' => $fieldName
				];
			}

			if ($this->plugin && empty($assoc['className'])) {
				$assoc['className'] = $this->plugin . '.' . $assoc['alias'];
			}
			$associations['belongsTo'][] = $assoc;
		}
		return $associations;
	}

/**
 * Find the hasMany relations and add them to associations list
 *
 * @param \Cake\ORM\Table $model Model instance being generated
 * @param array $associations Array of in progress associations
 * @return array Associations with hasMany added in.
 */
	public function findHasMany($model, array $associations) {
		$schema = $model->schema();
		$primaryKey = (array)$schema->primaryKey();
		$tableName = $schema->name();
		$foreignKey = $this->_modelKey($tableName);

		foreach ($this->listAll() as $otherTable) {
			$otherModel = $this->getTableObject($this->_camelize($otherTable), $otherTable);
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
						'foreignKey' => $fieldName
					];
				} elseif ($otherTable == $tableName && $fieldName === 'parent_id') {
					$className = ($this->plugin) ? $this->plugin . '.' . $model->alias() : $model->alias();
					$assoc = [
						'alias' => 'Child' . $model->alias(),
						'className' => $className,
						'foreignKey' => $fieldName
					];
				}
				if ($assoc && $this->plugin && empty($assoc['className'])) {
					$assoc['className'] = $this->plugin . '.' . $assoc['alias'];
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
 * @param \Cake\ORM\Table $model Model instance being generated
 * @param array $associations Array of in-progress associations
 * @return array Associations with belongsToMany added in.
 */
	public function findBelongsToMany($model, array $associations) {
		$schema = $model->schema();
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
				$habtmName = $this->_camelize($assocTable);
				$assoc = [
					'alias' => $habtmName,
					'foreignKey' => $foreignKey,
					'targetForeignKey' => $this->_modelKey($habtmName),
					'joinTable' => $otherTable
				];
				if ($assoc && $this->plugin) {
					$assoc['className'] = $this->plugin . '.' . $assoc['alias'];
				}
				$associations['belongsToMany'][] = $assoc;
			}
		}
		return $associations;
	}

/**
 * Get the display field from the model or parameters
 *
 * @param \Cake\ORM\Table $model The model to introspect.
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
 * @param \Cake\ORM\Table $model The model to introspect.
 * @return array The columns in the primary key
 */
	public function getPrimaryKey($model) {
		if (!empty($this->params['primary-key'])) {
			$fields = explode(',', $this->params['primary-key']);
			return array_values(array_filter(array_map('trim', $fields)));
		}
		return (array)$model->primaryKey();
	}

/**
 * Get the fields from a model.
 *
 * Uses the fields and no-fields options.
 *
 * @param \Cake\ORM\Table $model The model to introspect.
 * @return array The columns to make accessible
 */
	public function getFields($model) {
		if (!empty($this->params['no-fields'])) {
			return [];
		}
		if (!empty($this->params['fields'])) {
			$fields = explode(',', $this->params['fields']);
			return array_values(array_filter(array_map('trim', $fields)));
		}
		$schema = $model->schema();
		$columns = $schema->columns();
		$primary = $this->getPrimaryKey($model);
		$exclude = array_merge($primary, ['created', 'modified', 'updated']);

		$associations = $model->associations();
		foreach ($associations->keys() as $assocName) {
			$columns[] = $associations->get($assocName)->property();
		}
		return array_values(array_diff($columns, $exclude));
	}

/**
 * Get the hidden fields from a model.
 *
 * Uses the hidden and no-hidden options.
 *
 * @param \Cake\ORM\Table $model The model to introspect.
 * @return array The columns to make accessible
 */
	public function getHiddenFields($model) {
		if (!empty($this->params['no-hidden'])) {
			return [];
		}
		if (!empty($this->params['hidden'])) {
			$fields = explode(',', $this->params['hidden']);
			return array_values(array_filter(array_map('trim', $fields)));
		}
		$schema = $model->schema();
		$columns = $schema->columns();
		$whitelist = ['token', 'password', 'passwd'];
		return array_values(array_intersect($columns, $whitelist));
	}

/**
 * Generate default validation rules.
 *
 * @param \Cake\ORM\Table $model The model to introspect.
 * @return array The validation rules.
 */
	public function getValidation($model) {
		if (!empty($this->params['no-validation'])) {
			return [];
		}
		$schema = $model->schema();
		$fields = $schema->columns();
		if (empty($fields)) {
			return false;
		}

		$skipFields = false;
		$validate = [];
		$primaryKey = (array)$schema->primaryKey();

		foreach ($fields as $fieldName) {
			$field = $schema->column($fieldName);
			$validation = $this->fieldValidation($fieldName, $field, $primaryKey);
			if (!empty($validation)) {
				$validate[$fieldName] = $validation;
			}
		}
		return $validate;
	}

/**
 * Does individual field validation handling.
 *
 * @param string $fieldName Name of field to be validated.
 * @param array $metaData metadata for field
 * @param string $primaryKey The primary key field
 * @return array Array of validation for the field.
 */
	public function fieldValidation($fieldName, array $metaData, $primaryKey) {
		$ignoreFields = ['created', 'modified', 'updated'];
		if (in_array($fieldName, $ignoreFields)) {
			return false;
		}

		$rule = false;
		if ($fieldName === 'email') {
			$rule = 'email';
		} elseif ($metaData['type'] === 'uuid') {
			$rule = 'uuid';
		} elseif ($metaData['type'] === 'integer') {
			$rule = 'numeric';
		} elseif ($metaData['type'] === 'float') {
			$rule = 'numeric';
		} elseif ($metaData['type'] === 'decimal') {
			$rule = 'decimal';
		} elseif ($metaData['type'] === 'boolean') {
			$rule = 'boolean';
		} elseif ($metaData['type'] === 'date') {
			$rule = 'date';
		} elseif ($metaData['type'] === 'time') {
			$rule = 'time';
		} elseif ($metaData['type'] === 'datetime') {
			$rule = 'datetime';
		} elseif ($metaData['type'] === 'inet') {
			$rule = 'ip';
		}

		$allowEmpty = false;
		if (in_array($fieldName, $primaryKey)) {
			$allowEmpty = 'create';
		} elseif ($metaData['null'] === true) {
			$allowEmpty = true;
		}

		$validation = [
			'valid' => [
				'rule' => $rule,
				'allowEmpty' => $allowEmpty,
			]
		];

		if (in_array($fieldName, ['email', 'username', 'login'])) {
			$validation['unique'] = [
				'rule' => 'validateUnique',
				'provider' => 'table'
			];
		}

		return $validation;
	}

/**
 * Get behaviors
 *
 * @param \Cake\ORM\Table $model The model to generate behaviors for.
 * @return array Behaviors
 */
	public function getBehaviors($model) {
		$behaviors = [];
		$schema = $model->schema();
		$fields = $schema->columns();
		if (empty($fields)) {
			return [];
		}
		if (in_array('created', $fields) || in_array('modified', $fields)) {
			$behaviors['Timestamp'] = [];
		}

		if (in_array('lft', $fields) && $schema->columnType('lft') === 'integer' &&
			in_array('rght', $fields) && $schema->columnType('rght') === 'integer' &&
			in_array('parent_id', $fields)
		) {
			$behaviors['Tree'] = [];
		}

		$counterCache = $this->getCounterCache($model);
		if (!empty($counterCache)) {
			$behaviors['CounterCache'] = $counterCache;
		}
		return $behaviors;
	}

/**
 * Get CounterCaches
 *
 * @param \Cake\ORM\Table $model The table to get counter cache fields for.
 * @return array CounterCache configurations
 */
	public function getCounterCache($model) {
		$belongsTo = $this->findBelongsTo($model, ['belongsTo' => []]);
		$counterCache = [];
		foreach ($belongsTo['belongsTo'] as $otherTable) {
			$otherAlias = $otherTable['alias'];
			$otherModel = $this->getTableObject($this->_camelize($otherAlias), Inflector::underscore($otherAlias));

			try {
				$otherSchema = $otherModel->schema();
			} catch (\Cake\Database\Exception $e) {
				continue;
			}

			$otherFields = $otherSchema->columns();
			$alias = $model->alias();
			$field = Inflector::singularize(Inflector::underscore($alias)) . '_count';
			if (in_array($field, $otherFields, true)) {
				$counterCache[] = "'{$otherAlias}' => ['{$field}']";
			}
		}
		return $counterCache;
	}

/**
 * Bake an entity class.
 *
 * @param \Cake\ORM\Table $model Model name or object
 * @param array $data An array to use to generate the Table
 * @return string
 */
	public function bakeEntity($model, array $data = []) {
		if (!empty($this->params['no-entity'])) {
			return;
		}
		$name = $this->_entityName($model->alias());

		$ns = Configure::read('App.namespace');
		$pluginPath = '';
		if ($this->plugin) {
			$ns = $this->plugin;
			$pluginPath = $this->plugin . '.';
		}

		$data += [
			'name' => $name,
			'namespace' => $ns,
			'plugin' => $this->plugin,
			'pluginPath' => $pluginPath,
			'fields' => [],
		];

		$this->Template->set($data);
		$out = $this->Template->generate('classes', 'entity');

		$path = $this->getPath();
		$filename = $path . 'Entity' . DS . $name . '.php';
		$this->out("\n" . sprintf('Baking entity class for %s...', $name), 1, Shell::QUIET);
		$this->createFile($filename, $out);
		$emptyFile = $path . 'Entity' . DS . 'empty';
		$this->_deleteEmptyFile($emptyFile);
		return $out;
	}

/**
 * Bake a table class.
 *
 * @param \Cake\ORM\Table $model Model name or object
 * @param array $data An array to use to generate the Table
 * @return string
 */
	public function bakeTable($model, array $data = []) {
		if (!empty($this->params['no-table'])) {
			return;
		}

		$ns = Configure::read('App.namespace');
		$pluginPath = '';
		if ($this->plugin) {
			$ns = $this->plugin;
			$pluginPath = $this->plugin . '.';
		}

		$name = $model->alias();
		$data += [
			'plugin' => $this->plugin,
			'pluginPath' => $pluginPath,
			'namespace' => $ns,
			'name' => $name,
			'associations' => [],
			'primaryKey' => 'id',
			'displayField' => null,
			'table' => null,
			'validation' => [],
			'behaviors' => [],
		];

		$this->Template->set($data);
		$out = $this->Template->generate('classes', 'table');

		$path = $this->getPath();
		$filename = $path . 'Table' . DS . $name . 'Table.php';
		$this->out("\n" . sprintf('Baking table class for %s...', $name), 1, Shell::QUIET);
		$this->createFile($filename, $out);
		$emptyFile = $path . 'Table' . DS . 'empty';
		$this->_deleteEmptyFile($emptyFile);
		return $out;
	}

/**
 * Outputs the a list of possible models or controllers from database
 *
 * @return array
 */
	public function listAll() {
		if (!empty($this->_tables)) {
			return $this->_tables;
		}

		$this->_modelNames = [];
		$this->_tables = $this->_getAllTables();
		foreach ($this->_tables as $table) {
			$this->_modelNames[] = $this->_camelize($table);
		}
		return $this->_tables;
	}

/**
 * Get an Array of all the tables in the supplied connection
 * will halt the script if no tables are found.
 *
 * @return array Array of tables in the database.
 * @throws \InvalidArgumentException When connection class
 *   does not have a schemaCollection method.
 */
	protected function _getAllTables() {
		$tables = [];
		$db = ConnectionManager::get($this->connection);
		if (!method_exists($db, 'schemaCollection')) {
			$this->err(
				'Connections need to implement schemaCollection() to be used with bake.'
			);
			return $this->_stop();
		}
		$schema = $db->schemaCollection();
		$tables = $schema->listTables();
		if (empty($tables)) {
			$this->err('Your database does not have any tables.');
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
 * @param string $name Table name
 * @return string
 */
	public function getTable($name) {
		if (isset($this->params['table'])) {
			return $this->params['table'];
		}
		return Inflector::underscore($name);
	}

/**
 * Gets the option parser instance and configures it.
 *
 * @return \Cake\Console\ConsoleOptionParser
 */
	public function getOptionParser() {
		$parser = parent::getOptionParser();

		$parser->description(
			'Bake table and entity classes.'
		)->addArgument('name', [
			'help' => 'Name of the model to bake. Can use Plugin.name to bake plugin models.'
		])->addSubcommand('all', [
			'help' => 'Bake all model files with associations and validation.'
		])->addOption('table', [
			'help' => 'The table name to use if you have non-conventional table names.'
		])->addOption('no-entity', [
			'boolean' => true,
			'help' => 'Disable generating an entity class.'
		])->addOption('no-table', [
			'boolean' => true,
			'help' => 'Disable generating a table class.'
		])->addOption('no-validation', [
			'boolean' => true,
			'help' => 'Disable generating validation rules.'
		])->addOption('no-associations', [
			'boolean' => true,
			'help' => 'Disable generating associations.'
		])->addOption('no-fields', [
			'boolean' => true,
			'help' => 'Disable generating accessible fields in the entity.'
		])->addOption('fields', [
			'help' => 'A comma separated list of fields to make accessible.'
		])->addOption('no-hidden', [
			'boolean' => true,
			'help' => 'Disable generating hidden fields in the entity.'
		])->addOption('hidden', [
			'help' => 'A comma separated list of fields to hide.'
		])->addOption('primary-key', [
			'help' => 'The primary key if you would like to manually set one. Can be a comma separated list if you are using a composite primary key.'
		])->addOption('display-field', [
			'help' => 'The displayField if you would like to choose one.'
		])->addOption('no-test', [
			'boolean' => true,
			'help' => 'Do not generate a test case skeleton.'
		])->addOption('no-fixture', [
			'boolean' => true,
			'help' => 'Do not generate a test fixture skeleton.'
		])->epilog(
			'Omitting all arguments and options will list the table names you can generate models for'
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
		if (!empty($this->params['no-fixture'])) {
			return;
		}
		$this->Fixture->connection = $this->connection;
		$this->Fixture->plugin = $this->plugin;
		$this->Fixture->bake($className, $useTable);
	}

/**
 * Assembles and writes a unit test file
 *
 * @param string $className Model class name
 * @return string
 */
	public function bakeTest($className) {
		if (!empty($this->params['no-test'])) {
			return;
		}
		$this->Test->plugin = $this->plugin;
		$this->Test->connection = $this->connection;
		return $this->Test->bake('Table', $className);
	}

}
