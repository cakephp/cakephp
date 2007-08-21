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
 * @return void
 */
	function initialize() {
		$this->out('Cake Schema Shell');
		$this->hr();
	}
/**
 * Override startup
 *
 * @access public
 * @return void
 */
	function startup() {
		$this->Schema =& new CakeSchema(array('path'=> CONFIGS .'sql'));
	}
/**
 * Read and output contents od schema object
 * path to read as second arg
 *
 * @access public
 * @return void
 */
	function view() {
		$path = $this->Schema->path;
		if (!empty($this->args[0])) {
			$path = $this->args[0];
		}
		$File = new File($path . DS .'schema.php');
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
 * @return void
 */
	function generate() {
		$this->out('Generating Schema...');
		if (!empty($this->args[0])) {
			$this->Schema->connection = $this->args[0];
		}
		if (!empty($this->args[1])) {
			$this->Schema->path = $this->args[1];
		}

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
 * @return void
 */
	function dump() {
		$write = false;
		if (!empty($this->args[0])) {
			if($this->args[0] == 'write') {
				$write = true;
			}
		}
		if (!empty($this->args[1])) {
			$this->Schema->path = $this->args[1];
		}
		$Schema = $this->Schema->load();
		$db =& ConnectionManager::getDataSource($this->Schema->connection);
		$contents = $db->dropSchema($Schema) . $db->createSchema($Schema);
		if($write === true) {
			$File = new File($this->Schema->path . DS . Inflector::underscore($this->Schema->name) .'.sql', true);
			if($File->write($contents)) {
				$this->out(__('SQL dump file created in '. $File->pwd(), true));
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
 * @return void
 */
	function create() {
		$Schema = $this->Schema->load();
		$db =& ConnectionManager::getDataSource($this->Schema->connection);
		$drop = $db->dropSchema($Schema);
		$this->out($drop);
		if('y' == $this->in('Are you sure you want to drop tables and create your database?', array('y', 'n'), 'n')) {
			$contents = $db->createSchema($Schema);
			$this->out('Updating Database...');
			if ($db->_execute($contents)) {
				$this->out(__('Database created', true));
				exit();
			} else {
				$this->err(__('Database could not be created', true));
				$this->err($db->lastError());
				exit();
			}
		}
	}
/**
 * Update database with Schema object
 *
 * @access public
 * @return void
 */
	function update() {
		$this->out('Comparing Database to Schema...');
		if (!empty($this->args[0])) {
			$this->Schema->connection = $this->args[0];
		}
		if (!empty($this->args[1])) {
			$this->Schema->path = $this->args[1];
		}
		$Old = $this->Schema->read();
		$Schema = $this->Schema->load();
		$compare = $this->Schema->compare($Old, $Schema);

		$db =& ConnectionManager::getDataSource($this->Schema->connection);
		$db->fullDebug = true;
		Configure::write('debug', 2);
		$contents = $db->alterSchema($compare);
		if(empty($contents)) {
			$this->out(__('Current database is up to date.', true));
			exit();
		} else {
			$this->out($contents);
		}
		if('y' == $this->in('Are you sure you want to update your database?', array('y', 'n'), 'n')) {
			$this->out('Updating Database...');
			if ($db->_execute($contents)) {
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
 * @return void
 */
	function help() {
		$this->out('The Schema Shell generates a schema object from the database and updates the database from the schema.');
		$this->hr();
		$this->out("Usage: cake schema <command> <arg1> <arg2>...");
		$this->hr();
		$this->out('Commands:');
		$this->out("\n\tschema help\n\t\tshows this help message.");
		$this->out("\n\tschema view <path>\n\t\tread and output contents of schema file");
		$this->out("\n\tschema generate <connection> <path>\n\t\treads from 'connection' writes to 'path'");
		$this->out("\n\tschema dump 'write' <path>\n\t\tdump database sql based on schema file");
		$this->out("\n\tschema create <path>\n\t\tdrop tables and create database based on schema file");
		$this->out("\n\tschema update <path>\n\t\tmodify database based on schema file");
		$this->out("");
		exit();
	}

}
?>