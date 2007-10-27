<?php
/* SVN FILE: $Id$ */
/**
 * Command-line database management utility to automate programmer chores.
 *
 * Schema is CakePHP's database management utility. This helps you maintain versions of
 * of your database.
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) :  Rapid Development Framework <http://www.cakephp.org/>
 * Copyright 2005-2007, Cake Software Foundation, Inc.
 *								1785 E. Sahara Avenue, Suite 490-204
 *								Las Vegas, Nevada 89104
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @filesource
 * @copyright		Copyright 2005-2007, Cake Software Foundation, Inc.
 * @link			http://www.cakefoundation.org/projects/info/cakephp CakePHP(tm) Project
 * @package			cake
 * @subpackage		cake.cake.console.libs
 * @since			CakePHP(tm) v 1.2.0.5550
 * @version			$Revision$
 * @modifiedby		$LastChangedBy$
 * @lastmodified	$Date$
 * @license			http://www.opensource.org/licenses/mit-license.php The MIT License
 */
uses('file', 'model' . DS . 'schema');
/**
 * Schema is a command-line database management utility for automating programmer chores.
 *
 * @package		cake
 * @subpackage	cake.cake.console.libs
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
		$this->out('Cake Schema Shell');
		$this->hr();
	}
/**
 * Override startup
 *
 * @access public
 */
	function startup() {
		$settings = am(array('path'=> CONFIGS .'sql'), $this->params);
		$this->Schema =& new CakeSchema($settings);
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
 * Read and output contents od schema object
 * path to read as second arg
 *
 * @access public
 */
	function view() {
		$File = new File($this->Schema->path . DS .'schema.php');
		if ($File->exists()) {
			$this->out($File->read());
			exit();
		} else {
			$this->err(__('Schema could not be found', true));
			exit();
		}
	}
/**
 * Read database and Write schema object
 * accepts a connection as first arg or path to save as second arg
 *
 * @access public
 */
	function generate() {
		$this->out('Generating Schema...');
		$options = array();
		if (isset($this->params['f'])) {
			$options = array('models' => false);
		}

		$content = $this->Schema->read($options);

		$snapshot = false;
		if (isset($this->args[0]) && $this->args[0] === 'snapshot') {
			$snapshot = true;
		}

		if(!$snapshot && file_exists($this->Schema->path . DS . 'schema.php')) {
			$snapshot = true;
			$result = $this->in("Schema file exists.\n [O]verwrite\n [S]napshot\n [Q]uit\nWould you like to do?", array('o', 's', 'q'), 's');
			if($result === 'q') {
				exit();
			}
			if($result === 'o') {
				$snapshot = false;
			}
		}

		$content['file'] = 'schema.php';
		if($snapshot === true) {
			$Folder =& new Folder($this->Schema->path);
			$result = $Folder->read();
			$count = 1;
			if(!empty($result[1])) {
				foreach ($result[1] as $file) {
					if (preg_match('/schema/', $file)) {
						$count++;
					}
				}
			}
			$content['file'] = 'schema_'.$count.'.php';
		}

		if ($this->Schema->write($content)) {
			$this->out(sprintf(__('Schema file: %s generated', true), $content['file']));
			exit();
		} else {
			$this->err(__('Schema file: %s generated', true));
			exit();
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
		if (!empty($this->args[0])) {
			if($this->args[0] == 'true') {
				$write = Inflector::underscore($this->Schema->name);
			} else {
				$write = $this->args[0];
			}
		}
		$db =& ConnectionManager::getDataSource($this->Schema->connection);
		$contents = "#". $Schema->name ." sql generated on: " . date('Y-m-d H:m:s') . " : ". time()."\n\n";
		$contents .= $db->dropSchema($Schema) . "\n\n". $db->createSchema($Schema);
		if($write) {
			if(strpos($write, '.sql') === false) {
				$write .= '.sql';
			}
			$File = new File($this->Schema->path . DS . $write, true);
			if($File->write($contents)) {
				$this->out(sprintf(__('SQL dump file created in %s', true), $File->pwd()));
				exit();
			} else {
				$this->err(__('SQL dump could not be created', true));
				exit();
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
			$this->err('command not found');
			exit();
		}

		$command = $this->args[0];

		$this->Dispatch->shiftArgs();

		if(isset($this->params['dry'])) {
			$this->__dry = true;
			$this->out(__('Performing a dry run.', true));
		}

		$options = array('file' => $this->Schema->file);
		if(isset($this->params['s'])) {
			$options = array('file' => 'schema_'.$this->params['s'].'.php');
		}

		$Schema = $this->Schema->load($options);
		if(!$Schema) {
			$this->err(sprintf(__('%s could not be loaded', true), $options['file']));
			exit();
		}

		$table = null;
		if(isset($this->args[1])) {
			$table = $this->args[1];
		}

		switch($command) {
			case 'create':
				$this->__create($Schema, $table);
			break;
			case 'update':
				$this->__update($Schema, $table);
			break;
			default:
			$this->err('command not found');
			exit();
		}

	}
/**
 * Create database from Schema object
 * Should be called via the run method
 *
 * @access private
 */
	function __create($Schema, $table = null) {
		$options = array();
		$table = null;
		$event = array_keys($Schema->tables);
		if($table) {
			$event = array($table);
		}
		$errors = array();

		Configure::write('debug', 2);
		$db =& ConnectionManager::getDataSource($this->Schema->connection);
		$db->fullDebug = true;
		$drop = $db->dropSchema($Schema, $table);

		$this->out($drop);
		if('y' == $this->in('Are you sure you want to drop tables and create your database?', array('y', 'n'), 'n')) {
			$create = $db->createSchema($Schema, $table);
			$this->out('Updating Database...');
			$contents = array_map('trim', explode(";", $drop. $create));
			foreach($contents as $sql) {
				if($this->__dry === true) {
					$this->out($sql);
				} else {
					if(!empty($sql)) {
						if(!$this->Schema->before(array('created'=> $event))) {
							return false;
						}
						if (!$db->_execute($sql)) {
							$errors[] = $db->lastError();
						}
						$this->Schema->after(array('created'=> $event, 'errors'=> $errors));
					}
				}
			}
			if(!empty($errors)) {
				$this->err($errors);
			} elseif ($this->__dry !== true) {
				$this->out(__('Database updated', true));
				exit();
			}
		}
		$this->out(__('End', true));
		exit();
	}
/**
 * Update database with Schema object
 * Should be called via the run method
 *
 * @access private
 */
	function __update($Schema, $table = null) {
		$this->out('Comparing Database to Schema...');
		$Old = $this->Schema->read();
		$compare = $this->Schema->compare($Old, $Schema);

		if(isset($compare[$table])) {
			$compare = array($table => $compare[$table]);
		}

		Configure::write('debug', 2);
		$db =& ConnectionManager::getDataSource($this->Schema->connection);
		$db->fullDebug = true;

		$contents = $db->alterSchema($compare, $table);
		if(empty($contents)) {
			$this->out(__('Schema is up to date.', true));
			exit();
		} elseif($this->__dry === true || 'y' == $this->in('Would you like to see the changes?', array('y', 'n'), 'y')) {
			$this->out($contents);
		}
		if($this->__dry !== true) {
			if('y' == $this->in('Are you sure you want to update your database?', array('y', 'n'), 'n')) {
					$this->out('Updating Database...');
					if(!$this->Schema->before($compare)) {
						return false;
					}
					if ($db->_execute($contents)) {
						$this->Schema->after($compare);
						$this->out(__('Database updated', true));
					} else {
						$this->err(__('Database could not be updated', true));
						$this->err($db->lastError());
					}
				exit();
			}
		}
		$this->out(__('End', true));
		exit();
	}
/**
 * Displays help contents
 *
 * @access public
 */
	function help() {
		$this->out("The Schema Shell generates a schema object from \n\t\tthe database and updates the database from the schema.");
		$this->hr();
		$this->out("Usage: cake schema <command> <arg1> <arg2>...");
		$this->hr();
		$this->out('Params:');
		$this->out("\n\t-connection <config>\n\t\tset db config <config>. uses 'default' if none is specified");
		$this->out("\n\t-path <dir>\n\t\tpath <dir> to read and write schema.php.\n\t\tdefault path: ". $this->Schema->path);
		$this->out("\n\t-file <name>\n\t\tfile <name> to read and write.\n\t\tdefault file: ". $this->Schema->file);
		$this->out("\n\t-s <number>\n\t\tsnapshot <number> to use for run.");
		$this->out("\n\t-dry\n\t\tPerform a dry run on 'run' commands.\n\t\tQueries will be output to window instead of executed.");
		$this->out("\n\t-f\n\t\tforce 'generate' to create a new schema.");
		$this->out('Commands:');
		$this->out("\n\tschema help\n\t\tshows this help message.");
		$this->out("\n\tschema view\n\t\tread and output contents of schema file");
		$this->out("\n\tschema generate\n\t\treads from 'connection' writes to 'path'\n\t\tTo force genaration of all tables into the schema, use the -f param.");
		$this->out("\n\tschema dump <filename>\n\t\tdump database sql based on schema file to filename in schema path. \n\t\tif filename is true, default will use the app directory name.");
		$this->out("\n\tschema run create <table>\n\t\tdrop tables and create database based on schema file\n\t\toptional <table> arg for creating only one table\n\t\tpass the -s param with a number to use a snapshot\n\t\tTo see the changes, perform a dry run with the -dry param");
		$this->out("\n\tschema run update <table>\n\t\talter tables based on schema file\n\t\toptional <table> arg for altering only one table.\n\t\tTo use a snapshot, pass the -s param with the snapshot number\n\t\tTo see the changes, perform a dry run with the -dry param");
		$this->out("");
		exit();
	}

}
?>