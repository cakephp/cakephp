<?php
/**
 * Acl Shell provides Acl access in the CLI environment
 *
 * PHP 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2011, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2011, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         CakePHP(tm) v 1.2.0.5012
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
App::uses('ComponentCollection', 'Controller');
App::uses('AclComponent', 'Controller/Component');
App::uses('DbAcl', 'Model');

/**
 * Shell for ACL management.  This console is known to have issues with zend.ze1_compatibility_mode
 * being enabled.  Be sure to turn it off when using this shell.
 *
 * @package       Cake.Console.Command
 */
class AclShell extends Shell {

/**
 * Contains instance of AclComponent
 *
 * @var AclComponent
 */
	public $Acl;

/**
 * Contains arguments parsed from the command line.
 *
 * @var array
 */
	public $args;

/**
 * Contains database source to use
 *
 * @var string
 */
	public $connection = 'default';

/**
 * Contains tasks to load and instantiate
 *
 * @var array
 */
	public $tasks = array('DbConfig');

/**
 * Override startup of the Shell
 *
 * @return void
 */
	public function startup() {
		parent::startup();
		if (isset($this->params['connection'])) {
			$this->connection = $this->params['connection'];
		}

		if (!in_array(Configure::read('Acl.classname'), array('DbAcl', 'DB_ACL'))) {
			$out = "--------------------------------------------------\n";
			$out .= __d('cake_console', 'Error: Your current Cake configuration is set to an ACL implementation other than DB.') . "\n";
			$out .= __d('cake_console', 'Please change your core config to reflect your decision to use DbAcl before attempting to use this script') . "\n";
			$out .= "--------------------------------------------------\n";
			$out .= __d('cake_console', 'Current ACL Classname: %s', Configure::read('Acl.classname')) . "\n";
			$out .= "--------------------------------------------------\n";
			$this->err($out);
			$this->_stop();
		}

		if ($this->command) {
			if (!config('database')) {
				$this->out(__d('cake_console', 'Your database configuration was not found. Take a moment to create one.'), true);
				$this->args = null;
				return $this->DbConfig->execute();
			}
			require_once (APP . 'Config' . DS . 'database.php');

			if (!in_array($this->command, array('initdb'))) {
				$collection = new ComponentCollection();
				$this->Acl = new AclComponent($collection);
				$controller = null;
				$this->Acl->startup($controller);
			}
		}
	}

/**
 * Override main() for help message hook
 *
 * @return void
 */
	public function main() {
		$this->out($this->OptionParser->help());
	}

/**
 * Creates an ARO/ACO node
 *
 * @return void
 */
	public function create() {
		extract($this->_dataVars());

		$class = ucfirst($this->args[0]);
		$parent = $this->parseIdentifier($this->args[1]);

		if (!empty($parent) && $parent != '/' && $parent != 'root') {
			$parent = $this->_getNodeId($class, $parent);
		} else {
			$parent = null;
		}

		$data = $this->parseIdentifier($this->args[2]);
		if (is_string($data) && $data != '/') {
			$data = array('alias' => $data);
		} elseif (is_string($data)) {
			$this->error(__d('cake_console', '/ can not be used as an alias!') . __d('cake_console', "	/ is the root, please supply a sub alias"));
		}

		$data['parent_id'] = $parent;
		$this->Acl->{$class}->create();
		if ($this->Acl->{$class}->save($data)) {
			$this->out(__d('cake_console', "<success>New %s</success> '%s' created.", $class, $this->args[2]), 2);
		} else {
			$this->err(__d('cake_console', "There was a problem creating a new %s '%s'.", $class, $this->args[2]));
		}
	}

/**
 * Delete an ARO/ACO node.
 *
 * @return void
 */
	public function delete() {
		extract($this->_dataVars());

		$identifier = $this->parseIdentifier($this->args[1]);
		$nodeId = $this->_getNodeId($class, $identifier);

		if (!$this->Acl->{$class}->delete($nodeId)) {
			$this->error(__d('cake_console', 'Node Not Deleted') . __d('cake_console', 'There was an error deleting the %s. Check that the node exists.', $class) . "\n");
		}
		$this->out(__d('cake_console', '<success>%s deleted.</success>', $class), 2);
	}

/**
 * Set parent for an ARO/ACO node.
 *
 * @return void
 */
	public function setParent() {
		extract($this->_dataVars());
		$target = $this->parseIdentifier($this->args[1]);
		$parent = $this->parseIdentifier($this->args[2]);

		$data = array(
			$class => array(
				'id' => $this->_getNodeId($class, $target),
				'parent_id' => $this->_getNodeId($class, $parent)
			)
		);
		$this->Acl->{$class}->create();
		if (!$this->Acl->{$class}->save($data)) {
			$this->out(__d('cake_console', 'Error in setting new parent. Please make sure the parent node exists, and is not a descendant of the node specified.'), true);
		} else {
			$this->out(__d('cake_console', 'Node parent set to %s', $this->args[2]) . "\n", true);
		}
	}

/**
 * Get path to specified ARO/ACO node.
 *
 * @return void
 */
	public function getPath() {
		extract($this->_dataVars());
		$identifier = $this->parseIdentifier($this->args[1]);

		$id = $this->_getNodeId($class, $identifier);
		$nodes = $this->Acl->{$class}->getPath($id);

		if (empty($nodes)) {
			$this->error(
				__d('cake_console', "Supplied Node '%s' not found", $this->args[1]),
				__d('cake_console', 'No tree returned.')
			);
		}
		$this->out(__d('cake_console', 'Path:'));
		$this->hr();
		for ($i = 0; $i < count($nodes); $i++) {
			$this->_outputNode($class, $nodes[$i], $i);
		}
	}

/**
 * Outputs a single node, Either using the alias or Model.key
 *
 * @param string $class Class name that is being used.
 * @param array $node Array of node information.
 * @param integer $indent indent level.
 * @return void
 */
	protected function _outputNode($class, $node, $indent) {
		$indent = str_repeat('  ', $indent);
		$data = $node[$class];
		if ($data['alias']) {
			$this->out($indent . "[" . $data['id'] . "] " . $data['alias']);
		 } else {
			$this->out($indent . "[" . $data['id'] . "] " . $data['model'] . '.' . $data['foreign_key']);
		}
	}

/**
 * Check permission for a given ARO to a given ACO.
 *
 * @return void
 */
	public function check() {
		extract($this->_getParams());

		if ($this->Acl->check($aro, $aco, $action)) {
			$this->out(__d('cake_console', '%s is <success>allowed</success>.', $aroName), true);
		} else {
			$this->out(__d('cake_console', '%s is <error>not allowed</error>.', $aroName), true);
		}
	}

/**
 * Grant permission for a given ARO to a given ACO.
 *
 * @return void
 */
	public function grant() {
		extract($this->_getParams());

		if ($this->Acl->allow($aro, $aco, $action)) {
			$this->out(__d('cake_console', 'Permission <success>granted</success>.'), true);
		} else {
			$this->out(__d('cake_console', 'Permission was <error>not granted</error>.'), true);
		}
	}

/**
 * Deny access for an ARO to an ACO.
 *
 * @return void
 */
	public function deny() {
		extract($this->_getParams());

		if ($this->Acl->deny($aro, $aco, $action)) {
			$this->out(__d('cake_console', 'Permission denied.'), true);
		} else {
			$this->out(__d('cake_console', 'Permission was not denied.'), true);
		}
	}

/**
 * Set an ARO to inherit permission to an ACO.
 *
 * @return void
 */
	public function inherit() {
		extract($this->_getParams());

		if ($this->Acl->inherit($aro, $aco, $action)) {
			$this->out(__d('cake_console', 'Permission inherited.'), true);
		} else {
			$this->out(__d('cake_console', 'Permission was not inherited.'), true);
		}
	}

/**
 * Show a specific ARO/ACO node.
 *
 * @return void
 */
	public function view() {
		extract($this->_dataVars());

		if (isset($this->args[1])) {
			$identity = $this->parseIdentifier($this->args[1]);

			$topNode = $this->Acl->{$class}->find('first', array(
				'conditions' => array($class . '.id' => $this->_getNodeId($class, $identity))
			));

			$nodes = $this->Acl->{$class}->find('all', array(
				'conditions' => array(
					$class . '.lft >=' => $topNode[$class]['lft'],
					$class . '.lft <=' => $topNode[$class]['rght']
				),
				'order' => $class . '.lft ASC'
			));
		} else {
			$nodes = $this->Acl->{$class}->find('all', array('order' => $class . '.lft ASC'));
		}

		if (empty($nodes)) {
			if (isset($this->args[1])) {
				$this->error(__d('cake_console', '%s not found', $this->args[1]), __d('cake_console', 'No tree returned.'));
			} elseif (isset($this->args[0])) {
				$this->error(__d('cake_console', '%s not found', $this->args[0]), __d('cake_console', 'No tree returned.'));
			}
		}
		$this->out($class . ' tree:');
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
			$last = $n[$class]['rght'];
			$count = count($stack);

			$this->_outputNode($class, $n, $count);
		}
		$this->hr();
	}

