<?php
/**
 * The FixtureTask handles creating and updating fixture files.
 *
 * PHP 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2010, Cake Software Foundation, Inc.
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       cake.console.shells.tasks
 * @since         CakePHP(tm) v 1.3
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
include_once dirname(__FILE__) . DS . 'bake.php';
/**
 * Task class for creating and updating fixtures files.
 *
 * @package       cake.console.shells.tasks
 */
class FixtureTask extends BakeTask {

/**
 * Tasks to be loaded by this Task
 *
 * @var array
 * @access public
 */
	public $tasks = array('DbConfig', 'Model', 'Template');

/**
 * path to fixtures directory
 *
 * @var string
 * @access public
 */
	public $path = null;

/**
 * Schema instance
 *
 * @var object
 * @access protected
 */
	protected $_Schema = null;

/**
 * Override initialize
 *
 */
	public function __construct($stdout = null, $stderr = null, $stdin = null) {
		parent::__construct($stdout, $stderr, $stdin);
		$this->path = APP . 'tests' . DS . 'fixtures' . DS;
	}

/**
 * get the option parser.
 *
 * @return void
 */
	public function getOptionParser() {
		$parser = parent::getOptionParser();
		return $parser->description(
			__('Generate fixtures for use with the test suite. You can use `bake fixture all` to bake all fixtures.')
		)->addArgument('name', array(
			'help' => __('Name of the fixture to bake. Can use Plugin.name to bake plugin fixtures.')
		))->addOption('count', array(
			'help' => __('When using generated data, the number of records to include in the fixture(s).'),
			'short' => 'n',
			'default' => 10
		))->addOption('connection', array(
			'help' => __('Which database configuration to use for baking.'),
			'short' => 'c',
			'default' => 'default'
		))->addOption('plugin', array(
			'help' => __('CamelCased name of the plugin to bake fixtures for.'),
			'short' => 'p',
		))->addOption('records', array(
			'help' => 'Used with --count and <name>/all commands to pull [n] records from the live tables, where [n] is either --count or the default of 10',
			'short' => 'r',
			'boolean' => true
		))->epilog(__('Omitting all arguments and options will enter into an interactive mode.'));;
	}

/**
 * Execution method always used for tasks
 * Handles dispatching to interactive, named, or all processess.
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
			if (strtolower($this->args[0]) == 'all') {
				return $this->all();
			}
			$model = $this->_modelName($this->args[0]);
			$this->bake($model);
		}
	}

/**
 * Bake All the Fixtures at once.  Will only bake fixtures for models that exist.
 *
 * @return void
 */
	public function all() {
		$this->interactive = false;
		$this->Model->interactive = false;
		$tables = $this->Model->listAll($this->connection, false);
		foreach ($tables as $table) {
			$model = $this->_modelName($table);
			$this->bake($model);
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
		$this->out(sprintf("Bake Fixture\nPath: %s", $this->path));
		$this->hr();

		$useDbConfig = $this->connection;
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
		$doSchema = $this->in(__('Would you like to import schema for this fixture?'), array('y', 'n'), 'n');
		if ($doSchema == 'y') {
			$options['schema'] = $modelName;
		}
		$doRecords = $this->in(__('Would you like to use record importing for this fixture?'), array('y', 'n'), 'n');
		if ($doRecords == 'y') {
			$options['records'] = true;
		}
		if ($doRecords == 'n') {
			$prompt = __("Would you like to build this fixture with data from %s's table?", $modelName);
			$fromTable = $this->in($prompt, array('y', 'n'), 'n');
			if (strtolower($fromTable) == 'y') {
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
 * @return string Baked fixture content
 */
	public function bake($model, $useTable = false, $importOptions = array()) {
		if (!class_exists('CakeSchema')) {
			App::import('Model', 'CakeSchema', false);
		}
		$table = $schema = $records = $import = $modelImport = $recordImport = null;
		if (!$useTable) {
			$useTable = Inflector::tableize($model);
		} elseif ($useTable != Inflector::tableize($model)) {
			$table = $useTable;
		}

		if (!empty($importOptions)) {
			if (isset($importOptions['schema'])) {
				$modelImport = "'model' => '{$importOptions['schema']}'";
			}
			if (isset($importOptions['records'])) {
				$recordImport = "'records' => true";
			}
			if ($modelImport && $recordImport) {
				$modelImport .= ', ';
			}
			if (!empty($modelImport) || !empty($recordImport)) {
				$import = sprintf("array(%s%s)", $modelImport, $recordImport);
			}
		}

		$this->_Schema = new CakeSchema();
		$data = $this->_Schema->read(array('models' => false, 'connection' => $this->connection));
		if (!isset($data['tables'][$useTable])) {
			$this->err('Could not find your selected table ' . $useTable);
			return false;
		}

		$tableInfo = $data['tables'][$useTable];
		if (is_null($modelImport)) {
			$schema = $this->_generateSchema($tableInfo);
		}

		if (!isset($importOptions['records']) && !isset($importOptions['fromTable'])) {
			$recordCount = 1;
			if (isset($this->params['count'])) {
				$recordCount = $this->params['count'];
			}
			$records = $this->_makeRecordString($this->_generateRecords($tableInfo, $recordCount));
		}
		if (isset($this->params['records']) || isset($importOptions['fromTable'])) {
			$records = $this->_makeRecordString($this->_getRecordsFromTable($model, $useTable));
		}
		$out = $this->generateFixtureFile($model, compact('records', 'table', 'schema', 'import', 'fields'));
		return $out;
	}

/**
 * Generate the fixture file, and write to disk
 *
 * @param string $model name of the model being generated
 * @param string $fixture Contents of the fixture file.
 * @return string Content saved into fixture file.
 */
	public function generateFixtureFile($model, $otherVars) {
		$defaults = array('table' => null, 'schema' => null, 'records' => null, 'import' => null, 'fields' => null);
		$vars = array_merge($defaults, $otherVars);

		$path = $this->getPath();
		$filename = Inflector::underscore($model) . '_fixture.php';

		$this->Template->set('model', $model);
		$this->Template->set($vars);
		$content = $this->Template->generate('classes', 'fixture');

		$this->out("\nBaking test fixture for $model...", 1, Shell::QUIET);
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
			$path = $this->_pluginPath($this->plugin) . 'tests' . DS . 'fixtures' . DS;
		}
		return $path;
	}

/**
 * Generates a string representation of a schema.
 *
 * @param array $table Table schema array
 * @return string fields definitions
 */
	protected function _generateSchema($tableInfo) {
		$schema = $this->_Schema->generateTable('f', $tableInfo);
		return substr($schema, 10, -2);
	}

/**
 * Generate String representation of Records
 *
 * @param array $table Table schema array
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
				switch ($fieldInfo['type']) {
					case 'integer':
					case 'float':
						$insert = $i + 1;
					break;
					case 'string':
					case 'binary':
						$isPrimaryUuid = (
							isset($fieldInfo['key']) && strtolower($fieldInfo['key']) == 'primary' &&
							isset($fieldInfo['length']) && $fieldInfo['length'] == 36
						);
						if ($isPrimaryUuid) {
							$insert = String::uuid();
						} else {
							$insert = "Lorem ipsum dolor sit amet";
							if (!empty($fieldInfo['length'])) {
								 $insert = substr($insert, 0, (int)$fieldInfo['length'] - 2);
							}
						}
						$insert = "'$insert'";
					break;
					case 'timestamp':
						$ts = time();
						$insert = "'$ts'";
					break;
					case 'datetime':
						$ts = date('Y-m-d H:i:s');
						$insert = "'$ts'";
					break;
					case 'date':
						$ts = date('Y-m-d');
						$insert = "'$ts'";
					break;
					case 'time':
						$ts = date('H:i:s');
						$insert = "'$ts'";
					break;
					case 'boolean':
						$insert = 1;
					break;
					case 'text':
						$insert = "'Lorem ipsum dolor sit amet, aliquet feugiat.";
						$insert .= " Convallis morbi fringilla gravida,";
						$insert .= " phasellus feugiat dapibus velit nunc, pulvinar eget sollicitudin";
						$insert .= " venenatis cum nullam, vivamus ut a sed, mollitia lectus. Nulla";
						$insert .= " vestibulum massa neque ut et, id hendrerit sit,";
						$insert .= " feugiat in taciti enim proin nibh, tempor dignissim, rhoncus";
						$insert .= " duis vestibulum nunc mattis convallis.'";
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
		$out = "array(\n";
		foreach ($records as $record) {
			$values = array();
			foreach ($record as $field => $value) {
				$values[] = "\t\t\t'$field' => $value";
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
		if ($this->interactive) {
			$condition = null;
			$prompt = __("Please provide a SQL fragment to use as conditions\nExample: WHERE 1=1 LIMIT 10");
			while (!$condition) {
				$condition = $this->in($prompt, null, 'WHERE 1=1 LIMIT 10');
			}
		} else {
			$condition = 'WHERE 1=1 LIMIT ' . (isset($this->params['count']) ? $this->params['count'] : 10);
		}
		App::import('Model', 'Model', false);
		$modelObject = new Model(array('name' => $modelName, 'table' => $useTable, 'ds' => $this->connection));
		$records = $modelObject->find('all', array(
			'conditions' => $condition,
			'recursive' => -1
		));
		$db = ConnectionManager::getDataSource($modelObject->useDbConfig);
		$schema = $modelObject->schema(true);
		$out = array();
		foreach ($records as $record) {
			$row = array();
			foreach ($record[$modelObject->alias] as $field => $value) {
				$row[$field] = $db->value($value, $schema[$field]['type']);
			}
			$out[] = $row;
		}
		return $out;
	}

}
