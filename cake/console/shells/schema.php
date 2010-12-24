<?php
/**
 * Command-line database management utility to automate programmer chores.
 *
 * Schema is CakePHP's database management utility. This helps you maintain versions of
 * of your database.
 *
 * PHP 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       cake.console.shells
 * @since         CakePHP(tm) v 1.2.0.5550
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
App::import('Core', 'File', false);
App::import('Model', 'CakeSchema', false);

/**
 * Schema is a command-line database management utility for automating programmer chores.
 *
 * @package       cake.console.shells
 * @link          http://book.cakephp.org/view/1523/Schema-management-and-migrations
 */
class SchemaShell extends Shell {

/**
 * is this a dry run?
 *
 * @var boolean
 * @access private
 */
	private $__dry = null;

/**
 * Schema class being used.
 *
 * @var CakeSchema
 */
	public $Schema;

/**
 * Override initialize
 *
 */
	public function initialize() {
		$this->_welcome();
		$this->out('Cake Schema Shell');
		$this->hr();
	}

/**
 * Override startup
 *
 */
	public function startup() {
		$name = $file = $path = $connection = $plugin = null;
		if (!empty($this->params['name'])) {
			$name = $this->params['name'];
		} elseif (!empty($this->args[0])) {
			$name = $this->params['name'] = $this->args[0];
		}

		if (strpos($name, '.')) {
			list($this->params['plugin'], $splitName) = pluginSplit($name);
			$name = $this->params['name'] = $splitName;
		}

		if ($name) {
			$this->params['file'] = Inflector::underscore($name);
		}

		if (empty($this->params['file'])) {
			$this->params['file'] = 'schema.php';
		}
		if (strpos($this->params['file'], '.php') === false) {
			$this->params['file'] .= '.php';
		}
		$file = $this->params['file'];

		if (!empty($this->params['path'])) {
			$path = $this->params['path'];
		}

		if (!empty($this->params['connection'])) {
			$connection = $this->params['connection'];
		}
		if (!empty($this->params['plugin'])) {
			$plugin = $this->params['plugin'];
			if (empty($name)) {
				$name = $plugin;
			}
		}
		$this->Schema = new CakeSchema(compact('name', 'path', 'file', 'connection', 'plugin'));
	}

/**
 * Read and output contents of schema object
 * path to read as second arg
 *
 */
	public function view() {
		$File = new File($this->Schema->path . DS . $this->params['file']);
		if ($File->exists()) {
			$this->out($File->read());
			$this->_stop();
		} else {
			$file = $this->Schema->path . DS . $this->params['file'];
			$this->err(__('Schema file (%s) could not be found.', $file));
			$this->_stop();
		}
	}

/**
 * Read database and Write schema object
 * accepts a connection as first arg or path to save as second arg
 *
 */
	public function generate() {
		$this->out(__('Generating Schema...'));
		$options = array();
		if (isset($this->params['force'])) {
			$options = array('models' => false);
		}

		$snapshot = false;
		if (isset($this->args[0]) && $this->args[0] === 'snapshot') {
			$snapshot = true;
		}

		if (!$snapshot && file_exists($this->Schema->path . DS . $this->params['file'])) {
			$snapshot = true;
			$result = strtolower($this->in("Schema file exists.\n [O]verwrite\n [S]napshot\n [Q]uit\nWould you like to do?", array('o', 's', 'q'), 's'));
			if ($result === 'q') {
				return $this->_stop();
			}
			if ($result === 'o') {
				$snapshot = false;
			}
		}

		$content = $this->Schema->read($options);
		$content['file'] = $this->params['file'];

		if ($snapshot === true) {
			$Folder = new Folder($this->Schema->path);
			$result = $Folder->read();

			$numToUse = false;
			if (isset($this->params['snapshot'])) {
				$numToUse = $this->params['snapshot'];
			}

			$count = 0;
			if (!empty($result[1])) {
				foreach ($result[1] as $file) {
					if (preg_match('/schema(?:[_\d]*)?\.php$/', $file)) {
						$count++;
					}
				}
			}

			if ($numToUse !== false) {
				if ($numToUse > $count) {
					$count = $numToUse;
				}
			}

			$fileName = rtrim($this->params['file'], '.php');
			$content['file'] = $fileName . '_' . $count . '.php';
		}

		if ($this->Schema->write($content)) {
			$this->out(__('Schema file: %s generated', $content['file']));
			$this->_stop();
		} else {
			$this->err(__('Schema file: %s generated'));
			$this->_stop();
		}
	}

/**
 * Dump Schema object to sql file
 * Use the `write` param to enable and control SQL file output location.
 * Simply using -write will write the sql file to the same dir as the schema file.
 * If -write contains a full path name the file will be saved there. If -write only
 * contains no DS, that will be used as the file name, in the same dir as the schema file.
 *
 */
	public function dump() {
		$write = false;
		$Schema = $this->Schema->load();
		if (!$Schema) {
			$this->err(__('Schema could not be loaded'));
			$this->_stop();
		}
		if (!empty($this->params['write'])) {
			if ($this->params['write'] == 1) {
				$write = Inflector::underscore($this->Schema->name);
			} else {
				$write = $this->params['write'];
			}
		}
		$db = ConnectionManager::getDataSource($this->Schema->connection);
		$contents = "#" . $Schema->name . " sql generated on: " . date('Y-m-d H:i:s') . " : " . time() . "\n\n";
		$contents .= $db->dropSchema($Schema) . "\n\n". $db->createSchema($Schema);

		if ($write) {
			if (strpos($write, '.sql') === false) {
				$write .= '.sql';
			}
			if (strpos($write, DS) !== false) {
				$File = new File($write, true);
			} else {
				$File = new File($this->Schema->path . DS . $write, true);
			}

			if ($File->write($contents)) {
				$this->out(__('SQL dump file created in %s', $File->pwd()));
				$this->_stop();
			} else {
				$this->err(__('SQL dump could not be created'));
				$this->_stop();
			}
		}
		$this->out($contents);
		return $contents;
	}

/**
 * Run database create commands.  Alias for run create.
 *
 * @return void
 */
	function create() {
		list($Schema, $table) = $this->_loadSchema();
		$this->__create($Schema, $table);
	}

/**
 * Run database create commands.  Alias for run create.
 *
 * @return void
 */
	function update() {
		list($Schema, $table) = $this->_loadSchema();
		$this->__update($Schema, $table);
	}

/**
 * Prepares the Schema objects for database operations.
 *
 * @return void
 */
	function _loadSchema() {
		$name = $plugin = null;
		if (!empty($this->params['name'])) {
			$name = $this->params['name'];
		}
		if (!empty($this->params['plugin'])) {
			$plugin = $this->params['plugin'];
		}
		
		if (!empty($this->params['dry'])) {
			$this->__dry = true;
			$this->out(__('Performing a dry run.'));
		}

		$options = array('name' => $name, 'plugin' => $plugin);
		if (!empty($this->params['snapshot'])) {
			$fileName = rtrim($this->Schema->file, '.php');
			$options['file'] = $fileName . '_' . $this->params['snapshot'] . '.php';
		}

		$Schema = $this->Schema->load($options);

		if (!$Schema) {
			$this->err(__('%s could not be loaded', $this->Schema->path . DS . $this->Schema->file));
			$this->_stop();
		}
		$table = null;
		if (isset($this->args[1])) {
			$table = $this->args[1];
		}
		return array(&$Schema, $table);
	}

/**
 * Create database from Schema object
 * Should be called via the run method
 *
 * @access private
 */
	function __create($Schema, $table = null) {
		$db = ConnectionManager::getDataSource($this->Schema->connection);

		$drop = $create = array();

		if (!$table) {
			foreach ($Schema->tables as $table => $fields) {
				$drop[$table] = $db->dropSchema($Schema, $table);
				$create[$table] = $db->createSchema($Schema, $table);
			}
		} elseif (isset($Schema->tables[$table])) {
			$drop[$table] = $db->dropSchema($Schema, $table);
			$create[$table] = $db->createSchema($Schema, $table);
		}
		if (empty($drop) || empty($create)) {
			$this->out(__('Schema is up to date.'));
			$this->_stop();
		}

		$this->out("\n" . __('The following table(s) will be dropped.'));
		$this->out(array_keys($drop));

		if ('y' == $this->in(__('Are you sure you want to drop the table(s)?'), array('y', 'n'), 'n')) {
			$this->out(__('Dropping table(s).'));
			$this->__run($drop, 'drop', $Schema);
		}

		$this->out("\n" . __('The following table(s) will be created.'));
		$this->out(array_keys($create));

		if ('y' == $this->in(__('Are you sure you want to create the table(s)?'), array('y', 'n'), 'y')) {
			$this->out(__('Creating table(s).'));
			$this->__run($create, 'create', $Schema);
		}
		$this->out(__('End create.'));
	}

/**
 * Update database with Schema object
 * Should be called via the run method
 *
 * @access private
 */
	function __update(&$Schema, $table = null) {
		$db = ConnectionManager::getDataSource($this->Schema->connection);

		$this->out(__('Comparing Database to Schema...'));
		$options = array();
		if (isset($this->params['force'])) {
			$options['models'] = false;
		}
		$Old = $this->Schema->read($options);
		$compare = $this->Schema->compare($Old, $Schema);

		$contents = array();

		if (empty($table)) {
			foreach ($compare as $table => $changes) {
				$contents[$table] = $db->alterSchema(array($table => $changes), $table);
			}
		} elseif (isset($compare[$table])) {
			$contents[$table] = $db->alterSchema(array($table => $compare[$table]), $table);
		}

		if (empty($contents)) {
			$this->out(__('Schema is up to date.'));
			$this->_stop();
		}

		$this->out("\n" . __('The following statements will run.'));
		$this->out(array_map('trim', $contents));
		if ('y' == $this->in(__('Are you sure you want to alter the tables?'), array('y', 'n'), 'n')) {
			$this->out();
			$this->out(__('Updating Database...'));
			$this->__run($contents, 'update', $Schema);
		}

		$this->out(__('End update.'));
	}

/**
 * Runs sql from __create() or __update()
 *
 * @access private
 */
	function __run($contents, $event, &$Schema) {
		if (empty($contents)) {
			$this->err(__('Sql could not be run'));
			return;
		}
		Configure::write('debug', 2);
		$db = ConnectionManager::getDataSource($this->Schema->connection);

		foreach ($contents as $table => $sql) {
			if (empty($sql)) {
				$this->out(__('%s is up to date.', $table));
			} else {
				if ($this->__dry === true) {
					$this->out(__('Dry run for %s :', $table));
					$this->out($sql);
				} else {
					if (!$Schema->before(array($event => $table))) {
						return false;
					}
					$error = null;
					if (!$db->execute($sql)) {
						$error = $table . ': '  . $db->lastError();
					}

					$Schema->after(array($event => $table, 'errors' => $error));

					if (!empty($error)) {
						$this->out($error);
					} else {
						$this->out(__('%s updated.', $table));
					}
				}
			}
		}
	}

/**
 * get the option parser
 *
 * @return void
 */
	public function getOptionParser() {
		$plugin = array(
			'help' => __('The plugin to use.'),
		);
		$connection = array(
			'help' => __('Set the db config to use.'),
			'default' => 'default'
		);
		$path = array(
			'help' => __('Path to read and write schema.php'),
			'default' => CONFIGS . 'schema'
		);
		$file = array(
			'help' => __('File name to read and write.'),
			'default' => 'schema.php'
		);
		$name = array(
			'help' => __('Classname to use. If its Plugin.class, both name and plugin options will be set.')
		);
		$snapshot = array(
			'short' => 's',
			'help' => __('Snapshot number to use/make.')
		);
		$dry = array(
			'help' => 'Perform a dry run on create and update commands. Queries will be output instead of run.',
			'boolean' => true
		);
		$force = array(
			'short' => 'f',
			'help' => __('Force "generate" to create a new schema'),
			'boolean' => true
		);
		$write = array(
			'help' => __('Write the dumped SQL to a file.')
		);
		
		$parser = parent::getOptionParser();
		$parser->description(
			'The Schema Shell generates a schema object from' .
			'the database and updates the database from the schema.'
		)->addSubcommand('view', array(
			'help' => 'read and output the contents of a schema file',
			'parser' => array(
				'options' => compact('plugin', 'path', 'file', 'name', 'connection'),
				'arguments' => compact('name')
			)
		))->addSubcommand('generate', array(
			'help' => __('Reads from --connection and writes to --path. Generate snapshots with -s'),
			'parser' => array(
				'options' => compact('plugin', 'path', 'file', 'name', 'connection', 'snapshot', 'force'),
				'arguments' => array(
					'snapshot' => array('help' => __('Generate a snapshot.'))
				)
			)
		))->addSubcommand('dump', array(
			'help' => __('Dump database SQL based on a schema file to stdout.'),
			'parser' => array(
				'options' => compact('plugin', 'path', 'file', 'name', 'connection'),
				'arguments' => compact('name')
			)
		))->addSubcommand('create', array(
			'help' => __('Drop and create tables based on the schema file.'),
			'parser' => array(
				'options' => compact('plugin', 'path', 'file', 'name', 'connection', 'dry', 'snapshot'),
				'args' => array(
					'name' => array(
						'help' => __('Name of schema to use.')
					),
					'table' => array(
						'help' => __('Only create the specified table.')
					)
				)
			)
		))->addSubcommand('update', array(
			'help' => __('Alter the tables based on the schema file.'),
			'parser' => array(
				'options' => compact('plugin', 'path', 'file', 'name', 'connection', 'dry', 'snapshot'),
				'args' => array(
					'name' => array(
						'help' => __('Name of schema to use.')
					),
					'table' => array(
						'help' => __('Only create the specified table.')
					)
				)
			)
		));
		return $parser;
	}
}
