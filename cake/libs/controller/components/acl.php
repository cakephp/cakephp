<?php
/**
 * Access Control List factory class.
 *
 * Permissions system.
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
 * @subpackage    cake.cake.libs.controller.components
 * @since         CakePHP(tm) v 0.10.0.1076
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

/**
 * Access Control List factory class.
 *
 * Looks for ACL implementation class in core config, and returns an instance of that class.
 *
 * @package       cake
 * @subpackage    cake.cake.libs.controller.components
 */
class AclComponent extends Object {

/**
 * Instance of an ACL class
 *
 * @var object
 * @access protected
 */
	var $_Instance = null;

/**
 * Constructor. Will return an instance of the correct ACL class.
 *
 */
	function __construct() {
		$name = Inflector::camelize(strtolower(Configure::read('Acl.classname')));
		if (!class_exists($name)) {
			if (App::import('Component', $name)) {
				list($plugin, $name) = pluginSplit($name);
				$name .= 'Component';
			} else {
				trigger_error(sprintf(__('Could not find %s.', true), $name), E_USER_WARNING);
			}
		}
		$this->_Instance =& new $name();
		$this->_Instance->initialize($this);
	}

/**
 * Startup is not used
 *
 * @param object $controller Controller using this component
 * @return boolean Proceed with component usage (true), or fail (false)
 * @access public
 */
	function startup(&$controller) {
		return true;
	}

/**
 * Empty class defintion, to be overridden in subclasses.
 *
 * @access protected
 */
	function _initACL() {
	}

/**
 * Pass-thru function for ACL check instance.
 *
 * @param string $aro ARO
 * @param string $aco ACO
 * @param string $action Action (defaults to *)
 * @return boolean Success
 * @access public
 */
	function check($aro, $aco, $action = "*") {
		return $this->_Instance->check($aro, $aco, $action);
	}

/**
 * Pass-thru function for ACL allow instance.
 *
 * @param string $aro ARO
 * @param string $aco ACO
 * @param string $action Action (defaults to *)
 * @return boolean Success
 * @access public
 */
	function allow($aro, $aco, $action = "*") {
		return $this->_Instance->allow($aro, $aco, $action);
	}

/**
 * Pass-thru function for ACL deny instance.
 *
 * @param string $aro ARO
 * @param string $aco ACO
 * @param string $action Action (defaults to *)
 * @return boolean Success
 * @access public
 */
	function deny($aro, $aco, $action = "*") {
		return $this->_Instance->deny($aro, $aco, $action);
	}

/**
 * Pass-thru function for ACL inherit instance.
 *
 * @param string $aro ARO
 * @param string $aco ACO
 * @param string $action Action (defaults to *)
 * @return boolean Success
 * @access public
 */
	function inherit($aro, $aco, $action = "*") {
		return $this->_Instance->inherit($aro, $aco, $action);
	}

/**
 * Pass-thru function for ACL grant instance.
 *
 * @param string $aro ARO
 * @param string $aco ACO
 * @param string $action Action (defaults to *)
 * @return boolean Success
 * @access public
 */
	function grant($aro, $aco, $action = "*") {
		return $this->_Instance->grant($aro, $aco, $action);
	}

/**
 * Pass-thru function for ACL grant instance.
 *
 * @param string $aro ARO
 * @param string $aco ACO
 * @param string $action Action (defaults to *)
 * @return boolean Success
 * @access public
 */
	function revoke($aro, $aco, $action = "*") {
		return $this->_Instance->revoke($aro, $aco, $action);
	}
}

/**
 * Access Control List abstract class. Not to be instantiated.
 * Subclasses of this class are used by AclComponent to perform ACL checks in Cake.
 *
 * @package       cake
 * @subpackage    cake.cake.libs.controller.components
 * @abstract
 */
