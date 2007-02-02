#!/usr/bin/php -q
<?php
/* SVN FILE: $Id$ */
/**
 * Short description for file.
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
 * @subpackage		cake.cake.scripts
 * @since			CakePHP(tm) v 0.10.0.1232
 * @version			$Revision$
 * @modifiedby		$LastChangedBy$
 * @lastmodified	$Date$
 * @license			http://www.opensource.org/licenses/mit-license.php The MIT License
 */
/**
 * Enter description here...
 */
	ini_set('display_errors', '1');
	ini_set('error_reporting', '7');
	define ('DS', DIRECTORY_SEPARATOR);
	$app = 'app';
	$core = null;
	$root = dirname(dirname(dirname(__FILE__)));
	$here = $argv[0];
	$dataSource = 'default';
	$unset = array();
	for ($i = 1; $i < count($argv); $i++) {
		// Process command-line modifiers here
		switch (strtolower($argv[$i])) {
			case '-app':
				$app = $argv[$i + 1];
				$unset[$i] = $argv[$i];
				$unset[$i + 1] = $argv[$i + 1]; 
			break;
			case '-core':
				$core = $argv[$i + 1];
				$unset[$i] = $argv[$i];
				$unset[$i + 1] = $argv[$i + 1]; 
			break;
			case '-root':
				$root = $argv[$i + 1];
				$unset[$i] = $argv[$i];
				$unset[$i + 1] = $argv[$i + 1]; 
			break;
			case '-datasource':
				$dataSource = $argv[$i + 1];
				$unset[$i] = $argv[$i];
				$unset[$i + 1] = $argv[$i + 1]; 
			break;
		}
	}

	if (strlen($app) && $app[0] == DS) {
		$cnt = substr_count($root, DS);
		$app = str_repeat('..' . DS, $cnt) . $app;
	}
	define ('ROOT', $root.DS);
	define ('APP_DIR', $app);
	define ('DEBUG', 1);;
	define('CAKE_CORE_INCLUDE_PATH', ROOT);
	define('DATASOURCE', $dataSource);

	if(function_exists('ini_set')) {
		ini_set('include_path',ini_get('include_path').
			PATH_SEPARATOR.CAKE_CORE_INCLUDE_PATH.DS.
			PATH_SEPARATOR.CORE_PATH.DS.
			PATH_SEPARATOR.ROOT.DS.APP_DIR.DS.
			PATH_SEPARATOR.APP_DIR.DS.
			PATH_SEPARATOR.APP_PATH);
		define('APP_PATH', null);
		define('CORE_PATH', null);
	} else {
		define('APP_PATH', ROOT . DS . APP_DIR . DS);
		define('CORE_PATH', CAKE_CORE_INCLUDE_PATH . DS);
	}

	require ('cake'.DS.'basics.php');
	require ('cake'.DS.'config'.DS.'paths.php');
	require (CONFIGS.'core.php');
	uses ('object', 'configure', 'neat_array', 'session', 'security', 'inflector', 'model'.DS.'connection_manager',
			'model'.DS.'datasources'.DS.'dbo_source', 'model'.DS.'model');
	require(CAKE.'app_model.php');
	uses ('controller'.DS.'components'.DS.'acl', 'controller'.DS.'components'.DS.'dbacl'.DS.'models'.DS.'aclnode',
			'controller'.DS.'components'.DS.'dbacl'.DS.'models'.DS.'aco', 'controller'.DS.'components'.DS.'dbacl'.DS.'models'.DS.'acoaction',
			'controller'.DS.'components'.DS.'dbacl'.DS.'models'.DS.'aro');
	//Get and format args: first arg is the name of the script.
	$serverArgs = $argv;
	if(!empty($unset)) {
		$serverArgs = array_values(array_diff($argv, $unset));
	}
	
	$wasted = array_shift($serverArgs);
	$command = array_shift($serverArgs);
	$args = $serverArgs;
	$aclCLI = new AclCLI ($command, $args);
/**
 * @package		cake
 * @subpackage	cake.cake.scritps
 */
