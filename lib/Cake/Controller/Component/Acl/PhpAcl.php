<?php
/**
 * PHP configuration based AclInterface implementation
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       Cake.Controller.Component.Acl
 * @since         CakePHP(tm) v 2.1
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */

/**
 * PhpAcl implements an access control system using a plain PHP configuration file.
 * An example file can be found in app/Config/acl.php
 *
 * @package Cake.Controller.Component.Acl
 */
class PhpAcl extends Object implements AclInterface {

/**
 * Constant for deny
 *
 * @var boolean
 */
	const DENY = false;

/**
 * Constant for allow
 *
 * @var boolean
 */
	const ALLOW = true;

/**
 * Options:
 *  - policy: determines behavior of the check method. Deny policy needs explicit allow rules, allow policy needs explicit deny rules
 *  - config: absolute path to config file that contains the acl rules (@see app/Config/acl.php)
 *
 * @var array
 */
	public $options = array();

/**
 * Aro Object
 *
 * @var PhpAro
 */
	public $Aro = null;

/**
 * Aco Object
 *
 * @var PhpAco
 */
	public $Aco = null;

/**
 * Constructor
 *
 * Sets a few default settings up.
 */
	public function __construct() {
		$this->options = array(
			'policy' => self::DENY,
			'config' => APP . 'Config' . DS . 'acl.php',
		);
	}

/**
 * Initialize method
 *
 * @param AclComponent $Component Component instance
 * @return void
 */
	public function initialize(Component $Component) {
		if (!empty($Component->settings['adapter'])) {
			$this->options = array_merge($this->options, $Component->settings['adapter']);
		}

		App::uses('PhpReader', 'Configure');
		$Reader = new PhpReader(dirname($this->options['config']) . DS);
		$config = $Reader->read(basename($this->options['config']));
		$this->build($config);
		$Component->Aco = $this->Aco;
		$Component->Aro = $this->Aro;
	}

/**
 * build and setup internal ACL representation
 *
 * @param array $config configuration array, see docs
 * @return void
 * @throws AclException When required keys are missing.
 */
	public function build(array $config) {
		if (empty($config['roles'])) {
			throw new AclException(__d('cake_dev', '"roles" section not found in configuration.'));
		}

		if (empty($config['rules']['allow']) && empty($config['rules']['deny'])) {
			throw new AclException(__d('cake_dev', 'Neither "allow" nor "deny" rules were provided in configuration.'));
		}

		$rules['allow'] = !empty($config['rules']['allow']) ? $config['rules']['allow'] : array();
		$rules['deny'] = !empty($config['rules']['deny']) ? $config['rules']['deny'] : array();
		$roles = !empty($config['roles']) ? $config['roles'] : array();
		$map = !empty($config['map']) ? $config['map'] : array();
		$alias = !empty($config['alias']) ? $config['alias'] : array();

		$this->Aro = new PhpAro($roles, $map, $alias);
		$this->Aco = new PhpAco($rules);
	}

/**
 * No op method, allow cannot be done with PhpAcl
 *
 * @param string $aro ARO The requesting object identifier.
 * @param string $aco ACO The controlled object identifier.
 * @param string $action Action (defaults to *)
 * @return boolean Success
 */
	public function allow($aro, $aco, $action = "*") {
		return $this->Aco->access($this->Aro->resolve($aro), $aco, $action, 'allow');
	}

/**
 * deny ARO access to ACO
 *
 * @param string $aro ARO The requesting object identifier.
 * @param string $aco ACO The controlled object identifier.
 * @param string $action Action (defaults to *)
 * @return boolean Success
 */
	public function deny($aro, $aco, $action = "*") {
		return $this->Aco->access($this->Aro->resolve($aro), $aco, $action, 'deny');
	}

/**
 * No op method
 *
 * @param string $aro ARO The requesting object identifier.
 * @param string $aco ACO The controlled object identifier.
 * @param string $action Action (defaults to *)
 * @return boolean Success
 */
	public function inherit($aro, $aco, $action = "*") {
		return false;
	}

/**
 * Main ACL check function. Checks to see if the ARO (access request object) has access to the
 * ACO (access control object).
 *
 * @param string $aro ARO
 * @param string $aco ACO
 * @param string $action Action
 * @return boolean true if access is granted, false otherwise
 */
	public function check($aro, $aco, $action = "*") {
		$allow = $this->options['policy'];
		$prioritizedAros = $this->Aro->roles($aro);

		if ($action && $action !== "*") {
			$aco .= '/' . $action;
		}

		$path = $this->Aco->path($aco);

		if (empty($path)) {
			return $allow;
		}

		foreach ($path as $node) {
			foreach ($prioritizedAros as $aros) {
				if (!empty($node['allow'])) {
					$allow = $allow || count(array_intersect($node['allow'], $aros));
				}

				if (!empty($node['deny'])) {
					$allow = $allow && !count(array_intersect($node['deny'], $aros));
				}
			}
		}

		return $allow;
	}

}