/**
 * Initialize ACL database.
 *
 * @return mixed
 */
	public function initdb() {
		return $this->dispatchShell('schema create DbAcl');
	}

/**
 * Get the option parser.
 *
 * @return void
 */
	public function getOptionParser() {
		$parser = parent::getOptionParser();

		$type = array(
			'choices' => array('aro', 'aco'),
			'required' => true,
			'help' => __d('cake_console', 'Type of node to create.')
		);

		$parser->description(__d('cake_console', 'A console tool for managing the DbAcl'))
			->addSubcommand('create', array(
				'help' => __d('cake_console', 'Create a new ACL node'),
				'parser' => array(
					'description' => __d('cake_console', 'Creates a new ACL object <node> under the parent'),
					'arguments' => array(
						'type' => $type,
						'parent' => array(
							'help' => __d('cake_console', 'The node selector for the parent.'),
							'required' => true
						),
						'alias' => array(
							'help' => __d('cake_console', 'The alias to use for the newly created node.'),
							'required' => true
						)
					)
				)
			))->addSubcommand('delete', array(
				'help' => __d('cake_console', 'Deletes the ACL object with the given <node> reference'),
				'parser' => array(
					'description' => __d('cake_console', 'Delete an ACL node.'),
					'arguments' => array(
						'type' => $type,
						'node' => array(
							'help' => __d('cake_console', 'The node identifier to delete.'),
							'required' => true,
						)
					)
				)
			))->addSubcommand('setparent', array(
				'help' => __d('cake_console', 'Moves the ACL node under a new parent.'),
				'parser' => array(
					'description' => __d('cake_console', 'Moves the ACL object specified by <node> beneath <parent>'),
					'arguments' => array(
						'type' => $type,
						'node' => array(
							'help' => __d('cake_console', 'The node to move'),
							'required' => true,
						),
						'parent' => array(
							'help' => __d('cake_console', 'The new parent for <node>.'),
							'required' => true
						)
					)
				)
			))->addSubcommand('getpath', array(
				'help' => __d('cake_console', 'Print out the path to an ACL node.'),
				'parser' => array(
					'description' => array(
						__d('cake_console', "Returns the path to the ACL object specified by <node>."),
						__d('cake_console', "This command is useful in determining the inheritance of permissions for a certain object in the tree.")
					),
					'arguments' => array(
						'type' => $type,
						'node' => array(
							'help' => __d('cake_console', 'The node to get the path of'),
							'required' => true,
						)
					)
				)
			))->addSubcommand('check', array(
				'help' => __d('cake_console', 'Check the permissions between an ACO and ARO.'),
				'parser' => array(
					'description' => array(
						__d('cake_console', 'Use this command to check ACL permissions.')
					),
					'arguments' => array(
						'aro' => array('help' => __d('cake_console', 'ARO to check.'), 'required' => true),
						'aco' => array('help' => __d('cake_console', 'ACO to check.'), 'required' => true),
						'action' => array('help' => __d('cake_console', 'Action to check'), 'default' => 'all')
					)
				)
			))->addSubcommand('grant', array(
				'help' => __d('cake_console', 'Grant an ARO permissions to an ACO.'),
				'parser' => array(
					'description' => array(
						__d('cake_console', 'Use this command to grant ACL permissions. Once executed, the ARO specified (and its children, if any) will have ALLOW access to the specified ACO action (and the ACO\'s children, if any).')
					),
					'arguments' => array(
						'aro' => array('help' => __d('cake_console', 'ARO to grant permission to.'), 'required' => true),
						'aco' => array('help' => __d('cake_console', 'ACO to grant access to.'), 'required' => true),
						'action' => array('help' => __d('cake_console', 'Action to grant'), 'default' => 'all')
					)
				)
			))->addSubcommand('deny', array(
				'help' => __d('cake_console', 'Deny an ARO permissions to an ACO.'),
				'parser' => array(
					'description' => array(
						__d('cake_console', 'Use this command to deny ACL permissions. Once executed, the ARO specified (and its children, if any) will have DENY access to the specified ACO action (and the ACO\'s children, if any).')
					),
					'arguments' => array(
						'aro' => array('help' => __d('cake_console', 'ARO to deny.'), 'required' => true),
						'aco' => array('help' => __d('cake_console', 'ACO to deny.'), 'required' => true),
						'action' => array('help' => __d('cake_console', 'Action to deny'), 'default' => 'all')
					)
				)
			))->addSubcommand('inherit', array(
				'help' => __d('cake_console', 'Inherit an ARO\'s parent permissions.'),
				'parser' => array(
					'description' => array(
						__d('cake_console', "Use this command to force a child ARO object to inherit its permissions settings from its parent.")
					),
					'arguments' => array(
						'aro' => array('help' => __d('cake_console', 'ARO to have permissions inherit.'), 'required' => true),
						'aco' => array('help' => __d('cake_console', 'ACO to inherit permissions on.'), 'required' => true),
						'action' => array('help' => __d('cake_console', 'Action to inherit'), 'default' => 'all')
					)
				)
			))->addSubcommand('view', array(
				'help' => __d('cake_console', 'View a tree or a single node\'s subtree.'),
				'parser' => array(
					'description' => array(
						__d('cake_console', "The view command will return the ARO or ACO tree."),
						__d('cake_console', "The optional node parameter allows you to return"),
						__d('cake_console', "only a portion of the requested tree.")
					),
					'arguments' => array(
						'type' => $type,
						'node' => array('help' => __d('cake_console', 'The optional node to view the subtree of.'))
					)
				)
			))->addSubcommand('initdb', array(
				'help' => __d('cake_console', 'Initialize the DbAcl tables. Uses this command : cake schema create DbAcl')
			))->epilog(
				array(
					'Node and parent arguments can be in one of the following formats:',
					'',
					' - <model>.<id> - The node will be bound to a specific record of the given model.',
					'',
					' - <alias> - The node will be given a string alias (or path, in the case of <parent>)',
					"   i.e. 'John'.  When used with <parent>, this takes the form of an alias path,",
					"   i.e. <group>/<subgroup>/<parent>.",
					'',
					"To add a node at the root level, enter 'root' or '/' as the <parent> parameter."
				)
			);
		return $parser;
	}