class AclBase extends Object {

/**
 * This class should never be instantiated, just subclassed.
 *
 */
	function __construct() {
		if (strcasecmp(get_class($this), "AclBase") == 0 || !is_subclass_of($this, "AclBase")) {
			trigger_error(__("[acl_base] The AclBase class constructor has been called, or the class was instantiated. This class must remain abstract. Please refer to the Cake docs for ACL configuration.", true), E_USER_ERROR);
			return NULL;
		}
	}

/**
 * Empty method to be overridden in subclasses
 *
 * @param string $aro ARO
 * @param string $aco ACO
 * @param string $action Action (defaults to *)
 * @access public
 */
	function check($aro, $aco, $action = "*") {
	}

/**
 * Empty method to be overridden in subclasses
 *
 * @param object $component Component
 * @access public
 */
	function initialize(&$component) {
	}
}

/**
 * In this file you can extend the AclBase.
 *
 * @package       cake
 * @subpackage    cake.cake.libs.model
 */
class DbAcl extends AclBase {

/**
 * Constructor
 *
 */
	function __construct() {
		parent::__construct();
		if (!class_exists('AclNode')) {
			require LIBS . 'model' . DS . 'db_acl.php';
		}
		$this->Aro =& ClassRegistry::init(array('class' => 'Aro', 'alias' => 'Aro'));
		$this->Aco =& ClassRegistry::init(array('class' => 'Aco', 'alias' => 'Aco'));
	}

/**
 * Enter description here...
 *
 * @param object $component
 * @return void
 * @access public
 */
	function initialize(&$component) {
		$component->Aro =& $this->Aro;
		$component->Aco =& $this->Aco;
	}

/**
 * Checks if the given $aro has access to action $action in $aco
 *
 * @param string $aro ARO
 * @param string $aco ACO
 * @param string $action Action (defaults to *)
 * @return boolean Success (true if ARO has access to action in ACO, false otherwise)
 * @access public
 */
	function check($aro, $aco, $action = "*") {
		if ($aro == null || $aco == null) {
			return false;
		}

		$permKeys = $this->_getAcoKeys($this->Aro->Permission->schema());
		$aroPath = $this->Aro->node($aro);
		$acoPath = $this->Aco->node($aco);

		if (empty($aroPath) || empty($acoPath)) {
			trigger_error(__("DbAcl::check() - Failed ARO/ACO node lookup in permissions check.  Node references:\nAro: ", true) . print_r($aro, true) . "\nAco: " . print_r($aco, true), E_USER_WARNING);
			return false;
		}

		if ($acoPath == null || $acoPath == array()) {
			trigger_error(__("DbAcl::check() - Failed ACO node lookup in permissions check.  Node references:\nAro: ", true) . print_r($aro, true) . "\nAco: " . print_r($aco, true), E_USER_WARNING);
			return false;
		}

		$aroNode = $aroPath[0];
		$acoNode = $acoPath[0];

		if ($action != '*' && !in_array('_' . $action, $permKeys)) {
			trigger_error(sprintf(__("ACO permissions key %s does not exist in DbAcl::check()", true), $action), E_USER_NOTICE);
			return false;
		}

		$inherited = array();
		$acoIDs = Set::extract($acoPath, '{n}.' . $this->Aco->alias . '.id');

		$count = count($aroPath);
		for ($i = 0 ; $i < $count; $i++) {
			$permAlias = $this->Aro->Permission->alias;

			$perms = $this->Aro->Permission->find('all', array(
				'conditions' => array(
					"{$permAlias}.aro_id" => $aroPath[$i][$this->Aro->alias]['id'],
					"{$permAlias}.aco_id" => $acoIDs
				),
				'order' => array($this->Aco->alias . '.lft' => 'desc'),
				'recursive' => 0
			));

			if (empty($perms)) {
				continue;
			} else {
				$perms = Set::extract($perms, '{n}.' . $this->Aro->Permission->alias);
				foreach ($perms as $perm) {
					if ($action == '*') {

						foreach ($permKeys as $key) {
							if (!empty($perm)) {
								if ($perm[$key] == -1) {
									return false;
								} elseif ($perm[$key] == 1) {
									$inherited[$key] = 1;
								}
							}
						}

						if (count($inherited) === count($permKeys)) {
							return true;
						}
					} else {
						switch ($perm['_' . $action]) {
							case -1:
								return false;
							case 0:
								continue;
							break;
							case 1:
								return true;
							break;
						}
					}
				}
			}
		}
		return false;
	}

/**
 * Allow $aro to have access to action $actions in $aco
 *
 * @param string $aro ARO
 * @param string $aco ACO
 * @param string $actions Action (defaults to *)
 * @param integer $value Value to indicate access type (1 to give access, -1 to deny, 0 to inherit)
 * @return boolean Success
 * @access public
 */
	function allow($aro, $aco, $actions = "*", $value = 1) {
		$perms = $this->getAclLink($aro, $aco);
		$permKeys = $this->_getAcoKeys($this->Aro->Permission->schema());
		$save = array();

		if ($perms == false) {
			trigger_error(__('DbAcl::allow() - Invalid node', true), E_USER_WARNING);
			return false;
		}
		if (isset($perms[0])) {
			$save = $perms[0][$this->Aro->Permission->alias];
		}

		if ($actions == "*") {
			$permKeys = $this->_getAcoKeys($this->Aro->Permission->schema());
			$save = array_combine($permKeys, array_pad(array(), count($permKeys), $value));
		} else {
			if (!is_array($actions)) {
				$actions = array('_' . $actions);
			}
			if (is_array($actions)) {
				foreach ($actions as $action) {
					if ($action{0} != '_') {
						$action = '_' . $action;
					}
					if (in_array($action, $permKeys)) {
						$save[$action] = $value;
					}
				}
			}
		}
		list($save['aro_id'], $save['aco_id']) = array($perms['aro'], $perms['aco']);

		if ($perms['link'] != null && !empty($perms['link'])) {
			$save['id'] = $perms['link'][0][$this->Aro->Permission->alias]['id'];
		} else {
			unset($save['id']);
			$this->Aro->Permission->id = null;
		}
		return ($this->Aro->Permission->save($save) !== false);
	}

/**
 * Deny access for $aro to action $action in $aco
 *
 * @param string $aro ARO
 * @param string $aco ACO
 * @param string $actions Action (defaults to *)
 * @return boolean Success
 * @access public
 */
	function deny($aro, $aco, $action = "*") {
		return $this->allow($aro, $aco, $action, -1);
	}

/**
 * Let access for $aro to action $action in $aco be inherited
 *
 * @param string $aro ARO
 * @param string $aco ACO
 * @param string $actions Action (defaults to *)
 * @return boolean Success
 * @access public
 */
	function inherit($aro, $aco, $action = "*") {
		return $this->allow($aro, $aco, $action, 0);
	}

/**
 * Allow $aro to have access to action $actions in $aco
 *
 * @param string $aro ARO
 * @param string $aco ACO
 * @param string $actions Action (defaults to *)
 * @return boolean Success
 * @see allow()
 * @access public
 */
	function grant($aro, $aco, $action = "*") {
		return $this->allow($aro, $aco, $action);
	}

/**
 * Deny access for $aro to action $action in $aco
 *
 * @param string $aro ARO
 * @param string $aco ACO
 * @param string $actions Action (defaults to *)
 * @return boolean Success
 * @see deny()
 * @access public
 */
	function revoke($aro, $aco, $action = "*") {
		return $this->deny($aro, $aco, $action);
	}

/**
 * Get an array of access-control links between the given Aro and Aco
 *
 * @param string $aro ARO
 * @param string $aco ACO
 * @return array Indexed array with: 'aro', 'aco' and 'link'
 * @access public
 */
	function getAclLink($aro, $aco) {
		$obj = array();
		$obj['Aro'] = $this->Aro->node($aro);
		$obj['Aco'] = $this->Aco->node($aco);

		if (empty($obj['Aro']) || empty($obj['Aco'])) {
			return false;
		}

		return array(
			'aro' => Set::extract($obj, 'Aro.0.'.$this->Aro->alias.'.id'),
			'aco'  => Set::extract($obj, 'Aco.0.'.$this->Aco->alias.'.id'),
			'link' => $this->Aro->Permission->find('all', array('conditions' => array(
				$this->Aro->Permission->alias . '.aro_id' => Set::extract($obj, 'Aro.0.'.$this->Aro->alias.'.id'),
				$this->Aro->Permission->alias . '.aco_id' => Set::extract($obj, 'Aco.0.'.$this->Aco->alias.'.id')
			)))
		);
	}

/**
 * Get the keys used in an ACO
 *
 * @param array $keys Permission model info
 * @return array ACO keys
 * @access protected
 */
	function _getAcoKeys($keys) {
		$newKeys = array();
		$keys = array_keys($keys);
		foreach ($keys as $key) {
			if (!in_array($key, array('id', 'aro_id', 'aco_id'))) {
				$newKeys[] = $key;
			}
		}
		return $newKeys;
	}
}