class AclCLI {
/**
 * Enter description here...
 *
 * @var unknown_type
 */
	var $stdin;
/**
 * Enter description here...
 *
 * @var unknown_type
 */
	var $stdout;
/**
 * Enter description here...
 *
 * @var unknown_type
 */
	var $stderr;
/**
 * Enter description here...
 *
 * @var unknown_type
 */
	var $acl;
/**
 * Enter description here...
 *
 * @var unknown_type
 */
	var $args;
/**
 * Enter description here...
 *
 * @var unknown_type
 */
	var $dataSource = 'default';
/**
 * Enter description here...
 *
 * @param unknown_type $command
 * @param unknown_type $args
 * @return AclCLI
 */
	function AclCLI($command, $args) {
		$this->__construct($command, $args);
	}
/**
 * Enter description here...
 *
 * @param unknown_type $command
 * @param unknown_type $args
 */
	function __construct ($command, $args) {
		$this->stdin = fopen('php://stdin', 'r');
		$this->stdout = fopen('php://stdout', 'w');
		$this->stderr = fopen('php://stderr', 'w');

		if (ACL_CLASSNAME != 'DB_ACL'){
			$out = "--------------------------------------------------\n";
			$out .= "Error: Your current Cake configuration is set to \n";
			$out .= "an ACL implementation other than DB. Please change \n";
			$out .= "your core config to reflect your decision to use \n";
			$out .= "DB_ACL before attempting to use this script.\n";
			$out .= "--------------------------------------------------\n";
			$out .= "Current ACL Classname: " . ACL_CLASSNAME . "\n";
			$out .= "--------------------------------------------------\n";
			fwrite($this->stderr, $out);
			exit();
		}
		
		if(!in_array($command, array('help'))) {
			if(!file_exists(CONFIGS.'database.php')) {
				$this->stdout('');
				$this->stdout('Your database configuration was not found.');
				$this->stdout('Take a moment to create one:');
				$this->doDbConfig();
			}
			require_once (CONFIGS.'database.php');
			
			if(!in_array($command, array('initdb'))) {
				$this->dataSource = DATASOURCE;
				$this->Acl = new AclComponent();
				$this->args = $args;
				$this->db =& ConnectionManager::getDataSource($this->dataSource);
			}
		}

		switch ($command) {
			case 'create':
				$this->create();
			break;
			case 'delete':
				$this->delete();
			break;
			case 'setParent':
				$this->setParent();
			break;
			case 'getPath':
				$this->getPath();
			break;
			case 'grant':
				$this->grant();
			break;
			case 'deny':
				$this->deny();
			break;
			case 'inherit':
				$this->inherit();
			break;
			case 'view':
				$this->view();
			break;
			case 'initdb':
				$this->initdb();
			break;
			case 'upgrade':
				$this->upgradedb();
			break;
			case 'help':
				$this->help();
			break;
			default:
				fwrite($this->stderr, "Unknown ACL command '$command'.\nFor usage, try 'php acl.php help'.\n\n");
			break;
		}
	}
/**
 * Enter description here...
 *
 */
	function create() {
		$this->checkArgNumber(4, 'create');
		$this->checkNodeType();
		extract($this->__dataVars());
		
		$parent = (is_numeric($this->args[2])) ? intval($this->args[2]) : $this->args[2];
		if(!$this->Acl->{$class}->create(intval($this->args[1]), $parent, $this->args[3])){
			$this->displayError("Parent Node Not Found", "There was an error creating the ".$class.", probably couldn't find the parent node.\n If you wish to create a new root node, specify the <parent_id> as '0'.");
		}
		$this->stdout("New $class '".$this->args[3]."' created.\n\n");
	}
/**
 * Enter description here...
 *
 */
	function delete() {
		$this->checkArgNumber(2, 'delete');
		$this->checkNodeType();
		extract($this->__dataVars());
		if(!$this->Acl->{$class}->delete($this->args[1])) {
			$this->displayError("Node Not Deleted", "There was an error deleting the ".$class.". Check that the node exists.\n");
		}
		$this->stdout("{$class} deleted.\n\n");
	}

/**
 * Enter description here...
 *
 */
	function setParent() {
		$this->checkArgNumber(3, 'setParent');
		$this->checkNodeType();
		extract($this->__dataVars());
		if (!$this->Acl->{$class}->setParent($this->args[2], $this->args[1])){
			$this->stdout("Error in setting new parent. Please make sure the parent node exists, and is not a descendant of the node specified.\n");
		} else {
			$this->stdout("Node parent set to ".$this->args[2]."\n\n");
		}
	}
/**
 * Enter description here...
 *
 */
	function getPath() {
		$this->checkArgNumber(2, 'getPath');
		$this->checkNodeType();
		extract($this->__dataVars());
		$id = (is_numeric($this->args[2])) ? intval($this->args[1]) : $this->args[1];
		$nodes = $this->Acl->{$class}->getPath($id);
		if (empty($nodes)) {
			$this->displayError("Supplied Node '".$this->args[1]."' not found", "No tree returned.");
		}
		for ($i = 0; $i < count($nodes); $i++) {
			$this->stdout(str_repeat('  ', $i) . "[" . $nodes[$i][$class]['id'] . "]" . $nodes[$i][$class]['alias'] . "\n");
		}
	}
/**
 * Enter description here...
 *
 */
	function grant() {
		$this->checkArgNumber(3, 'grant');
		//add existence checks for nodes involved
		$aro = (is_numeric($this->args[0])) ? intval($this->args[0]) : $this->args[0];
		$aco = (is_numeric($this->args[1])) ? intval($this->args[1]) : $this->args[1];
		$this->Acl->allow($aro, $aco, $this->args[2]);
		$this->stdout("Permission granted.\n");
	}
/**
 * Enter description here...
 *
 */
	function deny() {
		$this->checkArgNumber(3, 'deny');
		//add existence checks for nodes involved
		$aro = (is_numeric($this->args[0])) ? intval($this->args[0]) : $this->args[0];
		$aco = (is_numeric($this->args[1])) ? intval($this->args[1]) : $this->args[1];
		$this->Acl->deny($aro, $aco, $this->args[2]);
		$this->stdout("Requested permission successfully denied.\n");
	}
/**
 * Enter description here...
 *
 */
	function inherit() {
		$this->checkArgNumber(3, 'inherit');
		$aro = (is_numeric($this->args[0])) ? intval($this->args[0]) : $this->args[0];
		$aco = (is_numeric($this->args[1])) ? intval($this->args[1]) : $this->args[1];
		$this->Acl->inherit($aro, $aco, $this->args[2]);
		$this->stdout("Requested permission successfully inherited.\n");
	}
/**
 * Enter description here...
 *
 */
	function view() {
		$this->checkArgNumber(1, 'view'); 
		$this->checkNodeType();
		extract($this->__dataVars());
		if (!is_null($this->args[1])) {
			$conditions = $this->Acl->{$class}->_resolveID($this->args[1]);
		} else {
			$conditions = null;
		}
		$nodes = $this->Acl->{$class}->findAll($conditions, null, 'lft ASC');
		if (empty($nodes)) {
			$this->displayError($this->args[1]." not found", "No tree returned.");
		}
		$right = array();

		$this->stdout($class . " tree:\n");
		$this->stdout("------------------------------------------------\n");

		for($i = 0; $i < count($nodes); $i++){
			if (count($right) > 0){
				while ($right[count($right)-1] < $nodes[$i][$class]['rght']){
					if ($right[count($right)-1]){
						array_pop($right);
					} else {
						break;
					}
				}
			}
			$this->stdout(str_repeat('  ',count($right)) . "[" . $nodes[$i][$class]['id'] . "]" . $nodes[$i][$class]['alias']."\n");
			$right[] = $nodes[$i][$class]['rght'];
		}
		$this->stdout("------------------------------------------------\n");
	}
/**
 * Enter description here...
 *
 */
	function initdb() {
		$db =& ConnectionManager::getDataSource($this->dataSource);
		$this->stdout("Initializing Database...\n");
		$this->stdout("Creating access control objects table (acos)...\n");
		$sql = " CREATE TABLE ".$db->fullTableName('acos')." (
				".$db->name('id')." ".$db->column($db->columns['primary_key']).",
				".$db->name('object_id')." ".$db->column($db->columns['integer'])." default NULL,
				".$db->name('alias')." ".$db->column($db->columns['string'])." NOT NULL default '',
				".$db->name('lft')." ".$db->column($db->columns['integer'])." default NULL,
				".$db->name('rght')." ".$db->column($db->columns['integer'])." default NULL,
				PRIMARY KEY  (".$db->name('id').")
				);";
		if ($db->query($sql) === false) {
			die("Error: " . $db->lastError() . "\n\n");
		}

