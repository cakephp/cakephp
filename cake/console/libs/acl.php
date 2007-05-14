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
 * @subpackage		cake.cake.console.libs
 * @since			CakePHP(tm) v 1.2.0.5012
 * @version			$Revision$
 * @modifiedby		$LastChangedBy$
 * @lastmodified	$Date$
 * @license			http://www.opensource.org/licenses/mit-license.php The MIT License
 */
uses ('controller'.DS.'components'.DS.'acl', 'model'.DS.'db_acl');
/**
 * @package		cake
 * @subpackage	cake.cake.console.libs
 */
class AclShell extends Shell {
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
 * override intialize of the Shell
 *
 */
	function initialize () {
		$this->dataSource = 'default';

		if (isset($this->params['datasource'])) {
			$this->dataSource = $this->params['datasource'];
		}

		if (ACL_CLASSNAME != 'DB_ACL') {
			$out = "--------------------------------------------------\n";
			$out .= "Error: Your current Cake configuration is set to \n";
			$out .= "an ACL implementation other than DB. Please change \n";
			$out .= "your core config to reflect your decision to use \n";
			$out .= "DB_ACL before attempting to use this script.\n";
			$out .= "--------------------------------------------------\n";
			$out .= "Current ACL Classname: " . ACL_CLASSNAME . "\n";
			$out .= "--------------------------------------------------\n";
			$this->err($out);
			exit();
		}

		//$this->Dispatch->shiftArgs();


		if($this->command && !in_array($this->command, array('help'))) {
			if(!file_exists(CONFIGS.'database.php')) {
				$this->out('');
				$this->out('Your database configuration was not found.');
				$this->out('Take a moment to create one:');
				$this->__doDbConfig();
			}
			require_once (CONFIGS.'database.php');

			if(!in_array($this->command, array('initdb'))) {
				$this->Acl = new AclComponent();
				$this->db =& ConnectionManager::getDataSource($this->dataSource);
			}
		}

	}
/**
 * Override main() for help message hook
 *
 */
	function main() {
		$out  = "Available ACL commands:\n";
		$out .= "\t - create\n";
		$out .= "\t - delete\n";
		$out .= "\t - setParent\n";
		$out .= "\t - getPath\n";
		$out .= "\t - grant\n";
		$out .= "\t - deny\n";
		$out .= "\t - inherit\n";
		$out .= "\t - view\n";
		$out .= "\t - initdb\n";
		$out .= "\t - help\n\n";
		$out .= "For help, run the 'help' command.  For help on a specific command, run 'help <command>'";
		$this->out($out);
	}
/**
 * Creates an ARO/ACO node
 *
 */
	function create() {
		$this->_checkArgs(3, 'create');
		$this->checkNodeType();
		extract($this->__dataVars());

		$class = ucfirst($this->args[1]);
		$object = new $class();

		if (preg_match('/^([\w]+)\.(.*)$/', $this->args[2], $matches) && count($matches) == 3) {
			$parent = array(
				'model' => $matches[1],
				'foreign_key' => $matches[2],
			);
		} else {
			$parent = $this->args[2];
		}

		if (!empty($parent) && $parent != '/' && $parent != 'root') {
			@$parent = $object->node($parent);
			if (empty($parent)) {
				$this->err('Could not find parent node using reference "' . $this->args[2] . '"');
				return;
			} else {
				$parent = Set::extract($parent, "0.{$class}.id");
			}
		} else {
			$parent = null;
		}

		if (preg_match('/^([\w]+)\.(.*)$/', $this->args[3], $matches) && count($matches) == 3) {
			$data = array(
				'model' => $matches[1],
				'foreign_key' => $matches[2],
			);
		} else {
			$data = array('alias' => $this->args[3]);
		}

		$data['parent_id'] = $parent;
		$object->create();

		if($object->save($data)) {
			$this->out("New $class '".$this->args[3]."' created.\n\n");
		} else {
			$this->err("There was a problem creating a new $class '".$this->args[3]."'.");
		}
	}
/**
 * Enter description here...
 *
 */
	function delete() {
		$this->_checkArgs(2, 'delete');
		$this->checkNodeType();
		extract($this->__dataVars());
		if(!$this->Acl->{$class}->delete($this->args[1])) {
			$this->displayError("Node Not Deleted", "There was an error deleting the ".$class.". Check that the node exists.\n");
		}
		$this->out("{$class} deleted.\n\n");
	}

/**
 * Enter description here...
 *
 */
	function setParent() {
		$this->_checkArgs(3, 'setParent');
		$this->checkNodeType();
		extract($this->__dataVars());
		if (!$this->Acl->{$class}->setParent($this->args[2], $this->args[1])){
			$this->out("Error in setting new parent. Please make sure the parent node exists, and is not a descendant of the node specified.\n");
		} else {
			$this->out("Node parent set to ".$this->args[2]."\n\n");
		}
	}
/**
 * Enter description here...
 *
 */
	function getPath() {
		$this->_checkArgs(2, 'getPath');
		$this->checkNodeType();
		extract($this->__dataVars());
		$id = (is_numeric($this->args[2])) ? intval($this->args[1]) : $this->args[1];
		$nodes = $this->Acl->{$class}->getPath($id);
		if (empty($nodes)) {
			$this->displayError("Supplied Node '".$this->args[1]."' not found", "No tree returned.");
		}
		for ($i = 0; $i < count($nodes); $i++) {
			$this->out(str_repeat('  ', $i) . "[" . $nodes[$i][$class]['id'] . "]" . $nodes[$i][$class]['alias'] . "\n");
		}
	}
/**
 * Enter description here...
 *
 */
	function grant() {
		$this->_checkArgs(3, 'grant');
		//add existence checks for nodes involved
		$aro = (is_numeric($this->args[0])) ? intval($this->args[0]) : $this->args[0];
		$aco = (is_numeric($this->args[1])) ? intval($this->args[1]) : $this->args[1];
		$this->Acl->allow($aro, $aco, $this->args[2]);
		$this->out("Permission granted.\n");
	}
/**
 * Enter description here...
 *
 */
	function deny() {
		$this->_checkArgs(3, 'deny');
		//add existence checks for nodes involved
		$aro = (is_numeric($this->args[0])) ? intval($this->args[0]) : $this->args[0];
		$aco = (is_numeric($this->args[1])) ? intval($this->args[1]) : $this->args[1];
		$this->Acl->deny($aro, $aco, $this->args[2]);
		$this->out("Requested permission successfully denied.\n");
	}
/**
 * Enter description here...
 *
 */
	function inherit() {
		$this->_checkArgs(3, 'inherit');
		$aro = (is_numeric($this->args[0])) ? intval($this->args[0]) : $this->args[0];
		$aco = (is_numeric($this->args[1])) ? intval($this->args[1]) : $this->args[1];
		$this->Acl->inherit($aro, $aco, $this->args[2]);
		$this->out("Requested permission successfully inherited.\n");
	}
/**
 * Enter description here...
 *
 */
	function view() {
		$this->_checkArgs(1, 'view');
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

		$this->out($class . " tree:");
		$this->hr(true);

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
			$this->out(str_repeat('  ',count($right)) . "[" . $nodes[$i][$class]['id'] . "]" . $nodes[$i][$class]['alias']."\n");
			$right[] = $nodes[$i][$class]['rght'];
		}
		$this->hr(true);
	}
