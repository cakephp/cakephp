<?php
/**
 * The FixtureTask handles creating and updating fixture files.
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
 * @since         CakePHP(tm) v 1.3
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */

App::uses('AppShell', 'Console/Command');
App::uses('BakeTask', 'Console/Command/Task');
App::uses('Model', 'Model');

/**
 * Task class for creating and updating fixtures files.
 *
 * @package       Cake.Console.Command.Task
 */
class FixtureTask extends BakeTask {

/**
 * Tasks to be loaded by this Task
 *
 * @var array
 */
	public $tasks = array('DbConfig', 'Model', 'Template');

/**
 * path to fixtures directory
 *
 * @var string
 */
	public $path = null;

/**
 * Schema instance
 *
 * @var CakeSchema
 */
	protected $_Schema = null;

/**
 * Override initialize
 *
 * @param ConsoleOutput $stdout A ConsoleOutput object for stdout.
 * @param ConsoleOutput $stderr A ConsoleOutput object for stderr.
 * @param ConsoleInput $stdin A ConsoleInput object for stdin.
 */
	public function __construct($stdout = null, $stderr = null, $stdin = null) {
		parent::__construct($stdout, $stderr, $stdin);
		$this->path = APP . 'Test' . DS . 'Fixture' . DS;
	}

/**
 * Gets the option parser instance and configures it.
 *
 * @return ConsoleOptionParser
 */
	public function getOptionParser() {
		$parser = parent::getOptionParser();

		$parser->description(
			__d('cake_console', 'Generate fixtures for use with the test suite. You can use `bake fixture all` to bake all fixtures.')
		)->addArgument('name', array(
			'help' => __d('cake_console', 'Name of the fixture to bake. Can use Plugin.name to bake plugin fixtures.')
		))->addOption('count', array(
			'help' => __d('cake_console', 'When using generated data, the number of records to include in the fixture(s).'),
			'short' => 'n',
			'default' => 1
		))->addOption('connection', array(
			'help' => __d('cake_console', 'Which database configuration to use for baking.'),
			'short' => 'c',
			'default' => 'default'
		))->addOption('plugin', array(
			'help' => __d('cake_console', 'CamelCased name of the plugin to bake fixtures for.'),
			'short' => 'p'
		))->addOption('schema', array(
			'help' => __d('cake_console', 'Importing schema for fixtures rather than hardcoding it.'),
			'short' => 's',
			'boolean' => true
		))->addOption('theme', array(
			'short' => 't',
			'help' => __d('cake_console', 'Theme to use when baking code.')
		))->addOption('force', array(
			'short' => 'f',
			'help' => __d('cake_console', 'Force overwriting existing files without prompting.')
		))->addOption('records', array(
			'help' => __d('cake_console', 'Used with --count and <name>/all commands to pull [n] records from the live tables, ' .
				'where [n] is either --count or the default of 10.'),
			'short' => 'r',
			'boolean' => true
		))->epilog(
			__d('cake_console', 'Omitting all arguments and options will enter into an interactive mode.')
		);

		return $parser;
	}

/**
 * Execution method always used for tasks
 * Handles dispatching to interactive, named, or all processes.
 *
 * @return void
 */
	public function execute() {
		parent::execute();
		if (empty($this->args)) {
			$this->_interactive();
		}

		if (isset($this->args[0])) {
			$this->interactive = false;
			if (!isset($this->connection)) {
				$this->connection = 'default';
			}
			if (strtolower($this->args[0]) === 'all') {
				return $this->all();
			}
			$model = $this->_modelName($this->args[0]);
			$this->bake($model);
		}
	}

/**
 * Bake All the Fixtures at once. Will only bake fixtures for models that exist.
 *
 * @return void
 */
	public function all() {
		$this->interactive = false;
		$this->Model->interactive = false;
		$tables = $this->Model->listAll($this->connection, false);

		foreach ($tables as $table) {
			$model = $this->_modelName($table);
			$importOptions = array();
			if (!empty($this->params['schema'])) {
				$importOptions['schema'] = $model;
			}
			$this->bake($model, false, $importOptions);
		}
	}

/**
 * Interactive baking function
 *
 * @return void
 */
	protected function _interactive() {
		$this->DbConfig->interactive = $this->Model->interactive = $this->interactive = true;
		$this->hr();
		$this->out(__d('cake_console', "Bake Fixture\nPath: %s", $this->getPath()));
		$this->hr();

		if (!isset($this->connection)) {
			$this->connection = $this->DbConfig->getConfig();
		}
		$modelName = $this->Model->getName($this->connection);
		$useTable = $this->Model->getTable($modelName, $this->connection);
		$importOptions = $this->importOptions($modelName);
		$this->bake($modelName, $useTable, $importOptions);
	}

/**
 * Interacts with the User to setup an array of import options. For a fixture.
 *
 * @param string $modelName Name of model you are dealing with.
 * @return array Array of import options.
 */
	public function importOptions($modelName) {
		$options = array();

		if (!empty($this->params['schema'])) {
			$options['schema'] = $modelName;
		} else {
			$doSchema = $this->in(__d('cake_console', 'Would you like to import schema for this fixture?'), array('y', 'n'), 'n');
			if ($doSchema === 'y') {
				$options['schema'] = $modelName;
			}
		}
		if (!empty($this->params['records'])) {
			$doRecords = 'y';
		} else {
			$doRecords = $this->in(__d('cake_console', 'Would you like to use record importing for this fixture?'), array('y', 'n'), 'n');
		}
		if ($doRecords === 'y') {
			$options['records'] = true;
		}
		if ($doRecords === 'n') {
			$prompt = __d('cake_console', "Would you like to build this fixture with data from %s's table?", $modelName);
			$fromTable = $this->in($prompt, array('y', 'n'), 'n');
			if (strtolower($fromTable) === 'y') {
				$options['fromTable'] = true;
			}
		}
		return $options;
	}

/**
 * Assembles and writes a Fixture file
 *
 * @param string $model Name of model to bake.
 * @param string $useTable Name of table to use.
 * @param array $importOptions Options for public $import
 * @return string|null Baked fixture content, otherwise null.
 */
	public function bake($model, $useTable = false, $importOptions = array()) {
		App::uses('CakeSchema', 'Model');
		$table = $schema = $records = $import = $modelImport = null;
		$importBits = array();

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
				$import = sprintf("array(%s)", implode(', ', $importBits));
			}
		}

		$this->_Schema = new CakeSchema();
		$data = $this->_Schema->read(array('models' => false, 'connection' => $this->connection));
		if (!isset($data['tables'][$useTable])) {
			$this->err("<warning>Warning:</warning> Could not find the '${useTable}' table for ${model}.");
			return null;
		}

		$tableInfo = $data['tables'][$useTable];
		if ($modelImport === null) {
			$schema = $this->_generateSchema($tableInfo);
		}

		if (empty($importOptions['records']) && !isset($importOptions['fromTable'])) {
			$recordCount = 1;
			if (isset($this->params['count'])) {
				$recordCount = $this->params['count'];
			}
			$records = $this->_makeRecordString($this->_generateRecords($tableInfo, $recordCount));
		}
		if (!empty($this->params['records']) || isset($importOptions['fromTable'])) {
			$records = $this->_makeRecordString($this->_getRecordsFromTable($model, $useTable));
		}
		$out = $this->generateFixtureFile($model, compact('records', 'table', 'schema', 'import'));
		return $out;
	}