		$this->stdout("Creating access request objects table (aros)...\n");
		$sql2 = "CREATE TABLE ".$db->fullTableName('aros')." (
				".$db->name('id')." ".$db->column($db->columns['primary_key']).",
				".$db->name('foreign_key')." ".$db->column($db->columns['integer'])." default NULL,
				".$db->name('alias')." ".$db->column($db->columns['string'])." NOT NULL default '',
				".$db->name('lft')." ".$db->column($db->columns['integer'])." default NULL,
				".$db->name('rght')." ".$db->column($db->columns['integer'])." default NULL,
				PRIMARY KEY  (".$db->name('id').")
				);";
		if ($db->query($sql2) === false) {
			die("Error: " . $db->lastError() . "\n\n");
		}

		$this->stdout("Creating relationships table (aros_acos)...\n");
		$sql3 = "CREATE TABLE ".$db->fullTableName('aros_acos')." (
				".$db->name('id')." ".$db->column($db->columns['primary_key']).",
				".$db->name('aro_id')." ".$db->column($db->columns['integer'])." default NULL,
				".$db->name('aco_id')." ".$db->column($db->columns['integer'])." default NULL,
				".$db->name('_create')." ".$db->column($db->columns['integer'])." NOT NULL default '0',
				".$db->name('_read')." ".$db->column($db->columns['integer'])." NOT NULL default '0',
				".$db->name('_update')." ".$db->column($db->columns['integer'])." NOT NULL default '0',
				".$db->name('_delete')." ".$db->column($db->columns['integer'])." NOT NULL default '0',
				PRIMARY KEY  (".$db->name('id').")
				);";
		if ($db->query($sql3) === false) {
			die("Error: " . $db->lastError() . "\n\n");
		}

		$this->stdout("\nDone.\n");
	}

