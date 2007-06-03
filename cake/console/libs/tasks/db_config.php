<?php
/* SVN FILE: $Id$ */
/**
 * The DbConfig Task handles creating and updating the database.php
 *
 * Long description for file
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
 * @link				http://www.cakefoundation.org/projects/info/cakephp CakePHP(tm) Project
 * @package			cake
 * @subpackage		cake.cake.console.libs.tasks
 * @since			CakePHP(tm) v 1.2
 * @version			$Revision$
 * @modifiedby		$LastChangedBy$
 * @lastmodified	$Date$
 * @license			http://www.opensource.org/licenses/mit-license.php The MIT License
 */
if(!class_exists('File')) {
	uses('file');
}
/**
 * Task class for creating and updating the database configuration file.
 *
 * @package		cake
 * @subpackage	cake.cake.console.libs.tasks
 */
class DbConfigTask extends Shell {

/**
 * Execution method always used for tasks
 *
 * @return void
 */
	function execute() {
		if(empty($this->args)) {
			$this->__interactive();
		}
	}
/**
 * Interactive interface
 *
 * @access private
 * @return void
 */
	function __interactive() {

		$this->out('Database Configuration:');
		$driver = '';
		while ($driver == '') {
			$driver = $this->in('Driver:', array('mysql','mysqli','mssql','sqlite','postgres', 'odbc', 'oracle', 'db2'), 'mysql');
		}

		$persistent = '';
		while ($persistent == '') {
			$persistent = $this->in('Persistent Connection?', array('y', 'n'), 'n');
		}
		if(low($persistent) == 'n') {
			$persistent = 'false';
		} else {
			$persistent = 'true';
		}

		$host = '';
		while ($host == '') {
			$host = $this->in('Database Host:', null, 'localhost');
		}

		$login = '';
		while ($login == '') {
			$login = $this->in('User:', null, 'root');
		}

		$password = '';
		$blankPassword = false;
		while ($password == '' && $blankPassword == false) {
			$password = $this->in('Password:');
			if ($password == '') {
				$blank = $this->in('The password you supplied was empty. Use an empty password?', array('y', 'n'), 'n');
				if($blank == 'y')
				{
					$blankPassword = true;
				}
			}
		}

		$database = '';
		while ($database == '') {
			$database = $this->in('Database Name:', null, 'cake');
		}

		$prefix = '';
		while ($prefix == '') {
			$prefix = $this->in('Table Prefix?', null, 'n');
		}
		if(low($prefix) == 'n') {
			$prefix = null;
		}

		$config = compact('driver', 'persistent', 'host', 'login', 'password', 'database', 'prefix');
		while($this->__verify($config) == false) {
			$this->__interactive();
		}

		config('database');
		return true;
	}
/**
 * Output verification message
 * and bake if it looks good
 *
 * @access private
 * @return bool
 */
	function __verify($config) {
		$defaults = array('driver'=> 'mysql', 'persistent'=> 'false', 'host'=> 'localhost',
						'login'=> 'root', 'password'=> 'password', 'database'=> 'project_name',
						'schema'=> null,'prefix'=> null);
		$config = am($defaults, $config);
		extract($config);

		$this->out('');
		$this->hr();
		$this->out('The following database configuration will be created:');
		$this->hr();
		$this->out("Driver:		   $driver");
		$this->out("Persistent:	   $persistent");
		$this->out("Host:		   $host");
		$this->out("User:		   $login");
		$this->out("Pass:		   " . str_repeat('*', strlen($password)));
		$this->out("Database:	   $database");
		$this->out("Table prefix:  $prefix");
		$this->hr();
		$looksGood = $this->in('Look okay?', array('y', 'n'), 'y');

		if (low($looksGood) == 'y' || low($looksGood) == 'yes') {
			return $this->bake($config);
		} else {
			return false;
		}
	}
/**
 * Assembles and writes database.php
 *
 * @access public
 * @return bool
 */
	function bake($config) {
		$defaults = array('driver'=> 'mysql', 'persistent'=> 'false', 'host'=> 'localhost',
						'login'=> 'root', 'password'=> 'password', 'database'=> 'project_name',
						'schema'=> null,'prefix'=> null);
		$config = am($defaults, $config);
		extract($config);

		if(is_dir(CONFIGS)) {
			$out = "<?php\n";
			$out .= "class DATABASE_CONFIG {\n\n";
			$out .= "\tvar \$default = array(\n";
			$out .= "\t\t'driver' => '{$driver}',\n";
			$out .= "\t\t'persistent' => {$persistent},\n";
			$out .= "\t\t'host' => '{$host}',\n";
			$out .= "\t\t'login' => '{$login}',\n";
			$out .= "\t\t'password' => '{$password}',\n";
			$out .= "\t\t'database' => '{$database}', \n";
			if($schema) {
				$out .= "\t\t'schema' => '{$schema}', \n";
			}
			$out .= "\t\t'prefix' => '{$prefix}' \n";
			$out .= "\t);\n";
			$out .= "}\n";
			$out .= "?>";
			$filename = CONFIGS.'database.php';
			return $this->createFile($filename, $out);
		} else {
			$this->err(CONFIGS .' not found');
		}
		return false;
	}

}
?>