/**
 * Enter description here...
 *
 */
	function initdb() {
		$db =& ConnectionManager::getDataSource($this->dataSource);
		$this->out("Initializing Database...\n");
		$this->out("Creating access control objects table (acos)...\n");
		$sql = " CREATE TABLE ".$db->fullTableName('acos')." (
				".$db->name('id')." ".$db->column($db->columns['primary_key']).",
				".$db->name('parent_id')." ".$db->column($db->columns['integer'])." default NULL,
				".$db->name('model')." ".$db->column($db->columns['string'])." default '' NOT NULL,
				".$db->name('foreign_key')." ".$db->column($db->columns['integer'])." default NULL,
				".$db->name('alias')." ".$db->column($db->columns['string'])." default '' NOT NULL,
				".$db->name('lft')." ".$db->column($db->columns['integer'])." default NULL,
				".$db->name('rght')." ".$db->column($db->columns['integer'])." default NULL,
				PRIMARY KEY  (".$db->name('id').")
				)";
		if ($db->query($sql) === false) {
			die("Error: " . $db->lastError() . "\n\n");
		}

		$this->out("Creating access request objects table (aros)...\n");
		$sql2 = "CREATE TABLE ".$db->fullTableName('aros')." (
				".$db->name('id')." ".$db->column($db->columns['primary_key']).",
				".$db->name('parent_id')." ".$db->column($db->columns['integer'])." default NULL,
				".$db->name('model')." ".$db->column($db->columns['string'])." default '' NOT NULL,
				".$db->name('foreign_key')." ".$db->column($db->columns['integer'])." default NULL,
				".$db->name('alias')." ".$db->column($db->columns['string'])." default '' NOT NULL,
				".$db->name('lft')." ".$db->column($db->columns['integer'])." default NULL,
				".$db->name('rght')." ".$db->column($db->columns['integer'])." default NULL,
				PRIMARY KEY  (".$db->name('id').")
				)";
		if ($db->query($sql2) === false) {
			die("Error: " . $db->lastError() . "\n\n");
		}

		$this->out("Creating relationships table (aros_acos)...\n");
		$sql3 = "CREATE TABLE ".$db->fullTableName('aros_acos')." (
				".$db->name('id')." ".$db->column($db->columns['primary_key']).",
				".$db->name('aro_id')." ".$db->column($db->columns['integer'])." default NULL,
				".$db->name('aco_id')." ".$db->column($db->columns['integer'])." default NULL,
				".$db->name('_create')." ".$db->column($db->columns['integer'])." default '0' NOT NULL,
				".$db->name('_read')." ".$db->column($db->columns['integer'])." default '0' NOT NULL,
				".$db->name('_update')." ".$db->column($db->columns['integer'])." default '0' NOT NULL,
				".$db->name('_delete')." ".$db->column($db->columns['integer'])." default '0' NOT NULL,
				PRIMARY KEY  (".$db->name('id').")
				)";
		if ($db->query($sql3) === false) {
			die("Error: " . $db->lastError() . "\n\n");
		}

		$this->out("\nDone.\n");
	}