/**
 * Enter description here...
 *
 */
	function upgradedb() {
		$db =& ConnectionManager::getDataSource($this->dataSource);
		$this->stdout("Initializing Database...\n");
		$this->stdout("Upgrading table (aros)...\n");
		$sql = "ALTER TABLE ".$db->fullTableName('aros')."
				CHANGE ".$db->name('user_id')."
				".$db->name('foreign_key')."
				INT( 10 ) UNSIGNED NULL DEFAULT NULL;";
		$sql .= "ALTER TABLE " . $db->name('aros_acos') . " CHANGE " . $db->name('_create') 
				. " " . $db->name('_create') . " CHAR(2) NOT NULL DEFAULT '0';";
		$sql .= "ALTER TABLE " . $db->name('aros_acos') . " CHANGE " . $db->name('_update') 
				. " " . $db->name('_update') . " CHAR(2) NOT NULL DEFAULT '0';";
		$sql .= "ALTER TABLE " . $db->name('aros_acos') . " CHANGE " . $db->name('_read') 
				. " " . $db->name('_read') . " CHAR(2) NOT NULL DEFAULT '0';";
		$sql .= "ALTER TABLE " . $db->name('aros_acos') . " CHANGE " . $db->name('_delete') 
				. " " . $db->name('_delete') . " CHAR(2) NOT NULL DEFAULT '0';";
		if ($db->query($sql) === false) {
			die("Error: " . $db->lastError() . "\n\n");
		}
		$this->stdout("\nDatabase upgrade is complete.\n");
	}

