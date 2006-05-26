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
 * CakePHP :  Rapid Development Framework <http://www.cakephp.org/>
 * Copyright (c)	2006, Cake Software Foundation, Inc.
 *								1785 E. Sahara Avenue, Suite 490-204
 *								Las Vegas, Nevada 89104
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @filesource
 * @copyright		Copyright (c) 2006, Cake Software Foundation, Inc.
 * @link				http://www.cakefoundation.org/projects/info/cakephp CakePHP Project
 * @package			cake
 * @subpackage		cake.cake.scripts
 * @since			CakePHP v 0.10.0.1232
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
	for ($i = 1; $i < count($argv); $i += 2)
	{
		// Process command-line modifiers here
		switch (strtolower($argv[$i]))
		{
			case '-app':
				$app = $argv[$i + 1];
			break;
			case '-core':
				$core = $argv[$i + 1];
			break;
			case '-root':
				$root = $argv[$i + 1];
			break;
			case '-datasource':
				$dataSource = $argv[$i + 1];
			break;
		}
	}
	define ('ROOT', $root.DS);
	define ('APP_DIR', $app);
	define ('APP_PATH', $app.DS);
	define ('DEBUG', 1);
	define ('CORE_PATH', $core);
	define ('CAKE_CORE_INCLUDE_PATH', ROOT);
	define('DATASOURCE', $dataSource);
	define ('DEBUG', 1);
	ini_set('include_path',ini_get('include_path').PATH_SEPARATOR.CAKE_CORE_INCLUDE_PATH.PATH_SEPARATOR.ROOT.DS.APP_DIR.DS);

	require ('cake'.DS.'basics.php');
	require ('cake'.DS.'config'.DS.'paths.php');
	require (CONFIGS.'core.php');
	if (file_exists( CONFIGS.'database.php' ))
	{
		require_once (CONFIGS.'database.php');
	}
	else
	{
		die("Unable to find /app/config/database.php.  Please create it before continuing.\n\n");
	}
	uses ('object', 'neat_array', 'session', 'security', 'inflector', 'model'.DS.'connection_manager',
			'model'.DS.'datasources'.DS.'dbo_source', 'model'.DS.'model');
	require(CAKE.'app_model.php');
	uses ('controller'.DS.'components'.DS.'acl', 'controller'.DS.'components'.DS.'dbacl'.DS.'models'.DS.'aclnode',
			'controller'.DS.'components'.DS.'dbacl'.DS.'models'.DS.'aco', 'controller'.DS.'components'.DS.'dbacl'.DS.'models'.DS.'acoaction',
			'controller'.DS.'components'.DS.'dbacl'.DS.'models'.DS.'aro');
	//Get and format args: first arg is the name of the script.
	$serverArgs = env('argv');
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
		$this->dataSource = DATASOURCE;
		$acl = new AclComponent();
		$this->acl = $acl->getACL();
		$this->args = $args;
		$this->db =& ConnectionManager::getDataSource($this->dataSource);
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

		switch ($command){
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
			case 'help':
				$this->help();
			break;
			default:
				fwrite($this->stderr, "Unknown ACL command '$command'.\nFor usage, try 'php acl.php help'.\n\n");
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
		$node = &new $class;

		$parent = intval($this->args[2]);

		if(!$node->create(intval($this->args[1]), $parent, $this->args[3])){
			$this->displayError("Parent Node Not Found", "There was an error creating the Aro, probably couldn't find the parent node.\n If you wish to create a new root node, specify the parent ID as '0'.");
		}
		fwrite($this->stdout, "New $class '".$this->args[3]."' created.\n\n");
	}
/**
 * Enter description here...
 *
 */
	function delete() {
		$this->checkArgNumber(2, 'delete');
		$this->checkNodeType();
		extract($this->__dataVars());
		$node = &new $class;
		//What about children
		//$node->del($this->args[1])
		//fwrite($this->stdout, "$class deleted.\n\n");
	}

