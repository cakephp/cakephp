<?php
/**
 * Command-line database management utility to automate programmer chores.
 *
 * Schema is CakePHP's database management utility. This helps you maintain versions of
 * of your database.
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) :  Rapid Development Framework (http://www.cakephp.org)
 * Copyright 2005-2009, Cake Software Foundation, Inc. (http://www.cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @filesource
 * @copyright     Copyright 2005-2009, Cake Software Foundation, Inc. (http://www.cakefoundation.org)
 * @link          http://www.cakefoundation.org/projects/info/cakephp CakePHP(tm) Project
 * @package       cake
 * @subpackage    cake.cake.console.libs
 * @since         CakePHP(tm) v 1.2.0.5550
 * @license       http://www.opensource.org/licenses/mit-license.php The MIT License
 */
App::import('File');
App::import('Model', 'Schema');
/**
 * Schema is a command-line database management utility for automating programmer chores.
 *
 * @package       cake
 * @subpackage    cake.cake.console.libs
 * @link          http://book.cakephp.org/view/734/Schema-management-and-migrations
 */
class SchemaShell extends Shell {
/**
 * is this a dry run?
 *
 * @var boolean
 * @access private
 */
	var $__dry = null;
/**
 * Override initialize
 *
 * @access public
 */
	function initialize() {
		$this->_welcome();
		$this->out('Cake Schema Shell');
		$this->hr();
	}
/**
 * Override startup
 *
 * @access public
 */
	function startup() {
		$name = null;
		if (!empty($this->params['name'])) {
			$name = $this->params['name'];
			$this->params['file'] = Inflector::underscore($name);
		}

		$path = null;
		if (!empty($this->params['path'])) {
			$path = $this->params['path'];
		}

		$file = null;
		if (empty($this->params['file'])) {
			$this->params['file'] = 'schema.php';
		}
		if (strpos($this->params['file'], '.php') === false) {
			$this->params['file'] .= '.php';
		}
		$file = $this->params['file'];

		$connection = null;
		if (!empty($this->params['connection'])) {
			$connection = $this->params['connection'];
		}

		$this->Schema =& new CakeSchema(compact('name', 'path', 'file', 'connection'));
	}
/**
 * Override main
 *
 * @access public
 */
	function main() {
		$this->help();
	}
/**
 * Read and output contents of schema object
 * path to read as second arg
 *
 * @access public
 */
	function view() {
		$File = new File($this->Schema->path . DS . $this->params['file']);
		if ($File->exists()) {
			$this->out($File->read());
			$this->_stop();
		} else {
			$this->err(__('Schema could not be found', true));
			$this->_stop();
		}
	}
/**
 * Read database and Write schema object
 * accepts a connection as first arg or path to save as second arg
 *
 * @access public
 */
	function generate() {
		$this->out(__('Generating Schema...', true));
		$options = array();
		if (isset($this->params['f'])) {
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
			$Folder =& new Folder($this->Schema->path);
			$result = $Folder->read();

			$numToUse = false;
			if (isset($this->params['s'])) {
				$numToUse = $this->params['s'];
			}

			$count = 1;
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
			$this->out(sprintf(__('Schema file: %s generated', true), $content['file']));
			$this->_stop();
		} else {
			$this->err(__('Schema file: %s generated', true));
			$this->_stop();
		}
	}
/**
 * Dump Schema object to sql file
 * if first arg == write, file will be written to sql file
 * or it will output sql
 *
 * @access public
 */
	function dump() {
		$write = false;
		$Schema = $this->Schema->load();
		if (!$Schema) {
			$this->err(__('Schema could not be loaded', true));
			$this->_stop();
		}
		if (!empty($this->args[0])) {
			if ($this->args[0] == 'write') {
				$write = Inflector::underscore($this->Schema->name);
			} else {
				$write = $this->args[0];
			}
		}
		$db =& ConnectionManager::getDataSource($this->Schema->connection);
		$contents = "#" . $Schema->name . " sql generated on: " . date('Y-m-d H:i:s') . " : " . time() . "\n\n";
		$contents .= $db->dropSchema($Schema) . "\n\n". $db->createSchema($Schema);
		if ($write) {
			if (strpos($write, '.sql') === false) {
				$write .= '.sql';
			}
			$File = new File($this->Schema->path . DS . $write, true);
			if ($File->write($contents)) {
				$this->out(sprintf(__('SQL dump file created in %s', true), $File->pwd()));
				$this->_stop();
			} else {
				$this->err(__('SQL dump could not be created', true));
				$this->_stop();
			}
		}
		$this->out($contents);
		return $contents;
	}
/**
 * Run database commands: create, update
 *
 * @access public
 */
	function run() {
		if (!isset($this->args[0])) {
			$this->err(__('Command not found', true));
			$this->_stop();
		}

		$command = $this->args[0];

		$this->Dispatch->shiftArgs();

		$name = null;
		if (isset($this->args[0])) {
			$name = $this->args[0];
		}
		if (isset($this->params['name'])) {
			$name = $this->params['name'];
		}

		if (isset($this->params['dry'])) {
			$this->__dry = true;
			$this->out(__('Performing a dry run.', true));
		}

		$options = array('name' => $name);
		if (isset($this->params['s'])) {
			$fileName = rtrim($this->Schema->file, '.php');
			$options['file'] = $fileName . '_' . $this->params['s'] . '.php';
		}

		$Schema = $this->Schema->load($options);

		if (!$Schema) {
			$this->err(sprintf(__('%s could not be loaded', true), $this->Schema->file));
			$this->_stop();
		}

		$table = null;
		if (isset($this->args[1])) {
			$table = $this->args[1];
		}

		switch ($command) {
			case 'create':
				$this->__create($Schema, $table);
			break;
			case 'update':
				$this->__update($Schema, $table);
			break;
			default:
				$this->err(__('Command not found', true));
				$this->_stop();
		}
	}
/**
 * Create database from Schema object
 * Should be called via the run method
 *
 * @access private
 */
	function __create(&$Schema, $table = null) {
		$db =& ConnectionManager::getDataSource($this->Schema->connection);

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
			$this->out(__('Schema is up to date.', true));
			$this->_stop();
		}