/**
 * Enter description here...
 *
 */
	function help() {
		$out = "Usage: php acl.php <command> <arg1> <arg2>...\n";
		$out .= "-----------------------------------------------\n";
		$out .= "Commands:\n";
		$out .= "\n";
		$out .= "\tcreate aro|aco <link_id> <parent_id> <alias>\n";
		$out .= "\t\tCreates a new ACL object under the parent specified by <parent_id>, an id/alias (see\n";
		$out .= "\t\t'view'). The link_id allows you to link a user object to Cake's\n";
		$out .= "\t\tACL structures. The alias parameter allows you to address your object\n";
		$out .= "\t\tusing a non-integer ID. Example: \"\$php acl.php create aro 57 0 John\"\n";
		$out .= "\t\twould create a new ARO object at the root of the tree, linked to 57\n";
		$out .= "\t\tin your users table, with an internal alias 'John'.";
		$out .= "\n";
		$out .= "\n";
		$out .= "\tdelete aro|aco <id>\n";
		$out .= "\t\tDeletes the ACL object with the specified ID (see 'view').\n";
		$out .= "\n";
		$out .= "\n";
		$out .= "\tsetParent aro|aco <id> <parent_id>\n";
		$out .= "\t\tUsed to set the parent of the ACL object specified by <id> to the ID\n";
		$out .= "\t\tspecified by <parent_id>.\n";
		$out .= "\n";
		$out .= "\n";
		$out .= "\tgetPath aro|aco <id>\n";
		$out .= "\t\tReturns the path to the ACL object specified by <id>. This command is\n";
		$out .= "\t\tis useful in determining the inhertiance of permissions for a certain\n";
		$out .= "\t\tobject in the tree.\n";
		$out .= "\n";
		$out .= "\n";
		$out .= "\tgrant <aro_id> <aco_id> <aco_action>\n";
		$out .= "\t\tUse this command to grant ACL permissions. Once executed, the ARO\n";
		$out .= "\t\tspecified (and its children, if any) will have ALLOW access to the\n";
		$out .= "\t\tspecified ACO action (and the ACO's children, if any).\n";
		$out .= "\n";
		$out .= "\n";
		$out .= "\tdeny <aro_id> <aco_id> <aco_action>\n";
		$out .= "\t\tUse this command to deny ACL permissions. Once executed, the ARO\n";
		$out .= "\t\tspecified (and its children, if any) will have DENY access to the\n";
		$out .= "\t\tspecified ACO action (and the ACO's children, if any).\n";
		$out .= "\n";
		$out .= "\n";
		$out .= "\tinherit <aro_id> <aco_id> <aco_action> \n";
		$out .= "\t\tUse this command to force a child ARO object to inherit its\n";
		$out .= "\t\tpermissions settings from its parent.\n";
		$out .= "\n";
		$out .= "\n";
		$out .= "\tview aro|aco [id]\n";
		$out .= "\t\tThe view command will return the ARO or ACO tree. The optional\n";
		$out .= "\t\tid/alias parameter allows you to return only a portion of the requested\n";
		$out .= "\t\ttree.\n";
		$out .= "\n";
		$out .= "\n";
		$out .= "\tinitdb\n";
		$out .= "\t\tUse this command to create the database tables needed to use DB ACL.\n";
		$out .= "\n";
		$out .= "\n";
		$out .= "\thelp\n";
		$out .= "\t\tDisplays this help message.\n";
		$out .= "\n";
		$out .= "\n";
		$this->stdout($out);
	}
/**
 * Enter description here...
 *
 * @param unknown_type $title
 * @param unknown_type $msg
 */
	function displayError($title, $msg) {
		$out = "\n";
		$out .= "Error: $title\n";
		$out .= "$msg\n";
		$out .= "\n";
		$this->stdout($out);
		exit();
	}

/**
 * Enter description here...
 *
 * @param unknown_type $expectedNum
 * @param unknown_type $command
 */
	function checkArgNumber($expectedNum, $command) {
		if (count($this->args) < $expectedNum) {
			$this->displayError('Wrong number of parameters: '.count($this->args), 'Please type \'php acl.php help\' for help on usage of the '.$command.' command.');
		}
	}
