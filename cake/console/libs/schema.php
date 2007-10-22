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
		$content = $this->Schema->read();
		if ($this->Schema->write($content)) {
			$this->out(__('Schema file created.', true));
			exit();
		} else {
			$this->err(__('Schema could not be created', true));
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
 * Create database from Schema object
 *
 * @access public
 */
	function create() {
		$Schema = $this->Schema->load();
		$table = null;
		$event = array_keys($Schema->tables);
		if(isset($this->args[0])) {
			$table = $this->args[0];
			$event = array($table);
		}
		$errors = array();
		$db =& ConnectionManager::getDataSource($this->Schema->connection);
		$drop = $db->dropSchema($Schema, $table);
		$this->out($drop);
		if('y' == $this->in('Are you sure you want to drop tables and create your database?', array('y', 'n'), 'n')) {
			$create = $db->createSchema($Schema, $table);
			$this->out('Updating Database...');
			$contents = array_map('trim', explode(";", $drop. $create));
			foreach($contents as $sql) {
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
			exit();
		}
		$this->out(__('Database updated', true));
		exit();
	}
/**
 * Update database with Schema object
 *
 * @access public
 */
	function update() {
		$this->out('Comparing Database to Schema...');
		$Old = $this->Schema->read();
		$Schema = $this->Schema->load();
		$compare = $this->Schema->compare($Old, $Schema);

		$table = null;
		if(isset($this->args[0])) {
			$table = $this->args[0];
			$compare = array($table => $compare[$table]);
		}

		$db =& ConnectionManager::getDataSource($this->Schema->connection);
		$db->fullDebug = true;
		Configure::write('debug', 2);

		$contents = $db->alterSchema($compare, $table);
		if(empty($contents)) {
			$this->out(__('Current database is up to date.', true));
			exit();
		} else {
			$this->out($contents);
		}
		if('y' == $this->in('Are you sure you want to update your database?', array('y', 'n'), 'n')) {
			$this->out('Updating Database...');
			if(!$this->Schema->before($compare)) {
				return false;
			}
			if ($db->_execute($contents)) {
				$this->Schema->after($compare);
				$this->out(__('Database updated', true));
				exit();
			} else {
				$this->err(__('Database could not be updated', true));
				$this->err($db->lastError());
				exit();
			}
		}
	}
/**
 * Displays help contents
 *
 * @access public
 */
	function help() {
		$this->out('The Schema Shell generates a schema object from the database and updates the database from the schema.');
		$this->hr();
		$this->out("Usage: cake schema <command> <arg1> <arg2>...");
		$this->hr();
		$this->out('Params:');
		$this->out("\n\t-connection\n\t\tset db config. uses 'default' if none is specified");
		$this->out("\n\t-path\n\t\tpath to read and write schema.php. uses ". $this->Schema->path ." by default");
		$this->out('Commands:');
		$this->out("\n\tschema help\n\t\tshows this help message.");
		$this->out("\n\tschema view\n\t\tread and output contents of schema file");
		$this->out("\n\tschema generate\n\t\treads from 'connection' writes to 'path'");
		$this->out("\n\tschema dump <filename>\n\t\tdump database sql based on schema file to filename in schema path. if filename is true, default will use the app directory name.");
		$this->out("\n\tschema create <table>\n\t\tdrop tables and create database based on schema file\n\t\toptional <table> arg for creating only one table");
		$this->out("\n\tschema update <table>\n\t\talter tables based on schema file\n\t\toptional <table> arg for altering only one table");
		$this->out("");
		exit();
	}

}
?>