/**
 * Enter description here...
 *
 */
	function setParent() {
		$this->checkArgNumber(3, 'setParent');
		$this->checkNodeType();
		extract($this->__dataVars());
		$node = &new $class;

		if (!$node->setParent($this->args[2], $this->args[1])){
			fwrite($this->stdout, "Error in setting new parent. Please make sure the parent node exists, and is not a descendant of the node specified.\n");
		} else {
			fwrite($this->stdout, "Node parent set to ".$this->args[2]."\n\n");
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
		$suppliedNode = $this->nodeExists($this->args[0], $this->args[1]);

		if (!$suppliedNode) {
			$this->displayError("Supplied Node '".$args[1]."' not found. No tree returned.");
		}
		$node = &new $class;
		$nodes = $node->getPath(intval($this->args[1]));

		for ($i = 0; $i < count($nodes); $i++) {
			fwrite($this->stdout, str_repeat('  ', $i) . "[" . $nodes[$i][$class]['id'] . "]" . $nodes[$i][$class]['alias'] . "\n");
		}
	}
/**
 * Enter description here...
 *
 */
	function grant() {
		$this->checkArgNumber(3, 'grant');
		//add existence checks for nodes involved
		$this->acl->allow(intval($this->args[0]), intval($this->args[1]), $this->args[2]);
		fwrite($this->stdout, "Permission granted.\n");
	}
/**
 * Enter description here...
 *
 */
	function deny() {
		$this->checkArgNumber(3, 'deny');
		//add existence checks for nodes involved
		$this->acl->deny(intval($this->args[0]), intval($this->args[1]), $this->args[2]);
		fwrite($this->stdout, "Requested permission successfully denied.\n");
	}
/**
 * Enter description here...
 *
 */
	function inherit() {}
/**
 * Enter description here...
 *
 */
	function view() {
		$this->checkArgNumber(1, 'view');
		$this->checkNodeType();
		extract($this->__dataVars());
		$node = &new $class;
		$nodes = $node->findAll(null, null, 'lft ASC');
		$right = array();

		fwrite($this->stdout, $class . " tree:\n");
		fwrite($this->stdout, "------------------------------------------------\n");

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
			fwrite($this->stdout, str_repeat('  ',count($right)) . "[" . $nodes[$i][$class]['id'] . "]" . $nodes[$i][$class]['alias']."\n");
			$right[] = $nodes[$i][$class]['rght'];
		}
		fwrite($this->stdout, "------------------------------------------------\n");
	}
/**
 * Enter description here...
 *
 */
	function initdb() {
		$db =& ConnectionManager::getDataSource($this->dataSource);
		fwrite($this->stdout, "Initializing Database...\n");
		fwrite($this->stdout, "Creating access control objects table (acos)...\n");
		$sql = " CREATE TABLE ".$db->name('acos')." (
				".$db->name('id')." ".$db->column($db->columns['primary_key']).",
				".$db->name('object_id')." ".$db->column($db->columns['integer'])." default NULL,
				".$db->name('alias')." ".$db->column($db->columns['string'])." NOT NULL default '',
				".$db->name('lft')." ".$db->column($db->columns['integer'])." default NULL,
				".$db->name('rght')." ".$db->column($db->columns['integer'])." default NULL,
				PRIMARY KEY  (".$db->name('id').")
				);";
		$db->query($sql);

		fwrite($this->stdout, "Creating access request objects table (aros)...\n");
		$sql2 = "CREATE TABLE ".$db->name('aros')." (
				".$db->name('id')." ".$db->column($db->columns['primary_key']).",
				".$db->name('user_id')." ".$db->column($db->columns['integer'])." default NULL,
				".$db->name('alias')." ".$db->column($db->columns['string'])." NOT NULL default '',
				".$db->name('lft')." ".$db->column($db->columns['integer'])." default NULL,
				".$db->name('rght')." ".$db->column($db->columns['integer'])." default NULL,
				PRIMARY KEY  (".$db->name('id').")
				);";
		$db->query($sql2);

		fwrite($this->stdout, "Creating relationships table (aros_acos)...\n");
		$sql3 = "CREATE TABLE ".$db->name('aros_acos')." (
				".$db->name('id')." ".$db->column($db->columns['primary_key']).",
				".$db->name('aro_id')." ".$db->column($db->columns['integer'])." default NULL,
				".$db->name('aco_id')." ".$db->column($db->columns['integer'])." default NULL,
				".$db->name('_create')." ".$db->column($db->columns['integer'])." NOT NULL default '0',
				".$db->name('_read')." ".$db->column($db->columns['integer'])." NOT NULL default '0',
				".$db->name('_update')." ".$db->column($db->columns['integer'])." NOT NULL default '0',
				".$db->name('_delete')." ".$db->column($db->columns['integer'])." NOT NULL default '0',
				PRIMARY KEY  (".$db->name('id').")
				);";
		$db->query($sql3);

		fwrite($this->stdout, "\nDone.\n");
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
		$out .= "\t\tCreates a new ACL object under the parent specified by parent_id (see\n";
		$out .= "\t\t'view'). The link_id allows you to link a current user object to Cake's\n";
		$out .= "\t\tACL structures. The alias parameter allows you address your object\n";
		$out .= "\t\tusing a non-integer ID. Example: \"\$php acl.php create aro 0 jda57 John\"\n";
		$out .= "\t\twould create a new ARO object at the root of the tree, linked to jda57\n";
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
		$out .= "\tinherit <aro_id> \n";
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
		fwrite($this->stdout, $out);
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
		fwrite($this->stdout, $out);
		exit();
	}

/**
 * Enter description here...
 *
 * @param unknown_type $expectedNum
 * @param unknown_type $command
 */
	function checkArgNumber($expectedNum, $command) {
		if (count($this->args) != $expectedNum) {
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
		//fwrite($this->stdout, "Check to see if $type with ID = $id exists...\n");
		extract($this->__dataVars($type));
		$node = &new $class;
		$possibility = $node->find('id = ' . $id);

		if (empty($possibility[$class]['id'])) {
			return false;
		} else {
			return $possibility;
		}
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
		$vars['secondary_id'] = ($class == 'aro' ? 'user_id' : 'object_id');
		$vars['data_name'] = $type;
		$vars['table_name'] = $type . 's';
		$vars['class'] = $class;
		return $vars;
	}
}
?>