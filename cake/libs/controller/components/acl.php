<?php
/* SVN FILE: $Id$ */
/**
 * Access Control List factory class.
 *
 * Permissions system.
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
 * @subpackage		cake.cake.libs.controller.components
 * @since			CakePHP(tm) v 0.10.0.1076
 * @version			$Revision$
 * @modifiedby		$LastChangedBy$
 * @lastmodified	$Date$
 * @license			http://www.opensource.org/licenses/mit-license.php The MIT License
 */
/**
 * Access Control List factory class.
 *
 * Looks for ACL implementation class in core config, and returns an instance of that class.
 *
 * @package		cake
 * @subpackage	cake.cake.libs.controller.components
 */
class AclComponent extends Object {

	var $_instance = null;

	var $name = ACL_CLASSNAME;
/**
 * Constructor. Will return an instance of the correct ACL class.
 *
 */
	function startup(&$controller) {
		$this->getACL();
	}
/**
 * Static function used to gain an instance of the correct ACL class.
 *
 * @return MyACL
 */
	function &getACL() {
		if ($this->_instance == null) {
			$name = $this->name;
			if (!class_exists($name)) {
				if (loadComponent($name)) {
					if (strpos($name, '.') !== false) {
						list($plugin, $name) = explode('.', $name);
					}
					$name .= 'Component';
				} else {
					trigger_error(sprintf(__('Could not find %s.', true), $name), E_USER_WARNING);
				}
			}
			$this->_instance =& new $name();
			$this->_instance->initialize($this);
		}
		return $this->_instance;
	}
/**
 * Empty class defintion, to be overridden in subclasses.
 *
 */
	function _initACL() {
	}
/**
 * Pass-thru function for ACL check instance.
 *
 * @param string $aro
 * @param string $aco
 * @param string $action : default = *
 * @return boolean
 */
	function check($aro, $aco, $action = "*") {
		return $this->_instance->check($aro, $aco, $action);
	}
/**
 * Pass-thru function for ACL allow instance.
 *
 * @param string $aro
 * @param string $aco
 * @param string $action : default = *
 * @return boolean
 */
	function allow($aro, $aco, $action = "*") {
		return $this->_instance->allow($aro, $aco, $action);
	}
/**
 * Pass-thru function for ACL deny instance.
 *
 * @param string $aro
 * @param string $aco
 * @param string $action : default = *
 * @return boolean
 */
	function deny($aro, $aco, $action = "*") {
		return $this->_instance->deny($aro, $aco, $action);
	}
/**
 * Pass-thru function for ACL inherit instance.
 *
 * @return boolean
 */
	function inherit($aro, $aco, $action = "*") {
		return $this->_instance->inherit($aro, $aco, $action);
	}
/**
 * Pass-thru function for ACL grant instance.
 *
 * @param string $aro
 * @param string $aco
 * @param string $action : default = *
 * @return boolean
 */
	function grant($aro, $aco, $action = "*") {
		return $this->_instance->grant($aro, $aco, $action);
	}
/**
 * Pass-thru function for ACL grant instance.
 *
 * @param string $aro
 * @param string $aco
 * @param string $action : default = *
 * @return boolean
 */
	function revoke($aro, $aco, $action = "*") {
		return $this->_instance->revoke($aro, $aco, $action);
	}
/**
 * Sets the current ARO instance to object from getAro
 *
 * @param string $id
 * @return boolean
 */
	function setAro($id) {
		return $this->Aro = $this->_instance->getAro($id);
	}
/**
* Sets the current ACO instance to object from getAco
 *
 * @param string $id
 * @return boolean
 */
	function setAco($id) {
		return $this->Aco = $this->_instance->getAco($id);
	}
/**
 * Pass-thru function for ACL getAro instance
 * that gets an ARO object from the given id or alias
 *
 * @param string $id
 * @return Aro
 */
	function getAro($id) {
		return $this->_instance->getAro($id);
	}
/**
 * Pass-thru function for ACL getAco instance.
 * that gets an ACO object from the given id or alias
 *
 * @param string $id
 * @return Aco
 */
	function getAco($id) {
		return $this->_instance->getAco($id);
	}
}
/**
 * Access Control List abstract class. Not to be instantiated.
 * Subclasses of this class are used by AclComponent to perform ACL checks in Cake.
 *
 * @package 	cake
 * @subpackage	cake.cake.libs.controller.components
 * @abstract
 */