/**
 * Access Control Object
 *
 */
class PhpAco {

/**
 * holds internal ACO representation
 *
 * @var array
 */
	protected $_tree = array();

/**
 * map modifiers for ACO paths to their respective PCRE pattern
 *
 * @var array
 */
	public static $modifiers = array(
		'*' => '.*',
	);

/**
 * Constructor
 *
 * @param array $rules Rules array
 */
	public function __construct(array $rules = array()) {
		foreach (array('allow', 'deny') as $type) {
			if (empty($rules[$type])) {
				$rules[$type] = array();
			}
		}

		$this->build($rules['allow'], $rules['deny']);
	}

/**
 * return path to the requested ACO with allow and deny rules attached on each level
 *
 * @param string $aco ACO string
 * @return array
 */
	public function path($aco) {
		$aco = $this->resolve($aco);
		$path = array();
		$level = 0;
		$root = $this->_tree;
		$stack = array(array($root, 0));

		while (!empty($stack)) {
			list($root, $level) = array_pop($stack);

			if (empty($path[$level])) {
				$path[$level] = array();
			}

			foreach ($root as $node => $elements) {
				$pattern = '/^' . str_replace(array_keys(self::$modifiers), array_values(self::$modifiers), $node) . '$/';

				if ($node == $aco[$level] || preg_match($pattern, $aco[$level])) {
					// merge allow/denies with $path of current level
					foreach (array('allow', 'deny') as $policy) {
						if (!empty($elements[$policy])) {
							if (empty($path[$level][$policy])) {
								$path[$level][$policy] = array();
							}
							$path[$level][$policy] = array_merge($path[$level][$policy], $elements[$policy]);
						}
					}

					// traverse
					if (!empty($elements['children']) && isset($aco[$level + 1])) {
						array_push($stack, array($elements['children'], $level + 1));
					}
				}
			}
		}

		return $path;
	}

/**
 * allow/deny ARO access to ARO
 *
 * @param string $aro ARO string
 * @param string $aco ACO string
 * @param string $action Action string
 * @param string $type access type
 * @return void
 */
	public function access($aro, $aco, $action, $type = 'deny') {
		$aco = $this->resolve($aco);
		$depth = count($aco);
		$root = $this->_tree;
		$tree = &$root;

		foreach ($aco as $i => $node) {
			if (!isset($tree[$node])) {
				$tree[$node] = array(
					'children' => array(),
				);
			}

			if ($i < $depth - 1) {
				$tree = &$tree[$node]['children'];
			} else {
				if (empty($tree[$node][$type])) {
					$tree[$node][$type] = array();
				}

				$tree[$node][$type] = array_merge(is_array($aro) ? $aro : array($aro), $tree[$node][$type]);
			}
		}

		$this->_tree = &$root;
	}

/**
 * resolve given ACO string to a path
 *
 * @param string $aco ACO string
 * @return array path
 */
	public function resolve($aco) {
		if (is_array($aco)) {
			return array_map('strtolower', $aco);
		}

		// strip multiple occurrences of '/'
		$aco = preg_replace('#/+#', '/', $aco);
		// make case insensitive
		$aco = ltrim(strtolower($aco), '/');
		return array_filter(array_map('trim', explode('/', $aco)));
	}

/**
 * build a tree representation from the given allow/deny informations for ACO paths
 *
 * @param array $allow ACO allow rules
 * @param array $deny ACO deny rules
 * @return void
 */
	public function build(array $allow, array $deny = array()) {
		$this->_tree = array();

		foreach ($allow as $dotPath => $aros) {
			if (is_string($aros)) {
				$aros = array_map('trim', explode(',', $aros));
			}

			$this->access($aros, $dotPath, null, 'allow');
		}

		foreach ($deny as $dotPath => $aros) {
			if (is_string($aros)) {
				$aros = array_map('trim', explode(',', $aros));
			}

			$this->access($aros, $dotPath, null, 'deny');
		}
	}

}

/**
 * Access Request Object
 *
 */
