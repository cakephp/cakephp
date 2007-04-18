<?php
/* SVN FILE: $Id$ */

/**
 * This is core configuration file.
 *
 * Use it to configure core behaviour ofCake.
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
 * @subpackage		cake.cake.libs.controller.componenets.dbacl
 * @since			CakePHP(tm) v 0.2.9
 * @version			$Revision$
 * @modifiedby		$LastChangedBy$
 * @lastmodified	$Date$
 * @license			http://www.opensource.org/licenses/mit-license.php The MIT License
 */
if (!defined('ACL_DATABASE')) {
	define('ACL_DATABASE', 'default');
}

uses('controller' . DS . 'components' . DS . 'acl_base');
uses('controller' . DS . 'components' . DS . 'dbacl' . DS . 'models' . DS . 'aclnode');
uses('controller' . DS . 'components' . DS . 'dbacl' . DS . 'models' . DS . 'aco');
uses('controller' . DS . 'components' . DS . 'dbacl' . DS . 'models' . DS . 'acoaction');
uses('controller' . DS . 'components' . DS . 'dbacl' . DS . 'models' . DS . 'aro');
uses('controller' . DS . 'components' . DS . 'dbacl' . DS . 'models' . DS . 'permission');

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
		$Perms = new Permission();
		$Aro = new Aro();
		$Aco = new Aco();

		if ($aro == null || $aco == null) {
			return false;
		}

		$permKeys = $this->_getAcoKeys($Perms->loadInfo());
		$aroPath = $Aro->node($aro);
		$acoPath = new Set($Aco->node($aco));

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

		for($i = count($aroPath) - 1; $i >= 0; $i--) {
			$perms = $Perms->findAll(
				array(
					'Permission.aro_id' => $aroPath[$i]['Aro']['id'],
					'Permission.aco_id' => $acoPath->extract('{n}.Aco.id')
				),
				null, array('Aco.lft' => 'desc'), null, null, 0
			);

			if (empty($perms)) {
				continue;
			} else {
				foreach(Set::extract($perms, '{n}.Permission') as $perm) {
					if ($action == '*') {
						// ARO must be cleared for ALL ACO actions
						foreach($permKeys as $key) {
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
	function allow($aro, $aco, $action = "*", $value = 1) {
		$Perms = new Permission();
		$perms = $this->getAclLink($aro, $aco);
		$permKeys = $this->_getAcoKeys($Perms->loadInfo());
		$save = array();

		if ($perms == false) {
			trigger_error(__('DB_ACL::allow() - Invalid node', true), E_USER_WARNING);
			return false;
		}

		if (isset($perms[0])) {
			$save = $perms[0]['Permission'];
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
				trigger_error(__('DB_ACL::allow() - Invalid ACO action', true), E_USER_WARNING);
				return false;
			}
		}

		$save['aro_id'] = $perms['aro'];
		$save['aco_id'] = $perms['aco'];

		if ($perms['link'] != null && count($perms['link']) > 0) {
			$save['id'] = $perms['link'][0]['Permission']['id'];
		}
		return $Perms->save(array('Permission' => $save));
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
		trigger_error(__('DB_ACL::getAro() - Usage deprecated.  Use AclComponent::$Aro::node().', true), E_USER_WARNING);
		return $this->__getObject($id, 'Aro');
	}
/**
 * Get an ACO object from the given id or alias
 *
 * @param mixed $id
 * @return Aco
 */
	function getAco($id = null) {
		trigger_error(__('DB_ACL::getAco() - Usage deprecated.  Use AclComponent::$Aco::node().', true), E_USER_WARNING);
		return $this->__getObject($id, 'Aco');
	}
/**
 * Private method
 *
 */
	function __getObject($id = null, $object) {
		if ($id == null) {
			trigger_error(__('Null id provided in DB_ACL::get', true) . $object, E_USER_WARNING);
			return null;
		}

		$obj = new $object;

		if (is_numeric($id)) {
			$conditions = array("{$object}.foreign_key" => $id);
		} else {
			$conditions = array("{$object}.alias" => $id);
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
		$Link = new Permission();

		$obj = array();
		$obj['Aro'] = $Aro->node($aro);
		$obj['Aco'] = $Aco->node($aco);

		if (empty($obj['Aro']) || empty($obj['Aco'])) {
			return false;
		}

		return array(
			'aro' => Set::extract($obj, 'Aro.0.Aro.id'),
			'aco'  => Set::extract($obj, 'Aco.0.Aco.id'),
			'link' => $Link->findAll(array(
				'Permission.aro_id' => Set::extract($obj, 'Aro.0.Aro.id'),
				'Permission.aco_id' => Set::extract($obj, 'Aco.0.Aco.id')
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

		foreach($keys as $key) {
			if (!in_array($key, array('id', 'aro_id', 'aco_id'))) {
				$newKeys[] = $key;
			}
		}
		return $newKeys;
	}
}

?>