/**
 * Enter description here...
 *
 */
	function checkNodeType() {
		if ($this->args[0] != 'aco' && $this->args[0] != 'aro') {
			$this->displayError("Missing/Unknown node type: '".$this->args[0]."'", 'Please specify which ACL object type you wish to create.');
		}
	}
/**
 * Enter description here...
 *
 * @param unknown_type $type
 * @param unknown_type $id
 * @return unknown
 */
	function nodeExists($type, $id) {
		//$this->stdout("Check to see if $type with ID = $id exists...\n");
		extract($this->__dataVars($type));
		$conditions = $this->Acl->{$class}->_resolveID($id);
		$possibility = $this->Acl->{$class}->findAll($conditions);
		return $possibility;
	}

/**
 * Enter description here...
 *
 * @param unknown_type $type
 * @return unknown
 */
	function __dataVars($type = null) {
		if ($type == null) {
			$type = $this->args[0];
		}

		$vars = array();
		$class = ucwords($type);
		$vars['secondary_id'] = ($class == 'aro' ? 'foreign_key' : 'object_id');
		$vars['data_name'] = $type;
		$vars['table_name'] = $type . 's';
		$vars['class'] = $class;
		return $vars;
	}
/**
 * Database configuration setup.
 *
 */
	function doDbConfig() {
		$this->hr();
		$this->stdout('Database Configuration:');
		$this->hr();

		$driver = '';

		while ($driver == '') {
			$driver = $this->getInput('What database driver would you like to use?', array('mysql','mysqli','mssql','sqlite','postgres', 'odbc'), 'mysql');
			if ($driver == '') {
				$this->stdout('The database driver supplied was empty. Please supply a database driver.');
			}
		}

		switch($driver) {
			case 'mysql':
			$connect = 'mysql_connect';
			break;
			case 'mysqli':
			$connect = 'mysqli_connect';
			break;
			case 'mssql':
			$connect = 'mssql_connect';
			break;
			case 'sqlite':
			$connect = 'sqlite_open';
			break;
			case 'postgres':
			$connect = 'pg_connect';
			break;
			case 'odbc':
			$connect = 'odbc_connect';
			break;
			default:
			$this->stdout('The connection parameter could not be set.');
			break;
		}

		$host = '';

		while ($host == '') {
			$host = $this->getInput('What is the hostname for the database server?', null, 'localhost');
			if ($host == '') {
				$this->stdout('The host name you supplied was empty. Please supply a hostname.');
			}
		}
		$login = '';

		while ($login == '') {
			$login = $this->getInput('What is the database username?', null, 'root');

			if ($login == '') {
				$this->stdout('The database username you supplied was empty. Please try again.');
			}
		}
		$password = '';
		$blankPassword = false;

		while ($password == '' && $blankPassword == false) {
			$password = $this->getInput('What is the database password?');
			if ($password == '') {
				$blank = $this->getInput('The password you supplied was empty. Use an empty password?', array('y', 'n'), 'n');
				if($blank == 'y')
				{
					$blankPassword = true;
				}
			}
		}
		$database = '';

		while ($database == '') {
			$database = $this->getInput('What is the name of the database you will be using?', null, 'cake');

			if ($database == '')  {
				$this->stdout('The database name you supplied was empty. Please try again.');
			}
		}

		$prefix = '';

		while ($prefix == '') {
			$prefix = $this->getInput('Enter a table prefix?', null, 'n');
		}
		if(low($prefix) == 'n') {
			$prefix = '';
		}

		$this->stdout('');
		$this->hr();
		$this->stdout('The following database configuration will be created:');
		$this->hr();
		$this->stdout("Driver:        $driver");
		$this->stdout("Connection:    $connect");
		$this->stdout("Host:          $host");
		$this->stdout("User:          $login");
		$this->stdout("Pass:          " . str_repeat('*', strlen($password)));
		$this->stdout("Database:      $database");
		$this->stdout("Table prefix:  $prefix");
		$this->hr();
		$looksGood = $this->getInput('Look okay?', array('y', 'n'), 'y');

		if (low($looksGood) == 'y' || low($looksGood) == 'yes') {
			$this->bakeDbConfig($driver, $connect, $host, $login, $password, $database, $prefix);
		} else {
			$this->stdout('Bake Aborted.');
		}
	}	
