<?php
/**
 * The FixtureTask handles creating and updating fixture files.
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2011, Cake Software Foundation, Inc.
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2011, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       cake
 * @subpackage    cake.cake.console.libs.tasks
 * @since         CakePHP(tm) v 1.3
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
include_once dirname(__FILE__) . DS . 'bake.php';
/**
 * Task class for creating and updating fixtures files.
 *
 * @package       cake
 * @subpackage    cake.cake.console.libs.tasks
 */
class FixtureTask extends BakeTask {

/**
 * Tasks to be loaded by this Task
 *
 * @var array
 * @access public
 */
	var $tasks = array('DbConfig', 'Model', 'Template');

/**
 * path to fixtures directory
 *
 * @var string
 * @access public
 */
	var $path = null;

/**
 * Schema instance
 *
 * @var object
 * @access protected
 */
	var $_Schema = null;

/**
 * Override initialize
 *
 * @access public
 */
	function __construct(&$dispatch) {
		parent::__construct($dispatch);
		$this->path = $this->params['working'] . DS . 'tests' . DS . 'fixtures' . DS;
	}

/**
 * Execution method always used for tasks
 * Handles dispatching to interactive, named, or all processess.
 *
 * @access public
 */
	function execute() {
		if (empty($this->args)) {
			$this->__interactive();
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
 * @access public
 * @return void
 */
	function all() {
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
 * @access private
 */
	function __interactive() {
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
 * @access public
 */
	function importOptions($modelName) {
		$options = array();
		$doSchema = $this->in(__('Would you like to import schema for this fixture?', true), array('y', 'n'), 'n');
		if ($doSchema == 'y') {
			$options['schema'] = $modelName;
		}
		$doRecords = $this->in(__('Would you like to use record importing for this fixture?', true), array('y', 'n'), 'n');
		if ($doRecords == 'y') {
			$options['records'] = true;
		}
		if ($doRecords == 'n') {
			$prompt = sprintf(__("Would you like to build this fixture with data from %s's table?", true), $modelName);
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
 * @param array $importOptions Options for var $import
 * @return string Baked fixture content
 * @access public
 */
	function bake($model, $useTable = false, $importOptions = array()) {
		if (!class_exists('CakeSchema')) {
			App::import('Model', 'CakeSchema', false);
		}
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
			if ($this->connection != 'default') {
				$importBits[] .= "'connection' => '{$this->connection}'";
			}
			if (!empty($importBits)) {
				$import = sprintf("array(%s)", implode(', ', $importBits));
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
 * @access public
 */
	function generateFixtureFile($model, $otherVars) {
		$defaults = array('table' => null, 'schema' => null, 'records' => null, 'import' => null, 'fields' => null);
		$vars = array_merge($defaults, $otherVars);

		$path = $this->getPath();
		$filename = Inflector::underscore($model) . '_fixture.php';

		$this->Template->set('model', $model);
		$this->Template->set($vars);
		$content = $this->Template->generate('classes', 'fixture');

		$this->out("\nBaking test fixture for $model...");
		$this->createFile($path . $filename, $content);
		return $content;
	}

/**
 * Get the path to the fixtures.
 *
 * @return void
 */
	function getPath() {
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
 * @access protected
 */
	function _generateSchema($tableInfo) {
		$schema = $this->_Schema->generateTable('f', $tableInfo);
		return substr($schema, 10, -2);
	}

/**
 * Generate String representation of Records
 *
 * @param array $table Table schema array
 * @return array Array of records to use in the fixture.
 * @access protected
 */
	function _generateRecords($tableInfo, $recordCount = 1) {
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
 * @access protected
 */
	function _makeRecordString($records) {
		$out = "array(\n";
		foreach ($records as $record) {
			$values = array();
			foreach ($record as $field => $value) {
				$val = var_export($value, true);
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
 * @access protected
 */
	function _getRecordsFromTable($modelName, $useTable = null) {
		if ($this->interactive) {
			$condition = null;
			$prompt = __("Please provide a SQL fragment to use as conditions\nExample: WHERE 1=1 LIMIT 10", true);
			while (!$condition) {
				$condition = $this->in($prompt, null, 'WHERE 1=1 LIMIT 10');
			}
		} else {
			$condition = 'WHERE 1=1 LIMIT ' . (isset($this->params['count']) ? $this->params['count'] : 10);
		}
		App::import('Model', 'Model', false);
		$modelObject =& new Model(array('name' => $modelName, 'table' => $useTable, 'ds' => $this->connection));
		$records = $modelObject->find('all', array(
			'conditions' => $condition,
			'recursive' => -1
		));
		$db =& ConnectionManager::getDataSource($modelObject->useDbConfig);
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

/**
 * Displays help contents
 *
 * @access public
 */
	function help() {
		$this->hr();
		$this->out("Usage: cake bake fixture <arg1> <params>");
		$this->hr();
		$this->out('Arguments:');
		$this->out();
		$this->out("<name>");
		$this->out("\tName of the fixture to bake. Can use Plugin.name");
		$this->out("\tas a shortcut for plugin baking.");
		$this->out();
		$this->out('Commands:');
		$this->out("\nfixture <name>\n\tbakes fixture with specified name.");
		$this->out("\nfixture all\n\tbakes all fixtures.");
		$this->out();
		$this->out('Parameters:');
		$this->out("\t-count       When using generated data, the number of records to include in the fixture(s).");
		$this->out("\t-connection  Which database configuration to use for baking.");
		$this->out("\t-plugin      CamelCased name of plugin to bake fixtures for.");
		$this->out("\t-records     Used with -count and <name>/all commands to pull [n] records from the live tables");
		$this->out("\t             Where [n] is either -count or the default of 10.");
		$this->out();
		$this->_stop();
	}
}
