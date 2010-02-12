<?php
/* SVN FILE: $Id$ */
/**
 * Short description for file.
 *
 * Long description for file
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       cake
 * @subpackage    cake.cake.console.libs
 * @since         CakePHP(tm) v 1.2.0.5012
 * @version       $Revision$
 * @modifiedby    $LastChangedBy$
 * @lastmodified  $Date$
 * @license       http://www.opensource.org/licenses/mit-license.php The MIT License
 */
App::import('Component', 'Acl');
App::import('Model', 'DbAcl');
/**
 * Shell for ACL management.
 *
 * @package       cake
 * @subpackage    cake.cake.console.libs
 */
class AclShell extends Shell {
/**
 * Contains instance of AclComponent
 *
 * @var AclComponent
 * @access public
 */
	var $Acl;
/**
 * Contains arguments parsed from the command line.
 *
 * @var array
 * @access public
 */
	var $args;
/**
 * Contains database source to use
 *
 * @var string
 * @access public
 */
	var $dataSource = 'default';
/**
 * Contains tasks to load and instantiate
 *
 * @var array
 * @access public
 */
	var $tasks = array('DbConfig');
/**
 * Override startup of the Shell
 *
 * @access public
 */
	function startup() {
		$this->dataSource = 'default';

		if (isset($this->params['datasource'])) {
			$this->dataSource = $this->params['datasource'];
		}

		if (!in_array(Configure::read('Acl.classname'), array('DbAcl', 'DB_ACL'))) {
			$out = "--------------------------------------------------\n";
			$out .= __("Error: Your current Cake configuration is set to", true) . "\n";
			$out .= __("an ACL implementation other than DB. Please change", true) . "\n";
			$out .= __("your core config to reflect your decision to use", true) . "\n";
			$out .= __("DbAcl before attempting to use this script", true) . ".\n";
			$out .= "--------------------------------------------------\n";
			$out .= sprintf(__("Current ACL Classname: %s", true), Configure::read('Acl.classname')) . "\n";
			$out .= "--------------------------------------------------\n";
			$this->err($out);
			$this->_stop();
		}

		if ($this->command && !in_array($this->command, array('help'))) {
			if (!config('database')) {
				$this->out(__("Your database configuration was not found. Take a moment to create one.", true), true);
				$this->args = null;
				return $this->DbConfig->execute();
			}
			require_once (CONFIGS.'database.php');

			if (!in_array($this->command, array('initdb'))) {
				$this->Acl = new AclComponent();
				$controller = null;
				$this->Acl->startup($controller);
			}
		}
	}
/**
 * Override main() for help message hook
 *
 * @access public
 */
	function main() {
		$out  = __("Available ACL commands:", true) . "\n";
		$out .= "\t - create\n";
		$out .= "\t - delete\n";
		$out .= "\t - setParent\n";
		$out .= "\t - getPath\n";
		$out .= "\t - check\n";
		$out .= "\t - grant\n";
		$out .= "\t - deny\n";
		$out .= "\t - inherit\n";
		$out .= "\t - view\n";
		$out .= "\t - initdb\n";
		$out .= "\t - help\n\n";
		$out .= __("For help, run the 'help' command.  For help on a specific command, run 'help <command>'", true);
		$this->out($out);
	}
/**
 * Creates an ARO/ACO node
 *
 * @access public
 */
	function create() {

		$this->_checkArgs(3, 'create');
		$this->checkNodeType();
		extract($this->__dataVars());

		$class = ucfirst($this->args[0]);
		$object = new $class();

		if (preg_match('/^([\w]+)\.(.*)$/', $this->args[1], $matches) && count($matches) == 3) {
			$parent = array(
				'model' => $matches[1],
				'foreign_key' => $matches[2],
			);
		} else {
			$parent = $this->args[1];
		}

		if (!empty($parent) && $parent != '/' && $parent != 'root') {
			@$parent = $object->node($parent);
			if (empty($parent)) {
				$this->err(sprintf(__('Could not find parent node using reference "%s"', true), $this->args[1]));
				return;
			} else {
				$parent = Set::extract($parent, "0.{$class}.id");
			}
		} else {
			$parent = null;
		}

		if (preg_match('/^([\w]+)\.(.*)$/', $this->args[2], $matches) && count($matches) == 3) {
			$data = array(
				'model' => $matches[1],
				'foreign_key' => $matches[2],
			);
		} else {
			if (!($this->args[2] == '/')) {
				$data = array('alias' => $this->args[2]);
			} else {
				$this->error(__('/ can not be used as an alias!', true), __('\t/ is the root, please supply a sub alias', true));
			}
		}

		$data['parent_id'] = $parent;
		$object->create();

		if ($object->save($data)) {
			$this->out(sprintf(__("New %s '%s' created.\n", true), $class, $this->args[2]), true);
		} else {
			$this->err(sprintf(__("There was a problem creating a new %s '%s'.", true), $class, $this->args[2]));
		}
	}
/**
 * Delete an ARO/ACO node.
 *
 * @access public
 */
	function delete() {
		$this->_checkArgs(2, 'delete');
		$this->checkNodeType();
		extract($this->__dataVars());
		if (!$this->Acl->{$class}->delete($this->args[1])) {
			$this->error(__("Node Not Deleted", true), sprintf(__("There was an error deleting the %s. Check that the node exists", true), $class) . ".\n");
		}
		$this->out(sprintf(__("%s deleted", true), $class) . ".\n", true);
	}

/**
 * Set parent for an ARO/ACO node.
 *
 * @access public
 */
	function setParent() {
		$this->_checkArgs(3, 'setParent');
		$this->checkNodeType();
		extract($this->__dataVars());
		$data = array(
			$class => array(
				'id' => $this->args[1],
				'parent_id' => $this->args[2]
			)
		);
		$this->Acl->{$class}->create();
		if (!$this->Acl->{$class}->save($data)) {
			$this->out(__("Error in setting new parent. Please make sure the parent node exists, and is not a descendant of the node specified.", true), true);
		} else {
			$this->out(sprintf(__("Node parent set to %s", true), $this->args[2]) . "\n", true);
		}
	}
/**
 * Get path to specified ARO/ACO node.
 *
 * @access public
 */
	function getPath() {
		$this->_checkArgs(2, 'getPath');
		$this->checkNodeType();
		extract($this->__dataVars());
		$id = is_numeric($this->args[1]) ? intval($this->args[1]) : $this->args[1];
		$nodes = $this->Acl->{$class}->getPath($id);
		if (empty($nodes)) {
			$this->error(sprintf(__("Supplied Node '%s' not found", true), $this->args[1]), __("No tree returned.", true));
		}
		for ($i = 0; $i < count($nodes); $i++) {
			$this->out(str_repeat('  ', $i) . "[" . $nodes[$i][$class]['id'] . "]" . $nodes[$i][$class]['alias'] . "\n");
		}
	}
/**
 * Check permission for a given ARO to a given ACO.
 *
 * @access public
 */
	function check() {
		$this->_checkArgs(3, 'check');
		extract($this->__getParams());

		if ($this->Acl->check($aro, $aco, $action)) {
			$this->out(sprintf(__("%s is allowed.", true), $aro), true);
		} else {
			$this->out(sprintf(__("%s is not allowed.", true), $aro), true);
		}
	}
/**
 * Grant permission for a given ARO to a given ACO.
 *
 * @access public
 */
	function grant() {
		$this->_checkArgs(3, 'grant');
		extract($this->__getParams());

		if ($this->Acl->allow($aro, $aco, $action)) {
			$this->out(__("Permission granted.", true), true);
		} else {
			$this->out(__("Permission was not granted.", true), true);
		}
	}
/**
 * Deny access for an ARO to an ACO.
 *
 * @access public
 */
	function deny() {
		$this->_checkArgs(3, 'deny');
		extract($this->__getParams());

		if ($this->Acl->deny($aro, $aco, $action)) {
			$this->out(__("Permission denied.", true), true);
		} else {
			$this->out(__("Permission was not denied.", true), true);
		}
	}
/**
 * Set an ARO to inhermit permission to an ACO.
 *
 * @access public
 */
	function inherit() {
		$this->_checkArgs(3, 'inherit');
		extract($this->__getParams());

		if ($this->Acl->inherit($aro, $aco, $action)) {
			$this->out(__("Permission inherited.", true), true);
		} else {
			$this->out(__("Permission was not inherited.", true), true);
		}
	}
/**
 * Show a specific ARO/ACO node.
 *
 * @access public
 */
	function view() {
		$this->_checkArgs(1, 'view');
		$this->checkNodeType();
		extract($this->__dataVars());
		if (isset($this->args[1]) && !is_null($this->args[1])) {
			$key = is_numeric($this->args[1]) ? $secondary_id : 'alias';
			$conditions = array($class . '.' . $key => $this->args[1]);
		} else {
			$conditions = null;
		}
		$nodes = $this->Acl->{$class}->find('all', array('conditions' => $conditions, 'order' => 'lft ASC'));
		if (empty($nodes)) {
			if (isset($this->args[1])) {
				$this->error(sprintf(__("%s not found", true), $this->args[1]), __("No tree returned.", true));
			} elseif (isset($this->args[0])) {
				$this->error(sprintf(__("%s not found", true), $this->args[0]), __("No tree returned.", true));
			}
		}
		$this->out($class . " tree:");
		$this->hr();
		$stack = array();
		$last  = null;
		foreach ($nodes as $n) {
			$stack[] = $n;
			if (!empty($last)) {
				$end = end($stack);
				if ($end[$class]['rght'] > $last) {
					foreach ($stack as $k => $v) {
						$end = end($stack);
						if ($v[$class]['rght'] < $end[$class]['rght']) {
							unset($stack[$k]);
						}
					}
				}
			}
			$last   = $n[$class]['rght'];
			$count  = count($stack);
			$indent = str_repeat('  ', $count);
			if ($n[$class]['alias']) {
				$this->out($indent . "[" . $n[$class]['id'] . "]" . $n[$class]['alias']."\n");
			 } else {
				$this->out($indent . "[" . $n[$class]['id'] . "]" . $n[$class]['model'] . '.' . $n[$class]['foreign_key'] . "\n");
			}
		}
		$this->hr();
	}
/**
 * Initialize ACL database.
 *
 * @access public
 */
	function initdb() {
		$this->Dispatch->args = array('schema', 'run', 'create', 'DbAcl');
		$this->Dispatch->dispatch();
	}
/**
 * Show help screen.
 *
 * @access public
 */
	function help() {
		$head  = __("Usage: cake acl <command> <arg1> <arg2>...", true) . "\n";
		$head .= "-----------------------------------------------\n";
		$head .= __("Commands:", true) . "\n\n";

		$commands = array(
			'create' => "\tcreate aro|aco <parent> <node>\n" .
						"\t\t" . __("Creates a new ACL object <node> under the parent specified by <parent>, an id/alias.", true) . "\n" .
						"\t\t" . __("The <parent> and <node> references can be in one of the following formats:", true) . "\n" .
						"\t\t\t- " . __("<model>.<id> - The node will be bound to a specific record of the given model", true) . "\n" .
						"\t\t\t- " . __("<alias> - The node will be given a string alias (or path, in the case of <parent>),", true) . "\n" .
						"\t\t\t  " . __("i.e. 'John'.  When used with <parent>, this takes the form of an alias path,", true) . "\n" .
						"\t\t\t  " . __("i.e. <group>/<subgroup>/<parent>.", true) . "\n" .
						"\t\t" . __("To add a node at the root level, enter 'root' or '/' as the <parent> parameter.", true) . "\n",

			'delete' =>	"\tdelete aro|aco <node>\n" .
						"\t\t" . __("Deletes the ACL object with the given <node> reference (see 'create' for info on node references).", true) . "\n",

			'setparent' => "\tsetParent aro|aco <node> <parent>\n" .
							"\t\t" . __("Moves the ACL object specified by <node> beneath the parent ACL object specified by <parent>.", true) . "\n" .
							"\t\t" . __("To identify the node and parent, use the row id.", true) . "\n",

			'getpath' => "\tgetPath aro|aco <node>\n" .
						"\t\t" . __("Returns the path to the ACL object specified by <node>. This command", true) . "\n" .
						"\t\t" . __("is useful in determining the inhertiance of permissions for a certain", true) . "\n" .
						"\t\t" . __("object in the tree.", true) . "\n" .
						"\t\t" . __("For more detailed parameter usage info, see help for the 'create' command.", true) . "\n",

			'check' =>	"\tcheck <aro_id> <aco_id> [<aco_action>] " . __("or", true) . " all\n" .
						"\t\t" . __("Use this command to check ACL permissions.", true) . "\n" .
						"\t\t" . __("For more detailed parameter usage info, see help for the 'create' command.", true) . "\n",

			'grant' =>	"\tgrant <aro_id> <aco_id> [<aco_action>] " . __("or", true) . " all\n" .
						"\t\t" . __("Use this command to grant ACL permissions. Once executed, the ARO", true) . "\n" .
						"\t\t" . __("specified (and its children, if any) will have ALLOW access to the", true) . "\n" .
						"\t\t" . __("specified ACO action (and the ACO's children, if any).", true) . "\n" .
						"\t\t" . __("For more detailed parameter usage info, see help for the 'create' command.", true) . "\n",

			'deny' =>	"\tdeny <aro_id> <aco_id> [<aco_action>]" . __("or", true) . " all\n" .
						"\t\t" . __("Use this command to deny ACL permissions. Once executed, the ARO", true) . "\n" .
						"\t\t" . __("specified (and its children, if any) will have DENY access to the", true) . "\n" .
						"\t\t" . __("specified ACO action (and the ACO's children, if any).", true) . "\n" .
						"\t\t" . __("For more detailed parameter usage info, see help for the 'create' command.", true) . "\n",

			'inherit' =>	"\tinherit <aro_id> <aco_id> [<aco_action>]" . __("or", true) . " all\n" .
							"\t\t" . __("Use this command to force a child ARO object to inherit its", true) . "\n" .
							"\t\t" . __("permissions settings from its parent.", true) . "\n" .
							"\t\t" . __("For more detailed parameter usage info, see help for the 'create' command.", true) . "\n",

			'view' =>	"\tview aro|aco [<node>]\n" .
						"\t\t" . __("The view command will return the ARO or ACO tree. The optional", true) . "\n" .
						"\t\t" . __("id/alias parameter allows you to return only a portion of the requested tree.", true) . "\n" .
						"\t\t" . __("For more detailed parameter usage info, see help for the 'create' command.", true) . "\n",

			'initdb' =>	"\tinitdb\n".
						"\t\t" . __("Uses this command : cake schema run create DbAcl", true) . "\n",

			'help' => 	"\thelp [<command>]\n" .
						"\t\t" . __("Displays this help message, or a message on a specific command.", true) . "\n"
		);

		$this->out($head);
		if (!isset($this->args[0])) {
			foreach ($commands as $cmd) {
				$this->out("{$cmd}\n\n");
			}
		} elseif (isset($commands[strtolower($this->args[0])])) {
			$this->out($commands[strtolower($this->args[0])] . "\n\n");
		} else {
			$this->out(sprintf(__("Command '%s' not found", true), $this->args[0]));
		}
	}
/**
 * Check that first argument specifies a valid Node type (ARO/ACO)
 *
 * @access public
 */
	function checkNodeType() {
		if (!isset($this->args[0])) {
			return false;
		}
		if ($this->args[0] != 'aco' && $this->args[0] != 'aro') {
			$this->error(sprintf(__("Missing/Unknown node type: '%s'", true), $this->args[1]), __('Please specify which ACL object type you wish to create.', true));
		}
	}
/**
 * Checks that given node exists
 *
 * @param string $type Node type (ARO/ACO)
 * @param integer $id Node id
 * @return boolean Success
 * @access public
 */
	function nodeExists() {
		if (!$this->checkNodeType() && !isset($this->args[1])) {
			return false;
		}
		extract($this->__dataVars($this->args[0]));
		$key = is_numeric($this->args[1]) ? $secondary_id : 'alias';
		$conditions = array($class . '.' . $key => $this->args[1]);
		$possibility = $this->Acl->{$class}->find('all', compact('conditions'));
		if (empty($possibility)) {
			$this->error(sprintf(__("%s not found", true), $this->args[1]), __("No tree returned.", true));
		}
		return $possibility;
	}
/**
 * get params for standard Acl methods
 *
 * @return array aro, aco, action
 * @access private
 */
	function __getParams() {
		$aro = is_numeric($this->args[0]) ? intval($this->args[0]) : $this->args[0];
		$aco = is_numeric($this->args[1]) ? intval($this->args[1]) : $this->args[1];

		if (is_string($aro) && preg_match('/^([\w]+)\.(.*)$/', $aro, $matches)) {
			$aro = array(
				'model' => $matches[1],
				'foreign_key' => $matches[2],
			);
		}

		if (is_string($aco) && preg_match('/^([\w]+)\.(.*)$/', $aco, $matches)) {
			$aco = array(
				'model' => $matches[1],
				'foreign_key' => $matches[2],
			);
		}

		$action = null;
		if (isset($this->args[2])) {
			$action = $this->args[2];
			if ($action == '' || $action == 'all') {
				$action = '*';
			}
		}
		return compact('aro', 'aco', 'action');
	}

/**
 * Build data parameters based on node type
 *
 * @param string $type Node type  (ARO/ACO)
 * @return array Variables
 * @access private
 */
	function __dataVars($type = null) {
		if ($type == null) {
			$type = $this->args[0];
		}
		$vars = array();
		$class = ucwords($type);
		$vars['secondary_id'] = strtolower($class) == 'aro' ? 'foreign_key' : 'object_id';
		$vars['data_name'] = $type;
		$vars['table_name'] = $type . 's';
		$vars['class'] = $class;
		return $vars;
	}
}
?>