/**
 * Enter description here...
 *
 */
	function help() {
		$head  = "Usage: cake acl <command> <arg1> <arg2>...\n";
		$head .= "-----------------------------------------------\n";
		$head .= "Commands:\n\n";

		$commands = array(
			'create' => "\tcreate aro|aco <parent> <node>\n" .
						"\t\tCreates a new ACL object <node> under the parent specified by <parent>, an id/alias.\n" .
						"\t\tThe <parent> and <node> references can be in one of the following formats:\n" .
						"\t\t\t- <model>.<id> - The node will be bound to a specific record of the given model\n" .
						"\t\t\t- <alias> - The node will be given a string alias (or path, in the case of <parent>),\n" .
						"\t\t\t  i.e. 'John'.  When used with <parent>, this takes the form of an alias path,\n" .
						"\t\t\t  i.e. <group>/<subgroup>/<parent>.\n",

			'delete' =>	"\tdelete aro|aco <node>\n" .
						"\t\tDeletes the ACL object with the given <node> reference (see 'create' for info on node references).\n",

			'setparent' => "\tsetParent aro|aco <node> <parent>\n" .
							"\t\tMoves the ACL object specified by <node> beneath the parent ACL object specified by <parent>.\n",

			'getpath' => "\tgetPath aro|aco <node>\n" .
						"\t\tReturns the path to the ACL object specified by <node>. This command is\n" .
						"\t\tis useful in determining the inhertiance of permissions for a certain\n" .
						"\t\tobject in the tree.\n",

			'grant' =>	"\tgrant <aro_id> <aco_id> [<aco_action>]\n" .
						"\t\tUse this command to grant ACL permissions. Once executed, the ARO\n" .
						"\t\tspecified (and its children, if any) will have ALLOW access to the\n" .
						"\t\tspecified ACO action (and the ACO's children, if any).\n",

			'deny' =>	"\tdeny <aro_id> <aco_id> [<aco_action>]\n" .
						"\t\tUse this command to deny ACL permissions. Once executed, the ARO\n" .
						"\t\tspecified (and its children, if any) will have DENY access to the\n" .
						"\t\tspecified ACO action (and the ACO's children, if any).\n",

			'inherit' =>	"\tinherit <aro_id> <aco_id> [<aco_action>]\n" .
							"\t\tUse this command to force a child ARO object to inherit its\n" .
							"\t\tpermissions settings from its parent.\n",

			'view' =>	"\tview aro|aco [<node>]\n" .
						"\t\tThe view command will return the ARO or ACO tree. The optional\n" .
						"\t\tid/alias parameter allows you to return only a portion of the requested\n" .
						"\t\ttree.\n",

			'initdb' =>	"\tinitdb\n".
						"\t\tUse this command to create the database tables needed to use DB ACL.\n",

			'help' => 	"\thelp [<command>]\n" .
						"\t\tDisplays this help message, or a message on a specific command.\n"
		);

		$this->out($head);
		if (!isset($this->args[1])) {
			foreach ($commands as $cmd) {
				$this->out("{$cmd}\n\n");
			}
		} elseif (isset($commands[low($this->args[1])])) {
			$this->out($commands[low($this->args[1])]);
		} else {
			$this->out("Command '" . $this->args[1] . "' not found");
		}
	}