/**
 * In this file you can extend the AclBase.
 *
 * @package       cake
 * @subpackage    cake.cake.libs.model.iniacl
 */
class IniAcl extends AclBase {

/**
 * Array with configuration, parsed from ini file
 *
 * @var array
 * @access public
 */
	var $config = null;

/**
 * The constructor must be overridden, as AclBase is abstract.
 *
 */
	function __construct() {
	}

/**
 * Main ACL check function. Checks to see if the ARO (access request object) has access to the ACO (access control object).
 * Looks at the acl.ini.php file for permissions (see instructions in /config/acl.ini.php).
 *
 * @param string $aro ARO
 * @param string $aco ACO
 * @param string $aco_action Action
 * @return boolean Success
 * @access public
 */
	function check($aro, $aco, $aco_action = null) {
		if ($this->config == null) {
			$this->config = $this->readConfigFile(CONFIGS . 'acl.ini.php');
		}
		$aclConfig = $this->config;

		if (isset($aclConfig[$aro]['deny'])) {
			$userDenies = $this->arrayTrim(explode(",", $aclConfig[$aro]['deny']));

			if (array_search($aco, $userDenies)) {
				return false;
			}
		}

		if (isset($aclConfig[$aro]['allow'])) {
			$userAllows = $this->arrayTrim(explode(",", $aclConfig[$aro]['allow']));

			if (array_search($aco, $userAllows)) {
				return true;
			}
		}

		if (isset($aclConfig[$aro]['groups'])) {
			$userGroups = $this->arrayTrim(explode(",", $aclConfig[$aro]['groups']));

			foreach ($userGroups as $group) {
				if (array_key_exists($group, $aclConfig)) {
					if (isset($aclConfig[$group]['deny'])) {
						$groupDenies=$this->arrayTrim(explode(",", $aclConfig[$group]['deny']));

						if (array_search($aco, $groupDenies)) {
							return false;
						}
					}

					if (isset($aclConfig[$group]['allow'])) {
						$groupAllows = $this->arrayTrim(explode(",", $aclConfig[$group]['allow']));

						if (array_search($aco, $groupAllows)) {
							return true;
						}
					}
				}
			}
		}
		return false;
	}

/**
 * Parses an INI file and returns an array that reflects the INI file's section structure. Double-quote friendly.
 *
 * @param string $fileName File
 * @return array INI section structure
 * @access public
 */
	function readConfigFile($fileName) {
		$fileLineArray = file($fileName);

		foreach ($fileLineArray as $fileLine) {
			$dataLine = trim($fileLine);
			$firstChar = substr($dataLine, 0, 1);

			if ($firstChar != ';' && $dataLine != '') {
				if ($firstChar == '[' && substr($dataLine, -1, 1) == ']') {
					$sectionName = preg_replace('/[\[\]]/', '', $dataLine);
				} else {
					$delimiter = strpos($dataLine, '=');

					if ($delimiter > 0) {
						$key = strtolower(trim(substr($dataLine, 0, $delimiter)));
						$value = trim(substr($dataLine, $delimiter + 1));

						if (substr($value, 0, 1) == '"' && substr($value, -1) == '"') {
							$value = substr($value, 1, -1);
						}

						$iniSetting[$sectionName][$key]=stripcslashes($value);
					} else {
						if (!isset($sectionName)) {
							$sectionName = '';
						}

						$iniSetting[$sectionName][strtolower(trim($dataLine))]='';
					}
				}
			}
		}

		return $iniSetting;
	}

/**
 * Removes trailing spaces on all array elements (to prepare for searching)
 *
 * @param array $array Array to trim
 * @return array Trimmed array
 * @access public
 */
	function arrayTrim($array) {
		foreach ($array as $key => $value) {
			$array[$key] = trim($value);
		}
		array_unshift($array, "");
		return $array;
	}
}
?>