class PhpAro {

/**
 * role to resolve to when a provided ARO is not listed in
 * the internal tree
 *
 * @var string
 */
	const DEFAULT_ROLE = 'Role/default';

/**
 * map external identifiers. E.g. if
 *
 * array('User' => array('username' => 'jeff', 'role' => 'editor'))
 *
 * is passed as an ARO to one of the methods of AclComponent, PhpAcl
 * will check if it can be resolved to an User or a Role defined in the
 * configuration file.
 *
 * @var array
 * @see app/Config/acl.php
 */
	public $map = array(
		'User' => 'User/username',
		'Role' => 'User/role',
	);

/**
 * aliases to map
 *
 * @var array
 */
	public $aliases = array();

/**
 * internal ARO representation
 *
 * @var array
 */
	protected $_tree = array();

/**
 * Constructor
 *
 * @param array $aro
 * @param array $map
 * @param array $aliases
 */
	public function __construct(array $aro = array(), array $map = array(), array $aliases = array()) {
		if (!empty($map)) {
			$this->map = $map;
		}

		$this->aliases = $aliases;
		$this->build($aro);
	}

/**
 * From the perspective of the given ARO, walk down the tree and
 * collect all inherited AROs levelwise such that AROs from different
 * branches with equal distance to the requested ARO will be collected at the same
 * index. The resulting array will contain a prioritized list of (list of) roles ordered from
 * the most distant AROs to the requested one itself.
 *
 * @param string|array $aro An ARO identifier
 * @return array prioritized AROs
 */
	public function roles($aro) {
		$aros = array();
		$aro = $this->resolve($aro);
		$stack = array(array($aro, 0));

		while (!empty($stack)) {
			list($element, $depth) = array_pop($stack);
			$aros[$depth][] = $element;

			foreach ($this->_tree as $node => $children) {
				if (in_array($element, $children)) {
					array_push($stack, array($node, $depth + 1));
				}
			}
		}

		return array_reverse($aros);
	}

/**
 * resolve an ARO identifier to an internal ARO string using
 * the internal mapping information.
 *
 * @param string|array $aro ARO identifier (User.jeff, array('User' => ...), etc)
 * @return string internal aro string (e.g. User/jeff, Role/default)
 */
	public function resolve($aro) {
		foreach ($this->map as $aroGroup => $map) {
			list ($model, $field) = explode('/', $map, 2);
			$mapped = '';

			if (is_array($aro)) {
				if (isset($aro['model']) && isset($aro['foreign_key']) && $aro['model'] === $aroGroup) {
					$mapped = $aroGroup . '/' . $aro['foreign_key'];
				} elseif (isset($aro[$model][$field])) {
					$mapped = $aroGroup . '/' . $aro[$model][$field];
				} elseif (isset($aro[$field])) {
					$mapped = $aroGroup . '/' . $aro[$field];
				}
			} elseif (is_string($aro)) {
				$aro = ltrim($aro, '/');

				if (strpos($aro, '/') === false) {
					$mapped = $aroGroup . '/' . $aro;
				} else {
					list($aroModel, $aroValue) = explode('/', $aro, 2);

					$aroModel = Inflector::camelize($aroModel);

					if ($aroModel === $model || $aroModel === $aroGroup) {
						$mapped = $aroGroup . '/' . $aroValue;
					}
				}
			}

			if (isset($this->_tree[$mapped])) {
				return $mapped;
			}

			// is there a matching alias defined (e.g. Role/1 => Role/admin)?
			if (!empty($this->aliases[$mapped])) {
				return $this->aliases[$mapped];
			}
		}
		return self::DEFAULT_ROLE;
	}

/**
 * adds a new ARO to the tree
 *
 * @param array $aro one or more ARO records
 * @return void
 */
	public function addRole(array $aro) {
		foreach ($aro as $role => $inheritedRoles) {
			if (!isset($this->_tree[$role])) {
				$this->_tree[$role] = array();
			}

			if (!empty($inheritedRoles)) {
				if (is_string($inheritedRoles)) {
					$inheritedRoles = array_map('trim', explode(',', $inheritedRoles));
				}

				foreach ($inheritedRoles as $dependency) {
					// detect cycles
					$roles = $this->roles($dependency);

					if (in_array($role, Hash::flatten($roles))) {
						$path = '';

						foreach ($roles as $roleDependencies) {
							$path .= implode('|', (array)$roleDependencies) . ' -> ';
						}

						trigger_error(__d('cake_dev', 'cycle detected when inheriting %s from %s. Path: %s', $role, $dependency, $path . $role));
						continue;
					}

					if (!isset($this->_tree[$dependency])) {
						$this->_tree[$dependency] = array();
					}

					$this->_tree[$dependency][] = $role;
				}
			}
		}
	}

/**
 * adds one or more aliases to the internal map. Overwrites existing entries.
 *
 * @param array $alias alias from => to (e.g. Role/13 -> Role/editor)
 * @return void
 */
	public function addAlias(array $alias) {
		$this->aliases = array_merge($this->aliases, $alias);
	}

/**
 * build an ARO tree structure for internal processing
 *
 * @param array $aros array of AROs as key and their inherited AROs as values
 * @return void
 */
	public function build(array $aros) {
		$this->_tree = array();
		$this->addRole($aros);
	}

}