/**
 * Checks that given node exists
 *
 * @return boolean Success
 */
	public function nodeExists() {
		if (!isset($this->args[0]) || !isset($this->args[1])) {
			return false;
		}
		extract($this->_dataVars($this->args[0]));
		$key = is_numeric($this->args[1]) ? $secondary_id : 'alias';
		$conditions = array($class . '.' . $key => $this->args[1]);
		$possibility = $this->Acl->{$class}->find('all', compact('conditions'));
		if (empty($possibility)) {
			$this->error(__d('cake_console', '%s not found', $this->args[1]), __d('cake_console', 'No tree returned.'));
		}
		return $possibility;
	}

/**
 * Parse an identifier into Model.foreignKey or an alias.
 * Takes an identifier determines its type and returns the result as used by other methods.
 *
 * @param string $identifier Identifier to parse
 * @return mixed a string for aliases, and an array for model.foreignKey
 */
	public function parseIdentifier($identifier) {
		if (preg_match('/^([\w]+)\.(.*)$/', $identifier, $matches)) {
			return array(
				'model' => $matches[1],
				'foreign_key' => $matches[2],
			);
		}
		return $identifier;
	}

/**
 * Get the node for a given identifier. $identifier can either be a string alias
 * or an array of properties to use in AcoNode::node()
 *
 * @param string $class Class type you want (Aro/Aco)
 * @param mixed $identifier A mixed identifier for finding the node.
 * @return integer Integer of NodeId. Will trigger an error if nothing is found.
 */
	protected function _getNodeId($class, $identifier) {
		$node = $this->Acl->{$class}->node($identifier);
		if (empty($node)) {
			if (is_array($identifier)) {
				$identifier = var_export($identifier, true);
			}
			$this->error(__d('cake_console', 'Could not find node using reference "%s"', $identifier));
		}
		return Set::extract($node, "0.{$class}.id");
	}

