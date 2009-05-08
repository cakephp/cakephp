<?php
/* SVN FILE: $Id$ */
/**
 * The FixtureTest handles creating and updating fixture files.
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) :  Rapid Development Framework (http://www.cakephp.org)
 * Copyright 2005-2008,	Cake Software Foundation, Inc.
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @filesource
 * @copyright     Copyright 2005-2008, Cake Software Foundation, Inc. (http://www.cakefoundation.org)
 * @link          http://www.cakefoundation.org/projects/info/cakephp CakePHP(tm) Project
 * @package       cake
 * @subpackage    cake.cake.console.libs.tasks
 * @since         CakePHP(tm) v 1.3
 * @version       $Revision$
 * @modifiedby    $LastChangedBy$
 * @lastmodified  $Date$
 * @license       http://www.opensource.org/licenses/mit-license.php The MIT License
 */
/**
 * Task class for creating and updating fixtures files.
 *
 * @package       cake
 * @subpackage    cake.cake.console.libs.tasks
 */
class FixtureTask extends Shell {
/**
 * Name of plugin
 *
 * @var string
 * @access public
 */
	var $plugin = null;
/**
 * Tasks to be loaded by this Task
 *
 * @var array
 * @access public
 */
	var $tasks = array('DbConfig', 'Model');
/**
 * path to fixtures directory
 *
 * @var string
 * @access public
 */
	var $path = null;
/**
 * The db connection being used for baking
 *
 * @var string
 **/
	var $connection = null;
/**
 * Override initialize
 *
 * @access public
 */
	function initialize() {
		$this->path = $this->params['working'] . DS . 'tests' . DS . 'fixtures' . DS;
		if (!class_exists('CakeSchema')) {
			App::import('Model', 'Schema');
		}
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
			if (!isset($this->connection)) {
				$this->connection = 'default';
			}
			if (strtolower($this->args[0]) == 'all') {
				return $this->all();
			}
			$model = Inflector::camelize($this->args[0]);
			$this->bake($model);
		}
	}

/**
 * Bake All the Fixtures at once.  Will only bake fixtures for models that exist.
 *
 * @access public
 * @return void
 **/
	function all() {
		$this->interactive = false;
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
		$this->interactive = true;
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
 **/
	function importOptions($modelName) {
		$options = array();
		$doSchema = $this->in('Would you like to import schema for this fixture?', array('y', 'n'), 'n');
		if ($doSchema == 'y') {
			$options['schema'] = $modelName;
		}
		$doRecords = $this->in('Would you like to import records for this fixture?', array('y', 'n'), 'n');
		if ($doRecords == 'y') {
			$options['records'] = true;
		}
		return $options;
	}

/**
 * Assembles and writes a Fixture file
 *
 * @param string $model Name of model to bake.
 * @param string $useTable Name of table to use.
 * @param array $importOptions Options for var $import
 * @return string Baked fixture
 * @access private
 */
	function bake($model, $useTable = false, $importOptions = array()) {
		$out = "class {$model}Fixture extends CakeTestFixture {\n";
		$out .= "\tvar \$name = '$model';\n";

		if (!$useTable) {
			$useTable = Inflector::tableize($model);
		} elseif ($useTable != Inflector::tableize($model)) {
			$out .= "\tvar \$table = '$useTable';\n";
		}

		$modelImport = $recordImport = null;
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
			$out .= sprintf("\tvar \$import = array(%s%s);\n", $modelImport, $recordImport);
		}

		$this->_Schema = new CakeSchema();
		$data = $this->_Schema->read(array('models' => false, 'connection' => $this->connection));

		if (!isset($data['tables'][$useTable])) {
			$this->err('Could not find your selected table ' . $useTable);
			return false;
		}

		$tableInfo = $data['tables'][$useTable];
		if (is_null($modelImport)) {
			$out .= $this->_generateSchema($tableInfo);
		}

		if (is_null($recordImport)) {
			$recordCount = 1;
			if (isset($this->params['count'])) {
				$recordCount = $this->params['count'];
			}
			$out .= $this->_generateRecords($tableInfo, $recordCount);
		}
		$out .= "}\n";
		$this->generateFixtureFile($model, $out);
		return $out;
	}

/**
 * Generate the fixture file, and write to disk
 *
 * @param string $model name of the model being generated
 * @param string $fixture Contents of the fixture file.
 * @access public
 * @return void
 **/
	function generateFixtureFile($model, $fixture) {
		//@todo fix plugin pathing.
		$path = $this->path;
		if (isset($this->plugin)) {
			$pluginPath = 'plugins' . DS . Inflector::underscore($this->plugin) . DS;
			$path = APP . $pluginPath . 'tests' . DS . 'fixtures' . DS;
		}
		$filename = Inflector::underscore($model) . '_fixture.php';
		$content = "<?php\n/* " . $model . " Fixture generated on: " . date('Y-m-d H:m:s') . " : ". time() . "*/\n";
		$content .= $fixture;
		$content .= "?>";
		$this->out("\nBaking test fixture for $model...");
		$this->createFile($path . $filename, $content);
	}

/**
 * Generates a string representation of a schema.
 *
 * @param array $table Table schema array
 * @return string fields definitions
 **/
	function _generateSchema($tableInfo) {
		$cols = array();
		$out = "\n\tvar \$fields = array(\n";
		foreach ($tableInfo as $field => $fieldInfo) {
			if (is_array($fieldInfo)) {
				if ($field != 'indexes') {
					$col = "\t\t'{$field}' => array('type'=>'" . $fieldInfo['type'] . "', ";
					$col .= join(', ',  $this->_Schema->__values($fieldInfo));
				} else {
					$col = "\t\t'indexes' => array(";
					$props = array();
					foreach ((array)$fieldInfo as $key => $index) {
						$props[] = "'{$key}' => array(".join(', ',  $this->_Schema->__values($index)).")";
					}
					$col .= join(', ', $props);
				}
				$col .= ")";
				$cols[] = $col;
			}
		}
		$out .= join(",\n", $cols);
		$out .= "\n\t);\n\n";
		return $out;
	}

/**
 * Generate String representation of Records
 *
 * @param array $table Table schema array
 * @return string
 **/
	function _generateRecords($tableInfo, $recordCount = 1) {
		$out = "\tvar \$records = array(\n";

		for ($i = 0; $i < $recordCount; $i++) {
			$records = array();
			foreach ($tableInfo as $field => $fieldInfo) {
				if (empty($fieldInfo['type'])) {
					continue;
				}
				switch ($fieldInfo['type']) {
					case 'integer':
						$insert = $i + 1;
					break;
					case 'string';
						$insert = "Lorem ipsum dolor sit amet";
						if (!empty($fieldInfo['length'])) {
							 $insert = substr($insert, 0, (int)$fieldInfo['length'] - 2);
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
				$records[] = "\t\t\t'$field'  => $insert";
			}
			$out .= "\t\tarray(\n";
			$out .= implode(",\n", $records);
			$out .= "\n\t\t),\n";
		}
		$out .= "\t);\n";
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
		$this->out('Commands:');
		$this->out("\nfixture <name>\n\tbakes fixture with specified name.");
		$this->out("\nfixture all\n\tbakes all fixtures.");
		$this->out("");
		$this->out('Parameters:');
		$this->out("\t-count        The number of records to include in the fixture(s).");
		$this->out("\t-connection   Which database configuration to use for baking.");
		$this->out("\t-plugin       lowercased_underscored name of plugin to bake fixtures for.");
		$this->out("");
		$this->_stop();
	}
}
?>