/**
 * Enter description here...
 *
 */
	function checkNodeType() {
		if ($this->args[1] != 'aco' && $this->args[1] != 'aro') {
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
		//$this->out("Check to see if $type with ID = $id exists...\n");
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
	function __doDbConfig() {
		$this->hr(true);
		$this->out('Database Configuration:');
		$this->hr(true);

		$driver = '';

		while ($driver == '') {
			$driver = $this->in('What database driver would you like to use?', array('mysql','mysqli','mssql','sqlite','postgres', 'odbc', 'oracle'), 'mysql');
			if ($driver == '') {
				$this->out('The database driver supplied was empty. Please supply a database driver.');
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
			case 'oracle':
			$connect = 'ocilogon';
			break;
			default:
			$this->out('The connection parameter could not be set.');
			break;
		}

		$host = '';

		while ($host == '') {
			$host = $this->in('What is the hostname for the database server?', null, 'localhost');
			if ($host == '') {
				$this->out('The host name you supplied was empty. Please supply a hostname.');
			}
		}
		$login = '';

		while ($login == '') {
			$login = $this->in('What is the database username?', null, 'root');

			if ($login == '') {
				$this->out('The database username you supplied was empty. Please try again.');
			}
		}
		$password = '';
		$blankPassword = false;

		while ($password == '' && $blankPassword == false) {
			$password = $this->in('What is the database password?');
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
			$database = $this->in('What is the name of the database you will be using?', null, 'cake');

			if ($database == '')  {
				$this->out('The database name you supplied was empty. Please try again.');
			}
		}

		$prefix = '';

		while ($prefix == '') {
			$prefix = $this->in('Enter a table prefix?', null, 'n');
		}
		if(low($prefix) == 'n') {
			$prefix = '';
		}

		$this->hr(true);
		$this->out('The following database configuration will be created:');
		$this->hr(true);
		$this->out("Driver:        $driver");
		$this->out("Connection:    $connect");
		$this->out("Host:          $host");
		$this->out("User:          $login");
		$this->out("Pass:          " . str_repeat('*', strlen($password)));
		$this->out("Database:      $database");
		$this->out("Table prefix:  $prefix");
		$this->hr(true);
		$looksGood = $this->in('Look okay?', array('y', 'n'), 'y');

		if (low($looksGood) == 'y' || low($looksGood) == 'yes') {
			$this->bakeDbConfig($driver, $connect, $host, $login, $password, $database, $prefix);
		} else {
			$this->out('Bake Aborted.');
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
		$this->createFile($filename, $out);
	}
}

?>