		$this->out("\n" . __('The following table(s) will be dropped.', true));
		$this->out(array_keys($drop));

		if ('y' == $this->in(__('Are you sure you want to drop the table(s)?', true), array('y', 'n'), 'n')) {
			$this->out(__('Dropping table(s).', true));
			$this->__run($drop, 'drop', $Schema);
		}

		$this->out("\n" . __('The following table(s) will be created.', true));
		$this->out(array_keys($create));

		if ('y' == $this->in(__('Are you sure you want to create the table(s)?', true), array('y', 'n'), 'y')) {
			$this->out(__('Creating table(s).', true));
			$this->__run($create, 'create', $Schema);
		}

		$this->out(__('End create.', true));
	}
/**
 * Update database with Schema object
 * Should be called via the run method
 *
 * @access private
 */
	function __update(&$Schema, $table = null) {
		$db =& ConnectionManager::getDataSource($this->Schema->connection);

		$this->out(__('Comparing Database to Schema...', true));
		$options = array();
		if (isset($this->params['f'])) {
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
			$this->out(__('Schema is up to date.', true));
			$this->_stop();
		}

		$this->out("\n" . __('The following statements will run.', true));
		$this->out(array_map('trim', $contents));
		if ('y' == $this->in(__('Are you sure you want to alter the tables?', true), array('y', 'n'), 'n')) {
			$this->out('');
			$this->out(__('Updating Database...', true));
			$this->__run($contents, 'update', $Schema);
		}

		$this->out(__('End update.', true));
	}
/**
 * Runs sql from __create() or __update()
 *
 * @access private
 */
	function __run($contents, $event, &$Schema) {
		if (empty($contents)) {
			$this->err(__('Sql could not be run', true));
			return;
		}
		Configure::write('debug', 2);
		$db =& ConnectionManager::getDataSource($this->Schema->connection);
		$db->fullDebug = true;

		foreach ($contents as $table => $sql) {
			if (empty($sql)) {
				$this->out(sprintf(__('%s is up to date.', true), $table));
			} else {
				if ($this->__dry === true) {
					$this->out(sprintf(__('Dry run for %s :', true), $table));
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
						$this->out(sprintf(__('%s updated.', true), $table));
					}
				}
			}
		}
	}
/**
 * Displays help contents
 *
 * @access public
 */
	function help() {
		$this->out("The Schema Shell generates a schema object from");
		$this->out("the database and updates the database from the schema.");
		$this->hr();
		$this->out("Usage: cake schema <command> <arg1> <arg2>...");
		$this->hr();
		$this->out('Params:');
		$this->out("\n\t-connection <config>\n\t\tset db config <config>. uses 'default' if none is specified");
		$this->out("\n\t-path <dir>\n\t\tpath <dir> to read and write schema.php.\n\t\tdefault path: ". $this->Schema->path);
		$this->out("\n\t-name <name>\n\t\tclassname to use.");
		$this->out("\n\t-file <name>\n\t\tfile <name> to read and write.\n\t\tdefault file: ". $this->Schema->file);
		$this->out("\n\t-s <number>\n\t\tsnapshot <number> to use for run.");
		$this->out("\n\t-dry\n\t\tPerform a dry run on 'run' commands.\n\t\tQueries will be output to window instead of executed.");
		$this->out("\n\t-f\n\t\tforce 'generate' to create a new schema.");
		$this->out('Commands:');
		$this->out("\n\tschema help\n\t\tshows this help message.");
		$this->out("\n\tschema view\n\t\tread and output contents of schema file");
		$this->out("\n\tschema generate\n\t\treads from 'connection' writes to 'path'\n\t\tTo force generation of all tables into the schema, use the -f param.\n\t\tUse 'schema generate snapshot <number>' to generate snapshots\n\t\twhich you can use with the -s parameter in the other operations.");
		$this->out("\n\tschema dump <filename>\n\t\tDump database sql based on schema file to <filename>. \n\t\tIf <filename> is write, schema dump will be written to a file\n\t\tthat has the same name as the app directory.");
		$this->out("\n\tschema run create <schema> <table>\n\t\tDrop and create tables based on schema file\n\t\toptional <schema> arg for selecting schema name\n\t\toptional <table> arg for creating only one table\n\t\tpass the -s param with a number to use a snapshot\n\t\tTo see the changes, perform a dry run with the -dry param");
		$this->out("\n\tschema run update <schema> <table>\n\t\talter tables based on schema file\n\t\toptional <schema> arg for selecting schema name.\n\t\toptional <table> arg for altering only one table.\n\t\tTo use a snapshot, pass the -s param with the snapshot number\n\t\tTo see the changes, perform a dry run with the -dry param");
		$this->out("");
		$this->_stop();
	}
}
?>