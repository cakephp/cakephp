<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         CakePHP(tm) v 1.2.0.5550
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

App::uses('AppShell', 'Console/Command');
App::uses('File', 'Utility');
App::uses('Folder', 'Utility');
App::uses('CakeSchema', 'Model');

/**
 * Schema is a command-line database management utility for automating programmer chores.
 *
 * Schema is CakePHP's database management utility. This helps you maintain versions of
 * of your database.
 *
 * @package       Cake.Console.Command
 * @link          http://book.cakephp.org/2.0/en/console-and-shells/schema-management-and-migrations.html
 */
class SchemaShell extends AppShell {

/**
 * Schema class being used.
 *
 * @var CakeSchema
 */
	public $Schema;

/**
 * is this a dry run?
 *
 * @var boolean
 */
	protected $_dry = null;

/**
 * Override startup
 *
 * @return void
 */
	public function startup() {
		$this->_welcome();
		$this->out('Cake Schema Shell');
		$this->hr();

		$name = $path = $connection = $plugin = null;
		if (!empty($this->params['name'])) {
			$name = $this->params['name'];
		} elseif (!empty($this->args[0]) && $this->args[0] !== 'snapshot') {
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
		$name = Inflector::classify($name);
		$this->Schema = new CakeSchema(compact('name', 'path', 'file', 'connection', 'plugin'));
	}

/**
 * Read and output contents of schema object
 * path to read as second arg
 *
 * @return void
 */
	public function view() {
		$File = new File($this->Schema->path . DS . $this->params['file']);
		if ($File->exists()) {
			$this->out($File->read());
			$this->_stop();
		} else {
			$file = $this->Schema->path . DS . $this->params['file'];
			$this->err(__d('cake_console', 'Schema file (%s) could not be found.', $file));
			$this->_stop();
		}
	}

/**
 * Read database and Write schema object
 * accepts a connection as first arg or path to save as second arg
 *
 * @return void
 */
	public function generate() {
		$this->out(__d('cake_console', 'Generating Schema...'));
		$options = array();
		if ($this->params['force']) {
			$options['models'] = false;
		} elseif (!empty($this->params['models'])) {
			$options['models'] = String::tokenize($this->params['models']);
		}

		$snapshot = false;
		if (isset($this->args[0]) && $this->args[0] === 'snapshot') {
			$snapshot = true;
		}

		if (!$snapshot && file_exists($this->Schema->path . DS . $this->params['file'])) {
			$snapshot = true;
			$prompt = __d('cake_console', "Schema file exists.\n [O]verwrite\n [S]napshot\n [Q]uit\nWould you like to do?");
			$result = strtolower($this->in($prompt, array('o', 's', 'q'), 's'));
			if ($result === 'q') {
				return $this->_stop();
			}
			if ($result === 'o') {
				$snapshot = false;
			}
		}

		$cacheDisable = Configure::read('Cache.disable');
		Configure::write('Cache.disable', true);

		$content = $this->Schema->read($options);
		$content['file'] = $this->params['file'];

		Configure::write('Cache.disable', $cacheDisable);

		if ($snapshot === true) {
			$fileName = rtrim($this->params['file'], '.php');
			$Folder = new Folder($this->Schema->path);
			$result = $Folder->read();

			$numToUse = false;
			if (isset($this->params['snapshot'])) {
				$numToUse = $this->params['snapshot'];
			}

			$count = 0;
			if (!empty($result[1])) {
				foreach ($result[1] as $file) {
					if (preg_match('/' . preg_quote($fileName) . '(?:[_\d]*)?\.php$/', $file)) {
						$count++;
					}
				}
			}

			if ($numToUse !== false) {
				if ($numToUse > $count) {
					$count = $numToUse;
				}
			}

			$content['file'] = $fileName . '_' . $count . '.php';
		}

		if ($this->Schema->write($content)) {
			$this->out(__d('cake_console', 'Schema file: %s generated', $content['file']));
			$this->_stop();
		} else {
			$this->err(__d('cake_console', 'Schema file: %s generated'));
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
 * @return string
 */
	public function dump() {
		$write = false;
		$Schema = $this->Schema->load();
		if (!$Schema) {
			$this->err(__d('cake_console', 'Schema could not be loaded'));
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
		$contents = "\n\n" . $db->dropSchema($Schema) . "\n\n" . $db->createSchema($Schema);

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
				$this->out(__d('cake_console', 'SQL dump file created in %s', $File->pwd()));
				$this->_stop();
			} else {
				$this->err(__d('cake_console', 'SQL dump could not be created'));
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
	public function create() {
		list($Schema, $table) = $this->_loadSchema();
		$this->_create($Schema, $table);
	}

/**
 * Run database create commands.  Alias for run create.
 *
 * @return void
 */
	public function update() {
		list($Schema, $table) = $this->_loadSchema();
		$this->_update($Schema, $table);
	}

/**
 * Prepares the Schema objects for database operations.
 *
 * @return void
 */
	protected function _loadSchema() {
		$name = $plugin = null;
		if (!empty($this->params['name'])) {
			$name = $this->params['name'];
		}
		if (!empty($this->params['plugin'])) {
			$plugin = $this->params['plugin'];
		}

		if (!empty($this->params['dry'])) {
			$this->_dry = true;
			$this->out(__d('cake_console', 'Performing a dry run.'));
		}

		$options = array('name' => $name, 'plugin' => $plugin);
		if (!empty($this->params['snapshot'])) {
			$fileName = rtrim($this->Schema->file, '.php');
			$options['file'] = $fileName . '_' . $this->params['snapshot'] . '.php';
		}

		$Schema = $this->Schema->load($options);

		if (!$Schema) {
			$this->err(__d('cake_console', 'The chosen schema could not be loaded. Attempted to load:'));
			$this->err(__d('cake_console', 'File: %s', $this->Schema->path . DS . $this->Schema->file));
			$this->err(__d('cake_console', 'Name: %s', $this->Schema->name));
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
 * @param CakeSchema $Schema
 * @param string $table
 * @return void
 */
	protected function _create($Schema, $table = null) {
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
			$this->out(__d('cake_console', 'Schema is up to date.'));
			$this->_stop();
		}

		$this->out("\n" . __d('cake_console', 'The following table(s) will be dropped.'));
		$this->out(array_keys($drop));

		if ('y' == $this->in(__d('cake_console', 'Are you sure you want to drop the table(s)?'), array('y', 'n'), 'n')) {
			$this->out(__d('cake_console', 'Dropping table(s).'));
			$this->_run($drop, 'drop', $Schema);
		}

		$this->out("\n" . __d('cake_console', 'The following table(s) will be created.'));
		$this->out(array_keys($create));

		if ('y' == $this->in(__d('cake_console', 'Are you sure you want to create the table(s)?'), array('y', 'n'), 'y')) {
			$this->out(__d('cake_console', 'Creating table(s).'));
			$this->_run($create, 'create', $Schema);
		}
		$this->out(__d('cake_console', 'End create.'));
	}

/**
 * Update database with Schema object
 * Should be called via the run method
 *
 * @param CakeSchema $Schema
 * @param string $table
 * @return void
 */
	protected function _update(&$Schema, $table = null) {
		$db = ConnectionManager::getDataSource($this->Schema->connection);

		$this->out(__d('cake_console', 'Comparing Database to Schema...'));
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
			$this->out(__d('cake_console', 'Schema is up to date.'));
			$this->_stop();
		}

		$this->out("\n" . __d('cake_console', 'The following statements will run.'));
		$this->out(array_map('trim', $contents));
		if ('y' == $this->in(__d('cake_console', 'Are you sure you want to alter the tables?'), array('y', 'n'), 'n')) {
			$this->out();
			$this->out(__d('cake_console', 'Updating Database...'));
			$this->_run($contents, 'update', $Schema);
		}

		$this->out(__d('cake_console', 'End update.'));
	}

/**
 * Runs sql from _create() or _update()
 *
 * @param array $contents
 * @param string $event
 * @param CakeSchema $Schema
 * @return void
 */
	protected function _run($contents, $event, &$Schema) {
		if (empty($contents)) {
			$this->err(__d('cake_console', 'Sql could not be run'));
			return;
		}
		Configure::write('debug', 2);
		$db = ConnectionManager::getDataSource($this->Schema->connection);

		foreach ($contents as $table => $sql) {
			if (empty($sql)) {
				$this->out(__d('cake_console', '%s is up to date.', $table));
			} else {
				if ($this->_dry === true) {
					$this->out(__d('cake_console', 'Dry run for %s :', $table));
					$this->out($sql);
				} else {
					if (!$Schema->before(array($event => $table))) {
						return false;
					}
					$error = null;
					try {
						$db->execute($sql);
					} catch (PDOException $e) {
						$error = $table . ': ' . $e->getMessage();
					}

					$Schema->after(array($event => $table, 'errors' => $error));

					if (!empty($error)) {
						$this->err($error);
					} else {
						$this->out(__d('cake_console', '%s updated.', $table));
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
			'short' => 'p',
			'help' => __d('cake_console', 'The plugin to use.'),
		);
		$connection = array(
			'short' => 'c',
			'help' => __d('cake_console', 'Set the db config to use.'),
			'default' => 'default'
		);
		$path = array(
			'help' => __d('cake_console', 'Path to read and write schema.php'),
			'default' => APP . 'Config' . DS . 'Schema'
		);
		$file = array(
			'help' => __d('cake_console', 'File name to read and write.'),
			'default' => 'schema.php'
		);
		$name = array(
			'help' => __d('cake_console', 'Classname to use. If its Plugin.class, both name and plugin options will be set.')
		);
		$snapshot = array(
			'short' => 's',
			'help' => __d('cake_console', 'Snapshot number to use/make.')
		);
		$models = array(
			'short' => 'm',
			'help' => __d('cake_console', 'Specify models as comma separated list.'),
		);
		$dry = array(
			'help' => __d('cake_console', 'Perform a dry run on create and update commands. Queries will be output instead of run.'),
			'boolean' => true
		);
		$force = array(
			'short' => 'f',
			'help' => __d('cake_console', 'Force "generate" to create a new schema'),
			'boolean' => true
		);
		$write = array(
			'help' => __d('cake_console', 'Write the dumped SQL to a file.')
		);

		$parser = parent::getOptionParser();
		$parser->description(
			__d('cake_console', 'The Schema Shell generates a schema object from the database and updates the database from the schema.')
		)->addSubcommand('view', array(
			'help' => __d('cake_console', 'Read and output the contents of a schema file'),
			'parser' => array(
				'options' => compact('plugin', 'path', 'file', 'name', 'connection'),
				'arguments' => compact('name')
			)
		))->addSubcommand('generate', array(
			'help' => __d('cake_console', 'Reads from --connection and writes to --path. Generate snapshots with -s'),
			'parser' => array(
				'options' => compact('plugin', 'path', 'file', 'name', 'connection', 'snapshot', 'force', 'models'),
				'arguments' => array(
					'snapshot' => array('help' => __d('cake_console', 'Generate a snapshot.'))
				)
			)
		))->addSubcommand('dump', array(
			'help' => __d('cake_console', 'Dump database SQL based on a schema file to stdout.'),
			'parser' => array(
				'options' => compact('plugin', 'path', 'file', 'name', 'connection', 'write'),
				'arguments' => compact('name')
			)
		))->addSubcommand('create', array(
			'help' => __d('cake_console', 'Drop and create tables based on the schema file.'),
			'parser' => array(
				'options' => compact('plugin', 'path', 'file', 'name', 'connection', 'dry', 'snapshot'),
				'args' => array(
					'name' => array(
						'help' => __d('cake_console', 'Name of schema to use.')
					),
					'table' => array(
						'help' => __d('cake_console', 'Only create the specified table.')
					)
				)
			)
		))->addSubcommand('update', array(
			'help' => __d('cake_console', 'Alter the tables based on the schema file.'),
			'parser' => array(
				'options' => compact('plugin', 'path', 'file', 'name', 'connection', 'dry', 'snapshot', 'force'),
				'args' => array(
					'name' => array(
						'help' => __d('cake_console', 'Name of schema to use.')
					),
					'table' => array(
						'help' => __d('cake_console', 'Only create the specified table.')
					)
				)
			)
		));
		return $parser;
	}

}
