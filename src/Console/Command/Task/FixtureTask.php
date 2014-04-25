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
 * @since         1.3.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Console\Command\Task;

use Cake\Console\ConsoleInput;
use Cake\Console\ConsoleOutput;
use Cake\Console\Shell;
use Cake\Core\Configure;
use Cake\Database\Schema\Table;
use Cake\Datasource\ConnectionManager;
use Cake\ORM\TableRegistry;
use Cake\Utility\Inflector;
use Cake\Utility\String;

/**
 * Task class for creating and updating fixtures files.
 */
class FixtureTask extends BakeTask {

/**
 * Tasks to be loaded by this Task
 *
 * @var array
 */
	public $tasks = ['Model', 'Template'];

/**
 * Get the file path.
 *
 * @return string
 */
	public function getPath() {
		$dir = 'Test/Fixture/';
		$path = ROOT . DS . $dir;
		if (isset($this->plugin)) {
			$path = $this->_pluginPath($this->plugin) . $dir;
		}
		return str_replace('/', DS, $path);
	}

/**
 * Gets the option parser instance and configures it.
 *
 * @return \Cake\Console\ConsoleOptionParser
 */
	public function getOptionParser() {
		$parser = parent::getOptionParser();

		$parser = $parser->description(
			__d('cake_console', 'Generate fixtures for use with the test suite. You can use `bake fixture all` to bake all fixtures.')
		)->addArgument('name', [
			'help' => __d('cake_console', 'Name of the fixture to bake. Can use Plugin.name to bake plugin fixtures.')
		])->addOption('table', [
			'help' => __d('cake_console', 'The table name if it does not follow conventions.'),
		])->addOption('count', [
			'help' => __d('cake_console', 'When using generated data, the number of records to include in the fixture(s).'),
			'short' => 'n',
			'default' => 10
		])->addOption('schema', [
			'help' => __d('cake_console', 'Create a fixture that imports schema, instead of dumping a schema snapshot into the fixture.'),
			'short' => 's',
			'boolean' => true
		])->addOption('records', [
			'help' => __d('cake_console', 'Used with --count and <name>/all commands to pull [n] records from the live tables, where [n] is either --count or the default of 10.'),
			'short' => 'r',
			'boolean' => true
		])->addOption('conditions', [
			'help' => __d('cake_console', 'The SQL snippet to use when importing records.'),
			'default' => '1=1',
		]);

		return $parser;
	}

/**
 * Execution method always used for tasks
 * Handles dispatching to interactive, named, or all processes.
 *
 * @return void
 */
	public function main($name = null) {
		parent::main();

		if (empty($name)) {
			$this->out(__d('cake_console', 'Choose a fixture to bake from the following:'));
			foreach ($this->Model->listAll() as $table) {
				$this->out('- ' . $this->_modelName($table));
			}
			return true;
		}

		$table = null;
		if (isset($this->params['table'])) {
			$table = $this->params['table'];
		}
		$model = $this->_modelName($name);
		$this->bake($model, $table);
	}

/**
 * Bake All the Fixtures at once. Will only bake fixtures for models that exist.
 *
 * @return void
 */
	public function all() {
		$tables = $this->Model->listAll($this->connection, false);

		foreach ($tables as $table) {
			$model = $this->_modelName($table);
			$importOptions = [];
			if (!empty($this->params['schema'])) {
				$importOptions['schema'] = $model;
			}
			$this->bake($model, false, $importOptions);
		}
	}
/**
 * Interacts with the User to setup an array of import options. For a fixture.
 *
 * @param string $modelName Name of model you are dealing with.
 * @return array Array of import options.
 */
	public function importOptions($modelName) {
		$options = [];

		if (!empty($this->params['schema'])) {
			$options['schema'] = $modelName;
		}
		if (!empty($this->params['records'])) {
			$options['records'] = true;
			$options['fromTable'] = true;
		}
		return $options;
	}

/**
 * Assembles and writes a Fixture file
 *
 * @param string $model Name of model to bake.
 * @param string $useTable Name of table to use.
 * @param array $importOptions Options for public $import
 * @return string Baked fixture content
 * @throws \RuntimeException
 */
	public function bake($model, $useTable = false, array $importOptions = []) {
		$table = $schema = $records = $import = $modelImport = null;
		$importBits = [];

		if (!$useTable) {
			$useTable = Inflector::tableize($model);
		} elseif ($useTable != Inflector::tableize($model)) {
			$table = $useTable;
		}

		if (!empty($importOptions)) {
			if (isset($importOptions['schema'])) {
				$modelImport = true;
				$importBits[] = "'model' => '{$importOptions['schema']}'";
			}
			if (isset($importOptions['records'])) {
				$importBits[] = "'records' => true";
			}
			if ($this->connection !== 'default') {
				$importBits[] .= "'connection' => '{$this->connection}'";
			}
			if (!empty($importBits)) {
				$import = sprintf("[%s]", implode(', ', $importBits));
			}
		}

		$connection = ConnectionManager::get($this->connection);
		if (!method_exists($connection, 'schemaCollection')) {
			throw new \RuntimeException(
				'Cannot generate fixtures for connections that do not implement schemaCollection()'
			);
		}
		$schemaCollection = $connection->schemaCollection();
		$data = $schemaCollection->describe($useTable);

		if ($modelImport === null) {
			$schema = $this->_generateSchema($data);
		}

		if (empty($importOptions['records']) && !isset($importOptions['fromTable'])) {
			$recordCount = 1;
			if (isset($this->params['count'])) {
				$recordCount = $this->params['count'];
			}
			$records = $this->_makeRecordString($this->_generateRecords($data, $recordCount));
		}
		if (!empty($this->params['records']) || isset($importOptions['fromTable'])) {
			$records = $this->_makeRecordString($this->_getRecordsFromTable($model, $useTable));
		}
		return $this->generateFixtureFile($model, compact('records', 'table', 'schema', 'import'));
	}

/**
 * Generate the fixture file, and write to disk
 *
 * @param string $model name of the model being generated
 * @param array $otherVars Contents of the fixture file.
 * @return string Content saved into fixture file.
 */
	public function generateFixtureFile($model, array $otherVars) {
		$defaults = [
			'name' => Inflector::singularize($model),
			'table' => null,
			'schema' => null,
			'records' => null,
			'import' => null,
			'fields' => null,
			'namespace' => Configure::read('App.namespace')
		];
		if ($this->plugin) {
			$defaults['namespace'] = $this->plugin;
		}
		$vars = $otherVars + $defaults;

		$path = $this->getPath();
		$filename = $vars['name'] . 'Fixture.php';

		$this->Template->set('model', $model);
		$this->Template->set($vars);
		$content = $this->Template->generate('classes', 'fixture');

		$this->out("\n" . __d('cake_console', 'Baking test fixture for %s...', $model), 1, Shell::QUIET);
		$this->createFile($path . $filename, $content);
		return $content;
	}

/**
 * Generates a string representation of a schema.
 *
 * @param \Cake\Database\Schema\Table $table Table schema
 * @return string fields definitions
 */
	protected function _generateSchema(Table $table) {
		$cols = $indexes = $constraints = [];
		foreach ($table->columns() as $field) {
			$fieldData = $table->column($field);
			$properties = implode(', ', $this->_values($fieldData));
			$cols[] = "\t\t'$field' => [$properties],";
		}
		foreach ($table->indexes() as $index) {
			$fieldData = $table->index($index);
			$properties = implode(', ', $this->_values($fieldData));
			$indexes[] = "\t\t\t'$index' => [$properties],";
		}
		foreach ($table->constraints() as $index) {
			$fieldData = $table->constraint($index);
			$properties = implode(', ', $this->_values($fieldData));
			$constraints[] = "\t\t\t'$index' => [$properties],";
		}
		$options = $this->_values($table->options());

		$content = implode("\n", $cols) . "\n";
		if (!empty($indexes)) {
			$content .= "\t\t'_indexes' => [\n" . implode("\n", $indexes) . "\n\t\t],\n";
		}
		if (!empty($constraints)) {
			$content .= "\t\t'_constraints' => [\n" . implode("\n", $constraints) . "\n\t\t],\n";
		}
		if (!empty($options)) {
			$content .= "\t\t'_options' => [\n" . implode(', ', $options) . "\n\t\t],\n";
		}
		return "[\n$content\t]";
	}

/**
 * Formats Schema columns from Model Object
 *
 * @param array $values options keys(type, null, default, key, length, extra)
 * @return array Formatted values
 */
	protected function _values($values) {
		$vals = [];
		if (!is_array($values)) {
			return $vals;
		}
		foreach ($values as $key => $val) {
			if (is_array($val)) {
				$vals[] = "'{$key}' => [" . implode(", ", $this->_values($val)) . "]";
			} else {
				$val = var_export($val, true);
				if ($val === 'NULL') {
					$val = 'null';
				}
				if (!is_numeric($key)) {
					$vals[] = "'{$key}' => {$val}";
				} else {
					$vals[] = "{$val}";
				}
			}
		}
		return $vals;
	}

/**
 * Generate String representation of Records
 *
 * @param \Cake\Database\Schema\Table $table Table schema
 * @param int $recordCount
 * @return array Array of records to use in the fixture.
 */
	protected function _generateRecords(Table $table, $recordCount = 1) {
		$records = [];
		for ($i = 0; $i < $recordCount; $i++) {
			$record = [];
			foreach ($table->columns() as $field) {
				$fieldInfo = $table->column($field);
				$insert = '';
				switch ($fieldInfo['type']) {
					case 'integer':
					case 'float':
						$insert = $i + 1;
						break;
					case 'string':
					case 'binary':
						$isPrimary = in_array($field, $table->primaryKey());
						if ($isPrimary) {
							$insert = String::uuid();
						} else {
							$insert = "Lorem ipsum dolor sit amet";
							if (!empty($fieldInfo['length'])) {
								$insert = substr($insert, 0, (int)$fieldInfo['length'] - 2);
							}
						}
						break;
					case 'timestamp':
						$insert = time();
						break;
					case 'datetime':
						$insert = date('Y-m-d H:i:s');
						break;
					case 'date':
						$insert = date('Y-m-d');
						break;
					case 'time':
						$insert = date('H:i:s');
						break;
					case 'boolean':
						$insert = 1;
						break;
					case 'text':
						$insert = "Lorem ipsum dolor sit amet, aliquet feugiat.";
						$insert .= " Convallis morbi fringilla gravida,";
						$insert .= " phasellus feugiat dapibus velit nunc, pulvinar eget sollicitudin";
						$insert .= " venenatis cum nullam, vivamus ut a sed, mollitia lectus. Nulla";
						$insert .= " vestibulum massa neque ut et, id hendrerit sit,";
						$insert .= " feugiat in taciti enim proin nibh, tempor dignissim, rhoncus";
						$insert .= " duis vestibulum nunc mattis convallis.";
						break;
				}
				$record[$field] = $insert;
			}
			$records[] = $record;
		}
		return $records;
	}

/**
 * Convert a $records array into a a string.
 *
 * @param array $records Array of records to be converted to string
 * @return string A string value of the $records array.
 */
	protected function _makeRecordString($records) {
		$out = "[\n";
		foreach ($records as $record) {
			$values = [];
			foreach ($record as $field => $value) {
				$val = var_export($value, true);
				if ($val === 'NULL') {
					$val = 'null';
				}
				$values[] = "\t\t\t'$field' => $val";
			}
			$out .= "\t\t[\n";
			$out .= implode(",\n", $values);
			$out .= "\n\t\t],\n";
		}
		$out .= "\t]";
		return $out;
	}

/**
 * Interact with the user to get a custom SQL condition and use that to extract data
 * to build a fixture.
 *
 * @param string $modelName name of the model to take records from.
 * @param string $useTable Name of table to use.
 * @return array Array of records.
 */
	protected function _getRecordsFromTable($modelName, $useTable = null) {
		$recordCount = (isset($this->params['count']) ? $this->params['count'] : 10);
		$conditions = (isset($this->params['conditions']) ? $this->params['conditions'] : '1=1');
		if (TableRegistry::exists($modelName)) {
			$model = TableRegistry::get($modelName);
		} else {
			$model = TableRegistry::get($modelName, [
				'table' => $useTable,
				'connection' => ConnectionManager::get($this->connection)
			]);
		}
		$records = $model->find('all', [
			'conditions' => $conditions,
			'limit' => $recordCount
		]);

		$schema = $model->schema();
		$alias = $model->alias();
		$out = [];
		foreach ($records as $record) {
			$out[] = $record->toArray();
		}
		return $out;
	}

}