/**
 * Generate the fixture file, and write to disk
 *
 * @param string $model name of the model being generated
 * @param string $otherVars Contents of the fixture file.
 * @return string Content saved into fixture file.
 */
	public function generateFixtureFile($model, $otherVars) {
		$defaults = array('table' => null, 'schema' => null, 'records' => null, 'import' => null, 'fields' => null);
		$vars = array_merge($defaults, $otherVars);

		$path = $this->getPath();
		$filename = Inflector::camelize($model) . 'Fixture.php';

		$this->Template->set('model', $model);
		$this->Template->set($vars);
		$content = $this->Template->generate('classes', 'fixture');

		$this->out("\n" . __d('cake_console', 'Baking test fixture for %s...', $model), 1, Shell::QUIET);
		$this->createFile($path . $filename, $content);
		return $content;
	}

/**
 * Get the path to the fixtures.
 *
 * @return string Path for the fixtures
 */
	public function getPath() {
		$path = $this->path;
		if (isset($this->plugin)) {
			$path = $this->_pluginPath($this->plugin) . 'Test' . DS . 'Fixture' . DS;
		}
		return $path;
	}

/**
 * Generates a string representation of a schema.
 *
 * @param array $tableInfo Table schema array
 * @return string fields definitions
 */
	protected function _generateSchema($tableInfo) {
		$schema = trim($this->_Schema->generateTable('f', $tableInfo), "\n");
		return substr($schema, 13, -1);
	}