class AclBase extends Object {
/**
 * This class should never be instantiated, just subclassed.
 *
 * @return AclBase
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
 * @param unknown_type $aro
 * @param unknown_type $aco
 * @param string $action
 */
	function check($aro, $aco, $action = "*") {
	}
/**
 * Empty method to be overridden in subclasses
 *
 * @param unknown_type $component
 */
	function initialize(&$component) {
	}
}
/**
 * In this file you can extend the AclBase.
 *
 * @package		cake
 * @subpackage	cake.cake.libs.model
 */
class DB_ACL extends AclBase {
/**
 * Enter description here...
 *
 */
	function __construct() {
		uses('model' . DS . 'db_acl');
		parent::__construct();
		$this->Aro =& new Aro();
		$this->Aco =& new Aco();
	}
/**
 * Enter description here...
 *
 * @param unknown_type $aro
 * @param unknown_type $aco
 * @param unknown_type $action
 * @return unknown
 */
	function initialize(&$component) {
		$component->Aro =& $this->Aro;
		$component->Aco =& $this->Aco;
	}
/**
 * Enter description here...
 *
 * @param unknown_type $aro
 * @param unknown_type $aco
 * @param unknown_type $action
 * @return unknown
 */
	function check($aro, $aco, $action = "*") {

		if ($aro == null || $aco == null) {
			return false;
		}

		$permKeys = $this->_getAcoKeys($this->Aro->Permission->loadInfo());
		$aroPath = $this->Aro->node($aro);
		$acoPath = new Set($this->Aco->node($aco));

		if (empty($aroPath) ||  empty($acoPath)) {
			trigger_error("DB_ACL::check() - Failed ARO/ACO node lookup in permissions check.  Node references:\nAro: " . print_r($aro, true) . "\nAco: " . print_r($aco, true), E_USER_WARNING);
			return false;
		}
		if ($acoPath->get() == null || $acoPath->get() == array()) {
			trigger_error("DB_ACL::check() - Failed ACO node lookup in permissions check.  Node references:\nAro: " . print_r($aro, true) . "\nAco: " . print_r($aco, true), E_USER_WARNING);
			return false;
		}

		$aroNode = $aroPath[0];
		$acoNode = $acoPath->get();
		$acoNode = $acoNode[0];

		if ($action != '*' && !in_array('_' . $action, $permKeys)) {
			trigger_error(sprintf(__("ACO permissions key %s does not exist in DB_ACL::check()", true), $action), E_USER_NOTICE);
			return false;
		}

		for ($i = count($aroPath) - 1; $i >= 0; $i--) {
			$perms = $this->Aro->Permission->findAll(
				array(
					$this->Aro->Permission->name . '.aro_id' => $aroPath[$i][$this->Aro->name]['id'],
					$this->Aro->Permission->name . '.aco_id' => $acoPath->extract('{n}.' . $this->Aco->name . '.id')
				),
				null, array($this->Aco->name .'.lft' => 'desc'), null, null, 0
			);

			if (empty($perms)) {
				continue;
			} else {
				foreach (Set::extract($perms, '{n}.' . $this->Aro->Permission->name) as $perm) {
					if ($action == '*') {
						// ARO must be cleared for ALL ACO actions
						foreach ($permKeys as $key) {
							if (!empty($perm)) {
								if ($perm[$key] != 1) {
									return false;
								}
							}
						}
						return true;
					} else {
						switch($perm['_' . $action]) {
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
 * Allow
 *
 * @return boolean
 */
	function allow($aro, $aco, $actions = "*", $value = 1) {
		$perms = $this->getAclLink($aro, $aco);
		$permKeys = $this->_getAcoKeys($this->Aro->Permission->loadInfo());
		$save = array();

		if ($perms == false) {
			trigger_error(__('DB_ACL::allow() - Invalid node', true), E_USER_WARNING);
			return false;
		}

		if (isset($perms[0])) {
			$save = $perms[0][$this->Aro->Permission->name];
		}

		if ($actions == "*") {
			$permKeys = $this->_getAcoKeys($this->Aro->Permission->loadInfo());

			foreach ($permKeys as $key) {
				$save[$key] = $value;
			}
		} else {
			if (!is_array($actions)) {
				$actions = array('_' . $actions);
				$actions = am($permKeys, $actions);
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

		$save['aro_id'] = $perms['aro'];
		$save['aco_id'] = $perms['aco'];

		if ($perms['link'] != null && count($perms['link']) > 0) {
			$save['id'] = $perms['link'][0][$this->Aro->Permission->name]['id'];
		}
		$this->Aro->Permission->create($save);
		return $this->Aro->Permission->save();
	}
/**
 * Deny
 *
 * @return boolean
 */
	function deny($aro, $aco, $action = "*") {
		return $this->allow($aro, $aco, $action, -1);
	}
/**
 * Inherit
 *
 * @return boolean
 */
	function inherit($aro, $aco, $action = "*") {
		return $this->allow($aro, $aco, $action, 0);
	}
/**
 * Allow alias
 *
 * @return boolean
 */
	function grant($aro, $aco, $action = "*") {
		return $this->allow($aro, $aco, $action);
	}
/**
 * Deny alias
 *
 * @return boolean
 */
	function revoke($aro, $aco, $action = "*") {
		return $this->deny($aro, $aco, $action);
	}
/**
 * Private method
 *
 */
	function &__getObject($id = null, $object) {
		if ($id == null) {
			trigger_error(__('Null id provided in DB_ACL::get', true) . $object, E_USER_WARNING);
			return null;
		}

		if (is_numeric($id)) {
			$conditions = array("{$object}.foreign_key" => $id);
		} else {
			$conditions = array("{$object}.alias" => $id);
		}

		$tmp = $this->{$object}->find($conditions);
		$this->{$object}->id = $tmp[$object]['id'];
		return $this->{$object};
	}
/**
 * Get an array of access-control links between the given Aro and Aco
 *
 * @param mixed $aro
 * @param mixed $aco
 * @return array
 */
	function getAclLink($aro, $aco) {
		$obj = array();
		$obj['Aro'] = $this->Aro->node($aro);
		$obj['Aco'] = $this->Aco->node($aco);

		if (empty($obj['Aro']) || empty($obj['Aco'])) {
			return false;
		}

		return array(
			'aro' => Set::extract($obj, 'Aro.0.'.$this->Aro->name.'.id'),
			'aco'  => Set::extract($obj, 'Aco.0.'.$this->Aco->name.'.id'),
			'link' => $this->Aro->Permission->findAll(array(
				$this->Aro->Permission->name . '.aro_id' => Set::extract($obj, 'Aro.0.'.$this->Aro->name.'.id'),
				$this->Aro->Permission->name . '.aco_id' => Set::extract($obj, 'Aco.0.'.$this->Aco->name.'.id')
			))
		);
	}
/**
 * Enter description here...
 *
 * @param unknown_type $keys
 * @return unknown
 */
	function _getAcoKeys($keys) {
		$newKeys = array();
		$keys = $keys->extract('{n}.name');

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
 * @package		cake
 * @subpackage	cake.cake.libs.model.iniacl
 */
class INI_ACL extends AclBase {
/**
 * Array with configuration, parsed from ini file
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
 * @param string $aro
 * @param string $aco
 * @return boolean
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
 * @param string $fileName
 * @return array
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
 * @param array $array
 * @return array
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