/**
 * Creates a database configuration file for Bake.
 *
 * @param string $host
 * @param string $login
 * @param string $password
 * @param string $database
 */
	function bakeDbConfig( $driver, $connect, $host, $login, $password, $database, $prefix) {
		$out = "<?php\n";
		$out .= "class DATABASE_CONFIG {\n\n";
		$out .= "\tvar \$default = array(\n";
		$out .= "\t\t'driver' => '{$driver}',\n";
		$out .= "\t\t'connect' => '{$connect}',\n";
		$out .= "\t\t'host' => '{$host}',\n";
		$out .= "\t\t'login' => '{$login}',\n";
		$out .= "\t\t'password' => '{$password}',\n";
		$out .= "\t\t'database' => '{$database}', \n";
		$out .= "\t\t'prefix' => '{$prefix}' \n";
		$out .= "\t);\n";
		$out .= "}\n";
		$out .= "?>";
		$filename = CONFIGS.'database.php';
		$this->__createFile($filename, $out);
	}
/**
 * Prompts the user for input, and returns it.
 *
 * @param string $prompt Prompt text.
 * @param mixed $options Array or string of options.
 * @param string $default Default input value.
 * @return Either the default value, or the user-provided input.
 */
	function getInput($prompt, $options = null, $default = null) {
		if (!is_array($options)) {
			$print_options = '';
		} else {
			$print_options = '(' . implode('/', $options) . ')';
		}

		if($default == null) {
			$this->stdout('');
			$this->stdout($prompt . " $print_options \n" . '> ', false);
		} else {
			$this->stdout('');
			$this->stdout($prompt . " $print_options \n" . "[$default] > ", false);
		}
		$result = trim(fgets($this->stdin));

		if($default != null && empty($result)) {
			return $default;
		} else {
			return $result;
		}
	}
/**
 * Outputs to the stdout filehandle.
 *
 * @param string $string String to output.
 * @param boolean $newline If true, the outputs gets an added newline.
 */
	function stdout($string, $newline = true) {
		if ($newline) {
			fwrite($this->stdout, $string . "\n");
		} else {
			fwrite($this->stdout, $string);
		}
	}
/**
 * Outputs to the stderr filehandle.
 *
 * @param string $string Error text to output.
 */
	function stderr($string) {
		fwrite($this->stderr, $string);
	}
/**
 * Outputs a series of minus characters to the standard output, acts as a visual separator.
 *
 */
	function hr() {
		$this->stdout('---------------------------------------------------------------');
	}
/**
 * Creates a file at given path.
 *
 * @param string $path		Where to put the file.
 * @param string $contents Content to put in the file.
 * @return Success
 */
	function __createFile ($path, $contents) {
		$path = str_replace('//', '/', $path);
		echo "\nCreating file $path\n";
		if (is_file($path) && $this->interactive === true) {
			fwrite($this->stdout, "File exists, overwrite?" . " {$path} (y/n/q):");
			$key = trim(fgets($this->stdin));

			if ($key=='q') {
				fwrite($this->stdout, "Quitting.\n");
				exit;
			} elseif ($key == 'a') {
				$this->dont_ask = true;
			} elseif ($key == 'y') {
			} else {
				fwrite($this->stdout, "Skip" . " {$path}\n");
				return false;
			}
		}

		if ($f = fopen($path, 'w')) {
			fwrite($f, $contents);
			fclose($f);
			fwrite($this->stdout, "Wrote" . "{$path}\n");
			return true;
		} else {
			fwrite($this->stderr, "Error! Could not write to" . " {$path}.\n");
			return false;
		}
	}
}
?>