/**
 * Generate String representation of Records
 *
 * @param array $tableInfo Table schema array
 * @param int $recordCount The number of records to generate.
 * @return array Array of records to use in the fixture.
 */
	protected function _generateRecords($tableInfo, $recordCount = 1) {
		$records = array();
		for ($i = 0; $i < $recordCount; $i++) {
			$record = array();
			foreach ($tableInfo as $field => $fieldInfo) {
				if (empty($fieldInfo['type'])) {
					continue;
				}
				$insert = '';
				switch ($fieldInfo['type']) {
					case 'integer':
					case 'float':
						$insert = $i + 1;
						break;
					case 'string':
					case 'binary':
						$isPrimaryUuid = (
							isset($fieldInfo['key']) && strtolower($fieldInfo['key']) === 'primary' &&
							isset($fieldInfo['length']) && $fieldInfo['length'] == 36
						);
						if ($isPrimaryUuid) {
							$insert = CakeText::uuid();
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
 * Convert a $records array into a string.
 *
 * @param array $records Array of records to be converted to string
 * @return string A string value of the $records array.
 */
	protected function _makeRecordString($records) {
		$out = "array(\n";
		foreach ($records as $record) {
			$values = array();
			foreach ($record as $field => $value) {
				$val = var_export($value, true);
				if ($val === 'NULL') {
					$val = 'null';
				}
				$values[] = "\t\t\t'$field' => $val";
			}
			$out .= "\t\tarray(\n";
			$out .= implode(",\n", $values);
			$out .= "\n\t\t),\n";
		}
		$out .= "\t)";
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
		$modelObject = new Model(array('name' => $modelName, 'table' => $useTable, 'ds' => $this->connection));
		if ($this->interactive) {
			$condition = null;
			$prompt = __d('cake_console', "Please provide a SQL fragment to use as conditions\nExample: WHERE 1=1");
			while (!$condition) {
				$condition = $this->in($prompt, null, 'WHERE 1=1');
			}

			$recordsFound = $modelObject->find('count', array(
				'conditions' => $condition,
				'recursive' => -1,
			));

			$prompt = __d('cake_console', "How many records do you want to import?");
			$recordCount = $this->in($prompt, null, ($recordsFound < 10 ) ? $recordsFound : 10);
		} else {
			$condition = 'WHERE 1=1';
			$recordCount = (isset($this->params['count']) ? $this->params['count'] : 10);
		}

		$records = $modelObject->find('all', array(
			'conditions' => $condition,
			'recursive' => -1,
			'limit' => $recordCount
		));

		$schema = $modelObject->schema(true);
		$out = array();
		foreach ($records as $record) {
			$row = array();
			foreach ($record[$modelObject->alias] as $field => $value) {
				if ($schema[$field]['type'] === 'boolean') {
					$value = (int)(bool)$value;
				}
				$row[$field] = $value;
			}
			$out[] = $row;
		}
		return $out;
	}

}
