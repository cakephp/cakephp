<?php
/* SVN FILE: $Id$ */

/**
 * This is core configuration file.
 *
 * Use it to configure core behaviour ofCake.
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
 * @subpackage		cake.cake.libs.controller.componenets.dbacl
 * @since			CakePHP v 0.2.9
 * @version			$Revision$
 * @modifiedby		$LastChangedBy$
 * @lastmodified	$Date$
 * @license			http://www.opensource.org/licenses/mit-license.php The MIT License
 */

uses('controller' . DS . 'components' . DS . 'acl_base');
uses('controller' . DS . 'components' . DS . 'dbacl' . DS . 'models' . DS . 'aclnode');
uses('controller' . DS . 'components' . DS . 'dbacl' . DS . 'models' . DS . 'aco');
uses('controller' . DS . 'components' . DS . 'dbacl' . DS . 'models' . DS . 'acoaction');
uses('controller' . DS . 'components' . DS . 'dbacl' . DS . 'models' . DS . 'aro');
uses('controller' . DS . 'components' . DS . 'dbacl' . DS . 'models' . DS . 'aros_aco');

/**
 * In this file you can extend the AclBase.
 *
 * @package		cake
 * @subpackage	cake.cake.libs.controller.components.dbacl
 */

class DB_ACL extends AclBase {

/**
 * Enter description here...
 *
 */
	function __construct() {
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
		$Perms = new ArosAco();
		$Aro = new Aro();
		$Aco = new Aco();

		if ($aro == null || $aco == null) {
			return false;
		}

		$permKeys = $this->_getAcoKeys($Perms->loadInfo());
		$aroPath = $Aro->getPath($aro);
		$tmpAcoPath = $Aco->getPath($aco);

		if ($tmpAcoPath === null) {
			return false;
		}

		$tmpAcoPath = array_reverse($tmpAcoPath);
		$acoPath = array();

		if ($action != '*' && !in_array('_' . $action, $permKeys)) {
			trigger_error('ACO permissions key "' . $action . '" does not exist in DB_ACL::check()', E_USER_NOTICE);
			return false;
		}

		foreach($tmpAcoPath as $a) {
			$acoPath[] = $a['Aco']['id'];
		}

		for($i = count($aroPath) - 1; $i >= 0; $i--) {
			$perms = $Perms->findAll(array(
				'ArosAco.aro_id' => $aroPath[$i]['Aro']['id'],
				'ArosAco.aco_id' => $acoPath), null,
				'Aco.lft desc'
			);

			if ($perms == null || count($perms) == 0) {
				continue;
			} else {
				foreach($perms as $perm) {
					if ($action == '*') {
						// ARO must be cleared for ALL ACO actions
						foreach($permKeys as $key) {
							if (isset($perm['ArosAco'])) {
								if ($perm['ArosAco'][$key] != 1) {
										return false;
								}
							}
						}

						return true;
					} else {
						switch($perm['ArosAco']['_' . $action]) {
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
	function allow($aro, $aco, $action = "*", $value = 1) {
		$Perms = new ArosAco();
		$perms = $this->getAclLink($aro, $aco);
		$permKeys = $this->_getAcoKeys($Perms->loadInfo());
		$save = array();

		if ($perms == false) {
			trigger_error('DB_ACL::allow() - Invalid node', E_USER_WARNING);
			return false;
		}

		if (isset($perms[0])) {
			$save = $perms[0]['ArosAco'];
		}

		if ($action == "*") {
			$permKeys = $this->_getAcoKeys($Perms->loadInfo());

			foreach($permKeys as $key) {
				$save[$key] = $value;
			}
		} else {
			if (in_array('_' . $action, $permKeys)) {
				$save['_' . $action] = $value;
			} else {
				trigger_error('DB_ACL::allow() - Invalid ACO action', E_USER_WARNING);
				return false;
			}
		}

		$save['aro_id'] = $perms['aro'];
		$save['aco_id'] = $perms['aco'];

		if ($perms['link'] != null && count($perms['link']) > 0) {
			$save['id'] = $perms['link'][0]['ArosAco']['id'];
		}
		return $Perms->save(array('ArosAco' => $save));
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
 * Get an ARO object from the given id or alias
 *
 * @param mixed $id
 * @return Aro
 */
	function getAro($id = null) {
		return $this->__getObject($id, 'Aro');
	}
/**
 * Get an ACO object from the given id or alias
 *
 * @param mixed $id
 * @return Aco
 */
	function getAco($id = null) {
		return $this->__getObject($id, 'Aco');
	}
/**
 * Private method
 *
 */
	function __getObject($id = null, $object) {
		if ($id == null) {
			trigger_error('Null id provided in DB_ACL::get' . $object, E_USER_WARNING);
			return null;
		}

		$obj = new $object;

		if (is_numeric($id)) {
			$key = 'user_id';
			if ($object == 'Aco') {
				$key = 'object_id';
			}

			$conditions = array($object . '.' . $key => $id);
		} else {
			$conditions = array($object . '.alias' => $id);
		}

		$tmp = $obj->find($conditions);
		$obj->id = $tmp[$object]['id'];
		return $obj;
	}
/**
 * Get an array of access-control links between the given Aro and Aco
 *
 * @param mixed $aro
 * @param mixed $aco
 * @return array
 */
	function getAclLink($aro, $aco) {
		$Aro = new Aro();
		$Aco = new Aco();
		$Link = new ArosAco();

		$obj = array();
		$obj['Aro'] = $Aro->find($Aro->_resolveID($aro));
		$obj['Aco'] = $Aco->find($Aco->_resolveID($aco));
		$obj['Aro'] = $obj['Aro']['Aro'];
		$obj['Aco'] = $obj['Aco']['Aco'];

		if ($obj['Aro'] == null || count($obj['Aro']) == 0 || $obj['Aco'] == null || count($obj['Aco']) == 0) {
			return false;
		}

		return array(
			'aro' => $obj['Aro']['id'],
			'aco'  => $obj['Aco']['id'],
			'link' => $Link->findAll(array(
				'ArosAco.aro_id' => $obj['Aro']['id'],
				'ArosAco.aco_id' => $obj['Aco']['id']
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
		$keys = $keys->value;

		foreach($keys as $key) {
			if ($key['name'] != 'id' && $key['name'] != 'aro_id' && $key['name'] != 'aco_id') {
				$newKeys[] = $key['name'];
			}
		}
		return $newKeys;
	}
}

?>