/**
 * get params for standard Acl methods
 *
 * @return array aro, aco, action
 */
	protected function _getParams() {
		$aro = is_numeric($this->args[0]) ? intval($this->args[0]) : $this->args[0];
		$aco = is_numeric($this->args[1]) ? intval($this->args[1]) : $this->args[1];
		$aroName = $aro;
		$acoName = $aco;

		if (is_string($aro)) {
			$aro = $this->parseIdentifier($aro);
		}
		if (is_string($aco)) {
			$aco = $this->parseIdentifier($aco);
		}
		$action = null;
		if (isset($this->args[2])) {
			$action = $this->args[2];
			if ($action == '' || $action == 'all') {
				$action = '*';
			}
		}
		return compact('aro', 'aco', 'action', 'aroName', 'acoName');
	}

/**
 * Build data parameters based on node type
 *
 * @param string $type Node type  (ARO/ACO)
 * @return array Variables
 */
	protected function _dataVars($type = null) {
		if ($type == null) {
			$type = $this->args[0];
		}
		$vars = array();
		$class = ucwords($type);
		$vars['secondary_id'] = (strtolower($class) == 'aro') ? 'foreign_key' : 'object_id';
		$vars['data_name'] = $type;
		$vars['table_name'] = $type . 's';
		$vars['class'] = $class;
		return $vars;
	}
}
